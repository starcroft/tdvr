<?php

if (PHP_SAPI === 'cli') {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
} else {
	if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start('ob_gzhandler'); else ob_start(); 
}

require_once dirname(__DIR__).'/vendor/autoload.php';
require "config.inc";
#require "deluge.torrent.inc";
require "transmission.torrent.inc";

	$token  = new \Tmdb\ApiToken('7c60e3c1af150245fedbbc4cba8ea1c9');
	$client = new \Tmdb\Client($token, [
    'cache' => [
        'path' => '/var/sites/s/starcroft.uk/tmp/php-tmdb'
    ]
]);

	$tmdb_configuration = $client->getConfigurationApi()->getConfiguration();
	
if ($_GET['update']==1) {
	update_movie_feeds($urls);
}

switch ($_GET['action']) {
	
	case "":
	list_movie_releases();
	break;

	case "listreleases":
	list_movie_releases();
	break;

	case "showreleaseinfo":
	show_release_info($_GET['releaseid']);
	break;

	case "downloadrelease":
	download_release($_GET['releaseid']);
	break;

	case "getposter":
	get_movie_poster ($_GET['moviedbid']);
	break;
	
	case "getmoviedb":
	get_movie_db ($_GET['moviedbid']);
	break;
	
	case "updateposters":
	update_movie_posters();
	break;
	
	case "updatelanguage":
	update_movie_language();
	break;
	
	case "updateimdb":
	update_movie_imdb();
	break;
	
	case "getmovieid":
	echo get_movie_id($_GET['moviename'], $_GET['movieyear']);
	break;

	case "dedupe":
	dedupe_movies();
	break;
	
	case "update":
	update_movie_feeds($movie_urls);
}
/* ===================================================================================== */
function tidy_up() {
	/*
	$sql='delete releases from releases
where releases.episodeid in (
select episodes.episodeid from shows
left join episodes on episodes.showid = shows.showid
where shows.updated < date_sub(now(), INTERVAL 90 day));';

	$sql='delete episodes from episodes
where episodes.showid in (
select shows.showid from shows
where shows.updated < date_sub(now(), INTERVAL 90 day));';
    
    $sql='delete releases from releases 
    where releases.relesaseid in (select releases.releaseid from releases
left join episodes on episodes.episodeid = releases.episodeid
left join shows on shows.showid = episodes.showid
where shows.showid is null
or episodes.episodeid is null);';

$sql='select * from episodes
left join shows on shows.showid = episodes.showid
where shows.showid is null
or episodes.episodeid is null';
*/
}
/* ===================================================================================== */
function show_release_info($releaseid) {
global $dvrdb;
$q="SELECT url FROM `releases` WHERE releaseid='$releaseid';";
   if ($result=mysql_query($q, $dvrdb)) {
   	if ($res=mysql_fetch_assoc($result)) {
   		get_torrent_info($res['url']);
		} 
	} else {
	
	print mysql_error();
}
}
/* ===================================================================================== */
function list_movies() {
global $dvrdb;
	print_html_header();
	print_nav();

$q="SELECT movies.movieid, movies.name, movies.description, movies.category, 
		FROM `movies` 
		WHERE `ignore`='0'		
		ORDER BY movies.updated desc";
		
$results=mysql_query($q, $dvrdb);	
		echo "<table class='showlist'>";
		if (mysql_num_rows($results)>0) {
			while ($res=mysql_fetch_assoc($results)) {		
						$showid=$res['showid'];
					 $alt=abs($alt-1);		
					 echo "<tr class='showlist_alt$alt' onmouseover=\"this.className='showlist_althigh'\" onmouseout=\"this.className='showlist_alt$alt'\">";
				 
					 echo "<td class='showlist_name'>".$res['name']."</td>
					 <td class='showlist_description' onmouseover=\"this.className='showlist_description_expand'\" onmouseout=\"this.className='showlist_description'\">".$res['description']."</td></tr>";
				}				
		}
	echo "</table>";
	print_html_footer();
}
/* ===================================================================================== */ 
function update_movie_desc($movie_name="") {
global $dvrdb, $tvdb;
	if ($movie_name) {
		$results=mysql_query("SELECT * from `movies` where (`description`='' or `moviedb_id`=0) `name`='$movie_name';", $dvrdb);	
	} else {
		$results=mysql_query("SELECT * from `movies` where (`tvdb_id` =0 or `description`='');", $dvrdb);
	}

		if (mysql_num_rows($results)>0) {
			while ($res=mysql_fetch_assoc($results)) {							
				$shows = $tvdb->getSeries($res['name']);			
				if ($show=$shows[0]) {				
					$category="";
					foreach ($show->genres as $genre) { $category.=$genre.","; }
					$category=preg_replace("#,$#","",$category);									
					$overview=addslashes($show->overview);
					$category=addslashes($category);
					$id=$res['showid'];
					$tvdb_id=$show->id;
					print_r($show);
					mysql_query("UPDATE `movies` SET `moviedb_id`=$tvdb_id, `description`='$overview', `category`='$category' where movieid='$id';", $dvrdb); 
				}	
			}			
		}
}
/* ===================================================================================== */
function search_show($show_name) {
	$info=get_show_info($show_name);
	var_dump($info);
}

