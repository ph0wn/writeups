<?php
/**
 * @file class.MiniPavi.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.3 Novembre 2023 - Août 2024
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * License GPL v2 ou supérieure
 *
 * 14/02/2024 : ajout redirection auto vers interpreteur XML si XML détécté
 * 15/03/2024 : ajout addCommandConnectToExt et addCommandConnectToTln
 * 01/05/2024 : ajout addCommandDuplicateStream
 * 13/09/2024 : ajout fonctions pour l'économiseur d'écran
 * 11/10/2024 : ajout addCommandInputForm
 * 08/07/2025 : ajout addCommandSetParam
 */


declare(ticks = 1); 

class MiniPavi {

	const VDT_CLR = "\x0C";
	const VDT_G0 = "\x0F";
	const VDT_G1 = "\x0E";
	const VDT_G2 = "\x19";
	const VDT_POS = "\x1F";
	const VDT_CURON = "\x11";
	const VDT_CUROFF = "\x14";
	const VDT_CLRLN = "\x18";
	const VDT_SZNORM = "\x1B\x4C";
	const VDT_SZNDBLH = "\x1B\x4D";
	const VDT_SZNDBLW = "\x1B\x4E";
	const VDT_SZNDBLHW = "\x1B\x4F";
	const VDT_TXTBLACK = "\x1B@";
	const VDT_TXTRED = "\x1BA";
	const VDT_TXTGREEN = "\x1BB";
	const VDT_TXTYELLOW = "\x1BC";
	const VDT_TXTBLUE = "\x1BD";
	const VDT_TXTMAGENTA = "\x1BE";
	const VDT_TXTCYAN = "\x1BF";
	const VDT_TXTWHITE = "\x1BG";

	const VDT_BGBLACK = "\x1BP";
	const VDT_BGRED = "\x1BQ";
	const VDT_BGGREEN = "\x1BR";
	const VDT_BGYELLOW = "\x1BS";
	const VDT_BGBLUE = "\x1BT";
	const VDT_BGMAGENTA = "\x1BU";
	const VDT_BGCYAN = "\x1BV";
	const VDT_BGWHITE = "\x1BW";

	const VDT_BLINK = "\x1BH";
	const VDT_FIXED = "\x1BI";

	const VDT_STOPUNDERLINE = "\x1BY";
	const VDT_STARTUNDERLINE = "\x1BZ";

	const VDT_FDNORM = "\x1B\\";
	const VDT_FDINV = "\x1B]";


	const VDT_PRO1_VER = "\x1B\x39\x7B";
	const VDT_PRO2_NOACK_MODEM = "\x1B\x3A\x64\x52";
	const VDT_PRO2_NOACK_PRISE = "\x1B\x3A\x64\x53";
	const VDT_PRO2_NOACK_ECRAN = "\x1B\x3A\x64\x50";
	const VDT_PRO2_NOACK_CLAVIER = "\x1B\x3A\x64\x51";
	const VDT_PRO3_ECHO_OFF = "\x1B\x3B\x60\x58\x52";
	const VDT_PRO3_ECHO_ON = "\x1B\x3B\x61\x58\x52";
	const VDT_PRO2_ROULEAU_ON = "\x1B\x3A\x69\x43";
	const VDT_PRO2_ROULEAU_OFF = "\x1B\x3A\x6A\x43";
	const VDT_PRO2_MINUSCULES_ON = "\x1B\x3A\x69\x45";
	const VDT_PRO2_MINUSCULES_OFF = "\x1B\x3A\x6A\x45";

	const FCT_SOMMAIRE = 70;
	const FCT_ANNULATION = 69;
	const FCT_RETOUR = 66;
	const FCT_REPETITION = 67;
	const FCT_GUIDE = 68;
	const FCT_CORRECTION = 71;
	const FCT_SUITE = 72;
	const FCT_ENVOI = 65;
	const FCT_CNX = 73;
	const FCT_CNX2 = 89;

	const MSK_SOMMAIRE = 1;
	const MSK_ANNULATION = 2;
	const MSK_RETOUR = 4;
	const MSK_REPETITION = 8;
	const MSK_GUIDE = 16;
	const MSK_CORRECTION = 32;
	const MSK_SUITE = 64;
	const MSK_ENVOI = 128;

	const MINIPAVI_LOGFILE = './';

	const MINIPAVI_SOCKETDIR = 'sockets/';		// Emplacement des sockets locales Unix
	const MINIPAVI_SOCKETUSAGE_CALL = 0;			// Socket locale pour connexion VoIP
	const MINIPAVI_SOCKETUSAGE_STREAMDUPL_RX = 1;	// Socket locale en réception de duplication de données
	const MINIPAVI_SOCKETUSAGE_STREAMDUPL_TX = 2;	// Socket locale en transmission de duplication de données
	
	public $uniqueId;
	public $clientIp;
	public $inCnx;
	public $pid;
	public $mainPid;
	public $versionMinitel;
	
	public $stepCnx;
	public $tCnx;
	public $tLastAction;
	public $tLastData;
	public $tLastWakeUp;
	public $echo=true;
	public $url;
	public $command=false;
	public $fromDefault=false;
	
	public $infos;				// Infos libre
	
	private $currX=0;
	private $currY=0;
	
	private $serviceResult;
	private $count;
	private $bufferIdx;
	private $buffer=array();
	
	private $filtered = array();
	private $idxFiltered=0;
	private $ignoreCount=0;
	private $filterBuffer='';
	
	public $commSockets;	//0: child, 1:parent
	
	public $context;
	public $directCallNum;
	
	public $bufferOut;
	
	public $tempBuffer='';
	public $screenBuffer='';
	public $screenSaver=false;
	public $screenSaverType=2;			// 0: Désactivé, 1: Maintient écran, 2: Smiley, 3: Texte modifiable
	public $screenSaverText='Touch my keyboard!';			// Texte si screenSaverType = 3
	
	public $tSimulateUser = array();
	
	public $lpath;
	private $tLocalSocket;
	public $extCallChannel;
	private $fRecord;
	public $objWebMedia;
	
	
	
	/*************************************************
	// Ecrit dans le fichier de logs 
	// msg: message à écrire
	*************************************************/
	
	function log($msg) {
		if ($this->lpath=='')
			$this->lpath='./';
		else if (substr($this->lpath,-1)!='/')
			$this->lpath.='/';
		
		$f = fopen($this->lpath.date("Ym").".log",'a');
		if ($f) {
			flock($f,LOCK_EX);
			$msg="[".date("d/m/Y H:i:s")."] ".$msg."\r\n";
			fputs($f,$msg);
			fclose($f);
		}
	}
	

	/*************************************************
	// Récupère les dernières lignes du fichier de logs
	// num: nombre de lignes à récupérer
	// Retourne un tableau des dernières lignes
	*************************************************/
	
	function getLastLines() {
		$lines = array();
		$file = $this->lpath.date("Ym").".log";
		$file = escapeshellarg($file);
		$content = `tail -n 100 $file`;
		$lines = explode("\n",$content);
		return $lines;
	}
	
	/*************************************************
	// Constructeur
	// lPath: Fichier de logs
	*************************************************/
	
	function __construct($lPath) {
		$this->lpath = $lPath;
		$this->stepCnx=0;
		$this->context='';
		$this->directCallNum=0;
		$this->tempBuffer=false;
		$this->commSockets = null;
		$this->tLocalSocket = array();
		$this->extCallChannel = null;
		$this->fRecord = null;
		$this->infos = '';		
		$this->versionMinitel='';
		$this->objWebMedia = new WebMedia();
	}
	
	
	/*************************************************
	// Initialisation
	// Appellé lorsque il s'agit d'une connexion client
	// inCnx: objet inCnx représentant la connexion
	// ip: ip
	// mainPid: PID du processus principal
	// pin: code pin à 4 chiffres (utilisé pour WebMedia)
	*************************************************/
	
	function init($inCnx,$ip,$mainPid,$pin='') {
		if ($pin == '')
			$pin = rand(1000,1999);
		$this->uniqueId = time().$pin;
		$this->clientIp = $ip;
		$this->inCnx= clone($inCnx);
		$this->tCnx=$this->tLastAction=$this->tLastData=$this->tLastWakeUp=time();
		$this->mainPid = $mainPid;
	}

	/*************************************************
	// Ajoute des données à envoyer au buffer de sortie
	// vdt: données
	// atStart: si 'true', données ajoutées au début du buffer
	*************************************************/
	
	function addToBufferOut($vdt,$atStart=false) {
			if ($atStart) 
				$this->bufferOut=$vdt.$this->bufferOut;
			else
				$this->bufferOut.=$vdt;
	}
	
	
	function setVersionMinitel($type) {
		if (strlen($type) == 5 && $type[0] == "\x01" && $type[4] == "\x04") {
			trigger_error('[MiniPavi-class] setVersionMinitel -> '.$type);
			$this->versionMinitel=trim($type,"\x01\x04");
		}
	}

	/*************************************************
	// Filtre de certaines séquences
	// datas: données (reçues)
	// Note: Les données filtrées sont stockées pour un futur traitement (Todo..)
	// Retourne les données après filtrage
	*************************************************/

