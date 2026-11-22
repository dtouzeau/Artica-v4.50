<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Tous les identifiants arrivent en entiers ; md5 n'etait recopie que dans du JavaScript.
foreach(array("service","table","id-js","id-popup","delete","serviceid") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=intval($_GET[$xk]);}}
$users=new usersMenus();
if(!$users->AsWebMaster && !$users->AsSystemWebMaster){exit();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["id-js"])){id_js();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["ID"])){id_save();exit;}
if(isset($_POST["delete"])){delete();exit;}

table_start();

// Regles d'acces client d'un site (table ngx_stream_access_module).
// Le demon (reverse-proxy, ConfiguratorMaps.StreamAccess) les emet en bloc nginx
// « geo $remote_addr » : l'adresse la PLUS PRECISE l'emporte, l'ordre ne compte pas,
// « * » devient « default », et sans aucune regle Deny rien n'est emis.
// Maquette Stitch 2026-11-05.

function nsa_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemWebMaster;
}
function nsa_deny():bool{
    echo nsa_js_str("ERROR_NO_PRIVS2");
    return false;
}
function nsa_h($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),$s);
}
function nsa_t($k,$vars=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$k."}");
    return str_replace(array_keys($vars),array_values($vars),$s);
}
// Token pour le JavaScript ou un message texte : traduit, sans [br], entites decodees.
function nsa_js_str($k,$vars=array()):string{
    return trim(html_entity_decode(str_replace(array("<br>","<br/>","<br />","[br]"),"",nsa_t($k,$vars)),ENT_QUOTES,"UTF-8"));
}
function nsa_container($serviceid):string{
    return "ngx_stream_access_module-".intval($serviceid);
}
function nsa_refresh_js($serviceid):string{
    $page=CurrentPageName();
    $serviceid=intval($serviceid);
    return "LoadAjax('".nsa_container($serviceid)."','$page?table=$serviceid');";
}

function table_start():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["service"]);
    echo "<div id='".nsa_container($ID)."' style='margin-top: 10px'></div>
    <script>LoadAjax('".nsa_container($ID)."','$page?table=$ID')</script>";
    return true;
}

function nsa_rows($serviceid):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_stream_access_module/serviceid/".intval($serviceid)),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["rows"]) || !is_array($json["rows"])){return array();}
    return $json["rows"];
}
function nsa_row($ID):array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_stream_access_module/ID/".intval($ID)),true);
    if(!is_array($json) || empty($json["Status"]) || empty($json["rows"])){return array();}
    return $json["rows"][0];
}

// Nature d'une adresse telle que nginx « geo » la lira.
// kind : all | host | net | range | invalid. Une plage a-b est refusee par geo (« invalid network ») :
// verifie avec nginx -t, elle empeche toute la configuration du site.
function nsa_classify($item):array{
    $it=trim((string)$item);
    $r=array("item"=>$it,"kind"=>"invalid","family"=>4,"prefix"=>-1,"count"=>0);
    if($it=="*"){$r["kind"]="all";return $r;}
    if(strpos($it,"-")>0){
        $p=explode("-",$it,2);
        if(filter_var(trim($p[0]),FILTER_VALIDATE_IP) && filter_var(trim($p[1]),FILTER_VALIDATE_IP)){
            $r["kind"]="range";
            $r["family"]=strpos($it,":")!==false?6:4;
        }
        return $r;
    }
    $p=explode("/",$it,2);
    $ip=$p[0];
    $fam=0;
    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){$fam=4;}
    elseif(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)){$fam=6;}
    if($fam==0){return $r;}
    $max=$fam==4?32:128;
    $prefix=$max;
    if(isset($p[1])){
        if(!ctype_digit($p[1])){return $r;}
        $prefix=intval($p[1]);
        if($prefix<0 || $prefix>$max){return $r;}
    }
    $r["family"]=$fam;
    $r["prefix"]=$prefix;
    $r["kind"]=$prefix==$max?"host":"net";
    if($fam==4){$r["count"]=pow(2,32-$prefix);}
    return $r;
}
function nsa_scope_label($c):string{
    switch($c["kind"]){
        case "all":   return "{nsa_all_addresses}";
        case "host":  return $c["family"]==4?"{nsa_scope_host4}":"{nsa_scope_host6}";
        case "net":   return nsa_t($c["family"]==4?"nsa_scope_net4":"nsa_scope_net6",array("%s"=>$c["prefix"]));
        case "range": return "{nsa_scope_range}";
    }
    return "{nsa_scope_invalid}";
}

