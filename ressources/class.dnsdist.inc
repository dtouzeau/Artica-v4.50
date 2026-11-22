<?php


class dnsdist_status{
    private $localip="127.0.0.1";
    public $error=null;
    public  $mainStats=array();
    private $json=array();

    public function __construct($localip="127.0.0.1"){
        if($localip<>null){$this->localip=$localip;}

    }

    public function generic_stats(){
        if(!$this->connect("servers/localhost/statistics")){
            return false;
        }
        $main=$this->json;


        foreach ($main as $index=>$array){
            $sMain[$array->name]=$array->value;

        }
        $responses=$sMain["responses"];
        $queries=$sMain["queries"];
        $prc=0;
        $miss=0;
        $hits=intval($sMain["cache-hits"]);
        if($hits>0) {
            $miss = $sMain["cache-misses"];
            $sum = $hits + $miss;
            $prc = $hits / $sum;
            $prc = round($prc * 100, 2);
        }
        $this->mainStats["responses"]=$responses;
        $this->mainStats["queries"]=$queries;
        $this->mainStats["hits"]=$hits;
        $this->mainStats["miss"]=$miss;
        $this->mainStats["cache_rate"]=$prc;
        return true;

    }


    private function connect($uri){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_INTERFACE, $this->localip);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-API-Key:artica"));
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_URL, "http://$this->localip:5600/api/v1/$uri");
        curl_setopt($ch, CURLOPT_NOPROXY,"*");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

        if ($result === false) {
            $Error=curl_error($ch);
            $this->div_error("{http_api_connection_error}||return network error code $Error");
            curl_close($ch);
            return false;
        }

        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($responseCode >= 400) {
            $this->div_error("{http_api_protocol_error}||return HTTP error code $responseCode");
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        $main=json_decode($result);
        $this->json=$main;
        return true;

    }

    private function div_error($line){
        $this->error=$line;
    }


}