<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["database-js"])){database_mysql_js();exit;}
if(isset($_GET["database-popup"])){database_popup();exit;}
if(isset($_POST["create-database"])){database_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["delete-database"])){database_delete_js();exit;}
if(isset($_POST["delete-database"])){database_delete();exit;}
if(isset($_GET["database-tabs"])){database_tabs();exit;}
if(isset($_GET["database-databases"])){database_databases();exit;}
if(isset($_GET["database-databases-list"])){database_databases_list();exit;}
if(isset($_GET["database-link-js"])){database_link_js();exit;}
if(isset($_GET["database-link-popup"])){database_link_popup();exit;}
if(isset($_GET["database-link-perform"])){database_link_perform();exit;}
table_start();

function database_mysql_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["database-js"];
	$MAINDEF=unserialize(base64_decode($MAIN));
	$title="{new_database}";

	if(isset($MAINDEF["database"])){
		$title="{database} {$MAINDEF["database"]}";
		$tpl->js_dialog1($title, "$page?database-tabs=$MAIN");
		return;
	}
	
	$tpl->js_dialog1($title, "$page?database-popup=$MAIN");
}
function database_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["database-tabs"];
	$array["{databases}"]="$page?database-popup=$MAIN";
	$array["{members}"]="$page?database-members=$MAIN";
	echo $tpl->tabs_default($array);
}
function database_link_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["database-link-js"];
	$MAINDEF=unserialize(base64_decode($MAIN));
	$user=$MAINDEF["user"];
	$host=$MAINDEF["host"];
	$tpl->js_dialog2("{member} {$MAINDEF["user"]}@{$MAINDEF["host"]} {link_database}","$page?database-link-popup=$MAIN");
}

function database_link_perform(){
	$page=CurrentPageName();
	$MAIN=$_GET["database-link-perform"];
	$md=$_GET["md"];
	$tpl=new template_admin();
	$MAINDEF=unserialize(base64_decode($MAIN));
	$database=$_GET["database"];
	$user=$MAINDEF["user"];
	$host=$MAINDEF["host"];
	$q=new mysql();
	$q->QUERY_SQL("GRANT ALL PRIVILEGES ON $database.* TO '$user'@'$host';");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	header("content-type: application/x-javascript");
	echo "$('#$md').remove();\n";
	echo "LoadAjax('database-databases-list','$page?database-databases-list={$MAIN}')";
	
	
}



function database_delete_js():bool{
	$tpl=new template_admin();
	$database=$_GET["delete-database"];
    $md=$_GET["md"];
	return $tpl->js_confirm_delete("$database", "delete-database", "$database","$('#$md').remove()");
}
function database_delete():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $database=$_POST["delete-database"];
    $GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/database/delete/$database");
    return admin_tracks("Remove MySQL database $database");
}


function database_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$database=$_GET["database-popup"];
	$title="{new_database}";
	$btn="{add}";
	$jsafter="LoadAjax('table-mysql-list','$page?table=yes');dialogInstance1.close();";
    $form[]=$tpl->field_text("create-database","{database}",null,true);
	echo $tpl->form_outside($title, $form,null,$btn,$jsafter,"AsDatabaseAdministrator");
	return true;
	
}


function database_save():bool{
	
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $database=$_POST["create-database"];

    if(!$GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/database/create/$database")){
        echo $tpl->post_error($GLOBALS["CLASS_SOCKETS"]->mysql_error);
        return false;
    }

	return admin_tracks("Create a new MySQL database $database");
	
}


function table_start():bool{
	$page=CurrentPageName();
	echo "<div id='table-mysql-list'></div><script>LoadAjax('table-mysql-list','$page?table=yes');</script>";
    return true;
}


function table(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$add="Loadjs('$page?database-js=');";
	

	$html[]="<table id='table-mysql-privs' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:14px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >{databases}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' >{size}</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$TRCLASS=null;

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/databases");
    VERBOSE($data,__LINE__);
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());
        return false;
    }

    $NODEL["mysql"]=true;
    $NODEL["performance_schema"]=true;
    $NODEL["sys"]=true;
    $NODEL["information_schema"]=true;



	foreach ($json->Databases as $index=>$JsOnUser){

        $ligne = get_object_vars($JsOnUser);
        $delete = null;
		$dbname=trim($ligne["Name"]);
		$Size=FormatBytes($ligne["Size"]/1024);
		$md=md5(serialize($ligne));

        if(!isset($NODEL[$dbname])){
            $delete=$tpl->icon_delete("Loadjs('$page?delete-database=$dbname&md=$md')","AsDatabaseAdministrator");

        }

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><i class='".ico_database."'></i>&nbsp;$dbname</td>";
		$html[]="<td width=1%>$Size</td>";
		$html[]="<td width=1%>$delete</td>";
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

    $topbuttons[] = array($add, ico_plus, "{new_database}");
    $APP_MYSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_MYSQL} v$APP_MYSQL_VERSION {databases}";
    $TINY_ARRAY["ICO"]=ico_admin;
    $TINY_ARRAY["EXPL"]="{APP_MYSQL_ABOUT}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-mysql-privs').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
	$jstiny
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function database_databases(){
	$page=CurrentPageName();
	echo "<div id='database-databases-list'></div><script>LoadAjax('database-databases-list','$page?database-databases-list={$_GET["database-databases"]}');</script>";
	
}

