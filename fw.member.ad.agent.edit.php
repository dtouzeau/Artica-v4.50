<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.ad-agent.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["profile"])){profile();exit;}
if(isset($_GET["profile-dump"])){profile_dump_js();exit;}
if(isset($_GET["profile-dump-popup"])){profile_dump_popup();exit;}

if(isset($_POST["NewGroup"])){new_group_save();exit;}
if(isset($_POST["uid"])){profile_save();exit;}
if(isset($_GET["groups"])){groups();exit;}
if(isset($_GET["groups-table"])){groups_table();exit;}
if(isset($_GET["new-group-js"])){new_group_js();exit;}
if(isset($_GET["new-group-popup"])){new_group_popup();exit;}

if(isset($_GET["link-group-js"])){link_group_js();exit;}
if(isset($_GET["link-group-popup"])){link_group_popup();exit;}
if(isset($_GET["link-group-table"])){link_group_table();exit;}
if(isset($_GET["link-group-perform"])){link_group_perform();exit;}
if(isset($_GET["unlink-js"])){group_js_unlink();exit;}
js();

function profile_dump_js(){
    $page           = CurrentPageName();
    $dn             = $_GET["profile-dump"];

    $DisplayName=$_GET["name"];
    $tpl=new template_admin();
    $dn=urlencode($dn);
    $tpl->js_dialog5($DisplayName, "$page?profile-dump-popup=$dn");

}

function urlExtension():string{
    $urlExtension               = "";
    if(isset($_GET["connection-id"])){
        $ActiveDirectoryIndex=intval($_GET["connection-id"]);
        $urlExtension="&connection-id=$ActiveDirectoryIndex";
    }
    if(isset($_GET["function"])){
        $urlExtension=$urlExtension."&function={$_GET["function"]}";
    }

    return $urlExtension;

}

function ActiveDirectoryIndex(){
    $ActiveDirectoryIndex       = "None";
    if(isset($_GET["connection-id"])){
        $ActiveDirectoryIndex=intval($_GET["connection-id"]);
    }
    return $ActiveDirectoryIndex;
}

function js(){
    $page           = CurrentPageName();
    $dn             = $_GET["dn"];

    $DisplayName=$_GET["name"];
    $tpl=new template_admin();
    $dn=urlencode($dn);
    $DisplayNameEnc=urlencode($DisplayName);
    $tpl->js_dialog4($DisplayName, "$page?tabs=$dn&name=$DisplayNameEnc");
}
function new_group_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $uidenc=null;
    $ou=$_GET["ou"];
    $ouenc=urlencode($ou);
    $uid=$_GET["uid"];
    if($uid<>null){
        $uidenc=urlencode($uid);
        $uid_title=" &raquo; {member} $uid";
    }
    $tpl->js_dialog5("{new_group} &raquo; {organization} $ou{$uid_title}","$page?new-group-popup=yes&ou=$ouenc&uid=$uidenc");
}

function link_group_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $uidenc=null;
    $ou=$_GET["ou"];
    $ouenc=urlencode($ou);
    $uid=$_GET["uid"];
    if($uid<>null){
        $uidenc=urlencode($uid);
        $uid_title=" &raquo; {member} $uid";
    }
    $tpl->js_dialog5("{link_group} &raquo; {organization} $ou{$uid_title}","$page?link-group-popup=yes&ou=$ouenc&uid=$uidenc");

}
function link_group_perform(){
    header("content-type: application/x-javascript");
    $gpid=$_GET["link-group-perform"];
    $md=$_GET["md"];
    $uid=$_GET["uid"];
    $page=CurrentPageName();
    $uidenc=urlencode($uid);
    $ldap=new clladp();
    if($ldap->AddUserToGroup($gpid, $uid)){
        $js[]="$('#$md').remove();";
        $js[]="if(document.getElementById('table-my-userid-groups')){ LoadAjaxTiny('user-groups-table','$page?groups-table=$uidenc');}";
        $js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
        echo @implode("\n", $js);
    }

}

function group_js_unlink(){
    $gpid=$_GET["unlink-js"];
    $md=$_GET["md"];
    $uid=$_GET["uid"];
    $ldap=new clladp();
    if($ldap->GroupDeleteUser($gpid, $uid)){
        $js[]="$('#$md').remove();";
        $js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
        echo @implode("\n", $js);
    }

}

