<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");

$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}
if(isset($_POST["xwizard"])){exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["record-js"])){record_js();exit;}
if(isset($_GET["record-info-js"])){record_info_js();exit;}
if(isset($_GET["record-info"])){record_info();exit;}
if(isset($_GET["record-popup"])){record_popup();exit;}
if(isset($_GET["enable-js"])){record_disable();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["td-column"])){td_column();exit;}

if(isset($_POST["id"])){record_save();exit;}
if(isset($_GET["id-deleted"])){deleted_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){record_delete();exit;}
if(isset($_GET["deletemem-js"])){record_deletem();exit;}
if(isset($_POST["deletemem"])){record_deletem_save();exit;}

if(isset($_GET["synchronize-js"])){synchronize_js();exit;}
if(isset($_GET["synchronize-popup"])){synchronize_popup();exit;}

if(isset($_GET["activedirectory-js"])){activedirectory_js();exit;}
if(isset($_GET["activedirectory-popup"])){activedirectory_popup();exit;}
if(isset($_POST["CNX"])){activedirectory_save();exit;}
page();

function activedirectory_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("Active Directory: {caches_rules}");
    $tpl->js_dialog7($title, "$page?activedirectory-popup=yes&function={$_GET["function"]}",810);
}
function record_deletem():bool{
    $tpl=new template_admin();
    $Name=$_GET["deletemem-js"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete($Name,"deletemem",$Name,"$('#$md').remove();");
}
function record_deletem_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $Name=$_POST["deletemem"];
    if($Name=="."){
        $Name="POINT";
    }
    $sock=new sockets();
    $data=$sock->REST_API("/dnscache/delete/memrecord/$Name");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error("API ERROR $sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Delete DNS record $Name");
}


function activedirectory_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ACTIVE_DIRECTORY_LDAP_CONNECTIONS=$tpl->ACTIVE_DIRECTORY_LDAP_CONNECTIONS();
    $CNS=array();
    $function=$_GET["function"]."();dialogInstance7.close();";
    foreach ($ACTIVE_DIRECTORY_LDAP_CONNECTIONS as $md5=>$array){
        $port=$array["LDAP_PORT"];
        $server=$array["LDAP_SERVER"];
        $dn=$array["LDAP_DN"];
        $CNS[$md5]="$server:$port ($dn)";

    }
    $server_ip=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($server);
    $form[]=$tpl->field_array_hash($CNS,"CNX","#nonull:{activedirectory_connection}");
    $form[]=$tpl->field_ipv4("ADIP","{activedirectory_addr}",$server_ip,true);
    $form[]=$tpl->field_numeric("ttl","TTL ({seconds})",3600,"{DNS_TTL_EXPLAIN}");

    echo $tpl->_ENGINE_parse_body($tpl->form_outside("Active Directory: {caches_rules}", @implode("\n", $form),"{unbound_ad_explain}","{create}","$function","AsDnsAdministrator"));


}

function record_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_record}");
	$id=intval($_GET["record-js"]);
	if($id>0){
		$q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
		$sql="SELECT name,`type`,content FROM records WHERE id=$id";
		$ligne=$q->mysqli_fetch_array($sql);
		$ligne["content"]=substr($ligne["content"],0,20);
		$title="{$ligne["name"]}::{$ligne["type"]}::{$ligne["content"]}...";
		
	}
	$tpl->js_dialog7($title, "$page?record-popup=$id&function={$_GET["function"]}",810);

	
}
function synchronize_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{synchronize}");
    $tpl->js_dialog7($title, "$page?synchronize-popup=yes&function={$_GET["function"]}",810);
}

