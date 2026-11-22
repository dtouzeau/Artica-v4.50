<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["upload-js"])){upload_js();exit;}
if(isset($_GET["upload-popup"])){upload_popup();exit;}
if(isset($_GET["file-uploaded"])){uploaded_js();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_confirm();exit;}
if(isset($_GET["cnx"])){rule_js();exit;}
if(isset($_GET["cnx-popup"])){rule_popup();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_POST["zmd5"])){rule_save();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["stop"])){stop();exit;}
if(isset($_GET["td-row"])){td_row(vpncl_md5($_GET["td-row"]));exit;}
if(isset($_GET["js-tiny"])){js_tiny();exit;}
if(isset($_GET["traffic-live"])){traffic_live();exit;}
page();

/**
 * Compteurs bruts des interfaces pour la courbe peity (appelée toutes les 5 s
 * par VpnClLiveTick). Le navigateur calcule le débit par différence entre deux
 * appels : les relevés du daemon ne sont qu'à la minute.
 */
function traffic_live():bool{
    header("content-type: application/json");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvpn/client/traffic?minutes=10"),true);
    $out=array();
    if(is_array($json) && !empty($json["Status"]) && isset($json["clients"]) && is_array($json["clients"])){
        foreach ($json["clients"] as $zmd5=>$c){
            $zmd5=vpncl_md5($zmd5);
            if($zmd5==""){continue;}
            $out[$zmd5]=array("up"=>!empty($c["up"]),
                "rx"=>floatval($c["rx_bytes"] ?? 0),"tx"=>floatval($c["tx_bytes"] ?? 0));
        }
    }
    echo json_encode(array("t"=>microtime(true),"clients"=>(object)$out));
    return true;
}

/**
 * Un identifiant de connexion VPN tel qu'il transite par l'URL ou le formulaire.
 * C'est toujours un md5 (colonne zmd5, cle primaire de vpn_client): il partait
 * BRUT dans une douzaine de requetes SQLite.
 */
function vpncl_md5($v):string{
    return preg_replace('/[^a-f0-9]/','',(string)$v);
}

/**
 * Le nom de la fonction JavaScript de rafraichissement. Il est reinjecte tel
 * quel dans des attributs OnClick et dans les URL des modales: il doit etre
 * restreint a l'alphabet que search_block produit.
 */
/**
 * Rafraichit les cartes apres une action (VpnClRefresh est definie par la
 * page ; ailleurs, l'appel ne fait rien).
 */
function vpncl_refresh_js($delay):string{
    $delay=intval($delay);
    return "setTimeout(function(){ if(typeof VpnClRefresh==='function'){ VpnClRefresh(); } }, $delay);";
}

function vpncl_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}


function rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $cnx=vpncl_md5($_GET["cnx"]);
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM vpn_client WHERE zmd5='$cnx'");
    $rulename=$ligne["rulename"];
    $function=vpncl_function();
    return $tpl->js_dialog2($rulename,"$page?cnx-popup=$cnx&function=$function");
}

