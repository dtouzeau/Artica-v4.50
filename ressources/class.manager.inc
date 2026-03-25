<?php

class class_manager{
    public $admin = null;
    public $password=null;
    public $suffix = null;
    public $server = null;
    public $port=389;

    function __construct(){
        if(!isset($GLOBALS["CLASS_SOCKETS"])){
            $GLOBALS["CLASS_SOCKETS"]=new sockets();
        }

        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/manager"));
        $this->admin="Manager";
        $this->password="secret";
        $this->server="127.0.0.1";
        $this->port=389;
        // REST API returned valid credentials
        if(is_object($json) && !empty($json->Status) && property_exists($json,"admin") && strlen($json->admin)>0){
            $this->admin=$json->admin;
            $this->password=base64_decode($json->password ?? '');
            $this->server=$json->server ?? '127.0.0.1';
            $this->port=intval($json->port ?? 389);
            $this->suffix=$json->suffix ?? '';
            return;
        }
        writelogs("Unable to load admin credentials from REST API",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);

    }


}