function synchronize_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $CacheDNSRemoteSynchronize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteSynchronize"));
    $CacheDNSRemoteServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteServer");
    $CacheDNSRemoteUsername=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteUsername");
    $CacheDNSRemoteServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteServerPort"));
    $CacheDNSRemoteServerPass=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteServerPass"));
    if($CacheDNSRemoteServerPort==0){$CacheDNSRemoteServerPort=9000;}



    $html[]="<div id='powerdns-synchronize' style='margin-bottom:20px'></div>";
    $form[]=$tpl->field_checkbox("CacheDNSRemoteSynchronize","{enable_feature}",$CacheDNSRemoteSynchronize,true);
    $form[]=$tpl->field_text("CacheDNSRemoteServer","{REMOTE_ARTICA_SERVER}",$CacheDNSRemoteServer,true);
    $form[]=$tpl->field_numeric("CacheDNSRemoteServerPort","{REMOTE_ARTICA_SERVER_PORT}",$CacheDNSRemoteServerPort);
    $form[]=$tpl->field_text("CacheDNSRemoteUsername","{REMOTE_ARTICA_USERNAME}",$CacheDNSRemoteUsername);
    $form[]=$tpl->field_password2("CacheDNSRemoteServerPass","{REMOTE_ARTICA_PASSWORD}",$CacheDNSRemoteServerPass);
    $jsafter="";
    $html[]=$tpl->form_outside("{synchronize}", @implode("\n", $form),"{CacheDNSSynchronizeExplain}","{apply}","$jsafter","AsDnsAdministrator");
    $html[]="";
    echo $tpl->_ENGINE_parse_body($html);
}


function record_disable(){
	$q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
	$id=$_GET['enable-js'];
	$sql="SELECT disabled FROM records WHERE id=$id";
	$ligne2=$q->mysqli_fetch_array($sql);
	$disabled=intval($ligne2["disabled"]);

	if($disabled==0){
		$q->QUERY_SQL("UPDATE records SET disabled=1 WHERE id=$id");
		if(!$q->ok){echo $q->mysql_error;}
		return false;
	}
	$q->QUERY_SQL("UPDATE records SET disabled=0 WHERE id=$id");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return false;}
    admin_tracks("Disable DNS record $id");
	return false;
}

function record_info_js(){
	$type=$_GET["type"];
	$id=$_GET["id"];
	$tpl=new template_admin();
	$prefix="{new_record} ";
	$title="{$prefix}Type:$type";

	$page=CurrentPageName();
	$tpl->js_dialog7($title, "$page?record-info=yes&type=$type&id=$id&function={$_GET["function"]}",810);
	
}
function record_delete():bool{
	$id=$_POST["delete"];
    $tpl            = new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/dnscache/delete/record/$id");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error("API ERROR $sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
   return admin_tracks("Delete DNS record $id");
}

