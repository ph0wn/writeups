<?php
/**
 * @file class.TelnetConnect.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Mars 2024
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * Liaison avec un serveur via connexion telnet
 *
 * License GPL v2 ou supérieure
 *
 */

declare(ticks = 1); 
class TelnetConnect {
	
	const TLNCNX_MAXTIME = 1800;			// Durée maximum d'une connexion
	const TLNCNX_MAXTIME_NOUSERRX = 180;	// Durée maximum sans recevoir de données de l'utilisateur
	
	/*************************************************
	// Effectue le pont entre le serveur indiqué et l'utilisateur
	// host: adresse du serveur
	// Retourne 0 si ok, sinon -1 
	*************************************************/
	
	static public function linkTo($host,$objMiniPaviC,$echo='off',$case='lower',$startSeq='') {
		$serverSocket = @stream_socket_client('tcp://'.$host, $errno, $errstr, 5);
		if (!$serverSocket) {
			trigger_error("[MiniPavi-TelnetConnect] Connexion à $host en echec");
			return -1;
		}
		stream_set_blocking($serverSocket, true);
		stream_set_timeout($serverSocket,0, 500000);
		trigger_error("[MiniPavi-TelnetConnect] Connexion à $host OK");

		if (strlen($startSeq)>0) {
			trigger_error("[MiniPavi-TelnetConnect] Envoi [$startSeq] ");
			@fwrite($serverSocket,$startSeq);
		}

		$userBuff='';
		$serverBuff='';
		$outDatasTmp='';
		
		$tStart = time();
		$tLastRx = time();
		$stop = false;
		$dataFromServerCounter = 0;
		
		if ($case=='lower') {
			$objMiniPaviC->inCnx->send(MiniPavi::VDT_PRO2_MINUSCULES_ON,$objMiniPaviC);
		} else { 
			$objMiniPaviC->inCnx->send(MiniPavi::VDT_PRO2_MINUSCULES_OFF,$objMiniPaviC);
		}
		
		$errCount = 0;
		do  {
			$tRead[0] = $serverSocket;		// Reception des données du serveur telnet
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
					trigger_error("[MiniPavi-TelnetConnect] Lecture socket");
					$ret=true;
					if ($sock == $serverSocket) {
						trigger_error("[MiniPavi-TelnetConnect] Reception serveur");
						$socketData = fread($sock, 1024);	// Données reçues dur serveur telnet
						$dataFromServerCounter+=strlen($socketData);
						if ($socketData=='' || $socketData===false)
							$ret=false;
					} else {								// Données reçues de l'utilisateur...
						$objMiniPaviC->sendToMainProc('setlastaction',array());
						$tLastRx = time();
						trigger_error("[MiniPavi-TelnetConnect] Reception client");
						$socketData = $objMiniPaviC->inCnx->read();
						if ($socketData===false) {
							trigger_error("[MiniPavi-TelnetConnect] RX erreur client");
							$ret=false;
						}
					}
					
					if ($ret == false) {
						// Erreur..Deconnexion
						$stop = true;
					} else {
						if ($sock == $serverSocket) {	// Données reçues du serveur telnet
							trigger_error("[MiniPavi-TelnetConnect] RX depuis serveur - Envoi -> Utilisateur");

							$serverBuff.=$socketData;
							
							// Traitement des demandes WebMedia
							$objMiniPaviC->objWebMedia->parseDatas($socketData);
							$objMiniPaviC->objWebMedia->sendRequestsToMain($objMiniPaviC);
							
							if (str_contains($serverBuff,'#*#GETIP*#*')) {
								// Le serveur demande l'IP de l'utilisateur
								$serverBuff = str_replace("#*#GETIP*#*", "", $serverBuff);
								if (@fwrite($serverSocket,'#*#'.trim($objMiniPaviC->clientIp).'*#*') === false)	{ 
									$stop =true;
								}
							}
							
							$l=strlen($serverBuff)-20;
							if ($l>0) $serverBuff = substr($userBuff,$l);
							
							
							$outDatasTmp.=$socketData;		// Tant que l'on reçoit du serveur, on stock avant d'envoyer plus tard à l'utilisateur
							
							if (strlen($outDatasTmp)>=25) {
								$objMiniPaviC->addToBufferOut($outDatasTmp);
								$outDatasTmp = $objMiniPaviC->prepareSendToUser();
								$objMiniPaviC->inCnx->send($outDatasTmp,$objMiniPaviC);	// Envoi vers l'utilisateur
								$outDatasTmp='';
							}
							
						} else {	// Données reçues de l'utilisateur
							trigger_error("[MiniPavi-TelnetConnect] RX depuis utilisateur");
							
							$userBuff.=$socketData;
							if (str_contains($userBuff,'***'."\x13\x46") || str_contains($userBuff,"\x13\x49")  ) {
								$stop =true;
							}
							$l=strlen($userBuff)-5;
							if ($l>0) $userBuff = substr($userBuff,$l);
							
							if (@fwrite($serverSocket,$socketData) === false)	{ // Envoi vers le serveur TELNET
								$stop =true;
							}
							if ($echo =='on') {
								$objMiniPaviC->inCnx->send($socketData,$objMiniPaviC);
							}
						}
					}
				}
			} else if ($retSocket!==false && $retSocket == 0) {	// Timeout
				$errCount = 0;
				if ($outDatasTmp!='') {
					$objMiniPaviC->addToBufferOut($outDatasTmp);
					$outDatasTmp = $objMiniPaviC->prepareSendToUser();
					$objMiniPaviC->inCnx->send($outDatasTmp,$objMiniPaviC);
					$outDatasTmp='';
				}
				// Limitation de la durée de connexion
				if ($dataFromServerCounter<200 && $tStart+30<time()) {
					// Aucune (ou peu) de données recues durant les premières secondes
					// Peut-être problème de connexion : on stop
					trigger_error("[MiniPavi-TelnetConnect] Aucune données recues - Stop");
					$stop = true;
				}
				if ($tLastRx+self::TLNCNX_MAXTIME_NOUSERRX<time()) {
					// durée maximum sans reception données utilisateur
					trigger_error("[MiniPavi-TelnetConnect] Durée max sans RX atteinte - Stop");
					$stop = true;
				}
				if ($tStart+self::TLNCNX_MAXTIME<time()) {
					// durée maximum de la connexion atteinte
					trigger_error("[MiniPavi-TelnetConnect] Durée max autorisee atteinte - Stop");
					$stop = true;
				}
			} else {
				$errCount++;
				if ($errCount>10) {
					// Erreur..Deconnexion
					trigger_error("[MiniPavi-TelnetConnect] Erreur");
					$stop = true;
				}
			}
		} while (!$stop); 
		trigger_error("[MiniPavi-TelnetConnect] Fin de connexion");
		
		@fclose($serverSocket);
		return 0;
	}
}