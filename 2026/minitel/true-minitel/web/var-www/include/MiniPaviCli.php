<?php
/**
 * @file MiniPaviCli.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.1 Novembre 2023 - Août 2024
 *
 * Communication avec la passerelle MiniPavi
 *
 * License GPL v2 ou supérieure
 *
 * 22/01/2024 : Renvoi du numéro de l'appellant si connexion depuis RTC/VoIP
 * 11/02/2024 : Redirection vers émulateur Minitel si appel direct depuis un navigateur
 * 08/03/2024 : Modifications concernant la converstions des caractères spéciaux
 * 16/03/2024 : Modifications concernant l'appel direct d'une url (ajout DIRECTCNX) et ajout des commandes createConnectToExtCmd et createConnectToTlnCmd
 * 17/04/2024 : Modifications concernant createBackgroundCallCmd: ajout de la simulation utilisateur 
 * 19/08/2024 : Ajout fonctions "WebMedia"
 * 11/10/2024 : Ajout commande "createInputFormCmd"
 *
 */
 
namespace MiniPavi;
 
const VERSION = '1.0';
define('VDT_LEFT', "\x08");			// Déplacement curseur vers la gauche
define('VDT_RIGHT', "\x09");		// Déplacement curseur vers la droite
define('VDT_DOWN', "\x0A");			// Déplacement curseur vers le bas
define('VDT_UP', "\x0B");			// Déplacement curseur vers le haut
define('VDT_CR', "\x0D");			// Retour charriot (début de ligne)
define('VDT_CRLF', "\x0D\x0A");		// Retour charriot + déplacement vers le bas
define('VDT_CLR', "\x0C");			// Effacement écran
define('VDT_G0', "\x0F");			// Jeu de caractères G0
define('VDT_G1', "\x0E");			// Jeu de caractères G1
define('VDT_G2', "\x19");			// Jeu de caractères G2
define('VDT_POS', "\x1F");			// Positionnement du curseur
define('VDT_REP', "\x12");			// Répétition caractère
define('VDT_CURON', "\x11");		// Curseur allumé
define('VDT_CUROFF', "\x14");		// Curseur éteint
define('VDT_CLRLN', "\x18");		// Effacement fin de ligne
define('VDT_SZNORM', "\x1B\x4C");	// Taille des caratcères normale
define('VDT_SZDBLH', "\x1B\x4D");	// Taille des caratcères double hauteur
define('VDT_SZDBLW', "\x1B\x4E");	// Taille des caratcères double largeur
define('VDT_SZDBLHW', "\x1B\x4F");	// Taille des caratcères double hauteur+double largeur
define('VDT_TXTBLACK', "\x1B@");	// Couleur texte noir
define('VDT_TXTRED', "\x1BA");		// Couleur texte rouge
define('VDT_TXTGREEN', "\x1BB");	// Couleur texte vert
define('VDT_TXTYELLOW', "\x1BC");	// Couleur texte jaune
define('VDT_TXTBLUE', "\x1BD");		// Couleur texte bleu
define('VDT_TXTMAGENTA', "\x1BE");	// Couleur texte magenta
define('VDT_TXTCYAN', "\x1BF");		// Couleur texte cyan
define('VDT_TXTWHITE', "\x1BG");	// Couleur texte blanc
define('VDT_BGBLACK', "\x1BP");		// Couleur fond noir
define('VDT_BGRED', "\x1BQ");		// Couleur fond rouge
define('VDT_BGGREEN', "\x1BR");		// Couleur fond vert
define('VDT_BGYELLOW', "\x1BS");	// Couleur fond jaune
define('VDT_BGBLUE', "\x1BT");		// Couleur fond bleu
define('VDT_BGMAGENTA', "\x1BU");	// Couleur fond magenta
define('VDT_BGCYAN', "\x1BV");		// Couleur fond cyan
define('VDT_BGWHITE', "\x1BW");		// Couleur fond blanc
define('VDT_BLINK', "\x1BH");		// Clignotement
define('VDT_FIXED', "\x1BI");		// Arrêt clignotement
define('VDT_STOPUNDERLINE', "\x1BY");	// Arrêt soulignement
define('VDT_STARTUNDERLINE', "\x1BZ");// Début soulignement
define('VDT_FDNORM', "\x1B\\");		// Fond normal
define('VDT_FDINV', "\x1B]");		// Fond inversé

