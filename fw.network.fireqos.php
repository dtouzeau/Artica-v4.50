<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.builder.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["containerid"])){container_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["interface-config-js"])){interface_config_js();exit;}
if(isset($_GET["interface-config-popup"])){interface_config_popup();exit;}
if(isset($_POST["eth"])){interface_config_save();exit;}
if(isset($_GET["qos-enabled"])){qos_enabled();exit;}
if(isset($_GET["containers"])){containers();exit;}
if(isset($_GET["containers-table"])){containers_table();exit;}
if(isset($_GET["container-js"])){container_js();exit;}
if(isset($_GET["container-tabs"])){container_tabs();exit;}
if(isset($_GET["container-popup"])){container_popup();exit;}
if(isset($_GET["container-enabled"])){container_enabled();exit;}
if(isset($_GET["container-move"])){container_move();exit;}
if(isset($_GET["container-delete"])){container_delete();exit;}
if(isset($_GET["config-file"])){config_file();exit;}


page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$rescan=$tpl->_ENGINE_parse_body("{rescan}");
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	$users=new usersMenus();

	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_FIREQOS}</h1><p>{qos_artica_explain}</p></div>
	</div>
	<div class='row'>
		<div id='progress-fireqos-restart'></div>";


	$html[]="
			<div class='ibox-content'>
				<div id='table-loader-fireqos'></div>
			</div>
	</div>";




	$html[]="<script>
		LoadAjax('table-loader-fireqos','$page?tabs=yes');
	</script>";


	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function config_file(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$id=md5(time());
	$value=@file_get_contents("/etc/firehol/fireqos.conf");
	$html[]="<textarea id='$id' name='$id' style='width:100%;height:850px'>$value</textarea>";
	$html[]="<script>";
	$html[]="var editor{$id} = CodeMirror.fromTextArea(document.getElementById('$id'), { lineNumbers: true, matchBrackets: true });";
	$html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function container_enabled(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["container-enabled"]);	
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db"); // sqlite_num_rows
	$sql="SELECT * FROM `qos_containers` WHERE ID='$ID'";
	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	if($ligne["enabled"]==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE qos_containers SET `enabled`=$enabled WHERE ID=$ID","artica_backup");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
}

function container_move(){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db"); // sqlite_num_rows
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["container-move"];
	$OrgID=$ID;
	$dir=$_GET["dir"];
	$sql="SELECT prio,eth FROM qos_containers WHERE ID='$ID'";
	
	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

	$eth=$ligne["eth"];
	$CurrentOrder=$ligne["prio"];
	
	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE qos_containers SET prio='$CurrentOrder' WHERE prio='$NextOrder' AND eth='$eth'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}


	$sql="UPDATE qos_containers SET prio=$NextOrder WHERE ID='$ID' AND eth='$eth'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}

	$results=$q->QUERY_SQL("SELECT ID FROM qos_containers WHERE eth='$eth' ORDER by prio","artica_backup");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}
	$c=1;
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$sql="UPDATE qos_containers SET prio='$c' WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}
		$c++;
	}
    CreateMarks();

}

function container_delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md=$_GET["md"];
	$ID=$_GET["container-delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");  // sqlite_num_rows
	$q->QUERY_SQL("DELETE FROM qos_sqacllinks WHERE aclid='$ID'");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>");return;}
	
	$q->QUERY_SQL("DELETE FROM qos_containers WHERE ID='$ID'");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>");return;}
	header("content-type: application/x-javascript");
	echo "$('#$md').remove();\n";
    CreateMarks();
}

function container_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["container-js"]);
	if($ID==0){
		$title=$tpl->_ENGINE_parse_body("{new_container}");
	}else{
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");  // sqlite_num_rows
		$results=$q->QUERY_SQL("SELECT * FROM `qos_containers` WHERE ID='$ID'");
		$ligne=$results[0];
		$title=utf8_decode($ligne["name"]);
	}
	$tpl->js_dialog1($title, "$page?container-tabs=$ID");
}

function containers(){
	$page=CurrentPageName();
	$html="<div id='qos-containers'></div><script>LoadAjaxSilent('qos-containers','$page?containers-table=yes');</script>";
	echo $html;
}

