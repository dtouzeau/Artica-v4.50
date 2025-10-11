<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["member-js"])){member_mysql_js();exit;}
if(isset($_GET["member-popup"])){member_popup();exit;}
if(isset($_POST["User2"])){member_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["delete-user"])){member_delete_js();exit;}
if(isset($_GET["delete-user-database"])){database_unlink_js();exit;}
if(isset($_POST["delete-user"])){member_delete();exit;}
if(isset($_GET["member-tabs"])){member_tabs();exit;}
if(isset($_GET["member-databases"])){member_databases();exit;}
if(isset($_GET["member-databases-list"])){member_databases_list();exit;}
if(isset($_GET["database-link-js"])){database_link_js();exit;}
if(isset($_GET["database-link-popup"])){database_link_popup();exit;}
if(isset($_GET["database-link-perform"])){database_link_perform();exit;}



table_start();

function member_mysql_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["member-js"];
	$MAINDEF=unserialize(base64_decode($MAIN));
	$title="{new_member}";

	if(isset($MAINDEF["user"])){
		$title="{member} {$MAINDEF["user"]}@{$MAINDEF["host"]}";
		$tpl->js_dialog1($title, "$page?member-tabs=$MAIN");
		return;
	}
	
	$tpl->js_dialog1($title, "$page?member-popup=$MAIN");
	
	
}
function member_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["member-tabs"];
	$array["{member}"]="$page?member-popup=$MAIN";
	$array["{databases}"]="$page?member-databases=$MAIN";
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

function database_link_perform():bool{
	$page=CurrentPageName();
	$MAIN=$_GET["database-link-perform"];
	$md=$_GET["md"];
	$tpl=new template_admin();
	$MAINDEF=unserialize(base64_decode($MAIN));
	$database=$_GET["database"];
	$user=$MAINDEF["user"];
	$host=$MAINDEF["host"];


    if(!$GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/database/grant/$database/$user/$host")){
        $tpl->js_mysql_alert($GLOBALS["CLASS_SOCKETS"]->mysql_error);
        return false;
    }
	header("content-type: application/x-javascript");
	echo "$('#$md').remove();\n";
	echo "LoadAjax('member-databases-list','$page?member-databases-list={$MAIN}')";
    return admin_tracks("MySQL Grant $database to $user@$host");
}

function database_unlink_js():bool{
    $MAIN=$_GET["delete-user-database"];
    $MAINDEF=unserialize(base64_decode($MAIN));
    $user=$MAINDEF["user"];
    $host=$MAINDEF["host"];
    $md=$MAINDEF["md"];
    $database=$MAINDEF["database"];
    $tpl=new template_admin();

    VERBOSE("mysql/database/revoke/$database/$user/$host",__LINE__);
    if(!$GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/database/revoke/$database/$user/$host")){
        $tpl->js_mysql_alert($GLOBALS["CLASS_SOCKETS"]->mysql_error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    return admin_tracks("MySQL Revoke $database to $user@$host");
}



function member_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["delete-user"];
	$MAINDEF=unserialize(base64_decode($MAIN));
	$user=$MAINDEF["user"];
	$host=$MAINDEF["host"];
	$md=$MAINDEF["md"];
	$MAINDEF["host"]=str_replace("%", "*", $MAINDEF["host"]);
	$tpl->js_confirm_delete("{$MAINDEF["user"]}@{$MAINDEF["host"]}", "delete-user", "$user@$host","$('#$md').remove()");
}
function member_delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$tbl=explode("@",$_POST["delete-user"]);
	$User=$tbl[0];
	$Host=$tbl[1];



	$q->QUERY_SQL("DELETE  FROM `db` WHERE `Host`='$Host' AND `User`='$User'","mysql");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DROP USER '$User'@'$Host'","mysql");
	if(!$q->ok){echo $q->mysql_error;return;}
}


function member_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["member-popup"];
	$MAINDEF=unserialize(base64_decode($MAIN));
	$title="{new_member}";
	$btn="{add}";
	$jsafter="LoadAjax('table-mysql-list','$page?table=yes');";
    $isAdmin=false;

	if(isset($MAINDEF["user"])){
        $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/user/{$MAINDEF["user"]}/{$MAINDEF["host"]}");
        $JsOnUser=json_decode($data);
        $ligne = get_object_vars($JsOnUser);
		$isAdmin=ifIsAdmin($ligne);
	}
	
	$form[]=$tpl->field_hidden("User2", $ligne["User"]);
	if(!isset($ligne["User"])){
		$form[]=$tpl->field_text("User", "{member}",null,true);
		$form[]=$tpl->field_text("Host", "{host}", null,true);
		$jsafter=$jsafter."dialogInstance1.close();";
	}else{
		$btn="{apply}";
		$title="{$ligne["User"]}@{$ligne["Host"]}";
		
		$form[]=$tpl->field_info("User", "{member}", $ligne["User"]);
		$form[]=$tpl->field_info("Host", "{host}",$ligne["Host"]);
	}
	
	$form[]=$tpl->field_checkbox("isAdmin","{mysql_global_admin}",$isAdmin);
	$form[]=$tpl->field_password2("password","{password}", null,true);
	
	echo $tpl->form_outside($title, $form,"{mysql_explain_create_user}",$btn,$jsafter,"AsDatabaseAdministrator");
	return true;
	
}


