<?php

class micronodes{
    private $NodeID=0;
    public $mysql_error="";

    function __construct($NodeID){
        $this->NodeID=$NodeID;
        $this->PatchTables();
    }

    private function PatchTables():bool{
        $q=new lib_sqlite("/home/artica/SQLITE/node.$this->NodeID.db");
        $sql="CREATE TABLE IF NOT EXISTS settings (key TEXT NOT NULL PRIMARY KEY, content TEXT NOT NULL DEFAULT '')";
        $q->QUERY_SQL($sql);
        return true;
    }

    function GET_INFO($key):string{
        $q=new lib_sqlite("/home/artica/SQLITE/node.$this->NodeID.db");
        $ligne=$q->mysqli_fetch_array("SELECT content FROM settings WHERE key='$key'");
        return base64_decode($ligne["content"]);
    }

    function SET_INFO($key,$content):bool{
        $content=base64_encode($content);
        $q=new lib_sqlite("/home/artica/SQLITE/node.$this->NodeID.db");
        $q->QUERY_SQL("DELETE FROM settings WHERE key='$key'");
        $q->QUERY_SQL("INSERT INTO settings (key,content) VALUES ('$key','$content')");
        if(!$q->ok){
            $this->mysql_error=$q->mysql_error;
            return false;
        }

        return true;
    }


}