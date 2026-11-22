<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Refonte stitch 2026-11-18 (stitch_geoip_database_admin_redesign) : une page unique
// alimentee par gip_model() ; les anciens widgets needlogin-status / widget_date /
// widget_db (rafraichis toutes les 5 s) sont remplaces par l'hote #gip-main.
if(isset($_GET["upload-db-js"])){upload_db_js();exit;}
if(isset($_GET["upload-db-popup"])){upload_db_popup();exit;}
if(isset($_GET["uploadb"])){uploaded_db();exit;}
if(isset($_GET["download-db"])){download_db();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_POST["GeoIPRsync"])){Save();exit;}
if(isset($_GET["rsync-js"])){rsync_js();exit;}
if(isset($_GET["rsync-popup"])){rsync_popup();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_POST["GeoIPAccount"])){Save();exit;}
if(isset($_GET["tabs"])){gip_build();exit;}
if(isset($_GET["build-page"])){gip_build();exit;}
if(isset($_GET["main-host"])){gip_main();exit;}
if(isset($_GET["status"])){gip_main();exit;}
if(isset($_GET["lookup"])){gip_lookup();exit;}
if(isset($_GET["client-js"])){client_js();exit;}
if(isset($_GET["client-popup"])){client_popup();exit;}
if(isset($_GET["search"])){client_search();exit;}
if(isset($_GET["enable-feature"])){enable_feature();exit;}
if(isset($_GET["disable-js"])){gip_disable_js();exit;}
if(isset($_POST["gip-disable"])){gip_disable_perform();exit;}

page();

// Toute ecriture exige AsSystemAdministrator cote serveur : les boutons n'etaient
// que masques, n'importe quelle session pouvait activer, desactiver ou changer la source.
function gip_is_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}

function parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!gip_is_admin()){return $tpl->js_no_privileges();}
    return $tpl->js_dialog2("{gip_src_maxmind}","$page?parameters-popup=yes");
}
function upload_db_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!gip_is_admin()){return $tpl->js_no_privileges();}
    return $tpl->js_dialog2("{load_databases_manually}","$page?upload-db-popup=yes");
}
function upload_db_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!gip_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return false;}
    $bt_upload=$tpl->button_upload("{gip_upload_btn}",$page,null,"&uploadb=yes")."&nbsp;&nbsp;";
    $html="<p style='font-size:14px;margin:0 0 14px'>{gip_upload_help}</p><div class='center'>$bt_upload</div>
	<div id='progress-certificates-center-import'></div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function uploaded_db(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!gip_is_admin()){echo $tpl->js_no_privileges();return false;}
    $file=urlencode($_GET["file-uploaded"]);

    $sock=new sockets();
    $json=json_decode($sock->REST_API("/geoip/import/$file"));

    if(!is_object($json) || !$json->Status){
        echo $tpl->js_error(is_object($json)?$json->Error:"{protocol_error}");
        return false;

    }

    echo "if(typeof GipReload==='function'){GipReload(true);}dialogInstance2.close();";
    return admin_tracks("GeoIP database $file uploaded");
}
function rsync_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!gip_is_admin()){return $tpl->js_no_privileges();}
    return $tpl->js_dialog2("{gip_src_rsync}","$page?rsync-popup=yes");
}
function Save(){
    $tpl=new template_admin();
    if(!gip_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/system/geopip/update");
    return true;
}
function client_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog12("{gip_lookup_title}","$page?client-popup=yes");
}
function client_search():bool{
    $tpl=new template_admin();
    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    $ipaddr=$_GET["search"];
    if(strlen($ipaddr)<4){
        return false;
    }
    echo gip_style().$tpl->_ENGINE_parse_body("<div class='gip'>".gip_lookup_result($ipaddr)."</div>");
    return true;
}
function download_db():bool{
    if(!gip_is_admin()){header("HTTP/1.1 403 Forbidden");return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/geoip/export");
    $filepath="/usr/share/artica-postfix/ressources/logs/GeoIP.tar.gz";
    header('Content-type: application/x-tar');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"GeoIP.tar.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize($filepath);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($filepath);
    return true;

}
function enable_feature():bool{
    $tpl=new template_admin();
    if(!gip_is_admin()){return $tpl->js_no_privileges();}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/geoip/install"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    header("content-type: application/x-javascript");
    echo "if(typeof GipReload==='function'){GipReload(true);}";
    return admin_tracks("Install and enable the GeoIP database");
}

// Desactiver coupe toutes les regles par pays : confirmation obligatoire.
function gip_disable_js():bool{
    $tpl=new template_admin();
    if(!gip_is_admin()){return $tpl->js_no_privileges();}
    $tpl->js_dialog_confirm_action("{gip_off_confirm}","gip-disable","none","if(typeof GipReload==='function'){GipReload(true);}");
    return true;
}
function gip_disable_perform():bool{
    $tpl=new template_admin();
    if(!gip_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/geoip/uninstall"),true);
    if(!isset($json["Status"])){echo $tpl->_ENGINE_parse_body("{protocol_error}");return false;}
    if(!$json["Status"]){echo (string)($json["Error"] ?? "");return false;}
    admin_tracks("Uninstall the GeoIP database");
    return true;
}

function client_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
}
function page():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $html=$tpl->page_header("{gip_title}",ico_database,
        "{gip_subtitle}","$page?build-page=yes","geoip-database",
        "progress-redis-restart",false,"table-loader-geoip-service");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{gip_title}",$html);
		echo $tpl->build_firewall();
		return true;
	}
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function rsync_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!gip_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return false;}
    $GeoIPRsync=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsync"));
    $GeoIPRsyncServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncServer"));
    $GeoIPRsyncServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncServerPort"));
    $GeoIPRsyncInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncInterface"));
    if($GeoIPRsyncServerPort==0){
        $GeoIPRsyncServerPort=873;
    }
    $form[]= $tpl->field_checkbox("GeoIPRsync","{gip_rsync_use}",$GeoIPRsync,true,"{gip_rsync_use_help}");
    $form[]= $tpl->field_text("GeoIPRsyncServer","{remote_server_address}",$GeoIPRsyncServer,false,"{gip_rsync_server_help}");
    $form[]= $tpl->field_numeric("GeoIPRsyncServerPort","{remote_port}",$GeoIPRsyncServerPort,"{gip_rsync_port_help}");
    $form[]= $tpl->field_interfaces("GeoIPRsyncInterface","{outgoing_interface}",$GeoIPRsyncInterface);
    $html[]= $tpl->form_outside("",$form,"{gip_rsync_explain}","{gip_apply}", "if(typeof GipReload==='function'){GipReload(true);}dialogInstance2.close();", "AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function parameters_popup():bool{
    $tpl=new template_admin();
    if(!gip_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return false;}
    $GeoIPAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPAccount"));
    $GeoIPKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPKey"));
    $form[]= $tpl->field_text("GeoIPAccount","{gip_account_id}",$GeoIPAccount,false,"{gip_account_id_help}");
    $form[]= $tpl->field_text("GeoIPKey","{gip_license_key}",$GeoIPKey,false,"{gip_license_key_help}");
    $html[]= $tpl->form_outside("",$form,"{gip_maxmind_explain}","{gip_apply}", "if(typeof GipReload==='function'){GipReload(true);}dialogInstance2.close();", "AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ---------------------------------------------------------------------------
// Page (maquette stitch) : un modele unique, un widget = une fonction.
// ---------------------------------------------------------------------------

function gip_build():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=gip_model();

    $topbuttons=array();
    $topbuttons[]=array("GipFocusLookup();","fa-solid fa-magnifying-glass","{gip_lookup_title}");
    if($m["admin"]){
        $topbuttons[]=array("Loadjs('$page?upload-db-js=yes');",ico_upload,"{load_databases_manually}");
    }
    $topbuttons[]=array("GipReload(true);",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{gip_title}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{gip_subtitle}";
    $TINY_ARRAY["URL"]="geoip-database";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html="<div class='gip'><div id='gip-progress'></div><div id='gip-main'>".gip_main_html($m)."</div></div>";
    echo gip_style().$tpl->_ENGINE_parse_body($html)."<script>NoSpinner();\n".gip_js($page)."\n$jstiny\nGipPoll();</script>";
    return true;
}

function gip_main():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(gip_main_html(gip_model()))."<script>if(typeof GipPoll==='function'){GipPoll();}</script>";
    return true;
}

function gip_t(string $token):string{
    $tpl=new template_admin();
    return (string)$tpl->_ENGINE_parse_body($token);
}

function gip_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $m["admin"]=gip_is_admin();
    $m["enabled"]=intval($s->GET_INFO("EnableGeoipUpdate"))==1;
    $m["corp"]=(bool)$s->CORP_LICENSE();
    $m["rsync"]=intval($s->GET_INFO("GeoIPRsync"))==1;
    $m["rsync_server"]=trim((string)$s->GET_INFO("GeoIPRsyncServer"));
    $m["rsync_port"]=intval($s->GET_INFO("GeoIPRsyncServerPort"));
    if($m["rsync_port"]==0){$m["rsync_port"]=873;}
    $m["rsync_iface"]=trim((string)$s->GET_INFO("GeoIPRsyncInterface"));
    $m["account"]=trim((string)$s->GET_INFO("GeoIPAccount"));
    $m["key"]=trim((string)$s->GET_INFO("GeoIPKey"));
    // Meme ordre de priorite que AGeoIP.UpdateGoIP() : rsync, puis miroir Artica, puis MaxMind.
    $m["source"]=$m["rsync"]?"rsync":($m["corp"]?"corp":"maxmind");
    $m["last"]=intval($s->GET_INFO("GEOIP_LAST_UPDATED"));
    $m["error"]=gip_redact(trim((string)$s->GET_INFO("GeoIPUpdateError")));
    if(strlen($m["error"])<3){$m["error"]="";}
    $m["error_time"]=intval($s->GET_INFO("GeoIPUpdateErrorTime"));
    $m["rsync_error"]="";
    if($m["enabled"] && $m["rsync"]){
        $m["rsync_error"]=trim((string)$s->GET_INFO("GEO_RSYNC"));
        if(strlen($m["rsync_error"])<2 && $m["rsync_server"]<>""){
            $iface=strlen($m["rsync_iface"])>2?$m["rsync_iface"]:"NONE";
            $t=json_decode((string)$s->REST_API("/rsyncd/testclient/".rawurlencode($m["rsync_server"])."/{$m["rsync_port"]}/".rawurlencode($iface)));
            if(is_object($t) && empty($t->Status)){$m["rsync_error"]=isset($t->Error)?(string)$t->Error:"";}
        }
    }

    $m["dbs_ok"]=false;
    $m["mmdb"]=array();
    $m["helpers"]=array("count"=>0,"size"=>0,"time"=>0);
    $m["total_size"]=0;
    $m["oldest"]=0;
    $json=json_decode((string)$s->REST_API("/geoip/databases"));
    if(is_object($json)){
        $m["dbs_ok"]=true;
        $list=(isset($json->Data) && is_array($json->Data))?$json->Data:array();
        foreach($list as $d){
            $fn=(string)($d->filename ?? "");
            if($fn=="" || preg_match("#COPYRIGHT|README|LICENSE#i",$fn)){continue;}
            $size=intval($d->size ?? 0);
            $time=intval($d->time ?? 0);
            $m["total_size"]+=$size;
            if(preg_match('#\.mmdb$#i',$fn)){
                $m["mmdb"][]=array("file"=>$fn,"size"=>$size,"time"=>$time);
                if($time>0 && ($m["oldest"]==0 || $time<$m["oldest"])){$m["oldest"]=$time;}
            }else{
                $m["helpers"]["count"]++;
                $m["helpers"]["size"]+=$size;
                if($time>$m["helpers"]["time"]){$m["helpers"]["time"]=$time;}
            }
        }
        usort($m["mmdb"],function($a,$b){ return gip_db_rank($a["file"])-gip_db_rank($b["file"]); });
    }
    $m["loaded"]=count($m["mmdb"]);
    $m["stale"]=($m["oldest"]>0 && (time()-$m["oldest"])>30*86400);
    $m["key_missing"]=($m["source"]=="maxmind" && $m["key"]=="");
    return $m;
}

