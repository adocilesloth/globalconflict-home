var tsdiv = document.createElement("div");
tsdiv.classList.add("panel");
tsdiv.classList.add("gc_homepage_col");
tsdiv.id = "tsstreamscontainer";
var tsh2 = document.createElement("h2");
tsh2.append(document.createTextNode("Streams"));
tsdiv.append(tsh2);
var tsstreamsdiv = document.createElement("div");
tsstreamsdiv.style.fontSize = "13px";
tsstreamsdiv.style.width = "90%";
tsstreamsdiv.style.margin = "0 auto";
tsstreamsdiv.style.paddingBottom = "10px";
tsstreamsdiv.id = "tsstreamsdiv";
tsdiv.append(tsstreamsdiv);
document.getElementById("gc_home_right_columm").append(tsdiv);
if(window.XMLHttpRequest){
	var tsconnection = new XMLHttpRequest();
} else {
	var tsconnection = new ActiveXObject("Microsoft.XMLHTTP");
}
tsconnection.onreadystatechange = function(){
	if(this.readyState == 4 && this.status == 200){
		tsUpdateStreams(this.responseText);
	}
}
tsconnection.open("POST", "twitchstreams.php", true);
tsconnection.send();
setInterval(function(){
	tsconnection.open("POST", "twitchstreams.php");
	tsconnection.send();
}, 60000);
function tsUpdateStreams(tsresponse){
	var tsstreamerdiv, tsstreamerspan1, tsstreamerspan2, tsstreameranchor, tsstreamerspan3, tsstreameremptydiv, tsstreameremptytext;
	document.getElementById("tsstreamsdiv").innerHTML = "";
	if(tsresponse != ""){
		tsresponse = tsresponse.split(";");
		tsresponse.forEach(function(tsstreamer){
			tsstreamer = tsstreamer.split(",");
			tsstreamerdiv = document.createElement("div");
			tsstreamerspan1 = document.createElement("span");
			tsstreamerspan1.style.color = (tsstreamer[1] > 0) ? "#ff0000" : "#aaaaaa";
			tsstreamerspan1.innerHTML = "&#11044; ";
			tsstreamerspan2 = document.createElement("span");
			tsstreameranchor = document.createElement("a");
			tsstreameranchor.href = "https://www.twitch.tv/" + tsstreamer[0];
			tsstreameranchor.setAttribute("target", "_blank");
			tsstreameranchor.append(document.createTextNode(tsstreamer[0]));
			tsstreamerspan2.append(tsstreameranchor);
			tsstreamerspan3 = document.createElement("span");
			tsstreamerspan3.style.float = "right";
			tsstreamerspan3.append(document.createTextNode(tsstreamer[2]));
			tsstreamerdiv.append(tsstreamerspan1);
			tsstreamerdiv.append(tsstreamerspan2);
			tsstreamerdiv.append(tsstreamerspan3);
			document.getElementById("tsstreamsdiv").append(tsstreamerdiv);
		});
	} else {
		tsstreameremptydiv = document.createElement("div");
		tsstreameremptydiv.style.textAlign = "center";
		tsstreameremptydiv.style.marginTop = "10px";
		tsstreameremptytext = document.createTextNode("No Streams Online");
		tsstreameremptydiv.append(tsstreameremptytext);
		document.getElementById("tsstreamsdiv").append(tsstreameremptydiv);
	}
}