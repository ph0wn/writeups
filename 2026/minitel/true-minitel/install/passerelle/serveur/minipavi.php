#!/usr/bin/php
<?php
/**
 * @file minipavi.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.2 Novembre 2023 - Juillet 2025
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * License GPL v2 ou supérieure
 *
 */

mb_internal_encoding('utf-8');
error_reporting(E_ERROR);

define('PAVI_VER', '1.5');

define('CNX_TIMEOUT', 600);		// Période maximum d'inactivité avant deconnexion (secondes)(valeur par défaut)
define('MAX_HISTO_CLIENT',30);	// Max historique clients (valeur par défaut)

define('TYPE_PROC_MAIN',0);		// Processus principal
define('TYPE_PROC_SERVICE',1);	// Processus service
define('TYPE_PROC_VISUWEB',2);	// Processus visualisation web
define('TYPE_PROC_BGCALLS',3);	// Processus appel url en arrière-plan

spl_autoload_register(function($className) {
	if (substr($className,0,5) == 'InCnx')
		require_once 'lib/class.InCnx.php';
	else require_once 'lib/class.'.$className.'.php';
});

require "lib/functions.php";				// Fonctions diverses
require "lib/visuWeb.php";					// Fonctionnalité "visuWeb"

$typeProc = TYPE_PROC_MAIN;

$tSocketsSer = array();		// Sockets serveur
$tObjClient = array();		// Clients
$tObjClientH = array();		// Historique Clients
$tStart = time();			// Heure de démarrage
$objConfig = null ;			// Configuration


/****************************************************

Options ligne de commande
v, optionnel : verbose

cfgfile, optionnel : fichier de configuration (recommandé).
host, obligatoire : adresse de connexion au serveur (domaine ou IP) si connexion websocket activée
maxc, obligatoire : nombre de connexions max
durl, obligatoire : url du service par défaut
wsport, optionnel : port pour les connexions via websockets
wssport, optionnel : port pour les connexions via websockets SSL
astport, optionnel : port pour les connexions depuis Asterisk
telnport, optionnel : port pour les connexions sans websockets
httpport, optionnel : port pour visualistation web admin
httpuser, optionnel : nom d'utilisateur visualisation web (obligatoire si httpport fourni)
httppwd, optionnel : mot de passe visualisation web (obligatoire si httpport fourni)
lpath, optionnel : emplacement du fichier de log
spath, optionnel : emplacement des fichiers de stats
rpath, optionnel : emplacement des enregistrements de sessions
ssl, optionnel : fichier certificat SSL 
sslkey, optionnel : fichier clé SSL 
ipdbkey, optionnel : clé AbuseIPDB.com pour vérification IP

Au moins wsport,wssport,astport ou telnport doit être fourni
Si wsport et/ou wssport sont fournis, host est obligatoire
Les arguments de la ligne de commande sont prioritaires sur le fichier de configuration.

Le script lance au moins 2 processus: 
- le principal qui va attendre les connexions d'utilisateurs et les connexions à la visualistation web
- un processus d'appel en arrière plan: processus qui fait des requêtes http différée vers les services, à la demande de ceux-ci (exemple: voir script miniChat/chatGPT)

Peuvent également exister:
- un processus par connecté par service
- un processus par visualisation web

*******************************************************/

$shortopts = "v";  
$longopts  = array(
	"maxc:",  
    "wsport:",     
	"wssport:",     
	"telnport:",
	"astport:",
	"durl:",
	"lpath:",
	"spath:",
	"rpath:",
	"cfgfile:",
	"ipdbkey:",
	"ssl:",
	"sslkey:",
	"httpport:",
	"httppwd:",
	"httpuser:",
	"host:",
	"help"
);


$stop = false;
$options = getopt($shortopts, $longopts);

if (isset($options['help'])) {
	echo("MiniPAVI Ver ".PAVI_VER."\nhttp://www.minipavi.fr\n*********************************\n\n");
	echo("Options ligne de commande:\n\n");
	echo("-v, optionnel : logs PHP verbose\n");
	echo("--cfgfile, optionnel : fichier de configuration (recommandé)\n");		
	echo("--maxc, obligatoire : nombre de connexions max\n");
	echo("--durl, obligatoire : url du service par défaut\n");	
	echo("--wsport, optionnel : port pour les connexions via websockets\n");
	echo("--wssport, optionnel : port pour les connexions via websockets SSL\n");
	echo("--telnport, optionnel : port pour les connexions sans websockets\n");
	echo("--astport, optionnel : port pour les connexions Asterisk\n");	
	echo("--host, obligatoire : adresse de connexion au serveur (domaine ou IP)\n");		
	echo("--lpath, optionnel : emplacement du fichier de log\n");
	echo("--spath, optionnel : emplacement des fichiers de stats\n");	
	echo("--rpath, optionnel : emplacement des enregistrements de sessions\n");	
	echo("--ipdbkey, optionnel : clé AbuseIPDB.com pour vérification IP\n");	
	echo("--ssl, optionnel : fichier certificat SSL \n");
	echo("--sslkey, optionnel : fichier clé SSL \n\n");
	echo("--httpport, optionnel : port pour accès visu web - WebMedia\n\n");
	echo("--httpuser, optionnel : nom d'utilisateur visu web, obligatoire si httpport fourni\n\n");	
	echo("--httppwd, optionnel : mot de passe visu web, obligatoire si httpport fourni\n\n");

	echo("Au moins wsport,wssport,astport ou telnport doit être fourni\n");
	echo("Si wsport et/ou wssport sont fournis, host est obligatoire\n");
	echo("Si un certificat SSL est fourni, la connexion à Visuweb est SSL (https://..)\n");
	echo("\n*********************************\n\n");
	exit(0);	
}

