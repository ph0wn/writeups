<?php
/**
 * @file class.PCE.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.1 Novembre 2023
 *
 * MINI Point d'Accès VIdeotex
 * PHP 8.2 (CLI) version Unix
 *
 * Gestion PCE
 * 
 * License GPL v2 ou supérieure
 *
 */

class PCE {

	const PCE_SYNTABLE = array(
		0x00, 0x12, 0x24, 0x36, 0x48, 0x5a, 0x6c, 0x7e,
		0x90, 0x82, 0xb4, 0xa6, 0xd8, 0xca, 0xfc, 0xee,
		0x32, 0x20, 0x16, 0x04, 0x7a, 0x68, 0x5e, 0x4c,
		0xa2, 0xb0, 0x86, 0x94, 0xea, 0xf8, 0xce, 0xdc,
		0x64, 0x76, 0x40, 0x52, 0x2c, 0x3e, 0x08, 0x1a,
		0xf4, 0xe6, 0xd0, 0xc2, 0xbc, 0xae, 0x98, 0x8a,
		0x56, 0x44, 0x72, 0x60, 0x1e, 0x0c, 0x3a, 0x28,
		0xc6, 0xd4, 0xe2, 0xf0, 0x8e, 0x9c, 0xaa, 0xb8,
		0xc8, 0xda, 0xec, 0xfe, 0x80, 0x92, 0xa4, 0xb6,
		0x58, 0x4a, 0x7c, 0x6e, 0x10, 0x02, 0x34, 0x26,
		0xfa, 0xe8, 0xde, 0xcc, 0xb2, 0xa0, 0x96, 0x84,
		0x6a, 0x78, 0x4e, 0x5c, 0x22, 0x30, 0x06, 0x14,
		0xac, 0xbe, 0x88, 0x9a, 0xe4, 0xf6, 0xc0, 0xd2,
		0x3c, 0x2e, 0x18, 0x0a, 0x74, 0x66, 0x50, 0x42,
		0x9e, 0x8c, 0xba, 0xa8, 0xd6, 0xc4, 0xf2, 0xe0,
		0x0e, 0x1c, 0x2a, 0x38, 0x46, 0x54, 0x62, 0x70,
		0x82, 0x90, 0xa6, 0xb4, 0xca, 0xd8, 0xee, 0xfc,
		0x12, 0x00, 0x36, 0x24, 0x5a, 0x48, 0x7e, 0x6c,
		0xb0, 0xa2, 0x94, 0x86, 0xf8, 0xea, 0xdc, 0xce,
		0x20, 0x32, 0x04, 0x16, 0x68, 0x7a, 0x4c, 0x5e,
		0xe6, 0xf4, 0xc2, 0xd0, 0xae, 0xbc, 0x8a, 0x98,
		0x76, 0x64, 0x52, 0x40, 0x3e, 0x2c, 0x1a, 0x08,
		0xd4, 0xc6, 0xf0, 0xe2, 0x9c, 0x8e, 0xb8, 0xaa,
		0x44, 0x56, 0x60, 0x72, 0x0c, 0x1e, 0x28, 0x3a,
		0x4a, 0x58, 0x6e, 0x7c, 0x02, 0x10, 0x26, 0x34,
		0xda, 0xc8, 0xfe, 0xec, 0x92, 0x80, 0xb6, 0xa4,
		0x78, 0x6a, 0x5c, 0x4e, 0x30, 0x22, 0x14, 0x06,
		0xe8, 0xfa, 0xcc, 0xde, 0xa0, 0xb2, 0x84, 0x96,
		0x2e, 0x3c, 0x0a, 0x18, 0x66, 0x74, 0x42, 0x50,
		0xbe, 0xac, 0x9a, 0x88, 0xf6, 0xe4, 0xd2, 0xc0,
		0x1c, 0x0e, 0x38, 0x2a, 0x54, 0x46, 0x70, 0x62,
		0x8c, 0x9e, 0xa8, 0xba, 0xc4, 0xd6, 0xe0, 0xf2);

	const PCE_PRO2_START_PROCEDURE = "\x1B\x3A\x69\x44";
	const PCE_PRO2_STOP_PROCEDURE = "\x1B\x3A\x6A\x44";

	const PCE_PARITY_BIT_POS = 7;
	const PCE_DLE = 0x10;
	const PCE_SYN = 0x16;
	const PCE_NUL = 0;
	const PCE_NACK = 0x15;

	const PCE_STATE_REPEAT_NULL = 0;
	const PCE_STATE_REPEAT_WAIT_NACK = 1;
	const PCE_STATE_REPEAT_WAIT_NBLOC = 2;

	const PCE_TIMER_DELAY = 150000;
	
