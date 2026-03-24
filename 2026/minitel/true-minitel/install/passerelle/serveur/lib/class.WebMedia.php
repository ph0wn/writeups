<?php
/**
 * @file class.WebMedia.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.1 Août 2024
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 * 
 * License GPL v2 ou supérieure
 *
 * Fonctionnalités WebMedia
 *
 */

  
class WebMedia {

	const WEBMEDIA_START = "\x14#D";	// Début de séquence
	const WEBMEDIA_STOP = "\x14#F";	// Fin de séquence
	const WEBMEDIA_MAX_DATAS = 256;	// Longueur max des infos
	
	private $globalBuffer;
	private $tRequests=array();
	public $tPing=0;

	public function parseDatas($datas) {
		$regex = '/'.self::WEBMEDIA_START.'((?:.(?!'.self::WEBMEDIA_START.'))*)'.self::WEBMEDIA_STOP.'/';
		$this->globalBuffer.=$datas;		
		$r = preg_match_all($regex,$this->globalBuffer,$tRet,PREG_OFFSET_CAPTURE);		

		if ($r<1) {
			$this->globalBuffer = substr($this->globalBuffer,-(self::WEBMEDIA_MAX_DATAS+strlen(self::WEBMEDIA_START)+strlen(self::WEBMEDIA_STOP)));
			return;
		}
		foreach($tRet[1] as $r) {
			if (preg_match('/URL:(.*)/',$r[0],$t)) {
				if (@filter_var($t[1], FILTER_VALIDATE_URL)!==false) {
					$this->setRequest('URL',trim($t[1]));
				}
			} else if (preg_match('/YT:(.*)/',$r[0],$t)) {
				$this->setRequest('YT',trim($t[1]));
			} else if (preg_match('/VID:(.*)/',$r[0],$t)) {
				if (@filter_var($t[1], FILTER_VALIDATE_URL)!==false) {
					$this->setRequest('VID',trim($t[1]));
				}
			} else if (preg_match('/SND:(.*)/',$r[0],$t)) {
				if (@filter_var($t[1], FILTER_VALIDATE_URL)!==false) {
					$this->setRequest('SND',trim($t[1]));
				}
			} else if (preg_match('/IMG:(.*)/',$r[0],$t)) {
				if (@filter_var($t[1], FILTER_VALIDATE_URL)!==false) {
					$this->setRequest('IMG',trim($t[1]));
				}
			}
			$lastPos = $r[1] + strlen($r[0]) + strlen(self::WEBMEDIA_STOP);			
		}
		if ($lastPos<strlen($this->globalBuffer)-1)
			$this->globalBuffer = substr($this->globalBuffer,$lastPos);
		return;
	}
	
	public function getRequest(&$type,&$infos) {
		$type='';
		$infos='';
		$e = array_shift($this->tRequests);
		if ($e !== null) {
			$type = $e['type'];
			$infos = $e['infos'];
			return true;
		}
		return false;
	}
	
	public function setRequest($type,$infos) {
		// On prévoit pour le futur qu'il pourra y avoir plusieurs demande simultanées
		//$i = count($this->tRequests);
		$i = 0;
		$this->tRequests[$i]['type']=$type;
		$this->tRequests[$i]['infos']=$infos;
	}
	
	public function sendRequestsToMain($objMiniPavi) {
		if (count($this->tRequests)==0)
			return;
		$objMiniPavi->sendToMainProc('setWebMedia',$this->tRequests);
		$this->tRequests = array();
	}
}