<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
# SP9, Security increased.
# v10/10/2026
if(isset($_GET["verbose"])){
    ini_set('html_errors',0);
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string','');
    ini_set('error_append_string','');
    ini_set("log_errors", 1);
    ini_set("error_log", "/var/log/php.log");
    $GLOBALS["VERBOSE"]=true;
}
{if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.progress.sign.inc');
// Marque blanche : fichier recent, absent d'une appliance plus ancienne -> garde is_file().
if(is_file(dirname(__FILE__).'/ressources/class.brand.inc')){include_once(dirname(__FILE__).'/ressources/class.brand.inc');}
// Every answer is JavaScript meant for Loadjs(): never let a browser sniff it as HTML.
header("X-Content-Type-Options: nosniff");
if(isset($_GET["refresh-menus"])){RefreshMenus();exit;}
if(isset($_GET["tiny-page"])){tiny_page();exit;}
if(isset($_GET["content"])){build_progress();exit;}
if(isset($_GET["startup"])){startup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
if(isset($_GET["no-privs"])){no_privs();exit;}
if(isset($_GET["no-lic"])){no_lic();exit;}
if(isset($_GET["confirm-before"])){confirm_before();exit;}
if(isset($_POST["Execute"])){admin_tracks("Confirmed {$_POST["Execute"]}");exit;}
if(isset($_GET["badprivs"])){badprivs();exit;}
if(isset($_GET["browser-items"])){browser_items();exit;}

// Unknown request: the query string used to be echoed back into the script (reflected
// XSS). Say nothing about it.
header("content-type: application/x-javascript");
echo "console.error('fw.progress.php: request not understood');\n";

// Reads a signed task (content=, startup=, build-js=) and checks its signature BEFORE
// decoding anything. Returns null when the link is unsigned, altered, or was built in
// another session. On success, $GLOBALS["FW_PROGRESS_SIGNED"] holds "<b64>&sig=<hmac>"
// to carry the signature on to the next step.
function fw_progress_read_signed($param){
    $b64=fw_progress_b64_normalize(isset($_GET[$param])?$_GET[$param]:"");
    $sig=isset($_GET["sig"])?strtolower(trim((string)$_GET["sig"])):"";
    if($b64==="" || !fw_progress_verify($b64,$sig,"task")){return null;}
    $ARRAY=fw_progress_decode($b64);
    if(count($ARRAY)==0){return null;}
    $GLOBALS["FW_PROGRESS_SIGNED"]="$b64&sig=$sig";
    return $ARRAY;
}

// The step that runs the REST route is only ever requested by Loadjs() (jQuery $.ajax,
// same origin), which always sends this header; a <script>, an <img>, a link or a form
// from elsewhere cannot.
function fw_progress_is_xhr(){
    if(!isset($_SERVER["HTTP_X_REQUESTED_WITH"])){return false;}
    return strtolower(trim((string)$_SERVER["HTTP_X_REQUESTED_WITH"]))==="xmlhttprequest";
}

// Text placed between single quotes in the emitted JavaScript.
// Texte de la carte (titre, etape, libelle d'echec) : insere par innerHTML apres un
// base64_decode() de default.js qui rend des octets bruts, donc tout caractere non ASCII
// voyage en entite HTML. javascript_parse_text() retirait les accents (« Arret », « Lance »).
function fw_progress_html($tpl,$value):string{
    $html=(string)$tpl->_ENGINE_parse_body((string)$value);
    $html=str_replace(array("\r\n","\r","\n"),array(" "," "," "),$html);
    if(!function_exists("mb_encode_numericentity")){return $html;}
    if(!mb_check_encoding($html,"UTF-8")){$html=mb_convert_encoding($html,"UTF-8","ISO-8859-1");}
    return (string)mb_encode_numericentity($html,array(0x80,0x10FFFF,0,0x1FFFFF),"UTF-8");
}

function fw_progress_js_str($value){
    return str_replace(array("\\","'","\r","\n","<",">"),array("\\\\","\\'","","","\\x3c","\\x3e"),(string)$value);
}

function RefreshMenus(){
    header("content-type: application/x-javascript");
    $f[]="if(document.getElementById('left-barr')){";
    $f[]="  LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes&removeclass=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('top-barr')){";
    $f[]="LoadAjaxSilent('top-barr','fw-top-bar.php');";
    $f[]="}";

    echo @implode("\n",$f);
}
function browser_items():bool{
    VERBOSE($_GET["browser-items"],__LINE__);
    $data=base64_decode($_GET["browser-items"]);
    VERBOSE("Data:$data",__LINE__);
    $BrowserItem=@unserialize($data,array("allowed_classes"=>false));

    if($GLOBALS["VERBOSE"]){print_r($BrowserItem);}
    if(!is_array($BrowserItem)){$BrowserItem=array();}
    $html=array();
    if(isset($BrowserItem["title"])){
        $title=base64_encode($BrowserItem["title"]);
        if(isset($_SERVER["HTTP_X_META_PREFIX"])){
            $title=base64_encode($BrowserItem["title"]." - via meta");
        }

        $html[]="$.address.title(base64_decode('$title'));";
    }

    if(isset( $BrowserItem["url"])){
        $url=(string)$BrowserItem["url"];
        $HTTP_X_ARTICA_SUBFOLDER=$url;
        $root="/";
        echo "// BrowserItem(url) == ".str_replace(array("\r","\n"),"",$url)."\n";
        if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
            $root="/{$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]}/";
        }
        if(isset($_SERVER["HTTP_X_META_PREFIX"])){
            $root="/{$_SERVER["HTTP_X_META_PREFIX"]}/";
        }
        $url=str_replace($root,"",$url);
        if(substr($url,0,1)<>"/"){
            $url="/$url";
        }

        $html[] = "$.address.state('".fw_progress_js_str($root)."');";
        $html[] = "$.address.value('".fw_progress_js_str($url)."');";
    }

    if(count($html)==0){
        VERBOSE("COUNT === 0",__LINE__);
        return false;
    }

    if($GLOBALS["VERBOSE"]){
        print_r($html);
    }

    header("content-type: application/x-javascript");
    echo @implode("\n",$html);
    return true;
}
function  tiny_page(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    // Not signed (built by ~570 pages, see class.progress.sign.inc): decoded without
    // objects, and only ever turned into page-header markup in the caller's own browser.
    $TINY_ARRAY=@unserialize(base64_decode(str_replace(" ","+",(string)$_GET["tiny-page"])),array("allowed_classes"=>false));
    if(!is_array($TINY_ARRAY)){$TINY_ARRAY=array();}

    $tinyuniq=md5(serialize($TINY_ARRAY).microtime(true).time());
    $issets[]="TITLE";
    $issets[]="ICO";
    $issets[]="EXPL";
    $issets[]="URL";
    $issets[]="BUTTONS";
    $issets[]="JSAFTER";
    foreach ($issets as $key){
        if(!isset($TINY_ARRAY[$key])){$TINY_ARRAY[$key]=null;}
    }
    $addonclass=null;
    $addonclass_explain=null;
    $addonclassend_explain=null;
    $explain=null;
    // Feuille globale perimee : le shell ne charge angular.css.php qu'une fois. Une console
    // ouverte avant une mise a jour recoit les pages en classes ak- (class.ui.kit.inc) sans
    // leur style. Si --ak-fill (kit) ou --afrm-v (formulaires, afrm_css()) est absent, on
    // recharge la feuille une seule fois.
    $js=array();
    $js[]="(function(){try{if(window.__akCssReload){return;}var d=document.documentElement;var cs=getComputedStyle(d);if(cs.getPropertyValue('--ak-fill')!==''&&cs.getPropertyValue('--afrm-v')!==''){return;}window.__akCssReload=1;var l=document.createElement('link');l.rel='stylesheet';l.href='/angular.css.php?ak='+(new Date()).getTime();document.getElementsByTagName('head')[0].appendChild(l);}catch(e){}})();";
    $title=$TINY_ARRAY["TITLE"];
    $ico=$TINY_ARRAY["ICO"];
    if(isset($TINY_ARRAY["EXPL"])) {
        $explain = $TINY_ARRAY["EXPL"];
    }

    $url=$TINY_ARRAY["URL"];
    $buttons=$TINY_ARRAY["BUTTONS"];
    if(isset($TINY_ARRAY["DANGER"])){
        if($TINY_ARRAY["DANGER"]==true){
            $addonclass="text-danger ";
            $addonclass_explain="<span class=\"text-danger\">";
            $addonclassend_explain="</span>";
        }
    }

    if($title<>null){
        $title=$tpl->_ENGINE_parse_body($title);
        // Pentest 2026-11-04 : TITLE et EXPL portent souvent des donnees (recherche,
        // nom d'hote, valeur en base) et finissent en innerHTML : filtre HTML, puis
        // antislash double AVANT l'apostrophe (sinon \' refermait la chaine).
        $title=html_safe_fragment($title);
        $title=str_replace("\\","\\\\",$title);

        if(isset($_SERVER["HTTP_X_META_PREFIX"])){
            $title=$title." <small>(via Meta)</small>";
        }

        $title=str_replace("'","\'",$title);
        $title=str_replace("\n","\\n",$title);
        $title_text=$tpl->javascript_parse_text($title);


        $title_text=str_replace("'","\'",$title_text);
        $title_text_header=strip_tags($title_text);



        $hostname=php_uname('n');
        $js[]="$.address.title('$hostname: $title_text_header');";
        $js[]="if(document.getElementById('tiny-title')){";
        $js[]="document.getElementById('tiny-title').innerHTML='<span id=\"$tinyuniq\">$addonclass_explain$title$addonclassend_explain</span>'";
        $js[]="}";
    }
    if($ico<>null) {
        if (!strpos($ico, "/")) {
            $js[] = "if(document.getElementById('tiny-ico')){";
            $js[] = "document.getElementById('tiny-ico').innerHTML='<i class=\"{$addonclass}fa-8x $ico\"></i>'";
            $js[] = "}";
        }else {
            $js[] = "if(document.getElementById('tiny-ico')){";
            $js[] = "document.getElementById('tiny-ico').innerHTML='<img class=\"{$addonclass}\" src='$ico'>'";
            $js[] = "}";
        }
    }
    $js[]="if(document.getElementById('tiny-explain')){";
    if($explain<>null){
        $explain=$tpl->_ENGINE_parse_body($explain);
        $explain=html_safe_fragment($explain); // Pentest 2026-11-04 (XSS stockee)
        $explain=str_replace("\\","\\\\",$explain);
        $explain=str_replace("'","\'",$explain);
        $explain=str_replace("\n","\\n",$explain);
        $js[]="document.getElementById('tiny-explain').innerHTML='$addonclass_explain$explain$addonclassend_explain';";

    }
    $js[]="}";

    //$js[]="if(!document.getElementById('tiny-button')){ alert('tiny button not exists'); }";
    
    $js[]="if(document.getElementById('tiny-button')){";
    if($buttons<>null){
        $buttons=$tpl->_ENGINE_parse_body($buttons);
        $buttons=str_replace("'","\'",$buttons);
        $buttons=str_replace("\n","",$buttons);
        $buttons=str_replace("\r","",$buttons);
        $buttons=str_replace("\t","",$buttons);
        $js[]="document.getElementById('tiny-button').innerHTML='$buttons'";

    }else{
        $js[]="document.getElementById('tiny-button').innerHTML='';";
    }
    $js[]="}";


    $divs[]="tiny-explain:bouncein";
    $divs[]="tiny-ico:flip";
    $divs[]="tiny-title:shake";
    $divs[]="tiny-button:slideInLeft";
    $t=time();
    $js[] = "$('#MainContent').removeClass('animated');";
    $js[] = "function Animated$t(){";
    foreach ($divs as $animatedDiv) {
        $tt=explode(":",$animatedDiv);
        $xdiv=$tt[0];
        $animation=$tt[1];
        $js[] = "if(document.getElementById('$xdiv')){";
        $js[] = "$('#$xdiv').removeAttr('class').attr('class', '');";
        $js[] = "$('#$xdiv').addClass('animated');";
        $js[] = "$('#$xdiv').addClass('$animation');";
        $js[] = "}\n";
    }
    $js[]="}";

    if($url<>null) {
        $HTTP_X_ARTICA_SUBFOLDER="/";
        if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
            $HTTP_X_ARTICA_SUBFOLDER="/$HTTP_X_ARTICA_SUBFOLDER/$url";
        }
        if(isset($_SERVER["HTTP_X_META_PREFIX"])){
            $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_META_PREFIX"]."/";
        }
        if(substr($url,0,1)<>"/"){
            $url="/$url";
        }
        $js[] = "$.address.state('$HTTP_X_ARTICA_SUBFOLDER');";
        $js[] = "$.address.value('$url');";
    }
    $js[]="Animated$t()";

    if(!isset($TINY_ARRAY["JSAFTER"])){
        $TINY_ARRAY["JSAFTER"]="";
    }
    if(is_null($TINY_ARRAY["JSAFTER"])){
        $TINY_ARRAY["JSAFTER"]="";
    }

    if(strlen($TINY_ARRAY["JSAFTER"])>3) {
        $TINY_ARRAY["JSAFTER"]=str_replace("%s",$tinyuniq,$TINY_ARRAY["JSAFTER"]);
        $js[] = $TINY_ARRAY["JSAFTER"];
    }

echo @implode("\n",$js);
}
function confirm_before():bool{
    $tpl=new template_admin();
    // The text AND the script run once confirmed come from the URL: both are signed
    // together by framework_buildjs() (fw_progress_confirm_args).
    $confirm_b64=fw_progress_b64_normalize(isset($_GET["confirm-before"])?$_GET["confirm-before"]:"");
    $jscode_b64=fw_progress_b64_normalize(isset($_GET["jscode"])?$_GET["jscode"]:"");
    $sig=isset($_GET["sig"])?strtolower(trim((string)$_GET["sig"])):"";
    if($confirm_b64==="" || $jscode_b64==="" ||
        !fw_progress_verify(fw_progress_confirm_payload($confirm_b64,$jscode_b64),$sig,"confirm")){
        return fw_progress_refuse("confirm-before");
    }
    $confirm_before=base64_decode($confirm_b64);
    $jscode=base64_decode($jscode_b64);
    return $tpl->js_confirm_execute($confirm_before,"Execute",$confirm_before,$jscode);
}
function no_privs():bool{
	$tpl=new templates();
	echo "<div class=text-danger>".$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}")."</div>";
    return true;
}
function badprivs():bool{
    $tpl=new template_admin();
    return $tpl->js_error("{ERROR_NO_PRIVS2}");
}
function no_lic():bool{
	$tpl=new templates();
	echo "<div class=text-danger>".$tpl->_ENGINE_parse_body("{this_feature_is_disabled_corp_license}")."</div>";
    return true;
}
// Carte de progression (maquette stitch_refonte_barre_de_progression) : en-tete
// icone + titre de la tache + etape courante + pourcentage, puis la piste.
// Les accents suivent la marque ; repli sur les verts de la maquette.
function fw_progress_css():string{
    $accent="#10b981";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$dark=$a["accent_dark"];
    }
    $soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$wash="rgba(16,185,129,.06)";$glow="rgba(16,185,129,.4)";
    if(function_exists("brand_rgba")){
        $v=brand_rgba($accent,".12");if($v<>""){$soft=$v;}
        $v=brand_rgba($accent,".35");if($v<>""){$line=$v;}
        $v=brand_rgba($accent,".06");if($v<>""){$wash=$v;}
        $v=brand_rgba($accent,".4");if($v<>""){$glow=$v;}
    }
    $c=array();
    $c[]=".fwp-card{background:#fff;border:1px solid rgba(226,232,240,.9);border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.05);overflow:hidden;margin:10px 0 12px;text-align:left;box-sizing:border-box}";
    $c[]=".fwp-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;padding:16px 24px 12px;border-bottom:1px solid #f1f5f9;background:linear-gradient(90deg,$wash,#fff 55%,rgba(255,255,255,0))}";
    $c[]=".fwp-id{display:flex;align-items:center;gap:12px;min-width:0;flex:1 1 260px}";
    $c[]=".fwp-ico{width:32px;height:32px;border-radius:8px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;flex:0 0 auto;font-weight:700;font-size:15px;line-height:1}";
    $c[]=".fwp-spin{width:16px;height:16px;border-radius:50%;border:2.5px solid $line;border-top-color:$dark;box-sizing:border-box;animation:fwpSpin .8s linear infinite}";
    $c[]=".fwp-glyph{display:none}";
    $c[]=".fwp-txt{min-width:0}";
    $c[]=".fwp-title{font-size:14px;font-weight:600;color:#0f172a;letter-spacing:-.01em;line-height:1.35;margin:0;overflow-wrap:anywhere}";
    $c[]=".fwp-step{font-size:12px;color:#64748b;line-height:1.4;margin:2px 0 0;overflow-wrap:anywhere}";
    $c[]=".fwp-step a{color:$dark;text-decoration:underline}";
    $c[]=".fwp-pct{font-size:24px;font-weight:700;color:#0f172a;letter-spacing:-.02em;line-height:1;font-variant-numeric:tabular-nums;white-space:nowrap}";
    $c[]=".fwp-body{padding:20px 24px}";
    $c[]=".fwp-track{position:relative;width:100%;height:14px;padding:2px;background:#f1f5f9;border:1px solid rgba(226,232,240,.8);border-radius:999px;box-shadow:inset 0 1px 2px rgba(15,23,42,.06);overflow:hidden;box-sizing:border-box}";
    $c[]=".fwp-fill{position:relative;height:100%;min-width:6px;border-radius:999px;background:linear-gradient(90deg,$dark,$accent,$dark);transition:width .5s ease-out;overflow:hidden;animation:fwpGlow 3s ease-in-out infinite}";
    $c[]=".fwp-shine{position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(90deg,rgba(255,255,255,0),rgba(255,255,255,.35),rgba(255,255,255,0));transform:translateX(-100%);animation:fwpShimmer 2s infinite}";
    $c[]=".fwp-ok .fwp-spin,.fwp-err .fwp-spin{display:none}";
    $c[]=".fwp-ok .fwp-glyph,.fwp-err .fwp-glyph{display:inline}";
    $c[]=".fwp-ok .fwp-shine,.fwp-err .fwp-shine{display:none}";
    $c[]=".fwp-ok .fwp-fill,.fwp-err .fwp-fill{animation:none}";
    $c[]=".fwp-ok .fwp-pct{color:$dark}";
    $c[]=".fwp-err .fwp-head{background:linear-gradient(90deg,rgba(239,68,68,.06),#fff 55%,rgba(255,255,255,0))}";
    $c[]=".fwp-err .fwp-ico{background:#fee2e2;color:#dc2626}";
    $c[]=".fwp-err .fwp-fill{background:linear-gradient(90deg,#ef4444,#dc2626)}";
    $c[]=".fwp-err .fwp-pct{color:#dc2626;font-size:16px}";
    $c[]=".fwp-err .fwp-title{color:#b91c1c}";
    $c[]=".fwp-big .fwp-title{font-size:18px}";
    $c[]=".fwp-big .fwp-step{font-size:13px}";
    $c[]=".fwp-big .fwp-pct{font-size:32px}";
    $c[]=".fwp-big .fwp-track{height:22px}";
    $c[]="@keyframes fwpSpin{to{transform:rotate(360deg)}}";
    $c[]="@keyframes fwpShimmer{100%{transform:translateX(100%)}}";
    $c[]="@keyframes fwpGlow{0%,100%{opacity:.85}50%{opacity:1;box-shadow:0 0 6px $glow}}";
    $c[]="@media (prefers-reduced-motion:reduce){.fwp-spin,.fwp-shine,.fwp-fill{animation:none}}";
    return @implode("",$c);
}

