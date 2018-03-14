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

$(document).ready(function() {
    // on initialise le tableau vide
    var json = {
        dates: ['lundi', 'mardi', 'mercredi'],
        projets: [{
                idprojet: 1,
                nom: "guidon"
            }, {
                idprojet: 2,
                nom: "cadre"
            }]
    };
    $.get(root + "templates/table.html", function(templates) {
        var page = $(templates).html();
        page = Mustache.render(page, json);
        $("#tableSuiviTemps").html(page);
    });
});

$("#dateSuivi").change(function() {
    var date = $("#dateSuivi").val();
    var dateDebut;
    console.log(date);
    $.post(server + "suivitemps.php" + (sessionStorage['session_id'] === undefined ? "" : "?gmba=" + sessionStorage['session_id']), { date: date }, function(messageJson) {
        // gestion des erreurs
        if (messageJson.error) {
            $("#texteModal").html("Erreur : "+messageJson.error);
            $('#myModal').modal('show');                   
        // gestion de la réussite
        } else if (messageJson.success) {
            // on récupère les infos de l'utilisateur
            if (messageJson[0][0]) {
                sessionStorage['user'] = JSON.stringify(messageJson[0][0]);
                // on redirige vers la page employe
                window.location.replace("employe.html");
            }
        }
    });
})