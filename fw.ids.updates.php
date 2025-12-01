<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
//$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}

if(isset($_GET["upload-pattern-js"])){upload_pattern_js();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{updates}","far fa-cloud-download","{ids_updates_explain2}","$page?table=yes",
        "ids-updates","progress-suricata-update-restart",false,"table-ids-update-loader");

	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$path="/etc/suricata/rules";
	$dir_handle = @opendir($path);
	$array=array();
	if(!$dir_handle){
		return array();
	}
	$count=0;	
	$TRCLASS=null;

    $jsrestart=$tpl->framework_buildjs(
        "/suricata/update",
        "suricata-update.progress",
        "suricata-update.progress.txt",
        "progress-suricata-update-restart",
        "LoadAjax('table-ids-update-loader','$page?table=yes');"
    );

    $jscompile=  $tpl->framework_buildjs(
        "suricata:/suricata/restart",
        "suricata.progress",
        "suricata.progress.txt","progress-ids-restart"
    );


    $topbuttons[] = array();
    $TINY_ARRAY["TITLE"]="{updates}";
    $TINY_ARRAY["ICO"]="fa fa-list";
    $TINY_ARRAY["EXPL"]="{ids_updates_explain2}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	
	
	$html[]="<table id='table-ids-updates' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{families}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{date}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>diff</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $td1prc="style='width:1%' nowrap";
	
	
	while ($file = readdir($dir_handle)) {
	  if($file=='.'){continue;}
	  if($file=='..'){continue;}
      if($file=='COPYRIGHT'){continue;}
	  if(preg_match("#\.(txt|yaml|map|config|conf)$#", $file)){continue;}
	  if(preg_match("#(LICENSE|-deleted|COPYRIGHT)#", $file)){continue;}
	  if(preg_match("#^(rbn|rbn-malvertisers|botcc\.portgrouped|modbus-events|emerging-misc|dyre_sslblacklist|emerging-info|iprep|emerging-retired|local|whitelist|Production)\.#", $file)){continue;}
	  if(is_dir("$path/$file")){continue;}
      
	 
	  if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	  $zdate=filemtime("$path/$file");
	  $date=$tpl->time_to_date($zdate,true);
	  $distances=distanceOfTimeInWords($zdate,time());
	  $min=file_time_min_Web("$path/$file");
	  $color=null;
	  $status="<span class='label label-primary'>{updated}</span>";
	  
	  if($min>6422){
	  	$status="<span class='label label-warning'>{updated}</span>";
	  	$color="class='text-warning'";
	  }
      if($min>18844){
        $status="<span class='label label-warning'>{outdated}</span>";
        $color="class='text-danger'";
      }

      $id=md5($file);
	  $html[]="<tr class='$TRCLASS'>";
	  $html[]="<td $td1prc><span style='font-weight:bold' id='id-$id' $color>$status</span></td>";
	  $html[]="<td><span style='font-weight:bold' id='id-$id' $color>{{$file}}</span></td>";
	  $html[]="<td $td1prc><span style='font-weight:bold' $color>$date</span></td>";
	  $html[]="<td $color $td1prc>$distances</td>";
	 
	  $html[]="</tr>";
	  
	}
	$html[]="</tbody>";
	$html[]="<tfoot>";



    $TINY_ARRAY["TITLE"]="{updates}";
    $TINY_ARRAY["ICO"]="far fa-cloud-download";
    $TINY_ARRAY["URL"]="ids-updates";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	$headsjs
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-ids-updates').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}