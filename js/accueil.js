var currentTab = 0; // Current tab is set to be the first tab (0)
var key = ""; // par défaut on affiche tout
showTab(currentTab); // Display the current tab

function showTab(n) {
    // This function will display the specified tab of the form ...
    var x = document.getElementsByClassName("tab" + key);
    x[n].style.display = "block";
    // ... and fix the Previous/Next buttons:
    if (n == 0) {
        document.getElementById("prevBtn").style.display = "none";
    } else {
        document.getElementById("prevBtn").style.display = "inline";
    }
    if (n == (x.length - 1)) {
        document.getElementById("nextBtn").innerHTML = "Submit";
    } else {
        document.getElementById("nextBtn").innerHTML = "Next";
    }
    // ... and run a function that displays the correct step indicator:
    if (key != "") { // on sait sur quelle série on est
        fixStepIndicator(n);
    }
}

function nextPrev(deltaN) {
    // 1. Utilisateur connu ou non ?
    if (currentTab == 0) {
        // Pour afficher les bons éléments en fonction de connu ou pas
        // Requete avec l'email
        // Utilisateur déjà dans la DB
        if (true) {
            key = "LogIn";
        } else {
            key = "SignUp";
        }
        // on active les cercles 
        $("#circles" + key).css('display', 'block');
    }
    // 2. Connu => bon mdp ? Pas connu => continuer
    if (key = "SignUp" && currentTab == 1) {
        // Requete avec le mot de passe : bons identifiants ?
        if (true) {
            key = "LogIn";
            // sortir de là
        } else {
            // mot de passe incorrect
        }
    }    
    // This function will figure out which tab to display
    var x = document.getElementsByClassName("tab" + key);
    // Exit the function if any field in the current tab is invalid:
    if (deltaN == 1 && !validateForm()) return false;
    // Hide the current tab:
    x[currentTab].style.display = "none";
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
        document.getElementsByClassName("step")[currentTab].className += " finish";
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