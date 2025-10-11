<?php
$GLOBALS["VERBOSE"]=false;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
if($GLOBALS["VERBOSE"]){echo "<H1>Includes...</H1>\n";}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["rowof"])){build_row_of();exit;}
if(isset($_GET["ch-hostname"])){ch_hostname_js();exit;}
if(isset($_GET["ch-hostname-popup"])){ch_hostname_popup();exit;}
if(isset($_POST["ch-hostname"])){ch_hostname_save();exit;}
if(isset($_GET["replace-js"])){replace_js();exit;}

if(isset($_POST["ch-admin"])){ch_admin_save();exit;}
if(isset($_GET["ch-admin"])){ch_admin_js();exit;}
if(isset($_GET["ch-admin-popup"])){ch_admin_popup();exit;}
if(isset($_GET["letsencrypt-wizard"])){letsencrypt_wizard();exit;}
if(isset($_POST["WordPressListenInterface"])){save_params();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["edit-js"])){edit_js();exit;}
if(isset($_GET["edit-tabs"])){edit_tabs();exit;}
if(isset($_GET["new-www-js"])){new_website_js();exit;}
if(isset($_GET["new-www-popup"])){new_website_popup();exit;}
if(isset($_GET["edit-parameters"])){edit_parameters();exit;}
if(isset($_GET["edit-parameters2"])){edit_parameters2();exit;}
if(isset($_GET["edit-ssl"])){edit_ssl();exit;}
if(isset($_GET["edit-ssl2"])){edit_ssl2();exit;}
if(isset($_POST["new"])){new_website_save();exit;}
if(isset($_POST["edit"])){edit_parameters_save();exit;}

if(isset($_GET["core-status"])){core_status();exit;}
if(isset($_GET["enable"])){enable_switch();exit;}
if(isset($_GET["readonly"])){readonly_switch();exit;}
if(isset($_GET["backup-delete"])){backup_delete_js();exit;}
if(isset($_POST["backup-delete"])){backup_delete_perform();exit;}


if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["delete-hole"])){exit;}
if(isset($_GET["delete-progress"])){delete_progress_js();exit;}
if(isset($_GET["delete-popup"])){delete_progress_popup();exit;}
if(isset($_GET["edit-backups"])){backup_popup();exit;}
if(isset($_GET["backups-table"])){backup_table();exit;}
if(isset($_GET["params"])){global_parameters();exit;}
if(isset($_GET["download-backup"])){backup_download();}
if(isset($_POST["edit-ssl"])){edit_ssl_save();exit;}
if(isset($_GET["edit-wp"])){edit_wp();exit;}
if(isset($_GET["cache-settings-js"])){cache_settings_js();exit;}
if(isset($_GET["cache-settings-popup"])){cache_settings_popup();exit;}
if(isset($_POST["cacheid"])){cache_settings_save();exit;}
if(isset($_GET["import-backup-js"])){backup_import_js();exit;}
if(isset($_GET["backup-import-popup"])){backup_import_popup();exit;}
if(isset($_GET["file-uploaded"])){backup_import_file_uploaded();exit;}
if(isset($_GET["restore-backup-js"])){backup_restore_js();exit;}
if(isset($_POST["restore-backup"])){backup_restore_confirm();exit;}
if(isset($_GET["replace-popup"])){replace_popup();exit;}
if(isset($_POST["replace_site_id"])){replace_save();exit;}
if($GLOBALS["VERBOSE"]){echo "<H1>Includes >END...</H1>\n";}

page();


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{websites}"]="$page?table=yes";
    $array["{global_parameters}"]="$page?params=yes";
    echo $tpl->tabs_default($array);
}
function backup_restore_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["restore-backup-js"]);
    $siteid=intval($_GET["siteid"]);
    $progressdiv="backup-progress-$siteid";
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT filename,fullpath,filesize FROM wp_backup WHERE ID=$ID");
    $filename=$ligne["filename"];
    $restore_backup_ask=$tpl->_ENGINE_parse_body("{restore_backup_ask}");
    $restore_backup_ask=str_replace("%s",$filename." ($ID)",$restore_backup_ask);
    $wp_hostname_from_id=wp_hostname_from_id($siteid);
    $restore_backup_ask=str_replace("%y",$wp_hostname_from_id,$restore_backup_ask);
    $jsrestart=$tpl->framework_buildjs(
        "wordpress.php?restore-backup=$ID&siteid=$siteid",
        "wordpres.$siteid.restore.backup.progress",
        "wordpres.$siteid.restore.backup.log",
        "$progressdiv",
        "LoadAjax('wordpress-backups-$siteid','$page?backups-table=$siteid');"
    );
    $data=base64_encode(serialize(array($ID,$siteid)));
    $tpl->js_confirm_execute($restore_backup_ask,"restore-backup",$data,$jsrestart);

}
function replace_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $siteid=intval($_GET["siteid"]);
    $tpl->js_dialog6("modal:{content_replacement}", "$page?replace-popup=$siteid","600");
}
function replace_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $siteid=intval($_GET["replace-popup"]);
    $tpl->field_hidden("replace_site_id",$siteid);
    $form[]=$tpl->field_text("search","{searchthestring}",null);
    $form[]=$tpl->field_text("replace","{replaceby}",null);

    $restart=$tpl->framework_buildjs(
        "wordpress.php?site-replace=$siteid",
        "wordpress.$siteid.replace.progress",
        "wordpress.$siteid.replace.log",
        "wp-replace-$siteid"
    );
    $html[]="<div id='wp-replace-$siteid'></div>";
    $html[]=$tpl->form_outside("{content_replacement}",$form,"{wordpress_replace_string}","{run}",$restart);
    //wordpress_rereplacebyplace_string
    echo $tpl->_ENGINE_parse_body($html);
}
function replace_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tmp=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WPSITES_REPLACESTRS");
    $MAIN=unserialize(base64_decode($tmp));
    $MAIN[$_POST["replace_site_id"]]=$_POST;
    $tpm=base64_encode(serialize($MAIN));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WPSITES_REPLACESTRS",$tpm);
    $sitename=wp_hostname_from_id($_POST["replace_site_id"]);
    admin_tracks_post("Change string in $sitename content");

}



