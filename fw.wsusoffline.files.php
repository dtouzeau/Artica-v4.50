<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["wsusofflineStorageDir"])){save();exit;}
if(isset($_GET["execute-now"])){execute_now();exit;}
if(isset($_GET["title"])){title();exit;}
page();


function page(){

	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding id='wsusoffline-title'></h1>
	</div>
	
</div>
			
                            
			
<div class='row'><div id='progress-wsusofflinefiles-restart'></div>
			<div class='ibox-content'>
       	
			 	<div id='table-loader'></div>
                                    
			</div>
</div>
					
			
			
<script>
LoadAjaxSilent('wsusoffline-title','$page?title=yes');
LoadAjax('table-loader','$page?table=yes');
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function title(){
	$dirsizes=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineSizes"));
	$CUR=intval($dirsizes["CUR"]);
	$PART=intval($dirsizes["PART"]);
	
	
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/wsusoffline.storage.prg";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/wsusoffline.storage.log";
	$ARRAY["CMD"]="wsusoffline.php?storage=yes";
	$ARRAY["TITLE"]="{status}";
	$ARRAY["AFTER"]="LoadAjaxSilent('wsusoffline-title','$page?title=yes');LoadAjax('table-loader','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsexecute="Loadjs('fw.progress.php?content=$prgress&mainid=progress-wsusofflinefiles-restart');";
	
	if($CUR==0){
	
		$js="<script>$jsexecute</script>";
	}
	
	echo $tpl->_ENGINE_parse_body("{APP_WSUSOFFLINE} &raquo;&raquo; {storage} <strong>".FormatBytes($CUR/1024)."</strong>/".FormatBytes($PART/1024))."\n$js";
	
	
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql();
	$TRCLASS=null;	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/wsusoffline.storage.prg";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/wsusoffline.storage.log";
	$ARRAY["CMD"]="wsusoffline.php?storage=yes";
	$ARRAY["TITLE"]="{status}";
	$ARRAY["AFTER"]="LoadAjaxSilent('wsusoffline-title','$page?title=yes');LoadAjax('table-loader','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsexecute="Loadjs('fw.progress.php?content=$prgress&mainid=progress-wsusofflinefiles-restart');";
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/wsusoffline.storage.prg";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/wsusoffline.storage.log";
	$ARRAY["CMD"]="wsusoffline.php?clean-timestamps=yes";
	$ARRAY["TITLE"]="{clean_timestamps}";
	$ARRAY["AFTER"]="LoadAjaxSilent('wsusoffline-title','$page?title=yes');LoadAjax('table-loader','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsexecute="Loadjs('fw.progress.php?content=$prgress&mainid=progress-wsusofflinefiles-restart');";
	
	
	
	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"javascript:$jsexecute\"><i class='fa fa-repeat'></i> {analyze_storage} </label>
			<label class=\"btn btn btn-warning\" OnClick=\"javascript:$jsexecute\"><i class='fa fa-download'></i> {clean_timestamps} </label>
	</div>");
	
	$html[]="<table id='table-wsusfiles-list' class=\"footable table white-bg table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='text-align:left'>{directory}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'>{filename}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'>{filesize}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%;text-align:center'>{filedate}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	

	$sql="SELECT *  FROM wsusoffline_dirs ORDER BY sizebytes DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$sizebytes=FormatBytes($ligne["sizebytes"]/1024);
		$filemtime=date("Y-m-d H:i:s",$ligne["filemtime"]);
		$filename=$ligne["filename"];
		$filepath=$ligne["path"];
		$dirnane=dirname($filepath);
		$basenmae=basename($dirnane);
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>$basenmae</td>";
		$html[]="<td nowrap>$filename</td>";
		$html[]="<td align='right' style='width:1%' nowrap>$sizebytes</td>";
		$html[]="<td style='width:1%' nowrap>$filemtime</td>";
		$html[]="</tr>";
	
	
	
	}
	$js=array();
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-wsusfiles-list').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	";
	$html[]=@implode("\n", $js)."</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}