if (isset($options['cfgfile'])) {
	$cfgFile = $options['cfgfile'];
	$objConfig = new MinipaviCfg($cfgFile);
	if (!$objConfig->load()) {
		echo("Fichier de configuration introuvable ou incorrect : ".$options['cfgfile']."\n");	
		exit(1);
	}
} else 
	$objConfig = new MinipaviCfg('');

	

if (isset($options['v'])) {
	$objConfig->verbose = true;
}

if ($objConfig->verbose) error_reporting(E_ALL);

if (isset($options['host']))
	$objConfig->host = $options['host'];

if (isset($options['maxc']))
	$objConfig->maxCnx = (int)$options['maxc'];
	
if (isset($options['wsport']))
	$objConfig->wsPort = (int)$options['wsport'];
	
if (isset($options['wssport']))
	$objConfig->wssPort = (int)$options['wssport'];
	
if (isset($options['telnport']))
	$objConfig->tcpPort = (int)$options['telnport'];

if (isset($options['astport']))
	$objConfig->astPort = (int)$options['astport'];

if (isset($options['httpport']))
	$objConfig->httpPort = (int)$options['httpport'];

if (isset($options['httppwd']))
	$objConfig->httpPwd = trim($options['httppwd']);

if (isset($options['httpuser']))
	$objConfig->httpUser = trim($options['httpuser']);

if (isset($options['durl']))
	$objConfig->defaultUrl = $options['durl'];

if (isset($options['lpath'])) 
	$objConfig->logPath = $options['lpath'];
if ($objConfig->logPath=='')
	$objConfig->logPath='./';
else if (substr($objConfig->logPath,-1)!='/')
	$objConfig->logPath.='/';
if (!is_dir( $objConfig->logPath ) && !file_exists($objConfig->logPath)) {
	mkdir($objConfig->logPath);       
}		


if (isset($options['spath']))
	$objConfig->statsPath = $options['spath'];
if ($objConfig->statsPath=='')
	$objConfig->statsPath='./';
else if (substr($objConfig->statsPath,-1)!='/')
	$objConfig->statsPath.='/';
if (!is_dir( $objConfig->statsPath ) && !file_exists($objConfig->statsPath)) {
	mkdir($objConfig->statsPath);       
}		


if (isset($options['rpath']))
	$objConfig->recordsPath = $options['rpath'];
if ($objConfig->recordsPath!='' && substr($objConfig->recordsPath,-1)!='/')
	$objConfig->recordsPath.='/';
if (!is_dir( $objConfig->recordsPath ) && !file_exists($objConfig->recordsPath)) {
	mkdir($objConfig->recordsPath);       
}		


if (isset($options['ipdbkey']))
	$objConfig->ipDBKey = $options['ipdbkey'];


if (isset($options['ssl']))
	$objConfig->sslCert = $options['ssl'];

if (isset($options['sslkey']))
	$objConfig->sslKey = $options['sslkey'];

if ((int)$objConfig->timeout <= 0 ) {
	$objConfig->timeout = CNX_TIMEOUT;
}

if ((int)$objConfig->maxHisto < 0 ) {
	$objConfig->maxHisto = MAX_HISTO_CLIENT;
}

if ($objConfig->maxCnx < 1) {
	echo("maxc (connexions max.) incorrect ou manquant, doit être supérieur à 0\n");
	$stop =true;
}

if ($objConfig->defaultUrl=='') {
	echo("durl (URL service par defaut) manquant\n");
	$stop =true;
}

if ($objConfig->wsPort < 1 && $objConfig->tcpPort<1 && $objConfig->wssPort<1 && $objConfig->astPort<1) {
	echo("telnport et/ou wsport et/ou wssport et/ou astport incorrect: au moins l'un des quatre doit être indiqué et supérieur à 0\n");
	$stop =true;
}
if ($objConfig->wssPort>0 && ($objConfig->sslCert=='' || $objConfig->sslKey=='')) {
	echo("Le certificat SSL et la clé doivent être fournis pour une websocket SSL\n");
	$stop =true;
}

if ($objConfig->httpPort>0 && ($objConfig->httpPwd=='' || $objConfig->httpUser=='' || strpos($objConfig->httpPwd, ':')!==false || strpos($objConfig->httpUser, ':')!==false ) ) {
	echo("Un nom d'utilisateur et un mot de passe doivent être fournis pour la connexion visu web\n");
	$stop = true;
}

if ($objConfig->host == '') {
	echo("host manquant\n");
	$stop =true;
}

if ($objConfig->screenSaver== 'no') {
	$objConfig->screenSaver = false;
} else {
	$objConfig->screenSaver = true;
}


if (!$stop && $objConfig->wssPort>0 && !file_exists($objConfig->sslCert)) {
	echo($objConfig->sslCert." introuvable\n");
	$stop =true;
}
if (!$stop && $objConfig->wssPort>0 && !file_exists($objConfig->sslKey)) {
	echo($objConfig->sslKey." introuvable\n");
	$stop =true;
}
	

if (count($objConfig->tAsterisk)>0 && ( @$objConfig->tAsterisk['sipchannel']=='' || @$objConfig->tAsterisk['ip']=='' || @$objConfig->tAsterisk['port']==''
 || @$objConfig->tAsterisk['user']=='' || @$objConfig->tAsterisk['context']=='' || @$objConfig->tAsterisk['ext']=='' || @$objConfig->tAsterisk['maxtime']==''
 || @$objConfig->tAsterisk['calltimeout']=='')) {
	echo("Paramètre(s) manquant(s) dans la configuration Asterisk (appels sortants)\n");
	$stop =true;
}

if ($stop) {
	echo("\n\n*** Arret ***\n\n");
	exit(1);
}


$wsGwUrl = '';
$wssGwUrl = '';
$visuWebUrl = '';

