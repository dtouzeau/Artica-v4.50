<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["PROXYPAC_SEARCH"])){$_SESSION["PROXYPAC_SEARCH"]="";}
//fad fa-scroll-old


    $html=$tpl->page_header("{APP_PROXY_PAC} {realtime_requests}",
        ico_eye,
    "{proxypac_explain}",
        "$page?table=yes",
        "proxypac-requests","progress-firehol-restart",true);


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_PROXY_PAC}",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));
    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}
    $EndPoint="/proxypac/queries/$ss/$MAX";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);
    $json=json_decode($data);

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $td1="style='width:1%' nowrap";


    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);
    $json=json_decode($data);
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>PID</th>
        	<th nowrap>{src}</th>
        	<th nowrap>{rule}</th>
        	<th nowrap>User-Agent</th>
        	<th>{size}</th>
        </tr>
  	</thead>
	<tbody>
";
	

    $RULES=array();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID,rulename FROM wpad_rules ORDER BY zorder");
    foreach ($results as $index=>$ligne){
        $RULES[$ligne["ID"]]=$ligne["rulename"];
    }



    foreach ($json->Logs as $line){

		$line=trim($line);
        if($line==null){continue;}
		if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:\s+(.+)#", $line,$re)) {
            echo "<H2>$line</H2>";
            continue;}
        $zdate = $re[1]." " .$re[2]. " ".$re[3];
        $pid = $re[4];
        $line = $re[5];
        $text=null;
        if(preg_match("#(error|failed|unable)#i",$line)){
            $text="text-danger";
        }
        if(preg_match("#(starting|success)#i",$line)){
            $text="text-info";
        }
        $line=str_replace("[SERVICE]","<strong>SERVICE</strong>",$line);


        $sr=explode("||",$line);
        $ipaddr=$sr[0];
        $RuleID=$sr[1];
        $RuleName=$sr[2];
        $Size=$sr[3];
        $unit="bytes";
        if($Size>1024){
            $unit="KB";
            $Size=round($Size/1024);
        }
        $UserAgent=$sr[4];
        if(trim($RuleName)==null){$RuleName="{failed} {unknown}";}

		$html[]="<tr>
				<td width=1% nowrap>$zdate</td>
				<td width=1% nowrap>$pid</td>
				<td width=1% nowrap>$ipaddr</td>
				<td>$RuleName</td>
				<td><small>$UserAgent</small></td>
				<td width=1% nowrap>$Size $unit</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/proxypac.syslog.query")."</i></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
