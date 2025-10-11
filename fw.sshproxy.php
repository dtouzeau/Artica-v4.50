<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.openssh.inc");

if(isset($_GET["sshproxy-status"])){service_status();exit;}
if(isset($_GET["sshproxy-settings"])){service_settings();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["forward-start"])){forward_start();exit;}
if(isset($_GET["search"])){forward_table();exit;}
if(isset($_GET["popup-js"])){forward_popup_js();exit;}
if(isset($_GET["popup"])){forward_popup();exit;}
if(isset($_POST["username"])){forward_save();exit;}
if(isset($_GET["switch-js"])){forward_switch();exit;}
if(isset($_GET["delete-js"])){forward_delete();exit;}
if(isset($_GET["hostkey-js"])){hostkey_js();exit;}
if(isset($_GET["hostkey-popup"])){hostkey_popup();exit;}
if(isset($_POST["privkey"])){hostkey_safe();exit;}
if(isset($_GET["options-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_POST["SSHProxyInterface"])){options_save();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_SSHPROXY}",ico_terminal,
        "{APP_SSHPROXY_EXPLAIN}","$page?tabs=yes","sshproxy","progress-sshd-restart",
        false,"table-loader-sshd-service");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SSHPROXY}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{APP_SSHPROXY}"]="$page?table=yes";
    $array["{forward_rules}"]="$page?forward-start=yes";
    $array["{events}"]="fw.sshd.events.php";
    echo $tpl->tabs_default($array);
    return true;
}
function forward_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'></div>";
    echo $tpl->search_block($page);
    return true;
}

function service_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh2proxy/status");

    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }



    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);

    $jsrestart=$tpl->framework_buildjs(
        "/ssh2proxy/restart",
        "restart-sshproxy.progress",
        "restart-sshproxy.log",
        "progress-sshd-restart",
        "LoadAjax('sshproxy-status','$page?sshproxy-status=yes');"

    );
    $jshelp="s_PopUpFull('https://wiki.articatech.com/ssh/sshproxy',1024,768,'SSH Proxy')";

    $topbuttons[]=array($jsrestart,ico_retweet,"{reconfigure_service}");
    $topbuttons[]=array($jshelp,"fas fa-question-circle","WIKI");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{APP_SSHPROXY}";
    $TINY_ARRAY["ICO"]=ico_terminal;
    $TINY_ARRAY["EXPL"]="{APP_SSHPROXY_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshproxy";

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

   echo  $tpl->SERVICE_STATUS($bsini, "APP_SSHPROXY",$jsrestart);
   echo "<script>$jstiny</script>";
   return true;
}

function forward_popup_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $user=$_GET["user"];
    if($user==null) {
        $title = "{new_rule}";
    }else{
        $title=$_GET["user"];
    }
    $tpl->js_dialog2("modal:$title","$page?popup=yes&user=$user&function=$function");
    return true;
}
function options_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("modal: {service_settings}","$page?options-popup=yes");
    return true;
}
function options_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SSHProxyInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHProxyInterface"));
    $SSHProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHProxyPort"));
    if($SSHProxyPort==0){$SSHProxyPort="22";}


    $jsrestart=$tpl->framework_buildjs(
        "/ssh2proxy/restart",
        "restart-sshproxy.progress",
        "restart-sshproxy.log",
        "progress-sshd-restart",
        "dialogInstance2.close();LoadAjax('sshproxy-status','$page?sshproxy-status=yes');LoadAjax('sshproxy-settings','$page?sshproxy-settings=yes');"

    );

    $form[]=$tpl->field_interfaces("SSHProxyInterface","nooloopNone:{listen_interface}",$SSHProxyInterface);
    $form[]=$tpl->field_numeric("SSHProxyPort","{listen_port}",$SSHProxyPort);
    echo $tpl->form_outside(null, $form,null,"{apply}",$jsrestart,"AsSystemAdministrator");
    return true;
}
function options_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    admin_tracks_post("Saving SSH proxy settings");
    return true;
}
function hostkey_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $hostname=$_GET["hostkey-js"];
    $tpl->js_dialog2("modal:$hostname: {key}","$page?hostkey-popup=yes&hostname=$hostname&function=$function");
    return true;
}
function hostkey_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $function=$_GET["function"];
    $hostname=$_GET["hostname"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $hostname=$q->SearchAntiXSS($hostname);

    $ligne=$q->mysqli_fetch_array("SELECT privkey FROM sshproxy_keys WHERE hostname='$hostname'");
    $privkey=base64_decode($ligne["privkey"]);
    $jsafter="dialogInstance2.close();$function();";
    $form[]=$tpl->field_hidden("hostname",$hostname);
    $form[]=$tpl->field_textareacode("privkey",null,$privkey);
    echo $tpl->form_outside($hostname, $form,"{sshproxy_keys}","{apply}",$jsafter,"AsSystemAdministrator");
    return true;
}
function hostkey_safe():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $privkey=$_POST["privkey"];
    $hostname=$_POST["hostname"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $privkey=base64_encode($q->SearchAntiXSS($privkey));
    $hostname=$q->SearchAntiXSS($hostname);
    $q->QUERY_SQL("DELETE FROM sshproxy_keys WHERE  hostname='$hostname'");
    $q->QUERY_SQL("INSERT INTO sshproxy_keys (privkey ,hostname) VALUES ('$privkey','$hostname')");

    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    admin_tracks("Add SSH Proxy Key for $hostname");
    return true;
}
function forward_delete():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $username=$_GET["delete-js"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $q->QUERY_SQL("DELETE FROM sshproxy WHERE username='$username'");
    admin_tracks("Remove SSH Proxy for $username");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();";
    return true;
}

