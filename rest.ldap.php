<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/ressources/class.dansguardian.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAPRestFul"))==0){
$RestAPi=new RestAPi();$RestAPi->response("Disabled feature", 503);exit;}
isAuth();


$request_uri=$_SERVER["REQUEST_URI"];
$request_uri=str_replace("/api/rest/ldap", "", $request_uri);
$f=explode("/",$request_uri);

if(!isset($f[2])){$f[2]=null;}
if(!isset($f[3])){$f[3]=null;}
if(!isset($f[4])){$f[4]=null;}
if(!isset($f[5])){$f[5]=null;}

ldap_switch($f[1],$f[2],$f[3],$f[4],$f[5]);


function isAuth(){
    $RestAPi = new RestAPi();
    $OpenLDAPRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAPIKey"));
    $SystemRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemRESTFulAPIKey"));



    if(isset($_SERVER["ArticaKey"])){$MyArticaKey=$_SERVER["ArticaKey"];}
    if(isset($_SERVER["HTTP_ARTICAKEY"])){$MyArticaKey=$_SERVER["HTTP_ARTICAKEY"];}
    if($MyArticaKey==null) {
        $array["status"] = false;
        $array["message"] = "Authentication Failed ( missing header)";
        $array["category"] = 0;
        logon_events("OK");
        $RestAPi->response(json_encode($array), 407);
        exit;
    }

    if($MyArticaKey==$SystemRESTFulAPIKey){return true;}
    if($MyArticaKey==$OpenLDAPRestFulApi){return true;}


    $array["status"] = false;
    $array["message"] = "Authentication Failed";
    $array["category"] = 0;
    $RestAPi->response(json_encode($array), 407);
    logon_events("FAILED");
    exit;

}
function logon_events($succes){
    if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
    $logFile="/var/log/artica-webauth.log";
    $date=date('Y-m-d H:i:s');
    $f = fopen($logFile, 'a');
    fwrite($f, "$date $IPADDR $succes\n");
    fclose($f);
}





function ldap_switch($function,$function1,$function2,$function3=null,$function4=null){
	//echo "ldap_switch:: function=$function function1=$function1, function2=$function2 function3=$function3 function4=$function4\n";
	if(intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP"))==0){
		$RestAPi=new RestAPi();$RestAPi->response("$function/function1=$function1/function2=$function2/function3=$function3/$function4 Error OpenLDAP not activated",501);
		die();
	}
	
	if($function=="organization"){ ldap_organization_switch($function1,$function2,$function3,$function4);return;}
	if($function=="member"){ldap_member_switch($function1,$function2,$function3,$function4);}
		
	$array["status"]=false;
	$array["message"]="Unknown function $function [".__LINE__."]";
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	
	
}

function ldap_member_switch($uid,$function2,$function3,$function4){
	if($function2=="delete"){ldap_member_delete($uid);return;}
	if($function2=="udpate"){ldap_member_update($uid);return;}
	if($function2==null){ldap_member_zoom($uid);return;}
	
	$RestAPi=new RestAPi();$RestAPi->response("bad function {$function2}",501);
}

function ldap_member_delete($uid){
	$ct=new user($uid);
	$ou=$ct->ou;
	$gpid=$ct->group_id;
	$DisplayName=$ct->DisplayName;
	if(!$ct->DeleteUser()){$RestAPi=new RestAPi();$RestAPi->response("Error removing $uid",501);die();}
	$array["status"]=true;
	$array["organization"]=$ou;
	$array["member"]=$uid;
	$array["guid"]=$gpid;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}

