<?php
// stamp 01/11/2026
$GLOBALS["HOTFIX"]="";
// Service Pack 9 2026-10-10
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_POST["EXIT"])){die();}
if(isset($_POST["none"])){admin_tracks("Artica Update task as been launched");die();}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ArticaAutoUpateOfficial"])){save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["history"])){history();exit;}
if(isset($_GET["history-start"])){history_start();exit;}
if(isset($_GET["seen-all"])){history_seen_all();exit;}
if(isset($_POST["updh-seen"])){updh_seen_save();exit;}
if(isset($_GET["perform-update"])){javascript_run_update();exit;}
if(isset($_GET["rollback-sp"])){roolbackp_sp();exit;}
if(isset($_GET["delete-all-sp-js"])){delete_all_sp_js();exit;}
if(isset($_POST["deleteallsp"])){delete_all_sp_perform();exit;}
if(isset($_GET["main-rollbackjs"])){main_rollback_js();exit;}
if(isset($_POST["roolbackfull"])){main_rollback_perform();exit;}
if(isset($_GET["PatchsBackup-remove"])){PatchsBackup_remove();exit;}
if(isset($_POST["PatchsBackup-remove"])){PatchsBackup_remove_perform();exit;}
if(isset($_GET["refresh-page"])){RefreshPage();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["status-left"])){table_left();exit;}
if(isset($_GET["status-rigth"])){table_right();exit;}
if(isset($_GET["widget-currentver"])){print_r(widget_current_version());exit;}
if(isset($_GET["settings-js"])){settings_js();exit;}
if(isset($_GET["hotfixdev-js"])){hotfixdev_js();exit;}
if(isset($_GET["hotfixdev-popup"])){hotfixdev_popup();exit;}
if(isset($_POST["ArticaHotFixDevs"])){hotfixdev_save();exit;}


if(isset($_GET["settings-popup"])){table_right_form();exit;}
if(isset($_GET["update-hotfix-js"])){hotfix_rc_js();exit;}
if(isset($_GET["update-hotfix-popup"])){hotfix_rc_popup();exit;}
if(isset($_POST["hfrc-start"])){hfrc_start();exit;}
if(isset($_GET["hfrc-progress"])){hfrc_progress();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/articaweb/chown");
    $html=$tpl->page_header("{update_artica}","far fa-cloud-download",
        "{install_applis_text}<div id='header-update-page'></div>","$page?tabs=yes",
        "artica-update","progress-articaupd-restart",false,"main-artica-update-section");
//LoadAjax('main-artica-update-section',$page?tabs=yes');
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{update_artica}",$html);
		echo $tpl->build_firewall();
		return true;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
// ---------------------------------------------------------------------------
// Fenetre "HotFix RC" (maquette stitch_artica_hotfix_modal_redesign).
// Le correctif affiche est relu dans le depot a chaque ouverture : c'est celui
// qu'artica-update -hotfix installera (il prend toujours le dernier publie pour
// la version et le Service Pack installes), et plus une valeur decodee de l'URL.
// ---------------------------------------------------------------------------
function hotfix_rc_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog8("{hfrc_dialog_title}","$page?update-hotfix-popup=yes",820);
}

/** Traduit un jeton a %s sequentiels (les valeurs sont deja echappees). */
function hfrc_tr(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

function hfrc_esc($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),$s);
}

/** Age relatif ("2 h ago"), la date exacte part dans l'info-bulle. */
function hfrc_ago(int $ts):string{
    $d=time()-$ts;
    if($d<60){return hfrc_tr("hfrc_ago_now");}
    if($d<3600){return hfrc_tr("hfrc_ago_min",array(intval($d/60)));}
    if($d<86400){return hfrc_tr("hfrc_ago_h",array(intval($d/3600)));}
    return hfrc_tr("hfrc_ago_days",array(intval($d/86400)));
}

/** Modele unique de la fenetre : tout ce qu'affichent les widgets. */
function hfrc_model():array{
    $users=new usersMenus();
    $m=array();
    $m["admin"]=(bool)$users->AsSystemAdministrator;
    $m["version"]=trim((string)@file_get_contents(dirname(__FILE__)."/VERSION"));
    $m["sp"]=intval($GLOBALS["CLASS_SOCKETS"]->SPVersion());
    $m["cur_hotfix"]=trim((string)(isset($GLOBALS["HOTFIX"])?$GLOBALS["HOTFIX"]:""));
    $repos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
    if(!is_array($repos)){$repos=array();}
    $hot=update_find_hotfix_unstable($repos);
    $m["available"]=false;
    $m["target"]="";$m["url"]="";$m["md5"]="";$m["size"]=0;$m["build"]=0;
    if(count($hot)>0){
        $target=trim((string)(isset($hot["VERSION"])?$hot["VERSION"]:""));
        if($target!==""){
            $m["available"]=true;
            $m["target"]=$target;
            $url=(string)(isset($hot["URL"])?$hot["URL"]:"");
            if(preg_match('#^https?://[A-Za-z0-9._~:/?\#\[\]@!$&()*+,;=%-]+$#',$url)){$m["url"]=$url;}
            $md5=strtolower(trim((string)(isset($hot["MD5"])?$hot["MD5"]:"")));
            if(preg_match('#^[a-f0-9]{32}$#',$md5)){$m["md5"]=$md5;}
            $m["size"]=intval(isset($hot["SIZE"])?$hot["SIZE"]:0);
            // 20230713-13 : date de construction puis numero.
            if(preg_match('#^(\d{4})(\d{2})(\d{2})-\d+$#',$target,$re) && checkdate(intval($re[2]),intval($re[3]),intval($re[1]))){
                $m["build"]=mktime(12,0,0,intval($re[2]),intval($re[3]),intval($re[1]));
            }
        }
    }
    // Le daemon n'horodate pas le releve du depot : le mtime du fichier de
    // progression qu'il ecrit a chaque passage en tient lieu (cf. upd_last_check).
    $m["last_check"]=0;
    $f=PROGRESS_DIR."/refresh.index.progress";
    if(is_file($f)){$t=@filemtime($f);if($t!==false && $t>0){$m["last_check"]=$t;}}
    return $m;
}

function hotfix_rc_popup():bool{
    $tpl=new template_admin();
    $m=hfrc_model();
    $html=array();
    $html[]="<div class='hfrc' id='hfrc-root'>";
    if(!$m["available"]){
        $html[]=hfrc_uptodate($m);
        $html[]="</div>";
        echo hfrc_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
        return true;
    }
    $html[]=hfrc_header($m);
    $html[]="<div id='hfrc-view-default'>";
    $html[]=hfrc_banner($m);
    $html[]=hfrc_compare($m);
    $html[]=hfrc_details($m);
    $html[]=hfrc_notice();
    $html[]=hfrc_footer($m);
    $html[]="</div>";
    $html[]=hfrc_installing($m);
    $html[]=hfrc_success($m);
    $html[]=hfrc_failure();
    $html[]="</div>";
    echo hfrc_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).hfrc_js($m);
    return true;
}

function hfrc_header(array $m):string{
    return "<div class='hfrc-head'><span class='hfrc-head-ico'><i class='fas fa-flask'></i></span>"
        ."<div><div class='hfrc-title'>{hfrc_title}</div>"
        ."<div class='hfrc-sub'>HotFix RC &middot; v".hfrc_esc($m["target"])."</div></div></div>";
}

function hfrc_banner(array $m):string{
    $sp=$m["sp"]>0?hfrc_tr("hfrc_sp_n",array($m["sp"])):hfrc_tr("hfrc_sp_none");
    $chips=array();
    $chips[]=array("fas fa-shield-check","{hfrc_chip_checked}","{hfrc_chip_checked_tip}");
    $chips[]=array("fas fa-bullseye",hfrc_tr("hfrc_chip_version",array(hfrc_esc($m["version"]),$sp)),"");
    $chips[]=array("fas fa-gears","{hfrc_chip_background}","{hfrc_chip_background_tip}");
    $c=array();
    foreach($chips as $ch){
        $title=$ch[2]!=""?" title=\"".$ch[2]."\"":"";
        $c[]="<span class='hfrc-chip'$title><i class='{$ch[0]}'></i>{$ch[1]}</span>";
    }
    return "<div class='hfrc-banner'><div class='hfrc-banner-t'>{hfrc_banner_title}</div>"
        ."<p>{hfrc_banner_text}</p><div class='hfrc-chips'>".@implode("",$c)."</div></div>";
}

function hfrc_compare(array $m):string{
    $sp=$m["sp"]>0?hfrc_tr("hfrc_sp_n",array($m["sp"])):hfrc_tr("hfrc_sp_none");
    $cur=$m["cur_hotfix"]!==""?hfrc_esc($m["cur_hotfix"]):"{hfrc_none}";
    $build="";
    if($m["build"]>0){
        $build="<div class='hfrc-cmp-note'>".hfrc_tr("hfrc_build_of",array(date("j M Y",$m["build"])))."</div>";
    }
    $h=array();
    $h[]="<div class='hfrc-sect-t'>{hfrc_compare_title}</div>";
    $h[]="<div class='hfrc-cmp'>";
    $h[]="<div class='hfrc-cmp-card'><div class='hfrc-cmp-k'>{hfrc_installed_now}</div>"
        ."<div class='hfrc-cmp-v'>Artica ".hfrc_esc($m["version"])."</div>"
        ."<div class='hfrc-cmp-l'>$sp <i class='far fa-circle-question hfrc-help' title=\"{hfrc_sp_tip}\" aria-label=\"{hfrc_sp_tip}\"></i></div>"
        ."<div class='hfrc-cmp-l'>{hfrc_hotfix_label} <b>$cur</b></div></div>";
    $h[]="<div class='hfrc-cmp-arrow' aria-hidden='true'><i class='fas fa-arrow-right'></i></div>";
    $h[]="<div class='hfrc-cmp-card hfrc-cmp-target'><div class='hfrc-cmp-top'><div class='hfrc-cmp-k'>{hfrc_after_update}</div>"
        ."<span class='hfrc-target'>{hfrc_target}</span></div>"
        ."<div class='hfrc-cmp-v'>Artica ".hfrc_esc($m["version"])."</div>"
        ."<div class='hfrc-cmp-l hfrc-cmp-acc'>$sp &middot; {hfrc_hotfix_label} ".hfrc_esc($m["target"])."</div>$build</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function hfrc_details(array $m):string{
    $file=$m["url"]!==""?basename(parse_url($m["url"],PHP_URL_PATH)):"";
    $h=array();
    $h[]="<div class='hfrc-card'><div class='hfrc-card-t'>{hfrc_details_title}</div><div class='hfrc-dl'>";
    // Paquet
    $dl="";
    if($m["url"]!==""){
        $dl=" <a class='hfrc-link' href=\"".hfrc_esc($m["url"])."\" rel='noopener noreferrer' download><i class='fas fa-download'></i>{hfrc_download}</a>";
    }
    $h[]="<div><div class='hfrc-dt'>{hfrc_package}</div><div class='hfrc-dd'><span class='hfrc-mono'>".($file!==""?hfrc_esc($file):"&mdash;")."</span>$dl</div>"
        ."<div class='hfrc-help-t'>{hfrc_package_help}</div></div>";
    // Taille
    $size=$m["size"]>0?FormatBytes($m["size"]/1024,true):"&mdash;";
    $h[]="<div><div class='hfrc-dt'>{hfrc_size}</div><div class='hfrc-dd'>$size</div>"
        ."<div class='hfrc-help-t'>{hfrc_size_help}</div></div>";
    // Empreinte
    if($m["md5"]!==""){
        $short=substr($m["md5"],0,8)."&hellip;".substr($m["md5"],-8);
        $md="<span class='hfrc-mono hfrc-md5' title='".$m["md5"]."'>$short</span>"
            ."<button type='button' class='hfrc-copy' OnClick=\"HfrcCopy('".$m["md5"]."');\" aria-label=\"{hfrc_copy_md5}\" title=\"{hfrc_copy_md5}\"><i class='far fa-copy'></i></button>"
            ."<span class='hfrc-copied' id='hfrc-copied' role='status'></span>";
    }else{
        $md="&mdash;";
    }
    $h[]="<div><div class='hfrc-dt'>{hfrc_md5}</div><div class='hfrc-dd'>$md</div>"
        ."<div class='hfrc-help-t'>{hfrc_md5_help}</div></div>";
    // Dernier releve
    if($m["last_check"]>0){
        $tpl=new template_admin();
        $exact=htmlspecialchars((string)$tpl->time_to_date($m["last_check"],true),ENT_QUOTES,"UTF-8");
        $lc="<span title=\"$exact\">".hfrc_ago($m["last_check"])." <i class='far fa-clock hfrc-help'></i></span>";
    }else{
        $lc="{never}";
    }
    $h[]="<div><div class='hfrc-dt'>{hfrc_last_check}</div><div class='hfrc-dd'>$lc</div>"
        ."<div class='hfrc-help-t'>{hfrc_last_check_help}</div></div>";
    $h[]="</div></div>";
    return @implode("",$h);
}

function hfrc_notice():string{
    return "<div class='hfrc-notice' role='note'><div class='hfrc-notice-t'><i class='fas fa-triangle-exclamation'></i>{hfrc_notice_title}</div>"
        ."<ul><li>{hfrc_notice_files}</li><li>{hfrc_notice_test}</li><li>{hfrc_notice_undo}</li><li>{hfrc_notice_settings}</li></ul></div>";
}

function hfrc_footer(array $m):string{
    $install="<button type='button' class='hfrc-btn hfrc-btn-primary' id='hfrc-install' OnClick=\"HfrcStart();\"><i class='fas fa-download'></i>{hfrc_install}</button>";
    $note="";
    if(!$m["admin"]){
        $install="<button type='button' class='hfrc-btn hfrc-btn-primary' disabled aria-describedby='hfrc-admin-note'><i class='fas fa-download'></i>{hfrc_install}</button>";
        $note="<span class='hfrc-admin-note' id='hfrc-admin-note'><i class='fas fa-lock'></i>{hfrc_admin_only}</span>";
    }
    return "<div class='hfrc-foot'><button type='button' class='hfrc-btn hfrc-btn-ghost' id='hfrc-notnow' OnClick=\"HfrcClose();\">{hfrc_not_now}</button>"
        ."<div class='hfrc-foot-r'>$note$install</div></div>";
}

