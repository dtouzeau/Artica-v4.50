<?php


class sugarleads{
	 var $id=null;
	 var $date_entered=null;
	 var $date_modified=null;
	 var $modified_user_id=null;
	 var $created_by=null;
	 var $description=null;
	 var $deleted=null;
	 var $assigned_user_id=null;
	 var $salutation=null;
	 var $first_name=null;
	 var $last_name=null;
	 var $title=null;
	 var $department=null;
	 var $do_not_call=null;
	 var $phone_home=null;
	 var $phone_mobile=null;
	 var $phone_work=null;
	 var $phone_other=null;
	 var $phone_fax=null;
	 var $primary_address_street=null;
	 var $primary_address_city=null;
	 var $primary_address_state=null;
	 var $primary_address_postalcode=null;
	 var $primary_address_country=null;
	 var $alt_address_street=null;
	 var $alt_address_city=null;
	 var $alt_address_state=null;
	 var $alt_address_postalcode=null;
	 var $alt_address_country=null;
	 var $assistant=null;
	 var $assistant_phone=null;
	 var $converted=null;
	 var $refered_by=null;
	 var $lead_source=null;
	 var $lead_source_description=null;
	 var $status=null;
	 var $status_description=null;
	 var $reports_to_id=null;
	 var $account_name=null;
	 var $account_description=null;
	 var $contact_id=null;
	 var $account_id=null;
	 var $opportunity_id=null;
	 var $opportunity_name=null;
	 var $opportunity_amount=null;
	 var $campaign_id=null;
	 var $birthdate=null;
	 var $portal_name=null;
	 var $portal_app=null;
	 var $website=null;
	 var $email_address=null;
	 var $assigned_username=null;
	 var $company=null;
	 var $EntriesCSV=array();

	 function AddCsvMemEntry(){
	 	$this->id=$this->create_guid();
	 	$this->deleted=0;
	 	$this->modified_user_id=1;
	 	$this->date_entered=date("m/d/Y H:i:s");
	 	$this->date_modified=$this->date_entered;
	 	$this->salutation=$this->SexTransform($this->salutation);
	 	$this->lead_source=$this->CheckSourceLeads($this->lead_source);
	 	if($this->last_name==null){$this->last_name=".";}
	 	$f[]="\"$this->first_name\"";
	 	$f[]="\"$this->last_name\"";
	 	$f[]="\"$this->id\"";
	 	$f[]="\"$this->salutation\"";
	 	$f[]="\"$this->title\"";
	 	$f[]="\"$this->department\"";
	 	$f[]="\"$this->account_name\"";
	 	$f[]="\"$this->account_description\"";
	 	$f[]="\"$this->website\"";
	 	$f[]="\"$this->email_address\"";
	 	$f[]="\"$this->phone_mobile\"";
	 	$f[]="\"$this->phone_work\"";
	 	$f[]="\"$this->phone_home\"";
	 	$f[]="\"$this->phone_other\"";
	 	$f[]="\"$this->phone_fax\"";
	 	$f[]="\"$this->primary_address_street\"";
	 	$f[]="\"$this->primary_address_city\"";
	 	$f[]="\"$this->primary_address_state\"";
	 	$f[]="\"$this->primary_address_postalcode\"";
	 	$f[]="\"$this->primary_address_country\"";
	 	$f[]="\"$this->alt_address_street\"";
	 	$f[]="\"$this->alt_address_city\"";
	 	$f[]="\"$this->alt_address_state\"";
	 	$f[]="\"$this->alt_address_postalcode\"";
	 	$f[]="\"$this->alt_address_country\"";
	 	$f[]="\"$this->status\"";
	 	$f[]="\"$this->status_description\"";
	 	$f[]="\"$this->lead_source\"";
	 	$f[]="\"$this->lead_source_description\"";
	 	$f[]="\"$this->description\"";
	 	$f[]="\"$this->converted\"";
	 	$f[]="\"$this->opportunity_name\"";
	 	$f[]="\"$this->opportunity_amount\"";
	 	$f[]="\"$this->refered_by\"";
	 	$f[]="\"$this->campaign_id\"";
	 	$f[]="\"$this->do_not_call\"";
	 	$f[]="\"$this->portal_name\"";
	 	$f[]="\"$this->portal_app\"";
	 	$f[]="\"$this->reports_to_id\"";
	 	$f[]="\"$this->assistant\"";
	 	$f[]="\"$this->assistant_phone\"";
	 	$f[]="\"$this->birthdate\"";
	 	$f[]="\"$this->contact_id\"";
	 	$f[]="\"$this->account_id\"";
	 	$f[]="\"$this->opportunity_id\"";
	 	$f[]="\"$this->assigned_username\"";
	 	$f[]="\"$this->assigned_user_id\"";
	 	$f[]="\"$this->date_entered\"";
	 	$f[]="\"$this->date_modified\"";
	 	$f[]="\"$this->created_by\"";
	 	$f[]="\"$this->modified_user_id\"";
	 	$f[]="\"$this->deleted\"";
	 	$f[]="\"$this->company\"";
	 	$this->EntriesCSV[]=@implode(",", $f);
	 	$this->ClearEntryBeforeInsert();
		}
		