echo ("***********************************\n");
echo ("Démarrage MiniPavi ".PAVI_VER."\n\n");
echo ("Serveur: ".$objConfig->host."\n");
echo ("Verbose: ");
if ($objConfig->verbose) echo "Oui\n"; else echo "Non\n";

if ($cfgFile!='') echo ("Fichier configuration: $cfgFile\n");
else echo ("Fichier configuration: **Aucun**\n");

echo ("Maximum de connexions: ".$objConfig->maxCnx."\n");
if ($objConfig->maxCnxByIP>0)
	echo ("Maximum de connexions/ip: ".$objConfig->maxCnxByIP."\n");
else echo ("Maximum de connexions/ip: Aucune limite\n");
	
echo ("Maximum historique: ".$objConfig->maxHisto."\n");
echo ("Timout inactivité (sec.): ".$objConfig->timeout."\n");
echo ("Port TCP Websockets: ");
if ($objConfig->wsPort>0) {
	echo $objConfig->wsPort."\n";
	$wsGwUrl = 'ws://'.$objConfig->host.':'.$objConfig->wsPort.'/';
	echo ("URL passerelle: ".$wsGwUrl."\n");
} else echo "Désactivé\n";
echo ("Port TCP SSL Websockets: ");
if ($objConfig->wssPort>0) {
	echo $objConfig->wssPort."\n";
	$wssGwUrl = 'wss://'.$objConfig->host.':'.$objConfig->wssPort.'/';
	echo ("URL passerelle SSL: ".$wssGwUrl."\n");
} else echo "Désactivé\n";

echo ("Port TCP standard: ");
if ($objConfig->tcpPort>0) echo $objConfig->tcpPort."\n"; else echo "Désactivé\n";

echo ("Port TCP Asterisk: ");
if ($objConfig->astPort>0) echo $objConfig->astPort."\n"; else echo "Désactivé\n";

echo ("Port TCP Visu web: ");
if ($objConfig->httpPort>0) {
	echo $objConfig->httpPort."\n";
	if ($objConfig->sslCert!='') 
		$visuWebUrl = 'https://'.$objConfig->host.':'.$objConfig->httpPort.'/';
	else $visuWebUrl = 'http://'.$objConfig->host.':'.$objConfig->httpPort.'/';
	echo ("URL VisuWeb: ".$visuWebUrl."\n");
	
} else echo "Désactivé\n";

echo ("Service par défaut: ".$objConfig->defaultUrl."\n");
echo ("Emplacement des logs: ".$objConfig->logPath."\n");
echo ("Emplacement des stats: ".$objConfig->statsPath."\n");
if ($objConfig->recordsPath != '')
	echo ("Emplacement des enregistrements de sessions: ".$objConfig->recordsPath."\n");
else 
	echo ("Emplacement des enregistrements de sessions: Inactif\n");

if ($objConfig->ipDBKey!='') echo ("Clé AbuseIPDB: ".$objConfig->ipDBKey."\n");
else echo ("Clé AbuseIPDB: **Aucune**\n");

if ($objConfig->ipBlackList!='') echo ("Blacklist IP: ".$objConfig->ipBlackList."\n");
else echo ("Blacklist IP: **Aucune**\n");

if ($objConfig->ipWhiteList!='') echo ("Whitelist IP: ".$objConfig->ipWhiteList."\n");
else echo ("Whitelist IP: **Aucune**\n");

if ($objConfig->sslCert!='') {
	echo ("Cert SSL: ".$objConfig->sslCert."\n");
	echo ("Key: ".$objConfig->sslKey."\n");
}
else echo ("SSL: Non\n");

echo ("Economiseur d'écran minitel: ");
if ($objConfig->screenSaver) 
	echo "Activé\n";
else
	echo "Désactivé\n";

if (count($objConfig->tAsterisk)>0) {
	echo ("\n>>> Configuration AMI (Appels sortants Asterisk) <<<\n\n");
	echo ("Channel SIP: ".$objConfig->tAsterisk['sipchannel']."\n");
	echo ("IP AMI: ".$objConfig->tAsterisk['ip']."\n");
	echo ("Port AMI: ".$objConfig->tAsterisk['port']."\n");
	echo ("Utilisateur AMI: ".$objConfig->tAsterisk['user']."\n");
	echo ("Contexte Asterisk: ".$objConfig->tAsterisk['context']."\n");
	echo ("Extension Asterisk: ".$objConfig->tAsterisk['ext']."\n");
	echo ("Durée maximum: ".$objConfig->tAsterisk['maxtime']." sec.\n");
	echo ("Timeout appel: ".$objConfig->tAsterisk['calltimeout']." msec.\n");
} else {
	echo ("\n>>> Aucune configuration AMI (Appels sortants Asterisk) <<<\n\n");	
}

echo ("***********************************\n\n");


$mainPid = getmypid();

trigger_error("[MiniPavi-Main] Démarrage");

declare(ticks = 1); 
function sigint()  { 
	exit;  
}  
pcntl_signal(SIGCHLD, "onChildStop");
pcntl_signal(SIGINT, 'sigint');  
pcntl_signal(SIGTERM, 'sigint'); 
pcntl_signal(SIGUSR2, "childIsCalling");

register_shutdown_function('onStop');

$objMiniPaviM = new MiniPavi($objConfig->logPath);

$objMiniPaviM->log("Démarrage...");
$objMiniPaviM->mainPid = $mainPid;;

// Pour Socket "WS"
if ($objConfig->wsPort > 0) {
	$inCnxWS = new InCnxWS();
	$tSocketsSer[0] = $inCnxWS->setServerSocket(port: $objConfig->wsPort);
	if (!$tSocketsSer[0]) {
		trigger_error ("[MiniPavi-Main] La création socket websock a échouée...");
	}
	trigger_error ("[MiniPavi-Main] TCP/WS Attente connexions port ".$objConfig->wsPort." ...");
}