define('PRO_MIN',"\x1B\x3A\x69\x45");					// Passage clavier en minuscules
define('PRO_MAJ',"\x1B\x3A\x6A\x45");					// Passage clavier en majuscules
define('PRO_LOCALECHO_OFF',"\x1B\x3B\x60\x58\x51");		// Arrêt echo local
define('PRO_LOCALECHO_ON',"\x1B\x3B\x61\x58\x51");		// Marche echo local
define('PRO_ROULEAU_ON', "\x1B\x3A\x69\x43");			// Mode rouleau actif
define('PRO_ROULEAU_OFF', "\x1B\x3A\x6A\x43");			// Mode rouleau inactif
define('VDT_RESET_DRCS', "\x1B\x28\x40\x1B\x29\x63");	// "Réinitialisation" des jeux de caractères normaux

// Touche de fonctione acceptables pour une saisie utilisateur
define('MSK_SOMMAIRE', 1);
define('MSK_ANNULATION', 2);
define('MSK_RETOUR', 4);
define('MSK_REPETITION', 8);
define('MSK_GUIDE', 16);
define('MSK_CORRECTION', 32);
define('MSK_SUITE', 64);
define('MSK_ENVOI', 128);

//define('DEFAULT_MINIPAVI_GW', 'wss://go.minipavi.fr:8181');		// Passerelle MiniPavi à utiliser si l'appel au script n'est pas effectué par une passerelle
define('DEFAULT_MINIPAVI_GW', 'ws://192.168.0.85:8182');		// Passerelle MiniPavi à utiliser si l'appel au script n'est pas effectué par une passerelle


class MiniPaviCli {
	
	static public $uniqueId='';		// Identifiant unique de la connexion
	static public $remoteAddr='';	// IP de l'utilisateur ou "CALLFROM xxxx" (xxx = numéro tel) si accès par téléphone
	static public $content=array();	// Contenu saisi
	static public $fctn='';			// Touche de fonction utilisée (ou CNX ou FIN)
	static public $urlParams='';	// Paramètres fournis lors de l'appel à l'url du service
	static public $context='';		// 65000 caractres libres d'utilisation et rappellés à chaque accès.
	static public $typeSocket;		// Type de connexion ('websocket' ou 'other')
	static public $versionMinitel;	// Version Minitel si connue, sinon '???'
	
	
	/**********************************************************************************
	//*********************************************************************************	
	//**
	//**
	//**	Fonctions d'interfaçage avec MiniPavi
	//**
	//**
	//*********************************************************************************	
	//********************************************************************************/
	
	
	/*************************************************
	// Reçoit les données envoyées depuis MiniPavi
	**************************************************/
	
	static function start() {
		if (strpos(@$_SERVER['HTTP_USER_AGENT'],'MiniPAVI') === false) {
			$currentUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$currentUrl = urlencode($currentUrl);
			$redirectUrl = 'http://192.168.0.85/emulminitel/index.php?gw='.urlencode(DEFAULT_MINIPAVI_GW.'/?url='.$currentUrl);
			header("Location: $redirectUrl");
			exit;
		}
		$rawPostData = file_get_contents("php://input");
		
		
		try {
			$requestData = json_decode($rawPostData,false,5,JSON_THROW_ON_ERROR);
				
			self::$uniqueId = @$requestData->PAVI->uniqueId;
			self::$remoteAddr = @$requestData->PAVI->remoteAddr;
			self::$typeSocket = @$requestData->PAVI->typesocket;
			self::$versionMinitel = @$requestData->PAVI->versionminitel;
			self::$content = @$requestData->PAVI->content;
			self::$context = @$requestData->PAVI->context;
			self::$fctn = @$requestData->PAVI->fctn;
			if (isset($requestData->URLPARAMS))
				self::$urlParams = @$requestData->URLPARAMS;

		}  catch (Exception $e) {
			throw new Exception('Erreur json decode '.$e->getMessage());
		}
	}
	
