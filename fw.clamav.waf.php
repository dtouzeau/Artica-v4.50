<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/ressources/class.modsectools.inc');
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Read-only page, but it had no privilege check at all (only the left menu hid it): the menu's
// web-filtering administrator or the WAF administrator may read it.
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator && !$users->AsWebSecurity && !$users->AsSystemAdministrator){exit();}
if(isset($_GET["form"])){wafav_form();exit;}
if(isset($_GET["search"])){search();exit;}
page();

// ---- design (maquette stitch_waf_threats, 2026-11-15) ----------------------------------------
// Files uploaded to the published websites and scanned by ClamAV through ModSecurity (rule 400001
// -> /usr/share/artica-postfix/bin/scanav, enabled per website by ModSecurityScanAvPost).
// scanav writes one JSON line per event in /var/log/nginx/antivirus.log: {level,time,message},
// message "Status:0|1|Name:<sig>|IP:<client>|Uri:<path>|Serverid:<id>" or
// "[VIRUS_FOUND] <sig> From IP:… Uri:… Serverid:…", or a scanner message.
// Read through articarest's closed log list (GET /system/logfile/search/waf-antivirus/…): the old
// framework/nginx.php?nginx-searchav ran grep in a root shell with the typed text, wrote a shared
// file and read it back. Everything shown is computed over the lines read.
// Left out of the mock-up because invented: "Daemon status: Enforcing (Block & Log)", the ClamAV
// engine / database version and "MaxMind … Updated today" under the top lists, "Live Inspection
// Active", "Buffer: … read in 12ms", "Socket connected", the version footer and its links.

function wafav_max_choices():array{ return array(50,100,250,500,1000); }
function wafav_logfile():string{ return "/var/log/nginx/antivirus.log"; }

// Search text, website id and line count, from the request then the session.
function wafav_params():array{
    $term=null;$max=0;$site=-1;
    if(isset($_GET["search"])){$term=(string)$_GET["search"];}
    if(isset($_GET["max"])){$max=intval($_GET["max"]);}
    if(isset($_GET["site"])){$site=intval($_GET["site"]);}
    if($term===null){ $term=isset($_SESSION["WAFAV_SEARCH"])?(string)$_SESSION["WAFAV_SEARCH"]:""; }
    if($max==0 && isset($_SESSION["WAFAV_MAX"])){$max=intval($_SESSION["WAFAV_MAX"]);}
    if($site<0){ $site=isset($_SESSION["WAFAV_SITE"])?intval($_SESSION["WAFAV_SITE"]):0; }
    if(preg_match("#(^|\s)([0-9]+)\s+events(\s|$)#i",$term,$re)){
        if($max==0){$max=intval($re[2]);}
        $term=str_replace($re[0]," ",$term);
    }
    $term=trim(preg_replace('/\s+/',' ',$term));
    // The text becomes a grep -E pattern: plain characters only, and the dot escaped below.
    $term=preg_replace('/[^A-Za-z0-9 _.:@\/=,\-]/',"",$term);
    if(strlen($term)>128){$term=substr($term,0,128);}
    if(!in_array($max,wafav_max_choices())){$max=250;}
    if($site<0){$site=0;}
    return array("term"=>$term,"max"=>$max,"site"=>$site);
}

// Token translated FIRST, then each %s replaced in order.
function wafav_tr(string $token):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body("{".$token."}");
    $args=func_get_args(); array_shift($args);
    foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
    return $t;
}

// Log text and site names are external data: escaped, braces/brackets neutralised for the engine.
function wafav_h($s):string{
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),req_html((string)$s));
}

function wafav_types():array{
    return array(
        "infected"=>array("label"=>"{wafav_t_infected}","ico"=>"fas fa-virus"),
        "clean"   =>array("label"=>"{wafav_t_clean}","ico"=>"fas fa-circle-check"),
        "error"   =>array("label"=>"{wafav_t_error}","ico"=>"fas fa-circle-xmark"),
        "warning" =>array("label"=>"{wafav_t_warning}","ico"=>"fas fa-triangle-exclamation"),
        "info"    =>array("label"=>"{wafav_t_info}","ico"=>"fas fa-circle-info"),
    );
}

// zerolog writes "time" as RFC3339 by default; an epoch is accepted too.
function wafav_ts($v):int{
    if(is_int($v) || (is_string($v) && ctype_digit($v))){ return intval($v); }
    if(is_float($v)){ return intval($v); }
    $s=preg_replace('/\.[0-9]+/','',trim((string)$v));
    if($s===""){return 0;}
    $t=strtotime($s);
    return $t===false?0:$t;
}

