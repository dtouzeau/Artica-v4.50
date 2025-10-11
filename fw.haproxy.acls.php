<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["start-js"])){start_js();exit;}
if(isset($_GET["stop-js"])){stop_js();exit;}
if(isset($_GET["sub-table"])){table_sub();exit;}
if(isset($_GET["ruleid-js"])){ruleid_js();exit;}
if(isset($_GET["ruleid-mv"])){ruleid_move();exit;}
if(isset($_GET["ruleid-delete"])){ruleid_delete();exit;}
if(isset($_GET["ruleid-tabs"])){ruleid_tabs();exit;}
if(isset($_GET["ruleid-parameters"])){ruleid_main();exit;}
if(isset($_POST["ruleid"])){ruleid_main_save();exit;}

if(isset($_GET["form-action"])){ruleid_main_action();exit;}
if(isset($_GET["ruleid-objects"])){objects_popup();exit;}
if(isset($_GET["ruleid-objects-table"])){objects_table();exit;}

if(isset($_GET["newgroup-js"])){object_new_js();exit;}
if(isset($_GET["newgroup-popup"])){object_new_popup();exit;}
if(isset($_POST["newgroup"])){object_new_save();exit;}

if(isset($_GET["linkgroup-js"])){object_link_js();exit;}
if(isset($_GET["linkgroup-popup"])){object_link_popup();exit;}
if(isset($_POST["link-group"])){object_link_save();exit;}

if(isset($_GET["groupid-js"])){object_js();exit;}
if(isset($_GET["groupid-tab"])){object_tab();exit;}
if(isset($_GET["groupid-parameters"])){object_parameters();exit;}
if(isset($_POST["groupid"])){object_parameters_save();exit;}
if(isset($_GET["groupid-items"])){object_items();exit;}
if(isset($_POST["groupitem"])){object_items_save();exit;}

if(isset($_GET["object-negation-js"])){object_negation_js();exit;}
if(isset($_GET["object-operator-js"])){object_operator_js();exit;}
if(isset($_GET["object-mv"])){object_move_js();exit;}
if(isset($_GET["object-delete"])){object_unlink();exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{load_balancing} &nbsp;&raquo;&nbsp; {ACLS}</h1>
		<p>{APP_HAPROXY_ACLS}</p>
	</div>

	</div>



	<div class='row'><div id='progress-haproxy-restart'></div>
	<div class='col-lg-12'>
		<div id='table-haproxy-acls'></div>
	</div>
	</div>



	<script>
	LoadAjaxSilent('table-haproxy-acls','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function object_move_js(){
	$mkey=$_GET["object-mv"];
	$direction=$_GET["direction"];
	$aclid=$_GET["ruleid"];
	$table="haproxy_acls_link";
	//up =1, Down=0
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$sql="SELECT zorder FROM haproxy_acls_link WHERE ID='$mkey'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	$OlOrder=$ligne["zorder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE haproxy_acls_link SET zorder='$OlOrder' WHERE zorder='$NewOrder' AND ruleid='$aclid'";
	//	echo $sql."\n";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sql="UPDATE haproxy_acls_link SET zorder='$NewOrder' WHERE ID='$mkey'";
	$q->QUERY_SQL($sql,"artica_backup");
	//	echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}
	
	$results=$q->QUERY_SQL("SELECT zmd5 FROM haproxy_acls_link WHERE ruleid='$aclid' ORDER BY zOrder");
	$c=1;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$zmd5=$ligne["ID"];
		$q->QUERY_SQL("UPDATE haproxy_acls_link SET zorder='$c' WHERE ID='$zmd5'");
		$c++;
	
	}	
}
function object_unlink(){
	$tpl=new template_admin();
	$md5=$_GET["object-delete"];
	$md=$_GET["md"];
	$sql="DELETE FROM haproxy_acls_link WHERE ID='$md5'";
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	header("content-type: application/x-javascript");
	echo "$('#$md').remove();";
}