	/*************************************************
	// Envoi des données à MiniPavi 
	// content: contenu de la page vdt
	// next: prochaine url à être appellée après saisie de l'utilisateur (ou déconnexion)
	// echo: active l'echo (true ou false)
	// commande: envoi une commande à MiniPavi
	// directcall: appel directement la prochaine url sans attendre une action utilisateur (limité à 2 utilisations consécutives)
	//				peut avoir la valeur 'yes-cnx' ou 'yes' (si true, équivaut à 'yes', pour compatibilité)
	//				Si 'yes' (ou true): la fonction envoyée au script sera 'DIRECT'
	//				Si 'yes-cnx': la fonction envoyée au script sera 'DIRECTCNX' et devra être traité comme une nouvelle connexion par le script
	**************************************************/
	
	static function send($content,$next,$context='',$echo=true,$cmd=null,$directCall=false) {
		$content = mb_convert_encoding($content,'7bit');
		$rep['version']=VERSION;
		$rep['content']=@base64_encode($content);
		$rep['context']=mb_convert_encoding(mb_substr($context,0,65000),'UTF-8');
		if ($echo)	$rep['echo']='on';
		else $rep['echo']='off';
		$rep['directcall']='no';
		if ($directCall !== false && $directCall !== 'no' && $directCall !== '') {
			if ($directCall === 'yes-cnx')
				$rep['directcall']='yes-cnx';
			else
				$rep['directcall']='yes';
		}
		
		
		$rep['next']=$next;
		
		if ($cmd && is_array($cmd))
			$rep = array_merge($rep,$cmd);
		$rep = json_encode($rep);
		
		echo $rep."\n";
	}



	/**********************************************************************************
	//*********************************************************************************	
	//**
	//**
	//**	Fonctions de création de commandes
	//**
	//**
	//*********************************************************************************	
	//********************************************************************************/

	
	/*************************************************
	// Cré une commande 'InputTxt' de saisie d'une ligne, validée par Envoi
	// posX: position X du champs de saisie
	// posY: position Y du champs de saisie
	// length: longueur du champs de saisie
	// validWith: valide la saisie uniquement avec les touches de fonctions indiquées
	// cursor: active l'affichage du curseur
	// spaceChar: caractère utilisée pour afficher le champs de saisie
	// char: caractère à afficher à chaque saisie (à la place du caractère saisi)
	// preFill: texte de pré-remplissage
	**************************************************/
	
	static function createInputTxtCmd($posX=1,$posY=1,$length=1,$validWith=MSK_ENVOI,$cursor=true,$spaceChar=' ',$char='',$preFill='') {
		$posX = (int)$posX;
		$posY = (int)$posY;
		$length = (int)$length;
		
		if ($posX<1 || $posX>40)
			$posX=1;
		if ($posY<0 || $posY>24)
			$posY=1;
		$maxLength = 41 - $posX;
		if ($length<1 || $length>$maxLength)
			$length=1;
		
		if (isset($preFill) && mb_strlen($preFill)>$length)
			$preFill = mb_substr($preFill,0,$length);
		else if (!isset($preFill)) $preFill='';
		
		$cmd=array();
		$cmd['COMMAND']['name']='InputTxt';
		$cmd['COMMAND']['param']['x']=$posX;
		$cmd['COMMAND']['param']['y']=$posY;
		$cmd['COMMAND']['param']['l']=$length;
		$cmd['COMMAND']['param']['char']=$char;
		$cmd['COMMAND']['param']['spacechar']=$spaceChar;
		$cmd['COMMAND']['param']['prefill']=$preFill;
		if ($cursor)
			$cmd['COMMAND']['param']['cursor']='on';
		else $cmd['COMMAND']['param']['cursor']='off';
		$cmd['COMMAND']['param']['validwith']=(int)$validWith;
		return $cmd;
	}

	
	/*************************************************
	// Cré une commande 'InputMsg' de saisie de plusieurs lignes, validée par n'mporte quelle touche de fonction (sauf Suite, Retour, Annulation et Correction)
	// posX: position X de la zone de saisie
	// posY: position Y de la zone de saisie
	// w: longueur de la zone de saisie
	// h: hauteur de la zone de saisie
	// validWith: valide la saisie uniquement avec les touches de fonctions indiquées	
	// cursor: active l'affichage du curseur
	// spaceChar: caractère utilisée pour afficher la zone de saisie
	// preFill: tableau du texte de pré-remplissage de chaque ligne
	**************************************************/
	
