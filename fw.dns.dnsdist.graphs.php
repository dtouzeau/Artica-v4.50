<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
/**
 * DNS service » Metrics (maquette stitch_unbound_dns_manager_stats).
 *
 * Filtre de période et Refresh dans le TINY ; six onglets tabs_default() :
 *   Overview   — échantillons dnsdist/unbound de 5 min (/<prefix>/metrics?range=1h|6h|24h|7d|30d) :
 *                total traité, cache, transmis, pointe, courbe, taux de cache, répartition.
 *   Queries    — /dnstap/metrics/summary + /dnstap/metrics/timeseries
 *   Clients    — /dnstap/metrics/top/ips (extra = NXDOMAIN)
 *   Query types, Domains, Categories — /dnstap/metrics/top/{qtypes|hosts|categories}
 * DnsTapDB ne connaît que 60 min / 24 h / 7 j / mois en cours (dnsg_tap_period) :
 * chaque onglet écrit la fenêtre réellement couverte.
 */

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfw.views.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}

$tpl=new template_admin();
$users=new usersMenus();
if(!$tpl->DnsReader()){exit();}

// ── Dispatcher ──────────────────────────────────────────────────
if(isset($_GET["main-view"]))         {main_view();          exit;}
foreach(array("requests","queries","top-ips","qtypes","top-hosts","categories") as $tabName){
    if(isset($_GET["tab-$tabName"])){ tab_render($tabName); exit; }
}
page();

// ── Helpers ─────────────────────────────────────────────────────

/** Onglet : le rendu (et les appels REST) sont dans ressources/class.dnsfw.views.inc. */
function tab_render(string $name):void{
    $v=new dnsfw_views("local");
    echo $v->tab($name,(string)($_GET["range"] ?? "1h"));
}

// ── Page entry point ────────────────────────────────────────────

function page():void{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $title="{APP_UNBOUND}";
    $expl="{didyouknow_unbound}";
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableDNSDist==1){
        $title="{APP_DNSDIST}";
        $expl="{APP_DNSDIST_EXPLAIN2}";
    }

    $html=$tpl->page_header("$title&nbsp;&raquo;&nbsp;{metrics}","fa-solid fa-chart-line",
        "$expl","$page?main-view=yes&range=1h","dnsfw-requests","progress-dnsdist-restart",
        false,"dnsfw-graphs-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Vue principale : onglets + filtre de période dans le TINY ───
// (maquette stitch_unbound_dns_manager_stats). Le filtre recharge cette vue
// entière (§2b) ; « Refresh » ne recharge que l'onglet affiché.

function main_view():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $v=new dnsfw_views("local");
    $range=$v->valid_range((string)($_GET["range"] ?? "1h"));

    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"dnsg-period","LABEL"=>"{dnsg_period}","VALUE"=>$range,
        "OPTIONS"=>array("1h"=>"1h","6h"=>"6h","24h"=>"24h","7d"=>"7d","30d"=>"30d"),"JS"=>"DnsgPeriod");
    $topbuttons=array();
    $topbuttons[]=array("DnsgRefresh();",ico_refresh,"{refresh}");

    $title="{APP_UNBOUND}";$expl="{didyouknow_unbound}";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"))==1){$title="{APP_DNSDIST}";$expl="{APP_DNSDIST_EXPLAIN2}";}
    $TINY=array();
    $TINY["TITLE"]="$title&nbsp;&raquo;&nbsp;{metrics}";
    $TINY["ICO"]="fa-solid fa-chart-line";
    $TINY["EXPL"]=$expl;
    $TINY["URL"]="dnsfw-requests";
    $TINY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));

    echo siem_tiny_filters_style().$v->graphs($page,$range);
    echo "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
}
