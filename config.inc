<?php
ini_set('error_reporting', E_ERROR);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$config=array("api_url"=>"", "api_user"=>"", "api_password"=>"");

include './Moinax/TvDb/Http/HttpClient.php';
include './Moinax/TvDb/Http/CurlClient.php';
include './Moinax/TvDb/CurlException.php';
include './Moinax/TvDb/Client.php';
include './Moinax/TvDb/Serie.php';
include './Moinax/TvDb/Banner.php';
include './Moinax/TvDb/Episode.php';
use Moinax\TvDb\Client;
$apiKey = $_ENV['TVDB_API_KEY];

$tvdb = new Client("http://thetvdb.com", $apiKey);

$dvrdb= mysql_connect($_ENV['MARIADB_SERVICE_HOST'], $_ENV['MYSQL_DB_USERNAME'] ,$_ENV['MYSQL_DB_PASSWORD'])
 or exit('Could not connect: ' . mysql_error()); 
$db = mysql_select_db($_ENV['MYSQL_DB_NAME'], $dvrdb);

$urls=array(
"https://www.torrenting.com/get_rss.php?feed=direct",
"https://www.scenetime.com/get_rss.php?feed=direct",
);


$video_list=array("x264", "h264", "xvid", "divx", "avc", "vc1");
$audio_list=array("ac3", "dd5", "dts", "aac", "flac");
$quality_list=array("bluray", "brrip", "bdrip", "avchd", "webdl", "webrip", "hdtv", "hdrip", "br-rip", "bd-rip", "blu-ray");
$resolution_list=array("720p", "1080p");

$config['save_dir']="/mnt/stick/done";
$config['hide_hd']="0";
$config['blocked']=array('vostfr','hevc','encodeking', 'micromkv', 'chamee', '4playhd', 'knix', 'evolve', 'subtitulado','Legendado','swesub','hebsub','dutch','Subtitulos','legendas', '-msd', 'german', 'dubbed', 'killers mp4', 'LOL mp4', 'qaac', 'nl subs', 'fqm mp4', 'HD-TS', 'HDTS', ' HC ', ' ita ', 'fum mp4', 'mp4 rar', 'dublado', 'nordic', 'french', 'vose','JBilbo', 'tla mp4', 'vtv mp4', 'x265');
date_default_timezone_set("Europe/London"); 
?>
