<?php
// Evenements des proxys parents : /var/log/squid/peers.log, ecrit par articarest
// (squid/squidtail.go -> notifs.PeerEvent) avec trois familles de lignes de cache.log :
// « Configuring Parent… », « Detected DEAD|REVIVED Parent: PeerNN », « Connection to PeerNN failed… ».
// GET /proxy/parents/events/{max}/{search} rend les lignes, les plus recentes d'abord (rg, insensible a la casse).
// GET /proxy/peers/status rend l'etat en direct de chaque parent (squidclient server_list + test TCP).
// Maquette Stitch « upstream proxy event dashboard » (2026-11-21), donnees reelles seulement :
// la duree d'une panne n'existe que si le DEAD et le REVIVED suivant sont dans les lignes lues,
// « Not back yet » seulement si l'etat en direct dit encore Down.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once(dirname(__FILE__)."/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();
if(!$users->AsFirewallManager){
    $tpl=new template_admin();
    echo $tpl->div_error("Privileges error");
    exit();
}

if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["ppe-host"])){ppe_host();exit;}
// Anciennes URL (search_block d'avant la refonte, navigateur reste ouvert pendant la mise a jour).
if(isset($_GET["form"])){build_page();exit;}
if(isset($_GET["search"])){ppe_host();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{parent_proxies} {events}",ico_eye,"{ppe_tiny}",
        "$page?build-page=yes","parents-events","progress-parents-restart",false,"parents-events");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{parent_proxies}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ---------------------------------------------------------------- helpers

function ppe_t(string $token,array $vals=array()):string{
    $tpl=new template_admin();
    $txt=html_entity_decode($tpl->_ENGINE_parse_body("{".$token."}"),ENT_QUOTES,"UTF-8");
    $txt=trim(preg_replace('#\s*<br\s*/?>\s*#i',' ',str_replace("[br]","<br>",$txt)));
    foreach($vals as $v){$p=strpos($txt,"%s");if($p===false){break;}$txt=substr_replace($txt,(string)$v,$p,2);}
    return $txt;
}
function ppe_h(string $token,array $vals=array()):string{return ppe_e(ppe_t($token,$vals));}
// Comme ppe_h, mais les valeurs sont du HTML deja sur (chiffres mis en gras) : on echappe le texte d'abord.
function ppe_hraw(string $token,array $html):string{
    $txt=ppe_e(ppe_t($token));
    foreach($html as $v){$p=strpos($txt,"%s");if($p===false){break;}$txt=substr_replace($txt,(string)$v,$p,2);}
    return $txt;
}
// Valeur venue du journal ou de Squid : HTML ET accolades (le moteur prendrait {x} pour un token).
function ppe_e($v):string{return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));}
function ppe_num($n):string{return number_format(floatval($n),0,".",",");}
function ppe_limits():array{return array(250,500,1000);}
function ppe_types():array{return array("","fail","back","load");}

function ppe_ago(int $ts):string{
    $s=max(0,time()-$ts);
    if($s<60){return ppe_t("ppe_just_now");}
    if($s<3600){return ppe_t("ppe_ago_m",array(intval(floor($s/60))));}
    if($s<172800){return ppe_t("ppe_ago_h",array(intval(floor($s/3600))));}
    return ppe_t("ppe_ago_d",array(intval(floor($s/86400))));
}
function ppe_duration(int $s):string{
    if($s<60){return ppe_t("ppe_dur_s",array(max(1,$s)));}
    if($s<3600){return ppe_t("ppe_dur_m",array(intval(round($s/60))));}
    if($s<86400){return ppe_t("ppe_dur_hm",array(intval(floor($s/3600)),intval(floor(($s%3600)/60))));}
    return ppe_t("ppe_dur_d",array(intval(floor($s/86400)),intval(floor(($s%86400)/3600))));
}

// ---------------------------------------------------------------- model

function ppe_params():array{
    $type=isset($_GET["type"])?(string)$_GET["type"]:"";
    if(!in_array($type,ppe_types(),true)){$type="";}
    $peer=isset($_GET["peer"])?(string)$_GET["peer"]:"";
    if(!preg_match('/^Peer[0-9]{1,6}$/',$peer)){$peer="";}
    $q=isset($_GET["q"])?trim((string)$_GET["q"]):"";
    if(function_exists("mb_substr")){$q=mb_substr($q,0,80,"UTF-8");}else{$q=substr($q,0,80);}
    $max=isset($_GET["max"])?intval($_GET["max"]):250;
    if(!in_array($max,ppe_limits(),true)){$max=250;}
    return array("type"=>$type,"peer"=>$peer,"q"=>$q,"max"=>$max);
}

