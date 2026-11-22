<?php
// Onglet « Firewall services » d'une règle (maquette stitch_firewall_rules_service).
// Services liés : servicesContainer de /firewall/iptables-rules/{id} ; écriture :
// PUT /firewall/config/rules/{id}/services (remplace la liste entière) ;
// catalogue : /firewall/config/services. Un service lié mais désactivé ou absent
// du catalogue est ignoré par le démon à la construction de la règle.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() : feuille de style partagée des tableaux d'entités
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
// Verrou de la politique Artica Meta "firewall" : regles tenues = services de la regle en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["build-table"])){build_table();exit;}
if(isset($_GET["link-js"])){fws_link_js();exit;}
if(isset($_GET["link-popup"])){fws_link_popup();exit;}
if(isset($_POST["object-link"])){save_link_object();exit;}
if(isset($_GET["new-js"])){fws_new_js();exit;}
if(isset($_GET["new-popup"])){fws_new_popup();exit;}
if(isset($_POST["object-save"])){save_object();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["fws-remove"])){fws_remove();exit;}
build_page();

// Services d'une règle : lus par la route de lecture squid (le conteneur est un
// format interne que fwconfig.Rule n'expose pas), réécrits entiers par l'API.
function rule_services_get(int $ID):array{
    $rule=acls_api_get("/firewall/iptables-rules/$ID");
    $MAIN=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($rule["servicesContainer"] ?? "");
    $out=array();
    if(!is_array($MAIN)){return $out;}
    foreach(array_keys($MAIN) as $n){$n=trim((string)$n);if($n!==""){$out[]=$n;}}
    return $out;
}
function rule_services_put(int $ID,array $names):string{
    return fwapi_error(fwapi_send("PUT","rules/$ID/services",array("services"=>array_values(array_unique($names)))));
}

/** Catalogue : nom => [ports serveur, ports client, activé]. */
function fws_catalog():array{
    if(isset($GLOBALS["FWS_CATALOG"])){return $GLOBALS["FWS_CATALOG"];}
    $out=array();
    $list=fwapi_data(fwapi_get("services"));
    if(is_array($list)){
        foreach($list as $s){
            if(!is_array($s)){continue;}
            $n=trim((string)($s["service"] ?? ""));
            if($n===""){continue;}
            $out[$n]=array("server"=>trim((string)($s["serverPort"] ?? "")),"client"=>trim((string)($s["clientPort"] ?? "")),"enabled"=>intval($s["enabled"] ?? 0)==1);
        }
    }
    ksort($out,SORT_NATURAL|SORT_FLAG_CASE);
    $GLOBALS["FWS_CATALOG"]=$out;
    return $out;
}

function fws_model(int $ID):array{
    $cat=fws_catalog();
    $rows=array();
    foreach(rule_services_get($ID) as $n){
        $state="missing";
        if(isset($cat[$n])){$state=$cat[$n]["enabled"]?"active":"disabled";}
        $rows[]=array("name"=>$n,"state"=>$state,"server"=>$cat[$n]["server"] ?? "","client"=>$cat[$n]["client"] ?? "");
    }
    return array("id"=>$ID,"rows"=>$rows,"catalog"=>$cat);
}

function fws_h(string $v):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($v,ENT_QUOTES));
}

/** Une pastille par jeton de port ; au-delà de 6, « +N ». */
function fws_ports(string $ports):string{
    $tok=preg_split('/\s+/',trim($ports),-1,PREG_SPLIT_NO_EMPTY);
    if(count($tok)==0){return "<span class='ak-muted'>—</span>";}
    $out=array();
    foreach(array_slice($tok,0,6) as $t){$out[]="<span class='ak-chip'>".fws_h($t)."</span>";}
    if(count($tok)>6){$out[]="<span class='ak-count' title=\"".fws_h(implode(" ",array_slice($tok,6)))."\">+".(count($tok)-6)."</span>";}
    return "<span class='ak-chips'>".implode("",$out)."</span>";
}

function fws_state(string $state):string{
    if($state=="active"){return "<span class='ak-pill ak-ok'><span class='ak-dot'></span>{fws_active}</span>";}
    if($state=="disabled"){return "<span class='ak-pill ak-warn' title=\"{fws_ignored}\"><i class='fas fa-triangle-exclamation'></i> {fws_disabled}</span>";}
    return "<span class='ak-pill ak-err' title=\"{fws_ignored}\"><i class='fas fa-circle-exclamation'></i> {fws_missing}</span>";
}

