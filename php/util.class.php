<?php
class Util
{
    /**
     * Function rechercheNom
     * 
     * Evenements qu'un utilisateur a le droit de voir
     *
     * @param
     * prenom/nom du créateur / si "" => affiche tout
     *
     * @return array
     * 'error' si pbm connexion DB
     * 'error' si pas d'événements trouvés
     * si réussi
        * success : "recup ok"
        * 0 : tableau des événements qu'on peut voir
     */
    public static function rechercheNom($createurInfo, $group, $voyages = FALSE)
    {      
        require 'database.class.php';
        $dbh = Database::connect();
        if (!$dbh) {
            $msg = array('error' => 'Connexion au serveur impossible');
            return $msg;
        }
        /* on récupère les événements auxquels on a accès (groupe by id pour éviter doublons si dans plusieurs rubriques 1 et 3 svt)
            * auquel on participe (pour récupérer notre réponse de participation)
            * les autres
        */
        $query = "SELECT * FROM (
                SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, p.participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN participationsevents p  ON e.id = p.idEvent
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                    WHERE p.emailParticipe = ? AND (u.prenom LIKE ? OR u.nom LIKE ?)
                        AND (e.idgroup = ? OR e.idgroup IN (SELECT idchild FROM grouprelations WHERE idfather = ?))
           UNION 
                SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, '' as participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                    WHERE (u.prenom LIKE ? OR u.nom LIKE ?)
                        AND e.idgroup = ? OR e.idgroup IN (SELECT idchild FROM grouprelations WHERE idfather = ?) ) results
                GROUP BY id
                HAVING ".
                ($voyages === FALSE ? "results.dateFin > NOW()" : "results.date < NOW()").
                    "ORDER BY results.date ASC";
        $sth = $dbh->prepare($query);
        $info = "%".$createurInfo."%";
        $sth->execute(array($_SESSION['email'], $info, $info, $group, $group, $info, $info, $group, $group));
        // Si on a au moins une réponse, c'est bon
        if ($sth->rowCount() > 0) {
            $msg = array('success' => 'Récupération réussie');
            if ($createurInfo == "") {
                // on met à jour le compteur de l'affichage des events publics
                Util::updateCompt($dbh, "convPub");
            }
            $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
            // on récupère les nb de participants
            for ($i = 0; $i < sizeof($resultSQL); $i++) {
                $resultSQL[$i] = array_merge($resultSQL[$i], Util::getParticipants($dbh, $resultSQL[$i]['id']));
            }
            array_push($msg, $resultSQL);
        } else {
            $msg = array('error' => 'Pas d\'événements trouvés pour l\'utilisateur : '.$_SESSION['email']);
        }
        return $msg;
    }

    /**
     * Function rechercheMail
     * 
     * Evenements créés par $mailCreateur et qu'un utilisateur a le droit de voir
     *
     * @param
     * mail de créateur du créateur
     *
     * @return array
     * 'error' si pbm connexion DB
     * 'error' si pas d'événements trouvés
     * si réussi
        * success : "recup ok"
        * 0 : tableau des événements qu'on peut voir
     */
    public static function rechercheMail($emailCreateur, $voyages = FALSE)
    {      
        require 'database.class.php';
        $dbh = Database::connect();
        if (!$dbh) {
            $msg = array('error' => 'Connexion au serveur impossible');
            return $msg;
        }
        // on récupère les événements auxquels on a accès par eventsAutorisation (et pour ces evenmts ie id/notre mail, on regarde si on participe) UNION les événements publics
        // et on les trie par ordre chrono & fin > date actuelle (pas encore fini)
        // left join car on veut tous les events et juste rajouter si on participe s'il existe déjà une entrée pour ça
        $query = "SELECT * FROM (
                SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, p.participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN participationsevents p  ON e.id = p.idEvent
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                        WHERE p.emailParticipe = ? AND (e.emailCreateur = ?)
                UNION 
                SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, '' as participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                        LEFT JOIN autorisationsevents ON autorisationsevents.idEvent = e.id
                        WHERE autorisationsevents.emailAutorise = ? AND (e.emailCreateur = ?)
               UNION 
                SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, '' as participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                        WHERE e.conf = 3 AND (e.emailCreateur = ?)) results
                GROUP BY id
                HAVING ".
                ($voyages === FALSE ? "results.dateFin > NOW()" : "results.date < NOW()").
                    "ORDER BY results.date ASC";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_SESSION['email'], $emailCreateur, $_SESSION['email'], $emailCreateur, $emailCreateur));
        // Si on a au moins une réponse, c'est bon
        if ($sth->rowCount() > 0) {
            $msg = array('success' => 'Récupération réussie');
            $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
            // on récupère les nb de participants
            for ($i = 0; $i < sizeof($resultSQL); $i++) {
                $resultSQL[$i] = array_merge($resultSQL[$i], Util::getParticipants($dbh, $resultSQL[$i]['id']));
            }
            array_push($msg, $resultSQL);
        } else {
            $msg = array('error' => 'Pas d\'événements trouvés pour l\'utilisateur : '.$emailCreateur);
        }
        return $msg;
    }

    /**
     * Function rechercheMail
     * 
     * Evenements créés par $mailCreateur et qu'un utilisateur a le droit de voir
     *
     * @param
     * mail de créateur du créateur
     *
     * @return array
     * 'error' si pbm connexion DB
     * 'error' si pas d'événements trouvés
     * si réussi
        * success : "recup ok"
        * 0 : tableau des événements qu'on peut voir
     */
    public static function eventsWithTag($tag, $voyages = FALSE)
    {      
        require 'database.class.php';
        $dbh = Database::connect();
        if (!$dbh) {
            $msg = array('error' => 'Connexion au serveur impossible');
            return $msg;
        }
        $tagShort = $tag;
        $tag = "'%".$tag."%'";
        // on récupère les événements auxquels on a accès par eventsAutorisation (et pour ces evenmts ie id/notre mail, on regarde si on participe) UNION les événements publics
        // et on les trie par ordre chrono & fin > date actuelle (pas encore fini)
        // left join car on veut tous les events et juste rajouter si on participe s'il existe déjà une entrée pour ça
        $query = "SELECT * FROM (
                SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, p.participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN participationsevents p  ON e.id = p.idEvent
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                        WHERE p.emailParticipe = ? AND (e.titre LIKE ".$tag." OR e.infos LIKE ".$tag." OR e.lieu LIKE ".$tag." OR e.emailCreateur LIKE ".$tag.")
                UNION 
                 SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, '' as participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                        LEFT JOIN autorisationsevents ON autorisationsevents.idEvent = e.id
                        WHERE autorisationsevents.emailAutorise = ? AND (e.titre LIKE ".$tag." OR e.infos LIKE ".$tag." OR e.lieu LIKE ".$tag." OR e.emailCreateur LIKE ".$tag.")
               UNION 
                SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, '' as participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                        WHERE e.conf = 3 AND (e.titre LIKE ".$tag." OR e.infos LIKE ".$tag." OR e.lieu LIKE ".$tag." OR e.emailCreateur LIKE ".$tag.")) results
                GROUP BY id
                HAVING ".
                ($voyages === FALSE ? "results.dateFin > NOW()" : "results.date < NOW()").
                    "ORDER BY results.date ASC";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_SESSION['email'], $_SESSION['email']));
        // Si on a au moins une réponse, c'est bon
        if ($sth->rowCount() > 0) {
            $msg = array('success' => 'Récupération réussie');
            // on met à jour le compteur
            Util::updateCompt($dbh, "search");
            $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
            // on récupère les nb de participants
            for ($i = 0; $i < sizeof($resultSQL); $i++) {
                $resultSQL[$i] = array_merge($resultSQL[$i], Util::getParticipants($dbh, $resultSQL[$i]['id']));
            }
            array_push($msg, $resultSQL);
        } else {
            $msg = array('error' => 'Pas d\'événements trouvés pour le tag : '.$tagShort);
        }
        return $msg;
    }

    /**
     * Function eventsParticipe
     * 
     * Evenements auxquels on participe
     *
     * @return array
     * 'error' si pbm connexion DB
     * 'error' si pas d'événements trouvés
     * si réussi
        * success : "recup ok"
        * 0 : tableau des événements qu'on peut voir
     */
    public static function eventsParticipe($group, $voyages = FALSE) 
    {      
        require 'database.class.php';
        $dbh = Database::connect();
        if (!$dbh) {
            $msg = array('error' => 'Connexion au serveur impossible');
            return $msg;
        }
        // on récupère les événements auxquels on a accès par eventsAutorisation (et pour ces evenmts ie id/notre mail, on regarde si on participe) UNION les événements publics
        // et on les trie par ordre chrono & fin > date actuelle (pas encore fini)
        // left join car on veut tous les events et juste rajouter si on participe s'il existe déjà une entrée pour ça
        $query = "SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, p.participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                    JOIN participationsevents p ON e.id = p.idEvent
                    WHERE p.emailParticipe = ? AND p.participation = 'yes'
                        AND (e.idgroup = ? OR e.idgroup IN (SELECT idchild FROM grouprelations WHERE idfather = ?))
                        AND ".
                        ($voyages === FALSE ? "e.dateFin > NOW()" : "e.date < NOW()").
                    "ORDER BY e.date ASC";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_SESSION['email'], $group, $group));
        // Si on a au moins une réponse, c'est bon
        if ($sth->rowCount() > 0) {
            $msg = array('success' => 'Récupération réussie');
            // on met à jour le compteur
            Util::updateCompt($dbh, "convPart");           
            $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
            // on récupère les nb de participants
            for ($i = 0; $i < sizeof($resultSQL); $i++) {
                $resultSQL[$i] = array_merge($resultSQL[$i], Util::getParticipants($dbh, $resultSQL[$i]['id']));
            }
            array_push($msg, $resultSQL);
        } else {
            $msg = array('error' => 'Pas d\'événements trouvés pour l\'utilisateur : '.$_SESSION['email']);
        }
        return $msg;
    }

    /**
     * Function eventsProches(n , lng, lat)
     * 
     * n événements proches des coords données
     *
     * @return array
     * 'error' si pbm connexion DB
     * 'error' si pas d'événements trouvés
     * si réussi
        * success : "recup ok"
        * 0 : tableau de n événements proches (pour le moment juste les nb plus proches)
     */
    public static function eventsProches($lng, $lat, $group, $voyages = FALSE) 
    {      
        require 'database.class.php';
        $dbh = Database::connect();
        if (!$dbh) {
            $msg = array('error' => 'Connexion au serveur impossible');
            return $msg;
        }

        /* on récupère les événements auxquels on a accès (groupe by id pour éviter doublons si dans plusieurs rubriques 1 et 3 svt)
            * auquel on participe (pour récupérer notre réponse de participation)
            * les autres (participation = '')
        */ 
        $query = "SELECT * FROM (
                SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, p.participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN participationsevents p  ON e.id = p.idEvent
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                        WHERE p.emailParticipe = ? 
                            AND (e.idgroup = ? OR e.idgroup IN (SELECT idchild FROM grouprelations WHERE idfather = ?))
               UNION 
                SELECT e.id, e.titre, e.date, e.dateFin, e.lieu, e.lng, e.lat, e.infos, e.conf, e.dateCreation, e.nbPhotos, e.emailCreateur, e.idgroup, u.prenom, u.nom, '' as participation, g.nom as gnom, g.idgroup as gidgroup, g.image as gimage, g.infos as ginfos, g.conf as gconf, g.lng as glng, g.lat as glat, g.zoom as gzoom, g.datecreation as gdatecreation FROM events e
                    JOIN utilisateurs u ON u.email = e.emailCreateur 
                    LEFT JOIN groups g ON e.idgroup = g.idgroup
                        WHERE e.idgroup = ? OR e.idgroup IN (SELECT idchild FROM grouprelations WHERE idfather = ?) ) results
                GROUP BY id
                HAVING ".
                ($voyages === FALSE ? "results.dateFin > NOW()" : "results.date < NOW()").
                //"ORDER BY (lng-?)*(lng-?)+(lat-?)*(lat-?) ASC : on prend les 10 plus récents seulement pour le moment
                "ORDER BY results.date ASC
                LIMIT 15";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_SESSION['email'],$group, $group, $group, $group)); //, $lng, $lng, $lat, $lat));
        
         if ($sth->rowCount() > 0) {
            $result = array('success' => 'Récupération réussie');
            // on met à jour le compteur
            Util::updateCompt($dbh, "map");
            $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
            // on récupère les nb de participants
            for ($i = 0; $i < sizeof($resultSQL); $i++) {
                $resultSQL[$i] = array_merge($resultSQL[$i], Util::getParticipants($dbh, $resultSQL[$i]['id']));
            }
            array_push($result, $resultSQL);
        } else {
            $result = array('error' => 'Pas d\'événements trouvés pour vous ('.$_SESSION['email'].')');
        }
        return $result;
    }

    /**
     * Function updateCompt(dbh, compt)
     * 
     * augmenter le compteur compt de 1
     */
    public static function updateCompt($dbh, $compt) 
    {      
        // on crée la ligne pour le compteur
        // si on a déjà une entrée (ie meme mail), on met à jour le bon compteur
        $query = "INSERT INTO compteurs (email, map, convPub, convPart, search, updatePart) VALUES (?,1,0,0,0,0) ON DUPLICATE KEY UPDATE {$compt} = {$compt} + 1";
        $sth = $dbh->prepare($query);
        $sth->execute(array($_SESSION['email'])); 
    }

    /**
     * Function getParticipants(dbh, idEvent)
     * 
     * renvoyer l'objet avec no, yes et maybe et les nb associés
     */
    public static function getParticipants($dbh, $idEvent) 
    {      
        // on crée la ligne pour le compteur
        // si on a déjà une entrée (ie meme mail), on met à jour le bon compteur
        $query = "SELECT participation, COUNT(emailParticipe) as 'nb' FROM participationsevents
            WHERE idEvent = ?
            GROUP BY participation";
        $sth = $dbh->prepare($query);
        $sth->execute(array($idEvent)); 
        // renvoie un tableau de couple (part, nbpart)
        $resultSQL = $sth->fetchAll(PDO::FETCH_ASSOC);
        $results = array();
        foreach ($resultSQL as $result) {
            $results = array_merge($results, (array) array($result['participation'] => $result['nb']));
        }
        // on met à zéro si personne
        $results = array(
            'nbYes' => array_key_exists('yes', $results) ? $results['yes'] : "0",
            'nbNo' => array_key_exists('no', $results) ? $results['no'] : "0",
            'nbMaybe' => array_key_exists('maybe', $results) ? $results['maybe'] : "0"
        );
        return $results;
    }  
}
