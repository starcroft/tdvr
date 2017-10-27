<?php
require_once( dirname( __FILE__ ) . '/class/TransmissionRPC.class.php' );

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
	$rpc = new TransmissionRPC($config['api_url'], $config['api_user'], $config['api_password']);

	$result = $rpc->add( (string) $torrent_url, $download_dir ); // Magic happens here :)
	print_r($result);
	if ($result->result == "success") {
		$res['error']="";
	} else {
		$res['error']=$result->result;
	}
	
	return($res);
}

?>
