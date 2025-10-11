<?php
include_once(dirname(__FILE__)."/class.docker.groups.inc");
include_once(dirname(__FILE__).'/class.docker.client.inc');
include_once(dirname(__FILE__).'/class.memcached.inc');
class dockerd{
    private $timerange = 1;
    public $mysql_error="";
    public $container_id="";
    public function __construct(){
    }
    public function __destruct(){

    }







    private function GetContainerInspect($ID,$direct=false):string{

        $mem=new lib_memcached();

        $key="docker:$ID:inspect";
        $data=$mem->getKey($key);
        if($mem->MemCachedFound){
            if(strlen($data)>10){
                return $data;
            }
        }

        $dock=new DockerClient();
        $json=$dock->dispatchCommand("/containers/$ID/json");
        if(!$dock->ok){return "";}
        $data=strval(json_encode($json));
        $mem->saveKey($key,$data,600);
        return $data;
    }


    public function GetContainerPerformances($ID):array{
        $DockerContainersStats=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerContainersStats"));
        if(isset($DockerContainersStats[$ID])){
            $cpu=$DockerContainersStats[$ID]["CPUPerc"]."%";
            $MemPerc=$DockerContainersStats[$ID]["MemPerc"]."%";
            $MemUsage=$DockerContainersStats[$ID]["MemUsage"];
            return array(
                "CPUPerc"=>$cpu,
                 "MemPerc"=>$MemPerc,
                "MemUsage"=>$MemUsage
            );

        }


        return array();
    }

    public function GetContainerStateClass($ID=null):string{
        $stateInt=$this->GetContainerState($ID);
        if($stateInt==2){return "warning";}
        if($stateInt==1){return "primary";}
        if($stateInt==0){return "danger";}
        return "default";
    }

    public function GetContainerDetails($ID):string{
        return $this->GetContainerInspect($ID);
    }

    public function GetContainerState($ID=null):int{
        if($ID==null){return 0;}
        $datas=$this->GetContainerInspect($ID);
        if(function_exists("VERBOSE")){
            VERBOSE("GetContainerInspect = ".strlen($datas));
        }
        $json=json_decode($datas);

        if(!property_exists($json,"State")){return 0;}

        if($json->State->Running){
            if($json->State->Paused){
                return 2;
            }
            return 1;
        }
        return 0;

    }
    public function GetContainerName($ID=null):string{
        if($ID==null){return "";}
        $datas=$this->GetContainerInspect($ID);
        $jsonSrc=json_decode($datas);

        if(!property_exists($jsonSrc,"Name")){
            if($GLOBALS["VERBOSE"]){
                echo "Unknown -&raquo; Name\n";
                return "Unknown";
            }
        }
        $name=$jsonSrc->Name;
        if(substr($name,0,1)=="/"){ return substr($name,1,strlen($name));}
        return $jsonSrc->Name;
    }
    public function GetContainerImageID($ContainerID=null):string{
        if($ContainerID==null){return "";}
        $datas=$this->GetContainerInspect($ContainerID);
        if(preg_match("#Error: No such object:#",$datas)){return "";}
        $jsonSrc=json_decode($datas);
        $json=$jsonSrc[0];
        if(!property_exists($json,"Image")){return "";}
        return $json->Image;
    }

    function unix_images_running():array{
        $unix=new unix();
        $docker=$unix->find_program("docker");

        $MAIN=array();
        exec("$docker ps -a --no-trunc --format json 2>&1",$results);
        foreach ($results as $ligne){
            if(is_null(trim($ligne))){continue;}
            $json=json_decode($ligne);
            $ID=$json->ID;
            $image=$this->GetContainerImageID($ID);
            if(strlen($image)<5){continue;}
            $MAIN[$image][]=$ID;
        }
        return $MAIN;

    }


    public function ContainersListImageName($image=null,$direct=false,$uniq=false):array{
        if($image==null){return array();}
        $tfile=PROGRESS_DIR."/docker.ContainersListImageName.$image";
        if(!$direct) {
            $tagencode=urlencode($image);
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?container-list-image=$tagencode");
        }else{
            $unix=new unix();
            $docker=$unix->find_program("docker");
            $cmd="$docker container ps -a --filter=ancestor=$image --no-trunc --format json >$tfile 2>&1";
            writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
            shell_exec($cmd);
        }

        $lines=explode("\n",@file_get_contents($tfile));
        $array=array();
        foreach ($lines as $line){
            $json=json_decode($line);
            if(!property_exists($json,"ID")){continue;}
            if($uniq){
                $array[0]=$json->ID;
                break;
            }
            $array[$json->ID]=$json->Names;


        }
        return $array;

    }

