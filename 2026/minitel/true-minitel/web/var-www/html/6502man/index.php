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

//require "MiniPaviCli.php";// A MODIFIER
require "../../include/MiniPaviCli.php";// A MODIFIER
//require "miniChatFunctions.php";
//require "animGPT.php";

//const MINICHAT_MAXCONN = 100;	// Nombre de connectés maximum
//const MINICHAT_NUMPARPAGE = 16;	// Nombre de connectés apparaissant sur chaque page
//const MINICHAT_TIMEOUT = 900;	// Temps maximum sans action avant d'être supprimé du chat

//const MINICHAT_CHATGPT = true;	// Activation de chatgpt


//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR|E_WARNING);
ini_set('display_errors',1);		// Mettre à 1 si l'on veut que les erreurs s'affichent


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

	
	if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') 
	{
		// Nouvelle connexion
			$step = 'accueil';
				$context = array();
		MiniPavi\MiniPaviCli::$content=array();
				$context['SLIDE']=0;
				$context['GAME']=0;
				$context['WIN']=8;
		trigger_error("[6502man] CNX");
	} 
	else 
	{
		// Connexion en cours
		$context = @unserialize(MiniPavi\MiniPaviCli::$context);	// Récupération du contexte utilisateur
		$step = $context['step'];	// Etape du script à executer, indiqué dans le paramètre 'url' de la requête http
	}


	$directCall=false;
	$vdt='';		// Contenu à envoyer au Minitel de l'utilisateur	
	$cmd=null;		// Commande à executer au niveau de MiniPAVI
	$prot='http';
	
	
	
	while(true) 
	{
		switch ($step) 
		{
			case 'accueil':
				// Accueil: affichage partie fixe
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('PH0WN.VDT');
				
				//$vdt.=MiniPavi\MiniPaviCli::setPos(1,24);
				//$vdt.=VDT_G0.VDT_TXTWHITE.VDT_BGBLACK.MiniPavi\MiniPaviCli::toG2(" Tapez ").VDT_FDINV." Suite ".VDT_FDNORM.VDT_CLRLN;
				
				$step = 'accueilB';
				$directCall=false;
				break 2;	// On arrête le script et on attend une saisie utilisateur ($directCall = false)

			case 'accueilB':
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') 
				{
					$step = 'accueil';
					break;
				}
				if ( MiniPavi\MiniPaviCli::$fctn == 'SUITE') 
				{
					$step = 'menu';
					break;
				}
				else
				{
					$step = 'accueilB';
					$directCall=false;
					break 2;					
				}
//---------------------------------------------------------------------------------------------------------------
			case 'menu':
				/*
				$vdt=VDT_CLR.VDT_CUROFF.MiniPavi\MiniPaviCli::setPos(1,2);
				$vdt.=VDT_SZDBLH.VDT_TXTWHITE.VDT_BGBLUE.' toto'.VDT_TXTBLACK.chr(hexdec('7D')).VDT_TXTWHITE.VDT_BGRED.' in the PLANETE'.VDT_CLRLN;
				
				for ($i=3;$i<24;$i++) 
				{
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,$i);				
					$vdt.=VDT_BGYELLOW.MiniPavi\MiniPaviCli::repeatChar(' ',40);
				}
				*/
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('PH0WN2.VDT');	


				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(28,24,1,MSK_ENVOI|MSK_SOMMAIRE,true,' ','');
				//$step = 'menu';
				
				$step = 'menu_choix';
				$directCall=false;
				break 2;
				
			case 'menu_choix':	
				$choix = (int)(@MiniPavi\MiniPaviCli::$content[0]);
				if ($choix<1 || $choix>9) 
				{
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Choix incorrect!');
					$step = 'menu';
					break;
				}


				if ($choix==9) {$step = 'Admin0';  break;}
				if ($choix==1) {$step = 'Phown1';  break;}
				if ($choix==2) {$step = 'Phown2';  break;}
				if ($choix==3) {$step = 'Phown3';  break;}
				if ($choix==4) {$step = 'SLIDE';  break;}
				if ($choix>4 || $choix<9 ) {$step = 'menu';  break;}				
				
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') 
				{
					$step = 'accueil';
					break ;
				}
				else				
				{
					$step = 'menu_choix';
					break;
				}
				

		
//---------------------------------------------------------------------------------------------------------------
			case 'SLIDE':
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE' )
				{
					$step = 'accueil';
					break ;
				}
				if (  MiniPavi\MiniPaviCli::$fctn == 'RETOUR' ) 
				{
					$step = 'menu';
					break ;
				}
				if ($context['SLIDE']==4) {
					$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,23);
					$vdt.=VDT_G0.VDT_TXTWHITE.VDT_BGBLACK.MiniPavi\MiniPaviCli::toG2("90001E781E79287A08740093F5F06489F663F08AA7F0A30809DAEE02001BCAFAE3E9E5FEEFE6").VDT_FDNORM.VDT_CLRLN;					
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,1);
				}
				$vdt.= file_get_contents('img'.$context['SLIDE'].'.vdt');
				$context['SLIDE']++;
				$context['SLIDE']%=5;
				$directCall=false;
				break 2;
//---------------------------------------------------------------------------------------------------------------


