<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["default-js"])){default_js();exit;}
if(isset($_GET["default-popup"])){default_popup();exit;}
if(isset($_POST["ProxyDefaultUncryptSSL"])){ProxyDefaultUncryptSSL_save();exit;}
if(isset($_GET["filltable"])){filltable();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();

	
	
	if(isset($_GET["main-page"])){

            $html=$tpl->page_header("{ssl_protocol}&nbsp;&raquo;&nbsp;{PROXY_ACLS}"
                ,"fas fa-file-certificate","{PROXY_ACLS_EXPLAIN}","$page?table=yes","proxy-ssl-rules",
                "progress-acls-restart",false,"table-acls-sslErrs-rules");

    }else{
        $html="<div class='row'><div id='progress-acls-restart'></div>
                <div class='ibox-content'>
                    <div id='table-acls-sslErrs-rules'></div>
                </div>
    </div><script>
        LoadAjax('table-acls-sslErrs-rules','$page?table=yes');
    </script>";


    }


	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function filltable(){
    $ACCESSEnabled=0;
    $tpl=new template_admin();
    $uncrypt_ssl=$tpl->javascript_parse_text("{uncrypt_websites}");
    $trust_ssl=$tpl->javascript_parse_text("{trust_ssl}");
    $ID=$_GET["filltable"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sslproxy_cert_error WHERE ID='$ID'");
    $crypt=$ligne["crypt"];
    $enabled=intval($ligne["enabled"]);
    if($crypt==1 OR ($ligne["trust"]==1)){$ACCESSEnabled=1;}
    $squid_acls_groups=new squid_acls_groups();
    $objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"sslproxy_cert_error_sqacllinks");
    $and_text=$tpl->javascript_parse_text(" {and} ");




    if($crypt==1){
        $uncrypt_ssl_text=$uncrypt_ssl;

        $ACCESSEnabled=1;
        $TTEXT[]="<strong>$uncrypt_ssl_text</strong>";
    }else{

        $TTEXT[]="<strong>{do_not_encrypt_websites}</strong>";
    }

    if($ligne["trust"]==1){
        $TTEXT[]="<strong>$trust_ssl</strong>";
        $ACCESSEnabled=1;
    }

    $TTEXT=array();
    $please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");

    if(count($objects)>0) {
        $explain=$squid_acls_groups->ACL_MULTIPLE_EXPLAIN($ligne['ID'],$ACCESSEnabled,0,"sslproxy_cert_error_sqacllinks")." {then} ".@implode($and_text, $TTEXT);

    }else{
        $explain="<div class=text-danger'>$please_specify_an_object</div>";
    }
    $img=$tpl->_ENGINE_parse_body(icon_status($crypt,$enabled,$objects));
    $explain=$tpl->_ENGINE_parse_body($explain);
    header("content-type: application/x-javascript");
    echo "document.getElementById('ssl-rule-icon-$ID').innerHTML=\"$img\";\n";
    echo "document.getElementById('ssl-rule-text-$ID').innerHTML=\"$explain\";\n";
}

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["rule-delete-js"];
	$md="acl-$ID";
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";
	
	
}
function rule_enable(){
    $page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM sslproxy_cert_error WHERE ID='$ID'");
	$enabled_src=intval($ligne["enabled"]);
	if($enabled_src==0){
		$js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
		$enabled=1;
	}else{
	    $js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
		$enabled=0;
	}
	
	$q->QUERY_SQL("UPDATE sslproxy_cert_error SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");


	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    header("content-type: application/x-javascript");
	echo "// ID = $ID, src=$enabled_src, enabled =$enabled\n";
	echo $js."\n";
	echo "Loadjs('$page?filltable=$ID');\n";
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval(trim($_GET["rule-id-js"]));
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT aclname FROM webfilters_sqacls WHERE ID='$ID'");
	$tpl->js_dialog("{rule}: $ID {$ligne["aclname"]}","$page?rule-tabs=$ID");
}
function default_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{default}","$page?default-popup=yes");

}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";
	$RefreshTable=base64_encode("LoadAjax('table-acls-sslErrs-rules','$page?table=yes');");
	$array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=sslproxy_cert_error_sqacllinks&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);
	
}



function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-settings"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM sslproxy_cert_error WHERE ID='{$ID}'");

	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_text("description", "{rule_name}", $ligne["description"],true);
    $Types[1]="{sslproxy_cert_error_deny_explain}";
    $Types[2]="{sslproxy_cert_error_allow_explain}";
    $form[]=$tpl->field_array_checkboxes2Columns($Types, "ruletype",$ligne["ruletype"]);

	$jsafter="LoadAjax('table-acls-sslErrs-rules','$page?table=yes');";
	
	$html=$tpl->form_outside("{rule} {$ligne["description"]}", @implode("\n", $form),null,"{apply}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_save(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
    $edit_fields=array();
	$_POST["description"]=sqlite_escape_string2(url_decode_special_tool($_POST["description"]));
	foreach ($_POST as $key=>$val){
		$add_fields[]="`$key`";
		$add_values[]="'$val'";
		$edit_fields[]="`$key`='$val'";
	}
	$sql="UPDATE sslproxy_cert_error SET ".@implode(",", $edit_fields)." WHERE ID='$ID'";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}
function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $Types[1]="{sslproxy_cert_error_deny_explain}";
    $Types[2]="{sslproxy_cert_error_allow_explain}";
	
	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_hidden("enabled","1");
    $form[]=$tpl->field_text("description", "{rule_name}", null,true);
    $form[]=$tpl->field_array_checkboxes2Columns($Types, "ruletype", 2);
    $jsafter="LoadAjax('table-acls-sslErrs-rules','$page?table=yes');BootstrapDialog1.close();";
	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),null,"{add}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}