	static function createInputMsgCmd($posX=1,$posY=1,$width=1,$height=1,$validWith=MSK_ENVOI,$cursor=true,$spaceChar=' ',$preFill=array()) {
		$posX = (int)$posX;
		$posY = (int)$posY;
		$width = (int)$width;
		$height = (int)$height;
		if (!is_array($preFill))
			$preFill = array();
		
		if ($posX<1 || $posX>40)
			$posX=1;
		if ($posY<1 || $posY>24)
			$posY=1;
		
		$maxWidth = 41 - $posX;
		if ($width<1 || $width>$maxWidth)
			$width=$maxWidth;
		
		$maxHeight = 25 - $posY;
		if ($height<1 || $height>$maxHeight)
			$height=$maxHeight;
		if (is_array($preFill) && count($preFill)>0) {
			array_splice($preFill, $height);
			foreach($preFill as $numLine=>$line) {
				$preFill[$numLine] = mb_substr($line,0,$width);
			}
		}
		
		$cmd=array();
		$cmd['COMMAND']['name']='InputMsg';
		$cmd['COMMAND']['param']['x']=$posX;
		$cmd['COMMAND']['param']['y']=$posY;
		$cmd['COMMAND']['param']['w']=$width;
		$cmd['COMMAND']['param']['h']=$height;
		$cmd['COMMAND']['param']['spacechar']=$spaceChar;
		$cmd['COMMAND']['param']['prefill']=$preFill;
		if ($cursor)
			$cmd['COMMAND']['param']['cursor']='on';
		else $cmd['COMMAND']['param']['cursor']='off';
		$cmd['COMMAND']['param']['validwith']=(int)$validWith;
		return $cmd;
	}



	/*************************************************
	// Cré une commande 'InputForm' de saisie d'un formulaire, validée par n'mporte quelle touche de fonction (sauf Suite, Retour, Annulation et Correction)
	// posX: tableau des positions X de chaque champs de saisie
	// posY: tableau des positions Y de chaque champs de saisie
	// length: tableau des longueur de chaque champs de saisie
	// validWith: valide la saisie uniquement avec les touches de fonctions indiquées	
	// cursor: active l'affichage du curseur
	// spaceChar: caractère utilisée pour afficher la zone de saisie
	// preFill: tableau du texte de pré-remplissage de chaque ligne
	**************************************************/

	static function createInputFormCmd($posX=array(1),$posY=array(1),$length=array(10),$validWith=MSK_ENVOI,$cursor=true,$spaceChar='.',$preFill=array()) {
		
		if (is_array($posX) && is_array($posY) && is_array($length)) {
			$cX = count($posX);
			$cY = count($posY);
			$cL = count($length);
		} else {
			$cX = $cY = $cL = 0;
		}
		
		if ($cX<1 || $cX>30 || $cX != $cY || $cY != $cL) {
			$posX = array(1);
			$posY = array(1);
			$length = array(10);
			$cX = 1;
		} else {
			foreach($posX as $k=>$v) {
				if ($posX[$k]<1 || $posX[$k]>40)
					$posX[$k] = 1;
				if ($posY[$k]<1 || $posY[$k]>24)
					$posY[$k] = 1;
				if ($length[$k]<1 || $length[$k]>41-$posX[$k])		
					$length[$k] = 41-$posX[$k];
			}
		}
		
		if (!is_array($preFill))
			$preFill = array();
		
		if (count($preFill)>0) {
			array_splice($preFill, $cX);
			foreach($preFill as $numLine=>$line) {
				$preFill[$numLine] = mb_substr($line,0,$length[$numLine]);
			}
		}
		
		$cmd=array();
		$cmd['COMMAND']['name']='InputForm';
		$cmd['COMMAND']['param']['x']=$posX;
		$cmd['COMMAND']['param']['y']=$posY;
		$cmd['COMMAND']['param']['l']=$length;
		$cmd['COMMAND']['param']['spacechar']=$spaceChar;
		$cmd['COMMAND']['param']['prefill']=$preFill;
		if ($cursor)
			$cmd['COMMAND']['param']['cursor']='on';
		else $cmd['COMMAND']['param']['cursor']='off';
		$cmd['COMMAND']['param']['validwith']=(int)$validWith;
		return $cmd;
	}


	
	/*************************************************
	// Envoi un message push en ligne "0" aux utilisateurs désignés
	// tMessage: tableau des messages à envoyer
	// tUniqueId: tableau des identifiants uniques des destinataires
	**************************************************/
	
