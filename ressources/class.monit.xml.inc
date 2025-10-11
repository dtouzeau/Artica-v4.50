<?php
include_once(dirname(__FILE__)."/class.ccurl.inc");

class monit_xml{
    private $data_parsed=array();
    public $ok=true;
    private function fetchdata():string{
        $addr = '127.0.0.1';
        $port = 2874;
        $path = '/_status?format=xml';
        $timeout = 5;
        $this->ok=true;

        $curl=new ccurl("http://$addr:$port$path",true);
        $curl->NoHTTP_POST=true;
        $curl->Timeout=$timeout;
        $curl->NoLocalProxy();
        if(!$curl->get()){
            $this->events("[Artica]: Cannot connect to watchdog $addr:$port $curl->error");
            $this->ok=false;
            return "";
        }
        return $curl->data;

    }

    private function events($text){
        if(!function_exists("syslog")){return false;}
        openlog("monit", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
        return true;
    }

    public function parse():array{
        $data=$this->fetchdata();
        if(!function_exists("simplexml_load_string")){return array();}
        $xml=simplexml_load_string($data);
        if(!is_object($xml)){
            $this->events("[Artica]: [$data] cannot be parsed (not an object)");
            return $this->data_parsed;
        }

        if(!property_exists($xml,"server")){
            return $this->data_parsed;
        }



        foreach ($xml->server as $server=>$sxml){
            foreach ($sxml as $key=>$object) {
                $this->data_parsed["SERVER"][strval($key)] = strval($sxml->$key[0]);

            }
        }

        $iterable["filedescriptors"]=true;
        $iterable["memory"]=true;
        $iterable["cpu"]=true;
        $iterable["read"]=true;
        $iterable["write"]=true;
        $iterable["timestamps"]=true;
        $iterable["port"]=true;
        $iterable["servicetime"]=true;

        $iterable2["bytesgeneric"]=true;
        $iterable2["bytes"]=true;
        $iterable2["operations"]=true;

        $iterable3["limit"]=true;



        foreach ($xml as $key=>$object){
           if($key=="service"){
               if(!property_exists($object,"name")){continue;}
               $MasterKey=strval($object->name);
               foreach ($object as $subkey=>$subvalue) {
                    $keyname=strval($subkey);
                    $subvalStr=strval($subvalue);
                    if(isset($iterable[$keyname])){
                        $subvalStr=array();
                        foreach ($subvalue as $second=>$thired){
                            $thiredv=trim(strval($thired));
                            $secondKey=strval($second);
                            if(isset($iterable2[$secondKey])){
                                foreach ($thired as $thirdkey=>$OthirdValue){
                                    $thirdkey=trim(strval($thirdkey));
                                    $thirdValue=strval($OthirdValue);

                                    if(isset($iterable3[$thirdkey])){
                                        foreach ($OthirdValue as $FourKey=>$FourVazlue){
                                            $FourKey=strval($FourKey);
                                            $FourVazlue=strval($FourVazlue);
                                            $subvalStr[$thiredv][$thirdkey][$FourKey]=$FourVazlue;
                                        }
                                        continue;

                                    }

                                    if($thiredv==null){
                                        $subvalStr[$thirdkey]=$thirdValue;
                                        continue;
                                    }
                                    $subvalStr[$thiredv][$thirdkey]=$thirdValue;
                                }
                                continue;
                            }
                            $subvalStr[$secondKey]=$thiredv;
                        }
                    }

                   $this->data_parsed["SERVICES"][$MasterKey][$keyname] = $subvalStr;
               }
           }
        }






        $event[0] = 'OK';
        $event[1] = 'Checksum failed';
        $event[2] = 'Resource limit matched';
        $event[4] = 'Timeout';
        $event[8] = 'Timestamp failed';
        $event[16] = 'Size failed';
        $event[32] = 'Connection failed';
        $event[64] = 'Permission failed';
        $event[128] = 'UID failed';
        $event[256] = 'GID failed';
        $event[512] = 'Does not exist';
        $event[1024] = 'Invalid type';
        $event[2048] = 'Data access error';
        $event[4096] = 'Execution failed';
        $event[8192] = 'Changed';
        $event[16384] = 'ICMP failed';




        $uptime=intval($this->data_parsed['SERVER']['uptime']);
        $days = floor($uptime/60/60/24);
        $hours = floor($uptime/60/60%24);
        $mins = floor($uptime/60%60);
        $uptime_text=null;
        if($days>0){
            $uptime_text=$days.'d ';
        }
        if($hours>0){
            $uptime_text=$uptime_text.$hours.'h ';
        }
        if($mins>0){
            $uptime_text=$uptime_text.$mins.'m';
        }

        $this->data_parsed['SERVER']["UPTIME_TEXT"]=$uptime_text;
        return $this->data_parsed;


    }

}