// La cle MaxMind voyage dans l'URL de telechargement : une erreur HTTP la recopie.
function gip_redact(string $s):string{
    return preg_replace('#(license_key=)[^&\s"\']+#i','$1•••',$s);
}

function gip_db_rank(string $f):int{
    if(stripos($f,"Country")!==false){return 1;}
    if(stripos($f,"City")!==false){return 2;}
    if(stripos($f,"ASN")!==false){return 3;}
    return 9;
}

function gip_main_html(array $m):string{
    $h=array();
    $h[]=gip_hero($m);
    $h[]=gip_steps($m);
    $h[]=gip_alerts($m);
    $h[]=gip_strip($m);
    $h[]="<div class='gip-cols'>";
    $h[]="<div class='gip-left'>".gip_dbs_card($m)."</div>";
    $h[]="<div class='gip-side'>".gip_source_card($m).gip_lookup_card().gip_off_card($m)."</div>";
    $h[]="</div>";
    $poll=($m["enabled"] && $m["loaded"]==0)?"1":"0";
    $h[]="<span id='gip-poll' data-poll='$poll' hidden></span>";
    return @implode("\n",$h);
}

function gip_ago(int $ts):string{
    if($ts<=0){return "{gip_never}";}
    $d=time()-$ts;
    if($d<0){$d=0;}
    if($d<120){$v=gip_t("{gip_just_now}");}
    elseif($d<3600){$v=intval($d/60)." min";}
    elseif($d<172800){$v=intval($d/3600)." h";}
    else{$v=intval($d/86400)." ".gip_t("{gip_days}");}
    if($d<120){return $v;}
    return str_replace("%s",$v,gip_t("{gip_ago}"));
}

function gip_date_attr(int $ts):string{
    if($ts<=0){return "";}
    return htmlspecialchars(date("Y-m-d H:i",$ts),ENT_QUOTES);
}

function gip_size(int $bytes):string{
    return FormatBytes($bytes/1024,true);
}

function gip_chip(string $ico,string $text,string $cls=""):string{
    return "<span class='gip-chip $cls'><i class='$ico'></i><span>$text</span></span>";
}

function gip_hero(array $m):string{
    $chips=array();
    if($m["source"]=="rsync"){
        $chips[]=gip_chip("fa-solid fa-rotate","{gip_chip_rsync}");
    }else{
        $chips[]=gip_chip("fa-solid fa-rotate","{gip_chip_5h}");
    }
    $chips[]=gip_chip("fa-solid fa-cloud-arrow-down","{gip_chip_changed}");
    $chips[]=gip_chip("fa-solid fa-shield-halved","{gip_chip_local}");
    if($m["source"]=="corp"){
        $chips[]=gip_chip("fa-solid fa-certificate","{gip_chip_corp}","gip-chip-on");
    }elseif($m["source"]=="maxmind"){
        $chips[]=gip_chip("fa-solid fa-key","{gip_chip_maxmind}");
    }
    $h="<section class='gip-hero'>";
    $h.="<div class='gip-hero-ico'><i class='fa-solid fa-earth-europe'></i></div>";
    $h.="<div class='gip-hero-body'><h2>{gip_hero_title}</h2>";
    $h.="<p>{gip_hero_text}</p>";
    $h.="<p class='gip-example'><i class='fa-regular fa-lightbulb'></i> {gip_hero_example}</p>";
    $h.="<div class='gip-chips'>".@implode("",$chips)."</div>";
    $h.="</div></section>";
    return $h;
}

function gip_source_name(array $m):string{
    if($m["source"]=="rsync"){return "{gip_src_rsync}";}
    if($m["source"]=="corp"){return "{gip_src_corp}";}
    return "{gip_src_maxmind}";
}

// Chemin de mise en service : visible tant que la fonction est coupee ou sans base.
function gip_steps(array $m):string{
    $srcOk=!$m["key_missing"] && !($m["source"]=="rsync" && $m["rsync_server"]=="");
    if($m["enabled"] && $m["loaded"]>0 && $srcOk){return "";}
    $page=CurrentPageName();
    $h="<div class='gip-steps'>";
    $h.=gip_step(1,$m["enabled"],"{gip_step1_t}","{gip_step1}",
        $m["enabled"]?"":gip_btn("Loadjs('$page?enable-feature=yes');","fa-solid fa-power-off","{enable_feature}","gip-btn-main",$m["admin"]));
    $srcBtn=$m["source"]=="rsync"?"Loadjs('$page?rsync-js=yes');":"Loadjs('$page?parameters-js=yes');";
    $h.=gip_step(2,$srcOk,"{gip_step2_t}","{gip_active}: ".gip_source_name($m),
        ($srcOk || $m["source"]=="corp")?"":gip_btn($srcBtn,"fa-solid fa-sliders","{gip_configure}","gip-btn-ghost",$m["admin"]));
    $h.=gip_step(3,$m["loaded"]>0,"{gip_step3_t}",$m["loaded"]>0?$m["loaded"]." {gip_dbs_loaded_n}":"{gip_step3}",
        ($m["loaded"]>0 || !$m["enabled"])?"":gip_btn(gip_update_js(),"fa-solid fa-rotate","{gip_update_now}","gip-btn-ghost",$m["admin"]));
    $h.="</div>";
    return $h;
}

function gip_step(int $n,bool $done,string $title,string $text,string $btn):string{
    $badge=$done?"<span class='gip-step-n gip-step-done' aria-label='done'><i class='fa-solid fa-check'></i></span>":"<span class='gip-step-n'>$n</span>";
    return "<div class='gip-step".($done?" is-done":"")."'>$badge<div class='gip-step-body'>"
        ."<span class='gip-step-k'>{gip_step} $n</span><strong>$title</strong><span>$text</span>"
        .($btn<>""?"<div class='gip-step-act'>$btn</div>":"")."</div></div>";
}

