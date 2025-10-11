<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

if(isset($_POST["IPADDR"])){simulate_perform();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters-start"])){parameters_start();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["fw-status"])){fw_status();exit;}
if(isset($_GET["simulate-js"])){simulate_js();exit;}
if(isset($_GET["simulate-popup"])){simulate_popup();exit;}
if(isset($_GET["simulate-ok"])){simulate_ok();exit;}

page();

function simulate_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{simulate_an_outgoing_connection}","$page?simulate-popup=yes");
}

function simulate_ok():bool{
    $tpl=new template_admin();
    return $tpl->js_display_results("{success_reachable}");
}

function simulate_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!isset($_SESSION["SIMULATE"])){
        $_SESSION["SIMULATE"]["IPADDR"]="152.199.4.127";
        $_SESSION["SIMULATE"]["PORT"]="443";
        $_SESSION["SIMULATE"]["TIMEOUT"]="2";
    }
    $form[]=$tpl->field_ipaddr("IPADDR","{dst}",$_SESSION["SIMULATE"]["IPADDR"],true);
    $form[]=$tpl->field_numeric("PORT","{destination_port}",$_SESSION["SIMULATE"]["PORT"],null,true);
    $form[]=$tpl->field_numeric("TIMEOUT","{timeout}",$_SESSION["SIMULATE"]["TIMEOUT"],null,true);

    echo $tpl->form_outside("{simulate_an_outgoing_connection}",$form,null,"{simulate}","Loadjs('$page?simulate-ok=yes');","AsSystemAdministrator",false);

}

function simulate_perform(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $_SESSION["SIMULATE"]=$_POST;

    $socket_context = stream_context_create(array());
    $conn=stream_socket_client(
        $_POST["IPADDR"] . ':' . $_POST["PORT"],
        $errno,
        $errstr,
        $_POST["TIMEOUT"],
        STREAM_CLIENT_CONNECT,
        $socket_context);


    if (!is_resource($conn)) {

        echo "jserror: Err.$errno $errstr";
        return;

    }

    fclose($conn);


}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $title=$tpl->_ENGINE_parse_body("{APP_NGINX_FW}");
    $js="LoadAjax('table-web-firewall','$page?tabs=yes');";




    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	    <div class=\"col-sm-12\"><h1 class=ng-binding>$title</H1>
	        <p>{APP_NGINX_FW_EXPLAIN}</p>
	    </div>
	</div>
    <div class='row'><div id='progress-web-firewall'></div>
	<div class='ibox-content' style='min-height:600px'>
    	<div id='table-web-firewall'></div>
    </div>
	<script>
	$.address.state('/');
	$.address.value('/firewall-web');
	$.address.title('Artica: $title');
	$js
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Web Application firewall {status}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?parameters-start=yes";
    $array["{rules}"]="fw.modsecurity.rules.php";
    echo $tpl->tabs_default($array);
    return true;
}
function parameters_start():bool{
    $page=CurrentPageName();
    echo "<div id='modsec-params' style='margin-top:10px'></div><script>LoadAjax('modsec-params','$page?parameters=yes');</script>";
    return true;
}

function parameters():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px'>";
    $html[]="<div id='nginx-fw-status'></div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:70%'>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('nginx-fw-status','$page?fw-status=yes');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}

