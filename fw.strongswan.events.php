<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}

page();




function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["STRONGSWAN_SEARCH"])){$_SESSION["STRONGSWAN_SEARCH"]="today max 500 everything";}
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{APP_STRONGSWAN} ({events})</h1><p>$btn</p></div>
	</div>
	<div class=\"row\">
	<div class='ibox-content'>
	<div class=\"input-group\">
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["STRONGSWAN_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	$.address.value('/ipsec-events');	
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
	if(ss.length >0){ss$t();}
}
Start$t();
</script>";
	if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Events',$html);echo $tpl->build_firewall();return;}
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
	$deamon=$tpl->javascript_parse_text("{daemon}");;
	$events=$tpl->javascript_parse_text("{events}");
	

	$html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$deamon}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$events}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$_SESSION["STRONGSWAN_SEARCH"]=$_GET["search"];
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	foreach ($MAIN as $val=>$key){
		$end=$end." $val=$key,";
	}
	reset($MAIN);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("strongswan.php?syslog=$line");
	$data=explode("\n",@file_get_contents(PROGRESS_DIR."/charon.syslog"));
	
	
	ksort($data);
	$tpl2=new templates();
	foreach ($data as $line){	
		$line=trim($line);
		if($line==null){continue;}
		$line=str_replace("RwFri","",$line);
		if(!preg_match("#([a-zA-Z]+)\s+([a-zA-Z]+)\s+([0-9]+)\s+([0-9:]+)\s+([0-9]+)\s+us=[0-9]+\s+(.+)#", $line,$re)){
			if(preg_match("#([a-zA-Z]+)\s+([a-zA-Z]+)\s+([0-9]+)\s+([0-9:]+)\s+([0-9]+)\s+(.+)#", $line,$re)){
				
			}else{continue;}
		}
		$md=md5(serialize($line));
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zDate="{$re[1]} {$re[2]} {$re[3]} {$re[4]} {$re[5]}";
		$ztime=strtotime($zDate);
		$line=$re[6];
		$color=null;
	
		$date=$tpl2->time_to_date($ztime,true);
        
        
        //STRONSWAN

        $msg = explode(' ', $line, 2);

		if (strpos($msg[1], 'failed') !== false) {
			$color=" text-danger";
		}

		if (strpos($msg[1], 'fatal') !== false) {
			$color=" text-danger";
		}

		if (strpos($msg[1], 'error') !== false) {
			$color=" text-danger";
		}

		$html[]="<tr class='$TRCLASS$color' id='$md'>";
		$html[]="<td nowrap><span class='fa fa-clock'> </span>&nbsp;{$date}</td>";
		$html[]="<td nowrap><span class='fas fa-cogs' ></span>&nbsp;{$msg[0]}</td>";
		$html[]="<td>{$msg[1]}</td>";

		$html[]="</tr>";
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
	$html[]="<div>$end</div>
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
	

	</script>";
	
	echo @implode("\n", $html);	
}

