<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_POST["EnableClamavUnofficial"])){Save();exit;}


page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $ClamAVDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");

    $html=$tpl->page_header("{clamav_antivirus_databases}",
        ico_download,"{APP_CLAMAV} $ClamAVDaemonVersion","$page?table=yes","clamav-updates",
        "progress-firehol-restart",false,"table-loader-cache-level");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {clamav_antivirus_databases}",$html);
        echo $tpl->build_firewall();
        return;
    }



	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$host=new hosts($_GET["mac"]);
	$mac=urlencode($_GET["mac"]);
	$CallBackFunction=urlencode($_GET["CallBackFunction"]);
	$users=new usersMenus();
	
	
	$array["{clamav_antivirus_patterns_status}"]="$page?status=yes";
	$array["{update_parameters}"]="$page?settings=yes";
	$array["{schedules}"]="fw.system.tasks.php?sub-main=yes&ForceTaskType=81";
	echo $tpl->tabs_default($array);
}


function settings(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	
	$FreshClamCheckDay=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreshClamCheckDay"));
	$FreshClamMaxAttempts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreshClamMaxAttempts"));
	$EnableFreshClam=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreshClam"));
	if($FreshClamCheckDay==0){$FreshClamCheckDay=16;}
	if($FreshClamMaxAttempts==0){$FreshClamMaxAttempts=5;}
	for($i=1;$i<25;$i++){$FreshClamCheckDayZ[$i]="$i {times}";}
	$EnableClamavSecuriteInfo_disabled=false;
	$EnableClamavSecuriteInfo_text=null;
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$EnableClamavSecuriteInfo=0;
		$EnableClamavSecuriteInfo_disabled=true;
		$EnableClamavSecuriteInfo_text="&nbsp;<span class=alert-warning>&laquo;{license_error}&raquo;</span>";
	}
	
	$EnableClamavUnofficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavUnofficial"));
	$EnableClamavSigTool=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavSigTool"));
	$FreshClamCountry=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreshClamCountry"));
	$MalwarePatrolCode=$sock->GET_INFO("MalwarePatrolCode");
	$EnableClamavSecuriteInfo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavSecuriteInfo"));
	
	
	$form[]=$tpl->field_numeric("FreshClamMaxAttempts","{MaxAttempts} ({times})",$FreshClamMaxAttempts,"{MaxAttempts_text}");
	
	$form[]=$tpl->field_array_hash(countrycodes(), "FreshClamCountry", "{country}", $FreshClamCountry,false,"");
	$form[]=$tpl->field_array_hash($FreshClamCheckDayZ, "FreshClamCheckDay", "{check_times}", $FreshClamCheckDay,false,"{FreshClamCheckDay}");
	$form[]=$tpl->field_checkbox("EnableClamavSigTool","{use_sigtool}",$EnableClamavSigTool,false,"{use_sigtool_explain}");
	
	
	$form[]=$tpl->field_checkbox("EnableClamavSecuriteInfo","{securiteinfo_antivirus_databases}$EnableClamavSecuriteInfo_text",$EnableClamavSecuriteInfo,false,"{securite_info_explain}",$EnableClamavSecuriteInfo_disabled);
	$form[]=$tpl->field_checkbox("EnableClamavUnofficial","{clamav_unofficial}",$EnableClamavUnofficial,"MalwarePatrolCode","{clamav_unofficial_text}");
	$form[]=$tpl->field_text("MalwarePatrolCode", "{MalwarePatrol}", $MalwarePatrolCode,false,"{malwarepatrol_receipt_code}");

	$security="AsDansGuardianAdministrator";
	$jsCompile=null;
	
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		
		$html[]="<div class='alert alert-warning' style='margin-top:20px'><strong>{license_error} {securiteinfo_antivirus_databases}</strong><br>{securite_info_explain}</div>";
	}
	
	$html[]=$tpl->form_outside("{update_parameters}", @implode("\n", $form),"","{apply}",$jsCompile,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}


