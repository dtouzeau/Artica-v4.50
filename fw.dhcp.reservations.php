<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_GET["dhcpfixed"])){dhcpfixed();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{reservations}",ico_computer_down,"{dhcp_reservations_explain}","$page?search-form=yes","dhcp-reservations","progress-reservations-restart");


if(isset($_GET["main-page"])){
	$tpl=new template_admin("{APP_DHCP} {reservations}",$html);
	echo $tpl->build_firewall();
	return;
}
	
	echo $tpl->_ENGINE_parse_body($html);

}
function dhcpfixed(){

    $mac=$_GET["dhcpfixed"];
    $comp=new hosts($mac);
    if($comp->dhcpfixed==1){
        $comp->dhcpfixed=0;
        $comp->Save();
        return admin_tracks("Remove host $mac from DHCP reservation");
    }else{
        $comp->dhcpfixed=1;
        $comp->Save();
        return admin_tracks("Add host $mac to reservation");
    }

}

function search_form(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$addr=$tpl->javascript_parse_text("{addr}");
    $function=$_GET["function"];
    if(!isset($_GET["search"])){$_GET["search"]="";}
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));

    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log",
            "progress-reservations-restart",
            "$function()"
        );

        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";

        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

	
	$t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";

    $query="WHERE dhcpfixed=1";
    $search=$_GET["search"];
    if(strlen($search)>1){
	$_SESSION["DHCPL_SEARCH"]=trim(strtolower($_GET["search"]));
    $search="*{$_GET["search"]}*";
    $search=str_replace("**","%",$search);
    $search=str_replace("*","%",$search);
    $search=str_replace("%%","%",$search);
    $query="WHERE (( TEXT(mac) LIKE '$search') OR (TEXT(ipaddr) LIKE '$search') OR (hostname LIKE '$search') OR (proxyalias LIKE '$search') OR (domainname LIKE '$search') OR (fullhostname LIKE '$search')";
    }

	$q=new postgres_sql();
	$sql="SELECT * FROM hostsnet $query ORDER BY updated DESC LIMIT 500";
	$results=$q->QUERY_SQL($sql);


	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{updated}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$hostname</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ComputerMacAddress}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$addr</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{gateway}</th>";
    if($EnableKEA==1){
        $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{nic}</th>";
    }
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	if(!$q->ok){
        echo $tpl->div_error("$q->mysql_error<code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong>");

	}
    $CallBackMyComps=base64_encode($function);
    $TRCLASS=null;
    $rows=pg_num_rows($results);
    VERBOSE("Rows: $rows",__LINE__);
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$md=md5(serialize($ligne));
        $ipaddr=$ligne["ipaddr"];
		$fullhostname=trim($ligne["hostname"]);
        $dhcpiface=trim($ligne["dhcpiface"]);
        $gateway=$ligne["gateway"];
        $mac=$ligne["mac"];
        $macenc=urlencode($mac);
		if(strlen($mac)<3){
            VERBOSE("MAC IS NULL!",__LINE__);
            continue;}
        $updated=strtotime($ligne["updated"]);
        $dhcpfixed=$ligne["dhcpfixed"];
        $domainname=$ligne["domainname"];

        if(strlen($domainname)>3){
            $fullhostname="$fullhostname.$domainname";
        }

		$jshost="Loadjs('fw.edit.computer.php?mac=$macenc&CallBackFunction=$CallBackMyComps')";
        $fullhostname=$tpl->td_href($fullhostname,"",$jshost,"ASDCHPAdmin");

        $zdate=$tpl->time_to_date($updated,true);
		$distance=distanceOfTimeInWords($updated,time());
		$clock=ico_clock_desk;
        $clock2=ico_clock;
        $nic=ico_nic;
        $comp=ico_computer;
        $router=ico_sensor;
        $enable=$tpl->icon_check($dhcpfixed,"Loadjs('$page?dhcpfixed=$macenc')","ASDCHPAdmin");
        $style1="style='width:1%' nowrap";
		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $style1><i class='$clock'></i>&nbsp;$zdate</td>";
		$html[]="<td $style1><i class='$clock2'></i>&nbsp;$distance</td>";
		$html[]="<td class=\"$text_class\"><strong><i class='$comp'></i>&nbsp;$fullhostname</strong></td>";
        $html[]="<td $style1><i class='$nic'></i>&nbsp;$mac</td>";
        $html[]="<td $style1><i class='$nic'></i>&nbsp;$ipaddr</td>";
        $html[]="<td $style1><i class='$router'></i>&nbsp;$gateway</td>";
        if($EnableKEA==1){
            $html[]="<td $style1><i class='$nic'></i>&nbsp;$dhcpiface</td>";
        }
        $html[]="<td $style1>$enable</td>";
		$html[]="</tr>";
		

	}
    $column=7;
    if($EnableKEA==1){
        $column=8;
    }
	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='$column'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table><div><i><b>$rows</b>: $sql</i></div>";
	$html[]="<script>";


    $topbuttons[] = array("Loadjs('fw.add.computer.php?CallBackFunction=$CallBackMyComps&dhcpfixed=1')", ico_plus, "{new_computer}");

    $jsreload=$tpl->framework_buildjs(
        "/kea/dhcp/reload","kea.service.progress",
        "kea.service.log","progress-reservations-restart","","","","ASDCHPAdmin");

    $topbuttons[] = array("$jsreload", ico_refresh, "{reload}");

    $TINY_ARRAY["TITLE"]="{reservations} $rows {records}";
    $TINY_ARRAY["ICO"]=ico_computer_down;
    $TINY_ARRAY["EXPL"]="{dhcp_reservations_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable({\"filtering\": {\"enabled\": false},\"sorting\": {\"enabled\": true } } ); });";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}


