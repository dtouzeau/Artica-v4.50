<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");

if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["file-uploaded"])){import_uploaded();exit;}

if(isset($_GET["code-js"])){code_js();exit;}
if(isset($_GET["code-popup"])){code_popup();exit;}
if(isset($_POST["FBulkRedirectsCode"])){code_save();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-table2"])){popup_table2();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["SrcPath"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}

function code_js():bool{

    $tpl = new template_admin();
    $page = CurrentPageName();
    $serviceid=$_GET["code-js"];
    $function = $_GET["function"];
    $function2 = $_GET["function2"];
    return $tpl->js_dialog5("{http_status_code}",
        "$page?code-popup=$serviceid&function=$function&function2=$function2",500);
}
function import_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $serviceid=$_GET["import-js"];
    $function = $_GET["function"];
    $function2 = $_GET["function2"];
    return $tpl->js_dialog5("{importing_form_csv_file}",
        "$page?import-popup=$serviceid&function=$function&function2=$function2",500);
}
function import_popup():bool{
    $tpl=new template_admin();
    $serviceid=$_GET["import-popup"];
    $page=CurrentPageName();
    $function=$_GET["function"];
    $function2 = $_GET["function2"];
    $bt_upload=$tpl->button_upload("{importing_form_csv_file}",$page,null,"&function=$function&serviceid=$serviceid&function2=$function2")."&nbsp;&nbsp;";
    $html="<div id='ca-form-import'>
        <div class='center'>$bt_upload</div>
    </div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function code_popup(){
    $tpl=new template_admin();
    $serviceid=$_GET["code-popup"];
    $page=CurrentPageName();
    $function=$_GET["function"];
    $function2 = $_GET["function2"];
    $sock=new socksngix($serviceid);
    $Code=intval($sock->GET_INFO("FBulkRedirectsCode"));
    if($Code==0){
        $Code=302;
    }
    $HTTP_CODE[301]="{Moved_Permanently} (301)";
    $HTTP_CODE[302]="{Moved_Temporarily} (302)";
    $HTTP_CODE[303]="{http_code_see_other} (303)";
    $HTTP_CODE[307]="{Moved_Temporarily} (307)";

    $form[]=$tpl->field_array_hash($HTTP_CODE,"FBulkRedirectsCode",",nonull:{http_status_code}",$Code);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $html[]=$tpl->form_outside(null,$form,"{warn_params_all_section}","{apply}",refresh_global($serviceid),"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function code_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("FBulkRedirectsCode",$_POST["FBulkRedirectsCode"]);
    $servname=get_servicename($serviceid);
    return admin_tracks("Saving Bulk redirects HTTP Code {$_POST["FBulkRedirectsCode"]} for reverse-proxy $servname");
}
function import_uploaded():bool{
    $tpl=new template_admin();
    $file=$_GET["file-uploaded"];
    $csvFile="/usr/share/artica-postfix/ressources/conf/upload/$file";
    $function=$_GET["function"];
    $function2 = $_GET["function2"];
    $serviceid=intval($_GET["serviceid"]);
    if($serviceid==0){
        echo $tpl->js_error("No service ID!");
        @unlink($csvFile);
        return false;
    }

    $sock=new socksngix($serviceid);
    $MainData       = unserialize(base64_decode($sock->GET_INFO("FBulkRedirectsData")));
    $c=0;$d=0;
    if (($handle = fopen($csvFile, 'r')) !== false) {
        $headers = fgetcsv($handle);
        if ($headers) {
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $data);
                $src=$row["src"];
                $dst=$row["dst"];

                if(preg_match("#^http.*?\/#",$src)){
                    $parsed=parse_url($src);
                    $tt=array();
                    if(isset($parsed["path"])){
                        $tt[]=$parsed["path"];
                    }
                    if(isset($parsed["query"])){
                        $tt[]=$parsed["query"];
                    }
                    $src=@implode("",$tt);
                }
                $c++;
                if(strlen($src)<2){

                    continue;}
                $d++;
                $MainData[sprintf("%s||%s",$src,$dst)]=1;
            }
        }else{
            writelogs("[$serviceid]: Header not found!",__FUNCTION__,__FILE__,__LINE__);
        }
        fclose($handle);
    } else {
        $tpl->js_error("Error: Cannot open the file.");
    }

    @unlink($csvFile);
    $encoded=serialize($MainData);
    $sock->SET_INFO("FBulkRedirectsData",base64_encode($encoded));
    $get_servicename=get_servicename($serviceid);


    echo "dialogInstance5.close();\n";
    if(strlen($function)>2){
        echo "$function();";
    }
    if(strlen($function2)>2){
        echo "$function2();";
    }
    return admin_tracks("Import $d redirects rules for reverse-proxy $get_servicename");
}

