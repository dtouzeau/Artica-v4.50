<?php
if(isset($_SESSION["uid"])){header("content-type: application/x-javascript");echo "document.location.href='logoff.php'";exit();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["www"])){test();exit;}
if(isset($_GET["results"])){results();exit;}
js();


function js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog("{verify_rules} {APP_GO_SHIELD_SERVER}", "$page?popup=yes");
}


function popup() {
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();

	$ipaddr=$_SESSION["GOSHIELDT"]["IP"];
	$username=$_SESSION["GOSHIELDT"]["USER"];
	$www=$_SESSION["GOSHIELDT"]["WWW"];
	if($ipaddr==null){$ipaddr=$_SERVER["REMOTE_ADDR"];}
	if($www==null){$www="http://www.youporn.com";}
	$form[]=$tpl->field_text("www", "{request}", $www);
	$form[]=$tpl->field_ipaddr("ipaddr", "{ipaddr}", $ipaddr);
	$form[]=$tpl->field_text("user", "{username}", $username);
	
	$html=$tpl->form_outside("", @implode("\n", $form),"{ufdbguard_verify_rules_explain}","{check}","Loadjs('$page?results=yes')");
	echo $tpl->_ENGINE_parse_body($html);
}

function results(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $json=json_decode($_SESSION["UFDB_TESTS_RESULTS"]);

    $Info=$json->Info;

    if(preg_match("#first=ERROR.*?errnum=([0-9]+)#",$Info,$m)){
        return $tpl->js_error("{error} $m[1]");
    }

    if(preg_match("#category-name=(.+?)\s+#",$Info,$re)){
        $category=$re[1];
    }
    if(strpos($Info,"shieldsblock=yes")>0){
        $redirected=$tpl->_ENGINE_parse_body("<strong class='text-danger'>{denied} TheShield</strong>");
        $f[]="<small>".  $tpl->_ENGINE_parse_body("{category}: <strong>$category</strong></small><br>");
        $tpl->js_display_results( @implode("<br>",$f),true,$redirected);
        return true;
    }
    if(strpos($Info,"webfilter=pass")>0){
        $title_pass=$tpl->_ENGINE_parse_body("{access_to_internet}");
        $tpl->js_display_results($title_pass."<br>$Info",false, "OK ");
        return true;
    }
    if(!preg_match("#status=[0-9]+\s+url=(.+?)\s+category-name=(.+?)\s+#",$Info,$re)){
        return $tpl->js_error("{error} $Info");
    }

    $url=$re[1];
    $category=$re[2];
    $dataz=parse_url($url);
    $array=array();
    $Username="";
    $queries=explode("&",$dataz["query"]);
    $f[]="<h3 class='font-bold no-margins'>{$dataz["scheme"]}://{$dataz["host"]}:{$dataz["port"]}</h3>";
    foreach ($queries as $line){
        if(preg_match("#(.+?)=(.+)#", $line,$re)){$array[$re[1]]=$re[2];}
    }

    if(preg_match("#clientname=(.+?)&#",$Info,$re)){
        $Username=$re[1];
    }

    if(strlen($category)>1){
        $f[]="<small>".  $tpl->_ENGINE_parse_body("{category}: <strong>$category</strong></small><br>");
    }

    if(isset($array["rule-id"])){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilter_rules WHERE ID={$array["rule-id"]}");
        if(isset($ligne["rulename"])) {
            $f[] = "<small>" . $tpl->_ENGINE_parse_body("{rulename}: <strong>{$ligne["rulename"]} - {$array["clientgroup"]}</strong></small><br>");
        }
    }
    if(isset($array["clientaddr"])){
        $f[]="<small>".   $tpl->_ENGINE_parse_body("{address}: <strong>{$array["clientaddr"]}</strong></small><br>");
    }
    if(strlen($Username)>1){
        $f[]="<small>".   $tpl->_ENGINE_parse_body("{member}: <strong>$Username</strong></small><br>");
    }



    $redirected=$tpl->_ENGINE_parse_body("<strong class='text-danger'>{redirected}</strong>");



    $tpl->js_display_results( @implode("<br>",$f),true,$redirected);





	return true;
}


function test():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ipaddr=urlencode($_POST["ipaddr"]);
	$user=$_POST["user"];
    if(strlen($user)<2){$user="-";}

    if(!preg_match("#^(http|https):#",$_POST["www"])){
        $_POST["www"]="https://".$_POST["www"];
    }


	$_SESSION["GOSHIELDT"]["IP"]=$ipaddr;
	$_SESSION["GOSHIELDT"]["USER"]=$user;
	$_SESSION["GOSHIELDT"]["WWW"]=$_POST["www"];

    $ipaddr=urlencode($_POST["ipaddr"]);
    $user=urlencode($user);
    $www=urlencode($_POST["www"]);
    $api="/goshield/webfiltering/$ipaddr/$user/$www";
    $_SESSION["UFDB_TESTS_RESULTS"]=$GLOBALS["CLASS_SOCKETS"]->REST_API($api);
    return true;
}