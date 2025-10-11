<?php

class nginx_wordpress{
	private $servername;
	private $IsFreeWeb=false;
	private $WORKING_DIRECTORY=null;
	public $ReverseProxyPattern=null;
	
	public function __construct($servername=null){
		$this->servername=$servername;
		if($this->servername<>null){
			$q=new mysql();
			$q->TestingConnection();
			$ligne=@mysqli_fetch_array($q->QUERY_SQL("SELECT servername,www_dir from freeweb WHERE servername='$this->servername'","artica_backup"));
			if(!$q->ok){return; }
			if($ligne["servername"]<>null){$this->IsFreeWeb=true;}
			$this->WORKING_DIRECTORY=$ligne["www_dir"];
			if($this->WORKING_DIRECTORY==null){$this->WORKING_DIRECTORY="/var/www/$this->servername";}
		}
		
		
	}
	
	
	public function Getconfig(){
		
		$f[]="################## WORDPRESS ##################";

		
		if(is_dir("$this->WORKING_DIRECTORY/wp-content")){
			$f[]="# All media (including uploaded) is under wp-content/ so";
			$f[]="# instead of caching the response from apache, we're just";
			$f[]="# going to use nginx to serve directly from there.";
			$f[]="\tlocation ~* ^/(wp-content|wp-includes|resources)/(.*)\.(gif|jpg|jpeg|png|ico|bmp|js|css|pdf|doc)$ {";
			$f[]="\t\troot $this->WORKING_DIRECTORY/;";
			$f[]="\t}";
		}
		
		$f[]="\tlocation ~* ^/(wp-admin|wp-login.php|xcache/){";
		$f[]="\t\tsend_timeout 180;";
		$f[]="\t\tproxy_read_timeout 120;";
		$f[]="\t\tproxy_connect_timeout 120;";
		$f[]="\t\trewrite ^([^.]*[^/])$ $1/ permanent;";
		$f[]="\t\tproxy_pass $this->ReverseProxyPattern;";
		$f[]="\t}";
		$f[]="################## WORDPRESS END ##################";
		return @implode("\n",$f);
	}
	
	
	
}