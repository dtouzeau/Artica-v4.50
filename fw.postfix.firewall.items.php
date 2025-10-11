<?php
//$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["ipaddr-js"])){ipaddr_js();exit;}
if(isset($_GET["ipaddr-popup"])){ipaddr_popup();exit;}
if(isset($_POST["ipaddr"])){ipaddr_save();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_GET["enable"])){enable();exit;}

table();

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$html[]="

	</div>
	<div class='ibox-content'>
	<div id='postfix-ipset'></div>

	</div>
	</div>
	";


	$html[]=$tpl->search_block($page,"postgres","smtp_ipset","smtp_ipset","");
	echo $tpl->_ENGINE_parse_body($html);

}

function delete(){
	$ipaddr=$_GET["delete"];
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM smtp_ipset WHERE pattern='$ipaddr'");
	
	$date=date("Y-m-d H:i:s");
	$admin=$_SESSION["uid"];
	if($admin==-100){$admin="Manager";}
	$subject="Firewall rule removed by $admin";
	$sql="INSERT INTO smtplog(zdate,ipaddr,reason,refused,smtp_code,subject) VALUES('$date','$ipaddr','FireWall remove',0,55,'$subject')";
	$q->QUERY_SQL($sql);
	echo "$('#{$_GET["md"]}').remove()\n";
}


function ipaddr_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ipaddr=$_GET["ipaddr-js"];
	if($ipaddr==null){$title="{new_entry}";}else{$title=$ipaddr;}
	$tpl->js_prompt("{new_address}", "{set_address_generic}", "far fa-plus-square", "$page", "ipaddr","{$_GET["function"]}()");
	
}
function enable(){
	$ipaddr=$_GET["enable"];
	$q=new postgres_sql();
	$tpl=new template_admin();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM smtp_ipset WHERE pattern='$ipaddr'"));
	VERBOSE("ENABLED: {$ligne["enabled"]}",__LINE__);
	if($ligne["enabled"]==1){
		$newenabled=0;
		VERBOSE("UPDATE smtp_ipset SET enabled=$newenabled WHERE pattern='$ipaddr'",__LINE__);
		$q->QUERY_SQL("UPDATE smtp_ipset SET enabled=$newenabled WHERE pattern='$ipaddr'");
	}else{
		$newenabled=1;
		VERBOSE("UPDATE smtp_ipset SET enabled=$newenabled WHERE pattern='$ipaddr'",__LINE__);
		$q->QUERY_SQL("UPDATE smtp_ipset SET enabled=$newenabled WHERE pattern='$ipaddr'");
	}
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);}
	$date=date("Y-m-d H:i:s");
	$admin=$_SESSION["uid"];
	if($admin==-100){$admin="Manager";}
	if($newenabled==1){
		$subject="Firewall rule enabled by $admin";
		$sql="INSERT INTO smtplog(zdate,ipaddr,reason,refused,smtp_code,subject) VALUES('$date','$ipaddr','FireWall Block',1,55,'$subject')";
		$q->QUERY_SQL($sql);
	}else{
		$subject="Firewall rule disabled by $admin";
		$sql="INSERT INTO smtplog(zdate,ipaddr,reason,refused,smtp_code,subject) VALUES('$date','$ipaddr','FireWall Unblock',0,55,'$subject')";
		$q->QUERY_SQL($sql);		
	}
	
	
}

function ipaddr_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ipaddr=$_GET["ipaddr-popup"];
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$jsafter="BootstrapDialog1.close();{$_GET["function"]}()";
	if($ipaddr==null){
		$bt="{add}";
		$title="{new_address}";
		$form[]=$tpl->field_text("ipaddr","{address}", $ipaddr);
		$description="Added $uid - ".date("Y-m-d H:i:s");
	}else{
		$bt="{apply}";
		$form[]=$tpl->field_ipaddr("ipaddr", "{address}", null,true);
		$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM smtp_ipset WHERE ipaddr='$ipaddr'"));
		$description=$ligne["description"];
		$title=$ipaddr." - {$ligne["zdate"]}";
	}
	$form[]=$tpl->field_text("description", "{description}", $description);
	echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
}

function ipaddr_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$ipclass=new IP();
	$ipaddr=$_POST["ipaddr"];
	$date=date("Y-m-d H:i:s");
	$q=new postgres_sql();
	if(!$ipclass->IsACDIROrIsValid($ipaddr)){echo "$ipaddr not an IP/CDIR address";return;}
	$q->QUERY_SQL("INSERT INTO smtp_ipset (pattern,zdate,patype,automatic,enabled) VALUES ('$ipaddr','$date','0','0',1)");
	if(!$q->ok){echo $q->mysql_error;return;}

	$admin=$_SESSION["uid"];
	if($admin==-100){$admin="Manager";}
	$subject="Firewall rule created by $admin";
	$sql="INSERT INTO smtplog(zdate,ipaddr,reason,refused,smtp_code,subject) VALUES('$date','$ipaddr','FireWall Block',1,55,'$subject')";
	$q->QUERY_SQL($sql);

}


function search(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new postgres_sql();
	$t=time();
	
	$PATTERN[0]="-";
	$PATTERN[1]="SpamHaus";
	$PATTERN[2]="Spamhaus extended";
	$PATTERN[3]="Blocklist SMTP";
	$PATTERN[4]="Blocklist IMAP";
	$PATTERN[5]="AlienVault";

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress.log";
	$ARRAY["CMD"]="postfix2.php?postfix-ipset-compile=yes";
	$ARRAY["TITLE"]="{APP_FIREWALL} {compile_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=postfix-ipset')";
	
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?ipaddr-js=&function={$_GET["function"]}');\">";
	$html[]="<i class='fa fa-plus'></i> {new_address} </label>";
	
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsRestart;\">";
	$html[]="<i class='fa fa-plus'></i> {compile_rules} </label>";	
	
	$html[]="</div>";
	$search="";
	if(isset($_GET["search"])){$search=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
	$aliases["ipaddr"]="ipaddr";
	$querys=$tpl->query_pattern($search,$aliases);
	$MAX=$querys["MAX"];
	if($MAX==0){$MAX=150;}
	$sql="SELECT * FROM smtp_ipset {$querys["Q"]} ORDER BY zdate DESC LIMIT $MAX";
	
	if(preg_match("#^([0-9\.]+)#", $search)){
		$sql="SELECT * FROM smtp_ipset WHERE pattern='$search' ORDER BY zdate DESC LIMIT $MAX";
	}
	
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}
	if(!$results){
		echo $tpl->_ENGINE_parse_body($html);
		echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
		return;
	}

	if(pg_num_rows($results)==0){
		echo $tpl->_ENGINE_parse_body($html);
		echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
		return;
	}
	
	$TRCLASS=null;
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enable}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$ipaddr=$ligne["pattern"];
		$zDate=strtotime($ligne["zdate"]);
		$time=$tpl->time_to_date($zDate,true);
		$delete=$tpl->icon_nothing();
		$class_text=null;
		$patternencoded=urlencode($ipaddr);
		$enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable={$patternencoded}&md=$md')");
		if($ligne["automatic"]==0){
			$delete=$tpl->icon_delete("Loadjs('$page?delete={$ligne["ipaddr"]}&md=$md')");
		}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>{$time}</td>";
		$html[]="<td style='width:97%' nowrap><span class='$class_text'>$ipaddr ({$PATTERN[$ligne["patype"]]})</span></td>";
		$html[]="<td style='width:1%' class='center' nowrap>$enable</center></td>";
		$html[]="<td style='width:1%' class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";
	
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<small>$sql</small>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
?>
