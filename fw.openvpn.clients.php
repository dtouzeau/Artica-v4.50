<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style()/awc_pager_js()/siem_tiny_filters(): inclusion INCONDITIONNELLE,
// au premier niveau. Placee dans le bloc if(!class_exists("sockets")) elle ne
// s'executerait jamais -> Call to undefined function au rendu.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["ConnectSupport"])){support_save();exit;}

if(isset($_GET["uid-row"])){uid_row();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["delete-js"])){delete_rule_js();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_POST["connection_name"])){buildconfig();exit;}
if(isset($_POST["delete-script"])){delete_script();exit;}
if(isset($_GET["rebuild"])){rebuild();exit;}
if(isset($_GET["fw-js"])){fw_js();exit;}
if(isset($_GET["support-js"])){support_js();exit;}
if(isset($_GET["support-popup"])){support_popup();exit;}
if(isset($_GET["export"])){export_csv();exit;}

page();
function rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$_GET["uid"]=urlencode($_GET["uid"]);
    $function=$_GET["function"];
	return $tpl->js_dialog("{BUILD_OPENVPN_CLIENT_CONFIG}","$page?rule-popup=yes&uid={$_GET["uid"]}&function=$function");
}

function fw_js():bool{
	// Open the per-client firewall manager (firewall page scoped by clientuid) in a dialog.
	$tpl=new template_admin();
	$uid=urlencode($_GET["uid"]);
	return $tpl->js_dialog2("{firewall}","fw.openvpn.firewall.php?main-start=yes&clientuid=$uid&function=NoSpinner");
}

function delete_rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $uid=base64_decode($_GET["delete-js"]);
	// Pentest 2026-11-04 : delete-js/id etait recopie sans encodage dans la page (XSS).
	$delete=$tpl->javascript_parse_text("{delete}")." ".req_js($uid);
	$domid=req_js($_GET["id"] ?? "");
	$deljs=req_js($_GET["delete-js"]);
	
	
echo "	
	var xOpenVPNRoutesDelete$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		 $('#$domid').remove();
	}
	
	function OpenVPNRoutesDelete$t(){
		if(!confirm('$delete ?')){return;}
		var XHR = new XHRConnection();
		
		XHR.appendData('delete-script','$deljs');
		XHR.sendAndLoad('$page', 'POST',xOpenVPNRoutesDelete$t);
	}
	
	OpenVPNRoutesDelete$t();
	";
	return true;
}

function support_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $_GET["uid"]=urlencode($_GET["uid"]);
    $function=$_GET["function"];
    return $tpl->js_dialog("{create_a_support_connection}","$page?support-popup=yes&function=$function");
}
function uid_row(){
    $uid=(string)$_GET["uid-row"];
    $uidmd5=md5($uid);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    // Le uid vient de l'URL et partait BRUT dans la clause WHERE.
    $uidSql=$q->sqlite_escape_string2($uid);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM openvpn_clients WHERE uid='$uidSql'");
    if(!is_array($ligne)){
        header("content-type: application/x-javascript");
        echo "";
        return;
    }

    // Memes constructeurs que le tableau: une ligne rafraichie ne peut donc pas
    // diverger d'une ligne rendue. L'adresse repartait auparavant en texte brut,
    // sans sa puce.
    $cells=array();
    $cells["fixip-$uidmd5"]  =$tpl->_ENGINE_parse_body(td_fixip($ligne));
    $cells["ipaddr-$uidmd5"] =$tpl->_ENGINE_parse_body(ovc_ip($ligne));
    $cells["status-$uidmd5"] =$tpl->_ENGINE_parse_body(td_status($ligne));
    $cells["auth-$uidmd5"]   =$tpl->_ENGINE_parse_body(td_auth($ligne));
    $cells["profile-$uidmd5"]=$tpl->_ENGINE_parse_body(td_profile($ligne));

    $f=array();
    foreach($cells as $id=>$html){
        $b64=base64_encode((string)$html);
        $f[]="if(document.getElementById('$id')){";
        $f[]="\tdocument.getElementById('$id').innerHTML=base64_decode('$b64');";
        $f[]="}";
    }

    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
}


function support_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $BootstrapDialog="BootstrapDialog1.close();";
    $Life[1800]="30 {minutes}";
    $Life[3600]="1 {hour}";
    $Life[7200]="2 {hours}";
    $Life[21600]="6 {hours}";
    $Life[43200]="12 {hours}";
    $Life[86400]="1 {day}";
    $Life[172800]="2 {days}";
    $Life[259200]="3 {days}";
    $Life[604800]="1 {week}";
    $Life[1814400]="3 {week}";
    $Life[2592000]="1 {month}";
    $Life[5184000]="2 {months}";

    $form[]=$tpl->field_hidden("ConnectSupport",1);
    $form[]=$tpl->field_text("connection_name", "{organizationName}", "",true);
    $form[]=$tpl->field_array_hash($Life,"lifetime", "nonull:{client_lifetime}", 21600);

    $html=$tpl->form_outside(null,$form,"{create_a_support_connection_explain}",
        "{generate_parameters}","$function();$BootstrapDialog","AsVPNManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function support_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $uid=FixConnectionName($_POST["connection_name"]);
    $add["uid"]=$uid;
    $add["ComputerOS"]="linux";
    $add["lifetime"]=time()+intval($_POST["lifetime"]);
    $add["support"]=1;
    $add["username"]=generateRandomSerial();
    $add["password"]=generateRandomSerial();

    foreach ($add as $key=>$val){
        $f_add[]="`$key`";
        $f_vals[]="'$val'";

    }

    $sql=sprintf("INSERT OR IGNORE INTO openvpn_clients (%s) VALUES (%s)",@implode(",",$f_add),@implode(",",$f_vals));
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error("SQL:$q->mysql_error<br>$sql");
        return false;
    }

    $sock=new sockets();
    $data=$sock->REST_API("/openvpn/client/build/$uid");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg()."<br>$sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error("API:$json->Error");
        return false;
    }
    return admin_tracks("Create a new OpenVPN Support client connection for $uid");

}
function generateRandomSerial($numGroups = 4):string {
    $serial = '';
    for ($i = 0; $i < $numGroups; $i++) {
        $group = '';
        for ($j = 0; $j < 4; $j++) {
            if (rand(0, 1)) {
                $group .= rand(0, 9);
            } else {
                $group .= chr(rand(65, 90));
            }
        }
        $serial .= $group;
        if ($i < $numGroups - 1) {
            $serial .= '-';
        }
    }

    return $serial;
}



