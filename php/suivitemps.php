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

// RECHERCHE/AFFICHAGE DES HEURES	

// On vérifie que la personne est connectée ou non.
if (!isset($_SESSION['email'])) {
	$msg = array('notlogged' => 'Vous n\'êtes pas connecté');
	echo json_encode($msg);
	exit();
}

// Si on a bien reçu par POST les champs input
// CAS 1 : on veut le tableau complet entre 2 dates
if (!(empty($_POST['dateDebut']) || empty($_POST['dateFin'])) === TRUE) {
    // On assainit les données
    $dateDebut = filter_input(INPUT_POST, 'dateDebut', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateFin = filter_input(INPUT_POST, 'dateFin', FILTER_SANITIZE_SPECIAL_CHARS);

	// On renvoie les événements de sa startup
	require 'database.class.php';
	$dbh = Database::connect();
	// On récupère directement tous les événements que l'on a créé
	$query = "SELECT idprojet, date, SUM(heures) as temps FROM `taches` 
				WHERE iduser = ? AND date >= ? AND date <= ?
				GROUP BY idprojet, date
				ORDER BY idprojet ASC"; // pour matcher le téléchargement des projets avant
	$sth = $dbh->prepare($query);
	$sth->execute(array($_SESSION['iduser'], $dateDebut, $dateFin));

	if ($sth->rowCount() > 0) {
	    $result = array('success' => 'Récupération réussie');
	    $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
	    $result['taches'] = $resultSQL;
	    // Puis on récupère le total des heures par jour
	    $query = "SELECT date, SUM(heures) as sommeHeures FROM `taches` 
					WHERE iduser = ? AND date >= ? AND date <= ?
					GROUP BY date;";
		$sth = $dbh->prepare($query);
		$sth->execute(array($_SESSION['iduser'], $dateDebut, $dateFin));

		if ($sth->rowCount() > 0) {
		    $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
		    $result['sommeHeures'] = $resultSQL;
		} else {
			// on s'en fiche
		}
	} else {
	    $result = array('nothing' => 'Pas de tâches trouvées pour vous au cours de cette période');
	}
// CAS 2 : on veut la liste précise des taches pour un jour
} else if (!empty($_POST['date']) === TRUE) {
    // On assainit les données
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_SPECIAL_CHARS);

	// On renvoie les événements de sa startup
	require 'database.class.php';
	$dbh = Database::connect();
	// On récupère directement tous les événements que l'on a créé
	$query = "SELECT taches.idprojet, idtache, date, cirable, idsub, nom, heures, description FROM `taches`
				JOIN projets ON projets.idprojet = taches.idprojet 
				WHERE iduser = ? AND date = ?
				ORDER BY idprojet ASC,heures DESC";
	$sth = $dbh->prepare($query);
	$sth->execute(array($_SESSION['iduser'], $date));

	if ($sth->rowCount() > 0) {
	    $result = array('success' => 'Récupération réussie');
	    $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
	    $result['tachesPrecis'] = $resultSQL;
	} else {
	    $result = array('nothing' => 'Pas de tâches trouvées pour vous au cours de cette période');
	}
} else {
    $result = array('error' => 'Veuillez choisir une date valide');
}
echo json_encode($result);