<?php
/**
 * @file functions.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Avril 2024
 * PHP 8.2 (CLI) version Unix
 *
 * Fonctions diverses
 *
 */
 
 
/**************
*** Traitement si un signal est envoyé par processus principal
*** Utilisé pour la communication entre clients
***************/
function fatherIsCalling($signal)
{
	global $typeProc;
	global $objMiniPavi;
	pcntl_signal(SIGUSR1, "fatherIsCalling");

	if ($typeProc != TYPE_PROC_SERVICE)		
		return;

	$sfTab=fgets($objMiniPavi->commSockets[0]);
	
	$sfTab = @unserialize($sfTab);
	
	
	if (is_array($sfTab) && isset($sfTab['command'])) {
		switch ($sfTab['command']) {
			case 'writeLine0': // recu envoi ligne 0 
				$objMiniPavi->inCnx->send(MiniPavi::VDT_POS.'@A'.$sfTab['text'].MiniPavi::VDT_CLRLN."\n",$objMiniPavi);
				break;				
			case 'simulateUser': // Simulation saisie utilisateur temporisée
				$objMiniPavi->tSimulateUser['time']=$sfTab['time'];
				$objMiniPavi->tSimulateUser['datas']=$sfTab['datas'];
				break;
			case 'duplicateStreamReq': // Demande de duplication des données recues vers socket locale (visualisation)
				$socket = stream_socket_client('unix://'.$sfTab['socket'], $errno, $errstr, 5);
				if (!$socket) {
					trigger_error("[MiniPavi-Cli] Connexion à ".$sfTab['socket']." en echec");
					break;
				}
				$objMiniPavi->registerLocalSocket($sfTab['socket'],$socket,MiniPavi::MINIPAVI_SOCKETUSAGE_STREAMDUPL_TX);
				trigger_error("[MiniPavi-Cli] Duplication activée");
				break;				
			case 'pingwebmedia': 
				$objMiniPavi->objWebMedia->tPing=time();
				//trigger_error("[MiniPavi-Cli] lastevent=".$sfTab['lastevent']);
				if ($sfTab['lastevent']!='') {
					$objMiniPavi->tSimulateUser['time']=time();
					$objMiniPavi->tSimulateUser['datas']='WMLASTEVENT/'.$sfTab['lastevent'];
				}
				break;				
				
		}
	}
	
}

