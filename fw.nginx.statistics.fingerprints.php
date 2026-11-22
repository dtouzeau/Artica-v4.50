<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.rtmm.tools.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["json-data"])){json_data_js();exit;}
if(isset($_GET["json-data-popup"])){json_data_popup();exit;}
if(isset($_GET["status"])){status_js();exit;}
if(isset($_GET["activate"])){activate();exit;}
if(isset($_GET["popup-search"])){search_header();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["popup-js"])){popup_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["status-popup"])){status_popup();exit;}
if(isset($_POST["fingerprint"])){status_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["ArticaWebFingerPrint"])){activate_save();exit;}
if(isset($_GET["zoom-ips"])){zoom_ips_js();exit;}
if(isset($_GET["zoom-ips-popup"])){zoom_ips_popup();exit;}

page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{fingerprinting}","fas fa-fingerprint","{fingerprinting_explain}",
        "$page?tabs=yes","fingerprinting","progress-fingerprinting-restart",false,"table-fingerprinting");

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{fingerprints}"]="$page?popup-search=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function search_header(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&real-search=yes");
    echo "</div>";
}


function status_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fingerprint=$_GET["status"];
    $function=$_GET["function"];
    $tpl->js_dialog1("{fingerprint} $fingerprint","$page?status-popup=$fingerprint&function=$function");
}
function status_save():bool{
    $tpl=new template_admin();
    $fingerprint=$_POST["fingerprint"];
    $ztype=intval($_POST["ztype"]);

    if($ztype==5){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"fingerprints","where"=>["fingerprint"=>$fingerprint]]);
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"fingerprints_events","where"=>["fingerprint"=>$fingerprint]]);
        return admin_tracks("Remove fingerprint Web console access $fingerprint");

    }


    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"update","table"=>"fingerprints","set"=>["status"=>$ztype],"where"=>["fingerprint"=>$fingerprint]]);
    return admin_tracks("Save fingerprint Web console access $fingerprint status to $ztype");
}
function status_popup(){
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $fingerprint=$_GET["status-popup"];
    // Pentest 2026-11-04 : fingerprint (GET) etait concatene dans la requete.
    $ligne=$q->mysqli_fetch_array("SELECT * FROM fingerprints WHERE fingerprint='".pg_escape_string2($fingerprint)."'");
    $Types[0] = "{artica_captcha_greyzone}";
    $Types[1] = "{artica_captcha_allow}";
    $Types[2] = "{artica_captcha_deny}";
    $Types[5] = "{artica_captcha_delete}";
    $form[]=$tpl->field_hidden("fingerprint",$fingerprint);
    $form[]=$tpl->field_array_checkboxes2Columns($Types, "ztype", $ligne["status"]);
    echo $tpl->form_outside("",$form,"","{apply}","$function();dialogInstance1.close();");

}

function popup_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fingerprint=$_GET["popup-js"];
    return $tpl->js_dialog1("{fingerprint} $fingerprint","$page?popup=$fingerprint");
}

function zoom_ips_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fingerprint=$_GET["zoom-ips"];
    return $tpl->js_dialog2("{fingerprint} > $fingerprint > {ipaddresses}","$page?zoom-ips-popup=$fingerprint");
}
function json_data_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fingerprint=$_GET["json-data"];
    return $tpl->js_dialog2("{fingerprint} $fingerprint","$page?json-data-popup=$fingerprint");
}
function json_data_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $fingerprint=$_GET["json-data-popup"];
    // Pentest 2026-11-04 : fingerprint (GET) etait concatene dans la requete.
    $ligne=$q->mysqli_fetch_array("SELECT jsondata FROM fingerprints WHERE fingerprint='".pg_escape_string2($fingerprint)."'");
    $jsondata=base64_decode($ligne["jsondata"]);
    // Pentest 2026-11-04 : empreinte JSON envoyee par le navigateur (donnee externe) affichee sans encodage (XSS stockee).
    echo "<textarea style='width:100%;min-height:450px'>".req_html($jsondata)."</textarea>";
    return true;

}
function GetPlateForm($jsondata){
    $jsondata=json_decode($jsondata);
    if(!is_object($jsondata)){
        return "NO_COMP";
    }

    if(!property_exists($jsondata,"components")){
        return "NO_COMP";
    }
    $components=$jsondata->components;
    if(!property_exists($components,"platform")){
        return "NO_PLAT";

    }
    return $components->platform->value;
}