// Modele de l'onglet, calcule une fois pour tous les widgets.
function nsa_model($serviceid):array{
    $site=fetch_nginx_service($serviceid);
    $m=array("id"=>intval($serviceid),"type"=>isset($site["type"])?intval($site["type"]):0,
        "can_write"=>nsa_can_write(),"rules"=>array(),"allow"=>0,"deny"=>0,"star"=>"",
        "kinds"=>array("net"=>0,"host"=>0,"all"=>0,"range"=>0,"invalid"=>0));
    $byItem=array();
    foreach(nsa_rows($serviceid) as $l){
        $c=nsa_classify(isset($l["item"])?$l["item"]:"");
        $c["id"]=intval($l["ID"]);
        $c["allow"]=intval($l["allow"])==1;
        $c["dup"]="";
        $m["rules"][]=$c;
        if($c["allow"]){$m["allow"]++;}else{$m["deny"]++;}
        $m["kinds"][$c["kind"]]++;
        $byItem[strtolower($c["item"])][]=count($m["rules"])-1;
    }
    // Doublons : nginx garde la derniere valeur, et le demon ecrit les Deny apres les Allow.
    foreach($byItem as $idx){
        if(count($idx)<2){continue;}
        $acts=array();
        foreach($idx as $i){$acts[$m["rules"][$i]["allow"]?1:0]=true;}
        foreach($idx as $i){$m["rules"][$i]["dup"]=count($acts)>1?"{nsa_dup_deny_wins}":"{nsa_dup}";}
    }
    foreach($m["rules"] as $c){
        if($c["kind"]!="all"){continue;}
        if(!$c["allow"]){$m["star"]="deny";break;}
        $m["star"]="allow";
    }
    // Du plus precis au plus large, le « * » toujours en dernier.
    usort($m["rules"],function($a,$b){
        $ka=$a["kind"]=="all"?1:0; $kb=$b["kind"]=="all"?1:0;
        if($ka!=$kb){return $ka-$kb;}
        $sa=$a["prefix"]<0?-1:($a["family"]==4?$a["prefix"]+96:$a["prefix"]);
        $sb=$b["prefix"]<0?-1:($b["family"]==4?$b["prefix"]+96:$b["prefix"]);
        if($sa!=$sb){return $sb-$sa;}
        return strcmp($a["item"],$b["item"]);
    });
    return $m;
}

function table():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $m=nsa_model(intval($_GET["table"]));
    $h=array();
    $h[]="<div class='nsa'>";
    $h[]=nsa_toolbar($m);
    $h[]=nsa_banners($m);
    $h[]=nsa_policy($m);
    $h[]="<p class='nsa-note'><i class='fa-solid fa-circle-info' aria-hidden='true'></i><span>{nsa_note}</span></p>";
    $h[]=nsa_table($m);
    $h[]="</div>";
    echo nsa_style().$tpl->_ENGINE_parse_body(implode("",$h))."<script>NoSpinner();</script>";
    return true;
}

function nsa_toolbar($m):string{
    $page=CurrentPageName();
    if($m["can_write"]){
        $btn="<button type='button' class='nsa-btn nsa-primary' OnClick=\"Loadjs('$page?id-js=0&serviceid={$m["id"]}');\"><i class='fa-solid fa-plus' aria-hidden='true'></i><span>{nsa_new_rule}</span></button>";
    }else{
        $btn="<button type='button' class='nsa-btn nsa-primary' aria-disabled='true' disabled><i class='fa-solid fa-lock' aria-hidden='true'></i><span>{nsa_new_rule}</span></button>";
    }
    return "<div class='nsa-toolbar'>$btn</div>";
}
function nsa_banner($cls,$ico,$txt):string{
    return "<div class='nsa-banner nsa-$cls' role='status'><i class='fa-solid $ico' aria-hidden='true'></i><span>$txt</span></div>";
}
function nsa_banners($m):string{
    $h=array();
    if($m["type"]==5 && $m["deny"]>0){$h[]=nsa_banner("err","fa-circle-xmark","{nsa_tcp_banner}");}
    elseif($m["type"]==5){$h[]=nsa_banner("warn","fa-triangle-exclamation","{nsa_tcp_banner}");}
    if($m["kinds"]["range"]>0){$h[]=nsa_banner("err","fa-circle-xmark","{nsa_range_banner}");}
    if(!$m["can_write"]){$h[]=nsa_banner("info","fa-lock","{nsa_readonly}");}
    return implode("",$h);
}

