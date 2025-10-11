<?php
if(isset($_SESSION["uid"])){header("content-type: application/x-javascript");echo "document.location.href='logoff.php'";exit();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["www"])){test();exit;}
if(isset($_GET["results"])){results();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{used_databases}", "$page?popup=yes");
}

function popup() {
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();

	$UfdbUsedDatabases=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUsedDatabases"));
    if(!is_array($UfdbUsedDatabases)){$UfdbUsedDatabases=array();}
    $TRCLASS=null;
    if(!isset($UfdbUsedDatabases["INSTALLED"])){
        $UfdbUsedDatabases["INSTALLED"]=array();
    }
    if(!isset($UfdbUsedDatabases["MISSING"])){
        $UfdbUsedDatabases["MISSING"]=array();
    }

    $INSTALLED=$UfdbUsedDatabases["INSTALLED"];
    $MISSING=$UfdbUsedDatabases["MISSING"];
    if(!is_array($MISSING)){$MISSING=array();}
    if(!is_array($INSTALLED)){$INSTALLED=array();}
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{database}/{categories}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $catz=new mysql_catz();


    foreach ($MISSING as $category_id=>$none){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($category_id));
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $category_name=$catz->CategoryIntToStr($category_id);
        $html[]="<td class='text-danger'><strong>$category_name ($category_id)</strong></td>";
        $html[]="<td width='1%' nowrap class='text-danger'>0</td>";
        $html[]="<td class='text-danger' width='1%' nowrap><i class=\"fas fa-times\"></i></td>";
    }

    foreach ($INSTALLED as $index=>$array){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($array));
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $category_id=$array["DB"];
        $category_name=$catz->CategoryIntToStr($category_id);
        $size=FormatBytes($array["SIZE"]/1024);
        $html[]="<td><strong>$category_name</strong></td>";
        $html[]="<td width='1%' nowrap>$size</td>";
        $html[]="<td width='1%' nowrap><i class=\"fas fa-check\"></i></td>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";

    echo $tpl->_ENGINE_parse_body($html);
}

function results(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_display_results($_SESSION["UFDB_TESTS_RESULTS"], $_SESSION["UFDB_TESTS_TRUE"]);
	
}


function test(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$www=$_POST["test"];
	$q=new mysql_squid_builder();
	$ipaddr=$_POST["ipaddr"];
	$user=$_POST["user"];
	$www=$_POST["www"];
	$_SESSION["UFDBT"]["IP"]=$ipaddr;
	$_SESSION["UFDBT"]["USER"]=$user;
	$_SESSION["UFDBT"]["WWW"]=$www;
	if($user==null){$user="-";}
	if($ipaddr==null){$ipaddr="-";}
	$sock=new sockets();
	$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig")));
	$UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));


	if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
	if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
	if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
	if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=1;}
	if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
	if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
	if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}
	if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
	if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=1;}
	if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
	if(is_file("/etc/artica-postfix/UFDB_NO_SOCKETS")){$datas["tcpsockets"]=1;}

	if($datas["remote_port"]==null){$UseRemoteUfdbguardService=0;}
	if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
	if($datas["listen_addr"]=="all"){$datas["listen_addr"]="127.0.0.1";}
	$address=null;


	if($UseRemoteUfdbguardService==1){
		if(trim($datas["remote_server"]==null)){$datas["remote_server"]="127.0.0.1";}
		$address="-S {$datas["remote_server"]} -p {$datas["remote_port"]} ";

	}


	if($address==null){
		if($datas["tcpsockets"]==1){
			$address="-S {$datas["listen_addr"]} -p {$datas["listen_port"]} ";
				
		}else{
			$address="-S 127.0.0.1 -p {$datas["listen_port"]} ";
		}
	}
	if($address==null){echo "<strong style='color:#d32d2d'>Cannot determine address</strong>\n";return;}

	$cmdline="$address $www $ipaddr $user";
	$cmdline=urlencode(base64_encode($cmdline));
	$datas=base64_decode($sock->getFrameWork("squid.php?ufdbclient=$cmdline"));

	if(preg_match("#^http.*#", $www)){
		$url_www=parse_url($www);
		$url_host=$url_www["host"];
	}else{
		$url_host=$www;
	}

	$tpl=new template_admin();
	$title_pass=$tpl->_ENGINE_parse_body("{access_to_internet}");
	$redirected=$tpl->_ENGINE_parse_body("<strong class='text-danger'>{redirected}/{denied}</strong>");
	$datas=trim($datas);
	if($datas=="OK"){$datas=null;}

	if(trim($datas)==null){

		$catz=new mysql_catz();
		$category_id=$catz->GET_CATEGORIES($url_host);
		if($category_id>0){$category_text="<br>".$tpl->_ENGINE_parse_body($tpl->CategoryidToName($category_id));}
		
		$_SESSION["UFDB_TESTS_TRUE"]=false;
		$_SESSION["UFDB_TESTS_RESULTS"]="<H1>$title_pass<H1>$category_text";
		

		return;}




	$HTTP_CODE="{http_status_code}: 302<br>";
	if(preg_match('#status=([0-9]+)\s+url="(.*?)"#', $datas,$re)){$datas=$re[2];$HTTP_CODE="{http_status_code}: {$re[1]}<br>";}
	$url=parse_url($datas);
	if(!is_numeric($url["port"])){$url["port"]=80;}

	$f[]="<h1>";
	$f[]="$redirected</H1>";
	$f[]="<h3 class='font-bold no-margins'>{$url["scheme"]}://{$url["host"]}:{$url["port"]}</h3>";


	$queries=explode("&",$url["query"]);

	while (list ($num, $line) = each ($queries)){
		if(preg_match("#(.+?)=(.+)#", $line,$re)){$array[$re[1]]=$re[2];}
	}

	$f[]="<small>". $tpl->_ENGINE_parse_body($HTTP_CODE)."</small><br>";


	if($array["targetgroup"]=="none"){
		$catz=new mysql_catz();
		$category_id=$catz->GET_CATEGORIES($url_host);
		if($category_id>0){$category=$tpl->CategoryidToName($category_id);}
		if($category==null){$array["targetgroup"]="{ufdb_none} - {unknown}";}else{$array["targetgroup"]="{ufdb_none} - $category";}

	}
	if(preg_match("#^P([0-9]+)#", $array["targetgroup"],$re)){
		$array["targetgroup"]=$tpl->CategoryidToName($array["targetgroup"]);
	}

	if($url["path"] == "/ufdbguardd.php"){
			if(isset($array["rule-id"])){
			$sql="SELECT * FROM webfilter_rules WHERE ID={$array["rule-id"]}";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
			$f[]="<small>".  $tpl->_ENGINE_parse_body("{rulename}: <strong>{$ligne["rulename"]} - {$array["clientgroup"]}</strong></small><br>");
			}
		if(isset($array["clientaddr"])){
		$f[]="<small>".   $tpl->_ENGINE_parse_body("{address}: {$array["clientaddr"]}</small><br>");
			}
		if(isset($array["clientuser"])){
		$f[]="<small>".   $tpl->_ENGINE_parse_body("{member}: {$array["clientuser"]}</small><br>");
			}
		if(isset($array["targetgroup"])){
		$f[]="<small>".   $tpl->_ENGINE_parse_body("{category}: <strong>{$array["targetgroup"]}</strong></small><br>");
			}
	}

	$f[]="";
	$_SESSION["UFDB_TESTS_TRUE"]=true;
	$_SESSION["UFDB_TESTS_RESULTS"]=@implode("\n", $f);

}