	static function createPushServiceMsgCmd($tMessage=array(),$tUniqueId=array()) {
		if (!is_array($tMessage) || count($tMessage)<1)
			return false;
		if (!is_array($tUniqueId) || count($tUniqueId)<1)
			return false;
		$cmd=array();
		$cmd['COMMAND']['name']='PushServiceMsg';
		$cmd['COMMAND']['param']['message'] = array();
		$cmd['COMMAND']['param']['uniqueids'] = array();
		$k=0;
		foreach($tMessage as $key=>$message) {
			$cmd['COMMAND']['param']['message'][$k]=$message;
			$cmd['COMMAND']['param']['uniqueids'][$k]=$tUniqueId[$key];
			$k++;
		}
	
		return $cmd;
	}


	/*************************************************
	// Demande différée d'appel par MiniPavi d'une url
	// (plusieurs appels possibles)
	// tUrl: tableau des url à appeller (ou des données envoyées, voir plus bas)
	// tTime: tableau des timestamp de l'heure des appels à effectuer
	// $tUniqueId: identifiants uniques qui seront indiqués dans les requêtes
	// $tSimulate: si false appel l'url indiqué dans $tUrl sans changement de l'état de l'utilisateur (le service ne pourra que faire en retour un message en ligne 0 via la commande createPushServiceMsgCmd)
	//				si true appel de l'url en simulant une saisie utilisateur: dans ce cas, l'url appellée est celle indiquée dans 'nexturl' de l'utilisateur et $tUrl contient les données "saisies".
	//				Le service peut se comporter comme lorsqu'il y a interaction d'un utilisateur (affichage etc.)
	//				Dans le premier cas (false), la touche de fonctione indiquée au script sera 'BGCALL', sinon (true) 'BGCALL-SIMU'
	**************************************************/

	static function createBackgroundCallCmd($tUrl=array(),$tTime=array(),$tUniqueId=array(),$tSimulate=array()) {
		if (!is_array($tUrl) || count($tUrl)<1)
			return false;
		if (!is_array($tTime) || count($tTime)<1)
			return false;
		if (!is_array($tUniqueId) || count($tUniqueId)<1)
			return false;
		if (!is_array($tSimulate) || count($tSimulate)<1)
			return false;
		
		$cmd=array();
		$cmd['COMMAND']['name']='BackgroundCall';
		$cmd['COMMAND']['param']['url'] = array();
		$cmd['COMMAND']['param']['time'] = array();
		$cmd['COMMAND']['param']['uniqueid'] = array();
		$cmd['COMMAND']['param']['simulate'] = array();
		$k=0;
		foreach($tUrl as $key=>$url) {
			$cmd['COMMAND']['param']['url'][$k]=$url;
			$cmd['COMMAND']['param']['time'][$k]=$tTime[$key];
			$cmd['COMMAND']['param']['uniqueid'][$k] = $tUniqueId[$key];
			if ($tSimulate[$key] === true) 
				$cmd['COMMAND']['param']['simulate'][$k] = $tSimulate[$key];
			else $cmd['COMMAND']['param']['simulate'][$k] = false;
			$k++;
		}
	
		return $cmd;
	}

	/*************************************************
	// Demande la connexion au serveur RTC
	// accessible au numéro indiqué
	// number: numéro d'appel
	// RX: Force minimale du signal en réception (dB)
	// TX: Force du signal en émission (dB)
	// key: Clé d'autorisation pour appel sortants
	**************************************************/

	static function createConnectToExtCmd($number,$RX=-35,$TX=-18,$key='') {
		$number = trim($number);
		if ($number == '')
			return false;
		
		$cmd=array();
		$cmd['COMMAND']['name']='connectToExt';
		$cmd['COMMAND']['param']['number'] = $number;
		$cmd['COMMAND']['param']['key'] = $key;
		$cmd['COMMAND']['param']['RX'] = (int)$RX;
		$cmd['COMMAND']['param']['TX'] = (int)$TX;
		return $cmd;
	}


	/*************************************************
	// Demande la connexion a un serveur par telnet
	// à l'adresse indiquée
	// host: adresse ex: 1.2.3.4:23
	// startSeq: séquence à envoyer au serveur à la connexion
	// key: Clé d'autorisation pour connexions sortantes
	**************************************************/

