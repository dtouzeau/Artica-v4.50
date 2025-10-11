<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.icon.top.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}

if(isset($_POST["fs_filemax2"])){fs_filemax_save();exit;}
if(isset($_POST["max_filedesc"])){filedesc_save();exit;}
if(isset($_GET["filedesc-set"])){filedesc_set();exit;}
if(isset($_GET["filedesc-current"])){filedesc_current();exit;}
if(isset($_GET["filedesc-section"])){filedesc_section();exit;}
if(isset($_GET["filedesc-form"])){filedsc_form();exit;}
if(isset($_GET["filedsc-form"])){filedsc_form();exit;}
if(isset($_GET["filedesc-form-js"])){filedesc_form_js();exit;}
if(isset($_GET["filedesc-status"])){filedesc_status();exit;}
if(isset($_GET["filedesc-max"])){filedesc_max();exit;}
if(isset($_GET["fs-filemax-js"])){fs_filemax_js();exit;}
if(isset($_GET["fs-filemax-popup"])){fs_filemax_popup();exit;}
if(isset($_GET["nf-conntrack-max-js"])){nf_conntrack_max_js();exit;}
if(isset($_GET["nf-conntrack-max-popup"])){nf_conntrack_max_popup();exit;}
if(isset($_POST["nf_conntrack_max"])){nf_conntrack_max_save();exit;}
if(isset($_GET["max-filedesc-js"])){max_filedesc_js();exit;}
if(isset($_GET["max-filedesc-popup"])){max_filedesc_popup();exit;}


page();
function fs_filemax_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog1("{file_descriptors} ({system})", "$page?fs-filemax-popup=yes&bypop=yes", 500);
}
function max_filedesc_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog1("{file_descriptors}", "$page?max-filedesc-popup=yes", 500);
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{file_descriptors}",
        "fas fa-file-medical-alt","{file_descriptors_squid_explain}","$page?table=yes","proxy-filedescriptors","progress-squid-fildesc-restart",
        false,"table-loader-proxy-fildescaddr");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{file_descriptors}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function fildesc_button_failed(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsafter="LoadAjax('filedesc-current','$page?filedesc-current=yes');";
    $restart_div="progress-squid-fildesc-restart";

    $js_restart=$tpl->framework_buildjs("/proxy/restart/single","squid.quick.rprogress",
        "squid.quick.rprogress.log",$restart_div,$jsafter,$jsafter);

        $button_restart=$tpl->button_autnonome("{restart}", $js_restart, "fas fa-sync-alt","AsProxyMonitor","99%","btn-danger");

        return $button_restart;

}

