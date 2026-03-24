#!/usr/bin/php
<?php
/**
 * @file class.AbuseIPDB.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.1 Mars 2024
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * License GPL v2 ou supérieure
 *
 *
 */
 
   
 class AbuseIPDB {
  
	const AIPDB_URL = 'https://api.abuseipdb.com/api/v2/check';
  
	static $tCheckedIp = [];
  
  	/*************************************************
	// Vérifie si une adresse IP est blacklistée chez abuseipdb.com
	// ip: adresse IP
	// apiKey: clé API
	// abuseConfidenceScore: score (0[OK]-100[KO])
	// countryCode: code pays
	// blackList: fichier d'IP blacklistée manuellement (une IP par ligne)
	// whiteList: fichier d'IP whitelistée manuellement (une IP par ligne)
	// Retourne true si la vérification a pu être effectuée, sinon false
	**************************************************/

	static function check($ip,$apiKey,&$abuseConfidenceScore,&$countryCode,$blackList=null,$whiteList=null) {
		
		if ($blackList != '') {
			$list = @file($blackList,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
			if ($list !== false && is_array($list)) {
				if (in_array($ip,$list)) {
					$abuseConfidenceScore = 100;
					$countryCode = 'XX';
					return true;
				}
			}
		}
		if ($whiteList != '') {
			$list = @file($whiteList,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
			if ($list !== false && is_array($list)) {
				if (in_array($ip,$list)) {
					$abuseConfidenceScore = 0;
					$countryCode = 'XX';
					return true;
				}
			}
		}
		
		if (trim($apiKey) == '') {
			$abuseConfidenceScore = 0;
			$countryCode = 'XX';
			return true;
		}
		$t = time();
		$tTmp = [];
		foreach(self::$tCheckedIp as $k=>$v) {
			if ($v['ts'] + 86400 > $t)
				$tTmp[$k]=$v;
		}
		self::$tCheckedIp = $tTmp;
			
		if (array_key_exists($ip,self::$tCheckedIp)) {
			$abuseConfidenceScore = self::$tCheckedIp[$ip]['score'];
			$countryCode = self::$tCheckedIp[$ip]['country'];
			return true;
		}
		
		
		$abuseConfidenceScore = 0;
		$countryCode='';
		
		$headers = array(
		'Key: '.$apiKey,
		'Accept: application/json'
		);
	 
		$params = array(
		'ipAddress'=>$ip,
		'maxAgeInDays'=>'90'
		);
		
		$url = self::AIPDB_URL.'?'.http_build_query($params);
		
		$ch = curl_init( $url );

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	 
		$retry = 0;
		do {
			$retry++;
			$result = curl_exec($ch);
			trigger_error("[AbuseIPDB] Check request $ip");					
		} while((curl_errno($ch) == CURLE_OPERATION_TIMEDOUT ||  curl_errno($ch) == CURLE_OPERATION_TIMEOUTED) && $retry < 2);
		
		curl_close($ch);		
		
		if ($result===false) {
			return false;
		}
		$tab=@json_decode($result,true,10,JSON_INVALID_UTF8_SUBSTITUTE);
		if (isset($tab['data']['abuseConfidenceScore'])) {
			$abuseConfidenceScore = (int)$tab['data']['abuseConfidenceScore'];
		}
		if (isset($tab['data']['countryCode'])) {
			$countryCode = $tab['data']['countryCode'];
		}
		self::$tCheckedIp[$ip]['ts']=time();
		self::$tCheckedIp[$ip]['score']=$abuseConfidenceScore;
		self::$tCheckedIp[$ip]['country']=$countryCode;
		return true;
	 }
 }