// One log line -> event. A line that is not JSON is kept as a scanner Info line, never dropped.
function wafav_parse(string $line):array{
    $r=array("ts"=>0,"kind"=>"info","sig"=>"","ip"=>"","uri"=>"","site"=>-1,"msg"=>"","raw"=>$line);
    $j=json_decode($line,true);
    if(!is_array($j)){ $r["msg"]=$line; return $r; }
    $r["ts"]=wafav_ts(isset($j["time"])?$j["time"]:"");
    $level=strtolower((string)(isset($j["level"])?$j["level"]:"info"));
    $msg=trim((string)(isset($j["message"])?$j["message"]:""));
    if(preg_match('#Status:([0-9]+)\|Name:(.*?)\|IP:(.*?)\|Uri:(.*?)\|Serverid:([0-9]+)#i',$msg,$re)){
        $r["kind"]=intval($re[1])==1?"infected":"clean";
        $r["sig"]=trim($re[2]); $r["ip"]=trim($re[3]); $r["uri"]=trim($re[4]); $r["site"]=intval($re[5]);
        return $r;
    }
    if(preg_match('#\[VIRUS_FOUND\]\s+(.+?)\s+From IP:(.*?)\s+Uri:(.+?)\s+Serverid:([0-9]+)#i',$msg,$re)){
        $r["kind"]="infected"; $r["sig"]=trim($re[1]); $r["ip"]=trim($re[2]); $r["uri"]=trim($re[3]); $r["site"]=intval($re[4]);
        return $r;
    }
    if(preg_match('#\[VIRUS_FOUND\]\s+(.+)$#i',$msg,$re)){
        $r["kind"]="infected"; $r["sig"]=trim($re[1]);
        return $r;
    }
    $r["msg"]=$msg;
    if($level=="error"||$level=="fatal"||$level=="panic"){$r["kind"]="error";}
    elseif($level=="warn"||$level=="warning"){$r["kind"]="warning";}
    return $r;
}

// grep -E pattern: the text (dot escaped), then the website when one is chosen (Serverid is the
// last field of a line, after the signature, the address and the path the text can match).
function wafav_pattern(array $P):string{
    $t=str_replace(".","\\.",$P["term"]);
    if(strlen($t)<3){$t="";}
    $s=$P["site"]>0?"Serverid:".intval($P["site"])."([^0-9]|$)":"";
    if($t!=="" && $s!==""){ return "$t.*?$s"; }
    if($t!==""){ return $t; }
    if($s!==""){ return $s; }
    return "NONE";
}

function wafav_sites():array{
    $out=array();
    foreach(fetch_all_sites_sorted() as $row){ $out[intval($row["ID"])]=array("name"=>(string)$row["servicename"],"enabled"=>intval($row["enabled"])); }
    return $out;
}

// One model per request.
function wafav_model():array{
    $P=wafav_params();
    $M=array("term"=>$P["term"],"max"=>$P["max"],"site"=>$P["site"],"error"=>"","rows"=>array(),
        "counts"=>array("infected"=>0,"clean"=>0,"error"=>0,"warning"=>0,"info"=>0),
        "oldest"=>0,"newest"=>0,"last_inf"=>null,"last_err"=>null,"sigs"=>array(),"ips"=>array(),"geo"=>array(),
        "sites"=>wafav_sites());
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/logfile/search/waf-antivirus/".$P["max"]."/".rawurlencode(wafav_pattern($P)));
    $json=json_decode((string)$data);
    if(!is_object($json)){
        $M["error"]=trim((string)$data)==""?"{wafav_no_answer}":"articarest: ".json_last_error_msg();
        return $M;
    }
    if(!isset($json->Status) || !$json->Status){
        $M["error"]=isset($json->Error)&&$json->Error!=""?(string)$json->Error:"{wafav_no_answer}";
        return $M;
    }
    $lines=(isset($json->Results)&&is_array($json->Results))?$json->Results:array();
    foreach($lines as $line){
        $line=trim((string)$line);
        if(strlen($line)<5){continue;}
        // A missing antivirus.log comes back as the reader's own error text: "nothing scanned yet".
        if(preg_match('#^(tail|rg|grep): .*(cannot open|No such file)#i',$line)){continue;}
        $r=wafav_parse($line);
        $M["rows"][]=$r;
        $M["counts"][$r["kind"]]++;
        if($r["ts"]>0){
            if($M["newest"]==0 || $r["ts"]>$M["newest"]){$M["newest"]=$r["ts"];}
            if($M["oldest"]==0 || $r["ts"]<$M["oldest"]){$M["oldest"]=$r["ts"];}
        }
        if($r["kind"]=="infected"){
            if($M["last_inf"]===null || $r["ts"]>$M["last_inf"]["ts"]){$M["last_inf"]=$r;}
            if($r["sig"]!==""){ $M["sigs"][$r["sig"]]=(isset($M["sigs"][$r["sig"]])?$M["sigs"][$r["sig"]]:0)+1; }
            if($r["ip"]!==""){ $M["ips"][$r["ip"]]=(isset($M["ips"][$r["ip"]])?$M["ips"][$r["ip"]]:0)+1; }
        }
        if($r["kind"]=="error" && ($M["last_err"]===null || $r["ts"]>$M["last_err"]["ts"])){$M["last_err"]=$r;}
        if($r["ip"]!=="" && !isset($M["geo"][$r["ip"]])){ $M["geo"][$r["ip"]]=wafav_geo($r["ip"]); }
    }
    arsort($M["sigs"]); arsort($M["ips"]);
    if(isset($_GET["search"])){
        $_SESSION["WAFAV_SEARCH"]=$P["term"];
        $_SESSION["WAFAV_MAX"]=$P["max"];
        $_SESSION["WAFAV_SITE"]=$P["site"];
    }
    return $M;
}

// Country and flag from the console's address cache (no DNS lookup), once per address.
function wafav_geo(string $ip):array{
    $g=array("country"=>"","flag"=>"");
    if(!filter_var($ip,FILTER_VALIDATE_IP)){return $g;}
    $m=new modesctools();
    $m->hostinfo($ip);
    $g["country"]=(string)$m->country_name;
    $g["flag"]=(string)$m->flag;
    return $g;
}

function wafav_site_name(array $M,int $id):string{
    if($id<=0 || !isset($M["sites"][$id])){ return ""; }
    return $M["sites"][$id]["name"];
}

