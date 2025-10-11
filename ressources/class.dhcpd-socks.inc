<?php


class dhcp_socks{
    private $Interface=null;


    function __construct($Interface=null){

        if($Interface==null){
            $Interface="default";
        }

        $this->Interface=$Interface;

    }



    function GET($key):string{
        $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $ligne=$q->mysqli_fetch_array("SELECT `val` FROM `dhcpd` WHERE `key`='$key' AND `interface`='$this->Interface'");
        if(!$ligne){
            return "";
        }
        if(!isset($ligne['val'])){return "";}
        return strval($ligne["val"]);
    }

    function SET($key,$val){
        writelogs("Save $key/$val for Interface $this->Interface",__FUNCTION__,__FILE__,__LINE__);
        $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM `dhcpd` WHERE `key`='$key' AND `interface`='$this->Interface'");
        $ID=intval($ligne["ID"]);
        if($ID>0){
            $q->QUERY_SQL("UPDATE `dhcpd` SET `val`='$val' WHERE ID=$ID");
            if(!$q->ok){$tpl=new template_admin();echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);}
            return;
        }
        $q->QUERY_SQL("INSERT INTO `dhcpd` (`key`,`val`,`interface`) VALUES ('$key','$val','$this->Interface')");
        if(!$q->ok){$tpl=new template_admin();echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);}

    }

    function CleanIpaddr1($FormattedVirgule){
        $f=array();
        $MAIN=array();
        $explode=explode(",",$FormattedVirgule);
        foreach ($explode as $ipaddr){
            $ipaddr=trim($ipaddr);
            if(!preg_match("#^([0-9\.]+)$#",$ipaddr)){continue;}
            if(preg_match("#127\.0\.0#",$ipaddr)){continue;}
            if($ipaddr==null){continue;}
            $MAIN[$ipaddr]=true;

        }

        foreach ($MAIN as $ipaddr=>$none){
            $f[]=$ipaddr;

        }

        return @implode(",",$f);

    }

}