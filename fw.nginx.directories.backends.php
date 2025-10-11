<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["id-js"])){id_js();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["ID"])){id_save();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["refresh-interface"])){refresh_interface();exit;}

table_start();


function suffix_RQ():string{
    $serviceid=0;
    $directory_id=0;
    $md5="";
    if(isset($_GET["directory_id"])) {
        $directory_id = intval($_GET["directory_id"]);
    }
    if(isset($_GET["serviceid"])) {
        $serviceid = intval($_GET["serviceid"]);
    }
    if(isset($_GET["md5"])) {
        $md5 = $_GET["md5"];
    }
    return "directory_id=$directory_id&serviceid=$serviceid&md5=$md5";
}

function table_id():string{
    $serviceid=0;
    $directory_id=0;
    if(isset($_GET["directory_id"])) {
        $directory_id = intval($_GET["directory_id"]);
    }
    if(isset($_GET["serviceid"])) {
        $serviceid = intval($_GET["serviceid"]);
    }
    return "backends-reverse-$directory_id-$serviceid";
}

function refresh_interface():bool{
    $page=CurrentPageName();
    $directory_id=intval($_GET["directory_id"]);
    $serviceid=intval($_GET["serviceid"]);
    $function="";
    if(isset($_GET["function"])){$function=$_GET["function"];}
    $f[]="LoadAjax('ngx_directories_access_module-$directory_id','fw.nginx.directories.php?table=$directory_id');";
    $suffiux=suffix_RQ();
    $f[]="LoadAjax('backends-reverse-$directory_id-$serviceid','$page?table=yes&$suffiux');";
    if(strlen($function)>2){
        $f[]="$function();";
    }
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;
}

function table_start():bool{
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $page=CurrentPageName();
    $suffix_RQ=suffix_RQ();
    $id=table_id();
    $js="LoadAjax('$id','$page?table=yes&$suffix_RQ');";
    $jsenc=base64_encode($js);
    echo "<div id='$id'></div>
	<script>LoadAjax('$id','$page?table=yes&$suffix_RQ&refreshjs=$jsenc&function=$function');</script>";
    return true;
}
function delete_js():bool{
    $tpl=new template_admin();
    $ID=$_GET["delete"];
    $md5=$_GET["md"];
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT `hostname`,`port` ,`serviceid`,`directory_id` FROM  directories_backends WHERE ID=$ID");
    $serviceid=$ligne["serviceid"];
    $directory_id=$ligne["directory_id"];
    $servicename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);

    if(strlen($function)>2){
        $function="$function();";
    }

    $js2="LoadAjax('ngx_directories_access_module-$directory_id','fw.nginx.directories.php?table=$directory_id');";
    return $tpl->js_confirm_delete("{$ligne["hostname"]}:{$ligne["port"]} $servicename/$dirname", "delete", "$ID","$('#$md5').remove();$js2;Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');$function");
}
function  delete():bool{
    $ID=$_POST["delete"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT `hostname`,`serviceid`,`directory_id` FROM  directories_backends WHERE ID=$ID");
    $hostname=$ligne["hostname"];
    $serviceid=$ligne["serviceid"];
    $directory_id=$ligne["directory_id"];
    $servicename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);
    admin_tracks("delete backend $hostname from path $dirname on site $servicename");
    $q=new lib_sqlite(NginxGetDB());
    $q->QUERY_SQL("DELETE FROM `directories_backends` WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;}
    return true;
}


function id_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["id-js"];
    $title="{backend}: $ID";
    if($ID==0){$title="{new_entry}";}
    $suffix_RQ=suffix_RQ();
    return $tpl->js_dialog2($title, "$page?id-popup=$ID&$suffix_RQ");
}
function id_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["id-popup"];
    $serviceid=$_GET["serviceid"];
    $directory_id=$_GET["directory_id"];
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $q=new lib_sqlite(NginxGetDB());
    $servicename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);
    $title="$servicename/$dirname: {new_item}";
    $options=array();
    $ligne=$q->mysqli_fetch_array("SELECT `type` FROM nginx_services WHERE ID=$serviceid");
    $Type=intval($ligne["type"]);
    $btname="{add}";

    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM directories_backends WHERE ID=$ID");
        $btname="{apply}";
        $title="$servicename/$dirname: {$ligne["hostname"]}:{$ligne["port"]}";
        $serviceid=$ligne["serviceid"];
    }
    $suffix=suffix_RQ();
    $js="dialogInstance2.close();Loadjs('$page?refresh-interface=yes&$suffix');Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');";
    if(isset($ligne["options"])) {
        $options = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
    }

    if($ID==0){
        if($Type==2){$ligne["port"]=80;}
        if($ligne["root"]==null){$ligne["root"]="/";}
    }
    if(!isset($options["UseSSL"])){
        $options["UseSSL"]=0;
    }

    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("serviceid", $serviceid);
    $form[]=$tpl->field_hidden("directory_id", $directory_id);
    $form[]=$tpl->field_text("hostname", "{hostname}/{ipaddr}", $ligne["hostname"]);
    $form[]=$tpl->field_text("root", "{TargetRemotePath}", $ligne["root"]);
    $form[]=$tpl->field_numeric("port","{port}",$ligne["port"]);
    if($Type==2){
        $form[]=$tpl->field_checkbox("UseSSL","{UseSSL}",$options["UseSSL"]);

    }
    if(strlen($function)>2){
        $function="$function();";
    }

    echo $tpl->form_outside($title, $form,"",$btname,"$js;$function","AsSystemWebMaster");
    return true;
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function get_directoryname($ID):string{
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT directory FROM ngx_directories WHERE ID=$ID");
    return $ligne["directory"];
}

