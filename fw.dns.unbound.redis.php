<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/externals/Net/DNS2.inc");
// Groupe redis de PUT /unbound/api/settings (spec 019 lot 4c).
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");
include_once(dirname(__FILE__)."/ressources/class.unbound.settings.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["UnBoundRedisDBTTL"])){Save();exit;}
if(isset($_GET["unbound-redis-status"])){redis_status();exit;}
if(isset($_GET["top-redis-status"])){redis_widgets();exit;}
if(isset($_GET["main"])){Start();exit;}
if(isset($_GET["search"])){search();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{UBOUND_REDIS}",ico_database,"{ubound_redis_explain}","$page?main=yes","dns-redis-database","unbound-redis-progress",false,"table-dns-redis-loader");

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Tuile de service partagee a gauche (conservee), mesures et contenu a droite.
    $html=array();
    $html[]="<div class='urd-top'>";
    $html[]="<div class='urd-side'><div id='unbound-redis-status'></div></div>";
    $html[]="<div class='urd-main ak'>";
    $html[]="<div id='top-redis-status'></div>";
    // Liste du cache seulement si le backend est active : sinon l'etat vide suffit.
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"))==1){
        $html[]="<div class='ak-card urd-list'><div class='ak-card-h'><span class='urd-list-t'>{cached_items}</span></div>";
        $html[]="<div class='urd-sb'>".$tpl->search_block($page)."</div></div>";
    }
    $html[]="</div></div>";
    // CSS et script concatenes APRES le parsing (accolades lues comme des {jetons}).
    $script="<script>".$tpl->RefreshInterval_js("unbound-redis-status",$page,"unbound-redis-status=yes")."</script>";
    echo urd_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;
}