    public function html_container_status($ID,$function_main,$btname=null){
        if(!class_exists("template_admin")){
            return "";
        }
        if($ID==null){
            return "<span class='label label-default'>NO ID!</span>";

        }

        $tpl=new template_admin();
        $stateInt=$this->GetContainerState($ID);
        VERBOSE("$ID: State == $stateInt",__LINE__);
        $ContainerName=$this->GetContainerName($ID);
        $unpause="Loadjs('fw.docker.containers.php?unpause-container=$ContainerName&ID=$ID&function-main=$function_main')";
        $start="Loadjs('fw.docker.containers.php?start-container=$ContainerName&ID=$ID&function-main=$function_main')";
        if($btname<>null){$btname="$btname:&nbsp;";}
        if($stateInt==2){
            return $tpl->td_href("<span class='label label-warning'>$btname{paused}</span>","{start} $ContainerName",$unpause);
        }
        if($stateInt==1){

            $js="s_PopUpFull('/$ID','1024','900');";
            return $tpl->td_href("<span class='label label-primary'>$btname{running}</span>",null,$js);
        }
        if($stateInt==0){
            return $tpl->td_href("<span class='label label-danged'>$btname{stopped}</span>","{start} $ContainerName",$start);
        }
        return "<span class='label label-default'>$btname{unknown}</span>";

    }

    //docker ps -a --filter=ancestor=sha256:5e9558052dad70a9de57ac452c6b16b1b0ec98a97cfc50c4370266444dc1cca8
    public function ContainersListByTag($tagname=null,$uniq=false):array{
        if($tagname==null){return array();}
        $tagname_md5=md5($tagname);
        $tagname=urlencode($tagname);
        $dockClient=new DockerClient();

        $tag=urlencode("{\"label\":{\"$tagname\":true}}");
        $json=$dockClient->dispatchCommand("/containers/json?all=1&filters=$tag");
        $array=array();
        foreach ($json as $line){


            if(!isset($line["Id"])){
                if($GLOBALS["VERBOSE"]){echo "!isset property ID!<br>\n";}
                continue;
            }

            if($GLOBALS["VERBOSE"]){echo "FOUND ID {$line["Id"]}<br>\n";}
            if($uniq){
                if($GLOBALS["VERBOSE"]){echo "UNIQ {$line["Id"]}<br>\n";}
                $array[0]=$line["Id"];
                break;
            }
            if($GLOBALS["VERBOSE"]){echo "UNIQ [NOT] {$line["Id"]}<br>\n";}
            $NN=array();
            foreach ($line["Names"]as $Names){
                if(substr($Names,0,1)=="/"){
                    $Names=substr($Names,1,strlen($Names));
                }
                $NN[]=$Names;
            }

            $array[$line["Id"]]=@implode(",",$NN);


        }
        return $array;

    }

    public function GetContainerLabels($ID=null):array{
        if($ID==null){return array();}
        $datas=$this->GetContainerInspect($ID);



        $json=json_decode($datas);
        if(!$json){
            return array();
        }

        if(!property_exists($json,"Config")){
            if(function_exists("VERBOSE")){
                VERBOSE("property_exists Config FALSE",__LINE__);
            }
            return array();
        }
        $Config=$json->Config;
        if(!property_exists($Config,"Labels")){return array();}
        $ARRAY=array();
        foreach ($Config->Labels as $key=>$val){
            $ARRAY[$key]=$val;
        }
        return $ARRAY;
    }

    public function GetContainerID($name=null):string{
        if($name==null){return "";}
        $md5=md5($name);
        $nameEncoded=urlencode($name);
        $tfile=PROGRESS_DIR."/docker.$md5.details";
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?container-id=$nameEncoded&md5=$md5");
        $datas=trim(@file_get_contents($tfile));
        if($datas==null){return "";}
        $jsonSrc=json_decode($datas);
        if(!property_exists($jsonSrc,"ID")){return "";}
        return $jsonSrc->ID;
    }

