<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["MEMBERS_SEARCH"])){$_SESSION["MEMBERS_SEARCH"]="";}


if(isset($_GET["table"])){table();exit;}
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
if(isset($_GET["tabs-multi"])){tabs_multi();exit;}
if(isset($_GET["form-multi"])){form_multi();exit;}
if(isset($_GET["single-tab"])){single_tab();exit;}



page();

function new_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{new_member}", "$page?new-popup=yes");
}

function new_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$html="
	<div id='create-user-progress'></div>		
	<div id='create-user-div'></div>
	<script>LoadAjaxTiny('create-user-div','$page?new-organization=yes');</script>		
	";
	
	echo $html;
}
function new_organization(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ldap=new clladp();
	$hash=$ldap->hash_get_ou(true);
	$add_new_organisation_text=$tpl->javascript_parse_text("{add_new_organisation_text}");
	
	$form[]=$tpl->field_text("AddOU", "{create_a_new_organization}", null,false);

	
			
	if(count($hash)==0){	
		echo $tpl->form_outside("{new_member} &raquo; {create_a_new_organization}", @implode("\n", $form),"{add_new_organisation_text}","{add}",
					"LoadAjaxTiny('create-user-div','$page?new-group=yes');","AllowAddUsers");
		return;
	}
	
	
	
	$form[]=$tpl->field_array_hash($hash, "ChooseOU", "{organization}", $_SESSION["ADD_USER_OU"],false);
	echo $tpl->form_outside("{new_member} &raquo; {your_organization}", @implode("\n", $form),null,"{next}",
			"LoadAjaxTiny('create-user-div','$page?new-group=yes');","AllowAddUsers");
	
	
}

function new_group(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ldap=new clladp();
	$form[]=$tpl->field_info("ou", "{organization}", $_SESSION["ADD_USER_OU"]);
	
	$hash_groups=$ldap->hash_groups($_SESSION["ADD_USER_OU"],1);
	$hash_domains=$ldap->hash_get_domains_ou($_SESSION["ADD_USER_OU"]);
	
	$form[]=$tpl->field_text("AddGroup", "{new_group}", null,false);
	$form[]=$tpl->field_array_hash($hash_groups, "ChooseGroup", "{group2}", $_SESSION["ADD_USER_GROUP"],false);
	$form[]=$tpl->field_array_hash($hash_domains, "ChooseDomain", "{domain}", $_SESSION["ADD_USER_DOMAIN"],false);
	echo $tpl->form_outside("{new_member} &raquo; {$_SESSION["ADD_USER_OU"]} &raquo; {group2}", @implode("\n", $form),null,"{next}",
			"LoadAjaxTiny('create-user-div','$page?new-member=yes');","AllowAddUsers");
	
}

function new_member(){
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
		$gpid=$_SESSION["ADD_USER_GROUP"];
	}
	
	
	$form[]=$tpl->field_hidden("gpid", $gpid);
	$form[]=$tpl->field_info("ou", "{organization}", $_SESSION["ADD_USER_OU"]);
	$form[]=$tpl->field_info("GroupName", "{group2}", $hash_groups[$gpid]);
	$form[]=$tpl->field_info("internet_domain", "{domain}", $_SESSION["ADD_USER_DOMAIN"]);
	$form[]=$tpl->field_text("firstname", "{firstname}", null,true);
	$form[]=$tpl->field_text("lastname", "{lastname}", null,true);
	$form[]=$tpl->field_text("email", "{email}", null,true);
	$form[]=$tpl->field_text("login", "{uid}", null,true);
	$form[]=$tpl->field_password2("password", "{password}", null,true);
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/create-user.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/create-user.progress.txt";
	$ARRAY["CMD"]="system.php?create-user-progress=yes";
	$ARRAY["TITLE"]="{please_wait_creating_member}";
	$ARRAY["AFTER"]="dialogInstance1.close();TableLoaderMyMemberearch()";
	
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=create-user-progress')";
	
	
	echo $tpl->form_outside("{new_member} &raquo; {$_SESSION["ADD_USER_OU"]} &raquo; {$hash_groups[$gpid]}", @implode("\n", $form),null,"{add}",
	"$jsrestart","AllowAddUsers");
	
}

