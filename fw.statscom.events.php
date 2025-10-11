<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["STATSREDIS_SEARCH"]==null){$_SESSION["STATSREDIS_SEARCH"]="limit 200";}
	
	$html[]="
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["STATSREDIS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>	
	
	
	
		
	<div class='row'><div id='progress-firehol-restart'></div>";

	$html[]="<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
		
		
		
<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?table=yes&t=$t&search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$eth_sql=null;
	$token=null;
	$class=null;
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$zdate=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->javascript_parse_text("{events}");
	$target_file=PROGRESS_DIR."/statsredis.log";
	
	$js="OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
	if($_GET["eth"]==null){$class=" active";}
	
	$t=time();
	

	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	
	$_SESSION["STATSREDIS_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
	$ss=urlencode($search["S"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("statscom.php?searchlogs=$ss&rp={$search["MAX"]}");
	$datas=explode("\n",@file_get_contents($target_file));


	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zdate</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";
	
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	$STATICO["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $STATICO["LOG"]=               "<span class='label'>INFO.</span>";
    $STATICO["WARNING"]=           "<span class='label label-warning'>WARN.</span>";
    $STATICO["HINT"]=              "<span class='label label-info'>HINT.</span>";
    $STATICO["STATEMENT"]=         "<span class='label label-info'>STAT.</span>";
    $STATICO["FATAL"]="<span class='label label-danger'>ERROR</span>";
	
	$TRCLASS=null;
	krsort($datas);

	$td1prc=$tpl->table_td1prc();

	foreach ($datas as $key=>$line){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		if(trim($line)==null){continue;}
		if($GLOBALS['VERBOSE']){echo "FOUND $line\n";}

        if(!preg_match("#^([0-9]+):([A-Z]+)\s+(.+?)\.[0-9]+\s+.*?\s+(.+)#",$line,$re)){continue;}

        $datetext=$re[3];

		$pid=$re[1];
		$status=$re[2];
		$line=$re[4];

		if(preg_match("#(does not exist|Error while|Can not)#i", $line)){
			$text_class="text-danger";
		}
		
		if(preg_match("#Setting antivirus default engine#i", $line)){
			$text_class="text-info";
		}
		if(preg_match("#VIRUS DETECTED#i", $line)){
			$text_class="text-warning";
		}

		if(preg_match("#incomplete startup packet#",$line)){
		    $line="<span style='color:#CCCCCC'>$line</span>";
        }

	    $html[]="<tr class='$TRCLASS'>";
		$html[]="<td $td1prc>$datetext</td>";
		$html[]="<td class=\"$text_class\">$line</a></td>";
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
	$html[]="</table><div><i></i></div>";
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable(
{
\"filtering\": {
\"enabled\": false
},
\"sorting\": {
				\"enabled\": true
			}

			}
		

	); });
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
