<?php
// Onglet « Fingerprint » de la console web (maquette stitch_browser_fingerprint_access_control).
// Une ligne par NAVIGATEUR (table fingerprints), agregee avec ses passages
// (fingerprints_events). Un modele unique (fpw_model), une fonction par widget.
// Regles appliquees a la connexion (fw.login.php fingerprint()) quand
// ArticaWebFingerPrint=1 : 1 autorise -> formulaire ; 2 refuse -> page sans droits ;
// 0 en attente -> captcha si ArticaWebFingerPrintCaptcha=1 (un captcha reussi passe
// le navigateur a 1, articarest FingerPrintVerifyCaptcha), sinon page sans droits.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

// Tout ici touche l'acces a la console : administrateur systeme seulement,
// pour l'affichage comme pour chaque action (avant : aucune verification).
$GLOBALS["FPW_USERS"]=new usersMenus();
if(!$GLOBALS["FPW_USERS"]->AsSystemAdministrator){
    if(isset($_GET["enable-js"])||isset($_GET["off-js"])||isset($_GET["delete-js"])||isset($_GET["details-js"])){
        header("content-type: application/x-javascript");
        $tpl=new template_admin();
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        exit;
    }
    if(count($_POST)>0){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");
        exit;
    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));
    exit;
}

if(isset($_GET["table"])){fpw_table();exit;}
if(isset($_GET["details-js"])){fpw_details_js();exit;}
if(isset($_GET["details"])){fpw_details();exit;}
if(isset($_GET["enable-js"])){fpw_enable_js();exit;}
if(isset($_GET["enable-popup"])){fpw_enable_popup();exit;}
if(isset($_GET["off-js"])){fpw_off_js();exit;}
if(isset($_GET["delete-js"])){fpw_delete_js();exit;}
if(isset($_POST["fpw-set"])){fpw_set_save();exit;}
if(isset($_POST["fpw-delete"])){fpw_delete_save();exit;}
if(isset($_POST["fpw-enable"])){fpw_enable_save();exit;}
if(isset($_POST["fpw-captcha"])){fpw_captcha_save();exit;}

fpw_host();

// ---------------------------------------------------------------------------
// Entree de l'onglet : un hote recharge apres chaque action.
function fpw_host(){
    $page=CurrentPageName();
    echo "<div id='fpw-host' style='margin-top:10px'></div>"
        ."<script>function FpwReload(){if(document.getElementById('fpw-host')){LoadAjaxSilent('fpw-host','$page?table=yes');}}"
        ."LoadAjax('fpw-host','$page?table=yes');</script>";
}

// Une empreinte FingerprintJS : 20 a 32 caracteres alphanumeriques (colonne VARCHAR(50)).
// Toute valeur venue du navigateur passe par ici avant SQL, URL ou JavaScript.
function fpw_valid_fp($fp):string{
    $fp=trim((string)$fp);
    if(!preg_match('#^[A-Za-z0-9_-]{1,50}$#',$fp)){return "";}
    return $fp;
}

function fpw_esc($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),$s);
}

function fpw_tr(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){$s.=" ".$a;continue;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

function fpw_ago(int $t):string{
    $d=max(0,time()-$t);
    if($d<60){return fpw_tr("fpw_ago_now");}
    if($d<3600){return fpw_tr("fpw_ago_min",array(intval($d/60)));}
    if($d<86400){return fpw_tr("fpw_ago_h",array(intval($d/3600)));}
    $n=intval($d/86400);
    return $n==1?fpw_tr("fpw_ago_day_one"):fpw_tr("fpw_ago_days",array($n));
}

/** "Chrome 131 on Windows" depuis le user agent ; icone de marque associee. */
function fpw_ua(string $ua):array{
    $name="";$ver="";$ico="fas fa-globe";
    $rules=array(
        array('#Edg(?:e|A|iOS)?/(\d+)#',"Edge","fab fa-edge"),
        array('#OPR/(\d+)#',"Opera","fab fa-opera"),
        array('#Firefox/(\d+)#',"Firefox","fab fa-firefox-browser"),
        array('#Chrome/(\d+)#',"Chrome","fab fa-chrome"),
        array('#Version/(\d+)[\d.]* .*Safari/#',"Safari","fab fa-safari"),
    );
    foreach($rules as $r){
        if(preg_match($r[0],$ua,$re)){$name=$r[1];$ver=$re[1];$ico=$r[2];break;}
    }
    $os="";
    if(stripos($ua,"Android")!==false){$os="Android";}
    elseif(preg_match('#iPhone|iPad|iPod#',$ua)){$os="iOS";}
    elseif(stripos($ua,"Windows")!==false){$os="Windows";}
    elseif(stripos($ua,"CrOS")!==false){$os="ChromeOS";}
    elseif(stripos($ua,"Mac OS X")!==false||stripos($ua,"Macintosh")!==false){$os="macOS";}
    elseif(stripos($ua,"Linux")!==false){$os="Linux";}
    if($name===""){return array("label"=>"","ico"=>$ico);}
    $label=trim("$name $ver");
    if($os!==""){$label=fpw_tr("fpw_browser_on",array($label,$os));}
    return array("label"=>$label,"ico"=>$ico);
}

function fpw_model():array{
    $m=array("err"=>"","rows"=>array(),"enabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"))==1,
        "captcha"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrintCaptcha"))==1,
        "nopg"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"))==1,
        "allowed"=>0,"waiting"=>0,"denied"=>0,"visits"=>0,"total"=>0);
    if($m["nopg"]){return $m;}
    $q=new postgres_sql();
    $sql="SELECT f.fingerprint,f.useragent,f.status,COALESCE(e.visits,0) AS visits,COALESCE(e.last,0) AS last,
            COALESCE(e.ips,'') AS ips,COALESCE(l.ip,'') AS lastip
          FROM fingerprints f
          LEFT JOIN (SELECT fingerprint,count(*) AS visits,max(unixtime) AS last,
                     string_agg(DISTINCT host(ipaddr),', ') AS ips FROM fingerprints_events GROUP BY fingerprint) e
                 ON e.fingerprint=f.fingerprint
          LEFT JOIN (SELECT DISTINCT ON (fingerprint) fingerprint,host(ipaddr) AS ip FROM fingerprints_events
                     ORDER BY fingerprint,unixtime DESC) l ON l.fingerprint=f.fingerprint
          ORDER BY COALESCE(e.last,0) DESC LIMIT 1000";
    $res=$q->QUERY_SQL($sql);
    if(!$q->ok){$m["err"]=$q->mysql_error;return $m;}
    while($r=@pg_fetch_array($res)){
        $st=intval($r["status"]);
        if($st==1){$m["allowed"]++;}elseif($st==2){$m["denied"]++;}else{$st=0;$m["waiting"]++;}
        $ips=trim((string)$r["ips"]);
        $m["rows"][]=array("fp"=>(string)$r["fingerprint"],"ua"=>(string)$r["useragent"],"status"=>$st,
            "visits"=>intval($r["visits"]),"last"=>intval($r["last"]),"lastip"=>(string)$r["lastip"],
            "ips"=>$ips,"nips"=>$ips===""?0:count(explode(", ",$ips)));
    }
    $l=$q->mysqli_fetch_array("SELECT count(*) AS tcount FROM fingerprints_events");
    $m["visits"]=is_array($l)?intval($l["tcount"]):0;
    // Les compteurs viennent de la table entiere : le tableau, lui, s'arrete a 1000.
    $l=$q->mysqli_fetch_array("SELECT count(*) AS tcount,
        sum(CASE WHEN status=1 THEN 1 ELSE 0 END) AS a, sum(CASE WHEN status=2 THEN 1 ELSE 0 END) AS d FROM fingerprints");
    if(is_array($l)){
        $m["total"]=intval($l["tcount"]);
        $m["allowed"]=intval($l["a"]);$m["denied"]=intval($l["d"]);$m["waiting"]=max(0,$m["total"]-$m["allowed"]-$m["denied"]);
    }
    return $m;
}

