<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
    $GLOBALS["DEBUG_PRIVS"]=true;
}
if(isset($_GET["form-change-pass-sqlite-popup"])){change_password_slite_popup();exit;}
if(isset($_GET["form-change-pass-sqlite"])){change_password_slite_js();exit;}
if(isset($_POST["formchangepasssqlite"])){change_password_slite_perform();exit;}
if(isset($_POST["change_admin"])){change_manager_perform();exit;}
if(isset($_GET["apilink-js"])){API_LINK_JS();exit;}
if(isset($_GET["apilink-popup"])){API_LINK_POPUP();exit;}
if(isset($_POST["apilink"])){API_LINK_SAVE();exit;}
if(isset($_GET["table"])){privileges();exit;}
if(isset($_POST["userfont"])){lang();exit;}
if(isset($_POST["lang2"])){change_language_perform();exit;}
if(isset($_GET["change-language-js"])){change_language_js();exit;}
if(isset($_GET["change-language-popup"])){change_language_form();exit;}
if(isset($_GET["after-language"])){change_language_after();exit;}
if(isset($_GET["change-manager-popup"])){change_manager_popup();exit;}
if(isset($_GET["change-manager-js"])){change_manager_js();exit;}
if(isset($_GET["client-certificate"])){client_certificate();exit;}
if(isset($_GET["pfx-manager"])){client_certificate_download();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
page();


function API_LINK_JS():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog1("Auth Link", "$page?apilink-popup=yes");
}
function change_manager_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{SuperAdmin}", "$page?change-manager-popup=yes");
}
function change_language_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{language}", "$page?change-language-popup=yes");
}
function change_password_slite_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{password}", "$page?form-change-pass-sqlite-popup=yes",550);
}
function change_password_slite_popup():bool{
    $tpl=new template_admin();

    $form[]=$tpl->field_password2("formchangepasssqlite", "{password}", null,true);
    $html[]=$tpl->form_outside("", $form,null,"{apply}","dialogInstance1.close();");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function change_password_slite_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=0;
    if(strlen($_POST["formchangepasssqlite"])<3){
        echo $tpl->post_error("Passwords must be at least 3 characters long");
        return false;
    }
    $passmd5=md5(trim($_POST["formchangepasssqlite"]));
    if(isset($_SESSION["SQLITE_ID"])) {
        if ($_SESSION["SQLITE_ID"] > 0) {
            $ID = intval($_SESSION["SQLITE_ID"]);
        }
    }
    if($ID==0){
        echo $tpl->post_error("Wrong username");
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
    $sql="UPDATE `users` SET passmd5='$passmd5' WHERE ID='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl=new template_admin();
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks("Change user password");

}

function change_manager_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ldap=new clladp();
    $change_admin=trim($_POST["change_admin"]);
    $change_password=trim($_POST["change_password"]);
    if(strpos("\"",$change_password)>0){
        echo $tpl->post_error("{double_quotes_not_supported}");
        return false;
    }

    if(trim(strtolower($change_admin))==strtolower($ldap->ldap_admin)){
        if($change_password==$ldap->ldap_password){
            admin_tracks("Super-admin credentials posted without changes");
            echo $tpl->post_error("{no_change}");
            return false;
        }
    }
    $password_len=strlen($change_password);
    if(!is_dir("/usr/share/artica-postfix/ressources/conf/upload")){
        @mkdir("/usr/share/artica-postfix/ressources/conf/upload",0755,true);
    }
    @file_put_contents("/usr/share/artica-postfix/ressources/conf/upload/ChangeLDPSSET", base64_encode(serialize($_POST)));


    $data=urlencode(base64_encode("{$_POST["change_admin"]}|||{$_POST["change_password"]}"));


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/password/manager/$data"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    


    return admin_tracks("SuperAdmin credentials as been changed from $ldap->ldap_admin to $change_admin with a password of $password_len characters");
}