function activedirectory_save(){
    $tpl            = new template_admin();
    $tpl->CLEAN_POST();

    $CONNECTIONS    = $tpl->ACTIVE_DIRECTORY_LDAP_CONNECTIONS();
    $CNX            = $_POST["CNX"];
    $settings       = $CONNECTIONS[$CNX];
    $ADIP           = $_POST["ADIP"];
    $TTL            = $_POST["ttl"];


    if(!isset($settings["LDAP_SUFFIX"])){
        echo "jserror:".$tpl->_ENGINE_parse_body("{ldap_suffix} {missing}");
        return false;
    }

    $settings["netbios"]=null;
    if(!isset($settings["fullhosname"])){$settings["fullhosname"]=$settings["LDAP_SERVER"];}
    if(!isset($settings["WINDOWS_DNS_SUFFIX"])){
        if(preg_match("#^(.+?)\.(.+)$#",$settings["fullhosname"],$re)){
            $settings["WINDOWS_DNS_SUFFIX"]=$re[2];
            $settings["netbios"]=$re[1];
        }
    }

    if($settings["netbios"]==null){
        if(preg_match("#^(.+?)\.(.+)$#",$settings["fullhosname"],$re)){
            $settings["netbios"]=$re[1];
        }
    }
    if($settings["netbios"]==null){
        echo "jserror:".$tpl->_ENGINE_parse_body("Netbios name: {missing} {$settings["fullhosname"]}");
        return false;
    }
    $LDAP_SUFFIX    = $settings["LDAP_SUFFIX"];
    $NETBIOS        = strtoupper($settings["netbios"]);





    $LDAP_SUFFIX_DNS=strtolower($LDAP_SUFFIX);
    $LDAP_SUFFIX_DNS=str_replace("dc=","",$LDAP_SUFFIX_DNS);
    $LDAP_SUFFIX_DNS=str_replace(",",".",$LDAP_SUFFIX_DNS);
    $nTDSDSA=null;



    $ad=new ActiveDirectory(0,$settings);
    $tty=$ad->ObjectProperty($LDAP_SUFFIX);

    if($ad->ldap_failed){
        echo "jserror:".$tpl->_ENGINE_parse_body($ad->ldap_last_error);
        return false;
    }

    if(!isset($tty["SOURCE"])){
        echo "jserror:".$tpl->_ENGINE_parse_body("$LDAP_SUFFIX (objectguid) Array {missing} ");
        return false;
    }

    $SOURCE=$tty["SOURCE"];


    if(!isset($SOURCE["objectguid"])){
        echo "jserror:".$tpl->_ENGINE_parse_body("$LDAP_SUFFIX objectguid {missing}");
        return false;
    }

    $objectguid=_to_p_guid($SOURCE["objectguid"][0]);
    if(!preg_match("#^[0-9a-z\-]+$#",$objectguid)){
        echo "jserror:".$tpl->_ENGINE_parse_body("$LDAP_SUFFIX objectguid $objectguid {incorrect}");
        return false;
    }



    if(!isset($tty["SOURCE"])){
        $nTDSDSA_dn="CN=DNS Settings,CN=$NETBIOS,CN=Servers,CN=Premier-Site-par-defaut,CN=Sites,CN=Configuration,$LDAP_SUFFIX";
        $tty=$ad->ObjectProperty($nTDSDSA_dn);

    }

    if(!isset($tty["SOURCE"])){
        echo "jserror:".$tpl->_ENGINE_parse_body("nTDSDSA SOURCE {missing}");
        return false;
    }

    $SOURCE=$tty["SOURCE"];

    if(!isset($SOURCE["objectguid"])){
        echo "jserror:".$tpl->_ENGINE_parse_body("nTDSDSA: objectguid {missing}");
        return false;
    }

    $nTDSDSA=_to_p_guid($SOURCE["objectguid"][0]);
    if(!preg_match("#^[0-9a-z\-]+$#",$nTDSDSA)){
        echo "jserror:".$tpl->_ENGINE_parse_body("nTDSDSA: objectguid $nTDSDSA {incorrect}");
        return false;
    }
    $NETBIOS=strtolower($NETBIOS);
    $sql="INSERT INTO records (name,type,content,ttl,prio) VALUES 
        ('$LDAP_SUFFIX_DNS','AD','$ADIP:$NETBIOS:$objectguid:$nTDSDSA','$TTL','0')
        ";
    admin_tracks("Create a new Active directory DNS record for $LDAP_SUFFIX_DNS with $ADIP:$NETBIOS:$objectguid:$nTDSDSA");
    $q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $q->QUERY_SQL($sql);

    if(!$q->ok){echo $q->mysql_error;return false;}

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?reload=yes");
    return true;
}

function _to_p_guid( $guid )
{
    $hex_guid = unpack( "H*hex", $guid );
    $hex    = $hex_guid["hex"];

    $hex1   = substr( $hex, -26, 2 ) . substr( $hex, -28, 2 ) . substr( $hex, -30, 2 ) . substr( $hex, -32, 2 );
    $hex2   = substr( $hex, -22, 2 ) . substr( $hex, -24, 2 );
    $hex3   = substr( $hex, -18, 2 ) . substr( $hex, -20, 2 );
    $hex4   = substr( $hex, -16, 4 );
    $hex5   = substr( $hex, -12, 12 );

    $guid = $hex1 . "-" . $hex2 . "-" . $hex3 . "-" . $hex4 . "-" . $hex5;

    return $guid;
}


function record_wizard(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$title="{new_record}";
	$bname="{add}";
	foreach (GetRtypes() as $record_type) {$TYPES[$record_type]=$record_type;}

	$fielddomain_id=md5("xdomain_id$tpl->suffixid");
	$fieldtype_id=md5("xtype$tpl->suffixid");
	$tpl->field_hidden("xwizard","yes");
	$form[]=$tpl->field_array_hash($TYPES, "xtype", "{type} (IN)", null,true,"blur()");
	$html[]=$tpl->form_outside("$title", @implode("\n", $form),"{ADD_DNS_ENTRY_TEXT}",$bname,"jsAfter$fielddomain_id()","AsDnsAdministrator");
	
	$html[]="
	<script>
		function jsAfter$fielddomain_id(){
			var type=document.getElementById('$fieldtype_id').value;
			Loadjs('$page?record-info-js=yes&type='+type+'&id=0&function={$_GET["function"]}');
		}
	</script>";
	
	echo @implode("\n", $html);
	
	
}

function record_info(){
    $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $tpl = new template_admin();
    $record_type = $_GET["type"];
    $page = CurrentPageName();
    $function=$_GET["function"];
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    VERBOSE("PowerDNSEnableClusterSlave=$PowerDNSEnableClusterSlave", __LINE__);

    $explain_form = null;
    $LABEL_NAME = "{name}";
    $functionToLoad = null;
    $record_type_explain = $record_type;
    $RRCODE_LABEL["SOA"] = "{zone}";
    $RRCODE["NS"] = "Name Server Record";
    $RRCODE["SOA"] = "Start of Authority Record";


    $RRCODE_EXPLAIN["SOA"] = "{SOA_RECORD_EXPLAIN}";
    $RRCODE_EXPLAIN["NS"] = "{NS_RECORD_EXPLAIN}";

    if (isset($RRCODE["$record_type"])) {
        $record_type_explain = $RRCODE[$record_type];
    }
    if (isset($RRCODE_EXPLAIN["$record_type"])) {
        $explain_form = $RRCODE_EXPLAIN[$record_type];
    }
    if (isset($RRCODE_LABEL["$record_type"])) {
        $LABEL_NAME = $RRCODE_LABEL[$record_type];
    }
    if (isset($_GET["function"])) {
        if ($_GET["function"] <> null) {
            $functionToLoad = "{$_GET["function"]}()";
        }
    }

    $title = "$record_type_explain";
    $SHOW_TTL = true;
    $id = intval($_GET["id"]);
    $btname = "{add}";
    $jsafter = "dialogInstance7.close();$functionToLoad;Loadjs('$page?filltable=$id&function=$function');";


    if ( (strtoupper($record_type) == "TXT") or (strtoupper($record_type) == "SPF") ){
        $SHOW_TTL = false;
    }



    if ($id > 0) {
        $sql = "SELECT * FROM records WHERE id=$id";
        $ligne = $q->mysqli_fetch_array($sql);
        $btname = "{apply}";
        $tpl->form_add_button("{delete}", "DeleteDNSR$id()");
    }

    if (!is_numeric($ligne["ttl"])) {
        $ligne["ttl"] = 3600;
    }

    $form[] = $tpl->field_hidden("id", $id);
    $form[] = $tpl->field_hidden("type", $record_type);
    $form[] = $tpl->field_text("name", "$LABEL_NAME", $ligne["name"], true, "");


	
	switch (strtoupper($record_type)) {
		case "A":
			$ADDPTR=0;
			if($id>0){
				$content_array = preg_split("/\./", $ligne["content"]);
				$content_rev = sprintf("%d.%d.%d.%d.in-addr.arpa", $content_array[3], $content_array[2], $content_array[1], $content_array[0]);
				$sql="SELECT id FROM records WHERE name='$content_rev'";
				$ligne2=$q->mysqli_fetch_array($sql);
				$id_reverse=intval($ligne2["id"]);
				
				if($id_reverse>0){
					$ADDPTR=1;
					$form[]=$tpl->field_hidden("ptr_id",$id_reverse);

				}
			}
		
			$form[]=$tpl->field_ipaddr("content", "{ipaddr}", $ligne["content"]);
			$form[]=$tpl->field_checkbox("ADDPTR","{dns_ptr_text}",$ADDPTR);
			break;
			
			
		case "TXT":
			$form[]=$tpl->field_textarea("content", "{content}", $ligne["content"],"636px","110px");
			break;
			
		case "CERT":
			$form[]=$tpl->field_textarea("content", "{certificate}", $ligne["content"],"636px","110px");
			break;		

		case "SSHFP":
			$form[]=$tpl->field_textarea("content", "SSH KEY", $ligne["content"],"636px","110px");
			break;

		case "IPSECKEY":
			$form[]=$tpl->field_textarea("content", "IP SECKEY", $ligne["content"],"636px","110px");
			break;	

			
		case "TKEY":
			$form[]=$tpl->field_textarea("content", "Secret Key Establishment for DNS", $ligne["content"],"636px","110px");
			break;			
			
		case "SIG":
			$form[]=$tpl->field_textarea("content", "Domain Name System Security Extensions", $ligne["content"],"636px","110px");
			break;			
			
		case "SPF":
			$form[]=$tpl->field_textarea("content", "SPF", $ligne["content"],"636px","50px");
			break;

        case "MX":
            $SHOW_TTL=false;
            $form[]=$tpl->field_text("content", "{smtp_server_address}", $ligne["content"],true);
            if(intval($ligne["prio"])==0){$ligne["prio"]=5;}
            if($id==0){$form[]=$tpl->field_numeric("prio","PRIO",$ligne["prio"]);}
            break;

		case "SOA":
			$SHOW_TTL=false;
			$TR=preg_split('/\s+/', $ligne["content"]);
			$form[]=$tpl->field_text("SOA_MNAME", "MNAME", $TR[0],true,"{SOA_COMPUTER_EXPLAIN}");
			$form[]=$tpl->field_text("SOA_EMAIL", "RNAME", $TR[1],true,"{SOA_EMAIL_EXPLAIN}");
			if(intval($TR[2])==0){$TR[2]=date("YmdHi");}
			if(intval($TR[3])==0){$TR[3]=86400;}
			if(intval($TR[4])==0){$TR[4]=7200;}
			if(intval($TR[5])==0){$TR[5]=2419200;}
			if(intval($TR[6])==0){$TR[6]=240;}
			
			$form[]=$tpl->field_numeric("SOA_SERIAL","{serial}",$TR[2],"{DNS_SOA_SERIAL_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_REFRESH","{refresh}",$TR[3],"{SOA_REFRESH_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_RETRY","{retry}",$TR[4],"{SOA_RETRY_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_EXPIRE","{expire}",$TR[5],"{SOA_EXPIRE_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_NEG","{negative_cache}",$TR[6],"{SOA_NEG_EXPLAIN}");
			break;

        case "PTR":
            $form[] = $tpl->field_text("content", "{hostname}",$ligne["content"]);

            if($ligne["name"]==null) {
                $form[] = $tpl->field_ipaddr("name", "{ipaddr}", $ligne["name"], true, "");
            }else{
                $form[] = $tpl->field_text("name", "PTR {record}", $ligne["name"], true, "");
            }

            break;

        case "NS":
            $form[]=$tpl->field_text("content", "{hostname}", $ligne["content"]);
            break;

			
		default:
			$form[]=$tpl->field_text("content", "{value}", $ligne["content"]);
	}
	
	if($id>0){$form[]=$tpl->field_numeric("prio","PRIO",$ligne["prio"]);}
	if($SHOW_TTL){$form[]=$tpl->field_numeric("ttl","TTL ({seconds})",$ligne["ttl"],"{DNS_TTL_EXPLAIN}");}
	

	if($PowerDNSEnableClusterSlave==1){
		$tpl->FORM_LOCKED=true;
		$tpl->this_form_locked_explain="{form_locked_cluster_client}";
	}
	
	$html[]=$tpl->form_outside($title, @implode("\n", $form),$explain_form,$btname,$jsafter,"AsDnsAdministrator");
	$html[]="<script>";
	$html[]="function DeleteDNSR$id(){";
	$html[]="Loadjs('$page?delete-js=$id&byPOP=yes&function={$_GET["function"]}');";
	$html[]="}";
	$html[]="</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function record_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$id=intval($_POST["id"]);
	unset($_POST["id"]);
    $fqdn_name=null;

    writelogs("Type.....: {$_POST["type"]}");
    writelogs("name.....: {$_POST["name"]}");
    if(!isset($_POST["ttl"])){$_POST["ttl"]=3600;}
    if(!isset($_POST["prio"])){$_POST["prio"]=1;}
    if(!isset($_POST["ADDPTR"])){$_POST["ADDPTR"]=1;}
    $AddGlobal=false;
    $MAIN["name"] = $_POST["name"];
    $MAIN["type"] = $_POST["type"];
    $MAIN["content"] = $_POST["content"];
    $MAIN["ttl"] = $_POST["ttl"];
    $MAIN["prio"] = intval($_POST["prio"]);
    $MAIN["id"] = $id;
    $MAIN["CreatePTR"]=intval($_POST["ADDPTR"]);

    if(strpos(" {$_POST["name"]}","*.")>0){
        $AddGlobal=true;
    }
	
	if($_POST["type"]=="SOA"){
		$SOA_MNAME=$_POST["SOA_MNAME"];
		$SOA_EMAIL=$_POST["SOA_EMAIL"];
		$SOA_SERIAL=$_POST["SOA_SERIAL"];
		$SOA_REFRESH=$_POST["SOA_REFRESH"];
		$SOA_RETRY=$_POST["SOA_RETRY"];
		$SOA_EXPIRE=$_POST["SOA_EXPIRE"];
		$SOA_NEG=$_POST["SOA_NEG"];
        $MAIN["ttl"]=$SOA_NEG;
        $MAIN["content"]="$SOA_MNAME $SOA_EMAIL $SOA_SERIAL $SOA_REFRESH $SOA_RETRY $SOA_EXPIRE $SOA_NEG";
    }

    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/dnscache/record", $MAIN);
    admin_tracks("Save DNS record $fqdn_name {$_POST["content"]}");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error("API ERROR {$GLOBALS["CLASS_SOCKETS"]->mysql_error}");
        return false;
    }

    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return true;

}


function record_popup(){
	$id=intval($_GET["record-popup"]);
	if($id==0){record_wizard();return;}
	$q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
	$tpl=new template_admin();
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

	$title_domain=null;
	$t=time();
	$bname="{add}";
	$title="{new_record}";
    $jsafter=null;

	VERBOSE("PowerDNSEnableClusterSlave=$PowerDNSEnableClusterSlave", __LINE__);

	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM records WHERE id=$id";
		$ligne=$q->mysqli_fetch_array($sql);
		$title="{type} {$ligne["type"]}&nbsp;&laquo;&nbsp;{$ligne["name"]}&nbsp;&laquo;&nbsp{$ligne["content"]}";

	}

	foreach (GetRtypes() as $record_type) {$TYPES[$record_type]=$record_type;}
	if($PowerDNSEnableClusterSlave==1){$btname=null;}
    $form[]=$tpl->field_text("name","{record}",$ligne["name"],true);
	$form[]=$tpl->field_array_hash_simple($TYPES, "type", "{type} (IN)", $ligne["type"],true,"FormRecordType$t()");
	$html[]=$tpl->form_outside("$title", @implode("\n", $form),"{ADD_DNS_ENTRY_TEXT}",$bname,"$jsafter","AsDnsAdministrator");
	$html[]="";
}

