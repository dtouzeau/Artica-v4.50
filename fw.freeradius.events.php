<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["FREERAD_SEARCH"])){$_SESSION["FREERAD_SEARCH"]="today this hour 50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["FREERAD_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

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
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("freeradius.php?syslog=$line");
	$filename=PROGRESS_DIR."/freeradius.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>{pid}</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["FREERAD_SEARCH"]=$_GET["search"];}
	rsort($data);

	
	$labels["Info"]="label-primary";
	$labels["Auth"]="label-info";
	$labels["Warn"]="label-warning";
	$labels["Error"]="label-danger";
	
	foreach ($data as $line){
		$line=trim($line);
		if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#", $line,$re)){
			echo "<strong style='color:red'>$line</strong><br>"; 
			continue;
		}
		$inf=null;
		$date="{$re[1]} {$re[2]} {$re[3]}";
        $pid=$re[4];
        $line=$re[5];
		//$inf=trim($re[6]);
		//$line=$re[7];

		if(!isset($labels[$inf])){$labels[$inf]=null;}
        if(preg_match("#Stopping#", $line)){$inf="Warn";}
		if(preg_match("#rejecting\s+#", $line)){$inf="Warn";}
		if(preg_match("#because of error:#", $line)){$inf="Warn";}
        if(preg_match("#Ready to process requests#", $line)){$inf="Info";}
        if(preg_match("#Success#i", $line)){$inf="Info";}

		$html[]="<tr>
				<td width=1% nowrap><span class='label {$labels[$inf]}'>$date</span></td>
				<td width=1% nowrap>$pid</td>
				<td>$line ($inf)</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/freeradius.syslog.pattern")."</i></div>";
	echo @implode("\n", $html);
	
	
	
}
