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
    if(!isset($_SESSION["NGINXD_SEARCH"])){$_SESSION["NGINXD_SEARCH"]="50 events";}
    $html="
	<div class=\"row\" style='margin-top: 10px'> 
		<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["NGINXD_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
	      		<span class=\"input-group-btn\"><button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button></span>
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
    $sock->getFrameWork("nginx.php?modsecurity-events=$line");
    $filename="/usr/share/artica-postfix/ressources/logs/web/modsecurity.log";

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th nowrap>{session}</th>
        	<th nowrap>{path}</th>
            <th nowrap>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    $data=explode("\n",@file_get_contents($filename));
    if(count($data)>3){$_SESSION["NGINXD_SEARCH"]=$_GET["search"];}
    rsort($data);
    $c=0;
    foreach ($data as $line){
        $t1=time();
        $line=trim($line);
        $ruleid=0;
        $rulename=null;
        $ACTION=null;
        $FF=false;
        if(!preg_match("#^\[(.+?)\]\s+\[(.*?)\]\s+(.+)#", $line,$re)){continue;}
        $session_id=$re[1];
        $events=$re[2];
        $class="";

        if(preg_match("#Cannot#",$re[3])){
            $class="text-warning";
        }

        $c++;
        $html[]="<tr>
				<td width='1%' class='$class' nowrap>$session_id</td>
				<td width='1%' class='$class' nowrap>{$re[2]}</td> 
				<td width='99%' class='$class'><strong>{$re[3]}</strong></td>  
                </tr>";

        $t2=time();
        if($GLOBALS["VERBOSE"]){
            VERBOSE("$c  ".($t2-$t1)." seconds");
        }

    }

    $html[]="</tbody></table>";

    $html[]="<script>";
    $TINY_ARRAY["TITLE"]="{service_events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_NGINX_FW_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=null;
    $html[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');\n";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);



}
