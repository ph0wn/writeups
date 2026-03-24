<?php
/**
 * @file MiniAPISncf.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Novembre 2023
 *
 * Communication basique avec l'API SNCF
 *
 * Clé d'accès à l'API SNCF
 * A demander sur https://numerique.sncf.com/startup/api/
 */

define('MINISNCF_KEY','7894396b-245d-4ade-8cf6-6e634621f95b');


class MiniAPISncf {

	/******************************
	/**
	/** Récupère la liste des gares
	/**
	/******************************/
	
	static function GetGares($search) {
		$search=preg_replace("/'/","\\'",$search);
		$search = urlencode($search);
		$urlToCall = "https://ressources.data.sncf.com/api/explore/v2.1/catalog/datasets/liste-des-gares/records?select=libelle%2Ccode_uic&where=suggest(libelle,'$search')&group_by=code_uic%2Clibelle&order_by=libelle&limit=99";		
		
		$ch = curl_init( $urlToCall );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_USERPWD, MINISNCF_KEY);
		$result = curl_exec($ch);
		
		if ($result===false) {
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		
		$tab=json_decode($result,true,10,JSON_INVALID_UTF8_SUBSTITUTE);
		$tab = $tab['results'];
		
		$tGares=array();
		$numGares=0;
		foreach ($tab as $result) {
				$tGares[$numGares]['uic_code'][0] = ltrim($result['code_uic'],'0');
				$tGares[$numGares]['name'] = $result['libelle'];
				$numGares++;
		}
		return $tGares;
	}

	/******************************
	/**
	/** Récupère les arrivées des gares
	/** dont le code UIC est dans le tableau $tUIC
	/**
	/******************************/
	
	static function getRTArrivals($tUIC) {
		$tArrivals=array();
		$numArr=0;
		foreach($tUIC as $uicCode) {
			$urlToCall = "https://api.sncf.com/v1/coverage/sncf/stop_areas/stop_area:SNCF:$uicCode/arrivals?data_freshness=realtime";
			$ch = curl_init( $urlToCall );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_USERPWD, MINISNCF_KEY);
			$result = curl_exec($ch);
			
			if ($result===false) {
				curl_close($ch);
				return false;
			}
			curl_close($ch);
			
			$tab=json_decode($result,true,10,JSON_INVALID_UTF8_SUBSTITUTE);
			
			
			foreach($tab['arrivals'] as $departure) {
				$tArrivals[$numArr]['direction']=$departure['display_informations']['name'];
				$tArrivals[$numArr]['direction2']=$departure['display_informations']['direction'];
				$tArrivals[$numArr]['commercial_mode']=$departure['display_informations']['commercial_mode'];
				$tArrivals[$numArr]['trip_short_name']=$departure['display_informations']['trip_short_name'];
				$tArrivals[$numArr]['arrival_date_time']=substr($departure['stop_date_time']['arrival_date_time'],6,2).'/'.substr($departure['stop_date_time']['arrival_date_time'],4,2).' '.substr($departure['stop_date_time']['arrival_date_time'],9,2).':'.substr($departure['stop_date_time']['arrival_date_time'],11,2);
				if (isset($departure['stop_date_time']['base_arrival_date_time'])) {
					$tArrivals[$numArr]['base_arrival_date_time']=substr($departure['stop_date_time']['base_arrival_date_time'],6,2).'/'.substr($departure['stop_date_time']['base_arrival_date_time'],4,2).' '.substr($departure['stop_date_time']['base_arrival_date_time'],9,2).':'.substr($departure['stop_date_time']['base_arrival_date_time'],11,2);
				} else {
					$tArrivals[$numArr]['base_arrival_date_time']=$tArrivals[$numArr]['arrival_date_time'];
				}
				
				if (strpos(@$departure['route']['direction']['id'],$uicCode)!==false) {
						$tArrivals[$numArr]['direction2']='TERMINUS';
				}
				$numArr++;
			}
			
			
			
		}
		return $tArrivals;

	}

	/******************************
	/**
	/** Récupère les départs des gares
	/** dont le code UIC est dans le tableau $tUIC
	/**
	/******************************/

	static function getRTDepartures($tUIC) {
		$tDeparture=array();
		$numDep=0;
		
		
		foreach($tUIC as $uicCode) {
			$urlToCall = "https://api.sncf.com/v1/coverage/sncf/stop_areas/stop_area:SNCF:$uicCode/departures?data_freshness=realtime";
			
			
			
			$ch = curl_init( $urlToCall );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_USERPWD, MINISNCF_KEY);
			$result = curl_exec($ch);
			
			if ($result===false) {
				curl_close($ch);
				return false;
			}
			curl_close($ch);
			
			$tab=json_decode($result,true,10,JSON_INVALID_UTF8_SUBSTITUTE);
			
			foreach($tab['departures'] as $departure) {
				
				$tDeparture[$numDep]['direction']=$departure['display_informations']['name'];
				$tDeparture[$numDep]['direction2']=$departure['display_informations']['direction'];
				$tDeparture[$numDep]['commercial_mode']=$departure['display_informations']['commercial_mode'];
				$tDeparture[$numDep]['trip_short_name']=$departure['display_informations']['trip_short_name'];
				$tDeparture[$numDep]['departure_date_time']=substr($departure['stop_date_time']['departure_date_time'],6,2).'/'.substr($departure['stop_date_time']['departure_date_time'],4,2).' '.substr($departure['stop_date_time']['departure_date_time'],9,2).':'.substr($departure['stop_date_time']['departure_date_time'],11,2);
				if (isset($departure['stop_date_time']['base_departure_date_time'])) {
					$tDeparture[$numDep]['base_departure_date_time']=substr($departure['stop_date_time']['base_departure_date_time'],6,2).'/'.substr($departure['stop_date_time']['base_departure_date_time'],4,2).' '.substr($departure['stop_date_time']['base_departure_date_time'],9,2).':'.substr($departure['stop_date_time']['base_departure_date_time'],11,2);
				} else {
					$tDeparture[$numDep]['base_departure_date_time'] = $tDeparture[$numDep]['departure_date_time'];
				}
				$numDep++;
			}
		}
		return $tDeparture;
	}

	
}
?>