// Pour Socket "WSS"
if ($objConfig->wssPort > 0) {
	$inCnxWSS = new InCnxWS();
	$tSocketsSer[2] = $inCnxWSS->setServerSocket(port: $objConfig->wssPort, sslCert: $objConfig->sslCert, sslKey: $objConfig->sslKey);
	if (!$tSocketsSer[2]) {
		trigger_error ("[MiniPavi-Main] La création socket SSL websock a échouée...");
	}
	trigger_error ("[MiniPavi-Main] TCP/WSS Attente connexions port ".$objConfig->wssPort." ...");
}

// Pour Socket Telnet (pour connexion depuis Asterisk)
if ($objConfig->astPort > 0) {
	$inCnxRTC = new InCnxRTC();
	$tSocketsSer[1] = $inCnxRTC->setServerSocket(port: $objConfig->astPort);
	if (!$tSocketsSer[1]) {
		trigger_error ("[MiniPavi-Main] La création socket AST websock a échouée...");
	}
	trigger_error ("[MiniPavi-Main] TCP/AST Attente connexions port ".$objConfig->astPort." ...");
}

// Pour Socket Telnet standard
if ($objConfig->tcpPort > 0) {
	$inCnxTelnet = new InCnxTelnet();
	//$tSocketsSer[3] = $inCnxRTC->setServerSocket(port: $objConfig->tcpPort);
	$tSocketsSer[3] = $inCnxTelnet->setServerSocket(port: $objConfig->tcpPort);
	if (!$tSocketsSer[3]) {
		//trigger_error ("[MiniPavi-Main] La création socket TCP websock a échouée...");
		trigger_error ("[MiniPavi-Main] La création socket TCP telnet a échouée...");
	}
	trigger_error ("[MiniPavi-Main] TCP Attente connexions port ".$objConfig->tcpPort." ...");
}


// Pour Socket Web (Visu web)
if ($objConfig->httpPort > 0) {
	if ($objConfig->sslCert!="") {
		$transport = 'tlsv1.3';
		$ssl = ['ssl' => [
          'local_cert'  => $objConfig->sslCert,       
          'local_pk'    => $objConfig->sslKey,    
          'disable_compression' => true,
          'verify_peer'         => false,
		  'allow_self_signed'         => true,            
          'ssltransport' => $transport,
        ] ];
		
		$context = stream_context_create($ssl);
		
		$socketWeb = stream_socket_server(
			'tcp://0.0.0.0:'.$objConfig->httpPort,
			$errno,
			$errstr,
			STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
			$context
		);
		
	} else {
		$socketWeb = stream_socket_server(
			'tcp://0.0.0.0:'.$objConfig->httpPort,
			$errno,
			$errstr,
			STREAM_SERVER_BIND|STREAM_SERVER_LISTEN
			
		);
	}
	trigger_error ("[MiniPavi-Main] Visu web port ".$objConfig->httpPort." ...");
	stream_set_blocking($socketWeb, true);	
} else $socketWeb = false;


$objMiniPaviM->commSockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
if (!is_array($objMiniPaviM->commSockets )) {
	trigger_error("[MiniPavi-Main] Erreur création sockets de communication background call");
	exit(1);
}
stream_set_timeout($objMiniPaviM->commSockets[0],0,50000);
stream_set_timeout($objMiniPaviM->commSockets[1],0,50000);

// Démarrage processus de traitement des appels en arrière plan

if (!startBgcallProc($objMiniPaviM)) {
	trigger_error("[MiniPavi-Main] Erreur startBgcallProc");
	exit(1);
}
		
// On attend de nouvelles connexions

