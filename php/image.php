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

// Si on doit afficher les images de l'event
if (!(empty($_POST['event'])) === TRUE ) {
    $event = filter_input(INPUT_POST, 'event', FILTER_SANITIZE_SPECIAL_CHARS);

    require 'database.class.php';
    $dbh = Database::connect();
    // On vérifie que l'user à le droit de voir l'event
    // PAS BESOIN POUR LE MOMENT CAR TOUS LES EVENTS SONT PUBLICS

    // $query = "SELECT * FROM groups g
    //             WHERE g.idgroup IN (SELECT DISTINCT idfather FROM grouprelations)
    //             AND g.conf = 1
    //             AND ? IN (SELECT email FROM adminrights WHERE idgroup = g.idgroup)";
    //             // il faudra rajouter ensuite les groupes privés en vérifiant que l'utilisateur connecte peut y acceder s'il appartient au groupe privé
    // $sth = $dbh->prepare($query);
    // $sth->execute(array($_SESSION['email']));

    // if ($sth->rowCount() > 0) {
    //     $result = array('success' => 'Récupération réussie');
    //     $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
    //     array_push($result, $resultSQL);
    // } else {
    //     $result = array('error' => 'Pas de groupes trouvés');
    // }

    // si les droits sont OK, on renvoie l'image (à laquelle on ne peut pas accéder sinon, directement par les dossiers)
    // on doit lister le dossier
    echo php.readdir("../img/"+$event);

} else {
    $result = array('error' => "Vous n'êtes pas connecté");
}
echo json_encode($result);