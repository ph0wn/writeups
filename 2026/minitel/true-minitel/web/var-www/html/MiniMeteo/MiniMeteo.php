<?php
/**
 * @file MiniMeteo.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Mars 2024
 *
 * Communication basique avec l'API open-meteo https://open-meteo.com
 *
 */


define('MMETEO_GEOLOCURL','https://geocoding-api.open-meteo.com/v1/search');
define('MMETEO_FORECASTURL','https://api.open-meteo.com/v1/forecast');
define('MMETEO_AIRQURL','https://air-quality-api.open-meteo.com/v1/air-quality');


// WMO Weather interpretation codes

// Petits symboles
$smallIcon['soleil']=VDT_G1.VDT_BGBLUE.VDT_TXTYELLOW.'`|t'.VDT_DOWN.VDT_LEFT.VDT_LEFT.VDT_LEFT.'*_?'.VDT_G0;
$smallIcon['soleil-nuage']=VDT_G1.VDT_BGBLUE.VDT_TXTYELLOW.'`|t'.VDT_DOWN.VDT_LEFT.VDT_LEFT.VDT_LEFT.VDT_TXTCYAN.'z'.VDT_BGWHITE.'{'.VDT_BGYELLOW.'p'.VDT_G0;
$smallIcon['nuage']=VDT_G1.VDT_BGBLUE.VDT_TXTCYAN.' p'.VDT_DOWN.VDT_LEFT.VDT_LEFT.VDT_TXTCYAN.'z'.VDT_BGWHITE.'r'.VDT_BGBLUE.'t'.VDT_G0;
$smallIcon['nuage-pluie']=VDT_G1.VDT_BGBLUE.VDT_TXTCYAN.'z'.VDT_BGWHITE.'r'.VDT_BGBLUE.'t'.VDT_DOWN.VDT_LEFT.VDT_LEFT.VDT_LEFT.VDT_G0.VDT_BGBLUE.VDT_TXTWHITE.' //';
$smallIcon['nuage-neige']=VDT_G1.VDT_BGBLUE.VDT_TXTCYAN.'z'.VDT_BGWHITE.'r'.VDT_BGBLUE.'t'.VDT_DOWN.VDT_LEFT.VDT_LEFT.VDT_LEFT.VDT_G0.VDT_BGBLUE.VDT_TXTWHITE.' **';
$smallIcon['brouillard']=VDT_G1.VDT_BGBLUE.VDT_TXTWHITE.',$,'.VDT_DOWN.VDT_LEFT.VDT_LEFT.VDT_LEFT.'pc3'.VDT_G0;
$smallIcon['orage']=VDT_G1.VDT_BGBLUE.VDT_TXTYELLOW.'#'.VDT_BGRED.'w'.VDT_BGBLUE."'".VDT_DOWN.VDT_LEFT.VDT_LEFT.VDT_LEFT."`'".VDT_G0;

// Table de correspondance des codes

