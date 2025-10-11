<?php


class squid_caches_rules{
	private $q;
	public $final_array=array();
	private $extensions_array=array();
	private $option=null;
	function __construct(){
		$this->q=new mysql_squid_builder();
		
	}
	
	function build(){
		if(!$this->q->TABLE_EXISTS("main_cache_rules")){return;}
		$sql="SELECT * FROM main_cache_rules WHERE `enabled`=1 ORDER BY zorder";
		$results = $this->q->QUERY_SQL($sql);
		if(!$this->q->ok){
			squid_admin_mysql(0, "Proxy Cache rules, MySQL ERROR ", $this->q->mysql_error."\n$sql");
			return;
		}
		
		while ($ligne = mysqli_fetch_assoc($results)) {
			$MAINID=$ligne["ID"];
			$this->extensions_array=array();
			$this->options=null;
			$this->build_options($MAINID);
			$Extensions=$this->ifExtensions($MAINID);
			$this->final_array[]="# --------- {$ligne["rulename"]}";
			$this->build_subrules($MAINID);
		}
		
		
		
	}
	
	
	private function ifExtensions($ID){
		$ID=intval($ID);
		$sql="SELECT *  FROM `cache_rules` WHERE ruleid='$ID' AND `enabled`=1 AND `GroupType`=1  ORDER BY zorder";
		$results = $this->q->QUERY_SQL($sql);
		$ct=mysqli_num_rows($results);
		if($ct==0){return array();}
		
		$ARRAY=array();
		$sql="SELECT ID,rulename  FROM `cache_rules` WHERE ruleid='$ID' AND `enabled`=1 AND `GroupType`=2 ORDER BY zorder";
		$results = $this->q->QUERY_SQL($sql);
		while ($ligne = mysqli_fetch_assoc($results)) {
			$ARRAY[$ligne["ID"]]=$ligne["rulename"];
			$this->build_extensions($ligne["ID"]);
		}
		
		if(count($this->extensions_array)==0){return array();}
		return $ARRAY;
		
	}
	
	
	private function build_extensions($ID){
		$ID=intval($ID);
		$sql="SELECT item FROM cache_rules_items WHERE ruleid=$ID AND enabled=1 ORDER BY zorder";
		$results = $this->q->QUERY_SQL($sql);
		while ($ligne = mysqli_fetch_assoc($results)) {
			$ligne["item"]=trim(strtolower($ligne["item"]));
			if($ligne["item"]==null){continue;}
			$this->extensions_array[$ligne["item"]]=$ligne["item"];
		}
		
	}
	
	private function build_options($ID){
		$ID=intval($ID);
		$sql="SELECT *  FROM `cache_rules_options` WHERE ruleid='$ID' AND `enabled`=1";
		$results = $this->q->QUERY_SQL($sql);
		$ct=mysqli_num_rows($results);
		if($ct==0){$this->option=null;return;}
		
		while ($ligne = mysqli_fetch_assoc($results)) {
			$opts[$ligne["option"]]=$ligne["option"];
			
		}
		
		while (list ($a, $b) = each ($opts) ){$N[]=$b;}
		if(count($N)==0){$this->option=null;return;}
		$this->option="\t".@implode(" ",$N);
	}
	
	private function build_subrules($ID){
		$ID=intval($ID);
		$sql="SELECT *  FROM `cache_rules` WHERE ruleid='$ID' AND `enabled`=1 ORDER BY zorder";
		$results = $this->q->QUERY_SQL($sql);
		$ct=mysqli_num_rows($results);
		if($ct==0){return array();}
		

		
		while ($ligne = mysqli_fetch_assoc($results)) {
			
			$RULEID=intval($ligne["ID"]);
			$GroupType=$ligne["GroupType"];
			if($GroupType==2){if(count($this->extensions_array)>0){if($GLOBALS["VERBOSE"]){"$RULEID / $GroupType extensions_array !\n";}continue;}}
			$this->final_array[]="# ------------------ {$ligne["rulename"]}";
			if($GroupType==1){$this->build_domains($RULEID,$ligne);continue;}
			if($GroupType==3){$this->build_regex($RULEID,$ligne);continue;}
			if($GroupType==2){$this->build_extensions_rule($RULEID,$ligne);continue;}
		}
		
	}
	
	private function build_subrules_extensions(){
		$EXTENSIONS=$this->extensions_array;
		while (list ($a, $b) = each ($EXTENSIONS) ){
			$b=trim(strtolower($b));
			if($b==null){continue;}
			$exts[]=$b;
		}
		if(count($exts)==0){return null;}
		return "/.*\.(".trim(@implode("|", $exts)).")";
		
	}
	
	private function build_domains($ID,$ligne){
		$age=$ligne["min"];
		$maxage=$ligne["max"];
		$perc=$ligne["perc"];
		
		$suffix=null;
		$sql="SELECT *  FROM `cache_rules_items` WHERE ruleid='$ID' AND `enabled`=1 ORDER BY zorder";
		$results = $this->q->QUERY_SQL($sql);
		$ct=mysqli_num_rows($results);
		if($ct==0){return array();}
		if(count($this->extensions_array)>0){$suffix=$this->build_subrules_extensions();}
		while ($ligne = mysqli_fetch_assoc($results)) {
			$domain=$ligne["item"];
			$domain=strtolower(str_replace(".", "\.", $domain));
			$this->final_array[]="refresh_pattern -i $domain$suffix\t$age\t{$perc}%\t$maxage$this->option";
		}
	}
	
	
	
	private function build_extensions_rule($ID,$ligne){
		$age=$ligne["min"];
		$maxage=$ligne["max"];
		$perc=$ligne["perc"];
	
		$suffix=null;
		$sql="SELECT *  FROM `cache_rules_items` WHERE ruleid='$ID' AND `enabled`=1 ORDER BY zorder";
		$results = $this->q->QUERY_SQL($sql);
		$ct=mysqli_num_rows($results);
		if($ct==0){return array();}
		
		while ($ligne = mysqli_fetch_assoc($results)) {
			$EXTENSIONS[$ligne["item"]]=$ligne["item"];
			
			
		}
		while (list ($a, $b) = each ($EXTENSIONS) ){
			$b=trim(strtolower($b));
			if($b==null){continue;}
			$exts[]=$b;
		}
		$finale="\.(".trim(@implode("|", $exts)).")";
		$this->final_array[]="refresh_pattern -i $finale\t$age\t{$perc}%\t$maxage$this->option";
	}
	

	
	private function build_regex($ID,$ligne){
		$age=$ligne["min"];
		$maxage=$ligne["max"];
		$perc=$ligne["perc"];
	
		$suffix=null;
		$sql="SELECT *  FROM `cache_rules_items` WHERE ruleid='$ID' AND `enabled`=1 ORDER BY zorder";
		$results = $this->q->QUERY_SQL($sql);
		$ct=mysqli_num_rows($results);
		if($ct==0){return array();}
	
		while ($ligne = mysqli_fetch_assoc($results)) {
			$EXTENSIONS[$ligne["item"]]=$ligne["item"];
				
				
		}
		while (list ($a, $b) = each ($EXTENSIONS) ){
			$this->final_array[]="refresh_pattern -i $b\t$age\t{$perc}%\t$maxage$this->option";
			
		}
		
		
	}	
	

	
	
}