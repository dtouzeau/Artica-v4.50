<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.greensql.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["GreenSQLVerbose"])){save();exit;}
if(isset($_GET["stats-mailqueue"])){stats_mailqueue();exit;}
if(isset($_GET["stats-mailstats"])){stats_mailstats();exit;}
if(isset($_GET["stats-mailvolume"])){stats_mailvolume();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$GREENSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GREENSQL_VERSION");
	if($GREENSQL_VERSION==null){$GREENSQL_VERSION="1.3.0";}
	$title=$tpl->_ENGINE_parse_body("{APP_GREENSQL} v{$GREENSQL_VERSION} &raquo;&raquo; {service_status}");
	$js="LoadAjax('table-greensql','$page?table=yes');";
	
	

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>$title</h1>
	<p>{APP_GREENSQL_ABOUT}</p>

	</div>

	</div>



	<div class='row'><div id='progress-greensql-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-greensql'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('greensql-status');
	$.address.title('Artica: GreenSQL status');
	$js

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_GREENSQL} v{$GREENSQL_VERSION}",$html);
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
	$array["{statistics} {queue}"]="$page?stats-mailqueue=yes";
	$array["{statistics} {messages}"]="$page?stats-mailstats=yes";
	$array["{statistics} {volumes}"]="$page?stats-mailvolume=yes";
	echo $tpl->tabs_default($array);
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$q=new green_sql();
	$sock->getFrameWork("greensql.php?status=yes");
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/greensql.status");
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/greensql.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/greensql.progress.log";
	$ARRAY["CMD"]="greensql.php?restart=yes";
	$ARRAY["TITLE"]="{restarting_service}";
	$ARRAY["AFTER"]="LoadAjax('table-greensql','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$service_restart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-greensql-restart');";
	

	$q=new green_sql();
	$sql="SELECT count(*) as tcount FROM proxy";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"greensql"));
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
	
	
	$CountOfProxy=$ligne["tcount"];
	$sql="SELECT COUNT(*) as tcount FROM alert WHERE block=1";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"greensql"));
	$CountOfBlocks=$ligne["tcount"];
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px' valign='top'>";
	$html[]="<table style='width:100%'>";
	$html[]="	<tr>";
	$html[]="		<td>
						<div class=\"ibox\" style='border-top:0px;margin-bottom:0px;padding-bottom:2px'>
    						<div class=\"ibox-content\" style='border-top:0px;margin-bottom:0px;padding-bottom:2px'>". $tpl->SERVICE_STATUS($ini, "APP_GREENSQL",$service_restart)."</div>
    					</div>";
	$html[]="<div class=\"col-lg-3\" style='width:375px'>";
	
	$html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fal fa-list-ul fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {routers}</span>
	<h2 class=\"font-bold\">$CountOfProxy</h2>
	</div>
	</div>
	</div>";
	
	$html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 red-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-ban fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {refused}</span>
	<h2 class=\"font-bold\">$CountOfBlocks</h2>
	</div>
	</div>
	</div>";
  $html[]="	</div>";
   $html[]="	</td>";
   $html[]="	</tr>";
