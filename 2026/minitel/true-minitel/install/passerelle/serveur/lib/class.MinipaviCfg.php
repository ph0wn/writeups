<?php
/**
 * @file class.MinipaviCfg.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.1 Mars 2024
 *
 * MINI Point d'Accès VIdeotex
 *
 * Lecture du fichier de configuration
 *
 * License GPL v2 ou supérieure
 */

class MinipaviCfg {
	public $cfgFile;
	public $host;
	public $verbose;
	public $maxCnx;	
	public $maxCnxByIP;	
	public $maxHisto;	
	public $timeout;	
	public $wsPort;
	public $wssPort;
	public $tcpPort;
	public $astPort;
	public $httpPort;
	public $httpPwd;
	public $httpUser;
	public $defaultUrl;
	public $logPath;
	public $statsPath;
	public $recordsPath;
	public $sslCert;
	public $sslKey;
	public $ipDBKey;
	public $ipBlackList;
	public $ipWhiteList;
	public $tExtCnxKeys;
	public $tAsterisk;
	public $extCallUrl;
	public $visuKey;
	public $tVisuwebAllowIp;
	public $viewer;
	public $screenSaver;


	/*************************************************
	// cfgFile: fichier de configuration
	*************************************************/
	function __construct($cfgFile) {
		$this->cfgFile=trim($cfgFile);
		$this->tExtCnxKeys=array();
		$this->tAsterisk=array();
		$this->tVisuwebAllowIp=array();
	}


	/*************************************************
	// Charge la configuration
	*************************************************/
	function load() {
		if ($this->cfgFile=='')
			return false;
		$objXML = simplexml_load_file($this->cfgFile,null,LIBXML_NOCDATA|LIBXML_NOBLANKS);
		if ($objXML === false || $objXML->getName() != 'config')
			return false;
		
		foreach ($objXML as $elementName=>$obj) {
			if ($elementName == 'general') {
				$this->readGeneral($obj);
			}
			if ($elementName == 'connecttocmd') {
				$this->readConnecttocmd($obj);
			}
			if ($elementName == 'recordings') {
				$this->readRecordings($obj);
			}
			
		}
		return true;
	}

	/*************************************************
	// Récupère la configuration générale
	// obj: element "general"
	*************************************************/
	private function readGeneral($obj) {
		$this->verbose = (int)@$obj->verbose;
		if ($this->verbose)
			$this->verbose=true;
		$this->host = trim(@$obj->host);
		$this->maxCnx = (int)@$obj->maxc;
		$this->maxCnxByIP = (int)@$obj->maxcip;
		$this->maxHisto = (int)@$obj->maxhisto;
		$this->timeout = (int)@$obj->timeout;
		$this->wsPort = (int)@$obj->wsport;
		$this->wssPort = (int)@$obj->wssport;
		$this->tcpPort = (int)@$obj->telnport;
		$this->astPort = (int)@$obj->astport;		
		$this->httpPort = (int)@$obj->httpport;
		$this->httpPwd = trim(@$obj->httppwd);
		$this->httpUser = trim(@$obj->httpuser);
		$this->defaultUrl = trim(@$obj->durl);
		$this->logPath = trim(@$obj->lpath);
		$this->statsPath = trim(@$obj->spath);		
		$this->viewer = trim(@$obj->viewer);		
		$this->screenSaver = trim(@$obj->screensaver);		
		$this->sslCert = trim(@$obj->ssl);
		$this->sslKey = trim(@$obj->sslkey);
		$this->ipDBKey = trim(@$obj->ipdbkey);
		$this->ipBlackList = trim(@$obj->ipblacklist);
		$this->ipWhiteList = trim(@$obj->ipwhitelist);
		foreach(@$obj->visuweb->children() as $elementName=>$v) {
			if ($elementName == 'allowip') {
				$this->tVisuwebAllowIp[]=trim($v);
			}
		}
	}
	
	/*************************************************
	// Récupère la configuration de l'élement connecttocmd
	// concernant les commandes de connexions avec des serveurs exterieurs (telnet, WS et RTC)
	// obj: element "connecttocmd"
	*************************************************/
	private function readConnecttocmd($obj) {
		$this->extCallUrl = trim(@$obj->extcallurl);
		$this->visuKey = trim(@$obj->visukey);
		foreach(@$obj->key as $k=>$key) {
			$this->tExtCnxKeys[]=trim($key);
		}
		
		foreach(@$obj->asterisk->children() as $elementName=>$v) {
			$this->tAsterisk[$elementName]=trim($v);
		}
	}
	
	private function readRecordings($obj) {
		$this->recordsPath = trim(@$obj->rpath);		
	}
	
}