/* ===================================================================================== */
function update_movie_feeds($movie_urls) {
	global $config, $dvrdb, $video_list, $audio_list, $quality_list, $resolution_list;
	$priority=0;
	foreach ($movie_urls as $url) {
		$opts = array(
	  	'http'=>array(
	    'method' => "GET",
	    'header' => "Accept-Encoding: gzip;q=0, compress;q=0\r\n", //Sets the Accept Encoding Feature.
	    'timeout' => 60,
		  )
		);

		$context = stream_context_create($opts);
		$new=0;
		print "$url :";
		$priority++;
		if ($page=file_get_contents($url, false, $context)) {
			if (in_array("Content-Encoding: gzip", $http_response_header)) {
				if ($gpage=&gzinflate(substr($page, 10, -8))) {
					$page=$gpage;	
				}
			}
			#print $page;
			$feed= new SimpleXMLElement($page);
	
			foreach ($feed->channel[0]->item as $item) {
				#print $title."<br>";
				$title=$item->title;
				$category=$item->category;
				$datestring=$item->pubDate;
		
				if (! $url=$item->enclosure['url'][0]) {
						$url=$item->link;
				}	

				$stamp=strtotime($datestring);
				#$title=preg_replace("#[\[\]\>]#","",$title);
				$title=preg_replace("#\.#", " ",$title);
				#$url=preg_replace("#\]\]>$#","",$url);
				$blocked=0;
				foreach ($config['blocked'] as $block) {
					if (stristr($title, $block)) { $blocked=1; }				
				}
				if ($blocked ==0 && (preg_match("#^(.+)\s+[\(\]]*(\d{4})[\)\}]*\s+(.+)$#si", $title, $bits) )) {
					$new++;
					$movie=$bits[1];
					$year=$bits[2];
					$other=$bits[3];

					$quality="";
					$video="";
					$audio="";
					$quality=match_array($quality_list, $other);
					$video=match_array($video_list, $other);
					$audio=match_array($audio_list, $other);
					$resolution=match_array($resolution_list, $other);
					if ($movie && $year && $quality && $video && $resolution) {
						$movie=str_replace("-","",$movie);
						print $movie." : ".$year." : ".$resolution." - ".$quality." - ".$video." - ".$audio."<br>";			
						$mysql_date=date("Y-m-d H:i:s", $stamp);
						if ($year>=2015) {
							insert_movie_release($movie, $year, $quality, $video, $audio, $resolution, $url, $mysql_date, $priority, $title);
						}
					} else {
						print "**** Not added : .".$movie." : ".$year." : ".$resolution." - ".$quality." - ".$video." - ".$audio."<br>";
					}			
				} else {
					if ($blocked) {
						print " Blocked: ".$title."<br>";	
					} else {	
						print " Skipped: ".$title."<br>";
					}							
				}
				$i++;
				#if ($i>20) { break; }
					
			}
			print "$new New<br>";
		} else {		
			print "Unable to open feed<br>";		
		}
	}
}
/* ===================================================================================== */
function match_array($needles, $haystack) {
		$haystack=preg_replace("/[\s\.\-]/", "", $haystack);
		
		foreach($needles as $needle) {
			if (stristr($haystack, $needle)) {
					return($needle);
			}
		}
} 
/* ===================================================================================== */	
function insert_movie_release($movie, $year, $quality, $video, $audio, $resolution, $url, $mysql_date, $priority, $title){
global $dvrdb;
$title=addslashes($title);
	if ($movieid = get_movie_id($movie, $year)) {	
		$result=mysql_query("SELECT * FROM releases WHERE `movieid`=$movieid and `priority` <= $priority and `video` = '$video' and `audio` = '$audio' and `resolution` = '$resolution' and `quality`='$quality' and `original_name` = '$title';", $dvrdb);			
			#print mysql_error($dvrdb);
			if (mysql_num_rows($result)==0) {								
					if (! mysql_query("INSERT INTO releases (movieid, quality, video, audio, resolution, url, timestamp, priority, original_name ) VALUES ('$movieid', '$quality', '$video', '$audio', '$resolution', '$url', '$mysql_date', '$priority', '$title');", $dvrdb)) {
						print mysql_error($dvrdb);			
					}
						
			}						
	} else {
		print "Not adding $title no moviedb entry<br>";
	}
}
/* ===================================================================================== */
function get_movie_id($movie_name, $year){
global $dvrdb;
	$sql_name=addslashes($movie_name);
	#rint $movie_name." - ".$year."<br>";
	$results=mysql_query("SELECT movieid from movies where name like '$sql_name' and `year`='$year';",$dvrdb);
		if (mysql_num_rows($results)>0) {
			$id=mysql_fetch_assoc($results);
			#echo "Found existing id.<br>";
			return($id['movieid']);
		} else {
			echo "Getting movie info for $movie_name<br>";
			if ($movie_info=get_movie_info($movie_name, $year)) {
				$moviedb_id=$movie_info['moviedb_id'];
				$results=mysql_query("SELECT movies.movieid FROM movies WHERE moviedb_id=$moviedb_id");
				if (mysql_num_rows($results) >0) {
					$id=mysql_fetch_assoc($results);
					return($id['movieid']);
				} else {
					$movie_description=addslashes($movie_info['description']);
					$movie_category=addslashes($movie_info['category']);
					$movie_poster=addslashes($movie_info['poster']);			
					$language=addslashes($movie_info['original_language']);
					$imdb_title=addslashes($movie_info['imdb_id']);
					
					if (mysql_query("INSERT INTO movies (name,description,category,year, moviedb_id, posterurl, language, imdbtitle) VALUES ('$sql_name','$movie_description', '$movie_category', '$year', '$moviedb_id', '$movie_poster', '$language', '$imdb_title');", $dvrdb)) {
						return(mysql_insert_id());	
					} else {
						print mysql_error();			
					}
				}		
		}	}
}
/* ===================================================================================== */ 
function get_movie_info($movie_name, $year) {
global $tvdb, $client;
	#$token  = new \Tmdb\ApiToken('7c60e3c1af150245fedbbc4cba8ea1c9');
	#$client = new \Tmdb\Client($token);
	$result = $client->getSearchApi()->searchMovies($movie_name);

	$movie_name_search=neutralise_name($movie_name);
	print "Looking for $movie_name_search<br>";

	foreach ($result['results'] as $movie) {
		$movie_name_found=neutralise_name($movie['original_title']);
		print "Found $movie_name_found<br>";
		
		if (levenshtein(strtolower($movie_name_search), strtolower($movie_name_found)) < 3) {
				$movie_year=substr($movie['release_date'],0,4);				
				print "Looking for $year, is ".$movie_year."<br>";
				if ($movie_year == $year) {
					print "Hit!<br>";
						$moviedb_id=$movie['id'];
						$movie_info=$client->getMoviesApi()->getMovie($moviedb_id);
						#var_dump($movie_info);
						foreach ($movie_info['genres'] as $genre) {
							$category.=$genre['name'].","; 
						}
						$category=preg_replace("#,$#","",$category);
						$info['category']=$category;
						$info['description']=$movie_info['overview'];
						$info['moviedb_id']=$moviedb_id;
						$info['poster']=get_movie_poster($moviedb_id);
						$info['original_language']=$movie_info["original_language"];
						$info['original_title']=$movie_info['original_title'];
						$info['imdb_id']=$movie_info['imdb_id'];
						return ($info);
				}
		}
	}
}
/* ===================================================================================== */
function neutralise_name($name) {
		$name=preg_replace("/[^a-z0-9\s]/i","",$name);
		$name=preg_replace("/\s{2,}/"," ", $name);
		return(rtrim(strtolower($name)));
}
/* ===================================================================================== */
function download_release($releaseid, $save_dir="") {
global $dvrdb, $config;
	if ($results=mysql_query("SELECT url, episodeid FROM releases WHERE releaseid='$releaseid';", $dvrdb)) {
		$res=mysql_fetch_assoc($results);
		$episodeid=$res['episodeid'];
		if (! $save_dir) { $save_dir=$config['save_dir']; }	
			$err=add_torrent($res['url'], $save_dir);
			if (! $err['error']) {
					mysql_query("UPDATE `releases` SET downloaded='1' WHERE releaseid='$releaseid';") or print mysql_error();
					#mysql_query("UPDATE `episodes` SET downloaded='1' WHERE episodeid='$episodeid';") or print mysql_error();		
			} else {
				log_it("3", $err['error']);
			}
			#echo "OK";
			return($err);
	} else {
		print mysql_error();
	}
}
/* ===================================================================================== */
function get_movie_poster ($moviedb_id) {
global $client, $tmdb_configuration;
	$result = $client->getMoviesApi()->getImages($moviedb_id);
	foreach ($result['posters'] as $array) {
		if ($array["iso_639_1"]=="en") {
			$url=$tmdb_configuration['images']['base_url']."w154".$array['file_path'];
			if (! $url) { $url="-"; }
			return($url);
		}	
	}
	return ("null.png");
}
/* ===================================================================================== */
function dedupe_movies () {
global $client, $tmdb_configuration;

	$results=mysql_query("SELECT movies.name, moviedb_id, count(movies.movieid) as count FROM `movies` group by moviedb_id having count>1 order by count desc");
	while($res=mysql_fetch_assoc($results)) {
			$moviedb_id=$res['moviedb_id'];
			print $moviedb_id."<br>";
			$movie_info=$client->getMoviesApi()->getMovie($moviedb_id);
			$movie_name=$movie_info['original_title'];
			$mresults=mysql_query("SELECT movieid, name FROM movies where moviedb_id=$moviedb_id");
			$winner="";
			$in="";
			while ($mres=mysql_fetch_assoc($mresults)) {
				$dbname=$mres['name'];
				print $mres['movieid']." ".$dbname." = ".$movie_name."? ";
				$leven=levenshtein(strtolower($dbname), strtolower($movie_name));
				print $leven;
				if (($dbname == $movie_name || $leven <= 2) && $winner=="") {
					print " Winner<br>";
					$winner=$mres['movieid'];
				} else {
					print " Nope<br>";
					$in.=$mres['movieid'].",";
				}
			}
			if ($winner) {
				$in=preg_replace("/\,$/", "", $in);
				if (mysql_query("update releases set movieid=$winner where movieid in ($in);")) {
					echo "updated releases $in<br>";
					 if (mysql_query("delete movies from movies where movieid in ($in);")) {
							echo "deleted movies $in<br>";
					} else {
						echo mysql_error();
					}				 
				} else {
					echo mysql_error();
				}	
				#print $sql;
			}
			print "<br>";
	}
	
}
/* ===================================================================================== */
function get_movie_db ($moviedb_id) {
global $client, $tmdb_configuration;	
	$movie_info=$client->getMoviesApi()->getMovie($moviedb_id);
	var_dump($movie_info);
	
	print "<br>";
	print $movie_info["original_language"];
}
/* ===================================================================================== */
function list_movie_releases() {
global $dvrdb, $config;
	print_html_header();
	print_nav();
	
	#$q='SELECT * FROM `movies` where year >= 2015 and moviedb_id >0 AND movies.updated > date_sub(now(), INTERVAL 90 day) order by updated desc';
	#$q='SELECT * FROM `movies` where year >= 2015 AND movies.updated > date_sub(now(), INTERVAL 30 day) order by updated desc';	
	$q='SELECT movies.*, count(releases.movieid) as count
FROM `movies` 
LEFT JOIN releases on releases.movieid = movies.movieid
WHERE year >= 2015
AND releases.timestamp > date_sub(now(), INTERVAL 90 day) 
AND movies.category not like "%horror%"
AND movies.category not like "%documentary%"
AND movies.language =  "en"
GROUP BY movies.movieid
HAVING `count` >1
order by releases.timestamp desc';
	echo '<div id="movielist" class="responsive">';
        
            
	$res=mysql_query($q, $dvrdb) or print mysql_error($dvrdb);
	while ($relitem=mysql_fetch_assoc($res)) {
		if ($relitem['imdbtitle']) {
				$imdburl="<a href='http://imdb.com/title/".$relitem['imdbtitle']."'>IMDB</a>";
		} else {
				$imdburl="";
		}
		echo "<div class='row'>";
		echo "<div class='col-xs-3 col-sm-3 col-md-2'><img class='img-responsive movie_poster' src='".$relitem['posterurl']."' /></div>";
		echo "<div class='col-xs-9 col-sm-4 col-md-3'><p class='movie_title'>".$relitem['name']."</p>";
		echo "<p class='movie_category'>".$relitem['category']."</p><p>".$imdburl."</p></div>";
		echo "<div class='col-xs-12 col-sm-5 col-md-7  movie_description'>".$relitem['description']."</div>";
		echo "</div>";
		
	}
	echo "</div>";
	print_html_footer();
}
/* ===================================================================================== */
function get_releases($movie_id) {
	global $dvrdb, $client, $tmdb_configuration;
	$results=mysql_query("SELECT original_name,url from releases where movie_id=$movie_id");
	while ($res=mysql_fetch_assoc($results)) {
			$urlarray[$res['original_name']]=$res['url'];
	}
	return ($urlarray);	
}
/* ===================================================================================== */
function update_movie_posters() {
global $dvrdb, $client, $tmdb_configuration;
$sql="select movieid,moviedb_id from movies where `moviedb_id` !=0 AND `ignore` =0 AND `posterurl` = 'null.png';";
$rows=mysql_query($sql, $dvrdb);
while ($row=mysql_fetch_assoc($rows)) {
	print $movieid." ".$row['moviedb_id']."<br>";
	$poster=get_movie_poster($row['moviedb_id']);
	$movieid=$row['movieid'];
	
	mysql_query("update `movies` set `posterurl` = '$poster' WHERE `movieid` = '$movieid';", $dvrdb);
	sleep(2);
}

}
/* ===================================================================================== */
function update_movie_language() {
global $dvrdb, $client, $tmdb_configuration;
$sql="select movieid,moviedb_id from movies where `moviedb_id` !=0 AND `ignore` =0 AND `language` is null order by movieid desc limit 100;";
$rows=mysql_query($sql, $dvrdb);
while ($row=mysql_fetch_assoc($rows)) {
	print $row['movieid']." ".$row['moviedb_id']." ";
	$movie_info=$client->getMoviesApi()->getMovie($row['moviedb_id']);
	$lang=$movie_info["original_language"];
	print $lang."<br>";
	mysql_query("update `movies` set `language` = '$lang' WHERE `movieid` = '".$row['movieid']."';", $dvrdb);
	sleep(1);
}

}
/* ===================================================================================== */
function update_movie_imdb() {
global $dvrdb, $client, $tmdb_configuration;
$sql="select movieid,moviedb_id from movies where `moviedb_id` !=0 AND `ignore` =0 AND `imdbtitle` is null and language='en' order by movieid desc limit 20;";
$rows=mysql_query($sql, $dvrdb);
while ($row=mysql_fetch_assoc($rows)) {
	print $row['movieid']." ".$row['moviedb_id']." ";
	$movie_info=$client->getMoviesApi()->getMovie($row['moviedb_id']);
	$imdb=$movie_info["imdb_id"];
	print $imdb."<br>";
	mysql_query("update `movies` set `imdbtitle` = '$imdb' WHERE `movieid` = '".$row['movieid']."';", $dvrdb);
	sleep(1);
}

}
/*---------------------------------------------------------------------------------------------------*/	
function log_it($code,$entry){
	global $dvrdb;
	$entry=addslashes($entry);
	mysql_query("INSERT INTO `log` (`error`, `text`) VALUES ('$code', '$entry');", $dvrdb) or print mysql_error();
}
/*---------------------------------------------------------------------------------------------------*/	
function print_html_header() {
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>movie dvr</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style.css" />
    <script src="./scripts.js" type="text/javascript"></script>
  </head>
  <body>

<?php		
}
/* ===================================================================================== */
function print_nav() {
?>
<nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">Project name</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="#">Profile</a></li>
            <li><a href="#">Help</a></li>
          </ul>
          <form class="navbar-form navbar-right">
            <input type="text" class="form-control" placeholder="Search...">
          </form>
        </div>
      </div>
    </nav>
    <div><h2>&nbsp;</h2></div> 
<?php
}
/*----------------------------------------------------------------------------------------------------*/	
function print_html_footer() {
?>
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
<?php
}	
?>
