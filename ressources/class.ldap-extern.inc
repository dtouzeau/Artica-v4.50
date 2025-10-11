<?php


class ldap_extern{
	private $ldap_server;
	private $ldap_port;
	private $userdn;
	private $ldap_password;
	public $ldap_suffix;
	private $ldap_filter_users;
	private $ldap_filter_group;
	private $ldap_user_attribute;
	private $ldap_group_attribute;
	private $ldap_filter_search_group;
	public $ldap_filter_group_attribute;
	private $ldap_connection;
	public $ldap_error;
	private $ldapbind;
	public	$ok=false;
	function __construct(){
		$this->LoadSettings();
		
	}
	
	
	
	function LoadSettings(){
		$EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalAuth")));
		$this->ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
		$this->ldap_port=intval($EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"]);
		$this->userdn=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
		$this->ldap_password=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
		$this->ldap_suffix=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
		$this->ldap_filter_users=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
		$this->ldap_filter_group=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
		$this->ldap_user_attribute=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user_attribute"];
		$this->ldap_group_attribute=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_group_attribute"];
		$this->ldap_filter_search_group=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_search_group"];
		$this->ldap_filter_group_attribute=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group_attribute"];
		
	}
	
	private function connect(){
		$this->ok=false;
		if(!$this->socket_connect()){
		    VERBOSE("socket_connect report failed",__LINE__);
			return false;}
		$this->ldap_error=null;
		ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
		$this->ldapbind=@ldap_bind($this->ldap_connection, $this->userdn, $this->ldap_password);
		
		if($this->ldapbind){
			$this->ok=true;
			return true;
		}
		
		$ldap_errno=ldap_errno($this->ldap_connection);
		$ldap_err2str=ldap_err2str($ldap_errno);
		VERBOSE("ldap_bind FAILED $ldap_err2str",__LINE__);
		$this->CloseConnection();
        
		$this->ldap_error="Error $ldap_errno $ldap_err2str";
		$this->ok=false;
		return false;
	}
    private function CloseConnection(){
        try {
            if ($this->ldap_connection  && @ldap_bind($this->ldap_connection)) {
                ldap_close($this->ldap_connection);
                $this->ldap_connection=false;
            }
        } catch (Exception $e) {

        }
    }
	
	private function socket_connect(){
		$this->ldap_connection=@ldap_connect($this->ldap_server, $this->ldap_port ) ;
		if($this->ldap_connection){
			$this->ok=true;
			return true;
		}
		$ldap_errno=ldap_errno($this->ldap_connection);
		$ldap_err2str=ldap_err2str($ldap_errno);
		$this->CloseConnection();
        VERBOSE("$this->ldap_server:$this->ldap_port socket failed err:$ldap_errno $ldap_err2str",__LINE__);
		$this->ldap_error="$this->ldap_server:$this->ldap_port socket failed err:$ldap_errno $ldap_err2str";
		return false;
		
	}
	

	function UserAndGroupSearch($tofind,$maxitems=0){
		
		if(!$this->connect()){$this->debug("Connection failed");return array();}
		$searchFilter=$this->ldap_filter_users;
		$searchFilter=str_replace("%u", "$tofind", $searchFilter);
		$searchFilter=str_replace("%s", "*", $searchFilter);
		
		$searchFilterGroup=$this->ldap_filter_search_group;
		$searchFilterGroup=str_replace("%u", "$tofind", $searchFilterGroup);
		$searchFilterGroup=str_replace("%s", "*", $searchFilterGroup);	

		$FullSearch="(|$searchFilter$searchFilterGroup)";
		
		
		$attrs=array("displayName","uid","cn","mail","objectClass","description","gidNumber","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
		$this->debug("Search $FullSearch in $this->ldap_suffix",__LINE__);
		$this->debug("Attributes: ".@implode(", ", $attrs),__LINE__);
		
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,"$FullSearch",$attrs,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->debug("`$error` ($errstr)",__LINE__);
				$this->ldap_error="SearchGroups:`$error` ($errstr) $searchFilter".__CLASS__."/".__FUNCTION__." line:".__LINE__;
			}
			$this->ok=false;
			$this->CloseConnection();
			return array();
		}
		
		$array_ldap=@ldap_get_entries($this->ldap_connection,$sr);
		$count=$array_ldap["count"];
		
		if($GLOBALS["VERBOSE"]){echo "$searchFilter -> $count<br>\n";print_r($array_ldap)."<br>\n";}
		return $array_ldap;
	
	}
	
	
	public function SearchGroups($pattern,$maxitems=0){
		if($maxitems==0){$maxitems=null;}
		if(!$this->connect()){
			if($GLOBALS["VERBOSE"]){echo "Connection failed<br>\n";}
			$this->ok=false;
			return array();
		}
		if($pattern==null){$pattern="*";}
		
		$searchFilter=$this->ldap_filter_search_group;
		$searchFilter=str_replace("%s", $pattern, $searchFilter);
		$filter=array($this->ldap_group_attribute);
		
		if($GLOBALS["VERBOSE"]){echo "$searchFilter/$this->ldap_suffix<br>\n";}
		
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,"$searchFilter",$filter,null, $maxitems, 20);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->ldap_error="SearchGroups:`$error` ($errstr) $searchFilter".__CLASS__."/".__FUNCTION__." line:".__LINE__;
			}
			$this->ok=false;
			$this->CloseConnection();
			return array();
		}
		
		$array_ldap=@ldap_get_entries($this->ldap_connection,$sr);
		$count=$array_ldap["count"];
		if($GLOBALS["VERBOSE"]){echo "$searchFilter -> $count<br>\n";}
		
		for($i=0;$i<$count;$i++){
			$attr=$array_ldap[$i][$this->ldap_group_attribute][0];
			$dn=$array_ldap[$i]["dn"];
			$MAIN[$dn]=$attr;
		}
		return $MAIN;
	}
	
	public function CountDeUsersByGroupDN($dn){
		return count($this->HashUsersFromGroupDN($dn));
		
	}
	
	
	public function DNInfos($dn){
		if(!$this->connect()){return false;}
		$sr =@ldap_read($this->ldap_connection,$dn,"objectClass=*",array(),null, null, 10);
		if(!$sr){
			$this->CloseConnection();
			return false;
		}
		
		
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		$this->CloseConnection();
		return $hash;
	}
	
	
	public function UserIdentity($uid):array{
        if(is_null($uid)){
            return array();
        }
        if(is_null($this->ldap_filter_users)){
            return array();
        }
		if(!$this->connect()){$this->debug("Connection failed",__LINE__);return array();}
		$searchFilter=$this->ldap_filter_users;
		$searchFilter=str_replace("%s", $uid, $searchFilter);
		$filter=array();
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->ldap_error="SearchGroups:`$error` ($errstr) ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
		
			}
			$this->ok=false;
			$this->CloseConnection();
			return array();
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if($hash["count"]==0){return array();}
		return $hash;
		
	}
	
	public function GETDN_FROM_UID($uid){
		if(!$this->connect()){$this->debug("Connection failed",__LINE__);return array();}
		$hash=$this->UserIdentity($uid);
		return $hash[0]["dn"];
	}
	private function _GetGroupsByMember_search($pattern){
		if(!$this->connect()){$this->debug("Connection failed");return array();}
		$searchFilter=$this->ldap_filter_group;
		$searchFilter=str_replace("%u", "$pattern", $searchFilter);
		$searchFilter=str_replace("%s", "*", $searchFilter);
		$filter=array("dn",$this->ldap_filter_group_attribute,$this->ldap_group_attribute);
		$this->debug("Search &laquo;$searchFilter&raquo; in $this->ldap_suffix",__LINE__);
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				$this->ldap_error="SearchGroups:`$error` ($errstr) ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
		
			}
			$this->ok=false;
			$this->CloseConnection();
			return array("count"=>0);
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		return $hash;
		
	}
	
	private function GetGroupsByMember($DN,$uid){
		if(!$this->connect()){$this->debug("Connection failed");return array();}
		$this->debug("[uid]={$uid}, ldap_filter_group_attribute=$this->ldap_filter_group_attribute, ldap_user_attribute=$this->ldap_user_attribute",__LINE__);
		
		$hash=$this->_GetGroupsByMember_search($DN);
		if($hash["count"]==0){
				if($uid==null){$uid=$this->get_uid_from_dn($DN);}
				$hash=$this->_GetGroupsByMember_search($uid);
				
			}
		$HASHFINAL=array();
		for($i=0;$i<$hash["count"];$i++){
			$HASHFINAL[$hash[$i]["dn"]]=$hash[$i][strtolower($this->ldap_group_attribute)][0];
			
		}
		
		
		return $HASHFINAL;
		
	}
	
	private function get_uid_from_dn($dn):string{
		$HASH=$this->DNInfos($dn);
		$ldap_user_attribute=strtolower($this->ldap_user_attribute);
        if(isset($HASH[0][$ldap_user_attribute][0])) {
            VERBOSE("$dn === {$HASH[0][$ldap_user_attribute][0]}",__LINE__);
            return $HASH[0][$ldap_user_attribute][0];
        }
        return "";
		
	}
	
	public function GetGroupsFromuser($uid){
		if(isset($GLOBALS[__CLASS__.".".__FUNCTION__][$uid])){return $GLOBALS[__CLASS__.".".__FUNCTION__][$uid];}
		
		if(stripos("    $uid", 'cn=')>0){
			return $this->GetGroupsByMember($uid,null);
		}
		if(stripos("    $uid", ',dc=')>0){
			return $this->GetGroupsByMember($uid,null);
		}		
		$this->debug("GetGroupsByMember($uid)", __LINE__);
		$hash=$this->UserIdentity($uid);
		$DN=$hash[0]["dn"];
		$FINAL=$this->GetGroupsByMember($DN,$uid);
		$this->debug("GetGroupsByMember($DN,$uid)={". count($FINAL)."}", __LINE__);
		return $FINAL;
			
		
		
		
	}
	
	private function debug($text,$line=0){
		if(!$GLOBALS["VERBOSE"]){return;}
		if(!function_exists("VERBOSE")){return;}
		VERBOSE($text, $line);;
	}
	
	public function HashUsersFromGroupDN($dn){

		if(isset($GLOBALS["HashUsersFromGroupDN($dn)"])){return $GLOBALS["HashUsersFromGroupDN($dn)"];}
		if(!$this->connect()){
		    VERBOSE("Connection failed",__LINE__);
			$this->ok=false;
			return array();
		}
		$f=array();
		$searchFilter=$this->ldap_filter_group;
		$searchFilter=str_replace("%u", "*", $searchFilter);
		$searchFilter=str_replace("%s", "*", $searchFilter);
	
		$filter=array($this->ldap_filter_group_attribute);
        VERBOSE("ldap_read($dn,$searchFilter,$this->ldap_filter_group_attribute)",__LINE__);
		$sr =@ldap_read($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if(is_numeric(ldap_errno($this->ldap_connection))){
				$error=ldap_errno($this->ldap_connection);
				$errstr=@ldap_err2str($error);
				VERBOSE("`$error` ($errstr",__LINE__);
				$this->ldap_error="SearchGroups:`$error` ($errstr) ".__CLASS__."/".__FUNCTION__." line:".__LINE__;
				
			}
			$this->ok=false;
			$this->CloseConnection();
			return array();
		}

		VERBOSE($searchFilter,__LINE__);

		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
        $this->ldap_filter_group_attribute=strtolower($this->ldap_filter_group_attribute);


		if(!isset($hash[0][$this->ldap_filter_group_attribute]["count"])){$hash[0][$this->ldap_filter_group_attribute]["count"]=0;}
		$MembersCount=$hash[0][$this->ldap_filter_group_attribute]["count"];
		
	
	    VERBOSE("MembersCount:$MembersCount in `$dn`",__LINE__);

		for($i=0;$i<$MembersCount;$i++){
			$MemberName=$hash[0][$this->ldap_filter_group_attribute][$i];
			if(strpos($MemberName, ",")>0){
				$MemberName=$this->MemberInfoByDN($MemberName);
			}
			if(!is_null($MemberName)) {
                $MemberName = trim(strtolower($MemberName));
                if ($MemberName == null) {
                    continue;
                }
                if ($GLOBALS["DEBUG_GROUPS"] > 0) {
                    if (function_exists("WLOG")) {
                        WLOG("Found \"$MemberName\"");
                    }
                }
                $f[$MemberName] = $MemberName;
            }
		}
		$GLOBALS["HashUsersFromGroupDN($dn)"]=$f;
		return $f;
	
	}
	
	private function MemberInfoByDN($dn){
		if(!$this->connect()){return null;}
		$searchFilter="(objectClass=*)";
	
		$filter=array($this->ldap_user_attribute);
		$sr =@ldap_read($this->ldap_connection,$dn,"$searchFilter",$filter,null, null, 10);
		if (!$sr) {
			if($GLOBALS["VERBOSE"]){echo "Bad search $dn / $searchFilter\n";}
			return null;
			
		}
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if(!is_array($hash)){return $dn;}
		if(isset($hash[0][$this->ldap_user_attribute][0])){return $hash[0][$this->ldap_user_attribute][0];}
	}
	
	public function checkcredentials($username,$password){
		if(!$this->connect()){
			
			if($GLOBALS["VERBOSE"]){echo "Connect failed\n";}
			return false;}
		$ldap_filter_users=$this->ldap_filter_users;
		$ldap_filter_users=str_replace("%s", $username, $ldap_filter_users);
		$sr =@ldap_search($this->ldap_connection,$this->ldap_suffix,$ldap_filter_users,array());
		
		if(!$sr){
			if($GLOBALS["VERBOSE"]){echo "$ldap_filter_users Search failed in $this->suffix\n";}
			$this->CloseConnection();
			return false;
		}
		
		$hash=ldap_get_entries($this->ldap_connection,$sr);
		if($hash["count"]==0){
			$this->CloseConnection();
			return false;
			
			
		}
		$this->CloseConnection();
		$userpassword=$hash[0]["userpassword"][0];
		return $this->check_password($password,$userpassword);
		
		
		
	}
	
	private function check_password($password, $hash){
		if($GLOBALS["VERBOSE"]){echo "Check '$hash'\n";}
		$encrypted_password=null;
 		if ($hash == '') {
 			return FALSE;
 		}
 
 		if ($hash[0] != '{'){
 			if ($password == $hash){return TRUE;}
 			return FALSE;
		}
 
 		if (substr($hash,0,7) == '{crypt}'){
 			if (crypt($password, substr($hash,7)) == substr($hash,7)){return TRUE;}
			return FALSE;
 		}
 		
 		
 		
 		if(substr($hash,0,5) == '{MD5}'){
 			if($GLOBALS["VERBOSE"]){echo "Check MD5\n";}
 			$encrypted_password = '{MD5}' . base64_encode(md5( $password,TRUE));
 		}
 		
 		
 	
 		
 		if($encrypted_password==null){
 			if(substr($hash,0,6) == '{SHA1}'){
 				if($GLOBALS["VERBOSE"]){echo "Check SHA1\n";}
 				$encrypted_password = '{SHA1}' . base64_encode(sha1( $password, TRUE ));
 			}
 		}
 		
 		if($encrypted_password==null){
	 		if(substr($hash,0,6) == '{SSHA}'){
	 			$salt = substr(base64_decode(substr($hash,6)),20);
	 			$encrypted_password = '{SSHA}' . base64_encode(sha1( $password.$salt, TRUE ). $salt);
			}
 		}
 		
 		
 		if($encrypted_password==null){
 			if($GLOBALS["VERBOSE"]){echo "Unsupported password hash format";}
 			return FALSE;
 		}
 		
 		if($GLOBALS["VERBOSE"]){echo "Checks against $encrypted_password for \"$hash\"\n";}
 		if ($hash == $encrypted_password){return TRUE;}
 		
 		return FALSE;
 
	}
	
	
	
		
			
	
}