function interface_config_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["interface-config-js"];
	$nicz=new system_nic($eth);
	$tpl->js_dialog1($nicz->NICNAME." ".$nicz->netzone ." ($eth)", "$page?interface-config-popup=$eth");
}
function interface_config_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=new system_nic($_GET["interface-config-popup"]);
	$modemType[null]="[LAN] Switch/Hub/Router";
	$modemType["adsl:pppoe-llc"]="[ADSL] PPPoE LLC/SNAP";
	$modemType["adsl:pppoe-vcmux"]="[ADSL] PPPoE VC/Mux";
	$modemType["adsl:pppoa-llc"]="[ADSL] PPPoA LLC/SNAP";
	$modemType["adsl:pppoa-vcmux"]="[ADSL] PPPoA VC/Mux";
	$modemType["adsl:ipoa-llc"]="[ADSL] IPoA LLC/SNAP";
	$modemType["adsl:ipoa-vcmux"]="[ADSL] IPoA VC/Mux";
	$modemType["adsl:bridged-llc"]="[ADSL] Bridged LLC/SNAP";
	$modemType["adsl:bridged-vcmux"]="[ADSL] Bridged VC/Mux";

	$UNITS["kbit"]="(Kbit/Kbps) kilobits per second";
	$UNITS["bps"]="(bps) Bytes per second";
	$UNITS["mbps"]="(mbps) Megabytes per second";
	$UNITS["gbps"]="(gbps) gigabytes per second";
	$UNITS["bit"]="(bits) per second";
	$UNITS["mbit"]="(Mbit) megabits per second";
	$UNITS["gbit"]="(Gbit) gigabits per second";

	if($eth->InputSpeed==0){$eth->InputSpeed=100;}
	if($eth->OutputSpeed==0){$eth->OutputSpeed=100;}

    $form[]=$tpl->field_hidden("SpeedUnit", "Mbit");
	$form[]=$tpl->field_hidden("eth", $_GET["interface-config-popup"]);
	$form[]=$tpl->field_checkbox("FireQOS","{enable_qos_fornic}",$eth->FireQOS,true,"{enable_qos_fornic_explain}");
	$form[]=$tpl->field_array_hash($modemType, "ModemType", "{ModemType}", $eth->ModemType);
	$form[]=$tpl->field_numeric("InputSpeed","{download_speed} (Mbit)",$eth->InputSpeed);
	$form[]=$tpl->field_numeric("OutputSpeed","{upload_speed} (Mbit)",$eth->OutputSpeed);



	$js[]="dialogInstance1.close()";
	$js[]="LoadAjax('table-loader-fireqos','$page?tabs=yes')";
	$html=$tpl->form_outside("{interface}", @implode("\n", $form),"{FireQOS_interface_explain}","{apply}",@implode(";", $js),"AsFirewallManager");
	echo $tpl->_ENGINE_parse_body($html);

}

function qos_enabled(){
	$eth=new system_nic($_GET["qos-enabled"]);
	if($eth->FireQOS==1){$eth->FireQOS=0;}else{$eth->FireQOS=1;}
	$eth->SaveNic();
}

function interface_config_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$eth=new system_nic($_POST["eth"]);
	unset($_POST["eth"]);
	foreach ($_POST as $key=>$val){
		$eth->$key=$val;
	}
	$eth->SaveNic();

}

