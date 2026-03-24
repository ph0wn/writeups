<?php
/**
 * @file class.Stats.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Avril 2024
 * PHP 8.2 (CLI) version Unix
 *
 * Statistiques
 *
 */
 

class Stats {
	
	const STATS_FILENAME = 'stats-';
	
	public $stats;
	private $fileName;
	
	function __construct($filePath='') {
		if ($filePath=='')
			$filePath='./';
		else if (substr($filePath,-1)!='/')
			$filePath.='/';
		
		$this->fileName = $filePath.self::STATS_FILENAME;
		return;
	}
	
	
	function addStats($startTime,$stopTime,$type) {
		$duration = $stopTime - $startTime;
		if ($duration<=0)
			return false;
		if ($duration<10)
			return true;
		$today = time();
		$fileName = $this->fileName.date('Ym',$today).'.stats';
		if (!file_exists($fileName)) {
			$f = fopen($fileName,'w');
			flock($f,LOCK_EX);
			$this->stats = array();
		} else {
			$f = fopen($fileName,'r+');
			if (!$f)
				return false;
			flock($f,LOCK_EX);
			$r = fgets($f);
			if (($this->stats = @unserialize($r)) === false) {
				fclose($f);
				return false;
			}
		}
		
		$key = date('d');
		if (isset($this->stats[$key][$type])) {
			$this->stats[$key][$type]['count']++;
			$this->stats[$key][$type]['duration']+=$duration;
		} else {
			$this->stats[$key][$type]['count']=1;
			$this->stats[$key][$type]['duration']=$duration;
		}
		fseek($f,0);
		fputs($f,serialize($this->stats));
		fclose($f);		
		return true;
	}


	function loadStats($mm,$YYYY) {
		$this->stats = array();
		$fileName = $this->fileName.sprintf('%04d%02d',$YYYY,$mm).'.stats';
		if (!file_exists($fileName)) {
			return true;
		}
		$f = fopen($fileName,'r');
		if (!$f)
			return false;
		flock($f,LOCK_EX);
		$r = fgets($f);
		fclose($f);
		if (($this->stats = @unserialize($r)) === false) 
			return false;
		else return true;
	}
}