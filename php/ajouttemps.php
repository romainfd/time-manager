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
    $msg = array('notlogged' => 'Vous n\'êtes pas connecté');
    echo json_encode($msg);
    exit();
}

// CREATION DE TACHE
/* @return
    * array avec success = Connexion réussie, 
    * array avec error = un des champs pas rempli OU pbm d'insert/update du sql
    * array avec errorAccess = pas les droits de modif ! (quelqu'un qui veut hacker)
*/

// Si on a bien reçu par POST les champs input
if (!(empty($_POST['date']) || empty($_POST['idprojet']) || empty($_POST['heures'])) === TRUE) {
    // On assainit les données
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_SPECIAL_CHARS);
    $idprojet = filter_input(INPUT_POST, 'idprojet', FILTER_SANITIZE_SPECIAL_CHARS);
    $heures = filter_input(INPUT_POST, 'heures', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!empty($_POST['description'])) {
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    } else {
        $description = NULL;
    }

    // test bateau sur les heures
    if ($heures <= 0 || $heures > 8) {
        $msg = array('error' => 'Nombre d\'heures invalide. Il doit être compris entre 0 et 8 heures.');
        echo json_encode($msg);
        exit();
    }

    // On vérifie que l'on est ni sur un weekend ni un jour férié
    if (date('w', strtotime($date)) == 0 || date('w', strtotime($date)) == 6) {
        $msg = array('error' => 'Impossible de rajouter des heures sur le weekend.');
        echo json_encode($msg);
        exit();
    }
    // On calcule les jours fériés de cette année
    $year = substr($date,0,4);
    $easterDate = easter_date($year); // renvoie la veille du dimanche de paques
    $easterDay = date('d', $easterDate);
    $easterMonth = date('m', $easterDate);
    $holidays = array(
            // Jours feries fixes
            mktime(0, 0, 0, 1, 1, $year),// 1er janvier
            mktime(0, 0, 0, 5, 1, $year),// Fete du travail
            mktime(0, 0, 0, 5, 8, $year),// Victoire des allies
            mktime(0, 0, 0, 7, 14, $year),// Fete nationale
            mktime(0, 0, 0, 8, 15, $year),// Assomption
            mktime(0, 0, 0, 11, 1, $year),// Toussaint
            mktime(0, 0, 0, 11, 11, $year),// Armistice
            mktime(0, 0, 0, 12, 25, $year),// Noel

            // Jour feries qui dependent de paques
            mktime(0, 0, 0, $easterMonth, $easterDay + 2, $year),// Lundi de paques
            mktime(0, 0, 0, $easterMonth, $easterDay + 40, $year),// Ascension
            mktime(0, 0, 0, $easterMonth, $easterDay + 50, $year), // Pentecote
    );
    if (in_array(mktime(0, 0, 0, substr($date,5,2), substr($date,8,2), $year), $holidays)) {
        $msg = array('error' => 'Impossible de rajouter des heures lors d\'un jour férié.');
        echo json_encode($msg);
        exit();
    }

    // puis on traite CIR et sub
    if (empty($_POST['cirable'])) {
        $cirable = 0;
    } else {
        $cirable = 1;
    }    
    if (empty($_POST['idsub'])) {
        $idsub = NULL;
    } else {
        $idsub = filter_input(INPUT_POST, 'idsub', FILTER_SANITIZE_SPECIAL_CHARS);
    }

    //// CONNEXION A LA BASE DE DONNEES
    require 'database.class.php';
    $dbh = Database::connect();
    if (!$dbh) {
        $msg = array('error' => 'Connexion au serveur impossible');
        echo json_encode($msg);
        exit();
    }

    // AJOUT d'une nouvelle tache
    if (empty($_POST['idtache'])) {
        // on verifie que le nombre d'heures est valide
        // on recupere le nombre d'heures travaillees ce jour par l'employe (sur tous les projets)
        $query = "SELECT SUM(heures) as heuresAvt FROM taches 
                    WHERE iduser = ? and date = ?";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_SESSION['iduser'], $date));
        if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
            $result = $sth->fetch(PDO::FETCH_ASSOC);
            // on verifie heuresAvt + heures <= 8
            if ($heures + $result['heuresAvt'] > 8) {
                $msg = array('error' => "Impossible d'ajouter les ".((float) $heures)." nouvelles heures car vous avez déjà travaillé ".((float) $result['heuresAvt'])." heures ce jour et cela ferait plus de 8 heures en une journée.");
                echo json_encode($msg);
                exit();            
            }
        } else {
            $msg = array('error' => 'Impossible d\'accéder à l\'historique de vos heures.');
            echo json_encode($msg);
            exit();
        }

        // On ajoute les heures
        $query = "INSERT INTO taches (iduser, idprojet, date, heures, description, cirable, idsub, dateModif) 
                    VALUES (?,?,?,?,?,?,?,?)";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_SESSION['iduser'], $idprojet, $date, $heures, $description, $cirable, $idsub, date("Y-m-d H:i:s")));

        if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
            $msg = array('success' => 'Création reussie !');
        } else {
            $msg = array('error' => 'Echec lors de la création de votre tâche.');
        }
    } else {
    // MISE A JOUR d'une tache
        $idtache = filter_input(INPUT_POST, 'idtache', FILTER_SANITIZE_SPECIAL_CHARS);
        // on verifie que le nombre d'heures est valide (en enlevant les heures de la tache qu'on update de la somme)
        // on recupere le nombre d'heures travaillees ce jour par l'employe (sur tous les projets)
        $query = "SELECT SUM(heures) as heuresAvt FROM taches 
                    WHERE iduser = ? and date = ? and idtache != ?;";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_SESSION['iduser'], $date, $idtache));
        if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
            $result = $sth->fetch(PDO::FETCH_ASSOC);
            // on verifie heuresAvt + heures <= 8
            if ($heures + $result['heuresAvt'] > 8) {
                $msg = array('error' => "Impossible d'ajouter les ".((float) $heures)." nouvelles heures car vous avez déjà travaillé ".((float) $result['heuresAvt'])." heures ce jour (en ne comptant pas la tache que vous souhaitez modifier) et cela ferait plus de 8 heures en une journée.");
                echo json_encode($msg);
                exit();            
            }
        } else {
            $msg = array('error' => 'Impossible d\'accéder à l\'historique de vos heures.');
            echo json_encode($msg);
            exit();
        }

        // On ajoute les heures
        $query = "UPDATE taches 
                    SET idprojet = ?, date = ?, heures = ?, description = ?, cirable = ?, idsub = ?, dateModif = ? 
                    WHERE idtache = ?"; // on met tout à jour sauf l'iduser (=> mana peut modifier !)
        $sth = $dbh->prepare($query);
        $sth->execute(array($idprojet, $date, $heures, $description, $cirable, $idsub, date("Y-m-d H:i:s"), $idtache));
        if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
            $msg = array('success' => 'Mise à jour reussie !');
        } else {
            $msg = array('error' => 'Echec lors de la mise à jour de votre tâche.');
        }        
    }
} else if (!(empty($_POST['idtache']) || empty($_POST['delete'])) === TRUE) {
    // SUPPRESSION d'une tache
    // On assainit les données
    $idtache = filter_input(INPUT_POST, 'idtache', FILTER_SANITIZE_SPECIAL_CHARS);
    $delete = filter_input(INPUT_POST, 'delete', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($delete !== "1") {
        $msg = array('error' => 'Une action extérieure est intervenue dans le processus de suppression. Veuillez n\'utiliser que l\'interface pour supprimer une tâche');
        echo json_encode($msg);
        exit();
    }
    //// CONNEXION A LA BASE DE DONNEES
    require 'database.class.php';
    $dbh = Database::connect();
    if (!$dbh) {
        $msg = array('error' => 'Connexion au serveur impossible');
        echo json_encode($msg);
        exit();
    }

    // On ajoute les heures
    $query = "DELETE FROM taches
                WHERE idtache = ? and iduser = ?"; // aussi tester les droits mana sur la startup
                // pour éviter que qq'un puisse tout supprimer
    $sth = $dbh->prepare($query);
    $sth->execute(array($idtache, $_SESSION['iduser']));

    if ($sth->rowCount() === 1) { // on a bien ajouté notre compte
        $msg = array('success' => 'Suppression reussie !');
    } else {
        $msg = array('error' => 'Échec lors de la suppression de votre tâche. Assurez-vous d\'avoir les droits requis et que cette tâche n\'a pas déjà été supprimée.');
    }
} else {
    $msg = array('error' => 'Un champ n\'est pas rempli');
}

echo json_encode($msg);