// Carte de politique : ce que vit un client non liste, les regles, la reponse au refus.
function nsa_policy($m):string{
    $n=count($m["rules"]);
    if($n==0){
        return "<section class='nsa-card nsa-state nsa-state-empty' role='status'><i class='fa-solid fa-circle-info' aria-hidden='true'></i>"
            ."<div><strong>{nsa_empty_title}</strong><p>{nsa_empty}</p></div></section>";
    }
    if($m["deny"]==0){
        return "<section class='nsa-card nsa-state nsa-state-warn' role='status'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>"
            ."<div><strong>{nsa_inactive_title}</strong><p>{nsa_inactive}</p></div></section>";
    }
    if($m["star"]=="deny"){
        $a="<div class='nsa-big nsa-c-deny'><i class='fa-solid fa-ban' aria-hidden='true'></i>{nsa_denied}</div><p>{nsa_by_star}</p>";
    }elseif($m["star"]=="allow"){
        $a="<div class='nsa-big nsa-c-allow'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{nsa_allowed}</div><p>{nsa_by_star}</p>";
    }else{
        $a="<div class='nsa-big nsa-c-allow'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{nsa_allowed}</div><p>{nsa_no_star}</p>";
    }
    $parts=array();
    foreach(array("net"=>"nsa_k_net","host"=>"nsa_k_host","range"=>"nsa_k_range","all"=>"nsa_k_all") as $k=>$tok){
        if($m["kinds"][$k]>0){$parts[]="{".$tok."}: ".$m["kinds"][$k];}
    }
    $b="<div class='nsa-counts'><span class='nsa-c-allow'><i class='fa-solid fa-check' aria-hidden='true'></i>".nsa_t("nsa_n_allowed",array("%s"=>$m["allow"]))."</span>"
        ."<span class='nsa-dot' aria-hidden='true'>&middot;</span>"
        ."<span class='nsa-c-deny'><i class='fa-solid fa-ban' aria-hidden='true'></i>".nsa_t("nsa_n_denied",array("%s"=>$m["deny"]))."</span></div>"
        ."<p>".implode(" &middot; ",$parts)."</p>";
    $c="<div class='nsa-code'>HTTP 403 Forbidden</div>";
    return "<section class='nsa-card nsa-policy'>"
        ."<div class='nsa-blk'><span class='nsa-caps'>{nsa_unmatched}</span>$a</div>"
        ."<div class='nsa-blk'><span class='nsa-caps'>{nsa_rules}</span>$b</div>"
        ."<div class='nsa-blk'><span class='nsa-caps'>{nsa_denied_receive}</span>$c</div></section>";
}