function object_negation_js(){
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$tpl=new template_admin();
	$md5=$_GET["object-negation-js"];
	$ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_acls_link WHERE ID='$md5'");
	if($ligne["revert"]==0){$revert=1;}else{$revert=0;}
	$sql="UPDATE haproxy_acls_link SET revert=$revert WHERE ID='$md5'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?reload-haproxy=yes");
}
function object_link_save(){
	$aclid=$_POST["link-group"];
	$gpid=$_POST["groupid"];
	
	$sql="INSERT OR IGNORE INTO haproxy_acls_link (ruleid,groupid,zorder) VALUES('$aclid','$gpid',1)";
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function object_operator_js(){
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$tpl=new template_admin();
	$md5=$_GET["object-operator-js"];
	$ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_acls_link WHERE ID='$md5'");
	if($ligne["operator"]==0){$revert=1;}else{$revert=0;}
	$sql="UPDATE haproxy_acls_link SET operator=$revert WHERE ID='$md5'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
	
	$acl=new haproxy();
	$operator=$acl->acl_operator[$revert];
	$md=$_GET["md"];
	header("content-type: application/x-javascript");
	echo "document.getElementById('operator-$md').innerHTML='".$tpl->_ENGINE_parse_body($operator)."';";
	
	$sock=new sockets();
	$sock->getFrameWork("services.php?reload-haproxy=yes");
	
	
}

function object_new_js(){
	$page=CurrentPageName();
	$ruleid=intval($_GET["newgroup-js"]);
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT servicename,rulename FROM haproxy_acls_rules WHERE ID='$ruleid'");
	$servicename=$ligne["servicename"];
	$rulename=$ligne["rulename"];
	$title="$rulename/$servicename {new_group}";
	$tpl->js_dialog2($title, "$page?newgroup-popup=$ruleid");
}
function object_link_js(){
	$page=CurrentPageName();
	$ruleid=intval($_GET["linkgroup-js"]);
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT servicename,rulename FROM haproxy_acls_rules WHERE ID='$ruleid'");
	$servicename=$ligne["servicename"];
	$rulename=$ligne["rulename"];
	$title="$rulename/$servicename {link_group}";
	$tpl->js_dialog2($title, "$page?linkgroup-popup=$ruleid");
	
}

function object_link_popup(){
	$page=CurrentPageName();
	$ruleid=intval($_GET["linkgroup-popup"]);
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT servicename,rulename FROM haproxy_acls_rules WHERE ID='$ruleid'");
	$servicename=$ligne["servicename"];
	$rulename=$ligne["rulename"];
	$title="$rulename/$servicename {link_group}";
	
	$sql="SELECT *  FROM `haproxy_acls_groups` ORDER BY groupname";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$haproxy=new haproxy();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
	
		$ligne['groupname']=utf8_encode($ligne['groupname']);
		$GroupTypeText=$tpl->_ENGINE_parse_body($haproxy->acl_GroupType[$ligne["grouptype"]]);
		$ligne2=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM haproxy_acls_items WHERE groupid='{$ligne['ID']}'");
		$CountDeMembers=intval($ligne2["tcount"]);
		if(!$q->ok){$CountDeMembers=$q->mysql_error;}
		if($ligne["grouptype"]=="all"){$CountDeMembers="*";}
		$main[$ligne["ID"]]="{$ligne['groupname']} ($GroupTypeText - $CountDeMembers ". $tpl->javascript_parse_text("{elements})");
	}
	
	$form[]=$tpl->field_hidden("link-group", $ruleid);
	$form[]=$tpl->field_array_hash($main, "groupid", "{objects}", null,true);
	$js[]="dialogInstance2.close()";
	$js[]="RefreshIboxTables()";
	echo $tpl->form_outside($title, @implode("\n", $form),null,"{link_object}",@implode(";", $js),"AsSquidAdministrator");
	
}


