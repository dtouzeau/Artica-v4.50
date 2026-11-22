<?php
// Post SP10 - 13/10/2026
// Stitch redesign "active_directory_member_browser" (2026-11-18): value banner, one tab per
// domain (readable name + domain controllers), search card with People | Groups, result
// sentence, cap notice, kind chips filtered client-side, initials avatars, invitations for
// every empty state. Read-only: nothing is written to the directory from this page.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
// awc_table_style(): shared entity-table sheet (kept for the awc-* classes still used here)
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["MEMBERS_SEARCH"])){$_SESSION["MEMBERS_SEARCH"]="";}

// The "new member / new group / new organization" handlers and BuildRequests() were removed
// on 2026-11-18: no button led to them any more, and BuildRequests() built a hostsnet SQL
// query that nothing called.
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs-multi"])){tabs_multi();exit;}
if(isset($_GET["form-multi"])){form_multi();exit;}

page();

// ── Data ─────────────────────────────────────────────────────────────────────

function adm_privs():bool{
    $users=new usersMenus();
    if($users->AllowAddGroup OR $users->AllowAddUsers) {return true;}
    return false;
}

/** "DC=corp,DC=local" -> "corp.local"; anything else is returned as is. */
function adm_domain_name($suffix):string{
    $suffix=trim((string)$suffix);
    if($suffix===""){return "";}
    if(!preg_match('/dc=/i',$suffix)){return $suffix;}
    $parts=array();
    foreach(explode(",",$suffix) as $p){
        $p=trim($p);
        if(preg_match('/^dc=(.+)$/i',$p,$re)){$parts[]=strtolower(trim($re[1]));}
    }
    return count($parts)>0?implode(".",$parts):$suffix;
}

/** The configured connections, indexed by connection id. */
function adm_connections():array{
    $c=unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    if(!is_array($c)){$c=array();}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADLicenseRemoved"))==1){$c=array();}
    return $c;
}

/** Kerberos authentication gives a "Default (Kerberos)" domain on connection 0. */
function adm_kerberos():bool{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"))==1){return true;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"))==1){return true;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"))==1){return true;}
    return false;
}

/** The browsable domains: id => array(label, kerberos). */
function adm_domains():array{
    $out=array();
    if(adm_kerberos()){ $out[0]=array("label"=>"{adm_default_kerberos}","kerberos"=>true); }
    foreach(adm_connections() as $id=>$ligne){
        if(!is_numeric($id)){continue;}
        $suffix=isset($ligne["LDAP_SUFFIX"])?(string)$ligne["LDAP_SUFFIX"]:"";
        if(strlen($suffix)<3){continue;}
        $out[intval($id)]=array("label"=>htmlspecialchars(adm_domain_name($suffix)),"kerberos"=>false);
    }
    return $out;
}

function CheckHaCluster(){
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==0){return;}
    $haClusterAD=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    if(!is_array($haClusterAD)){return;}
    $array["LDAP_DN"]=$haClusterAD["KerberosUsername"] ?? "";
    $array["LDAP_SUFFIX"]=trim((string)($haClusterAD["kerberosActiveDirectorySuffix"] ?? ""));
    $array["LDAP_SERVER"]=$haClusterAD["kerberosActiveDirectoryHost"] ?? "";
    $array["LDAP_SERVER2"]=$haClusterAD["kerberosActiveDirectory2Host"] ?? "";
    $array["LDAP_PASSWORD"]=$haClusterAD["KerberosPassword"] ?? "";

    $ActiveDirectoryConnections=unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}
    if(!isset($ActiveDirectoryConnections[0]["LDAP_SERVER"])){
        $ActiveDirectoryConnections[0]=$array;
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($ActiveDirectoryConnections), "ActiveDirectoryConnections");
    }
}

// ── Style & shared widgets ───────────────────────────────────────────────────