function deleted_js()
{

    $page=CurrentPageName();
    $id=$_GET["id-deleted"];

    $js[]="$('#{$id}').remove();";
    if(isset($_GET["function"])){
        if($_GET["function"]<>null){$js[]=$_GET["function"]."()";}
    }

    if(isset($_GET["byPOP"])) {
        $js[] = "dialogInstance7.close();";
        $js[] = "if(document.getElementById('table-dns-records-loader')){";
        $js[]="\tLoadAjax('table-dns-records-loader','$page?main=yes');";
        $js[]="\t}";
    }

    $js[]="if(document.getElementById('TABLEAU-TOP-RECHERCHE')){";
    $js[]="\tvar Search=encodeURIComponent(document.getElementById('top-search').value);";
    $js[]="\tLoadAjax('MainContent','fw.top.search.php?search='+Search);";
    $js[]="\t}";
    header("content-type: application/x-javascript");
    echo @implode("\n",$js);

    $jslog=base64_decode($_GET["jslog"]);
    admin_tracks("DNS record deleted: $jslog");
}

function delete_js(){
    $page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$id=$_GET["delete-js"];


	$q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
	$sql="SELECT content,`type`,`name` FROM records WHERE id=$id";
	$ligne=$q->mysqli_fetch_array($sql);
	$value=$ligne["content"];
	$type=$ligne["type"];
	$name=$ligne["name"];
	$delete=$tpl->javascript_parse_text("{delete}");


    $final[]="id-deleted={$_GET["id"]}";


	if(isset($_GET["function"])){
	    if($_GET["function"]<>null){
	        $function=$_GET["function"]."()";
            $final[]="&function=$function";
        }
    }
    if(strlen( (string) $value)>30){
        $value=substr($value,0,27)."...";
    }
	if(isset($_GET["byPOP"])) {
        $final[] = "&byPOP=yes";
    }

	$final[]="&jslog=".base64_encode("$name $type $value");
	$jsfinal="Loadjs('$page?".@implode("",$final)."')";
	$tpl->js_confirm_delete("$delete:<br>$name<br>$type<br>$value<br>ID: ($id) ?","delete",$id,$jsfinal);

	
}