function fpw_table(){
    $tpl=new template_admin();
    $m=fpw_model();
    $h=array();
    $h[]="<div class='fpw'>";
    if($m["nopg"]){
        $h[]="<div class='fpw-note fpw-note-warn'><i class='fas fa-database'></i>{fpw_nopg}</div>";
    }elseif($m["err"]!==""){
        $h[]=$tpl->div_error(fpw_esc($m["err"]));
    }
    $h[]=fpw_hero($m);
    if(!$m["enabled"] && !$m["nopg"]){$h[]=fpw_steps($m);}
    if(count($m["rows"])==0){
        $h[]=fpw_empty();
    }else{
        $h[]="<div class='fpw-kpis'>".fpw_kpi_allowed($m).fpw_kpi_waiting($m).fpw_kpi_denied($m).fpw_kpi_visits($m)."</div>";
        $h[]=fpw_list($m);
    }
    $h[]="</div>";
    echo fpw_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).fpw_js().fpw_tiny();
}

function fpw_tiny():string{
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{fingerprints_webaccess}";
    $TINY_ARRAY["ICO"]="fas fa-fingerprint";
    $TINY_ARRAY["EXPL"]="{fpw_tiny_explain}";
    $TINY_ARRAY["BUTTONS"]="";
    return "<script>NoSpinner();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function fpw_hero(array $m):string{
    $page=CurrentPageName();
    $chips=array(array("fas fa-key","{fpw_chip_password}"));
    if($m["captcha"]){$chips[]=array("fas fa-user-check","{fpw_chip_captcha}");}
    else{$chips[]=array("fas fa-hand","{fpw_chip_manual}");}
    $chips[]=array("fas fa-database","{fpw_chip_pg}");
    $c=array();
    foreach($chips as $ch){$c[]="<span class='fpw-chip'><i class='{$ch[0]}'></i>{$ch[1]}</span>";}
    if($m["enabled"]){
        $sw=$m["captcha"]?"true":"false";
        $state="<div class='fpw-state'><span class='fpw-pill fpw-pill-ok'><i class='fas fa-shield-check'></i>{fpw_on}</span>"
            ."<div class='fpw-sw-line'><button type='button' role='switch' aria-checked='$sw' class='fpw-sw' id='fpw-captcha-sw' OnClick=\"FpwCaptcha(this);\" aria-labelledby='fpw-captcha-l'><span></span></button>"
            ."<span id='fpw-captcha-l'>{fpw_captcha_label}</span></div>"
            ."<button type='button' class='fpw-btn fpw-btn-ghost' OnClick=\"Loadjs('$page?off-js=yes');\"><i class='fas fa-power-off'></i>{fpw_turn_off}</button></div>";
    }else{
        $state="<div class='fpw-state'><span class='fpw-pill'><i class='fas fa-circle-pause'></i>{fpw_off}</span>".fpw_on_button($m)."</div>";
    }
    return "<div class='fpw-hero'><span class='fpw-hero-ico'><i class='fas fa-fingerprint'></i></span><div class='fpw-hero-b'>"
        ."<div class='fpw-hero-t'>{fpw_hero_title}</div><p>{fpw_hero_text}</p><div class='fpw-chips'>".@implode("",$c)."</div></div>$state</div>";
}

