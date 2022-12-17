<?php

if (PHP_SAPI === 'cli') {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
} else {
	if (array_key_exists('HTTP_ACCEPT_ENCODING', $_SERVER)) {
		if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start('ob_gzhandler'); else ob_start();
	} else {
		ob_start();
	}
}


require "config.inc";
require "transmission.torrent.inc";

if (isset($_GET['update]']) && $_GET['update']==1) {
	update_feeds($urls);
}

if (isset($_GET['action'])) {
	$action=$_GET['action'];
} else {
	$action="";
}
switch ($action) {

	case "rss":
		build_feed();
		break;
    case "getposter":
		get_tvdb_poster($_GET['id']);
		break;

	case "listfavourites":
		list_favourites();
		break;

	case "listshows":
		list_shows($_GET['search']);
		break;

	case "dofavourites":
		process_favourites();
		break;

	case "listreleases":
		list_releases();
		break;

	case "showreleaseinfo":
		show_release_info($_GET['releaseid']);
		break;

	case "downloadrelease":
		download_release($_GET['releaseid']);
		break;

	case "addfavourite":
		add_favourite($_GET['showid'], $_GET['quality'], $_GET['set']);
		break;
	case "setfavourite":
		set_favourite($_GET['favouriteid'], $_GET['quality'], $_GET['set']);
		break;

	case "ignoreshow":
		ignore_show($_GET['showid']);
		break;

	case "showepisode":
		echo get_episode_description($_GET['show'], $_GET['season'], $_GET['episode']);
		break;

	case "update":
		update_feeds($urls);

	case "dofavourites":
		process_favourites();

	case "updateshowdescription":
        update_show_desc($_GET['showname']);

	case "updateepisodedescription":
        update_episode_desc();

	case "updatereleasetags":
		update_release_tags();
		break;

	case "searchshow":
		search_show($_GET['name']);
		break;

	case "getepisode":
		search_episode($_GET['show'], $_GET['season'], $_GET['episode']);
		break;

	case "tidy":
		tidy_up();
		break;

	default:
		list_releases();
		break;
}

function tidy_up() {
	global $dvrdb;

$sqlarray=array(
"DELETE `releases` FROM `releases`
left join episodes on episodes.episodeid = releases.episodeid
left join shows on shows.showid = episodes.showid
where shows.ignore = 1,
AND releases.timestamp < date_sub(now(), interval 90 day);",
"delete `releases` from `releases` where releases.episodeid in ( select episodes.episodeid from shows left join episodes on episodes.showid = shows.showid where shows.updated < date_sub(now(), INTERVAL 180 day))",
"delete `favourites` from `favourites` where favourites.showid in (select showid from shows where `ignore`=1)",
"delete `releases` from `releases` left join episodes on episodes.episodeid = `releases`.episodeid left join shows on shows.showid = episodes.showid where releases.movied is null and (shows.showid is null or episodes.episodeid is null)",
"delete `episodes` from `episodes` left join shows on shows.showid = episodes.showid where shows.showid is null or episodes.episodeid is null",
"delete `releases` FROM `releases` where timestamp < date_sub(now(), INTERVAL 365 day)",
);
foreach ($sqlarray as $sql) {
	mysqli_query($dvrdb, $sql);
}
}

