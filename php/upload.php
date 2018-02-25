<?php
/* Entête obligatoirement présente dans tous vos webservices */
// ini_set('session.use_cookies', 0);
// ini_set('session.use_only_cookies', 0);
// ini_set('session.use_trans_sid', 1);
// Attention au session_name
// session_name('name');
// session_start();

header('Content-Type: text/html; charset=utf-8');
header('Content-type: application/json; charset=utf-8');
header('access-control-allow-origin: *');
/* Fin de l'entête obligatoire */

// On vérifie si la personne est déjà connectée ou non.
// if (!empty($_SESSION['email'])) {
// 	$msg = array('success' => "Vous êtes déjà connecté avec l'email : ".$_SESSION['email']);
// 	echo json_encode($msg);
// 	exit();
// }

// On récupère la valeur du champ HTML input type='file' s'appelant photo et on vérifie l'upload, comme vous l'avez fait avec $_POST
if (!(empty($_FILES['photo']['tmp_name']) || empty($_POST['dateDebut']) || empty($_POST['emailCreateur']) || empty($_POST['numeroPhoto'])) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
	$emailCreateur = filter_input(INPUT_POST, 'emailCreateur', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateDebut = filter_input(INPUT_POST, 'dateDebut', FILTER_SANITIZE_SPECIAL_CHARS);
    $numeroPhoto = filter_input(INPUT_POST, 'numeroPhoto', FILTER_SANITIZE_SPECIAL_CHARS);
    if(!is_dir("photos/".$emailCreateur."-".$dateDebut)) { // dossier n'existe pas
    	if(!mkdir("photos/".$emailCreateur."-".$dateDebut, 0700)){ // mais on arrive pas à le créer
    		$msg = array('error' => 'Impossible de créer le dossier pour les photos');
    		exit();
		} 
	// le dossier existe ou a été créé
	// on récupère les propriétés de l'image
	list($larg,$haut,$type,$attr) = getimagesize($_FILES['photo']['tmp_name']);
	// on vérifie que l'on a bien un type = 2 = JPEG
	if ($type == 2) {
		// on constuit le chemin dans lequel on va sauvegarder la photo envoyée
		// regardez les paragraphes suivants pour savoir comment faire

		// générer un nom unique pour éviter les collisions (createur - dateEvenement)
		$pathPhoto = "photos/".$emailCreateur."-".$dateDebut."/".$numeroPhoto.".jpg";
		if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $pathPhoto)) {
			$msg = array('error' => 'Probleme d\'upload');
		} else {
			$msg = array('success' => 'La photo a bien ete uploadee');
		}
	} else {
		$msg = array('error' => 'Format d\'image invalide');
	}	
} else {
	$msg = array('error' => 'Il manque des informations');
}

echo json_encode($msg);