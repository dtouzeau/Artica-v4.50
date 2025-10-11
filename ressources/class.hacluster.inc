<?php


class hacluster_status{
    public $MainInfo=array();
    public $ok=true;
    public $mysql_error="";
    private $filepath=PROGRESS_DIR."/hacluster.json.info";
    function __construct(){

    }

    function  getinfo($AsRoot=false):bool{
        $cmd="/bin/echo \"show info json\"|/bin/socat stdio unix-connect:/run/hacluster/admin.sock >$this->filepath 2>&1";

        if(!$AsRoot) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hacluster.php?getinfo=yes");
        }else{
            shell_exec($cmd);
        }
        $results=@file_get_contents($this->filepath);
        $json=json_decode($results);
        try {
            foreach ($json as $index=>$val){
                $key=$val->field->name;
                $value=$val->value->value;
                $this->MainInfo[$key]=$value;
            }
        } catch (Exception $e) {
            $this->mysql_error=$e->getMessage();
            $this->ok=false;
            return false;
        }

        return true;
    }

}