function delete_js():bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $md5=vpncl_md5($_GET["delete"]);
    $md=vpncl_md5($_GET["md"]);
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM vpn_client WHERE zmd5='$md5'");
    $rulename=$ligne["rulename"];
    return $tpl->js_confirm_delete($rulename,"delete",$md5,"$('#$md').remove();".vpncl_refresh_js(500));
}
function delete_confirm():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $md5=vpncl_md5($_POST["delete"]);
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM vpn_client WHERE zmd5='$md5'");
    $rulename=$ligne["rulename"];
    $q->QUERY_SQL("DELETE FROM vpn_client WHERE zmd5='$md5'");
    $sock=new sockets();
    $sock->REST_API("/openvpn/client/uninstall/$md5");
    return admin_tracks("Delete OpenVPN Client connection $rulename");
}
function enable():bool{
    $page=CurrentPageName();
    $zmd5=vpncl_md5($_GET["enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $sock=new sockets();
    $ligne=$q->mysqli_fetch_array("SELECT rulename,enable FROM vpn_client WHERE zmd5='$zmd5'");
    $enable=intval($ligne["enable"]);
    $rulename=$ligne["rulename"];
    header("content-type: application/x-javascript");
    if($enable==0){
        $q->QUERY_SQL("UPDATE vpn_client SET enable=1 WHERE zmd5='$zmd5'");
        $sock->REST_API("/openvpn/client/enable/$zmd5");
        echo vpncl_refresh_js(0);
        return admin_tracks("Enable OpenVPN Client connection $rulename");
    }

    $q->QUERY_SQL("UPDATE vpn_client SET enable=0 WHERE zmd5='$zmd5'");
    $sock->REST_API("/openvpn/client/disable/$zmd5");
    echo vpncl_refresh_js(0);
    return admin_tracks("Disable OpenVPN Client connection $rulename");
}
function start():bool{
    $zmd5=vpncl_md5($_GET["start"]);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $sock=new sockets();
    $state=base64_encode($tpl->_ENGINE_parse_body("{starting}"));
    $sock->REST_API("/openvpn/client/start/$zmd5");
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename,enable FROM vpn_client WHERE zmd5='$zmd5'");
    $rulename=$ligne["rulename"];
    $f[]="if (document.getElementById('status-$zmd5') ){";
    $f[]="\tdocument.getElementById('status-$zmd5').innerHTML=base64_decode('$state');";
    $f[]="\tdocument.getElementById('status-$zmd5').className='ak-pill ak-warn';";
    $f[]="}";
    $f[]=vpncl_refresh_js(2500);
    $f[]=vpncl_refresh_js(8000);
    echo @implode("\n",$f);
    return admin_tracks("Starting OpenVPN Client connection $rulename");
}

function stop():bool{
    $zmd5=vpncl_md5($_GET["stop"]);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $sock=new sockets();
    $state=base64_encode($tpl->_ENGINE_parse_body("{stopping}"));
    $sock->REST_API("/openvpn/client/stop/$zmd5");
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename,enable FROM vpn_client WHERE zmd5='$zmd5'");
    $rulename=$ligne["rulename"];
    $f[]="if (document.getElementById('status-$zmd5') ){";
    $f[]="\tdocument.getElementById('status-$zmd5').innerHTML=base64_decode('$state');";
    $f[]="\tdocument.getElementById('status-$zmd5').className='ak-pill ak-warn';";
    $f[]="}";
    $f[]=vpncl_refresh_js(2500);
    $f[]=vpncl_refresh_js(8000);
    echo @implode("\n",$f);
    return admin_tracks("Stopping OpenVPN Client connection $rulename");
}
function rule_popup():bool{
    $tpl=new template_admin();
    $disableform=false;
    $function=vpncl_function();
    $cnx=vpncl_md5($_GET["cnx-popup"]);
    $Auth=false;
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename,username,password,details FROM vpn_client WHERE zmd5='$cnx'");

    $info=unserialize(base64_decode($ligne["details"]));
    $server="{$info["proto"]}://{$info["server"]}:{$info["port"]}";
    if($info["auth"]=="yes"){
        $Auth=true;
    }

    if(preg_match("#^[A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+$#",$ligne["username"])){
        $disableform=true;
    }
    if($ligne["debugmode"]==0){
        $ligne["debugmode"]=2;
    }
    $verb[2]="{normal}";
    for($i=3;$i<128;$i++){
        $verb[$i]=$i;
    }

    $form[]=$tpl->field_hidden("zmd5",$cnx);
    $form[]=$tpl->field_text("rulename", "{connection_name}", $ligne["rulename"]);
    $form[] = $tpl->field_text("username", "{username}", $ligne["username"]);
    $form[] = $tpl->field_password("password", "{password}", $ligne["password"]);
    $form[] = $tpl->field_array_hash($verb,"debugmode","{debug}",$ligne["debugmode"]);

    $html=$tpl->form_outside($server, $form,null,"{apply}","$function();dialogInstance2.close();","AsVPNManager",false,$disableform);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");

    if(!$q->FIELD_EXISTS("vpn_client","debugmode")){
        $q->QUERY_SQL("ALTER TABLE vpn_client ADD `debugmode` INTEGER NOT NULL DEFAULT 0");
    }

    $zmd5=vpncl_md5($_POST["zmd5"]);
    $upd["rulename"]=$_POST["rulename"];
    $upd["debugmode"]=$_POST["debugmode"];
    if(isset($_POST["username"])){
        $upd["username"]=$_POST["username"];
        $upd["password"]=$_POST["password"];
    }

    // Pentest 2026-11-04 : rulename/username/password/debugmode etaient concatenes dans la requete.
    $upd["debugmode"]=intval($upd["debugmode"]);
    $sqlparams=array();
    foreach ($upd as $key=>$val){
        $qq[]="`$key`=?";
        $sqlparams[]=$val;
    }
    $sqlparams[]=$zmd5;
    $q->QUERY_SQL_PARAMS("UPDATE vpn_client SET ".@implode(",",$qq)." WHERE zmd5=?",$sqlparams);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $sock=new sockets();
    $sock->REST_API("/openvpn/client/install/$zmd5");
    return admin_tracks("Updating VPN Client Connection {$_POST["rulename"]} ($zmd5)");
}

function td_row($zmd5):bool{


    list($StatusTool,$error)=td_status($zmd5);
    $tpl=new template_admin();
    $StatusTool=base64_encode($tpl->_ENGINE_parse_body($StatusTool));
    $error=base64_encode($tpl->_ENGINE_parse_body($error));
    header("content-type: application/x-javascript");
    $f[]="if (document.getElementById('status-$zmd5') ){";
    $f[]="\tdocument.getElementById('status-$zmd5').innerHTML=base64_decode('$StatusTool');";
    $f[]="}";
    $f[]="if (document.getElementById('error-$zmd5') ){";
    $f[]="\tdocument.getElementById('error-$zmd5').innerHTML=base64_decode('$error');";
    $f[]="}";

    echo @implode("\n",$f);
    return true;


}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");

    $html=$tpl->page_header("{vpn_client} v$OpenVPNVersion",
        "fa fa-compress","{vpn_client_explain}","$page?main-start=yes",
        "vpn-client","progress-vpnclient-restart",false,"table-vpnclient");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

/**
 * Squelette de la page : feuille de style, barre de recherche, conteneur des
 * cartes et carte d'ajout. Les cartes sont chargees par ?main=yes et
 * rechargees par VpnClRefresh() (recherche, actions, minuterie).
 */
function main_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=array();
    $html[]="<div class='vcl-page ak'>";
    $html[]=vpncl_toolbar();
    $html[]="<div id='vcl-list'></div>";
    $html[]=vpncl_add_card($page);
    $html[]="</div>";
    echo vpncl_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).vpncl_page_js($page);
    return true;
}

/** Accents de la marque (repli : les valeurs de serie). */
function vpncl_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

