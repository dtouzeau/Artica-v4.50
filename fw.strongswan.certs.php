<?php
// Service Pack 9 2026-10-10 // Service Pack 9
// fw.strongswan.certs.php — IPsec VPN certificates: each entry is a private authority (10 years)
// plus a server certificate signed by it (5 years), RSA 4096, made by articarest module
// strongswancerts (GET /strongswan/certificates[/build/{name}/{cn}/{id}|/delete/{id}]), the Go
// port of exec.strongswan.php build_cert()/cert_file_remove() (2026-11-22).
//
// Stitch redesign 2026-11-22 (stitch_vpn_certificate_manager_redesign). REAL DATA ONLY:
//   /home/artica/SQLITE/strongswan.db strongswan_certs (ID, name, cn, ca_*, server_*,
//   ca_cert_content = serialized authority PEM, base64). Dates and fingerprint are read from that
//   PEM (openssl_x509_parse); the server certificate dates come from GET /strongswan/certificates
//   (the daemon reads /etc/ipsec.d/certs), and fall back to creation + 1825 days, shown as
//   "about", only when the daemon does not answer. Tunnels using a certificate come from
//   strongswan_conns.params (leftcert = server-<name>-cert.pem).
// The host (fw-objects-table, ?build-table=yes) is kept: the creation progress reloads it.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc"); if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus(); if (!$users->AsFirewallManager) {
    exit();
}
include_once(dirname(__FILE__)."/framework/class.unix.inc");
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if(isset($_GET["download"])){download();exit;}
if (isset($_POST["object-save"])) {save_object();exit;}
if (isset($_GET["build-table"])) {build_table();exit;}
if (isset($_GET["new-object"])) {new_object();exit;}
if (isset($_GET["delete-js"])) {delete_js();exit;}
if (isset($_POST["delete-remove"])) {delete_remove();exit;}
if (isset($_GET["newrule-js"])) {new_rule_js();exit;}
if(isset($_GET["build-progress"])){build_progress();exit;}
build_page();

// The values are validated again here (and by articarest) and travel URL-encoded in the route.
function build_progress(){
    $DATAS=@unserialize((string)($_SESSION["POSTFORMED"] ?? ""),array("allowed_classes"=>false));
    if(!is_array($DATAS) || !sswc_valid_name((string)($DATAS["cert-name"] ?? "")) || !sswc_valid_cn((string)($DATAS["cn"] ?? ""))){
        header("content-type: application/x-javascript");
        return;
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($DATAS["object-save"] ?? 0);
    $formid=preg_replace('/[^a-z0-9\-]/i','',(string)($DATAS["form-id"] ?? ""));
    $route="/strongswan/certificates/build/".rawurlencode($DATAS["cert-name"])."/".rawurlencode($DATAS["cn"])."/$id";
    header("content-type: application/x-javascript");
    echo $tpl->framework_buildjs($route,"strongswan.build.progress","strongswan.build.log",$formid,
        "BootstrapDialog1.close();LoadAjax('fw-objects-table','$page?build-table=yes');");
}

function build_page()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html="
    <div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_STRONGSWAN} {certificates}</h1><p>{sswc_header_explain}</p></div>
	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content' style='background:#f6f8f9'>
    <div id='fw-objects-table' class='sswc' aria-live='polite'></div>
    </div></div>
    <script>
        $.address.state('/');
	    $.address.value('/ipsec-certificates');
        LoadAjax('fw-objects-table','$page?build-table=yes');
    </script>";
    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Certificates',$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Validation (shared by the form and the progress) ──────────────────────

function sswc_valid_name(string $name):bool{
    return $name!=="" && strlen($name)<=64 && !preg_match('#[^a-zA-Z0-9]#',$name);
}
function sswc_valid_cn(string $cn):bool{
    if(filter_var($cn,FILTER_VALIDATE_IP)){return true;}
    return strlen($cn)<=253 && (bool)preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,63}$/',$cn);
}

// ── Model ─────────────────────────────────────────────────────────────────