function wafav_age(int $ts):string{
    if($ts<=0){return "";}
    $d=time()-$ts;
    if($d<0){$d=0;}
    if($d<60){return wafav_tr("wafav_age_now");}
    if($d<3600){return wafav_tr("wafav_age_min",intval($d/60));}
    if($d<86400){return wafav_tr("wafav_age_hours",intval($d/3600));}
    return wafav_tr("wafav_age_days",intval($d/86400));
}

function wafav_short_date(int $ts):string{
    if($ts<=0){return "—";}
    return date("Y",$ts)==date("Y")?date("M j",$ts):date("M j Y",$ts);
}

// ---- page shell ------------------------------------------------------------------------------

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=$tpl->page_header("{WAF_LONG}: {threats}",ico_eye,"{wafav_explain}","$page?form=yes","waf-viruses",null,false,"div-waf-viruses");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{WAF}: {threats}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// Content loaded by page_header: banner host, search card, results host.
function wafav_form(){
    $tpl=new template_admin();
    $P=wafav_params();
    $html=array();
    $html[]="<div class='wafav' id='wafav-root'>";
    $html[]="<div id='wafav-banner'></div>";
    $html[]=wafav_search_card($P);
    $html[]="<div id='wafav-results'></div>";
    $html[]="</div>";
    $q="&max=".$P["max"]."&site=".$P["site"]."&search=".urlencode($P["term"]);
    echo wafav_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>".wafav_js()."WafAvLoad('".$q."');</script>";
}
function wafav_search_card(array $P):string{
    $opts=array();
    foreach(wafav_max_choices() as $n){
        $sel=$n==$P["max"]?" selected":"";
        $opts[]="<option value='$n'$sel>".wafav_tr("wafav_n_lines",$n)."</option>";
    }
    $sites=array("<option value='0'>".wafav_tr("wafav_all_sites")."</option>");
    foreach(fetch_enabled_sites_sorted() as $row){
        $id=intval($row["ID"]);
        $sel=$id==$P["site"]?" selected":"";
        $sites[]="<option value='$id'$sel>".wafav_h($row["servicename"])."</option>";
    }
    $v=wafav_h($P["term"]);
    return "<div class='wafav-card wafav-search'>
  <div class='wafav-search-row'>
    <div class='wafav-field wafav-grow'>
      <label for='wafav-q' class='wafav-label'>{wafav_search}</label>
      <div class='wafav-input'><span class='wafav-input-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>
      <input type='text' id='wafav-q' value=\"$v\" maxlength='128' placeholder=\"{wafav_search_ph}\" onkeydown=\"WafAvKey(event)\"></div>
      <div class='wafav-help'>{wafav_search_help}</div>
    </div>
    <div class='wafav-field wafav-site'>
      <label for='wafav-site' class='wafav-label'>{wafav_website}</label>
      <select id='wafav-site' onchange='WafAvGo()'>".implode("",$sites)."</select>
      <div class='wafav-help'>{wafav_website_help}</div>
    </div>
    <div class='wafav-field wafav-lines'>
      <label for='wafav-max' class='wafav-label'>{wafav_lines}</label>
      <select id='wafav-max' onchange='WafAvGo()'>".implode("",$opts)."</select>
      <div class='wafav-help'>{wafav_lines_help}</div>
    </div>
    <div class='wafav-field wafav-btns'>
      <span class='wafav-label'>&nbsp;</span>
      <div class='wafav-btnrow'>
      <button type='button' class='wafav-btn' onclick='WafAvGo()'><span aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span> {wafav_search_btn}</button>
      <button type='button' class='wafav-btn-sec' onclick='WafAvClear()'>{wafav_clear}</button>
      </div>
    </div>
  </div>
  <div class='wafav-chips' id='wafav-chips' style='display:none'></div>
</div>";
}

// ---- results (?search=) ----------------------------------------------------------------------

function search(){
    $tpl=new template_admin();
    $M=wafav_model();
    $html=array();
    $html[]="<div id='wafav-banner-src' hidden>".wafav_banner($M)."</div>";
    if($M["error"]!=""){
        $html[]=wafav_error($M);
    }else{
        $html[]=wafav_kpis($M);
        $html[]=wafav_tops($M);
        $html[]=wafav_table($M);
    }
    $html[]=wafav_state($M);
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>WafAvBanner();".wafav_tiny()."WafAvChips();WafAvApply();</script>";
}

