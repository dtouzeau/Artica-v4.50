<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__) . "/ressources/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectoryRootDSE.inc");
if(isset($_POST["connection"])){connection_save();exit;}
if(isset($_GET["connection-js"])){connection_js();exit;}
if(isset($_GET["connection-popup"])){connection_popup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["remove"])){remove_js();exit;}
if(isset($_GET["bySpopup"])){bySpopup();exit;}
if(isset($_GET["simulate-js"])){simulate_js();exit;}
if(isset($_GET["simulate-popup"])){simulate_popup();exit;}
if(isset($_POST["simusername"])){simulate_save();exit;}
if(isset($_GET["simulate-results"])){simulate_results();exit;}
if(isset($_GET["simulate-perform"])){simulate_results();exit;}
if(isset($_GET["uninstall-js"])){uninstall_js();exit;}
if(isset($_GET["export-js"])){export_js();exit;}
if(isset($_GET["export-popup"])){export_popup();exit;}
if(isset($_POST["export"])){export();exit;}
if(isset($_GET["ADUserCanConnect"])){ADUserCanConnect_save();exit;}

page();


function bySpopup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog("{active_directory_ldap_connections}","$page",1024);
}
function simulate_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{simulate_authentication}","$page?simulate-popup=yes",850);
}
function uninstall_js():bool{
    header("content-type: application/x-javascript");
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ActiveDirectoryFeature.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ActiveDirectoryFeature.log";
    $ARRAY["CMD"]="/activedirectory/uninstall";
    $ARRAY["TITLE"]="{uninstall} Active Directory";
    $ARRAY["AFTER"]="document.location.href='/index'";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-adldap-restart')";
    echo $jsRestart;
    return true;
}

function simulate_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<div id='simulate-results'></div>";
    $form[]=$tpl->field_text("simusername","{username}",$_SESSION["SIMULATE-USERNAME"],true);
    $form[]=$tpl->field_password("simpassword","{password}",$_SESSION["SIMULATE-PASSWORD"],true);
    $html[]=$tpl->form_outside("{simulate_authentication}",$form,"{simulate_authentication_explain}","{apply}","LoadAjax('simulate-results','$page?simulate-perform=yes')");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function simulate_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["SIMULATE-USERNAME"]=$_POST["simusername"];
    $_SESSION["SIMULATE-PASSWORD"]=$_POST["simpassword"];
}

function export_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{import_export}","$page?export-popup=yes");
    return true;
}
function export_popup():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $ActiveDirectoryConnections=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections");
    $KerbAuthInfos=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos");
    $FULL["ActiveDirectoryConnections"]=$ActiveDirectoryConnections;
    $FULL["KerbAuthInfos"]=$KerbAuthInfos;
    $data=base64_encode(serialize($FULL));
    $jsrestart="LoadAjax('table-ldap-connect','$page?popup=yes');";
    $form[]=$tpl->field_textarea("export", "{connections}", $data,"664px");
    echo $tpl->form_outside("{import_export}", @implode("\n", $form),null,"{apply}",$jsrestart);
    return true;
}

