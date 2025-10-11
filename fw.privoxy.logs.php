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
	if(!isset($_SESSION["PRIVOXY_SEARCH"])){$_SESSION["PRIVOXY_SEARCH"]="today this hour 50 events";}
	
	$html="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["PRIVOXY_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	
	
	$rp=intval($MAIN["MAX"]);
	if($rp==0){$rp=100;}
	
	
	if($MAIN["TERM"]<>null){
		$search=base64_encode($MAIN["TERM"]);
		$sock->getFrameWork("privoxy.php?events=$search&rp=$rp");
	
		$datas=unserialize(@file_get_contents(PROGRESS_DIR."/privoxy-events"));
		$total=count($datas);
	
	}else{
		$sock->getFrameWork("privoxy.php?events=&rp=$rp");
		$datas=unserialize(@file_get_contents(PROGRESS_DIR."/privoxy-events"));
		$total=count($datas);
	}
	

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
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";
	
	
	if(count($datas)>3){$_SESSION["PRIVOXY_SEARCH"]=$_GET["search"];}
	rsort($datas);

	
	foreach ($datas as $line){
		$date="&nbsp;";
		$color="text-primary";
		if(strpos($line, "privoxy.org")>0){continue;}
		if(!preg_match("#([0-9\-]+)\s+(.*?)\s+(.*?)\s+(.+)#",$line,$re)){continue;}
		$date=$re[1]." ".$re[2];
		$line2=$re[4];
		if(preg_match("#Blocked:#", $line2)){$color="text-danger";}
		$line=str_replace('"', "`", $line);

		
		$html[]="<tr>
				<td width=1% nowrap>$date</td>
				<td width=99% nowrap><span class='$color'>$line2</span></td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div></div>";
	echo @implode("\n", $html);
	
	
	
}