function filedesc_set():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $filedesc_status=filedesc_status();
    $color="green";

    if(isset($filedesc_status["ERROR"])){
        $color="red";
        $html[]=$tpl->widget_h("$color","fas fa-tachometer-alt",fildesc_button_failed(),"{error}");
        $html[]="<script>";
        $html[]="LoadAjaxSilent('filedesc-max','$page?filedesc-max=yes');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);

        //
        return true;
    }

    $CPU=0;
    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    for($i=1;$i<$CPU_NUMBER+1;$i++){
        if(!isset($SquidSMPConfig[$i])){$CPUNumber=0;}else{$CPUNumber=intval($SquidSMPConfig[$i]);}
        if($i==1){if($CPUNumber==0){$CPUNumber=1;}}
        if($CPUNumber==0){continue;}
        $CPU++;
    }
    $textreal=null;
    $text=$filedesc_status["SETTINGS"];
    if($CPU>1){
        $text=$tpl->FormatNumber($filedesc_status["SETTINGS"])."/$CPU CPU(s)";
        $textreal=": <strong>".round(intval($filedesc_status["CURRENT_INT"])/$CPU)."/CPU</strong>";
    }
    $html[]=$tpl->widget_h("$color","fas fa-cogs",$text,"{defined}$textreal");
    $html[]="<script>";
    $html[]="LoadAjaxSilent('filedesc-max','$page?filedesc-max=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function filedesc_current(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $filedesc_status=filedesc_status();

    if(isset($filedesc_status["ERROR"])){
        $color="red";
        $html[]=$tpl->widget_h("$color","fas fa-tachometer-alt","{$filedesc_status["ERROR"]}","{error}");
        $html[]="<script>";
        $html[]="LoadAjaxSilent('filedesc-set','$page?filedesc-set=yes');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);

       // fildesc_button_failed
        return true;
    }




    $CURRENT=$filedesc_status["CURRENT"];
    $CURRENT_PRC=$filedesc_status["CURRENT_PRC"];
    $color="green";
    if($CURRENT_PRC>80){$color="yellow";}
    if($CURRENT_PRC>95){$color="red";}

    $html[]=$tpl->widget_h("$color","fas fa-tachometer-alt","$CURRENT ($CURRENT_PRC%)","{using}");
    $html[]="<script>";
    $html[]="LoadAjaxSilent('filedesc-set','$page?filedesc-set=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function filedesc_max(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $filedesc_status=filedesc_status();

    if(isset($filedesc_status["ERROR"])){
        $color="red";
        $html[]=$tpl->widget_h("$color","fas fa-tachometer-alt","{$filedesc_status["ERROR"]}","{error}");
        $html[]="<script>";
        //$html[]="LoadAjaxSilent('filedesc-set','$page?filedesc-set=yes');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);

        // fildesc_button_failed
        return true;
    }


    $CURRENT=$filedesc_status["CURRENT"];
    $CURRENT_PRC=$filedesc_status["CURRENT_PRC"];
    $color="green";
    if($CURRENT_PRC>80){$color="yellow";}
    if($CURRENT_PRC>95){$color="red";}

    $html[]=$tpl->widget_h("$color","fas fa-tachometer-alt",$filedesc_status["MAX"],"TOP {value}");
    $html[]="<script>";
    //$html[]="LoadAjax('filedesc-set','$page?filedesc-set=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);



}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:390px;vertical-align: top'><div id='filedesc_status'></div></td>";
    $html[]="<td style='width:99%;vertical-align: top'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%;vertical-align: top'><div id='filedesc-set'></div>";
    $html[]="<td style='width:33%;vertical-align: top;padding-left:15px'><div id='filedesc-current'></div>";
    $html[]="<td style='width:33%;vertical-align: top;padding-left:15px'><div id='filedesc-max'></div>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<div id='filedesc-form' style='padding-left: 10px'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $js=$tpl->RefreshInterval_js("filedesc-current",$page,"filedesc-current=yes");
    $html[]="<script>";
    $html[]="$js";
    $html[]="LoadAjax('filedesc-form','$page?filedesc-section=yes');";

    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function filedesc_section():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=True;

    $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
    $file_max=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("fs.file-max"));
    $nf_conntrack_max=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("net.netfilter.nf_conntrack_max"));

    if($max_filedesc==0){$max_filedesc=16384;}
    $max_filedesc=$tpl->FormatNumber($max_filedesc);
    $file_max=$tpl->FormatNumber($file_max);
    $nf_conntrack_max=$tpl->FormatNumber($nf_conntrack_max);
    $tpl->table_form_section("{file_descriptors}");
    $tpl->table_form_field_js("Loadjs('$page?max-filedesc-js=yes')");
    $tpl->table_form_field_text("{file_descriptors} (proxy)",$max_filedesc,ico_performance);
    $tpl->table_form_field_js("Loadjs('$page?fs-filemax-js=yes')");
    $tpl->table_form_field_text("{file_descriptors} ({system})",$file_max,ico_performance);
    $tpl->table_form_field_js("Loadjs('$page?nf-conntrack-max-js=yes')");
    $tpl->table_form_field_text("{nf_conntrack_max} ({system})",$nf_conntrack_max,ico_performance);
    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="<script>";
//    $html[]="LoadAjax('filedesc_status','$page?filedesc-status=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function max_filedesc_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=True;
    $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
    if($max_filedesc==0){$max_filedesc=16384;}
    $security="AsSquidAdministrator";
    $jsafter=jsReload();
    $jsrestart=$jsafter.$tpl->framework_buildjs("/proxy/general/nohup/restart","squid.articarest.nohup",
        "squid.articarest.nohup.log","progress-squid-fildesc-restart");

    $form[]=$tpl->field_multiple_64("max_filedesc","{file_descriptors} (proxy)",$max_filedesc,"");
    $html=$tpl->form_outside("",$form,null,"{apply}",$jsrestart,$security,true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function filedesc_form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{file_descriptors}","$page?filedsc-form=yes&bypop=yes",950);
}

function jsReload():string{
    $page=CurrentPageName();
    $html[]="if( document.getElementById('filedesc-form') ){";
    $html[]="LoadAjax('filedesc-form','$page?filedesc-section=yes');";
    $html[]="}";
    $html[]="if( document.getElementById('applications-squid-status') ){";
    $html[]="LoadAjax('applications-squid-status','fw.proxy.status.php?applications-squid-status=yes');";
    $html[]="}";
    return @implode("", $html);

}

