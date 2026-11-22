<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.autofs.inc");
// Le kit de tables awc_* vit dans class.statistics_appliance.inc. L'inclusion doit
// etre INCONDITIONNELLE : placee dans le bootstrap if(!class_exists("sockets")),
// elle ne s'executerait jamais -- class.template-admin.inc a deja charge
// class.sockets.inc -- et la page mourrait sur "Call to undefined function
// awc_table_style()", c'est-a-dire un spinner qui ne se vide jamais.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["SSH_UPDATE"])){ssh_form_save();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["wizard"])){wizard_js();exit;}
if(isset($_GET["wizard1"])){wizard1();exit;}
if(isset($_GET["wizard-js2"])){wizard_js2();exit;}
if(isset($_GET["wizard2"])){wizard2();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_POST["wizard1"])){save();exit;}
if(isset($_POST["wizard2"])){save_final();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["ssh-form-js"])){ssh_form_js();exit;}
if(isset($_GET["sftp-form-js"])){sftp_form_js();exit;}
if(isset($_GET["sftp-form-popup"])){sftp_form_popup();exit;}
if(isset($_POST["ftpid"])){sftp_form_save();exit;}
if(isset($_GET["ssh-form-popup"])){ssh_form_popup();exit;}
if(isset($_GET["autofs-status"])){autofs_status();exit;}
if(isset($_GET["row-status"])){row_status();exit;}
if(isset($_GET["remove-js"])){remove_js();exit;}
if(isset($_POST["remove-service"])){remove_confirm();exit;}

page();

function remove_js():bool{
    $tpl=new template_admin();

    $remove=$tpl->framework_buildjs("/autofs/uninstall",
        "autofs.install.progress",
    "autofs.install.progress.txt","progress-autofs-restart",
        "document.location.href='/index';",null,null,"AsSystemAdministrator");

    $tpl->js_confirm_delete("{automount_center}","remove-service","yes",$remove);
    return true;
}
function remove_confirm():bool{
    return admin_tracks("Unstall AutoFS (automount) feature");
}

function enable_js():bool{
    $page=CurrentPageName();
	$mountpoint=$_GET["enable-js"];
    $md=$_GET["md"];
    $mountpointenc=urlencode($mountpoint);
	$q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
	// Pentest 2026-11-04 : enable-js (GET) etait concatene dans la requete.
	$rows=$q->QUERY_SQL_PARAMS("SELECT enabled FROM autofs WHERE mountpoint=?",array($mountpoint));
	$ligne=(is_array($rows) && isset($rows[0]))?$rows[0]:array();
	if(!$q->ok){echo "alert('$q->mysql_error')";return false;}
	if(intval($ligne["enabled"])==0){$enable=1;}else{$enable=0;}
	$q->QUERY_SQL_PARAMS("UPDATE autofs SET enabled=? WHERE mountpoint=?",array($enable,$mountpoint));
    if($enable==0) {
        admin_tracks("Disable auto-mount point $mountpoint");
    }else{
        admin_tracks("Enable auto-mount point $mountpoint");
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/autofs/reconfigure");
    echo "Loadjs('$page?row-status=$mountpointenc&md=$md');";
	if(!$q->ok){echo "alert('$q->mysql_error')";}
	return true;
}
function autofs_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/autofs/status"));

    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", json_last_error_msg()));
        return true;
    }
    if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error));
            return true;
    }
    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);

    $jsrestart=$tpl->framework_buildjs("/autofs/restart",
        "autofs.restart.progress",
        "autofs.restart.progress.txt","progress-autofs-restart","LoadAjax('table-loader-autofs','$page?main=yes');");

    echo $tpl->SERVICE_STATUS($ini, "APP_AUTOFS",$jsrestart);
    return true;
}

function ssh_form_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Pentest 2026-11-04 : ssh-form-js etait recopie sans encodage dans la page (XSS).
    $idenc=preg_replace('#[^A-Za-z0-9+/=]#','',(string)$_GET["ssh-form-js"]);
    $id=req_html(req_html(base64_decode($idenc)));
    $tpl->js_dialog2("SSH: $id","$page?ssh-form-popup=$idenc",850);
    return true;

}
function sftp_form_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Pentest 2026-11-04 : sftp-form-js etait recopie sans encodage dans la page (XSS).
    $idenc=preg_replace('#[^A-Za-z0-9+/=]#','',(string)$_GET["sftp-form-js"]);
    $id=req_html(req_html(base64_decode($idenc)));
    $tpl->js_dialog2("FTP: $id","$page?sftp-form-popup=$idenc",850);
    return true;
}
function wizard_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{add_mount_point}");
	$tpl->js_dialog($title, "$page?wizard1=yes");
    return true;
}
function wizard_js2():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{add_mount_point}:{$_SESSION["AUTOFS"]["autofs_proto"]}");
	$tpl->js_dialog($title, "$page?wizard2=yes");
	return true;
}

function delete_js():bool{
	// Pentest 2026-11-04 : delete-js et id etaient recopies sans encodage dans la page (XSS).
	$id=(string)$_GET["delete-js"];
	$md=preg_replace('#[^A-Za-z0-9_-]#','',(string)$_GET["id"]);
	$tpl=new template_admin();
	$final="$('#$md').remove();";
	$tpl->js_confirm_delete(req_html($id), "delete", $id,$final);
    return true;
}