function backup_restore_confirm(){
    $data=unserialize(base64_decode($_POST["restore-backup"]));
    $ID=intval($data[0]);
    $siteid=intval($data[1]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT filename,fullpath,filesize FROM wp_backup WHERE ID=$ID");
    $filename=$ligne["filename"];
    $wp_hostname_from_id=wp_hostname_from_id($siteid);
    admin_tracks("Restore Wordpress $filename backup to $wp_hostname_from_id");
}
function backup_import_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $siteid=intval($_GET["import-backup-js"]);
    $tpl->js_dialog6("modal:{restore_a_backup}", "$page?backup-import-popup=$siteid","600");
}
function backup_import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $siteid=intval($_GET["backup-import-popup"]);
    $bt_upload=$tpl->button_upload("{upload_backup} *.gz",$page,null,"&siteid=$siteid")."&nbsp;&nbsp;";
    $html="<div id='ca-form-import'><div class='center'>$bt_upload</div></div>
	<div id='progress-backup-wordpress-import-$siteid'></div>";
    echo $tpl->_ENGINE_parse_body($html);

}
function wp_hostname_from_id($ID){
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    return $ligne["hostname"];
}
function backup_import_file_uploaded(){
    $tpl = new template_admin();
    $page=CurrentPageName();
    $file = $_GET["file-uploaded"];
    $siteid=intval($_GET["siteid"]);
    $fileencoded=base64_encode($file);
    $progressdiv="progress-backup-wordpress-import-$siteid";
    header("content-type: application/x-javascript");
    $jsrestart=$tpl->framework_buildjs(
        "wordpress.php?import-backup=$fileencoded&siteid=$siteid",
        "wordpres.$siteid.restore.backup.progress",
        "wordpres.$siteid.restore.backup.log",
        "$progressdiv",
        "dialogInstance6.close();LoadAjax('wordpress-backups-$siteid','$page?backups-table=$siteid');"
    );
    $host=wp_hostname_from_id($siteid);
    admin_tracks("Importing new backup $file for $host");
    echo $jsrestart;
}

function global_parameters(){
    $tpl = new template_admin();
    $WordPressMaxDaysBackup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordPressMaxDaysBackup"));
    $WordPressListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordPressListenInterface"));

    if($WordPressMaxDaysBackup==0){$WordPressMaxDaysBackup=7;}
    $WordPressDisabledSitePage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordPressDisabledSitePage"));

    $form[]=$tpl->field_interfaces("WordPressListenInterface", "nooloopNoDef:{listen_interface}", $WordPressListenInterface);

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $WordPressMaxDaysBackup=2;
        $WordPressDisabledSitePage=null;
    }

    $form[]=$tpl->field_section("{backup}");
    $form[]=$tpl->field_numeric("WordPressMaxDaysBackup","{retention_days}",$WordPressMaxDaysBackup);


    if(strlen($WordPressDisabledSitePage)<10){
        $WordPressDisabledSitePage="PCFET0NUWVBFIGh0bWw+CjxodG1sPgo8aGVhZD4KICAgIDxtZXRhIGNoYXJzZXQ9InV0Zi04Ij4KICAgIDxtZXRhIG5hbWU9InZpZXdwb3J0IiBjb250ZW50PSJ3aWR0aD1kZXZpY2Utd2lkdGgsIGluaXRpYWwtc2NhbGU9MS4wIj4KICAgIDx0aXRsZT40MDQgRG9tYWluIGRpc2FibGVkPC90aXRsZT4KICAgIDxsaW5rIGhyZWY9ImNzcy9ib290c3RyYXAubWluLmNzcyIgcmVsPSJzdHlsZXNoZWV0Ij4KICAgICA8bGluayBocmVmPSJjc3MvYW5pbWF0ZS5jc3MiIHJlbD0ic3R5bGVzaGVldCI+CiAgICA8bGluayBocmVmPSJjc3Mvc3R5bGUuY3NzIiByZWw9InN0eWxlc2hlZXQiPgo8L2hlYWQ+Cjxib2R5IGNsYXNzPSJncmF5LWJnIj4KIDxkaXYgY2xhc3M9Im1pZGRsZS1ib3ggdGV4dC1jZW50ZXIgYW5pbWF0ZWQgZmFkZUluRG93biI+CiAgICAgICAgPGgxPjUwMDwvaDE+CiAgICAgICAgPGgzIGNsYXNzPSJmb250LWJvbGQiPkludGVybmFsIFNlcnZlciBFcnJvcjwvaDM+CgogICAgICAgIDxkaXYgY2xhc3M9ImVycm9yLWRlc2MiPgogICAgICAgICAgICBUaGUgc2VydmVyIGlzIGN1cnJlbnRseSBkaXNhYmxlZCwgc29tZXRoaW5nIHVuZXhwZWN0ZWQgdGhhdCBkaWRuJ3QgYWxsb3cgaXQgdG8gY29tcGxldGUgdGhlIHJlcXVlc3QuIFdlIGFwb2xvZ2l6ZS48YnIvPgogICAgICAgICAgICAKICAgICAgICA8L2Rpdj4KICAgIDwvZGl2PgoKICAgIDwhLS0gTWFpbmx5IHNjcmlwdHMgLS0+CiAgICA8c2NyaXB0IHNyYz0ianMvanF1ZXJ5LTMuMS4xLm1pbi5qcyI+PC9zY3JpcHQ+CiAgICA8c2NyaXB0IHNyYz0ianMvYm9vdHN0cmFwLm1pbi5qcyI+PC9zY3JpcHQ+CjwvYm9keT4KPC9odG1sPgoKCgo=";}


    $form[]=$tpl->field_section("{page_for_disabled_sites}","{page_for_disabled_sites_explain}");

    $form[]=$tpl->field_textareacode("WordPressDisabledSitePage","{content}",base64_decode($WordPressDisabledSitePage));



    echo $tpl->form_outside("{global_parameters}",$form,null,"{apply}",null,"AsSystemWebMaster",true);
}

function save_params(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $_POST["WordPressDisabledSitePage"]=base64_encode($_POST["WordPressDisabledSitePage"]);
    $tpl->SAVE_POSTs();

}

function edit_wp(){

    $disabled=false;
    $ID=intval($_GET["edit-wp"]);
    $qlite=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$qlite->mysqli_fetch_array("SELECT hostname,database_name FROM wp_sites WHERE ID=$ID");
}


function validate_url( $url ):bool{
    $url = trim( $url );
    $array=parse_url($url);
    if(!isset($array["scheme"])){return false;}
    if(!isset($array["host"])){return false;}
    if(isset($array["query"])){return false;}
    return true;
}

function backup_download(){

    $ID=intval($_GET["download-backup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT filename,fullpath,filesize FROM wp_backup WHERE ID=$ID");
    $filename=$ligne["filename"];
    $fullpath=$ligne["fullpath"];
    $filesize=intval($ligne["filesize"]);
    header('Content-type: application/x-tgz');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: $filesize");
    ob_clean();
    flush();
    readfile($fullpath);
}


function new_website_js(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->js_dialog2("modal:{new_wordpress_site}", "$page?new-www-popup=yes");
}
function ch_admin_js(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["ch-admin"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $tpl->js_dialog2("{$ligne["hostname"]}: {administrator}", "$page?ch-admin-popup=$ID",550);
}
function ch_hostname_js(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["ch-hostname"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $tpl->js_dialog2("{$ligne["hostname"]}: {sitename}", "$page?ch-hostname-popup=$ID",850);
}

function ch_admin_popup(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["ch-admin-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $tpl->field_hidden("ch-admin","$ID");
    $form[]=$tpl->field_text("admin_user","{administrator}",$ligne["admin_user"],true,"");
    $form[]=$tpl->field_text("admin_email","{admin_email}",$ligne["admin_email"],true,"");
    $form[]=$tpl->field_password2("admin_password","{password}",$ligne["admin_password"],true,"");


    $service_reconfigure=$tpl->framework_buildjs("wordpress.php?ch-admin=$ID",
        "wordpress.$ID.progress","wordpress.$ID.progress.txt","progress-websites-restart","Loadjs('$page?rowof=$ID')","Loadjs('$page?rowof=$ID')");


    echo $tpl->form_outside("{$ligne["hostname"]}: {administrator}",$form,null,"{apply}",
        "dialogInstance2.close();$service_reconfigure","AsSystemWebMaster");

}

function ch_admin_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ch-admin"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");

    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $hostname=$ligne["hostname"];
    if($_POST["admin_password"]==$ligne["admin_password"]){
        if($_POST["admin_user"]==$ligne["admin_user"]){
            if($_POST["admin_email"]==$ligne["admin_email"]){
                echo $tpl->_ENGINE_parse_body("{no_change}");
                exit;
            }
        }

    }
    $_POST["admin_password"]=$q->sqlite_escape_string2( $_POST["admin_password"]);
    $sql="UPDATE wp_sites SET 
    admin_user='{$_POST["admin_user"]}',
    admin_password='{$_POST["admin_password"]}',
    admin_email='{$_POST["admin_email"]}' WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);
        return false;
    }
    admin_tracks("Wordpress: $hostname Change admin {$_POST["admin_user"]}/{$_POST["admin_email"]} username and password");
    return true;
}

