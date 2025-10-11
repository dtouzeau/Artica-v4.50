<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["folder-js"])){folder_js();exit;}
if(isset($_GET["folder-popup"])){folder_popup();exit;}
if(isset($_POST["ID"])){folder_save_settings();exit;}
if(isset($_GET["folder-settings"])){folder_settings();exit;}
if(isset($_GET["folder-users"])){folder_users();exit;}
if(isset($_GET["folder-users-table"])){folder_users_table();exit;}
if(isset($_GET["member-js"])){folder_users_js();exit;}
if(isset($_GET["member-popup"])){folder_users_popup();exit;}
if(isset($_POST["member"])){folder_users_save();exit;}
if(isset($_GET["member-delete"])){member_delete();exit;}
if(isset($_GET["folder-restrictions"])){folder_restrictions();exit;}
if(isset($_GET["folder-restrictions-table"])){folder_restrictions_table();exit;}
if(isset($_POST["restriction_id"])){folder_restrictions_save();exit;}

page();
function folder_users_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["member-js"]);
	if($ID==0){$title="{new_member}";}
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$ligne=$q->mysqli_fetch_array("SELECT `directory` FROM rsyncd_folders WHERE ID='$ID'");
	$title="{new_member}, {shared_folder}: ".basename($ligne["directory"]);
	$tpl->js_dialog2($title, "$page?member-popup=$ID");
}
function folder_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["folder-js"]);
	if($ID==0){$title="{new_folder}";}
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
		$ligne=$q->mysqli_fetch_array("SELECT `directory` FROM rsyncd_folders WHERE ID='$ID'");
		$title="{shared_folder2}: ".basename($ligne["directory"]);
	}
	
	$tpl->js_dialog1($title, "$page?folder-popup=$ID");
}
function member_delete(){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$tpl->CLEAN_POST();
	$ID=intval($_GET["ID"]);
	$ligne=$q->mysqli_fetch_array("SELECT members FROM rsyncd_folders WHERE ID='$ID'");
	$config=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["members"]);
	unset($config[$_GET["member-delete"]]);
	$newconfig=$q->sqlite_escape_string2(base64_encode(serialize($config)));
	$q->QUERY_SQL("UPDATE rsyncd_folders SET `members`='$newconfig' WHERE ID='$ID'");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
	header("content-type: application/x-javascript");
	echo "$('#{$_GET["md"]}').remove();";
	
}

function folder_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$ID=intval($_GET["folder-popup"]);	
	
	if($ID==0){
		$_GET["folder-settings"]=0;
		folder_settings();
		return;
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT `directory` FROM rsyncd_folders WHERE ID='$ID'");
		$title=basename($ligne["directory"]);
	}
	
	$array[$title]="$page?folder-settings=$ID";
	$array["{authentication}"]="$page?folder-users=$ID";
	$array["{restrictions}"]="$page?folder-restrictions=$ID";
	
	echo $tpl->tabs_default($array);
}
function folder_users(){
	$page=CurrentPageName();
	$ID=intval($_GET["folder-users"]);
	echo "<div style='margin-top:10px' id='rsync-folder-users'></div>
	<script>LoadAjaxSilent('rsync-folder-users','$page?folder-users-table=$ID');</script>";
}
function folder_restrictions(){
	$page=CurrentPageName();
	$ID=intval($_GET["folder-restrictions"]);
	echo "<div style='margin-top:10px' id='rsync-folder-restrictions'></div>
	<script>LoadAjaxSilent('rsync-folder-restrictions','$page?folder-restrictions-table=$ID');</script>";	
}
function folder_restrictions_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$ID=intval($_GET["folder-restrictions-table"]);
	$form[]=$tpl->field_hidden("restriction_id", $ID);
	$ligne=$q->mysqli_fetch_array("SELECT config FROM rsyncd_folders WHERE ID='$ID'");

	$config=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["config"]);
	$form[]=$tpl->field_textareacode("restrictions", null, @implode("\n", $config));
	$html=$tpl->form_outside("{restrictions}", $form,"{rsync_host_allow_explain}","{apply}",null,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}
function folder_restrictions_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$tpl->CLEAN_POST();
	$ID=intval($_POST["restriction_id"]);
    $F=array();
	$tt=explode("\n",$_POST["restrictions"]);
	foreach ($tt as $line){
		if(trim($line)==null){continue;}
		$F[$line]=$line;
		
	}
	$config=$F;
	$newconfig=mysql_escape_string2(base64_encode(serialize($config)));
	$q->QUERY_SQL("UPDATE rsyncd_folders SET `config`='$newconfig' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error."<br>";}
}