function vpncl_css():string{
    $ac=vpncl_accents();
    $D=$ac["accent_dark"]; $S=$ac["accent_soft"];
    $f[]="<style>";
    $f[]=".vcl-page{color:#0b1c30}";
    $f[]=".vcl-bar{display:flex;flex-wrap:wrap;align-items:center;gap:12px;background:#eff4ff;border-radius:12px;padding:12px 16px;margin-bottom:18px}";
    $f[]=".vcl-search{flex:1 1 320px;display:flex;gap:8px;align-items:center}";
    $f[]=".vcl-search .vcl-in{flex:1}";
    $f[]=".vcl-sum{display:flex;align-items:center;gap:18px;font-size:14px;font-weight:600;margin-left:auto}";
    $f[]=".vcl-sum .ak-dot{margin-right:6px;vertical-align:middle}";
    $f[]=".vcl-sum .vcl-total{color:#6c7a77;font-weight:500}";
    $f[]=".vcl-card{display:grid;grid-template-columns:minmax(280px,1.7fr) minmax(140px,.8fr) minmax(170px,.9fr) minmax(220px,1.4fr) auto;gap:20px;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px 20px;margin-bottom:14px}";
    $f[]=".vcl-card.vcl-off{background:#fbfcff}";
    $f[]=".vcl-id{display:flex;gap:14px;align-items:flex-start;min-width:0}";
    $f[]=".vcl-ico{position:relative;flex:0 0 52px;height:52px;border-radius:12px;background:$S;color:$D;display:flex;align-items:center;justify-content:center;font-size:20px}";
    $f[]=".vcl-ico.vcl-ico-err{background:#ffdad6;color:#ba1a1a}.vcl-ico.vcl-ico-off{background:#eef1f6;color:#6c7a77}";
    $f[]=".vcl-pip{position:absolute;top:-5px;right:-5px;width:14px;height:14px;border-radius:50%;border:2px solid #fff;background:$D}";
    $f[]=".vcl-pip.vcl-pip-err{background:#ba1a1a}.vcl-pip.vcl-pip-warn{background:#fea619}.vcl-pip.vcl-pip-off{background:#9ca4bd}";
    $f[]=".vcl-head{display:flex;flex-wrap:wrap;align-items:center;column-gap:8px;row-gap:4px;min-width:0}";
    $f[]=".vcl-name{font-size:19px;font-weight:700;letter-spacing:-.01em;line-height:24px;overflow-wrap:anywhere;min-width:0}";
    $f[]=".vcl-name a,.vcl-name a:hover,.vcl-name a:focus{color:#0b1c30;text-decoration:none}.vcl-name a:hover{color:$D}";
    $f[]=".vcl-nic{margin-top:6px;font-size:14px;color:#3c4947;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $f[]=".vcl-chip{margin-right:6px}";
    $f[]=".vcl-sub{margin-top:6px;font-size:13px;color:#565e74}";
    $f[]=".vcl-err{margin-top:6px;font-size:13px;color:#ba1a1a}";
    $f[]=".vcl-lbl{display:block;margin-bottom:6px}";
    $f[]=".vcl-val{font-size:14px;font-weight:600;letter-spacing:.02em;word-break:break-all}";
    $f[]=".vcl-note{margin-top:4px;font-size:12px;color:$D}.vcl-note.vcl-note-off{color:#6c7a77}.vcl-note.vcl-note-warn{color:#855300}";
    $f[]=".vcl-trf{min-width:0}";
    $f[]=".vcl-rates{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}";
    $f[]=".vcl-rate{font-size:15px;font-weight:700;color:$D;white-space:nowrap}.vcl-rate.vcl-up{color:#855300}.vcl-rate.vcl-zero{color:#9ca4bd;font-weight:500}";
    $f[]=".vcl-vol{font-size:13px;color:#3c4947;white-space:nowrap}";
    $f[]=".vcl-spark{display:block;width:100%;height:56px;margin-top:6px}";
    $f[]=".vcl-wait{font-size:12px;color:#6c7a77;margin-top:4px}";
    $f[]=".vcl-pkw{position:relative;overflow:hidden}.vcl-pk{position:absolute;left:0;top:0;right:0;bottom:0}.vcl-pk svg{display:block}";
    $f[]=".ak-act.vcl-act-stop{color:#ba1a1a}.ak-act.vcl-act-on{color:$D}";
    $f[]=".vcl-add{display:flex;align-items:center;gap:16px;background:#eff4ff;border-radius:16px;padding:20px 24px;margin-top:8px}";
    $f[]=".vcl-add .vcl-ico{background:#dce9ff}";
    $f[]=".vcl-add-t{font-size:19px;font-weight:700}.vcl-add-e{font-size:14px;color:#3c4947;margin-top:2px}";
    $f[]=".vcl-add .vcl-add-txt{flex:1}";
    $f[]="@media (max-width:1300px){.vcl-card{grid-template-columns:minmax(0,1.5fr) minmax(0,1fr) minmax(0,1fr)}.vcl-trf{grid-column:1 / 3}}";
    $f[]="@media (max-width:700px){.vcl-card{grid-template-columns:1fr}.vcl-add{flex-wrap:wrap}}";
    $f[]="</style>";
    return @implode("\n",$f);
}

/** Barre de recherche + compteurs (remplis par ?main=yes). */
function vpncl_toolbar():string{
    $ac=vpncl_accents();
    $f[]="<div class='vcl-bar' style='background-color: whitesmoke;'>";
    $f[]="<div class='vcl-search'><label class='ak-search vcl-in'><i class='fas fa-magnifying-glass'></i>";
    $f[]="<input type='text' id='vcl-q' placeholder='{vpncl_search_placeholder}' onkeydown=\"if(event.keyCode==13){VpnClRefresh();return false;}\"></label>";
    $f[]="<button type='button' class='ak-btn ak-btn-lg ak-btn-primary' OnClick=\"VpnClRefresh();\">{search}</button></div>";
    $f[]="<div class='vcl-sum'>";
    $f[]="<span><span class='ak-dot ak-ok'></span><span id='vcl-n-up'>0</span> {vpncl_connected}</span>";
    $f[]="<span><span class='ak-dot ak-err'></span><span id='vcl-n-down'>0</span> {offline}</span>";
    $f[]="<span class='vcl-total'><span id='vcl-n-total'>0</span> {vpncl_configured_tunnels}</span>";
    $f[]="</div></div>";
    return @implode("",$f);
}

/** Carte d'ajout d'une connexion (import du .zip exporte par le serveur). */
function vpncl_add_card($page):string{
    $users=new usersMenus();
    if(!$users->AsVPNManager){return "";}
    $f[]="<div class='vcl-add'>";
    $f[]="<div class='vcl-ico'><i class='fas fa-link'></i></div>";
    $f[]="<div class='vcl-add-txt'><div class='vcl-add-t'>{create_a_connection}</div>";
    $f[]="<div class='vcl-add-e'>{vpncl_add_explain}</div></div>";
    $f[]="<button type='button' class='ak-btn ak-btn-lg ak-btn-primary' OnClick=\"Loadjs('$page?upload-js=yes&function=VpnClRefresh');\"><i class='fas fa-file-import'></i> {vpncl_import}</button>";
    $f[]="</div>";
    return @implode("",$f);
}

/**
 * VpnClRefresh() recharge les cartes avec la recherche courante ; une
 * minuterie la rappelle toutes les 30 s tant que la page est affichee
 * (debits et etats), et s'arrete d'elle-meme quand on change de page.
 */
