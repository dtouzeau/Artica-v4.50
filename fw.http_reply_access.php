<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$users=new usersMenus();if(!$users->AsDansGuardianAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_main_save();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["parameters"])){rule_parameters();exit;}
if(isset($_POST["parameters-save"])){rule_parameters_save();exit;}
if(isset($_GET["example-js"])){example_js();exit;}


page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{http_reply_access}",ico_shield,
        "{http_reply_access_text}","$page?table=yes","proxy-reply-access","progress-reply-access-restart",
    false,"table-loader-proxy-reply-access");




    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {http_reply_access}",$html);
        echo $tpl->build_firewall();
        return;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function enabled_js(){
    $tpl        = new template_admin();
    $aclid      = $_GET["enabled-js"];
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT enabled FROM http_reply_access WHERE ID='$aclid'");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $enabled=$ligne["enabled"];
    if($enabled==1){$enabled=0;}else{$enabled=1;}
    $q->QUERY_SQL("UPDATE http_reply_access SET enabled=$enabled WHERE ID=$aclid");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);}
}


function example_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM http_reply_access WHERE config='ARTICA_EXAMPLE'");
    $aclid=intval($ligne["ID"]);

    if($aclid==0){
        $rulename="Deny executables";
        $q->QUERY_SQL("INSERT INTO `http_reply_access` (`rulename`,`enabled` ,`zorder`,`allow`,`config`) 
        VALUES ('$rulename','1','0','1','ARTICA_EXAMPLE')");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM http_reply_access WHERE config='ARTICA_EXAMPLE'");
        $aclid=intval($ligne["ID"]);

    }

    if($aclid==0){
        $tpl->js_mysql_alert("Unable to found main ID");
        return;
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='HTTP_REPLY_ACCESS_EXAMPLE'");
    $gpid=intval($ligne["ID"]);
    if($gpid==0){
        $GroupName="Mime: Executables";
        $GroupType="rep_mime_type";
        $q->QUERY_SQL("INSERT INTO `webfilters_sqgroups` (GroupName,GroupType,enabled,tplreset,params) 
        VALUES ('$GroupName','$GroupType',1,0,'HTTP_REPLY_ACCESS_EXAMPLE')");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='HTTP_REPLY_ACCESS_EXAMPLE'");
        $gpid=intval($ligne["ID"]);

    }

    if($gpid==0){
        $tpl->js_mysql_alert("Unable to found Group ID");
        return;
    }

    $md5=md5($aclid.$gpid);
    $sql="INSERT OR IGNORE INTO `http_reply_access_links` (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',0)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

   $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$gpid'");

    $tt[]="application/x-msdownload";
    $tt[]="application/octet-stream";
    $tt[]="application/exe";
    $tt[]="application/x-exe";
    $tt[]="application/dos-exe";
    $tt[]="vms/exe";
    $tt[]="application/x-winexe";
    $tt[]="application/msdos-windows";
    $tt[]="application/x-msdos-program";
    $tt[]="application/x-msi";
    $tt[]="text/vbscript";
    $tt[]="text/vbs";
    $tt[]="application/gzip";
    $tt[]="application/x-compress";
    $tt[]="application/x-gtar";
    $tt[]="application/x-tar";
    $tt[]="application/x-rar-compressed";
    $tt[]="application/x-7z-compressed";
    $tt[]="application/x-bittorrent";

    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="Manager";}
    $zdate=date("Y-m-d H:i:s");
    foreach ($tt as $mime){
        $sqladd="INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other,zdate,uid) 
        VALUES ('$mime','$gpid',1,'$mime (EXE) mime type','$zdate','$uid')";
        $q->QUERY_SQL($sqladd);
        if(!$q->ok){echo $q->mysql_error;}
    }


    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-proxy-reply-access','$page?table=yes');";



}

function rule_delete_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $aclid=intval($_GET["delete-rule-js"]);
    header("content-type: application/x-javascript");

    $delete_personal_cat_ask=$tpl->javascript_parse_text("{delete} {$_GET["name"]} ?");
    $t=time();
    $html="
	
	var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#row-parent-$aclid').remove();
	}
	
function Action$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-rule','$aclid');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
	
	Action$t();";
    echo $html;

}