function export():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $data=unserializeb64($_POST["export"]);
    if(!isset($data["KerbAuthInfos"])){
        $tpl->post_error("{corrupted_parameters}");
        return false;
    }
    if(!isset($data["ActiveDirectoryConnections"])){
        $tpl->post_error("{corrupted_parameters}");
        return false;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryConnections",$data["ActiveDirectoryConnections"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",$data["KerbAuthInfos"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    return true;
}

function ifRights($users){

    if($users->AsSystemAdministrator){
        $GLOBALS["Ifrights"][]="As System Administrator = <strong>True</strong>";
        return true;
    }
    $GLOBALS["Ifrights"][]="As System Administrator = False";

    if($users->AsFirewallManager){
        $GLOBALS["Ifrights"][]="As firewall manager = <strong>True</strong>";
        return true;}

    $GLOBALS["Ifrights"][]="As firewall manager = False";
    if($users->AsVPNManager){
        $GLOBALS["Ifrights"][]="As VPN Manager = <strong>True</strong>";
        return true;}
    $GLOBALS["Ifrights"][]="As VPN manager = False";

    if($users->AsSquidAdministrator){
        $GLOBALS["Ifrights"][]="As Proxy Manager = <strong>True</strong>";
        return true;

    }
    $GLOBALS["Ifrights"][]="As Proxy Manager = False";

    if($users->AsProxyMonitor){
        $GLOBALS["Ifrights"][]="As Proxy Monitor = <strong>True</strong>";
        return true;}
    $GLOBALS["Ifrights"][]="As Proxy Monitor = False";
    if($users->AsMessagingOrg ){
        $GLOBALS["Ifrights"][]="As Messaging manager = <strong>True</strong>";
        return true;}
    $GLOBALS["Ifrights"][]="As Messaging manager = False";
    if($users->AsPostfixAdministrator ){
        $GLOBALS["Ifrights"][]="As SMTP manager = <strong>True</strong>";
        return true;
    }

    $GLOBALS["Ifrights"][]="As SMTP manager = False";
    if($users->AsDnsAdministrator ){
        $GLOBALS["Ifrights"][]="As DNS manager = <strong>True</strong>";
        return true;
    }
    $GLOBALS["Ifrights"][]="As DNS Administrator = False";
    $GLOBALS["Ifrights"][]="<strong>No privileges!!</strong>";
    return false;
}

function simulate_results(){
    include_once(dirname(__FILE__).'/ressources/class.user.inc');
    include_once(dirname(__FILE__).'/ressources/class.privileges.inc');
    include_once(dirname(__FILE__).'/ressources/class.artica-logon.inc');
    $tpl=new template_admin();
    $login=new artica_logon();
    $login->Simulate=true;
    $username=$_SESSION["SIMULATE-USERNAME"];
    $MAINstyle="style='width:100%;margin-bottom: 15px;height:450px;overflow:auto'";
    if(!$login->CheckCreds($_SESSION["SIMULATE-USERNAME"],$_SESSION["SIMULATE-PASSWORD"])) {
        $html[] = "<div $MAINstyle class='center'>" . $tpl->widget_rouge($_SESSION["SIMULATE-USERNAME"], "{failed}");

        foreach ($login->VERBOSE as $line) {
            $HR = false;
            $style = null;
            if (strpos("   $line", "-HR-") > 0) {
                $line = str_replace("-HR-", "", $line);
                $HR = true;
            }
            if ($HR) {
                $style = "style='border-top:2px solid #CCCCCC;width:100%;margin-top:8px'";
            }
            if (strlen($line) > 0) {
                $line = "<code>$line</code>";
            }
            $html[] = "<div $style>$line</div>\n";

        }

        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    if(is_numeric($login->ACTIVE_DIRECTORY_INDEX)){
        $username="AD:$login->ACTIVE_DIRECTORY_INDEX:$login->ACTIVE_DIRECTORY_DN:user=$login->username";

    }

    $privs=new privileges($username,null,0,true);
    $privileges_array=$privs->privs;
    if(!is_array($privileges_array)){$privileges_array=array();}
    $GLOBALS["Ifrights"][]="privileges_array == ".count($privs->privs);
    $users = new usersMenus();
    if(count($privileges_array)>0) {
        $users->_TranslateRights($privileges_array, true);
    }

    if(!ifRights($users)){
        $html[] = "<div $MAINstyle class='center'>" . $tpl->widget_rouge($_SESSION["SIMULATE-USERNAME"], "{iar}");

    }else{
        $html[]="<div $MAINstyle class='center'>".$tpl->widget_vert($_SESSION["SIMULATE-USERNAME"],"{success}");
    }

    foreach ($login->VERBOSE as $line) {
        $HR = false;
        $style = null;
        if (strpos("   $line", "-HR-") > 0) {
            $line = str_replace("-HR-", "", $line);
            $HR = true;
        }
        if ($HR) {
            $style = "style='border-top:2px solid #CCCCCC;width:100%;margin-top:8px'";
        }
        if (strlen($line) > 0) {
            $line = "<code>$line</code>";
        }
        $html[] = "<div $style>$line</div>\n";

    }

    foreach ($privs->VERBOSE as $line) {
        $HR = false;
        $style = null;
        if (strpos("   $line", "-HR-") > 0) {
            $line = str_replace("-HR-", "", $line);
            $HR = true;
        }
        if ($HR) {
            $style = "style='border-top:2px solid #CCCCCC;width:100%;margin-top:8px'";
        }
        if (strlen($line) > 0) {
            $line = "<code>$line</code>";
        }
        $html[] = "<div $style>$line</div>\n";

    }

    foreach ($GLOBALS["Ifrights"] as $line) {
        $HR = false;
        $style = null;
        if (strpos("   $line", "-HR-") > 0) {
            $line = str_replace("-HR-", "", $line);
            $HR = true;
        }
        if ($HR) {
            $style = "style='border-top:2px solid #CCCCCC;width:100%;margin-top:8px'";
        }
        if (strlen($line) > 0) {
            $line = "<code>$line</code>";
        }
        $html[] = "<div $style>$line</div>\n";

    }

    $html[]="</div>";echo $tpl->_ENGINE_parse_body($html);
    return true;


}


function page(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();

    $html= $tpl->page_header("{active_directory_ldap_connections}",
        "fab fa-windows","{ad_ldap_parameters_explain}",
        "$page?popup=yes","ad-ldap","progress-adldap-restart",false,"active_directory_ldap_connections");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{active_directory_ldap_connections}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);


}

function ADUserCanConnect_save():bool{
    $ID=intval($_GET["ADUserCanConnect"]);
    $md=$_GET["md"];
    CheckHaCluster();
    VERBOSE("Check $ID",__LINE__);
    if($ID==0) {
        $array = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

        $ADUserCanConnect = intval($array["ADUserCanConnect"]);
        if ($ADUserCanConnect == 1) {
            $array["ADUserCanConnect"] = 0;
        } else {
            $array["ADUserCanConnect"] = 1;
        }
        $KerbAuthInfos = base64_encode(serialize($array));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos", $KerbAuthInfos);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");

        header("content-type: application/x-javascript");
        $html = base64_encode(td_UserADCanConnect($array, $ID, $md));
        $Id = "ADUserCanConnect$md";
        echo "if ( !document.getElementById('$Id') ){alert('$Id not found');}\n";

        echo "if (document.getElementById('$Id') ){\n";
        echo "document.getElementById('$Id').innerHTML=base64_decode('$html')\n";
        echo "}";
        return admin_tracks("Save User Can Connect AD={$array["ADUserCanConnect"]} on Default Active Directory connection");

    }

    $RealKey=$ID-1;
    $connexion=$RealKey;
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if(!is_array($ActiveDirectoryConnections)){
        $ActiveDirectoryConnections[0]=DefaultConnection();
    }

    foreach ($ActiveDirectoryConnections as $index => $Farray) {
        $Cconnection=$Farray["connection"];
        VERBOSE("$Cconnection($index) == $ID ?",__LINE__);
        if ($Cconnection == $ID) {
            VERBOSE("Found index $index",__LINE__);
            $RealKey=$index;
        }
    }


    $array=$ActiveDirectoryConnections[$RealKey];
    $ADUserCanConnect=intval($array["ADUserCanConnect"]);
    if($ADUserCanConnect==1){
        $array["ADUserCanConnect"]=0;
    }else{
        $array["ADUserCanConnect"]=1;
    }
    if (isset($array["connection"])) {
        $connexion = intval($array["connection"]);
    }

    $array["connection"]=$connexion;
    $ActiveDirectoryConnections[$RealKey]=$array;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryConnections",serialize($ActiveDirectoryConnections));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");

    header("content-type: application/x-javascript");
    $html=base64_encode(td_UserADCanConnect($array,$connexion,$md));
    $Id="ADUserCanConnect$md";
    echo "if (document.getElementById('$Id') ){\n";
    echo "document.getElementById('$Id').innerHTML=base64_decode('$html')\n";
    echo "}";

    return admin_tracks("Save User Can Connect AD={$array["ADUserCanConnect"]} on Active Directory connection #$RealKey");
}

