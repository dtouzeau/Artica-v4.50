<?php
include_once(dirname(__FILE__)."/class.unix.inc");

class hd{
	public $dev;
	
	
	public function __construct($dev){
		$this->dev=$dev;
		
		
	}
	
	
	public function uuid_from_dev(){
		$unix=new unix();
		$blkid=$unix->find_program("blkid");
		if(!is_file($blkid)){return;}
		exec("$blkid $this->dev 2>&1",$results);
		foreach ($results as $num=>$line){
			if(preg_match("#UUID=\"(.+?)\"#", $line,$re)){return $re[1];}
		}
		
	}
	
	public function AddFSTab($mount){
		if($this->dev==null){return null;}
		if($mount==null){return null;}
		$uuid=$this->uuid_from_dev();
		if($uuid<>null){$this->AddFSTabUUid($uuid,$mount);return;}
		writelogs_framework("create directory $mount ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		@mkdir($mount,0755,true);
	
		$datas=explode("\n",@file_get_contents("/etc/fstab"));
		$chk=$datas;
	
		while (list ($num, $ligne) = each ($chk) ){
			if(preg_match("#(.+?)\s+(.+?)\s+#",$ligne,$re)){
				if(trim(strtolower($this->dev))==trim(strtolower($re[1]))){
					if(trim(strtolower($mount))==trim(strtolower($re[2]))){
						return null;
					}
				}
			}
				
		}
	
		$datas[]="$this->dev\t$mount\tauto\tdefaults\t0\t0\n";
		@copy("/etc/fstab", "/etc/fstab.".time().".bak");
		if(!file_put_contents("/etc/fstab",implode("\n",$datas))){
			writelogs_framework("Unable to save /etc/fstab",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		$unix=new unix();
		$mountBin=$unix->find_program("mount");
		shell_exec("$mountBin \"$mount\"");
	
	}	
	
 private function AddFSTabUUid($uuid,$mount){
	$unix=new unix();
 	writelogs_framework("$uuid: create directory $mount ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
 	@mkdir($mount,0755,true);
 	$datas=explode("\n",@file_get_contents("/etc/fstab"));
 	
 	foreach ($datas as $num=>$ligne){
 		if(preg_match("#UUID=$uuid\s+(.+?)\s+#",$ligne,$re)){
 			if(trim(strtolower($mount))==trim(strtolower($re[1]))){
 				writelogs_framework("$uuid -> $mount Already in FSTAB /etc/fstab",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
 				return;
 			}
 			unset($datas[$num]);
 			break;
 		}
 	}
 	$datas[]="UUID=$uuid\t$mount\tauto\tdefaults\t0\t0\n";
 	@copy("/etc/fstab", "/etc/fstab.".time().".bak");
 	if(!file_put_contents("/etc/fstab",implode("\n",$datas))){
 		writelogs_framework("Unable to save /etc/fstab",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
 		return;
 	}
 	$mountBin=$unix->find_program("mount");
 	shell_exec("$mountBin \"$mount\"");
 	
 }
	
	
}