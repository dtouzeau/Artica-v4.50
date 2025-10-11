<?php
class fstab{
	
	var $fstab_array=array();
	var $mount_array=array();
	function __construct(){
		$sock=new sockets();
		$tbl=unserialize(base64_decode($sock->getFrameWork('cmd.php?fstablist=yes')));
		
		
		foreach ($tbl as $num=>$val){
			$val=trim($val);
			if($val==null){continue;}
			if(substr($val,0,1)=='#'){continue;}
			
			if(preg_match("#(.+?)\s+(.+?)\s+(.+)#",$val,$re)){
				$this->fstab_array[$re[1]][]=array("mount"=>$re[2],"options"=>$re[3]);
			}
		}
		
		$datas=base64_decode($sock->getFrameWork('hd.php?mountlist=yes'));
		$tbl=explode("\n",$datas);
		foreach ($tbl as $num=>$val){
			$val=trim($val);
			if($val==null){continue;}
			if(substr($val,0,1)=='#'){continue;}
			if(preg_match("#(.+?)\s+on\s+(.+?)\s+type#",$val,$re)){
				$this->mount_array[$re[1]][$re[2]]=true;
			}
		}
		
	}
	
	public function save(){
		if(!is_array($this->fstab_array)){return false;}
		reset($this->fstab_array);
		while (list ($dev, $array) = each ($this->fstab_array) ){
			while (list ($index, $array2) = each ($array) ){
			 $conf=$conf ."$dev\t{$array2["mount"]}\t{$array2["options"]}\n";
			 }
		}
		
		$sock=new sockets();
		$sock->SaveConfigFile($conf,'SystemFSTAB');
		$sock->getfile('fstabapply');
	}
}
?>