		private function ClearEntryBeforeInsert(){
		 	$this->first_name=null;
		 	$this->last_name=null;
		 	$this->id=null;
		 	$this->salutation=null;
		 	$this->title=null;
		 	$this->department=null;
		 	$this->account_name=null;
		 	$this->account_description=null;
		 	$this->website=null;
		 	$this->email_address=null;
		 	$this->phone_mobile=null;
		 	$this->phone_work=null;
		 	$this->phone_home=null;
		 	$this->phone_other=null;
		 	$this->phone_fax=null;
		 	$this->primary_address_street=null;
		 	$this->primary_address_city=null;
		 	$this->primary_address_state=null;
		 	$this->primary_address_postalcode=null;
		 	$this->primary_address_country=null;
		 	$this->alt_address_street=null;
		 	$this->alt_address_city=null;
		 	$this->alt_address_state=null;
		 	$this->alt_address_postalcode=null;
		 	$this->alt_address_country=null;
		 	$this->status=null;
		 	$this->status_description=null;
		 	$this->lead_source=null;
		 	$this->lead_source_description=null;
		 	$this->description=null;
		 	$this->converted=null;
		 	$this->opportunity_name=null;
		 	$this->opportunity_amount=null;
		 	$this->refered_by=null;
		 	$this->campaign_id=null;
		 	$this->do_not_call=null;
		 	$this->portal_name=null;
		 	$this->portal_app=null;
		 	$this->reports_to_id=null;
		 	$this->assistant=null;
		 	$this->assistant_phone=null;
		 	$this->birthdate=null;
		 	$this->contact_id=null;
		 	$this->account_id=null;
		 	$this->opportunity_id=null;
		 	$this->assigned_username=null;
		 	$this->assigned_user_id=null;
		 	$this->date_entered=null;
		 	$this->date_modified=null;
		 	$this->created_by=null;
		 	$this->modified_user_id=null;
		 	$this->deleted=null;
		 	$this->company=null;			
		}
		
		function CompileCSVMem(){
			$headers="\"First Name\",\"Last Name\",\"ID\",\"Salutation\",\"Title\",\"Department\",\"Account Name\",\"Account Description\",\"Website\",\"Email Address\",\"Mobile Phone\",\"Office Phone\",\"Home Phone\",\"Other Phone\",\"Fax\",\"Primary Address Street\",\"Primary Address City\",\"Primary Address State\",\"Primary Address Postalcode\",\"Primary Address Country\",\"Alt Address Street\",\"Alt Address City\",\"Alt Address State\",\"Alt Address Postalcode\",\"Alt Address Country\",\"Status\",\"Status Description\",\"Lead Source\",\"Lead Source Description\",\"Description\",\"Converted\",\"Opportunity Name\",\"Opportunity Amount\",\"Referred By\",\"Campaign ID\",\"Do Not Call\",\"Portal Name\",\"Portal Application\",\"Reports To ID\",\"Assistant\",\"Assistant Phone\",\"Birthdate\",\"Contact ID\",\"Account ID\",\"Opportunity ID\",\"Assigned User Name\",\"Assigned User ID\",\"Date Created\",\"Date Modified\",\"Created By ID\",\"Modified By ID\",\"Deleted\",\"company\"";
			@file_put_contents("/home/lead.csv", $headers."\n".@implode("\n", $this->EntriesCSV));
				
		}
		