/* ===================================================================================== */
function show_release_info($releaseid) {
global $dvrdb;
	$releaseid=clean_number($releaseid);
	$q="SELECT url FROM `releases` WHERE releaseid='$releaseid';";
	if ($result=mysqli_query($dvrdb, $q)) {
		if ($res=mysqli_fetch_assoc($result)) {
			get_torrent_info($res['url']);
		}
	} else {
		print mysqli_error();
	}
}
/* ===================================================================================== */
function ignore_show($showid) {
	global $dvrdb;
	$showid=clean_number($showid);
	$q="UPDATE `shows` SET `ignore`=IF(`ignore`=1, 0, 1) WHERE `showid`='$showid';";
	if ($res=mysqli_query($dvrdb, $q)) {
	$q="SELECT `ignore` from `shows` WHERE `showid`='$showid';";
	$res=mysqli_query($dvrdb,$q);
	$result=mysqli_fetch_assoc($res);
	if ($result['ignore']==1) {
			echo "ignore";
		} else {
			echo "show";
		}
	}
}
/* ===================================================================================== */
function add_favourite($showid, $quality="") {
global $dvrdb;
	$showid=clean_number($showid);
	$quality=clean_text($quality);
	$resolution=clean_text($resolution);
	$video=clean_text($video);

   if ($result=mysqli_query($dvrdb, "SELECT favouriteid FROM favourites WHERE showid = '$showid';")) {
   	if ($res=mysqli_fetch_assoc($result)) {
			mysqli_query($dvrdb, "UPDATE `favourites`  SET `quality` = '$quality' WHERE `showid` = '$showid';");
   	} else {
			if (mysqli_query($dvrdb, "INSERT INTO `favourites` (`showid`, `quality`) VALUES ('$showid', '$quality');")) {
				print "OK";
			}
   	}
   }
}
/* ===================================================================================== */
function set_favourite($favourite, $quality, $set) {
global $dvrdb;
	$favourite=clean_number($favourite);
	$quality=clean_text($quality);
	$resolution=clean_text($resolution);
	$video=clean_text($video);
	$set=clean_number($set);
	if($favourite) {
		$result=mysqli_query($dvrdb, "select quality from favourites where favouriteid='$favourite';");
		   	if ($res=mysqli_fetch_assoc($result)) {
				$qlist=$res['quality'];
				$qlist=str_replace($quality, "", $qlist);
				$qlist=rtrim(ltrim($qlist));
				if ($set == 1) {
					$qlist.=" $quality";
				}
				$qlist=preg_replace("/\s+/"," ",$qlist);
				echo $qlist;
				$result=mysqli_query($dvrdb, "update `favourites` set `quality`='$qlist' where `favouriteid`='$favourite';");
			} else {
				echo "cannot read favourite";
			}
	}
}
/* ===================================================================================== */
function list_shows($search) {
global $dvrdb;
	print_html_header();
	$search=clean_text($search);
	if ($search) {
		$q="SELECT shows.showid, shows.name, shows.description, shows.category, shows.ignore, favourites.favouriteid
		FROM `shows`
		LEFT JOIN `favourites` on favourites.showid = shows.showid
		WHERE shows.name like '%$search%'
		ORDER BY shows.updated desc";
	}else{
		$q="SELECT shows.showid, shows.name, shows.description, shows.category, shows.ignore, favourites.favouriteid
		FROM `shows`
		LEFT JOIN `favourites` on favourites.showid = shows.showid
		WHERE `ignore`='0'
		ORDER BY shows.updated desc";
	}
	$results=mysqli_query($dvrdb, $q);
	echo "<table class='table table-condensed table-striped table-responsive'>";
	echo "<tbody>";
	if (mysqli_num_rows($results)>0) {
		while ($res=mysqli_fetch_assoc($results)) {
			$showid=$res['showid'];
			echo "<tr>";
			if ($res['favouriteid']) {
			 	echo "<td class='showlist_icon'><img id='favourite_icon_$showid' src='favourite.png' onclick=\"addFavourite($showid, '');\"></td>";
			} else {
			 	echo "<td class='showlist_icon'><img id='favourite_icon_$showid' src='favourite_grey.png' onclick=\"addFavourite($showid, '');\"></td>";
			}
			if ($res['ignore']) {
				echo "<td class='showlist_icon'><img id='ignore_icon_$showid' src='ignore.png' onclick=\"ignoreShow($showid);\"></td>";
			}	else {
				echo "<td class='showlist_icon'><img id='ignore_icon_$showid' src='ignore_grey.png' onclick=\"ignoreShow($showid);\"></td>";
			}
			echo "<td class='showlist_name_long'>".$res['name']."</td>
			  <td class='showlist_description'\">".$res['description']."</td>";
			echo "</tr>";
		}
	}
	echo "</tbody></table>";
	print_html_footer();
}
/* ===================================================================================== */
function update_episode_desc() {
global $dvrdb;
		$q="SELECT shows.name,shows.tvdb_id, shows.showid, episodes.episodeid, episodes.season, episodes.episode_number, episodes.episode_name
			FROM `shows`
			LEFT JOIN episodes ON episodes.showid = shows.showid
			WHERE shows.`tvmaze_id` is not null
			AND episodes.`episodeid` is not null
			AND (episodes.`episode_name` ='unknown' OR episodes.episode_name ='')
			AND episodes.`timestamp` > date_sub(now(), INTERVAL 60 day)
			AND episodes.`episode_number` > 0
			AND shows.`ignore`=0
			LIMIT 50
			;";

		$q="SELECT shows.name,shows.tvdb_id, shows.showid, episodes.episodeid, episodes.season, episodes.episode_number, episodes.episode_name
			FROM `shows`
			LEFT JOIN episodes ON episodes.showid = shows.showid
			WHERE shows.`tvmaze_id` is not null
			AND episodes.`episodeid` is not null
			AND episodes.episode_name =''
			AND episodes.`timestamp` > date_sub(now(), INTERVAL 120 day)
			AND episodes.`episode_number` > 0
			AND shows.`ignore`=0
			order by episodes.timestamp DESC
			LIMIT 100
			;";

		$results=mysqli_query($dvrdb, $q);

		if (mysqli_num_rows($results)>0) {
			while ($res=mysqli_fetch_assoc($results)) {
					$id=$res['episodeid'];
					$overview=addslashes(get_episode_description($res['showid'], $res['season'], $res['episode_number']));
					#print $overview."<BR>";
					mysqli_query($dvrdb, "UPDATE `episodes` SET `episode_name`='$overview' where episodeid='$id';");
				}
		}
}
/* ===================================================================================== */
function update_show_desc($show_name="") {
global $dvrdb, $tvdb;
	$show_name=clean_text($show_name);
	if ($show_name) {
		$results=mysqli_query($dvrdb, "SELECT * from `shows` where (`description`='' or `poster` = '' or `poster` is null or `tvmaze_id` is null) and shows.ignore= '0' and `name` like '$show_name';");
	} else {
		$results=mysqli_query($dvrdb, "SELECT * from `shows` where (`description`='' or `tvmaze_id` is null or `poster` is null or `poster` = '') and shows.ignore = '0' and shows.updated > date_sub(now(), INTERVAL 28 day) order by updated DESC limit 100;");
	}
	if (mysqli_num_rows($results)>0) {
			while ($res=mysqli_fetch_assoc($results)) {
				$name=$res['name'];
				$id=$res['showid'];
				echo $name." ".$id."<br>";
				$show=get_show_info($name);
				if ($show['description']) {
					print $name."<br>";
					print_r($show);
					print "<hr>";
					#if ($show['tvdb_id'] == '') {
					#	$show['tvdb_id'] = "NULL";
					#}
					# `tvdb_id`='".$show['tvdb_id']."',
					mysqli_query($dvrdb, "UPDATE `shows` SET  `tvmaze_id`='".$show['tvmaze_id']."', `description`='".addslashes($show['description'])."', `category`='".addslashes($show['category'])."', `poster`='".addslashes($show['poster'])."' where showid='$id';");
				}	else {
					print $name." not found<br>";
				}

			}
		}
}
/* ===================================================================================== */
function search_show($show_name) {
global $tvmaze;
	$info=get_show_info($show_name);
	var_dump($info);
}
/* ===================================================================================== */
function get_show_info($show_name) {
global $tvdb, $tvmaze;
	$show_name=clean_text($show_name);
	#$shows = $tvdb->getSeries($show_name);
	$category="";
	#print_r($shows);
	if (preg_match("/[0-9]{4}$/", $show_name, $match)) {
		$year=$match[0];
		$name_no_year=str_replace($year, "", $show_name);
	}

	$tvmaze = new JPinkney\TVMaze\Client;

	$shows=$tvmaze->TVMaze->singleSearch($show_name);
	#print_r($shows);
	if ($shows[0]->id) {
		$show=$shows[0];
	} elseif ($name_no_year) {
		echo "Running no year search ".$name_no_year."<br>";
		$shows=$tvmaze->TVMaze->singleSearch($name_no_year);
		if ($shows[0]->id && stristr($shows[0]->premiered, $year) ) {
			$show=$shows[0];
		}
	}

	if ($show->id) {
			foreach ($show->genres as $genre) { $category.=$genre.","; }
			$category=preg_replace("#,$#","",$category);
			$info['name']=$show->name;
			$info['tvdb_id']=$show->externalIDs['thetvdb'];
			$info['category']=$category;
			$info['description']=$show->summary;
			$info['poster']=$show->mediumImage;
			$info['tvmaze_id']=$show->id;
	}

	if ($info['name'] && $info['description']) {
		if (strip_text($info['name']) == strip_text($show_name) || strip_text($info['name']) == strip_text($name_no_year) || strip_text(swapands($info['name'])) == strip_text($show_name)) {
			return($info);
		} else {
			echo $info['name']." != ".$show_name."<br><br>";
		}
	} else {
		echo "$show_name not found or no description<br>";
	}
}
function get_tvdb_poster($tvdb_id) {
	global $apiKey;
	$url="http://thetvdb.com/api/".$apiKey."/series/".$tvdb_id."/banners.xml";
    $xml=download_xml($url);
    $list=new SimpleXMLElement($xml);
	#var_dump($list->Banner[2]);

 foreach ($list->Banner as $bannerlist) {
	 if ($bannerlist->BannerType== "poster") {
		return ("https://thetvdb.com/banners/".$bannerlist->BannerPath);
	}
 }
}
/* ===================================================================================== */
function search_episode ($showname, $season_number, $episode_number) {
global $dvrdb;
		$showname=clean_text($showname);
		$season_number=clean_number($season_number);
		$episode_number=clean_number($episode_number);
		$result=mysqli_query($dvrdb, "select shows.showid, episodes.episode_name from shows left join episodes on episodes.showid = shows.showid where shows.name like '$showname' and episodes.episode_number=$episode_number and episodes.season=$season_number;");
		if ($res=mysqli_fetch_assoc($result) && $res['episode_name']){
			#print "gotfrom db";
			print $res['episode_name'];
		} else {
			#print "getfrom tvdb";
			print get_episode_description($showname, $season_number, $episode_number);
		}
}
/* ===================================================================================== */
function get_episode_description($showid="", $season_number="", $episode_number="") {
global $dvrdb,$tvdb;
		$showid=clean_text($showid);
		$season_number=clean_number($season_number);
		$episode_number=clean_number($episode_number);
		if (is_numeric($showid)) {
				$tvdb_lookup=mysqli_query($dvrdb, "SELECT tvmaze_id from shows where showid='$showid';");
		} else {
				$tvdb_lookup=mysqli_query($dvrdb, "SELECT tvmaze_id from shows where name like '$showid';");
		}
		if ($res=mysqli_fetch_assoc($tvdb_lookup)){
				$tvdb_id=$res['tvmaze_id'];
				$season_number=intval($season_number);
				$episode_number=intval($episode_number);
				print "id : $tvmaze_id $season_number $episode_number : ";

				if ($tvdb_id && $episode_info=$tvmaze->TVMaze->getEpisodeByNumber) {
					if ($episode_info->name) {
						#var_dump($episode_info->name);
						print $episode_info->name."<br>";
						return($episode_info->name);
					} else{
						print "unknown <br>";
						return("unknown");
					}

				}
		} else {
			print "Error finding $showid <br>";
		}
}

