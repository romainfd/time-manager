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

// RECHERCHE/AFFICHAGE DES PROJETS	

// On vérifie que la personne est connectée ou non.
if (!isset($_SESSION['email'])) {
	$msg = array('notlogged' => 'Vous n\'êtes pas connecté');
	echo json_encode($msg);
	exit();
}

// On renvoie les événements de sa startup
require 'database.class.php';
$dbh = Database::connect();
// On récupère directement tous les événements que l'on a créé
$query = "SELECT * FROM projets
            WHERE idsu = ?";
$sth = $dbh->prepare($query);
$sth->execute(array($_SESSION['idsu']));

 if ($sth->rowCount() > 0) {
    $result = array('success' => 'Récupération réussie');
    $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
    array_push($result, $resultSQL);
} else {
    $result = array('error' => 'Pas de projets trouvés pour votre startup');
}

echo json_encode($result);