function object_js(){
	$page=CurrentPageName();
	$groupid=intval($_GET["groupid-js"]);
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT groupname FROM haproxy_acls_groups WHERE ID='{$groupid}'");
	$title=$tpl->javascript_parse_text($ligne["groupname"]);
	$tpl->js_dialog2($title, "$page?groupid-tab=$groupid");
}
function object_tab(){
	$page=CurrentPageName();
	$groupid=intval($_GET["groupid-tab"]);
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT groupname FROM haproxy_acls_groups WHERE ID='{$groupid}'");
	$title=$tpl->javascript_parse_text($ligne["groupname"]);
	$array["{$title}"]="$page?groupid-parameters=$groupid";
	$array["{items}"]="$page?groupid-items=$groupid";
	echo $tpl->tabs_default($array);
}
function object_parameters(){
	$page=CurrentPageName();
	$groupid=intval($_GET["groupid-parameters"]);
	$tpl=new template_admin();
	$haproxy=new haproxy();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_acls_groups WHERE ID='{$groupid}'");
	$title=$tpl->javascript_parse_text($ligne["groupname"]);
	$form[]=$tpl->field_hidden("groupid", $groupid);
	$form[]=$tpl->field_info("grouptype", "{type}", $haproxy->acl_GroupType[$ligne["grouptype"]]);
	$form[]=$tpl->field_text("groupname", "{groupname}", $ligne["groupname"],true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
	
	$js[]="RefreshIboxTables()";
	echo $tpl->form_outside($title, @implode("\n", $form),null,"{apply}",@implode(";", $js),"AsSquidAdministrator");
}
function object_parameters_save(){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$tpl->CLEAN_POST();
	$groupid=intval($_POST["groupid"]);
	$groupname=sqlite_escape_string2($_POST["groupname"]);
	$enabled=$_POST["enabled"];
	$sql="UPDATE haproxy_acls_groups SET groupname='$groupname',enabled='$enabled' WHERE ID=$groupid";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}
function object_items(){
	$page=CurrentPageName();
	$groupid=intval($_GET["groupid-items"]);
	$tpl=new template_admin();
	$haproxy=new haproxy();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_acls_groups WHERE ID='{$groupid}'");
	
	$GroupType=$ligne["grouptype"];
	$title=$tpl->javascript_parse_text($ligne["groupname"]." ($GroupType)");
	if($GroupType=="src"){$explain="{acl_src_text}";}
	if($GroupType=="dst"){$explain="{acl_dst_text}";}
	if($GroupType=="arp"){$explain="{ComputerMacAddress}";}
	if($GroupType=="dstdomain"){$explain="{squid_ask_domain}";}
	if($GroupType=="maxconn"){$explain="{squid_aclmax_connections_explain}";}
	if($GroupType=="port"){$explain="{acl_squid_remote_ports_explain}";}
	if($GroupType=="ext_user"){$explain="{acl_squid_ext_user_explain}";}
	if($GroupType=="req_mime_type"){$explain="{req_mime_type_explain}";}
	if($GroupType=="rep_mime_type"){$explain="{rep_mime_type_explain}";}
	if($GroupType=="referer_regex"){$explain="{acl_squid_referer_regex_explain}";}
	if($GroupType=="srcdomain"){$explain="{acl_squid_srcdomain_explain}";}
	if($GroupType=="url_regex_extensions"){$explain="{url_regex_extensions_explain}";}
	if($GroupType=="max_user_ip"){$explain="<b>{acl_max_user_ip_title}</b><br>{acl_max_user_ip_text}";}
	//if($GroupType=="quota_time"){$explain="{acl_quota_time_text}";}
	if($GroupType=="quota_size"){$explain="{acl_quota_size_text}";}
	if($GroupType=="ssl_sni"){$explain="{acl_ssl_sni_text}";}
	if($GroupType=="myportname"){$explain="{acl_myportname_text}";}
	if($GroupType=="hdr(host)"){$explain="{squid_ask_domain}";}
	
	$sql="SELECT * FROM haproxy_acls_items WHERE groupid='$groupid'";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$itemz[]=$ligne["pattern"];
	}
	
	$form[]=$tpl->field_hidden("groupitem", $groupid);
	$form[]=$tpl->field_textareacode("items", null, @implode("\n", $itemz));
	$js[]="RefreshIboxTables()";
	echo $tpl->form_outside($title, @implode("\n", $form),$explain,"{apply}",@implode(";", $js),"AsSquidAdministrator");
	
}
function object_items_save(){
	$page=CurrentPageName();
	$groupid=intval($_POST["groupitem"]);
	$tpl=new template_admin();
	$haproxy=new haproxy();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$tpl->CLEAN_POST();
	$sql="DELETE FROM haproxy_acls_items WHERE groupid='$groupid'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$tt=array();
	$f=explode("\n",$_POST["items"]);
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		$line=sqlite_escape_string2($line);
		$tt[]="('$groupid','$line')";
	}
	if(count($tt)==0){return;}
	$sql="INSERT OR IGNORE INTO haproxy_acls_items(groupid,pattern) VALUES ".@implode(",", $tt);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?reload-haproxy=yes");
	
	
}