function sswc_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function sswc_tr(string $tok,array $repl=array()):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body("{".$tok."}");
    foreach($repl as $k=>$v){$t=str_replace($k,(string)$v,$t);}
    return $t;
}

// The authority PEM stored in the row (serialized, then base64): "" when unreadable.
function sswc_pem(string $stored):string{
    $raw=base64_decode($stored,true);
    if($raw===false){return "";}
    $pem=@unserialize($raw,array("allowed_classes"=>false));
    return is_string($pem)&&strpos($pem,"-----BEGIN CERTIFICATE-----")!==false?$pem:"";
}

// Tunnels per server certificate file name, from strongswan_conns.params.
function sswc_tunnels_by_cert():array{
    $out=array();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $res=$q->QUERY_SQL("SELECT ID,conn_name,params,enable FROM strongswan_conns");
    foreach((is_array($res)?$res:array()) as $l){
        $params=@unserialize((string)base64_decode((string)($l["params"] ?? "")),array("allowed_classes"=>false));
        if(!is_array($params)){continue;}
        foreach($params as $p){
            if(!is_array($p) || ($p["name"] ?? "")!=="leftcert"){continue;}
            $val="";
            if(($p["type"] ?? "")==="select" && is_array($p["values"] ?? null)){
                foreach($p["values"] as $v){ if(is_array($v) && !empty($v["selected"])){$val=(string)($v["value"] ?? "");} }
            }else{
                $val=(string)($p["value"] ?? "");
            }
            if($val!==""){$out[$val][]=array("name"=>(string)($l["conn_name"] ?? ""),"enabled"=>intval($l["enable"] ?? 0)==1);}
        }
    }
    return $out;
}

// Real dates read by the daemon (it can read /etc/ipsec.d/certs, the console cannot), by id.
function sswc_daemon_dates():array{
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/strongswan/certificates"),true);
    $out=array();
    if(!is_array($j) || empty($j["Status"]) || !is_array($j["certificates"] ?? null)){return $out;}
    foreach($j["certificates"] as $c){
        if(is_array($c) && isset($c["id"])){$out[intval($c["id"])]=$c;}
    }
    return $out;
}

function sswc_model():array{
    $m=array("rows"=>array(),"error"=>"");
    $daemon=sswc_daemon_dates();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $res=$q->QUERY_SQL("SELECT ID,name,cn,server_cert,ca_cert_content FROM strongswan_certs ORDER BY ID DESC");
    if(!$q->ok){$m["error"]=(string)$q->mysql_error;return $m;}
    $usage=sswc_tunnels_by_cert();
    foreach((is_array($res)?$res:array()) as $l){
        $r=array("id"=>intval($l["ID"] ?? 0),"name"=>(string)($l["name"] ?? ""),"cn"=>(string)($l["cn"] ?? ""),
            "created"=>0,"ca_until"=>0,"srv_until"=>0,"srv_exact"=>false,"srv_missing"=>false,"fp"=>"","tunnels"=>array());
        $pem=sswc_pem((string)($l["ca_cert_content"] ?? ""));
        if($pem!==""){
            $x=@openssl_x509_parse($pem);
            if(is_array($x)){
                $r["created"]=intval($x["validFrom_time_t"] ?? 0);
                $r["ca_until"]=intval($x["validTo_time_t"] ?? 0);
                // build_cert(): the server certificate is issued with --lifetime 1825 at creation.
                if($r["created"]>0){$r["srv_until"]=$r["created"]+1825*86400;}
            }
            $fp=@openssl_x509_fingerprint($pem,"sha256");
            if(is_string($fp)){$r["fp"]=strtoupper(implode(":",str_split($fp,2)));}
        }
        $d=$daemon[$r["id"]] ?? null;
        if(is_array($d)){
            if(!empty($d["server_missing"])){
                $r["srv_missing"]=true;
            }elseif(intval($d["server_not_after"] ?? 0)>0){
                $r["srv_until"]=intval($d["server_not_after"]);
                $r["srv_exact"]=true;
            }
        }
        $file=(string)($l["server_cert"] ?? "").".pem";
        if(isset($usage[$file])){$r["tunnels"]=$usage[$file];}
        $m["rows"][]=$r;
    }
    return $m;
}

