<?php

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once(dirname(__FILE__)."/ressources/class.ufdbrules.meta.inc");


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

// Pentest 2026-11-04 : duplicate-from et duplicate-name (et les valeurs recopiees de la regle) etaient
// concatenes dans la requete ; addslashes() n'echappe pas l'apostrophe pour SQLite.
function fwdup_esc($v):string{
    return str_replace("'","''",strval($v));
}
// zOrder d'une regle LOCALE creee par copie : apres la derniere regle Artica Meta.
// null = regles Meta illisibles (echec ferme : pas de copie).
function fwdup_local_zorder($zOrder){
    $max=ufdbrules_meta_max_zorder();
    if($max===null){return null;}
    $zOrder=intval($zOrder);
    if($zOrder<=$max){$zOrder=$max+1;}
    return $zOrder;
}
// Copie LOCALE (metaid vide) d'un groupe source et de ses membres. Rend le nouvel ID, 0 en echec.
function fwdup_local_group($q,$groupid):int{
    $rows=$q->QUERY_SQL_PARAMS("SELECT groupname,localldap,enabled,gpid,description,dn,settings FROM webfilter_group WHERE ID=?",array(intval($groupid)));
    if(!$q->ok || !isset($rows[0])){return 0;}
    $g=$rows[0];
    $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_group (groupname,localldap,enabled,gpid,description,dn,settings) VALUES (?,?,?,?,?,?,?)",
        array(strval($g["groupname"]),intval($g["localldap"]),intval($g["enabled"]),intval($g["gpid"]),
            strval($g["description"]),strval($g["dn"]),strval($g["settings"])));
    if(!$q->ok){return 0;}
    $newid=intval($q->last_id);
    if($newid<1){return 0;}
    // lib_sqlite ouvre une connexion PDO par requete : un BEGIN/COMMIT ne couvrirait pas les
    // appels suivants. Compensation : en cas d'echec, le groupe copie et ses membres deja
    // inseres sont supprimes (pas de groupe orphelin ni de copie partielle).
    $members=$q->QUERY_SQL_PARAMS("SELECT pattern,enabled,membertype FROM webfilter_members WHERE groupid=?",array(intval($groupid)));
    if(!$q->ok){fwdup_drop_group($q,$newid);return 0;}
    if(is_array($members)){
        foreach($members as $m){
            $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_members (pattern,enabled,groupid,membertype) VALUES (?,?,?,?)",
                array(strval($m["pattern"]),intval($m["enabled"]),$newid,intval($m["membertype"])));
            if(!$q->ok){fwdup_drop_group($q,$newid);return 0;}
        }
    }
    return $newid;
}
// Retrait d'une copie de groupe inachevee (membres puis groupe).
function fwdup_drop_group($q,$newid){
    $err=$q->mysql_error;
    $q->QUERY_SQL_PARAMS("DELETE FROM webfilter_members WHERE groupid=?",array(intval($newid)));
    $q->QUERY_SQL_PARAMS("DELETE FROM webfilter_group WHERE ID=?",array(intval($newid)));
    writelogs("duplicate: copy of a Meta group failed ($err), group #$newid removed",__FUNCTION__,__FILE__,__LINE__);
}
function duplicate_default_rule(){
    $_POST["duplicate-name"]=url_decode_special_tool($_POST["duplicate-name"]);
    $idname=addslashes($_POST["duplicate-name"]);
    $sock=new sockets();
    $ligne=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule")));
    $ligne["groupmode"]=1;
    $ligne["enabled"]=1;
    $ligne["embeddedurlweight"]=0;
    if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=1;}
    // La copie est une regle LOCALE : jamais avant les regles Artica Meta.
    $ligne["zOrder"]=fwdup_local_zorder($ligne["zOrder"]);
    if($ligne["zOrder"]===null){ echo ufdbrules_meta_text(); return; }
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
    $values[]="'".fwdup_esc($_POST["duplicate-name"])."'";
    foreach ($f as $key=>$none){
        $fields[]="`$key`";
        $values[]="'".fwdup_esc($ligne[$key])."'";

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
        $groupid=intval($ligne["group_id"]);
        $md5=md5("$newruleid$groupid");
        $sql="INSERT INTO webfilter_assoc_groups (zMD5,webfilter_id,group_id) VALUES('$md5','$newruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}

    }

    $sql="SELECT * FROM webfilter_blklnk WHERE webfilter_ruleid=0";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $groupid=intval($ligne["webfilter_blkid"]);
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
        $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_blks (webfilter_id,	modeblk,category) VALUES(?,?,?)",array(intval($newruleid),strval($ligne["modeblk"]),strval($category)));
        if(!$q->ok){echo $q->mysql_error;return;}

    }
    $sql="SELECT * FROM webfilter_bannedexts WHERE ruleid=0";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $description=strval($ligne["description"]);
        $md5=md5("$newruleid{$ligne["ext"]}");
        $enabled=intval($ligne["enabled"]);
        $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_bannedexts (enabled,zmd5,ext,description,ruleid) VALUES($enabled,?,?,?,?);",array($md5,strval($ligne["ext"]),$description,intval($newruleid)));
        if(!$q->ok){echo $q->mysql_error;return;}
    }

    $tpl=new templates();
    echo $tpl->javascript_parse_text("{success}");

}


