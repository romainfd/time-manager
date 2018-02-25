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

// PARTICIPANTS A UN EVENT AVEC UNE REPONSE PRECISE
// idEvent : id de l'event concerné
// participation : 'true'/'false'/'maybe'
//      @return liste des participants avec statut
//              nobody si personne
//              notlogged si pas connecté
//              error si pbm   

// On vérifie que la personne est connectée ou non.
if (!isset($_SESSION['email'])) {
    $msg = array('notlogged' => 'Vous n\'êtes pas connecté');
    echo json_encode($msg);
    exit();
}

// Si on a bien reçu par POST les champs input login et password
if (!(empty($_POST['idEvent']) || empty($_POST['participation'])) === TRUE) {
    // On assainit les données
    $idEvent = filter_input(INPUT_POST, 'idEvent', FILTER_SANITIZE_SPECIAL_CHARS);
    $participation = filter_input(INPUT_POST, 'participation', FILTER_SANITIZE_SPECIAL_CHARS);

    //// CONNEXION A LA BASE DE DONNEES
    require 'database.class.php';
    $dbh = Database::connect();
    if (!$dbh) {
        $msg = array('error' => 'Connexion au serveur impossible');
        echo json_encode($msg);
        exit();
    }
    // Recherche des gens ayant répondu à l'event et avec le bon statut
    $query = "SELECT u.idUser, u.email, u.prenom, u.nom FROM `participationsevents` p
                JOIN utilisateurs u ON p.emailParticipe = u.email
                WHERE p.idEvent = ? AND p.participation = ?";
    $sth = $dbh->prepare($query);
    $sth->execute(array($idEvent, $participation));
    if ($sth->rowCount() > 0) {
        $msg = array('success' => 'Récupération réussie');
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        array_push($msg, $result);
    } else { // personne n'a répondu
        $msg = array('nobody' => 'Personne n\'a répondu '.$participation);
    }
} else {
    $msg = array('error' => 'Un champ n\'est pas rempli');
}

echo json_encode($msg);