function countrycodes(){
	$f[]="ac";
	$f[]="ad";
	$f[]="ae";
	$f[]="af";
	$f[]="ag";
	$f[]="ai";
	$f[]="al";
	$f[]="am";
	$f[]="an";
	$f[]="ao";
	$f[]="aq";
	$f[]="ar";
	$f[]="as";
	$f[]="at";
	$f[]="au";
	$f[]="aw";
	$f[]="ax";
	$f[]="az";
	$f[]="ba";
	$f[]="bb";
	$f[]="bd";
	$f[]="be";
	$f[]="bf";
	$f[]="bg";
	$f[]="bh";
	$f[]="bi";
	$f[]="bj";
	$f[]="bl";
	$f[]="bm";
	$f[]="bn";
	$f[]="bo";
	$f[]="bq";
	$f[]="br";
	$f[]="bs";
	$f[]="bt";
	$f[]="bv";
	$f[]="bw";
	$f[]="by";
	$f[]="bz";
	$f[]="ca";
	$f[]="cc";
	$f[]="cd";
	$f[]="cf";
	$f[]="cg";
	$f[]="ch";
	$f[]="ci";
	$f[]="ck";
	$f[]="cl";
	$f[]="cm";
	$f[]="cn";
	$f[]="co";
	$f[]="cr";
	$f[]="cu";
	$f[]="cv";
	$f[]="cw";
	$f[]="cx";
	$f[]="cy";
	$f[]="cz";
	$f[]="de";
	$f[]="dj";
	$f[]="dk";
	$f[]="dm";
	$f[]="do";
	$f[]="dz";
	$f[]="ec";
	$f[]="ee";
	$f[]="eg";
	$f[]="eh";
	$f[]="er";
	$f[]="es";
	$f[]="et";
	$f[]="eu";
	$f[]="fi";
	$f[]="fj";
	$f[]="fk";
	$f[]="fm";
	$f[]="fo";
	$f[]="fr";
	$f[]="ga";
	$f[]="gb";
	$f[]="gd";
	$f[]="ge";
	$f[]="gf";
	$f[]="gg";
	$f[]="gh";
	$f[]="gi";
	$f[]="gl";
	$f[]="gm";
	$f[]="gn";
	$f[]="gp";
	$f[]="gq";
	$f[]="gr";
	$f[]="gs";
	$f[]="gt";
	$f[]="gu";
	$f[]="gw";
	$f[]="gy";
	$f[]="hk";
	$f[]="hm";
	$f[]="hn";
	$f[]="hr";
	$f[]="ht";
	$f[]="hu";
	$f[]="id";
	$f[]="ie";
	$f[]="il";
	$f[]="im";
	$f[]="in";
	$f[]="io";
	$f[]="iq";
	$f[]="ir";
	$f[]="is";
	$f[]="it";
	$f[]="je";
	$f[]="jm";
	$f[]="jo";
	$f[]="jp";
	$f[]="ke";
	$f[]="kg";
	$f[]="kh";
	$f[]="ki";
	$f[]="km";
	$f[]="kn";
	$f[]="kp";
	$f[]="kr";
	$f[]="kw";
	$f[]="ky";
	$f[]="kz";
	$f[]="la";
	$f[]="lb";
	$f[]="lc";
	$f[]="li";
	$f[]="lk";
	$f[]="lr";
	$f[]="ls";
	$f[]="lt";
	$f[]="lu";
	$f[]="lv";
	$f[]="ly";
	$f[]="ma";
	$f[]="mc";
	$f[]="md";
	$f[]="me";
	$f[]="mf";
	$f[]="mg";
	$f[]="mh";
	$f[]="mk";
	$f[]="ml";
	$f[]="mm";
	$f[]="mn";
	$f[]="mo";
	$f[]="mp";
	$f[]="mq";
	$f[]="mr";
	$f[]="ms";
	$f[]="mt";
	$f[]="mu";
	$f[]="mv";
	$f[]="mw";
	$f[]="mx";
	$f[]="my";
	$f[]="mz";
	$f[]="na";
	$f[]="nc";
	$f[]="ne";
	$f[]="nf";
	$f[]="ng";
	$f[]="ni";
	$f[]="nl";
	$f[]="no";
	$f[]="np";
	$f[]="nr";
	$f[]="nu";
	$f[]="nz";
	$f[]="om";
	$f[]="pa";
	$f[]="pe";
	$f[]="pf";
	$f[]="pg";
	$f[]="ph";
	$f[]="pk";
	$f[]="pl";
	$f[]="pm";
	$f[]="pn";
	$f[]="pr";
	$f[]="ps";
	$f[]="pt";
	$f[]="pw";
	$f[]="py";
	$f[]="qa";
	$f[]="re";
	$f[]="ro";
	$f[]="rs";
	$f[]="ru";
	$f[]="rw";
	$f[]="sa";
	$f[]="sb";
	$f[]="sc";
	$f[]="sd";
	$f[]="se";
	$f[]="sg";
	$f[]="sh";
	$f[]="si";
	$f[]="sj";
	$f[]="sk";
	$f[]="sl";
	$f[]="sm";
	$f[]="sn";
	$f[]="so";
	$f[]="sr";
	$f[]="ss";
	$f[]="st";
	$f[]="su";
	$f[]="sv";
	$f[]="sx";
	$f[]="sy";
	$f[]="sz";
	$f[]="tc";
	$f[]="td";
	$f[]="tf";
	$f[]="tg";
	$f[]="th";
	$f[]="tj";
	$f[]="tk";
	$f[]="tl";
	$f[]="tm";
	$f[]="tn";
	$f[]="to";
	$f[]="tp";
	$f[]="tr";
	$f[]="tt";
	$f[]="tv";
	$f[]="tw";
	$f[]="tz";
	$f[]="ua";
	$f[]="ug";
	$f[]="uk";
	$f[]="um";
	$f[]="us";
	$f[]="uy";
	$f[]="uz";
	$f[]="va";
	$f[]="vc";
	$f[]="ve";
	$f[]="vg";
	$f[]="vi";
	$f[]="vn";
	$f[]="vu";
	$f[]="wf";
	$f[]="ws";
	$f[]="ye";
	$f[]="yt";
	$f[]="za";
	$f[]="zm";
	$f[]="zw";
	$country[null]="{none}";
	foreach ($f as $line){
		$country[$line]=$line;
	}

	return $country;
}