function delete():bool{
	$id=$_POST["delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
	// Pentest 2026-11-04 : delete (POST) etait concatene dans la requete.
	$q->QUERY_SQL_PARAMS("DELETE FROM autofs WHERE mountpoint=?",array($id));
	if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Removing Auto-mount connection $id");
	$q->QUERY_SQL_PARAMS("DELETE FROM automount_davfs WHERE local_dir=?",array($id));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/autofs/reconfigure");
    return true;

}

function wizard1():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

	$DAVFS_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DAVFS_INSTALLED"));
    $CURLFTPFS_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CURLFTPFS_INSTALLED"));
    $CIFS_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CIFS_INSTALLED"));
    $SSHFS_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHFS_INSTALLED"));

	if($CURLFTPFS_INSTALLED){$protos["FTP"]="{ftp_directory}";}
	if($CIFS_INSTALLED){$protos["CIFS"]="{windows_network_share}";}
	if($DAVFS_INSTALLED==1){$protos["DAVFS"]="{TAB_WEBDAV}";}
    if($SSHFS_INSTALLED==1){$protos["SSH"]="OpenSSH";}

	$protos["NFSV3"]="NFS v3";
	$protos["NFSV4"]="NFS v4";
//	$protos["USB"]="{external_device}";
	$protos[null]="{select}";
	
	$form[]=$tpl->field_hidden("wizard1", "1");
	$form[]=$tpl->field_array_hash($protos, "autofs_proto", "{proto}", $_SESSION["AUTOFS"]["autofs_proto"],true);
	
	$security="AsSystemAdministrator";
	$jsafter="BootstrapDialog1.close();Loadjs('$page?wizard-js2=yes');";
	$html[]=$tpl->form_outside("{add_mount_point}", @implode("\n", $form),"{autofs_wizard_1}","{next}",$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}
function wizard2(){

	switch ($_SESSION["AUTOFS"]["autofs_proto"]) {
		case 'FTP':form_add_details_FTP();break;
		case 'CIFS':form_add_details_CIFS();break;
		case 'NFSV3':form_add_details_NFS();break;
		case 'NFSV4':form_add_details_NFS();break;
		case 'DAVFS':form_add_details_DAVFS();break;
        case 'SSH':form_add_details_SSH();break;
		case 'Start':StartApplyConfig();break;
		default:
			break;
	}
	
	}

function form_add_details_FTP(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$form[]=$tpl->field_hidden("wizard2", "1");
	$form[]=$tpl->field_info("autofs_proto", "{proto}", $_SESSION["AUTOFS"]["autofs_proto"]);
	$form[]=$tpl->field_text("FTP_SERVER", "{remote_server_name}", $_SESSION["AUTOFS"]["FTP_SERVER"]);
	$form[]=$tpl->field_text("FTP_USER", "{ftp_user}", $_SESSION["AUTOFS"]["FTP_USER"]);
	$form[]=$tpl->field_password("FTP_PASSWORD", "{password}", $_SESSION["AUTOFS"]["FTP_PASSWORD"]);
	$form[]=$tpl->field_text("FTP_LOCAL_DIR", "{local_directory_name}", $_SESSION["AUTOFS"]["FTP_LOCAL_DIR"],true);
	
	$security="AsSystemAdministrator";
	$jsafter="LoadAjax('table-loader-autofs','$page?main=yes');BootstrapDialog1.close();";
	$html[]=$tpl->form_outside("{add_mount_point}", @implode("\n", $form),"{autofs_wizard_2}","{save}",$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function form_add_details_CIFS(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$form[]=$tpl->field_hidden("wizard2", "1");
	$form[]=$tpl->field_info("autofs_proto", "{proto}", $_SESSION["AUTOFS"]["autofs_proto"]);
	$form[]=$tpl->field_text("CIFS_SERVER", "{remote_server_name}", $_SESSION["AUTOFS"]["CIFS_SERVER"]);
	$form[]=$tpl->field_text("CIFS_USER", "{username}", $_SESSION["AUTOFS"]["CIFS_USER"]);
	$form[]=$tpl->field_text("CIFS_DOMAIN", "{domain}", $_SESSION["AUTOFS"]["CIFS_DOMAIN"]);
	$form[]=$tpl->field_password("CIFS_PASSWORD", "{password}", $_SESSION["AUTOFS"]["CIFS_PASSWORD"]);
	$form[]=$tpl->field_text("CIFS_FOLDER", "{target_directory}", $_SESSION["AUTOFS"]["CIFS_FOLDER"]);
	$form[]=$tpl->field_text("CIFS_LOCAL_DIR", "{local_directory_name}", $_SESSION["AUTOFS"]["CIFS_LOCAL_DIR"],true);
	
	$security="AsSystemAdministrator";
	$jsafter="LoadAjax('table-loader-autofs','$page?main=yes');BootstrapDialog1.close();";
	$html[]=$tpl->form_outside("{add_mount_point}", @implode("\n", $form),"{autofs_wizard_2}","{save}",$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}	
function form_add_details_NFS(){
	$dn=$_GET["dn"];
	$page=CurrentPageName();
	$tpl=new templates();

	switch ($_SESSION["AUTOFS"]["autofs_proto"]) {
		case 'NFSV3':$type="nfs3";break;
		case 'NFSV4':$type="nfs4";break;
		default:
			break;
	}
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$form[]=$tpl->field_hidden("wizard2", "1");
	
	$form[]=$tpl->field_hidden("NFS_PROTO",$type);
	$form[]=$tpl->field_info("autofs_proto", "{proto}", $_SESSION["AUTOFS"]["autofs_proto"]);
	$form[]=$tpl->field_text("NFS_SERVER", "{remote_server_name}", $_SESSION["AUTOFS"]["NFS_SERVER"]);
	$form[]=$tpl->field_text("NFS_FOLDER", "{target_directory}", $_SESSION["AUTOFS"]["NFS_FOLDER"]);
	$form[]=$tpl->field_text("NFS_LOCAL_DIR", "{local_directory_name}", $_SESSION["AUTOFS"]["NFS_LOCAL_DIR"],true);
	
	$security="AsSystemAdministrator";
	$jsafter="LoadAjax('table-loader-autofs','$page?main=yes');BootstrapDialog1.close();";
	$html[]=$tpl->form_outside("{add_mount_point}", @implode("\n", $form),"{autofs_wizard_2}","{save}",$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}
function form_add_details_DAVFS(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$form[]=$tpl->field_hidden("wizard2", "1");
	$form[]=$tpl->field_info("autofs_proto", "{proto}", $_SESSION["AUTOFS"]["autofs_proto"]);
	$form[]=$tpl->field_text("HTTP_SERVER", "{url}", $_SESSION["AUTOFS"]["HTTP_SERVER"]);
	$form[]=$tpl->field_text("HTTP_USER", "{web_user}", $_SESSION["AUTOFS"]["HTTP_USER"]);
	$form[]=$tpl->field_password("HTTP_PASSWORD", "{password}", $_SESSION["AUTOFS"]["HTTP_PASSWORD"]);
	$form[]=$tpl->field_text("HTTP_LOCAL_DIR", "{local_directory_name}", $_SESSION["AUTOFS"]["HTTP_LOCAL_DIR"],true);
	
	$security="AsSystemAdministrator";
	$jsafter="LoadAjax('table-loader-autofs','$page?main=yes');BootstrapDialog1.close();";
	$html[]=$tpl->form_outside("{add_mount_point}", @implode("\n", $form),"{autofs_wizard_2}","{save}",$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function sftp_form_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
    $autofs=new autofs();
    // Pentest 2026-11-04 : sftp-form-popup etait recopie sans encodage dans la page (XSS).
    $idenc=preg_replace('#[^A-Za-z0-9+/=]#','',(string)$_GET["sftp-form-popup"]);
    $id=base64_decode($idenc);
    // Pentest 2026-11-04 : le point de montage (GET, base64) etait concatene dans la requete.
    $rows=$q->QUERY_SQL_PARAMS("SELECT automountInformation FROM autofs WHERE mountpoint=?",array($id));
    $ligne=(is_array($rows) && isset($rows[0]))?$rows[0]:array();
    $automountInformation=base64_decode($ligne["automountInformation"]);
    $ARRAY=$autofs->parseCommand($automountInformation);
    $form[]=$tpl->field_hidden("ftpid",$idenc);
    $form[]=$tpl->field_text("HOSTNAME", "{remote_server_name}", $ARRAY["HOSTNAME"]);
    $form[]=$tpl->field_checkbox("FTPSSL", "FTP SSL (990)", $ARRAY["FTPSSL"]);
    $form[]=$tpl->field_checkbox("FTPTLS", "FTP SSL (21)", $ARRAY["FTPTLS"]);
    $form[]=$tpl->field_text("USERNAME", "{ftp_user}", $ARRAY["USERNAME"]);
    $form[]=$tpl->field_password("PASSWORD", "{password}", $ARRAY["PASSWORD"]);
    $form[]=$tpl->field_text("DIRECTORY", "{remote_directory}", $ARRAY["DIRECTORY"]);
    $form[]=$tpl->field_checkbox("USEPROXY","{UseProxyServer}",$ARRAY["USEPROXY"],
    "PROXYTYPE,PROXYSERVER,PROXYPORT,PROXYUSERNAME,PROXYPASSWORD");

    $ARRAYPP["proxytunnel"]="TUNNEL";
    $ARRAYPP["socks4"]="SOCKS4";
    $ARRAYPP["socks5"]="SOCKS5";
    $ARRAYPP["httpproxy"]="HTTP PROXY";
    if(!isset($ARRAY["PROXYTYPE"])){$ARRAY["PROXYTYPE"]="httpproxy";}
    $form[]=$tpl->field_array_hash($ARRAYPP,"PROXYTYPE","{type}",$ARRAY["PROXYTYPE"]);
    $form[]=$tpl->field_text("PROXYSERVER", "{proxy_server}", $ARRAY["PROXYSERVER"]);
    $form[]=$tpl->field_numeric("PROXYPORT", "{remote_port}", $ARRAY["PROXYPORT"]);
    $form[]=$tpl->field_text("PROXYUSERNAME", "{proxy_username}", $ARRAY["PROXYUSERNAME"]);
    $form[]=$tpl->field_password("PROXYPASSWORD", "{password}", $ARRAY["PROXYPASSWORD"]);
    $security="AsSystemAdministrator";
    $jsafter="LoadAjax('table-loader-autofs','$page?main=yes');dialogInstance2.close();";
    $html[]=$tpl->form_outside("{ftp_directory} $id",$form,null,"{save}",$jsafter,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function sftp_form_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=base64_decode($_POST["ftpid"]);
    unset($_POST["ftpid"]);
    admin_tracks("Saving Automount FTP session {$_POST["PROXYSERVER"]}");
    $encrypt=base64_encode("sftp:".base64_encode(serialize($_POST)));
    $q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
    // Pentest 2026-11-04 : ftpid (POST) etait concatene dans la requete.
    $q->QUERY_SQL_PARAMS("UPDATE autofs SET automountInformation=? WHERE mountpoint=?",array($encrypt,$id));
    if(!$q->ok){
        $tpl->post_error($q->mysql_error);
        return false;
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/autofs/reconfigure");
    return true;
}

function ssh_form_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
    $autofs=new autofs();
    // Pentest 2026-11-04 : ssh-form-popup etait recopie sans encodage dans la page (XSS).
    $idenc=preg_replace('#[^A-Za-z0-9+/=]#','',(string)$_GET["ssh-form-popup"]);
    $id=base64_decode($idenc);
    // Pentest 2026-11-04 : le point de montage (GET, base64) etait concatene dans la requete.
    $rows=$q->QUERY_SQL_PARAMS("SELECT automountInformation FROM autofs WHERE mountpoint=?",array($id));
    $ligne=(is_array($rows) && isset($rows[0]))?$rows[0]:array();
    $automountInformation=base64_decode($ligne["automountInformation"]);
    $ARRAY=$autofs->parseCommand($automountInformation);
    $form[]=$tpl->field_hidden("SSH_UPDATE",$id);
    $form[]=$tpl->field_text("SSH_SERVER","{remote_server}", $ARRAY["SSH_SERVER"]);
    $form[]=$tpl->field_text("SSH_PORT","{remote_port}", $ARRAY["SSH_PORT"]);
    $form[]=$tpl->field_text("SSH_USER","{username}", $ARRAY["SSH_USER"]);
    $form[]=$tpl->field_text("SSH_REMOTE_PATH","{target_directory}", $ARRAY["SSH_REMOTE_PATH"]);
    if($ARRAY["AUTHKEY"]<>null) {
        $ARRAY["AUTHKEY"]=$ARRAY["AUTHKEY"]." ".php_uname('n');
        $form[] = $tpl->field_textarea("AUTHKEY", "{authorizationkey}", $ARRAY["AUTHKEY"]);
    }
    $form[]=$tpl->field_textarea("PRIVKEY","{smtpd_tls_key_file}",$ARRAY["PRIVKEY"]);

    $security="AsSystemAdministrator";
    $jsafter="LoadAjax('table-loader-autofs','$page?main=yes');dialogInstance2.close();";
    $html[]=$tpl->form_outside("{remote_ssh_service} $id",$form,null,"{save}",$jsafter,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function ssh_form_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=$_POST["SSH_UPDATE"];
    unset($_POST["ssh-rsa"]);
    $q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
    $autofs=new autofs();
    
    // Pentest 2026-11-04 : SSH_UPDATE (POST) etait concatene dans la requete.
    $rows=$q->QUERY_SQL_PARAMS("SELECT automountInformation FROM autofs WHERE mountpoint=?",array($id));
    $ligne=(is_array($rows) && isset($rows[0]))?$rows[0]:array();
    $automountInformation=base64_decode($ligne["automountInformation"]);
    $ARRAY=$autofs->parseCommand($automountInformation);

    $PRIVKEY=trim($_POST["PRIVKEY"] ?? "");
    $OLD_PRIVKEY=trim($ARRAY["PRIVKEY"] ?? "");
    if(!isset($_POST["AUTHKEY"])){
        $_POST["AUTHKEY"]=null;
    }

    // Derive the public key when we have none, or when the private key was
    // edited. These were two separate blocks, each writing the private key to
    // PROGRESS_DIR/<md5-of-the-key>.key so framework/sshd.php?ssh-kegen-key
    // could run `ssh-keygen -y` and leave the answer in a sibling file. The key
    // now travels in the request body and never touches the disk.
    if($PRIVKEY<>null){
        if($PRIVKEY<>$OLD_PRIVKEY){
            writelogs("Private key changed, deriving a new public key",__FUNCTION__,__FILE__,__LINE__);
        }
        if($_POST["AUTHKEY"]==null || $PRIVKEY<>$OLD_PRIVKEY){
            $pubkey=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/ssh/keypair/publickey",array("PrivateKey"=>$PRIVKEY)));
            if(!is_object($pubkey) || !isset($pubkey->Status) || !$pubkey->Status){
                $keyError="Unable to read this private key.";
                if(is_object($pubkey) && isset($pubkey->Error) && strlen($pubkey->Error)>0){
                    $keyError="$keyError\n{$pubkey->Error}";
                }
                echo $keyError;
                return false;
            }
            $_POST["AUTHKEY"]=$pubkey->AuthorizedKey;
        }
    }

    foreach ($_POST as $key=>$val){$ARRAY[$key]=$val;}
    $pattern=":sshfs:".base64_encode(serialize($_POST));
    $pattern=base64_encode($pattern);
    $q->QUERY_SQL_PARAMS("UPDATE autofs SET automountInformation=? WHERE mountpoint=?",array($pattern,$id));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/autofs/reconfigure");
    return true;
}


function form_add_details_SSH():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(!isset($_SESSION["AUTOFS"]["SSH_PORT"])){
        $_SESSION["AUTOFS"]["SSH_PORT"]=22;
    }
    if(!isset($_SESSION["AUTOFS"]["SSH_SERVER"])){
        $_SESSION["AUTOFS"]["SSH_SERVER"]="1.2.3.4";
    }
    if(!isset($_SESSION["AUTOFS"]["autofs_proto"])){
        $_SESSION["AUTOFS"]["autofs_proto"]="SSH";
    }
    if(!isset($_SESSION["AUTOFS"]["SSH_LOCAL_DIR"])){
        $_SESSION["AUTOFS"]["SSH_LOCAL_DIR"]="ssh_".time();
    }
    if(!isset($_SESSION["AUTOFS"]["SSH_LOCAL_DIR"])){
        $_SESSION["AUTOFS"]["SSH_LOCAL_DIR"]="ssh_".time();
    }
    if(!isset($_SESSION["AUTOFS"]["SSH_USER"])){
        $_SESSION["AUTOFS"]["SSH_USER"]="root";
    }
    $form[]=$tpl->field_hidden("wizard2", "1");
    $form[]=$tpl->field_info("autofs_proto", "{proto}", $_SESSION["AUTOFS"]["autofs_proto"]);
    $form[]=$tpl->field_text("SSH_SERVER","{remote_server}", $_SESSION["AUTOFS"]["SSH_SERVER"]);
    $form[]=$tpl->field_text("SSH_PORT","{remote_port}", $_SESSION["AUTOFS"]["SSH_PORT"]);
    $form[]=$tpl->field_text("SSH_USER","{username}",$_SESSION["AUTOFS"]["SSH_USER"]);


    $form[]=$tpl->field_text("SSH_REMOTE_PATH","{target_directory}", $_SESSION["AUTOFS"]["SSH_REMOTE_PATH"]);
    $form[]=$tpl->field_text("SSH_LOCAL_DIR", "{local_directory_name}", $_SESSION["AUTOFS"]["SSH_LOCAL_DIR"],true);

    $security="AsSystemAdministrator";
    $jsafter="LoadAjax('table-loader-autofs','$page?main=yes');BootstrapDialog1.close();";
    $html[]=$tpl->form_outside("{add_mount_point} {remote_ssh_service}", @implode("\n", $form),"{autofs_wizard_2}","{save}",$jsafter,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function save():bool{
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
	foreach ($_POST as $key=>$value){
		$_SESSION["AUTOFS"][$key]=$value;
	}
    return true;
}

function save_final():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
    $pattern=null;
    $local_dir=null;
	foreach ($_POST as $key=>$value){
		$_SESSION[$key]=$value;
	}
    if(isset($_POST["SSH_SERVER"])){
        $local_dir=addslashes($_POST["SSH_LOCAL_DIR"]);
        if($local_dir==null){$local_dir="ssh_".time();}
        // Key material comes back inside the response. The old path called
        // framework/sshd.php?build-key-pair=<time>, which wrote a serialized
        // file under PROGRESS_DIR for this line to read back — and never
        // reported failure, so a missing ssh-keypair-gen binary silently saved
        // a mount with empty keys.
        $keys=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/keypair/build"));
        if(!is_object($keys)){
            echo "Unable to generate the SSH key pair: no answer from the service.";
            return false;
        }
        if(!isset($keys->Status) || !$keys->Status){
            $sshError="Unable to generate the SSH key pair.";
            if(isset($keys->Error) && strlen($keys->Error)>0){$sshError="$sshError\n{$keys->Error}";}
            echo $sshError;
            return false;
        }
        // Same 50-byte floor the autofs configurator applies before writing the
        // sshfs identity file — refuse here rather than fail at mount time.
        if(!isset($keys->PrivateKey) || strlen($keys->PrivateKey)<50){
            echo "Unable to generate the SSH key pair: the returned private key is empty or truncated.";
            return false;
        }
        $_POST["PRIVKEY"]=$keys->PrivateKey;
        $_POST["PUBLICKEY"]=$keys->PublicKey;
        $_POST["AUTHKEY"]=$keys->AuthorizedKey;
        $pattern=":sshfs:".base64_encode(serialize($_POST));

    }


	if(isset($_POST["FTP_LOCAL_DIR"])){
		$_POST["FTP_LOCAL_DIR"]=strtolower($tpl->StripSpecialsChars($_POST["FTP_LOCAL_DIR"]));
        if($_POST["FTP_LOCAL_DIR"]==null){$_POST["FTP_LOCAL_DIR"]="ftp_".time();}
        $ARRAY["HOSTNAME"]=$_POST["FTP_SERVER"];
        $ARRAY["USERNAME"]=$_POST["FTP_USER"];
        $ARRAY["PASSWORD"]=$_POST["FTP_PASSWORD"];
        admin_tracks("Saving Automount FTP session {$_POST["FTP_SERVER"]}");
        $pattern=base64_encode("sftp:".base64_encode(serialize($ARRAY)));
		$local_dir=addslashes($_POST["FTP_LOCAL_DIR"]);
	}
	
	if(isset($_POST["CIFS_LOCAL_DIR"])){
        $domain=null;
        $auth=null;
		$_POST["CIFS_PASSWORD"]=autofs_escape_chars($_POST["CIFS_PASSWORD"]);
		$_POST["CIFS_FOLDER"]=autofs_escape_chars($_POST["CIFS_FOLDER"]);
		$_POST["CIFS_LOCAL_DIR"]=strtolower($tpl->StripSpecialsChars($_POST["CIFS_LOCAL_DIR"]));
		if($_POST["CIFS_DOMAIN"]<>null){$domain="domain={$_POST["CIFS_DOMAIN"]},";}
		if($_POST["CIFS_USER"]<>null){$auth="user={$_POST["CIFS_USER"]},{$domain}pass={$_POST["CIFS_PASSWORD"]}";}
		
		$pattern="-fstype=cifs,rw,noperm,vers=2.0,$auth ://{$_POST["CIFS_SERVER"]}/{$_POST["CIFS_FOLDER"]}";
		$local_dir=addslashes($_POST["CIFS_LOCAL_DIR"]);
	}
	if(isset($_POST["NFS_LOCAL_DIR"])){
		$_POST["NFS_LOCAL_DIR"]=strtolower($tpl->StripSpecialsChars($_POST["NFS_LOCAL_DIR"]));
		$pattern="-fstype={$_POST["NFS_PROTO"]},rw,soft,intr,rsize=8192,wsize=8192\t{$_POST["NFS_SERVER"]}/{$_POST["NFS_FOLDER"]}/&";
		$local_dir=addslashes($_POST["NFS_LOCAL_DIR"]);
	}
	if(isset($_POST["HTTP_LOCAL_DIR"])){
		$uri=$_POST["HTTP_SERVER"];
		if(!preg_match("#^http.*?:\/\/#",$uri,$re)){
			echo req_html($_POST["HTTP_SERVER"]).": Bad format\nuse https://... or http://...";
			return false;
		}
		$_POST["HTTP_LOCAL_DIR"]=strtolower($tpl->StripSpecialsChars($_POST["HTTP_LOCAL_DIR"]));

		$_POST["HTTP_SERVER"]=str_replace(":","\:",$_POST["HTTP_SERVER"]);
		if(substr($_POST["HTTP_SERVER"],strlen($_POST["HTTP_SERVER"])-1,1)<>"/"){$_POST["HTTP_SERVER"]=$_POST["HTTP_SERVER"]."/";}
		if($_POST["HTTP_LOCAL_DIR"]==null){$_POST["HTTP_LOCAL_DIR"]=time();}
		$pattern="-fstype=davfs,rw,nosuid,nodev,user :{$_POST["HTTP_SERVER"]}";
		$password=addslashes($_POST["HTTP_PASSWORD"]);
		$user=addslashes($_POST["HTTP_USER"]);
		$local_dir=addslashes($_POST["HTTP_LOCAL_DIR"]);
		// Pentest 2026-11-04 : addslashes() n'echappe rien en SQLite ; user/password/uri/local_dir
		// (POST) etaient concatenes dans la requete.
		$q->QUERY_SQL_PARAMS("DELETE FROM automount_davfs WHERE local_dir=?",array($local_dir));
		$q->QUERY_SQL_PARAMS("INSERT INTO automount_davfs (user,password,uri,local_dir) VALUES (?,?,?,?)",array($user,$password,$uri,$local_dir));
		if(!$q->ok){echo $q->mysql_error;return false;}
	}
	$pattern=base64_encode($pattern);

    $sql="CREATE TABLE IF NOT EXISTS `autofs` (`mountpoint` TEXT PRIMARY KEY,`enabled` INTEGER NOT NULL DEFAULT 1,`automountInformation` TEXT NOT NULL)";
    $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."<br>$sql";return false;}

	// Pentest 2026-11-04 : le repertoire local (POST) etait concatene dans la requete.
	$q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO autofs (mountpoint,enabled,automountInformation) VALUES (?,1,?)",array($local_dir,$pattern));
	if(!$q->ok){echo $q->mysql_error;}
    admin_tracks_post("Saving Auto-mount connection:");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/autofs/reconfigure");
	return true;
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{automount_center}",
        "fas fa-conveyor-belt-alt","{autofs_about}","$page?main=yes","automount"
        ,"progress-autofs-restart",false,"table-loader-autofs");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Automount Center",$html);
        echo $tpl->build_firewall();
        return;
    }


	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/autofs/listdirs"));
    $LOCALDIRS=unserialize($json->Info);


    $jsrestart=$tpl->framework_buildjs("/autofs/restart",
        "autofs.restart.progress",
    "autofs.restart.progress.txt","progress-autofs-restart","LoadAjax('table-loader-autofs','$page?main=yes');");



	$delete=$tpl->javascript_parse_text("{delete}");
	$local_directory_name=$tpl->_ENGINE_parse_body("{local_directory_name}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$source=$tpl->_ENGINE_parse_body("{source}");

	$uri=$tpl->_ENGINE_parse_body("{url}");
	$enable=$tpl->_ENGINE_parse_body("{enabled}");

    $topbuttons[]=array("Loadjs('$page?remove-js=yes');",ico_trash,"{disable_feature}");
    $topbuttons[]=array("s_PopUpFull('https://wiki.articatech.com/system/automount',1024,768,'Auto-mount help');",ico_support,"{help}");
    $topbuttons[]=array("Loadjs('$page?wizard=yes');",ico_plus,"{add_mount_point}");
    $topbuttons[]=array("LoadAjax('table-loader-autofs','$page?main=yes');",ico_refresh,"{refresh}");
    $topbuttons[]=array($jsrestart,ico_save,"{reconfigure_service}");
    $btn=$tpl->table_buttons($topbuttons);


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    // La tuile rendue par widget_vert/widget_grey fait 335px : une cellule de 200px
    // la laissait deborder sur le tableau et rogner sa premiere colonne.
    $html[]="<td style='vertical-align:top;width:350px' nowrap>";
    $html[]="<div id='autofs-status' style='width:335px'></div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:76%'>";
	$html[]=awc_table_style();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'>";
	$html[]="<thead><tr>";
	$html[]="<th>{$local_directory_name}</th>";
	$html[]="<th>$proto</th>";
	$html[]="<th>$source</th>";
	$html[]="<th>$uri</th>";
	$html[]="<th class='center'>$enable</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead>";
	// L'id du <tbody> est le point d'ancrage de awc_pager_js() : sans lui le pager
	// rend vide et toutes les lignes s'affichent d'un bloc, en silence.
	$html[]="<tbody id='autofs-rows'>";


    $q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
	$sql="SELECT *  FROM autofs ORDER BY mountpoint";
	$results = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }
	
	$autofs=new autofs(null,true);
	foreach ($results as $index=>$ligne){
		$color_href=null;
		$md=md5(serialize($ligne));
		$id=$ligne["mountpoint"];
        $idenc=urlencode($id);
		$automountInformation=base64_decode($ligne["automountInformation"]);
        $configured=1;

        $main_status="<span class='awc-badge awc-badge-off'>{inactive}</span>";


		$ARRAY=$autofs->parseCommand($automountInformation);
		$ARRAY["SRC"]=$tpl->_ENGINE_parse_body($ARRAY["SRC"]);
        $status = "<i class='text-danger fa-solid fa-folder'></i>";

		$text_recursive=null;
		if($ligne["enabled"]==0){
            VERBOSE("$local_directory_name configured=0 (disabled)",__LINE__);
            $configured=0;
            $status="<i class='fa-solid fa-folder'></i>";
            $color_href="style='color:#A0A0A0'";
        }
        $local_directory_name="/automounts/$id";
        $local_directory_nameurl=urlencode($local_directory_name);
        $local_directory_nameenc=base64_encode($local_directory_name);
        $local_directory_uri=$tpl->td_href($local_directory_name,"{browse}","Loadjs('fw.browse-directory2.php?field-id=0&title=$local_directory_nameurl&basepath=$local_directory_nameenc')");

        if(!isset($LOCALDIRS[$id])){
            VERBOSE("$id configured=0 (LOCALDIRS)",__LINE__);
            $configured=0;}

        if($configured==0) {
            $local_directory_uri = $local_directory_name;
        }

        VERBOSE("$local_directory_name configured=$configured",__LINE__);
        if($configured==1) {
            $OPEN=CheckDir($local_directory_nameurl);
            if ($OPEN == "") {
                $configured=0;
            }else{
                $status="<i class='fa-solid fa-folder' style='color:rgb(24, 166, 137)'></i>";
            }
        }
        $ttoltip_error=null;

        if($ARRAY["FS"]=="ssh"){
            if(strlen($ARRAY["PRIVKEY"])<50){
                $ttoltip_error="&nbsp;<span class='label label-danger'>{error} Key-pair</span>";
            }

            $idenc2=base64_encode($id);
            $ARRAY["SRC"]=$tpl->td_href($ARRAY["SRC"],null,"Loadjs('$page?ssh-form-js=$idenc2')");

        }

        if($ARRAY["FS"]=="ftp"){
            $idenc2=base64_encode($id);
            $ARRAY["SRC"]=$tpl->td_href($ARRAY["SRC"],null,"Loadjs('$page?sftp-form-js=$idenc2')");
        }


        if($configured==1){
            $main_status="<span class='awc-badge awc-badge-on'>{active2}</span>";
        }

		// Les quatre identifiants ci-dessous sont pilotes en direct par row_status() :
		// le <tr> lui-meme, folder-$md, dir-$md et main-status-$md. Les perdre
		// couperait le rafraichissement de ligne sans aucune erreur visible.
		$off=($configured==1)?"":" awc-ico-off";
		$offn=($configured==1)?"":" awc-name-off";
		$h ="<tr id='$md'>";
		$h.="<td><div class='awc-ident'>";
		$h.="<span class='awc-ico$off' id='folder-$md'>$status</span>";
		$h.="<span><span class='awc-name$offn' id='dir-$md'>$local_directory_uri</span>";
		$h.="<div class='awc-meta'>".htmlspecialchars((string)$id)."</div>";
		$h.="</span></div></td>";
		$h.="<td class='awc-mono'>".htmlspecialchars((string)$ARRAY["FS"])."</td>";
		$h.="<td>{$ARRAY["SRC"]}$ttoltip_error</td>";
		$h.="<td class='awc-mono'>{$ARRAY["BROWSER_URI"]}</td>";
		$h.="<td class='center'><span id='main-status-$md'>$main_status</span></td>";
		$h.="<td><div class='awc-acts'>";
		$h.=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js={$ligne["mountpoint"]}&md=$md')",null,"AsDnsAdministrator");
		$h.=$tpl->icon_delete("Loadjs('$page?delete-js=$idenc&id=$md')","AsDnsAdministrator");
		$h.="</div></td>";
		$h.="</tr>";
		$html[]=$h;
	}

	if(count($results)==0){
		$html[]="<tr><td colspan='6'><div class='awc-empty'>{no_mount_points}</div></td></tr>";
	}
	$html[]="</tbody>";
	$html[]="</table></div>";
	$html[]="<div class='awc-foot'><span>".count($results)." {mount_points}</span>";
	$html[]="<span class='awc-pg' id='autofs-pager'></span></div>";
	$html[]="</div></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $TINY_ARRAY["TITLE"]="{automount_center}";
    $TINY_ARRAY["ICO"]="fas fa-conveyor-belt-alt";
    $TINY_ARRAY["EXPL"]="{autofs_about}";
    $TINY_ARRAY["URL"]="automount";
    $TINY_ARRAY["BUTTONS"]=$btn;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $jsRefresh=$tpl->RefreshInterval_js("autofs-status",$page,"autofs-status=yes");
	$html[]="
	<script>
        NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
        $jstiny
        $jsRefresh
    </script>";

    // Le pager se concatene APRES l'analyse : le corps de sa fonction serait lu
    // comme un {token} par le regex #{(.+?)}# de _ENGINE_parse_body.
    echo $tpl->_ENGINE_parse_body($html).awc_pager_js(25,"autofs-rows","autofs-pager");
    return true;
}
function CheckDir($DirPathEnc):string{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/autofs/checkdir/$DirPathEnc"));
    if(!$json->Status){
        VERBOSE("$DirPathEnc $json->Error",__LINE__);
        return "";
    }
    if(!property_exists($json,"dirStatus")){
        VERBOSE("$DirPathEnc No property",__LINE__);
        return "";
    }
    if (!$json->dirStatus){
        VERBOSE("$DirPathEnc dirStatus->Error",__LINE__);
        return "";
    }
    return "TRUE";
}

function row_status():bool{
    $md=$_GET["md"];
    $mountpoint=$_GET["row-status"];
    $tpl=new template_admin();
    $autofs=new autofs();
    $color_href=null;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/autofs/listdirs"));
    $LOCALDIRS=unserialize($json->Info);


    $local_directory_name="/automounts/$mountpoint";
    $local_directory_nameurl=urlencode("$local_directory_name");
    $local_directory_nameenc=base64_encode($local_directory_name);
    // Pentest 2026-11-04 : row-status etait recopie sans encodage dans la page (XSS).
    $local_directory_nameH=req_html($local_directory_name);
    $local_directory_uri=$tpl->td_href($local_directory_nameH,"{browse}","Loadjs('fw.browse-directory2.php?field-id=0&title=$local_directory_nameurl&basepath=$local_directory_nameenc')");

    $q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
    // Pentest 2026-11-04 : row-status (GET) etait concatene dans la requete.
    $rows=$q->QUERY_SQL_PARAMS("SELECT * FROM autofs WHERE mountpoint=?",array($mountpoint));
    $ligne=(is_array($rows) && isset($rows[0]))?$rows[0]:array();

    $automountInformation=base64_decode($ligne["automountInformation"]);
    $configured=1;

    $status = "<i class='text-danger fa-solid fa-folder'></i>";
    // Meme balisage que la table : un label Bootstrap ici ferait retomber la ligne
    // rafraichie sur l'ancien style, une fois sur deux selon qu'elle a ete redessinee.
    $main_status="<span class='awc-badge awc-badge-off'>{inactive}</span>";


    $ARRAY=$autofs->parseCommand($automountInformation);
    $ARRAY["SRC"]=$tpl->_ENGINE_parse_body($ARRAY["SRC"]);

    if(!isset($LOCALDIRS[$mountpoint])) {$configured=0;}

    if($configured==1) {
        $main_status = "<span class='awc-badge awc-badge-on'>{active2}</span>";
        $OPEN = CheckDir($local_directory_uri);
        if ($OPEN == "") {
            $local_directory_uri=$local_directory_nameH;
        }else{
            $status="<i class='fa-solid fa-folder' style='color:rgb(24, 166, 137)'></i>";
        }

    }

    if($ligne["enabled"]==0){
        VERBOSE("$local_directory_name configured=0 (disabled)",__LINE__);
        $status="<i class='fa-solid fa-folder'></i>";
        $local_directory_uri=$local_directory_nameH;
        $color_href="#A0A0A0";
    }

    $idstatus="main-status-$md";
    $iddir="dir-$md";
    $idfodler="folder-$md";

    $local_directory_uri_base=base64_encode($local_directory_uri);
    $idstatus_base=base64_encode($tpl->_ENGINE_parse_body($main_status));
    $status_base=base64_encode($status);
    header("content-type: application/x-javascript");
    $f[]="document.getElementById('$iddir').innerHTML=base64_decode('$local_directory_uri_base')";
    $f[]="document.getElementById('$idstatus').innerHTML=base64_decode('$idstatus_base')";
    $f[]="document.getElementById('$idfodler').innerHTML=base64_decode('$status_base')";
    $f[]="document.getElementById('$md').style.color='$color_href';";
    echo @implode("\n",$f);
    return true;
}