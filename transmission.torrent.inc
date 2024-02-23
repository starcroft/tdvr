<?php
##require_once( dirname( __FILE__ ) . '/class/TransmissionRPC.class.php' );
require_once( dirname( __FILE__ ) . '/class/transmission-rpc.class.php' );

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

function add_2_torrent($torrent_url, $download_dir, $ratio = "1.0") {
global $config;
$id=0;
	print "download $torrent_url ".$config['api_url']." ".$config['api_user'];
	$rpc = new TransmissionRPC($config['api_url'], $config['api_user'], $config['api_password']);
	print "download $torrent_url";
	$result = $rpc->add( (string) $torrent_url, $download_dir, false, $ratio ); // Magic happens here :)
	print_r($result);
	if ($result->result == "success") {
		$res['error']="";
	} else {
		$res['error']=$result->result;
	}
	
	return($res);
}


function add_torrent($torrent_url, $download_dir, $ratio) {
global $config;
$id=0;
	$rpc = new TransmissionRPC("trans.starcroft.org", 443, $config['api_user'], $config['api_password']);
	#print "download $torrent_url $ratio";
	$result = $rpc->addTorrent( $torrent_url, $download_dir, false, $ratio ); // Magic happens here :)
	#print_r($result);
	if ($result->result == "success") {
		$res['error']="";
	} else {
		$res['error']=$result->result;
	}
	
	return($res);
}


?>
