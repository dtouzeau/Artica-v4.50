<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["start-table"])){start_table();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}
js();

function js(){
    $users=new usersMenus();
    if(!$users->AsWebMaster){die();}
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["siteid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $tpl->js_dialog2("{$ligne["hostname"]}: {plugins}", "$page?start-table=$ID",650);
}
function start_table(){
    $page = CurrentPageName();
    $ID=intval($_GET["start-table"]);
    
    echo "<div id='wordpress-plugins-progress-$ID'></div>
          <div id='wordpress-plugins-$ID'></div>
          <script>LoadAjax('wordpress-plugins-$ID','$page?table=$ID')</script>";
}
function enable_js(){
    $plugin_name=$_GET["enable-js"];
    $ID=intval($_GET["siteid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $hostname=$ligne["hostname"];
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wordpress.php?plugins-list=$ID");
    $target=PROGRESS_DIR."/wp.plugins-list.$ID";
    $data=@file_get_contents($target);
    $json=json_decode($data);
    $statusz["inactive"]=0;
    $statusz["active"]=1;
    foreach ($json as $index=>$main) {
        $name = $main->name;
        $status = $statusz[$main->status];
        if($name==$plugin_name){
            if($status==0){
                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wordpress.php?plugins-enable=$ID&plugin=$name");
                admin_tracks("Wordpress: $hostname activate plugin $name");
                return true;
            }
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wordpress.php?plugins-disable=$ID&plugin=$name");
            admin_tracks("Wordpress: $hostname disable plugin $name");
            return true;
        }
    }
    return false;
}

function delete_js(){
    $ID=$_GET["siteid"];
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $hostname=$ligne["hostname"];
    $plugin=urlencode($_GET["delete-js"]);
    $tpl = new template_admin();

    $service_reconfigure=$tpl->framework_buildjs("wordpress.php?plugins-uninstall=$ID&plugin=$plugin",
        "wordpress.single-install.$ID",
        "wordpress.single-install.$ID.log",
        "wordpress-plugins-progress-$ID","LoadAjaxSilent('wordpress-plugins-$ID','$page?table=$ID')");

    $tpl->js_confirm_delete($plugin,"delete","$hostname plugin $plugin",$service_reconfigure);

}
function delete_perform(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks("Wordpress: uninstall plugin {$_POST["delete"]}");
}

function file_uploaded(){
    $tpl = new template_admin();
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];
    $siteid=$_GET["siteid"];
    $filename=base64_encode($file);
    $service_reconfigure=$tpl->framework_buildjs("wordpress.php?plugins-install=$siteid&filename=$filename",
        "wordpress.single-install.$siteid",
        "wordpress.single-install.$siteid.log",
        "wordpress-plugins-progress-$siteid","LoadAjaxSilent('wordpress-plugins-$siteid','$page?table=$siteid')");
    echo $service_reconfigure;
}

function table(){
    $users=new usersMenus();
    if(!$users->AsWebMaster){die();}
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["table"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wordpress.php?plugins-list=$ID");
    $target=PROGRESS_DIR."/wp.plugins-list.$ID";

    $data=@file_get_contents($target);
    $t=time();
    $html[]=$tpl->button_upload("{upload_package}",$page,null,"&siteid=$ID");
    $html[] = "<table id='table-$t-main' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:15px'>";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th></th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{plugin}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{version}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{status}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>DEL</th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";
    $json=json_decode($data);
    $TRCLASS=null;

    $statusz["inactive"]=0;
    $statusz["active"]=1;
    foreach ($json as $index=>$main){
        if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
        $name=$main->name;
        $status=$statusz[$main->status];
        $update=$main->update;
        $version=$main->version;
        $md=md5(serialize($main));

        $enable=$tpl->icon_check($status,"Loadjs('$page?enable-js=$name&siteid=$ID')");
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$name&siteid=$ID')");

        //--deactivate

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap class='center'><i class='fa-solid fa-puzzle-piece-simple'></i></td>";
        $html[]="<td style='width:99%' nowrap><span class='font-bold'>$name</span></td>";
        $html[]="<td style='width:1%' nowrap class='center'>$version</td>";
        $html[]="<td style='width:1%'>$enable</td>";
        $html[]="<td style='width:1%'>$delete</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
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
