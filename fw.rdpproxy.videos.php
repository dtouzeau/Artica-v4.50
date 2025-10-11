<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["remove"])){remove_video_ask();exit;}
if(isset($_POST["remove"])){remove_video_perform();exit;}

page();
function download(){
    $ID=intval($_GET["download"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM videos WHERE ID='$ID'");
    $connection_date=$ligne["connection_date"];
    $psize=$ligne["psize"];
    $path=$ligne["path"];

    $RDPProxyVideoPathConf=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoPath");
    if($RDPProxyVideoPathConf==null){$RDPProxyVideoPathConf="/home/artica/rds/videos";}

    header('Content-type: application/zip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"{$connection_date}_.$path.zip\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$psize);
    ob_clean();
    flush();
    readfile("$RDPProxyVideoPathConf/$path");

}

function remove_video_ask(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["remove"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM videos WHERE ID=$ID");
    $duration=$ligne["duration"];
    $psize=$ligne["psize"];
    $path=$ligne["path"];
    $ID=$ligne["ID"];
    $psize=FormatBytes($psize/1024);

    $after="$('#$md').remove();";
    $tpl->js_confirm_delete("$path<br>$psize<br>{duration}: $duration","remove",$ID,$after);
}
function remove_video_perform(){
    $ID=intval($_POST["remove"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("rdpproxy.php?remove-video=$ID");
}

function disconnect(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");

    $ID=intval($_GET["disconnect"]);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM rdpproxy_sessions WHERE ID=$ID");
    $ip_client=$ligne["ip_client"];
    $psid=$ligne["psid"];

    $tpl->js_dialog_confirm_action("{disconnect}: {session} $psid","disconnect",$ID,"LoadAjax('table-rdpproxy-videos','$page?table=yes');");

}
function disconnect_perform(){
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("UPDATE rdpproxy_sessions SET pkill=1 WHERE ID={$_POST["disconnect"]}");

}






function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {videos}</h1>
	<p>{APP_RDPPROXY_EXPLAIN}</p>

	</div>

	</div>
		

		
	<div class='row'><div id='progress-rdpproxy-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-rdpproxy-videos'></div>

	</div>
	</div>
		
		
		
	<script>
	$.address.state('/');
	$.address.value('/rdpproxy-videos');
	LoadAjax('table-rdpproxy-videos','$page?table=yes');
		
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {sessions}",$html);
        echo $tpl->build_firewall();
        return;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy-videos.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy-videos.progress.txt";
    $ARRAY["CMD"]="rdpproxy.php?videos=yes";
    $ARRAY["TITLE"]="{synchronization}";
    $ARRAY["AFTER"]="LoadAjax('table-rdpproxy-videos','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $restartService="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rdpproxy-restart');";



    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>".
        $tpl->button_label_table("{synchronization}", $restartService, "fas fa-sync-alt","AsSquidAdministrator")."

			</div>");


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{videos}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{rules}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{client}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{targets}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM videos ORDER BY connection_date DESC");
if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

    $tpl2=new templates();
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $connection_date=$ligne["connection_date"];
        $rule_name=$ligne["rule_name"];
        $rule_id=$ligne["rule_id"];
        $username=$ligne["username"];
        $userid=$ligne["userid"];
        $hostname=$ligne["hostname"];
        $hostid=$ligne["hostid"];
        $duration=$ligne["duration"];
        $psize=$ligne["psize"];
        $path=$ligne["path"];
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $created=$tpl->time_to_date($connection_date,true);
        $link=$tpl->ClickMouse("pop:$page?download=$ID");
        $path="<a href=\"#\" $link>$path</a>";
        $psize=FormatBytes($psize/1024);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\"  width=1% nowrap><i class=\"fas fa-film\"></i>&nbsp;$created</td>";
        $html[]="<td class=\"\" nowrap>$path&nbsp;<small>$psize</small></td>";
        $html[]="<td class=\"\" width='1%'  nowrap>$rule_name</td>";
        $html[]="<td class=\"\" width='1%'  nowrap>$username</td>";
        $html[]="<td class=\"\" width='1%'  nowrap>$hostname</td>";
        $html[]="<td class=\"\" width=1% nowrap>". $tpl->icon_delete("Loadjs('$page?remove=$ID&md=$md')","AsSquidAdministrator")."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}