<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["nf-conntrack-max-js"])){nf_conntrack_max_js();exit;}
if(isset($_GET["nf-conntrack-max-popup"])){nf_conntrack_max_popup();exit;}
if(isset($_POST["nf_conntrack_max"])){nf_conntrack_max_save();exit;}

if(isset($_POST["HaClusterTransParentMode"])){Save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["params-js"])){param_js();exit;}
if(isset($_GET["params-popup"])){param_popup();exit;}
table();

function nf_conntrack_max_js(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $tpl->js_dialog1("{nf_conntrack_max} ({system})", "$page?nf-conntrack-max-popup=yes&bypop=yes", 500);
}
function nf_conntrack_max_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $bypop=false;
    if(isset($_GET["bypop"])){$bypop=true;}
    $security="AsSquidAdministrator";
    $jsafter="LoadAjax('applications-squid-status','fw.proxy.status.php?applications-squid-status=yes');";

    if($bypop){
        $jsafter="Loadjs('$page?nf-conntrack-max-js=yes');$jsafter";
    }
    $nf_conntrack_max=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("net.netfilter.nf_conntrack_max"));

    $form[]=$tpl->field_multiple_64("nf_conntrack_max","{nf_conntrack_max} ({system})",$nf_conntrack_max,"");
    $html[]=$tpl->form_outside("{nf_conntrack_max}", @implode("\n", $form),"","{apply}","dialogInstance1.close();$jsafter",$security,true);
    echo $tpl->_ENGINE_parse_body($html);
}
function nf_conntrack_max_save(){
    $nf_conntrack_max=$_POST["nf_conntrack_max"];
    if($nf_conntrack_max<524288){$nf_conntrack_max=524288;}
    admin_tracks("Max Connections tracking for the system defined to $nf_conntrack_max");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nf_conntrack_max",$nf_conntrack_max);
    $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("net.netfilter.nf_conntrack_max",$nf_conntrack_max);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    $HaClusterTransParentMode=intval($_POST["HaClusterTransParentMode"]);
    $HaClusterTransParentCertif=$_POST["HaClusterTransParentCertif"];

    if($HaClusterTransParentMode==1){
        if(strlen($HaClusterTransParentCertif)<3){
            echo $tpl->post_error("Please choose a certificate!");
            return false;
        }
    }


    $tpl->SAVE_POSTs();
    return true;
}
function param_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{transparent_mode}","$page?params-popup=yes");
}


