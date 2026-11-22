<?php
/**
 * DNS tab of a remote node: /etc/resolv.conf (name servers, search, domain,
 * options, sortlist) and the hostname.
 *
 * Entry:  tab "DNS" of fw.netagents.list.php agent_info_tabs() -> ?id={agent_id}
 *         Loadjs('fw.netagents.dns.php?dns-js={agent_id}')     -> same form in a dialog
 * API:    GET  /netagents/status/{id}          (dns, hostname, artica, cached_at)
 *         POST /netagents/system/{id}/network  {"dns":{...},"hostname":""}
 *
 * On an Artica appliance the agent hands the values to Artica
 * (articarest resolv.MergeMetaDNS), which keeps 3 servers, 3 domains (domain
 * first), timeout, attempts, rotate and use-vc, and reports the rest in
 * applied.ignored.
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.dns.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){ $GLOBALS["CLASS_SOCKETS"]=new sockets(); }

if(!nadns_is_admin()){
    if(isset($_GET["dns-js"])){ exit; }
    $tpl=new template_admin();
    if(isset($_POST["save"])){ nadns_reply(false,$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")); exit; }
    echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS}"));
    exit;
}
if(isset($_GET["dns-js"])){ dns_js(); exit; }
if(isset($_POST["save"])){ dns_save(); exit; }
dns_form();

// ── Dispatcher actions ───────────────────────────────────────────────────────

/** Opens the form in a dialog. */
function dns_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["dns-js"]);
    return $tpl->js_dialog3("{dns_settings}","$page?id=$id",1100);
}

/** The tab: notices, two columns of cards, action bar. */
function dns_form():bool{
    $tpl=new template_admin();
    $id=nadns_id();
    $m=nadns_model(nadns_rest("/netagents/status/$id"));

    if($m["error"]!=""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($m["error"]));
        return false;
    }
    if($m["nodns"]){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{unable_to_retrieve_the_settings}<br>{check_agent_version_explain}"));
        return false;
    }
    if($m["pending"]){
        echo nadns_style().$tpl->_ENGINE_parse_body("<div class='nadns'>".nadns_notice_pending()."</div>");
        return true;
    }

    $h=array();
    $h[]="<div class='nadns' id='nadns-$id'>";
    $h[]=nadns_notice_stale($m);
    $h[]=nadns_notice_artica($m);
    $h[]="<div class='nadns-grid'>";
    $h[]="<div class='nadns-col'>".nadns_card_hostname($m).nadns_card_nameservers($m)."</div>";
    $h[]="<div class='nadns-col'>".nadns_card_resolver($m).nadns_card_options($m)."</div>";
    $h[]="</div>";
    $h[]=nadns_actionbar();
    $h[]="</div>";
    echo nadns_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).nadns_js($id,$m);
    return true;
}

/** Validates, posts to the node, answers JSON {ok, html, hostname, ignored[]}. */
function dns_save():void{
    $tpl=new template_admin();
    $id=nadns_id();
    $error="";
    $dns=nadns_post_dns($error);
    if($dns===null){ nadns_reply(false,$error); return; }
    $nameservers=$dns["nameservers"];
    $hostname=trim(isset($_POST["hostname"])?(string)$_POST["hostname"]:"");
    if($hostname!=="" && !nadns_valid_name($hostname)){
        nadns_reply(false,nadns_error_html("{nadns_err_hostname}",$hostname));
        return;
    }
    $data=array("dns"=>$dns);
    // Sent only when it changed: on an Artica appliance a hostname change
    // reloads sshd, unbound, postfix...
    if($hostname!==""){ $data["hostname"]=$hostname; }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/system/$id/network",$data));
    if(!is_object($json)){ nadns_reply(false,nadns_error_html("{nadns_err_daemon}")); return; }
    if(isset($json->Status) && !$json->Status){
        nadns_reply(false,nadns_h(isset($json->Error)?$json->Error:"error"));
        return;
    }
    if(isset($json->success) && !$json->success){
        nadns_reply(false,nadns_h(isset($json->error)?$json->error:"error"));
        return;
    }

    $applied=(isset($json->applied) && is_object($json->applied))?$json->applied:new stdClass();
    $ignored=array();
    if(isset($applied->ignored) && is_array($applied->ignored)){
        foreach($applied->ignored as $item){ $ignored[]=nadns_h((string)$item); }
    }
    $out=array(
        "ok"=>true,
        "html"=>$tpl->_ENGINE_parse_body("{meta_wait_parameters}"),
        "ignored_title"=>count($ignored)>0?$tpl->_ENGINE_parse_body("{netagent_dns_ignored}"):"",
        "ignored"=>$ignored,
        "hostname"=>(isset($applied->hostname) && is_string($applied->hostname))?$applied->hostname:"",
        "hostname_pending"=>!empty($applied->hostname_pending),
    );
    admin_tracks("Netagent #$id: DNS configuration updated (".implode(", ",$nameservers).")".($hostname!==""?" hostname $hostname":""));
    header("content-type: application/json");
    echo json_encode($out);
}
