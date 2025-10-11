<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.identity.inc');
if(isset($_POST["EnableSystemOptimize"])){SaveConfig();exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["infos"])){infos();exit;}
if(isset($_GET["chhostname-js"])){chhostname_js();exit;}
if(isset($_GET["chhostname-popup"])){chhostname_popup();exit;}
if(isset($_GET["title-hostname"])){chhostname_innerHTML();exit;}
if(isset($_POST["hostname"])){chhostname_save();exit;}
if(isset($_GET["main-page"])){ header("location:fw.system.information.php?main-page=yes");die();}

$page=CurrentPageName();
echo "<div id='fw-system-info' class='row border-bottom white-bg'></div>
<script>LoadAjax('fw-system-info','$page?infos=yes');</script>		
";

function infos(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $cgroupsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsEnabled"));
    $cgroupsPHPNonPtime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPNonPtime"));

    $cgroupsPHPCpuShares=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPCpuShares"));
    $cgroupsPHPDiskIO=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPDiskIO"));
    $SYSTEM_DISK_SPEED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEM_DISK_SPEED"));
    if($cgroupsPHPCpuShares==0){$cgroupsPHPCpuShares=256;}
    if($cgroupsPHPDiskIO==0){$cgroupsPHPDiskIO=450;}

    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));

    if($CPU_NUMBER==0){
        $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?CPU-NUMBER=yes"));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CPU_NUMBER",$CPU_NUMBER);
    }

    $cgroupsPHPCpuChoose=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPCpuChoose"));
    $cgroupsPHPDiskBandwidth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPDiskBandwidth"));
    if($cgroupsPHPDiskBandwidth==0){$cgroupsPHPDiskBandwidth=10;}

    for ($i=0;$i<$CPU_NUMBER+1;$i++){
        $CPUZ[0]="{CPU} #".$i+1;
    }

    $CPUSHARE[102]="10%";
    $CPUSHARE[204]="20%";
    $CPUSHARE[256]="25%";
    $CPUSHARE[307]="30%";
    $CPUSHARE[512]="50%";
    $CPUSHARE[620]="60%";
    $CPUSHARE[716]="70%";
    $CPUSHARE[819]="80%";
    $CPUSHARE[921]="90%";
    $CPUSHARE[1024]="100%";


    $BLKIO[100]="10%";
    $BLKIO[200]="20%";
    $BLKIO[250]="25%";
    $BLKIO[300]="30%";
    $BLKIO[450]="45%";
    $BLKIO[500]="50%";
    $BLKIO[700]="70%";
    $BLKIO[800]="80%";
    $BLKIO[900]="90%";
    $BLKIO[1000]="100%";

    $html[]="
	