/** Le bouton d'activation ; desactive avec sa raison tant qu'aucun navigateur n'est autorise. */
function fpw_on_button(array $m):string{
    $page=CurrentPageName();
    if($m["nopg"]){
        return "<button type='button' class='fpw-btn fpw-btn-pri' disabled aria-describedby='fpw-nopg-r'><i class='fas fa-shield-halved'></i>{fpw_turn_on}</button>"
            ."<span class='fpw-reason' id='fpw-nopg-r'>{fpw_nopg_short}</span>";
    }
    if($m["allowed"]==0){
        return "<button type='button' class='fpw-btn fpw-btn-pri' disabled aria-describedby='fpw-lock-r'><i class='fas fa-shield-halved'></i>{fpw_turn_on}</button>"
            ."<span class='fpw-reason' id='fpw-lock-r'>{fpw_lock_reason}</span>";
    }
    return "<button type='button' class='fpw-btn fpw-btn-pri' OnClick=\"Loadjs('$page?enable-js=yes');\"><i class='fas fa-shield-halved'></i>{fpw_turn_on}</button>";
}

function fpw_step(int $n,bool $done,string $title,string $extra):string{
    $badge=$done?"<span class='fpw-step-n fpw-done'><i class='fas fa-check'></i></span>":"<span class='fpw-step-n'>$n</span>";
    $st=$done?"<span class='fpw-step-k fpw-k-done'>".fpw_tr("fpw_step_done",array($n))."</span>":"<span class='fpw-step-k'>".fpw_tr("fpw_step_n",array($n))."</span>";
    return "<div class='fpw-step'>$badge<div class='fpw-step-b'>$st<div class='fpw-step-t'>$title</div>$extra</div></div>";
}

function fpw_steps(array $m):string{
    $n=count($m["rows"]);
    $s1=fpw_step(1,$n>0,"{fpw_step1}",$n>0?"<div class='fpw-step-s'>".fpw_tr("fpw_step1_count",array($n))."</div>":"");
    $s2=fpw_step(2,$m["allowed"]>0,"{fpw_step2}",$m["allowed"]>0?"<div class='fpw-step-s'>".fpw_tr("fpw_step2_count",array($m["allowed"]))."</div>":"");
    $s3=fpw_step(3,false,"{fpw_step3}","<div class='fpw-step-a'>".fpw_on_button($m)."</div>");
    return "<div class='fpw-steps'>$s1$s2$s3</div>";
}

function fpw_kpi(string $l,int $n,string $s,string $ico,string $cls):string{
    return "<div class='fpw-kpi'><div><div class='fpw-kpi-l'>$l</div><div class='fpw-kpi-n'>".number_format($n)."</div>"
        ."<div class='fpw-kpi-s'>$s</div></div><span class='fpw-kpi-ico $cls'><i class='$ico'></i></span></div>";
}
function fpw_kpi_allowed(array $m):string{return fpw_kpi("{fpw_allowed}",$m["allowed"],"{fpw_allowed_sub}","fas fa-circle-check","fpw-ok");}
function fpw_kpi_waiting(array $m):string{return fpw_kpi("{fpw_waiting_long}",$m["waiting"],$m["captcha"]?"{fpw_waiting_sub_captcha}":"{fpw_waiting_sub_manual}","fas fa-hourglass-half","fpw-warn");}
function fpw_kpi_denied(array $m):string{return fpw_kpi("{fpw_denied}",$m["denied"],"{fpw_denied_sub}","fas fa-ban","fpw-bad");}
function fpw_kpi_visits(array $m):string{return fpw_kpi("{fpw_visits}",$m["visits"],"{fpw_visits_sub}","fas fa-clock-rotate-left","");}

function fpw_status_pill(int $st):string{
    if($st==1){return "<span class='fpw-pill fpw-pill-ok'><i class='fas fa-circle-check'></i>{fpw_allowed}</span>";}
    if($st==2){return "<span class='fpw-pill fpw-pill-bad'><i class='fas fa-ban'></i>{fpw_denied}</span>";}
    return "<span class='fpw-pill fpw-pill-warn'><i class='fas fa-hourglass-half'></i>{fpw_waiting}</span>";
}

function fpw_row(array $r):string{
    $page=CurrentPageName();
    $fp=fpw_valid_fp($r["fp"]);
    if($fp===""){return "";}
    $ua=fpw_ua($r["ua"]);
    $label=$ua["label"]!==""?fpw_esc($ua["label"]):"{fpw_unknown_browser}";
    $short=strlen($fp)>12?substr($fp,0,4)."&hellip;".substr($fp,-4):$fp;
    $state=$r["status"]==1?"allowed":($r["status"]==2?"denied":"waiting");
    $q=strtolower($fp." ".$ua["label"]." ".$r["ua"]." ".$r["ips"]);
    $last=$r["last"]>0?"<span class='fpw-ago' tabindex='0' title=\"".date("Y-m-d H:i:s",$r["last"])."\">".fpw_ago($r["last"])."</span>":"<span class='fpw-muted'>&#8212;</span>";
    $lastip=$r["lastip"]!==""?"<div class='fpw-mono'>".fpw_esc($r["lastip"])."</div>":"";
    $addr=$r["nips"]==0?"<span class='fpw-muted'>&#8212;</span>"
        :"<span class='fpw-ago' tabindex='0' title=\"".fpw_esc($r["ips"])."\">".($r["nips"]==1?fpw_tr("fpw_one_address"):fpw_tr("fpw_n_addresses",array($r["nips"])))."</span>";
    $a1=$r["status"]==1?"true":"false"; $a2=$r["status"]==2?"true":"false";
    $acts="<div class='fpw-seg' role='radiogroup' aria-label=\"{fpw_decision}\">"
        ."<button type='button' role='radio' aria-checked='$a1' class='fpw-seg-b".($r["status"]==1?" fpw-on-ok":"")."' OnClick=\"FpwSet('$fp',1);\">{fpw_allow}</button>"
        ."<button type='button' role='radio' aria-checked='$a2' class='fpw-seg-b".($r["status"]==2?" fpw-on-bad":"")."' OnClick=\"FpwSet('$fp',2);\">{fpw_deny}</button></div>"
        ."<button type='button' class='fpw-btn fpw-btn-ghost fpw-btn-sm' OnClick=\"Loadjs('$page?details-js=$fp');\"><i class='fas fa-circle-info'></i>{fpw_details}</button>"
        ."<button type='button' class='fpw-icon' aria-label=\"{fpw_remove}\" title=\"{fpw_remove}\" OnClick=\"Loadjs('$page?delete-js=$fp');\"><i class='fas fa-trash-can'></i></button>";
    return "<tr data-state='$state' data-q=\"".htmlspecialchars($q,ENT_QUOTES,"UTF-8")."\">"
        ."<td><div class='fpw-ident'><span class='fpw-bico'><i class='{$ua["ico"]}'></i></span><div><div class='fpw-name'>$label</div>"
        ."<div class='fpw-mono' title=\"$fp\">$short <button type='button' class='fpw-copy' aria-label=\"{fpw_copy}\" title=\"{fpw_copy}\" OnClick=\"FpwCopy('$fp',this);\"><i class='far fa-copy'></i></button></div></div></div></td>"
        ."<td>".fpw_status_pill($r["status"])."</td><td>$last$lastip</td><td>$addr</td>"
        ."<td class='fpw-n'>".number_format($r["visits"])."</td><td class='fpw-right'><div class='fpw-acts'>$acts</div></td></tr>";
}

