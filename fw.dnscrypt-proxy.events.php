<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsDnsAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["service"])){service_events();exit;}
if(isset($_GET["requests"])){requests_events();exit;}
if(isset($_GET["search-requests"])){requests_search();exit;}
tabs();


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $DnsCryptQueryLogging=intval($sock->GET_INFO("DnsCryptQueryLogging"));

    $array["{service_events}"]="$page?service=yes";

    if($DnsCryptQueryLogging==1){
        $array["{requests}"]="$page?requests=yes";
    }
    echo $tpl->tabs_default($array);

}


function service_events(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["DNSCRYPT_PROXY_SEARCH"])){$_SESSION["DNSCRYPT_PROXY_SEARCH"]="today this hour 50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["DNSCRYPT_PROXY_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	$.address.state('/');
	$.address.value('/DNSCrypt-events');
	$.address.title('Artica: DSNCrypt Proxy Events');	
		function Search$t(e){
			if(!checkEnter(e) ){ return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		ss$t();
	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: DSNCrypt Proxy Events",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}

function requests_events(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    if(!isset($_SESSION["DNSCRYPT_PROXY_SEARCH"])){$_SESSION["DNSCRYPT_PROXY_SEARCH"]="today this hour 50 events";}

    $html="<div class=\"row\"> 
		<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["DNSCRYPT_PROXY_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	      		<span class=\"input-group-btn\">
	       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
	      	</span>
     	</div>
    	</div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-requests'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/DNSCrypt-requests');
	$.address.title('Artica: DSNCrypt Proxy Requests');	
		function Search$t(e){
			if(!checkEnter(e) ){ return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-requests','$page?search-requests='+ss);
		}
		
		ss$t();
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: DSNCrypt Proxy Requests",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$date=null;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("dnscrypt-proxy.php?syslog=$line");
	$filename=PROGRESS_DIR."/dnscrypt-proxy.search";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>PID</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["DNSCRYPT_PROXY_SEARCH"]=$_GET["search"];}
	krsort($data);
$xServe["pdns"]="{APP_PDNS}";
$xServe["exec.pdns_server.php"]="Artica";
$xServe["pdns_recursor"]="{APP_PDNS_RECURSOR}";
	
	foreach ($data as $line){
		$line=trim($line);
		$rulename=null;
		$ACTION=null;
		if(!preg_match("#(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\s+dnscrypt-proxy\[([0-9]+)\]:(.+)#", $line,$re)){
			echo "<strong style='color:red'>$line</strong><br>"; 
			continue;}

			
			
		$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
		$xtime=strtotime($date);
		$FTime=$tpl->time_to_date($xtime,true);
		
		
		$pid=$re[4];
		$line=trim($re[5]);

		

		
		$html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td>$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/dnscrypt-proxy.pattern")."</i></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}

function requests_search(){
    $time=null;
    $sock=new sockets();
    $tpl=new template_admin();
    $date=null;

    $MAIN=$tpl->format_search_protocol($_GET["search-requests"]);

    $line=base64_encode(serialize($MAIN));
    $sock->getFrameWork("dnscrypt-proxy.php?requests=$line");
    $filename=PROGRESS_DIR."/dnscrypt-proxy.queries";
    $date_text=$tpl->_ENGINE_parse_body("{date}");

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>{client}</th>
        	<th>{query}</th>
        	<th>Q</th>
        </tr>
  	</thead>
	<tbody>
";

    $data=explode("\n",@file_get_contents($filename));
    if(count($data)>3){$_SESSION["DNSCRYPT_PROXY_SEARCH"]=$_GET["search-requests"];}
    krsort($data);
    $xServe["pdns"]="{APP_PDNS}";
    $xServe["exec.pdns_server.php"]="Artica";
    $xServe["pdns_recursor"]="{APP_PDNS_RECURSOR}";

    foreach ($data as $line){
        $line=trim($line);
        if($line==null){continue;}
        $rulename=null;
        $ACTION=null;
        if(!preg_match("#^\[(.+?)\]\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+)#", $line,$re)){continue;}



        $date=$re[1];
        $xtime=strtotime($date);
        $FTime=$tpl->time_to_date($xtime,true);


        $Client=$re[2];
        $Domain=$re[3];
        $Q=$re[4];
        $INFO=$re[5];





        $html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$Client</td>
				<td>$Domain</td>
				<td width=1% nowrap>$Q/$INFO</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/dnscrypt-proxy.pattern")."</i></div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}
?>