<?php
/**
 * @file class.WSConnect.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Mars 2024
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * Liaison avec un serveur via connexion Websocket
 *
 * License GPL v2 ou supérieure
 *
 **/


declare(ticks = 1); 
class WSConnect {
	
	const WSCNX_MAXTIME = 1800;	// Durée maximum d'une connexion
	const WSCNX_MAXTIME_NOUSERRX = 180;	// Durée maximum sans recevoir de données de l'utilisateur
	
	/*************************************************
	// Effectue le pont entre le serveur WS indiqué et l'utilisateur
	// myHost : adresse client (moi)
	// host: adresse du serveur
	// Retourne 0 si ok, sinon -1 
	*************************************************/
	
	static public function linkTo($myHost,$host,$objMiniPaviC,$path='/',$echo='off',$case='lower',$proto='') {
		$serverSocket = @stream_socket_client($host, $errno, $errstr, 5,STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT);
		if (!$serverSocket) {
			trigger_error("[MiniPavi-WSConnect] Connexion à $host [$path] en echec [$errno $errstr]");
			return -1;
		}
		stream_set_blocking($serverSocket, true);
		stream_set_timeout($serverSocket,1, 500000);
		trigger_error("[MiniPavi-WSConnect] Connexion à $host [$path] OK");

		$elemUrl=parse_url($host);

		// Requête demande websocket
		
		$key = base64_encode(openssl_random_pseudo_bytes(16));
	
		$tHeaders = array(
		'Host'=>@$elemUrl['host'],
		'User-Agent'=>'MiniPavi',		
		'Connection'=>'keep-alive, Upgrade',		
		'Upgrade'=>'websocket',
		'Sec-WebSocket-Key'=>$key,
		'Sec-WebSocket-Version'=>'13',
		'Origin'=>$myHost,
		'Sec-Fetch-Dest'=>'empty',
		'Sec-Fetch-Mode'=>'websocket',
		'Sec-Fetch-Site'=>'cross-site',
		'Pragma'=>'no-cache',
		'Cache-Control'=>'no-cache',
		'Accept'=>'*/*'
		);

		if ($proto!='') {
			$tHeaders['Sec-WebSocket-Protocol']=$proto;	
		}
		
		$headerString = "GET ".$path." HTTP/1.1\r\n";
		foreach($tHeaders as $p=>$h) {
			$headerString.=$p.': '.$h."\r\n";
		}
		$headerString.="\r\n";

		
		$rc = fwrite($serverSocket, $headerString);
		if (!$rc) {
			return -1;
		}

		$responseHeader = fread($serverSocket, 1024);

		// Demande acceptée ?

		if (empty($responseHeader) || stripos($responseHeader, 'HTTP/1.1 101')===false || stripos($responseHeader, 'Sec-Websocket-Accept')===false) {
			trigger_error("[MiniPavi-WSConnect] Erreur header [$responseHeader]");	
			return -1;
		}
		// On devrait vérifier ici la valeur de Sec-WebSocket-Accept
		
		$userBuff='';
		$serverBuff='';
		$outDatasTmp='';
		
		$tStart = time();
		$tLastRx = time();
		$stop = false;
		$dataFromServerCounter = 0;
		$sepChar = false;
		$dataBuff = '';
		
		if ($case=='lower') {
			$objMiniPaviC->inCnx->send(MiniPavi::VDT_PRO2_MINUSCULES_ON,$objMiniPaviC);
		} else {
			$objMiniPaviC->inCnx->send(MiniPavi::VDT_PRO2_MINUSCULES_OFF,$objMiniPaviC);
		}
		
		$errCount = 0;
		do  {
			
			if ($outDatasTmp!='') {
				// Il y'a des données en attente à envoyer à l'utilisateur
				$objMiniPaviC->addToBufferOut($outDatasTmp);
				$outDatasTmp = $objMiniPaviC->prepareSendToUser();
				$objMiniPaviC->inCnx->send($outDatasTmp,$objMiniPaviC);
				$outDatasTmp='';
			}

			$tRead[0] = $serverSocket;		// Socket des données du serveur distant WS
			$tRead[1] = $objMiniPaviC->inCnx->getSocket();					// Reception des données de l'utlisateur 
			
			if ($objMiniPaviC->inCnx->getTypeSocket() == InCnx::WS_ASTSOCKET && $objMiniPaviC->inCnx->pce->enabled) {
				$to = 50000;			// Le TO est court si l'utilisateur est en PCE, pour l'envoi des blocs
			} else {
				if ($outDatasTmp=='') $to = 2000000;
				else $to = 250000;
			}
			
			$retSocket=stream_select($tRead, $null, $null, 0, $to);
			
			if ($retSocket!==false && $retSocket>0) {
				$errCount = 0;
				foreach($tRead as $k=>$sock) {
					trigger_error("[MiniPavi-WSConnect] Lecture socket");
					$retWS=true;
					if ($sock == $serverSocket) {
						trigger_error("[MiniPavi-WSConnect] Reception serveur");
						$retWS = self::read($sock,$socketData,$dataBuff);		// Données reçu du serveur WS
						$dataFromServerCounter+=strlen($socketData);
						if ($retWS === false) {
							trigger_error("[MiniPavi-WSConnect] RX erreur serveur");							
						}
					} else {								// Données reçues de l'utilisateur...
						$objMiniPaviC->sendToMainProc('setlastaction',array());
						$tLastRx = time();
						$socketData = $objMiniPaviC->inCnx->read();
						if ($socketData===false) {
							trigger_error("[MiniPavi-WSConnect] RX erreur client");
							$retWS=false;
						}
					}
					
					if ($retWS === false) {
						// Erreur..Deconnexion
						$stop = true;
					} else {
						// Traitement des données reçues
						if ($sock == $serverSocket) {	// Données reçues du serveur WS
							trigger_error("[MiniPavi-WSConnect] RX depuis WS");
							$serverBuff.=$socketData;
							
							// Traitement des demandes WebMedia
							$objMiniPaviC->objWebMedia->parseDatas($socketData);
							$objMiniPaviC->objWebMedia->sendRequestsToMain($objMiniPaviC);

							if (str_contains($serverBuff,'#*#GETIP*#*')) {
								// Le serveur demande l'IP de l'utilisateur
								$serverBuff = str_replace("#*#GETIP*#*", "", $serverBuff);
								if (@fwrite($serverSocket,'#*#'.trim($objMiniPaviC->clientIp).'*#*') === false)	{ 
									trigger_error("[MiniPavi-WSConnect] Erreur renvoi IP");
									$stop =true;
								}
							}
							trigger_error("[MiniPavi-WSConnect] Envoi -> Utilisateur");
							$l=strlen($serverBuff)-20;
							if ($l>0) $serverBuff = substr($userBuff,$l);
							
							$outDatasTmp.=$socketData;		// Tant que l'on reçoit du serveur, on stock (max 25 octets) avant d'envoyer plus tard à l'utilisateur
							
							if (strlen($outDatasTmp)>=25) {
								$objMiniPaviC->addToBufferOut($outDatasTmp);
								$outDatasTmp = $objMiniPaviC->prepareSendToUser();
								$objMiniPaviC->inCnx->send($outDatasTmp,$objMiniPaviC);	// Envoi vers l'utilisateur								
								$outDatasTmp='';
							}
							
						} else {	// Données reçues de l'utilisateur
							trigger_error("[MiniPavi-WSConnect] RX depuis Utilisateur");
							if ($socketData!='') {
								$userBuff.=$socketData;
								if (str_contains($userBuff,'***'."\x13\x46") || str_contains($userBuff,"\x13\x49") ) {
									trigger_error("[MiniPavi-WSConnect] Fin de connexion demandée");
									$stop =true;
								}
								$l=strlen($userBuff)-5;
								if ($l>0) $userBuff = substr($userBuff,$l);
								
								trigger_error("[MiniPavi-WSConnect] Envoi -> Serveur ");
								
								// Certain serveurs n'acceptent pas qu'une touche fonction soit sur 2 frames
								// On attend donc la suite si on reçoit un seul caractère et qu'il s'agit de SEP (0x13)
								
								if (strlen($socketData)==1 && $socketData[0]==chr(0x13)) {
									$sepChar = true;
								} else {
									if ($sepChar) {
										// On avait reçu un SEP juste avant
										$socketData=chr(0x13).$socketData;
										$sepChar=false;
									}
									if (self::write($serverSocket, $socketData) === false ) { // Envoi vers le serveur WS
										trigger_error("[MiniPavi-WSConnect] Erreur envoi vers serveur");
										$stop =true;
									}
									
									if ($echo =='on') {
										$objMiniPaviC->inCnx->send($socketData,$objMiniPaviC);
									}
								}
							}
						}
					}
				}
			} else if ($retSocket!==false && $retSocket == 0) {	// Timeout
				$errCount = 0;
				// Limitation de la durée de connexion
				if ($dataFromServerCounter<200 && $tStart+30<time()) {
					// Aucune (ou peu) de données recues durant les premières secondes
					// Peut-être problème de connexion : on stop
					trigger_error("[MiniPavi-WSConnect] Aucune données reçues - Stop");
					$stop = true;
				}
				if ($tLastRx+self::WSCNX_MAXTIME_NOUSERRX<time()) {
					// durée maximum sans reception données utilisateur
					trigger_error("[MiniPavi-WSConnect] Durée max sans RX atteinte - Stop");
					$stop = true;
				}
				if ($tStart+self::WSCNX_MAXTIME<time()) {
					// durée maximum de la connexion atteinte
					trigger_error("[MiniPavi-WSConnect] Durée max autorisée atteinte - Stop");
					$stop = true;
				}
			} else {
				$errCount++;
				if ($errCount>10) {
					// Erreur..Deconnexion
					trigger_error("[MiniPavi-WSConnect] Erreur");
					$stop = true;
				}
			}
		} while (!$stop); 
		trigger_error("[MiniPavi-WSConnect] Fin de connexion");
		fclose($serverSocket);
		return 0;
	}
	

	/*************************************************
	// Envoi de la frame
	// socket: socket de connexion au serveur
	// content: contenu
	// Retourne le résultat de fwrite
	*************************************************/
	
	static private function write($socket, string $content) {
		$frame = WebSocket::encodeFrame($content,0x01,true);
		return fwrite($socket, $frame,strlen($frame));
    }
	

	/*************************************************
	// Lit une frame
	// socket: socket de connexion au serveur
	// data: données reçues
	// dataBuff: données précédemment reçues et non traitées (frame partielle)
	// Retourne false si erreur, sinon true
	*************************************************/
	
	static private function read($socket,&$data,&$dataBuff) {
		$data = fread($socket, 32767);
		if ($data == '') {
			return false;
		}
		$data=WebSocket::decodeFrame($data,$null,$dataBuff,$socket);
		if ($data===true)
			$data='';
		return true;
	}
}