<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["search"])){search();exit;}

js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	$title=$tpl->javascript_parse_text("{APP_OPENLDAP} {groups2}");
	$tpl->js_dialog7($title, "$page?content=yes&field-id={$_GET["field-id"]}",650);
}
function content(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new template_admin();
$html="	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["AD_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>
     <div id='search-$t'></div>
    </div>
	<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('search-$t','$page?search='+ss+'&field-id={$_GET["field-id"]}');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>    
    
    
    ";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function search(){
	$search="*{$_GET["search"]}*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$field_user=$_GET["field-id"];
	$tpl=new template_admin();
	$ldap=new clladp();
    $array=$ldap->UserAndGroupSearch(null,$search,300,true);


	$t=time();
	$html[]="<table id='proxy-ad-groups-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groupname}</th>";
	$html[]="<th data-sortable=false>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$TRCLASS=null;
	$c=0;

	$Max=$array["count"];

	for($i=0;$i<$Max;$i++){
	    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$c++;
	    $main=$array[$i];
        $memberuids=$main["memberuid"]["count"];
	    $description=$main["description"][0];
	    $groupname=$main["cn"][0];
	    if(!isset($main["gidnumber"])){continue;}
	    $gidnumber=$main["gidnumber"][0];

		$zmd5=md5(serialize($main));
        $html[]="<tr class='$TRCLASS' id='tr-$zmd5'>";
		
		$select=$tpl->icon_select("SelectThis$t('$gidnumber')");
		if($description<>null){$description="<br><small>$description</small>";}
		$html[]="<td><span style='font-weight:bold'>$groupname ($memberuids {members})</span>$description</td>";
		$html[]="<td class=center><center>$select</center></td>";
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
	$(document).ready(function() { $('#proxy-ad-groups-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });	
	
	function SelectThis$t(name){
		if(!document.getElementById('$field_user') ){
			alert('$field_user no such id');
			return;
		}
		document.getElementById('$field_user').value=name;
		dialogInstance7.close();
	
	}
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}