function link_group_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ou=$_GET["ou"];
    $uid=$_GET["uid"];
    $uidenc=urlencode($uid);
    $group = new groups ( );
    $hash_group = $group->list_of_groups ( $ou, 1 );
    $users = new user($uid);
    $usergroups = $users->Groups_list();



    $html[]=$tpl->_ENGINE_parse_body("
<table id='table-link-userid-groups' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groupname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($hash_group as $gpid=>$GroupName){
        if(isset($usergroups[$gpid])){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md5=md5($gpid);
        $html[]="<tr class='$TRCLASS' id='$md5'>";
        $html[]="<td>$GroupName</td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->icon_select("Loadjs('$page?link-group-perform=$gpid&uid=$uidenc&md=$md5')","AllowAddGroup") ."</td>";
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
    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-link-userid-groups').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function link_group_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $uidenc=null;
    $ou=$_GET["ou"];
    $ouenc=urlencode($ou);
    $uid=$_GET["uid"];
    $uidenc=urlencode($uid);

    echo "<div id='link-group-to-user'></div>
	<script>LoadAjaxTiny('link-group-to-user','$page?link-group-table=yes&ou=$ouenc&uid=$uidenc');</script>
			
	";


}

function new_group_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if($_GET["uid"]<>null){
        $uid_title=" {member} &raquo; {$_GET["uid"]}";
        $uidenc=urlencode($_GET["uid"]);}
    $form[]=$tpl->field_info("ou", "{organization}", $_GET["ou"]);
    if($_GET["uid"]<>null){$form[]=$tpl->field_info("uid", "{member}", $_GET["uid"]);}
    $form[]=$tpl->field_text("NewGroup", "{groupname}", null,true);

    $js[]="if(document.getElementById('table-my-userid-groups')){ LoadAjaxTiny('user-groups-table','$page?groups-table=$uidenc');}";
    $js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
    $js[]="dialogInstance5.close();";
    $jsafter=@implode("", $js);
    $html[]=$tpl->form_outside("{new_group}", @implode("\n", $form),null,"{add}",$jsafter,"AllowAddGroup");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function new_group_save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $group=$_POST["NewGroup"];
    $ou=$_POST["ou"];
    $uid=$_POST["uid"];

    if($ou==null){if($_SESSION["ou"]<>null){$ou=$_SESSION["ou"];}}
    $ldap=new clladp();
    $groupClass=new groups();
    $list=$groupClass->samba_group_list();

    if(is_array($list)){
        foreach ($list as $num=>$ligne){
            if(trim(strtolower($ligne))==trim(strtolower($group))){
                $tpl=new templates();
                echo $tpl->_ENGINE_parse_body('{no_samba_group_in_ou}');
                return;
            }
        }
    }

    if(!$ldap->AddGroup($group,$ou)){echo $ldap->ldap_last_error;}

    if($uid<>null){
        $generated_id=$ldap->GroupIDFromName($ou, $group);

        writelogs("Link gpid $generated_id to $uid",__FUNCTION__,__FILE__,__LINE__);
        $group=new groups($generated_id);
        if(!$group->AddUsertoThisGroup($uid)){
            echo "Failed to add user $uid into $ldap->generated_id....\n";
        }

    }


}

function tabs(){
    $dn=$_GET["tabs"];
    $dnenc=urlencode($_GET["tabs"]);
    $DisplayName=$_GET["name"];
    $page=CurrentPageName();
    $tpl=new template_admin();

    $array[$DisplayName]="$page?profile=$dnenc";
    $array["{groups2}"]="$page?groups=$dnenc";
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));
    if($LighttpdArticaClientAuth==1){
        $sarray["uid"]=$DisplayName;
        $sarray["type"]="AD";
        $sarray["dn"]=$dn;
        $sarray["displayname"]=$DisplayName;
        $sdata=urlencode(base64_encode(serialize($sarray)));
        $array["{client_certificate}"]="fw.member.client.cert.php?sdata=$sdata";
    }
    echo $tpl->tabs_default($array);
}

function profile_dump_popup(){
    $dn=$_GET["profile-dump-popup"];
    $dnenc=urlencode($dn);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $encodedval=urlencode($dn);
    $adagent=new ADAgent();

    $admemberattr=$adagent->getAttrOf("$encodedval");
    $array = json_decode($admemberattr, TRUE);

    $html[]="<table>";
    foreach ($array[0]["data"] as $index=>$minz){
        if($index=="count"){continue;}
        if($index=="dn"){continue;}
        if($index=="objectsid"){continue;}
        if($index=="objectguid"){continue;}
        if(is_numeric($index)){continue;}
        $html[]="<tr>";

        $html[]="<td align='right' valign='top'><strong>$index:</strong></td>";
        $html[]="<td valign='top'>";
        if(is_array($minz)){
            foreach ($minz as $k=>$v){
                $html[]="<div style='margin-left:15px'>{$v}</div>";
            }
        }
        $txt=htmlspecialchars($minz);
        $html[]="<div style='margin-left:15px'>{$txt}</div>";

        $html[]="</td>";
        $html[]="</tr>";



    }
    $html[]="</table>";
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
}

