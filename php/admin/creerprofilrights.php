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

// CREATION DE COMPTE AVEC PRENOM, NOM, MAIL, MOTDEPASSE par défaut
// c'est l'email qui est unique et qui est utilisé comme 'cookie' pour le serveur
// l'accès à la session se fait lui avec le session_id transmis au javascript au moment de la connexion

/* @return
    * array avec success = Connexion réussie, session_id = id_session()
    * array avec error = erreur création OU vérifier mail/mdp OU un des champs pas rempli
*/


// Si on a bien reçu par POST les champs input login et password
if (!(empty($_POST['prenom']) || empty($_POST['nom']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['rightkey'])) === TRUE) {
    // On assainit les données
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_SPECIAL_CHARS);
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $rightkey = filter_input(INPUT_POST, 'rightkey', FILTER_SANITIZE_SPECIAL_CHARS);


    //// CONNEXION A LA BASE DE DONNEES
    require '../database.class.php';
    $dbh = Database::connect();
    if (!$dbh) {
        $msg = array('error' => 'Connexion au serveur impossible');
        echo json_encode($msg);
        exit();
    }

    // MAIL DEJA PRIS ?
    $query = "SELECT * FROM utilisateurs WHERE email = ?";
    $sth = $dbh->prepare($query);
    $sth->execute(array($email));
    // Si on a aucune réponse, c'est bon
    if ($sth->rowCount() === 0) {
        // On fait tout sur une transaction pour annuler en cas d'erreur
        $dbh->beginTransaction();
        // CREATION DU NOUVEAU COMPTE
        $query = "INSERT INTO utilisateurs (prenom, nom, email, motdepasse) VALUES (?,?,?,?)";
        $sth = $dbh->prepare($query);
        $sth->execute(array($prenom, $nom, $email, password_hash($password, PASSWORD_DEFAULT)));

        if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
            // On enregistre l'email sur la session du serveur
            $_SESSION['email'] = $email;

            // CREATION DES DROITS
            $query = "SELECT idgroup FROM rightkeys WHERE rightkey = ?;";
            $sth = $dbh->prepare($query);
            $sth->execute(array($rightkey));
                if ($sth->rowCount() >= 1) { // on a bien des droits associés à cette clé
                $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
                for ($i = 0; $i < count($resultSQL); $i++) {
                    // on ajoute les droits au groupe associé   
                    $query = "INSERT INTO `adminrights`(`email`, `idgroup`) VALUES (?,?)";
                    $sth = $dbh->prepare($query);
                    $sth->execute(array($_SESSION['email'], $resultSQL[$i]['idgroup']));
                    if ($sth->rowCount() !== 1) { // on n'a pas réussi à ajouter le droit
                        // on annule la transaction :
                        $dbh->rollBack();
                        $msg = array('error' => 'Impossible de donner les droits au groupe '.$resultSQL[$i]['idgroup']);
                        echo json_encode($msg);
                        exit();
                    }
                    // tout a fonctionné => on commit
                    $dbh->commit();
                    $msg = array('success' => 'Creation reussie', 'session_id' => session_id());
                }
            } else {
                $msg = array('error' => 'Aucun droit d\'administrateur n\'est associé à votre clé ('.$rightkey.')');
            }
        } else {
            $msg = array('error' => 'Echec lors de la création du compte');
        }
    } else {
    $msg = array('error' => 'Login deja utilise/Erreur de connexion');
    }
} else {
    $msg = array('error' => 'Un champ n\'est pas rempli');
}

echo json_encode($msg);