$html[]="</table></td>";
	
	
	$html[]="<td style='width:99%;vertical-align:top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='padding-left:10px;padding-top:20px'>";
	
	$GreenSQLBlockLevel=intval($q->GET_INFO("GreenSQLBlockLevel"));
	if($GreenSQLBlockLevel==0){$GreenSQLBlockLevel=30;}
	$GreenSQLWarnLevel=intval($q->GET_INFO("GreenSQLWarnLevel"));
	if($GreenSQLWarnLevel==0){$GreenSQLWarnLevel=20;}
	
	$GreenSQLRiskSQLComments=intval($q->GET_INFO("GreenSQLRiskSQLComments"));
	if($GreenSQLRiskSQLComments==0){$GreenSQLRiskSQLComments=30;}
	
	$GreenSQLRiskSenstiviteTables=intval($q->GET_INFO("GreenSQLRiskSenstiviteTables"));
	if($GreenSQLRiskSenstiviteTables==0){$GreenSQLRiskSenstiviteTables=10;}
	
	$GreenSQLRiskOrToken=intval($q->GET_INFO("GreenSQLRiskOrToken"));
	if($GreenSQLRiskOrToken==0){$GreenSQLRiskOrToken=5;}
	
	$GreenSQLRiskUnionToken=intval($q->GET_INFO("GreenSQLRiskUnionToken"));
	if($GreenSQLRiskUnionToken==0){$GreenSQLRiskUnionToken=10;}
	
	$GreenSQLRiskVarCmpVar=intval($q->GET_INFO("GreenSQLRiskVarCmpVar"));
	if($GreenSQLRiskVarCmpVar==0){$GreenSQLRiskVarCmpVar=30;}
	
	$GreenSQLRiskAlwaysTrue=intval($q->GET_INFO("GreenSQLRiskAlwaysTrue"));
	if($GreenSQLRiskAlwaysTrue==0){$GreenSQLRiskAlwaysTrue=30;}
	
	$GreenSQLRiskEmptyPassword=intval($q->GET_INFO("GreenSQLRiskEmptyPassword"));
	if($GreenSQLRiskEmptyPassword==0){$GreenSQLRiskEmptyPassword=30;}
	
	$GreenSQLRiskMultipleQueries=intval($q->GET_INFO("GreenSQLRiskMultipleQueries"));
	if($GreenSQLRiskMultipleQueries==0){$GreenSQLRiskMultipleQueries=15;}
	
	$GreenSQLVerbose=intval($q->GET_INFO("GreenSQLVerbose"));
	if($GreenSQLVerbose==0){$GreenSQLVerbose=3;}

	for($i=1;$i<11;$i++){
		$FFLEV[$i]="{level} $i";
	}
	
	$form[]=$tpl->field_array_hash($FFLEV, "GreenSQLVerbose", "{debug_level}", $GreenSQLVerbose);
	$form[]=$tpl->field_numeric("GreenSQLWarnLevel","{warn_level}",$GreenSQLWarnLevel,"{GREENSQL_warn_level}");
	$form[]=$tpl->field_numeric("GreenSQLBlockLevel","{block_level}",$GreenSQLBlockLevel,"{GREENSQL_block_level}");
	
	$form[]=$tpl->field_section("{scores}");
	$form[]=$tpl->field_numeric("GreenSQLRiskSQLComments","{risk_sql_comments}",$GreenSQLRiskSQLComments,"{GREENSQL_risk_sql_comments}");
	$form[]=$tpl->field_numeric("GreenSQLRiskSenstiviteTables","{risk_senstivite_tables}",$GreenSQLRiskSenstiviteTables,"{GREENSQL_risk_senstivite_tables}");
	$form[]=$tpl->field_numeric("GreenSQLRiskOrToken","{risk_or_token}",$GreenSQLRiskOrToken,"{GREENSQL_risk_or_token}");
	$form[]=$tpl->field_numeric("GreenSQLRiskUnionToken","{risk_union_token}",$GreenSQLRiskUnionToken,"{GREENSQL_risk_union_token}");
	$form[]=$tpl->field_numeric("GreenSQLRiskVarCmpVar","{risk_var_cmp_var}",$GreenSQLRiskVarCmpVar,"{GREENSQL_risk_var_cmp_var}");
	$form[]=$tpl->field_numeric("GreenSQLRiskAlwaysTrue","{risk_always_true}",$GreenSQLRiskAlwaysTrue,"{GREENSQL_risk_always_true}");
	$form[]=$tpl->field_numeric("GreenSQLRiskEmptyPassword","{risk_empty_password}",$GreenSQLRiskAlwaysTrue,"{GREENSQL_risk_empty_password}");
	$form[]=$tpl->field_numeric("GreenSQLRiskMultipleQueries","{risk_multiple_queries}",$GreenSQLRiskMultipleQueries,"{GREENSQL_risk_multiple_queries}");
	
	$html[]=$tpl->form_outside("{parameters}", $form,null,"{apply}",$service_restart,"AsSquidAdministrator");
	
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="</td>";
	$html[]="</tr>";
	
	$html[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

	
}

function save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new green_sql();
	foreach ($_POST as $key=>$value){
		$q->SET_INFO($key, $value);
		
		
	}
	
}


function stats_mailqueue(){
	
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailqueue-day.png";
	$f[]="postfix_mailqueue-week.png";
	$f[]="postfix_mailqueue-month.png";
	$f[]="postfix_mailqueue-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
	
}

function stats_mailstats(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailstats-day.png";
	$f[]="postfix_mailstats-week.png";
	$f[]="postfix_mailstats-month.png";
	$f[]="postfix_mailstats-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}
function stats_mailvolume(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailvolume-day.png";
	$f[]="postfix_mailvolume-week.png";
	$f[]="postfix_mailvolume-month.png";
	$f[]="postfix_mailvolume-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}