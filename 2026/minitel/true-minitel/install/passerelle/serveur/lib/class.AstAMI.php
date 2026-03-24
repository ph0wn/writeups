<?php
/**
 * @file class.AstAMI.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Mars 2024
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * Appels sortants vers serveurs RTC
 * et liaison avec l'utilisateur 
 * Utilise l'interface AMI Asterisk
 *
 * License GPL v2 ou supérieure
 */

declare(ticks = 1); 
class AstAMI {
	const ASTAMI_MAXTIME_NOUSERRX = 180;	// Durée maximum sans recevoir de données de l'utilisateur
	
	static private function sendStartSeq($tStart,&$count) {
		$t = time()-$tStart;
		
		$tmin = 30 + ($count*5);
		$tmax = $tmin+5;
		
		if ($t>= $tmin && $t<$tmax) {
			$count++;
			return true;
		}
		return false;
	}

	/***************************
	** Amorce un appel sortant pour
	** connexion à un serveur RTC
	** numero: numéro à appeller
	** fromPid: piud du processus ayant demandé l'appel
	** objMiniPaviC: objet MiniPavi de l'utilisateur
	** RX: puissance signal minimum en réception
	** TX: puissance signal en emission
	** Retourne 0 si ok, sinon -1
	****************************/

	static public function startCall($numero,$fromPid,$objMiniPaviC,$RX,$TX,$tConfigAsterisk) {

		if (@$tConfigAsterisk['sipchannel']=='')
			return -1;
		
		$channelStr = str_replace("%NUM%", $numero,$tConfigAsterisk['sipchannel']);
		$errno=0 ;
		$errstr=0 ;
		$dataFromServerCounter = 0;
		$RX = (int)$RX;		// Force minimale du signal en réception
		$TX = (int)$TX;		// Force du signal en emission
		
		// Création socket locale pour communication
		
		trigger_error("[MiniPavi-AMICall] Création socket locale");
		
		$socketFile = $objMiniPaviC->getLocalSocketName();

		$objMiniPaviC->unregisterLocalSocket($socketFile);			// Au cas où
		
		
		$socket = stream_socket_server('unix://'.$socketFile,$errno,$errstr,STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
		if (!$socket) {
			return -1;
		}
		
		trigger_error("[MiniPavi-AMICall] Socket locale créée");
		
		$objMiniPaviC->registerLocalSocket($socketFile,$socket);
		
		stream_set_blocking($socket, true);
		
		
		// Connexion à Asterisk
		
		trigger_error("[MiniPavi-AMICall] Ouverture connexion Asterisk");
		
		$oSocket = fsockopen ($tConfigAsterisk['ip'], $tConfigAsterisk['port'], $errno, $errstr, 20);
		if (!$oSocket) {
			$objMiniPaviC->unregisterLocalSocket($socketFile);
			return -1;
		}
		
		$actionId = time().rand(10000,99999);
		
		// Login AMI
		
		trigger_error("[MiniPavi-AMICall] Login Asterisk");
		
		fputs($oSocket, "Action: login\r\n");
		fputs($oSocket, "Events: on\r\n");
		fputs($oSocket, "Username: ".$tConfigAsterisk['user']."\r\n");
		fputs($oSocket, "Secret: ".$tConfigAsterisk['pwd']."\r\n\r\n");

		$response=array();
		$line = '';
		while($line != "\r\n"){
			$line = fgets($oSocket,128);
			$response[]=trim($line);
		}

		if (array_search('Response: Success',$response) === false) {
			fclose($oSocket);
			$objMiniPaviC->unregisterLocalSocket($socketFile);
			return -1;
		}
		
		// Demande d'appel
		
		trigger_error("[MiniPavi-AMICall] Asterisk appel $numero RX=$RX TX=$TX");
		
		fputs($oSocket, "Action: Originate\r\n");
		fputs($oSocket, "ActionId: $actionId\r\n");
		fputs($oSocket, "Channel: $channelStr\r\n" );
		fputs($oSocket, "Exten: ".$tConfigAsterisk['ext']."\r\n" );	
		fputs($oSocket, "Timeout : ".$tConfigAsterisk['calltimeout']."\r\n");
		fputs($oSocket, "Codecs : alaw,ulaw\r\n");
		fputs($oSocket, "CallerId: MiniPavi\r\n");
		fputs($oSocket, "Context: ".$tConfigAsterisk['context']."\r\n");
		fputs($oSocket, "Priority: 1\r\n");
		fputs($oSocket, "Variable: CALLED=$numero\r\n");		// Variable transmise au module softmodem
		fputs($oSocket, "Variable: PID=$socketFile\r\n");		// Variable transmise au module softmodem
		fputs($oSocket, "Variable: RX=$RX\r\n");				// Variable transmise au module softmodem
		fputs($oSocket, "Variable: TX=$TX\r\n");				// Variable transmise au module softmodem
		fputs($oSocket, "\r\n");

		$response=array();
		$line = '';
		$t=time();
		while($line != "\r\n" && $t+($tConfigAsterisk['calltimeout']/1000) > time()){
			$line = fgets($oSocket,128);
			$response[]=trim($line);
		}

		if (array_search('Response: Success',$response) === false) {
			fclose($oSocket);
			$objMiniPaviC->unregisterLocalSocket($socketFile);
			return -1;
		}
		
		trigger_error("[MiniPavi-AMICall] Asterisk attente nom channel");
		
		$time = time();
		$newChannel = null;
		
		do {
		
			$response=array();
			$line = '';
			
			while($line != "\r\n"){
				$line = fgets($oSocket,128);
				$response[]=trim($line);
			}
							  
			if (array_search('Event: Newchannel',$response)!==false) {
				trigger_error("[MiniPavi-AMICall] Asterisk Event Newchannel trouvé");
				foreach($response as $k=>$v) {
					if (substr($v,0,9) == 'Channel: ') {
						trigger_error("[MiniPavi-AMICall] Asterisk channel trouvé");
						$newChannel = trim(substr($v,9));
						break 2;
					}
				}
			}
		} while($time+5 > time());
		
		fclose($oSocket);
		
		if ($newChannel == null) {
			trigger_error("[MiniPavi-AMICall] Asterisk pas de nom de channel");
			
			$objMiniPaviC->unregisterLocalSocket($socketFile);
			return -1;
		}
		
		$objMiniPaviC->extCallChannel = $newChannel;
		trigger_error("[MiniPavi-AMICall] Channel = $newChannel");
		
		$stop = false;
		
		trigger_error("[MiniPavi-AMICall] Appel effectué - En attente de connexion");

		$errCount = 0;
		while(true) {
			$tRead[0]=$socket;
			$r=stream_select($tRead, $null, $null, 30, 0);
			if ($r===0) {
				trigger_error("[MiniPavi-AMICall] Connexion time out");
				$objMiniPaviC->unregisterLocalSocket($socketFile);
				return -1;
			} else if ($r === false) {
				$errCount++;
				if ($errCount>10) {
					trigger_error("[MiniPavi-AMICall] Erreur attente connexion");
					$objMiniPaviC->unregisterLocalSocket($socketFile);
					return -1;
				}					
			} else {
				break;
			}
		} 
		
		$socket = stream_socket_accept($socket,5);
		
		trigger_error("[MiniPavi-AMICall] Connexion acceptée");
		
		$userBuff='';
		$serverBuff='';
		$outDatasTmp='';
		
		$tStart = time();
		$tLastRx = time();
		$errCount = 0;
		$startSeqCount = 0;
		
		do  {
			$tRead[0] = $socket;		// Reception des données du serveur RTC depuis socket Unix
			$tRead[1] = $objMiniPaviC->inCnx->getSocket();					// Reception des données de l'utlisateur
			
			
			if ( $objMiniPaviC->inCnx->getTypeSocket() == InCnx::WS_ASTSOCKET && $objMiniPaviC->inCnx->pce->enabled) {
				$to = 50000;			// Le TO est court si l'utilisateur est en PCE, pour l'envoi des blocs
			} else {
				if ($outDatasTmp=='') $to = 2000000;
				else $to = 250000;
			}			
			
			
			$retSocket=stream_select($tRead, $null, $null, 0, $to);
			
			if ($retSocket!==false && $retSocket>0) {
				$errCount=0;
				foreach($tRead as $k=>$sock) {
					
					if ($sock == $socket) {
						trigger_error("[MiniPavi-AMICall] Reception depuis SERVEUR");
						$socketData = fread($sock, 1024);	// Données reçu dur serveur RTC
						$dataFromServerCounter+=strlen($socketData);
						if ($socketData === '')
							$socketData=false;
					} else {								// Données reçues de l'utilisateur...
						$socketData = $objMiniPaviC->inCnx->read();
					}
					
					if ($socketData === false) {
						// Erreur..Deconnexion
						$stop = true;
					} else {
						if ($sock == $socket) {	// Données reçues du serveur RTC
							trigger_error("[MiniPavi-AMICall] RX depuis RTC - Envoi -> Utilisateur");
							$serverBuff.= preg_replace('/[\x00-\x1F\x7F]/u', '', $socketData); 
							
							// Traitement des demandes WebMedia
							$objMiniPaviC->objWebMedia->parseDatas($socketData);
							$objMiniPaviC->objWebMedia->sendRequestsToMain($objMiniPaviC);
							
							if (str_contains($serverBuff,'#*#GETIP*#*')) {
								// Le serveur demande l'IP de l'utilisateur
								trigger_error("[MiniPavi-AMICall] >>>>>   Sequence GETIP recue   >>>>>>");
								$serverBuff = str_replace('#*#GETIP*#*','',$serverBuff);
								if (@fwrite($socket,'#*#'.trim($objMiniPaviC->clientIp).'*#*'."\x13".'A') === false)	{ 
									$stop =true;
								}
							}
							
							
							if (strlen($serverBuff)>20)
								$serverBuff = substr($serverBuff,-20);
							
							$outDatasTmp.=$socketData;		// Tant que l'on reçoit du serveur, on stock avant d'envoyer plus tard à l'utilisateur
							
							if (strlen($outDatasTmp)>=25) {
								$objMiniPaviC->addToBufferOut($outDatasTmp);
								$outDatasTmp = $objMiniPaviC->prepareSendToUser();
								$objMiniPaviC->inCnx->send($outDatasTmp,$objMiniPaviC); // Envoi vers l'utilisateur
								$outDatasTmp='';
							}
							
						} else {	// Données reçues de l'utilisateur
							trigger_error("[MiniPavi-AMICall] RX depuis Utilisateur");
							$objMiniPaviC->sendToMainProc('setlastaction',array());
							$tLastRx = time();
							$userBuff.=$socketData;
							if (str_contains($userBuff,'***'."\x13\x46") || str_contains($userBuff,"\x13\x49") ) {
								$stop =true;
							}
							
							if (strlen($userBuff)>5)
								$userBuff = substr($userBuff,-5);
							
							if (@fwrite($socket,$socketData) === false)	{ // Envoi vers le serveur RTC
								$stop =true;
							}
							
						}
					}
				}
			} else if ($retSocket!==false && $retSocket == 0) {	// Timeout
				$errCount=0;
				if ($outDatasTmp!='') {
					$objMiniPaviC->addToBufferOut($outDatasTmp);
					$outDatasTmp = $objMiniPaviC->prepareSendToUser();
					$objMiniPaviC->inCnx->send($outDatasTmp,$objMiniPaviC); // Envoi vers l'utilisateur
					$outDatasTmp='';
				}
				
				
				if ($dataFromServerCounter<200 && self::sendStartSeq($tStart,$startSeqCount)) {
					trigger_error("[MiniPavi-AMICall] Aucune données reçues => Envoi séquence 1/3 5/3 ($startSeqCount)");
					fwrite($socket,"\x13\x53");
				}
				
				
				// Limitation de la durée de connexion
				if ($dataFromServerCounter<200 && $tStart+60<time()) {
					// Aucune (ou peu) de données recues durant les premières secondes
					// Peut-être problème de connexion : on stop
					trigger_error("[MiniPavi-AMICall] Aucune données reçues 60s - Stop");
					$stop = true;
				}
				
				if ($tLastRx+self::ASTAMI_MAXTIME_NOUSERRX<time()) {
					// durée maximum sans reception données utilisateur
					trigger_error("[MiniPavi-AMICall] Durée max sans RX atteinte - Stop");
					$stop = true;
				}
				
				if ($tStart+$tConfigAsterisk['maxtime']<time()) {
					// durée maximum de la connexion atteinte
					trigger_error("[MiniPavi-AMICall] Durée max autorisée atteinte - Stop");
					$stop = true;
					
				}
			} else {
				// Erreur..Deconnexion
				$errCount++;
				if ($errCount>10) {
					trigger_error("[MiniPavi-AMICall] Erreur 2");
					$stop = true;
				}
			}
		} while (!$stop); 
		trigger_error("[MiniPavi-AMICall] Fin de connexion");
		
		
		$objMiniPaviC->unregisterLocalSocket($socketFile);

		self::endCall($objMiniPaviC->extCallChannel,$tConfigAsterisk);
		$objMiniPaviC->extCallChannel = null;
		return 0;
	}
	
	
	/***************************
	** Raccroche un appel
	** channel: channel Asterisk à raccrocher
	** Retourne 0 si ok, sinon -1
	****************************/
	
	static public function endCall($channel,$tConfigAsterisk) {
		if (!$channel)
			return;
		trigger_error("[MiniPavi-AMICall] Raccrochage channel ".$channel);
		$errno=0 ;
		$errstr=0 ;
		
		// Connexion à Asterisk
		$oSocket = fsockopen ($tConfigAsterisk['ip'], $tConfigAsterisk['port'], $errno, $errstr, 20);
		if (!$oSocket) {
			trigger_error("[MiniPavi-AMICall] Raccrochage channel NOK");
			return -1;
		}

		// Login
		
		fputs($oSocket, "Action: login\r\n");
		fputs($oSocket, "Events: on\r\n");
		fputs($oSocket, "Username: ".$tConfigAsterisk['user']."\r\n");
		fputs($oSocket, "Secret: ".$tConfigAsterisk['pwd']."\r\n\r\n");

		$response=array();
		$line = '';
		while($line != "\r\n"){
			$line = fgets($oSocket,128);
			$response[]=trim($line);
		}

		if (array_search('Response: Success',$response) === false) {
			fclose($oSocket);
			trigger_error("[MiniPavi-AMICall] Raccrochage channel NOK");
			return -1;
		}
		
		// Hangup
		
		fputs($oSocket, "Action: Hangup\r\n");
		fputs($oSocket, "Channel: $channel\r\n");
		fputs($oSocket, "\r\n");

		$response=array();
		$line = '';
		while($line != "\r\n"){
			$line = fgets($oSocket,128);
			$response[]=trim($line);
		}

		if (array_search('Response: Success',$response) === false) {
			fclose($oSocket);
			trigger_error("[MiniPavi-AMICall] Raccrochage channel NOK");
			return -1;
		}

		
		fclose($oSocket);
		trigger_error("[MiniPavi-AMICall] Raccrochage channel OK ");
		return 0;
	}

	
	
	/***************************
	** Fait le lien entre l'appel sortant (qui a créé une connexion telnet entrante)
	** et l'utilisateur de MiniPavi ayant initié l'appel.
	** objMiniPaviC: objet MiniPavi représentant la connexion telnet entrante suite à l'appel sortant
	** socketFile: socket Unix pour communication avec processus ayant initié la demande
	** Retourne 'true', ou 'false' si erreur
	****************************/
	
	static public function linkToCallerProc($objMiniPaviC,$socketFile) {
		trigger_error("[MiniPavi-AMILink] Essai connexion socket UNIX");
		$socket = stream_socket_client('unix://'.$socketFile, $errno, $errstr, 5);
		if (!$socket) 
			return false;
		stream_set_blocking($socket, true);
		trigger_error("[MiniPavi-AMILink] Connexion socket '".$socketFile."' UNIX ok");
		
		$bufferIn='';
		$stop = false;
		$errCount = 0;
		
		do  {
			$tRead[0] = $socket;	// Socket locale
			$tRead[1] = $objMiniPaviC->inCnx->getSocket();	// Socket externe
			$retSocket=stream_select($tRead, $null, $null, 0, 250000);
			if ($retSocket!==false && $retSocket>0) {
				$errCount = 0;
				foreach($tRead as $k=>$sock) {
					$socketData = fread($sock, 1024);
					
					if ($socketData === false || $socketData === '') {
						// Erreur..Deconnexion
						if ($socketData === false)
							trigger_error("[MiniPavi-AMILink] Erreur 1 FALSE");
						else
							trigger_error("[MiniPavi-AMILink] Erreur 1 VIDE");
						$stop = true;
					} else {
						if ($sock == $objMiniPaviC->inCnx->getSocket()) { // Données reçues du serveur RTC
							$bufferIn.=$socketData;	// Envoi vers le client, par blocs
							if (strlen($bufferIn)>5) {
								trigger_error("[MiniPavi-AMILink] Envoi buffer -> Client");
								if (@fwrite($socket,$bufferIn) === false)	{ // Envoi vers le client
									$stop =true;
									trigger_error("[MiniPavi-AMILink] Erreur 3");
								}
								$bufferIn = '';
							}
						} else {	// Données reçues du client
							trigger_error("[MiniPavi-AMI] RX depuis UNIX socket - Envoi -> Serveur");
							if (@fwrite($objMiniPaviC->inCnx->getSocket(),$socketData) === false) { 	// Envoi vers le serveur
								$stop = true;
								trigger_error("[MiniPavi-AMILink] Erreur 4");
							}
						}
					}
				}
			} else if ($retSocket!==false && $retSocket == 0) {	// Timeout
				$errCount = 0;
				if ($bufferIn!=='') {
					trigger_error("[MiniPavi-AMILink] Envoi rest buffer -> Client");
					if (@fwrite($socket,$bufferIn) === false) {	// Il reste de données : envoi vers le client
						$stop =true;
						trigger_error("[MiniPavi-AMILink] Erreur 5");
					}
					$bufferIn = '';
				}
			} else {
				// Erreur..Deconnexion
				$errCount++;
				if ($errCount>10) {
					trigger_error("[MiniPavi-AMILink] Erreur 2");
					$stop = true;
				}
			}
		} while (!$stop); 
		
		@fclose($socket);
		return true;
	}
}