function urd_style():string{
    $c=array();
    $c[]=".urd-top{display:flex;gap:16px;align-items:flex-start}";
    $c[]=".urd-side{width:350px;flex:0 0 auto}.urd-main{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:16px}";
    $c[]=".urd-w{display:flex;flex-direction:column;gap:16px}";
    $c[]=".urd-strip{gap:16px}.urd-facts{flex:1 1 auto;min-width:0;display:flex;flex-wrap:wrap;gap:12px 24px}.urd-acts{flex:0 0 auto;display:flex;gap:8px;align-items:center}";
    $c[]=".urd-fact{display:flex;flex-direction:column;gap:2px;min-width:0}.urd-fact-v{font-size:15px;font-weight:600;color:#0f172a}";
    $c[]=".urd-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}@media (min-width:1600px){.urd-tiles{grid-template-columns:repeat(4,minmax(0,1fr))}}";
    $c[]=".urd-tiles .ak-fig{align-items:flex-start;min-height:124px}.urd-tiles .ak-fig-txt{gap:8px;flex:1 1 auto}.urd-unit{font-size:14px;font-weight:600;color:#64748b}";
    $c[]=".urd-ring{flex:0 0 auto}.urd-list-t{font-size:15px;font-weight:600}.urd-sb{padding:14px 18px 4px}";
    $c[]=".urd-tbl{table-layout:fixed}.urd-tbl th:nth-child(1){width:34%}.urd-tbl th:nth-child(2){width:92px}.urd-tbl th:nth-child(4){width:110px;text-align:right}";
    $c[]=".urd-tbl td:first-child .ak-name{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".urd-ans{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#334155}";
    $c[]=".urd-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 18px;border-top:1px solid #f1f5f9;font-size:13px;color:#475569}";
    $c[]="@media (max-width:1200px){.urd-top{flex-direction:column}.urd-side{width:100%}}";
    return "<style>".implode("\n",$c)."</style>";
}

/**
 * Motif de recherche: les jokers "*" restent supportes, tout le reste est litteral.
 */
function urd_search_pattern($search):string{
    $search=trim((string)$search);
    if(strlen($search)<2){return "";}
    $parts=explode("*",$search);
    foreach($parts as $k=>$p){$parts[$k]=preg_quote($p,"#");}
    return "#".@implode(".*?",$parts)."#i";
}

/**
 * Parcourt le cache par SCAN + MGET (500 cles par aller-retour) au lieu de
 * KEYS * suivi d'un GET par cle. $fn recoit chaque valeur et rend false pour
 * arreter. Rend false si $fn a arrete le parcours avant la fin.
 */
function urd_scan($redis,callable $fn):bool{
    $redis->setOption(Redis::OPT_SCAN,Redis::SCAN_RETRY);
    $it=null;
    while(($keys=$redis->scan($it,"*",500))!==false){
        if(!is_array($keys) || count($keys)==0){continue;}
        $vals=$redis->mget($keys);
        if(!is_array($vals)){continue;}
        foreach($vals as $v){
            if($v===false){continue;}
            if(!$fn($v)){return false;}
        }
    }
    return true;
}

/**
 * Fragments de la recherche qui doivent figurer tels quels dans la valeur brute.
 * Seuls les fragments contenant une lettre hors hexadecimal sont retenus : un
 * fragment comme "192" ou "2a00" peut correspondre a une adresse IP de la
 * reponse, qui est stockee en binaire et ne se trouve donc pas dans la valeur.
 */
function urd_prefilter_needles(string $search):array{
    $search=trim($search);
    if(strlen($search)<2){return array();}
    $out=array();
    foreach(preg_split('#[*.]#',$search) as $p){
        if(!preg_match('#^[A-Za-z0-9_-]+$#',$p)){
            if($p===""){continue;}
            return array();
        }
        if(preg_match('#[g-zG-Z_-]#',$p)){$out[]=$p;}
    }
    return $out;
}

// ── Decodage d'une entree cachedb d'Unbound ─────────────────────────────────
// Valeur = message DNS au format filaire, suivi de 16 octets : horodatage de
// l'enregistrement puis date d'expiration (deux entiers 64 bits big-endian).
// L'ancien decodeur lisait les reponses a partir de l'octet 12 (au milieu de la
// question) et ne gardait que des enregistrements ayant une cle "data" qu'il ne
// creait jamais : il ne rendait que le nom interroge.
function urd_u16(string $d,int $o):int{
    if($o+2>strlen($d)){return -1;}
    $a=unpack("n",substr($d,$o,2));return intval($a[1]);
}
function urd_u32(string $d,int $o):int{
    if($o+4>strlen($d)){return -1;}
    $a=unpack("N",substr($d,$o,4));return intval($a[1]);
}
// Nom DNS avec pointeurs de compression ; $o avance apres le nom.
function urd_name(string $d,int &$o,int $depth=0):string{
    $labels=array();$len=strlen($d);$jumped=false;$p=$o;
    while($p<$len && $depth<16){
        $l=ord($d[$p]);
        if($l==0){$p++;break;}
        if(($l & 0xC0)==0xC0){
            if($p+1>=$len){break;}
            $ptr=(($l & 0x3F)<<8)|ord($d[$p+1]);
            if(!$jumped){$o=$p+2;}
            $jumped=true;$p=$ptr;$depth++;continue;
        }
        $labels[]=substr($d,$p+1,$l);$p+=$l+1;
    }
    if(!$jumped){$o=$p;}
    return implode(".",$labels);
}
function urd_type_name(int $t):string{
    $m=array(1=>"A",2=>"NS",5=>"CNAME",6=>"SOA",12=>"PTR",15=>"MX",16=>"TXT",28=>"AAAA",33=>"SRV",64=>"SVCB",65=>"HTTPS",257=>"CAA");
    return isset($m[$t])?$m[$t]:"TYPE$t";
}
function urd_rdata(string $d,int $type,int $o,int $rl):string{
    $rd=substr($d,$o,$rl);
    if(($type==1 && $rl==4) || ($type==28 && $rl==16)){$ip=@inet_ntop($rd);return $ip===false?"":$ip;}
    if($type==2 || $type==5 || $type==12){$x=$o;return urd_name($d,$x);}
    if($type==15){$x=$o+2;return urd_u16($d,$o)." ".urd_name($d,$x);}
    if($type==33){$x=$o+6;return urd_u16($d,$o)." ".urd_u16($d,$o+2)." ".urd_u16($d,$o+4)." ".urd_name($d,$x);}
    if($type==16){
        $out=array();$p=0;
        while($p<$rl){$l=ord($rd[$p]);$out[]='"'.substr($rd,$p+1,$l).'"';$p+=$l+1;}
        return implode(" ",$out);
    }
    return "";
}
// Rend array(name,type,answer,count,expires) ou array() si la valeur n'est pas un message.
function urd_parse($value):array{
    $v=(string)$value;
    if(strlen($v)<12+16){return array();}
    $expires=0;
    $t=unpack("N2",substr($v,-8));
    if(is_array($t)){$expires=intval($t[1])*4294967296+intval($t[2]);}
    $d=substr($v,0,-16);
    $qd=urd_u16($d,4);$an=urd_u16($d,6);
    if($qd<1){return array();}
    $o=12;
    $name=urd_name($d,$o);
    $qtype=urd_u16($d,$o);$o+=4;
    for($i=1;$i<$qd;$i++){urd_name($d,$o);$o+=4;}
    // Premiere reponse du type demande ; a defaut la premiere decodable (CNAME d'une chaine).
    $answer="";$first="";$count=0;
    for($i=0;$i<$an && $o<strlen($d);$i++){
        urd_name($d,$o);
        $type=urd_u16($d,$o);$rl=urd_u16($d,$o+8);
        if($type<0 || $rl<0){break;}
        $o+=10;
        $r=urd_rdata($d,$type,$o,$rl);
        if($type==$qtype){
            $count++;
            if($answer==="" && $r!==""){$answer=$r;}
        }elseif($first==="" && $r!==""){
            $first=$r;
        }
        $o+=$rl;
    }
    if($answer===""){$answer=$first;}
    return array("name"=>$name,"type"=>urd_type_name($qtype),"answer"=>$answer,"count"=>$count,"expires"=>$expires);
}

function urd_duration(int $s):string{
    if($s<60){return "{$s}s";}
    $d=intdiv($s,86400);$h=intdiv($s%86400,3600);$m=intdiv($s%3600,60);
    if($d>0){return "{$d}d {$h}h";}
    if($h>0){return "{$h}h {$m}m";}
    return "{$m}m ".($s%60)."s";
}

function urd_row(array $e):string{
    $tag=preg_match('#^(A|AAAA)$#',$e["type"])?"ak-info":($e["type"]=="CNAME"?"ak-ok":($e["type"]=="MX"?"ak-warn":"ak-off"));
    $ans=$e["answer"]!==""?htmlspecialchars($e["answer"]):"<span class='ak-muted'>—</span>";
    if($e["count"]>1 && $e["answer"]!==""){$ans.=" <span class='ak-count'>{$e["count"]}</span>";}
    if($e["expires"]>0){
        $left=$e["expires"]-time();
        $ttl=$left>0?"<span class='ak-mono'>".urd_duration($left)."</span>":"<span class='ak-tag ak-off'>{urd_expired}</span>";
    }else{
        $ttl="<span class='ak-muted'>—</span>";
    }
    $h ="<tr>";
    $n=htmlspecialchars((string)$e["name"]);
    $h.="<td><span class='ak-name' title=\"$n\"><i class='fas fa-globe ak-muted' aria-hidden='true'></i> $n</span></td>";
    $h.="<td><span class='ak-tag $tag'>".htmlspecialchars($e["type"])."</span></td>";
    $h.="<td><div class='ak-mono urd-ans' title=\"".htmlspecialchars($e["answer"])."\">$ans</div></td>";
    $h.="<td class='ak-end'>$ttl</td>";
    $h.="</tr>";
    return $h;
}

function urd_connect(&$err){
    $err="";
    if(!class_exists("Redis")){$err="php-redis is not available";return null;}
    try{
        $redis=new Redis();
        $redis->connect("127.0.0.1",21647,2);
        return $redis;
    }catch(Exception $e){
        $err=$e->getMessage();
        return null;
    }
}

function search():bool{
    $tpl=new template_admin();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"))==0){return false;}
    $err="";
    $redis=urd_connect($err);
    if($redis===null){
        echo $tpl->_ENGINE_parse_body("<div class='ak'><div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span class='ak-banner-m'>{urd_unreachable} ".htmlspecialchars($err)."</span></div></div>");
        return false;
    }
    $pattern=urd_search_pattern($tpl->CLEAN_BAD_XSS((string)($_GET["search"] ?? "")));
    $needles=urd_prefilter_needles((string)($_GET["search"] ?? ""));
    $rows=array();$match=0;$cap=250;$partial=false;
    if($pattern===""){
        // Sans filtre : les $cap premieres entrees suffisent, le total vient de DBSIZE.
        // L'ancien code lisait tout le cache (294 000 GET, ~15 s sur 192.168.1.140).
        urd_scan($redis,function($v) use (&$rows,$cap){
            $e=urd_parse($v);
            if(count($e)==0){return true;}
            $rows[]=urd_row($e);
            return count($rows)<$cap;
        });
        $match=max(count($rows),intval($redis->dbSize()));
    }else{
        $deadline=microtime(true)+8;
        $partial=!urd_scan($redis,function($v) use (&$rows,&$match,$cap,$pattern,$needles,$deadline){
            // Pre-filtre sur la valeur brute : un fragment alphabetique de la recherche
            // figure tel quel dans le message DNS (noms en clair). Evite le decodage.
            foreach($needles as $n){if(stripos($v,$n)===false){return true;}}
            $e=urd_parse($v);
            if(count($e)==0){return true;}
            if(!preg_match($pattern,$e["name"]) && !preg_match($pattern,$e["answer"])){return true;}
            $match++;
            if($match<=$cap){$rows[]=urd_row($e);}
            return microtime(true)<$deadline;
        });
    }
    $html=array();
    $html[]="<div class='ak'><table class='ak-tbl urd-tbl'><thead><tr>";
    $html[]="<th>{urd_name}</th><th>{urd_type}</th><th>{urd_answer}</th><th>{urd_ttl}</th>";
    $html[]="</tr></thead><tbody id='urd-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='4'><div class='ak-empty-c'><i class='fas fa-database' aria-hidden='true'></i><span>{no_data}</span></div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table>";
    $showing=str_replace(array("%s","%t"),array("<strong>".count($rows)."</strong>","<strong>".$tpl->FormatNumber($match)."</strong>"),$tpl->_ENGINE_parse_body("{urd_showing}"));
    $refine=($match>$cap || $partial)?" <span class='ak-tag ak-warn'><i class='fas fa-filter' aria-hidden='true'></i> {urd_refine}</span>":"";
    $html[]="<div class='urd-foot'><span>$showing$refine</span><span class='ak-pg' id='urd-pager'></span></div></div>";
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
    echo urd_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script.awc_pager_js(50,"urd-rows","urd-pager");
    return true;
}

function redis_status():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/unbound/status"));
    if(!is_object($json) || empty($json->Status) || !property_exists($json,"Info")){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{UBOUND_REDIS}<hr>{no_data}"));
        return false;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $page=CurrentPageName();
    $topbuttons=array();
    $jsRedisRestart=$tpl->framework_buildjs("/unbound/redis/restart",
        "unbound-redis.progress","unbound-redis.log","unbound-redis-progress",null,null,null,"AsDnsAdministrator");
    // /unbound/redis/flush (UnboundRedis.FlushAll, progression unbound-redis-flush.progress).
    // L'ancien appel visait unbound.php?remove-redis-database, que le framework n'a
    // jamais eu : le bouton ne faisait rien.
    $remove_db=$tpl->framework_buildjs("/unbound/redis/flush",
        "unbound-redis-flush.progress","unbound-redis.log","unbound-redis-progress",null,null,null,"AsDnsAdministrator");
    // Vider la base est irreversible : confirmation signee avant la tache.
    $remove_db="Loadjs('fw.progress.php?".fw_progress_confirm_args($tpl->_ENGINE_parse_body("{urd_confirm_remove}"),$remove_db)."')";

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"))==1){
        $topbuttons[]=array($jsRedisRestart,ico_retweet,"{restart}");
        $topbuttons[]=array($remove_db,ico_trash,"{REMOVE_DATABASE}");
    }
    $TINY_ARRAY["TITLE"]="{UBOUND_REDIS}";
    $TINY_ARRAY["ICO"]="fa fas fa-database";
    $TINY_ARRAY["EXPL"]="{ubound_redis_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $final=array();
    $final[]=$tpl->SERVICE_STATUS($bsini,"UBOUND_REDIS",$jsRedisRestart);
    $final[]="<script>";
    $final[]="LoadAjaxSilent('top-redis-status','$page?top-redis-status=yes');";
    $final[]=$jstiny;
    $final[]="</script>";
    echo @implode("",$final);
    return true;
}

