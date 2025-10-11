<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["STATSRDEBUG_SEARCH"]==null){$_SESSION["STATSRDEBUG_SEARCH"]="limit 200";}
	
	$html[]="
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["STATSRDEBUG_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
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
	$zdate=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->javascript_parse_text("{events}");
	$target_file=PROGRESS_DIR."/statsdebug.log";
	$t=time();
	

	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	
	$_SESSION["STATSRDEBUG_SEARCH"]=trim(strtolower($_GET["search"]));
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    $ss=urlencode($search["S"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("statscom.php?searchdebugs=$ss&rp={$search["MAX"]}");
	$datas=explode("\n",@file_get_contents($target_file));


	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zdate</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>PID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";
	
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$TRCLASS=null;
	krsort($datas);

	$td1prc=$tpl->table_td1prc();

	foreach ($datas as $key=>$line){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		if(trim($line)==null){continue;}
        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]:(.*)#",trim($line),$re)){
            VERBOSE("NO MATCHES $line",__LINE__);
            $line=htmlspecialchars($line);
            $html[]="<tr class='$TRCLASS'>";
            $html[]="<td $td1prc></td>";
            $html[]="<td $td1prc></td>";
            $html[]="<td class=\"$text_class\">$line</td>";
            $html[]="</tr>";
            continue;
        }
        $line=trim($line);
        if($line==null){continue;}
        $datetext=$re[1]." ".$re[2]." ".$re[3];
        $pid=$re[4];
		$line=$re[5];
        $line=htmlspecialchars($line);
        $line=str_replace("ParseStats.go[squidstats/StatsCom.","[",$line);

		if(preg_match("#(does not exist|Error while|Can not|error|fatal|failed)#i", $line)){
			$text_class="text-danger";
		}
		
		if(preg_match("#Setting antivirus default engine#i", $line)){
			$text_class="text-info";
		}
		if(preg_match("#\] Parsed #i", $line)){
			$text_class="text-navy font-bold";
		}

		if(preg_match("#incomplete startup packet#",$line)){
		    $line="<span style='color:#CCCCCC'>$line</span>";
        }

	    $html[]="<tr class='$TRCLASS'>";
		$html[]="<td $td1prc>$datetext</td>";
        $html[]="<td $td1prc>$pid</td>";
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
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