// Valid / expires soon (under 90 days) / expired / unknown, judged on the server certificate.
function sswc_status(array $r):string{
    if($r["srv_missing"]){return "missing";}
    if($r["srv_until"]<=0){return "unknown";}
    $left=$r["srv_until"]-time();
    if($left<=0){return "expired";}
    if($left<90*86400){return "soon";}
    return "ok";
}

function sswc_in(int $ts):string{
    $d=$ts-time();
    if($d<=0){return sswc_tr("sswc_ago_days",array("%s"=>max(1,intval(-$d/86400))));}
    if($d<86400*60){return sswc_tr("sswc_in_days",array("%s"=>max(1,intval($d/86400))));}
    if($d<86400*730){return sswc_tr("sswc_in_months",array("%s"=>intval(round($d/(86400*30)))));}
    return sswc_tr("sswc_in_years",array("%s"=>intval(round($d/(86400*365)))));
}

function sswc_date(int $ts):string{
    return $ts>0?date("Y-m-d",$ts):"&mdash;";
}

// ── Host ──────────────────────────────────────────────────────────────────

function build_table()
{
    $tpl=new template_admin();
    $m=sswc_model();
    $h=array();
    $h[]=sswc_banner();
    if($m["error"]!=""){
        $h[]="<div class='sswc-alert' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i><span>".sswc_h($m["error"])."</span></div>";
    }elseif(count($m["rows"])==0){
        $h[]=sswc_steps();
        $h[]=sswc_empty();
    }else{
        $h[]="<div class='sswc-sec-head'><h3 class='sswc-sec-t'>{sswc_list_title}</h3><span class='sswc-pill sswc-pill-accent'>"
            .sswc_tr("sswc_count",array("%s"=>count($m["rows"])))."</span></div>";
        $cards="";
        foreach($m["rows"] as $r){$cards.=sswc_card($r);}
        $h[]="<div class='sswc-grid'>$cards</div>";
    }
    // Style and helpers travel with the host: the main-page route renders build_page() through
    // build_firewall(), which would drop anything echoed beside it.
    echo sswc_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).sswc_js(CurrentPageName())."<script>NoSpinner();</script>";
}

// ── Widgets ───────────────────────────────────────────────────────────────

function sswc_new_button():string{
    return "<button type='button' class='sswc-btn' onclick=\"Loadjs('".CurrentPageName()."?newrule-js=yes&cert-id=0')\"><i class='fas fa-plus' aria-hidden='true'></i>{new_certificate}</button>";
}

function sswc_banner():string{
    $chips=array(array("fas fa-lock","{sswc_chip_keys}"),array("fas fa-file-shield","{sswc_chip_nosecret}"),array("fas fa-calendar-check","{sswc_chip_validity}"));
    $c="";
    foreach($chips as $ch){$c.="<span class='sswc-chip'><i class='{$ch[0]}' aria-hidden='true'></i>{$ch[1]}</span>";}
    return "<section class='sswc-banner'><div class='sswc-banner-ico' aria-hidden='true'><i class='fas fa-shield-halved'></i></div>"
        ."<div class='sswc-banner-txt'><h2 class='sswc-banner-t'>{sswc_banner_title}</h2><p class='sswc-banner-p'>{sswc_banner_text}</p><div class='sswc-chips'>$c</div></div>"
        ."<div class='sswc-banner-act'>".sswc_new_button()
        ."<button type='button' class='sswc-btn-q' onclick=\"s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')\"><i class='fas fa-headset' aria-hidden='true'></i>{online_help}</button></div></section>";
}

function sswc_steps():string{
    $s=array("{sswc_step1}","{sswc_step2}","{sswc_step3}");
    $li="";
    foreach($s as $i=>$t){$li.="<li><span class='sswc-step-n'>".($i+1)."</span><span>$t</span></li>";}
    return "<section class='sswc-card sswc-steps'><h3 class='sswc-card-t'>{sswc_steps_title}</h3><ol>$li</ol></section>";
}