function change_manager_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();

   

    $f[]="dialogInstance1.close();LoadAjax('table-loader','$page?table=yes');";

    $field[]=$tpl->field_text("change_admin","{SuperAdmin}",$ldap->ldap_admin,true);
    $field[]=$tpl->field_password2("change_password", "{password}", $ldap->ldap_password,true);
    $ht[]="<div id='ch-super-admin-progress'></div>";
    $ht[]=$tpl->form_outside("{SuperAdmin}", @implode("\n", $field),"{SuperAdminChangeExplain}","{apply}",@implode(";",$f));
    echo $tpl->_ENGINE_parse_body($ht);
    return true;

}

function API_LINK_POPUP():bool{
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$tpl=new template_admin();
	$ligne=$q->mysqli_fetch_array("SELECT ID,zmd5 FROM `APIs` WHERE userid='{$_SESSION["uid"]}'");
	$ID=intval($ligne["ID"]);
	$zmd5=$ligne["zmd5"];
	$button="{create_auth_link}";
	$HTTP_HOST=$_SERVER["HTTP_HOST"];
    $form=array();



	if($ID>0){
		$LINK="https://$HTTP_HOST/auth/$zmd5";
		$form[]=$tpl->field_text("nothing", "{link}", $LINK);
		$button="{update_auth_link}";
		
	}
	$tpl->field_hidden("apilink", "$ID");
	echo $tpl->form_outside("Auth Link", $form,"{authlink_explain}",$button,"Loadjs('$page?apilink-js=yes')");
    return true;
}

function API_LINK_SAVE(){
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$FULLCONTENT=base64_encode(serialize($_SESSION));
	$uid=$_SESSION["uid"];
	$md5=md5(time().$FULLCONTENT);
	$ID=$_POST["apilink"];
	
	if($ID==0){
		$sql="INSERT INTO APIs (zmd5,userid,content) VALUES ('$md5','$uid','$FULLCONTENT')";
	}else{
		$sql="UPDATE APIs SET content='$FULLCONTENT',zmd5='$md5' WHERE ID=$ID";
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
	
}

function old_page(){
    $page=CurrentPageName();
    $username=$_SESSION["uid"];
    if($username==-100){ $ldap=new clladp(); $username="$ldap->ldap_admin/{administrator}"; }
    if(isset($_SESSION["ACTIVE_DIRECTORY_INFO"])){ $username=$_SESSION["ACTIVE_DIRECTORY_INFO"]["displayName"][0]; }
    $html="
<div class=\"row border-bottom white-bg dashboard-header\">	<div class=\"col-sm-12\"><h1 class=ng-binding>$username</h1></div></div><div class='row'><div id='progress-myaccount-restart'></div><div class='ibox-content'><div id='table-loader'></div></div></div><script>$.address.state('/');$.address.value('/account');LoadAjax('table-loader','$page?table=yes');</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin($username,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);



}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!method_exists($tpl,"page_header")) {old_page();return true;}

    $username=$_SESSION["uid"];
    if($username==-100){ $ldap=new clladp(); $username="$ldap->ldap_admin/{administrator}"; }

    $html=$tpl->page_header("$username","fad fa-user-tie","{myaccount_text}",
        "$page?table=yes","account","progress-myaccount-restart",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin($username,$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return false;
}



function change_language_form(){
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $htmltools_inc  = new htmltools_inc();
    $lang           = $htmltools_inc->LanguageArray();
    $lang_selected=$_COOKIE["artica-language"];
    $FixedLanguage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FixedLanguage"));
    $lock=0;
    if($FixedLanguage<>null){
        $lang_selected=$FixedLanguage;
        $lock=1;
    }
    $field[]=$tpl->field_array_hash($lang, "lang2", "{mylanguage}", $lang_selected);


    $users=new usersMenus();

    if($users->AsSystemAdministrator){
        $field[]= $tpl->field_section(null,"{fix_language_explain}");
        $field[]=$tpl->field_checkbox("LockLang","{fix_language_perference}",$lock);

    }
    $ht[]=$tpl->form_outside("{mylanguage}", @implode("\n", $field),null,"{apply}","Loadjs('$page?after-language=yes')",null,false,false,"far fa-language");
    echo $tpl->_ENGINE_parse_body($ht);
}

function change_language_perform(){
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["detected_lang"]=$_POST["lang2"];
    if(isset($_POST["LockLang"])){
        $fix=intval($_POST["LockLang"]);
        if($fix==0){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FixedLanguage","");
        }else{
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FixedLanguage",$_POST["lang2"]);
        }
    }


    admin_tracks("Change Web console language to {$_POST["lang2"]}");
    setcookie("artica-language", $_POST["lang2"], time()+31536000);
}
function change_language_after(){
    $xtime=strtotime( '+30 days' );
    $page=CurrentPageName();
    $language=$_SESSION["detected_lang"];
    $f[]="Delete_Cookie('artica-language', '/', '');";
    $f[]="Set_Cookie('artica-language', '$language', '$xtime', '/', '', '');";
    $f[]="dialogInstance1.close();";
    $f[]="LoadAjax('table-loader','$page?table=yes');";
    $f[]="var uri=document.getElementById('fw-left-menus-uri').value;";
    $f[]="LoadAjaxSilent('left-barr',uri);";
    $f[]="LoadAjaxSilent('top-barr','fw-top-bar.php');";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
}
function form_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog3("{parameters}", "$page?form-popup=yes");
}

