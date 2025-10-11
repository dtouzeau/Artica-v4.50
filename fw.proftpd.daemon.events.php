<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["PROFTPD_SEARCH"])){$_SESSION["PROFTPD_SEARCH"]="today this hour 50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["PROFTPD_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	
	$MAIN=$tpl->format_search_protocol($_GET["search"],false,true);

    $sock=new sockets();
    $data=$sock->REST_API("/proftpd/events/{$MAIN["TERM"]}/{$MAIN["MAX"]}");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }


	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
    		<th>&nbsp;</th>
        	<th>$date_text</th>
        	<th>PID</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	

	if(count($json->Results)>3){$_SESSION["PROFTPD_SEARCH"]=$_GET["search"];}


	$STATE["INFO"]="label-primary";
	$STATE["NOTICE"]="label-warning";
	$STATE["SERVICE"]="label-success";
	foreach ($json->Results as $line){
		$line=trim($line);
		$ruleid=0;
		$rulename=null;
		$ACTION=null;
		$FF=false;
		$curDate=date("Y-m-d");
		$statex="INFO";

		
		if(!preg_match("#^(.+?),.*?\[([0-9]+)\]\s+(.*?)\s+(.+)#", $line,$re)){continue;}
			$FTime="{$re[1]}";
			$pid=$re[2];
			$none=$re[3];
			$line=$re[4];
			$FTime=trim(str_replace($curDate, "", $FTime));
			$state="label";


			if(preg_match("#STARTUP#i", $line)){$state="label-success";$statex="INFO";}
			if(preg_match("#no such#i", $line)){$state="label-warning";$statex="WARN";}
			if(preg_match("#warning:#i", $line)){$state="label-warning";$statex="WARN";}
            if(preg_match("#disconnecting#i", $line)){$state="label-warning";$statex="WARN";}
			if(preg_match("#successful#i", $line)){$state="label-success";$statex="INFO";}
            if(preg_match("#Permission denied#i", $line)){$state="label-danger";$statex="ALERT";}
            if(preg_match("#SECURITY VIOLATION#i", $line)){$state="label-danger";$statex="SECURITY";}
		
		$html[]="<tr>
				<td width=1% nowrap><span class='label $state'>$statex</span></td>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td >$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/PROFTPD.syslog.pattern")."</i></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
