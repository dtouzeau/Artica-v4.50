<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.3proxy.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

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
	$ligne=uproxy_template($ID);
	$title=$ligne["explain"];
	
	$tpl->js_dialog1($title, "$page?error-content=$ID");
	
}

function error_popup(){
	$page=CurrentPageName();
	$ID=$_GET["error-content"];
	$tpl=new template_admin();
	$ligne=uproxy_template($ID);
	$title=$ligne["explain"];
	$tpl->field_hidden("error-id", $ID);
	$form[]=$tpl->field_text("title", "{subject}", $ligne["title"]);
	$form[]=$tpl->field_textareacode("content", "{content}", $ligne["content"]);
	echo $tpl->form_outside($title, $form,null,"{apply}",null,"AsFirewallManager",true);
}

function error_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["error-id"]);
	$e=uproxy_error(uproxy_template_save($ID,array(
		"title"=>$_POST["title"],
		"content"=>$_POST["content"])));
	if($e!=""){echo $tpl->post_error($e);return;}
	
	
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
	
		
	$jsrestart=$tpl->framework_buildjs("/3proxy/reload",
		"3proxy.progress",
		"3proxy.progress.log",
		"progress-pxtempl-restart");
	

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
	
	$results=uproxy_templates();
	
	
	
	$jsrestart=$tpl->framework_buildjs("/3proxy/reload",
		"3proxy.progress",
		"3proxy.progress.log",
		"progress-pxtempl-restart");
	
	$add="Loadjs('$page?rule-js=0&ruleid=$ruleid')";
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_parameters} </label>";
	$html[]="</div>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\"></div>";
	
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{type}</th><th>{subject}</th>";
	$html[]="</tr></thead><tbody id='awc-rows-$t'>";

	if(count($results)==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
	}
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$title=trim((string)$ligne["title"]);
		if($title==""){$title="<span class='awc-none'>{no_subject}</span>";}else{$title=htmlspecialchars($title);}
		$html[]="<tr>";
		$html[]="<td><div class='awc-ident'>".
			"<span class='awc-ico'><i class='fas fa-file-lines'></i></span>".
			"<span><span class='awc-name'>".
			"<a href=\"javascript:blur();\" onclick=\"Loadjs('$page?error=$ID')\">".htmlspecialchars((string)$ligne["explain"])."</a>".
			"</span></span></div></td>";
		$html[]="<td class='awc-mono'>$title</td>";
		$html[]="</tr>";
	}

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>".count($results)." {items}</span>";
	$html[]="<span class='awc-pg' id='awc-pager-$t'></span>";
	$html[]="</div></div></div>";

	$iconjs="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$iconjs.awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"awc-rows-$t","awc-pager-$t");

}

