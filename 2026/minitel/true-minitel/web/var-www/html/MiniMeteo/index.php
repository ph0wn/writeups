<?php
/**
 * @file index.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Mars 2024
 *
 * Script Meteo pour service compatible MiniPavi
 * 
 * 
 */

require "../../include/MiniPaviCli.php";	// A modifier
require "MiniMeteo.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR);
ini_set('display_errors',0);

try {
	MiniPavi\MiniPaviCli::start();

	if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
		// Initialisation
		$step = 'accueil';
		$context = array();
		MiniPavi\MiniPaviCli::$content=array();
		trigger_error("[METEO] CNX");
	} else {
		$context = unserialize(MiniPavi\MiniPaviCli::$context);		// Récupération du contexte utilisateur
		$step = $context['step'];									// Etape du script à executer, indiqué dans le paramètre 'url' de la requête http
	}
	
	if (MiniPavi\MiniPaviCli::$fctn == 'FIN') {
			// Deconnexion
			trigger_error("[METEO] DECO");
			exit;
	}
	
	$vdt='';		// Contenu à envoyer au Minitel de l'utilisateur
	$cmd=null;		// Commande à executer au niveau de MiniPAVI
	$directCall = false;
	while(true) {
		switch ($step) {
			case 'accueil':
				// Accueil
				$context['ville']='';
				$vdt =MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.=MiniPavi\MiniPaviCli::webMediaSound("http://www.minipavi.fr/minimeteo/jinglemeteo.mp3");
				$vdt.= file_get_contents('meteoacc.vdt');
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(23,'Rechercher une ville (3 lettres min.)');
				$vdt.=MiniPavi\MiniPaviCli::setPos(31,24);
				$vdt.=VDT_TXTGREEN.VDT_STARTUNDERLINE.' '.VDT_FDINV.MiniPavi\MiniPaviCli::toG2(' Envoi ').VDT_TXTWHITE.VDT_FDNORM.VDT_STOPUNDERLINE.' ';
				$vdt.=MiniPavi\MiniPaviCli::writeLine0('Données fournies par OPEN METEO');				


			case 'accueil-init-saisie':
				// Initialisation de la zone de saisie
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(2,24,29,MSK_ENVOI|MSK_REPETITION,true,'.','',@$context['ville']);
				$step='accueil-traite-saisie';
				$directCall=false;
				break 2;	// On arrête le script et on attend une saisie utilisateur ($directCall = false)
				

			case 'accueil-traite-saisie':			
				// Traitement de la saisie
				if ( MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$step='accueil';
					break;
				}
				
				$ville = trim(@MiniPavi\MiniPaviCli::$content[0]);
				$context['ville'] = $ville;
				
				if (strlen($ville)<3) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Entrer mini. 3 lettres !');
					$step='accueil-init-saisie';
					break;
				}
				
				$vdt=MiniPavi\MiniPaviCli::writeLine0('Recherche en cours...');
				$step='recherche-ville';
				$directCall=true;
				break 2;	// Envoi du message d'attente (et donc arrêt du script) et on passe directement à l'étape 7 par un appel direct au script (sans attente d'entrée utilisateur)
				
				case 'recherche-ville':
				// Recherche des villes disponibles
				$tVilles = GetCities($context['ville']);
				if ($tVilles === false || count($tVilles)<1) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Aucune ville trouvée. Réessayez!');
					$step='accueil-init-saisie';
					break;
				}
				$context['tvilles'] = $tVilles;
				
			case 'liste-ville-init':			
				// Affichage de la liste des villes, zone fixe
				
				$context['page']=0;
				
				// On sépare l'initialisation et l'affiche pour pouvoir rafraichier l'affichage (touche REPETITION)
				// sans réinitialiser la page courante, ou lorsque l'on vient d'une page "ville"

			case 'liste-ville-zone-fixe':						
				// Affichage de la liste des villes, zone fixe
				$vdt=MiniPavi\MiniPaviCli::clearScreen();
				$vdt.= file_get_contents('meteofondpage.vdt');
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,23);
				$vdt.='Entrez votre choix:';
				$vdt.=MiniPavi\MiniPaviCli::setPos(8,1);
				$vdt.=MiniPavi\MiniPaviCli::toG2('LISTE DES VILLES CORRESPONDANTES');
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24).VDT_BGBLUE;
				$vdt.=" .. ".VDT_FDINV." Envoi ".VDT_FDNORM." ou ".VDT_FDINV." Suite ".VDT_FDNORM." ".VDT_FDINV." Retour ".VDT_FDNORM." ".VDT_FDINV." Somm. ".VDT_FDNORM.VDT_CLRLN;
				
				$n = count($context['tvilles']);
				$vdt.=MiniPavi\MiniPaviCli::setPos(8,2).VDT_TXTYELLOW;
				if ($n>1)
					$vdt.=VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2('Minitel a trouvé '.count($context['tvilles']).' villes !');
				else 
					$vdt.=VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2("Minitel n'a trouvé qu'une ville !");
				

			case 'liste-ville-zone-variable':									
				// Affichage de la partie variable
				$page = (int)$context['page'];
				$start = $page * 6;						// 5 villes par pages maximum
				if ($start> count($context['tvilles']) || $start<0) {
					// Ne devrait pas arriver
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Page hors bornes');
					$step='accueil-init-saisie';
					break;
				}

				$stop = $start+6;
				if ($stop >= count($context['tvilles']))
					$stop = count($context['tvilles']);
				
				$vdt.= VDT_CUROFF;
				for ($idx=$start,$l=5;$idx<$stop;$idx++,$l+=3) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_TXTGREEN.VDT_FDINV.' '.sprintf('%2d',$idx+1).VDT_FDNORM;
					$vdt.=VDT_TXTWHITE.' '.MiniPavi\MiniPaviCli::toG2(mb_substr($context['tvilles'][$idx]['nomVille'],0,35)).VDT_CLRLN;
					
					$vdt.=MiniPavi\MiniPaviCli::setPos(6,$l+1).VDT_TXTCYAN;
					if (@$context['tvilles'][$idx]['pays']!='')
						$vdt.=MiniPavi\MiniPaviCli::toG2(mb_substr($context['tvilles'][$idx]['pays'],0,16)).' '.VDT_CLRLN;
					if (@$context['tvilles'][$idx]['region']!='')
						$vdt.='('.MiniPavi\MiniPaviCli::toG2(mb_substr($context['tvilles'][$idx]['region'],0,16)).')'.VDT_CLRLN;
					
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+2).VDT_TXTRED;
					$vdt.=MiniPavi\MiniPaviCli::repeatChar('`',40);
					
				}
				for (;$l<23;$l++) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l).VDT_CLRLN;
				}


			case 'liste-ville-init-saisie':												
				// Initialisation de la zone de saisie
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(2,24,2,MSK_ENVOI|MSK_SUITE|MSK_RETOUR|MSK_SOMMAIRE|MSK_REPETITION,true,'.','');
				$step='liste-ville-traite-saisie';
				$directCall=false;
				break 2;

			case 'liste-ville-traite-saisie':															
				// Traitement de la saisie utilisateur
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$step='accueil';
					break;
				}
				
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$step = 'liste-ville-zone-fixe';
					break;
				}
				
				// L'utilisateur veut changer de page
				$page = (int)$context['page'];
				if (MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					if ($page==0) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Première page atteinte');
						$step = 'liste-ville-init-saisie';
						break;
					}
					$context['page'] = $page-1;
					$step = 'liste-ville-zone-variable';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					if ((6*($page+1)) >= count($context['tvilles'])) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Dernière page atteinte');
						$step = 'liste-ville-init-saisie';
						break;
					}
					$context['page'] = $page+1;
					$step = 'liste-ville-zone-variable';
					break;
				}
				
				// C'est donc la touche ENVOI 
				
				$choix = (int)(@MiniPavi\MiniPaviCli::$content[0]);				
				
				if ($choix<1 || $choix > count($context['tvilles'])) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Choix incorrect');
					$step = 'liste-ville-init-saisie';
					break;
				}

				$context['idxville'] = $choix-1;

				$vdt=MiniPavi\MiniPaviCli::writeLine0('Minitel cherche les prévisions...');
				$step = 'recherche-previsions';
				$directCall=true;
				break 2;	// Envoi du message d'attente (et donc arrêt du script) et on passe directement à l'étape 16 par un appel direct au script (sans attente d'entrée utilisateur)

			case 'recherche-previsions':															
				// Récupération de prévisions
				$r = GetForecast($context['tvilles'][$context['idxville']]['latitude'],$context['tvilles'][$context['idxville']]['longitude'],$context['tvilles'][$context['idxville']]['timezone'],$tNow,$tFuture);

				if ($r === false) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Aucune prévision trouvée...');
					$step = 'liste-ville-init-saisie';
					break;
				}
				
				$context['tquality'] = false;
				$tQuality = GetAirQuality($context['tvilles'][$context['idxville']]['latitude'],$context['tvilles'][$context['idxville']]['longitude']);
				if (@$tQuality['european_aqi']!='')
					$context['tquality'] = $tQuality;
				$context['tnow'] = $tNow;
				$context['tfuture'] = $tFuture;
				
			case 'temps-actuel-affiche':																		
				// Affichage de l'accueil prévisions d'une ville
				$tNow = $context['tnow'];
				$tFuture = $context['tfuture'];

				$localeDate = substr($tFuture[0]['time'],8,2).'/'.substr($tFuture[0]['time'],5,2);

				$timezone = new DateTimeZone($context['tvilles'][$context['idxville']]['timezone']);
				$localDateTime = new DateTime('now', $timezone);
				$localTime  = $localDateTime->format('H:i');


				$vdt=MiniPavi\MiniPaviCli::clearScreen();
				$vdt.= file_get_contents('meteofondpage.vdt');
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,5);
				$vdt.=VDT_TXTBLUE.VDT_FDINV;
				for($i=5;$i<23;$i++) {
					$vdt.=' '.VDT_CLRLN.VDT_CRLF;
				}
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,23).VDT_TXTGREEN.MiniPavi\MiniPaviCli::repeatChar('`',40);
				$vdt.=MiniPavi\MiniPaviCli::setPos(8,1);
				$vdt.=MiniPavi\MiniPaviCli::toG2(mb_substr($context['tvilles'][$context['idxville']]['nomVille'],0,31));
				$vdt.=MiniPavi\MiniPaviCli::setPos(8,2).VDT_TXTYELLOW;
				$vdt.=MiniPavi\MiniPaviCli::toG2(mb_substr($context['tvilles'][$context['idxville']]['pays'].' Pop. '.number_format($context['tvilles'][$context['idxville']]['population'],0,',',' ').' h.',0,31));
				$vdt.=MiniPavi\MiniPaviCli::setPos(8,3).VDT_TXTYELLOW;
				$vdt.=$localeDate.MiniPavi\MiniPaviCli::toG2(" Lever ").substr($tFuture[0]['lever'],-5).MiniPavi\MiniPaviCli::toG2(" Coucher ").substr($tFuture[0]['coucher'],-5);
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,6);
				$vdt.=VDT_BGBLUE.VDT_TXTYELLOW.' ACTUELLEMENT A '.$localTime;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,8);
				$vdt.=VDT_BGBLUE.VDT_TXTCYAN.VDT_SZDBLHW.' '.(int)($tNow['instant']['temperature']).MiniPavi\MiniPaviCli::toG2('°');
				$vdt.=VDT_SZNORM.VDT_TXTWHITE.' Ressentie '.(int)($tNow['instant']['temperatureRessentie']).MiniPavi\MiniPaviCli::toG2('°');
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,9);
				$vdt.=VDT_BGBLUE.VDT_TXTWHITE.' '.MiniPavi\MiniPaviCli::toG2($tWMO[$tNow['instant']['weatherCode']]['texte']);
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,10);
				$vdt.=VDT_BGBLUE.VDT_TXTWHITE.' Nuages '.(int)($tNow['instant']['couvertureNuage']).'% Pluie '.(int)($tNow['instant']['precipitation']).'mm';
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,11);
				$vdt.=VDT_BGBLUE.VDT_TXTWHITE.' Vent '.(int)($tNow['instant']['vent']).'km/h '.$tNow['instant']['directionVent'];
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,12).VDT_BGBLUE.VDT_TXTRED.' '.MiniPavi\MiniPaviCli::repeatChar('`',38);

				if ($tNow['instant']['jour']==1)
					$vdt.= file_get_contents('icones/'.$tWMO[$tNow['instant']['weatherCode']]['imgjour'].'.vdt');
				else
					$vdt.= file_get_contents('icones/'.$tWMO[$tNow['instant']['weatherCode']]['imgnuit'].'.vdt');
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,13).VDT_BGBLUE.VDT_TXTYELLOW.' Matin';
				$vdt.=MiniPavi\MiniPaviCli::setPos(15,13).VDT_BGBLUE.VDT_TXTYELLOW.'Midi';
				$vdt.=MiniPavi\MiniPaviCli::setPos(28,13).VDT_BGBLUE.VDT_TXTYELLOW.'Soir';
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,20).VDT_BGBLUE.VDT_TXTCYAN.' '.(int)($tNow['matin']['temperature']).MiniPavi\MiniPaviCli::toG2('°');
				$vdt.= VDT_TXTWHITE.' ('.(int)($tNow['matin']['temperatureRessentie']).MiniPavi\MiniPaviCli::toG2('°)');
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,21).VDT_BGBLUE.VDT_TXTWHITE.' Nuag. '.(int)($tNow['matin']['couvertureNuage']).'%';
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,22).VDT_BGBLUE.VDT_TXTWHITE.' Pluie '.(int)($tNow['matin']['precipitation']).'%';
				$vdt.= file_get_contents('icones/'.$tWMO[$tNow['matin']['weatherCode']]['imgjour'].'_matin.vdt');
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(15,20).VDT_BGBLUE.VDT_TXTCYAN.''.(int)($tNow['midi']['temperature']).MiniPavi\MiniPaviCli::toG2('°');
				$vdt.= VDT_TXTWHITE.'('.(int)($tNow['midi']['temperatureRessentie']).MiniPavi\MiniPaviCli::toG2('°)');
				$vdt.=MiniPavi\MiniPaviCli::setPos(15,21).VDT_BGBLUE.VDT_TXTWHITE.'Nuag. '.(int)($tNow['midi']['couvertureNuage']).'%';
				$vdt.=MiniPavi\MiniPaviCli::setPos(15,22).VDT_BGBLUE.VDT_TXTWHITE.'Pluie '.(int)($tNow['midi']['precipitation']).'%';
				$vdt.= file_get_contents('icones/'.$tWMO[$tNow['midi']['weatherCode']]['imgjour'].'_midi.vdt');
			
				$vdt.=MiniPavi\MiniPaviCli::setPos(28,20).VDT_BGBLUE.VDT_TXTCYAN.''.(int)($tNow['soir']['temperature']).MiniPavi\MiniPaviCli::toG2('°');
				$vdt.= VDT_TXTWHITE.'('.(int)($tNow['soir']['temperatureRessentie']).MiniPavi\MiniPaviCli::toG2('°)');
				$vdt.=MiniPavi\MiniPaviCli::setPos(28,21).VDT_BGBLUE.VDT_TXTWHITE.'Nuag. '.(int)($tNow['soir']['couvertureNuage']).'%';
				$vdt.=MiniPavi\MiniPaviCli::setPos(28,22).VDT_BGBLUE.VDT_TXTWHITE.'Pluie '.(int)($tNow['soir']['precipitation']).'%';
				$vdt.= file_get_contents('icones/'.$tWMO[$tNow['soir']['weatherCode']]['imgjour'].'_soir.vdt');
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24).VDT_BGRED;
				if ($context['tquality'] === false)
					$vdt.=MiniPavi\MiniPaviCli::toG2("    Prévisions ").VDT_FDINV." Suite ".VDT_FDNORM." ou ".VDT_FDINV." Sommaire ".VDT_FDNORM." ".VDT_CLRLN;
				else $vdt.=MiniPavi\MiniPaviCli::toG2(" Prév. ").VDT_FDINV." Suite ".VDT_FDNORM." Q.air. ".VDT_FDINV." Guide ".VDT_FDNORM.' ou '.VDT_FDINV." Som. ".VDT_FDNORM." ";
				
				$step = 'temps-actuel-traite-saisie';
				break 2;
			
			case 18:
			case 'temps-actuel-traite-saisie':																		
				// Traitement du choix de l'utilisateur
				// Seule une touche de fonction est attendue: pas besoin de créer une zone de saisie, toutes les touches de fonctions sont acceptées
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$step = 'accueil';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$step = 'temps-actuel-affiche';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					$step = 'previsions';
					break;
				}
				if ($context['tquality'] !== false && MiniPavi\MiniPaviCli::$fctn == 'GUIDE') {
					$step = 'qualite-air';
					break;
				}
				
				// Touche de fonction inopérante, on ne fait rien				
				break 2;
				
			case 'previsions':																					
				// Accueil prévisions
				$tFuture = $context['tfuture'];
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,5);
				$vdt.=VDT_TXTBLUE.VDT_FDINV;
				for($i=5;$i<23;$i++) {
					$vdt.=' '.VDT_CLRLN.VDT_CRLF;
				}


				for($day=0;$day<6;$day++) {
					$l = (3*$day)+5;
					$localDate = substr($tFuture[$day+1]['time'],8,2).'/'.substr($tFuture[$day+1]['time'],5,2);
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+1);
					$vdt.=VDT_BGBLUE.VDT_TXTCYAN;
					$vdt.=VDT_SZDBLH.' '.$localDate.VDT_SZDBLH;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,$l+2);
					$vdt.=VDT_TXTBLUE.VDT_BGRED.VDT_FDINV;
					$vdt.=' '.MiniPavi\MiniPaviCli::repeatChar('`',38);
					
					$vdt.=MiniPavi\MiniPaviCli::setPos(8,$l);
					$vdt.=VDT_BGBLUE;
					$vdt.='Max '.(int)($tFuture[$day+1]['temperatureMax']).MiniPavi\MiniPaviCli::toG2('°');
					$vdt.=' Min '.(int)($tFuture[$day+1]['temperatureMin']).MiniPavi\MiniPaviCli::toG2('°');
					$vdt.=' Pluie '.(int)($tFuture[$day+1]['precipitation']).'%';

					$vdt.=MiniPavi\MiniPaviCli::setPos(8,$l+1);
					$vdt.=VDT_BGBLUE.VDT_TXTYELLOW;
					$vdt.=MiniPavi\MiniPaviCli::toG2($tWMO[$tFuture[$day+1]['weatherCode']]['texte']);	
					
					$vdt.=MiniPavi\MiniPaviCli::setPos(37,$l);
					$vdt.=$smallIcon[$tWMO[$tFuture[$day+1]['weatherCode']]['small']];
					
				}
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24).VDT_BGRED;
				$vdt.=MiniPavi\MiniPaviCli::toG2("    Précédent ").VDT_FDINV." Retour ".VDT_FDNORM." ou ".VDT_FDINV." Sommaire ".VDT_FDNORM." ".VDT_CLRLN;
				
				$step = 'previsions-traite-saisie';
				break 2;
				
			case 'previsions-traite-saisie':																								
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$step = 'accueil';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$step = 'previsions';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					$step = 'temps-actuel-affiche';
					break;
				}
				// Touche de fonction inopérante, on ne fait rien				
				break 2;
			
			case 'qualite-air':																											
				// Accueil qualité de l'air
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,5);
				$vdt.=VDT_TXTBLUE.VDT_FDINV;
				for($i=5;$i<23;$i++) {
					$vdt.=' '.VDT_CLRLN.VDT_CRLF;
				}

				$vdt.=MiniPavi\MiniPaviCli::setPos(9,6);
				$vdt.=VDT_TXTWHITE.VDT_BGBLUE." INDICE DE LA QUALITE DE L'AIR";
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,8);
				$vdt.=VDT_TXTCYAN.VDT_BGBLUE.VDT_SZDBLH.' '.VDT_SZDBLHW.sprintf('%3d',$context['tquality']['european_aqi']).' ';
				$q = (int)$context['tquality']['european_aqi'];
				
				if ($q<51) { $t='Bonne qualité'; $c=VDT_TXTGREEN; $b=20;}
				elseif ($q<101) { $t='Qualité modérée'; $c=VDT_TXTGREEN; $b=16;}
				elseif ($q<151) { $t='Qualité assez mauvaise'; $c=VDT_TXTYELLOW; $b=12;}
				elseif ($q<201) { $t='Mauvaise qualité'; $c=VDT_TXTYELLOW; $b=8;}
				elseif ($q<301) { $t='Très mauvaise qualité'; $c=VDT_TXTRED; $b=4;}
				else { $t='Dangereux, urgence sanitaire'; $c=VDT_TXTMAGENTA; $b=2;}
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(9,9);
				$vdt.=VDT_TXTYELLOW.VDT_BGBLUE.' '.MiniPavi\MiniPaviCli::toG2($t);
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(10,8);
				$vdt.=VDT_SZDBLH.$c.VDT_FDINV;
				for($i=0;$i<20;$i++) {
					if ($i<$b ||$i>$b) $vdt.='}';
					elseif ($i==$b) $vdt.=VDT_TXTBLACK.'}';
					
				}
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,10).VDT_BGBLUE.VDT_TXTRED.' '.MiniPavi\MiniPaviCli::repeatChar('`',38);
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,11);
				$q=(int)$context['tquality']['pm10'];
				$vdt.=VDT_BGBLUE.' PM10 : '.sprintf('%4d',$q). ' microgr./m3';
				if ($q<21) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Très bon');
				elseif ($q<41) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Bon');
				elseif ($q<51) $vdt.=VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2(' Moyen');
				elseif ($q<101) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Mauvais');
				elseif ($q<151) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Très mauvais');
				else $vdt.=VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(' Fuyez!');
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,13);
				$q= (int)$context['tquality']['pm2_5'];
				$vdt.=VDT_BGBLUE.' PM2,5: '.sprintf('%4d',$q).' microgr./m3';
				if ($q<11) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Très bon');
				elseif ($q<21) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Bon');
				elseif ($q<26) $vdt.=VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2(' Moyen');
				elseif ($q<51) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Mauvais');
				elseif ($q<76) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Très mauvais');
				else $vdt.=VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(' Fuyez!');

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,15);
				$q= (int)$context['tquality']['carbon_monoxide'];
				$vdt.=VDT_BGBLUE.' CO   : '.sprintf('%4d',$q).' microgr./m3';
				if ($q<5001) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Très bon');
				elseif ($q<7501) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Bon');
				elseif ($q<10001) $vdt.=VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2(' Moyen');
				elseif ($q<20001) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Mauvais');
				elseif ($q<30001) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Très mauvais');
				else $vdt.=VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(' Fuyez!');

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,17);
				$q= (int)$context['tquality']['nitrogen_dioxide'];
				$vdt.=VDT_BGBLUE.' NO2  : '.sprintf('%4d',$q).' microgr./m3';
				if ($q<41) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Très bon');
				elseif ($q<91) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Bon');
				elseif ($q<121) $vdt.=VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2(' Moyen');
				elseif ($q<231) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Mauvais');
				elseif ($q<341) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Très mauvais');
				else $vdt.=VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(' Fuyez!');

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,19);
				$q= (int)$context['tquality']['sulphur_dioxide'];
				$vdt.=VDT_BGBLUE.' SO2  : '.sprintf('%4d',$q).' microgr./m3';
				if ($q<101) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Très bon');
				elseif ($q<201) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Bon');
				elseif ($q<351) $vdt.=VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2(' Moyen');
				elseif ($q<501) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Mauvais');
				elseif ($q<751) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Très mauvais');
				else $vdt.=VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(' Fuyez!');

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,21);
				$q= (int)$context['tquality']['ozone'];
				$vdt.=VDT_BGBLUE.' O3   : '.sprintf('%4d',$q).' microgr./m3';
				if ($q<51) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Très bon');
				elseif ($q<101) $vdt.=VDT_TXTGREEN.MiniPavi\MiniPaviCli::toG2(' Bon');
				elseif ($q<131) $vdt.=VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2(' Moyen');
				elseif ($q<241) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Mauvais');
				elseif ($q<381) $vdt.=VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(' Très mauvais');
				else $vdt.=VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(' Fuyez!');

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24).VDT_BGRED;
				$vdt.=MiniPavi\MiniPaviCli::toG2("    Précédent ").VDT_FDINV." Retour ".VDT_FDNORM." ou ".VDT_FDINV." Sommaire ".VDT_FDNORM." ".VDT_CLRLN;

				$step = 'qualite-air-traite-saisie';
				break 2;

			case 'qualite-air-traite-saisie':																														
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$step = 'accueil';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$step = 'qualite-air';
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					$step = 'temps-actuel-affiche';
					break;
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

	$nextPage=$prot."://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'];

	$context['step']=$step;
	
	MiniPavi\MiniPaviCli::send($vdt,$nextPage,serialize($context),true,$cmd,$directCall);
} catch (Exception $e) {
	throw new Exception('Erreur MiniPavi '.$e->getMessage());
}
exit;
?>
