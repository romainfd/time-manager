// Classe pour gérer les dates
var dateObj = class dateObj {
    // iddate de la forme 2018-03-09
    constructor(iddate) {
        this.iddate = iddate;
        this.dateClean = this.cleanDisplay();
    }

    // on parse 2018-03-09 sur 
    cleanDisplay() {
        var date = new Date(this.iddate);

        var datejour = date.getDate();
        var tabMois = ["Jan.", "Fev.", "Mars", "Avr.", "Mai", "Juin",
            "Juil.", "Août", "Sept.", "Oct.", "Nov.", "Dec."
        ];
        var mois = tabMois[date.getMonth()];
        var tabJour = ["dim.", "lun.", "mar.", "mer.", "jeu.",
            "ven.", "sam."
        ];
        var jour = tabJour[date.getDay()];

        return jour + " " + datejour + " " + mois;
    }

    createByAddDays(nbJours) {
        var newDate = new Date(this.iddate);
        newDate.setDate(newDate.getDate() + nbJours);
        return new dateObj(this.formatDateToIdDate(newDate));
    }

    formatDateToIdDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    /*
    Toutes les dates autour iddate avec -deltaDebut dates avant et deltaFin date après
    ATTENTION : deltaDebut est négatif
     */
    datesSpan(deltaDebut, deltaFin) {
        var dates = [];
        for (var i = deltaDebut; i <= deltaFin; i++) {
            dates.push(this.createByAddDays(i));
        }
        return dates;
    }
}

// MENU
/* Set the width of the side navigation to 250px and the left margin of the page content to 250px and add a black background color to body */
function openNav() {
    $("#menuCross").toggleClass("change");
    $("#menuCross").css("display", "none");
    document.getElementById("mySidenav").style.width = "250px";
    document.getElementById("main").style.marginLeft = "250px";
    //document.body.style.backgroundColor = "rgba(0,0,0,0.4)";
    //document.getElementsByClassName("regForm")[0].style.backgroundColor = "rgba(255,255,255,0.2)";
}

/* Set the width of the side navigation to 0 and the left margin of the page content to 0, and the background color of body to white */
function closeNav() {
    $("#menuCross").toggleClass("change");
    $("#menuCross").css("display", "inline-block");
    document.getElementById("mySidenav").style.width = "0";
    document.getElementById("main").style.marginLeft = "0";
    //document.body.style.backgroundColor = "rgba(241,241,241,1)";
}

// Tableau des heures précises sur une journée
function displayDayPrecise(date, idprojet){
/*  Les 2 font pareil pour le moment (on s'en fout du projet, on affiche toute la journée)
    if (idprojet == null) {
        console.log("Affichage des détails pour la journée "+date);
    } else {
        console.log("Affichage des détails du projet "+idprojet+" pour la journée "+date);
    }*/

    // On récupère les heures à afficher
    $.post(server + "suivitemps.php" + (sessionStorage['session_id'] === undefined ? "" : "?gmba=" + sessionStorage['session_id']), { date: date }, function(messageJson) {
        // gestion des erreurs
        if (messageJson.error) {
            $("#texteModal").html("Erreur : " + messageJson.error);
            $('#myModal').modal('show');
            // gestion de la réussite
        } else if (messageJson.success) {
            // On remplit le template
            $.get(root + "templates/tablePrecis.html", function(templates) {
                var page = $(templates).html();
                page = Mustache.render(page, messageJson);
                $("#tableSuiviTempsPrecis").html(page);
            });
        } else if (messageJson.nothing) {
            $("#tableSuiviTempsPrecis").html("<p style='font-style: italic; color: grey; font-size:12px; margin: 0 0 0 0'>"+
                                                "Vous n'avez pas encore rentré d'heures à cette date."+"</p>");
        }
    }); 
}

// supprime la tache idtache de la DB
function supprimerTache(idtache) {
    $.post(server + "ajouttemps.php?gmba=" + sessionStorage['session_id'],  { delete: 1, idtache: idtache }, function(messageJson) {
        if (messageJson.error) {
            $("#texteModal").html("Erreur : " + messageJson.error);
            $('#myModal').modal('show');
        } else if (messageJson.success) {
            $("#texteModal").html(messageJson.success);
            $('#myModal').modal('show');
        }
    });
}
 
// Fonction pour récupérer les parametres passés dans l'URL
// La description doit toujours etre en derniere
function getAllUrlParams(url) {
  // get query string from url (optional) or window
  var queryString = url ? url.split('?')[1] : window.location.search.slice(1);

  // we'll store the parameters here
  var obj = {};

  // if query string exists
  if (queryString) {

    // stuff after # is not part of query string, so get rid of it
    queryString = queryString.split('#')[0];

    // split our query string into its component parts
    var arr = queryString.split('&');

    for (var i=0; i<arr.length; i++) {
      // separate the keys and the values
      var a = arr[i].split('=');

      // in case params look like: list[]=thing1&list[]=thing2
      var paramNum = undefined;
      var paramName = a[0].replace(/\[\d*\]/, function(v) {
        paramNum = v.slice(1,-1);
        return '';
      });

      // set parameter value (use 'true' if empty)
      var paramValue = typeof(a[1])==='undefined' ? true : a[1];

      // (optional) keep case consistent
      paramName = paramName.toLowerCase();
      paramValue = paramValue.toLowerCase();

      // cas terminal de la description eventuelle
      if (paramName == "description") {
        var index = queryString.indexOf("&description=");
        obj['description']=decodeURIComponent(queryString.substr(index + "?description=".length));
        return obj;
      }

      // if parameter name already exists
      if (obj[paramName]) {
        // convert value to array (if still string)
        if (typeof obj[paramName] === 'string') {
          obj[paramName] = [obj[paramName]];
        }
        // if no array index number specified...
        if (typeof paramNum === 'undefined') {
          // put the value on the end of the array
          obj[paramName].push(paramValue);
        }
        // if array index number specified...
        else {
          // put the value at that index number
          obj[paramName][paramNum] = paramValue;
        }
      }
      // if param name doesn't exist yet, set it
      else {
        obj[paramName] = paramValue;
      }
    }
  }
  return obj;
}