function profile(){
    $dn=$_GET["profile"];
    $dnenc=urlencode($dn);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $encodedval=urlencode($dn);
    $adagent=new ADAgent();

    $admemberattr=$adagent->getAttrOf("$encodedval");
    $array = json_decode($admemberattr, TRUE);


    $UserAccountControl[1]="useraccountcontrol_script";
    $UserAccountControl[2]="ACCOUNTDISABLE";
    $UserAccountControl[8]="HOMEDIR_REQUIRED";
    $UserAccountControl[32]="PASSWD_NOTREQD";
    $UserAccountControl[64]="PASSWD_CANT_CHANGE";
    $UserAccountControl[128]="ENCRYPTED_TEXT_PWD_ALLOWED";
    $UserAccountControl[256]="TEMP_DUPLICATE_ACCOUNT";
    $UserAccountControl[512]="NORMAL_ACCOUNT";
    $UserAccountControl[2048]="INTERDOMAIN_TRUST_ACCOUNT";
    $UserAccountControl[4096]="WORKSTATION_TRUST_ACCOUNT";
    $UserAccountControl[8192]="SERVER_TRUST_ACCOUNT";
    $UserAccountControl[65536]="DONT_EXPIRE_PASSWORD";
    $UserAccountControl[131072]="MNS_LOGON_ACCOUNT";
    $UserAccountControl[262144]="SMARTCARD_REQUIRED";
    $UserAccountControl[524288]="TRUSTED_FOR_DELEGATION";
    $UserAccountControl[1048576]="NOT_DELEGATED";
    $UserAccountControl[2097152]="USE_DES_KEY_ONLY";
    $UserAccountControl[4194304]="DONT_REQ_PREAUTH";
    $UserAccountControl[8388608]="PASSWORD_EXPIRED";
    $UserAccountControl[16777216]="TRUSTED_TO_AUTH_FOR_DELEGATION";
    $UserAccountControl[67108864]="PARTIAL_SECRETS_ACCOUNT";

    $UserAccountControl[805306368]="NORMAL_ACCOUNT";
    $UserAccountControl[805306369]="WORKSTATION_TRUST_ACCOUNT";
    $UserAccountControl[805306370]="INTERDOMAIN_TRUST_ACCOUNT";
    $UserAccountControl[268435456]="SECURITY_GLOBAL_GROUP";
    $UserAccountControl[268435457]="DISTRIBUTION_GROUP";
    $UserAccountControl[536870912]="SECURITY_LOCAL_GROUP";
    $UserAccountControl[536870913]="DISTRIBUTION_LOCAL_GROUP";

    $useraccountcontrol_value=intval($array[0]["data"]["userAccountControl"]);
    $lastlogoff=$tpl->icon_nothing();
    $lastlogon=$tpl->icon_nothing();
    $pwdlastset=$tpl->icon_nothing();
    $lastlogontimestamp=$tpl->icon_nothing();
    if(!isset($array[0]["data"]["lastLogoff"])){$array[0]["data"]["lastLogoff"]=0;}
    if(!isset($array[0]["data"]["lastLogon"])){$array[0]["data"]["lastLogon"]=0;}
    if(!isset($array[0]["data"]["pwdLastSet"])){$array[0]["data"]["pwdLastSet"]=0;}
    if(!isset($array[0]["data"]["accountExpires"])){$array[0]["data"]["accountExpires"]=0;}
    if(!isset($array[0]["data"]["telephoneNumber"])){$array[0]["data"]["telephoneNumber"]=0;}
    if(!isset($array[0]["data"]["mobile"])){$array[0]["data"]["mobile"]=0;}
    if(!isset($array[0]["data"]["description"])){$array[0]["data"]["description"]=0;}


    if(intval($array[0]["data"]["lastLogoff"])>0){
        $lastlogoff=convertWindowsTimestamp($array[0]["data"]["lastLogoff"]);
        $lastlogoff=$lastlogoff.")".$tpl->time_to_date($lastlogoff)." <small>(".distanceOfTimeInWords($lastlogoff,time()).")</small>";
    }
    if(intval($array[0]["data"]["lastLogon"])>0){
        $lastlogon=convertWindowsTimestamp($array[0]["data"]["lastLogon"]);
        $lastlogon=$tpl->time_to_date($lastlogon)." <small>(".distanceOfTimeInWords($lastlogon,time())."</small>)";

        if(intval($array[0]["data"]["lastLogonTimestamp"])>0) {
            $lastlogontimestamp = convertWindowsTimestamp($array[0]["data"]["lastLogonTimestamp"]);
            $lastlogontimestamp = $tpl->time_to_date($lastlogontimestamp) . " <small>(" . distanceOfTimeInWords($lastlogontimestamp, time()) . "</small>)";
        }

    }
    if(intval($array[0]["data"]["pwdLastSet"])>0){
        $pwdlastset=convertWindowsTimestamp($array[0]["data"]["pwdLastSet"]);
        $lastlogon=$tpl->time_to_date($lastlogon)." <small>(".distanceOfTimeInWords($lastlogon,time())."</small>)";
        $pwdlastset=$tpl->time_to_date($pwdlastset)." <small>(".distanceOfTimeInWords($pwdlastset,time())."</small>)";
    }




    $accountexpires=convertWindowsTimestamp($array[0]["data"]["accountExpires"]);
    $samaccounttype=intval($array[0]["data"]["sAMAccountType"]);
    $accountexpires=$tpl->time_to_date($accountexpires)." <small>(".distanceOfTimeInWords($accountexpires,time())."</small>)";




    $form[]=$tpl->field_section($array[0]["data"]["sAMAccountName"].":ico=fad fa-user");
    $form[]=$tpl->field_info("{CommonName}","{CommonName}", $array[0]["data"]["cn"]);
    $form[]=$tpl->field_info("{givenname}","{givenname}", $array[0]["data"]["givenName"]);
    $form[]=$tpl->field_info("{name}","{name}", $array[0]["data"]["name"]);
    $form[]=$tpl->field_info("{samaccountname}","{member}", $array[0]["data"]["sAMAccountName"]);
    $form[]=$tpl->field_info("{telephoneNumber}","{telephoneNumber}", $array[0]["data"]["telephoneNumber"]);
    $form[]=$tpl->field_info("{mobile}","{mobile}", $array[0]["data"]["mobile"]);
    $form[]=$tpl->field_info("{description}","{description}", $array[0]["data"]["description"]);
    $form[]=$tpl->field_section("{options}:ico=fad fa-user-cog");


    $form[]=$tpl->field_info("{DN}","{DN}", $dn);
    $form[]=$tpl->field_info("{uid}","{uid}", $array[0]["data"]["sAMAccountName"]);
    $form[]=$tpl->field_info("{lastlogon}","{lastlogon}", $lastlogon);
    $form[]=$tpl->field_info("{lastlogontimestamp}","{lastlogon} ({replicate})", $lastlogontimestamp);
    $form[]=$tpl->field_info("{lastlogoff}","{lastlogoff}", $lastlogoff);
    $form[]=$tpl->field_info("{pwdlastset}","{pwdlastset}", $pwdlastset);
    $form[]=$tpl->field_info("{accountexpires}","{expire}", $accountexpires);


    if(isset($UserAccountControl[$useraccountcontrol_value])) {
        $form[]=$tpl->field_info("{useraccountcontrol}","{useraccountcontrol}","{{$UserAccountControl[$useraccountcontrol_value]}}");
    }


    if(isset($UserAccountControl[$samaccounttype])) {
        $form[] = $tpl->field_info("{type}", "{type}", "{{$UserAccountControl[$samaccounttype]}}");
    }

    $tpl->form_add_button("{more}","Loadjs('$page?profile-dump=$dnenc&name={$array[0]["data"]["name"]}')");
    echo "<p>&nbsp;</p>".$tpl->form_outside(null, @implode("\n", $form),null,null,"blur()","AllowAddUsers");
}
function convertWindowsTimestamp($wintime) {
    return $wintime / 10000000 - 11644477200;
}

