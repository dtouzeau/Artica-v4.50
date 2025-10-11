<?php


class harmpnode{
    private $host="";
    private $uuid="";
    private $port=9503;
    private $ssl=0;
    public $groupid=0;
    private $ConsoleUUID="";
    public $mysql_error="";
    public $EndPoint="";
    public $ok=true;
    public $Traces=array();

    public $nodename="";
    public $nodetype="";
    public $ipaddr="";
    public $NetAgentPort="";
    public $enabled="";
    public $hostname="";
    public $status="";
    public $lastsaved="";
    public $zOrder="";
    public $cpu="";
    public $mem="";
    public $version="";
    public $kernel="";
    private $Prefix=null;
    public $HttpResult="";
    private $StoreDir="/home/artica/harmp";
    public $NginxVersion="";
    public $DistributionName="";
    public $NginxRun=0;
    public $CpuPourc="";
    public $MemRow="";

    public $SavedWebSites=array();








    function __construct($uuid=null){

        if($uuid<>null){
            $this->uuid=$uuid;
            $this->LoadNode();
        }

    }

    public function SetMyuuid($uuid):bool{
        $this->ConsoleUUID=$uuid;
        return true;
    }

    public function HostNameFromUUID():string{
        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
        $ligne=$q->QUERY_SQL("SELECT hostname FROM hamrp WHERE uuid='$this->uuid'");
        if(!isset($ligne["hostname"])){return "";}
        return $ligne["hostname"];
    }

    public function SaveSyncSettings():bool{
        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
        $Groupid=$this->groupid;
        $ligne = $q->mysqli_fetch_array("SELECT * FROM groups WHERE ID='$Groupid'");

        $array["EnableRedis"]=$ligne["EnableRedis"];
        $array["hostname"]=$this->hostname;
        $array["nodetype"]=$this->nodetype;


        if(!$q->FIELD_EXISTS("hamrp","CpuPourc")){
            $q->QUERY_SQL("ALTER TABLE hamrp ADD CpuPourc TEXT NOT NULL DEFAULT '0'");
            $q->QUERY_SQL("ALTER TABLE hamrp ADD MemRow TEXT NOT NULL DEFAULT '0.0,0,0'");
        }
        if(!$q->FIELD_EXISTS("hamrp","NginxVersion")){
            $q->QUERY_SQL("ALTER TABLE hamrp ADD NginxVersion TEXT NOT NULL DEFAULT ''");
            $q->QUERY_SQL("ALTER TABLE hamrp ADD HaProxyVersion TEXT NOT NULL DEFAULT ''");
        }



        $json=$this->PutJson("/harmp/sync",$array);
        if(!$json->Status){
            $this->mysql_error=$json->Error;
            return false;
        }
        return true;
    }
    public function UpdateAgent():bool{
        return $this->_UploadApi("/harmp/client/upload","/usr/share/artica-postfix/bin/articarest");
    }

    public function SyncNGinx($dbgz):bool{
        return $this->_UploadApi("/harmp/nginx/upload",$dbgz,"nginx");
    }
    public function SyncCertCenter($dbgz):bool{
        return $this->_UploadApi("/harmp/certs/upload",$dbgz,"database");
    }
    private function Debug($text):bool{
        if(!$GLOBALS["VERBOSE"]) {return true;}
        echo "[DEBUG] $text\n";
        return true;
    }
    public function UpgradeSoft($product,$ver):bool{
        if(!$this->_GETApi("/system/softwares/install/$product/$ver")){
            $this->Debug("Send GET API failed");
            echo @implode("\n", $this->Traces);
            return false;
        }

        $content=$this->HttpResult;
        $json=json_decode($content);
        if (json_last_error()> JSON_ERROR_NONE) {
            echo json_last_error_msg()."\n";
            return false;
        }

        if(!$json->Status){
            echo $json->Traces;
            return false;
        }

        return true;
    }

    public function SyncSettings():bool{
        $this->SavedWebSites=array();
        $json=$this->GetJson("/harmp/status");
        $Status=$json->Status;
        if(!$Status){
            return false;
        }
        $NginxVersion="";
        $NginxRun=0;
        $Version=$json->Version;
        $cpus=$json->cpus;
        $CurMem=$json->CurMem;
        $Kernel=$json->Kernel;
        $Hostname=$json->Hostname;
        $CpuPourc=$json->CpuPourc;
        $DistributionName=$json->DistributionName;
        $MemRow=$json->MemRow;
        $this->SavedWebSites=array();
        if(property_exists($json,"NginxVersion")) {
            $NginxVersion = $json->NginxVersion;
        }
        if(property_exists($json,"SavedWebSites")) {
            if (is_object($json->SavedWebSites)) {
                foreach ($json->SavedWebSites as $SiteID=>$none){
                    $this->SavedWebSites[$SiteID]=true;
                }
            }
            $this->VERBOSE("SavedWebSites: ".count($this->SavedWebSites),__LINE__);
        }else{
            $this->VERBOSE("Property SavedWebSites doesn't exists.",__LINE__);
        }



        if(property_exists($json,"NginxRun")) {
            $NginxRun = $json->NginxRun;
        }else{
            $this->VERBOSE("Property NginxRun doesn't exists.",__LINE__);
        }

        $this->VERBOSE("Property NginxRun: $json->NginxRun.",__LINE__);
        $NodeType=$json->NodeType;


        $MAIN["hostname"]=$Hostname;
        $MAIN["lastsaved"]=time();
        $MAIN["cpu"]=$cpus;
        $MAIN["mem"]=round($CurMem);
        $MAIN["version"]=$Version;
        $MAIN["kernel"]=$Kernel;
        $MAIN["CpuPourc"]=$CpuPourc;
        $MAIN["MemRow"]=$MemRow;
        $MAIN["NginxVersion"]=$NginxVersion;
        $MAIN["NginxRun"]=$NginxRun;
        $MAIN["nodetype"]=$NodeType;
        $MAIN["DistributionName"]=$DistributionName;
        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");

        foreach ($MAIN as $key=>$val){
            $UPD[]="$key='$val'";
        }
        $sql="UPDATE hamrp SET ".@implode(",",$UPD)." WHERE uuid='$this->uuid'";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $q->mysql_error."\n";
        }

        $groupid=$this->groupid;
        if($groupid==0){
            $this->VERBOSE("Alert, GroupID is not Set!",__LINE__);
        }
        $NginxDB= "/home/artica/SQLITE/nginx.$groupid.db";
        $q=new lib_sqlite($NginxDB);
        $q->QUERY_SQL("UPDATE nginx_services SET HamrpSaved=0");

        if (count($this->SavedWebSites)>0){
            foreach ($this->SavedWebSites as $SiteID=>$none){
                $this->VERBOSE("Site: $SiteID OK\n",__LINE__);
                $q->QUERY_SQL("UPDATE nginx_services SET HamrpSaved=1 WHERE ID=$SiteID");
                if(!$q->ok){
                    echo $q->mysql_error."\n";
                    return false;
                }
            }
        }

        $CurDir="$this->StoreDir/$this->uuid";
        if(!is_dir($CurDir)){
            mkdir($CurDir,0755,true);
        }
        $targetfile=$this->StoreDir."/$this->uuid/sync.zip";
        if(is_file($targetfile)){@unlink($targetfile);}

        if($this->_DownloadFileApi("/harmp/package",$targetfile)) {
            $zip = new ZipArchive;
            $res = $zip->open($targetfile);
            if (!$res) {
                $this->Debug("Failed to unzip the archive $targetfile");
                return false;
            }
            $zip->extractTo($CurDir);
            $zip->close();
            @unlink($targetfile);
        }
     return true;
    }

    public function DeleteNode():bool{
        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
        $q->QUERY_SQL("DELETE FROM hamrp WHERE uuid='$this->uuid'");
        if(!$q->ok){
            $this->mysql_error=$q->mysql_error;
            return false;
        }
        $sock=new sockets();
        $sock->REST_API("/harmp/local/remove/$this->uuid");

        return true;
    }

    public function LoadNode():bool{
        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM hamrp WHERE uuid='$this->uuid'");
        $this->nodename=$ligne["nodename"];
        $this->nodetype=$ligne["nodetype"];
        $this->groupid=$ligne["groupid"];
        $this->ipaddr=$ligne["ipaddr"];
        $this->NetAgentPort=$ligne["port"];
        $this->enabled=$ligne["enabled"];
        $this->hostname=$ligne["hostname"];
        $this->status=$ligne["status"];
        $this->lastsaved=$ligne["lastsaved"];
        $this->zOrder=$ligne["zOrder"];
        $this->cpu=$ligne["cpu"];
        $this->mem=$ligne["mem"];
        $this->version=$ligne["version"];
        $this->kernel=$ligne["kernel"];
        $this->ssl=$ligne["ssl"];
        $this->CpuPourc=$ligne["CpuPourc"];
        $this->MemRow=$ligne["MemRow"];
        $this->NginxVersion=$ligne["NginxVersion"];
        $this->NginxRun=$ligne["NginxRun"];
        $this->DistributionName=$ligne["DistributionName"];

        $proto="http";
        if($this->ssl==1){
            $proto="https";
        }
        $this->Prefix="$proto://$this->ipaddr:$this->NetAgentPort";

        return $this->VERBOSE("$this->uuid = {$ligne["hostname"]} ($this->Prefix)",__LINE__);

    }

    private function VERBOSE($text,$line):bool{
        if(!$GLOBALS["VERBOSE"]){return true;}
        if(!function_exists("VERBOSE")){return true;}
        VERBOSE($text,$line);
        return true;
    }

    public function SetNode($host,$port,$ssl=0,$groupid=0):bool{
        $this->host=$host;
        $this->port=$port;
        $this->ssl=$ssl;
        $this->groupid=$groupid;
        return true;
    }

    private function PutJson($endpoint,$Fields=array()):object{
        $content=$this->_PostApi($endpoint,$Fields);

        if(!$this->ok){
            $json["Status"]=false;
            $json["Error"]=$this->mysql_error;
            return json_decode(json_encode($json));
        }
        $json=json_decode($content);
        if (json_last_error()> JSON_ERROR_NONE) {
            $this->mysql_error=$this->mysql_error."\n".json_last_error_msg()."\n".$content."\n";
            $json["Status"]=false;
            $json["Error"]=$this->mysql_error;
            return json_decode(json_encode($json));
        }
        return $json;
    }

    private function GetJson($endpoint):object{
        $content=$this->GetApi($endpoint);

        if(!$this->ok){
            $json["Status"]=false;
            $json["Error"]=$this->mysql_error;
            return json_decode(json_encode($json));
        }
        $json=json_decode($content);
        if (json_last_error()> JSON_ERROR_NONE) {
            $this->mysql_error=$this->mysql_error."\n".json_last_error_msg()."\n".$content."\n";
            $json["Status"]=false;
            $json["Error"]=$this->mysql_error;
            return json_decode(json_encode($json));
        }
        return $json;
    }

    public function LinkNode():bool{

        $json=$this->GetJson("harmp/connect/$this->ConsoleUUID");

        if(!$json->Status){
            $this->mysql_error=$json->Error;
            return false;
        }
        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");

        $MAIN["uuid"]=$json->uuid;
        $MAIN["nodename"]=$json->Hostname;
        $MAIN["nodetype"]=0;
        $MAIN["groupid"]=$this->groupid;
        $MAIN["ipaddr"]=$this->host;
        $MAIN["port"]=$this->port;
        $MAIN["enabled"]=1;
        $MAIN["hostname"]=$json->Hostname;
        $MAIN["status"]=1;
        $MAIN["lastsaved"]=time();
        $MAIN["zOrder"]=0;
        $MAIN["cpu"]=$json->cpus;
        $MAIN["mem"]=round($json->CurMem);
        $MAIN["version"]=$json->Version;
        $MAIN["kernel"]=$json->Kernel;
        $MAIN["ssl"]=$this->ssl;

        foreach ($MAIN as $key=>$val){
            $this->traces("Receive $key = $val");
            $fields[]=$key;
            $vals[]="'$val'";
            if($key=="uuid"){continue;}
            $UPD[]="$key='$val'";
        }
        $sql="INSERT INTO hamrp (".@implode(",",$fields).") VALUES (".@implode(",",$vals).")";

        $ligne=$q->mysqli_fetch_array("SELECT uuid FROM hamrp WHERE uuid='{$MAIN["uuid"]}'");
        if(strlen($ligne["uuid"])>8){
            $this->traces("Updating table with {$MAIN["uuid"]} entry");
            $sql="UPDATE hamrp SET ".@implode(",",$UPD)." WHERE uuid='{$MAIN["uuid"]}'";
        }



        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $this->mysql_error=$q->mysql_error;
            return false;
        }


        $json=$this->GetJson("harmp/lock");

        if(!$json->Status){
            $this->traces("Error: Unbale to Lock the remote node to this Artica server");
            $this->mysql_error=$json->Error;
            return false;
        }
        $this->traces("Lock the remote node to this Artica server Success");
        return true;


    }

    private function traces($text):bool{
        $this->Traces[]=$text;
        return true;
    }

    private function _GETApi($endpoint):bool{
        $cURLConnection = $this->cURLConnection();
        $this->ok=true;
        $proto="http";
        if($this->ssl==1){
            $proto="https";
        }

        if(substr($endpoint,0,1)=="/"){
            $endpoint=substr($endpoint,1,strlen($endpoint));
        }

        $this->EndPoint="$proto://$this->host:$this->port/$endpoint";


        if($this->Prefix<>null){
            $this->EndPoint="$this->Prefix/$endpoint";
        }
        $this->Debug("Connecting to \"$this->EndPoint\"");
        curl_setopt($cURLConnection, CURLOPT_URL,  $this->EndPoint);

        $this->HttpResult=curl_exec($cURLConnection);
        if (!$this->HttpResult) {
            $this->isCurlError($cURLConnection);
            $this->TracesInfos(curl_getinfo($cURLConnection));
            curl_close($cURLConnection);
            return false;
        }

        if(!$this->isCurlError($cURLConnection)){
            $this->TracesInfos(curl_getinfo($cURLConnection));
            curl_close($cURLConnection);
            return false;
        }
        curl_close($cURLConnection);
        return true;

    }

    private function _DownloadFileApi($endpoint,$filepath):bool{
        $cURLConnection = $this->cURLConnection();
        $this->ok=true;
        $proto="http";
        if($this->ssl==1){
            $proto="https";
        }

        if(substr($endpoint,0,1)=="/"){
            $endpoint=substr($endpoint,1,strlen($endpoint));
        }

        $this->EndPoint="$proto://$this->host:$this->port/$endpoint";
        if($this->Prefix<>null){
            $this->EndPoint="$this->Prefix/$endpoint";
        }

        $fp = fopen($filepath, 'wb');
        curl_setopt($cURLConnection, CURLOPT_FILE, $fp);
        curl_setopt($cURLConnection, CURLOPT_URL,  $this->EndPoint);
        curl_setopt($cURLConnection, CURLOPT_POST, 0);


        $result=curl_exec($cURLConnection);
        if (!$result) {
            $this->isCurlError($cURLConnection);
            $this->TracesInfos(curl_getinfo($cURLConnection));
            curl_close($cURLConnection);
            return false;
        }

        if(!$this->isCurlError($cURLConnection)){
            $this->TracesInfos(curl_getinfo($cURLConnection));
            curl_close($cURLConnection);
            return false;
        }
        curl_close($cURLConnection);
        return true;
    }
    private function _UploadApi($endpoint,$filepath,$filefield="articarest"):bool{
        $cURLConnection = $this->cURLConnection();
        $file_mime = 'application/octet-stream';
        $this->ok=true;
        $proto="http";
        if($this->ssl==1){
            $proto="https";
        }

        if(substr($endpoint,0,1)=="/"){
            $endpoint=substr($endpoint,1,strlen($endpoint));
        }

        $this->EndPoint="$proto://$this->host:$this->port/$endpoint";
        if($this->Prefix<>null){
            $this->EndPoint="$this->Prefix/$endpoint";
        }

        if(!is_file($filepath)){
            $this->Traces[]="$filepath, no such file";
            return false;
        }

        $cfile = new CURLFile($filepath, $file_mime, basename($filepath));

        $post_fields = [$filefield => $cfile];


        curl_setopt($cURLConnection, CURLOPT_URL,  $this->EndPoint);
        curl_setopt($cURLConnection, CURLOPT_POST, 1);
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $post_fields);

        curl_exec($cURLConnection);
        
        if(!$this->isCurlError($cURLConnection)){
            $this->TracesInfos(curl_getinfo($cURLConnection));
            curl_close($cURLConnection);
            return false;
        }
        curl_close($cURLConnection);
        return true;
    }

    private function TracesInfos($array=array()):bool{
        reset($array);
        foreach ($array as $index=>$ligne){
            if(is_array($ligne)){
                foreach ($ligne as $a=>$b){
                    $this->Traces[]="INFO: `$index`:$a = `$b`";
                }
                continue;
            }
            $this->Traces[]="INFO: `$index` = `$ligne`";
        }
        return true;
    }

    private function _PostApi($endpoint,$fields):string{
        $cURLConnection = $this->cURLConnection();
        $this->ok=true;
        $proto="http";
        if($this->ssl==1){
            $proto="https";
        }

        if(substr($endpoint,0,1)=="/"){
            $endpoint=substr($endpoint,1,strlen($endpoint));
        }

        $this->EndPoint="$proto://$this->host:$this->port/$endpoint";
        if($this->Prefix<>null){
            $this->EndPoint="$this->Prefix/$endpoint";
        }
        curl_setopt($cURLConnection, CURLOPT_URL,  $this->EndPoint);
        curl_setopt($cURLConnection, CURLOPT_POST, 1);
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $fields);

        $resp = curl_exec($cURLConnection);

        if(!$this->isCurlError($cURLConnection)){
            curl_close($cURLConnection);
            return "";
        }

        return $resp;

    }

    private function isCurlError($cURLConnection):bool{
        $curl_error_num=curl_errno($cURLConnection);
        $CURLINFO_HTTP_CODE=intval(curl_getinfo($cURLConnection,CURLINFO_HTTP_CODE));
        $this->Debug("Curl.err=$curl_error_num Code:$CURLINFO_HTTP_CODE");

        if($this->is_curl_error($curl_error_num)){
            $this->Debug("is_curl_error($curl_error_num) --> True");
            return false;
        }

        if($CURLINFO_HTTP_CODE<>200){
            $this->httpCodeToText($CURLINFO_HTTP_CODE);
            $this->ok=false;
            return false;
        }
        return true;
    }

    private function cURLConnection(){
        $cURLConnection = curl_init();
        $Headers[] = "Cache-Control: no-cache,must revalidate";
        $Headers[] = "Expect:";

        if ($this->uuid <> null) {
            $Headers[] = "X-Auth-Token: $this->uuid";
        }

        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, $Headers);

        curl_setopt($cURLConnection, CURLOPT_NOPROXY, "*");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

        if ($this->ssl==1){
            curl_setopt($cURLConnection,CURLOPT_SSL_VERIFYHOST,0);
            curl_setopt($cURLConnection,CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($cURLConnection, CURLOPT_SSLVERSION,'all');
        }

        return $cURLConnection;
    }



    private function GetApi($endpoint):string{
            $cURLConnection = $this->cURLConnection();
            $this->ok=true;
            $proto="http";
            if($this->ssl==1){
                $proto="https";
            }
            if(substr($endpoint,0,1)=="/"){
                $endpoint=substr($endpoint,1,strlen($endpoint));
            }

            $this->EndPoint="$proto://$this->host:$this->port/$endpoint";
            if($this->Prefix<>null){
                $this->EndPoint="$this->Prefix/$endpoint";
            }

            $this->Debug("Connecting to \"$this->EndPoint\"");
            curl_setopt($cURLConnection, CURLOPT_URL,  $this->EndPoint);
            $resp = curl_exec($cURLConnection);
            if(!$this->isCurlError($cURLConnection)){
                return "";
            }
            return $resp;
        }

    private function httpCodeToText($CURLINFO_HTTP_CODE):bool{
            if($CURLINFO_HTTP_CODE==400){
                $this->mysql_error= "($CURLINFO_HTTP_CODE): FATAL";
                return true;
            }

            if($CURLINFO_HTTP_CODE==503){
                $this->mysql_error= "Error $CURLINFO_HTTP_CODE";
                return true;
            }

            if($CURLINFO_HTTP_CODE==504){
                $this->mysql_error= "Error Server timeout $CURLINFO_HTTP_CODE";
                return true;
            }

            if($CURLINFO_HTTP_CODE==201){
                $this->mysql_error= "Error $CURLINFO_HTTP_CODE";
                return true;
            }
            if($CURLINFO_HTTP_CODE==407){
            $this->mysql_error= "Error $CURLINFO_HTTP_CODE Authentication failed";
            return true;
            }
        $this->mysql_error= "Error $CURLINFO_HTTP_CODE";
        return true;
    }

    private function is_curl_error($curl_error_num=0):bool{
        if($curl_error_num==0){
            return false;
        }
        if($curl_error_num==3) {
            $this->mysql_error = "(3) Malformed URL [$this->EndPoint]";
            $this->Debug($this->mysql_error);
            return true;
        }


        if($curl_error_num==60) {
            $this->mysql_error = "(60) SSL certificate problem: unable to get local issuer certificate";
            $this->Debug($this->mysql_error);
            return true;
        }

        if($curl_error_num==7){
            $this->mysql_error="(7) Error Failed to connect() to host";
            $this->Debug($this->mysql_error);
            return true;
        }
        if($curl_error_num==52){
            $this->mysql_error="(52) Empty reply from server";
            $this->Debug($this->mysql_error);
            return true;
        }
        $this->mysql_error="Error number $curl_error_num";
        return false;
    }
}