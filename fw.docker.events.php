<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){exit();}
if(isset($_GET["search"])){search();exit;}
page();


function page():bool{
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_DOCKER} {events}",ico_eye,
        "{events}",null,"docker-events","progress-docker-restart",
        true,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function search(){
    $tpl=new template_admin();
	$MAIN=$tpl->format_search_protocol($_GET["search"],false,false,false,true);
	$line=base64_encode(serialize($MAIN));
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?events=$line");
	$filename=PROGRESS_DIR."/docker.syslog";
	$date_text=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>&nbsp;</th>
        	<th>$date_text</th>
        	<th>$events</th>
        	<th>{topic}</th>
        </tr>
  	</thead>
	<tbody>
";
	
	$data=explode("\n",@file_get_contents($filename));
	if(count($data)>3){$_SESSION["DOCKER_SEARCH"]=$_GET["search"];}
	rsort($data);

    $INFOS["info"]="<span class='label label-info'>{info}</span>";
    $INFOS["warning"]="<span class='label label-warning'>{warning}</span>";
    $INFOS["error"]="<span class='label label-danger'>{error}</span>";

    $COLORS["info"]="text-default";
    $COLORS["warning"]="text-warning font-bold";
    $COLORS["error"]="text-danger font-bold";
	
	foreach ($data as $line){
		$line=trim($line);
        $line=str_replace('\"',"&quot;",$line);
        $FTime=0;
        $container=null;$module=null;
        $namespace=null;
        $topic=null;
        if(!preg_match('#^time="(.+?)\s+level=(.+?)\s+msg="(.+?)"#',$line,$re)){continue;}
        if(preg_match("#([0-9\-]+)T([0-9:]+)#",$re[1],$ri)) {
            $FTime = strtotime($ri[1]." ".$ri[2]);
        }
        $level=$re[2];
        $zDate=$tpl->time_to_date($FTime,true);
        $msg=$re[3];
        if(preg_match('#container=(.+?)\s+module=(.+?)\s+namespace=(.+?)\s+topic=(.+?)\s+type="(.+?)"#',$line,$re)){
            $container=$re[1];
            $module=$re[2];
            $namespace=$re[3];
            $topic=$re[4];

        }

        $color=$COLORS[$level];
		
		$html[]="<tr>
				<td width=1% class='$color' nowrap>$INFOS[$level]</td>
				<td width=1% class='$color' nowrap>$zDate</td>
				<td class='$color' nowrap >$msg</td>
				<td width=1% class='$color' nowrap>$topic</td>
				</tr>";
		
	}
	
	$html[]="</tbody></table>";
	$html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/docker.syslog.pattern")."</i></div>";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
	
	
}

