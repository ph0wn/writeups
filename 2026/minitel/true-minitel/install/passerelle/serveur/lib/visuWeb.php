<?php
/**
 * @file visuWeb.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Novembre 2023
 * PHP 8.2 (CLI) version Unix
 *
 * License GPL v2 ou supérieure
 *
 * Affichage visuWeb / webMedia
 *
 */

function visuWeb($visuSocket,$objMiniPaviM,$objConfig,$tObjClient,$tObjClientH,$tStart,$wssGwUrl,$wsGwUrl,$ip) {

	if ($objConfig->sslCert!="") {
		if (!@stream_socket_enable_crypto($visuSocket,true,STREAM_CRYPTO_METHOD_TLSv1_3_SERVER)) {
			trigger_error("[MiniPavi-Visu] Impossible d'amorcer le SSL sur Visuweb");					
			return false;
		}
	}
	// Récupération des paramètres de l'url
	$tGetParams = array();
	$recv=array();
	do {
		$r = trim(fgets($visuSocket));
		if (count($tGetParams)==0) {
			$pregRes = @preg_match("/GET \/(.*) HTTP\/1\.1/", $r,$tPreg);
			if ($pregRes == 1 && @isset($tPreg[1])) {
				$tPreg[1] = str_replace('?','',$tPreg[1]);
				@parse_str($tPreg[1], $tGetParams);
			}
		}
		$recv[]=$r;
	} while($r!="");
	
	$tIp = explode(':',$ip);
	$ip = $tIp[0];
	
	$action = @$tGetParams['action'];

	if ($action == 'webmedia') {
		// Pas d'autorisation d'accès nécessaire pour le WebMedia
		if (!isset($tGetParams['pin'])) {
			return false;
		}
		$pin = $tGetParams['pin'];

		if (strlen($pin)!=4) {
			return false;
		}
		if (isset($tGetParams['lastevent'])) {
			$lastEvent = $tGetParams['lastevent'];
		} else $lastEvent = '';
		
		$origin='';
		foreach($recv as $hl) {
			if(strpos($hl,'Origin: ') === 0) {
				$origin = trim(substr($hl,8));
			}
		}
		$found = false;
		foreach($tObjClient as $k=>$o) {
			if ($pin == substr($o->uniqueId,-4)) {
				$found = true;
				break;
			}
		}
		
		if (!$found) {
			$tRep = array('result'=>'KO');
		} else {
			if ($o->objWebMedia->getRequest($type,$infos)) {
				$tRep = array('result'=>'OK','content'=>'1','type'=>$type,'infos'=>$infos);
				$o->sendToMainProc('shiftWebMedia',array('pid'=>$o->pid,'lastevent'=>$lastEvent));
			} else {
				$tRep = array('result'=>'OK','content'=>'0');
				$o->sendToMainProc('pingWebMedia',array('pid'=>$o->pid,'lastevent'=>$lastEvent));
			}
		}
		$body=json_encode($tRep);
		$send="HTTP/1.1 200 OK\r\nAccess-Control-Allow-Origin: $origin\r\nContent-Length: ".strlen($body)."\r\nContent-Type: application/json; charset=utf-8\r\n\r\n".$body;				
		return $send;
	}
	// Vérification autorisation d'accès
	if (!in_array($ip,$objConfig->tVisuwebAllowIp)) {
		$password = base64_encode($objConfig->httpUser.':'.$objConfig->httpPwd);
		$auth = "Authorization: Basic $password";
		
		if (!array_search($auth, $recv)) {
			
			trigger_error("[MiniPavi-Visu] Accès refuse de ".$ip);					
			$send = "HTTP/1.1 401 Unauthorized\r\nWWW-Authenticate: Basic realm=\"insert realm\", charset=\"UTF-8\"\r\n\r\n";
			return $send;
		}
	}

	if ($objConfig->sslCert!="" && $action == 'proxy' && isset($tGetParams['url'])) {
		// Mini basic web proxy (seulement en SSL)
		$urlToCall = $tGetParams['url'];
		trigger_error("[MiniPavi-Visu] Proxy url ".$urlToCall);					
		$ch = curl_init( $urlToCall );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		if ($result===false) {
			trigger_error("[MiniPavi-Visu] Proxy failed ");					
			$send = "HTTP/1.1 502 Bad Gateway\r\n\r\n";
			return $send;
		} else {
			trigger_error("[MiniPavi-Visu] Proxy success ");					
			return $result;
		}
	}

	if ($action != 'visujson') {
		$cssStyle = @file_get_contents('css/visu.css');
	}
	
	switch ($action) {
		case 'stats':
			// Visualisation des statistiques
			$m = (int)@$tGetParams['m'];
			$y = (int)@$tGetParams['y'];
			if ($m == 0 || $y==0) {
				$m=(int)date('m');
				$y=(int)date('Y');
			}
			$numDays=cal_days_in_month(CAL_GREGORIAN,$m,$y);
			$objStats = new Stats($objConfig->statsPath);
			$objStats->loadStats($m,$y);
			
			$selectedMonth[$m]='selected';
			$selectedYear[$y]='selected';
			
			$body="<html><style>$cssStyle</style><title>[".count($tObjClient)."] VisuWeb MiniPAVI</title><body><h1>MiniPAVI ".PAVI_VER." | <a href='/'>Visu direct</a> | Stats</h1>Démarré le ".date("d/m/Y H:i:s",$tStart);

			$body.="<br/><br/><form action='/' method='GET' >";
			$body.="<input type='hidden' name='action' value='stats' />";
			$body.="<select name='m'>";
			$body.="<option value='1' ".@$selectedMonth[1].">Janvier</option>";
			$body.="<option value='2' ".@$selectedMonth[2].">Février</option>";
			$body.="<option value='3' ".@$selectedMonth[3].">Mars</option>";
			$body.="<option value='4' ".@$selectedMonth[4].">Avril</option>";
			$body.="<option value='5' ".@$selectedMonth[5].">Mai</option>";
			$body.="<option value='6' ".@$selectedMonth[6].">Juin</option>";
			$body.="<option value='7' ".@$selectedMonth[7].">Juillet</option>";
			$body.="<option value='8' ".@$selectedMonth[8].">Août</option>";
			$body.="<option value='9' ".@$selectedMonth[9].">Septembre</option>";
			$body.="<option value='10' ".@$selectedMonth[10].">Octobre</option>";
			$body.="<option value='11' ".@$selectedMonth[11].">Novembre</option>";
			$body.="<option value='12' ".@$selectedMonth[12].">Décembre</option>";
			$body.="</select>&nbsp;";
			$body.="<select name='y'>";
			for($i=2024;$i<=date('Y');$i++) {
				$body.="<option value='$i' ".@$selectedYear[$i].">$i</option>";
			}
			$body.="</select>&nbsp;";
			$body.="<input type='submit' class='button-3' value = ' Voir les stats &#x2714;' /></form>";

			$body.="<br/><h3>Statistiques pour ".sprintf('%02d/%4d',$m,$y)."</h3>";

			$body.="<table class='rounded-corners' width='1000'>
			<tr>
			<th>&nbsp;</th><th colspan='2'>WS</th><th colspan='2'>WSS</th><th colspan='2'>AST</th><th colspan='2'>TELN</th><th colspan='3'>Total</th><th>&nbsp;</th><th colspan='2'>WS</th><th colspan='2'>WSS</th><th colspan='2'>AST</th><th colspan='2'>TELN</th><th colspan='3'>Total</th></tr>
			<tr class='head'><td>Jour</td><td>Nbr</td><td>Durée</td><td>Nbr</td><td>Durée</td><td>Nbr</td><td>Durée</td><td>Nbr</td><td>Durée</td><td>Nbr</td><td>Durée</td><td>TMC</td><td>Jour</td><td>Nbr</td><td>Durée</td><td>Nbr</td><td>Durée</td><td>Nbr</td><td>Durée</td><td>Nbr</td><td>Durée</td><td>Nbr</td><td>Durée</td><td>TMC</td>
			</tr>\n";
			$totalCountWS=0;
			$totalDurationWS=0;
			$totalCountWSS=0;
			$totalDurationWSS=0;
			$totalCountTELN=0;
			$totalDurationTELN=0;
			$totalCountAST=0;
			$totalDurationAST=0;
		
			for($d=1;$d<=$numDays;$d+=2) {
				$body.= '<tr>';
				
				for($i=0;$i<2 && ($d+$i)<=$numDays;$i++) {
					$day = sprintf('%02d',$d+$i);
					$totalCount=0;
					$totalDuration=0;
					$body.="<td class='head'>".$day.'</td>';
					if (isset($objStats->stats[$day])) {
						$body.='<td align="right">'.sprintf('%d',@$objStats->stats[$day][InCnx::WS_WEBSOCKET]['count']).'</td>';
						$mins = floor (@$objStats->stats[$day][InCnx::WS_WEBSOCKET]['duration'] / 60);
						$secs = @$objStats->stats[$day][InCnx::WS_WEBSOCKET]['duration'] % 60;
						$body.='<td align="right">'.sprintf('%d:%02d',$mins,$secs).'</td>';
						$totalCount+=@$objStats->stats[$day][InCnx::WS_WEBSOCKET]['count'];
						$totalDuration+=@$objStats->stats[$day][InCnx::WS_WEBSOCKET]['duration'];
						$totalCountWS+=@$objStats->stats[$day][InCnx::WS_WEBSOCKET]['count'];
						$totalDurationWS+=@$objStats->stats[$day][InCnx::WS_WEBSOCKET]['duration'];
						
						$body.='<td align="right">'.sprintf('%d',@$objStats->stats[$day][InCnx::WS_WEBSOCKETSSL]['count']).'</td>';
						$mins = floor (@$objStats->stats[$day][InCnx::WS_WEBSOCKETSSL]['duration'] / 60);
						$secs = @$objStats->stats[$day][InCnx::WS_WEBSOCKETSSL]['duration'] % 60;
						$body.='<td align="right">'.sprintf('%d:%02d',$mins,$secs).'</td>';
						$totalCount+=@$objStats->stats[$day][InCnx::WS_WEBSOCKETSSL]['count'];
						$totalDuration+=@$objStats->stats[$day][InCnx::WS_WEBSOCKETSSL]['duration'];
						$totalCountWSS+=@$objStats->stats[$day][InCnx::WS_WEBSOCKETSSL]['count'];
						$totalDurationWSS+=@$objStats->stats[$day][InCnx::WS_WEBSOCKETSSL]['duration'];
						
						$body.='<td align="right">'.sprintf('%d',@$objStats->stats[$day][InCnx::WS_ASTSOCKET]['count']).'</td>';
						$mins = floor (@$objStats->stats[$day][InCnx::WS_ASTSOCKET]['duration'] / 60);
						$secs = @$objStats->stats[$day][InCnx::WS_ASTSOCKET]['duration'] % 60;
						$body.='<td align="right">'.sprintf('%d:%02d',$mins,$secs).'</td>';
						$totalCount+=@$objStats->stats[$day][InCnx::WS_ASTSOCKET]['count'];
						$totalDuration+=@$objStats->stats[$day][InCnx::WS_ASTSOCKET]['duration'];
						$totalCountAST+=@$objStats->stats[$day][InCnx::WS_ASTSOCKET]['count'];
						$totalDurationAST+=@$objStats->stats[$day][InCnx::WS_ASTSOCKET]['duration'];

						$body.='<td align="right">'.sprintf('%d',@$objStats->stats[$day][InCnx::WS_TELNSOCKET]['count']).'</td>';
						$mins = floor (@$objStats->stats[$day][InCnx::WS_TELNSOCKET]['duration'] / 60);
						$secs = @$objStats->stats[$day][InCnx::WS_TELNSOCKET]['duration'] % 60;
						$body.='<td align="right">'.sprintf('%d:%02d',$mins,$secs).'</td>';
						$totalCount+=@$objStats->stats[$day][InCnx::WS_TELNSOCKET]['count'];
						$totalDuration+=@$objStats->stats[$day][InCnx::WS_TELNSOCKET]['duration'];
						$totalCountTELN+=@$objStats->stats[$day][InCnx::WS_TELNSOCKET]['count'];
						$totalDurationTELN+=@$objStats->stats[$day][InCnx::WS_TELNSOCKET]['duration'];
						
						$body.='<td align="right" class="total">'.sprintf('%d',$totalCount).'</td>';
						$mins = floor ($totalDuration / 60);
						$secs = $totalDuration % 60;
						$body.='<td align="right" class="total">'.sprintf('%d:%02d',$mins,$secs).'</td>';
						
						$mins = floor (($totalDuration/$totalCount) / 60);
						$secs = (int)($totalDuration/$totalCount) % 60;
						$body.='<td align="right" class="total">'.sprintf('%d:%02d',$mins,$secs).'</td>';
					} else {
						$body.='<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>';
					}
				}
				$body.='</tr>';
			}
			$body.='</table><br/><br/>';

			$totalCountAll = $totalCountTELN + $totalCountAST + $totalCountWSS + $totalCountWS;
			$totalDurationAll = $totalDurationTELN + $totalDurationAST + $totalDurationWSS + $totalDurationWS;
			
			$body.="<table width='1000' class='rounded-corners'>
			<tr>
			<th colspan='2'>WS</th><th colspan='2'>WSS</th><th colspan='2'>AST</th><th colspan='2'>TELN</th><th colspan='3'>Total</th></tr>
			<tr class='head'>
			<td>Cnx</td><td>Durée</td><td>Cnx</td><td>Durée</td><td>Cnx</td><td>Durée</td><td>Cnx</td><td>Durée</td><td>Cnx</td><td>Durée</td><td>TMC</td></tr>
			<tr>";
			$body.="<td>".sprintf('%d',$totalCountWS)."</td>";
			$mins = floor ($totalDurationWS / 60);
			$secs = $totalDurationWS % 60;
			$body.='<td>'.sprintf('%d:%02d',$mins,$secs).'</td>';

			$body.="<td>".sprintf('%d',$totalCountWSS)."</td>";
			$mins = floor ($totalDurationWSS / 60);
			$secs = $totalDurationWSS % 60;
			$body.='<td>'.sprintf('%d:%02d',$mins,$secs).'</td>';

			$body.="<td>".sprintf('%d',$totalCountAST)."</td>";
			$mins = floor ($totalDurationAST / 60);
			$secs = $totalDurationAST % 60;
			$body.='<td>'.sprintf('%d:%02d',$mins,$secs).'</td>';

			$body.="<td>".sprintf('%d',$totalCountTELN)."</td>";
			$mins = floor ($totalDurationTELN / 60);
			$secs = $totalDurationTELN % 60;
			$body.='<td>'.sprintf('%d:%02d',$mins,$secs).'</td>';
			
			$body.="<td class='total'>".sprintf('%d',$totalCountAll)."</td>";
			$mins = floor ($totalDurationAll / 60);
			$secs = $totalDurationAll % 60;
			$body.='<td class="total">'.sprintf('%d:%02d',$mins,$secs).'</td>';
			if ($totalCountAll>0) {
				$mins = floor (($totalDurationAll/$totalCountAll) / 60);
				$secs = (int)($totalDurationAll/$totalCountAll) % 60;
				$body.='<td class="total">'.sprintf('%d:%02d',$mins,$secs).'</td>';
			} else {
				$body.='<td class="total">&nbsp;</td>';
			}
			
			$body.='</tr></table><br/>';
			
			break;
			
		case 'visujson':
		default:
		// Visualisation des connexions
		$l = percentLoadAvg();
		$tRes['loadavg'] = @$l[0];
		$tRes['freespace'] = number_format((disk_free_space('.')/1024)/1024,0,',',' ');
		$tRes['cnxtotal'] = count($tObjClient);
		$tRes['cnx']=array();
		$k=0;
		
		foreach($tObjClient as $cli) {
			$typeSocket=$cli->inCnx->getDirection().'/'.$cli->inCnx->getTypeSocket();
			
			$tRes['cnx'][$k]['type']=$typeSocket;
			$tRes['cnx'][$k]['direction']=$cli->inCnx->getDirection();
			$tRes['cnx'][$k]['uniqueid']=$cli->uniqueId;
			$tRes['cnx'][$k]['ip']=$cli->clientIp;
			$tRes['cnx'][$k]['pid']=$cli->pid;
			$tRes['cnx'][$k]['tcnx']=date("d/m/Y H:i:s",$cli->tCnx);
			if ($cli->inCnx->getDirection()=='IN') {
				$tRes['cnx'][$k]['tlastaction']=date("H:i:s",$cli->tLastAction);
				$duree = $cli->tLastAction-$cli->tCnx;
				$mins = floor ($duree / 60);
				$secs = $duree % 60;
				$tRes['cnx'][$k]['duree']=sprintf('%d:%02d',$mins,$secs);
			} else {
				$tRes['cnx'][$k]['tlastaction']='--';
				$duree = time()-$cli->tCnx;
				$mins = floor ($duree / 60);
				$secs = $duree % 60;
				$tRes['cnx'][$k]['duree']=sprintf('%d:%02d',$mins,$secs);
			}
			$tRes['cnx'][$k]['url']=$cli->url;
			$tRes['cnx'][$k]['infos']=$cli->infos;
			$k++;
		}
		
		if ($action == '') {
			$body="<html><meta http-equiv='refresh' content='30'><style>$cssStyle</style>
			<title>[".$tRes['cnxtotal']."] VisuWeb MiniPAVI</title><body><h1>MiniPAVI ".PAVI_VER." | Visu direct | <a href='/action=stats'>Stats</a></h1>Démarré le ".date("d/m/Y H:i:s",$tStart);
			$body.="<br/><br/><table class='rounded-corners' width='1000'><tr><td style='font-weight:bold;'>Nbr connexions max:</td><td>".$objConfig->maxCnx."</td>";
			$body.="<td style='font-weight:bold;'>WS Port:</td><td>".$objConfig->wsPort."</td>";
			$body.="<td style='font-weight:bold;'>WSS Port:</td><td>".$objConfig->wssPort."</td>";
			$body.="<td style='font-weight:bold;'>TELN Port:</td><td>".$objConfig->tcpPort."</td>";
			$body.="<td style='font-weight:bold;'>ASTERISK Port:</td><td>".$objConfig->astPort."</td></tr>";	
			$body.="<tr><td style='font-weight:bold;' colspan='4'>Service par défaut:</td><td colspan='6'>".$objConfig->defaultUrl."</td></tr>";
			$body.="<tr><td style='font-weight:bold;' colspan='4'>Logpath:</td><td colspan='6'>".$objConfig->logPath."</td></tr>";					
			$body.="<tr><td style='font-weight:bold;' colspan='4'>Statspath:</td><td colspan='6'>".$objConfig->statsPath."</td></tr></table>";										
			
			$body.="<h2>".$tRes['cnxtotal']." connexion(s) en cours | ".date("d/m/Y H:i:s")." | Usage CPU ".number_format((float)$tRes['loadavg']*100,2,',',' ')."% | Disque ".$tRes['freespace']." Mo libres</h2><br/>";
			
			$body.="<table width='1000' class='rounded-corners'><tr><th>ID</th><th>IP</th><th>Type</th><th>PID</th><th>Heure connexion</th><th>Dernière activité</th><th>URL</th><th>Infos</th></tr>";
			
			foreach($tRes['cnx'] as $cli) {
				if ($objConfig->extCallUrl!='' && $objConfig->viewer!='' && $objConfig->visuKey!='' && $cli['direction']=='IN' && ($wssGwUrl!='' || $wsGwUrl!='') ) {
					if ($wssGwUrl!='') $gw = $wssGwUrl;
					else $gw = $wsGwUrl;
					$body.="<tr><td><a onclick=\"window.open('".$objConfig->viewer."?nowebmedia=1&gw=".urlencode($gw)."&url=".urlencode($objConfig->extCallUrl."?key=".urlencode($objConfig->visuKey)."&uniqueid=".urlencode($cli['uniqueid']))."', '_blank', 'location=yes,height=800,width=780,scrollbars=yes,status=yes');\"  style='color:#ff850f;cursor:pointer;'><nobr>".$cli['uniqueid']." &#128471;</nobr></a></td><td>".$cli['ip']."</td><td><nobr>".$cli['type']."</nobr></td><td>".$cli['pid']."</td><td>".$cli['tcnx']."</td><td>".$cli['tlastaction']." [".$cli['duree']."]</td><td>".$cli['url']."</td><td>".$cli['infos']."</td></tr>";
				} else
					$body.="<tr><td>".$cli['uniqueid']."</td><td>".$cli['ip']."</td><td><nobr>".$cli['type']."</nobr></td><td>".$cli['pid']."</td><td>".$cli['tcnx']."</td><td>".$cli['tlastaction']." [".$cli['duree']."]</td><td>".$cli['url']."</td><td>".$cli['infos']."</td></tr>";
			}

			$body.="</table><h3>Dernières connexions</h3>";
			$t = array_reverse($tObjClientH);
			
			$body.="<table width='1000' class='rounded-corners'><tr><th>ID</th><th>IP</th><th>Type</th><th>PID</th><th>Heure connexion</th><th>Dernière activité</th><th>URL</th><th>Infos</th></tr>";
			foreach($t as $cli) {
				$typeSocket=$cli->inCnx->getDirection().'/'.$cli->inCnx->getTypeSocket();
				
				if ($cli->inCnx->getDirection()=='IN') {
					$duree = $cli->tLastAction-$cli->tCnx;
					$mins = floor ($duree / 60);
					$secs = $duree % 60;
					$duree=sprintf('%d:%02d',$mins,$secs);
				} else {
					$duree = $cli->tLastAction-$cli->tCnx;
					$mins = floor ($duree / 60);
					$secs = $duree % 60;
					$duree=sprintf('%d:%02d',$mins,$secs);
				}
				if ($objConfig->viewer == '' || $cli->inCnx->getDirection() != 'IN' || ($objConfig->wssPort<=0 && $objConfig->wsPort<=0) ) {
					$body.="<tr><td>".$cli->uniqueId."</td><td>".$cli->clientIp."</td><td><nobr>".$typeSocket."</nobr></td><td>".$cli->pid."</td><td>".date("d/m/Y H:i:s",$cli->tCnx)."</td><td>".date("H:i:s",$cli->tLastAction)." [".$duree."]</td><td>".$cli->url."</td><td>".$cli->infos."</td></tr>";								
				} else {
					if ($wssGwUrl!='')
						$prm = $wssGwUrl.'?streamuniqueid='.$cli->uniqueId;
					else $prm = $wsGwUrl.'?streamuniqueid='.$cli->uniqueId;
					
					$prm = urlencode($prm);
					$body.="<tr><td><a onclick=\"window.open('".$objConfig->viewer."?nowebmedia=1&gw=".$prm."', '_blank', 'location=yes,height=800,width=780,scrollbars=yes,status=yes');\"  style='color:#ff850f;cursor:pointer;'><nobr>".$cli->uniqueId." &#128471;</nobr></a></td><td>".$cli->clientIp."</td><td><nobr>".$typeSocket."</nobr></td><td>".$cli->pid."</td><td>".date("d/m/Y H:i:s",$cli->tCnx)."</td><td>".date("H:i:s",$cli->tLastAction)." [".$duree."]</td><td>".$cli->url."</td><td>".$cli->infos."</td></tr>";
				}
			}
			$body.="</table><h3>Derniers logs</h3>";
			$lines=$objMiniPaviM->getLastLines();
			$lines=array_reverse($lines);
			foreach($lines as $line) {
				$body.="<code>".trim($line)."<br/></code>";
			}
		}
	}
	
	if ($action != 'visujson') {
		$contentType = 'text/html';
		$body.="<hr>MiniPAVI <a href='http://www.minipavi.fr' target='_blank' style='color:white;'>www.minipavi.fr</a> - PHP ".phpversion()."</body></html>";
	} else {
		$body = json_encode($tRes);
		$contentType = 'application/json';
	}
	$send="HTTP/1.1 200 OK\r\nContent-Length: ".strlen($body)."\r\nContent-Type: ".$contentType."; charset=utf-8\r\n\r\n".$body;		
	return $send;
}