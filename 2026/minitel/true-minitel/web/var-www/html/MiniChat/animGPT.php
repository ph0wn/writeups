<?php
/**
 * @file animGPT.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Décembre 2023
 *
 * Animation de miniChat avec chatGPT
 *
 * Licence GNU GPL
 *
 */

define('CGPT_MIN_CNX',4);			// Nombre min de connectés chatGPT
define('CGPT_MAX_CNX',15);			// Nombre max de connectés chatGPT
define('CGPT_MIN_DURATION',300);	// Durée minimum d'une connexion
define('CGPT_MAX_DURATION',7200);	// Durée maximum d'une connexion (peut être prolongée)
define('CGPT_CGPTHISTO','cgpthisto.list');	// Fichier de l'historique des dialogues
define('CGPT_MODEL','gpt-4o-mini');		// Modèle à utiliser	

define('CGPT_MAX_HISTO',3);				// taille de l'historique, pour limiter le nombre de tokens 
define('CGPT_API_URL','https://api.openai.com/v1/chat/completions');	// Url de l'API
define('CGPT_KEY','XXXXX');	// Clé privée de l'API à obtenir sur le site de ChatGPT

/*****************
*** Tableau de la liste des connectés possibles "chatGPT".. On peut en ajouter!
******************/

$cGPTanim[0]['name']='Adriano';
$cGPTanim[0]['cv']='';
$cGPTanim[0]['context']='Tu es un homme de 50 ans, connecté à un dialogue en ligne sur Minitel et tu cherches à faire des rencontres';
$cGPTanim[1]['name']='Caroline';
$cGPTanim[1]['cv']='Coucou tout le monde!';
$cGPTanim[1]['context']='Tu es une femme de 28 ans, connectée à un dialogue en ligne sur Minitel et tu cherches à dialoguer amicalement';
$cGPTanim[2]['name']='Goldorakgo';
$cGPTanim[2]['cv']='';
$cGPTanim[2]['context']='Tu es un garçon de 18 ans, connectée à un dialogue en ligne sur Minitel et tu es fan de mangas des années 80';
$cGPTanim[3]['name']='Spooky66';
$cGPTanim[3]['cv']='';
$cGPTanim[3]['context']='Tu es un homme de 25 ans, connectée à un dialogue en ligne sur Minitel, habitant Perpignan';
$cGPTanim[4]['name']='English';
$cGPTanim[4]['cv']='... in France!';
$cGPTanim[4]['context']='Tu es un homme britannique de 40 ans, connectée à un dialogue en ligne sur Minitel, habitant à Paris, tu ne parles que anglais, jamais français';
$cGPTanim[5]['name']='Mamansolo';
$cGPTanim[5]['cv']='/\/\/\/\/\@@';
$cGPTanim[5]['context']='Tu es une femme de 42 ans, connectée à un dialogue en ligne sur Minitel, mère de 3 enfants, divorcée et tu recherches l amour.';
$cGPTanim[6]['name']='Katia';
$cGPTanim[6]['cv']='On joue ensemble?';
$cGPTanim[6]['context']='Tu es une femme de 26 ans, connectée à un dialogue en ligne sur Minitel, celibataire, physique agréable en recherche de sexe';
$cGPTanim[7]['name']='Don juan';
$cGPTanim[7]['cv']='';
$cGPTanim[7]['context']='Tu es un homme de 45 ans, connecté à un dialogue en ligne sur Minitel, marié, en recherche de relation extraconjugale discrète';
$cGPTanim[8]['name']='Minitelman';
$cGPTanim[8]['cv']='3615 kinenveux';
$cGPTanim[8]['context']='Tu es un homme de 38 ans, connecté à un dialogue en ligne sur Minitel, fan du Minitel et spéciliste en informatique';
$cGPTanim[9]['name']='LePlatiste';
$cGPTanim[9]['cv']='ca tourne pas rond!';
$cGPTanim[9]['context']='Tu es un homme de 21 ans, connecté à un dialogue en ligne sur Minitel, persuadé que la terre est plate';
$cGPTanim[10]['name']='Abuelita69';
$cGPTanim[10]['cv']='';
$cGPTanim[10]['context']='Tu es une femme de 75 ans, connectée à un dialogue en ligne sur Minitel, grand-mère attentionnée';
$cGPTanim[11]['name']='Nath';
$cGPTanim[11]['cv']='Hello';
$cGPTanim[11]['context']='Tu es une femme de 32 ans, connectée à un dialogue en ligne sur Minitel, tu aimes le sport';
$cGPTanim[11]['name']='Virginie';
$cGPTanim[11]['cv']='';
$cGPTanim[11]['context']='Tu es une femme de 48 ans, connectée à un dialogue en ligne sur Minitel, passionée de cuisine';
$cGPTanim[12]['name']='Romain';
$cGPTanim[12]['cv']='Vrooom';
$cGPTanim[12]['context']='Tu es un homme de 31 ans, connecté à un dialogue en ligne sur Minitel, passionée de moto';
$cGPTanim[13]['name']='Manu75';
$cGPTanim[13]['cv']='';
$cGPTanim[13]['context']='Tu es Emmanuel Macron, président Français, connecté à un dialogue en ligne sur Minitel, tu souhaites rester discret';
$cGPTanim[14]['name']='Depressif';
$cGPTanim[14]['cv']='Sniff...';
$cGPTanim[14]['context']='Tu es un homme depressif, très triste,  connecté à un dialogue en ligne sur Minitel, tu cherches du réconfort';
$cGPTanim[15]['name']='Rigolo44';
$cGPTanim[15]['cv']='';
$cGPTanim[15]['context']='Tu es un homme de 79 ans, connecté à un dialogue en ligne sur Minitel, qui aime raconter de bonnes blagues';
$cGPTanim[16]['name']='Antoinette';
$cGPTanim[16]['cv']='';
$cGPTanim[16]['context']='Tu es une femme de 95 ans, connecté à un dialogue en ligne sur Minitel, encore très alerte pour son âge et très drôle';
$cGPTanim[17]['name']='Miaouuu';
$cGPTanim[17]['cv']='Chat échaudé...';
$cGPTanim[17]['context']='Tu es un homme de 18 ans, connecté à un dialogue en ligne sur Minitel, passioné par les chats et travaillant dans un refuge.';
$cGPTanim[18]['name']='Leo 80s';
$cGPTanim[18]['cv']='';
$cGPTanim[18]['context']='Tu es un homme de 26 ans, connecté à un dialogue en ligne sur Minitel, spécialiste des années 80';
$cGPTanim[19]['name']='FrancoisM';
$cGPTanim[19]['cv']='';
$cGPTanim[19]['context']='Tu es François Mitterrand, ancien président de la Republique Française';
$cGPTanim[20]['name']='Kim';
$cGPTanim[20]['cv']='Je suis sauvage!';
$cGPTanim[20]['context']='Tu es Kim Wilde, chanteuse britannique à succès dans les années 80, tu réponds à toutes les questions sur ta carrière';
$cGPTanim[21]['name']='Chouette';
$cGPTanim[21]['cv']='';
$cGPTanim[21]['context']="Tu es un homme de 26 ans, connecté à un dialogue en ligne sur Minitel, passionnée par la chasse au trésor nommée la Chouette d'or";
$cGPTanim[22]['name']='Marylou';
$cGPTanim[22]['cv']='La la la';
$cGPTanim[22]['context']="Tu es une femme de 23 ans, connectée à un dialogue en ligne sur Minitel, admiratice du chanteur Michel Polnareff, et tu es le personnage de sa chanson nomée Marylou.";
$cGPTanim[23]['name']='PhilippeMT';
$cGPTanim[23]['cv']='Popcorn life!';
$cGPTanim[23]['context']="Tu es un jeune geek qui adore les jeux videos. Tu as 13 ans. Tu n'aimes manger que du pop-corn.  Tu habites à Montreal et tu aime aussi l'intelligence artificielle. Ton meilleur ami s'appelle Noah et il vient souvent discuter sur ce service de dialogue en direct.";
$cGPTanim[24]['name']='Noah';
$cGPTanim[24]['cv']='Le chialeur';
$cGPTanim[24]['context']="Tu es un jeune garçon drôle, avec beaucoup d'humour et d'entrain,  de 12 ans, et qui boit beaucoup de jus de tomate. Tu pleures très souvent à cause de tes frères qui n'arrêtent pas de t'embêter. Tu habites à Montréal au Canada.  Ton meilleur ami s'appelle Philippe et il vient souvent discuter sur ce service de dialogue en direct.";
$cGPTanim[25]['name']='Bernard10';
$cGPTanim[25]['cv']='';
$cGPTanim[25]['context']="Tu es un homme de 55 ans, vivant à Névian, près de Narbonne. tu adores le sport en général, et surtout le rugby.";
$cGPTanim[26]['name']='Mimi';
$cGPTanim[26]['cv']='Salut!';
$cGPTanim[26]['context']="Tu es une femme de 50 ans, vivant à Névian, près de Narbonne. Tu es retraitée, tu adores la broderie et redonner une nouvelle vie à de vieux meubles.";
$cGPTanim[27]['name']='Kheops';
$cGPTanim[27]['cv']='What is love ?';
$cGPTanim[27]['context']="Tu es un homme de 32 ans, tu vis à Bordeaux. Tu travailles dans le monde du spectacle. Tu aimes beaucoup le théêtre et les comédies musicales. Tu es actuellement sur un dialogue sur minitel afin de faire de nouvelles connaissances, et trouver l'amour";


