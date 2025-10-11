<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["ElasticsearchMaxMemory"])){save();exit;}
if(isset($_GET["ClusterLists-js"])){ClusterLists_js();exit;}
if(isset($_GET["ClusterLists-popup"])){ClusterLists_popup();exit;}
if(isset($_POST["ElasticSearchClusterLists2"])){ClusterLists_save();exit;}

page();

function ClusterLists_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{clusters_list}","$page?ClusterLists-popup=yes",650);
}

function ClusterLists_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress.txt";
    $ARRAY["CMD"]="elasticsearch.php?reload=yes";
    $ARRAY["TITLE"]="{restart_service}";
    $ARRAY["AFTER"]="LoadAjaxTiny('elasticsearch-status','$page?status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-elasticsearch-restart')";

    $ElasticSearchClusterLists=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchClusterLists"));
    $ElasticSearchClusterLists=str_replace(",","\n",$ElasticSearchClusterLists);
    $form[]=$tpl->field_textareacode("ElasticSearchClusterLists2","{clusters_list}",$ElasticSearchClusterLists);
    echo $tpl->form_outside("{clusters_list}",$form,"{elastic_cluster_explain}","{apply}",$jsrestart);
}

function ClusterLists_save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO(
        "ElasticSearchClusterLists",
        str_replace("\n",",",
            $_POST["ElasticSearchClusterLists2"]));



}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_VERSION");
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_ELASTICSEARCH} v$version</h1>
	<p>{APP_ELASTICSEARCH_EXPLAIN}</p>
	</div>

	</div>



	<div class='row'><div id='progress-elasticsearch-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-elasticsearch-service'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-elasticsearch-service','$page?tabs=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();

	$array["{status}"]="$page?table=yes";
	$array["{tables}"]="fw.elasticsearch.databases.php";
    $array["{nodes}"]="fw.elasticsearch.nodes.php";



	echo $tpl->tabs_default($array);

}