function form_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HTMLTITLE=null;
    if(!isset($_COOKIE["userfont"])){$_COOKIE["userfont"]="lato";}
    if(!isset($_COOKIE["StandardDropDown"])){$_COOKIE["StandardDropDown"]=0;}
    $userfont=$_COOKIE["userfont"];
    $StandardDropDown=intval($_COOKIE["StandardDropDown"]);
    if(isset($_COOKIE["StandardDropDown"])){$StandardDropDown=intval($_COOKIE["StandardDropDown"]);}
    if(isset($_COOKIE["HTMLTITLE"])){$HTMLTITLE=$_COOKIE["HTMLTITLE"];}
    if($HTMLTITLE==null){$HTMLTITLE="%s (%v)";}

    if($userfont==null){$userfont="lato";}
    $fonts["ailerons"]="Ailerons";
    $fonts["bariol"]="Bariol";
    $fonts["lato"]="Lato";
    $fonts["Roboto"]="Roboto";
    $fonts["standard"]="Standard";

    if($_SESSION["uid"]==-100){
        $ldap=new clladp();
        $field[]=$tpl->field_none_bt("change_admin","{SuperAdmin}",$ldap->ldap_admin,"{change}","Loadjs('$page?change-manager-js=yes')");
        $field[]=$tpl->field_password("change_password", "{password}", $ldap->ldap_password,false,null,true);

    }
    $field[]=$tpl->field_none_bt("lang","{mylanguage}",$_COOKIE["artica-language"],"{change}","Loadjs('$page?change-language-js=yes')");

    $field[]=$tpl->field_array_hash($fonts, "userfont", "{font_family}", $userfont);
    $field[]=$tpl->field_checkbox("StandardDropDown","{standard_dropdown}",$StandardDropDown);
    $field[]=$tpl->field_text("HTMLTITLE","{title_pages}",$HTMLTITLE);

    $tpl->form_add_button("Auth Link", "Loadjs('$page?apilink-js=yes')");
    echo $tpl->form_outside(null, @implode("\n", $field),null,"{apply}","Loadjs('$page?after-form=yes')");
}