function fpw_list(array $m):string{
    $rows=array();
    foreach($m["rows"] as $r){$rows[]=fpw_row($r);}
    $n=count($m["rows"]);
    $seg=array("all"=>array("{fpw_all}",$n),"waiting"=>array("{fpw_waiting}",$m["waiting"]),"allowed"=>array("{fpw_allowed}",$m["allowed"]),"denied"=>array("{fpw_denied}",$m["denied"]));
    $s=array();
    foreach($seg as $k=>$o){$s[]="<button type='button' role='radio' aria-checked='false' class='fpw-fseg-b' data-v='$k' OnClick=\"FpwView('$k');\">{$o[0]} ({$o[1]})</button>";}
    return "<div class='fpw-card'><div class='fpw-card-h'><div class='fpw-card-t'>{fpw_list_title}</div><div class='fpw-card-s'>{fpw_list_explain}</div><div class='fpw-card-s'><i class='fas fa-circle-info'></i> {fpw_update_note} {fpw_retention_note}</div></div>"
        ."<div class='fpw-tools'><label class='fpw-search'><i class='fas fa-magnifying-glass'></i><input type='text' id='fpw-q' autocomplete='off' placeholder=\"{fpw_search}\" aria-label=\"{fpw_search}\"></label>"
        ."<div class='fpw-fseg' role='radiogroup' aria-label=\"{fpw_filter}\">".@implode("",$s)."</div></div>"
        ."<div class='fpw-scroll'><table class='fpw-tbl'><thead><tr><th scope='col'>{fpw_browser}</th><th scope='col'>{status}</th>"
        ."<th scope='col'>{fpw_last_seen}</th><th scope='col'>{fpw_addresses}</th><th scope='col' class='fpw-n'>{fpw_visits_col}</th>"
        ."<th scope='col' class='fpw-right'>{actions}</th></tr></thead><tbody id='fpw-rows'>".@implode("",$rows)."</tbody></table></div>"
        .($m["total"]>count($m["rows"])?"<div class='fpw-note fpw-note-in'><i class='fas fa-circle-info'></i>".fpw_tr("fpw_limit_note",array(number_format(count($m["rows"])),number_format($m["total"])))."</div>":"")
        ."<div class='fpw-nomatch' id='fpw-nomatch' hidden>{fpw_no_match}</div>"
        ."<div class='fpw-foot'><span id='fpw-shown' data-tpl=\"".htmlspecialchars(fpw_tr("fpw_showing"),ENT_QUOTES,"UTF-8")."\"></span>"
        ."<span class='fpw-pg' id='fpw-pg'><button type='button' class='fpw-pg-b' id='fpw-prev' aria-label=\"{fpw_prev}\"><i class='fas fa-chevron-left'></i></button>"
        ."<span id='fpw-pgn'></span><button type='button' class='fpw-pg-b' id='fpw-next' aria-label=\"{fpw_next}\"><i class='fas fa-chevron-right'></i></button></span></div></div>";
}

function fpw_empty():string{
    return "<div class='fpw-card fpw-empty'><i class='fas fa-fingerprint'></i><div class='fpw-empty-t'>{fpw_empty_title}</div><p>{fpw_empty_text}</p></div>";
}

function fpw_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $copied=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{fpw_copied}"),ENT_QUOTES,"UTF-8"));
    return "<script>