function object_new_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$haproxy=new haproxy();
	$ruleid=intval($_GET["newgroup-popup"]);
	$ligne=$q->mysqli_fetch_array("SELECT servicename,rulename FROM haproxy_acls_rules WHERE ID='$ruleid'");
	$servicename=$ligne["servicename"];
	$rulename=$ligne["rulename"];
	$title="$rulename/$servicename {new_group}";
	
	$form[]=$tpl->field_hidden("newgroup", $ruleid);
	$form[]=$tpl->field_text("groupname", "{groupname}", null,true);
	$form[]=$tpl->field_array_hash($haproxy->acl_GroupType, "ztype", "{type}", null,true);
	
	
	$js[]="dialogInstance2.close()";
	$js[]="RefreshIboxTables()";
	echo $tpl->form_outside($title, @implode("\n", $form),null,"{add}",@implode(";", $js),"AsSquidAdministrator");
}
function object_new_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$tpl->CLEAN_POST();
	$ruleid=$_POST["newgroup"];
	
	$groupname=sqlite_escape_string2($_POST["groupname"]);
	$grouptype=$_POST["ztype"];
	$enabled=1;
	
	$sql="INSERT OR IGNORE INTO haproxy_acls_groups (groupname,grouptype,enabled) VALUES ('$groupname','$grouptype','$enabled')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$gpid=$q->last_id;
	if($gpid==0){echo "Unable to obtain last id!\n";return;}
	
	$sql="INSERT OR IGNORE INTO haproxy_acls_link (ruleid,groupid,operator) VALUES ('$ruleid','$gpid','0')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}


function ruleid_delete(){
	$ID=intval($_GET["ruleid-delete"]);
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$q->QUERY_SQL("DELETE FROM haproxy_acls_link WHERE ruleid='$ID'");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
	$q->QUERY_SQL("DELETE FROM haproxy_acls_rules WHERE ID='$ID'");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
	header("content-type: application/x-javascript");
	echo "$('#{$_GET["md"]}').remove();";
	$sock=new sockets();
	$sock->getFrameWork("services.php?reload-haproxy=yes");
}

