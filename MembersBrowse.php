<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.samba.inc');


	
	
	$user=new usersMenus();
	
	if(!CheckRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	if(isset($_GET["SambaAclBrowseFilter"])){SambaAclBrowseFilter();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["query"])){query();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	if(!isset($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=0;}
	if(!isset($_GET["OnlyGUID"])){$_GET["OnlyGUID"]=0;}
	if(!isset($_GET["NOComputers"])){$_GET["NOComputers"]=0;}
	if(!isset($_GET["Zarafa"])){$_GET["Zarafa"]=0;}
	if(!isset($_GET["OnlyAD"])){$_GET["OnlyAD"]=0;}
	if(isset($_GET["security"])){$_GET["security"]=null;}
	if(!isset($_GET["OnlyName"])){$_GET["OnlyName"]=0;}
	if(!isset($_GET["OnlyCheckAD"])){$_GET["OnlyCheckAD"]=0;}
	if(!isset($_GET["OnlyLDAP"])){$_GET["OnlyLDAP"]=0;}
	if(!isset($_GET["UseDN"])){$_GET["UseDN"]=0;}
	
	$title="{members}";
	if($_GET["OnlyGroups"]==1){$title="{groups2}";}
	
	$title=$tpl->_ENGINE_parse_body("{browse}::$title::");
	echo "YahooUser('534','$page?popup=yes&field-user={$_GET["field-user"]}&UseDN={$_GET["UseDN"]}&OnlyCheckAD={$_GET["OnlyCheckAD"]}&OnlyName={$_GET["OnlyName"]}&NOComputers={$_GET["NOComputers"]}&prepend={$_GET["prepend"]}&prepend-guid={$_GET["prepend-guid"]}&OnlyUsers={$_GET["OnlyUsers"]}&organization={$_GET["organization"]}&OnlyGroups={$_GET["OnlyGroups"]}&OnlyGUID={$_GET["OnlyGUID"]}&callback={$_GET["callback"]}&Zarafa={$_GET["Zarafa"]}&OnlyAD={$_GET["OnlyAD"]}&security={$_GET["security"]}','$title');";	
	
	
	
}
function popup(){
	if(isset($_SESSION["SambaAclBrowseFilter"]["acls_comps"])){$_SESSION["SambaAclBrowseFilter"]["acls_comps"]=0;}
	if(isset($_SESSION["SambaAclBrowseFilter"]["acls_gps"])){$_SESSION["SambaAclBrowseFilter"]["acls_gps"]=1;}
	if(isset($_SESSION["SambaAclBrowseFilter"]["acls_users"])){$_SESSION["SambaAclBrowseFilter"]["acls_users"]=1;}	
	$_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]=$_GET["acls_onlyad"];
	if(!is_numeric($_SESSION["SambaAclBrowseFilter"]["acls_onlyad"])){$_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]=0;}	
	if($_GET["prepend"]==null){$_GET["prepend"]=0;}
	if($_GET["prepend-guid"]==null){$_GET["prepend-guid"]=0;}
	$OnlyGUID=$_GET["OnlyGUID"];
	$OnlyAD=$_GET["OnlyAD"];
	$OnlyName=$_GET["OnlyName"];
	$OnlyCheckAD=$_GET["OnlyCheckAD"];
	$OnlyLDAP=$_GET["OnlyLDAP"];
	$UseDN=$_GET["UseDN"];
	if(!is_numeric($OnlyName)){$OnlyName=0;}
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyAD)){$OnlyAD=0;}
	if(!is_numeric($OnlyCheckAD)){$OnlyCheckAD=0;}
	if(!is_numeric($OnlyLDAP)){$OnlyLDAP=0;}
	if(!is_numeric($UseDN)){$UseDN=0;}
	
	if($_GET["callback"]<>null){$callback="{$_GET["callback"]}(id,prependText,guid);YahooUserHide();return;";}	
	
	$sock=new sockets();
	
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	$page=CurrentPageName();
	$tpl=new templates();	
	$dansguardian2_members_groups_explain=$tpl->_ENGINE_parse_body("{dansguardian2_members_groups_explain}");
	$t=time();
	$group=$tpl->_ENGINE_parse_body("{group}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$do_you_want_to_delete_this_group=$tpl->javascript_parse_text("{do_you_want_to_delete_this_group}");
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	$title=null;
	$filter=$tpl->_ENGINE_parse_body("{filter}");
	$groups=$tpl->_ENGINE_parse_body("{groups2}");
	
	$SUFFIX[]="&UseDN=$UseDN&prepend={$_GET["prepend"]}&field-user={$_GET["field-user"]}&prepend-guid={$_GET["prepend-guid"]}";
	$SUFFIX[]="&OnlyUsers={$_GET["OnlyUsers"]}&OnlyGUID={$_GET["OnlyGUID"]}&organization={$_GET["organization"]}";
	$SUFFIX[]="&OnlyGroups={$_GET["OnlyGroups"]}&callback={$_GET["callback"]}&NOComputers={$_GET["NOComputers"]}";
	$SUFFIX[]="&Zarafa={$_GET["Zarafa"]}&OnlyAD=$OnlyAD&t=$t&security={$_GET["security"]}&OnlyName=$OnlyName&OnlyCheckAD=$OnlyCheckAD&OnlyLDAP=$OnlyLDAP";
	$SUFFIX_FORMATTED=@implode("", $SUFFIX);
	
	
	$member_query="{display: '$members', name : 'members'},";
	if($_GET["OnlyGroups"]==1){$member_query=null;}
	
	$buttons="
	buttons : [
	{name: '$filter', bclass: 'Search', onpress : SambaAclBrowseFilter$t},$BrowsAD
	],";		
	
$html="
<div style='margin-left:-10px'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var rowid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?query=yes$SUFFIX_FORMATTED',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'groupname', width : 31, sortable : true, align: 'center'},	
		{display: '$members', name : 'members', width :405, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'members', width :31, sortable : false, align: 'left'},
		
		],
	$buttons
	searchitems : [
		$member_query
		{display: '$groups', name : 'groups'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 524,
	height: 350,
	singleSelect: true,
	rpOptions: [50,100,200,500,1000,2000]
	
	});   
});