// Un seul modele : reglage, connexion, INFO de Redis, test d'ecriture.
function urd_model():array{
    $m=array("enabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"))==1,"error"=>"","info"=>array(),"write"=>null);
    if(!$m["enabled"]){return $m;}
    $err="";
    $redis=urd_connect($err);
    if($redis===null){$m["error"]=$err;return $m;}
    $i=$redis->info();
    $m["info"]=is_array($i)?$i:array();
    try{
        $m["write"]=(bool)$redis->set("TESTKEY","TESTVALUE",2);
        if(method_exists($redis,"unlink")){$redis->unlink("TESTKEY");}else{$redis->delete("TESTKEY");}
    }catch(Exception $e){$m["write"]=false;}
    return $m;
}
function urd_iv(array $m,string $k):int{return isset($m["info"][$k])?intval($m["info"][$k]):0;}
function urd_sv(array $m,string $k):string{return isset($m["info"][$k])?(string)$m["info"][$k]:"";}

function redis_widgets(){
    $tpl=new template_admin();
    $m=urd_model();
    $h=array();
    if(!$m["enabled"]){
        $h[]=urd_disabled($tpl);
    }elseif($m["error"]!==""){
        $h[]="<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span class='ak-banner-m'>{urd_unreachable} ".htmlspecialchars($m["error"])."</span></div>";
        $h[]=urd_tiles($m,$tpl);
    }else{
        $h[]=urd_strip($m,$tpl);
        $h[]=urd_tiles($m,$tpl);
    }
    echo urd_style().$tpl->_ENGINE_parse_body("<div class='ak urd-w'>".implode("\n",$h)."</div>");
}