function connection_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["connection-js"]);
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if($ID==0){
        $array = $ActiveDirectoryConnections[$ID] ?? DefaultConnection();
        $title="{default}: {$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
        $tpl->js_dialog2($title, "$page?connection-popup=$ID");
        return;

    }
    if($ID==9999999999999){
        $title=$tpl->_ENGINE_parse_body("{new_connection}");
        $tpl->js_dialog2($title, "$page?connection-popup=$ID");
        return;
    }
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $array=$ActiveDirectoryConnections[$ID];
    $title="{$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
    $tpl->js_dialog2($title, "$page?connection-popup=$ID");


}
function remove_js():bool{
    $ID=intval($_GET["remove"]);
    $page=CurrentPageName();
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if(isset($_GET["connection"])){
        foreach ($ActiveDirectoryConnections as $index=>$ligne){
            if(isset($ligne["connection"])){
                if($ligne["connection"]==$_GET["connection"]){
                    unset($ActiveDirectoryConnections[$index]);
                    $datas=serialize($ActiveDirectoryConnections);
                    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "ActiveDirectoryConnections");
                    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
                    if($Enablehacluster==1) {
                        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
                    }
                    header("content-type: application/x-javascript");
                    echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');\n";
                    echo "LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');\n";
                    return true;
                }
            }

        }
    }
    if(!isset($ActiveDirectoryConnections[$ID])){
        $tpl=new template_admin();
        echo $tpl->js_error("£ID not found");
        return false;
    }


    unset($ActiveDirectoryConnections[$ID]);
    $datas=serialize($ActiveDirectoryConnections);
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "ActiveDirectoryConnections");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');\n";
    echo "LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    if($Enablehacluster==1) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    }
