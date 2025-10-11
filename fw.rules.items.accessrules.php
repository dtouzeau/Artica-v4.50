<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}

if(isset($_GET["page"])){page();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["enabled-js"])){enable_switch();exit;}
js();

function js(){
    $function2="";
    if(isset($_GET["function2"])) {
        $function2 = $_GET["function2"];
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["gpid"]);
    if($gpid==0){
        return $tpl->js_error("gpid == 0 ???");
    }
    $tpl->js_dialog10("{items}","$page?page=$gpid&function2=$function2");

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["gpid"]);
    $function2="";
    if(isset($_GET["function2"])) {
        $function2 = $_GET["function2"];
    }
    if($gpid==0){
        echo $tpl->div_error("gpid == 0 ???");
        return false;
    }

    echo $tpl->search_block($page,null,null,null,"&gpid=$gpid&function2=$function2");
    return true;
}
function enable_switch():bool{
    $ruleid=intval($_GET["enabled-js"]);
    $gpid=intval($_GET["gpid"]);
    $tpl=new template_admin();
    if($gpid==0){
        return $tpl->js_error("gpid == 0 ???");
    }

    $function2="";
    if(isset($_GET["function2"])) {
        $function2 = $_GET["function2"];
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID,description FROM webfilters_sqitems WHERE pattern='$ruleid' AND gpid=$gpid");
    $description=$ligne["description"];
    $ID=intval($ligne["ID"]);
    if($ID==0){
        $uid=$_SESSION["uid"];
        $tpl=new template_admin();
        if($uid==-100){$uid="Manager";}
        $date=date("Y-m-d H:i:s");
        $desc="by $uid on $date";
        if(strlen($description)>2){
            $desc=$description;
        }
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems(pattern,gpid,enabled,description) VALUES('$ruleid',$gpid,1,'$desc')");
        if(!$q->ok){
            echo $tpl->js_error($q->mysql_error);
            return false;
        }
        header("content-type: application/x-javascript");
        if(strlen($function2)>3){
            echo "$function2()\n";
        }
        return admin_tracks("Add access rule TAG $ruleid to group $gpid");
    }

    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE ID=$ID");
    header("content-type: application/x-javascript");
    if(strlen($function2)>3){
        echo "$function2()\n";
    }
    return admin_tracks("Remove access rule TAG $ruleid to group $gpid");
}

function search(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $function=$_GET["function"];
    $function2="";
    if(isset($_GET["function2"])) {
        $function2 = $_GET["function2"];
    }
    $page=CurrentPageName();
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));

    if($Go_Shield_Server_Enable==0){
        echo $tpl->div_error("{ERROR_GO_SHIELD_SERVER_DISABLED}");
        return false;
    }
    $gpid=intval($_GET["gpid"]);
    $ALREADY=array();
    $results=$q->QUERY_SQL("SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid");
    foreach ($results as $index=>$ligne){
        $pattern=intval($ligne["pattern"]);
        $ALREADY[$pattern]=true;
    }

    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{rules}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $search=trim($_GET["search"]);
    if(strlen($search)>1) {
        if(preg_match("#(rows|max|limit)=([0-9]+)#i",$search,$re)){
            $limit=intval($re[2]);
            $search=str_replace("$re[1]=$re[2]","",$search);
        }
        $search = "*$search*";
        $search = str_replace("**", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*", "%", $search);
    }
    if($limit==0){$limit=30;}
    $sql = "SELECT * FROM webfilters_simpleacls WHERE ruleaction=2 ORDER BY xORDER LIMIT $limit";
    if(strlen($search)>0) {
        $sql = "SELECT * FROM webfilters_simpleacls WHERE ruleaction=2 AND aclname LIKE '$search' ORDER BY xORDER LIMIT $limit";
    }

    writelogs("sql=$sql",__FUNCTION__,__FILE__,__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return true;
    }
    $TRCLASS="";

    foreach ($results as $index=>$ligne){

        $ID=$ligne["ID"];
        $enabled=0;
        if(isset($ALREADY[$ID])){$enabled=1;}
        $aclname=$ligne["aclname"];
        if(strlen($aclname)==0){$aclname="{unknown}";}

        $enableico=$tpl->icon_check($enabled,"Loadjs('$page?enabled-js=$ID&gpid=$gpid&function2=$function2')");;

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]= "<tr class='$TRCLASS'>";
        $html[]= "<td><strong>$aclname</strong></td>";
        $html[]= "<td style='width:1%'>$enableico</td>";
        $html[]= "</tr>";
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
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}