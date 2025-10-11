<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ntpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["ntpd-table"])){table();exit;}
if(isset($_GET["new-server"])){new_server_js();exit;}
if(isset($_GET["new-server-popup"])){new_server_popup();exit;}
if(isset($_POST["new-server"])){new_server_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["ntpdservermove"])){ntpdservermove();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog3("{ntp_servers}", "$page?popup=yes",550);
}
function new_server_js(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog4("{ntp_servers} {new_server}", "$page?new-server-popup=yes");
}
function ntpdservermove(){
	$ntp=new ntpd();
	$servername=$_GET["ntpdservermove"];
	$direction=$_GET["direction"];
	$ntp->MoveServer($servername,$direction);
}

function popup(){
	$page=CurrentPageName();
    echo "<div id='progress-ntpd-restart'></div>";
	echo "<div id='ntpd-servers'></div>
	<script>
		LoadAjaxSilent('ntpd-servers','$page?ntpd-table=yes');
	</script>";
}
function new_server_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("/chrony/reconfigure",
        "ntpd.progress",
        "ntpd.progress.log",
        "progress-ntpd-restart",
        "LoadAjax('table-loader-ntpd-service','fw.system.ntpd.php?tabs=yes');");
	
	$form[]=$tpl->field_text("new-server", "{server_address}", null);
	echo "<div id='ntpd-servers-progress'></div>";
	echo $tpl->form_outside("{new_server}", @implode("\n", $form),"{how_to_find_timeserver}","{add}","dialogInstance4.close();LoadAjaxSilent('ntpd-servers','$page?ntpd-table=yes');$jsrestart","AsSystemAdministrator");
}

function new_server_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ntp=new ntpd();
	$ntp->AddServer($_POST["new-server"]);
}
function delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ntp=new ntpd();

	if(isset($_GET["md"])){$id=$_GET["md"];}
    if(isset($_GET["id"])){$id=$_GET["id"];}

    $jsrestart=$tpl->framework_buildjs("/chrony/reconfigure",
        "ntpd.progress",
        "ntpd.progress.log",
        "progress-ntpd-restart",
        "LoadAjax('table-loader-ntpd-service','fw.system.ntpd.php?tabs=yes');");

	$ntp->DeleteServer($_GET["delete-js"]);
	echo "$('#{$id}').remove();\n
	if(document.getElementById('progress-ntpd-restart')){
	    $jsrestart;
	}";

}


function table(){
	$q=new lib_sqlite("/home/artica/SQLITE/ntp.db");
	$page=CurrentPageName();
	$tpl=new template_admin();
	$reconfigure=null;
	$NTPDEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDEnabled"));
    $jsrestart=$tpl->framework_buildjs("/chrony/reconfigure",
        "ntpd.progress",
        "ntpd.progress.log",
        "progress-ntpd-restart");

    $add_btn="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?new-server=yes');\"><i class='fa fa-plus'></i> {new_server} </label>";
	
	if($NTPDEnabled==0){
		$reconfigure="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {reconfigure_service} </label>";
	}
	$NTPDUseSpecifiedServers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDUseSpecifiedServers"));
	$NTPClientDefaultServerList=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPClientDefaultServerList");
	if($NTPClientDefaultServerList==null){$NTPClientDefaultServerList="United States";}
	
	if($NTPDUseSpecifiedServers==0){
		$html[]="<div style='margin-top:20px'><H2>{default_ntp_servers}:$NTPClientDefaultServerList</H2>";
		$add_btn=null;
	}
	
	
	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			$add_btn
			$reconfigure
			</div>");
	
	
			$html[]="<table id='table-ntpd-servers' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
			$html[]="<thead>";
			$html[]="<tr>";
	
	
	
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ntp_servers}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>mv</center></th>";
			$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>Del.</center></th>";
			$html[]="</tr>";
			$html[]="</thead>";
			$html[]="<tbody>";
	
			$TRCLASS=null;
			
			if($NTPDUseSpecifiedServers==0){
				$ntp=new ntpd();
				$SERVERS=$ntp->ServersList();
				foreach ($SERVERS[$NTPClientDefaultServerList] as $hostname ){
					if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
					$html[]="<tr class='$TRCLASS' id=''>";
					$html[]="<td>{$hostname}</td>";
					$html[]="<td style='vertical-align:middle' width=1% nowrap>&nbsp;</td>";
					$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>&nbsp;</td>";
					$html[]="</tr>";
					
				}
				
			}
			
			
			if($NTPDUseSpecifiedServers==1){
				$sql="SELECT * FROM ntpd_servers ORDER BY `ntpd_servers`.`order` ASC";
				writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
				$results = $q->QUERY_SQL($sql,"artica_backup");
		
				foreach ($results as $index=>$ligne){
					if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
					$md=md5(serialize($ligne));
					$serverenc=urlencode($ligne["ntp_servers"]);
					$html[]="<tr class='$TRCLASS' id='$md'>";
					$html[]="<td>{$ligne["ntp_servers"]}</td>";
					$html[]="<td style='vertical-align:middle' width=1% nowrap>".
					$tpl->icon_up("Loadjs('$page?ntpdservermove=$serverenc&direction=up')")."&nbsp;".
					$tpl->icon_down("Loadjs('$page?ntpdservermove=$serverenc&direction=down')")."
					
					</td>";
					$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-js=$serverenc&id=$md')","AsDnsAdministrator")."</center></td>";
					$html[]="</tr>";
					
				}
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
			$html[]="
			<script>
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$(document).ready(function() { $('#table-ntpd-servers').footable( { 	\"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
			</script>";	
			
			echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}