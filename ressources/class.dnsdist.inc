<?php


class dnsdist_status{
    private $localip="127.0.0.1";
    public $error=null;
    public  $mainStats=array();
    private $json=array();
    public $rules_array=array();
    public $pools_array=array();

    public function __construct($localip="127.0.0.1"){
        if($localip<>null){$this->localip=$localip;}

    }

    public function localhost():bool{
        if(!$this->connect("servers/localhost")){
            VERBOSE("Connection Failed!!!",__LINE__);
            return false;
        }
        VERBOSE("Connection Success",__LINE__);
        $main=$this->json;

        if(property_exists($main,"servers")){
            $this->parse_servers($main);
        }
        if(property_exists($main,"pools")){
            $this->parse_pools($main);
        }

        if(property_exists($main,"rules")){
            $this->parse_rules($main);
        }
        return true;

    }
    private function parse_pools($main){

        foreach ($main->pools as $index=>$pools){
            $pool=$pools->name;
            if($pool==null){continue;}
            $hits=$pools->cacheHits;
            $misses=$pools->cacheMisses;
            $cacheSize=$pools->cacheSize;
            $cacheEntries=$pools->cacheEntries;
            $this->pools_array[$pool]["STATS"]=array(
                "hits"=>$hits,
                "miss"=>$misses,
                "cachesize"=>$cacheSize,
                "entries"=>$cacheEntries);

        }
    }

    private function parse_servers($main){

        foreach ($main->servers as $index=>$servers){
            $address=$servers->address;
            $name=$servers->name;
            $latency=$servers->latency;
            $queries=$servers->queries;
            $state=$servers->state;
            $pool=$servers->pools[0];
            $id=$servers->id;

            $this->pools_array[$pool]["SERVERS"][]=array(
                "address"=>$address,
                "name"=>$name,
                "latency"=>$latency,
                "queries"=>$queries,
                "state"=>$state,
                "id"=>$id
            );
        }

    }
    private function parse_rules($main){
        foreach ($main->rules as $index=>$rules){
            $this->rules_array[$rules->uuid]=$rules->matches;

        }
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