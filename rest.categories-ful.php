<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
include_once(dirname(__FILE__).'/ressources/class.categories.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

isEnabled();


$request_uri=$_SERVER["REQUEST_URI"];
$request_uri=str_replace("/api/rest/category/", "", $request_uri);
$f=explode("/",$request_uri);

foreach ($f as $index=>$params){
    $params=str_replace("?","",$params);
    $f[$index]=$params;
}

if($f[0]=="stats"){STATS_CATEGORIES();exit;}
if($f[0]=="list"){LIST_CATEGORIES();exit;}
if($f[0]=="add"){CREATE_CATEGORIES();exit;}
if($f[0]=="get"){GET_CATEGORIES($f[1]);exit;}
if($f[0]=="compile"){COMPILE_ALL_CATEGORIES($f[1]);exit;}
if($f[0]=="restart"){RESTART_UFDBGUARD();exit;}
if($f[0]=="status"){STATUS_UFDBGUARD();exit;}
if($f[0]=="reload"){RELOAD_UFDBGUARD();exit;}
if($f[0]=="uncategorized"){UNCATEGORIZED_CATEGORIES();exit;}
if(is_numeric($f[0])){CATEGORIZE(intval($f[0]),$f[1],$f[2]);exit;}

RestSyslog("Unable to understand query <{$f[0]}> <{$f[1]}> in $request_uri");

$array["status"]=false;
$array["message"]="Unable to understand query <{$f[0]}> <{$f[1]}> in $request_uri";
$array["category"]=0;

$RestAPi=new RestAPi();
$RestAPi->response(json_encode($array),404);


function isEnabled(){
    $EnableCategoriesRESTFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCategoriesRESTFul"));
    if($EnableCategoriesRESTFul==1){return isAuth();}
    $array["status"]=false;
    $array["message"]="Feature disabled";
    $array["category"]=0;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),407);
    logon_events("FAILED");
    RestSyslog("Feature not enabled.");
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


function isAuth(){
    $RestAPi = new RestAPi();
    $CategoriesRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAPIKey"));
    $SystemRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemRESTFulAPIKey"));



    if(isset($_SERVER["ArticaKey"])){$MyArticaKey=$_SERVER["ArticaKey"];}
    if(isset($_SERVER["HTTP_ARTICAKEY"])){$MyArticaKey=$_SERVER["HTTP_ARTICAKEY"];}
    if($MyArticaKey==null) {
        $array["status"] = false;
        RestSyslog("Authentication Failed ( missing header)");
        $array["message"] = "Authentication Failed ( missing header)";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 407);
        logon_events("FAILED");
        exit;
    }

    if($MyArticaKey==$SystemRESTFulAPIKey){return true;}
    if($MyArticaKey==$CategoriesRESTFulAPIKey){return true;}

     RestSyslog("Authentication Failed");
     $array["status"] = false;
     $array["message"] = "Authentication Failed";
     $array["category"] = 0;
     $RestAPi->response(json_encode($array), 407);
     exit;

}

function RESTART_UFDBGUARD(){
    $RestAPi = new RestAPi();
    $sock=new sockets();
    $sock->getFrameWork("ufdbguard.php?restart-service=yes");
    $array["status"]=true;
    $array["message"]="Success launch in background mode the restart operation.";
    $RestAPi->response(json_encode($array),200);

}
function RELOAD_UFDBGUARD(){
    $RestAPi = new RestAPi();
    $sock=new sockets();
    $sock->getFrameWork("ufdbguard.php?reload-service=yes");
    $array["status"]=true;
    $array["message"]="Success launch in background mode the reload operation.";
    $RestAPi->response(json_encode($array),200);
}

function STATUS_UFDBGUARD(){
    $sock=new sockets();
    $RestAPi = new RestAPi();
    $sock->getFrameWork("ufdbguard.php?services-status=yes");
    $ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/databases/ALL_UFDB_STATUS");
    $array["status"]=true;
    $array["services"]=$ini->_params;
    $array["message"]="Success: Status of the Web-Filtering service";
    $RestAPi->response(json_encode($array),200);
}


function COMPILE_ALL_CATEGORIES($category_id){
	$category_id=intval($category_id);
    $RestAPi = new RestAPi();
	$sock=new sockets();
	if($category_id==0){
		$sock->getFrameWork("ufdbguard.php?compile-all-categories=yes");
		$array["status"]=true;
		$array["message"]="Success launch in background mode the categories compilation.";
		$array["category"]=0;
		$RestAPi->response(json_encode($array),200);
		exit;
	}

	
	$sock->REST_API("/category/compile/$category_id");
	$array["status"]=true;
	$array["message"]="Success launch in background mode the $category_id category compilation.";
	$array["category"]=$category_id;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;
	
	
}

