<?php


class dockerApi{

    private $Gateway="";
    private $RemotePort=9000;
    private $UseSSL=0;
    private $API_KEY;
    private $PermimeterID=0;
    private $GroupID=0;
    private $mysql_error="";
    public $data="";

    function __construct(){
        $this->Gateway=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerGateway");
        $Main=unserialize(@file_get_contents("/usr/share/artica-postfix/Docker/info.json"));
        $this->RemotePort=$Main["ArticaHttpsPort"];
        $this->UseSSL=$Main["ArticaHttpUseSSL"];
        $this->API_KEY=$Main["API_KEY"];
        $this->PermimeterID=$Main["perimeter"];
        $this->GroupID=$Main["groupid"];

    }

    function Get($EntryPoint):bool{
        $proto="https";
        if($this->UseSSL==0){
            $proto="http";
        }
        $uri="https://$this->Gateway:$this->RemotePort/nodes.listener.php?docker-api=$this->API_KEY&PermimeterID=$this->PermimeterID&GroupID=$this->GroupID&$EntryPoint";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_NOPROXY,"*");
        if($proto=="https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 'all');
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $this->data=curl_exec($ch);
        if($GLOBALS["VERBOSE"]){echo "$this->data\n";}
        $errno=curl_errno($ch);
        $error_text=curl_error($ch);
        $this->mysql_error="Error $errno - $error_text";
        $CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));

        if($CURLINFO_HTTP_CODE<>200){
            return false;
        }

        if($ch){@curl_close($ch);}
        return true;

    }


}