<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["CICAPSRV_SEARCH"]==null){$_SESSION["CICAPSRV_SEARCH"]="limit 200";}

    $html=$tpl->page_header("{SERVICE_WEBAVEX} {events}",ico_eye,"{ACTIVATE_ICAP_AV_TEXT}",null,"cicap-events",null,true);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{APP_C_ICAP}");
        return;
    }
	
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$zdate=$tpl->_ENGINE_parse_body("{date}");
	$title=$tpl->_ENGINE_parse_body("{IDS} {events}");
	$events=$tpl->javascript_parse_text("{events}");
	$sock=new sockets();
	$target_file=PROGRESS_DIR."/c-icap.log";
	
	$js="OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
	if($_GET["eth"]==null){$class=" active";}
	
	$t=time();
	

	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-firewall-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	
	$_SESSION["CICAPSRV_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]=".";}
	$ss=base64_encode($search["S"]);



	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zdate</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";
	
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);


	
	
	
	$TRCLASS=null;

    $EndPoint="/cicap/events/$ss";
    $data=$sock->REST_API($EndPoint);

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    foreach ($json->Logs as $line){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		if(trim($line)==null){continue;}
		if($GLOBALS['VERBOSE']){echo "FOUND $line\n";}
        $srcline=$line;


        list($datetext,$pid,$line)=ParseRows($srcline);
        $line=str_replace("#012","",$line);
        $line=str_replace("#011","",$line);

		if(preg_match("#(does not exist|Error while|Can not|VIRUS DETECTED)#i", $line)){
			$text_class="font-bold text-danger";
		}
		
		if(preg_match("#(Setting antivirus default engine|success)#i", $line)){
			$text_class="text-info";
		}
		if(preg_match("#(erm signal received|Reloading|Restarting)#i", $line)){
			$text_class="font-bold text-warning";
		}
		
		
		
		
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" width='1%' nowrap=''>$datetext</td>";
		$html[]="<td class=\"$text_class\" width='99%'>$line</a></td>";
		$html[]="</tr>";
		

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table><div><i></i></div>";
    $zlines=count($json->Logs);
    $TINY_ARRAY["TITLE"]="{SERVICE_WEBAVEX} {events} &laquo;{$search["S"]}&raquo; $zlines {results}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{ACTIVATE_ICAP_AV_TEXT}";
    $TINY_ARRAY["URL"]="cicap-events";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	$jstiny
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    </script>";

			echo @implode("\n", $html);

}
function ParseRows($line){
    $tpl=new template_admin();
    $months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");

    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]: (.+)#",trim($line),$re)){
        $month=$months[$re[1]];
        $day=$re[2];
        $time=$re[3];
        $date=strtotime(date("Y")."-$month-$day $time");
        $pid=$re[4];
        $line=$re[5];
        $sDate=$tpl->time_to_date($date,true);
        return array($sDate,$pid,$line);
    }

    if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?c-icap:\s+([0-9]+)\/[0-9]+, (.+)#",trim($line),$re)){
        $month=$months[$re[1]];
        $day=$re[2];
        $time=$re[3];
        $date=strtotime(date("Y")."-$month-$day $time");
        $pid=$re[4];
        $line=$re[5];
        $sDate=$tpl->time_to_date($date,true);
        return array($sDate,$pid,$line);
    }
}
