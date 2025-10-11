<?php

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');


$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
    $tpl=new templates();
    $alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
    echo "alert('$alert');";
    die();
}

if(isset($_POST["duplicate-from"])){duplicate_rule();exit;}


js();


function js(){
    $page=CurrentPageName();
    $tpl=new templates();
    $t2=time();
    $t=$_GET["t"];
    $rulefrom=$_GET["from"];
    $pageTable=$_GET["page"];
    if(is_numeric($rulefrom)){
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $sql="SELECT groupname FROM webfilter_rules WHERE ID=$rulefrom";
        $results=$q->QUERY_SQL($sql);
        $ligne=$q->mysqli_fetch_array($sql);
        $tmpname=$ligne["groupname"]." (copy)";
        $tmpname=addslashes($tmpname);
        $tmpname=replace_accents($tmpname);
    }

    if(isset($_GET["default-rule"])){
        $rulefrom="default";
        $tmpname=$tpl->javascript_parse_text("{default} (copy)");
        $tmpname=replace_accents($tmpname);
    }
    header("content-type: application/x-javascript");
    $jafter="LoadAjax('table-loader-ufdbrules-service','$pageTable?table=yes')";
    $ask=$tpl->javascript_parse_text("{duplicate_the_ruleid_give_name}");
    $html="
		var x_Duplicaterule$t2= function (obj) {
			var res=obj.responseText;
			if (res.length>0){alert(res);$jafter}
			$('#flexRT$t').flexReload();
		}
	
	
		function Duplicaterule$t2(){
			var rulename=prompt('$ask $rulefrom','$tmpname');
			if(!rulename){return;}
			 var XHR = new XHRConnection();
		     XHR.appendData('duplicate-from', '$rulefrom');
		     var pp=encodeURIComponent(rulename);
		     XHR.appendData('duplicate-name', pp);
		     XHR.appendData('nextpage', '$pageTable');
		     XHR.sendAndLoad('$page', 'POST',x_Duplicaterule$t2); 
		
		}
		
	
	Duplicaterule$t2();";
    echo $html;
}

function duplicate_default_rule(){
    $_POST["duplicate-name"]=url_decode_special_tool($_POST["duplicate-name"]);
    $idname=addslashes($_POST["duplicate-name"]);
    $sock=new sockets();
    $ligne=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/DansGuardianDefaultMainRule")));
    $ligne["groupmode"]=1;
    $ligne["enabled"]=1;
    $ligne["embeddedurlweight"]=0;
    if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=1;}
    if(!is_numeric($ligne["AllSystems"])){$ligne["AllSystems"]=0;}
    if(!is_numeric($ligne["UseSecurity"])){$ligne["UseSecurity"]=0;}
    if(!is_numeric($ligne["blockdownloads"])){$ligne["blockdownloads"]=0;}
    if(!is_numeric($ligne["naughtynesslimit"])){$ligne["naughtynesslimit"]=0;}
    if(!is_numeric($ligne["searchtermlimit"])){$ligne["searchtermlimit"]=0;}
    if(!is_numeric($ligne["bypass"])){$ligne["bypass"]=0;}
    if(!is_numeric($ligne["deepurlanalysis"])){$ligne["deepurlanalysis"]=0;}
    if(!is_numeric($ligne["UseExternalWebPage"])){$ligne["UseExternalWebPage"]=0;}
    if(!is_numeric($ligne["sslcertcheck"])){$ligne["sslcertcheck"]=0;}
    if(!is_numeric($ligne["sslmitm"])){$ligne["sslmitm"]=0;}
    if(!is_numeric($ligne["GoogleSafeSearch"])){$ligne["GoogleSafeSearch"]=1;}




    $ligne["endofrule"]="any";
    $f["groupmode"]=true;
    $f["embeddedurlweight"]=true;
    $f["bypass"]=true;
    $f["enabled"]=true;
    $f["BypassSecretKey"]=true;
    $f["endofrule"]=true;
    $f["blockdownloads"]=true;
    $f["naughtynesslimit"]=true;
    $f["searchtermlimit"]=true;
    $f["deepurlanalysis"]=true;
    $f["sslcertcheck"]=true;

    $f["bypass"]=true;
    $f["deepurlanalysis"]=true;
    $f["UseExternalWebPage"]=true;
    $f["AllSystems"]=true;
    $f["ExternalWebPage"]=true;
    $f["freeweb"]=true;
    $f["sslcertcheck"]=true;
    $f["UseSecurity"]=true;
    $f["blockdownloads"]=true;
    $f["sslmitm"]=true;
    $f["zOrder"]=true;
    $f["GoogleSafeSearch"]=true;
    $f["TimeSpace"]=true;
    $f["TemplateError"]=true;
    $f["RewriteRules"]=true;
    $idname=addslashes($_POST["duplicate-name"]);
    $fields[]="`groupname`";
    $values[]="'".$idname."'";
    foreach ($f as $key=>$none){
        $fields[]="`$key`";
        $values[]="'".addslashes($ligne[$key])."'";

    }



    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $sql="INSERT INTO webfilter_rules (".@implode(",", $fields).")
	VALUES (".@implode(",", $values).")";

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return;}
    $newruleid=$q->last_id;
    if($newruleid<1){echo "Failed";return;}

    $sql="SELECT * FROM webfilter_assoc_groups WHERE webfilter_id=0";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $groupid=$ligne["group_id"];
        $md5=md5("$newruleid$groupid");
        $sql="INSERT INTO webfilter_assoc_groups (zMD5,webfilter_id,group_id) VALUES('$md5','$newruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}

    }

    $sql="SELECT * FROM webfilter_blklnk WHERE webfilter_ruleid=0";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $groupid=$ligne["webfilter_blkid"];
        $md5=md5(microtime());
        $blacklist=intval($ligne['blacklist']);
        $sql="INSERT INTO webfilter_blklnk (zmd5,webfilter_blkid,webfilter_ruleid,blacklist) VALUES('$md5','$groupid','$newruleid',$blacklist)";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}

    }

    $sql="SELECT * FROM webfilter_blks WHERE webfilter_id=0";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $category=$ligne["category"];
        $category=addslashes($category);
        $q->QUERY_SQL("INSERT INTO webfilter_blks (webfilter_id,	modeblk,category) VALUES('$newruleid','{$ligne["modeblk"]}','$category')");
        if(!$q->ok){echo $q->mysql_error;return;}

    }
    $sql="SELECT * FROM webfilter_bannedexts WHERE ruleid=0";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $description=addslashes($ligne["description"]);
        $md5=md5("$newruleid{$ligne["ext"]}");
        $enabled=$ligne["enabled"];
        $q->QUERY_SQL("INSERT INTO webfilter_bannedexts (enabled,zmd5,ext,description,ruleid) VALUES($enabled,'$md5','{$ligne["ext"]}','$description',$newruleid);");
        if(!$q->ok){echo $q->mysql_error;return;}
    }

    $tpl=new templates();
    echo $tpl->javascript_parse_text("{success}");

}


