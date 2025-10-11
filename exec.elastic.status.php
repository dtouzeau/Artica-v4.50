"edc<?php
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');

if(isset($argv[1])) {
    if (preg_match("#--verbose#", implode(" ", $argv))) {
        $GLOBALS["VERBOSE"] = true;
    }
    if ($argv[1] == "--filebeat") {
        filebeat();
        exit;
    }
    if ($argv[1] == "--indices") {
        indices();
        exit;
    }
    if ($argv[1] == "--nodes") {
        nodes();
        exit;
    }
}

xstatus();



function xstatus(){

    $ElasticsearchBindPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
    if($ElasticsearchBindPort==0){$ElasticsearchBindPort=9200;}

    $ch = curl_init();
    $method = "GET";
    $url = "http://127.0.0.1:$ElasticsearchBindPort/_cluster/stats?human&pretty";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $result = curl_exec($ch);
    echo "Status ".strlen($result)." bytes\n";
    curl_close($ch);

    if(strlen($result)>200) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ELASTICSEARCH_STATUS", $result);
    }

    indices();
    nodes();
}

function indices(){

    $ElasticsearchBindPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
    if($ElasticsearchBindPort==0){$ElasticsearchBindPort=9200;}

    $ch = curl_init();
    $method = "GET";
    $url = "http://127.0.0.1:$ElasticsearchBindPort/_cat/indices?v&s=docs.count:desc&h=health,status,index,uuid,pri,rep,docs.count,docs.deleted,store.size,pri.store.size,tm&format=json";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $result = curl_exec($ch);
    echo "indices():: Status ".strlen($result)." bytes\n";
    curl_close($ch);

    if(strlen($result)>200) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ELASTICSEARCH_INDICES", $result);
    }
}

function nodes(){
    $ElasticsearchBindPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
    if($ElasticsearchBindPort==0){$ElasticsearchBindPort=9200;}

    $ch = curl_init();
    $method = "GET";
    $url = "http://127.0.0.1:$ElasticsearchBindPort/_nodes";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $result = curl_exec($ch);
    echo "nodes():: Status ".strlen($result)." bytes\n";
    curl_close($ch);

    if(strlen($result)>200) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ELASTICSEARCH_NODES", $result);
    }
    nodes_status();

}
function nodes_status(){
    $ElasticsearchBindPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
    if($ElasticsearchBindPort==0){$ElasticsearchBindPort=9200;}

    $ch = curl_init();
    $method = "GET";
    $url = "http://127.0.0.1:$ElasticsearchBindPort/_nodes/stats";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $result = curl_exec($ch);
    echo "nodes():: Status ".strlen($result)." bytes\n";
    curl_close($ch);

    if(strlen($result)>200) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ELASTICSEARCH_NODESSTATS", $result);
    }


}


function filebeat(){
    $ElasticsearchBindPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
    if($ElasticsearchBindPort==0){$ElasticsearchBindPort=9200;}
    $ch = curl_init();
    $method = "GET";
    $url = "http://127.0.0.1:$ElasticsearchBindPort/filebeat-*?human&pretty";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $result = curl_exec($ch);
    echo "Status ".strlen($result)." bytes\n";
    curl_close($ch);
    $json=json_decode($result);

    foreach ($json as $key=>$array){
        echo "Key: $key\n";
        if(preg_match("#^filebeat-#",trim($key))){
            return true;
        }
    }


    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    system("$php ".dirname(__FILE__)."/exec.filebeat.php --setup");

}

/**
 * Created by PhpStorm.
 * User: dtouzeau
 * Date: 04/05/19
 * Time: 10:58
 */