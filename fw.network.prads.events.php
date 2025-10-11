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
    if(!isset($_SESSION["PRADS_SEARCH"])){$_SESSION["PRADS_SEARCH"]="";}

    $html="
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["PRADS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
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

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_PRADS}",$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);

}

function search(){
    $time=null;
    $sock=new sockets();
    $tpl=new template_admin();
    $MAIN=$tpl->format_search_protocol($_GET["search"],false,true,false,false);
    $line=base64_encode(serialize($MAIN));
    $sock->getFrameWork("prads.php?service-events=$line");
    $filename=PROGRESS_DIR."/prads.syslog";
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";


    $labels["DEBUG"]="label-warning";
    $labels["END"]="label-primary";
    $labels["INFO"]="label-info";
    $labels["WARNING"]="label-warning";
    $labels["ALERT"]="label-danger";

    $TEXT["END"]="text-success";
    $TEXT["INFO"]="";
    $TEXT["DEBUG"]="text-muted";
    $TEXT["WARNING"]="text-warning";
    $TEXT["ALERT"]="text-danger";

    $data=explode("\n",@file_get_contents($filename));
    if(count($data)>3){$_SESSION["PRADS_SEARCH"]=$_GET["search"];}
    krsort($data);

    foreach ($data as $line){

        $line=trim($line);
        if(!preg_match("#^([0-9\-]+)\s+([0-9:]+)\s+(.+)#", $line,$re)) {
            continue;
        }
        $text="text-muted";
        $time=strtotime($re[1]." ".$re[2]);
        $zdate=$tpl->time_to_date($time,true);
        if(preg_match("#(discover|success)#i",$line)){$text="text-success";}
        if(preg_match("#(fatal|error)#i",$line)){$text="text-danger";}

        $line="<span class='$text'>$line</span>";



        $html[]="<tr>
				<td width=1% nowrap>$zdate</td>
				<td>$line</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/prads.syslog.query")."</i></div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}
