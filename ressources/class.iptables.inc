<?php

class iptables{

	function __construct(){


	}

	public function delete_rule($ID=0){
	    if($ID==0){return;}
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $q->QUERY_SQL("DELETE FROM iptables_main WHERE ID='$ID'");
        if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}

        $q->QUERY_SQL("DELETE FROM traffic_shaping WHERE ruleid='$ID'");
        if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}

        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL("DELETE FROM firewallfilter_sqacllinks WHERE aclid='$ID'");
        if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}

    }
		

	
}
