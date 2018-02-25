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

// CREATION DE EVENT 
// c'est email + date qui est unique et permettra de retrouver les photos qui sont numérotées de 1 à nbPhotos

/* @return
    * array avec success = Connexion réussie, 
    * array avec error = un des champs pas rempli OU pbm d'insert/update du sql
    * array avec errorAccess = pas les droits de modif ! (quelqu'un qui veut hacker)
*/

// Si on a bien reçu par POST les champs input
if (!(empty($_POST['titre']) || empty($_POST['date']) || empty($_POST['dateFin']) || empty($_POST['lieu']) || empty($_POST['infos']) || empty($_POST['conf']) || empty($_POST['dateCreation']) || !isset($_POST['nbPhotos']) || empty($_POST['idgroup'])) === TRUE) {
    // On assainit les données
    $titre = filter_input(INPUT_POST, 'titre', FILTER_SANITIZE_SPECIAL_CHARS);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateFin = filter_input(INPUT_POST, 'dateFin', FILTER_SANITIZE_SPECIAL_CHARS);
    $lieu = filter_input(INPUT_POST, 'lieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $lng = filter_input(INPUT_POST, 'lng', FILTER_SANITIZE_SPECIAL_CHARS);
    $lat = filter_input(INPUT_POST, 'lat', FILTER_SANITIZE_SPECIAL_CHARS);
    $infos = filter_input(INPUT_POST, 'infos', FILTER_SANITIZE_SPECIAL_CHARS);
    $conf = filter_input(INPUT_POST, 'conf', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateCreation = filter_input(INPUT_POST, 'dateCreation', FILTER_SANITIZE_SPECIAL_CHARS);
    $nbPhotos = filter_input(INPUT_POST, 'nbPhotos', FILTER_SANITIZE_SPECIAL_CHARS);
    $idgroup = filter_input(INPUT_POST, 'idgroup', FILTER_SANITIZE_SPECIAL_CHARS);

    //// CONNEXION A LA BASE DE DONNEES
    require 'database.class.php';
    $dbh = Database::connect();
    if (!$dbh) {
        $msg = array('error' => 'Connexion au serveur impossible');
        echo json_encode($msg);
        exit();
    }

    if(empty($_POST['updateId'])) { // on insère un nouveau truc
        // CREATION DU NOUVEAU EVENT
        // si on a le droit de créer dans ce groupe
        $query = "SELECT COUNT(*) FROM groups
                    WHERE idgroup = ?
                    AND idgroup IN (
                        SELECT idgroup FROM adminrights
                        WHERE email = ?
                            UNION
                        SELECT idchild FROM grouprelations
                        WHERE idfather IN ( 
                            SELECT idgroup FROM adminrights
                            WHERE email = ?)
                    )";
        $sth = $dbh->prepare($query);
        $sth->execute(array($idgroup, $_SESSION['email'], $_SESSION['email']));
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        if ($result["COUNT(*)"] == 1) { // on a les droits
            $query = "INSERT INTO events (titre, date, dateFin, lieu, lng, lat, infos, conf, dateCreation, nbPhotos, emailCreateur, idgroup) 
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
            $sth = $dbh->prepare($query);
            $sth->execute(array($titre, $date, $dateFin, $lieu, $lng, $lat, $infos, $conf, $dateCreation, $nbPhotos, $_SESSION['email'], $idgroup));

            if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
                $msg = array('success' => 'Creation reussie');
            } else {
                $msg = array('error' => 'Echec lors de la création de votre event');
            }
        } else {
            $msg = array('errorAccess' => "Vous n'avez pas les droits pour créer cet événement dans ce groupe !");
        }
    } else { // on veut mettre à jour
        $updateId = filter_input(INPUT_POST, 'updateId', FILTER_SANITIZE_SPECIAL_CHARS);
        // MISE A JOUR DE L'EVENT
        // On vérifie les droits du gars qui modif
        // est ce que notre event (id = ?) est dans un groupe que le gars admin ou dans un sous groupe de ces groupes
        $query = "SELECT * FROM events e
                    WHERE id = ? AND idgroup IN (
                        SELECT idgroup FROM adminrights
                        WHERE email = ?
                            UNION
                        SELECT idchild FROM grouprelations
                        WHERE idfather IN ( 
                            SELECT idgroup FROM adminrights
                            WHERE email = ?)
                    )";
        $sth = $dbh->prepare($query);
        $sth->execute(array($updateId, $_SESSION['email'], $_SESSION['email']));
        if ($sth->rowCount() === 1) { // on a bien cet event et on a les droits
            // On peut METTRE A JOUR
            // on ne change pas l'email du créateur !
            $query = "UPDATE events SET titre = ?, date = ?, dateFin = ?, lieu = ?, lng = ?, lat = ?, infos = ?, conf = ?, dateCreation = ?, nbPhotos = ?, idgroup = ?
                        WHERE id = ?";
            $sth = $dbh->prepare($query);
            $sth->execute(array($titre, $date, $dateFin, $lieu, $lng, $lat, $infos, $conf, $dateCreation, $nbPhotos, $idgroup, $updateId));
            if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
                $msg = array('success' => 'Modification reussie');
            } else {
                $msg = array('error' => 'Echec lors de la modification de votre event');
            }
        } else {
            $msg = array('errorAccess' => "Vous n'avez pas les droits pour modifier cet événement ! Demander les droits administrateurs pour le groupe ".$idgroup);
        }
    }
} else {
    $msg = array('error' => 'Un champ n\'est pas rempli');
}

echo json_encode($msg);