function fws_row(int $ID,array $r):string{
    $page=CurrentPageName();
    $tone=array("active"=>"ak-info","disabled"=>"ak-warn","missing"=>"ak-err")[$r["state"]];
    $b64=urlencode(base64_encode($r["name"]));
    $local=$r["client"]!==""?"<div class='fws-sub'>{local_ports}: <span class='ak-mono'>".fws_h($r["client"])."</span></div>":"";
    return "<tr><td><div class='fws-ident'><span class='ak-ico ak-ico-sm ak-ico-sq $tone' aria-hidden='true'><i class='fas fa-network-wired'></i></span>"
        ."<div><div class='fws-name'>".fws_h($r["name"])."</div>$local</div></div></td>"
        ."<td>".($r["state"]=="missing"?"<span class='ak-muted'>—</span>":fws_ports($r["server"]))."</td>"
        ."<td>".fws_state($r["state"])."</td>"
        ."<td class='ak-end'>".(firewall_meta_holds("rules")?"":"<button type='button' class='ak-act ak-danger' aria-label=\"{fws_remove} ".fws_h($r["name"])."\" title=\"{fws_remove}\" OnClick=\"Loadjs('$page?delete-js=$ID&s=$b64')\"><i class='fas fa-trash-can'></i></button>")."</td></tr>";
}

function fws_header(array $m):string{
    $n=count($m["rows"]);
    if(firewall_meta_holds("rules")){
        return "<div class='fws-head'><div class='fws-title'><h3>{firewall_services}</h3><span class='ak-count'>$n</span></div></div>";
    }
    return "<div class='fws-head'><div class='fws-title'><h3>{firewall_services}</h3><span class='ak-count'>$n</span></div>"
        ."<div class='ak-actions'>".(firewall_meta_holds("services")?"":"<button type='button' class='ak-btn' OnClick=\"FwsNew()\"><i class='fas fa-plus'></i> {new_service}</button>").""
        ."<button type='button' class='ak-btn ak-btn-primary' OnClick=\"FwsLink()\"><i class='fas fa-link'></i> {link_service}</button></div></div>";
}

function fws_empty():string{
    if(firewall_meta_holds("rules")){
        return "<div class='ak-card'><div class='ak-empty-c'><i class='fas fa-plug'></i><div><b>{fws_none}</b></div></div></div>";
    }
    return "<div class='ak-card'><div class='ak-empty-c'><i class='fas fa-plug'></i><div><b>{fws_none}</b></div><div>{fws_none_text}</div>"
        ."<div class='ak-actions' style='justify-content:center;margin-top:12px'>".(firewall_meta_holds("services")?"":"<button type='button' class='ak-btn' OnClick=\"FwsNew()\"><i class='fas fa-plus'></i> {new_service}</button>").""
        ."<button type='button' class='ak-btn ak-btn-primary' OnClick=\"FwsLink()\"><i class='fas fa-link'></i> {link_service}</button></div></div></div>";
}

function build_page(){
    $ID=intval($_GET["rule-id"]);
    $page=CurrentPageName();
    echo "<div class='ak fws' style='margin-top:16px'><div id='fw-objects-table'></div></div>
<script>
function FwsReload(){ LoadAjaxSilent('fw-objects-table','$page?build-table=yes&ID=$ID'); }
function FwsLink(){ Loadjs('$page?link-js=$ID'); }
function FwsNew(){ Loadjs('$page?new-js=$ID'); }
FwsReload();
</script>";
}

function build_table(){
    $ID=intval($_GET["ID"]);
    $tpl=new template_admin();
    $m=fws_model($ID);
    $h=array();
    $metaBanner=firewall_meta_banner("rules",$tpl);
    $h[]=fws_header($m);
    if(count($m["rows"])==0){
        $h[]=fws_empty();
    }else{
        $rows=array();
        foreach($m["rows"] as $r){$rows[]=fws_row($ID,$r);}
        $h[]="<div class='ak-card'><table class='ak-tbl fws-tbl'><thead><tr><th>{service2}</th><th>{fws_listening_ports}</th><th>{fws_catalog_state}</th><th class='ak-end'></th></tr></thead>"
            ."<tbody>".implode("",$rows)."</tbody></table></div>";
        $ignored=0;
        foreach($m["rows"] as $r){if($r["state"]!="active"){$ignored++;}}
        if($ignored>0){$h[]="<p class='ak-note fws-note'><i class='fas fa-circle-info'></i> {fws_ignored}</p>";}
    }
    echo fws_style().$metaBanner.$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>NoSpinner();</script>";
}

// Après une modification : la liste et la ligne de la règle dans le tableau des règles.
function fws_after_js(int $ID):string{
    return "FwsReload();Loadjs('fw.rules.php?fill=$ID');";
}

// ── Retrait d'un service (confirmation, POST) ──
function delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-js"]);
    if(firewall_meta_holds("rules")){firewall_meta_refuse_js();return;}
    $name=(string)base64_decode((string)($_GET["s"] ?? ""),true);
    if($ID<1 || $name===""){return;}
    echo $tpl->js_confirm_delete(fws_h($name),"fws-remove","$ID:".base64_encode($name),fws_after_js($ID));
}

