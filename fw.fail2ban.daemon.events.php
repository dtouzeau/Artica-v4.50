<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["FAIL2BAN_SEARCH"])){$_SESSION["FAIL2BAN_SEARCH"]="today this hour 50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["FAIL2BAN_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	
	$line=base64_encode(serialize($MAIN));
	$sock->getFrameWork("fail2ban.php?syslog=$line");
	$filename=PROGRESS_DIR."/fail2ban.syslog";
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
    		<th>&nbsp;</th>
        	<th>$date_text</th>
        	<th>PID</th>
        	<th nowrap>{service}</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["FAIL2BAN_SEARCH"]=$_GET["search"];}
	rsort($data);

	$STATE["INFO"]="label-primary";
	$STATE["NOTICE"]="label-warning";
	$STATE["SERVICE"]="label-success";
	foreach ($data as $line){
		$line=trim($line);
		$ruleid=0;
		$rulename=null;
		$ACTION=null;
		$FF=false;
		$curDate=date("Y-m-d");
		

		
		if(preg_match("#^(.+?),[0-9]+\s+fail2ban\.(.+?)\s+\[([0-9]+)\]:\s+([A-Z]+)\s+\[(.+?)\]\s+(.+)#", $line,$re)){
			$FTime="{$re[1]}";
			
			$service=$re[2].":".$re[5];
			$pid=$re[3];
			$line=$re[6];
		}else{
			if(preg_match("#^(.+?),[0-9]+\s+fail2ban\.(.+?)\s+\[([0-9]+)\]:\s+([A-Z]+)\s+(.+)#", $line,$re)){
				$FTime="{$re[1]}";
				$service=$re[2].":service";
				$pid=$re[3];
				$line=$re[5];
				$re[4]="SERVICE";
			}
			
		}
		
		if($FTime==null){
			$html[]="<tr>
			<td colspan=5>{$line}</span></td>
			</tr>";
			continue;
		}
		
		$FTime=trim(str_replace($curDate, "", $FTime));
		$state="label";
		
		if(isset($STATE[$re[4]])){$state=$STATE[$re[4]];}



		
		$html[]="<tr>
				<td width=1% nowrap><span class='label $state'>{$re[4]}</span></td>
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td width=1% nowrap>$service</td>
				<td >$line</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/fail2ban.syslog.pattern")."</i></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
	
}
