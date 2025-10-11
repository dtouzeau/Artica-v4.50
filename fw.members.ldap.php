<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["none"])){exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["new-js"])){new_js();exit;}
if(isset($_GET["new-popup"])){new_popup();exit;}
if(isset($_GET["new-organization"])){new_organization();exit;}
if(isset($_GET["new-group"])){new_group();exit;}
if(isset($_POST["AddOU"])){new_organization_save();exit;}
if(isset($_POST["AddGroup"])){new_group_save();exit;}
if(isset($_GET["new-member"])){new_member();exit;}
if(isset($_POST["login"])){new_member_save();exit;}
if(isset($_GET["import-js"])){import_member_js();exit;}
if(isset($_GET["import-popup"])){import_member_popup();exit;}
if(isset($_GET["import-organization"])){import_member_organization();exit;}
if(isset($_GET["import-group"])){import_group();exit;}
if(isset($_GET["import-member-final"])){import_members_final();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["delete-user-js"])){delete_user_js();exit;}
if(isset($_POST["delete-user"])){delete_user();exit;}

if(isset($_GET["delete-group-ask"])){delete_group_ask();exit;}
if(isset($_POST["delete-group"])){delete_group_perform();exit;}

if(isset($_GET["export-js"])){export_js();exit;}
if(isset($_GET["export-confirm"])){export_confirm();exit;}
if(isset($_GET["export-popup"])){export_popup();exit;}
if(isset($_GET["export-final"])){export_final();exit;}
if(isset($_GET["filter-js"])){filter_js();exit;}
page();

function new_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isset($_GET["function"])){$_GET["function"]=null;}
    $tpl->js_dialog1("{new_member}", "$page?new-popup=yes&function={$_GET["function"]}");
}
function export_confirm(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{export} {members}", "$page?export-popup=yes",550);

}
function export_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_execute("{export} {members}","none","none","Loadjs('$page?export-confirm=yes')");
}
function export_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<H2>{export} {members}</H2>";
    $html[]="<div id='export-ldap-member-progress'></div>";
    $html[]="<div id='export-ldap-member-final'></div>";

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ldap.import.members";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ldap.import.members.txt";
    $ARRAY["CMD"]="openldap.php?export-members=yes";
    $ARRAY["TITLE"]="{exporting} {members}";
    $ARRAY["AFTER"]="LoadAjax('export-ldap-member-final','$page?export-final=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=export-ldap-member-progress')";
    $html[]="<script>$jsrestart;</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function delete_group_perform(){
    $gid        = intval($_POST["delete-group"]);
    $grp        = new groups($gid);

    if(!$grp->Delete()){
        echo $grp->ldap_error;
        return;
    }

    return true;
}

function delete_group_ask(){
    $tpl        = new template_admin();
    $gid        = intval($_GET["delete-group-ask"]);
    $grp        = new groups($gid);
    $GroupName  = $grp->groupName;
    $md         = $_GET["md"];


    $tpl->js_confirm_delete($GroupName,"delete-group",$gid,"$('#$md').remove()");
    return true;
}

function export_final(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $file_path=dirname(__FILE__)."/ressources/logs/ldap_members.gz";

    if(!is_file($file_path)){
        $html[]="
                <div class=\"widget style1 red-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-exclamation fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {failed} </span>
                            <h2 class=\"font-bold\">ldap_members.gz Error!</h2>
                        </div>
                    </div>
                </div>
            ";

        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    //<i class="fas fa-exclamation"></i>
    // <i class="fas fa-file-archive"></i>
    $filesize=FormatBytes(@filesize($file_path)/1024);

    $html[]="<a href='ressources/logs/ldap_members.gz'>
                <div class=\"widget style1 navy-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-file-archive fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {members} </span>
                            <h2 class=\"font-bold\">ldap_members.gz <small style='color:white'>($filesize)</small></h2>
                        </div>
                    </div>
                </div></a>
            ";

    echo $tpl->_ENGINE_parse_body($html);
    return false;
}

function delete_user_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $uid=$_GET["delete-user-js"];
    $md=$_GET["md"];
    $tpl->js_confirm_delete($uid,"delete-user",$uid,"$('#$md').remove()");
}
function delete_user(){

    $users=new user($_POST["delete-user"]);
    if(!$users->DeleteUser()){
        echo "Failed to delete {$_POST["delete-user"]}";
    }

}