// JavaScript emis une fois par tache (reponse content=) : injecte la feuille une
// seule fois dans <head> et definit FwProg(), seul point qui touche la carte.
// st = run|ok|err ; t et s = titre et etape en base64 (HTML), null = inchanges ;
// label remplace le pourcentage affiche (ex. "Failed").
function fw_progress_helper_js():string{
    $css=json_encode(fw_progress_css(),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
    return "(function(){
    if(!document.getElementById('fwp-css')){
        var st=document.createElement('style');st.id='fwp-css';st.type='text/css';
        st.appendChild(document.createTextNode($css));
        (document.head||document.getElementsByTagName('head')[0]).appendChild(st);
    }
    window.FwProg=function(id,st,prc,t,s,label){
        var c=document.getElementById('main-'+id);
        if(!c){return false;}
        c.className='fwp-card fwp-'+st+(c.getAttribute('data-big')==='1'?' fwp-big':'');
        var p=parseInt(prc,10);
        if(isNaN(p)||p<0){p=0;}
        if(p>100){p=100;}
        var b=document.getElementById('barr-'+id);
        if(b){b.style.width=p+'%';b.setAttribute('aria-valuenow',p);}
        var e=document.getElementById('pct-'+id);
        if(e){e.innerHTML=(label?label:p+'%');}
        var g=document.getElementById('glyph-'+id);
        if(g){g.innerHTML=(st==='err'?'!':'&#10003;');}
        if(t!==null){var x=document.getElementById('title-'+id);if(x){x.innerHTML=base64_decode(t);}}
        if(s!==null){var y=document.getElementById('step-'+id);if(y){y.innerHTML=base64_decode(s);}}
        return true;
    };
})();\n";
}

