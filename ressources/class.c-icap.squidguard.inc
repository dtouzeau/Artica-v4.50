<?php
$GLOBALS["OUTPUT_TEXT"]="[WEBF]: C-IPCAP service";
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.groups.inc");
include_once(dirname(__FILE__)."/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/class.squid.inc");;

class cicap_squidguard{
	
	var $DatabaseDirectory="/home/c-icap/blacklists";
	var $PersonalDirectory="/home/c-icap/blacklists-personal";
	var $categories_available=array();
	private $Profiles=array();
	private $headerlogs=array();
	private $c_icap_mods_sguardDB;
	
	function __construct(){
		$unix=new unix();
		$this->c_icap_mods_sguardDB=$unix->find_program("c-icap-mods-sguardDB");
	}
	
	
	public function build(){
		
		
		$this->BuildCategories();
		$this->BuildProfiles_list();
		
		
		
		$f[]="LogFormat myUrlCheck \"%tl, %>a %im %is %huo  [MatchedCat: %{url_check:matched_cat}Sa] [Action4cat: %{url_check:action_cat}Sa] [Action: %{url_check:action}Sa]\"";
		//$f[]="AccessLog Logger";
		$f[]="";
		$f[]="# # # # # # # # RULES # # # # # # # ";
		if(count($this->headerlogs)>0){ while (list ($index,$line) = each ($this->headerlogs) ){ $f[]="# $line"; }}
		$f[]="# Defines a black/white databases";
		$f[]="";
		while (list ($category,$line) = each ($this->categories_available) ){
			$f[]="$line";
			
		}
		$f[]="";
		$f[]="# # # # # # # # Profiles # # # # # # # ";
		$f[]="";
		while (list ($ruleid,$items) = each ($this->Profiles) ){
			$f[]="# Profile for rule N.{$ruleid}";
			$f[]=@implode("\n",$items);
			$f[]="";
			
		}
		$f[]="";
		return @implode("\n", $f);
	}
	
	
	private function BuildCategories(){
		
		$q=new mysql_squid_builder();
		$sock=new sockets();

		$sql="SELECT category FROM cicap_profiles_blks GROUP BY category";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){$this->headerlogs[]=$q->mysql_error;}
		if(mysqli_num_rows($results)>0){
			while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
				$category=$ligne["category"];
				$this->build_categories_paragraph($category);
			}
		}
		
		
		
		
	}
	
	private function BuildProfile_categories($ID){
		$ProfileName="Profile$ID";
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM cicap_profiles_blks WHERE mainid='$ID'";
		$results=$q->QUERY_SQL($sql);
		if(mysqli_num_rows($results)==0){
			$this->Profiles[$ID][]="url_check.Profile $ProfileName pass ALL";
		}
		
		$ACTIONS[0]="block";
		$ACTIONS[1]="pass";
		
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$category=trim($ligne["category"]);
			if($category==null){continue;}
				
			$tablename=$q->cat_totablename($category);
			if(!isset($this->categories_available[$category])){
				$this->headerlogs[]=__LINE__."]: $category no such category";
				continue;
			}
			$this->Profiles[$ID][]="url_check.Profile $ProfileName {$ACTIONS[$ligne["bltype"]]} $category";
		}
		
	}
	
	
	private function BuildProfile($ID){
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM cicap_profiles WHERE ID='$ID'"));
		$ID=$ligne["ID"];
		$ProfileName="Profile$ID";
		if($ID==0){$ProfileName="default";}
		$ProfilesCat=$this->BuildProfile_categories($ID);
	}
	
	
	private function BuildProfiles_list(){
		$q=new mysql_squid_builder();
		$this->BuildProfile(0);
		$sql="SELECT rulename,ID FROM cicap_profiles WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		if(mysqli_num_rows($results)==0){return;}
		
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$ID=$ligne["ID"];
			$this->BuildProfile($ID);
		}

	}
	
	
	
	private function build_categories_paragraph($category){
		
		$q=new mysql_squid_builder();
		$tablename=$q->cat_totablename($category);
		echo "Starting......: ".date("H:i:s")." {$GLOBALS["OUTPUT_TEXT"]} Checking $category/$tablename\n";
		
		if(is_file("$this->PersonalDirectory/$tablename/domains.db")){
			echo "Starting......: ".date("H:i:s")." {$GLOBALS["OUTPUT_TEXT"]} $category is available\n";
			$this->categories_available[$category]="url_check.LoadSquidGuardDB $category $this->PersonalDirectory/$tablename/ \"Personal $category Sites\"";
			return;
		}
		
		if(!is_file("$this->DatabaseDirectory/$tablename/domains.db")){
			echo "Starting......: ".date("H:i:s")." {$GLOBALS["OUTPUT_TEXT"]} Checking $tablename no such database\n";
			return;
		}
		echo "Starting......: ".date("H:i:s")." {$GLOBALS["OUTPUT_TEXT"]} $category is available\n";
		$this->buildFakeDB($category);
		$this->categories_available[$category]="url_check.LoadSquidGuardDB $category $this->DatabaseDirectory/$tablename/ \"Artica $category Sites\"";
	}
	
	private function buildFakeDB($category){
		$unix=new unix();
		if(!is_file($this->c_icap_mods_sguardDB)){return;}
		$q=new mysql_squid_builder();
		$tablename=$q->cat_totablename($category);
		$DatabaseDirectory="$this->DatabaseDirectory/$tablename";
		@unlink("$DatabaseDirectory/urls.db");
		@file_put_contents("$DatabaseDirectory/urls", "http://www.".md5(microtime()));
		shell_exec("$this->c_icap_mods_sguardDB -C -f urls -db $DatabaseDirectory");
		$unix->chmod_func(0755, "$DatabaseDirectory/urls.db");
		$unix->chown_func("squid","squid","$DatabaseDirectory/urls.db");
	}
	
	private function buildFakeDB_perso($category){
		$unix=new unix();
		if(!is_file($this->c_icap_mods_sguardDB)){return;}
		$q=new mysql_squid_builder();
		$tablename=$q->cat_totablename($category);
		$DatabaseDirectory="$this->PersonalDirectory/$tablename";
		@unlink("$DatabaseDirectory/urls.db");
		@file_put_contents("$DatabaseDirectory/urls.db", "http://www.".md5(microtime()));
		shell_exec("$this->c_icap_mods_sguardDB -C -f urls -db $DatabaseDirectory");
		$unix->chmod_func(0755, "$DatabaseDirectory/urls.db");
		$unix->chown_func("squid","squid","$DatabaseDirectory/urls.db");
	}	
	
	public function Construct_personal_categories(){
		$unix=new unix();
		@mkdir("$this->PersonalDirectory",0755,true);
		$unix->chown_func("squid","squid",$this->PersonalDirectory);
		
		$q=new mysql_squid_builder();
		$sql="SELECT category FROM personal_categories";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$category=trim($ligne["category"]);
			if($category==null){continue;}
			
			$this->contruct_personal_category($category);
		}
		
		
		
	}
	
	public function contruct_personal_category($categoryname){
		$unix=new unix();
		$q=new mysql_squid_builder();
		$tablename=$q->cat_totablename($categoryname);
		$DatabaseDir="$this->PersonalDirectory/$tablename";
		$files[]="$DatabaseDir/domains.db";
		$files[]="$DatabaseDir/domains.db";
		$files[]="$DatabaseDir/__db.001";  
		$files[]="$DatabaseDir/__db.002";  
		$files[]="$DatabaseDir/__db.003";  
		$files[]="$DatabaseDir/__db.004";	
		$files[]="$DatabaseDir/urls";  
		$files[]="$DatabaseDir/urls.db";
		$files[]="$DatabaseDir/domains";
		$files[]="$DatabaseDir/domains.tmp";
		
		while (list ($num, $filename) = each ($files) ){
			
			if(is_file($filename)){
				if($GLOBALS["VERBOSE"]){echo "Removing $filename\n";}
				@unlink($filename);}
		}
		
		
		
		if($q->COUNT_ROWS($tablename)==0){
			if($GLOBALS["VERBOSE"]){echo "Found category $categoryname no such item, aborting\n";}
			$this->headerlogs[]=__LINE__."]: $categoryname no such item";
			return;}
		if($GLOBALS["VERBOSE"]){echo "Found category $categoryname `$tablename`\n";}
		
		if(!is_dir($DatabaseDir)){@mkdir($DatabaseDir,0755,true);}
		$unix->chmod_func(0755, "$DatabaseDir");
		$unix->chown_func("squid","squid","$DatabaseDir");
		$sql="SELECT pattern FROM $tablename WHERE enabled=1 ORDER BY pattern INTO OUTFILE '$DatabaseDir/domains.tmp' LINES TERMINATED BY '\n';";
		if($GLOBALS["VERBOSE"]){echo "Extract items from $tablename to $DatabaseDir/domains.tmp\n";}
		$q->QUERY_SQL($sql);
		if(!is_file("$DatabaseDir/domains.tmp")){
			if($GLOBALS["VERBOSE"]){echo "Failed !!! $DatabaseDir/domains.tmp, no such database\n";}
			return;
	
		}
		
		$handle  = @fopen("$DatabaseDir/domains.tmp", "r");
		$handle2 = @fopen("$DatabaseDir/domains", "w");
		if (!$handle) {echo "Failed to open $DatabaseDir/domains.tmp\n";return;}
		if (!$handle2) {echo "Failed to open $DatabaseDir/domains\n";return;}
		$c=0;
		while (!feof($handle)){
			$www =trim(fgets($handle));
			$www=trim(str_replace('"', "", $www));
			if($www==null){continue;}
			fwrite($handle2, "$www\n");
			fwrite($handle2, "www.$www\n");
			$c++;
		}
		
		@fclose($handle);
		@fclose($handle2);
		@unlink("$DatabaseDir/domains.tmp");
		$size=@filesize("$DatabaseDir/domains");
		if($GLOBALS["VERBOSE"]){echo "$DatabaseDir/domains = $size bytes $c items\n";}
		
		if(!$q->ok){
			$error=str_replace("\n", " ", $q->mysql_error);
			$this->headerlogs[]=__LINE__."]: $categoryname $error";
			if($GLOBALS["VERBOSE"]){echo "Found category $categoryname `$q->mysql_error`\n";}
			return;
		}
		
		if($GLOBALS["VERBOSE"]){echo "Building conversion\n";}
		$resultsCMD=array();
		$cmd="$this->c_icap_mods_sguardDB -C -f domains -db $DatabaseDir -d 3 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		
		exec($cmd,$resultsCMD);
		
		while (list ($num, $line) = each ($resultsCMD) ){
			if($GLOBALS["VERBOSE"]){echo "Extract $line\n";}
		}
		
		reset($files);
		while (list ($num, $filename) = each ($files) ){
			if(is_file($filename)){
				if($GLOBALS["VERBOSE"]){echo "$filename (squid:squid)\n";}
				$unix->chown_func("squid","squid","$filename");
			}
		}
		
		
		$uri_table="categoryuris_$categoryname";
		if($q->COUNT_ROWS($uri_table)==0){
			$this->headerlogs[]=__LINE__."]: $uri_table no such item";
			if($GLOBALS["VERBOSE"]){echo "Found category $uri_table `no such item`\n";}
			return;
		}
		
		$sql="SELECT pattern FROM $tablename WHERE enabled=1 ORDER BY pattern INTO OUTFILE '$DatabaseDir/urls' LINES TERMINATED BY '\n';";
		if($GLOBALS["VERBOSE"]){echo "Extract URLS items from $tablename to $DatabaseDir/urls\n";}
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			$error=str_replace("\n", " ", $q->mysql_error);
			$this->headerlogs[]=__LINE__."]: $categoryname $error";
			if($GLOBALS["VERBOSE"]){echo "Found category URLS $categoryname `$q->mysql_error`\n";}
			$this->buildFakeDB_perso();
			return;
		}
		
		$resultsCMD=array();
		$cmd="$this->c_icap_mods_sguardDB -C -f urls -db $DatabaseDir -d 3 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec($cmd,$resultsCMD);
		while (list ($num, $line) = each ($resultsCMD) ){
			if($GLOBALS["VERBOSE"]){echo "Extract URL: $line\n";}
		}
		
	}
	
	
}