<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["members-start"])){members_start();exit;}
if(isset($_GET["members-table"])){members_table();exit;}
if(isset($_GET["member-ad-js"])){member_ad_js();exit;}
if(isset($_GET["member-ad-popup"])){member_ad_popup();exit;}
if(isset($_GET["member-js"])){member_js2();exit;}
if(isset($_GET["member-popup"])){member_popup();exit;}
if(isset($_GET["member-delete"])){member_delete();exit;}
if(isset($_POST["member-delete"])){member_delete_perform();exit;}
if(isset($_POST["member-id"])){member_save();exit;}
if(isset($_POST["member-ad-id"])){member_ad_save();exit;}
if(isset($_GET["groups-start"])){groups_start();exit;}
if(isset($_GET["groups-table"])){groups_table();exit;}
if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-param"])){group_params();exit;}
if(isset($_POST["group-params"])){group_params_save();exit;}
if(isset($_GET["group-enable"])){group_enable();exit;}
if(isset($_GET["group-status"])){echo groups_status($_GET["group-status"]);exit;}
if(isset($_GET["group-delete"])){echo group_delete();exit;}
if(isset($_POST["group-delete"])){echo group_delete_perform();exit;}

if(isset($_GET["group-tabs"])){group_tabs();exit;}
if(isset($_GET["groups-members-start"])){group_members_start();exit;}
if(isset($_GET["group-members-table"])){group_members_table();exit;}

if(isset($_GET["target-status"])){target_status();exit;}
if(isset($_GET["target-enable"])){target_enable();exit;}
if(isset($_GET["target-delete"])){target_delete();exit;}
if(isset($_POST["target-delete"])){target_delete_perform();exit;}
if(isset($_GET["targets-table"])){targets_table();exit;}
if(isset($_GET["targets-start"])){targets_start();exit;}
if(isset($_GET["target-ad-js"])){targets_ad_js();exit;}
if(isset($_GET["target-js"])){targets_js();exit;}
if(isset($_GET["target-popup"])){targets_popup();exit;}
if(isset($_GET["target-ad-popup"])){targets_ad_popup();exit;}
if(isset($_POST["target-id"])){targets_save();exit;}
if(isset($_POST["target-ad-id"])){targets_ad_save();exit;}
if(isset($_GET["member-link"])){member_link_js();exit;}
if(isset($_GET["member-link-table"])){member_link_table();exit;}
if(isset($_GET["member-link-perform"])){member_link_perform();exit;}
if(isset($_GET["member-unlink"])){member_unlink();exit;}


if(isset($_GET["groups-targets-start"])){group_targets_start();exit;}
if(isset($_GET["groups-targets-table"])){group_targets_table();exit;}
if(isset($_GET["target-link"])){target_link_js();exit;}
if(isset($_GET["target-link-table"])){target_link_table();exit;}
if(isset($_GET["target-link-perform"])){target_link_perform();exit;}
if(isset($_GET["target-unlink"])){target_unlink();exit;}

if(isset($_GET["groups-network-start"])){group_network_start();exit;}
if(isset($_GET["groups-network-table"])){group_network_table();exit;}
if(isset($_GET["network-unlink"])){group_network_delete();exit;}
if(isset($_GET["network-link"])){group_network_link_js();exit;}
if(isset($_GET["network-link-popup"])){group_network_link_popup();exit;}
if(isset($_POST["network-link"])){group_network_link_save();exit;}

if(isset($_POST["group-time-save"])){group_time_save();exit;}
if(isset($_GET["group-time-start"])){group_time_start();exit;}
if(isset($_GET["group-time-table"])){group_time_table();exit;}
if(isset($_GET["group-time-popup"])){group_time_popup();exit;}
if(isset($_GET["time-link"])){group_time_link_js();exit;}
if(isset($_GET["time-unlink"])){group_time_unlink_js();exit;}

page();