(function(){
 var S=window.FpwState=window.FpwState||{view:'all',q:'',page:0}, PS=25;
 var tb=document.getElementById('fpw-rows'), inp=document.getElementById('fpw-q');
 function draw(){
  var b=document.querySelectorAll('.fpw-fseg-b');
  for(var i=0;i<b.length;i++){var on=b[i].getAttribute('data-v')===S.view;b[i].setAttribute('aria-checked',on?'true':'false');b[i].className='fpw-fseg-b'+(on?' fpw-fon':'');}
  if(!tb){return;}
  var q=S.q.toLowerCase(),hit=[],t=tb.rows.length;
  for(var j=0;j<t;j++){var r=tb.rows[j];r.style.display='none';if((S.view==='all'||r.getAttribute('data-state')===S.view)&&(q===''||(r.getAttribute('data-q')||'').indexOf(q)>=0)){hit.push(r);}}
  var n=hit.length,pages=Math.max(1,Math.ceil(n/PS));if(S.page>=pages){S.page=pages-1;}if(S.page<0){S.page=0;}
  for(var k=S.page*PS;k<Math.min(n,(S.page+1)*PS);k++){hit[k].style.display='';}
  var pg=document.getElementById('fpw-pg');if(pg){pg.hidden=(pages<=1);}
  var pn=document.getElementById('fpw-pgn');if(pn){pn.textContent=(S.page+1)+' / '+pages;}
  var pv=document.getElementById('fpw-prev'),nx=document.getElementById('fpw-next');
  if(pv){pv.disabled=(S.page==0);pv.onclick=function(){S.page--;draw();};}
  if(nx){nx.disabled=(S.page>=pages-1);nx.onclick=function(){S.page++;draw();};}
  var nm=document.getElementById('fpw-nomatch');if(nm){nm.hidden=(n>0||t==0);}
  var sh=document.getElementById('fpw-shown');if(sh){sh.textContent=(sh.getAttribute('data-tpl')||'').replace('%1',n).replace('%2',t);}
 }
 window.FpwView=function(v){S.view=v;S.page=0;draw();};
 if(inp){inp.value=S.q;inp.onkeyup=function(){S.q=inp.value.replace(/^\\s+|\\s+\$/g,'');S.page=0;draw();};}
 function post(d){ \$.post('$page',d,function(r){r=String(r||'').trim();if(r.length>0){alert(r);}if(typeof FpwReload=='function'){FpwReload();}}); }
 window.FpwSet=function(fp,st){post({'fpw-set':fp,'status':st});};
 window.FpwCaptcha=function(b){var on=b.getAttribute('aria-checked')!=='true';b.setAttribute('aria-checked',on?'true':'false');post({'fpw-captcha':on?1:0});};
 window.FpwCopy=function(v,b){
  var ok=function(){b.title=$copied;b.setAttribute('aria-label',$copied);};
  if(navigator.clipboard && window.isSecureContext){navigator.clipboard.writeText(v).then(ok,function(){fb();});return;}
  fb();
  function fb(){var t=document.createElement('textarea');t.value=v;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();try{document.execCommand('copy');ok();}catch(e){}document.body.removeChild(t);}
 };
 draw();
})();
</script>";
}

// ---------------------------------------------------------------------------
// Actions. Chaque handler POST ne rend QUE l'erreur et trace l'action.

function fpw_set_save(){
    $tpl=new template_admin();
    $fp=fpw_valid_fp($_POST["fpw-set"]);
    $st=intval($_POST["status"] ?? -1);
    if($fp===""||!in_array($st,array(0,1,2),true)){echo $tpl->_ENGINE_parse_body("{fpw_err_bad}");return;}
    $q=new postgres_sql();
    $q->QUERY_SQL_PARAMS("UPDATE fingerprints SET status=$1 WHERE fingerprint=$2",array($st,$fp));
    if(!$q->ok){echo $q->mysql_error;return;}
    // Le dernier navigateur autorise ne peut pas etre refuse tant que la verification
    // est active : la console se fermerait a tout le monde.
    if($st!=1 && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"))==1 && fpw_allowed_count()==0){
        $q->QUERY_SQL_PARAMS("UPDATE fingerprints SET status=1 WHERE fingerprint=$1",array($fp));
        echo $tpl->_ENGINE_parse_body("{fpw_err_last_allowed}");
        return;
    }
    admin_tracks("Set Web console fingerprint $fp status to $st");
}

function fpw_allowed_count():int{
    $q=new postgres_sql();
    $l=$q->mysqli_fetch_array("SELECT count(*) AS tcount FROM fingerprints WHERE status=1");
    return is_array($l)?intval($l["tcount"]):0;
}

function fpw_delete_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $fp=fpw_valid_fp($_GET["delete-js"]);
    if($fp===""){$tpl->js_error("{fpw_err_bad}");return;}
    $tpl->js_confirm_delete("{fpw_remove_confirm}","fpw-delete",$fp,"FpwReload();");
}

function fpw_delete_save(){
    $tpl=new template_admin();
    $fp=fpw_valid_fp($_POST["fpw-delete"]);
    if($fp===""){echo $tpl->_ENGINE_parse_body("{fpw_err_bad}");return;}
    $q=new postgres_sql();
    $l=$q->mysqli_fetch_array("SELECT status FROM fingerprints WHERE fingerprint='".pg_escape_string2($fp)."'");
    if(is_array($l) && intval($l["status"])==1 && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"))==1 && fpw_allowed_count()<=1){
        echo $tpl->_ENGINE_parse_body("{fpw_err_last_allowed}");
        return;
    }
    $q->QUERY_SQL_PARAMS("DELETE FROM fingerprints_events WHERE fingerprint=$1",array($fp));
    if(!$q->ok){echo $q->mysql_error;return;}
    $q->QUERY_SQL_PARAMS("DELETE FROM fingerprints WHERE fingerprint=$1",array($fp));
    if(!$q->ok){echo $q->mysql_error;return;}
    admin_tracks("Remove Web console fingerprint $fp");
}

function fpw_enable_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(fpw_allowed_count()==0){$tpl->js_error("{fpw_lock_reason}");return;}
    $page=CurrentPageName();
    $tpl->js_dialog1("{fpw_turn_on}","$page?enable-popup=yes",560);
}