function ldap_organization_switch($ou,$function2=null,$function3=null,$function4=null){
	//ou=$ou function2=$function2 function3=$function3 function4=$function4<br>";
	if($function2=="members"){ldap_organization_members($ou);return;}
	if($function2=="groups"){
		//echo "ldap_organization_groups_switch($ou,$function3,$function4) <br>";
		ldap_organization_groups_switch($ou,$function3,$function4);return;}
	
	if($ou=="list"){ldap_organization_list();return;}
	if($ou=="create"){ldap_organization_create();return;}
	if($ou=="delete"){ldap_organization_delete($function2);return;}
	
	
	$array["status"]=false;
	$array["message"]="Unknown function $function2 or $ou [".__LINE__."]";
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	

	
	
}
function ldap_organization_groups_switch($ou,$function3,$function4){
	if($function3=="list"){ldap_organization_groups_list($ou);return;}
	if($function3=="create"){ldap_organization_groups_create($ou);return;}
	if($function3=="delete"){ldap_organization_groups_delete($ou,$function4);return;}
	if(is_numeric($function3)){
		if($function4=="users"){ldap_organization_groups_members($ou,$function3);return;}
		if($function4=="add"){ldap_organization_groups_addmember($ou,$function3);return;}
		if($function4=="unlink"){ldap_organization_groups_delmember($ou,$function3);return;}
		$ct=new user($function4);
		if($ct->UserExists){ldap_organization_groups_linkmember($ou,$function3,$function4);return;}
	}
	
	$array["status"]=false;
	$array["message"]="Unknown function $function3";
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	
	
}

function ldap_organization_list(){
	$ldap=new clladp();
	$hash_get_ou=$ldap->hash_get_ou();
	$array["status"]=true;
	$array["organizations"]=$hash_get_ou;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}
function ldap_organization_create(){
	$ldap=new clladp();
	if(!isset($_POST["name"])){
		$RestAPi=new RestAPi();$RestAPi->response("Error name field not posted",501);
		return;
	}
	
	
	if(!$ldap->AddOrganization($_POST["name"])){
		$RestAPi=new RestAPi();$RestAPi->response("Error $ldap->ldap_last_error",501);
		die();
	}
	$array["status"]=true;
	$array["organization"]=$ldap->RealOU;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}

function ldap_organization_members($ou){
	$ldap=new clladp();
	$array["status"]=true;
	$array["organization"]=$ou;
	$array["members"]=$ldap->hash_users_ou($ou);
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}
function ldap_organization_groups_addmember($ou,$gidNumber){
	$ct=new user();
	$ct->uid=$_POST["uid"];
	$ct->DisplayName=url_decode_special_tool($_POST["DisplayName"]);
	$ct->givenName=url_decode_special_tool($_POST["givenName"]);
	$ct->sn=url_decode_special_tool($_POST["name"]);
	$ct->password=url_decode_special_tool($_POST["password"]);
	$ct->group_id=$gidNumber;
	$ct->ou=$ou;
	if(!$ct->add_user()){$RestAPi=new RestAPi();$RestAPi->response("Error $ct->ldap_error",501);die();}
	$array["status"]=true;
	$array["organization"]=$ou;
	$array["guid"]=$ct->group_id;
	$array["member"]=$ct->uid;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	
}
function ldap_member_update($uid){
	$ct=new user($uid);
	$ct->DisplayName=url_decode_special_tool($_POST["DisplayName"]);
	$ct->givenName=url_decode_special_tool($_POST["givenName"]);
	$ct->sn=url_decode_special_tool($_POST["name"]);
	$ct->password=url_decode_special_tool($_POST["password"]);
	if(!$ct->add_user()){$RestAPi=new RestAPi();$RestAPi->response("Error $ct->ldap_error",501);die();}
	$array["status"]=true;
	$array["organization"]=$ct->ou;
	$array["guid"]=$ct->group_id;
	$array["member"]=$ct->uid;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}
function ldap_organization_groups_linkmember($ou,$gid,$uid){
	$gp=new groups($gid);
	if(!$gp->user_add_to_group($uid)){$RestAPi=new RestAPi();$RestAPi->response("Error",501);die();}
	$array["status"]=true;
	$array["organization"]=$ou;
	$array["guid"]=$gid;
	$array["member"]=$uid;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}
