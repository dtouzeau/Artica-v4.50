<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsDatabaseAdministrator){exit();}
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

    $html=$tpl->page_header("{APP_OPENLDAP} {events}",ico_eye,"",
        "$page?table=yes","ldap-events","progress-openldap-restart",true,"table-loader-openldap-service");


    if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["OPENLDAP_SEARCH"]==null){$_SESSION["OPENLDAP_SEARCH"]="limit 200";}

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_OPENLDAP} {events}",$html);
        echo $tpl->build_firewall();
        return;
    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$eth_sql=null;
	$token=null;
	$class=null;
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$zdate=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->javascript_parse_text("{events}");
    $target_file=PROGRESS_DIR."/slapd.log";



	if($_GET["eth"]==null){$class=" active";}
	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	
	$_SESSION["OPENLDAP_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));

	$ss=base64_encode($search["S"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("openldap.php?searchlogs=$ss&rp={$search["MAX"]}");
	$datas=explode("\n",@file_get_contents($target_file));


	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zdate</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{PID}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$STATICO["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $STATICO["LOG"]=               "<span class='label'>INFO.</span>";
    $STATICO["WARNING"]=           "<span class='label label-warning'>WARN.</span>";
    $STATICO["HINT"]=              "<span class='label label-info'>HINT.</span>";
    $STATICO["STATEMENT"]=         "<span class='label label-info'>STAT.</span>";
    $STATICO["NOTICE"]=         "<span class='label label-info'>NOTICE</span>";
    $STATICO["FATAL"]="<span class='label label-danger'>ERROR</span>";
	
	$TRCLASS=null;
	krsort($datas);

	$td1prc=$tpl->table_td1prc();

	foreach ($datas as $key=>$line){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		if(trim($line)==null){continue;}
		if($GLOBALS['VERBOSE']){echo "FOUND $line\n";}

        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?(\[([0-9]+)\]|(slapd)):\s+(.+)#",$line,$re)){
            echo "$line, no match;";
            continue;}
       // print_r($re);
        $date=strtotime($re[1] ." ".$re[2]." ".$re[3]);
        $pid=$re[5];
		$line=$re[7];
		$datetext=$tpl->time_to_date($date,true);
		if(preg_match("#(does not exist|Error while|Can not|error|failed|slapd stopped|slapd shutdown|daemon: shutdown)#i", $line)){
			$text_class="text-danger";
		}
		
		if(preg_match("#(Starting|OpenLDAP:|ACCEPT from)#i", $line)){
			$text_class="text-info";
		}
		if(preg_match("#connections_destroy#i", $line)){
			$text_class="text-warning";
		}

		if(preg_match("#incomplete startup packet#",$line)){
		    $line="<span style='color:#CCCCCC'>$line</span>";
        }

	    $html[]="<tr class='$TRCLASS'>";
		$html[]="<td $td1prc>$datetext</td>";
        $html[]="<td $td1prc>{$pid}</td>";
		$html[]="<td class=\"$text_class\">$line</a></td>";
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
	$html[]="</table><div><i></i></div>";
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true } } ); });
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