	static function createConnectToTlnCmd($host,$echo='off',$case='lower',$startSeq='',$key='') {
		$host = trim($host);
		if ($host == '')
			return false;
		
		$cmd=array();
		$cmd['COMMAND']['name']='connectToTln';
		$cmd['COMMAND']['param']['host'] = $host;
		$cmd['COMMAND']['param']['key'] = $key;
		if ($echo !='off' && $echo !='on')
			$echo ='off';
		if ($case !='lower' && $case !='upper')
			$case ='lower';
		$cmd['COMMAND']['param']['echo'] = $echo;		
		$cmd['COMMAND']['param']['case'] = $case;		
		$cmd['COMMAND']['param']['startseq'] = $startSeq;	
		
		return $cmd;
	}

	/*************************************************
	// Demande la connexion a un serveur par ws
	// à l'adresse indiquée
	// host: adresse ex: 1.2.3.4:23
	// key: Clé d'autorisation pour connexions sortantes
	**************************************************/

	static function createConnectToWsCmd($host,$path='/',$echo='off',$case='lower',$proto='',$key='') {
		$host = trim($host);
		if ($host == '')
			return false;
		
		$cmd=array();
		$cmd['COMMAND']['name']='connectToWs';
		$cmd['COMMAND']['param']['host'] = $host;
		$cmd['COMMAND']['param']['key'] = $key;
		$cmd['COMMAND']['param']['path'] = $path;
		if ($echo !='off' && $echo !='on')
			$echo ='off';
		if ($case !='lower' && $case !='upper')
			$case ='lower';
		$cmd['COMMAND']['param']['echo'] = $echo;		
		$cmd['COMMAND']['param']['case'] = $case;		
		$cmd['COMMAND']['param']['proto'] = $proto;		
		return $cmd;
	}


	/*************************************************
	// Demande de visualisation d'un autre utilisateur
	// uniqueid: identifiant de la connexion à dupliquer
	// key: Clé d'autorisation pour connexions sortantes
	**************************************************/

	static function createDuplicateStream($uniqueid,$key='') {
		$uniqueid = trim($uniqueid);
		if ($uniqueid == '')
			return false;
		
		$cmd=array();
		$cmd['COMMAND']['name']='duplicateStream';
		$cmd['COMMAND']['param']['uniqueid'] = $uniqueid;
		$cmd['COMMAND']['param']['key'] = $key;
		return $cmd;
	}


	/*************************************************
	// Demande de déconnexion
	**************************************************/

	static function createLibCnxCmd() {
		$cmd=array();
		$cmd['COMMAND']['name']='libCnx';
		return $cmd;
	}



	/**********************************************************************************
	//*********************************************************************************	
	//**
	//**
	//**	Fonctions d'affichage / multimedia
	//**
	//**
	//*********************************************************************************	
	//********************************************************************************/

	
	/*************************************************
	// Positionne le curseur de l'utilisateur
	**************************************************/

	static function setPos($col,$line) {
		// col : de 1 a 40
		// line : de 0 à 24
		return VDT_POS.chr(64+$line).chr(64+$col);
	}

	
	/*************************************************
	// Ecrit en ligne 0 puis le curseur revient à la position courante
	**************************************************/
	
	static function writeLine0($txt,$blink=false) {
		$txt = self::toG2($txt);
		if ($blink) 
			$blink=VDT_BLINK;
		else $blink='';
		$vdt = self::setPos(1,0).$blink.$txt.VDT_FIXED.VDT_CLRLN."\n";
		return $vdt;
	}

	

	/*************************************************
	// Efface totalement l'écran
	**************************************************/

	static function clearScreen() {
			$vdt = self::setPos(1,0).' '.self::repeatChar(' ',39).self::setPos(1,1).VDT_CLR.VDT_CUROFF;
			return $vdt;
		
	}

	
	/*************************************************
	// Repète num fois le caractère char
	**************************************************/

	static function repeatChar($char,$num) {
		return $char.VDT_REP.chr(63+$num);
	}
	
	/*************************************************
	// Ecrit un texte centré, précédé des attributs $attr
	**************************************************/	
	
	static function writeCentered($line,$text,$attr='') {
		if (mb_strlen($text)>=40) {
			$vdt = self::setPos(1,$line);
		} else {
			$vdt = self::setPos(ceil((40-mb_strlen($text))/2),$line);
		}
		$vdt.= $attr.self::toG2($text);
		return $vdt;
	}

