<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["PROXYPAC_SEARCH"])){$_SESSION["PROXYPAC_SEARCH"]="";}
//fad fa-scroll-old


    $html=$tpl->page_header("{APP_PROXY_PAC} {events}",
        ico_eye,
    "{proxypac_explain}",
        "$page?table=yes",
        "proxypac-events","progress-firehol-restart",true);


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_PROXY_PAC}",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$tpl=new template_admin();
    $users=new usersMenus();
    if(!isReader($users)){
        return "";
    }
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));
    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}
    $EndPoint="/proxypac/events/$ss/$MAX";

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    // json_decode() rend NULL quand le daemon ne repond pas: lire ->Status
    // dessus est une erreur fatale en PHP 8.
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

	$html[]="
<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>
<table class='awc-tbl awc-tbl-compact'>
	<thead>
    	<tr>
        	<th class='center'>{date}</th>
        	<th class='center'>PID</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody id='pace-rows'>
";
	

    $RULES=array();
    $rows = acls_api_get("/proxypac/wpad-rules") ?? [];
    foreach ($rows as $ligne){
        $RULES[intval($ligne["id"] ?? 0)]=$ligne["ruleName"] ?? "";
    }


    $_SESSION["PROXYPAC_SEARCH"]=$_GET["search"];


    foreach ((isset($json->Logs) && is_array($json->Logs))?$json->Logs:array() as $line){

		$line=trim($line);
        if($line==null){continue;}
		if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:\s+(.+)#", $line,$re)) {continue;}
        $zdate = $re[1]." " .$re[2]. " ".$re[3];
        $pid = $re[4];
        // Pentest 2026-11-04 : ligne du journal (donnee externe) affiche sans encodage (XSS stockee).
        $line = req_html($re[5]);
        $text=null;
        if(preg_match("#(error|failed|unable)#i",$line)){
            $text="text-danger";
        }
        if(preg_match("#(starting|success)#i",$line)){
            $text="text-info";
        }
        $line=str_replace("[SERVICE]","<strong>SERVICE</strong>",$line);

        $line="<span class='$text'>$line</span>";

		$html[]="<tr>
				<td style='width:1%' nowrap>$zdate</td>
				<td style='width:1%' nowrap>$pid</td>
				<td>$line</td>
				</tr>";
		
	}


    $jsrestart=$tpl->framework_buildjs("/proxypac/reconfigure",
        "autoconfiguration.apply.progress",
        "/autoconfiguration.apply.log",
        "progress-firehol-restart",
        "{$_GET["function"]}()"
    );

    $jsSimul="Loadjs('fw.proxypac.simul.php')";
	
	$html[]="</tbody></table></div></div></div>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/proxypac.syslog.query")."</i></div>";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$jsSimul\"><i class='fas fa-vial'></i> {test_your_rules} </label>";
    $btns[]="</div>";



    $TINY_ARRAY["TITLE"]="{APP_PROXY_PAC} {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{proxypac_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $html[]= "<script>";
    $html[]= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]= "</script>";


	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js(100,"pace-rows","pace-pager");
	
	
	
}
function isReader($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }
    if($users->AsProxyMonitor){
        return true;
    }
    return false;
}
function isWritter($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }

    return false;
}