function gip_btn(string $js,string $ico,string $label,string $cls,bool $allowed=true):string{
    if(!$allowed){
        $why=htmlspecialchars(gip_plain("{gip_admin_only}"),ENT_QUOTES);
        return "<button type='button' class='gip-btn $cls' disabled title=\"$why\"><i class='$ico'></i> $label</button>";
    }
    return "<button type='button' class='gip-btn $cls' OnClick=\"$js\"><i class='$ico'></i> $label</button>";
}

function gip_plain(string $s):string{
    $t=gip_t($s);
    $t=preg_replace('#<br\s*/?>#i'," ",$t);
    return trim(html_entity_decode(strip_tags($t),ENT_QUOTES,"UTF-8"));
}

function gip_update_js():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/system/geopip/update","GeoipUpdate.progress","GeoipUpdate.log",
        "gip-progress","GipReload(true);","GipReload(true);");
}

function gip_alerts(array $m):string{
    if(!$m["enabled"]){return "";}
    $a=array();
    if($m["rsync_error"]<>""){$a[]=gip_alert_rsync($m);}
    if($m["key_missing"]){$a[]=gip_alert_key($m);}
    if($m["error"]<>"" && !$m["key_missing"]){$a[]=gip_alert_failed($m);}
    if($m["stale"]){$a[]=gip_alert_stale($m);}
    if(!$m["dbs_ok"]){$a[]=gip_alert("err","fa-solid fa-plug-circle-xmark","{gip_al_daemon_t}","{gip_al_daemon}","","");}
    if(count($a)==0){return "";}
    return "<div class='gip-alerts'>".@implode("",$a)."</div>";
}

function gip_alert(string $kind,string $ico,string $title,string $text,string $raw,string $btn):string{
    $h="<div class='gip-alert gip-alert-$kind' role='alert'><span class='gip-alert-ico'><i class='$ico'></i></span>";
    $h.="<div class='gip-alert-body'><strong>$title</strong><span>$text</span>";
    if($raw<>""){$h.="<span class='gip-raw'>".htmlspecialchars($raw)."</span>";}
    $h.="</div>";
    if($btn<>""){$h.="<div class='gip-alert-act'>$btn</div>";}
    return $h."</div>";
}

function gip_alert_failed(array $m):string{
    $e=$m["error"];
    if(stripos($e,"429")!==false || stripos($e,"Too Many Requests")!==false){$hint="{gip_hint_429}";}
    elseif(stripos($e,"GeoIPKey is empty")!==false){$hint="{gip_hint_nokey}";}
    elseif(stripos($e,"401")!==false || stripos($e,"Unauthorized")!==false){$hint="{gip_hint_401}";}
    else{$hint="{gip_hint_generic}";}
    $title="{gip_al_failed_t}";
    if($m["error_time"]>0){$title.=" <span class='gip-when' title='".gip_date_attr($m["error_time"])."'>&middot; ".gip_ago($m["error_time"])."</span>";}
    return gip_alert("err","fa-solid fa-circle-xmark",$title,$hint,$e,
        gip_btn(gip_update_js(),"fa-solid fa-rotate","{gip_update_now}","gip-btn-err",$m["admin"]));
}

function gip_alert_key(array $m):string{
    $page=CurrentPageName();
    return gip_alert("err","fa-solid fa-key","{gip_al_key_t}","{gip_al_key}","",
        gip_btn("Loadjs('$page?parameters-js=yes');","fa-solid fa-key","{gip_enter_key}","gip-btn-err",$m["admin"]));
}

function gip_alert_rsync(array $m):string{
    $page=CurrentPageName();
    return gip_alert("err","fa-solid fa-server","{gip_al_rsync_t}","{gip_al_rsync}",$m["rsync_error"],
        gip_btn("Loadjs('$page?rsync-js=yes');","fa-solid fa-sliders","{gip_configure}","gip-btn-err",$m["admin"]));
}

function gip_alert_stale(array $m):string{
    return gip_alert("warn","fa-solid fa-hourglass-half","{gip_al_stale_t}","{gip_al_stale}","",
        gip_btn(gip_update_js(),"fa-solid fa-rotate","{gip_update_now}","gip-btn-warn",$m["admin"]));
}

function gip_strip(array $m):string{
    $h="<div class='gip-strip'>";
    $h.=gip_tile_state($m);
    $h.=gip_tile_loaded($m);
    $h.=gip_tile_last($m);
    $h.=gip_tile_source($m);
    return $h."</div>";
}

function gip_tile(string $label,string $value,string $sub,string $title=""):string{
    $t=$title<>""?" title='$title'":"";
    return "<div class='gip-tile'><span class='gip-tile-lbl'>$label</span><span class='gip-tile-val'$t>$value</span><span class='gip-tile-sub'>$sub</span></div>";
}

function gip_tile_state(array $m):string{
    $v=$m["enabled"]?"<span class='gip-dot gip-dot-ok'></span>{gip_on}":"<span class='gip-dot'></span>{gip_off}";
    return gip_tile("{gip_t_state}",$v,"{gip_t_state_sub}");
}

function gip_tile_loaded(array $m):string{
    $v=($m["dbs_ok"] && $m["enabled"])?(string)$m["loaded"]:"&mdash;";
    return gip_tile("{gip_t_loaded}",$v,$m["loaded"]>0?"{gip_t_loaded_sub}":"{gip_t_loaded_none}");
}

function gip_tile_last(array $m):string{
    $sub=$m["source"]=="rsync"?"{gip_chip_rsync}":"{gip_chip_5h}";
    return gip_tile("{gip_t_last}",gip_ago($m["last"]),$sub,gip_date_attr($m["last"]));
}

function gip_tile_source(array $m):string{
    $v=gip_source_name($m);
    if($m["source"]=="corp"){$v.=" <span class='gip-tag'>CORP</span>";}
    $sub="{gip_t_source_sub}";
    if($m["source"]=="rsync" && $m["rsync_server"]<>""){$sub=htmlspecialchars($m["rsync_server"].":".$m["rsync_port"]);}
    return gip_tile("{gip_t_source}","<span class='gip-src-val'>$v</span>",$sub);
}