function group_tabs(){
    $ID=intval($_GET["group-tabs"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID=$ID");
    $title=$ligne["groupname"];

    $array["{$title}"]="$page?group-param=$ID";
    $array["{members}"]="$page?groups-members-start=$ID";
    $array["{targets}"]="$page?groups-targets-start=$ID";
    $array["{networks}"]="$page?groups-network-start=$ID";
    $array["{time}/{schedule}"]="$page?group-time-start=$ID";
    echo $tpl->tabs_default($array);
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{policies}"]="$page?groups-start=yes";
    $array["{members}"]="$page?members-start=yes";
    $array["{targets}"]="$page?targets-start=yes";
    echo $tpl->tabs_default($array);
}

function member_delete(){
    $ID=intval($_GET["member-delete"]);
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT username FROM members WHERE ID=$ID");
    $tpl->js_confirm_delete("{delete_user} {$ligne["username"]}","member-delete",$ID,"$('#$md').remove()");

}
function target_delete(){
    $ID=intval($_GET["target-delete"]);
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM targets WHERE ID=$ID");
    $target_login=$ligne["target_login"];
    $proto_dest=$ligne["proto_dest"];
    $target_host=$ligne["target_host"];
    $target_device=$ligne["target_device"];
    $target_port=$ligne["target_port"];
    $tpl->js_confirm_delete("{$target_login} $proto_dest/$target_port $target_host $target_device","target-delete",$ID,"$('#$md').remove()");

}
function target_delete_perform(){
    $ID=intval($_POST["target-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("DELETE FROM targets WHERE ID=$ID");
    $q->QUERY_SQL("DELETE FROM link_target WHERE targetid=$ID");
}

function member_delete_perform(){
    $ID=intval($_POST["member-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("DELETE FROM members WHERE ID=$ID");
    $q->QUERY_SQL("DELETE FROM link_members WHERE userid=$ID");

}

function group_time_link_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["time-link"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID=$gpid");
    $title=$ligne["groupname"];
    $tpl->js_dialog2("$title","$page?group-time-popup=$gpid",650);

}
function group_time_unlink_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["md"];
    $ID=intval($_GET["time-unlink"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("DELETE FROM timeline WHERE ID=$ID");
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return;
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";

}



function group_time_save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $gpid=intval($_POST["group-time-save"]);
    $thour1=$_POST["thour1"];
    $thour2=$_POST["thour2"];
    $tday=$_POST["tday"];
    $date1=strtotime(date("Y-m-d")." $thour1");
    $date2=strtotime(date("Y-m-d")." $thour2");
    if($date1>$date2){
        echo $tpl->_ENGINE_parse_body("{wrong_time} $thour1 - $thour2");
        return;
    }

    $sql="INSERT OR IGNORE INTO timeline (gpid,tday,thour1,thour2,ttl) VALUES ('$gpid','$tday','$thour1','$thour2',0)";

    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return;}

}

function group_time_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["group-time-popup"]);

    $tpl->field_hidden("group-time-save",$gpid);
    $array_day_of_week=array(
        "6"=>"{Sunday}",
        "0"=>"{Monday}",
        "1"=>"{Tuesday}",
        "2"=>"{Wednesday}",
        "3"=>"{Thursday}",
        "4"=>"{Friday}",
        "5"=>"{Saturday}");

    $form[]=$tpl->field_array_hash($array_day_of_week,"tday","{day}",0);
    $form[]=$tpl->field_clock("thour1","{from_time}","07:30:00");
    $form[]=$tpl->field_clock("thour2","{to_time}","23:59:00");

    $bt="{add}";
    $js="dialogInstance2.close();LoadAjax('rdpproxy-time-table-$gpid','$page?group-time-table=$gpid');";
    echo $tpl->form_outside("{add_working_period}",$form,null,$bt,$js,"AllowAddUsers");

}
function targets_ad_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-ad-js"]);
    if($ID==0){
        $title="{new_target} {active_directory_group}";
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT alias FROM targets WHERE ID=$ID");
        $title=$ligne["alias"];
    }

    $tpl->js_dialog1($title,"$page?target-ad-popup=$ID",750);

}

function targets_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-js"]);
    if($ID==0){
        $title="{new_target}";
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT alias FROM targets WHERE ID=$ID");
        $title=$ligne["alias"];
    }

    $tpl->js_dialog1($title,"$page?target-popup=$ID",750);

}
function group_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["group-js"]);
    if($ID==0) {
        $title = "{new_policy}";
        $tpl->js_dialog1($title, "$page?group-param=$ID", 650);
        return;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID=$ID");
    $title=$ligne["groupname"];
    $tpl->js_dialog1($title,"$page?group-tabs=$ID",900);


}
function member_link_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-link"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID=$ID");
    $title=$ligne["groupname"];
    $tpl->js_dialog2($title,"$page?member-link-table=$ID",400);

}
function target_link_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-link"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID=$ID");
    $title=$ligne["groupname"];
    $tpl->js_dialog2($title,"$page?target-link-table=$ID",800);
}
function group_network_link_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["network-link"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID=$ID");
    $title=$ligne["groupname"];
    $tpl->js_dialog2($title,"$page?network-link-popup=$ID",550);

}

function group_network_link_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["network-link-popup"]);
    $title = "{new_policy}";
    $bt = "{add}";
    $js = "dialogInstance2.close();LoadAjax('rdpproxy-network-table-$ID','$page?groups-network-table=$ID');";

    $tpl->field_hidden("network-link",$ID);
    $form[]= $tpl->field_text("network","{network}","0.0.0.0/0",true);

    echo $tpl->form_outside($title,$form,"{pdns_network_item_add}",$bt,$js,"AllowAddUsers");

}
function group_network_link_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $gpid=$_POST["network-link"];
    $sql="INSERT OR IGNORE INTO networks (pattern,gpid) VALUES ('{$_POST["network"]}',$gpid)";
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){$q->mysql_error;return;}
}

function target_link_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $gpid=intval($_GET["target-link-table"]);
    $ALREADY=array();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM link_target WHERE gpid='$gpid'");
    foreach ($results as $index=>$ligne){
        $targetid=$ligne["targetid"];
        $ALREADY[$targetid]=true;


    }

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{targets}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM targets order by alias");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $targetid=$ligne["ID"];
        if(isset( $ALREADY[$targetid])){continue;}
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $alias=$ligne["alias"];
        $designation=$ligne["designation"];
        $target_host=$ligne["target_host"];
        $target_device=$ligne["target_device"];
        $target_port=$ligne["target_port"];
        $icon_user="<i class='fas fa-server'></i>";

        if(preg_match("#CN=(.*?),#i",$alias,$re)){
            $alias="<strong>{$re[1]}</strong>";
            $icon_user="<i class='fa fa-users'></i>";
            $target_host=$alias;
        }

        $gps=array();
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap>$icon_user&nbsp;$alias $target_host:$target_port ($target_device)</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->button_autnonome("{link}","Loadjs('$page?target-link-perform=$ID&md=$md&gpid=$gpid')","fas fa-link","AllowAddUsers",0,"btn-primary","small") ."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
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

function member_link_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $gpid=intval($_GET["member-link-table"]);
    $ALREADY=array();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM link_members WHERE gpid='$gpid'");
    foreach ($results as $index=>$ligne){
        $userid=$ligne["userid"];
        $ALREADY[$userid]=true;


    }

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM members order by username");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $userid=$ligne["ID"];
        if(isset( $ALREADY[$userid])){continue;}
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $ligne2=$q->mysqli_fetch_array("SELECT username FROM members WHERE ID=$userid");
        $username=$ligne2["username"];
        $icon_user="<i class='fa fa-user'></i>";

        if(preg_match("#CN=(.*?),#i",$username,$re)){
            $username=$re[1];
            $icon_user="<i class='fa fa-users'></i>";
        }

