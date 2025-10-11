<?php
include_once(dirname(__FILE__)."/class.ccurl.inc");

class ipinfo{
    private $httpr="aHR0cDovL2lwLWluZm8uZmYuYXZhc3QuY29tL3YyL2luZm8/aXA=";
    private $memcache;
    private $ipaddr=null;
    private $checktableok=false;
    public $results=null;
    private $q;
    public function __construct(){
        $this->memcache=new lib_memcached();
        $this->q=new lib_sqlite("/home/artica/SQLITE/ipinfo.db");
        $this->checktable();

    }

    private function checktable(){
        if($this->checktableok){return true;}
        if($this->q->TABLE_EXISTS("ipinfo")) {
            $this->checktableok=true;
            VERBOSE("/home/artica/SQLITE/ipinfo.db ipinfo OK",__LINE__);
            return true;
        }

        VERBOSE("/home/artica/SQLITE/ipinfo.db ipinfo no such table",__LINE__);

        $sql = "CREATE TABLE IF NOT EXISTS ipinfo (
          `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		  `ipaddr` TEXT NOT NULL UNIQUE,
		  `content` TEXT
		  )";
        $this->q->QUERY_SQL($sql);
        if(!$this->q->ok){VERBOSE($this->q->mysql_error,__LINE__);}
        return false;
    }

    private function getcache():bool{
        //if($GLOBALS["VERBOSE"]){return false;}
        $memkey="ipinfo:$this->ipaddr";
        $this->results=$this->memcache->getKey($memkey);
        if($this->memcache->MemCachedFound){
            VERBOSE("ipinfo:$this->ipaddr HIT MEM [$this->results]",__LINE__);
            return true;
        }
        $ligne=$this->q->mysqli_fetch_array("SELECT ID,content FROM ipinfo WHERE ipaddr='$this->ipaddr'");
        $ID=intval($ligne["ID"]);
        if($ID==0){
            VERBOSE("ipinfo:$this->ipaddr MISS",__LINE__);
            return false;
        }
        $this->results=base64_decode($ligne["content"]);
        $this->savecachemem();
        return true;
    }

    private function savecachemem(){
        $memkey="ipinfo:$this->ipaddr";
        $this->memcache->saveKey($memkey,$this->results);
    }

    private function savecache(){
        $this->savecachemem();
        $data=base64_encode($this->results);
        $this->checktable();
        $this->q->QUERY_SQL("INSERT INTO ipinfo(ipaddr,content) VALUES ('$this->ipaddr','$data')");

    }

    public function get($ipaddr=null):bool{
        if($ipaddr==null){return false;}
        $this->ipaddr=$ipaddr;
        if(!$this->getcache()){
            if(!$this->get_http()){return false;}
            $this->savecache();
            return true;
        }
        return true;
    }

    private function get_http(){
        $uri=base64_decode($this->httpr)."=$this->ipaddr";
        $curl=new ccurl($uri);
        $curl->NoHTTP_POST=true;

        if(!$curl->get()){
            VERBOSE("$curl->error",__LINE__);
            return false;}
        VERBOSE("$curl->data",__LINE__);
        $this->results=$curl->data;
        return true;
    }



}