// Squelette HTML de la carte. Les textes y sont deja traduits.
function fw_progress_card($myid,$title,$step,$big):string{
    $bigattr=$big?"1":"0";
    $bigcls=$big?" fwp-big":"";
    return "<div class=\"fwp-card fwp-run$bigcls\" id=\"main-$myid\" data-big=\"$bigattr\">
<div class=\"fwp-head\">
  <div class=\"fwp-id\">
    <div class=\"fwp-ico\"><span class=\"fwp-spin\"></span><span class=\"fwp-glyph\" id=\"glyph-$myid\"></span></div>
    <div class=\"fwp-txt\"><div class=\"fwp-title\" id=\"title-$myid\">$title</div><div class=\"fwp-step\" id=\"step-$myid\">$step</div></div>
  </div>
  <div class=\"fwp-pct\" id=\"pct-$myid\">2%</div>
</div>
<div class=\"fwp-body\"><div class=\"fwp-track\"><div class=\"fwp-fill\" id=\"barr-$myid\" style=\"width:2%\" role=\"progressbar\" aria-valuemin=\"0\" aria-valuemax=\"100\" aria-valuenow=\"2\"><div class=\"fwp-shine\"></div></div></div></div>
</div>";
}

function build_progress():bool{
	header("content-type: application/x-javascript");
	$tpl        = new templates();
	$page       = CurrentPageName();
    $badpath    =PROGRESS_DIR . PROGRESS_DIR;
    // Signature checked BEFORE anything is decoded. (The old "numeric content + md5"
    // form never defined $ARRAY, so it never ran anything: no page builds it.)
    $ARRAY=fw_progress_read_signed("content");
    if($ARRAY===null){return fw_progress_refuse("content");}
    $signed=$GLOBALS["FW_PROGRESS_SIGNED"];
    $stylebig=isset($ARRAY["BIG"]);

	$id=fw_progress_safe_id(isset($_GET["mainid"])?$_GET["mainid"]:"");
    $myid=md5(microtime());
	$t=time();
    if(!isset($ARRAY["PROGRESS_FILE"])){
        $ARRAY["PROGRESS_FILE"]=null;
    }
    if(!isset($ARRAY["LOG_FILE"])){$ARRAY["LOG_FILE"]="";}
    if(!isset($ARRAY["TITLE"])){$ARRAY["TITLE"]="";}

    $ARRAY["LOG_FILE"]=trim($ARRAY["LOG_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace("PROGRESS_DIR",PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    if(!is_null($ARRAY["PROGRESS_FILE"])) {
        $ARRAY["PROGRESS_FILE"] = str_replace("PROGRESS_DIR",PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);
        $ARRAY["PROGRESS_FILE"] = str_replace($badpath, PROGRESS_DIR, $ARRAY["PROGRESS_FILE"]);
        $ARRAY["PROGRESS_FILE"] = str_replace("\n","",$ARRAY["PROGRESS_FILE"]);
        $ARRAY["PROGRESS_FILE"] = str_replace(" ","",$ARRAY["PROGRESS_FILE"]);
        echo "// PROGRESS_FILE = <".fw_progress_js_str($ARRAY["PROGRESS_FILE"]).">\n";
    }
    echo "// Framework = ".fw_progress_js_str(isset($ARRAY["CMD"])?$ARRAY["CMD"]:"")."\n";
    echo fw_progress_helper_js();

	$title=fw_progress_html($tpl,$ARRAY["TITLE"]);
	$title2=fw_progress_html($tpl,"{please_wait_preparing_settings}");
    $htmlContent=base64_encode(fw_progress_card($myid,$title,$title2,$stylebig));

    // Sans fichier de progression la tache ne peut pas etre suivie : la carte
    // s'affiche directement en echec au lieu d'ecrire dans des id inexistants.
    $next="Loadjs('$page?startup=$signed&mainid=$id&myid=$myid&t=$t');";
	if(is_null($ARRAY["PROGRESS_FILE"])) {
        $failed=base64_encode("Progress file not set L.".__LINE__);
        $next="FwProg('$myid','err',100,null,'$failed','".fw_progress_js_str(fw_progress_html($tpl,"{failed}"))."');";
    }
	$html="// Line:".__LINE__."
	function f$myid(){
		if(document.getElementById('reconfigure-service-div') ){
			document.getElementById('reconfigure-service-div').style.marginTop='0px';
			document.getElementById('reconfigure-service-div').className='';
			document.getElementById('reconfigure-service-div').innerHTML='';
		}

        if(! document.getElementById('$id') ){
               alert('div layer $id not found');
               return false;
        }
		document.getElementById('$id').innerHTML=base64_decode('$htmlContent');
	    $next
	}
	
	f$myid();";
	echo $html;
	return true;
}
function  startup():bool{
    $badpath    =PROGRESS_DIR ."/". PROGRESS_DIR;
    if($GLOBALS["VERBOSE"]){
        error_log(__LINE__." OK");
    }

	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$id=fw_progress_safe_id(isset($_GET["mainid"])?$_GET["mainid"]:"");
	$myid=fw_progress_safe_id(isset($_GET["myid"])?$_GET["myid"]:"");
    $md5_uri=null;
	$t=fw_progress_safe_id(isset($_GET["t"])?$_GET["t"]:"");

    // This step runs the REST route: Loadjs() only (X-Requested-With), and a signature
    // made in this session — both checked before anything is decoded.
    if(!fw_progress_is_xhr()){return fw_progress_refuse("startup without X-Requested-With");}
    $ARRAY=fw_progress_read_signed("startup");
    if($ARRAY===null){return fw_progress_refuse("startup");}
    $signed=$GLOBALS["FW_PROGRESS_SIGNED"];
    if(!isset($ARRAY["CMD"])){$ARRAY["CMD"]=null;}
    if(!isset($ARRAY["PROGRESS_FILE"])){$ARRAY["PROGRESS_FILE"]=null;}
    if(!isset($ARRAY["LOG_FILE"])){$ARRAY["LOG_FILE"]=null;}
    if(!isset($ARRAY["TITLE"])){$ARRAY["TITLE"]=null;}

	$CMD=$ARRAY["CMD"];
    if(isset($ARRAY["PRIV"]) && !fw_progress_priv_allowed($ARRAY["PRIV"])){
        $what="Web console: task refused, privilege ".preg_replace('/[^A-Za-z|]/','',(string)$ARRAY["PRIV"])." missing";
        error_log("Artica progress: $what");
        admin_tracks($what);
        return $tpl->js_error("{ERROR_NO_PRIVS2}");
    }
    if(!fw_progress_cmd_safe($CMD)){
        return fw_progress_refuse("startup: route outside its prefix");
    }
	if($CMD<>null){
        $err=RUN_CMD($CMD);
        if(strlen($err)>1){
            return $tpl->js_error($err);
        }
    }
    $ARRAY["PROGRESS_FILE"]=str_replace("PROGRESS_DIR",PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace("PROGRESS_DIR",PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["PROGRESS_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);

	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];
	

	$failed=fw_progress_js_str(fw_progress_html($tpl,"{failed}"));
	if($GLOBALS["PROGRESS_FILE"]==null){
		$err=base64_encode("Progress file not set");
		echo "if(window.FwProg){FwProg('$myid','err',100,null,'$err','$failed');}";
		return true;
	}
	
	if(strlen($GLOBALS["PROGRESS_FILE"])<5){
		$err=base64_encode("Progress file ".htmlspecialchars($GLOBALS["PROGRESS_FILE"])." corrupted");
		echo "if(window.FwProg){FwProg('$myid','err',100,null,'$err','$failed');}";
		return true;
	}
	
	$title=$tpl->javascript_parse_text($ARRAY["TITLE"]);
    admin_tracks("Launch task $title ($CMD)");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
	
	$html="
//$CMD
function Step1$t(){
	if(window.FwProg){FwProg('$myid','run',5,null,null);}
	Loadjs('$page?build-js=$signed&mainid=$id{$md5_uri}&myid=$myid&t=$t',false,{retryStatuses:[403]});
}
setTimeout(\"Step1$t()\",1000);\n";
	echo $html;
	return true;
}
function RUN_CMD($CMD):string{

    $sock=new sockets();
    if(preg_match("#^ppac:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_API_PROXYPAC($CMD)",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_API_PROXYPAC($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            writelogs("REST_API_PROXYPAC($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>".$json->Info;
        }
        return "";
    }

    if(preg_match("#^firecrack:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_API_FIRECR($CMD)",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_API_FIRECR($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            writelogs("REST_API_FIRECR($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>".$json->Info;
        }
        return "";
    }
    if(preg_match("#^watch:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_ARTWATCH($CMD)",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_ARTWATCH($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            writelogs("REST_API($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>".$json->Info;
        }
        return "";
    }

    if(preg_match("#^nginx-pjson:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_API_NGINX_POST_JSON($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_API_NGINX_POST_JSON($CMD,array());
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            $info="";
            if(property_exists($json,"Info")){
                $info=$json->Info;
            }
            writelogs("REST_API_NGINX_POST_JSON($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>$info";
        }
        return "";
    }
    if(preg_match("#^apipost:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_API_POST($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_API_POST($CMD,array());
        $json = json_decode($data,true);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }

        if(isset($json["success"]) && $json["success"]){
            return "";
        }

        if(!isset($json["Status"])){
            return "Status false<br>$data<br>Protocol error";
        }

        if(!$json["Status"]){
            $info="";
            if(isset($json["Info"])){
                $info=$json["Info"];
            }
            writelogs("REST_API_POST($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>$info";
        }
        return "";
    }



    if(preg_match("#^nginx:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_API_NGINX($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_API_NGINX($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            $info="";
            if(property_exists($json,"Info")){
                $info=$json->Info;
            }
            writelogs("REST_API_NGINX($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>$info";
        }
        return "";
    }

    if(preg_match("#^postfix:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_API_POSTFIX($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_API_POSTFIX($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            $info="";
            if(property_exists($json,"Info")){
                $info=$json->Info;
            }
            writelogs("REST_API_POSTFIX($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>$info";
        }
        return "";
    }


    if(preg_match("#^suricata:(.+)$#",$CMD,$match)){
        $CMD=$match[1];
        writelogs("REST_API_SURICATA($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
        $data=$sock->REST_API_SURICATA($CMD);
        $json = json_decode($data);
        if (json_last_error() > JSON_ERROR_NONE) {
            return "Decoding data".json_last_error()."<br>$sock->mysql_error";
        }
        if(!$json->Status){
            $info="";
            if(property_exists($json,"Info")){
                $info=$json->Info;
            }
            writelogs("REST_API_SURICATA($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
            return "Status false<br>$data<br>$info";
        }
        return "";
    }



    if (substr($CMD, 0, 1) === '/') {
            writelogs("REST_API($CMD)",__FUNCTION__,__FILE__,__LINE__);
            $data=$sock->REST_API($CMD);
            $json = json_decode($data);
            if (json_last_error() > JSON_ERROR_NONE) {
                return "Decoding data".json_last_error()."<br>$sock->mysql_error";
            }
            if(!$json->Status){
                $info="";
                if(property_exists($json,"Info")){
                    $info=$json->Info;
                    }
                writelogs("REST_API($CMD) Status=FALSE",__FUNCTION__,__FILE__,__LINE__);
                return "Status false<br>$data<br>$info";
            }
        return "";
    }


    writelogs("getFrameWork($CMD)",__FUNCTION__,__FILE__,__LINE__);
    $sock->getFrameWork($CMD);
    return "";

}
function buildjs():bool{
	$time=time();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
    $badpath    =PROGRESS_DIR ."/". PROGRESS_DIR;
	$id             = fw_progress_safe_id(isset($_GET["mainid"])?$_GET["mainid"]:"");
	$myid           = fw_progress_safe_id(isset($_GET["myid"])?$_GET["myid"]:"");
	$REFRESH_MENU   = 0;
	$t              = fw_progress_safe_id(isset($_GET["t"])?$_GET["t"]:"");
    $md5_uri        = null;
	$ORDER          = 0;
	$PROGRESS_DIR   = "/usr/share/artica-postfix/ressources/logs/web";

    // AFTER / AFTER_FAILED / BEFORE are sent back as JavaScript: signed task only.
    $ARRAY=fw_progress_read_signed("build-js");
    if($ARRAY===null){return fw_progress_refuse("build-js");}
    $signed=$GLOBALS["FW_PROGRESS_SIGNED"];
    if(!isset($ARRAY["PROGRESS_FILE"])){$ARRAY["PROGRESS_FILE"]=null;}
    if(!isset($ARRAY["LOG_FILE"])){$ARRAY["LOG_FILE"]=null;}
    if(!isset($ARRAY["CMD"])){$ARRAY["CMD"]=null;}
    if(!isset($ARRAY["TITLE"])){$ARRAY["TITLE"]=null;}
    $_GET["md5file"]=fw_progress_safe_id(isset($_GET["md5file"])?$_GET["md5file"]:"");
    echo "// $badpath\n";
    $ARRAY["PROGRESS_FILE"]=str_replace("PROGRESS_DIR",$PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace("PROGRESS_DIR",$PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["LOG_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["LOG_FILE"]);
    $ARRAY["PROGRESS_FILE"]=str_replace($badpath,PROGRESS_DIR,$ARRAY["PROGRESS_FILE"]);
    if(!isset($ARRAY["BEFORE"])){$ARRAY["BEFORE"]=null;}
    if(!isset($ARRAY["AFTER"])){$ARRAY["AFTER"]=null;}
	$CMD=$ARRAY["CMD"];
	$GLOBALS["PROGRESS_FILE"]=$ARRAY["PROGRESS_FILE"];
	$GLOBALS["LOG_FILE"]=$ARRAY["LOG_FILE"];
	$cachefile=$GLOBALS["PROGRESS_FILE"];
	$logsFile=$GLOBALS["LOG_FILE"];
	$logsFileEncoded=urlencode($logsFile);
	$BEFORE=$ARRAY["BEFORE"];
	$AFTER=$ARRAY["AFTER"];
    $AFTER_FAILED="";
    if(isset($ARRAY["AFTER_FAILED"])){$AFTER_FAILED=$ARRAY["AFTER_FAILED"];}
	if(isset($ARRAY["AFTER-FAILED"])){$AFTER_FAILED=$ARRAY["AFTER-FAILED"];}
	if(isset($ARRAY["REFRESH-MENU"])){$REFRESH_MENU=1;}
    $Details="";
    if( is_file($logsFile)){
        $Details = $tpl->_ENGINE_parse_body("&nbsp;&nbsp;<a href=\"javascript:blur()\" OnClick=\"Zoom$t()\">&laquo;{details}&raquo;</a>");
    }
	
	$title_src=fw_progress_html($tpl,$ARRAY["TITLE"]);
    $title_src_default=fw_progress_html($tpl,"{please_wait}");
	$title2=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}");	
	echo "// Array of ".count($ARRAY)." elements\n";
	echo "// Cache file = $cachefile\n";
	echo "// Log file = $logsFile\n";
	echo "// CMD = $CMD\n";
	$array=@unserialize((string)@file_get_contents($cachefile),array("allowed_classes"=>false));
    if(!is_array($array)){$array=array();}
    if(!isset($array["POURC"])){
        $array["POURC"]=0;
    }

	$prc=intval($array["POURC"]);
	echo "// prc = $prc\n";

    if(!isset($array["TEXT"])){$array["TEXT"]="None";}
	$title=fw_progress_html($tpl,$array["TEXT"]);
	$titleEncoded=urlencode($title_src);
    if($title_src==null){$title_src=$title_src_default;}
    if(!isset($_GET["md5file"])){$_GET["md5file"]="";}
	
if($prc==0){
	echo "
	function Start$time(){
			if(!document.getElementById('$id')){return;}
			Loadjs('$page?build-js=$signed&mainid=$id{$md5_uri}&myid=$myid&t=$t&md5file={$_GET["md5file"]}',false,{retryStatuses:[403]});
			LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
	}
	setTimeout(\"Start$time()\",1000);";
	return true;
}

    $md5file="";
    if(is_file($logsFile)) {
        $md5file = md5_file($logsFile);
    }
    $bTitle=base64_encode($title_src);
    $sText=base64_encode($title);

if($md5file<>$_GET["md5file"]){
	echo "
	var xStart$time= function (obj) {
//		if(!document.getElementById('text-$t')){return;}
//		var res=obj.responseText;
//		if (res.length>3){ document.getElementById('text-$t').value=res; }		
		Loadjs('$page?build-js=$signed&mainid=$id{$md5_uri}&myid=$myid&t=$t&md5file=$md5file',false,{retryStatuses:[403]});
		LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
	}		
	
	function Start$time(){
		if(!document.getElementById('$id')){return;}
		if(window.FwProg){FwProg('$myid','run',$prc,'$bTitle','$sText');}
		var XHR = new XHRConnection();
		XHR.appendData('Filllogs', 'yes');
		XHR.appendData('filename','".urlencode($logsFile)."');
		XHR.appendData('t', '$t');
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xStart$time,false); 
	}
	setTimeout(\"Start$time()\",1000);";
	return true;
}

if($prc>100){
    $sText=base64_encode("$title$Details");
    $failed=fw_progress_js_str(fw_progress_html($tpl,"{failed}"));
    admin_tracks("Launch task $title_src failed");
	echo "
	function Start$time(){
		if(!document.getElementById('$id')){return;}
		if(window.FwProg){FwProg('$myid','err',100,'$bTitle','$sText','$failed');}
		LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		
	}
	function jsProgressAfterFailed(){
	     Zoom$t();
	}
	
	function Clean$time(){
	    if(!document.getElementById('$id')){return;}
	    document.getElementById('$id').innerHTML='';
	}
	
	function Zoom$t(){ Loadjs('fw.progress.details.php?logfile=$logsFileEncoded&title=$titleEncoded'); }
	setTimeout(\"Start$time()\",1000);
	$AFTER_FAILED;
    setTimeout(\"Clean$time()\",5000);";
	return true;
	
}
    $JS_CLUSTER=null;
    $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
    if($PowerDNSEnableClusterMaster==1){
        $JS_CLUSTER="Loadjs('fw.system.cluster.master.php?Launch=yes')";
    }


if($prc==100){
    admin_tracks("Launch task $title_src success");
    $sText=base64_encode("$title$Details");

	echo "
	function Start$time(){
		var REFRESH_MENU=$REFRESH_MENU;
		if(!document.getElementById('$id')){return;}
		if(window.FwProg){FwProg('$myid','ok',100,'$bTitle','$sText');}
		if(REFRESH_MENU==1){
			uri=document.getElementById('fw-left-menus-uri').value
			LoadAjaxSilent('left-barr',uri);
			LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');
		}
		$AFTER;
		$JS_CLUSTER;
		setTimeout(\"Clean$time()\",5000);
	}
	function Clean$time(){
	    if(!document.getElementById('$id')){return;}
	    document.getElementById('$id').innerHTML='';
	}
	function Zoom$t(){ Loadjs('fw.progress.details.php?logfile=$logsFileEncoded&title=$titleEncoded'); }
	
	setTimeout(\"Start$time()\",1000);
	";	
	return true;
}
$sText=base64_encode($title);
echo "	
function Start$time(){
	if(!document.getElementById('$id')){return;}
	if(window.FwProg){FwProg('$myid','run',$prc,'$bTitle','$sText');}
	Loadjs('$page?build-js=$signed&mainid=$id{$md5_uri}&myid=$myid&t=$t&md5file={$_GET["md5file"]}',false,{retryStatuses:[403]});
	
}
$BEFORE;
setTimeout(\"Start$time()\",1500);
";
return true;
}
function popup():bool{
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$text=$tpl->_ENGINE_parse_body("{please_wait_preparing_settings}...");
	
$html="
<center id='title-$t' style='font-size:18px;margin-bottom:20px'>$text</center>
<div id='progress-$t' style='height:50px'></div>
<p>&nbsp;</p>
<textarea style='margin-top:5px;font-family:Courier New,serif;
font-weight:bold;width:98%;height:446px;border:5px solid #8E8E8E;
overflow:auto;font-size:11px' id='text-$t'></textarea>
	
<script>
function Step1$t(){
	$('#progress-$t').progressbar({ value: 1 });
	Loadjs('$page?build-js=yes&t=$t&md5file=0&comand=".urlencode($_GET["comand"])."');
}
$('#progress-$t').progressbar({ value: 1 });
setTimeout(\"Step1$t()\",1000);

</script>
";
echo $html;
return true;
}
function Filllogs():bool{
    if(!isset($GLOBALS["LOG_FILE"])){return false;}
	$logsFile=$GLOBALS["LOG_FILE"];
	$t=explode("\n",@file_get_contents($logsFile));
	krsort($t);
	echo @implode("\n", $t);
    return true;
}