function folder_users_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$ID=intval($_GET["member-popup"]);
	
	
	$form[]=$tpl->field_hidden("member", $ID);
	$form[]=$tpl->field_text("username", "{username}", null,true);
	$form[]=$tpl->field_password2("password", "{password}", null,true);
	
	$js[]="dialogInstance2.close()";
	$js[]="LoadAjaxSilent('rsync-folder-users','$page?folder-users-table=$ID');";
		
	$html=$tpl->form_outside("{new_member}", @implode("\n", $form),null,"{add}",@implode(";", $js),"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}
function folder_users_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$tpl->CLEAN_POST();
	$ID=intval($_POST["member"]);
	$ligne=$q->mysqli_fetch_array("SELECT members FROM rsyncd_folders WHERE ID='$ID'");
	$config=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["members"]);
	$config[$_POST["username"]]=$_POST["password"];
	$newconfig=$q->sqlite_escape_string2(base64_encode(serialize($config)));
	$q->QUERY_SQL("UPDATE rsyncd_folders SET `members`='$newconfig' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error."<br>";}
}

function folder_users_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$TRCLASS=null;
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$ID=intval($_GET["folder-users-table"]);
	$ligne=$q->mysqli_fetch_array("SELECT members FROM rsyncd_folders WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error_html(true);}
	$config=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["members"]);
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?member-js=$ID');\"><i class='fa fa-plus'></i> {new_member} </label>";
	$html[]="</div>";
	
	
	$html[]="<table id='table-rsync-folder-users-$ID' class=\"footable table white-bg table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='text-align:left'>{username}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%;text-align:center'>Del.</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
    foreach ($config as $username=>$password){
		$md=md5($username.$password);
		$usernameenc=urlencode($username);
		$delete=$tpl->icon_delete("Loadjs('$page?member-delete=$usernameenc&ID=$ID&md=$md')","AsSystemAdministrator");
		
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>$username</strong></td>";
		$html[]="<td class=\"center\" style='width:1%'>$delete</td>";
		$html[]="</tr>";
		
		
		
	}
	
	$js=array();
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-rsync-folder-users-$ID').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	";
	$html[]=@implode("\n", $js)."</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
}



