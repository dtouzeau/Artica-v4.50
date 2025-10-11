<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["ID"])){save();exit;}
if(isset($_GET["autofill"])){autofill();exit;}
page();


function page(){
    $page=CurrentPageName();
    $ID=intval($_GET["ID"]);
    if($ID==0){die();}
    echo "<div id='wordpress-secure-wp-admin-$ID'></div><script>LoadAjax('wordpress-secure-wp-admin-$ID','$page?table=$ID');</script>";
}

function delete(){

    $ID=intval($_GET['delete']);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $q->QUERY_SQL("DELETE FROM wp_admin_ip WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return;}
    header("content-type: application/x-javascript");
    echo "$('#{$_GET["md"]}').remove();";

}

function enable(){
    $tpl=new template_admin();
    $ID=intval($_GET['enable']);
    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM wp_admin_ip WHERE ID=$ID");
    echo "// $ID == {$ligne["enabled"]}\n";
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    if(intval($ligne["enabled"])==0){
        $ena=1;
    }else{$ena=0;}
    echo "// UPDATE wp_admin_ip SET enabled='{$ena}' WHERE ID=$ID\n";
    $q->QUERY_SQL("UPDATE wp_admin_ip SET enabled='{$ena}' WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

}

function js(){
    $page=CurrentPageName();
    $ID=intval($_GET["js"]);
    $wpid=intval($_GET["wpid"]);
    $tpl=new template_admin();


    if($wpid==0){$tpl->js_error("No wpid!");return;}

    if($ID==0){$title="{new_address}";}else{
        $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
        $ligne=$q->mysqli_fetch_array("SELECT address FROM wp_admin_ip WHERE ID=$ID");
        $title=$ligne["address"];
    }
    $tpl->js_dialog1($title,"$page?popup=$ID&wpid=$wpid");


}

function autofill(){
    $ID=intval($_GET["autofill"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_admin_ip WHERE ID=$ID");
    $description=$tpl->javascript_parse_text($ligne["description"]);
    header("content-type: application/x-javascript");
    echo "document.getElementById('wpadmin1-$ID').innerHTML='$description';\n";
    echo "document.getElementById('wpadmin0-$ID').innerHTML='{$ligne["address"]}';\n";

}

function popup(){
    $page=CurrentPageName();
    $ID=intval($_GET["popup"]);
    $wpid=intval($_GET["wpid"]);
    $tpl=new template_admin();
    if($ID==0){
        $title="{new_address}";
        $ligne["address"]=GET_REMOTE_ADDR();
        $btname="{create}";
        $jsafter="dialogInstance1.close();LoadAjax('wordpress-secure-wp-admin-$wpid','$page?table=$wpid');";
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_admin_ip WHERE ID=$ID");
        $title=$ligne["address"];
        $btname="{apply}";
        $jsafter="Loadjs('$page?autofill=$ID');";
    }

    $tpl->field_hidden("ID",$ID);
    $tpl->field_hidden("wpid",$wpid);
    $form[]=$tpl->field_text("address","{address}",$ligne["address"],true);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    echo $tpl->form_outside($title,$form,"{ngx_stream_access_module}","$btname",$jsafter,"AsSystemWebMaster",false);



}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $wpid=intval($_POST["wpid"]);
    if($wpid==0){echo "jserror:No WPID !";return;}
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $description=$q->sqlite_escape_string2($_POST["description"]);
    $IPClass=new IP();

    if(!$IPClass->isIPAddressOrRange($_POST['address'])){
        echo "jserror:Please specify an IP address or netmask";
        return null;
    }
    if($ID==0){
        $q->QUERY_SQL("INSERT INTO wp_admin_ip (address,description,wpid) VALUES('{$_POST['address']}','$description','$wpid')");
        if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";return;}
        return;
    }

    $q->QUERY_SQL("UPDATE wp_admin_ip SET address='{$_POST['address']}',description='$description' WHERE ID=$ID");
    if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";return;}
}


function table(){
    $wpid=intval($_GET["table"]);
    $t=time();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $page=CurrentPageName();
    $tpl=new template_admin();

    $sql="CREATE TABLE IF NOT EXISTS `wp_admin_ip` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`address` text,
	`description` text,
	`enabled` INTEGER NOT NULL DEFAULT 1,
	`wpid` INTEGER )";
    $q->QUERY_SQL($sql);


    $service_reconfigure=$tpl->framework_buildjs(
        "wordpress.php?build-single=$wpid",
        "wordpress.$wpid.progress",
        "wordpress.$wpid.progress.txt",
        "mainid=reconfigure$wpid",
        ""
    );


    $html[] = "<div id='reconfigure$wpid' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[] = "<div class=\"btn-group\" data-toggle=\"buttons\" >";
    $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?js=0&wpid=$wpid');\"><i class='fa fa-plus'></i> {new_address} </label>";
    $html[] = "<label class=\"btn btn btn-info\" OnClick=\"$service_reconfigure\"><i class='fa fa-save'></i> {apply_configuration} </label>";
    $html[] = "</div>";
    $html[] = "<table id='table-$t-main' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{enabled}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{address}</th>";
    $html[] = "<th data-sortable=false></th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    $results=$q->QUERY_SQL("SELECT * FROM wp_admin_ip WHERE wpid=$wpid ORDER BY address");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $ID = $ligne["ID"];
        $address = $tpl->td_href($ligne["address"],null,"Loadjs('$page?js=$ID&wpid=$wpid')");
        $description = $ligne["description"];
        $md = md5(serialize($ligne));
        $enabled = intval($ligne['enabled']);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'>". $tpl->icon_check($enabled,"Loadjs('$page?enable=$ID')",null,"AsSystemWebMaster")."</td>";
        $html[]="<td><strong id='wpadmin0-$ID'>$address</strong>&nbsp;<small id='wpadmin1-$ID'>$description</small></td>";
        $html[]="<td style='width:1%'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md=$md')","AsSystemWebMaster")."</td>";
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
	$(document).ready(function() { $('#table-$t-main').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);


}
function GET_REMOTE_ADDR(){
    if(isset($_SERVER["REMOTE_ADDR"])){
        $IPADDR=$_SERVER["REMOTE_ADDR"];
        if($GLOBALS["VERBOSE"]){echo "REMOTE_ADDR = $IPADDR<br>\n";}
    }
    if(isset($_SERVER["HTTP_X_REAL_IP"])){
        $IPADDR=$_SERVER["HTTP_X_REAL_IP"];
        if($GLOBALS["VERBOSE"]){echo "HTTP_X_REAL_IP = $IPADDR<br>\n";}
    }
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
        $IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];
        if($GLOBALS["VERBOSE"]){echo "HTTP_X_FORWARDED_FOR = $IPADDR<br>\n";}
    }
    $GLOBALS["HTTP_USER_AGENT"]=$_SERVER["HTTP_USER_AGENT"];
    if($GLOBALS["VERBOSE"]){echo "HTTP_USER_AGENT = {$GLOBALS["HTTP_USER_AGENT"]}<br>\n";}

    if($GLOBALS["VERBOSE"]){
        while (list ($num, $Linz) = each ($_SERVER) ){
            if(is_array($Linz)){
                while (list ($a, $b) = each ($Linz) ){
                    echo "<li style='font-size:10px'>\$_SERVER[\"$num\"][\"$a\"]=\"$b\"</li>\n";
                }
                continue;
            }
            echo "<li style='font-size:10px'>\$_SERVER[\"$num\"]=\"$Linz\"</li>\n";
        }

    }


    return $IPADDR;
}