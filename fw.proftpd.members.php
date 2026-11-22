<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

// Comptes FTP (ProFTPD), maquette stitch_artica_ftp_server_members.
// Toutes les lectures et ecritures passent par articarest (/proftpd/users,
// proftpd/users.go) : la console n'ouvre jamais ftpusers.db. Le mot de passe
// n'est jamais relu ; vide a la modification = inchange.

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit();}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["member-js"])){zmember_js();exit;}
if(isset($_GET["member-popup"])){member_popup();exit;}
if(isset($_POST["id"])){member_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{ftpm_title}","fas fa-user-group","{ftpm_explain}","$page?table=yes","ftp-users","progress-proftpd-restart",false,"table-proftpd-members");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{ftpm_title}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Helpers ─────────────────────────────────────────────────────────────────

// Traduit d'abord, substitue ensuite (%1, %2).
function ftpm_t($token,$a="",$b=""):string{
    if(!isset($GLOBALS["FTPM_T"])){$GLOBALS["FTPM_T"]=array();}
    if(!isset($GLOBALS["FTPM_T"][$token])){
        $tpl=new template_admin();
        $GLOBALS["FTPM_T"][$token]=$tpl->_ENGINE_parse_body("{"."$token}");
    }
    return str_replace(array("%1","%2"),array($a,$b),$GLOBALS["FTPM_T"][$token]);
}

function ftpm_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

// Age relatif ; la date exacte va dans l'info-bulle.
function ftpm_ago($epoch):string{
    $d=time()-intval($epoch);
    if($d<60){return ftpm_t("ftpsv_just_now");}
    if($d<3600){return ftpm_t("ftpsv_min_ago",(string)intval($d/60));}
    if($d<86400){return ftpm_t("ftpsv_h_ago",(string)intval($d/3600));}
    if($d<14*86400){return ftpm_t("ftpsv_d_ago",(string)intval($d/86400));}
    return ftpm_t("ftpm_w_ago",(string)intval($d/(7*86400)));
}

// Initiales de l'avatar : deux premieres lettres ou initiales des mots.
function ftpm_initials($name):string{
    $parts=preg_split('#[^A-Za-z0-9]+#',(string)$name,-1,PREG_SPLIT_NO_EMPTY);
    if(count($parts)>=2){return strtoupper(substr($parts[0],0,1).substr($parts[1],0,1));}
    return strtoupper(substr((string)$name,0,2));
}

// Couleur d'avatar stable par nom (8 teintes douces).
function ftpm_avatar_class($name):string{
    return "ftpm-av".(abs(crc32((string)$name))%8);
}

// Renvoie [objet, erreur] ; l'erreur est deja lisible (texte de l'API). Un compte tenu
// par Artica Meta (HTTP 423, Error "managed_by_meta") devient le jeton de la console.
function ftpm_api($json):array{
    $o=json_decode((string)$json);
    if(!is_object($o)){return array(null,"articarest: no answer");}
    if(property_exists($o,"Status") && !$o->Status){
        if(!empty($o->meta_locked) || (property_exists($o,"Error") && (string)$o->Error=="managed_by_meta")){
            return array(null,"{ftp_meta_account_refused}");
        }
        return array(null,property_exists($o,"Error")?(string)$o->Error:"articarest: refused");
    }
    return array($o,"");
}

// Verrou de la politique Artica Meta « FTP service » pose sur ce noeud.
function ftpm_meta_managed():bool{
    return trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FTPMetaManaged"))==="1";
}

function ftpm_meta_tag():string{
    return "<span class='ftpm-meta' title=\"{ftpm_meta_held_tip}\"><i class='fas fa-lock'></i> Artica Meta</span>";
}

// Longueur du mot de passe (8 a 128 caracteres, regle d'articarest) ; vide = garde.
function ftpm_password_ok($p):bool{
    $p=(string)$p;
    if($p===""){return true;}
    $n=function_exists("mb_strlen")?mb_strlen($p,"UTF-8"):strlen($p);
    return $n>=8 && $n<=128;
}

function ftpm_user($id):array{
    list($o,$err)=ftpm_api($GLOBALS["CLASS_SOCKETS"]->REST_API("/proftpd/users/".intval($id)));
    if($err!==""){return array(array(),$err);}
    if(!property_exists($o,"user") || !is_object($o->user)){return array(array(),"articarest: no account");}
    return array((array)$o->user,"");
}

// Comptes systeme proposes ("uid:gid" => nom), sans root.
function ftpm_system_users():array{
    if(isset($GLOBALS["FTPM_SYSUSERS"])){return $GLOBALS["FTPM_SYSUSERS"];}
    $out=array();
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/systemlist"));
    if(is_object($json) && property_exists($json,"Status") && $json->Status && property_exists($json,"users")){
        foreach($json->users as $key=>$name){$out[(string)$key]=(string)$name;}
        foreach(array_keys($out) as $k){
            if($out[$k]=="root" || strpos($k,"0:")===0){unset($out[$k]);}
        }
    }
    $GLOBALS["FTPM_SYSUSERS"]=$out;
    return $out;
}

// JS de rafraichissement : la page des comptes, ou la page du service quand la
// fenetre a ete ouverte depuis son invitation « Create an FTP account ».
function ftpm_refresh_js():string{
    $page=CurrentPageName();
    if(($_GET["refresh"] ?? "")=="ftpsv"){return "if(typeof FtpsvRefresh==='function'){FtpsvRefresh();}";}
    return "LoadAjax('table-proftpd-members','$page?table=yes');";
}

// ── Modele ──────────────────────────────────────────────────────────────────

function ftpm_model():array{
    $m=array("ok"=>false,"error"=>"","users"=>array(),"total"=>0,"used"=>0,"never"=>0,"meta"=>0,
        "last_userid"=>"","last_epoch"=>0,"total_logins"=>0);
    list($o,$err)=ftpm_api($GLOBALS["CLASS_SOCKETS"]->REST_API("/proftpd/users"));
    if($err!==""){$m["error"]=$err;return $m;}
    $m["ok"]=true;
    $sys=ftpm_system_users();
    $rows=property_exists($o,"users") && is_array($o->users)?$o->users:array();
    foreach($rows as $u){
        $zuid=intval($u->uid).":".intval($u->gid);
        $r=array(
            "id"=>intval($u->id),
            "userid"=>(string)$u->userid,
            "uid"=>intval($u->uid),
            "homedir"=>(string)$u->homedir,
            "owner"=>isset($sys[$zuid])?$sys[$zuid]:"",
            "count"=>intval($u->count),
            "accessed"=>intval($u->accessed_epoch),
            "meta"=>!empty($u->meta_held),
            // login_allowed absent (articarest plus ancien) = compte actif.
            "enabled"=>!property_exists($u,"login_allowed") || !empty($u->login_allowed),
        );
        if($r["meta"]){$m["meta"]++;}
        $m["users"][]=$r;
        $m["total_logins"]+=$r["count"];
        // Tant que count vaut 0, accessed est la date de creation : jamais une connexion.
        if($r["count"]>0){
            $m["used"]++;
            if($r["accessed"]>$m["last_epoch"]){$m["last_epoch"]=$r["accessed"];$m["last_userid"]=$r["userid"];}
        }else{
            $m["never"]++;
        }
    }
    $m["total"]=count($m["users"]);
    return $m;
}

// ── Contenu ─────────────────────────────────────────────────────────────────

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $m=ftpm_model();
    $h=array();
    $h[]="<div class='ftpm'>";
    $h[]=ftpm_hero();
    if(!$m["ok"]){
        $h[]="<div class='ftpm-alert'><i class='fas fa-circle-exclamation'></i><div><strong>{ftpm_unreadable}</strong><br>".ftpm_h($m["error"])."</div></div>";
    }else{
        if($m["meta"]>0 || ftpm_meta_managed()){
            $h[]="<div class='ftpm-meta-banner' role='status'><i class='fas fa-diagram-project'></i><div>{ftpm_meta_banner}</div></div>";
        }
        // Sans compte, l'invitation suffit : des tuiles a zero n'apprennent rien.
        if($m["total"]==0){
            $h[]=ftpm_empty();
        }else{
            $h[]=ftpm_tiles($m);
            $h[]=ftpm_card($m);
        }
    }
    $h[]="</div>";

    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?member-js=0');",ico_plus,"{ftpm_new_account}","AsSystemAdministrator");
    $topbuttons[]=array("LoadAjax('table-proftpd-members','$page?table=yes');",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{ftpm_title}";
    $TINY_ARRAY["ICO"]="fas fa-user-group";
    $TINY_ARRAY["EXPL"]="{ftpm_explain}";
    $TINY_ARRAY["URL"]="ftp-users";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo ftpm_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>NoSpinner();".ftpm_js()."$tiny</script>";
}

// ── Widgets (une fonction chacun) ──────────────────────────────────────────

function ftpm_hero():string{
    return "<div class='ftpm-hero'><div class='ftpm-hero-ico'><i class='fas fa-user-group'></i></div>"
        ."<div class='ftpm-hero-body'><h3>{ftpm_hero_title}</h3><p>{ftpm_hero_text}</p><div class='ftpm-chips'>"
        ."<span class='ftpm-chip'><i class='fas fa-lock'></i> {ftpm_chip_jail}</span>"
        ."<span class='ftpm-chip'><i class='fas fa-key'></i> {ftpm_chip_pwd}</span>"
        ."<span class='ftpm-chip'><i class='fas fa-folder-open'></i> {ftpm_chip_keep}</span>"
        ."</div></div><a class='ftpm-btn' href='/ftp-service'><i class='fas fa-sliders'></i> {ftpm_server_settings}</a></div>";
}

function ftpm_tiles($m):string{
    return "<div class='ftpm-tiles'>".ftpm_tile_total($m).ftpm_tile_used($m).ftpm_tile_never($m).ftpm_tile_last($m)."</div>";
}

function ftpm_tile_total($m):string{
    return "<div class='ftpm-tile'><div><div class='ftpm-tile-l'>{ftpm_total_accounts}</div><div class='ftpm-tile-v'>".number_format($m["total"])."</div></div>"
        ."<div class='ftpm-tile-ico'><i class='fas fa-users'></i></div></div>";
}

// Anneau mesure contre le nombre reel de comptes.
function ftpm_ring($part,$total):string{
    $pct=$total>0?round(100*$part/$total):0;
    $c=2*M_PI*16;
    $dash=sprintf("%.2f %.2f",$c*$pct/100,$c);
    $fg=$pct>0?"<circle cx='20' cy='20' r='16' class='ftpm-ring-fg' stroke-dasharray='$dash' transform='rotate(-90 20 20)'/>":"";
    return "<svg class='ftpm-ring' viewBox='0 0 40 40' role='img' aria-label='$pct%'><circle cx='20' cy='20' r='16' class='ftpm-ring-bg'/>"
        .$fg
        ."<text x='20' y='23.5' text-anchor='middle'>$pct%</text></svg>";
}

function ftpm_tile_used($m):string{
    return "<div class='ftpm-tile'><div><div class='ftpm-tile-l'>{ftpm_signed_once}</div>"
        ."<div class='ftpm-tile-v'>".number_format($m["used"])." <small>".ftpm_t("ftpm_of_n",number_format($m["total"]))."</small></div></div>"
        .ftpm_ring($m["used"],$m["total"])."</div>";
}

function ftpm_tile_never($m):string{
    $cls=$m["never"]>0?" ftpm-warn":"";
    $click=$m["never"]>0?" role='button' tabindex='0' OnClick=\"FtpmFilter('never');\"":"";
    return "<div class='ftpm-tile$cls'$click><div><div class='ftpm-tile-l'>{ftpm_never_used}</div>"
        ."<div class='ftpm-tile-v'>".number_format($m["never"])."</div><div class='ftpm-tile-s'>{ftpm_never_help}</div></div>"
        ."<div class='ftpm-tile-ico'><i class='fas fa-clock-rotate-left'></i></div></div>";
}

function ftpm_tile_last($m):string{
    if($m["last_epoch"]<=0){
        $v="<span class='ftpm-muted'>{ftpm_no_signin}</span>";
        $sub="";
    }else{
        $v="<span title='".date("Y-m-d H:i:s",$m["last_epoch"])."'>".ftpm_ago($m["last_epoch"])."</span> <small>· ".ftpm_h($m["last_userid"])."</small>";
        $sub="<div class='ftpm-tile-s'>".ftpm_t("ftpsv_logins_total",number_format($m["total_logins"]))."</div>";
    }
    return "<div class='ftpm-tile'><div class='ftpm-tile-main'><div class='ftpm-tile-l'>{ftpm_last_signin}</div>"
        ."<div class='ftpm-tile-v ftpm-tile-v-sm'>$v</div>$sub</div>"
        ."<div class='ftpm-tile-ico'><i class='fas fa-right-to-bracket'></i></div></div>";
}

function ftpm_empty():string{
    return "<div class='ftpm-card ftpm-empty'><div class='ftpm-empty-ico'><i class='fas fa-user-plus'></i></div>"
        ."<h4>{ftpsv_empty_title}</h4><p>{ftpsv_empty_text}</p>"
        ."<button type='button' class='ftpm-btn ftpm-btn-primary' OnClick=\"Loadjs('".CurrentPageName()."?member-js=0');\"><i class='fas fa-user-plus'></i> {ftpm_new_account}</button>"
        ."<div class='ftpm-example'>{ftpm_empty_example}</div></div>";
}

function ftpm_row($u):string{
    $page=CurrentPageName();
    $id=$u["id"];
    $name=ftpm_h($u["userid"]);
    $edit=$u["meta"]?"":"Loadjs('$page?member-js=$id');";
    $state=$u["count"]>0?"used":"never";
    if($u["count"]>0){
        $last="<span class='ftpm-ago' title='".date("Y-m-d H:i:s",$u["accessed"])."'>".ftpm_ago($u["accessed"])."</span>";
        $count=number_format($u["count"]);
    }else{
        $last="<span class='ftpm-badge'><i class='fas fa-circle-minus'></i> {ftpm_never_used_badge}</span>";
        $count="<span class='ftpm-muted'>0</span>";
    }
    $owner=$u["owner"]!==""?ftpm_h($u["owner"]):"—";
    $search=ftpm_h(strtolower($u["userid"]." ".$u["homedir"]));
    $label=ftpm_h(ftpm_t("ftpm_edit_x",$u["userid"]));
    $dlabel=ftpm_h(ftpm_t("ftpm_delete_x",$u["userid"]));
    $tags="";
    if($u["meta"]){$tags.=" ".ftpm_meta_tag();}
    if(!$u["enabled"]){$tags.=" <span class='ftpm-badge ftpm-off'><i class='fas fa-ban'></i> {disabled}</span>";}
    // Compte tenu par Meta : actions grisees, la raison en info-bulle.
    if($u["meta"]){
        $tip=ftpm_h(ftpm_t("ftpm_meta_held_tip"));
        $actions="<button type='button' class='ftpm-ibtn' disabled aria-disabled='true' aria-label=\"$label\" title=\"$tip\"><i class='fas fa-pen'></i></button>"
            ."<button type='button' class='ftpm-ibtn' disabled aria-disabled='true' aria-label=\"$dlabel\" title=\"$tip\"><i class='fas fa-trash'></i></button>";
        $acc="<div class='ftpm-acc ftpm-acc-ro'>";
    }else{
        $actions="<button type='button' class='ftpm-ibtn' aria-label=\"$label\" title=\"$label\" OnClick=\"$edit\"><i class='fas fa-pen'></i></button>"
            ."<button type='button' class='ftpm-ibtn ftpm-ibtn-del' aria-label=\"$dlabel\" title=\"$dlabel\" OnClick=\"Loadjs('$page?delete-js=$id');\"><i class='fas fa-trash'></i></button>";
        $acc="<div class='ftpm-acc' OnClick=\"$edit\">";
    }
    return "<tr data-state='$state' data-search=\"$search\">"
        ."<td>$acc<span class='ftpm-av ".ftpm_avatar_class($u["userid"])."'>".ftpm_h(ftpm_initials($u["userid"]))."</span><strong>$name</strong>$tags</div></td>"
        ."<td><span class='ftpm-path'>".ftpm_h($u["homedir"])."</span></td>"
        ."<td><div>$owner</div><div class='ftpm-sub'>uid {$u["uid"]}</div></td>"
        ."<td>$last</td>"
        ."<td class='ftpm-num'>$count</td>"
        ."<td class='ftpm-actions'>$actions</td>"
        ."</tr>";
}

function ftpm_card($m):string{
    $page=CurrentPageName();
    $rows=array();
    foreach($m["users"] as $u){$rows[]=ftpm_row($u);}
    $seg="<div class='ftpm-seg' role='radiogroup' aria-label='{ftpm_filter}'>"
        ."<button type='button' role='radio' aria-checked='true' data-f='all' OnClick=\"FtpmFilter('all');\">".ftpm_t("ftpm_seg_all",(string)$m["total"])."</button>"
        ."<button type='button' role='radio' aria-checked='false' data-f='used' OnClick=\"FtpmFilter('used');\">".ftpm_t("ftpm_seg_used",(string)$m["used"])."</button>"
        ."<button type='button' role='radio' aria-checked='false' data-f='never' OnClick=\"FtpmFilter('never');\">".ftpm_t("ftpm_seg_never",(string)$m["never"])."</button></div>";
    $ph=ftpm_h(ftpm_t("ftpm_search_ph"));
    return "<div class='ftpm-card'>"
        ."<div class='ftpm-card-h'><h3>{ftpm_card_title} <span class='ftpm-count'>".number_format($m["total"])."</span></h3>"
        ."<div class='ftpm-tools'><label class='ftpm-search'><i class='fas fa-magnifying-glass'></i><input type='search' id='ftpm-q' placeholder=\"$ph\" aria-label=\"$ph\" OnInput=\"FtpmApply();\"></label>"
        .$seg
        ."<button type='button' class='ftpm-btn ftpm-btn-primary' OnClick=\"Loadjs('$page?member-js=0');\"><i class='fas fa-user-plus'></i> {ftpm_new_account}</button></div></div>"
        ."<div class='ftpm-tablewrap'><table class='ftpm-table' id='ftpm-table'><thead><tr>"
        ."<th>{ftpm_col_account}</th><th>{ftpm_col_folder}</th><th>{ftpm_col_owner}</th><th>{ftpm_last_signin}</th><th class='ftpm-num'>{ftpm_col_signins}</th><th class='ftpm-actions'>{ftpm_col_actions}</th>"
        ."</tr></thead><tbody>".implode("",$rows)."</tbody></table>"
        ."<div class='ftpm-nomatch' id='ftpm-nomatch' style='display:none'>{ftpm_no_match}</div></div>"
        ."<div class='ftpm-foot'><span id='ftpm-shown'>".ftpm_t("ftpm_shown",(string)$m["total"],(string)$m["total"])."</span></div>"
        ."</div>";
}

// ── Modales et ecritures ────────────────────────────────────────────────────

function delete_js(){
	$tpl=new template_admin();
	$id=intval($_GET["delete-js"]);
    list($u,$err)=ftpm_user($id);
    if($err==="" && !empty($u["meta_held"])){$tpl->js_error("{ftp_meta_account_refused}");return;}
    $name=$err===""?$u["userid"]:"#$id";
    $dir=$err===""?$u["homedir"]:"";
    $text="<strong style='font-size:16px'>".ftpm_h(ftpm_t("ftpm_delete_title",$name))."</strong><br><br>"
        .ftpm_t("ftpm_delete_text",ftpm_h($name),"<span style='font-family:monospace'>".ftpm_h($dir)."</span>");
	$tpl->js_confirm_delete($text,"delete",$id,ftpm_refresh_js(),true);
}

function delete(){
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl=new template_admin();echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
	$id=intval($_POST["delete"]);
    list($o,$err)=ftpm_api($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/proftpd/users/$id"));
	if($err!==""){echo (new template_admin())->post_error($err);return;}
    admin_tracks("FTP account #$id deleted (its folder is kept)");
}

function zmember_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$title="{ftpm_new_account}";
	$id=intval($_GET["member-js"]);
	if($id>0){
        list($u,$err)=ftpm_user($id);
        if($err===""){$title=$tpl->javascript_parse_text($u["userid"]);}
	}
    $refresh=($_GET["refresh"] ?? "")=="ftpsv"?"&refresh=ftpsv":"";
	$tpl->js_dialog1($title, "$page?member-popup=$id$refresh",620);
}

function member_popup(){
	$tpl=new template_admin();
	$btname="{ftpm_create_account}";
	$id=intval($_GET["member-popup"]);
	$js=array();
	$js[]=ftpm_refresh_js();
    $ligne=array("userid"=>"","uid"=>"","gid"=>"","homedir"=>"");
	if($id>0){
		$btname="{ftpm_save_changes}";
        list($ligne,$err)=ftpm_user($id);
        if($err!==""){echo $tpl->div_error(ftpm_h($err));return false;}
        if(!empty($ligne["meta_held"])){echo $tpl->div_error("{ftp_meta_account_refused}");return false;}
	}
    $js[]="dialogInstance1.close();";
    $REALUSERS=ftpm_system_users();
    if(count($REALUSERS)==0){
        echo $tpl->div_error("{ftpm_no_system_users}");
        return false;
    }
	$zuid=$id>0?"{$ligne["uid"]}:{$ligne["gid"]}":"";
	$form=array();
	$form[]=$tpl->field_hidden("id", $id);
	$form[]=$tpl->field_text("userid", "{ftpm_f_username}", $ligne["userid"],true,"{ftpm_f_username_help}");
	$form[]=$tpl->field_password2("passwd", "{password}", "",$id==0,$id>0?"{ftpm_password_keep_len}":"{ftpm_password_help_len}");
	$form[]=$tpl->field_array_hash($REALUSERS, "uid", "{ftpm_col_owner}", $zuid,true,"{ftpm_system_user_help}");
	$form[]=$tpl->field_browse_directory("homedir", "{ftpm_col_folder}", $ligne["homedir"],null,true,"{ftpm_homedir_help}");
	echo $tpl->form_outside("", @implode("\n", $form),"{ftpm_popup_explain}",$btname,@implode(";", $js),"AsSystemAdministrator");
}

// Le mot de passe est transmis tel quel (url_decode de CLEAN_POST compris) ;
// articarest valide tout le reste et cree le dossier du compte.
function member_save(){
	$tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
	$tpl->CLEAN_POST();
    if(!ftpm_password_ok($_POST["passwd"] ?? "")){echo $tpl->post_error("{ftpm_password_length}");return;}
	$tt=explode(":",(string)($_POST["uid"] ?? ""));
	$payload=array(
        "id"=>intval($_POST["id"]),
        "userid"=>trim((string)($_POST["userid"] ?? "")),
        "passwd"=>(string)($_POST["passwd"] ?? ""),
        "uid"=>intval($tt[0]),
        "gid"=>intval(isset($tt[1])?$tt[1]:0),
        "homedir"=>trim((string)($_POST["homedir"] ?? "")),
    );
    list($o,$err)=ftpm_api($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/proftpd/users",$payload));
	if($err!==""){echo $tpl->post_error($err);return;}
    $what=$payload["id"]==0?"created":"updated";
    admin_tracks("FTP account {$payload["userid"]} $what (folder {$payload["homedir"]})");
}

// ── Style et script ─────────────────────────────────────────────────────────

function ftpm_js():string{
    $shown=json_encode(html_entity_decode(ftpm_t("ftpm_shown","%1","%2"),ENT_QUOTES,"UTF-8"));
    return "
window.FtpmState={f:'all'};
function FtpmFilter(f){
    window.FtpmState.f=f;
    var b=document.querySelectorAll('.ftpm-seg button');
    for(var i=0;i<b.length;i++){b[i].setAttribute('aria-checked',b[i].getAttribute('data-f')===f?'true':'false');}
    FtpmApply();
}
function FtpmApply(){
    var t=document.getElementById('ftpm-table'); if(!t){return;}
    var q=document.getElementById('ftpm-q'); q=q?q.value.toLowerCase().trim():'';
    var rows=t.tBodies[0].rows, n=0;
    for(var i=0;i<rows.length;i++){
        var r=rows[i], ok=true;
        if(window.FtpmState.f!=='all' && r.getAttribute('data-state')!==window.FtpmState.f){ok=false;}
        if(ok && q!=='' && (r.getAttribute('data-search')||'').indexOf(q)<0){ok=false;}
        r.style.display=ok?'':'none'; if(ok){n++;}
    }
    var nm=document.getElementById('ftpm-nomatch'); if(nm){nm.style.display=n===0?'':'none';}
    var s=document.getElementById('ftpm-shown'); if(s){s.textContent=$shown.replace('%1',n).replace('%2',rows.length);}
}
";
}

function ftpm_style():string{
    if(isset($GLOBALS["FTPM_STYLE"])){return "";}
    $GLOBALS["FTPM_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.ftpm{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;margin-top:8px}
.ftpm-hero{position:relative;display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px 24px 20px 28px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.ftpm-hero:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:$accent}
.ftpm-hero-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:20px}
.ftpm-hero-body{flex:1;min-width:0}
.ftpm-hero h3{margin:0 0 6px 0;font-size:18px;font-weight:600}
.ftpm-hero p{margin:0;font-size:14px;line-height:1.6;color:#475569;max-width:820px}
.ftpm-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.ftpm-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid $line;background:$soft;border-radius:999px;padding:3px 11px;font-size:12px;color:#334155}
.ftpm-chip i,.ftpm-chip svg{color:$accent}
.ftpm-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.ftpm-tile{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.06);min-width:0}
.ftpm-tile[role=button]{cursor:pointer}
.ftpm-tile[role=button]:focus-visible{outline:2px solid $accent;outline-offset:2px}
.ftpm-tile-main{min-width:0}
.ftpm-tile-l{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.ftpm-tile-v{margin-top:6px;font-size:26px;font-weight:600;line-height:1.15;color:#0f172a}
.ftpm-tile-v small{font-size:15px;font-weight:500;color:#64748b}
.ftpm-tile-v-sm{font-size:19px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ftpm-tile-s{margin-top:4px;font-size:12px;color:#64748b}
.ftpm-tile-ico{flex:0 0 40px;height:40px;border-radius:10px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center}
.ftpm-warn .ftpm-tile-v{color:#b45309}
.ftpm-warn .ftpm-tile-ico{background:#fffbeb;color:#b45309}
.ftpm-ring{flex:0 0 52px;width:52px;height:52px}
.ftpm-ring-bg{fill:none;stroke:#e2e8f0;stroke-width:4}
.ftpm-ring-fg{fill:none;stroke:$accent;stroke-width:4;stroke-linecap:round}
.ftpm-ring text{font-size:9px;font-weight:700;fill:#0f172a}
.ftpm-muted{color:#94a3b8;font-weight:500}
.ftpm-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.ftpm-card-h{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:16px 22px;border-bottom:1px solid #e2e8f0}
.ftpm-card-h h3{margin:0;font-size:17px;font-weight:600;display:flex;align-items:center;gap:10px}
.ftpm-count{display:inline-block;border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:1px 10px;font-size:12px;font-weight:600;color:#334155}
.ftpm-tools{display:flex;flex-wrap:wrap;align-items:center;gap:10px}
.ftpm-search{display:flex;align-items:center;gap:8px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;height:38px;width:280px;margin:0;color:#94a3b8;background:#fff}
.ftpm-search input{border:0;outline:0;flex:1;min-width:0;font-size:14px;color:#0f172a;background:transparent;height:36px}
.ftpm-search:focus-within{border-color:$accent;box-shadow:0 0 0 2px $soft}
.ftpm-seg{display:flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:3px}
.ftpm-seg button{border:0;background:transparent;border-radius:6px;padding:5px 12px;font-size:13px;color:#475569;cursor:pointer;white-space:nowrap}
.ftpm-seg button[aria-checked=true]{background:#fff;color:#0f172a;font-weight:600;box-shadow:0 1px 2px rgba(15,23,42,.1)}
.ftpm-seg button:focus-visible{outline:2px solid $accent}
.ftpm-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:8px 16px;font-size:14px;font-weight:500;color:#334155;cursor:pointer;white-space:nowrap;text-decoration:none}
.ftpm-btn:hover{background:#f8fafc;color:#0f172a;text-decoration:none}
.ftpm-btn:focus-visible{outline:2px solid $accent;outline-offset:2px}
.ftpm-btn-primary{background:$accent;border-color:$accent;color:#fff;font-weight:600}
.ftpm-btn-primary:hover{background:$dark;border-color:$dark;color:#fff}
.ftpm-tablewrap{overflow-x:auto}
.ftpm-table{width:100%;border-collapse:collapse;color:#0f172a}
.ftpm-table th{background:#f8fafc;text-align:left;font-size:13px;font-weight:600;color:#475569;padding:12px 22px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.ftpm-table td{padding:12px 22px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:14px}
.ftpm-table tbody tr:hover td{background:#f8fafc}
.ftpm-table tbody tr:last-child td{border-bottom:0}
.ftpm-num{text-align:right;font-family:monospace;font-size:13px}
.ftpm-table th.ftpm-num{font-family:inherit}
.ftpm-actions{text-align:right;white-space:nowrap;width:1%}
.ftpm-acc{display:flex;align-items:center;gap:12px;cursor:pointer}
.ftpm-acc strong{font-weight:600}
.ftpm-av{flex:0 0 38px;width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
.ftpm-av0{background:#d1fae5;color:#047857}.ftpm-av1{background:#dbeafe;color:#1d4ed8}.ftpm-av2{background:#e2e8f0;color:#334155}.ftpm-av3{background:#ccddda;color:#005447}
.ftpm-av4{background:#ccfbf1;color:#0f766e}.ftpm-av5{background:#ccddda;color:#005447}.ftpm-av6{background:#fee2e2;color:#b91c1c}.ftpm-av7{background:#fef3c7;color:#b45309}
.ftpm-path{display:inline-block;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:3px 9px;font-family:monospace;font-size:13px;color:#334155;overflow-wrap:anywhere}
.ftpm-sub{font-size:12px;color:#64748b}
.ftpm-ago{text-decoration:underline dotted #94a3b8;text-underline-offset:3px;cursor:help}
.ftpm-badge{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:2px 11px;font-size:12px;color:#475569;white-space:nowrap}
.ftpm-ibtn{border:0;background:transparent;color:#94a3b8;width:32px;height:32px;border-radius:8px;cursor:pointer}
.ftpm-ibtn:hover{background:#f1f5f9;color:#334155}
.ftpm-ibtn-del:hover{background:#fef2f2;color:#b91c1c}
.ftpm-ibtn:focus-visible{outline:2px solid $accent}
.ftpm-ibtn[disabled]{color:#cbd5e1;cursor:not-allowed}
.ftpm-ibtn[disabled]:hover{background:transparent;color:#cbd5e1}
.ftpm-acc-ro{cursor:default}
.ftpm-meta{display:inline-flex;align-items:center;gap:5px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:999px;padding:2px 9px;font-size:12px;font-weight:600;color:#1d4ed8;white-space:nowrap}
.ftpm-meta i,.ftpm-meta svg{color:#1d4ed8}
.ftpm-off{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.ftpm-meta-banner{display:flex;gap:12px;align-items:flex-start;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;font-size:13px;line-height:1.6;color:#1e3a8a}
.ftpm-meta-banner i,.ftpm-meta-banner svg{color:#1d4ed8;margin-top:3px}
.ftpm-nomatch{padding:28px;text-align:center;color:#64748b}
.ftpm-foot{display:flex;justify-content:space-between;padding:12px 22px;border-top:1px solid #e2e8f0;font-size:13px;color:#64748b}
.ftpm-empty{display:flex;flex-direction:column;align-items:center;text-align:center;padding:44px 24px;gap:10px}
.ftpm-empty-ico{width:72px;height:72px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:28px}
.ftpm-empty h4{margin:6px 0 0 0;font-size:17px;font-weight:600}
.ftpm-empty p{margin:0;color:#475569;max-width:520px;line-height:1.6}
.ftpm-example{margin-top:8px;font-size:13px;color:#64748b}
.ftpm-alert{display:flex;gap:12px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;color:#b91c1c}
@media (max-width:1200px){.ftpm-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.ftpm-search{width:220px}}
@media (prefers-reduced-motion:no-preference){.ftpm-card,.ftpm-tile,.ftpm-hero{animation:ftpmIn .15s ease-out}}
@keyframes ftpmIn{from{opacity:0}to{opacity:1}}
</style>";
}
