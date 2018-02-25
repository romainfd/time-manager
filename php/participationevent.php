<?php
/* Entête obligatoirement présente dans tous vos webservices */
ini_set('session.use_cookies', 0);
ini_set('session.use_only_cookies', 0);
ini_set('session.use_trans_sid', 1);
// Attention au session_name
session_name('name');
session_start();

header('Content-Type: text/html; charset=utf-8');
header('Content-type: application/json; charset=utf-8');
header('access-control-allow-origin: *');
/* Fin de l'entête obligatoire */

// PARTICIPATION A UN EVENT 
// Avec l'idEvent et (yes/no) on met à jour la table de participation en ajoutant/retirant

/* @return
    * array avec success = Connexion réussie, 
    * array avec error = un des champs pas rempli
*/


// Si on a bien reçu par POST les champs input login et password
if (!(empty($_POST['participation']) || empty($_POST['idEvent'])) === TRUE) {
    // On assainit les données
    $participation = filter_input(INPUT_POST, 'participation', FILTER_SANITIZE_SPECIAL_CHARS);    
    $idEvent = filter_input(INPUT_POST, 'idEvent', FILTER_SANITIZE_SPECIAL_CHARS);

    //// CONNEXION A LA BASE DE DONNEES
    require 'database.class.php';
    $dbh = Database::connect();
    if (!$dbh) {
        $msg = array('error' => 'Connexion au serveur impossible');
        echo json_encode($msg);
        exit();
    }

    // si on a déjà une entrée (ie meme mail et event), on se contente de mettre à jour la participation
    $query = "INSERT INTO participationsevents (idEvent, emailParticipe, participation) VALUES (?,?,?) ON DUPLICATE KEY UPDATE participation = ?";
    $sth = $dbh->prepare($query);

    if ($participation == 'yes') { // on regarde si la participation si elle n'y était pas déjà
        $sth->execute(array($idEvent, $_SESSION['email'], 'yes', 'yes'));
    } else if ($participation == 'no') { // on enlève notre participation
        $sth->execute(array($idEvent, $_SESSION['email'], 'no', 'no'));
    } else if ($participation == 'maybe') {
        $sth->execute(array($idEvent, $_SESSION['email'], 'maybe', 'maybe'));
    }
    if ($sth->rowCount() >= 1) { // on a bien mis à jour
        $msg = array('success' => 'Mise à jour reussie');
        require 'util.class.php';
        Util::updateCompt($dbh, "updatePart");
    } else {
        $msg = array('error' => 'Echec lors de la mise à jour de votre participation');
    }
} else {
    $msg = array('error' => 'Il manque des informations');
}

echo json_encode($msg);