function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");

    $html=$tpl->page_header("{members} v$OpenVPNVersion",
        "fas fa-users-crown","{BUILD_OPENVPN_CLIENT_CONFIG_TEXT}","$page?main-start=yes",
        "openvpn-configurators","progress-vpnclientsscr-restart",false,"table-loader");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function rebuild(){
    $tpl=new template_admin();
    $uid=$_GET["rebuild"];
    $function=$_GET["function"];

    $sock=new sockets();
    $data=$sock->REST_API("/openvpn/client/build/$uid");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $tpl->js_error(json_last_error_msg()."<br>$sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "$function()";
    return admin_tracks("Rebuild a new OpenVPN client connection $uid");
}



function rule_popup():bool{
	$tpl=new template_admin();
    $function=$_GET["function"];
	$os=GetOS();
	$os[null]="{select}";
	$BootstrapDialog="BootstrapDialog1.close();";



	$form[]=$tpl->field_text("connection_name", "{connection_name}", $_GET["uid"]);
	$form[]=$tpl->field_array_hash($os,"ComputerOS","nonull:{ComputerOS}",null,false);
	$html=$tpl->form_outside("{connection}",$form,"{BUILD_OPENVPN_CLIENT_CONFIG_TEXT}",
	"{generate_parameters}","$function();$BootstrapDialog","AsVPNManager");
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function routes_add(){
	$vpn=new openvpn();
	$vpn->routes[$_POST["ROUTE_FROM"]]=$_POST["ROUTE_MASK"];
	$vpn->Save();
}
function routes_delete(){
	$vpn=new openvpn();
	unset($vpn->routes[$_POST["DELETE_ROUTE_FROM"]]);
	$vpn->Save();

}

function GetOS():array{
	$os["windowsXP"]="Windows XP";
	$os["windows2003"]="Windows 2003/7";
	$os["linux"]="Linux/Synology";
    $os["artica"]="Artica Client";
	$os["mac"]="OS X 10.4, 10.5, 10.6";
	$os["Windows7"]="Windows 7,8,10";
	$os["Android_IOS"]="Android / IOS";
	return $os;
}

/* ===========================================================================
 *  Bibliotheque de composants .ovc-* -- transposition de
 *  /home/claude/images/stitch_refonte_interface_vpn_clients
 *  Regle du projet : un widget = une fonction.
 * ======================================================================== */

/**
 * Feuille de style de la page. Emise UNE SEULE FOIS par requete et TOUJOURS
 * concatenee EN DEHORS de _ENGINE_parse_body(): son motif #{(.+?)}#is
 * prendrait chaque corps de regle CSS pour un {token}.
 */
function ovc_style():string{
    if(isset($GLOBALS["OVC_CSS_DONE"])){return "";}
    $GLOBALS["OVC_CSS_DONE"]=true;
    return "<style>
.ovc-wrap{color:#334155;font-size:13px;line-height:1.45}
.ovc-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(216px,1fr));gap:16px;
margin:6px 0 18px}
.ovc-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:15px 18px;
display:flex;align-items:center;justify-content:space-between;gap:12px;
box-shadow:0 1px 2px rgba(15,23,42,.04)}
.ovc-kpi-l{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
.ovc-kpi-v{font-size:25px;font-weight:800;color:#0f172a;line-height:1.1;margin-top:5px}
.ovc-kpi-s{font-size:11px;color:#94a3b8;margin-left:7px;font-weight:500}
.ovc-kpi-i{width:42px;height:42px;border-radius:10px;display:inline-flex;align-items:center;
justify-content:center;font-size:16px;flex:0 0 auto}
.ovc-i-slate{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0}
.ovc-i-teal{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0}
.ovc-i-grey{background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0}
.ovc-i-amber{background:#fffbeb;color:#d97706;border:1px solid #fde68a}
.ovc-i-red{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.ovc-kpi-teal>div>.ovc-kpi-l,.ovc-kpi-teal>div>.ovc-kpi-v{color:#059669}
.ovc-kpi-amber>div>.ovc-kpi-l,.ovc-kpi-amber>div>.ovc-kpi-v{color:#d97706}
.ovc-kpi-red>div>.ovc-kpi-l,.ovc-kpi-red>div>.ovc-kpi-v{color:#dc2626}
.ovc-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;
box-shadow:0 1px 2px rgba(15,23,42,.04);margin-top:14px}
.ovc-scroll{overflow-x:auto}
.ovc-tbl{width:100%;border-collapse:collapse;font-size:13px}
.ovc-tbl th{font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;
color:#64748b;text-align:left;padding:14px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.ovc-tbl td{padding:13px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.ovc-nowrap{white-space:nowrap}
.ovc-center{text-align:center}
.ovc-pill{display:inline-flex;align-items:center;gap:7px;border-radius:999px;padding:5px 12px;
font-size:12px;font-weight:600;border:1px solid;white-space:nowrap}
.ovc-p-on{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.ovc-p-off{background:#f8fafc;color:#64748b;border-color:#e2e8f0}
.ovc-p-exp{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.ovc-dot{width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;
flex:0 0 auto}
.ovc-ident{display:flex;align-items:flex-start;gap:10px;min-width:0}
.ovc-uico{font-size:15px;color:#94a3b8;margin-top:2px;flex:0 0 auto}
.ovc-uico-sup{color:#d97706}
.ovc-uico-exp{color:#dc2626}
.ovc-name{font-size:13px;font-weight:700;color:#0f172a;word-break:break-word}
.ovc-name a{color:#0f172a;text-decoration:none}
.ovc-name a:hover{color:#0d9488}
.ovc-sub{font-size:11px;margin-top:3px}
.ovc-warn{color:#dc2626;font-style:italic}
.ovc-mut{color:#94a3b8}
.ovc-sup{color:#d97706;font-weight:600}
.ovc-chip{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #e2e8f0;
border-radius:7px;padding:4px 9px;font-size:12px;color:#334155;white-space:nowrap}
.ovc-chip-ip{font-family:'Courier New',Courier,monospace;background:#ecfdf5;border-color:#a7f3d0;
color:#047857;font-weight:600}
.ovc-none{color:#94a3b8;font-style:italic;font-family:'Courier New',Courier,monospace;font-size:12px;
white-space:nowrap}
.ovc-act{width:34px;height:34px;border-radius:8px;display:inline-flex;align-items:center;
justify-content:center;border:1px solid;cursor:pointer;font-size:13px;text-decoration:none}
.ovc-act:hover{text-decoration:none}
.ovc-a-teal{background:#ecfdf5;border-color:#a7f3d0;color:#059669}
.ovc-a-teal:hover{background:#d1fae5;color:#047857}
.ovc-a-slate{background:#f8fafc;border-color:#e2e8f0;color:#64748b}
.ovc-a-slate:hover{background:#f1f5f9;color:#334155}
.ovc-a-red{background:#fef2f2;border-color:#fecaca;color:#dc2626}
.ovc-a-red:hover{background:#fee2e2;color:#b91c1c}
.ovc-a-off{background:#f8fafc;border-color:#f1f5f9;color:#cbd5e1;cursor:not-allowed}
.ovc-foot{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
padding:14px;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8}
.ovc-foot-l{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.ovc-dhcp{display:inline-flex;align-items:center;gap:7px;font-family:'Courier New',Courier,monospace;
color:#64748b}
.ovc-empty{text-align:center;color:#94a3b8;padding:44px 14px}
</style>";
}

/** Detache le bloc <style> d'un fragment: il ne doit pas etre analyse. */
function ovc_split_style($html):array{
    $css="";
    $out=preg_replace_callback('#<style\b[^>]*>.*?</style>#is',
        function($m) use (&$css){$css.=$m[0];return "";},(string)$html);
    return array($css,(string)$out);
}

/** Etat demande, sur liste blanche: la valeur part dans du SQL et du JavaScript. */
function ovc_state():string{
    $state=(string)(isset($_GET["state"])?$_GET["state"]:"all");
    $allowed=array("all","connected","disconnected","expired");
    if(!in_array($state,$allowed,true)){return "all";}
    return $state;
}

/** Nom de la fonction de rafraichissement fournie par search_block. */
function ovc_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}

/** Clause WHERE commune a la liste et a l'export: meme filtre, meme recherche. */
function ovc_where($q,$search,$state):string{
    $where=array();
    $search=(string)$search;
    if(strlen($search)>2){
        $like=str_replace("*","%","*$search*");
        $like=str_replace("%%","%",$like);
        // Le terme est interpole dans le SQL: il doit etre echappe, sinon une
        // apostrophe dans la boite de recherche reecrit la requete.
        $like=$q->sqlite_escape_string2($like);
        $where[]="( uid LIKE '$like' OR ComputerOS LIKE '$like'"
                ." OR vpnip LIKE '$like' OR fixip LIKE '$like' )";
    }
    $now=time();
    if($state=="connected"){$where[]="( status=1 AND (lifetime=0 OR lifetime>$now) )";}
    if($state=="disconnected"){$where[]="( status<>1 AND (lifetime=0 OR lifetime>$now) )";}
    if($state=="expired"){$where[]="( lifetime>0 AND lifetime<=$now )";}
    if(count($where)==0){return "";}
    return " WHERE ".@implode(" AND ",$where);
}

/**
 * Compteurs des vignettes. Calcules sur la table ENTIERE, jamais sur la page
 * filtree: sinon "Total 8" mentirait des qu'un filtre est actif.
 */
function ovc_counters():array{
    $out=array("total"=>0,"connected"=>0,"disconnected"=>0,"support"=>0,"expired"=>0);
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $now=time();
    $sql="SELECT COUNT(*) AS total,"
        ." SUM(CASE WHEN lifetime>0 AND lifetime<=$now THEN 1 ELSE 0 END) AS expired,"
        ." SUM(CASE WHEN status=1 AND (lifetime=0 OR lifetime>$now) THEN 1 ELSE 0 END) AS connected,"
        ." SUM(CASE WHEN status<>1 AND (lifetime=0 OR lifetime>$now) THEN 1 ELSE 0 END) AS disconnected,"
        ." SUM(CASE WHEN support=1 THEN 1 ELSE 0 END) AS support"
        ." FROM openvpn_clients";
    $results=$q->QUERY_SQL($sql);
    if(!is_array($results)){return $out;}
    if(!isset($results[0])){return $out;}
    foreach($out as $key=>$unused){
        $out[$key]=intval(isset($results[0][$key])?$results[0][$key]:0);
    }
    return $out;
}

/** Une vignette de comptage. */
function ovc_kpi($ico,$tone,$label,$value,$sub=""):string{
    $subHtml=($sub!="")?"<span class='ovc-kpi-s'>$sub</span>":"";
    $h=array();
    $h[]="<div class='ovc-kpi ovc-kpi-$tone'>";
    $h[]="<div><div class='ovc-kpi-l'>$label</div>";
    $h[]="<div class='ovc-kpi-v'>$value$subHtml</div></div>";
    $h[]="<span class='ovc-kpi-i ovc-i-$tone'><i class='$ico'></i></span>";
    $h[]="</div>";
    return @implode("",$h);
}

/** La rangee de vignettes. Celle des expires n'apparait que s'il y en a. */
function ovc_kpi_row(array $c):string{
    $k=array();
    $k[]=ovc_kpi("fas fa-sitemap","slate","{ovc_total_connections}",$c["total"]);
    $k[]=ovc_kpi("fas fa-circle-check","teal","{ovc_successful_connections}",
        $c["connected"],"{ovc_online}");
    $k[]=ovc_kpi("fas fa-circle-pause","grey","{disconnected}",$c["disconnected"],"{ovc_offline}");
    $k[]=ovc_kpi("fas fa-headset","amber","{ovc_support_profile}",$c["support"],"{ovc_dedicated_tunnels}");
    if($c["expired"]>0){
        $k[]=ovc_kpi("fas fa-triangle-exclamation","red","{expired}",$c["expired"],"");
    }
    return "<div class='ovc-kpis'>".@implode("",$k)."</div>";
}

/** Icone d'identite: casque pour un tunnel de support, silhouette sinon. */
function ovc_uico($ligne):string{
    $support=intval(isset($ligne["support"])?$ligne["support"]:0);
    $cls=$support==1?"ovc-uico ovc-uico-sup":"ovc-uico";
    if(IsExpired($ligne)){$cls="ovc-uico ovc-uico-exp";}
    $ico=$support==1?"fas fa-headset":"fas fa-user";
    return "<i class='$ico $cls'></i>";
}

/**
 * Bloc identite: nom cliquable, IP fixe, methode d'authentification, profil.
 * Les identifiants de span sont un CONTRAT EXTERNE: uid_row() les rafraichit
 * depuis fw.openvpn.clients.config.php et fw.openvpn.profiles.php.
 */
function ovc_ident($ligne):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=ovc_function();
    $uid=(string)$ligne["uid"];
    $md5=md5($uid);
    $uidEnc=urlencode($uid);
    // Pentest 2026-11-04 : uid/adresses/noms (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1 (XSS stockee).
    $name=req_html($uid);
    if(!IsExpired($ligne)){
        $name="<a href=\"javascript:blur();\" OnClick=\"Loadjs("
             ."'fw.openvpn.clients.config.php?uid=$uidEnc&function=$function');\">$name</a>";
    }
    $h=array();
    $h[]="<div class='ovc-ident'>".ovc_uico($ligne);
    $h[]="<div style='min-width:0'>";
    $h[]="<div class='ovc-name'>$name<span id='fixip-$md5'>".td_fixip($ligne)."</span></div>";
    $h[]="<div id='auth-$md5'>".td_auth($ligne)."</div>";
    $h[]="<div id='profile-$md5'>".td_profile($ligne)."</div>";
    $h[]="</div></div>";
    return @implode("",$h);
}

/** Adresse distribuee au client, ou la mention explicite qu'il n'y en a pas. */
function ovc_ip($ligne):string{
    $ip=trim((string)(isset($ligne["vpnip"])?$ligne["vpnip"]:""));
    if($ip==="" OR $ip==="0.0.0.0"){
        return "<span class='ovc-none'>&mdash; {ovc_unassigned} &mdash;</span>";
    }
    return "<span class='ovc-chip ovc-chip-ip'>".req_html($ip)."</span>";
}

/** Systeme d'exploitation declare a la creation de la connexion. */
function ovc_os($ligne):string{
    $icons=array(""=>"fas fa-question","windowsXP"=>"fab fa-windows",
        "windows2003"=>"fab fa-windows","linux"=>"fab fa-linux","artica"=>"fab fa-linux",
        "mac"=>"fab fa-apple","Windows7"=>"fab fa-windows","Android_IOS"=>"fas fa-mobile-alt");
    $names=GetOS();
    $key=(string)(isset($ligne["ComputerOS"])?$ligne["ComputerOS"]:"");
    $ico=isset($icons[$key])?$icons[$key]:"fas fa-question";
    $name=isset($names[$key])?$names[$key]:"{unknown}";
    return "<span class='ovc-chip'><i class='$ico'></i>".htmlspecialchars($name)."</span>";
}

/**
 * Colonne "logiciel": soit le client est fourni par le systeme, soit on donne
 * les liens de telechargement du client OpenVPN adapte.
 */
function ovc_software($ligne):string{
    $down="<i class='fa fa-download'></i>&nbsp;";
    $connect="$down<a href='http://articatech.net/download/openvpn-connect-3.4.3.3337_signed.msi'>";
    $links=array();
    $links["windowsXP"]="$down<a href='http://www.articatech.net/download/openvpn-install-2.3.11-I001-i686.exe'>32Bits</a>&nbsp;|&nbsp;<a href='http://www.articatech.net/download/openvpn-install-2.3.11-I001-x86_64.exe'>64Bits</a>";
    $links["windows2003"]=$links["windowsXP"];
    $links["Windows7"]="$down<a href='http://articatech.net/download/openvpn-install-2.3.13-I601-x86_64.exe'>Win7 64Bits</a><br>{$connect}Win10/11 64bits</a>";
    $links["mac"]="<a href='http://articatech.net/download/Tunnelblick_3.7.0_build_4790.dmg'>OS X 10.4+</a>";
    $key=(string)(isset($ligne["ComputerOS"])?$ligne["ComputerOS"]:"");
    if(isset($links[$key])){return "<span class='ovc-nowrap'>".$links[$key]."</span>";}
    return "<span class='ovc-nowrap'><span class='ovc-dot' style='color:#10b981'></span>"
          ."&nbsp;In System</span>";
}

/** Un bouton d'action de ligne. $js vide = bouton inerte, visiblement eteint. */
function ovc_act($ico,$tone,$title,$js):string{
    if($js==""){
        return "<span class='ovc-act ovc-a-off' title='$title'><i class='$ico'></i></span>";
    }
    return "<a class='ovc-act ovc-a-$tone' href=\"javascript:blur();\" title='$title' "
          ."OnClick=\"$js\"><i class='$ico'></i></a>";
}

/**
 * Les quatre actions de ligne. Un client expire perd telechargement et
 * reconstruction -- comportement d'avant la refonte, conserve.
 */
function ovc_actions($ligne,$canManage):array{
    $page=CurrentPageName();
    $function=ovc_function();
    $uid=(string)$ligne["uid"];
    $uidEnc=urlencode($uid);
    $md5=md5(serialize($ligne));
    $zipsize=intval(isset($ligne["zipsize"])?$ligne["zipsize"]:0);
    $expired=IsExpired($ligne);

    $jsDownload="";
    if($canManage AND !$expired AND $zipsize>0){
        $jsDownload="document.location.href='$page?download=yes&uid=$uidEnc'";
    }
    $jsRebuild="";
    if($canManage AND !$expired){
        $jsRebuild="Loadjs('$page?rebuild=$uidEnc&function=$function');";
    }
    $jsFirewall="Loadjs('$page?fw-js=yes&uid=$uidEnc&function=$function')";
    $jsDelete="";
    if($canManage){
        $jsDelete="Loadjs('$page?delete-js=".urlencode(base64_encode($uid))."&id=$md5')";
    }
    return array(
        ovc_act("fas fa-download","teal","{download2}",$jsDownload),
        ovc_act("fas fa-arrow-right-to-bracket","slate","{rebuild}",$jsRebuild),
        ovc_act("fas fa-shield-halved","slate","{firewall}",$jsFirewall),
        ovc_act("fas fa-trash","red","{delete}",$jsDelete));
}

/** Une ligne du tableau. */
function ovc_row($ligne,$canManage):string{
    $md5=md5($ligne["uid"]);
    $rowid=md5(serialize($ligne));
    $acts=ovc_actions($ligne,$canManage);
    $h=array();
    $h[]="<tr id='$rowid'>";
    $h[]="<td class='ovc-nowrap'><span id='status-$md5'>".td_status($ligne)."</span></td>";
    $h[]="<td>".ovc_ident($ligne)."</td>";
    $h[]="<td class='ovc-nowrap'><span id='ipaddr-$md5'>".ovc_ip($ligne)."</span></td>";
    $h[]="<td class='ovc-nowrap'>".td_lifetime($ligne)."</td>";
    $h[]="<td class='ovc-nowrap'>".ovc_os($ligne)."</td>";
    $h[]="<td>".ovc_software($ligne)."</td>";
    foreach($acts as $a){$h[]="<td class='ovc-center'>$a</td>";}
    $h[]="</tr>";
    return @implode("",$h);
}

/**
 * 255.255.255.0 -> 24. La forme CIDR tient dans la ligne de pied la ou le
 * masque pointe la fait deborder; la valeur non convertible est rendue telle
 * quelle plutot que remplacee par un chiffre faux.
 */
function ovc_mask_to_prefix($mask){
    $mask=trim((string)$mask);
    if(preg_match('/^\d{1,2}$/',$mask)){return $mask;}
    $long=@ip2long($mask);
    if($long===false){return $mask;}
    $bits=decbin($long);
    if(!preg_match('/^1*0*$/',str_pad($bits,32,"0",STR_PAD_LEFT))){return $mask;}
    return (string)substr_count($bits,"1");
}

/** Puce du pied: le reseau distribue par le serveur OpenVPN. */
function ovc_dhcp_chip():string{
    $vpn=new openvpn();
    $global=isset($vpn->main_array["GLOBAL"])&&is_array($vpn->main_array["GLOBAL"])
        ?$vpn->main_array["GLOBAL"]:array();
    $start=trim((string)(isset($global["IP_START"])?$global["IP_START"]:""));
    $mask=trim((string)(isset($global["NETMASK"])?$global["NETMASK"]:""));
    if($start==="" OR $mask===""){return "";}
    $network=$start."/".ovc_mask_to_prefix($mask);
    return "<span class='ovc-dhcp'><span class='ovc-dot' style='color:#94a3b8'></span>"
          ."{ovc_dhcp_lease}: ".req_html($network)."</span>";
}

/** Pied du tableau: volume affiche, reseau distribue, pagination. */
function ovc_foot($shown,$total):string{
    $h=array();
    $h[]="<div class='ovc-foot'><div class='ovc-foot-l'>";
    $h[]="<span>{showing} $shown / $total</span>";
    $h[]=ovc_dhcp_chip();
    $h[]="</div><span class='awc-pg' id='ovc-pager'></span></div>";
    return @implode("",$h);
}

/* ===========================================================================
 *  Fragments
 * ======================================================================== */

/**
 * Section principale: vignettes, barre de recherche, et l'en-tete TINY qui
 * porte le segment d'etat. Le filtre est dans l'EN-TETE et non dans une bande
 * au-dessus du tableau (regle artica-webconsole-php-page 2b): une bande
 * occuperait exactement le bandeau que l'operateur depasse a chaque visite.
 */
function main_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $state=ovc_state();
    $search=(string)(isset($_GET["search"])?$_GET["search"]:"");
    $counters=ovc_counters();


    // Le terme tape est reinjecte dans le champ: sans cela un changement de
    // filtre, qui recharge toute la section, effacerait la recherche en cours.
    $prefill=null;
    if($search!==""){$prefill="value=".htmlspecialchars($search,ENT_QUOTES);}
    list($sbCss,$sb)=ovc_split_style(
        $tpl->search_block($page,null,null,$prefill,"&main=yes&state=$state"));

    $html=array();
    $html[]="<div class='ovc-wrap'>";
    $html[]=ovc_kpi_row($counters);
    $html[]=$sb;
    $html[]="</div>";




    if($counters["expired"]>0){$options["expired"]="{expired}";}

    $jstiny=tiny();


    // Un changement de filtre recharge TOUTE la section: le segment actif, les
    // vignettes et le urlToadd de search_block sont alors regeneres ensemble,
    // donc ils ne peuvent pas diverger.
    $script="<script>
function OvcState(v){LoadAjaxSilent('table-loader','$page?main-start=yes&state='+encodeURIComponent(v)+OvcTerm());}
function OvcTerm(){var e=document.querySelector('.sb-stitch input.form-control');
return e?('&search='+encodeURIComponent(e.value)):'';}
function OvcRefresh(){OvcState('$state');}
function OvcExport(){document.location.href='$page?export=csv&state=$state'+OvcTerm();}
$jstiny
</script>";

    echo ovc_style().siem_tiny_filters_style().$sbCss
        .$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;
}

function tiny():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=ovc_function();
    $fields=array();
    $state=ovc_state();

    $options=array("all"=>"{all}","connected"=>"{connected}","disconnected"=>"{disconnected}");

    $fields[]=array("TYPE"=>"segment","ID"=>"ovc-f-state","LABEL"=>"{status}",
        "VALUE"=>$state,"OPTIONS"=>$options,"JS"=>"OvcState");

    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?ruleid-js=&function=$function');",ico_plus,
        "{create_a_connection}");
    $topbuttons[]=array("Loadjs('$page?support-js=&function=$function');",ico_support,
        "{create_a_support_connection}");
    $topbuttons[]=array("OvcRefresh();",ico_refresh,"{refresh}");
    $topbuttons[]=array("OvcExport();",ico_csv,"{export}");


    $OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{members} v$OpenVPNVersion";
    $TINY_ARRAY["ICO"]="fas fa-users-crown";
    $TINY_ARRAY["EXPL"]="{BUILD_OPENVPN_CLIENT_CONFIG_TEXT}";
    $TINY_ARRAY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

}

/** Le tableau: repondeur de search_block. */
function main(){
    $tpl=new template_admin();
    $users=new usersMenus();
    $canManage=$users->AsVPNManager;
    $state=ovc_state();
    $search=(string)(isset($_GET["search"])?$_GET["search"]:"");

    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $where=ovc_where($q,$search,$state);

    $total=0;
    $cnt=$q->QUERY_SQL("SELECT COUNT(*) AS tcount FROM openvpn_clients$where");
    if(is_array($cnt) AND isset($cnt[0]["tcount"])){$total=intval($cnt[0]["tcount"]);}

    // La pagination est faite par le NAVIGATEUR (awc_pager_js): sans borne,
    // toutes les lignes partent dans le fragment et finissent par bloquer le
    // rendu -- meme classe de panne que le RESULT_CODE_HUNG de
    // fw.proxy.whitelist.php. On ne sert que les premieres.
    $LIMIT=500;
    $sql="SELECT * FROM openvpn_clients$where ORDER BY uid,ComputerOS LIMIT $LIMIT";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error_html(true,$sql);
        return;
    }
    if(!is_array($results)){$results=array();}

    $html=array();
    if($total>count($results)){
        $html[]=$tpl->div_warning("{showing} ".count($results)." / $total");
    }
    $html[]="<div class='ovc-card'><div class='ovc-scroll'>";
    $html[]="<table class='ovc-tbl'><thead><tr>";
    $html[]="<th>{status}</th>";
    $html[]="<th>{connections}</th>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>TTL</th>";
    $html[]="<th>{ComputerOS}</th>";
    $html[]="<th>{software}</th>";
    $html[]="<th class='ovc-center'>{download2}</th>";
    $html[]="<th class='ovc-center'>{rebuild}</th>";
    $html[]="<th class='ovc-center'>{firewall}</th>";
    $html[]="<th class='ovc-center'>{delete}</th>";
    $html[]="</tr></thead><tbody id='ovc-rows'>";

    $n=0;
    foreach($results as $ligne){
        if(!is_array($ligne)){continue;}
        $n++;
        $html[]=ovc_row($ligne,$canManage);
    }
    if($n==0){
        $html[]="<tr><td colspan='10' class='ovc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]=ovc_foot($n,$total);
    $html[]="</div>";
    $jstiny=tiny();
    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo ovc_style().awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny;</script>"
        .awc_pager_js(50,"ovc-rows","ovc-pager");
}

/**
 * Une cellule CSV. Un tableur interprete une valeur commencant par = + - @
 * comme une formule: le uid est saisi par un operateur, on le neutralise.
 */
function ovc_csv_safe($value):string{
    $value=(string)$value;
    if($value===""){return "";}
    if(strpos("=+-@",substr($value,0,1))!==false){return "'".$value;}
    return $value;
}

/**
 * Export CSV. Rejoue exactement la recherche et le filtre affiches, et
 * verifie le droit COTE SERVEUR: masquer le bouton ne protege rien, le
 * repartiteur repond a toute session authentifiee.
 */
function export_csv(){
    $users=new usersMenus();
    if(!$users->AsVPNManager){
        header("HTTP/1.1 403 Forbidden");
        header("Content-Type: text/plain; charset=UTF-8");
        die("Forbidden\n");
    }
    $state=ovc_state();
    $search=(string)(isset($_GET["search"])?$_GET["search"]:"");
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $where=ovc_where($q,$search,$state);
    $results=$q->QUERY_SQL("SELECT uid,ComputerOS,status,vpnip,fixip,publicip,support,"
        ."lifetime,profile,updated FROM openvpn_clients$where ORDER BY uid,ComputerOS");
    if(!is_array($results)){$results=array();}

    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=openvpn-clients-".date("Ymd-His").".csv");
    header("X-Content-Type-Options: nosniff");
    header("Cache-Control: no-cache, no-store, must-revalidate");

    $out=fopen("php://output","w");
    fputcsv($out,array("uid","os","status","vpnip","fixip","publicip","support",
        "expires","profile","updated"));
    foreach($results as $ligne){
        if(!is_array($ligne)){continue;}
        $lifetime=intval(isset($ligne["lifetime"])?$ligne["lifetime"]:0);
        fputcsv($out,array(
            ovc_csv_safe(isset($ligne["uid"])?$ligne["uid"]:""),
            ovc_csv_safe(isset($ligne["ComputerOS"])?$ligne["ComputerOS"]:""),
            intval(isset($ligne["status"])?$ligne["status"]:0),
            ovc_csv_safe(isset($ligne["vpnip"])?$ligne["vpnip"]:""),
            ovc_csv_safe(isset($ligne["fixip"])?$ligne["fixip"]:""),
            ovc_csv_safe(isset($ligne["publicip"])?$ligne["publicip"]:""),
            intval(isset($ligne["support"])?$ligne["support"]:0),
            $lifetime>0?date("Y-m-d H:i:s",$lifetime):"",
            intval(isset($ligne["profile"])?$ligne["profile"]:0),
            intval(isset($ligne["updated"])?$ligne["updated"]:0)));
    }
    fclose($out);
    admin_tracks("Exported the OpenVPN clients list (state=$state)");
}

/* ===========================================================================
 *  Cellules partagees -- SOURCE UNIQUE
 *  uid_row() les rejoue pour rafraichir une ligne depuis
 *  fw.openvpn.clients.config.php et fw.openvpn.profiles.php: la ligne
 *  rafraichie et la ligne rendue ne peuvent donc pas diverger.
 * ======================================================================== */

function td_auth($ligne):string{
    // Accumulateurs declares d'emblee: PHP 8 leve une TypeError fatale sur
    // count(null), et les deux tableaux n'etaient affectes que dans un if -- un
    // client sans authentification locale et sans profil tuait toute la page.
    $zAuth=array();
    $a=array();
    $LocalAuth=intval(isset($ligne["LocalAuth"])?$ligne["LocalAuth"]:0);
    if($LocalAuth==1){
        $zAuth[]="{UseLocalDatabase}";
    }
    if(count($zAuth)==0){
        $profile=intval(isset($ligne["profile"])?$ligne["profile"]:0);
        if($profile>0) {
            $q = new lib_sqlite("/home/artica/SQLITE/openvpn.db");
            $results = $q->QUERY_SQL("SELECT rulename FROM auths WHERE profileid=$profile ORDER BY ID LIMIT 250");
            if(is_array($results)){
                foreach ($results as $index => $row) {
                    $a[] = req_html((string)$row["rulename"]);
                }
            }
        }
        if(count($a)>0){
            return "<div class='ovc-sub ovc-mut'>{authentication}: ".@implode(", ",$a)."</div>";
        }
        return "<div class='ovc-sub ovc-warn'>{no_auth_defined}</div>";
    }
    return "<div class='ovc-sub ovc-mut'>{authentication}: ".@implode(", ",$zAuth)."</div>";
}

function td_status($ligne):string{
    if(IsExpired($ligne)) {
        return "<span class='ovc-pill ovc-p-exp'><span class='ovc-dot'></span>{expired}</span>";
    }
    $status=intval(isset($ligne["status"])?$ligne["status"]:0);
    if($status==1){
        return "<span class='ovc-pill ovc-p-on'><span class='ovc-dot'></span>{connected}</span>";
    }
    return "<span class='ovc-pill ovc-p-off'><span class='ovc-dot'></span>{disconnected}</span>";
}

function td_profile($ligne):string{
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $profile=intval(isset($ligne["profile"])?$ligne["profile"]:0);
    $userid=urlencode((string)(isset($ligne["uid"])?$ligne["uid"]:""));
    if($profile==0){
        return "<div class='ovc-sub ovc-mut'>{profile}: {none}</div>";
    }
    $row=$q->mysqli_fetch_array("SELECT rulename FROM profiles WHERE ID=$profile");
    $name=req_html((string)(isset($row["rulename"])?$row["rulename"]:""));
    if($name===""){
        return "<div class='ovc-sub ovc-mut'>{profile}: {none}</div>";
    }
    $js="Loadjs('fw.openvpn.profiles.php?ruleid-js=$profile&uid=$userid')";
    return "<div class='ovc-sub ovc-mut'>{profile}: <a class='ovc-sup' "
          ."href=\"javascript:blur();\" OnClick=\"$js\">$name</a></div>";
}

function td_fixip($ligne){
    $fixip=(string)(isset($ligne["fixip"])?$ligne["fixip"]:"");

    if($fixip=="0.0.0.0"){
        return "";
    }
    if(strlen($fixip)<3){
        return "";
    }

    $ipclass=new IP();
    if(!$ipclass->isValid($fixip)){
        return "";
    }

    return " <span class='ovc-mut'>(".req_html($fixip).")</span>";
}


function IsExpired($ligne){
    $lifetime=intval(isset($ligne["lifetime"])?$ligne["lifetime"]:0);
    if($lifetime==0){return false;}
    if($lifetime>time()){return false;}
    return true;
}

function td_lifetime($ligne){
    $lifetime=intval(isset($ligne["lifetime"])?$ligne["lifetime"]:0);
    $clock="<i class='far fa-clock'></i>";

    if($lifetime==0){
        return "<span class='ovc-chip'>$clock{unlimited}</span>";
    }
    if(time()>$lifetime) {
        return "<span class='ovc-chip' style='background:#fef2f2;border-color:#fecaca;"
              ."color:#b91c1c'>$clock{expired}</span>";
    }
    return "<span class='ovc-chip'>$clock".distanceOfTimeInWords(time(),$lifetime)."</span>";
}

function delete_script()
{
    $uid = base64_decode($_POST["delete-script"]);
    // Pentest 2026-11-04 : delete-script (uid) etait concatene dans la requete.
    $sql = "DELETE FROM vpnclient WHERE connexion_name=?";
    $q = new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $q->QUERY_SQL_PARAMS($sql,array($uid));
    if (!$q->ok) {
        echo $q->mysql_error;
        return;
    }
    $sql = "DELETE FROM openvpn_clients WHERE uid=?";
    $q->QUERY_SQL_PARAMS($sql,array($uid));
    $sql = "DELETE FROM memberlinks WHERE connection=?";
    $q->QUERY_SQL_PARAMS($sql,array($uid));
}


function FixConnectionName($connection_name):string{
    if($connection_name==null){$connection_name=time();}
    $connection_name=replace_accents($connection_name);
    $connection_name=str_replace("/", "-", $connection_name);
    $connection_name=str_replace('\\', "-", $connection_name);
    $connection_name=str_replace("&","",$connection_name);
    $connection_name=str_replace(",","",$connection_name);
    $connection_name=str_replace(";","",$connection_name);
    $connection_name=str_replace("%","",$connection_name);
    $connection_name=str_replace("*","",$connection_name);
    $connection_name=str_replace("ø","",$connection_name);
    $connection_name=str_replace("$","",$connection_name);
    $connection_name=str_replace("/","",$connection_name);
    $connection_name=str_replace("\\","",$connection_name);
    $connection_name=str_replace("?","",$connection_name);
    $connection_name=str_replace("µ","",$connection_name);
    $connection_name=str_replace("£","",$connection_name);
    $connection_name=str_replace(")","",$connection_name);
    $connection_name=str_replace("(","",$connection_name);
    $connection_name=str_replace("[","",$connection_name);
    $connection_name=str_replace("]","",$connection_name);
    $connection_name=str_replace("#","",$connection_name);
    $connection_name=str_replace("'","",$connection_name);
    $connection_name=str_replace("\"","",$connection_name);
    $connection_name=str_replace("+","_",$connection_name);
    $connection_name=str_replace(" ",".",$connection_name);
    return $connection_name;

}

function buildconfig():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $connection_name=FixConnectionName($_POST["connection_name"]);

	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");

    // Pentest 2026-11-04 : ComputerOS etait concatene dans la requete.
    $sql="INSERT OR IGNORE INTO `openvpn_clients` (uid,ComputerOS) VALUES (?,?)";
    $params=array($connection_name,strval($_POST["ComputerOS"]));

    if($_POST["ComputerOS"]=="artica"){
        $username=generateRandomSerial();
        $password=generateRandomSerial();
        $sql="INSERT OR IGNORE INTO `openvpn_clients` (uid,ComputerOS,username,password,LocalAuth) VALUES (?,?,?,?,1)";
        $params=array($connection_name,"artica",$username,$password);
    }
    $q->QUERY_SQL_PARAMS($sql,$params);
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}

	$sock=new sockets();
    $data=$sock->REST_API("/openvpn/client/build/$connection_name");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg()."<br>$sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }

    return admin_tracks("Created a new OpenVPN client connection $connection_name");

}
function download(){
	$uid=$_GET["uid"];
	header("Content-Type:  application/zip");
	header("Content-Disposition: attachment; filename=".preg_replace('#[^A-Za-z0-9_.@-]#','_',(string)$uid).".zip"); // Pentest 2026-11-04 : injection d'en-tete
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
	header("Pragma: no-cache"); // HTTP 1.0
	header("Expires: 0"); // Proxies
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé

	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");

	
	//$ligne=$q->mysqli_fetch_array("SELECT zipcontent,zipsize FROM openvpn_clients WHERE uid='$uid'");
	//$ligne=$q->QUERY_SQL("SELECT zipcontent,zipsize FROM openvpn_clients WHERE uid='$uid'");
	//$ligne=mysqli_fetch_array($res);
	// Pentest 2026-11-04 : uid etait concatene dans la requete.
	$results=$q->QUERY_SQL_PARAMS("SELECT zipcontent,zipsize FROM openvpn_clients WHERE uid=?",array($uid));
	$ligne=(is_array($results) && isset($results[0]))?$results[0]:array();


	if(!$q->ok){exit;}
	header("Content-Length: ".$ligne["zipsize"]);
	ob_clean();
	flush();
	echo base64_decode($ligne["zipcontent"]);
}