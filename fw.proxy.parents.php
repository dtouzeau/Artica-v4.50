<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_main_save();exit;}
if(isset($_GET["proxies"])){proxies();exit;}
if(isset($_GET["proxies-list"])){proxies_list();exit;}
if(isset($_GET["fiche-proxy-js"])){proxy_fiche_js();exit;}
if(isset($_GET["fiche-proxy"])){proxy_fiche();exit;}
if(isset($_GET["delete-proxy-js"])){proxy_delete_js();exit;}
if(isset($_POST["proxy-aclid"])){proxy_fiche_save();exit;}
if(isset($_POST["delete-proxy"])){proxy_delete();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
page();

function page():bool{
	$page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{parent_proxies}","fa fa-sitemap",
        "{parent_proxies_explain}","$page?table=yes","proxy-parents-rules",
        "progress-firehol-restart",null,"table-loader-proxy-parents");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{parent_proxies}",$html);
        echo $tpl->build_firewall();
        return true;
    }


	echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function enabled_js():bool{
	$aclid=$_GET["enabled-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_parents_acls WHERE aclid='$aclid'");
	$enabled=$ligne["enabled"];
	if($enabled==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE squid_parents_acls SET enabled=$enabled WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks("Proxy Parent, activate rule $aclid");
}

function rule_delete_js():bool{
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
    return true;
}

function rule_delete():bool{
	$aclid      = $_POST["delete-rule"];
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT rulename FROM squid_parents_acls WHERE aclid='$aclid'");
    $rulename   = $ligne["rulename"];

	$q->QUERY_SQL("DELETE FROM parents_sqacllinks WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;return false;}
	$q->QUERY_SQL("DELETE FROM squid_parents_acls WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;return false;}
    $q->QUERY_SQL("DELETE FROM parents_white_sqacllinks WHERE aclid=$aclid");
    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks("DELETE PROXY Parent rule $rulename");

}


function proxy_fiche_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$proxname=urlencode($_GET["proxname"]);
	$title=$proxname;
	$aclid=$_GET["aclid"];
	if($proxname==null){$title=$tpl->javascript_parse_text("{new_proxy}");}
	return $tpl->js_dialog1($title,"$page?fiche-proxy=$proxname&aclid=$aclid");
}

function proxy_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$proxname=$_GET["delete-proxy-js"];
	$aclid=$_GET["aclid"];
    $md=$_GET["md"];
	header("content-type: application/x-javascript");

	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete} $proxname ?");
	$t=time();
	$html="
	
var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#$md').remove();
	if(document.getElementById('table-loader-proxy-parents') ){ 
	    LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes'); 
	}
}
	
function Action$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-proxy','$proxname');
	XHR.appendData('aclid','$aclid');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
	
Action$t();";
echo $html;
}

function rule_id_js():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $users      = new usersMenus();

    if(!$users->AsSquidAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return false;
    }


	$id=$_GET["ruleid-js"];
	$title="{new_rule}";

	if($id>0){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_parents_acls WHERE aclid='$id'");
		$title="{rule}: $id {$ligne["rulename"]}";
	}
	$title=$tpl->javascript_parse_text($title);
	$tpl->js_dialog($title,"$page?rule-popup=$id");
	return true;
}

function proxy_delete():bool{
	$aclid=$_POST["aclid"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT proxies FROM squid_parents_acls WHERE aclid='$aclid'");
	$MAIN=unserialize(base64_decode($ligne["proxies"]));
	unset($MAIN[$_POST["delete-proxy"]]);
	$MAIN_FINAL=base64_encode(serialize($MAIN));
	$sql="UPDATE squid_parents_acls SET proxies='$MAIN_FINAL' WHERE aclid='$aclid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return false;}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks("Delete proxy parent rule #$aclid");

}
function proxy_fiche(){
	$proxname=$_GET["fiche-proxy"];
	$title=$proxname;
	$btname="{apply}";
	$aclid=$_GET["aclid"];
	$BootstrapDialog=null;
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT proxies,use_lb FROM squid_parents_acls WHERE aclid='$aclid'");
	if(!isset($ligne["proxies"])){
        $ligne["proxies"]=base64_encode(serialize(array()));
    }

    $MAIN=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["proxies"]);
    $useLB=intval($ligne["use_lb"]);

	if($proxname==null){
		$title="{new_proxy}";
		$btname="{add}";
		$BootstrapDialog="dialogInstance1.close();";
	}
	$ARRAY=$MAIN[$proxname];
	$ST=explode(":",$proxname);

	if(!is_numeric($ST[1])){$ST[1]="3128";}
	$tpl->field_hidden("proxy-aclid", $aclid);
	$form[]=$tpl->field_text("hostname","{hostname}",$ST[0],true);
	$form[]=$tpl->field_numeric("port","{listen_port}",$ST[1]);
    $form[]=$tpl->field_hidden('verification_token',$ST[0].':'.$ST[1]);


        $options["tls"] = "tls";
        $options["proxy-only"] = "proxy-only";
        $options["Weight"] = "Weight=nn";
        $options["ttl"] = "ttl=nn";
        $options["no-query"] = "no-query";
        $options["default"] = "default";
        $options["round-robin"] = "round-robin";
        $options["multicast-responder"] = "multicast-responder";
        $options["closest-only"] = "closest-only";
        $options["no-digest"] = "no-digest";
        $options["no-tproxy"] = "no-tproxy";
        $options["no-netdb-exchange"] = "no-netdb-exchange";
        $options["no-delay"] = "no-delay";
        $options["userpassword"] = "login=user:password";
        $options["login_PASSTHRU"] = "login=PASSTHRU";
        $options["login_PASS"] = "login=PASS";
        $options["connect-timeout"] = "connect-timeout=nn";

    $explain["tls"] ="{UseSSL}";
	$explain["proxy-only"] ="{parent_options_proxy_only}";
	$explain["Weight"] ="{parent_options_proxy_weight}";
	$explain["ttl"] ="{parent_options_proxy_ttl}";
	$explain["no-query"] ="{parent_options_proxy_no_query}";
	$explain["default"] ="{parent_options_proxy_default}";
	$explain["round-robin"] ="{parent_options_proxy_round_robin}";
	$explain["multicast-responder"] ="{parent_options_proxy_multicast_responder}";
	$explain["closest-only"] ="{parent_options_proxy_closest_only}";
	//$explain["no-digest"] ="{parent_options_proxy_no_digest}";
	$explain["no-netdb-exchange"] ="{parent_options_proxy_no_netdb_exchange}";
	$explain["no-delay"] ="{parent_options_proxy_no_delay}";
	$explain["userpassword"] ="{parent_options_proxy_login}";
	$explain["login_PASSTHRU"] ="{parent_options_login_passthru}";
	$explain["login_PASS"] ="{parent_options_login_pass}";
	$explain["connect-timeout=nn"] ="{parent_options_proxy_connect_timeout}";
	$explain["digest-url"] ="{parent_options_proxy_digest_url}";
	$explain["no-tproxy"] ="{parent_options_no_tproxy}";
	$explain["connect-timeout"] ="{parent_options_connect_timeout}";

    $explain_form="{proxy}:$proxname";

    if($proxname==null){$explain_form=null;}

	foreach ($options as $key=>$val){
		if($key=="connect-timeout"){
            if(!isset($ARRAY["OPT_".$key])){
                $ARRAY["OPT_".$key]["VALUE"]=5;
                $ARRAY["OPT_".$key]["ENABLED"]=1;

            }
        }
        if(!isset($ARRAY["OPT_".$key])){$ARRAY["OPT_".$key]["VALUE"]=null;}
        $default_value=$ARRAY["OPT_".$key]["VALUE"];
        if(!isset($ARRAY["OPT_".$key]["ENABLED"])){
            $ARRAY["OPT_".$key]["ENABLED"]=0;
        }
		$default_enabled=intval($ARRAY["OPT_".$key]["ENABLED"]);
        if($useLB==1){
            $tpl->field_hidden("OPT_".$key,$default_enabled);
            continue;
        }
        $field_checkbox=$tpl->field_checkbox("OPT_".$key,$key,$default_enabled,false,$explain[$key]);

		if(preg_match("#=nn#", $val)){
			$form[]=$tpl->field_checkbox("OPT_".$key,$key,$default_enabled,"VAL_$key",$explain[$key]);
			$form[]=$tpl->field_numeric("VAL_$key","{value} $key",$default_value);
			continue;
		}
		if(preg_match("#user:password#", $val)){
            $zExplain="";
            if(isset($explain[$key])){
                $zExplain=$explain[$key];
            }
			$form[]=$tpl->field_checkbox("OPT_".$key,$key,$default_enabled,"USR_$key,PASS_$key",$zExplain);
			$default_value1=explode(":",$default_value);
			$form[]=$tpl->field_text("USR_$key","{username}",$default_value1[0]);
			$form[]=$tpl->field_password("PASS_$key","{password}",$default_value1[1]);
			continue;
		}


		$form[]=$field_checkbox;
	}

	echo $tpl->form_outside($title,@implode("\n", $form),
        $explain_form,$btname,
			"LoadAjax('proxies-list-$aclid','$page?proxies=$aclid');LoadAjax('table-loader-proxy-parents','$page?table=yes');$BootstrapDialog");

}

function proxy_fiche_save():bool{

	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return false;}

	$aclid=intval($_POST["proxy-aclid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT proxies FROM squid_parents_acls WHERE aclid='$aclid'");
	$MAIN=unserialize(base64_decode($ligne["proxies"]));
	$hostname=url_decode_special_tool($_POST["hostname"]).":".$_POST["port"];
	$hostname=strtolower($hostname);
    $verification_token=url_decode_special_tool($_POST['verification_token']);
	unset($MAIN[$hostname]);
	unset($_POST["proxy-aclid"]);
	unset($_POST["hostname"]);
	unset($_POST["port"]);

	$MAIN[$hostname]["SAVED"]=time();

    foreach ($_POST as $key=>$val){
		$val=url_decode_special_tool($val);
		if(!preg_match("#^OPT_(.+)#", $key,$re)){continue;}
		if(intval($val)==0){continue;}
		$key=$re[1];
		$MAIN[$hostname]["OPT_".$key]["ENABLED"]=1;
		if($_POST["VAL_$key"]<>null){$MAIN[$hostname]["OPT_".$key]["VALUE"]=url_decode_special_tool($_POST["VAL_$key"]); }
		if($_POST["USR_$key"]<>null){$MAIN[$hostname]["OPT_".$key]["VALUE"]=url_decode_special_tool($_POST["USR_$key"]).":".url_decode_special_tool($_POST["PASS_$key"]);}
	}

	if($verification_token!==$hostname){
	    UNSET($MAIN[$verification_token]);
    }

	$MAIN_FINAL=base64_encode(serialize($MAIN));
	$sql="UPDATE squid_parents_acls SET proxies='$MAIN_FINAL' WHERE aclid='$aclid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return false;}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks_post("Save parent proxy $hostname");


}

function rule_settings(){
	$aclid=intval($_GET["rule-settings"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne["enabled"]=1;
	$ligne["zorder"]=1;
	$btname="{add}";
	$title="{parent_proxies_rule}: {new_rule}";
	$BootstrapDialog="BootstrapDialog1.close();LoadAjax('table-loader-proxy-parents','$page?table=yes');";
    $ligneCheck = $q->mysqli_fetch_array("SELECT * FROM squid_parents_acls WHERE enabled=1 AND use_lb=1");
    $oldid = intval($ligneCheck["aclid"]);
    $VIEWLB=True;
    if($oldid>0){ if($oldid<>$aclid){$VIEWLB=false;} }

	if($aclid>0){
		$btname="{apply}";
		$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_parents_acls WHERE aclid='$aclid'");
		$title=$ligne["rulename"];
		$BootstrapDialog=null;
        $add="Loadjs('$page?fiche-proxy-js=yes&proxname=&aclid=$aclid')";
        $form[]=$tpl->form_add_button("{new_proxy}",$add);
	}


	$tpl->field_hidden("rule-save", $aclid);
	$form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"],true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_checkbox("always_direct","{do_not_use_parent_proxy}",$ligne["always_direct"],false,"{do_not_use_parent_proxy}");
	$form[]=$tpl->field_checkbox("never_direct","{never_direct}",$ligne["never_direct"],false,"{never_direct_explain}");
    $form[]=$tpl->field_checkbox("proxyproxy","{AuthParentPort}",$ligne["proxyproxy"],false,"{proxyproxy_explain}");

    if($VIEWLB) {
        $form[] = $tpl->field_checkbox("use_lb", "{use_lb_system}", $ligne["use_lb"], false, "{use_lb_system_explain}");
    }else{
        $tpl->field_hidden("use_lb",0);
    }


	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
	echo $tpl->form_outside($title,@implode("\n", $form),"",$btname,"LoadAjax('table-loader-proxy-parents','$page?table=yes');$BootstrapDialog");
}

function rule_main_save():bool{
	$tpl    = new template_admin();
    $q      = new lib_sqlite("/home/artica/SQLITE/acls.db");

    if($_POST["always_direct"]==1){$_POST["never_direct"]=0;}
	$aclid=$_POST["rule-save"];
	$rulename=url_decode_special_tool($_POST["rulename"]);
	$rulename=mysql_escape_string2($rulename);
	if($aclid==0){
		$sqlB="INSERT INTO `squid_parents_acls` (`rulename`,`enabled` ,`zorder`,`never_direct`,`always_direct`,`proxyproxy`,`use_lb`) VALUES ('$rulename','{$_POST["enabled"]}','{$_POST["zorder"]}','{$_POST["never_direct"]}','{$_POST["always_direct"]}','{$_POST["proxyproxy"]}','{$_POST["use_lb"]}')";
	}else{
		$sqlB="UPDATE `squid_parents_acls` SET `rulename`='$rulename',`enabled`='{$_POST["enabled"]}',`zorder`='{$_POST["zorder"]}',never_direct='{$_POST["never_direct"]}',
                                always_direct='{$_POST["always_direct"]}',
                                proxyproxy = '{$_POST["proxyproxy"]}',
                                use_lb = '{$_POST["use_lb"]}'
                                WHERE aclid='$aclid'";
	}


    $q->QUERY_SQL($sqlB);
	if(!$q->ok){echo $q->mysql_error_html(true);return false;}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/parents/compile");
    return admin_tracks("INSERT/UPDATE parent rule $rulename never_direct={$_POST["never_direct"]} always_direct={$_POST["always_direct"]}");

}

function rule_tab(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["rule-popup"]);
	$q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne          = $q->mysqli_fetch_array("SELECT rulename,always_direct FROM squid_parents_acls WHERE aclid='$aclid'");
	$rulename       = $ligne["rulename"];
	$always_direct  = $ligne["always_direct"];
	$ppage          = "fw.proxy.objects.php";

    if($aclid==0){$rulename="{new_rule}";}

	$array[$rulename]="$page?rule-settings=$aclid";
	if($aclid>0){

		if($always_direct==0) {
            $array["{parent_proxies}"]      = "$page?proxies=$aclid";
            $array["{objects} {use_proxy}"] = "$ppage?aclid=$aclid&main-table=parents_sqacllinks&fast-acls=1";
            $array["{objects} {exclude}"]   = "$ppage?aclid=$aclid&main-table=parents_white_sqacllinks&fast-acls=1";
        }else{
            $array["{objects} {exclude}"]   = "$ppage?aclid=$aclid&main-table=parents_sqacllinks&fast-acls=1";
        }

	}
	echo $tpl->tabs_default($array);


}
function proxies(){
	$page=CurrentPageName();
	$aclid=intval($_GET["proxies"]);
	echo "<div id='proxies-list-$aclid' style='margin-top:20px'></div>
	<script>LoadAjax('proxies-list-$aclid','$page?proxies-list=$aclid');</script>	
	";

}
function proxies_list(){
	$aclid=intval($_GET["proxies-list"]);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$ligne=$q->mysqli_fetch_array("SELECT proxies FROM squid_parents_acls WHERE aclid='$aclid'");
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	$proxies=unserialize(base64_decode($ligne["proxies"]));
	$add="Loadjs('$page?fiche-proxy-js=yes&proxname=&aclid=$aclid')";



    $topbuttons[] = array($add, ico_plus, "{new_proxy}");


	$html[]=$tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$hostname</th>";
	$html[]="<th data-sortable=false>DEL.</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$TRCLASS=null;
    $icon_srv="<i class='".ico_server." fa-2x'></i>";
	foreach ($proxies as $hostname => $array){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$rulename=urlencode($hostname);
		$md=md5($hostname.serialize($array));
		$js="Loadjs('$page?fiche-proxy-js=yes&proxname=$rulename&aclid=$aclid')";
		$delete=$tpl->icon_delete("Loadjs('$page?delete-proxy-js=$rulename&aclid=$aclid&md=$md')");
		if(!$users->AsSquidAdministrator){$delete=$tpl->icon_delete("blur()");}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:99%'>$icon_srv&nbsp;<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold;font-size: 25px;'>$hostname</span></td>";
		$html[]="<td class='center' style='width:1%'>$delete</td>";
		$html[]="</tr>";

	}
	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]=$tpl->table_footable_footer("table-$t",false);
	echo $tpl->_ENGINE_parse_body($html);
}

function rule_move_js():bool{

    $users      = new usersMenus();
    if(!$users->AsSquidAdministrator){
        $tpl    = new template_admin();
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return false;
    }

    header("content-type: application/x-javascript");
	$q      = new lib_sqlite("/home/artica/SQLITE/acls.db");
	$tpl    = new template_admin();
    $dir    = $_GET["dir"];
	$aclid  = intval($_GET["aclid"]);
	$ligne  = $q->mysqli_fetch_array("SELECT zorder FROM squid_parents_acls WHERE aclid='$aclid'");
	$zorder = intval($ligne["zorder"]);



		echo "// Current order = $zorder\n";

		if($dir=="up"){
			$zorder=$zorder-1;
			if($zorder<0){$zorder=0;}

		}
		else{
			$zorder=$zorder+1;
		}
		echo "// New order = $zorder\n";
		$q->QUERY_SQL("UPDATE squid_parents_acls SET zorder='$zorder' WHERE aclid='$aclid'");
		if(!$q->ok){
			$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
			echo "alert('$q->mysql_error');";return false;
		}

		$c=0;
		$results=$q->QUERY_SQL("SELECT aclid FROM squid_parents_acls ORDER BY zorder");
		foreach($results as $index=>$ligne) {
			$aclid=$ligne["aclid"];
			echo "// $index $aclid New order = $c";
			$q->QUERY_SQL("UPDATE squid_parents_acls SET zorder='$c' WHERE aclid='$aclid'");
			$c++;
		}

	return true;

}

function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$add="Loadjs('$page?ruleid-js=0',true);";

    $jsrestart=$tpl->framework_buildjs("/proxy/parents/compile",
        "squid.access.center.progress","squid.access.center.progress.log",
        "progress-firehol-restart","LoadAjax('table-loader-proxy-parents','$page?table=yes');");


    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");

    $html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";



	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="<th data-sortable=true style='width:1%'></th>";
    $html[]="<th data-sortable=true style='width:1%'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rulename</th>";
	$html[]="<th data-sortable=false>{move}</th>";
	$html[]="<th data-sortable=false>DEL.</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    if(!isset($_GET["eth"])){$_GET["eth"]="";}
	$jsAfter="LoadAjax('table-loader-proxy-parents','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$isRights=isRights();
	$results=$q->QUERY_SQL("SELECT * FROM squid_parents_acls ORDER BY zorder");
	$TRCLASS=null;

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid-parents.php?rules-status=yes");
    $peer_stat=PROGRESS_DIR."/cache.peer.status.arr";
    $peer_status=unserialize(@file_get_contents($peer_stat));

	foreach($results as $index=>$ligne) {
		$square_class="text-navy";
		$pproxy=array();
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $use_lb_txt     = null;
			$rulename       = $ligne["rulename"];
			$rulenameenc    = urlencode($rulename);
			$aclid          = $ligne["aclid"];
			$enabled        = $ligne["enabled"];
			$never_direct   = intval($ligne["never_direct"]);
			$always_direct  = intval($ligne["always_direct"]);
            $use_lb         = intval($ligne["use_lb"]);
			$MAIN           = unserialize(base64_decode($ligne["proxies"]));
            $up             = $tpl->icon_up("Loadjs('$page?move-js=yes&aclid=$aclid&dir=up')");
            $down           = $tpl->icon_down("Loadjs('$page?move-js=yes&aclid=$aclid&dir=down')");
            $pproxy_text    = null;
            $white          = null;
            $returndef      = true;
			foreach ($MAIN as $num=>$val){$pproxy[]="<strong>$num</strong>";}

            $array_help["TITLE"]="{inactive2}";
            $array_help["content"]="{inactive_acl_why}";
            $array_help["ico"]="fa fa-question";
            $scontent=base64_encode(serialize($array_help));

            $status=$tpl->td_href("<span class='label'>{inactive2}</span>","{explain}","LoadAjax('artica-modal-dialog','fw.popup.php?array=$scontent')");
            if(isset($peer_status[$aclid])){
                $status="<span class='label label-primary'>{active2}</span>";
            }

            if($use_lb==1){
                $use_lb_txt=" {using_a_local_lb}";
            }

			if(count($pproxy)>0){$pproxy_text="<br>{then_use_parent_proxies}$use_lb_txt ".@implode(" {or} ", $pproxy);}
			else{
			    if($always_direct==0) {
                    $pproxy_text = "{inactive_rule}";
                    $enabled = 0;
                }
			}

			$check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$aclid')");
			if($always_direct==1){$never_direct=0;}

			if($enabled==1){
				if($never_direct==0){
					$pproxy_text=$pproxy_text." <small>{never_direct_explain_no}</small>";
				}else{
					$pproxy_text=$pproxy_text." <small>{never_direct_label}</small>";
				}
			}


			$js="Loadjs('$page?ruleid-js=$aclid',true);";
			$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js=$aclid&name=$rulenameenc')");
            if($never_direct==0){$white=proxy_objects($aclid,"parents_white_sqacllinks");}
			if($white<>null){$white="<br>{and} <strong>{notfor}</strong>: $white";}
			if($always_direct==1){$returndef=false;}

			$objects=proxy_objects($aclid,"parents_sqacllinks",$returndef);
            $explain=$tpl->_ENGINE_parse_body("{for_objects} $objects$white$pproxy_text");

            if($always_direct==1){
			    if($objects==null){
                    $explain="{inactive_rule}";
                }else{
                    $explain=$tpl->_ENGINE_parse_body("{for_objects} $objects {then} <strong class='text-navy'>{do_not_use_parent_proxy}</strong>");
                }

            }


			if(!$isRights){
				$up=null;
				$down=null;
				$delete=null;
			}

			$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
			$html[]="<td class=\"center\"><button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" ><i class='fa fa-paste'></i></button></td>";
			$html[]="<td class=\"center\">$check</td>";
            $html[]="<td class=\"center\">$status</td>";
			$html[]="<td style='vertical-align:middle'>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$rulename:</a>&nbsp;&raquo;&nbsp;$explain</span></td>";
			$html[]="<td class=\"center\" width='1%' nowrap>$up&nbsp;&nbsp;$down</td>";
			$html[]="<td class=center width='1%' nowrap>$delete</td>";
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

    $TINY_ARRAY["TITLE"]="{parent_proxies} &raquo;&raquo; {rules}";
    $TINY_ARRAY["ICO"]="fa fa-sitemap";
    $TINY_ARRAY["EXPL"]="{parent_proxies_status_explain}";
    $TINY_ARRAY["URL"]="proxy-parents-rules";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
$jstiny
</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function isRights():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

function proxy_objects($aclid,$tablelink="parents_sqacllinks",$returndef=true):string{

    $tt         = array();
	$q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
	$qProxy     = new mysql_squid_builder(true);

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
		$GroupName=$ligne["GroupName"];
		$tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-weight:bold'>$GroupName</a> <small>(".$qProxy->acl_GroupType[$ligne["GroupType"]].")</small>";
	}

	if(count($tt)>0){
		return @implode("<br>{and} ", $tt);

	}

    if(!$returndef){return "";}
	return "{all}";



}