return  true;
}
function popup():bool{
    $page=CurrentPageName();
    $html="<div id='active_directory_ldap_connections'></div><script>LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');</script>";
    echo $html;
    return true;
}

function CheckHaCluster():bool{
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==0){return  false;}
    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $KerberosUsername=$haClusterAD["KerberosUsername"];
    $KerberosPassword=$haClusterAD["KerberosPassword"];
    $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];
    $kerberosActiveDirectory2Host=$haClusterAD["kerberosActiveDirectory2Host"];
    $kerberosActiveDirectorySuffix=trim($haClusterAD["kerberosActiveDirectorySuffix"]);


    $array["LDAP_DN"]=$KerberosUsername;
    $array["LDAP_SUFFIX"]=$kerberosActiveDirectorySuffix;
    $array["LDAP_SERVER"]=$kerberosActiveDirectoryHost;
    $array["LDAP_SERVER2"]=$kerberosActiveDirectory2Host;
    $array["LDAP_PASSWORD"]=$KerberosPassword;


    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if(!isset($ActiveDirectoryConnections[0]["LDAP_SERVER"])){
        $ActiveDirectoryConnections[0]=$array;
        $datas=serialize($ActiveDirectoryConnections);
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "ActiveDirectoryConnections");
    }
    return true;
}