function SambaAclBrowseFilter$t(){
	RTMMail('505','$page?SambaAclBrowseFilter=yes$SUFFIX_FORMATTED','$filter');
}

	function SambaBrowseSelect(id,prependText,guid){
			$callback
			var prepend={$_GET["prepend"]};
			var prepend_gid={$_GET["prepend-guid"]};
			var OnlyGUID=$OnlyGUID;
			if(document.getElementById('{$_GET["field-user"]}')){
				var selected=id;
				if(OnlyGUID==1){
					document.getElementById('{$_GET["field-user"]}').value=guid;
					YahooUserHide();
					return;
				}
				
				if(prepend==1){selected=prependText+id;}
				if(prepend_gid==1){
					if(guid>1){
						selected=prependText+id+':'+guid;
					}
				}
				document.getElementById('{$_GET["field-user"]}').value=selected;
				YahooUserHide();
			}
		}

</script>";	
	
	echo $html;
	
	
}
function SambaAclBrowseFilter(){
	$t=$_GET["t"];
	$tt=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
	
	$OnlyAD=$_GET["OnlyAD"];
	$OnlyGUID=$_GET["OnlyAD"];
	$OnlyUsers=$_GET["OnlyUsers"];
	$NOComputers=$_GET["NOComputers"];
	$OnlyName=$_GET["OnlyName"];
	$OnlyLDAP=$_GET["OnlyLDAP"];
	if(!is_numeric($OnlyAD)){$OnlyAD=0;}
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyUsers)){$OnlyUsers=0;}
	if(!is_numeric($NOComputers)){$NOComputers=1;}
	if(!is_numeric($OnlyName)){$OnlyName=0;}
	if(!is_numeric($OnlyLDAP)){$OnlyLDAP=0;}
	if($NOComputers==0){$NOComputers=1;}
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":".__LINE__." OnlyUsers=$OnlyUsers<br>\n";}
	
	
	unset($_GET["SambaAclBrowseFilter"]);
	foreach ($_GET as $key=>$value){
		$keyBin=$key;
		$keyBin=str_replace("-", "_", $keyBin);
		$jss[]="var $keyBin='$value';";
		$jssUri[]="'&$key='+$keyBin+";
	}
	
	
	$jssUriT=@implode("", $jssUri);
	if(substr($jssUriT,strlen($jssUriT)-1,1)=='+'){$jssUriT=substr($jssUriT, 0,strlen($jssUriT)-1);}
	$jssT=@implode("\n\t", $jss);
	if($EnableSambaActiveDirectory==1){
		$config_activedirectory=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
		$WORKGROUP=strtoupper($config_activedirectory["WORKGROUP"]);
		$onlyAd="
		<tr>
		<td width=1%><img src='img/wink3-32.png'></td>
		<td valign='top' class=legend style='font-size:16px'>{only_from_activedirectory} ($WORKGROUP):</td>
		<td>". Field_checkbox("OnlyAD-$tt", 1,$OnlyAD,"CheckAclsFilter()")."</td>
		</tr>";
	}
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td width=1%><img src='img/computer-32.png'></td>
		<td valign='top' class=legend style='font-size:16px'>{computers}:</td>
		<td width=1%>". Field_checkbox("NOComputers-$tt", 1,$NOComputers,"CheckAclsFilter()")."</td>
	</tr>
	<tr>
		<td width=1%><img src='img/member-32.png'></td>
		<td valign='top' class=legend style='font-size:16px'>{groupsF}:</td>
		<td width=1%>". Field_checkbox("OnlyGUID-$tt", 1,$OnlyGUID,"CheckAclsFilter()")."</td>
	</tr>
	<tr>
		<td width=1%><img src='img/user-32.png'></td>
		<td valign='top' class=legend style='font-size:16px'>{members}:</td>
		<td width=1%>". Field_checkbox("OnlyUsers-$tt", 1,$OnlyUsers,"CheckAclsFilter()")."</td>
	</tr>
	$onlyAd		
	</table>
	<script>
		function CheckAclsFilter(){
			$jssT
			if(document.getElementById('NOComputers-$tt').checked){NOComputers=0;}else{NOComputers=1;}
			if(document.getElementById('OnlyGUID-$tt').checked){OnlyGUID=1;}else{OnlyGUID=0;}
			if(document.getElementById('OnlyUsers-$tt').checked){OnlyUsers=1;}else{OnlyUsers=0;}
			if(document.getElementById('OnlyAD-$tt')){
				if(document.getElementById('OnlyAD-$tt').checked){OnlyAD=1;}else{OnlyAD=0;}
			}
			$('#flexRT$t').flexOptions({url: '$page?query=yes'+$jssUriT}).flexReload(); 
		}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function query_group(){
	if($_GET["OnlyUsers"]=="yes"){$_GET["OnlyUsers"]=1;}
	$users=new user();
	$query=$_POST["query"];
	$nogetent=false;
	$OnlyUsers=$_GET["OnlyUsers"];
	$OnlyGroups=$_GET["OnlyGroups"];
	$OnlyGUID=$_GET["OnlyGUID"];
	$OnlyName=$_GET["OnlyName"];
	$OnlyCheckAD=$_GET["OnlyCheckAD"];
	$UseDN=$_GET["UseDN"];
	$Zarafa=$_GET["Zarafa"];
	if(!is_numeric($_POST["rp"])){$_POST["rp"]=250;}
	$ObjectZarafa=false;
	
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyUsers)){$OnlyUsers=0;}
	if(!is_numeric($OnlyName)){$OnlyName=0;}
	if(!is_numeric($OnlyCheckAD)){$OnlyCheckAD=0;}
	if($Zarafa==1){$nogetent=true;$ObjectZarafa=true;}
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":".__LINE__." OnlyUsers=$OnlyUsers,OnlyGroups=$OnlyGroups<br>\n";}
	$OnlyUsers=0;
	$OnlyGroups=1;

	
	$ObjectZarafa=false;
	$Zarafa=$_GET["Zarafa"];
	if($Zarafa==1){$nogetent=true;$ObjectZarafa=true;}
	$hash=array();
	if(!isset($_GET["prepend"])){$_GET["prepend"]=0;}else{if($_GET["prepend"]=='yes'){$_GET["prepend"]=1;}if($_GET["prepend"]=='no'){$_GET["prepend"]=0;}}
	$WORKGROUP=null;
	$sock=new sockets();
	$ldap=new clladp();
	
	if($query==null){$query="*";}
	
	if($ldap->IsKerbAuth()){
		$adKerb=new external_ad_search();
		if($GLOBALS["VERBOSE"]){echo "<strong>searchGroup($query,array(),{$_POST["rp"]})</strong><br>\n";}
		$hash=$adKerb->searchGroup($query,array(),$_POST["rp"]);
		
		if($adKerb->IsError){
			json_error_show($adKerb->error,1);
		}
		
	}else{
		if($GLOBALS["VERBOSE"]){echo "<strong>IsKerbAuth = false</strong><br>\n";}
		if($OnlyGroups==1){
			if($GLOBALS["VERBOSE"]){echo "<strong>find_ldap_items_groups($query,...)</strong><br>\n";}
			$hash=$users->find_ldap_items_groups($query,$_GET["organization"],$nogetent,$ObjectZarafa,$_POST["rp"],$OnlyGUID,$OnlyUsers,$OnlyCheckAD);
		}else{
			if($GLOBALS["VERBOSE"]){echo "<strong>find_ldap_items($query,{$_GET["organization"]},$nogetent,$ObjectZarafa,{$_POST["rp"]},$OnlyGUID,$OnlyUsers,$OnlyCheckAD)<br>\n";}
			$hash=$users->find_ldap_items($query,$_GET["organization"],$nogetent,$ObjectZarafa,$_POST["rp"],$OnlyGUID,$OnlyUsers,$OnlyCheckAD);
		}
	}
	
	$query=$_POST["query"];
	if($query==null){$query="*";}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($hash);
	$data['rows'] = array();
	$c=0;
	
	foreach ($hash as $num=>$ligne){
		if($GLOBALS["VERBOSE"]){echo "<code>&raquo;$num&laquo; = $ligne</code><br>\n";}
		if($num==null){continue;}
		$gid=0;
		
	
		if(!preg_match("#^@(.+?):(.+?)$#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "<code style='color:#d32d2d'>&raquo;$ligne&laquo; ! = ^@(.+?):([0-9]+)</code><br>\n";}
			continue;
		}
		if($OnlyUsers==1){
			if($GLOBALS["VERBOSE"]){echo "<code style='color:#d32d2d'>OnlyUsers = 1 -> next</code><br>\n";}
			continue;
		}
		
		$img="wingroup.png";
		$Displayname="{$re[1]}";
		$prepend="group:";
		$gid=$re[2];
		if($OnlyName==1){if(preg_match("#^@(.+)#", $num,$ri)){$num=$ri[1];}}
				
		
	
		$js="SambaBrowseSelect('$num','$prepend','$gid')";
		if($_GET["callback"]<>null){$js="{$_GET["callback"]}('$num','$prepend','$gid')";}
	
		$c++;
		if($c>$_POST["rp"]){
			if($GLOBALS["VERBOSE"]){echo "<code style='color:#d32d2d'>\$c ($c) > {$_POST["rp"]} break</code><br>\n";}
			break;}
	
		$data['rows'][] = array(
				'id' => md5(serialize($ligne["displayname"])),
				'cell' => array(
						"<img src='img/$img'>",
						"<span style='font-size:14px;font-weight:bolder'>$Displayname</span> <span style='font-size:11px'>($num)</span>",
						"<span style='font-size:14px'>".imgsimple("arrow-right-24.png","{add}",$js)."</span>",
				)
		);
	
	
	
	}
	$data['total'] = $c;
	echo json_encode($data);	
	
	
	
}