		private function SexTransform($value){
				$ARR["0d0a4d6d65"]="";
				$ARR["M"]="Mr.";
				$ARR["MR"]="Mr.";
				$ARR["Mr"]="Mr.";
				$ARR["cv"]="Mr.";
				$ARR["%%E"]="Mr.";
				$ARR["-"]="Mr.";
				$ARR["--"]="Mr.";
				$ARR["1"]="Mr.";
				$ARR["11/01/99"]="Mr.";
				$ARR["21"]="Mr.";
				$ARR["Capitaine"]="Mr.";
				$ARR["F"]="Mr.";
				$ARR["Jean"]="Mr.";
				$ARR["M"]="Mr.";
				$ARR["M%r"]="Mr.";
				$ARR["M,"]="Mr.";
				$ARR["M."]="Mr.";
				$ARR["M;"]="Mr.";
				$ARR["ME"]="Mrs.";
				$ARR["MELLE"]="Mrs.";
				$ARR["MLE"]="Mrs.";
				$ARR["MME"]="Mrs.";
				$ARR["MMME"]="Mrs.";
				$ARR["MMR"]="Mr.";
				$ARR["MR"]="Mr.";;
				$ARR["MRMME"]="Mrs.";
				$ARR["Mademoiselle"]="Mrs.";
				if(isset($ARR[$value])){return $ARR[$value];}
				return $value;			
		}
		
		
		private function CheckSourceLeads($value){
			$f["Cold Call"]=true;
			$f["Existing Customer"]=true;
			$f["Self Generated"]=true;
			$f["Employee"]=true;
			$f["Partner"]=true;
			$f["Public Relations"]=true;
			$f["Direct Mail"]=true;
			$f["Conference"]=true;
			$f["Trade Show"]=true;
			$f["Web Site"]=true;
			$f["Word of mouth"]=true;
			$f["Email"]=true;
			$f["Campaign"]=true;
			$f["Other"]=true;
			if(!isset($f[$value])){return "Other";}			
			
			
		}
		
		
private function create_guid(){
	$microTime = microtime();
	list($a_dec, $a_sec) = explode(" ", $microTime);

	$dec_hex = dechex($a_dec* 1000000);
	$sec_hex = dechex($a_sec);

	$this->ensure_length($dec_hex, 5);
	$this->ensure_length($sec_hex, 6);

	$guid = "";
	$guid .= $dec_hex;
	$guid .= $this->create_guid_section(3);
	$guid .= '-';
	$guid .= $this->create_guid_section(4);
	$guid .= '-';
	$guid .= $this->create_guid_section(4);
	$guid .= '-';
	$guid .= $this->create_guid_section(4);
	$guid .= '-';
	$guid .= $sec_hex;
	$guid .= $this->create_guid_section(6);

	return $guid;

}		
	private function create_guid_section($characters){
		$return = "";
		for($i=0; $i<$characters; $i++)
		{
			$return .= dechex(mt_rand(0,15));
		}
		return $return;
	}
	
	private function ensure_length(&$string, $length){
		$strlen = strlen($string);
		if($strlen < $length)
		{
			$string = str_pad($string,$length,"0");
		}
		else if($strlen > $length)
		{
			$string = substr($string, 0, $length);
		}
	}	 
	
}