function duplicate_rule(){
    $_POST["duplicate-name"]=url_decode_special_tool($_POST["duplicate-name"]);
    $idfrom=$_POST["duplicate-from"];
    if($idfrom=="default"){duplicate_default_rule();exit;}
    $idname=addslashes($_POST["duplicate-name"]);

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $sql="SELECT * FROM webfilter_rules WHERE ID=$idfrom";
    //$results=$q->QUERY_SQL($sql);
    $cname=$q->FIELDS_LIST_FOR_QUERY('webfilter_rules');
    $len = count($cname);
    $ligne=$q->mysqli_fetch_array($sql);

    for ($i = 0; $i < $len; $i++) {
        $name = $cname[$i];
        if($name=="ID"){continue;}
        if($name=="embeddedurlweight"){
            if(!is_numeric($ligne[$name])){$ligne[$name]=0;}
        }
        $FIELDZ[$name]=true;
        $fields[]="`$name`";
        if($name=="groupname"){$ligne[$name]=$idname;}
        $values[]="'".addslashes($ligne[$name])."'";
    }

    if(!isset($FIELDZ["embeddedurlweight"])){
        $fields[]="`embeddedurlweight`";
        $values[]="'0'";
    }

    $sql="INSERT INTO webfilter_rules (".@implode(",", $fields).") 
	VALUES (".@implode(",", $values).")";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "MySQL Error\n".__FUNCTION__."\nIn line:".__LINE__."\n".$q->mysql_error;
        return;
    }
    $newruleid=$q->last_id;
    if($newruleid<1){echo "Failed";return;}

    $sql="SELECT * FROM webfilter_assoc_groups WHERE webfilter_id=$idfrom";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $groupid=$ligne["group_id"];
        $md5=md5("$newruleid$groupid");
        $sql="INSERT INTO webfilter_assoc_groups (zMD5,webfilter_id,group_id) VALUES('$md5','$newruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}

    }

    $sql="SELECT * FROM webfilter_blklnk WHERE webfilter_ruleid=$idfrom";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $groupid=$ligne["webfilter_blkid"];
        $md5=md5(microtime());
        $blacklist=intval($ligne['blacklist']);
        $sql="INSERT INTO webfilter_blklnk (zmd5,webfilter_blkid,webfilter_ruleid,blacklist) VALUES('$md5','$groupid','$newruleid',$blacklist)";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}

    }

    $sql="SELECT * FROM webfilter_blks WHERE webfilter_id=$idfrom";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $category=$ligne["category"];
        $category=addslashes($category);
        $q->QUERY_SQL("INSERT INTO webfilter_blks (webfilter_id,	modeblk,category) VALUES('$newruleid','{$ligne["modeblk"]}','$category')");
        if(!$q->ok){echo $q->mysql_error;return;}

    }
    $sql="SELECT * FROM webfilter_bannedexts WHERE ruleid=$idfrom";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $description=addslashes($ligne["description"]);
        $md5=md5("$newruleid{$ligne["ext"]}");
        $enabled=$ligne["enabled"];
        $q->QUERY_SQL("INSERT INTO webfilter_bannedexts (enabled,zmd5,ext,description,ruleid) VALUES($enabled,'$md5','{$ligne["ext"]}','$description',$newruleid);");
        if(!$q->ok){echo $q->mysql_error;return;}
    }
    $tpl=new templates();
    echo $tpl->javascript_parse_text("{success}");



}