function vpncl_page_js($page):string{
    $f[]="<script>";
    $f[]="function VpnClRefresh(){";
    $f[]="  var q=document.getElementById('vcl-q');";
    $f[]="  var s=q?encodeURIComponent(q.value):'';";
    $f[]="  LoadAjaxSilent('vcl-list','$page?main=yes&function=VpnClRefresh&search='+s);";
    $f[]="}";
    $f[]="if(window.VpnClTimer){ clearInterval(window.VpnClTimer); }";
    $f[]="window.VpnClTimer=setInterval(function(){";
    $f[]="  if(!document.getElementById('vcl-list')){ clearInterval(window.VpnClTimer); window.VpnClTimer=null; return; }";
    $f[]="  if(document.querySelector('.modal.in')){ return; }";
    $f[]="  VpnClRefresh();";
    $f[]="},30000);";
    $f[]="VpnClRefresh();";
    $f[]="Loadjs('$page?js-tiny=yes&function=VpnClRefresh');";
    $f[]="</script>";
    return @implode("\n",$f).vpncl_live_js($page);
}

/**
 * Courbes peity mises a jour toutes les 5 s. Le tampon de chaque tunnel vit
 * dans window.VpnClLive (hors du DOM) : le rechargement des cartes toutes les
 * 30 s recree les elements sans perdre les points deja traces.
 */
function vpncl_live_js($page):string{
    $ac=vpncl_accents();
    $soft=$ac["accent_soft"]; $dark=$ac["accent_dark"];
    $f[]="<script>";
    $f[]="window.VpnClLive=window.VpnClLive||{};";
    $f[]="var VPNCL_MAXPTS=60;";
    $f[]="function VpnClFmtRate(bps){";
    $f[]="  var b=bps*8,u=['b/s','Kb/s','Mb/s','Gb/s'],i=0;";
    $f[]="  while(b>=1000 && i<u.length-1){b=b/1000;i++;}";
    $f[]="  return ((b>=100||i==0)?b.toFixed(0):b.toFixed(1))+' '+u[i];";
    $f[]="}";
    $f[]="function VpnClFmtBytes(bytes){";
    $f[]="  var kb=Math.floor(bytes/1024),r=function(v){return Math.round(v*100)/100;};";
    $f[]="  if(kb<1024){return kb+'KB';}";
    $f[]="  if(kb>1048576){var g=r(kb/1048576);return g>1000?r(g/1000)+'TB':g+'GB';}";
    $f[]="  return r(kb/1024)+'MB';";
    $f[]="}";
    $f[]="function VpnClSeed(el,cls){";
    $f[]="  var s=el.querySelector(cls+' span');";
    $f[]="  if(!s){return [];}";
    $f[]="  return s.textContent.split(',').map(function(v){return parseFloat(v)||0;});";
    $f[]="}";
    // Trace les deux calques sur la meme echelle (max commun), largeur relue a chaque fois.
    $f[]="function VpnClDraw(el){";
    $f[]="  if(!window.jQuery || !jQuery.fn.peity){return;}";
    $f[]="  var z=el.getAttribute('data-zmd5'),L=VpnClLive[z];";
    $f[]="  if(!L){L=VpnClLive[z]={rx:VpnClSeed(el,'.vcl-pk-rx'),tx:VpnClSeed(el,'.vcl-pk-tx'),t:0,brx:0,btx:0,live:0};}";
    $f[]="  var max=Math.max.apply(null,[1].concat(L.rx,L.tx));";
    $f[]="  var w=el.clientWidth||300,h=el.clientHeight||56;";
    $f[]="  jQuery(el).find('.vcl-pk-rx span').text(L.rx.join(',')).peity('line',{width:w,height:h,min:0,max:max,fill:'$soft',stroke:'$dark',strokeWidth:2});";
    $f[]="  jQuery(el).find('.vcl-pk-tx span').text(L.tx.join(',')).peity('line',{width:w,height:h,min:0,max:max,fill:'none',stroke:'rgba(133,83,0,.7)',strokeWidth:1.5});";
    $f[]="}";
    $f[]="function VpnClPeityAll(){";
    $f[]="  var els=document.querySelectorAll('.vcl-pkw[data-zmd5]');";
    $f[]="  for(var i=0;i<els.length;i++){VpnClDraw(els[i]);}";
    $f[]="}";
    $f[]="function VpnClPush(a,v){a.push(v);while(a.length>VPNCL_MAXPTS){a.shift();}}";
    // Un releve : debit = difference de compteurs / temps ecoule (serveur).
    $f[]="function VpnClApply(d){";
    $f[]="  if(!d || !d.clients){return;}";
    $f[]="  for(var z in d.clients){";
    $f[]="    var c=d.clients[z],L=VpnClLive[z];";
    $f[]="    if(!L){continue;}";
    $f[]="    if(!c.up){L.t=0;continue;}";
    $f[]="    if(L.t>0 && d.t>L.t){";
    $f[]="      var dt=d.t-L.t;";
    $f[]="      var rr=(c.rx>=L.brx?c.rx-L.brx:c.rx)/dt, tr=(c.tx>=L.btx?c.tx-L.btx:c.tx)/dt;";
    $f[]="      VpnClPush(L.rx,rr); VpnClPush(L.tx,tr); L.live++;";
    $f[]="      var card=document.querySelector('.vcl-trf[data-zmd5=\"'+z+'\"]');";
    $f[]="      if(card){";
    $f[]="        var e=card.querySelector('.vcl-rv-rx'); if(e){e.textContent=VpnClFmtRate(rr);}";
    $f[]="        e=card.querySelector('.vcl-rv-tx'); if(e){e.textContent=VpnClFmtRate(tr);}";
    $f[]="        e=card.querySelector('.vcl-vv'); if(e){e.textContent=VpnClFmtBytes(c.rx+c.tx);}";
    $f[]="        if(L.live>=2){e=card.querySelector('.vcl-wait'); if(e){e.parentNode.removeChild(e);}}";
    $f[]="        var sp=card.querySelector('.vcl-pkw'); if(sp){VpnClDraw(sp);}";
    $f[]="      }";
    $f[]="    }";
    $f[]="    L.t=d.t; L.brx=c.rx; L.btx=c.tx;";
    $f[]="  }";
    $f[]="}";
    $f[]="function VpnClLiveTick(){";
    $f[]="  if(!document.getElementById('vcl-list')){ clearInterval(window.VpnClLiveTimer); window.VpnClLiveTimer=null; return; }";
    $f[]="  if(document.hidden || window.VpnClLiveBusy){ return; }";
    $f[]="  if(!document.querySelector('.vcl-pkw[data-zmd5]')){ return; }";
    $f[]="  window.VpnClLiveBusy=true;";
    $f[]="  var x=new XMLHttpRequest();";
    $f[]="  x.open('GET','$page?traffic-live=yes&_='+Date.now(),true);";
    $f[]="  x.timeout=4500;";
    // Session expiree : la console renvoie du HTML, JSON.parse echoue et on ignore le releve.
    $f[]="  x.onload=function(){ window.VpnClLiveBusy=false; var d=null; try{ d=JSON.parse(x.responseText); }catch(e){ return; } VpnClApply(d); };";
    $f[]="  x.onerror=x.ontimeout=function(){ window.VpnClLiveBusy=false; };";
    $f[]="  x.send();";
    $f[]="}";
    $f[]="if(window.VpnClLiveTimer){ clearInterval(window.VpnClLiveTimer); }";
    $f[]="window.VpnClLiveBusy=false;";
    $f[]="window.VpnClLiveTimer=setInterval(VpnClLiveTick,5000);";
    $f[]="</script>";
    return @implode("\n",$f);
}