function gip_db_info(string $file):array{
    if(stripos($file,"Country")!==false){return array("{gip_db_country}","{gip_db_country_help}");}
    if(stripos($file,"City")!==false){return array("{gip_db_city}","{gip_db_city_help}");}
    if(stripos($file,"ASN")!==false){return array("{gip_db_asn}","{gip_db_asn_help}");}
    return array(htmlspecialchars($file),"");
}

function gip_dbs_card(array $m):string{
    $page=CurrentPageName();
    $h="<div class='gip-card'>";
    $h.="<div class='gip-card-head'><div><h3>{gip_dbs_title}</h3><p>{gip_dbs_intro}</p></div>";
    if($m["total_size"]>0){$h.="<span class='gip-total'>{gip_total}: ".gip_size($m["total_size"])."</span>";}
    $h.="</div>";
    if($m["loaded"]==0){
        $h.=gip_dbs_empty($m);
    }else{
        $h.="<div class='gip-dbs'>";
        foreach($m["mmdb"] as $d){$h.=gip_db_row($d);}
        if($m["helpers"]["count"]>0){$h.=gip_db_helpers($m["helpers"]);}
        $h.="</div>";
    }
    $h.="<div class='gip-card-foot'>";
    // Fonction coupee : pas de bouton grise dont la raison affichee (droits) serait fausse.
    if($m["enabled"]){$h.=gip_btn(gip_update_js(),"fa-solid fa-rotate","{gip_update_now}","gip-btn-main",$m["admin"]);}
    if($m["loaded"]>0){
        $h.=gip_btn("document.location.href='/$page?download-db=yes';","fa-solid fa-file-zipper","{gip_download}","gip-btn-ghost",$m["admin"]);
    }
    $h.=gip_btn("Loadjs('$page?upload-db-js=yes');","fa-solid fa-upload","{load_databases_manually}","gip-btn-link",$m["admin"]);
    $h.="</div>";
    $h.="<p class='gip-foot-help'><i class='fa-solid fa-circle-info'></i> {gip_download_help}</p>";
    return $h."</div>";
}

function gip_db_row(array $d):string{
    list($name,$help)=gip_db_info($d["file"]);
    $old=($d["time"]>0 && (time()-$d["time"])>30*86400);
    $pill=$old?"<span class='gip-pill gip-pill-warn'><i class='fa-solid fa-hourglass-half'></i> {gip_old}</span>"
              :"<span class='gip-pill gip-pill-ok'><i class='fa-solid fa-circle-check'></i> {gip_fresh}</span>";
    $h="<div class='gip-db'><div class='gip-db-main'>";
    $h.="<div class='gip-db-name'><strong>$name</strong><span class='gip-file'>".htmlspecialchars($d["file"])."</span></div>";
    if($help<>""){$h.="<span class='gip-db-help'>$help</span>";}
    $h.="</div><div class='gip-db-side'><div><span class='gip-size'>".gip_size($d["size"])."</span> $pill</div>";
    $h.="<span class='gip-db-when' title='".gip_date_attr($d["time"])."'>{gip_updated} ".gip_ago($d["time"])."</span></div></div>";
    return $h;
}

function gip_db_helpers(array $x):string{
    $h="<div class='gip-db'><div class='gip-db-main'>";
    $h.="<div class='gip-db-name'><strong>{gip_db_helpers}</strong><span class='gip-file'>".intval($x["count"])." CSV</span></div>";
    $h.="<span class='gip-db-help'>{gip_db_helpers_help}</span>";
    $h.="</div><div class='gip-db-side'><div><span class='gip-size'>".gip_size($x["size"])."</span></div>";
    $h.="<span class='gip-db-when' title='".gip_date_attr($x["time"])."'>{gip_updated} ".gip_ago($x["time"])."</span></div></div>";
    return $h;
}

function gip_dbs_empty(array $m):string{
    $page=CurrentPageName();
    if(!$m["enabled"]){
        $t="{gip_empty_off_t}";$x="{gip_empty_off}";
        $b=gip_btn("Loadjs('$page?enable-feature=yes');","fa-solid fa-power-off","{enable_feature}","gip-btn-main",$m["admin"]);
    }else{
        $t="{gip_empty_t}";$x="{gip_empty}";
        $b="";
    }
    return "<div class='gip-empty'><i class='fa-solid fa-database'></i><h4>$t</h4><p>$x</p>".($b<>""?"<div>$b</div>":"")."</div>";
}

function gip_source_card(array $m):string{
    $page=CurrentPageName();
    $h="<div class='gip-card'>";
    $h.="<div class='gip-card-head'><div><h3>{gip_src_title}</h3><p>{gip_src_intro}</p></div></div>";
    // Artica
    $corpBody="{gip_src_corp_help}";
    if(!$m["corp"]){$corpBody.="<span class='gip-src-why'><i class='fa-solid fa-lock'></i> {gip_src_corp_need}</span>";}
    $h.=gip_src_opt($m["source"]=="corp","fa-solid fa-cloud","{gip_src_corp}",$corpBody,"",!$m["corp"]);
    // MaxMind
    $mm="{gip_src_maxmind_help}";
    if($m["account"]<>"" || $m["key"]<>""){
        $mm.="<span class='gip-src-meta'>{gip_account_id} ".($m["account"]<>""?htmlspecialchars($m["account"]):"&mdash;")
            ." &middot; {gip_license_key} ".gip_mask($m["key"])."</span>";
    }
    if($m["source"]=="corp"){$mm.="<span class='gip-src-meta'>{gip_src_maxmind_corp}</span>";}
    $h.=gip_src_opt($m["source"]=="maxmind","fa-solid fa-key","{gip_src_maxmind}",$mm,
        gip_btn("Loadjs('$page?parameters-js=yes');","fa-solid fa-sliders","{gip_configure}","gip-btn-small",$m["admin"]),false);
    // rsync
    $rs="{gip_src_rsync_help}";
    if($m["rsync_server"]<>""){
        $via=$m["rsync_iface"]<>""?" &middot; ".htmlspecialchars($m["rsync_iface"]):"";
        $rs.="<span class='gip-src-meta gip-mono'>".htmlspecialchars($m["rsync_server"].":".$m["rsync_port"])."$via</span>";
    }
    if($m["rsync"]){$rs.="<span class='gip-src-meta'>{gip_chip_rsync}</span>";}
    $h.=gip_src_opt($m["source"]=="rsync","fa-solid fa-server","{gip_src_rsync}",$rs,
        gip_btn("Loadjs('$page?rsync-js=yes');","fa-solid fa-sliders","{gip_configure}","gip-btn-small",$m["admin"]),false);
    $h.="<p class='gip-foot-help'><i class='fa-solid fa-circle-info'></i> {gip_src_order}</p>";
    return $h."</div>";
}