function new_member_save(){
	
	foreach ($_POST as $num=>$ligne){
		$_POST[$num]=url_decode_special_tool($ligne);
	}
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	
	$sql="CREATE TABLE IF NOT EXISTS `CreateUserQueue` ( `zMD5` TEXT PRIMARY KEY,`content` TEXT NOT NULL ) ";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	$tpl=new templates();
	$usersmenus=new usersMenus();
	if($usersmenus->ZARAFA_INSTALLED){$_POST["ByZarafa"]="yes";}
	$fulldata=base64_encode(serialize($_POST));
	
	$md5=md5($fulldata);
	$fulldata=mysql_escape_string2($fulldata);
	$q->QUERY_SQL("INSERT OR IGNORE INTO `CreateUserQueue` (zMD5,`content`) VALUES ('$md5','$fulldata')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}


function new_group_save(){
	$group=url_decode_special_tool($_POST["AddGroup"]);
	$ou=url_decode_special_tool($_POST["ou"]);
	if($ou==null){if($_SESSION["ou"]<>null){$ou=$_SESSION["ou"];}}
	
	$ldap=new clladp();
	include_once(dirname(__FILE__).'/ressources/class.groups.inc');
	
	$groupClass=new groups();
	$list=$groupClass->samba_group_list();
	
	if(is_array($list)){
        foreach ($list as $num=>$ligne){
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
	$tpl=new templates();
	if($_POST["AddOU"]<>null){
		$ou=url_decode_special_tool($_POST["AddOU"]);
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
	
	$_SESSION["ADD_USER_OU"]=url_decode_special_tool($_POST["ChooseOU"]);;
	
	
}
function single_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $ligne=$ActiveDirectoryConnections[0];
    $array=array();
    if($users->AllowAddGroup OR $users->AllowAddUsers) {
        if (isset($ligne["LDAP_SUFFIX"])) {
            if ($ligne["LDAP_SUFFIX"] <> null) {
                $array["{default}"] = "$page?form-multi=yes&connection-id=0";
            }
        }
        $array["{privileges_list}"] = "fw.members.privileges.php";
    }

    if(count($array)==0){
        echo $tpl->div_error("{ERROR_NO_PRIVS}||{no_privileges}");
        return false;
    }
    echo $tpl->tabs_default($array);

    return true;
}
function privs(){
    $users=new usersMenus();
    if($users->AllowAddGroup OR $users->AllowAddUsers) {return true;}
    return false;
}

function tabs_multi(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    if(!privs()){
        echo $tpl->div_error("{ERROR_NO_PRIVS}||{no_privileges}");
        return false;
    }

	$MUNIN=false;
	$ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));

	if($LockActiveDirectoryToKerberos==1){
        $EnableKerbAuth=1;
    }

	if($EnableKerbAuth==1){
		$array["{default}"]="$page?form-multi=yes&connection-id=0";
		
	}

	foreach ($ActiveDirectoryConnections as $cnxids=>$MAIN){
        if(!isset($ActiveDirectoryConnections[$cnxids]["LDAP_SUFFIX"])){
            continue;
        }

		$LDAP_SUFFIX=$ActiveDirectoryConnections[$cnxids]["LDAP_SUFFIX"];
		$array[$LDAP_SUFFIX]="$page?form-multi=yes&connection-id=$cnxids";
		
	}
    $array["{privileges_list}"] = "fw.members.privileges.php";
	echo $tpl->tabs_default($array);
    return true;
}

function form_multi(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$connection_id=$_GET["connection-id"];
	$function="SearchInADLDAP{$connection_id}";
	$html[]="
			
        <input type='hidden' id='ActiveDirectory-connection-type' value='0'>
		<div class='ibox-content'>
			<div class=\"input-group\">
      			<input type=\"text\" id='search-$connection_id-$t' class=\"form-control\" value=\"{$_SESSION["MEMBERS_SEARCH"]}\" placeholder=\"{search}\"  OnKeyPress=\"{$function}$t(event);\">
      			<span class=\"input-group-btn\">
       				 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"$function();\">Go!</button>
      			</span>
     		</div>
   	 </div>
";
	
	$html[]="
	<div class='ibox-content'>
		<div id='table-loader-$connection_id-members'></div>
	</div>";
	
    $html[]="<script>";

    $html[]="function {$function}$t(e){
        if(checkEnter(e)){ $function();}
    
    }";

    $html[]="
    function $function(){
        var type=document.getElementById('ActiveDirectory-connection-type').value;
        var ss=encodeURIComponent(document.getElementById('search-$connection_id-$t').value);
        LoadAjax('table-loader-$connection_id-members','$page?table=yes&t=$t&search='+ss+'&type='+type+'&function=$function&connection-id=$connection_id');
    }
    $function();";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}



function page_multi(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["MEMBERS_SEARCH"]==null){$_SESSION["MEMBERS_SEARCH"]="";}

    $html=$tpl->page_header("{my_members}: Active Directory",
        "fad fa-users",
        "<input type='hidden' id='ActiveDirectory-connection-type' value='0'>","$page?tabs-multi=yes",
        "ad-members","progress-firehol-restart",false,"mytabsadusers");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{my_members}",$html);
        echo $tpl->build_firewall();
        return;
    }
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function CheckHaCluster(){
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==0){return;}
    $haClusterAD=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
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
}