function ruleid_move(){
	$ID=$_GET["ruleid-mv"];
	$direction=$_GET["direction"];
	$servicename=$_GET["servicename"];
	$table="webfilters_sqacllinks";
	//up =1, Down=0
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$sql="SELECT zorder FROM haproxy_acls_rules WHERE ID='$ID'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));

	$OlOrder=$ligne["zorder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}

	$sql="UPDATE haproxy_acls_rules SET zorder='$OlOrder' WHERE zorder='$NewOrder' AND ID='$ID'";
	//	echo $sql."\n";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sql="UPDATE haproxy_acls_rules SET zorder='$NewOrder' WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	//	echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT ID FROM haproxy_acls_rules WHERE servicename='$servicename' ORDER BY zorder");
	$c=1;
	while ($ligne = mysqli_fetch_assoc($results)) {$zmd5=$ligne["zmd5"];$q->QUERY_SQL("UPDATE haproxy_acls_rules SET zorder='$c' WHERE ID='$zmd5'");$c++;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?reload-haproxy=yes");
}
function objects_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$servicename=urlencode($_GET["servicename"]);
	$ruleid=$_GET["ruleid-objects"];
	
	$ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_acls_rules WHERE ID='$ruleid'");
	$servicename=$ligne["servicename"];
	$rulename=$ligne["rulename"];
	$title="$rulename/$servicename {objects}";
	

	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?newgroup-js=$ruleid&servicename=$servicename');\">";
	$html[]="<i class='fa fa-plus'></i> {new_object} </label>";
	
	$html[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?linkgroup-js=$ruleid&servicename=$servicename');\">";
	$html[]="<i class='fa fa-plus'></i> {link_object} </label>";
	
	$html[]="</div>";
	$html[]=$tpl->table_ibox("$page?ruleid-objects-table=$ruleid&servicename=$servicename",$ligne["servicename"]);
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}
function objects_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$servicename=urlencode($_GET["servicename"]);
	$ruleid=$_GET["ruleid-objects-table"];
	
	$html[]="			<table class='table table-striped'>";
	$html[]="				<thead>";
	$html[]="					<tr>";
	$html[]="						<th nowrap>{order}</th>";
	$html[]="						<th nowrap>{objects}</th>";
	$html[]="						<th nowrap>{reverse}</th>";
	$html[]="						<th>{operator}</th>";
	$html[]="						<th nowrap>{items}</th>";
	$html[]="						<th>mv</th>";
	$html[]="						<th>Del</th>";
	$html[]="					</tr>";
	$html[]="				</thead>";
	$html[]="				<tbody>";
	
	

$sql="SELECT haproxy_acls_link.groupid,
	haproxy_acls_link.ID as tid,
	haproxy_acls_link.revert,
	haproxy_acls_link.operator,
	haproxy_acls_link.zorder as torder,
	haproxy_acls_groups.* FROM haproxy_acls_link,haproxy_acls_groups 
	WHERE haproxy_acls_link.groupid=haproxy_acls_groups.ID AND haproxy_acls_link.ruleid=$ruleid
	ORDER BY haproxy_acls_link.zorder";
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$acl=new haproxy();
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		$val=0;
		$mkey=$ligne["tid"];
		$arrayF=$acl->FlexArray($ligne['ID']);
		$delete=imgsimple("delete-24.png",null,"DeleteObjectLinks('$mkey')");
		
		$operator=$ligne["operator"];
		$operator=$acl->acl_operator[$operator];
		$operator=$tpl->_ENGINE_parse_body($operator);
		$up=imgsimple("arrow-up-16.png","","AclGroupUpDown('$mkey',0)");
		$down=imgsimple("arrow-down-18.png","","AclGroupUpDown('$mkey',1)");
		if($ligne["torder"]==1){$up=null;}
		if($ligne["torder"]==0){$up=null;}
		
		$operator="
		<a href=\"javascript:blur()\" OnClick=\"Loadjs('$page?object-operator-js=$mkey&md=$md')\"
		style='text-decoration:underline;font-weight:bolder' id='operator-$md'>$operator</a>";
		
		
		$negation=$tpl->icon_check($ligne["revert"],"Loadjs('$page?object-negation-js=$mkey')",null,"AsSquidAdministrator");
		$up=$tpl->icon_up("Loadjs('$page?object-mv=$mkey&direction=0&ruleid=$ruleid')","AsProxyMonitor");
		$down=$tpl->icon_down("Loadjs('$page?object-mv=$mkey&direction=1&ruleid=$ruleid')","AsProxyMonitor");
		$del=$tpl->icon_unlink("Loadjs('$page?object-delete=$mkey&md=$md')","AsSquidAdministrator");
		
		
		$html[]="";
		$html[]="<tr id='$md'>";
		$html[]="<td width=1% class='center' nowrap>{$ligne["torder"]}</center></td>";
		$html[]="<td>{$arrayF["ROW"]}</td>";
		$html[]="<td width=1% class='center' nowrap>$negation</center></td>";
		$html[]="<td width=1% nowrap><center >$operator</center></td>";
		$html[]="<td width=1% nowrap>{$arrayF["ITEMS"]}</td>";
		$html[]="<td width=1% nowrap>&nbsp;$up&nbsp;$down&nbsp;</td>";
		$html[]="<td width=1% nowrap>$del</td>";
		$html[]="</tr>";
		$html[]="";
	}
	
	$html[]="				</tbody>";
	$html[]="			</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	
}