function edit_js(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["edit-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $tpl->js_dialog("{$ligne["hostname"]}", "$page?edit-tabs=$ID");
}
function delete_progress_js(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["delete-progress"]);
    if($ID==0){die();}
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $hostname=$ligne["hostname"];
    $md=$_GET["md"];
    $tpl->js_dialog("{remove} $hostname", "$page?delete-popup=$ID&md=$md");
}
function delete_progress_popup(){

    $ID=intval($_GET["delete-popup"]);
    $tpl = new template_admin();
    $md=$_GET["md"];

    $service_reconfigure=$tpl->framework_buildjs(
        "wordpress.php?remove-site=$ID",
        "wordpress.remove.progress",
        "wordpress.remove.progress.txt","delete-wordpress-$ID","$('#$md').remove();BootstrapDialog1.close();"

    );

    $html[]="<div id='delete-wordpress-$ID'></div>";
    $html[]="<script>$service_reconfigure</script>";
    echo $tpl->_ENGINE_parse_body($html);


}

function backup_popup(){
    $page = CurrentPageName();
    $ID=intval($_GET["edit-backups"]);
    $html="<div style='margin-top:15px;margin-bottom:15px' id='progress-backup-$ID'></div>
    <div id='wordpress-backups-$ID'></div>
    <script>LoadAjax('wordpress-backups-$ID','$page?backups-table=$ID');</script>";
    echo $html;


}
function backup_delete_js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["backup-delete"]);
    $md=$_GET["md"];
    if($ID==0){die();}
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_backup WHERE ID=$ID");
    $zdate=$tpl->time_to_date($ligne["backuptime"],true);
    $filesize=FormatBytes($ligne["filesize"]/1024);
    return $tpl->js_dialog_confirm_action("{remove} {backup} $ID($filesize): $zdate ","backup-delete",$ID,"$('#$md').remove()");

}
function backup_delete_perform():bool{
    $tpl = new template_admin();
    $ID=intval($_POST["backup-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_backup WHERE ID=$ID");
    $wp_hostname_from_id=wp_hostname_from_id($ligne["siteid"]);
    $zdate=$tpl->time_to_date($ligne["backuptime"],true);
    $filesize=FormatBytes($ligne["filesize"]/1024);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wordpress.php?backup-delete=$ID");
    return admin_tracks("Removing Wordpress backup $ID $zdate $filesize for $wp_hostname_from_id");
}

function backup_table(){

    $wpid=intval($_GET["backups-table"]);
    $page = CurrentPageName();
    $tpl = new template_admin();
    $t=time();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.backup.$wpid.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.backup.$wpid.log";
    $ARRAY["CMD"] = "wordpress.php?backup-now=$wpid";
    $ARRAY["TITLE"] = "{reconfiguring}";
    $ARRAY["AFTER"] = "LoadAjax('wordpress-backups-$wpid','$page?backups-table=$wpid');";
    $prgress = base64_encode(serialize($ARRAY));
    $backup_now = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-backup-$wpid');";

    $html[] = "<div id='backup-progress-$wpid'></div>";
    $html[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"$backup_now\"><i class='fa fa-plus'></i> {backup_now} </label>";
    $html[] = "<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?import-backup-js=$wpid');\"><i class='fa fa-save'></i> {import_wordpress_backup} </label>";
    $html[] = "</div>";
    $html[] = "<table id='table-$t-main' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{saved_on}</a></th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>".$tpl->td_href("DB","{database_size}")."</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>".$tpl->td_href("BCK","{backup_size}")."</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{download}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{restore}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>Del</th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");

    $results=$q->QUERY_SQL("SELECT * FROM wp_backup WHERE siteid=$wpid ORDER BY ID DESC");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
        $ID = $ligne["ID"];
        $zdate=$tpl->time_to_date($ligne["backuptime"],true);
        $dbsize=FormatBytes($ligne["dbsize"]/1024);
        $filesize=FormatBytes($ligne["filesize"]/1024);
        $md=md5(serialize($ligne));
        $fullpath=$ligne["fullpath"];
        if(!is_file($fullpath)){
            $q->QUERY_SQL("DELETE FROM wp_backup WHERE ID=$ID");
            continue;
        }

        $download=$tpl->icon_download("direct:/$page?download-backup=$ID","AsSystemWebMaster");
        $restore=$tpl->icon_upload("Loadjs('$page?restore-backup-js=$ID&siteid=$wpid')","AsSystemWebMaster");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td nowrap><strong>$zdate</strong></td>";
        $html[]="<td style='width:1%' nowrap>$dbsize</td>";
        $html[]="<td style='width:1%' nowrap>$filesize</td>";
        $html[]="<td style='width:1%' nowrap class='center'>$download</td>";
        $html[]="<td style='width:1%' nowrap>$restore</td>";
        $html[]="<td style='width:1%'>". $tpl->icon_delete("Loadjs('$page?backup-delete=$ID&md=$md')","AsSystemWebMaster")."</td>";
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
	$(document).ready(function() { $('#table-$t-main').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);



}


function edit_tabs(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["edit-tabs"]);

    $array["{website}"]="$page?edit-parameters=$ID";
    $array["{UseSSL}"]="$page?edit-ssl=$ID";
    $array["{backup}"]="$page?edit-backups=$ID";
    $array["wp-admin"]="fw.wordpress.wp-admin.php?ID=$ID";
    echo $tpl->tabs_default($array);

}
function delete_js(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["delete"]);
    $md=$_GET["md"];
    if($ID==0){die();}
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $hostname=$ligne["hostname"];

    $DELETE_WORDPRESS_WARN=$tpl->_ENGINE_parse_body("{DELETE_WORDPRESS_WARN}");
    $DELETE_WORDPRESS_WARN=str_replace("%s",$hostname,$DELETE_WORDPRESS_WARN);
    $DELETE_WORDPRESS_WARN=str_replace("%id",$ID,$DELETE_WORDPRESS_WARN);
    $jsafter="Loadjs('$page?delete-progress=$ID&md=$md');";
    $tpl->js_dialog_confirm_action($DELETE_WORDPRESS_WARN,"delete-hole",$ID,$jsafter);


}