function filedsc_form(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $bypop=false;
    if(isset($_GET["bypop"])){
        $bypop=true;
    }
    $security="AsSquidAdministrator";


    if($bypop){
        $jsafter="Loadjs('$page?filedesc-form-js=yes');".jsReload();
    }

    echo "<div id='popup-filedesc-progress'></div>";
    $jsrestart=$jsafter.$tpl->framework_buildjs("/proxy/general/nohup/restart","squid.articarest.nohup",
        "squid.articarest.nohup.log","popup-filedesc-progress");




    $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
    $ico=new icontop();
    if(method_exists($ico,"filedescriptors_checking")) {
        $ico->filedescriptors_checking(true);
        $Configured = $ico->current_in_conf;
        $OnProxy = $ico->Available;
        if ($Configured > 0 && $OnProxy > 0) {
            if ($Configured > $OnProxy) {
                $text = $tpl->_ENGINE_parse_body("{diff_filedesc}");
                $text = str_replace("%s1", $OnProxy, $text);
                $text = str_replace("%s2", $Configured , $text);
                $html[] = $tpl->div_error("{you_need_to_restart_proxy_service}<br>$text");
                $tpl->form_add_button("{restart}", "Loadjs('$page?restart-js=yes')");
            }
        }
    }


    if($max_filedesc==0){$max_filedesc=16384;}

    $form[]=$tpl->field_multiple_64("max_filedesc","{file_descriptors} (proxy)",$max_filedesc,"");
    $html[]=$tpl->form_outside("{file_descriptors}", @implode("\n", $form),"{file_descriptors_squid_explain}","{apply}",$jsrestart,$security,true);
    echo $tpl->_ENGINE_parse_body($html);
}
function filedesc_save():bool{
    $tpl=new template_admin();
    $max_filedesc=intval($_POST["max_filedesc"]);
    $fs_filemax=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("fs.file-max"));

    if ($max_filedesc > $fs_filemax) {
        $error = $tpl->javascript_parse_text("{error_fs_max2}");
        $max_filedesc = $max_filedesc + 500;
        $error = str_replace("%s", $max_filedesc, $error);
        echo "jserror:$error";
        return false;
    }

    if($_POST["max_filedesc"]<1024){$_POST["max_filedesc"]=16384;}
    admin_tracks("filedescriptors for the Proxy service defined to $max_filedesc");
    $tpl->post_error("Saved $max_filedesc");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("max_filedesc", $max_filedesc);
    return admin_tracks("filedescriptors for the Proxy service defined to $max_filedesc");
}
function filedesc_status():array{

    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc"));


    if(!$json->Status){
        $ARRAY["ERROR"]=$json->Error;
        return $ARRAY;
    }

    $percentage=$json->Info->current_file_descriptors_in_use/$json->Info->max_filedescriptors;
    $percentage=round($percentage*100,2);

    $ARRAY["CURRENT_INT"]=$json->Info->current_file_descriptors;
    $ARRAY["CURRENT"]=$tpl->FormatNumber($json->Info->current_file_descriptors_in_use);
    $ARRAY["CURRENT_PRC"]=$percentage;
    $ARRAY["MAX"]=$json->Info->largest_file_descriptors;
    $ARRAY["SETTINGS"]=$json->Info->must_file_descriptors;
    return $ARRAY;
}
function nf_conntrack_max_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog1("{nf_conntrack_max} ({system})", "$page?nf-conntrack-max-popup=yes&bypop=yes", 500);
}
function nf_conntrack_max_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $bypop=false;
    if(isset($_GET["bypop"])){$bypop=true;}
    $security="AsSquidAdministrator";
    $jsafter=jsReload();

    if($bypop){
        $jsafter="Loadjs('$page?nf-conntrack-max-js=yes');$jsafter";
    }
    $nf_conntrack_max=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("net.netfilter.nf_conntrack_max"));

    $form[]=$tpl->field_multiple_64("nf_conntrack_max","{nf_conntrack_max} ({system})",$nf_conntrack_max,"");
    $html[]=$tpl->form_outside("", $form,"","{apply}","dialogInstance1.close();$jsafter",$security,true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nf_conntrack_max_save():bool{
    $nf_conntrack_max=$_POST["nf_conntrack_max"];
    if($nf_conntrack_max<524288){$nf_conntrack_max=524288;}
    admin_tracks("Max Connections tracking for the system defined to $nf_conntrack_max");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nf_conntrack_max",$nf_conntrack_max);
    $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("net.netfilter.nf_conntrack_max",$nf_conntrack_max);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return admin_tracks("set kernel parameter nf_conntrack_max to $nf_conntrack_max");
}
function fs_filemax_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $bypop=false;
    if(isset($_GET["bypop"])){$bypop=true;}
    $security="AsSquidAdministrator";
    $jsafter=jsReload();

    if($bypop){
        $jsafter="Loadjs('$page?fs-filemax-js=yes');$jsafter";
    }

    $file_max=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("fs_filemax"));
    $form[]=$tpl->field_multiple_64("fs_filemax2","{file_descriptors} ({system})",$file_max,"");
    $html[]=$tpl->form_outside("", $form,"","{apply}","dialogInstance1.close();$jsafter",$security,true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function fs_filemax_save():bool{
    $tpl=new template_admin();
    $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

    $fs_filemax=intval($_POST["fs_filemax2"]);
    $max_filedesc=$max_filedesc+5000;
    if($SQUIDEnable==1) {
        if ($max_filedesc > $fs_filemax) {
            $error = $tpl->javascript_parse_text("{error_fs_max2}");
            $max_filedesc = $max_filedesc + 500;
            $error = str_replace("%s", $max_filedesc, $error);
            echo $tpl->post_error($error);
            return false;
        }
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fs_filemax",$fs_filemax);
    $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("fs.file-max",$fs_filemax);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return admin_tracks("filedescriptors for the system defined to $max_filedesc");
}