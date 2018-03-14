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
	$msg = array('notlogged' => 'Veuillez rentrer un email');
	echo json_encode($msg);
	exit();
}

// CONNEXION AVEC MAIL ET MDP par défaut
// c'est l'email qui est unique et qui est utilisé comme 'cookie' pour le serveur
// l'accès à la session se fait lui avec le session_id transmis au javascript au moment de la connexion

/* @return
	* array avec success = Connexion réussie et tous les champs de l'utilisateur 
	* array avec error = vérifier mail/mdp OU un des champs pas rempli
*/

// Si on a bien reçu par POST les champs input email et password
if (!(empty($_SESSION['email']) || empty($_POST['password'])) === TRUE) {
	// On assainit les données
	$email = $_SESSION['email'];
	$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

	// CONNEXION A LA BASE DE DONNEES
	require 'database.class.php';
	$dbh = Database::connect();

	// CONNEXION DE L'UTILISATEUR
	// on récupère le mot de passe de l'utilisateur
	$query = "SELECT * FROM utilisateurs WHERE actif = 1 AND email = ?";
	$sth = $dbh->prepare($query);
	$sth->execute(array($email));
	
    // GESTION DU RESULTAT
	// Si on a une réponse
	if ($sth->rowCount() === 1) {
		// Vérifier que c'est le bon mdp
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (password_verify($password,$result[0]['motdepasse']) === TRUE) {
			// On renvoie un message de succès avec l'ID de session
			$msg = array('success' => 'Connexion réussie', 'session_id' => session_id());
			// on récupère aussi les données de l'utilisateur pour son profil, ...
			// en enlevant le mot de passe et le statut actif qui servent à rien
			unset($result[0]['motdepasse']);
			unset($result[0]['actif']);
			// on stocke l'idsu pour afficher facilement les projets, subv, ...
			$_SESSION['idsu'] = $result[0]['idsu'];
			$_SESSION['iduser'] = $result[0]['iduser'];
			array_push($msg, $result);
		} else {
			$msg = array('error' => 'Le mot de passe n\'est pas correct');
		}
	} else {
		$msg = array('error' => 'Email invalide');
	}
} else {
	$msg = array('error' => 'Un des champs n\'est pas rempli');
}

//// DECONNECTION
// @return success => Connexion réussie
// S'il existe logout dans l'URL
if (isset($_GET['logout'])) {
	try{
		// on essaye de désactiver le cookie
		// CONNEXION A LA BASE DE DONNEES
		require 'database.class.php';
		$dbh = Database::connect();
		/** avec active on avait pbm pour recreer un token
		$query = "UPDATE tokens
					SET active = 0
					WHERE email = ? AND userAgent = ?"; */
		$query = "DELETE FROM tokens WHERE email = ? AND userAgent = ?";
		$sth = $dbh->prepare($query);
		$sth->execute(array($_SESSION['email'], $_SESSION['userAgent']));
	}catch( PDOException $Exception ) { } // s'en fout !

	// on vide et on détruit la session
	$_SESSION = array();
	session_destroy();
	// on envoie éventuellement un message de succès
	$msg = array('success' => 'Déconnexion réussie');
	echo json_encode($msg);
	exit();
}

echo json_encode($msg);