function vpncl_table_check($q){
    if(!$q->TABLE_EXISTS("vpn_client")){
        $sql="CREATE TABLE IF NOT EXISTS `vpn_client` (
		`zmd5` TEXT NOT NULL PRIMARY KEY,
		`rulename` TEXT NOT NULL DEFAULT '',
		`zipcontent` TEXT NOT NULL DEFAULT '',
		 `details` TEXT NOT NULL DEFAULT '',
		 `error` TEXT NOT NULL DEFAULT '',
		`enable`INTEGER NOT NULL DEFAULT 1,
		`username` TEXT NOT NULL DEFAULT '',
		`password` TEXT NOT NULL DEFAULT '',
        `lifetime` INTEGER NOT NULL DEFAULT 0,
        `starttime` INTEGER NOT NULL DEFAULT 0,
        `stoptime` INTEGER NOT NULL DEFAULT 0,
        `support` INTEGER NOT NULL DEFAULT 0,
        `asgateway` INTEGER NOT NULL DEFAULT 0,
        `interface` TEXT NOT NULL DEFAULT '',
        `ipaddr` TEXT NOT NULL DEFAULT '',
        `netmask` TEXT NOT NULL DEFAULT '',
        `network` TEXT NOT NULL DEFAULT '',
        `uptime` TEXT NOT NULL DEFAULT '',
		`status` INT NOT NULL DEFAULT 0
		) ";
        $q->QUERY_SQL($sql);
    }
    $cols=array("status"=>"INT NOT NULL DEFAULT 0","lifetime"=>"INT NOT NULL DEFAULT 0",
        "starttime"=>"INT NOT NULL DEFAULT 0","stoptime"=>"INT NOT NULL DEFAULT 0",
        "uptime"=>"TEXT NOT NULL DEFAULT ''","support"=>"INT NOT NULL DEFAULT 0",
        "asgateway"=>"INT NOT NULL DEFAULT 0","netmask"=>"TEXT NOT NULL DEFAULT ''",
        "ipaddr"=>"TEXT NOT NULL DEFAULT ''","interface"=>"TEXT NOT NULL DEFAULT ''",
        "network"=>"TEXT NOT NULL DEFAULT ''");
    foreach ($cols as $col=>$def){
        if(!$q->FIELD_EXISTS("vpn_client",$col)){
            $q->QUERY_SQL("ALTER TABLE vpn_client ADD `$col` $def");
        }
    }
}

/**
 * Trafic de toutes les connexions en un appel (articarest
 * /openvpn/client/traffic). Un daemon plus ancien ne connait pas la route :
 * tableau vide, les cartes affichent alors des tirets.
 */
function vpncl_traffic():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvpn/client/traffic?minutes=120"),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["clients"]) || !is_array($json["clients"])){
        return array();
    }
    return $json["clients"];
}

/** Les cartes, avec mise a jour des compteurs de la barre. */
function main():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=vpncl_function();
    if($function==""){$function="VpnClRefresh";}

    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    vpncl_table_check($q);
    $EnableArticaAsGateway=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway"));

    $results=$q->QUERY_SQL("SELECT * FROM vpn_client ORDER BY rulename");
    if(!$q->ok){
        echo $q->mysql_error_html(true);
        return false;
    }
    if(!is_array($results)){$results=array();}
    $traffic=vpncl_traffic();

    // Recherche : nom, serveur, compte, interface et IP du tunnel. Filtree ici
    // et non en SQL : le serveur est dans un tableau PHP serialise (details).
    $search=strtolower(trim((string)($_GET["search"] ?? "")));
    $search=str_replace("*","",$search);

    $cards=array();
    $nUp=0; $nDown=0;
    foreach($results as $ligne){
        $st=vpncl_state($ligne);
        if($st["kind"]=="ok"){$nUp++;}else{$nDown++;}
        if($search<>""){
            $info=@unserialize(base64_decode((string)$ligne["details"]));
            if(!is_array($info)){$info=array();}
            $hay=strtolower(@implode(" ",array($ligne["rulename"],$ligne["username"],
                $ligne["interface"],$ligne["ipaddr"],$info["server"] ?? "")));
            if(strpos($hay,$search)===false){continue;}
        }
        $tr=isset($traffic[$ligne["zmd5"]])?$traffic[$ligne["zmd5"]]:array();
        $cards[]=vpncl_card($ligne,$st,$tr,$page,$function,$EnableArticaAsGateway,$tpl);
    }

    $html=array();
    if(count($cards)==0){
        $html[]="<div class='ak-empty'>{no_data}</div>";
    }else{
        $html[]=@implode("\n",$cards);
    }
    $total=count($results);
    $script="<script>NoSpinner();"
        ."(function(){var m={'vcl-n-up':$nUp,'vcl-n-down':$nDown,'vcl-n-total':$total};"
        ."for(var k in m){var e=document.getElementById(k);if(e){e.innerHTML=m[k];}}})();"
        ."if(typeof VpnClPeityAll==='function'){VpnClPeityAll();}</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;
}