	function filterDatas($datas) {
		$seq = array(
		0=>array('seq'=>"\x1B\x39",'ic'=>1,'replace'=>'','func'=>null),
		1=>array('seq'=>"\x1B\x3A",'ic'=>2,'replace'=>'','func'=>null),
		2=>array('seq'=>"\x1B\x3B",'ic'=>3,'replace'=>'','func'=>null),
		3=>array('seq'=>"\x1B\x5B\x43",'ic'=>0,'replace'=>' ','func'=>null),
		4=>array('seq'=>"\x1B\x5B\x44",'ic'=>0,'replace'=>"\x13\x47",'func'=>null),
		5=>array('seq'=>"\x1B\x5B\x41",'ic'=>0,'replace'=>"\x13\x42",'func'=>null),
		6=>array('seq'=>"\x1B\x5B\x42",'ic'=>0,'replace'=>"\x13\x48",'func'=>null),
		7=>array('seq'=>"\x01   \x04",'ic'=>0,'replace'=>'','func'=>array($this,'setVersionMinitel'))
		);
		
		$retDatas='';
			
		$lengthDatas = strlen($datas);
		for($idxDatas=0;$idxDatas<$lengthDatas;$idxDatas++) {
			$c = $datas[$idxDatas];
			if ($this->ignoreCount>0) {
				trigger_error('[MiniPavi-class] Ignoré '.ord($c));
				if (isset($this->filtered[$this->idxFiltered]))
					$this->filtered[$this->idxFiltered].=$c;
				else $this->filtered[$this->idxFiltered]=$c;
				$this->ignoreCount--;
				if ($this->ignoreCount==0) {
					$this->idxFiltered++;
					$this->filterBuffer='';
				}
				continue;
			}
			
			$this->filterBuffer.=$c;
			$tbuffer = str_split($this->filterBuffer);
			$release = true;
			
			foreach($seq as $k=>$v) {
				
				foreach($tbuffer as $p=>$c) {
					if (isset($seq[$k]['seq'][$p]) && $seq[$k]['seq'][$p] == ' ') {
						$seq[$k]['seq'][$p] = $c;
					} 
				}
				
				if ($this->filterBuffer == $seq[$k]['seq']) {
					$this->ignoreCount = $seq[$k]['ic'];
					$retDatas.=$seq[$k]['replace'];
					if ($seq[$k]['func'] != null) {
						call_user_func($seq[$k]['func'],$this->filterBuffer);
					}
					$this->filterBuffer = '';
					break;
				} else 	if (strpos($seq[$k]['seq'],$this->filterBuffer) === 0) {
					$release = false;
				}
			}
			if ($release) {
				$retDatas.=$this->filterBuffer;
				$this->filterBuffer='';					
			}
		}
			
		return $retDatas;
	}


	/*************************************************
	// Limitation des données reçues de la part du service
	// Fonction appellée par Curl
	*************************************************/
	
	function curlCallback($ch,$dlTotal, $dlNow, $ulTotal, $ulNow) {
		if ($dlNow > 65000)
			return 1;
		return 0;
	}
	

	/*************************************************
	// Envoi des données du buffer vers le service par requête http
	// fctn: touche de fonction (ENVOI, SUITE, etc.), ou évenement (DIRECT,DIRECTCNX, etc..) , ayant initié l'envoi
	// Retourne 'true' si ok, autrement false.
	*************************************************/
	