function container_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["container-tabs"]);
	$array["{container}"]="$page?container-popup=$ID";
	if($ID>0){
		$array["{objects}"]="fw.network.fireqos.rules.php?ruleid=$ID";

	}


	echo $tpl->tabs_default($array);
}
function container_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql();
	$ID=intval($_GET["container-popup"]);
	$btname="{apply}";
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");  // sqlite_num_rows


    if(!$q->FIELD_EXISTS("qos_containers","mark")){
        $q->QUERY_SQL("ALTER TABLE qos_containers ADD `mark` INTEGER NOT NULL DEFAULT 20");
        if(!$q->ok){echo $q->mysql_error_html(true);}
        CreateMarks();
    }


	$results=$q->QUERY_SQL("SELECT InputSpeed,OutputSpeed,SpeedUnit,Interface FROM nics WHERE FireQOS=1 ORDER BY Interface","artica_backup");
	foreach ($results as $index=>$ligne){
		$HASH[$ligne["Interface"]."-in"]=$ligne["Interface"]." {inbound}/{download2} {$ligne["InputSpeed"]}{$ligne["SpeedUnit"]}";
		$HASH[$ligne["Interface"]."-out"]=$ligne["Interface"]." {outbound}/{upload2} {$ligne["OutputSpeed"]}{$ligne["SpeedUnit"]}";
	}

	if($ID==0){
		$btname="{add}";
		$title=$tpl->_ENGINE_parse_body("{new_container}");
		$js[]="dialogInstance1.close()";
	}else{
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $results=$q->QUERY_SQL("SELECT * FROM `qos_containers` WHERE ID='$ID'","artica_backup");
		$ligne=$results[0];
		$title=utf8_decode($ligne["name"]);
	}

	$js[]="LoadAjaxSilent('qos-containers','$page?containers-table=yes')";
	$UNITS1["%"]="(%) {percent}";
	$UNITS1["kbit"]="(Kbit) kilobits per second";
	$UNITS1["bps"]="(bps) Bytes per second";
	$UNITS1["kbps"]="(kbps) Kilobytes per second";
	$UNITS1["mbps"]="(mbps) Megabytes per second";
	$UNITS1["gbps"]="(gbps) gigabytes per second";
	$UNITS1["bit"]="(bits) per second";
	$UNITS1["mbit"]="(Mbit) megabits per second";
	$UNITS1["gbit"]="(Gbit) gigabits per second";


	$UNITS["kbit"]="(Kbit) kilobits per second";
	$UNITS["bps"]="(bps) Bytes per second";
	$UNITS["kbps"]="(kbps) Kilobytes per second";
	$UNITS["mbps"]="(mbps) Megabytes per second";
	$UNITS["gbps"]="(gbps) gigabytes per second";
	$UNITS["bit"]="(bits) per second";
	$UNITS["mbit"]="(Mbit) megabits per second";
	$UNITS["gbit"]="(Gbit) gigabits per second";
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if($ID==0){$ligne["enabled"]=1;}
	if($ligne["name"]==null){$ligne["name"]="container_".time();}

	$tpl->field_hidden("rate_unit","mbit");
    $tpl->field_hidden("ceil_unit","mbit");
	$form[]=$tpl->field_hidden("containerid", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_text("name", "{container}", $ligne["name"],true);
	$form[]=$tpl->field_numeric("prio","{priority}",$ligne["prio"],null);

	$form[]=$tpl->field_array_hash($HASH, "eth", "{interface}", $ligne["eth"]);
	$form[]=$tpl->field_numeric("rate","{guaranteed_rate} (Mbit)",$ligne["rate"],"{Guaranteed_Rate_explain}");
    $form[]=$tpl->field_numeric("ceil","{max_bandwidth} (Mbit)",$ligne["ceil"],"{qos_ceil_explain}");
    $titleplus=" <small>MARK {$ligne["mark"]}</small>";
    if($ID>0){
        if($ligne["mark"]>0) {
            $mak = NdpiboxClientValueToNdpiMark($ligne["mark"]);
            $titleplus = " <small>MARK {$ligne["mark"]}/<strong>$mak</strong></small>";
        }
    }


	echo $tpl->form_outside($title.$titleplus, @implode("\n", $form),null,$btname,@implode(";", $js),"AsFirewallManager");

}

function container_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");  // sqlite_num_rows
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$ID=$_POST["containerid"];
	unset($_POST["containerid"]);
	$_POST["name"]=replace_accents($_POST["name"]);
	$_POST["name"]=str_replace(" ", "", $_POST["name"]);
	$_POST["name"]=str_replace("-", "", $_POST["name"]);
	$_POST["name"]=str_replace("_", "", $_POST["name"]);
	$_POST["name"]=str_replace("/", "", $_POST["name"]);
	$_POST["name"]=str_replace("\\", "", $_POST["name"]);


	if(!$q->FIELD_EXISTS("qos_containers","mark")){
	    $q->QUERY_SQL("ALTER TABLE qos_containers ADD `mark` INTEGER NOT NULL DEFAULT 20");
    }


	$table="qos_containers";
	foreach ($_POST as $key=>$value){
		$fields[]="`$key`";
		$values[]="'".$q->sqlite_escape_string2($value)."'";
		$edit[]="`$key`='".$q->sqlite_escape_string2($value)."'";

	}
	$eth=$_POST["eth"];
	if($ID>0){
		$sql="UPDATE $table SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT OR IGNORE INTO $table (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}




	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

	$results=$q->QUERY_SQL("SELECT ID FROM qos_containers WHERE eth='$eth' ORDER by prio");
	if(!$q->ok){echo "Line:".__LINE__.":".$q->mysql_error;}
	$c=1;
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$sql="UPDATE qos_containers SET prio=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Line:".__LINE__.":$sql\n".$q->mysql_error;}
		$c++;
	}

    CreateMarks();

}