function import_member_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{importing_form_text_file}", "$page?import-popup=yes");
}
function import_member_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $def="LoadAjaxTiny('import-user-div','$page?import-organization=yes')";
    $EnableMultipleOrganizations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));
    if($EnableMultipleOrganizations==0) {
        $ldap = new clladp();
        $hash = $ldap->hash_get_ou(false);
        if (count($hash) > 0) {
            $def="LoadAjaxTiny('import-user-div','$page?import-group=yes');";
            $_SESSION["ADD_USER_OU"]=$hash[0];
        }

    }

    $html="
	<div id='import-user-progress'></div>		
	<div id='import-user-div'></div>
	<script>$def</script>		
	";

    echo $html;

}
function import_member_organization(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    $hash=$ldap->hash_get_ou(true);


    $form[]=$tpl->field_text("AddOU", "{create_a_new_organization}", null,false);



    if(count($hash)==0){
        echo $tpl->form_outside("{new_member} &raquo; {create_a_new_organization}", @implode("\n", $form),"{add_new_organisation_text}","{add}",
            "LoadAjaxTiny('import-user-div','$page?import-group=yes');","AllowAddUsers");
        return;
    }



    $form[]=$tpl->field_array_hash($hash, "ChooseOU", "{organization}", $_SESSION["ADD_USER_OU"],false);
    echo $tpl->form_outside("{import} &raquo; {your_organization}", @implode("\n", $form),null,"{next}",
        "LoadAjaxTiny('import-user-div','$page?import-group=yes');","AllowAddUsers");

}

function new_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $def="LoadAjaxTiny('create-user-div','$page?new-organization=yes')";
    $EnableMultipleOrganizations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));

    if($EnableMultipleOrganizations==0) {
        $ldap = new clladp();
        $hash = $ldap->hash_get_ou(false);
        if (count($hash) > 0) {
            $def="LoadAjaxTiny('create-user-div','$page?new-group=yes&function={$_GET["function"]}');";
            $_SESSION["ADD_USER_OU"]=$hash[0];
        }

    }


    $html="
	<div id='create-user-progress'></div>		
	<div id='create-user-div'></div>
	<script>$def</script>		
	";

    echo $html;
}
function new_organization():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    $hash=$ldap->hash_get_ou(true);
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $form[]=$tpl->field_text("AddOU", "{create_a_new_organization}", null);
    if(count($hash)==0){
        echo $tpl->form_outside("{new_member} &raquo; {create_a_new_organization}", @implode("\n", $form),"{add_new_organisation_text}","{add}",
            "LoadAjaxTiny('create-user-div','$page?new-group=yes&function=$function');","AllowAddUsers");
        return true;
    }
    $form[]=$tpl->field_array_hash($hash, "ChooseOU", "{organization}", $_SESSION["ADD_USER_OU"]);
    echo $tpl->form_outside("{new_member} &raquo; {your_organization}", @implode("\n", $form),null,"{next}",
        "LoadAjaxTiny('create-user-div','$page?new-group=yes&function=$function');","AllowAddUsers");

    return true;
}
function import_group(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    $form[]=$tpl->field_info("ou", "{organization}", $_SESSION["ADD_USER_OU"]);

    $hash_groups=$ldap->hash_groups($_SESSION["ADD_USER_OU"],1);


    $form[]=$tpl->field_text("AddGroup", "{new_group}", null,false);
    $form[]=$tpl->field_array_hash($hash_groups, "ChooseGroup", "{group2}", $_SESSION["ADD_USER_GROUP"],false);

    echo $tpl->form_outside("{import} &raquo; {$_SESSION["ADD_USER_OU"]} &raquo; {group2}", @implode("\n", $form),"{LDAP_MEMBER_IMPOURT_GROUP_EXP}","{next}",
        "LoadAjaxTiny('import-user-div','$page?import-member-final=yes');","AllowAddUsers");

}