function enable_feature():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["serviceid"]);
    $enable=intval($_GET["enable-rule-js"]);
    $sockngix=new socksngix(($serviceid));
    $sockngix->SET_INFO("BulkRedirects",$enable);
    $function2 = $_GET["function2"];
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjaxSilent('useragent-nginx-bulkredirs$serviceid','$page?top-buttons=$serviceid');";
    if(strlen($function2)>1){
        $f[]="$function2();";
    }
    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    return admin_tracks("Turn feature to $enable for redirecting paths on  $get_servicename reverse-proxy site");

}

function service_js():bool{
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog4("{redirects}","$page?popup-main=$serviceid&function2=$function");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $function =$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{redirects} {rule}: $rule";
    if($rule==0){$title="{redirects} {new_rule}";}
    return $tpl->js_dialog5("$title","$page?popup-rule=$rule&serviceid=$serviceid&function=$function");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $md=$_GET["md"];
    $UserAgent=$_GET["pattern-remove"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FBulkRedirectsData")));
    unset($data[$UserAgent]);
    $encoded=serialize($data);
    $sock->SET_INFO("FBulkRedirectsData",base64_encode($encoded));
    echo "$('#$md').remove();\n";
    echo refresh_global_no_close($serviceid);
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
function rule_enable():bool{
    $UserAgent=$_GET["pattern-enable"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FBulkRedirectsData")));
    if(intval($data[$UserAgent])==1){
        $data[$UserAgent]=0;
    }else{
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FBulkRedirectsData",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Enable={$data[$UserAgent]} For reverse-proxy $get_servicename User-Agent rule $UserAgent");
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $bt="{add}";

    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("SrcPath","{path}","",true);
    $form[]=$tpl->field_text("DstPath","{redirect_to_the_specified_link}","",true);
    $html[]=$tpl->form_outside(null,$form,null,$bt,refresh_global($serviceid),"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function refresh_global_no_close($serviceid):string{
    if(isset($_GET["function"])){
        if(strlen($_GET["function"])>2) {
            $f[] = "{$_GET["function"]}()";
        }
    }
    if(isset($_GET["function2"])){
        if(strlen($_GET["function2"])>2) {
            $f[] = "{$_GET["function2"]}()";
        }
    }

    if($serviceid==0){return "";}
    $f[]="LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');";
    return @implode(";",$f)."\n";

}

function refresh_global($serviceid):string{
    $page=CurrentPageName();
    $f[]=refresh_global_no_close($serviceid);


    $t[]="$page?popup-table=$serviceid";

    if(isset($_GET["function"])){
        if(strlen($_GET["function"])>2) {
            $t[] = "function={$_GET["function"]}";
        }
    }
    if(isset($_GET["function2"])){
        if(strlen($_GET["function2"])>2) {
            $t[] = "function2={$_GET["function2"]}";
        }
    }

    $url=@implode("&",$t);
    $f[]="LoadAjax('main-popup-$serviceid','$url');";
    $f[]="dialogInstance5.close();";
    return @implode(";",$f);
}

function rule_export_js(){
    $serviceid=intval($_GET["rule-export-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog6("{http_user_agent}: {export}/{import}","$page?rule-export-popup=$serviceid",950);
}

function rule_export_popup(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $serviceid=intval($_GET["rule-export-popup"]);
    $sock       = new socksngix($serviceid);
    $tpl->field_hidden("importid","$serviceid");
    $jsrestart="dialogInstance6.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    $form[]=$tpl->field_textarea("export", "{rules}", $sock->GET_INFO("FBulkRedirectsData"),"664px");
    echo $tpl->form_outside("{export}/{import}", @implode("\n", $form),null,"{apply}",$jsrestart,null);
}
function rule_export_save(){
    $serviceid=intval($_POST["importid"]);
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("FBulkRedirectsData",$_POST["export"]);
}

function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FBulkRedirectsData")));


    if(preg_match("#^http.*?\/#",$_POST["SrcPath"])){
        $parsed=parse_url($_POST["SrcPath"]);
        $tt=array();
        if(isset($parsed["path"])){
            $tt[]=$parsed["path"];
        }
        if(isset($parsed["query"])){
            $tt[]=$parsed["query"];
        }
        $_POST["SrcPath"]=@implode("",$tt);
    }
    if(strlen($_POST["SrcPath"])<2){
        echo $tpl->post_error("{$_POST["SrcPath"]} {corrupted}");
        return false;
    }

    $key=sprintf("%s||%s",$_POST["SrcPath"],$_POST["DstPath"]);
    $logname=sprintf("From %s to %s",$_POST["SrcPath"],$_POST["DstPath"]);

    $data[$key]=1;
    $encoded=serialize($data);
    $sock->SET_INFO("FBulkRedirectsData",base64_encode($encoded));
    $get_servicename=get_servicename($serviceid);
    return admin_tracks("Add a new path redirect $logname for reverse-proxy $get_servicename");

}

function popup_main():bool{
    $function2=$_GET["function2"];
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid&function2=$function2')</script>";
    return true;
}
function popup_table():bool{
    $function2=$_GET["function2"];
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();
    echo "<div id='useragent-nginx-bulkredirs$serviceid' style='margin-bottom:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table2=$serviceid&function2=$function2");

    return true;
}
function OnlyActive():bool{
    $function=$_GET["function"];
    $Key=basename(__FILE__)."OnlyActive";
    if(!isset($_SESSION[$Key])){
        $_SESSION[$Key]=true;
    }else{
        unset($_SESSION[$Key]);
    }
    header("content-type: application/x-javascript");
    echo "$function();";
    return true;
}

function top_buttons():bool{
    $function2=$_GET["function2"];
    $serviceid  = intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $BulkRedirects=intval($sock->GET_INFO("BulkRedirects"));
    if($BulkRedirects==1){
        $topbuttons[] = array("Loadjs('$page?enable-rule-js=0&serviceid=$serviceid&function=$function&function2=$function2')", ico_check, "{active2}");
    }else{
        $topbuttons[] = array("Loadjs('$page?enable-rule-js=1&serviceid=$serviceid&function=$function&function2=$function2')", ico_disabled, "{disabled}");

    }
    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid&function=$function&function2=$function2')", ico_plus, "{new_rule}");

    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function&function2=$function2')", ico_filter, "{OnlyActive}");

    $topbuttons[] = array("Loadjs('$page?disableall=yes&serviceid=$serviceid&function=$function'&function2=$function2)", ico_disabled, "{disable_all}");
    $topbuttons[] = array("Loadjs('$page?enableall=yes&serviceid=$serviceid&function=$function&function2=$function2')", ico_check, "{enable_all}");

    $topbuttons[] = array("Loadjs('$page?import-js=$serviceid&function=$function&function2=$function2')", ico_import, "{import}");


    if(!isHarmpID()) {
        $compile_js_progress=compile_js_progress($serviceid);
        $topbuttons[] = array($compile_js_progress, ico_save, "{apply}");
    }
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all(){
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FBulkRedirectsData")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=0;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FBulkRedirectsData",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all user-Agent Deny For reverse-proxy $get_servicename");
}
function rule_enable_all(){
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $data=unserialize(base64_decode($sock->GET_INFO("FBulkRedirectsData")));
    foreach ($data as $UserAgent=>$none){
        $data[$UserAgent]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FBulkRedirectsData",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Enable all user-Agent Deny For reverse-proxy $get_servicename");
}
function popup_table2():bool{
    $function2=$_GET["function2"];
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $function =$_GET["function"];
    $tableid    = time();
    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="</div>";

    $search=$_GET["search"];

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{path}</th>
        	<th nowrap>{redirect_to_the_specified_link}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $Code=intval($sock->GET_INFO("FBulkRedirectsCode"));
    if($Code==0){
        $Code=302;
    }
    $data=unserialize(base64_decode($sock->GET_INFO("FBulkRedirectsData")));
    if(!is_array($data)){
        $data=array();
    }
    if(count($data)==0){
        $data=array();
    }
    ksort($data);
    $c=0;

    $OnlyActive=false;
    $KeyActive=basename(__FILE__)."OnlyActive";
    if(isset($_SESSION[$KeyActive])){
        $OnlyActive=true;
    }

    foreach ($data as $pattern=>$enable){

        if($search<>null){
            $search=str_replace(".","\.",$search);
            $search=str_replace("*",".*?",$search);
            if(!preg_match("#$search#",$pattern)){
                continue;
            }
        }

        if($OnlyActive){
            if($enable==0){continue;}
        }

        $encoded=urlencode($pattern);

        $pattern=htmlentities($pattern);
        $md=md5($pattern);

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$encoded&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$encoded&serviceid=$serviceid&md=$md')","AsWebMaster");

        $c++;

        if($c>250){break;}

        $tb=explode("||",$pattern);
        $Src=$tb[0];
        $Dest=$tb[1];



        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}

    $code_text=$tpl->td_href("{http_status_code} $Code",null,"Loadjs('$page?code-js=$serviceid&function=$function&function2=$function2')");
    $html[]="<tr id='$md'>
				<td width=50%>$Src</td>
				<td width=50%>$Dest ($code_text)</td>
				<td width=1%  nowrap >$enable</td>
				<td width=1%  nowrap >$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('useragent-nginx-bulkredirs$serviceid','$page?top-buttons=$serviceid&function=$function&function2=$function2');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }