<?php
/*
 * REST helpers for /home/artica/SQLITE/haproxy.db (hacluster_backends).
 * Replaces direct lib_sqlite() use from the fw.hacluster.* pages.
 *
 * Backed by the cached articarest endpoints under /hacluster/db/backends/*.
 * Every helper returns plain PHP values — array() or 0/false on failure —
 * so call sites do not need to inspect the JSON envelope.
 */

class HaClusterBackends{
    private $ligne=array();
    private $BackendID=0;
    public $MetricsArray=array();
    public $isDisconnected=0;
    public $BackenEnabled=0;
    public $BackendState=0;
    public $BackendStateAdm=0;
    public $HostNameInDB="";
    public $BtnClass="";
    public $LabelClass="";
    public $ArticaVersion="";
    public $StatusText="";
    public $Zoomjs="";
    public $StatusLine=0;
    public $Color="";
    public $DotColor="";
    public $WarningDB=0;
    public $HaClusterClientMaxMember=0;
    public $HaClusterClientEmergencyLoad=0;
    public $HaClusterClientMaxLoad=0;
    public $HaClusterClientMaxLoadPeriod=0;
    public $HaClusterClientMoniCPU=0;
    public $MetricCPUPercent=0;
    public $MetricCPUText="";
    public $MetricLoadPercent=0;
    public $MetricLoadText="";
    public $MetricsMaxClients=0;
    public $MetricsNumberOfIPs=0;
    public $MetricsNumberOfSessions=0;
    public $MetricsNumberOfRequests=0;
    public $CPUNumber=0;


    public function __construct($BackendID){
        $this->BackendID=$BackendID;
        if(!isset($_SESSION["BACKENDS_STATS"]) OR !isset($_SESSION["backendsSess"]) OR !isset($GLOBALS["backends"])){
            $this->Prepare();
        }
        $this->Status();
    }

    private function unInset(){
        if(!isset($this->ligne["ID"])){
            $this->ligne["ID"]=0;
        }
        if(!isset($this->ligne["status"])){
            $this->ligne["status"]=0;
        }
    }
    public function Opstate():int{
        $ID=$this->BackendID;

        $SrvProxy="proxy$ID";
        if(!isset($_SESSION["BACKENDS_STATS"][$SrvProxy])){
            return 99;
        }

        $json=$_SESSION["BACKENDS_STATS"][$SrvProxy];

        if(!property_exists($json,"srv_op_state")){
            return 99;
        }
        return $json->srv_op_state;
    }
    public function OpFront(){
        $ID=$this->BackendID;
        $backends=$GLOBALS["backends"];

        if(!is_object($backends)){
            VERBOSE("$ID) backends object not found",__LINE__);
            return 99;
        }
        if(!property_exists($backends,"proxy$ID")){
            VERBOSE("$ID no property",__LINE__);
            return 99;
        }
        $backendState=$backends->{"proxy$ID"};
        if(!property_exists($backendState,"srv_admin_state")){
            VERBOSE("$ID no property srv_admin_state",__LINE__);
            return 99;
        }
        return intval($backendState->srv_admin_state);

    }
    private function Prepare():bool{
        $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/inproduction");
        $json=json_decode($data);
        $_SESSION["jsonstats"]="";
        $_SESSION["backendsSess"]="";
        $_SESSION["inprod"]="";
        $GLOBALS["backends"]="";
        if(is_object($json)){
            $_SESSION["inprod"]=$json->Info ?? "";
            $_SESSION["jsonstats"]=base64_encode(json_encode($json->Stats ?? null));
            $_SESSION["backendsSess"]=base64_encode(json_encode($json->Sessions ?? null));
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/clients"));
        if(is_object($json) && property_exists($json,"backends")) {
            $GLOBALS["backends"] = $json->backends;
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/stats"));
        if(!empty($json->Status)) {
            foreach (($json->Stats ?? []) as $SrvProxy=>$class){
                $_SESSION["BACKENDS_STATS"][$SrvProxy]=$class;
            }
        }

        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/inproduction"));

        if(empty($json->Status)){
            $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_TD_PREPARE_INPRODUCTION"));
        }else{
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HACLUSTER_TD_PREPARE_INPRODUCTION",json_encode($json));
        }

        $_SESSION["inprod"]=$json->Info ?? "";
        if(is_object($json) && property_exists($json,"Stats") && isset($json->Stats)) {
            $_SESSION["jsonstats"] = base64_encode(json_encode($json->Stats));
        }
        if(is_object($json) && property_exists($json,"Sessions") && isset($json->Sessions)) {
            $_SESSION["backendsSess"] = base64_encode(json_encode($json->Sessions));
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/clients"));
        if(empty($json->Status)){
            $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_TD_PREPARE_CLIENTS"));
        }else{
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HACLUSTER_TD_PREPARE_CLIENTS",json_encode($json));
        }

        if(is_object($json) && property_exists($json,"backends")) {
            $GLOBALS["backends"] = $json->backends;
        }
        if(isset($_SESSION["BACKENDS_STATS"])) {
            unset($_SESSION["BACKENDS_STATS"]);
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/stats"));
        if(empty($json->Status)){
            $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HACLUSTER_TD_PREPARE_STATS"));
        }else{
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HACLUSTER_TD_PREPARE_STATS",json_encode($json));
        }
        if(is_object($json) && property_exists($json,"Status")) {
            if ($json->Status) {
                foreach (($json->Stats ?? []) as $SrvProxy => $class) {
                    $_SESSION["BACKENDS_STATS"][$SrvProxy] = $class;
                }
            }
        }
        return true;
    }
    private function Linkzoom(){
        $this->Zoomjs= "Loadjs('fw.hacluster.backends.zoom.php?backend-zoom-js=$this->BackendID');";
    }

    private function settingsDefaults():array{
        $agentcfg=unserializeb64($this->ligne["agentcfg"]);
        if(isset($agentcfg["HaClusterClientMaxLoad"]) && $agentcfg["HaClusterClientMaxLoad"]>0){
            if(!isset($agentcfg["HaClusterClientMaxMember"])){
                $agentcfg["HaClusterClientMaxMember"]=500;
            }

            return $agentcfg;
        }
        $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
        $HaClusterClientMaxLoad=intval($HaClusterGBConfig["HaClusterClientMaxLoad"]);
        $HaClusterClientMaxLoadPeriod=intval($HaClusterGBConfig["HaClusterClientMaxLoadPeriod"]);
        $HaClusterClientMoniCPU=intval($HaClusterGBConfig["HaClusterClientMoniCPU"]);


        if($HaClusterClientMaxLoad==0){$HaClusterClientMaxLoad=3;}
        $HaClusterClientEmergencyLoad=intval($HaClusterGBConfig["HaClusterClientCheckGlobal"]);
        if($HaClusterClientEmergencyLoad==0){$HaClusterClientEmergencyLoad=15;}
        if($HaClusterClientEmergencyLoad<3){$HaClusterClientEmergencyLoad=3;}
        $agentcfg["HaClusterClientMaxLoad"]=$HaClusterClientMaxLoad;
        $agentcfg["HaClusterClientMaxLoadPeriod"]=$HaClusterClientMaxLoadPeriod;
        $agentcfg["HaClusterClientMoniCPU"]=$HaClusterClientMoniCPU;
        $agentcfg["HaClusterClientEmergencyLoad"]=$HaClusterClientEmergencyLoad;
        if(!isset($agentcfg["HaClusterClientMaxMember"])){
            $agentcfg["HaClusterClientMaxMember"]=500;
        }
        return $agentcfg;

    }

   private function settings(){
       $agentcfg=$this->settingsDefaults();
       $this->HaClusterClientMaxLoad=intval($agentcfg["HaClusterClientMaxLoad"]);
       $this->HaClusterClientMaxLoadPeriod=intval($agentcfg["HaClusterClientMaxLoadPeriod"]);
       $this->HaClusterClientMoniCPU=intval($agentcfg["HaClusterClientMoniCPU"]);
       $this->HaClusterClientMaxMember=intval($agentcfg["HaClusterClientMaxMember"]);
       $this->HaClusterClientEmergencyLoad=intval($agentcfg["HaClusterClientEmergencyLoad"]);

       if($this->HaClusterClientMaxMember==0){
           $this->HaClusterClientMaxMember=500;
       }
       if($this->HaClusterClientEmergencyLoad==0){
           $this->HaClusterClientEmergencyLoad=15;
       }
       if($this->HaClusterClientEmergencyLoad<3){
           $this->HaClusterClientEmergencyLoad=3;
       }
       if($this->HaClusterClientMaxLoad==0){
           $this->HaClusterClientMaxLoad=3;
       }
   }

   private function warningDB():int{
        if(!isset($this->ligne["ID"])){
            return 0;
        }
        if(!isset($this->ligne["status"])){
            return 0;
        }
        if($this->ligne["status"]==100){
            return 0;
        }
        return 1;
    }

    private function version():bool{
        $articarest="";
        if(isset($this->ligne["articarest"])){
            $articarest=$this->ligne["articarest"];
        }

        $v=array();
        if(isset($this->ligne["articaversion"])){
            $this->ligne["articaversion"]=str_replace(".000000","",$this->ligne["articaversion"]);
            $v[]=$this->ligne["articaversion"];
        }
        if($articarest<>null){
            $v[]=$articarest;
        }

        if(count($v)>0){
            $this->ArticaVersion=@implode("&nbsp;/&nbsp;",$v);
        }
        return true;
    }

    private function loadPercent(){
        $ClientLoad="";
        $Metrics=$this->peityLoads();
        if(count($Metrics)>0){
            $ClientLoad=$Metrics["LOAD"];
        }

        $sparkMax=$this->HaClusterClientMaxLoad;
        $clRaw = strval($ClientLoad);
        if(isset($_GET["verbose"])){
            echo "// ClientLoad -> $ClientLoad\n";
        }
        $clNoCpu="";
        if(preg_match('/CPU\s*:\s*([0-9]+(?:\.[0-9]+)?)\s*%/i', $clRaw, $mCpu)){
            $cpuPct = floatval($mCpu[1]);
            $this->MetricCPUText = $mCpu[1]."%";
            $this->MetricCPUPercent = min(100, max(0, $cpuPct));
            $clNoCpu = preg_replace('/CPU\s*:[0-9.]+\s*%/i', '', $clRaw);
        } else {
            $this->MetricCPUText = $Metrics["CPU_AVG"];
            $this->MetricCPUPercent =  $Metrics["CPU_AVG"];
            if(preg_match("#([0-9.]+)#",$this->MetricCPUPercent,$re)){
                $this->MetricCPUPercent=$re[1];
                $this->MetricCPUText = $re[1];
            }
        }

        if(preg_match('/([0-9]+(?:\.[0-9]+)?)/', strval(strip_tags($clNoCpu)), $mLoad)){
            $loadValue = floatval($mLoad[1]);
            $this->MetricLoadText = $mLoad[1];
            if($sparkMax > 0){
                $this->MetricLoadPercent = min(100, max(0, ($loadValue / $sparkMax) * 100));
            } else {
                // No configured max → fall back to capping at 100 of the raw value.
                $this->MetricLoadPercent = min(100, max(0, $loadValue));
            }
        }else{
            // Regex above couldn't find any numeric load value in $clNoCpu, so
            // $clRaw is non-numeric. Dividing it would TypeError under PHP 8.
            $this->MetricLoadText = $clRaw;
            $this->MetricLoadPercent = 0;
        }
    }

    private function __LocalClusterInfo():array{
        if(isset($GLOBALS["__LocalClusterInfo"])){
            return $GLOBALS["__LocalClusterInfo"];
        }
        $GLOBALS["__LocalClusterInfo"]["ClusterVersion"]="";

        $nodes_status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/status"),true);
        if(!isset($nodes_status["MasterID"])){
            VERBOSE("No MasterID",__LINE__);
            $GLOBALS["__LocalClusterInfo"]["MasterID"]=0;
            return $GLOBALS["__LocalClusterInfo"];
        }
        $master_id=intval($nodes_status["MasterID"]);
        if($master_id==0){
            VERBOSE("MasterID == 0",__LINE__);
            $GLOBALS["__LocalClusterInfo"]["MasterID"]=0;
            return $GLOBALS["__LocalClusterInfo"];
        }

        $ClusterVersion=$nodes_status["ClusterVersion"];
        if(strlen($ClusterVersion)<5) {
            VERBOSE("$ClusterVersion len <5",__LINE__);
            $GLOBALS["__LocalClusterInfo"]["MasterID"]=0;
            $GLOBALS["__LocalClusterInfo"]["ClusterVersion"]="";
            return $GLOBALS["__LocalClusterInfo"];
        }
        VERBOSE("$master_id --> $ClusterVersion",__LINE__);
        $GLOBALS["__LocalClusterInfo"]["MasterID"]=$master_id;
        $GLOBALS["__LocalClusterInfo"]["ClusterVersion"]=$ClusterVersion;
        return $GLOBALS["__LocalClusterInfo"];
    }

    public function SMPDisabled():bool{
        $this->getMetricsArray();
        if(!isset($this->MetricsArray["SMP_DISABLED"])){
            return false;
        }
        $val=intval($this->MetricsArray["SMP_DISABLED"]);
        if($val==1){
            return true;
        }
        return false;
    }

    public function TextClusterPackage():string{
        $this->getMetricsArray();

        if(isset($this->MetricsArray["CLUSTER_MASTER_VERSION"])) {
            $PackageVersion=$this->MetricsArray["CLUSTER_MASTER_VERSION"];
            return  "<span>$PackageVersion</span>";
        }

        if(!isset($this->MetricsArray["CLUSTER_SLAVE_INFO"])) {
            return "-";
        }
        $PackageVersion="-";
        $ClusterInfo = json_decode($this->MetricsArray["CLUSTER_SLAVE_INFO"], true);
        if(isset($ClusterInfo["PackageVersion"])){
            $PackageVersion=$ClusterInfo["PackageVersion"];
        }


        $MyCluster=$this->__LocalClusterInfo();
        if($MyCluster["MasterID"]>0 && strlen($PackageVersion)>3) {
           $Locaver=$MyCluster["ClusterVersion"];
           if( version_is_lower($Locaver,$PackageVersion)){
               return "<span class=\"text-warning\">$PackageVersion</span>";
           }
        }
        return  "<span>$PackageVersion</span>";

    }

    public function ReplicFreezed():bool{

        if(!isset($this->MetricsArray["HaClusterFreezeReplic"])){
            return false;
        }
        if(intval($this->MetricsArray["HaClusterFreezeReplic"])==1){
            return true;
        }
        return false;
    }

    private function getMetricsArray():bool{

        if(is_array($this->MetricsArray)){
            if(count($this->MetricsArray)>5){
                return true;
            }
        }

        $workdir="ressources/logs/hacluster/$this->BackendID";
        $workfile="$workdir/HaClusterMetrics.array";
        if(!is_file($workfile)){
            VERBOSE("$workfile does not exist",__LINE__);
            $this->MetricsArray=array();
            return false;
        }

        $serialized=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(file_get_contents($workfile));
        if(!$serialized){
            $this->MetricsArray=array();
            return false;
        }
        if(!is_array($serialized)){
            $this->MetricsArray=array();
            return false;
        }
        //print_r($serialized);
        $this->MetricsArray=$serialized;
        return true;
    }

    private function peityLoads():array{

        $this->getMetricsArray();
        $tpl=new template_admin();
        if(!isset($this->MetricsArray["LOAD"])){
            $this->MetricsArray["LOAD"]=0;
        }
        $Metrics["LOAD"]=$this->MetricsArray["LOAD"];
        if($GLOBALS["VERBOSE"]){
            echo "----------------------------- Server ID $this->BackendID\n";
            foreach ($this->MetricsArray as $key=>$val){
                echo "\tKey  = [$key] = [$val]\n";
            }
        }
        $REQUESTS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($this->MetricsArray["REQUESTS"]);
        if($GLOBALS["VERBOSE"]) {
            print_r($REQUESTS);
        }
        if(!isset($REQUESTS["RQS"])){
            $REQUESTS["RQS"]=0;
        }
        $Metrics["RQS"]=$tpl->FormatNumber($REQUESTS["RQS"]);
        if(!isset($REQUESTS["RQSS"])){$REQUESTS["RQSS"]=0;}
        $Metrics["HTTPS"]=$REQUESTS["RQSS"]."/s";
        if(isset($this->MetricsArray["REQUESTS_SECONDS"])){
            $Metrics["HTTPS"]=$this->MetricsArray["REQUESTS_SECONDS"]."/s";
        }
        if(isset($this->MetricsArray["CPU_AVG"])) {
            VERBOSE("CPU_AVG: {$this->MetricsArray["CPU_AVG"]}",__LINE__);
            $Metrics["CPU_AVG"] = "&nbsp;CPU:".$this->MetricsArray["CPU_AVG"]."%";
        }


        if(isset($this->MetricsArray["LOADLINE"])) {
            VERBOSE("LoadLine: {$this->MetricsArray["LOADLINE"]}",__LINE__);
            $Metrics["LOADLINE"] = $this->MetricsArray["LOADLINE"];
        }
        if(isset($this->MetricsArray["LOADLINE"])) {
            VERBOSE("LoadLine: {$this->MetricsArray["LOADLINE"]}",__LINE__);
            $Metrics["LOADLINE"] = $this->MetricsArray["LOADLINE"];
        }

        $zload=array();
        if(!isset($Metrics["LOADLINE"])) {
            if(isset($REQUESTS["TIMES"])) {
                foreach ($REQUESTS["TIMES"] as $time => $value) {
                    $value = floatval($value);
                    $zload[] = $value;
                }
            }
            $Metrics["LOADLINE"] = implode(",", $zload);
            VERBOSE("LOADLINE = {$Metrics["LOADLINE"]}");
        }

        return $Metrics;


    }
    private function metrics(){
        $metricFile=dirname(__FILE__)."/logs/HaClusterClientMetrics.$this->BackendID";
        if(is_file($metricFile)){
            $data=@file_get_contents($metricFile);
            $array=$this->MetricsToMain($data);
            // explode(",") emits strings; coerce so downstream consumers (CSS
            // width interpolation, future arithmetic) don't see "12.5" as text.
            if(isset($array["cpupercent"])){
                $this->MetricCPUPercent=floatval($array["cpupercent"]);
            }
            if(isset($array["Cpunumber"])){
                $this->CPUNumber=intval($array["Cpunumber"]);
            }
        }
        $metricFile=dirname(__FILE__)."/logs/hacluster/$this->BackendID/report.json";
        if(is_file($metricFile)){
            $json=json_decode(file_get_contents($metricFile),true);
            if(isset($json["MetricsSessions"])){
                if(isset($json["MetricsSessions"]["maxClients"])){
                    $this->MetricsMaxClients=$json["MetricsSessions"]["maxClients"];
                }
                if(isset($json["MetricsSessions"]["numberOfIPs"])){
                    $this->MetricsNumberOfIPs=$json["MetricsSessions"]["numberOfIPs"];
                }
                if(isset($json["MetricsSessions"]["numberOfSessions"])){
                    $this->MetricsNumberOfSessions=$json["MetricsSessions"]["numberOfSessions"];
                }
                if(isset($json["MetricsSessions"]["current_requests"])){
                    $this->MetricsNumberOfRequests=$json["MetricsSessions"]["current_requests"];
                }

            }
        }



        $this->loadPercent();
    }
    function MetricsToMain($row):array{
        $MAIN=array();
        $array=explode(",",$row);
        // Function reads $array[0]..$array[7] below, so the row must carry at
        // least 8 comma-separated fields. The previous "count($MAIN) < 4" check
        // was a typo (always 0 < 4 → always returned an empty array), which
        // silently disabled the populating branch.
        if(count($array)<8){
            return $MAIN;
        }

        $MAIN["CurLoad"]=$array[0];
        $MAIN["MaxLoad"]=$array[1];
        $MAIN["Cpunumber"]=$array[2];
        $MAIN["cpupercent"]=$array[3];
        $MAIN["requests"]=$array[4];
        $MAIN["clients"]=$array[5];
        $MAIN["maxfd"]=$array[6];
        $MAIN["curfd"]=$array[7];

        return $MAIN;
    }


    public function Status():bool{

        $this->ligne = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/db/backends/$this->BackendID"), true);
        if(!is_array($this->ligne)){
            return false;
        }
        if(isset($this->ligne["backendname"])) {
            $this->HostNameInDB = $this->ligne["backendname"];
        }
        $this->unInset();
        $this->version();
        $this->settings();
        $this->metrics();
        $this->DotColor="#9aa1ab";

        $ID=intval($this->ligne["ID"]);
        $status=intval($this->ligne["status"]);

        $status_textin=$this->StatusIntToStr($status);

        if(!isset($this->ligne["isDisconnected"])){
            $this->ligne["isDisconnected"]=0;
        }
        if(!isset($this->ligne["enabled"])){
            $this->ligne["enabled"]=0;
        }
        $this->isDisconnected=intval($this->ligne["isDisconnected"]);
        $this->BackenEnabled=intval($this->ligne["enabled"]);
        $this->BackendState=$this->Opstate();
        $this->BackendStateAdm=$this->OpFront();
        $ActiveDirectoryEmergency=intval($this->ligne["ActiveDirectoryEmergency"]);
        $this->Linkzoom();
        $this->WarningDB=$this->warningDB();
        VERBOSE("BTN#$ID status=$status, Enabled=$this->BackenEnabled BackendState=$this->BackendState BackendStateAdm=$this->BackendStateAdm isDisconnected=$this->isDisconnected",__LINE__);
        if($this->ligne["isMaster"]==1){
            $this->DotColor="#1c84c6";
        }
        if($ActiveDirectoryEmergency==1){
            $this->DotColor="#f8ac59";
        }



        if($this->BackendState==99 or $this->BackendState==999){
            if($this->BackenEnabled==0) {
                $this->StatusLine=__LINE__;
                $this->StatusText="{disabled}";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }
            if($this->isDisconnected==1){
                $this->StatusLine=__LINE__;
                $this->StatusText="{disconnected}";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }
            $this->StatusLine=__LINE__;
            $this->BtnClass="btn-warning";
            $this->Color="#f8ac59";
            $this->LabelClass="label-warning";
            $this->StatusText="{disconnected}";
            return true;
        }



        if($this->BackenEnabled==0){
            if($this->BackendState==0) {
                $this->StatusText="{in_production} OFF";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }
            if($this->BackendState==1){
                $this->StatusText="{in_production}";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }

            if($this->BackendState==2) {
                $this->StatusText="{in_production}";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }
            if($this->BackendState==3) {
                $this->StatusText="{in_production} OFF";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }
        }
        if($this->isDisconnected==1){
            if($this->BackendState==0) {
                $this->StatusText="{in_production} OFF";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }
            if($this->BackendState==1) {
                $this->StatusText="{in_production}";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }

            if($this->BackendState==2) {
                $this->StatusText="{in_production}";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }
            if($this->BackendState==3) {
                $this->StatusText="{in_production} OFF";
                $this->BtnClass="btn-default";
                $this->Color="#f4f3f3";
                $this->LabelClass="label-default";
                return true;
            }
            $this->StatusText="{disconnected}";
            $this->BtnClass="btn-default";
            $this->Color="#f4f3f3";
            $this->LabelClass="label-default";
            return true;

        }

        if($this->BackendState==0){
            VERBOSE("BTN#$ID <strong>BackendState==0</strong> status=$status, Enabled=$this->BackenEnabled BackendState=$this->BackendState BackendStateAdm=$this->BackendStateAdm isDisconnected=$this->isDisconnected",__LINE__);
            if($this->BackendStateAdm==1) {
                VERBOSE("BTN#$ID btn-warning",__LINE__);
                $this->StatusText=$status_textin;
                $this->BtnClass="btn-warning";
                $this->Color="#f8ac59";
                $this->LabelClass="label-warning";
                return true;
            }
        }

        VERBOSE("BTN#$ID btnClass PASS status=$status, Enabled=$this->BackenEnabled BackendState=$this->BackendState BackendStateAdm=$this->BackendStateAdm",__LINE__);

        if($status==1){
            $this->StatusLine=__LINE__;
            $this->StatusText=$status_textin;
            $this->BtnClass="btn-danger";
            $this->Color="#ed5565";;
            $this->LabelClass="label-danger";
            return true;
        }
        if($status==2){
            $this->StatusLine=__LINE__;
            $this->StatusText=$status_textin;
            $this->BtnClass="btn-warning";
            $this->Color="#f8ac59";
            $this->LabelClass="label-warning";
            return true;
        }

        if($status==3){
            $this->StatusText=$status_textin;
            $this->BtnClass="btn-danger";
            $this->Color="#ed5565";
            $this->LabelClass="label-danger";
            return true;
        }
        if($status==4){
            $this->StatusLine=__LINE__;
            $this->StatusText=$status_textin;
            $this->BtnClass="btn-warning";
            $this->Color="#f8ac59";
            $this->LabelClass="label-warning";
            return true;
        }
        if($status==5){
            $this->StatusLine=__LINE__;
            $this->StatusText=$status_textin;
            $this->BtnClass="btn-warning";
            $this->Color="#f8ac59";
            $this->LabelClass="label-warning";
            return true;
        }
        if($status==6){
            $this->StatusText=$status_textin;
            $this->BtnClass="btn-warning";
            $this->Color="#f8ac59";
            $this->LabelClass="label-warning";
            return true;
        }
        if($status==7){
            $this->StatusLine=__LINE__;
            $this->StatusText=$status_textin;
            $this->BtnClass="btn-warning";
            $this->Color="#f8ac59";
            $this->LabelClass="label-warning";
            return true;
        }
        if($status==8){
            $this->StatusLine=__LINE__;
            $this->StatusText=$status_textin;
            $this->BtnClass="btn-warning";
            $this->Color="#f8ac59";
            $this->LabelClass="label-warning";
            return true;
        }
        if($status==9){
            $this->StatusLine=__LINE__;
            $this->StatusText=$status_textin;
            $this->BtnClass="btn-warning";
            $this->Color="#f8ac59";
            $this->LabelClass="label-warning";
            return true;
        }
        if($status==10){
            $this->StatusText=$status_textin;
            $this->StatusLine=__LINE__;
            $this->BtnClass="btn-warning";
            $this->Color="#f8ac59";
            $this->LabelClass="label-warning";
            return true;

        }

        if($status==100){
            $ARRAY=array();
            $MAIN=$this->LoadStatus();
            if(isset($MAIN[$ID])) {
                $ARRAY = $MAIN[$ID];
            }

            if(isset($ARRAY["STOPPED"])){
                if(isset($GLOBALS["LAYER_STATUS"][$ARRAY["LAYER_STATUS"]])){
                    $status_textin=$GLOBALS["LAYER_STATUS"][$ARRAY["LAYER_STATUS"]];
                }
                $this->StatusLine=__LINE__;
                $this->StatusText=$status_textin;
                $this->BtnClass="btn-danger";
                $this->Color="#ed5565";;
                $this->LabelClass="label-danger";
                return true;
            }


            if($this->BackendState==0) {
                if($this->BackendStateAdm==1) {
                    $this->StatusLine=__LINE__;
                    $this->StatusText=$status_textin;
                    $this->BtnClass="btn-warning";
                    $this->Color="#f8ac59";
                    $this->LabelClass="label-warning";
                    return true;
                }

                if($this->BackendStateAdm==2){
                    $this->StatusLine=__LINE__;
                    $this->StatusText=$status_textin;
                    $this->BtnClass="btn-warning";
                    $this->Color="#f8ac59";
                    $this->LabelClass="label-warning";
                    return true;
                }
                if($this->BackendStateAdm==4){
                    $this->StatusLine=__LINE__;
                   $this->StatusText=$status_textin;
                   $this->BtnClass="btn-danger";
                   $this->Color="#ed5565";;
                   $this->LabelClass="label-danger";
                    return true;
                }
                if($this->BackendStateAdm==20){
                    $this->StatusLine=__LINE__;
                   $this->StatusText=$status_textin;
                   $this->BtnClass="btn-danger";
                   $this->Color="#ed5565";;
                   $this->LabelClass="label-danger";
                    return true;
                }

            }

            $this->Color="#1ab394";
            $this->StatusText=$status_textin;
            if($this->SMPDisabled()){
                $this->StatusLine=__LINE__;
                $this->BtnClass="btn-warning";
                $this->Color="#f8ac59";
                $this->LabelClass="label-warning";
                return true;
            }
            $this->StatusLine=__LINE__;
            $this->BtnClass="btn-primary";
            $this->LabelClass="label-primary";
            return true;
        }
        if($status==110){
            $this->StatusLine=__LINE__;
           $this->StatusText=$status_textin;
           $this->BtnClass="btn-danger";
           $this->Color="#ed5565";
           $this->LabelClass="label-danger";
           return true;

        }
        $this->StatusLine=__LINE__;
        $this->StatusText=$status_textin;
        $this->BtnClass="btn-warning";
        $this->Color="#f8ac59";
        $this->LabelClass="label-warning";
        return true;
    }
    private function StatusIntToStr($status):string{
        if($status==0){return "{waiting_registration}";}
        if($status==1){return "{error}";}
        if($status==2){return "{configuring}";}
        if($status==3){return "{setup_error}";}
        if($status==4){return "{setup} 50%";}
        if($status==5){return "PING OK";}
        if($status==6){return "{configuring} 70%";}
        if($status==7){return "{configuring} 80%";}
        if($status==8){return "{configuring} 90%";}
        if($status==9){return "{configuring} 95%";}
        if($status==10){return "{success} 100%";}
        if($status==100){return "{in_production}";}
        if($status==110){return "{rebooting}";}
        if($status==999){return "{ghost_server}";}
        return "{unknown}";
    }
    private function LoadStatus(){
        $MAIN=array();
        $page=CurrentPageName();
        $users=new usersMenus();
        if(isset($GLOBALS["LOAD_STATUS"])){
            return $GLOBALS["LOAD_STATUS"];
        }

        $GLOBALS["makeQueryForce"]=true;
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/globalstats");
        $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
        $xtable=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/hacluster.status.dmp"));

        if($HaClusterTransParentMode==1) {
            $manager = new cache_manager("127.0.0.1:3150");
            $data = $manager->makeQuery("server_list");
            $tt = explode("\n", $data);
            foreach ($tt as $zline) {
                if (preg_match("#Parent\s+:\s+(.+)#", $zline, $re)) {
                    $PARENT = trim($re[1]);
                    continue;
                }
                if (preg_match("#^(.+?)\s+:\s+(.+)#", $zline, $re)) {
                    $key=strtoupper(trim($re[1]));

                    if(isset($TRANSPARENT[$PARENT][$key])){
                        if(is_numeric($TRANSPARENT[$PARENT][$key])){
                            $TRANSPARENT[$PARENT][$key]=intval($TRANSPARENT[$PARENT][$key])+intval($re[2]);
                            continue;
                        }
                    }
                    $TRANSPARENT[$PARENT][$key] = trim($re[2]);
                }

            }


            foreach ($TRANSPARENT as $ID=>$array){
                VERBOSE("Transparent: $ID  == {$array["STATUS"]}");
                if($array["STATUS"]=="Up"){
                    $button=null;
                    $color="#D20C0C";
                    $img="<div class='label label-primary' style='display:block;padding:5px'>{running}</div>";
                    $button="<button class='btn btn-w-m btn-primary' type='button' OnClick=\"Loadjs('$page?stop-transparent-js=$ID')\">{stop}</button>";
                    if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";}
                }else{
                    $button=null;
                    $img="<div class='label label-danger' style='display:block;padding:5px'>{stopped}</div>";
                    $color="#D20C0C";
                    $button="<button class='btn btn-w-m btn-danger' type='button' OnClick=\"Loadjs('$page?start-transparent-js=$ID')\">{start}</button>";
                    if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}
                }

                $req_tot=$array["FETCHES"];
                $MAIN[$ID]["IMG"]=$img;
                $MAIN[$ID]["BUTTON"]=$button;
                $MAIN[$ID]["COLOR"]=$color;
                $MAIN[$ID]["REQS"]=intval($req_tot);


            }

        }
        $typof=array(0=>"frontend", 1=>"backend", 2=>"server", 3=>"socket");
        $status["UNK"]="unknown";
        $status["INI"]="initializing";
        $status["SOCKERR"]="socket error";
        $status["L4OK"]="check passed on layer 4, no upper layers testing enabled";
        $status["L4TMOUT"]="layer 1-4 timeout";
        $status["L4CON"]="layer 1-4 connection problem";
        $status["L6OK"]="check passed on layer 6";
        $status["L6TOUT"]="layer 6 (SSL) timeout";
        $status["L6RSP"]="layer 6 invalid response - protocol error";
        $status["L7OK"]="check passed on layer 7";
        $status["L7OKC"]="check conditionally passed on layer 7, for example 404 with disable-on-404";
        $status["L7TOUT"]="layer 7 (HTTP/SMTP) timeout";
        $status["L7RSP"]="layer 7 invalid response - protocol error";
        $status["L7STS"]="layer 7 response error, for example HTTP 5xx";

        $ERR["SOCKERR"]=true;
        $ERR["L4TMOUT"]=true;
        $ERR["L4CON"]=true;
        $ERR["L6TOUT"]=true;
        $ERR["L6RSP"]=true;
        $ERR["L7TOUT"]=true;
        $ERR["L7RSP"]=true;
        $ERR["L7STS"]=true;


        foreach ($xtable as $ligne) {
            $check_status_text=null;
            $ligne = trim($ligne);
            if ($ligne == null) {
                continue;
            }
            if (preg_match("#\##", $ligne)) {
                continue;
            }

            VERBOSE($ligne,__LINE__);
            $f = explode(",", $ligne);


            $pxname = $f[0];
            $svname = trim($f[1]);

            if($GLOBALS["VERBOSE"]){
                VERBOSE("Found server $svname - $pxname",__LINE__);
            }

            if(!preg_match("#^(T|)proxy([0-9]+)#",$svname,$re)){continue;}
            $ID=$re[2];


            $qcur = $f[2];
            $qmax = $f[3];
            $scur = $f[4];
            $smax = $f[5];
            $slim = $f[6];
            $stot = $f[7];
            $bin = FormatBytes($f[8] / 1024);
            $bout = FormatBytes($f[9] / 1024);
            $dreq = $f[10];
            $dresp = $f[11];
            $ereq = $f[12];
            $econ = $f[13];
            $eresp = $f[14];
            $wretr = $f[15];
            $wredis = $f[16];
            $status = $f[17];
            $weight = $f[18];
            $act = $f[19];
            $bck = $f[20];
            $chkfail = $f[21];
            $chkdown = $f[22];
            $lastchg = $f[23];
            $downtime = $f[24];
            $qlimit = $f[25];
            $pid = $f[26];
            $iid = $f[27];
            $sid = $f[28];
            $throttle = $f[29];
            $lbtot = $f[30];
            $tracked = $f[31];
            $type = $typof[$f[32]];
            $rate = $f[33];
            $rate_lim = $f[34];
            $rate_max = $f[35];
            $check_status = $f[36];
            $check_code = $f[37];
            $check_duration = $f[38];
            $hrsp_1xx = $f[39];
            $hrsp_2xx = $f[40];
            $hrsp_3xx = $f[41];
            $hrsp_4xx = $f[42];
            $hrsp_5xx = $f[43];
            $hrsp_other = $f[44];
            $hanafail = $f[45];
            $req_rate = $f[46];
            $req_rate_max = $f[47];
            $req_tot = intval($f[48]);
            $cli_abrt = $f[49];
            $srv_abrt = $f[50];
            $issue_cnx=$f[57];
            $last_agent_check=$f[57];
            $LAYER_STATUS=$f[62]; // L7OK
            $agent_status=$f[66];



            if($status=="no check" && $check_status==""){
                $check_status=$LAYER_STATUS;
                if(isset($ERR[$check_status])){
                    $status="DOWN";
                }
            }


            VERBOSE("$pxname; Status:[$status] check_status:[$check_status] LAYER_STATUS[$LAYER_STATUS]",__LINE__);
            $img=null;
            $color=null;

            if(isset($ERR[$check_status])){$img="<div class='label label-danger' style='display:block;padding:5px'>$check_status</div>";$color="#D20C0C";}


            if($status=="MAINT"){
                $color="#F8AC59";
                $img="<div class='label label-warning' style='display:block;padding:5px'>{maintenance}</div>";
                $button="<button class='btn btn-w-m btn-warning' type='button' OnClick=\"Loadjs('$page?start-js=$ID')\">{start}</button>";
                if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}

            }

            if(preg_match("#DOWN#", $status)){
                $img="<div class='label label-danger' style='display:block;padding:5px'>{stopped}</div>";
                $MAIN[$ID]["STOPPED"]=true;
                $color="#D20C0C";
                $button="<button class='btn btn-w-m btn-danger' type='button' OnClick=\"Loadjs('$page?start-js=$ID')\">{start}</button>";
                if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{start}</button>";}

            }

            if(preg_match("#UP#", $status)){
                $img="<div class='label label-primary' style='display:block;padding:5px'>{running}</div>";
                $button="<button class='btn btn-w-m btn-primary' type='button' OnClick=\"Loadjs('$page?stop-js=$ID')\">{stop}</button>";
                if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";}

            }

            if(preg_match("#no check#", $status)){
                $img="<div class='label label-warning' style='display:block;padding:5px'>{running}</div>";
                $button="<button class='btn btn-w-m btn-primary' type='button' OnClick=\"Loadjs('$page?stop-js=$ID')\">{stop}</button>";
                if(!$users->AsProxyMonitor){$button="<button class='btn btn-w-m btn-default' type='button'>{stop}</button>";}
            }


            $req_tot=$this->FormatNumber($stot);
            $MAIN[$ID]["NAME"]="$pxname/$svname";
            $MAIN[$ID]["LAYER_STATUS"]=$LAYER_STATUS;
            $MAIN[$ID]["IMG"]=$img;
            $MAIN[$ID]["BUTTON"]=$button;
            $MAIN[$ID]["COLOR"]=$color;
            $MAIN[$ID]["BIN"]=intval($bin);
            $MAIN[$ID]["BOUT"]=intval($bout);
            $MAIN[$ID]["REQS"]=intval($req_tot);





        }
        $GLOBALS["LOAD_STATUS"]=$MAIN;
        return $MAIN;
    }
    private function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

}


if(!function_exists("hacluster_db_get_backend")){
    function hacluster_db_get_backend($ID){
        $ID = intval($ID);
        if($ID <= 0){return array();}
        $r = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/db/backends/$ID"), true);
        return is_array($r) ? $r : array();
    }
}

if(!function_exists("hacluster_db_get_backend_by_hostname")){
    function hacluster_db_get_backend_by_hostname($hostname){
        $rows = hacluster_db_list_backends("ID","ASC","ID,hostname");
        foreach($rows as $row){
            if(isset($row["hostname"]) && $row["hostname"] === $hostname){
                return hacluster_db_get_backend(intval($row["ID"]));
            }
        }
        return array();
    }
}

if(!function_exists("hacluster_db_list_backends")){
    function hacluster_db_list_backends($order = "bweight", $dir = "DESC", $fields = null){
        $url = "/hacluster/db/backends?order=" . urlencode($order) . "&dir=" . urlencode($dir);
        if($fields !== null){
            $url .= "&fields=" . urlencode($fields);
        }
        $r = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($url), true);
        if(!is_array($r)){ return array(); }
        // The daemon normally returns a bare list of row arrays, but on error it
        // may return an envelope/error object (e.g. {"error":"..."} or
        // {"Status":false,...}). Unwrap known envelopes and keep only row arrays,
        // so consumers iterating $row["ID"] never receive a scalar (which fatals
        // with "Cannot access offset of type string on string").
        if(isset($r["data"]) && is_array($r["data"])){ $r = $r["data"]; }
        elseif(isset($r["backends"]) && is_array($r["backends"])){ $r = $r["backends"]; }
        $rows = array();
        foreach($r as $row){ if(is_array($row)){ $rows[] = $row; } }
        return $rows;
    }
}

if(!function_exists("hacluster_db_update_backend")){
    function hacluster_db_update_backend($ID, $fields){
        $ID = intval($ID);
        if($ID <= 0 || !is_array($fields) || count($fields) === 0){return false;}
        $r = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/hacluster/db/backends/$ID", $fields), true);
        return is_array($r) && !empty($r["success"]);
    }
}

if(!function_exists("hacluster_db_insert_backend")){
    function hacluster_db_insert_backend($fields){
        if(!is_array($fields) || count($fields) === 0){return 0;}
        $r = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/hacluster/db/backends", $fields), true);
        if(!is_array($r) || empty($r["success"])){return 0;}
        return intval($r["id"] ?? 0);
    }
}

if(!function_exists("hacluster_db_delete_backend")){
    function hacluster_db_delete_backend($ID){
        $ID = intval($ID);
        if($ID <= 0){return false;}
        $r = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/hacluster/db/backends/$ID"), true);
        return is_array($r) && !empty($r["success"]);
    }
}

if(!function_exists("hacluster_db_delete_backend_by_hostname")){
    function hacluster_db_delete_backend_by_hostname($hostname){
        $hostname = urlencode($hostname);
        $r = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/hacluster/db/backends?hostname=$hostname"), true);
        return is_array($r) && !empty($r["success"]);
    }
}

if(!function_exists("hacluster_db_clear_master")){
    function hacluster_db_clear_master(){
        $r = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/hacluster/db/backends/clear-master", array()), true);
        return is_array($r) && !empty($r["success"]);
    }
}