// Etat en direct (squidclient server_list) : liste vide si aucun parent ou si Squid ne repond pas.
function ppe_peers():array{
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/peers/status");
    $json=json_decode((string)$data);
    $out=array();
    if(!is_object($json) || !isset($json->peers) || !is_array($json->peers)){return $out;}
    foreach($json->peers as $p){
        if(!is_object($p) || !isset($p->parent_name)){continue;}
        $name=trim((string)$p->parent_name);
        if($name===""){continue;}
        $addr=isset($p->parent_addr)?trim((string)$p->parent_addr):"";
        $port=isset($p->parent_port)?intval($p->parent_port):0;
        $st=strtolower(trim(isset($p->status)?(string)$p->status:""));
        $state="unknown";
        if($st==="up"){$state="up";}
        if($st==="down"){$state="down";}
        $out[$name]=array(
            "name"=>$name,"addr"=>$addr,"port"=>$port,
            "host"=>($addr==="")?$name:($port>0?"$addr:$port":$addr),
            "state"=>$state,
            "fetches"=>isset($p->fetches)?floatval($p->fetches):0,
            "conns"=>isset($p->open_conns)?floatval($p->open_conns):0,
            "err"=>trim(isset($p->telneterror)?(string)$p->telneterror:""),
        );
    }
    uasort($out,function($a,$b){return strnatcasecmp($a["host"],$b["host"]);});
    return $out;
}

// Motif rg envoye au daemon : le texte cherche en litteral, plus les noms techniques
// des parents dont l'adresse contient ce texte (le journal ne cite que « PeerNN »).
function ppe_pattern(array $p,array $peers):string{
    $alts=array();
    if(strlen($p["q"])>=3){
        $alts[]=preg_quote($p["q"],"#");
        foreach($peers as $name=>$pe){
            if(stripos($pe["host"],$p["q"])!==false){$alts[]=preg_quote($name,"#")."\\b";}
        }
    }elseif($p["peer"]!==""){
        $alts[]=$p["peer"]."\\b";
        if(isset($peers[$p["peer"]]) && $peers[$p["peer"]]["addr"]!==""){$alts[]=preg_quote($peers[$p["peer"]]["addr"],"#");}
    }
    if(count($alts)==0){return "NONE";}
    return implode("|",array_unique($alts));
}

function ppe_kind(string $text):string{
    if(stripos($text,"REVIVED")!==false){return "back";}
    if(stripos($text,"DEAD")!==false){return "down";}
    if(stripos($text,"Connection to")!==false && stripos($text,"fail")!==false){return "connfail";}
    if(stripos($text,"Configuring")!==false){return "loaded";}
    return "other";
}

function ppe_parse_line(string $raw,array $peers):array{
    $e=array("raw"=>$raw,"date"=>"","ts"=>0,"text"=>$raw,"peer"=>"","kind"=>"other","dur"=>-1,"open"=>false);
    if(preg_match('#^(\d{4}[/-]\d{2}[/-]\d{2})\s+(\d{2}:\d{2}:\d{2})(?:\.\d+)?\s+(.*)$#',$raw,$re)){
        $e["date"]=$re[1]." ".$re[2];
        $e["text"]=trim($re[3]);
        $ts=strtotime(str_replace("/","-",$re[1])." ".$re[2]);
        if($ts!==false){$e["ts"]=intval($ts);}
    }
    if(preg_match('#\b(Peer[0-9]+)\b#',$e["text"],$re)){
        $e["peer"]=$re[1];
    }else{
        foreach($peers as $name=>$pe){
            if($pe["addr"]!=="" && strpos($e["text"],$pe["addr"])!==false){$e["peer"]=$name;break;}
        }
    }
    $e["kind"]=ppe_kind($e["text"]);
    return $e;
}

function ppe_type_match(string $type,string $kind):bool{
    if($type===""){return true;}
    if($type==="fail"){return $kind==="down" || $kind==="connfail";}
    if($type==="back"){return $kind==="back";}
    if($type==="load"){return $kind==="loaded";}
    return true;
}

function ppe_model(array $p):array{
    $m=array("p"=>$p,"error"=>"","peers"=>array(),"events"=>array(),"read"=>0,
        "counts"=>array(""=>0,"fail"=>0,"back"=>0,"load"=>0),"up"=>0,"down"=>0,"unknown"=>0);
    $m["peers"]=ppe_peers();
    foreach($m["peers"] as $pe){$m[$pe["state"]]++;}

    $pattern=ppe_pattern($p,$m["peers"]);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/events/".intval($p["max"])."/".rawurlencode($pattern));
    $json=json_decode((string)$data);
    if(json_last_error()>JSON_ERROR_NONE || !is_object($json)){
        $m["error"]="articarest: ".json_last_error_msg();
        return $m;
    }
    if(!isset($json->Status) || !$json->Status){
        $m["error"]=isset($json->Error)&&trim((string)$json->Error)!==""?(string)$json->Error:"articarest: unknown error";
        return $m;
    }
    $all=array();
    if(isset($json->Logs) && is_array($json->Logs)){
        foreach($json->Logs as $line){
            $line=trim((string)$line);
            if($line===""){continue;}
            $all[]=ppe_parse_line($line,$m["peers"]);
        }
    }
    $m["read"]=count($all);

    // Appariement DEAD -> REVIVED suivant, du plus ancien au plus recent (le daemon rend le plus recent d'abord).
    $pending=array();
    for($i=count($all)-1;$i>=0;$i--){
        $e=$all[$i];
        if($e["peer"]===""){continue;}
        if($e["kind"]==="down" && !isset($pending[$e["peer"]])){$pending[$e["peer"]]=$i;continue;}
        if($e["kind"]==="back" && isset($pending[$e["peer"]])){
            $d=$all[$pending[$e["peer"]]];
            if($d["ts"]>0 && $e["ts"]>=$d["ts"]){$all[$i]["dur"]=$e["ts"]-$d["ts"];}
            unset($pending[$e["peer"]]);
        }
    }
    foreach($pending as $peer=>$idx){
        if(isset($m["peers"][$peer]) && $m["peers"][$peer]["state"]==="down"){$all[$idx]["open"]=true;}
    }

    foreach($all as $e){
        if($p["peer"]!=="" && $e["peer"]!==$p["peer"]){continue;}
        $m["counts"][""]++;
        foreach(array("fail","back","load") as $t){if(ppe_type_match($t,$e["kind"])){$m["counts"][$t]++;}}
        if(!ppe_type_match($p["type"],$e["kind"])){continue;}
        $m["events"][]=$e;
    }
    return $m;
}

