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
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    if(!isset($_SESSION["STRONGSWAN_SEARCH2"])){$_SESSION["STRONGSWAN_SEARCH2"]="today max 500 everything";}
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{APP_STRONGSWAN} ({connections})</h1><p>$btn</p></div>
	</div>
	<div class=\"row\">
	<div class='ibox-content'>
	<div class=\"input-group\">
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["STRONGSWAN_SEARCH2"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	<span class=\"input-group-btn\">
	<button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
	</span>
	</div>
	</div>
	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/ipsec-connections');	
function Search$t(e){
	if(!checkEnter(e) ){return;}
	ss$t();
}

function ss$t(){
	var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
	LoadAjax('table-loader','$page?search='+ss);
}

function Start$t(){
	var ss=document.getElementById('search-this-$t').value;
	ss$t();
}
Start$t();
</script>";

    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Connections',$html);echo $tpl->build_firewall();return;}
echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$vpn=new strongswan();
	$nic=new networking();
	$sock=new sockets();
	$page=CurrentPageName();
	
	$date=$tpl->javascript_parse_text("{connection_date}");

	$userid=$tpl->javascript_parse_text("{member}");
	$ipaddr=$tpl->javascript_parse_text("{remote_ip_address}");
    $vips=$tpl->javascript_parse_text("{local_ip_address}");

	$events=$tpl->javascript_parse_text("{events}");


	$html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$vips}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$userid}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$events}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$_SESSION["STRONGSWAN_SEARCH2"]=$_GET["search"];
	
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
	
	$q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");

	
	$count=$q->mysqli_fetch_array("SELECT COUNT(*) as count FROM strongswan_cnx");
    if(strlen($search["Q"])<2){
        $search["Q"]="";

    }

	$sql="SELECT * FROM strongswan_cnx {$search["Q"]} ORDER BY id DESC LIMIT {$search["MAX"]}";

	$results=$q->QUERY_SQL($sql);
	$Items=$count['count'];
	
	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";}
	
	
    $array["host-up"]="fa-link";
    $array["host-ipv6-up"]="fa-link";
    $array["host-down"]="fa-unlink";
    $array["host-ipv6-down"]="fa-unlink";
	
    $array["client-up"]="fa-link";;
    $array["client-ipv6-up"]="fa-link";
    $array["client-down"]="fa-unlink";
    $array["client-ipv6-down"]="fa-unlink";


	
	$tpl2=new templates();
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ztime=strtotime($ligne["zdate"]);

    $date=$tpl2->time_to_date($ztime,true);
		$md=md5(serialize($ligne));
		$fuser="fa-user";
		if($ligne["member"]==null){$ligne["member"]="&nbsp;-&nbsp;";$fuser="fa-user-o";}
		if(!isset($arrayT[$ligne["action"]])){$arrayT[$ligne["action"]]=$ligne["action"];}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td nowrap><span class='fa fa-clock'> </span>&nbsp;{$date}</td>";
        $html[]="<td nowrap><span class='fa fa-desktop' ></span>&nbsp;{$ligne["ipaddr_local"]}</td>";
        $html[]="<td nowrap><span class='fa fa-desktop' ></span>&nbsp;{$ligne["ipaddr_vip"]}</td>";
		$html[]="<td nowrap><span class='fa $fuser' ></span>&nbsp;{$ligne["member"]}</td>";
		$html[]="<td style=font-weight:bold'><span class='fa {$array[$ligne["action"]]}' ></span>&nbsp;{$ligne["action"]}</td>";

		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
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
	\"enabled\": true
	},
	\"sorting\": {
	\"enabled\": true
	}
	
	}
	
	
	); });
	

	</script>";
	
	echo @implode("\n", $html);	
}

