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
	if($_SESSION["KERNELEVNTS"]==null){$_SESSION["KERNELEVNTS"]="limit 200";}
	
	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{events}: {system_kernel}</h1><p>{kernel_infos_text}</p></div>
	</div>
		

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["KERNELEVNTS"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
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
	    $.address.value('icap-events');		
		Start$t();
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{icap_events}");
        return;
    }
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl                    = new template_admin();
	$page                   = CurrentPageName();
	$t                      = $_GET["t"];
	$target_file            = PROGRESS_DIR."/kernel-events.log";
    $_SESSION["KERNELEVNTS"]   = trim(strtolower($_GET["search"]));
    $search                 = $tpl->query_pattern(trim(strtolower($_GET["search"])));
    $ss                     = base64_encode($search["S"]);

    if(!is_numeric($t)){$t=time();}




	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?kernel-events=$ss&rp={$search["MAX"]}");
	$datas=explode("\n",@file_get_contents($target_file));


	$fields[]="{time}";
    $fields[]="{events}";

    $html[]=$tpl->table_head($fields,"table-$t");

	$TRCLASS=null;
	krsort($datas);
    $pregm="^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\](.+)";


    if(strpos($datas[0],"tail: cannot open")>0){
        echo $tpl->div_warning("{no_data}");
        return false;
    }

	foreach ($datas as $key=>$line){
        $line       = trim($line);
        if($line==null){continue;}
        $text_class=null;

	    if(!preg_match("#$pregm#",$line,$re)){
	        echo "<H1>$line <code>$pregm</code></H1>\n";
	        continue;
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $date=$re[1]." {$re[2]} {$re[3]}";
        $line=$tpl->logs_color($re[4]);



		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\">$date</td>";
		$html[]="<td class=\"$text_class\">$line</td>";
		$html[]="</tr>";
		

	}

    $html[]=$tpl->table_footer("table-$t",count($fields),false);
    echo $tpl->_ENGINE_parse_body($html);
    return false;
}