function member_save():bool{
	
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	$_POST["User"]=str_replace("'", "", $_POST["User"]);
	$_POST["User"]=str_replace(" ", "", $_POST["User"]);
	$_POST["User"]=str_replace("\"", "", $_POST["User"]);
    $_POST["Host"]=str_replace("*", "%", $_POST["Host"]);

    $isAdmin=$_POST["isAdmin"];
    $PostData["User"]=$_POST["User"];
    $PostData["Host"]=$_POST["Host"];

	

    $PostData["Password"]=$_POST["Password"];
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST("mysql/users/create",$PostData);
    $json=json_decode($data);
    if(!$json->Status){
        echo  $tpl->js_mysql_alert($json->Error);
        return false;
    }

	if($isAdmin==1){
        $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST("mysql/users/grant",$PostData);
        $json=json_decode($data);
        if(!$json->Status){
            echo  $tpl->js_mysql_alert($json->Error);
            return false;
        }
	}else{
        $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST("mysql/users/revoke",$PostData);
        $json=json_decode($data);
        if(!$json->Status){
            echo  $tpl->js_mysql_alert($json->Error);
            return false;
        }
	}
	
	
	return true;
	
}


function table_start():bool{
	$page=CurrentPageName();
	echo "<div id='table-mysql-list'></div><script>LoadAjax('table-mysql-list','$page?table=yes');</script>";
    return true;
}


function table(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$add="Loadjs('$page?member-js=');";
	

	$html[]="<table id='table-mysql-privs' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:14px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >{host}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' >{member}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{write}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{read}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize'><center>{admin}</center></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$TRCLASS=null;

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/users");
    VERBOSE($data,__LINE__);
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());
        return false;
    }

	foreach ($json->Users as $index=>$JsOnUser){

        $ligne = get_object_vars($JsOnUser);

		$Host=trim($ligne["Host"]);
		$User=$ligne["User"];
		$md=md5(serialize($ligne));
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$admin=$tpl->icon_nothing();
		$write=$tpl->icon_nothing();
		$read=$tpl->icon_nothing();
		if(ifIsAdmin($ligne)){
			$admin="<i class='fas fa-check'></i>";
		}
		if(ifIsWrite($ligne)){
			$write="<i class='fas fa-check'></i>";
		}
		
		if(ifIsRead($ligne)){
			$read="<i class='fas fa-check'></i>";
		}
		
		$MAINJS["user"]=$User;
		$MAINJS["host"]=$Host;
		$MAINJS["md"]=$md;
		
		$MAINENC=base64_encode(serialize($MAINJS));
		
		$delete=$tpl->icon_delete("Loadjs('$page?delete-user=$MAINENC')","AsDatabaseAdministrator");
		if($User=="root" AND $Host=="localhost"){$delete=$tpl->icon_nothing();}
        if($User=="mariadb.sys" AND $Host=="localhost"){$delete=$tpl->icon_nothing();}


		$js="Loadjs('$page?member-js=$MAINENC')";
		
		$Host=str_replace("%", "*", $Host);
		if($Host=="*"){$Host="{AllSystems}";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% nowrap>$Host</td>";
		$html[]="<td><i class='".ico_admin."'></i>&nbsp;".$tpl->td_href($User,null,$js)."</td>";
		$html[]="<td width=1%><center>$write</center></td>";
		$html[]="<td width=1%><center>$read</center></td>";
		$html[]="<td width=1%><center>$admin</center></td>";
		$html[]="<td width=1%><center>$delete</center></td>";
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

    $topbuttons[] = array($add, ico_plus, "{new_member}");
    $APP_MYSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_MYSQL} v$APP_MYSQL_VERSION {members}";
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

function member_databases(){
	$page=CurrentPageName();
	echo "<div id='member-databases-list'></div><script>LoadAjax('member-databases-list','$page?member-databases-list={$_GET["member-databases"]}');</script>";
	
}

function member_databases_list(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["member-databases-list"];
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

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/user/$user/$host/privileges");
    VERBOSE("DATA:$data",__LINE__);
    $json=json_decode($data);
    if (json_last_error()== JSON_ERROR_NONE) {
        foreach ($json->Databases as $index=>$JsOnUser){
            $ligne = get_object_vars($JsOnUser);
            $database=trim($ligne["DatabaseName"]);
            if($database==null){continue;}
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
            $js="Loadjs('$page?member-database-js=$MAINENC')";

            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td>$database</td>";
            $html[]="<td width=1%><center>$write</center></td>";
            $html[]="<td width=1%><center>$read</center></td>";
            $html[]="<td width=1%><center>$admin</center></td>";
            $html[]="<td width=1%><center>$delete</center></td>";
            $html[]="</tr>";

        }
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
return true;
		
	
	
}

function database_link_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MAIN=$_GET["database-link-popup"];
	$MAINDEF=unserialize(base64_decode($MAIN));
	$user=$MAINDEF["user"];
	$host=$MAINDEF["host"];


    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/user/$user/$host/privileges");
    VERBOSE($data,__LINE__);
    $json=json_decode($data);
    if (json_last_error()== JSON_ERROR_NONE) {
        foreach ($json->Databases as $index=>$JsOnUser){
            $ligne = get_object_vars($JsOnUser);
            $database = trim($ligne["DatabaseName"]);
            $DTBZ[$database] = true;
        }
    }
	


	
	
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
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("mysql/databases");
    VERBOSE($data,__LINE__);
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());
        return false;
    }

    $HIDEDB["mysql"]=true;
    $HIDEDB["sys"]=true;
    $HIDEDB["performance_schema"]=True;
    $HIDEDB["information_schema"]=True;


    foreach ($json->Databases as $index=>$JsOnUser){
        $ligne = get_object_vars($JsOnUser);
		$database=trim($ligne["Name"]);
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
