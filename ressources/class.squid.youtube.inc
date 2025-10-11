<?php
	include_once(dirname(__FILE__)."/class.ccurl.inc");
	include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");
class YoutubeStats{
	private $VIDEOID='';
	
	public function youtube_category($youtubeid){
		if(isset($GLOBALS["youtube_category($youtubeid)"])){return $GLOBALS["youtube_category($youtubeid)"];}
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT category FROM youtube_objects WHERE youtubeid='$youtubeid'"));
		if(trim($ligne["category"])==null){
			if($GLOBALS["VERBOSE"]){echo " ->youtube_infos($youtubeid)\n";}
			if($this->youtube_infos($youtubeid)){
				$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT category FROM youtube_objects WHERE youtubeid='$youtubeid'"));
				$GLOBALS["youtube_category($youtubeid)"]=$ligne["category"];
				return trim($ligne["category"]);
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo " ->youtube_category($youtubeid) = \"{$ligne["category"]}\"\n";}
		}
	
		$GLOBALS["youtube_category($youtubeid)"]=$ligne["category"];
		return $GLOBALS["youtube_category($youtubeid)"];
	}
	
	public function youtube_title($youtubeid){
		if(isset($GLOBALS["youtube_title($youtubeid)"])){return $GLOBALS["youtube_title($youtubeid)"];}
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT title FROM youtube_objects WHERE youtubeid='$youtubeid'"));
		if(trim($ligne["title"])==null){$ligne["title"]="";}
		$GLOBALS["youtube_title($youtubeid)"]=$ligne["title"];
		return $GLOBALS["youtube_title($youtubeid)"];
	}

	
	
	
	public function youtube_infos($VIDEOID,$forceupdate=false){
			if(!isset($GLOBALS["YTUBE_NO_PROXY"])){$GLOBALS["YTUBE_NO_PROXY"]=false;}
			if(!isset($GLOBALS["RESTARTED_HTTP"])){$GLOBALS["RESTARTED_HTTP"]=false;}
			$this->VIDEOID=$VIDEOID;
			if(isset($GLOBALS["youtubeid"][$VIDEOID])){return true;}
			$uri="https://gdata.youtube.com/feeds/api/videos/$VIDEOID?v=2&alt=jsonc";
			if($GLOBALS["VERBOSE"]){echo "$VIDEOID:: $uri -> \n";}
			$curl=new ccurl($uri);
			if($GLOBALS["YTUBE_NO_PROXY"]){$curl->NoLocalProxy(); }
			$curl->Timeout=10;
			$error=null;
			if($GLOBALS["VERBOSE"]){echo "%% $uri\n";}
			
			if(!$curl->GetFile("/tmp/jsonc.inc")){
				if(!$GLOBALS["RESTARTED_HTTP"]){
					$this->youtube_events("gdata.youtube.com = Failed = > $curl->error Try without no proxy",__LINE__);
					$GLOBALS["RESTARTED_HTTP"]=true;
					$GLOBALS["YTUBE_NO_PROXY"]=true;
					return $this->youtube_infos($VIDEOID,$forceupdate);
				}
				$this->youtube_events("gdata.youtube.com = Failed = > $curl->error",__LINE__);
				return false;
			}
			
			$infox=@file_get_contents("/tmp/jsonc.inc");
			$infos=json_decode($infox);
			
			
			
			$uploaded=$infos->data->uploaded;
			$title=$infos->data->title;
			if($title==null){
				$error=$infos->error->message;
				if($error==null){
					$this->youtube_events("data->title NULL ($error)",__LINE__);
					if($GLOBALS["VERBOSE"]){echo "data->title NULL ($error)\n";var_dump($infos);}
					return false;
				}else{
					$title=$error;
				}
			}
			$category=$infos->data->category;
			if($category==null){
				if($error<>null){
					$category=$error;
				}else{
					$category="unknown";
				}
			}
	
	
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT `youtubeid` FROM youtube_objects WHERE `youtubeid`='$VIDEOID'"));
	
		if(!$q->ok){
			$this->youtube_events("$q->mysql_error",__LINE__);
			if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}
			return;
		}
	