<table style='width:100%' id='fw-system-info-div-detect'>
<td style='padding:10px;vertical-align:top'>
<div id='system-progress-barr' class='row white-bg'></div>	
";

    $form[]=$tpl->field_none_bt("hostname", "{hostname}", $hostname,"{change}","Loadjs('$page?chhostname-js=yes')");


    $languages=Local_array();
    $langbox[null]="{select}";
    foreach ($languages as $data){$langbox[$data]=$data;}



    $SYSTEM_DISK_SPEED_TEXT=null;
    if($cgroupsEnabled==1){
        if($SYSTEM_DISK_SPEED>0){
            $SYSTEM_DISK_SPEED_TEXT="/{$SYSTEM_DISK_SPEED}MB/s";
        }
        $form[]=$tpl->field_section("{limit_background_processes_consumption}","{enable_processes_limitation_explain}");
        $form[]=$tpl->field_checkbox("cgroupsPHPNonPtime","{only_during_production_time}",$cgroupsPHPNonPtime);
        $form[]=$tpl->field_array_hash($CPUZ, "cgroupsPHPCpuChoose", "{cpu}", $cgroupsPHPCpuChoose);
        $form[]=$tpl->field_array_hash($CPUSHARE, "cgroupsPHPCpuShares", "{cpu_performance} ({artica_processes})", $cgroupsPHPCpuShares);
        $form[]=$tpl->field_array_hash($BLKIO, "cgroupsPHPDiskIO", "{disk_performance} ({artica_processes})", $cgroupsPHPDiskIO);
        $form[]=$tpl->field_numeric("cgroupsPHPDiskBandwidth","{bandwidth} {disk} (MB/s)$SYSTEM_DISK_SPEED_TEXT",$cgroupsPHPDiskBandwidth);



    }

    $jsrestart=$tpl->framework_buildjs("/system/optimize",
        "system.optimize.progress","system.optimize.progress.txt",
        "system-progress-barr","LoadAjax('fw-system-info','$page?infos=yes');");



    $html[]=$tpl->form_outside("{your_server}", @implode("\n", $form),null,"{apply}",$jsrestart,"AsSystemAdministrator");
    $html[]="</tr></table>";


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function SaveConfig(){
    $tpl=new template_admin();
    $sock=new sockets();
    $tpl->CLEAN_POST();

    $LOCALE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCALE"));
    if($LOCALE<>$_POST["LOCALE"]){
        $sock->SET_INFO("LOCALE", $_POST["LOCALE"]);
        $sock->REST_API("/savelocales");

    }


    $sock->SET_INFO("EnableSystemOptimize", $_POST["EnableSystemOptimize"]);
    $sock->SET_INFO("EnableIntelCeleron", $_POST["EnableIntelCeleron"]);

    $cgroups["cgroupsPHPNonPtime"]=true;
    $cgroups["cgroupsPHPCpuChoose"]=true;
    $cgroups["cgroupsPHPCpuShares"]=true;
    $cgroups["cgroupsPHPDiskIO"]=true;
    $cgroups["cgroupsPHPDiskBandwidth"]=true;

    $RESTART_CGROUPS=false;

    foreach ($_POST as $key=>$val){
        if(isset($cgroups[$key])){
            $sock->SET_INFO($key,$val);
            $RESTART_CGROUPS=true;
        }

    }


    if($RESTART_CGROUPS){
        $sock->getFrameWork("cgroup.php?ApplyCgroupConf=yes");
    }




}

function chhostname_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{change_hostname}","$page?chhostname-popup=yes",550);


}
function chhostname_innerHTML(){
    header("content-type: application/x-javascript");
    $hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    echo "document.getElementById('title-hostname').innerHTML='$hostname'\n";
}
function chhostname_save():bool{

    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if($_POST["hostname"]=='null'){return false;}
    $_POST["hostname"]=trim(strtolower($_POST["hostname"]));

    if(!filter_var($_POST["hostname"], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)){
        echo $tpl->_ENGINE_parse_body("jserror:{$_POST["hostname"]}: {not_an_fqdn}");
        return false;
    }

    $t=explode(".",$_POST["hostname"]);
    if(count($t)==1){echo $tpl->_ENGINE_parse_body("jserror:{$_POST["hostname"]}: {not_an_fqdn}");
        return false;
    }

    $hostname=$t[0];
    unset($t[0]);
    $domainname=trim(@implode(".",$t));
    if($domainname==null){
        echo $tpl->_ENGINE_parse_body("jserror:{$_POST["hostname"]}: {not_an_fqdn}");
        return false;
    }
    $sidentity=new sidentity();
    $sidentity->SET("myhostname",$_POST["hostname"]);
    $sidentity->SET("netbiosname",$hostname);
    $sidentity->SET("domain",$domainname);

   $sockets=new sockets();
   $data=$sockets->REST_API("/system/network/nohup/hostname/{$_POST["hostname"]}");
   $json=json_decode($data);
   if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg());
        return false;
    }
   if(!$json->Status){
       echo $tpl->post_error("Failed");
       return false;
   }
   $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/widget/hostname");
   sleep(1);
   return admin_tracks("Modify System hostname to {$_POST["hostname"]}");
}