function td_UserADCanConnect($array=array(),$index=0,$md5=null):string{
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(!isset($array["ADUserCanConnect"])){
        $array["ADUserCanConnect"]=0;
    }
    VERBOSE("$page?ADUserCanConnect=$index&md=$md5",__LINE__);
    $href="<a href='#' onclick=\"Loadjs('$page?ADUserCanConnect=$index&md=$md5')\">";
    if ($array["ADUserCanConnect"] == 0) {
        return $tpl->_ENGINE_parse_body("$href<span class='label label-danger'>{deny}</span></a>");
    }
    return $tpl->_ENGINE_parse_body("$href<span class='label label-primary'>{allow}</span></a>");

}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $TRCLASS=null;
    $active=new ActiveDirectory();
    CheckHaCluster();
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    if($LockActiveDirectoryToKerberos==1){$EnableKerbAuth=1;}
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $row1prc=$tpl->table_td1prc();


    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" id='active-directory-barr'>";
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $btn[]=$tpl->button_label_table("{new_connection}", "blur()", "fa-plus","AllowAddGroup",true);
    }else {
        if($PowerDNSEnableClusterSlave==0) {
            $btn[] = $tpl->button_label_table("{new_connection}", "Loadjs('$page?connection-js=9999999999999')", "fa-plus", "AllowAddGroup");
        }
    }
    $btn[]=$tpl->button_label_table("{refresh}", "LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');", "fal fa-sync-alt","AllowAddGroup");

    $btn[]=$tpl->button_label_table("{simulate_authentication}", "Loadjs('$page?simulate-js=yes');", "fas fa-debug","AllowAddGroup");

    if($PowerDNSEnableClusterSlave==0) {
        $btn[] = $tpl->button_label_table("{import_export}", "Loadjs('$page?export-js=yes');", "fas fa-file-import", "AllowAddGroup");

        if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            $btn[] = $tpl->button_label_table("{remove_feature}", "Loadjs('$page?uninstall-js=yes');", "fas fa-debug", "AllowAddGroup");
        }
    }

    $btn[]="</div>";
    $html[]="<table id='table-active-directory-connections' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{username}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%' nowrap>Artica Console</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

    if($EnableKerbAuth==1){
        $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        if($GLOBALS["VERBOSE"]) {
            VERBOSE("KerbAuthInfos=",__LINE__);
            print_r($array);
        }

        if(!isset($array["ADUserCanConnect"])){$array["ADUserCanConnect"]=0;}
        if(!isset($array["LDAP_SERVER"])) {$array["LDAP_SERVER"]="";}
        if(!isset($array["fullhosname"])) {$array["fullhosname"]="";}
        if(!isset($array["LDAP_DN"])) {$array["LDAP_DN"]="";}
        if(!isset($array["LDAP_SUFFIX"])) {$array["LDAP_SUFFIX"]="";}
        if(!isset($array["LDAP_SSL"])) {$array["LDAP_SSL"]=0;}

        if($array["LDAP_SERVER"]==""){
            if(strlen($array["fullhosname"])>3){
                $array["LDAP_SERVER"]=$array["fullhosname"];
            }
        }


        if($GLOBALS["VERBOSE"]) {
            print_r($array);
        }
        if(!isset($array["LDAP_DN"])){
            if(isset($array["WINDOWS_SERVER_ADMIN"])){
                $array["LDAP_DN"]=$array["WINDOWS_SERVER_ADMIN"];
            }
        }
        if(!isset($array["LDAP_PORT"])){$array["LDAP_PORT"]=null;}

        if(!isset($array["LDAP_PASSWORD"])){
            if(isset($array["WINDOWS_SERVER_PASS"])){
                $array["LDAP_PASSWORD"]=$array["WINDOWS_SERVER_PASS"];
            }
        }

        if(!isset($array["LDAP_PASSWORD"])){
            $array["LDAP_PASSWORD"]="";
        }
        if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
        if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
        if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
        if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
        if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
        if($array["LDAP_SSL"]==null){$array["LDAP_SSL"]=$active->ldap_ssl;}

        if(preg_match("#^(.+?)@(.+?)@$#",trim($array["LDAP_DN"]),$re)){$array["LDAP_DN"]="$re[1]@$re[2]";}

        if(strlen($array["LDAP_SERVER"])>3) {
            $label = test_ldap_connection($array);
            $failed = $GLOBALS["LDAP_CONNECTION_FAILED"];
            if ($failed <> null) {
                $failed = "&nbsp;<div class='text-danger'>$failed</div>";
            }

            $js = "Loadjs('$page?connection-js=0')";
            if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
                $js = "blur();";
            }

            if (intval($array["LDAP_SSL"]) == 1) {
                $array["LDAP_SERVER"] = "ldaps://{$array["LDAP_SERVER"]}:636";
            }

            $default="&nbsp;<span class='label label-default'>{default}</span>$failed";
            $delete = $tpl->icon_nothing();
            $html[] = "<tr class='$TRCLASS'>";
            $html[] = "<td $row1prc>$label</td>";
            $html[] = "<td >" . $tpl->td_href($array["LDAP_SERVER"] . " $default", "{click_to_edit}", $js) . "</td>";
            $html[] = "<td >" . $tpl->td_href($array["LDAP_DN"], "{click_to_edit}", $js) . "</td>";
            $md5=md5(serialize($array).time()+rand(1,100));
            $td_UserADCanConnect=td_UserADCanConnect($array,0,$md5);
            $html[] = "<td $row1prc><span id='ADUserCanConnect$md5'>$td_UserADCanConnect</span>";
            $html[] = "<td style='width:1%' nowrap>$delete</td>";
            $html[] = "</tr>";
        }
    }
    $Data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections");
    VERBOSE("FOUND [$Data]",__LINE__);
    $ActiveDirectoryConnections=unserialize($Data);
    if(!is_array($ActiveDirectoryConnections)){
        VERBOSE("ActiveDirectoryConnections is not an Array",__LINE__);
        $ActiveDirectoryConnections=array();
    }
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    VERBOSE("Start looping",__LINE__);
    foreach ($ActiveDirectoryConnections as $index=>$ligne){
        VERBOSE("INDEX[$index]",__LINE__);
        $VirtualKey=$index;
        if(!is_numeric($index)){
            VERBOSE("$index is not a number",__LINE__);
            continue;
        }


        if($HaClusterClient==0) {
            if ($EnableKerbAuth == 1) {
                if ($index == 0) {
                    VERBOSE("EnableKerbAuth and index=0 continue", __LINE__);
                    continue;
                }
            }
        }

        if($GLOBALS["VERBOSE"]) {print_r($ligne);}
        if(!isset($ligne["LDAP_PORT"])){$ligne["LDAP_PORT"]=389;}
        if(!isset($ligne["LDAP_SSL"])){$ligne["LDAP_SSL"]=0;}
        if(!isset($ligne["ADNETIPADDR"])){$ligne["ADNETIPADDR"] ="";}
        if(!isset($ligne["WINDOWS_SERVER_ADMIN"])){
            if(isset($ligne["LDAP_DN"])){
                $ligne["WINDOWS_SERVER_ADMIN"]=$ligne["LDAP_DN"];
            }
        }

        if(!isset($ligne["LDAP_SERVER"])) {
            if ($ligne["ADNETIPADDR"] <> null) {
                $ligne["LDAP_SERVER"] = $ligne["ADNETIPADDR"];
            }
        }
        if(!isset($ligne["LDAP_SERVER"])) {continue;}
        $label=test_ldap_connection($ligne);
        $failed=$GLOBALS["LDAP_CONNECTION_FAILED"];
        $server = $ligne["LDAP_SERVER"];
        VERBOSE("{$ligne["LDAP_SERVER"]}: server = [$server]", __LINE__);
        if($ligne["ADNETIPADDR"]<>null){$server = $ligne["ADNETIPADDR"];}

        $RootDSE=null;
        if($failed<>null){
            VERBOSE("{$ligne["LDAP_SERVER"]}: CONNECTION FAILED --> $failed", __LINE__);
            $failed="<div class='text-danger'>$failed</div>";
        }

        if($failed==null){
            VERBOSE("$server: -- RootDSE", __LINE__);
            $dse=new ad_rootdse($server, $ligne["LDAP_PORT"], $ligne["LDAP_DN"], $ligne["LDAP_PASSWORD"],$ligne["LDAP_SSL"]);
            $RootDSE=$dse->RootDSE();
            if(!$dse->ok){
                $failed="<div class='text-danger'>$dse->mysql_error</div>";
                $label="<span class='label label-danger'>{failed_to_connect}</span>";
            }
            if($RootDSE<>null){
                if($ligne["LDAP_SUFFIX"]==null){
                    $ligne["LDAP_SUFFIX"]=$RootDSE;
                    $ActiveDirectoryConnections[$index]=$ligne;
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryConnections",serialize($ActiveDirectoryConnections));
                    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
                }
                $suff1=trim(strtolower($ligne["LDAP_SUFFIX"]));
                $suff2=trim(strtolower($RootDSE));
                if($suff1<>$suff2){
                    $RootDSE="$suff1 {not} $suff2";
                    $label="<span class='label label-warning'>SUFFIX ERROR</span>";
                }
                $RootDSE="<br><small>{suffix}:$RootDSE</small>";}
        }

        if(intval($ligne["LDAP_SSL"])==1){$ligne["LDAP_SERVER"]="ldaps://$server:636";}
        $Key2="";
        if(isset($array["connection"])){
            if(is_numeric($array["connection"])) {
                $Key2 = "&connection={$array["connection"]}";
            }
        }
        $default="";
        $js="Loadjs('$page?connection-js=$VirtualKey')";
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$js="blur();";}
        $delete=$tpl->icon_delete("Loadjs('$page?remove=$VirtualKey$Key2')","AsSystemAdministrator");

        if($VirtualKey==0){
            $delete=$tpl->icon_delete("");
            $default="&nbsp;&nbsp;<span class='label label-default'>{default}</span>$failed";
        }

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td $row1prc>$label</td>";
        $html[]="<td >". $tpl->td_href($ligne["LDAP_SERVER"].$default,"{click_to_edit}",$js)."$failed$RootDSE</td>";
        $html[]="<td >". $tpl->td_href($ligne["LDAP_DN"],"{click_to_edit}",$js)."</td>";

        $md5=md5(serialize($ligne).time()."".rand(1,100));
        $td_UserADCanConnect=td_UserADCanConnect($ligne,$VirtualKey,$md5);
        $html[] = "<td $row1prc><span id='ADUserCanConnect$md5'>$td_UserADCanConnect</span>";
        $html[]="<td $row1prc>$delete</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $TINY_ARRAY["TITLE"]="{active_directory_ldap_connections}";
    $TINY_ARRAY["ICO"]="fab fa-windows";
    $TINY_ARRAY["EXPL"]="{ad_ldap_parameters_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-active-directory-connections').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function test_ldap_connection($array):string{

    if(!isset($array["LDAP_SUFFIX"])) {
            return "<span class='label label-danger'>{no_ldap_suffix}</span>";

    }
    if(strlen($array["LDAP_SUFFIX"])<3) {
        return "<span class='label label-danger'>{no_ldap_suffix}</span>";
    }
    $ad=new ActiveDirectory();
    if($ad->test_ldap_connection($array["LDAP_SUFFIX"])){
        return "<span class='label label-primary'>{connected}</span>";
    }

    return "<span class='label label-danger'>{failed_to_connect}</span>";

}