//<i class="fas fa-link"></i>
        $gps=array();
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap>$icon_user&nbsp;$username</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->button_autnonome("{link}","Loadjs('$page?member-link-perform=$ID&md=$md&gpid=$gpid')","fas fa-link","AllowAddUsers",0,"btn-primary","small") ."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
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
function target_link_perform(){
    $page=CurrentPageName();
    $targetid=$_GET["target-link-perform"];
    $md=$_GET["md"];
    $gpid=$_GET["gpid"];
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("INSERT INTO link_target (targetid,gpid) VALUES ('$targetid','$gpid')");
    if(!$q->ok){echo $q->mysql_error;return;}
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');\n";
    echo "LoadAjax('rdpproxy-targets-table-$gpid','$page?groups-targets-table=$gpid');\n";

}
function member_link_perform(){
    $page=CurrentPageName();
    $userid=$_GET["member-link-perform"];
    $md=$_GET["md"];
    $gpid=$_GET["gpid"];
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("INSERT INTO link_members (userid,gpid) VALUES ('$userid','$gpid')");
    if(!$q->ok){echo $q->mysql_error;return;}
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');\n";
    echo "LoadAjax('rdpproxy-members-table-$gpid','$page?group-members-table=$gpid');";

}
function member_unlink(){
    $ID=intval($_GET["member-unlink"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("DELETE FROM link_members WHERE ID=$ID");

    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    echo "LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');\n";
    echo "$('#$md').remove();\n";
}
function target_unlink(){
    $ID=intval($_GET["target-unlink"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("DELETE FROM link_target WHERE ID=$ID");

    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    echo "LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');\n";
    echo "$('#$md').remove();\n";
}

function group_params(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["group-param"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM groups WHERE ID=$ID");
    if($ID==0) {
        $title = "{new_policy}";
        $bt = "{add}";
        $js = "dialogInstance1.close();LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');";
    }else{
        $title="";
        $bt="{apply}";
        $js="LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');";
    }

    $CACHE_AGES[0]="{never}";
    $CACHE_AGES[5]="5 {minutes}";
    $CACHE_AGES[10]="10 {minutes}";
    $CACHE_AGES[15]="15 {minutes}";
    $CACHE_AGES[30]="30 {minutes}";
    $CACHE_AGES[60]="1 {hour}";
    $CACHE_AGES[120]="2 {hours}";
    $CACHE_AGES[180]="3 {hours}";
    $CACHE_AGES[240]="4 {hours}";
    $CACHE_AGES[360]="6 {hours}";
    $CACHE_AGES[720]="12 {hours}";
    $CACHE_AGES[1440]="24 {hours}";


    $tpl->field_hidden("group-params",$ID);
    $form[]= $tpl->field_text("groupname","{rulename}",$ligne["groupname"],true);
    $form[]= $tpl->field_text("description","{description}",$ligne["description"],true);
    $form[]= $tpl->field_checkbox("user_rec","{video_recording}",$ligne["user_rec"]);
    $form[]=$tpl->field_array_hash($CACHE_AGES,"session_time","{session_time}",$ligne["session_time"]);
    echo $tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");


}

function group_params_save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["group-params"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    patch_table();



    if($ID==0){
        $sql="INSERT OR IGNORE INTO groups (groupname,description,session_time,user_rec) VALUES ('{$_POST["groupname"]}','{$_POST["description"]}','{$_POST["session_time"]}','{$_POST["user_rec"]}')";
    }else{
      $sql="UPDATE groups SET   
      groupname='{$_POST["groupname"]}',
      description='{$_POST["description"]}',
      session_time='{$_POST["session_time"]}',
      user_rec='{$_POST["user_rec"]}'
      WHERE ID=$ID";
   }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}
}

function member_js2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-js"]);
    if($ID==0){
        $title="{new_member}";
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT username FROM members WHERE ID=$ID");
        $title=$ligne["username"];
    }

    $tpl->js_dialog1($title,"$page?member-popup=$ID",750);
}
function member_ad_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-ad-js"]);
    if($ID==0){
        $title="{active_directory_group}";
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT username FROM members WHERE ID=$ID");
        $title=$ligne["username"];
    }

    $tpl->js_dialog1($title,"$page?member-ad-popup=$ID",750);

}

function targets_ad_save(){
    patch_table();
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS("target-ad-id");
    $ID=intval($_POST["target-ad-id"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");


    $alias=$_POST["alias"];
    $proto_dest=$_POST["proto_dest"];
    $target_port=$_POST["target_port"];
    $DontResolve=$_POST["DontResolve"];

    if($ID==0){
        $sql="INSERT INTO targets(alias,proto_dest,target_port,enabled,DontResolve) VALUES ('$alias','$proto_dest','$target_port',1,$DontResolve)";
    }else{
        $sql="UPDATE targets SET alias='$alias',proto_dest='$proto_dest',target_port='$target_port',
        DontResolve=$DontResolve WHERE ID=$ID";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);}

}

function targets_ad_popup(){
    patch_table();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-ad-popup"]);
    $js="dialogInstance1.close();LoadAjax('rdpproxy-target-table','$page?targets-table=yes');";
    if($ID==0){
        $title="{active_directory_group}";
        $bt="{add}";

    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM targets WHERE ID=$ID");
        $title="{active_directory_group}: {$ligne["alias"]}";
        $bt="{apply}";

    }

    $proto_dest=$ligne["proto_dest"];
    $target_port=intval($ligne["target_port"]);



    if($target_port==0){$target_port=3389;}

    $PRT["RDP"]="RDP";
    $PRT["VNC"]="VNC";

    $tpl->field_hidden("target-ad-id",$ID);
    $form[]= $tpl->field_activedirectorygrp("alias","{active_directory_group}",$ligne["alias"],true,null,false,true);
    $form[]=$tpl->field_array_hash($PRT,"proto_dest","{protocol}",$proto_dest);
    $form[]=$tpl->field_numeric("target_port","{remote_port}",$target_port);
    $form[]=$tpl->field_checkbox("DontResolve","{DontResolve}",$ligne["DontResolve"]);
    echo $tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");
}


function member_ad_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-ad-popup"]);
    $js="dialogInstance1.close();LoadAjax('rdpproxy-members-table','$page?members-table=yes');";
    if($ID==0){
        $title="{active_directory_group}";
        $bt="{add}";

    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM members WHERE ID=$ID");
        $title="{active_directory_group}: {$ligne["username"]}";
        $bt="{apply}";

    }
    if(intval($ligne["endoflife"])==0){
        $ligne["endoflife"]=strtotime("2099-01-01 23:59:59");
    }
    $tpl->field_hidden("member-ad-id",$ID);
    $form[]= $tpl->field_activedirectorygrp("username","{active_directory_group}",$ligne["username"],true,null,false,true);
    $form[]= $tpl->field_date("endoflife","{end_of_life}",date("Y-m-d H:i:s",$ligne["endoflife"]));
    echo $tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");
}

function member_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-popup"]);
    $js="dialogInstance1.close();LoadAjax('rdpproxy-members-table','$page?members-table=yes');";
    if($ID==0){
        $title="{new_member}";
        $bt="{add}";
        $ligne["password"]=null;
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM members WHERE ID=$ID");
        $title="{member}: {$ligne["username"]}";
        $bt="{apply}";
        $ligne["password"]="thisisnotapassword";
    }

   if(intval($ligne["endoflife"])==0){
       $ligne["endoflife"]=strtotime("2099-01-01 23:59:59");
   }

   $tpl->field_hidden("member-id",$ID);
   $form[]= $tpl->field_text("username","{username}",$ligne["username"],true);
   $form[]= $tpl->field_date("endoflife","{end_of_life}",date("Y-m-d H:i:s",$ligne["endoflife"]));
   $form[]= $tpl->field_password2("password","{password}",$ligne["password"],true);

   echo $tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");

}
function targets_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["target-popup"]);
    if($ID==0){
        $title="{new_target}";
        $bt="{add}";
        $js="dialogInstance1.close();LoadAjax('rdpproxy-target-table','$page?targets-table=yes');";
        $ligne["password"]=null;
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM targets WHERE ID=$ID");
        $title="{target}: {$ligne["alias"]}";
        $bt="{apply}";
        $js="dialogInstance1.close();LoadAjaxTiny('target-status-$ID','$page?target-status=$ID');";

    }
    $alias=$ligne["alias"];
    $designation=$ligne["designation"];
    $target_password=$ligne["target_password"];
    $target_login=$ligne["target_login"];
    $proto_dest=$ligne["proto_dest"];
    $target_host=$ligne["target_host"];
    $target_device=$ligne["target_device"];
    $target_port=intval($ligne["target_port"]);
    $session_probe=$ligne["session_probe"];
    $mode_console=$ligne["mode_console"];

    $tpl->field_hidden("target-id",$ID);
    if($target_port==0){$target_port=3389;}

    $PRT["RDP"]="RDP";
    $PRT["VNC"]="VNC";

    $form[]=$tpl->field_text("alias","{alias}",$alias,true);
    $form[]=$tpl->field_text("designation","{description}",$designation,true);
    $form[]=$tpl->field_array_hash($PRT,"proto_dest","{protocol}",$proto_dest);
    $form[]=$tpl->field_text("target_host","{hostname}",$target_host,true);
    $form[]=$tpl->field_text("target_device","{address}",$target_device,false);
    $form[]=$tpl->field_numeric("target_port","{remote_port}",$target_port);
    $form[]=$tpl->field_text("target_login","{username}",$target_login,true);
    $form[]=$tpl->field_password2("target_password","{password}",$target_password,true);

    echo $tpl->form_outside($title,$form,null,$bt,$js,"AllowAddUsers");

}
function target_enable(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["target-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");

    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM targets WHERE ID=$ID");
    if(intval($ligne["enabled"])==1){$enabled=0;}else{$enabled=1;}
    $q->QUERY_SQL("UPDATE targets SET enabled=$enabled WHERE ID=$ID");
    if(!$q->ok){
        echo $tpl->js_mysql_alert($q->mysql_error);
        return;
    }

    echo "LoadAjaxTiny('target-status-$ID','$page?target-status=$ID');";


}

function patch_table(){
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    if(!$q->FIELD_EXISTS("targets","enabled")){
        $q->QUERY_SQL("ALTER TABLE targets ADD enabled NOT NULL DEFAULT 1");
    }
    if(!$q->FIELD_EXISTS("targets","DontResolve")){
        $q->QUERY_SQL("ALTER TABLE targets ADD DontResolve NOT NULL DEFAULT 0");
    }


    if(!$q->FIELD_EXISTS("members","ADGROUP")){
        $q->QUERY_SQL("ALTER TABLE members ADD ADGROUP INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("groups","session_time")){
        $q->QUERY_SQL("ALTER TABLE groups ADD session_time INTEGER");
    }
    if(!$q->FIELD_EXISTS("groups","user_rec")){
        $q->QUERY_SQL("ALTER TABLE groups ADD user_rec INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("groups","networks")){
        $q->QUERY_SQL("ALTER TABLE groups ADD networks TEXT");
        $q->QUERY_SQL("ALTER TABLE groups ADD enabled INTEGER NOT NULL DEFAULT 1");

    }


}


function targets_save(){
    patch_table();
    $tpl=new template_admin();
    $array=$tpl->CLEAN_POST("target-id");
    $ID=intval($_POST["target-id"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");




    if($ID==0){
        $array["FIELDS_ADD"][]="enabled";
        $array["VALUES_ADD"][]=1;
        $sql="INSERT OR IGNORE INTO targets (".@implode(",",$array["FIELDS_ADD"]).") 
        VALUES (".@implode(",",$array["VALUES_ADD"]).")";

    }else{
        $sql="UPDATE targets SET ".@implode(",",$array["EDIT"])." WHERE ID=$ID";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}
}

function member_ad_save(){
    patch_table();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $_POST["username"]=$q->sqlite_escape_string2($_POST["username"]);
    if(intval($_POST["endoflife"])==0){$_POST["endoflife"]=strtotime("2099-01-01 23:59:59");}
    $endoflife=strtotime($_POST["endoflife"]);
    $ID=intval($_POST["member-ad-id"]);




    if($ID==0){

        $sql="INSERT OR IGNORE INTO members (username,password,endoflife,ADGROUP) VALUES ('{$_POST["username"]}','','$endoflife',1)";
    }else {
        $pass = null;
        $sql = "UPDATE members SET username='{$_POST["username"]}',endoflife='$endoflife' WHERE ID=$ID";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}


}

function member_save(){

    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();

    $_POST["username"]=$q->sqlite_escape_string2($_POST["username"]);

    if(intval($_POST["endoflife"])==0){
        $_POST["endoflife"]=strtotime("2099-01-01 23:59:59");
    }

    $endoflife=strtotime($_POST["endoflife"]);


    $ID=intval($_POST["member-id"]);
    if($ID==0){
        $password=md5($_POST["password"]);
        $sql="INSERT OR IGNORE INTO members (username,password,endoflife) VALUES ('{$_POST["username"]}','$password','$endoflife')";
    }else {
        $pass = null;
        if ($_POST["password"] <> "thisisnotapassword") {
            $pass = ",`password`='" . md5($_POST["password"]) . "'";
        }


        $sql = "UPDATE members SET username='{$_POST["username"]}',endoflife='$endoflife'{$pass} WHERE ID=$ID";
       // echo $sql;
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}
}



function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {policies}</h1>
	</div>

	</div>
		

		
	<div class='row'><div id='progress-rdpcnx-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-rdpcnx-status'></div>

	</div>
	</div>
		
		
		
	<script>
	$.address.state('/');
	$.address.value('/rdpproxy-connections');
	LoadAjax('table-rdpcnx-status','$page?tabs=yes');
		
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {connections}",$html);
        echo $tpl->build_firewall();
        return;
    }



    echo $tpl->_ENGINE_parse_body($html);

}

function groups_start(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-groups-table' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');</script>";
}

function members_start(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-members-table' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-members-table','$page?members-table=yes');</script>";
}
function targets_start(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-target-table' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-target-table','$page?targets-table=yes');</script>";
}
function group_members_start(){
    $page=CurrentPageName();
    $ID=intval($_GET["groups-members-start"]);
    echo "<div id='rdpproxy-members-table-$ID' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-members-table-$ID','$page?group-members-table=$ID');</script>";
}
function group_targets_start(){
    $page=CurrentPageName();
    $ID=intval($_GET["groups-targets-start"]);
    echo "<div id='rdpproxy-targets-table-$ID' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-targets-table-$ID','$page?groups-targets-table=$ID');</script>";
}
function group_network_start(){
    $page=CurrentPageName();
    $ID=intval($_GET["groups-network-start"]);
    echo "<div id='rdpproxy-network-table-$ID' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-network-table-$ID','$page?groups-network-table=$ID');</script>";
}
function group_time_start(){
    $page=CurrentPageName();
    $ID=intval($_GET["group-time-start"]);
    echo "<div id='rdpproxy-time-table-$ID' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-time-table-$ID','$page?group-time-table=$ID');</script>";
}

