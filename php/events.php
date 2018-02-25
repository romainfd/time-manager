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

// RECHERCHE/AFFICHAGE DE PLUSIEURS EVENEMENTS
// participation : 'true'/'false' 
// 		* true : que les événements auxquels on participe (pour conv')
// 		* false : tous les événements (recherche avec nom qui contient ''
// emailCreateur : 
// 		@return liste des evenements créés par l'utilisateur que l'on est autorisé à voir
// lng - lat :	
//		@return liste des événements les plus proches		

// On vérifie que la personne est connectée ou non.
if (!isset($_SESSION['email'])) {
	$msg = array('notlogged' => 'Vous n\'êtes pas connecté');
	echo json_encode($msg);
	exit();
}

// Si on doit afficher les événements en fonction de la participation
if (!(empty($_GET['participation']) || empty($_POST['group'])) === TRUE ) {
	$participation = $_GET['participation'];
	$group = filter_input(INPUT_POST, 'group', FILTER_SANITIZE_SPECIAL_CHARS);
	// on convertit en booleen
	$participation = ($participation === 'true') ? true: false;

	require 'util.class.php';
	// On récupère les événements
	if ($participation) {
		$result = Util::eventsParticipe($group);
	} else {
		$result = Util::rechercheNom("", $group); // events autorisés avec tous les noms possibles
	}
}
else if (!empty($_POST['emailCreateur']) === TRUE) {
    // On assainit les données
    $emailCreateur = filter_input(INPUT_POST, 'emailCreateur', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($emailCreateur == $_SESSION['email']) { // on veut récupérer ses events
    	require 'database.class.php';
		$dbh = Database::connect();
		// On récupère directement tous les événements que l'on a créé
		$query = "SELECT * FROM events
		            JOIN utilisateurs ON utilisateurs.email = events.emailCreateur 
                    LEFT JOIN participationsevents ON events.id = participationsEvents.idEvent 
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
	}
} else if (!(empty($_POST['lng']) || empty($_POST['lat']) || empty($_POST['group'])) === TRUE) {
    $lng = filter_input(INPUT_POST, 'lng', FILTER_SANITIZE_SPECIAL_CHARS);
    $lat = filter_input(INPUT_POST, 'lat', FILTER_SANITIZE_SPECIAL_CHARS);    
    $group = filter_input(INPUT_POST, 'group', FILTER_SANITIZE_SPECIAL_CHARS);
    require 'util.class.php';
    $result = Util::eventsProches($lng, $lat, $group);
} else if (!empty($_POST['tag']) === TRUE) {
    $tag = filter_input(INPUT_POST, 'tag', FILTER_SANITIZE_SPECIAL_CHARS);
    require 'util.class.php';
    $result = Util::eventsWithTag($tag);
} else { 
    $result = array('error' => 'Pas assez d\'informations fournies : veuillez choisir un groupe');
}

echo json_encode($result);