<?php
/**
 * @file france24Functions.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Novembre 2023
 *
 * Fonctions utlisées dans le script France24
 * 
 */

 $urlFlux[0]['name']='Monde';
 $urlFlux[1]['name']='Europe';
 $urlFlux[2]['name']='France';
 $urlFlux[3]['name']='Afrique';
 $urlFlux[4]['name']='Moyen Orient';
 $urlFlux[5]['name']='Amériques';
 $urlFlux[6]['name']='Asie Pacifique';
 
 $urlFlux[0]['url']='https://www.france24.com/fr/rss';
 $urlFlux[1]['url']='https://www.france24.com/fr/europe/rss';
 $urlFlux[2]['url']='https://www.france24.com/fr/france/rss';
 $urlFlux[3]['url']='https://www.france24.com/fr/afrique/rss';
 $urlFlux[4]['url']='https://www.france24.com/fr/moyen-orient/rss';
 $urlFlux[5]['url']='https://www.france24.com/fr/ameriques/rss';
 $urlFlux[6]['url']='https://www.france24.com/fr/asie-pacifique/rss';
 
  
function getNews($idx) {
	global $urlFlux;
	$tRes=array();
	
	if (!isset($urlFlux[$idx]))
		return $tRes;
	
	$fichier = $urlFlux[$idx]['url'];
	$dom = new DOMDocument();
	if (!$dom->load($fichier)) {
		return $tRes;
	}
	
	$i=0;
	$itemList = $dom->getElementsByTagName('item');
	foreach ($itemList as $item) {
		$titre = $item->getElementsByTagName('title');
		if ($titre->length > 0) {
			$string = $titre->item(0)->nodeValue;
			$tRes[$i]['titre']=$string;
			
		} else {
			$tRes[$i]['titre']='Sans titre';
		}

		$desc = $item->getElementsByTagName('description');
		if ($desc->length > 0) {
			$string = $desc->item(0)->nodeValue;
			$tRes[$i]['desc']=$string;
			
		} else {
			$tRes[$i]['desc']='Article vide';
		}
		
		$date = $item->getElementsByTagName('pubDate');
		if ($date->length > 0) {
			$tRes[$i]['date']=trim($date->item(0)->nodeValue);
		} else {
			$tRes[$i]['date']='Sans date';
		}
		$i++;
	}
	return $tRes;
}
 ?>