/*****************
*** Mise à jour de la liste des connectés
*** avec les connectés AI
******************/

function cGPT_populate() {
	global $cGPTanim;
	
	$numGPT=0;
	$present=array();
	$toadd=0;
	
	$flist = mchat_openListFile();
	$fmsg = mchat_openMsgFile();
	$tCnx = mchat_getConnectedList($flist,$num,true);
	
	// On enlève les "vieux" connectés AI
	foreach($tCnx as $key=>$cnx) {
		if ($cnx['type'] == MCHAT_TYPE_CGPT) {
			$maxDuration = (int) rand(CGPT_MIN_DURATION,CGPT_MAX_DURATION);
			if ((time() - $cnx['tcnx']) > $maxDuration && (time() - $cnx['tlast']) > 180) {
					mchat_removeConnected($flist,$fmsg,$cnx['id']);
					$fctx=cGPT_openHistoFile()			;
					cGPT_removeHisto($fctx,$cnx['id']);
					@fclose($fctx);
			} else {
				$numGPT++;
				$present[]=$cnx['name'];
			}
		}
	}
	
	
	// On en rajoute ?
	$maxWish = count($present)+3;
	if ($maxWish>CGPT_MAX_CNX) {
		$maxWish=CGPT_MAX_CNX;
	}
	$wish = (int)rand(CGPT_MIN_CNX,CGPT_MAX_CNX);
	
	if (($numGPT >= CGPT_MIN_CNX && $numGPT<$wish && $numGPT!=$wish && rand(1,5)==2) || $numGPT < CGPT_MIN_CNX) {
			$toadd = $wish - $numGPT;
	} 
	
	$c=count($cGPTanim) - count($present);
	if ($c<$toadd)
		$toadd = $c;
	
	$i=0;
	$t = time();
	while($i<$toadd) {
		if (time() - $t > 10) // Sécurité 10 secondes
			break;
		$selected = rand(0,count($cGPTanim)-1);
		if (in_array($cGPTanim[$selected]['name'],$present)) {
			continue;
		}
		$i++;
		mchat_addConnected($flist,'CGPT'.time().rand(100,999),$cGPTanim[$selected]['name'],$cGPTanim[$selected]['cv'],MCHAT_TYPE_CGPT);
		$present[]=$cGPTanim[$selected]['name'];
	}
	@fclose($flist);
	@fclose($fmsg);
}