/**************
*** Traitement si un signal est envoyé par l'un des processus service
*** Utilisé pour la communication entre clients
*** et information de l'url du service en cours d'un client
***************/
function childIsCalling($signal,$siginfo)
{
	global $typeProc;
	global $tObjClient;
	global $objMiniPaviM;
	pcntl_signal(SIGUSR2, "childIsCalling");
	
	if ($typeProc != TYPE_PROC_MAIN)
		return;


	while(true) {
		// On vérifie si c'est un signal du process des appels en arrière plan
		$sfTab=fgets($objMiniPaviM->commSockets[1]);
		if ($sfTab === false) {
			// On vérifie si c'est un signal du process d'un service
			foreach($tObjClient as $k=>$obj) {
				if ($obj->commSockets != null && isset($obj->commSockets[1])) {
					$sfTab=fgets($obj->commSockets[1]);
					if ($sfTab !== false) {
						trigger_error("[MiniPavi-Main] childIsCalling => recu depuis SERVICE");
						$senderPid = $obj->pid;
						break;
					}
				}
			}
		} else {
			$senderPid = $objMiniPaviM->pid;
			trigger_error("[MiniPavi-Main] childIsCalling => recu depuis BG");
		}
		
		if ($sfTab === false) {
			return;
		}

		$sfTab = @unserialize($sfTab);
		if (is_array($sfTab) && isset($sfTab['command'])) {
			trigger_error("[MiniPavi-Main] childIsCalling Commande => ".$sfTab['command']);
			switch ($sfTab['command']) {

				case 'simulateUser': 
				foreach($tObjClient as $objMiniPaviC) {
					if ($objMiniPaviC->uniqueId == $sfTab['uniqueId']) {
						if ($objMiniPaviC->commSockets != null && isset($objMiniPaviC->commSockets[1])) {
							// Dans $objCommand->datas il y a les données telles que les auraient tapé l'utilisateur
							@fwrite($objMiniPaviC->commSockets[1], serialize($sfTab)."\n");
							posix_kill($objMiniPaviC->pid,SIGUSR1);
						}
						
						break;						
					}
				}
				break;
				
				case 'addCommandPushServiceMsg': // envoi ligne 0 vers un autre process
				$objCommand = $sfTab['object']; 

				if (count($objCommand->param->uniqueids)>0) {
					foreach($objCommand->param->uniqueids as $idx=>$uniqueId) {
						foreach($tObjClient as $objMiniPaviC) {
							if ($objMiniPaviC->uniqueId == $uniqueId) {
								if ($objMiniPaviC->commSockets != null && isset($objMiniPaviC->commSockets[1])) {								
									$sfTab = array();
									$sfTab['command']='writeLine0';
									$sfTab['text']=@$objCommand->param->message[$idx];
									@fwrite($objMiniPaviC->commSockets[1], serialize($sfTab)."\n");
									posix_kill($objMiniPaviC->pid,SIGUSR1);
								}
								
							}
						}
					}
				}
				break;

				case 'duplicateStreamReq': // Demande de duplication des données sortantes

				if ($sfTab['uniqueid']!='' && $sfTab['socket']!='') {
					foreach($tObjClient as $objMiniPaviC) {
						if ($objMiniPaviC->uniqueId == $sfTab['uniqueid']) {
							if ($objMiniPaviC->commSockets != null && isset($objMiniPaviC->commSockets[1])) {								
								$sfTab2 = array();
								$sfTab2['command']='duplicateStreamReq';
								$sfTab2['reqpid']=$senderPid;
								$sfTab2['socket']=$sfTab['socket'];
								@fwrite($objMiniPaviC->commSockets[1], serialize($sfTab2)."\n");
								posix_kill($objMiniPaviC->pid,SIGUSR1);
							}
							break;
						}
					}
				}
				break;
				
				case 'nexturl': // information de l'url en cours d'un processus
				$url = $sfTab['url']; 
					foreach($tObjClient as $objMiniPaviC) {
						if ($objMiniPaviC->pid == $senderPid) {
							$objMiniPaviC->url = $url;
							$objMiniPaviC->tLastAction = time();
							break;
						}
					}
				break;

				case 'setinfos': // met à jour le champs libre infos
				$infos = $sfTab['infos']; 
					foreach($tObjClient as $objMiniPaviC) {
						if ($objMiniPaviC->pid == $senderPid) {
							$objMiniPaviC->infos = $infos;
							$objMiniPaviC->tLastAction = time();
							break;
						}
					}
				break;
				
				case 'setdirection': // met à jour le champs direction
				$direction = $sfTab['direction']; 
					foreach($tObjClient as $objMiniPaviC) {
						if ($objMiniPaviC->pid == $senderPid) {
							$objMiniPaviC->inCnx->setDirection($direction);
							break;
						}
					}
				break;

				case 'setlastaction': // met à jour le champs tLastAction
					foreach($tObjClient as $objMiniPaviC) {
						if ($objMiniPaviC->pid == $senderPid) {
							$objMiniPaviC->tLastAction = time();
							break;
						}
					}
				break;

				case 'setWebMedia': // ajoute les demandes de media web
					foreach($tObjClient as $objMiniPaviC) {
						if ($objMiniPaviC->pid == $senderPid) {
							foreach($sfTab as $k=>$e) {
								if (is_array($e) && isset($e['type']) && isset($e['infos'])) {
									$objMiniPaviC->objWebMedia->setRequest($e['type'],$e['infos']);
								}
							}
							break;
						}
					}
				break;

				case 'shiftWebMedia': // supprime une demande de media web
					$pid = $sfTab['pid']; 
					$lastEvent = $sfTab['lastevent']; // STOP ou START ou vide
					foreach($tObjClient as $objMiniPaviC) {
						if ($objMiniPaviC->pid == $pid) {
							$objMiniPaviC->objWebMedia->getRequest($null,$null);
							$objMiniPaviC->objWebMedia->tPing=time();
							
							if ($objMiniPaviC->commSockets != null && isset($objMiniPaviC->commSockets[1])) {								
								$sfTab = array();
								$sfTab['command']='pingwebmedia';
								$sfTab['lastevent']=$lastEvent;
								@fwrite($objMiniPaviC->commSockets[1], serialize($sfTab)."\n");
								posix_kill($objMiniPaviC->pid,SIGUSR1);
							}
							break;
						}
					}
				break;

				case 'pingWebMedia': 
					$pid = $sfTab['pid']; 
					$lastEvent = $sfTab['lastevent']; // STOP ou START ou vide
					foreach($tObjClient as $objMiniPaviC) {
						if ($objMiniPaviC->pid == $pid) {
							if ($objMiniPaviC->commSockets != null && isset($objMiniPaviC->commSockets[1])) {								
								$sfTab = array();
								$sfTab['command']='pingwebmedia';
								$sfTab['lastevent']=$lastEvent;
								@fwrite($objMiniPaviC->commSockets[1], serialize($sfTab)."\n");
								posix_kill($objMiniPaviC->pid,SIGUSR1);
							}
							break;
						}
					}
				break;
				
				case 'addCommandBackgroundCall':	// Appel d'une url en différé
					fwrite($objMiniPaviM->commSockets[1], serialize($sfTab)."\n");
				break;
			}
		}
	}
}


