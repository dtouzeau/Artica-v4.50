<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["link-group-js"])){link_group_js();exit;}
if(isset($_GET["link-group-popup"])){link_group_popup();exit;}
if(isset($_GET["link-group-choose"])){link_group_choose();exit;}
if(isset($_GET["link-group-step2"])){link_group_choose_step2();exit;}
if(isset($_POST["link1"])){link_group_choose_save();exit;}
if(isset($_POST["link2"])){link_group_choose_final();exit;}
if(isset($_GET["unlink-js"])){group_rmunlink();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["none"])){exit();}
if(isset($_GET["link-dump-js"])){link_dump_js();exit;}
if(isset($_GET["link-dump-popup"])){link_dump_popup();exit;}

js();


function js(){
	$ID=intval($_GET["ID"]);
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$tpl->js_dialog("{sources} $rulename", "$page?popup=$ID");
}
function link_group_js(){
	$ID=intval($_GET["link-group-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$tpl->js_dialog("{link_group} $rulename", "$page?link-group-popup=$ID");
}
function link_dump_js(){
	$ID=intval($_GET["link-dump-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$tpl->js_dialog2("$rulename {members}", "$page?link-dump-popup=$ID");
	
}
function link_dump_popup(){
	$ID=intval($_GET["link-dump-popup"]);
	$sock=new sockets();
	$tpl=new template_admin();
	$sock->getFrameWork("ufdbguard.php?dump-members=$ID");
	$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/external_acl_squid_ldap.dump.$ID");
        $t=time();

    $html[]="<div class='alert alert-info'>{ufdb_dump_users_howto}</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $f=explode("\n",$data);

    foreach ($f as $member) {
        $member=trim($member);
        if($member==null){continue;}
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $html[] = "<tr class='$TRCLASS' id='nn'>";
        $html[] = "<td><i class=\"fas fa-user-shield\"></i>&nbsp;$member</td>";

        $html[] = "</tr>";
    }




$html[]="</tbody>";
$html[]="<tfoot>";

$html[]="<tr>";
$html[]="<td colspan='5'>";
$html[]="<ul class='pagination pull-right'></ul>";
$html[]="</td>";
$html[]="</tr>";
$html[]="</tfoot>";
$html[]="</table>";
$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
	</script>";

echo $tpl->_ENGINE_parse_body($html);

}


function group_rmunlink(){
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$md=$_GET["md"];
	$ID=intval($_GET["unlink-js"]);
	$q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE ID=$ID");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	header("content-type: application/x-javascript");
	$js[]="LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";
	$js[]="\$('#$md').remove()";
	echo @implode("\n", $js);
	
}

function delete_js(){
	$ruleid=intval($_GET["ruleid"]);
	$gpid=intval($_GET["gpid"]);
	$assoc=intval($_GET["delete-js"]);
	$md=$_GET["md"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ruleid";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	
	$sql="SELECT groupname FROM webfilter_group WHERE ID=$gpid";
	$ligne=$q->mysqli_fetch_array($sql);
	$GroupName=$ligne["groupname"];
	$text="{delete_group} $GroupName {in_database} <br>{and} $GroupName  {in_rule} $rulename<br>{and} {in_all_rules} ?";
	$jsafter="Loadjs('$page?delete-confirm=$assoc&ruleid=$ruleid&gpid=$gpid&md=$md')";
	$tpl->js_confirm_delete($text, "none", "none",$jsafter,true);
}

function delete_confirm(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$ruleid=intval($_GET["ruleid"]);
	$gpid=intval($_GET["gpid"]);
	$assoc=intval($_GET["delete-confirm"]);
	$md=$_GET["md"];
	
	$q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE group_id=$gpid");
	$q->QUERY_SQL("DELETE FROM webfilter_group WHERE ID=$gpid");
	$js[]="LoadAjaxSilent('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";
	$js[]="LoadAjaxSilent('ufdb-rules-source','$page?table=$ruleid');";
	header("content-type: application/x-javascript");
	echo @implode("\n", $js);
}



function popup(){
	$page=CurrentPageName();
	$ID=intval($_GET["popup"]);
	$RefreshTable=base64_encode("LoadAjaxSilent('ufdb-rules-source','$page?table=$ID');");
	echo "<div id='ufdb-rules-source'></div><script>LoadAjaxSilent('ufdb-rules-source','$page?table=$ID&RefreshTable=$RefreshTable');</script>";
}
function link_group_popup():bool{
	$page=CurrentPageName();
	$ID=intval($_GET["link-group-popup"]);
	
	echo "<div id='ufdb-rules-source-choose'></div><script>LoadAjaxSilent('ufdb-rules-source-choose','$page?link-group-choose=$ID');</script>";
    return true;
}
function link_group_choose():bool{
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["link-group-choose"]);
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($Enablehacluster==1){$EnableKerbAuth=1;}
    if($EnableActiveDirectoryFeature==1){
        $EnableKerbAuth=1;
    }
	$_SESSION["UFDB_CHOOSE_GROUP"]=array();

	if($EnableOpenLDAP==1){$zlocalldap[0]="{ldap_group}";}
	$zlocalldap[1]="{virtual_group}";
	if($EnableKerbAuth==1){
		$zlocalldap[2]="{active_directory_group}";
        $zlocalldap[5]="{active_directory_ou}";
	}
	$zlocalldap[3]="{remote_ladp_group}";
	$localldap[0]="{ldap_group}";
	$localldap[1]="{virtual_group}";
	$localldap[2]="{active_directory_group}";
	$localldap[3]="{remote_ladp_group}";
	$localldap[4]="{active_directory_group} ({other})";
    $localldap[5]="{active_directory_ou}";
	
	$sql="SELECT *  FROM `webfilter_group` order by groupname";
	$results = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
	$GROUPS=array();
    foreach ($results as $index=>$ligne){
		$groupid=$ligne['ID'];
		if(preg_match("#ExtLDAP:(.+?):#", $ligne["groupname"],$re)){$ligne["groupname"]=$re[1];}
		$typeexplain=$tpl->_ENGINE_parse_body($localldap[$ligne["localldap"]]);
		$GROUPS[$groupid]="{$ligne["groupname"]} ($typeexplain)";
	}
	$Form[]=$tpl->field_hidden("link1", $ID);
	if(count($GROUPS)>0){
		$Form[]=$tpl->field_array_hash($GROUPS, "choosen","{choose_a_source}");
	}
	$Form[]=$tpl->field_array_hash($zlocalldap, "localldap", "{new_source}", null);
	
	echo $tpl->form_outside("$rulename {link_group}", @implode("\n", $Form),null,"{next}","LoadAjaxSilent('ufdb-rules-source-choose','$page?link-group-step2=$ID');");
	return true;

}

function link_group_choose_step2(){
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["link-group-step2"]);
	
	$js[]="BootstrapDialog1.close()";
	$js[]="LoadAjaxSilent('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";
	$js[]="LoadAjaxSilent('ufdb-rules-source','$page?table=$ID');";
	
	
	if(isset($_SESSION["UFDB_CHOOSE_GROUP"]["choosen"])){
		$groupid=intval($_SESSION["UFDB_CHOOSE_GROUP"]["choosen"]);
        if($groupid>0) {
            $md5 = md5("$ID$groupid");
            $sql = "INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$ID','$groupid')";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error_html();
                return;
            }
            echo "<script>" . @implode(";\n", $js) . "</script>";
            return;
        }
	}
	
	
	
	
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$localldap_f=$_SESSION["UFDB_CHOOSE_GROUP"]["localldap"];
	$localldap[0]="{ldap_group}";
	$localldap[1]="{virtual_group}";
	$localldap[2]="{active_directory_group}";
	$localldap[3]="{remote_ladp_group}";
	$localldap[4]="{active_directory_group} ({other})";
    $localldap[5]="{active_directory_ou}";
	$typeexplain=$tpl->_ENGINE_parse_body($localldap[$localldap_f]);
	
	$form[]=$tpl->field_hidden("link2", $ID);
	$form[]=$tpl->field_hidden("localldap", $localldap_f);



	if($localldap_f==0){
        $form_explain="{ufdb_localldap_0}";
        $form[]=$tpl->field_ldap_group("groupid", "{ldap_group}",
            null,true,null,false,true);
    }
	
	if($localldap_f==2){
		$form_explain="{ufdb_localldap_2}";
		$form[]=$tpl->field_activedirectorygrp("groupid", "{activedirectory_group}",
            null,true,null,false,true);
	}

    if($localldap_f==3){
        $form_explain="{ufdb_localldap_3}";
        // Sous la form de ExtLDAP:GroupName:DN
        $form[]=$tpl->field_remote_ldap_groups("groupid", "{remote_ladp_group}",
            null,true,null,false,true);
    }
	
	if($localldap_f==1){
		$form_explain="{group_explain_proxy_acls_type_1}";
		$form[]=$tpl->field_text("groupid", "{groupname}", null,true,null,false,true);
	}

    if($localldap_f==5){
        $form_explain="{group_explain_proxy_acls_type_5}";
        $form[]=$tpl->field_text("groupid", "{organization}", null,true,null,false,true);
    }


    echo $tpl->form_outside("$rulename &raquo; {source} &raquo; $typeexplain", @implode("\n", $form),$form_explain,"{link}",@implode(";", $js),"AsDansGuardianAdministrator");
	
	
}

function link_group_choose_final(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$webfilter_ruleid=intval($_POST["link2"]);
	$localldap=intval($_POST["localldap"]);
	$groupid=$_POST["groupid"];



    if($localldap==5){
        $ouName=$groupid;
        $ad=new external_ad_search();
        $Ous=$ad->active_directory_ListOus($ouName);
        if(!$Ous){
            echo "jserror: ".$tpl->_ENGINE_parse_body("{unable_to_find_ou}")." $ouName<br>{$ad->error}";
            return;
        }

        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) VALUES ('$ouName','$ouName',1,$localldap,'Organization Unit $ouName','')";
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $q->QUERY_SQL($sql_add);
        if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";return;}

        $groupid=$q->last_id;
        if($groupid==0){echo "jserror: Unable to find the Next ID!!";return;}

        $md5=md5("$webfilter_ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}
        return;

    }
	
	if($localldap==2){
		$dn=$groupid;
		$ad=new external_ad_search();

		$ARRAY=$ad->FindParametersByDN($dn);
		if($ARRAY["samaccountname"]==null){
			echo "jserror:samaccountname not found";
			return;
		}
		$settings=base64_encode(serialize($ARRAY));
		$groupname=$ARRAY["samaccountname"];
		$description=$ARRAY["description"];
		$ldap_server=$ARRAY["LDAP_SERVER"].":".$ARRAY["LDAP_PORT"];
		//recherche la source via le DN...
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$dn=$q->sqlite_escape_string2($dn);
		$description=$q->sqlite_escape_string2($description);
		$sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) VALUES ('$settings','$groupname',1,$localldap,'$description','$dn')";

		$q->QUERY_SQL($sql_add);

		if(!$q->ok){echo $q->mysql_error;return;}
		$groupid=$q->last_id;
		if($groupid==0){echo "Unable to find the Next ID!!\n";return;}
		
		$md5=md5("$webfilter_ruleid$groupid");
		$sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		return;
	}	
	
	if($localldap==1){
		$groupname=$groupid;
		$sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) 
		VALUES ('','$groupname',1,$localldap,'{virtual_group}','')";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$q->QUERY_SQL($sql_add);
		if(!$q->ok){echo $q->mysql_error;return;}
		$groupid=$q->last_id;
		if($groupid==0){echo "Unable to find the Next ID!!\n";return;}
		$md5=md5("$webfilter_ruleid$groupid");
		$sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		return;
		
	}
    if($localldap==3){

        if(!preg_match("#ExtLDAP:(.+?):(.+)#", $groupid,$re)){
            WLOG("Wrong ACL pattern $groupid");
            echo "jserror:Wrong ACL pattern $groupid";
            return;
        }

        $groupname=$groupid;
        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) 
		VALUES ('','$groupname',1,$localldap,'{remote_ladp_group}','')";
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $q->QUERY_SQL($sql_add);
        if(!$q->ok){echo $q->mysql_error;return;}
        $groupid=$q->last_id;
        if($groupid==0){echo "Unable to find the Next ID!!\n";return;}
        $md5=md5("$webfilter_ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}
        return;

    }

    if($localldap==0){
        $groupname=$groupid;
        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`,gpid) 
		VALUES ('','$groupname',1,$localldap,'{ldap_group}','',$groupid)";
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $q->QUERY_SQL($sql_add);
        if(!$q->ok){echo $q->mysql_error;return;}
        $groupid=$q->last_id;
        if($groupid==0){echo "Unable to find the Next ID!!\n";return;}
        $md5=md5("$webfilter_ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$webfilter_ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}
        return;

    }
	
	
	
	
	
	
	
}

function link_group_choose_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$_SESSION["UFDB_CHOOSE_GROUP"]["localldap"]=$_POST["localldap"];
	$_SESSION["UFDB_CHOOSE_GROUP"]["ruleid"]=$_POST["link1"];
	if(isset($_POST["choosen"])){$_SESSION["UFDB_CHOOSE_GROUP"]["choosen"]=$_POST["choosen"];}
	
}



function table(){
	if(!isset($_GET["QuotaID"])){$_GET["QuotaID"]=0;}
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$ID=intval($_GET["table"]);
	$RefreshTable=$_GET["RefreshTable"];
	
	$dump_group_text=$tpl->_ENGINE_parse_body("{dump_group}");
		
	$sql="SELECT webfilter_assoc_groups.ID,webfilter_assoc_groups.zMD5,webfilter_assoc_groups.webfilter_id, 
	webfilter_group.groupname,webfilter_group.description,webfilter_group.gpid,
	webfilter_group.localldap,webfilter_group.ID as webfilter_group_ID, webfilter_group.dn as webfilter_group_dn, webfilter_group.settings as settings, 
	webfilter_group.enabled
	FROM webfilter_group,webfilter_assoc_groups WHERE webfilter_assoc_groups.webfilter_id={$ID} AND webfilter_assoc_groups.group_id=webfilter_group.ID ORDER BY webfilter_group.groupname";
	
	if($_GET["QuotaID"]>0){
		if(!$q->TABLE_EXISTS("webfilter_assoc_quota_groups")){$q->CheckTables(null,true);}
		$sql="SELECT webfilter_assoc_quota_groups.ID,webfilter_assoc_quota_groups.webfilter_id,webfilter_group.groupname,webfilter_group.description,webfilter_group.gpid,webfilter_group.localldap,webfilter_group.ID as webfilter_group_ID,webfilter_group.dn as webfilter_group_dn,webfilter_group.enabled FROM webfilter_group,webfilter_assoc_quota_groups WHERE webfilter_assoc_quota_groups.webfilter_id={$_GET["QuotaID"]} AND webfilter_assoc_quota_groups.group_id=webfilter_group.ID ORDER BY webfilter_group.groupname";
	}
	
	$localldap[0]="{ldap_group}";
	$localldap[1]="{virtual_group}";
	$localldap[2]="{active_directory_group}";
	$localldap[3]="{remote_ladp_group}";
	$localldap[4]="{active_directory_group} ({other})";
    $localldap[5]="{active_directory_ou}";
	
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]=$tpl->button_label_table("{link_group}", "Loadjs('$page?link-group-js=$ID')", "fa-plus","AsDansGuardianAdministrator");
	$html[]=$tpl->button_label_table("{display_members}", "Loadjs('$page?link-dump-js=$ID')", "fa-search","AsDansGuardianAdministrator");
	$html[]="</div>";
	
	$html[]="<table id='table-ufbd-linked-groups' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sources}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();return;}
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$textExplainGroup=null;
		$KEY_ID_GROUP=$ligne["webfilter_group_ID"];
		$CountDeMembers="??";
		$Textdynamic=null;
		$md=$ligne["zMD5"];
		$webfilter_assoc_groups_ID=$ligne["ID"];
		
	
		if($ligne["localldap"]==0){
			$gp=new groups($ligne["gpid"]);
            $ligne["groupname"]=$tpl->td_href($gp->groupName,"","Loadjs('fw.groups.ldap.php?gpid={$ligne["gpid"]}')");
            $description=$gp->description;
            if(strlen($description)>1){$description="<br><small>$description</small>";}
			$CountDeMembers=count($gp->members);
		}
	
		if($ligne["localldap"]==1){
			$sql="SELECT COUNT(ID) as tcount FROM webfilter_members WHERE `groupid`='$KEY_ID_GROUP'";
			$COUNLIGNE=$q->mysqli_fetch_array($sql);
			$CountDeMembers=$COUNLIGNE["tcount"];
		}
	
		
		if($ligne["enabled"]==0){$color="#9A9A9A";}
		
		if($ligne["localldap"]==2){
			if(preg_match("#AD:(.*?):(.+)#", $ligne["webfilter_group_dn"],$re)){
			$dnEnc=$re[2];
			$LDAPID=$re[1];
			$ad=new ActiveDirectory($LDAPID);

			if(preg_match("#^CN=(.+?),.*#i", base64_decode($dnEnc),$re)){
				$groupname=_ActiveDirectoryToName($re[1]);
				$CountDeMembers='-';
				$Debug="&nbsp;<a href=\"javascript:Loadjs('dansguardian2.explodeadgroup.php?rule-id={$_GET["rule-id"]}&groupid=$KEY_ID_GROUP');\"
				style=\"text-decoration:underline\">$dump_group_text</a>";
			}else{
				$tty=$ad->ObjectProperty(base64_decode($dnEnc));
                if(!isset($tty["MEMBERS"])){$tty["MEMBERS"]=0;}
				$CountDeMembers=$tty["MEMBERS"];
			}
	
			$description=htmlentities($tty["description"]);
			$description=str_replace("'", "`", $description);
			if(trim($ligne["description"])==null){$ligne["description"]=$description;}
			}else{
				$settings=unserialize(base64_decode($ligne["settings"]));
				$groupname=$ligne["groupname"];
				$description=$ligne["description"];
				$ad=new ActiveDirectory(0,$settings);
				$tty=$ad->ObjectProperty($ligne["webfilter_group_dn"]);
                if(!isset($tty["MEMBERS"])){$tty["MEMBERS"]=0;}
				$CountDeMembers=$tty["MEMBERS"];
			}
			
		}
	
		if($ligne["localldap"]==0){
			if(preg_match("#^ExtLdap:(.+)#", $ligne["webfilter_group_dn"],$re)){
				$CountDeMembers='-';
				$groupadd_text="&nbsp;{$re[1]}";
			}
		}
	
		if($ligne["localldap"]==3){
			if(preg_match("#ExtLDAP:(.+?):(.+)#", $ligne["groupname"],$re)){$ligne["groupname"]=$re[1];}
			$DN=base64_decode($re[2]);
			include_once(dirname(__FILE__)."/ressources/class.ldap-extern.inc");
			$ldap_ext=new ldap_extern();
			$CountDeMembers=$ldap_ext->CountDeUsersByGroupDN($DN);
		}
	
		if($ligne["localldap"]==4){
			$settings=unserialize(base64_decode($ligne["settings"]));
			$ad=new ActiveDirectory(0,$settings);
			$Members=$ad->search_users_from_groupName($ligne["groupname"]);
			$CountDeMembers=count($Members);
		}
		
		
		if($ligne["localldap"]==1){
			$ligne["groupname"]=$tpl->td_href($ligne["groupname"],null,"Loadjs('fw.ufdb.group.php?ID=$KEY_ID_GROUP&RefreshTable=$RefreshTable')");
			$description=$ligne["description"];
			if(strlen($description)>1){$description="<br><small>$description</small>";}
		}
	
		$imgGP="win7groups-32.png";
		if($ligne["localldap"]<2){$imgGP="group-32.png";}
		if($Textdynamic<>null){$imgGP="warning-panneau-32.png";}
		
		$TextGroupType=$tpl->_ENGINE_parse_body($localldap[$ligne["localldap"]]);
		
				if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
				$html[]="<tr class='$TRCLASS' id='$md'>";
				$html[]="<td>{$ligne["groupname"]}$description</td>";
				$html[]="<td>{$TextGroupType}</td>";
				$html[]="<td style='width:1%' nowrap>{$CountDeMembers}</td>";
				$html[]="<td style='width:1%' class='center' nowrap>".$tpl->icon_unlink("Loadjs('$page?unlink-js=$webfilter_assoc_groups_ID&md=$md')","AsDansGuardianAdministrator") ."</center></td>";
				$html[]="<td style='width:1%' class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-js=$webfilter_assoc_groups_ID&gpid=$KEY_ID_GROUP&ruleid=$ID&md=$md')","AsDansGuardianAdministrator") ."</center></td>";
				$html[]="</tr>";
				
			
	}
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-ufbd-linked-groups').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}