/**
 * Etat d'une connexion : kind = ok | warn | err | off, plus le libelle.
 * status (vpn_client) : 100 lance, 110 erreur, 200/500 arrete, 0 non installe.
 */
function vpncl_state($ligne):array{
    $zmd5=vpncl_md5($ligne["zmd5"]);
    if(intval($ligne["enable"])==0){
        return array("kind"=>"off","label"=>"{disabled}");
    }
    $status=intval($ligne["status"]);
    if($status==110){return array("kind"=>"err","label"=>"{error}");}
    if($status==200 || $status==500){return array("kind"=>"err","label"=>"{stopped}");}
    if($status==0){return array("kind"=>"off","label"=>"{uninstalled}");}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvpn/client/state/$zmd5"),true);
    $cnx="";
    if(is_array($json) && isset($json["Info"]["Connected"])){$cnx=(string)$json["Info"]["Connected"];}
    if($cnx=="CONNECTED"){return array("kind"=>"ok","label"=>"{vpncl_connected}");}
    if($cnx==""){return array("kind"=>"err","label"=>"{disconnected}");}
    // Etats transitoires du socket de gestion (CONNECTING, WAIT, AUTH...).
    return array("kind"=>"warn","label"=>req_html($cnx));
}

/** Debit en octets/s rendu en bits/s. */
function vpncl_fmt_rate($bytesPerSec):string{
    $b=floatval($bytesPerSec)*8;
    $units=array("b/s","Kb/s","Mb/s","Gb/s");
    $i=0;
    while($b>=1000 && $i<count($units)-1){$b=$b/1000;$i++;}
    $v=($b>=100||$i==0)?sprintf("%.0f",$b):sprintf("%.1f",$b);
    return "$v {$units[$i]}";
}

/**
 * Courbe des debits (descendant en aire, montant en trait). Moins de deux
 * points : trait pointille.
 */
function vpncl_sparkline($points,$up):string{
    $ac=vpncl_accents();
    $W=300; $H=56;
    if(!is_array($points) || count($points)<2){
        $color=$up?$ac["accent_line"]:"#bbcac6";
        return "<svg class='vcl-spark' viewBox='0 0 $W $H' preserveAspectRatio='none'>"
            ."<line x1='0' y1='".($H-4)."' x2='$W' y2='".($H-4)."' stroke='$color' stroke-width='2' stroke-dasharray='5 5' vector-effect='non-scaling-stroke'/></svg>";
    }
    $max=0.0;
    foreach ($points as $p){
        $max=max($max,floatval($p["rx"] ?? 0),floatval($p["tx"] ?? 0));
    }
    if($max<=0){$max=1;}
    $n=count($points);
    $rx=array(); $tx=array();
    foreach (array_values($points) as $i=>$p){
        $x=round($i*$W/($n-1),1);
        $rx[]="$x,".round($H-3-(floatval($p["rx"] ?? 0)/$max)*($H-8),1);
        $tx[]="$x,".round($H-3-(floatval($p["tx"] ?? 0)/$max)*($H-8),1);
    }
    $gid="g".substr(md5(serialize($points)),0,8);
    $f="<svg class='vcl-spark' viewBox='0 0 $W $H' preserveAspectRatio='none'>";
    $f.="<defs><linearGradient id='$gid' x1='0' y1='0' x2='0' y2='1'>"
        ."<stop offset='0' stop-color='{$ac["accent"]}' stop-opacity='.30'/>"
        ."<stop offset='1' stop-color='{$ac["accent"]}' stop-opacity='0'/></linearGradient></defs>";
    $f.="<polygon points='0,$H ".@implode(" ",$rx)." $W,$H' fill='url(#$gid)'/>";
    $f.="<polyline points='".@implode(" ",$rx)."' fill='none' stroke='{$ac["accent_dark"]}' stroke-width='2' vector-effect='non-scaling-stroke'/>";
    $f.="<polyline points='".@implode(" ",$tx)."' fill='none' stroke='#855300' stroke-width='1.5' stroke-opacity='.7' vector-effect='non-scaling-stroke'/>";
    $f.="</svg>";
    return $f;
}

/**
 * Courbe peity d'un tunnel actif : deux calques superposes (descendant en
 * aire, montant en trait), amorces avec l'historique a la minute puis
 * alimentes toutes les 5 s par VpnClLiveTick().
 */
function vpncl_peity($zmd5,$points):string{
    $rx=array(); $tx=array();
    if(is_array($points)){
        foreach (array_slice(array_values($points),-60) as $p){
            $rx[]=sprintf("%.2f",floatval($p["rx"] ?? 0));
            $tx[]=sprintf("%.2f",floatval($p["tx"] ?? 0));
        }
    }
    if(count($rx)==0){$rx[]="0";$tx[]="0";}
    return "<div class='vcl-spark vcl-pkw' data-zmd5='$zmd5'>"
        ."<div class='vcl-pk vcl-pk-rx'><span>".@implode(",",$rx)."</span></div>"
        ."<div class='vcl-pk vcl-pk-tx'><span>".@implode(",",$tx)."</span></div></div>";
}

/** Bloc trafic d'une carte : debits, volume, courbe. */
function vpncl_traffic_block($tr,$kind,$zmd5=""):string{
    $up=($kind=="ok");
    $hasData=is_array($tr) && isset($tr["rx_bytes"]);
    $rxRate=$hasData?floatval($tr["rx_rate"] ?? 0):0;
    $txRate=$hasData?floatval($tr["tx_rate"] ?? 0):0;
    $zero=(!$up || !$hasData)?" vcl-zero":"";
    $f="<div class='vcl-trf' data-zmd5='$zmd5'><div class='vcl-rates'>";
    if($hasData){
        $f.="<span class='vcl-rate$zero'><i class='fas fa-arrow-down'></i> <span class='vcl-rv-rx'>".vpncl_fmt_rate($rxRate)."</span></span>";
        $f.="<span class='vcl-rate vcl-up$zero'><i class='fas fa-arrow-up'></i> <span class='vcl-rv-tx'>".vpncl_fmt_rate($txRate)."</span></span>";
        $vol=(floatval($tr["rx_bytes"] ?? 0)+floatval($tr["tx_bytes"] ?? 0))/1024;
        $f.="<span class='vcl-vol'>{volume}: <span class='vcl-vv'>".FormatBytes($vol,true)."</span></span>";
    }else{
        $f.="<span class='vcl-rate vcl-zero'><i class='fas fa-arrow-down'></i> &mdash;</span>";
        $f.="<span class='vcl-rate vcl-up vcl-zero'><i class='fas fa-arrow-up'></i> &mdash;</span>";
        $f.="<span class='vcl-vol'>{volume}: &mdash;</span>";
    }
    $f.="</div>";
    $points=($hasData && isset($tr["points"]) && is_array($tr["points"]))?$tr["points"]:array();
    if($up && $hasData && $zmd5<>""){
        $f.=vpncl_peity($zmd5,$points);
    }else{
        $f.=vpncl_sparkline($points,$up);
    }
    if($up && count($points)<2){
        $f.="<div class='vcl-wait'>{vpncl_traffic_wait}</div>";
    }
    $f.="</div>";
    return $f;
}

