<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__) ."/ressources/externals/class.aesCrypt.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["PowerDNSEnableClusterMaster"])){Save();exit;}

page();



function page(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	



	$PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	
	$PowerDNSClusterPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterPassword"));
	

	$PowerDNSClusterSlaveInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveInterface"));
	$PowerDNSClusterMasterAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
	$PowerDNSClusterMasterPort=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterPort"));
	
	if($PowerDNSClusterMasterPort==0){$PowerDNSClusterMasterPort=9000;}
	
	$form[]=$tpl->field_section("{master_mode}","{pdns_master_mode_explain}");
	$form[]=$tpl->field_checkbox("PowerDNSEnableClusterMaster","{enable_master_mode}",$PowerDNSEnableClusterMaster,"PowerDNSClusterPassword");
	$form[]=$tpl->field_password2("PowerDNSClusterPassword", "{password}", $PowerDNSClusterPassword);
	
	$form[]=$tpl->field_section("{slave_mode}","{pdns_slave_mode_explain}");
	$form[]=$tpl->field_checkbox("PowerDNSEnableClusterSlave","{enable_slave_mode}",$PowerDNSEnableClusterSlave,"PowerDNSClusterSlaveInterface,PowerDNSClusterMasterAddress,PowerDNSClusterMasterPort");

	$form[]=$tpl->field_interfaces("PowerDNSClusterSlaveInterface", "{interface}", $PowerDNSClusterSlaveInterface);
	$form[]=$tpl->field_text("PowerDNSClusterMasterAddress", "{master_address}", $PowerDNSClusterMasterAddress);
	$form[]=$tpl->field_numeric("PowerDNSClusterMasterPort","{remote_port}",$PowerDNSClusterMasterPort);
	
	echo $tpl->form_outside("{cluster_configuration}", $form,null,"{apply}",null,"AsDnsAdministrator",true);
	
	
}


function Save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
	
	if($_POST["PowerDNSEnableClusterMaster"]==1){
		
		if(trim($_POST["PowerDNSClusterPassword"])==null){
			echo "jserror:".$tpl->_ENGINE_parse_body("{password_cannot_be_null}");
			return;
		}


        $GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/server/build");
	}
	
	if($_POST["PowerDNSEnableClusterSlave"]==1){
		
			$PowerDNSClusterMasterAddress=$_POST["PowerDNSClusterMasterAddress"];
			$PowerDNSClusterMasterPort=$_POST["PowerDNSClusterMasterPort"];

			

			$uri="https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort/pdns-cluster/index.txt";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
			curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_URL, "$uri");
			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSLVERSION,'all');
            curl_setopt($ch, CURLOPT_SSLVERSION,'all');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$data=curl_exec($ch);
			$CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
			$header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
			$curl_errno=curl_errno($ch);
			
			$error=curl_errno($ch);
			if($error>0){
				$text=curl_error($ch);
				echo "jserror:".$tpl->_ENGINE_parse_body("{error} $error $text");
				$sock->SET_INFO("PowerDNSEnableClusterSlave", 0);
				return;
			}
			
			if($CURLINFO_HTTP_CODE>200){
				echo "jserror:".$tpl->_ENGINE_parse_body("https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort<br>{error} $CURLINFO_HTTP_CODE");
				$sock->SET_INFO("PowerDNSEnableClusterSlave", 0);
				return;
			}
			

			$ARRAY=unserialize(base64_decode($data));
			if(!isset($ARRAY["SERIAL"])){
				echo "jserror:Decrypt data failed!";
				$sock->SET_INFO("PowerDNSEnableClusterSlave", 0);
			}
			
		
	}
	
}