function rule_delete(){
    $aclid=$_POST["delete-rule"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM http_reply_access_links WHERE aclid=$aclid");
    if(!$q->ok){echo $q->mysql_error;return;}
    $q->QUERY_SQL("DELETE FROM http_reply_access WHERE ID=$aclid");
    if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Delete Proxy reply access rule  $aclid ");}




function rule_id_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["ruleid-js"];
    $title="{new_rule}";

    if($id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM http_reply_access WHERE ID='$id'");
        $title="{rule}: $id {$ligne["rulename"]}";
    }
    $title=$tpl->javascript_parse_text($title);
    $tpl->js_dialog($title,"$page?rule-popup=$id");
}



function rule_settings(){
    $aclid=intval($_GET["rule-settings"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne["enabled"]=1;
    $ligne["zorder"]=1;
    $btname="{add}";
    $title="{new_rule}";
    $BootstrapDialog="BootstrapDialog1.close();";
    VERBOSE("ID = $aclid");
    if($aclid>0){
        $btname="{apply}";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM http_reply_access WHERE ID='$aclid'");
        $title=$ligne["rulename"];
        $BootstrapDialog=null;
    }

   $allow[0]="{allow}";
   $allow[1]="{deny}";

    $tpl->field_hidden("rule-save", $aclid);
    $form[]=$tpl->field_text("rulename","{rule_name}",$ligne["rulename"],true,null,false);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);

    $form[]=$tpl->field_array_hash($allow, "allow", "{access}", $ligne["allow"],true,null,false);
    $form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
    echo $tpl->form_outside($title,@implode("\n", $form),"{http_reply_access_explain}",$btname,"LoadAjax('table-loader-proxy-reply-access','$page?table=yes');$BootstrapDialog","AsDansGuardianAdministrator");
}




function rule_main_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$users->AsDansGuardianAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}

    $aclid=$_POST["rule-save"];
    $tpl->CLEAN_POST_XSS();
    $rulename=$q->sqlite_escape_string2($_POST["rulename"]);

    if($aclid==0){
        $sqlB="INSERT INTO `http_reply_access` (`rulename`,`enabled` ,`zorder`,`allow`) 
		VALUES ('$rulename','{$_POST["enabled"]}','{$_POST["zorder"]}','{$_POST["allow"]}')";
    }else{
        $sqlB="UPDATE `http_reply_access` SET `rulename`='$rulename',`enabled`='{$_POST["enabled"]}',
		`zorder`='{$_POST["zorder"]}',`allow`='{$_POST["allow"]}' WHERE aclid='$aclid'";
    }


    $q->QUERY_SQL($sqlB);
    if(!$q->ok){echo $q->mysql_error_html(true);}
}

function rule_tab(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $aclid=intval($_GET["rule-popup"]);

    $array["{rule}"]="$page?rule-settings=$aclid";
    if($aclid>0){
        $array["{objects}"]="fw.proxy.objects.php?aclid=$aclid&main-table=http_reply_access_links&fast-acls=0";

    }
    echo $tpl->tabs_default($array);


}



