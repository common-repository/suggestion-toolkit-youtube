var sggtool_player;

function sggtool_showVideo(vid){
	
	rpwl_player = new YT.Player('sggtool_youtubeVideoObject', {
          height: '360',
          width: '640',
          videoId: vid,
          events: {
            'onReady': (event)=>{ event.target.playVideo();},
            'onStateChange': (event)=>{}
          }
        });

	document.querySelector('.sggtool_youtubeVideoBg').classList.remove("hidden");
}

function sggtool_hideVideo(){
	rpwl_player.destroy();
	document.querySelector('.sggtool_youtubeVideoBg').classList.add("hidden");
}