function sswc_empty():string{
    return "<section class='sswc-card sswc-empty'><div class='sswc-empty-ico' aria-hidden='true'><i class='fas fa-certificate'></i></div>"
        ."<h3 class='sswc-card-t'>{sswc_empty_title}</h3><p class='sswc-muted'>{sswc_empty_text}</p>".sswc_new_button()."</section>";
}

function sswc_status_pill(string $st):string{
    $map=array("ok"=>array("sswc-pill-ok","fas fa-circle-check","{sswc_valid}"),
        "soon"=>array("sswc-pill-warn","fas fa-clock","{sswc_soon}"),
        "expired"=>array("sswc-pill-bad","fas fa-circle-xmark","{sswc_expired}"),
        "missing"=>array("sswc-pill-bad","fas fa-file-circle-xmark","{sswc_missing}"),
        "unknown"=>array("sswc-pill","fas fa-circle-question","{sswc_unknown}"));
    $x=$map[$st];
    return "<span class='sswc-pill $x[0]'><i class='$x[1]' aria-hidden='true'></i>$x[2]</span>";
}

function sswc_card(array $r):string{
    $page=CurrentPageName();
    $st=sswc_status($r);
    $isIp=filter_var($r["cn"],FILTER_VALIDATE_IP)!==false;
    $type=$isIp?"{sswc_type_ip}":"{sswc_type_host}";
    $srvCls=$st=="soon"?" sswc-warn":($st=="expired"?" sswc-bad":"");
    if($r["srv_missing"]){
        $srv="<b class='sswc-bad'>{sswc_srv_missing}</b>";
    }elseif($r["srv_until"]>0){
        $date=$r["srv_exact"]?sswc_date($r["srv_until"]):sswc_tr("sswc_about",array("%s"=>sswc_date($r["srv_until"])));
        $srv="<b class='$srvCls'>$date</b><small class='$srvCls'>".sswc_in($r["srv_until"])."</small>";
    }else{
        $srv="<b>&mdash;</b>";
    }
    $ca=$r["ca_until"]>0?"<b>".sswc_date($r["ca_until"])."</b><small>".sswc_tr("sswc_created",array("%s"=>sswc_date($r["created"])))."</small>":"<b>&mdash;</b>";
    $tun="";
    foreach($r["tunnels"] as $t){
        $tun.="<span class='sswc-tun".($t["enabled"]?"":" sswc-tun-off")."'><span class='sswc-dot' aria-hidden='true'></span>".sswc_h($t["name"])
            .($t["enabled"]?"":" <small>{sswc_disabled}</small>")."</span>";
    }
    if($tun==""){$tun="<span class='sswc-tun sswc-tun-none'><i class='fas fa-link-slash' aria-hidden='true'></i>{sswc_not_used}</span>";}
    $fp=$r["fp"]!==""
        ?"<div class='sswc-fp'><span class='sswc-mono sswc-fp-v' title='".sswc_h($r["fp"])."' data-copy='".sswc_h($r["fp"])."'>".sswc_h(substr($r["fp"],0,23))."&hellip;".sswc_h(substr($r["fp"],-8))."</span>"
         ."<button type='button' class='sswc-link' aria-label=\"{sswc_copy_fp}\" onclick='SswcCopy(this)'><i class='fas fa-copy' aria-hidden='true'></i><span>{sswc_copy}</span></button></div><span class='sswc-help'>{sswc_fp_help}</span>"
        :"<span class='sswc-muted'>&mdash;</span>";
    $id=$r["id"];
    return "<article class='sswc-card sswc-cert'><div class='sswc-cert-head'><div><h4>".sswc_h($r["name"])."</h4><span class='sswc-pill'>#$id</span></div>".sswc_status_pill($st)."</div>"
        ."<div class='sswc-cert-body'>"
        ."<div class='sswc-field'><div class='sswc-field-l'><span>{sswc_address}</span><span class='sswc-pill'>$type</span></div>"
        ."<div class='sswc-addr sswc-mono'>".sswc_h($r["cn"])."</div><span class='sswc-help'>{sswc_address_help}</span></div>"
        ."<div class='sswc-dates'><div><span class='sswc-field-l'>{sswc_srv_until}</span>$srv</div><div><span class='sswc-field-l'>{sswc_ca_until}</span>$ca</div>"
        ."<div><span class='sswc-field-l'>{sswc_spec}</span><b><span class='sswc-pill sswc-pill-line'>RSA 4096</span></b></div></div>"
        ."<div class='sswc-field'><div class='sswc-field-l'><span>{sswc_used_by}</span></div><div class='sswc-tuns'>$tun</div></div>"
        ."<div class='sswc-field'><div class='sswc-field-l'><span>{sswc_fingerprint}</span></div>$fp</div>"
        ."</div><div class='sswc-cert-foot'><div>".sswc_download_button($r)."</div>"
        ."<div class='sswc-foot-acts'><button type='button' class='sswc-btn-q' onclick=\"Loadjs('$page?newrule-js=yes&cert-id=$id')\"><i class='fas fa-pen' aria-hidden='true'></i>{sswc_change}</button>"
        ."<button type='button' class='sswc-del' onclick=\"Loadjs('$page?delete-js=$id')\"><i class='fas fa-trash' aria-hidden='true'></i>{sswc_delete}</button></div></div></article>";
}