function GetRtypes(){
	$rtypes = array(
			'A',
			'AAAA',
			'AFSDB',
			'CERT',
			'CNAME',
			'DHCID',
			'DLV',
			'DNSKEY',
			'DS',
			'EUI48',
			'EUI64',
			'HINFO',
			'IPSECKEY',
			'KEY',
			'KX',
			'LOC',
			'MINFO',
			'MR',
			'MX',
			'NAPTR',
			'NS',
			'NSEC',
			'NSEC3',
			'NSEC3PARAM',
			'OPT',
			'PTR',
			'RKEY',
			'RP',
			'RRSIG',
			'SIG',
			'SOA',
			'SPF',
			'SRV',
			'SSHFP',
			'TLSA',
			'TSIG',
			
			'TXT',
			'WKS',
	);

	return $rtypes;


}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    unbound_databases();
    $html=$tpl->page_header("{DNS_RECORDS}","fas fa-list-ol","{unbound_dns_records_explain}","$page?tabs=yes","dns-cache-records","progress-firehol-restart","main=yes","table-dns-records-loader");

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}




function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $search_type=null;
	

    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

	$delete=$tpl->javascript_parse_text("{delete}");

	$t=time();
	$content=$tpl->_ENGINE_parse_body("{content}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$record=$tpl->_ENGINE_parse_body("{record}");



	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	


	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width='1%'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width='20%'>{$record}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width='1%'>{$content}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width='1%'>{$type}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width='1%'>{$delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $qSearch=null;
    $search=trim($_GET["search"]);
    $OrgSearch=$search;

    if($search<>null){
        $search="*$search*";
        $search=str_replace("**","*",$search);$search=str_replace("**","*",$search);$search=str_replace("*","%",$search);
        $qSearch="WHERE ( (name LIKE '$search') OR (type LIKE '$search') OR (content LIKE '$search') )";
    }
    $sql="SELECT * FROM records $qSearch ORDER BY name LIMIT 0,500";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->div_error("LINE ".__LINE__." $q->mysql_error");return;}
	$s1prc="style='width:1%'";
    $s50prc="style='width:50%'";

    if(strlen($OrgSearch)>0){
        $OrgSearch=str_replace(".","\.",$OrgSearch);
        $OrgSearch=str_replace("*",".*?",$OrgSearch);
    }

	foreach ($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$md=md5(serialize($ligne));
		$id=$ligne["id"];
		$color="#000000";
		$type=$ligne["type"];
		$name=$ligne["name"];
		$content=$ligne["content"];
		$disabled=intval($ligne["disabled"]);
		if(strlen($content)>90){$content=substr($content, 0,87)."...";}
		$jshost="Loadjs('$page?record-info-js=yes&type=$type&id=$id');";
		if($disabled==0){$enable=true;}else{$enable=false;}

        $icon_delete=$tpl->icon_nothing();
        if($PowerDNSEnableClusterSlave==0){
            $icon_delete=$tpl->icon_delete("Loadjs('$page?delete-js=$id&id=$md')","AsDnsAdministrator");
        }
        $AL["$name.$type"]=true;

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td $s1prc>".$tpl->td_href($id,null,$jshost)."</td>";
		$html[]="<td $s50prc><span id='dns-name-$id'>".$tpl->td_href($name,null,$jshost)."</span></td>";
		$html[]="<td style='font-weight:bold;width:50%'><span id='dns-content-$id'>$content</span></td>";
        $html[]="<td $s1prc><span id='dns-type-$id'>".$tpl->td_href($type,null,$jshost)."</span></td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>$icon_delete</td>";
		$html[]="</tr>";
	}
    $sock=new sockets();
    $data=$sock->REST_API("/dnscache/records");
    $Mainjson=json_decode($data);
    $c=0;
    foreach ($Mainjson->Records as $json){
        $c++;
        if(isset($AL["$json->name$json->type"])){continue;}
        if(strlen($OrgSearch)>0){
            if(!preg_match("#$OrgSearch#","$json->name$json->content")){
                continue;
            }
        }
        $nameEncoded=urlencode($json->name);
        $md=md5("$json->name$json->type$json->content$c");
        $icon_delete=$tpl->icon_delete("Loadjs('$page?deletemem-js=$nameEncoded&md=$md')","AsDnsAdministrator");
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $s1prc><span class='label label-default'>Cache</span></td>";
        $html[]="<td $s50prc><span style='font-weight:bold;'>$json->name</span></td>";
        $html[]="<td style='font-weight:bold;width:50%'>$json->content</td>";
        $html[]="<td $s1prc><span id='dns-type-$id'>$json->type</span></td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>$icon_delete</td>";
        $html[]="</tr>";
    }

    if(!isset($_SESSION["UNBOUND_RECORDS_SEARCH"])){$_SESSION["UNBOUND_RECORDS_SEARCH"]="";}
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
//CreateDNSRecord fa fa-list-ol

    $t=time();
    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array("Loadjs('$page?record-js=0&function={$_GET["function"]}');", ico_plus, "{new_record}");

        $topbuttons[] = array(" Loadjs('$page?activedirectory-js=yes&function={$_GET["function"]}');", "fab fa-windows", "Active Directory");
    }

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{DNS_RECORDS}";
    $TINY_ARRAY["ICO"]="fas fa-list-ol";
    $TINY_ARRAY["EXPL"]="{unbound_dns_records_explain}";
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<div><small>$sql</small></div>
<script>
    function  CreateDNSRecord(){
        Loadjs('$page?record-js=0&function={$_GET["function"]}');
        
    }
    function ActiveDirectoryRecord(){
        Loadjs('$page?activedirectory-js=yes&function={$_GET["function"]}');
    }
    
    $headsjs;
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
</script>";

