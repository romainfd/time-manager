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

// RENVOIE LES INFOS COMPLETES D'UN EVENEMENT
// email et dateCreation 	@return le bon evenmt (dans un array !) => utile pour afficher la page d'1 seul événement

// On vérifie que la personne est connectée ou non.
if (!isset($_SESSION['email'])) {
	$msg = array('notlogged' => 'Vous n\'êtes pas connecté');
	echo json_encode($msg);
	exit();
}

if (!empty($_POST['emailCreateur']) === TRUE) {
    // On assainit les données
    $emailCreateur = filter_input(INPUT_POST, 'emailCreateur', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($emailCreateur == $_SESSION['email']) { // on veut récupérer ses events
    	require 'database.class.php';
		$dbh = Database::connect();
		// On récupère directement tous les événements que l'on a créé
		$query = "SELECT * FROM events
		            JOIN utilisateurs ON utilisateurs.email = events.emailCreateur 
                    LEFT JOIN participationsevents ON events.id = participationsevents.idEvent 
                    WHERE emailCreateur = ?";
		$sth = $dbh->prepare($query);
		$sth->execute(array($emailCreateur));
		
	     if ($sth->rowCount() > 0) {
            $result = array('success' => 'Récupération réussie');
            $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
            array_push($result, $resultSQL);
        } else {
            $result = array('error' => 'Pas d\'événements trouvés pour vous ('.$_SESSION['email'].')');
        }
    } else {
	    require 'util.class.php';
		$result = Util::rechercheMail($emailCreateur);
		// Si on donne la date aussi
		// if (!empty($_POST['dateCreation']) === TRUE) {
		// 	require 'database.class.php';
		// 	$dbh = Database::connect();
		// 	$param = array($emailCreateur);
		// 	// On a aussi la date de création => evenement unique
		// 	$dateCreation = filter_input(INPUT_POST, 'dateCreation', FILTER_SANITIZE_SPECIAL_CHARS);
		// 	$query = "SELECT * FROM events 
		// 	INNER JOIN utilisateurs ON events.emailCreateur = utilisateurs.email
		// 	LEFT JOIN participationsevents ON events.id = participationsevents.idEvent
		// 	WHERE emailCreateur = ? AND dateCreation = ?";
		// 	array_push($param, $dateCreation);
		// }
		// $sth = $dbh->prepare($query);
		// $sth->execute($param);
		// if ($sth->rowCount() >= 1) {
		// 	$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		// } else {
		// 	$result = array('error' => 'Il n\'existe pas un tel évènement');
		// }
	}
} else if (!(empty($_POST['lng']) || empty($_POST['lat']))) {
    $lng = filter_input(INPUT_POST, 'lng', FILTER_SANITIZE_SPECIAL_CHARS);
    $lat = filter_input(INPUT_POST, 'lat', FILTER_SANITIZE_SPECIAL_CHARS);
    
    require 'util.class.php';
    $result = Util::eventsProches($lng, $lat);
} else { 
    $result = array('error' => 'Pas assez d\'informations fournies');
}
echo json_encode($result);