<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.doh-client.inc");
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
$sock=new sockets();
$tpl=new template_admin();
$users=new usersMenus();


if(isset($_POST["dohuri"])){doh_cli();exit;}
if(isset($_GET["standard"])){popup();exit;}
if(isset($_GET["popup"])){tabs();exit;}
if(isset($_POST["dns_hostname"])){tests();exit;}
if(isset($_GET["results"])){results_js();exit;}
if(isset($_GET["results-popup"])){results_popup();exit;}
if(isset($_GET["doh"])){popup_doh();exit;}
if(isset($_GET["doh-cli"])){popup_doh_cli();exit;}

js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog6("{dns_simulation}", "$page?popup=yes",650);
}
function results_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog7("{dns_simulation} {results}", "$page?results-popup=yes",880);	
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	if(!isset($_SESSION["dns_hostname"])){$_SESSION["dns_hostname"]="www.google.fr";}
	if($_SESSION["dns_server"]==null){$_SESSION["dns_server"]="8.8.8.8";}

	if($_SESSION["dns_interface"]==null){$_SESSION["dns_interface"]="no default";}
	if(intval($_SESSION["dns_timeout"])==0){$_SESSION["dns_timeout"]=3;}
	$form[]=$tpl->field_text("dns_hostname", "{hostname}",  $_SESSION["dns_hostname"],true);
    $form[]=$tpl->field_checkbox("PTR","{reverse_lookup}",0);
	$form[]=$tpl->field_interfaces("dns_interface", "{interface}", $_SESSION["dns_interface"]);
	$form[]=$tpl->field_ipaddr("dns_server", "{dns_server}", $_SESSION["dns_server"]);
	$form[]=$tpl->field_numeric("dns_timeout","{timeout2} ({seconds})",$_SESSION["dns_timeout"]);
	$html=$tpl->form_outside(null, $form,"{check_resolution_dns_engine}","{run}","Loadjs('$page?results=yes')");
	echo $html;
}
function popup_doh(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!isset($_SESSION["dns_hostname"])){$_SESSION["dns_hostname"]="www.google.fr";}
    if(!isset($_SESSION["doh_uri"])){$_SESSION["doh_uri"]="https://cloudflare-dns.com/dns-query";}
    $form[]=$tpl->field_ipaddr("dnsipaddr", "{ipaddr}",  $_SESSION["dnsipaddr"],true);
    $form[]=$tpl->field_text("dns_hostname", "{hostname}",  $_SESSION["dns_hostname"],true);
    $form[]=$tpl->field_text("doh_uri", "{doh_uri}",  $_SESSION["doh_uri"],true);
    $html=$tpl->form_outside(null, $form,"{check_resolution_dns_engine}","{run}","");
    echo $html;
}

function doh_cli(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $fileresults=PROGRESS_DIR."/pdns.query";


    $_SESSION["doh_uri"]=$_POST["doh_uri"];
    $_SESSION["dns_hostname"]=$_POST["dns_hostname"];
    $_SESSION["CONTENT_TYPE"]=$_POST["CONTENT_TYPE"];
    $_SESSION["dnsipaddr"]=$_POST["dnsipaddr"];


/*
    $LOGS[]="DoH URL: $doccli->HTTP_URL";
    $LOGS[]="HTTP CODE: ".$doccli->CURLINFO_HTTP_CODE;
    $LOGS[]="HTTP Engine code: ".$doccli->curl_errno;
    $LOGS[]="ANSWER SECTION: {$_SESSION["dns_hostname"]}: $results";
*/
    @file_put_contents($fileresults,serialize($_POST));
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/dohquery"));

    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }

    echo $tpl->post_error("Success: {$_SESSION["dns_hostname"]}: [".$json->Info."]");
}

function popup_doh_cli(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!isset($_SESSION["dns_hostname"])){$_SESSION["dns_hostname"]="www.google.fr";}
    if(!isset($_SESSION["doh_uri"])){$_SESSION["doh_uri"]="https://google.dns/dns-query";}
    if(!isset($_SESSION["dnsipaddr"])){$_SESSION["dnsipaddr"]="8.8.8.8";}
    $form[]=$tpl->field_ipaddr("dnsipaddr", "{ipaddr}",  $_SESSION["dnsipaddr"]);
    $form[]=$tpl->field_text("dns_hostname", "{hostname}",  $_SESSION["dns_hostname"],true);
    $form[]=$tpl->field_text("dohuri", "{doh_uri}",  $_SESSION["doh_uri"],true);
//    $form[]=$tpl->field_array_hash($ContentType,"CONTENT_TYPE","Content-Type",$_SESSION["CONTENT_TYPE"]);

    $html=$tpl->form_outside(null, $form,"{check_resolution_dns_engine}","{run}","Loadjs('$page?results=yes')");
    echo $html;

}

function tabs(){
    $sock=new sockets();
    $page=CurrentPageName();
    $tpl=new template_admin();

    $array["{dns_simulation}"]="$page?standard=yes";
    $array["{doh_simulation}"]="$page?doh-cli=yes";
    $DOHServerEnabled=intval($sock->GET_INFO("DOHServerEnabled"));
    if($DOHServerEnabled==1){$array["{APP_DOH_SERVER}"]="$page?doh=yes";}
    echo $tpl->tabs_default($array);
}

function tests(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();

    foreach ($_POST as $key => $value) {
        writelogs("$key = > [$value]",__FUNCTION__,__FILE__,__LINE__);
    }
    if(!isset($_POST["dns_hostname"])){
        echo $tpl->post_error("dns_hostname missing");
        return false;
    }
    if(isset($_POST["doh_uri"])){
        if(!is_null($_POST["doh_uri"])) {
            $params[] = "doh_uri=" . urlencode($_POST["doh_uri"]);
        }
    }
	$params[]="hostname={$_POST["dns_hostname"]}";
	$params[]="interface={$_POST["dns_interface"]}";
	$params[]="dns_server={$_POST["dns_server"]}";
	$params[]="timeout={$_POST["dns_timeout"]}";
    $params[]="PTR=".intval($_POST["PTR"]);
    $cmd="pdns.php?digg=yes&".@implode("&", $params);
    $tpl->SESSION_POST();
	$sock->getFrameWork($cmd);
	
	
}
function results_popup(){
	$tpl=new template_admin();
	$data=@file_get_contents(PROGRESS_DIR."/pdns.query");
    $data=htmlentities($data);
    $TITLE=null;
    $tz=array();
    $dataZ=explode("\n",$data);
    foreach ($dataZ as $line){

        if(preg_match("#ANSWER SECTION#",$line)) {
            $TITLE = "<hr><strong style='font-size:16px'>$line</strong><hr>";
            continue;
        }

        if(preg_match("#(.+?)\.\s+[0-9]+\s+[A-Z]+\s+[A-Z]+\s+([0-9\.]+)#",$line,$re)){
            $TITLE = "<hr><H2>{$re[1]} = {$re[2]}</h2>
            <div style='margin-top:-10px'><small>$line</small></div>
            <hr>";
        }

        if(preg_match("#DoH URL:(.+)#",$line,$re)){
            $line = "DoH URL: <code>{$re[1]}</code>";
        }
        if(preg_match("#HTTP Engine code: 0#",$line)){continue;}

        $tz[]=$line;
    }

    $data=nl2br(@implode("\n",$tz));

	echo $tpl->_ENGINE_parse_body("$TITLE<p class='alert alert-success m-b-sm'>$data</p>");
}
