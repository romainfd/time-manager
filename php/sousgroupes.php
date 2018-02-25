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

// RECHERCHE/AFFICHAGE DES SOUS-GROUPES

// On vérifie que la personne est connectée ou non.
if (!isset($_SESSION['email'])) {
	$msg = array('notlogged' => 'Vous n\'êtes pas connecté');
	echo json_encode($msg);
	exit();
}
// Si on doit afficher les événements en fonction de la participation
if (!(empty($_POST['idgroup'])) === TRUE ) {
	$idgroup = filter_input(INPUT_POST, 'idgroup', FILTER_SANITIZE_SPECIAL_CHARS);

	require 'database.class.php';
	$dbh = Database::connect();
	// On récupère directement tous les événements que l'on a créé
	$query = "SELECT * FROM groups
                WHERE idgroup IN (
                	SELECT idchild FROM grouprelations
                	WHERE idfather = ?)
                AND conf=1";
                // il faudra rajouter ensuite les groupes privés en vérifiant que l'utilisateur connecte peut y acceder s'il appartient au groupe privé
	$sth = $dbh->prepare($query);
	$sth->execute(array($idgroup));
	
    if ($sth->rowCount() > 0) {
        $result = array('success' => 'Récupération réussie');
        $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
        array_push($result, $resultSQL);
    } else {
        $result = array('error' => 'Pas de sous-groupes trouvés');
    }
} else {
    $result = array('error' => 'Veuillez sélectionner un groupe');
}
echo json_encode($result);