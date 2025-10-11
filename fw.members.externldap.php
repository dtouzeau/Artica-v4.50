<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap-extern.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["member-js"])){uid_js();exit;}
if(isset($_GET["member-tabs"])){uid_tabs();exit;}
if(isset($_GET["member-profile"])){member_profile();exit;}
if(isset($_GET["member-groups"])){member_groups();exit;}

if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-tabs"])){group_tabs();exit;}
if(isset($_GET["groups-members"])){group_members();exit;}


page();



function uid_js(){
	$page=CurrentPageName();
	$dn=$_GET["member-js"];
	$dnenc=urlencode($dn);
	$tpl=new template_admin();
	$tpl->js_dialog1("$dn", "$page?member-tabs=$dnenc");
	
}
function group_js(){
	$page=CurrentPageName();
	$dn=$_GET["group-js"];
	$dnenc=urlencode($dn);
	$tpl=new template_admin();
	$tpl->js_dialog2("$dn", "$page?group-tabs=$dnenc");

}



function uid_tabs(){
	$dn=$_GET["member-tabs"];
	$dnenc=urlencode($dn);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ldap=new ldap_extern();
	$HASH=$ldap->DNInfos($dn);

	if(isset($HASH[0]["displayname"])){$title=$HASH[0]["displayname"][0];}
	if($title==null){ if(isset($HASH[0]["uid"])){$title=$HASH[0]["uid"][0];} }
	if($title==null){ if(isset($HASH[0]["cn"])){$title=$HASH[0]["cn"][0];} }
	if($title==null){$title="unknown";}
	
	$array[$title]="$page?member-profile=$dnenc";
	$array["{groups2}"]="$page?member-groups=$dnenc";
	echo $tpl->tabs_default($array);
}
function group_tabs(){
	$dn=$_GET["group-tabs"];
	$dnenc=urlencode($dn);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ldap=new ldap_extern();
	$HASH=$ldap->DNInfos($dn);

	if(isset($HASH[0]["displayname"])){$title=$HASH[0]["displayname"][0];}
	if($title==null){ if(isset($HASH[0]["uid"])){$title=$HASH[0]["uid"][0];} }
	if($title==null){ if(isset($HASH[0]["cn"])){$title=$HASH[0]["cn"][0];} }
	if($title==null){$title="unknown";}

	$array[$title]="$page?member-profile=$dnenc";
	$array["{members}"]="$page?groups-members=$dnenc";
	echo $tpl->tabs_default($array);
}