function rule_move_js(){
    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $dir=$_GET["dir"];
    $aclid=intval($_GET["aclid"]);
    $ligne=$q->mysqli_fetch_array("SELECT zorder FROM http_reply_access WHERE ID='$aclid'");
    $zorder=intval($ligne["zorder"]);
    echo "// Current order = $zorder\n";

    if($dir=="up"){
        $zorder=$zorder-1;
        if($zorder<0){$zorder=0;}

    }
    else{
        $zorder=$zorder+1;
    }
    echo "// New order = $zorder\n";
    $q->QUERY_SQL("UPDATE http_reply_access SET zorder='$zorder' WHERE ID='$aclid'");
    if(!$q->ok){
        $q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
        echo "alert('$q->mysql_error');";return;
    }

    $c=0;
    $results=$q->QUERY_SQL("SELECT aclid FROM http_reply_access ORDER BY zorder");
    foreach($results as $index=>$ligne) {
        $aclid=$ligne["aclid"];
        echo "// $aclid New order = $c";
        $q->QUERY_SQL("UPDATE http_reply_access SET zorder='$c' WHERE ID='$aclid'");
        $c++;
    }



}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $users=new usersMenus();
    $eth_sql=null;
    $token=null;
    $class=null;

    $rulename=$tpl->_ENGINE_parse_body("{rulename}");
    $ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
    $t=$_GET["t"];
    if(!is_numeric($t)){$t=time();}

    $t=time();
    $add="Loadjs('$page?ruleid-js=0',true);";
    if(!$users->AsDansGuardianAdministrator){$add="alert('$ERROR_NO_PRIVS2')";}



    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    $ARRAY["CMD"]="squid2.php?global-reply-access-center=yes";
    $ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER}";
    $ARRAY["REFRESH-MENU"]=1;
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-reply-access-restart')";

    $jsexample="Loadjs('$page?example-js=yes')";


    $topbuttons[] = array($add,ico_plus,"{new_rule}");
    $topbuttons[] = array($jsrestart,ico_save,"{apply_rules}");
    $topbuttons[] = array($jsexample,ico_plus,"{example}");

    $html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";



    $html[]="<th data-sortable=false style='width:1%'></th>";
    $html[]="<th data-sortable=true style='width:1%'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rulename</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $jsAfter="LoadAjax('table-loader-proxy-reply-access','$page?table=yes&eth={$_GET["eth"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

    $allows[0]="{allow}";
    $allows[1]="{reject}";

    $btns[1]="btn-danger";
    $btns[0]="btn-info";

    $isRights=isRights();
    $results=$q->QUERY_SQL("SELECT * FROM http_reply_access ORDER BY zorder");
    $TRCLASS=null;
    foreach($results as $index=>$ligne) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $rulename=htmlspecialchars($ligne["rulename"]);
        $rulenameenc=urlencode($rulename);
        $aclid=$ligne["ID"];
        $allow=$ligne["allow"];
        $check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$aclid')");

        $up=$tpl->icon_up("Loadjs('$page?move-js=yes&aclid=$aclid&dir=up')");
        $down=$tpl->icon_down("Loadjs('$page?move-js=yes&aclid=$aclid&dir=down')");
        $js="Loadjs('$page?ruleid-js=$aclid',true);";
        $delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$aclid}&name=$rulenameenc')");

        $explain=$tpl->_ENGINE_parse_body("{for_objects} ".proxy_objects($aclid)." {then} <strong>{$allows[$allow]}</strong> {web_server_reponse}");
        if(!$isRights){
            $up=null;
            $down=null;
            $delete=null;
        }




        $html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
        $html[]="<td class=\"center\"><button type='button' class='btn {$btns[$allow]} btn-bitbucket' OnClick=\"$js\" ><i class='fa fa-paste'></i></button></td>";
        $html[]="<td class=\"center\">$check</td>";
        $html[]="<td style='vertical-align:middle'>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>{$rulename}:</a>&nbsp;&raquo;&nbsp;$explain</span></td>";
        $html[]="<td class=\"center\">$up&nbsp;&nbsp;$down</td>";
        $html[]="<td class=center>$delete</td>";
        $html[]="</tr>";

    }





    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
    $html[]="<td class=\"center\"><button type='button' class='btn btn-info btn-bitbucket' ><i class='fa fa-paste'></i></button></td>";
    $html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle'>".$tpl->_ENGINE_parse_body("<strong>{default}</strong>:</a>&nbsp;&raquo;&nbsp;{for_objects} {all} {then} {do_nothing} {after_webserver_reply}")."</td>";
    $html[]="<td class=\"center\">&nbsp;&nbsp;</td>";
    $html[]="<td class=center>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";




    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";


    $TINY_ARRAY["TITLE"]="{http_reply_access}";
    $TINY_ARRAY["ICO"]=ico_shield;
    $TINY_ARRAY["EXPL"]="{http_reply_access_text}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
</script>";

    echo @implode("\n", $html);

}
function isRights():bool{
    $users=new usersMenus();
    if($users->AsDansGuardianAdministrator){return true;}
    if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

function proxy_objects($aclid):string{
    $qProxy=new mysql_squid_builder(true);
    $tt=array();
    $tablelink="http_reply_access_links";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql="SELECT
	$tablelink.gpid,
	$tablelink.zmd5,
	$tablelink.negation,
	$tablelink.zOrder,
	webfilters_sqgroups.GroupType,
	webfilters_sqgroups.GroupName,
	webfilters_sqgroups.ID
	FROM $tablelink,webfilters_sqgroups
	WHERE $tablelink.gpid=webfilters_sqgroups.ID
	AND $tablelink.aclid=$aclid
	ORDER BY $tablelink.zorder";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){return "";}

    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
        $neg_text="{is}";
        if($ligne["negation"]==1){$neg_text="{is_not}";}
        $GroupName=htmlspecialchars($ligne["GroupName"]);
        $tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$GroupName</a> (".$qProxy->acl_GroupType[$ligne["GroupType"]].")";
    }

    if(count($tt)>0){
        return @implode("<br>{and} ", $tt);

    }else{
        return "{all}";
    }


}