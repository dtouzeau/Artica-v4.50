<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.rbldnsd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["top-section"])){status_top();exit;}
if(isset($_GET["flat-section"])){flat_section();exit;}

function status_top():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $INSTALL_STATUS="";$PRIV="";
    $EnableMCPcatz=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMCPcatz"));
    if($EnableMCPcatz==0){
        $Install=$tpl->framework_buildjs(
            "/mcpcatz/install",
            "mcp-categorization.install.progress",
            "mcp-categorization.install.progress.log",
            "progress-mcp-restart");

        $btn = array();
        $btn[0]["margin"] = 20;
        $btn[0]["name"] = "{install}";
        $btn[0]["icon"] = ico_cd;
        $btn[0]["js"] = $Install;
        $INSTALL_STATUS=$tpl->widget_style1("gray-bg",ico_cd, "{not_installed}","{install}", $btn,);

    }else{
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->MCPCATZ_GET("/status"),true);
        $Priv=$data["granted"]["max_tier"];
        $IA= $data["granted"]["grants_ia"];
        if($IA){
            $PRIV=$tpl->widget_style1("lazur-bg",ico_admin, "{privileges}","IA");

        }


    }
    //MCPCATZ_GET

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$INSTALL_STATUS</td>";
    $html[]="<td style='width:33%'>$PRIV</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mcpcatz/status"),true);
    if(!isset($json["Status"])){
        echo $tpl->div_error("{protocol_error}");
        return true;
    }
    if(!$json["Status"]){
        echo $tpl->div_error($json["Error"]);
        return true;
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json["Info"]);
    echo $tpl->SERVICE_STATUS($ini,"APP_MCPCATZ",null);

    echo "<script>
        LoadAjaxSilent('top-section','$page?top-section=yes');
        LoadAjaxSilent('flat-section','$page?flat-section=yes');
        </script>";

return true;
}

function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan='2'><div id='top-section'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:240px'><div id='left-section'></div></td>";
    $html[]="<td style='width:99%;padding-left:15px;vertical-align:top'><div id='flat-section'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";

    $TINY_ARRAY["TITLE"]="MCPCatz";
    $TINY_ARRAY["ICO"]="fa-solid fa-brain-circuit";
    $TINY_ARRAY["EXPL"]="{mcpcatz_explain}";
    $TINY_ARRAY["URL"]="categories-mcp";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=$jstiny;
    $html[]=$tpl->RefreshInterval_js("left-section","$page","status=yes");
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
return true;

}

function flat_section(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableMCPcatz=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMCPcatz"));
    if($EnableMCPcatz==0){
        echo $tpl->div_explain("{install}||{mcpcatz_install_first}");
    }
    $sock=new sockets();
    $data=json_decode($sock->MCPCATZ_GET("/status"),true);

    $Priv=$data["granted"]["max_tier"];
    $IA= $Priv=$data["granted"]["grants_ia"];

    $version=$data["version"]["version"];
    $tpl->table_form_field_text("{version}",$version,ico_infoi);
    $tpl->table_form_field_text("{privileges}",$Priv,ico_admin);

    $lic=$tpl->time_to_date(strtotime($data["license"]["expiry"]));
    $tpl->table_form_field_text("{license}","{expire_in} $lic",ico_certificate);



    echo $tpl->table_form_compile();

//"max_tier":"read-write","grants_ia":true
//Array ( [granted] => Array ( [grants_ia] => 1 [max_tier] => admin ) [license] => Array ( [expired] => [expiry] => 2036-06-11T01:57:40.595219746Z [issued_to] => [level] => admin ) [licensed] => 1 [version] => Array ( [version] => 1.0.18 [commit] => 4e2bb3d [build_date] => 2026-06-14T15:34:54Z [go] => go1.26.0 ) )
}