function download_xml($url) {
	$opts = array(
	  	'http'=>array(
	    'method' => "GET",
	    'header' => "Accept-Encoding: gzip;q=1, compress;q=1\r\n", //Sets the Accept Encoding Feature.
	    'timeout' => 15,
	    'user_agent' => "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1",
	    'protocol_version'=> 1.0,
		  )
		);

	$context = stream_context_create($opts);
	if ($page=file_get_contents($url, false, $context)) {
		#print_r($http_response_header);
		if (in_array("Content-Encoding: gzip", $http_response_header) || in_array("content-encoding: gzip", $http_response_header)) {
			if ($gpage=&gzinflate(substr($page, 10, -8))) {
				$page=$gpage;
			}
		}
	}
	return($page);
}
/*----------------------------------------------------------------------------------------------------*/
function update_feeds() {
	global $config, $dvrdb;
	$priority=0;

	if (! $results=mysqli_query($dvrdb, "select url,priority from feeds order by priority desc")) {
		echo "<h1>ERROR No Feeds Defined</h1><br>";
		exit;
	}
	while ($relitem=mysqli_fetch_assoc($results)) {
		$url=$relitem['url'];
		$priority=$relitem['priority'];
		#print "$url :";
		if ($page=download_xml($url)) {
			//print $url."<br/>";
			//
			$page=preg_replace("/ & /", "", $page);

			try {

				$feed= new SimpleXMLElement($page);
				foreach ($feed->channel[0]->item as $item) {
					$title=$item->title;
					$category=$item->category;
					$datestring=$item->pubDate;
					if (! $url=$item->enclosure['url'][0]) {
							$url=$item->link;
					}
					$stamp=strtotime($datestring);
					$title=preg_replace("#[\[\]\>]#","",$title);
					$title=preg_replace("#\.#", " ",$title);
					$url=preg_replace("#\]\]>$#","",$url);
					$blocked=0;
					foreach ($config['blocked'] as $block) {
						if (stristr($title, $block)) { $blocked=1; }
					}
					if ($blocked ==0 && (preg_match("#(.+)\ss([0-9]+)e([0-9]+)\s(.*)#si", $title, $bits) || preg_match("#(.+)\s([0-9]+)x([0-9]+)\s(.*)#si", $title, $bits) || preg_match("#(.+)\ss([0-9]+)e([0-9]+)e([0-9]+)\s(.*)#si", $title, $bits) )) {
						$new++;
						$show=$bits[1];
						$season=$bits[2];
						$episode=$bits[3];
						if ($bits[5]) {
							$other=$bits[5];
							$episode=$bits[3].",".$bits[4];
						} else {
							$other=$bits[4];
						}

						$quality="unknown";
						$vcodec="unknown";
						$resolution="480p";
						foreach ($config['video_map'] as $name => $codec) {
							if (stristr($other, $name)) {
								$vcodec=$codec;
								break;
							}
						}
						foreach ($config['resolutions'] as $res) {
							if (stristr($other, $res)) {
								$resolution = $res;
							}
						}
						//if (stristr($other, "720p")) { $quality="720p"; }
						//if (stristr($other, "1080p")) { $quality="1080p"; }
						if ($quality == "unknown" && stristr($other, "hdtv")) { $quality="hdtv"; }
						if ($quality == "unknown" && stristr($other, "dvdrip")) { $quality="dvd"; }
						if ($quality == "unknown" && stristr($other, "pdtv")) { $quality="pdtv"; }
						if ($quality == "unknown" && stristr($other, "dsr")) { $quality="dsr"; }
						if ($quality == "unknown" && stristr($other, "web-dl")) { $quality="web"; }
					    if ($quality == "unknown" && stristr($other, "web")) { $quality="web"; }
					    if ($quality == "unknown" && stristr($other, "AFG")) { $quality="hdtv"; }
						$url=htmlspecialchars($url);
						$mysql_date=date("Y-m-d H:i:s", $stamp);
						#echo "$title : $season : $episode : $quality : $vcodec : $resolution<br>";
						insert_release($show, $season, $episode, $quality, $url, $mysql_date, $priority, $title, $vcodec, $resolution);
					} else {
						#print " ** ".$title."<br>";
					}
				}

			} catch (Exception $e) {
				echo "Error parsing XML $e <br/><hr/>";
				print $page."<hr/>";
			}

			print "$new New<br>";
		} else {
			print "Unable to open feed<br>";
		}
	}
}
/*----------------------------------------------------------------------------------------------------*/
function update_release_tags() {
	global $dvrdb, $config;
  $result=mysqli_query($dvrdb, "SELECT * FROM releases WHERE (`quality` = '' or `video` = '' or `resolution` = '' or `score` is null) and `timestamp` > date_sub(NOW(), interval 90 day);");
	while ($release=mysqli_fetch_assoc($result)) {
		$quality="unknown";
		$vcodec="unknown";
		$resolution='480p';

		if ($quality == "unknown" && stristr($release['original_name'], "hdtv")) { $quality="hdtv"; }
		if ($quality == "unknown" && stristr($release['original_name'], "dvdrip")) { $quality="dvd"; }
		if ($quality == "unknown" && stristr($release['original_name'], "pdtv")) { $quality="pdtv"; }
		if ($quality == "unknown" && stristr($release['original_name'], "dsr")) { $quality="dsr"; }
		if ($quality == "unknown" && stristr($release['original_name'], "web-dl")) { $quality="web"; }
		if ($quality == "unknown" && stristr($release['original_name'], "web")) { $quality="web"; }
		if ($quality == "unknown" && stristr($release['original_name'], "AFG")) { $quality="hdtv"; }

		foreach ($config['video_map'] as $name => $codec) {
			if (stristr($release['original_name'], $name)) {
				$vcodec=$codec;
				break;
			}
		}
		foreach ($config['resolutions'] as $res) {
			if (stristr($release['original_name'], $res)) {
				$resolution = $res;
				break;
			}
		}
		$totalscore = 0;
		foreach ($config['scoring'] as $tag => $score) {
			if ($resolution == $tag || $vcodec == $tag) {
					$totalscore += $score;
			}
		}
		print $release['original_name'] . " : " . $vcodec . " - " . $resolution . " - " . $totalscore . "<br>";

		if ($vcodec == 'unknown') {
			print $release['original_name']." ".$vcodec."<br>";
		}
		if (! mysqli_query($dvrdb, "UPDATE releases SET `quality` = '$quality', `video` = '$vcodec', `resolution` = '$resolution', `score` = '$totalscore' WHERE `releaseid` = '".$release['releaseid']."';")) {
			print mysqli_error($dvrdb);
		}
	}
}

