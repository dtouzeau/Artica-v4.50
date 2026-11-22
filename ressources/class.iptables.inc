<?php
include_once(dirname(__FILE__)."/class.acls.backend.inc");
include_once(dirname(__FILE__)."/class.firewall.api.inc");

class iptables{

	function __construct(){


	}

	public function delete_rule($ID=0){
	    $ID=intval($ID);
	    if($ID==0){return;}
	    // Le daemon supprime la règle, ses lignes de shaping et de nDPI, puis
	    // reconstruit le pare-feu (fwconfig).
	    $e=fwapi_error(fwapi_send("DELETE","rules/$ID"));
	    if($e!=""){echo $e;return;}
        // acls.db est à l'API ACL : liens d'objets de la règle.
        list($ok,$err)=acls_api_send("DELETE","/proxy/acls/sqlite/links/by-aclid?table=firewallfilter_sqacllinks&aclid=".$ID);
        if(!$ok){echo "Error line:".__LINE__."\n".$err;return;}
    }
		

	
}