// ---------------------------------------------------------------- widgets

function ppe_banner(array $m):string{
    $n=count($m["peers"]);
    $chips=array();
    if($n==0){
        $chips[]="<span class='ppe-chip'><i class='fa-solid fa-server' aria-hidden='true'></i>".ppe_h("ppe_chip_none")."</span>";
    }else{
        $chips[]="<span class='ppe-chip'><i class='fa-solid fa-server' aria-hidden='true'></i>".ppe_h($n==1?"ppe_chip_one":"ppe_chip_many",array($n))."</span>";
        $cls=$m["down"]>0?"ppe-chip bad":"ppe-chip";
        $ico=$m["down"]>0?"fa-circle-xmark":"fa-circle-check";
        $chips[]="<span class='$cls'><i class='fa-solid $ico' aria-hidden='true'></i>".ppe_h("ppe_chip_state",array($m["up"],$m["down"]))."</span>";
    }
    $chips[]="<span class='ppe-chip'><i class='fa-solid fa-lock' aria-hidden='true'></i>".ppe_h("ppe_chip_ro")."</span>";
    return "<div class='ppe-card ppe-banner'>
<div class='ppe-bico'><i class='fa-solid fa-diagram-project' aria-hidden='true'></i></div>
<div class='ppe-btxt'><h2>".ppe_h("ppe_banner_title")."</h2><p>".ppe_h("ppe_banner_text")."</p>
<div class='ppe-chips'>".implode("",$chips)."</div></div>
<a class='ppe-ghost' href='/proxy-parents-rules'><i class='fa-solid fa-sliders' aria-hidden='true'></i><span>".ppe_h("ppe_manage")."</span></a>
</div>";
}

function ppe_state_pill(string $state):string{
    if($state==="up"){return "<span class='ppe-pill ok'><i class='ppe-dot' aria-hidden='true'></i>".ppe_h("ppe_online")."</span>";}
    if($state==="down"){return "<span class='ppe-pill bad'><i class='ppe-dot' aria-hidden='true'></i>".ppe_h("ppe_down")."</span>";}
    return "<span class='ppe-pill neu'><i class='ppe-dot' aria-hidden='true'></i>".ppe_h("ppe_unknown")."</span>";
}

function ppe_status_card(array $pe,string $selected):string{
    $on=($selected===$pe["name"]);
    $err="";
    if($pe["err"]!==""){$err="<span class='ppe-sc-err'>".ppe_h("ppe_card_err",array($pe["err"]))."</span>";}
    return "<button type='button' class='ppe-card ppe-sc".($on?" on":"")."' data-peer='".ppe_e($pe["name"])."' aria-pressed='".($on?"true":"false")."' aria-label='".ppe_h("ppe_card_aria",array($pe["host"]))."'>
<span class='ppe-sc-head'><span class='ppe-sc-id'><b class='mono' title='".ppe_e($pe["host"])."'>".ppe_e($pe["host"])."</b><small>".ppe_e($pe["name"])."</small></span>".ppe_state_pill($pe["state"])."</span>
<span class='ppe-sc-l'>".ppe_hraw("ppe_card_fetches",array("<b>".ppe_num($pe["fetches"])."</b>"))."</span>
<span class='ppe-sc-l'>".ppe_hraw("ppe_card_conns",array("<b>".ppe_num($pe["conns"])."</b>"))."</span>$err
</button>";
}

function ppe_status_empty():string{
    return "<div class='ppe-card ppe-sc-empty'><i class='fa-solid fa-route' aria-hidden='true'></i>
<div><b>".ppe_h("ppe_none_title")."</b><p>".ppe_h("ppe_none_text")."</p>
<a href='/proxy-parents-rules'>".ppe_h("ppe_none_link")."</a></div></div>";
}

function ppe_status_strip(array $m):string{
    $cards=array();
    foreach($m["peers"] as $pe){$cards[]=ppe_status_card($pe,$m["p"]["peer"]);}
    $body=count($cards)==0?ppe_status_empty():"<div class='ppe-grid'>".implode("",$cards)."</div>";
    $hint=count($cards)==0?"":"<span class='ppe-sub'>".ppe_h("ppe_now_hint")."</span>";
    return "<section class='ppe-now' aria-labelledby='ppe-now-h'><div class='ppe-sech'><div><h3 id='ppe-now-h'>".ppe_h("ppe_now_title")."</h3><span class='ppe-sub'>".ppe_h("ppe_now_sub")."</span></div>$hint</div>$body</section>";
}