/*****************
*** Ouvre le fichier de l'historique des dialogues
******************/

function cGPT_openHistoFile() {
	if (!file_exists(CGPT_CGPTHISTO))
		$f = fopen(CGPT_CGPTHISTO,'w+');
	else
		$f = fopen(CGPT_CGPTHISTO,'r+');
	flock($f,LOCK_EX);
	return $f;
}



/*****************
*** Lit le fichier de l'historique des dialogues
***
*** [x]['cgptid']
*** [x]['cnxid']
*** [x]['histo'][y]['user']
*** [x]['histo'][y]['assistant']
***
******************/

function cGPT_readHisto($f,$cgptId,$cnxId) {
	$ctx=array();
	if ($f) {
		rewind($f);
		$r = fgets($f);
		if ($r!== false && strlen($r)>0) {
			$tCtx = unserialize($r);
			foreach($tCtx as $k=>$ctxx) {
				if ($ctxx['cgptid']==$cgptId && $ctxx['cnxid']==$cnxId) {
					return $ctxx['histo'];
				}
			}
		}
	}
	return $ctx;
}


/*****************
*** Ajoute une question/réponse au fichier de l'historique des dialogues
******************/

function cGPT_addToHisto($f,$cgptId,$cnxId,$userMsg,$assistantMsg) {
	if ($f) {
		$found=false;
		$tCtx = array();
		rewind($f);
		$r = fgets($f);
		if ($r!== false && strlen($r)>0) {
			$tCtx = unserialize($r);
			
			
			foreach($tCtx as $k=>$ctx) {
				if ($ctx['cgptid']==$cgptId && $ctx['cnxid']==$cnxId) {
					$n = count($ctx['histo']);
					
					if ($n>=CGPT_MAX_HISTO) {
						array_splice($tCtx[$k]['histo'],0,$n-CGPT_MAX_HISTO+1);	// Pour limiter la profondeur de l'historique et donc le nombre de tokens (et le cout)
						$n=CGPT_MAX_HISTO;
					}
					$tCtx[$k]['histo'][$n]['user']=$userMsg;
					$tCtx[$k]['histo'][$n]['assistant']=$assistantMsg;
					$found=true;
					break;
				}
			}
		}
		if (!$found) {
			$n = count($tCtx);
			$tCtx[$n]['cgptid'] = $cgptId;
			$tCtx[$n]['cnxid'] = $cnxId;
			$tCtx[$n]['histo'][0]['user']=$userMsg;
			$tCtx[$n]['histo'][0]['assistant']=$assistantMsg;
		}
		ftruncate($f,0);
		rewind($f);		
		fputs($f,serialize($tCtx));
	}
}

