<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

if(isset($_POST["EnableAtomicCorp"])){Save();exit;}
if(isset($_POST["ModSecurityRetentionDays"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters-start"])){parameters_start();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["statistics-parameters"])){statistics_engine();exit;}
if(isset($_GET["protocols-js"])){protocols_js();exit;}
if(isset($_GET["protocols-popup"])){protocols_popup();exit;}
if(isset($_POST["PROTO_GET"])){protocols_save();exit;}

page();

function protocols_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{allow}: {method}","$page?protocols-popup=yes");
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
$ModSecurityPatternCount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPatternCount"));
$ModSecurityPatternVersion=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPatternVersion"));
	$title=$tpl->_ENGINE_parse_body("Web Firewall");

    $html=$tpl->page_header("Atomic WAF",
        "atomiccorp-107.png","{atomoccorp_explain}","$page?tabs=yes","atomic","progress-atomic");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: $title",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function protocols_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $available=explode(" ","GET HEAD POST OPTIONS PUT PATCH DELETE CHECKOUT COPY DELETE LOCK MERGE MKACTIVITY MKCOL MOVE PROPFIND PROPPATCH PUT UNLOCK TRACE CONNECT");

    $ModSecurityProtocols=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityProtocols"));
    if(!is_array($ModSecurityProtocols)){$ModSecurityProtocols=array();}
    if(count($ModSecurityProtocols)==0){
        $ModSecurityProtocols=array("GET"=>1,"HEAD"=>1, "POST"=>1,"OPTIONS"=>1);
    }

    foreach ($available as $proto){
        $proto=trim($proto);
        if($proto==null){continue;}
        $value=0;
        if(isset($ALREADY[$proto])){continue;}
        if(isset($ModSecurityProtocols[$proto])){$value=intval($ModSecurityProtocols[$proto]);}
        $form[]=$tpl->field_checkbox("PROTO_$proto",$proto,$value);
        $ALREADY[$proto]=true;

    }


    $html[]=$tpl->form_outside(null, $form,"{modsecurity_allow_protocol}","{apply}","LoadAjaxSilent('modsec-params','$page?parameters=yes');dialogInstance1.close();","AsWebMaster",true);
    echo $tpl->_ENGINE_parse_body($html);


}


function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{parameters}"]="$page?parameters-start=yes";
    $array["{statistics_parameters}"]="$page?statistics-parameters=yes";
    $array["{default_rules}"]="fw.modsecurity.defrules.php";
    echo $tpl->tabs_default($array);
}
function parameters_start(){
    $page=CurrentPageName();
    echo "
<div id='atomiccorp-params' style='margin-top:10px'></div><script>LoadAjaxSilent('atomiccorp-params','$page?parameters=yes');</script>";
}

function statistics_engine(){

}

function parameters(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $EnableAtomicCorp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAtomicCorp"));
    $TheShieldsLicense=$tpl->TheShieldsLicenseStatus();
    $kInfos            =$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
    $LicenseActive=intval($kInfos["enable"]);
    if($LicenseActive==0){$EnableAtomicCorp=0;}

    $jsupdate=$tpl->framework_buildjs("nginx.php?atomi-update=yes","atomi.progress","atomi.log",
        "progress-atomic","LoadAjaxSilent('atomiccorp-params','$page?parameters=yes');");

    if($EnableAtomicCorp==0){

        $jenable=$tpl->framework_buildjs("nginx.php?atomi-enable=yes","atomi.progress","atomi.log",
            "progress-atomic","LoadAjaxSilent('atomiccorp-params','$page?parameters=yes');");

        if($LicenseActive==1) {
            $btn[0]["js"] = $jenable;
            $btn[0]["name"] = "{activate}";
            $btn[0]["icon"] = "far fa-shield-check";
        }
        $sstatus = $tpl->widget_grey("{service_status}", "{disabled}", $btn);

    }
    if($EnableAtomicCorp==1){
        $jenable=$tpl->framework_buildjs("nginx.php?atomi-disable=yes","atomi.progress","atomi.log",
            "progress-atomic","LoadAjaxSilent('atomiccorp-params','$page?parameters=yes');");
        $btn[0]["js"] = $jenable;
        $btn[0]["name"] = "{uninstall}";
        $btn[0]["icon"] = "far fa-shield-check";
        $sstatus = $tpl->widget_vert("{service_status}", "{active2}", $btn);

        $ATOMI_MODSEC_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ATOMI_MODSEC_VERSION"));

        if($ATOMI_MODSEC_VERSION>0) {
            $btn[0]["js"] = $jsupdate;
            $btn[0]["name"] = "{update}";
            $btn[0]["icon"] = "far fa-shield-check";
            $sversion = $tpl->widget_vert("{databases_version}", $ATOMI_MODSEC_VERSION, $btn);
        }

    }

    $html[]="<table style='width:63%'>";
    $html[]="<tr>";

    $html[]="<td style='width:33%'>";
    $html[]=$TheShieldsLicense;
    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left:15px'>";
    $html[]=$sstatus;
    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left:15px'>";
    $html[]=$sversion;
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $jsafter=$tpl->framework_buildjs("nginx.php?atomi-update=yes","atomi.progress","atomi.log",
        "progress-atomic","LoadAjaxSilent('atomiccorp-params','$page?parameters=yes');");



	echo $tpl->_ENGINE_parse_body($html);
	
}


function Save(){
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks("Saving Web application Firewall Atomic CORP databases enable:{$_POST["EnableAtomicCorp"]}");
	$tpl->SAVE_POSTs();
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}