<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.nginx.params.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}


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
    $ligne=fetch_directory_backend_by_id($ID);
    $serviceid=isset($ligne["serviceid"])?intval($ligne["serviceid"]):0;
    $directory_id=isset($ligne["directory_id"])?intval($ligne["directory_id"]):0;
    $hostname=isset($ligne["hostname"])?$ligne["hostname"]:"";
    $port=isset($ligne["port"])?$ligne["port"]:"";
    $servicename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);

    if(strlen($function)>2){
        $function="$function();";
    }

    $js2="LoadAjax('ngx_directories_access_module-$directory_id','fw.nginx.directories.php?table=$directory_id');";
    return $tpl->js_confirm_delete("$hostname:$port $servicename/$dirname", "delete", "$ID","$('#$md5').remove();$js2;Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');$function");
}
function  delete():bool{
    $ID=intval($_POST["delete"]);
    $ligne=fetch_directory_backend_by_id($ID);
    $hostname=isset($ligne["hostname"])?$ligne["hostname"]:"#$ID";
    $serviceid=isset($ligne["serviceid"])?intval($ligne["serviceid"]):0;
    $directory_id=isset($ligne["directory_id"])?intval($ligne["directory_id"]):0;
    $servicename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);
    admin_tracks("delete backend $hostname from path $dirname on site $servicename");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"delete","table"=>"directories_backends","where"=>array("ID"=>"$ID")));
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
    $servicename=get_servicename($serviceid);
    $dirname=get_directoryname($directory_id);
    $title="$servicename/$dirname: {new_item}";
    $options=array();
    $ligne=array();
    $Type=fetch_nginx_service_type($serviceid);
    $btname="{add}";

    if($ID>0){
        $ligne=fetch_directory_backend_by_id($ID);
        $btname="{apply}";
        $hostname0=isset($ligne["hostname"])?$ligne["hostname"]:"#$ID";
        $port0=isset($ligne["port"])?$ligne["port"]:"";
        $title="$servicename/$dirname: $hostname0:$port0";
        $serviceid=isset($ligne["serviceid"])?intval($ligne["serviceid"]):$serviceid;
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
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
function get_directoryname($ID):string{
    $row=fetch_directory_row($ID);
    return isset($row["directory"]) ? strval($row["directory"]) : "";
}

function id_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["ID"];
    $serviceid=intval($_POST['serviceid']);
    $directory_id=intval($_POST["directory_id"]);
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
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"insert","table"=>"directories_backends","values"=>array("serviceid"=>"$serviceid","directory_id"=>"$directory_id","hostname"=>"$hostname","port"=>"$port","options"=>"$options","root"=>"$root")));
        return true;
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"update","table"=>"directories_backends","set"=>array("hostname"=>"$hostname","port"=>"$port","root"=>"$root","options"=>"$options"),"where"=>array("ID"=>"$ID")));
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
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{port}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $results=fetch_directory_backends_for_directory($directory_id);



    $TRCLASS=null;
    $suffix=suffix_RQ();
    foreach ($results as $md5=>$ligne){
        $TRCLASS=null;
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

    $html[]="</tbody></table></div></div></div>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body($html);
    return true;
}

