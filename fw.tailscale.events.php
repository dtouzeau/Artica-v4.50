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
    $users  = new usersMenus();
    if(!$users->AsVPNManager){return "";}
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["TAILSCALE_SEARCH"])){$_SESSION["TAILSCALE_SEARCH"]="today max 500";}

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_TAILSCALE} ({events})</h1></div>
	</div>
	<div class=\"row\">
	<div class='ibox-content'>
	<div class=\"input-group\">
	<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["TAILSCALE_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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


echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$tpl    = new template_admin();
	$sock   = new sockets();
	$page   = CurrentPageName();
	$t      = time();
	
	$date=$tpl->javascript_parse_text("{date}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$events=$tpl->javascript_parse_text("{events}");
	

	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{$date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$events}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$_SESSION["TAILSCALE_SEARCH"]=$_GET["search"];
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	reset($MAIN);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("tailscale.php?syslog=$line");
	$srcfile=PROGRESS_DIR."/tailscale.syslog";
	$data=explode("\n",@file_get_contents($srcfile));
    krsort($data);


	foreach ($data as $line){
		$line=trim($line);
		if($line==null){continue;}

		if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?tailscale\[([0-9]+)\]:(.*)#",$line,$re)){continue;}

		$Month=$re[1];
		$day=$re[2];
		$time=$re[3];
		$pid=$re[4];
        $event=$re[5];
        $font=null;

        if(preg_match("#_out\(\)\{([0-9]+)\}#",$event,$re)){
            $event=trim(str_replace("_out(){{$re[1]}}","",$event));
        }
		if(preg_match("#^([0-9\/]+)\s+([0-9:]+)\s+#",$event,$re)){
            $event=trim(str_replace("$re[1] $re[2]","",$event));
            $sdate=strtotime("$re[1] $re[2]");
            $Month=date("M",$sdate);
            $day=date("d",$sdate);
            $time=$re[2];
        }
		if(preg_match("#(error|failed|unable)#",$event)){
            $font=" class='text-danger'";
        }
		if(preg_match("#(Success|: ok)#",$event)){
            $font=" class='text-success'";
        }


		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td nowrap style='width:1%' $font nowrap><span class='fa fa-clock'> </span>&nbsp;$Month $day $time</td>";
		$html[]="<td $font nowrap>$event</td>";
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

