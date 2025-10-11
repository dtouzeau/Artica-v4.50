<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");

if(isset($_GET["template-import"])){template_import_js();exit;}
if(isset($_GET["template-import-popup"])){template_import_popup();exit;}
if(isset($_GET["file-uploaded"])){template_import_uploaded();exit;}

if(isset($_POST["restore"])){restore_save();exit;}
if(isset($_GET["restore-js"])){restore_js();exit;}
if(isset($_GET["restore-popup"])){restore_popup();exit;}
if(isset($_GET["restore-saved"])){restore_saved();exit;}

if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["template-remove"])){template_remove_js();exit;}
if(isset($_POST["template-remove"])){template_remove_perform();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["template-js"])){template_js();exit;}
if(isset($_GET["template-popup"])){template_popup();exit;}
if(isset($_POST["templateid"])){template_save();exit;}
if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}
if(isset($_GET["download"])){download();exit;}
service_js();


function service_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog4("{template_manager}","$page?popup-main=yes&function=$function");
}
function restore_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tplid=intval($_GET["restore-js"]);
    $function=$_GET["function"];
    $function0=$_GET["function0"];
    return $tpl->js_dialog5("{template_manager}:{restore}","$page?restore-popup=$tplid&function=$function&function0=$function0");

}
function template_import_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $function = $_GET["function"];
    return $tpl->js_dialog5("{template_manager}:{import_template}", "$page?template-import-popup=yes&function=$function",600);
}
function template_js():bool{
    $ID  = intval($_GET["template-js"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{template}: #$ID";
    if($ID==0){$title="{new_template}";}
    return $tpl->js_dialog5($title,"$page?template-popup=$ID&function=$function");

}

function template_remove_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $md=$_GET["md"];
    $tplid=intval($_GET["template-remove"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT tpname FROM nginx_templates WHERE ID=$tplid");
    $tpname=$ligne["tpname"];
    return $tpl->js_confirm_delete($tpname,"template-remove",$tplid,"$('#$md').remove();");
}
function template_remove_perform():bool{
    $tplid=intval($_POST["template-remove"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT tpname FROM nginx_templates WHERE ID=$tplid");
    $tpname=$ligne["tpname"];
    $q->QUERY_SQL("DELETE FROM nginx_templates WHERE ID=$tplid");
    return admin_tracks("Removed reverse-proxy template $tpname configuration");
}

function template_import_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $function=$_GET["function"];
    $bt_upload=$tpl->button_upload("{upload_template}",$page,null,"&function=$function")."&nbsp;&nbsp;";
    $explain=$tpl->div_explain("{upload_template}||{upload_template_explain}<p>");
    $html="<div id='ca-form-import'>
        <div class='center'>$bt_upload</div></div>
	    <div style='margin-top:20px'>$explain</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function template_import_uploaded():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;$tpl->CLUSTER_CLI=true;
    $file=$_GET["file-uploaded"];
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";
    $function=$_GET["function"];
    $ligne=unserialize(base64_decode(file_get_contents($fullpath)));
    @unlink($fullpath);

    if(!is_array($ligne)){
        return $tpl->js_error("{corrupted}",__FUNCTION__,__FILE__,__LINE__);
    }
    $q                          = new lib_sqlite(NginxGetDB());

    $Keys = array();
    $vals = array();
    unset($ligne["ID"]);
    foreach ($ligne as $key => $value) {
        $Keys[] = $key;
        $vals[] = sprintf("'%s'", $value);
    }
    $sql = sprintf("INSERT INTO nginx_templates (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        return $tpl->js_error($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
    }
    header("content-type: application/x-javascript");
    echo "dialogInstance5.close();\n";
    echo "$function();\n";
    return true;
}


function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
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
function restore_popup():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $function0=$_GET["function0"];
    $tplid     = intval($_GET["restore-popup"]);
    $tpl        = new template_admin();
    $q=new lib_sqlite(NginxGetDB());

    $ligne=$q->mysqli_fetch_array("SELECT tpname,tpdesc  FROM nginx_templates WHERE ID=$tplid");
    $tpname=$ligne["tpname"];
    $tpdesc="<H2>{restore} $tpname</H2>{$ligne["tpdesc"]}";

    $form[]=$tpl->field_hidden("restore",$tplid);
    $sql="SELECT ID,servicename FROM nginx_services ORDER BY zorder";
    $results=$q->QUERY_SQL($sql);
    $MAIN=array();
    foreach ($results as $index=>$ligne){
        $servicename=$ligne["servicename"];
        $ServiceID=$ligne["ID"];
        $MAIN[$ServiceID]=$servicename;
    }

    $form[]=$tpl->field_array_hash($MAIN,"serviceid","nonull:{website} ({target})",null);
    $html[]="<div id='restore-$tplid'></div>";
    $html[]=$tpl->div_warning("{warn_restore_template_nginx}");
    $html[]=$tpl->form_outside(null,$form,$tpdesc,"{restore}","Loadjs('$page?restore-saved=$tplid&function0=$function0');","AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function restore_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $tplid=intval($_POST["restore"]);
    $_SESSION["NEWNGINXAFTER"]["TPLID"]=$_POST["restore"];
    $_SESSION["NEWNGINXAFTER"]["SITEID"]=$_POST["serviceid"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT tpname FROM nginx_templates WHERE ID=$tplid");
    $tpname=$ligne["tpname"];
    $sitname=get_servicename($_POST["serviceid"]);
    return admin_tracks("Restored template $tpname parameters for reversed website $sitname");
}
function restore_saved():bool{
    $tpl        = new template_admin();
    $function0=$_GET["function0"];
    header("content-type: application/x-javascript");
    if(!isset($_SESSION["NEWNGINXAFTER"]["TPLID"])){
        return $tpl->js_error("{corrupted}",__FUNCTION__,__FILE__,__LINE__);

    }
    if(!is_numeric( $_SESSION["NEWNGINXAFTER"]["TPLID"])){
        return $tpl->js_error("{corrupted}",__FUNCTION__,__FILE__,__LINE__);
    }

    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;$tpl->CLUSTER_CLI=true;
    $tmplid=intval($_SESSION["NEWNGINXAFTER"]["TPLID"]);
    $serviceid=intval($_SESSION["NEWNGINXAFTER"]["SITEID"]);
    $js="dialogInstance5.close();$function0()";
    echo  $tpl->framework_buildjs("nginx.php?apply-template=$tmplid&serviceid=$serviceid",
        "nginx.replic.$serviceid.progress",
        "nginx.replic.$serviceid.log",
        "restore-$tmplid",$js
    );
    return true;
}
function template_popup():bool{
    $function=$_GET["function"];
    $tplid     = intval($_GET["template-popup"]);
    $tpl        = new template_admin();
    $bt="{apply}";
    $form[]=$tpl->field_hidden("templateid",$tplid);

    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT ID,servicename FROM nginx_services ORDER BY zorder";
    $results=$q->QUERY_SQL($sql);
    $MAIN=array();
    foreach ($results as $index=>$ligne){
        $servicename=$ligne["servicename"];
        $ServiceID=$ligne["ID"];
        $MAIN[$ServiceID]=$servicename;


    }
    $js="dialogInstance5.close();$function()";
    $ligne=$q->mysqli_fetch_array("SELECT tpname,tpdesc  FROM nginx_templates WHERE ID=$tplid");
    if(!isset($ligne["tpname"])){
        $ligne["tpname"]="New template";
        $ligne["tpdesc"]="My new template for replicating parameters";
    }

    $tpname=$ligne["tpname"];
    $tpdesc=$ligne["tpdesc"];

    $form[]=$tpl->field_text("tpname","{template_name}",$tpname,true);
    $form[]=$tpl->field_text("tpdesc","{description}",$tpdesc,true);
    if($tplid==0){
        $form[]=$tpl->field_array_hash($MAIN,"serviceid","nonull:{based_on}",null);
        $form[]=$tpl->field_checkbox("domains","{import}: {domains}",1);
        $form[]=$tpl->field_checkbox("backends","{import}: {backends}",1);
        $bt="{add}";
    }
    $html[]=$tpl->form_outside(null,$form,null,$bt,$js,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function template_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();

    $q=new lib_sqlite(NginxGetDB());

    $sql="CREATE TABLE IF NOT EXISTS `nginx_templates` ( 
    `ID` INTEGER PRIMARY KEY AUTOINCREMENT, 
    `tpname` TEXT NOT NULL DEFAULT '' ,
    `tpdate` INTEGER NOT NULL DEFAULT 0 ,
    `serviceid` INTEGER NOT NULL DEFAULT 0 ,
    `tpdata` TEXT NOT NULL DEFAULT '' ,
    `tpdesc` TEXT NOT NULL DEFAULT '' )";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."<hr>$sql";
        return false;
    }

    $tpname=$_POST["tpname"];
    $tpdesc=$_POST["tpdesc"];

    $add_fields[]="tpname";
    $add_fields[]="tpdesc";
    $add_fields[]="tpdata";
    $add_fields[]="serviceid";
    $add_fields[]="tpdate";


    $add_data[]="'".$q->sqlite_escape_string2($tpname)."'";
    $add_data[]="'".$q->sqlite_escape_string2($tpdesc)."'";

    $upd[]=sprintf("%s='%s'","tpname",$q->sqlite_escape_string2($tpname));
    $upd[]=sprintf("%s='%s'","tpdesc",$q->sqlite_escape_string2($tpdesc));


    $templateid=intval($_POST["templateid"]);
    $serviceid=intval($_POST["serviceid"]);
    $domains=intval($_POST["domains"]);
    $backends=intval($_POST["backends"]);
    if($templateid==0){
        $get_servicename=get_servicename($serviceid);
        $templateData=GetTemplateData($serviceid,$domains,$backends);
        if(strlen($templateData)<10){
            echo $tpl->post_error("Failed {$GLOBALS["GetTemplateData_ERROR"]}");
        }
        $add_data[]="'".$q->sqlite_escape_string2($templateData)."'";
        $add_data[]="'$serviceid'";
        $add_data[]=sprintf("'%s'",time());

        $sql="INSERT INTO nginx_templates (".implode(",",$add_fields).") VALUES (".implode(",",$add_data).")";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        return admin_tracks("Create a new template $tpname based on $get_servicename");
    }

    $sql=sprintf("UPDATE nginx_templates SET %s WHERE ID=%s",@implode(",",$upd),$templateid);
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks("Update template #$templateid $tpname");
}
function download():bool{
    $tplid=$_GET["download"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT *  FROM nginx_templates WHERE ID=$tplid");
    $tpname=$ligne["tpname"];
    $tpname=strtolower(str_replace(" ","-",$tpname));
    $tpdata=base64_encode(serialize($ligne));
    $size=strlen($tpdata);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $tpname . '.tpl"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $size); // Tells the browser the size of the file
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // Helps with caching
    ob_clean();
    flush();
    echo $tpdata;
    return true;
}
function GetTemplateData($serviceid,$domains=1,$backends=1):string{
    $q=new lib_sqlite(NginxGetDB());
    // Get Main Config
    // Get Ports
    $results=$q->QUERY_SQL("SELECT * FROM stream_ports WHERE serviceid='$serviceid'");
    $MAIN["stream_ports"]=serialize($results);

    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_services WHERE ID=$serviceid");
    if($domains==0){
        unset($ligne["hosts"]);
    }
    $MAIN["nginx_services"]=serialize($ligne);
    if($backends==1){
    $results=$q->QUERY_SQL("SELECT * FROM backends WHERE serviceid='$serviceid'");
        $MAIN["backends"]=serialize($results);
    }

    $results=$q->QUERY_SQL("SELECT * FROM ngx_directories WHERE serviceid=$serviceid");
    if(!$q->ok){
        $GLOBALS["GetTemplateData_ERROR"]=$q->mysql_error;
        return "";
    }
    foreach ($results as $index=>$ligne){
        $KeyData=serialize($ligne);
        $ID=$ligne["ID"];
        $results2=$q->QUERY_SQL("SELECT * FROM ngx_subdir_items WHERE directoryid=$ID");
        foreach ($results2 as $index=>$ligne) {
            $MAIN["ngx_directories"][$KeyData]["ngx_subdir_items"][] = serialize($ligne);
        }

        $results2=$q->QUERY_SQL("SELECT * FROM directories_backends WHERE directory_id=$ID");
        foreach ($results2 as $index=>$ligne) {
            $MAIN["ngx_directories"][$KeyData]["directories_backends"][] = serialize($ligne);
        }
    }

    $results=$q->QUERY_SQL("SELECT * FROM ngx_stream_access_module WHERE serviceid=$serviceid");
    $MAIN["ngx_stream_access_module"]=serialize($results);

    $results=$q->QUERY_SQL("SELECT * FROM modsecurity_whitelist WHERE serviceid=$serviceid");
    $MAIN["modsecurity_whitelist"]=serialize($results);

    $results=$q->QUERY_SQL("SELECT * FROM service_parameters WHERE serviceid=$serviceid");
    $MAIN["service_parameters"]=serialize($results);

    return base64_encode(serialize($MAIN));

    

}
function popup_main():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $function=$_GET["function"];
    $html[]="<div id='tplnames-nginx-btns' style='margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page,null,null,null,"&function0=$function");
    echo @implode("\n",$html);
    return true;
}


function top_buttons():bool{
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function0=$_GET["function0"];

    $topbuttons[] = array("Loadjs('$page?template-js=0&function=$function&function0=$function0')", ico_plus, "{new_template}");
    $topbuttons[] = array("Loadjs('$page?template-import=yes&function=$function&function0=$function0')", ico_import, "{import_template}");

    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all(){
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FUris")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=0;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FUris",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all user-Agent Deny For reverse-proxy $get_servicename");
}

function search():bool{

//<template_name>Template name</template_name>
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function =$_GET["function"];
    $function0=$_GET["function0"];
    $tableid    = time();
    $search=$_GET["search"];

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{templates}</th>
        	<th nowrap>{description}</small></th>
        	<th nowrap>{based_on}</small></th>
        	<th nowrap>{created}</th>
        	<th nowrap>&nbsp;</small></th>
        	<th nowrap>&nbsp;</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $q=new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT *  FROM nginx_templates");

    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["ID"]);
        $tpname=$ligne["tpname"];
        $tpdate=$ligne["tpdate"];
        $tpdesc=$ligne["tpdesc"];
        $tpdata=$ligne["tpdata"];
        $bytes=strlen($tpdata);
        $bytes=FormatBytes($bytes/1024);

        $x=unserialize(base64_decode($tpdata));
        //$MAIN=unserialize($x["ngx_directories"]);
       // print_r($x["ngx_directories"]);

        $serviceid=$ligne["serviceid"];
        $servicename=get_servicename($serviceid);
        $md=md5(serialize($ligne));
        $delete=$tpl->icon_delete("Loadjs('$page?template-remove=$ID&md=$md')","AsWebMaster");
        $tpname=$tpl->td_href($tpname,"","Loadjs('$page?template-js=$ID&function=$function')");
        $down=$tpl->icon_download("document.location.href='$page?download=$ID'","AsWebMaster");
        $restore=$tpl->icon_restore("Loadjs('$page?restore-js=$ID&function=$function&function0=$function0')","AsWebMaster");

        $html[]="<tr id='$md'>
				<td width=1% nowrap><i class='fas fa-file-alt'></i>&nbsp;$tpname ($bytes)</td>
				<td width=99% ><i>$tpdesc</i></td>
				<td width=1%  nowrap >$servicename</td>
				<td width=1%  nowrap >".$tpl->time_to_date($tpdate)."</td>
				<td width=1%  nowrap >$down</td>
				<td width=1%  nowrap >$restore</td>
				<td width=1%  nowrap >$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('tplnames-nginx-btns','$page?top-buttons=yes&function=$function&function0=$function0');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }