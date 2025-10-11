<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
include_once(dirname(__FILE__)."/ressources/class.spf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["mynetworks"])){mynetworks();exit;}
if(isset($_GET["mynetwork-table"])){mynetworks_table();exit;}
if(isset($_GET["mynetwork-js"])){mynetworks_js();exit;}
if(isset($_GET["mynetwork-popup"])){mynetworks_popup();exit;}
if(isset($_POST["mynetworks-ipv4"])){mynetworks_save();exit;}
if(isset($_POST["mynetworks-cdir"])){mynetworks_save();exit;}
if(isset($_GET["mynetworks-delete"])){mynetworks_delete();exit;}
if(isset($_GET["mynetworks-edit-js"])){mynetworks_edit_js();exit;}
if(isset($_POST["mynetworks-delete"])){mynetworks_delete_perform();exit;}
if(isset($_POST["mynetworks-description"])){mynetworks_description_save();exit;}
if(isset($_GET["mynetwork-import-js"])){mynetworks_import_js();exit;}
if(isset($_GET["mynetwork-import-popup"])){mynetworks_import_popup();exit;}
if(isset($_POST["mynetwork-import"])){mynetworks_import_save();exit;}

if(isset($_GET["mynetwork-export-js"])){mynetworks_export_js();exit;}
if(isset($_GET["mynetwork-export-popup"])){mynetworks_export_popup();exit;}



if(isset($_GET["routing"])){routing_popup();exit;}
if(isset($_GET["routing-table"])){routing_table();exit;}
if(isset($_GET["routing-js"])){routing_js();exit;}
if(isset($_GET["routing-entry"])){routing_entry();exit;}
if(isset($_GET["routing-enable"])){routing_enable();exit;}
if(isset($_GET["routing-delete"])){routing_delete();exit;}
if(isset($_POST["routing-delete"])){routing_delete_perform();exit;}
if(isset($_GET["routing-domains"])){routing_domains();exit;}
if(isset($_POST["OtherDomains"])){routing_domains_save();exit;}
if(isset($_GET["routing-params"])){routing_params();exit;}
if(isset($_POST["nexthope"])){routing_save();exit;}
if(isset($_GET["routing-spf"])){routing_spf();exit;}


if(isset($_GET["routing-bcc"])){routing_bcc();exit;}
if(isset($_GET["routing-bcc-popup"])){routing_bcc_popup();exit;}


if(isset($_GET["tabs"])){tabs();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $instance_id=intval($_GET["instance-id"]);
    $html=$tpl->page_header("{routing_tables} v$POSTFIX_VERSION",
        "fas fa-bus",
        "{postfix_routing_tables_explain}",
        "$page?tabs=yes&instance-id=$instance_id",
        "postfix-routing",
        "progress-postfix-routing",false,
        "table-loader-postfix-routing"
    );

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}

function mynetworks_edit_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["mynetworks-edit-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM mynetworks WHERE ID='$ID'");
	$ipaddr=$ligne["addr"];
    $instance_id=$ligne["instance_id"];
    $instancename="SMTP Master";
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=$ligne["instancename"];
    }

	$tpl->js_prompt("[$ID] $instancename:<br><strong>$ipaddr</strong>", "{description}", "fas fa-info-circle",$page,
			 "mynetworks-description","LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');",
			 $ligne["description"],$ID);
	
	
}

function mynetworks_import_js(){
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{mynetworks_title}:: {import}", "$page?mynetwork-import-popup=yes&instance-id=$instance_id");

}

function mynetworks_export_js(){
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{mynetworks_title}:: {export}", "$page?mynetwork-export-popup=yes&instance-id=$instance_id");
}

function mynetworks_import_popup(){
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();


    $instancename="SMTP Master";
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=$ligne["instancename"];
    }

    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_textareacode("mynetwork-import","{networks}",null,null);
    echo $tpl->form_outside("$instancename:{import}",$form,null,"{import}","dialogInstance1.close();LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
}

function mynetworks_export_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results=$q->QUERY_SQL("SELECT * FROM mynetworks WHERE instance_id=$instance_id");

    foreach ($results as $index=>$ligne){
        $description=$ligne["description"];
        $val[]=trim($ligne["addr"])."\t$description";

    }
    $tpl->field_hidden("instance_id",$instance_id);
    $tpl->field_hidden("FROMEX",1);
    $form[]=$tpl->field_textareacode("mynetwork-import","{networks}",@implode("\n",$val),null);
    echo $tpl->form_outside("{data}",$form,null,"{apply}","dialogInstance1.close();LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
}

function mynetworks_description_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$desc=$q->sqlite_escape_string2($_POST["mynetworks-description"]);
	$ID=$_POST["KeyID"];
	$q->QUERY_SQL("UPDATE mynetworks SET `description`='$desc' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	if(!extension_loaded("pdo_sqlite")){
		echo $tpl->FATAL_ERROR_SHOW_128("{Warninphp70sqlite3}");
		return false;
	}
	
	$array["{routing}"]="$page?routing=yes&instance-id=$instance_id";
	$array["{mynetworks_title}"]="$page?mynetworks=yes&instance-id=$instance_id";
	
	echo $tpl->tabs_default($array);
}

function mynetworks_delete(){
	$tpl=new template_admin();
	$addr=$_GET["mynetworks-delete"];
    $instance_id=intval($_GET["instance-id"]);
	$tpl->js_confirm_delete($addr, "mynetworks-delete", "$addr||$instance_id","$('#{$_GET["id"]}').remove()");
}
function routing_delete(){
	$tpl=new template_admin();
	$addr=$_GET["routing-delete"];
    $instance_id=intval($_GET["instance-id"]);
	$tpl->js_confirm_delete($addr, "routing-delete", "$addr||$instance_id","$('#{$_GET["id"]}').remove()");
}
function routing_delete_perform(){
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    $value=explode("||",$_POST["routing-delete"]);
    $addr=$value[0];
    $instance_id=$value[1];
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$q->QUERY_SQL("DELETE FROM transport_maps WHERE addr='$addr'");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM relay_domains_restricted WHERE domainname='$addr' AND instanceid=$instance_id");
	
	
}

function routing_bcc(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$value=$_GET["routing-bcc"];
	if($value==null){$title="{new_bcc}";}else{$title=$value;}
	$valueenc=urlencode($value);
    $instance_id=intval($_GET["instance-id"]);
	if(preg_match("#BCC:(.+)#", $value,$re)){$title="{BCC} {$re[1]}";}
	$tpl->js_dialog1("{routing_table}:: $title", "$page?routing-bcc-popup=$valueenc&instance-id=$instance_id");
	
}

function routing_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$value=$_GET["routing-js"];
	if($value==null){$title="{new_entry}";}else{$title=$value;}
	$valueenc=urlencode($value);
    $instance_id=intval($_GET["instance-id"]);
	$tpl->js_dialog1("{routing_table}:: $title", "$page?routing-entry=$valueenc&instance-id=$instance_id");
    return true;
	
}

function mynetworks_delete_perform():bool{
    $value=explode("||",$_POST["mynetworks-delete"]);
    $addr=$value[0];
    $instance_id=intval($value[1]);
	//$addr=$_POST["mynetworks-delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    //$instance_id=intval($_GET["instance-id"]);
    if(!$q->FIELD_EXISTS("mynetworks","instanceid")){
        $q->QUERY_SQL("ALTER TABLE mynetworks ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }

	$q->QUERY_SQL("DELETE FROM mynetworks WHERE addr='$addr' AND instanceid=$instance_id");
	if(!$q->ok){echo $q->mysql_error;}
    $sock=new sockets();
    $sock->REST_API("/postfix/mynetwork/$instance_id");
    return true;
}

function mynetworks_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	$tpl->js_dialog1("{mynetworks_title}:: {new_address}", "$page?mynetwork-popup=&instance-id=$instance_id");
    return true;
}
function mynetworks_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $instancename=null;
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    if($instance_id>0){
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=" <small>({$ligne["instancename"]})</small>";
    }

    if(!$q->FIELD_EXISTS("mynetworks","ID")){
        echo $tpl->div_error("{error}||Need to upgrade table mynetworks!");
        return false;
    }


    $instance_id=intval($_GET["instance-id"]);
    $form[]=$tpl->field_hidden("instance_id",$instance_id);
	$form[]=$tpl->field_ipv4("mynetworks-ipv4", "{new_address}", null);
	$form[]=$tpl->field_cdir("mynetworks-cdir", "{new_range}", null);
	$form[]=$tpl->field_text("description", "{description}", null);
	echo $tpl->form_outside("{mynetworks_title}$instancename", $form,"{mynetworks_text}","{add}",
			"dialogInstance1.close();LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
}
function routing_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$addr=$_POST["addr"];
	$direction=$_POST["direction"];
	$service=$_POST["service"];
	$nexthope=$_POST["nexthope"];
	$nextport=$_POST["nextport"];
	$enabled=$_POST["enabled"];
	$tls_enabled=$_POST["tls_enabled"];
	$tls_mode=$_POST["tls_mode"];
    if(!isset($_POST["instance_id"])){
        echo $tpl->post_error("Need instance id");
        return false;
    }
    $instance_id=intval($_POST["instance_id"]);
	
	$auth=$_POST["auth"];
	$username=$_POST["username"];
	$password=$_POST["password"];
    $client_certificate=$_POST["client_certificate"];
    $certificate=$_POST["certificate"];

	
	if($service=="BCC"){
		if(!preg_match("#BCC:(.+)#", $addr)){$addr="BCC:$addr";}
		
	}
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    if(!$q->FIELD_EXISTS("transport_maps", "client_certificate")){
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD `client_certificate` INTEGER DEFAULT 0");
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD `certificate` TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("transport_maps","instanceid")){
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }


	$results=$q->QUERY_SQL("SELECT * FROM transport_maps WHERE addr='{$_POST["addr"]}' AND instanceid=$instance_id");
	if($results[0]["addr"]==null){
		$Values="VALUES('$addr','$direction','$service','$enabled','$nexthope','$nextport','$tls_enabled','$tls_mode','$auth','$username','$password','$client_certificate','$certificate',$instance_id)";
		$q->QUERY_SQL("INSERT INTO transport_maps (addr,direction,service,enabled,nexthope,nextport,tls_enabled,tls_mode,auth,username,password,client_certificate,certificate,instanceid) $Values");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$q->QUERY_SQL("UPDATE transport_maps SET direction='$direction', service='$service',tls_enabled=$tls_enabled,
    tls_mode='$tls_mode', enabled='$enabled',nexthope='$nexthope', 
    nextport='$nextport', auth='$auth',username='$username',
    password='$password',client_certificate='$client_certificate',certificate='$certificate'
    WHERE addr='$addr'");
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `relay_domains_restricted` (`domainname` text PRIMARY KEY)";
	$q->QUERY_SQL($sql);
	if(isset($_POST["relay_domains_restricted"])){
		if($_POST["relay_domains_restricted"]==0){
			$q->QUERY_SQL("DELETE FROM relay_domains_restricted WHERE domainname='$addr'");
		}else{
			$q->QUERY_SQL("INSERT OR IGNORE INTO relay_domains_restricted  (`domainname`) VALUES ('$addr')");
			if(!$q->ok){echo $q->mysql_error;}
		}
		
	}


	$EnableDKFilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDKFilter"));
	if($EnableDKFilter==1){
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("opendkim.php?syncdomains=yes");
    }

	
	
	return;
}

function routing_enable(){
	header("content-type: application/x-javascript");
	$addr=$_GET["routing-enable"];
    $instance_id=intval($_GET["instance-id"]);
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$results=$q->QUERY_SQL("SELECT enabled FROM transport_maps WHERE addr='$addr' AND instanceid=$instance_id");
	if($results[0]["enabled"]==1){$enabled=0;}else{$enabled=1;}

	$q->QUERY_SQL("UPDATE transport_maps SET enabled='$enabled' WHERE addr='$addr' AND instanceid=$instance_id");
	if(!$q->ok){echo "alert('$q->mysql_error')";}
}


function routing_bcc_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	$value=$_GET["routing-bcc-popup"];
	$button="{add}";
	$title="{new_bcc}";
	$array_direction[0]="{inbound}";
	$array_direction[1]="{outbound}";
	$js="dialogInstance1.close();";
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	if(!$q->IF_TABLE_EXISTS("transport_maps")){
		$q->QUERY_SQL("CREATE TABLE transport_maps (addr varchar(256),
                direction int(1),service varchar(40),instanceid INTEGER NOT NULL DEFAULT 0,
				enabled INT(1),nexthope varchar(128),nextport int(5), PRIMARY KEY (addr) ) ");
		@chmod("/home/artica/SQLITE/postfix.db", 0777);
	}
	
	
	$form[]=$tpl->field_hidden("service", "BCC");
	if($value==null){
		$form[]=$tpl->field_text("addr", "{email}", null,true,"{transport_email_explain}");
	}else{
		$results=$q->QUERY_SQL("SELECT * FROM transport_maps WHERE addr='$value' AND instanceid=$instance_id");
		$ligne=$results[0];
		$form[]=$tpl->field_hidden("addr", $value);
		if(preg_match("#BCC:(.+)#", $value,$re)){$title="{BCC} {$re[1]}";}
		
		$button="{apply}";
	
	}
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$form[]=$tpl->field_array_hash($array_direction, "direction", "{direction}", intval($ligne["direction"]));
	$form[]=$tpl->field_text("nexthope", "{bcc_to}", $ligne["nexthope"],false);
	
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
	echo $tpl->form_outside("{routing_rule}: $title", $form,"",$button,"$js;LoadAjax('routing-div','$page?routing-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
	
	
}

function routing_entry(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $value = $_GET["routing-entry"];
    $instance_id=intval($_GET["instance-id"]);
    $valueencoded=urlencode($value);
    $array["{parameters}"]="$page?routing-params=$valueencoded&instance-id=$instance_id";
    if($value<>null) {
        $array["{identical_domains}"] = "$page?routing-domains=$valueencoded&instance-id=$instance_id";
        $array["SPF"] = "$page?routing-spf=$valueencoded&instance-id=$instance_id";
    }
    echo $tpl->tabs_default($array);

}

function routing_domains(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $value = $_GET["routing-domains"];
    $instance_id=intval($_GET["instance-id"]);
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    if(!$q->FIELD_EXISTS("transport_maps", "OtherDomains")){
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD `OtherDomains` TEXT");
    }

    $ligne=$q->mysqli_fetch_array("SELECT OtherDomains FROM transport_maps WHERE addr='$value'");
    $HASH=unserialize(base64_decode($ligne["OtherDomains"]));

    foreach ($HASH as $domain=>$none){
        $domain=trim(strtolower($domain));
        if($domain==null){continue;}
        $f[]=$domain;

    }

    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_hidden("domainkey",$value);
    $form[]=$tpl->field_textareacode("OtherDomains",null,@implode("\n",$f));
    echo $tpl->form_outside("{domains}", $form,"{identical_domains_explain}","{apply}","LoadAjax('routing-div','$page?routing-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");

}

function routing_domains_save(){

    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $domainkey=$_POST["domainkey"];
    $OtherDomains=explode("\n",$_POST["OtherDomains"]);

    foreach ($OtherDomains as $domain){
        $domain=trim(strtolower($domain));
        if($domain==null){continue;}
        $MAIN[$domain]=true;
    }

    ksort($MAIN);
    $MAIN_ENCODED=base64_encode(serialize($MAIN));
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $q->QUERY_SQL("UPDATE transport_maps SET `OtherDomains`='$MAIN_ENCODED' WHERE addr='$domainkey'");
    if(!$q->ok){echo $q->mysql_error;}
}

function routing_params(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $postfixBin=$GLOBALS["CLASS_SOCKETS"]->Postfix_VersionBin();


    $instance_id=intval($_GET["instance-id"]);
    $value = $_GET["routing-params"];
	$button="{add}";
	$title="{new_entry}";
	$array_direction[0]="{inbound}";
	$array_direction[1]="{outbound}";
	$js="dialogInstance1.close();";
	
	$arra_service["smtp"]="SMTP";
	$arra_service["error"]="{error}";
	
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	
	if(!$q->FIELD_EXISTS("transport_maps", "auth")){
		$q->QUERY_SQL("ALTER TABLE transport_maps ADD `auth` INTEGER DEFAULT 0");
		$q->QUERY_SQL("ALTER TABLE transport_maps ADD `username` TEXT");
		$q->QUERY_SQL("ALTER TABLE transport_maps ADD `password` TEXT");
	}

    if(!$q->FIELD_EXISTS("transport_maps", "client_certificate")){
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD `client_certificate` INTEGER DEFAULT 0");
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD `certificate` TEXT NULL");
    }
    $form[]=$tpl->field_hidden("instance_id", $instance_id);
	if($value==null){
		$form[]=$tpl->field_text("addr", "{destination_domain_or_recipient}", null,true,"{transport_email_explain}");
	}else{
		$results=$q->QUERY_SQL("SELECT * FROM transport_maps WHERE addr='$value' AND instanceid=$instance_id");
		$ligne=$results[0];
		$form[]=$tpl->field_hidden("addr", $value);
		$title=$value;
		$button="{apply}";
		
	}
	
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!isset($ligne["service"])){$ligne["service"]="smtp";}
	if(intval($ligne["nextport"])==0){$ligne["nextport"]=25;}
	if($ligne["service"]==null){$ligne["service"]="smtp";}
	$array_field_relay_tls=$q->array_field_relay_tls;
	
	if(strpos(" $value", "@")==0){
		$ligne2=$q->mysqli_fetch_array("SELECT domainname FROM relay_domains_restricted WHERE domainname='$value'");
		if($ligne2==null){$relay_domains_restricted=0;}else{$relay_domains_restricted=1;}
		$form[]=$tpl->field_checkbox("relay_domains_restricted","{reject_unverified_recipient}",
                $relay_domains_restricted,false,"{reject_unverified_recipient_text}");
	}
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_array_hash($array_direction, "direction", "{direction}", intval($ligne["direction"]));
	$form[]=$tpl->field_array_hash($arra_service, "service", "{service2}", $ligne["service"],true);
	$form[]=$tpl->field_text("nexthope", "{relay_address}", $ligne["nexthope"],false);
	$form[]=$tpl->field_numeric("nextport","{port}", $ligne["nextport"]);
	$form[]=$tpl->field_section("{tls_title}");
	$form[]=$tpl->field_checkbox("tls_enabled","{smtp_use_tls}",$ligne["tls_enabled"],false);
	$form[]=$tpl->field_array_hash($array_field_relay_tls, "tls_mode", "{tls_label}", $ligne["tls_mode"]);
    if($postfixBin>356) {
            $form[] = $tpl->field_checkbox("client_certificate","{client_certificate}",$ligne["client_certificate"]);
            $form[] = $tpl->field_certificate("certificate","{use_certificate_from_certificate_center}",
                $ligne["certificate"]);

    }


	$form[]=$tpl->field_section("{authenticate}");
	$form[]=$tpl->field_checkbox("auth","{enabled}",$ligne["auth"],false);
	$form[]=$tpl->field_text("username", "{username}", $ligne["username"],false);
	$form[]=$tpl->field_password2("password", "{password}", $ligne["password"]);

    if($postfixBin<357){
        $html[]="<p>";
        $html[]=$tpl->div_error("{need_to_update_postfix357}");
        $html[]="</p>";
    }

	$html[]=$tpl->form_outside("{routing_rule}: $title", $form,"",$button,"$js;LoadAjax('routing-div','$page?routing-table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}


function mynetworks(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	echo "<div id='mynetworks-div' style='margin-top:20px'></div><script>LoadAjax('mynetworks-div','$page?mynetwork-table=yes&instance-id=$instance_id');</script>";
}
function routing_popup(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	echo "<div id='routing-div' style='margin-top:20px'></div><script>LoadAjax('routing-div','$page?routing-table=yes&instance-id=$instance_id');</script>";
}
function mynetworks_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$main=new main_cf($_POST["instance_id"]);
	if($_POST["mynetworks-ipv4"]<>null){$main->add_my_networks($_POST["mynetworks-ipv4"],$_POST["description"],$_POST["instance_id"]);}
	if($_POST["mynetworks-cdir"]<>null){$main->add_my_networks($_POST["mynetworks-cdir"],$_POST["description"],$_POST["instance_id"]);}

    $sock=new sockets();
    $sock->REST_API("/postfix/mynetwork/{$_POST["instance_id"]}");
}

function mynetworks_import_save(){
    include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instance_id=$_POST["instance_id"];
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

    $instancename="SMTP Master";
    if($instance_id>0){
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=$ligne["instancename"];
    }

    if(isset($_POST["FROMEX"])){
        $q->QUERY_SQL("DELETE FROM mynetworks WHERE instance_id=$instance_id");
        if(!$q->ok){echo $q->mysql_error;return false;}
    }

    $IP=new IP();
    $final=array();
    $tb=explode("\n",$_POST["mynetwork-import"]);
    foreach ($tb as $line){
        $line=trim($line);
        $description="Instance: $instancename - Imported on ".$tpl->time_to_date(time());
        if(preg_match("#^(.+?)\s+(.+)#",$line,$re)){
            $ipaddr=$re[1];
            $description=$re[2];
        }else{
            $ipaddr=$line;
        }
        $description=$q->sqlite_escape_string2($description);
        if(!$IP->IsACDIROrIsValid($ipaddr)){continue;}
        $final[]="('$ipaddr','$description','$instance_id')";
    }
    if(count($final)==0){return false;}
    $sql="INSERT OR IGNORE INTO mynetworks (addr,description,instance_id) VALUES ".@implode(",",$final);

    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $q->QUERY_SQL("PRAGMA optimize;");
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return false;}
    if(function_exists("admin_tracks")) {
        admin_tracks("Imported " . count($final) . " authorized networks for the SMTP relay $instancename");
    }
    $sock=new sockets();
    $sock->REST_API("/postfix/mynetwork/$instance_id");
    return true;

}


function mynetworks_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);

    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?mynetwork-js=&instance-id=$instance_id');\">";
    $btn[]="<i class='fa fa-plus'></i> {new_address} </label>";

    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?mynetwork-import-js=yes&instance-id=$instance_id');\">";
    $btn[]="<i class='fa fa-plus'></i> {import} </label>";

    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?mynetwork-export-js=yes&instance-id=$instance_id');\">";
    $btn[]="<i class='fas fa-file-csv'></i> {export} </label>";
	$html[]="</div>";
	$html[]="<table id='table-mynetworks' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{networks}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

    if(!$q->FIELD_EXISTS("mynetworks","ID")){
        echo $tpl->div_error("{error}||Need to upgrade table mynetworks!");
        return false;
    }
    if(!$q->FIELD_EXISTS("mynetworks","instanceid")){
        $q->QUERY_SQL("ALTER TABLE mynetworks ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }

	@chmod("/home/artica/SQLITE/postfix.db", 0777);

	
	if(!$q->FIELD_EXISTS("mynetworks", "description")){
		$q->QUERY_SQL("ALTER TABLE mynetworks add description text");
	}
    if(!$q->FIELD_EXISTS("mynetworks", "instance_id")){
        $q->QUERY_SQL("ALTER TABLE mynetworks add instance_id INTEGER NOT NULL DEFAULT 0");
    }
		
	$results=$q->QUERY_SQL("SELECT * FROM mynetworks WHERE instance_id=$instance_id");
	
	
	foreach ($results as $num=>$ligne){
        $ID=$ligne["ID"];
		$val=trim($ligne["addr"]);
		if($val==null){continue;}
		$valencode=urlencode($val);
		$id=md5($val);
		if($ligne["description"]<>null){$ligne["description"]=" ({$ligne["description"]})";}
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}	
		$html[]="<tr class='$TRCLASS' id='$id'>";
		$html[]="<td><strong>".$tpl->td_href("{$val} {$ligne["description"]}",null,"Loadjs('$page?mynetworks-edit-js=$ID&instance-id=$instance_id')")."</strong></td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?mynetworks-delete=$val&id=$id&instance-id=$instance_id')","AsPostfixAdministrator")."</center></td>";
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

    $instancename=null;
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=$ligne["instancename"];
    }

    $TINY_ARRAY["TITLE"]="{mynetworks_title} <small>$instancename</small>";
    $TINY_ARRAY["ICO"]="fa fa-network-wired";
    $TINY_ARRAY["EXPL"]="{mynetworks_text}";
    $TINY_ARRAY["URL"]="postfix-routing";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-mynetworks').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function routing_table(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();
    $function="";
	$instance_id=intval($_GET["instance-id"]);
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.transport.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.transport.progress.txt";
	$ARRAY["CMD"]="postfix2.php?transport=yes&instance-id=$instance_id";
	$ARRAY["TITLE"]="{reconfiguring}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-routing')";
	
	
	$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?routing-js=&instance-id=$instance_id');\">";
    $btn[]="<i class='fa fa-plus'></i> {new_rule} </label>";

    $btn[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?routing-bcc=&instance-id=$instance_id');\">";
    $btn[]="<i class='fa fa-plus'></i> {new_bcc} </label>";

    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart;\">";
    $btn[]="<i class='fa fa-save'></i> {apply_configuration} </label>";

    $btn[]="</div>";
	$html[]="<table id='table-mynetworks' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	$array_direction[0]="{inbound}";
	$array_direction[1]="{outbound}";
	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{direction}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{item}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{forward_to}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{enabled}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$results=$q->QUERY_SQL("SELECT * FROM transport_maps WHERE instanceid=$instance_id ORDER BY addr");
	$array_field_relay_tls=$q->array_field_relay_tls;
	
	foreach ($results as $num=>$ligne){
		$relay_domains_restricted_text=null;
		$item=trim($ligne["addr"]);
		$service=$ligne["service"];
		$nexthope=$ligne["nexthope"];
		$nextport=intval($ligne["nextport"]);
        $certificate=null;
		$OtherDomains=unserializeb64($ligne["OtherDomains"]);
		$tls_text=null;
		$auth_text=null;
		$next="($service)&nbsp;$nexthope:$nextport";
        $item_text=$item;
		if($item=="*"){$item_text="{all_domains}";}

		if(intval($ligne["client_certificate"])==1){
		    $certificate="&nbsp;<i class='fas fa-file-certificate'></i> {$ligne["certificate"]}";
        }



		
		if($item==null){continue;}
		$itemencode=urlencode($item);
		$linkjs="Loadjs('$page?routing-js=$itemencode&instance-id=$instance_id')";
		
		if(preg_match("#BCC:(.+)#", $item,$re)){
			$next="{bcc_to} $nexthope";
			$item="{$re[1]}";
			$linkjs="Loadjs('$page?routing-bcc=$itemencode&instance-id=$instance_id')";
		}
		
		
		$id=md5($item);
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$id'>";
		
		$dir=$array_direction[$ligne["direction"]];
		$ligne2=$q->mysqli_fetch_array("SELECT domainname FROM relay_domains_restricted WHERE domainname='$item'");
		if($ligne2==null){$relay_domains_restricted=0;}else{$relay_domains_restricted=1;}
		
		if($relay_domains_restricted==1){$relay_domains_restricted_text="<br><small>{verifiy_recipients_on_target_server}</small>";}
		if($ligne["tls_enabled"]==1){
			if($ligne["tls_mode"]<>null){
				$tls_text="<br><small>{smtp_use_tls}: {$array_field_relay_tls[$ligne["tls_mode"]]}</small>";
			}
		}
		if($ligne["auth"]==1){
			$auth_text="<br><small>{authentication}: {$ligne["username"]}</small>";
		}

		if(count($OtherDomains)>10){
            $item_text=$item_text. " {and} ".count($OtherDomains)." {identical_domains}";
        }else{
            $dmz=array();
		    foreach ($OtherDomains as $dm=>$none){ if(strpos("  $dm","#")>0){continue;} $dmz[]=$dm; }
		    if(count($dmz)>0) {
                $item_text = $item_text . "," . @implode(", ", $dmz);
            }
        }
        $opt1="style='width:1%' nowrap";
        $optvert="style='vertical-align:middle;width:1%' class=center";
		
		$html[]="<td $opt1>$dir</td>";
		$html[]="<td nowrap><strong>".$tpl->td_href($item_text,$next,$linkjs)."</strong></td>";
		$html[]="<td nowrap><strong>$next</strong>$relay_domains_restricted_text$tls_text$auth_text$certificate</td>";
		$html[]="<td $optvert>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?routing-enable=$itemencode&instance-id=$instance_id')",null,"AsPostfixAdministrator")."</td>";
		$html[]="<td $optvert>".$tpl->icon_delete("Loadjs('$page?routing-delete=$itemencode&id=$id&instance-id=$instance_id')","AsPostfixAdministrator")."</td>";
		$html[]="</tr>";
	
	
	}

    $main=new maincf_multi($instance_id);
    $RestrictToInternalDomains=intval($main->GET("RestrictToInternalDomains"));
    $RestrictToInternalDomainsLists=$main->GET('RestrictToInternalDomainsLists');
    $RestrictToOutgoingDomains=intval($main->GET("RestrictToOutgoingDomains"));
    $RestrictToOutgoingDomainsLists=$main->GET('RestrictToOutgoingDomainsLists');
    $RestrictToOutgoingDomainsErrorMsg=$main->GET('RestrictToOutgoingDomainsErrorMsg');

    if($RestrictToInternalDomains==1){
        $js="Loadjs('fw.postfix.settings.php?section-outin=yes&$instance_id-id=0&function=$function')";
        $text=$tpl->td_href("{RestrictToInternalDomains}: $RestrictToInternalDomainsLists",null,$js);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id=''>";
        $html[]="<td $opt1>{$array_direction[0]}</td>";
        $html[]="<td nowrap><strong>$text</strong></td>";
        $html[]="<td nowrap></td>";
        $html[]="<td $optvert></td>";
        $html[]="<td $optvert></td>";
        $html[]="</tr>";
    }
    if($RestrictToOutgoingDomains==1){
        $js="Loadjs('fw.postfix.settings.php?section-outin=yes&$instance_id-id=0&function=$function')";
        $text=$tpl->td_href("{RestrictToOutgoingDomains}: $RestrictToOutgoingDomainsLists",null,$js);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id=''>";
        $html[]="<td $opt1>{$array_direction[1]}</td>";
        $html[]="<td nowrap><strong>$text</strong></td>";
        $html[]="<td nowrap></td>";
        $html[]="<td $optvert></td>";
        $html[]="<td $optvert></td>";
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

    $instancename=null;
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename=$ligne["instancename"];
    }

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{routing_tables} <small>$instancename</small>";
    $TINY_ARRAY["ICO"]="fas fa-bus";
    $TINY_ARRAY["EXPL"]="{postfix_routing_tables_explain}";
    $TINY_ARRAY["URL"]="postfix-routing";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="

	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-mynetworks').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	//
	
}

