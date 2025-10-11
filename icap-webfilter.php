<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.ActiveDirectory.inc');
include_once(dirname(__FILE__).'/ressources/class.external.ldap.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<script>alert('$alert');</script>";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

tabs();


function tabs(){
	$sock=new sockets();
	$tpl=new templates();
	
	$CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
	
	if($CicapEnabled==0){
		
		$sock->REST_API("/clamd/sigtool");
		$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));
		if(count($bases)<2){
		
			echo FATAL_ERROR_SHOW_128("<span style='font-size:48px'>{missing_clamav_pattern_databases}</span>
				<center style='margin:50px'>
					". button("{update_now}", "Loadjs('clamav.update.progress.php')",52).
					"</center>
		
				");
			return;
		}
		
		$html="
		<div style='font-size:60px;margin-bottom:40px'>{http_antivirus_for_proxy}</div>		
		<center style='margin:50px;width:90%;padding:30px' class=form>". 
				button("{ACTIVATE_ICAP_AV}", "Loadjs('c-icap.enable.progress.php')",40).
				"<center style='margin-top:20px;font-size:22px'>{ACTIVATE_ICAP_AV_TEXT}</center>
		</center>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	
	$array["status"]='{status}';
	//$array["rules"]='{webfiltering}';
	$array["daemons"]='{daemon_settings}';
	$array["clamav"]='ClamAV Antivirus';
	$array["realtime"]="{realtime_requests}";
	$array["events"]='{daemon} {events}';
	
	$fontsize="22";
		
	foreach ($array as $num=>$ligne){
		
		if($num=="rules"){
			$html[]= "<li><a href=\"c-icap.rules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		if($num=="events"){
			$html[]= "<li><a href=\"c-icap.events.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="realtime"){
			$html[]= "<li><a href=\"cicap.access.log.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"c-icap.index.php?main=$num&t=$t\">
				 <span style='font-size:{$fontsize}px;'>$ligne</span></a></li>\n");
	}



	$html=build_artica_tabs($html,'main_icapwebfilter_tabs',1490)."<script>LeftDesign('webfiltering-white-256-opac20.png');</script>";
	
	echo $html;

}