function CleanArray($ActiveDirectoryConnections):array{
    $NewArray=array();
    $c=0;
    foreach($ActiveDirectoryConnections as $index=>$main){
        if(!is_numeric($index)){continue;}
        $NewArray[$index]=$main;
        $c++;
    }
    if($c==0){
     $NewArray2[]=DefaultConnection();
     return $NewArray2;
    }
    return $NewArray;

}

function connection_save():bool{
    $tpl    = new template_admin();
    $tpl->CLEAN_POST();
    $ID     = intval($_POST["connection"]);
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $ActiveDirectoryConnectionsTemp=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections");
    if(strlen($ActiveDirectoryConnectionsTemp)<4) {
        $ActiveDirectoryConnectionsTemp=serialize(array());
    }

    writelogs("Save Active Directory Connection ID:$ID",__FUNCTION__,__FILE__
            ,__LINE__);
    $ActiveDirectoryConnections=CleanArray(unserialize($ActiveDirectoryConnectionsTemp));
    $_POST["WINDOWS_SERVER_ADMIN"]=$_POST["LDAP_DN"];
    if($ID==0){
        if($Enablehacluster==1){
            $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
            $haClusterAD["kerberosActiveDirectoryHost"]=$_POST["LDAP_SERVER"];
            $haClusterAD["kerberosActiveDirectory2Host"]=$_POST["LDAP_SERVER2"];
            $haClusterAD["KerberosUsername"]=$_POST["LDAP_DN"];
            $haClusterAD["KerberosPassword"]=$_POST["LDAP_PASSWORD"];
            $haClusterAD["kerberosActiveDirectorySuffix"]=$_POST["LDAP_SUFFIX"];
            $haClusterAD["KerberosLDAPS"]=$_POST["LDAP_SSL"];
            $datas=base64_encode(serialize($haClusterAD));
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",$datas);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
        }

        $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        foreach ($_POST as $key=>$val){$array[$key]=$val;}
        $datas=base64_encode(serialize($array));
        $ActiveDirectoryConnections[0]=$array;
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "KerbAuthInfos");
        $datas=serialize($ActiveDirectoryConnections);
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "ActiveDirectoryConnections");
        $_POST["LDAP_PASSWORD"]="****";
        if($Enablehacluster==1) {
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        }
        return admin_tracks_post("Update Default Active Directory Connection");
    }

    if($ID==9999999999999){
        if(count($ActiveDirectoryConnections)==0){
            $ActiveDirectoryConnections[1]=$_POST;
        }else{
            $ActiveDirectoryConnections[]=$_POST;
        }
        $datas=serialize($ActiveDirectoryConnections);
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "ActiveDirectoryConnections");
        $_POST["LDAP_PASSWORD"]="****";
        if($Enablehacluster==1) {
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        }
        return admin_tracks_post("Create a new Active Directory Connection #$ID");
    }

    $ID=$ID-1;
    if($ID<1){$ID=1;}

    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    foreach ($_POST as $key=>$val){$array[$key]=$val;}

    $ActiveDirectoryConnections[$ID]=$array;
    $datas=serialize($ActiveDirectoryConnections);
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "ActiveDirectoryConnections");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    }
    $_POST["LDAP_PASSWORD"]="****";
    return admin_tracks_post("Save Active Directory Connection #$ID");

}

