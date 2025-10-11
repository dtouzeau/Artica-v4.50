<?php


class artica_meta_client_categories{
	
	
	
	function __construct($gzfile){
		$this->build($gzfile);
		
	}
	
	private function build($gzfile){
		if(!is_file($gzfile)){
			$this->events("$gzfile no such file", __FUNCTION__,__FILE__,__LINE__);
			return;}
		$unix=new unix();
		$sock=new sockets();
		
		if(!$unix->uncompress($gzfile, "$gzfile.array")){
			@unlink("$gzfile.array");
			@unlink("$gzfile");
			$this->events("[CATEGORIES]: $gzfile wrong gz file", __FUNCTION__,__FILE__,__LINE__);
			return;
		}
		@unlink("$gzfile");
		$OLD_MD5=$sock->GET_INFO("ArticaMetaGzCatz");
		$NEW_MD5=@md5_file("$gzfile.array");
		$this->events("$OLD_MD5 / $NEW_MD5", __FUNCTION__,__FILE__,__LINE__);
		if($OLD_MD5==$NEW_MD5){
			@unlink("$gzfile.array");
			$this->events("[CATEGORIES]: $gzfile no update", __FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		$array=unserialize(@file_get_contents("$gzfile.array"));
        if(!is_array($array)){$array=array();}
		@unlink("$gzfile.array");
		if(!is_array($array)){
			$this->events("[CATEGORIES]: $gzfile not an array", __FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		$q=new mysql_squid_builder();
		while (list ($category, $MAIN) = each ($array)){
			
			if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: * * * * $category * * * *\n";}
			$MAIN_SITES=count($MAIN["SITES"]);
			if(!isset($MAIN["URLS"])){$MAIN["URLS"]=array();}
			$this->events("[CATEGORIES]: $category: $MAIN_SITES sites items", __FUNCTION__,__FILE__,__LINE__);
			$this->events("[CATEGORIES]: $category: ".count($MAIN["URLS"])." urls items", __FUNCTION__,__FILE__,__LINE__);
			$this->inject_sites($MAIN["SITES"], $category);
			$this->inject_uris($MAIN["URLS"],$category);
			$CATEGORIES[$category]=true;
		}
		
		$php5=$unix->LOCATE_PHP5_BIN();
		$q->QUERY_SQL("DELETE FROM personal_categories WHERE Meta=1");
		while (list ($category, $www) = each ($CATEGORIES)){
			
			$category="$category";
			$q->QUERY_SQL("INSERT IGNORE INTO personal_categories (category,Meta) VALUES ('$category','1')");
			$q->QUERY_SQL("UPDATE personal_categories SET Meta=1 WHERE category='$category'");
			system("$php5 /usr/share/artica-postfix/exec.squidguard.php --compile-category \"$category\"");
		}
		
		$sock->SET_INFO("ArticaMetaGzCatz", $NEW_MD5);
	
	}
	
	private function inject_uris($array,$category){
		if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: * * * * $category -> inject_uris() * * * *\n";}
		$q=new mysql_squid_builder();
	
	
	
		$sql="CREATE TABLE IF NOT EXISTS `webfiltering_meta_urls` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`zmd5` VARCHAR(90) NOT NULL,
				`pattern` VARCHAR(128) NOT NULL,
				PRIMARY KEY (`zmd5`),
				INDEX ( `pattern`)
				)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
		$q->QUERY_SQL("DELETE FROM webfiltering_meta_urls WHERE category='$category'");
	
		if(count($array)==0) {
			if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: * * * * $category -> 0 sites aborting * * * *\n";}
			return;
		}
		$f=array();
		print_r($array);
		reset($array);
		while (list ($index, $www) = each ($array)){
				
				
			if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: $index * * * * $www * * * *\n";}
			$md5=md5("$category$www");
			$f[]="('$md5','$category','$www')";
	
			if(count($f)>500){
				$sql="INSERT IGNORE INTO webfiltering_meta_urls (zmd5,category,pattern) VALUES ".@implode(",", $f);
				$q->QUERY_SQL($sql);
				if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: $sql\n\n";}
				$f=array();
			}
	
		}
	
	
	
		if(count($f)>0){
			$sql="INSERT IGNORE INTO webfiltering_meta_urls (zmd5,category,pattern) VALUES ".@implode(",", $f);
			$q->QUERY_SQL($sql);
			if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: $sql\n\n";}
			$f=array();
		}
		if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: INJECT DONE\n";}
	
	}
	
	
	private function inject_sites($array,$category){
		if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: * * * * $category -> inject_sites() * * * *\n";}
		$q=new mysql_squid_builder();
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `webfiltering_meta_items` (
				`category` VARCHAR( 15 ) NOT NULL ,
				`zmd5` VARCHAR(90) NOT NULL,
				`pattern` VARCHAR(128) NOT NULL,
				PRIMARY KEY (`zmd5`),
				INDEX ( `pattern`) 
				)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
		$q->QUERY_SQL("DELETE FROM webfiltering_meta_items WHERE category='$category'");
		
		if(count($array)==0) {
			if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: * * * * $category -> 0 sites aborting * * * *\n";}
			return;
		}
		$f=array();
		print_r($array);
		reset($array);
		while (list ($index, $www) = each ($array)){
			
			
			if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: $index * * * * $www * * * *\n";}
			$md5=md5("$category$www");
			$f[]="('$md5','$category','$www')";
				
			if(count($f)>500){
				$sql="INSERT IGNORE INTO webfiltering_meta_items (zmd5,category,pattern) VALUES ".@implode(",", $f);
				$q->QUERY_SQL($sql);
				if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: $sql\n\n";}
				$f=array();
			}
				
		}
		
		
		
		if(count($f)>0){
			$sql="INSERT IGNORE INTO webfiltering_meta_items (zmd5,category,pattern) VALUES ".@implode(",", $f);
			$q->QUERY_SQL($sql);
			if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: $sql\n\n";}
			$f=array();
		}
		if($GLOBALS["OUTPUT"]){echo "[CATEGORIES]: INJECT DONE\n";}
		
	}
	
	
	private function events($text,$function,$file=null,$line=0){
		if($file==null){$file=basename(__FILE__);}else{$file=basename($file);}
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-meta-agent.log";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$text="[$file][$pid] $date $function:: $text (L.$line)\n";
		if($GLOBALS["OUTPUT"]){echo $text;}
		
		@fwrite($f, $text);
		@fclose($f);
	
		$logFile="/var/log/artica-meta.log";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, $text);
		@fclose($f);
	}
	
}