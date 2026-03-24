<?php
/**
 * @file index.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Novembre 2023
 *
 * Script service SNCF, prochains départs et arrivées
 * A des fins de démonstration
 * 
 */

require "../../include/MiniPaviCli.php";	// A modifier
require "MiniAPISncf.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR);
ini_set('display_errors',0);

try {
	MiniPavi\MiniPaviCli::start();

	if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
		// Initialisation
		$step = 0;
		$context = array();
		MiniPavi\MiniPaviCli::$content=array();
		trigger_error("[SNCF] CNX");
		
	} else {
		$step=(int)@MiniPavi\MiniPaviCli::$urlParams->step;		// Etape du script à executer, indiqué dans le paramètre 'url' de la requête http
		$context = unserialize(MiniPavi\MiniPaviCli::$context);		// Récupération du contexte utilisateur
		
	}

	
	if (MiniPavi\MiniPaviCli::$fctn == 'FIN' || MiniPavi\MiniPaviCli::$fctn == 'FCTN?') {
			// Deconnexion
			trigger_error("[SNCF] DECO");
			exit;
	}
	
	
	$vdt='';		// Contenu à envoyer au Minitel de l'utilisateur
	$cmd=null;		// Commande à executer au niveau de MiniPAVI
	$directCall = false;
	while(true) {
		switch ($step) {
			case 0:
				// Accueil
				$vdt =MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.=MiniPavi\MiniPaviCli::setPos(3,1);
				$vdt.=VDT_G1.VDT_TXTRED.MiniPavi\MiniPaviCli::repeatChar('p',38);
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,8);
				$vdt.=VDT_G1.VDT_TXTRED.MiniPavi\MiniPaviCli::repeatChar(',',37);
				$vdt.= file_get_contents('sncf2.vdt');
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,10);
				$vdt.=VDT_SZDBLH.VDT_BGRED.MiniPavi\MiniPaviCli::repeatChar(' ',40);
				$vdt.=MiniPavi\MiniPaviCli::setPos(13,10);
				$vdt.=VDT_SZDBLH.VDT_TXTWHITE."C'EST POSSIBLE !";
				$vdt.= file_get_contents('train.vdt');
				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(12,'Retrouvez ici les prochains passages',VDT_TXTYELLOW);
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(13,'des trains dans votre gare',VDT_TXTYELLOW);
				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(15,'Entrez le nom de votre gare');
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(16,'(3 lettres minimum)');
				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(18,'La recherche retourne 99 gares maximum',VDT_TXTMAGENTA);
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(3,20);
				$vdt.="Pour valider";
				$vdt.=MiniPavi\MiniPaviCli::setPos(5,23);
				$vdt.=VDT_TXTGREEN.VDT_FDINV.VDT_SZDBLH." Envoi ";
				$vdt.=MiniPavi\MiniPaviCli::writeLine0('Données fournies par SNCF NUMERIQUE');
			case 2:
				// Initialisation de la zone de saisie
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(6,17,27,MSK_ENVOI|MSK_REPETITION,true,'.','',@$context['gare']);
				$step=5;
				$directCall=false;
				break 2;	// On arrête le script et on attend une saisie utilisateur ($directCall = false)
				
			case 5:
				// Traitement de la saisie
				if ( MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$step = 0;
					break;
				}
				
				$gare = trim(@MiniPavi\MiniPaviCli::$content[0]);
				$context['gare'] = $gare;
				
				if (strlen($gare)<3) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Entrer mini. 3 lettres !');
					$step=2;
					break;
				}
				
				$vdt=MiniPavi\MiniPaviCli::writeLine0('Recherche en cours...');
				$step=7;
				$directCall=true;
				break 2;	// Envoi du message d'attente (et donc arrêt du script) et on passe directement à l'étape 7 par un appel direct au script (sans attente d'entrée utilisateur)
				
				
			case 7:
				// Recherche des gares disponibles
				$tGares = MiniAPISncf::GetGares($context['gare']);
				if (count($tGares)<1) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Aucune gare trouvée. Réessayez!');
					$step=2;
					break;
				}
				
				$context['tgares'] = $tGares;
				
			case 8:
				// Affichage de la liste des gares, zone fixe
				
				$context['page']=0;
				
				// On sépare l'initialisation et l'affiche pour pouvoir rafraichier l'affichage (touche REPETITION)
				// sans réinitialiser la page courante, ou lorsque l'on vient d'une page "gare"
			case 9:
				// Affichage de la liste des gares, zone fixe
				
				$vdt=MiniPavi\MiniPaviCli::clearScreen();
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,1).VDT_G1.VDT_TXTRED.MiniPavi\MiniPaviCli::repeatChar('p',40);
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,4).VDT_G1.VDT_TXTRED.MiniPavi\MiniPaviCli::repeatChar(',',40);				
				

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,2).VDT_BGBLUE.MiniPavi\MiniPaviCli::repeatChar(' ',40);				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,3).VDT_BGBLUE.MiniPavi\MiniPaviCli::repeatChar(' ',40);				

				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(2,'LISTE DES GARES TROUVEES');
				
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24).VDT_BGBLUE;
				$vdt.=" .. ".VDT_FDINV." Envoi ".VDT_FDNORM." ou ".VDT_FDINV." Suite ".VDT_FDNORM." ".VDT_FDINV." Retour ".VDT_FDNORM." ".VDT_FDINV." Somm. ".VDT_FDNORM.VDT_CLRLN;

				
				$n = count($context['tgares']);
				if ($n>1)
					$vdt.=MiniPavi\MiniPaviCli::writeCentered(3,'Il y a '.count($context['tgares']).' gares correpondant',VDT_TXTYELLOW);
				else 
					$vdt.=MiniPavi\MiniPaviCli::writeCentered(3,'Il y a une seule gare correpondant',VDT_TXTYELLOW);
				
				
				
			case 10:
				// Affichage de la partie variable
				
				$page = (int)$context['page'];
				$start = $page * 18;						// 18 gares par pages maximum
				if ($start> count($context['tgares']) || $start<0) {
					// Ne devrait pas arriver
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Page hors bornes');
					$step=2;
					break;
				}

				$stop = $start+18;
				if ($stop >= count($context['tgares']))
					$stop = count($context['tgares']);
				
				$vdt.= VDT_CUROFF;
				for ($idx=$start,$l=5;$idx<$stop;$idx++,$l++) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_TXTMAGENTA.VDT_FDINV.VDT_STARTUNDERLINE.' '.sprintf('%2d',$idx+1).' ';
					$vdt.=VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE.' '.MiniPavi\MiniPaviCli::toG2(mb_substr($context['tgares'][$idx]['name'],0,35)).VDT_CLRLN;
				}
				for (;$l<23;$l++) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_TXTMAGENTA.VDT_FDINV.VDT_STARTUNDERLINE.'    '.VDT_STOPUNDERLINE.VDT_FDNORM.' '.VDT_CLRLN;
				}

			case 12:
				// Initialisation de la zone de saisie
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(2,24,2,MSK_ENVOI|MSK_SUITE|MSK_RETOUR|MSK_SOMMAIRE|MSK_REPETITION,true,'.','');
				$step=14;
				$directCall=false;
				break 2;

			case 14:
				// Traitement de la saisie utilisateur
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$step = 0;
					break;
				}
				
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$step = 9;
					break;
				}
				
				// L'utilisateur veut changer de page
				$page = (int)$context['page'];
				if (MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					if ($page==0) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Première page atteinte');
						$step=12;
						break;
					}
					$context['page'] = $page-1;
					$step=10;
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					if ((18*($page+1)) >= count($context['tgares'])) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Dernière page atteinte');
						$step=12;
						break;
					}
					$context['page'] = $page+1;
					$step=10;
					break;
				}
				
				// C'est donc la touche ENVOI 
				
				$choix = (int)(@MiniPavi\MiniPaviCli::$content[0]);				
				
				if ($choix<1 || $choix > count($context['tgares'])) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Choix incorrect');
					$step=12;
					break;
				}
				
				
				$context['vue'] = 'departs';		// Initialisation de la vue (departs ou arrivees)
				$context['idxgare'] = $choix-1;

				$vdt=MiniPavi\MiniPaviCli::writeLine0('Recherche en cours...');
				$step=16;
				$directCall=true;
				break 2;	// Envoi du message d'attente (et donc arrêt du script) et on passe directement à l'étape 16 par un appel direct au script (sans attente d'entrée utilisateur)


			case 16:
				// Affichage de la liste des départs ou arrivées
				$vdt=MiniPavi\MiniPaviCli::clearScreen();
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,1).VDT_G1.VDT_TXTRED.MiniPavi\MiniPaviCli::repeatChar('p',40);
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,4).VDT_G1.VDT_TXTRED.MiniPavi\MiniPaviCli::repeatChar(',',40);			
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,2).VDT_BGBLUE.MiniPavi\MiniPaviCli::repeatChar(' ',40);								
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,3).VDT_BGBLUE.MiniPavi\MiniPaviCli::repeatChar(' ',40);				
				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(3,mb_substr($context['tgares'][$context['idxgare']]['name'],0,36));
				
				if ($context['vue'] == 'departs')
					$vdt.=MiniPavi\MiniPaviCli::writeCentered(2,"PROCHAINS DEPARTS",VDT_TXTYELLOW);
				else $vdt.=MiniPavi\MiniPaviCli::writeCentered(2,"PROCHAINES ARRIVEES",VDT_TXTYELLOW);
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24).VDT_BGBLUE;
				
				if ($context['vue'] == 'departs')
					$vdt.=MiniPavi\MiniPaviCli::toG2(" Arrivées ");
				else $vdt.=MiniPavi\MiniPaviCli::toG2(" Départs ");
				$vdt.=VDT_FDINV." Suite ".VDT_FDNORM." ou ".VDT_FDINV." Sommaire ".VDT_FDNORM.VDT_CLRLN;
			
				if ($context['vue'] == 'departs')
					$tRes = MiniAPISncf::getRTDepartures($context['tgares'][$context['idxgare']]['uic_code']);
				else $tRes = MiniAPISncf::getRTArrivals($context['tgares'][$context['idxgare']]['uic_code']);
				
				if (!is_array($tRes) || count($tRes)<1) {
					if ($context['vue'] == 'departs')
						$vdt.=MiniPavi\MiniPaviCli::writeCentered(13,'Aucun départ trouvé');
					else $vdt.=MiniPavi\MiniPaviCli::writeCentered(13,'Aucune arrivée trouvée');
				} else {
					$l = 5;
					foreach($tRes as $info) {
						if ($context['vue'] == 'departs') {
							//$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_BGBLUE.VDT_TXTYELLOW.' '.MiniPavi\MiniPaviCli::toG2($info['commercial_mode'].' '.$info['trip_short_name']).VDT_CLRLN;
							
							$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_BGBLUE.VDT_TXTYELLOW.' '.MiniPavi\MiniPaviCli::toG2($info['commercial_mode'].' '.$info['trip_short_name']);
							$lReste = 39 - (mb_strlen($info['commercial_mode'])+mb_strlen($info['trip_short_name'])+2);
							if ($lReste>0) {
								$route = substr($info['direction'],0,$lReste);
								$vdt.=' '.VDT_TXTCYAN.MiniPavi\MiniPaviCli::toG2($route);
							}
							$vdt.=VDT_CLRLN;

							
							$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+1).VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(mb_substr($info['direction2'],0,40)).VDT_CLRLN;
							

							
							if ($info['base_departure_date_time'] != $info['departure_date_time']) 
								$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+2).VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2('Prévu ').$info['base_departure_date_time'].VDT_TXTWHITE.VDT_BLINK.' RETARDE '.VDT_FIXED.$info['departure_date_time'].VDT_CLRLN;
							else
								$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+2).VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2('Prévu ').$info['base_departure_date_time'].VDT_CLRLN;
							$l+=3;
							if ($l>22)
								break;
						} else {
							//$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_BGBLUE.VDT_TXTYELLOW.' '.MiniPavi\MiniPaviCli::toG2($info['commercial_mode'].' '.$info['trip_short_name']).VDT_CLRLN;
							
							$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_BGBLUE.VDT_TXTYELLOW.' '.MiniPavi\MiniPaviCli::toG2($info['commercial_mode'].' '.$info['trip_short_name']);


							$lReste = 39 - (mb_strlen($info['commercial_mode'])+mb_strlen($info['trip_short_name'])+2);
							if ($lReste>0) {
								$route = substr($info['direction'],0,$lReste);
								$vdt.=' '.VDT_TXTCYAN.MiniPavi\MiniPaviCli::toG2($route);
							}
							$vdt.=VDT_CLRLN;
							
							
							$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+1).VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(mb_substr($info['direction2'],0,40)).VDT_CLRLN;
							if ($info['base_arrival_date_time'] != $info['arrival_date_time']) 
								$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+2).VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2('Prévu ').$info['base_arrival_date_time'].VDT_TXTWHITE.VDT_BLINK.' RETARDE '.VDT_FIXED.$info['arrival_date_time'].VDT_CLRLN;
							else
								$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+2).VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2('Prévu ').$info['base_arrival_date_time'].VDT_CLRLN;
							$l+=3;
							if ($l>22)
								break;
						}
					}
				}


				$step = 17;
				break 2;
			
			case 17:
				// Traitement du choix de l'utilisateur
				// Seule une touche de fonction est attendue: pas besoin de créer une zone de saisie, toutes les touches de fonctions sont acceptées

				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$step = 9;
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$step = 16;
					break;
				}
				
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					if ($context['vue'] == 'departs')
						$context['vue'] = 'arrivees';
					else $context['vue'] = 'departs';
					
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Recherche en cours...');
					$step=16;
					$directCall=true;
					break 2;	// Envoi du message d'attente (et donc arrêt du script) et on passe directement à l'étape 16 par un appel direct au script (sans attente d'entrée utilisateur)
					
				}
				// Touche de fonction inopérante, on ne fait rien
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