function forward_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $tpl->CLEAN_GET_XSS();
    $user=$_GET["user"];
    $btn="{apply}";
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sshproxy WHERE username='$user'");

    if($user<>null){
        $title=$user;
        $form[]=$tpl->field_hidden("username",$user);
    }else{
        $title="{new_rule}";
        $form[]=$tpl->field_text("username","{username}",null,true);
        $btn="{add}";
        $ligne["port"]=22;
    }
    $jsafter="dialogInstance2.close();$function();";
    $form[]=$tpl->field_text("hostname","{APP_OPENSSH}",$ligne["hostname"]);
    $form[]=$tpl->field_numeric("port","{APP_OPENSSH} {listen_port}",$ligne["port"]);
    echo $tpl->form_outside($title, $form,null,$btn,$jsafter,"AsSystemAdministrator");
    return true;
}
function forward_save():bool{
    $tpl=new template_admin();
    if(intval($_POST["port"])==0){$_POST["port"]=22;}
    $array=$tpl->CLEAN_POST_XSS();
    $username=$_POST["username"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $q->QUERY_SQL("DELETE FROM sshproxy WHERE username='$username'");


    $sql="INSERT INTO sshproxy (".@implode(",",$array["FIELDS_ADD"]).") VALUES (".@implode(",",$array["VALUES_ADD"]).")";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }

    admin_tracks_post("Create SSH Proxy account");
    return true;
}
function forward_switch():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $username=$_GET["switch-js"];
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM sshproxy WHERE username='$username'");
    if($ligne["enabled"]==1){
        $q->QUERY_SQL("UPDATE sshproxy SET enabled=0 WHERE username='$username'");
        admin_tracks("Set SSH Proxy $username as disbaled");

    }else{
        $q->QUERY_SQL("UPDATE sshproxy SET enabled=1 WHERE username='$username'");
        admin_tracks("Set SSH Proxy $username as enabled");
    }
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return false;}
    return true;
    
}