function STATS_CATEGORIES(){

    $sock=new sockets();
    $MAIN=unserialize(base64_decode($sock->GET_INFO("PERSONAL_CATEGORIES_COUNT")));

    $array["status"]=true;
    $array["message"]=null;
    $array["count"]=count($MAIN);
    $array["statistics"]=$MAIN;
    $array["notcategorized_count"]=intval($sock->GET_INFO("PERSONAL_NOT_CATEGORIZED_COUNT"));
    $array["time"]=date("Y-m-d H:i:s",intval($sock->GET_INFO("CATEGORIES_MAINTENANCE_TIME")));
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);


}

function UNCATEGORIZED_CATEGORIES(){
    $WHERE=null;
	$q=new postgres_sql();
    $LIMIT=250;
	if(isset($_POST["MAX"])){
        $LIMIT=intval($_POST["MAX"]);
    }

	if(isset($_POST["query"])){
	    if(trim($_POST["query"])<>null){
            $_POST["query"]="*{$_POST["query"]}*";
            $_POST["query"]=str_replace("**","*",$_POST["query"]);
            $_POST["query"]=str_replace("*","%",$_POST["query"]);
            $WHERE=" WHERE familysite LIKE '{$_POST["query"]}' ";
        }
    }
	
	$results=$q->QUERY_SQL("SELECT * FROM not_categorized{$WHERE} ORDER BY familysite LIMIT $LIMIT");
	if(!$q->ok){
		$array["status"]=false;
		$array["message"]=$q->mysql_error;
		$array["count"]=0;
		$array["websites"]=array();
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}
	
	$c=0;
	$fam=new squid_familysite();
	while ($ligne = pg_fetch_assoc($results)) {
		$sitename=$ligne["familysite"];
		$familysite=$fam->GetFamilySites($sitename);

		if(!preg_match("#.+?\.([a-z]+)$#",$sitename)){
            $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$sitename'");
            continue;
        }
        $ARRAY[$c]=array("SITENAME"=>$sitename,"maindom"=>"$familysite","RQS"=>$ligne["rqs"],"DATE"=>$ligne["zdate"]);
		$c++;
	}
	
	$array["status"]=true;
	$array["message"]=null;
	$array["count"]=$c;
	$array["websites"]=$ARRAY;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	
}

function LIST_CATEGORIES(){
	$sql=LIST_CATEGORIES_SQL();
	$q=new postgres_sql();
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		$array["status"]=false;
		$array["message"]=$q->mysql_error;
		$array["categories"]=array();
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}
	
	while ($ligne = pg_fetch_assoc($results)) {
		$category_id=$ligne["category_id"];
		$categoryname=$ligne["categoryname"];
		$categorykey=$ligne["categorykey"];
		$description=$ligne["category_description"];
		if(preg_match("#^reserved#", $categoryname)){continue;}
		$CATEGORIES[$category_id]["NAME"]=$categoryname;
		$CATEGORIES[$category_id]["KEY"]=$categorykey;
        $CATEGORIES[$category_id]["id"]=$category_id;
        $CATEGORIES[$category_id]["DESCRIPTION"]=$description;
	}
	
	
	if(count($CATEGORIES)==0){
		$array["status"]=false;
		$array["message"]="No category";
        $array["TOTAL"]=count($CATEGORIES);
		$array["categories"]=array();
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		return;
		
	}

	
	$array["status"]=true;
	$array["message"]=count($CATEGORIES)." categories";
    $array["TOTAL"]=count($CATEGORIES);
	$array["categories"]=$CATEGORIES;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	return;
}

function GET_CATEGORIES($sitename){
	
	$q=new mysql_catz();
	$category_id=$q->GET_CATEGORIES($sitename);
	if($category_id>9999){
		$array["status"]=false;
		$array["message"]="Not categorized";
		$array["category_id"]=0;
        $array["category_name"]="unknown";
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),404);
		exit;
	}
	
	if($category_id==0){
		$array["status"]=false;
		$array["message"]="Not categorized";
		$array["category_id"]=0;
        $array["category_name"]="unknown";
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),404);
		exit;		
	}

	$cname=$q->CategoryIntToStr($category_id);
	$array["status"]=true;
	$array["message"]="$sitename categorized as $cname ($category_id)";
	$array["category_id"]=$category_id;
    $array["category_name"]=$cname;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;
	
	
}

