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
	if(!isset($_SESSION["SAFESEARCH_SEARCH"])){$_SESSION["SAFESEARCH_SEARCH"]="50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["SAFESEARCH_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
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
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("squid2.php?events-gsb-service=$line");
	$filename="/usr/share/artica-postfix/ressources/logs/web/SafeBrowsing.syslog";
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
    	    <th>{date}</th>
        	<th nowrap>{PID}</th>
        	<th>{type}</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["SAFESEARCH_SEARCH"]=$_GET["search"];}
	rsort($data);

	$tdprc=$tpl->table_td1prc();
	foreach ($data as $line){
		$line=trim($line);
        if(!preg_match("#(.*?)\s+([0-9]+)\s+([0-9:]+).*?\s+proxy-safebrowsing\[([0-9]+)\]:.*?\[([A-Z]+)\]:(.+)#",$line,$re)){
            VERBOSE("NO MATCH $line",__LINE__);
            continue;}

        $color=null;
		$Day=$re[1];
        $DayNum=$re[2];
        $Time=$re[3];
        $Pid=$re[4];
        $Type=$re[5];
        $line=trim($re[6]);

        $stime=date("Y-m")."-$DayNum $Time";
        $ttime=strtotime($stime);
        $date=$tpl->time_to_date($ttime,true);

        if(preg_match("#(INFO)+#", $Type)){$color="text-success";}
		if(preg_match("#\s+stopped(\s+|\.)#i", $line)){$color="text-warning";}
		if(preg_match("#ALERT#", $Type)){$color="text-danger font-bold";}
		if(preg_match("#Disabling#", $line)){$color="text-warning";}
		if(preg_match("#Not loading#", $line)){$color="text-warning";}
		if(preg_match("#(ERROR|FATAL)#", $line)){$color="text-danger";}
		
		$html[]="<tr>
                <td $tdprc><span class='$color'>$date</span></td>
				<td $tdprc><span class='$color'>$Pid</span></td>
				<td $tdprc><span class='$color'>$Type</span></td>
				<td><span class='$color'>$line</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/SafeBrowsing.syslog.patter")."</i></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