function ldap_organization_groups_delmember($ou,$gid){
	$gp=new groups($gid);
	if(!$gp->DeleteUserFromThisGroup($_POST["uid"])){$RestAPi=new RestAPi();$RestAPi->response("Error",501);die();}
	$array["status"]=true;
	$array["organization"]=$ou;
	$array["guid"]=$gid;
	$array["member"]=$_POST["uid"];
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}

function ldap_member_zoom($uid){
	$ct=new user($uid);
	$array["status"]=true;
	$array["organization"]=$ct->ou;
	$array["guid"]=$ct->group_id;
	$array["member"]=$ct->uid;
	$array["details"]=$ct->Dump;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	
}

function ldap_organization_groups_list($ou){
	$ldap=new clladp();
	$array["status"]=true;
	$array["organization"]=$ou;
	
	$HASH=$ldap->hash_groups($ou);
	$c=0;
	foreach ($HASH as $GroupName=>$array){
		$gp=array();
		$gid=$array["gid"];
		$array["groups"][$gid]["Name"]=$GroupName;
		$array["groups"][$gid]["gid"]=$gid;
		$array["groups"][$gid]["description"]=utf8_encode($array["description"]);
		$c++;
		
	}
	
	$array["groups"]["count"]=$c;
	
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}
function ldap_organization_groups_create($ou){
	$ct=new groups();
	if(!$ct->add_new_group($_POST["name"],$ou)){$RestAPi=new RestAPi();$RestAPi->response("Error",501);die();}
	$array["status"]=true;
	$array["organization"]=$ou;
	$array["guid"]=$ct->generated_id;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}
function ldap_organization_groups_delete($ou,$gidNumber){
	$ct=new groups($gidNumber);
	if($ct->groupName==null){$RestAPi=new RestAPi();$RestAPi->response("$gidNumber no such group",501);die();}
	$ct->Delete();
	$array["status"]=true;
	$array["organization"]=$ou;
	$array["guid"]=$gidNumber;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}
function ldap_organization_groups_members($ou,$gidNumber){
	$ct=new groups($gidNumber);
	if($ct->groupName==null){$RestAPi=new RestAPi();$RestAPi->response("$gidNumber no such group",501);die();}
	$array["status"]=true;
	$array["organization"]=$ou;
	$array["guid"]=$gidNumber;
	$array["count"]=count($ct->members);
	$array["members"]=$ct->members;
}

function ldap_organization_delete($ou){
	$ldap=new clladp();
	if(!$ldap->ldap_delete("ou=$ou,dc=organizations,$ldap->suffix",true)){
		$RestAPi=new RestAPi();$RestAPi->response("Error $ldap->ldap_last_error",501);
		die();
	}
	$array["status"]=true;
	$array["organization"]=$ou;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	
}

function webfilter_switch($function,$function1,$function2){
	$sock=new sockets();
	if($function=="service"){
		if($function1=="status"){
			$sock->getFrameWork("ufdbguard.php?services-status=yes");
			$ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/databases/ALL_UFDB_STATUS");
			$array["status"]=true;
			$array["task"]=$function1;
			$array["SERVICES"]=$ini->_params;
			$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
			return;
		}
		
		
		
		$datas=$sock->getFrameWork("ufdbguard.php?service-cmds=$function1");
		$array["status"]=true;
		$array["task"]=$function1;
		$array["events"]=$datas;
		$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
		return;
	
	}
	$RestAPi=new RestAPi();$RestAPi->response("bad function {$function}",501);
}




function categorycreate(){
	$q=new mysql_squid_builder();
	$_POST["category"]=url_decode_special_tool($_POST["category"]);
	$_POST["category_text"]=url_decode_special_tool($_POST["category_text"]);
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$html=new htmltools_inc();
	
	$_POST["category"]=strtolower($html->StripSpecialsChars($_POST["category"]));
	$_POST["category_text"]=mysql_escape_string2($_POST["category_text"]);
	$sql="SELECT category FROM personal_categories WHERE category='{$_POST["category"]}'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if($ligne["category"]<>null){
		$RestAPi=new RestAPi();$RestAPi->response("{$_POST["category"]} already exists",500);
		return;
	}
	
	$sql="INSERT IGNORE INTO personal_categories (category,category_description,master_category,PublicMode) VALUES ('{$_POST["category"]}','{$_POST["category_text"]}','','0');";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		$RestAPi=new RestAPi();$RestAPi->response("$q->mysql_error",500);
		return;
	}
	
	$array["status"]=true;
	$array["category"]=$_POST["category"];
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	
}

