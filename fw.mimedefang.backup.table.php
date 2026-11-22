<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
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
table();

function zoom_js(){
	$q=new postgres_sql();
	$tpl=new template_admin();
	$page=CurrentPageName();
	// Pentest 2026-11-04 : zoom-js etait concatene dans la requete.
	$id=intval($_GET["zoom-js"]);
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM backupmsg WHERE id='$id'"));
	$subject=$ligne["subject"];
	
	if(function_exists("imap_mime_header_decode")){
		$elements = imap_mime_header_decode($subject);
		if(isset($elements[0])){
			if($elements[0]->text<>null){
				$subject=htmlentities($elements[0]->text);
				$subject=replace_accents($ligne["subject"]);
			}
	
		}
	}
	
	$tpl->js_dialog6($subject, "$page?zoom-popup=$id",900);
	
}

function delete_message_js(){
	$q=new postgres_sql();
	$tpl=new template_admin();
	$page=CurrentPageName();
	// Pentest 2026-11-04 : delete-message-js etait concatene dans la requete.
	$id=intval($_GET["delete-message-js"]);
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM backupmsg WHERE id='$id'"));
	$subject=$ligne["subject"];
	$zdate=$tpl->javascript_parse_text("{date}:")." ".$ligne["zdate"];
	$mailfrom=$tpl->javascript_parse_text("{from}:")." ".$ligne["mailfrom"];
	
	if(function_exists("imap_mime_header_decode")){
		$elements = imap_mime_header_decode($subject);
		if(isset($elements[0])){
			if($elements[0]->text<>null){
				$subject=utf8_encode($elements[0]->text);
			}
	
		}
	}
	$subject=str_replace("'", "`", $subject);
	
	$md=$_GET["md"];
	$tpl->js_confirm_delete("$zdate: $mailfrom", "delete-message", $id,"$('#$md').remove();");
	
	
}
function delete_message(){
	$q=new postgres_sql();
	$tpl=new templates();
	$q=new postgres_sql();
	// Pentest 2026-11-04 : delete-message etait concatene dans la requete.
	$id=intval($_POST["delete-message"]);
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM backupmsg WHERE id='$id'"));
	if(!$q->ok){echo $q->mysql_error;return;}
	$msgmd5=$ligne["msgmd5"];
	$mailfrom=$ligne["mailfrom"];
	$mailto=$ligne["mailto"];
	$msgid=$ligne["msgid"];
	$subject=$ligne["subject"];
	if(function_exists("imap_mime_header_decode")){
		$elements = imap_mime_header_decode($subject);
		if(isset($elements[0])){
			if($elements[0]->text<>null){
				$subject=utf8_encode($elements[0]->text);
			}
	
		}
	}
	$subject=str_replace("'", "`", $subject);
	
	// Pentest 2026-11-04 : msgmd5 (lu en base, issu du message) etait concatene dans la requete.
	$ligne=pg_fetch_array($q->QUERY_SQL_PARAMS("SELECT contentid FROM quardata WHERE msgmd5=$1",array($msgmd5)));
	if(!$q->ok){echo $q->mysql_error;return;}
	$contentid=intval($ligne["contentid"]);
	
	if($contentid>0){$q->QUERY_SQL("select lo_unlink($contentid)");}
	$q->QUERY_SQL_PARAMS("DELETE FROM quardata WHERE msgmd5=$1",array($msgmd5));
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL_PARAMS("DELETE FROM backupmsg WHERE msgmd5=$1",array($msgmd5));
	if(!$q->ok){echo $q->mysql_error;return;}
	
	include_once(dirname(__FILE__).'/ressources/class.maillog.tools.inc');
	$maillog=new maillog_tools();
	
	$ARRAY["MESSAGE_ID"]=$msgid;
	$ARRAY["HOSTNAME"]="localhost";
	$ARRAY["IPADDR"]="0.0.0.0";
	$ARRAY["SENDER"]=$mailfrom;
	$ARRAY["SUBJECT"]=$subject;
	$ARRAY["REJECTED"]="Removed from backup";
	$ARRAY["REFUSED"]=1;
	$ARRAY["SEQUENCE"]=55;
	$ARRAY["RECIPIENT"]=$mailto;
	$maillog->berkleydb_relatime_write($msgid,$ARRAY);
}