function database_databases_list(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["database-databases-list"];
	$MAINDEF=unserialize(base64_decode($MAIN));
	$user=$MAINDEF["user"];
	$host=$MAINDEF["host"];
	
	
	
	$add="Loadjs('$page?database-link-js=$MAIN');";
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fas fa-link'></i> {link_database} </label>";
	$html[]="</div>";
	
	
	$html[]="<table id='table-mysql-privs-database' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >{database}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{write}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{read}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize'><center>{admin}</center></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$TRCLASS=null;
	$results=$q->QUERY_SQL("SELECT * FROM `db` WHERE `User`='$user' AND `Host`='$host' ORDER BY `Db`","mysql");
	if(!$q->ok){echo $tpl->FATAl_ERROR_SHOW_128($q->mysql_error);return;}
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$database=trim($ligne["Db"]);
		$md=md5(serialize($ligne));
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$admin=$tpl->icon_nothing();
		$write=$tpl->icon_nothing();
		$read=$tpl->icon_nothing();
		if(ifIsAdmin($ligne)){$admin="<i class='fas fa-check'></i>";}
		if(ifIsWrite($ligne)){$write="<i class='fas fa-check'></i>";}
		if(ifIsRead($ligne)){$read="<i class='fas fa-check'></i>";}
	
		$MAINJS["user"]=$user;
		$MAINJS["host"]=$host;
		$MAINJS["database"]=$database;
		$MAINJS["md"]=$md;
	
		$MAINENC=base64_encode(serialize($MAINJS));
	
		$delete=$tpl->icon_delete("Loadjs('$page?delete-user-database=$MAINENC')","AsDatabaseAdministrator");
		if($user=="root" AND $host=="localhost"){$delete=$tpl->icon_nothing();}
		$js="Loadjs('$page?database-database-js=$MAINENC')";
	
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>$database</td>";
		$html[]="<td width=1%><center>$write</center></td>";
		$html[]="<td width=1%><center>$read</center></td>";
		$html[]="<td width=1%><center>$admin</center></td>";
		$html[]="<td width=1%><center>$delete</center></td>";
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
	$(document).ready(function() { $('#table-mysql-privs-database').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
		
	
	
}

function database_link_popup(){
	
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["database-link-popup"];
	$MAINDEF=unserialize(base64_decode($MAIN));
	$user=$MAINDEF["user"];
	$host=$MAINDEF["host"];
	
	
	
	
	$results=$q->QUERY_SQL("SELECT `Db` FROM `db` WHERE `User`='$user' AND `Host`='$host' ORDER BY `Db`","mysql");
	if(!$q->ok){echo $tpl->FATAl_ERROR_SHOW_128($q->mysql_error);return;}
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$DTBZ[$ligne["Db"]]=true;
	}
	
	$HIDEDB["performance_schema"]=True;
	$HIDEDB["information_schema"]=True;
	
	
	$html[]="<table id='table-mysql-link-database' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >{database}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'><center>{select}</center></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;
	$results=$q->QUERY_SQL("SHOW DATABASES;","mysql");
	if(!$q->ok){echo $tpl->FATAl_ERROR_SHOW_128($q->mysql_error);return;}
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$database=trim($ligne["Database"]);
		if(isset($DTBZ[$database])){continue;}
		if(isset($HIDEDB[$database])){continue;}
		$md=md5(serialize($ligne));
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$link=null;
		$database_enc=urlencode($database);
		$link=$tpl->icon_select("Loadjs('$page?database-link-perform=$MAIN&database=$database_enc&md=$md')","AsDatabaseAdministrator");
		
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>$database</td>";
		$html[]="<td width=1%><center>$link</center></td>";
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
	$html[]="</table>";
	$html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-mysql-link-database').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}



function ifIsAdmin($ligne){
	$f["Select_priv"]=true;
	$f["Insert_priv"]=true;
	$f["Update_priv"]=true;
	$f["Delete_priv"]=true;
	$f["Create_priv"]=true;
	$f["Drop_priv"]=true;
	$f["Grant_priv"]=true;
	$f["References_priv"]=true;
	$f["Index_priv"]=true;
	$f["Alter_priv"]=true;
	$f["Create_tmp_table_priv"]=true;
	$f["Lock_tables_priv"]=true;
	$f["Show_view_priv"]=true;
	$f["Create_view_priv"]=true;

    foreach ($f as $num=>$none){
        //echo "{$ligne["User"]}: $num == {$ligne[$num]}\n<br>";
		if(strtolower($ligne[$num])<>"y"){
			VERBOSE("ifIsAdmin '$num' -- {$ligne[$num]} FALSE",__LINE__);
			return false;
        }

	}
	return true;
}
function ifIsWrite($ligne):bool{

	$f["Insert_priv"]=true;
	$f["Update_priv"]=true;
	$f["Delete_priv"]=true;
	$f["Create_priv"]=true;
	$f["Drop_priv"]=true;
	$f["Index_priv"]=true;
	$f["Alter_priv"]=true;
	$f["Create_tmp_table_priv"]=true;
	$f["Create_view_priv"]=true;
	$f["Show_view_priv"]=true;
    foreach ($f as $num=>$none){
		if(strtolower($ligne[$num])<>"y"){
			VERBOSE("ifIsWrite '$num' -- {$ligne[$num]} FALSE",__LINE__);
			return false;}

	}
	return true;
}

function ifIsRead($ligne):bool{
	$f["Select_priv"]=true;
    foreach ($f as $num=>$none){
		if(strtolower($ligne[$num])<>"y"){VERBOSE("ifIsRead '$num' -- {$ligne[$num]} FALSE",__LINE__);return false;}

	}
	return true;
}