$tWMO = array('0'=>array('texte'=>'Ciel clair','imgjour'=>'icone_soleil','imgnuit'=>'icone_lune','small'=>'soleil'),
'1'=>array('texte'=>'Généralement clair','imgjour'=>'icone_soleil_nuage','imgnuit'=>'icone_lune_nuage','small'=>'soleil-nuage'),
'2'=>array('texte'=>'Partiellement nuageux','imgjour'=>'icone_soleil_nuage','imgnuit'=>'icone_lune_nuage','small'=>'soleil-nuage'),
'3'=>array('texte'=>'Couvert','imgjour'=>'icone_nuage','imgnuit'=>'icone_nuage','small'=>'nuage'),
'45'=>array('texte'=>'Brouillard','imgjour'=>'icone_brouillard','imgnuit'=>'icone_brouillard','small'=>'brouillard'),
'48'=>array('texte'=>'Dépôt de brouillard givré','imgjour'=>'icone_brouillard','imgnuit'=>'icone_brouillard','small'=>'brouillard'),
'51'=>array('texte'=>'Légère bruine','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'53'=>array('texte'=>'Bruine modérée','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'55'=>array('texte'=>'Bruine dense','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'56'=>array('texte'=>'Légère bruine verglançante','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'57'=>array('texte'=>'Bruine verglançante dense','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'61'=>array('texte'=>'Légère pluie','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'63'=>array('texte'=>'Pluie modérée','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'65'=>array('texte'=>'Forte pluie','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'66'=>array('texte'=>'Légère pluie verglaçante','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'67'=>array('texte'=>'Forte pluie verglaçante','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'71'=>array('texte'=>'Faibles chutes de neige','imgjour'=>'icone_nuage_neige','imgnuit'=>'icone_nuage_neige','small'=>'nuage-neige'),
'73'=>array('texte'=>'Chutes de neige modérées','imgjour'=>'icone_nuage_neige','imgnuit'=>'icone_nuage_neige','small'=>'nuage-neige'),
'75'=>array('texte'=>'Fortes chutes de neige','imgjour'=>'icone_nuage_neige','imgnuit'=>'icone_nuage_neige','small'=>'nuage-neige'),
'77'=>array('texte'=>'Grains de neige','imgjour'=>'icone_nuage_neige','imgnuit'=>'icone_nuage_neige','small'=>'nuage-neige'),
'80'=>array('texte'=>'Légères averses','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'81'=>array('texte'=>'Averses','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'82'=>array('texte'=>'Fortes averses','imgjour'=>'icone_nuage_pluie','imgnuit'=>'icone_nuage_pluie','small'=>'nuage-pluie'),
'85'=>array('texte'=>'Légères averses de neige','imgjour'=>'icone_nuage_neige','imgnuit'=>'icone_nuage_neige','small'=>'nuage-neige'),
'86'=>array('texte'=>'Fortes averses de neige','imgjour'=>'icone_nuage_neige','imgnuit'=>'icone_nuage_neige','small'=>'nuage-neige'),
'95'=>array('texte'=>'Orages','imgjour'=>'icone_orage','imgnuit'=>'icone_orage','small'=>'orage'),
'96'=>array('texte'=>'Orages avec légère grêle','imgjour'=>'icone_orage','imgnuit'=>'icone_orage','small'=>'orage'),
'99'=>array('texte'=>'Orages avec forte grêle','imgjour'=>'icone_orage','imgnuit'=>'icone_orage','small'=>'orage')
);



$tDirectionVent[0]='Nord';
$tDirectionVent[1]='Nord-Nord-Est';
$tDirectionVent[2]='Nord-Est';
$tDirectionVent[3]='Est-Nord-Est';
$tDirectionVent[4]='Est';
$tDirectionVent[5]='Est-Sud-Est';
$tDirectionVent[6]='Sud-Est';
$tDirectionVent[7]='Sud-Sud-Est';
$tDirectionVent[8]='Sud';
$tDirectionVent[9]='Sud-Sud-Ouest';
$tDirectionVent[10]='Sud-Ouest';
$tDirectionVent[11]='Ouest-Sud-Ouest';
$tDirectionVent[12]='Ouest';
$tDirectionVent[13]='Ouest-Nord-Ouest';
$tDirectionVent[14]='Nord-Ouest';
$tDirectionVent[15]='Nord-Nord-Ouest';
$tDirectionVent[16]='Nord';

/******************************
/**
/** Récupère la liste des villes
/**
/******************************/


function GetCities($name) {
	$name = trim($name);
	if (mb_strlen($name)<3) {
		return false;
	}
	
	$maxResult = 50;
	
	
	$urlToCall=MMETEO_GEOLOCURL.'?name='.urlencode($name).'&count='.$maxResult.'&language=fr&format=json';
	
	$ch = curl_init( $urlToCall );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
	$result = curl_exec($ch);
	
	if ($result===false) {
		curl_close($ch);
		return false;
	}
	curl_close($ch);
	
	$tab=json_decode($result,true,20,JSON_INVALID_UTF8_SUBSTITUTE);
	$tOk = array();
	$i = 0;
	
	foreach($tab['results'] as $k=>$e) {
		$tOk[$i]['nomVille'] = $e['name'];
		$tOk[$i]['population'] = @$e['population'];
		$tOk[$i]['altitude'] = @$e['elevation'];
		$tOk[$i]['latitude'] = $e['latitude'];
		$tOk[$i]['longitude'] = $e['longitude'];
		$tOk[$i]['pays'] = @$e['country'];
		$tOk[$i]['region'] = @$e['admin1'];
		$tOk[$i]['timezone'] = @$e['timezone'];
		$i++;
	}
	
	return $tOk;
}





/******************************
/**
/** Récupère les prévisions
/**
/******************************/


function GetForecast($latitude,$longitude,$timezone,&$tNow,&$tFuture) {
	global $tDirectionVent;
	
	$tNow = array();
	$tFuture = array();
	
	$latitude = (float)$latitude;
	$longitude = (float)$longitude;
	
	$urlToCall=MMETEO_FORECASTURL.'?latitude='.urlencode($latitude).'&longitude='.$longitude.'&current=temperature_2m,apparent_temperature,is_day,weather_code,cloud_cover,wind_speed_10m,wind_direction_10m&hourly=temperature_2m,apparent_temperature,precipitation_probability,weather_code,cloud_cover,wind_speed_10m,wind_direction_10m&daily=precipitation_probability_max,weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,sunrise,sunset,uv_index_max,wind_speed_10m_max,wind_direction_10m_dominant&timezone='.urlencode($timezone);
	
	$ch = curl_init( $urlToCall );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
	$result = curl_exec($ch);
	
	if ($result===false) {
		curl_close($ch);
		return false;
	}
	curl_close($ch);
	
	$tab=json_decode($result,true,20,JSON_INVALID_UTF8_SUBSTITUTE);


	$tNow['instant']['temperature'] = $tab['current']['temperature_2m'];
	$tNow['instant']['temperatureRessentie'] = $tab['current']['apparent_temperature'];
	$tNow['instant']['weatherCode'] = $tab['current']['weather_code'];
	$tNow['instant']['couvertureNuage'] = $tab['current']['cloud_cover'];
	$tNow['instant']['vent'] = $tab['current']['wind_speed_10m'];
	$idx = (int)(((float)($tab['current']['wind_direction_10m'])%360)/22.5);
	$tNow['instant']['directionVent'] = $tDirectionVent[$idx];
	$tNow['instant']['jour'] = $tab['current']['is_day'];
	$tNow['instant']['precipitation'] = $tab['current']['precipitation'];

	$today = date('Y-m-d');
	
	$k = array_search($today.'T07:00',$tab['hourly']['time']);

	if ($k!==false) {
		$tNow['matin']['temperature'] = $tab['hourly']['temperature_2m'][$k];
		$tNow['matin']['temperatureRessentie'] = $tab['hourly']['apparent_temperature'][$k];
		$tNow['matin']['precipitation'] = $tab['hourly']['precipitation_probability'][$k];
		$tNow['matin']['weatherCode'] = $tab['hourly']['weather_code'][$k];
		$tNow['matin']['couvertureNuage'] = $tab['hourly']['cloud_cover'][$k];
		$tNow['matin']['vent'] = $tab['hourly']['wind_speed_10m'][$k];
		$idx = (int)(((float)($tab['hourly']['wind_direction_10m'][$k])%360)/22.5);
		$tNow['matin']['directionVent'] = $tDirectionVent[$idx];
	}
	
	$k = array_search($today.'T14:00',$tab['hourly']['time']);
	
	if ($k!==false) {	
		$tNow['midi']['temperature'] = $tab['hourly']['temperature_2m'][$k];
		$tNow['midi']['temperatureRessentie'] = $tab['hourly']['apparent_temperature'][$k];
		$tNow['midi']['precipitation'] = $tab['hourly']['precipitation_probability'][$k];
		$tNow['midi']['weatherCode'] = $tab['hourly']['weather_code'][$k];
		$tNow['midi']['couvertureNuage'] = $tab['hourly']['cloud_cover'][$k];
		$tNow['midi']['vent'] = $tab['hourly']['wind_speed_10m'][$k];
		$idx = (int)(((float)($tab['hourly']['wind_direction_10m'][$k])%360)/22.5);
		$tNow['midi']['directionVent'] = $tDirectionVent[$idx];
	}
	
	$k = array_search($today.'T21:00',$tab['hourly']['time']);
	
	if ($k!==false) {	
		$tNow['soir']['temperature'] = $tab['hourly']['temperature_2m'][$k];
		$tNow['soir']['temperatureRessentie'] = $tab['hourly']['apparent_temperature'][$k];
		$tNow['soir']['precipitation'] = $tab['hourly']['precipitation_probability'][$k];
		$tNow['soir']['weatherCode'] = $tab['hourly']['weather_code'][$k];
		$tNow['soir']['couvertureNuage'] = $tab['hourly']['cloud_cover'][$k];
		$tNow['soir']['vent'] = $tab['hourly']['wind_speed_10m'][$k];
		$idx = (int)(((float)($tab['hourly']['wind_direction_10m'][$k])%360)/22.5);
		$tNow['soir']['directionVent'] = $tDirectionVent[$idx];
	}


	for($day=0;$day<7;$day++) {
		$tFuture[$day]['time'] = $tab['daily']['time'][$day];
		$tFuture[$day]['weatherCode'] = $tab['daily']['weather_code'][$day];
		$tFuture[$day]['temperatureMax'] = $tab['daily']['temperature_2m_max'][$day];
		$tFuture[$day]['temperatureMin'] = $tab['daily']['temperature_2m_min'][$day];
		$tFuture[$day]['temperatureMaxRessentie'] = $tab['daily']['apparent_temperature_max'][$day];
		$tFuture[$day]['temperatureMinRessentie'] = $tab['daily']['apparent_temperature_min'][$day];		
		$tFuture[$day]['lever'] = $tab['daily']['sunrise'][$day];				
		$tFuture[$day]['coucher'] = $tab['daily']['sunset'][$day];				
		$tFuture[$day]['UVMax'] = (int)$tab['daily']['uv_index_max'][$day];				
		$tFuture[$day]['vent'] = $tab['daily']['wind_speed_10m_max'][$day];				
		$idx = (int)(((float)($tab['daily']['wind_direction_10m_dominant'][$day])%360)/22.5);
		$tFuture[$day]['directionVent'] = $tDirectionVent[$idx];
		$tFuture[$day]['precipitation'] = $tab['daily']['precipitation_probability_max'][$day];				

	}
	
	return true;
}


/******************************
/**
/** Récupère la qualité de l'air
/**
/******************************/

function GetAirQuality($latitude,$longitude) {
	
	$latitude = (float)$latitude;
	$longitude = (float)$longitude;

	$urlToCall=MMETEO_AIRQURL.'?latitude='.urlencode($latitude).'&longitude='.urlencode($longitude).'&current=european_aqi,pm10,pm2_5,carbon_monoxide,nitrogen_dioxide,sulphur_dioxide,ozone,alder_pollen,birch_pollen,grass_pollen,mugwort_pollen,olive_pollen,ragweed_pollen&domains=cams_europe';
	
	$ch = curl_init( $urlToCall );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
	$result = curl_exec($ch);
	
	if ($result===false) {
		curl_close($ch);
		return false;
	}
	curl_close($ch);
	
	$tab=json_decode($result,true,20,JSON_INVALID_UTF8_SUBSTITUTE);

	$tOk = $tab['current'];
	return $tOk;
}
?>