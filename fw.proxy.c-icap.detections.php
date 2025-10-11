<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["delete-js"])){delete_rule_js();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_REQUEST["GenerateProgress"])){GenerateProgress();exit;}
if(isset($_POST["connection_name"])){buildconfig();exit;}
page();




function page(){

	$tpl=new template_admin();


    $html=$tpl->page_header("{antivirus_threats}",
        "fa-solid fa-viruses","{antivirus_threats_explain}",null,
        "icap-viruses","progress-firehol-restart",true);


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {antivirus_threats}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);




}

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$vpn=new openvpn();
	$nic=new networking();
	$sock=new sockets();
	$page=CurrentPageName();
	
	$date=$tpl->javascript_parse_text("{connection_date}");
	$events=$tpl->javascript_parse_text("{events}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$Items_text=$tpl->_ENGINE_parse_body("{items}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	//INSERT INTO webfilter (zDate,website,category,rulename,public_ip,blocktype,why,hostname,client,PROXYNAME,rqs)
	
	
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$events}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$hostname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$website}</th>";
	
	
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$_SESSION["AVTHREATS_SEARCH2"]=$_GET["search"];
	$aliases["src_ip"]="hostname";
	$aliases["ipaddr"]="hostname";
	$aliases["website"]="website";

	if($_GET["search"]<>null) {
        $search = $tpl->query_pattern(trim(strtolower($_GET["search"])), $aliases);
        VERBOSE("{$_GET["search"]} = '{$search["Q"]}'");
        if($search["Q"]<>null){
            $pattern=$search["Q"];
            $search["Q"]="WHERE ( (hostname ILIKE '$pattern') OR ( website ILIKE '$pattern') OR (category ILIKE '$pattern') OR (why ILIKE '$pattern') )";
        }
    }



	$q=new postgres_sql();

	
	
	if(intval($search["MAX"])==0){$search["MAX"]=250;}
	$sql="SELECT zDate,website,client,category,hostname,why
	FROM webfilter {$search["Q"]} ORDER BY zdate DESC LIMIT {$search["MAX"]}";
	$results=$q->QUERY_SQL($sql);
	$Items=pg_num_rows($results);
	
	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";}

	
	$tpl2=new templates();
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$date=$tpl2->time_to_date(strtotime($ligne["zdate"]),true);
		$md=md5(serialize($ligne));
		$fuser="fa-user";
		$why=$ligne["why"];
		if($ligne["client"]==null){
			if($ligne["hostname"]<>null){$ligne["client"]=$ligne["hostname"];}
		}
			
			if($ligne["client"]==null){$ligne["client"]="&nbsp;-&nbsp;";$fuser="fa-user-o";}
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td nowrap><span class='fa fa-clock'> </span>&nbsp;{$date}</td>";
		$html[]="<td nowrap><span class='fa fa-bug' ></span>&nbsp;$why&nbsp;&nbsp;-&nbsp;&nbsp;{$ligne["category"]}</td>";
		$html[]="<td nowrap><span class='fa fa-desktop' ></span>&nbsp;{$ligne["hostname"]}</td>";
		$html[]="<td nowrap><span class='fa fa-cloud' ></span>&nbsp;{$ligne["website"]}</td>";
	

		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<div><i>$Items $Items_text &laquo;$sql&raquo;</i></div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable(
	{
	\"filtering\": {
	\"enabled\": false
	},
	\"sorting\": {
	\"enabled\": true
	}
	
	}
	
	
	); });
	
function CreateNewVPNClient(){
	Loadjs('$page?ruleid-js=');
}
var xxXGenerateVPNConfig= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){ Loadjs(tempvalue); }
	
}

function XGenerateVPNConfig(uid){
	var XHR = new XHRConnection();
	XHR.appendData('GenerateProgress',uid);
	XHR.sendAndLoad('$page', 'POST',xxXGenerateVPNConfig);		
}	

	</script>";
	
	echo @implode("\n", $html);	
}
function findlocalIP($ROUTING,$ipaddr){
	if(isset($GLOBALS["findlocalIP"][$ipaddr])){return $GLOBALS["findlocalIP"][$ipaddr];}
	while (list ($index, $array) = each ($ROUTING) ){
		$RealAddress=$array["RealAddress"];
		if($RealAddress==$ipaddr){
			$GLOBALS["findlocalIP"][$ipaddr]=$array["VirtualAddress"];
			return $GLOBALS["findlocalIP"][$ipaddr];}

	}
	$GLOBALS["findlocalIP"][$ipaddr]=null;

}