	/*************************************************
	// Génère code videotex de demande d'affichage sur WebMedia de la video YT $youtubeId
	**************************************************/	
	
	static function webMediaYoutube($youtubeId) {
		$vdt = self::setPos(1,0);
		$vdt.= "\x1B@\x14#DYT:$youtubeId\x14#F".VDT_CLRLN."\n";
		return $vdt;
	}

	/*************************************************
	// Génère code videotex de demande d'affichage sur WebMedia d'une video accessible à l'URL $url
	**************************************************/	
	
	static function webMediaVideo($url) {
		$vdt = self::setPos(1,0);
		$vdt.= "\x1B@\x14#DVID:$url\x14#F".VDT_CLRLN."\n";
		return $vdt;
	}

	/*************************************************
	// Génère code videotex de demande de jouer sur WebMedia une son accessible à l'URL $url
	**************************************************/	

	static function webMediaSound($url) {
		$vdt = self::setPos(1,0);
		$vdt.= "\x1B@\x14#DSND:$url\x14#F".VDT_CLRLN."\n";
		return $vdt;
	}

	/*************************************************
	// Génère code videotex de demande d'affichage sur WebMedia d'une image accessible à l'URL $url
	**************************************************/	

	static function webMediaImg($url) {
		$vdt = self::setPos(1,0);
		$vdt.= "\x1B@\x14#DIMG:$url\x14#F".VDT_CLRLN."\n";
		return $vdt;
	}

	/*************************************************
	// Génère code videotex pour lien vers $url
	**************************************************/	
	
	static function webMediaUrl($url) {
		$vdt = self::setPos(1,0);
		$vdt.= "\x1B@\x14#DURL:$url\x14#F".VDT_CLRLN."\n";
		return $vdt;
	}
	
	
	/*************************************************
	// Conversion de caractères spéciaux
	**************************************************/

	static function toG2($str) {
		
		$str=preg_replace('/[\x00-\x1F\x81\x8D\x8F\x90\x9D]/', ' ', $str);
		
		$tabAcc=array('é','è','à','ç','ê','É','È','À','Ç','Ê',
		'β','ß','œ','Œ','ü','û','ú','ù','ö','ô','ó','ò','ï','î','í','ì','ë','ä',
		'â','á','£','°','±','←','↑','→','↓','¼','½','¾','Â','Î','ō','á','’',' ','ň','ć','ř','ý','š','í','ą');
		
		$tabG2=array(VDT_G2."\x42e",
		VDT_G2."\x41e",
		VDT_G2."\x41a",
		VDT_G2."\x4B\x63",
		VDT_G2."\x43e",
		VDT_G2."\x42E",
		VDT_G2."\x41E",
		VDT_G2."\x41A",
		VDT_G2."\x4B\x63",
		VDT_G2."\x43E",
		VDT_G2."\x7B",		
		VDT_G2."\x7B",		
		VDT_G2."\x7A",		
		VDT_G2."\x6A",		
		VDT_G2."\x48\x75",		
		VDT_G2."\x43\x75",		
		VDT_G2."\x42\x75",		
		VDT_G2."\x41\x75",		
		VDT_G2."\x48\x6F",		
		VDT_G2."\x43\x6F",		
		VDT_G2."\x42\x6F",		
		VDT_G2."\x41\x6F",		
		VDT_G2."\x48\x69",		
		VDT_G2."\x43\x69",		
		VDT_G2."\x42\x69",		
		VDT_G2."\x41\x69",		
		VDT_G2."\x48\x65",		
		VDT_G2."\x48\x61",		
		VDT_G2."\x43\x61",		
		VDT_G2."\x42\x61",
		VDT_G2."\x23",		
		VDT_G2."\x30",		
		VDT_G2."\x31",		
		VDT_G2."\x2C",		
		VDT_G2."\x2D",		
		VDT_G2."\x2E",		
		VDT_G2."\x2F",		
		VDT_G2."\x3C",		
		VDT_G2."\x3D",		
		VDT_G2."\x3E",
		VDT_G2."\x43A",
		'I','o','a',"'",' ','n','c','r','y','s','i','a'
		);
		
		return str_replace($tabAcc, $tabG2, $str);	
	}
	
}
?>
