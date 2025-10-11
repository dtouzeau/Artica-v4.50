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
	if(!isset($_SESSION["SNMPD_SEARCH"])){$_SESSION["SNMPD_SEARCH"]="50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["SNMPD_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
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
	$sock->getFrameWork("snmpd.php?events=$line");
	$filename=PROGRESS_DIR."/snmpd.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["SNMPD_SEARCH"]=$_GET["search"];}
	rsort($data);

	
	foreach ($data as $line){
		$line=trim($line);
		$ruleid=0;
		$rulename=null;
		$ACTION=null;
		$color="text-muted";
		$FF=false;


		
		if(preg_match("#Downloading#", $line)){$color="text-info font-bold";}
		if(preg_match("#Can't connect#", $line)){$color="text-danger";}
		if(preg_match("#errno=[0-9]+#", $line)){$color="text-danger";}
		if(preg_match("#Database updated#", $line)){$color="text-success";}
		if(preg_match("#Started at#", $line)){$color="text-success";}
		if(preg_match("#WARNING:#", $line)){$color="text-warning";}
		if(preg_match("#failed:#", $line)){$color="text-danger font-bold";}
		if(preg_match("#Disabling#", $line)){$color="text-warning";}
		if(preg_match("#Not loading#", $line)){$color="text-warning";}
		if(preg_match("#ERROR:#", $line)){$color="text-danger";}
		
		$html[]="<tr>
				<td><span class='$color'>$line</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/snmpd.syslog.pattern")."</i></div>";
	echo @implode("\n", $html);
	
	
	
}
