<?php
// Add a list of user streams
$streams = array(
"GlobalConflict",
);
$clientID = ""; // Add your client id betweeen the quotes
$clientSecret = ""; // Add your client secret between the quotes
$url = "https://api.twitch.tv/helix/streams";
$first = true;

$filedata = file_get_contents("twitchstreamsdata.data");
$filedata = explode("\r\n", $filedata);
if(time() > ($filedata[0] + 60)){
	foreach($streams as $stream){
		if($first){
			$url .= "?";
			$first = false;
		} else {
			$url .= "&";
		}
		$url .= "user_login=" .$stream;
	}
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => "https://id.twitch.tv/oauth2/token?client_id=$clientID&client_secret=$clientSecret&grant_type=client_credentials",
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => 1,
	));
	$token = curl_exec($ch);
	curl_close($ch);
	$token = json_decode($token);
	$token = $token->access_token;
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => $url,
		CURLOPT_POST => false,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_HTTPHEADER => array("client-id: $clientID", "Authorization: Bearer $token")
	));
	$results = curl_exec($ch);
	curl_close($ch);
	$results = json_decode($results);
	$results = $results->data;
	$resultstring = "";
	foreach($results as $result){
		if($resultstring != "") $resultstring .= ";";
		$resultstring .= $result->user_login .",1," .$result->viewer_count;
	}
	file_put_contents("twitchstreamsdata.data", time() ."\r\n" .$resultstring);
	echo $resultstring;
} else {
	echo $filedata[1];
}	
?>