function fpw_enable_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $cap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrintCaptcha"))==1?"true":"false";
    $h="<div class='fpw fpw-dlg'><p class='fpw-dlg-p'>{fpw_on_explain}</p>"
        ."<div class='fpw-opt'><button type='button' role='switch' aria-checked='$cap' class='fpw-sw' id='fpw-on-cap' aria-labelledby='fpw-on-cap-l' OnClick=\"this.setAttribute('aria-checked',this.getAttribute('aria-checked')==='true'?'false':'true');\"><span></span></button>"
        ."<div><div class='fpw-opt-t' id='fpw-on-cap-l'>{fpw_captcha_label}</div><div class='fpw-opt-h'>{fpw_captcha_help}</div></div></div>"
        ."<div class='fpw-note fpw-note-warn'><i class='fas fa-triangle-exclamation'></i>{fpw_on_warning}</div>"
        ."<div class='fpw-dlg-f'><button type='button' class='fpw-btn fpw-btn-ghost' OnClick=\"dialogInstance1.close();\">{cancel}</button>"
        ."<button type='button' class='fpw-btn fpw-btn-pri' OnClick=\"FpwEnable();\"><i class='fas fa-shield-halved'></i>{fpw_turn_on}</button></div></div>"
        ."<script>function FpwEnable(){var c=document.getElementById('fpw-on-cap').getAttribute('aria-checked')==='true'?1:0;"
        ."\$.post('$page',{'fpw-enable':1,'captcha':c},function(r){r=String(r||'').trim();if(r.length>0){alert(r);return;}dialogInstance1.close();if(typeof FpwReload=='function'){FpwReload();}});}</script>";
    echo fpw_style().$tpl->_ENGINE_parse_body($h);
}

function fpw_off_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $tpl->js_confirm_execute("{fpw_off_confirm}","fpw-enable","0","FpwReload();");
}

function fpw_enable_save(){
    $tpl=new template_admin();
    $on=intval($_POST["fpw-enable"])==1;
    if($on){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"))==1){echo $tpl->_ENGINE_parse_body("{fpw_nopg_short}");return;}
        if(fpw_allowed_count()==0){echo $tpl->_ENGINE_parse_body("{fpw_lock_reason}");return;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaWebFingerPrintCaptcha",intval($_POST["captcha"] ?? 0)==1?1:0);
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaWebFingerPrint",$on?1:0);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/reconfigure");
    admin_tracks(($on?"Enable":"Disable")." Web console fingerprint verification");
}

function fpw_captcha_save(){
    $on=intval($_POST["fpw-captcha"])==1?1:0;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaWebFingerPrintCaptcha",$on);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/reconfigure");
    admin_tracks(($on?"Enable":"Disable")." captcha for unknown browsers of the Web console");
}

// ---------------------------------------------------------------------------
// Detail d'un navigateur : les composants lisibles, polices et plugins replies.

function fpw_details_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $fp=fpw_valid_fp($_GET["details-js"]);
    if($fp===""){$tpl->js_error("{fpw_err_bad}");return;}
    $page=CurrentPageName();
    $tpl->js_dialog1("{fpw_details}","$page?details=$fp",720);
}

function fpw_comp($c,string $k){
    if(!is_object($c) || !property_exists($c,$k) || !is_object($c->$k) || !property_exists($c->$k,"value")){return null;}
    return $c->$k->value;
}

function fpw_details(){
    $tpl=new template_admin();
    $fp=fpw_valid_fp($_GET["details"]);
    if($fp===""){echo $tpl->_ENGINE_parse_body($tpl->div_error("{fpw_err_bad}"));return;}
    $q=new postgres_sql();
    $res=$q->QUERY_SQL_PARAMS("SELECT useragent,status,jsondata FROM fingerprints WHERE fingerprint=$1",array($fp));
    $l=$res?@pg_fetch_array($res):false;
    if(!is_array($l)){echo $tpl->_ENGINE_parse_body($tpl->div_error("{fpw_err_bad}"));return;}
    $json=json_decode((string)base64_decode((string)$l["jsondata"]));
    $c=(is_object($json) && property_exists($json,"components"))?$json->components:null;
    $ua=(string)$l["useragent"];
    if(strlen($ua)<3 && is_object($json) && property_exists($json,"userAgent")){$ua=(string)$json->userAgent;}
    $ips=array();
    $r2=$q->QUERY_SQL_PARAMS("SELECT DISTINCT host(ipaddr) AS ip FROM fingerprints_events WHERE fingerprint=$1",array($fp));
    if($r2){while($x=@pg_fetch_array($r2)){$ips[]=(string)$x["ip"];}}
    $rows=array();
    if(count($ips)){$rows[]=array("{fpw_addresses_seen}",fpw_esc(implode(", ",$ips)));}
    $v=fpw_comp($c,"platform"); if(is_string($v)&&$v!==""){$rows[]=array("{fpw_system}",fpw_esc($v));}
    $v=fpw_comp($c,"timezone"); if(is_string($v)&&$v!==""){$rows[]=array("{fpw_timezone}",fpw_esc($v));}
    $v=fpw_comp($c,"screenResolution"); if(is_array($v)&&count($v)){$rows[]=array("{fpw_screen}",fpw_esc(implode(" x ",$v)));}
    $v=fpw_comp($c,"languages");
    if(is_array($v)){$langs=array();foreach($v as $a){foreach((array)$a as $b){$langs[]=(string)$b;}} if(count($langs)){$rows[]=array("{fpw_languages}",fpw_esc(implode(", ",array_unique($langs))));}}
    $v=fpw_comp($c,"videoCard");
    if(is_object($v)){$card=property_exists($v,"renderer")?(string)$v->renderer:(property_exists($v,"vendor")?(string)$v->vendor:"");if($card!==""){$rows[]=array("{fpw_graphics}",fpw_esc($card));}}
    $rows[]=array("{fpw_user_agent}","<span class='fpw-mono fpw-wrap'>".fpw_esc($ua)."</span>");
    $ui=fpw_ua($ua);
    $h=array();
    $h[]="<div class='fpw fpw-dlg'><div class='fpw-ident'><span class='fpw-bico'><i class='{$ui["ico"]}'></i></span><div><div class='fpw-name'>"
        .($ui["label"]!==""?fpw_esc($ui["label"]):"{fpw_unknown_browser}")."</div><div class='fpw-mono'>$fp</div></div>".fpw_status_pill(intval($l["status"]))."</div>";
    $h[]="<dl class='fpw-dl'>";
    foreach($rows as $r){$h[]="<dt>{$r[0]}</dt><dd>{$r[1]}</dd>";}
    $h[]="</dl>";
    foreach(array("fonts"=>"fpw_fonts","plugins"=>"fpw_plugins") as $k=>$tok){
        $v=fpw_comp($c,$k);
        if(!is_array($v) || count($v)==0){continue;}
        $items=array();
        foreach($v as $x){$items[]=fpw_esc(is_object($x)&&property_exists($x,"name")?$x->name:(is_string($x)?$x:""));}
        $h[]="<details class='fpw-more'><summary>".fpw_tr($tok,array(count($items)))."</summary><p>".implode(", ",array_filter($items))."</p></details>";
    }
    $h[]="</div>";
    echo fpw_style().$tpl->_ENGINE_parse_body(@implode("\n",$h));
}

