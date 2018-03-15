switch (document.location.hostname) {
    case 'localhost':
        var rootFolder = 'temporease/';
        break;
    default:
        // for other servers
        var rootFolder = '';
}
var root = window.location.protocol + "//" + window.location.host + "/" + rootFolder;
var server = root + "php/";

var minCaseWidth = 130; // la taille minimale en pixels des cases du tableau => pour déterminer le nombre de cases en fonction de l'affichage
var n; // le nombre de cases
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
});

$("#dateSuivi").change(function() {
    var date = $("#dateSuivi").val();
    if (date == "") return;
    // on répartit les colonnes autour de la date
    var debut, fin;
    if (n % 2 == 0) {
        debut = n / 2;
        fin = n / 2 - 1;
    } else {
        debut = parseInt(n / 2);
        fin = parseInt(n / 2);
    }
    // on récupère la liste des PROJETS de la startup
    $.getJSON(server + "projets.php?gmba=" + sessionStorage['session_id'], function(messageJson) {
        if (messageJson.error) {
            console.log(messageJson.error);
            return;
        } else if (messageJson.notlogged) {
            alert(messageJson.notlogged);
            window.location.replace("accueil.html");
        } else if (messageJson.success) {
            tableTitres.projets = messageJson[0];

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
                    for (var i = 0; i < messageJson[0].length; i++) {
                        elem = messageJson[0][i]; 
                        $("tr#"+elem.idprojet+" td."+elem.date).html(elem.temps);
                    }
                    console.log(messageJson[0][0]);
                    console.log(messageJson[0][1]);
                }
            });
        }
    }, "html");
})