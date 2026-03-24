<?php
/**
 * @file class.WebSocket.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Octobre 2024
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * Envoi et réception sur connexion entrante telnet, websocket et "RTC"
 *
 * License GPL v2 ou supérieure
 *
 **/
abstract class  InCnx {
	const WS_READTYPE_CNX = '0';
	const WS_READTYPE_DEC = '1';
	const WS_READTYPE_DATAS = '2';
	const WS_WEBSOCKET = 'WS';		// Connexion websocket non-sécurisée
	const WS_WEBSOCKETSSL = 'WSS';	// Connexion websocket sécurisée
	const WS_ASTSOCKET = 'AST';		// Connexion tcp type telnet pour Asterisk
	const WS_TELNSOCKET = 'TELN';	// Connexion tcp type telnet

	
	protected $clientIp;
	protected $socket;		
	protected $typeSocket;
	protected $direction;

	abstract  function setServerSocket(... $params) : mixed;	// Configuration de la connexion serveur
	abstract  function accept($socket) : bool;					// Acceptation connexion entrante
	abstract  function close() : void;							// Fermeture connexion
	abstract  function handShake(&$tCnxParams) : bool;			// Handshake
	abstract  function send($datas,$objMiniPavi=null,$noBuffer=false,$tOtherSocket=array(),$tCallable=array()) : void;	// envoi de données
	abstract  function sendPing(): void;						// Envoi d'un ping
	abstract  function checkPingStatus(): bool;					// Verification du status du ping/pong
	abstract  function read(): string|bool;						// Lecture de données

	public function getClientIp() : string {
		return $this->clientIp;
	}

	public function getTypeSocket() : string {
		return $this->typeSocket;
	}
	
	public function getSocket() : mixed {
		return $this->socket;
	}

	public function getDirection() : string {
		return $this->direction;
	}

	public function setDirection($direction) : void {
		$this->direction = $direction;
	}
	

	/*
	Duplication des données envoyées vers une autre Connexion
	et enregistre la ssession
	*/
	
	protected function _send($datas,$objMiniPavi,$noBuffer=false) {
		$tSockets = $objMiniPavi->getLocalSocketByUsage(MiniPavi::MINIPAVI_SOCKETUSAGE_STREAMDUPL_TX);
	
		foreach($tSockets as $k=>$socket) {
			if (@fwrite($socket['socket'], $datas) === false) {		// duplication des données envoyées
				$objMiniPavi->unregisterLocalSocket($socket['name']);
			}
		}
		
		$objMiniPavi->writeToLocalRecording($datas);
		if ($noBuffer == false)
			$objMiniPavi->addToScreenBuffer($datas);
	}
}


/*******************************************
Connexion TCP type telnet
*******************************************/

class InCnxTelnet extends InCnx {

	
	public function __construct() {
		$this->direction = '';
		$this->clientIp = '';
		$this->socket = null;
		$this->typeSocket = self::WS_TELNSOCKET;
	}
	
	public function setServerSocket(... $params) : mixed {
		if (!isset($params['port']))
			return false;
		$port = (int) $params['port'];
		if ($port<1)
			return false;
		$socket = stream_socket_server(
			'tcp://0.0.0.0:'.$port,
			$errno,
			$errstr,
			STREAM_SERVER_BIND|STREAM_SERVER_LISTEN
		);
		trigger_error ("[MiniPavi-Main] TCP Attente connexion port ".$port." ...");
		stream_set_blocking($socket, true);
		return $socket;
	}
	
	public function accept($socket) : bool {
		$this->socket = stream_socket_accept($socket,5,$this->clientIp);
		if (!$this->socket)
			return false;
		stream_set_timeout($this->socket, 60);		
		return true;
	}
	
	public function close() : void {
		if ($this->socket) {
			fclose($this->socket);
			$this->socket = null;
		}
	}
	

	public function handShake(&$tCnxParams) : bool {
		$tCnxParams = array();
		if ($this->socket) {
			stream_set_timeout($this->socket, 1);
			fread($this->socket,8192);	// On vide le buffer, car on reçoit parfois du garbage depuis certains émulateurs telnet à la connexion.
			stream_set_timeout($this->socket, 60);
			$this->direction = 'IN';
			return true;
		}
		return false;
	}
	
	public function send($datas,$objMiniPavi=null,$noBuffer=false,$tOtherInCnx=array(),$tCallable=array()) : void {
		if (!$this->socket) 
			return;
		fwrite($this->socket,$datas);
		foreach($tOtherInCnx as $oInCnx) {
			if ($oInCnx instanceof InCnx) {
				$oInCnx->send($datas);
			}
		}
		foreach($tCallable as $func) {
			call_user_func($func,$datas);
		}
		if ($objMiniPavi) {
			$this->_send($datas,$objMiniPavi,$noBuffer);
		} 
	}
	