do {
	$tCpy = $tSocketsSer;
	if ($socketWeb)
		$tCpy[] = $socketWeb;
	
	$retSocket=@stream_select($tCpy, $null, $null, 10, 0);
	
	if ($retSocket>0 && (!$socketWeb || ($socketWeb && !in_array($socketWeb,$tCpy,true)))) {
		// Nouvelle connexion
		if (isset($tSocketsSer[0]) && in_array($tSocketsSer[0],$tCpy)) {
			// Websocket non SSL
			$inCnx = $inCnxWS;
			$socket = $tSocketsSer[0];
		} else if (isset($tSocketsSer[2]) && in_array($tSocketsSer[2],$tCpy)) {
			// Websocket SSL
			$inCnx = $inCnxWSS;
			$socket = $tSocketsSer[2];
		} else if (isset($tSocketsSer[3]) && in_array($tSocketsSer[3],$tCpy)) {
			// Socket "telnet"
			$inCnx = $inCnxTelnet;
			$socket = $tSocketsSer[3];
		} else {
			// Socket "telnet" pour Asterisk
			$inCnx = $inCnxRTC;
			$socket = $tSocketsSer[1];
		}
		
		if ($inCnx->accept($socket)) {
			trigger_error("[MiniPavi-Main] NEWCNX ".$inCnx->getTypeSocket());
		} else {
			trigger_error("[MiniPavi-Main] Erreur acceptation de la connexion");
			continue;
		}
		
		if (count($tObjClient)>=$objConfig->maxCnx) {
			trigger_error("[MiniPavi-Main] Max connexions atteint");
			$inCnx->close();
			continue;
		}
		
		$tTmp = explode(':',$inCnx->getClientIp());
		$ip = @$tTmp[0];
		
		
		if ($objConfig->maxCnxByIP>0 && (count($objConfig->tAsterisk)==0 || (count($objConfig->tAsterisk)>0 && $objConfig->tAsterisk['ip']!=$ip)) ) {
			$cip=0;
			foreach($tObjClient as $objMiniPaviC) {
				if ($objMiniPaviC->clientIp == $ip) {
					$cip++;
					if ($cip>=$objConfig->maxCnxByIP) {
						// Trop de connexion depuis cette IP
						trigger_error("[MiniPavi-Main] Max connexions atteint pour IP=".$ip);
						break;
					}
				}
			}
			if ($cip>=$objConfig->maxCnxByIP) {
				$inCnx->close();				
				continue;
			}
			
		}
		
		AbuseIPDB::check($ip,$objConfig->ipDBKey,$indice,$pays,$objConfig->ipBlackList,$objConfig->ipWhiteList);
		trigger_error("[MiniPavi-Main] Vérification IP $ip idx=$indice [$pays]");
		if ($indice>30) {
			trigger_error("[MiniPavi-Main] AbuseIPDB: Connexion refusée");
			$inCnx->close();
			continue;
		}
		
	
		
		$objMiniPavi = new MiniPavi($objConfig->logPath);
		$pin = createPin();
		$objMiniPavi->init($inCnx,$ip,$mainPid,$pin);
		$objMiniPavi->commSockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
		if (!is_array($objMiniPavi->commSockets )) {
			trigger_error("[MiniPavi-Main] Erreur création sockets de communication");
			$inCnx->close();
			continue;
		}
		stream_set_timeout($objMiniPavi->commSockets[0],0,50000);
		stream_set_timeout($objMiniPavi->commSockets[1],0,50000);

		trigger_error("[MiniPavi-Cli] ".date('d/m/Y H:i:s')." CNX  IP=$ip uniqueId=".$objMiniPavi->uniqueId);					
		
		$resType=InCnx::WS_READTYPE_CNX;

		$pid = pcntl_fork();
		
		if ($pid>0) {
			$objMiniPavi->pid = $pid;
			$tObjClient[]=$objMiniPavi;	
			if (count($tObjClientH)>$objConfig->maxHisto) {
				$tObjClientH[0]->deleteLocalRecording($objConfig->recordsPath);
				array_shift($tObjClientH);
			}
			$tObjClientH[]=$objMiniPavi;
			$objMiniPavi->log("Connexion #".count($tObjClient));
		} else if ($pid == 0) {
			$typeProc = TYPE_PROC_SERVICE;
			
			$tCnxParams=array();

			// Réception de l'en-tête.
			if (!$objMiniPavi->inCnx->handShake($tCnxParams)) {
				trigger_error("[MiniPavi-Cli] Erreur handshake. uniqueId=".$objMiniPavi->uniqueId);	
				$inCnx->close();					
				exit;
			}
			$objMiniPavi->sendToMainProc('setdirection',array('direction'=>$objMiniPavi->inCnx->getDirection()));

			if ($objMiniPavi->inCnx->getTypeSocket() == InCnx::WS_WEBSOCKET || $objMiniPavi->inCnx->getTypeSocket() == InCnx::WS_WEBSOCKETSSL ) {
				
				// Websockets 
				
				if (isset($tCnxParams['streamuniqueid'])) {	// Connexion pour lire une session préalablement enregistrée
					$objMiniPavi->sendToMainProc('setdirection',array('direction'=>'--'));
					$datas = $objMiniPavi->getStreamFromRecording($objConfig->recordsPath,$tCnxParams['streamuniqueid']);
					if ($datas !== false) {
						$datas.=MiniPavi::VDT_CUROFF.MiniPavi::VDT_POS.'@A'.MiniPavi::VDT_BGBLUE.' Fin de session'.MiniPavi::VDT_CLRLN;
						$objMiniPavi->inCnx->send($datas,$objMiniPavi);
					}
					$objMiniPavi->inCnx->close();
					exit;
				}
				
				$objMiniPavi->sendToMainProc('setdirection',array('direction'=>$objMiniPavi->inCnx->getDirection()));
				$objMiniPavi->startLocalRecoding($objConfig->recordsPath);

			} else if ($objMiniPavi->inCnx->getTypeSocket() == InCnx::WS_ASTSOCKET) {
				
				// Connexion telnet (Asterisk): on récupère l'entête
				
				if ($objMiniPavi->inCnx->getDirection() == 'IN') {
					

					if ($tCnxParams['headerpce'] == 'PCE 1') {
						trigger_error("[MiniPavi-Cli] Activation PCE demandée. uniqueId=".$objMiniPavi->uniqueId);	
						$objMiniPavi->inCnx->pce->enable(); // Activation de la PCE du Minitel
					}
					
					$objMiniPavi->sendToMainProc('setinfos',array('infos'=>trim($tCnxParams['headercallnum'])));
					$objMiniPavi->sendToMainProc('setdirection',array('direction'=>$objMiniPavi->inCnx->getDirection()));
			
					$objMiniPavi->log("Appel entrant de ".trim($tCnxParams['headercallnum']));
					$objMiniPavi->log("StartURL [".@$tCnxParams['url']."]");
					$objMiniPavi->startLocalRecoding($objConfig->recordsPath);
				
				} else {
					// Il s'agit d'une connexion telnet suite à un appel sortant (connexion à un serveur RTC distant)
					$objMiniPavi->log("Appel sortant vers ".trim($tCnxParams['headercallnum']));
					$objMiniPavi->log("PID req [".trim($tCnxParams['headerinfo'])."]");
					$objMiniPavi->sendToMainProc('setinfos',array('infos'=>trim($tCnxParams['headercallnum']).' '.trim($tCnxParams['headerinfo'])));
					$objMiniPavi->sendToMainProc('setdirection',array('direction'=>$objMiniPavi->inCnx->getDirection()));
					// On se comporte comme un client
					$socketFile = trim(substr($tCnxParams['headerinfo'],4));
					if ($socketFile!='') {
						$objMiniPavi->log("Liaison RTC<->Client");
						AstAMI::linkToCallerProc($objMiniPavi,$socketFile);
					}
					$objMiniPavi->sendToMainProc('setlastaction',array());
					$objMiniPavi->log("Fin connexion RTC");
					$objMiniPavi->inCnx->close();
					exit;
				}
			} else {
				
				// Connexion telnet standard
				
				$objMiniPavi->sendToMainProc('setdirection',array('direction'=>$objMiniPavi->inCnx->getDirection()));
				$objMiniPavi->startLocalRecoding($objConfig->recordsPath);
			}
			
			$objMiniPavi->pid = getmypid();
			pcntl_signal(SIGUSR1, "fatherIsCalling");
			
			trigger_error("[MiniPavi-Cli] Fork OK PID=".$objMiniPavi->pid);
			break;
		} else {
			trigger_error("[MiniPavi-Main] Erreur fork");
			$objMiniPavi->inCnx->close();
		}
	} else if ($retSocket>0 && $socketWeb && in_array($socketWeb,$tCpy,true) ) {

		// Traitement de la connexion visu Web
		// On ne fait que renvoyer des données puis on ferme la connexion

		$visuSocket = stream_socket_accept($socketWeb,2,$ip);
		if ($visuSocket) {
			
			$tTmp = explode(':',$ip);
			$ip = @$tTmp[0];
			

			AbuseIPDB::check($ip,$objConfig->ipDBKey,$indice,$pays,$objConfig->ipBlackList,$objConfig->ipWhiteList);
			if ($indice>50) {
				trigger_error("[MiniPavi-Main] AbuseIPDB: Connexion refusée IP $ip idx=$indice [$pays]");
				fclose($visuSocket);
				continue;
			}

			
			$visuPid = pcntl_fork();
			if ($visuPid<0) {
				trigger_error("[MiniPavi-Main] Erreur fork visu Web");
				fclose($visuSocket);
			} else if ($visuPid==0) {
				// Traitement
				$typeProc = TYPE_PROC_VISUWEB;
				$send = visuWeb($visuSocket,$objMiniPaviM,$objConfig,$tObjClient,$tObjClientH,$tStart,$wssGwUrl,$wsGwUrl,$ip);
				if ($send !== false) {
					@fwrite($visuSocket,$send);
				}
				fclose($visuSocket);
				exit(0);
			} else {
				fclose($visuSocket);
			}
		} else {
			trigger_error("[MiniPavi-Main] Accès visu Web en echec");
		}
	}
} while(true);

