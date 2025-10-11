<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["resend"])){resend_js();exit;}
if(isset($_POST["resend"])){exit;}
if(isset($_GET["htmlmessg-js"])){html_message_js();exit;}
if(isset($_GET["htmlmess-popup"])){html_message_popup();exit;}
if(isset($_GET["delete-message-js"])){delete_message_js();exit;}
if(isset($_POST["delete-message"])){delete_message();exit;}
if(isset($_GET["whitelist-email"])){whitelist_email_js();exit;}
if(isset($_POST["whitelist-email"])){whitelist_email();exit;}

if(isset($_GET["whitelist-domain"])){whitelist_domain_js();exit;}
if(isset($_POST["whitelist-domain"])){whitelist_domain();exit;}



table();



function zoom_js(){
	$q=new postgres_sql();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$id=$_GET["zoom-js"];
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM quarmsg WHERE id='$id'"));
	$subject=$tpl->decode_mime_string($ligne["subject"]);

	
	$tpl->js_dialog6($subject, "$page?zoom-popup=$id",900);
	
}

function delete_message_js(){
	$tpl=new template_admin();
	$id=$_GET["delete-message-js"];
	$q=new postgres_sql();
	$ligne=$q->mysqli_fetch_array("SELECT * FROM quarmsg WHERE id='$id'");
	$zdate=$tpl->javascript_parse_text("{date}:")." ".$ligne["zdate"];
	$mailfrom=$tpl->javascript_parse_text("{from}:")." ".$ligne["mailfrom"];
	$md=$_GET["md"];
	$tpl->js_confirm_delete("$zdate: $mailfrom", "delete-message", $id,"$('#$md').remove();");
}

function whitelist_email_js(){
    $tpl=new template_admin();
    $id=$_GET["whitelist-email"];
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT mailfrom FROM quarmsg WHERE id='$id'");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/mimedefang.resend.progress.$id";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/mimedefang.resend.progress.$id.log";
    $ARRAY["CMD"]="mimedefang.php?resend-quarantine=$id";
    $ARRAY["TITLE"]="{resend} N.$id";
    $ARRAY["AFTER"]=null;
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=resend-progress-$id')";


    $mailfrom=$tpl->javascript_parse_text("{whitelist} {from}:".$ligne["mailfrom"]." {and_resend_the_message}");
    $tpl->js_dialog_confirm_action("$mailfrom", "whitelist-email", $id,$jsrestart);
}
function whitelist_domain_js(){
    $tpl=new template_admin();
    $id=$_GET["whitelist-domain"];
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT mailfrom FROM quarmsg WHERE id='$id'");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/mimedefang.resend.progress.$id";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/mimedefang.resend.progress.$id.log";
    $ARRAY["CMD"]="mimedefang.php?resend-quarantine=$id";
    $ARRAY["TITLE"]="{resend} N.$id";
    $ARRAY["AFTER"]=null;
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=resend-progress-$id')";

    $trz=explode("@",$ligne["mailfrom"]);
    $ligne["mailfrom"]=$trz[1];

    $mailfrom=$tpl->javascript_parse_text("{whitelist} {from}: ".$ligne["mailfrom"]." {and_resend_the_message}");
    $tpl->js_dialog_confirm_action("$mailfrom", "whitelist-domain", $id,$jsrestart);

}