function adm_style():string{
    if(!empty($GLOBALS["ADM_CSS_DONE"])){return "";}
    $GLOBALS["ADM_CSS_DONE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0a8059";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
    }
    return "<style>
.adm{margin-top:14px}
.adm-hero{display:flex;gap:20px;align-items:flex-start;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.06);margin-bottom:20px}
.adm-hero .ak-ico{background:$soft}
.adm-hero-t{font-size:20px;font-weight:700;color:#111d23;margin:0 0 6px 0;line-height:1.3}
.adm-hero-p{font-size:14px;color:#3d4947;line-height:1.55;margin:0 0 14px 0;max-width:980px}
.adm-hero .ak-tag{border-radius:999px;padding:5px 12px}
.adm-tabs .nav-tabs>li:last-child{float:right}
.adm-dc{font-size:13px;color:#6d7a77;margin:0 4px 14px 4px}
.adm-dc strong{color:#3d4947;font-weight:600}
.adm-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.adm-search{padding:20px 24px;margin-bottom:20px}
.adm-search-row{display:flex;gap:16px;align-items:center}
.adm-field{position:relative;flex:1 1 auto;min-width:0}
.adm-field>i,.adm-field>svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#6d7a77;font-size:14px}
.adm-field input{width:100%;height:44px;border:1px solid #cbd5e1;border-radius:8px;padding:0 40px 0 40px;font-size:15px;color:#111d23;background:#fff;outline:0;box-sizing:border-box}
.adm-field input:focus{border-color:$dark;box-shadow:0 0 0 2px $soft}
.adm-clear{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#6d7a77;width:28px;height:28px;border-radius:6px;cursor:pointer;display:none}
.adm-clear:hover{background:#f1f5f9;color:#111d23}
.adm-field input:not(:placeholder-shown)~.adm-clear{display:block}
.adm-seg{display:inline-flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:3px;flex:0 0 auto}
.adm-seg button{border:0;background:transparent;border-radius:6px;padding:8px 16px;font-size:14px;font-weight:600;color:#475569;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.adm-seg button[aria-pressed=true]{background:#fff;color:#111d23;box-shadow:0 1px 2px rgba(15,23,42,.12)}
.adm-go{flex:0 0 auto;height:44px;border:0;border-radius:8px;background:$dark;color:#fff;font-size:15px;font-weight:600;padding:0 22px;display:inline-flex;align-items:center;gap:8px;cursor:pointer}
.adm-go:hover{filter:brightness(.95)}
.adm-seg button:focus-visible,.adm-go:focus-visible,.adm-chip:focus-visible,.adm-clear:focus-visible{outline:2px solid $dark;outline-offset:2px}
.adm-hint{font-size:13px;color:#6d7a77;margin:10px 0 0 2px}
.adm-hint code,.adm-hint .adm-kbd{font-family:monospace;background:#f1f5f9;border-radius:4px;padding:1px 6px;color:#111d23}
.adm-res-h{padding:18px 24px 10px 24px}
.adm-res-t{font-size:15px;font-weight:600;color:#111d23;margin:0}
.adm-cap{margin:12px 0 0 0;display:flex;gap:8px;align-items:flex-start;background:#ffddb9;color:#865300;border-radius:8px;padding:10px 14px;font-size:13px;font-weight:500}
.adm-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
.adm-chip{border:1px solid #cbd5e1;background:#fff;color:#3d4947;border-radius:999px;padding:5px 14px;font-size:13px;font-weight:600;cursor:pointer}
.adm-chip[aria-pressed=true]{background:#111d23;border-color:#111d23;color:#fff}
.adm-tbl{width:100%;border-collapse:collapse}
.adm-tbl th{font-size:13px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#475569;padding:12px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;text-align:left;white-space:nowrap}
.adm-tbl td{padding:12px 24px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:14px;color:#111d23}
.adm-tbl tbody tr:hover{background:#f8fafc}
.adm-tbl td.adm-nm{max-width:0;width:34%}
.adm-id{display:flex;align-items:center;gap:12px;min-width:0}
.adm-av{flex:0 0 36px;width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;background:$soft;color:#00504a}
.adm-av-off{background:#e3f0f8;color:#3d4947}
.adm-av-sq{border-radius:8px}
.adm-id-t{min-width:0}
.adm-name{font-size:15px;font-weight:700;color:#111d23;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.adm-name a{color:$dark;text-decoration:none}
.adm-name a:hover{text-decoration:underline}
.adm-sub{font-size:13px;color:#6d7a77;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.adm-sub em{font-style:italic}
.adm-kind{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:13px;font-weight:600;white-space:nowrap}
.adm-kind-person{background:$soft;color:#00504a}
.adm-kind-other{background:#e3f0f8;color:#3d4947}
.adm-dash{color:#94a3b8}
.adm-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 24px;font-size:13px;color:#6d7a77}
.adm-pg{display:flex;gap:6px}
.adm-pg a{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#111d23;text-decoration:none;font-size:13px;font-weight:600}
.adm-pg a.cur{background:$dark;border-color:$dark;color:#fff}
.adm-pg a.off{color:#cbd5e1;pointer-events:none}
.adm-state{padding:44px 24px;text-align:center}
.adm-state-i{font-size:34px;color:#6d7a77;margin-bottom:12px}
.adm-state-t{font-size:16px;font-weight:700;color:#111d23;margin:0 0 6px 0}
.adm-state-p{font-size:14px;color:#3d4947;margin:0 auto 16px auto;max-width:520px}
.adm-state .ak-btn+.ak-btn{margin-left:8px}
.adm-err{display:flex;gap:14px;align-items:flex-start;background:#ffdad6;border:1px solid #f5b5ae;border-radius:12px;padding:20px 24px;color:#3d1110}
.adm-err-t{font-size:16px;font-weight:700;color:#ba1a1a;margin:0 0 4px 0}
.adm-err-p{font-size:14px;margin:0 0 12px 0}
.adm-err>i,.adm-err>svg{font-size:22px;color:#ba1a1a;margin-top:2px}
@media(max-width:1200px){.adm-search-row{flex-wrap:wrap}.adm-field{flex-basis:100%}}
</style>";
}

function adm_hero():string{
    return "<div class='adm-hero'>"
        ."<span class='ak-ico ak-ico-lg ak-accent'><i class='fas fa-address-book'></i></span>"
        ."<div class='ak-grow'><div class='adm-hero-t'>{adm_hero_title}</div>"
        ."<p class='adm-hero-p'>{adm_hero_text}</p>"
        ."<div class='ak-chips'>"
        ."<span class='ak-tag ak-ok'><i class='fas fa-eye'></i> {adm_chip_readonly}</span>"
        ."<span class='ak-tag ak-ok'><i class='fas fa-shield-halved'></i> {adm_chip_account}</span>"
        ."<span class='ak-tag ak-ok'><i class='fas fa-magnifying-glass'></i> {adm_chip_search}</span>"
        ."</div></div></div>";
}

/** A centred invitation (no search yet, no result, no domain, no privilege). */
function adm_state($ico,$title,$text,$buttons=""):string{
    return "<div class='adm-state'><div class='adm-state-i'><i class='$ico'></i></div>"
        ."<p class='adm-state-t'>$title</p><p class='adm-state-p'>$text</p>$buttons</div>";
}

function adm_settings_button($label,$ico,$class="ak-btn"):string{
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){return "";}
    return "<a class='$class' href=\"javascript:blur();\" OnClick=\"LoadAjaxSilent('MainContent','fw.system.activedirectory.ldap.php');\">"
        ."<i class='$ico'></i> $label</a>";
}

// ── Page, tabs, search card ──────────────────────────────────────────────────

function page(){
    CheckHaCluster();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{my_members}: Active Directory","fad fa-users","{adm_explain}",
        "$page?tabs-multi=yes","ad-members","progress-firehol-restart",false,"mytabsadusers");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{my_members}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function adm_tiny():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    $topbuttons[]=array("LoadAjax('mytabsadusers','$page?tabs-multi=yes')",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{my_members}: Active Directory";
    $TINY["ICO"]="fad fa-users";
    $TINY["EXPL"]="{adm_explain}";
    $TINY["URL"]="ad-members";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
}

function tabs_multi(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(!adm_privs()){
        echo adm_style().$tpl->_ENGINE_parse_body("<div class='ak adm'><div class='adm-card'>"
            .adm_state("fas fa-lock","{adm_noprivs_title}","{adm_noprivs_text}")."</div></div>").adm_tiny();
        return false;
    }

    $domains=adm_domains();
    $h=array();
    $h[]="<div class='ak adm'>";
    $h[]=adm_hero();
    if(count($domains)==0){
        $h[]="<div class='adm-card'>".adm_state("fas fa-link-slash","{adm_nodomain_title}","{adm_nodomain_text}",
            adm_settings_button("{adm_connect_ad}","fas fa-plus","ak-btn ak-btn-primary"))."</div>";
        $h[]="</div>";
        echo adm_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).adm_tiny();
        return true;
    }
    $h[]="</div>";

    $array=array();
    foreach($domains as $id=>$d){
        $array["<i class='fas fa-building'></i> ".$d["label"]]="$page?form-multi=yes&connection-id=$id";
    }
    $array["<i class='fas fa-key'></i> {privileges_list}"]="fw.members.privileges.php";
    echo adm_style().$tpl->_ENGINE_parse_body(implode("\n",$h))
        ."<div class='adm-tabs'>".$tpl->tabs_default($array)."</div>".adm_tiny();
    return true;
}

/** "Domain controllers: dc1 · dc2 — read with svc@corp" (the password is never shown). */
function adm_dc_line($connection_id):string{
    $c=adm_connections();
    if(!isset($c[$connection_id]) || !is_array($c[$connection_id])){return "";}
    $l=$c[$connection_id];
    $dcs=array();
    foreach(array("LDAP_SERVER","LDAP_SERVER2") as $k){
        $v=trim((string)($l[$k] ?? ""));
        if($v!==""){$dcs[]="<strong>".htmlspecialchars($v)."</strong>";}
    }
    if(count($dcs)==0){return "";}
    $s="{adm_dcs} ".implode(" · ",$dcs);
    $dn=trim((string)($l["LDAP_DN"] ?? ""));
    if($dn!==""){$s.=" — {adm_read_with} <strong>".htmlspecialchars($dn)."</strong>";}
    return "<p class='adm-dc'><i class='fas fa-server'></i> $s</p>";
}

function form_multi(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["connection-id"] ?? 0);
    $fn="AdmSearch$id";
    $field="adm-q-$id";
    $memo=htmlspecialchars((string)($_SESSION["MEMBERS_SEARCH"] ?? ""),ENT_QUOTES);
    $memoType=intval($_SESSION["MEMBERS_SEARCH_TYPE"] ?? 1)==0?0:1;

    $h=array();
    $h[]="<div class='ak adm' style='margin-top:12px'>";
    $h[]=adm_dc_line($id);
    $h[]="<div class='adm-card adm-search'><div class='adm-search-row'>";
    $h[]="<label class='adm-field'><span class='sr-only'>{adm_search_label}</span><i class='fas fa-magnifying-glass'></i>"
        ."<input type='text' id='$field' value=\"$memo\" placeholder='{adm_placeholder}' autocomplete='off' "
        ."OnKeyPress=\"{$fn}Key(event);\">"
        ."<button type='button' class='adm-clear' aria-label='{clear}' OnClick=\"{$fn}Clear();\"><i class='fas fa-xmark'></i></button></label>";
    $h[]="<div class='adm-seg' role='group' aria-label='{adm_view}'>"
        ."<button type='button' id='adm-t1-$id' aria-pressed='".($memoType==1?"true":"false")."' OnClick=\"{$fn}Type(1);\"><i class='fas fa-user'></i> {adm_people}</button>"
        ."<button type='button' id='adm-t0-$id' aria-pressed='".($memoType==0?"true":"false")."' OnClick=\"{$fn}Type(0);\"><i class='fas fa-users'></i> {adm_groups}</button></div>";
    $h[]="<button type='button' class='adm-go' OnClick=\"{$fn}();\"><i class='fas fa-magnifying-glass'></i> {search}</button>";
    $h[]="</div><p class='adm-hint'>{adm_hint}</p></div>";
    $h[]="<div id='adm-res-$id'></div>";
    $h[]="</div>";

    // Script concatenated after the parse: its braces would be read as {tokens}.
    $js=array();
    $js[]="var AdmType$id=$memoType;";
    $js[]="function {$fn}Key(e){if(checkEnter(e)){{$fn}();}}";
    $js[]="function {$fn}Type(t){AdmType$id=t;"
        ."document.getElementById('adm-t1-$id').setAttribute('aria-pressed',t==1?'true':'false');"
        ."document.getElementById('adm-t0-$id').setAttribute('aria-pressed',t==0?'true':'false');"
        ."if(document.getElementById('adm-res-$id').getAttribute('data-done')){{$fn}();}}";
    $js[]="function {$fn}Clear(){var f=document.getElementById('$field');if(!f){return;}f.value='';f.focus();}";
    $js[]="function {$fn}All(t){var f=document.getElementById('$field');if(f){f.value='';}{$fn}Type(t);{$fn}();}";
    $js[]="function {$fn}Focus(){var f=document.getElementById('$field');if(f){f.focus();f.select();}}";
    $js[]="function {$fn}(){var r=document.getElementById('adm-res-$id');r.setAttribute('data-done','1');"
        ."var q=encodeURIComponent(document.getElementById('$field').value);"
        ."LoadAjax('adm-res-$id','$page?table=yes&type='+AdmType$id+'&search='+q+'&connection-id=$id');}";
    if($memo!==""){
        $js[]="$fn();";
        $start="";
    }else{
        $start="<div class='adm-card'>".adm_state("fas fa-magnifying-glass","{adm_start_title}","{adm_start_text}",
            "<a class='ak-btn' href=\"javascript:blur();\" OnClick=\"{$fn}All(0);\"><i class='fas fa-users'></i> {adm_browse_groups}</a>"
            ."<a class='ak-btn' href=\"javascript:blur();\" OnClick=\"{$fn}All(1);\"><i class='fas fa-list'></i> {adm_list_everyone}</a>")."</div>";
    }
    $js[]="NoSpinner();";

    $out=$tpl->_ENGINE_parse_body(implode("\n",$h));
    if($start!==""){
        $out=str_replace("<div id='adm-res-$id'></div>","<div id='adm-res-$id'>".$tpl->_ENGINE_parse_body($start)."</div>",$out);
    }
    echo adm_style().$out."<script>".implode("\n",$js)."</script>";
}

// ── Results ──────────────────────────────────────────────────────────────────

/** Escape a value for an LDAP filter (RFC 4515), keeping * as the wildcard. */
function adm_ldap_filter_escape($s):string{
    $s=(string)$s;
    $s=str_replace("\\","\\5c",$s);
    $s=str_replace("(","\\28",$s);
    $s=str_replace(")","\\29",$s);
    $s=str_replace("\x00","\\00",$s);
    $s=str_replace("|","",$s);
    $s=str_replace("&","",$s);
    return $s;
}

/** What the operator typed, cleaned (shown back in the result sentence). */
function adm_typed($tpl):string{
    if(!isset($_GET["search"])){return "";}
    return trim($tpl->CLEAN_BAD_XSS(url_decode_special_tool($_GET["search"])));
}

function adm_search_string($typed):string{
    $v="*".adm_ldap_filter_escape($typed)."*";
    while(strpos($v,"**")!==false){$v=str_replace("**","*",$v);}
    return $v;
}

function adm_suffix($ligne):string{
    $ysuffix=strtolower((string)($ligne["SUFFIX"] ?? ""));
    $ysuffix=str_replace(array("dc=","ou=","cn="),"",$ysuffix);
    return @implode(".",explode(",",$ysuffix));
}

function adm_attr($ligne,$key):string{
    if(!is_array($ligne) || !isset($ligne[$key])){return "";}
    $v=$ligne[$key];
    if(is_array($v)){return isset($v[0])?(string)$v[0]:"";}
    return (string)$v;
}

function adm_initials($name):string{
    $name=trim(preg_replace('/[^\p{L}\p{N} ]+/u',' ',(string)$name));
    if($name===""){return "?";}
    $w=preg_split('/\s+/',$name);
    $a=function_exists("mb_substr")?mb_substr($w[0],0,1,"UTF-8"):substr($w[0],0,1);
    $b="";
    if(count($w)>1){$b=function_exists("mb_substr")?mb_substr($w[count($w)-1],0,1,"UTF-8"):substr($w[count($w)-1],0,1);}
    elseif(strlen($w[0])>1){$b=function_exists("mb_substr")?mb_substr($w[0],1,1,"UTF-8"):substr($w[0],1,1);}
    $s=$a.$b;
    return htmlspecialchars(function_exists("mb_strtoupper")?mb_strtoupper($s,"UTF-8"):strtoupper($s));
}

function adm_dash():string{ return "<span class='adm-dash'>—</span>"; }

/**
 * The search, run once: rows plus the facts the header needs.
 * kind: person | computer | service. error: set only when nothing came back AND the
 * connection itself failed, so "unreachable" never hides a real empty result.
 */
function adm_model($type,$id,$typed):array{
    $m=array("rows"=>array(),"raw"=>0,"cap"=>($type==0?50:200),"error"=>"","server"=>"",
             "counts"=>array("person"=>0,"computer"=>0,"service"=>0));
    $ad=new external_ad_search(null,$id);
    $hash=($type==0)?$ad->AllGroups(adm_search_string($typed),50):$ad->AllUsers(adm_search_string($typed),200);
    if(!is_array($hash)){$hash=array();}
    $m["raw"]=count($hash);
    $m["server"]=(string)($ad->KerbAuthInfos["LDAP_SERVER"] ?? "");
    if(count($hash)==0 && preg_match('/^Failed to/',(string)$ad->error)){ $m["error"]=(string)$ad->error; }
    foreach($hash as $dn=>$ligne){
        if($type==1 && strpos($dn,"dc=pureftpd,dc=organizations")>0){continue;}
        $row=($type==0)?adm_group_entry($dn,$ligne):adm_user_entry($dn,$ligne);
        if($row===null){continue;}
        if($type==1){$m["counts"][$row["kind"]]++;}
        $m["rows"][]=$row;
    }
    return $m;
}

function adm_group_entry($dn,$ligne):?array{
    $name=adm_attr($ligne,"samaccountname");
    if($name===""){$name=adm_attr($ligne,"cn");}
    if($name===""){$name=(string)$dn;}
    $members=0;
    if(isset($ligne["member"]["count"])){$members=intval($ligne["member"]["count"]);}
    return array("dn"=>$dn,"name"=>$name,"description"=>adm_attr($ligne,"description"),
                 "domain"=>adm_suffix($ligne),"members"=>$members);
}

function adm_user_entry($dn,$ligne):?array{
    if(!is_array($ligne)){return null;}
    $uid=adm_attr($ligne,"samaccountname");
    $objectclass=(isset($ligne["objectclass"]) && is_array($ligne["objectclass"]))?$ligne["objectclass"]:array();
    $description=adm_attr($ligne,"description");
    $e=array("dn"=>$dn,"login"=>$uid,"description"=>$description,"domain"=>adm_suffix($ligne),
             "mail"=>adm_attr($ligne,"mail"),"upn"=>adm_attr($ligne,"userprincipalname"),
             "phone"=>adm_attr($ligne,"telephonenumber"),"mobile"=>adm_attr($ligne,"mobile"));
    if(in_array("computer",$objectclass)){
        $e["kind"]="computer";
        $e["name"]=preg_replace('/\$$/','',$uid);
        return $e;
    }
    if($uid===""){return null;}
    $given=adm_attr($ligne,"givenname");$sn=adm_attr($ligne,"sn");
    $name=($given!=="" && $sn!=="")?"$given $sn":trim(adm_attr($ligne,"displayname"));
    if($name===""){$name=$uid;}
    $e["name"]=$name;
    $e["kind"]=(substr($uid,-1)==='$' || substr($name,-1)==='$')?"service":"person";
    return $e;
}

function adm_user_row($e):string{
    $kind=$e["kind"];
    $isPerson=($kind=="person");
    $name=htmlspecialchars($e["name"]);
    if($isPerson){
        $name="<a href=\"javascript:blur();\" OnClick=\"Loadjs('fw.member.ad.edit.php?dn=".urlencode($e["dn"])."')\">$name</a>";
    }
    $sub=array();
    if($e["login"]!=="" && $e["login"]!==$e["name"]){$sub[]=htmlspecialchars(preg_replace('/\$$/','',$e["login"]));}
    if($e["description"]!==""){$sub[]=htmlspecialchars($e["description"]);}
    $subHtml=implode(" · ",$sub);
    if(!$isPerson){$subHtml.=($subHtml===""?"":" — ")."<em>{adm_no_card}</em>";}
    $kinds=array("person"=>array("adm-kind-person","fas fa-user","{adm_kind_person}"),
                 "computer"=>array("adm-kind-other","fas fa-desktop","{adm_kind_computer}"),
                 "service"=>array("adm-kind-other","fas fa-gears","{adm_kind_service}"));
    $k=$kinds[$kind];
    $mail=$e["mail"]!==""?$e["mail"]:$e["upn"];
    $mailHtml=$mail===""?adm_dash():"<div>".htmlspecialchars($mail)."</div>";
    if($e["mail"]!=="" && $e["upn"]!=="" && strcasecmp($e["mail"],$e["upn"])!=0){
        $mailHtml.="<div class='adm-sub'>".htmlspecialchars($e["upn"])."</div>";
    }
    $phoneHtml=$e["phone"]!==""?"<div>".htmlspecialchars($e["phone"])."</div>":"";
    if($e["mobile"]!==""){$phoneHtml.="<div class='adm-sub'>{adm_mobile} ".htmlspecialchars($e["mobile"])."</div>";}
    if($phoneHtml===""){$phoneHtml=adm_dash();}

    return "<tr data-kind='$kind'><td class='adm-nm'><div class='adm-id'>"
        ."<span class='adm-av".($isPerson?"":" adm-av-off")."'>".adm_initials($e["name"])."</span>"
        ."<div class='adm-id-t'><div class='adm-name' title=\"".htmlspecialchars($e["name"])."\">$name</div>"
        .($subHtml===""?"":"<div class='adm-sub'>$subHtml</div>")."</div></div></td>"
        ."<td><span class='adm-kind {$k[0]}'><i class='{$k[1]}'></i> {$k[2]}</span></td>"
        ."<td>".($e["domain"]!==""?htmlspecialchars($e["domain"]):adm_dash())."</td>"
        ."<td>$mailHtml</td><td>$phoneHtml</td></tr>";
}

function adm_group_row($e):string{
    $js="Loadjs('fw.groups.ad.php?dn=".urlencode($e["dn"])."&connection-id=".intval($_GET["connection-id"] ?? 0)."')";
    return "<tr data-kind='group'><td class='adm-nm'><div class='adm-id'>"
        ."<span class='adm-av adm-av-sq'><i class='fas fa-users'></i></span>"
        ."<div class='adm-id-t'><div class='adm-name' title=\"".htmlspecialchars($e["name"])."\">"
        ."<a href=\"javascript:blur();\" OnClick=\"$js\">".htmlspecialchars($e["name"])."</a></div>"
        .($e["domain"]!==""?"<div class='adm-sub'>".htmlspecialchars($e["domain"])."</div>":"")."</div></div></td>"
        ."<td>".($e["description"]!==""?htmlspecialchars($e["description"]):adm_dash())."</td>"
        ."<td style='text-align:right;white-space:nowrap'><strong>".number_format($e["members"])."</strong> ".($e["members"]==1?"{adm_member_word}":"{adm_members_word}")."</td></tr>";
}

/** Translate then fill %1, %2 (values already escaped). */
function adm_t($token,$args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    $s=preg_replace('#<br\s*/?>\s*$#i','',$s);
    for($i=count($args);$i>=1;$i--){$s=str_replace("%$i",(string)$args[$i-1],$s);}
    return $s;
}

function table():bool{
    $tpl=new template_admin();
    $type=intval($_GET["type"] ?? 1)==0?0:1;
    $id=intval($_GET["connection-id"] ?? 0);
    $fn="AdmSearch$id";
    $typed=adm_typed($tpl);
    $_SESSION["MEMBERS_SEARCH"]=$typed;
    $_SESSION["MEMBERS_SEARCH_TYPE"]=$type;
    $m=adm_model($type,$id,$typed);
    $n=count($m["rows"]);
    $q=htmlspecialchars($typed);

    if($m["error"]!==""){
        $srv=$m["server"]!==""?htmlspecialchars($m["server"]):"{adm_the_dc}";
        $html="<div class='ak'><div class='adm-err'><i class='fas fa-triangle-exclamation'></i><div>"
            ."<p class='adm-err-t'>{adm_unreach_title}</p>"
            ."<p class='adm-err-p'>".adm_t("adm_unreach_text",array("<strong>$srv</strong>"))."</p>"
            .adm_settings_button("{adm_open_settings}","fas fa-gear","ak-btn")
            ."</div></div></div><script>NoSpinner();</script>";
        echo adm_style().$tpl->_ENGINE_parse_body($html);
        return true;
    }

    if($n==0){
        $example=htmlspecialchars(rtrim(str_replace("*","",$typed),"*")===""?"mar":substr(str_replace("*","",$typed),0,2))."*";
        $title=($typed==="")?"{adm_empty_all}":adm_t("adm_nomatch_title",array("“".$q."”"));
        $text=adm_t("adm_nomatch_text",array("<span class='adm-kbd'>$example</span>"));
        $html="<div class='ak'><div class='adm-card'>".adm_state("fas fa-user-slash",$title,$text,
            "<a class='ak-btn' href=\"javascript:blur();\" OnClick=\"{$fn}Focus();\"><i class='fas fa-arrow-up'></i> {adm_refine}</a>")
            ."</div></div><script>NoSpinner();</script>";
        echo adm_style().$tpl->_ENGINE_parse_body($html);
        return true;
    }

    if($typed===""){
        $sentence=adm_t($type==0?"adm_found_groups_all":"adm_found_people_all",array(number_format($n)));
    }else{
        $sentence=adm_t($type==0?"adm_found_groups":"adm_found_people",array(number_format($n),"“".$q."”"));
    }

    $h=array();
    $h[]="<div class='ak'><div class='adm-card'>";
    $h[]="<div class='adm-res-h'><p class='adm-res-t'>$sentence</p>";
    if($m["raw"]>=$m["cap"]){
        $h[]="<div class='adm-cap'><i class='fas fa-triangle-exclamation'></i><span>"
            .adm_t($type==0?"adm_cap_groups":"adm_cap_people",array(number_format($m["cap"])))."</span></div>";
    }
    if($type==1){
        $chips=array(array("all","{adm_everyone}",$n),array("person","{adm_kind_people}",$m["counts"]["person"]),
                     array("computer","{adm_kind_computers}",$m["counts"]["computer"]),
                     array("service","{adm_kind_services}",$m["counts"]["service"]));
        $h[]="<div class='adm-chips' role='group' aria-label='{adm_filter_kind}'>";
        foreach($chips as $c){
            if($c[0]!="all" && $c[2]==0){continue;}
            $h[]="<button type='button' class='adm-chip' data-k='{$c[0]}' aria-pressed='".($c[0]=="all"?"true":"false")."'>{$c[1]} ({$c[2]})</button>";
        }
        $h[]="</div>";
    }
    $h[]="</div>";
    $h[]="<div style='overflow-x:auto'><table class='adm-tbl'><thead><tr>";
    if($type==1){
        $h[]="<th>{adm_col_name}</th><th>{adm_col_kind}</th><th>{adm_col_domain}</th><th>{adm_col_email}</th><th>{adm_col_phone}</th>";
    }else{
        $h[]="<th>{adm_col_group}</th><th>{adm_col_description}</th><th style='text-align:right'>{adm_col_members}</th>";
    }
    $h[]="</tr></thead><tbody id='adm-rows-$id'>";
    foreach($m["rows"] as $e){ $h[]=($type==1)?adm_user_row($e):adm_group_row($e); }
    $h[]="</tbody></table></div>";
    $h[]="<div class='adm-foot'><span id='adm-count-$id'></span><span class='adm-pg' id='adm-pager-$id'></span></div>";
    $h[]="</div></div>";

    $showing=$tpl->_ENGINE_parse_body("{adm_showing}");
    echo adm_style().$tpl->_ENGINE_parse_body(implode("\n",$h))
        ."<script>NoSpinner();</script>".adm_list_js($id,25,$showing);
    return true;
}

/**
 * Client-side kind filter + pager over the rendered rows (at most 200).
 * Single-quoted PHP string: the JS $ identifiers must not be interpolated.
 */
function adm_list_js($id,$size,$showing):string{
    $js='<script>
(function(){
 var tb=document.getElementById("adm-rows-__ID__"); if(!tb){return;}
 var pg=document.getElementById("adm-pager-__ID__"), ct=document.getElementById("adm-count-__ID__");
 var rows=Array.prototype.slice.call(tb.rows), size=__SIZE__, cur=1, kind="all", label=__SHOWING__;
 function vis(){ return rows.filter(function(r){ return kind=="all" || r.getAttribute("data-kind")==kind; }); }
 function draw(){
   var v=vis(), n=v.length, pages=Math.max(1,Math.ceil(n/size)); if(cur>pages){cur=pages;}
   rows.forEach(function(r){ r.style.display="none"; });
   v.forEach(function(r,i){ if(i>=(cur-1)*size && i<cur*size){ r.style.display=""; } });
   var a=n==0?0:(cur-1)*size+1, b=Math.min(n,cur*size);
   if(ct){ ct.textContent=label.replace("%1",a+"–"+b).replace("%2",n); }
   if(!pg){return;}
   if(pages<2){ pg.innerHTML=""; return; }
   var h="<a href=\"javascript:blur();\" data-p=\""+(cur-1)+"\" class=\""+(cur==1?"off":"")+"\" aria-label=\"Previous\">&lsaquo;</a>";
   var end=Math.min(pages,cur+2), start=Math.max(1,end-4); end=Math.min(pages,start+4);
   for(var p=start;p<=end;p++){ h+="<a href=\"javascript:blur();\" data-p=\""+p+"\" class=\""+(p==cur?"cur":"")+"\">"+p+"</a>"; }
   h+="<a href=\"javascript:blur();\" data-p=\""+(cur+1)+"\" class=\""+(cur==pages?"off":"")+"\" aria-label=\"Next\">&rsaquo;</a>";
   pg.innerHTML=h;
   Array.prototype.forEach.call(pg.getElementsByTagName("a"),function(x){
     x.onclick=function(){ var p=parseInt(this.getAttribute("data-p"),10); if(p>=1&&p<=pages&&p!=cur){cur=p;draw();} return false; };
   });
 }
 var box=tb.closest?tb.closest(".adm-card"):null;
 if(box){ Array.prototype.forEach.call(box.querySelectorAll(".adm-chip"),function(c){
   c.onclick=function(){
     Array.prototype.forEach.call(box.querySelectorAll(".adm-chip"),function(o){ o.setAttribute("aria-pressed","false"); });
     this.setAttribute("aria-pressed","true"); kind=this.getAttribute("data-k"); cur=1; draw();
   };
 }); }
 draw();
})();
</script>';
    return str_replace(array("__ID__","__SIZE__","__SHOWING__"),array(intval($id),intval($size),json_encode(strip_tags($showing))),$js);
}