function routing_spf(){
    $tpl=new template_admin();
    $value=$_GET["routing-spf"];
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT addr,OtherDomains FROM transport_maps WHERE addr='$value'");
    $item = trim($ligne["addr"]);
    $OtherDomains = unserializeb64($ligne["OtherDomains"]);

    $myhostname=php_uname("n");
    $tb=explode(".",$myhostname);
    $netbiosname=$tb[0];unset($tb[0]);
    $domainname=@implode(".",$tb);

    $record_generated[]= "{$item}.  IN TXT or SPF   \"v=spf1 mx ip4:1.2.3.4 ip4:1.2.3.4.5 a:$netbiosname.$domainname a:{$netbiosname}2.$domainname";

    $zdoms=array();
    foreach ($OtherDomains as $domain=>$none){
        $zdoms[]="include:$domain";
    }

    if(count($zdoms)>0){
        $record_generated[]=@implode(" ",$zdoms);
    }

    $DNS_ENTRY=@implode(" ",$record_generated)." -all\"";



    $html="<table style='width:100%;margin-top:20px'><tr><td valign='top' width='99%'><p >{SPF_DNS_HOWTO}</p>
<div style='text-align:right'></td><td style='vertical-align: top'>".$tpl->button_autnonome("{verify_spf_entries}",
            "s_PopUpFull('https://mxtoolbox.com/SuperTool.aspx?action=spf:$item','1024','900');","fas fa-shield-check"
        ). "</div></td></tr></table>
    <textarea style='width:99%;height:250px;margin-top:20px'>$DNS_ENTRY</textarea>";


    echo $tpl->_ENGINE_parse_body($html);



}

