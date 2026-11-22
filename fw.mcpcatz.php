<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.rbldnsd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}



if(isset($_GET["tabs"])){tabs();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("MCPCatz",
        "fa-solid fa-brain-circuit",
        "{mcpcatz_explain}",
        "$page?tabs=yes","categories-mcp",
        "progress-mcp-restart",false,"table-mcpcat-status");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

/* Tab bar loaded by page_header: each tab LoadAjax's a sibling page into its
 * panel — client-certificate management and peer (server-to-server) management. */
function tabs(){
    $tpl=new template_admin();

    $array["{status}"]="fw.mcpcatz.status.php?start=yes";
    $array["{client_certificates}"]="fw.mcpcatz.clients.php";
    $array["{peers}"]="fw.mcpcatz.peers.php";

    /* The "AI Metrics" tab is only offered when the daemon grants the IA
     * privilege (the /ia-stats endpoint requires the IA license). */
    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->MCPCATZ_GET("/status"),true);
    if(is_array($status) && !empty($status["granted"]["grants_ia"])){
        $array["{ia_metrics}"]="fw.mcpcatz.iastats.php";
        $array["{ia_graphs}"]="fw.mcpcatz.iagraphs.php";
    }

    $TINY_ARRAY["TITLE"]="MCPCatz";
    $TINY_ARRAY["ICO"]="fa-solid fa-brain-circuit";
    $TINY_ARRAY["EXPL"]="{mcpcatz_explain}";
    $TINY_ARRAY["URL"]="categories-mcp";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo $tpl->tabs_default($array);
    echo "<script>$jstiny</script>";
}