function gip_src_opt(bool $active,string $ico,string $title,string $body,string $btn,bool $locked):string{
    $cls="gip-src".($active?" is-active":"").($locked?" is-locked":"");
    $h="<div class='$cls'><span class='gip-src-ico'><i class='$ico'></i></span><div class='gip-src-body'>";
    $h.="<div class='gip-src-top'><strong>$title</strong>".($active?"<span class='gip-tag gip-tag-on'><i class='fa-solid fa-check'></i> {gip_active}</span>":"")."</div>";
    $h.="<span class='gip-src-txt'>$body</span>";
    if($btn<>""){$h.="<div class='gip-src-act'>$btn</div>";}
    return $h."</div></div>";
}

function gip_mask(string $k):string{
    if($k==""){return "&mdash;";}
    $tail=strlen($k)>4?substr($k,-4):"";
    return "<span class='gip-mono'>&bull;&bull;&bull;&bull;&bull;&bull;".htmlspecialchars($tail)."</span>";
}

function gip_lookup_card():string{
    $h="<div class='gip-card' id='gip-lookup-card'>";
    $h.="<div class='gip-card-head'><div><h3>{gip_lookup_title}</h3><p>{gip_lookup_intro}</p></div></div>";
    $h.="<form class='gip-lookup' onsubmit='GipLookup();return false;'>";
    $h.="<label class='sr-only' for='gip-lookup-ip'>{gip_ip_address}</label>";
    $h.="<input type='text' id='gip-lookup-ip' class='gip-input' placeholder='203.0.113.7' autocomplete='off' spellcheck='false'>";
    $h.="<button type='submit' class='gip-btn gip-btn-dark'><i class='fa-solid fa-magnifying-glass'></i> {gip_look_up}</button>";
    $h.="</form><div id='gip-lookup-res'></div>";
    return $h."</div>";
}

function gip_lookup():bool{
    $tpl=new template_admin();
    $ip=trim((string)$_GET["lookup"]);
    echo $tpl->_ENGINE_parse_body(gip_lookup_result($ip));
    return true;
}

function gip_lookup_result(string $ip):string{
    $IP=new IP();
    if($ip=="" || !$IP->isValid($ip)){
        return "<div class='gip-res gip-res-err'><i class='fa-solid fa-circle-exclamation'></i> {gip_lookup_invalid}</div>";
    }
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/geoip/query/".rawurlencode($ip)));
    if(!is_object($json)){
        return "<div class='gip-res gip-res-err'><i class='fa-solid fa-circle-exclamation'></i> {gip_al_daemon_t}</div>";
    }
    if(empty($json->Status)){
        return "<div class='gip-res gip-res-err'><i class='fa-solid fa-circle-exclamation'></i> {gip_lookup_failed}"
            ."<span class='gip-raw'>".htmlspecialchars((string)($json->Error ?? ""))."</span></div>";
    }
    $I=isset($json->Info)?$json->Info:new stdClass();
    $country=(string)($I->Country->Names->en ?? "");
    $iso=(string)($I->Country->IsoCode ?? "");
    $cont=(string)($I->Continent->Names->en ?? "");
    $city=(string)($I->City->Names->en ?? "");
    $tz=(string)($I->Location->TimeZone ?? "");
    $asnN=intval($json->ASN->AutonomousSystemNumber ?? 0);
    $asnO=(string)($json->ASN->AutonomousSystemOrganization ?? "");
    $h="<div class='gip-res'>";
    $h.="<div class='gip-res-top'><span class='gip-mono'>".htmlspecialchars($ip)."</span>";
    if($country<>""){
        $h.="<strong>".htmlspecialchars($country).($iso<>""?" (".htmlspecialchars($iso).")":"")."</strong>";
    }else{
        $h.="<strong class='gip-muted'>{gip_lookup_nocountry}</strong>";
    }
    $h.="</div><div class='gip-res-grid'>";
    $h.=gip_res_cell("{gip_continent}",$cont);
    $h.=gip_res_cell("{gip_city}",$city);
    $h.=gip_res_cell("{gip_timezone}",$tz);
    $h.=gip_res_cell("{gip_asn}",$asnN>0?"AS$asnN".($asnO<>""?" &middot; ".htmlspecialchars($asnO):""):"",true);
    $h.="</div>";
    if($country==""){$h.="<p class='gip-foot-help'>{gip_lookup_private}</p>";}
    return $h."</div>";
}

function gip_res_cell(string $label,string $value,bool $raw=false):string{
    $v=$value==""?"&mdash;":($raw?$value:htmlspecialchars($value));
    return "<div><span class='gip-res-lbl'>$label</span><span class='gip-res-val'>$v</span></div>";
}

function gip_off_card(array $m):string{
    if(!$m["enabled"]){return "";}
    $page=CurrentPageName();
    $h="<div class='gip-off'><strong>{gip_off_title}</strong><p>{gip_off_text}</p>";
    $h.=gip_btn("Loadjs('$page?disable-js=yes');","fa-solid fa-power-off","{disable_feature}","gip-btn-danger",$m["admin"]);
    return $h."</div>";
}