function readonly_switch(){
    $ID=$_GET["readonly"];
    $sock=new sockets();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $tpl=new template_admin();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");

    if(intval($ligne["readonly"])==1) {
        $q->QUERY_SQL("UPDATE wp_sites SET readonly='0' WHERE ID=$ID");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
        $sock->getFrameWork("wordpress.php?readonly-off=$ID");
        return;
    }

    $q->QUERY_SQL("UPDATE wp_sites SET readonly='1' WHERE ID=$ID");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $sock->getFrameWork("wordpress.php?readonly-on=$ID");

}

function build_row_of():bool{
    $ID=intval($_GET["rowof"]);
    $tpl=new template_admin();

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $WORDPRESS_LOCAL_CONFS=wordpress_fs();
    $status=base64_encode($tpl->_ENGINE_parse_body(table_status($ligne,$WORDPRESS_LOCAL_CONFS)));
    $statusExplain=base64_encode(table_status_explain($ligne));
    $cache_status=base64_encode(status_row_cache($ligne));
    header("content-type: application/x-javascript");

    $f[]= "// $ID) Status = {$ligne["status"]}, enabled = {$ligne["enabled"]}\n";

    $f[]="if(document.getElementById('cache-status-$ID')){";
    $f[]="\ttempdata=base64_decode('$cache_status');";
    $f[]="\tdocument.getElementById('cache-status-$ID').innerHTML=tempdata;";
    $f[]="}\n";
    $f[]="if(document.getElementById('wp-status-$ID')){";
    $f[]="\ttempdata=base64_decode('$status');";
    $f[]="document.getElementById('wp-status-$ID').innerHTML=tempdata;";
    $f[]="}\n";
    $f[]="if(document.getElementById('wp-status-explain-$ID')){";
    $f[]="\ttempdata=base64_decode('$statusExplain');";
    $f[]="document.getElementById('wp-status-explain-$ID').innerHTML=tempdata;";
    $f[]="}\n";
    echo @implode("\n",$f);
    return true;
}

function enable_switch(){
    $ID=$_GET["enable"];
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $tpl=new template_admin();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    if(intval($ligne["enabled"])==1){
        $q->QUERY_SQL("UPDATE wp_sites SET enabled='0' WHERE ID=$ID");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

        $ligne["enabled"]=0;
        header("content-type: application/x-javascript");
        echo "Loadjs('$page?rowof=$ID')";
        $sock=new sockets();
        $sock->getFrameWork("wordpress.php?enable-checks=yes");
        return;
    }

    $q->QUERY_SQL("UPDATE wp_sites SET enabled='1' WHERE ID=$ID");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    $ligne["enabled"]=1;
    header("content-type: application/x-javascript");
    echo "Loadjs('$page?rowof=$ID')";
    $sock=new sockets();
    $sock->getFrameWork("wordpress.php?enable-checks=yes");

}



function edit_parameters(){
    $page = CurrentPageName();
    $ID=intval($_GET["edit-parameters"]);
    echo "<div id='wordpress-maindiv-$ID'></div>
    <script>
        LoadAjax('wordpress-maindiv-$ID','$page?edit-parameters2=$ID');
    </script>";
}

function edit_parameters2(){

    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["edit-parameters2"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");

    $sql="CREATE TABLE IF NOT EXISTS `wp_sites` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`WP_LANG` TEXT,
		`date_created` TEXT,
		`hostname` TEXT UNIQUE,
		`admin_user` TEXT,
		`admin_password` TEXT,
		`admin_email` TEXT,
		`database_name` TEXT,
		`database_user` TEXT,
		`database_password` TEXT,
		`database_error` TEXT,
		`aliases` TEXT,
		`wp_version` TEXT,
		`ssl` INTEGER NOT NULL DEFAULT 0,
		`letsencrypt` INTEGER NOT NULL DEFAULT 0,
		`ssl_certificate` TEXT,
		`enabled` INTEGER,
		`status` INTEGER,
		`cgicache` INTEGER,
		`readonly` INTEGER NOT NULL DEFAULT 0,
		`site_size` INTEGER NOT NULL DEFAULT 0,
		`wp_config` TEXT,
        `zmd5` TEXT
		)
		";

    $q->QUERY_SQL($sql);

    if($q->TABLE_EXISTS("wp_sites")) {
        if (!$q->FIELD_EXISTS("wp_sites", "cgicache")) {
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD cgicache INTEGER");
        }
        if (!$q->FIELD_EXISTS("wp_sites", "readonly")) {
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD readonly INTEGER DEFAULT 0");
        }
        if (!$q->FIELD_EXISTS("wp_sites", "redirecturi")) {
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD redirecturi TEXT NULL");
        }
        if (!$q->FIELD_EXISTS("wp_sites", "pagespeed")) {
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD pagespeed INTEGER DEFAULT 0");
        }
        if (!$q->FIELD_EXISTS("wp_sites", "yoast")) {
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD yoast INTEGER DEFAULT 0");
        }
    }

    $nginx_pagespeed_enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"));
    $nginx_pagespeed_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"));
    if($nginx_pagespeed_installed==0){$nginx_pagespeed_enabled=0;}

    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $tpl->field_hidden("edit","$ID");

    if($nginx_pagespeed_enabled==1) {
        $form[] = $tpl->field_checkbox("pagespeed", "{enable_mod_pagespeed}", $ligne["pagespeed"], false, "{mod_pagespeed_about}");
    }else{
        $form[] = $tpl->field_checkbox("pagespeed", "{enable_mod_pagespeed}", 0, false, "{mod_pagespeed_about}",true);
    }


    $form[]=$tpl->field_checkbox("readonly","{readonly}",$ligne["readonly"],false);
    $form[]=$tpl->field_button("{$ligne["hostname"]}","{change}","Loadjs('$page?ch-hostname=$ID')");
    $form[]=$tpl->field_button("{administrator} ({$ligne["admin_user"]})","{change}","Loadjs('$page?ch-admin=$ID')");
    $form[]=$tpl->field_button("{plugins}","{manage}","Loadjs('fw.wordpress.plugins.php?siteid=$ID')");
    $form[]=$tpl->field_checkbox("yoast","{yoast_plugin_support}",$ligne["yoast"],false);
    $form[]=$tpl->field_button("{templates}","{manage}","Loadjs('fw.wordpress.templates.php?siteid=$ID')");
    $form[]=$tpl->field_button("{content_replacement}","{tool}","Loadjs('$page?replace-js=yes&siteid=$ID')");




    $service_reconfigure=$tpl->framework_buildjs(
        "wordpress.php?build-single=$ID",
        "wordpress.$ID.progress",
        "wordpress.$ID.progress.txt",
        "progress-websites-restart",
        "LoadAjax('table-wordpress-rules','$page?table=yes');","Loadjs('$page?rowof=$ID')"
    );


    $form=$tpl->form_outside("{$ligne["hostname"]}: {parameters}",$form,null,"{apply}",$service_reconfigure,"AsSystemWebMaster");
    $html[]="<div id='wp-progr-$ID'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valign='top' style='width:530px'>$form</td>";
    $html[]="<td valign='top' style='width:120px'><div id='core-version-$ID' style='border-left:3px solid #cccccc;padding-left: 10px'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>LoadAjax('core-version-$ID','$page?core-status=$ID');</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function core_status(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["core-status"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wordpress.php?core-status=$ID");
    $target=PROGRESS_DIR."/wp.core-status.$ID";
    $data=explode("\n",@file_get_contents($target));
    $WP_VER="0.0.0";
    $DB_VER="0.0.0";
    $LANG_VER="{unknown}";
    foreach ($data as $ligne){
        if(preg_match("#WordPress version:(.+)#i",$ligne,$re)){
            $WP_VER=$re[1];
        }
        if(preg_match("#Database revision:(.+)#i",$ligne,$re)){
            $DB_VER=$re[1];
        }
        if(preg_match("#Package language:(.+)#i",$ligne,$re)){
            $LANG_VER=$re[1];
        }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $database_name=$ligne["database_name"];

    $q=new mysql();
    $ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT TABLE_SCHEMA AS `Database`,ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS `MB` FROM information_schema.TABLES WHERE TABLE_SCHEMA='$database_name'"));
    $DB_SIZE=$ligne["MB"];


    $style1="width:1%;vertical-align:top;padding-top:4px;text-align:right";

    $html[]="<table style='width:100%;margin-top:39px;margin-left:10px'>";
    $html[]="<tr style='height: 50px'>";
    $html[]="<td style='$style1' nowrap><strong>Wordpress {version}:</td>";
    $html[]="<td valign='top' style='width:99%;padding-left:10px'><H2 style='margin:0'>$WP_VER</td>";
    $html[]="</tr>";
    $html[]="<tr style='height: 50px'>";
    $html[]="<td  style=$style1' nowrap><strong>{database} {version}:</td>";
    $html[]="<td valign='top' style='width:99%;padding-left:10px'><H2 style='margin:0'>$DB_VER</td>";
    $html[]="</tr>";
    $html[]="<tr style='height: 50px'>";
    $html[]="<td  style=$style1' nowrap><strong>{database} {size}:</td>";
    $html[]="<td valign='top' style='width:99%;padding-left:10px' nowrap><H2 style='margin:0'>$DB_SIZE MB</td>";
    $html[]="</tr>";
    $html[]="<tr style='height: 50px'>";
    $html[]="<td style='$style1' nowrap><strong>{language} {version}:</td>";
    $html[]="<td valign='top' style='width:99%;padding-left:10px'><H2 style='margin:0'>$LANG_VER</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td valign='top' style='padding-top:10px;border-top:1px solid #CCCCCC' colspan=2 align='right'>".
        $tpl->button_inline("{update}","Loadjs('fw.wordpress.updates.php?siteid=$ID')",
        "fa-solid fa-download",null,128,"btn-info")."</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<tr><td valign='top'colspan=2 align='right'>&nbsp;</td></tr>";
    $html[]="<tr>";
//<i class="fa-duotone fa-hammer"></i>

    $js=$tpl->framework_buildjs("wordpress.php?repair-db=$ID",
        "wordpress.mysql.$ID.progress","wordpress.mysql.$ID.log","wp-progr-$ID");

    $html[]="<td valign='top' style='padding-top:10px;border-top:1px solid #CCCCCC' colspan=2 align='right'>".
        $tpl->button_inline("{mysql_repair}",$js,
            "fa-duotone fa-hammer",null,128,"btn-warning")."</td>";
    $html[]="</tr>";



    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);
}