function forward_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $function=$_GET["function"];
    $t=time();
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");

    $sql="CREATE TABLE IF NOT EXISTS sshproxy ( 
    `username` TEXT NOT NULL PRIMARY KEY,
    `hostname` TEXT NOT NULL,
    `port` INTEGER NOT NULL DEFAULT 22,
    `enabled` INTEGER NOT NULL DEFAULT 1 )";
    $q->QUERY_SQL($sql);

    $sql="CREATE TABLE IF NOT EXISTS sshproxy_keys ( 
    `hostname` TEXT NOT NULL PRIMARY KEY,
    `privkey` TEXT NOT NULL )";
    $q->QUERY_SQL($sql);

    $search=$q->PatternToSearch($_GET["search"]);
    $TRCLASS=null;
    $sql="SELECT * FROM sshproxy WHERE ( (username LIKE '$search') OR ( hostname LIKE '$search') ) LIMIT 250";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{username}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{destination}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{key}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{active2}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($results as $index=>$ligne) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $md=md5(serialize($ligne));
        $username=$ligne["username"];
        $hostname=$ligne["hostname"];
        $port=$ligne["port"];
        $js="Loadjs('$page?popup-js=yes&user=$username&function=$function&index=$index')";
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?switch-js=$username')");
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$username&md=$md')");

        $ligne2=$q->mysqli_fetch_array("SELECT privkey FROM sshproxy_keys WHERE hostname='$hostname'");
        if(!isset($ligne2["privkey"])){
            $ligne2["privkey"]="";
        }
        $js="OnClick=\"javascript:Loadjs('$page?hostkey-js=$hostname&function=$function');\"";
        if(strlen((string) $ligne2["privkey"])<50){
            $hostkey="<button class='btn btn-danger btn-xs' $js>{not_saved}</button>";
        }else{
            $hostkey="<button class='btn btn-primary btn-xs' $js>{saved}</button>";
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap><strong><i class='fa-solid fa-user-large'></i>&nbsp;$username</strong></td>";
        $html[]="<td style='width:1%' nowrap><i class='fa-regular fa-arrow-right-to-bracket fa-2x'></i></td>";
        $html[]="<td style='width:99%' nowrap><a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold;' nowrap><i
 class='fa-solid fa-server'></i>&nbsp;$hostname:$port</span></td>";

        $html[]="<td class='center' style='width:1%'>$hostkey</td>";
        $html[]="<td class='center' style='width:1%'>$enabled</td>";
        $html[]="<td class='center' style='width:1%'>$delete</td>";
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
    $html[]="<script>";

    $add="Loadjs('$page?popup-js=yes&username=&function=$function')";

    $jsrestart=$tpl->framework_buildjs(
        "/ssh2proxy/restart",
        "restart-sshproxy.progress",
        "restart-sshproxy.log",
        "progress-sshd-restart",
        "$function();"

    );
    $jshelp="s_PopUpFull('https://wiki.articatech.com/ssh/sshproxy',1024,768,'SSH Proxy')";

    $topbuttons[]=array($add,ico_plus,"{new_rule}");
    $topbuttons[]=array($jsrestart,ico_retweet,"{apply}");
    $topbuttons[]=array($jshelp,"fas fa-question-circle","Wiki");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{APP_SSHPROXY}: {forward_rules}";
    $TINY_ARRAY["ICO"]=ico_terminal;
    $TINY_ARRAY["EXPL"]="{APP_SSHPROXY_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshproxy";

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=$jstiny;
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px' nowrap>";
    $html[]="<div id='sshproxy-status'></div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:99%;padding-left:20px'>";
    $html[]="<div id='sshproxy-settings'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $refresh=$tpl->RefreshInterval_js("sshproxy-status",$page,"sshproxy-status=yes");
    $html[]="function RefreshSSHProxyStatus(){";
    $html[]="LoadAjax('sshproxy-settings','$page?sshproxy-settings=yes');";
    $html[]="}";
    $html[]="RefreshSSHProxyStatus()";
    $html[]=$refresh;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function service_settings():bool{
    $TRCLASS=null;
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $SSHProxyInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHProxyInterface"));
    $SSHProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHProxyPort"));
    if($SSHProxyPort==0){$SSHProxyPort="22";}

    $INT[]="<table style='width:100%;margin-top:20px'>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $edit=$tpl->icon_parameters("Loadjs('$page?options-js=yes&domain=".microtime()."&function=RefreshSSHProxyStatus')","AsDnsAdministrator");

    if($SSHProxyInterface==null){
        $ListenInterface_text="{all}";
    }else{
        $nic=new system_nic($SSHProxyInterface);
        $ListenInterface_text=$nic->NICNAME." ($nic->IPADDR)";
    }

    $INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
    $INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-solid fa-ethernet fa-2x'></i></td>";
    $INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>{listen_interface}:</td>";
    $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$ListenInterface_text</strong></td>";
    $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
    $INT[] = "</tr>";
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $edit=$tpl->icon_parameters("Loadjs('$page?options-js=yes&domain=".microtime()."&function=RefreshSSHProxyStatus')","AsDnsAdministrator");
    $INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
    $INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-solid fa-ethernet fa-2x'></i></td>";
    $INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>{listen_port}:</td>";
    $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$SSHProxyPort</strong></td>";
    $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
    $INT[] = "</tr>";



    $INT[] ="</table>";
    echo $tpl->_ENGINE_parse_body($INT);
    return true;


}