<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
class ArticaNetAgents{

    private $id=0;

    public function __construct($id=0){
        $this->id=intval($id);
    }
    public function GetAgentHostname():string{
        if(isset($GLOBALS[__CLASS__.__FUNCTION__.$this->id])){
            return $GLOBALS[__CLASS__.__FUNCTION__.$this->id];
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$this->id"));
        if(!is_object($json)){
            return "";
        }
        if(!property_exists($json,"hostname")){
            return "";
        }
        $GLOBALS[__CLASS__.__FUNCTION__.$this->id]=$json->hostname;
        return $json->hostname;
    }
    function get_progress_bar($progress=0, $status=""):string {
        $barClass = "progress-bar-info";
        if ($status == "completed") {
            $barClass = "progress-bar-success";
        } elseif ($status == "failed") {
            $barClass = "progress-bar-danger";
            $progress=100;
        }

        return "<div class='progress' style='margin-bottom:0;'>
        <div class='progress-bar $barClass' role='progressbar' style='width: $progress%;'>$progress%</div>
    </div>";
    }
    public function get_status_badge(string $status):string {
        switch ($status) {
            case "pending":
                return "<span class='label label-default'><i class='fas fa-clock'></i> {pending}</span>";
            case "running":
                return "<span class='label label-info'><i class='fas fa-spinner fa-spin'></i> {running}</span>";
            case "completed":
                return "<span class='label label-success'><i class='fas fa-check'></i> {completed}</span>";
            case "failed":
                return "<span class='label label-danger'><i class='fas fa-times'></i> {failed}</span>";
            default:
                return "<span class='label label-default'>$status</span>";
        }
    }

    public function GET_INFO($key):string{
        $softs=$this->AgenSoft($this->id);
        if(!property_exists($softs,"artica_settings")){
            return "";
        }
        $set=$softs->artica_settings;
        if(!property_exists($set,$key)){
            return "";
        }
        return $set->$key;
    }
    private function AgenSoft($id){
        if($id==0){
            return json_encode(array());
        }
        if(isset($GLOBALS["AGENTSOFT"][$id])){
            if(!is_object($GLOBALS["AGENTSOFT"][$id])){
                return json_encode(array());
            }
            return $GLOBALS["AGENTSOFT"][$id];
        }
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/softwares/$id"));
        if(!is_object($json)){
            return json_encode(array());
        }
        $GLOBALS["AGENTSOFT"][$id]=$json;
        return $GLOBALS["AGENTSOFT"][$id];
    }

}