<?php
/**
 * @file class.WebSocket.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Mars 2024
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * Envoi et réception sur websockets
 *
 * License GPL v2 ou supérieure
 *
 *
 *	Trame websocket RFC6455:
 *  
 *      0                   1                   2                   3
 *      0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *     +-+-+-+-+-------+-+-------------+-------------------------------+
 *     |F|R|R|R| opcode|M| Payload len |    Extended payload length    |
 *     |I|S|S|S|  (4)  |A|     (7)     |             (16/64)           |
 *     |N|V|V|V|       |S|             |   (if payload len==126/127)   |
 *     | |1|2|3|       |K|             |                               |
 *     +-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
 *     |     Extended payload length continued, if payload len == 127  |
 *     + - - - - - - - - - - - - - - - +-------------------------------+
 *     |                               |Masking-key, if MASK set to 1  |
 *     +-------------------------------+-------------------------------+
 *     | Masking-key (continued)       |          Payload Data         |
 *     +-------------------------------- - - - - - - - - - - - - - - - +
 *     :                     Payload Data continued ...                :
 *     + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
 *     |                     Payload Data continued ...                |
 *     +---------------------------------------------------------------+
 **/
class WebSocket {

	/*************************************************
	// Récupère les paramètres de la requête GET
	// reponse: requête
	// Retourne le tableau des paramètres ou false
	**************************************************/
	static function getGetRequest($response) {
		$t=array();
		$pGet=array();
		if (stripos($response, 'GET /?') === 0) {
			$req = mb_substr($response,6);
			$i=stripos($req, ' ');
			$req = mb_substr($req,0,$i);
			
			if (mb_strlen($req)>0) {
				$t=explode('&',$req);
			}
			foreach($t as $k=>$p) {
				$t2=explode('=',$p);
				if (count($t2)==2) {
					$pGet[''.$t2[0]]=urldecode($t2[1]);
				}
			}
			return $pGet;
		} 
		return false;
	}	


	/*************************************************
	// Accepte une connexion WS
	// header: Entête de la requête reçue
	// socket: socket de la connexion
	// serverAdress: adresse du serveur
	// serverPort: port du serveur	
	// Retourne les paramètres de la requête
	*************************************************/
	static function handshake($header, $socket,$ServerAdress,$ServerPort) {
		$cnxParams = array();
		$headers = array();
		
		if ($header !==null) {
			$lines = preg_split("/\r\n/", $header);
			foreach($lines as $line) {
				$line = chop($line);
				if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
					$headers[$matches[1]] = $matches[2];
				}
				$res = self::getGetRequest($line);
				if ($res !== false) {
					$cnxParams = $res;
				}
			}
		} 
		
		$secKey = @$headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		
		$buffer = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n";
		$buffer.= "Upgrade: websocket\r\n";
		$buffer.= "Connection: Upgrade\r\n";
		$buffer.= "WebSocket-Origin: ".$ServerAdress."\r\n";
		$buffer.= "WebSocket-Location: ws://".$ServerAdress.':'.$ServerPort."/\r\n";
		$buffer.= "Sec-WebSocket-Accept:$secAccept\r\n\r\n";


