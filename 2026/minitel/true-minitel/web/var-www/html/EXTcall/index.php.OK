<?php
/**
 * @file index.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Février 2024
 *
 * EXTcall: Script d'appel d'un serveur externe en RTC ou telnet
 * 
 * Licence GNU GPL v2 ou superieure
 */

require "../../include/MiniPaviCli.php";	// A modifier: placer  à un emplacement inaccessible depuis l'exterieur

define("EXTCALL_CFGFILE","../../include/extcall.conf");	// Fichier par défaut de configuration, à modifier: placer  à un emplacement inaccessible depuis l'exterieur
define("EXTCALL_VER","1.0");

//error_reporting(E_ERROR);
error_reporting(E_ALL);
ini_set('display_errors',0);

/*************************************************
// Retourne les informations contenues dans le fichier
// de configuration pour un identifiant donné
**************************************************/
function getInfos($id,&$key,&$tInfos) {
	$tInfos=array();
	$id = trim($id);
	
	libxml_use_internal_errors(true);
	
	$objXML = simplexml_load_file(EXTCALL_CFGFILE,null,LIBXML_NOCDATA|LIBXML_NOBLANKS);
	if ($objXML === false) {
		$tInfos = libxml_get_errors();
		return false;
	}
	
	foreach ($objXML as $elementNameExt=>$objExt) {
		if ($elementNameExt == 'extcall') {
			foreach ($objExt as $elementNameSer=>$objSer) {
				if ($elementNameSer == 'serveur' && @$objSer['id'] == $id) {
					$tInfos['nom'] = (string)@$objSer->nom;
					$key = (string)@$objExt['key'];
					if (@$objSer['type'] == 'tel') {
						$tInfos['type'] = 'tel';
						$tInfos['numero'] = (string)@$objSer->numero;
						$tInfos['rx'] = (int)@$objSer->rx;
						$tInfos['tx'] = (int)@$objSer->tx;
						return true;
					} else if (@$objSer['type'] == 'int') {
						$tInfos['type'] = 'int';
						$tInfos['host'] = (string)@$objSer->host;
						$tInfos['echo'] = (string)@$objSer->echo;
						$tInfos['case'] = (string)@$objSer->case;
						$tInfos['startseq'] = (string)@$objSer->startseq;
						return true;
					} else if (@$objSer['type'] == 'ws') {
						$tInfos['type'] = 'ws';
						$tInfos['host'] = (string)@$objSer->host;
						$tInfos['path'] = (string)@$objSer->path;
						$tInfos['echo'] = (string)@$objSer->echo;
						$tInfos['case'] = (string)@$objSer->case;
						$tInfos['proto'] = (string)@$objSer->proto;
						return true;
					}
				}
			}
		}
	}
	return false;
}


$vdt='';			// Contenu à envoyer au Minitel de l'utilisateur
$cmd=null;			// Commande à executer au niveau de MiniPAVI
$directCall = false;

