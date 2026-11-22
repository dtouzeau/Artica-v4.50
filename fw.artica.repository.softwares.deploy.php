<?php
// Envoi d'un logiciel du depot Meta (/softwares/debian<N>/<APP>/<version>.tar.gz) vers des noeuds.
// Meme fenetre que l'envoi des paquets Artica (maquettes stitch_artica_update_push_modal et _modal2) :
// briques communes dans ressources/class.artica.push.ui.inc, applicabilite propre aux logiciels
// (un paquet est construit pour UNE version de Debian).
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once dirname(__FILE__) . '/ressources/class.netagent.label.inc';
include_once dirname(__FILE__) . '/ressources/class.artica.push.ui.inc';

if(isset($_GET["operation-details"])){operation_details();exit;}
if(isset($_GET["operation-details-popup"])){operation_details_popup();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["groups"])){groups();exit;}
if(isset($_POST["push-update"])){push();exit;}
if(isset($_POST["push-update-groups-post"])){push_groups();exit;}
if(isset($_GET["push-results"])){pshu_activity("/articarepos/push-software/status",5);exit;}

// Droit verifie cote serveur : envoi de logiciels du depot a des noeuds = meta_can_push()
// (infogerant, ou tenant vers SES noeuds, le daemon filtre ; spec 2026-11-13 5.10.5).
function swd_can_write():bool{
    return meta_can_push();
}

// Chemin relatif au depot. Le demon accepte un chemin ABSOLU et ne fait que verifier qu'il existe :
// la forme est donc verrouillee ici (ancree, sans "..", segments a caracteres fixes).
function swd_parse(string $rel):array{
    $rel=trim($rel);
    if(strpos($rel,"..")!==false){return array();}
    if(!preg_match('#^/softwares/debian([0-9]{1,2})/([A-Za-z0-9_]+)/([A-Za-z0-9_.+~-]+)\.tar\.gz$#',$rel,$re)){return array();}
    return array("rel"=>$rel,"deb"=>intval($re[1]),"code"=>$re[2],"ver"=>$re[3]);
}

function swd_get_rel(string $key):array{
    return swd_parse((string)($_GET[$key] ?? ""));
}

function swd_repo_path():string{
    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/status"));
    $repo=is_object($st)&&isset($st->repo_path)?(string)$st->repo_path:"";
    if($repo=="" || $repo[0]!="/"){$repo=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLocalRepoPath");}
    if(strlen($repo)<4){$repo="/home/artica/artica-repository";}
    return rtrim($repo,"/");
}

// Fiche du paquet : taille et md5 lus dans l'index du depot.
function swd_file(array $p):array{
    $f=$p+array("size"=>-1,"md5"=>"","indexed"=>null);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/files/software/".$p["deb"]));
    if(!is_object($json) || !isset($json->files) || !is_array($json->files)){return $f;}
    $f["indexed"]=false;
    foreach($json->files as $row){
        $lp=(string)($row->local_path ?? "");
        if($lp=="" || substr($lp,-strlen($p["rel"]))!==$p["rel"]){continue;}
        $f["indexed"]=true;
        if(isset($row->size)){$f["size"]=floatval($row->size);}
        if(isset($row->md5)){$f["md5"]=strtolower((string)$row->md5);}
        if(!empty($row->version)){$f["ver"]=(string)$row->version;}
        break;
    }
    return $f;
}

// applicable | other (autre Debian) | unknown (Debian non remontee) | blocked
function swd_state(array $p,int $deb,bool $blacklisted):string{
    if($blacklisted){return "blocked";}
    if($deb<=0){return "unknown";}
    return $deb==$p["deb"]?"applicable":"other";
}

function swd_labels():array{
    return array(
        "applicable"=>array("ak-ok","fa-circle-check","{pshu_st_applicable}"),
        "other"=>array("ak-warn","fa-triangle-exclamation","{swd_st_other}"),
        "unknown"=>array("ak-off","fa-circle-question","{swd_st_unknown}"),
    );
}

function swd_size($bytes):string{
    $b=floatval($bytes);
    if($b<1024){return intval($b)." B";}
    $u=array("KB","MB","GB","TB");$i=-1;
    while($b>=1024 && $i<3){$b=$b/1024;$i++;}
    return number_format($b,$b<10?1:0,".","")." ".$u[$i];
}

function js():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $p=swd_parse((string)($_GET["filename"] ?? ""));
    if(count($p)==0){return $tpl->js_error("{swd_invalid}");}
    return $tpl->js_dialog10("{push_update}", "$page?tabs=yes&filename=".urlencode($p["rel"]), 980);
}

