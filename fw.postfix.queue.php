<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["scan"])){scan_queue();exit;}
if(isset($_GET["flush"])){flush_queue();exit;}
if(isset($_GET["table-div"])){table_div();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["view-js"])){view_js();exit;}
if(isset($_GET["view-popup"])){view_popup();exit;}
if(isset($_GET["pqv-tab"])){pqv_tab();exit;}
if(isset($_GET["eml"])){pqv_eml();exit;}
if(isset($_POST["pqv-action"])){pqv_action();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["pause-queue-js"])){pause_queue_js();exit;}
if(isset($_GET["empty-queue-js"])){empty_queue_js();exit;}
if(isset($_POST["empty-queue-perform"])){empty_queue_perform();exit;}
if(isset($_POST["pause_queue"])){exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_POST["PostfixQueueEnabled"])){parameters_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["watchdog-pane"])){watchdog_pane();exit;}
if(isset($_GET["watchdog-status"])){watchdog_status();exit;}
if(isset($_GET["watchdog-flat"])){watchdog_flat();exit;}
if(isset($_GET["watchdog-charts"])){watchdog_charts();exit;}
page();

function parameters_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
   $tpl->js_dialog1("{parameters}","$page?parameters-popup=yes",890);

}
function scan_queue():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $instanceid=intval($_GET["scan"]);
    $function=$_GET["function"];
    $data=$sock->REST_API_POSTFIX("/postfix/queue/scan/$instanceid");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return $tpl->js_error("System error");
    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    $tpl->js_executed_background("");
    echo "$function();";
    return true;
}
function flush_queue():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $instanceid=intval($_GET["flush"]);
    $function=$_GET["function"];
    $data=$sock->REST_API_POSTFIX("/postfix/queue/flush/$instanceid");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return $tpl->js_error("System error");
    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    $tpl->js_ok("{success}");
    echo "$function();";
    return true;
}

function parameters_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $PostfixQueueEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixQueueEnabled"));
    $PostfixQueueMaxMails=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixQueueMaxMails"));
    $PostfixQueueDeferredMaxMails=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixQueueDeferredMaxMails"));
    $PostfixQueueDeferredMaxAgeHours=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixQueueDeferredMaxAgeHours"));
    $PostfixQueueScanInterval=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixQueueScanInterval"));
    $CadenceList=watchdog_cadences();
    if(!isset($CadenceList[$PostfixQueueScanInterval])){$PostfixQueueScanInterval="*/10 * * * *";}
    if($PostfixQueueDeferredMaxMails==0){$PostfixQueueDeferredMaxMails=500;}
    if($PostfixQueueDeferredMaxAgeHours==0){$PostfixQueueDeferredMaxAgeHours=24;}
    $master=new maincf_multi($instance_id);
    $in_flow_delay=$master->GET("in_flow_delay");
    $minimal_backoff_time=$master->GET("minimal_backoff_time");
    $maximal_backoff_time=$master->GET("maximal_backoff_time");
    $bounce_queue_lifetime=$master->GET("bounce_queue_lifetime");
    $maximal_queue_lifetime=$master->GET("maximal_queue_lifetime");
    $queue_run_delay=$master->GET("queue_run_delay");

    if($in_flow_delay==null){$in_flow_delay="1s";}
    if($minimal_backoff_time==null){$minimal_backoff_time="300s";}
    if($maximal_backoff_time==null){$maximal_backoff_time="4000s";}
    if($bounce_queue_lifetime==null){$bounce_queue_lifetime="5d";}
    if($maximal_queue_lifetime==null){$maximal_queue_lifetime="5d";}
    if($queue_run_delay==null){$queue_run_delay="300s";}

    if($PostfixQueueMaxMails==0){$PostfixQueueMaxMails=20;}


    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_text("minimal_backoff_time","{minimal_backoff_time}",$minimal_backoff_time,true,"{minimal_backoff_time_text}");
    $form[]=$tpl->field_text("maximal_backoff_time","{maximal_backoff_time}",$maximal_backoff_time,true,"{maximal_backoff_time_text}");
    $form[]=$tpl->field_text("bounce_queue_lifetime","{bounce_queue_lifetime}",$bounce_queue_lifetime,true,"{bounce_queue_lifetime_text}");
    $form[]=$tpl->field_text("maximal_queue_lifetime","{maximal_queue_lifetime}",$maximal_queue_lifetime,true,"{maximal_queue_lifetime_text}");
    $form[]=$tpl->field_text("in_flow_delay","{in_flow_delay}",$in_flow_delay,true,"{in_flow_delay_text}");
    $form[]=$tpl->field_text("queue_run_delay","{queue_run_delay}",$queue_run_delay,true,"{queue_run_delay_text}");


    $form[]=$tpl->field_section("{watchdog}");
    $form[]=$tpl->field_checkbox("PostfixQueueEnabled","{PostfixQueueEnabled}",$PostfixQueueEnabled,"PostfixQueueMaxMails");
    // La cadence est une LISTE FERMEE, pas un champ libre: une periode arbitraire invite des
    // valeurs qui soit noient l'appliance, soit rendent l'alerte plus lente que l'incident.
    // La liste vient du daemon (source unique) et non d'une copie codee ici, qui deriverait.
    $form[]=$tpl->field_array_select($CadenceList,"PostfixQueueScanInterval","{PostfixQueueScanInterval}",$PostfixQueueScanInterval,"{PostfixQueueScanInterval_text}");
    $form[]=$tpl->field_numeric("PostfixQueueMaxMails","{PostfixQueueMaxMails}",$PostfixQueueMaxMails);
    // DEUX seuils: un arriere differe est normal sur un relais, un arriere en incoming ne l'est
    // jamais. Une valeur unique appliquee aux deux transforme le chien de garde en alarme continue.
    $form[]=$tpl->field_numeric("PostfixQueueDeferredMaxMails","{PostfixQueueDeferredMaxMails}",$PostfixQueueDeferredMaxMails);
    $form[]=$tpl->field_numeric("PostfixQueueDeferredMaxAgeHours","{PostfixQueueDeferredMaxAgeHours}",$PostfixQueueDeferredMaxAgeHours);
    echo $tpl->form_outside("", $form,null,"{apply}","dialogInstance1.close();","AsPostfixAdministrator");


}

function parameters_save(){
   $tpl=new template_admin();
   $tpl->CLEAN_POST_XSS();
   $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixQueueEnabled",$_POST["PostfixQueueEnabled"]);
   $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixQueueMaxMails",$_POST["PostfixQueueMaxMails"]);
   // Une cadence hors liste n'est PAS enregistree: le daemon retomberait au defaut, mais la page
   // afficherait alors une valeur qui n'est pas celle qui tourne.
   $Cadences=watchdog_cadences();
   $Interval=isset($_POST["PostfixQueueScanInterval"]) ? trim($_POST["PostfixQueueScanInterval"]) : "";
   if(isset($Cadences[$Interval])){
       $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixQueueScanInterval",$Interval);
   }
   if(isset($_POST["PostfixQueueDeferredMaxMails"])){
       $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixQueueDeferredMaxMails",intval($_POST["PostfixQueueDeferredMaxMails"]));
   }
   if(isset($_POST["PostfixQueueDeferredMaxAgeHours"])){
       $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixQueueDeferredMaxAgeHours",intval($_POST["PostfixQueueDeferredMaxAgeHours"]));
   }
   $instance_id=intval($_POST["instance_id"]);
   $master=new maincf_multi($instance_id);
   $master->SAVE_POSTS();
   // Application des temporisations de file par le daemon.
   //
   // Remplace getFrameWork("postfix2.php?queue-params=yes&instance-id=...") qui lancait
   // exec.postfix.maincf.php --queue-params. Ce script IGNORAIT son --instance-id
   // (maincf_multi("master","master") en dur) : sur une appliance multi-instance les valeurs
   // etaient bien enregistrees pour la bonne instance puis celles de l'instance 0 etaient
   // appliquees — des reglages qui paraissent sauvegardes et ne prennent jamais effet.
   $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/queue/params/$instance_id");
  }