try {
	MiniPavi\MiniPaviCli::start();
	
	if (MiniPavi\MiniPaviCli::$fctn == 'CNX'  || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX' ) {
		// Initialisation

		$step = 0;
		$context = array();
		MiniPavi\MiniPaviCli::$content=array();
		trigger_error("[EXTcall] CNX");

		if (@MiniPavi\MiniPaviCli::$urlParams->cid != null)
			$context['cid'] = trim(@MiniPavi\MiniPaviCli::$urlParams->cid);
		if (@MiniPavi\MiniPaviCli::$urlParams->uniqueid != null)
			$context['uniqueid'] = trim(@MiniPavi\MiniPaviCli::$urlParams->uniqueid);
		if ($context['cid']=='' && $context['uniqueid']=='') {
			trigger_error("[EXTcall] Pas de donnees d'appel ".print_r($tInfos,true));
			$step=100;
		}
		
		if ($context['cid']!='') {
			$r=getInfos($context['cid'],$key,$tInfos);
			
			if (!$r) {
				trigger_error("[EXTcall] cid inconnu ".print_r($tInfos,true));
				$step=100;
			} else {
				$context['key'] = $key;
				$context['tinfos'] = $tInfos;
				$step =0;	
			}
		} else {
			if ($context['uniqueid'] == @MiniPavi\MiniPaviCli::$uniqueId) {
				// On ne peut pas s'auto visualiser!
				trigger_error("[EXTcall] Auto visualisation impossible");
				$step=100;
			}
			$context['key'] = trim(@MiniPavi\MiniPaviCli::$urlParams->key);
			$context['tinfos'] = array('type'=>'duplicate');
		}
		
		$vdt= MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
		$vdt.= MiniPavi\MiniPaviCli::writeLine0("EXTcall MiniPAVI ".EXTCALL_VER);
	} else {
		$context = unserialize(MiniPavi\MiniPaviCli::$context);		// Récupération du contexte		
		$step=(int)@MiniPavi\MiniPaviCli::$urlParams->step;
	}

	if (MiniPavi\MiniPaviCli::$fctn == 'FIN' || MiniPavi\MiniPaviCli::$fctn == 'FCTN?') {
			// Deconnexion
			trigger_error("[EXTcall] DECO");
			exit;
	}
	
	while(true) {
		$tInfos = @$context['tinfos'];
		$key = @$context['key'];
		
		switch ($step) {
			case 0:
				// Accueil
				if ($tInfos['type']=='duplicate') {
					$vdt.= MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
					$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(' Visualisation de '.$context['uniqueid']).VDT_CLRLN.VDT_CRLF;

					for($i=0;$i<6;$i++) {
						$vdt.= MiniPavi\MiniPaviCli::setPos(6,4+$i);
						$vdt.= VDT_BGBLUE.MiniPavi\MiniPaviCli::repeatChar(' ',29);
					}

					$vdt.= MiniPavi\MiniPaviCli::writeCentered(5,"La visualisation d'une",VDT_BGBLUE);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(6,"connexion tierce est",VDT_BGBLUE);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(7,"à sens unique.",VDT_BGBLUE);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(8,"Pas d'interaction possible.",VDT_BGBLUE.VDT_TXTRED);
					
					$vdt.= MiniPavi\MiniPaviCli::setPos(13,11);
					$vdt.= VDT_TXTYELLOW."***".VDT_STARTUNDERLINE.' '.VDT_FDINV.VDT_TXTRED." Sommaire ".VDT_FDNORM;
					$vdt.= MiniPavi\MiniPaviCli::setPos(5,12);
					$vdt.= VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2("pour se déconnecter du service");
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(13,"pendant la visualisation",VDT_TXTYELLOW);

					$vdt.= MiniPavi\MiniPaviCli::setPos(8,17);
					$vdt.= VDT_TXTGREEN.VDT_FDINV." Suite ".VDT_FDNORM.' pour vous connecter';
					$vdt.= MiniPavi\MiniPaviCli::setPos(6,20);
					$vdt.= VDT_TXTGREEN.VDT_FDINV." Connexion/fin ".VDT_FDNORM.' pour abandonner';
					$vdt.= MiniPavi\MiniPaviCli::setPos(1,21);
					$vdt.= MiniPavi\MiniPaviCli::setPos(1,0);
					$vdt.= VDT_TXTBLACK;
					$step = 5;
					break 2;
				} else if ($tInfos['type']=='tel') {
					$vdt.= MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
					$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(' Connexion à '.$tInfos['nom']).VDT_CLRLN.VDT_CRLF;
					$vdt.= VDT_BGBLUE.VDT_TXTWHITE.' Appel du '.$tInfos['numero'].VDT_CLRLN.VDT_CRLF;

					for($i=0;$i<6;$i++) {
						$vdt.= MiniPavi\MiniPaviCli::setPos(6,4+$i);
						$vdt.= VDT_BGBLUE.MiniPavi\MiniPaviCli::repeatChar(' ',29);
					}
					
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(5,"Des caractères aléatoires",VDT_BGBLUE);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(6,"peuvent apparaître durant",VDT_BGBLUE);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(7,"la phase de connexion.",VDT_BGBLUE);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(8,"Durée maximum: 10 minutes",VDT_BGBLUE.VDT_TXTRED);
					$vdt.= MiniPavi\MiniPaviCli::setPos(13,11);
					$vdt.= VDT_TXTYELLOW."***".VDT_STARTUNDERLINE.' '.VDT_FDINV.VDT_TXTRED." Sommaire ".VDT_FDNORM;
					$vdt.= MiniPavi\MiniPaviCli::setPos(11,12);
					$vdt.= VDT_TXTYELLOW."ou ".VDT_FDINV.VDT_TXTRED." Connexion/fin ".VDT_FDNORM;
					$vdt.= MiniPavi\MiniPaviCli::setPos(5,13);
					$vdt.= VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2("pour se déconnecter du service");
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(14,"pendant la consultation",VDT_TXTYELLOW);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(15,"et revenir sur MiniPavi",VDT_TXTYELLOW);
					$vdt.= MiniPavi\MiniPaviCli::setPos(8,17);
					$vdt.= VDT_TXTGREEN.VDT_FDINV." Suite ".VDT_FDNORM.' pour vous connecter';
					$vdt.= MiniPavi\MiniPaviCli::setPos(2,18);
					$vdt.= VDT_TXTGREEN.'La connexion demande environ 10-15 sec';
					$vdt.= MiniPavi\MiniPaviCli::setPos(6,20);
					$vdt.= VDT_TXTGREEN.VDT_FDINV." Connexion/fin ".VDT_FDNORM.' pour abandonner';
					$vdt.= MiniPavi\MiniPaviCli::setPos(1,21);
					$vdt.= MiniPavi\MiniPaviCli::setPos(1,0);
					$vdt.= VDT_TXTBLACK;
					$step = 5;
					break 2;
				} else {
					$vdt.= MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
					$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(' Connexion à '.$tInfos['nom']).VDT_CLRLN.VDT_CRLF;

					for($i=0;$i<6;$i++) {
						$vdt.= MiniPavi\MiniPaviCli::setPos(7,4+$i);
						$vdt.= VDT_BGBLUE.MiniPavi\MiniPaviCli::repeatChar(' ',28);
					}

					$vdt.= MiniPavi\MiniPaviCli::writeCentered(5," Vous allez être connecté",VDT_BGBLUE);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(6," à un serveur externe",VDT_BGBLUE);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(7," à l'environnement MiniPavi",VDT_BGBLUE);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(8," Durée maximum: 30 minutes",VDT_BGBLUE.VDT_TXTRED);
					$vdt.= MiniPavi\MiniPaviCli::setPos(13,12);
					$vdt.= VDT_TXTYELLOW."***".VDT_STARTUNDERLINE.' '.VDT_FDINV.VDT_TXTRED." Sommaire ".VDT_FDNORM;
					$vdt.= MiniPavi\MiniPaviCli::setPos(11,13);
					$vdt.= VDT_TXTYELLOW."ou ".VDT_FDINV.VDT_TXTRED." Connexion/fin ".VDT_FDNORM;
					$vdt.= MiniPavi\MiniPaviCli::setPos(5,15);
					$vdt.= VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2("pour se déconnecter du service");
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(16,"pendant la consultation",VDT_TXTYELLOW);
					$vdt.= MiniPavi\MiniPaviCli::writeCentered(17,"et revenir sur MiniPavi",VDT_TXTYELLOW);
					$vdt.= MiniPavi\MiniPaviCli::setPos(8,20);
					$vdt.= VDT_TXTGREEN.VDT_FDINV." Suite ".VDT_FDNORM.' pour vous connecter';
					$vdt.= MiniPavi\MiniPaviCli::setPos(6,22);
					$vdt.= VDT_TXTGREEN.VDT_FDINV." Connexion/fin ".VDT_FDNORM.' pour abandonner';
					$vdt.= MiniPavi\MiniPaviCli::setPos(1,0);
					$vdt.= VDT_TXTBLACK;
					$step = 5;
					break 2;
				}
			case 5:
				if (MiniPavi\MiniPaviCli::$fctn != 'SUITE') {
					$vdt=MiniPavi\MiniPaviCli::writeLine0("Tapez Suite ou Connexion/fin !");
					break 2;
				
				}
				if ($tInfos['type']=='duplicate') {
					$vdt=MiniPavi\MiniPaviCli::writeLine0("Visualisation en cours");
					$vdt.= MiniPavi\MiniPaviCli::setPos(1,1);
				} else
					$vdt=MiniPavi\MiniPaviCli::writeLine0("Veuillez patienter...",true);
				$step = 7;
				$directCall = true;
				break 2;
			case 7:
				if ($tInfos['type']=='duplicate') {
					$cmd=MiniPavi\MiniPaviCli::createDuplicateStream($context['uniqueid'],$context['key']);
				} else if ($tInfos['type']=='tel') {
					$cmd=MiniPavi\MiniPaviCli::createConnectToExtCmd($tInfos['numero'],$tInfos['rx'],$tInfos['tx'],$key);
				} else if ($tInfos['type']=='int' || $tInfos['type']=='ws') {
					if ($tInfos['type']=='int')
						$cmd=MiniPavi\MiniPaviCli::createConnectToTlnCmd($tInfos['host'],$tInfos['echo'],$tInfos['case'],$tInfos['startseq'],$key);
					else $cmd=MiniPavi\MiniPaviCli::createConnectToWsCmd($tInfos['host'],$tInfos['path'],$tInfos['echo'],$tInfos['case'],$tInfos['proto'],$key);
				} 
				$step = 10;
				break 2;
			case 10:
				// Retour après appel
				$vdt.= MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				switch(MiniPavi\MiniPaviCli::$fctn) {
				case 'DIRECTCALLFAILED':
					if ($tInfos['type']=='duplicate') {				
						$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(" Visualisation impossible").VDT_CLRLN;
					} else if ($tInfos['type']=='tel') {				
						$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(" Appel en échec (non réponse, occuppé)").VDT_CLRLN;
					} else {
						$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(" Serveur indisponible").VDT_CLRLN;					
					}
					break;
				case 'DIRECTCALLENDED':
					if ($tInfos['type']=='duplicate') {				
						$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(" Visualisation terminée").VDT_CLRLN;
					} else if ($tInfos['type']=='tel') {				
						$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(" Appel terminé").VDT_CLRLN;
					} else {
						$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(" Connexion avec ce serveur terminée").VDT_CLRLN;
					}
				}
			
				for($i=0;$i<5;$i++) {
					$vdt.= MiniPavi\MiniPaviCli::setPos(6,4+$i);
					$vdt.= VDT_BGBLUE.MiniPavi\MiniPaviCli::repeatChar(' ',27);
				}
				$vdt.= MiniPavi\MiniPaviCli::setPos(10,5);
				$vdt.= VDT_BGBLUE.VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2(" Connexion terminée");
				
				$cmd=MiniPavi\MiniPaviCli::createLibCnxCmd();
				
				break 2;
			case 100:
				$vdt.= MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(' Appel incorrect au service').VDT_CLRLN.VDT_CRLF;
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

	$nextPage=$prot."://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'].'?step='.$step;
	MiniPavi\MiniPaviCli::send($vdt,$nextPage,serialize($context),true,$cmd,$directCall);
} catch (Exception $e) {
	throw new Exception('Erreur MiniPavi '.$e->getMessage());
}
exit;
?>
