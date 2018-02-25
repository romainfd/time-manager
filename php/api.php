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


// RECUPERATION DU GROUPE CORRESPONDANT A L'APIKEY

/* @return
	* array avec success = Connexion réussie, tous les champs du groupe 
	* array avec error = erreur
*/

// Si on a bien reçu par POST l'apikey
if (!(empty($_POST['apikey'])) === TRUE) {
	// On assainit les données
    $apikey = filter_input(INPUT_POST, 'apikey', FILTER_SANITIZE_SPECIAL_CHARS);

	// CONNEXION A LA BASE DE DONNEES
	require 'database.class.php';
	$dbh = Database::connect();

	// On vérifie que l'apiKey existe et on récupère les données du groupe
	$query = "SELECT * FROM apikeys a
				JOIN groups g ON a.idGroup = g.idGroup
				WHERE a.apikey = ?";
	$sth = $dbh->prepare($query);
	$sth->execute(array($apikey));
	
    // GESTION DU RESULTAT
	// Si on a une et une seule réponse, c'est bon
	if ($sth->rowCount() === 1) {
		// On renvoie un message de succès avec l'ID de session
		$msg = array('success' => 'APIkey valide');
		// on récupère aussi les données du groupe
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		array_push($msg, $result);
		// on augmente de un le compteur des utilisations
		$query = "UPDATE apikeys
				SET count = count + 1
				WHERE apikeys.apikey = ?";
		$sth = $dbh->prepare($query);
		$sth->execute(array($apikey));
	} else {
		$msg = array('error' => 'Votre APIkey n\'est pas valide');
	}
	echo json_encode($msg);
	exit();
}