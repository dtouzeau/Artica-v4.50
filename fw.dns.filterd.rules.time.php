<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once("ressources/class.ldap-extern.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");

if(isset($_GET["list"])){table();exit;}
if(isset($_GET["period-add-js"])){period_add_js();exit;}
if(isset($_GET["period-add"])){period_add();exit;}
if(isset($_GET["period-del-js"])){period_del_js();exit;}
if(isset($_GET["category-post-js"])){categoy_post();exit;}
if(isset($_GET["category-del-js"])){category_del();exit;}	
if(isset($_POST["RuleMatchTime"])){rule_time_main_save();exit;}
if(isset($_POST["TIMEID"])){period_save();exit;}



	//category-del-js=$categorykey&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md

page();

function period_add_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog2("{period} {$_GET["title"]}", "$page?period-add=yes&ID={$_GET["ID"]}&TIMEID={$_GET["TIMEID"]}");
	
	
}
function period_del_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$TIMEID=$_GET["TIMEID"];
	$tpl=new templates();
	$page=CurrentPageName();
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
	}
	
	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	}
	
	
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	unset($TimeSpace["TIMES"][$TIMEID]);
	$TimeSpaceNew=base64_encode(serialize($TimeSpace));
	
	if($ID==0){
		$ligne["TimeSpace"]=$TimeSpaceNew;
		$sock=new dnsfiltersocks();
		$sock->SET_INFO("DansGuardianDefaultMainRule",serialize($ligne));
		echo "$('#{$_GET["md"]}').remove();\n";
		echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";		
		return;
	}
	
	$sql="UPDATE webfilter_rules SET TimeSpace='$TimeSpaceNew' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	echo "$('#{$_GET["md"]}').remove();\n";
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
}


function category_del(){
	header("content-type: application/x-javascript");
	$category=$_GET["category-del-js"];
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$modeblk=$_GET["modeblk"];
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="DELETE FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	echo "$('#{$_GET["md"]}').remove();\n";
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
}

function categoy_post(){
	header("content-type: application/x-javascript");
	$category=$_GET["category-post-js"];
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$modeblk=$_GET["modeblk"];
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT ID FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($ligne["ID"]>0){return;}
	
	$sql="INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk)
	VALUES ('$ID','{$category}','{$modeblk}')";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	
	echo "$('#{$_GET["md"]}').remove();\n";
	echo "RefreshUfdbCategoriesList();\n";
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
	
	
}

function page(){
	$page=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$explain=$tpl->_ENGINE_parse_body("<div class='alert alert-info'>{ufdbguardTimeSpaceExplain}</div>");
	
	
	echo "<div style='margin-top:10px'></div>$explain<div id='ufdb-time-list' style='margin-top:20px'></div>
	<script>
		function RefreshUfdbTimeList(){
			LoadAjax('ufdb-time-list','$page?list=yes&ID={$_GET["ID"]}');
			
		}
		RefreshUfdbTimeList();
	</script>	
	";
	
	
}

function period_add(){
	include_once('ressources/class.cron.inc');
	$ID=$_GET["ID"];
	$TIMEID=$_GET["TIMEID"];
	$QuotaID=$_GET["QuotaID"];
	$tpl=new template_admin();
	$page=CurrentPageName();
	$buttonname="{apply}";
	$close=null;
	
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");		
		$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
	}
	
	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));		
	}
	
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	$days=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");
	$cron=new cron_macros();
	$buttonname="{apply}";
	if($TIMEID==-1){
		$close="dialogInstance2.close();";
		$buttonname="{add}";}
	$Config=$TimeSpace["TIMES"][$TIMEID];
	while (list ($num, $val) = each ($days) ){
		$form[]=$tpl->field_checkbox("day_{$num}","{{$val}}",$Config["DAYS"][$num]);
	}
	
	
	$TT1=intval($Config["BEGINH"]).":".intval($Config["BEGINM"]);
	$TT2=intval($Config["ENDH"]).":".intval($Config["ENDM"]);
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_hidden("TIMEID", $TIMEID);
	
	
	
	$form[]=$tpl->field_clock("BEGINH", "{hourBegin}", $TT1);
	$form[]=$tpl->field_clock("ENDH", "{hourEnd}", $TT2);

	echo $tpl->form_outside("{set_the_period}", @implode("\n", $form),null,$buttonname,"RefreshUfdbTimeList();LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')$close","AsDnsAdministrator",true);
	return;

}
function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$daysARR=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");
	$ID=$_GET["ID"];
	
	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	}else{
		$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
	}
	
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$RuleBH=array("inside"=>"{inside_time}","outside"=>"{outside_time}","none"=>"{disabled}");
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
	if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}
	$RULESS["none"]="{none}";
	$RULESS[0]="{default}";
	$sql="SELECT ID,enabled,groupmode,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY groupname";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($ligne["ID"]==$ID){continue;}
		$RULESS[$ligne["ID"]]=$ligne["groupname"];
	
	}
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_array_hash($RuleBH, "RuleMatchTime","{match}", $TimeSpace["RuleMatchTime"]);
	$form[]=$tpl->field_array_hash($RULESS, "RuleAlternate","{alternate_rule}", $TimeSpace["RuleAlternate"]);
	$html[]=$tpl->form_outside("{behavior}", @implode("\n", $form),null,"{apply}","LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')","AsDnsAdministrator",true);
	

	$html[]=$tpl->_ENGINE_parse_body("
	
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?period-add-js=yes&ID={$_GET["ID"]}&TIMEID=-1')\"><i class='fa fa-plus'></i> {add_a_period} </label>
			</div>
				<div class=\"btn-group\" data-toggle=\"buttons\">
			</div>
			<table id='table-all-webftimes' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$rule}</th>";
	$html[]="<th data-sortable=false>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	if($ID>0){
		$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
		$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	}
	
	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
		$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	
	}
	
	
	
	
	
	$TRCLASS=null;
	while (list ($TIMEID, $array) = each ($TimeSpace["TIMES"]) ){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($array).$TIMEID);
		$dd=array();
		if(is_array($array["DAYS"])){
			while (list ($day, $val) = each ($array["DAYS"])){if($val==1){$dd[]="{{$daysARR[$day]}}";}}
			$daysText=@implode(", ", $dd);
		}
		if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
		if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
		if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
		if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}
		$daysText=$daysText." {from} {$array["BEGINH"]}:{$array["BEGINM"]} {to} {$array["ENDH"]}:{$array["ENDM"]}";
	
		$textfinal=$tpl->javascript_parse_text("{each} $daysText");
		$textfinal_enc=urlencode($textfinal);
		$js="Loadjs('$page?period-add-js=yes&ID=$ID&TIMEID=$TIMEID&title=$textfinal_enc')";
		$js_delete="Loadjs('$page?period-del-js=yes&ID=$ID&TIMEID=$TIMEID&md=$md')";
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%'><i class='fa fa-clock'></i>&nbsp;$TIMEID</td>";
		$html[]="<td>".$tpl->td_href($textfinal,null,$js)."</td>";
		$html[]="<td>". $tpl->icon_delete($js_delete,"AsDnsAdministrator")."</td>";
		$html[]="</tr>";
		
	}
		
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-all-webftimes').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
		echo @implode("\n", $html);
}

function rule_time_main_save(){
	$ID=$_POST["ID"];
	$tpl=new templates();
	$page=CurrentPageName();

	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
		$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
		$TimeSpace["RuleMatchTime"]=$_POST["RuleMatchTime"];
		$TimeSpace["RuleAlternate"]=$_POST["RuleAlternate"];
		$TimeSpaceNew=base64_encode(serialize($TimeSpace));
		$ligne["TimeSpace"]=$TimeSpaceNew;
		$sock->SET_INFO("DansGuardianDefaultMainRule", serialize($ligne));
		return;
	}


	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	$TimeSpace["RuleMatchTime"]=$_POST["RuleMatchTime"];
	$TimeSpace["RuleAlternate"]=$_POST["RuleAlternate"];
	$TimeSpaceNew=base64_encode(serialize($TimeSpace));
	$sql="UPDATE webfilter_rules SET TimeSpace='$TimeSpaceNew' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new dnsfiltersocks();
	
}
function period_save(){
	include_once('ressources/class.cron.inc');
	$ID=$_POST["ID"];
	$TIMEID=$_POST["TIMEID"];
	$tpl=new template_admin();
	$page=CurrentPageName();

	if($ID==0){
		$sock=new dnsfiltersocks();
		$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	}
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
	}
	
	$pp=explode(":",$_POST["ENDH"]);
	$_POST["ENDH"]=$pp[0];
	$_POST["ENDM"]=$pp[1];
	
	
	$pp=explode(":",$_POST["BEGINH"]);
	$_POST["BEGINH"]=$pp[0];
	$_POST["BEGINM"]=$pp[1];

	

	$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
	$Config["ENDH"]=$_POST["ENDH"];
	$Config["ENDM"]=$_POST["ENDM"];
	$Config["BEGINH"]=$_POST["BEGINH"];
	$Config["BEGINM"]=$_POST["BEGINM"];

    foreach ($_POST as $index=>$value){
		if(preg_match("#day_([a-z])#", $index,$re)){
			$Config["DAYS"][$re[1]]=$value;
		}
	}


	if($TIMEID==-1){
		$TimeSpace["TIMES"][]=$Config;
	}else{
		$TimeSpace["TIMES"][$TIMEID]=$Config;
	}



	if($ID==0){
		$TimeSpaceNew=base64_encode(serialize($TimeSpace));
		$ligne["TimeSpace"]=$TimeSpaceNew;
		$sock=new dnsfiltersocks();
		$sock->SET_INFO("DansGuardianDefaultMainRule",serialize($ligne));
		return;
	}

	$TimeSpaceNew=base64_encode(serialize($TimeSpace));
	$sql="UPDATE webfilter_rules SET TimeSpace='$TimeSpaceNew' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}


}