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

$("#date").change(function() {
    displayDayPrecise($("#date").val());
});

$(document).ready(function() {
    // on connecte l'utilisateur pour l'envoi de ses temps
    $("#formAjoutTemps").attr('action', server + 'ajouttemps.php' + (sessionStorage['session_id'] === undefined ? "" : "?gmba=" + sessionStorage['session_id']));

    // on affiche la liste des PROJETS de la startup
    $.getJSON(server + "projets.php?gmba=" + sessionStorage['session_id'], function(messageJson) {
        if (messageJson.error) {
            console.log(messageJson.error);
            return;
        } else if (messageJson.notlogged) {
            alert(messageJson.notlogged);
            window.location.replace("accueil.html");
        } else if (messageJson.success) {
            messageJson['accueil'] = "Choisissez un projet";
            $.get(root + "templates/choixSelect.html", function(templates) {
                var page = $(templates).html();
                page = Mustache.render(page, messageJson);
                $("#projet").html(page);

                // en callback, on récupère l'eventuel idprojet pour préremplir
                if (params.idprojet) {
                    $("#projet").val(params.idprojet);
                }
            });
        }
    }, "html");

    // on affiche la liste des SUBVENTIONS de la startup
    $.getJSON(server + "subs.php?gmba=" + sessionStorage['session_id'], function(messageJson) {
        if (messageJson.error) {
            console.log(messageJson.error);
            return;
        } else if (messageJson.notlogged) {
            // inutile car déjà avec projets.php
            // alert(messageJson.notlogged);
            // window.location.replace("accueil.html");
        } else if (messageJson.success) {
            messageJson['accueil'] = "Choisissez une subvention";
            $.get(root + "templates/choixSelect.html", function(templates) {
                var page = $(templates).html();
                page = Mustache.render(page, messageJson);
                $("#subvention").html(page);
                // en callback, on récupère l'eventuel subv pour préremplir
                if (params.subvention) {
                    $("#subvention").val(params.subvention);
                }
            });
        }
    }, "html");

    var params = getAllUrlParams();
    if (params != {}) {
        if (params.idtache) {
            $("#idtache").val(params.idtache);
            $(".regForm h1:first").html("Modification");
        }
        if (params.date) {
            $("#date").val(params.date);
            displayDayPrecise(params.date);
        }
        if (params.heures) {
            $("#heures").val(params.heures);
        }
        if (params.description) {
            $("#description").text(params.description);
        }
        if (params.cirable == 1) {
            $("#cirable").prop('checked', true);
        }
    }


    // Envoi du formulaire
    $('#formAjoutTemps').on('submit', function(e) {
        e.preventDefault();
        if (!validateForm()) {
            $("#texteModal").html("Un des champs obligatoire n'est pas rempli.");
            return;
        }
        $.ajax({
            url: $(this).attr('action'),
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
                $("#texteModal").html("Erreur de connexion " + errorThrown);
            }
        });
    });
});