echo $tpl->_ENGINE_parse_body($html);
}
function filltable(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $id=intval($_GET["filltable"]);
    $function=$_GET["function"];
    $f[]="LoadAjaxSilent('dns-name-$id','$page?id=$id&td-column=name&function=$function');";
    $f[]="LoadAjaxSilent('dns-domain-$id','$page?id=$id&td-column=domain&function=$function');";
    $f[]="LoadAjaxSilent('dns-type-$id','$page?id=$id&td-column=type&function=$function');";
    $f[]="LoadAjaxSilent('dns-content-$id','$page?id=$id&td-column=content&function=$function');";

echo @implode("\n",$f);



}

function td_column(){
    $page=CurrentPageName();
    $id=$_GET["id"];
    $field=$_GET["td-column"];
    $tpl=new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $sql = "SELECT * FROM records WHERE id=$id";
    $ligne = $q->mysqli_fetch_array($sql);
    $type=$ligne["type"];
    $jshost="Loadjs('$page?record-info-js=yes&type=$type&id=$id');";


    if($field=="content"){
        $content=$ligne["content"];
        if(strlen($content)>90){$content=substr($content, 0,87)."...";}
        echo $content;
        return;
    }

    echo $tpl->td_href($ligne[$field],null,$jshost);

}

function unbound_databases(){
    $q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
    @chmod("/home/artica/SQLITE/unbound.db", 0644);
    @chown("/home/artica/SQLITE/unbound.db", "www-data");

    $sql="CREATE TABLE IF NOT EXISTS records (
 `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  name                  TEXT DEFAULT NULL,
  type                  TEXT DEFAULT NULL,
  content               TEXT DEFAULT NULL,
  ttl                   INT DEFAULT NULL,
  prio                  INT DEFAULT NULL,
  disabled              INTEGER DEFAULT 0,
  ordername             TEXT DEFAULT NULL,
  auth                  INTEGER DEFAULT 1 )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        $tpl=new template_admin();
        echo $tpl->div_error("Fatal: $q->mysql_error (".__LINE__.")\n");
    }

}