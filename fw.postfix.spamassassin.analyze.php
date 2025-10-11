<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.mime.parser.inc");
include_once(dirname(__FILE__)."/ressources/class.imap.rfc822_addresses.inc");
if(isset($_GET["popup"])){popup();exit();}
if(isset($_GET["table"])){table();exit();}
if(isset($_GET["new-message-js"])){new_message_js();exit;}
if(isset($_GET["message-js"])){message_js();exit;}
if(isset($_GET["new-message-popup"])){new_message_popup();exit;}
if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}

if(isset($_GET["delete-js"])){report_delete();exit;}

if(isset($_POST["ID"])){new_message_save();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{APP_SPAMASSASSIN}::{message_analyze}", "$page?popup=yes",1024);
}
function report_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["report-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("SELECT subject FROM amavisd_tests WHERE ID=$ID");
	$title=$ligne["subject"];
	$tpl->js_dialog7($title, "$page?report-popup=$ID",900);
}
function report_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["report-popup"];
	if(!is_numeric($ID)){return null;}
	$sql="SELECT subject,amavisd_results FROM amavisd_tests WHERE ID=$ID";
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$spamassassin_results=base64_decode($ligne["amavisd_results"]);
	$spamassassin_results=utf8_decode($spamassassin_results);
	$bytes=strlen($spamassassin_results);
	$tbl=explode("\n",$spamassassin_results);
	if(is_array($tbl)){
		while (list ($index, $line) = each ($tbl) ){
			$line=trim($line);
			if($line==null){continue;}
			$fontsize=10;
			$fontcolor="005447";
			$fontweight="normal";
			if(preg_match("#Content analysis details#i", $line)){
				$fontweight="bold";
				$fontsize="12";
				$fontcolor="d32d2d";
			}
				
			if(preg_match("#([0-9\.])+\s+([A-Z0-9_]+)\s+#", $line)){
				$fontweight="bold";
				$fontsize="10";
				$fontcolor="2975b8";
			}
				
	
				
			$line=htmlentities($line);
			$line=str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$line);
			$line=str_replace(" ","&nbsp;",$line);
			$content=$content."<div style='margin-top:5px;color:$fontcolor;font-weight:$fontweight'><code style='font-size:{$fontsize}px'>$line</code></div>\n";
	
				
		}
			
	
			
	}
	
	$bytes=FormatBytes($bytes/1024);
	
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/spamassassin.analyze.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/spamassassin.analyze.logs";
	$ARRAY["CMD"]="mimedefang.php?spamass-test=$ID";
	$ARRAY["TITLE"]="{analyze_message}";
	$ARRAY["AFTER"]="LoadAjax('spamssass-table-abalyze','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="dialogInstance7.close();Loadjs('fw.progress.php?content=$prgress&mainid=spamssass-table-progress')";
	$btn=$tpl->button_autnonome("{rescan}", $jsrestart, "fas fa-retweet-alt");
	
	
	$html="
	<div style='float:right'>$btn</div>
	<H2>{$ligne["subject"]} $bytes</H2>
	$content";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function report_delete(){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$q->QUERY_SQL("DELETE FROM amavisd_tests WHERE ID='{$_GET["delete-js"]}'");
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
	echo "$('#{$_GET["md"]}').remove();";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	echo "
	<div id='spamssass-table-progress'></div>		
	<div id='spamssass-table-abalyze'></div>
	<script>LoadAjax('spamssass-table-abalyze','$page?table=yes');</script>";		
}

function new_message_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog7("{APP_SPAMASSASSIN}::{new_message}", "$page?new-message-popup=0",900);
}
function message_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["message-js"];
	$sql="SELECT subject FROM amavisd_tests WHERE ID=$id";
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$tpl->js_dialog7("{$ligne["subject"]}", "$page?new-message-popup=$id",900);
}

