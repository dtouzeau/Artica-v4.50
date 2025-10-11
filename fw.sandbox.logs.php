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
	if($_SESSION["SANDBOXEVNTS"]==null){$_SESSION["SANDBOXEVNTS"]="limit 200";}
	
	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{sandbox_connector} {events}</h1></div>
	</div>
		

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["SANDBOXEVNTS"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
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
		$.address.state('/');
	    $.address.value('sandbox-events');		
		Start$t();
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{sandbox_connector}");
        return;
    }
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl                    = new template_admin();
	$page                   = CurrentPageName();
	$t                      = $_GET["t"];
    $target_file            = PROGRESS_DIR."/sandbox.log";
    $_SESSION["SANDBOXEVNTS"]   = trim(strtolower($_GET["search"]));
    $search                 = $tpl->query_pattern(trim(strtolower($_GET["search"])));
    $ss                     = base64_encode($search["S"]);

    if(!is_numeric($t)){$t=time();}




	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("cicap.php?sandbox-events=$ss&rp={$search["MAX"]}");
	$datas=explode("\n",@file_get_contents($target_file));


	$fields[]="{time}";
    $fields[]="pid";
    $fields[]="{events}";
    $html[]=$tpl->table_head($fields,"table-$t");

	$TRCLASS=null;
	krsort($datas);


	foreach ($datas as $key=>$line){
        $line       = trim($line);
        if($line==null){continue;}
        $text_class=null;
	    if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?ArticaSandBox\[([0-9]+)\]:(.+)#",$line,$re)){
	        echo "<H1>$line <code>$line</code></H1>\n";
	        continue;
        }





        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $time=$re[1] . " ". $re[2]." ".$re[3];
        $pid=$re[4];
        $events=trim($re[5]);


        if(preg_match("#(ERROR|CORRUPTED)#",$line)){$text_class="text-warning font-bold";}
        if(preg_match("#(MALICIOUS|DETECTED)\s+#",$line)){$text_class="text-danger font-bold";}
        if(preg_match("#(OK|CLEAN)\s+#",$line)){$text_class="text-success";}



		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" width='1%' nowrap=''>$time</td>";
        $html[]="<td class=\"$text_class\" width='1%' nowrap=''>$pid</td>";
        $html[]="<td class=\"$text_class\">$events</td>";
		$html[]="</tr>";
		

	}

    $html[]=$tpl->table_footer("table-$t",count($fields),false);
    echo $tpl->_ENGINE_parse_body($html);

}
