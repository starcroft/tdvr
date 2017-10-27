<?php


function get_torrent_info($torrent_url){
global $config;
$id=0;
	$array=array("method"=>"auth.login", "params"=>array($config['api_password']), "id"=>$id++);
	$res=post_json($array);
	if ($res['error']) { return($res['error']); }

	$array=array("method"=>"web.download_torrent_from_url", "params"=>array("$torrent_url"), "id"=>$id++);
	$res=(post_json($array));
	if ($torr_path=$res['result']) {
		#print $torr_path." : ".$dir."<br>";

	 $array=array("method"=>"web.get_torrent_info", "params"=>array("$torr_path"), "id"=>$id++);
	 $res=(post_json($array));
	 if ($file_list=$res['result']['files_tree']) {

	 }
}

}

function add_torrent($torrent_url, $download_dir) {
global $config;
$id=0;
	$array=array("method"=>"auth.login", "params"=>array($config['api_password']), "id"=>$id++);
	$res=post_json($array);
	if ($res['error']) { return($res['error']); }

	if (preg_match("/^magnet:/i", $torrent_url)) {
		$res=add_torrent_magnet($torrent_url, $download_dir, $id);
	} else {
		$res=add_torrent_file($torrent_url, $download_dir, $id);
	}
	return($res);
}

function add_torrent_magnet($url, $dir, $id){ 

	$array=array("method"=>"core.add_torrent_magnet", "params"=>array("$url", array("download_location"=>"$dir")), "id"=>$id++);
	$res=post_json($array);
	return($res);
}

function add_torrent_file($url, $dir, $id) {

	$array=array("method"=>"web.download_torrent_from_url", "params"=>array("$url"), "id"=>$id++);
	$res=(post_json($array));
	if ($torr_path=$res['result']) {
		file_put_contents("./tdvr.log",$url." ".$torr_path." : ".$dir."\n");
		#$array=array("method"=>"web.add_torrents", "params"=>array(array(array("path"=>"$torr_path", "options"=>array("download_location"=>"$dir")))), "id"=>$id++);
		#$array=array("method"=>"web.add_torrents", "params"=>array(array("path"=>"$torr_path", "options"=>array("download_location"=>"$dir"))), "id"=>$id++);
		
		$array=array("method"=>"web.add_torrents", "params"=>array(array(array("path"=>"$torr_path", "options"=>NULL))), "id"=>$id++);

	#	{"method":"web.add_torrents","params":[[{"path":"\/tmp\/delugeweb-F3YTyi\/Lost.Survivors.S01E01.Bungle.in.the.Jungle.720p.HDTV.x264-KNiFESHARP.torrent","options":[]}]],"id":2}
#       {"method":"web.add_torrents","params":[[{"path":"/tmp/ubuntu-12.04.1-desktop-amd64.iso.torrent","options":null}]],"id":1}

		$res=post_json($array);
		file_put_contents("./tdvr.log", print_r($res));
	}
	return ($res);
}
	
function post_json($array) {
global $config;
$content = json_encode($array);
	$curl = curl_init($config['api_url']);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_ENCODING, "gzip");
	curl_setopt($curl, CURLOPT_COOKIEJAR, "./cookie.txt");
	curl_setopt($curl, CURLOPT_COOKIEFILE, "./cookie.txt");

   curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
	$json_response = curl_exec($curl);
	$response = json_decode($json_response, true);
	if (! $response['result']) {
		echo "<br>".$content;
		echo "<br>Error: call to URL ".$config['api_url']." failed, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl);
      echo "<br>"; 
	 }
	curl_close($curl);
	return($response);
}
?>
