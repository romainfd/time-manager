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

// On vérifie que la personne est connectée ou non.
if (!isset($_SESSION['email'])) {
    $msg = array('notlogged' => 'Veuillez rentrer un email');
    echo json_encode($msg);
    exit();
}

// CREATION DE COMPTE AVEC PRENOM, NOM, MAIL, MOTDEPASSE, IDSU par défaut
// c'est l'email qui est unique et qui est utilisé comme 'cookie' pour le serveur
// l'accès à la session se fait lui avec le session_id transmis au javascript au moment de la connexion

/* @return
    * array avec success = Connexion réussie, iduser & idsu
    * array avec error = erreur création OU vérifier mail/mdp OU un des champs pas rempli
*/


// Si on a bien reçu par POST les champs input login et password
if (!(empty($_POST['prenom']) || empty($_POST['nom']) || empty($_POST['password']) || empty($_POST['codestartup'])) === TRUE) {
    // On assainit les données
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_SPECIAL_CHARS);
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $codestartup = filter_input(INPUT_POST, 'codestartup', FILTER_SANITIZE_SPECIAL_CHARS);

    //// CONNEXION A LA BASE DE DONNEES
    require 'database.class.php';
    $dbh = Database::connect();
    if (!$dbh) {
        $msg = array('error' => 'Connexion au serveur impossible');
        echo json_encode($msg);
        exit();
    }

    // mail pas déjà pris sinon on connecte et on ne crée pas !
    // Récupérer l'id de la startup
    $query = "SELECT idsu FROM startups WHERE codesu = ?";
    $sth = $dbh->prepare($query);
    $sth->execute(array($codestartup));
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    // Si on a une réponse, c'est bon
    if ($sth->rowCount() === 1) {
        $idsu = $result[0]['idsu'];
        // on le stocke pour la session
        $_SESSION['idsu'] = $idsu;
        $dbh->beginTransaction(); // pour récupérer l'id après insertion
        // CREATION DU NOUVEAU COMPTE
        $query = "INSERT INTO utilisateurs (prenom, nom, email, motdepasse, idsu) VALUES (?,?,?,?,?)";
        $sth = $dbh->prepare($query);
        $sth->execute(array($prenom, $nom, $_SESSION['email'], password_hash($password, PASSWORD_DEFAULT), $idsu));
        if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
            $iduser = $dbh->lastInsertId();
            $_SESSION['iduser'] = $iduser;
            $msg = array('success' => 'Creation reussie', 'iduser' => $iduser, 'idsu' => $idsu);
            $dbh->commit();
        } else {
            $msg = array('error' => 'Echec lors de la création du compte');
            $dbh->rollback();
        }
    } else {
    $msg = array('error' => 'Veuillez vérifier votre Code StartUp');
    }
} else {
    $msg = array('error' => 'Un champ n\'est pas rempli');
}

echo json_encode($msg);