function CREATE_CATEGORIES(){
	$ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $CategoriesRESTFulAllowCreate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAllowCreate"));
	$name=$_POST["name"];
	$desc=$_POST["desc"];
	
	if($name==null){
		$array["status"]=false;
		$array["message"]="Category name missing";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}

	if($CategoriesRESTFulAllowCreate==0){
        $array["status"]=false;
        $array["message"]="Cannot create $name permission denied.";
        $array["category_id"]=0;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),503);
        exit;
    }
	
	if($ManageOfficialsCategories==1){
		$array["status"]=false;
		$array["message"]="Cannot create $name Read-only mode, your are managing Public categories.";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
		
	}
	$category=new categories();
	if(!$category->create_category($name, $desc, 0)){
		$array["status"]=false;
		$array["message"]=$category->mysql_error;
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}
	
	$array["status"]=true;
	$array["message"]="Success created $name ($category->last_id)";
	$array["category_id"]=$category->last_id;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	
}

function CATEGORIZE_LIST($category_id,$pattern){
    $MAX=250;

    if(preg_match("#^MAX=([0-9]+)#",$pattern,$re)){
        $MAX=$re[1];
        $pattern=null;
    }


    if(preg_match("#MAX=([0-9]+)#",$pattern,$re)){
        $MAX=$re[1];
        $pattern=str_replace("MAX={$re[1]}","",$pattern);

    }

    if($pattern<>null) {
        $pattern = "*$pattern";
        $pattern = str_replace("**", "*", $pattern);
        $pattern = str_replace("**", "*", $pattern);
        $pattern = trim(str_replace("*", "%", $pattern));
        if($pattern<>"%") {
            $WHERE = "WHERE sitename LIKE '$pattern'";
        }
    }


	$q=new mysql_squid_builder();

	$categorytable=$q->GetCategoryTable($category_id);

	if($categorytable==null){
        RestSyslog("$categorytable Wrong Category ID $category_id (table not found)");
		$array["status"]=false;
		$array["message"]="Wrong Category ID $category_id (table not found)";
		$array["count"]=0;
		$array["sites"]=array();
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}
	
	$sql="SELECT * FROM $categorytable $WHERE ORDER BY sitename LIMIT $MAX";
    RestSyslog($sql);

	$q=new postgres_sql();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
        RestSyslog($q->mysql_error);
		$array["status"]=false;
		$array["message"]=$q->mysql_error;
		$array["count"]=0;
		$array["sites"]=array();
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}
	
	if(pg_num_rows($results)==0){
        RestSyslog("Nothing found $sql");
		$array["status"]=false;
		$array["message"]="Nothing found";
		$array["count"]=0;
		$array["sites"]=array();
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),200);
		exit;
		
	}
	
	while ($ligne = pg_fetch_assoc($results)) {
		$f[]=$ligne["sitename"];
		
	}
	$array["status"]=true;
	$array["message"]="";
	$array["count"]=count($f);
	$array["sites"]=$f;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;
	
	
}

