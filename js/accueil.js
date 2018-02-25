var server = 'php/';

var currentTab = 0; // Current tab is set to be the first tab (0)
var key = ""; // par défaut on affiche tout
showTab(currentTab); // Display the current tab

function showTab(n) {
    // This function will display the specified tab of the form ...
    var x = document.getElementsByClassName("tab" + key);
    x[n].style.display = "block";
    // ... and fix the Previous/Next buttons:
    if (n == 0) {
        $("#prevBtn").css('display', "none");
    } else {
        $("#prevBtn").css("display", "inline");
    }
    console.log(n);
    if (n == (x.length - 1)) {
        if (key == "SignUp") {
            $("#nextBtn").html("Créer");
        } else {
            $("#nextBtn").html("Connexion");
        }
    } else {
        $("#nextBtn").html("Suivant");
    }
    // ... and run a function that displays the correct step indicator:
    if (key != "") { // on sait sur quelle série on est
        fixStepIndicator(n);
    }
}

function nextPrev(deltaN) {
    // 1. Utilisateur connu ou non ?
    if (currentTab == 0) {
        // Pour afficher les bons éléments en fonction de connu ou pas => Requete avec l'email (utilisateur déjà dans la DB)
        var email = $("#email").val();
        $.post(server + "checkmail.php", { email: email }, function(messageJson) {
            var messageAffiche = "";
            if (messageJson.error) {
                console.log("Error : " + messageJson.error);
            } else { // gestion de la réussite
                // on enregistre l'email, ça peut servir
                sessionStorage.setItem('email', email);
                // on garde l'ID de session PHP si elle existe
                if (messageJson.session_id) {
                    sessionStorage['session_id'] = messageJson.session_id;
                }
                if (messageJson.notexist) {
                    key = "SignUp";
                } else if (messageJson.success) { 
                    key = "LogIn";
                }
                // on active les cercles 
                $("#circles" + key).css('display', 'block');
                // on affiche la suite
                displayNextTab(deltaN);
            }
        });
    }
    // 2. Connu => bon mdp ? Pas connu => continuer
    else if (deltaN == 1 && currentTab == 1) {
        if (key == "SignUp") { 
            // on passe à la suite
            displayNextTab(deltaN);
        } else if (key == "LogIn") { // Requete avec le mot de passe : bons identifiants ?
            var password = $("#password").val();
            $.post(server + "connecter.php" + (sessionStorage['session_id'] === undefined ? "" : "?name=" + sessionStorage['session_id']), { password: password }, function(messageJson) {
                // gestion des erreurs
                if (messageJson.error) {
                    alert("Error : " + messageJson.error);
                    // gestion de la réussite
                } else if (messageJson.success) {
                    // on récupère les infos de l'utilisateur
                    if (messageJson[0][0]) {
                        sessionStorage['user'] = JSON.stringify(messageJson[0][0]);
                        // on redirige vers la page employe
                        console.log("ok");
                        // window.location.replace("employe.html");
                    }
                }
            });
        } else {
            // revenir au début
            nextPrev(-1);
        }
    }
    // on crée notre compte
    else if (deltaN == 1 && currentTab == 2) {
        // on récupère les données
                   var nom = $("#nom").val();
            var prenom = $("#prenom").val();
            var codestartup = $("#codestartup").val();
        var password = $("#password2").val();
        $.post(server + "creerprofil.php" + (sessionStorage['session_id'] === undefined ? "" : "?name=" + sessionStorage['session_id']), { nom: nom, prenom: prenom, codestartup: codestartup, password: password }, function(messageJson) {
            // gestion des erreurs
            if (messageJson.error) {
                alert("Error : " + messageJson.error);
                // gestion de la réussite
            } else if (messageJson.success) {
                // on stocke les infos de l'utilisateur
                sessionStorage['user'] = JSON.stringify({
                    nom: nom,
                    prenom: prenom,
                    iduser: messageJson.iduser,
                    email: $("#email").val(),
                    idsu: messageJson.idsu,
                    coutcir: 0,
                    coutsub: 0
                });
                // on redirige vers la page employe
                console.log("ok");
                // window.location.replace("employe.html");
            }
        });

    } else { // deltaN == -1
        displayNextTab(deltaN);
    }
}

function displayNextTab(deltaN) {
    // This function will figure out which tab to display
    var x = document.getElementsByClassName("tab" + key);
    // Exit the function if any field in the current tab is invalid:
    if (deltaN == 1 && !validateForm()) return false;
    // Hide the current tab:
    x[currentTab].style.display = "none";
    // Si on retourne au début, on cache les cercles
    if (currentTab == 1 && deltaN == -1) {
        // on désactive les cercles 
        $("#circles" + key).css('display', 'none');
    }
    // Increase or decrease the current tab by 1:
    currentTab = currentTab + deltaN;
    // Display the correct tab:
    showTab(currentTab);
}

function validateForm() {
    // This function deals with validation of the form fields
    var x, y, i, valid = true;
    x = document.getElementsByClassName("tab" + key);
    y = x[currentTab].getElementsByTagName("input");
    // A loop that checks every input field in the current tab:
    for (i = 0; i < y.length; i++) {
        // If a field is empty...
        if (y[i].value == "") {
            // add an "invalid" class to the field:
            y[i].className += " invalid";
            // and set the current valid status to false:
            valid = false;
        }
    }
    // If the valid status is true, mark the step as finished and valid:
    if (valid) {
        $("#circles" + key + " .step")[currentTab].className += " finish";
    }
    return valid; // return the valid status
}

function fixStepIndicator(n) {
    // This function removes the "active" class of all steps...
    var i, x = $("#circles" + key + " .step");
    for (i = 0; i < x.length; i++) {
        x[i].className = x[i].className.replace(" active", "");
    }
    //... and adds the "active" class to the current step:
    x[n].className += " active";
}