function fpw_style():string{
    if(isset($GLOBALS["FPW_CSS"])){return "";}
    $GLOBALS["FPW_CSS"]=true;
    $accent="#005447";$dark="#003d33";$soft="#e6eeed";$line="#bfd4d1";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    $mono="'JetBrains Mono',Menlo,Consolas,monospace";
    return "<style>
.fpw{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;max-width:1320px}
.fpw *{box-sizing:border-box}
.fpw-hero{display:flex;gap:16px;align-items:flex-start;background:#f2f6f6;border:1px solid $line;border-radius:12px;padding:20px 22px}
.fpw-hero-ico{width:42px;height:42px;flex:0 0 42px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:18px}
.fpw-hero-b{flex:1;min-width:0}
.fpw-hero-t{font-size:18px;font-weight:600;margin-bottom:4px}
.fpw-hero-b p{margin:0 0 12px;color:#334155;line-height:1.55;max-width:860px}
.fpw-chips{display:flex;flex-wrap:wrap;gap:8px}
.fpw-chip{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid $line;border-radius:999px;padding:3px 11px;font-size:13px;color:#1e293b}
.fpw-chip i,.fpw-chip svg{color:$accent}
.fpw-state{flex:0 0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;flex-direction:column;align-items:flex-start;gap:10px;max-width:320px}
.fpw-reason{font-size:12.5px;color:#64748b;max-width:280px}
.fpw-pill{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;border-radius:999px;padding:2px 10px;white-space:nowrap;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}
.fpw-pill-ok{background:$soft;color:$dark;border-color:$line}
.fpw-pill-warn{background:#fffbeb;color:#b45309;border-color:#fde68a}
.fpw-pill-bad{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.fpw-sw-line{display:flex;align-items:center;gap:10px;font-size:13px;color:#334155}
.fpw-sw{position:relative;width:38px;height:22px;flex:0 0 38px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0;transition:background .15s}
.fpw-sw span{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .15s}
.fpw-sw[aria-checked=true]{background:$accent}
.fpw-sw[aria-checked=true] span{left:19px}
.fpw-btn{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid transparent;white-space:nowrap;transition:background .15s}
.fpw-btn-pri{background:$accent;color:#fff}
.fpw-btn-pri:hover{background:$dark;color:#fff}
.fpw-btn-pri[disabled]{background:#cbd5e1;color:#475569;cursor:not-allowed}
.fpw-btn-ghost{background:#fff;color:#334155;border-color:#e2e8f0}
.fpw-btn-ghost:hover{background:#f8fafc}
.fpw-btn-sm{height:32px;padding:0 12px}
.fpw-btn:focus-visible,.fpw-seg-b:focus-visible,.fpw-fseg-b:focus-visible,.fpw-sw:focus-visible,.fpw-icon:focus-visible,.fpw-copy:focus-visible{outline:2px solid $accent;outline-offset:2px}
.fpw-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.fpw-step{display:flex;gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px}
.fpw-step-n{width:30px;height:30px;flex:0 0 30px;border-radius:50%;background:#f1f5f9;color:#475569;font-weight:700;display:flex;align-items:center;justify-content:center}
.fpw-step-n.fpw-done{background:$accent;color:#fff}
.fpw-step-k{font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#64748b}
.fpw-k-done{color:#15803d}
.fpw-step-t{font-weight:600;margin-top:2px}
.fpw-step-s{font-size:13px;color:#64748b;margin-top:2px}
.fpw-step-a{margin-top:10px;display:flex;flex-direction:column;align-items:flex-start;gap:6px}
.fpw-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.fpw-kpi{display:flex;justify-content:space-between;gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.fpw-kpi-l{font-size:13px;color:#475569}
.fpw-kpi-n{font-size:26px;font-weight:700;line-height:1.25;margin:2px 0;font-variant-numeric:tabular-nums}
.fpw-kpi-s{font-size:13px;color:#64748b}
.fpw-kpi-ico{width:38px;height:38px;flex:0 0 38px;border-radius:10px;background:#f1f5f9;color:#64748b;display:flex;align-items:center;justify-content:center;font-size:15px}
.fpw-kpi-ico.fpw-ok{background:#ecfdf5;color:#15803d}
.fpw-kpi-ico.fpw-warn{background:#fffbeb;color:#b45309}
.fpw-kpi-ico.fpw-bad{background:#fef2f2;color:#b91c1c}
.fpw-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04);overflow:hidden}
.fpw-card-h{padding:18px 22px 4px}
.fpw-card-t{font-size:16px;font-weight:600}
.fpw-card-s{font-size:13px;color:#64748b;margin-top:2px}
.fpw-tools{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 22px;flex-wrap:wrap}
.fpw-search{display:flex;align-items:center;gap:8px;border:1px solid #e2e8f0;border-radius:8px;padding:0 12px;height:38px;width:380px;max-width:100%;color:#64748b;margin:0;font-weight:400}
.fpw-search input{border:0;outline:0;flex:1;font-size:14px;color:#0f172a;background:transparent;min-width:0}
.fpw-search:focus-within{border-color:$accent;box-shadow:0 0 0 2px $soft}
.fpw-fseg{display:inline-flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:3px;gap:2px;flex-wrap:nowrap}
.fpw-fseg-b{border:0;background:transparent;color:#475569;font-size:13px;font-weight:600;padding:6px 12px;border-radius:8px;cursor:pointer;white-space:nowrap}
.fpw-fseg-b.fpw-fon{background:#fff;color:$dark;box-shadow:0 1px 2px rgba(15,23,42,.08)}
.fpw-scroll{overflow-x:auto}
.fpw-tbl{width:100%;border-collapse:collapse}
.fpw-tbl th{font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#475569;text-align:left;padding:10px 18px;border-top:1px solid #f1f5f9;border-bottom:1px solid #e2e8f0;background:#fafbfc;white-space:nowrap}
.fpw-tbl td{padding:12px 18px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.fpw-ident{display:flex;align-items:center;gap:12px}
.fpw-bico{width:36px;height:36px;flex:0 0 36px;border-radius:10px;background:#f1f5f9;color:#334155;display:flex;align-items:center;justify-content:center;font-size:17px}
.fpw-name{font-weight:600;white-space:nowrap}
.fpw-mono{font-family:$mono;font-size:12.5px;color:#64748b}
.fpw-wrap{word-break:break-all}
.fpw-copy{border:0;background:none;color:#94a3b8;cursor:pointer;padding:0 2px}
.fpw-copy:hover{color:$accent}
.fpw-ago{border-bottom:1px dotted #94a3b8;cursor:help;white-space:nowrap}
.fpw-muted{color:#64748b}
.fpw-n{text-align:right!important;font-variant-numeric:tabular-nums;white-space:nowrap}
.fpw-right{text-align:right!important}
.fpw-acts{display:inline-flex;align-items:center;gap:8px;justify-content:flex-end;white-space:nowrap}
.fpw-seg{display:inline-flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden}
.fpw-seg-b{border:0;background:#fff;color:#475569;font-size:13px;font-weight:600;padding:0 12px;height:32px;cursor:pointer}
.fpw-seg-b+.fpw-seg-b{border-left:1px solid #e2e8f0}
.fpw-seg-b.fpw-on-ok{background:$accent;color:#fff}
.fpw-seg-b.fpw-on-bad{background:#b91c1c;color:#fff}
.fpw-icon{border:1px solid transparent;background:none;color:#64748b;width:32px;height:32px;border-radius:8px;cursor:pointer}
.fpw-icon:hover{color:#b91c1c;border-color:#fecaca;background:#fef2f2}
.fpw-note-in{margin:0 22px 12px}
.fpw-nomatch{padding:18px 22px;color:#64748b;text-align:center}
.fpw-foot{padding:12px 22px;font-size:13px;color:#64748b;display:flex;justify-content:space-between;align-items:center;gap:12px}
.fpw-pg{display:inline-flex;align-items:center;gap:8px}
.fpw-pg[hidden]{display:none}
.fpw-pg-b{width:30px;height:30px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#334155;cursor:pointer}
.fpw-pg-b:disabled{opacity:.4;cursor:default}
.fpw-empty{text-align:center;padding:36px 20px;display:flex;flex-direction:column;align-items:center;gap:8px}
.fpw-empty > i,.fpw-empty > svg{font-size:42px;color:#94a3b8}
.fpw-empty-t{font-size:17px;font-weight:600}
.fpw-empty p{margin:0;color:#64748b;max-width:560px}
.fpw-note{display:flex;gap:10px;align-items:flex-start;font-size:13px;border-radius:10px;padding:10px 14px;background:#f2f6f6;border:1px solid #e2e8f0;color:#334155}
.fpw-note-warn{background:#fffbeb;border-color:#fde68a;color:#78350f}
.fpw-note-warn i,.fpw-note-warn svg{color:#b45309;margin-top:2px}
.fpw-dlg{gap:16px;padding:4px}
.fpw-dlg-p{margin:0;color:#334155}
.fpw-opt{display:flex;gap:12px;align-items:flex-start}
.fpw-opt-t{font-weight:600}
.fpw-opt-h{font-size:12.5px;color:#64748b;margin-top:2px}
.fpw-dlg-f{display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #e2e8f0;padding-top:14px}
.fpw-dl{display:grid;grid-template-columns:170px 1fr;gap:8px 16px;margin:0}
.fpw-dl dt{font-size:13px;color:#64748b;font-weight:400}
.fpw-dl dd{margin:0}
.fpw-more summary{cursor:pointer;color:$accent;font-weight:600;font-size:13px}
.fpw-more p{font-size:12.5px;color:#475569;margin:8px 0 0}
@media (max-width:1200px){.fpw-kpis{grid-template-columns:1fr 1fr}.fpw-steps{grid-template-columns:1fr}.fpw-hero{flex-wrap:wrap}.fpw-state{max-width:none;width:100%}}
@media (prefers-reduced-motion:reduce){.fpw *{transition:none!important}}
</style>";
}
