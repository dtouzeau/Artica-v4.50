<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["SambaInterfaces"])){save_config();exit;}
if(isset($_POST["SambaWorkgroup"])){save_config();exit;}
if(isset($_GET["promote"])){promote();exit;}



page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
    $html=$tpl->page_header("{APP_SAMBA} {$version}","fa fa-folder","{SAMBA_MAIN_PARAMS_TEXT}",
    "$page?tabs=yes","samba","progress-samba-restart",false,"table-loader-samba-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SAMBA} {$version}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function service_status(){
    $tpl=new template_admin();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/samba/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($data->Info);

    $jsrestart=$tpl->framework_buildjs("/samba/restart",
        "samba.restart.progress","samba.restart.log","progress-samba-restart");

    $f[]=$tpl->SERVICE_STATUS($ini, "APP_SAMBA",$jsrestart);
    echo $tpl->_ENGINE_parse_body($f);
}
function status(){
	$sock=new sockets();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$NMBD_STATUS=null;

    $PromoteSamba=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PromoteSamba"));

	$sock->getFrameWork('samba.php?global-status=yes');
	$ini=new Bs_IniHandler(PROGRESS_DIR."/samba.status");
	$SambaDisableNetbios=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaDisableNetbios"));
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/samba.install.prg";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/samba.install.log";
	$ARRAY["CMD"]="samba.php?reconfigure=yes";
	$ARRAY["TITLE"]="{apply_parameters}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-samba-service','$page?tabs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-samba-restart')";
	
	if($SambaDisableNetbios==0){
		$NMBD_STATUS=$tpl->SERVICE_STATUS($ini, "SAMBA_NMBD",$jsrestart);
	}
	
	$html[]="<table style='width:100%;margin-top:20px'>
	<tr>
		<td valign='top' style='width:350px'><div id='main-samba-status'></div></td>
		<td valign='top'><div id='zPromote'>";
	
	$EnableKerbAuth=@intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	$SambaInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaInterfaces"));
	$SambaNetbiosName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaNetbiosName"));
	$SambaServerString=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaServerString"));
	
	$SambaClientNTLMv2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaClientNTLMv2"));
	$workgroup=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaWorkgroup"));
	$SambaEnableEditPosixExtension=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaEnableEditPosixExtension"));
	
	if($workgroup==null){$workgroup="WORKGROUP";}

	$form[]=$tpl->field_interfaces_choose("SambaInterfaces", "{listen_interface}", $SambaInterfaces);
	if($EnableKerbAuth==0){
		$form[]=$tpl->field_text("SambaWorkgroup", "{workgroup}", $workgroup,true);
		$form[]=$tpl->field_text("SambaNetbiosName", "{netbiosname}", $SambaNetbiosName,true,"{netbiosname_text}");
		
	}
	$form[]=$tpl->field_text("SambaServerString", "{server string}", $SambaServerString,false,"{server string_text}");

	$form[]=$tpl->field_checkbox("SambaDisableNetbios","{disable netbios}",$SambaDisableNetbios,false,"{disable netbios_text}");
	$form[]=$tpl->field_checkbox("SambaEnableEditPosixExtension","{enable_Editposix}",$SambaEnableEditPosixExtension,false,"{enable_Editposix_text}");
	
	if($EnableKerbAuth==1){
		$form[]=$tpl->field_checkbox("SambaClientNTLMv2","{client_ntlmv2_aut}",$SambaClientNTLMv2,false,"{client_ntlmv2_auth_text}");
	}

	
	$html[]=$tpl->form_outside("{general_settings}", @implode("\n", $form),null,"{apply}",$jsrestart,"AsSystemAdministrator");
	$html[]="</div></td></tr></table>";
    $html[]="<script>";
	if($PromoteSamba==0){
        $html[]="LoadAjax('zPromote','$page?promote=true');";

    }
    $html[]=$tpl->RefreshInterval_js("main-samba-status",$page,"service-status=yes");
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function promote(){
    $sock=new sockets();
    $tpl=new template_admin();
    $page=CurrentPageName();


    //<ROLE_DOMAIN_BDC>Backup Domain Controller</ROLE_DOMAIN_BDC>
    //<ROLE_DOMAIN_MEMBER>Member of an AD Domain</ROLE_DOMAIN_MEMBER>
    //<ROLE_DOMAIN_PDC>Primary Domain controler</ROLE_DOMAIN_PDC>
    //<ROLE_STANDALONE>Stand alone server</ROLE_STANDALONE>

    $workgroup=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaWorkgroup"));
    $SambaType=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaRole"));
    $hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $SambaAdmin=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdmin"));
    $SambaPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaPassword"));

    if($SambaAdmin==null){$SambaAdmin="Administrator";}
    $tb=explode(".",$hostname);
    unset($tb[0]);
    $domain=@implode(".",$tb);

    if($workgroup==null){
        if(preg_match("#^(.*?)\.#",$domain,$re)){
            $workgroup=$re[1];
        }
    }

    //$SambaType["dc"]="{domain_controler}";
    $SambaTypeZ["standalone"]="{ROLE_STANDALONE}";

    $form[]=$tpl->field_array_hash($SambaTypeZ,"SambaRole","nonull:{WINDOWS_SERVER_TYPE}",$SambaType);
    $form[]=$tpl->field_text("SambaWorkgroup", "{workgroup}", $workgroup,true);
    $form[]=$tpl->field_password2("SambaPassword","{password} (Administrator)",$SambaPassword,true);
    $html[]=$tpl->form_outside("{wizard} ($domain)", @implode("\n", $form),null,"{promote_server}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}


function save_config(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	while (list ($num, $val) = each ($_POST)){
		$sock->SET_INFO("$num", $val);
	}
	
}


function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();

	$array["{status}"]="$page?status=yes";
	//$array["{events}"]="fw.sshd.events.php";

	
	echo $tpl->tabs_default($array);	
	
}