	private $socket;
	public $enabled;
	
	private $memBlocs;
	private $countMemBlocs;
	private $countMemBlocsErr;
	private $blocsToSend;
	private $sendPaused;
	
	private $buffer;

	private $procedureStateRepeat;
	private $blocToRepeat;
	private $headerToSend;
	
	private $timeout;
	private $txTimer;
	
	private $ack;
	private $ackTimeout;
	
	
	
	
	function __construct($socket) {
		$this->socket = $socket;
		$this->enabled = false;
		
		$this->_init();
	}
	
	private function _init() {
		$this->sendPaused = false;
		$this->countMemBlocs=0;
		$this->countMemBlocsErr=0;
		$this->memBlocs=array();
		$this->blocsToSend=array();
		$this->buffer=array();
		$this->procedureStateRepeat=self::PCE_STATE_REPEAT_NULL;
		$this->blocToRepeat=-1;
		$this->headerToSend='';
		$this->txTimer=microtime(true);
		$this->ack=0;
	}
	
	function isAck() {
		if ($this->ack<4)
			return false;
		return true;
	}
	
	function getSocket() {
		return $this->socket;
	}
	
	function freeSocket() {
		$this->socket = null;
	}



	/***************************
	** Prépare les blocs de données
	** datas: données à préparer
	****************************/
	function send($datas) {
		if (!$this->enabled) {
			fwrite($this->socket,$datas);
			return;
		}
		$currBloc=array();
		$posBloc = 0;
		$tDatas = unpack('C*',$datas);
		foreach( $tDatas as $d) {			// On "découpe" toutes les données à transmettre en blocs
			if ($d>127)
				$d=32;
			$tD = array();
			$tD[0] = $d;
			if ($tD[0] == self::PCE_DLE || $tD[0] == self::PCE_SYN || $tD[0] == self::PCE_NACK || $tD[0] == self::PCE_NUL) {
				$tD[1] = $tD[0];
				$tD[0] = self::PCE_DLE;
				}
		
			foreach($tD as $idx=>$v) {
				if ($idx == 0 && $v == self::PCE_DLE && $posBloc ==14) {		// On ne peut pas scinder la séquence DLE sur 2 blocs
					$currBloc[$posBloc] = self::PCE_NUL;
					$posBloc = 0;
					$this->blocReady($currBloc);
					$currBloc=array();
				}
				
				$currBloc[$posBloc] = $v;
				$posBloc++;
				if ($posBloc == 15) {
					$this->blocReady($currBloc);
					$posBloc = 0;
					$currBloc=array();
				}
			}
		}
		
		if ($posBloc <=14 && $posBloc >0) {
			// on complète le bloc en cours
			do {
				$currBloc[$posBloc] = self::PCE_NUL;
				$posBloc++;
			} while ($posBloc<15);
			
			$this->blocReady($currBloc);
		}
	}


	/***************************
	** Ajoute le CRC au bloc et le place dans la file d'attente
	** tBloc: bloc de données
	****************************/
	private function blocReady($tBloc) {
		// On prépare le bloc pour être transmis (ajout CRC)
		$tBlocPCE=$this->applyPCE($tBloc);
		$this->blocsToSend[]['bloc']=$tBlocPCE;									// on met le bloc dans la liste des blocs à transmettre
		$this->blocsToSend[(array_key_last($this->blocsToSend) ?? 0)]['new']=true;
	}
	

