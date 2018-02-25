<?php
// /* Entête obligatoirement présente dans tous vos webservices */
ini_set('session.use_cookies', 0);
ini_set('session.use_only_cookies', 0);
ini_set('session.use_trans_sid', 1);
// // Attention au session_name
session_name('name');
session_start();

header('Content-Type: text/html; charset=utf-8');
header('Content-type: application/json; charset=utf-8');
header('access-control-allow-origin: *');
/* Fin de l'entête obligatoire */

$server = "https://mapit.fr/";
// RENVOIT UN JSON ARRAY DES PHOTOS
// Chaque élément est une photo et a en particulier un attribut src qui pointe vers elle

// On vérifie que la personne est connectée ou non.
if (!isset($_SESSION['email'])) {
	$msg = array('notlogged' => 'Vous n\'êtes pas connecté');
	echo json_encode($msg);
	exit();
}

if (!(empty($_POST['dateDebut']) || empty($_POST['emailCreateur']))) {
	$dateDebut = filter_input(INPUT_POST, 'dateDebut', FILTER_SANITIZE_SPECIAL_CHARS);
	$emailCreateur = filter_input(INPUT_POST, 'emailCreateur', FILTER_SANITIZE_SPECIAL_CHARS);

	// On récupère le tableau des images 
	$directory = "photos/".$emailCreateur."-".$dateDebut;
	$images = scandir($directory);
	$imagesTraitees = array();
	// on met l'adresse en premier
	array_push($imagesTraitees, array('directory' => $server.$directory));
	if ($images) {  // scandir retourne false si pas de path/dossier ie pas de photos 
		for ($i = 2; $i < sizeof($images); $i++) { // i = 2 car fichiers . et .. en 1ers
			array_push($imagesTraitees, array('src' => $server.$directory."/".$images[$i]));
		}
	}
} else {
	$imagesTraitees = array('error' => "Pas assez d'informations");
}
echo json_encode($imagesTraitees);
