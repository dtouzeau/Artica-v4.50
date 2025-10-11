<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["main-flat"])){main_flat();exit;}

if(isset($_POST["statscomHideMacs"])){save();exit;}
if(isset($_GET["main-js"])){main_js();exit;}
if(isset($_GET["main-popup"])){main_popup();exit;}
page();

function page(){
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $html="

	<div class='row'>
	

	<div id='table-loader'></div>

	
	</div>
	<script>
	
	LoadAjax('table-loader','$page?main=yes');

	</script>";
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

function main(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html[]="
    <div id='statscom-restart-settings'></div>
    <table style='width:100%'>
	<tr>
		<td	style='vertical-align:top;width:90%'><div id='statscom-params'></div></td>
	</tr>
	</table>
    <script>LoadAjax('statscom-params','$page?main-flat=yes');</script></script>
	";

    echo @implode("\n",$html);
    return true;

}

function main_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{settings}","$page?main-popup=yes",650);
}

function main_flat():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $hideMacs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("statscomHideMacs"));
    $hideUnkownMembers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("statscomHideUnkownMembers"));
    $StatsComDebugLelvel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComDebugLelvel"));
    for($i=0;$i<5;$i++){
        $DEBUG_LEVEL[$i]=$i;
    }
    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $InfluxAdminRetentionTime=5;
    }
    $tpl->table_form_field_js("Loadjs('$page?main-js=yes')");
    $tpl->table_form_field_text("{StatisticsRetentionPeriod}","$InfluxAdminRetentionTime {days}",ico_timeout);
    $tpl->table_form_field_bool("{statscomHideMacs}",$hideMacs,ico_params);
    $tpl->table_form_field_bool("{statscomHideUnkownMembers}",$hideUnkownMembers,ico_params);
    $tpl->table_form_field_text("{debug}","{level} $StatsComDebugLelvel",ico_bug);
    echo $tpl->table_form_compile();
    return true;
}

function main_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $hideMacs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("statscomHideMacs"));
    $hideUnkownMembers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("statscomHideUnkownMembers"));
    $StatsComDebugLelvel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComDebugLelvel"));
    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));

    if($InfluxAdminRetentionTime==0){
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            $InfluxAdminRetentionTime=5;
        }else{
            $InfluxAdminRetentionTime=183;
        }
    }
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $InfluxAdminRetentionTime=5;
    }

    for($i=0;$i<5;$i++){
        $DEBUG_LEVEL[$i]=$i;
    }

    $js[]="LoadAjax('statscom-params','$page?main-flat=yes');";
    $js[]="dialogInstance2.close();";

    $form[]=$tpl->field_numeric("InfluxAdminRetentionTime","{StatisticsRetentionPeriod} ({days})",$InfluxAdminRetentionTime);
    $form[]=$tpl->field_array_hash($DEBUG_LEVEL,"StatsComDebugLelvel","{debug} ({level})",$StatsComDebugLelvel);
    $form[]=$tpl->field_checkbox("statscomHideMacs","{statscomHideMacs}",$hideMacs,false,null);
    $form[]=$tpl->field_checkbox("statscomHideUnkownMembers","{statscomHideUnkownMembers}",$hideUnkownMembers,false,null);
    $tpl->FORM_IN_ARRAY=false;
    $jsrestart=@implode(";",$js);
    $myform=$tpl->form_outside("{settings}", @implode("\n", $form),null,"{apply}",$jsrestart);


    echo $tpl->_ENGINE_parse_body($myform);
    return true;

}

function save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Save Proxy statistics parameters");
}
