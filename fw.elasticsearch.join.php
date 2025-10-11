<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["ARTICA_MASTER"])){Save();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["step1"])){step1();exit;}
js();


function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{join_a_cluster}","$page?start=yes");
}

function start(){
    $page=CurrentPageName();

    echo "<div id='join-cluster-progress'></div><div id='join-cluster-div'></div>
    <script>LoadAjax('join-cluster-div','$page?step1=yes');</script>
    ";

}

function step1(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ELASTIC_CLUSTER_WIZARD=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTIC_CLUSTER_WIZARD"));
    $security="AsWebStatisticsAdministrator";

    $ElasticsearchTransportInterface=$ELASTIC_CLUSTER_WIZARD["ElasticsearchTransportInterface"];
    $ElasticsearchTransportPort=$ELASTIC_CLUSTER_WIZARD["ElasticsearchTransportPort"];
    if($ElasticsearchTransportPort==0){$ElasticsearchTransportPort=9300;}
    $ARTICA_MASTER=$ELASTIC_CLUSTER_WIZARD["ARTICA_MASTER"];
    if($ARTICA_MASTER==null){$ARTICA_MASTER="https://10.10.10.1:9000";}
    $form[]=$tpl->field_text("ARTICA_MASTER","{artica_master}",$ARTICA_MASTER,true);
    $form[]=$tpl->field_interfaces("ElasticsearchTransportInterface","{transport_interface}",$ElasticsearchTransportInterface,"{elk_transport_interface_explain}");
    $form[]=$tpl->field_numeric("ElasticsearchTransportPort","{transport_port}",$ElasticsearchTransportPort,"{elk_transport_port_explain}");



    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.nodes.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.nodes.progress.txt";
    $ARRAY["CMD"]="elasticsearch.php?nodes-wizard=yes";
    $ARRAY["TITLE"]="{join_cluster}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-elasticsearch-service','fw.elasticsearch.php?tabs=yes');dialogInstance1.close();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=join-cluster-progress')";

    $html=$tpl->form_outside("{join_cluster}", @implode("\n", $form),"{ELK_join_a_cluster_explain1}","{join}",$jsrestart,$security);
    echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ELASTIC_CLUSTER_WIZARD",serialize($_POST));



}