<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["ipset-status"])){ipset_status();exit;}
if(isset($_POST["PostFixAutopIpsets"])){save();exit;}
if(isset($_GET["stats-mailvolume"])){stats_mailvolume();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
	$title=$tpl->_ENGINE_parse_body("{APP_POSTFIX} &raquo;&raquo; {firewall}");
	$js="LoadAjax('table-postfix','$page?tabs=yes');";
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>$title</h1>
	<p>{APP_POSTFIX_FIREWALL_TEXT}</p>

	</div>

	</div>



	<div class='row'><div id='progress-ipset-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-postfix'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('postfix-firewall');
	$.address.title('Artica: SMTP Postfix Firewall');
	$js

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();

	$array["{status}"]="$page?table=yes";
	$array["{rules}"]="fw.postfix.firewall.items.php";
	echo $tpl->tabs_default($array);
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ipset.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/ipset.progress.log";
	$ARRAY["CMD"]="postfix2.php?postfix-ipset=yes";
	$ARRAY["TITLE"]="{APP_FIREWALL} {compile_rules}";
	$ARRAY["AFTER"]="LoadAjax('table-postfix','$page?tabs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-ipset-restart')";
	
	$PostFixAutopIpsets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostFixAutopIpsets"));
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:240px;vertical-align: top'><div id='ipset-status'></div></td>";
	$html[]="<td valign='top' style='padding-left:20px'>";
	
	$form[]=$tpl->field_checkbox("PostFixAutopIpsets","{use_public_rules}",$PostFixAutopIpsets,false,"{PostFixAutopIpsets_explain}");
	$html[]=$tpl->form_outside("{parameters}", $form,null,"{apply}",$jsRestart,"AsPostfixAdministrator",true);
	$html[]="</td>";
	$html[]="</table>";
	$html[]="<script>LoadAjax('ipset-status','$page?ipset-status=yes');</script>";
	echo $tpl->_ENGINE_parse_body($html);
}
function save(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
}

function ipset_status(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$PostFixAutopIpsets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostFixAutopIpsets"));
	$PostFixAutopIpsetsDB=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostFixAutopIpsetsDB"));
	if($PostFixAutopIpsets==0){
		$f[]=$tpl->widget_h("grey","fas fa-thumbs-down","{disabled}","{use_public_rules}");
	}else{
		$f[]=$tpl->widget_h("green","fas fa-thumbs-up","{enabled}","{use_public_rules}");
	}
	
	$TIME=$tpl->time_to_date($PostFixAutopIpsetsDB["TIME"],true);
	
	$ITEMS=FormatNumber($PostFixAutopIpsetsDB["ITEMS"]);
	
	$f[]=$tpl->widget_vert("{items} - <span style='font-size:10px'>$TIME</span>", "$ITEMS");
	echo $tpl->_ENGINE_parse_body($f);
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
?>