function CreateMarks(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID FROM qos_containers ORDER BY ID");

    $mark=0;
    foreach ($results as $index=>$ligne){
        $mark++;
        $ID=$ligne["ID"];

        if($mark>128){
            $q->QUERY_SQL("UPDATE qos_containers SET enabled=0 WHERE ID=$ID");
            continue;
        }

        $q->QUERY_SQL("UPDATE qos_containers SET mark=$mark WHERE ID=$ID");



    }



}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{interfaces}"]="$page?interfaces=yes";
	$array["{containers}"]="$page?containers=yes";
	$array["{config}"]="$page?config-file=yes";

	echo $tpl->tabs_default($array);
}

function interfaces(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$TRCLASS=null;

	$modemType[null]="[LAN] Switch/Hub/Router";
	$modemType["adsl:pppoe-llc"]="[ADSL] PPPoE LLC/SNAP";
	$modemType["adsl:pppoe-vcmux"]="[ADSL] PPPoE VC/Mux";
	$modemType["adsl:pppoa-llc"]="[ADSL] PPPoA LLC/SNAP";
	$modemType["adsl:pppoa-vcmux"]="[ADSL] PPPoA VC/Mux";
	$modemType["adsl:ipoa-llc"]="[ADSL] IPoA LLC/SNAP";
	$modemType["adsl:ipoa-vcmux"]="[ADSL] IPoA VC/Mux";
	$modemType["adsl:bridged-llc"]="[ADSL] Bridged LLC/SNAP";
	$modemType["adsl:bridged-vcmux"]="[ADSL] Bridged VC/Mux";





	$html[]="<table id='table-fireqos-interfaces' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{interface}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{tcp_address}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{ModemType}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{download_speed}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{upload_speed}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{enabled}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="SELECT Interface FROM nics WHERE enabled=1";
	$results=$qlite->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$Interface=$ligne["Interface"];
		$nicz=new system_nic($Interface);
		$FireQOS=$nicz->FireQOS;
		$html[]="<tr class=$TRCLASS>";
		$html[]="<td>". $tpl->td_href($nicz->NICNAME." ".$nicz->netzone ." ($Interface)","{click_to_edit}","Loadjs('$page?interface-config-js=$Interface')")."</td>";
		$html[]="<td>".$nicz->IPADDR."</td>";
		$html[]="<td width=1% nowrap>".$modemType[$nicz->ModemType]."</td>";
		$html[]="<td>".$nicz->InputSpeed." $nicz->SpeedUnit</td>";
		$html[]="<td>".$nicz->OutputSpeed." $nicz->SpeedUnit</td>";
		$html[]="<td width=1%>".$tpl->icon_check($FireQOS,"Loadjs('$page?qos-enabled=$Interface')",null,"AsFirewallManager")."</td>";
		$html[]="</tr>";
		
	}
	

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-fireqos-interfaces').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function containers_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$GLOBALS["jsAfterEnc"]="LoadAjaxSilent('qos-containers','$page?containers-table=yes');";
	$q=new mysql();
	$TRCLASS=null;
	$suffix["in"]=$tpl->javascript_parse_text("{inbound}");
	$suffix["out"]=$tpl->javascript_parse_text("{outbound}");	
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fireqos.reconfigure.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/fireqos.reconfigure.progress.txt";
	$ARRAY["CMD"]="firehol.php?reconfigure-qos-progress=yes";
	$ARRAY["TITLE"]="{apply_QOS_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-fireqos-restart')";
	
	
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?container-js=0');\">";
	$html[]="<i class='fa fa-plus'></i> {new_container} </label>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_QOS_rules} </label>";
	$html[]="</div>";

	
	$html[]="<table id='table-fireqos-containers' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{priority}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{containers}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{interface}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{guaranteed_rate}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{max_bandwidth}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{enabled}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{move}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>Del.</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$sql="SELECT * FROM qos_containers ORDER BY prio";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");  // sqlite_num_rows
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$cellule=null;
		$md=md5(serialize($ligne));
		
		
		if(preg_match("#(.*?)-(.+)$#", $ligne["eth"],$re)){
			$ligne["eth"]=$re[1];
			$eth_text=$re[1]." <strong>".$suffix[$re[2]]."</strong>";
		}
		
		
		$nic=new system_nic($ligne["eth"]);
		
		if($ligne["ceil"]>0){
			$cellule="{$ligne["ceil"]}{$ligne["ceil_unit"]}";
				
		}
		$EXPLAIN_LIST_OBJECTS=EXPLAIN_LIST_OBJECTS($ligne["ID"]);
		
		$mv_up=$tpl->icon_up("Loadjs('$page?container-move={$ligne["ID"]}&dir=0')","AsFirewallManager");
		$mv_down=$tpl->icon_down("Loadjs('$page?container-move={$ligne["ID"]}&dir=1')","AsFirewallManager");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>{$ligne["prio"]}</td>";
		$html[]="<td>{apply_qos_rule_with_container} ". $tpl->td_href($ligne["name"],"{click_to_edit}","Loadjs('$page?container-js={$ligne["ID"]}')")."$EXPLAIN_LIST_OBJECTS</td>";
		$html[]="<td nowrap>$nic->NICNAME ({$eth_text})</td>";
		$html[]="<td>{$ligne["rate"]}{$ligne["rate_unit"]}</td>";
		$html[]="<td>$cellule</td>";
		$html[]="<td>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?container-enabled={$ligne["ID"]}')",null,"AsFirewallManager")."</td>";
		$html[]="<td width=1% nowrap>$mv_up&nbsp;$mv_down</td>";
		$html[]="<td width=1% nowrap>".$tpl->icon_delete("Loadjs('$page?container-delete={$ligne["ID"]}&md=$md')","AsFirewallManager")."</td>";
		$html[]="</tr>";
	
	}
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='8'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-fireqos-containers').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	
}