function ch_hostname_popup(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["ch-hostname-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $dbname=$ligne["database_name"];
    $hostname=$ligne["hostname"];
    $disabled=false;

    $tpl->field_hidden("ch-hostname","$ID");
    $form[]=$tpl->field_text("hostname","{sitename}",$ligne["hostname"],false);

    if(!$q->FIELD_EXISTS("wp_sites","aliases")){
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD aliases TEXT");
    }

    $aliases=unserialize(base64_decode($ligne["aliases"]));
    $zalizas=array();
    foreach ($aliases as $sitename=>$none){
        $zalizas[]=trim(strtolower($sitename));
    }
    $form[]=$tpl->field_text("aliases","{aliases}",@implode(",",$zalizas),false);

    $q=new mysql();
    $ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT option_value FROM prfx_options WHERE option_name = 'home'",$dbname));
    if(!$q->ok){echo "<p>".$q->mysql_error_html()."</p>";$disabled=true;}

    $form[]=$tpl->field_text("prfx_home","{sitename} (home)",$ligne["option_value"],true);
    $ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT option_value FROM prfx_options WHERE option_name = 'siteurl'",$dbname));

    $t=time();

    $service_reconfigure=$tpl->framework_buildjs("wordpress.php?build-single=$ID",
        "wordpress.$ID.progress",
        "wordpress.$ID.progress.txt",
        "div-$t",
        "LoadAjax('table-wordpress-rules','$page?table=yes');LoadAjax('wordpress-maindiv-$ID','$page?edit-parameters2=$ID');");

    //$service_reconfigure=null;

    $form[]=$tpl->field_text("prfx_siteurl","{sitename} (url)",$ligne["option_value"],true);
    $html[]="<div id='div-$t'></div>";
    $html[]= $tpl->form_outside("$hostname",$form,null,"{apply}",
        $service_reconfigure,"AsSystemWebMaster",false,$disabled);

    echo $tpl->_ENGINE_parse_body($html);

}
function ch_hostname_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $HASH=array();
    $ID=intval($_POST["ch-hostname"]);
    $_POST["aliases"]=str_replace(" ",",",$_POST["aliases"]);
    $_POST["aliases"]=str_replace(";",",",$_POST["aliases"]);
    $content=explode(",",$_POST["aliases"]);
    foreach ($content as $domain){
        $domain=trim(strtolower($domain));
        if($domain==null){continue;}
        $HASH[$domain]=true;
    }
    $new_aliases=base64_encode(serialize($HASH));

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname,database_name FROM wp_sites WHERE ID=$ID");
    $dbname=$ligne["database_name"];
    $hostname=$ligne["hostname"];

    $sql="UPDATE wp_sites SET hostname='{$_POST["hostname"]}',aliases='$new_aliases' WHERE ID=$ID";
    $q->QUERY_SQL($sql);

    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }

    $siteurl=$_POST["prfx_siteurl"];
    $home=$_POST["prfx_home"];

    if (!validate_url($home)) {
        echo $tpl->post_error("$home {invalid}");
        return false;
    }
    if (!validate_url($home)) {
        echo $tpl->post_error("$siteurl {invalid}");
        return false;
    }

    $q=new mysql();
    $q->QUERY_SQL("UPDATE prfx_options SET option_value='$siteurl' WHERE option_name = 'siteurl'",$dbname);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error."\nDatabase:$dbname");return false;}

    $q->QUERY_SQL("UPDATE prfx_options SET option_value='$home' WHERE option_name = 'home'",$dbname);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    admin_tracks_post("Wordpress($hostname) change");
    return true;


}