	public function read($tCallable=array()) : string|bool{
		if (!$this->socket) 
			return false;
		$datas = @fread($this->socket, 8192);
		if ($datas == '')
			return false;
		foreach($tCallable as $func) {
			call_user_func($func,$datas);
		}
		return $datas;
	}
	
	public function sendPing() : void {
		return;
	}
	
	public function checkPingStatus() : bool {
		return true;
	}
}





/*******************************************
Connexion Websocket sécurisée et non-sécurisée
*******************************************/


class InCnxWS extends InCnx {

	private $serverPort;
	private $lastPing;
	private $pongReceived;
	private $datasBuf;

	
	public function __construct() {
		$this->clientIp = '';
		$this->socket = null;
		$this->typeSocket = '';
		$this->lastPing = -1;
		$this->pongReceived = true;
		$this->datasBuf = '';
		$this->direction = '';
	}
	
	public function setServerSocket(... $params) : mixed {
		if (!isset($params['port']))
			return false;
		$port = (int) $params['port'];
		if ($port<1)
			return false;
		$this->serverPort = $port;
		
		// Si les paramètres sslCert et sslKey sont présents, il s'agit d'une liaison sécurisée
		if (isset($params['sslCert']) && isset($params['sslKey'])) {

			$this->typeSocket = self::WS_WEBSOCKETSSL;
			$transport = 'tlsv1.3';
			$ssl = ['ssl' => [
			  'local_cert'  => $params['sslCert'],       
			  'local_pk'    => $params['sslKey'],    
			  'disable_compression' => true,
			  'verify_peer'         => false,
			  'allow_self_signed'         => true,            
			  'ssltransport' => $transport,
			] ];
			
			$context = stream_context_create($ssl);
			
			$socket = stream_socket_server(
				'tcp://0.0.0.0:'.$port,
				$errno,
				$errstr,
				STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
				$context
			);
		} else {
			$this->typeSocket = self::WS_WEBSOCKET;
			$socket = stream_socket_server(
				'tcp://0.0.0.0:'.$port,
				$errno,
				$errstr,
				STREAM_SERVER_BIND|STREAM_SERVER_LISTEN
			);
		}
		
		if (!$socket) {
			return false;
		}
		
		stream_set_blocking($socket, true);
		return $socket;
	}
	
	public function accept($socket) : bool {
		$this->socket = stream_socket_accept($socket,5,$this->clientIp);
		if (!$this->socket)
			return false;
		stream_set_timeout($this->socket, 60);		
		return true;
	}
	
	public function close() : void {
		if ($this->socket) {
			fclose($this->socket);
			$this->socket = null;
		}
	}
	

	public function handShake(&$tCnxParams) : bool {
		$tCnxParams = array();
		if ($this->socket) {
			stream_set_timeout($this->socket,2);
			$this->direction = 'IN';

			if ($this->typeSocket == self::WS_WEBSOCKETSSL && !stream_socket_enable_crypto($this->socket,true,STREAM_CRYPTO_METHOD_TLSv1_3_SERVER)) {
				trigger_error("[MiniPavi-Cli] Impossible d'amorcer le SSL");					
				exit;
			}

			
			$header=fread($this->socket,8192);
			
			if ($header === false || $header == '') {
				trigger_error("[MiniPavi-Cli] Entete manquante.");					
				return false;
			}
			trigger_error("[MiniPavi-Cli] Entete recue.");					
			// Handshake
			$tCnxParams = WebSocket::handshake($header, $this->socket,'127.0.0.1',$this->serverPort);
			return true;
		}
		return false;
	}
	
	public function send($datas,$objMiniPavi=null,$noBuffer=false,$tOtherInCnx=array(),$tCallable=array()) : void {
		if (!$this->socket) 
			return;
		
		$raw = WebSocket::encodeFrame($datas,0x01,false);
		
		fwrite($this->socket, $raw);
		
		foreach($tOtherInCnx as $oInCnx) {
			if ($oInCnx instanceof InCnx) {
				$oInCnx->send($datas);
			}
		}
		
		foreach($tCallable as $func) {
			call_user_func($func,$datas);
		}
		if ($objMiniPavi) {
			$this->_send($datas,$objMiniPavi,$noBuffer);
		}
		
	}
	
	public function read($tCallable=array()) : string|bool{
		if (!$this->socket) 
			return false;
		$datas = @fread($this->socket, 1024);

		if ($datas == '')
			return false;
		$datas= WebSocket::decodeFrame($datas,$this->pongReceived,$this->datasBuf,$this->socket);
		if ($datas !== true) {
			foreach($tCallable as $func) {
				call_user_func($func,$datas);
			}
		}
		if ($datas == '') return true;
		return $datas;
	}
	
