<?php
/**
 * @file class.DuplicateStream.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Mai 2024
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * Visualisation par un utilisateur des données sortantes d'un autre utilisateur
 *
 * License GPL v2 ou supérieure
 *
 */


declare(ticks = 1); 
class DuplicateStream {
	
	/*************************************************
	// Cré une socket locale pour recevoir une copie des données envoyées à un utilisateur 
	// uniqueId: identifiant unique de l'utilisateur pour lequel on duplique les données sortantes
	// objMiniPaviC: objet MiniPavi de l'utilisateur faisant la demande
	// Retourne 0 si ok, sinon -1 
	*************************************************/
	
	static public function linkTo($uniqueid,$objMiniPaviC) {
		
		$socketFile = $objMiniPaviC->getLocalSocketName();
		$objMiniPaviC->unregisterLocalSocket($socketFile);
		
		// Création de la socket
		$socketServ = stream_socket_server('unix://'.$socketFile,$errno,$errstr,STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
		if (!$socketServ) {
			trigger_error("[MiniPavi-Cli] Erreur creation socket pour duplication");
			return -1;
		}
		
		// Envoi du nom de la socket au processus principal
		$objMiniPaviC->registerLocalSocket($socketFile,$socketServ,MiniPavi::MINIPAVI_SOCKETUSAGE_STREAMDUPL_RX);
		$objMiniPaviC->sendToMainProc('duplicateStreamReq',array('uniqueid'=>$uniqueid,'socket'=>$socketFile));
		
		$tCpy=array($socketServ);
		
		$errCount = 0;
		while(true) {
			$retSocket=@stream_select($tCpy, $null, $null, 5, 0);		
			
			if ($retSocket>0 && in_array($socketServ,$tCpy)) {		
				$socket = stream_socket_accept($socketServ,5);
				if (!$socket) {
					trigger_error("[MiniPavi-DuplicateStream] Erreur connexion socket (1)");
					$objMiniPaviC->unregisterLocalSocket($socketFile);
					return -1;
				}
				break;
			} else {
				if ($retSocket===false) 
					$errCount++;
				if ($retSocket===0 || $errCount>5) {
					trigger_error("[MiniPavi-DuplicateStream] Erreur connexion socket (2)");
					$objMiniPaviC->unregisterLocalSocket($socketFile);
					return -1;
				}
			}
		}
		$userBuff='';
		$serverBuff='';
		$outDatasTmp='';
		$stop = false;
		$dataFromServerCounter = 0;
		$errCount=0;
		do  {
			$tRead[0] = $socket;		// Reception des données de la duplication
			$tRead[1] = $objMiniPaviC->inCnx->getSocket();					// Reception des données de l'utlisateur WS
			
			if ($objMiniPaviC->inCnx->getTypeSocket() == InCnx::WS_ASTSOCKET && $objMiniPaviC->inCnx->pce->enabled) {
				$to = 50000;			// Le TO est court si l'utilisateur est en PCE, pour l'envoi des blocs
			} else {
				if ($outDatasTmp=='') $to = 2000000;
				else $to = 250000;
			}
			
			$retSocket=stream_select($tRead, $null, $null, 0, $to);
			
			if ($retSocket!==false && $retSocket>0) {
				$errCount=0;
				foreach($tRead as $k=>$sock) {
					trigger_error("[MiniPavi-DuplicateStream] Lecture socket");
					$ret=true;
					if ($sock == $socket) {
						trigger_error("[MiniPavi-DuplicateStream] Reception duplication");
						$socketData = fread($sock, 131072);	
						$dataFromServerCounter+=strlen($socketData);
						if ($socketData=='' || $socketData===false) {
							$ret=false;
						}
					} else {								// Données reçues de l'utilisateur...
						$objMiniPaviC->sendToMainProc('setlastaction',array());
						trigger_error("[MiniPavi-DuplicateStream] Reception client");
						$socketData = $objMiniPaviC->inCnx->read();
						if ($socketData===false) {
							trigger_error("[MiniPavi-DuplicateStream] RX erreur client");
							$ret = false;
						}
					}
					
					if ($ret == false) {
						// Erreur..Deconnexion
						$stop = true;
					} else {
						if ($sock == $socket) {	// Données reçues de la duplication
							trigger_error("[MiniPavi-DuplicateStream] RX depuis duplication - Envoi -> Utilisateur");

							$serverBuff.=$socketData;
							$l=strlen($serverBuff)-20;
							if ($l>0) $serverBuff = substr($userBuff,$l);
							
							$outDatasTmp.=$socketData;		// Tant que l'on reçoit de la duplication, on stock avant d'envoyer plus tard à l'utilisateur
							
							if (strlen($outDatasTmp)>=25) {
								$objMiniPaviC->addToBufferOut($outDatasTmp);
								$outDatasTmp = $objMiniPaviC->prepareSendToUser();
								$objMiniPaviC->inCnx->send($outDatasTmp,$objMiniPaviC); // Envoi vers l'utilisateur
								$outDatasTmp='';
							}
							
						} else {	// Données reçues de l'utilisateur
							trigger_error("[MiniPavi-DuplicateStream] RX depuis utilisateur");
							$userBuff.=$socketData;
							
							if (str_contains($userBuff,'***'."\x13\x46") || str_contains($userBuff,"\x13\x49")  ) {
								// On stop à la reception de *** + Sommaire ou Cnx/fin
								$stop =true;
							}
							$l=strlen($userBuff)-5;
							if ($l>0) $userBuff = substr($userBuff,$l);
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
			} else {
				$errCount++;
				if ($errCount>10) {
					// Erreur..Deconnexion
					trigger_error("[MiniPavi-DuplicateStream] Erreur");
					$stop = true;
				}
			}
		} while (!$stop); 
		trigger_error("[MiniPavi-DuplicateStream] Fin de connexion");
		@fclose($socket);
		$objMiniPaviC->unregisterLocalSocket($socketFile);
		return 0;
	}
}