<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["SnortRulesCode"])){Save_gen();exit;}
if(isset($_GET["fail2ban-status"])){fail2ban_status();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["pf-ring-infos"])){pf_ring_infos();exit;}
if(isset($_GET["pf-ring-popup"])){pf_ring_popup();exit;}
if(isset($_GET["reconfigure-js"])){reconfigure_js();exit;}
if(isset($_GET["restart-js"])){restart_js();exit;}
if(isset($_GET["reconfigure-js"])){reconfigure_js();exit;}
if(isset($_GET["reconfigure-popup"])){reconfigure_popup();exit;}
if(isset($_GET["restart-popup"])){restart_popup();exit;}
page();




function restart_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsFirewallManager){$tpl->popup_no_privs();}
	$tpl->js_dialog6("{restart_service}", "$page?restart-popup=yes");

}


function reconfigure_array(){
    $page=CurrentPageName();
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fail2ban.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/fail2ban.restart.progress.txt";
	$ARRAY["CMD"]="fail2ban.php?reload=yes";
	$ARRAY["TITLE"]="{reconfigure_service} {APP_FAIL2BAN}";
	$ARRAY["AFTER"]="LoadAjax('table-fail2ban-dashboard','$page?main=yes');";
	$prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-fail2ban-restart')";

	
}
function restart_array(){
    $page=CurrentPageName();
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fail2ban.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/fail2ban.restart.progress.txt";
	$ARRAY["CMD"]="fail2ban.php?restart=yes";
	$ARRAY["TITLE"]="{restart_service} {APP_FAIL2BAN}";
    $ARRAY["AFTER"]="LoadAjax('table-fail2ban-dashboard','$page?main=yes');";
	$prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-fail2ban-restart')";

}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_FAIL2BAN}</h1></div>
	<p>{APP_FAIL2BAN_EXPLAIN}</p></div>
	</div>



	<div class='row'><div id='progress-fail2ban-restart'></div>
	<div class='ibox-content'>

	<div id='table-fail2ban-dashboard'></div>

	</div>
	</div>
    <script>
	$.address.state('/');
	$.address.value('/fail2ban-index');
	LoadAjax('table-fail2ban-dashboard','$page?main=yes');

	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }


	echo $tpl->_ENGINE_parse_body($html);

}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	$sock=new sockets();
	$sock->getFrameWork("fail2ban.php?client-status=yes");
	$JAIL_NUMBER=0;
	$ftext=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/fail2ban.client.status"));
	foreach ($ftext as $line){
		
		if(preg_match("#Number of jail:\s+([0-9]+)#i",$line,$re)){
			$JAIL_NUMBER=$re[1];
			continue;
		}
		
		if(preg_match("#Jail list:\s+(.*)#",$line,$re)){
			$JAIL_LIST=$re[1];
			continue;
		}
	}
	
	
	
	$COUNT_OF_FAIL2BAN=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/COUNT_OF_FAIL2BAN"));
	$COUNT_OF_FAIL2BAN=FormatNumber($COUNT_OF_FAIL2BAN);
	
	$COUNT_OF_FAIL2BAN_SRC=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/COUNT_OF_FAIL2BAN_SRC"));
	$COUNT_OF_FAIL2BAN_SRC=FormatNumber($COUNT_OF_FAIL2BAN_SRC);
	
	$html[]="<table style='width:100%;margin-top:15px'>";
	$html[]="<tr>";
	$html[]="<td style='width:240px'><div id='fail2ban-status'></div></td>";
	$html[]="<td style='width:99%;padding-left:10px;'>";
	$html[]="<div style='margin-top:-27px'>";
	
	if($JAIL_NUMBER==0){$JAIL_NUMBER_BCK="red";}else{$JAIL_NUMBER_BCK="green";}
	if($COUNT_OF_FAIL2BAN==0){$COUNT_OF_FAIL2BAN_BCK="green";}else{$COUNT_OF_FAIL2BAN_BCK="yellow";}
	//fas fa-list-ul
	//	
		
	$html[]=$tpl->widget_h($JAIL_NUMBER_BCK,"far fa-sitemap",$JAIL_NUMBER,"{engine_filters}: $JAIL_LIST");
	
	$html[]=$tpl->widget_h($COUNT_OF_FAIL2BAN_BCK,"fas fa-list-ul",$COUNT_OF_FAIL2BAN,"{ids_events}");
	
	$html[]=$tpl->widget_h("lazur","fas fa-user-shield",$COUNT_OF_FAIL2BAN_SRC,"{src_ips}");



    $html[]="</div>";
    $APP_FAIL2BAN_TO_CROWDSEC=$tpl->_ENGINE_parse_body("{APP_FAIL2BAN_TO_CROWDSEC}");
    $APP_FAIL2BAN_TO_CROWDSEC=str_replace("%s","<strong><a href=\"https://wiki.articatech.com/network/firewall/Behavior-detection-engine-CrowdSec\" target=_new>CrowdSec: Wiki</a></strong>",$APP_FAIL2BAN_TO_CROWDSEC);
    $html[]=$tpl->div_warning("{APP_FAIL2BAN_TO_CROWDSEC}");

	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>";
	$html[]="LoadAjaxSilent('fail2ban-status','$page?fail2ban-status=yes');";
	$html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function fail2ban_status(){
	$tpl=new template_admin();
	$sock=new sockets();
	$users=new usersMenus();
	$ERR=null;
	$sock->getFrameWork('fail2ban.php?status=yes');
	$js=reconfigure_array();
	$srestart=restart_array();
	$ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/fail2ban.status");
	echo $tpl->SERVICE_STATUS($ini, "APP_FAIL2BAN",$srestart);

    if($users->AsFirewallManager) {
        echo $tpl->_ENGINE_parse_body("<button type=\"button\" class=\"btn btn-primary\"
	style='width:335px;margin-top:15px'
	OnClick=\"$js\">{reconfigure_service}</button>");
    }
	
	
	
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}