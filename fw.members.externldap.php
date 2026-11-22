<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap-extern.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
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
	
	
	
	$rows=array();
	$MAIN=isset($HASH[0])?$HASH[0]:array();
	foreach ($attrs as $attribute){
		$attribute=strtolower($attribute);
		if(!isset($MAIN[$attribute])){continue;}
		$count=intval($MAIN[$attribute]["count"]);
		if($count==0){continue;}
		$pp=array();
		for($i=0;$i<$count;$i++){
			$pp[]=htmlspecialchars((string)$MAIN[$attribute][$i]);
		}
		$rows[]="<tr><td class='awc-mono'><strong>".htmlspecialchars($attribute)."</strong></td>"
			   ."<td>".@implode(", ",$pp)."</td></tr>";
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead><tr><th>{field}</th><th>{value}</th></tr></thead><tbody>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div></div></div>";

	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function group_members(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$dn=$_GET["groups-members"];	
	$ldap=new ldap_extern();

	VERBOSE("->HashUsersFromGroupDN($dn)",__LINE__);
	$Members=$ldap->HashUsersFromGroupDN($dn);
	
	$suffix=$ldap->ldap_suffix;
	$rows=array();
	if(!is_array($Members)){$Members=array();}
	foreach ($Members as $uid=>$none){
		if(trim((string)$uid)==null){continue;}
		$DN=$ldap->GETDN_FROM_UID($uid);
		$DNEnc=urlencode($DN);
		$DN=str_replace($suffix,"",$DN);
		if(strlen($DN)>70){$DN=substr($DN,0,67)."...";}
		$jsGRP="Loadjs('$page?member-js=$DNEnc')";

		$h ="<tr>";
		$h.="<td><div class='awc-ident'>";
		$h.="<span class='awc-ico'><i class='fas fa-user'></i></span>";
		$h.="<span><span class='awc-name'>".$tpl->td_href(htmlspecialchars((string)$uid),"{click_to_edit}",$jsGRP)."</span></span>";
		$h.="</div></td>";
		$h.="<td class='awc-mono'>".htmlspecialchars($DN)."</td>";
		$h.="</tr>";
		$rows[]=$h;
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead><tr><th>{member}</th><th>{path}</th></tr></thead><tbody id='extgrpm-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_members}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} ".count($rows)." {items}</span>";
	$html[]="<span class='awc-pg' id='extgrpm-pager'></span></div>";
	$html[]="</div></div>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.awc_pager_js(25,"extgrpm-rows","extgrpm-pager");
}

function member_groups(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$dn=$_GET["member-groups"];
	$ldap=new ldap_extern();

	$Groups=$ldap->GetGroupsFromuser($dn);
	if(!is_array($Groups)){$Groups=array();}

	$rows=array();
	foreach ($Groups as $GroupDN=>$GroupName){
		if(trim((string)$GroupName)==null){continue;}
		$jsGRP="Loadjs('$page?group-js=".urlencode((string)$GroupDN)."')";

		$h ="<tr>";
		$h.="<td><div class='awc-ident'>";
		$h.="<span class='awc-ico'><i class='far fa-users'></i></span>";
		$h.="<span><span class='awc-name'>".$tpl->td_href(htmlspecialchars((string)$GroupName),"{click_to_edit}",$jsGRP)."</span></span>";
		$h.="</div></td>";
		$h.="<td class='awc-mono'>".htmlspecialchars((string)$GroupDN)."</td>";
		$h.="</tr>";
		$rows[]=$h;
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead><tr><th>{group2}</th><th>{path}</th></tr></thead><tbody id='extmbrg-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} ".count($rows)." {items}</span>";
	$html[]="<span class='awc-pg' id='extmbrg-pager'></span></div>";
	$html[]="</div></div>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.awc_pager_js(25,"extmbrg-rows","extmbrg-pager");
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
	$stringtofind=url_decode_special_tool($_GET["search"]);
	$stringtofind="*$stringtofind*";
	$stringtofind=str_replace("**", "*", $stringtofind);
	$stringtofind=str_replace("**", "*", $stringtofind);

	$MAIN_HASH=$ldap->UserAndGroupSearch($stringtofind,200);
	if(!is_array($MAIN_HASH)){$MAIN_HASH=array();}
	if(!isset($MAIN_HASH["count"])){$MAIN_HASH["count"]=0;}

	// Design awc-* : une fonction par type de ligne, assemblees dans $rows.
	// Plus de footable ici: il reecrit les classes du <table> et ajoute son
	// propre pager Bootstrap, etranger a la carte.
	$rows=array();
	for($i=0;$i<$MAIN_HASH["count"];$i++){
		if(!isset($MAIN_HASH[$i])){continue;}
		$ligne=$MAIN_HASH[$i];
		$objectclass=isset($ligne["objectclass"])?$ligne["objectclass"]:array();
		if(!is_array($objectclass)){$objectclass=array();}
		if(in_array("posixGroup",$objectclass)){
			$rows[]=extldap_group_row($ligne,$tpl,$page);
			continue;
		}
		if(isset($ligne["dn"]) && strpos((string)$ligne["dn"],"dc=pureftpd,dc=organizations")>0){continue;}
		$rows[]=extldap_user_row($ligne,$ldap,$tpl,$page);
	}
	$c=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead><tr>";
	$html[]="<th>{displayname}</th>";
	$html[]="<th>{email}</th>";
	$html[]="<th>{phone}</th>";
	$html[]="<th>{groups2}</th>";
	$html[]="</tr></thead><tbody id='extldap-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='extldap-pager'></span>";
	$html[]="</div></div></div>";

	// Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque accolade JavaScript pour un {token}.
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(50,"extldap-rows","extldap-pager");
}

// Un groupe de l'annuaire distant. La description passait en <small> collee au
// nom; elle devient le sous-titre de la pastille d'identite.
function extldap_group_row($ligne,$tpl,$page):string{
	$displayname=isset($ligne["cn"][0])?(string)$ligne["cn"][0]:"";
	if(isset($ligne["displayname"][0])){$displayname=(string)$ligne["displayname"][0];}
	$description=isset($ligne["description"][0])?(string)$ligne["description"][0]:"";
	$js="Loadjs('$page?group-js=".urlencode((string)$ligne["dn"])."')";

	$h ="<tr>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='far fa-users'></i></span>";
	$h.="<span><span class='awc-name'>".$tpl->td_href(htmlspecialchars($displayname),"{click_to_edit}",$js)."</span>";
	if($description!==""){$h.="<div class='awc-meta'>".htmlspecialchars($description)."</div>";}
	$h.="</span></div></td>";
	$h.="<td><span class='awc-none'>&mdash;</span></td>";
	$h.="<td><span class='awc-none'>&mdash;</span></td>";
	$h.="<td><span class='awc-none'>&mdash;</span></td>";
	$h.="</tr>";
	return $h;
}

// Un membre de l'annuaire distant. Tous ces attributs partaient BRUTS dans le
// HTML; la description etait meme reinjectee dans CHAQUE lien de groupe.
function extldap_user_row($ligne,$ldap,$tpl,$page):string{
	$uid="";
	if(isset($ligne["samaccountname"][0])){$uid=(string)$ligne["samaccountname"][0];}
	if(isset($ligne["uid"][0])){$uid=(string)$ligne["uid"][0];}

	$email_address=array();
	$telephonenumber=array();
	if(isset($ligne["userprincipalname"][0])){$email_address[]=(string)$ligne["userprincipalname"][0];}
	if(isset($ligne["mail"][0])){$email_address[]=(string)$ligne["mail"][0];}
	if(isset($ligne["telephonenumber"][0])){$telephonenumber[]=(string)$ligne["telephonenumber"][0];}
	if(isset($ligne["mobile"][0])){$telephonenumber[]=(string)$ligne["mobile"][0];}

	$displayname=null;
	$givenname=isset($ligne["givenname"][0])?(string)$ligne["givenname"][0]:null;
	$sn=isset($ligne["sn"][0])?(string)$ligne["sn"][0]:null;
	$description=isset($ligne["description"][0])?(string)$ligne["description"][0]:"";
	if($givenname<>null){if($sn<>null){$displayname=trim("$givenname $sn");}}
	if($displayname==null && isset($ligne["displayname"][0])){$displayname=trim((string)$ligne["displayname"][0]);}
	if($displayname==null){$displayname=$uid;}

	$gps=array();
	$Groups=$ldap->GetGroupsFromuser($uid);
	if(!is_array($Groups)){$Groups=array();}
	foreach($Groups as $GroupDN=>$GroupName){
		if(trim((string)$GroupName)==null){continue;}
		$jsGRP="Loadjs('$page?group-js=".urlencode((string)$GroupDN)."')";
		$gps[]="<span class='awc-chip'><span class='awc-chip-ico'><i class='fas fa-users'></i></span>"
			  ."<a href=\"javascript:blur();\" OnClick=\"$jsGRP\">".htmlspecialchars((string)$GroupName)."</a></span>";
		if(count($gps)>5){$gps[]="<span class='awc-none'>&hellip;</span>";break;}
	}

	$js="Loadjs('$page?member-js=".urlencode((string)$ligne["dn"])."')";
	$mails=array();
	foreach($email_address as $m){$mails[]="<div>".htmlspecialchars($m)."</div>";}
	$phones=array();
	foreach($telephonenumber as $p){$phones[]="<div>".htmlspecialchars($p)."</div>";}

	$h ="<tr>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-user'></i></span>";
	$h.="<span><span class='awc-name'>".$tpl->td_href(htmlspecialchars($displayname),"{click_to_edit}",$js)."</span>";
	if($description!==""){$h.="<div class='awc-meta'>".htmlspecialchars($description)."</div>";}
	elseif($uid!==""){$h.="<div class='awc-meta awc-mono'>".htmlspecialchars($uid)."</div>";}
	$h.="</span></div></td>";
	$h.="<td class='awc-mono'>".(count($mails)>0?@implode("",$mails):"<span class='awc-none'>&mdash;</span>")."</td>";
	$h.="<td class='awc-mono'>".(count($phones)>0?@implode("",$phones):"<span class='awc-none'>&mdash;</span>")."</td>";
	$h.="<td>".(count($gps)>0?"<div class='awc-chips'>".@implode("",$gps)."</div>":"<span class='awc-none'>{no_members}</span>")."</td>";
	$h.="</tr>";
	return $h;
}
