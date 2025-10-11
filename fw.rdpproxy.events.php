<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["APP_RDPPROXY_AUTHHOOK"])){APP_RDPPROXY_AUTHHOOK_EVENTS();exit;}
if(isset($_POST["disconnect"])){disconnect_perform();exit;}
if(isset($_GET["AUTHHOOK-SEARCH"])){APP_RDPPROXY_AUTHHOOK_SEARCH();exit;}
if(isset($_GET["APP_RDPPROXY"])){APP_RDPPROXY_EVENTS();exit;}
if(isset($_GET["APP-RDPPROXY-SEARCH"])){APP_RDPPROXY_SEARCH();exit;}



page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {events}</h1>
	<p>{APP_RDPPROXY_EXPLAIN}</p>

	</div>

	</div>
		

		
	<div class='row'><div id='progress-rdpproxy-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-rdpproxy-events'></div>

	</div>
	</div>
		
		
		
	<script>
	$.address.state('/');
	$.address.value('/rdpproxy-events');
	LoadAjax('table-rdpproxy-events','$page?tabs=yes');
		
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {events}",$html);
        echo $tpl->build_firewall();
        return;
    }



    echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{APP_RDPPROXY_AUTHHOOK}"]="$page?APP_RDPPROXY_AUTHHOOK=yes";
    $array["{APP_RDPPROXY}"]="$page?APP_RDPPROXY=yes";
    echo $tpl->tabs_default($array);
}

function APP_RDPPROXY_EVENTS(){
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["AUTHHOOK-SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	      		<span class=\"input-group-btn\"><button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button></span>
	     	</div>
    	</div>
 <div id='table-$t'></div>";


    $html[]="<script>function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-$t','$page?APP-RDPPROXY-SEARCH='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";


    echo $tpl->_ENGINE_parse_body($html);

}

function APP_RDPPROXY_AUTHHOOK_EVENTS(){
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["AUTHHOOK-SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	      		<span class=\"input-group-btn\"><button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button></span>
	     	</div>
    	</div>
 <div id='table-$t'></div>";


    $html[]="<script>function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-$t','$page?AUTHHOOK-SEARCH='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";


    echo $tpl->_ENGINE_parse_body($html);
    
}
function APP_RDPPROXY_SEARCH(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $_SESSION["AUTHHOOK-SEARCH"]=$_GET["APP-RDPPROXY-SEARCH"];
    $MAIN=$tpl->format_search_protocol($_GET["APP-RDPPROXY-SEARCH"]);
    $line=base64_encode(serialize($MAIN));
    $sock->getFrameWork("rdpproxy.php?RDPPROXY-SEARCH=$line&file=".urlencode("/var/log/rdpproxy/daemon.log"));
    $filedest=PROGRESS_DIR."/rdpproxy.daemon.log";
    $exploded=explode("\n",@file_get_contents($filedest));
    krsort($exploded);


    $html[]="<table class='table table-striped'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{time}</th>";
    $html[]="<th>{type}</th>";
    $html[]="<th>{events}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $LABELS["INFO"]="label label-info";
    $LABELS["DEBUG"]="label";
    $LABELS["ERR"]="label label-danger";
    $LABELS["WARN"]="label label-warning";

    $LABELST["INFO"]="text-primary";
    $LABELST["DEBUG"]="text-primary";
    $LABELST["ERR"]="text-danger font-bold";
    $LABELST["WARN"]="text-warning font-bold";



    foreach ($exploded as $line){
        $line=trim($line);
        if($line==null){continue;}

        if(preg_match("#(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?\s+rdpproxy:\s+\[rdpproxy\]\s+(.+)#",$line,$re)){
            $date="{$re[1]} {$re[2]} {$re["3"]}";
            $type="INFO";
            $line=$re[4];
        }

        if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?\s+rdpproxy:\s+([A-Z]+)\s+.+?\s+(.+)#",$line,$re)){
            $date="{$re[1]} {$re[2]} {$re["3"]}";
            $type=$re[4];
            $line=$re[5];

        }
        $xtime=strtotime($date);
        $content=htmlentities($line);
        if($type=="WARNING"){$type="WARN";}

        $html[]="<tr>";
        $html[]="<td width='1%' nowrap>".$tpl->time_to_date($xtime,true)."</td>";
        $html[]="<td width='1%' nowrap><span class='{$LABELS["$type"]}'>$type</span></td>";
        $html[]="<td><span class='{$LABELST[$type]}'>$content</span></td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);







}


function APP_RDPPROXY_AUTHHOOK_SEARCH(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $_SESSION["AUTHHOOK-SEARCH"]=$_GET["AUTHHOOK-SEARCH"];
    $MAIN=$tpl->format_search_protocol($_GET["AUTHHOOK-SEARCH"]);
    $line=base64_encode(serialize($MAIN));
    $sock->getFrameWork("rdpproxy.php?LOGS-SEARCH=$line&file=".urlencode("/var/log/rdpproxy/auth.log"));
    $filedest=PROGRESS_DIR."/rdpproxy.syslog";
    $exploded=explode("\n",@file_get_contents($filedest));
    krsort($exploded);


    $html[]="<table class='table table-striped'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{time}</th>";
    $html[]="<th>{type}</th>";
    $html[]="<th>{events}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $LABELS["INFO"]="label label-info";
    $LABELS["DEBUG"]="label";
    $LABELS["ERROR"]="label label-danger";

    $LABELST["INFO"]="text-primary";
    $LABELST["DEBUG"]="text-primary";
    $LABELST["ERROR"]="text-danger font-bold";


    
    foreach ($exploded as $line){
        $line=trim($line);
        if($line==null){continue;}
        $type="DEBUG";
        $content=null;
        if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+).*?rdpproxy-auth\[.*?\[([A-Z]+)\]([:|\s+])(.+)#",$line,$re)){
            echo "<code>$line</code><br>";
            continue;}
            $Month=$tpl->MonthToInteger($re[1]);
            $Day=$re[2];
            $time=$re[3];
            $type=$re[4];
            $content=trim($re[6]);


        $content=htmlentities($content);
        $content=str_replace("#012","<br>",$content);
        $xtime=strtotime(date("Y")."-$Month-$Day $time");
        $html[]="<tr>";
        $html[]="<td width='1%' nowrap>".$tpl->time_to_date($xtime,true)."</td>";
        $html[]="<td width='1%' nowrap><span class='{$LABELS["$type"]}'>$type</span></td>";
        $html[]="<td><span class='{$LABELST[$type]}'>$content</span></td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
echo $tpl->_ENGINE_parse_body($html);

}