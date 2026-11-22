<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
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
	// Pentest 2026-11-04 : report-js etait concatene dans la requete.
	$ID=intval($_GET["report-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("SELECT subject FROM amavisd_tests WHERE ID=$ID");
	$title=$ligne["subject"];
	$tpl->js_dialog7($title, "$page?report-popup=$ID",900);
}
function report_popup(){
    if(!fw_progress_page_priv("AsPostfixAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
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
		foreach ($tbl as $index=>$line){
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
				
	
				
			// Pentest 2026-11-04 : resultat SpamAssassin (message analyse, donnee externe) : req_html, octets Latin-1 apres utf8_decode().
			$line=req_html($line);
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
	$jsrestart="dialogInstance7.close();Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=spamssass-table-progress')";
	$btn=$tpl->button_autnonome("{rescan}", $jsrestart, "fas fa-retweet-alt");
	
	
	// Pentest 2026-11-04 : subject (message analyse, donnee externe) affiche sans encodage (XSS stockee).
	$html="
	<div style='float:right'>$btn</div>
	<H2>".req_html($ligne["subject"])." $bytes</H2>
	$content";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function report_delete(){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	// Pentest 2026-11-04 : delete-js etait concatene dans la requete.
	$ID=intval($_GET["delete-js"]);
	$q->QUERY_SQL("DELETE FROM amavisd_tests WHERE ID='$ID'");
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
	echo "$('#".req_js($_GET["md"])."').remove();";
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
	// Pentest 2026-11-04 : message-js etait concatene dans la requete.
	$id=intval($_GET["message-js"]);
	$sql="SELECT subject FROM amavisd_tests WHERE ID=$id";
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$tpl->js_dialog7("{$ligne["subject"]}", "$page?new-message-popup=$id",900);
}

function new_message_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 : new-message-popup etait concatene dans la requete.
	$ID=intval($_GET["new-message-popup"]);
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/spamassassin.analyze.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/spamassassin.analyze.logs";
	$ARRAY["CMD"]="mimedefang.php?spamass-test=yes";
	$ARRAY["TITLE"]="{analyze_message}";
	$ARRAY["AFTER"]="LoadAjax('spamssass-table-abalyze','$page?table=yes')";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=spamssass-table-progress')";
	
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
	$subject=(string)$decoded[0]["Headers"]["subject:"];
	$Date=strtotime($decoded[0]["Headers"]["Date:"]);
	$sender=$decoded[0]["Headers"]["from:"];
	$recipients=$decoded[0]["Headers"]["to:"];
	$message=base64_encode($_POST["message"]);
	
	
	if(preg_match("#<(.+?)>#", $sender,$re)){$sender=$re[1];}
	if(preg_match("#<(.+?)>#", $recipients,$re)){$recipients=$re[1];}
	if($Date==0){$Date=time();}
$saved_date=date("Y-m-d H:i:s",$Date);
	// Pentest 2026-11-04 : from/to du message soumis etaient concatenes dans la requete.
	$q->QUERY_SQL_PARAMS("INSERT INTO amavisd_tests (sender,recipients,message,saved_date,subject)
		VALUES (?,?,?,?,?)",array((string)$sender,(string)$recipients,$message,$saved_date,$subject));
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
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&spamssass-table-progress')";
	
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
	
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{subject}</th>";
	$html[]="<th class='center'>{status}</th>";
	$html[]="<th>{date}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='pfxsa-rows'>";
	
	$results=$q->QUERY_SQL("SELECT * FROM amavisd_tests ORDER BY ID DESC");
	
	
	foreach ($results as $index=>$ligne){
		$TRCLASS=null;
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
		$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=spamssass-table-progress')";
		
		
		
		
		
		$js="Loadjs('$page?message-js=$ID')";
		$report=$tpl->icon_run($jsrestart);
		$status=$tpl->icon_nothing();
		
		if($ligne["subject"]==null){$ligne["subject"]="{subject}:{unknown}";}
		$recp=explode(",",$ligne["recipients"]);
		$rcpt_text=@implode("<br>",$recp);
		
		if($ligne["finish"]==1){
				$report=$tpl->icon_excel("Loadjs('$page?report-js=$ID')");
				$status="100%";
		}
		
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')");
		// Le sujet etait tronque a 20 caracteres pour tenir dans une colonne
		// etroite: en colonne d'identite il tient en entier.
		$html[]=pfxsa_row($md,$js,(string)$ligne["subject"],(string)$ligne["sender"],$rcpt_text,$date,$status,$report.$delete,intval($ligne["finish"]));
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span class='awc-pg' id='pfxsa-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.awc_pager_js(50,"pfxsa-rows","pfxsa-pager");	
	
	
}


/**
 * Un message soumis a l'analyse: sujet en identite, expediteur et destinataires
 * en sous-titre. Sujet et adresses viennent du message analyse -- donc d'un tiers:
 * ils etaient rendus BRUTS sur une page dont le but est justement d'inspecter du
 * courrier suspect.
 */
function pfxsa_row($md,$js,$subject,$sender,$rcpt_text,$date,$status,$actions,$finished):string{
    $subject=trim($subject);
    $rcpts=trim(strip_tags(str_replace("<br>",", ",$rcpt_text)));

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='".($finished?"awc-ico":"awc-ico awc-ico-off")."'><i class='fas fa-envelope-open-text'></i></span>";
    $h.="<span><span class='awc-name'><a href=\"javascript:blur();\" title='{view}' OnClick=\"$js\">"
        .($subject===""?"{subject}: {unknown}":htmlspecialchars($subject))."</a></span>";
    $meta=array();
    if(trim($sender)!==""){$meta[]=htmlspecialchars(trim($sender));}
    if($rcpts!==""){$meta[]="&rarr; ".htmlspecialchars($rcpts);}
    if(count($meta)>0){$h.="<div class='awc-meta'>".@implode(" ",$meta)."</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center'>".($finished
        ?"<span class='awc-badge awc-badge-on'>100%</span>"
        :"<span class='awc-badge awc-badge-off'>&mdash;</span>")."</td>";
    $h.="<td class='awc-mono'>".htmlspecialchars($date)."</td>";
    $h.="<td><div class='awc-acts'>$actions</div></td>";
    $h.="</tr>";
    return $h;
}