function groups(){
    $uid=urlencode($_GET["groups"]);
    $page=CurrentPageName();
    echo "<div id='user-groups-table'></div>
	<script>LoadAjaxTiny('user-groups-table','$page?groups-table=$uid');</script>		
	";

}

function groups_table(){
    $dn=$_GET["groups-table"];
    $uid=urlencode($dn);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md5=md5($dn);

    $html[]=$tpl->_ENGINE_parse_body("
<table id='table-membersGroupof-$md5' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";


    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{displayname}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $encodedval=urlencode($dn);

    $adagent=new ADAgent();

    $admemberattr=$adagent->getAttrOf("$encodedval");
    $array = json_decode($admemberattr, TRUE);


    $TRCLASS=null;

    foreach ($array[0]["data"]["memberOf"] as $dn=>$GroupName){
        $text_class=null;
        $md=md5($dn.$GroupName);
        $DisplayName=explode(",", $GroupName);
        $DisplayName=str_replace("CN=", "", $DisplayName[0]);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'><i class='fad fa-users'></i></td>";
        $html[]="<td width='99%'>". $tpl->td_href($DisplayName,"{click_to_edit}","Loadjs('fw.groups.ad.agent.php?dn=$GroupName&group_name=$DisplayName')")."</td>";
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
    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-membersGroupof-$md5').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


