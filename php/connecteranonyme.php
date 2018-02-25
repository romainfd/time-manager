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

// Clé pour crypter les cookies
$SECRET_KEY = "ce15rz9dK4Plma54456koNJ489fferIBf8FDuDdf19i6YfsS64GF6qdfDZE";

// CONNEXION PAR TOKEN
// l'accès à la session se fait lui

/* @return
	* array avec success = Connexion réussie, session_id = id_session(), tous les champs de l'utilisateur 
	* array avec errorExpired = token expiré
	* array avec error = erreur
*/

// Si on a bien reçu par POST
if (!(empty($_POST['useragent'])) === TRUE) {
	// On assainit les données
	$ipaddress = $_SERVER['REMOTE_ADDR'];
	$useragent = filter_input(INPUT_POST, 'useragent', FILTER_SANITIZE_SPECIAL_CHARS);

	// CONNEXION A LA BASE DE DONNEES
	require 'database.class.php';
	$dbh = Database::connect();

	// CONNEXION DE L'UTILISATEUR
	$query = "INSERT INTO utilisateursanonymes (ipaddress, useragent) VALUES (?,?) ON DUPLICATE KEY UPDATE count = count + 1";
	$sth = $dbh->prepare($query);
	$sth->execute(array($ipaddress, $useragent));
	// on récupère l'id
	$query = "SELECT id FROM utilisateursanonymes
				WHERE ipaddress = ? AND useragent = ?";
	$sth = $dbh->prepare($query);
	$sth->execute(array($ipaddress, $useragent));
	
    // GESTION DU RESULTAT
	// Si on a une et une seule réponse, c'est bon
	if ($sth->rowCount() === 1) {
		// On renvoie un message de succès avec l'ID de session
		$msg = array('success' => 'Connexion réussie', 'session_id' => session_id());
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		$_SESSION['email'] = $result[0]["id"];
		$_SESSION['useragent'] = $useragent;
		array_push($msg, $result);
	} else {
		$msg = array('error' => "Problème d'accès automatique aux données");
	}
	echo json_encode($msg);
	exit();
} else {
	echo json_encode(array('error' => 'Impossible de se connecter aux données'));
	exit();
}