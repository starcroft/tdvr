<?php

require_once "../TVMaze-PHP-API-Wrapper/TVMazeIncludes.php";

$name="Sexy Beasts 2021";

if (preg_match("/[0-9]{4}$/", $name, $match)) {
	$year=$match[0];
	$name_no_year=str_replace($year, "", $name);
}

echo $name_no_year."<br>";
echo $name."<br>";

$tvmaze = new JPinkney\TVMaze\Client;

		$shows=$tvmaze->TVMaze->singleSearch($name);
		#print_r($shows);
		if ($shows[0]->id) {
			$show=$shows[0];
		} else {
			$shows=$tvmaze->TVMaze->singleSearch($name_no_year);
			if ($shows[0]->id && stristr($shows[0]->premiered, $year) ) {
				$show=$shows[0];
			}
		}


		if ($show->id) {
				foreach ($show->genres as $genre) { $category.=$genre.","; }
				$category=preg_replace("#,$#","",$category);
				print_r($show);
				echo $show->name;
				#echo $show->externalIDs['thetvdb'];
				#echo $category;
				#echo $show->summary;
				#echo $show->mediumImage;
				#echo "tvmaze ".$show->id;
				echo "<br>";

#				$eps=$tvmaze->TVMaze->getEpisodesByShowID($show->id);
#				print_r($eps);
				$eps=$tvmaze->TVMaze->getEpisodeByNumber($show->id,1,1);
				print_r($eps);
		}

?>