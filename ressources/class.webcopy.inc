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
        $q      = new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $ligne=$q->mysqli_fetch_array("SELECT enforceuri FROM httrack_sites WHERE ID=$this->WebCopyID");
        $enforceuri=$ligne["enforceuri"];
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


