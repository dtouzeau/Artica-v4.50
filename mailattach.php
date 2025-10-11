<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once(dirname(__FILE__).'/ressources/class.mysql.mimedefang.inc');
$filename=$_GET["fname"];
if($filename==null){$filename=$_SERVER["QUERY_STRING"];}
if($filename==null){$filename=$_SERVER["PATH_INFO"];}
if($filename==null){$filename=$_SERVER["REQUEST_URI"];}

if(preg_match("#download=(.*?)\&#", $filename,$re)){sendout($re[1]);exit;}

if(strpos("  $filename", '/')>0){
	$tb=explode("/", $filename);
	$filename=$tb[count($tb)-1];
	
}



$tpl=new templates();
$template=@file_get_contents("ressources/templates/default/mailattach.html");
$http="http";
$meport=$_SERVER["SERVER_PORT"];
if($meport==80){$meport=null;}
if($meport==443){$meport=null;$http="https";}
if($meport<>null){
	$meport=":$meport";
}
$page=CurrentPageName();
$me=$_SERVER["HTTP_HOST"];
$uri="$http://$me$meport/$page?download=$filename&t=".time();
	$q=new mysql_mimedefang_builder();
	$sql="SELECT `filename`,`filesize`,`filetime` FROM `storage` WHERE `filename`='$filename'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if(strlen(trim($ligne["filename"]))==0){
		$error="<H4 style='color:#E33034'>{sorry}:: $filename {doesnnot_exists_in_database}</H4>";
		$uri="#";
	}

$size=FormatBytes($ligne["filesize"]);
$time=$ligne["filetime"];

$html="
<strong style='font-size:14px'><a href=\"$uri\">$filename</a></strong>$error<br><br>
<b>{size}:</b>&nbsp;$size<br> <br>
<div style='font-size:12px'><i><strong>{sended}:</a>&nbsp;$time</strong></div><br>
";

$content=str_replace("{TITLE}", $filename, $template);
$content=str_replace("{CONTENT}", $html, $content);
$content=$tpl->_ENGINE_parse_body($content);
$content=str_replace("%s", $filename, $content);
$content=str_replace("%S", $size, $content);
echo $content;

function sendout($filename){
	$q=new mysql_mimedefang_builder();
	$sql="SELECT `filesize`,`filedata` FROM `storage` WHERE `filename`='$filename'";
	include_once(dirname(__FILE__)."/ressources/mimestypes.inc");
	$ext = pathinfo($filename, PATHINFO_EXTENSION);
	
	$content_type="application/octet-stream";
	if(isset($GLOBALS["MIME_CONTENT_TYPES_ARRAY"][$ext])){
		$content_type=$GLOBALS["MIME_CONTENT_TYPES_ARRAY"][$ext];
	}
	
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));	
	$fsize = $ligne["filesize"];
	
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$filename\"");	
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	header("Content-Length: ".$fsize); 
	ob_clean();
	flush();
	echo $ligne["filedata"];	
	
}
