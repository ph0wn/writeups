<?php
/**
 * ACCUEIL MINIPAVI
 * @file index.php
 * @author Jean-arthur SILVE <ludojoey@astroz.com>
 * @version 1.0 Novembre 2023
 *
 * Licence GNU GPL v2 ou superieure
 */

require "../../include/MiniPaviCli.php";					// A modifier: placer  à un emplacement inaccessible depuis l'exterieur
require "../../include/miniPaviAccFunctions.php";			// A modifier: placer  à un emplacement inaccessible depuis l'exterieur

define('DEFAULT_CFGFILE','../../include/paviacc.conf');	// Fichier par défaut de configuration, à modifier: placer  à un emplacement inaccessible depuis l'exterieur

error_reporting(E_ERROR);
ini_set('display_errors',0);


// Lecture du fichier de configuration
$r = getConfig(DEFAULT_CFGFILE,$tConfig);
if (!$r) {
	trigger_error("[MiniPaviAcc] erreur config ".print_r($tConfig,true));
	exit;
}

try {
	MiniPavi\MiniPaviCli::start();

	if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
		// Initialisation du contexte utilisateur
		$context = array();
		$context['step']='accueil';
		MiniPavi\MiniPaviCli::$content=array();
		trigger_error("[MiniPaviAcc] CNX");
	} else {
		$context = unserialize(MiniPavi\MiniPaviCli::$context);		// Récupération du contexte utilisateur
	}
	
	if (MiniPavi\MiniPaviCli::$fctn == 'FIN') {
			// Deconnexion
			trigger_error("[MiniPaviAcc] DECO");
			exit;
	}

	
	$vdt='';		// Contenu à envoyer au Minitel de l'utilisateur
	$cmd=null;		// Commande à executer au niveau de MiniPAVI
	$goUrl = false;	
	$directCall=false;
	
	while(true) {
		switch ($context['step']) {
			case 'accueil':
				// Accueil: affichage partie fixe
				$vdt =MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF.PRO_ROULEAU_OFF.VDT_CUROFF.VDT_RESET_DRCS;
				
				if (MiniPavi\MiniPaviCli::$typeSocket == 'other') {
					$vdt.=MiniPavi\MiniPaviCli::writeLine0("Connexion de ".MiniPavi\MiniPaviCli::$remoteAddr);
				}
				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(2,"Passerelle MINIPAVI",VDT_SZDBLH.VDT_TXTYELLOW);				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(3,"Service d'accueil",VDT_TXTGREEN);				

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,6);
				$vdt.=MiniPavi\MiniPaviCli::repeatChar('_',40);
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,7);
				$vdt.=VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2("   Créez vos propres services Minitel!").VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,8);
				$vdt.=VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2("    Plus d'infos sur: www.minipavi.fr").VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,9);
				$vdt.=MiniPavi\MiniPaviCli::repeatChar(chr(0x7E),40);
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,12);
				$vdt.=VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2("Code ou URL du service:");
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,16);
				$vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::repeatChar(chr(0x60),40);
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,18);
				$vdt.=VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2("Annuaire des services       ").VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.MiniPavi\MiniPaviCli::toG2(' Guide ').VDT_TXTWHITE.VDT_FDNORM.VDT_STOPUNDERLINE.' ';
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,19);
				$vdt.=VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2("Valider code du service     ");
				$vdt.=VDT_TXTGREEN.VDT_STARTUNDERLINE.' '.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_TXTWHITE.VDT_STOPUNDERLINE.' ';
				
				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(21,"Pour revenir à cet écran depuis",VDT_TXTYELLOW);
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(22,"n'importe quel service, tapez",VDT_TXTYELLOW);	
				if (MiniPavi\MiniPaviCli::$versionMinitel != '???' && MiniPavi\MiniPaviCli::$typeSocket == 'websocket') {	
					// On vient peut être d'un Minimit ou ESP32 en général
					$vdt.=MiniPavi\MiniPaviCli::setPos(9,23);
					$vdt.=VDT_TXTWHITE.'[Maj] +'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.MiniPavi\MiniPaviCli::toG2(' Connexion/fin ').VDT_TXTWHITE.VDT_FDNORM.VDT_STOPUNDERLINE.' ';
				
				} else {
					$vdt.=MiniPavi\MiniPaviCli::setPos(12,24);
					$vdt.=VDT_TXTWHITE.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.MiniPavi\MiniPaviCli::toG2(' Connexion/fin ').VDT_TXTWHITE.VDT_FDNORM.VDT_STOPUNDERLINE.' ';
				}
				
				$context['step'] = 'accueil/initsaisie';
				break;
			case 'accueil/initsaisie':
				$cmd=MiniPavi\MiniPaviCli::createInputMsgCmd(1,13,40,2,MSK_ENVOI|MSK_REPETITION|MSK_GUIDE,true,'.',MiniPavi\MiniPaviCli::$content);
				$context['url']='';
				$context['step'] = 'accueil/traitesaisie';
				$directCall=false;
				break 2;
			case 'accueil/traitesaisie':
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$context['step']='accueil';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'GUIDE') {
					$context['step']='annuaire/init';
					break;
				}
				
				foreach(@MiniPavi\MiniPaviCli::$content as $val) 
					@$context['url'].=$val;
				if (strlen($context['url'])<2) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0("Saisissez un code d'accès");
					$context['url']='';
					$context['step'] = 'accueil/initsaisie';
					break;
				}
				$url=filter_var($context['url'], FILTER_VALIDATE_URL);
				if (!$url) {
					// Ce n'est pas une url... code service ?
					$url = getUrlFromCode($context['url'],$tConfig);
					
					if ($url === false) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0("Service inexistant");
						$context['url']='';
						$context['step'] = 'accueil/initsaisie';
						break;
					}
				}

				$vdt=MiniPavi\MiniPaviCli::writeLine0("Cnx en cours. Cnx/Fin pour revenir.");
				$directCall='yes-cnx';
				$goUrl = true;
				break 2;
				
			case 'annuaire/init':	// Annuaire des services
				$context['numparpage'] = 4;
				$context['ligneparcode'] = 4;
				$context['page'] = 0;

			case 'annuaire': // 101
			
				$vdt =MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF.VDT_CUROFF;
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,1);
				$vdt.=MiniPavi\MiniPaviCli::repeatChar('_',40);

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,2);
				$vdt.=VDT_BGBLUE.VDT_TXTWHITE.' Annuaire des Services '.VDT_CLRLN;

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,3);
				$vdt.=MiniPavi\MiniPaviCli::repeatChar(chr(0x7E),40);

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,23);
				$vdt.=VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(" Code ....................... +").VDT_STARTUNDERLINE.' '.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE.' ';
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24);
				$vdt.=VDT_BGBLUE.VDT_TXTWHITE.' Pages'.VDT_STARTUNDERLINE.' '.VDT_FDINV.' Suite '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_STARTUNDERLINE.' '.VDT_FDINV.' Retour '.VDT_FDNORM.VDT_STOPUNDERLINE.'  ou '.VDT_STARTUNDERLINE.' '.VDT_FDINV.' Sommaire '.VDT_FDNORM.VDT_STOPUNDERLINE.' ';
				
			case 'annuaire/afficheliste': //102
				if (is_array($tConfig) && count($tConfig)>0) {
					$numCodes = count($tConfig);
					$page = (int)$context['page'];
					$start = $page * $context['numparpage'];
					if ($start> $numCodes || $start<0) {
						// Ne devrait pas arriver
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Page hors bornes');
						$context['step'] = 'annuaire/initsaisie';
						break;
					}
					$vdt.=MiniPavi\MiniPaviCli::writeLine0('');
					$stop = $start+$context['numparpage'];
					if ($stop >= $numCodes)
						$stop = $numCodes;
					
					$vdt.= VDT_CUROFF;

					for ($idx=$start,$l=5;$idx<$stop;$idx++,$l+=$context['ligneparcode']) {
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(strtoupper($tConfig[$idx]['code']));
						$vdt.=VDT_CLRLN;
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+1).VDT_CLRLN;						
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+2).VDT_CLRLN;						
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+1).VDT_TXTCYAN.MiniPavi\MiniPaviCli::toG2($tConfig[$idx]['infos']);
						if (($idx+1)<$stop)
							$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+3).VDT_TXTBLUE.MiniPavi\MiniPaviCli::repeatChar('`',40);
					}
					for (;$l<22;$l++) {
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_CLRLN;
					}
					$vdt.=MiniPavi\MiniPaviCli::setPos(18,21);
					$vdt.=VDT_TXTBLUE.$numCodes.' services '.VDT_TXTYELLOW.'Page '.($page+1).'/'.ceil($numCodes/$context['numparpage']);
					
					
				}
			case 'annuaire/initsaisie': //108:
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(7,23,23,MSK_SUITE|MSK_RETOUR|MSK_ENVOI|MSK_SOMMAIRE|MSK_REPETITION,true,'.','');
				$context['step'] = 'annuaire/traitesaisie';
				break 2;
			
			case 'annuaire/traitesaisie': //110:
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$context['step'] = 'annuaire';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$context['step'] = 'accueil';
					break;
				}
				
				if ( MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					$page = (int)$context['page'];
					if (($context['numparpage']*($page+1)) < count($tConfig)) {
						$context['page']++;
						$context['step'] = 'annuaire/afficheliste';
						break;
					} 
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Dernière page');
					$context['step'] = 'annuaire/initsaisie';
					break;
				}
				if ( MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					$page = (int)$context['page'];
					if ($page>0) {
						$context['page']--;
						$context['step'] = 'annuaire/afficheliste';
						break;
					} 
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Première page');
					$context['step'] = 'annuaire/initsaisie';
					break;
				}
				
				if (MiniPavi\MiniPaviCli::$fctn == 'ENVOI') {
					$code = trim(MiniPavi\MiniPaviCli::$content[0]);
					$url = getUrlFromCode($code,$tConfig);
					if ($url === false) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0("Service inexistant");
						$context['step'] = 'annuaire/initsaisie';
						break;
					}
					$vdt=MiniPavi\MiniPaviCli::writeLine0("Cnx en cours. Cnx/Fin pour revenir.");
					$directCall='yes-cnx';
					$goUrl = true;
					break 2;
				}
				break 2;
		}
	}
	
	// Url à appeller lors de la prochaine saisie utilisateur (ou sans attendre si directCall=true)
	
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

	if (!$goUrl)
		$nextPage=$prot."://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'];
	else
		$nextPage=$url;
	
	MiniPavi\MiniPaviCli::send($vdt,$nextPage,serialize($context),true,$cmd,$directCall);
	
} catch (Exception $e) {
	throw new Exception('Erreur MiniPavi '.$e->getMessage());
}
exit;
?>
