<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["disconnect"])){disconnect();exit;}
if(isset($_POST["disconnect"])){disconnect_perform();exit;}


page();


function disconnect(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");

    $ID=intval($_GET["disconnect"]);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM rdpproxy_sessions WHERE ID=$ID");
    $ip_client=$ligne["ip_client"];
    $psid=$ligne["psid"];

    $tpl->js_dialog_confirm_action("{disconnect}: {session} $psid","disconnect",$ID,"LoadAjax('table-rdpproxy-sessions','$page?table=yes');");

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
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {sessions}</h1>
	<p>{APP_RDPPROXY_EXPLAIN}</p>

	</div>

	</div>
		

		
	<div class='row'><div id='progress-rdpproxy-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-rdpproxy-sessions'></div>

	</div>
	</div>
		
		
		
	<script>
	$.address.state('/');
	$.address.value('/rdpproxy-sessions');
	LoadAjax('table-rdpproxy-sessions','$page?table=yes');
		
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

//xtime,sessionid,psid,ip_client,userid,target_login,pkill

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{updated}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{client}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{targets}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{rules}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM rdpproxy_sessions ORDER BY xtime DESC");
if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

    $tpl2=new templates();
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $xtime=$ligne["xtime"];
        $sessionid=$ligne["sessionid"];
        $ID=$ligne["ID"];
        preg_match("#([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+):([0-9]+)-([0-9]+)-([0-9]+)#",$sessionid,$re);
        $status="<span class='label label-primary'>{connected}</span>";

        $Year=$re[1];
        $month=$re[2];
        $day=$re[3];
        $hour=$re[4];
        $min=$re[4];
        $timeCreated=$ligne["created"];
        $ruleid=$re[6];
        $targetid=$re[7];
        $userid=$re[8];
        $ip_client=$ligne["ip_client"];
        $pkill=$ligne["pkill"];
        $username_db=$ligne["username"];

        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $created=$tpl->time_to_date($timeCreated,true);
        $updated=$tpl->time_to_date($xtime,true);

        $distance=distanceOfTimeInWords($timeCreated,$xtime);

        if($userid>0) {
            $ligne2 = $q->mysqli_fetch_array("SELECT username FROM members WHERE ID=$userid");
            $username = "<strong>" . $tpl->td_href($ligne2["username"], null, "Loadjs('fw.rdpproxy.connections.php?member-js=$userid')") . "</strong>";
        }else{
            $username=$username_db;
        }
        $target_login=$ligne["target_login"];

        $client=$ip_client. "<br><small>($username/$target_login)</small>";




        $ligne2=$q->mysqli_fetch_array("SELECT alias,target_host,target_device,target_port FROM targets WHERE ID=$targetid");
        $alias=$ligne2["alias"];
        $target_host=$ligne2["target_host"];
        $target_device=$ligne2["target_device"];
        $target_port=$ligne2["target_port"];
        $target="<strong>".$tpl->td_href("$alias",null,"Loadjs('fw.rdpproxy.connections.php?target-js=$targetid')")."</strong><br><small>($target_host:$target_port,$target_device:$target_port)</small>";


        $ligne2=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID=$ruleid");
        $rulename= "<strong>".$tpl->td_href($ligne2["groupname"],null,"Loadjs('fw.rdpproxy.connections.php?group-js=$ruleid')")."</strong>";

        if($pkill==1){
            $status="<span class='label label-danger'>{closing}</span>";

        }

        $diff=(time()-$xtime)/60;

        if($diff>5){
            $status="<span class='label label-warning'>{unknown}</span>";
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"center\" width='1%' nowrap>$status</td>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fa fa-desktop'></i>&nbsp;$created</td>";
        $html[]="<td class=\"\" width=1% nowrap>$updated<br><small>$distance</small></td>";
        $html[]="<td class=\"\" nowrap>$client</td>";
        $html[]="<td class=\"\" nowrap>$target</td>";
        $html[]="<td class=\"\" nowrap>$rulename</td>";
        $html[]="<td class=\"\" width=1% nowrap>". $tpl->icon_unlink("Loadjs('$page?disconnect=$ID')")."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='7'>";
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