function nsa_action_pill($allow):string{
    if($allow){return "<span class='nsa-pill nsa-p-allow'><i class='fa-solid fa-check' aria-hidden='true'></i>{nsa_allow}</span>";}
    return "<span class='nsa-pill nsa-p-deny'><i class='fa-solid fa-ban' aria-hidden='true'></i>{nsa_deny}</span>";
}
function nsa_row_html($m,$c):string{
    $page=CurrentPageName();
    $label=$c["kind"]=="all"?"<i class='fa-solid fa-globe' aria-hidden='true'></i>{nsa_all_addresses} (*)":nsa_h($c["item"]);
    $addr="<button type='button' class='nsa-addr' OnClick=\"Loadjs('$page?id-js={$c["id"]}&serviceid={$m["id"]}');\">$label</button>";
    $notes=array();
    if($c["kind"]=="range"){$notes[]="<span class='nsa-rownote nsa-c-deny'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i>{nsa_range_row}</span>";}
    if($c["kind"]=="invalid"){$notes[]="<span class='nsa-rownote nsa-c-deny'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i>{nsa_range_row}</span>";}
    if($c["dup"]<>""){$notes[]="<span class='nsa-rownote nsa-c-warn'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>{$c["dup"]}</span>";}
    $aria=nsa_h(nsa_js_str("nsa_delete_rule")." ".($c["kind"]=="all"?"*":$c["item"]));
    if($m["can_write"]){
        $del="<button type='button' class='nsa-del' aria-label=\"$aria\" title=\"$aria\" OnClick=\"Loadjs('$page?delete={$c["id"]}&serviceid={$m["id"]}');\"><i class='fa-solid fa-trash-can' aria-hidden='true'></i></button>";
    }else{
        $del="<button type='button' class='nsa-del' aria-disabled='true' disabled aria-label=\"$aria\"><i class='fa-solid fa-lock' aria-hidden='true'></i></button>";
    }
    return "<tr><td class='nsa-c-act'>".nsa_action_pill($c["allow"])."</td>"
        ."<td class='nsa-c-addr'>$addr".(count($notes)>0?"<div class='nsa-notes'>".implode("",$notes)."</div>":"")."</td>"
        ."<td class='nsa-c-scope'><span class='nsa-pill nsa-p-neutral'>".nsa_scope_label($c)."</span></td>"
        ."<td class='nsa-c-del'>$del</td></tr>";
}
function nsa_table($m):string{
    $page=CurrentPageName();
    $n=count($m["rules"]);
    if($n==0){
        $btn="";
        if($m["can_write"]){
            $btn="<button type='button' class='nsa-btn nsa-primary' OnClick=\"Loadjs('$page?id-js=0&serviceid={$m["id"]}');\"><i class='fa-solid fa-plus' aria-hidden='true'></i><span>{nsa_new_rule}</span></button>";
        }
        return "<section class='nsa-card nsa-empty'><i class='fa-solid fa-shield-halved' aria-hidden='true'></i><p>{nsa_empty_table}</p>$btn</section>";
    }
    $h=array();
    $h[]="<section class='nsa-card nsa-tblcard'><table class='nsa-tbl'><thead><tr>";
    $h[]="<th class='nsa-c-act'>{nsa_col_action}</th><th class='nsa-c-addr'>{nsa_col_address}</th><th class='nsa-c-scope'>{nsa_col_scope}</th><th class='nsa-c-del'><span class='nsa-sr'>{nsa_delete_rule}</span></th>";
    $h[]="</tr></thead><tbody>";
    $sep=false;
    foreach($m["rules"] as $c){
        if($c["kind"]=="all" && !$sep){
            $sep=true;
            $h[]="<tr class='nsa-sep'><td colspan='4'>{nsa_default_section}</td></tr>";
        }
        $h[]=nsa_row_html($m,$c);
    }
    $h[]="</tbody></table>";
    $h[]="<div class='nsa-foot'>".($n==1?"{nsa_one_rule}":nsa_t("nsa_n_rules",array("%s"=>$n)))."</div>";
    $h[]="</section>";
    return implode("",$h);
}

function delete_js():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["delete"]);
    $l=nsa_row($ID);
    $item=isset($l["item"])?trim($l["item"]):"";
    $serviceid=isset($l["serviceid"])?intval($l["serviceid"]):intval($_GET["serviceid"]);
    if($item=="*"){$item=nsa_js_str("nsa_all_addresses")." (*)";}
    return $tpl->js_confirm_delete(nsa_h($item), "delete", "$ID",nsa_refresh_js($serviceid)."Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');");
}
function delete():bool{
    if(!nsa_can_write()){return nsa_deny();}
    $ID=intval($_POST["delete"]);
    $l=nsa_row($ID);
    $serviceid=isset($l["serviceid"])?intval($l["serviceid"]):0;
    $item=isset($l["item"])?$l["item"]:"";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"ngx_stream_access_module","where"=>["ID"=>$ID]]);
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
    $servicename=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Remove Access item $item from $servicename");
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}

function id_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["id-js"]);
    $serviceid=intval($_GET["serviceid"]);
    $title=$ID>0?"{nsa_edit_rule}":"{nsa_new_rule}";
    return $tpl->js_dialog3($title, "$page?id-popup=$ID&serviceid=$serviceid");
}

