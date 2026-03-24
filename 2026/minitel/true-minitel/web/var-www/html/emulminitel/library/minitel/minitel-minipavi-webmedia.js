"use strict"
/**
 * @file minitel-minipavi-webmedia.js
 * @author Jean-arthur Silve <contact@minipavi.fr>
 * @version 1.1
 *
 * Ajout des possibilités multimedia à l'émulateur si connecté à un serveur MiniPavi
 */


// insertion du script API Youtube
var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);


/**
 * @namespace Minitel
 */
var Minitel = Minitel || {}
	
	
Minitel.MiniPaviWebMedia = class {

    constructor(container) {
		this.YTPlayer = null;
		this.intervalId = null;
		this.isFetching = false; 
		this.numKo = 0;
		this.container = container;
		this.callMiniPaviWM(container);
		this.lastEvent = '';
		
		var linkButton=container.getElementsByClassName('mpwb-linkButton');
		if (linkButton.length != 1)
			return;
		this.linkButton = linkButton.item(0);

		var audioPlayer=container.getElementsByClassName('mpwb-audioPlayer');
		if (audioPlayer.length != 1)
			return;
		this.audioPlayer = audioPlayer.item(0);

		var youtubePlayer=container.getElementsByClassName('mpwb-youtubePlayer');
		if (youtubePlayer.length != 1)
			return;
		this.youtubePlayer = youtubePlayer.item(0);

		var videoPlayer=container.getElementsByClassName('mpwb-videoPlayer');
		if (videoPlayer.length != 1)
			return;
		this.videoPlayer = videoPlayer.item(0);

		var imgViewer=container.getElementsByClassName('mpwb-imgViewer');
		if (imgViewer.length != 1)
			return;
		this.imgViewer = imgViewer.item(0);

		this.linkButton.addEventListener('click', function() {
			const fileUrl = this.getAttribute('data-filename');
			const a = document.createElement('a'); 
			a.href = fileUrl;
			a.target = '_blank';
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
		});
		

		var mpwmClose=container.getElementsByClassName('mpwb-close');
		if (mpwmClose.length != 1)
			return;
		this.mpwmClose = mpwmClose.item(0);
	
		var that = this;
		this.mpwmClose.onclick = function() { 
			that.stopYoutubePlayer(that.youtubePlayer);  
			that.stopAudioPlayer(that.audioPlayer); 
			that.stopVideoPlayer(that.videoPlayer); 
			that.stopImgViewer(that.imgViewer);
			that.stopLinkButton(that.linkButton);
			that.closeButton(that.mpwmClose);
		}
	}
	
	
	callMiniPaviWM(container) {
	   var pinValue;
		console.log("MiniPavi webmedia started");	   
		this.intervalId = setInterval(() => {
			if (this.numKo<20 && !this.isFetching) {
				var elem = container.querySelector(
                '[data-minitel="minipaviwebmedia"]'
				);
				
				var sb=container.getElementsByClassName('mpwb-status');
				if (sb.length != 1)
					return;
				sb = sb.item(0);
				
				if (elem == undefined) {
					sb.style.backgroundColor="#000000"; // Noir
					console.log("MiniPavi webmedia undefined");
					return;
				} else {
					pinValue=elem.getAttribute("data-pin");
					if (pinValue!=="") {
					} else {
						sb.style.backgroundColor="#d65204"; // Orange
						return;
					}
				}
				var url = elem.getAttribute("data-url");
			
				this.isFetching = true; 
				const fullUrl = `${url}&pin=${encodeURIComponent(pinValue)}&lastevent=${this.lastEvent}`;
				console.log(fullUrl);
				this.lastEvent = '';
				fetch(fullUrl)
					.then(response => {
						if (!response.ok) {
							sb.style.backgroundColor="#a60003"; // rouge
							throw new Error('Erreur réseau : ' + response.statusText);
						}
						return response.json();
					})
					.then(data => {
						sb.style.backgroundColor="#006200"; // Vert
						this.handleApiResponse(container,data);  
					})
					.catch(error => {
						sb.style.backgroundColor="#d65204"; // Orange
						console.error('Erreur:', error);
					})
					.finally(() => {
						this.isFetching = false; 
					}
				);
				
			}
		}, 1200); 
	}

	
	
	handleApiResponse(container,data) {

		var contentDiv=container.getElementsByClassName('mpwb-content');
		if (contentDiv.length != 1)
			return;
		contentDiv = contentDiv.item(0);

		var that = this;
		
		if (data.result === 'KO') {
			var sb=container.getElementsByClassName('mpwb-status');
			if (sb.length == 1) {
				sb = sb.item(0);
			}
			sb.style.backgroundColor="#d65204"; // Orange
			this.numKo++;
			this.stopYoutubePlayer(this.youtubePlayer);  
			this.stopAudioPlayer(this.audioPlayer);  
			this.stopVideoPlayer(this.videoPlayer);  
			this.stopImgViewer(this.imgViewer);
			this.stopLinkButton(this.linkButton);
			this.closeButton(this.mpwmClose);
		} else if (data.result === 'OK') {
			this.numKo = 0;
			if (data.content === '1') {
				if (data.type === 'IMG') {
					this.stopYoutubePlayer(this.youtubePlayer); 
					this.stopVideoPlayer(this.videoPlayer); 
					this.stopAudioPlayer(this.audioPlayer); 
					this.stopLinkButton(this.linkButton);
					this.imgViewer.src = data.infos;
					this.imgViewer.style.display = 'block';
					this.openButton(this.mpwmClose);
				} else if (data.type === 'SND') {
					this.stopYoutubePlayer(this.youtubePlayer);
					this.stopVideoPlayer(this.videoPlayer); 
					this.stopImgViewer(this.imgViewer);	
					this.stopLinkButton(this.linkButton);						
					this.audioPlayer.src = data.infos;
					this.audioPlayer.style.display = 'block';
					
					this.audioPlayer.onended = function(){that.eventStopped();};
					this.audioPlayer.onplaying = function(){that.eventPlaying();};
					
					
					this.audioPlayer.play();
					this.openButton(this.mpwmClose);
				} else if (data.type === 'YT') {
					this.stopAudioPlayer(this.audioPlayer); 
					this.stopVideoPlayer(this.videoPlayer); 
					this.stopImgViewer(this.imgViewer);		
					this.stopLinkButton(this.linkButton);						
					this.youtubePlayer.style.display = 'block';						
					this.openButton(this.mpwmClose);
					const youtubeUrl = `https://www.youtube.com/embed/${data.infos}?enablejsapi=1&rel=0&origin=`+encodeURIComponent(window.location.origin);
					this.youtubePlayer.innerHTML = `<iframe id="YTPlayer" class="responsive-ytiframe" src="${youtubeUrl}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>`;
					
					this.YTPlayer = new YT.Player('YTPlayer', {
					events: {
						'onReady': that.onPlayerReady,
						'onStateChange': that.onStateChange.bind(this),
					}
					});

				} else if (data.type === 'VID') {
					this.stopYoutubePlayer(this.youtubePlayer);  
					this.stopAudioPlayer(this.audioPlayer); 
					this.stopImgViewer(this.imgViewer);	
					this.stopLinkButton(this.linkButton);						
					this.openButton(this.mpwmClose);
					this.videoPlayer.src = data.infos;
					this.videoPlayer.style.display = 'block';
					
					this.videoPlayer.onended = function(){that.eventStopped();};
					this.videoPlayer.onplaying = function(){that.eventPlaying();};
					
					this.videoPlayer.play();
				} else if (data.type === 'URL') {
					this.stopYoutubePlayer(this.youtubePlayer);  
					this.stopAudioPlayer(this.audioPlayer); 
					this.stopImgViewer(this.imgViewer);	
					this.stopLinkButton(this.linkButton);		
					this.stopVideoPlayer(this.videoPlayer); 						
					this.openButton(this.mpwmClose);
					this.linkButton.setAttribute('data-filename', data.infos); 
					this.linkButton.innerHTML = "Cliquez pour aller vers<br/><b>"+data.infos+"</b>";
					this.linkButton.style.display = 'inline-block';
				}

			}
		}
	}


	eventStopped() {
		this.lastEvent = 'STOP';
		console.log("Arret lecteur");
	}

	eventPlaying() {
		this.lastEvent = 'START';
		console.log("Lecture");
	}


	onPlayerReady(event) {
		console.log('YT READY');
		event.target.playVideo();
	}
	
	onStateChange(event,that) {
		if (event.data == 0)
			this.eventStopped();
		else if (event.data == 1)
			this.eventPlaying();
	}
	
	stopLinkButton(linkButton) {
		linkButton.style.display = 'none';
	}
	
	stopAudioPlayer(audioPlayer) {
		audioPlayer.pause();
		audioPlayer.currentTime = 0;
		audioPlayer.style.display = 'none';
	}

	stopImgViewer(imgViewer) {
		imgViewer.style.display = 'none';
	}

	stopVideoPlayer(videoPlayer) {
		videoPlayer.pause();
		videoPlayer.currentTime = 0;
		videoPlayer.style.display = 'none';
	}

	stopYoutubePlayer(youtubePlayer) {
		youtubePlayer.innerHTML = ''; 
		youtubePlayer.style.display = 'none'; 
	}

	closeButton(button) {
		button.style.display = 'none';
	}

	openButton(button) {
		button.style.display = 'block';
	}
}