function urd_disabled($tpl):string{
    $js=$tpl->framework_buildjs("/unbound/redis/install","unbound-redis.progress","unbound-redis.log",
        "unbound-redis-progress",null,null,null,"AsDnsAdministrator");
    return "<div class='ak-card ak-empty-c'><i class='fas fa-database' aria-hidden='true'></i>"
        ."<strong>{urd_disabled_t}</strong><span>{urd_disabled_s}</span>"
        ."<button type='button' class='ak-btn ak-btn-primary' OnClick=\"$js\"><i class='fas fa-download' aria-hidden='true'></i> {urd_install}</button></div>";
}

function urd_fact(string $label,string $value):string{
    return "<div class='urd-fact'><span class='ak-kicker'>$label</span><span class='urd-fact-v'>$value</span></div>";
}

function urd_strip(array $m,$tpl):string{
    $up=urd_iv($m,"uptime_in_seconds");
    $write=$m["write"]===true
        ?"<span class='ak-pill ak-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {urd_ok}</span>"
        :"<span class='ak-pill ak-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {urd_failed}</span>";
    $un=$tpl->framework_buildjs("/unbound/redis/uninstall","unbound-redis.progress","unbound-redis.log",
        "unbound-redis-progress",null,null,null,"AsDnsAdministrator");
    $un="Loadjs('fw.progress.php?".fw_progress_confirm_args($tpl->_ENGINE_parse_body("{urd_confirm_uninstall}"),$un)."')";
    $v=urd_sv($m,"redis_version");
    $pol=urd_sv($m,"maxmemory_policy");
    return "<div class='ak-strip urd-strip'><div class='urd-facts'>"
        .urd_fact("{urd_version}","<span class='ak-mono'>".($v!==""?htmlspecialchars($v):"—")."</span>")
        .urd_fact("{urd_uptime}",$up>0?urd_duration($up):"—")
        .urd_fact("{urd_clients}",(string)urd_iv($m,"connected_clients"))
        .urd_fact("{urd_policy}","<span class='ak-mono'>".($pol!==""?htmlspecialchars($pol):"—")."</span>")
        .urd_fact("{urd_write_test}",$write)
        ."</div><div class='urd-acts'><button type='button' class='ak-btn' OnClick=\"Loadjs('fw.dns.unbound.php?unbound-performance-redis-js=yes')\"><i class='fas fa-sliders' aria-hidden='true'></i> {parameters}</button>"
        ."<button type='button' class='ak-btn ak-btn-danger' OnClick=\"$un\"><i class='fas fa-trash-can' aria-hidden='true'></i> {urd_uninstall}</button>"
        ."</div></div>";
}

