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


// RECHERCHE DE L'EMAIL
// l'accès à la session se fait lui avec le session_id transmis au javascript au moment de la connexion

/* @return
	* array avec success = Mail existe, session_id = id_session() 
	* array avec notexist = Créez un compte, session_id = id_session() 
	* array avec error = Veuillez renseigner un email
*/

// Si on a bien reçu par POST l'email
if (!(empty($_POST['email'])) === TRUE) {
	// On assainit les données
	$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);

	// CONNEXION A LA BASE DE DONNEES
	require 'database.class.php';
	$dbh = Database::connect();

	// CONNEXION DE L'UTILISATEUR
	// On vérifie que l'utilisateur existe et que son mot de passe est correct (on compare les SHA1 dans la base avec le SHA1 de ce qui a été posté)
	$query = "SELECT * FROM utilisateurs WHERE email = ?";
	$sth = $dbh->prepare($query);
	$sth->execute(array($email));
	
    // GESTION DU RESULTAT
	// Si on a une réponse
	if ($sth->rowCount() === 1) {
			$msg = array('success' => 'Connexion réussie', 'session_id' => session_id());
	} else {
		$msg = array('notexist' => 'Cet email n\'existe pas dans notre base de données. Veuillez créer un compte.', 'session_id' => session_id());
	}
	// dans les 2 cas : on enregistre le login sur la session du serveur
	$_SESSION['email'] = $email;
} else {
	$msg = array('error' => 'Veuillez renseigner un email.');
}

//// DECONNECTION
// @return success => Connexion réussie
// S'il existe logout dans l'URL
if (isset($_GET['logout'])) {
	// on vide et on détruit la session
	$_SESSION = array();
	session_destroy();
	// on envoie éventuellement un message de succès
	$msg = array('success' => 'Déconnexion réussie');
	echo json_encode($msg);
	exit();
}

echo json_encode($msg);
