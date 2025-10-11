<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
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
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$error=null;


	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{forward_rules}</h1>
	<p>{forward_rules_explain}</p>
	</div>

	</div>



	<div class='row'><div id='progress-privoxy-apply'></div>
	<div class='ibox-content'>

	<div id='table-loader-proxy-forwardrules'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-proxy-forwardrules','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function enabled_js(){
	$aclid=$_GET["enabled-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_privoxy_acls WHERE aclid='$aclid'");
	$enabled=$ligne["enabled"];
	if($enabled==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE squid_privoxy_acls SET enabled=$enabled WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;}
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
	$q->QUERY_SQL("DELETE FROM privoxy_sqacllinks WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM squid_privoxy_acls WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;return;}
}




function rule_id_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["ruleid-js"];
	$title="{new_rule}";

	if($id>0){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_privoxy_acls WHERE aclid='$id'");
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
	if($aclid>0){
		$btname="{apply}";

		$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_privoxy_acls WHERE aclid='$aclid'");
		$title=$ligne["rulename"];
		$BootstrapDialog=null;
	}
	
	if(!isset($ligne["deny"])){$ligne["deny"]=1;}
	$deny[1]="{do_not_use_the_service}";
	$deny[0]="{use_the_service}";


	$tpl->field_hidden("rule-save", $aclid);
	$form[]=$tpl->field_text("rulename","{rule_name}",$ligne["rulename"],true,null,false);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_array_hash($deny, "deny", "{type}", $ligne["deny"]);
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
	echo $tpl->form_outside($title,@implode("\n", $form),"{privoxy_rule_explain_proxy}",$btname,"LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');$BootstrapDialog","AsSquidAdministrator");
}




function rule_main_save(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	if(!$users->AsSquidAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}

	$aclid=$_POST["rule-save"];
	$rulename=url_decode_special_tool($_POST["rulename"]);
	$rulename=mysql_escape_string2($rulename);






	if($aclid==0){
		$sqlB="INSERT INTO `squid_privoxy_acls` (`rulename`,`enabled` ,`zorder`,`deny`)
		VALUES ('$rulename','{$_POST["enabled"]}','{$_POST["zorder"]}','{$_POST["deny"]}')";
	}else{
		$sqlB="UPDATE `squid_privoxy_acls` SET `rulename`='$rulename',`enabled`='{$_POST["enabled"]}',
		`zorder`='{$_POST["zorder"]}',deny='{$_POST["deny"]}' WHERE aclid='$aclid'";
	}


	$q->QUERY_SQL($sqlB);
	if(!$q->ok){echo $q->mysql_error_html(true);}
}

function rule_tab(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["rule-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT rulename,logtype FROM squid_privoxy_acls WHERE aclid='$aclid'");



	$array["{rule}"]="$page?rule-settings=$aclid";
	if($aclid>0){
		$array["{objects}"]="fw.proxy.objects.php?aclid=$aclid&main-table=privoxy_sqacllinks&fast-acls=0";

	}
	echo $tpl->tabs_default($array);


}



function rule_move_js(){
	header("content-type: application/x-javascript");
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$tpl=new template_admin();
	$dir=$_GET["dir"];
	$aclid=intval($_GET["aclid"]);
	$ligne=$q->mysqli_fetch_array("SELECT zorder FROM squid_privoxy_acls WHERE aclid='$aclid'");
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
			$q->QUERY_SQL("UPDATE squid_privoxy_acls SET zorder='$zorder' WHERE aclid='$aclid'");
			if(!$q->ok){
			$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
			echo "alert('$q->mysql_error');";return;
			}

			$c=0;
			$results=$q->QUERY_SQL("SELECT aclid FROM squid_privoxy_acls ORDER BY zorder");
			foreach($results as $index=>$ligne) {
				$aclid=$ligne["aclid"];
				echo "// $aclid New order = $c";
			$q->QUERY_SQL("UPDATE squid_privoxy_acls SET zorder='$c' WHERE aclid='$aclid'");
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
				$order=$tpl->_ENGINE_parse_body("{order}");
				$rulename=$tpl->_ENGINE_parse_body("{rulename}");
				$interface=$tpl->_ENGINE_parse_body("{interface}");
				$type=$tpl->_ENGINE_parse_body("{type}");
				$title=$tpl->_ENGINE_parse_body("{nat_title}");
				$nic_from=$tpl->javascript_parse_text("{nic}");
				$ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
				$t=$_GET["t"];
				if(!is_numeric($t)){$t=time();}
				$tablesize=868;
				$descriptionsize=705;
				$bts=array();

				$delete=$tpl->javascript_parse_text("{delete}");
				$about=$tpl->javascript_parse_text("{about2}");
				$type=$tpl->javascript_parse_text("{type}");
				$reconstruct=$tpl->javascript_parse_text("{apply_firewall_rules}");
				$description=$tpl->_ENGINE_parse_body("{description}");


				$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `squidlogs`.`privoxy_sqacllinks` ( `zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,`aclid` BIGINT UNSIGNED , `negation` smallint(1) NOT NULL ,`gpid` INT UNSIGNED , `zOrder` INT( 10 ) NOT NULL ,INDEX ( `aclid` , `gpid`,`negation`),KEY `zOrder`(`zOrder`)	)  ENGINE = MYISAM;");

				$t=time();
				$add="Loadjs('$page?ruleid-js=0',true);";
				if(!$users->AsSquidAdministrator){$add="alert('$ERROR_NO_PRIVS2')";}



		$ARRAY=array();
		$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress";
		$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.txt";
		$ARRAY["CMD"]="privoxy.php?restart=yes";
		$ARRAY["TITLE"]="{restart_service}";
		$prgress=base64_encode(serialize($ARRAY));
		$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-privoxy-apply');";

				$html[]=$tpl->_ENGINE_parse_body("

						<div class=\"btn-group\" data-toggle=\"buttons\">
						<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
						<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>
						</div>
						<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
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

	$jsAfter="LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$Tdeny[1]="<strong class='text-danger'>{do_not_use_the_service}</strong>";
	$Tdeny[0]="<strong class='text-success'>{use_the_service}</strong>";

	$isRights=isRights();
	$results=$q->QUERY_SQL("SELECT * FROM squid_privoxy_acls ORDER BY zorder");
	$TRCLASS=null;
	foreach($results as $index=>$ligne) {
	$square_class="text-navy";
	$pproxy=array();
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$rulename=$ligne["rulename"];
	$rulenameenc=urlencode($rulename);
	$aclid=$ligne["aclid"];
	$enabled=$ligne["enabled"];
	$addlogtype=null;
	$deny=$ligne["deny"];
	
	
	$check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$aclid')");
				
	$up=$tpl->icon_up("Loadjs('$page?move-js=yes&aclid=$aclid&dir=up')");
	$down=$tpl->icon_down("Loadjs('$page?move-js=yes&aclid=$aclid&dir=down')");
	$js="Loadjs('$page?ruleid-js=$aclid',true);";
	$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$aclid}&name=$rulenameenc')");
						
	
	if(!$isRights){$up=null;$down=null;$delete=null;}
						
						
	$explain=$tpl->_ENGINE_parse_body("{for_objects} ".proxy_objects($aclid)." {then} {$Tdeny[$deny]}");
						

	$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
	$html[]="<td class=\"center\"><button type='button' class='btn btn-default btn-bitbucket' OnClick=\"javascript:$js\" ><i class='fa fa-paste'></i></button></td>";
	$html[]="<td class=\"center\">$check</td>";
	$html[]="<td style='vertical-align:middle'>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-weight:bold'>{$rulename}:</a>&nbsp;&raquo;&nbsp;$explain</span></td>";
	$html[]="<td class=\"center\">$up&nbsp;&nbsp;$down</td>";
	$html[]="<td class=center><center>$delete</center></td>";
	$html[]="</tr>";
	
	}

	$explain=$tpl->_ENGINE_parse_body("{privoxy_default_rule}");
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT * FROM proxy_ports WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
	$html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";
	$html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";
	$html[]="<td style='vertical-align:middle'>".$tpl->_ENGINE_parse_body("<strong>{default}</strong>:</a>&nbsp;&raquo;&nbsp;{privoxy_default_rule}")."</td>";
	$html[]="<td class=\"center\">&nbsp;&nbsp;</td>";
	$html[]="<td class=center><center>".$tpl->icon_nothing()."</center></td>";
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
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

				echo @implode("\n", $html);

			}
function isRights(){
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
}

function proxy_objects($aclid){

				$tpl=new template_admin();
				$tablelink="privoxy_sqacllinks";
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
				if(!$q->ok){return;}

				foreach($results as $index=>$ligne) {
					$gpid=$ligne["gpid"];
					$js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
					$neg_text="{is}";
					if($ligne["negation"]==1){$neg_text="{is_not}";}
					$GroupName=$ligne["GroupName"];
					$tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-weight:bold'>$GroupName</a> (".$q->acl_GroupType[$ligne["GroupType"]].")";
				}

				if(count($tt)>0){
					return @implode("<br>{and} ", $tt);

				}else{
					return "{all}";
				}


			}