function tabs():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $p=swd_get_rel("filename");
    if(count($p)==0){echo pshu_style().$tpl->_ENGINE_parse_body("<div class='ak'><div class='ak-banner ak-err'><i class='fas fa-circle-exclamation'></i><span class='ak-banner-m'>{swd_invalid}</span></div></div>");return false;}
    $enc=urlencode($p["rel"]);
    $tabs=array();
    $tabs["{pshu_tab_nodes}"]="$page?popup=$enc";
    $tabs["{pshu_tab_groups}"]="$page?groups=$enc";
    echo pshu_style().$tpl->_ENGINE_parse_body(swd_package(swd_file($p))).$tpl->tabs_default($tabs);
    return true;
}

// 1. Fiche du paquet
function swd_package(array $f):string{
    $chips=array();
    $chips[]="<span class='ak-chip ak-accent pshu-chip'>{pshu_type_software}</span>";
    $chips[]="<span class='ak-chip pshu-chip'>".pshu_t("swd_debian",array($f["deb"]))."</span>";
    $chips[]="<span class='ak-chip pshu-chip'>".pshu_t("pshu_version",array(htmlspecialchars($f["ver"])))."</span>";
    if($f["size"]>=0){$chips[]="<span class='ak-chip pshu-chip'>".swd_size($f["size"])."</span>";}
    if(strlen($f["md5"])==32 && ctype_xdigit($f["md5"])){
        $md5=$f["md5"];
        $chips[]="<span class='ak-chip pshu-chip' title='$md5'>MD5 ".substr($md5,0,8)."&hellip;".substr($md5,-4)
            ."<button type='button' class='ak-act pshu-copy' aria-label=\"{hfx_copy_md5}\" title=\"{hfx_copy_md5}\" onclick=\"PshuCopy(this,'$md5')\"><i class='far fa-copy'></i></button></span>";
    }
    $warn="";
    if($f["indexed"]===false){
        $warn="<div class='ak-banner ak-warn pshu-warn'><i class='fas fa-triangle-exclamation'></i><span class='ak-banner-m'>{pshu_not_in_repo}</span></div>";
    }
    return "<section class='ak pshu-pkg'><div class='ak-strip'><span class='ak-ico ak-ico-sq ak-info'><i class='fas fa-cube'></i></span>
<div class='ak-strip-main'><div class='ak-strip-t'>{".$f["code"]."} <span class='ak-mono pshu-fn'>".htmlspecialchars($f["ver"])."</span></div>
<div class='ak-code'>".htmlspecialchars($f["rel"])."</div><div class='ak-chips pshu-chips'>".implode("",$chips)."</div></div></div>$warn</section>";
}

// ---------- Onglet Nodes ----------
function swd_nodes_model(array $p,int $pre):array{
    $m=array("error"=>"","agents"=>array());
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/alist?status=online&enabled=1"));
    if(!is_object($json)){$m["error"]="Artica REST API: no answer";return $m;}
    if(isset($json->Status) && !$json->Status){$m["error"]=(string)($json->Error ?? "{error}");return $m;}
    if(!isset($json->agents) || !is_array($json->agents)){return $m;}
    foreach($json->agents as $ag){
        if(($ag->status ?? "")!="online"){continue;}
        $a=array("id"=>intval($ag->id ?? 0),"host"=>(string)($ag->hostname ?? ""),"ip"=>(string)($ag->ip_address ?? ""),
            "deb"=>intval($ag->debian_version ?? 0),"ver"=>trim((string)($ag->artica_version ?? "")),
            "blacklisted"=>!empty($ag->blacklisted));
        if($a["id"]<=0){continue;}
        $a["state"]=swd_state($p,$a["deb"],$a["blacklisted"]);
        $a["checked"]=($pre==$a["id"] && $a["state"]!="blocked");
        $m["agents"][]=$a;
    }
    usort($m["agents"],function($x,$y){ return strcasecmp($x["host"],$y["host"]); });
    return $m;
}