function html_message_js(){
	// Pentest 2026-11-04 : htmlmessg-js etait concatene dans la requete.
	$id=intval($_GET["htmlmessg-js"]);
	$q=new postgres_sql();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM backupmsg WHERE id='$id'"));
	$subject=$ligne["subject"];

	if(function_exists("imap_mime_header_decode")){
		$elements = imap_mime_header_decode($subject);
		if(isset($elements[0])){
			if($elements[0]->text<>null){
				$subject=htmlentities($elements[0]->text);
				$subject=replace_accents($ligne["subject"]);
			}

		}
	}
	$tpl->js_dialog7($subject, "$page?htmlmess-popup=$id",1200);

}
function html_message_popup(){
	$q=new postgres_sql();
	// Pentest 2026-11-04 : htmlmess-popup etait concatene dans la requete.
	$id=intval($_GET["htmlmess-popup"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$resend=$tpl->javascript_parse_text("{resend}");
	$t=time();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT htmlmess FROM backupmsg WHERE id='{$id}'"));

	$data=base64_decode($ligne["htmlmess"]);
	if(preg_match("#--X-Body-Begin-->(.+?)<\!--X-User-Footer-End-->#is", $data,$re)){
		$data=$re[1];
	}

	$data=str_ireplace("<!--X-Body-of-Message-->", "<!--X-Body-of-Message-->\n<div style='font-size:16px !important;width:98%' class=form>",$data);
	$data=str_ireplace("<!--X-Body-of-Message-End-->", "<!--X-Body-of-Message-End-->\n</div>",$data);
	// Pentest 2026-11-04 : corps HTML du message sauvegarde (donnee externe) affiche sans encodage (XSS stockee).
	// Rendu dans une iframe sandbox (aucun script) : la mise en forme du message est conservee.
	$srcdoc=req_html("<!DOCTYPE html><html><head></head><body>".$data."</body></html>");
	echo "<div class='ibox-content'><div class=row><iframe sandbox='' referrerpolicy='no-referrer' style='width:100%;height:75vh;border:0' srcdoc=\"$srcdoc\"></iframe></div></div>";

}

function resend_js(){
    if(!fw_progress_page_priv("AsPostfixAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
	$tpl=new template_admin();
	$page=CurrentPageName();
	$id=fw_progress_seg($_GET["resend"] ?? "");
	if($id===""){return fw_progress_bad_value("resend");}
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/mimedefang.resend.progress.$id";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/mimedefang.resend.progress.$id.log";
	$ARRAY["CMD"]="mimedefang.php?resend-backup=$id";
	$ARRAY["TITLE"]="{resend} N.$id";
	$ARRAY["AFTER"]=null;
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=resend-progress-$id')";
	$tpl->js_confirm_execute("{resend} N.$id", "resend", $id,$jsrestart);
	
}


function zoom_popup(){
	// Pentest 2026-11-04 : zoom-popup etait concatene dans la requete.
	$id=intval($_GET["zoom-popup"]);
	$q=new postgres_sql();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$resend=$tpl->javascript_parse_text("{resend}");
	$t=time();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM backupmsg WHERE id='$id'"));
	
	
	if(function_exists("imap_mime_header_decode")){
		$elements = imap_mime_header_decode($ligne["subject"]);
		if(isset($elements[0])){
			if($elements[0]->text<>null){
				$ligne["subject"]=replace_accents($elements[0]->text);
			}
	
		}
	}
	// Pentest 2026-11-04 : subject, mailfrom, mailto, msgmd5 (donnee externe) affiche sans encodage (XSS stockee).
	$ligne["subject"]=req_html($ligne["subject"]);
	$ligne["mailfrom"]=req_html($ligne["mailfrom"]);
	$ligne["mailto"]=req_html($ligne["mailto"]);
	$ligne["msgmd5"]=req_url($ligne["msgmd5"]);
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
	$html[]="</div>";
	$html[]="</div></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
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
	$.address.value('mail-backup');
	$.address.title('Artica: Backup on the fly');
</script>";

	

	$html[]=$script;
	$html[]=$tpl->search_block($page,"postgres","backupmsg","smtp-backup-fly",$urltoadd);

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
	$table="backupmsg";
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

	// Pentest 2026-11-04 : query2 etait un fragment SQL brut (base64) concatene dans la requete.
	// Aucune page ne l'envoie : il est ignore.
	if(false){
		$query="";
		if(preg_match("#tomail=#", $query)){$tomail_column=false;}
		if(preg_match("#reason=#", $query)){$reason_column=false;}
		$sql="SELECT * FROM $table $query ORDER BY id DESC LIMIT 250";
	}

	//zmd5,ip_addr,mailfrom,mailto,stime,hostname,whitelisted


	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." ".htmlspecialchars($q->mysql_error,ENT_QUOTES));return;}

	if(pg_num_rows($results)==0){
		
		echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
		return;
	}

	$TRCLASS=null;
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>ID</center></th>";
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
			// Pentest 2026-11-04 : msgid etait concatene dans la clause second-query (desormais signee et echappee).
			$js_msgid="Loadjs('fw.postfix.transactions.php?second-query=".signed_html_token("WHERE msgid='".pg_escape_string2($msgid)."'")."&title=".urlencode("{see_messages_with}:$msgid")."')";
			$msgid=$tpl->td_href($msgid,"{see_messages_with}:$msgid",$js_msgid);
			
		}
		$zdate=$tpl->time_to_date(strtotime($ligne["zdate"]),true);
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		if(function_exists("imap_mime_header_decode")){
			$elements = imap_mime_header_decode($ligne["subject"]);
			if(isset($elements[0])){
				if($elements[0]->text<>null){
					$ligne["subject"]=replace_accents($elements[0]->text);
				}
			}
		}
		// Pentest 2026-11-04 : subject (donnee externe) affiche sans encodage (XSS stockee).
		$ligne["subject"]=req_html($ligne["subject"]);
		
		
		$zdate=$tpl->td_href($zdate,"","Loadjs('$page?zoom-js=$id')");
		$ligne["mailfrom"]=$tpl->td_href($ligne["mailfrom"],"","Loadjs('$page?zoom-js=$id')");
		$ligne["mailto"]=$tpl->td_href($ligne["mailto"],"","Loadjs('$page?zoom-js=$id')");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' class='center' nowrap>".$tpl->td_href($id,"","Loadjs('$page?zoom-js=$id')")."</center></td>";
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
		$html[]="<td colspan='8'>";
		$html[]="<ul class='pagination pull-right'></ul>";
		$html[]="</td>";
		$html[]="</tr>";
		$html[]="</tfoot>";
		$html[]="</table>";
		$html[]="<small>".req_html($sql)."</small>
		<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";
		
		echo $tpl->_ENGINE_parse_body($html);
	}
	
function download(){
	
	$q=new postgres_sql();
	$zmd5=strval($_GET["download"]);
	// Pentest 2026-11-04 : download etait concatene dans la requete.
	if(!preg_match('/^[A-Za-z0-9_.-]{1,90}$/',$zmd5)){$zmd5="";}
	$sql="SELECT contentid FROM backupdata WHERE msgmd5='$zmd5'";
	if($GLOBALS["VERBOSE"]){echo "<hr>$sql<br>\n";}
	$ligne=$q->mysqli_fetch_array($sql);
	
	if(!$q->ok){
		if($GLOBALS["VERBOSE"]){echo "<hr>MySQL Error:".$q->mysql_error."<br>".die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");
	}
	
	$contentid=intval($ligne["contentid"]);
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
		die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");
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