// Only offered when the stored authority certificate is readable (download() would 404 otherwise).
function sswc_download_button(array $r):string{
    if($r["fp"]===""){return "<span class='sswc-help'>{sswc_no_pem}</span>";}
    $page=CurrentPageName();
    return "<a class='sswc-btn' href='".sswc_h("$page?download=yes&ID={$r["id"]}")."'><i class='fas fa-download' aria-hidden='true'></i>{sswc_download}</a>"
        ."<span class='sswc-help'>".sswc_tr("sswc_download_help",array("%s"=>sswc_h("ca-".$r["name"]."-cert.pem")))."</span>";
}

// ── Dialogs ───────────────────────────────────────────────────────────────

function new_rule_js()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["cert-id"]);
    $tpl->js_dialog($ID==0?"{new_certificate}":"{sswc_change}","$page?new-object=yes&cert-id=$ID");
}

function sswc_row(int $id):array{
    if($id<1){return array();}
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $l=$q->mysqli_fetch_array("SELECT ID,name,cn,server_cert FROM strongswan_certs WHERE ID=$id");
    return (is_array($l)&&isset($l["ID"]))?$l:array();
}

// Names of the tunnels using one certificate row (for the warnings before regenerate/delete).
function sswc_tunnel_names(array $row):array{
    $usage=sswc_tunnels_by_cert();
    $file=(string)($row["server_cert"] ?? "").".pem";
    $names=array();
    foreach(($usage[$file] ?? array()) as $t){$names[]=$t["name"];}
    return $names;
}