function ppe_filters(array $p):string{
    $seg=array();
    $labels=array(""=>"ppe_t_all","fail"=>"ppe_t_fail","back"=>"ppe_t_back","load"=>"ppe_t_load");
    foreach($labels as $k=>$tok){
        $on=($p["type"]===$k);
        $seg[]="<button type='button' role='radio' class='ppe-sbtn".($on?" on":"")."' data-t='$k' aria-checked='".($on?"true":"false")."' onclick=\"PpeType('$k')\">".ppe_h($tok)."<em data-c='$k'></em></button>";
    }
    $opts=array();
    foreach(ppe_limits() as $l){$opts[]="<option value='$l'".($l==$p["max"]?" selected":"").">".ppe_h("ppe_lines_opt",array($l))."</option>";}
    $peerOpt="<option value=''>".ppe_h("ppe_all_parents")."</option>";
    if($p["peer"]!==""){$peerOpt.="<option value='".ppe_e($p["peer"])."' selected>".ppe_e($p["peer"])."</option>";}
    return "<div class='ppe-card ppe-filters'>
<div class='ppe-frow'>
<div class='ppe-seg' role='radiogroup' aria-label='".ppe_h("ppe_type_aria")."'>".implode("",$seg)."</div>
<label class='ppe-sel'><span class='sr'>".ppe_h("ppe_parent_aria")."</span><select id='ppe-peer' onchange='PpePeer(this.value)'>$peerOpt</select></label>
<label class='ppe-search'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i><span class='sr'>".ppe_h("ppe_search_ph")."</span><input type='search' id='ppe-q' value='".ppe_e($p["q"])."' placeholder='".ppe_h("ppe_search_ph")."' autocomplete='off' maxlength='80'></label>
<label class='ppe-sel'><span class='sr'>".ppe_h("ppe_lines_aria")."</span><select id='ppe-max' onchange='PpeMax(this.value)'>".implode("",$opts)."</select></label>
<button type='button' class='ppe-ghost' onclick='PpeReload()'><i class='fa-solid fa-rotate' aria-hidden='true'></i><span>".ppe_h("ppe_refresh")."</span></button>
</div>
<div class='ppe-frow2'><span>".ppe_h("ppe_examples")."</span>
<button type='button' class='ppe-ex mono' onclick=\"PpeSearch('DEAD')\">DEAD</button>
<button type='button' class='ppe-ex mono' onclick=\"PpeSearch('REVIVED')\">REVIVED</button>
<span id='ppe-ex-addr'></span>
<span class='ppe-help'>".ppe_h("ppe_lines_help")."</span></div>
</div>";
}

function ppe_event_pill(string $kind):string{
    $map=array("down"=>array("bad","ppe_k_down"),"back"=>array("ok","ppe_k_back"),"connfail"=>array("warn","ppe_k_connfail"),
        "loaded"=>array("neu","ppe_k_loaded"),"other"=>array("neu","ppe_k_other"));
    $x=isset($map[$kind])?$map[$kind]:$map["other"];
    return "<span class='ppe-pill {$x[0]} k-$kind'><i class='ppe-dot' aria-hidden='true'></i>".ppe_h($x[1])."</span>";
}

function ppe_peer_cell(array $e,array $peers):string{
    if($e["peer"]===""){return "<span class='ppe-muted'>—</span>";}
    if(isset($peers[$e["peer"]])){
        return "<b class='mono'>".ppe_e($peers[$e["peer"]]["host"])."</b><small>".ppe_e($e["peer"])."</small>";
    }
    return "<i class='ppe-unk'>".ppe_h("ppe_unknown_parent",array($e["peer"]))."</i><small>".ppe_h("ppe_unknown_sub")."</small>";
}

function ppe_sentence(array $e,array $peers):string{
    $who=$e["peer"]===""?"":(isset($peers[$e["peer"]])?$peers[$e["peer"]]["host"]:$e["peer"]);
    if($who===""){return ppe_e($e["text"]);}
    if($e["kind"]==="down"){return ppe_h("ppe_s_down",array($who));}
    if($e["kind"]==="back"){
        if($e["dur"]>=0){return ppe_h("ppe_s_back_dur",array($who,ppe_duration($e["dur"])));}
        return ppe_h("ppe_s_back",array($who));
    }
    if($e["kind"]==="connfail"){return ppe_h("ppe_s_connfail",array($who));}
    if($e["kind"]==="loaded"){return ppe_h("ppe_s_loaded",array($who));}
    return ppe_e($e["text"]);
}

function ppe_row(array $e,array $peers,int $i):string{
    $when="<span class='ppe-muted'>—</span>";
    if($e["ts"]>0){$when="<b title='".ppe_e($e["date"])."'>".ppe_e(ppe_ago($e["ts"]))."</b><small class='mono'>".ppe_e($e["date"])."</small>";}
    elseif($e["date"]!==""){$when="<b>".ppe_e($e["date"])."</b>";}
    $tag=$e["open"]?" <span class='ppe-tag'>".ppe_h("ppe_not_back")."</span>":"";
    // Ligne brute construite au premier depliage (data-raw) : 1000 lignes x un bloc cache alourdissaient le DOM.
    $id="ppe-raw-$i";
    return "<tr class='ppe-row' tabindex='0' aria-expanded='false' aria-controls='$id' data-raw='".ppe_e($e["raw"])."'>
<td class='ppe-when'>$when</td>
<td>".ppe_event_pill($e["kind"])."</td>
<td class='ppe-peer'>".ppe_peer_cell($e,$peers)."</td>
<td class='ppe-what'><span class='ppe-chev' aria-hidden='true'></span>".ppe_sentence($e,$peers)."$tag</td>
</tr>";
}

