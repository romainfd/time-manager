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
    if ($('#categorie').val() == "") {
        valid = false;
        $("#categorie").addClass("invalid");
    }
    if ($('#montant').val() == 0) {
        valid = false;
        $("#montant").addClass("invalid");
    }
    return valid; // return the valid status
}

$(document).ready(function() {
    // on connecte l'utilisateur pour l'envoi de ses temps
    $("#formAjoutFrais").attr('action', server+'/ajoutfrais.php' + (sessionStorage['session_id'] === undefined ? "" : "?gmba=" + sessionStorage['session_id']));

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
            });
        }
    }, "html");

    // on affiche la liste des CATEGORIES de FRAIS de la startup
    $.getJSON(server + "categoriesfrais.php?gmba=" + sessionStorage['session_id'], function(messageJson) {
        if (messageJson.error) {
            console.log(messageJson.error);
            return;
        } else if (messageJson.notlogged) {
            alert(messageJson.notlogged);
            window.location.replace("accueil.html");
        } else if (messageJson.success) {
            messageJson['accueil'] = "Choisissez une catégorie";
            $.get(root + "templates/choixSelect.html", function(templates) {
                var page = $(templates).html();
                page = Mustache.render(page, messageJson);
                $("#categorie").html(page);
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
            });
        }
    }, "html");

    // Envoi du formulaire
    $('#formAjoutFrais').on('submit', function(e) {
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
                    document.getElementById("formAjoutFrais").reset();
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