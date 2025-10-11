<?php


class squid_meta_acls{
	private $aclid=0;
	private $httpaccess=null;
	function __construct($aclid){
		$q=new mysql();
		$this->aclid=$aclid;
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM meta_webfilters_acls WHERE ID='$this->aclid'"));
		
	}
	
	
}