function RestSyslog($text){

    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("RESTAPI", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
}

function CATEGORIZE($category_id,$sitename,$next_category_id){
    $bname=basename(__FILE__);
    RestSyslog("$bname: Command (1):'$category_id' (2):'$sitename' (3):'$next_category_id' [".__LINE__."]");
	$FORCE=false;
	if(strtolower($next_category_id)=="force"){$FORCE=true;$next_category_id=0;}
    RestSyslog("$bname: Command (1):'$category_id' (2):'$sitename' (3):'$next_category_id' Force:$FORCE [".__LINE__."]");

	if(strtolower($next_category_id)=="remove"){
        $q=new mysql_squid_builder();
        $categorytable=$q->GetCategoryTable($category_id);
        if($categorytable==null){
            $array["status"]=false;
            RestSyslog("[$sitename]: Remove failed, Category ID $category_id (table not found)");
            $array["message"]="Remove failed, Category ID $category_id (table not found)";
            $array["category_id"]=$category_id;
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),503);
            exit;
        }

        $pos=new postgres_sql();
        $pos->QUERY_SQL("DELETE FROM $categorytable WHERE sitename='$sitename'");
        if(!$pos->ok){
            RestSyslog("[$sitename]: unable to remove $sitename: $pos->mysql_error");
            $array["status"]=false;
            $array["message"]="$sitename: $pos->mysql_error";
            $array["category_id"]=$category_id;
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),503);
            exit;
        }

        RestSyslog("$bname: [$sitename]: Category $category_id remove site:'$sitename' Success. [".__LINE__."]");
        $array["status"]=true;
        $array["message"]="sitename: $q->finaldomain removed";
        $array["category_id"]=$category_id;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;

    }



	if($sitename=="list"){
        RestSyslog("$bname: List $category_id,$next_category_id [".__LINE__."]");
        RestSyslog("CATEGORIZE_LIST()");
		CATEGORIZE_LIST($category_id,$next_category_id);
		exit;
	}

	$next_category_id=intval($next_category_id);
	$sitename=trim(strtolower($sitename));
	$sitename=str_replace(array("*",";"), "", $sitename);
	$q=new mysql_squid_builder();
	$pos=new postgres_sql();

    if($sitename==null) {
        RestSyslog("$bname: Sitename is null [".__LINE__."]");
        $array["status"] = false;
        $array["message"] = "Sitename is null";
        $array["category_id"] = 0;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 503);
        exit;
    }

    RestSyslog("$bname[$sitename]: categoryid: $category_id , next_category_id: $next_category_id");

	if($next_category_id>0){
		$categorytable=$q->GetCategoryTable($next_category_id);
        RestSyslog("[$sitename]: next_category_id: $categorytable");
		if($categorytable==null){
			$array["status"]=false;
            RestSyslog("$bname[$sitename]: Wrong Category ID $next_category_id (table not found)");
			$array["message"]="Wrong Category ID $next_category_id (table not found)";
			$array["category_id"]=0;
			$RestAPi=new RestAPi();
			$RestAPi->response(json_encode($array),503);
			exit;
		}

        RestSyslog("[$sitename]: DELETE FROM $categorytable WHERE sitename='$sitename'");
		$pos->QUERY_SQL("DELETE FROM $categorytable WHERE sitename='$sitename'");
		$FORCE=true;
		if(!$pos->ok){
            RestSyslog("$bname[$sitename]: $sitename: $pos->mysql_error");
			$array["status"]=false;
			$array["message"]="$sitename: $pos->mysql_error";
			$array["category_id"]=0;
			$RestAPi=new RestAPi();
			$RestAPi->response(json_encode($array),503);
			exit;
		}
		
		if($category_id==0){
            RestSyslog("$bname[$sitename]: Removed $sitename Success from category id $next_category_id");
			$array["status"]=true;
			$array["message"]="Removed $sitename Success from $next_category_id";
			$array["category_id"]=$next_category_id;
			$RestAPi=new RestAPi();
			$RestAPi->response(json_encode($array),200);
			exit;
		}
		
	}
	
	
	if($category_id==0){
        RestSyslog("$bname[$sitename]: Wrong Category ID == 0");
		$array["status"]=false;
		$array["message"]="Wrong Category ID";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}
	if($sitename==null){
        RestSyslog("$bname[$sitename]: Website to categorize is null [".__LINE__."]");
		$array["status"]=false;
		$array["message"]="Website to categorize is null";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}

    RestSyslog("$bname[$sitename]: Find Category name of $category_id [".__LINE__."]");
	$ligne=$pos->mysqli_fetch_array("SELECT categoryname FROM personal_categories where category_id=$category_id");
	$categoryname=trim($ligne["categoryname"]);
    RestSyslog("[$sitename]: Category $category_id ($categoryname)");

	if($categoryname==null){
        RestSyslog("$bname[$sitename]: Category $category_id not found [".__LINE__."]");
		$array["status"]=false;
		$array["message"]="Category $category_id not found";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
		
	}
	
	$zcat=new mysql_catz();
    RestSyslog("$bname[$sitename]: Category $category_id ($categoryname) Force=$FORCE [".__LINE__."]");
	if(!$q->categorize($sitename, $category_id,$FORCE)){
		if($q->last_id>0){
			if($q->last_id==$category_id){
			    $categoryname=$zcat->CategoryIntToStr($category_id);
                RestSyslog("$bname[$sitename]: Already categorized as $category_id ($categoryname)");
				$array["status"]=True;
				$array["message"]="$q->finaldomain Already categorized as $category_id ($categoryname)";
				$array["category_id"]=$category_id;
                $array["categoryname"]=$categoryname;
				$RestAPi=new RestAPi();
				$RestAPi->response(json_encode($array),200);
				exit;
			}
		}
		$array["status"]=false;
		$array["message"]="$q->finaldomain: $q->mysql_error";
		$array["category_id"]=$q->last_id;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}
    RestSyslog("$bname\[{$q->finaldomain}\]: Category $category_id ($categoryname) Force=$FORCE Success.");
	$array["status"]=true;
	$array["message"]="$q->finaldomain success";
	$array["category_id"]=$category_id;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;

}


function LIST_CATEGORIES_SQL(){
	$ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
	if($ManageOfficialsCategories==1){
	    return "SELECT * FROM personal_categories WHERE free_category=0 order by categoryname";
	}

	return "SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 order by categoryname";
	
}



