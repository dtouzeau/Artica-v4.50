<?php
include_once dirname(__FILE__) . "/class.ccurl.inc";

class elasticsearch
{

    public $CURL_ALL_INFOS;
    public $error_number;
    public $error_text;
    private $ElasticsearchBindPort;

    public function __construct()
    {
        $ElasticsearchBindPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
        if ($ElasticsearchBindPort == 0) {$ElasticsearchBindPort = 9200;}
        $this->ElasticsearchBindPort = $ElasticsearchBindPort;
    }

    public function GetMapping($database)
    {
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/$database/_mapping/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "XGET");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
        $result = curl_exec($ch);
        curl_close($ch);
        $array = json_decode($result);
        return $array->$database->mappings;

	}
	
	public function GetVersion() {
		$url = "http://127.0.0.1:{$this->ElasticsearchBindPort}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
		curl_setopt($ch, CURLOPT_NOPROXY, "*");
		$result = curl_exec($ch);
		$this->CURL_ALL_INFOS = curl_getinfo($ch);
		$error = curl_errno($ch);
		curl_close($ch);
		if ($error == 0) {
		$array = json_decode($result);
		return $array->version->number;
		}
		if ($error > 0) {
			return "0.0.0";
		}
	}

    public function CountOfDocuments($database)
    {
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/$database/_count/";
        if($GLOBALS["VERBOSE"]){echo "$url\n";}
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        if($GLOBALS["VERBOSE"]){echo "Run....\n";}
        $result = curl_exec($ch);
        $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
        $curl_errno=curl_errno($ch);
        $curl_errstr=curl_error($ch);

        if($GLOBALS["VERBOSE"]){echo "Done....$CURLINFO_HTTP_CODE/$curl_errno/$curl_errstr\n";}
        curl_close($ch);
        $array = json_decode($result);
        return $array->count;
    }
    public function statisticsOf($database)
    {
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/$database/_stats/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
        $result = curl_exec($ch);
        curl_close($ch);
        $array = json_decode($result);
        return $array;
    }
    public function remove_database($database)
    {


        $database = trim($database);
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/$database";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
        $result = curl_exec($ch);
        curl_close($ch);
        $array = json_decode($result);
        if (array_key_exists("acknowledged", $array)) {
            $acknowledged = $array->acknowledged;
            if ($acknowledged) {
                $sock=new sockets();
                $sock->getFrameWork("elasticsearch.php?indices=yes");
                return true;
            }
        }


        return $array;
    }

    public function GetIndex($database)
    {
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/$database/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
        $result = curl_exec($ch);
        curl_close($ch);
        $array = json_decode($result);
        if (array_key_exists($database, $array)) {
            $uuid = $array->$database->settings->index->uuid;
            $zdate = $array->$database->settings->index->creation_date;
            if ($GLOBALS["VERBOSE"]) {echo "$database $uuid OK created on $zdate\n";}
            return true;
        }
    }

    private function CreateIndex($database)
    {
        $ch = curl_init();
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/$database/";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "XPUT");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
        $result = curl_exec($ch);
        $array = json_decode($result);
        curl_close($ch);
        if (array_key_exists($database, $array)) {
            $uuid = $array->$database->settings->index->uuid;
            $zdate = $array->$database->settings->index->creation_date;
            if ($GLOBALS["VERBOSE"]) {echo "$database $uuid OK created on $zdate\n";}
            return true;
        }
    }

    public function postinfos($json, $database = "proxy")
    {
        /*    if(!$this->GetIndex($database)){if(!$this->CreateIndex($database)){
        $this->error_number=-1;
        $this->error_text="Create index failed";
        return false;};}
         */
        $ci = curl_init();
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/$database?pretty";
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_TIMEOUT, 200);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ci, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($ci);
        $this->CURL_ALL_INFOS = curl_getinfo($ci);
        $error = curl_errno($ci);

        $array = json_decode($response);
        curl_close($ci);

        if ($error == 0) {
            if (array_key_exists('acknowledged', $array)) {
                return $array->acknowledged;
                if ($GLOBALS["VERBOSE"]) {echo "postinfos:: OK\n";}
            }
        }

        if ($GLOBALS["VERBOSE"]) {echo "postinfos:: curl_close() Error N.$error " . strlen($response) . " bytes\n------------------------------------------\n$response\n------------------------------------------\n";}


        if ($error > 0) {
            $this->error_number = $error;
            return false;
        }

        if ($GLOBALS["VERBOSE"]) {print_r($array);}

        if (array_key_exists('status', $array)) {
            $this->error_number = $array->status;
        }

        if (array_key_exists('error', $array)) {
            $zz = array();
            foreach ($array->error->root_cause as $zarray) {

                while (list($key, $val) = each($zarray)) {
                    $zz[] = "$key: $val";
                }
            }
            $this->error_text = @implode("; ", $zz);
            return false;

        }

    }

    private function mapping_proxy_v7(){
        $maps_props = array();
        $maps_props["properties"]["timestamp"]["type"]="date";
        $maps_props["properties"]["timestamp"]["format"]="epoch_second";
        $maps_props["properties"]["timestamp_ms"]["type"] = "integer";
        $maps_props["properties"]["host"]["type"] = "keyword";
        $maps_props["properties"]["hierarchy"]["type"] = "keyword";
        $maps_props["properties"]["src_ip"]["type"] = "ip";
        $maps_props["properties"]["http_size"]["type"] = "integer";
        $maps_props["properties"]["request_status"]["type"] = "keyword";
        $maps_props["properties"]["response_time"]["type"] = "integer";
        $maps_props["properties"]["status_code"]["type"] = "integer";
        $maps_props["properties"]["http_method"]["type"] = "keyword";
        $maps_props["properties"]["content_type"]["type"] = "keyword";
        $maps_props["properties"]["website"]["type"] = "keyword";
        $maps_props["properties"]["familysite"]["type"] = "keyword";
        $maps_props["properties"]["user"]["type"] = "keyword";
        $maps_props["properties"]["category"]["type"] = "integer";
        $maps_props["properties"]["catname"]["type"] = "keyword";
        $maps_props["properties"]["filtered"]["type"] = "keyword";
        $maps_props["properties"]["filtered_rule"]["type"] = "keyword";
        $maps_props["properties"]["proxyname"]["type"] = "keyword";
        $maps_props["properties"]["server_ip"]["type"] = "ip";
        $maps_props["properties"]["mac"]["type"] = "keyword";
        $maps_props["properties"]["geoip.location"]["type"] = "geo_point";
        $maps["mappings"]=$maps_props;
        return  $maps;
    }


    private function mapping_proxy_v6(){
        $maps_props = array();
        $maps_props["_all"]["enabled"] = false;

        $maps_props["properties"]["timestamp"]["type"] = "date";
        $maps_props["properties"]["timestamp"]["format"] = "epoch_second";

        $maps_props["properties"]["timestamp_ms"]["type"] = "integer";
        $maps_props["properties"]["timestamp_ms"]["index"] = false;

        $maps_props["properties"]["host"]["type"] = "keyword";
        $maps_props["properties"]["host"]["index"] = true;

        $maps_props["properties"]["hierarchy"]["type"] = "keyword";
        $maps_props["properties"]["hierarchy"]["index"] = true;

        $maps_props["properties"]["src_ip"]["type"] = "ip";
        $maps_props["properties"]["src_ip"]["index"] = true;

        $maps_props["properties"]["http_size"]["type"] = "integer";
        $maps_props["properties"]["http_size"]["index"] = true;

        $maps_props["properties"]["request_status"]["type"] = "keyword";
        $maps_props["properties"]["request_status"]["index"] = true;

        $maps_props["properties"]["response_time"]["type"] = "integer";
        $maps_props["properties"]["response_time"]["index"] = false;

        $maps_props["properties"]["status_code"]["type"] = "integer";
        $maps_props["properties"]["status_code"]["index"] = true;

        $maps_props["properties"]["http_method"]["type"] = "keyword";
        $maps_props["properties"]["http_method"]["index"] = true;

        $maps_props["properties"]["content_type"]["type"] = "keyword";
        $maps_props["properties"]["content_type"]["index"] = true;

        $maps_props["properties"]["website"]["type"] = "keyword";
        $maps_props["properties"]["website"]["index"] = true;

        $maps_props["properties"]["familysite"]["type"] = "keyword";
        $maps_props["properties"]["familysite"]["index"] = true;

        $maps_props["properties"]["user"]["type"] = "keyword";
        $maps_props["properties"]["user"]["index"] = true;

        $maps_props["properties"]["category"]["type"] = "integer";
        $maps_props["properties"]["category"]["index"] = true;

        $maps_props["properties"]["catname"]["type"] = "keyword";
        $maps_props["properties"]["catname"]["index"] = true;

        $maps_props["properties"]["filtered"]["type"] = "keyword";
        $maps_props["properties"]["filtered"]["index"] = true;

        $maps_props["properties"]["filtered_rule"]["type"] = "keyword";
        $maps_props["properties"]["filtered_rule"]["index"] = true;

        $maps_props["properties"]["proxyname"]["type"] = "keyword";
        $maps_props["properties"]["proxyname"]["index"] = true;

        $maps_props["properties"]["server_ip"]["type"] = "ip";
        $maps_props["properties"]["server_ip"]["index"] = true;

        $maps_props["properties"]["mac"]["type"] = "keyword";
        $maps_props["properties"]["mac"]["index"] = true;

        $maps_props["properties"]["geoip.location"]["type"] = "geo_point";
        $maps_props["properties"]["geoip.location"]["index"] = true;

        $maps["settings"]["number_of_replicas"] = 1;
        $maps["settings"]["number_of_shards"] = 3;
        $maps["mappings"]["squid"]=$maps_props;
        return $maps;
    }



    public function mapping_proxy(){
        $maps= $this->mapping_proxy_v7();

        $json_doc = json_encode($maps);

        if ($GLOBALS["VERBOSE"]) {echo "postinfos -> proxy\n";}
        if (!$this->postinfos($json_doc, "proxy")) {
            echo "Failed with error : $this->error_number\n$this->error_text";

        } else {
            echo "Creating Ingest Pipline\n";
            $this->create_pipeline();
        }
    }

    public function create_pipeline()
    {
        $json = '{"description":"Add geoip info", "processors":[{"geoip":{"field":"server_ip"}}]}';
        $ci = curl_init();
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_ingest/pipeline/geoip";
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_TIMEOUT, 200);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ci, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($ci);
        $this->CURL_ALL_INFOS = curl_getinfo($ci);
        $error = curl_errno($ci);

        $array = json_decode($response);
        curl_close($ci);
        if ($error == 0) {
            if (array_key_exists('acknowledged', $array)) {
				$this->import_dashboard();
                return $array->acknowledged;
                if ($GLOBALS["VERBOSE"]) {echo "pipeline:: OK\n";}
            }
        }

        if ($GLOBALS["VERBOSE"]) {echo "pipeline:: curl_close() Error N.$error " . strlen($response) . " bytes\n------------------------------------------\n$response\n------------------------------------------\n";}


        if ($error > 0) {
            $this->error_number = $error;
            return false;
        }

        if ($GLOBALS["VERBOSE"]) {print_r($array);}

        if (array_key_exists('status', $array)) {
            $this->error_number = $array->status;
        }

        if (array_key_exists('error', $array)) {
            $zz = array();
            foreach ($array->error->root_cause as $zarray) {

                while (list($key, $val) = each($zarray)) {
                    $zz[] = "$key: $val";
                }
            }
            $this->error_text = @implode("; ", $zz);
            return false;

        }
    }

    public function import_dashboard()
    {

        $EnableKibana=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKibana"));
        if($EnableKibana==0){return true;}
        $ci = curl_init();
        $url = "http://127.0.0.1:5601/api/kibana/dashboards/import";
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_TIMEOUT, 200);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'kbn-xsrf: true'));
        curl_setopt($ci, CURLOPT_POSTFIELDS,  @file_get_contents('/usr/share/artica-postfix/ressources/export.json') );
        $response = curl_exec($ci);
        $this->CURL_ALL_INFOS = curl_getinfo($ci);
        $error = curl_errno($ci);

        $array = json_decode($response);
        curl_close($ci);
        if ($error == 0) {
            if (array_key_exists('acknowledged', $array)) {
                return $array->acknowledged;
                if ($GLOBALS["VERBOSE"]) {echo "dashboard:: OK\n";}
            }
        }

        if ($GLOBALS["VERBOSE"]) {echo "dashboard:: curl_close() Error N.$error " . strlen($response) . " bytes\n------------------------------------------\n$response\n------------------------------------------\n";}

        $ccurl = new ccurl(null, true, null, true);
        if ($error > 0) {
            $this->error_number = $error;
            return false;
        }

        if ($GLOBALS["VERBOSE"]) {print_r($array);}

        if (array_key_exists('status', $array)) {
            $this->error_number = $array->status;
        }

        if (array_key_exists('error', $array)) {
            $zz = array();
            foreach ($array->error->root_cause as $zarray) {

                while (list($key, $val) = each($zarray)) {
                    $zz[] = "$key: $val";
                }
            }
            $this->error_text = @implode("; ", $zz);
            return false;

        }
	}
	public function FullUpgradeStop_Step1() {
		$json = '{"persistent": {"cluster.routing.allocation.enable": "primaries"}}';
        $ci = curl_init();
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_cluster/settings";
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_TIMEOUT, 200);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ci, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($ci);
		$this->FullUpgradeStop_Step2();
		curl_close($ci);
	}

	public function FullUpgradeStop_Step2() {
		$url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_flush/synced";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
		$result = curl_exec($ch);
		$this->FullUpgradeStop_Step3();
        curl_close($ch);
	}

	public function FullUpgradeStop_Step3() {
		$url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_ml/set_upgrade_mode?enabled=true";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
		$result = curl_exec($ch);
        curl_close($ch);
	}

	public function FullUpgradeStart_Step1() {
		$json = '{"persistent": {"cluster.routing.allocation.enable": null}}';
        $ci = curl_init();
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_cluster/settings";
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_TIMEOUT, 200);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ci, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($ci);
		$this->FullUpgradeStart_Step2();
		curl_close($ci);
	}

	public function FullUpgradeStart_Step2() {
		$url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_ml/set_upgrade_mode?enabled=false";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
		$result = curl_exec($ch);
        curl_close($ch);
	}
	
	public function RollingUpgradeStop_Step1() {
		$json = '{"persistent": {"cluster.routing.allocation.enable": "primaries"}}';
        $ci = curl_init();
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_cluster/settings";
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_TIMEOUT, 200);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ci, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($ci);
		$this->RollingUpgradeStop_Step2();
		curl_close($ci);
	}

	public function RollingUpgradeStop_Step2() {
		$url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_flush/synced";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
		$result = curl_exec($ch);
		$this->RollingUpgradeStop_Step3();
        curl_close($ch);
	}

	public function RollingUpgradeStop_Step3() {
		$url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_ml/set_upgrade_mode?enabled=true";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
		$result = curl_exec($ch);
        curl_close($ch);
	}

	public function RollingUpgradeStart_Step1() {
		$json = '{"persistent": {"cluster.routing.allocation.enable": null}}';
        $ci = curl_init();
        $url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_cluster/settings";
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_TIMEOUT, 200);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ci, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($ci);
		$this->RollingUpgradeStart_Step2();
		curl_close($ci);
	}

	public function RollingUpgradeStart_Step2() {
		$url = "http://127.0.0.1:{$this->ElasticsearchBindPort}/_ml/set_upgrade_mode?enabled=false";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_NOPROXY, "*");
		$result = curl_exec($ch);
        curl_close($ch);
	}

}