// Valeur postée par js_confirm_delete : "<rule id>:<nom en base64>". N'imprime que l'erreur.
function fws_remove(){
    $tpl=new template_admin();
    if(firewall_meta_holds("rules")){echo firewall_meta_refuse_post();return;}
    $parts=explode(":",(string)$_POST["fws-remove"],2);
    $ID=intval($parts[0]);
    $name=(string)base64_decode((string)($parts[1] ?? ""),true);
    if($ID<1 || $name===""){echo $tpl->post_error("Invalid entry");return;}
    $names=array_diff(rule_services_get($ID),array($name));
    $e=rule_services_put($ID,$names);
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks("Firewall rule $ID: service $name removed");
}

// ── Lier un service du catalogue ──
function fws_link_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["link-js"]);
    if(firewall_meta_holds("rules")){firewall_meta_refuse_js();return;}
    $tpl->js_dialog5("{link_service}",CurrentPageName()."?link-popup=$ID",760);
}

function fws_link_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["link-popup"]);
    $linked=array_flip(rule_services_get($ID));
    $cat=fws_catalog();
    $rows=array();
    foreach($cat as $n=>$s){
        $act=isset($linked[$n])
            ?"<span class='ak-pill ak-off'>{fws_linked}</span>"
            :"<button type='button' class='ak-btn ak-btn-sm ak-btn-primary' OnClick=\"FwsPick($ID,'".base64_encode($n)."')\">{fws_select}</button>";
        $off=$s["enabled"]?"":" <span class='ak-pill ak-warn'>{fws_disabled_short}</span>";
        $rows[]="<tr data-q=\"".fws_h(strtolower($n." ".$s["server"]))."\"".($s["enabled"]?"":" class='fws-off'")."><td><span class='fws-name'>".fws_h($n)."</span>$off</td><td>".fws_ports($s["server"])."</td><td class='ak-end'>$act</td></tr>";
    }
    $h=array();
    $h[]="<div class='ak fws'>";
    $h[]="<label class='ak-search fws-search'><i class='fas fa-magnifying-glass'></i><input type='text' id='fws-q-$ID' placeholder=\"{fws_search_ph}\" aria-label=\"{fws_search_ph}\" OnKeyUp=\"FwsFilter($ID)\" autocomplete='off'></label>";
    $h[]="<div class='ak-card fws-pick'><table class='ak-tbl fws-tbl'><tbody id='fws-cat-$ID'>".implode("",$rows)."</tbody></table>";
    $h[]="<div class='ak-empty-c' id='fws-cat-none-$ID' hidden><div>{fws_nomatch}</div></div></div>";
    $h[]="<p class='ak-note fws-note'>".str_replace("%s",(string)count($cat),$tpl->_ENGINE_parse_body("{fws_catalog_count}"))."</p>";
    $h[]="</div>";
    $oops=addslashes($tpl->javascript_parse_text("{error}"));
    $after=fws_after_js($ID);
    echo fws_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>
function FwsFilter(id){
    var q=String(document.getElementById('fws-q-'+id).value||'').toLowerCase().trim(), n=0;
    jQuery('#fws-cat-'+id+' tr').each(function(){ var ok=(q==='')||String(this.getAttribute('data-q')).indexOf(q)>=0; this.style.display=ok?'':'none'; if(ok){n++;} });
    document.getElementById('fws-cat-none-'+id).hidden=(n>0);
}
function FwsPick(id,b64){
    jQuery.post('$page',{'object-link':id,'service':b64},function(r){
        r=String(r).trim();
        if(r.length>3){ var t=(r.indexOf('jserror:')===0)?r.substring(8):jQuery('<div>').text(r).html(); swal({title:'$oops',text:t,html:true,type:'error'}); return; }
        dialogInstance5.close();$after
    }).fail(function(x){ swal({title:'$oops',text:'HTTP '+x.status,html:false,type:'error'}); });
}
</script>";
}

// POST object-link=<rule id>, service=<nom en base64>. N'imprime que l'erreur.
function save_link_object(){
    $tpl=new template_admin();
    $ID=intval($_POST["object-link"]);
    if(firewall_meta_holds("rules")){echo firewall_meta_refuse_post();return;}
    $service=(string)base64_decode((string)($_POST["service"] ?? ""),true);
    if($ID<1 || $service===""){echo $tpl->post_error("Invalid entry");return;}
    if(!isset(fws_catalog()[$service])){echo $tpl->post_error("{fws_missing}: ".fws_h($service));return;}
    $names=rule_services_get($ID);
    $names[]=$service;
    $e=rule_services_put($ID,$names);
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks("Firewall rule $ID: service $service linked");
}