function delete_message(){
	$q=new postgres_sql();
	$tpl=new templates();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM quarmsg WHERE id='{$_POST["delete-message"]}'"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$msgmd5=$ligne["msgmd5"];
	$mailfrom=$ligne["mailfrom"];
	$mailto=$ligne["mailto"];
	$msgid=$ligne["msgid"];
	$subject=$tpl->decode_mime_string($ligne["subject"]);
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT contentid FROM quardata WHERE msgmd5='$msgmd5'"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$contentid=$ligne["contentid"];
	
	if($contentid>0){$q->QUERY_SQL("select lo_unlink($contentid)");}
	$q->QUERY_SQL("DELETE FROM quardata WHERE msgmd5='$msgmd5'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM quarmsg WHERE msgmd5='$msgmd5'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	include_once(dirname(__FILE__).'/ressources/class.maillog.tools.inc');
	$maillog=new maillog_tools();
	
	$ARRAY["MESSAGE_ID"]=$msgid;
	$ARRAY["HOSTNAME"]="localhost";
	$ARRAY["IPADDR"]="0.0.0.0";
	$ARRAY["SENDER"]=$mailfrom;
	$ARRAY["SUBJECT"]=$subject;
	$ARRAY["REJECTED"]="Removed from Quarantine";
	$ARRAY["REFUSED"]=1;
	$ARRAY["SEQUENCE"]=55;
	$ARRAY["RECIPIENT"]=$mailto;
	$maillog->berkleydb_relatime_write($msgid,$ARRAY);
}

function html_message_js(){
	$id=$_GET["htmlmessg-js"];
	$q=new postgres_sql();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT subject FROM quarmsg WHERE id='$id'"));
	$subject=$tpl->decode_mime_string($ligne["subject"]);
	$tpl->js_dialog7($subject, "$page?htmlmess-popup=$id",1200);

}
function html_message_popup(){
	$q=new postgres_sql();
	$id=$_GET["htmlmess-popup"];
	$tpl=new templates();
	$page=CurrentPageName();
	$resend=$tpl->javascript_parse_text("{resend}");
	$t=time();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT htmlmess FROM quarmsg WHERE id='{$id}'"));

	$data=$ligne["htmlmess"];
	if(preg_match("#--X-Body-Begin-->(.+?)<\!--X-User-Footer-End-->#is", $data,$re)){
		$data=$re[1];
	}

	$data=str_ireplace("<!--X-Body-of-Message-->", "<!--X-Body-of-Message-->\n<div style='font-size:16px !important;width:98%' class=form>",$data);
	$data=str_ireplace("<!--X-Body-of-Message-End-->", "<!--X-Body-of-Message-End-->\n</div>",$data);
	echo "<div class='ibox-content'><div class=row>$data</div></div>";

}

function resend_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$id=$_GET["resend"];
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/mimedefang.resend.progress.$id";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/mimedefang.resend.progress.$id.log";
	$ARRAY["CMD"]="mimedefang.php?resend-quarantine=$id";
	$ARRAY["TITLE"]="{resend} N.$id";
	$ARRAY["AFTER"]=null;
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=resend-progress-$id')";
	
	
	$tpl->js_confirm_execute("{resend} N.$id", "resend", $id,$jsrestart);
	
}


