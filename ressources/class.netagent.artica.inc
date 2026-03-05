<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
class ArticaNetAgents{

    private $id=0;
    private $groupid=0;

    public function __construct($id=0){
        $this->id=intval($id);
    }
    public function GetAgentSoftwares():array{
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/softwares/$this->id"),true);
        if(!is_array($json)){
            return array();
        }
        return $json;
    }
    public function AgentArtica():bool{
        if(isset($GLOBALS["AGENTARICA"][$this->id])){
            return $GLOBALS["AGENTARICA"][$this->id];
        }
        $json=AgenSoft($this->id);
        if(!is_object($json)){
            $GLOBALS["AGENTARICA"][$this->id]=false;
            return false;
        }

        if(!property_exists($json,"artica_version") ){
            $GLOBALS["AGENTARICA"][$this->id]=false;
            return false;
        }
        if(strlen($json->artica_version)>3){
            $GLOBALS["AGENTARICA"][$this->id]=true;
            return true;
        }
        $GLOBALS["AGENTARICA"][$this->id]=false;
        return false;
    }
    function AgenSoft(){
        if(isset($GLOBALS["AGENTSOFT"][$this->id])){
            if(!is_object($GLOBALS["AGENTSOFT"][$this->id])){
                return json_encode(array());
            }
            return $GLOBALS["AGENTSOFT"][$this->id];
        }
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/softwares/$this->id"));
        if(!is_object($json)){
            return json_encode(array());
        }
        $GLOBALS["AGENTSOFT"][$this->id]=$json;
        return $GLOBALS["AGENTSOFT"][$this->id];
    }
    private function statusClass($agentJson):string{
        $status = $agentJson->status;
        if($status == "online"){
            return "label-primary";

        }
        if($status == "offline"){

            $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/export/$agentJson->id"));
            if(!is_object($json) || !property_exists($json,"client_cert")){
                return "label-default";
            }
            return "label-danger";
        }
        if($status == "pending"){
            $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/export/$agentJson->id"));
            if(!is_object($json) || !property_exists($json,"client_cert")){
                return "label-default";

            }
           return  "label-warning";
        }
        return "label-white";

    }
    public function LabelTypeStatus($agentJson):string{
        $statusClass=$this->statusClass($agentJson);
        $AgentArtica=$this->AgentArtica();
        if(!$AgentArtica){return "";}

        $tpl=new template_admin();

        $Enablehacluster=intval($this->GET_INFO("Enablehacluster"));
        if($Enablehacluster==1){
            return "<span class='label $statusClass'>HaCluster LB</span>";
        }
        $SquidEnable=intval($this->GET_INFO("SQUIDEnable"));
        if($SquidEnable==1){
            $HaClusterClient=intval($this->GET_INFO("HaClusterClient"));
            if($HaClusterClient==1){
                return $tpl->_ENGINE_parse_body("<span class='label $statusClass'>HaCluster Client</span>");
            }
            return $tpl->_ENGINE_parse_body("<span class='label $statusClass'>{APP_SQUID}</span>");
        }

        $EnableNginx=intval($this->GET_INFO("EnableNginx"));
        if($EnableNginx==1){
            return "<span class='label $statusClass'>Reverse-Proxy</span>";
        }
        $UnboundEnabled     =   intval($this->GET_INFO("UnboundEnabled"));
        if($UnboundEnabled==1){
            $EnableDHCPServer=$this->GET_INFO("EnableDHCPServer");
            if($EnableDHCPServer==1){
                return "<span class='label $statusClass'>DHCP/DNS</span>";
            }
            return $tpl->_ENGINE_parse_body("<span class='label $statusClass'>{APP_UNBOUND}</span>");
        }

        $EnableDNSDist=intval($this->GET_INFO("EnableDNSDist"));
        if($EnableDNSDist==1){
            return "<span class='label $statusClass'>DNS Firewall</span>";
        }



        return "";
    }
    private function ArticaNodeType():string{
            $status=$this->Status();
            $tpl=new template_admin();
            $f=array();
            if(!isset($status["dhcp3"])){
                $status["dhcp3"]["exists"]=false;
                $status["dhcp3"]["installed"]=false;
                $status["dhcp3"]["version"]="";
            }

            if($status["dhcp3"]["exists"] && $status["dhcp3"]["installed"]){
                $f[]=$tpl->td_href("<span class='label label-success'>{APP_DHCP}</span>",
                    "{dhcp_parameters}","Loadjs('fw.netagents.dhcp.php?dhcp-js=$this->id')");

            }

        return $tpl->_ENGINE_parse_body(@implode("\n",$f));
    }

    public function LabelType():string{


            $AgentArtica=$this->AgentArtica();
            if(!$AgentArtica){return $this->ArticaNodeType();}

            $tpl=new template_admin();

            $Enablehacluster=intval($this->GET_INFO("Enablehacluster"));
            if($Enablehacluster==1){
                return "<span class='label label-success'>HaCluster LB</span>";
            }
            $SquidEnable=intval($this->GET_INFO("SQUIDEnable"));
            if($SquidEnable==1){
                $HaClusterClient=intval($this->GET_INFO("HaClusterClient"));
                if($HaClusterClient==1){
                    return $tpl->_ENGINE_parse_body("<span class='label label-success'>HaCluster Client</span>");
                }
                return $tpl->_ENGINE_parse_body("<span class='label label-success'>{APP_SQUID}</span>");
            }

            $EnableNginx=intval($this->GET_INFO("EnableNginx"));
            if($EnableNginx==1){
                return "<span class='label label-success'>Reverse-Proxy</span>";
            }
            $UnboundEnabled     =   intval($this->GET_INFO("UnboundEnabled"));
            if($UnboundEnabled==1){
                $EnableDHCPServer=$this->GET_INFO("EnableDHCPServer");
                if($EnableDHCPServer==1){
                    return "<span class='label label-success'>DHCP/DNS</span>";
                }
                return $tpl->_ENGINE_parse_body("<span class='label label-success'>{APP_UNBOUND}</span>");
            }

        $EnableDNSDist=intval($this->GET_INFO("EnableDNSDist"));
        if($EnableDNSDist==1){
            return "<span class='label label-success'>DNS Firewall</span>";
        }



            return "";

    }
    public function SetGroup($id){
        $this->groupid=$id;
    }
    public function GroupInfo():array{
        if(isset($GLOBALS[__CLASS__.__FUNCTION__.$this->groupid])){
            return $GLOBALS[__CLASS__.__FUNCTION__.$this->groupid];
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$this->groupid"),true);
        if(!is_array($json)){
            return array();
        }

        if(!isset($json["name"])){
            return array();
        }

        $GLOBALS[__CLASS__.__FUNCTION__.$this->groupid]=$json;
        return $json;
    }
    public function Status():array{
        if(isset($GLOBALS[__CLASS__.__FUNCTION__.$this->id])){
            return $GLOBALS[__CLASS__.__FUNCTION__.$this->id];
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$this->id"),true);

        if(!array($json)){
            return array();
        }
        if(!isset($json["hostname"])){
            return array();
        }
        $GLOBALS[__CLASS__.__FUNCTION__.$this->id]=$json;
        return $json;


    }

    public function GetAgentHostname():string{
        if(isset($GLOBALS[__CLASS__.__FUNCTION__.$this->id])){
            return $GLOBALS[__CLASS__.__FUNCTION__.$this->id];
        }
        $array=$this->Status();
        if(!isset($array["hostname"])){
            $array["hostname"]="";
        }
        $GLOBALS[__CLASS__.__FUNCTION__.$this->id]=$array["hostname"];
        return $array["hostname"];
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


}