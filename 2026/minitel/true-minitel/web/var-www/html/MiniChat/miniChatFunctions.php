<?php
/**
 * @file miniChatFunctions.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Novembre 2023
 *
 * Fonctions utlisées dans le script miniChat.php
 *
 * Licence GNU GPL 
 */

// Attention à autoriser le script en lecture/écriture (et création) sur ces fichiers !!
define('MINICHAT_MSGLIST','files/messages.list');	// Fichier de la liste des message
define('MINICHAT_CNXLIST','files/connected.list');	// Fichier de la liste des connectés

define('MCHAT_TYPE_NORMAL',1);
define('MCHAT_TYPE_CGPT',0);

function mchat__sortMsg($a,$b) {
	return $a['time']>$b['time'];
}

function mchat_openListFile() {
	if (!file_exists(MINICHAT_CNXLIST))
		$f = fopen(MINICHAT_CNXLIST,'w+');
	else
		$f = fopen(MINICHAT_CNXLIST,'r+');
	flock($f,LOCK_EX);
	return $f;
}

function mchat_openMsgFile() {
	if (!file_exists(MINICHAT_MSGLIST))
		$f = fopen(MINICHAT_MSGLIST,'w+');
	else
		$f = fopen(MINICHAT_MSGLIST,'r+');
	flock($f,LOCK_EX);
	return $f;
}


/*****************
*** Récupère les messages pour le destinataire $id (ou tous si $id=0), ou le message $idMsg si $idMsg!=''
******************/

function mchat_getMsgFor($fmsg,$id=0,$idMsg='') {
	$tRes = array();

	if ($fmsg) {
		rewind($fmsg);
		$r = fgets($fmsg);
		if ($r === false) {
			return $tRes;
		}
		
		
		
		if (strlen($r)>0) {
			$tResTmp = unserialize($r);
		}

		if ($idMsg=='') {
			if($id>0) {
				foreach($tResTmp as $k=>$msg) {
					if ($msg['iddest'] == $id)
						$tRes[]=$tResTmp[$k];
				}
			}
			else $tRes = $tResTmp;
			usort($tRes, "mchat__sortMsg");
		} else {
			foreach($tResTmp as $k=>$msg) {
				if ($msg['idmsg'] == $idMsg) {
					$tRes[]=$tResTmp[$k];
					break;
				}
			}
		}
	}
	return $tRes;
}


/*****************
*** Ajoute un nouveau message
******************/

function mchat_setMsg($fmsg,$flist,$idExp,$idDest,$typeExp,$typeDest,$nameExp,$nameDest,$content,$prevContent=array()) {
	if ($fmsg && $fmsg) {
		
		$found = false;
		$tList=mchat_getConnectedList($flist,$num,true);
		foreach($tList as $cnx) {
			if ($cnx['id'] == $idDest) {
				$found = true;
				break;
			}
		}
		
		if (!$found) {
			return true;
		}
		
		
		
		rewind($fmsg);
		$tMsg=array();
		$r = fgets($fmsg);
		if ($r!== false && strlen($r)>0) {
			$tMsg = unserialize($r);
		}
		$i=count($tMsg);

		
		foreach($content as $k=>$v) {
			$content[$k]=str_replace("\r"," ",$content[$k]);	
			$content[$k]=str_replace("\n"," ",$content[$k]);	
		}
		
		$tMsg[$i]['idmsg']=uniqid();
		$tMsg[$i]['time']=time();
		$tMsg[$i]['idexp']=$idExp;
		$tMsg[$i]['nameexp']=trim($nameExp);
		$tMsg[$i]['namedest']=trim($nameDest);
		$tMsg[$i]['iddest']=$idDest;
		$tMsg[$i]['typedest']=$typeDest;
		$tMsg[$i]['typeexp']=$typeExp;
		$tMsg[$i]['content']=$content;
		$tMsg[$i]['prevcontent']=$prevContent;

		ftruncate($fmsg,0);
		rewind($fmsg);		
		fputs($fmsg,serialize($tMsg));
		return true;
	}
	return false;
}


/*****************
*** Supprime le messages dont le idmsg=$idMsg (ou dont le iddest=$idMsg si $isIdDest=true)
******************/

function mchat_delMsg($fmsg,$idMsg,$isIdDest=false) {
	if ($fmsg) {
		$tMsg=array();
		rewind($fmsg);
		$r = fgets($fmsg);
		if ($r === false) {
			return false;
		}
		if (strlen($r)>0) {
			$tMsg = unserialize($r);
		}
		$found = false;
		foreach($tMsg as $k=>$msg) {
			if (($msg['idmsg'] == $idMsg && !$isIdDest) || ($msg['iddest'] == $idMsg && $isIdDest)) {
				unset($tMsg[$k]);
				$found = true;
				if (!$isIdDest)
					break;
			}
		}
		if (!$found) {
			return true;
		}
		$tMsg = array_values($tMsg);
		ftruncate($fmsg,0);
		rewind($fmsg);		
		fputs($fmsg,serialize($tMsg));
		return true;
	}
	return false;
}

