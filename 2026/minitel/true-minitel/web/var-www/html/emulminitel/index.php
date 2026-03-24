<?php
if (!isset($_REQUEST['gw'])) {
		// Ligne à modifier en indiquant l'adresse de votre passerelle (et port) 
		//$gw = 'ws://192.168.0.85:8182/';
		$gw = "ws://".$_SERVER['SERVER_NAME'].":8182/";
		//////////////////////////////////////////////////////////
} else {
		$gw = $_REQUEST['gw'];
}

if (!isset($_REQUEST['url'])) {
		$url = $gw;
} else {
		$url = $gw.'?url='.urlencode($_REQUEST['url']);
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta http-equiv="Access-Control-Allow-Origin" content="*">
    <title>Emulateur Minitel</title>
    <link rel="stylesheet" href="css/minitel-keyboard.css" />
	<link rel="stylesheet" href="css/minitel-minipavi-webmedia.css" />
	<link rel="stylesheet" href="css/crt.css" />
	
		
    <meta name="keywords" content="MiniPavi, minitel, vintage, années 80, technologie, videotex, stum1b, teletel, 3615" />
    <meta name="description" content="Créez votre services Minitel facilement! Voyagez dans le passé et aidez à la sauvergarde du patrimoine technologique !" />
	<meta name="author" content="Jean-arthur Silve" />	
	<meta property="og:type" content="website">
	<meta property="og:site_name" content="MiniPAVI">
	<meta property="og:description" content="Créez votre services Minitel facilement! Voyagez dans le passé et aidez à la sauvergarde du patrimoine technologique !">
    <meta property="og:title" content="Accueil service MiniPAVI">
	<meta property="og:url" content="http://www.minipavi.fr">

  </head>
  <body>
    <div>
	<br/>
	
	<x-minitel id="emul-1" data-socket="<?php echo $url;?>"

                 data-speed="1200"
                 data-color="true">
		
		<div id="oldeffect" class="minitel-oldeffect-off"   >
			<canvas class="minitel-screen" data-minitel="screen"></canvas>
			<canvas class="minitel-cursor" data-minitel="cursor"></canvas>
			<div id="scanlines" class="minitel-scanlines"></div>
			<div id="crt" class="minitel-crt-off"></div>
		</div>
		
		
		<import src="import/minitel-minipavi-webmedia.html"></import>
        <import src="import/minitel-keyboard.html"></import>
		<br/>

		<div style="display:flex;justify-content: space-evenly;">
		<div class="checkbox-wrapper-14">
		  <input id="oldminitelSw" type="checkbox" class="switch" onchange="oldMinitelChange('oldeffect','crt',this.checked);">
		  <label for="oldminitelSw">Minitel fatigué &#128577;</label>
		</div>
		<div class="checkbox-wrapper-14">
		  <input id="scanlinesSw" type="checkbox" class="switch" onchange="scanlinesChange('scanlines',this.checked);" checked>
		  <label for="scanlinesSw">Scanlines</label>
		</div>
		</div>
		
		<font  style="font-size:14px;color:White;font-family:Arial,Helvetica">
		<br/><code><?php echo $url;?></code><br/><br/>
		<?php if (!isset($_REQUEST['gw'])) { ?>
			Après la connexion à un service, "Cnx/fin" pour revenir à la page d'accueil MiniPavi.
			<br/>
			(sauf en cas d'accès direct à un service)
			<br/><br/>
		<?php } ?>
		Plus d'infos sur MiniPAVI - <a href="https://www.minipavi.fr/" style="color:white;" target="_blank">https://www.minipavi.fr/</a>
		<br/>Emulateur Minitel Frédéric BISSON (version modifiée MiniPavi) - <a href="https://minitel.cquest.org/" style="color:white;" target="_blank">https://minitel.cquest.org/</a>
		<br/><br/>
		
		</font>
        <audio class="minitel-beep" data-minitel="beep">
          <source src="sound/minitel-bip.mp3" type="audio/mpeg"/>
          Too bad, your browser does not support HTML5 audio or mp3 format.
        </audio>      



      </x-minitel>
    </div>

	<script>
	function oldMinitelChange(id,id2, checked) {
		var e1 = document.getElementById(id);
		var e2 = document.getElementById(id2);
		if (checked) {
			e1.className='minitel-oldeffect';
			e2.className='minitel-crt';
		} else { 
			e1.className='minitel-oldeffect-off';
			e2.className='minitel-crt-off';
		}
	}
	function scanlinesChange(id, checked) {
		var e = document.getElementById(id);
		if (checked)
			e.className='minitel-scanlines';
		else e.className='minitel-scanlines-off';
	}
	
	</script>

    <script src="library/generichelper/generichelper.js"></script>
    <script src="library/import-html/import-html.js"></script>
    <script src="library/autocallback/autocallback.js"></script>
    <script src="library/query-parameters/query-parameters.js"></script>
    <script src="library/finite-stack/finite-stack.js"></script>
    <script src="library/key-simulator/key-simulator.js"></script>
    <script src="library/settings-suite/settings-suite.js"></script>
    <script src="library/minitel/constant.js"></script>
    <script src="library/minitel/protocol.js"></script>
    <script src="library/minitel/elements.js"></script>
    <script src="library/minitel/text-grid.js"></script>
    <script src="library/minitel/char-size.js"></script>
    <script src="library/minitel/font-sprite.js"></script>
    <script src="library/minitel/page-cell.js"></script>
    <script src="library/minitel/vram.js"></script>
    <script src="library/minitel/vdu-cursor.js"></script>
    <script src="library/minitel/vdu.js"></script>
    <script src="library/minitel/decoder.js"></script>
    <script src="library/minitel/keyboard.js"></script>
    <script src="library/minitel/minitel-emulator.js"></script>
    <script src="library/minitel/start-emulators.js"></script>
	<script src="library/minitel/minitel-minipavi-webmedia.js"></script>
	
    <script src="app/minitel.js"></script>

  </body>
</html>