function import_members_final(){
    $EnableVirtualDomainsInMailBoxes=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    $hash_groups=$ldap->hash_groups($_SESSION["ADD_USER_OU"],1);
    if(!is_numeric($_SESSION["ADD_USER_GROUP"])){
        foreach ($hash_groups as $id=>$xname){
            if(trim(strtolower($xname))==trim(strtolower($_SESSION["ADD_USER_GROUP"]))){
                $gpid=$id;
                break;
            }
        }

    }else{
        $gpid=intval($_SESSION["ADD_USER_GROUP"]);
    }
    $ou=urlencode($_SESSION["ADD_USER_OU"]);

    $addparams="&ou=$ou&gpid=$gpid";
    if($gpid>0) {
        $html[] = "<H2>{import} {$_SESSION["ADD_USER_OU"]}/{$hash_groups[$gpid]}</H2>";
        $html[] = "<div class='alert alert-info'>{importuser_text}<br>{importuser_aliases_text}</div>";
    }else{
        $html[] = "<H2>{import} {$_SESSION["ADD_USER_OU"]}</H2>";
        $html[] = "<div class='alert alert-info'>{imporldaptuser_text2}</div>";
    }
    $html[]="<div class='center'>". $tpl->button_upload("{upload}",$page,null,$addparams)."</div>";
    echo $tpl->_ENGINE_parse_body($html);


}



function new_group():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    if(!isset($_SESSION["ADD_USER_GROUP"])){
        $_SESSION["ADD_USER_GROUP"]=0;
    }
    if(!isset($_SESSION["ADD_USER_DOMAIN"])){
        $_SESSION["ADD_USER_DOMAIN"]="";
    }
    if(!isset($_SESSION["ADD_USER_OU"])){
        $_SESSION["ADD_USER_OU"]="";
    }

    $form[]=$tpl->field_info("ou", "{organization}", $_SESSION["ADD_USER_OU"]);

    $hash_groups=$ldap->hash_groups($_SESSION["ADD_USER_OU"],1);
    $hash_domains=$ldap->hash_get_domains_ou($_SESSION["ADD_USER_OU"]);

    $form[]=$tpl->field_text("AddGroup", "{new_group}", null);
    $form[]=$tpl->field_array_hash($hash_groups, "ChooseGroup", "{group2}", $_SESSION["ADD_USER_GROUP"]);
    if(count($hash_domains)>0) {
        $form[] = $tpl->field_array_hash($hash_domains, "ChooseDomain", "{domain}", $_SESSION["ADD_USER_DOMAIN"]);
    }else{
        $form[] = $tpl->field_hidden("ChooseDomain","");
    }
    echo $tpl->form_outside("{new_member} &raquo; {$_SESSION["ADD_USER_OU"]} &raquo; {group2}", $form,null,"{next}",
        "LoadAjaxTiny('create-user-div','$page?new-member=yes&function={$_GET["function"]}');","AllowAddUsers");
    return true;
}