function table(){
	$page           = CurrentPageName();
	$tpl            = new template_admin();
	$users          = new usersMenus();
	$IPClass        = new IP();
	$sock           = new sockets();
	
	$html[]="<table style='width:100%;margin-top:20px'>";
	$html[]="<tr>";
	$html[]="<td style='width:300px;vertical-align:top'>";
	$html[]="<div id='elasticsearch-status'></div>";
	$html[]="</td>";
	$html[]="<td style='vertical-align:top;padding-left:20px'>";

    $ElasticsearchBehindReverse     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBehindReverse"));
    $ElasticSearchBehindHostname    = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchBehindHostname"));
    $ElasticSearchBehindCertificate = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchBehindCertificate"));
    $ElasticsearchAuthenticate      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchAuthenticate"));
    if($ElasticSearchBehindHostname==null){$GLOBALS["CLASS_SOCKETS"]->GET_INFO("fqdn_hostname");}

	$ElasticsearchMaxMemory     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchMaxMemory"));
	$ElasticsearchBindInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindInterface"));
	$ElasticsearchBindPort      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
    $ElasticsearchTransportPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchTransportPort"));

	$ElasticSearchClusterClient         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchClusterClient"));
    $ElasticSearchClusterClientInjest   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchClusterClientInjest"));
    $ClusterGroupName                   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterGroupName"));
    $ElasticSearchClusterLists          = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchClusterLists"));

    $ElasticsearchTransportInterface    = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchTransportInterface"));
    $ElasticSearch_FS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearch_FS"));
    if($ElasticSearch_FS==0){
        $ElasticSearch_FS=65536;
    }

    if($ClusterGroupName==null) {
        $LicenseInfos = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        $WizardSavedSettings = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
        if ($LicenseInfos["COMPANY"] == null) {
            $LicenseInfos["COMPANY"] = $WizardSavedSettings["company_name"];
        }
        $ClusterGroupName=$LicenseInfos["COMPANY"];
        $ClusterGroupName=str_replace(".","_",$ClusterGroupName);
        $ClusterGroupName=str_replace(" ","_",$ClusterGroupName);
    }
	
	if($ElasticsearchMaxMemory==0){$ElasticsearchMaxMemory=512;}
	if($ElasticsearchBindInterface==null){$ElasticsearchBindInterface="lo";}
	if($ElasticsearchBindPort==0){$ElasticsearchBindPort=9200;}
    if($ElasticsearchTransportPort==0){$ElasticsearchTransportPort=9300;}

	
	$INTERFACES["127.0.0.1"]="{loopback}";
	$INTERFACES["0.0.0.0"]="{all_interfaces}";

	
	$security="AsWebStatisticsAdministrator";

    $form[]=$tpl->field_section("{APP_ELASTICSEARCH}");


	$form[]=$tpl->field_checkbox("ElasticsearchBehindReverse","{ElasticsearchBehindReverse}",$ElasticsearchBehindReverse,"ElasticSearchBehindHostname,ElasticSearchBehindCertificate,ElasticsearchAuthenticate","{ElasticsearchBehindReverse_explain}");
    $form[]=$tpl->field_text("ElasticSearchBehindHostname","{ElasticSearchBehindHostname}",$ElasticSearchBehindHostname,false,"{ElasticSearchBehindHostname_explain}");
    $form[]=$tpl->field_certificate("ElasticSearchBehindCertificate","{use_certificate_from_certificate_center}",$ElasticSearchBehindCertificate);
    $form[]=$tpl->field_checkbox("ElasticsearchAuthenticate","{ElasticsearchAuthenticate}",$ElasticsearchAuthenticate,false,"{ElasticsearchAuthenticate_explain}");

    if($ElasticsearchBehindReverse==0) {
        $form[] = $tpl->field_interfaces("ElasticsearchBindInterface", "{listen_interface}", $ElasticsearchBindInterface);
        $form[] = $tpl->field_numeric("ElasticsearchBindPort", "{listen_port}", $ElasticsearchBindPort);
    }else{
        $form[]=$tpl->field_info("ElasticsearchBindInterfaceNone","{listen_interface}","127.0.0.1");
        $form[]=$tpl->field_info("ElasticsearchBindPortNone","{listen_port}","9200");
    }

    $form[]=$tpl->field_interfaces("ElasticsearchTransportInterface","{transport_interface}",$ElasticsearchTransportInterface,"{elk_transport_interface_explain}");

    $form[]=$tpl->field_numeric("ElasticsearchTransportPort","{transport_port}",$ElasticsearchTransportPort,"{elk_transport_port_explain}");
	$form[]=$tpl->field_numeric("ElasticsearchMaxMemory","{max_daemon_memory} (MB)",$ElasticsearchMaxMemory);
    $form[]=$tpl->field_multiple_64("ElasticSearch_FS","{file_descriptors} (Elasticsearch)",$ElasticSearch_FS,"");

	$form[]=$tpl->field_section("{cluster_configuration}");
    $form[]=$tpl->field_text("ClusterGroupName","{cluster_groupname}",$ClusterGroupName,true);
	$form[]=$tpl->field_none_bt("ElasticSearchClusterLists","{clusters_list}",$ElasticSearchClusterLists,"{edit}","Loadjs('$page?ClusterLists-js=yes')","{elastic_cluster_explain}");
    $form[]=$tpl->field_checkbox("ElasticSearchClusterClientInjest","{can_receive_data}",$ElasticSearchClusterClientInjest);


	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress.txt";
	$ARRAY["CMD"]="elasticsearch.php?restart=yes";
	$ARRAY["TITLE"]="{restart_service}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-elasticsearch-service','$page?tabs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-elasticsearch-restart')";
	
	$tpl->form_add_button("{join_a_cluster}","Loadjs('fw.elasticsearch.join.php')");

	$html[]=$tpl->form_outside("{statistics_database}", @implode("\n", $form),null,"{apply}",$jsrestart,$security);
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>LoadAjaxTiny('elasticsearch-status','$page?status=yes');</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function save(){

	$tpl=new template_admin();
    $tpl->SAVE_POSTs();
	
	

	
}

function status(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new template_admin();
	
	$page=CurrentPageName();
	$sock->getFrameWork("elasticsearch.php?status=yes");
	$ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/elasticsearch.status");
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress.txt";
	$ARRAY["CMD"]="elasticsearch.php?restart=yes";
	$ARRAY["TITLE"]="{restart_service}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-elasticsearch-service','$page?tabs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-elasticsearch-restart')";
	

	
	echo $tpl->SERVICE_STATUS($ini, "APP_ELASTICSEARCH",$jsrestart);
	//echo $tpl->SERVICE_STATUS($ini, "APP_ARTICALOGGER",$jsrestart_logger);
	$database_size=$tpl->_ENGINE_parse_body("{database_size}");

    $result=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_STATUS");

    if(strlen($result)>2000){
        $ELASTICSEARCH_STATUS=json_decode($result);


		$total_in_bytes=$ELASTICSEARCH_STATUS->nodes->fs->total_in_bytes;
		$used_in_bytes=$ELASTICSEARCH_STATUS->indices->store->total_data_set_size_in_bytes;
		$total=$ELASTICSEARCH_STATUS->nodes->fs->total;
		$used=$ELASTICSEARCH_STATUS->indices->store->size;
		//$used_percent=$ELASTICSEARCH_STATUS->nodes->os->mem->used_percent;


        $used_percent = ($used_in_bytes / $total_in_bytes) * 100;

  		$color="46a346";
		if($used_percent>70){$color="d32d2d";}
		
		echo "
	<center>
		<div class=\"ibox\">
			<div class=\"ibox-content\">
			<h5>$database_size</h5>
		<h2>$used / $total</h2>
		<div class=\"text-center\">
		<div id=\"sparkline\"></div>
		</div>
		</div>
		</div>
		</center>
		<script>$('#sparkline').sparkline([$used_in_bytes, $total_in_bytes], {
		type: 'pie',
		height: '140',
		sliceColors: ['#{$color}', '#F5F5F5']
		});\n</script>";
	}

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}