function privileges():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $HTMLTITLE=null;
	$ht=array();
	include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
    if(!isset($_COOKIE["userfont"])){$_COOKIE["userfont"]="lato";}
    if(!isset($_COOKIE["StandardDropDown"])){$_COOKIE["StandardDropDown"]=0;}

	if(isset( $_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]] )){
		unset($_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]]);
	}


	$userfont=$_COOKIE["userfont"];
	$StandardDropDown=intval($_COOKIE["StandardDropDown"]);
	if(isset($_COOKIE["StandardDropDown"])){$StandardDropDown=intval($_COOKIE["StandardDropDown"]);}
	if(isset($_COOKIE["HTMLTITLE"])){$HTMLTITLE=$_COOKIE["HTMLTITLE"];}
	if($HTMLTITLE==null){$HTMLTITLE="%s (%v)";}
	
	if($userfont==null){$userfont="lato";}
	$fonts["ailerons"]="Ailerons";
	$fonts["bariol"]="Bariol";
	$fonts["lato"]="Lato";
	$fonts["Roboto"]="Roboto";
	$fonts["standard"]="Standard";

    $username=$_SESSION["uid"];
    if($username=="-100"){
        $username="Manager";
    }

    $tpl->table_form_field_js("Loadjs('$page?form-js=yes')");
    $tpl->table_form_field_text("{member}",$username,ico_user);
    $StandardPass=true;
    if(isset($_SESSION["SQLITE_ID"])) {
        if ($_SESSION["SQLITE_ID"] > 0) {
            $tpl->table_form_field_js("Loadjs('$page?form-change-pass-sqlite=yes')");
            $tpl->table_form_field_text("{password}", "* * * *", ico_field);
            $StandardPass=false;
        }
    }
    if($StandardPass) {
        $tpl->table_form_field_js("Loadjs('$page?form-js=yes')");
        $tpl->table_form_field_text("{password}", "* * * *", ico_field);
    }
    $tpl->table_form_section("{skin}");
    $tpl->table_form_field_text("{font_family}",$fonts[$userfont],ico_field);
    $tpl->table_form_field_bool("{standard_dropdown}",$StandardDropDown,ico_field);
    $tpl->table_form_field_text("{title_pages}",$HTMLTITLE,ico_html);
    $tpl->table_form_field_js("");
	$cr=new TranslateRights(null, $_SESSION["uid"]);
	$ActiveDirectoryIndex="Nan";
	$r=$cr->GetPrivsArray();

	$users=new usersMenus();

	$ldap=new clladp();
	$IsKerbAuth=$ldap->IsKerbAuth();

	

	if(isset($_SESSION["ACTIVE_DIRECTORY_INDEX"])){
		if(is_numeric($_SESSION["ACTIVE_DIRECTORY_INDEX"])){
			$ActiveDirectoryIndex=$_SESSION["ACTIVE_DIRECTORY_INDEX"];
		}
	}
    VERBOSE("$page: ActiveDirectoryIndex=$ActiveDirectoryIndex", __LINE__);
	if(is_numeric($ActiveDirectoryIndex)){
		$IsKerbAuth=true;
		$ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
		$HASH=$ActiveDirectoryConnections[$ActiveDirectoryIndex];
	}

	$CountDePrivs=count($r);
    $cprivs=0;

    if(count($users->NGINX_SERVICES)>0){
        $tpl->table_form_section("{myWebServices}","{myWebServices_text}");
        foreach ($users->NGINX_SERVICES as $ServicesID=>$none){
            if(!is_numeric($ServicesID)){continue;}
            $ServiceName=get_nginx_servicename($ServicesID);
            if(strlen($ServiceName)>3) {
                $cprivs++;
                $tpl->table_form_field_text("{webservice}",$ServiceName, ico_earth);
            }
        }

    }
    if(count($users->SIMPLE_ACLS)>0){
        $tpl->table_form_section("{access_rules}","");
        $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
        foreach ($users->SIMPLE_ACLS as $aclid=>$none){
            $ligne      = $q->mysqli_fetch_array("SELECT ID,aclname FROM webfilters_simpleacls WHERE ID='$aclid'");
            if(!isset($ligne["aclname"])){
                continue;
            }
            $aclname=$ligne["aclname"];
            $tpl->table_form_field_js("Loadjs('fw.proxy.rules.php?rule-settings=$aclid')");
            $cprivs++;
            $tpl->table_form_field_text("{rule}",$aclname, ico_shield);
        }
    }


    $tpl->table_form_field_js("");
    $tpl->table_form_section("{my_privileges}");
    $dPrivs=0;

    if($users->AsHamrpAdmin){
        $cprivs++;
        $dPrivs++;
        $tpl->table_form_field_bool("{APP_HAMRP}",1,ico_check);
    }


    foreach ($r as $key=>$val){
		if($GLOBALS["VERBOSE"]){echo "<li>$key = \"$val\"</li>\n";}
		if($users->$key){
            $dPrivs++;
			$cprivs++;
            $tpl->table_form_field_bool("{{$key}}",1,ico_check);
		}
	}

	if($cprivs==0){
        $tpl->table_form_field_text("{error}","{ERROR_NO_PRIVS2}",ico_infoi_bounce,true);

	}else{
        if($dPrivs==0){
            $tpl->table_form_field_text("{info}","{ERROR_NO_PRIVS2}",ico_lock);
        }
    }

	$ht[]=$tpl->table_form_compile();


    if($_SESSION["uid"]<>"-100") {
	if($IsKerbAuth){
        $tpl->table_form_section("{my_microsoft_groups}");

		$cprivs=0;
		if($GLOBALS["VERBOSE"]){echo "<li><strong>IsKerbAuth = TRUE (line ".__LINE__.")</strong></li>\n";}
		include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
		$ad=new external_ad_search(null,$ActiveDirectoryIndex);
		$groups=$ad->GroupsOfMember($_SESSION["ACTIVE_DIRECTORY_DN"]);

		foreach ($groups as $dn=>$name){
			$cprivs++;
            $tpl->table_form_field_text("$name",$dn,ico_users);
		}

		if($cprivs==0){
            $tpl->table_form_field_text("{error}","{ERROR_NO_GROUP_ACCOUNT}",ico_infoi_bounce,true);
		}
	}else {
        $tpl->table_form_section("{my_groups}");
        $ct = new user($_SESSION["uid"]);
        $groups = $ct->GetGroups($_SESSION["uid"]);

        $cprivs = 0;
        foreach ($groups as $name => $gidNumber) {
            $cprivs++;
            $tpl->table_form_field_bool("$name", 1, ico_users);
        }
    }

        if(isset($_SESSION["SQLITE_ID"])) {
            if ($_SESSION["SQLITE_ID"] > 0) {
                $q = new lib_sqlite("/home/artica/SQLITE/admins.db");

                $sql = "SELECT lnk.ID as tid,groups.ID,groups.groupname,groups.enabled FROM `groups`,`lnk`
	                        WHERE groups.ID=lnk.groupid AND lnk.userid={$_SESSION["SQLITE_ID"]} ORDER BY groupname";
                VERBOSE($sql, __LINE__);
                $results = $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);
                    return false;
                }
                foreach ($results as $index => $ligne) {
                    $groupname = $ligne["groupname"];
                    if ($groupname == null) {$groupname = "{unknown}";}
                    $cprivs++;
                    $tpl->table_form_field_bool("$groupname",1,ico_users);
                }
            }
        }

        if($cprivs==0){
                $tpl->table_form_field_text("{error}", "{ERROR_NO_GROUP_ACCOUNT}", ico_infoi_bounce, true);

        }
		

	}
	
	$ht[]=$tpl->table_form_compile();

	if(!isset($_SESSION["SQUID_DYNAMIC_ACLS"])){$_SESSION["SQUID_DYNAMIC_ACLS"]=array();}
	
	
	if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){
		$ht[]="
		<table class=\"table table-hover\">
		<thead>
		<tr>
		<th>{dynamic_acls_newbee}</th>
		</tr>
		</thead>
		<tbody>";
		$q=new mysql_squid_builder();

		foreach ($_SESSION["SQUID_DYNAMIC_ACLS"] as $gpid=>$val){
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
			$ht[]="<tr><td><span class='fa fa-file-o'></span>&nbsp;{$ligne["GroupName"]}</td></tr>";
		}
		
		$ht[]="</tbody></table>";
		
	}


	$ht[]="</div>";

    $Center=@implode("\n",$ht);
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:150px'><div id='client-certificate'></div></td>";
    $html[]="<td style='vertical-align: top;width:99%;padding-left:15px'>$Center</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $username=$_SESSION["uid"];
    if($username==-100){ $ldap=new clladp(); $username="$ldap->ldap_admin/{administrator}"; }
    $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    $you_have_x_minutes="<br><strong>{sessionid_label}: {unlimited}</strong>";

    if($SessionCookieLifetime>0){
        $final=$_SESSION["SESSARTSTART"]+($SessionCookieLifetime*60);
        $left=$tpl->time_diff_min($final);
        $you_have_x_minutes=$tpl->_ENGINE_parse_body("{you_have_x_minutes}");
        $you_have_x_minutes="<br><strong>{sessionid_label}: ".str_replace("%s",$left,$you_have_x_minutes)."</strong>";
    }



    $TINY_ARRAY["TITLE"]=$username;
    $TINY_ARRAY["ICO"]="fad fa-user-tie";
    $TINY_ARRAY["EXPL"]="{myaccount_text}$you_have_x_minutes";
    $TINY_ARRAY["BUTTONS"]=null;
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="<script>";
    $html[]="LoadAjax('client-certificate','$page?client-certificate=yes');";
    $html[]=$headsjs;
    $html[]="</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}



