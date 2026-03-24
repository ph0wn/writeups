<?php
/**
 * @file miniPaviAccFunctions.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Février 2024
 *
 * Fonctions utlisées pour le service miniPaviAcc
 *
 * Licence GNU GPL v2 ou superieure
 */


/*****************
*** Lit le fichier de configuraton
*** configFile: fichier de configuration
*** tConfig: tableau contenant la configuration ou objet LibXMLError si erreur
*** Retourne true si ok, false si erreur
******************/

function getConfig($configFile,&$tConfig) {
	$tConfig=array();
	$tConfig = array();
	
	libxml_use_internal_errors(true);
	
	$objXML = simplexml_load_file($configFile,null,LIBXML_NOCDATA|LIBXML_NOBLANKS);
	if ($objXML === false || $objXML->getName() != 'config') {
		$tConfig = libxml_get_errors();
		return false;
	}
	
	
	foreach ($objXML as $elementName=>$obj) {
		if ($elementName == 'annuaire') {
			foreach ($obj as $annuElementName=>$annuObj) {		
				if ($annuElementName == 'service') 	{
					if (isset($annuObj['code']) && strlen(trim((string)$annuObj['code']))>=2) {
						$annuObj['code'] = trim((string)$annuObj['code']);
						$k = count($tConfig);
						$tConfig[$k]['code']=strtoupper((string)$annuObj['code']);
						if (isset($annuObj->url))
							$tConfig[$k]['url']=(string)$annuObj->url;
						else $tConfig[$k]['url']='';
						if (isset($annuObj->infos))
							$tConfig[$k]['infos']=(string)$annuObj->infos;
						else $tConfig[$k]['infos']='';
					}
				}
			}
		}
	}
	return true;
}

/*****************
*** Récupère le lien d'un service en fonction du code
*** code : code du service
*** tConfig: tableau de la configuration
*** Retourne le lien si trouvé, sinon false
******************/

function getUrlFromCode($code,$tConfig) {
	$code = strtoupper(trim($code));
	foreach($tConfig as $service) {
		if ($service['code'] == $code) {
			return $service['url'];
		}
	}
	return false;
}

?>