/** Etapes reelles d'artica-update -hotfix (hotfix.update.progress). */
function hfrc_installing(array $m):string{
    $steps=array(
        1=>array("{hfrc_step_download}","{hfrc_step_download_help}"),
        2=>array("{hfrc_step_check}","{hfrc_step_check_help}"),
        3=>array("{hfrc_step_install}","{hfrc_step_install_help}"),
        4=>array("{hfrc_step_perms}","{hfrc_step_perms_help}"),
    );
    $li=array();
    foreach($steps as $n=>$s){
        $li[]="<li class='hfrc-step' id='hfrc-step-$n' data-state='pending'><span class='hfrc-step-dot'><span class='hfrc-step-n'>$n</span>"
            ."<i class='fas fa-check hfrc-step-ok'></i><i class='fas fa-xmark hfrc-step-ko'></i><span class='hfrc-spin'></span></span>"
            ."<div><div class='hfrc-step-t'>{$s[0]}</div><div class='hfrc-help-t'>{$s[1]}</div></div>"
            ."<span class='hfrc-step-state' id='hfrc-step-state-$n'>{hfrc_state_pending}</span></li>";
    }
    return "<div id='hfrc-view-installing' style='display:none'>"
        ."<div class='hfrc-prog'><div class='hfrc-prog-top'><span id='hfrc-prog-msg'>{hfrc_waiting_start}</span><span id='hfrc-prog-pct'>0%</span></div>"
        ."<div class='hfrc-bar' role='progressbar' id='hfrc-bar' aria-valuemin='0' aria-valuemax='100' aria-valuenow='0' aria-label=\"{hfrc_progress_label}\"><div class='hfrc-bar-fill' id='hfrc-bar-fill'></div></div>"
        ."<p class='hfrc-help-t'><i class='fas fa-circle-info'></i> {hfrc_background_note}</p></div>"
        ."<div class='hfrc-sect-t'>{hfrc_steps_title}</div><ol class='hfrc-steps'>".@implode("",$li)."</ol>"
        ."<div class='hfrc-foot'><span></span><button type='button' class='hfrc-btn hfrc-btn-ghost' OnClick=\"HfrcClose();\">{hfrc_close_window}</button></div></div>";
}

function hfrc_success(array $m):string{
    return "<div id='hfrc-view-success' style='display:none' class='hfrc-result'>"
        ."<span class='hfrc-result-ico hfrc-ok'><i class='fas fa-circle-check'></i></span>"
        ."<div class='hfrc-result-t'>".hfrc_tr("hfrc_success_title",array(hfrc_esc($m["target"])))."</div>"
        ."<p>{hfrc_success_text}</p>"
        ."<div class='hfrc-foot hfrc-foot-c'><button type='button' class='hfrc-btn hfrc-btn-primary' OnClick=\"HfrcClose();\">{close}</button></div></div>";
}

function hfrc_failure():string{
    return "<div id='hfrc-view-failure' style='display:none'><div class='hfrc-fail' role='alert'>"
        ."<i class='fas fa-circle-xmark hfrc-fail-ico'></i><div><div class='hfrc-result-t'>{hfrc_fail_title}</div>"
        ."<div class='hfrc-fail-msg' id='hfrc-fail-msg'></div><p id='hfrc-fail-help'></p>"
        ."<p class='hfrc-fail-safe' id='hfrc-fail-safe' style='display:none'><i class='fas fa-shield-check'></i> {hfrc_fail_nothing_changed}</p></div></div>"
        ."<div class='hfrc-foot'><button type='button' class='hfrc-btn hfrc-btn-ghost' OnClick=\"HfrcClose();\">{close}</button>"
        ."<button type='button' class='hfrc-btn hfrc-btn-primary' id='hfrc-retry' OnClick=\"HfrcStart();\"><i class='fas fa-rotate-right'></i>{hfrc_try_again}</button></div></div>";
}

/** Plus rien a installer : etat vide qui rassure au lieu d'un tableau vide. */
function hfrc_uptodate(array $m):string{
    $sp=$m["sp"]>0?hfrc_tr("hfrc_sp_n",array($m["sp"])):hfrc_tr("hfrc_sp_none");
    $lc="";
    if($m["last_check"]>0){
        $tpl=new template_admin();
        $lc="<p class='hfrc-help-t' title=\"".htmlspecialchars((string)$tpl->time_to_date($m["last_check"],true),ENT_QUOTES,"UTF-8")."\">{hfrc_last_check}: ".hfrc_ago($m["last_check"])."</p>";
    }
    return "<div class='hfrc-result'><span class='hfrc-result-ico hfrc-ok'><i class='fas fa-circle-check'></i></span>"
        ."<div class='hfrc-result-t'>{hfrc_uptodate_title}</div>"
        ."<p>".hfrc_tr("hfrc_uptodate_text",array(hfrc_esc($m["version"]),$sp))."</p>$lc"
        ."<div class='hfrc-foot hfrc-foot-c'><button type='button' class='hfrc-btn hfrc-btn-primary' OnClick=\"HfrcClose();\">{close}</button></div></div>";
}

/** POST hfrc-start : lance artica-update -hotfix (detache) et rend l'heure de depart. */
function hfrc_start():bool{
    header("content-type: application/json");
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl=new template_admin();
        echo json_encode(array("ok"=>false,"error"=>html_entity_decode($tpl->_ENGINE_parse_body("{hfrc_admin_only}"),ENT_QUOTES,"UTF-8")));
        return false;
    }
    $t0=time();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/artica/hotfix/perform"));
    if(!$json || !isset($json->Status) || !$json->Status){
        $err=($json && isset($json->Error))?(string)$json->Error:"articarest: no answer";
        echo json_encode(array("ok"=>false,"error"=>$err));
        return false;
    }
    admin_tracks("Launch the installation of the latest Artica HotFix RC");
    echo json_encode(array("ok"=>true,"t0"=>$t0));
    return true;
}

/** GET hfrc-progress : etat du fichier ecrit par artica-update (POURC/TEXT). */
function hfrc_progress():bool{
    header("content-type: application/json");
    header("Cache-Control: no-store");
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo json_encode(array("ok"=>false));return false;}
    $f=PROGRESS_DIR."/hotfix.update.progress";
    if(!is_file($f)){echo json_encode(array("ok"=>true,"mtime"=>0,"now"=>time()));return true;}
    clearstatcache(true,$f);
    $a=@unserialize((string)@file_get_contents($f),array("allowed_classes"=>false));
    if(!is_array($a)){$a=array();}
    $tpl=new template_admin();
    $text=(string)(isset($a["TEXT"])?$a["TEXT"]:"");
    $text=html_entity_decode($tpl->_ENGINE_parse_body($text),ENT_QUOTES,"UTF-8");
    echo json_encode(array("ok"=>true,"mtime"=>intval(@filemtime($f)),"now"=>time(),
        "pourc"=>intval(isset($a["POURC"])?$a["POURC"]:0),"text"=>$text));
    return true;
}

function hfrc_js(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=array();
    foreach(array("hfrc_state_pending","hfrc_state_running","hfrc_state_done","hfrc_state_failed","hfrc_waiting_start",
        "hfrc_fail_download","hfrc_fail_corrupt","hfrc_fail_uptodate","hfrc_fail_nostart","hfrc_fail_generic","hfrc_copied") as $k){
        $t[$k]=trim(html_entity_decode(str_replace(array("[br]","<br>","<br/>","<br />")," ",$tpl->_ENGINE_parse_body("{".$k."}")),ENT_QUOTES,"UTF-8"));
    }
    $T=json_encode($t);
    return "<script>
(function(){
var T=$T, P='$page';
window.HfrcState={t0:0,timer:null,started:0};
function el(id){return document.getElementById(id);}
function show(v){var a=['default','installing','success','failure'];for(var i=0;i<a.length;i++){var e=el('hfrc-view-'+a[i]);if(e){e.style.display=(a[i]==v)?'':'none';}}}
window.HfrcClose=function(){ if(window.HfrcState.timer){clearTimeout(window.HfrcState.timer);} if(typeof dialogInstance8!=='undefined' && dialogInstance8){dialogInstance8.close();} };
window.HfrcCopy=function(v){
  var ok=function(){var s=el('hfrc-copied');if(s){s.textContent=T.hfrc_copied;setTimeout(function(){s.textContent='';},1600);}};
  if(navigator.clipboard && window.isSecureContext){navigator.clipboard.writeText(v).then(ok,function(){fb();});return;}
  fb();
  function fb(){var ta=document.createElement('textarea');ta.value=v;ta.setAttribute('readonly','');ta.style.position='absolute';ta.style.left='-9999px';document.body.appendChild(ta);ta.select();try{document.execCommand('copy');ok();}catch(e){}document.body.removeChild(ta);}
};
function setStep(n,state){var li=el('hfrc-step-'+n);if(!li){return;}li.setAttribute('data-state',state);
  if(state=='running'){li.setAttribute('aria-current','step');}else{li.removeAttribute('aria-current');}
  var s=el('hfrc-step-state-'+n);if(s){s.textContent=T['hfrc_state_'+state];}}
function setBar(p,msg){p=Math.max(0,Math.min(100,p));var f=el('hfrc-bar-fill');if(f){f.style.width=p+'%';}
  var b=el('hfrc-bar');if(b){b.setAttribute('aria-valuenow',p);} var pc=el('hfrc-prog-pct');if(pc){pc.textContent=p+'%';}
  var m=el('hfrc-prog-msg');if(m && msg){m.textContent=msg;}}
function steps(p){ // 50 telechargement, 70 decompression (MD5 deja verifie), 90 permissions, 100 fin
  var r= p<70?1 : (p<90?3:4);
  for(var i=1;i<=4;i++){ setStep(i, i<r?'done':(i==r?'running':'pending')); }
  return r;}
function fail(text,step){
  for(var i=1;i<=4;i++){var li=el('hfrc-step-'+i);if(li && li.getAttribute('data-state')=='running'){step=step||i;}}
  var low=(text||'').toLowerCase(), help=T.hfrc_fail_generic, safe=false;
  if(low.indexOf('corrupt')>-1){help=T.hfrc_fail_corrupt;safe=true;step=2;}
  else if(low.indexOf('download')>-1||low.indexOf('url')>-1){help=T.hfrc_fail_download;safe=true;step=1;}
  else if(low.indexOf('no available')>-1){help=T.hfrc_fail_uptodate;safe=true;step=1;}
  if(step){for(var j=1;j<step;j++){setStep(j,'done');} for(var k=step+1;k<=4;k++){setStep(k,'pending');} setStep(step,'failed');}
  el('hfrc-fail-msg').textContent=text||'';
  el('hfrc-fail-help').textContent=help;
  el('hfrc-fail-safe').style.display=safe?'':'none';
  show('failure');}
function done(){ for(var i=1;i<=4;i++){setStep(i,'done');} setBar(100,null);
  show('success');
  if(el('status-left')){LoadAjaxSilent('status-left',P+'?status-left=yes');}
  if(el('status-right')){LoadAjaxSilent('status-right',P+'?status-rigth=yes');}}
function poll(){
  var S=window.HfrcState; if(!el('hfrc-root')){return;}
  $.ajax({url:P+'?hfrc-progress=yes&_='+Date.now(),dataType:'json',cache:false}).done(function(d){
    if(!el('hfrc-root')){return;}
    if(d && d.ok && d.mtime>=S.t0-1 && d.pourc>0){
      if(d.pourc>100){fail(d.text,0);return;}
      if(d.pourc>=100){done();return;}
      steps(d.pourc); setBar(d.pourc,d.text);
    }else if(Date.now()-S.started>180000){fail(T.hfrc_fail_nostart,1);return;}
    S.timer=setTimeout(poll,2000);
  }).fail(function(){ S.timer=setTimeout(poll,4000); });}
window.HfrcStart=function(){
  var S=window.HfrcState; if(S.timer){clearTimeout(S.timer);}
  for(var i=1;i<=4;i++){setStep(i,'pending');} setStep(1,'running'); setBar(0,T.hfrc_waiting_start);
  show('installing');
  $.post(P,{'hfrc-start':'yes'},null,'json').done(function(d){
    if(!d||!d.ok){fail((d&&d.error)?d.error:'',1);return;}
    S.t0=parseInt(d.t0,10)||0; S.started=Date.now(); S.timer=setTimeout(poll,1500);
  }).fail(function(x){fail(x&&x.responseText?String(x.responseText).substring(0,200):'',1);});
};
var b=el('hfrc-notnow'); if(b){try{b.focus();}catch(e){}}
})();
</script>";
}

