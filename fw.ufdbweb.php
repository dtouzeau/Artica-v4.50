<?php
$GLOBALS["redirtypes"][0]="{ngix_rwfl_redirect}";
$GLOBALS["redirtypes"][1]="{ngix_rwfl_permanent}";
$GLOBALS["redirtypes"][2]="{inside_redirection}";
$GLOBALS["redirtypes"][3]="{reset_connection}";

$GLOBALS["protocol"][0]="{all}";
$GLOBALS["protocol"][1]="GET";
$GLOBALS["protocol"][2]="CONNECT";
$GLOBALS["protocol"][3]="POST";



include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["start-rules"])){start_rules();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["SquidGuardWebUseExternalUri"])){save();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){rule_enable();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_GET["rule-move"])){rule_move();exit;}
if(isset($_POST["rule-delete-confirm"])){rule_delete_confirm();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{banned_page_webservice}","fad fa-page-break",
        "{APP_UFDB_HTTP_EXPLAIN}","$page?tabs=yes","webfiltering-webpages",
        "progress-ufdbweb-restart,progress-weberrorules-restart",false,"table-ufdbweb-tabs");


	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_UFDB_HTTP}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function start_rules(){
    $page=CurrentPageName();
    echo "<div id='ufdbpages-rules'></div><script>LoadAjax('ufdbpages-rules','$page?table=yes');</script>";
    
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$users=new usersMenus();
	$EnableUfdbGuard=intval($sock->GET_INFO('EnableUfdbGuard'));
    $UfdbUseInternalService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"));

    $array["{service_status}"]="fw.proxy.errors.page.php?service-status-start=yes";

	if($EnableUfdbGuard==1){
            $array["{redirect_rules}"] = "$page?start-rules=yes";
	}


    if ($users->AsSquidAdministrator or $users->AsDnsAdministrator) {
            $array["{rules}"] = "fw.ufdbweb.rules.php?table2=yes";
        }
    if ($EnableUfdbGuard == 1) {
        if ($users->AsDansGuardianAdministrator or $users->AsProxyMonitor) {
            $array["{unblock_list}"] = "fw.ufdbweb.unblock.php";
            $array["{requests_list}"] = "fw.ufdbweb.requests.php";
        }
        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        if ($DisablePostGres == 0) {
            $array["{events}"] = "fw.ufdbweb.users-events.php";
        }
    }

    if($UfdbUseInternalService==1){
        $array["{service_events}"]="fw.proxy.errors.page.php?service-status-events=yes";

    }

	echo $tpl->tabs_default($array);

}
function rule_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-popup"]);
    $title="{new_rule}";
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM ufdb_errors WHERE ID='$ID'");
        $title="{rule}: {$ligne["rulename"]}";
    }
    $array[$title]="$page?rule-settings=$ID";
    echo $tpl->tabs_default($array);

}
function rule_settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $ID=intval($_GET["rule-settings"]);
    $explain=null;
    $title="{new_rule}";
    $but="{add}";
    $ligne["rulename"]="New rule";
    $ligne["enabled"]=1;

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");




    if($ID>0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM ufdb_errors WHERE ID='$ID'");
        $title = "{rule}: {$ligne["rulename"]}";
        $but="{apply}";
    }
    $webruleids[0]="{all_rules}";
    $exit=null;


    if($ID==0){$exit="dialogInstance1.close();";}
    $jsafter="LoadAjax('ufdbpages-rules','$page?table=yes');$exit";

    $form[]=$tpl->field_hidden("rule-save", "$ID");
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));

    $form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_array_hash($GLOBALS["protocol"], "zprotocol", "nonull:{protocol}", $ligne["protocol"],false,null);
    $form[]=$tpl->field_webfiltering_rules("webruleid","nonull:{webfiltering_rule}",$ligne["webruleid"],null,$webruleids);
    $form[]=$tpl->field_categories_list("category","{category}",intval($ligne["category"]));

    if($EnableNginx==0) {
        $form[] = $tpl->field_checkbox_negative("redirwebserv", "{use_weberror_service}", $ligne["redirwebserv"], "redirtype,url");
        $form[] = $tpl->field_text("url", "{url}", $ligne["url"]);
    }else{
        $form[] = $tpl->field_text("url", "{url}", $ligne["url"]);
    }
    $form[]=$tpl->field_array_hash($GLOBALS["redirtypes"], "redirtype", "nonull:{redirect_uri_text}", $ligne["redirtype"],false,null);



    $html=$tpl->form_outside($title, @implode("\n", $form),$explain,$but,$jsafter,"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function GetWebErrorPageUri():string{

    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==1){return "";}

    $UfdbUseInternalServiceHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
    $UfdbUseInternalServiceHTTPSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPSPort"));

    $UfdbUseInternalServiceHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHostname"));
    if($UfdbUseInternalServiceHostname==null){$UfdbUseInternalServiceHostname=php_uname("n");}
    if($UfdbUseInternalServiceHTTPPort==0){$UfdbUseInternalServiceHTTPPort=9025;}
    if($UfdbUseInternalServiceHTTPSPort==0){$UfdbUseInternalServiceHTTPSPort=9026;}
    $UfdbUseInternalServiceEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceEnableSSL"));
    if($UfdbUseInternalServiceEnableSSL==1){
        return "https://$UfdbUseInternalServiceHostname:$UfdbUseInternalServiceHTTPSPort";
    }
    return "https://$UfdbUseInternalServiceHostname:$UfdbUseInternalServiceHTTPPort";

}