function param_popup():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();

    $jsrestart=$tpl->framework_buildjs(
        "/hacluster/server/transparent/restart",
        "hacluster.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart",
        "LoadAjax('intercept-status','$page?status=yes');"
    );

    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    $HaClusterTransParentCertif=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentCertif"));
    $HaClusterTransParentMasquerade=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMasquerade"));
    $HaClusterTransParentCPU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentCPU"));
    $HaClusterTransparentBalance=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransparentBalance"));
    $HaClusterTransParentDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentDebug"));

    $form[]=$tpl->field_checkbox("HaClusterTransParentMode","{transparent_mode}",$HaClusterTransParentMode,false,'{SquidTransparentMixed_text}');

    $form[]=$tpl->field_certificate("HaClusterTransParentCertif","{certificate}",$HaClusterTransParentCertif,"{hacluster_Tproxy_certificate}",null);
    $form[]=$tpl->field_checkbox("HaClusterTransParentMasquerade","{masquerading}",$HaClusterTransParentMasquerade,false,null);
    $form[]=$tpl->field_checkbox("HaClusterTransParentDebug","{debug}",$HaClusterTransParentDebug,false,null);

    $nf_conntrack_max=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("net.netfilter.nf_conntrack_max"));
    $nf_conntrack_max=$tpl->FormatNumber($nf_conntrack_max);
    $value=array();
    $value["VALUE"]=$nf_conntrack_max;
    $value["BUTTON"]=true;
    $value["BUTTON_JS"]="Loadjs('$page?nf-conntrack-max-js=yes')";
    $value["BUTTON_CAPTION"]="{modify}";

    $form[]=$tpl->field_info("nf_conntrack_max2","{nf_conntrack_max} ({system})",$value);

    $f[]="LoadAjaxSilent('hacluster-parameters','fw.hacluster.status.php?parameters-table=yes');";
    $f[]="dialogInstance2.close()";
    $f[]=$jsrestart;

    $html[]=$tpl->form_outside("",$form,"{SquidTransparentMixed_text}","{apply}",@implode(";",$f),"AsSquidAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
    return true;


}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    $HaClusterTransParentCertif=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentCertif"));
    $HaClusterTransParentMasquerade=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMasquerade"));
    $HaClusterTransParentCPU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentCPU"));
    $HaClusterTransparentBalance=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransparentBalance"));
    $HaClusterTransParentDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentDebug"));
    if($HaClusterTransparentBalance==null){$HaClusterTransparentBalance="leastconn";}
    if($HaClusterTransParentCPU==0){$HaClusterTransParentCPU=1;}

    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?CPU-NUMBER=yes"));
    }
    $CPUz[1] = "1 CPU (monocore)";
    if($CPU_NUMBER>2) {
        for ($i = 2; $i < $CPU_NUMBER; $i++) {
            $s = null;
            $CPUz[$i] = "$i {cpu}s";
        }
    }
    $jsrestart=$tpl->framework_buildjs(
        "/hacluster/server/transparent/restart",
        "hacluster.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart",
        "LoadAjax('intercept-status','$page?status=yes');"
    );
    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px' valign='top'>";
    $html[]="<div id='intercept-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;padding-left: 20px' valign='top'>";

    $form[]=$tpl->field_checkbox("HaClusterTransParentMode","{SquidTransparentMixed}",$HaClusterTransParentMode,"HaClusterTransParentCertif,HaClusterTransParentMasquerade,HaClusterTransParentCPU",'{SquidTransparentMixed_text}');

    $algo["source"]="{strict-hashed-ip}";
    $algo["roundrobin"]="{round-robin}";
    $algo["leastconn"]="{leastconn}";
    $form[]=$tpl->field_array_hash($algo,"HaClusterTransparentBalance","{method}",$HaClusterTransparentBalance);

    if($CPU_NUMBER>2) {
        $form[] = $tpl->field_array_hash($CPUz, "HaClusterTransParentCPU", "nonull:{SquidCpuNumber}", $HaClusterTransParentCPU, false, "{haproxy_nbproc}");
    }
    $form[]=$tpl->field_certificate("HaClusterTransParentCertif","{certificate}",$HaClusterTransParentCertif,"{hacluster_Tproxy_certificate}",null);
    $form[]=$tpl->field_checkbox("HaClusterTransParentMasquerade","{masquerading}",$HaClusterTransParentMasquerade,false,null);
    $form[]=$tpl->field_checkbox("HaClusterTransParentDebug","{debug}",$HaClusterTransParentDebug,false,null);

    $nf_conntrack_max=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("net.netfilter.nf_conntrack_max"));
    $nf_conntrack_max=$tpl->FormatNumber($nf_conntrack_max);
    $value=array();
    $value["VALUE"]=$nf_conntrack_max;
    $value["BUTTON"]=true;
    $value["BUTTON_JS"]="Loadjs('$page?nf-conntrack-max-js=yes')";
    $value["BUTTON_CAPTION"]="{modify}";

    $form[]=$tpl->field_info("nf_conntrack_max2","{nf_conntrack_max} ({system})",$value);

    $html[]=$tpl->form_outside("{parameters}",$form,null,"{apply}",$jsrestart,"AsSquidAdministrator",true);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>LoadAjax('intercept-status','$page?status=yes')</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));

    if($HaClusterTransParentMode==0){
        echo $tpl->widget_grey("{feature}","{disabled}");
        return;

    }

    $ports[]=3150;
    $ports[]=3154;
    $ports[]=3155;

    foreach ($ports as $portnum){

        $fp=@fsockopen("127.0.0.1", $portnum, $errno, $errstr, 1);
        if(!$fp){
            echo $tpl->widget_rouge($errstr,"Port: $portnum");
            return;
        }

    }
    echo $tpl->widget_vert("{feature}","OK");


    $jsRestart=$tpl->framework_buildjs(
        "/hacluster/server/transparent/restart",
        "hacluster.progress",
        "hacluster.progress.txt",
        "progress-hacluster-restart",
        "LoadAjax('intercept-status','$page?status=yes');"
    );

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/transparent/status"));
    if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$json->Error", "{error}"));

    } else{
        $ini = new Bs_IniHandler();
        $ini->loadString($json->Info);
        echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_HAPROXY_CLUSTER_TRANSPARENT", $jsRestart));
    }

}