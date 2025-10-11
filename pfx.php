<?php
if(isset($_GET["verbose"])){
	$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
include_once("ressources/logs.inc");
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once('ressources/class.stats-appliance.inc');

if(isset($_GET["pfx"])){pfx();exit;}
if(isset($_GET["der"])){der();exit;}
$q=new mysql();

$q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
$results=$q->QUERY_SQL("SELECT CommonName,pks12,DerContent FROM sslcertificates ORDER BY CommonName");
if(!$q->ok){echo FATAL_ERROR_SHOW_128($q->mysql_error_html());}

foreach ($results as $index=>$ligne){
	
	if(strlen($ligne["pks12"])>0){
	
		$f[]="<div style='float:left;width:128px;margin:10px;width:350px;' class=form>
		<center><a href=\"pfx.php?der={$ligne["CommonName"]}\"><img src='img/certificate-128.png'></center>
		<center style='font-size:26px;text-decoration:underline;font-weight:bold'>{$ligne["CommonName"]}.der</center>
		</div></a>";
		
		$f[]="<div style='float:left;width:128px;margin:10px;width:350px;' class=form>
			<center><a href=\"pfx.php?pfx={$ligne["CommonName"]}\"><img src='img/pfx-128.png'></center>
			<center style='font-size:26px;text-decoration:underline;font-weight:bold'>{$ligne["CommonName"]}.pfx</center>
			</div></a>";
		}
	
	

}

$tpl=new template_users("{certificate}",@implode("\n", $f),$_SESSION,0,0,0,$cfg);
$html=$tpl->web_page;
echo $html;

function der(){

	$CommonName=$_GET["der"];
	$q=new mysql();
	$sql="SELECT DerContent  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$data=$ligne["DerContent"];
	$fsize=strlen($data);
	header('Content-type:  application/x-x509-ca-cert');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$CommonName.der\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $data;


}

function pfx(){

	$CommonName=$_GET["pfx"];
	$q=new mysql();
	$sql="SELECT pks12  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$data=base64_decode($ligne["pks12"]);
	$fsize=strlen($data);
	header('Content-type: application/x-pkcs12');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$CommonName.pfx\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $data;


}