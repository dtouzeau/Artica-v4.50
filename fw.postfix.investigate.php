<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsPostfixAdministrator){exit();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["query-js"])){query_js();exit;}
if(isset($_GET["query-popup"])){query_popup();exit;}
if(isset($_POST["FROM_DATE"])){query_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
page();

function query_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$tpl->js_dialog1("{new_query}", "$page?query-popup=yes");
	
}
function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 : delete-js etait recopie sans encodage dans la page (XSS) ; c'est l'ID entier de postfix_search.
	$id=intval($_GET["delete-js"]);
	$tpl->js_confirm_delete($id, "delete", $id,"$('#{$_GET["md"]}').remove()");
}
function delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("postfix2.php?history-delete={$_POST["delete"]}");
	$q=new lib_sqlite("/home/artica/SQLITE/postfix_events.db");
	// Pentest 2026-11-04 : delete etait concatene dans la requete.
	$deleteID=intval($_POST["delete"]);
	$q->QUERY_SQL("DELETE FROM postfix_search WHERE ID='$deleteID'");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function query_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$RunInvestigate=RunInvestigate();
	$form[]=$tpl->field_date("FROM_DATE","{from_date}",null);
	$form[]=$tpl->field_clock("FROM_TIME", "{from_time}", "00:00");
	$form[]=$tpl->field_numeric("maxlines", "{max_lines}", 200);
	$form[]=$tpl->field_text("STRING","{search}",null,true);
	echo $tpl->form_outside("{new_query}", $form,"{postfix_search_history_explain}","{search}","dialogInstance1.close();LoadAjax('table-postfix-investigate','$page?table=yes');$RunInvestigate","AsPostfixAdministrator");
	
}

function query_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix_events.db");
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `postfix_search` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`status` integer,
			`maxlines` integer,
			time integer,
			therms text,
			fsize INTEGER,
			fpath text ) ");
	
	$time=strtotime($_POST["FROM_DATE"]." ".$_POST["FROM_TIME"] );
	$_POST["STRING"]=sqlite_escape_string2($_POST["STRING"]);
	
	// Pentest 2026-11-04 : maxlines etait concatene dans la requete.
	$maxlines=intval($_POST["maxlines"]);
	$q->QUERY_SQL("INSERT INTO postfix_search (`time`,`therms`,`status`,`maxlines`) VALUES ('$time','{$_POST["STRING"]}',0,'$maxlines')");
	if(!$q->ok){echo $q->mysql_error."<br>";}
	
	
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{messaging} {investigate}</h1><i>{mail_investigate_explain}</i></div>
	</div>



	<div class='row'><div id='progress-postfix-investigate'></div>
	<div class='ibox-content'>

	<div id='table-postfix-investigate'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/mail-investigate');
	LoadAjax('table-postfix-investigate','$page?table=yes');

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,@implode("\n", $html));
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

function RunInvestigate(){
	$page=CurrentPageName();
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/postfix.events.search.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/postfix.events.search.progress.txt";
	$ARRAY["CMD"]="postfix2.php?history-search=yes";
	$ARRAY["TITLE"]="{history} {search}";
	$ARRAY["AFTER"]="LoadAjax('table-postfix-investigate','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsApply="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-postfix-investigate')";
	return $jsApply;
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix_events.db");
	
	
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?query-js=yes');\">
				<i class='fa fas fa-search-plus'></i> {new_query} 
			</label>
			<label class=\"btn btn btn-warning\" OnClick=\"javascript:".RunInvestigate()."\">
				<i class='fas fa-play'></i> {run_search} 
			</label>			
			</div>";

	
	$results=$q->QUERY_SQL("SELECT * FROM postfix_search ORDER BY `time` DESC");

	$rows=array();
	foreach ($results as $index=>$ligne){
		$rows[]=pfxinv_row($tpl,$page,$ligne);
	}

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{search}</th>";
	$html[]="<th class='center'>{status}</th>";
	$html[]="<th>{size}</th>";
	$html[]="<th>{from_date}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='pfxinv-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$shown=count($rows);
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown / $shown</span>";
	$html[]="<span class='awc-pg' id='pfxinv-pager'></span>";
	$html[]="</div></div></div>";

	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.awc_pager_js(50,"pfxinv-rows","pfxinv-pager");
}

/** Pastille d'etat d'une recherche differee. */
function pfxinv_status_badge($int_status):string{
    switch(intval($int_status)){
        case 1: return "<span class='awc-badge' style='background:#006a62'>{running}</span>";
        case 2: return "<span class='awc-badge awc-badge-on'>{completed}</span>";
        case 3: return "<span class='awc-badge' style='background:#ba1a1a'>{error}</span>";
    }
    return "<span class='awc-badge awc-badge-off'>{scheduled}</span>";
}

/**
 * Une recherche: les termes forment l'identite. Ils viennent de l'operateur et
 * etaient rendus BRUTS dans un <code> -- a la fois une balise proscrite par le
 * theme et un vecteur de XSS stocke.
 */
function pfxinv_row($tpl,$page,$ligne):string{
	$ID=intval($ligne["ID"]);
	$md=md5(serialize($ligne));
	$therms=htmlspecialchars((string)$ligne["therms"]);
	$done=(intval($ligne["fsize"])>0);

	// Le lien vers le journal n'a de sens que si le fichier a ete produit.
	$name=$done
		?"<a href=\"/postfix-search/$ID.log\">$therms</a>"
		:$therms;
	$download=$done?$tpl->icon_download("direct:/postfix-search/$ID.log","AsPostfixAdministrator"):$tpl->icon_nothing();
	$del=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsPostfixAdministrator");

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='".($done?"awc-ico":"awc-ico awc-ico-off")."'><i class='fas fa-magnifying-glass'></i></span>";
	$h.="<span><span class='awc-name' style='font-family:monospace'>$name</span></span>";
	$h.="</div></td>";
	$h.="<td class='center'>".pfxinv_status_badge($ligne["status"])."</td>";
	$h.="<td><span class='awc-seen'>".FormatBytes($ligne["fsize"]/1024)."</span></td>";
	$h.="<td class='awc-mono'>".htmlspecialchars($tpl->time_to_date($ligne["time"],true))."</td>";
	$h.="<td><div class='awc-acts'>$download$del</div></td>";
	$h.="</tr>";
	return $h;
}
