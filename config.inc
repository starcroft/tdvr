<?php
ini_set('error_reporting', E_ERROR);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$config=array("api_url"=>getenv('BACKEND_URL'), "api_user"=>getenv('BACKEND_USERNAME'), "api_password"=>getenv('BACKEND_PASSWORD'));

include './Moinax/TvDb/Http/HttpClient.php';
include './Moinax/TvDb/Http/CurlClient.php';
include './Moinax/TvDb/CurlException.php';
include './Moinax/TvDb/Client.php';
include './Moinax/TvDb/Serie.php';
include './Moinax/TvDb/Banner.php';
include './Moinax/TvDb/Episode.php';
include './Moinax/TvDb/Exception.php';
include './Moinax/TvDb/XmlException.php';

use Moinax\TvDb\Client;
$apiKey = getenv('TVDB_API_KEY');

$tvdb = new Client("https://thetvdb.com", $apiKey);

require 'TVMaze/TVMaze.php';
require 'TVMaze/TVProduction.php';
require 'TVMaze/TVShow.php';
require 'TVMaze/Actor.php';
require 'TVMaze/Character.php';
require 'TVMaze/Crew.php';
require 'TVMaze/Episode.php';
require 'TVMaze/AKA.php';
require 'TVMaze/Client.php';

$tvmaze = new JPinkney\TVMaze\Client;

$dvrdb= mysql_connect(getenv('MARIADB_SERVICE_HOST'), getenv('MYSQL_DB_USERNAME') ,getenv('MYSQL_DB_PASSWORD'))
 or exit('Could not connect: ' . mysql_error());
$db = mysql_select_db(getenv('MYSQL_DB_NAME'), $dvrdb);

$video_list=array("x264", "h264", "xvid", "divx", "avc", "vc1", "hevc", 'x265');

$audio_list=array("ac3", "dd5", "dts", "aac", "flac");
$quality_list=array("bluray", "brrip", "bdrip", "avchd", "webdl", "webrip", "hdtv", "hdrip", "br-rip", "bd-rip", "blu-ray");
$resolution_list=array("720p", "1080p");

$config['save_dir']="/mnt/stick/done";
$config['hide_hd']="0";
$config['blocked']=array('vostfr','encodeking', 'micromkv', 'chamee', '4playhd', 'knix', 'evolve', 'subtitulado','Legendado','swesub','hebsub','dutch','Subtitulos','legendas', '-msd', 'german', 'dubbed', 'killers mp4', 'LOL mp4', 'qaac', 'nl subs', 'fqm mp4', 'HD-TS', 'HDTS', ' HC ', ' ita ', 'fum mp4', 'mp4 rar', 'dublado', 'nordic', 'french', 'vose','JBilbo', 'tla mp4', 'vtv mp4');
$config['video_map']=array('h 264' => 'x264', 'mp4' => 'x264', 'x264' => 'x264', 'h264' => 'x264', 'xvid' => 'xvid', 'divx' => 'xvid', 'hevc' => 'x265', 'x265' => 'x265', 'h265' => 'x265');
$config['resolutions']=array('480p', '720p', '1080p');
$config['scoring']=array('x265' => 3, 'x264' => 2, 'xvid' => 0, '720p' => 3, 'hdtv' => 2, '1080p' => 0);
date_default_timezone_set("Europe/London");
?>