function gip_js(string $page):string{
    return "
function GipBusy(){
\tif(typeof ModalIsOpenOrOpening==='function' && ModalIsOpenOrOpening()){return true;}
\treturn !!document.querySelector('.sweet-alert.visible');
}
function GipReload(force){
\tif(!document.getElementById('gip-main')){ if(window.GipTimer){clearInterval(window.GipTimer);window.GipTimer=null;} return; }
\tif(!force && GipBusy()){return;}
\tLoadAjaxSilent('gip-main','$page?main-host=yes');
}
// Sondage seulement pendant la premiere mise a jour (fonction active, aucune base).
function GipPoll(){
\tvar p=document.getElementById('gip-poll'), on=p && p.getAttribute('data-poll')==='1';
\tif(on && !window.GipTimer){ window.GipTimer=setInterval(function(){GipReload(false);},20000); }
\tif(!on && window.GipTimer){ clearInterval(window.GipTimer); window.GipTimer=null; }
}
function GipLookup(){
\tvar e=document.getElementById('gip-lookup-ip'); if(!e){return;}
\tvar v=e.value.replace(/^\\s+|\\s+$/g,''); if(v.length===0){e.focus();return;}
\tLoadAjaxSilent('gip-lookup-res','$page?lookup='+encodeURIComponent(v));
}
function GipFocusLookup(){
\tvar c=document.getElementById('gip-lookup-card'), e=document.getElementById('gip-lookup-ip');
\tif(c && c.scrollIntoView){ c.scrollIntoView({behavior:'smooth',block:'center'}); }
\tif(e){ setTimeout(function(){e.focus();},300); }
}
if(window.GipTimer){clearInterval(window.GipTimer);window.GipTimer=null;}";
}