/** Boutons d'action d'une carte. */
function vpncl_actions($ligne,$kind,$page,$md):string{
    $users=new usersMenus();
    $zmd5=vpncl_md5($ligne["zmd5"]);
    $enable=intval($ligne["enable"]);
    $f="<div class='ak-acts'>";
    $f.="<button type='button' class='ak-act' title='{events}' OnClick=\"Loadjs('fw.openvpn.events.php?client-js=$zmd5');\"><i class='fas fa-file-lines'></i></button>";
    if($users->AsVPNManager){
        if($enable==1){
            if($kind=="ok" || $kind=="warn"){
                $f.="<button type='button' class='ak-act' title='{restart}' OnClick=\"Loadjs('$page?start=$zmd5');\"><i class='fas fa-rotate-right'></i></button>";
                $f.="<button type='button' class='ak-act vcl-act-stop' title='{stop}' OnClick=\"Loadjs('$page?stop=$zmd5');\"><i class='fas fa-circle-stop'></i></button>";
            }else{
                $f.="<button type='button' class='ak-btn ak-btn-lg ak-btn-primary' OnClick=\"Loadjs('$page?start=$zmd5');\"><i class='fas fa-play'></i> {vpncl_relaunch}</button>";
            }
        }
        $cls=($enable==1)?"ak-act vcl-act-on":"ak-act";
        $tip=($enable==1)?"{disable}":"{enable}";
        $f.="<button type='button' class='$cls' title='$tip' OnClick=\"Loadjs('$page?enable=$zmd5');\"><i class='fas fa-power-off'></i></button>";
        $f.="<button type='button' class='ak-act ak-danger' title='{delete}' OnClick=\"Loadjs('$page?delete=$zmd5&md=$md');\"><i class='fas fa-trash-can'></i></button>";
    }
    $f.="</div>";
    return $f;
}

/** Une connexion VPN cliente = une carte. */
// Pentest 2026-11-04 : htmlspecialchars UTF-8 rendait "" sur du Latin-1 ; remplace par req_html (XSS stockee).
function vpncl_card($ligne,$st,$tr,$page,$function,$EnableArticaAsGateway,$tpl):string{
    $zmd5=vpncl_md5($ligne["zmd5"]);
    $md=md5($zmd5);
    $kind=$st["kind"];
    $rulename=(string)$ligne["rulename"];
    $username=(string)$ligne["username"];
    $lifetime=intval($ligne["lifetime"]);
    $interface=trim((string)$ligne["interface"]);
    $ipaddr=trim((string)$ligne["ipaddr"]);
    $support=intval($ligne["support"]);
    $asgateway=intval($ligne["asgateway"]);
    if($EnableArticaAsGateway==1){$asgateway=1;}
    $error=trim((string)$ligne["error"]);
    $uptime=trim((string)$ligne["uptime"]);

    $info=@unserialize(base64_decode((string)$ligne["details"]));
    if(!is_array($info)){$info=array();}
    $proto=strtoupper((string)($info["proto"] ?? ""));
    $srv=(string)($info["server"] ?? "");
    $port=(string)($info["port"] ?? "");

    // Un identifiant de licence tient lieu de compte pour les connexions de support.
    $isLicense=preg_match("#^[A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+$#",$username)==1;
    $ico=$isLicense?ico_support:ico_plug;
    if($asgateway==1 && $support==0){$ico="fas fa-network-wired";}

    $icoCls="vcl-ico"; $pip="vcl-pip"; $pill="ak-pill ak-ok";
    if($kind=="err"){$icoCls.=" vcl-ico-err";$pip.=" vcl-pip-err";$pill="ak-pill ak-err";}
    if($kind=="warn"){$pip.=" vcl-pip-warn";$pill="ak-pill ak-warn";}
    if($kind=="off"){$icoCls.=" vcl-ico-off";$pip.=" vcl-pip-off";$pill="ak-pill ak-off";}

    $jsOpen="Loadjs('$page?cnx=$zmd5&function=$function')";
    $nameSafe=req_html($rulename);

    // Colonne 1 : identite.
    $c1="<div class='vcl-id'><div class='$icoCls'><i class='$ico'></i><span class='$pip'></span></div>";
    $c1.="<div style='min-width:0'>";
    $c1.="<div class='vcl-head'><div class='vcl-name' title='$nameSafe'><a href=\"javascript:blur();\" OnClick=\"$jsOpen\">$nameSafe</a></div>"
        ."<span class='$pill' id='status-$zmd5'>{$st["label"]}</span></div>";
    $nic=($interface<>"")?"<span class='ak-chip vcl-chip'>".req_html($interface)."</span>":"";
    $ip=($ipaddr<>"" && $kind=="ok")?req_html($ipaddr):"{vpncl_not_assigned}";
    $c1.="<div class='vcl-nic'>$nic$ip</div>";
    $sub=array();
    if($support==1){$sub[]="{vpncl_support_cnx}";}
    if($asgateway==1 && $support==0){$sub[]="{vpncl_used_as_gateway}";}
    if($kind=="ok" && $uptime<>""){$sub[]="{running_since} ".req_html($uptime);}
    if(count($sub)>0){$c1.="<div class='vcl-sub'>".@implode(" &bull; ",$sub)."</div>";}
    if($kind=="err" && $error<>""){
        $c1.="<div class='vcl-err' id='error-$zmd5'><i class='fas fa-triangle-exclamation'></i> ".req_html($error)."</div>";
    }
    $c1.="</div></div>";

    // Colonne 2 : passerelle distante.
    $c2="<div><div class='ak-fig-l vcl-lbl'>{vpncl_remote_gateway}</div>";
    if($srv<>""){
        $c2.="<div class='vcl-val'>".req_html($srv)."</div>";
        $meta=array();
        // « Port » en clair : le jeton {port} vaut « Port TCP » en francais.
        if($port<>""){$meta[]="Port ".req_html($port);}
        if($proto<>""){$meta[]=req_html($proto);}
        if(count($meta)>0){$c2.="<div style='margin-top:6px'><span class='ak-chip vcl-chip'>".@implode(" &bull; ",$meta)."</span></div>";}
    }else{
        $c2.="<div class='vcl-val'>&mdash;</div>";
    }
    $c2.="</div>";

    // Colonne 3 : compte et validite.
    $c3="<div><div class='ak-fig-l vcl-lbl'>{vpncl_account_validity}</div>";
    if(strlen($username)<3){
        $c3.="<div class='vcl-val' style='color:#ba1a1a'>{missing}</div>";
    }else{
        $c3.="<div class='vcl-val'>".req_html($username)."</div>";
    }
    if($lifetime>0){
        if($lifetime>time()){
            $c3.="<div class='vcl-note'><i class='fas fa-clock'></i> {expire_in} ".distanceOfTimeInWords(time(),$lifetime,true)."</div>";
        }else{
            $c3.="<div class='vcl-note vcl-note-warn'><i class='fas fa-clock'></i> {expired}</div>";
        }
    }elseif($kind!="ok"){
        $c3.="<div class='vcl-note vcl-note-off'><i class='fas fa-hourglass'></i> {vpncl_session_inactive}</div>";
    }
    $c3.="</div>";

    $cardCls=($kind=="ok")?"vcl-card":"vcl-card vcl-off";
    return "<div class='$cardCls' id='$md'>$c1$c2$c3".vpncl_traffic_block($tr,$kind,vpncl_md5($ligne["zmd5"])).vpncl_actions($ligne,$kind,$page,$md)."</div>";
}