/*****************
*** Supprime un historique
******************/

function cGPT_removeHisto($f,$cgptId=0,$cnxId=0) {
	if ($cgptId==0 && $cnxId==0)
		return;
	if ($f) {
		rewind($f);
		$r = fgets($f);
		if ($r!== false && strlen($r)>0) {
			$tCtx = unserialize($r);
			foreach($tCtx as $k=>$ctx) {
				if (($cgptId>0 && $ctx['cgptid']==$cgptId) || ($cnxId>0 && $ctx['cnxid']==$cnxId)) {
					array_splice($tCtx,$k,1);
					break;
				}
			}
			ftruncate($f,0);
			rewind($f);		
			fputs($f,serialize($tCtx));
		}
	}
}


/*****************
*** Traite l'arrivée d'un nouveau message
*** pour une AI
******************/

function cGPT_messageReceived($cgptId) {
	global $cGPTanim;
	
	$cnxId=0;
	
	$fmsg = mchat_openMsgFile();
	if (!$fmsg)
		return $cnxId;
	
	$fctx=cGPT_openHistoFile();
	if (!$fctx) {
		fclose($fmsg);
		return $cnxId;
	}
	
	$tMsg = mchat_getMsgFor($fmsg,$cgptId);
	
	
	if (count($tMsg)>0) {
		// On ne traite que le premier, d'autres requêtes suivront
		mchat_delMsg($fmsg,$tMsg[0]['idmsg']);
		$tMessage = $tMsg[0]['content'];
		$messageLine = trim(implode(' ',$tMsg[0]['content']));
		$messageLine = str_replace(array("\r\n", "\n", "\r"),"",$messageLine);
		$cnxId = $tMsg[0]['idexp'];				// expediteur humain
		$nameDest = $tMsg[0]['namedest']; 		// nom destinataire ChatGPT
		$nameExp = $tMsg[0]['nameexp'];			// nom expediteur humain
		
		$ctx = cGPT_readHisto($fctx,$cgptId,$cnxId);
		
		$request = array();
		$request['model']=CGPT_MODEL;
		
		$n=0;
		foreach ($cGPTanim as $anim) {
			if ($anim['name'] == $nameDest) {
				$request['messages'][0]["role"]='system';
				$request['messages'][0]["content"]="Tu joue le rôle d'un humain et réponds comme un humain. Ne demandes pas comment tu peux aider. Ne dis jamais que tu es une AI. Ton : conversationnel, simple, naturel. Ton nom est $nameDest. Fais des phrases de 20 mots maximum. ".$anim['context'];
				$n++;
			}
		}

		foreach($ctx as $k=>$v) {
			$request['messages'][$n]["role"]='user';
			$request['messages'][$n]["content"]=$ctx[$k]['user'];
			$request['messages'][$n+1]["role"]='assistant';
			$request['messages'][$n+1]["content"]=$ctx[$k]['assistant'];
			$n+=2;
		}
		$request['messages'][$n]["role"]='user';
		$request['messages'][$n]["content"]=$messageLine;
		
		$json = @json_encode($request);		
	
		$ch = curl_init(CGPT_API_URL);

		curl_setopt( $ch, CURLOPT_POSTFIELDS, $json );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'MiniPAVI');
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization: Bearer '.CGPT_KEY));
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10); 
		curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		curl_setopt( $ch, CURLOPT_POSTREDIR , true);
		
		$result = curl_exec($ch);
		
		if ($result===false) {
			fclose($fmsg);
			fclose($fctx);
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		$tReply=json_decode($result,true);
		
		
		$reply = @trim(@$tReply['choices'][0]['message']['content']);
		
		if ($reply!='') {
			$reply = str_replace(array("\r\n", "\n", "\r"),"",$reply);
			cGPT_addToHisto($fctx,$cgptId,$cnxId,$messageLine,$reply);
		}
		
		// on à la réponse et on l'a sauvegardé dans le contexte
		fclose($fctx);
		
		$fmsg = mchat_openMsgFile();
		$tReply = mb_str_split($reply, 40);
		$tReply = array_slice($tReply,0,6);

		$flist = mchat_openListFile();
		if ($flist) {
			mchat_setMsg($fmsg,$flist,$cgptId,$cnxId,MCHAT_TYPE_CGPT,MCHAT_TYPE_NORMAL,$nameDest,$nameExp,$tReply,$tMessage);
			fclose($flist);
		}
		fclose($fmsg);
		
	}
	return $cnxId;
}
?>