function members_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    $btdisabled=false;
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($EnableActiveDirectoryFeature==0){$btdisabled=true;}
    if(!$CORP_LICENSE){$btdisabled=true;}

    VERBOSE("EnableActiveDirectoryFeature = $EnableActiveDirectoryFeature, CORP_LIC= $CORP_LICENSE");

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]=$tpl->button_label_table("{new_member}", "Loadjs('$page?member-js=0')", "far fa-user-plus","AllowAddUsers");
    $html[]=$tpl->button_label_table("{active_directory_group}", "Loadjs('$page?member-ad-js=0')", "far fa-user-plus","AllowAddUsers",$btdisabled);

    $html[]="</div>";
    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{end_of_life}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{policies}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");

    $results=$q->QUERY_SQL("SELECT * FROM members ORDER BY username");

//	print_r($hash_full);
    $TRCLASS=null;
   foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $username=$ligne["username"];
        $js="Loadjs('$page?member-js=$ID')";
        $status=null;
        $icon="fa fa-user";

        $gps=array();
        $results2=$q->QUERY_SQL("SELECT * FROM link_members WHERE userid='$ID'");
        foreach ($results2 as $index2=>$ligne2){
            $gpid=$ligne2["gpid"];
            $ligne3=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID=$gpid");
            if(trim($ligne3["groupname"])==null){continue;}
            $gps[]=$tpl->td_href($ligne3["groupname"],null,"Loadjs('fw.rdpproxy.connections.php?group-js=$gpid')");
        }


        $endoflife=$ligne["endoflife"];
       $finish=distanceOfTimeInWords(time(),$endoflife);

        if(time()>$endoflife){
            $finish=distanceOfTimeInWords($endoflife,time());
            $status="&nbsp;<span class='label label-danger'>{expired}</span>";
        }
        if($endoflife==0){
            $finish=$tpl->icon_nothing();
            $status=null;
        }

       $ADGROUP=intval($ligne["ADGROUP"]);
       if($ADGROUP==1){
           if(preg_match("#CN=(.+?),#i",$username,$re)){
               $username=$re[1];
               $icon="fa fa-users";
           }
       }


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='$icon'></i>&nbsp;". $tpl->td_href($username,"{click_to_edit}",$js)."</td>";

       $html[]="<td class=\"\" width='1%' nowrap><i class='fas fa-alarm-clock'></i>&nbsp;". $tpl->td_href($finish,"{click_to_edit}",$js)."$status</td>";

        $html[]="<td class=\"\">".@implode(", ",$gps)."</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->icon_delete("Loadjs('$page?member-delete=$ID&md=$md')","AllowAddUsers") ."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
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