function categorydelete(){
	$category=trim($_POST["category"]);
	if(strlen($category)==0){
		$array["status"]=false;
		$array["category"]=$category;
		$array["events"]="No defined category";
		$RestAPi=new RestAPi();$RestAPi->response("No defined category",500);
		return;
	}
	$q=new mysql_squid_builder();
	if(!$q->DELETE_CATEGORY($category)){
		$array["status"]=false;
		$array["category"]=$category;
		$array["events"]="Failed to delete";
		$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),500);
		return;
	}
	
	$array["status"]=true;
	$array["category"]=$category;
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	
}

function categorypopuplate($category){
	
	$q=new mysql_squid_builder();
	$table=$q->cat_totablename($category);
	if($q->TABLE_EXISTS($table)){
		$q->QUERY_SQL("TRUNCATE TABLE $table");
		if(!$q->ok){
			$array["status"]=false;
			$array["category"]=$category;
			$array["events"]=$q->mysql_error;
			$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),500);
			return;
		}
	}
	
	if(!$q->free_categorizeSave($_POST["items"],$category,$_POST["ForceCat"],$_POST["ForceExt"],1)){
		$array["status"]=false;
		$array["category"]=$category;
		$array["events"]=@implode("\n", $GLOBALS["free_categorizeSave"]);
		$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),500);
		return;
	}
	
	$array["status"]=true;
	$array["category"]=$category;
	$array["events"]=@implode("\n", $GLOBALS["free_categorizeSave"]);
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	
	
}

function categoryitems($category){
	$q=new mysql_squid_builder();
	$table=$q->cat_totablename($category);
	if(!$q->TABLE_EXISTS($table)){
		$array["status"]=false;
		$array["category"]=$category;
		$array["events"]="no such table $table";
		$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),500);
		return;
	}
	
	$sql="SELECT pattern FROM $table ORDER BY pattern";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){if($q->mysql_error<>null){
		$array["status"]=false;
		$array["category"]=$category;
		$array["events"]=$q->mysql_error;
		$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),500);
		return;
	}}
	
	if(mysqli_num_rows($results)==0){
		$array["total"]=0;
		$array["category"]=$category;
		$array["items"]=array();
		$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	}
	
	$array["total"]=mysqli_num_rows($results);
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$categorykey=$ligne["category"];
		$table=$q->cat_totablename($categorykey);
		$array["items"][]=$ligne["pattern"];
	
	}
	
	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);	
}

function categorylist(){
	
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();
	$OnlyPersonal=1;
	$rp=200;
	
	if(!$q->BD_CONNECT()){$RestAPi=new RestAPi();$RestAPi->response("MySQL Error",500);}
	$sql="SELECT * FROM personal_categories";
	$total = $q->COUNT_ROWS("personal_categories");
	$sql="SELECT * FROM personal_categories ORDER BY category";
	$results = $q->QUERY_SQL($sql);
	
	
	if(!$q->ok){if($q->mysql_error<>null){
		$RestAPi=new RestAPi();$RestAPi->response("$q->mysql_error",500);
	}}
		
		
		
	if(mysqli_num_rows($results)==0){
		$array["total"]=0;
		$array["categories"]=array();
		$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
	}
	
	$array["total"]=mysqli_num_rows($results);
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$categorykey=$ligne["category"];
		$table=$q->cat_totablename($categorykey);
		$array["categories"][$categorykey]=array(
				"category"=>$ligne["category"],
				"description"=>$ligne["category_description"],
				"items"=>$q->COUNT_ROWS($table),
				"table"=>$table
				
				);
		
	}

	$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),200);
}