<?php
require "../../include/MiniPaviCli.php"; // Inclusion de la librairie
error_reporting(E_ERROR|E_WARNING);
ini_set('display_errors',1);            // Mettre à 1 si l'on veut que les erreurs s'affichent

try {
    MiniPavi\MiniPaviCli::start();
    if (
        MiniPavi\MiniPaviCli::$fctn == "CNX" ||
        MiniPavi\MiniPaviCli::$fctn == "DIRECTCNX"
    ) {
        // C'est une nouvelle connexion.
        // Ici, vous pouvez initialiser ce que vous souhaitez
        // Par exemple, un contexte utilisateur contenant une variable "step" avec la
        // valeur "accueil"
        // Cette variables nous servira pour savoir quelle partie du script exécuter.
        // Toutes les variables du contexte seront dans un tableau associatif
        //clé/valeur qui sera ensuite "serializé" avec
        // la fonction serialize() de PHP

        $context = ["step" => "accueil"];
    } else {
        // Ce n'est pas une nouvelle connexion
        // Une touche de fonction a été saisie ou un évènement a eu lieu
        if (MiniPavi\MiniPaviCli::$fctn == "FIN") {
            // C'est la déconnexion de l'utilisateur
            // On peut en profiter pour effectuer des tâches nécessaires
            // lors de la déconnexion
            // Attention: cet évènement peut être appelé plusieurs fois de
            //suite
            exit();
        }
        // On récupère le contacte utilisateur.
        $context = unserialize(MiniPavi\MiniPaviCli::$context);
        // On récupère la touche de fonction
        $fctn = MiniPavi\MiniPaviCli::$fctn;
        // On récupère la saisie utilisateur
        $content = MiniPavi\MiniPaviCli::$content;
    }
    // On initialise quelques variables
    $vdt = ""; // Le contenu videotex à envoyer au Minitel de l'utilisateur
    $cmd = null; // La commande à exécuter au niveau de MiniPAVI. Par défaut, aucune.
    while (true) {
        // On execute la partie du script qui correspond à la valeur de la variable
        // "step"
        switch ($context["step"]) {
            case "accueil":
                // Accueil du service : on affiche une page et on attend que
                //l'utilisateur tape sur SUITE
                // On remplit la variable $vdt au fur et à mesure
                // Effacement de l'écran et suppression de l'echo local du
                // Minitel au cas où
                $vdt =
                    MiniPavi\MiniPaviCli::clearScreen() .
                    PRO_MIN .
                    PRO_LOCALECHO_OFF;
                // Récupération du contenu d'une page videotext, que l'on ajoute
                //à l'effacement d'écran précédent
                // Le fichier de la page doit exister et être lisible depuis ce
                //script
                //$vdt.= file_get_contents('mapage.vdt');
                // On affiche la date et l'heure en ligne 10, colonne 5,
                // en Rouge
                $vdt .= MiniPavi\MiniPaviCli::setPos(1, 10);
                $vdt .= VDT_TXTRED . "service s\x19\x42ecuris\x19\x42e ph0wn";
                // Lors du prochain appel du script par la passerelle, on
                // exécutera la partie 'accueil-traitement-saisie'
                // pour traiter la saisie de l'utilisateur
                $context["step"] = "accueil-traitement-saisie";

                // On attend une saisie utilisateur : on ne rappelle pas le
                // script immédiatement
                $directCall = false;
                break 2; // On sort du bloc "switch" et "while"
            case "accueil-traitement-saisie":
                // On n'accepte qu'un appui sur la touche SUITE...
                if ($fctn == "SUITE") {
                    // L'utilisateur a appuyé sur 'SUITE'.
                    // On veut maintenant exécuter la partie du script 'menu'
                    $context["step"] = "menu";
                    // On continue le script dans sa partie 'menu': on sort
                    // du bloc 'switch', mais on reste dans le bloc 'while'
                    break;
                }
                // sinon, on lui affiche un message en ligne "0"
                // et on attend de nouveau qu'il appuies sur "SUITE"
                $vdt = MiniPavi\MiniPaviCli::writeLine0("Tapez sur Suite !");
                // On ne modifie pas la valeur de 'step', qui a déjà la valeur
                // 'accueil-traitement-saisie'
                // On attend une saisie utilisateur : on ne rappelle pas le
                // script immédiatement
                $directCall = false;
                break 2; // On sort du bloc "switch" et "while"
            case "menu":
                // Effacement de l'écran
                $vdt = MiniPavi\MiniPaviCli::clearScreen();
                $vdt .= MiniPavi\MiniPaviCli::setPos(1, 10);
                $vdt .= VDT_TXTRED . "mot de passe:";
                $cmd = MiniPavi\MiniPaviCli::createInputTxtCmd(
                    1,
                    11,
                    40,
                    MSK_ENVOI | MSK_SOMMAIRE,
                    true,
                    "",  // prechar
		    "",	  // hidechar
		    ""   // word prefill
		);
		//$cmd = MiniPavi\MiniPaviCli::createInputMsgCmd (15, 10, 24, 2, MSK_ENVOI | MSK_SOMMAIRE, true, "", "", "");
                // Lors du prochain appel du script par la passerelle, on
                // executera la partie 'menu-saisie'
                // pour traiter la saisie de l'utilisateur
                $context["step"] = "menu-saisie";
                // On attend une saisie utilisateur : on ne rappelle pas le
                //script immédiatement
                $directCall = false;
                break 2; // On sort du bloc "switch" et "while"
            case "menu-saisie":
                // L'utilisateur a donc validé sa saisie avec ENVOI ou SOMMAIRE
                // Si c'est ENVOI : on lui affiche ce qu'il a tapé
                // Si c'est SOMMAIRE : on revient à l'accueil
                if ($fctn == "SOMMAIRE") {
                    // L'utilisateur a appuyé sur 'SOMMAIRE'.
                    // On veut maintenant exécuter la partie du script
                    // 'accueil'
                    $context["step"] = "accueil";
                    // On continue le script dans sa partie 'accueil': on
                    // sort du bloc 'switch', mais on reste dans le bloc
                    // 'while'
                    break;
                }
                // C'est donc la touche ENVOI qui a été tapée
                // Effacement de l'écran
		$vdt = MiniPavi\MiniPaviCli::clearScreen();
		//$vdt .= $content[0];
                // A la ligne 12, on affiche, centré, en magenta, le texte 'Vous
		// avez tapé'

		ob_start();
		$err = 42;
		passthru("../../include/secret ".escapeshellarg($content[0]),$err);
		$result = ob_get_clean(); 
		if ($err === 0) {
			$vdt .= MiniPavi\MiniPaviCli::writeCentered(12, "bien joue! ph0wn(neverForgetSimpleStuff)", VDT_TXTRED);
		} else {
			$vdt .= MiniPavi\MiniPaviCli::setPos(1, 10);
			$vdt .= VDT_TXTRED."le mot de passe suivant est erron\x19\x42e:";
			$vdt .= MiniPavi\MiniPaviCli::setPos(1, 11);
			$vdt .= VDT_TXTWHITE.$result;
                };
                // A la ligne 14, on affiche, centré, en jaune, le texte saisi
                // par l'utilisateur
                /*$vdt .= MiniPavi\MiniPaviCli::writeCentered(
                    14,
                    $content[0],
                    VDT_TXTYELLOW
		);*/
                // Maintenant, on attend qu'il tape n'importe quelle touche de
                // fonction pour revenir à la saisie d'un texte
                // (partie "menu" du script)
                $context["step"] = "menu";
                // On attend une saisie utilisateur : on ne rappelle pas le
                // script immédiatement
                $directCall = false;
                break 2; // On sort du bloc "switch" et "while"
        }
    }
    // Url à appeler lors de la prochaine saisie utilisateur (ou sans attendre si
    // directCall=true)
    // On reprend l'Url du script courant que l'on va placer dans la variable $nextPage
    if (!empty($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) !== "off") {
        $prot = "https";
    } elseif (
        isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) &&
        strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https"
    ) {
        $prot = "https";
    } elseif (
        !empty($_SERVER["HTTP_FRONT_END_HTTPS"]) &&
        strtolower($_SERVER["HTTP_FRONT_END_HTTPS"]) !== "off"
    ) {
        $prot = "https";
    } elseif (
        isset($_SERVER["SERVER_PORT"]) &&
        intval($_SERVER["SERVER_PORT"]) === 443
    ) {
        $prot = "https";
    } else {
        $prot = "http";
    }
    $nextPage =
        $prot . "://" . $_SERVER["HTTP_HOST"] . "" . $_SERVER["PHP_SELF"];
    // On envoi à la passerelle le contenu à afficher ($vdt), l'url du prochain script
    // à appeler ($nextPage)

    // le contexte utilisateur sérialisé ($context), l'éventuelle commande à exécuter
    // On active l'echo de caractères pour que l'utilisateur voit ce qu'il tape
    // Si $directCall = true, le script sera appellé immédiatement
    MiniPavi\MiniPaviCli::send(
        $vdt,
        $nextPage,
        serialize($context),
        true,
        $cmd,
        $directCall
    );
} catch (Exception $e) {
    throw new Exception("Erreur MiniPavi " . $e->getMessage());
}
exit();
?>