function new_object()
{
    $page=CurrentPageName();
    $ID=intval($_GET["cert-id"]);
    $tpl=new template_admin();
    $t=time();
    $jsAfter="LoadAjax('fw-objects-table','$page?build-table=yes');";
    $BootstrapDialog="BootstrapDialog1.close();";
    $warn="";
    if ($ID==0) {
        $btname="{sswc_create}";
        $title="{sswc_new_intro}";
        $tpl->field_hidden("object-save", 0);
        $form[]=$tpl->field_text("cert-name", "{sswc_f_name}", null, true, "{sswc_f_name_help}");
        $form[]=$tpl->field_text("cn", "{sswc_f_cn}", null, true, "{sswc_f_cn_help}");
    } else {
        $row=sswc_row($ID);
        if(count($row)==0){echo $tpl->div_error("{sswc_gone}");return;}
        $btname="{sswc_regenerate}";
        $title="";
        $names=sswc_tunnel_names($row);
        $warn=$tpl->div_error("{sswc_regen_title}||".sswc_tr("sswc_regen_text")
            .(count($names)>0?"<br>".sswc_tr("sswc_regen_tunnels",array("%s"=>sswc_h(implode(", ",$names)))):""));
        $tpl->field_hidden("object-save", $ID);
        $tpl->field_hidden("cert-name", $row["name"]);
        $form[]=$tpl->field_text("cn", "{sswc_f_cn}", (string)$row["cn"], true, "{sswc_f_cn_help}");
    }
    $tpl->form_add_button("{cancel}", $BootstrapDialog);
    $tpl->field_hidden("form-id","form-$t");
    $html=array();
    $html[]=$warn;
    $html[]="<div id='form-$t'></div>";
    $html[]=$tpl->form_outside($title, @implode("\n", $form), null, $btname, "Loadjs('$page?build-progress=yes');", "AsFirewallManager");
    echo $tpl->_ENGINE_parse_body($html);
}

// Validates, then hands the values to build-progress through the session (as before).
function save_object()
{
    $tpl=new template_admin();
    $ID=intval($_POST["object-save"]);
    $cn=trim((string)$_POST["cn"]);
    $name=trim((string)$_POST["cert-name"]);
    if ($ID>0) {
        // The name of an existing certificate never comes from the form.
        $row=sswc_row($ID);
        if(count($row)==0){echo $tpl->post_error($tpl->_ENGINE_parse_body("{sswc_gone}"));return;}
        $name=(string)$row["name"];
    }
    if (!sswc_valid_name($name)) {
        echo $tpl->post_error($tpl->_ENGINE_parse_body("{sswc_err_name}"));
        return;
    }
    if (!sswc_valid_cn($cn)) {
        echo $tpl->post_error($tpl->_ENGINE_parse_body("{sswc_err_cn}"));
        return;
    }
    if ($ID==0) {
        $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
        $res=$q->QUERY_SQL_PARAMS("SELECT ID FROM strongswan_certs WHERE name=?",array($name));
        if (is_array($res) && count($res)>0) {
            echo $tpl->post_error($tpl->_ENGINE_parse_body("{sswc_err_exists}"));
            return;
        }
    }
    $_POST["cert-name"]=$name;
    $_POST["cn"]=$cn;
    $_SESSION["POSTFORMED"]=serialize($_POST);
    admin_tracks(($ID==0?"Create":"Regenerate")." the IPsec certificate $name for $cn");
}

function delete_js()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["delete-js"]);
    $row=sswc_row($ID);
    header("content-type: application/x-javascript");
    if(count($row)==0){$tpl->js_error("{sswc_gone}");return;}
    $names=sswc_tunnel_names($row);
    $text="<b>".sswc_h($row["name"])."</b> (".sswc_h($row["cn"]).")<br>"
        .(count($names)>0?sswc_tr("sswc_del_used",array("%n"=>count($names),"%s"=>sswc_h(implode(", ",$names)))):sswc_tr("sswc_del_unused"))
        ."<br>".sswc_tr("sswc_del_final");
    $tpl->js_confirm_delete($text,"delete-remove",strval($ID),"LoadAjax('fw-objects-table','$page?build-table=yes');",true);
}

// Echoes nothing on success, the error otherwise.
function delete_remove()
{
    $ID=intval($_POST["delete-remove"]);
    $row=sswc_row($ID);
    if(count($row)==0){return;}
    // articarest removes the four files and the row, then rebuilds ipsec.secrets.
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/strongswan/certificates/delete/$ID"),true);
    if(!is_array($j)){$tpl=new template_admin();echo $tpl->_ENGINE_parse_body("{sswc_err_unreachable}");return;}
    if(empty($j["Status"])){echo sswc_h((string)($j["Error"] ?? "error"));return;}
    admin_tracks("Delete the IPsec certificate ".$row["name"]." (".$row["cn"].")");
}

