<?php
define("slapath","/usr/share/artica-postfix/ressources/logs/reverse-proxy/results.json");
define("vitripath","/usr/share/artica-postfix/ressources/logs/reverse-proxy/vitrification.json");

if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}

class NginxSla{

    public $MainArray = array();

    public function __construct(){

    }

    public function Parse(){
        $this->ParseSla();
        $this->ParseVitrifcation();
    }

    private function ParseVitrifcation():bool{
        if(!file_exists(vitripath)){
            return false;
        }
        $json=json_decode(@file_get_contents(vitripath));
        if(is_null($json)){
            return false;
        }
        if(!property_exists($json,"states")){
            return false;
        }
        foreach($json->states as $serviceid=>$JsonState){
            foreach ($JsonState->Hosts as $dom=>$jsonDom){
                $this->MainArray[$dom]["vitrification"]=$this->vitrificationAttributes($jsonDom);
            }
        }
        return true;
    }

    private function vitrificationAttributes($json):array{
        $array["PublicIP"]=$json->PublicIP;
        $array["Url"]=$json->Url;
        $array["errstr"]=$json->errstr;
        $array["status"]=$json->status;
        $array["statusstr"]=$json->statusstr;
        $array["workdir"]=$json->workdir;
        $array["package"]=$json->package;
        $array["packageSize"]=$json->packageSize;
        $array["checked_time"]=$json->checked_time;
        $array["serviceid"]=$json->serviceid;
        $array["aivalable"]=$json->aivalable;
        $array["update_time"]=$json->update_time;
        $array["package_time"]=$json->package_time;
        return $array;
    }


    private function ParseSla():bool{
        if(!file_exists(slapath)){
            return false;
        }
        $json=json_decode(@file_get_contents(slapath));
        if(is_null($json)){
            return false;
        }
        if(!property_exists($json,"services")){
            return false;
        }
        foreach($json->services as $serviceid=>$service){
            if(!property_exists($service,"domains")) {
                continue;
            }
            foreach($service->domains as $dom=>$domain){
                $this->MainArray[$dom]=$this->SlaAttributes($domain);
            }
        }
        return true;
    }

    private function SlaLatencies($json):array{
        $array["TTFBMs"]=$json->TTFBMs;
        $array["BackendMs"]=$json->BackendMs;
        $array["NetworkMs"]=$json->NetworkMs;
        $array["TTFBSource"]=$json->TTFBSource;
        $array["BEsource"]=$json->BEsource;
        return $array;
    }

    private function SlaAttributes($json):array{
        $array["time"] = $json->time;
        $array["url"] = $json->url;
        $array["Status"] = $json->Status;
        $array["resolved"] = $json->resolved;
        $array["error"] = $json->error;
        $array["headers"] = $json->headers;
        $array["latency"] = $this->SlaLatencies($json->latency);
        $array["execute_time"] = $json->execute_time;
        $array["serviceid"] = $json->serviceid;
        $array["report"] = $json->report;
        return $array;
    }

}
