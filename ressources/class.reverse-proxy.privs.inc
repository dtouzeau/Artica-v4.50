<?php
class NginxPrivs{

    private $GroupDN;

    function __construct($GroupDN){
        $this->GroupDN=$GroupDN;
    }
    public function NginxPrivs():string{
        $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
        if($EnableNginx==0){
            return "";
        }
        // The daemon's /nginx-db/query/{table}/{column}/{value} route does not
        // URL-decode the value segment, so items containing ':' (e.g. "sqlite:1")
        // never match. Fetch the whole table and filter PHP-side instead.
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/adminprivs");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return "";
        }

        $ids=array();
        foreach ($data["rows"] as $ligne){
            if(!isset($ligne["item"]) || strval($ligne["item"])!==strval($this->GroupDN)){continue;}
            if(isset($ligne["serviceid"])){
                $ids[]=$ligne["serviceid"];
            }
        }
        if(count($ids)>0){
            return @implode(",",$ids);
        }
        return "";
    }
}