function chhostname_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js[]="if(document.getElementById('fw-system-info')){";
    $js[]="LoadAjax('fw.system.info.php','$page?infos=yes');";
    $js[]="}";
    $js[]="if(document.getElementById('table-loader-system')){";
    $js[]="LoadAjax('table-loader-system','fw.system.information.php?table=yes');";
    $js[]="}Loadjs('$page?title-hostname=yes');dialogInstance1.close();";
    $hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $form[]=$tpl->field_text("hostname", "{hostname}", $hostname,true);
    $html[]=$tpl->form_outside("{modify_hostname}", $form,null,"{apply}",@implode("",$js),"AsSystemAdministrator");


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function Local_array(){
    $f[]="aa_DJ.UTF-8 UTF-8";
    $f[]="aa_DJ ISO-8859-1";
    $f[]="aa_ER UTF-8";
    $f[]="aa_ER@saaho UTF-8";
    $f[]="aa_ET UTF-8";
    $f[]="af_ZA.UTF-8 UTF-8";
    $f[]="af_ZA ISO-8859-1";
    $f[]="am_ET UTF-8";
    $f[]="an_ES.UTF-8 UTF-8";
    $f[]="an_ES ISO-8859-15";
    $f[]="ar_AE.UTF-8 UTF-8";
    $f[]="ar_AE ISO-8859-6";
    $f[]="ar_BH.UTF-8 UTF-8";
    $f[]="ar_BH ISO-8859-6";
    $f[]="ar_DZ.UTF-8 UTF-8";
    $f[]="ar_DZ ISO-8859-6";
    $f[]="ar_EG.UTF-8 UTF-8";
    $f[]="ar_EG ISO-8859-6";
    $f[]="ar_IN UTF-8";
    $f[]="ar_IQ.UTF-8 UTF-8";
    $f[]="ar_IQ ISO-8859-6";
    $f[]="ar_JO.UTF-8 UTF-8";
    $f[]="ar_JO ISO-8859-6";
    $f[]="ar_KW.UTF-8 UTF-8";
    $f[]="ar_KW ISO-8859-6";
    $f[]="ar_LB.UTF-8 UTF-8";
    $f[]="ar_LB ISO-8859-6";
    $f[]="ar_LY.UTF-8 UTF-8";
    $f[]="ar_LY ISO-8859-6";
    $f[]="ar_MA.UTF-8 UTF-8";
    $f[]="ar_MA ISO-8859-6";
    $f[]="ar_OM.UTF-8 UTF-8";
    $f[]="ar_OM ISO-8859-6";
    $f[]="ar_QA.UTF-8 UTF-8";
    $f[]="ar_QA ISO-8859-6";
    $f[]="ar_SA.UTF-8 UTF-8";
    $f[]="ar_SA ISO-8859-6";
    $f[]="ar_SD.UTF-8 UTF-8";
    $f[]="ar_SD ISO-8859-6";
    $f[]="ar_SY.UTF-8 UTF-8";
    $f[]="ar_SY ISO-8859-6";
    $f[]="ar_TN.UTF-8 UTF-8";
    $f[]="ar_TN ISO-8859-6";
    $f[]="ar_YE.UTF-8 UTF-8";
    $f[]="ar_YE ISO-8859-6";
    $f[]="az_AZ.UTF-8 UTF-8";
    $f[]="as_IN.UTF-8 UTF-8";
    $f[]="ast_ES.UTF-8 UTF-8";
    $f[]="ast_ES ISO-8859-15";
    $f[]="be_BY.UTF-8 UTF-8";
    $f[]="be_BY CP1251";
    $f[]="be_BY@latin UTF-8";
    $f[]="ber_DZ UTF-8";
    $f[]="ber_MA UTF-8";
    $f[]="bg_BG.UTF-8 UTF-8";
    $f[]="bg_BG CP1251";
    $f[]="bn_BD UTF-8";
    $f[]="bn_IN UTF-8";
    $f[]="br_FR.UTF-8 UTF-8";
    $f[]="br_FR ISO-8859-1";
    $f[]="br_FR@euro ISO-8859-15";
    $f[]="bs_BA.UTF-8 UTF-8";
    $f[]="bs_BA ISO-8859-2";
    $f[]="byn_ER UTF-8";
    $f[]="ca_AD.UTF-8 UTF-8";
    $f[]="ca_AD ISO-8859-15";
    $f[]="ca_ES.UTF-8 UTF-8";
    $f[]="ca_ES ISO-8859-1";
    $f[]="ca_ES@euro ISO-8859-15";
    $f[]="ca_ES.UTF-8@valencia UTF-8";
    $f[]="ca_ES@valencia ISO-8859-15";
    $f[]="ca_FR.UTF-8 UTF-8";
    $f[]="ca_FR ISO-8859-15";
    $f[]="ca_IT.UTF-8 UTF-8";
    $f[]="ca_IT ISO-8859-15";
    $f[]="crh_UA UTF-8";
    $f[]="cs_CZ.UTF-8 UTF-8";
    $f[]="cs_CZ ISO-8859-2";
    $f[]="csb_PL UTF-8";
    $f[]="cy_GB.UTF-8 UTF-8";
    $f[]="cy_GB ISO-8859-14";
    $f[]="da_DK.UTF-8 UTF-8";
    $f[]="da_DK ISO-8859-1";
    $f[]="da_DK.ISO-8859-15 ISO-8859-15";
    $f[]="de_AT.UTF-8 UTF-8";
    $f[]="de_AT ISO-8859-1";
    $f[]="de_AT@euro ISO-8859-15";
    $f[]="de_BE.UTF-8 UTF-8";
    $f[]="de_BE ISO-8859-1";
    $f[]="de_BE@euro ISO-8859-15";
    $f[]="de_CH.UTF-8 UTF-8";
    $f[]="de_CH ISO-8859-1";
    $f[]="de_DE.UTF-8 UTF-8";
    $f[]="de_DE ISO-8859-1";
    $f[]="de_DE@euro ISO-8859-15";
    $f[]="de_LI.UTF-8 UTF-8";
    $f[]="de_LU.UTF-8 UTF-8";
    $f[]="de_LU ISO-8859-1";
    $f[]="de_LU@euro ISO-8859-15";
    $f[]="dz_BT UTF-8";
    $f[]="el_GR.UTF-8 UTF-8";
    $f[]="el_GR ISO-8859-7";
    $f[]="el_CY.UTF-8 UTF-8";
    $f[]="el_CY ISO-8859-7";
    $f[]="en_AU.UTF-8 UTF-8";
    $f[]="en_AU ISO-8859-1";
    $f[]="en_BW.UTF-8 UTF-8";
    $f[]="en_BW ISO-8859-1";
    $f[]="en_CA.UTF-8 UTF-8";
    $f[]="en_CA ISO-8859-1";
    $f[]="en_DK.UTF-8 UTF-8";
    $f[]="en_DK.ISO-8859-15 ISO-8859-15";
    $f[]="en_DK ISO-8859-1";
    $f[]="en_GB.UTF-8 UTF-8";
    $f[]="en_GB ISO-8859-1";
    $f[]="en_GB.ISO-8859-15 ISO-8859-15";
    $f[]="en_HK.UTF-8 UTF-8";
    $f[]="en_HK ISO-8859-1";
    $f[]="en_IE.UTF-8 UTF-8";
    $f[]="en_IE ISO-8859-1";
    $f[]="en_IE@euro ISO-8859-15";
    $f[]="en_IN UTF-8";
    $f[]="en_NG UTF-8";
    $f[]="en_NZ.UTF-8 UTF-8";
    $f[]="en_NZ ISO-8859-1";
    $f[]="en_PH.UTF-8 UTF-8";
    $f[]="en_PH ISO-8859-1";
    $f[]="en_SG.UTF-8 UTF-8";
    $f[]="en_SG ISO-8859-1";
    $f[]="en_US.UTF-8 UTF-8";
    $f[]="en_US ISO-8859-1";
    $f[]="en_US.ISO-8859-15 ISO-8859-15";
    $f[]="en_ZA.UTF-8 UTF-8";
    $f[]="en_ZA ISO-8859-1";
    $f[]="en_ZW.UTF-8 UTF-8";
    $f[]="en_ZW ISO-8859-1";
    $f[]="eo.UTF-8 UTF-8";
    $f[]="eo ISO-8859-3";
    $f[]="es_AR.UTF-8 UTF-8";
    $f[]="es_AR ISO-8859-1";
    $f[]="es_BO.UTF-8 UTF-8";
    $f[]="es_BO ISO-8859-1";
    $f[]="es_CL.UTF-8 UTF-8";
    $f[]="es_CL ISO-8859-1";
    $f[]="es_CO.UTF-8 UTF-8";
    $f[]="es_CO ISO-8859-1";
    $f[]="es_CR.UTF-8 UTF-8";
    $f[]="es_CR ISO-8859-1";
    $f[]="es_DO.UTF-8 UTF-8";
    $f[]="es_DO ISO-8859-1";
    $f[]="es_EC.UTF-8 UTF-8";
    $f[]="es_EC ISO-8859-1";
    $f[]="es_ES.UTF-8 UTF-8";
    $f[]="es_ES ISO-8859-1";
    $f[]="es_ES@euro ISO-8859-15";
    $f[]="es_GT.UTF-8 UTF-8";
    $f[]="es_GT ISO-8859-1";
    $f[]="es_HN.UTF-8 UTF-8";
    $f[]="es_HN ISO-8859-1";
    $f[]="es_MX.UTF-8 UTF-8";
    $f[]="es_MX ISO-8859-1";
    $f[]="es_NI.UTF-8 UTF-8";
    $f[]="es_NI ISO-8859-1";
    $f[]="es_PA.UTF-8 UTF-8";
    $f[]="es_PA ISO-8859-1";
    $f[]="es_PE.UTF-8 UTF-8";
    $f[]="es_PE ISO-8859-1";
    $f[]="es_PR.UTF-8 UTF-8";
    $f[]="es_PR ISO-8859-1";
    $f[]="es_PY.UTF-8 UTF-8";
    $f[]="es_PY ISO-8859-1";
    $f[]="es_SV.UTF-8 UTF-8";
    $f[]="es_SV ISO-8859-1";
    $f[]="es_US.UTF-8 UTF-8";
    $f[]="es_US ISO-8859-1";
    $f[]="es_UY.UTF-8 UTF-8";
    $f[]="es_UY ISO-8859-1";
    $f[]="es_VE.UTF-8 UTF-8";
    $f[]="es_VE ISO-8859-1";
    $f[]="et_EE.UTF-8 UTF-8";
    $f[]="et_EE ISO-8859-1";
    $f[]="et_EE.ISO-8859-15 ISO-8859-15";
    $f[]="eu_ES.UTF-8 UTF-8";
    $f[]="eu_ES ISO-8859-1";
    $f[]="eu_ES@euro ISO-8859-15";
    $f[]="eu_FR.UTF-8 UTF-8";
    $f[]="eu_FR ISO-8859-1";
    $f[]="eu_FR@euro ISO-8859-15";
    $f[]="fa_IR UTF-8";
    $f[]="fi_FI.UTF-8 UTF-8";
    $f[]="fi_FI ISO-8859-1";
    $f[]="fi_FI@euro ISO-8859-15";
    $f[]="fil_PH UTF-8";
    $f[]="fo_FO.UTF-8 UTF-8";
    $f[]="fo_FO ISO-8859-1";
    $f[]="fr_BE.UTF-8 UTF-8";
    $f[]="fr_BE ISO-8859-1";
    $f[]="fr_BE@euro ISO-8859-15";
    $f[]="fr_CA.UTF-8 UTF-8";
    $f[]="fr_CA ISO-8859-1";
    $f[]="fr_CH.UTF-8 UTF-8";
    $f[]="fr_CH ISO-8859-1";
    $f[]="fr_FR.UTF-8 UTF-8";
    $f[]="fr_FR ISO-8859-1";
    $f[]="fr_FR@euro ISO-8859-15";
    $f[]="fr_LU.UTF-8 UTF-8";
    $f[]="fr_LU ISO-8859-1";
    $f[]="fr_LU@euro ISO-8859-15";
    $f[]="fur_IT UTF-8";
    $f[]="fy_NL UTF-8";
    $f[]="fy_DE UTF-8";
    $f[]="ga_IE.UTF-8 UTF-8";
    $f[]="ga_IE ISO-8859-1";
    $f[]="ga_IE@euro ISO-8859-15";
    $f[]="gd_GB.UTF-8 UTF-8";
    $f[]="gd_GB ISO-8859-15";
    $f[]="gez_ER UTF-8";
    $f[]="gez_ER@abegede UTF-8";
    $f[]="gez_ET UTF-8";
    $f[]="gez_ET@abegede UTF-8";
    $f[]="gl_ES.UTF-8 UTF-8";
    $f[]="gl_ES ISO-8859-1";
    $f[]="gl_ES@euro ISO-8859-15";
    $f[]="gu_IN UTF-8";
    $f[]="gv_GB.UTF-8 UTF-8";
    $f[]="gv_GB ISO-8859-1";
    $f[]="ha_NG UTF-8";
    $f[]="he_IL.UTF-8 UTF-8";
    $f[]="he_IL ISO-8859-8";
    $f[]="hi_IN UTF-8";
    $f[]="hr_HR.UTF-8 UTF-8";
    $f[]="hr_HR ISO-8859-2";
    $f[]="hsb_DE.UTF-8 UTF-8";
    $f[]="hsb_DE ISO-8859-2";
    $f[]="hu_HU.UTF-8 UTF-8";
    $f[]="hu_HU ISO-8859-2";
    $f[]="hy_AM UTF-8";
    $f[]="hy_AM.ARMSCII-8 ARMSCII-8";
    $f[]="ia UTF-8";
    $f[]="id_ID.UTF-8 UTF-8";
    $f[]="id_ID ISO-8859-1";
    $f[]="ig_NG UTF-8";
    $f[]="ik_CA UTF-8";
    $f[]="is_IS.UTF-8 UTF-8";
    $f[]="is_IS ISO-8859-1";
    $f[]="it_CH.UTF-8 UTF-8";
    $f[]="it_CH ISO-8859-1";
    $f[]="it_IT.UTF-8 UTF-8";
    $f[]="it_IT ISO-8859-1";
    $f[]="it_IT@euro ISO-8859-15";
    $f[]="iu_CA UTF-8";
    $f[]="iw_IL.UTF-8 UTF-8";
    $f[]="iw_IL ISO-8859-8";
    $f[]="ja_JP.UTF-8 UTF-8";
    $f[]="ja_JP.EUC-JP EUC-JP";
    $f[]="ka_GE.UTF-8 UTF-8";
    $f[]="ka_GE GEORGIAN-PS";
    $f[]="kk_KZ.UTF-8 UTF-8";
    $f[]="kk_KZ PT154";
    $f[]="kl_GL.UTF-8 UTF-8";
    $f[]="kl_GL ISO-8859-1";
    $f[]="km_KH UTF-8";
    $f[]="kn_IN UTF-8";
    $f[]="ko_KR.UTF-8 UTF-8";
    $f[]="ko_KR.EUC-KR EUC-KR";
    $f[]="ks_IN UTF-8";
    $f[]="ku_TR.UTF-8 UTF-8";
    $f[]="ku_TR ISO-8859-9";
    $f[]="kw_GB.UTF-8 UTF-8";
    $f[]="kw_GB ISO-8859-1";
    $f[]="ky_KG UTF-8";
    $f[]="lg_UG.UTF-8 UTF-8";
    $f[]="lg_UG ISO-8859-10";
    $f[]="li_BE UTF-8";
    $f[]="li_NL UTF-8";
    $f[]="lo_LA UTF-8";
    $f[]="lt_LT.UTF-8 UTF-8";
    $f[]="lt_LT ISO-8859-13";
    $f[]="lv_LV.UTF-8 UTF-8";
    $f[]="lv_LV ISO-8859-13";
    $f[]="mai_IN UTF-8";
    $f[]="mg_MG.UTF-8 UTF-8";
    $f[]="mg_MG ISO-8859-15";
    $f[]="mi_NZ.UTF-8 UTF-8";
    $f[]="mi_NZ ISO-8859-13";
    $f[]="mk_MK.UTF-8 UTF-8";
    $f[]="mk_MK ISO-8859-5";
    $f[]="ml_IN UTF-8";
    $f[]="mn_MN UTF-8";
    $f[]="mr_IN UTF-8";
    $f[]="ms_MY.UTF-8 UTF-8";
    $f[]="ms_MY ISO-8859-1";
    $f[]="mt_MT.UTF-8 UTF-8";
    $f[]="mt_MT ISO-8859-3";
    $f[]="nb_NO.UTF-8 UTF-8";
    $f[]="nb_NO ISO-8859-1";
    $f[]="nds_DE UTF-8";
    $f[]="nds_NL UTF-8";
    $f[]="ne_NP UTF-8";
    $f[]="nl_BE.UTF-8 UTF-8";
    $f[]="nl_BE ISO-8859-1";
    $f[]="nl_BE@euro ISO-8859-15";
    $f[]="nl_NL.UTF-8 UTF-8";
    $f[]="nl_NL ISO-8859-1";
    $f[]="nl_NL@euro ISO-8859-15";
    $f[]="nn_NO.UTF-8 UTF-8";
    $f[]="nn_NO ISO-8859-1";
    $f[]="nr_ZA UTF-8";
    $f[]="nso_ZA UTF-8";
    $f[]="oc_FR.UTF-8 UTF-8";
    $f[]="oc_FR ISO-8859-1";
    $f[]="om_ET UTF-8";
    $f[]="om_KE.UTF-8 UTF-8";
    $f[]="om_KE ISO-8859-1";
    $f[]="or_IN UTF-8";
    $f[]="pa_IN UTF-8";
    $f[]="pa_PK UTF-8";
    $f[]="pap_AN UTF-8";
    $f[]="pl_PL.UTF-8 UTF-8";
    $f[]="pl_PL ISO-8859-2";
    $f[]="pt_BR.UTF-8 UTF-8";
    $f[]="pt_BR ISO-8859-1";
    $f[]="pt_PT.UTF-8 UTF-8";
    $f[]="pt_PT ISO-8859-1";
    $f[]="pt_PT@euro ISO-8859-15";
    $f[]="ro_RO.UTF-8 UTF-8";
    $f[]="ro_RO ISO-8859-2";
    $f[]="ru_RU.UTF-8 UTF-8";
    $f[]="ru_RU.KOI8-R KOI8-R";
    $f[]="ru_RU ISO-8859-5";
    $f[]="ru_RU.CP1251 CP1251";
    $f[]="ru_UA.UTF-8 UTF-8";
    $f[]="ru_UA KOI8-U";
    $f[]="rw_RW UTF-8";
    $f[]="sa_IN UTF-8";
    $f[]="sc_IT UTF-8";
    $f[]="se_NO UTF-8";
    $f[]="si_LK UTF-8";
    $f[]="sid_ET UTF-8";
    $f[]="sk_SK.UTF-8 UTF-8";
    $f[]="sk_SK ISO-8859-2";
    $f[]="sl_SI.UTF-8 UTF-8";
    $f[]="sl_SI ISO-8859-2";
    $f[]="so_DJ.UTF-8 UTF-8";
    $f[]="so_DJ ISO-8859-1";
    $f[]="so_ET UTF-8";
    $f[]="so_KE.UTF-8 UTF-8";
    $f[]="so_KE ISO-8859-1";
    $f[]="so_SO.UTF-8 UTF-8";
    $f[]="so_SO ISO-8859-1";
    $f[]="sq_AL.UTF-8 UTF-8";
    $f[]="sq_AL ISO-8859-1";
    $f[]="sr_ME UTF-8";
    $f[]="sr_RS UTF-8";
    $f[]="sr_RS@latin UTF-8";
    $f[]="ss_ZA UTF-8";
    $f[]="st_ZA.UTF-8 UTF-8";
    $f[]="st_ZA ISO-8859-1";
    $f[]="sv_FI.UTF-8 UTF-8";
    $f[]="sv_FI ISO-8859-1";
    $f[]="sv_FI@euro ISO-8859-15";
    $f[]="sv_SE.UTF-8 UTF-8";
    $f[]="sv_SE ISO-8859-1";
    $f[]="sv_SE.ISO-8859-15 ISO-8859-15";
    $f[]="ta_IN UTF-8";
    $f[]="te_IN UTF-8";
    $f[]="tg_TJ.UTF-8 UTF-8";
    $f[]="tg_TJ KOI8-T";
    $f[]="th_TH.UTF-8 UTF-8";
    $f[]="th_TH TIS-620";
    $f[]="ti_ER UTF-8";
    $f[]="ti_ET UTF-8";
    $f[]="tig_ER UTF-8";
    $f[]="tk_TM UTF-8";
    $f[]="tl_PH.UTF-8 UTF-8";
    $f[]="tl_PH ISO-8859-1";
    $f[]="tn_ZA UTF-8";
    $f[]="tr_CY.UTF-8 UTF-8";
    $f[]="tr_CY ISO-8859-9";
    $f[]="tr_TR.UTF-8 UTF-8";
    $f[]="tr_TR ISO-8859-9";
    $f[]="ts_ZA UTF-8";
    $f[]="tt_RU.UTF-8 UTF-8";
    $f[]="tt_RU@iqtelif.UTF-8 UTF-8";
    $f[]="ug_CN UTF-8";
    $f[]="uk_UA.UTF-8 UTF-8";
    $f[]="uk_UA KOI8-U";
    $f[]="ur_PK UTF-8";
    $f[]="uz_UZ.UTF-8 UTF-8";
    $f[]="uz_UZ ISO-8859-1";
    $f[]="uz_UZ@cyrillic UTF-8";
    $f[]="ve_ZA UTF-8";
    $f[]="vi_VN UTF-8";
    $f[]="vi_VN.TCVN TCVN5712-1";
    $f[]="wa_BE.UTF-8 UTF-8";
    $f[]="wa_BE ISO-8859-1";
    $f[]="wa_BE@euro ISO-8859-15";
    $f[]="wo_SN UTF-8";
    $f[]="xh_ZA.UTF-8 UTF-8";
    $f[]="xh_ZA ISO-8859-1";
    $f[]="yi_US.UTF-8 UTF-8";
    $f[]="yi_US CP1255";
    $f[]="yo_NG UTF-8";
    $f[]="zh_CN.UTF-8 UTF-8";
    $f[]="zh_CN.GB18030 GB18030";
    $f[]="zh_CN.GBK GBK";
    $f[]="zh_CN GB2312";
    $f[]="zh_HK.UTF-8 UTF-8";
    $f[]="zh_HK BIG5-HKSCS";
    $f[]="zh_SG.UTF-8 UTF-8";
    $f[]="zh_SG.GBK GBK";
    $f[]="zh_SG GB2312";
    $f[]="zh_TW.UTF-8 UTF-8";
    $f[]="zh_TW BIG5";
    $f[]="zh_TW.EUC-TW EUC-TW";
    $f[]="zu_ZA.UTF-8 UTF-8";
    $f[]="zu_ZA ISO-8859-1";


    return $f;
}
