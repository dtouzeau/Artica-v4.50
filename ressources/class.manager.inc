<?php

class class_manager{
    public $admin = null;
    public $password=null;
    public $suffix = null;
    public $server = null;
    public $port=389;
    function __construct(){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/manager"));
        if(!$json->Status) {
            VERBOSE("Status == fasle",__LINE__);
            $this->LoadDirect();
            return;
        }
        if(!property_exists($json,"admin")){
            if(!$this->LoadDirect()){
                writelogs("!property_exists ! admin",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
            }
        }
        $this->admin=$json->admin;
        $this->password=$json->password;
        $this->server=$json->server;
        $this->port=$json->port;
        $this->suffix=$json->suffix;

    }
    private function LoadDirect():bool{
        if(!is_readable("/etc/artica-postfix/ldap_settings/admin")){
            writelogs("/etc/artica-postfix/ldap_settings/admin not readable!!",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
            return false;
        }

        if(is_file("/etc/artica-postfix/ldap_settings/admin")){
            $this->admin=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/admin"));
        }
        if(is_file("/etc/artica-postfix/ldap_settings/suffix")){
            $this->suffix=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/suffix"));
        }
        if(is_file("/etc/artica-postfix/ldap_settings/password")){
            $this->password=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/password"));
        }
        if(is_file("/etc/artica-postfix/ldap_settings/server")){
            $this->server=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/server"));
        }
        if(is_file("/etc/artica-postfix/ldap_settings/port")){
            $this->port=intval(trim(@file_get_contents("/etc/artica-postfix/ldap_settings/port")));
        }
        return true;
    }
}