// The authority certificate (public, no secret inside), exactly as stored.
function download(){
    $ID=intval($_GET["ID"]);
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $ligne=$q->mysqli_fetch_array("SELECT name,ca_cert_content FROM strongswan_certs WHERE ID=$ID");
    $pem=is_array($ligne)?sswc_pem((string)($ligne["ca_cert_content"] ?? "")):"";
    if($pem===""){header("HTTP/1.1 404 Not Found");return;}
    $name=preg_replace('/[^A-Za-z0-9]/','',(string)$ligne["name"]);
    header("Content-Type: application/x-pem-file");
    header("Content-Disposition: attachment; filename=\"ca-$name-cert.pem\"");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Content-Length: ".strlen($pem));
    if(ob_get_level()>0){ob_clean();}
    flush();
    echo $pem;
}

// ── JS / CSS ──────────────────────────────────────────────────────────────

function sswc_js(string $page):string{
    $tpl=new template_admin();
    $copied=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{sswc_copied}"),ENT_QUOTES,"UTF-8"));
    return "<script>
window.SswcCopy=function(btn){
    var v=btn.parentNode.querySelector('[data-copy]'); if(!v){return;}
    var t=v.getAttribute('data-copy');
    var done=function(){ var s=btn.querySelector('span'); var o=s?s.textContent:''; if(s){s.textContent=$copied;}
        setTimeout(function(){ if(s){s.textContent=o;} },2000); };
    var fallback=function(){ var x=document.createElement('textarea'); x.value=t; x.setAttribute('readonly',''); x.style.position='absolute'; x.style.left='-9999px';
        document.body.appendChild(x); x.select(); try{document.execCommand('copy');done();}catch(e){} document.body.removeChild(x); };
    if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(t).then(done,fallback); } else { fallback(); }
};
</script>";
}

