<?php
/**
 * @file miniChat.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.1 Novembre 2023
 *
 * Script de service de dialogue en direct basique pour Minitel via passerelle MiniPAVI
 * avec support chatGPT. Réalisé sans nécessité de BD
 *
 * A des fins de démonstration - Licence GNU GPL
 * 
 */

require "../../include/MiniPaviCli.php";// A MODIFIER
require "miniChatFunctions.php";
require "animGPT.php";

const MINICHAT_MAXCONN = 100;	// Nombre de connectés maximum
const MINICHAT_NUMPARPAGE = 16;	// Nombre de connectés apparaissant sur chaque page
const MINICHAT_TIMEOUT = 900;	// Temps maximum sans action avant d'être supprimé du chat

const MINICHAT_CHATGPT = true;	// Activation de chatgpt


//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR|E_WARNING);
ini_set('display_errors',0);		// Mettre à 1 si l'on veut que les erreurs s'affichent


/****************************************************
Guide (très)Rapide pour Développer son service Minitel
Structure générale d'un service

Généralement, votre service Minitel s'articule autour d'un ensemble de pages videotex
qui s'afficheront et à partir desquelles l'utilisateur devra effectuer une saisie.

Selon cette saisie, une action sera effectuée, et une nouvelle page sera affichée.

Le service (Minitel) s'articule autour d'un script qui contient
une structure switch/case, le tout dans une boucle while infinie.

Chaque page du service regroupe un ensemble de "case" qui
correspondent à:

- Affichage de la partie fixe de la page:
	Exemple: Affichage du titre "Liste des connectés" et de la liste vide.
	
- Affichage de l'éventuelle partie variable de l'affichage:
	Exemple: Effacement de l'éventuelle précédente liste de connectés et réaffichage de cette liste
	
- Initialisation de la commande de saisie:
	Exemple Déterminer si l'utilisateur doit saisir un choix, un message multilignes..
	
- Traitement de la saisie utilisateur
	Test de la touche de fonction pressée (Envoi, Suite, etc.) et traitement.

Chaque "case" se termine par un "break" ou "break 2":
	- "break": le script va continuer en executant la case correspondant à la valeur de la variable $step
	- "break 2": le script s'arrête. Typiquement après l'initialisation de la commande de saisie (on attend en effet l'action utilisateur)

Lors de l'appel du script par la passerelle MiniPavi, la première instruction doit être
MiniPaviCli::start(), laquelle va initialiser la classe MiniPavi qui représente l'utilisateur et 
donne accès à plusieurs variables:

$step : l'étape de l'execution du script
$content : tableau de la saisie utilisateur
$fctn : la touche de fonction utilisée
$context: une zone à disposition du service qui est rappellé à chaque appel du script 
et que le script peut faire varier
$uniqueId: Identifiant unique de la connexion au niveau de la passerelle
$remoteAddr: ip de l'utilisateur
$typesocket: le type de connexion, 'websocket' (connexion via websocket) ou 'other' (connexion RTC)
$urlParams: paramètres indiqués dans l'url du script

A la fin du traitement, le script doit appeller MiniPaviCli::send en indiquant notamment les paramètres représentant 
la prochaine url à appeller, le contexte utilisateur, la saisie attendue et la page videotex à afficher.

Si votre service n'est que sur un seul script (comme celui-ci par exemple), alors l'url sera toujours la même, et seul un paramètre "step"
indiquera quelle partie du script doit être executé.

Un service peut bien sûr être développé sur plusieurs scripts différents, et pas tout dans un seul énorme fichier PHP (pas pratique si le service est complexe).

L'accès à l'émulateur Minitel connecté à MiniPavi est dispo sur http://www.minipavi.fr/emulminitel/
Pour tester ce script, saisissez le code "MINICHAT"

Pour tester vos scripts avec l'émulateur, allez sur:
http://www.minipavi.fr/emulminitel/?url=[url de votre script]
Exemple : http://www.minipavi.fr/emulminitel/index.php?url=http://www.monsite.com/monscript.php

Enjoy!

*****************************************************/

