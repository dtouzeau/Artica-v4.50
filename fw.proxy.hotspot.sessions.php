<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$users=new usersMenus();if(!$users->AsHotSpotManager){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["HotSpotRedirectUI"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["uncheck"])){uncheck();exit;}
if(isset($_GET["remove"])){remove();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["reload-squid-cache"])){reload_squid_cache();exit;}

page();


function remove(){
    $sessionkey=$_GET["remove"];
    $md=$_GET["md"];
    $tpl=new template_admin();
    $tpl->js_confirm_delete("{session} $sessionkey","remove",$sessionkey,"$('#$md').remove();");
}
function remove_perform(){
    $sessionkey=$_POST["remove"];
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $memcached=new lib_memcached();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='$sessionkey'");
    $macaddress=$ligne["macaddress"];
    $ipaddr=$ligne["ipaddr"];
    $memcached->Delkey("MICROHOTSPOT:$ipaddr");
    if($macaddress<>null){$memcached->Delkey("MICROHOTSPOT:$macaddress");}
    $q->QUERY_SQL("DELETE FROM sessions WHERE sessionkey='$sessionkey'");
    if(!$q->ok){echo $q->mysql_error;}
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?reload-squid-cache=yes");
}


function uncheck(){

    $sessionkey=$_GET["uncheck"];
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='$sessionkey'");
    $macaddress=$ligne["macaddress"];
    $ipaddr=$ligne["ipaddr"];
    $enabled=$ligne["enabled"];
    if($enabled==1){$newenabled=0;}else{$newenabled=1;}
    $q->QUERY_SQL("UPDATE sessions SET enabled=$newenabled WHERE sessionkey='$sessionkey'");
    if($q->ok){
        admin_tracks("Update HotSpot session key $sessionkey to enabled=$newenabled");
    }
    $memcached=new lib_memcached();


    if($newenabled==0){
        if($macaddress<>null){$memcached->Delkey("MICROHOTSPOT:$macaddress");}
        $memcached->Delkey("MICROHOTSPOT:$ipaddr");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?reload-squid-cache=yes");
    }else{
        $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='$sessionkey'");
        if($macaddress<>null){$memcached->saveKey("MICROHOTSPOT:$macaddress",$ligne,5*60);}
        $memcached->saveKey("MICROHOTSPOT:$ipaddr",$ligne,5*60);
    }
}

function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{sessions_manager} v5",
        "fas fa-address-card",
        "{sessions_manager_explain}",
        "$page?table=yes",
        "hotspot-sessions",
        "progress-hotspot-sessions-restart",false,"table-hotspot-sessions");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{sessions_manager}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{general_settings}"]="$page?table=yes";
    echo $tpl->tabs_default($array);
}

function reload_squid_cache(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?reload-squid-cache=yes");
    $tpl=new template_admin();
    $tpl->js_display_results("{empty_cache}","{empty_cache} {success}");
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/squid/hotspot/database.db");
    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";

    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?reload-squid-cache=yes')\"><i class='fa fa-sync-alt'></i> {empty_cache} </label>";
    $btns[]="</label>";

    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"LoadAjax('table-hotspot-sessions','$page?table=yes')\"><i class='fa fa-sync-alt'></i> {refresh} </label>";
    $btns[]="</label>";

    $btns[]="</div>";
	$html[]="<table id='table-hotspot-sessions-list' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{created}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' nowrap>{member}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{MAC}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{will_be_removed_on}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>{enabled}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$results=$q->QUERY_SQL("SELECT * FROM sessions ORDER BY removeaccount");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$Time=time();
		$md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$enabled=$ligne["enabled"];
		$sessionkey=$ligne["sessionkey"];
		$removeaccount=$ligne["removeaccount"];
		$macaddress=$ligne["macaddress"];
		$ipaddr=$ligne["ipaddr"];
		$username=$ligne["username"];
		$autocreate=$ligne["autocreate"];
        $tooltip=null;
		if($autocreate==1){
		    $tooltip=" <span class='label label-warning'>{waiting_confirmation}</span>";
        }
        if($autocreate==2){
            $tooltip=" <span class='label label-primary'>{confirmed}</span>";
        }
        if($autocreate==10){
            $tooltip=" <span class='label label-primary'>{voucher_room}</span>";
        }

		
		if($removeaccount>0){
			
			$removed_on=distanceOfTimeInWords($Time,$removeaccount)." (".$tpl->time_to_date($removeaccount,true).")";
		}else{
			$removed_on="{never}";
		}
		if($macaddress==null){$macaddress="00:00:00:00:00:00";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>".$tpl->time_to_date($ligne["created"],true)."</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->td_href($username,null,"{Loadjs('$page?edit=$sessionkey')")." $tooltip</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->td_href($macaddress,null,"{Loadjs('$page?edit=$sessionkey')")."</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->td_href($ipaddr,null,"{Loadjs('$page?edit=$sessionkey')")."</td>";
		$html[]="<td nowrap>$removed_on</td>";
		$html[]="<td style='width:1%' nowrap class='center'>".$tpl->icon_check($enabled,
                "Loadjs('$page?uncheck=$sessionkey')","AsHotSpotManager")."</td>";
		$html[]="<td style='width:1%' nowrap class='center'>".$tpl->icon_delete("Loadjs('$page?remove=$sessionkey&md=$md')",
                "AsHotSpotManager")."</center></td>";
		$html[]="</tr>";
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="";
	$html[]="<script>";

    $TINY_ARRAY["TITLE"]="{sessions_manager} v5";
    $TINY_ARRAY["ICO"]="fas fa-address-card";
    $TINY_ARRAY["EXPL"]="{sessions_manager_explain}";
    $TINY_ARRAY["URL"]="hotspot-sessions";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="\t$jstiny";
	$html[]="\tNoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
	$html[]="\t$(document).ready(function() { ";
	$html[]="$('#table-hotspot-sessions-list').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if($_POST["HotSpotRedirectUI2"]<>null){
		$_POST["HotSpotRedirectUI"]=$_POST["HotSpotRedirectUI2"];
		unset($_POST["HotSpotRedirectUI2"]);
	}
	$tpl->SAVE_POSTs();
	
	
	
}