//---------------------------------------------------------------------------------------------------------------			
			case 'Phown1':			
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('PH0WN3.VDT');				

				$step = 'Phown1_choix';
				$directCall=false;
				break 2;
				
			case 'Phown1_choix':				
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') 
				{
					$step = 'accueil';
					break ;
				}				
				if ( MiniPavi\MiniPaviCli::$fctn == 'RETOUR') 
				{
					$step = 'menu';
					break ;
				}
				if ( ( MiniPavi\MiniPaviCli::$fctn != 'SOMMAIRE')  ||  ( MiniPavi\MiniPaviCli::$fctn != 'RETOUR')  ) 
				{
					$step = 'Phown1';
					break ;
				}			
//---------------------------------------------------------------------------------------------------------------							
			case 'Phown2':			
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('PH0WN4.VDT');				

				$step = 'Phown2_choix';
				$directCall=false;
				break 2;
				
			case 'Phown2_choix':				
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') 
				{
					$step = 'accueil';
					break ;
				}				
				if ( MiniPavi\MiniPaviCli::$fctn == 'RETOUR') 
				{
					$step = 'menu';
					break ;
				}
				if ( ( MiniPavi\MiniPaviCli::$fctn != 'SOMMAIRE')  ||  ( MiniPavi\MiniPaviCli::$fctn != 'RETOUR')  ) 
				{
					$step = 'Phown2';
					break ;
				}					
//---------------------------------------------------------------------------------------------------------------							
			case 'Phown3':			
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('PH0WN5.VDT');				

				$step = 'Phown3_choix';
				$directCall=false;
				break 2;
				
			case 'Phown3_choix':				
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') 
				{
					$step = 'accueil';
					break ;
				}				
				if ( MiniPavi\MiniPaviCli::$fctn == 'RETOUR') 
				{
					$step = 'menu';
					break ;
				}
				if ( ( MiniPavi\MiniPaviCli::$fctn != 'SOMMAIRE')  ||  ( MiniPavi\MiniPaviCli::$fctn != 'RETOUR')  ) 
				{
					$step = 'Phown3';
					break ;
				}				
			
//---------------------------------------------------------------------------------------------------------------
			case 'Admin0':			
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('ADMIN0.VDT');				

				//$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(28,24,8,MSK_ENVOI|MSK_SOMMAIRE,true,' ','');
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(13,9,8,MSK_ENVOI|MSK_SUITE,true,'.','',@$context['pass']);
				
				$step = 'Admin0_choix';
				$directCall=false;
				break 2;
				
			case 'Admin0_choix':	
				//$pass = (@MiniPavi\MiniPaviCli::$content[0]);	
				$context['pass']=@MiniPavi\MiniPaviCli::$content[0];
				if ($context['pass']=='@picotel') 
				{
					$step= 'Admin1';
					break;
				}
				else
				{
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Wrong!');
					$step = 'menu';
					break;
				}
			
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') 
				{
					$step = 'accueil';
					break ;
				}				
				if ( MiniPavi\MiniPaviCli::$fctn == 'RETOUR') 
				{
					$step = 'menu';
					break ;
				}
				else				
				{
					$step = 'menu_choix';
				}				
//---------------------------------------------------------------------------------------------------------------							
			case 'Admin1':			
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('ADMIN1.VDT');				

				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(28,24,1,MSK_ENVOI|MSK_SOMMAIRE,true,' ','');
				
				$step = 'Admin1_choix';
				$directCall=false;
				break 2;
				
			case 'Admin1_choix':
				$choix = (int)(@MiniPavi\MiniPaviCli::$content[0]);
				if ($choix<1 || $choix>2) 
				{
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Choix incorrect!');
					$step = 'Admin1';
					break;
				}
				if ($choix==1) {$step = 'Admin2';  break;}
				if ($choix==2) {$step = 'Admin3';  break;}				
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') 
				{
					$step = 'accueil';
					break ;
				}				
				if ( MiniPavi\MiniPaviCli::$fctn == 'RETOUR') 
				{
					$step = 'menu';
					break ;
				}
				if ( ( MiniPavi\MiniPaviCli::$fctn != 'SOMMAIRE')  ||  ( MiniPavi\MiniPaviCli::$fctn != 'RETOUR')  ) 
				{
					$step = 'Admin1_choix';
					break ;
				}					
//---------------------------------------------------------------------------------------------------------------
			case 'Admin2':			
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('ADMIN2.VDT');				

				$step = 'Admin2_choix';
				$directCall=false;
				break 2;
				
			case 'Admin2_choix':				
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') 
				{
					$step = 'accueil';
					break ;
				}				
				if ( MiniPavi\MiniPaviCli::$fctn == 'RETOUR') 
				{
					$step = 'Admin1';
					break ;
				}
				else				
				if ( ( MiniPavi\MiniPaviCli::$fctn != 'SOMMAIRE')  ||  ( MiniPavi\MiniPaviCli::$fctn != 'RETOUR')  ) 
				{
					$step = 'Admin2_choix';
					break ;
				}				
//---------------------------------------------------------------------------------------------------------------	
			case 'Admin3':			
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('ADMIN3.VDT');				

				$step = 'Admin3_choix';
				$directCall=false;
				break 2;
				
			case 'Admin3_choix':				
				if ( MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') 
				{
					$step = 'accueil';
					break ;
				}				
				if ( MiniPavi\MiniPaviCli::$fctn == 'RETOUR') 
				{
					$step = 'Admin1';
					break ;
				}
				if ( ( MiniPavi\MiniPaviCli::$fctn != 'SOMMAIRE')  ||  ( MiniPavi\MiniPaviCli::$fctn != 'RETOUR')  ) 
				{
					$step = 'Admin3_choix';
					break ;
				}				
//---------------------------------------------------------------------------------------------------------------



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