function ruleid_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$servicename=$_GET["servicename"];
	$ruleid=$_GET["ruleid-js"];
	if($ruleid==0){$title="{new_rule}";}else{
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_acls_rules WHERE ID='$ruleid'");
		$servicename=$ligne["servicename"];
		$rulename=$ligne["rulename"];
		$title="$servicename/$rulename";
	}
	$servicenameenc=urlencode($servicename);
	
	$tpl->js_dialog1($title, "$page?ruleid-tabs=$ruleid&servicename=$servicenameenc");
}
function ruleid_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ruleid=$_GET["ruleid-tabs"];
	$servicename=$_GET["servicename"];
	if($ruleid==0){$title="{new_rule}";}else{
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_acls_rules WHERE ID='$ruleid'");
		$servicename=$ligne["servicename"];
		$rulename=$ligne["rulename"];
		$title="$rulename";
	}
	
	
	
	$servicenameenc=urlencode($servicename);
	
	$array["{$title}"]="$page?ruleid-parameters=$ruleid&servicename=$servicenameenc";
	if($ruleid>0){
		$array["{objects}"]="$page?ruleid-objects=$ruleid&servicename=$servicenameenc";
	}
	
	echo $tpl->tabs_default($array);
	
}

function ruleid_main(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$haproxy=new haproxy();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$ruleid=$_GET["ruleid-parameters"];
	$servicename=$_GET["servicename"];
	
	$btname="{add}";
	$results = $q->QUERY_SQL("SELECT servicename FROM haproxy GROUP BY servicename ORDER BY servicename");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$services[$ligne["servicename"]]=$ligne["servicename"];
	}
	$js[]="RefreshIboxTables()";
	if($ruleid==0){
		$title="{new_rule}";
		$js[]="dialogInstance1.close()";
		$js[]="LoadAjaxSilent('table-haproxy-acls','$page?table=yes')";
	}else{
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_acls_rules WHERE ID='$ruleid'");
		$servicename=$ligne["servicename"];
		$rulename=$ligne["rulename"];
		$title="$servicename/$rulename";
		$btname="{apply}";
	}
	$servicenameenc=urlencode($servicename);
	$form[]=$tpl->field_hidden("ruleid", $ruleid);
	$form[]=$tpl->field_text("rulename", "{rulename}", $ligne["rulename"],true);
	$form[]=$tpl->field_array_hash($services, "servicename","{tcp_services}", $servicename,true);
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
	if($ruleid>0){
		$form[]=$tpl->field_array_hash($haproxy->acls_actions, "rule_action","{method}", $ligne["rule_action"],true,null,"Rulid{$ruleid}");
	}
	$html[]=$tpl->form_outside($title, @implode("\n", $form),null,$btname,@implode(";", $js),"AsSquidAdministrator");
	
	$html[]="<div id='ruleid-$ruleid'></div>";
	$html[]="<script>";
	$html[]="function Rulid{$ruleid}(action){";
	$html[]="\tLoadAjaxSilent('ruleid-$ruleid','$page?form-action=$ruleid&servicename=$servicenameenc&method='+action);";
	$html[]="}";
	if($ruleid>0){$html[]="Rulid{$ruleid}('{$ligne["rule_action"]}');";}
	$html[]="</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function ruleid_main_action(){
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$tpl=new template_admin();
	$ruleid=$_GET["form-action"];
	$method=intval($_GET["method"]);
	if($method==0){return;}
	$servicename=$_GET["servicename"];
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$ARRAY=array();
	$ligne=$q->mysqli_fetch_array("SELECT rule_action_data FROM haproxy_acls_rules WHERE ID='$ruleid'");
	$rule_action_data=$ligne["rule_action_data"];
	$form[]=$tpl->field_hidden("ruleid", $ruleid);
	$form[]=$tpl->field_hidden("rule_action", $method);
	if($method==1){
		$sql="SELECT ID,groupname FROM haproxy_backends_groups WHERE servicename='$servicename'";
		$results = $q->QUERY_SQL($sql,'artica_backup');
		while ($ligne = mysqli_fetch_assoc($results)) {
			$ARRAY[$ligne["ID"]]=$ligne["groupname"];
		}
		$form[]=$tpl->field_array_hash($ARRAY,"rule_action_data","{item}",$rule_action_data);
		
	
	}
	
	if($method==2){
		$sql="SELECT ID,backendname FROM haproxy_backends WHERE servicename='$servicename'";
		$results = $q->QUERY_SQL($sql,'artica_backup');
		while ($ligne = mysqli_fetch_assoc($results)) {
			$ARRAY[$ligne["ID"]]=$ligne["backendname"];
		}
		
		$form[]=$tpl->field_array_hash($ARRAY,"rule_action_data","{item}",$rule_action_data);
	
	}
	
	if($method==3){
		$ARRAY[null]="{deny}";
		$form[]=$tpl->field_array_hash($ARRAY,"rule_action_data","{item}",$rule_action_data);
		
	}
	$js[]="RefreshIboxTables()";
	
	$haproxy=new haproxy();
	$title="{method}: ".$haproxy->acls_actions[$method];
	echo "<hr>";
	echo $tpl->form_outside($title, @implode("\n", $form),null,"{apply}",@implode(";", $js),"AsSquidAdministrator");
	
}