function folder_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$btname="{apply}";

	$ID=intval($_GET["folder-settings"]);
	$js[]="LoadAjax('table-loader-rsyndF-service','$page?table=yes')";
	
	if($ID==0){
		$title="{new_shared_folder}";
		$ligne["directory"]=null;
		$ligne["comment"]="My new folder"; 
		$ligne["readonly"]=1;
		$ligne["enabled"]=1;
		$ligne["writeonly"]=0;
		$ligne["listable"]=1;
		$btname="{add}";
		$js[]="dialogInstance1.close();";
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT * FROM rsyncd_folders WHERE ID='$ID'");
		$title=basename($ligne["directory"]);
	}
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_browse_directory("directory", "{directory}", $ligne["directory"]);
	$form[]=$tpl->field_text("comment", "{description}", $ligne["comment"]);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
	$form[]=$tpl->field_checkbox("readonly","{readonly}",$ligne["readonly"]);
	$form[]=$tpl->field_checkbox("writeonly","{writeonly}",$ligne["writeonly"],false,"{writelonly_rsync}");
	$form[]=$tpl->field_checkbox("listable","{browseable}",$ligne["listable"],false,"{list_rsync}");
	$html=$tpl->form_outside("$title", @implode("\n", $form),null,$btname,@implode(";", $js),"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function folder_save_settings(){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$tpl->CLEAN_POST();
	
	$ID=intval($_POST["ID"]);
	$fields[]="directory";
	$fields[]="comment";
	$fields[]="enabled";
	$fields[]="readonly";
	$fields[]="writeonly";
	$fields[]="listable";
	
	foreach ($fields as $field){
		if(!isset($_POST[$field])){continue;}
		
		$insert_a[]="`$field`";
		$insert_b[]="'".$q->sqlite_escape_string2($_POST[$field])."'";
		$edit_a[]="`$field`='".$q->sqlite_escape_string2($_POST[$field])."'";
		
	}
	
	
	if($ID==0){
		$sql="INSERT INTO rsyncd_folders (".@implode(",", $insert_a).") VALUES (".@implode(",", $insert_b).")";
	}else{
		$sql="UPDATE rsyncd_folders SET ".@implode(",", $edit_a)." WHERE ID='$ID'";
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."<br>$sql";}
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_VERSION");

    $html=$tpl->page_header("{APP_RSYNC_SERVER} v$version &raquo;&raquo; {shared_folders}",
        ico_folder,
        "{APP_RSYNC_SERVER_EXPLAIN}",
        "$page?table=yes","rsyncd-folders","progress-rsyncdF-restart",false,"table-loader-rsyndF-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_RSYNC_SERVER}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/rsyncd.db");
	$TRCLASS=null;
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_VERSION");
    $jsrestart=$tpl->framework_buildjs("/rsyncd/restart",
        "rsync.install.prg", "rsync.install.log",
        "progress-rsyncdF-restart",
        "LoadAjax('table-loader-rsyncdF-service','$page?table=yes');");

    $topbuttons[] = array("Loadjs('$page?folder-js=0');",ico_plus,"{new_shared_folder}");
    $topbuttons[] = array($jsrestart,ico_save,"{apply_configuration}");
    $TINY_ARRAY["TITLE"]="{APP_RSYNC_SERVER} v$version &raquo;&raquo; {shared_folders}";
    $TINY_ARRAY["ICO"]=ico_folder;
    $TINY_ARRAY["EXPL"]="{APP_RSYNC_SERVER_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	
	$html[]=$tpl->_ENGINE_parse_body("
	<table id='table-rsyncdfolders-list' class=\"footable table white-bg table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='text-align:left'>{directory}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'>{readonly}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'>{writeonly}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'>{browseable}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%;text-align:center'>{enabled}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";	
	
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$html[]="<tr class='$TRCLASS' id='row-parent-ashare'>";
	$html[]="<td><H3>/usr/share/artica-postfix</H3><div style='margin-top:-5px'><small>For replicating/update Artica trough rsync</small></div></td>";
	$html[]="<td class=\"center\"><i class='fas fa-check'></i></td>";
	$html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";
	$html[]="<td class=\"center\"><i class='fas fa-check'></i></td>";
	$html[]="<td class=\"center\"><i class='fas fa-check'></i></td>";
	$html[]="<td class=\"center\" style='width:1%'>&nbsp;</td>";
	$html[]="</tr>";

    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    if($EnableGeoipUpdate==1) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $html[] = "<tr class='$TRCLASS' id='row-parent-ashare'>";
        $html[] = "<td><H3>/usr/local/share/GeoIP</H3><div style='margin-top:-5px'><small>{geo_location} {databases}</small></div></td>";
        $html[] = "<td class=\"center\"><i class='fas fa-check'></i></td>";
        $html[] = "<td class=\"center\">" . $tpl->icon_nothing() . "</td>";
        $html[] = "<td class=\"center\"><i class='fas fa-check'></i></td>";
        $html[] = "<td class=\"center\"><i class='fas fa-check'></i></td>";
        $html[] = "<td class=\"center\" style='width:1%'>&nbsp;</td>";
        $html[] = "</tr>";
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $html[] = "<tr class='$TRCLASS' id='row-parent-ashare'>";
        $html[] = "<td><H3>/usr/share/xt_geoip</H3><div style='margin-top:-5px'><small>{geo_location} {databases} Firewall</small></div></td>";
        $html[] = "<td class=\"center\"><i class='fas fa-check'></i></td>";
        $html[] = "<td class=\"center\">" . $tpl->icon_nothing() . "</td>";
        $html[] = "<td class=\"center\"><i class='fas fa-check'></i></td>";
        $html[] = "<td class=\"center\"><i class='fas fa-check'></i></td>";
        $html[] = "<td class=\"center\" style='width:1%'>&nbsp;</td>";
        $html[] = "</tr>";

    }
	
	
	$EnableWsusOffline=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWsusOffline"));
	if($EnableWsusOffline==1){
		$wsusofflineStorageDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineStorageDir"));
		if($wsusofflineStorageDir==null){$wsusofflineStorageDir="/usr/share/wsusoffline";}
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='row-parent-wsufoff'>";
		$html[]="<td><H3>$wsusofflineStorageDir/client</H3><div style='margin-top:-5px'><small>WsusOffline replication</small></div></td>";
		$html[]="<td class=\"center\"><i class='fas fa-check'></i></td>";
		$html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";
		$html[]="<td class=\"center\"><i class='fas fa-check'></i></td>";
		$html[]="<td class=\"center\"><i class='fas fa-check'></i></td>";
		$html[]="<td class=\"center\" style='width:1%'&nbsp;</td>";
		$html[]="</tr>";
	}
	
	$sql="SELECT *  FROM rsyncd_folders ORDER BY directory";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$directory=$ligne["directory"];
		$comment=$ligne["comment"];
		$md=md5(serialize($ligne));
		$readonly=$tpl->icon_check($ligne["readonly"],"Loadjs('$page?readonly-js={$ligne["ID"]}')",null,"AsSystemAdministrator");
		$writeonly=$tpl->icon_check($ligne["writeonly"],"Loadjs('$page?writeonly-js={$ligne["ID"]}')",null,"AsSystemAdministrator");
		$listable=$tpl->icon_check($ligne["listable"],"Loadjs('$page?listable-js={$ligne["ID"]}')",null,"AsSystemAdministrator");
		$enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js={$ligne["ID"]}')",null,"AsSystemAdministrator");
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><H3>". $tpl->td_href($directory,"{click_to_edit}","Loadjs('$page?folder-js={$ligne["ID"]}');")."</H3><div style='margin-top:-5px'><small>$comment</small></div></td>";
		$html[]="<td class=\"center\" style='width:1%'>$readonly</td>";
		$html[]="<td class=\"center\" style='width:1%'>$writeonly</td>";
		$html[]="<td class=\"center\" style='width:1%'>$listable</td>";
		$html[]="<td class=\"center\" style='width:1%'>$enabled</td>";
		$html[]="<td class=\"center\" style='width:1%'&nbsp;</td>";
		$html[]="</tr>";
		
		
		
	}
	$js=array();
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
	$headsjs
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-rsyncdfolders-list').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	";
	$html[]=@implode("\n", $js)."</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