function duplicate_rule(){
    $_POST["duplicate-name"]=url_decode_special_tool($_POST["duplicate-name"]);
    $idfrom=$_POST["duplicate-from"];
    if($idfrom=="default"){duplicate_default_rule();exit;}
    $idfrom=intval($idfrom);
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
        // La copie d'une regle Artica Meta est LOCALE (metaid vide), placee apres les regles Meta.
        if($name=="metaid"){$ligne[$name]="";}
        if($name=="zOrder"){
            $ligne[$name]=fwdup_local_zorder($ligne[$name]);
            if($ligne[$name]===null){ echo ufdbrules_meta_text(); return; }
        }
        $FIELDZ[$name]=true;
        $fields[]="`".preg_replace('/[^A-Za-z0-9_]/','',$name)."`";
        if($name=="groupname"){$ligne[$name]=$_POST["duplicate-name"];}
        $values[]="'".fwdup_esc($ligne[$name])."'";
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
        $groupid=intval($ligne["group_id"]);
        // Un groupe Artica Meta n'est jamais lie a une regle locale : la copie recoit une
        // copie LOCALE du groupe (et de ses membres).
        if(ufdbrules_meta_locked_group($groupid)){
            $groupid=fwdup_local_group($q,$groupid);
            if($groupid<1){echo ufdbrules_meta_text();return;}
        }
        $md5=md5("$newruleid$groupid");
        $sql="INSERT INTO webfilter_assoc_groups (zMD5,webfilter_id,group_id) VALUES('$md5','$newruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}

    }

    $sql="SELECT * FROM webfilter_blklnk WHERE webfilter_ruleid=$idfrom";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $groupid=intval($ligne["webfilter_blkid"]);
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
        $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_blks (webfilter_id,	modeblk,category) VALUES(?,?,?)",array(intval($newruleid),strval($ligne["modeblk"]),strval($category)));
        if(!$q->ok){echo $q->mysql_error;return;}

    }
    $sql="SELECT * FROM webfilter_bannedexts WHERE ruleid=$idfrom";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $description=strval($ligne["description"]);
        $md5=md5("$newruleid{$ligne["ext"]}");
        $enabled=intval($ligne["enabled"]);
        $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_bannedexts (enabled,zmd5,ext,description,ruleid) VALUES($enabled,?,?,?,?);",array($md5,strval($ligne["ext"]),$description,intval($newruleid)));
        if(!$q->ok){echo $q->mysql_error;return;}
    }
    $tpl=new templates();
    echo $tpl->javascript_parse_text("{success}");



}