/**************
*** Fermeture des connexions en cas d'arrêt du processus principal
***************/
function onStop() {
	global $tObjClient;
	global $typeProc;
	global $objMiniPaviM;
	global $objMiniPavi;
	global $tSocketsSer;
	global $objConfig;
	
	if ($typeProc == TYPE_PROC_VISUWEB)
		return;
	$pid=getmypid();
	try {
		if ($typeProc == TYPE_PROC_MAIN) {
			trigger_error("[MiniPavi-Main] onStop: Arrêt");
			$objMiniPaviM->log("Arrêt PID=".getmypid());
			foreach($tObjClient as $k=>$obj) {
				$objMiniPaviM->log("onStop: Envoi DECO à service UID=".$obj->uniqueId);
				$obj->sendToService('FIN'); // A REVOIR
				$objMiniPaviM->log("onStop: Fermeture socket UID=".$obj->uniqueId);
				trigger_error("[MiniPavi-Main] onStop: Fermeture socket secondaire pid=".$obj->pid);
				$obj->inCnx->close();
			}
			trigger_error("[MiniPavi-Main] onStop: Fermeture sockets principaux");
			if (isset($tSocketsSer[0])) {
				@fclose($tSocketsSer[0]);
				$tSocketsSer[0] = null;
			}
			if (isset($tSocketsSer[1])) {
				@fclose($tSocketsSer[1]);
				$tSocketsSer[1] = null;
			}
			if (isset($tSocketsSer[2])) {
				@fclose($tSocketsSer[2]);
				$tSocketsSer[2] = null;
			}
			
			trigger_error("[MiniPavi-Main] onStop: Fermeture sockets background calls");
			@fclose($objMiniPaviM->commSockets[0]);
			@fclose($objMiniPaviM->commSockets[1]);
			
			trigger_error("[MiniPavi-Main] onStop: Suppression des enregistrements de sessions");
			$objMiniPaviM->deleteAllLocalRecordings($objConfig->recordsPath);
			
		} else if ($typeProc == TYPE_PROC_BGCALLS) {			
			trigger_error("[MiniPavi-Cli] onStop: Arrêt bgcalls pid=".$pid);
			@fclose($objMiniPaviM->commSockets[0]);
			@fclose($objMiniPaviM->commSockets[1]);
		} else {
			trigger_error("[MiniPavi-Cli] onStop: Arrêt pid=".$pid);
			$objMiniPavi->sendToService('FIN');
			$objMiniPavi->command = null;
			$objMiniPavi->processResponseFromService($objConfig->tAsterisk,$objConfig,true);		
			$objMiniPavi->unregisterLocalSocket();
			if ($objMiniPavi->extCallChannel) {
				trigger_error("[MiniPavi-Cli] onStop: Fermeture channel=".$objMiniPavi->extCallChannel);
				AstAMI::endCall($objMiniPavi->extCallChannel,$objConfig->tAsterisk);
				$objMiniPavi->extCallChannel = null;
			}
			
			$objMiniPavi->log("***DECO*** onStop: UID=".$objMiniPavi->uniqueId);
			$objMiniPavi->log("onStop: Arrêt service en cours...");
		}
	} catch (Exception $e) {
		trigger_error("[MiniPavi] onStop Exception ".$e->getMessage());
	}
}