// Modale d'ajout / d'edition : adresse + indice de portee en direct + Allow / Deny.
function id_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["id-popup"]);
    $serviceid=intval($_GET["serviceid"]);
    $item="";$allow=true;
    if($ID>0){
        $l=nsa_row($ID);
        $item=isset($l["item"])?trim($l["item"]):"";
        $allow=isset($l["allow"]) && intval($l["allow"])==1;
        if(isset($l["serviceid"])){$serviceid=intval($l["serviceid"]);}
    }
    $w=nsa_can_write();
    $uid="nsam".substr(md5(microtime().rand(0,65535)),0,10);
    $dis=$w?"":" disabled";
    $h=array();
    $h[]="<div class='nsa nsam' id='$uid'>";
    if(!$w){$h[]=nsa_banner("info","fa-lock","{nsa_readonly}");}
    $h[]="<section class='nsam-sec'><div class='nsam-lblrow'><label class='nsam-lbl' for='$uid-ip'>{nsa_ip}</label>";
    if($w){$h[]="<button type='button' class='nsam-link' data-nsa='star'><i class='fa-solid fa-globe' aria-hidden='true'></i>{nsa_all_btn}</button>";}
    $h[]="</div><input type='text' id='$uid-ip' class='nsam-ip' data-nsa='ip' autocomplete='off' spellcheck='false' placeholder='192.168.1.10, 10.0.0.0/8, 2001:db8::/32' value=\"".nsa_h($item)."\"$dis aria-describedby='$uid-help $uid-hint'>";
    $h[]="<p class='nsam-help' id='$uid-help'>{nsa_ip_help}</p>";
    $h[]="<p class='nsam-hint' id='$uid-hint' data-nsa='hint' aria-live='polite'></p></section>";
    $h[]="<section class='nsam-sec'><span class='nsam-lbl' id='$uid-al'>{nsa_action}</span><div class='nsam-seg' role='radiogroup' aria-labelledby='$uid-al'>";
    foreach(array(1=>array("fa-check","nsa_allow","nsa_allow_desc","allow"),0=>array("fa-ban","nsa_deny","nsa_deny_desc","deny")) as $v=>$o){
        $chk=(($v==1)==$allow)?"true":"false";
        $h[]="<button type='button' role='radio' aria-checked='$chk' class='nsam-opt nsam-opt-{$o[3]}' data-val='$v'$dis><i class='fa-solid {$o[0]}' aria-hidden='true'></i><span><strong>{".$o[1]."}</strong><small>{".$o[2]."}</small></span></button>";
    }
    $h[]="</div></section>";
    $h[]="<div class='nsam-err' role='alert' data-nsa='err'></div>";
    $go=$ID>0?"{nsa_apply}":"{nsa_add}";
    $h[]="<div class='nsam-foot'><button type='button' class='nsa-btn nsa-ghost' data-nsa='cancel'>{nsa_cancel}</button>";
    $h[]=$w?"<button type='button' class='nsa-btn nsa-primary' data-nsa='go'>$go</button>":"<button type='button' class='nsa-btn nsa-primary' aria-disabled='true' disabled><i class='fa-solid fa-lock' aria-hidden='true'></i>$go</button>";
    $h[]="</div></div>";
    echo nsa_style().$tpl->_ENGINE_parse_body(implode("",$h)).nsa_modal_js($uid,$ID,$serviceid,$w);
    return true;
}
function nsa_modal_js($uid,$ID,$serviceid,$w):string{
    $page=CurrentPageName();
    $C=array("uid"=>$uid,"page"=>$page,"id"=>intval($ID),"serviceid"=>intval($serviceid),"w"=>(bool)$w);
    $L=array(
        "all"=>nsa_js_str("nsa_all_addresses"),"host4"=>nsa_js_str("nsa_scope_host4"),"host6"=>nsa_js_str("nsa_scope_host6"),
        "net4"=>nsa_js_str("nsa_scope_net4"),"net6"=>nsa_js_str("nsa_scope_net6"),"count"=>nsa_js_str("nsa_hint_count"),
        "invalid"=>nsa_js_str("nsa_invalid"),"range"=>nsa_js_str("nsa_range_refused"),
        "ok"=>nsa_js_str("success_form_submited"),
    );
    $flags=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $after="dialogInstance3.close();".nsa_refresh_js($serviceid)."Loadjs('fw.nginx.hup.php?hup=yes&serviceid=".intval($serviceid)."');";
    $j=array();
    $j[]="<script>(function(){";
    $j[]="var C=".json_encode($C,$flags).",L=".json_encode($L,$flags).";";
    $j[]="var R=document.getElementById(C.uid);if(!R){return;}function q(s){return R.querySelector(s);}";
    $j[]="var ip=q('[data-nsa=ip]'),hint=q('[data-nsa=hint]'),err=q('[data-nsa=err]'),busy=false;";
    // Indice de portee : meme classement que nsa_classify(), le serveur reste seul juge.
    $j[]="function v4(a){var p=a.split('.');if(p.length!==4){return false;}for(var i=0;i<4;i++){if(!/^\\d{1,3}$/.test(p[i])||parseInt(p[i],10)>255){return false;}}return true;}";
    $j[]="function v6(a){return a.indexOf(':')>=0&&/^[0-9a-f:.]+$/i.test(a)&&a.split('::').length<=2;}";
    $j[]="function classify(s){s=s.trim();if(s===''){return {k:'empty'};}if(s==='*'){return {k:'all'};}if(s.indexOf('-')>0){return {k:'range'};}";
    $j[]=" var p=s.split('/'),a=p[0],f=v4(a)?4:(v6(a)?6:0);if(!f||p.length>2){return {k:'invalid'};}var mx=f===4?32:128,n=mx;";
    $j[]=" if(p.length===2){if(!/^\\d{1,3}$/.test(p[1])){return {k:'invalid'};}n=parseInt(p[1],10);if(n>mx){return {k:'invalid'};}}";
    $j[]=" return {k:n===mx?'host':'net',f:f,n:n};}";
    $j[]="function render(){var c=classify(ip.value),t='',bad=false;";
    $j[]=" if(c.k==='all'){t=L.all;}else if(c.k==='range'){t=L.range;bad=true;}else if(c.k==='invalid'){t=L.invalid;bad=true;}";
    $j[]=" else if(c.k==='host'){t=c.f===4?L.host4:L.host6;}";
    $j[]=" else if(c.k==='net'){t=(c.f===4?L.net4:L.net6).replace('%s',c.n);if(c.f===4){t+=' — '+L.count.replace('%s',Math.pow(2,32-c.n).toLocaleString('en-US'));}}";
    $j[]=" hint.textContent=t;hint.className='nsam-hint'+(bad?' nsam-bad':'');ip.setAttribute('aria-invalid',bad?'true':'false');return !bad&&c.k!=='empty';}";
    $j[]="ip.addEventListener('input',function(){err.textContent='';render();});";
    $j[]="var star=q('[data-nsa=star]');if(star){star.addEventListener('click',function(){ip.value='*';render();ip.focus();});}";
    $j[]="var opts=R.querySelectorAll('.nsam-opt');for(var i=0;i<opts.length;i++){opts[i].addEventListener('click',function(){if(!C.w){return;}for(var k=0;k<opts.length;k++){opts[k].setAttribute('aria-checked',opts[k]===this?'true':'false');}});}";
    $j[]="q('[data-nsa=cancel]').addEventListener('click',function(){if(typeof dialogInstance3!=='undefined'){dialogInstance3.close();}});";
    $j[]="var go=q('[data-nsa=go]');if(go){go.addEventListener('click',function(){if(busy){return;}if(!render()){ip.focus();return;}busy=true;go.disabled=true;";
    $j[]=" var a=R.querySelector('.nsam-opt[aria-checked=true]'),X=new XHRConnection();";
    $j[]=" X.appendData('ID',C.id);X.appendData('serviceid',C.serviceid);X.appendData('ipaddr',encodeURIComponent(ip.value.trim()));X.appendData('allow',a?a.getAttribute('data-val'):'0');";
    $j[]=" X.sendAndLoad(C.page,'POST',function(o){busy=false;go.disabled=false;var r=(o&&o.responseText?o.responseText:'');";
    $j[]="  r=((window.DOMParser?new DOMParser().parseFromString(r,'text/html').body.textContent:r.replace(/<[^>]*>/g,' '))||'').trim();";
    $j[]="  if(r.length>3){err.textContent=r;return;}";
    $j[]="  if(typeof toastr!=='undefined'){toastr.options.timeOut=2000;toastr.success(L.ok);}";
    $j[]="  ".$after;
    $j[]=" });});}";
    $j[]="render();";
    $j[]="})();</script>";
    return implode("\n",$j);
}