function popup():bool{
    $tpl = new template_admin();
    $p=swd_get_rel("popup");
    if(count($p)==0){echo $tpl->_ENGINE_parse_body("<div class='ak'><div class='ak-banner ak-err'><span class='ak-banner-m'>{swd_invalid}</span></div></div>");return false;}
    $pre = isset($_GET["agent_id"]) ? intval($_GET["agent_id"]) : 0;
    $m=swd_nodes_model($p,$pre);
    $id=pshu_host_id();
    $html=array();
    $html[]="<div class='ak pshu' id='$id' data-kind='nodes' data-file='".htmlspecialchars($p["rel"],ENT_QUOTES)."' data-type='software'>";
    if($m["error"]!=""){
        $html[]="<div class='ak-banner ak-err'><i class='fas fa-circle-exclamation'></i><span class='ak-banner-m'>".htmlspecialchars($m["error"])."</span></div>";
    }elseif(count($m["agents"])==0){
        $html[]=pshu_empty("fa-plug-circle-xmark","{pshu_empty_nodes}","{pshu_empty_nodes_s}");
    }else{
        $html[]=pshu_toolbar($id,"nodes",true,count($m["agents"]));
        $html[]=swd_nodes_table($id,$m,$p);
        $html[]=pshu_confirm($id,"nodes");
        $html[]=pshu_actionbar($id,"nodes");
    }
    $html[]=pshu_activity_host($id,$p["rel"]);
    $html[]="</div>";
    echo pshu_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).pshu_js($id);
    return true;
}

function swd_nodes_table(string $id,array $m,array $p):string{
    $rows=array();
    foreach($m["agents"] as $a){$rows[]=swd_node_row($id,$a,$p);}
    return "<div class='ak-card pshu-tblwrap'><div class='pshu-scroll' tabindex='0' role='region' aria-label=\"{pshu_tab_nodes}\"><table class='ak-tbl pshu-tbl'>
<thead><tr><th class='pshu-cbcol'><input type='checkbox' id='$id-all' aria-label=\"{pshu_sel_all}\" onclick=\"PshuSel('$id',this.checked?'all':'none')\"></th>
<th>{pshu_th_host}</th><th>{swd_th_debian}</th><th>{pshu_th_ver}</th><th>{pshu_th_app}</th></tr></thead>
<tbody id='$id-rows'>".implode("",$rows)."</tbody></table>
<div class='pshu-nomatch' id='$id-nomatch' hidden>{pshu_nomatch}</div></div></div>";
}

function swd_node_row(string $id,array $a,array $p):string{
    $st=$a["state"];
    $locked=($st=="blocked");
    $host=netagent_display_name($a["id"],$a["host"]);
    $ip=netagent_address_label($a["ip"]);
    $q=htmlspecialchars(strtolower($a["host"]." ".$a["ip"]),ENT_QUOTES);
    $lid="$id-l".$a["id"];
    $deb=$a["deb"]>0?"<span class='ak-mono".($st=="other"?" pshu-diff":"")."'>Debian ".$a["deb"]."</span>":"<span class='ak-muted'>&mdash;</span>";
    $ver=$a["ver"]==""?"<span class='ak-muted'>&mdash;</span>":"<span class='ak-mono'>".htmlspecialchars($a["ver"])."</span>";
    $cls="pshu-row pshu-st-$st".($a["checked"]?" on":"");
    $h="<tr class='$cls' data-q='$q' data-st='$st' data-label='$host'>";
    $h.="<td class='pshu-cbcol'><input type='checkbox' class='pshu-cb' value='{$a["id"]}' aria-labelledby='$lid'".($a["checked"]?" checked":"").($locked?" disabled":"")." onchange=\"PshuSync('$id')\"></td>";
    $h.="<td><span class='ak-name' id='$lid'>$host</span><span class='ak-code'>$ip</span></td>";
    $h.="<td>$deb</td><td>$ver</td><td>".swd_state_pill($st,$p)."</td></tr>";
    return $h;
}

