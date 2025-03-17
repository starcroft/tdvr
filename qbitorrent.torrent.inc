<?php
require 'vendor/autoload.php';

use AldoBarr\QBittorrent;

function add_torrent($torrent_url, $download_dir, $ratio) {
	global $config;
    $qbt = new QBittorrent('https://qbit.starcroft.org', '443', $config['api_user'], $config['api_password']);
    $id=0;
    $response = $qbt->request('app/version');
    #echo $response->getBody()->getContents();
	#echo $torrent_url;
	echo $ratio;
	$response=$qbt->addTorrent(urls: [$torrent_url], ratio_limit: $ratio);
	echo $response;
}

function download_torrent($torrent_url, $path) {
	$basename=basename($torrent_url);
	$basename=preg_replace("/\?.*$/", "" ,$basename);
	$basename=preg_replace("/\s/", ".", $basename);
	$contents=file_get_contents($torrent_url);
	return(base64_encode($contents));
}

?>