function wafav_tiny():string{
    $tpl=new template_admin();
    $topbuttons=array();
    $topbuttons[]=array("WafAvGo()",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{WAF_LONG}: {threats}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{wafav_explain}";
    $TINY_ARRAY["URL"]="waf-viruses";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

// Hidden carrier read by the JS: current server-side filters, for the chips.
function wafav_state(array $M):string{
    $sn=wafav_site_name($M,intval($M["site"]));
    return "<span id='wafav-state' hidden data-term=\"".wafav_h($M["term"])."\" data-site='".intval($M["site"])."' data-sitename=\"".wafav_h($sn)."\" data-max='".intval($M["max"])."'></span>";
}

function wafav_error(array $M):string{
    $err=$M["error"];
    if(strpos($err,"{")!==0){$err=wafav_h($err);}
    return "<div class='wafav-alert' role='alert'><span class='wafav-alert-ico' aria-hidden='true'><i class='fas fa-plug-circle-xmark'></i></span>
<div><strong>{wafav_unreachable}</strong><div class='wafav-help wafav-mono'>$err</div></div></div>";
}

// Outcome banner (moved above the search card by WafAvBanner()).
function wafav_banner(array $M):string{
    if($M["error"]!=""){ return ""; }
    $n=count($M["rows"]);
    $C=$M["counts"];
    $ctx="";
    if($n>0){
        $ctx=wafav_tr("wafav_in_lines",$n);
        if($M["oldest"]>0){
            $ctx.=" — ".wafav_tr("wafav_from_to",wafav_short_date($M["oldest"])." ".date("H:i",$M["oldest"]),wafav_short_date($M["newest"])." ".date("H:i",$M["newest"]));
        }
    }
    $detail="";$link="";
    if($n==0 && strlen($M["term"])<3 && $M["site"]==0){
        $tone="neutral";$ico="fas fa-circle-info";$title="{wafav_o_none}";
        $detail="<div class='wafav-ob-why'>{wafav_o_none_help}</div>";
        $link="<a class='wafav-ob-link' href='fw.nginx.sites.php?main-page=yes'><span aria-hidden='true'><i class='fas fa-globe'></i></span> {wafav_open_sites}</a>";
    }elseif($C["infected"]>0){
        $tone="err";$ico="fas fa-virus";
        $L=$M["last_inf"];
        $site=wafav_site_name($M,$L["site"]);
        $title=$site!==""
            ?wafav_tr("wafav_o_infected_site",$C["infected"],wafav_age($L["ts"]),wafav_h($site),wafav_h($L["sig"]))
            :wafav_tr("wafav_o_infected",$C["infected"],wafav_age($L["ts"]),wafav_h($L["sig"]));
    }elseif($C["error"]>0){
        $tone="warn";$ico="fas fa-triangle-exclamation";
        $title=wafav_tr("wafav_o_errors",$C["error"]);
        $e=$M["last_err"]["msg"];
        $short=strlen($e)>140?substr($e,0,137)."…":$e;
        $detail="<div class='wafav-ob-why wafav-mono' title=\"".wafav_h($e)."\">".wafav_h($short)."</div>";
    }elseif($n>0){
        $tone="ok";$ico="fas fa-shield-check";
        $title=wafav_tr("wafav_o_clean",$C["clean"]);
    }else{
        $tone="neutral";$ico="fas fa-filter";$title="{wafav_o_nomatch}";
    }
    return "<div class='wafav-ob wafav-ob-$tone' role='status'>
<span class='wafav-ob-ico' aria-hidden='true'><i class='$ico'></i></span>
<div class='wafav-ob-b'><div class='wafav-ob-t'>$title</div>$detail".($ctx==""?"":"<div class='wafav-ob-ctx'>$ctx</div>")."$link</div></div>";
}

// Four filter cards (aria-pressed, disabled at 0) and the last detection.
function wafav_kpis(array $M):string{
    if(count($M["rows"])==0){ return ""; }
    $c=array();
    foreach(array("infected","clean","error","warning") as $k){ $c[]=wafav_kpi($M,$k); }
    $c[]=wafav_kpi_last($M);
    return "<div class='wafav-kpis'>".implode("",$c)."</div>";
}

function wafav_kpi(array $M,string $k):string{
    $T=wafav_types();
    $n=intval($M["counts"][$k]);
    $labels=array("infected"=>"{wafav_k_infected}","clean"=>"{wafav_k_clean}","error"=>"{wafav_k_errors}","warning"=>"{wafav_k_warnings}");
    $subs=array("infected"=>"{wafav_ks_infected}","clean"=>"{wafav_ks_clean}","error"=>"{wafav_ks_errors}","warning"=>"{wafav_ks_warnings}");
    $tone=$n>0?" wafav-k-$k":" wafav-k-zero";
    $dis=$n==0?" disabled title=\"{wafav_k_none}\"":"";
    return "<button type='button' class='wafav-kpi$tone' data-type='$k' aria-pressed='false' onclick=\"WafAvType('$k')\"$dis>
<span class='wafav-kpi-h'><span class='wafav-kpi-ico' aria-hidden='true'><i class='{$T[$k]["ico"]}'></i></span><span class='wafav-kpi-l'>{$labels[$k]}</span></span>
<span class='wafav-kpi-n'>$n</span><span class='wafav-kpi-s'>{$subs[$k]}</span></button>";
}

function wafav_kpi_last(array $M):string{
    $L=$M["last_inf"];
    if($L!==null && $L["ts"]>0){
        $v=wafav_age($L["ts"]);
        $site=wafav_site_name($M,$L["site"]);
        $sub=wafav_short_date($L["ts"])." ".date("H:i",$L["ts"]).($site!==""?" · ".wafav_h($site):"");
        $tone=" wafav-k-last";
    }else{
        $v="{wafav_none_here}"; $sub=""; $tone=" wafav-k-zero";
    }
    return "<div class='wafav-kpi wafav-kpi-static$tone'>
<span class='wafav-kpi-h'><span class='wafav-kpi-ico' aria-hidden='true'><i class='fas fa-clock'></i></span><span class='wafav-kpi-l'>{wafav_k_last}</span></span>
<span class='wafav-kpi-t'>$v</span><span class='wafav-kpi-s'>$sub</span></div>";
}

// Most detected signatures and top source addresses, only when something was infected.
function wafav_tops(array $M):string{
    if(count($M["sigs"])==0 && count($M["ips"])==0){ return ""; }
    $s=array();
    foreach(array_slice($M["sigs"],0,5,true) as $sig=>$n){
        $s[]="<li><span class='wafav-sig'>".wafav_h($sig)."</span><span class='wafav-cnt'>$n</span></li>";
    }
    $i=array();
    foreach(array_slice($M["ips"],0,5,true) as $ip=>$n){
        $i[]="<li>".wafav_source_html((string)$ip,$M,true)."<span class='wafav-cnt'>$n</span></li>";
    }
    $card=function($title,$items){
        if(count($items)==0){return "";}
        return "<div class='wafav-card wafav-top'><div class='wafav-top-h'><h3>$title</h3><span class='wafav-help'>{wafav_from_lines}</span></div><ul>".implode("",$items)."</ul></div>";
    };
    return "<div class='wafav-tops'>".$card("{wafav_top_sigs}",$s).$card("{wafav_top_ips}",$i)."</div>";
}

// Flag + address (link to the WAF's IP sheet for a WAF administrator) + country.
function wafav_source_html(string $ip,array $M,bool $inline=false):string{
    $g=isset($M["geo"][$ip])?$M["geo"][$ip]:array("country"=>"","flag"=>"");
    $valid=filter_var($ip,FILTER_VALIDATE_IP)!==false;
    $country=wafav_h($g["country"]);
    $flag=($g["flag"]!=="" && preg_match('/^[A-Za-z0-9_.\-\/]+$/',$g["flag"]))?"<img class='wafav-flag' src='img/".$g["flag"]."' alt=\"$country\">":"";
    $users=new usersMenus();
    $ipH=wafav_h($ip);
    if($valid && $users->AsWebSecurity){
        $aria=wafav_tr("wafav_open_ip",$ipH);
        $ipH="<a class='wafav-ip' href=\"javascript:blur();\" aria-label=\"$aria\" OnClick=\"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=".rawurlencode($ip)."')\">$ipH</a>";
    }else{
        $ipH="<span class='wafav-ip'>$ipH</span>";
    }
    if($inline){ return "<span class='wafav-src'>$flag$ipH".($country!==""?" <span class='wafav-country'>$country</span>":"")."</span>"; }
    return "<div class='wafav-src'>$flag$ipH</div>".($country!==""?"<div class='wafav-country'>$country</div>":"");
}

function wafav_table(array $M):string{
    if(count($M["rows"])==0){ return wafav_empty($M); }
    $rows=array();
    foreach($M["rows"] as $r){ $rows[]=wafav_row($r,$M); }
    return "<div class='wafav-card wafav-tablecard'>
<div class='wafav-th'><h3>{wafav_stream}</h3><span class='wafav-th-n' id='wafav-shown'></span></div>
<table class='wafav-table'>
<thead><tr><th class='wafav-c-time'>{wafav_time}</th><th class='wafav-c-res'>{wafav_result}</th><th class='wafav-c-src'>{wafav_source}</th><th class='wafav-c-site'>{wafav_website}</th><th class='wafav-c-sig'>{wafav_signature}</th><th>{wafav_path}</th></tr></thead>
<tbody id='wafav-tbody'>".implode("\n",$rows)."</tbody></table>
<div class='wafav-empty' id='wafav-type-empty' style='display:none'>
  <span class='wafav-empty-ico' aria-hidden='true'><i class='fas fa-filter'></i></span>
  <h4 id='wafav-type-empty-t'></h4><p>{wafav_look_further}</p>
</div>
<div class='wafav-foot'><span>{wafav_source_file} <span class='wafav-mono'>".wafav_logfile()."</span></span><span>".wafav_tr("wafav_read_n",count($M["rows"]))."</span></div>
</div>";
}

function wafav_badge(string $kind):string{
    $T=wafav_types();
    return "<span class='wafav-badge wafav-b-$kind'><span aria-hidden='true'><i class='{$T[$kind]["ico"]}'></i></span> {$T[$kind]["label"]}</span>";
}

function wafav_row(array $r,array $M):string{
    if($r["ts"]>0){
        $title=wafav_h(date("D M j H:i:s Y",$r["ts"]));
        $time="<span class='wafav-age'>".wafav_age($r["ts"])."</span><span class='wafav-day'>".wafav_short_date($r["ts"])." ".date("H:i:s",$r["ts"])."</span>";
    }else{
        $title="";
        $time="<span class='wafav-day'>—</span>";
    }
    $k=$r["kind"];
    $edge=$k=="infected"?" wafav-r-inf":"";
    $head="<tr class='wafav-row$edge' data-type='$k'><td class='wafav-c-time' title=\"$title\">$time</td><td>".wafav_badge($k)."</td>";
    if($k=="infected" || $k=="clean"){
        $site=wafav_site_name($M,$r["site"]);
        $siteH=$site!==""?wafav_h($site):"<span class='wafav-muted'>{wafav_unknown_site}</span>";
        $src=$r["ip"]!==""?wafav_source_html($r["ip"],$M):"<span class='wafav-muted'>—</span>";
        $sig=($k=="infected" && $r["sig"]!=="")?"<span class='wafav-sig'>".wafav_h($r["sig"])."</span>":"<span class='wafav-muted'>—</span>";
        $uri=$r["uri"]!==""?"<span class='wafav-path' title=\"".wafav_h($r["uri"])."\">".wafav_h($r["uri"])."</span>":"<span class='wafav-muted'>—</span>";
        return $head."<td>$src</td><td class='wafav-sitecell'>$siteH</td><td>$sig</td><td>$uri</td></tr>";
    }
    return $head."<td colspan='4' class='wafav-scan wafav-scan-$k'><span class='wafav-scan-l'>{wafav_scanner}</span> ".wafav_h($r["msg"])."</td></tr>";
}

function wafav_empty(array $M):string{
    if(strlen($M["term"])>=3 || $M["site"]>0){
        return "<div class='wafav-card wafav-empty'>
  <span class='wafav-empty-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>
  <h4>{wafav_no_match}</h4><p>{wafav_no_match_help}</p>
  <button type='button' class='wafav-btn-sec' onclick='WafAvClear()'>{wafav_clear_filter}</button></div>";
    }
    return "<div class='wafav-card wafav-empty'>
  <span class='wafav-empty-ico' aria-hidden='true'><i class='fas fa-file-shield'></i></span>
  <h4>{wafav_o_none}</h4><p>{wafav_o_none_help}</p></div>";
}

// ---- assets ----------------------------------------------------------------------------------

function wafav_plain($tok):string{
    $tpl=new template_admin();
    return trim(strip_tags(html_entity_decode($tpl->_ENGINE_parse_body($tok),ENT_QUOTES,"UTF-8")));
}

function wafav_js():string{
    $page=CurrentPageName();
    $labels=array("infected"=>"{wafav_k_infected}","clean"=>"{wafav_k_clean}","error"=>"{wafav_k_errors}","warning"=>"{wafav_k_warnings}");
    foreach($labels as $k=>$v){ $labels[$k]=wafav_plain($v); }
    $txt=array("type"=>"{wafav_chip_type}","text"=>"{wafav_chip_text}","site"=>"{wafav_website}","active"=>"{wafav_active_filters}",
        "clear"=>"{wafav_clear_all}","remove"=>"{wafav_remove_filter}","notype"=>"{wafav_no_type_lines}","shown"=>"{wafav_showing}");
    foreach($txt as $k=>$v){ $txt[$k]=wafav_plain($v); }
    $J=json_encode(array("labels"=>$labels,"t"=>$txt));
    return "
window.WafAvI18n=$J; if(typeof window.WafAvTypeF==='undefined'){window.WafAvTypeF='';}
function WafAvQS(){var q=document.getElementById('wafav-q'),m=document.getElementById('wafav-max'),s=document.getElementById('wafav-site');
  return '&max='+encodeURIComponent(m?m.value:'250')+'&site='+encodeURIComponent(s?s.value:'0')+'&search='+encodeURIComponent(q?q.value:'');}
function WafAvLoad(qs){LoadAjax('wafav-results','$page?r=1'+qs);}
function WafAvGo(){if(!document.getElementById('wafav-results')){return;}WafAvLoad(WafAvQS());}
function WafAvKey(e){if(e&&(e.keyCode==13)){e.preventDefault();WafAvGo();}}
function WafAvClear(){var q=document.getElementById('wafav-q'),s=document.getElementById('wafav-site');if(q){q.value='';}if(s){s.value='0';}window.WafAvTypeF='';WafAvGo();}
function WafAvClearText(){var q=document.getElementById('wafav-q');if(q){q.value='';}WafAvGo();}
function WafAvClearSite(){var s=document.getElementById('wafav-site');if(s){s.value='0';}WafAvGo();}
function WafAvType(k){window.WafAvTypeF=(window.WafAvTypeF===k)?'':k;WafAvApply();WafAvChips();}
function WafAvEsc(s){var d=document.createElement('div');d.textContent=String(s);return d.innerHTML;}
function WafAvBanner(){var s=document.getElementById('wafav-banner-src'),d=document.getElementById('wafav-banner');
  if(!d){return;}d.innerHTML=s?s.innerHTML:'';if(s){s.parentNode.removeChild(s);}}
function WafAvApply(){
  var f=window.WafAvTypeF||'',tb=document.getElementById('wafav-tbody'),shown=0,total=0;
  var ks=document.querySelectorAll('.wafav-kpi[data-type]');
  for(var i=0;i<ks.length;i++){ks[i].setAttribute('aria-pressed',ks[i].getAttribute('data-type')===f?'true':'false');}
  if(!tb){return;}
  for(var j=0;j<tb.rows.length;j++){var r=tb.rows[j];total++;var ok=(f===''||r.getAttribute('data-type')===f);r.style.display=ok?'':'none';if(ok){shown++;}}
  var I=window.WafAvI18n,n=document.getElementById('wafav-shown');
  if(n){n.textContent='· '+I.t.shown.replace('%s',shown).replace('%s',total);}
  var e=document.getElementById('wafav-type-empty');if(!e){return;}
  if(f!==''&&shown===0){document.getElementById('wafav-type-empty-t').textContent=I.t.notype.replace('%s',I.labels[f]||f);e.style.display='';}
  else{e.style.display='none';}
}
function WafAvChips(){
  var c=document.getElementById('wafav-chips');if(!c){return;}
  var I=window.WafAvI18n,st=document.getElementById('wafav-state'),h=[],tmp=document.createElement('textarea');
  var term=st?st.getAttribute('data-term'):'';tmp.innerHTML=term||'';term=tmp.value;
  var sn=st?st.getAttribute('data-sitename'):'';tmp.innerHTML=sn||'';sn=tmp.value;
  var chip=function(cls,label,val,fn){return '<span class=\"wafav-chip '+cls+'\">'+WafAvEsc(label)+': <strong>'+WafAvEsc(val)+'</strong> <button type=\"button\" aria-label=\"'+WafAvEsc(I.t.remove+' '+val)+'\" onclick=\"'+fn+'\">&times;</button></span>';};
  if(st&&st.getAttribute('data-site')!=='0'&&sn){h.push(chip('',I.t.site,sn,'WafAvClearSite()'));}
  if(term&&term.length>=3){h.push(chip('',I.t.text,term,'WafAvClearText()'));}
  if(window.WafAvTypeF){h.push(chip('wafav-chip-t',I.t.type,I.labels[window.WafAvTypeF]||'','WafAvType(window.WafAvTypeF)'));}
  if(h.length===0){c.innerHTML='';c.style.display='none';return;}
  c.style.display='';c.innerHTML='<span class=\"wafav-chips-l\">'+WafAvEsc(I.t.active)+'</span>'+h.join('')+'<button type=\"button\" class=\"wafav-link\" onclick=\"WafAvClear()\">'+WafAvEsc(I.t.clear)+'</button>';
}
";
}

function wafav_style():string{
    $a="#0d9488";$ad="#0f766e";$as="rgba(13,148,136,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        if(!empty($b["accent"])){$a=$b["accent"];}
        if(!empty($b["accent_dark"])){$ad=$b["accent_dark"];}
        if(!empty($b["accent_soft"])){$as=$b["accent_soft"];}
    }
    return "<style>
.wafav{color:#111d23;font-size:14px;margin-top:16px}
.wafav *{box-sizing:border-box}
.wafav-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:16px}
.wafav-help{font-size:13px;color:#6d7a77;line-height:1.45}
.wafav-mono{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px}
.wafav-muted{color:#94a3b8}
.wafav-ob{display:flex;align-items:center;gap:16px;padding:16px 20px;border-radius:12px;border:1px solid;margin-bottom:16px}
.wafav-ob-ico{width:40px;height:40px;flex:0 0 40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff}
.wafav-ob-b{min-width:0}
.wafav-ob-t{font-size:17px;font-weight:700;line-height:1.3}
.wafav-ob-why{margin-top:3px;font-size:14px;line-height:1.45}
.wafav-ob-why.wafav-mono{font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wafav-ob-ctx{font-size:13px;margin-top:3px;opacity:.9}
.wafav-ob-link{display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-weight:600;font-size:14px;color:$ad!important}
.wafav-ob-ok{background:rgba(16,185,129,.12);border-color:rgba(4,120,87,.3);color:#047857}.wafav-ob-ok .wafav-ob-ico{background:#047857}
.wafav-ob-warn{background:#fff4e5;border-color:#ffddb9;color:#865300}.wafav-ob-warn .wafav-ob-ico{background:#865300}
.wafav-ob-err{background:#ffdad6;border-color:rgba(186,26,26,.3);color:#93000a}.wafav-ob-err .wafav-ob-ico{background:#ba1a1a}
.wafav-ob-neutral{background:#e3f0f8;border-color:#c9dde9;color:#3d4947}.wafav-ob-neutral .wafav-ob-ico{background:#6d7a77}
.wafav-search{padding:16px}
.wafav-search-row{display:flex;gap:16px;align-items:flex-start}
.wafav-field{display:flex;flex-direction:column;gap:4px;min-width:0}
.wafav-grow{flex:1}
.wafav-site{width:280px;flex-shrink:0}
.wafav-lines{width:150px;flex-shrink:0}
.wafav-btns{flex-shrink:0}
.wafav-btnrow{display:flex;gap:8px}
.wafav-label{font-size:13px;font-weight:600;color:#3d4947;margin:0;display:block;line-height:17px}
.wafav-input{position:relative}
.wafav-input-ico{position:absolute;left:12px;top:0;height:40px;display:flex;align-items:center;color:#6d7a77;font-size:14px;pointer-events:none}
.wafav-input input,.wafav-search select{width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;color:#111d23;background:#fff;outline:none}
.wafav-input input{padding:0 12px 0 36px}
.wafav-search select{padding:0 10px}
.wafav-input input:focus,.wafav-search select:focus{border-color:$a;box-shadow:0 0 0 2px $as}
.wafav-btn{height:40px;padding:0 18px;border:0;border-radius:6px;background:$a;color:#fff;font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:8px;cursor:pointer;white-space:nowrap}
.wafav-btn:hover{background:$ad}
.wafav-btn-sec{height:40px;padding:0 14px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#3d4947;font-weight:600;font-size:14px;cursor:pointer}
.wafav-empty .wafav-btn-sec{margin-top:12px}
.wafav button:focus-visible,.wafav a:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.wafav-chips{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9}
.wafav-chips-l{font-size:13px;font-weight:600;color:#6d7a77;text-transform:uppercase;letter-spacing:.03em;margin-right:4px}
.wafav-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 4px 3px 12px;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;font-size:13px}
.wafav-chip-t{background:#fff4f2;border-color:#ffdad6;color:#93000a}
.wafav-chip button{border:0;background:transparent;color:inherit;font-size:16px;line-height:1;padding:0 6px;cursor:pointer}
.wafav-link{border:0;background:transparent;color:$ad;font-size:13px;font-weight:600;cursor:pointer;margin-left:4px}
.wafav-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:16px}
.wafav-kpi{display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-height:96px;padding:12px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;text-align:left;font:inherit;color:inherit;cursor:pointer}
.wafav-kpi:hover:not([disabled]):not(.wafav-kpi-static){border-color:#cbd5e1;background:#fbfdfe}
.wafav-kpi[disabled]{cursor:default}
.wafav-kpi-static{cursor:default}
.wafav-kpi-h{display:flex;align-items:center;gap:8px}
.wafav-kpi-ico{font-size:14px;color:#94a3b8}
.wafav-kpi-l{font-size:14px;font-weight:600;color:#3d4947}
.wafav-kpi-n{font-size:24px;font-weight:700;line-height:1.15}
.wafav-kpi-t{font-size:20px;font-weight:700;line-height:1.3}
.wafav-kpi-s{font-size:13px;color:#6d7a77}
.wafav-k-zero .wafav-kpi-n,.wafav-k-zero .wafav-kpi-t,.wafav-k-zero .wafav-kpi-l{color:#94a3b8}
.wafav-k-infected .wafav-kpi-ico,.wafav-k-infected .wafav-kpi-l,.wafav-k-infected .wafav-kpi-n{color:#ba1a1a}
.wafav-k-clean .wafav-kpi-ico,.wafav-k-clean .wafav-kpi-l,.wafav-k-clean .wafav-kpi-n{color:#047857}
.wafav-k-error .wafav-kpi-ico,.wafav-k-error .wafav-kpi-l,.wafav-k-error .wafav-kpi-n{color:#ba1a1a}
.wafav-k-warning .wafav-kpi-ico,.wafav-k-warning .wafav-kpi-l,.wafav-k-warning .wafav-kpi-n{color:#865300}
.wafav-k-last .wafav-kpi-ico{color:#3d4947}
.wafav-kpi[aria-pressed=true]{border:2px solid $a;background:$as;padding:11px 13px}
.wafav-k-infected[aria-pressed=true],.wafav-k-error[aria-pressed=true]{border-color:#ba1a1a;background:#fff4f2}
.wafav-k-warning[aria-pressed=true]{border-color:#865300;background:#fff8ef}
.wafav-tops{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.wafav-top{padding:16px}
.wafav-top-h{display:flex;justify-content:space-between;align-items:baseline;gap:12px;padding-bottom:10px;border-bottom:1px solid #eef2f6}
.wafav-top-h h3,.wafav-th h3{margin:0;font-size:16px;font-weight:600;color:#111d23}
.wafav-top ul{list-style:none;margin:0;padding:0}
.wafav-top li{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9}
.wafav-top li:last-child{border-bottom:0}
.wafav-cnt{display:inline-block;min-width:28px;padding:1px 9px;border-radius:999px;background:#ffdad6;color:#93000a;font-weight:700;font-size:13px;text-align:center}
.wafav-sig{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;font-weight:600;color:#ba1a1a;overflow-wrap:anywhere}
.wafav-src{display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap}
.wafav-flag{width:18px;height:auto;border-radius:2px}
.wafav-ip{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;color:$ad!important;font-weight:600}
span.wafav-ip{color:#111d23!important}
.wafav-country{font-size:13px;color:#6d7a77}
.wafav-tablecard{overflow:hidden}
.wafav-th{display:flex;align-items:baseline;gap:8px;padding:14px 16px;border-bottom:1px solid #e2e8f0}
.wafav-th-n{font-size:13px;color:#6d7a77}
.wafav-table{width:100%;border-collapse:collapse;table-layout:fixed;color:#111d23}
.wafav-table th{background:#f8fafc;font-size:13px;font-weight:600;color:#3d4947;text-align:left;padding:10px 16px}
.wafav-table td{padding:10px 16px;border-top:1px solid #eef2f6;vertical-align:middle;color:#111d23}
.wafav-c-time{width:140px}.wafav-c-res{width:130px}.wafav-c-src{width:190px}.wafav-c-site{width:170px}.wafav-c-sig{width:210px}
.wafav-age{display:block;font-size:14px}
.wafav-day{display:block;font-size:13px;color:#6d7a77;white-space:nowrap}
.wafav-row:hover td{background:#f8fafc}
.wafav-r-inf td:first-child{box-shadow:inset 3px 0 0 #ba1a1a}
.wafav-sitecell{overflow-wrap:anywhere}
.wafav-path{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;color:#3d4947;overflow-wrap:anywhere}
.wafav-scan{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px;color:#3d4947;overflow-wrap:anywhere}
.wafav-scan-error{color:#93000a}.wafav-scan-warning{color:#653e00}
.wafav-scan-l{font-family:inherit;font-weight:700;margin-right:4px}
.wafav-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}
.wafav-b-infected{background:#ffdad6;color:#93000a}
.wafav-b-clean{background:rgba(16,185,129,.12);color:#047857}
.wafav-b-error{background:#ffdad6;color:#93000a}
.wafav-b-warning{background:#ffddb9;color:#653e00}
.wafav-b-info{background:#f1f5f9;color:#3d4947}
.wafav-foot{display:flex;justify-content:space-between;gap:12px;padding:12px 16px;border-top:1px solid #e2e8f0;font-size:13px;color:#6d7a77}
.wafav-empty{text-align:center;padding:40px 24px}
.wafav-empty-ico{display:inline-flex;width:56px;height:56px;border-radius:50%;background:#f1f5f9;color:#6d7a77;align-items:center;justify-content:center;font-size:24px;margin-bottom:12px}
.wafav-empty h4{font-size:16px;font-weight:600;margin:0 0 6px;color:#111d23}
.wafav-empty p{font-size:14px;color:#3d4947;margin:0 auto;max-width:560px}
.wafav-alert{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border:1px solid rgba(186,26,26,.3);background:#ffdad6;border-radius:12px;margin-bottom:16px;color:#93000a}
.wafav-alert-ico{font-size:22px;line-height:1}
.wafav-alert strong{font-size:15px}
@media (max-width:1200px){.wafav-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.wafav-tops{grid-template-columns:minmax(0,1fr)}.wafav-search-row{flex-wrap:wrap}.wafav-c-src{width:160px}.wafav-c-sig{width:170px}}
@media (prefers-reduced-motion:reduce){.wafav *{transition:none!important}}
</style>";
}