		if($ligne["youtubeid"]<>null){
			if(trim($ligne["category"])==null){
				if($category<>null){
					$category=mysql_escape_string2($category);
					$q->QUERY_SQL("UPDATE youtube_objects SET category='$category' WHERE youtubeid='{$ligne["youtubeid"]}'");
					if(!$q->ok){$this->youtube_events("$q->mysql_error",__LINE__);}
				}
			}
			
			
			$GLOBALS["youtubeid"][$VIDEOID]=true;
			if(!$forceupdate){
				if($GLOBALS["VERBOSE"]){echo "$VIDEOID already exists in table\n";}
				return true;
			}
		}
	
		$tumbnail=$infos->data->thumbnail->sqDefault;
		$curl=new ccurl($tumbnail);
		$curl->GetFile("/tmp/thumbnail");
		$CATZ["Autos"]="automobile/cars";
		$CATZ["Autos & Vehicles"]="automobile/cars";
		$CATZ["Film & Animation"]="movies";
		$CATZ["Film"]="movies";
		$CATZ["Games"]="games";
		$CATZ["News"]="news";
		$CATZ["Gaming"]="games";
		$CATZ["Education"]="recreation/schools";
		$CATZ["Music"]="music";
		$CATZ["News & Politics"]="news";
		$CATZ["People & Blogs"]="hobby/pets";
		$CATZ["Science & Technology"]="sciences";
		$CATZ["Sports"]="recreation/sports";
		$CATZ["Travel & Events"]="recreation/travel";
		if(isset($CATZ[$category])){$category=$CATZ[$category];}
	
		$date=strtotime($uploaded);
		$zDate=date("Y-m-d H:i:s");
		$infox_enc=mysql_escape_string2(base64_encode($infox));
		$title=mysql_escape_string2($title);
		$category=mysql_escape_string2($category);
		$duration=$infos->data->duration;
		
		
	
	
		$thumbnail=mysql_escape_string2(@file_get_contents("/tmp/thumbnail"));
		
		
			if($ligne["youtubeid"]<>null){
				$sql="UPDATE youtube_objects SET 
				category='$category',
				title='$title',
				content='$infox_enc',
				uploaded='$zDate',
				duration='$duration',
				thumbnail='$thumbnail'
				WHERE youtubeid='{$ligne["youtubeid"]}'
				";
				$q->QUERY_SQL($sql);
				if(!$q->ok){
					$this->youtube_events("$q->mysql_error",__LINE__);
					if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}
					return false;
				}
				return true;
			}
		
		
		$sql="INSERT IGNORE INTO youtube_objects (youtubeid,category,title,content,uploaded,duration,thumbnail)
		VALUES('$VIDEOID','$category','$title','$infox_enc','$zDate','$duration','$thumbnail')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			$this->youtube_events($q->mysql_error,__LINE__);
			if(strpos("youtube_objects' doesn", " $q->mysql_error")>0){
				$q->CheckTables();
				$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT `youtubeid` FROM youtube_objects WHERE `youtubeid`='$VIDEOID'"));
			}
		}
		if(!$q->ok){$this->youtube_events("$q->mysql_error",__LINE__);if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}return false;}
		return true;
	
	
	
	}

	private function youtube_events($text,$line=0){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if($GLOBALS["VERBOSE"]){echo "[$this->VIDEOID] $text\n";}
		$common="/var/log/artica-postfix/youtube.inject.log";
		$size=@filesize($common);
		if($size>100000){@unlink($common);}
		$pid=getmypid();
		$date=date("Y-m-d H:i:s");
		$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)."$date $text");
		$h = @fopen($common, 'a');
		$sline="[$pid] [$this->VIDEOID] $text";
		$line="$date [$pid] [$this->VIDEOID] $text [Line:$line]\n";
		@fwrite($h,$line);
		@fclose($h);
	
	}
	
}