function query(){
	if($_GET["OnlyUsers"]=="yes"){$_GET["OnlyUsers"]=1;}
	$users=new user();
	$query=$_POST["query"];
	$OnlyUsers=$_GET["OnlyUsers"];
	$OnlyGroups=$_GET["OnlyGroups"];
	$OnlyGUID=$_GET["OnlyGUID"];
	$OnlyName=$_GET["OnlyName"];
	$OnlyCheckAD=$_GET["OnlyCheckAD"];
	$OnlyLDAP=$_GET["OnlyLDAP"];
	$Zarafa=$_GET["Zarafa"];
	$OnlyAD=$_GET["OnlyAD"];	
	$sock=new sockets();
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
	
	
	writelogs("qtype={$_POST["qtype"]}; EnableSambaActiveDirectory=$EnableSambaActiveDirectory, OnlyUsers=$OnlyUsers OnlyGroups={$_GET["OnlyGroups"]}",__FUNCTION__,__FILE__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":".__LINE__." OnlyUsers=$OnlyUsers OnlyGroups={$_GET["OnlyGroups"]}<br>\n";}
	
	if($EnableSambaActiveDirectory==1){
		if($_POST["qtype"]=="members"){
			query_members_ad();
			return;
			
		}
		
	}
	
	
	
	if($_POST["qtype"]=="groups"){
		query_group();
		return;
		
	}
	
	
	if($OnlyGroups==1){
		query_group();
		return;		
	}
	
	$nogetent=false;	
		

	
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyUsers)){$OnlyUsers=0;}
	if(!is_numeric($OnlyName)){$OnlyName=0;}
	if(!is_numeric($OnlyCheckAD)){$OnlyCheckAD=0;}	
	if(!is_numeric($OnlyLDAP)){$OnlyLDAP=0;}
	if(!is_numeric($OnlyAD)){$OnlyAD=0;}
	if(!is_numeric($OnlyGroups)){$OnlyGroups=0;}
	
	
	
	if($OnlyLDAP==1){$_GET["OnlyAD"]=0;}
	$ObjectZarafa=false;
	
	if($OnlyAD==1){$OnlyCheckAD=1;}
	
	if($Zarafa==1){$nogetent=true;$ObjectZarafa=true;}
	$hash=array();
	if(!isset($_GET["prepend"])){$_GET["prepend"]=0;}else{if($_GET["prepend"]=='yes'){$_GET["prepend"]=1;}if($_GET["prepend"]=='no'){$_GET["prepend"]=0;}}
	$WORKGROUP=null;

	$sock=new sockets();
	
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":".__LINE__." OnlyAD={$_GET["OnlyAD"]}<br>\n";}
	
	if($_GET["OnlyAD"]==1){
		$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
		if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
		if($EnableSambaActiveDirectory==1){
			$config_activedirectory=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
			$WORKGROUP=strtoupper($config_activedirectory["WORKGROUP"])."/";
		}
	}
	
	if($query=='*'){
		if($WORKGROUP<>null){
				$query="$WORKGROUP/*";
			}else{
				$query=null;}
	}else{
		if($WORKGROUP<>null){$query="$WORKGROUP/$query";}
		
	}
	
	$usersMenus=new usersMenus();
	if(!$usersMenus->IsSuperAdmin()){
		$_GET["organization"]=$_SESSION["ou"];
	}
	
	if($query==null){$query="*";}
	if(!isset($_POST["rp"])){$_POST["rp"]=50;}
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":".__LINE__." ->";}
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":".__LINE__." ->find_ldap_items($query,{$_GET["organization"]},$nogetent,ObjectZarafa=$ObjectZarafa,{$_POST["rp"]},OnlyGUID=$OnlyGUID,OnlyUsers=$OnlyUsers,OnlyCheckAD=$OnlyCheckAD...<br>\n";}
	$hash=$users->find_ldap_items($query,$_GET["organization"],$nogetent,$ObjectZarafa,$_POST["rp"],$OnlyGroups,$OnlyUsers,$OnlyCheckAD);

	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($hash);
	$data['rows'] = array();	
	$c=0;
	
	foreach ($hash as $num=>$ligne){
		if($num==null){continue;}
		$gid=0;
		
		
		if(preg_match("#^@(.+?):([0-9]+)#",$ligne,$re)){
			if($OnlyUsers==1){continue;}
			$img="wingroup.png";
			$Displayname="{$re[1]}";
			$prepend="group:";
			$gid=$re[2];
			if($OnlyName==1){if(preg_match("#^@(.+)#", $num,$ri)){$num=$ri[1];}}
			
		}else{
			if($OnlyGroups==1){continue;}
			$Displayname=$ligne;
			$img="user-18.png";
			$prepend="user:";
		}
		
		if(substr($num,strlen($num)-1,1)=='$'){
			if($_GET["NOComputers"]==1){continue;}
			$Displayname=str_replace('$','',$Displayname);
			$img="base.gif";
			$prepend="computer:";
			
		}
		
		$js="SambaBrowseSelect('$num','$prepend',$gid)";
		if($_GET["callback"]<>null){$js="{$_GET["callback"]}('$num','$prepend',$gid)";}

		$c++;
		if($c>$_POST["rp"]){break;}
		
		$data['rows'][] = array(
		'id' => md5(serialize($ligne["displayname"])),
		'cell' => array(
			"<img src='img/$img'>",
			"<span style='font-size:14px;font-weight:bolder'>$Displayname</span> <span style='font-size:11px'>($num)</span>",
			"<span style='font-size:14px'>".imgsimple("arrow-right-24.png","{add}",$js)."</span>",
			)
		);		
		
		
	
	}
	$data['total'] = $c;
	echo json_encode($data);	

	
}