function fw_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/reverse-proxy/firewall/status"));
    $OUT=unserialize($json->Info);
    $status=false;
    if($OUT["STATUS"]=="true"){
        $status=true;
    }

    $EnableNginxFW=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"));

    if($EnableNginxFW==0){
        echo $tpl->widget_grey("{firewall} ({outbound})","{disabled}",null,null,412);
        return false;

    }

    $NGINX_FW_TIMESTAMP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_FW_TIMESTAMP"));
    $NGINX_FW_TIMESTAMP_TEXT="";
    if($NGINX_FW_TIMESTAMP>0){
        $NGINX_FW_TIMESTAMP_TEXT=distanceOfTimeInWords($NGINX_FW_TIMESTAMP,time());
        $NGINX_FW_TIMESTAMP_TEXT="<br><small style='color:white;font-size:12px'>{since} $NGINX_FW_TIMESTAMP_TEXT</small>";
    }

    $service_stop=$tpl->framework_buildjs("/reverse-proxy/firewall/stop",
        "nginxfw.stop.progress",
        "nginxfw.stop.txt",
        "progress-web-firewall");

    $service_start=$tpl->framework_buildjs("/reverse-proxy/firewall/start",
        "nginxfw.start.progress",
        "nginxfw.start.txt",
        "progress-web-firewall");



    if($status){
        $button["name"] = "{stop}";
        $button["js"] = $service_stop;
        $button["ico"]="fas fa-stop-circle";

        echo $tpl->widget_h("green",ico_firewall,$tpl->_ENGINE_parse_body("{active2}"),"{firewall} ({outbound})$NGINX_FW_TIMESTAMP_TEXT",$button);

        $btn_config=$tpl->button_autnonome("{simulate}", "Loadjs('$page?simulate-js=yes')", "fas fa-file-code","AsSystemAdministrator",420,"btn-warning");
        echo $tpl->_ENGINE_parse_body("<div style='margin-top:10px;margin-bottom:10px'>$btn_config</div>");

    }else{
        $button["name"] = "{start}";
        $button["js"] = $service_start;
        $button["ico"]=ico_play;
        echo $tpl->widget_h("grey",ico_firewall,$tpl->_ENGINE_parse_body("{inactive}"),"{firewall} ({outbound})",$button);
    }


    $SIZE_INMEM=$OUT["SIZE IN MEMORY"];
    $NUMBER_OF_ENTRIES=$OUT["NUMBER OF ENTRIES"];
    $TEXT=$tpl->FormatNumber($NUMBER_OF_ENTRIES)." {records} <br><small style='font-size:12px;color:white'>".FormatBytes($SIZE_INMEM/1024)." {memory}</small>";

    if($NUMBER_OF_ENTRIES>0){
        echo $tpl->widget_vert("{whitelists_host} ({outbound})",$TEXT,null,ico_firewall,420);
    }

    return true;

}
function ServiceStatus():string{
    $sock=new sockets();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $data=$sock->REST_API_NGINX("/reverse-proxy/service/status");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->widget_rouge("{error}","Framework return false!");
        return false;
    }
    $ini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($ini, "APP_NGINX","");

}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $users=new usersMenus();

    include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr><td>
	<div class=\"ibox\" style='border-top:0px'>
    	<div class=\"ibox-content\" style='border-top:0px'>".
        ServiceStatus().
        "</div>
    </div></td></tr>";

    $html[]="</table></td>";

    $curl=new ccurl("http://127.0.0.1:1842/nginx_status/",true,"127.0.0.1");
    $curl->NoLocalProxy();
    $curl->interface_force("127.0.0.1");
    $curl->NoHTTP_POST=true;
    $curl->get();

    $tbl=explode("\n",$curl->data);
    $ActiveConnections=0;
    $requests=0;
    foreach ($tbl as $index=>$ligne){
        $ligne=trim($ligne);
        if($ligne==null){continue;}
        if(preg_match("#Active connections:\s+([0-9]+)#i",$ligne,$re)){
            $ActiveConnections=$re[1];
            continue;
        }
        if(preg_match("#^([0-9]+)\s+([0-9]+)\s+([0-9]+)#",$ligne,$re)){
            $requests=$re[3];

        }
    }




    $html[]="<td style='width:99%;vertical-align:top'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='padding-left:10px;padding-top:20px'>";
    $html[]="<div class=\"col-lg-3\">";

    $html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-ethernet fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {connections}</span>
	<h2 class=\"font-bold\">".FormatNumber($ActiveConnections)."</h2>
	</div>
	</div>
	</div>";


    $html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-tachometer fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {requests}</span>
	<h2 class=\"font-bold\">".FormatNumber($requests)."</h2>
	</div>
	</div>
	</div>";



    $html[]="</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}