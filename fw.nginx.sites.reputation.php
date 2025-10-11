<?php
///$_GET["verbose"]="yes";
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tpl.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["serviceid"])){Save();exit;}
if(isset($_GET["popup"])){popup();exit;}

js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["serviceid"];
    $servicename=get_servicename($ID);
    return $tpl->js_dialog4("#$ID - $servicename - {reputation_service}", "$page?popup=$ID",800);
}
function popup():bool{
    $tpl=new template_admin();
    $serviceid=$_GET["popup"];
    $servicename=get_servicename($serviceid);
    $page=CurrentPageName();
    $sockngix                   = new socksngix($serviceid);
    $ReputationServiceBlack = $sockngix->GET_INFO("ReputationServiceBlack");
    $ReputationServiceWhite = $sockngix->GET_INFO("ReputationServiceWhite");
    $ReputationServiceRedir = intval($sockngix->GET_INFO("ReputationServiceRedir"));
    $ReputationServiceURL = trim($sockngix->GET_INFO("ReputationServiceURL"));
    $ReputationServiceErrCode = intval($sockngix->GET_INFO("ReputationServiceErrCode"));
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results=$q->QUERY_SQL("SELECT * FROM rbl_reputations WHERE enabled=1");
    $HASH=array();
    if(count($results)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_reput_rules}"));
        return true;
    }
    $squid_error["400"]="Bad Request";
    $squid_error["401"]="Unauthorized";
    $squid_error["402"]="Payment Required";
    $squid_error["403"]="Forbidden";
    $squid_error["404"]="Not Found";
    $squid_error["405"]="Method Not Allowed";
    $squid_error["406"]="Not Acceptable";
    $squid_error["407"]="Proxy Authentication Required";
    $squid_error["408"]="Request Timeout";
    $squid_error["409"]="Conflict";
    $squid_error["410"]="Gone";
    $squid_error["411"]="Length Required";
    $squid_error["412"]="Precondition Failed";
    $squid_error["413"]="Request Entity Too Large";
    $squid_error["414"]="Request URI Too Large";
    $squid_error["415"]="Unsupported Media Type";
    $squid_error["416"]="Request Range Not Satisfiable";
    $squid_error["417"]="Expectation Failed";
    $squid_error["424"]="Locked";
    $squid_error["433"]="Unprocessable Entity";
    $squid_error["500"]="Internal Server Error";
    $squid_error["501"]="Not Implemented";
    $squid_error["502"]="Bad Gateway";
    $squid_error["503"]="Service Unavailable";
    $squid_error["504"]="Gateway Timeout";
    $squid_error["505"]="HTTP Version Not Supported";
    $squid_error["507"]="Insufficient Storage";

    foreach ($squid_error as $key => $value) {
        $squid_error2[$key]="[$key] $value";
    }

    foreach($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $rulename = $ligne["rulename"];
        $HASH[$ID]=$rulename;
    }
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_array_hash($HASH,"ReputationServiceBlack","{deny_access}",$ReputationServiceBlack);
    $form[]=$tpl->field_array_hash($HASH,"ReputationServiceWhite","{allow_access}",$ReputationServiceWhite);

    $form[]=$tpl->field_array_hash($squid_error2,"ReputationServiceErrCode","{http_status_code}",$ReputationServiceErrCode);

    $form[]=$tpl->field_checkbox("ReputationServiceRedir","{REDIRECT}",$ReputationServiceRedir,"ReputationServiceURL");
    $form[]=$tpl->field_text("ReputationServiceURL","{RedirectQueries}",$ReputationServiceURL);



    echo $tpl->form_outside("",$form,"","{apply}",
        "dialogInstance4.close();LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');","AsSystemAdministrator");
    return true;
}


function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return $ligne["servicename"];
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}

function Save(){
    $serviceid=intval($_POST["serviceid"]);
    $sockngix                   = new socksngix($serviceid);
    unset($_POST["serviceid"]);
    foreach ($_POST as $key=>$value){
        $sockngix->SET_INFO($key,$value);
    }

    $sock=new sockets();
    $sock->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
}