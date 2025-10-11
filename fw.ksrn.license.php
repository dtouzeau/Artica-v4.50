<?php

$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["KRSN_DEBUG"])){Save();exit;}
if(isset($_GET["ksrn-license-status"])){status();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["js-popup"])){js_popup();exit;}
if(isset($_GET["refreshjs"])){refreshjs();exit;}

page();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog7("{KSRN}: {license}","$page?js-popup=yes");
}

function js_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='progress-ksrnlics-restart'></div>";
    $html[]="<div id='table-loader-ksrnlics-pages'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('table-loader-ksrnlics-pages','$page?table=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<table style='width:100%'>
	    <tr>
	        <td valign='top' style='padding-right: 10px'><i class='fa-8x fas fa-shield-virus'></i></td>
	        <td valign='top'><div class=\"col-sm-12\"><h1 class=ng-binding>{KSRN}</h1><p>{KSRN_EXPLAIN}</p></div></td>
        </tr>
	
    </table>
		
	</div>
	<div class='row'>
	<div id='progress-ksrnlics-restart'></div>";
$html[]="</div><div class='row'><div class='ibox-content'>";
	$html[]="
	<div id='table-loader-ksrnlics-pages'></div>
	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/the-shields-license');
	LoadAjax('table-loader-ksrnlics-pages','$page?table=yes');
	</script>";

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,@implode("\n",$html));echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function k_info_disabled($kInfos,$pstyle):string{
    $tpl=new template_admin();
    if(isset($kInfos["expire"])){
        $expire=intval($kInfos["expire"]);
        if($expire>0){
            if($expire<time()) {
                return "<p style='$pstyle'>{ksrn_license_explain}</p>" .
                    $tpl->button_autnonome("{verify_the_license}", check_js(), "fad fa-clock", null, 400, "btn-primary");
            }
        }
    }

    return "<p style='$pstyle'>{ksrn_trial_explain}</p>".
        $tpl->button_autnonome("{get_a_free_trial_period}",trial_js(),"fad fa-clock",null,400,"btn-primary")."";

}

function table(){


	$tpl=new template_admin();
	$page=CurrentPageName();
	$kInfos         = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos")));
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}



$pstyle='text-align: left;margin-bottom:20px';
            $form[] = "<div  style='$pstyle'>{ksrn_license_explain}</div>
<div class='center'>" .
                $tpl->button_autnonome("{verify_the_license}", check_js(), "fad fa-clock", null, 400, "btn-primary") . "</div>";


	$html="
	<div id='ksrn-license-status' style='margin-top:15px;width:589px'></div>
	<div  style='margin-top:15px;width:589px'>".@implode("\n",$form)."</div>
	<script>LoadAjaxSilent('ksrn-license-status','$page?ksrn-license-status=yes');</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function refreshjs(){
    $page=CurrentPageName();
    $f[]="if (document.getElementById('atomiccorp-params') ){";
    $f[]="LoadAjaxSilent('atomiccorp-params','fw.modsecurity.atomic.php?parameters=yes');";
    $f[]="}";

    $f[]="if (document.getElementById('ksrn-license-status') ){";
    $f[]="LoadAjaxSilent('ksrn-license-status','$page?ksrn-license-status=yes');";
    $f[]="}";


    echo @implode("\n",$f);
}