function ppe_empty(array $m):string{
    $p=$m["p"];
    $filtered=($p["q"]!=="" || $p["peer"]!=="" || $p["type"]!=="");
    if(!$filtered){
        return "<div class='ppe-empty'><span class='ppe-eico ok'><i class='fa-solid fa-circle-check' aria-hidden='true'></i></span><b>".ppe_h("ppe_empty_title")."</b><p>".ppe_h("ppe_empty_text")."</p></div>";
    }
    $what=$p["q"]!==""?ppe_h("ppe_nomatch_q",array($p["q"])):ppe_h("ppe_nomatch");
    return "<div class='ppe-empty'><span class='ppe-eico'><i class='fa-solid fa-filter-circle-xmark' aria-hidden='true'></i></span><b>$what</b><p>".ppe_h("ppe_nomatch_text")."</p><button type='button' class='ppe-ghost' onclick='PpeClear()'>".ppe_h("ppe_clear")."</button></div>";
}

function ppe_history(array $m):string{
    $n=count($m["events"]);
    $count=ppe_h("ppe_count",array(ppe_num($n),ppe_num($m["read"])));
    $head="<div class='ppe-hh'><div><h3>".ppe_h("ppe_hist_title")."</h3><span class='ppe-sub'>".ppe_h("ppe_hist_sub")."</span></div><span class='ppe-sub'>$count</span></div>";
    if($n==0){return "<div class='ppe-card ppe-hist'>$head".ppe_empty($m)."</div>";}
    $rows=array();
    foreach($m["events"] as $i=>$e){$rows[]=ppe_row($e,$m["peers"],$i);}
    return "<div class='ppe-card ppe-hist'>$head
<div class='ppe-scroll'><table class='ppe-tbl'><thead><tr><th>".ppe_h("ppe_col_when")."</th><th>".ppe_h("ppe_col_event")."</th><th>".ppe_h("ppe_col_parent")."</th><th>".ppe_h("ppe_col_what")."</th></tr></thead>
<tbody id='ppe-rows'>".implode("",$rows)."</tbody></table></div>
<div class='ppe-foot'><span id='ppe-showing'></span><span class='ppe-pg' id='ppe-pager'></span></div></div>";
}

function ppe_error(string $err):string{
    return "<div class='ppe-card ppe-err' role='alert'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><div><b>".ppe_h("ppe_err_title")."</b><p class='mono'>".ppe_e($err)."</p></div><button type='button' class='ppe-ghost' onclick='PpeReload()'>".ppe_h("ppe_retry")."</button></div>";
}

// Donnees passees au JS du hote : parents (liste deroulante, exemple d'adresse) et compteurs par type.
function ppe_host_data(array $m):string{
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $peers=array();
    foreach($m["peers"] as $pe){$peers[]=array("v"=>$pe["name"],"l"=>$pe["host"]." (".$pe["name"].")","a"=>$pe["addr"]);}
    return "<script>window.PpeData=".json_encode(array("peers"=>$peers,"counts"=>($m["error"]!==""?new stdClass():$m["counts"])),$J).";</script>";
}

// ---------------------------------------------------------------- style / js