function DefaultConnection():array{
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));

    if($Enablehacluster==1){
        $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
        $KerberosUsername=$haClusterAD["KerberosUsername"];
        $KerberosPassword=$haClusterAD["KerberosPassword"];
        $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];
        $kerberosActiveDirectorySuffix=trim($haClusterAD["kerberosActiveDirectorySuffix"]);
        $KerberosLDAPS=intval($haClusterAD["KerberosLDAPS"]);
        if(!isset($haClusterAD["kerberosActiveDirectory2Host"])){
            $haClusterAD["kerberosActiveDirectory2Host"]="";
        }
        $ldap_port=389;
        if($KerberosLDAPS==1){
            $ldap_port=636;
        }
        $array["LDAP_DN"]=$KerberosUsername;
        $array["LDAP_SUFFIX"]=$kerberosActiveDirectorySuffix;
        $array["LDAP_SERVER"]=$kerberosActiveDirectoryHost;
        $array["LDAP_PORT"]=$ldap_port;
        $array["LDAP_PASSWORD"]=$KerberosPassword;
        $array["LDAP_SSL"]=$KerberosLDAPS;
        $array["LDAP_SERVER2"]=$haClusterAD["kerberosActiveDirectory2Host"];
        return $array;

    }
    $active=new ActiveDirectory();
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
    if(!isset($array["LDAP_SERVER2"])){$array["LDAP_SERVER2"]=null;}
    if(!isset($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
    if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=null;}
    if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]=null;}
    if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=$array["WINDOWS_SERVER_ADMIN"];}
    if(!isset($array["ADNETIPADDR"])){$array["ADNETIPADDR"]=null;}
    if($array["LDAP_PASSWORD"]==null){
        if(isset($array["WINDOWS_SERVER_PASS"])) {
            $array["LDAP_PASSWORD"] = $array["WINDOWS_SERVER_PASS"];
        }
    }

    if($array["ADNETIPADDR"]==null){$array["ADNETIPADDR"]=$active->ldap_ipaddr;}
    if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
    if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
    if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
    if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
    if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
    if($array["LDAP_SSL"]==null){$array["LDAP_SSL"]=$active->ldap_ssl;}
    if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$array["fullhosname"];}
    $array["connection"]=0;
    return $array;
}