function page(){
    CheckHaCluster();
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	$ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));





	VERBOSE(count($ActiveDirectoryConnections)." Active Directory Connections.");
    $c=0;
    foreach ($ActiveDirectoryConnections as $index=>$ligne){
        if(!isset($ligne["LDAP_SUFFIX"])){continue;}
        VERBOSE("$index = Suffix = {$ligne["LDAP_SUFFIX"]}",__LINE__);
        if($ligne["LDAP_SUFFIX"]==null){continue;}
        if(!is_numeric($index)){continue;}
        $c++;
    }

    VERBOSE("$c Active Directory Connections.(after)");
    if($EnableKerbAuth==1) {
        VERBOSE("EnableKerbAuth = $EnableKerbAuth",__LINE__);
        $c++;
    }


	if($c>0){
        VERBOSE("-- page_multi() --",__LINE__);
		page_multi();
		return;
	}
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["MEMBERS_SEARCH"]==null){$_SESSION["MEMBERS_SEARCH"]="";}

    $html=$tpl->page_header("{my_members}: Active Directory",
        "fad fa-users",
        "<input type='hidden' id='ActiveDirectory-connection-type' value='0'>","$page?single-tab=yes",
        "ad-members","progress-firehol-restart",false);

	


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{my_members}: Active Directory", $html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);

}

