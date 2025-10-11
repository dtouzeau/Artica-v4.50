<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ElasticSearchAddress"])){Save();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["ufdbdebug"])){ufdbdebug_js();exit;}

page();


function page(){
    $page=CurrentPageName();
    $Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FILEBEAT_VERSION");


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_FILEBEAT} v$Version &raquo;&raquo; {service_status}</h1>
	<p>{APP_FILEBEAT_EXPLAIN}</p>

	</div>

	</div>
		

		
	<div class='row'><div id='progress-filebeat-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-filebeat-status'></div>

	</div>
	</div>
		
		
		
	<script>
	$.address.state('/');
	$.address.value('/filebeat-status');
	LoadAjax('table-filebeat-status','$page?table=yes');
		
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_FILEBEAT} v$Version &raquo;&raquo; {service_status}",$html);
        echo $tpl->build_firewall();
        return;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $sock=new sockets();
    $sock->getFrameWork("filebeat.php?status=yes");
    $ini->loadFile("/usr/share/artica-postfix/ressources/logs/web/filebeat.status");


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/filebeat.restart.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/filebeat.restart.log";
    $ARRAY["CMD"]="filebeat.php?restart=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('table-3proxy-status','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestartfilebeat="Loadjs('fw.progress.php?content=$prgress&mainid=progress-filebeat-restart');";
    $eventsSent="";

    $APP_FILEBEAT_STATS=$sock->GET_INFO("APP_FILEBEAT_STATS");
    if(strlen($APP_FILEBEAT_STATS)>10){
        $json=json_decode($APP_FILEBEAT_STATS,true);
        if(array_key_exists('filebeat', $json) ){
            //<i class="fas fa-satellite-dish"></i>
            $events_sent=$json["filebeat"]["events"]["done"];
            $eventsSent=$tpl->widget_h("green","fas fa-satellite-dish",FormatNumber($events_sent),"{sent_events}");

        }
    }



    if(!is_file("/etc/artica-postfix/elasticsearch_remote_configured")){

        echo $tpl->FATAL_ERROR_SHOW_128("{filebeat_elasticsearch_not_configured}");
    }



    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px;vertical-align: top'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr><td>
	<div class=\"ibox\">
    	<div class=\"ibox-content\">".
        $tpl->SERVICE_STATUS($ini, "APP_FILEBEAT",$jsrestartfilebeat)
        ."$eventsSent</div>
	    	</div></td></tr>";


    $html[]="</table></td>";
    $html[]="<td style='width:99%;vertical-align:top'>";
    $ElasticSearchAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchAddress"));
    $ElasticsearchRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchRemotePort"));
    $ElasticSearchProtocol=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchProtocol"));
    $protocol['http']="http";
    $protocol['https']="https";
    if(empty($ElasticSearchProtocol)){$ElasticSearchProtocol='http';}
    $ElasticsearchEnableAuthFilebeat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchEnableAuthFilebeat"));
    $ElasticSearchUsernameFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchUsernameFilebeat"));
    $ElasticSearchPasswordFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchPasswordFilebeat"));
    if($ElasticsearchRemotePort==0){$ElasticsearchRemotePort=9200;}

    $form[]=$tpl->field_ipv4("ElasticSearchAddress","{elasticsearch_address}",$ElasticSearchAddress,true);
    $form[]=$tpl->field_numeric("ElasticSearchRemotePort","{elasticsearch_remote_port}",$ElasticsearchRemotePort);
    $form[]=$tpl->field_array_hash($protocol, "ElasticSearchProtocol", "nonull:{protocol}", $ElasticSearchProtocol);
    $form[]=$tpl->field_checkbox("ElasticsearchEnableAuthFilebeat","{authentication}",$ElasticsearchEnableAuthFilebeat,"ElasticSearchUsernameFilebeat,ElasticSearchPasswordFilebeat");
    $form[]=$tpl->field_text("ElasticSearchUsernameFilebeat", "{username}", $ElasticSearchUsernameFilebeat);
    $form[]=$tpl->field_password("ElasticSearchPasswordFilebeat", "{password}", $ElasticSearchPasswordFilebeat);
    $formula=$tpl->form_outside("{APP_ELASTICSEARCH}",$form,null,"{apply}",$jsrestartfilebeat,"AsWebStatisticsAdministrator",true);

    $html[]=$formula;

    $html[]="</td>";
    $html[]="</tr>";

    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    $ElasticSearchAddress=$_POST["ElasticSearchAddress"];
    $ElasticSearchRemotePort=$_POST["ElasticSearchRemotePort"];
    $ElasticSearchProtocol=$_POST["ElasticSearchProtocol"];
    $ElasticSearchUsernameFilebeat=$_POST["ElasticSearchUsernameFilebeat"];
    $ElasticSearchPasswordFilebeat=$_POST["ElasticSearchPasswordFilebeat"];
    if(empty($ElasticSearchProtocol)){$ElasticSearchProtocol='http';}
    if($_POST["ElasticsearchEnableAuthFilebeat"]==1){
        if(empty($ElasticSearchUsernameFilebeat)){
            echo "jserror:Username is mandatory";
            return false;
        }
        if(empty($ElasticSearchPasswordFilebeat)){
            echo "jserror:Password is mandatory";
            return false;
        }
    }

    $ch = curl_init();
    $method = "GET";
    $url = "$ElasticSearchProtocol://$ElasticSearchAddress:$ElasticSearchRemotePort/_cluster/stats?human&pretty";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    if($_POST["ElasticsearchEnableAuthFilebeat"]==1) {
        curl_setopt($ch, CURLOPT_USERPWD, "$ElasticSearchUsernameFilebeat:$ElasticSearchPasswordFilebeat");
    }
    $result = curl_exec($ch);

    if ($result === false) {
        $Error=curl_error($ch);
        echo "jserror:return network error code $Error";
        curl_close($ch);
        return;
    }

    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($responseCode >= 400) {
        echo "jserror:return HTTP error code $responseCode";
        curl_close($ch);
        return;
    }

    $tpl->SAVE_POSTs();

}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
    $tmp1 = round((float) $number, $decimals);
    while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
        $tmp1 = $tmp2;
    return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}