	/***************************
	** Envoi le premier bloc à transmettre
	** et le(s) supprime de la file d'attente
	** all: si 'true', envoie tous les blocs
	****************************/
	public function flushBuffer($all=false) {
		if (!$this->enabled)
			return;

		if ($this->ack<4 ) {
			if (time()-$this->ackTimeout>2) {
				trigger_error("[PCE] FLUSH PAS ACK RECU");
				$this->startPCE();
			}
			return;
		}

		
		if ($this->sendPaused) {
			return;
		}
		
		if ( $this->timeout!=-1 && (microtime(true) - $this->timeout > 134000)) {
			trigger_error("[PCE] FLUSH ENVOI NUL");
			fwrite($this->socket, self::PCE_NUL);
			$this->timeout = -1;
		}
		
		if ( (microtime(true) - $this->txTimer)*1000000 <= self::PCE_TIMER_DELAY) {
			return;
		}
		
		if (count($this->blocsToSend)>0) {
			if ($this->headerToSend!='') {
					trigger_error("[PCE] FLUSH _SENDHEADER ".print_r($this->headerToSend,true));
					fwrite($this->socket, $this->headerToSend,3);
					$this->headerToSend='';
			}
			
			if ($all)
				$max = count($this->blocsToSend);
			else
				$max = 1;
			
			for ($countSend=0;$countSend<$max; $countSend++) {
				
				if ($this->blocsToSend[0]['new']) {
					if ($this->countMemBlocs>0)
						$prcErr = $this->countMemBlocsErr / ($this->countMemBlocs/100);
					else $prcErr = 0;
					trigger_error('[PCE] MEMO BLOC # '.($this->countMemBlocs % 16).' ['.$this->countMemBlocs.'][ERR '.sprintf('%.02f',$prcErr).'%]');
					$this->memBlocs[$this->countMemBlocs % 16]['bloc']=$this->blocsToSend[0]['bloc'];			// On garde le bloc dans la mémore de blocs
					$this->memBlocs[$this->countMemBlocs % 16]['number']=$this->countMemBlocs;
					$this->countMemBlocs ++;
				}
				
				fwrite($this->socket, pack('C*',...$this->blocsToSend[0]['bloc']),17);
				array_shift($this->blocsToSend);
			}

			$this->txTimer = microtime(true);
			$this->timeout = microtime(true);
		} 
	}

	
	/***************************
	** Amorce la lecture de données
	** en provenance du Minitel
	** Retourne les données lues
	****************************/
	function read() {
		if (!$this->enabled) {
			$socketData = @fread($this->socket, 8192);
			if ($socketData == '')
				return false;
			return $socketData;
		}

		if (!$this->_read())
			return false;
		$s=pack('C*',... $this->buffer);
		$this->buffer=array();
		return $s;
	}


	/***************************
	** Lecture de données
	** en provenance du Minitel et traitement des demandes de renvoi de blocs
	** Retourne true ou false (erreur)
	****************************/
	private function _read() {

		$tTmp = array($this->socket);
			$socketData = @fread($this->socket, 8192);
			
			if ($socketData === false || $socketData==='') {
				trigger_error("[PCE] _READ ERREUR");
				return false;
			} 
			
			$tDatas = unpack('C*',$socketData);
			
			// On traite les données reçues octet par octet
			
			foreach($tDatas as $d) {

				// Gestion de l'acquittement d'activation, à l'arrache...
				// Todo: acquittement de la désactivation
				
				if ($this->ack<4 && $d == self::PCE_NACK) {
					$this->ack=4;
					trigger_error("[PCE] FORCE ACK ");
				}
				
				if ($this->ack<4) {
					switch ($this->ack) {
						case 0:
							if ($d == 0x1B) {
								trigger_error("[PCE] ACK 1 ");
								$this->ack++;
							}
							break;
						case 1:
							if ($d == 0x3A) {
								$this->ack++;
								trigger_error("[PCE] ACK 2 ");
							}
							break;						
						case 2:
							if ($d == 0x73) {
								$this->ack++;
								trigger_error("[PCE] ACK 3 ");
							}
							break;						
						case 3:
							$this->ack++;
							trigger_error("[PCE] ACK 4 START ");
							break;
					}
					$d = 0x00;
					continue;
				}
				// Fin gestion aquittement
				
				if ($d == self::PCE_NACK) { 
					trigger_error("[PCE] RECU NACK - ATTENTE NUMEROBLOC");
					$this->procedureStateRepeat = self::PCE_STATE_REPEAT_WAIT_NBLOC;
					$this->sendPaused = true;										// Demande renvoi: on suspend l'envoi des blocs en cours
				} else if ($this->procedureStateRepeat == self::PCE_STATE_REPEAT_NULL) {
						$this->buffer[]=$d;
				} else {
					trigger_error("[PCE] RECU NUMEROBLOC ".sprintf("%d",$d)." [".($d%16)."]");
					$this->procedureStateRepeat = self::PCE_STATE_REPEAT_NULL;
					
					if ($d<0x40 || $d > 0x4F ||!isset($this->memBlocs[$d%16])) {
						// Le numero de bloc est incorrect
						trigger_error("[PCE] BLOC INCORRECT");
						if (count($this->blocsToSend)>0) {
							// Il ya des blocs à emettre en stock
							$d = 0x40 + $this->countMemBlocs%16;
							$this->headerToSend = sprintf('%c%c%c',self::PCE_SYN,self::PCE_SYN,$d);
							$this->sendPaused = false;
							trigger_error("[PCE] BLOCS EN STOCK - PROCHAIN MEM=".($this->countMemBlocs%16));
						} else {
							// On a pas de blocs, mais le prochain sera considéré comme une réponse à la demande de répétition
							$d = 0x40 + (($this->countMemBlocs%16));
							$this->headerToSend = sprintf('%c%c%c',self::PCE_SYN,self::PCE_SYN,$d);
							$this->sendPaused = false;
							trigger_error("[PCE] BLOCS PROCHAINS - PROCHAIN MEM=".($this->countMemBlocs%16));
						}
					} else {
						$this->headerToSend = sprintf('%c%c%c',self::PCE_SYN,self::PCE_SYN,$d);
						$this->blocToRepeat = $d;
						$this->_repeatBloc();
					}
				}
			}

		return true;
	}
	