		fwrite($socket,$buffer,strlen($buffer));
		return $cnxParams;
	}

	/*************************************************
	// Création des frames à partir des données fournies
	// Les données doivent être non-masquées si envoi serveur->client
	// socketData: données 
	// opcode: code opération
	// masked : données masquées (true) ou non (false)
	// Retourne les frames
	*************************************************/
	static function encodeFrame($socketDatas,$opcode=0x01,$masked=false) {
		
		$toSend = '';
		
		while (strlen($socketDatas)>0) {
			$header = '';
			$datas = substr($socketDatas,0,65535);
			$socketDatas = substr($socketDatas,65535);
			$header = chr(0x80 | $opcode); 
			
			if ($masked) 
				$mchar=0x80;
			else $mchar = 0;
			$length = strlen($datas);
			if ($length < 126) 
				$header.= chr($mchar | $length);
			else  
				$header.= chr($mchar | 126) . pack("n", $length);
			
			// Ajout du masque éventuel
			
			if ($masked) {
				$mask = pack("N", rand(1, 0x7FFFFFFF));
				$header .= $mask;
				// Masquage des données
				for ($i = 0; $i < $length; $i++) {
					$datas[$i] = $datas[$i] ^ $mask[$i % 4];
				}
			}
			$toSend.=$header.$datas;
		}
		return $toSend;
	}

	/*************************************************
	// Décode les données reçues à partir d'une ou plusieurs frames WS
	// avec réponse auto aux pings reçus
	// socketData: données (frame)
	// pongReceived: positionné à true si pong reçu
	// dataBuff: données précédemment reçues et non traitées (frame partielle)
	// socket: pour envoi de pong si ping reçu
	// Retourne les données reçues décodées
	*************************************************/
	
	static public function decodeFrame($socketDatas,&$pongReceived,&$dataBuff,$socket=null) {
		$socketDatas = $dataBuff.$socketDatas;
		$dataBuff = '';
		$totalRec = strlen($socketDatas);

		if ($totalRec<2) {
			$dataBuff = $socketDatas;
			return true;
		}
		
		$retDatas = '';
		$start = 0;
		$pongHasChanged = false;
		
		while($start<$totalRec) {
			$socketData = substr($socketDatas,$start);
			$dataBuff = $socketData;
			
			if (!isset($socketData[0]))  {
				return $retDatas;
			}
			
			$opcode = ord($socketData[0]) & 15;
			
			if ($opcode != 0x01 && $opcode != 0x02 && $opcode != 0x09 && $opcode != 0x0A) {
				return $retDatas;
			}
			
			if (!isset($socketData[1])) {
				return $retDatas;
			}
			
			$length = ord($socketData[1]) & 0x7F;
			$masked = ord($socketData[1]) & 0x80;

			$extLength=0;
			if ($length >= 0x7E) {
				$extLength = 2;
				if ($length == 0x7F) {
					$extLength = 8;
				}
				
				if ((!$masked && strlen($socketData)<2+$extLength) || ($masked && strlen($socketData)<6+$extLength)) {
					return $retDatas;
				}
				
				$length = 0;
				for ($i = 0; $i < $extLength; $i++)
					$length += ord($socketData[2+$i]) << ($extLength - $i - 1) * 8;
			}

			if ((!$masked && strlen($socketData)<2+$extLength+$length) || ($masked && strlen($socketData)<6+$extLength+$length)) {
				// Pas assez de données reçues
				return $retDatas;
			}
			
			if ($masked) {
				$masks = substr($socketData, 2+$extLength, 4);
				$data = substr($socketData, 6+$extLength,$length);
				$start += 6+$extLength+$length;
			} else {
				$data = substr($socketData, 2+$extLength,$length);
				$start += 2+$extLength+$length;
			}
		
			$socketData = "";

			if ($masked) {
				// Démasquage des données 
				for ($i = 0;$i < strlen($data); ++$i) {
					$socketData .= $data[$i] ^ $masks[$i%4];
				}
			} else {
				$socketData = $data;
			}
			if ($opcode == 0x09 && $socket) {
				// Ping recu-> envoi Pong
				if ($masked)
					$frame = self::encodeFrame($socketData,0x0A,false);
				else $frame = self::encodeFrame($socketData,0x0A,true);
				$socketData='';
				fwrite($socket, $frame);
			} else if ($opcode == 0x0A) {
				// Pong reçu
				$socketData='';
				$pongReceived = true;
				$pongHasChanged = true;
			} else if ($opcode != 0x09 ){
				$retDatas.=$socketData;
			}
			$dataBuff = '';
		}
		$dataBuff = substr($socketDatas,$start);
		if ($pongHasChanged && $retDatas=='')
			return true;
		return $retDatas;
	}
}