// **************************************************************
// **************************************************************
//
// Début traitement individuel de la connexion de l'utilisateur
//
// **************************************************************
// **************************************************************

do {
	
	if ($resType == InCnx::WS_READTYPE_DEC) {		// Deconnexion
		$objMiniPavi->log("InCnx::WS_READTYPE_DEC: exit UID=".$objMiniPavi->uniqueId);
		exit;
	}
	
	if ($resType == InCnx::WS_READTYPE_CNX) {		// Nouvelle connexion
		$objMiniPavi->versionMinitel = '???';
		$vdt=MiniPavi::VDT_PRO1_VER;
		
		switch ($objMiniPavi->inCnx->getTypeSocket()) {
		case InCnx::WS_WEBSOCKET:
			$objMiniPavi->log("***CNX*** WS from $ip UID=".$objMiniPavi->uniqueId);
			//$vdt.=MiniPavi::VDT_PRO2_NOACK_ECRAN.MiniPavi::VDT_PRO2_NOACK_MODEM.MiniPavi::VDT_PRO2_NOACK_PRISE.MiniPavi::VDT_PRO3_ECHO_OFF.MiniPavi::VDT_CLR.MiniPavi::VDT_G1.MiniPavi::VDT_POS.'@A'.'MiniPAVI '.PAVI_VER.'WS '.date('d/m/Y H:i').MiniPavi::VDT_CLRLN;
			$vdt.=MiniPavi::VDT_CLR.MiniPavi::VDT_G1.MiniPavi::VDT_POS.'@A'.'MiniPAVI '.PAVI_VER.'WS '.date('d/m/Y H:i').MiniPavi::VDT_CLRLN;			
			break;
		case InCnx::WS_WEBSOCKETSSL:
			$objMiniPavi->log("***CNX*** WSS from $ip UID=".$objMiniPavi->uniqueId);
			//$vdt.=MiniPavi::VDT_PRO2_NOACK_ECRAN.MiniPavi::VDT_PRO2_NOACK_MODEM.MiniPavi::VDT_PRO2_NOACK_PRISE.MiniPavi::VDT_PRO3_ECHO_OFF.MiniPavi::VDT_CLR.MiniPavi::VDT_G1.MiniPavi::VDT_POS.'@A'.'MiniPAVI '.PAVI_VER.'WSS '.date('d/m/Y H:i').MiniPavi::VDT_CLRLN;
			$vdt.=MiniPavi::VDT_CLR.MiniPavi::VDT_G1.MiniPavi::VDT_POS.'@A'.'MiniPAVI '.PAVI_VER.'WSS '.date('d/m/Y H:i').MiniPavi::VDT_CLRLN;						
			break;
		case InCnx::WS_TELNSOCKET:
			$objMiniPavi->log("***CNX*** TELN from $ip UID=".$objMiniPavi->uniqueId);
			//$vdt.=MiniPavi::VDT_CLR.MiniPavi::VDT_G1.MiniPavi::VDT_POS.'@A'.'MiniPAVI '.PAVI_VER.'TELN '.date('d/m/Y H:i').MiniPavi::VDT_CLRLN;			
			//$vdt.=MiniPavi::VDT_CLR.MiniPavi::VDT_POS.'@A'.'MiniPAVI '.PAVI_VER.'TELN '.date('d/m/Y H:i').MiniPavi::VDT_CLRLN;			
			$vdt.=MiniPavi::VDT_PRO3_ECHO_OFF.MiniPavi::VDT_CLR.MiniPavi::VDT_POS.'@A'.'MiniPAVI '.PAVI_VER.'TELN '.date('d/m/Y H:i').MiniPavi::VDT_CLRLN;			
			break;
		default:
			if ($objMiniPavi->inCnx->pce->enabled) {
				$objMiniPavi->log("***CNX*** RTC(PCE) from $ip UID=".$objMiniPavi->uniqueId);
				$vdt.=MiniPavi::VDT_PRO2_NOACK_CLAVIER.MiniPavi::VDT_PRO2_NOACK_ECRAN.MiniPavi::VDT_PRO2_NOACK_MODEM.MiniPavi::VDT_PRO2_NOACK_PRISE.MiniPavi::VDT_CLR.MiniPavi::VDT_G1.MiniPavi::VDT_POS.'@A'.'MiniPAVI '.PAVI_VER.'PCE '.date('d/m/Y H:i').MiniPavi::VDT_CLRLN;
			} else {
				$objMiniPavi->log("***CNX*** RTC from $ip UID=".$objMiniPavi->uniqueId);
				$vdt.=MiniPavi::VDT_PRO2_NOACK_CLAVIER.MiniPavi::VDT_PRO2_NOACK_ECRAN.MiniPavi::VDT_PRO2_NOACK_MODEM.MiniPavi::VDT_PRO2_NOACK_PRISE.MiniPavi::VDT_CLR.MiniPavi::VDT_G1.MiniPavi::VDT_POS.'@A'.'MiniPAVI '.PAVI_VER.'RTC '.date('d/m/Y H:i').MiniPavi::VDT_CLRLN;
			}
		}
		
		$vdt.="\x1B@\x14PIN:".substr($objMiniPavi->uniqueId,-4)."\x14\n";
		
		trigger_error("[MiniPavi-Cli] Nouvelle connexion ".$ip);
		
		if (count($tCnxParams) == 0 || !isset($tCnxParams['url']) || trim($tCnxParams['url']=='')) {
			$objMiniPavi->log("Pas d'url, passage au service par défaut UID=".$objMiniPavi->uniqueId);
			$objMiniPavi->url=$objConfig->defaultUrl;
			$objMiniPavi->fromDefault=true;
		} else {
			$objMiniPavi->url=filter_var($tCnxParams['url'], FILTER_VALIDATE_URL);
			if (!$objMiniPavi->url) {
				$objMiniPavi->log("Url incorrecte ".$tCnxParams['url']." UID=".$objMiniPavi->uniqueId);
				$objMiniPavi->addToBufferOut($vdt);
				$objMiniPavi->addToBufferOut($objMiniPavi->showError(2,'Paramètre url incorrect','url:'.$tCnxParams['url']));
				$outDatas = $objMiniPavi->prepareSendToUser();
				$objMiniPavi->inCnx->send($outDatas,$objMiniPavi);
				trigger_error("[MiniPavi-Cli] Paramètre url incorrect");
				exit;
			}
		}
		$objMiniPavi->log("Start url ".$objMiniPavi->url." UID=".$objMiniPavi->uniqueId);
		$objMiniPavi->echo=true;
		$objMiniPavi->addToBufferOut($vdt);
		$fctn='CNX';
	} else {	// $resType == InCnx::WS_READTYPE_DATAS
		$objMiniPavi->receiveFromUser($datas,$fctn,$simulate);
		if ($fctn=='' && $objMiniPavi->bufferOut!='') {
			$outDatas = $objMiniPavi->prepareSendToUser(false);
			$objMiniPavi->inCnx->send($outDatas,$objMiniPavi);
		} else if ($fctn!='') {
			$objMiniPavi->log("Données de l'utilisateur Fctn=$fctn UID=".$objMiniPavi->uniqueId);
			trigger_error("[MiniPavi-Cli] FCTN=[$fctn] URL=[".$objMiniPavi->url."]");
			$objMiniPavi->tLastAction=time();
			$objMiniPavi->tLastData=time();
			$objMiniPavi->sendToMainProc('setlastaction',array());
			$objMiniPavi->tSimulateUser = array();		// Suppression d'une éventuelle simulation utilisateur à venir
		}
	}
	
	if ($fctn !='') {
		do {
			if ($fctn=='FIN') {
				// Deconnexion
				if ($objMiniPavi->fromDefault && strpos($objMiniPavi->url,$objConfig->defaultUrl)===false)  {
					// L'utilisateur n'est pas sur le service par défaut -> retourne au service par défaut
					$objMiniPavi->sendToService('FIN');
					$objMiniPavi->log("Envoi DECO au service UID=".$objMiniPavi->uniqueId);
					$objMiniPavi->processResponseFromService($objConfig->tAsterisk,$objConfig,true);							
					$objMiniPavi->url = $objConfig->defaultUrl;
					$objMiniPavi->log("Passage au service par défaut UID=".$objMiniPavi->uniqueId);
					$objMiniPavi->sendToService('CNX');
				} else {
					exit;
				}
			} else {
				$resServ = $objMiniPavi->sendToService($fctn);
				$objMiniPavi->log("Envoi requête au service UID=".$objMiniPavi->uniqueId);
				if (!$resServ && $objMiniPavi->fromDefault) {
					$objMiniPavi->url = $objConfig->defaultUrl;
					$objMiniPavi->log("Requête échouée, passage au service par défaut UID=".$objMiniPavi->uniqueId);
					trigger_error("[MiniPavi-Cli] Service indisponible");
					$resServ = $objMiniPavi->sendToService('CNX');
				}
				if (!$resServ) {
					$objMiniPavi->addToBufferOut($objMiniPavi->showError(9,'Service indisponible','url:'.$objMiniPavi->url));
					$outDatas = $objMiniPavi->prepareSendToUser();
					$objMiniPavi->inCnx->send($outDatas,$objMiniPavi);
					trigger_error("[MiniPavi-Cli] Service indisponible");
					$objMiniPavi->log("Requête échouée, fermeture connexion UID=".$objMiniPavi->uniqueId);
					exit;
				}
			}
			
			$r=$objMiniPavi->processResponseFromService($objConfig->tAsterisk,$objConfig);							
			$outDatas = $objMiniPavi->prepareSendToUser();
			if ($objMiniPavi->inCnx->getTypeSocket() == InCnx::WS_ASTSOCKET && $resType == InCnx::WS_READTYPE_CNX)
				sleep(5);
			
			$objMiniPavi->log("Envoi réponse à l'utilisateur UID=".$objMiniPavi->uniqueId);
			$objMiniPavi->inCnx->send($outDatas,$objMiniPavi);
			if ($r>0) {
				switch($r) {
				case 1:
					$fctn='DIRECT';				// Appel direct simple
					break;
				case 2:
					$fctn='DIRECTCNX';			// Appel direct equivalent à une connexion
					break;
				case 3:
					$fctn='DIRECTCALLFAILED';	// Appel direct après appel VoIP en échec
					break;
				case 4:
					$fctn='DIRECTCALLENDED';	// Appel direct après appel VoIP terminé avec succès
					break;
				case 99:
					$fctn='FIN';				// Demande de deconnexion
					break;
				default:
					$fctn='DIRECT';
				}
			}
			
		} while($r);
		$objMiniPavi->directCallNum=0;
	}
	
	// On attend des données ou une deconnexion
	$simulate = false;
	do {
		$tTmp = array($objMiniPavi->inCnx->getSocket());
		if ($objMiniPavi->inCnx->getTypeSocket() == InCnx::WS_ASTSOCKET) 
			$to = 50000;
		else $to = 3000000;
		
		$retSocket=@stream_select($tTmp, $null, $null, 0, $to);

		if (time() - $objMiniPavi->tLastAction > $objConfig->timeout) {
			$resType=InCnx::WS_READTYPE_DEC;
			trigger_error("[MiniPavi-Cli] DECO 3 Timeout Pid ".getmypid()." uniqueId=".$objMiniPavi->uniqueId);
			$objMiniPavi->inCnx->send("\x1F@A".$objMiniPavi->toG2("Deconnexion pour inactivité...")."\x18",$objMiniPavi);
			break;
		}

		if ($retSocket==0) {
			
			if (!$objMiniPavi->inCnx->checkPingStatus()) {
				// Pas de réponbse ua ping (WS)
				$resType=InCnx::WS_READTYPE_DEC;
				trigger_error("[MiniPavi-Cli] DECO 4 Pid ".getmypid());
				break;
			}
			
			$objMiniPavi->inCnx->sendPing();
			
			if (count($objMiniPavi->tSimulateUser)>0 && $objMiniPavi->tSimulateUser['time'] <= time()) {
				// Simulation d'une saisie utilisateur
				trigger_error("[MiniPavi-Cli] Simulation utilisateur temporisée [".$objMiniPavi->tSimulateUser['datas'].']');
				$datas = $objMiniPavi->tSimulateUser['datas'];
				$resType=InCnx::WS_READTYPE_DATAS;
				$simulate = true;
				$objMiniPavi->tSimulateUser=array();
				$objMiniPavi->tLastData=time();
				$objMiniPavi->screenSaverStop();
				break;
			}
			
			//if ( $objMiniPavi->versionMinitel!='' &&  $objMiniPavi->versionMinitel!='EmU' && $objConfig->screenSaver && (time() - $objMiniPavi->tLastData) > 120 && (time() - $objMiniPavi->tLastWakeUp) > 5) {
			if ( $objMiniPavi->versionMinitel!='' &&  $objMiniPavi->versionMinitel!='EmU' && $objConfig->screenSaver && (time() - $objMiniPavi->tLastData) > 61 && (time() - $objMiniPavi->tLastWakeUp) > 5) {
				// Pour que l'écran du minitel ne s'éteigne pas
				$objMiniPavi->tLastWakeUp = time();
				$objMiniPavi->screenSaverGo();
			}
		}
		
		if ($retSocket!==false && $retSocket>0) {

			$socketData = $objMiniPavi->inCnx->read();
			
			if ($socketData === false) {
				trigger_error("[MiniPavi-Cli] DATAS erreur");
				$resType=InCnx::WS_READTYPE_DEC;
				trigger_error("[MiniPavi-Cli] DECO 1 Pid ".getmypid());
				break;
			} else 	if ($socketData==='' && ($objMiniPavi->inCnx->getTypeSocket() == InCnx::WS_WEBSOCKET || $objMiniPavi->inCnx->getTypeSocket() == InCnx::WS_WEBSOCKETSSL || $objMiniPavi->inCnx->getTypeSocket() == InCnx::WS_TELNSOCKET ||  ( $objMiniPavi->inCnx->getTypeSocket() == InCnx::WS_ASTSOCKET && !$objMiniPavi->inCnx->pce->enabled ))) {
				//Déconnexion
				$resType=InCnx::WS_READTYPE_DEC;
				trigger_error("[MiniPavi-Cli] DECO 2 Pid ".getmypid()." uniqueId=".$objMiniPavi->uniqueId);
				break;
			} else if ($socketData !== true) {
				$datas = $socketData;
				$resType=InCnx::WS_READTYPE_DATAS;
				if ($datas!='') {
					$objMiniPavi->tLastData=time();
					$objMiniPavi->screenSaverStop();
				}
				break;
			}
		}
	} while(true);
	
} while(true);
