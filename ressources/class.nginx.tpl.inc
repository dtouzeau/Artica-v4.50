<?php
class NginxTemplates{
    public $mysql_error="";
    public $TemplateName="";
    private $tplid=0;
    public $serviceid=0;
    private $tpldata=array();


    function __construct($tplid=0){
        $this->tplid=$tplid;
        $this->LoadData();
    }

    private function LoadData():void{
        if($this->tplid==0){
            return;
        }
        $q=new lib_sqlite($this->NginxGetDB());
        $ligne=$q->mysqli_fetch_array("SELECT tpname,tpdata FROM nginx_templates WHERE ID=$this->tplid");
        $this->tpldata=unserialize(base64_decode($ligne["tpdata"]));
        $this->TemplateName=$ligne["tpname"];
    }

    private function GetTypeFromTemplate():int{
        if(!isset($this->tpldata["nginx_services"])){
            return 0;
        }
        $ligne=unserialize($this->tpldata["nginx_services"]);
        return intval($ligne["type"]);
    }

    public function RestoreSite($serviceid){
        $this->serviceid=$serviceid;
        return $this->ReplicateParams();
    }

    public function CreateSite($servicename):bool{
        $TEMPKEY=md5(time().rand(0,time()));
        $q=new lib_sqlite($this->NginxGetDB());
        $ztype=$this->GetTypeFromTemplate();

        if($ztype==0){
            $this->mysql_error="Unable to get Type from Template #$this->tplid";
            return false;
        }

        $q->QUERY_SQL("INSERT INTO nginx_services (`type`,`servicename`,`enabled`) VALUES ('$ztype','$TEMPKEY',1)");
        if(!$q->ok){
            $this->mysql_error=$q->mysql_error;
            return false;
        }
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE servicename='$TEMPKEY'");
        $this->serviceid=intval($ligne["ID"]);

        if($this->serviceid==0){
            $this->mysql_error="Unable to find serviceid for $servicename/$TEMPKEY";
            return false;
        }

        $q->QUERY_SQL("UPDATE nginx_services SET `servicename`='$servicename',`goodconftime`=0 WHERE ID=$this->serviceid");
        if(!$q->ok){
            $this->mysql_error=$q->mysql_error;
            return false;
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
        return true;
    }

    private function build_progress($prc,$text):bool{
        $unix=new unix();
        return $unix->framework_progress($prc,$text,"nginx.replic.$this->serviceid.progress");
    }

    private function ReplicateParams():bool{
        $q=new lib_sqlite($this->NginxGetDB());
        $ligne=unserialize($this->tpldata["nginx_services"]);

        unset($ligne["ID"]);
        unset($ligne["goodconftime"]);
        unset($ligne["servicename"]);
        $edits=array();
        foreach ($ligne as $key=>$value){
            $edits[]=sprintf("%s='%s'",$key,$value);
        }
        $this->build_progress(20,"Main service parameters");
        $sql=sprintf("UPDATE nginx_services SET %s WHERE ID='%s'",implode(",",$edits),$this->serviceid);
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $this->mysql_error=$q->mysql_error."<hr>$sql";
            return false;
        }
        $this->build_progress(30,"Main service Ports");
        $results=unserialize($this->tpldata["stream_ports"]);
        $q->QUERY_SQL("DELETE FROM stream_ports WHERE serviceid=$this->serviceid");
        if(count($results)>0) {
            foreach ($results as $index => $ligne) {
                $ligne["serviceid"] = $this->serviceid;
                unset($ligne["ID"]);
                $Keys = array();
                $vals = array();
                $ligne["zmd5"] = md5($ligne["interface"] . $ligne["port"] . $this->serviceid);
                foreach ($ligne as $key => $value) {
                    $Keys[] = $key;
                    $vals[] = sprintf("'%s'", $value);
                }
                $sql = sprintf("INSERT INTO stream_ports (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    $this->mysql_error = $q->mysql_error . "<hr>$sql";
                    return false;
                }
            }
        }
        $this->build_progress(40,"Backends...");
        if(isset($this->tpldata["backends"])) {
            $results = unserialize($this->tpldata["backends"]);
            if(count($this->tpldata["backends"])>0) {
                $q->QUERY_SQL("DELETE FROM backends WHERE serviceid=$this->serviceid");
                if (count($results) > 0) {
                    foreach ($results as $index => $ligne) {
                        $ligne["serviceid"] = $this->serviceid;
                        unset($ligne["ID"]);
                        $Keys = array();
                        $vals = array();
                        foreach ($ligne as $key => $value) {
                            $Keys[] = $key;
                            $vals[] = sprintf("'%s'", $value);
                        }
                        $sql = sprintf("INSERT INTO backends (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
                        $q->QUERY_SQL($sql);
                        if (!$q->ok) {
                            $this->mysql_error = $q->mysql_error . "<hr>$sql";
                            return false;
                        }
                    }
                }
            }
        }
        $this->build_progress(50,"{parameters}");
        $c=50;
        echo "service_parameters...\n";
        $results=unserialize($this->tpldata["service_parameters"]);
        $q->QUERY_SQL("DELETE FROM service_parameters WHERE serviceid=$this->serviceid");
        if(count($results)>0) {
            foreach ($results as $index => $ligne) {
                $ligne["serviceid"] = $this->serviceid;
                unset($ligne["ID"]);
                $Keys = array();
                $vals = array();
                foreach ($ligne as $key => $value) {
                    $Keys[] = $key;
                    $vals[] = sprintf("'%s'", $value);
                }
                $sql = sprintf("INSERT INTO service_parameters (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
                $c++;
                if($c>60){$c=60;}
                $this->build_progress($c,"{parameters}");
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    $this->mysql_error = $q->mysql_error . "<hr>$sql";
                    return false;
                }
            }
        }
        $this->build_progress(60,"ngx_stream_access_module");
        echo "ngx_stream_access_module...\n";
        $results=unserialize($this->tpldata["ngx_stream_access_module"]);
        $q->QUERY_SQL("DELETE FROM ngx_stream_access_module WHERE serviceid=$this->serviceid");
        if(count($results)>0) {
            foreach ($results as $index => $ligne) {
                $ligne["serviceid"] = $this->serviceid;
                unset($ligne["ID"]);
                $Keys = array();
                $vals = array();
                foreach ($ligne as $key => $value) {
                    $Keys[] = $key;
                    $vals[] = sprintf("'%s'", $value);
                }
                $sql = sprintf("INSERT OR IGNORE ngx_stream_access_module (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
                writelogs("$sql", __FUNCTION__, __FILE__, __LINE__);
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    $this->mysql_error = $q->mysql_error . "<hr>$sql";
                    return false;
                }
            }
        }
        $this->build_progress(70,"directories_backends");
        echo "Directories Backends...\n";
        $results=unserialize($this->tpldata["directories_backends"]);
        $q->QUERY_SQL("DELETE FROM directories_backends WHERE serviceid=$this->serviceid");
        if(count($results)>0) {
            foreach ($results as $index => $ligne) {
                $ligne["serviceid"] = $this->serviceid;
                unset($ligne["ID"]);
                $Keys = array();
                $vals = array();
                foreach ($ligne as $key => $value) {
                    $Keys[] = $key;
                    $vals[] = sprintf("'%s'", $value);
                }
                $sql = sprintf("INSERT OR IGNORE directories_backends (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
               echo $sql."\n";
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    echo $q->mysql_error;
                    $this->mysql_error = $q->mysql_error . "<hr>$sql";
                    return false;
                }
            }
        }
        $this->build_progress(80,"modsecurity_whitelist");
        echo "modsecurity_whitelist...\n";
        $results=unserialize($this->tpldata["modsecurity_whitelist"]);
        $q->QUERY_SQL("DELETE FROM modsecurity_whitelist WHERE serviceid=$this->serviceid");
        if(count($results)>0) {
            foreach ($results as $index => $ligne) {
                $ligne["serviceid"] = $this->serviceid;
                unset($ligne["ID"]);
                $Keys = array();
                $vals = array();
                foreach ($ligne as $key => $value) {
                    $Keys[] = $key;
                    $vals[] = sprintf("'%s'", $value);
                }
                $sql = sprintf("INSERT OR IGNORE INTO modsecurity_whitelist (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
                writelogs("$sql", __FUNCTION__, __FILE__, __LINE__);
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    $this->mysql_error = $q->mysql_error . "<hr>$sql";
                    return false;
                }
            }
        }
        $this->build_progress(90,"ngx_directories");
        echo "ngx_directories...\n";
        $MAIN=$this->tpldata["ngx_directories"];
        $q->QUERY_SQL("DELETE FROM ngx_directories WHERE serviceid=$this->serviceid");
        $q->QUERY_SQL("DELETE FROM directories_backends WHERE serviceid=$this->serviceid");
        $q->QUERY_SQL("DELETE FROM ngx_subdir_items WHERE serviceid=$this->serviceid");

        if(count($MAIN)>0) {
            if(!$this->ReplicateNginxDirectories($MAIN)){
                return false;
            }
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
        $this->build_progress(100,"{success}");
        return true;
    }

    private function ReplicateNginxDirectoriesInser($ligne):int{
        $q=new lib_sqlite($this->NginxGetDB());
        $ligne["serviceid"] = $this->serviceid;
        unset($ligne["ID"]);
        $Keys = array();
        $vals = array();
        foreach ($ligne as $key => $value) {
            $Keys[] = $key;
            $vals[] = sprintf("'%s'", $value);
        }
        $sql = sprintf("INSERT INTO ngx_directories (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
       echo $sql."\n";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            $this->mysql_error = $q->mysql_error . "<hr>$sql";
            return false;
        }

        return intval($q->last_id);

    }

    private function ReplicateNginxDirectories($MAIN):bool{
        foreach ($MAIN as $KeyData => $NextArrays) {
            $ligne=unserialize($KeyData);
            $Directoryid=$this->ReplicateNginxDirectoriesInser($ligne);
            if($Directoryid==0){
                $this->mysql_error=$this->mysql_error." Unable to get Directory ID";
                return false;
            }



            if(count($NextArrays["ngx_subdir_items"])>0){
                if(!$this->ReplicateNginxSubDirItems($Directoryid,$NextArrays["ngx_subdir_items"])){
                    $this->mysql_error=$this->mysql_error." Unable to get ngx_subdir_items";
                    return false;
                }
            }
            if(count($NextArrays["directories_backends"])>0){
                echo "Replicate Directories backends for Directory $Directoryid ad site $this->serviceid\n";
                if(!$this->ReplicateNginxDirectoriesBackends($Directoryid,$NextArrays["directories_backends"])){
                    $this->mysql_error=$this->mysql_error." Unable to get directories_backends";
                    return false;
                }

            }

        }
        return true;

    }
    private function ReplicateNginxDirectoriesBackends($DirectoryID,$array):bool{
        $q=new lib_sqlite($this->NginxGetDB());

        foreach ($array as $index=>$SubArray){
            $ligne=unserialize($SubArray);
            $ligne["directory_id"]=$DirectoryID;
            $ligne["serviceid"]=$this->serviceid;
            unset($ligne["ID"]);
            $Keys = array();
            $vals = array();
            foreach ($ligne as $key => $value) {
                $Keys[] = $key;
                $vals[] = sprintf("'%s'", $value);
            }
            echo "\t[directories_backends]: Insert $DirectoryID - $this->serviceid\n";
            $sql = sprintf("INSERT INTO directories_backends (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));
            echo "\t$sql\n";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error."\n";
                $this->mysql_error = $q->mysql_error . "<hr>$sql";
                return false;
            }
        }
        echo "ReplicateNginxDirectoriesBackends [OK]\n";
        return true;
    }
    private function ReplicateNginxSubDirItems($DirectoryID,$array):bool{
        echo "[ngx_subdir_items]: Replicate Directory $DirectoryID\n";


        $q=new lib_sqlite($this->NginxGetDB());
        foreach ($array as $index=>$SubArray){
            $ligne=unserialize($SubArray);
            $ligne["directoryid"]=$DirectoryID;
            $ligne["serviceid"]=$this->serviceid;
            unset($ligne["ID"]);
            $Keys = array();
            $vals = array();
            foreach ($ligne as $key => $value) {
                $Keys[] = $key;
                $vals[] = sprintf("'%s'", $value);
            }
            $sql = sprintf("INSERT INTO ngx_subdir_items (%s) VALUES (%s)", implode(",", $Keys), implode(",", $vals));

            echo "[ngx_subdir_items]:$DirectoryID/$this->serviceid $sql\n";

            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo "\t$q->mysql_error !!!\n";
                $this->mysql_error = $q->mysql_error . "<hr>$sql";
                return false;
            }
        }
        return true;
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