function new_member(){
    $EnableVirtualDomainsInMailBoxes=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes");
    $tpl=new template_admin();
    $ldap=new clladp();
    $hash_groups=$ldap->hash_groups($_SESSION["ADD_USER_OU"],1);
    if(!is_numeric($_SESSION["ADD_USER_GROUP"])){
        foreach ($hash_groups as $id=>$xname){
            if(trim(strtolower($xname))==trim(strtolower($_SESSION["ADD_USER_GROUP"]))){
                $gpid=$id;
                break;
            }
        }

    }else{
        $gpid=$_SESSION["ADD_USER_GROUP"];
    }

    //$WarninEnableVirtualDomainsInMailBoxes=null;
    $form[]=$tpl->field_hidden("gpid", $gpid);
    $form[]=$tpl->field_info("ou", "{organization}", $_SESSION["ADD_USER_OU"]);
    $form[]=$tpl->field_info("GroupName", "{group2}", $hash_groups[$gpid]);
    if(strlen($_SESSION["ADD_USER_DOMAIN"])>2) {
        $form[] = $tpl->field_info("internet_domain", "{domain}", $_SESSION["ADD_USER_DOMAIN"]);
    }
    $form[]=$tpl->field_text("firstname", "{firstname}", null,true);
    $form[]=$tpl->field_text("lastname", "{lastname}", null,true);
    $form[]=$tpl->field_email("email", "{email}", null,true);
    if($EnableVirtualDomainsInMailBoxes==0){
        $form[]=$tpl->field_text("login", "{uid}", null,true);
    }else{
        //$WarninEnableVirtualDomainsInMailBoxes="{WarninEnableVirtualDomainsInMailBoxes}";
        $form[]=$tpl->field_hidden("login", "");
    }
    $form[]=$tpl->field_password2("password", "{password}", null,true);


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/create-user.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/create-user.progress.txt";
    $ARRAY["CMD"]="system.php?create-user-progress=yes";
    $ARRAY["TITLE"]="{please_wait_creating_member}";
    $ARRAY["AFTER"]="dialogInstance1.close();TableLoaderMyMemberearch()";
    if($_GET["function"]<>null){
        $ARRAY["AFTER"]=$_GET["function"]."();{$ARRAY["AFTER"]}";

    }

    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=create-user-progress')";


    echo $tpl->form_outside("{new_member} &raquo; {$_SESSION["ADD_USER_OU"]} &raquo; {$hash_groups[$gpid]}",
        @implode("\n", $form),null,"{add}",
        "$jsrestart","AllowAddUsers");

}

function new_member_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if($_POST["login"]==null){
        $m=explode("@",$_POST["email"]);
        $_POST["login"]=$m[0];
    }
    $_POST["login"]=$tpl->CLEAN_BAD_XSS($_POST["login"]);
    $_POST["firstname"]=$tpl->CLEAN_BAD_XSS($_POST["firstname"]);
    $_POST["lastname"]=$tpl->CLEAN_BAD_XSS($_POST["lastname"]);
    $_POST["email"]=$tpl->CLEAN_BAD_CHARMAIL($_POST["email"]);

    $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
    $sql="CREATE TABLE IF NOT EXISTS `CreateUserQueue` ( `zMD5` TEXT PRIMARY KEY,`content` TEXT NOT NULL ) ";
    $q->QUERY_SQL($sql);

    if(!$q->ok){echo $q->mysql_error;}
    $fulldata=base64_encode(serialize($_POST));

    $md5=md5($fulldata);
    $fulldata=sqlite_escape_string2($fulldata);
    $q->QUERY_SQL("INSERT OR IGNORE INTO `CreateUserQueue` (`zMD5`,`content`) VALUES ('$md5','$fulldata')");
    if(!$q->ok){echo $q->mysql_error;}
}


function new_group_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $group=$tpl->CLEAN_BAD_XSS($_POST["AddGroup"]);
    $ou=$_POST["ou"];
    if($ou==null){if($_SESSION["ou"]<>null){$ou=$_SESSION["ou"];}}

    $ldap=new clladp();
    include_once(dirname(__FILE__).'/ressources/class.groups.inc');

    $groupClass=new groups();
    $list=$groupClass->samba_group_list();

    if(is_array($list)){
        foreach ($list as $ligne){
            if(trim(strtolower($ligne))==trim(strtolower($group))){
                $tpl=new templates();
                echo $tpl->_ENGINE_parse_body('{no_samba_group_in_ou}');
                exit;
            }
        }
    }

    if($group<>null){
        if(!$ldap->AddGroup($group,$ou)){echo $ldap->ldap_last_error;}
        $_SESSION["ADD_USER_GROUP"]=$ldap->generated_id;
    }else{
        $_SESSION["ADD_USER_GROUP"]=$_POST["ChooseGroup"];
    }



    $_SESSION["ADD_USER_DOMAIN"]=url_decode_special_tool($_POST["ChooseDomain"]);

}