function Save(){
	$reconfigure_squid=false;
	$sock=new sockets();
	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$_POST["EnableClamavSecuriteInfo"]=0;}

    foreach ($_POST as $num=>$line){
		$line=url_decode_special_tool($line);
		$sock->SET_INFO($num, $line);
	}
	$sock->REST_API("/freshclam/reconfigure");

	
	NotifyServers();
}

function status(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
    $q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
	$signature=$tpl->_ENGINE_parse_body("{signature}");
	$zdate=$tpl->_ENGINE_parse_body("{date}");
	$version=$tpl->_ENGINE_parse_body("{version}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	
	$refresh=$tpl->_ENGINE_parse_body("{refresh_databases_information}");
	$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));
	$yest=strtotime ( '-3 day');
	$yestt=date("Y-m-d",$yest);
	
	$SECUINFO["securiteinfo.hdb"]=true;
	$SECUINFO["securiteinfo.ign2"]=true;
	$SECUINFO["javascript.ndb"]=true;
	$SECUINFO["securiteinfohtml.hdb"]=true;
	$SECUINFO["spam_marketing.ndb"]=true;
	$SECUINFO["securiteinfoandroid.hdb"]=true;
	$SECUINFO["securiteinfoascii.hdb"]=true;

    $jsrestart=$tpl->framework_buildjs("/clamd/sigtooldb",
    "clamav.status.db.progress","clamav.status.db.log","progress-clamav-resfresh","LoadAjaxSilent('MainContent','$page');"
    );

    $jsrestart2=$tpl->framework_buildjs(
        "/freshclam/run",
        "clamav.update.progress",
        "clamav.update.progress.txt",
        "progress-clamav-resfresh","LoadAjaxSilent('MainContent','$page');"

    );


	
	
	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>
			<label class=\"btn btn btn-primary\" 
			OnClick=\"$jsrestart;\">
			<i class='fas fa-sync-alt'></i> $refresh </label>
			
			<label class=\"btn btn btn-warning\" 
			OnClick=\"$jsrestart2;\">
			<i class='far fa-cloud-download-alt'></i> {update_antivirus_patterns} </label>			
			
			</div>
			
			
			
			<div id='progress-clamav-resfresh'></div>
			
			");

	    if(!is_array($bases)){$bases=array();}
        $results=$q->QUERY_SQL("SELECT * FROM pattern_status");
	    foreach ($results as $index=>$ligne){
            $dbname=$ligne["dbname"];
            $version=$ligne["version"];
            $signatures=$ligne["signatures"];
            $patterndate=$ligne["patterndate"];
            $bases[$dbname]["zDate"]=$patterndate;
            $bases[$dbname]["signatures"]=$signatures;
            $bases[$dbname]["version"]=$version;
        }



	if(count($bases)==0){
		$html[]="<div class='alert alert-danger'>{missing_clamav_pattern_databases}</div>";
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
		return;
	}
	foreach ($SECUINFO as $db=>$MAIN){
		if(!isset($bases[$db])){
			$bases[$db]["zDate"]="-";
			$bases[$db]["signatures"]="0";
			$bases[$db]["LICENSE"]=true;
			$bases[$db]["TYPE"]="Extended";
		}else{
			$bases[$db]["LICENSE"]=true;
			$bases[$db]["TYPE"]="Extended";
		}
	}
	
	
	$bases["daily.cvd"]["TYPE"]="{official}";
	$bases["bytecode.cvd"]["TYPE"]="{official}";
	$bases["main.cvd"]["TYPE"]="{official}";
	$bases["whitelist.ign2"]["TYPE"]="{whitelist}";
	$bases["main.cld"]["TYPE"]="{official}";
	
	
	
	$bases["winnow_extended_malware.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["spear.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["winnow.attachments.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["scam.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["bofhland_phishing_URL.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["spamattach.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["porcupine.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["bofhland_malware_attach.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["winnow_bad_cw.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["jurlbla.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["rfxn.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["phish.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["spamimg.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["crdfam.clamav.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["sigwhitelist.ign2"]["TYPE"]="{whitelist}";
	$bases["rogue.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["lott.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["badmacro.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["rfxn.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["jurlbl.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["winnow_malware.hdb"]["TYPE"]="{clamav_unofficial}";
	$bases["bofhland_cracked_URL.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["spearl.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["bofhland_malware_URL.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["winnow_malware_links.ndb"]["TYPE"]="{clamav_unofficial}";
	
	$bases["junk.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["blurl.ndb"]["TYPE"]="{clamav_unofficial}";
	$bases["phishtank.ndb"]["TYPE"]="{clamav_unofficial}";
	
	$html[]=$tpl->_ENGINE_parse_body("
	<table id='table-my-computers' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$signature</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zdate</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$type</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{version}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$items</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$users=new usersMenus();

	
	$TRCLASS=null;
	foreach ($bases as $db=>$MAIN){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$color="black";
		$text_class=null;
		$diff=null;
		$tier=0;
		if(!isset($MAIN["signatures"])){$MAIN["signatures"]=0;}
        if(!isset($MAIN["zDate"])){$MAIN["zDate"]=null;}
		if($MAIN["zDate"]==null){$MAIN["zDate"]=$tpl->icon_nothing();$tier=1;}
		if(!isset($MAIN["version"])){$MAIN["version"]=$tpl->icon_nothing();$tier=1;}
		
		if($tier==0){
			$diff="({since} ".distanceOfTimeInWords(strtotime($MAIN["zDate"]),time()).")";
			$xtime=strtotime($MAIN["zDate"]);
			$MAIN["zDate"]=str_replace(date("Y-m-d"), "{today}", $MAIN["zDate"]);
			$MAIN["zDate"]=str_replace($yestt, "{yesterday}", $MAIN["zDate"]);
			$mustWarningTime=strtotime("-15 day");
			$mustRedTime=strtotime("-60 day");
			$mustminmaltime=strtotime(date("Y")."-01-01 00:00:00");
			if($xtime>$mustminmaltime){
				if($xtime<$mustWarningTime){$text_class="alert-warning";}
				if($xtime<$mustRedTime){$text_class="alert-danger";}
			}
		}

		
		if($MAIN["signatures"]>0){
			$signatures=$tpl->FormatNumber($MAIN["signatures"]);
		}else{
			$signatures=$tpl->icon_nothing();
		}
		
		if(isset($MAIN["LICENSE"])){
			if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
				$text_class="alert-warning";
				$MAIN["zDate"]="{license_error}";
			}
		}

		
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\">$db</td>";
		$html[]="<td class=\"$text_class\">{$MAIN["zDate"]} $diff</td>";
		$html[]="<td class=\"$text_class\">{$MAIN["TYPE"]}</td>";
		$html[]="<td class=\"$text_class\">{$MAIN["version"]}</td>";
		$html[]="<td class=\"$text_class\">$signatures</td>";
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
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function NotifyServers(){
	$sock=new sockets();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->STATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
	}

}