function ruleid_main_save(){
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$f_fields[]="rulename";
	$f_fields[]="servicename";
	$f_fields[]="zorder";
	$f_fields[]="rule_action";
	$f_fields[]="rule_action_data";
	
	foreach($f_fields as $field){
		if(!isset($_POST[$field])){continue;}
		$sqladdF[]=$field;
		$sqladdV[]="'".sqlite_escape_string2($_POST[$field])."'";
		$sqlupd[]="`$field`='".sqlite_escape_string2($_POST[$field])."'";
		
	}
	
	
	$id=intval($_POST["ruleid"]);
	$rulename=sqlite_escape_string2($_POST["rulename"]);
	if($id==0){
		$sql="INSERT OR IGNORE INTO haproxy_acls_rules (" .@implode(",", $sqladdF).") VALUES (".@implode(",", $sqladdV).")";
	}else{
		$sql="UPDATE haproxy_acls_rules SET ". @implode(",", $sqlupd)." WHERE ID='$id'";
		
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?ruleid-js=0&servicename=');\">";
	$html[]="<i class='fa fa-plus'></i> {new_rule} </label>";
	$html[]="</div>";

	
	$results = $q->QUERY_SQL("SELECT servicename FROM haproxy_acls_rules GROUP BY servicename ORDER BY servicename");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$html[]=$tpl->table_ibox("$page?sub-table=".urlencode($ligne["servicename"]),$ligne["servicename"]);
	}
	
		
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function table_sub(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$servicename=$_GET["sub-table"];
	$servicenameenc=urlencode($servicename);
	$html[]="			<table class='table table-striped'>";
	$html[]="				<thead>";
	$html[]="					<tr>";
	$html[]="						<th nowrap>{rule}</th>";
	$html[]="						<th nowrap>{description}</th>";
	$html[]="						<th nowrap>mv</th>";
	$html[]="						<th>Del</th>";
	$html[]="					</tr>";
	$html[]="				</thead>";
	$html[]="				<tbody>";
	
	$results = $q->QUERY_SQL("SELECT * FROM haproxy_acls_rules WHERE servicename='$servicename' ORDER BY zorder");
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$color=null;
		$md=md5(serialize($ligne));
		$EXPLAIN=EXPLAIN_THIS_RULE($ligne["ID"]);
		$rulename=trim($ligne["rulename"]);
		if($rulename==null){$rulename=$tpl->_ENGINE_parse_body("{rule} {$ligne["ID"]}");}
		if($ligne["rule_action"]==0){$color="color:#8a8a8a";}
		$js="Loadjs('$page?ruleid-js={$ligne["ID"]}&servicename=$servicenameenc')";
		
		$up=$tpl->icon_up("Loadjs('$page?ruleid-mv={$ligne["ID"]}&servicename=$servicenameenc&direction=0')","AsProxyMonitor");
		$down=$tpl->icon_down("Loadjs('$page?ruleid-mv={$ligne["ID"]}&servicename=$servicenameenc&direction=1')","AsProxyMonitor");
		$del=$tpl->icon_delete("Loadjs('$page?ruleid-delete={$ligne["ID"]}&md=$md')","AsSquidAdministrator");
		
		$html[]="";
		$html[]="<tr id='$md'>";
		$html[]="<td style='$color'>". $tpl->td_href($rulename,"{click_to_edit}",$js)."</td>";
		$html[]="<td style='$color'>$EXPLAIN</td>";
		$html[]="<td width=1% nowrap>&nbsp;$up&nbsp;$down&nbsp;</td>";
		$html[]="<td width=1% nowrap>&nbsp;$del&nbsp;</td>";
		$html[]="</tr>";
		$html[]="";
	}
	
	$html[]="				</tbody>";
	$html[]="			</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function EXPLAIN_THIS_RULE($ruleid){
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$tpl=new templates();
	$ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_acls_rules WHERE ID='$ruleid'");
	if(!$q->ok){return $q->mysql_error;}
	$haproxy=new haproxy();
	$rule_action=$ligne["rule_action"];
	$rule_action_data=$ligne["rule_action_data"];
	if($rule_action==0){return $tpl->_ENGINE_parse_body("{do_nothing}");}

	if($rule_action==1){
		$ligne=$q->mysqli_fetch_array("SELECT groupname FROM haproxy_backends_groups WHERE ID='$rule_action_data'");
		$to=$ligne["groupname"];
		$rule_action_text=$tpl->_ENGINE_parse_body($haproxy->acls_actions[$rule_action] ."{to} $to");
	}

	if($rule_action==2){
		$ligne=$q->mysqli_fetch_array("SELECT backendname FROM haproxy_backends WHERE ID='$rule_action_data'");
		$to=$ligne["backendname"];
		$rule_action_text=$tpl->_ENGINE_parse_body($haproxy->acls_actions[$rule_action])." {to} $to";
	}

	if($rule_action==3){
		$rule_action_text=$tpl->_ENGINE_parse_body("{deny_access}");
	}

	$table="SELECT haproxy_acls_link.groupid,
	haproxy_acls_link.ID as tid,
	haproxy_acls_link.revert,
	haproxy_acls_link.operator,
	haproxy_acls_link.zorder as torder,
	haproxy_acls_groups.* FROM haproxy_acls_link,haproxy_acls_groups
	WHERE haproxy_acls_link.groupid=haproxy_acls_groups.ID AND
	haproxy_acls_link.ruleid=$ruleid ORDER BY haproxy_acls_link.zorder";

	$results = $q->QUERY_SQL($table,"artica_backup");

	$acl=new haproxy();


	$c=0;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$revert=$ligne["revert"];
		$revert_text=null;
		if($revert==1){$revert_text="{not} ";}
		$operator=$ligne["operator"];
		$operator=$acl->acl_operator[$operator];
		$operator=$tpl->_ENGINE_parse_body($operator)." ";
		if($c==0){$operator=null;}
		$arrayF=$acl->FlexArray($ligne['ID']);
		$items=$arrayF["ITEMS"];
		if($items==0){continue;}
		$f[]="$operator$revert_text{$arrayF["ROW"]} ($items {items})";
		$c++;
	}

	if(count($f)==0){return $tpl->_ENGINE_parse_body("{do_nothing} ({no_group_defined})");}
	return $tpl->_ENGINE_parse_body("{for_objects} ".@implode("<br>", $f)."<br>{then} $rule_action_text</span>");
}