function new_organization_save(){
    $usr=new usersMenus();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if($_POST["AddOU"]<>null){
        $ou=$tpl->CLEAN_BAD_XSS($_POST["AddOU"]);
        if($ou=="_Global"){echo "Reserved!";exit;}
        $ldap=new clladp();
        $ldap->AddOrganization($ou);
        if($ldap->ldap_last_error<>null){
            if($ldap->ldap_last_error_num<>68){
                echo "\n****************************************\nAdding Organization \"{$ou}\"\n********************\n";
                echo $ldap->ldap_last_error;
                return;
            }
        }

        $ldap->ldap_close();
        $sock=new sockets();
        $sock->getFrameWork("status.php?force-front-end=yes");
        return;
    }

    $_SESSION["ADD_USER_OU"]=$_POST["ChooseOU"];


}

function filter_js(){
    $value=intval($_GET["filter-js"]);
    $_SESSION["MEMBERS_SEARCH_FILTER"]=$value;
    $function=$_GET["function"];
    VERBOSE("MEMBERS_SEARCH_FILTER={$_SESSION["MEMBERS_SEARCH_FILTER"]}",__LINE__);
    echo "$function();\n";

}

function page(){
    $tpl=new template_admin();
    if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
    if(!isset($_SESSION["MEMBERS_SEARCH"])){$_SESSION["MEMBERS_SEARCH"]="";}
    if($_SESSION["MEMBERS_SEARCH"]==null){$_SESSION["MEMBERS_SEARCH"]="";}
    $CountOfLDAPMembers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CountOfLDAPMembers"));
    if($CountOfLDAPMembers==0){
        $sock=new sockets();
        $sock->getFrameWork("openldap.php?count-members=yes");
    }

    $filters[0]="{members}";
    $filters[1]="{users_and_groups}";
    $filters[2]="{groups2}";

    if(!isset($_SESSION["MEMBERS_SEARCH_FILTER"])){$_SESSION["MEMBERS_SEARCH_FILTER"]=1;}
    $title=$filters[$_SESSION["MEMBERS_SEARCH_FILTER"]];

    $html=$tpl->page_header("{search} $title","fad fa-users",
        "{search_ldap_members}",
        null,
        "local-ldap-members",
        "progress-firehol-restart",true,
        "table-loader-my-members"
    );




    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {my_members}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ldap=new clladp();
    $t=time();
    $stringtofind=url_decode_special_tool($_GET["search"]);
    $stringtofind="*$stringtofind*";
    $stringtofind=str_replace("**", "*", $stringtofind);
    $stringtofind=str_replace("**", "*", $stringtofind);
    $filter=$_SESSION["MEMBERS_SEARCH_FILTER"];
    $function=$_GET["function"];

    $filters[0]="{members}";
    $filters[1]="{users_and_groups}";
    $filters[2]="{groups2}";

    if(!isset($_SESSION["MEMBERS_SEARCH_FILTER"])){$_SESSION["MEMBERS_SEARCH_FILTER"]=1;}
    $title=$filters[$_SESSION["MEMBERS_SEARCH_FILTER"]];
    $CLUSTER_CLIENT=false;
    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient");
    if($EnableLDAPSyncProv==1){
        if($EnableLDAPSyncProvClient==1){
            $CLUSTER_CLIENT=true;
        }
    }


    $MAIN_HASH=array();
    VERBOSE("Filter=$filter",__LINE__);

    if($filter==1) {
        $MAIN_HASH = $ldap->UserAndGroupSearch(null, $stringtofind, 100);
    }

    if($filter==0) {
        $MAIN_HASH = $ldap->UserAndGroupSearch(null, $stringtofind, 100,false,true);

    }
    if($filter==2) {
        $MAIN_HASH = $ldap->UserAndGroupSearch(null, $stringtofind, 100,true,false);

    }
    //<i class="fas fa-file-export"></i>

    $filters_bts["{users_and_groups}"]="Loadjs('$page?filter-js=1&function=$function');";
    $filters_bts["{members}"]="Loadjs('$page?filter-js=0&function=$function');";
    $filters_bts["{groups2}"]="Loadjs('$page?filter-js=2&function=$function');";


    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom: 15px'>";
    if($filter==1 OR $filter==0) {
        if(!$CLUSTER_CLIENT) {
            $btns[] = $tpl->button_label_table("{new_member}",
                "Loadjs('$page?new-js=yes&function=$function')", "far fa-user-plus", "AllowAddUsers");
        }
    }
    if($filter==1 OR $filter==2) {
        if(!$CLUSTER_CLIENT) {
            $btns[] = $tpl->button_label_table("{import}", "Loadjs('$page?import-js=yes')", "fas fa-file-import", "AllowAddUsers");
        }
        $btns[] = $tpl->button_label_table("{export}", "Loadjs('$page?export-js=yes')", "fas fa-file-export", "AllowAddUsers");
        if(!$CLUSTER_CLIENT) {
            $btns[] = $tpl->button_label_table("{new_group}", "Loadjs('fw.member.ldap.edit.php?new-group-js=yes&function=$function')", "fas fa-users-medical", "AllowAddUsers");
        }
    }
    $btns[]=$tpl->button_dropdown_table("{filter}",$filters_bts,"AllowAddUsers");
    $btns[]="</div>";

    $filterstitle[0]="{members}";
    $filterstitle[1]="{users_and_groups}";
    $filterstitle[2]="{groups2}";

    $title=$filterstitle[$_SESSION["MEMBERS_SEARCH_FILTER"]];
    $TINY_ARRAY["TITLE"]="{search} $title &laquo;$stringtofind&raquo;";
    $TINY_ARRAY["ICO"]="fad fa-users";
    $TINY_ARRAY["EXPL"]="{search_ldap_members}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




    $html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' width=1%>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{displayname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{email}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{phone}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groups2}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $TRCLASS=null;
    if(!$MAIN_HASH){
        $MAIN_HASH=array();
        $MAIN_HASH["count"]=0;
    }


    for($i=0;$i<$MAIN_HASH["count"];$i++){


        $ligne=$MAIN_HASH[$i];
        $id=md5(serialize($ligne));
        $objectclass=$ligne["objectclass"];
        if(in_array("posixGroup", $objectclass)){
            $gidnumber=$ligne["gidnumber"][0];
            $displayname=$ligne["cn"][0];
            $gp=new groups($gidnumber);
            $NumBerOfusers=count($gp->ARRAY_MEMBERS);
            $delete=$tpl->icon_delete("Loadjs('$page?delete-group-ask=$gidnumber&md=$id')","AllowAddGroup");
            if($CLUSTER_CLIENT){$delete=$tpl->icon_delete();}

            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $js="Loadjs('fw.groups.ldap.php?gpid=$gidnumber')";
            $html[]="<tr class='$TRCLASS' id='$id'>";
            $html[]="<td class=\"center\"><div><i class='far fa-users'></i></div></td>";
            $html[]="<td class=\"\">". $tpl->td_href($displayname,"{click_to_edit}",$js)."</td>";
            $html[]="<td class=\"\">". $tpl->icon_nothing()."</td>";
            $html[]="<td class=\"\">". $tpl->icon_nothing()."</td>";
            $html[]="<td class=\"\">$NumBerOfusers {members}</td>";
            $html[]="<td class=\"\" width=1% nowrap>$delete</td>";
            $html[]="</tr>";
            continue;
        }



        if(!isset($ligne["uid"][0])){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $displayname=null;
        $givenname=null;
        $uid=null;
        $email_address=array();
        $telephonenumber=array();
        $GroupsTableau=null;
        $sn=null;
        $gps=array();
        $text_class=null;
        if(strpos($ligne["dn"],"dc=pureftpd,dc=organizations")>0){continue;}
        if(isset($ligne["samaccountname"][0])){$uid=$ligne["samaccountname"][0];}
        if(isset($ligne["userprincipalname"][0])){$email_address[]="<div>{$ligne["userprincipalname"][0]}</div>";}
        if(isset($ligne["telephonenumber"][0])){$telephonenumber[]="<div>{$ligne["telephonenumber"][0]}</div>";}
        if(isset($ligne["mobile"][0])){$telephonenumber[]="<div>{$ligne["mobile"][0]}</div>";}


        if(isset($ligne["givenname"][0])){$givenname=$ligne["givenname"][0];}
        if(isset($ligne["sn"][0])){$sn=$ligne["sn"][0];}

        if($givenname<>null){if($sn<>null){ $displayname=" $givenname $sn"; }}


        $uid=$ligne["uid"][0];
        if(isset($ligne["mail"][0])){$email_address[]="{$ligne["mail"][0]}";}

        $Groups=$ldap->GetUserGroups($uid);

        foreach ($Groups as $GroupDN=>$GroupName){
            if(trim($GroupName)==null){continue;}
            $jsGRP="Loadjs('fw.groups.ldap.php?gpid=".urlencode($GroupDN)."')";
            $gps[]="<div><a href=\"javascript:blur();\" OnClick=\"$jsGRP\" style='text-decoration:underline'>$GroupName</a></div>";
            if(count($gps)>5){$gps[]="...";break;}

        }

        $GroupsTableau=@implode(", ", $gps);
        if($displayname==null){$displayname=trim($ligne["displayname"][0]);}
        if($displayname==null){$displayname=$uid;}
        $uidenc=urlencode($uid);
        $js="Loadjs('fw.member.ldap.edit.php?uid=$uidenc')";

        if(count($telephonenumber)==0){$telephonenumber[]=$tpl->icon_nothing();}
        if(count($email_address)==0){$email_address[]=$tpl->icon_nothing();}

        $delete=$tpl->icon_delete("Loadjs('$page?delete-user-js=$uidenc&md=$id')");
        if($CLUSTER_CLIENT){$delete=$tpl->icon_delete();}


        $html[]="<tr class='$TRCLASS' id='$id'>";
        $html[]="<td class=\"center\"><div><i class='fa fa-user'></i></div></td>";
        $html[]="<td class=\"$text_class\">". $tpl->td_href($displayname,"{click_to_edit}",$js)."</td>";
        $html[]="<td class=\"$text_class\">". $tpl->td_href(@implode("", $email_address),"{click_to_edit}",$js)."</td>";
        $html[]="<td class=\"$text_class\">". @implode("", $telephonenumber)."</td>";
        $html[]="<td class=\"$text_class\">$GroupsTableau</td>";
        $html[]="<td class=\"$text_class\" width=1% nowrap>$delete</td>";
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
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false },\"sorting\": {\"enabled\": true } } ); });
	$headsjs

</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function BuildRequests($stringtofind=null){
    $LIMIT=200;
    $stringtofind=trim(strtolower($stringtofind));
    $stringtofind=str_replace("  ", " ", $stringtofind);
    $stringtofind=str_replace("  ", " ", $stringtofind);
    $stringtofind=str_replace("  ", " ", $stringtofind);
    $ipClass=new IP();
    $fixed=false;

    if(preg_match("#limit\s+([0-9]+)#", $stringtofind,$re)){
        $stringtofind=trim(str_replace("limit {$re[1]}", "", $stringtofind));
        $LIMIT=$re[1];
    }


    if(strpos("  $stringtofind", "+fixed")){
        $fixed=true;
        $stringtofind=str_replace("+fixed", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }




    $ORDERL="DESC";
    $ORDERBY="ORDER BY fullhostname";
    if(strpos("  $stringtofind", "order by time")){
        $ORDERBY ="ORDER BY updated";
        $stringtofind=str_replace("order by time", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }
    if(strpos("  $stringtofind", "order by ip")){
        $ORDERBY ="ORDER BY ipaddr";
        $stringtofind=str_replace("order by ip", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }
    if(strpos("  $stringtofind", "order by name")){
        $ORDERBY ="ORDER BY ipaddr";
        $stringtofind=str_replace("order by name", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }
    if(strpos("  $stringtofind", "order by alias")){
        $ORDERBY ="ORDER BY proxyalias";
        $stringtofind=str_replace("order by alias", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }

    if(strpos("  $stringtofind", "+asc")){
        $ORDERL="ASC";
        $stringtofind=str_replace("+asc", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }
    if(strpos("  $stringtofind", "+desc")){
        $ORDERL="DESC";
        $stringtofind=str_replace("+desc", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }

    if(preg_match("#(.+?)\s+\(#", $stringtofind,$re)){$stringtofind=trim($re[1]);}

    $stringtofind=str_replace("**", "*", $stringtofind);
    $stringtofind2=str_replace("*", "", $stringtofind);
    $stringtofind=str_replace("*", ".*?", $stringtofind);

    $INET=false;
    $MAC=false;
    if($ipClass->isIPAddressOrRange($stringtofind2)){
        $OR[]="(inet '$stringtofind2' = ipaddr )";
        $INET=true;
    }

    if(!$INET){
        if(preg_match("#^[0-9\.]+$#", $stringtofind2)){
            if($stringtofind2<>null){
                $tt=explode(".",$stringtofind2);

                foreach ($tt as $index=>$value){
                    if(strlen( (string) $value)>3){$tt[$index]="0";}
                }

                if(!isset($tt[1])){$tt[1]="0";}
                if(!isset($tt[2])){$tt[2]="0";}
                if(!isset($tt[3])){$tt[3]="0";}



                $tipaddr=@implode(".", $tt);
                if($tipaddr<>"0.0.0.0"){
                    $OR[]="( inet '$tipaddr' >= ipaddr )";
                }
            }
        }
    }

    if($ipClass->IsvalidMAC($stringtofind2)){
        $OR[]="( mac='$stringtofind2' )";
        $MAC=true;
    }



    if(!$MAC){
        if($stringtofind2<>null){
            if(preg_match("#^[0-9a-z]+(:|-)[0-9a-z]+#", $stringtofind2)){
                $tt=explode(":",$stringtofind2);

                foreach ($tt as $index=>$value){if(strlen( (string) $value)>2){$tt[$index]="00";}}

                if(!isset($tt[1])){$tt[1]="00";}
                if(!isset($tt[2])){$tt[2]="00";}
                if(!isset($tt[3])){$tt[3]="00";}
                if(!isset($tt[4])){$tt[4]="00";}
                if(!isset($tt[5])){$tt[5]="00";}
                $tipaddr=@implode(":", $tt);
                if($tipaddr<>"00:00:00:00:00:00"){
                    $OR[]="( mac >='$tipaddr' )";
                    $MAC=true;
                }
            }
        }

    }

    if($INET==false){
        if($MAC==false){
            $OR[]="( fullhostname ~ '$stringtofind' )";
            $OR[]="( hostname ~ '$stringtofind' )";
            $OR[]="( hostalias1 ~ '$stringtofind' )";
            $OR[]="( hostalias2 ~ '$stringtofind' )";
            $OR[]="( hostalias3 ~ '$stringtofind' )";
            $OR[]="( hostalias4 ~ '$stringtofind' )";
        }
    }

    if($fixed){$AND1=" AND dhcpfixed=1 ";}


    return "SELECT * FROM hostsnet WHERE ( ".@implode(" OR ", $OR).")$AND1 $ORDERBY $ORDERL LIMIT $LIMIT";
}
function file_uploaded(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];
    $ou=urlencode($_GET["ou"]);
    $gpid=$_GET["gpid"];
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ldap.import.members";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ldap.import.members.txt";
    $ARRAY["CMD"]="openldap.php?import-members=yes&ou=$ou&gpid=$gpid&filename=".urlencode($file);
    $ARRAY["TITLE"]="{importing} $file";
    $ARRAY["AFTER"]="TableLoaderMyMemberearch();dialogInstance1.close();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=import-user-progress')";
    echo $jsrestart;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}