function gip_style():string{
    if(isset($GLOBALS["GIP_STYLE_DONE"])){return "";}
    $GLOBALS["GIP_STYLE_DONE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    $mono="ui-monospace,SFMono-Regular,Menlo,Consolas,monospace";
    $c=array();
    $c[]=".gip{--gp-accent:$accent;--gp-soft:$soft;margin-top:10px;color:#0f172a;font-size:14px}";
    $c[]=".gip *{box-sizing:border-box}";
    $c[]=".gip #gip-main{display:flex;flex-direction:column;gap:16px}";
    $c[]=".gip-mono{font-family:$mono}.gip-muted{color:#64748b}";
    $c[]=".gip-hero{display:flex;gap:18px;background:#fff;border:1px solid #e2e8f0;border-left:4px solid var(--gp-accent);border-radius:12px;padding:20px 24px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".gip-hero-ico{flex:0 0 52px;height:52px;border-radius:12px;background:var(--gp-soft);color:var(--gp-accent);display:flex;align-items:center;justify-content:center;font-size:24px}";
    $c[]=".gip-hero-body{flex:1;min-width:0}.gip-hero h2{margin:0 0 6px;font-size:20px;font-weight:600}";
    $c[]=".gip-hero p{margin:0;color:#475569;line-height:1.55;max-width:1000px}";
    $c[]=".gip-example{margin-top:8px!important;font-size:13px;color:#64748b!important}.gip-example i{color:#f59e0b;margin-right:4px}";
    $c[]=".gip-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9}";
    $c[]=".gip-chip{display:inline-flex;align-items:center;gap:7px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:5px 11px;font-size:13px;color:#334155}.gip-chip i{color:#64748b}";
    $c[]=".gip-chip-on{background:var(--gp-soft);border-color:transparent;color:#065f46}.gip-chip-on i{color:var(--gp-accent)}";
    $c[]=".gip-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}";
    $c[]=".gip-step{display:flex;gap:14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px}";
    $c[]=".gip-step-n{flex:0 0 34px;height:34px;border-radius:50%;border:1px solid #cbd5e1;display:flex;align-items:center;justify-content:center;font-weight:600;color:#334155}";
    $c[]=".gip-step-done{background:var(--gp-soft);border-color:transparent;color:var(--gp-accent)}";
    $c[]=".gip-step-body{display:flex;flex-direction:column;gap:3px;min-width:0;font-size:13px;color:#64748b}";
    $c[]=".gip-step-k{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8}";
    $c[]=".gip-step-body strong{font-size:15px;color:#0f172a}.gip-step-act{margin-top:8px}";
    $c[]=".gip-alerts{display:flex;flex-direction:column;gap:8px}";
    $c[]=".gip-alert{display:flex;align-items:center;gap:14px;border:1px solid;border-radius:12px;padding:14px 18px}";
    $c[]=".gip-alert-ico{flex:0 0 34px;height:34px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:16px}";
    $c[]=".gip-alert-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px;font-size:13.5px;line-height:1.5}";
    $c[]=".gip-alert-act{flex:0 0 auto}.gip-when{font-weight:400}";
    $c[]=".gip-alert-err{background:#fef2f2;border-color:#fca5a5;color:#991b1b}.gip-alert-err .gip-alert-ico{color:#dc2626}";
    $c[]=".gip-alert-warn{background:#fffbeb;border-color:#fcd34d;color:#92400e}.gip-alert-warn .gip-alert-ico{color:#d97706}";
    $c[]=".gip-raw{display:block;font-family:$mono;font-size:12px;color:#475569;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:5px 8px;word-break:break-all;margin-top:4px}";
    $c[]=".gip-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:8px;padding:8px 14px;font-size:13.5px;font-weight:600;border:1px solid transparent;cursor:pointer;line-height:1.2;white-space:nowrap}";
    $c[]=".gip-btn:focus-visible,.gip-input:focus-visible{outline:2px solid var(--gp-accent);outline-offset:2px}";
    $c[]=".gip-btn[disabled]{opacity:.55;cursor:not-allowed}";
    $c[]=".gip-btn-main{background:var(--gp-accent);color:#fff}.gip-btn-ghost{background:#fff;border-color:#e2e8f0;color:#334155}";
    $c[]=".gip-btn-link{background:transparent;color:#475569;font-weight:500}.gip-btn-dark{background:#0f172a;color:#fff}";
    $c[]=".gip-btn-small{padding:5px 10px;font-size:13px;background:#fff;border-color:#e2e8f0;color:#334155}";
    $c[]=".gip-btn-err{background:#fff;border-color:#f87171;color:#b91c1c}.gip-btn-warn{background:#fff;border-color:#f59e0b;color:#92400e}";
    $c[]=".gip-btn-danger{background:#fff;border-color:#f87171;color:#b91c1c}";
    $c[]=".gip-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}";
    $c[]=".gip-tile{display:flex;flex-direction:column;gap:4px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;min-height:100px;min-width:0}";
    $c[]=".gip-tile-lbl{font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b}";
    $c[]=".gip-tile-val{font-size:22px;font-weight:700;display:flex;align-items:center;gap:8px;font-variant-numeric:tabular-nums}";
    $c[]=".gip-src-val{font-size:17px;display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap}";
    $c[]=".gip-tile-sub{font-size:13px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".gip-dot{width:10px;height:10px;border-radius:50%;background:#94a3b8;display:inline-block}.gip-dot-ok{background:var(--gp-accent)}";
    $c[]=".gip-tag{display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:700;letter-spacing:.04em;border-radius:5px;padding:2px 7px;background:#d1fae5;color:#065f46}";
    $c[]=".gip-cols{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:16px;align-items:start}";
    $c[]=".gip-left,.gip-side{display:flex;flex-direction:column;gap:16px;min-width:0}";
    $c[]=".gip-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 22px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".gip-card-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:12px}";
    $c[]=".gip-card-head h3{margin:0 0 4px;font-size:16px;font-weight:600}.gip-card-head p{margin:0;color:#64748b;font-size:13.5px;line-height:1.5}";
    $c[]=".gip-total{flex:0 0 auto;font-family:$mono;font-size:13px;background:#f1f5f9;border-radius:6px;padding:4px 9px;color:#334155;white-space:nowrap}";
    $c[]=".gip-dbs{border-top:1px solid #f1f5f9}";
    $c[]=".gip-db{display:flex;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid #f1f5f9}";
    $c[]=".gip-db-main{min-width:0;display:flex;flex-direction:column;gap:4px}";
    $c[]=".gip-db-name{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.gip-db-name strong{font-size:15px}";
    $c[]=".gip-file{font-family:$mono;font-size:12px;background:#f1f5f9;border-radius:5px;padding:2px 7px;color:#475569}";
    $c[]=".gip-db-help{font-size:13px;color:#64748b}";
    $c[]=".gip-db-side{flex:0 0 auto;text-align:right;display:flex;flex-direction:column;gap:4px;align-items:flex-end}";
    $c[]=".gip-size{font-family:$mono;font-size:13px;color:#334155;margin-right:6px}.gip-db-when{font-size:13px;color:#94a3b8}";
    $c[]=".gip-pill{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:2px 9px;font-size:12.5px;font-weight:600}";
    $c[]=".gip-pill-ok{background:#d1fae5;color:#047857}.gip-pill-warn{background:#fef3c7;color:#92400e}";
    $c[]=".gip-card-foot{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-top:16px}";
    $c[]=".gip-foot-help{margin:10px 0 0;font-size:13px;color:#64748b;line-height:1.5}.gip-foot-help i{color:#94a3b8;margin-right:4px}";
    $c[]=".gip-empty{text-align:center;padding:28px 12px;border:1px dashed #cbd5e1;border-radius:10px;color:#475569}";
    $c[]=".gip-empty>i{font-size:36px;color:var(--gp-accent)}.gip-empty h4{margin:10px 0 6px;font-size:17px;font-weight:600;color:#0f172a}";
    $c[]=".gip-empty p{margin:0 auto 14px;max-width:460px;line-height:1.55}";
    $c[]=".gip-src{display:flex;gap:12px;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;margin-bottom:10px}";
    $c[]=".gip-src.is-active{border:2px solid var(--gp-accent);background:var(--gp-soft)}";
    $c[]=".gip-src.is-locked{opacity:.75}";
    $c[]=".gip-src-ico{flex:0 0 22px;color:#64748b;font-size:17px;padding-top:1px}.gip-src.is-active .gip-src-ico{color:var(--gp-accent)}";
    $c[]=".gip-src-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}";
    $c[]=".gip-src-top{display:flex;justify-content:space-between;gap:8px;align-items:center}.gip-src-top strong{font-size:14.5px}";
    $c[]=".gip-src-txt{font-size:13px;color:#475569;line-height:1.5}";
    $c[]=".gip-src-meta,.gip-src-why{display:block;margin-top:4px;font-size:13px;color:#334155}.gip-src-why{color:#92400e}";
    $c[]=".gip-src-act{margin-top:8px}";
    $c[]=".gip-lookup{display:flex;gap:8px}";
    $c[]=".gip-input{flex:1;min-width:0;border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;font-family:$mono;font-size:14px}";
    $c[]="#gip-lookup-res:empty{display:none}";
    $c[]=".gip-res{margin-top:12px;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;background:#f8fafc}";
    $c[]=".gip-res-err{color:#b91c1c;background:#fef2f2;border-color:#fca5a5;font-size:13.5px}";
    $c[]=".gip-res-top{display:flex;justify-content:space-between;gap:10px;align-items:center;padding-bottom:8px;margin-bottom:8px;border-bottom:1px solid #e2e8f0;flex-wrap:wrap}";
    $c[]=".gip-res-top .gip-mono{font-weight:600}";
    $c[]=".gip-res-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 16px}";
    $c[]=".gip-res-lbl{display:block;font-size:12.5px;color:#94a3b8}.gip-res-val{font-size:13.5px;color:#0f172a;word-break:break-word}";
    $c[]=".gip-off{background:#fff5f5;border:1px solid #fecaca;border-radius:12px;padding:16px 20px;color:#991b1b}";
    $c[]=".gip-off p{margin:4px 0 12px;font-size:13.5px;line-height:1.5}";
    $c[]=".sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}";
    $c[]="@media (prefers-reduced-motion:reduce){.gip *{transition:none!important;animation:none!important}}";
    $c[]="@media (max-width:1200px){.gip-cols{grid-template-columns:minmax(0,1fr)}.gip-strip{grid-template-columns:repeat(2,minmax(0,1fr))}.gip-steps{grid-template-columns:minmax(0,1fr)}}";
    return "<style>".@implode("\n",$c)."</style>";
}
