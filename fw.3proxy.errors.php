<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["error"])){error_js();exit;}
if(isset($_GET["error-content"])){error_popup();exit;}
if(isset($_POST["3ProxyTemplateID"])){Save();exit;}
if(isset($_POST["error-id"])){error_save();exit;}
page();


function error_js(){
	$page=CurrentPageName();
	$ID=$_GET["error"];
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT `explain` FROM `3proxy_acls_templates` WHERE ID=$ID");
	$title=$ligne["explain"];
	
	$tpl->js_dialog1($title, "$page?error-content=$ID");
	
}

function error_popup(){
	$page=CurrentPageName();
	$ID=$_GET["error-content"];
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM `3proxy_acls_templates` WHERE ID=$ID");
	$title=$ligne["explain"];
	$tpl->field_hidden("error-id", $ID);
	$form[]=$tpl->field_text("title", "{subject}", $ligne["title"]);
	$form[]=$tpl->field_textareacode("content", "{content}", $ligne["content"]);
	echo $tpl->form_outside($title, $form,null,"{apply}",null,"AsFirewallManager",true);
}

function error_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ID=$_POST["error-id"];
	$title=$q->sqlite_escape_string2($_POST["title"]);
	$content=$q->sqlite_escape_string2($_POST["content"]);
	
	$sql="UPDATE `3proxy_acls_templates` SET `title`='$title', content='$content' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$rescan=$tpl->_ENGINE_parse_body("{rescan}");
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	$users=new usersMenus();
	$TITLE="{errors_pages}";
	$sock=new sockets();
	$SQUIDEnable=intval($sock->GET_INFO("SQUIDEnable"));
	if($SQUIDEnable==0){$TITLE="{templates} & {errors_pages}";}

	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>$TITLE</h1><p>{proxy_errors_pages_explain}</p></div>
	</div>
	<div class='row'>
	<div id='progress-pxtempl-restart'></div>
	";



	$html[]="
</div><div class='row'><div class='ibox-content'>
";

	$html[]="


	<div id='table-loader-errors-pages'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/universal-proxy-errors');
	LoadAjax('table-loader-errors-pages','$page?tabs=yes');
	</script>";

	if(isset($_GET["main-page"])){$tpl=new template_admin($TITLE,@implode("\n", $html));echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$SQUIDEnable=intval($sock->GET_INFO("SQUIDEnable"));
	
	
	$array["{template_manager}"]="fw.proxy.templates.manager.php";
	$array["{parameters}"]="$page?parameters=yes";
	$array["{errors_pages}"]="$page?table=yes";
	
	
	echo $tpl->tabs_default($array);
	
	
}

function parameters(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ProxyTemplateID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("3ProxyTemplateID"));
	$form[]=$tpl->field_templates("3ProxyTemplateID", "{template}", $ProxyTemplateID);
	
		
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/3proxy.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/3proxy.progress.log";
	$ARRAY["CMD"]="3proxy.php?reload=yes";
	$ARRAY["TITLE"]="{reloading_service}";
	//$ARRAY["AFTER"]="LoadAjax('table-3proxy-status','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-pxtempl-restart');";
	

	echo $tpl->form_outside("{proxy_error_pages}", $form,null,"{apply}",$jsrestart,
			"AsFirewallManager",true);
}

function Save(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$TRCLASS=null;
	$t=time();
	$ruleid=intval($_GET["table"]);
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="SELECT * FROM `3proxy_acls_templates` ORDER BY ID";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/3proxy.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/3proxy.progress.log";
	$ARRAY["CMD"]="3proxy.php?reload=yes";
	$ARRAY["TITLE"]="{reloading_service}";
	//$ARRAY["AFTER"]="LoadAjax('table-3proxy-status','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-pxtempl-restart');";
	
	$add="Loadjs('$page?rule-js=0&ruleid=$ruleid')";
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_parameters} </label>";
	$html[]="</div>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\"></div>";
	
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{subject}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$nothing=$tpl->icon_nothing();
	
	/*`explain` TEXT,
	`title` TEXT,
	`content` TEXT)";
	*/
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ID=$ligne["ID"];
		$md=md5(serialize($ligne));
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% nowrap>". $tpl->td_href($ligne["explain"],null,"Loadjs('$page?error=$ID')")."</td>";
		$html[]="<td>{$ligne["title"]}</td>";
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
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

