<?php

class compile_ufdbguard_expression{
	var $ScriptName="";
	var $ExprID;
	var $REGEX_TABLE=array();
	
	function __construct($ExprID=null){
		
		$this->ScriptName=basename(__FILE__);
		if(is_numeric($ExprID)){
			$this->ExprID=$ExprID;
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] ->compile_ufdbguard_expression($ExprID)->Load()\n";}
			$this->Load();
		}else{
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] ->compile_ufdbguard_expression($ExprID) Not a numeric\n";}
		}
		
	}

	
	private function Load(){
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$sql="SELECT termsgid FROM webfilter_ufdbexprassoc WHERE enabled=1 AND groupid=$this->ExprID";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error $this->ScriptName (". __LINE__.")\n";return null;}
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] ufdbexprassoc ". mysqli_num_rows($results)." items\n";}
		foreach ($results as $index=>$ligne){
			$this->LoadTermsInGroup($ligne["termsgid"]);
		}
		
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] ->REGEX_TABLE = ". count($this->REGEX_TABLE)." items\n";}
		
	} 
	
	
	private function LoadTermsInGroup($groupid){
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$sql="SELECT webfilter_terms.term,webfilter_terms.xregex FROM webfilter_termsassoc,webfilter_terms
		WHERE webfilter_termsassoc.termid=webfilter_terms.ID
		AND webfilter_termsassoc.term_group=$groupid
		AND webfilter_terms.enabled=1";
		$results=$q->QUERY_SQL($sql);
		$tt=array();
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error $this->ScriptName (". __LINE__.")\n";return null;}
		
		
		
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] Groupid:$groupid (". mysqli_num_rows($results).") items\n";}
		
		
		
		
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." -------------------------------\n";}
		foreach ($results as $index=>$ligne){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] Check:`{$ligne["term"]}`\n";}
			$pattern=$ligne["term"];
			if($ligne["xregex"]==1){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] Add REGEX pattern:`$pattern`\n";}
				$REGEX_TABLE[]=$pattern;
				continue;
			}
			
			$pattern=str_replace(".", "\.", $pattern);
			$pattern=str_replace("$", "\$", $pattern);
			$pattern=str_replace("%", "\%", $pattern);
			$pattern=str_replace("#", "\#", $pattern);
			$pattern=str_replace("(", "\(", $pattern);
			$pattern=str_replace(")", "\)", $pattern);
			$pattern=str_replace("?", "\?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
			$pattern=str_replace("+", "\+", $pattern);
			$pattern=str_replace("*", "\*", $pattern);
			$pattern=str_replace("]", "\]", $pattern);
			$pattern=str_replace("[", "\]", $pattern);
			$pattern=str_replace("-", "\-", $pattern);
			$pattern=str_replace("|", "\|", $pattern);
			
			
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] Add WORD pattern:`$pattern`\n";}
			$tt[]=$pattern;
			
		}	
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." -------------------------------\n";}
		
		
		
		if(count($tt)>1){
			$patternDefinitive="(". @implode("|", $tt).")";
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] REGEX_TABLE[]:`$patternDefinitive`\n";}
			$REGEX_TABLE[]=$patternDefinitive;
		}
		if(count($tt)==1){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] REGEX_TABLE[]:`". @implode("", $tt). "`\n";}
			$REGEX_TABLE[]=@implode("", $tt);}
		
		if(count($REGEX_TABLE)==0){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] REGEX_TABLE: No items\n";}
			return;}
		if(count($REGEX_TABLE)==1){
			$patternDefinitive=@implode("", $REGEX_TABLE);
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] ->REGEX_TABLE[]: \"$patternDefinitive\"\n";}
			$this->REGEX_TABLE[]=$patternDefinitive;return;}
		
		
		$patternDefinitive=@implode(".*", $REGEX_TABLE);
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] ->REGEX_TABLE[]: \"$patternDefinitive\"\n";}
		$this->REGEX_TABLE[]=$patternDefinitive;
		
		
	}
	
	private function isAregex($pattern){
		$pattern="  $pattern";
		$pattern=str_replace('\.', "", $pattern);
		$pattern=str_replace('\?', "", $pattern);
		$pattern=str_replace('\/', "", $pattern);
		$pattern=str_replace('\#', "", $pattern);
		if(strpos($pattern, ".")){return true;}
		if(strpos($pattern, "*")){return true;}
		if(strpos($pattern, ")")){return true;}
		if(strpos($pattern, "(")){return true;}
		if(strpos($pattern, "[")){return true;}
		if(strpos($pattern, "]")){return true;}
		if(strpos($pattern, "$")){return true;}
		if(strpos($pattern, "^")){return true;}
		
		
	}
	
	
}