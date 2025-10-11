<?php

class dockerd_groups{
    private $frontend_id=0;
    private $gpid=0;
    public function __construct($groupid=0){
        if($groupid>0){
            $this->gpid=$groupid;
            $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
            $ligne=$q->mysqli_fetch_array("SELECT frontend_id FROM groups WHERE ID=$groupid");
            $this->frontend_id=$ligne["frontend_id"];
        }

    }
    public function GetPermimeterID():int{
        return intval($this->frontend_id);
    }

    public function GetPerimeterParams():array{
        $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM frontends WHERE ID=$this->frontend_id");
        if(!is_array($ligne)){$ligne=array();}
        return $ligne;
    }
    public function GetPerimeterName():string{
        $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
        $ligne=$q->mysqli_fetch_array("SELECT name FROM frontends WHERE ID=$this->frontend_id");
        if(!is_array($ligne)){$ligne=array();}
        if(!isset($ligne["name"])){return "";}
        return strval($ligne["name"]);
    }

    function listNodes($groupid){

        $PermimeterID=$this->GetPermimeterID();
        $Tag="com.articatech.artica.scope.$PermimeterID.backend.$groupid";
        $dock=new dockerd();
        $lists=$dock->ContainersListByTag($Tag);

    }


    public function GroupName():string{
        $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
        $ligne=$q->mysqli_fetch_array("SELECT name FROM groups WHERE ID=$this->gpid");
        if(!isset($ligne["name"])){return "";}
        return strval($ligne["name"]);
    }
    public function GroupNameFull():string{
        $gpname=$this->GroupName();
        $ligne=$this->GetPerimeterParams();
        $fname=$ligne["name"];
        return "$gpname/$fname";

    }



    public function Get($key):string{
        $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
        $key=trim(strtolower($key));
        $ligne=$q->mysqli_fetch_array("SELECT ID,value FROM groups_params WHERE key='$key' AND groupid=$this->gpid");
        if(!isset($ligne["ID"])){return "";}
        if(is_null($ligne["ID"])){return "";}
        $ID=intval($ligne["ID"]);
        if($ID==0){return "";}
        return trim(base64_decode($ligne["value"]));
    }
    public function Set($key,$val=null):bool{
        $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
        $key=trim(strtolower($key));
        $ligne=$q->mysqli_fetch_array("SELECT ID,value FROM groups_params WHERE key='$key'");
        if(!isset($ligne["ID"])){$ligne["ID"]=0;}
        $ID=intval($ligne["ID"]);
        if($ID==0){
            if($val==null){return false;}
            $val=base64_encode($val);
            $q->QUERY_SQL("INSERT INTO groups_params (frontend_id,groupid,key,value) 
                VALUES ('$this->frontend_id','$this->gpid','$key','$val')");
            if(!$q->ok){return false;}
            return true;
        }
        $oldval=trim($ligne["val"]);
        $newval=base64_encode($val);
        if($oldval==$newval){return true;}
        $q->QUERY_SQL("UPDATE groups_params SET val='$newval' WHERE ID=$ID");
        if(!$q->ok){return false;}
        return true;

    }


}