// ── Créer un service et le lier ──
function fws_new_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["new-js"]);
    if(firewall_meta_holds("rules") || firewall_meta_holds("services")){firewall_meta_refuse_js();return;}
    $tpl->js_dialog6("{new_service}",CurrentPageName()."?new-popup=$ID",720);
}

function fws_new_popup(){
    $tpl=new template_admin();
    $ID=intval($_GET["new-popup"]);
    $tpl->field_hidden("object-save",$ID);
    $form=array();
    $form[]=$tpl->field_text("service","{service_name2}",null,true,"{fws_name_hint}");
    $form[]=$tpl->field_text("server_port","{fws_ports}",null,true,"{fws_ports_hint}");
    $form[]=$tpl->field_text("client_port","{local_ports}",null,false,"{fws_local_hint}");
    $form[]=$tpl->field_checkbox("enabled","{enabled}",1,false,"{fws_enabled_hint}");
    $html=array();
    $html[]=$tpl->div_explain("{new_service}||{fws_new_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),"","{add_service}","dialogInstance6.close();".fws_after_js($ID),"AsFirewallManager");
    $html[]="<details class='fws-help'><summary>{fws_syntax}</summary><ul><li><span class='ak-mono'>tcp/80</span> — {fws_syn_single}</li><li><span class='ak-mono'>tcp/8000:8100</span> — {fws_syn_range}</li><li><span class='ak-mono'>udp/any</span> — {fws_syn_any}</li></ul></details>";
    echo fws_style().$tpl->_ENGINE_parse_body(implode("\n",$html));
}

// POST object-save=<rule id> : crée le service au catalogue puis le lie. N'imprime que l'erreur.
function save_object(){
    $ID=intval($_POST["object-save"]);
    $tpl=new template_admin();
    // Cree un service (partie services) puis le lie (partie regles).
    if(firewall_meta_holds("rules") || firewall_meta_holds("services")){echo firewall_meta_refuse_post();return;}
    $tpl->CLEAN_POST();
    foreach($_POST as $num=>$dr){$_POST[$num]=url_decode_special_tool($dr);}
    $_POST["service"]=$tpl->CleanServiceName($_POST["service"]);
    if(trim((string)$_POST["service"])===""){echo $tpl->post_error("{service_name2}: {fws_required}");return;}
    $client=trim(preg_replace('/\s+/',' ',(string)($_POST["client_port"] ?? "")));
    // Un port nu devient tcp/<port> ; les jetons proto/... passent tels quels.
    $f1=array();
    foreach(preg_split('/\s+/',trim((string)($_POST["server_port"] ?? "")),-1,PREG_SPLIT_NO_EMPTY) as $port){
        if(!preg_match("#(.+?)\/(.+)#",$port)){
            if(!is_numeric($port) || intval($port)<1){continue;}
            $port="tcp/$port";
        }
        $f1[]=$port;
    }
    if(count($f1)==0){echo $tpl->post_error("{fws_ports}: {fws_required}");return;}
    $svcBody=array("service"=>$_POST["service"],"serverPort"=>implode(" ",$f1),"clientPort"=>$client,
        "enabled"=>intval($_POST["enabled"] ?? 0),"helper"=>"");
    $e=fwapi_error(fwapi_send("POST","services",$svcBody));
    if($e!=""){echo $tpl->post_error($e);return;}
    $names=rule_services_get($ID);
    $names[]=$_POST["service"];
    $e=rule_services_put($ID,$names);
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks("Firewall rule $ID: service {$_POST["service"]} created (".implode(" ",$f1).") and linked");
}

function fws_style():string{
    if(isset($GLOBALS["FWS_CSS"])){return "";}
    $GLOBALS["FWS_CSS"]=true;
    return "<style>
.fws-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.fws-head .ak-actions{margin:0;padding:0;border:0}
.fws-title{display:flex;align-items:center;gap:10px}
.fws-title h3{margin:0;font-size:18px;font-weight:600;color:#111d23}
.fws-ident{display:flex;align-items:center;gap:12px}
.fws-name{font-size:15px;font-weight:600;color:#111d23}
.fws-sub{font-size:13px;color:#6b7280;margin-top:2px}
.fws-tbl td{vertical-align:middle}
.fws-note{margin:10px 2px 0}
.fws-search{display:flex;margin-bottom:12px}
.fws-pick{max-height:420px;overflow:auto}
.fws-off .fws-name{color:#6d7a77}
.fws-help{margin-top:12px;font-size:13px;color:#3d4947}
.fws-help summary{cursor:pointer;font-weight:600}
.fws-help ul{margin:8px 0 0;padding-left:18px}
</style>";
}