    public function GetContainerNetworks($ID,$direct=false):array{
        $ARRAY=array();
        $datas=$this->GetContainerInspect($ID,$direct);
        if(preg_match("#Error: No such object:#",$datas)){return "";}
        $jsonSrc=json_decode($datas);


        foreach ($jsonSrc->NetworkSettings->Networks as $NetworkName=>$json2){
            $IPAddress=$json2->IPAddress;
            $MacAddress=$json2->MacAddress;
            $NetworkID=$json2->NetworkID;
            $Gateway=$json2->Gateway;
            $ARRAY[$NetworkID]=array("IPAddress"=>$IPAddress,"MAC"=>$MacAddress,"GATEWAY"=>$Gateway,"Name"=>$NetworkName);


        }
        return $ARRAY;

    }

    function GetImageName($ID):string{
        if($ID==null){return "";}
        $tfile=PROGRESS_DIR."/inspect.$ID";
        if(!is_file($tfile)){
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?image-inspect=$ID");
        }
        $data=@file_get_contents($tfile);
        if(preg_match("#Error: No such object:#",$data)){return "";}
        $json0=json_decode($data);
        $json=$json0[0];
        if(!property_exists($json,"RepoTags")){return "";}
        if(!isset($json->RepoTags[0])){return "";}
        $Names=array();
        foreach ($json->RepoTags as $xname){$Names[] = $xname;}
        if(count($Names)>0){
               return @implode(", ",$Names);
        }
        return strval($json->RepoTags[0]);

    }
    private function GetImageNameInspect($ID,$direct=false):string{
        $tfile=PROGRESS_DIR."/inspect.$ID";
        if(!$direct) {
            if (is_file($tfile)) {
                return @file_get_contents($tfile);

            }
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?image-inspect=$ID");
            return @file_get_contents($tfile);
        }
        exec("/usr/bin/docker image inspect $ID 2>&1",$results);
        return @implode("\n",$results);
    }

    public function GetImageNameInfos($ID,$direct=false):string{
        $data=$this->GetImageNameInspect($ID,$direct);
        $json0=json_decode($data);
        $json=$json0[0];
        return $json->ContainerConfig->Image;

    }

    public function GetImageLabels($ID,$direct=false):array{
        $data=$this->GetImageNameInspect($ID,$direct);
        $json0=json_decode($data);
        $json=$json0[0];
        $MAIN=array();
        foreach ($json->Config->Labels as $key=>$val){
            $MAIN[$key]=$val;
        }
        return $MAIN;

    }

    public function create_databases():bool{
        $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
        $sql="CREATE TABLE IF NOT EXISTS frontends (
             `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
              name                  TEXT DEFAULT NULL,
              zmd5                  TEXT DEFAULT NULL,
              frontendimageid       TEXT DEFAULT NULL,
              adminimageid          TEXT DEFAULT NULL,
              networkid             TEXT DEFAULT NULL,
              enabled              INTEGER DEFAULT 1,
              created               INTEGER DEFAULT 0
              )";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $this->mysql_error=$q->mysql_error;
            return false;
        }
        $sql="CREATE TABLE IF NOT EXISTS groups (
             `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
              name                  TEXT DEFAULT NULL,
              frontend_id           INTEGER NOT NULL,
              enabled              INTEGER DEFAULT 1,
              created               INTEGER DEFAULT 0
              )";
        $q->QUERY_SQL($sql);