function EXPLAIN_LIST_OBJECTS($ID,$color){
	
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$table="SELECT qos_sqacllinks.gpid,qos_sqacllinks.negation,
	qos_sqacllinks.zOrder,qos_sqacllinks.zmd5 as mkey,
	webfilters_sqgroups.GroupName,
	webfilters_sqgroups.ID as gpid,
	webfilters_sqgroups.GroupType FROM qos_sqacllinks,webfilters_sqgroups
	WHERE qos_sqacllinks.gpid=webfilters_sqgroups.ID
	AND qos_sqacllinks.aclid=$ID
	AND webfilters_sqgroups.enabled='1'
	ORDER BY qos_sqacllinks.zOrder";


	$results=$q->QUERY_SQL($table);
	if(!$q->ok){return $q->mysql_error;}
	$GPS=array();
	foreach ($results as $index=>$ligne) {
		$GroupName=utf8_encode($ligne["GroupName"]);
		$GroupType=$ligne["GroupType"];
		$js_group_final=null;
		$ID=$ligne["gpid"];


		$js_group="javascript:Loadjs('fw.rules.items.php?groupid=$ID&js-after={$GLOBALS["jsAfterEnc"]}')";


		$js_group_final="<a href=\"javascript:blur();\" OnClick=\"$js_group\"
		style='text-decoration:underline;color:$color'>";
		if($GroupType=="all"){$js_group_final=null;}

		$ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$ID'");
		$items=$ligne2["tcount"];
		$GPS[]="<strong>$js_group_final$GroupName</a> ( $items {elements} )</strong>";

	}
	if(count($GPS)==0){return "<div class=text-danger>{error_acl_no_object}</div>";}
	
	return "{for_objects} ".@implode("<br> {or} ", $GPS);"<br>";
}
function NdpiboxClientValueToNdpiMark($iValue = 0){
    $ndpi = dechex($iValue * 64);
    $result = "0x".strtoupper($ndpi);
    return $result;
}