function table_ad_groups():bool{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $ldap           = new clladp();
    $SearchType     = intval($_GET["type"]);
    $stringtofind = "";

    if(isset($_GET["search"])){
        $stringtofind = url_decode_special_tool($_GET["search"]);
        $stringtofind=trim($tpl->CLEAN_BAD_XSS($stringtofind));
    }

    $stringtofind = "*$stringtofind*";
    $stringtofind = str_replace("**", "*", $stringtofind);
    $stringtofind = str_replace("**", "*", $stringtofind);
    $ActiveDirectoryIndex = "None";
    if (isset($_GET["connection-id"])) {
        $ActiveDirectoryIndex = $_GET["connection-id"];
        $urlExtension = "&connection-id=$ActiveDirectoryIndex";
    }
    if (isset($_GET["function"])) {
        $urlExtension = $urlExtension . "&function={$_GET["function"]}";
    }

    $html[]="<table id='table-my-computers' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' width=1%>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groups2}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{explain}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $stringtofind=url_decode_special_tool($_GET["search"]);
    $stringtofind="*$stringtofind*";
    $stringtofind=str_replace("**", "*", $stringtofind);
    $stringtofind=str_replace("**", "*", $stringtofind);
    $ActiveDirectoryIndex="None";
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}

    VERBOSE(" external_ad_search(null,$ActiveDirectoryIndex)",__LINE__);
    $ad=new external_ad_search(null,$ActiveDirectoryIndex);
    $MAIN_HASH=$ad->AllGroups($stringtofind,50);
    $LDAP_SUFFIX=$ad->KerbAuthInfos["LDAP_SUFFIX"];
    $TRCLASS=null;

    $c=0;
    foreach ($MAIN_HASH as $FirstDN=>$ligne){
        $MembersCount = 0;
        $ysuffix = strtolower($ligne["SUFFIX"]);
        $ysuffix = str_replace("dc=", "", $ysuffix);
        $ysuffix = str_replace("ou=", "", $ysuffix);
        $ysuffix = str_replace("cn=", "", $ysuffix);
        $zsuf = explode(",", $ysuffix);
        $suffix = @implode(".", $zsuf);
        $description="&nbsp;";
        $displayname = $ligne["samaccountname"][0];
        if(isset($ligne["member"]["count"])) {
            $MembersCount = $ligne["member"]["count"];
        }

        if(isset($ligne["description"][0])){
            $description=$ligne["description"][0].'<br>';
        }

        $dn = urlencode($FirstDN);
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $js = "Loadjs('fw.groups.ad.php?dn=$dn$urlExtension')";
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td class=\"center\"><div><i class='far fa-users'></i></div></td>";
        $html[] = "<td class=\"\">" . $tpl->td_href($displayname, "{click_to_edit}", $js) . "</td>";
        $html[] = "<td class=\"\">$description <small>" . $tpl->td_href($suffix, "{click_to_edit}", $js) . "</small></td>";
        $html[] = "<td class=\"center\" width='1%' nowrap><strong>" . $MembersCount . "</strong></td>";
        $html[] = "</tr>";
        $c++;

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $table_buttons=table_buttons();
    $TINY_ARRAY["TITLE"]="{groups2}:&nbsp;$LDAP_SUFFIX";
    $TINY_ARRAY["ICO"]="fad fa-users-class";
    $TINY_ARRAY["EXPL"]="{search}: $stringtofind &nbsp;<strong><i class='fa-regular fa-users-between-lines'></i>&nbsp;$c {items}</strong>";
    $TINY_ARRAY["BUTTONS"]=$table_buttons[0];
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




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
	$(document).ready(function() { $('#table-my-computers').footable({ \"filtering\": { \"enabled\": false },\"sorting\": {\"enabled\": true } } ); });
	{$table_buttons[1]}
	$jstiny
	</script>
	";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function table_buttons(){
    $time           = time();
    $connection_id  = 0;
    $SearchType     = intval($_GET["type"]);
    $function       = $_GET["function"];
    $search         = urlencode($_GET["search"]);
    $btn_group      = "btn-default";
    $btn_user       = "btn-default";
    if($SearchType==0){$btn_group="btn-primary";}
    if($SearchType==1){$btn_user="btn-primary";}

    if(isset($_GET["connection-id"])){$connection_id=intval($_GET["connection-id"]);}

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]="<label class=\"btn btn $btn_group\" OnClick=\"CheckGroup{$time}()\"><i class='fas fa-users'></i> {groups2} </label>";
    $html[]="<label class=\"btn btn $btn_user\" OnClick=\"CheckUsers{$time}()\"><i class='fas fa-user'></i> {members} </label>";
    $html[]="</div>";




    $src[]="function CheckGroup{$time}(){";
    $src[]="\tdocument.getElementById('ActiveDirectory-connection-type').value=0;";
    $src[]="\t$function();";
    $src[]="}";
    $src[]="function CheckUsers{$time}(){";
    $src[]="\tdocument.getElementById('ActiveDirectory-connection-type').value=1;";
    $src[]="\t$function();";
    $src[]="}";

    return array(@implode("",$html),@implode("",$src));
}

function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $urlExtension       = null;
	$SearchType         = $_GET["type"];
	if($SearchType==0){
	    return table_ad_groups();
	}
	$stringtofind=url_decode_special_tool($_GET["search"]);
	$stringtofind="*$stringtofind*";
	$stringtofind=str_replace("**", "*", $stringtofind);
	$stringtofind=str_replace("**", "*", $stringtofind);
	$ActiveDirectoryIndex="None";
	if(isset($_GET["connection-id"])){
	    $ActiveDirectoryIndex=$_GET["connection-id"];
	    $urlExtension="&connection-id=$ActiveDirectoryIndex";
	}
	if(isset($_GET["function"])){
	    $urlExtension=$urlExtension."&function={$_GET["function"]}";
	}
	
    VERBOSE("external_ad_search(null,$ActiveDirectoryIndex)",__LINE__);
	$ad=new external_ad_search(null,$ActiveDirectoryIndex);

    $MAIN_HASH=$ad->AllUsers($stringtofind,200);
    $LDAP_SUFFIX=$ad->KerbAuthInfos["LDAP_SUFFIX"];


	$html[]="<table id='table-my-computers' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text' width=1%>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{displayname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domain}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{email}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{phone}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
//	print_r($hash_full);
	$TRCLASS=null;
    $c=0;
    foreach ($MAIN_HASH as $FirstDN=>$ligne){
		$ysuffix=strtolower($ligne["SUFFIX"]);
		$ysuffix=str_replace("dc=", "", $ysuffix);
		$ysuffix=str_replace("ou=", "", $ysuffix);
		$ysuffix=str_replace("cn=", "", $ysuffix);
		$zsuf=explode(",",$ysuffix);
		$suffix=@implode(".", $zsuf);
		$description=null;
        $displayname=null;
		if(isset($ligne["description"][0])){$description="<br><small>{$ligne["description"][0]}</small>";}
		if($description==null){
            if(isset($ligne["displayname"][0])){$description="<br><small>{$ligne["displayname"][0]}</small>";}
        }


		$objectclass=$ligne["objectclass"];

		if(in_array("computer", $objectclass)){
			$displayname=$ligne["samaccountname"][0];
			if(preg_match("#^(.+?)\\$#", $displayname,$re)){$displayname=$re[1];}
			if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$html[]="<tr class='$TRCLASS'>";
			$html[]="<td class=\"center\"><div><i class='fa fa-desktop'></i></div></td>";
			$html[]="<td class=\"\">$displayname</td>";
			$html[]="<td class=\"\">$suffix</td>";
			$html[]="<td class=\"\">". $tpl->icon_nothing()."</td>";
			$html[]="<td class=\"\">". $tpl->icon_nothing()."</td>";
			$html[]="</tr>";
			continue;
			
			
		}
		
		
		if(!isset($ligne["samaccountname"][0])){continue;}
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$givenname=null;
		$uid=null;
		$email_address=array();
		$telephonenumber=array();
		$GroupsTableau=null;
		$sn=null;
		$gps=array();
		$text_class=null;

		if(strpos($FirstDN,"dc=pureftpd,dc=organizations")>0){continue;}
		if(isset($ligne["samaccountname"][0])){$uid=$ligne["samaccountname"][0];}
		if(isset($ligne["userprincipalname"][0])){$email_address[]="<div>{$ligne["userprincipalname"][0]}</div>";}
		if(isset($ligne["telephonenumber"][0])){$telephonenumber[]="<div>{$ligne["telephonenumber"][0]}</div>";}
		if(isset($ligne["mobile"][0])){$telephonenumber[]="<div>{$ligne["mobile"][0]}</div>";}
        if(isset($ligne["mail"][0])){$email_address[]="<div>{$ligne["mail"][0]}</div>";}
		if(isset($ligne["givenname"][0])){$givenname=$ligne["givenname"][0];}
		if(isset($ligne["sn"][0])){$sn=$ligne["sn"][0];}
			
		if($givenname<>null){if($sn<>null){ $displayname=" $givenname $sn"; }}

		$uid=$ligne["samaccountname"][0];

		if($displayname==null){
            if(isset($ligne["displayname"][0])){
            $displayname=trim($ligne["displayname"][0]);}
        }
		if($displayname==null){$displayname=$uid;}

		$js="Loadjs('fw.member.ad.edit.php?dn=".urlencode($FirstDN)."')";
		
		
		if(count($telephonenumber)==0){$telephonenumber[]=$tpl->icon_nothing();}
		if(count($email_address)==0){$email_address[]=$tpl->icon_nothing();}
		
		if(preg_match("#^(.+?)\\$#", $displayname,$re)){
			$html[]="<tr class='$TRCLASS'>";
			$html[]="<td class=\"center\"><div><i class='fa fa-unversity'></i></div></td>";
			$html[]="<td class=\"$text_class\">$displayname$description</td>";
			$html[]="<td class=\"\">$suffix</td>";
			$html[]="<td class=\"$text_class\">". $tpl->icon_nothing()."</td>";
			$html[]="<td class=\"$text_class\">". $tpl->icon_nothing()."</td>";
			$html[]="</tr>";
			continue;
		}
		
		
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"center\"><div><i class='fa fa-user'></i></div></td>";
		$html[]="<td class=\"$text_class\">". $tpl->td_href($displayname,"{click_to_edit}",$js)."$description</td>";
		$html[]="<td class=\"\">". $tpl->td_href($suffix,"{click_to_edit}",$js)."</td>";
		$html[]="<td class=\"$text_class\">". $tpl->td_href(@implode("", $email_address),"{click_to_edit}",$js)."</td>";
		$html[]="<td class=\"$text_class\">". @implode("", $telephonenumber)."</td>";
		$html[]="</tr>";
        $c++;
	
	}

    //
    $table_buttons=table_buttons();
    $TINY_ARRAY["TITLE"]="{my_members}:&nbsp;$LDAP_SUFFIX";
    $TINY_ARRAY["ICO"]="fad fa-users";
    $TINY_ARRAY["EXPL"]="{search}: $stringtofind &nbsp;<strong><i class='fa-regular fa-users-between-lines'></i>&nbsp;$c {items}</strong>";
    $TINY_ARRAY["BUTTONS"]=$table_buttons[0];
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-my-computers').footable({ \"filtering\": { \"enabled\": false },\"sorting\": {\"enabled\": true } } ); });
$jstiny
{$table_buttons[1]}
var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	LoadAjax('table-loader-my-members','$page?table=yes');
}

function RuleGroupUpDown$t(ID,direction,eth){
	var XHR = new XHRConnection();
	XHR.appendData('rule-order', ID);
	XHR.appendData('direction', direction);
	XHR.appendData('eth', eth);
	XHR.sendAndLoad('firehol.nic.rules.php', 'POST',xRuleGroupUpDown$t);
}
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}

function BuildRequests($stringtofind){
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