function new_message_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["new-message-popup"];
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/spamassassin.analyze.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/spamassassin.analyze.logs";
	$ARRAY["CMD"]="mimedefang.php?spamass-test=yes";
	$ARRAY["TITLE"]="{analyze_message}";
	$ARRAY["AFTER"]="LoadAjax('spamssass-table-abalyze','$page?table=yes')";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=spamssass-table-progress')";
	
	$source_message_explain=$tpl->_ENGINE_parse_body("{source_message_explain}");
	$btn="{apply}";
	$js[]="$jsrestart";
	
	if($ID>0){
		$sql="SELECT * FROM amavisd_tests WHERE ID=$ID";
		$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
		$ligne=$q->mysqli_fetch_array($sql);
		$message=base64_decode($ligne["message"]);
		$title=$ligne["subject"];
	}
	
	
	if($ID==0){
		$title="{new_message}";
		$message=$source_message_explain;
		$btn="{add}";
		$js[]="dialogInstance7.close();";
	}
	
	
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_textarea("message", "{message}", $message);
	echo $tpl->form_outside($title, $form,$source_message_explain,$btn,@implode(";", $js),null);
}
function new_message_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	
	$mime=new mime_parser_class();
	$mime->decode_bodies = 0;
	$mime->ignore_syntax_errors = 1;
	$parameters['Data']=$_POST["message"];
	$parameters['SkipBody']=1;
	$decoded=array();
	$mime->Decode($parameters, $decoded);
	$subject=$q->sqlite_escape_string2($decoded[0]["Headers"]["subject:"]);
	$Date=strtotime($decoded[0]["Headers"]["Date:"]);
	$sender=$decoded[0]["Headers"]["from:"];
	$recipients=$decoded[0]["Headers"]["to:"];
	$message=base64_encode($_POST["message"]);
	
	
	if(preg_match("#<(.+?)>#", $sender,$re)){$sender=$re[1];}
	if(preg_match("#<(.+?)>#", $recipients,$re)){$recipients=$re[1];}
	if($Date==0){$Date=time();}
$saved_date=date("Y-m-d H:i:s",$Date);
	$q->QUERY_SQL("INSERT INTO amavisd_tests (sender,recipients,message,saved_date,subject)
		VALUES ('$sender','$recipients','$message','$saved_date','$subject')");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/spamassassin.analyze.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/spamassassin.analyze.logs";
	$ARRAY["CMD"]="mimedefang.php?spamass-test=yes";
	$ARRAY["TITLE"]="{analyze_message}";
	$ARRAY["AFTER"]="LoadAjax('spamssass-table-abalyze','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&spamssass-table-progress')";
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `amavisd_tests` (
			  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `sender` TEXT,
			  `recipients` TEXT,
			  `message` TEXT,
			  `amavisd_results` TEXT,
			  `spamassassin_results` TEXT,
			  `spamassassin_results_header` TEXT,
			  `spamassassin_score` TEXT,
			  `sanlearn` INTEGER,
			  `finish` INTEGER NOT NULL DEFAULT '0',
			  `saved_date` TEXT NOT NULL,
			  `subject` TEXT NOT NULL)");
	
	
	$html[]=$tpl->_ENGINE_parse_body("
	 <div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?new-message-js=yes');\">
			<i class='fa fa-plus'></i> {new_message} </label>
			</div>");
	
	$TRCLASS=null;
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sender}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{recipients}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{subject}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{report}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$results=$q->QUERY_SQL("SELECT * FROM amavisd_tests ORDER BY ID DESC");
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$ID=$ligne["ID"];
		$saved_date=strtotime($ligne["saved_date"]);
		$date=$tpl->time_to_date($saved_date);
		
		$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/spamassassin.analyze.progress";
		$ARRAY["LOG_FILE"]=PROGRESS_DIR."/spamassassin.analyze.logs";
		$ARRAY["CMD"]="mimedefang.php?spamass-test=$ID";
		$ARRAY["TITLE"]="{analyze_message}";
		$ARRAY["AFTER"]="BootstrapDialog1.close();";
		$prgress=base64_encode(serialize($ARRAY));
		$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=spamssass-table-progress')";
		
		
		
		
		
		$js="Loadjs('$page?message-js=$ID')";
		$report=$tpl->icon_run($jsrestart);
		$status=$tpl->icon_nothing();
		
		if($ligne["subject"]==null){$ligne["subject"]="{subject}:{unknown}";}
		$recp=explode(",",$ligne["recipients"]);
		$rcpt_text=@implode($recp,"<br>");
		
		if($ligne["finish"]==1){
				$report=$tpl->icon_excel("Loadjs('$page?report-js=$ID')");
				$status="100%";
		}
		
		if($ligne["subject"]<>null){
			$subj=$ligne["subject"];
			$subj_length=strlen($subj);
			if($subj_length>20){$subj=substr($subj, 0,17)."...";}
			$subj=$tpl->td_href($subj,$ligne["subject"],$js);
		}
		
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>".$tpl->td_href($date,"{view}",$js)."</td>";
		$html[]="<td style='width:1%' nowrap>{$ligne["sender"]}</td>";
		$html[]="<td style='width:1%' nowrap>{$rcpt_text}</td>";
		$html[]="<td>$subj</td>";
		$html[]="<td style='width:1%' class='center' nowrap>$report</center></td>";
		$html[]="<td style='width:1%' class='center' nowrap>$status</center></td>";
		$html[]="<td style='width:1%' class='center' nowrap>$delete</center></td>";
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
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}