function CheckRights(){
	$users=new usersMenus();
	if($users->IsSuperAdmin()){return true;}
	if($users->AsSambaAdministrator){return true;}
	if($users->AsPostfixAdministrator){return true;}
	if($users->AsMessagingOrg){return true;}
	if($users->AsMailBoxAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
	return false;
	
	
}

function query_members_ad(){
	include_once(dirname(__FILE__).'/class.external.ad.inc');
	$sock=new sockets();
	$config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
	$ldap=new external_ad_search($config);
	$query=$_POST["query"];
	if($query==null){$query="*";}
	$hash=$ldap->find_users(null,$query,$_POST["rp"]);
	
	writelogs("COUNT={$hash["count"]}",__FUNCTION__,__FILE__,__LINE__);
	
	

	$data = array();
	$data['page'] = 1;
	$data['total'] = $hash["count"];
	$data['rows'] = array();
	$c=0;
	
	for($i=0;$i<$hash["count"];$i++){
		$ligne=$hash[$i];
		
		$samaccountname=$ligne["samaccountname"][0];
		if($samaccountname==null){continue;}
		$gid=0;
		$Displayname=$samaccountname;
		$img="user-18.png";
		$prepend="user:";
		
	
		if(substr($samaccountname,strlen($samaccountname)-1,1)=='$'){
			if($_GET["NOComputers"]==1){continue;}
			$Displayname=str_replace('$','',$Displayname);
			$img="base.gif";
			$prepend="computer:";
				
		}
	
		$js="SambaBrowseSelect('$samaccountname','$prepend',$gid)";
		if($_GET["callback"]<>null){$js="{$_GET["callback"]}('$samaccountname','$prepend',$gid)";}
	
		if(isset($ligne["displayname"][0])){
			$Displayname=$ligne["displayname"][0];
		}
		
		$c++;
		if($c>$_POST["rp"]){break;}
	
		$data['rows'][] = array(
				'id' => md5(serialize($ligne["displayname"])),
				'cell' => array(
						"<img src='img/$img'>",
						"<span style='font-size:14px;font-weight:bolder'>$Displayname</span> <span style='font-size:11px'>($samaccountname)</span>",
						"<span style='font-size:14px'>".imgsimple("arrow-right-24.png","{add}",$js)."</span>",
				)
		);
	
	
	
	}
	$data['total'] = $c;
	echo json_encode($data);	
	
}



