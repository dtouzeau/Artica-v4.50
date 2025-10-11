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

        $q=new lib_sqlite($this->NginxGetDB());

        if(!$q->TABLE_EXISTS("adminprivs")){
            return "";
        }

        $results=$q->QUERY_SQL("SELECT * FROM adminprivs WHERE item='$this->GroupDN'");
        if(!$q->ok){
            return "";
        }

        foreach ($results as $index=>$ligne){
            $results[]=$ligne["serviceid"];
        }
        if(count($results)>0){
            return @implode(",",$results);
        }
        return "";
    }
    private function NginxGetDB():string{
        if(!$this->isHarmpID()){
            return "/home/artica/SQLITE/nginx.db";
        }
        $Gpid=$_SESSION["HARMPID"];
        return "/home/artica/SQLITE/nginx.$Gpid.db";
    }
    private function isHarmpID():bool{
        if(!isset($_SESSION["HARMPID"])){
            return false;
        }
        if(intval($_SESSION["HARMPID"])==0){
            return false;
        }

        return true;
    }
}