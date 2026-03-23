<?php
// Network Agents Management Page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["popup"])){popup();exit;}
js();
function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $AgentObj=new ArticaNetAgents($id);
    $hostname=$AgentObj->GetAgentHostname();
    if(strlen($hostname)<1){$hostname="#$id";}
    return $tpl->js_dialog12("<i class='".ico_certificate."'></i> $hostname — {license}","$page?popup=yes&id=$id",1050);
}

function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $AgentObj=new ArticaNetAgents($id);
    $Crustatus=$AgentObj->Status();

    $license=$Crustatus["artica_license"];
    $LicenseInfos=$license["Info"];
    $FINAL_TIME             = intval($LicenseInfos["FINAL_TIME"]);
    $DateCreated=intval($LicenseInfos["date_created"]);


    list($ExpiresSoon_label,$expiredate)=expire_text($FINAL_TIME);

    if($license["gold_license"]){
        $LicenseInfos["license_status"]="<strong class='text-info'>Gold Edition</strong>";
        $expiredate="{never}";
        $LicenseInfos["ABOUT_PP"]="Entreprise / Gold Edition";
        $ExpiresSoon_label="";
    }

    $tpl->table_form_section("{artica_license}$ExpiresSoon_label",$LicenseInfos["license_status"]);
    if(strlen($LicenseInfos["license_number"])>0) {
        $tpl->table_form_field_info("{license_number}", $LicenseInfos["license_number"], ico_certificate);
    }

    if(!$license["gold_license"]) {
        if (is_numeric($LicenseInfos["TIME"])) {
            $tt = distanceOfTimeInWords($LicenseInfos["TIME"], time());
            $tpl->table_form_field_info("{last_update}", "{since} $tt", ico_clock);
        }
    }

    if($DateCreated>0){
        $tpl->table_form_field_info("{install_date}",$tpl->time_to_date($DateCreated),ico_clock);
    }
    $tpl->table_form_field_info("{expiredate}", $expiredate, ico_timeout);
    if(strlen($LicenseInfos["COMPANY"])>0) {
        $tpl->table_form_field_info("{company}", $LicenseInfos["COMPANY"], ico_city);
    }
    if(strlen($LicenseInfos["REQUEST_BY"])>0) {
        $tpl->table_form_field_info("{requested_by}", $LicenseInfos["REQUEST_BY"], ico_admin);
    }
    $tpl->table_form_field_info("{type}", $LicenseInfos["ABOUT_PP"], ico_diplome);
    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    return true;
}
function expire_text($FINAL_TIME=0):array{
    $tpl=new template_admin();
    $ExpiresSoon_label      = "<div style='float: right'>&nbsp;&nbsp;<span class='label label-info'>{use_community_edition}</span>&nbsp;&nbsp;</div>";
    $ExpiresSoon_eval        = "<div style='float: right'>&nbsp;&nbsp;<span class='label label-warning'>{evaluation_mode}</span>&nbsp;&nbsp;</div>";
    $ExpiresSoon_expired    = "<div style='float: right'>&nbsp;&nbsp;<span class='label label-danger'>{expired}</span>&nbsp;&nbsp;</div>";
    $ExpiresSoon_text="";
    $ExpiresSoon_ok= "<div style='float: right'>&nbsp;&nbsp;<span class='label label-primary'>Enterprise Edition</span>&nbsp;&nbsp;</div>";


    if($GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()){
        return array($ExpiresSoon_ok,"{unlimited}");
    }


    if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $ExpiresSoon_label=$ExpiresSoon_ok;
        $LicenseInfos["license_status"]="{license_active}";
    }


    if ($FINAL_TIME>0) {
        $ExpiresSoon_label=$ExpiresSoon_ok;
        $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));

        $distanceOfTimeInWords="(".distanceOfTimeInWords(time(), $FINAL_TIME).")";
        if ($ExpiresSoon<7) {
            $ExpiresSoon_text="<span class=text-danger>&nbsp;{ExpiresSoon}</span>";
            $ExpiresSoon_label=$ExpiresSoon_eval;
        }

        if ($ExpiresSoon<31) {
            VERBOSE("Evaluation Mode",__LINE__);
            $ExpiresSoon_label=$ExpiresSoon_eval;
        }

        if ($FINAL_TIME<time()) {
            $head_error="{license_expired_explain}";
            $ExpiresSoon_text="<span class=text-danger><strong>&laquo;{expired}&raquo;</strong></span>";
            $distanceOfTimeInWords=null;
            $ExpiresSoon_label=$ExpiresSoon_expired;
        }

        return array($ExpiresSoon_label,$tpl->time_to_date($FINAL_TIME)." $distanceOfTimeInWords$ExpiresSoon_text");



    }

    return array($ExpiresSoon_label,"{never}");


}