function trial_js():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.k.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica.k.log";
    $ARRAY["CMD"]="ksrn.php?trial=yes";
    $ARRAY["TITLE"]="{trial_period}";
    $ARRAY["AFTER"]="Loadjs('$page?refreshjs=yes');";
    $ARRAY["AFTER_FAILED"]="LoadAjaxSilent('ksrn-license-status','$page?ksrn-license-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-ksrnlics-restart')";
}
function check_js():string{
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.k.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica.k.log";
    $ARRAY["CMD"]="ksrn.php?check=yes";
    $ARRAY["TITLE"]="{verify_the_license}";
    $ARRAY["AFTER"]="Loadjs('$page?refreshjs=yes');";
    $ARRAY["AFTER_FAILED"]="LoadAjaxSilent('ksrn-license-status','$page?ksrn-license-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?content=$prgress&mainid=progress-ksrnlics-restart')";
}
function status(){
    if($GLOBALS["VERBOSE"]){VERBOSE(__FUNCTION__,__LINE__);}
    $tpl            = new template_admin();
    $Val            =$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos");
    $Val            = base64_decode($Val);
    $kInfos         = unserialize($Val);
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}
    if(!isset($kInfos["status"])){$kInfos["status"]=null;}

    if($GLOBALS["VERBOSE"]){
        foreach ($kInfos as $index=>$val){
            VERBOSE("[$index]=$val",__LINE__);

        }

    }
    $uuidRea=base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?system-unique-id=yes"));
    $uuid="<br><small>{uuid}: $uuidRea</small>";


    if($kInfos["enable"]==0 && $kInfos["status"]==null){

        $html[]="<div class='widget gray-bg p-lg text-center'>
			<div class='m-b-md'>
			<i class='fa fa-key fa-4x'></i>";
        $html[]="<h1 class='m-xs'>{license_status}</h1>";
        $html[] = "<h3 class='font-bold no-margins'>{license_invalid}$uuid</h3>";
        $html[] = "</div>";
        $html[] = "</div>";

        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }


    VERBOSE("STATUS: ".$kInfos["status"]." Enable ={$kInfos["enable"]}",__LINE__);
    if($kInfos["enable"]==0){
        if($kInfos["status"]=="{license_active}"){
            $kInfos["status"]=="{refresh} {license}";
        }


        $html[]="<div class='widget gray-bg p-lg text-center'>
			<div class='m-b-md'>
			<i class='fa fa-key fa-4x'></i>";
	    $html[]="<h1 class='m-xs'>{license_status}</h1>";
        $html[] = "<h3 class='font-bold no-margins'>{$kInfos["status"]}$uuid</h3>";
        $html[] = "</div>";
        $html[] = "</div>";

        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    if($kInfos["enable"]==1){
        if(!isset($kInfos["ispaid"])){$kInfos["ispaid"]=0;}
        if(intval($kInfos["expire"])>0){
            VERBOSE("Expire in {$kInfos["expire"]}",__LINE__);
            $reste_days=$tpl->TimeToDays($kInfos["expire"]);
            if($reste_days<15){
                $html[]="<div class='widget yellow-bg p-lg text-center'>
			<div class='m-b-md'>
			<i class='fa fa-key fa-4x'></i>";
                $html[]="<h1 class='m-xs'>{trial_period}</h1>";
                $html[] = "<h3 class='font-bold no-margins'>{expire_in}: $reste_days {days}</h3>";
                $html[] = "<h4 class='font-bold text-white'>{uuid}: $uuidRea</h4>";
                $html[] = "</div>";
                $html[] = "</div>";
                echo $tpl->_ENGINE_parse_body($html);
                return false;
            }
            $color = "yellow";
            $error = "{not_paid_license}";

            if($kInfos["ispaid"]==1) {
                $color = "navy";
                $error = "{paid_license}";
            }


                $html[]="<div class='widget {$color}-bg p-lg text-center'>
			<div class='m-b-md'>
			<i class='fa fa-key fa-4x'></i>";
                $html[]="<h1 class='m-xs'>{$kInfos["status"]}</h1>";
                $html[] = "<h3 class='font-bold no-margins'>{expire_in}: $reste_days {days}</h3>";
                $html[] = "<h4 class='font-bold' style='margin-top:15px'>$error</h4>";
                $html[] = "<h4 class='font-bold text-white'>{uuid}: $uuidRea</h4>";
                $html[] = "</div>";
                $html[] = "</div>";
                echo $tpl->_ENGINE_parse_body($html);
                return false;

            }

            if(intval($kInfos["expire"])==0){
                if($kInfos["status"]=="{gold_license}"){
                    $color = "navy";
                    $html[]="<div class='widget {$color}-bg p-lg text-center'>
			<div class='m-b-md'>
			<i class='fa fa-key fa-4x'></i>";
                    $html[]="<h1 class='m-xs'>{$kInfos["status"]}</h1>";
                    $html[] = "<h3 class='font-bold no-margins'>{expire_in}: {never}$uuid</h3>";
                    $html[] = "</div>";
                    $html[] = "</div>";
                    echo $tpl->_ENGINE_parse_body($html);
                    return true;
                }


            }


        }
    return false;
}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$KSRN_DAEMONS=base64_encode(serialize($_POST));
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KRSN_DEBUG",$_POST["KRSN_DEBUG"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRN_DAEMONS",$KSRN_DAEMONS);

}