function urd_tile(string $tone,string $ico,string $label,string $value,string $sub,string $extra=""):string{
    $icon=$extra!==""?$extra:"<span class='ak-ico ak-ico-sq'><i class='fas $ico' aria-hidden='true'></i></span>";
    return "<div class='ak-fig $tone'><div class='ak-fig-txt'><span class='ak-fig-l'>$label</span><span class='ak-fig-v'>$value</span>$sub</div>$icon</div>";
}

// Anneau SVG (aucun etat JS : se redessine avec le fragment).
function urd_ring(float $pct):string{
    $r=22;$c=2*M_PI*$r;$on=max(0,min(100,$pct))/100*$c;
    return "<svg class='urd-ring' width='56' height='56' viewBox='0 0 56 56' aria-hidden='true'><circle cx='28' cy='28' r='$r' fill='none' stroke='#e2e8f0' stroke-width='6'/>"
        ."<circle cx='28' cy='28' r='$r' fill='none' stroke='var(--ak-accent)' stroke-width='6' stroke-linecap='round' stroke-dasharray='".sprintf('%.2f %.2f',$on,$c)."' transform='rotate(-90 28 28)'/></svg>";
}

function urd_tiles(array $m,$tpl):string{
    $ok=$m["error"]==="";
    // Memoire, mesuree contre maxmemory quand Redis a un plafond.
    $used=urd_iv($m,"used_memory");$max=urd_iv($m,"maxmemory");
    if(!$ok){
        $mem=urd_tile("","fa-memory","{urd_memory}","—","");
    }else{
        $val=htmlspecialchars(urd_sv($m,"used_memory_human"));
        if($max>0){
            $pct=round($used/$max*100,1);
            $tone=$pct>=90?"ak-err":($pct>=75?"ak-warn":"");
            $sub="<div class='ak-bar $tone'><span style='width:".min(100,$pct)."%'></span></div><span class='ak-fig-s'>".str_replace("%s",$pct."%",$tpl->_ENGINE_parse_body("{urd_of_max}"))."</span>";
            $val.=" <span class='urd-unit'>/ ".htmlspecialchars(urd_sv($m,"maxmemory_human"))."</span>";
            $mem=urd_tile($tone,"fa-memory","{urd_memory}",$val,$sub);
        }else{
            $mem=urd_tile("","fa-memory","{urd_memory}",$val,"<span class='ak-fig-s'>{urd_no_limit}</span>");
        }
    }
    // Enregistrements : cles de db0.
    $keys=0;
    if(isset($m["info"]["db0"]) && preg_match('#keys=([0-9]+)#',(string)$m["info"]["db0"],$re)){$keys=intval($re[1]);}
    $rec=urd_tile("","fa-database","{urd_records}",$ok?$tpl->FormatNumber($keys):"—",$ok?"<span class='ak-fig-s'>{urd_records_sub}</span>":"");
    // Efficacite : hits / (hits + misses).
    $hit=urd_iv($m,"keyspace_hits");$mis=urd_iv($m,"keyspace_misses");
    if(!$ok){
        $eff=urd_tile("","fa-bullseye","{urd_efficiency}","—","");
    }elseif($hit+$mis==0){
        $eff=urd_tile("","fa-bullseye","{urd_efficiency}","—","<span class='ak-fig-s'>{urd_no_lookups}</span>");
    }else{
        $pct=round($hit/($hit+$mis)*100,1);
        $sub=str_replace(array("%h","%m"),array($tpl->FormatNumber($hit),$tpl->FormatNumber($mis)),$tpl->_ENGINE_parse_body("{urd_hits_misses}"));
        $eff=urd_tile("","fa-bullseye","{urd_efficiency}","$pct %","<span class='ak-fig-s'>$sub</span>",urd_ring($pct));
    }
    // Connexions recues, cles expirees et evincees.
    $sub=str_replace(array("%e","%v"),array($tpl->FormatNumber(urd_iv($m,"expired_keys")),$tpl->FormatNumber(urd_iv($m,"evicted_keys"))),$tpl->_ENGINE_parse_body("{urd_exp_evict}"));
    $con=urd_tile("","fa-plug","{urd_connections}",$ok?$tpl->FormatNumber(urd_iv($m,"total_connections_received")):"—",$ok?"<span class='ak-fig-s'>$sub</span>":"");
    return "<div class='ak-grid-4 urd-tiles'>$mem$rec$eff$con</div>";
}

/**
 * Reglages Redis du cache DNS (activation, purge) : groupe redis de
 * PUT /unbound/api/settings, qui valide, regenere, verifie par
 * unbound-checkconf, redemarre et prouve. SAVE_POSTs ecrivait toute cle postee,
 * sans controle de privilege. Le cycle de vie de Redis (installation,
 * redemarrage, purge) reste sur ses routes, hors de ce handler.
 */
function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!$tpl->IsSecurity("AsDnsAdministrator")){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
    $values=array();
    foreach(array("UnboundRedisEnabled","UnBoundRedisDBTTL") as $k){
        if(isset($_POST[$k])){$values[$k]=$_POST[$k];}
    }
    $split=unbound_settings::split($values,array());
    $res=unbound_settings::apply(new unbound_api(),$split["groups"]);
    if(!$res["ok"]){ echo $tpl->post_error($res["error"]);return false; }
    unbound_settings::queue($res);
    admin_tracks("Redis database for Cache DNS settings changed");
    return true;
}