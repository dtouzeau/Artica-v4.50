<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsDnsAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["PDNS_SEARCH"])){$_SESSION["PDNS_SEARCH"]="today this hour 50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["PDNS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	$.address.state('/');
	$.address.value('/pdns-events');
	$.address.title('Artica: PowerDNS Events');	
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
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: PowerDNS Events",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("pdns.php?syslog=$line");
	$filename=PROGRESS_DIR."/pdns.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$src=$tpl->_ENGINE_parse_body("{src}");
	$srcport=$tpl->_ENGINE_parse_body("{source_port}");
	$dst=$tpl->_ENGINE_parse_body("{dst}");
	$dstport=$tpl->_ENGINE_parse_body("{destination_port}");
	$incoming=$tpl->_ENGINE_parse_body("{incoming2}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>PID</th>
        	<th>{service2}</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["PDNS_SEARCH"]=$_GET["search"];}
	krsort($data);
$xServe["pdns"]="{APP_PDNS}";
$xServe["exec.pdns_server.php"]="Artica";
$xServe["pdns_recursor"]="{APP_PDNS_RECURSOR}";
	
	foreach ($data as $line){
		$line=trim($line);
		$ruleid=0;
		$rulename=null;
		$ACTION=null;
		$FF=false;
		if(!preg_match("#^(.+?)\s+([0-9])+\s+([0-9:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:\s+(.+)#", $line,$re)){
			echo "<strong style='color:red'>$line</strong><br>"; 
			continue;}
			$service=$re[5];
			
			
		$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
		$xtime=strtotime($date);
		$FTime=date("Y-m-d H:i:s",$xtime);
		$curDate=date("Y-m-d");
		$FTime=trim(str_replace($curDate, "", $FTime));
		
		$pid=$re[6];
		$line=$re[7];

		

		
		$html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td width=1% nowrap>{$xServe[$service]}</td>
				<td>$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/pdns.syslog.pattern")."</i></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