function member_profile(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$dn=$_GET["member-profile"];
	$ldap=new ldap_extern();
	$HASH=$ldap->DNInfos($dn);
	$attrs[]="description";

	$attrs[]="gidNumber";
	$attrs[]="sambaSID";
	$attrs[]="uidnumber";
	$attrs[]="title";
	$attrs[]="gender";
	$attrs[]="givenName";
	$attrs[]="sn";
	$attrs[]="displayname";
	$attrs[]="cn";
	$attrs[]="gecos";
	$attrs[]="departmentnumber";
	$attrs[]="employeetype";
	$attrs[]="employeenumber";
	$attrs[]="homedirectory";
	$attrs[]="telephonenumber";
	$attrs[]="mobile";
	$attrs[]="mail";
	$attrs[]="mozillaSecondEmail";
	$attrs[]="mozillaNickname";	
	$attrs[]="homePhone";
	$attrs[]="homePostalAddres";
	$attrs[]="street";
	$attrs[]="postOfficeBox";
	$attrs[]="postalCode";
	$attrs[]="postaladdress";
	$attrs[]="facsimileTelephoneNumber";
	$attrs[]="fax";
	$attrs[]="nsaimid";
	$attrs[]="nsicqid";
	$attrs[]="nsmsnid";
	$attrs[]="nsyahooid";
	
	$attrs[]="businessrole";
	$attrs[]="managername";
	$attrs[]="assistantname";
	$attrs[]="roomnumber";
	$attrs[]="birthdate";
	$attrs[]="spousename";
	$attrs[]="anniversary";
	
	
	
	$html[]="<table class='table table-striped'>";
	$html[]="<thead>";
	$html[]="<tr><th align='right'>{field}</th><th>{value}</th></tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$MAIN=$HASH[0];
	foreach ($attrs as $attribute){
		$attribute=strtolower($attribute);
		if(!isset($MAIN[$attribute])){continue;}
		$count=$MAIN[$attribute]["count"];
		if($count==0){continue;}
		$html[]="<tr>";
		$html[]="<td align='right'><strong>$attribute:</strong></td>";
		$pp=array();
		for($i=0;$i<$count;$i++){
			$pp[]=$MAIN[$attribute][$i];
		}
		$html[]="<td>".@implode(", " ,$pp)."</td></tr>";
		
	}
	$html[]="</tbody>";
	$html[]="</table>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function group_members(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$dn=$_GET["groups-members"];	
	$ldap=new ldap_extern();

	VERBOSE("->HashUsersFromGroupDN($dn)",__LINE__);
	$Members=$ldap->HashUsersFromGroupDN($dn);
	
	$html[]="<table class='table table-striped'>";
	$html[]="<thead>";
	$html[]="<tr><th align='left'>&nbsp;</th><th align='left'>{member}</th><th>{path}</th></tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	

	$suffix=$ldap->ldap_suffix;
	
	foreach ($Members as $uid=>$none){
		if(trim($uid)==null){continue;}
		$DN=$ldap->GETDN_FROM_UID($uid);
		$DNEnc=urlencode($DN);
        $DN=str_replace($suffix,"",$DN);

		$jsGRP="Loadjs('$page?member-js=$DNEnc')";
		if(strlen($DN)>70){$DN=substr($DN,0,67)."...";}
		$html[]="<tr>";
        $html[]="<td align='left' width='1%' nowrap><i class='fa fa-user'></i>&nbsp;</td>";
		$html[]="<td align='left' width='1%' nowrap><strong>$uid</strong></td>";
		$html[]="<td>".$tpl->td_href($DN,null,$jsGRP)."</td></tr>";
	}
	$html[]="</tbody>";
	$html[]="</table>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function member_groups(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$dn=$_GET["member-groups"];
	$ldap=new ldap_extern();

	$html[]="<table class='table table-striped'>";
	$html[]="<thead>";
	$html[]="<tr><th align='right'>{group2}</th><th>{path}</th></tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$Groups=$ldap->GetGroupsFromuser($dn);
	
	
	foreach ($Groups as $GroupDN=>$GroupName){
		if(trim($GroupName)==null){continue;}
		$jsGRP="Loadjs('$page?group-js=".urlencode($GroupDN)."')";
		$html[]="<tr>";
		$html[]="<td align='right'><strong>$GroupName</strong></td>";
		$html[]="<td>".$tpl->td_href($GroupDN,null,$jsGRP)."</td></tr>";
	}
	$html[]="</tbody>";
	$html[]="</table>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["EXT_MEMBERS_SEARCH"]==null){$_SESSION["EXT_MEMBERS_SEARCH"]="";}
	
	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{my_members}</h1></div>
	</div>
		

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["EXT_MEMBERS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:TableLoaderMyMemberearch();\">Go!</button>
      	</span>
     </div>
    </div>
</div>	
	
	
	
		
	<div class='row'><div id='progress-firehol-restart'></div>";

	$html[]="<div class='ibox-content'>

	<div id='table-loader-my-members'></div>

	</div>
	</div>
		
		
		
<script>
	$.address.state('/');
	$.address.value('remote-members');
	$.address.title('Artica: {my_members}');

		function Search$t(e){
			if(!checkEnter(e) ){return;}
			TableLoaderMyMemberearch();
		}
		
		function TableLoaderMyMemberearch(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader-my-members','$page?table=yes&t=$t&search='+ss+'&function=TableLoaderMyMemberearch');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			TableLoaderMyMemberearch();
		}
		Start$t();
	</script>";
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: {my_members}",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ldap=new ldap_extern();
	$t=time();
	$stringtofind=url_decode_special_tool($_GET["search"]);
	$stringtofind="*$stringtofind*";
	$stringtofind=str_replace("**", "*", $stringtofind);
	$stringtofind=str_replace("**", "*", $stringtofind);
	
	$MAIN_HASH=$ldap->UserAndGroupSearch($stringtofind,200);
	
	/*$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">". 
			$tpl->button_label_table("{new_member}", "Loadjs('$page?new-js=yes')", "far fa-user-plus","AllowAddUsers")."

			</div>");
	*/
	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text' width=1%>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{displayname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{email}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{phone}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groups2}</th>";
	
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
//	print_r($hash_full);
	$TRCLASS=null;
	for($i=0;$i<$MAIN_HASH["count"];$i++){
		$description=null;
		
		$ligne=$MAIN_HASH[$i];
		$objectclass=$ligne["objectclass"];
		if(in_array("posixGroup", $objectclass)){
			$displayname=$ligne["cn"][0];
			$gidnumber=$ligne["gidnumber"][0];
			if(isset($ligne["displayname"][0])){$displayname=$ligne["displayname"][0];}
			if(isset($ligne["description"][0])){$description=$ligne["description"][0];}else{VERBOSE("description: Not found...",__LINE__);}
			if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$js="Loadjs('$page?group-js=".urlencode($ligne["dn"])."')";
			
			if($description<>null){
				VERBOSE("description: $description.",__LINE__);
				$description="<br><small>{$description}</small>";
			}else{
				VERBOSE("$displayname: description: NULL",__LINE__);
			}
			
			
			$html[]="<tr class='$TRCLASS'>";
			$html[]="<td class=\"center\"><div><i class='far fa-users'></i></div></td>";
			$html[]="<td class=\"\">". $tpl->td_href($displayname,"{click_to_edit}",$js)."$description</td>";
			$html[]="<td class=\"\">". $tpl->icon_nothing()."</td>";
			$html[]="<td class=\"\">". $tpl->icon_nothing()."</td>";
			$html[]="<td class=\"\">". $tpl->icon_nothing()."</td>";
			$html[]="</tr>";
			continue;
		}
		
		
		
		
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
		$description=null;
		if(strpos($ligne["dn"],"dc=pureftpd,dc=organizations")>0){continue;}
		if(isset($ligne["samaccountname"][0])){$uid=$ligne["samaccountname"][0];}
		if(isset($ligne["userprincipalname"][0])){$email_address[]="<div>{$ligne["userprincipalname"][0]}</div>";}
		if(isset($ligne["telephonenumber"][0])){$telephonenumber[]="<div>{$ligne["telephonenumber"][0]}</div>";}
		if(isset($ligne["mobile"][0])){$telephonenumber[]="<div>{$ligne["mobile"][0]}</div>";}
		if(isset($ligne["uid"][0])){$uid=$ligne["uid"][0];}
		if(isset($ligne["mail"][0])){$email_address[]="{$ligne["mail"][0]}";}
		if(isset($ligne["givenname"][0])){$givenname=$ligne["givenname"][0];}
		if(isset($ligne["sn"][0])){$sn=$ligne["sn"][0];}
		if(isset($ligne["description"][0])){$description=$ligne["description"][0];}else{VERBOSE("description: Not found...",__LINE__);}
			
		if($givenname<>null){if($sn<>null){ $displayname=" $givenname $sn"; }}
		if($description<>null){
			VERBOSE("description: $description.",__LINE__);
			$description="<br><small>{$description}</small>";
		}else{
			VERBOSE("$displayname: description: NULL",__LINE__);
		}
			
				
		$Groups=$ldap->GetGroupsFromuser($uid);
		
		
		foreach ($Groups as $GroupDN=>$GroupName){
			if(trim($GroupName)==null){continue;}
			
			$jsGRP="Loadjs('$page?group-js=".urlencode($GroupDN)."')";
				
			$gps[]="<div><a href=\"javascript:blur();\" OnClick=\"javascript:$jsGRP\" style='text-decoration:underline'>$GroupName</a>$description</div>";
			if(count($gps)>5){$gps[]="...";break;}
				
		}
		$GroupsTableau=@implode(", ", $gps);
        if(isset($ligne["displayname"][0])) {
            if ($displayname == null) {
                $displayname = trim($ligne["displayname"][0]);
            }
        }
		if($displayname==null){$displayname=$uid;}
		$dnenc=urlencode($ligne["dn"]);
		$js="Loadjs('$page?member-js=$dnenc')";
		
		if(count($telephonenumber)==0){$telephonenumber[]=$tpl->icon_nothing();}
		if(count($email_address)==0){$email_address[]=$tpl->icon_nothing();}
		if($description<>null){
			VERBOSE("description: $description.",__LINE__);
			$description="<br><small>{$description}</small>";
		}else{
			VERBOSE("$displayname: description: NULL",__LINE__);
		}
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"center\"><div><i class='fa fa-user'></i></div></td>";
		$html[]="<td class=\"$text_class\">". $tpl->td_href($displayname,"{click_to_edit}",$js)."$description</td>";
		$html[]="<td class=\"$text_class\">". $tpl->td_href(@implode("", $email_address),"{click_to_edit}",$js)."</td>";
		$html[]="<td class=\"$text_class\">". @implode("", $telephonenumber)."</td>";
		$html[]="<td class=\"$text_class\">".@implode("",$gps)."</td>";
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
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });

</script>";

			echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
