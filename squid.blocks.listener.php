<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
	if(isset($_POST["STATS_LINE"])){STATS_LINE();exit;}
	if(isset($_POST["STREAM_LINE"])){STREAM_LINE();exit;}
	if(isset($_FILES["SQUID_GRAPHS"])){SQUID_GRAPHS();exit;}



function STATS_LINE(){
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==0){
		writelogs("EnableWebProxyStatsAppliance=$EnableWebProxyStatsAppliance from ".$_SERVER["REMOTE_ADDR"] ." (aborting)",__FUNCTION__,__FILE__,__LINE__);
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	$q=new mysql_squid_builder();
	$sql=base64_decode($_POST["STATS_LINE"]);
	if($sql==null){return;}
	if(substr($sql, strlen($sql)-1,1)==','){$sql=substr($sql, 0,strlen($sql)-1);}
	$sql=str_replace("VALUES ,(", "VALUES (", $sql);
	
	if(!preg_match("#\s+VALUES\s+\(#", $sql)){
		writelogs("WRONG QUERY `$sql` BUT ACCEPT IT",__FUNCTION__,__FILE__,__LINE__);
		echo "<ANSWER>OK</ANSWER>\n";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	if(!$q->QUERY_SQL($sql)){
		if(preg_match("#Error Column count doesn.+?t match value count#", $q->mysql_error)){
			if(!chekError($sql)){echo "$q->mysql_error\n";
			writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
			die("DIE " .__FILE__." Line: ".__LINE__);}
		}else{
			echo "$q->mysql_error\n";
			writelogs("Mysql error: `$q->mysql_error`",__FUNCTION__,__FILE__,__LINE__);
			writelogs("Mysql error: -------------------",__FUNCTION__,__FILE__,__LINE__);
			writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
			writelogs("Mysql error: -------------------",__FUNCTION__,__FILE__,__LINE__);
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
		
	}
	writelogs("Received ".strlen($sql)." bytes from ".$_SERVER["REMOTE_ADDR"] ." (success)",__FUNCTION__,__FILE__,__LINE__);
	echo "<ANSWER>OK</ANSWER>\n";
	
}
function STREAM_LINE(){
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==0){writelogs("EnableWebProxyStatsAppliance=$EnableWebProxyStatsAppliance from ".$_SERVER["REMOTE_ADDR"] ." (aborting)",__FUNCTION__,__FILE__,__LINE__);die("DIE " .__FILE__." Line: ".__LINE__);}
	$q=new mysql();
	$sql=base64_decode($_POST["STREAM_LINE"]);
	$q->BuildTables();
	
	
	$hostname=$_POST["HOSTNAME"];
	$q->QUERY_SQL("DELETE FROM youtubecache WHERE proxyname='$hostname'","artica_events");
	
	$q->QUERY_SQL($sql,"artica_events");
	if($sql==null){return;}
	if(!$q->QUERY_SQL($sql,"artica_events")){
		writelogs("Received ".strlen($sql)." bytes from ".$_SERVER["REMOTE_ADDR"] ." ({$_POST["HOSTNAME"]}) (failed)",__FUNCTION__,__FILE__,__LINE__);
		writelogs("Mysql error: `$q->mysql_error`",__FUNCTION__,__FILE__,__LINE__);
		writelogs("Mysql error: -------------------",__FUNCTION__,__FILE__,__LINE__);
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		writelogs("Mysql error: -------------------",__FUNCTION__,__FILE__,__LINE__);
		echo "$q->mysql_error\n";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
		
	
	writelogs("Received ".strlen($sql)." bytes from ".$_SERVER["REMOTE_ADDR"] ." (success)",__FUNCTION__,__FILE__,__LINE__);
	echo "<ANSWER>OK</ANSWER>\n";
	
}



function chekError($sql){
	if(preg_match("#INSERT INTO `(.+?)`.+?,public_ip\) VALUES(.+)#is",$sql,$re)){
		writelogs("Preg Match success...",__FUNCTION__,__FILE__,__LINE__);
		$sql="INSERT INTO `{$re[1]}` (client,website,category,rulename,public_ip,`why`,`blocktype`,`hostname`) VALUES {$re[2]}";
		$q=new mysql_squid_builder();
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			writelogs("Failed sql after preg_match...$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
		return $q->ok;
	}else{
		writelogs("Preg Match failed...",__FUNCTION__,__FILE__,__LINE__);
	}
	
}

function GetProxyName($hostname,$ipaddr){
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ipaddr FROM squidservers WHERE hostname ='$hostname'"));
	if($ligne["ipaddr"]<>null){return $hostname;}
	
	$f=explode(".", $hostname);
	$hostname=$f[0];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ipaddr FROM squidservers WHERE hostname ='$hostname'"));
	if($ligne["ipaddr"]<>null){return $hostname;}
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT hostname FROM squidservers WHERE ipaddr ='$ipaddr'"));
	if($ligne["hostname"]<>null){return $ligne["hostname"];}
	return $hostname;
}

function SQUID_GRAPHS(){
	while (list ($num, $line) = each ($_POST) ){writelogs("_POST:: receive $num=$line",__FUNCTION__,__FILE__,__LINE__);}
	while (list ($num, $line) = each ($_FILES['SQUID_GRAPHS']) ){writelogs("_FILES:: receive $num=$line",__FUNCTION__,__FILE__,__LINE__);}	
	
	$ipaddr=$_SERVER["REMOTE_ADDR"];
	reset($_FILES['SQUID_GRAPHS']);
	$error=$_FILES['SQUID_GRAPHS']['error'];
	$tmp_file = $_FILES['SQUID_GRAPHS']['tmp_name'];
	$hostname=$_POST["HOSTNAME"];
	$hostname=GetProxyName($hostname,$ipaddr);
	
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/$hostname";
	if(!is_dir($content_dir)){mkdir($content_dir,0755,true);}
	if( !is_uploaded_file($tmp_file) ){while (list ($num, $val) = each ($_FILES['fichier']) ){$error[]="$num:$val";}writelogs("ERROR:: ".@implode("\n", $error),__FUNCTION__,__FILE__,__LINE__);exit();}
	 
	$type_file = $_FILES['SQUID_GRAPHS']['type'];
	$name_file = $_FILES['SQUID_GRAPHS']['name'];
	writelogs("_POST:: receive name_file=$name_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
	if(file_exists( $content_dir . "/" .$name_file)){@unlink( $content_dir . "/" .$name_file);}
 	if( !move_uploaded_file($tmp_file, $content_dir . "/" .$name_file) ){writelogs("Error Unable to Move File : ". $content_dir . "/" .$name_file,__FUNCTION__,__FILE__,__LINE__);exit();}
    $moved_file=$content_dir . "/" .$name_file;
    
    if(!is_file("/bin/tar")){
    	writelogs("/bin/tar no such file",__FUNCTION__,__FILE__,__LINE__);
    	return;
    }
    $cmd="/bin/tar -xhf $moved_file -C $content_dir/";
    writelogs("$cmd",__FUNCTION__,__FILE__,__LINE__);
    exec("$cmd 2>&1",$results);
    foreach ($results as $num=>$val){writelogs("$val",__FUNCTION__,__FILE__,__LINE__);	}
    
 		
}