/*****************
*** Efface la liste des connectés
******************/

function mchat_clearConnectedList($flist) {
	mchat_setConnectedList($flist,array());
}


/*****************
*** Retourne la liste des connectés et le nombre de connectés.
*** Si $clean=true, le tableau retourné ne comporte pas d'éléments vides.
******************/

function mchat_getConnectedList($flist,&$num,$clean=false) {
	$tRes = array();
	$num=0;
	if ($flist) {
		rewind($flist);
		$r = fgets($flist);
		if ($r === false) {
			return $tRes;
		}
		if (strlen($r)>0) {
			$tRes = unserialize($r);
		}
		foreach($tRes as $k=>$v) 
			if ($v['id']!='') $num++;
			else if ($clean) {
				unset($tRes[$k]);
			}
			
	}
	return $tRes;
	
}


/*****************
*** Ajoute un connecté à la liste
******************/

function mchat_addConnected($flist,$id,$name,$cv,$type=MCHAT_TYPE_NORMAL) {
	$t = mchat_getConnectedList($flist,$null);
	foreach($t as $key=>$vals) {
		if ($t[$key]['id']=='') {
			$t[$key]['id']=$id;
			$t[$key]['tlast']=time();
			$t[$key]['tcnx']=time();
			$t[$key]['name']=trim($name);
			$t[$key]['cv']=trim($cv);
			$t[$key]['type']=$type;
			mchat_setConnectedList($flist,$t);
			return;
		}
	}
	$i = count($t);
	$t[$i]['id']=$id;
	$t[$i]['tlast']=time();
	$t[$i]['tcnx']=time();
	$t[$i]['name']=trim($name);
	$t[$i]['cv']=trim($cv);
	$t[$i]['type']=$type;	
	mchat_setConnectedList($flist,$t);
}

/*****************
*** Supprime un connecté de la liste
******************/

function mchat_removeConnected($flist,$fmsg,$id) {
	$t = mchat_getConnectedList($flist,$null);
	foreach($t as $key=>$vals) {
		if ($vals['id']==$id) {
			$t[$key]['id']='';
		}
	}
	mchat_setConnectedList($flist,$t);
	mchat_delMsg($fmsg,$id,true);
}

/*****************
*** Enregistre la liste des connectés
******************/

function mchat_setConnectedList($flist,$t) {
	if ($flist) {
		ftruncate($flist,0);
		rewind($flist);
		fputs($flist,serialize($t));
	}
}


/*****************
*** Mise à jour du timestamp de la dernière activité
******************/

function mchat_updateLastAction($flist,$id) {
	$tRes = array();
	$found=false;
	if ($flist) {
		rewind($flist);
		$r = fgets($flist);
		if ($r === false) {
			return $found;
		}
		if (strlen($r)>0) {
			$tRes = unserialize($r);
		}
		foreach($tRes as $k=>$v) {
			if ($v['id']==$id)  {
				$found=true;
				$tRes[$k]['tlast']=time();
				break;
			}
		}
		ftruncate($flist,0);
		rewind($flist);		
		fputs($flist,serialize($tRes));
	}
	return $found;
}


/*****************
*** Nettoyage des connexions inactives depuis plus d'une heure 
******************/
function mchat_chatClean($flist,$fmsg) {
	$tDel=array();
	

	if ($flist && $fmsg) {
		rewind($flist);		
		$r = fgets($flist);
		if ($r === false) {
			return $tDel;
		}
		if (strlen($r)>0) {
			$tRes = unserialize($r);
		}
		foreach($tRes as $k=>$v) {
			if ($tRes[$k]['type']==MCHAT_TYPE_NORMAL && $tRes[$k]['id']!='' && (int)($v['tlast'])+MINICHAT_TIMEOUT<time()) {
				trigger_error("[MiniChatFunc] Ghost ".$tRes[$k]['id']);
				$tDel[]=$tRes[$k]['id'];
				mchat_delMsg($fmsg,$tRes[$k]['id'],true);
				$tRes[$k]['id']='';
			}
		}
		ftruncate($flist,0);
		rewind($flist);		
		fputs($flist,serialize($tRes));
	}
	return $tDel;
}


function setStep($newStep) {
	global $step;
	global $prevStep;
	
	$prevStep=$step;
	$step=$newStep;
}
?>
