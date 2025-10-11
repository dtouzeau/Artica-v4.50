<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["app-status"])){echo app_status();exit;}
xgen();



function xgen(){
$OPENVPN=false;	
$users=new usersMenus();
$page=CurrentPageName();

$VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBCAT_VERSION");

$t=time();
$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_UFDBCAT} v$VERSION</h1>
	<p>{APP_UFDBCAT_EXPLAIN}</p>	
	<div id='progress-firehol-restart'></div>
	</div>
</div>
<div class=\"wrapper wrapper-content animated fadeInRight\">
	<div class='row white-bg' id='app-status'></div>
	
</div>
<script>LoadAjax('app-status','$page?app-status=yes');</script>
";

$tpl=new template_admin();
echo $tpl->_ENGINE_parse_body($html);
}

function app_status(){
	$ini=new Bs_IniHandler();
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	$UfdbCatCountDatabases=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatCountDatabases"));
	$DATABASE_MAX=154;
	
	$sock=new sockets();
	$sock->getFrameWork("ufdbguard.php?services-status=yes");
	$ini->loadFile("/usr/share/artica-postfix/ressources/databases/ALL_UFDB_STATUS");
	
	$ARRAY=$ini->_params["APP_UFDBCAT"];


	if($DATABASE_MAX-$UfdbCatCountDatabases>0){
	
	$html[]="<div class=\"col-lg-3\">
		<div class=\"ibox\">
		<div class=\"ibox-content\">
		<h5>{used_databases}</h5>
		<h2>$UfdbCatCountDatabases / $DATABASE_MAX</h2>
		<div class=\"text-center\">
		<div id=\"sparklineUsedDB\"></div>
		</div>
		</div>
		</div>
		</div>";
	}else{
		$html[]="<div class=\"col-lg-3\" style='vertical-align:top'>
		<div class='widget navy-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
		<i class='fa fa-thumbs-up fa-4x'></i>
		<H2 class='font-bold no-margins'>100% {used_databases}</H2>
		<H3 class='font-bold no-margins'>$UfdbCatCountDatabases / $DATABASE_MAX</H3>
		
		</div>
		</div>";		
		
	}
			
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ufdbcat.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/ufdbcat.log";
	$ARRAY["CMD"]="squid.php?ufdbcat-restart=yes";
	$ARRAY["TITLE"]="{APP_UFDBCAT} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjax('app-status','$page?app-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";
	$html[]="";	
	$button=$tpl->button_autnonome("{restart}", $jsRestart, "fas fa-sync-alt");
	$button="<div style='margin-top:30px'>$button</div>";
	
	if($ARRAY["running"]==0){
		
		$html[]="<div class=\"col-lg-3\">
					<div class='widget red-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
						<i class='fas fa-exclamation-triangle fa-4x'></i>
						<H2 class='font-bold no-margins'>{stopped}</H2>
						$button
					</div>
				</div>
				
				";
		
	}else{
		$mem=$ARRAY["master_memory"];
		$mem=FormatBytes($mem);
		$html[]="<div class=\"col-lg-3\" style='vertical-align:top'>
				<div class='widget navy-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
				<i class='fa fa-thumbs-up fa-4x'></i>
				<H2 class='font-bold no-margins'>{running}</H2>
				<H3 class='font-bold no-margins'>{since} {$ARRAY["uptime"]}</H3>
				<H3 class='font-bold no-margins'>{memory_used}: $mem</H3>
				$button
				</div>
			</div>
				
				";		
		
	}

	$html[]="<div class=\"col-lg-3\">
	<div class='widget lazur-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
	<i class='fa fa-info fa-4x'></i>
	<H2 class='font-bold no-margins'>{use_the_search_engine}</H2>
	<small>{ufdbcat_searchengine}</small>
	</div>
	</div>
	
	";	
	
	
	

	$cachesjs[]="$('#sparklineUsedDB').sparkline([{$UfdbCatCountDatabases}, {$DATABASE_MAX}], {
	type: 'pie',
	height: '140',
	sliceColors: ['#d32d2d', '#F5F5F5']
	});";
	
	
$final=$tpl->_ENGINE_parse_body(@implode("\n", $html))."<script>".@implode("\n", $cachesjs)."</script>";
	return $final;
	
}