	/***************************
	** Ajoute un bloc à répéter dans la file
	** des blocs à transmettre
	****************************/
	private function _repeatBloc() {
		trigger_error("[PCE] REPETE BLOC ".$this->blocToRepeat);
		$tmp = array();
		$idxTmp=0;
		$n = $this->blocToRepeat;
		
		do {
			if (isset($this->memBlocs[($n%16)])) {
				trigger_error("[PCE] ----- REPETE BLOC # ".($n%16). " STOP= # ".$this->countMemBlocs%16);
				$tmp[$idxTmp]['bloc']=$this->memBlocs[($n%16)]['bloc'];
				$tmp[$idxTmp]['new'] = false;
				$idxTmp++;
				$this->countMemBlocsErr++;
			}
			$n++;
		} while(($n%16) != ($this->countMemBlocs%16));

		// On supprimme les éventuels blocs à répéter déjà dans la file
		// Cas ou la demande de répétition arrive quand on répete déjà (pas sûr que cela puisse arriver).
		
		do {
			if (count($this->blocsToSend)<1)
				break;
			if (!$this->blocsToSend[0]['new']) {
				array_shift($this->blocsToSend);
			} else 
				break;
		} while(true);

		
		if (count($tmp)>0) {
			
			$this->blocsToSend=array_merge($tmp,$this->blocsToSend);
		}
		
		$this->sendPaused = false;
	}

	private function startPCE() {
		$this->ackTimeout=time();
		// Envoi de la séquence
		trigger_error("[PCE] START PROCEDURE");
		fwrite($this->socket, self::PCE_PRO2_START_PROCEDURE);
	}

	private function stopPCE() {
		// Envoi de la séquence
		trigger_error("[PCE] STOP PROCEDURE");
		$this->send(self::PCE_PRO2_STOP_PROCEDURE);
		$this->flushBuffer();
	}

	
	function enable() {
		$this->enabled = true;
		$this->_init();
		$this->startPCE();
	}

	function disable() {
		$this->stopPCE();
		$this->enabled = false;
	}

	
	
	/***************************
	** Fonctions de calcul du CRC
	** basées sur le projet MiniGo
	** https://github.com/NoelM
	****************************/

	
	private function crc7BeByte($crc, $data) {
		return (int)self::PCE_SYNTABLE[$crc ^ $data];
	}


	private function getCRC7($data) {
		$crc = 0;
		foreach ($data as $b) {
			$b = (int)$b;
			$crc = $this->crc7BeByte($crc, $b);
		}
		return $crc >> 1;
	}	
	
	
	private function computePCEBlock($buf) {
		$inner = array_slice($buf, 0, 15);
		for($i=count($inner);$i<15;$i++) {
			$inner[$i]=self::PCE_NUL;
		}
		$crc = $this->getCRC7($inner);
		$inner[] = $crc;
		$inner[] = 0;
		return $inner;
	}	

	private function applyPCE($in) {
		$inner = array_slice($in, 0, 15);
		for($i=count($inner);$i<15;$i++) {
			$inner[$i]=self::PCE_NUL;
		}
		
		$tmp = $this->applyParity($inner);
		$crc = $this->getCRC7($tmp);
		$inner[] = $crc;
		$inner[] = 0;
		return $inner;
	}	


	private function applyParity($in) {
		$out = array();
		foreach ($in as $id => $b) {
			$b = (int)$b;
			$out[$id] = $this->getByteWithParity($b);
		}

		return $out;
	}	
	
	private function getByteWithParity($b) {
		$b = (int)$b;
		return $this->bitWriteAt($b, self::PCE_PARITY_BIT_POS, !$this->isByteEven($b));
	}	
	
	private function bitWriteAt($b, $pos, $value) {
		if ($value) {
			return $b | (1 << $pos);
		} else {
			return $b &~ (1 << $pos);
		}
	}	

	private function isByteEven($b) {
		$b = (int)$b;
		$even = true;

		for ($i = 0; $i < self::PCE_PARITY_BIT_POS; $i++) {
			if ($this->bitReadAt($b, $i)) {
				$even = !$even;
			}
		}
		return $even;
	}

	private function bitReadAt($b, $i) {
		$b = (int)$b;
		$i = (int)$i;
		return ($b & (1 << $i)) > 0;
	}

}