function insert_release($show, $season_number, $episode_number, $quality, $url, $stamp, $priority, $title, $vcodec, $resolution) {
global $dvrdb;
$title=addslashes($title);
	$season_number=clean_number($season_number);
	$episode_number=clean_number($episode_number);
	$quality=clean_text($quality);

	if ($showid = get_show_id($show)) {
		$episode_list=explode(",",$episode_number);
		foreach ($episode_list as $episode_number) {
					if ($episodeid= get_episode_id($showid, $season_number, $episode_number, $stamp)) {
						$result=mysqli_query($dvrdb, "SELECT * FROM releases WHERE `episodeid`=$episodeid and `url` = '$url' and `original_name` = '$title';");
						if (mysqli_num_rows($result)==0) {
							echo "$title : $season : $episode : $quality : $vcodec : $resolution<br>";
							if (! mysqli_query($dvrdb, "INSERT INTO releases (episodeid, quality, url, timestamp, priority, original_name, video, resolution, audio, downloaded, proper ) VALUES ('$episodeid', '$quality', '$url', '$stamp', '$priority', '$title', '$vcodec', '$resolution', '', '0', '0');")) {
									print mysqli_error($dvrdb);
							}
						} else {
							#echo "Already present 2<br>";
						}
					} else {
						#echo "Cannot get episode id<br>";
					}
				}
	} else {
		echo "Cannot get show id";
	}

}
/*----------------------------------------------------------------------------------------------------*/
function get_episode_id($showid, $season_number, $episode_number, $stamp) {
global $dvrdb;
	$showid=clean_number($showid);
	$season_number=clean_number($season_number);
	$episode_number=clean_number($episode_number);
	$stamp=clean_number($stamp);
	$results=mysqli_query($dvrdb, "SELECT episodeid from episodes where showid='$showid' and season='$season_number' and episode_number='$episode_number';");
	if (mysqli_num_rows($results)>0) {
			$id=mysqli_fetch_assoc($results);
			$rid=$id['episodeid'];
	} else {
			$episode_description=addslashes(get_episode_description($showid, $season_number, $episode_number));
			if (mysqli_query($dvrdb, "INSERT INTO episodes (showid, season, episode_number, downloaded, episode_name, timestamp) VALUES ('$showid', '$season_number', '$episode_number','0', '$episode_description', '$stamp');")) {
				$id=mysqli_insert_id($dvrdb);
				mysqli_query($dvrdb, "UPDATE `shows` SET `updated` = '$stamp' WHERE `showid` = '$showid';");
				$rid=$id;
			} else {
				print mysqli_error($dvrdb);
			}
	}
	return ($rid);
}
/*----------------------------------------------------------------------------------------------------*/
function get_show_id($show_name){
global $dvrdb;
	$show_name=clean_text($show_name);
	$sql_name=addslashes($show_name);
	$results=mysqli_query($dvrdb, "SELECT showid from shows where name like '$sql_name';");
		if (mysqli_num_rows($results)>0) {
			$id=mysqli_fetch_assoc($results);
			return($id['showid']);
		} else {
			if ($show_info=get_show_info($show_name)) {
				$tvdb_id=$show_info['tvdb_id'];
				$tvmaze_id=$show_info['tvmaze_id'];
				$show_description=addslashes($show_info['description']);
				$show_category=addslashes($show_info['category']);
				if (! is_numeric($tvmaze_id)) {
					$tvmaze_id=0;
				}
			}
			if ($tvmaze_id != "") {
				if (mysqli_query($dvrdb, "INSERT INTO shows (`name`, `description`, `category`, `tvmaze_id`, `ignore`) VALUES ('$sql_name','$show_description', '$show_category', '$tvmaze_id', '0');")) {
					return(mysqli_insert_id());
				} else {
					print mysqli_error();
				}
			}
		}
}
/*----------------------------------------------------------------------------------------------------*/
function download_release($releaseid, $save_dir="") {
global $dvrdb, $config;
	$releaseid=clean_number($releaseid);

	if ($results=mysqli_query($dvrdb, "SELECT url, episodeid FROM releases WHERE releaseid='$releaseid';")) {
		$res=mysqli_fetch_assoc($results);
		$episodeid=$res['episodeid'];
		if (! $save_dir) { $save_dir=$config['save_dir']; }
			$err=add_torrent($res['url'], $save_dir);
			if (! $err['error']) {
					mysqli_query($dvrdb, "UPDATE `releases` SET downloaded='1' WHERE releaseid='$releaseid';") or print mysqli_error();
					mysqli_query($dvrdb, "UPDATE `episodes` SET downloaded='1' WHERE episodeid='$episodeid';") or print mysqli_error();
			} else {
				log_it("3", $err['error']);
			}
			#echo "OK";
			return($err);
	} else {
		print mysqli_error();
	}
}
/*----------------------------------------------------------------------------------------------------*/
function list_releases() {
global $dvrdb, $config;
	print_html_header();
	$ignore=($_GET['showignore'] ? 1 : 0);
	echo "<table class='table table-condensed table-striped'>";
	$showid=$_GET['showid'] ? clean_number($_GET['showid']) : "";
	$search=$_GET['search'] ? clean_text($_GET['search']) : "";
	$filter=$_GET['filter'] ? clean_text($_GET['filter']) : "";

	if ($showid) {
		$q="SELECT shows.name, shows.tvdb_id, shows.showid, shows.description, shows.category, shows.poster, shows.ignore, favourites.quality as fquality, favourites.favouriteid, favourites.season as fseason, favourites.episode as fepisode, episodes.season, episodes.episode_number, episodes.timestamp, episodes.episodeid, episodes.episode_name, episodes.downloaded as edownloaded FROM `episodes`
			LEFT JOIN shows ON shows.showid = episodes.showid
			LEFT JOIN `favourites` on favourites.showid = episodes.showid
			WHERE episodes.showid = '".$showid."'
			AND shows.showid='".$showid."'
			ORDER BY episodes.timestamp desc
			LIMIT 100;";
	} elseif ($search) {
		$q="SELECT shows.name, shows.showid, shows.description, shows.category, shows.ignore, favourites.quality as fquality, favourites.favouriteid, favourites.season as fseason, favourites.episode as fepisode, episodes.season, episodes.episode_number, episodes.timestamp, episodes.episodeid, episodes.episode_name, episodes.downloaded as edownloaded FROM `episodes`
			LEFT JOIN shows ON shows.showid = episodes.showid
			LEFT JOIN `favourites` on favourites.showid = episodes.showid
			WHERE shows.name like '%".$search."%'
			ORDER BY episodes.timestamp desc
			LIMIT 100;";
	} else {
		$q="SELECT shows.name, shows.showid, shows.description, shows.category, shows.ignore, favourites.quality as fquality, favourites.favouriteid, favourites.season as fseason, favourites.episode as fepisode, episodes.season, episodes.episode_number, episodes.timestamp, episodes.episodeid, episodes.episode_name, episodes.downloaded as edownloaded FROM `episodes`
			LEFT JOIN shows ON shows.showid = episodes.showid
			LEFT JOIN `favourites` on favourites.showid = episodes.showid
			WHERE shows.ignore = '$ignore'
			AND episodes.timestamp > date_sub(now(), INTERVAL 14 day)
			ORDER BY episodes.timestamp desc";
	}
	echo "<tbody>";
	$first=0;
	$res=mysqli_query($dvrdb, $q) or print mysqli_error($dvrdb);
	while ($relitem=mysqli_fetch_assoc($res)) {
		if ($_GET['showid'] && $first==0) {
			$first=1;
			if ($relitem['poster']) {
				$poster=stripslashes($relitem['poster']);
			} else {
				$poster="null.png";
			}
			$search=urlencode('"'.$relitem['name'].'"');
			$imdb="https://www.imdb.com/find?q=$search";
			$google="https://google.com/search?q=tv+show+$search";
			echo "<tr><td colspan='3' rowspan='100'><img src='$poster' width='100%'/><p><a href='$google' target='_blank'>Google</a></p><p><a href='$imdb' target='_blank'>IMDB</a></p></td><td colspan='10'>".$relitem['description']."</td></tr>";
		}
		$epi_num=str_pad($relitem['episode_number'], 2, "0", STR_PAD_LEFT);
		$season=str_pad($relitem['season'], 2, "0", STR_PAD_LEFT);
		$show=str_replace(" ",".",stripslashes($relitem['name']));
		$show_description=$relitem['description'] ? stripslashes($relitem['description']) : "&nbsp;";
		$show_category=$relitem['category'] ? stripslashes($relitem['category']) : "&nbsp;";
		$episode_name=$relitem['episode_name'] ? stripslashes($relitem['episode_name']) : "&nbsp;";
		if ($_GET['showid']) {
			$limit = '';
		} else {
			$limit = 'limit 1';
		}
		if ($filter) {
			$fstring="and (`quality` = '$filter' or `video` = '$filter' or `resolution` = '$filter')";
		}
		$instring='';
		if ($relitem['fquality']) {
			foreach (explode(" ", $relitem['fquality']) as $item) {
				$instring.="'$item',";
			}
			$instring=preg_replace("/,$/", "", $instring);
			$fstring="and (`quality` in ($instring) or `video` in ($instring) or `resolution` in ($instring))";
			#echo $fstring;
			$limit = 'limit 1';
		}
		$rel=mysqli_query($dvrdb, "SELECT * FROM `releases` where episodeid='".$relitem['episodeid']."' $fstring group by resolution,video,quality order by score desc $limit;");
		$done=array();
		$alldone=0;
		while ($release=mysqli_fetch_assoc($rel)) {
				$showid=$relitem['showid'];
				$releaseid=$release['releaseid'];
				$quality=$release['resolution']." ".$release['video'];
				$line= "<tr>";
				if ($release['downloaded'] || $relitem['edownloaded']) {
					$line.="<td class='showlist_icon'><img id='download_icon_$releaseid' src='download_done.png' onclick=\"downloadRelease($releaseid);\"/></td>";
				} else {
					$line.= "<td class='showlist_icon'><img id='download_icon_$releaseid' src='download.png' onclick=\"downloadRelease($releaseid);\"/></td>";
				}
				if ($relitem['favouriteid']) {
					$line.="<td class='showlist_icon'><img id='favourite_icon_$showid' src='favourite.png' onclick=\"addFavourite($showid, '$quality');\"></td>";
				} else {
					$line.="<td class='showlist_icon'><img id='favourite_icon_$showid' src='favourite_grey.png' onclick=\"addFavourite($showid, '$quality');\"></td>";
				}
				if ($relitem['ignore']==1) {
					$line.="<td class='showlist_icon'><img id='ignore_icon_$showid' src='ignore.png' onclick=\"ignoreShow($showid);\"></td>";
				} else {
					$line.= "<td class='showlist_icon'><img id='ignore_icon_$showid' src='ignore_grey.png' onclick=\"ignoreShow($showid);\"></td>";
				}

				$line.="<td class='showlist_name_long'><a title='".$release['original_name']."' href='?showid=".$relitem['showid']."'>".$relitem['name']."</a></td>";
				$line.="<td class='showlist_episode hidden-sm hidden-xs'>".$episode_name."</td>";
  			#if ( ! $_GET['showid'] ) { $line.="<td class='showlist_category hidden-sm hidden-xs'>".$show_category."</td>"; }
				$line.="<td class='showlist_season'>".$season."</td><td class='showlist_season'>".$epi_num."</td>";
				$line.="<td class='showlist_quality hidden-sm hidden-xs'><a href='".base_url('filter', $release['quality'])."'>".$release['quality']."</a></td>";
				$line.="<td class='showlist_quality'><a href='".base_url('filter', $release['resolution'])."'>".$release['resolution']."</a></td>";
				$line.="<td class='showlist_quality'><a href='".base_url('filter', $release['video'])."'>".$release['video']."</a></td>";

				$line.="</tr>
				";
				echo $line;
		}
	}

	echo "</tbody></table>";
	print_html_footer();
}
function base_url($param, $value) {
	$url="?";
	foreach ($_GET as $key => $val) {
		if ($key != $param) {
			$url.=$key."=".$val."&";
	  }
	}
	$url.=$param."=".$value;
	return($url);
}
/*----------------------------------------------------------------------------------------------------*/
function build_feed() {
global $dvrdb, $config;

	$xml = new SimpleXMLElement('<rss version="2.0" encoding="utf-8"></rss>');

	/* static channel data */
	$xml->addChild('channel');
	$xml->channel->addChild('title', 'tDvr Feed');
	$xml->channel->addChild('link', 'http://starcroft.org/tdvr/');
	$xml->channel->addChild('description', 'tDvr TV Programs.');
	$xml->channel->addChild('language', 'en-gb');
	$xml->channel->addChild('pubDate', date(DATE_RSS));

	$q="SELECT shows.name, shows.description, shows.category, favourites.favouriteid, episodes.season, episodes.episode_number, episodes.timestamp, episodes.episodeid, episodes.episode_name FROM `episodes`
		LEFT JOIN shows ON shows.showid = episodes.showid
		LEFT JOIN `favourites` on favourites.showid = episodes.showid
		WHERE shows.ignore = '0' and favourites.favouriteid is not null
		and episodes.timestamp > date_sub(now(), INTERVAL 30 day)
		ORDER BY episodes.timestamp desc";

	$res=mysqli_query($dvrdb,$q) or print mysqli_error($dvrdb);
	while ($relitem=mysqli_fetch_assoc($res)) {
		$epi_num=str_pad($relitem['episode_number'], 2, "0", STR_PAD_LEFT);
		$season=str_pad($relitem['season'], 2, "0", STR_PAD_LEFT);
		$show=str_replace(" ",".",stripslashes($relitem['name']));
		$show_description=stripslashes($relitem['description']);
		$show_category=stripslashes($relitem['category']);
		$show_prefix="$show.S".$season."E".$epi_num.".";
		if ($relitem['episode_name']) {
				$show_prefix.=str_replace(" ", ".", stripslashes($relitem['episode_name']))."."; }
		$rel=mysqli_query($dvrdb, " SELECT * FROM `releases` where episodeid='".$relitem['episodeid']."' group by quality, priority order by priority asc ;");
			$done=array();
			while ($release=mysqli_fetch_assoc($rel)) {
				if ($config['hide_hd']==1 && ($release['quality']=="720p" || $release['quality']=='1080p')) {

				} else {
					if (! $done[$release['quality']]) {
    					$item = $xml->channel->addChild('item');
    					$item->addChild('title', $show_prefix.$release['quality']);
    					$item->addChild('link', htmlentities($release['url']));
						$item->addChild('description', $show_description);
						$item->addChild('category', $show_category);
    					$item->addChild('pubDate', date(DATE_RSS, strtotime($release['timestamp'])));
						$done[$release['quality']]=1;
					}
				}
			}
	}
	header('Content-type: text/xml');
	echo $xml->asXML();
}
/*---------------------------------------------------------------------------------------------------*/
function list_favourites() {
global $dvrdb, $config;
print_html_header();

$qresults=mysqli_query($dvrdb, "SELECT distinct(`quality`) from `releases` where `quality` not in ('unknown', 'dsr', '') order by `quality`");
$vresults=mysqli_query($dvrdb, "SELECT distinct(`video`) from `releases` where `video` not in ('unknown', '') order by `video`");
$rresults=mysqli_query($dvrdb, "SELECT distinct(`resolution`) from `releases` where `resolution` not in ('unknown', '') order by `resolution`");
$headarray=array("Name", "S", "E");

while ($res = mysqli_fetch_assoc($qresults)) {
		$qarray[]=$res['quality'];
}
while ($res = mysqli_fetch_assoc($vresults)) {
		$varray[]=$res['video'];
}
while ($res = mysqli_fetch_assoc($rresults)) {
		$rarray[]=$res['resolution'];
}
$matcharray=array_merge($qarray, $varray, $rarray);
$headarray=array_merge($headarray, $matcharray);

$results=mysqli_query($dvrdb, "SELECT shows.name, shows.showid, favourites.season, favourites.episode, favourites.favouriteid, favourites.quality, favourites.location, favourites.ratio
FROM `favourites`
LEFT JOIN `shows` on shows.showid = favourites.showid
WHERE shows.name is not null
ORDER BY shows.updated DESC;") or print mysqli_error();

echo "<table class='table table-condensed table-striped'><thead>";
foreach ($headarray as $head) {
	echo "<th>$head</th>";
}
echo "</thead><tbody>";
while ($res=mysqli_fetch_assoc($results)) {
	$alt=abs($alt-1);
	echo "<tr>";

	echo "<td><a href='?showid=".$res['showid']."'>".$res['name']."</a></td>";
	echo "<td>".$res['season']."</td>";
	echo "<td>".$res['episode']."</td>";
	foreach ($matcharray as $q) {
		if (stristr($res['quality'], $q)) {
			$checked="checked";
		} else {
			$checked="";
		}
		echo "<td><input type='checkbox' class='favcheck' id='".$res['favouriteid']."-".$q."' $checked onchange='toggleFavourite(".$res['favouriteid'].",\"$q\");'></td>";

	}
	echo "</tr>";
}
echo "</tbody></table>";
print_html_footer();
}
/*---------------------------------------------------------------------------------------------------*/
function process_favourites() {
	global $dvrdb, $config;

	$q="SELECT shows.name, shows.showid, episodes.episodeid, episodes.season as e_season, episodes.episode_number as e_episode, releases.score as score, releases.url, releases.video as rvideo, releases.resolution as rresolution, releases.quality as rquality, releases.priority, releases.releaseid, releases.timestamp as release_date, favourites.favouriteid, favourites.quality, favourites.location, favourites.ratio
	FROM `favourites`
	LEFT JOIN `shows` on shows.showid=favourites.showid
	LEFT JOIN `episodes` on episodes.showid = shows.showid
	LEFT JOIN `releases` on releases.episodeid= episodes.episodeid
	WHERE ((episodes.episode_number > favourites.episode and episodes.season = favourites.season) or (episodes.season > favourites.season) or (favourites.season =0) or favourites.season is NULL)
	and releases.url is not null
	and shows.ignore ='0'
	and episodes.downloaded = '0'
	and releases.timestamp > date_sub(now(), interval 14 day)
	ORDER BY shows.name, episodes.season ASC , episodes.episode_number ASC , releases.score DESC, releases.priority DESC
	";
	$oldshow="";
	$oldepi="";
	$oldseas="";
	$got=0;
	$match=0;

	if ($results=mysqli_query($dvrdb, $q)) {
		while ($rel=mysqli_fetch_assoc($results)){
			$showid=$rel['showid'];
			$epi=$rel['e_episode'];
			$seas=$rel['e_season'];
			if ($showid != $oldshow || $oldepi != $epi || $oldseas != $seas) {
				$got=0;
				$match=0;
				$rmatched=0;
				$rmatch=0;
				$vmatched=0;
				$vmatch=0;
				$qmatch=0;
				$qmatched=0;
			}
			print $rel['name']." : ".$seas."/".$epi." ".$rel['rquality']." ".$rel['rvideo']." ".$rel['rresolution']." :: ".$rel['quality']." :: " ;
			if ($got == 0) {
						$qbits=explode(" ", $rel['quality']);
						foreach ($qbits as $bit) {
							print "-$bit-";
								if (in_array($bit, $config['resolutions'])) {
									$rmatch=1;
									if ($bit == $rel['rresolution']) {
										$rmatched=1;
									}
								}
								if (in_array($bit, $config['video_map'])) {
									$vmatch=1;
									if ($bit == $rel['rvideo']) {
										$vmatched=1;
									}
								}

								if (in_array($bit, $config['quality'])) {
									$qmatch=1;
									if ($bit == $rel['rquality']) {
										$qmatched=1;
									}
								}
				}
				# This will be 0 if all the matches are matched, or if no matches set :D
				$allmatch=($qmatch - $qmatched) + ($vmatch - $vmatched) + ($rmatch - $rmatched);
				print "$rmatch $rmatched $vmatch $vmatched $qmatch $qmatched $allmatch<br>";
				if ($allmatch == 0 ) {
					if ($rel['location']) {
						$save_dir=$rel['location'];
					}	else {
						$save_dir=$config['save_dir'];
					}

					#print $rel['url']." : ".$rel['releaseid']." : ".$save_dir."<br>";
					$err=add_torrent($rel['url'], $save_dir);
					print "<br>";
					if ($err['error']) {
						print $err['error']['message'];
					} else {
						$got=1;
						mysqli_query($dvrdb, "UPDATE `favourites` SET season='$seas', episode='$epi' WHERE favouriteid='".$rel['favouriteid']."';") or print mysqli_error();
						mysqli_query($dvrdb, "UPDATE `releases` SET downloaded='1' WHERE releaseid='".$rel['releaseid']."';") or print mysqli_error();
						mysqli_query($dvrdb, "UPDATE `episodes` SET downloaded='1' WHERE episodeid='".$rel['episodeid']."';") or print mysqli_error();
					}
				}
			}
			$oldepi=$epi;
			$oldseas=$seas;
			$oldshow=$showid;
		}
	}
}
/*---------------------------------------------------------------------------------------------------*/
function clean_number($number) {
		$number=preg_replace("/[^0-9]/", "", $number);
		return(abs($number));
}
/*---------------------------------------------------------------------------------------------------*/
function clean_text($string) {
		$string=preg_replace("/[^a-z0-9\s\-\_\.!]/i", "", $string);
		return ($string);
}
function strip_text($string) {
                $string=strtolower(preg_replace("/[^a-z0-9]/i", "", $string));
                return ($string);
}
function swapands($string) {
	if (stristr($string, " and ")) {
		$string=str_replace(" and ", " & ", $string);

	} elseif (stristr($string, " & ")) {
		$string=str_replace(" & ", " and ", $string);
	}
	return ($string);
}
/*---------------------------------------------------------------------------------------------------*/
function log_it($code,$entry){
	global $dvrdb;
	#$entry=addslashes($entry);
	#mysqli_query($dvrdb, "INSERT INTO `log` (`error`, `text`) VALUES ('$code', '$entry');") or print mysqli_error();
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
	<title>tellyDvr</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap-united.min.3.css" />
	<link href="https://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-glyphicons.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="style.css" />
	<script src="./scripts.js?" type="text/javascript"></script>
</head>
<body>
<?php
print_html_nav();
}
/*----------------------------------------------------------------------------------------------------*/
function print_html_footer() {
?>
	</div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.6/umd/popper.js"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta/js/bootstrap.min.js"></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>
<?php
}
/*----------------------------------------------------------------------------------------------------*/
function print_html_nav() {

$action=$_GET['action'] ? clean_text($_GET['action']) : "listreleases";
$search=$_GET['search'] ? clean_text($_GET['search']) : "";
?>
    <div class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <a href="?action=listreleases" class="navbar-brand">tellyDvr</a>
          <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>
        <div class="navbar-collapse collapse" id="navbar-main">
          <ul class="nav navbar-nav">
            <li>
              <a href="?action=listshows">Shows</a>
            </li>
            <li>
              <a href="?action=listfavourites">Favourites</a>
            </li>
						<li>
							<a href="?action=update">Update</a>
						</li>
          </ul>
		<div class="col-sm-3 col-md-4 col-lg-4 navbar-right">
			<form class="navbar-form" role="search" action="?action=listshows">
			<input type="hidden" name="action" value="<?php echo $action; ?>">
			<div class="input-group">
				<input type="text" class="form-control" placeholder="Search" name="search" value="<?php echo $search; ?>">
				<div class="input-group-btn">
					<button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
				</div>
			</div>
			</form>
		</div>
        </div>
      </div>
    </div>

    <div id="mainbody" class="container-fluid">
<?php
}
?>