try {
	
	// On commence toujours par cela
	MiniPavi\MiniPaviCli::start();

	if (MiniPavi\MiniPaviCli::$fctn == 'BGCALL') {
		// Il s'agit d'une requête programmée effectuée en arrière plan par MiniPavi
		// Dans miniChat, c'est pour traiter un message dont le destinataire est chatGPT		
		// le paramètre "id" de la requête contient l'identifiant du "connecté" chatGPT
		// qui a reçu un message
		if (MINICHAT_CHATGPT) {
			
			$destId=cGPT_messageReceived(@MiniPavi\MiniPaviCli::$urlParams->id);
			if ($destId>0) {
				// chatGPT a répondu: on prévient le destinataire
				$flist = mchat_openListFile();
				mchat_updateLastAction($flist,@MiniPavi\MiniPaviCli::$urlParams->id);			// Le connecté AI est encore en vie!
				@fclose($flist);	
				$cmd = MiniPavi\MiniPaviCli::createPushServiceMsgCmd(array('Vous avez recu un message!'),array($destId));
				MiniPavi\MiniPaviCli::send('','','',true,$cmd);
			} else
				// Pas de réponse effectuée (cas si le connecté chatGPT a été supprimé entre temps)
				MiniPavi\MiniPaviCli::send('','','',true);
			exit;
		}
	}
	
	if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
		// Nouvelle connexion
		$context = array('step'=>'accueil');
		MiniPavi\MiniPaviCli::$content=array();
		trigger_error("[MiniChat] CNX");
	} else {
		// Connexion en cours
		$context = @unserialize(MiniPavi\MiniPaviCli::$context);	// Récupération du contexte utilisateur
		$step = $context['step'];	// Etape du script à executer, indiqué dans le paramètre 'url' de la requête http
	}

	
	if (MiniPavi\MiniPaviCli::$fctn == 'FIN') {
			// Deconnexion 
			trigger_error("[MiniChat] DECO");
			$flist = mchat_openListFile();
			$fmsg = mchat_openMsgFile();
			$tCnx = mchat_getConnectedList($flist,$numCnx,true);
			$message = 'Un connecté est parti!';
			foreach($tCnx as $k=>$cnx) {
				if ($cnx['id'] == MiniPavi\MiniPaviCli::$uniqueId) {
					$message = $cnx['name'].' vient de nous quitter!';
					unset($tCnx[$k]);
					break;
				}
			}
			mchat_removeConnected($flist,$fmsg,MiniPavi\MiniPaviCli::$uniqueId);

			@fclose($flist);
			@fclose($fmsg);
			if (MINICHAT_CHATGPT) {
				// Si charGPT activé, on supprime l'historique des conversations
				$fctx=cGPT_openHistoFile();
				cGPT_removeHisto($fctx,0,MiniPavi\MiniPaviCli::$uniqueId);
				@fclose($fctx);
			}
			
			// On prévient, en ligne 0, les autres connectés du départ de celui-ci
			$tUniqueId = array();
			$cmd = null;
			foreach($tCnx as $cnx) {
				if (substr($cnx['id'],0,4) != 'CGPT') {
					$tUniqueId[]=$cnx['id'];
					$tMsg[]=$message;
				}
			}
			if (count($tUniqueId)>0) {
				$cmd = MiniPavi\MiniPaviCli::createPushServiceMsgCmd($tMsg,$tUniqueId);
				MiniPavi\MiniPaviCli::send('','','',false,$cmd);
			}
			exit;
	}
	

	
	$flist = mchat_openListFile();
	$found=mchat_updateLastAction($flist,MiniPavi\MiniPaviCli::$uniqueId);		// On garde le timestamp du dernier accès, pour faire un nettoyage de temps en temps, au cas ou.
	@fclose($flist);

	if (!$found && strpos($step,'accueil-')===false) {
		// L'utilisateur n'est pas dans la liste des connectés et est sur une étape après l'identification: on le renvoi au début
		$step = 'accueil';
		$context=array();
	}
	
	$flist = mchat_openListFile();
	$fmsg = mchat_openMsgFile();
	mchat_chatClean($flist,$fmsg);	// Nettoyage des vieilles connexions éventuelles
	@fclose($flist);
	@fclose($fmsg);

	
	$vdt='';		// Contenu à envoyer au Minitel de l'utilisateur
	$cmd=null;		// Commande à executer au niveau de MiniPAVI
	
	
	if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
		$prot='https';
	} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
		$prot='https';
	} elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
		$prot='https';
	} elseif (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443) {
		$prot='https';
	} else
		$prot='http';
	
	
	while(true) {
		switch ($step) {
			case 'accueil':
				// Accueil: affichage partie fixe
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('MiniChatAcc.vdt');
				
				$vdt.= MiniPavi\MiniPaviCli::setPos(12,9);
				$vdt.= VDT_BGBLUE.' '.MiniPavi\MiniPaviCli::toG2('www.minipavi.fr');
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(7,15);
				$vdt.=VDT_TXTYELLOW.VDT_FDINV.VDT_BLINK.MiniPavi\MiniPaviCli::toG2('> Service animé par ChatGPT <');
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(2,18);
				$vdt.=VDT_TXTWHITE.'Pseudonyme:..........';
				$vdt.=' '.VDT_FDINV.' Suite '.VDT_FDNORM;
				$vdt.=MiniPavi\MiniPaviCli::setPos(2,19);
				$vdt.=VDT_TXTBLUE.MiniPavi\MiniPaviCli::toG2('Nom sous lequel vous apparaissez');
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(2,21);
				$vdt.=VDT_TXTWHITE.'CV:......................';
				$vdt.=MiniPavi\MiniPaviCli::setPos(2,22);
				$vdt.=VDT_TXTBLUE.MiniPavi\MiniPaviCli::toG2('Court texte de présentation');
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,23);
				$vdt.=VDT_TXTRED;
				$vdt.=MiniPavi\MiniPaviCli::repeatChar('_',40);
				$vdt.=MiniPavi\MiniPaviCli::setPos(25,24);
				$vdt.=VDT_TXTWHITE.'Valider ';
				$vdt.=VDT_TXTBLACK.VDT_BGGREEN.' Envoi '.VDT_TXTWHITE;

				$step = 'accueil-init-saisie-pseudo';
				
				break;		// On continue le script

			case 'accueil-init-saisie-pseudo':
				// Accueil: initialisation de la zone de saisie du pseudo
				// Zone en colonne 13, ligne 18, longueur 10, accepte les touche Envoi et Suite, affiche le curseur,
				// '.' est le caractère de remplissage, pas de caractère 'alternatif' (pour cacher la saisie, style mot de passe)
				// pré-remplissage par la valeur du pseudo
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(13,18,10,MSK_ENVOI|MSK_SUITE,true,'.','',@$context['pseudo']);

				$step = 'accueil-traite-saisie-pseudo';
				$directCall=false;
				break 2;	// On arrête le script et on attend une saisie utilisateur ($directCall = false)

			case 'accueil-traite-saisie-pseudo':
				// Accueil: traitement de la saisie de la touche de fonction pourt la 1ere zone de saisie (pseudo)
				$context['pseudo']=@MiniPavi\MiniPaviCli::$content[0];
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					// initialisation de la zone de saisie du cv
					$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(5,21,22,MSK_ENVOI|MSK_RETOUR,true,'.','',@$context['cv']);
					$step = 'accueil-traite-saisie-cv';
					$directCall=false;
					break 2;
				}

				// C'est donc ENVOI : traitement de la saisie des 2 zones (pseudo + cv)
				$step = 'accueil-verif-pseudo';
				break;

			case 'accueil-traite-saisie-cv':
				// Accueil: traitement de la saisie de la touche de fonction pourt la 2ème zone de saisie (cv)
				$context['cv']=@MiniPavi\MiniPaviCli::$content[0];
				if (MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					// Retour à la zone de saisie du pseudo
					$step = 'accueil-init-saisie-pseudo';
					break;
				}
				// C'est donc ENVOI : traitement de la saisie des 2 zones (pseudo + cv)
				// Les 2 lignes suivantes sont inutiles car l'étape 20 suit, mais pour plus de compréhension on les met.
				$step = 'accueil-verif-pseudo';
				break;

			case 'accueil-verif-pseudo':
				// Accueil: traitement de la saisie du contenu des zones de saisie
				if (strlen(@$context['pseudo'])<3) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Votre pseudo doit faire 3 car. min.');
					$step = 'accueil-init-saisie-pseudo';
					break;
				}
				
				$flist = mchat_openListFile();
				$tCnx = mchat_getConnectedList($flist,$numCnx);
				@fclose($flist);
				
				
				if ($numCnx>=MINICHAT_MAXCONN) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Le chat est déjà plein ! Désolé !');
					$step = 'accueil-init-saisie-pseudo';
					break;
					
				}
				foreach($tCnx as $cnx) {
					if (@$cnx['id'] != '' && @$cnx['name'] == trim($context['pseudo'])) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Ce pseudo est déjà pris !');
						$step = 'accueil-init-saisie-pseudo';
						break 2;
					}
				}
				
				$flist = mchat_openListFile();
				mchat_addConnected($flist,MiniPavi\MiniPaviCli::$uniqueId,$context['pseudo'],@$context['cv']);
				@fclose($flist);
				
				trigger_error("[MiniChat] ".($numCnx+1)." connecte(s)");
				$step = 'accueil-infos';
				break;
				
			case 'liste-partie-fixe':
				// Liste des connectés : affichage partie fixe
				$context['page']=0;
				$context['pagestartindex']=0;
				$context['linetoclear']=MINICHAT_NUMPARPAGE;
				$vdt= MiniPavi\MiniPaviCli::clearScreen();
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,2).VDT_TXTYELLOW.VDT_BGBLUE.VDT_SZDBLH.' Le Dialogue en Direct !'.VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,4).VDT_TXTBLACK.VDT_BGGREEN.VDT_SZNORM.' Num.|    Pseudo|CV'.VDT_CLRLN.VDT_TXTWHITE; 
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,21).VDT_BGBLUE.' '.VDT_CLRLN;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.VDT_BGBLACK.MiniPavi\MiniPaviCli::toG2('Ecrire : numéro .. +').VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTBLUE.' G=ChatGPT'.VDT_TXTWHITE;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.'Lire vos messages   '.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE.VDT_BGBLACK;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.'Pages'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Suite '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE.VDT_BGBLACK;				
				$vdt.=VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Retour '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE;								
				$vdt.=' Sortir'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Sommaire '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE;				

				$step = 'liste-partie-variable';
				
				break;

			case 'liste-partie-variable':
				// Liste des connectés : affichage partie variable (liste des connectés) et mise à jour eventuelle des AI chatGPT
				
				if (MINICHAT_CHATGPT) {
					// Mise à jour de la liste des connectés avec chatGPT
					cGPT_populate();
				}
				
				$vdt.=VDT_CUROFF;
				
				for($i=0;$i<$context['linetoclear'];$i++) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+$i).MiniPavi\MiniPaviCli::repeatChar(' ',5).'|'.MiniPavi\MiniPaviCli::repeatChar(' ',10).'| '.VDT_CLRLN; 
				}
			
				$flist = mchat_openListFile();
				$tCnx = mchat_getConnectedList($flist,$numCnx,true);
				@fclose($flist);				
				
				if ($numCnx<2) 
					$txt="Vous êtes seul".VDT_CLRLN;
				else $txt="$numCnx connectés!".VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(26,1).MiniPavi\MiniPaviCli::toG2($txt);
				$vdt.=MiniPavi\MiniPaviCli::setPos(26,2).'Page '.($context['page']+1).'/'.ceil(($numCnx/MINICHAT_NUMPARPAGE));
				

				$start = MINICHAT_NUMPARPAGE * $context['page'];
				for($i=0;$i<$start;$i++) {
					next($tCnx);
				}
				
				$j=0;
				
				do {
					if (($cnx = current($tCnx)) === false)
						break;
					$i = key($tCnx);
					if ($cnx['id'] == MiniPavi\MiniPaviCli::$uniqueId)
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+$j).VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(sprintf('%5d',($i+1))).'|'.VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(sprintf('%10s',ucfirst(strtolower($cnx['name'])))).VDT_TXTWHITE.'|'.VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(ucfirst(strtolower(sprintf('%-22s',$cnx['cv'])))).VDT_CLRLN;
					else {
						if ($cnx['type'] == MCHAT_TYPE_CGPT)
							$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+$j).VDT_TXTBLUE.'G '.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(sprintf('%3d',($i+1))).'|'.MiniPavi\MiniPaviCli::toG2(sprintf('%10s',ucfirst(strtolower($cnx['name'])))).'|'.VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(ucfirst(strtolower(sprintf('%-22s',$cnx['cv'])))).VDT_CLRLN;
						else
							$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+$j).VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(sprintf('%5d',($i+1))).'|'.MiniPavi\MiniPaviCli::toG2(sprintf('%10s',ucfirst(strtolower($cnx['name'])))).'|'.VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(ucfirst(strtolower(sprintf('%-22s',$cnx['cv'])))).VDT_CLRLN;							
					}
					$j++;
					next($tCnx);
					if ($j==MINICHAT_NUMPARPAGE) {
						break;
					}
				} while(true);
				$toclear = $context['linetoclear']-$j;
				
				$context['linetoclear']=$j;
				
				for(;$j<$toclear;$j++)
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+$j).MiniPavi\MiniPaviCli::repeatChar(' ',5).'|'.MiniPavi\MiniPaviCli::repeatChar(' ',10).'|'.MiniPavi\MiniPaviCli::repeatChar(' ',22).VDT_CLRLN;
				
				$step = 'liste-init-saisie';
				break;

			case 'liste-init-saisie':
				// Liste des connectés : création de la zone de saisie et affichage nombre de messages en attente
				$fmsg = mchat_openMsgFile();				
				$tMsg = mchat_getMsgFor($fmsg,@MiniPavi\MiniPaviCli::$uniqueId);
				@fclose($fmsg);
				
				$numMsg = count($tMsg);
				if ($numMsg<1) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,21).VDT_TXTCYAN.VDT_BGBLUE.' Aucun message en attente'.VDT_CLRLN;
				} else {
					if ($numMsg == 1)
						$s=''; else $s='s';
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,21).VDT_TXTCYAN.VDT_BGBLUE." $numMsg message$s en attente".VDT_CLRLN;
				}
				
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(17,22,2,MSK_ENVOI|MSK_SUITE|MSK_RETOUR|MSK_REPETITION|MSK_SOMMAIRE,true,'.','','');
				$step = 'liste-traite-saisie';
				$directCall=false;
				break 2;
				
			case 'liste-traite-saisie':
				// Liste des connectés : traitement de l'entrée utilisateur
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					// Sortie du chat
					$flist = mchat_openListFile();
					$fmsg = mchat_openMsgFile();
					mchat_removeConnected($flist,$fmsg,MiniPavi\MiniPaviCli::$uniqueId);
					@fclose($flist);
					@fclose($fmsg);
					
					$step = 'accueil';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					// Réaffichage de la partie variable de la page
					$vdt=MiniPavi\MiniPaviCli::writeLine0(' ');
					$step = 'liste-partie-variable';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					// Passage à la page suivante si possible
					$flist = mchat_openListFile();
					$tCnx = mchat_getConnectedList($flist,$numCnx);
					@fclose($flist);
					if (($context['page']+1)*MINICHAT_NUMPARPAGE >= $numCnx) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Dernière page atteinte!');
						$step = 'liste-partie-variable';
						break;

					}
					$context['page']++;
					$vdt=MiniPavi\MiniPaviCli::writeLine0('');
					$step = 'liste-partie-variable';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					// Passage à la page précédente si possible
					$flist = mchat_openListFile();
					$tCnx = mchat_getConnectedList($flist,$numCnx);
					@fclose($flist);
					if (($context['page']-1)<0) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Première page atteinte!');
						
						$step = 'liste-partie-variable';
						break;
					}
					$context['page']--;
					$vdt=MiniPavi\MiniPaviCli::writeLine0('');
					$step = 'liste-partie-variable';
					break;
				}
				if (trim(@MiniPavi\MiniPaviCli::$content[0])=='') {
					// ENVOI seul: lecture des messages
					$step = 'lecture-partie-fixe';
					break;
				}
				$numDest = (int)@MiniPavi\MiniPaviCli::$content[0];
				if ($numDest<=0) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Saisie incorrecte!');
					$step = 'liste-init-saisie';
					break;
				}
				$numDest--;
				$flist = mchat_openListFile();
				$tCnx = mchat_getConnectedList($flist,$numCnx,true);
				@fclose($flist);
				if (!isset($tCnx[$numDest]) || $tCnx[$numDest]['id']=='') {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Destinataire inconnu!');
					$step = 'liste-init-saisie';
					break;
				}
				$context['sendiddest'] = $tCnx[$numDest]['id'];
				$context['sendnamedest'] = $tCnx[$numDest]['name'];
				$context['sendtypedest'] = $tCnx[$numDest]['type'];
				unset($context['sendidmsg']);
				$step = 'ecriture-partie-fixe';
				break;
			
			case 'accueil-infos':
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,10).VDT_CUROFF;
				for($i=0;$i<13;$i++) {
					$vdt.=VDT_CLRLN.VDT_CRLF;
				}
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(11,"Quelques explications...",VDT_TXTYELLOW);
				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(13,"Ce chat vous permet de dialoguer avec");
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(14,"les autres connectés, mais aussi avec");
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(15,"des connectés \"ChatGPT\" !");
				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(16,"N'hésitez pas à entamer la conversation");
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(17,"de manière totalement naturelle!");
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(18,"Par exemple: \"Slt! ca va?\",\"Ca roule?\"");
	
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(19,"Ensuite, laissez le temps à votre");
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(20,"interlocuteur pour répondre!");

				$vdt.=MiniPavi\MiniPaviCli::writeCentered(21,"Vous serez averti en haut de votre écran");
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(22,"lorsque vous recevrez un message.");

				$vdt.=MiniPavi\MiniPaviCli::setPos(13,24);
				$vdt.=VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2('Accèder au dialogue ');
				$vdt.=VDT_TXTBLACK.VDT_BGGREEN.' Suite '.VDT_TXTWHITE;

				$step = 'accueil-infos-saisie';
				break 2;

			case 'accueil-infos-saisie':
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					$step = 'liste-partie-fixe';
					break;
				}
				break 2;
				
			case 'lecture-partie-fixe':
				// Lecture des messages: Affichage partie fixe
				$fmsg = mchat_openMsgFile();
				$tMsg = mchat_getMsgFor($fmsg,@MiniPavi\MiniPaviCli::$uniqueId);
				@fclose($fmsg);
				
				if (count($tMsg)<1) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Aucun message en attente!');
					if (@$context['frommsgsend']==1) {
						$step = 'liste-partie-fixe';
						unset($context['frommsgsend']);
					} else {
						$step = 'liste-init-saisie';
					}
					break;
					
				}

				$vdt= MiniPavi\MiniPaviCli::clearScreen();
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,1).VDT_TXTYELLOW.VDT_BGBLUE.' Message de '.VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,8).VDT_TXTYELLOW.VDT_BGBLUE.' Vous lui aviez dit :'.VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,15).VDT_TXTYELLOW.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Votre réponse :').VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,22).VDT_BGBLUE.' '.VDT_CLRLN;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.VDT_BGBLACK.'Changer de ligne'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Suite '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE;			
				$vdt.=VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Retour '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE.' ';
				$vdt.=VDT_CRLF.VDT_TXTWHITE.'Envoi message'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE;
				$vdt.=VDT_TXTWHITE.' Sortir'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Sommaire '.VDT_FDNORM.VDT_STOPUNDERLINE;

				$step = 'lecture-partie-variable';
				break;

			case 'lecture-partie-variable':
				// Lecture des messages: Affichage du message
				$vdt.=VDT_CUROFF.VDT_TXTWHITE;
				$vdt.=MiniPavi\MiniPaviCli::setPos(13,1);
				$vdt.=MiniPavi\MiniPaviCli::toG2(ucfirst(strtolower($tMsg[0]['nameexp'])).' à ').date('H:i');
				
				foreach (@$tMsg[0]['content'] as $k=>$line) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,2+$k);
					$vdt.=MiniPavi\MiniPaviCli::toG2($line);
				}
				
				$vdt.=VDT_CUROFF;
				
				if (is_array($tMsg[0]['prevcontent']) && count($tMsg[0]['prevcontent'])>0) {
					foreach (@$tMsg[0]['prevcontent'] as $k=>$line) {
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,9+$k);
						$vdt.=VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2($line);
					}
				}
				
				$context['sendidmsg']=$tMsg[0]['idmsg'];	// On garde l'identifiant du message
				unset($context['sendiddest']);				// Suppression de l'éventuelle id destinataire initialisé lors de l'envoi direct d'un message

				$step = 'ecriture-init-saisie';
				break;

			case 'ecriture-init-saisie':
				// Lecture-écriture des messages : création de la zone de saisie
				// Zone en colonne 1, ligne 16, longueur 40, heuteur (lignes) 6, accepte les touche Envoi et Suite, affiche le curseur,
				// ' ' est le caractère de remplissage
				
				$cmd=MiniPavi\MiniPaviCli::createInputMsgCmd(1,16,40,6,MSK_ENVOI|MSK_SOMMAIRE,true,' ');
				$step = 'ecriture-traite-saisie';
				$directCall=false;
				break 2;

			case 'ecriture-traite-saisie':
				// Lecture-écriture des messages : traitement de la saisie utilisateur
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					// Abandon de saisie, retour à la liste
					unset($context['sendidmsg']);
					unset($context['sendiddest']);
					$step = 'liste-partie-fixe';
					break;
				}
				
				if (isset($context['sendidmsg'])) {
					// Réponse à un message
					$fmsg = mchat_openMsgFile();
					$tMsg = mchat_getMsgFor($fmsg,0,$context['sendidmsg']);
					@fclose($fmsg);
					if (count($tMsg)!=1) {
						// Message à disparu, ne devrait pas arriver
						unset($context['sendidmsg']);
						$step = 'liste-partie-fixe';
						break;
					}
					$fmsg = mchat_openMsgFile();
					mchat_delMsg($fmsg,$tMsg[0]['idmsg']);
					@fclose($fmsg);
					
					if (trim(implode(@MiniPavi\MiniPaviCli::$content)!='')) {
						// Le message n'est pas vide
						$fmsg = mchat_openMsgFile();
						$flist = mchat_openListFile();
						mchat_setMsg($fmsg,$flist,@MiniPavi\MiniPaviCli::$uniqueId,$tMsg[0]['idexp'],MCHAT_TYPE_NORMAL,$tMsg[0]['typeexp'],$context['pseudo'],$tMsg[0]['nameexp'],@MiniPavi\MiniPaviCli::$content,$tMsg[0]['content']);
						@fclose($flist);
						@fclose($fmsg);
						if ($tMsg[0]['typeexp'] == MCHAT_TYPE_NORMAL) {
							// On prévient le destinataire	
							$cmd = MiniPavi\MiniPaviCli::createPushServiceMsgCmd(array('Vous avez recu un message!'),array($tMsg[0]['idexp']));
						} else {
							// Message pour ChatGPT
							// Demande d'appel différé par MiniPavi afin de traiter le message
							$tUrl=array($prot."://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'].'?step=1000&id='.$tMsg[0]['idexp']);
							$tTime=array(time()+rand(5,10));	// Leger différé de la requête, pour que ca fasse plus vrai: personne ne réponds immédiattement
							$tUniqueId=array(MiniPavi\MiniPaviCli::$uniqueId);
							$cmd = MiniPavi\MiniPaviCli::createBackgroundCallCmd($tUrl,$tTime,$tUniqueId,array(false));
						}
					}
				} else {
					// Envoi d'un nouveau message
					$fmsg = mchat_openMsgFile();
					$flist = mchat_openListFile();
					mchat_setMsg($fmsg,$flist,@MiniPavi\MiniPaviCli::$uniqueId,$context['sendiddest'],MCHAT_TYPE_NORMAL,$context['sendtypedest'],$context['pseudo'],$context['sendnamedest'],@MiniPavi\MiniPaviCli::$content);					
					@fclose($flist);
					@fclose($fmsg);
					
					if ($context['sendtypedest'] == MCHAT_TYPE_NORMAL) {
						// On prévient le destinataire
						$cmd = MiniPavi\MiniPaviCli::createPushServiceMsgCmd(array('Vous avez recu un message!'),array($context['sendiddest']));
					} else {
						// ChatGPT
						// Demande d'appel différé par MiniPavi afin de traiter le message
						$tUrl=array($prot."://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'].'?step=1000&id='.$context['sendiddest']);
						$tTime=array(time()+rand(5,10));	// Leger différé de la requête, pour que ca fasse plus vrai: personne ne réponds immédiattement
						$tUniqueId=array(MiniPavi\MiniPaviCli::$uniqueId);
						$cmd = MiniPavi\MiniPaviCli::createBackgroundCallCmd($tUrl,$tTime,$tUniqueId,array(false));
					}
				}

				if (isset($context['sendidmsg'])) {
					// La saisie du message fait suite à la lecture d'un message reçu: on passa au message suivant
					unset($context['sendidmsg']);
					unset($context['sendiddest']);
					unset($context['sendtypedest']);
					$step = 'lecture-partie-fixe';
					$directCall=true;
					$context['frommsgsend']=1;
					// On ne pars pas directement à l'étape 50 (message suivant) car autrement la commande d'envoi push ne partira pas
					// On envoi donc à MiniPavi avec demande d'appel direct de l'url suivante (sans attendre de saisie utilisateur)
					break 2;
				} else {
					// La saisie du message fait suite à la création d'un nouveau message: on retourne à la liste
					unset($context['sendiddest']);
					unset($context['sendnamedest']);
					unset($context['sendtypedest']);
					$step = 'liste-partie-fixe';
					$directCall=true;
					// On ne pars pas directement à l'étape 21 (liste) car autrement la commande d'envoi push ne partira pas
					// On envoi donc à MiniPavi avec demande d'appel direct de l'url suivante (sans attendre de saisie utilisateur)
					break 2;
				}

			case 'ecriture-partie-fixe':
				// Envoi d'un nouveau messages: Affichage partie fixe
				$vdt= MiniPavi\MiniPaviCli::clearScreen();
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,15).VDT_TXTYELLOW.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Message à : '.ucfirst(strtolower($context['sendnamedest'])) ).VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,22).VDT_BGBLUE.' '.VDT_CLRLN;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.VDT_BGBLACK.'Changer de ligne'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Suite '.VDT_FDNORM.VDT_STOPUNDERLINE;			
				$vdt.=VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Retour '.VDT_FDNORM.VDT_STOPUNDERLINE.' ';
				$vdt.=VDT_CRLF.VDT_TXTWHITE.'Envoi message'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE;
				$vdt.=VDT_TXTWHITE.' Sortir'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Sommaire '.VDT_FDNORM.VDT_STOPUNDERLINE;

				$step = 'ecriture-init-saisie';
				break;
				
			default:
				exit;
				
		}
	}
	
	// Url à appeller lors de la prochaine saisie utilisateur (ou sans attendre si directCall=true)
	$context['step']=$step;
	$nextPage=$prot."://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'];
	// On envoi tout cela à la passerelle
	MiniPavi\MiniPaviCli::send($vdt,$nextPage,serialize($context),true,$cmd,$directCall);
} catch (Exception $e) {
	throw new Exception('Erreur MiniPavi '.$e->getMessage());
}
exit;
?>