function ppe_style():string{
    return "<style>
.ppe{margin:10px 0 20px 0;color:#1f2937;font-size:14px}
.ppe *{box-sizing:border-box}
.ppe .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.ppe .sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.ppe-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);margin-bottom:16px}
.ppe-banner{display:flex;gap:18px;align-items:flex-start;padding:20px 24px;border-left:4px solid #005447}
.ppe-bico{flex:0 0 44px;height:44px;border-radius:50%;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:19px}
.ppe-btxt{flex:1;min-width:0}
.ppe-btxt h2{margin:0 0 6px 0;font-size:20px;font-weight:600;color:#111827}
.ppe-btxt p{margin:0;font-size:14px;color:#4b5563;line-height:1.55;max-width:960px}
.ppe-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.ppe-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f2f6f6;color:#005447;font-size:13px;border:1px solid #e6eeed}
.ppe-chip.bad{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.ppe-ghost{display:inline-flex;align-items:center;gap:8px;white-space:nowrap;padding:8px 14px;border:1px solid #bfd4d1;border-radius:8px;background:#fff;color:#005447;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none}
.ppe-ghost:hover,.ppe-ghost:focus{background:#e6eeed;color:#003d33;text-decoration:none}
.ppe button:focus-visible,.ppe a:focus-visible,.ppe select:focus-visible,.ppe input:focus-visible,.ppe tr:focus-visible{outline:2px solid #005447;outline-offset:2px}
.ppe-sech{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;margin:4px 2px 10px 2px}
.ppe-sech h3,.ppe-hh h3{margin:0;font-size:16px;font-weight:600;color:#111827}
.ppe-sub{display:block;font-size:13px;color:#6b7280}
.ppe-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}
.ppe-sc{display:flex;flex-direction:column;align-items:stretch;gap:6px;text-align:left;padding:16px 18px;margin:0;cursor:pointer;font:inherit;color:inherit;min-width:0}
.ppe-sc:hover{border-color:#bfd4d1}
.ppe-sc.on{border-color:#005447;box-shadow:0 0 0 1px #005447}
.ppe-sc-head{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:4px}
.ppe-sc-id{display:flex;flex-direction:column;min-width:0}
.ppe-sc-id b{font-size:14px;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ppe-sc-id small,.ppe-peer small,.ppe-when small{display:block;font-size:13px;color:#6b7280}
.ppe-sc-l{font-size:13px;color:#4b5563}
.ppe-sc-l b{color:#111827;font-weight:600}
.ppe-sc-err{font-size:13px;color:#b91c1c;overflow-wrap:anywhere}
.ppe-sc-empty{display:flex;gap:16px;align-items:flex-start;padding:18px 20px}
.ppe-sc-empty>i,.ppe-sc-empty>svg{font-size:22px;color:#005447;margin-top:2px}
.ppe-sc-empty p{margin:4px 0 8px 0;color:#4b5563}
.ppe-sc-empty a{color:#005447;font-weight:600}
.ppe-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}
.ppe-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:currentColor;flex:0 0 8px}
.ppe-pill.ok{background:#e6eeed;color:#005447}
.ppe-pill.bad{background:#fef2f2;color:#b91c1c}
.ppe-pill.warn{background:#fffbeb;color:#92400e}
.ppe-pill.neu{background:#f3f4f6;color:#374151}
.ppe-pill.k-down .ppe-dot{border-radius:2px;transform:rotate(45deg)}
.ppe-pill.k-connfail .ppe-dot{border-radius:1px}
.ppe-filters{padding:14px 18px}
.ppe-frow{display:flex;flex-wrap:wrap;gap:12px;align-items:center}
.ppe-seg{display:inline-flex;background:#f3f4f6;border-radius:10px;padding:4px;gap:2px}
.ppe-sbtn{border:0;background:transparent;padding:7px 14px;border-radius:8px;font-size:14px;color:#374151;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.ppe-sbtn:hover{background:#e5e7eb}
.ppe-sbtn.on{background:#005447;color:#fff}
.ppe-sbtn em{font-style:normal;font-size:13px;color:#6b7280}
.ppe-sbtn.on em{color:#e6eeed}
.ppe-sel{margin:0;font-weight:400}
.ppe-sel select{height:38px;border:1px solid #e5e7eb;border-radius:8px;padding:0 10px;background:#fff;font-size:14px;color:#111827;max-width:240px}
.ppe-search{flex:1;min-width:300px;position:relative;margin:0;font-weight:400}
.ppe-search>i,.ppe-search>svg{position:absolute;left:12px;top:12px;color:#6b7280;font-size:14px}
.ppe-search input{width:100%;height:38px;border:1px solid #e5e7eb;border-radius:8px;padding:0 12px 0 34px;font-size:14px}
.ppe-frow2{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px;font-size:13px;color:#6b7280}
.ppe-ex{border:1px solid #e5e7eb;background:#f9fafb;border-radius:6px;padding:2px 8px;font-size:13px;color:#374151;cursor:pointer}
.ppe-ex:hover{background:#e6eeed;border-color:#bfd4d1;color:#005447}
.ppe-help{margin-left:auto}
.ppe-hist{padding:0;overflow:hidden}
.ppe-hh{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;padding:16px 20px 12px 20px}
.ppe-scroll{overflow-x:auto}
.ppe-tbl{width:100%;border-collapse:collapse;table-layout:fixed}
.ppe-tbl th{text-align:left;font-size:13px;font-weight:600;color:#6b7280;padding:8px 20px;border-bottom:1px solid #e5e7eb;background:#f9fafb}
.ppe-tbl th:nth-child(1){width:17%}.ppe-tbl th:nth-child(2){width:16%}.ppe-tbl th:nth-child(3){width:21%}
.ppe-tbl td{padding:12px 20px;border-bottom:1px solid #f1f5f9;vertical-align:top;color:#111827}
.ppe-row{cursor:pointer}
.ppe-row:hover td{background:#fafafa}
.ppe-when b{font-weight:600}
.ppe-peer b{font-size:13px;font-weight:600;overflow-wrap:anywhere}
.ppe-unk{color:#6b7280}
.ppe-muted{color:#9ca3af}
.ppe-what{overflow-wrap:anywhere}
.ppe-chev{float:right;width:8px;height:8px;margin:6px 0 0 10px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(45deg);transition:transform .15s}
.ppe-row[aria-expanded=true] .ppe-chev{transform:rotate(225deg)}
.ppe-tag{display:inline-block;margin-left:8px;padding:1px 8px;border-radius:999px;background:#fffbeb;color:#92400e;font-size:13px;font-weight:600}
.ppe-raw{margin-top:10px;padding:10px 12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;display:flex;gap:10px;align-items:flex-start;cursor:default}
.ppe-raw[hidden]{display:none}
.ppe-rawl{font-size:13px;color:#6b7280;flex:0 0 auto}
.ppe-raw pre{flex:1;margin:0;padding:0;border:0;background:transparent;font-size:13px;white-space:pre-wrap;word-break:normal;overflow-wrap:anywhere;color:#111827}
.ppe-copy{border:0;background:transparent;color:#005447;font-weight:600;font-size:13px;cursor:pointer;white-space:nowrap}
.ppe-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 20px;font-size:13px;color:#6b7280}
.ppe-pg{display:flex;gap:4px}
.ppe-pg button{min-width:34px;height:32px;border:1px solid #e5e7eb;background:#fff;border-radius:8px;font-size:13px;color:#374151;cursor:pointer;padding:0 10px}
.ppe-pg button.on{background:#005447;border-color:#005447;color:#fff}
.ppe-pg button:disabled{opacity:.45;cursor:default}
.ppe-empty{text-align:center;padding:40px 20px 44px 20px}
.ppe-empty b{display:block;font-size:16px;color:#111827;margin-top:10px}
.ppe-empty p{margin:6px auto 14px auto;max-width:520px;color:#4b5563}
.ppe-eico{display:inline-flex;width:48px;height:48px;border-radius:50%;background:#f3f4f6;color:#374151;align-items:center;justify-content:center;font-size:20px}
.ppe-eico.ok{background:#e6eeed;color:#005447}
.ppe-err{display:flex;gap:14px;align-items:flex-start;padding:16px 20px;background:#fef2f2;border-color:#fecaca;color:#991b1b}
.ppe-err>div{flex:1;min-width:0}
.ppe-err p{margin:4px 0 0 0;font-size:13px;overflow-wrap:anywhere}
@media (max-width:1200px){.ppe-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ppe-help{margin-left:0}}
@media (prefers-reduced-motion:reduce){.ppe-chev{transition:none}}
</style>";
}

function ppe_js(array $p):string{
    $page=CurrentPageName();
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $st=json_encode(array("type"=>$p["type"],"peer"=>$p["peer"],"max"=>$p["max"]),$J);
    $tx=json_encode(array("prev"=>ppe_t("ppe_prev"),"next"=>ppe_t("ppe_next"),"copied"=>ppe_t("ppe_copied"),
        "all"=>ppe_t("ppe_all_parents"),"raw"=>ppe_t("ppe_raw"),"copy"=>ppe_t("ppe_copy"),"showing"=>ppe_t("ppe_showing")),$J);
    return "<script>NoSpinner();(function(){var P=".json_encode($page,$J).",T=$tx;
window.PpeS=$st;
function el(id){return document.getElementById(id);}
function url(){var q=el('ppe-q');return P+'?ppe-host=yes&type='+encodeURIComponent(PpeS.type)+'&peer='+encodeURIComponent(PpeS.peer)+'&max='+PpeS.max+'&q='+encodeURIComponent(q?q.value:'');}
window.PpeReload=function(){LoadAjaxSilent('ppe-host',url());};
window.PpeType=function(t){PpeS.type=t;var b=document.querySelectorAll('.ppe-sbtn');for(var i=0;i<b.length;i++){var on=b[i].getAttribute('data-t')===t;b[i].className='ppe-sbtn'+(on?' on':'');b[i].setAttribute('aria-checked',on?'true':'false');}PpeReload();};
window.PpePeer=function(v){PpeS.peer=v||'';var s=el('ppe-peer');if(s&&s.value!==PpeS.peer){s.value=PpeS.peer;}PpeReload();};
window.PpeMax=function(v){PpeS.max=parseInt(v,10)||250;PpeReload();};
window.PpeSearch=function(t){var q=el('ppe-q');if(q){q.value=t;}PpeReload();};
window.PpeClear=function(){var q=el('ppe-q');if(q){q.value='';}PpeS.peer='';var s=el('ppe-peer');if(s){s.value='';}PpeType('');};
function esc(s){return String(s).replace(/[&<>\"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',\"'\":'&#39;'}[c];});}
// Le haut (bandeau, etat en direct) arrive avec l'historique : on le place au-dessus des filtres.
window.PpeAfter=function(){var src=el('ppe-top-src'),dst=el('ppe-top');if(src&&dst){dst.innerHTML='';while(src.firstChild){dst.appendChild(src.firstChild);}src.parentNode.removeChild(src);}
 var D=window.PpeData||{peers:[],counts:{}};
 var s=el('ppe-peer');if(s){var h='<option value=\"\">'+esc(T.all)+'</option>',found=false;for(var i=0;i<D.peers.length;i++){var o=D.peers[i];if(o.v===PpeS.peer){found=true;}h+='<option value=\"'+esc(o.v)+'\"'+(o.v===PpeS.peer?' selected':'')+'>'+esc(o.l)+'</option>';}
  if(PpeS.peer&&!found){h+='<option value=\"'+esc(PpeS.peer)+'\" selected>'+esc(PpeS.peer)+'</option>';}s.innerHTML=h;}
 var c=document.querySelectorAll('.ppe-sbtn em');for(var j=0;j<c.length;j++){var k=c[j].getAttribute('data-c');var n=D.counts&&D.counts[k]!==undefined?D.counts[k]:'';c[j].textContent=n===''?'':String(n);}
 var ex=el('ppe-ex-addr');if(ex){ex.innerHTML='';for(var a=0;a<D.peers.length;a++){if(D.peers[a].a){var bt=document.createElement('button');bt.type='button';bt.className='ppe-ex mono';bt.textContent=D.peers[a].a;bt.onclick=(function(v){return function(){PpeSearch(v);};})(D.peers[a].a);ex.appendChild(bt);break;}}}
 var cards=document.querySelectorAll('.ppe-sc');for(var x=0;x<cards.length;x++){cards[x].onclick=function(){var v=this.getAttribute('data-peer');PpePeer(PpeS.peer===v?'':v);};}
 var tb=el('ppe-rows');if(!tb){return;}pager(tb,50);
 tb.addEventListener('click',function(e){var cp=e.target.closest?e.target.closest('.ppe-copy'):null;if(cp){copy(cp);return;}if(e.target.closest&&e.target.closest('.ppe-raw')){return;}var tr=e.target.closest?e.target.closest('tr.ppe-row'):null;if(tr){toggle(tr);}});
 tb.addEventListener('keydown',function(e){if(e.keyCode!=13&&e.keyCode!=32){return;}var tr=e.target;if(tr&&tr.className.indexOf('ppe-row')>=0){e.preventDefault();toggle(tr);}});};
function toggle(tr){var id=tr.getAttribute('aria-controls'),d=el(id);
 if(!d){d=document.createElement('div');d.className='ppe-raw';d.id=id;d.hidden=true;var l=document.createElement('span');l.className='ppe-rawl';l.textContent=T.raw;var pre=document.createElement('pre');pre.className='mono';pre.textContent=tr.getAttribute('data-raw')||'';
  var b=document.createElement('button');b.type='button';b.className='ppe-copy';var bs=document.createElement('span');bs.textContent=T.copy;b.appendChild(bs);d.appendChild(l);d.appendChild(pre);d.appendChild(b);var c=tr.querySelector('.ppe-what');if(c){c.appendChild(d);}}var o=tr.getAttribute('aria-expanded')!=='true';tr.setAttribute('aria-expanded',o?'true':'false');if(d){d.hidden=!o;}}
function copy(b){var pre=b.parentNode.querySelector('pre');if(!pre){return;}var txt=pre.textContent,lab=b.querySelector('span'),old=lab?lab.textContent:'';
 var done=function(){if(lab){lab.textContent=T.copied;setTimeout(function(){lab.textContent=old;},1500);}};
 function fb(){var t=document.createElement('textarea');t.value=txt;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();try{document.execCommand('copy');done();}catch(e){}document.body.removeChild(t);}
 if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(txt).then(done,fb);}else{fb();}}
function pager(tb,size){var rows=[].slice.call(tb.querySelectorAll('tr.ppe-row')),n=rows.length,pages=Math.max(1,Math.ceil(n/size)),box=el('ppe-pager'),sh=el('ppe-showing');
 function draw(cur){for(var i=0;i<n;i++){rows[i].style.display=(i>=(cur-1)*size&&i<cur*size)?'':'none';}
  if(sh){var a=(cur-1)*size+1,b=Math.min(n,cur*size);sh.textContent=T.showing.replace('%s',a).replace('%s',b).replace('%s',n);}
  if(!box){return;}box.innerHTML='';if(pages<2){return;}
  function btn(l,p,dis,on){var x=document.createElement('button');x.type='button';x.textContent=l;if(dis){x.disabled=true;}if(on){x.className='on';x.setAttribute('aria-current','page');}x.onclick=function(){draw(p);};box.appendChild(x);}
  btn(T.prev,cur-1,cur==1,false);var end=Math.min(pages,cur+2),start=Math.max(1,end-4);end=Math.min(pages,start+4);for(var p=start;p<=end;p++){btn(String(p),p,false,p==cur);}btn(T.next,cur+1,cur==pages,false);}
 draw(1);}
var q=el('ppe-q');if(q&&!q.__ppe){q.__ppe=1;q.addEventListener('input',function(){clearTimeout(window.PpeDeb);window.PpeDeb=setTimeout(PpeReload,600);});
 q.addEventListener('keydown',function(e){if(e.keyCode==13){e.preventDefault();clearTimeout(window.PpeDeb);PpeReload();}});}
PpeReload();
})();</script>";
}

// ---------------------------------------------------------------- pages

function build_page(){
    $tpl=new template_admin();
    $p=ppe_params();
    $html="<div class='ppe'><div id='ppe-top'></div>".ppe_filters($p)."<div id='ppe-host'></div></div>";
    $TINY=array("TITLE"=>"{parent_proxies} {events}","ICO"=>ico_eye,"EXPL"=>"{ppe_tiny}","URL"=>"parents-events","BUTTONS"=>"");
    echo ppe_style().$tpl->_ENGINE_parse_body($html).ppe_js($p)
        ."<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
}

function ppe_host(){
    $tpl=new template_admin();
    $p=ppe_params();
    $m=ppe_model($p);
    $top="<div id='ppe-top-src'>".ppe_banner($m).ppe_status_strip($m)."</div>";
    $body=$m["error"]!==""?ppe_error($m["error"]):ppe_history($m);
    echo $tpl->_ENGINE_parse_body($top.$body).ppe_host_data($m)."<script>NoSpinner();if(window.PpeAfter){PpeAfter();}</script>";
}
