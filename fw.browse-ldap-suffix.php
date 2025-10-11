<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["ldap_server"])){saveRootdse();exit;}
if(isset($_GET["results"])){results();exit;}
js();
function js(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	foreach ($_GET as $key=>$value){
        $value=urlencode($value);
		$tt[]="$key=$value";
	}
	
	$tpl->js_dialog13("{browse} {ldap_suffix}", "$page?popup=yes&".@implode("&", $tt));
	
	
	
	
}

function results(){
	
	header("content-type: application/x-javascript");
	$target_field=$_SESSION["LDABROWSE"]["field-id"];
	$ldap_server=$_SESSION["LDABROWSE"]["ldap_server"];
	$ldap_port=$_SESSION["LDABROWSE"]["ldap_port"];
	$suffix=$_SESSION["LDABROWSE"]["suffix"];
	$idOfServer=$_SESSION["LDABROWSE"]["idOfServer"];
	$idOfPort=$_SESSION["LDABROWSE"]["idOfPort"];
	

	$scr[]="if(document.getElementById('$idOfServer')){
				document.getElementById('$idOfServer').value='$ldap_server';
			}";
	$scr[]="if(document.getElementById('$idOfPort')){
	document.getElementById('$idOfPort').value='$ldap_port';
	}";	
	
	$scr[]="if(document.getElementById('$target_field')){
	document.getElementById('$target_field').value='$suffix';
	}";	
	
	$scr[]="dialogInstance13.close();";
	
	echo @implode("\n", $scr);
	
}

function SyslogAd($text){
    if(!function_exists("openlog")){return true;}
    $f=basename(__FILE__);
    $text="[$f]: $text";
    openlog("activedirectory", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}


function saveRootdse(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->CLEAN_POST();
	
	$ldap_connection=ldap_connect($_POST["ldap_server"], $_POST["ldap_port"] );
	ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	$ldapbind=@ldap_bind($ldap_connection, null,null);
	
	$attributes = array('vendorName',
			'vendorVersion',
			'namingContexts',
			'altServer',
			'supportedExtension',
			'supportedControl',
			'supportedSASLMechanisms',
			'supportedLDAPVersion',
			'subschemaSubentry' );
	
	if(!$ldapbind){
        SyslogAd("{$_POST["ldap_server"]}:{$_POST["ldap_port"]} anonymous failed");
		$errornumber= ldap_errno($ldapbind);
		
		switch (ldap_errno($ldap_connection)) {
			case 0x31:
				echo "jserror:Bad username or password. Please try again.";
				break;
			case 0x32:
				echo "jserror:Insufficient access rights.";
				break;
			case 81:
				echo "jserror:Unable to connect to the LDAP server<br>please,<br>verify if ldap daemon is running\n or the ldap server address";
				break;
			case -1:
	
				break;
			default:
				echo "jserror:Could not bind to the LDAP server Err.$errornumber<br>". ldap_err2str(ldap_errno($ldap_connection));
		}
		@ldap_close($ldap_connection);
		return;
	}
	
	$result=@ldap_read($ldap_connection, "", "(objectClass=*)",$attributes);
	
	
	if (!$result) {
		$errornumber= ldap_errno($ldap_connection);
		$error=ldap_err2str($errornumber);
		echo "jserror:Err.$errornumber, $error";
		@ldap_close($ldap_connection);
		return array();
			
	}
		
	$hash=ldap_get_entries($ldap_connection,$result);
	
	if(!isset($hash[0]["namingcontexts"][0])){
		echo "jserror: NamingContexts attribute not found...";
		
	}
	
	$_SESSION["LDABROWSE"]["field-id"]=$_POST["field-id"];
	$_SESSION["LDABROWSE"]["ldap_server"]=$_POST["ldap_server"];
	$_SESSION["LDABROWSE"]["ldap_port"]=$_POST["ldap_port"];
	$_SESSION["LDABROWSE"]["suffix"]=$hash[0]["namingcontexts"][0];
	$_SESSION["LDABROWSE"]["idOfServer"]=$_POST["idOfServer"];
	$_SESSION["LDABROWSE"]["idOfPort"]=$_POST["idOfPort"];
	
}


function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$field_id=$_GET["field-id"];
	$idOfServer=$_GET["idOfServer"];
	$idOfPort=$_GET["idOfPort"];
	
	
	$idOfUser=$_GET["idOfUser"];
	$idOffPassword2=$_GET["idOffPassword2"];
	$idOffpassword=$_GET["idOffpassword"];
	
	
	$ldap_server_id=md5("ldap_server$tpl->suffixid");
	$ldap_port_id=md5("ldap_port$tpl->suffixid");
	$ldap_password=md5("ldap_password$tpl->suffixid");
	$ldap_password2=md5("2ldap_password$tpl->suffixid");
	$ldap_user_id=md5("ldap_user$tpl->suffixid");
	
	$tpl->field_hidden("field-id", $field_id);
	$tpl->field_hidden("idOfServer", $idOfServer);
	$tpl->field_hidden("idOfPort", $idOfPort);
	$form[]=$tpl->field_text("ldap_server","{openldap_server}",null);
	$form[]=$tpl->field_numeric("ldap_port","{listen_port}", null);
	
	$html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{search}","Loadjs('$page?results=yes')",null);
	
	$html[]="<script>
			if(document.getElementById('$idOfServer')){
				document.getElementById('$ldap_server_id').value=document.getElementById('$idOfServer').value;
			}
			if(document.getElementById('$idOfPort')){
				document.getElementById('$ldap_port_id').value=document.getElementById('$idOfPort').value;
			}
			
	</script>
			";
	
	echo $tpl->_ENGINE_parse_body($html);

}