        $sql="CREATE TABLE IF NOT EXISTS groups_params (
             `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
              key                  TEXT NOT NULL,
              value                TEXT NOT NULL,
              groupid              INTEGER NOT NULL,
              frontend_id          INTEGER NOT NULL,
              created               INTEGER DEFAULT 0
              )";
        $q->QUERY_SQL($sql);


        if(!$q->ok){
            $this->mysql_error=$q->mysql_error;
            return false;
        }


        return true;
    }

    public function NetworkList():array{
        $tfile=PROGRESS_DIR."/docker.network.list";
        if(!is_file($tfile)) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?network-list=yes");
        }
        $f=explode("\n",@file_get_contents($tfile));
        $ARRAY=array();
        foreach ($f as $line){
            $json=json_decode($line);
            $ID=$json->ID;
            $Driver=$json->Driver;
            $Name=$json->Name;
            $Scope=$json->Scope;
            $MAIN=$this->NetworkInfo($ID);
            $Subnet=trim($MAIN["Subnet"]);
            if($Name==null){continue;}
            $ARRAY[$ID]="$Name $Scope/$Subnet ($Driver)";

        }
        return $ARRAY;
    }


    public function GetImageLinkJS($ID=null,$Name=null):string{
        if(!preg_match("#^sha256:#",$ID)){
            $Name=$ID;
            $ID=null;
        }
        $funct=null;
        $Name=urlencode($Name);
        $ID=urlencode($ID);
        if(isset($_GET["function-main"])){
            $funct="&function-main={$_GET["function-main"]}";
        }
        return "Loadjs('fw.docker.images.php?inspect-image-js=$ID&name=$Name$funct');";
    }
    private function GetContenerLinkJS($ID=null){
        if(isset($_GET["function-main"])){
            $funct="&function-main={$_GET["function-main"]}";
        }
        //A mediter
        return "Loadjs('fw.docker.containers.php?info-container=$base64$funct')";
    }

    public function PerimeterName($ID):string{
        $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
        $ligne=$q->mysqli_fetch_array("SELECT name FROM frontends WHERE ID='$ID'");
        return strval($ligne["name"]);
    }
    public function PerimeterNetwork($ID):string{
        $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
        $ligne=$q->mysqli_fetch_array("SELECT networkid FROM frontends WHERE ID='$ID'");
        return strval($ligne["networkid"]);
    }
    public function GroupName($gpid):string{
        $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
        $ligne=$q->mysqli_fetch_array("SELECT name FROM groups WHERE ID=$gpid");
        return $ligne["name"];
    }

    public function unix_remove_image($ID):bool{
        if(!class_exists("unix")){return false;}
        $unix=new unix();
        $rm=$unix->find_program("rm");
        $docker=$unix->find_program("docker");
        $md5=md5($ID);
        $tfile=PROGRESS_DIR."/image.delete.$md5";
        $cmd="$docker image rm --force $ID >$tfile 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);

        $data=explode("\n",@file_get_contents($tfile));
        @unlink($tfile);
        $SUCCESS=false;
        foreach ($data as $line){
            $line=trim($line);
            if(preg_match("#(deleted|Untagged)#i",$line)){$SUCCESS=true;break;}
            if($line<>null){echo $line."\n";}
        }
        shell_exec("rm -f ".PROGRESS_DIR."/inspect.*");

        if($SUCCESS) {
            $php=$unix->LOCATE_PHP5_BIN();
            shell_exec("$php /usr/share/artica-postfix/exec.docker.php --list-images");
        }
        return $SUCCESS;
    }

    private function unix_container_running($ContainerID):bool{
        $unix=new unix();
        $docker=$unix->find_program("docker");
        exec("$docker ps -q --no-trunc 2>&1",$results);
        foreach ($results as $line){
            if(is_null($line)){continue;}
            $line=trim($line);
            if($line==$ContainerID){return true;}
        }
        return false;
    }
    public function unix_stop_container($ContainerID):bool{
        if(!$this->unix_container_running($ContainerID)){
            return true;
        }
        $unix=new unix();
        $docker=$unix->find_program("docker");
        $cmd="$docker stop $ContainerID 2>&1";
        exec($cmd,$array);

        foreach ($array as $line){
            if(is_null($line)){continue;}
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#(error|invalid)#i",$line)){
                $this->mysql_error=$line;
                return false;
            }
        }

        if(!$this->unix_container_running($ContainerID)){
            return true;
        }
        return false;
    }
    public function unix_copy_container($From,$To,$ContainerID):bool{
        if(!is_file($From)){return false;}
        $unix=new unix();
        $docker=$unix->find_program("docker");
        $cmd="$docker container cp $From $ContainerID:$To 2>&1";
        exec($cmd,$array);
        foreach ($array as $line){
            if(is_null($line)){continue;}
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#(error|invalid)#i",$line)){
                $this->mysql_error=$line;
                return false;
            }
        }
        return true;
    }
    private function _out($text){
        if(!function_exists("openlog")){return false;}
        openlog("dockerd", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
        return true;
    }
    public function unix_start_container($ContainerID):bool{
        $unix=new unix();
        $docker=$unix->find_program("docker");
        if($this->unix_clean_container_cache($ContainerID)){
            $this->_out("Cache Cleaned for $ContainerID");
        }
        $cmd="$docker container cp /usr/share/artica-postfix/bin/docker-client $ContainerID:/usr/sbin/docker-client";
        shell_exec($cmd);
        $this->_out("[STARTING] Container \"$ContainerID\"");
        $cmd="$docker start $ContainerID 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        exec($cmd,$results);
        foreach ($results as $line){
            if(is_null($line)){continue;}
            if(preg_match("#See.*?--help#",$line)){
                continue;
            }
            if(preg_match("#(error|invalid)#i",$line)){
                $this->_out("[ERROR] Container \"$ContainerID\" $line");
                $this->mysql_error=$line;
                return false;
            }

        }
        return true;

    }

    private function unix_clean_container_cache($ID):bool{
        $path="/usr/share/artica-postfix/ressources/logs/web/docker.$ID.details";
        if(is_file($path)){@unlink($path);return true;}
        $unix=new unix();
        $rm=$unix->find_program("rm");
        shell_exec("$rm -f ".PROGRESS_DIR."/docker.*");
        shell_exec("$rm -f ".PROGRESS_DIR."/image.*");
        shell_exec("$rm -f ".PROGRESS_DIR."/inspect.*");
        return true;
    }
    private function unix_run_container_env($array):string{
        if(!isset($array["ENV"])){return "";}
        if(count($array["ENV"])==0){return "";}
        $xcmd=array();
        foreach ($array["ENV"] as $key=>$val){
            $xcmd[]="--env \"$key\"=\"$val\"";
        }
        if(count($xcmd)==0){return "";}
        return @implode(" ",$xcmd);
    }
    private function unix_run_container_expose($array):string{
        if(!isset($array["EXPOSE"])){return "";}
        if(count($array["EXPOSE"])==0){return "";}
        $xcmd=array();
        foreach ($array["EXPOSE"] as $exposed){
            $xcmd[]="--expose $exposed";
        }
        if(count($xcmd)==0){return "";}
        return @implode(" ",$xcmd);
    }
    private function unix_run_container_publish($array):string{
        if(!isset($array["PUBLISH"])){return "";}
        if(count($array["PUBLISH"])==0){return "";}
        $xcmd=array();
        foreach ($array["PUBLISH"] as $localport=>$sconf){
            if(!isset($sconf["src"])){
                $sconf["src"]="0.0.0.0:$localport";
            }
            if(!isset($sconf["proto"])){
                $sconf["proto"]="tcp";
            }
            $src=$sconf["src"];
            $proto=$sconf["proto"];
            $smd="--publish target=$localport,published=$src";
            if(strpos($proto,"/")>0){
                $xvc=explode("/",$proto);
                foreach ($xvc as $addproto){
                    if(is_null($addproto)){continue;}
                    $xcmd[]="$smd,protocol=$addproto";
                }
            }else{
                $xcmd[]="$smd,protocol=$proto";
            }

        }
        if(count($xcmd)==0){return "";}
        return @implode(" ",$xcmd);
    }

    public function unix_run_container($array):bool{
        if(!class_exists("unix")){return false;}
        $unix=new unix();
        if(!is_array($array)){
            $this->mysql_error="No Configuration specified for running a container";
            return false;
        }
        $templabels=$unix->FILE_TEMP();
        $docker=$unix->find_program("docker");
        $image=$array["image"];
        if($image==null){
            $this->mysql_error="No image ID specified";
            return false;
        }
        $cmd[]="$docker run --detach";
        if(!isset($array["entrypoint"])){$array["entrypoint"]="tail -f /dev/null";}

        if(isset($array["hostname"])){
            if(strlen($array["hostname"])>1) {
                $cmd[] = "--hostname \"{$array["hostname"]}\"";
            }
        }

        if(isset($array["RESTART"])){
            if($array["RESTART"]){
                $cmd[] = "--restart unless-stopped";
            }
        }

        if(isset($array["name"])){
            if(strlen($array["name"])>1) {
                $cmd[] = "--name \"{$array["name"]}\"";
            }
        }

        $PUBLISH=$this->unix_run_container_publish($array);
        if(strlen($PUBLISH)>3){
            $cmd[] = $PUBLISH;
        }
        $EXPOSE=$this->unix_run_container_expose($array);
        if(strlen($EXPOSE)>3){
            $cmd[] = $EXPOSE;
        }
        $ENV=$this->unix_run_container_env($array);
        if(strlen($ENV)>3){
            $cmd[] = $ENV;
        }


      if(isset($array["network"])){
          if(strlen($array["network"])>3) {
              $cmd[] = "--network={$array["network"]}";
          }
      }
      if(isset($array["MOUNTS_BIND"])){
          foreach ($array["MOUNTS_BIND"] as $src=>$destination){
              if(strpos($destination,"|")>0){
                  $tbz=explode("|",$destination);
                  $destination=$tbz[0];
                  if($tbz[1]=="ro"){
                      $destination=$destination.",readonly";
                  }
              }

              $cmd[] = "--mount type=bind,source=$src,target=$destination";
          }
      }

        if(isset($array["LABELS"])){
            $lbls=array();
            foreach ($array["LABELS"] as $key=>$val){
                $lbls[]="$key=\"$val\"";

            }
            if(count($lbls)>0) {
                @file_put_contents($templabels, @implode("\n", $lbls));
                $cmd[] = "--label-file \"$templabels\"";
            }

        }

        if(strlen($array["entrypoint"])>1) {
                $cmd[] = "--entrypoint \"{$array["entrypoint"]}\"";
        }
        $cmd[]="\"$image\"";
        $cmd[] ="2>&1";
        $cmdline=@implode(" ",$cmd);
        exec($cmdline,$results);
        if(isset($array["md5_out"])) {
            $tfile = PROGRESS_DIR . "/docker.{$array["md5_out"]}.run";
            @file_put_contents($tfile,@implode("\n",$results));
        }

        foreach ($results as $line){
            if(function_exists("_out")){_out("Container:{$array["name"]} $line");}
            if(preg_match("#error#i",$line)){
                if(is_file($templabels)){@unlink($templabels);}
                $this->mysql_error=$line;
                return false;
            }
            if(preg_match("#invalid reference format#",$line)){
                if(is_file($templabels)){@unlink($templabels);}
                $this->mysql_error=$line;
                return false;
            }
        }
        $this->container_id=trim(@implode("",$results));
        return true;
    }

    public function NetworkInfo($ID):array{

        $DockerClient=new DockerClient();
        VERBOSE("GET NETWORK INFO FOR $ID");
        $json=$DockerClient->dispatchCommand("/networks/$ID");
        $MAIN["Name"]=$json["Name"];
        $MAIN["Scope"]=$json["Scope"];
        $MAIN["Driver"]=$json["Driver"];
        $MAIN["Subnet"]="";
        $MAIN["Gateway"]="";
        $IPAM=array();
       if(isset($json["IPAM"])){
            $IPAM=$json["IPAM"];
        }
        if(isset($IPAM["Config"])){
            if(isset($IPAM["Config"][0])) {
                $MAIN["Subnet"] = $IPAM["Config"][0]["Subnet"];

                if (isset($IPAM["Config"][0]["Gateway"])) {
                    $MAIN["Gateway"] = $IPAM["Config"][0]["Gateway"];
                }
            }
        }

        if(isset($json["Containers"])){
            foreach ($json["Containers"] as $contener_id=>$json2){
                $MAIN["Containers"][$contener_id]["Name"]=$json2["Name"];
                $MAIN["Containers"][$contener_id]["MacAddress"]=$json2["MacAddress"];
                $MAIN["Containers"][$contener_id]["IPv4Address"]=$json2["IPv4Address"];
                $MAIN["Containers"][$contener_id]["EndpointID"]=$json2["EndpointID"];

            }

        }
        return $MAIN;
    }

    function DockTimeToInt($zdate):int{
        if(!preg_match("#^([0-9\-]+)\s+([0-9:]+)#",$zdate,$re)){return 0;}
        return strtotime("$re[1] $re[2]");
    }


    function GetImageID($Name):string{
        if($Name==null){return "";}
        $md5=md5($Name);
        $Name=urlencode($Name);
        $tfile=PROGRESS_DIR."/inspect.image.$md5";
        if(!is_file($tfile)){
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?image-inspect-name=$Name&md5=$md5");
        }

        $data=@file_get_contents($tfile);
        $json=json_decode($data);
        $json0=$json[0];
        if(!property_exists($json0,"Id")){return "";}
        return $json0->Id;
    }

    function isTaskExport($container_id){
        $tfile=PROGRESS_DIR."/docker.$container_id.export.run";
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?export-container-progress=$container_id");
        return intval(@file_get_contents($tfile));
    }




}