function swd_state_pill(string $st,array $p):string{
    if($st=="blocked"){return "<span class='ak-pill ak-err pshu-pill' title=\"{pshu_tip_blocked}\"><i class='fas fa-ban'></i> {pshu_st_blocked}</span>";}
    $L=swd_labels();
    if(!isset($L[$st])){return "";}
    $tip="";
    if($st=="other"){$tip=" title=\"".htmlspecialchars(pshu_t("swd_tip_other",array($p["deb"])),ENT_QUOTES)."\"";}
    if($st=="unknown"){$tip=" title=\"{swd_tip_unknown}\"";}
    return "<span class='ak-pill {$L[$st][0]} pshu-pill'$tip><i class='fas {$L[$st][1]}'></i> {$L[$st][2]}</span>";
}

// ---------- Onglet Groups ----------
function swd_eval(array $p){
    return function($ag)use($p){ return swd_state($p,intval($ag->debian_version ?? 0),false); };
}

function swd_groups_model(array $p):array{
    $m=array("error"=>"","groups"=>array());
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups"));
    if($json===null){$m["error"]="Artica REST API: no answer";return $m;}
    if(is_object($json) && isset($json->Status) && !$json->Status){$m["error"]=(string)($json->Error ?? "{error}");return $m;}
    if(!is_array($json)){return $m;}
    foreach($json as $g){
        $gid=intval($g->id ?? 0);
        if($gid<=0){continue;}
        $row=array("id"=>$gid,"name"=>(string)($g->name ?? "#$gid"),"desc"=>trim((string)($g->description ?? "")),
            "denied"=>!empty($g->blacklisted),"read"=>false);
        $m["groups"][]=array_merge($row,pshu_group_targets($gid,swd_eval($p)));
    }
    usort($m["groups"],function($x,$y){ return strcasecmp($x["name"],$y["name"]); });
    return $m;
}

function groups():bool{
    $tpl = new template_admin();
    $p=swd_get_rel("groups");
    if(count($p)==0){echo $tpl->_ENGINE_parse_body("<div class='ak'><div class='ak-banner ak-err'><span class='ak-banner-m'>{swd_invalid}</span></div></div>");return false;}
    $m=swd_groups_model($p);
    $id=pshu_host_id();
    $html=array();
    $html[]="<div class='ak pshu' id='$id' data-kind='groups' data-file='".htmlspecialchars($p["rel"],ENT_QUOTES)."' data-type='software'>";
    if($m["error"]!=""){
        $html[]="<div class='ak-banner ak-err'><i class='fas fa-circle-exclamation'></i><span class='ak-banner-m'>".htmlspecialchars($m["error"])."</span></div>";
    }elseif(count($m["groups"])==0){
        $html[]=pshu_empty("fa-layer-group","{pshu_empty_groups}","");
    }else{
        $rows=array();
        foreach($m["groups"] as $g){$rows[]=pshu_group_row($id,$g,true,swd_labels());}
        $html[]=pshu_toolbar($id,"groups",false,count($m["groups"]));
        $html[]="<div class='ak-card pshu-tblwrap'><div class='pshu-scroll' tabindex='0' role='region' aria-label=\"{pshu_tab_groups}\"><table class='ak-tbl pshu-tbl pshu-gtbl'>
<thead><tr><th class='pshu-cbcol'><input type='checkbox' id='$id-all' aria-label=\"{pshu_sel_all}\" onclick=\"PshuSel('$id',this.checked?'all':'none')\"></th>
<th>{pshu_th_group}</th><th>{pshu_th_online}</th><th>{pshu_th_app}</th><th class='ak-num'>{pshu_th_offline}</th></tr></thead>
<tbody id='$id-rows'>".implode("",$rows)."</tbody></table>
<div class='pshu-nomatch' id='$id-nomatch' hidden>{pshu_nomatch}</div></div></div>";
        $html[]=pshu_confirm($id,"groups");
        $html[]=pshu_actionbar($id,"groups");
    }
    $html[]=pshu_activity_host($id,$p["rel"]);
    $html[]="</div>";
    echo pshu_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).pshu_js($id);
    return true;
}