/**
 * Fenetre « message en file » (maquette stitch_queue_dialog_ux_redesign).
 *
 * Tout passe par le daemon artica-postfix (/postfix/queue/message|hold|release|retry, l'historique
 * par /postfix/maillog/messages) : l'ancienne fenetre appelait framework/postfix.php?postcat-q=,
 * qui passait l'identifiant tel quel a shell_exec, et rendait le contenu du message BRUT dans un
 * <textarea> — un message contenant </textarea><script> executait du code dans la console.
 * Tout ce qui vient du message (adresses, en-tetes, motifs, dump) est donc echappe ici.
 */
function pqv_id():string{
    $id=trim((string)($_GET["id"] ?? ($_GET["view-js"] ?? ($_GET["view-popup"] ?? ""))));
    if(!preg_match('/^[0-9A-Za-z]{6,32}$/',$id)){return "";}
    return $id;
}
function pqv_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}
function pqv_is_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsPostfixAdministrator;
}
function pqv_e($s):string{
    // Les accolades sont encodees aussi : un « {admin} » dans un sujet serait sinon traduit par
    // _ENGINE_parse_body() comme un jeton de la console.
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

function view_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=pqv_id();
	if($id==""){return;}
    $instance_id=intval($_GET["instance-id"]);
    $fn=pqv_function();
	$tpl->js_dialog1("{message}: $id", "$page?view-popup=$id&instance-id=$instance_id&function=$fn",1100);
}

/** Lit le message aupres du daemon ; renvoie [objet, erreur]. */
function pqv_fetch(int $instance_id,string $id):array{
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/queue/message/$instance_id/$id");
    $json=json_decode((string)$resp);
    if(!is_object($json)){return array(null,"{error}: artica-postfix");}
    if(empty($json->Status) || !isset($json->Message) || !is_object($json->Message)){
        return array(null,isset($json->Error)?(string)$json->Error:"{error}");
    }
    return array($json->Message,"");
}

// Etat de la file -> ton du kit ak-.
function pqv_state_tone(string $q):string{
    $map=array("deferred"=>"ak-warn","hold"=>"ak-info","active"=>"ak-ok","incoming"=>"ak-ok","maildrop"=>"ak-ok");
    return isset($map[$q])?$map[$q]:"ak-off";
}
function pqv_state_label(string $q):string{
    $map=array("deferred"=>"{pqv_state_deferred}","hold"=>"{pqv_state_hold}","active"=>"{pqv_state_active}",
        "incoming"=>"{pqv_state_incoming}","maildrop"=>"{pqv_state_maildrop}");
    return isset($map[$q])?$map[$q]:"{pqv_state_gone}";
}

function view_popup(){
    $tpl=new template_admin();
    if(!pqv_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS}");return;}
    $page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);
    $id=pqv_id();
    $fn=pqv_function();
    if($id==""){echo $tpl->div_error("{error}: {queue_id}");return;}
    list($m,$err)=pqv_fetch($instance_id,$id);
    if($m===null){
        echo pqv_css().$tpl->_ENGINE_parse_body("<div class='pqv ak'><div class='pqv-body'>".
            "<div class='ak-banner ak-err pqv-alert'><i class='fas fa-triangle-exclamation'></i><div>".pqv_e($err)."</div></div></div></div>");
        return;
    }
    $q=(string)$m->queue_name;
    $host=(string)$m->hostname;

    $h=array();
    $h[]="<div class='pqv ak'>";
    $h[]=pqv_head($m,$instance_id);
    $h[]=pqv_actions($m,$instance_id,$fn);
    $tabs=array();
    $base="$page?id=$id&instance-id=$instance_id";
    $tabs["{pqv_overview}"]="$base&pqv-tab=overview";
    $tabs["{pqv_mime_routing}"]="$base&pqv-tab=headers";
    $tabs["{pqv_raw_queue}"]="$base&pqv-tab=raw";
    $tabs["{pqv_delivery_history}"]="$base&pqv-tab=history";
    $h[]="<div class='pqv-tabs'>".$tpl->tabs_default($tabs)."</div>";
    $retry="";
    if($q=="deferred" || $q=="active"){
        $retry="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"PqvAct('retry')\"><i class='fas fa-rotate'></i> {pqv_retry_now}</button>";
    }elseif($q=="hold"){
        $retry="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"PqvAct('release')\"><i class='fas fa-play'></i> {pqv_release}</button>";
    }
    $h[]="<div class='pqv-foot'><span class='pqv-foot-st'><span class='ak-dot ak-ok'></span>{pqv_postfix_on} <span class='ak-mono'>".pqv_e($host).":25</span></span>";
    $h[]="<span class='pqv-foot-bt'><button type='button' class='ak-btn' OnClick=\"dialogInstance1.close();\">{close}</button>$retry</span></div>";
    $h[]="</div>";
    echo pqv_css().$tpl->_ENGINE_parse_body(@implode("\n",$h)).pqv_actions_js($instance_id,$id,$fn);
}

function pqv_head($m,int $instance_id):string{
    $q=(string)$m->queue_name;
    $id=pqv_e($m->queue_id);
    $h=array();
    $h[]="<div class='pqv-head'>";
    $h[]="<div class='pqv-head-l'><span class='ak-ico ak-ico-sq'><i class='far fa-envelope'></i></span><div>";
    $h[]="<div class='pqv-eyebrow'>{queue} Postfix".($instance_id>0?" #$instance_id":"")." <span class='pqv-sep'>&bull;</span> <span class='ak-pill ".pqv_state_tone($q)."'><span class='ak-dot'></span>".pqv_state_label($q)."</span></div>";
    $h[]="<div class='pqv-title'>{pqv_message_details} <span class='ak-chip' id='pqv-qid'>$id <a href='javascript:blur();' OnClick=\"PqvCopy('pqv-qid-v');\" title='{copy}'><i class='far fa-copy'></i></a></span><input type='hidden' id='pqv-qid-v' value='$id'></div>";
    $h[]="</div></div>";
    $h[]="<div class='pqv-host'>host: ".pqv_e($m->hostname)."</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function pqv_actions($m,int $instance_id,string $fn):string{
    $page=CurrentPageName();
    $q=(string)$m->queue_name;
    $id=urlencode((string)$m->queue_id);
    $b=array();
    if($q=="hold"){
        $b[]="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"PqvAct('release')\"><i class='fas fa-play'></i> {pqv_release}</button>";
    }elseif($q!=""){
        $b[]="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"PqvAct('retry')\"><i class='fas fa-rotate'></i> {pqv_retry_now}</button>";
        $b[]="<button type='button' class='ak-btn' OnClick=\"PqvAct('hold')\"><i class='far fa-circle-pause' style='color:#f59e0b'></i> {hold}</button>";
    }
    $b[]="<button type='button' class='ak-btn ak-btn-danger' OnClick=\"Loadjs('$page?delete-js=$id&instance-id=$instance_id&function=$fn')\"><i class='far fa-trash-can'></i> {delete}</button>";
    $eml="<a class='ak-btn' href='$page?eml=$id&instance-id=$instance_id'><i class='fas fa-download'></i> {pqv_download_eml}</a>";
    return "<div class='pqv-actbar'><span class='pqv-act-l'>".@implode("",$b)."</span>$eml</div>";
}

