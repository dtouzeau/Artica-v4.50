<?php
class webcopy{
    private $WebCopyID=0;

    function __construct($WebCopyID=0){
        $this->WebCopyID=$WebCopyID;
    }

    public function WebCopyDir():string{
        $workingDir=$this->WebCopySrcDir();
        if($workingDir==""){return "";}
        $HostName=$this->WebCopyOriginalHostName($this->WebCopyID);
        if($HostName==""){return "";}
        return $workingDir."/$HostName";

    }
    public function WebCopyOriginalHostName():string{
        $ID=intval($this->WebCopyID);
        if($ID==0){return "";}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/httrack_sites/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return "";}
        $enforceuri=isset($data["rows"][0]["enforceuri"]) ? $data["rows"][0]["enforceuri"] : "";
        if($enforceuri==null){return "";}
        $url=parse_url($enforceuri);
        if(!isset($url["host"])){return "";}
        $hostname=$url["host"];
        if(isset($url["port"])){
            $hostname=$hostname."_".$url["port"];
        }
        return $hostname;
    }

    public function WebCopySrcDir():string{
        $WebCopyDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebCopyDir"));
        if(strlen($WebCopyDir)<4){$WebCopyDir="/home/artica/WebCopy";}
        return "$WebCopyDir/$this->WebCopyID";

    }

}