	public function sendPing() : void {
		if (!$this->socket) 
			return;
		if ($this->lastPing + 20 < time()) {
			// envoi d'un Ping au plus toutes les 20 secondes.
			$datas = chr(0x80 | 0x09).chr(0); 
			@fwrite($this->socket, $datas);
			$this->lastPing = time();
			$this->pongReceived = false;
		}
		return;
	}
	
	public function checkPingStatus() : bool {
		if ($this->lastPing + 15 < time() && !$this->pongReceived ) {
			// Le client a 15 secondes pour répondre au ping
			return false;
		}
		return true;
	}
}




/*******************************************
Connexion telnet pour serveur Asterisk
*******************************************/


class InCnxRTC extends InCnx {
	public $clientPhoneNumber;
	public $pce;
	
	
	public function __construct() {
		$this->clientPhoneNumber = '';
		$this->clientIp = '';
		$this->socket = null;
		$this->typeSocket = self::WS_ASTSOCKET;
		$this->pce = null;
		$this->direction = '';
	}
	
	
	public function setServerSocket(... $params) : mixed {
		if (!isset($params['port']))
			return false;
		$port = (int) $params['port'];
		if ($port<1)
			return false;
		$socket = stream_socket_server(
			'tcp://0.0.0.0:'.$port,
			$errno,
			$errstr,
			STREAM_SERVER_BIND|STREAM_SERVER_LISTEN
		);
		trigger_error ("[MiniPavi-Main] TCP Attente connexion port ".$port." ...");
		stream_set_blocking($socket, true);
		return $socket;
	}
	
	
	public function accept($socket) : bool {
		$this->socket = stream_socket_accept($socket,5,$this->clientIp);
		if (!$this->socket)
			return false;
		$this->pce = new PCE($this->socket);
		stream_set_timeout($this->socket, 60);	
		return true;
	}
	
	public function close() : void {
		if ($this->socket) {
			fclose($this->socket);
			$this->socket = null;
		}
		$this->pce = null;
	}
	

	public function handShake(&$tCnxParams) : bool {
		$tCnxParams = array();
		if ($this->socket) {
			stream_set_timeout($this->socket,6);
			$headerCallNum = fgets($this->socket,30);
			$headerInfo = fgets($this->socket,200);
			$headerPce = fgets($this->socket,6);
			stream_set_timeout($this->socket, 60);
							
			if ($headerCallNum === false || (substr($headerCallNum,0,9)!=='CALLFROM ' && substr($headerCallNum,0,7)!=='CALLTO ') )
				return false;
			
			$tCnxParams['headerinfo'] = trim($headerInfo);					
			$tCnxParams['headercallnum'] = trim($headerCallNum);			
			$tCnxParams['headerpce'] = trim($headerPce);			
			
			if (substr($headerCallNum,0,9)=='CALLFROM ') {
				// Il s'agit d'une connexion telnet suite à un appel entrant
				$this->clientPhoneNumber = trim(substr($headerCallNum,9));
				$this->direction = 'IN';
				if ($headerInfo !== false && substr($headerInfo,0,9)=='STARTURL ') {
					$tCnxParams['url'] = trim(substr($headerInfo,9));					
				} 
				register_tick_function(array($this->pce, 'flushBuffer'));
			} else {
				// Il s'agit d'une connexion telnet suite à un appel sortant (connexion à un serveur RTC distant)
				$this->direction = 'OUT';
				$this->clientPhoneNumber = trim(substr($headerCallNum,7));
			}
			return true;
		}
		return false;

	}
	
	public function send($datas,$objMiniPavi=null,$noBuffer=false,$tOtherInCnx=array(),$tCallable=array()) : void {
		if (!$this->socket) 
			return;
		
		if ($this->direction == 'IN') {
			$this->pce->send($datas);
		} else {
			fwrite($this->socket,$datas);
		}
		
		foreach($tOtherInCnx as $oInCnx) {
			if ($oInCnx instanceof InCnx) {
				$oInCnx->send($datas);
			}
		}
		
		foreach($tCallable as $func) {
			call_user_func($func,$datas);
		}
		if ($objMiniPavi) {
			$this->_send($datas,$objMiniPavi,$noBuffer);
		}
		
		
	}
	
	public function read($tCallable=array()) : string|bool{
		if (!$this->socket) 
			return false;
		if ($this->direction == 'IN') {
			$datas = $this->pce->read();
		} else {
			$datas = @fread($this->socket, 8192);
		}
		if ($datas !== null) {
			foreach($tCallable as $func) {
				call_user_func($func,$datas);
			}
			return $datas;
		}
		return false;
	}
	
	public function sendPing() : void {
		return;
	}
	
	public function checkPingStatus() : bool {
		return true;
	}
}