function pqv_actions_js(int $instance_id,string $id,string $fn):string{
    $page=CurrentPageName();
    $refresh=($fn=="")?"":"if(typeof $fn=='function'){{$fn}();}";
    return "<script>
function PqvAct(a){
  $.post('$page',{'pqv-action':a,'id':'$id','instance-id':'$instance_id'},function(r){
    r=String(r||'').trim();
    if(r.length>0){alert(r);return;}
    dialogInstance1.close();$refresh
  });
}
function PqvCopy(src){
  var e=document.getElementById(src);if(!e){return;}
  var t=(e.value!==undefined)?e.value:e.textContent;
  if(navigator.clipboard){navigator.clipboard.writeText(t);}
}
NoSpinner();
</script>";
}

/** POST pqv-action : hold / release / retry. N'ecrit RIEN en cas de succes. */
function pqv_action(){
    $tpl=new template_admin();
    if(!pqv_is_admin()){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS}");return;}
    $a=(string)$_POST["pqv-action"];
    $id=trim((string)($_POST["id"] ?? ""));
    $instance_id=intval($_POST["instance-id"] ?? 0);
    if(!preg_match('/^[0-9A-Za-z]{6,32}$/',$id)){echo "invalid queue id";return;}
    if(!in_array($a,array("hold","release","retry"),true)){echo "invalid action";return;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/queue/$a/$instance_id/$id"));
    if(!is_object($json)){echo "artica-postfix: no answer";return;}
    if(empty($json->Status)){echo (string)$json->Error;return;}
    admin_tracks("MTA queue: $a message $id on instance $instance_id");
}

/** GET eml= : le message tel qu'il sera remis (cURL direct : REST_API* decode du JSON). */
function pqv_eml(){
    if(!pqv_is_admin()){header("HTTP/1.1 403 Forbidden");return;}
    $id=trim((string)$_GET["eml"]);
    $instance_id=intval($_GET["instance-id"]);
    if(!preg_match('/^[0-9A-Za-z]{6,32}$/',$id)){header("HTTP/1.1 400 Bad Request");return;}
    $ch=curl_init("http://localhost/postfix/queue/message/$instance_id/$id/eml");
    curl_setopt($ch,CURLOPT_UNIX_SOCKET_PATH,"/run/artica-postfix.sock");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_TIMEOUT,60);
    $data=curl_exec($ch);
    $ctype=(string)curl_getinfo($ch,CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    if($data===false || strpos($ctype,"message/rfc822")===false){
        header("Content-Type: text/plain; charset=UTF-8");
        header("X-Content-Type-Options: nosniff");
        $json=json_decode((string)$data);
        echo is_object($json)&&isset($json->Error)?$json->Error:"artica-postfix: no answer";
        return;
    }
    admin_tracks("MTA queue: download message $id on instance $instance_id");
    header("Content-Type: message/rfc822");
    header("X-Content-Type-Options: nosniff");
    header("Content-Security-Policy: default-src 'none'");
    header("Content-Disposition: attachment; filename=\"$id.eml\"");
    header("Content-Length: ".strlen($data));
    echo $data;
}

function pqv_tab(){
    $tpl=new template_admin();
    if(!pqv_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS}");return;}
    $instance_id=intval($_GET["instance-id"]);
    $id=pqv_id();
    $tab=(string)$_GET["pqv-tab"];
    if($tab=="history"){echo $tpl->_ENGINE_parse_body(pqv_tab_history($instance_id,$id));return;}
    list($m,$err)=pqv_fetch($instance_id,$id);
    if($m===null){
        echo $tpl->_ENGINE_parse_body("<div class='pqv-body'><div class='ak-banner ak-err pqv-alert'><i class='fas fa-triangle-exclamation'></i><div>".pqv_e($err)."</div></div></div>");
        return;
    }
    if($tab=="headers"){echo $tpl->_ENGINE_parse_body(pqv_tab_headers($m));return;}
    if($tab=="raw"){
        // Le dump n'entre PAS dans _ENGINE_parse_body() : un {mot} du message serait pris pour un jeton.
        echo pqv_tab_raw($m,$tpl);
        return;
    }
    echo $tpl->_ENGINE_parse_body(pqv_tab_overview($m)).pqv_terminal($m,false);
}

function pqv_diag($m):string{
    $q=(string)$m->queue_name;
    $reasons=array();
    if(isset($m->recipients) && is_array($m->recipients)){
        foreach($m->recipients as $r){
            if(isset($r->delay_reason) && trim((string)$r->delay_reason)!=""){
                $reasons[]="<li><strong>".pqv_e($r->address)."</strong> &mdash; ".pqv_e($r->delay_reason)."</li>";
            }
        }
    }
    $flag=($m->trace_flags!=="")?"<span class='pqv-flag'>trace_flags=".pqv_e($m->trace_flags)."</span>":"";
    $cls="ak-warn";$ico="fas fa-triangle-exclamation";$title="{pqv_deferred_title}";$text="{pqv_deferred_explain}";
    if($q=="hold"){$cls="ak-info";$ico="far fa-circle-pause";$title="{pqv_hold_title}";$text="{pqv_hold_explain}";}
    elseif($q=="active"||$q=="incoming"||$q=="maildrop"){$cls="ak-ok";$ico="fas fa-paper-plane";$title="{pqv_active_title}";$text="{pqv_active_explain}";}
    $h="<div class='ak-banner $cls pqv-alert'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='$ico'></i></span><div class='pqv-alert-b'>";
    $h.="<div class='pqv-alert-t'>$title$flag</div><div>$text</div>";
    if(count($reasons)>0){$h.="<ul class='pqv-reasons'>".@implode("",$reasons)."</ul>";}
    if(!empty($m->forced_expire)){$h.="<div style='margin-top:6px'><strong>{pqv_forced_expire}</strong></div>";}
    return $h."</div></div>";
}

function pqv_initials(string $s):string{
    $s=preg_replace('/[^\p{L}\p{N} ]+/u',' ',$s);
    $w=preg_split('/\s+/',trim((string)$s));
    $i="";
    foreach($w as $x){if($x!==""){$i.=mb_substr($x,0,1,"UTF-8");} if(mb_strlen($i,"UTF-8")>=2){break;}}
    return mb_strtoupper($i==""?"?":$i,"UTF-8");
}

/** "Nom <adresse>" -> [nom, adresse]. */
function pqv_split_addr(string $h,string $fallback):array{
    $h=trim($h);
    if(preg_match('/^(.*?)<([^>]+)>\s*$/',$h,$re)){return array(trim($re[1]," \"'"),trim($re[2]));}
    if(preg_match('/^(\S+@\S+)\s*\((.+)\)$/',$h,$re)){return array(trim($re[2]),trim($re[1]));}
    if($h!==""){return array("",$h);}
    return array("",$fallback);
}

function pqv_party(string $label,string $name,string $addr,string $cls):string{
    $show=($name!=="")?$name:$addr;
    return "<div class='pqv-party'><span class='pqv-avatar $cls'>".pqv_e(pqv_initials($show))."</span><div style='min-width:0'>".
        "<div class='pqv-party-l'>$label</div><div class='pqv-party-n'>".pqv_e($show)."</div>".
        "<span class='pqv-addr $cls'>".pqv_e($addr)."</span></div></div>";
}

function pqv_tab_overview($m):string{
    $h=array();
    $h[]="<div class='pqv-body'>";
    $h[]=pqv_diag($m);

    $size=intval($m->message_size);
    $arr=intval($m->arrival_time);
    $date=$arr>0?date("d M Y",$arr)." <span class='ak-muted'>".date("H:i:s",$arr)."</span>":"&mdash;";
    $ctype=trim(explode(";",(string)$m->content_type)[0]);
    $enc=trim((string)$m->encoding);
    $mimev=pqv_e($enc==""?"7bit":$enc)." &bull; ".pqv_e($ctype==""?"text/plain":$ctype);
    $origin=(string)$m->origin;
    $h[]="<div class='ak-grid-4 pqv-kpis'>";
    $h[]="<div class='ak-card pqv-kpi'><div class='pqv-kpi-l'>{pqv_total_size}</div><div class='pqv-kpi-v'>".number_format($size,0,","," ")." <span class='ak-muted ak-mono'>{bytes} (".FormatBytes($size/1024,true).")</span></div></div>";
    $h[]="<div class='ak-card pqv-kpi'><div class='pqv-kpi-l'>{arrival_time}</div><div class='pqv-kpi-v'>$date</div></div>";
    $h[]="<div class='ak-card pqv-kpi'><div class='pqv-kpi-l'>{pqv_encoding_mime}</div><div class='pqv-kpi-v ak-mono pqv-small'>$mimev</div></div>";
    $h[]="<div class='ak-card pqv-kpi'><div class='pqv-kpi-l'>{pqv_log_origin}</div><div class='pqv-kpi-v ak-mono pqv-small pqv-okc'>".
        ($origin==""?"&mdash;":"<span class='ak-dot ak-ok'></span> ".pqv_e($origin)." (".pqv_e($m->hostname).")")."</div></div>";
    $h[]="</div>";

    $sender=(string)$m->sender;
    list($fname,$faddr)=pqv_split_addr((string)$m->from,$sender);
    if($sender!==""){$faddr=$sender;}
    if($sender===""){$fname=($fname==""?"MAILER-DAEMON":$fname);if($faddr==""){$faddr="<>";}}
    $rcpts=array();
    if(isset($m->recipients) && is_array($m->recipients)){foreach($m->recipients as $r){$rcpts[]=(string)$r->address;}}
    list($tname,$taddr)=pqv_split_addr((string)$m->to,count($rcpts)>0?$rcpts[0]:"");
    if(count($rcpts)>0){$taddr=$rcpts[0];}
    $more=(count($rcpts)>1)?" <span class='ak-muted'>+".(count($rcpts)-1)."</span>":"";

    $h[]="<div class='ak-card pqv-card'>";
    $h[]="<div class='ak-card-h pqv-card-h'><span>{pqv_envelope_title}</span><span class='ak-mono ak-muted pqv-small'>".pqv_e($m->queue_name)."/".pqv_e($m->queue_id)."</span></div>";
    $h[]="<div class='pqv-parties'>".pqv_party("{pqv_sender_label}",$fname,$faddr,"pqv-from").pqv_party("{pqv_recipient_label}",$tname,$taddr,"pqv-to").$more."</div>";
    $h[]="<div class='pqv-card-f'><span><strong>{subject}&nbsp;:</strong> ".pqv_e($m->subject)."</span>";
    if((string)$m->message_id!==""){$h[]="<span class='ak-mono ak-muted pqv-small pqv-ell'>Message-ID: ".pqv_e($m->message_id)."</span>";}
    $h[]="</div></div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

/** Terminal sombre : enregistrements d'enveloppe (vue d'ensemble) ou dump complet. Jamais parse. */
function pqv_terminal($m,bool $full):string{
    $path=pqv_e($m->queue_name."/".$m->queue_id);
    $lines=array();
    if($full){
        foreach(explode("\n",(string)$m->raw) as $l){
            if(preg_match('/^\*\*\* .*\*\*\*$/',$l)){$lines[]="<span class='pqv-t-sec'>".pqv_e($l)."</span>";continue;}
            $lines[]=pqv_e($l);
        }
    }else{
        $lines[]="<span class='pqv-t-sec'>*** ENVELOPE RECORDS $path ***</span>";
        if(isset($m->envelope) && is_array($m->envelope)){
            foreach($m->envelope as $e){
                $lines[]="<span class='pqv-t-k'>".pqv_e($e->name).":</span> ".pqv_e(preg_replace('/\s+/',' ',(string)$e->value));
            }
        }
    }
    $copy=$full?"<a href='javascript:blur();' class='pqv-t-copy' OnClick=\"PqvCopy('pqv-raw-src');\"><i class='far fa-copy'></i> Copy</a>":"";
    $src=$full?"<textarea id='pqv-raw-src' style='display:none'>".pqv_e($m->raw)."</textarea>":"";
    return "<div class='pqv-body' style='padding-top:0'><div class='pqv-term'><div class='pqv-term-h'><span class='pqv-lights'><i></i><i></i><i></i></span>".
        "<span class='ak-mono'>postfix/queue/$path</span>$copy</div><pre class='pqv-term-b'>".@implode("\n",$lines)."</pre>$src</div></div>";
}

function pqv_tab_raw($m,$tpl):string{
    $warn="";
    if(!empty($m->raw_truncated)){
        $warn=$tpl->_ENGINE_parse_body("<div class='pqv-body' style='padding-bottom:0'><div class='ak-banner ak-info pqv-alert'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-scissors'></i></span><div class='pqv-alert-b'>{pqv_raw_truncated}</div></div></div>");
    }
    return $warn.pqv_terminal($m,true);
}

function pqv_tab_headers($m):string{
    $h=array();
    $h[]="<div class='pqv-body'><div class='ak-card pqv-card'><div class='ak-card-h pqv-card-h'><span>{headers}</span><span class='ak-muted pqv-small'>".count((array)$m->headers)."</span></div>";
    $h[]="<table class='pqv-kv'>";
    if(isset($m->headers) && is_array($m->headers)){
        foreach($m->headers as $x){
            $h[]="<tr><th>".pqv_e($x->name)."</th><td>".pqv_e($x->value)."</td></tr>";
        }
    }
    $h[]="</table></div>";
    if(isset($m->recipients) && is_array($m->recipients) && count($m->recipients)>0){
        $h[]="<div class='ak-card pqv-card'><div class='ak-card-h pqv-card-h'><span>{recipients}</span><span class='ak-muted pqv-small'>".count($m->recipients)."</span></div><table class='pqv-kv'>";
        foreach($m->recipients as $r){
            $h[]="<tr><th>".pqv_e($r->address)."</th><td>".(trim((string)$r->delay_reason)==""?"&mdash;":pqv_e($r->delay_reason))."</td></tr>";
        }
        $h[]="</table></div>";
    }
    $h[]="</div>";
    return @implode("\n",$h);
}

/** Historique de remise : le suivi du journal (PostgreSQL) via /postfix/maillog/messages. */
function pqv_tab_history(int $instance_id,string $id):string{
    if($id==""){return "";}
    $iname=($instance_id>0)?"postfix-instance$instance_id":"postfix";
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/maillog/messages/$iname/$id"));
    if(!is_object($json) || !isset($json->queue_id)){
        return "<div class='pqv-body'><div class='ak-banner ak-info pqv-alert'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-clock-rotate-left'></i></span><div class='pqv-alert-b'>{pqv_no_history}</div></div></div>";
    }
    $h=array();
    $h[]="<div class='pqv-body'>";
    if(isset($json->recipients) && is_array($json->recipients) && count($json->recipients)>0){
        $h[]="<div class='ak-card pqv-card'><div class='ak-card-h pqv-card-h'><span>{recipients}</span></div><table class='pqv-kv pqv-kv-cols'>";
        $h[]="<tr><th>{recipient}</th><th>{status}</th><th>DSN</th><th>{relay}</th><th>{reason}</th></tr>";
        foreach($json->recipients as $r){
            $h[]="<tr><td>".pqv_e($r->recipient ?? "")."</td><td>".pqv_e($r->status ?? "")."</td><td class='ak-mono'>".pqv_e($r->dsn ?? "").
                "</td><td class='ak-mono pqv-small'>".pqv_e($r->relay ?? "")."</td><td class='pqv-small'>".pqv_e($r->reason ?? "")."</td></tr>";
        }
        $h[]="</table></div>";
    }
    $h[]="<div class='ak-card pqv-card'><div class='ak-card-h pqv-card-h'><span>{delivery_timeline}</span></div><table class='pqv-kv pqv-kv-cols'>";
    $h[]="<tr><th>{date}</th><th>{process}</th><th>{event}</th></tr>";
    $n=0;
    if(isset($json->timeline) && is_array($json->timeline)){
        foreach($json->timeline as $e){
            $n++;
            $t="";
            try{ if(!empty($e->event_time)){$d=new DateTime((string)$e->event_time);$t=$d->format("Y-m-d H:i:s");} }catch(Exception $ex){$t=(string)$e->event_time;}
            $h[]="<tr><td class='ak-mono pqv-small' style='white-space:nowrap'>".pqv_e($t)."</td><td class='ak-mono pqv-small'>".pqv_e($e->process ?? "").
                "</td><td class='ak-mono pqv-small'>".pqv_e($e->raw_line ?? ($e->event_type ?? ""))."</td></tr>";
        }
    }
    if($n==0){$h[]="<tr><td colspan='3'>{no_data}</td></tr>";}
    $h[]="</table></div></div>";
    return @implode("\n",$h);
}

/**
 * Feuille de style de la fenetre. Jamais passee dans _ENGINE_parse_body() (chaque regle CSS
 * serait prise pour un {jeton}). Accent = marque (brand_ui_accents), pas un bleu en dur.
 */
function pqv_css():string{
    if(isset($GLOBALS["PQV_CSS"])){return "";}
    $GLOBALS["PQV_CSS"]=true;
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){$ac=brand_ui_accents(brand_palette());}
    $a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];$al=$ac["accent_line"];
    // Carte, pastilles, boutons, bandeaux, puce, point : kit ak- (angular.css.php).
    return "<style>
.pqv{background:#f8fafc;border-radius:10px;overflow:hidden;color:#1e293b}
.pqv .ak-muted{font-weight:400}
.pqv a.ak-btn{text-decoration:none}
.pqv-small{font-size:12px}
.pqv-head{background:#fff;padding:20px 24px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.pqv-head-l{display:flex;align-items:center;gap:16px;min-width:0}
.pqv-eyebrow{color:#64748b;font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pqv-eyebrow .ak-pill{text-transform:none;letter-spacing:0;font-weight:500}
.pqv-sep{color:#cbd5e1}
.pqv-title{color:#0f172a;font-size:22px;font-weight:700;margin-top:4px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.pqv-title .ak-chip a{color:#94a3b8;margin-left:6px}
.pqv-title .ak-chip a:hover{color:$ad}
.pqv-host{font-family:'JetBrains Mono',Consolas,monospace;color:#64748b;font-size:12px;white-space:nowrap}
.pqv-actbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 24px 18px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
.pqv-act-l{display:flex;gap:10px;flex-wrap:wrap}
.pqv-tabs{background:#fff}
.pqv-tabs .nav-tabs{padding:0 16px;border-bottom:1px solid #e2e8f0}
.pqv-tabs .nav-tabs>li>a{color:#64748b;font-weight:500;border:0!important;border-bottom:2px solid transparent!important;background:transparent!important}
.pqv-tabs .nav-tabs>li.active>a{color:$ad!important;border-bottom-color:$a!important}
.pqv-tabs .tab-content{background:#f8fafc;max-height:62vh;overflow:auto}
.pqv-body{padding:20px 24px}
.pqv-alert{align-items:flex-start;gap:14px;border-radius:12px;padding:16px 18px;margin-bottom:18px}
.pqv-alert .ak-ico{background:#fff}
.pqv-alert-b{flex:1;min-width:0;line-height:1.6;font-size:13px}
.pqv-alert-t{font-weight:700;font-size:15px;display:flex;justify-content:space-between;gap:10px;margin-bottom:2px}
.pqv-flag{font-family:'JetBrains Mono',Consolas,monospace;font-size:12px;font-weight:400;background:#fef3c7;border-radius:6px;padding:2px 8px;white-space:nowrap}
.pqv-reasons{margin:8px 0 0;padding-left:18px;word-break:break-word}
.pqv-kpis{gap:14px;margin-bottom:18px}
.pqv-kpi{padding:14px 16px}
.pqv-kpi-l{color:#64748b;font-size:13px;margin-bottom:6px}
.pqv-kpi-v{font-size:16px;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pqv-kpi-v.pqv-small{white-space:normal;word-break:break-all;line-height:1.5}
.pqv-okc{color:#047857}
.pqv-card{margin-bottom:18px}
.pqv-card-h{padding:14px 20px;background:#f8fafc;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#334155}
.pqv-card-f{display:flex;justify-content:space-between;gap:16px;padding:12px 20px;border-top:1px solid #f1f5f9;font-size:13px;flex-wrap:wrap}
.pqv-ell{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%}
.pqv-parties{display:grid;grid-template-columns:1fr 1fr;gap:20px;padding:20px}
@media(max-width:700px){.pqv-parties{grid-template-columns:1fr}}
.pqv-party{display:flex;gap:14px;min-width:0}
.pqv-avatar{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex:0 0 auto;border:1px solid #e2e8f0;background:#f1f5f9;color:#475569}
.pqv-avatar.pqv-to{background:$as;color:$ad;border-color:$al}
.pqv-party-l{font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em}
.pqv-party-n{font-size:15px;font-weight:700;color:#0f172a;margin:2px 0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pqv-addr{font-family:'JetBrains Mono',Consolas,monospace;font-size:12px;border-radius:4px;padding:2px 8px;background:#f1f5f9;color:#475569;display:inline-block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pqv-addr.pqv-to{background:$as;color:$ad}
.pqv-kv{width:100%;border-collapse:collapse;font-size:12px}
.pqv-kv th,.pqv-kv td{padding:7px 20px;border-top:1px solid #f1f5f9;vertical-align:top;text-align:left;word-break:break-word}
.pqv-kv th{width:220px;color:#475569;font-weight:600;font-family:'JetBrains Mono',Consolas,monospace}
.pqv-kv-cols th{width:auto;background:#f8fafc;font-family:inherit}
.pqv-term{background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0}
.pqv-term-h{display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:12px}
.pqv-lights{display:inline-flex;gap:7px}
.pqv-lights i{width:12px;height:12px;border-radius:50%;display:inline-block;background:#ef4444}
.pqv-lights i:nth-child(2){background:#f59e0b}.pqv-lights i:nth-child(3){background:#10b981}
.pqv-t-copy{margin-left:auto;color:#475569!important;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:3px 10px;font-family:'JetBrains Mono',Consolas,monospace}
.pqv-term-b{margin:0;background:transparent;border:0;color:#1e293b;font-family:'JetBrains Mono',Consolas,monospace;font-size:12px;line-height:1.8;padding:14px 20px;max-height:46vh;overflow:auto;white-space:pre-wrap;word-break:break-all;border-radius:0}
.pqv-t-sec{color:$ad;font-weight:700}
.pqv-t-k{color:#94a3b8}
.pqv-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;background:#fff;border-top:1px solid #e2e8f0;padding:14px 24px;font-size:13px;color:#475569}
.pqv-foot-bt{display:flex;gap:10px}
.pqv-foot-st{display:flex;align-items:center;gap:8px}
</style>";
}

function delete_js(){

	$tpl=new template_admin();
	$msgid=pqv_id_value((string)$_GET["delete-js"]);
	if($msgid==""){return;}
    $instance_id=intval($_GET["instance-id"]);
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	$fn=pqv_function();
	// Depuis la ligne : on retire la ligne. Depuis la fenetre : on la ferme et on rafraichit le tableau.
	$after="$('#$md').remove();";
	if($md==""){$after="dialogInstance1.close();".($fn==""?"":"if(typeof $fn=='function'){{$fn}();}");}
	$tpl->js_confirm_delete("{message} $msgid", "delete", "$msgid||$instance_id",$after);
}
function pqv_id_value(string $id):string{
    $id=trim($id);
    return preg_match('/^[0-9A-Za-z]{6,32}$/',$id)?$id:"";
}

function delete(){
    $tpl=new template_admin();
    if(!pqv_is_admin()){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS}");return false;}
    $tpl->CLEAN_POST();
    $ff=explode("||",(string)$_POST["delete"]);
	$msgid=pqv_id_value((string)$ff[0]);
    $instance_id=intval($ff[1] ?? 0);
	if($msgid==""){echo "invalid queue id";return false;}
	$sock=new sockets();
    $data=$sock->REST_API_POSTFIX("/postfix/queue/delete/$instance_id/$msgid");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo json_last_error_msg();
        return false;
    }
    if (!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("MTA queue: delete message $msgid on instance $instance_id");
}

function pause_queue_js(){
    $instance_id=intval($_GET["instance-id"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/postqueue";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/postqueue.txt";
    $ARRAY["CMD"]="cmd.php?postfix-freeze=yes&instance-id=$instance_id";
    $ARRAY["TITLE"]="{pause_the_queue}";
    $ARRAY["AFTER"]="LoadAjax('postfix-queue-div','$page?table=yes&instance-id=$instance_id');";
    $prgress=base64_encode(serialize($ARRAY));
    $pause_the_queue="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-postfix-queue')";
    $tpl->js_confirm_execute("{pause_the_queue_explain}","pause_queue","yes",$pause_the_queue);


}

function empty_queue_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);
    $empty_queue_js="Loadjs('$page?empty-queue-perform=$instance_id')";
    $tpl->js_confirm_execute("{empty_queue_warning}","empty-queue-perform",$instance_id,$empty_queue_js);

}
function empty_queue_perform():bool{
    $instance_id=intval($_POST["empty-queue-perform"]);
    $sock=new sockets();
    $data=$sock->REST_API_POSTFIX("/postfix/queue/empty/$instance_id");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo json_last_error_msg();
        return false;
    }
    if (!$json->Status){
        echo $json->Error;
        return false;
    }

    return admin_tracks("Empty the MTA Queue for instance $instance_id");
}



/**
 * Liste FERMEE des cadences de surveillance (feature 026).
 *
 * La cle est la spec cron stockee telle quelle: elle part directement au planificateur du daemon,
 * donc valider revient a chercher dans cette table plutot qu'a analyser une expression.
 */
function watchdog_cadences(){
    return array(
        "*/3 * * * *"  => "3 {minutes}",
        "*/5 * * * *"  => "5 {minutes}",
        "*/10 * * * *" => "10 {minutes}",
        "*/30 * * * *" => "30 {minutes}",
        "0 * * * *"    => "1 {hour}",
        "0 */2 * * *"  => "2 {hours}",
        "0 */6 * * *"  => "6 {hours}"
    );
}

/**
 * Duree lisible. Une valeur negative ou nulle rend "-": zero seconde et "pas de mesure" ne
 * doivent pas s'ecrire pareil.
 */
function watchdog_age($secs){
    $secs=intval($secs);
    if($secs<=0){return "-";}
    $d=intval($secs/86400); $h=intval(($secs%86400)/3600); $m=intval(($secs%3600)/60);
    if($d>0){return "{$d}d {$h}h";}
    if($h>0){return "{$h}h {$m}m";}
    if($m>0){return "{$m}m";}
    return "{$secs}s";
}

/**
 * Le panneau « Watchdog » : le widget d'etat a gauche, la fiche de parametres a droite.
 *
 * §9b — l'interrupteur de la fonctionnalite est un WIDGET, pas une ligne parmi quinze. C'est la
 * reponse a la premiere question qu'on se pose en ouvrant la page (« est-ce que ca tourne ? ») ;
 * noyee au milieu de la fiche, elle est indiscernable d'« activer les journaux verbeux ».
 */
function watchdog_pane(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:250px;vertical-align:top'><div id='qwd-status-$instance_id'></div></td>";
    $html[]="<td style='width:99%;padding-left:10px;vertical-align:top'><div id='qwd-flat-$instance_id'></div></td>";
    $html[]="</tr></table>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("qwd-status-$instance_id",$page,"watchdog-status=yes&instance-id=$instance_id",30);
    $html[]="LoadAjaxSilent('qwd-flat-$instance_id','$page?watchdog-flat=yes&instance-id=$instance_id');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

/**
 * Widget d'etat du chien de garde.
 *
 * Vert quand la surveillance tourne et qu'aucune file n'est en alerte, ROUGE des qu'une file l'est :
 * un incident en cours doit se voir sans lire un tableau.
 */
function watchdog_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);

    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/queue/watchdog/status"),true);
    $enabled=(is_array($json) && !empty($json["enabled"]));
    $ever=(is_array($json) && !empty($json["ever_ran"]));

    $alerting=0;
    if(is_array($json) && isset($json["instances"]) && is_array($json["instances"])){
        foreach($json["instances"] as $inst){
            if(empty($inst["queues"]) || !is_array($inst["queues"])){continue;}
            foreach($inst["queues"] as $q){ if(!empty($q["alerting"])){$alerting++;} }
        }
    }

    // Format attendu par widget_engine : name / js / icon (et non TEXT / JS).
    $btn=array();
    $btn[]=array("name"=>"{parameters}","js"=>"Loadjs('$page?parameters-js=yes&instance-id=$instance_id')","icon"=>"fas fa-cogs");

    if(!$enabled){
        echo $tpl->_ENGINE_parse_body($tpl->widget_grey("{watchdog}","{disabled}",$btn));
        return;
    }
    if(!$ever){
        echo $tpl->_ENGINE_parse_body($tpl->widget_grey("{watchdog}","{queuewatchdog.never_ran}",$btn));
        return;
    }
    if($alerting>0){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("{watchdog}","$alerting {alerts}",$btn));
        return;
    }
    echo $tpl->_ENGINE_parse_body($tpl->widget_vert("{watchdog}","{ok}",$btn));
}

/**
 * Panneau en lecture seule du chien de garde (US5).
 *
 * Il ne declenche JAMAIS de mesure: il lit ce que la derniere passe a stocke. Un panneau qui
 * mesure a chaque rafraichissement est un panneau qu'on ouvre une fois.
 */
function watchdog_flat(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/queue/watchdog/status");
    $json=json_decode($data,true);
    if(!is_array($json) || !isset($json["Status"]) || !$json["Status"]){
        // L'API renvoie des JETONS i18n dans Error, mais REST_API_POSTFIX y met de la PROSE pour
        // une panne de transport: on n'encadre que ce qui ressemble a un jeton.
        $err=isset($json["Error"])?$json["Error"]:"";
        if(preg_match("#^[a-z0-9_.]+$#",$err)){$err="{".$err."}";}
        if($err==null){$err="{error}";}
        echo $tpl->_ENGINE_parse_body("<div class='alert alert-warning' style='margin-top:10px'>$err</div>");
        return;
    }

    // L'interrupteur de la fonctionnalite n'est PAS ici : c'est le widget de gauche (§9b).
    // La fiche ne porte que les reglages.
    $tpl->table_form_section("{watchdog}");
    $tpl->table_form_field_js("Loadjs('$page?parameters-js=yes&instance-id=$instance_id')");

    if(!$json["enabled"]){
        // Desactive: on le DIT, au lieu d'afficher des compteurs a zero qui se lisent "tout va bien".
        $tpl->table_form_field_js("");
        $tpl->table_form_field_text("{status}","{disabled}",ico_warning);
        echo $tpl->_ENGINE_parse_body($tpl->table_form_compile(2));
        return;
    }

    $Cadences=watchdog_cadences();
    $spec=isset($json["cadence"])?$json["cadence"]:"";
    $label=isset($Cadences[$spec])?$Cadences[$spec]:$spec;
    $tpl->table_form_field_text("{PostfixQueueScanInterval}",$label,ico_clock);

    $st=isset($json["settings"])?$json["settings"]:array();
    $fast=isset($st["fast_threshold"])?intval($st["fast_threshold"]):0;
    $def=isset($st["deferred_threshold"])?intval($st["deferred_threshold"]):0;
    $ageh=isset($st["deferred_max_age_hours"])?intval($st["deferred_max_age_hours"]):0;
    $tpl->table_form_field_text("{PostfixQueueMaxMails}",$fast,ico_mail);
    $tpl->table_form_field_text("{PostfixQueueDeferredMaxMails}",$def,ico_mail);
    $tpl->table_form_field_text("{PostfixQueueDeferredMaxAgeHours}",$ageh." {hours}",ico_clock);

    if(!$json["ever_ran"]){
        // "Jamais mesure" et "mesure a zero" sont deux reponses differentes (FR-021).
        $tpl->table_form_field_js("");
        $tpl->table_form_section("{status}");
        $tpl->table_form_field_text("{last_scan}","{queuewatchdog.never_ran}",ico_warning);
        echo $tpl->_ENGINE_parse_body($tpl->table_form_compile(2));
        return;
    }

    // switchjs n'est jamais rearme par le framework : sans cette remise a zero, toutes les lignes
    // qui suivent heritent du crayon d'edition de la derniere section. Sur une ligne de MESURE
    // (« Deferred : 48 messages ») un crayon annonce une valeur modifiable qui ne l'est pas.
    $tpl->table_form_field_js("");

    $tpl->table_form_section("{status}");
    $tpl->table_form_field_text("{last_scan}",date("Y-m-d H:i:s",intval($json["last_pass_at"])),ico_history);

    $instances=isset($json["instances"])&&is_array($json["instances"])?$json["instances"]:array();
    foreach($instances as $inst){
        $name=isset($inst["name"])?$inst["name"]:"";
        if(!$inst["measured"]){
            // Une instance non mesurable n'est PAS une instance vide.
            $reason=isset($inst["reason"])?$inst["reason"]:"";
            if(preg_match("#^[a-z0-9_.]+$#",$reason)){$reason="{".$reason."}";}
            $tpl->table_form_field_text($name,$reason,ico_warning,true);
            continue;
        }
        $queues=isset($inst["queues"])&&is_array($inst["queues"])?$inst["queues"]:array();
        foreach($queues as $q){
            $line=intval($q["count"])." {messages}";
            if(intval($q["oldest_age_secs"])>0){
                $line.=" — ".watchdog_age($q["oldest_age_secs"]);
            }
            $alerting=isset($q["alerting"])?$q["alerting"]:false;
            if($alerting){
                $cause=isset($q["cause"])?$q["cause"]:"";
                $line.=" ({queuewatchdog.cause.".$cause."})";
            }
            $tpl->table_form_field_text($name." / ".$q["queue"],$line,ico_mail,$alerting);
        }
        $rem=isset($inst["remedial"])?$inst["remedial"]:array();
        $rec=isset($rem["corrupt_recovered"])?intval($rem["corrupt_recovered"]):0;
        $col=isset($rem["corrupt_collisions"])?intval($rem["corrupt_collisions"]):0;
        if($rec>0 || $col>0){
            $tpl->table_form_field_text("{queuewatchdog.corrupt_recovered}","$rec (+$col {skipped})",ico_check);
        }
    }

    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile(2));
}

/**
 * Graphique d'evolution du remplissage (US5).
 *
 * Charge APRES les chiffres, pour qu'un graphique lent ne retarde jamais la lecture des compteurs.
 */
function watchdog_charts(){
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $hours=intval($_GET["hours"]); if($hours<1){$hours=168;}

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/queue/watchdog/history?instance=$instance_id&hours=$hours");
    $json=json_decode($data,true);
    if(!is_array($json) || !isset($json["Status"]) || !$json["Status"]){return;}

    if(!isset($json["enough_history"]) || !$json["enough_history"]){
        // Sous deux points on DIT pourquoi, plutot que de dessiner un graphique vide dont
        // l'exploitant conclurait que la mesure est cassee.
        echo $tpl->_ENGINE_parse_body("<div class='panel panel-default' style='margin-top:10px'>"
            ."<div class='panel-body'><div class='awc-empty'>{queuewatchdog.history_short}</div></div></div>");
        return;
    }

    $points=isset($json["points"])&&is_array($json["points"])?$json["points"]:array();
    $gaps=isset($json["gaps"])&&is_array($json["gaps"])?$json["gaps"]:array();

    $lab=array(); $inc=array(); $def=array();
    foreach($points as $p){
        $lab[]=date("d/m H:i",intval($p["at"]));
        $inc[]=intval($p["incoming"]);
        $def[]=intval($p["deferred"]);
    }
    $lj=json_encode($lab); $ij=json_encode($inc); $dj=json_encode($def);

    $uid="qwd-fill-".time();
    // §16 — Chart.js n'est PAS charge globalement : il faut le charger DANS cette reponse AJAX et
    // dessiner dans le callback. Un simple `if(typeof Chart==='undefined')return;` sauterait
    // silencieusement le graphique chaque fois qu'il n'est pas deja la.
    $ensure ="function qwdEnsureChart(cb){if(typeof Chart!=='undefined'){cb();return;}"
        ."var s=document.createElement('script');"
        ."s.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';"
        ."s.onload=function(){cb();};document.head.appendChild(s);}";
    $js ="qwdEnsureChart(function(){new Chart(document.getElementById('$uid'),{type:'line',data:{labels:$lj,datasets:[";
    $js.="{label:'".$tpl->javascript_parse_text("{incoming}")."',data:$ij,borderColor:'#c2410c',backgroundColor:'rgba(194,65,12,0.08)',fill:true,tension:0.3,pointRadius:2,spanGaps:false},";
    $js.="{label:'".$tpl->javascript_parse_text("{deferred}")."',data:$dj,borderColor:'#0369a1',backgroundColor:'rgba(3,105,161,0.10)',fill:true,tension:0.3,pointRadius:2,spanGaps:false}]},";
    // responsive + maintainAspectRatio:false DANS un conteneur a hauteur explicite : obligatoire ici,
    // car tabs_default injecte le contenu AVANT d'afficher le panneau — le graphique est donc cree
    // dans un parent display:none et mesurerait une largeur nulle sans cela.
    $js.="options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:true}},scales:{y:{beginAtZero:true}}}});});";

    $note="";
    if(count($gaps)>0){
        // Un trou est un trou: le dire evite qu'une ligne interrompue passe pour une panne du
        // graphique alors qu'elle signale une periode sans aucune mesure.
        $note="<div class='awc-empty' style='margin-top:6px'>{queuewatchdog.history_gaps}: ".count($gaps)."</div>";
    }

    echo $tpl->_ENGINE_parse_body("<div class='panel panel-default' style='margin-top:10px'>"
        ."<div class='panel-heading'>{queuewatchdog.chart_fill}</div>"
        ."<div class='panel-body'><div style='position:relative;height:260px'><canvas id='$uid'></canvas></div>$note</div></div>")
        ."<script>$ensure\n$js</script>";
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $instance_id=intval($_GET["instance-id"]);
    $html=$tpl->page_header("{queue} v$POSTFIX_VERSION",
        "fal fa-list-ul",
        "{postfix_queue_explain}",
        "$page?tabs=yes&instance-id=$instance_id",
        "postfix-queue-$instance_id",
        "progress-postfix-queue",false,
        "table-loader-postfix-queue"
    );

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}



/**
 * Onglets de la page.
 *
 * UN ONGLET PAR MODE DE LECTURE. Le tableau de la file (lignes qu'on cherche et qu'on pagine), la
 * fiche de parametres du chien de garde (valeurs qu'on parcourt) et le graphique (une forme qu'on
 * lit d'un coup d'oeil) sont trois modes differents : empiles sur un meme ecran ils se disputent la
 * hauteur, le graphique passe sous la ligne de flottaison et n'est jamais vu, et un rafraichissement
 * du tableau detruit le canvas auquel l'instance Chart reste accrochee.
 *
 * Le TABLEAU est le premier onglet : c'est celui que tabs_default charge immediatement.
 */
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);

    $array=array();
    $array["{queue}"]      = "$page?table-div=yes&instance-id=$instance_id";
    $array["{watchdog}"]   = "$page?watchdog-pane=yes&instance-id=$instance_id";
    $array["{statistics}"] = "$page?watchdog-charts=yes&instance-id=$instance_id";
    echo $tpl->tabs_default($array);
}

function table_div(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,null,null,null,"&table=yes&instance-id=$instance_id");

}


function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $function=pqv_function();


    $flush_the_queue="Loadjs('$page?flush=$instance_id&function=$function')";
    $scan_the_queue="Loadjs('$page?scan=$instance_id&function=$function')";
    $POSTFIX_QUEUENUM_TIME=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_QUEUENUM_TIME"));

    VERBOSE("POSTFIX_QUEUENUM_TIME=$POSTFIX_QUEUENUM_TIME",__LINE__);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/postqueue";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/postqueue.txt";
    $ARRAY["CMD"]="cmd.php?postfix-freeze=yes&instance-id=$instance_id";
    $ARRAY["TITLE"]="{pause_the_queue}";
    $ARRAY["AFTER"]="LoadAjax('postfix-queue-div','$page?table=yes&instance-id=$instance_id');";
    $prgress=base64_encode(serialize($ARRAY));
    $pause_the_queue="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-postfix-queue')";




    $main=new maincf_multi($instance_id);
    $freeze_delivery_queue=intval($main->GET("freeze_delivery_queue"));
    if($freeze_delivery_queue==1) {$html[]="<div class='alert alert-danger'>{WARN_QUEUE_FREEZE}</div>";}


	$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"$flush_the_queue\">";
    $btn[]="<i class='fas fa-forward'></i> {flush_the_queue2} </label>";

	if($freeze_delivery_queue==0) {
        $btn[] = "<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
        $btn[] = "<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?pause-queue-js=yes&instance-id=$instance_id')\">";
        $btn[] = "<i class='fas fa-stop-circle'></i> {freeze_the_queue} </label>";
    }else{

        $btn[] = "<label class=\"btn btn btn-danger\" OnClick=\"$pause_the_queue\">";
        $btn[] = "<i class='fas fa-play-circle'></i> {unfreeze_the_queue} </label>";
    }



    $btn[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?empty-queue-js=yes&instance-id=$instance_id')\">";
    $btn[] = "<i class='fas fa-trash-alt'></i> {empty_queue} </label>";


    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"$scan_the_queue;\">";
    $btn[]="<i class='fas fa-sync'></i> {actualize} </label>";

    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?parameters-js=yes&instance-id=$instance_id');\">";
    $btn[]="<i class='fas fa-tools'></i> {parameters} </label>";


    $btn[]="</div>";
    $database="/home/artica/SQLITE/postqueue.db";
    if($instance_id>0){
        $database="/home/artica/SQLITE/postqueue.$instance_id.db";
    }

	$q=new lib_sqlite($database);

    $results=$q->QUERY_SQL("SELECT * FROM postqueuep ORDER BY arrival_time DESC LIMIT 0,250");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
	
	$label["deferred"]="label-danger";
	$label["active"]="label-primary";
	$label["incoming"]="label-primary";
	$label["hold"]="label-warning";
	
	$rows=array();
	foreach ($results as $num=>$ligne){
		$rows[]=pfxq_row($tpl,$page,$instance_id,$ligne,$function);
	}

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{from}</th>";
	$html[]="<th class='center'>{queue}</th>";
	$html[]="<th>{size}</th>";
	$html[]="<th>{arrival_time}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='pfxq-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$shown=count($rows);
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown / $shown</span>";
	$html[]="<span class='awc-pg' id='pfxq-pager'></span>";
	$html[]="</div></div></div>";

    $instancename="SMTP Master";
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename="&nbsp;<small>({$ligne["instancename"]})</small>";

        $POSTFIX_QUEUENUM_TIME = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO(sprintf("POSTFIX_QUEUENUM_TIME_%s",$instance_id)));
    }

    $lastscan="";
    if ($POSTFIX_QUEUENUM_TIME > 0) {
            $lastscan = "<br><i>{last_scan}:" . $tpl->time_to_date($POSTFIX_QUEUENUM_TIME, true)."</i>";
        }

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{queue} $instancename v$POSTFIX_VERSION";
    $TINY_ARRAY["ICO"]="fal fa-list-ul";
    $TINY_ARRAY["EXPL"]="{postfix_queue_explain}$lastscan";
    $TINY_ARRAY["URL"]="postfix-queue-$instance_id";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"pfxq-rows","pfxq-pager");
	return true;
}

/**
 * Un message en file: expediteur en identite, destinataire et motif de report en
 * sous-titre (le motif etait glisse en petit sous l'expediteur, dans la meme
 * cellule). Adresses et motif viennent du message, donc d'un tiers: ils sont
 * echappes -- ils etaient rendus bruts.
 */
function pfxq_row($tpl,$page,$instance_id,$ligne,$function=""):string{
	$queue_name=trim((string)$ligne["queue_name"]);
	$queue_id=urlencode((string)$ligne["queue_id"]);
	$arrival=(string)$ligne["arrival_time"];
	$id=md5(serialize($ligne));
	$recipient=(string)$ligne["recipient"];
	$reason=(string)$ligne["delay_reason"];

	$bg="#263238";
	if($queue_name=="deferred"){$bg="#ba1a1a";}
	elseif($queue_name=="hold"){$bg="#865300";}
	elseif($queue_name=="active"||$queue_name=="incoming"){$bg="#006a62";}

	$view=$tpl->icon_loupe(true,"Loadjs('$page?view-js=$queue_id&instance-id=$instance_id&function=$function')",null,"AsPostfixAdministrator");
	$del=$tpl->icon_delete("Loadjs('$page?delete-js=$queue_id&md=$id&instance-id=$instance_id')","AsPostfixAdministrator");

	$h ="<tr id='$id'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='".($queue_name=="deferred"?"awc-ico awc-ico-off":"awc-ico")."'><i class='fas fa-envelope'></i></span>";
	$h.="<span><span class='awc-name'>".htmlspecialchars((string)$ligne["sender"])."</span>";
	if($recipient!==""){$h.="<div class='awc-meta'>&rarr; ".htmlspecialchars($recipient)."</div>";}
	if($reason!==""){$h.="<div class='awc-meta' style='color:#ba1a1a'>".htmlspecialchars($reason)."</div>";}
	$h.="</span></div></td>";
	$h.="<td class='center'><span class='awc-badge' style='background:$bg'>".htmlspecialchars($queue_name)."</span></td>";
	$h.="<td class='awc-mono'>".FormatBytes($ligne["message_size"]/1024)."</td>";
	$h.="<td><span class='awc-seen'>".htmlspecialchars($arrival)."</span>";
	$h.="<div class='awc-seen-sub'>".htmlspecialchars(distanceOfTimeInWords(strtotime($arrival),time(),true))."</div></td>";
	$h.="<td><div class='awc-acts'>$view$del</div></td>";
	$h.="</tr>";
	return $h;
}