function id_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["ID"];
    $serviceid=intval($_POST['serviceid']);
    $directory_id=intval($_POST["directory_id"]);
    $q=new lib_sqlite(NginxGetDB());
    if($serviceid==0){echo $tpl->post_error("Service ID missing or null");return false;}
    if($directory_id==0){echo $tpl->post_error("Path ID missing or null");return false;}
    $hostname=$_POST["hostname"];
    $port=intval($_POST["port"]);
    $options=base64_encode(serialize($_POST));
    $root=$tpl->CLEAN_BAD_XSS($_POST["root"]);

    $servicename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);
    admin_tracks_post("Save/edit backend for path $dirname in site $servicename");

    if($ID==0){
        $sql="INSERT OR IGNORE INTO directories_backends (serviceid,directory_id,hostname,port,options,root) 
    	VALUES ($serviceid,$directory_id,'$hostname',$port,'$options','$root')";

        $q->QUERY_SQL($sql);
        writelogs($sql);
        if(!$q->ok){echo $tpl->post_error($q->mysql_error);}
        return true;
    }

    $q->QUERY_SQL("UPDATE directories_backends SET hostname='$hostname',
    port='$port',root='$root', options='$options' WHERE ID=$ID");
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);}
    return true;

}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $directory_id=intval($_GET["directory_id"]);
    $serviceid=intval($_GET["serviceid"]);
    $suffix=suffix_RQ();
    $forcediv="compile-site-id-$serviceid-$directory_id";

    $topbuttons[] = array("Loadjs('$page?id-js=0&$suffix');",
        ico_plus,"{new_backend}");

    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="</div>";
    $html[]="<div id='$forcediv'></div>";
    $html[]="<table id='table-backends-{$_GET["table"]}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{port}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $q=new lib_sqlite(NginxGetDB());

    $results=$q->QUERY_SQL("SELECT * FROM  directories_backends WHERE directory_id='$directory_id'");
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return true;}



    $TRCLASS=null;
    $suffix=suffix_RQ();
    foreach ($results as $md5=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne).$md5);
        $ID=intval($ligne["ID"]);
        $port=$ligne["port"];
        $hostname=$ligne["hostname"];
        $js="Loadjs('$page?id-js=$ID&$suffix&md=$md&function=$function')";
        $options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]); //UseSSL
        $UseSSL=$options["UseSSL"];
        $proto="http";
        if($UseSSL==1){$proto="https";}
        $hostname="$proto://$hostname:$port".$ligne["root"];

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td nowrap>".$tpl->td_href("$hostname",null,$js)."</td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->td_href($port,null,$js)."</td>";
        $html[]="<td style='width:1%' class='center'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&$suffix&md=$md&function=$function')","AsSystemWebMaster")."</td>";
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
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-backends-{$_GET["table"]}').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"false\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}