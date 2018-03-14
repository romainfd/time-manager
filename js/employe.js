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

function validateForm() {
    var valid = true;
    // check all inputs
    if ($("#date").val() == "") {
        valid = false;
        $("#date").addClass("invalid");
    }
    if ($('#projet').val() == "") {
        valid = false;
        $("#projet").addClass("invalid");
    }
    if ($('#heures').val() == 0) {
        valid = false;
        $("#heures").addClass("invalid");
    }
    return valid; // return the valid status
}

$(document).ready(function() {
    // on connecte l'utilisateur pour l'envoi de ses temps
    $("#formAjoutTemps").attr('action', 'php/ajouttemps.php' + (sessionStorage['session_id'] === undefined ? "" : "?name=" + sessionStorage['session_id']));

    // on affiche la liste des PROJETS de la startup
    $.getJSON(server + "projets.php?name=" + sessionStorage['session_id'], function(messageJson) {
        if (messageJson.error) {
            console.log(messageJson.error);
            return;
        } else if (messageJson.notlogged) {
            alert(messageJson.notlogged);
            window.location.replace("accueil.html");
        } else if (messageJson.success) {
            projets = messageJson[0];
            $.get(root + "templates/choixProjets.html", function(templates) {
                var page = $(templates).html();
                page = Mustache.render(page, projets);
                $("#projet").html(page);
            });
        }
    }, "html");

    // on affiche la liste des SUBVENTIONS de la startup
    $.getJSON(server + "subs.php?name=" + sessionStorage['session_id'], function(messageJson) {
        if (messageJson.error) {
            console.log(messageJson.error);
            return;
        } else if (messageJson.notlogged) {
            // inutile car déjà avec projets.php
            // alert(messageJson.notlogged);
            // window.location.replace("accueil.html");
        } else if (messageJson.success) {
            subs = messageJson[0];
            $.get(root + "templates/choixSubs.html", function(templates) {
                var page = $(templates).html();
                page = Mustache.render(page, subs);
                $("#subvention").html(page);
            });
        }
    }, "html");

    // Envoi du formulaire
    $('#formAjoutTemps').on('submit', function(e) {
        e.preventDefault();
        if (!validateForm()) {
            $("#texteModal").html("Un des champs obligatoire n'est pas rempli.");
            return;
        }
        $.ajax({
            url: $(this).attr('action') || window.location.pathname,
            type: "POST",
            data: $(this).serialize(),
            success: function(data) {
                if (data.success) {
                    $("#texteModal").html(data.success);
                    document.getElementById("formAjoutTemps").reset();
                } else {
                    $("#texteModal").html(data.error);
                }
            },
            error: function(jXHR, textStatus, errorThrown) {
                $("#texteModal").html("Erreur de connexion "+ errorThrown);
            }
        });
    });
});