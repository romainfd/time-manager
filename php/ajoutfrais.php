<?php
/* Entête obligatoirement présente dans tous vos webservices */
ini_set('session.use_cookies', 0);
ini_set('session.use_only_cookies', 0);
ini_set('session.use_trans_sid', 1);
// Attention au session_name
session_name('gmba');
session_start();

header('Content-Type: text/html; charset=utf-8');
header('Content-type: application/json; charset=utf-8');
header('access-control-allow-origin: *');
/* Fin de l'entête obligatoire */

// On vérifie que la personne est connectée ou non.
if (!isset($_SESSION['email'])) {
    $msg = array('notlogged' => 'Vous n\'êtes pas connecté');
    echo json_encode($msg);
    exit();
}

// CREATION DE TACHE
/* @return
    * array avec success = Connexion réussie, 
    * array avec error = un des champs pas rempli OU pbm d'insert/update du sql
    * array avec errorAccess = pas les droits de modif ! (quelqu'un qui veut hacker)
*/

// Si on a bien reçu par POST les champs input
if (!(empty($_POST['idprojet']) || empty($_POST['date']) || empty($_POST['idcategorie']) || empty($_POST['montant'])) === TRUE) {
    // On assainit les données
    $idprojet = filter_input(INPUT_POST, 'idprojet', FILTER_SANITIZE_SPECIAL_CHARS);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_SPECIAL_CHARS);
    $idcategorie = filter_input(INPUT_POST, 'idcategorie', FILTER_SANITIZE_SPECIAL_CHARS);
    $montant = filter_input(INPUT_POST, 'montant', FILTER_SANITIZE_SPECIAL_CHARS);
    if (empty($_POST['idsub'])) {
        $idsub = NULL;
    } else {
        $idsub = filter_input(INPUT_POST, 'idsub', FILTER_SANITIZE_SPECIAL_CHARS);
    }

    //// CONNEXION A LA BASE DE DONNEES
    require 'database.class.php';
    $dbh = Database::connect();
    if (!$dbh) {
        $msg = array('error' => 'Connexion au serveur impossible');
        echo json_encode($msg);
        exit();
    }

    $query = "INSERT INTO frais (idprojet, iduser, idcategorie, date, montant, idsub) 
                VALUES (?,?,?,?,?,?)";
    $sth = $dbh->prepare($query);
    $sth->execute(array($idprojet, $_SESSION['iduser'], $idcategorie, $date, $montant, $idsub));

    if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
        $msg = array('success' => 'Création reussie !');
    } else {
        $msg = array('error' => 'Echec lors de la création de votre event');
    }
} else {
    $msg = array('error' => 'Un champ n\'est pas rempli');
}

echo json_encode($msg);