function rule_id_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid-js"]);
    $title="{new_rule}";


    if($ID>0){
         $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
         $ligne=$q->mysqli_fetch_array("SELECT rulename FROM ufdb_errors WHERE ID='$ID'");
         $title="{rule}: {$ligne["rulename"]}";
    }



    $title=$tpl->javascript_parse_text($title);
    return $tpl->js_dialog1($title,"$page?rule-popup=$ID");

}
function rule_delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-rule-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM ufdb_errors WHERE ID='$ID'");
    $title="{rule}: {$ligne["rulename"]}";
    $md=$_GET["md"];
    $tpl->js_confirm_delete($title,"rule-delete-confirm",$ID,"$('#$md').remove();");
    return true;
}
function rule_delete_confirm():bool{
    $tpl=new template_admin();
    $ID         = $_POST["rule-delete-confirm"];
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM ufdb_errors WHERE ID='$ID'");
    $title="{$ligne["rulename"]}";
    $q->QUERY_SQL("DELETE FROM ufdb_errors WHERE ID=$ID");

    $sock=new sockets();
    $json=json_decode($sock->REST_API("/weberror/rules"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/weberror/rules"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }

    admin_tracks("Remove Web error page rule $ID - $title");

    return true;
}
function rule_enable():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $ID=intval($_GET["enable-rule-js"]);

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled,rulename FROM ufdb_errors WHERE ID='$ID'");
    $title="{$ligne["rulename"]}";

    if(intval($ligne["enabled"])==0){$enabled=1;}else{$enabled=0;}

    $q->QUERY_SQL("UPDATE ufdb_errors SET enabled='$enabled' WHERE ID='$ID'");
    if(!$q->ok){echo "alert('".$q->mysql_error."')";return false;}
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/weberror/rules"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }

    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
    return admin_tracks("Change web page error rule $title to $enabled");

}
function rule_move(){
    $tpl=new template_admin();
    $ID=intval($_GET["rule-move"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT zorder,rulename FROM ufdb_errors WHERE ID='$ID'");


    $xORDER_ORG=intval($ligne["xORDER"]);
    $xORDER=$xORDER_ORG;
    $aclname=$ligne["rulename"];

    if($_GET["rule-dir"]==1){
        $xORDER=$xORDER_ORG-1;
    }
    if($_GET["rule-dir"]==0){
        $xORDER=$xORDER_ORG+1;

    }

    $sql="UPDATE ufdb_errors SET zorder=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);


    $sql="UPDATE ufdb_errors SET zorder=$xORDER_ORG WHERE `ID`<>'$ID' AND zorder=$xORDER";
    $q->QUERY_SQL($sql);

    $c=1;
    $sql="SELECT ID FROM ufdb_errors WHERE ORDER BY zorder";
    $results = $q->QUERY_SQL($sql);

    foreach($results as $index=>$ligne) {
        echo "// ID {$ligne["ID"]} became $c\n";
        $q->QUERY_SQL("UPDATE ufdb_errors SET zorder=$c WHERE `ID`={$ligne["ID"]}");
        $c++;
    }
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/weberror/rules"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
    return admin_tracks("Move Web error page rule order of $aclname from $xORDER_ORG to $xORDER");

}

function rule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["rule-save"]);
    unset($_POST["rule-save"]);
    $_POST["protocol"]=$_POST["zprotocol"];
    unset($_POST["zprotocol"]);

    reset($_POST);foreach ($_POST as $key=>$val){
        $EDIT[]="`$key`='$val'";
        $ADDFIELD[]="`$key`";
        $ADDVALS[]="'$val'";

    }

    if($ID==0){
        $sql="INSERT INTO ufdb_errors (".@implode(",", $ADDFIELD).") VALUES (".@implode(",", $ADDVALS).")";

    }else{
        $sql="UPDATE ufdb_errors SET ".@implode(",", $EDIT)." WHERE ID=$ID";

    }

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl->post_error($q->mysql_error);
        return false;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_PACKAGE();
    admin_tracks("Add/edit web error page rule {$_POST["rulename"]}");
    return true;
}

function table(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $page=CurrentPageName();
    $t=time();
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");

    $TRCLASS=null;
    $add="Loadjs('$page?ruleid-js=0',true);";
    //if(!$users->AsDansGuardianAdministrator){$add="alert('ERROR_NO_PRIVS2')";}
    //$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ufdb-http.build.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/ufdb-http.build.progress.log";
    $ARRAY["CMD"]="ufdbguard.php?unlock-rules=yes";
    $ARRAY["TITLE"]="{unlock}::{apply}";
    $prgress=base64_encode(serialize($ARRAY));

    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-weberrorules-restart')";

    $btns[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[] = "<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $btns[] = "</div>";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false>{move}</th>";
    $html[]="<th data-sortable=false>{enable}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $jsAfter="LoadAjax('table-loader-webhttp-rules','$page?table=yes&eth={$_GET["eth"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

    $results=$q->QUERY_SQL("SELECT * FROM ufdb_errors ORDER BY zorder");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $TRCLASS=null;

    $qs=new mysql_catz();

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));
        $category=intval($ligne["category"]);
        $webruleid=intval($ligne["webruleid"]);
        $protocol=intval($ligne["protocol"]);
        $redirtype=intval($ligne["redirtype"]);
        $url=$ligne["url"];
        $explain=array();
        $for_all_webfiltering_rules="{for_all_webfiltering_rules}";
        $protocol_text=$GLOBALS["protocol"][$protocol];
        $redirwebserv=intval($ligne["redirwebserv"]);
        $label_redirect="{label_redirect}";

        if($EnableNginx==0){
            if($redirwebserv==1){
                $label_redirect="<strong>{use_weberror_service}</strong>";
                $url=null;
                $redirtype=0;
            }

        }
        $reditype=$GLOBALS["redirtypes"][$redirtype];
        if($webruleid==0){
            $explain[]="$for_all_webfiltering_rules";
        }else{
            $ligne2=$q->mysqli_fetch_array("SELECT groupname FROM webfilter_rules WHERE ID=$webruleid");
            if(!$q->ok){$ligne2["groupname"]="<span class='text-danger'>{$q->mysql_error}</span>";}
            if($ligne2["groupname"]==null){$ligne2["groupname"]="{rule}:{unknown}";}
            $explain[]="{only_for_webfrule} <strong>".utf8_encode($ligne2["groupname"])."</strong>";
        }
        if($category>0){
            $explain[]="{and} {category} <strong>". $qs->CategoryIntToStr($category)."</strong>";
        }


        $explain[]="{and} {protocol} $protocol_text";
        $explain[]="{then} $label_redirect <strong>$url</strong> ($reditype)";

        $js="Loadjs('$page?ruleid-js=$ID')";
        $final_text=$tpl->utf8_encode($tpl->_ENGINE_parse_body(@implode("&nbsp;", $explain)));



        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><H3>". $tpl->td_href($ligne["rulename"],null,$js)."</H3>$final_text</td>";
        $up=$tpl->icon_up("Loadjs('$page?rule-move=$ID&rule-dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?rule-move=$ID&rule-dir=0');");
        $html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";

        $html[]="<td width=1% class='center' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-rule-js=$ID')",null,"AsDansGuardianAdministrator");

        $html[]="<td width=1% class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-rule-js=$ID&md=$md')","AsDansGuardianAdministrator") ."</center></td>";
        $html[]="</tr>";

    }
    $explain=array();
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $explain[]="{for_all_webfiltering_rules}  {and_for_all_categories} {and} {protocol} {all} {then} {label_redirect} <strong>http://articatech.net/block.html</strong> ({ngix_rwfl_redirect})";
    $final_text=utf8_encode($tpl->_ENGINE_parse_body(@implode("<br>", $explain)));



    $html[]="<tr class='$TRCLASS' id='null'>";
    $html[]="<td><H3>{default}</H3>$final_text</td>";
    $html[]="<td width=1% class='center' nowrap>". $tpl->icon_nothing() ."</center></td>";
    $html[]="<td width=1% class='center' nowrap>". $tpl->icon_nothing() ."</center></td>";
    $html[]="<td width=1% class='center' nowrap>". $tpl->icon_nothing() ."</center></td>";
    $html[]="</tr>";


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";


    $TINY_ARRAY["TITLE"]="{banned_page_webservice}";
    $TINY_ARRAY["ICO"]="fad fa-page-break";
    $TINY_ARRAY["EXPL"]="{APP_UFDB_HTTP_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}