function connection_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["connection-popup"]);
    $btname="{apply}";
    $jsafter=null;
    $title="";
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if($ID==0){
        $array = $ActiveDirectoryConnections[$ID] ?? DefaultConnection();
        $title="{default}: {$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
        $jsafter="LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');";

    }
    if($ID==9999999999999){
        $btname="{add}";
        $jsafter="dialogInstance2.close();LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');";
        $title="{new_connection}";
    }

    if($ID<9999999999999){
        if($ID>0){
            $array=$ActiveDirectoryConnections[$ID];
            $title="[$ID]: {$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
            $jsafter="LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');";
        }
    }
    if(!isset($array["LDAP_SERVER2"])){$array["LDAP_SERVER2"]="";}
    if(!isset($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
    if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]="";}
    if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]="";}
    if(!isset($array["ADNETIPADDR"])){$array["ADNETIPADDR"]="";}
    if(!isset($array["LDAP_SSL"])){$array["LDAP_SSL"]="0";}
    if(!isset($array["ADUserCanConnect"])){$array["ADUserCanConnect"]="0";}


    if(preg_match("#^(.+?)@(.+?)@$#",trim($array["LDAP_DN"]),$re)){$array["LDAP_DN"]="$re[1]@$re[2]";}

    if(intval($array["LDAP_PORT"])==0){$array["LDAP_PORT"]=389;}


    $form[]=$tpl->field_info("connection", "{connection} ID",$ID);
    $form[]=$tpl->field_text("LDAP_SERVER", "{hostname}","{$array["LDAP_SERVER"]}",true);
    $form[]=$tpl->field_text("ADNETIPADDR", "{ipaddr} ({optional})","{$array["ADNETIPADDR"]}");
    $form[]=$tpl->field_text("LDAP_SERVER2", "{FQDNDC2}", $array["LDAP_SERVER2"],false,"{ad_quick_1}");
    $form[]=$tpl->field_numeric("LDAP_PORT", "{ldap_port}","{$array["LDAP_PORT"]}");
    $form[]=$tpl->field_checkbox("LDAP_SSL", "{enable_ssl} (port 636)","{$array["LDAP_SSL"]}");
    $form[]=$tpl->field_section("{credentials}");
    $form[]=$tpl->field_email("LDAP_DN", "{username}","{$array["LDAP_DN"]}",true);
    $form[]=$tpl->field_password2("LDAP_PASSWORD", "{password}", $array["LDAP_PASSWORD"]);
    $form[]=$tpl->field_checkbox("ADUserCanConnect","{ADUserCanConnect}",intval($array["ADUserCanConnect"]),false,"{ADUserCanConnect_explain}");
    $form[]=$tpl->field_section("{suffix}");
    $form[]=$tpl->field_ad_suffix("LDAP_SUFFIX", "{ldap_suffix}","{$array["LDAP_SUFFIX"]}",true);

    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $ClusterNotReplicateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateAD"));
    $LOCK=false;
    if($PowerDNSEnableClusterSlave==1){
        $LOCK=true;
    }
    if($ClusterNotReplicateAD==1){
        $LOCK=false;
    }
    echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,"AsSystemAdministrator",true,$LOCK);

    return true;




}