function id_save():bool{
    if(!nsa_can_write()){return nsa_deny();}
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST_XSS();
    $ID=intval($_POST["ID"]);
    $serviceid=intval($_POST['serviceid']);
    if($serviceid<=0){echo nsa_js_str("nsa_svc_missing");return false;}
    $item=trim((string)$_POST["ipaddr"]);
    $c=nsa_classify($item);
    if($c["kind"]=="range"){echo nsa_js_str("nsa_range_refused");return false;}
    if($c["kind"]=="invalid"){echo nsa_js_str("nsa_invalid").": ".$item;return false;}
    if(strpos($item,":")!==false){$item=strtolower($item);}
    // Une meme adresse deux fois : nginx garde la derniere valeur, la regle visible mentirait.
    foreach(nsa_rows($serviceid) as $l){
        if(intval($l["ID"])==$ID){continue;}
        if(strtolower(trim((string)$l["item"]))==strtolower($item)){echo nsa_js_str("nsa_dup_refused");return false;}
    }
    $allow=intval($_POST["allow"])==1?1:0;
    if($ID==0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"insert","table"=>"ngx_stream_access_module","values"=>["serviceid"=>$serviceid,"item"=>$item,"allow"=>$allow]]);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"update","table"=>"ngx_stream_access_module","set"=>["item"=>$item,"allow"=>$allow],"where"=>["ID"=>$ID]]);
    }
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    $verb=$ID==0?"Add":"Set";
    return admin_tracks("$verb Access item $item (".($allow==1?"allow":"deny").") for site #$serviceid");
}