function js_tiny(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=vpncl_function();
    $users=new usersMenus();
    $topbuttons=array();
    if($users->AsVPNManager) {
        $topbuttons[] = array("Loadjs('$page?upload-js=yes&function=$function')", ico_plus, "{create_a_connection}");
    }

    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/openvpn/service/version");
    $TINY_ARRAY["EXPL"]="{vpn_client_explain}";


    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $TINY_ARRAY["DANGER"]=true;
    }
    if(!$json->Status){
        $TINY_ARRAY["EXPL"]="{vpn_client_explain}<br><strong>$json->Error</strong>";


    }
    $topbuttons[] = array("Loadjs('fw.system.upgrade-software.php?product=APP_OPENVPN')", ico_cd, "{upgrade}");
    $TINY_ARRAY["TITLE"]="{vpn_client} v$OpenVPNVersion";
    $TINY_ARRAY["ICO"]="fa fa-compress";

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

}


function upload_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=vpncl_function();
    return $tpl->js_dialog6("{upload_connection}", "$page?upload-popup=yes&function=$function",550);
}
function upload_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=vpncl_function();
    $btn=$tpl->button_upload("{upload_connection}",$page,null,"&function=$function");
    $html="<div class='alert alert-success'>{upload_connection_vpn_client}</div>
			<div class='center' style='margin:30px'>$btn</div>
			";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function td_status($zmd5):array{

    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $ligne=$q->mysqli_fetch_array("SELECT status,error,enable,uptime FROM vpn_client WHERE zmd5='$zmd5'");
    // Pentest 2026-11-04 : erreur/uptime du client OpenVPN (donnee externe) inseres en innerHTML sans encodage (XSS stockee).
    $error=req_html((string)$ligne["error"]);
    $enable=intval($ligne["enable"]);
    $uptime=req_html((string)$ligne["uptime"]);
    $StatusTool="";
    $stateZ=td_state($zmd5);

    $status=intval($ligne["status"]);
    if($status==110){
        $StatusTool="<span class='label label-danger'>{error}</span>";
        $error="<br><span class='text-danger'>$error</span>";
    }
    if($status==100){

        $StatusTool=td_state($zmd5);

        if (strlen($uptime)>0) {
            $error = "<br>{running_since}: $uptime";
        }
    }
    if($status==200){
        $StatusTool="<span class='label label-danger'>{stopped}</span>";
        $error="";
    }
    if($status==500){
        $StatusTool="<span class='label label-danger'>{stopped}</span>";
        $error="<br><span class='text-danger'>$error</span>";
    }
    if($status==0){
        $StatusTool="<span class='label label-default'>{uninstalled}</span>";
        $error="<br><span class='text-danger'>$error</span>";
    }
    if($enable==0){
        $StatusTool="<span class='label label-default'>{disabled}</span>";
    }
    $tpl=new template_admin();
    $StatusTool=$tpl->_ENGINE_parse_body($StatusTool);
    return array($StatusTool,$error);

}

function td_state($zmd5){
    $tpl=new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("openvpn/client/state/$zmd5");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return "<span class='label label-danger'>Err.10</span>";
    }

    $state=$json->Info;
    if ($state->Connected=="CONNECTED"){
        return $tpl->_ENGINE_parse_body("<span class='label label-primary'>{connected}</span>");
    }
    // Pentest 2026-11-04 : etat renvoye par le socket de gestion OpenVPN (donnee externe) affiche sans encodage.
    $Connected_h=req_html((string)$state->Connected);
    return $tpl->_ENGINE_parse_body("<span class='label label-default'>$Connected_h</span>");
}

function uploaded_js():bool{
    $function=vpncl_function();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];

    $sock=new sockets();
    $data = $sock->REST_API("/openvpn/client/config/upload/$filename");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->js_error(json_last_error_msg()."<br>$sock->mysql_error");
        return false;
    }

    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();\n";
    echo "$function();\n";
    return true;
}