function lang(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $userfont=$_POST["userfont"];
    $StandardDropDown=$_POST["StandardDropDown"];
    $HTMLTITLE=$_POST["HTMLTITLE"];
    $HTMLTITLE=str_replace("'", "`", $HTMLTITLE);


    setcookie("userfont", $userfont, time()+172800);
    setcookie("StandardDropDown", $StandardDropDown, time()+172800);
    setcookie("HTMLTITLE", $HTMLTITLE, time()+172800);

}
function client_certificate(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js=$tpl->framework_buildjs("webconsole.php?certificate-manager=yes",
        "manager-certificate.progress","manager-certificate.log",
        "progress-myaccount-restart",
        "LoadAjax('client-certificate','$page?client-certificate=yes');");

    if($_SESSION["uid"]<>-100){return false;}
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));
    if($LighttpdArticaClientAuth==0){return false;}
    $LighttpdManagerClientAuth=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdManagerClientAuth"));
    if($LighttpdManagerClientAuth==null){

        $html="<div id='manager-certificate' class='center'>
            <i class='fas fa-file-certificate fa-10x'></i><p style='margin-top: 10px'>
            ".$tpl->button_autnonome("{create_certificate}",$js,"fas fa-file-certificate",null,165)."
          </p>
           </div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;

    }


    $jsdown="window.location.href='$page?pfx-manager=yes'";
    $html="<div id='manager-certificate' class='center'>
            <i class='fas fa-file-certificate fa-10x' style='color:#18a689'></i>
            <p style='margin-top: 10px'>
            <div>".$tpl->button_autnonome("{download}",$jsdown,"fas fa-file-certificate",null,165)."</div>
            
            <div style='margin-top:5px'>".$tpl->button_autnonome("{create_certificate}",$js,"fas fa-file-certificate",null,165,"btn-warning")."</div>
            
            <div style='margin-top:5px'>".$tpl->button_autnonome("{display_certificate}",$js,"fas fa-file-certificate",null,165,"btn-info")."</div>
            
          </p>
           </div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function client_certificate_download(){
    if($_SESSION["uid"]<>"-100"){die();}
    $LighttpdManagerClientAuth=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdManagerClientAuth"));
    $fsize=strlen($LighttpdManagerClientAuth);
    if($fsize==0){die();}
    $content_type=" application/x-pkcs12";
    $tfilename=php_uname("n")."-artica-webconsole.pfx";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$tfilename\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    admin_tracks("Downloaded Web console Client PFX Certificate");
    echo $LighttpdManagerClientAuth;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerCertDown",1);


}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function get_nginx_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}