function hfrc_style():string{
    if(isset($GLOBALS["HFRC_CSS"])){return "";}
    $GLOBALS["HFRC_CSS"]=true;
    $accent="#005447";$dark="#003d33";$soft="#e6eeed";$line="#bfd4d1";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.hfrc{color:#0f172a;font-size:14px;line-height:1.5;display:flex;flex-direction:column;gap:18px;padding:4px 4px 0}
.hfrc *{box-sizing:border-box}
.hfrc-head{display:flex;align-items:center;gap:14px;padding-bottom:16px;border-bottom:1px solid #e2e8f0}
.hfrc-head-ico{width:40px;height:40px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:17px;flex:0 0 40px}
.hfrc-title{font-size:18px;font-weight:600;line-height:1.3}
.hfrc-sub{font-size:13px;color:#64748b}
#hfrc-view-default,#hfrc-view-installing,#hfrc-view-failure{display:flex;flex-direction:column;gap:18px}
.hfrc-banner{background:#f2f6f6;border:1px solid $line;border-radius:10px;padding:16px 18px}
.hfrc-banner-t{font-weight:600;font-size:15px;margin-bottom:6px}
.hfrc-banner p{margin:0 0 12px;color:#334155}
.hfrc-chips{display:flex;flex-wrap:wrap;gap:8px}
.hfrc-chip{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid $line;border-radius:999px;padding:3px 11px;font-size:13px;color:#1e293b}
.hfrc-chip i,.hfrc-chip svg{color:$accent}
.hfrc-sect-t{font-size:13px;font-weight:600;color:#475569;margin-bottom:-8px}
.hfrc-cmp{display:grid;grid-template-columns:1fr 32px 1fr;align-items:stretch;gap:8px}
.hfrc-cmp-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;min-width:0}
.hfrc-cmp-target{background:$soft;border:2px solid $accent}
.hfrc-cmp-top{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.hfrc-cmp-k{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#475569;margin-bottom:6px}
.hfrc-target{background:$accent;color:#fff;font-size:12px;font-weight:600;border-radius:6px;padding:1px 8px;white-space:nowrap}
.hfrc-cmp-v{font-weight:600;font-size:15px}
.hfrc-cmp-l{color:#334155;font-size:13px;margin-top:2px}
.hfrc-cmp-acc{color:$dark;font-weight:600}
.hfrc-cmp-note{color:#64748b;font-size:12.5px;margin-top:6px}
.hfrc-cmp-arrow{display:flex;align-items:center;justify-content:center;color:$accent;font-size:16px}
.hfrc-help{color:#94a3b8;cursor:help}
.hfrc-card{border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px;background:#fff}
.hfrc-card-t{font-weight:600;margin-bottom:12px}
.hfrc-dl{display:grid;grid-template-columns:1fr 1fr;gap:14px 24px}
.hfrc-dt{font-size:13px;color:#64748b}
.hfrc-dd{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin:2px 0}
.hfrc-help-t{font-size:12.5px;color:#64748b;margin:0}
.hfrc-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px;word-break:break-all}
.hfrc-md5{background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:2px 8px;color:#334155}
.hfrc-link{color:$accent;font-weight:600;text-decoration:underline;display:inline-flex;align-items:center;gap:5px}
.hfrc-link:hover{color:$dark}
.hfrc-copy{border:1px solid transparent;background:none;color:#64748b;border-radius:6px;padding:2px 6px;cursor:pointer}
.hfrc-copy:hover{color:$accent;border-color:$line}
.hfrc-copied{font-size:12.5px;color:#15803d}
.hfrc-notice{background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #f59e0b;border-radius:10px;padding:14px 18px;color:#78350f}
.hfrc-notice-t{font-weight:600;color:#92400e;display:flex;align-items:center;gap:8px;margin-bottom:6px}
.hfrc-notice-t i,.hfrc-notice-t svg{color:#b45309}
.hfrc-notice ul{margin:0;padding-left:26px}
.hfrc-notice li{margin:3px 0}
.hfrc-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;border-top:1px solid #e2e8f0;padding-top:16px;margin-top:2px}
.hfrc-foot-c{justify-content:center;border-top:0}
.hfrc-foot-r{display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end}
.hfrc-btn{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 18px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;border:1px solid transparent;transition:background .15s,color .15s}
.hfrc-btn-primary{background:$accent;color:#fff}
.hfrc-btn-primary:hover{background:$dark;color:#fff}
.hfrc-btn-primary[disabled]{background:#cbd5e1;color:#475569;cursor:not-allowed}
.hfrc-btn-ghost{background:#fff;color:#334155;border-color:#e2e8f0}
.hfrc-btn-ghost:hover{background:#f8fafc}
.hfrc-btn:focus-visible,.hfrc-link:focus-visible,.hfrc-copy:focus-visible{outline:2px solid $accent;outline-offset:2px}
.hfrc-admin-note{font-size:13px;color:#64748b;display:inline-flex;align-items:center;gap:6px}
.hfrc-prog{border:1px solid #e2e8f0;border-radius:10px;padding:14px 18px}
.hfrc-prog-top{display:flex;justify-content:space-between;font-weight:600;margin-bottom:8px}
.hfrc-bar{height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin-bottom:10px}
.hfrc-bar-fill{height:100%;width:0;background:$accent;border-radius:999px;transition:width .4s}
.hfrc-steps{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
.hfrc-step{display:grid;grid-template-columns:32px 1fr auto;gap:12px;align-items:center;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px}
.hfrc-step-dot{width:28px;height:28px;border-radius:50%;border:2px solid #cbd5e1;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:13px;font-weight:600;position:relative}
.hfrc-step-ok,.hfrc-step-ko,.hfrc-spin{display:none}
.hfrc-step-t{font-weight:600}
.hfrc-step-state{font-size:12.5px;color:#64748b;white-space:nowrap}
.hfrc-step[data-state=running]{border-color:$line;background:#f2f6f6}
.hfrc-step[data-state=running] .hfrc-step-dot{border-color:$accent;border-top-color:transparent;animation:hfrcspin 1s linear infinite}
.hfrc-step[data-state=running] .hfrc-step-n{animation:hfrcspin 1s linear infinite reverse}
.hfrc-step[data-state=running] .hfrc-step-state{color:$dark;font-weight:600}
.hfrc-step[data-state=done] .hfrc-step-dot{background:$accent;border-color:$accent;color:#fff}
.hfrc-step[data-state=done] .hfrc-step-n{display:none}
.hfrc-step[data-state=done] .hfrc-step-ok{display:inline-block}
.hfrc-step[data-state=failed]{border-color:#fecaca;background:#fef2f2}
.hfrc-step[data-state=failed] .hfrc-step-dot{background:#b91c1c;border-color:#b91c1c;color:#fff}
.hfrc-step[data-state=failed] .hfrc-step-n{display:none}
.hfrc-step[data-state=failed] .hfrc-step-ko{display:inline-block}
.hfrc-step[data-state=failed] .hfrc-step-state{color:#b91c1c;font-weight:600}
@keyframes hfrcspin{to{transform:rotate(360deg)}}
.hfrc-result{text-align:center;padding:12px 8px 0;display:flex;flex-direction:column;align-items:center;gap:8px}
.hfrc-result p{margin:0;color:#475569;max-width:560px}
.hfrc-result-ico{font-size:48px;line-height:1}
.hfrc-ok{color:#15803d}
.hfrc-result-t{font-size:17px;font-weight:600}
.hfrc-fail{display:flex;gap:14px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 18px;color:#7f1d1d}
.hfrc-fail-ico{color:#b91c1c;font-size:26px;flex:0 0 auto}
.hfrc-fail .hfrc-result-t{color:#b91c1c}
.hfrc-fail-msg{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px;margin:6px 0}
.hfrc-fail p{margin:4px 0 0}
.hfrc-fail-safe{color:#166534}
@media (max-width:1200px){.hfrc-cmp{grid-template-columns:1fr}.hfrc-cmp-arrow{transform:rotate(90deg)}.hfrc-dl{grid-template-columns:1fr}}
@media (prefers-reduced-motion:reduce){.hfrc *{animation:none!important;transition:none!important}}
</style>";
}

function delete_all_sp_js():bool{
    $tpl=new template_admin();
   return  $tpl->js_confirm_delete("Service Packs: {delete_all}","deleteallsp","none","document.location.href='/artica-update';");
}
function PatchsBackup_remove():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PatchsBackupSize=FormatBytes(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PatchsBackupSize"))/1024);
    return $tpl->js_confirm_delete("{FullAgeMax} $PatchsBackupSize",
        "PatchsBackup-remove","$PatchsBackupSize",
        "LoadAjax('main-artica-update-section','$page?tabs=yes');");
}
function PatchsBackup_remove_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork('artica.php?PatchsBackupRemove=yes');
    admin_tracks("Removed Service Packs backup folder.");
    return true;
}
function main_rollback_js():bool{
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $tpl=new template_admin();
    // Cette valeur repartait BRUTE dans la ligne de commande passee au
    // repartiteur framework ET dans le JavaScript de confirmation. Un numero de
    // version ne contient que des chiffres et des points.
    $version=preg_replace('/[^0-9.]/','',(string)$_GET["main-rollbackjs"]);
    if($version===""){return $tpl->js_error("{error}");}
    $jsrestart=$tpl->framework_buildjs("system.php?roolback-global=$version","roolback.progress","roolback.progress.txt","progress-articaupd-restart","document.location.href='/logoff.php'","AsSystemAdministrator");
    return $tpl->js_confirm_execute("Roolback : $version","roolbackfull",$version,$jsrestart);
}
function main_rollback_perform():bool{
    $version=$_POST["roolbackfull"];
    admin_tracks("Rollback main artica version to $version");
    return true;
}
function delete_all_sp_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/artica/servicepacks/removeall");
    admin_tracks("Service packs history as been removed");
    return true;
}


function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["Artica"]="$page?table-start=yes";
    // Onglet "{schedule}" retire : il pointait sur
    // fw.system.tasks.php?ForceTaskType=83, et le type 83
    // ({update_artica_firmware}) n'existe plus au catalogue (SystemTasks/
    // catalog.go). Il etait deja inerte cote PHP -- tasks_processes[83]
    // commente et task_disabled[83]=true, donc saute deux fois par
    // build_schedules() -- de sorte que l'onglet n'affichait qu'une liste vide
    // dont le bouton "nouvelle tache" proposait de creer une planification que
    // le generateur refuse maintenant explicitement ("unknown task type").
    $array["{history}"]="$page?history-start=yes";
    $array["{artica_packages}"]="fw.updates.packages.php";
	$array["{OS}"]="fw.updates.debian.php";
	echo $tpl->tabs_default($array);
    return true;
}

function roolbackp_sp():bool{
    $tpl        = new template_admin();
    $sp         = intval($_GET["rollback-sp"]);
    $page       = CurrentPageName();
    $users      = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/roolback.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/roolback.progress.txt";
    $ARRAY["CMD"]="system.php?roolback-sp=$sp";
    $ARRAY["TITLE"]="Rollbackup Service Pack $sp";
    $ARRAY["AFTER"]="LoadAjax('table-loader-articaupd-service','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-articaupd-restart')";
    return $tpl->js_confirm_execute("Rollback Service Pack $sp","EXIT","EXIT",$jsrestart);
}

function save():bool{
	$tpl=new template_admin();

	if(isset($_POST["ArticaEnablePatchs"])){

	    if(intval($_POST["ArticaEnablePatchs"])==1){
	        $_POST["ArticaDisablePatchs"]=0;
        }else{
            $_POST["ArticaDisablePatchs"]=1;
        }
        unset($_POST["ArticaEnablePatchs"]);
    }

    admin_tracks("Artica Update settings as been modified.");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?config-client=yes");
	$tpl->SAVE_POSTs();
    return true;
}
// -- History tab ---------------------------------------------------------------
// Stitch redesign 2026-11-21 (stitch_artica_update_history_redesign). REAL DATA ONLY:
// /home/artica/SQLITE/nightly.db table history (ID, version UNIQUE, updated, asseen), written by
// articarest aupdate/Nightly.go after a SUCCESSFUL install only. Two version formats:
//   major update  -> "450000000"            (VERSION without dots)
//   service pack  -> "4.50.000000 Service Pack 9 Hotfix 20261101-17" (ArticaGetFullVersion)
// One model (updh_model) feeds every widget; one widget = one function.
function history_start():bool{
    $page=CurrentPageName();
    echo updh_style()."<div id='artica-updates-history' class='updh' aria-live='polite'></div>"
        ."<script>LoadAjax('artica-updates-history','$page?history=yes');</script>".updh_js($page);
    return true;
}

function updh_is_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}

// Mark every entry as read. Admin only; refreshes the top-bar badge and the tab.
function history_seen_all():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    if(!updh_is_admin()){$tpl=new template_admin();$tpl->js_no_privileges();return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
    $q->QUERY_SQL("UPDATE history SET asseen=1 WHERE asseen=0");
    admin_tracks("Marked every Artica update of the history as read");
    echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');\nLoadAjax('artica-updates-history','$page?history=yes');";
    return true;
}

// Mark one entry as read. Echoes nothing on success, the error otherwise.
function updh_seen_save():bool{
    $tpl=new template_admin();
    if(!updh_is_admin()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["updh-seen"]);
    if($ID<1){return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
    $q->QUERY_SQL_PARAMS("UPDATE history SET asseen=1 WHERE ID=?",array($ID));
    if(!$q->ok){echo $q->mysql_error;return false;}
    return true;
}

// "4.50.000000" -> "4.50" ; "450000000" -> "4.50"
function updh_short_version(string $v):string{
    if(preg_match('/^(\d+)\.(\d+)/',$v,$m)){return $m[1].".".$m[2];}
    if(preg_match('/^\d{9,}$/',$v)){ $l=strlen($v); return intval(substr($v,0,$l-8)).".".substr($v,$l-8,2); }
    return $v;
}

// Decode one history.version into type / title / subtitle.
function updh_parse(string $raw):array{
    $raw=trim($raw);
    $o=array("type"=>"version","title"=>$raw,"base"=>"","sp"=>0,"hotfix"=>"","raw"=>$raw);
    if(preg_match('/^\d{9,}$/',$raw)){
        $o["base"]=updh_short_version($raw);
        return $o;
    }
    if(preg_match('/^(\d+\.\d+(?:\.\d+)?)/',$raw,$m)){$o["base"]=updh_short_version($m[1]);}
    if(preg_match('/Service\s+Pack\s+(\d+)/i',$raw,$m)){$o["sp"]=intval($m[1]);}
    if(preg_match('/Hotfix\s+(\S+)/i',$raw,$m)){$o["hotfix"]=$m[1];}
    if($o["sp"]>0){$o["type"]="sp";}elseif($o["hotfix"]!=""){$o["type"]="hotfix";}
    return $o;
}

// Token resolved FIRST, then its placeholders replaced (a raw "{tok}" holds no %s to replace).
function updh_tr(string $tok,array $repl=array()):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body("{".$tok."}");
    foreach($repl as $k=>$v){ $t=str_replace($k,$v,$t); }
    return $t;
}

function updh_ago(int $ts):string{
    $d=time()-$ts;
    if($ts<=0){return "";}
    if($d<60){return "{updh_ago_now}";}
    if($d<3600){return updh_tr("updh_ago_min",array("%s"=>strval(intval($d/60))));}
    if($d<86400){return updh_tr("updh_ago_hour",array("%s"=>strval(intval($d/3600))));}
    if($d<86400*45){$n=intval($d/86400);return $n==1?"{updh_ago_day1}":updh_tr("updh_ago_day",array("%s"=>strval($n)));}
    if($d<86400*365){$n=intval($d/(86400*30));return $n==1?"{updh_ago_month1}":updh_tr("updh_ago_month",array("%s"=>strval($n)));}
    $n=intval($d/(86400*365));
    return $n==1?"{updh_ago_year1}":updh_tr("updh_ago_year",array("%s"=>strval($n)));
}

function updh_model():array{
    $HIST_MAX=500;
    $m=array("rows"=>array(),"total"=>0,"unseen"=>0,"max"=>$HIST_MAX,"admin"=>updh_is_admin());
    $q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
    if(!$q->TABLE_EXISTS("history")){return $m;}
    $cnt=$q->QUERY_SQL("SELECT COUNT(*) as tcount, SUM(CASE WHEN asseen=1 THEN 0 ELSE 1 END) as unseen FROM history");
    if(is_array($cnt) && isset($cnt[0])){$m["total"]=intval($cnt[0]["tcount"]);$m["unseen"]=intval($cnt[0]["unseen"]);}
    $results=$q->QUERY_SQL("SELECT ID,version,updated,asseen FROM history ORDER BY updated DESC LIMIT $HIST_MAX");
    if(!is_array($results)){$results=array();}
    foreach($results as $l){
        if(!is_array($l)){continue;}
        $p=updh_parse((string)($l["version"] ?? ""));
        $p["id"]=intval($l["ID"] ?? 0);
        $p["ts"]=intval($l["updated"] ?? 0);
        $p["seen"]=intval($l["asseen"] ?? 0)==1;
        $m["rows"][]=$p;
    }
    return $m;
}

function history():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=updh_model();
    $h=array();
    $h[]=updh_banner();
    if($m["total"]==0){
        $h[]=updh_empty();
    }else{
        $h[]="<div class='updh-kpis'>".updh_kpi_latest($m).updh_kpi_unseen($m).updh_kpi_total($m)."</div>";
        $h[]=updh_timeline($m);
    }

    $btns=array();
    if($m["admin"] && $m["unseen"]>0){
        $btns[]=array("Loadjs('$page?seen-all=yes');","fas fa-check-double","{mark_all_as_read}");
    }
    $btns[]=array("UpdhReload();",ico_refresh,"{refresh}");
    $TINY_ARRAY["TITLE"]="{update_artica} {history}";
    $TINY_ARRAY["ICO"]=ico_clock_desk;
    $TINY_ARRAY["EXPL"]="{updh_header_explain}<div id='header-update-page'></div>";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo "<div class='updh-in'>".$tpl->_ENGINE_parse_body(implode("\n",$h))."</div>"
        ."<script>NoSpinner();$jstiny if(document.getElementById('header-update-page')){document.getElementById('header-update-page').innerHTML='';} if(window.UpdhInit){UpdhInit();}</script>";
    return true;
}

// -- Widgets -------------------------------------------------------------------

function updh_banner():string{
    $chips=array(array("fas fa-circle-check","{updh_chip_success}"),array("fas fa-clock","{updh_chip_auto}"),
        array("fas fa-bell","{updh_chip_badge}"));
    $c=array();
    foreach($chips as $ch){ $c[]="<span class='updh-chip'><i class='{$ch[0]}' aria-hidden='true'></i>{$ch[1]}</span>"; }
    return "<section class='updh-banner'><div class='updh-banner-ico' aria-hidden='true'><i class='fas fa-clock-rotate-left'></i></div>
<div class='updh-banner-txt'><h2 class='updh-banner-t'>{updh_banner_title}</h2><p class='updh-banner-p'>{updh_banner_text}</p>
<div class='updh-chips'>".implode("",$c)."</div></div></section>";
}

function updh_empty():string{
    return "<section class='updh-card updh-empty'><div class='updh-empty-ico' aria-hidden='true'><i class='fas fa-clock-rotate-left'></i></div>
<h3 class='updh-card-t'>{updh_empty_title}</h3><p class='updh-muted'>{updh_empty_text}</p>
<button type='button' class='updh-btn' onclick='UpdhGoArtica()'><i class='fas fa-arrow-left' aria-hidden='true'></i>{updh_empty_btn}</button></section>";
}

function updh_kpi($icon,$label,$value,$sub,$extra=""):string{
    return "<div class='updh-card updh-kpi'><div class='updh-kpi-head'><span>$label</span><span class='updh-kpi-ico' aria-hidden='true'><i class='$icon'></i></span></div>
<div class='updh-kpi-v$extra'>$value</div><div class='updh-kpi-s'>$sub</div></div>";
}
function updh_kpi_latest(array $m):string{
    $r=$m["rows"][0];
    $tpl=new template_admin();
    $sub="<span title='".htmlspecialchars($tpl->time_to_date($r["ts"],true),ENT_QUOTES)."'>".updh_ago($r["ts"])."</span>";
    return updh_kpi("fas fa-cloud-arrow-down","{updh_kpi_latest}",htmlspecialchars(updh_title($r)),$sub," updh-kpi-name");
}
function updh_kpi_unseen(array $m):string{
    if($m["unseen"]==0){ return updh_kpi("fas fa-bell","{updh_kpi_unseen}","0","{updh_kpi_unseen_zero}"," updh-kpi-zero"); }
    return updh_kpi("fas fa-bell","{updh_kpi_unseen}",$m["unseen"],"{updh_kpi_unseen_sub}"," updh-kpi-accent");
}
function updh_kpi_total(array $m):string{
    $tpl=new template_admin();
    return updh_kpi("fas fa-list","{updh_kpi_total}",$tpl->FormatNumber($m["total"]),"{updh_kpi_total_sub}");
}

function updh_title(array $r):string{
    if($r["type"]=="sp"){return "Service Pack ".$r["sp"];}
    if($r["type"]=="hotfix"){return "Hotfix ".$r["hotfix"];}
    if($r["base"]!=""){return "Version ".$r["base"];}
    return $r["raw"];
}

function updh_timeline(array $m):string{
    $tpl=new template_admin();
    $h=array();
    $h[]="<section class='updh-card updh-tl'><div class='updh-tl-head'><div><h3 class='updh-card-t'>{updh_tl_title} <span class='updh-tag'>{updh_newest_first}</span></h3>";
    $h[]="<p class='updh-muted'>{updh_tl_text}</p></div>";
    $h[]="<div class='updh-tl-tools'>".updh_filter($m)."<span class='updh-count' id='updh-count'></span></div></div>";
    $h[]="<div id='updh-list'>";
    $month="";
    foreach($m["rows"] as $r){
        $key=date("Y-m",$r["ts"]);
        if($key!=$month){
            $month=$key;
            $h[]="<h4 class='updh-month' data-month='$key'>".updh_month_label($r["ts"])."</h4>";
        }
        $h[]=updh_row($r,$m["admin"],$tpl);
    }
    $h[]="</div>";
    $h[]="<div class='updh-nomatch' id='updh-nomatch' style='display:none'>{updh_nomatch} <button type='button' class='updh-link' onclick=\"UpdhFilter('all')\">{updh_show_all}</button></div>";
    $foot="";
    if($m["total"]>count($m["rows"])){
        $foot=updh_tr("updh_capped",array("%a"=>$tpl->FormatNumber(count($m["rows"])),"%b"=>$tpl->FormatNumber($m["total"])));
    }
    $h[]="<div class='updh-foot'><span>$foot</span><nav class='updh-pg' id='updh-pager' aria-label='{updh_pages}'></nav></div>";
    $h[]="</section>";
    return implode("",$h);
}

function updh_month_label(int $ts):string{
    $months=array(1=>"{January}",2=>"{February}",3=>"{March}",4=>"{April}",5=>"{May}",6=>"{June}",7=>"{July}",
        8=>"{August}",9=>"{September}",10=>"{October}",11=>"{November}",12=>"{December}");
    return $months[intval(date("n",$ts))]." ".date("Y",$ts);
}

function updh_filter(array $m):string{
    $badge=$m["unseen"]>0?" <span class='updh-fbadge'>".$m["unseen"]."</span>":"";
    $opts=array("all"=>"{updh_f_all}","new"=>"{updh_f_new}$badge","sp"=>"{updh_f_sp}");
    $h="<div class='updh-seg' role='radiogroup' aria-label='{updh_filter}'>";
    foreach($opts as $k=>$lbl){
        $on=$k=="all"?"true":"false";
        $h.="<button type='button' role='radio' aria-checked='$on' data-f='$k' onclick=\"UpdhFilter('$k')\">$lbl</button>";
    }
    return $h."</div>";
}

function updh_row(array $r,bool $admin,$tpl):string{
    $icons=array("version"=>"fas fa-rocket","sp"=>"fas fa-layer-group","hotfix"=>"fas fa-wrench");
    $pills=array("version"=>"{updh_t_version}","sp"=>"{updh_t_sp}","hotfix"=>"{updh_t_hotfix}");
    $t=$r["type"];
    $sub=array();
    if($t=="version"){ if($r["base"]!=""){$sub[]="{updh_sub_version}";} }
    else{
        if($r["base"]!=""){$sub[]="Artica ".htmlspecialchars($r["base"]);}
        if($t=="sp" && $r["hotfix"]!=""){$sub[]="Hotfix ".htmlspecialchars($r["hotfix"]);}
        if($t=="sp" && $r["hotfix"]==""){$sub[]="{updh_sub_sp}";}
        if($t=="hotfix"){array_unshift($sub,"{updh_sub_hotfix}");}
    }
    $raw=htmlspecialchars($r["raw"]);
    $date=$r["ts"]>0?date("Y-m-d",$r["ts"])." &middot; ".date("H:i",$r["ts"]):"&mdash;";
    $exact=htmlspecialchars($tpl->time_to_date($r["ts"],true),ENT_QUOTES);
    if($r["seen"]){
        $state="<span class='updh-seen'><i class='fas fa-check' aria-hidden='true'></i>{updh_reviewed}</span>";
        $new="";
    }else{
        $new="<span class='updh-new'><span class='updh-dot' aria-hidden='true'></span>{updh_new}</span>";
        $state=$admin
            ?"<button type='button' class='updh-mark' onclick='UpdhSeen({$r["id"]},this)'><i class='fas fa-check' aria-hidden='true'></i>{updh_mark}</button>"
            :"<span class='updh-seen updh-unseen'>{updh_not_reviewed}</span>";
    }
    $cls=$r["seen"]?"":" updh-row-new";
    return "<div class='updh-row$cls' data-type='$t' data-new='".($r["seen"]?"0":"1")."' data-month='".date("Y-m",$r["ts"])."'>
<span class='updh-badge updh-b-$t' aria-hidden='true'><i class='{$icons[$t]}'></i></span>
<div class='updh-main'><div class='updh-title'><b>".htmlspecialchars(updh_title($r))."</b><span class='updh-pill updh-p-$t'>{$pills[$t]}</span>$new</div>
<div class='updh-sub'>".implode(" &middot; ",$sub)."<span class='updh-raw' title='$raw'>$raw</span></div></div>
<div class='updh-when'><span title='$exact'>$date</span><small>".updh_ago($r["ts"])."</small></div>
<div class='updh-state'>$state</div></div>";
}

// -- JS / CSS -----------------------------------------------------------------

function updh_js(string $page):string{
    $tpl=new template_admin();
    $of=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{updh_showing_of}"),ENT_QUOTES,"UTF-8"));
    $rev=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{updh_reviewed}"),ENT_QUOTES,"UTF-8"));
    $prev=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{updh_prev}"),ENT_QUOTES,"UTF-8"));
    $next=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{updh_next}"),ENT_QUOTES,"UTF-8"));
    return "<script>
window.UpdhState={f:'all',p:1,per:30};
function UpdhReload(){ LoadAjax('artica-updates-history','$page?history=yes'); }
function UpdhGoArtica(){
    var c=document.getElementById('artica-updates-history'); var t=c?jQuery(c).closest('.tabs-container'):null;
    var a=t&&t.length?t.find('.nav-tabs a').first():null; if(a&&a.length){ a.trigger('click'); }
}
function UpdhFilter(f){ UpdhState.f=f; UpdhState.p=1; UpdhRender(); }
function UpdhPage(p){ UpdhState.p=p; UpdhRender(); var l=document.getElementById('updh-list'); if(l&&l.scrollIntoView){l.scrollIntoView({block:'nearest'});} }
function UpdhMatch(r){ var f=UpdhState.f; if(f=='new'){return r.getAttribute('data-new')=='1';} if(f=='sp'){return r.getAttribute('data-type')!='version';} return true; }
function UpdhRender(){
    var list=document.getElementById('updh-list'); if(!list){return;}
    var rows=list.querySelectorAll('.updh-row'), vis=[], i;
    for(i=0;i<rows.length;i++){ if(UpdhMatch(rows[i])){vis.push(rows[i]);} rows[i].style.display='none'; }
    var pages=Math.max(1,Math.ceil(vis.length/UpdhState.per)); if(UpdhState.p>pages){UpdhState.p=pages;}
    var a=(UpdhState.p-1)*UpdhState.per, b=Math.min(vis.length,a+UpdhState.per), months={};
    for(i=a;i<b;i++){ vis[i].style.display=''; months[vis[i].getAttribute('data-month')]=1; }
    var hs=list.querySelectorAll('.updh-month'); for(i=0;i<hs.length;i++){ hs[i].style.display=months[hs[i].getAttribute('data-month')]?'':'none'; }
    var segs=document.querySelectorAll('.updh-seg button'); for(i=0;i<segs.length;i++){ segs[i].setAttribute('aria-checked',segs[i].getAttribute('data-f')==UpdhState.f?'true':'false'); }
    var c=document.getElementById('updh-count'); if(c){ c.textContent=$of.replace('%a',vis.length?(b-a):0).replace('%b',vis.length); }
    var nm=document.getElementById('updh-nomatch'); if(nm){ nm.style.display=vis.length?'none':''; }
    var pg=document.getElementById('updh-pager'); if(!pg){return;}
    var h=''; if(pages>1){
        h+='<button type=\"button\" '+(UpdhState.p==1?'disabled':'onclick=\"UpdhPage('+(UpdhState.p-1)+')\"')+'>'+$prev+'</button>';
        for(i=1;i<=pages;i++){ if(i==1||i==pages||Math.abs(i-UpdhState.p)<=1){ h+='<button type=\"button\" class=\"'+(i==UpdhState.p?'on':'')+'\" '+(i==UpdhState.p?'aria-current=\"page\"':'')+' onclick=\"UpdhPage('+i+')\">'+i+'</button>'; } else if(Math.abs(i-UpdhState.p)==2){ h+='<span>&hellip;</span>'; } }
        h+='<button type=\"button\" '+(UpdhState.p==pages?'disabled':'onclick=\"UpdhPage('+(UpdhState.p+1)+')\"')+'>'+$next+'</button>';
    }
    pg.innerHTML=h;
}
function UpdhInit(){ UpdhState.p=1; UpdhRender(); }
function UpdhSeen(id,btn){
    btn.disabled=true;
    $.post('$page',{'updh-seen':id}).done(function(r){
        r=String(r||'').trim(); if(r.length>0){ btn.disabled=false; if(window.swal){swal({title:'Oops...',text:r,type:'warning',html:true});}else{alert(r);} return; }
        var row=btn.closest('.updh-row'); if(row){ row.setAttribute('data-new','0'); row.classList.remove('updh-row-new'); var n=row.querySelector('.updh-new'); if(n){n.parentNode.removeChild(n);} }
        btn.outerHTML='<span class=\"updh-seen\">'+$rev+'</span>';
        if(window.LoadAjaxSilent){ LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes'); }
    }).fail(function(){ btn.disabled=false; });
}
</script>";
}

function updh_style():string{
    if(isset($GLOBALS["UPDH_STYLE"])){return "";}
    $GLOBALS["UPDH_STYLE"]=true;
    $accent="#005447";$soft="#e6eeed";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.updh{color:#0f172a;font-size:14px;margin-top:10px}
.updh *{box-sizing:border-box}
.updh-in{display:flex;flex-direction:column;gap:20px}
.updh h2,.updh h3,.updh h4,.updh p{margin:0}
.updh-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.updh-card-t{font-size:17px;font-weight:600;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.updh-muted{color:#475569;font-size:13px;line-height:1.5;margin-top:4px!important}
.updh-banner{display:flex;gap:18px;align-items:flex-start;background:$soft;border:1px solid #bfd4d1;border-radius:16px;padding:24px}
.updh-banner-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px}
.updh-banner-txt{flex:1;min-width:0}
.updh-banner-t{font-size:20px;font-weight:600;line-height:1.3;color:$accent}
.updh-banner-p{font-size:14px;line-height:1.55;color:#334155;margin-top:6px!important;max-width:820px}
.updh-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.updh-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#334155;background:#fff;border:1px solid #bfd4d1;border-radius:999px;padding:4px 12px}
.updh-chip i,.updh-chip svg{color:$accent}
.updh-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
.updh-kpi{padding:18px 20px}
.updh-kpi-head{display:flex;justify-content:space-between;align-items:center;font-size:14px;color:#475569;font-weight:500}
.updh-kpi-ico{width:34px;height:34px;border-radius:9px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:15px}
.updh-kpi-v{font-size:28px;font-weight:600;line-height:34px;margin-top:6px;font-variant-numeric:tabular-nums;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.updh-kpi-accent{color:$accent}
.updh-kpi-zero{color:#94a3b8}
.updh-kpi-s{font-size:13px;color:#64748b;margin-top:2px}
.updh-kpi-s span[title]{border-bottom:1px dotted #94a3b8;cursor:help}
.updh-tl{overflow:hidden}
.updh-tl-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;padding:20px 24px 16px}
.updh-tag{font-size:13px;font-weight:500;color:#475569;background:#f1f5f9;border-radius:6px;padding:2px 8px}
.updh-tl-tools{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.updh-seg{display:inline-flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:3px;gap:2px}
.updh-seg button{border:0;background:none;border-radius:8px;padding:6px 14px;font-size:13px;font-weight:500;color:#334155;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.updh-seg button[aria-checked=true]{background:$accent;color:#fff}
.updh-fbadge{display:inline-block;min-width:20px;text-align:center;font-size:13px;font-weight:600;border-radius:999px;background:#dcfce7;color:#166534;padding:0 6px}
.updh-count{font-size:13px;color:#475569;font-variant-numeric:tabular-nums}
.updh-month{font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#475569;background:#f8fafc;border-top:1px solid #eef2f6;border-bottom:1px solid #eef2f6;padding:10px 24px}
.updh-row{display:flex;align-items:center;gap:16px;padding:12px 24px;border-bottom:1px solid #f1f5f9;border-left:4px solid transparent;min-height:64px}
.updh-row-new{border-left-color:$accent;background:#fbfdfc}
.updh-badge{flex:0 0 32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px}
.updh-b-version{background:$accent}.updh-b-sp{background:#0e7490}.updh-b-hotfix{background:#d97706}
.updh-main{flex:1;min-width:0}
.updh-title{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.updh-title b{font-size:15px;font-weight:600}
.updh-row:not(.updh-row-new) .updh-title b{color:#334155}
.updh-pill{font-size:13px;font-weight:500;border-radius:999px;padding:1px 10px}
.updh-p-version{background:$soft;color:$accent}.updh-p-sp{background:#ecfeff;color:#0e7490}.updh-p-hotfix{background:#fffbeb;color:#92400e}
.updh-new{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:$accent;background:$soft;border:1px solid #bfd4d1;border-radius:999px;padding:0 10px}
.updh-dot{width:6px;height:6px;border-radius:50%;background:$accent;display:inline-block}
.updh-sub{font-size:13px;color:#64748b;margin-top:3px;display:flex;gap:6px;align-items:baseline;flex-wrap:wrap;min-width:0}
.updh-raw{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#64748b;margin-left:8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:420px}
.updh-raw:before{content:'\\00b7';margin-right:8px;color:#cbd5e1}
.updh-when{flex:0 0 auto;text-align:right;font-size:13px;font-weight:500;font-variant-numeric:tabular-nums;white-space:nowrap}
.updh-when small{display:block;font-size:13px;font-weight:400;color:#64748b}
.updh-state{flex:0 0 150px;text-align:right}
.updh-mark{display:inline-flex;align-items:center;gap:6px;border:1px solid #bfd4d1;background:$soft;color:$accent;border-radius:8px;padding:5px 12px;font-size:13px;font-weight:600;cursor:pointer}
.updh-mark:hover{background:#d6e5e3}
.updh-seen{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748b}
.updh-unseen{color:$accent;font-weight:500}
.updh-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 24px;font-size:13px;color:#64748b}
.updh-pg{display:flex;gap:6px;align-items:center}
.updh-pg button{min-width:34px;height:34px;border:1px solid #e2e8f0;background:#fff;border-radius:8px;font-size:13px;color:#334155;cursor:pointer;padding:0 10px}
.updh-pg button.on{background:$accent;border-color:$accent;color:#fff}
.updh-pg button[disabled]{color:#94a3b8;cursor:not-allowed}
.updh-nomatch{padding:28px 24px;text-align:center;font-size:14px;color:#475569}
.updh-link{background:none;border:0;color:$accent;font-weight:600;font-size:13px;cursor:pointer}
.updh-btn{display:inline-flex;align-items:center;gap:8px;background:$accent;color:#fff;border:0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;margin-top:14px}
.updh-btn:hover{background:#003d33}
.updh-empty{text-align:center;padding:40px 24px;display:flex;flex-direction:column;align-items:center}
.updh-empty .updh-card-t{justify-content:center}
.updh-empty-ico{width:64px;height:64px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:14px}
.updh-empty .updh-muted{max-width:520px}
.updh-seg button:focus-visible,.updh-mark:focus-visible,.updh-pg button:focus-visible,.updh-btn:focus-visible,.updh-link:focus-visible{outline:2px solid $accent;outline-offset:2px}
@media(max-width:1200px){.updh-raw{display:none}.updh-state{flex-basis:auto}}
@media (prefers-reduced-motion:no-preference){.updh-row{transition:background .2s,border-color .2s}}
</style>";
}

function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='artica-status-update' style='margin-top:10px'></div>
    <script>LoadAjax('artica-status-update','$page?table=yes');</script>";
    return true;
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // L'ancienne mise en page etait un <table> dont la SECONDE ligne n'etait ni
    // sa cellule ni elle-meme refermee: <tr><td>..</td></tr><tr><td>..</table>.
    $html=array();
    $html[]="<div id='status-left'></div>";
    $html[]="<div id='status-right' style='margin-top:10px'></div>";
    $script="<script>"
        ."LoadAjax('status-left','$page?status-left=yes');"
        ."LoadAjax('status-right','$page?status-rigth=yes');"
        ."</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;
}

function widget_current_version():array{
    // Les sept branches de decision (official/nightly x version/service pack)
    // sont conservees a l'identique. Seule leur charge utile change: elles
    // rendaient un widget_h() tout fait, elles renvoient desormais le libelle
    // du bouton de mise a jour, que upd_hero_installed() place dans la carte.
    $tpl=new template_admin();
    $ArticaUpdateServicesPacks=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateServicesPacks"));
    if(!is_array($ArticaUpdateServicesPacks)){$ArticaUpdateServicesPacks=array();}
    $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
    if(!is_array($ArticaUpdateRepos)){$ArticaUpdateRepos=array();}
    $ArticaAutoUpateOfficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateOfficial"));
    $ArticaAutoUpateNightly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));
    $ArticaDisablePatchs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));
    $NightlylServicePackVersion="";
    $CURVER=@file_get_contents("VERSION");
    $CURVER_KEY=str_replace(".", "", $CURVER);
    // $OfficialServicePackVersion n'etait affectee que DANS le isset() plus bas,
    // puis comparee inconditionnellement: avertissement de variable indefinie en
    // PHP 8 des que le depot ne publie pas de service pack.
    $OfficialServicePackVersion=0;
    $CURPATCH= $GLOBALS["CLASS_SOCKETS"]->SPVersion();
    VERBOSE("Current version = $CURVER Service Pack [$CURPATCH]",__LINE__);

    $key_offical=update_find_latest($ArticaUpdateRepos);
    VERBOSE("[WIDGET]: Get the Official version = $key_offical",__LINE__);
    $OFFICIALS=isset($ArticaUpdateRepos["OFF"])&&is_array($ArticaUpdateRepos["OFF"])?$ArticaUpdateRepos["OFF"]:array();
    $Lastest=(string)(isset($OFFICIALS[$key_offical]["VERSION"])?$OFFICIALS[$key_offical]["VERSION"]:"");

    if($ArticaAutoUpateOfficial==1) {
        VERBOSE("ArticaAutoUpateOfficial = $ArticaAutoUpateOfficial Get Official updates", __LINE__);

        if ($key_offical > intval($CURVER_KEY)) {
            VERBOSE("<H1>OFFICIAL</H1> $key_offical > $CURVER_KEY", __LINE__);
            return array("{update_now} v$Lastest", true);
        }
        if ($key_offical == intval($CURVER_KEY)) {
            VERBOSE("[WIDGET]: Check Service Pack for $Lastest", __LINE__);

            if (isset($ArticaUpdateServicesPacks[$Lastest])) {
                $OfficialServicePackVersion = intval($ArticaUpdateServicesPacks[$Lastest]["VERSION"]);
            }
            if($ArticaDisablePatchs==0) {
                VERBOSE("[WIDGET]: Check Service Pack for Offical [$OfficialServicePackVersion] > [$CURPATCH]", __LINE__);
                if ($OfficialServicePackVersion > $CURPATCH) {
                    return array("{update_now} {service_pack} $OfficialServicePackVersion", true);
                }
            }
        }
    }

    if($ArticaAutoUpateNightly==1){
        VERBOSE("<H1>[NIGHTLY]</H1>: Nightly Is allowed",__LINE__);
        $key_nightly=update_find_latest_nightly($ArticaUpdateRepos);
        $NIGHTLYS=isset($ArticaUpdateRepos["NIGHT"])&&is_array($ArticaUpdateRepos["NIGHT"])?$ArticaUpdateRepos["NIGHT"]:array();
        $Lastest_nightly=(string)(isset($NIGHTLYS[$key_nightly]["VERSION"])?$NIGHTLYS[$key_nightly]["VERSION"]:"");
        if ($key_nightly > intval($CURVER_KEY)) {
            VERBOSE("[NIGHTLY] $key_nightly > $CURVER_KEY", __LINE__);
            return array("{update_now} v$Lastest_nightly", true);
        }

        if ($key_nightly == intval($CURVER_KEY)) {
            VERBOSE("[NIGHTLY] Check Service Pack for $Lastest_nightly", __LINE__);

            if (isset($ArticaUpdateServicesPacks[$Lastest_nightly])) {
                $NightlylServicePackVersion = intval($ArticaUpdateServicesPacks[$Lastest_nightly]["VERSION"]);
            }
            if($ArticaDisablePatchs==0) {
                VERBOSE("[NIGHTLY] Check Service Pack for $NightlylServicePackVersion > $CURPATCH", __LINE__);
                if ($NightlylServicePackVersion > $CURPATCH) {
                    return array("{update_now} {service_pack} $NightlylServicePackVersion", true);
                }
            }
        }
    }

    VERBOSE("Nothing to do...",__LINE__);
    return array("",false);
}

/* ===========================================================================
 *  Bibliotheque de composants .upd-* -- transposition de
 *  /home/claude/images/stitch_refonte_page_mise_jour
 *  Regle du projet : un widget = une fonction.
 * ======================================================================== */

/**
 * Feuille de style de la page. Emise UNE SEULE FOIS par requete et TOUJOURS
 * concatenee EN DEHORS de _ENGINE_parse_body(): son motif #{(.+?)}#is
 * prendrait chaque corps de regle CSS pour un {token} et declencherait une
 * recherche memcached par regle.
 */
function upd_style():string{
    if(isset($GLOBALS["UPD_CSS_DONE"])){return "";}
    $GLOBALS["UPD_CSS_DONE"]=true;
    return "<style>
.upd-wrap{color:#334155;font-size:13px;line-height:1.45}
.upd-heroes{display:flex;gap:18px;flex-wrap:wrap;margin-top:6px}
.upd-hero{position:relative;overflow:hidden;flex:1 1 380px;min-width:326px;border-radius:14px;
padding:20px 22px;color:#fff}
.upd-hero-green{background:linear-gradient(135deg,#059669 0%,#047857 55%,#065f46 100%)}
.upd-hero-amber{background:linear-gradient(135deg,#d97706 0%,#b45309 55%,#92400e 100%)}
.upd-hero-slate{background:linear-gradient(135deg,#334155 0%,#1e293b 60%,#0f172a 100%)}
.upd-hero-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
position:relative;z-index:2}
.upd-hero-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.14);
border-radius:7px;padding:5px 10px;font-size:10px;font-weight:700;letter-spacing:.09em;
text-transform:uppercase;color:#fff}
.upd-hero-right{font-size:11px;color:rgba(255,255,255,.74);text-align:right;max-width:50%}
.upd-dot{width:7px;height:7px;border-radius:50%;background:#a7f3d0;display:inline-block;flex:0 0 auto}
.upd-dot-off{background:#fca5a5}
.upd-ver{font-family:'Courier New',Courier,monospace;font-size:36px;font-weight:700;
line-height:1.1;margin-top:16px;position:relative;z-index:2;word-break:break-all}
.upd-hero-sub{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:6px;
position:relative;z-index:2}
.upd-sp{font-size:18px;font-weight:800;text-transform:uppercase}
.upd-chip{font-family:'Courier New',Courier,monospace;font-size:11px;
background:rgba(255,255,255,.15);border-radius:6px;padding:4px 9px;white-space:nowrap}
.upd-hero-foot{display:flex;align-items:center;justify-content:space-between;gap:14px;
flex-wrap:wrap;margin-top:18px;padding-top:14px;border-top:1px solid rgba(255,255,255,.17);
position:relative;z-index:2}
.upd-hero-acts{display:flex;gap:9px;flex-wrap:wrap;align-items:center}
.upd-hero-note{font-size:11px;color:rgba(255,255,255,.74);text-align:right;margin-left:auto}
.upd-hero-note b{color:#fff;font-weight:600}
.upd-bt{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:7px;
border:0;border-radius:8px;padding:9px 14px;font-size:12px;font-weight:600;cursor:pointer;
color:#fff;text-decoration:none}
.upd-bt:hover{color:#fff;text-decoration:none}
/* button_upload() pose son <input type=file> DANS le bouton: la feuille du
   theme le masque, mais on ne s'y fie pas -- sans cette regle le selecteur de
   fichier natif s'affiche en clair au milieu de la carte. */
.upd-bt input[type=file]{position:absolute;top:0;left:0;width:100%;height:100%;
opacity:0;cursor:pointer;font-size:200px}
.upd-bt-dark{background:rgba(0,0,0,.26)}
.upd-bt-dark:hover{background:rgba(0,0,0,.4)}
.upd-bt-amber{background:#c2410c}
.upd-bt-amber:hover{background:#9a3412}
.upd-bt-green{background:#059669}
.upd-bt-green:hover{background:#047857}
.upd-wm{position:absolute;right:12px;bottom:-18px;font-size:148px;color:rgba(255,255,255,.09);
z-index:1;line-height:1}
.upd-sec{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:26px 0 12px}
.upd-sec-t{font-size:12px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#475569}
.upd-sec-n{font-size:11px;color:#94a3b8;white-space:nowrap}
.upd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(420px,1fr));gap:14px}
.upd-cell{display:flex;min-width:0}
.upd-cell-w{grid-column:1/-1}
.upd-row{display:flex;align-items:center;gap:13px;background:#fff;border:1px solid #e2e8f0;
border-radius:12px;padding:14px 16px;box-shadow:0 1px 2px rgba(15,23,42,.04);width:100%}
.upd-ico{width:38px;height:38px;border-radius:9px;display:inline-flex;align-items:center;
justify-content:center;font-size:15px;background:#f1f5f9;color:#64748b;flex:0 0 auto}
.upd-ico-on{background:#ecfdf5;color:#059669}
.upd-ico-deb{background:#fef2f2;color:#dc2626;font-family:'Courier New',Courier,monospace;
font-size:11px;font-weight:700}
.upd-row-b{flex:1;min-width:0}
.upd-row-t{font-size:13px;font-weight:600;color:#1e293b}
.upd-row-s{font-size:11px;color:#94a3b8;margin-top:3px}
.upd-st{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;
letter-spacing:.05em;text-transform:uppercase;margin-top:4px}
.upd-st-on{color:#059669}
.upd-st-off{color:#94a3b8}
.upd-sdot{width:7px;height:7px;border-radius:50%;background:#cbd5e1;display:inline-block;flex:0 0 auto}
.upd-sdot-on{background:#10b981}
.upd-pen{width:30px;height:30px;border-radius:7px;display:inline-flex;align-items:center;
justify-content:center;color:#94a3b8;cursor:pointer;flex:0 0 auto;font-size:13px}
.upd-pen:hover{background:#f1f5f9;color:#475569}
.upd-lnk{color:#0f766e;cursor:pointer}
.upd-lnk:hover{text-decoration:underline}
.upd-del{color:#dc2626;cursor:pointer;font-size:11px}
.upd-del:hover{text-decoration:underline}
.upd-mono{font-family:'Courier New',Courier,monospace}
.upd-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
.upd-tile{min-width:0;background:#fff;border:1px solid #e2e8f0;
border-radius:12px;padding:14px 16px;box-shadow:0 1px 2px rgba(15,23,42,.04);
display:flex;align-items:center;gap:13px}
.upd-tile-v{font-family:'Courier New',Courier,monospace;font-size:15px;font-weight:700;color:#1e293b}
.upd-warn{color:#b45309}
/* Tuile MISE EN AVANT: un correctif disponible n'est pas une information de
   plus, c'est une action a faire. Les tuiles LTS et Nightly sont documentaires,
   celle-ci ne l'est pas -- elle prend la largeur de l'encart, l'ambre de la
   page (meme teinte que .upd-hero-amber et .upd-warn) et un vrai bouton. */
.upd-tile-hot{grid-column:1/-1;background:#fffbeb;border-color:#fcd34d;
box-shadow:0 2px 12px -3px rgba(180,83,9,.28)}
.upd-tile-hot .upd-ico{background:#fef3c7;color:#b45309}
.upd-tile-hot .upd-row-t{color:#7c2d12}
.upd-tile-hot .upd-tile-v{color:#92400e}
.upd-tile-hot .upd-row-s{color:#a16207}
.upd-tile-hot .upd-bt{flex:0 0 auto}
/* Pastille du BANDEAU: solide et non translucide comme .upd-chip, sinon elle
   se fondrait dans le degrade vert -- or elle annonce le seul element du
   bandeau sur lequel il y a quelque chose a faire. */
/* Ambre CLAIR sur texte brun, et non l'inverse: l'ambre fonce (#b45309) a une
   luminance trop proche du vert du bandeau -- mesure 1,33:1, la pastille s'y
   fondait. En clair le rapport passe a 2,7:1 contre le fond, tout en gardant
   7:1 pour le texte. Meme jaune que le bord de la tuile de l'encart. */
.upd-hot-pill{display:inline-flex;align-items:center;gap:7px;background:#fcd34d;color:#7c2d12;
border-radius:999px;padding:4px 11px;font-size:11px;font-weight:700;letter-spacing:.06em;
text-transform:uppercase;box-shadow:0 1px 7px rgba(0,0,0,.22);cursor:pointer;white-space:nowrap}
.upd-hot-pill:hover{background:#fbbf24}
/* Sur pastille claire, l'onde doit etre sombre: l'onde blanche est invisible. */
.upd-hot-pill .upd-pulse{background:#7c2d12;animation-name:updpulsedark}
@keyframes updpulsedark{0%{box-shadow:0 0 0 0 rgba(124,45,18,.55)}
70%{box-shadow:0 0 0 7px rgba(124,45,18,0)}100%{box-shadow:0 0 0 0 rgba(124,45,18,0)}}
.upd-new{display:inline-flex;align-items:center;gap:6px;margin-left:9px;vertical-align:middle;
background:#b45309;color:#fff;border-radius:999px;padding:2px 9px;font-size:10px;font-weight:700;
letter-spacing:.08em;text-transform:uppercase}
.upd-pulse{width:6px;height:6px;border-radius:50%;background:#fff;flex:0 0 auto;
animation:updpulse 1.9s ease-out infinite}
@keyframes updpulse{0%{box-shadow:0 0 0 0 rgba(255,255,255,.75)}
70%{box-shadow:0 0 0 7px rgba(255,255,255,0)}100%{box-shadow:0 0 0 0 rgba(255,255,255,0)}}
/* Un utilisateur qui a demande moins d'animations ne voit qu'une pastille fixe. */
@media(prefers-reduced-motion:reduce){.upd-pulse{animation:none}}
</style>";
}

/**
 * Accumulateur du JavaScript des composants: button_upload() rend son HTML et
 * son script colles, et ce script ne doit pas traverser _ENGINE_parse_body()
 * -- son motif lit chaque bloc { } comme un {token}.
 */
function upd_js_push($js){
    if(!isset($GLOBALS["UPD_JS"])){$GLOBALS["UPD_JS"]=array();}
    $GLOBALS["UPD_JS"][]=$js;
}

/** Rend puis vide l'accumulateur. A appeler APRES _ENGINE_parse_body(). */
function upd_js_compile():string{
    if(!isset($GLOBALS["UPD_JS"])){return "";}
    $js=$GLOBALS["UPD_JS"];
    $GLOBALS["UPD_JS"]=array();
    if(count($js)==0){return "";}
    return "<script>".@implode("\n",$js)."</script>";
}

/** Detache les <script> d'un fragment et les range dans l'accumulateur. */
function upd_strip_scripts($html):string{
    // Groupe 1 = le CORPS du script. Pousser $m[0] (balise comprise) produit un
    // <script> imbrique dans le <script> emis par upd_js_compile(), et jQuery
    // leve alors "Unexpected token '<'" en evaluant le bloc.
    $out=preg_replace_callback('#<script\b[^>]*>(.*?)</script>#is',
        function($m){upd_js_push($m[1]);return "";},(string)$html);
    return (string)$out;
}

/** Puce monospace d'une carte hero. */
function upd_chip($text):string{
    return "<span class='upd-chip'>$text</span>";
}

/** Bouton d'une carte hero. $tone: dark | amber | green */
function upd_hero_btn($label,$ico,$js,$tone="dark"):string{
    $ico=($ico!="")?"<i class='$ico'></i>":"";
    return "<a class='upd-bt upd-bt-$tone' OnClick=\"$js\">$ico$label</a>";
}

/** Gabarit commun des deux cartes hero. */
function upd_hero(array $o):string{
    $tone=isset($o["tone"])?$o["tone"]:"green";
    $dot=isset($o["dot_off"])&&$o["dot_off"]?" upd-dot-off":"";
    $h=array();
    $h[]="<div class='upd-hero upd-hero-$tone'>";
    $h[]="<div class='upd-hero-top'>";
    $h[]="<span class='upd-hero-badge'><span class='upd-dot$dot'></span>".$o["badge"]."</span>";
    if(strlen((string)$o["right"])>0){$h[]="<span class='upd-hero-right'>".$o["right"]."</span>";}
    $h[]="</div>";
    $h[]="<div class='upd-ver'>".$o["version"]."</div>";
    $h[]="<div class='upd-hero-sub'>".$o["sub"].$o["chip"]."</div>";
    $h[]="<div class='upd-hero-foot'>";
    $h[]="<div class='upd-hero-acts'>".$o["actions"]."</div>";
    $h[]="<div class='upd-hero-note'>".$o["note"]."</div>";
    $h[]="</div>";
    $h[]="<i class='".$o["watermark"]." upd-wm'></i>";
    $h[]="</div>";
    return @implode("",$h);
}

/**
 * Etat d'acces au depot. NoInternetAccess est le reglage que le daemon lui-meme
 * consulte avant d'interroger les serveurs de mise a jour (maintenance.go,
 * UpdateIndex): c'est donc la seule source qui dise vraiment si la box a le
 * droit de sortir.
 */
function upd_online_state():array{
    $blocked=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));
    if($blocked==1){return array(false,"{upd_internet_disabled}");}
    return array(true,"{online}");
}

/**
 * Date de la derniere interrogation du depot. Le daemon n'horodate rien, mais
 * il ecrit refresh.index.progress a chaque passage: le mtime de ce fichier EST
 * la date de la derniere verification. Absent = jamais verifie.
 */
function upd_last_check():string{
    $file=PROGRESS_DIR."/refresh.index.progress";
    if(!is_file($file)){return "{never}";}
    $time=@filemtime($file);
    if($time===false OR $time<=0){return "{never}";}
    $tpl=new template_admin();
    return "<b>".htmlspecialchars($tpl->time_to_date($time,true))."</b>";
}

/**
 * Le correctif disponible, tel qu'il s'annonce DANS le bandeau: une pastille a
 * cote de la version installee, et un bouton d'action. Les deux ouvrent la meme
 * fenetre que la tuile de l'encart "versions disponibles" (upd_tile_rc).
 *
 * Rend un tableau VIDE quand le depot ne publie rien de plus recent que la
 * version installee: le bandeau ne change alors pas d'un pouce, et rien ne
 * clignote pour rien.
 */
function upd_hero_hotfix(array $repos):array{
    return array();
    $page=CurrentPageName();
    $array=update_find_hotfix_unstable($repos);
    if(count($array)==0){return array();}
    $VERSION=(string)(isset($array["VERSION"])?$array["VERSION"]:"");
    if(trim($VERSION)===""){return array();}
    $js="Loadjs('$page?update-hotfix-js=yes')";
    return array(
        "PILL"=>"<span class='upd-hot-pill' OnClick=\"$js\">"
               ."<span class='upd-pulse'></span>{new} HOTFIX v.".htmlspecialchars($VERSION)."</span>",
        "ACTION"=>upd_hero_btn("HotFix RC v.".htmlspecialchars($VERSION),"fas fa-flask",$js,"amber"));
}

/** Carte hero gauche: la version installee. */
function upd_hero_installed($MustUpdate,$updateLabel,$users,array $repos=array()):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $CURVER=trim((string)@file_get_contents("VERSION"));
    $CURPATCH=intval($GLOBALS["CLASS_SOCKETS"]->SPVersion());
    // Ligne 2 du fichier: le paquet hotfix y ecrit sa version, et
    // exec.artica.update.manu.php::CurrentHotFix() la relit au regex sur le
    // source brut -- d'ou l'interdiction de reformater cette ligne.
    // (Le jumeau unix::GetHotFixVersion() de framework/ n'a aucun appelant et
    // sa regex #HOTFIX.*?="([0-9]+)"# ne matche de toute facon pas une valeur
    // a tiret comme 20260908-02: verifie sur le fichier d'origine.)
    $HOTFIX=trim((string)$GLOBALS["HOTFIX"]);

    list($online,$stateText)=upd_online_state();

    $chip="";
    if($HOTFIX!==""){$chip=upd_chip("Hotfix ".htmlspecialchars($HOTFIX));}
    // La pastille du correctif DISPONIBLE vient apres celle du correctif
    // INSTALLE: les deux se lisent alors comme "j'ai ceci, il existe cela".
    $offer=upd_hero_hotfix($repos);
    if(isset($offer["PILL"])){$chip.=$offer["PILL"];}
    $sub="";
    if($CURPATCH>0){$sub="<span class='upd-sp'>{service_pack} $CURPATCH</span>";}

    $acts=array();
    if($MustUpdate){
        $acts[]=upd_hero_btn($updateLabel,"fas fa-cloud-arrow-down",
            "Loadjs('$page?perform-update=yes')","amber");
    }
    // Apres la mise a jour complete quand il y en a une (elle emporte le
    // correctif), mais avant l'envoi manuel, qui n'est pas une offre du depot.
    if(isset($offer["ACTION"])){$acts[]=$offer["ACTION"];}
    if($users->AsSystemAdministrator){
        $acts[]=upd_strip_scripts($tpl->button_upload("{manual_update}",$page,"upd-bt upd-bt-dark"));
    }

    return upd_hero(array(
        "tone"=>$MustUpdate?"amber":"green",
        "dot_off"=>!$online,
        "badge"=>"{upd_installed_version}",
        "right"=>$stateText,
        "version"=>htmlspecialchars($CURVER),
        "sub"=>$sub,
        "chip"=>$chip,
        "actions"=>@implode("",$acts),
        "note"=>"{upd_last_check}<br>".upd_last_check(),
        "watermark"=>"fas fa-circle-info"));
}

/** Carte hero droite: le canal officiel publie par le depot. */
function upd_hero_channel(array $repos,array $sps,$users):string{
    $page=CurrentPageName();
    $key=update_find_latest($repos);
    $OFF=isset($repos["OFF"])&&is_array($repos["OFF"])?$repos["OFF"]:array();
    $Lastest=(string)(isset($OFF[$key]["VERSION"])?$OFF[$key]["VERSION"]:"");
    $URI=(string)(isset($OFF[$key]["URL"])?$OFF[$key]["URL"]:"");
    $MD5=(string)(isset($OFF[$key]["MD5"])?$OFF[$key]["MD5"]:"");

    $spVersion=0;$spURI="";
    if(isset($sps[$Lastest])){
        $spVersion=intval(isset($sps[$Lastest]["VERSION"])?$sps[$Lastest]["VERSION"]:0);
        $spURI=(string)(isset($sps[$Lastest]["URI"])?$sps[$Lastest]["URI"]:"");
    }

    $sub="";
    if($spVersion>0){$sub="<span class='upd-sp'>{service_pack} $spVersion</span>";}

    // La puce "version de repli" de la maquette ne s'affiche que si un point de
    // retour existe reellement, et nomme celui-la.
    $chip="";
    $rollback=upd_rollback_points();
    if(count($rollback)>0){
        $last=end($rollback);
        $chip=upd_chip("{upd_rollback_point} {service_pack} ".intval($last));
    }

    $acts=array();
    if($users->AsSystemAdministrator){
        if($spURI!==""){
            $acts[]=upd_hero_btn("{service_pack} $spVersion","fas fa-file-zipper",
                "document.location.href='/".htmlspecialchars($spURI)."'");
        }
        if($MD5!==""){
            $acts[]=upd_hero_btn("{rollback}","fas fa-backward-fast",
                "Loadjs('$page?main-rollbackjs=".htmlspecialchars($Lastest)."')","amber");
        }
    }

    // La maquette annoncait "Archive validee SHA256"; le depot ne publie qu'une
    // empreinte MD5, c'est donc elle qui est nommee.
    $note="";
    if($MD5!==""){$note="{upd_archive_checksum}<br><b class='upd-mono'>MD5 "
        .htmlspecialchars(substr($MD5,0,10))."</b>";}

    $right="";
    $host=(string)@parse_url($URI,PHP_URL_HOST);
    if($host!==""){
        $right="{upd_mirror_reference}<br><b class='upd-mono'>".htmlspecialchars($host)."</b>";
    }

    return upd_hero(array(
        "tone"=>"slate",
        "dot_off"=>false,
        "badge"=>"{upd_official_channel}",
        "right"=>$right,
        "version"=>htmlspecialchars($Lastest),
        "sub"=>$sub,
        "chip"=>$chip,
        "actions"=>@implode("",$acts),
        "note"=>$note,
        "watermark"=>"fas fa-arrow-up"));
}

/** Points de retour disponibles (numeros de service pack), tries croissants. */
function upd_rollback_points():array{
    $backuped=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("backuped_patchs"));
    if(!is_array($backuped)){return array();}
    $out=array();
    foreach($backuped as $spname=>$spsize){
        $spname=intval($spname);
        if($spname==0){continue;}
        $out[]=$spname;
    }
    sort($out);
    return $out;
}

/** En-tete de section: titre a gauche, note discrete a droite. */
function upd_section_head($title,$note=""):string{
    $right=($note!="")?"<span class='upd-sec-n'>$note</span>":"";
    return "<div class='upd-sec'><span class='upd-sec-t'>$title</span>$right</div>";
}

/** Pastille d'etat ACTIF / INACTIF. */
function upd_state($enabled):string{
    if(intval($enabled)==1){
        return "<span class='upd-st upd-st-on'><span class='upd-sdot upd-sdot-on'></span>{enabled}</span>";
    }
    return "<span class='upd-st upd-st-off'><span class='upd-sdot'></span>{disabled}</span>";
}

/** Une ligne de la grille de reglages. */
function upd_row(array $o):string{
    $ico=isset($o["ico"])?$o["ico"]:"fas fa-gear";
    $on=(isset($o["on"])&&$o["on"])?" upd-ico-on":"";
    $icoCls=isset($o["ico_class"])?$o["ico_class"]:"upd-ico$on";
    $icoHtml=isset($o["ico_text"])?$o["ico_text"]:"<i class='$ico'></i>";
    $sub=isset($o["sub"])&&$o["sub"]!=""?"<div class='upd-row-s'>".$o["sub"]."</div>":"";
    $state=isset($o["state"])?$o["state"]:"";
    $pen="";
    if(isset($o["js"])&&$o["js"]!=""){
        $pen="<span class='upd-pen' OnClick=\"".$o["js"]."\"><i class='fas fa-pen'></i></span>";
    }
    $right=isset($o["right"])?$o["right"]:"";
    $h=array();
    $cell=(isset($o["wide"])&&$o["wide"])?"upd-cell upd-cell-w":"upd-cell";
    $h[]="<div class='$cell'><div class='upd-row'>";
    $h[]="<span class='$icoCls'>$icoHtml</span>";
    $h[]="<div class='upd-row-b'><div class='upd-row-t'>".$o["title"]."</div>$sub$state</div>";
    $h[]=$right.$pen;
    $h[]="</div></div>";
    return @implode("",$h);
}

/** Grille deux colonnes des reglages. */
function upd_grid(array $rows):string{
    if(count($rows)==0){return "";}
    return "<div class='upd-grid'>".@implode("",$rows)."</div>";
}

/** Une tuile de l'encart "versions disponibles". */
function upd_tile(array $o):string{
    $ico=isset($o["ico"])?$o["ico"]:"fas fa-tag";
    $js=isset($o["js"])?$o["js"]:"";
    $cursor=($js!="")?" style='cursor:pointer'":"";
    $click=($js!="")?" OnClick=\"$js\"":"";
    $sub=isset($o["sub"])&&$o["sub"]!=""?"<div class='upd-row-s'>".$o["sub"]."</div>":"";
    $h=array();
    $h[]="<div class='upd-tile'$cursor$click>";
    $h[]="<span class='upd-ico'><i class='$ico'></i></span>";
    $h[]="<div class='upd-row-b'><div class='upd-row-t'>".$o["title"]."</div>";
    $h[]="<div class='upd-tile-v'>".$o["version"]."</div>$sub</div>";
    $h[]="</div>";
    return @implode("",$h);
}

/** Tuile Nightly: seulement si le depot publie une nightly plus recente. */
function upd_tile_nightly(array $repos,array $sps):string{
    $key=update_find_latest_nightly($repos);
    $NIGHT=isset($repos["NIGHT"])&&is_array($repos["NIGHT"])?$repos["NIGHT"]:array();
    if(!isset($NIGHT[$key]["VERSION"])){return "";}
    $version=(string)$NIGHT[$key]["VERSION"];
    $URI=(string)(isset($NIGHT[$key]["URL"])?$NIGHT[$key]["URL"]:"");
    $CURVER=trim((string)@file_get_contents("VERSION"));
    if(intval(str_replace(".","",$version))<=intval(str_replace(".","",$CURVER))){return "";}
    $sub="";
    if(isset($sps[$version]["VERSION"])){
        $sub="{service_pack} ".intval($sps[$version]["VERSION"]);
    }
    $js=($URI!=="")?"document.location.href='".htmlspecialchars(addslashes($URI),ENT_COMPAT)."'":"";
    return upd_tile(array("ico"=>"fas fa-moon","title"=>"{nightly}",
        "version"=>htmlspecialchars($version),"sub"=>$sub,"js"=>$js));
}

/** Tuile LTS. Reprend les deux cas de l'ancienne line_lts(). */
function upd_tile_lts(array $repos):string{
    $key_lts=update_find_lts($repos);
    if($key_lts==0){return "";}
    $tpl=new template_admin();
    $CURVER=trim((string)@file_get_contents("VERSION"));
    $CURVER_KEY=intval(str_replace(".","",$CURVER));
    $LTS=isset($repos["LTS"])&&is_array($repos["LTS"])?$repos["LTS"]:array();
    $Lastest=(string)(isset($LTS[$key_lts]["VERSION"])?$LTS[$key_lts]["VERSION"]:"");

    if($CURVER_KEY==$key_lts){
        $wiki="https://wiki.articatech.com/en/maintenance/upgrade-artica/hotfix-$key_lts";
        return upd_tile(array("ico"=>"fas fa-shield-halved","title"=>"LTS",
            "version"=>htmlspecialchars($Lastest),"sub"=>"{see_list_fixes}",
            "js"=>"s_PopUpFull('$wiki','1024','900');"));
    }
    if($key_lts>$CURVER_KEY){
        $NEW_LTS_TEXT=$tpl->_ENGINE_parse_body("{NEW_LTS_TEXT}");
        $NEW_LTS_TEXT=str_replace("%s",htmlspecialchars($Lastest),$NEW_LTS_TEXT);
        return upd_tile(array("ico"=>"fas fa-shield-halved","title"=>"LTS",
            "version"=>htmlspecialchars($Lastest),
            "sub"=>"<span class='upd-warn'>$NEW_LTS_TEXT</span>",
            "js"=>"Loadjs('fw.update.lts.php')"));
    }
    return "";
}

/**
 * Tuile MISE EN AVANT de l'encart "versions disponibles".
 *
 * Meme ossature que upd_tile(), mais elle annonce quelque chose a INSTALLER:
 * pastille "{new}", fond ambre, et un bouton explicite au lieu d'une tuile
 * simplement cliquable -- rien n'indiquait qu'il fallait cliquer dessus.
 */
function upd_tile_hot(array $o):string{
    $ico=isset($o["ico"])?$o["ico"]:"fas fa-bolt";
    $js=isset($o["js"])?$o["js"]:"";
    $sub=(isset($o["sub"]) && $o["sub"]!="")?"<div class='upd-row-s'>".$o["sub"]."</div>":"";
    $badge=isset($o["badge"])?$o["badge"]:"{new}";
    $action=isset($o["action"])?$o["action"]:"{install}";
    $h=array();
    $h[]="<div class='upd-tile upd-tile-hot'>";
    $h[]="<span class='upd-ico'><i class='$ico'></i></span>";
    $h[]="<div class='upd-row-b'>";
    $h[]="<div class='upd-row-t'>".$o["title"]."<span class='upd-new'><span class='upd-pulse'></span>$badge</span></div>";
    $h[]="<div class='upd-tile-v'>".$o["version"]."</div>$sub";
    $h[]="</div>";
    if($js!=""){
        $h[]="<a class='upd-bt upd-bt-amber' href=\"javascript:blur();\" OnClick=\"$js\">"
            ."<i class='fas fa-download'></i>$action</a>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

/** Tuile HotFix RC. Reprend l'ancienne line_hotfix_unstable(). */
function upd_tile_rc(array $repos):string{
    $page=CurrentPageName();
    $array=update_find_hotfix_unstable($repos);
    if(count($array)==0){return "";}
    $VERSION=(string)(isset($array["VERSION"])?$array["VERSION"]:"");
    // update_find_hotfix_unstable() ne rend une entree que si le depot publie un
    // correctif PLUS RECENT que la version installee: cette tuile signifie donc
    // toujours "quelque chose a installer", d'ou la mise en avant.
    return upd_tile_hot(array("ico"=>"fas fa-flask","title"=>"HotFix",
        "version"=>"v.".htmlspecialchars($VERSION),"sub"=>"{hotfix_available_explain}",
        "js"=>"Loadjs('$page?update-hotfix-js=yes')"));
}

/** Encart "versions disponibles". Rendu seulement s'il a du contenu. */
function upd_available_strip(array $repos,array $sps):string{
    // Le correctif disponible passe EN TETE: il est le seul element actionnable
    // de l'encart, et il etait relegue derriere deux tuiles documentaires.
    $tiles=array();
    $rc=upd_tile_rc($repos);
    if($rc!=""){$tiles[]=$rc;}
    $nightly=upd_tile_nightly($repos,$sps);
    if($nightly!=""){$tiles[]=$nightly;}
    $lts=upd_tile_lts($repos);
    if($lts!=""){$tiles[]=$lts;}
    if(count($tiles)==0){return "";}
    return upd_section_head("{upd_available_versions}")
        ."<div class='upd-tiles'>".@implode("",$tiles)."</div>";
}

function table_left():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();

    $ArticaUpdateServicesPacks=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateServicesPacks"));
    if(!is_array($ArticaUpdateServicesPacks)){$ArticaUpdateServicesPacks=array();}
    $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
    if(!is_array($ArticaUpdateRepos)){$ArticaUpdateRepos=array();}
    $ArticaAutoUpateNightly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));

    $perform_update_js = "Loadjs('$page?perform-update=yes')";
    $perform_update_btn = "<label class=\"btn btn btn-warning\" OnClick=\"$perform_update_js\"><i class='fa fa-download'></i> {update_now}</label>";

    $btns=array();
    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";

    list($updateLabel,$MustUpdate)=widget_current_version();
    VERBOSE("Widget MustUpdate=[$MustUpdate]",__LINE__);
    $BT_UPDATE=false;
    if($MustUpdate){
        $btns[]=$perform_update_btn;
        $BT_UPDATE=true;
    }

    // Le bouton "mettre a jour maintenant" de l'entete apparait aussi quand on
    // est deja sur la version de la nightly mais qu'elle publie un service pack
    // plus recent -- condition imbriquee conservee telle quelle.
    $CURVER=trim((string)@file_get_contents("VERSION"));
    $CURVER_KEY=intval(str_replace(".","",$CURVER));
    $CURPATCH=intval($GLOBALS["CLASS_SOCKETS"]->SPVersion());
    $key_nightly=update_find_latest_nightly($ArticaUpdateRepos);
    $NIGHTLY=isset($ArticaUpdateRepos["NIGHT"])&&is_array($ArticaUpdateRepos["NIGHT"])?$ArticaUpdateRepos["NIGHT"]:array();
    if(isset($NIGHTLY[$key_nightly]["MD5"])){
        $Lastest_nightly_ver=(string)(isset($NIGHTLY[$key_nightly]["VERSION"])?$NIGHTLY[$key_nightly]["VERSION"]:"");
        $NightlySP=0;
        if(isset($ArticaUpdateServicesPacks[$Lastest_nightly_ver]["VERSION"])){
            $NightlySP=intval($ArticaUpdateServicesPacks[$Lastest_nightly_ver]["VERSION"]);
        }
        if($ArticaAutoUpateNightly==1){
            if($CURVER_KEY==$key_nightly){
                if($NightlySP>$CURPATCH){
                    if(!$BT_UPDATE){$btns[]=$perform_update_btn;}
                }
            }
        }
    }

    $html=array();
    $html[]="<div class='upd-wrap'>";
    $html[]="<div class='upd-heroes'>";
    $html[]=upd_hero_installed($MustUpdate,$updateLabel,$users,$ArticaUpdateRepos);
    $html[]=upd_hero_channel($ArticaUpdateRepos,$ArticaUpdateServicesPacks,$users);
    $html[]="</div>";
    $html[]=upd_available_strip($ArticaUpdateRepos,$ArticaUpdateServicesPacks);
    $html[]="</div>";

    $jsresfresh=$tpl->framework_buildjs("/system/artica/indexes","refresh.index.progress","refresh.index.txt","progress-articaupd-restart","LoadAjax('main-artica-update-section','$page?tabs=yes');LoadAjax('table-loader-articaupd-service','$page?tabs=yes');");

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?PatchsBackupSize=yes");
    $PatchsBackupSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PatchsBackupSize"));

    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$jsresfresh\"><i class='fas fa-sync-alt'></i> {verify}</label>";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.update.events.php')\"><i class='".ico_eye."'></i>{display_update_events}</label>";

    if($PatchsBackupSize>16384){
        $PatchsBackupSize=FormatBytes($PatchsBackupSize/1024);
        if($users->AsSystemAdministrator) {
            $btns[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?PatchsBackup-remove=yes')\">
				<i class='fa-solid fa-trash'></i> {backup} ($PatchsBackupSize) </label>";
        }
    }
    $btns[]="</div>";

    $TINY_ARRAY["TITLE"]="{update_artica}";
    $TINY_ARRAY["ICO"]="far fa-cloud-download";
    $TINY_ARRAY["EXPL"]="{install_applis_text}<div id='header-update-page'></div>";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Le bloc de script est concatene APRES l'analyse, et l'acces au div est
    // desormais garde: document.getElementById() rend null quand la page est
    // chargee hors de son entete, et l'affectation levait alors une TypeError
    // qui interrompait le script -- donc aussi le $jstiny qui le precede.
    $script=upd_js_compile()."<script>
$jstiny
if(document.getElementById('header-update-page')){ document.getElementById('header-update-page').innerHTML=''; }
</script>";
	echo upd_style().$tpl->_ENGINE_parse_body(@implode("\n", $html)).$script;
    return true;
}
function hotfixdev_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ArticaHotFixDevs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHotFixDevs"));
    $form[]=$tpl->field_checkbox("ArticaHotFixDevs","{enable}",$ArticaHotFixDevs);
    $security="AsSystemAdministrator";
    $after="dialogInstance2.close();LoadAjax('status-right','$page?status-rigth=yes');";
    $MyForm=$tpl->form_outside("",$form,"{ArticaHotFixDevsExplain}","{apply}",$after,$security);
    echo $MyForm;
    return true;
}
function hotfixdev_save():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaHotFixDevs",intval($_POST["ArticaHotFixDevs"]));
    $sock=new sockets();
    $sock->REST_API("/system/artica/hotfix/update");
    return admin_tracks("Set Enable Hotfix in developpement to ".intval($_POST["ArticaHotFixDevs"]));
}
function table_right():bool
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users = new usersMenus();

    $ArticaAutoUpateRsync = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsync"));
    $ArticaAutoUpateRsyncServer = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServer"));
    $ArticaAutoUpateRsyncServerPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServerPort"));
    $ArticaHotFixDevs = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHotFixDevs"));
    $ArticaDisablePatchs = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));
    $ArticaAutoUpateOfficial = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateOfficial"));
    $ArticaAutoUpateNightly = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));
    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));

    if (!is_numeric($ArticaAutoUpateOfficial)) {
        $ArticaAutoUpateOfficial = 1;
    }
    if ($ArticaAutoUpateRsyncServerPort == 0) {
        $ArticaAutoUpateRsyncServerPort = 873;
    }
    if ($ArticaDisablePatchs == 0) {
        $ArticaEnablePatchs = 1;
    } else {
        $ArticaEnablePatchs = 0;
    }
    $EnableAPTClientMirror = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAPTClientMirror"));
    $APTClientMirrorAddr = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APTClientMirrorAddr"));

    // Les cibles de clic sont celles d'avant la refonte: le crayon ouvre la
    // meme boite de reglages, et disparait quand l'operateur n'est pas
    // administrateur -- masquer le bouton ne protege rien, mais le laisser
    // visible a qui ne peut pas s'en servir n'a pas de sens non plus.
    $settingsJs = "";
    $hotfixJs = "";
    if ($users->AsSystemAdministrator) {
        $settingsJs = "Loadjs('$page?settings-js=yes');";
        $hotfixJs = "Loadjs('$page?hotfixdev-js=yes');";
    }

    $rows = array();

    // 1. Points de rollback: pas de crayon, ses actions sont dans la ligne.
    $points = upd_rollback_points();
    if (count($points) > 0) {
        $links = array();
        foreach ($points as $sp) {
            $sp = intval($sp);
            $target = "{service_pack} $sp";
            if ($users->AsSystemAdministrator) {
                $links[] = "<span class='upd-lnk' OnClick=\"Loadjs('$page?rollback-sp=$sp');\">$target</span>";
                continue;
            }
            $links[] = $target;
        }
        $right = "";
        if ($users->AsSystemAdministrator) {
            $right = "<span class='upd-del' OnClick=\"Loadjs('$page?delete-all-sp-js=yes');\">{delete_all}</span>";
        }
        $rows[] = upd_row(array("ico" => "fas fa-backward-fast", "title" => "{upd_rollback_points}",
            "sub" => @implode(" &middot; ", $links), "right" => $right));
    }

    // 2. Releases officielles
    $rows[] = upd_row(array("ico" => "fas fa-cloud-arrow-down", "title" => "{update_artica_official}",
        "on" => ($ArticaAutoUpateOfficial == 1), "state" => upd_state($ArticaAutoUpateOfficial),
        "js" => $settingsJs));

    // 3. Service packs
    $rows[] = upd_row(array("ico" => "fas fa-box-archive", "title" => "{update_services_packs}",
        "on" => ($ArticaEnablePatchs == 1), "state" => upd_state($ArticaEnablePatchs),
        "js" => $settingsJs));

    // 4. Nightly
    $rows[] = upd_row(array("ico" => "fas fa-moon", "title" => "{update_artica_nightly}",
        "on" => ($ArticaAutoUpateNightly == 1), "state" => upd_state($ArticaAutoUpateNightly),
        "js" => $settingsJs));

    // 5. Correctifs en developpement (sa propre boite de dialogue)
    $rows[] = upd_row(array("ico" => "fas fa-bolt", "title" => "{update_hotfix_dev}",
        "on" => ($ArticaHotFixDevs == 1), "state" => upd_state($ArticaHotFixDevs),
        "js" => $hotfixJs));

    // 6. SSL
    $rows[] = upd_row(array("ico" => "fas fa-lock", "title" => "{ssl}",
        "on" => ($ArticaRepoSSL == 1), "state" => upd_state($ArticaRepoSSL),
        "js" => $settingsJs));

    // 7. Rsync: la ligne n'existe que si le binaire est installe -- c'etait deja
    // le cas avant la refonte.
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_INSTALLED")) == 1) {
        $sub = "";
        if ($ArticaAutoUpateRsync == 1 AND $ArticaAutoUpateRsyncServer != "") {
            $sub = "<span class='upd-mono'>" . htmlspecialchars($ArticaAutoUpateRsyncServer)
                . ":" . $ArticaAutoUpateRsyncServerPort . "</span>";
        }
        $rows[] = upd_row(array("ico" => "fas fa-right-left", "title" => "{remote_synchronization} (Rsync)",
            "on" => ($ArticaAutoUpateRsync == 1), "sub" => $sub,
            "state" => upd_state($ArticaAutoUpateRsync), "js" => $settingsJs));
    }

    $html = array();
    $html[] = "<div class='upd-wrap'>";
    $html[] = upd_section_head("{upd_distribution_channels}",
        count($rows) . " {upd_configurable_components}");
    $html[] = upd_grid($rows);

    // Section systeme d'exploitation: le reglage du miroir APT, sur toute la
    // largeur. L'adresse ne s'affiche que lorsque le miroir est actif.
    $subOS = "{upd_debian_mirror_explain}";
    if ($EnableAPTClientMirror == 1 AND $APTClientMirrorAddr != "") {
        $subOS = "<span class='upd-mono'>" . htmlspecialchars($APTClientMirrorAddr) . "</span>";
    }
    $html[] = upd_section_head("{upd_os_section}", "{upd_apt_upstream}");
    $html[] = upd_grid(array(upd_row(array(
        "ico_class" => "upd-ico upd-ico-deb", "ico_text" => "DEB",
        "title" => "{useInternalDebianMirror}", "sub" => $subOS,
        "state" => upd_state($EnableAPTClientMirror), "js" => $settingsJs, "wide" => true))));
    $html[] = "</div>";

    echo upd_style() . $tpl->_ENGINE_parse_body(@implode("\n", $html)) . upd_js_compile();
    return true;
}
function hotfixdev_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->js_dialog2("{update_hotfix_dev}","$page?hotfixdev-popup=yes");
    return true;
}
function settings_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->js_dialog2("{settings}","$page?settings-popup=yes");
    return true;
}
function table_right_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ArticaAutoUpateRsync=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsync"));
    $ArticaAutoUpateRsyncServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServer"));
    $ArticaAutoUpateRsyncServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServerPort"));


    $ArticaDisablePatchs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));

    $ArticaAutoUpateOfficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateOfficial"));
    $ArticaAutoUpateNightly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));
    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
    if(!is_numeric($ArticaAutoUpateOfficial)){$ArticaAutoUpateOfficial=1;}
    if($ArticaAutoUpateRsyncServerPort==0){$ArticaAutoUpateRsyncServerPort=873;}



    $CurlBandwith=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurlBandwith");
    $CurlTimeOut=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurlTimeOut");
    if(!is_numeric($CurlBandwith)){$CurlBandwith=0;}
    if(!is_numeric($CurlTimeOut)){$CurlTimeOut=3600;}
    if($CurlTimeOut<720){$CurlTimeOut=3600;}

    $form[]=$tpl->field_checkbox("ArticaAutoUpateOfficial","{update_artica_official}",$ArticaAutoUpateOfficial,true,"{update_artica_official_explain}");

    if($ArticaDisablePatchs==0){$ArticaEnablePatchs=1;}else{$ArticaEnablePatchs=0;}
    $form[]=$tpl->field_checkbox("ArticaEnablePatchs","{update_services_packs}",$ArticaEnablePatchs,false,"");


    $form[]=$tpl->field_checkbox("ArticaAutoUpateNightly","{update_artica_nightly}",$ArticaAutoUpateNightly,false,"{update_artica_nightly_explain}");
    $form[]=$tpl->field_checkbox("ArticaRepoSSL","{ssl}",$ArticaRepoSSL,false);


    $form[]=$tpl->field_section("{system_update}");
    $EnableAPTClientMirror=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAPTClientMirror"));
    $APTClientMirrorAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APTClientMirrorAddr"));
    $use_internal_debian_mirror=$tpl->_ENGINE_parse_body("{useInternalDebianMirror}");
    $form[]=$tpl->field_checkbox("EnableAPTClientMirror",$use_internal_debian_mirror,$EnableAPTClientMirror,
        "APTClientMirrorAddr","");
    $form[]=$tpl->field_text("APTClientMirrorAddr","{internal_debian_mirror_addr}",$APTClientMirrorAddr);

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_INSTALLED"))==1){
        $form[]=$tpl->field_section("{remote_synchronization} (Rsync)");
        $form[]=$tpl->field_checkbox("ArticaAutoUpateRsync","{enable_remote_synchronization}",$ArticaAutoUpateRsync,"ArticaAutoUpateRsyncServer,ArticaAutoUpateRsyncServerPort","{ArticaAutoUpateRsync}");
        $form[]=$tpl->field_text("ArticaAutoUpateRsyncServer","{remote_server}",$ArticaAutoUpateRsyncServer);
        $form[]=$tpl->field_text("ArticaAutoUpateRsyncServerPort","{remote_server_port}",$ArticaAutoUpateRsyncServerPort);
    }else{
        $form[]=$tpl->field_hidden("ArticaAutoUpateRsync", 0);
    }
    $form[]=$tpl->field_section("{timeouts}");
    $form[]=$tpl->field_numeric("CurlTimeOut","{HTTP_TIMEOUT} ({seconds})",$CurlTimeOut);
    $form[]=$tpl->field_numeric("CurlBandwith","{limit_bandwidth} (kb/s)",$CurlBandwith);

    $security="AsSystemAdministrator";
    $after="dialogInstance2.close();LoadAjax('status-right','$page?status-rigth=yes');";
    $MyForm=$tpl->form_outside("{settings}",$form,null,"{apply}",$after,$security);
    echo $MyForm;
    return true;
}

function file_uploaded(){
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
    $tpl=new template_admin();
	$file=$_GET["file-uploaded"];

	admin_tracks("Artica Update $file as been successfully uploaded");
    $fname=urlencode(base64_encode($file));
    $jsrestart=$tpl->framework_buildjs(
        "/system/artica/update/manual/$fname",
        "artica.install.progress",
        "artica.install.progress.txt",
        "progress-articaupd-restart",
        "LoadAjax('status-left','$page?status-left=yes');"
    );

	echo $jsrestart;
}

function update_find_latest($array):int{
    if(!is_array($array)){return 0;}
    if(!isset($array["OFF"])){return 0;}
	$MAIN=$array["OFF"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}if($key>$keyMain){$keyMain=$key;}}return $keyMain;
}

function update_find_hotfix_unstable($array):array{
    if(!isset($array["HOTFIX"])){return array();}
    $VERSION=trim(@file_get_contents("VERSION"));
    if(!isset($array["HOTFIX"][$VERSION])){return array();}
    $CURPATCH= $GLOBALS["CLASS_SOCKETS"]->SPVersion();
    if($CURPATCH==0){$CURPATCH="000";}
    if(!isset($array["HOTFIX"][$VERSION][$CURPATCH])){return array();}
    $ARRAY=$array["HOTFIX"][$VERSION][$CURPATCH];
    $CurHo=0;
    foreach ($ARRAY as $HotFixNum=>$array){
        if($HotFixNum>$CurHo){
            $CurHo=$HotFixNum;
        }
    }

    if($CurHo>0){
        $CURHOTFIX=intval(str_replace("-","",$GLOBALS["HOTFIX"]));
        if($CurHo>$CURHOTFIX) {
            $ARRAY[$CurHo]["INDEX"] = $CurHo;
            return $ARRAY[$CurHo];
        }
    }
    return array();

}
function update_find_lts($array):int{
    // Les deux verifications etaient dans l'ordre INVERSE: is_array() lisait la
    // cle avant qu'isset() n'ait verifie qu'elle existe, ce qui emet un
    // avertissement en PHP 8 des que le depot ne publie pas de branche LTS.
    if(!is_array($array) || !isset($array["LTS"]) || !is_array($array["LTS"])){return 0;}
    $MAIN=$array["LTS"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}
        if($key>$keyMain){$keyMain=$key;}}
    return $keyMain;
}

function update_find_latest_nightly($array):int{
    // Meme inversion que dans update_find_lts().
    if(!is_array($array) || !isset($array["NIGHT"]) || !is_array($array["NIGHT"])){return 0;}
	$MAIN=$array["NIGHT"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}if($key>$keyMain){$keyMain=$key;}}return $keyMain;
}

function RefreshPage(){

    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $f[]="if(document.getElementById('main-artica-update-section')){";
    $f[]="LoadAjax('main-artica-update-section','$page?tabs=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('table-loader-articaupd-service')){";
    $f[]="LoadAjax('table-loader-articaupd-service','$page?tabs=yes');";
    $f[]="}";
    echo @implode("\n",$f);
}

function javascript_run_update():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $prgress=$tpl->framework_buildjs(
        "system.php?artica-update=yes",
        "artica.updatemanu.progress",
        "artica.updatemanu.log",
        "progress-articaupd-restart",
        "LoadAjax('status-left','$page?status-left=yes');");

    $tpl->js_confirm_execute("{artica_update}","none","ok",$prgress);
    return true;

}