// ---------- Envois (POST) : n'ecrivent que l'erreur ----------
function swd_post_ids(string $key):array{
    $ids=array();
    foreach(explode(",",(string)($_POST[$key] ?? "")) as $raw){
        $n=intval(trim($raw));
        if($n>0){$ids[$n]=true;}
    }
    return array_keys($ids);
}

function swd_send(array $p,array $ids,int $gid=0):string{
    $data=array("filename"=>swd_repo_path().$p["rel"],"debian-version"=>$p["deb"],"product"=>$p["code"],"agent_ids"=>implode(",",$ids));
    if($gid>0){$data["group_id"]=$gid;}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/articarepos/push-software",$data));
    if(!is_object($json)){return "Artica REST API: no answer";}
    if(isset($json->Status) && !$json->Status){return (string)($json->Error ?? "error");}
    return "";
}

function push():bool{
    $tpl = new template_admin();
    if(!swd_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}
    $p=swd_parse((string)($_POST["filename"] ?? ""));
    if(count($p)==0){echo $tpl->_ENGINE_parse_body("{swd_invalid}");return false;}
    $ids=swd_post_ids("agent_ids");
    if(count($ids)==0){echo $tpl->_ENGINE_parse_body("{pshu_nothing}");return false;}
    $err=swd_send($p,$ids);
    if($err!=""){echo $err;return false;}
    return admin_tracks("Push software {$p["code"]} {$p["ver"]} (Debian {$p["deb"]}) to ".count($ids)." Meta nodes (".implode(",",$ids).")");
}

// Groupes : noeuds en ligne, Artica et non bloques ; groupe "Deny from updates" ignore ;
// un envoi par groupe (nomme d'apres lui), un noeud de deux groupes n'est envoye qu'une fois.
function push_groups():bool{
    $tpl = new template_admin();
    if(!swd_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}
    $p=swd_parse((string)($_POST["filename"] ?? ""));
    if(count($p)==0){echo $tpl->_ENGINE_parse_body("{swd_invalid}");return false;}
    $gids=swd_post_ids("group_ids");
    if(count($gids)==0){echo $tpl->_ENGINE_parse_body("{pshu_nothing}");return false;}
    $glist=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups"));
    $denied=array();
    if(is_array($glist)){ foreach($glist as $g){ if(!empty($g->blacklisted)){$denied[intval($g->id ?? 0)]=true;} } }
    $seen=array();$plan=array();
    foreach($gids as $gid){
        if(isset($denied[$gid])){continue;}
        $t=pshu_group_targets($gid);
        $list=array();
        foreach($t["on"] as $aid){
            if(isset($seen[$aid])){continue;}
            $seen[$aid]=true;$list[]=$aid;
        }
        if(count($list)>0){$plan[$gid]=$list;}
    }
    if(count($seen)==0){echo $tpl->_ENGINE_parse_body("{pshu_no_target}");return false;}
    $done=array();
    foreach($plan as $gid=>$list){
        $err=swd_send($p,$list,$gid);
        if($err!=""){
            if(count($done)>0){admin_tracks("Push software {$p["code"]} {$p["ver"]} started for groups ".implode(",",$done)." then failed on group $gid: $err");}
            echo $err;return false;
        }
        $done[]=$gid;
    }
    return admin_tracks("Push software {$p["code"]} {$p["ver"]} (Debian {$p["deb"]}) to ".count($seen)." Meta nodes via groups ".implode(",",$done));
}

// ---------- Detail d'une operation ----------
function swd_op_id(string $key):string{
    return preg_replace('/[^A-Za-z0-9_.-]/','',(string)($_GET[$key] ?? ""));
}

function operation_details():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=swd_op_id("operation-details");
    return $tpl->js_dialog11("{operation_details}","$page?operation-details-popup=$id",800);
}
// Rapport d'un envoi de logiciel : fenetre commune opd_* (class.artica.push.ui.inc).
// Le demon garde ces rapports 5 min apres la fin (SoftwarePushOperationTTL).
function operation_details_popup():bool{
    return opd_popup(swd_op_id("operation-details-popup"),"/articarepos/push-software/status",5);
}