function rule_move(){
	$tpl=new template_admin();
	$ID=$_GET["acl-rule-move"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT zOrder FROM sslproxy_cert_error WHERE `ID`='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
	$xORDER_ORG=intval($ligne["zOrder"]);
	$xORDER=$xORDER_ORG;
	
	
	if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE sslproxy_cert_error SET zOrder=$xORDER WHERE `ID`='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if($_GET["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE sslproxy_cert_error SET zOrder=$xORDER2 WHERE `ID`<>'$ID' AND zOrder=$xORDER";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	}
	if($_GET["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE sslproxy_cert_error SET zOrder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}' AND zOrder=$xORDER";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	}

	$c=0;
	$sql="SELECT ID FROM sslproxy_cert_error ORDER BY zOrder";
	$results = $q->QUERY_SQL($sql);

	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE sslproxy_cert_error SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
		if($GLOBALS["VERBOSE"]){echo "UPDATE sslproxy_cert_error SET zOrder=$c WHERE `ID`={$ligne["ID"]}\n";}
		$c++;
	}


}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$add="Loadjs('$page?newrule-js=yes',true);";

    $jsrestart=$tpl->framework_buildjs(
        "/proxy/ssl/build",
        "squid.ssl.rules.articarest.progress",
        "squid.ssl.rules.progress.log",
        "progress-acls-restart",
    "LoadAjax('table-acls-sslErrs-rules','$page?table=yes');");


	$html[]=$tpl->_ENGINE_parse_body("		
	<table id='table-ssl-proxy-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:10px;'>");
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%'>{status}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%'>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center'>{enabled}</th>";
    $html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-firewall-rules','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$squid_acls_groups=new squid_acls_groups();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$results=$q->QUERY_SQL("SELECT * FROM sslproxy_cert_error ORDER BY zOrder");
	$TRCLASS=null;
	$acls=new squid_acls_groups();
	$and_text=$tpl->javascript_parse_text(" {and} ");

	$sslconf=explode("\n",@file_get_contents("/etc/squid3/ssl.bump.conf"));
    $PRODS=array();
	foreach ($sslconf as $line){
	    $line=trim($line);
	    if($line==null){continue;}
	    if(!preg_match("#SSLPROXY_CERT_ID:([0-9]+)#",$line,$re)){continue;}
	    $PRODS[$re[1]]=true;
    }

	$td1=$tpl->table_td1prc();

	foreach($results as $index=>$ligne) {

		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ACCESSEnabled=0;
		$ruletype=$ligne["ruletype"];
		$enabled=intval($ligne["enabled"]);
		$ID=$ligne["ID"];
		$rulename=$tpl->utf8_encode($ligne["description"]);
		$please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");
        $inprod="<span class='label'>{inactive2}</span>";

        if(isset($PRODS[$ID])){
            $inprod="<span class='label label-primary'>{active2}</span>";
        }

		$TTEXT=array();
		if($ruletype==2){
			$ACCESSEnabled=1;
			$TTEXT[]="<strong>{trust_ssl}</strong>";
		}else{
			$TTEXT[]="<strong>{deny_access}</strong>";
		}

		$objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"sslproxy_cert_error_sqacllinks");
        VERBOSE("Objects: ".count($objects),__LINE__);
			
		if(count($objects)>0){
			$explain=$acls->ACL_MULTIPLE_EXPLAIN($ligne['ID'],$ACCESSEnabled,0,"sslproxy_cert_error_sqacllinks")." {then} ".@implode($and_text, $TTEXT);
        }else{
            VERBOSE("Objects: NO_OBJECT",__LINE__);
			$explain="<div class=text-danger'>$please_specify_an_object</div>";
		}
		
		$ssl_img=icon_status($ruletype,$enabled,$objects);
        if($ssl_img==null){$ssl_img=$tpl->icon_nothing();}
		
		
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
		$js="Loadjs('$page?rule-id-js=$ID')";

        $up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");
        $rulename=$tpl->utf8_decode($rulename);
		
		$html[]="<tr class='$TRCLASS' id='acl-$ID'>";
		$html[]="<td $td1>$inprod</td>";
		$html[]="<td $td1><span id='ssl-rule-icon-$ID'>{$ssl_img}</span></td>";
		$html[]="<td style='text-align:left' nowrap>". $tpl->td_href($rulename,"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'><span id='ssl-rule-text-$ID'>$explain</span></td>";
		$html[]="<td $td1>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')")."</td>";
		$html[]="<td $td1>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td $td1>$delete</td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";


    //

    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/proxy-service/ssl/certificate-validation',1024,768,'certificate validation rules')";
    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
    $topbuttons[] = array($jshelp, ico_support, "WIKI");

    $TINY_ARRAY["TITLE"]="{certificate_validation}";
    $TINY_ARRAY["ICO"]="fas fa-file-certificate";
    $TINY_ARRAY["EXPL"]="{proxy_certificate_validation}";
    $TINY_ARRAY["URL"]="proxy-ssl-rules";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



	$html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-ssl-proxy-rules').footable({ \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) }); 
$jstiny
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function icon_status($ruletype=0,$enabled=0,$objects=array()){

    if($ruletype==2){

        $ssl_img="<span class='label label-primary'>{trust_ssl}</span>";

    }else{
        $ssl_img="<span class='label label-danger'>{deny_access}</span>";

    }

    if(count($objects)==0){
        $ssl_img="<div class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{error}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>";
    }


    if($enabled==0){
          $ssl_img="<span class='label'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{disabled}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
    }

    return $ssl_img;

}

function new_rule_save(){
	unset($_POST["newrule"]);
	$_POST["description"]=sqlite_escape_string2(url_decode_special_tool($_POST["description"]));
    $add_fields[]="`zDate`";
    $add_values[]="'".date("Y-m-d H:i:s")."'";

	foreach ($_POST as $key=>$val){
	
		$add_fields[]="`$key`";
		$add_values[]="'$val'";
		$edit_fields[]="`$key`='$val'";
	
	
	}
	$sql="INSERT INTO sslproxy_cert_error (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."<hr>$sql";}

}

function rule_delete($ID){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM sslproxy_cert_error WHERE ID='$ID'");
	return true;
}

