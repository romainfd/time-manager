var minCaseWidth = 135; // la taille minimale en pixels des cases du tableau => pour déterminer le nombre de cases en fonction de l'affichage
var n; 
var debut, fin;// le nombre de cases
var date;
var tableTitres = {
    dates: [],
    projets: [{
        idprojet: 1,
        nom: "guidon"
    }, {
        idprojet: 2,
        nom: "cadre"
    }]
};

$(document).ready(function() {
    // on initialise le tableau vide
    n = parseInt($(".regForm").width() / minCaseWidth);
    if (n > 5) { n = 5; } // au plus une semaine sur un écran
    // on répartit les colonnes autour de la date
    if (n % 2 == 0) {
        debut = n / 2; // on prefere avant car il y a rarement qqchose demain...
        fin = n / 2 - 1;
    } else {
        debut = parseInt(n / 2);
        fin = parseInt(n / 2);
    }
    // par defaut, on centre sur aujourd'hui
    date = (new Date()).toISOString().substr(0,10);
    $("#dateSuivi").val(date);
    displayDateInfo();
});

// Gestion des dates
Date.prototype.addDays = function(days) {
  var dat = new Date(this.valueOf());
  dat.setDate(dat.getDate() + days);
  return dat;
}

// pour mettre lundi sur 1 et non plus dimanche
Date.prototype.gDay = function() {
  return (this.getDay() - 1 + 7)%7 + 1;
}

// avancer le display
function moveDisplay(direction) {
    var dateArithm = new Date(date);
    if (direction > 0) {
        // soit on touchait déjà à vendredi
        if (dateArithm.gDay() + fin == 5) {
            // on avance d'une semaine et on se colle à lundi
            dateArithm = dateArithm.addDays(7 - (5 - n));
        } else { // on avance juste en fonction de notre display
            dateArithm = dateArithm.addDays(Math.min(n, 5-n));
        }
    } else {
        // soit on touchait déjà à lundi
        if (dateArithm.gDay() - debut == 1) {
            // on recule d'une semaine et on se colle à vendredi
            dateArithm = dateArithm.addDays(-7 + (5 - n));
        } else { // on recule juste en fonction de notre display (de n si on a la place, sinon juste pour toucher lundi)
            dateArithm = dateArithm.addDays(-Math.min(n, 5-n));
        }        
    }
    date = dateArithm.toISOString().substr(0,10);
    $("#dateSuivi").val(date);
    displayDateInfo();
}

$("#dateSuivi").change(displayDateInfo);

function displayDateInfo() {
    date = $("#dateSuivi").val();
    if (date == "") return;
    // On affiche tjs centré sur une semaine
    var dateArithm = new Date(date);
    // Cas 1 : on s'étend sur plus d'une semaine => on garde la semaine de dateArithm (sachant qu'on fait au plus 5 en taille)
    if (dateArithm.addDays(fin).gDay() < dateArithm.addDays(-debut).gDay()) {
        if (dateArithm.gDay() < 3) { // on avance
            date = (dateArithm.addDays(1 + (debut - dateArithm.gDay()))).toISOString().substr(0,10);
        } else { // on recule (date + fin doit etre <= 5)
            date = (dateArithm.addDays(-(dateArithm.gDay() + fin - 5))).toISOString().substr(0,10);
        }
    } else if (dateArithm.addDays(fin).gDay() > 5) {
    // Cas 2 : on est sur la meme semaine mais la fin déborde sur le weekend => on recule
        date = (dateArithm.addDays(-(dateArithm.gDay() + fin - 5))).toISOString().substr(0,10);
    }
    $("#dateSuivi").val(date);
    // on récupère la liste des PROJETS de la startup
    $.getJSON(server + "projets.php?gmba=" + sessionStorage['session_id'], function(messageJson) {
        if (messageJson.error) {
            console.log(messageJson.error);
            return;
        } else if (messageJson.notlogged) {
            alert(messageJson.notlogged);
            window.location.replace("accueil.html");
        } else if (messageJson.success) {
            tableTitres.projets = messageJson[0]; // on récupère les projets

            // On choisit les DATES
            tableTitres.dates = (new dateObj(date)).datesSpan(-debut, fin);
            var dateDebut = tableTitres.dates[0].iddate;
            var dateFin = tableTitres.dates[tableTitres.dates.length - 1].iddate;
            // On affiche le tableau initialisé vide
            $.get(root + "templates/table.html", function(templates) {
                var page = $(templates).html();
                page = Mustache.render(page, tableTitres);
                $("#tableSuiviTemps").html(page);
            });

            // On récupère les heures à afficher
            $.post(server + "suivitemps.php" + (sessionStorage['session_id'] === undefined ? "" : "?gmba=" + sessionStorage['session_id']), { dateDebut: dateDebut, dateFin: dateFin }, function(messageJson) {
                // gestion des erreurs
                if (messageJson.error) {
                    $("#texteModal").html("Erreur : " + messageJson.error);
                    $('#myModal').modal('show');
                    // gestion de la réussite
                } else if (messageJson.success) {
                    // on remplit le tableau
                    var elem;
                    for (var i = 0; i < messageJson['taches'].length; i++) {
                        elem = messageJson['taches'][i]; 
                        $("tr#"+elem.idprojet+" div."+elem.date).html(elem.temps);
                    }
                    // on remplit aussi la ligne des sommes
                    for (var i = 0; i < messageJson['sommeHeures'].length; i++) {
                        elem = messageJson['sommeHeures'][i]; 
                        $("div#somme"+elem.date).html(elem.sommeHeures);
                    }
                }
            });
        }
    }, "html");
}