function edit_parameters_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["edit"];
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");

    $sql="UPDATE wp_sites SET 
    pagespeed='{$_POST["pagespeed"]}',
    yoast='{$_POST["yoast"]}',
    readonly='{$_POST["readonly"]}' WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    admin_tracks_post("Wordpress site {$_POST["hostname"]}");
    return true;
}

function edit_ssl(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID = intval($_GET["edit-ssl"]);
    $html[]="<div id='letsencrypt-ssl-$ID'></div>";
    $html[]="<script>LoadAjaxSilent('letsencrypt-ssl-$ID','$page?edit-ssl2=$ID');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function edit_ssl2(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ID = intval($_GET["edit-ssl2"]);
    $q = new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $APP_LETSENCRYPT_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_LETSENCRYPT_INSTALLED"));
    $ligne = $q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $tpl->field_hidden("edit-ssl", "$ID");

    if ($APP_LETSENCRYPT_INSTALLED == 1) {
        //$js="letsencrypt-progress-$ID";
        if($ligne["ssl"]==0) {
            $js = "LoadAjaxSilent('letsencrypt-progress-$ID','$page?letsencrypt-wizard=$ID');";

            $form[] = "<div style='text-align:right'>" . $tpl->button_autnonome("{LETSENCRYPT_CERTIFICATE}", $js,
                    "fa-duotone fa-file-certificate", "AsSystemWebMaster", 350) . "</div>";
        }
    }


    $form[] = "<div id='letsencrypt-progress-$ID'></div>";
    $form[] = $tpl->field_checkbox("ssl", "{UseSSL}", $ligne["ssl"], true);
    $form[] = $tpl->field_certificate("ssl_certificate", "{use_certificate_from_certificate_center}", $ligne["ssl_certificate"]);


    $ARRAY["PROGRESS_FILE"] = "/usr/share/artica-postfix/ressources/logs/wordpress.$ID.progress";
    $ARRAY["LOG_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/wordpress.$ID.progress.txt";
    $ARRAY["CMD"] = "wordpress.php?ssl=$ID";
    $ARRAY["TITLE"] = "{reconfiguring}";
    $ARRAY["AFTER"] = "LoadAjax('table-wordpress-rules','$page?table=yes');";
    $prgress = base64_encode(serialize($ARRAY));
    $service_reconfigure = "BootstrapDialog1.close();Loadjs('fw.progress.php?content=$prgress&mainid=letsencrypt-progress-$ID');";
    echo $tpl->form_outside("{generate_ssl}", $form, null, "{apply}", $service_reconfigure, "AsSystemWebMaster");
}
function letsencrypt_wizard(){
    $page=CurrentPageName();
    $tpl = new template_admin();
    $ID=intval($_GET["letsencrypt-wizard"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $aliases=unserialize(base64_decode($ligne["aliases"]));
    $email=$ligne["admin_email"];
    $wws[]=$ligne["hostname"];
    foreach ($aliases as $sitename=>$none){
        $wws[]=$sitename;
    }

    $text=$tpl->_ENGINE_parse_body("{WIZARD_LETSENCRYPT}");
    $text=str_replace("%d",@implode(", ",$wws),$text);
    $text=str_replace("%o",$email,$text);
    $html[]="<div style='width:95%'>";
    $html[]=$tpl->div_explain("{LETSENCRYPT_CERTIFICATE}||$text");

    $back="document.getElementById('letsencrypt-progress-$ID').innerHTML='';";

    $service_reconfigure=$tpl->framework_buildjs(
        "wordpress.php?letsencrypt=$ID",
        "wordpress.letsencrypt.progress.$ID",
        "wordpress.letsencrypt.log.$ID",
        "letsencrypt-progress-$ID",
        "LoadAjaxSilent('letsencrypt-ssl-$ID','$page?edit-ssl2=$ID');"
    );

    $html[]="<div style='width:100%;text-align:right'>";
    $html[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[] = "<label class=\"btn btn btn-warning\" OnClick=\"$back\"><i class='fa-solid fa-hand-back-point-left'></i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{cancel}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>";
    $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"$service_reconfigure\"><i class='fa-solid fa-hand-back-point-right'></i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{next}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>";
    $html[] = "</div>";
    $html[] = "</div>";



    echo $tpl->_ENGINE_parse_body($html);
}

function edit_ssl_save(){
    $tpl = new template_admin();
    $ID=intval($_POST["edit-ssl"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $_POST["ssl"]=intval($_POST["ssl"]);
    $_POST["letsencrypt"]=intval($_POST["letsencrypt"]);
    $q->QUERY_SQL("UPDATE wp_sites set 
        ssl={$_POST["ssl"]},ssl_certificate='{$_POST["ssl_certificate"]}',letsencrypt='{$_POST["letsencrypt"]}' WHERE ID=$ID");


    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);}
}


function new_website_popup(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->field_hidden("new","yes");
    $t=time();
    $form[]=$tpl->field_text("hostname","{webserver_name}",null,true,"{acls_add_dstdomaindst}");
    $form[]=$tpl->field_text("admin_user","{administrator}",null,true,"");
    $form[]=$tpl->field_email("admin_email","{admin_email}",null,true,"");
    $form[]=$tpl->field_password2("admin_password","{password}",null,true,"");

    $service_reconfigure=$tpl->framework_buildjs("wordpress.php?build-new-sites=yes",
        "wordpress.build.progress",
        "wordpress.build.progress.txt", $t,
        "dialogInstance2.close();
        LoadAjax('table-wordpress-rules','$page?table=yes');",
        "LoadAjax('table-wordpress-rules','$page?table=yes');"
    );



    $html[]="<div id='$t'></div>";
    $html[]=$tpl->form_outside("{new_wordpress_site}",$form,null,"{add}",$service_reconfigure,"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);



}

function new_website_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    /* `WP_LANG` VARCHAR( 128 ),
     `date_created` INTEGER,
     `hostname` VARCHAR( 256 ),
     `admin_user` VARCHAR( 128 ),
     `admin_password` VARCHAR( 128 ),
     `admin_email` VARCHAR( 128 ),
     `database_name` VARCHAR(128),
     `wp_version` VARCHAR(10),
     `ssl_certificate` VARCHAR(128),
     `enabled` INTEGER
    */

    $_POST["hostname"]=trim($_POST["hostname"]);
    if(trim($_POST["hostname"])==null){
        echo $tpl->post_error("Hostname is None!");
        return false;
    }

    if(strpos("   ".$_POST["hostname"],"/")>0){
        $parse=parse_url($_POST["hostname"]);
        $www=$parse["host"];
        if(strpos($www, ":")>0){$t=explode(":", $www);$www=$t[0];}
        $_POST["hostname"]=$www;
    }

    $md5=md5($_POST["hostname"].$_POST["admin_email"].time());

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");

    if(!$q->FIELD_EXISTS("wp_sites","zmd5")){
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD zmd5 TEXT");
    }

    $_POST["admin_password"]=$q->sqlite_escape_string2( $_POST["admin_password"]);
    $vals[]=time();
    $vals[]=$_POST["hostname"];
    $vals[]=$_POST["admin_user"];
    $vals[]=$_POST["admin_email"];
    $vals[]=$_POST["admin_password"];
    $vals[]="wordpress_".time();
    $vals[]=0;
    $vals[]=1;
    $vals[]=$md5;
    $vals=$tpl->MYSQL_ENCOSE($vals);

    $WORDPRESS_TO_CREATE_UU=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WORDPRESS_TO_CREATE"));
    $WORDPRESS_TO_CREATE=unserialize($WORDPRESS_TO_CREATE_UU);
    $WORDPRESS_TO_CREATE[$md5]=true;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WORDPRESS_TO_CREATE",base64_encode(serialize($WORDPRESS_TO_CREATE)));

    $sql="INSERT INTO wp_sites (date_created,hostname,admin_user,admin_email,admin_password,database_name,status,enabled,zmd5 ) VALUES (".@implode(",",$vals).")";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);}

}



function page(){
    $page=CurrentPageName();
    $WORDPRESS_SRC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WORDPRESS_SRC_VERSION");
    $tpl=new template_admin();

    $html=$tpl->page_header("{wordpress_websites} v.$WORDPRESS_SRC_VERSION",
        "fab fa-wordpress","{APP_WORDPRESS_ARTICA_TEXT}","$page?tabs=yes","wordpress","progress-websites-restart",false,"table-wordpress-rules");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{wordpress_websites}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function table_status($ligne,$WORDPRESS_LOCAL_CONFS){
    $status=intval($ligne["status"]);
    $enabled=intval($ligne["enabled"]);
    $ID=intval($ligne["ID"]);


    if($enabled==0){
        return "<span class=\"label label\">{disabled}</span>";
    }

    if($status==0){
        return "<span class=label>{waiting}</span>";
    }

    if($status==999){
        return "<span class=\"label label-danger\">{error}</span>";

    }
    if($status==1){
        if(!isset($WORDPRESS_LOCAL_CONFS[$ID])){
            return "<span class=\"label label-danger\">{missing} {file}</span>";
        }

        return "<span class=\"label label-primary\">{installed}</span>";
    }
    if($status==2){
        return "<span class=\"label label-warning\">{error}</span>";
    }

    if(!isset($WORDPRESS_LOCAL_CONFS[$ID])){
        return "<span class=\"label label-danger\">{missing} {file}</span>";
    }




    return null;



}

function wordpress_fs(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wordpress.php?wordpress-fs=yes");
    $wordpress_fs=PROGRESS_DIR."/wordpress-fs.dump";
    $WORDPRESS_LOCAL_CONFS=unserialize(@file_get_contents($wordpress_fs));
    return $WORDPRESS_LOCAL_CONFS;
}

function table_status_explain($ligne){
    $status=intval($ligne["status"]);
    $statusExplain=null;
    if($status==999){
        $statusExplain="<br><small class='text-danger'>{$ligne["database_error"]}</small>";
    }
    if($status==2){
        $statusExplain="<br><small class='text-danger'>{$ligne["database_error"]}</small>";
    }

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $statusExplain="<br><small class='text-danger'>{license_error}</small>";
    }
    return $statusExplain;
}

function table()
{
    $page = CurrentPageName();
    $tpl = new template_admin();

    $t=time();

    $service_reconfigure=$tpl->framework_buildjs("wordpress.php?build-sites=yes",
        "wordpress.build.progress",
        "wordpress.build.progress.txt",
        "progress-websites-restart",
        "LoadAjax('table-wordpress-rules','$page?table=yes');");


    $WORDPRESS_LOCAL_CONFS=wordpress_fs();


    if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $bts[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
        $bts[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?new-www-js=yes');\"><i class='fa fa-plus'></i> {new_wordpress_site} </label>";
        $bts[] = "<label class=\"btn btn btn-info\" OnClick=\"$service_reconfigure\"><i class='fa fa-save'></i> {reconfigure_service} </label>";
        $bts[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.wordpress.duplicator.php')\"><i class='fa fa-save'></i> Wordpress Duplicator </label>";


        $bts[] = "</div>";
    }else{
        $bts[] = "<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:15px'>";
        $bts[] = "<label class=\"btn btn btn-default\" OnClick=\"blur()\"><i class='fa fa-plus'></i> {new_wordpress_site} </label>";
        $bts[] = "<label class=\"btn btn btn-default\" OnClick=\"blur()\"><i class='fa fa-save'></i> {reconfigure_service} </label>";
        $bts[] = "</div>";

    }
    $html[] = "<table id='table-$t-main' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:15px'>";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{status}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap width='1%'>&nbsp;</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap width='1%'>&nbsp;</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap width='1%'>&nbsp;</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{wordpress_websites}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{email}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{version}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{size}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{cache}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{saved_on}</th>";
    $html[] = "<th data-sortable=false width='1%' nowrap>{readonly}</th>";
    $html[] = "<th data-sortable=false width='1%' nowrap>{enabled}</th>";
    $html[] = "<th data-sortable=false width='1%' nowrap>&nbsp;</th>";

    $html[] = "<th data-sortable=false width='1%' nowrap></th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");

    if(!$q->FIELD_EXISTS("wp_sites","ssl")){
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD ssl INTEGER NOT NULL DEFAULT '0'");
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD letsencrypt INTEGER NOT NULL DEFAULT '0'");
    }
    if(!$q->FIELD_EXISTS("wp_sites","pagespeed")){
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD pagespeed INTEGER DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("wp_sites","cacheid")){
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD cacheid INTEGER DEFAULT 0");
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD proxy_cache_revalidate INTEGER DEFAULT 1");
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD proxy_cache_min_uses INTEGER DEFAULT 1");
    }


    $results=$q->QUERY_SQL("SELECT * FROM wp_sites ORDER BY hostname");
    $NGINX_ALL_HOSTS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_ALL_HOSTS"));


    $TRCLASS=null;
    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
        $ID = $ligne["ID"];
        $hostname=$ligne["hostname"];
        $hostname_src=$hostname;
        $date_created=$tpl->time_to_date($ligne["date_created"]);
        $md=md5(serialize($ligne));
        $status=intval($ligne["status"]);
        $enabled=intval($ligne['enabled']);
        $cgicache=intval($ligne["cgicache"]);
        $site_size=intval($ligne["site_size"]);
        $admin_email=trim($ligne["admin_email"]);
        $version=trim($ligne["wp_version"]);
        $readonly=intval($ligne["readonly"]);
        $pagespeed=intval($ligne["pagespeed"]);
        $cacheid=intval($ligne["cacheid"]);

        if($site_size==0){$site_size=$tpl->icon_nothing();}
        if($site_size>0){$site_size=FormatBytes($site_size/1024);}

        $statusTips=table_status($ligne,$WORDPRESS_LOCAL_CONFS);
        $statusExplain=table_status_explain($ligne);

        $zaliases_text=null;
        $zaliases=array();
        $aliases=unserialize(base64_decode($ligne["aliases"]));
        if(is_array($aliases)) {
            if (count($aliases) > 0) {
                foreach ($aliases as $sitename => $none) {
                    $zaliases[] = $sitename;
                }
            }
        }

        if(count($zaliases)>0){$zaliases_text="<br><small>". @implode(", ",$zaliases)."</small>";}
        $sreq_text=null;$sreq=array();
        if(isset($NGINX_ALL_HOSTS[$hostname_src])){
            $RQS=$NGINX_ALL_HOSTS[$hostname_src]["RQS"];
            $BYTES=$NGINX_ALL_HOSTS[$hostname_src]["RQS"];
            if($RQS>0){
                $sreq[]=$tpl->FormatNumber($RQS)." {requests}";
            }
            if($BYTES>0){
                $sreq[]=FormatBytes($RQS/1024);
            }

            $sreq_text="&nbsp;<small>".@implode("&nbsp;/&nbsp;",$sreq)."</small>";
        }

        if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            $hostname = $tpl->td_href("$hostname", null, "Loadjs('$page?edit-js=$ID')");
        }

        $icon_check_ro=$tpl->icon_check($readonly,"Loadjs('$page?readonly=$ID')",null,"AsSystemWebMaster");
        $icon_check=$tpl->icon_check($enabled,"Loadjs('$page?enable=$ID')",null,"AsSystemWebMaster");

        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            $icon_check_ro=null;
            $icon_check=null;
        }

        $reconf=$tpl->framework_buildjs("wordpress.php?nginx-site=$ID","wordpress.single.$ID","wordpress.single.$ID.log","progress-websites-restart","Loadjs('$page?rowof=$ID')","Loadjs('$page?rowof=$ID')");

        $icon_run=$tpl->icon_run($reconf,"AsSystemWebMaster");



        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap><span id='wp-status-$ID'>$statusTips</span></td>";
        $html[]="<td style='width:1%' nowrap class='center'>$ID</td>";

        $pagespeed_ico="&nbsp;";
        if($pagespeed==1) {
            $pagespeed_ico="<i class='text-warning fa-solid fa-gauge-circle-bolt'></i>";
            if (is_file("/etc/nginx/wordpress/pagespeed.$ID.module")) {
                $pagespeed_ico="<i class='fa-solid fa-gauge-circle-bolt' style='color:#1ab394'></i>";
            }
            if($enabled==0){
                $pagespeed_ico="<i class='text-muted fa-solid fa-gauge-circle-bolt'></i>";
            }
        }
        $graphs="<i class='fas fa-chart-pie' style='color:#d1dade'></i>";
        if(is_file("img/squid/nginx_requests_$hostname_src-day.flat.png")){
            VERBOSE("img/squid/nginx_requests_$hostname_src-day.flat.png OK",__LINE__);
            $graphs=$tpl->td_href("<i class='fas fa-chart-pie' style='color:#1ab394'></i>",null,
                "Loadjs('fw.rrd.php?img=nginx_requests_$hostname_src')");
        }else{
            VERBOSE("img/squid/nginx_requests_$hostname_src-day.flat.png no such file",__LINE__);
        }
        $stattuscgicache=status_row_cache($ligne);
        $html[]="<td style='width:1%' nowrap class='center'>$pagespeed_ico</td>";
        $html[]="<td style='width:1%' nowrap class='center'>$graphs</td>";
        $html[]="<td><strong>$hostname</strong>{$sreq_text}{$zaliases_text}<span id='wp-status-explain-$ID'>{$statusExplain}</span></td>";
        $url_version_update="Loadjs('fw.wordpress.updates.php?siteid=$ID')";
        $version=$tpl->td_href($version,null,$url_version_update);
        $html[]="<td style='width:1%' nowrap>$admin_email</td>";
        $html[]="<td style='width:1%' nowrap>$version</td>";
        $html[]="<td style='width:1%' nowrap>$site_size</td>";
        $html[]="<td style='width:1%' nowrap><span id='cache-status-$ID'>$stattuscgicache</span></td>";
        $html[]="<td style='width:1%' nowrap>$date_created</td>";
        $html[]="<td class='center'>$icon_check_ro</td>";
        $html[]="<td class='center'>$icon_check</td>";
        $html[]="<td class='center'>$icon_run</td>";
        $html[]="<td class='center'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md=$md')","AsSystemWebMaster")."</td>";
        $html[]="</tr>";

    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='14'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $WORDPRESS_SRC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WORDPRESS_SRC_VERSION");


    $TINY_ARRAY["TITLE"]="{wordpress_websites} v.$WORDPRESS_SRC_VERSION";
    $TINY_ARRAY["ICO"]="fab fa-wordpress";
    $TINY_ARRAY["EXPL"]="{APP_WORDPRESS_ARTICA_TEXT}";
    $TINY_ARRAY["URL"]="wordpress";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t-main').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	$jstiny
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function status_row_cache($ligne):string{

    $ID=$ligne["ID"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isset($GLOBALS["NginxCacheRedis"])) {
        $NginxCacheRedis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"));
        $GLOBALS["NginxCacheRedis"] = $NginxCacheRedis;
    }

    $stattuscgicache= $tpl->label_click("grey","{disabled}","");
    if($GLOBALS["NginxCacheRedis"]==0){return $stattuscgicache;}
    $cacheid=intval($ligne["cgicache"]);
    if($cacheid==0) {
        return $tpl->label_click("grey","{inactive}","Loadjs('$page?cache-settings-js=$ID');");

    }

    return $tpl->label_click("green","{active2}","Loadjs('$page?cache-settings-js=$ID');");
}
function cache_settings_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["cache-settings-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $tpl->js_dialog1("{cache} {$ligne["hostname"]}","$page?cache-settings-popup=$ID");
}
function cache_settings_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["cache-settings-popup"]);



    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID=$ID");
    $tpl->field_hidden("cache",$ID);
    $tpl->field_hidden("hostname",$ligne["hostname"]);
    $tpl->field_hidden("cacheid",0);
    $form[]=$tpl->field_checkbox("cgicache","PHP {cache}",$ligne["cgicache"],false,"{ENABLE_CGI_CACHE}");
    if($ligne["proxy_cache_min_uses"]<5){

        $ligne["proxy_cache_min_uses"]=14400/60;
    }


    $form[]=$tpl->field_numeric("proxy_cache_min_uses","{cache_time} ({minutes})",$ligne["proxy_cache_min_uses"],"{proxy_cache_min_uses_text}");

    $service_reconfigure=$tpl->framework_buildjs(
        "wordpress.php?cache=$ID",
        "nginx.cache.$ID.progress",
        "nginx.cache.$ID.log",
        "progress-websites-restart",
        "Loadjs('$page?rowof=$ID')",
        "Loadjs('$page?rowof=$ID')"
    );


    $form=$tpl->form_outside("{$ligne["hostname"]}: {cache} ($ID)",$form,null,"{apply}","dialogInstance1.close();$service_reconfigure","AsSystemWebMaster");


    echo $tpl->_ENGINE_parse_body($form);


}
function cache_settings_save(){
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["cache"];
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");

    $sql="UPDATE wp_sites SET 
    cgicache='{$_POST["cgicache"]}',
    cacheid='{$_POST["cacheid"]}',
    proxy_cache_revalidate='{$_POST["proxy_cache_revalidate"]}',
    proxy_cache_min_uses='{$_POST["proxy_cache_min_uses"]}'
                WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    admin_tracks_post("Wordpress site cache settings {$_POST["hostname"]}");
    return true;
}