function nsa_style():string{
    $f=array();
    $f[]="<style>";
    $f[]=".nsa{display:flex;flex-direction:column;gap:16px;color:#0f172a;font-size:14px}";
    $f[]=".nsa *{box-sizing:border-box}";
    $f[]=".nsa-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}";
    $f[]=".nsa-toolbar{display:flex;align-items:center;gap:12px}";
    $f[]=".nsa-btn{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 18px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;border:1px solid transparent}";
    $f[]=".nsa-primary{background:#0369a1;border-color:#0369a1;color:#fff}.nsa-primary:hover{background:#075985}";
    $f[]=".nsa-ghost{background:#fff;border-color:#cbd5e1;color:#334155}.nsa-ghost:hover{background:#f1f5f9}";
    $f[]=".nsa-btn[disabled]{opacity:.6;cursor:not-allowed}";
    $f[]=".nsa-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $f[]=".nsa-caps{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b}";
    $f[]=".nsa-policy{display:grid;grid-template-columns:repeat(3,1fr)}";
    $f[]=".nsa-blk{display:flex;flex-direction:column;gap:8px;padding:18px 20px;border-left:1px solid #f1f5f9;min-width:0}";
    $f[]=".nsa-blk:first-child{border-left:0}";
    $f[]=".nsa-blk p{margin:0;font-size:13px;color:#64748b}";
    $f[]=".nsa-big{display:flex;align-items:center;gap:10px;font-size:20px;font-weight:700}";
    $f[]=".nsa-counts{display:flex;align-items:center;flex-wrap:wrap;gap:8px 10px;font-size:16px;font-weight:700}";
    $f[]=".nsa-counts span{display:inline-flex;align-items:center;gap:6px}";
    $f[]=".nsa-dot{color:#cbd5e1}";
    $f[]=".nsa-code{align-self:flex-start;padding:4px 10px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;font-family:monospace;font-size:15px;font-weight:600;white-space:nowrap}";
    $f[]=".nsa-c-allow{color:#047857}.nsa-c-deny{color:#b91c1c}.nsa-c-warn{color:#b45309}";
    $f[]=".nsa-state{display:flex;align-items:flex-start;gap:12px;padding:16px 20px}";
    $f[]=".nsa-state>i,.nsa-state>svg{margin-top:3px;font-size:18px;flex-shrink:0}";
    $f[]=".nsa-state p{margin:4px 0 0;font-size:13px}";
    $f[]=".nsa-state-warn{background:#fffbeb;border-color:#fde68a;border-left:4px solid #d97706;color:#78350f}";
    $f[]=".nsa-state-empty{background:#f8fafc;color:#475569}";
    $f[]=".nsa-banner{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:8px;border:1px solid;font-size:13px;line-height:1.5}";
    $f[]=".nsa-banner>i,.nsa-banner>svg{margin-top:3px;flex-shrink:0}";
    $f[]=".nsa-err{background:#fef2f2;border-color:#fecaca;color:#991b1b}";
    $f[]=".nsa-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}";
    $f[]=".nsa-info{background:#f8fafc;border-color:#e2e8f0;color:#475569}";
    $f[]=".nsa-note{display:flex;align-items:flex-start;gap:8px;margin:0;font-size:13px;color:#475569}";
    $f[]=".nsa-note>i,.nsa-note>svg{color:#0369a1;margin-top:3px}";
    $f[]=".nsa-tblcard{overflow:hidden}";
    $f[]=".nsa-tbl{width:100%;border-collapse:collapse;table-layout:fixed}";
    $f[]=".nsa-tbl th{height:40px;padding:0 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left}";
    $f[]=".nsa-tbl td{padding:12px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle}";
    $f[]=".nsa-tbl tbody tr:not(.nsa-sep):hover{background:#f8fafc}";
    $f[]=".nsa-tbl .nsa-c-act{width:130px}.nsa-tbl .nsa-c-scope{width:200px}.nsa-tbl .nsa-c-del{width:64px;text-align:right}";
    $f[]=".nsa-sep td{padding:8px 16px;background:#f8fafc;border-top:2px solid #e2e8f0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b}";
    $f[]=".nsa-addr{display:inline-flex;align-items:center;gap:8px;max-width:100%;padding:0;border:0;background:none;font-family:monospace;font-size:15px;font-weight:600;color:#0f172a;text-align:left;cursor:pointer;word-break:break-all}";
    $f[]=".nsa-addr:hover{text-decoration:underline}";
    $f[]=".nsa-notes{display:flex;flex-direction:column;gap:2px;margin-top:4px}";
    $f[]=".nsa-rownote{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600}";
    $f[]=".nsa-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;border:1px solid transparent;font-size:13px;font-weight:600;line-height:1.35;white-space:nowrap}";
    $f[]=".nsa-p-allow{background:#ecfdf5;color:#047857;border-color:#a7f3d0}";
    $f[]=".nsa-p-deny{background:#fef2f2;color:#b91c1c;border-color:#fecaca}";
    $f[]=".nsa-p-neutral{background:#f1f5f9;color:#475569;border-color:#e2e8f0;font-weight:500}";
    $f[]=".nsa-del{width:34px;height:34px;border:0;border-radius:6px;background:none;color:#94a3b8;cursor:pointer}";
    $f[]=".nsa-del:not([disabled]):hover,.nsa-del:not([disabled]):focus-visible{color:#b91c1c;background:#fef2f2}";
    $f[]=".nsa-del[disabled]{cursor:not-allowed}";
    $f[]=".nsa-foot{height:40px;display:flex;align-items:center;padding:0 16px;font-size:13px;color:#64748b}";
    $f[]=".nsa-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;min-height:160px;padding:24px;color:#475569}";
    $f[]=".nsa-empty>i,.nsa-empty>svg{font-size:40px;color:#cbd5e1}.nsa-empty p{margin:0}";
    $f[]=".nsam{gap:20px;padding:4px}";
    $f[]=".nsam-sec{display:flex;flex-direction:column;gap:8px}";
    $f[]=".nsam-lblrow{display:flex;align-items:center;justify-content:space-between;gap:12px}";
    $f[]=".nsam-lbl{margin:0;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#475569}";
    $f[]=".nsam-link{display:inline-flex;align-items:center;gap:6px;padding:2px 4px;border:0;background:none;color:#0369a1;font-size:13px;font-weight:600;cursor:pointer}";
    $f[]=".nsam-link:hover{text-decoration:underline}";
    $f[]=".nsam-ip{height:44px;padding:0 14px;border:1px solid #cbd5e1;border-radius:8px;font-family:monospace;font-size:16px;color:#0f172a}";
    $f[]=".nsam-ip[aria-invalid=true]{border-color:#b91c1c}";
    $f[]=".nsam-help{margin:0;font-size:13px;color:#64748b}";
    $f[]=".nsam-hint{margin:0;min-height:18px;font-size:13px;font-weight:600;color:#0369a1}";
    $f[]=".nsam-hint.nsam-bad{color:#b91c1c}";
    $f[]=".nsam-seg{display:grid;grid-template-columns:1fr 1fr;gap:12px}";
    $f[]=".nsam-opt{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;text-align:left;cursor:pointer}";
    $f[]=".nsam-opt>i,.nsam-opt>svg{font-size:18px;color:#64748b}";
    $f[]=".nsam-opt span{display:flex;flex-direction:column;gap:2px}";
    $f[]=".nsam-opt strong{font-size:15px;color:#0f172a}.nsam-opt small{font-size:13px;color:#64748b}";
    $f[]=".nsam-opt-allow[aria-checked=true]{border:2px solid #047857;background:#ecfdf5}.nsam-opt-allow[aria-checked=true]>i,.nsam-opt-allow[aria-checked=true]>svg{color:#047857}";
    $f[]=".nsam-opt-deny[aria-checked=true]{border:2px solid #b91c1c;background:#fef2f2}.nsam-opt-deny[aria-checked=true]>i,.nsam-opt-deny[aria-checked=true]>svg{color:#b91c1c}";
    $f[]=".nsam-opt[disabled]{cursor:not-allowed;opacity:.7}";
    $f[]=".nsam-err{font-size:13px;font-weight:600;color:#b91c1c}.nsam-err:empty{display:none}";
    $f[]=".nsam-foot{display:flex;justify-content:flex-end;gap:12px;padding-top:16px;border-top:1px solid #e2e8f0}";
    $f[]=".nsa button:focus-visible,.nsa input:focus-visible{outline:2px solid #0369a1;outline-offset:2px}";
    $f[]="@media (max-width:1199px){.nsa-policy{grid-template-columns:1fr}.nsa-blk{border-left:0;border-top:1px solid #f1f5f9}.nsa-blk:first-child{border-top:0}.nsa-tbl .nsa-c-scope{display:none}}";
    $f[]="@media (prefers-reduced-motion:reduce){.nsa *{transition:none!important}}";
    $f[]="</style>";
    return implode("",$f);
}