function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $fingerprint=$_GET["popup"];
    // Pentest 2026-11-04 : fingerprint (GET) etait concatene dans la requete.
    $ligne=$q->mysqli_fetch_array("SELECT * FROM fingerprints WHERE fingerprint='".pg_escape_string2($fingerprint)."'");
    $UserAgent=$ligne["useragent"];
    $jsondata=json_decode(base64_decode($ligne["jsondata"]));
    //var_dump($jsondata);
    $FontArray=array();

    if(strlen($UserAgent)<3){
        if(property_exists($jsondata,"userAgent")) {
            $UserAgent = $jsondata->userAgent;
        }
    }

    $LanguageArray=array();
    $screenResolution=array();
    $zips=array();
    $plugins=array();
    $timezone="";$platform="";$vendor="";$videoCard="";
    if(property_exists($jsondata,"components")){
        $components=$jsondata->components;
        if(property_exists($components,"fonts")){
            $fonts=$components->fonts->value;
            foreach ($fonts as $font){
                $FontArray[]=$font;
            }
        }
        if(property_exists($components,"languages")){
            $languages=$components->languages->value;

            foreach ($languages as $lang){
                foreach ($lang as $lang2) {
                    $LanguageArray[] = $lang2;
                }
            }
        }
        if(property_exists($components,"screenResolution")){
            $screen=$components->screenResolution->value;
            foreach ($screen as $src){
                $screenResolution[]=$src;
            }
        }
        if(property_exists($components,"plugins")){
            $plugs=$components->plugins->value;
            foreach ($plugs as $src){
                $plugins[]=$src->name;
            }
        }


        if(property_exists($components,"videoCard")){
            $videoCard=$components->videoCard->value->vendor;
            if(property_exists($components->videoCard->value,"renderer")){
                $videoCard=$components->videoCard->value->renderer;
            }
        }

        if(property_exists($components,"vendor")){
            $vendor=$components->vendor->value;
        }
        if(property_exists($components,"vendorFlavors")) {
            foreach ($components->vendorFlavors->value as $val){
                $vendor = $vendor . " " . $val;
            }
        }


        if(property_exists($components,"timezone")){
            $timezone=$components->timezone->value;
        }
        if(property_exists($components,"platform")){
            $platform=$components->platform->value;
        }
    }

    // Pentest 2026-11-04 : fingerprint (GET) etait concatene dans la requete.
    $results=$q->QUERY_SQL("SELECT ipaddr FROM fingerprints_events WHERE fingerprint='".pg_escape_string2($fingerprint)."'");
    $zipZoom=false;
    $zips21=array();
    if($q->ok){
        while($zipligne=@pg_fetch_array($results)){
            $zips21[$zipligne["ipaddr"]]=true;

        }
        if(count($zips21)>5){
            $zipZoom=true;
        }
        $c=0;
        foreach ($zips21 as $ip=>$none){
            $zips[]=$ip;
            if($zipZoom){
                if(count($zips)>3){
                    break;
                }
            }
        }
    }

    // Pentest 2026-11-04 : composants de l'empreinte (donnee externe : User-Agent, polices, plugins...) affiches sans encodage (XSS stockee).
    $zips=array_map('req_html',$zips);
    $plugins=array_map('req_html',$plugins);
    $FontArray=array_map('req_html',$FontArray);
    $LanguageArray=array_map('req_html',$LanguageArray);
    $screenResolution=array_map('req_html',$screenResolution);
    if(count($zips)>0){
        if(!$zipZoom) {
            $tpl->table_form_field_text("{ipaddresses}", "" . implode(", ", $zips) . "", ico_nic);
        }else{
            $tpl->table_form_field_js("Loadjs('$page?zoom-ips=$fingerprint')");
            $tpl->table_form_field_text("{ipaddresses}", "<small>" . implode(", ", $zips) . " <strong class='text-danger'>(".(count($zips21)-3)." {ipaddresses})</small>", ico_nic);
            $tpl->table_form_field_js("");
        }
    }

    if(strlen($vendor)>0){
           $tpl->table_form_field_text("{browser}",req_html($vendor),"fad fa-browser");
    }
    if(strlen($UserAgent)>0){
        $tpl->table_form_field_text("{useragent}","<small>".req_html($UserAgent)."</small>","fad fa-browser");
    }


    if(strlen($timezone)>0){
        $tpl->table_form_field_text("{timezone}",req_html($timezone),ico_clock_desk);
    }
    if(strlen($platform)>0){
        $tpl->table_form_field_text("{OS}",req_html($platform),ico_params);
    }
    if(strlen($videoCard)>0){
        $tpl->table_form_field_text("{video}","<small>".req_html($videoCard)."</small>","fad fa-camcorder");
    }


    if(count($plugins)>0){
        $tpl->table_form_field_text("{plugins}","<small>".@implode(", ",$plugins)."</small>",ico_plug);
    }
    if(count($FontArray)>0){
        $tpl->table_form_field_text("{font_face}","<small>".@implode(", ",$FontArray)."</small>",ico_fonts);
    }
    if(count($LanguageArray)>0){
        $tpl->table_form_field_text("{language}","<small>".@implode(", ",$LanguageArray)."</small>",ico_language);
    }
    if(count($screenResolution)>0){
        $tpl->table_form_field_text("{screen}","<small>".@implode("x",$screenResolution)."</small>",ico_computer);
    }

    $tpl->table_form_button("{data}","Loadjs('$page?json-data=$fingerprint');",null,"fas fa-fingerprint");

    echo $tpl->table_form_compile();
}
function hashServices():array{
    $zids=array();
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_services?order=servicename");
    $data=json_decode($resp,true);
    $rows=(is_array($data) && !empty($data["Status"]) && isset($data["rows"]) && is_array($data["rows"])) ? $data["rows"] : [];
    foreach ($rows as $ligne){
        if(intval(isset($ligne["enabled"])?$ligne["enabled"]:0)!==1){continue;}
        $zids[$ligne["ID"]]=$ligne["servicename"];
    }
    return $zids;
}
function table(){
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $t=time();
    // Deux colonnes portaient width:99% -- impossible pour deux d'entre elles a
    // la fois; awc laisse les largeurs naturelles.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th class='center'>{date}</th>";
    $html[]="<th>{fingerprint}</th>";
    $html[]="<th>{OS}</th>";
    $html[]="<th>{website}</th>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th class='center'>{type}</th>";
    $html[]="</tr></thead><tbody id='fp-rows'>";




    // INJECTION SQL: la saisie n'etait transformee que par un str_replace de
    // "*" en "%" puis concatenee dans un LIKE '...'. Une apostrophe suffisait a
    // sortir de la chaine PostgreSQL. On neutralise l'apostrophe, seul
    // caractere de fin de chaine, en la doublant.
    $search=(string)($_GET["search"] ?? "");
    $sql="SELECT * FROM fingerprints_events ORDER BY unixtime DESC LIMIT 250";
    if(strlen($search)>0){
        $like=str_replace("*","%","*".$search."*");
        $like=str_replace("%%","%",$like);
        $like=str_replace("'","''",$like);
        $sql="SELECT * FROM fingerprints_events WHERE (
            ipaddr::TEXT LIKE '$like' OR webtype LIKE '$like')  ORDER BY unixtime DESC LIMIT 250";
    }
    VERBOSE($sql,__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($q->mysql_error);
        return false;
    }

    $clock="<i class='".ico_clock."'></i>&nbsp;";

    $IconOS["Linux x86_64"]="<i class='fab fa-linux'></i>";
    $IconOS["MacIntel"]="<i class='fas fa-apple-alt'></i>";
    $IconOS["Win32"]="<i class='fab fa-windows'></i>";
    $IconOS["Linux armv81"]="<i class='fab fa-linux'></i>";
    $IconOS["iPad"]="<i class='fas fa-apple-alt'></i>";
    $IconOS["iPhone"]="<i class='fas fa-apple-alt'></i>";
    $hashServices=hashServices();
    while($ligne=@pg_fetch_array($results)){
        $TRCLASS=null;
        $md=md5(serialize($ligne));
        $fingerprint=$ligne["fingerprint"];
        $ipaddr=$ligne["ipaddr"];
        $webtype=$ligne["webtype"];
        $unixtime=$tpl->time_to_date($ligne["unixtime"],true);
        $serviceid=intval($ligne["siteid"]);
        $sitename=$tpl->icon_nothing();
        if($webtype<>"WEBCONSOLE") {
            if(isset($hashServices[$serviceid])) {
                $sitename = $hashServices[$serviceid];
            }
        }

        // L'empreinte vient de la base et partait BRUTE dans une seconde requete
        // SQL ainsi que dans l'URL d'un Loadjs.
        $fpSql=str_replace("'","''",(string)$fingerprint);
        $ligne2=$q->mysqli_fetch_array("SELECT jsondata,useragent,status FROM fingerprints WHERE fingerprint='$fpSql'");
        if(!is_array($ligne2)){$ligne2=array();}

        $country="";
        $flag=GetFlags($ligne["country"]);
        if(strlen((string)$ligne["country"])>0){
            $country="&nbsp;(".htmlspecialchars((string)$ligne["country"]).")";
        }

        // $jsondata etait lu par GetPlateForm() AVANT d'etre affecte, une ligne
        // plus bas: la premiere ligne du tableau interrogeait donc null, et
        // chacune des suivantes les donnees de la ligne PRECEDENTE.
        $jsondata=base64_decode((string)($ligne2["jsondata"] ?? ""));
        $OS=GetPlateForm($jsondata);
        $system=($IconOS[$OS] ?? "<i class='fas fa-question-circle'></i>")
               ."&nbsp;(".htmlspecialchars((string)$OS).")";

        $fpLink=$tpl->td_href(htmlspecialchars((string)$fingerprint),"",
            "Loadjs('$page?popup-js=".urlencode((string)$fingerprint)."&function=$function')");
        $ipLink=$tpl->td_href(htmlspecialchars((string)$ipaddr),null,
            "Loadjs('fw.modsecurity.threats.php?zoom-ip-js=".urlencode((string)$ipaddr)."')");
        $ico="<i class='".UserAgentToIcon((string)($ligne2["useragent"] ?? ""))."'></i>&nbsp;";

        $html[]="<tr id='$md'>"
            ."<td class='center awc-nowrap awc-mono'>$clock$unixtime</td>"
            ."<td class='awc-nowrap awc-mono'>$ico$fpLink</td>"
            ."<td class='awc-nowrap'>$system</td>"
            ."<td class='awc-nowrap'>$sitename</td>"
            ."<td class='awc-nowrap'><img src='img/".htmlspecialchars((string)$flag)."'>&nbsp;$ipLink$country</td>"
            ."<td class='center awc-nowrap'>".htmlspecialchars((string)$webtype)."</td>"
            ."</tr>";

    }

    // L'ancien pied -- une <ul class='pagination'> dans un <tfoot> -- etait rempli
    // par footable; le pager awc vit en dehors du tableau.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='fp-pager'></span></div>";
    $html[]="</div></div>";


    $ArticaWebFingerPrint=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"));

    if($ArticaWebFingerPrint==0) {
        $topbuttons[] = array("Loadjs('$page?activate=yes&function=$function')", ico_plus, "{activate_fingerprint_verification}");
    }else{
        $topbuttons[] = array("Loadjs('$page?activate=yes&function=$function')", ico_trash, "{disable_fingerprint_verification}");
    }


    $TINY_ARRAY["TITLE"]="{fingerprinting}";
    $TINY_ARRAY["ICO"]="fas fa-fingerprint";
    $TINY_ARRAY["EXPL"]="{fingerprinting_explain}";
    //$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n$jstiny\n</script>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(100,"fp-rows","fp-pager");
    return true;
}
function zoom_ips_popup():bool{
    $fingerprint=$_GET["zoom-ips-popup"];
    $t=time();
    $q=new postgres_sql();
    $hashServices=hashServices();
    $tpl=new template_admin();
    // Pentest 2026-11-04 : fingerprint (GET) etait concatene dans la requete.
    $results=$q->QUERY_SQL("SELECT count(*) as tcount,ipaddr,siteid,country FROM fingerprints_events WHERE siteid > 0 AND fingerprint='".pg_escape_string2($fingerprint)."' GROUP BY ipaddr,siteid,country ORDER BY tcount DESC");

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>{website}</th>";
    $html[]="<th class='center'>{requests}</th>";
    $html[]="</tr></thead><tbody id='fpip-rows'>";

    while($ligne=@pg_fetch_array($results)) {
        $TRCLASS=null;
        $md = md5(serialize($ligne));

        $ipaddr = $ligne["ipaddr"];
        $country="";
        $flag=GetFlags($ligne["country"]);
        if(strlen((string)$ligne["country"])>0){
            $country="&nbsp;(".htmlspecialchars((string)$ligne["country"]).")";
        }


        $serviceid = intval($ligne["siteid"]);
        $sitename = $tpl->icon_nothing();
        $requests=$ligne["tcount"];

            if (isset($hashServices[$serviceid])) {
               $sitename = $hashServices[$serviceid];
            }

        // La derniere cellule portait class='left' dans un tableau dont les
        // trois colonnes etaient deja alignees a gauche.
        $html[]="<tr id='$md'>"
            ."<td class='awc-nowrap'><img src='img/".htmlspecialchars((string)$flag)."'>&nbsp;"
            .htmlspecialchars((string)$ipaddr).$country."</td>"
            ."<td class='awc-nowrap'>$sitename</td>"
            ."<td class='center awc-nowrap awc-mono'>".htmlspecialchars((string)$requests)."</td>"
            ."</tr>";


    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='fpip-pager'></span></div>";
    $html[]="</div></div>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
        .awc_pager_js(100,"fpip-rows","fpip-pager");


return true;
}