function zoom_popup(){
	$id=$_GET["zoom-popup"];
	$q=new postgres_sql();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$resend=$tpl->javascript_parse_text("{resend}");
	$t=time();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM quarmsg WHERE id='$id'"));
	
	
	if(function_exists("imap_mime_header_decode")){
		$elements = imap_mime_header_decode($ligne["subject"]);
		if(isset($elements[0])){
			if($elements[0]->text<>null){
				$ligne["subject"]=htmlentities($elements[0]->text);
				$ligne["subject"]=replace_accents($ligne["subject"]);
			}
	
		}
	}
	$html[]="<div class='ibox-content'><div class=row><div id='resend-progress-$id'></div>";
	$html[]="<div class='col-md-6'>";
	$html[]="<H3 class='font-bold m-b-xs'>{$ligne["subject"]}</h3>";
	$html[]="<dl class='dl-horizontal m-t-md'>";
	$html[]="<dt>{size}:</dt><dd>". FormatBytes($ligne["size"]/1024)."</dd>";
	$html[]="<dt>{from}:</dt><dd>{$ligne["mailfrom"]}</dd>";
	$html[]="<dt>{to}:</dt><dd>{$ligne["mailto"]}</dd>";
	$html[]="<dt>{retention}:</dt><dd>".date("Y {l} {F} d",$ligne["final"])."</dd>";
	$html[]="</dl>";
	$html[]="</div>";





	
	button("{resend}","Resend$t()",26).
	
	$html[]="<div class='col-md-5'>";
	$html[]="<center style='margin:5px'>".$tpl->button_autnonome("{view_message}", "Loadjs('$page?htmlmessg-js={$ligne["id"]}')", "fas fa-eye",null,220)."</center>";
	$html[]="<center style='margin:5px'>".$tpl->button_autnonome("{download2}", "document.location.href='$page?download={$ligne["msgmd5"]}'", "fas fa-download",null,220)."</center>";
	$html[]="<center style='margin:5px'>".$tpl->button_autnonome("{resend}", "Loadjs('$page?resend={$ligne["id"]}')", "fas fa-share-square",null,220)."</center>";
    $html[]="<hr>";
    $html[]="<center style='margin:5px'>".$tpl->button_autnonome("{whitelist} {email}", "Loadjs('$page?whitelist-email={$ligne["id"]}')", "fas fa-thumbs-up",null,220)."</center>";
    $html[]="<center style='margin:5px'>".$tpl->button_autnonome("{whitelist} {domain}", "Loadjs('$page?whitelist-domain={$ligne["id"]}')", "fas fa-thumbs-up",null,220)."</center>";

	$html[]="</div>";
	$html[]="</div></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function whitelist_email(){

    $id=$_POST["whitelist-email"];
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT mailfrom FROM quarmsg WHERE id='$id'");
    $pattern=$ligne["mailfrom"];
    $patternNone=str_replace("*@", "", $pattern);
    $tpl=new template_admin();
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM miltergreylist_acls WHERE method='blacklist' AND pattern='$pattern'");

    $userid=$_SESSION["uid"];
    if($userid==-100){$userid="Manager";}

    $sql="SELECT id FROM miltergreylist_acls ORDER BY id desc LIMIT 1";
    $ligne=$q->mysqli_fetch_array($sql);
    $lastid=$ligne["id"];
    $lastid++;

    $q->QUERY_SQL("INSERT INTO miltergreylist_acls (id,zdate,method,type,pattern,description) VALUES($lastid,NOW(),'whitelist','from','$pattern','By $userid')");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $zmd5=md5("$patternNone*");
    $q->QUERY_SQL("INSERT INTO autowhite (zmd5,mailfrom,mailto) VALUES ('$zmd5','$patternNone','*')");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $sock=new sockets();
    $sock->getFrameWork("postfix.php?smtpd-client-restrictions=yes");
}
function whitelist_domain(){

    $id=$_POST["whitelist-domain"];
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT mailfrom FROM quarmsg WHERE id='$id'");
    $tbl=explode("@",$ligne["mailfrom"]);

    $pattern="*@{$tbl[1]}";
    $patternNone=str_replace("*@", "", $pattern);
    $tpl=new template_admin();
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM miltergreylist_acls WHERE method='blacklist' AND pattern='$pattern'");

    $userid=$_SESSION["uid"];
    if($userid==-100){$userid="Manager";}

    $sql="SELECT id FROM miltergreylist_acls ORDER BY id desc LIMIT 1";
    $ligne=$q->mysqli_fetch_array($sql);
    $lastid=$ligne["id"];
    $lastid++;

    $q->QUERY_SQL("INSERT INTO miltergreylist_acls (id,zdate,method,type,pattern,description) VALUES($lastid,NOW(),'whitelist','from','$pattern','By $userid')");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $zmd5=md5("$patternNone*");
    $q->QUERY_SQL("INSERT INTO autowhite (zmd5,mailfrom,mailto) VALUES ('$zmd5','$patternNone','*')");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $sock=new sockets();
    $sock->getFrameWork("postfix.php?smtpd-client-restrictions=yes");
}
function table(){
	$viarbl=null;
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$urltoadd=null;
	$html[]="

	</div>
	<div class='ibox-content'>
	<div id='postfix-transactions'></div>

	</div>
	</div>
	";

	$script="<script>
	$.address.state('/');
	$.address.value('quarantine');
	$.address.title('Artica: Quarantine');
</script>";

	

	$html[]=$script;
	$html[]=$tpl->search_block($page,"postgres","quarmsg","smtp-quarantine",$urltoadd);

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new postgres_sql();
	$t=time();
	$table="quarmsg";
	$tomail_column=true;
	$reason_column=true;
	$mem=new lib_memcached();
	$IntelligentSearch=null;
	$search=trim($_GET["search"]);
	$MAX=150;
	


	/*$html[]=$tpl->_ENGINE_parse_body("
	 <div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"javascript:$jsApply\">
			<i class='fa fa-save'></i> {analyze_database} </label>
			</div>");
	*/

	if(!isset($querys["Q"])){
		$querys=$tpl->query_pattern($search);
		$MAX=$querys["MAX"];
		if($MAX==0){$MAX=150;}
	}

	
	$sql="SELECT * FROM $table {$querys["Q"]} ORDER BY id DESC LIMIT $MAX";

	if(isset($_GET["query2"])){
		$query=base64_decode($_GET["query2"]);
		if(preg_match("#tomail=#", $query)){$tomail_column=false;}
		if(preg_match("#reason=#", $query)){$reason_column=false;}
		$sql="SELECT * FROM $table $query ORDER BY id DESC LIMIT 250";
	}

	//zmd5,ip_addr,mailfrom,mailto,stime,hostname,whitelisted


	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}

	if(pg_num_rows($results)==0){
		
		echo $tpl->FATAL_ERROR_SHOW_128("{no_data}<br><small>$sql</small>");
		return;
	}

	$TRCLASS=null;
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{message_id}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{from}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{to}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{subject}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$INFOS=array();
		$md=md5(serialize($ligne));
		$color="#000000";
		$status=null;
		$id=$ligne["id"];
		$msgid=$ligne["msgid"];
		if($msgid==null){$msgid=$tpl->icon_nothing();}else{
			$js_msgid="Loadjs('fw.postfix.transactions.php?second-query=".base64_encode("WHERE msgid='$msgid'")."&title=".urlencode("{see_messages_with}:$msgid")."')";
			$msgid=$tpl->td_href($msgid,"{see_messages_with}:$msgid",$js_msgid);
			
		}
		$zdate=$tpl->time_to_date(strtotime($ligne["zdate"]),true);
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		if(function_exists("imap_mime_header_decode")){
			$elements = imap_mime_header_decode($ligne["subject"]);
			if(isset($elements[0])){
				if($elements[0]->text<>null){
					$ligne["subject"]=htmlentities(replace_accents($elements[0]->text));
				}
			}
		}
		
		
		$zdate=$tpl->td_href($zdate,"","Loadjs('$page?zoom-js=$id')");
		$ligne["mailfrom"]=$tpl->td_href($ligne["mailfrom"],"","Loadjs('$page?zoom-js=$id')");
		$ligne["mailto"]=$tpl->td_href($ligne["mailto"],"","Loadjs('$page?zoom-js=$id')");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>{$zdate}</td>";
		$html[]="<td style='width:1%' nowrap>{$msgid}</td>";
		
		$html[]="<td style='width:1%' nowrap>{$ligne["mailfrom"]}</td>";
		$html[]="<td style='width:1%' nowrap>{$ligne["mailto"]}</td>";
		$html[]="<td>{$ligne["subject"]}</td>";
		$html[]="<td style='width:1%' nowrap>{$ligne["size"]}</td>";
		$html[]="<td style='width:1%'><center>".$tpl->icon_delete("Loadjs('$page?delete-message-js=$id&md=$md')")."</center></td>";
		$html[]="</tr>";
		
	}
		$html[]="</tbody>";
		$html[]="<tfoot>";
		$html[]="<tr>";
		$html[]="<td colspan='7'>";
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
	
	function download(){
	
		$q=new postgres_sql();
		$zmd5=$_GET["download"];
		$sql="SELECT contentid FROM quardata WHERE msgmd5='$zmd5'";
		if($GLOBALS["VERBOSE"]){echo "<hr>$sql<br>\n";}
		$ligne=$q->mysqli_fetch_array($sql);
	
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "<hr>MySQL Error:".$q->mysql_error."<br>".die("DIE " .__FILE__." Line: ".__LINE__);}
			header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
	
		$contentid=$ligne["contentid"];
		if($GLOBALS["VERBOSE"]){echo "<hr>contentid:&laquo;$contentid&raquo;<br>";}
		@mkdir("/usr/share/artica-postfix/ressources/conf/upload",0777,true);
		@chmod("/usr/share/artica-postfix/ressources/conf/upload",0777);
	
		if(is_file("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz")){
			@unlink("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz");
		}
	
		$sql="select lo_export($contentid, '/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz')";
		if($GLOBALS["VERBOSE"]){echo "<hr>$sql<br>\n";}
	
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "<hr>MySQL Error:".$q->mysql_error."<br>";}
			header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
		if($GLOBALS["VERBOSE"]){echo "<hr>OK /usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz<br>\n";}
		if(!$GLOBALS["VERBOSE"]){
			header('Content-type: '."application/x-gzip");
			header('Content-Transfer-Encoding: binary');
			header("Content-Disposition: attachment; filename=\"$zmd5.gz\"");
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
		}
	
		if($GLOBALS["VERBOSE"]){
			if(!is_file("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz")){
				echo "<hr>/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz no such file<br>\n";
			}else{
				echo "<hr>/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz Exists<br>\n";
			}
	
		}
		if($GLOBALS["VERBOSE"]){echo "<hr>filesize()...<br>\n";}
		$fsize = @filesize("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz");
		if($GLOBALS["VERBOSE"]){echo "<hr>fsize:$fsize<br>\n";}
		header("Content-Length: ".$fsize);
		ob_clean();
		flush();
		readfile("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz");
		@unlink("/usr/share/artica-postfix/ressources/conf/upload/$zmd5.gz");
	}	
?>