function sswc_style():string{
    if(isset($GLOBALS["SSWC_STYLE"])){return "";}
    $GLOBALS["SSWC_STYLE"]=true;
    $accent="#005447";$soft="#e6eeed";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.sswc{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px}
.sswc *{box-sizing:border-box}
.sswc h2,.sswc h3,.sswc h4,.sswc p,.sswc ol{margin:0}
.sswc-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.sswc-card-t{font-size:17px;font-weight:600}
.sswc-muted{color:#475569;font-size:14px;line-height:1.55;margin-top:6px!important}
.sswc-help{display:block;color:#64748b;font-size:13px;line-height:1.45;margin-top:4px}
.sswc-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
.sswc-banner{display:flex;gap:18px;align-items:flex-start;background:$soft;border:1px solid #bfd4d1;border-radius:16px;padding:24px}
.sswc-banner-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px}
.sswc-banner-txt{flex:1;min-width:0}
.sswc-banner-t{font-size:20px;font-weight:600;line-height:1.3}
.sswc-banner-p{font-size:14px;line-height:1.55;color:#334155;margin-top:6px!important;max-width:780px}
.sswc-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.sswc-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#334155;background:#fff;border:1px solid #bfd4d1;border-radius:999px;padding:4px 12px}
.sswc-chip i,.sswc-chip svg{color:$accent}
.sswc-banner-act{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.sswc-btn{display:inline-flex;align-items:center;gap:8px;background:$accent;color:#fff!important;border:0;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;text-decoration:none!important}
.sswc-btn:hover{background:#003d33}
.sswc-btn-q{display:inline-flex;align-items:center;gap:8px;background:#fff;color:#0f172a;border:1px solid #cbd5e1;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.sswc-btn-q:hover{border-color:$accent;color:$accent}
.sswc-link{display:inline-flex;align-items:center;gap:6px;background:none;border:0;color:$accent;font-weight:600;font-size:13px;cursor:pointer;padding:2px 0}
.sswc-del{display:inline-flex;align-items:center;gap:6px;background:none;border:0;color:#b91c1c;font-weight:600;font-size:13px;cursor:pointer;padding:6px 4px}
.sswc-del:hover{text-decoration:underline}
.sswc-alert{display:flex;gap:10px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;font-size:13px;color:#7f1d1d;word-break:break-word}
.sswc-sec-head{display:flex;align-items:center;gap:10px}
.sswc-sec-t{font-size:17px;font-weight:600}
.sswc-pill{display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:500;border-radius:999px;padding:2px 10px;white-space:nowrap;background:#f1f5f9;color:#334155}
.sswc-pill-accent{background:$soft;color:$accent;border:1px solid #bfd4d1}
.sswc-pill-ok{background:#dcfce7;color:#166534}
.sswc-pill-warn{background:#fef3c7;color:#92400e}
.sswc-pill-bad{background:#fee2e2;color:#991b1b}
.sswc-pill-line{background:#fff;border:1px solid #bfd4d1;color:$accent}
.sswc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.sswc-cert{display:flex;flex-direction:column}
.sswc-cert-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid #f1f5f9}
.sswc-cert-head>div{display:flex;align-items:center;gap:10px;min-width:0}
.sswc-cert-head h4{font-size:17px;font-weight:600;word-break:break-all}
.sswc-cert-body{padding:18px 22px;display:flex;flex-direction:column;gap:16px;flex:1}
.sswc-field-l{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:13px;font-weight:600;color:#334155;text-transform:uppercase;letter-spacing:.03em;margin-bottom:6px}
.sswc-addr{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;font-size:14px;font-weight:600;word-break:break-all}
.sswc-dates{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;background:#f8fafc;border:1px solid #eef2f6;border-radius:10px;padding:14px 16px}
.sswc-dates .sswc-field-l{text-transform:none;letter-spacing:0;font-weight:500;color:#64748b;margin-bottom:4px}
.sswc-dates b{display:block;font-size:14px;font-weight:600}
.sswc-dates small{display:block;font-size:13px;color:#64748b;margin-top:2px}
.sswc-warn{color:#b45309!important}.sswc-bad{color:#b91c1c!important}
.sswc-tuns{display:flex;flex-wrap:wrap;gap:8px}
.sswc-tun{display:inline-flex;align-items:center;gap:6px;font-size:13px;border-radius:8px;padding:5px 10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.sswc-tun .sswc-dot{width:7px;height:7px;border-radius:50%;background:#15803d;display:inline-block}
.sswc-tun-off{background:#f8fafc;border-color:#e2e8f0;color:#475569}
.sswc-tun-off .sswc-dot{background:#94a3b8}
.sswc-tun small{font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase}
.sswc-tun-none{background:#f8fafc;border-color:#e2e8f0;color:#64748b}
.sswc-fp{display:flex;justify-content:space-between;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px}
.sswc-fp-v{font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.sswc-cert-foot{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;padding:16px 22px;border-top:1px solid #f1f5f9;background:#fafbfc;border-radius:0 0 12px 12px}
.sswc-foot-acts{display:flex;gap:8px;align-items:center}
.sswc-steps{padding:20px 24px}
.sswc-steps ol{list-style:none;padding:0!important;margin-top:12px!important;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.sswc-steps li{display:flex;gap:10px;align-items:flex-start;font-size:14px;line-height:1.45}
.sswc-step-n{flex:0 0 28px;height:28px;border-radius:50%;background:$soft;color:$accent;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:13px}
.sswc-empty{text-align:center;padding:40px 24px;display:flex;flex-direction:column;align-items:center;gap:6px}
.sswc-empty-ico{width:64px;height:64px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:8px}
.sswc-empty .sswc-btn{margin-top:12px}
.sswc-btn:focus-visible,.sswc-btn-q:focus-visible,.sswc-link:focus-visible,.sswc-del:focus-visible{outline:2px solid $accent;outline-offset:2px}
@media(max-width:1200px){.sswc-grid{grid-template-columns:minmax(0,1fr)}.sswc-banner{flex-wrap:wrap}.sswc-steps ol{grid-template-columns:minmax(0,1fr)}}
@media (prefers-reduced-motion:no-preference){.sswc-btn,.sswc-btn-q{transition:all .15s}}
</style>";
}