/**************
*** Fermeture des connexions en cas d'arrêt d'un processus fils
***************/
function onChildStop($signal) {
	global $tObjClient;
	global $typeProc;
	global $objMiniPaviM;
	global $tObjClientH;
	global $objConfig;
	
	pcntl_signal(SIGCHLD, "onChildStop");
	
	if ($typeProc == TYPE_PROC_MAIN) {
		while(($pid = pcntl_wait($status, WNOHANG)) > 0) {
			
			if(!pcntl_wifexited($status)) {
				trigger_error("[MiniPavi-Main] onChildStop: Service killed $pid");
			}
			
			if ($pid == $objMiniPaviM->pid) {
				trigger_error("[MiniPavi-Main] onChildStop: Arrêt background calls");
			} else {
				foreach($tObjClient as $k=>$obj) {
					if ($obj->pid == $pid) {
						
						trigger_error("[MiniPavi-Main] onChildStop: Fermeture socket secondaire ($k) PID=".$pid);
						$obj->inCnx->close();
						
						if (is_array($tObjClient[$k]->commSockets)) {
							trigger_error("[MiniPavi-Main] onChildStop: Fermeture sockets communication ($k) PID=".$pid);					
							if (isset($tObjClient[$k]->commSockets[0]) && $tObjClient[$k]->commSockets[0]!=null) {
								fclose($tObjClient[$k]->commSockets[0]);
								$tObjClient[$k]->commSockets[0]=null;
							}
							if (isset($tObjClient[$k]->commSockets[1]) && $tObjClient[$k]->commSockets[1]!=null) {
								fclose($tObjClient[$k]->commSockets[1]);
								$tObjClient[$k]->commSockets[1] = null;
							}
						}
						$tObjClient[$k]->stopLocalRecoding();
						
						if ($tObjClient[$k]->inCnx->getDirection() == 'IN') {
							$o = new Stats($objConfig->statsPath);
							$o->addStats($tObjClient[$k]->tCnx,$tObjClient[$k]->tLastAction,$tObjClient[$k]->inCnx->getTypeSocket());
							
						}
						
						unset($tObjClient[$k]);
						
						foreach($tObjClientH as $k=>$cliH) {
							if ($cliH->pid == $pid) {
								if ( ($obj->tLastAction-$obj->tCnx)<10) {
									$obj->deleteLocalRecording($objConfig->recordsPath);
									array_splice($tObjClientH,$k,1);
									trigger_error("[MiniPavi-Main] onChildStop: Temps de connexion non significatif -> suppression histo PID=".$pid);
								}
							}
						}
						
					}
				}
			}
		}
	}
	
	
}

/**************
*** Lance le processus des tâches en arrière plan
***************/

function startBgcallProc($objMiniPaviM) {
	global $typeProc;
	$pid = pcntl_fork();
	if ($pid<0) {
		trigger_error("[MiniPavi-Main] Erreur fork processus background call");
		return false;
	}
	if ($pid == 0) {
		$typeProc = TYPE_PROC_BGCALLS;
		$objMiniPaviM->backgroundCalls();
		exit(0);
	}
	$objMiniPaviM->pid = $pid;			// On garde le pid du processus des appels en arrière plan
	return true;
}


/**************
*** Retourne l'usage du CPU
***************/

function percentLoadAvg(){
    $cpu_count = 1;
    if(is_file('/proc/cpuinfo')) {
        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);
        $cpu_count = count($matches[0]);
    } 
    $sys_getloadavg = sys_getloadavg();
    $sys_getloadavg[0] = $sys_getloadavg[0] / $cpu_count;
    $sys_getloadavg[1] = $sys_getloadavg[1] / $cpu_count;
    $sys_getloadavg[2] = $sys_getloadavg[2] / $cpu_count;
    return $sys_getloadavg;
}

/**************
*** Défini un pincode à 4 chiffres non utilisé actuellement
***************/

function createPin() {
global $tObjClient;
	$last = array_key_last($tObjClient);
	do {
		$pin = rand(2000,9999);
		if (count($tObjClient)==0)
			return $pin;
		foreach($tObjClient as $k=>$o) {
			if ($pin == substr($o->uniqueId,-4))
				break;
		}
		if ($k == $last) {
			return $pin;
		}
	} while(true);
}