function group_network_delete(){
    $ID=$_GET["network-unlink"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("DELETE FROM networks WHERE ID=$ID");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";

}

function group_time_table(){
    $gpid=intval($_GET["group-time-table"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=md5(time().$gpid.rand(0,65595));
//<i class="fas fa-network-wired"></i>
    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{add_working_period}", "Loadjs('$page?time-link=$gpid')", "fas fa-clock","AllowAddUsers")."

			</div>");

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{working_period}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM timeline WHERE gpid='$gpid'");
    $TRCLASS=null;
    if(count($results)==0){
        $md=time();
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fas fa-clock'></i>&nbsp;{every_day_every_time}</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->icon_nothing() ."</td>";
        $html[]="</tr>";
    }

    $array_day_of_week=array(
        "6"=>"{Sunday}",
        "0"=>"{Monday}",
        "1"=>"{Tuesday}",
        "2"=>"{Wednesday}",
        "3"=>"{Thursday}",
        "4"=>"{Friday}",
        "5"=>"{Saturday}");



    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $targetid=$ligne["targetid"];
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $tday=$ligne["tday"];
        $thour1=$ligne["thour1"];
        $thour2=$ligne["thour2"];

        $pattern=$array_day_of_week[$tday]." $thour1 - $thour2";

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fas fa-clock'></i>&nbsp;$pattern</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->icon_delete("Loadjs('$page?time-unlink=$ID&md=$md')","AllowAddUsers") ."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}

function group_network_table(){
    $gpid=intval($_GET["groups-network-table"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=md5(time().$gpid.rand(0,65595));
//<i class="fas fa-network-wired"></i>
    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{add_network}", "Loadjs('$page?network-link=$gpid')", "fas fa-network-wired","AllowAddUsers")."

			</div>");

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{networks}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

$TRCLASS=null;
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM networks WHERE gpid='$gpid'");

    if(count($results)==0){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='000'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fas fa-network-wired'></i>&nbsp;{refused_from_everyone}</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->icon_nothing() ."</td>";
        $html[]="</tr>";

    }

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $targetid=$ligne["targetid"];
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $pattern=$ligne["pattern"];

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fas fa-network-wired'></i>&nbsp;$pattern</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->icon_delete("Loadjs('$page?network-unlink=$ID&md=$md')","AllowAddUsers") ."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}

function group_targets_table(){
    $gpid=intval($_GET["groups-targets-table"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=md5(time().$gpid.rand(0,65595));


    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{link_target}", "Loadjs('$page?target-link=$gpid')", "fas fa-server","AllowAddUsers")."

			</div>");

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{targets}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM link_target WHERE gpid='$gpid'");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $targetid=$ligne["targetid"];
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $ligne2=$q->mysqli_fetch_array("SELECT * FROM targets WHERE ID=$targetid");
        $alias=$ligne2["alias"];
        $designation=$ligne2["designation"];
        $target_host=$ligne2["target_host"];
        $target_device=$ligne2["target_device"];
        $target_port=$ligne2["target_port"];
        $icon_user="<i class='fas fa-server'></i>";
        if(preg_match("#CN=(.*?),#i",$alias,$re)){
            $alias="<strong>{$re[1]}</strong>";
            $icon_user="<i class='fa fa-users'></i>";
            $target_host=$alias;
        }


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap>$icon_user&nbsp;$alias<br><small>$designation</small></td>";
        $html[]="<td class=\"\" width='100%' nowrap>$target_host:$target_port ($target_device)</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->icon_unlink("Loadjs('$page?target-unlink=$ID&md=$md')","AllowAddUsers") ."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}
function target_status(){
    $tpl=new template_admin();
    $ID=intval($_GET["target-status"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM targets WHERE ID=$ID");
    echo $tpl->_ENGINE_parse_body(group_target_status($ligne));
}

function group_target_status($ligne){
    $target_host=$ligne["target_host"];
    $alias=$ligne["alias"];
    $target_device=$ligne["target_device"];
    $target_port=$ligne["target_port"];
    $enabled=intval($ligne["enabled"]);

    if($enabled==0){
        return "<span class='label label'>{disabled}</span>";
    }


    if(preg_match("#(CN|OU|DC)=#i",$alias)){
        return "<span class='label label-primary'>Active Directory</span>";
    }


    if($target_device<>null) {
        $fsock = @fsockopen($target_device, $target_port, $errno, $errstr, 2);
        if (!$fsock) {
            return "<span class='label label-danger'>{error} $errno</span>";
        }
        fclose($fsock);
        return "<span class='label label-primary'>{connected}</span>";
    }

    $fsock = @fsockopen($target_host, $target_port, $errno, $errstr, 2);
    if (!$fsock) {
        return "<span class='label label-danger'>{disconnected}</span>";
    }
    fclose($fsock);
    return "<span class='label label-primary'>{connected}</span>";

}

function group_members_table(){
    $gpid=intval($_GET["group-members-table"]);
    $tpl=new template_admin();
    if($gpid==0){echo $tpl->FATAL_ERROR_SHOW_128("Cannot load a rule 0!");return;}

    $page=CurrentPageName();
    $t=md5(time().$gpid.rand(0,65595));

    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{link_member}", "Loadjs('$page?member-link=$gpid')", "far fa-user-plus","AllowAddUsers")."

			</div>");

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM link_members WHERE gpid='$gpid'");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $userid=$ligne["userid"];
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $status=null;
        $ligne2=$q->mysqli_fetch_array("SELECT username,endoflife FROM members WHERE ID=$userid");
        $username=$ligne2["username"];
        $icon_user="<i class='fa fa-user'></i>";

        if($ligne2["endoflife"]>0) {
            if ($ligne2["endoflife"] < time()) {
                $status = "&nbsp;<span class='label label-danger'>{expired}</span>";
            }
        }

        if(preg_match("#CN=(.*?),#i",$username,$re)){
            $username=$re[1];
            $icon_user="<i class='fa fa-users'></i>";
        }
        
        $gps=array();
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap>$icon_user&nbsp;$username$status</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->icon_unlink("Loadjs('$page?member-unlink=$ID&md=$md')","AllowAddUsers") ."</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    

}
function group_enable(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["group-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");

    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM groups WHERE ID=$ID");
    if(intval($ligne["enabled"])==1){$enabled=0;}else{$enabled=1;}
    $q->QUERY_SQL("UPDATE groups SET enabled=$enabled WHERE ID=$ID");
    if(!$q->ok){
        echo $tpl->js_mysql_alert($q->mysql_error);
        return;
    }

    echo "setTimeout(\"LoadAjaxTiny('grp-status-$ID','$page?group-status=$ID')\",800)";


}

function group_delete(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $md=$_GET["md"];
    $ID=intval($_GET["group-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID=$ID");
    $tpl->js_confirm_delete($ligne["groupname"],"group-delete",$ID,"$('#$md').remove()");
}
function group_delete_perform(){
    $ID=intval($_POST["group-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $q->QUERY_SQL("DELETE FROM link_members WHERE gpid='$ID'");
    $q->QUERY_SQL("DELETE FROM link_target WHERE gpid='$ID'");
    $q->QUERY_SQL("DELETE FROM timeline WHERE gpid='$ID'");
    $q->QUERY_SQL("DELETE FROM networks WHERE gpid='$ID'");
    $q->QUERY_SQL("DELETE FROM groups WHERE ID='$ID'");


}

function groups_status($ID){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM groups WHERE ID=$ID");
    if($ligne["enabled"]==0){
        return $tpl->_ENGINE_parse_body("<span class='label label'>{disabled}</span>");
    }

    $ligne2=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM link_members WHERE gpid='$ID'");
    $CountOfUsers=$ligne2["tcount"];
    if($CountOfUsers==0){
        return $tpl->_ENGINE_parse_body("<span class='label label-warning'>{inactive}</span>");
    }

    $ligne2=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM link_target WHERE gpid='$ID'");
    $CountOfTargets=$ligne2["tcount"];
    if($CountOfTargets==0){
        return $tpl->_ENGINE_parse_body("<span class='label label-warning'>{inactive}</span>");
    }

    $ligne2=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM networks WHERE gpid='$ID'");
    $CountOfNets=$ligne2["tcount"];
    if($CountOfNets==0){
        return $tpl->_ENGINE_parse_body("<span class='label label-warning'>{inactive}</span>");
    }

    return $tpl->_ENGINE_parse_body("<span class='label label-primary'>{active2}</span>");
}

function groups_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $t=md5(time().rand(0,65595));
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:15px'>";
    if($PowerDNSEnableClusterSlave==0) {
        $html[] = $tpl->button_label_table("{new_policy}", "Loadjs('$page?group-js=0')", "fas fa-clipboard-list", "AllowAddUsers");

    }
    $html[] = $tpl->button_label_table("{reload}", "LoadAjax('rdpproxy-groups-table','$page?groups-table=yes');",
        "fas fa-redo");
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{policy}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>-</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{targets}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{time}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{network}</th>";
    if($PowerDNSEnableClusterSlave==0) {
        $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";
        $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";
    }

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    patch_table();
   // <i class="fas fa-server"></i> <i class="fas fa-alarm-clock"></i> <i class="fas fa-network-wired"></i>


    $results=$q->QUERY_SQL("SELECT * FROM groups ORDER BY groupname");
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }
    $CountOfRules=count($results);
    VERBOSE("SELECT * FROM groups ORDER BY groupname -- ".count($results),__LINE__);
    if($CountOfRules==0){
        $ligne2=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM link_members");
        $CountOfUsers=$ligne2["tcount"];
        VERBOSE("Count OF rules = $CountOfRules, Count of users = $CountOfUsers",__LINE__);
        if($CountOfUsers>0){
            echo $tpl->div_error("Corrupted database");
        }
    }


    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));
        $groupname=$ligne["groupname"];
        $description=$ligne["description"];
        $js="Loadjs('$page?group-js=$ID')";
        $networks=$ligne["networks"];
        $enabled=$ligne["enabled"];
        $user_rec=intval($ligne["user_rec"]);
        $user_rec_ico=null;
        $icon=groups_status($ID);

        $groupname=$tpl->td_href("$groupname","{click_to_edit}",$js)."<br><small>$description</small>";

        $ligne2=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM link_members WHERE gpid='$ID'");
        $CountOfUsers=$ligne2["tcount"];

        $ligne2=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM link_target WHERE gpid='$ID'");
        $CountOfTargets=$ligne2["tcount"];

        $ligne2=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM timeline WHERE gpid='$ID'");
        $CountOfTime=$ligne2["tcount"];

        $ligne2=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM networks WHERE gpid='$ID'");
        $CountOfNets=$ligne2["tcount"];

        $span="<span style='font-size:18px;font-weight:bolder'>";

        if($user_rec==1){
            $user_rec_ico="&nbsp;<span class='label label-warning'>{video_recording}</span>";
        }

        if($CountOfNets==0){
            $rule_error="&nbsp;<span class='text-danger'>{rulefailed_no_network}</span>";
        }
        $iconcheck=$tpl->icon_check($enabled, "Loadjs('$page?group-enable=$ID')", "AllowAddUsers");
        $icondelete=$tpl->icon_delete("Loadjs('$page?group-delete=$ID&md=$md')", "AllowAddUsers");
        $gps=array();
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"center\" width='1%' nowrap><span id='grp-status-$ID'>".groups_status($ID)."</span></td>";
        $html[]="<td><i class='fas fa-clipboard-list'></i>&nbsp;$groupname$rule_error</td>";
        $html[]="<td class=\"center\" width='1%' nowrap>$user_rec_ico</td>";
        $html[]="<td class=\"center\" width='1%' nowrap><i class='fas fa-users'></i>&nbsp;$span$CountOfUsers</span></td>";
        $html[]="<td class=\"center\" width='1%' nowrap><i class='fas fa-server'></i>&nbsp;$span$CountOfTargets</span></td>";
        $html[]="<td class=\"center\" width='1%' nowrap><i class='fas fa-alarm-clock'></i>&nbsp;$span$CountOfTime</span></td>";
        $html[]="<td class=\"center\" width='1%' nowrap><i class='fas fa-network-wired'></i>&nbsp;$span$CountOfNets</span></td>";
        if($PowerDNSEnableClusterSlave==0) {
            $html[] = "<td class=\"center\" width='1%' nowrap>$iconcheck</td>";
            $html[] = "<td class=\"center\" width='1%' nowrap>$icondelete</td>";
        }
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='9'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}

function targets_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    $btdisabled=false;
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($EnableActiveDirectoryFeature==0){$btdisabled=true;}
    if(!$CORP_LICENSE){$btdisabled=true;}
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]=$tpl->button_label_table("{new_target}", "Loadjs('$page?target-js=0')", "fas fa-server","AllowAddUsers");
   $html[]=$tpl->button_label_table("{active_directory_group}", "Loadjs('$page?target-ad-js=0')", "far fa-user-plus","AllowAddUsers",$btdisabled);


    $html[]="</div>";
    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{target}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>PROTO</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{username}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    patch_table();

    $results=$q->QUERY_SQL("SELECT * FROM targets ORDER BY alias");

//	print_r($hash_full);
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));
        $alias=$ligne["alias"];
        $designation=$ligne["designation"];
        $target_password=$ligne["target_password"];
        $target_login=$ligne["target_login"];
        $proto_dest=$ligne["proto_dest"];
        $target_host=$ligne["target_host"];
        $target_device=$ligne["target_device"];
        $target_port=$ligne["target_port"];
        $session_probe=$ligne["session_probe"];
        $mode_console=$ligne["mode_console"];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $username=$ligne["username"];
        $enabled=intval($ligne["enabled"]);
        $js="Loadjs('$page?target-js=$ID')";
        $ico_user="<i class='fa fa-user'></i>";

        $host_text=$tpl->td_href($alias,"{click_to_edit}",$js)."<br><small>$designation</small>";



        $status=group_target_status($ligne);

        if(preg_match("#(CN|DC|OU)=#i",$alias)){
            $ad=new external_ad_search();
            $arrayAD=$ad->DNDUMP($alias);
            $js="Loadjs('$page?target-ad-js=$ID')";
            $GroupName=$arrayAD["name"][0];
            $CMPS=array();
            if($arrayAD["member"]["count"]>0){
                for($i=0;$i<$arrayAD["member"]["count"];$i++){
                    $mdn=$arrayAD["member"][$i];
                    $marr=$ad->DNDUMP($mdn);
                    if(isset($marr["dnshostname"])){
                        $CMPS[]=$marr["dnshostname"][0];
                        continue;
                    }
                    $CMPS[]=str_replace("$","",$marr["samaccountname"][0]);
                }
            }
            $target_host=$GroupName;
            $host_text="<strong>".$tpl->td_href($GroupName,null,$js)."</strong> <small>(".@implode(", ",$CMPS).")</small>";
            $target_login=$tpl->icon_nothing();
            $ico_user=null;
        }



        $gps=array();
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='1%' nowrap><span id='target-status-$ID'>$status</span></td>";
        $html[]="<td><i class='fas fa-server'></i>&nbsp;$host_text</td>";
        $html[]="<td class=\"\" width='1%' nowrap>". $tpl->td_href("$target_host:$target_port","{click_to_edit}",$js)."</td>";
        $html[]="<td class=\"\" width='1%' nowrap>$proto_dest</td>";
        $html[]="<td class=\"\" width='1%' nowrap>$ico_user&nbsp;$target_login</td>";
        $html[]="<td class=\"\" width='1%' nowrap>". $tpl->icon_check($enabled,"Loadjs('$page?target-enable=$ID')","AllowAddUsers")."</td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->icon_delete("Loadjs('$page?target-delete=$ID&md=$md')","AllowAddUsers") ."</td>";
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