	function sendToService($fctn) {
		
		if ($this->url == null)
			return false;
		
		$this->serviceResult='';
		$urlParams=array();
		$elemUrl=parse_url($this->url);
		@parse_str(@$elemUrl['query'],$urlParams);
		
		foreach($this->buffer as $k=>$v) {
			$this->buffer[$k] = $this->fromG2($this->buffer[$k]);
			$this->buffer[$k] = mb_convert_encoding($this->buffer[$k],'UTF-8');
		}
		$this->log("Appel service [$fctn] [".@$this->buffer[0]."] > ".$this->url);
		trigger_error("[MiniPavi-class] Appel service [$fctn] [".@$this->buffer[0]."] > ".$this->url);
		
		$toSend=array();
		
		$toSend['PAVI']['version']=PAVI_VER;
		$toSend['PAVI']['uniqueId']=$this->uniqueId;
		$toSend['PAVI']['remoteAddr']=$this->clientIp;
		if (isset($this->inCnx) && $this->inCnx!==null) {
			$toSend['PAVI']['typesocket']=$this->inCnx->getTypeSocket();
			if ($this->inCnx->getTypeSocket() == InCnx::WS_ASTSOCKET)
				$toSend['PAVI']['remoteAddr']=$this->inCnx->clientPhoneNumber;
		} else $toSend['PAVI']['typesocket']='';
		$toSend['PAVI']['versionminitel']=$this->versionMinitel;
		$toSend['PAVI']['content']=$this->buffer;		
		$toSend['PAVI']['context']= mb_convert_encoding($this->context,'UTF-8');		
		$toSend['PAVI']['fctn']=$fctn;
		if (is_array($urlParams) && count($urlParams)>0)
			$toSend['URLPARAMS']=$urlParams;
		
		
		if ($this->objWebMedia->tPing+5<time())
			$toSend['PAVI']['webmedia']=0;
		else $toSend['PAVI']['webmedia']=1;
		
		$json = @json_encode($toSend);
		if ($json === false) {
			trigger_error('[MiniPavi-class] Erreur Encode json '.json_last_error(),E_USER_WARNING  );
			return false;
		}
		
		$urlToCall = @$elemUrl['scheme'].'://'.@$elemUrl['host'].@$elemUrl['path'];
		
		$forceXml = false;
		$ch = curl_init( $urlToCall );

		curl_setopt($ch, CURLOPT_POSTFIELDS, $json );
		curl_setopt($ch, CURLOPT_USERAGENT, 'MiniPAVI/'.PAVI_VER);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		if (@$elemUrl['port']!='')
			curl_setopt($ch, CURLOPT_PORT, (int)$elemUrl['port']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POSTREDIR , true);
		curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
		curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this, 'curlCallback'));		
		
		$retry = 0;
		do {
			$retry++;
			$result = curl_exec($ch);
		} while((curl_errno($ch) == CURLE_OPERATION_TIMEDOUT ||  curl_errno($ch) == CURLE_OPERATION_TIMEOUTED) && $retry < 3);
		
		$curlErrNo = curl_errno($ch);
		
		curl_close($ch);		
		
		if ($result===false && ($curlErrNo == CURLE_OPERATION_TIMEDOUT || $curlErrNo == CURLE_OPERATION_TIMEOUTED)) {
			trigger_error("[MiniPavi-class] Erreur curl=".curl_error($ch));
			return false;
		} elseif ($result===false) {
			trigger_error("[MiniPavi-class] Erreur curl=".curl_error($ch).' / Forçage XML sur '.$urlToCall);
			$forceXml = true;
			$ch = curl_init( $urlToCall );
			curl_setopt($ch, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:132.0) Gecko/20100101 Firefox/132.0');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Connection: keep-alive'));
			if (@$elemUrl['port']!='')
				curl_setopt($ch, CURLOPT_PORT, (int)$elemUrl['port']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
			curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this, 'curlCallback'));		
			$retry = 0;
			do {
				$retry++;
				$result = curl_exec($ch);
			} while((curl_errno($ch) == CURLE_OPERATION_TIMEDOUT ||  curl_errno($ch) == CURLE_OPERATION_TIMEOUTED) && $retry < 3);
			if ($result===false) {
				trigger_error("[MiniPavi-class] Erreur curl=".curl_error($ch));
				return false;
			}
		}
		
		// Test si le retour est du XML qui doit être interprété 
		// Si oui, l'url de l'interpréteur est indiqué dans l'élement "service"
		
		libxml_use_internal_errors(true);
		$objXML = simplexml_load_string($result,null,LIBXML_NOCDATA|LIBXML_NOBLANKS);
		if ($objXML !== false) {
			$this->log("Appel service: XML détécté");
			// C'est du XML, à interpreter ?
			if ($objXML->getName() == 'service') {
				if ($objXML->interpreteur['url'] && filter_var($objXML->interpreteur['url'], FILTER_VALIDATE_URL)  ) {
					$this->url = $objXML->interpreteur['url'].urlencode($this->url);
					$this->log("Appel service: interpreteur: ".$this->url);
					return $this->sendToService($fctn);
				}
				
			}
			
		} else if ($forceXml) {
			trigger_error("[MiniPavi-class] Contenu non-XML après forçage [$result]");
			return false;
		}
		if (trim($result)!='')
			$this->serviceResult = $result;
		else $this->serviceResult = null;
		return true;
	}

	
	/*************************************************
	// Traitement des données reçues de l'utilisateur (du Minitel)
	// inDatas: flux de données reçues
	// fctn: retour de la touche de fonction pressée le cas échéant
	*************************************************/
	
	function receiveFromUser($inDatas,&$fctn,$simulate=false) {
		
		if ($simulate) {
			if (strpos($inDatas,'WMLASTEVENT/') === 0) {
				//$this->buffer[0]='';
				$lastEvent = substr($inDatas,12);
				$fctn = 'BGCALL-WM-'.$lastEvent; // BGCALL-WM-STOP ou BGCALL-WM-START
				return;
			}
			$this->buffer[0] = $inDatas;
			$fctn = 'BGCALL-SIMU';
			return;
		}
		
		$fctn='';
		
		$altChar='';

		if ($this->command) {
			switch($this->command->name) {
				case 'InputTxt':
					if ($this->command->param->char!='')
						$altChar=$this->command->param->char;
					break;
				default:
					break;
			}
		}

		$inDatas = $this->filterDatas($inDatas);

		if ($this->tempBuffer!='') {
			
			$inDatas = $this->tempBuffer.$inDatas;
			$this->tempBuffer='';
		}
		$lengthDatas = strlen($inDatas);
		
		for($idxDatas=0;$idxDatas<$lengthDatas;$idxDatas++) {
			$skipChar=false;
			$input = $inDatas[$idxDatas];

			$code = ord($input);
			if ($code==19) {
				$idxDatas++;
				if ($idxDatas==$lengthDatas) {
						//Touche fonction tronquée
						$this->tempBuffer=$inDatas;
						return;
				}
				$input = $inDatas[$idxDatas];
				$code = ord($input);

				switch ($code) {
					case self::FCT_SOMMAIRE:
						if ($this->command) {
							switch($this->command->name) {
								case 'InputTxt':
									if(($this->command->param->validwith & self::MSK_SOMMAIRE) == 0) 
										return;
									break;
								case 'InputMsg':
									if(($this->command->param->validwith & self::MSK_SOMMAIRE) == 0) 
										return;
									break;
							}
						}
						$fctn="SOMMAIRE";
						break;
					case self::FCT_ANNULATION:
						if ($this->command) {
							switch($this->command->name) {
								case 'InputTxt':
									$this->addToBufferOut($this->processAnnulationInputTxt());
									break;
								case 'InputMsg':
									$this->addToBufferOut($this->processAnnulationInputMsg());								
									break;
								case 'InputForm':
									$this->addToBufferOut($this->processAnnulationInputForm());								
									break;
								default:
									$fctn="ANNULATION";
									break;
							}
						} else 
							$fctn="ANNULATION";
						
						break;
					case self::FCT_SUITE:
						if ($this->command) {
							switch($this->command->name) {
								case 'InputMsg':
									$this->addToBufferOut($this->processSuiteInputMsg());
									break;
								case 'InputForm':
									$this->addToBufferOut($this->processSuiteInputForm());
									break;
								case 'InputTxt':
									if(($this->command->param->validwith & self::MSK_SUITE) == 0) 
										return;
								default:
									$fctn="SUITE";
									break;
							}
						} else 
							$fctn="SUITE";
						
						break;
					case self::FCT_RETOUR:
						if ($this->command) {
							switch($this->command->name) {
								case 'InputMsg':
									$this->addToBufferOut($this->processRetourInputMsg());
									break;
								case 'InputForm':
									$this->addToBufferOut($this->processRetourInputForm());
									break;
								case 'InputTxt':
									if(($this->command->param->validwith & self::MSK_RETOUR) == 0) 
										return;
								default:
									$fctn="RETOUR";
									break;
							}
						} else 
							$fctn="RETOUR";
						break;
					case self::FCT_REPETITION:
						if ($this->command) {
							switch($this->command->name) {
								case 'InputTxt':
									if(($this->command->param->validwith & self::MSK_REPETITION) == 0) 
										return;
									break;
								case 'InputMsg':
									if(($this->command->param->validwith & self::MSK_REPETITION) == 0) 
										return;
									break;
								case 'InputForm':
									if(($this->command->param->validwith & self::MSK_REPETITION) == 0) 
										return;
									break;
							}
						}
					
						$fctn="REPETITION";
						break;
					
					case self::FCT_GUIDE:
						
						if ($this->command) {
							switch($this->command->name) {
								case 'InputTxt':
									if(($this->command->param->validwith & self::MSK_GUIDE) == 0) 
										return;
									break;
								case 'InputMsg':
									if(($this->command->param->validwith & self::MSK_GUIDE) == 0) 
										return;
									break;
								case 'InputForm':
									if(($this->command->param->validwith & self::MSK_GUIDE) == 0) 
										return;
									break;
							}
						}
					
						$fctn="GUIDE";
						break;
					case self::FCT_CORRECTION:
						if ($this->command) {
							switch($this->command->name) {
								case 'InputTxt':
									$this->addToBufferOut($this->processCorrectionInputTxt());
									break;
								case 'InputMsg':
									$this->addToBufferOut($this->processCorrectionInputMsg());
									break;
								case 'InputForm':
									$this->addToBufferOut($this->processCorrectionInputForm());
									break;
									
								default:
									$fctn="CORRECTION";
									break;
							}
						} else 
							$fctn="CORRECTION";
						
						break;
					case self::FCT_ENVOI:
						if ($this->command) {
							switch($this->command->name) {
								case 'InputTxt':
									if(($this->command->param->validwith & self::MSK_ENVOI) == 0) 
										return;
									break;
								case 'InputMsg':
									if(($this->command->param->validwith & self::MSK_ENVOI) == 0) 
										return;
									break;
								case 'InputForm':
									if(($this->command->param->validwith & self::MSK_ENVOI) == 0) 
										return;
									break;
							}
						}
						$fctn="ENVOI";
						break;
					case self::FCT_CNX:
					case self::FCT_CNX2:
						$fctn="FIN";
						break;
					default:
						// Touche de fonction inconnue
						trigger_error("[MiniPavi-class] Touche de fonction inconnue");
						break;
				}
			} else {
				if ($this->command) {
					switch($this->command->name) {
						case 'InputTxt':
							$oldLength = mb_strlen(self::fromG2(@$this->buffer[$this->bufferIdx]));
							if ($this->count>=$this->command->param->l) {
								$skipChar=true;
							} else {
								if (($code>=32 && $code<127) || $input==self::VDT_G2 || $code==8) {
									@$this->buffer[$this->bufferIdx].=$input;
									if ($this->echo)
										if ($altChar!='') $this->addToBufferOut($altChar);
										else $this->addToBufferOut($input);
								}
								$this->count = mb_strlen(self::fromG2(@$this->buffer[$this->bufferIdx]));
								$this->currX+=($this->count-$oldLength);
							}
							break;
						case 'InputMsg':
							$oldLength = mb_strlen(self::fromG2(@$this->buffer[$this->bufferIdx]));
							if ($this->count>=$this->command->param->w) {
								$skipChar=true;
							} else {
								if (($code>=32 && $code<127) || $input==self::VDT_G2 || $code==8) {
									@$this->buffer[$this->bufferIdx].=$input;
									if ($this->echo)
										if ($altChar!='') $this->addToBufferOut($altChar);
										else $this->addToBufferOut($input);
								}
								$this->count = mb_strlen(self::fromG2(@$this->buffer[$this->bufferIdx]));
								$this->currX+=($this->count-$oldLength);
								if ($this->count>=$this->command->param->w) {
									$this->addToBufferOut($this->processSuiteInputMsg());
								}
							}
							break;
						case 'InputForm':
							$oldLength = mb_strlen(self::fromG2(@$this->buffer[$this->bufferIdx]));
							if ($this->count>=$this->command->param->l[$this->bufferIdx]) {
								$skipChar=true;
							} else {
								if (($code>=32 && $code<127) || $input==self::VDT_G2 || $code==8) {
									@$this->buffer[$this->bufferIdx].=$input;
									if ($this->echo)
										if ($altChar!='') $this->addToBufferOut($altChar);
										else $this->addToBufferOut($input);
								}
								$this->count = mb_strlen(self::fromG2(@$this->buffer[$this->bufferIdx]));
								$this->currX+=($this->count-$oldLength);
							}
							break;
							
						default:
							break;
					}
				}
			}
			
			if ($fctn!='') {
				$this->command =false;
				return;
			}
		}
	}
	
	
	/*************************************************
	// Traitement de la réponse envoyée par le service http
	// tConfigAsterisk: Configuration section Asterisk
	// objConfig: Configuration generale
	// acceptNoResult: si true, le service peut renvoyer une réponse vide (cas si appel après fin de connexion "FIN")
	// Retour >0 si un appel au service doit être effectué immédiatement, sans attente d'action de la part de l'utilisateur
	*************************************************/
	
	function processResponseFromService($tConfigAsterisk,$objConfig,$acceptNoResult=false) {
		if ($this->serviceResult == null)
			return 0;
		$extVdt='';
		$returnVal = 1;		// Valeur "impossible"
		$tExtCnxKeys=$objConfig->tExtCnxKeys;
		//$this->buffer=array();
		//$this->bufferIdx=0;
		try {
			$rJson = json_decode($this->serviceResult,false,50,JSON_THROW_ON_ERROR);

			if (@$rJson->next!==null && trim(@$rJson->next) != '' && !@filter_var(@$rJson->next, FILTER_VALIDATE_URL) ) {				
				$this->addToBufferOut($this->showError(5,"Prochaine URL incorrecte",@$rJson->next));
				return 0;
			}
			
			$requestedUrl = $this->url;
			if (@$rJson->next!==null && trim(@$rJson->next) != '') 
				$this->url = @$rJson->next;
			
			
			if (@$rJson->context!==null && strlen(@$rJson->context)>0)
				$this->context = mb_substr(@$rJson->context,0,64000);
			
			
			// On informe le processus principal de l'url pour qu'il l'appelle en cas de deconnexion
			
			$this->sendToMainProc('nexturl',array('url'=>$this->url));
			
			if (@$rJson->echo == 'on') 
				$this->echo=true;
			else $this->echo = false;
			
			if (!$this->command && @$rJson->COMMAND->name!='') {
				try {
					switch(@$rJson->COMMAND->name) {
						case 'InputTxt':
							$extVdt=self::addCommandInputTxt(@$rJson->COMMAND);
							break;
						case 'InputMsg':	
							$extVdt=self::addCommandInputMsg(@$rJson->COMMAND);
							break;
						case 'InputForm':	
							$extVdt=self::addCommandInputForm(@$rJson->COMMAND);
							break;
						case 'PushServiceMsg':
							trigger_error ("[MiniPavi-class] commande PushServiceMsg");
							$this->addCommandPushServiceMsg(@$rJson->COMMAND);
							break;
						case 'BackgroundCall':
							trigger_error ("[MiniPavi-class] commande BackgroundCall");
							$this->addCommandBackgroundCall(@$rJson->COMMAND);
							break;
						case 'connectToExt':
							trigger_error ("[MiniPavi-class] commande connectToExt");
							$returnVal = $this->addCommandConnectToExt(@$rJson->COMMAND,$tConfigAsterisk,$tExtCnxKeys);
							break;
						case 'connectToTln':
							trigger_error ("[MiniPavi-class] commande connectToTln");
							$returnVal = $this->addCommandConnectToTln(@$rJson->COMMAND,$tExtCnxKeys,$requestedUrl);
							break;
						case 'connectToWs':
							trigger_error ("[MiniPavi-class] commande connectToWs");
							$returnVal = $this->addCommandConnectToWs(@$rJson->COMMAND,$objConfig->host,$tExtCnxKeys,$requestedUrl);
							break;
						case 'duplicateStream':
							trigger_error ("[MiniPavi-class] commande duplicateStream");
							$returnVal = $this->addCommandDuplicateStream(@$rJson->COMMAND,$objConfig->visuKey);
							break;
						case 'setParam':
							trigger_error ("[MiniPavi-class] commande setParam");
							$this->addCommandSetParam(@$rJson->COMMAND);
							break;
						case 'libCnx':		
							trigger_error ("[MiniPavi-class] commande déconnexion");
							$returnVal = -99;
							break;
						default:
							trigger_error('[MiniPavi-class] Commande inconnue');
							$this->addToBufferOut($this->showError(7,"Commande inconnue",substr(print_r(@$rJson->COMMAND,true),0,500)));
							return 0;
					}
				} catch (Exception $e) {
					trigger_error('[MiniPavi-class] Commande mal formée');
					$this->addToBufferOut($this->showError(10,"Commande mal formée",'Exception: '.$e->getMessage()));
					return 0;
				}
				
			}
			
		} catch (Exception $e) {
			if (!$acceptNoResult) {
				$txt = htmlspecialchars_decode($this->serviceResult);
				$txt = preg_replace('#<br\s*/?>#i', "\r\n", $txt);
				$txt = trim(strip_tags($txt));
				$this->addToBufferOut($this->showError(3,"Réponse non JSON",'Exception: '.$e->getMessage(),'Votre serveur a répondu: '.substr($txt,0,400)));
				trigger_error ("[MiniPavi-class] Erreur #3 - NOJSON ".print_r($this,true));
			}
			return 0;
		}
		
		if (@$rJson->content !== null) {
			$dec = base64_decode($rJson->content);
			$this->objWebMedia->parseDatas($dec);
			$this->objWebMedia->sendRequestsToMain($this);
			$this->addToBufferOut($dec.$extVdt);
		} else $this->addToBufferOut($extVdt);
		
		if ($returnVal < 1 || ((@$rJson->directcall == 'yes' || @$rJson->directcall == 'yes-cnx' ) && $this->directCallNum < 2))  {
			// Appel direct si demandé, ou bien si une commande d'appel exterieur VoIP a échouée
			$this->directCallNum++;
			$this->command =false;
			if ($returnVal == -1)
				return 3;		// appel type DIRECTCALLFAILED
			if ($returnVal == 0)
				return 4;		// appel type DIRECTCALLENDED
			if ($returnVal == -99)
				return 99;		// Demande de déconnexion		
			
			if (@$rJson->directcall == 'yes')
				return 1;		//appel type DIRECT
			if (@$rJson->directcall == 'yes-cnx')
				return 2;		//appel type DIRECTCNX
		}
		return 0;
	}
	
	
	/*************************************************
	// Initialisation de la commande de saisie de choix (une ligne)
	// objCommand: commande
	// Retourne le videotex à afficher
	*************************************************/
	
	 function addCommandInputTxt($objCommand) {
		$extVdt='';
		$this->buffer=array();
		$this->buffer[0]='';
		
		$posX = (int)@$objCommand->param->x;
		$posY = (int)@$objCommand->param->y;
		$length = (int)@$objCommand->param->l;

		if ($posX<1 || $posX>40)
			$posX=1;
		if ($posY<0 || $posY>24)
			$posY=1;
		$maxLength = 41 - $posX;
		if ($length<1 || $length>$maxLength)
			$length=1;
		
		if (@$objCommand->param->cursor !='on' && @$objCommand->param->cursor !='off')
			@$objCommand->param->cursor = 'on';
		@$objCommand->param->x = $posX;
		@$objCommand->param->y = $posY;
		@$objCommand->param->l = $length;
	
		if (@$objCommand->param->spacechar=='')
			@$objCommand->param->spacechar=' ';
		@$objCommand->param->spacechar=mb_substr(@$objCommand->param->spacechar,0,1);
	
		if (@$objCommand->param->prefill!=null) 
			$lPrefill = @mb_strlen(@$objCommand->param->prefill);
		else $lPrefill = 0;
		if ($lPrefill>$length) {
			@$objCommand->param->prefill = mb_substr(@$objCommand->param->prefill,$length);
			$lPrefill = $length;
		}
	
	
		$this->command = $objCommand;
		
		// Affichage de la zone 
		
		$extVdt = self::getVdtPos($posX,$posY);
		$this->currX=$posX;
		$this->currY=$posY;
		$this->count=0;
		$this->bufferIdx=0;

		$extVdt.=$this->command->param->spacechar."\x12".chr(0x40+$this->command->param->l-1).$extVdt;
		
		if ($objCommand->param->cursor == 'on')
			$extVdt.=self::VDT_CURON;
		else $extVdt.=self::VDT_CUROFF;
		
		$extVdt.=$this->toG2(@$objCommand->param->prefill);
		
		// Eventuel pré-remplissage de la zone
		
		if ($lPrefill>0) {
			$this->currX+=$lPrefill;
			$this->count+=$lPrefill;
			$this->buffer[0] = @$objCommand->param->prefill;
		}
		return $extVdt;
	}


	/*************************************************
	// Initialisation de la commande de saisie de message (plusieurs lignes)
	// objCommand: commande
	// Retourne le videotex à afficher	
	*************************************************/

	 function addCommandInputMsg($objCommand) {
		$extVdt='';
		$this->buffer=array();
		
		$posX = (int)@$objCommand->param->x;
		$posY = (int)@$objCommand->param->y;
		$width = (int)@$objCommand->param->w;
		$height = (int)@$objCommand->param->h;

		if ($posX<1 || $posX>40)
			$posX=1;
		if ($posY<1 || $posY>24)
			$posY=1;
		
		$maxWidth = 41 - $posX;
		if ($width<1 || $width>$maxWidth)
			$width=$maxWidth;
		
		$maxHeight = 25 - $posY;
		if ($height<1 || $height>$maxHeight)
			$height=$maxHeight;
		for($i=0;$i<$height;$i++) $this->buffer[$i]='';
		if (!is_array(@$objCommand->param->prefill))
			$objCommand->param->prefill=array();
		if (count($objCommand->param->prefill)>0) {
			array_splice($objCommand->param->prefill, $height);
			foreach($objCommand->param->prefill as $numLine=>$line) {
				$objCommand->param->prefill[$numLine] = mb_substr($line,0,$width);
				$this->buffer[$numLine] = $objCommand->param->prefill[$numLine];
			}
		}
		$preFillLines=0;
		$j=count($objCommand->param->prefill);
		for ($i=0;$i<$j;$i++) {
			if (trim($objCommand->param->prefill[$i])!='')
				$preFillLines=$i+1;
		}

		
		if (@$objCommand->param->cursor !='on' && @$objCommand->param->cursor !='off')
			@$objCommand->param->cursor = 'on';
		
		@$objCommand->param->x = $posX;
		@$objCommand->param->y = $posY;
		@$objCommand->param->h = $height;
		@$objCommand->param->w = $width;
	
		if (@$objCommand->param->spacechar=='')
			@$objCommand->param->spacechar=' ';
		@$objCommand->param->spacechar=mb_substr(@$objCommand->param->spacechar,0,1);
		
		if ((int)@$objCommand->param->validwith<=0)
			@$objCommand->param->validwith = self::MSK_ENVOI;
		
		$this->command = $objCommand;
		
		$extVdt = '';
		$this->currX=$posX;
		$this->currY=$posY;
		$this->count=0;
		$this->bufferIdx=$preFillLines-1;
		if ($this->bufferIdx<0)
			$this->bufferIdx=0;
		
		// Affichage de la zone
		
		for($i=0;$i<$height;$i++) {
			$extVdt.= self::getVdtPos($posX,$posY+$i);
			$extVdt.= $this->command->param->spacechar."\x12".chr(0x40+$this->command->param->w-1);
			if ($i<$preFillLines) {
				$extVdt.= self::getVdtPos($posX,$posY+$i);
				$extVdt.= $this->toG2($objCommand->param->prefill[$i]);
			}
		}
		
		// Eventuel pré-remplissage de la zone
		
		if ($preFillLines > 0) {
			$extVdt.= self::getVdtPos($posX+mb_strlen($objCommand->param->prefill[$preFillLines-1]),$posY+($preFillLines-1));
			$this->currX+=mb_strlen($objCommand->param->prefill[$preFillLines-1]);
			$this->currY+=$preFillLines-1;
			$this->count=mb_strlen($objCommand->param->prefill[$preFillLines-1]);
		} else {
			$extVdt.= self::getVdtPos($posX,$posY);
		}
		
		if ($objCommand->param->cursor == 'on')
			$extVdt.=self::VDT_CURON;
		else $extVdt.=self::VDT_CUROFF;
		
		return $extVdt;
	}


	/*************************************************
	// Initialisation de la commande de saisie d'un formulaire (plusieurs lignes)
	// objCommand: commande
	// Retourne le videotex à afficher	
	*************************************************/

	 function addCommandInputForm($objCommand) {
		$extVdt='';
		$this->buffer=array();
		
		$cX = count(@$objCommand->param->x);
		$cY = count(@$objCommand->param->y);
		$cL = count(@$objCommand->param->l);
		if ($cX<1 || $cX>30 || $cX != $cY || $cY != $cL) {
			$objCommand->param->x = array(1);
			$objCommand->param->y = array(1);
			$objCommand->param->l = array(10);
		}

		foreach($objCommand->param->x as $k=>$v) {
			if ($objCommand->param->x[$k]<1 || $objCommand->param->x[$k]>40)
				$objCommand->param->x[$k] = 1;
			if ($objCommand->param->y[$k]<1 || $objCommand->param->y[$k]>24)
				$objCommand->param->y[$k] = 1;
			if ($objCommand->param->l[$k]<1 || $objCommand->param->l[$k]>41-$objCommand->param->x[$k])		
				$objCommand->param->l[$k] = 41-$objCommand->param->x[$k];
		}
		$this->buffer = array_fill(0,$cX,'');
		if (!is_array(@$objCommand->param->prefill))
			$objCommand->param->prefill=array_fill(0,$cX,'');

		if (count($objCommand->param->prefill)>0) {
			array_splice($objCommand->param->prefill, $cX);
			foreach($objCommand->param->prefill as $numLine=>$line) {
				$objCommand->param->prefill[$numLine] = mb_substr($line,0,$objCommand->param->l[$numLine]);
				$this->buffer[$numLine] = $objCommand->param->prefill[$numLine];
			}
		}

		if (@$objCommand->param->cursor !='on' && @$objCommand->param->cursor !='off')
			@$objCommand->param->cursor = 'on';
		
		if (@$objCommand->param->spacechar=='')
			@$objCommand->param->spacechar='.';
		@$objCommand->param->spacechar=mb_substr(@$objCommand->param->spacechar,0,1);
		
		if ((int)@$objCommand->param->validwith<=0 || (int)@$objCommand->param->validwith==self::MSK_ENVOI  || (int)@$objCommand->param->validwith==self::MSK_RETOUR)
			@$objCommand->param->validwith = self::MSK_ENVOI;
		
		
		$this->command = $objCommand;
		$extVdt = '';
		
		// Affichage de la zone
		
		for($i=0;$i<$cX;$i++) {
			$extVdt.= self::getVdtPos($objCommand->param->x[$i],$objCommand->param->y[$i]);
			$extVdt.= $this->command->param->spacechar."\x12".chr(0x40+$this->command->param->l[$i]-1);
		}
		for($i=0;$i<$cX;$i++) {
			$extVdt.= self::getVdtPos($objCommand->param->x[$i],$objCommand->param->y[$i]);
			$extVdt.= $this->toG2(@$this->command->param->prefill[$i]);
		}
		
		$this->currX=$objCommand->param->x[0] + mb_strlen($this->buffer[0]);
		$this->currY=$objCommand->param->y[0];
		$extVdt.= self::getVdtPos($this->currX,$this->currY);
		$this->count=mb_strlen($this->buffer[0]);
		$this->bufferIdx=0;

		if ($objCommand->param->cursor == 'on')
			$extVdt.=self::VDT_CURON;
		else $extVdt.=self::VDT_CUROFF;
		
		return $extVdt;
	}


	/*************************************************
	// Initialisation et execution (bloquante) de la commande d'appel d'un serveur exterieur par appel VoIP
	// objCommand: commande
	// tExtCnxKeys: clés d'autorisation des appels et connexions sortantes
	// Retourne la valeur de retour de la commande d'appel (-1 si erreur, sinon 0)
	*************************************************/

	function addCommandConnectToExt($objCommand,$tConfigAsterisk,$tExtCnxKeys=array()) {
		if (!is_array($tConfigAsterisk) || count($tConfigAsterisk)==0)
			return false;
		$key = @$objCommand->param->key;
		if (array_search($key,$tExtCnxKeys) === false)
			return false;
		$number = @$objCommand->param->number;
		if ($number=='')
			return false;
		$r = AstAMI::startCall($number,getmypid(),$this,(int)@$objCommand->param->RX,(int)@$objCommand->param->TX,$tConfigAsterisk);
		$this->tLastData=time();
		return $r;
	}



	/*************************************************
	// Initialisation et execution (bloquante) de la commande de connexion à un serveur via telnet
	// objCommand: commande
	// tExtCnxKeys: clés d'autorisation des appels et connexions sortantes
	// $requestedUrl: Url du script ayant demandé cette commande	
	// Retourne la valeur de retour de la commande de connexion (-1 si erreur, sinon 0)	
	*************************************************/

	function addCommandConnectToTln($objCommand,$tExtCnxKeys=array(),$requestedUrl='') {
		$host = trim(@$objCommand->param->host);
		if ($host=='')
			return false;
		
		$access = false;
		
		if ($requestedUrl!='') {
			$elemUrlReq=parse_url($requestedUrl);
			$elemUrlSrv=parse_url($host);
			if ($elemUrlReq !== false && $elemUrlSrv !== false) {
				trigger_error ("[MiniPavi-class] ConnectToTln: Req URL=[".@$elemUrlReq['host']."] hostTLN=[".@$elemUrlSrv['host']."]");
				if (@$elemUrlReq['host'] == @$elemUrlSrv['host']) {
					// Si le host de script requérant est le même que celui du serveur WS, pas besoin de clé
					$access = true;	
				}
			}
		}
		if (!$access) {
			$key = @$objCommand->param->key;
			if (array_search($key,$tExtCnxKeys) === false)
				return false;
		}
		$echo = trim(@$objCommand->param->echo);				
		$case = trim(@$objCommand->param->case);		
		$startSeq = stripcslashes(trim(@$objCommand->param->startseq));		
		
		$this->sendToMainProc('setinfos',array('infos'=>"TELNETO $host"));
		$r = TelnetConnect::linkTo($host,$this,$echo,$case,$startSeq);
		$this->sendToMainProc('setinfos',array('infos'=>''));
		$this->tLastData=time();
		return $r;
	}

	/*************************************************
	// Initialisation et execution (bloquante) de la commande de connexion à un serveur via Ws
	// objCommand: commande
	// myHost : adresse client (moi)
	// tExtCnxKeys: clés d'autorisation des appels et connexions sortantes
	// $requestedUrl: Url du script ayant demandé cette commande
	// Retourne la valeur de retour de la commande de connexion (-1 si erreur, sinon 0)	
	*************************************************/

	function addCommandConnectToWs($objCommand,$myHost,$tExtCnxKeys=array(),$requestedUrl='') {
		$host = trim(@$objCommand->param->host);
		if ($host=='' || $myHost=='')
			return false;
		
		$access = false;
		
		if ($requestedUrl!='') {
			$elemUrlReq=parse_url($requestedUrl);
			$elemUrlSrv=parse_url($host);
			if ($elemUrlReq !== false && $elemUrlSrv !== false) {
				trigger_error ("[MiniPavi-class] ConnectToWs: Req URL=[".@$elemUrlReq['host']."] hostWS=[".@$elemUrlSrv['host']."]");
				if (@$elemUrlReq['host'] == @$elemUrlSrv['host']) {
					// Si le host de script requérant est le même que celui du serveur WS, pas besoin de clé
					$access = true;	
				}
			}
		}
		
		if (!$access) {
			$key = @$objCommand->param->key;
			if (array_search($key,$tExtCnxKeys) === false)
				return false;
		}
		
		$path = trim(@$objCommand->param->path);		
		$echo = trim(@$objCommand->param->echo);				
		$case = trim(@$objCommand->param->case);		
		$proto = trim(@$objCommand->param->proto);		
		$this->sendToMainProc('setinfos',array('infos'=>"WSTO $host $path"));
		$r = WSConnect::linkTo($myHost,$host,$this,$path,$echo,$case,$proto);
		$this->sendToMainProc('setinfos',array('infos'=>''));
		$this->tLastData=time();
		return $r;
	}

	/*************************************************
	// Initialisation et execution (bloquante) de la commande de visualisation d'une autre connexion
	// objCommand: commande
	// tExtCnxKeys: clés d'autorisation des appels et connexions sortantes
	// Retourne la valeur de retour de la commande de connexion (-1 si erreur, sinon 0)	
	*************************************************/

	function addCommandDuplicateStream($objCommand,$visuKey) {
		$key = @$objCommand->param->key;
		if ($key !== $visuKey)
			return false;
		$uniqueid = trim(@$objCommand->param->uniqueid);		
		$this->sendToMainProc('setinfos',array('infos'=>"DUPL $uniqueid"));
		$r = DuplicateStream::linkTo($uniqueid,$this);
		$this->sendToMainProc('setinfos',array('infos'=>''));
		$this->tLastData=time();
		return $r;
	}


	/*************************************************
	// Execution de la commande d'appel d'une url en différé
	// objCommand: commande
	*************************************************/
	
	 function addCommandBackgroundCall($objCommand) {
		$this->sendToMainProc('addCommandBackgroundCall',array('object'=>$objCommand));
	}
	

	
	/*************************************************
	// Execution de la commande d'envoi de message en ligne0
	// objCommand: commande
	*************************************************/
	
	 function addCommandPushServiceMsg($objCommand) {
		$this->sendToMainProc('addCommandPushServiceMsg',array('object'=>$objCommand));
	}
	


	/*************************************************
	// Execution de la commande de paramètrage de minipavi
	// objCommand: commande
	*************************************************/
	
	function addCommandSetParam($objCommand) {
		switch (@$objCommand->param->paramName) {
		case 'screensaver':
			$t = (int)@$objCommand->param->params->type; // (0,1,2,3)
			if ($t<0 || $t>3) $t=2; // Defaut : Smiley
			$this->screenSaverType = $t;
			if ($t == 3) {	// Texte libre
				if (@$objCommand->param->params->text=='')
					$this->screenSaverText = "Not set";
				else $this->screenSaverText = mb_substr($objCommand->param->params->text,0,30);
			}
		}
	}

	/*************************************************
	// Traitement de la touche "SUITE"  lors d'une commande de saisie de message
	// Retourne le videotex à afficher
	*************************************************/

	 private function processSuiteInputMsg() {
		$currLine = $this->currY - $this->command->param->y;

		if ($currLine+1 < $this->command->param->h) {
			$this->command->param->prefill[$currLine]=$this->buffer[$this->bufferIdx];
			$this->bufferIdx++;
			$this->buffer[$this->bufferIdx] = @$this->command->param->prefill[$currLine+1];
			$this->currY++;
			if ($this->buffer[$this->bufferIdx]!=null) {
				$this->currX=$this->command->param->x+mb_strlen(self::fromG2($this->buffer[$this->bufferIdx]));
				$this->count=mb_strlen(self::fromG2($this->buffer[$this->bufferIdx]));
			} else {
				$this->currX=$this->command->param->x;
				$this->count=0;
			}
			$vdt = self::getVdtPos($this->command->param->x,$this->currY).$this->buffer[$this->bufferIdx];
			return $vdt;
		}
	}



	/*************************************************
	// Traitement de la touche "RETOUR"  lors d'une commande de saisie de message
	// Retourne le videotex à afficher
	*************************************************/

	 private function processRetourInputMsg() {
		$currLine = $this->currY - $this->command->param->y;

		if ($currLine>0) {
			$this->command->param->prefill[$currLine]=$this->buffer[$this->bufferIdx];
			$this->bufferIdx--;
			$this->buffer[$this->bufferIdx] = @$this->command->param->prefill[$currLine-1];
			$this->currY--;
			if ($this->buffer[$this->bufferIdx]!=null) {
				$this->currX=$this->command->param->x+mb_strlen(self::fromG2($this->buffer[$this->bufferIdx]));
				$this->count=mb_strlen(self::fromG2($this->buffer[$this->bufferIdx]));
			} else {
				$this->currX=$this->command->param->x;
				$this->count=0;
			}
			$vdt = self::getVdtPos($this->command->param->x,$this->currY).$this->buffer[$this->bufferIdx];
			return $vdt;
		}
		
	}


	/*************************************************
	// Traitement de la touche "SUITE"  lors d'une commande de saisie de formulaire
	// Retourne le videotex à afficher
	*************************************************/
	
	 private function processSuiteInputForm() {
		if ($this->bufferIdx+1 < count($this->command->param->x)) {
			$this->command->param->prefill[$this->bufferIdx]=$this->buffer[$this->bufferIdx];
			$this->bufferIdx++;
			$this->buffer[$this->bufferIdx] = @$this->command->param->prefill[$this->bufferIdx];
			$this->currY = $this->command->param->y[$this->bufferIdx];
			if ($this->buffer[$this->bufferIdx]!=null) {
				$this->currX=$this->command->param->x[$this->bufferIdx]+mb_strlen(self::fromG2($this->buffer[$this->bufferIdx]));
				$this->count=mb_strlen(self::fromG2($this->buffer[$this->bufferIdx]));
			} else {
				$this->currX=$this->command->param->x[$this->bufferIdx];
				$this->count=0;
			}
			$vdt = self::getVdtPos($this->command->param->x[$this->bufferIdx],$this->currY).$this->buffer[$this->bufferIdx];
			return $vdt;
		}
	}

	/*************************************************
	// Traitement de la touche "RETOUR"  lors d'une commande de saisie de formulaire
	// Retourne le videotex à afficher
	*************************************************/
	
	 private function processRetourInputForm() {
		if ($this->bufferIdx>0) {
			$this->command->param->prefill[$this->bufferIdx]=$this->buffer[$this->bufferIdx];
			$this->bufferIdx--;
			$this->buffer[$this->bufferIdx] = @$this->command->param->prefill[$this->bufferIdx];
			$this->currY = $this->command->param->y[$this->bufferIdx];
			if ($this->buffer[$this->bufferIdx]!=null) {
				$this->currX=$this->command->param->x[$this->bufferIdx]+mb_strlen(self::fromG2($this->buffer[$this->bufferIdx]));
				$this->count=mb_strlen(self::fromG2($this->buffer[$this->bufferIdx]));
			} else {
				$this->currX=$this->command->param->x[$this->bufferIdx];
				$this->count=0;
			}
			$vdt = self::getVdtPos($this->command->param->x[$this->bufferIdx],$this->currY).$this->buffer[$this->bufferIdx];
			return $vdt;
		}
	}

	
	/*************************************************
	// Traitement de la touche "CORRECTION"  lors d'une commande de saisie de texte
	// Retourne le videotex à afficher	
	*************************************************/
	
	 private function processCorrectionInputTxt() {
		if ($this->count>0) {
			$this->count--;
			$this->currX--;
			$this->buffer[$this->bufferIdx] = $this->toG2(@mb_substr($this->fromG2($this->buffer[$this->bufferIdx]), 0, -1)); 
			return "\x08".$this->command->param->spacechar."\x08";
		}
	}

	
	/*************************************************
	// Traitement de la touche "CORRECTION"  lors d'une commande de saisie de message
	// Retourne le videotex à afficher	
	*************************************************/
	
	 private function processCorrectionInputMsg() {
		if ($this->count>0) {
			$this->count--;
			$this->currX--;
			$this->buffer[$this->bufferIdx] = $this->toG2(@mb_substr($this->fromG2($this->buffer[$this->bufferIdx]), 0, -1)); 
			return "\x08".$this->command->param->spacechar."\x08";
		}
		
	}

	/*************************************************
	// Traitement de la touche "CORRECTION"  lors d'une commande de saisie de formulaire
	// Retourne le videotex à afficher	
	*************************************************/
	
	 private function processCorrectionInputForm() {
		if ($this->count>0) {
			$this->count--;
			$this->currX--;
			$this->buffer[$this->bufferIdx] = $this->toG2(@mb_substr($this->fromG2($this->buffer[$this->bufferIdx]), 0, -1)); 
			return "\x08".$this->command->param->spacechar."\x08";
		}
		
	}


	/*************************************************
	// Traitement de la touche "ANNULATION"  lors d'une commande de saisie de texte
	// Retourne le videotex à afficher	
	*************************************************/

	 private function processAnnulationInputTxt() {
		if ($this->count>0) {
			$vdt = self::getVdtPos($this->command->param->x,$this->command->param->y);
			$vdt.=$this->command->param->spacechar."\x12".chr(0x40+$this->count-1);
			$vdt.=self::getVdtPos($this->command->param->x,$this->command->param->y);
			$this->count=0;
			$this->currX=$this->command->param->x;
			$this->currY=$this->command->param->y;
			$this->buffer[$this->bufferIdx] = ''; 
			return $vdt;
		}
		
	}
	

	/*************************************************
	// Traitement de la touche "ANNULATION"  lors d'une commande de saisie de message
	// Retourne le videotex à afficher	
	*************************************************/
	
	 private function processAnnulationInputMsg() {	
		if ($this->count>0) {
			$vdt = self::getVdtPos($this->command->param->x,$this->currY);
			$vdt.=$this->command->param->spacechar."\x12".chr(0x40+$this->count-1);
			$vdt.=self::getVdtPos($this->command->param->x,$this->currY);
			$this->count=0;
			$this->currX=$this->command->param->x;
			$this->buffer[$this->bufferIdx] = ''; 
			return $vdt;
		}
	}
	
	/*************************************************
	// Traitement de la touche "ANNULATION"  lors d'une commande de saisie de formulaire
	// Retourne le videotex à afficher	
	*************************************************/
	
	 private function processAnnulationInputForm() {	
		if ($this->count>0) {
			$vdt = self::getVdtPos($this->command->param->x[$this->bufferIdx],$this->currY);
			$vdt.=$this->command->param->spacechar."\x12".chr(0x40+$this->count-1);
			$vdt.=self::getVdtPos($this->command->param->x[$this->bufferIdx],$this->currY);
			$this->count=0;
			$this->currX=$this->command->param->x[$this->bufferIdx];
			$this->buffer[$this->bufferIdx] = ''; 
			return $vdt;
		}
	}


	/*************************************************
	// Préparation du buffer à envoyer à l'utilisateur (au Minitel)
	// clean: si 'true', efface l'écran
	// Retourne le videotex à afficher	
	*************************************************/

	 function prepareSendToUser($clean=false) {	
		
		if ($clean) {
			$vdt=self::VDT_CUROFF;
			$vdt.=self::VDT_G0.self::VDT_POS.'@A'.self::VDT_CLRLN.self::VDT_CLR;
			$this->addToBufferOut($vdt,true);
		}

		$r=$this->bufferOut;
		$this->bufferOut='';
		return $r;
	}
	

	/*************************************************
	// Envoi au service l'évenement 'DECO' lors d'une déconnexion
	*************************************************/
	
	function onDeco() {
		trigger_error("[MiniPavi-class] MiniPavi stopping");
		$this->sendToService($this->url,'','DECO');
	}


	/*************************************************
	// Retourne un nom pour une socket locale
	// pid: si !false, utilise le pid indiqué
	// Retourne le nom de la socket
	*************************************************/

	function getLocalSocketName($pid=false) {
		if ($pid === false)
			$pid = getmypid();
		if ( !is_dir( self::MINIPAVI_SOCKETDIR ) ) {
			mkdir( self::MINIPAVI_SOCKETDIR );       
		}		
		return self::MINIPAVI_SOCKETDIR.'skminipavi-'.(int)$pid.'-'.time().rand(1000,9999).'.sock';
	}


	/*************************************************
	// Enregistre une socket locale et son nom associé
	// localSocketName: nom (chemin complet + nom fichier)
	// localSocket:  la socket
	*************************************************/

	function registerLocalSocket($localSocketName,$localSocket,$usage=self::MINIPAVI_SOCKETUSAGE_CALL) {
		if (!is_array($this->tLocalSocket))
			$this->tLocalSocket=array();
		$i = count($this->tLocalSocket);
		$this->tLocalSocket[$i]['name'] = $localSocketName;
		$this->tLocalSocket[$i]['socket'] = $localSocket;
		$this->tLocalSocket[$i]['usage'] = $usage;
	}


	/*************************************************
	// Supprime (ferme et efface) un ou toute les sockets locale
	// localSocketName: nom (chemin complet + nom fichier) de la socket à supprimer
	// Si false, toutes les sockets locales sont supprimées
	*************************************************/
	
	function unregisterLocalSocket($localSocketName=false) {
		if (!is_array($this->tLocalSocket))
			return;
		if ($localSocketName === '')
			return;
		foreach($this->tLocalSocket as $k=>$localSocket) {
			if (isset($localSocket['socket']) && $localSocket['socket']!=null) {
				if ($localSocketName==false || $localSocket['name']==$localSocketName) {
					fclose($localSocket['socket']);
					@unlink($localSocket['name']);
					if ($localSocket['name']==$localSocketName) {
						unset($this->tLocalSocket[$k]['name']);
						unset($this->tLocalSocket[$k]['socket']);
						unset($this->tLocalSocket[$k]['usage']);
						return;
					}
				}
			}
		}
	}
	
	/*************************************************
	// Retourne les sockets locales selon leur tyoe d'usage
	// usage: type d'usage
	*************************************************/
	
	function getLocalSocketByUsage($usage) {
		if (!is_array($this->tLocalSocket))
			return array();
		$tSockets = array();
		foreach($this->tLocalSocket as $k=>$localSocket) {
			if (isset($localSocket['socket']) && $localSocket['socket']!=null && $localSocket['usage']==$usage) {
				$tSockets[]=$localSocket;
			}
		}
		return $tSockets;
	}

	/*************************************************
	// Envoi une commande au processus principal
	// command: nom de la commande (addCommandPushServiceMsg,nexturl,setinfos,addCommandBackgroundCall)
	// sfTab: tableau des arguments de la commande
	*************************************************/

	function sendToMainProc($command,$sfTab) {
		if ($command == 'setlastaction' && $this->tLastAction+5>time())
			return;
		trigger_error ("[MiniPavi-class] Envoi COMMANDE $command vers processus principal");		
		$sfTab['command']=$command;
		fwrite($this->commSockets[0], serialize($sfTab)."\n");
		posix_kill($this->mainPid,SIGUSR2);
		$this->tLastAction = time();
	}
	

	/*************************************************
	// Ouvre le fichier pour l'enreegistrement local de la session
	// recordsFilePath: chemin du fichier d'enregistrement
	// Retourne true si ok, sinon false
	*************************************************/
	
	function startLocalRecoding($recordsFilePath) {
		if ($this->fRecord!=null || $this->uniqueId =='' || $recordsFilePath=='') 
			return false;
		$fileName = $recordsFilePath.'rec-'.$this->uniqueId.'.vdt';
		$this->fRecord = fopen($fileName,'w');
		if (!$this->fRecord) {
			$this->fRecord = null;
			return false;
		}
		return true;
	}

	/*************************************************
	// Ferme le fichier pour l'enreegistrement local de la session
	*************************************************/

	function stopLocalRecoding() {
		if ($this->fRecord!=null ) 
			fclose($this->fRecord);
	}
	

	/*************************************************
	// Ecrit des données dans le fichier d'enregistrement de session
	*************************************************/
	
	function writeToLocalRecording($datas) {
		if ($this->fRecord!=null) {
			if (fwrite($this->fRecord,$datas) === false) {
				fclose($this->fRecord);
				$this->fRecord = null;
			}
		}
	}		

	/*************************************************
	// Efface le fichier pour l'enreegistrement local de la session
	*************************************************/

	function deleteLocalRecording($recordsFilePath) {
		if ($this->uniqueId =='' || $recordsFilePath=='') 
			return;
		$fileName = $recordsFilePath.'rec-'.$this->uniqueId.'.vdt';
		@unlink($fileName);
	}

	/*************************************************
	// Efface tous les fichiers pour l'enreegistrement local de la session
	*************************************************/
	
	function deleteAllLocalRecordings($recordsFilePath) {
		if ($recordsFilePath=='') 
			return;
		$tFiles = array_diff(scandir($recordsFilePath), array('..', '.'));	
		foreach($tFiles as $file) {
			if (preg_match('/rec-(.*)\.vdt/', $file)) {
				trigger_error ("[MiniPavi-class] Suppression de ".$recordsFilePath.$file);			
				unlink($recordsFilePath.$file);
			}
		}
	}

	/*************************************************
	// Lit un fichier d'enreegistrement local de la session
	// conernant l'identifiant unique indiqué
	// uniqueId : identifiant d'une session
	// Retourne les données si ok, sinon false
	*************************************************/
	
	function getStreamFromRecording($recordsFilePath,$uniqueId) {
		if ($recordsFilePath=='') 
			return;
		$fileName = $recordsFilePath.'rec-'.$uniqueId.'.vdt';
		$datas = file_get_contents($fileName);
		if ($datas == false) 
			return false;
		return $datas;
	}



	/************************************************
	// Garde le dernier écran affiché
	// On considère qu'un écran commence lors de l'effacement de celui-ci
	***********************************************/
	
	function addToScreenBuffer($datas) {
		$this->screenBuffer.=$datas;
		$this->screenBuffer = preg_replace('/^(.*)\x0C/s',"",$this->screenBuffer,-1,$count);	
	}
	

	/************************************************
	// Arrête l'économiseur d'écran
	***********************************************/
	
	function screenSaverStop() {
		if ($this->screenSaver && ($this->screenSaverType == 2 || $this->screenSaverType == 3) && $this->screenBuffer!='') {
			$this->inCnx->send("\x0C".$this->screenBuffer,$this);
		}
		$this->screenSaver = false;
	}

	/************************************************
	// Enclenche l'économiseur d'écran
	***********************************************/
	

	function screenSaverGo() {
		if (!$this->screenSaverType)
			return;
		
		$this->screenSaver = true;
		if ($this->screenSaverType == 1) {
			$vdt="\x01";
		} else if ($this->screenSaverType == 2) {
			$color = rand (65,71);
			$lstart = rand(1,18);
			$cstart = rand(2,32);
			
			$vdt = "\x14\x1F@A\x18\x0C";
			$vdt.= "\x1F".chr(64+$lstart).chr(64+$cstart)."\x0E\x1BC x^__|0";
			$vdt.="\x1F".chr(64+$lstart+1).chr(64+$cstart)."\x0E\x1BCz_\x1BQ7_7__\x1BP0";
			$vdt.="\x1F".chr(64+$lstart+2).chr(64+$cstart)."\x0E\x1BC_\x1BQ7___7_\x1BP5";
			$vdt.="\x1F".chr(64+$lstart+3).chr(64+$cstart)."\x0E\x1BCk_\x1BQvss^_\x1BP!";
			$vdt.="\x1F".chr(64+$lstart+4).chr(64+$cstart)."\x0E\x1BC +o__/!";
			$vdt.="\x1F".chr(64+$lstart+5).chr(64+$cstart-1)."\x1BH\x1B".chr($color).'Touche mon';
			$vdt.="\x1F".chr(64+$lstart+6).chr(64+$cstart)."\x1BH\x1B".chr($color).'clavier!';
		} else if ($this->screenSaverType == 3) {
			$lstart = rand(1,24);
			$cstart = rand(1,40-mb_strlen($this->screenSaverText));
			$color = rand (65,71);
			$vdt = "\x14\x1F@A\x18\x0C";
			$vdt.="\x1F".chr(64+$lstart).chr(64+$cstart)."\x1BH\x1B".chr($color).$this->toG2($this->screenSaverText);
		}
		$this->inCnx->send($vdt,$this,true);
	}
	
	
	/*************************************************
	// Transforme une chaine de caractère vers le jeu de caractère videotex G2
	// str: chaine de caractères
	// Retourne la chaine transformée
	*************************************************/
	
	function toG2($str) {
		if ($str===null || $str=='')
			return "";
		$tabAcc=array('é','è','à','ç','ê','É','È','À','Ç','Ê',
		'β','ß','œ','Œ','ü','û','ú','ù','ö','ô','ó','ò','î','î','í','ì','ë','ä',
		'â','á','£','°','±','←','↑','→','↓','¼','½','¾','Â');
		
		$tabG2=array(self::VDT_G2."\x42e",
		self::VDT_G2."\x41e",
		self::VDT_G2."\x41a",
		self::VDT_G2."\x4B\x63",
		self::VDT_G2."\x43e",
		self::VDT_G2."\x42E",
		self::VDT_G2."\x41E",
		self::VDT_G2."\x41A",
		self::VDT_G2."\x4B\x63",
		self::VDT_G2."\x43E",
		self::VDT_G2."\x7B",		
		self::VDT_G2."\x7B",		
		self::VDT_G2."\x7A",		
		self::VDT_G2."\x6A",		
		self::VDT_G2."\x48\x75",		
		self::VDT_G2."\x43\x75",		
		self::VDT_G2."\x42\x75",		
		self::VDT_G2."\x41\x75",		
		self::VDT_G2."\x48\x6F",		
		self::VDT_G2."\x43\x6F",		
		self::VDT_G2."\x42\x6F",		
		self::VDT_G2."\x41\x6F",		
		self::VDT_G2."\x48\x69",		
		self::VDT_G2."\x43\x69",		
		self::VDT_G2."\x42\x69",		
		self::VDT_G2."\x41\x69",		
		self::VDT_G2."\x48\x65",		
		self::VDT_G2."\x48\x61",		
		self::VDT_G2."\x43\x61",		
		self::VDT_G2."\x42\x61",
		self::VDT_G2."\x23",		
		self::VDT_G2."\x30",		
		self::VDT_G2."\x31",		
		self::VDT_G2."\x2C",		
		self::VDT_G2."\x2D",		
		self::VDT_G2."\x2E",		
		self::VDT_G2."\x2F",		
		self::VDT_G2."\x3C",		
		self::VDT_G2."\x3D",		
		self::VDT_G2."\x3E",		
		self::VDT_G2."\x43A"
		);
		
		return str_replace($tabAcc, $tabG2, $str);	
	}



	/*************************************************
	// Transforme une chaine de caractère depuis le jeu de caractère videotex G2
	// str: chaine de caractères
	// Retourne la chaine transformée	
	*************************************************/

	function fromG2($str) {
		if ($str===null || $str=='')
			return '';
		
		$tabAcc=array('é','è','à','ç','ê','É','È','À','Ç','Ê',
		'β','ß','œ','Œ','ü','û','ú','ù','ö','ô','ó','ò','î','î','í','ì','ë','ä',
		'â','á','£','°','±','←','↑','→','↓','¼','½','¾','Â','','','','','','');
		
		
		$tabG2=array(self::VDT_G2."\x42e",
		self::VDT_G2."\x41e",
		self::VDT_G2."\x41a",
		self::VDT_G2."\x4B\x63",
		self::VDT_G2."\x43e",
		self::VDT_G2."\x42E",
		self::VDT_G2."\x41E",
		self::VDT_G2."\x41A",
		self::VDT_G2."\x4B\x63",
		self::VDT_G2."\x43E",
		self::VDT_G2."\x7B",		
		self::VDT_G2."\x7B",		
		self::VDT_G2."\x7A",		
		self::VDT_G2."\x6A",		
		self::VDT_G2."\x48\x75",		
		self::VDT_G2."\x43\x75",	
		self::VDT_G2."\x42\x75",		
		self::VDT_G2."\x41\x75",		
		self::VDT_G2."\x48\x6F",		
		self::VDT_G2."\x43\x6F",		
		self::VDT_G2."\x42\x6F",		
		self::VDT_G2."\x41\x6F",		
		self::VDT_G2."\x48\x69",		
		self::VDT_G2."\x43\x69",		
		self::VDT_G2."\x42\x69",		
		self::VDT_G2."\x41\x69",		
		self::VDT_G2."\x48\x65",		
		self::VDT_G2."\x48\x61",		
		self::VDT_G2."\x43\x61",		
		self::VDT_G2."\x42\x61",
		self::VDT_G2."\x23",		
		self::VDT_G2."\x30",		
		self::VDT_G2."\x31",		
		self::VDT_G2."\x2C",		
		self::VDT_G2."\x2D",		
		self::VDT_G2."\x2E",		
		self::VDT_G2.'\\'."\x2F",		
		self::VDT_G2."\x3C",		
		self::VDT_G2."\x3D",		
		self::VDT_G2."\x3E",
		self::VDT_G2."\x43A",
		self::VDT_G2."\x41",
		self::VDT_G2."\x4B",
		self::VDT_G2."\x43",
		self::VDT_G2."\x42",
		self::VDT_G2."\x48",
		self::VDT_G2.""
		
		);

		return str_replace($tabG2,$tabAcc, $str);
	}
	

	/*************************************************
	// Formate un message d'erreur en videotex
	// num: numéro de l'erreur
	// msg: message
	// infos: infos supplémentaires
	// ext: infos supplémentaires	
	// Retourne le videotex à afficher
	*************************************************/
	
	function showError($num,$msg,$infos='',$ext='') {
		
		$vdt=self::VDT_CLR.self::VDT_G0.self::VDT_POS.'BA'.self::VDT_SZNDBLH.self::VDT_TXTWHITE.self::VDT_BGRED.' ERR #'.sprintf('%02d',(int)$num).self::VDT_TXTBLACK."\x7D".self::VDT_TXTWHITE.self::VDT_BGGREEN.' '.self::toG2($msg).self::VDT_CLRLN;	
		if ($infos!='') {
				$vdt.=self::VDT_POS.'DA'.self::toG2($infos);
		}
		if ($ext!='') {
				$vdt.=self::VDT_POS.'GA'.self::toG2($ext);
		}
		$vdt.=self::VDT_POS.'XAUID:'.$this->uniqueId.' Aide:aide@minipavi.fr';
		return $vdt;
	}
	
	private  function getVdtPos($x,$y) {
			return self::VDT_POS.chr(64+$y).chr(64+$x);
	}
	


	/*************************************************
	// Effectue les appels différés créés par la commande "addCommandBackgroundCall"
	// Note : utilisée dans un processus dédié
	*************************************************/
	
	function backgroundCalls() {
		
		$tCalls = array();
		
		trigger_error("[MiniPavi-class] Lancement processus backgroundcalls");
		$errCount=0;
		do {
			
			$tCpy[] = $this->commSockets[0];
			$retSocket=@stream_select($tCpy, $null, $null, 1, 0);
			if ($retSocket>0) {
				$sfTab=fgets($this->commSockets[0]);
				$sfTab = @unserialize($sfTab);
				if ($sfTab['command'] == 'addCommandBackgroundCall') {
					$c = $sfTab["object"];
					foreach($c->param->simulate as $k=>$v) {
						if ($v == true) {
							trigger_error('[MiniPavi-bgcall] Simulation utilisateur uniqueId '.$c->param->uniqueid[$k]);
							$this->sendToMainProc('simulateUser',array('time'=>$c->param->time[$k],'datas'=>$c->param->url[$k],'uniqueId'=>$c->param->uniqueid[$k]));
							array_splice($c->param->time,$k,1);
						}
					}
				}
				if (count($sfTab["object"]->param->time)>0) 
					$tCalls[]=$sfTab;
			} else if ($retSocket === false) {
				$errCount++;
				if ($errCount>10)
					exit(1);
				continue;
			}
			
			foreach($tCalls as $k1=>$calls) {
				if ($calls['command'] == 'addCommandBackgroundCall') {
					$c = $calls["object"];
					foreach($c->param->time as $k=>$v) {
						$this->serviceResult = '';
						if ($c->param->time[$k] <= time() && $c->param->time[$k]>0) {
							$try=0;
							do {
								trigger_error('[MiniPavi-bgcall] Appel '.$c->param->url[$k]);
								$this->url = $c->param->url[$k];
								$this->buffer=array();
								$this->uniqueId=$c->param->uniqueid[$k];
								$this->clientIp='';
								$this->context='';
								$this->serviceResult = '';
								$r=$this->sendToService("BGCALL");
								if ($r) {
									break;
								}
								$try++;
							} while($try<5);

							unset($c->param->time[$k]);
						}
						if (count($c->param->time)==0) {
							unset($tCalls[$k1]);
						}
						
						
						if ($this->serviceResult != '') {	// uniquement possible si simulate == false
							try {
								$rJson = json_decode(trim($this->serviceResult),false,50,JSON_THROW_ON_ERROR);
							} catch (Exception $e) {
								trigger_error ("[MiniPavi-bgcall] Réponse non json ".$e->getMessage().' '.$e->getTraceAsString()."***".$this->serviceResult."***");
								continue;
							}
							
							if (@$rJson->COMMAND->name!='') {
								switch(@$rJson->COMMAND->name) {					
									case 'PushServiceMsg':		// En retour d'un appel en arrière plan, la seul réponse acceptable est une commande PushServiceMsg
																// il n'y a pas de modification de nexturl de l'utilisateur: on est tjrs en attente d'une action utilisateur
										$this->addCommandPushServiceMsg(@$rJson->COMMAND);
										break;
									default:
										trigger_error('[MiniPavi-bgcall] Commande inconnue');
								}
							}
						}
					}
				}
			}
		} while(true);
	}
}