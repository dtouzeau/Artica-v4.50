<?php
$GLOBALS["redirtypes"][0]="{ngix_rwfl_redirect}";
$GLOBALS["redirtypes"][1]="{ngix_rwfl_permanent}";
$GLOBALS["redirtypes"][2]="{inside_redirection}";
$GLOBALS["redirtypes"][3]="{reset_connection}";

$GLOBALS["protocol"][0]="SSH";
$GLOBALS["protocol"][1]="FTP";
$GLOBALS["protocol"][2]="Rsync";




include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["start-rules"])){start_rules();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["SquidGuardWebUseExternalUri"])){save();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){rule_enable();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_GET["rule-move"])){rule_move();exit;}
if(isset($_POST["rule-delete-confirm"])){rule_delete_confirm();exit;}
page();
function page(){
    if(!isset($_GET["main-page"])){}
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{categories} {backub}","fas fa-ban",
        "{APP_UFDB_HTTP_EXPLAIN}","$page?tabs=yes","webfiltering-webpages",
        "progress-ufdbweb-restart,progress-weberrorules-restart",false,"categories-backup-tab");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_UFDB_HTTP}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function start_rules(){
    $page=CurrentPageName();
    echo "<div id='categories-backup-rules'></div><script>LoadAjax('categories-backup-rules','$page?table=yes');</script>";

}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{backup}"] = "$page?start-rules=yes";
    echo $tpl->tabs_default($array);

}
function rule_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-popup"]);
    $title="{new_rule}";
    $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM categories_backup WHERE ID='$ID'");
        $title="{rule}: {$ligne["rulename"]}";
    }
    $array[$title]="$page?rule-settings=$ID";
    echo $tpl->tabs_default($array);

}
function rule_settings(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $ID=intval($_GET["rule-settings"]);
    $explain=null;
    $title="{new_rule}";
    $but="{add}";
    $ligne["rulename"]="New rule";
    $ligne["enabled"]=1;

    $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
    if($ID>0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM categories_backup WHERE ID='$ID'");
        $but="{apply}";
    }
    $exit=null;


    if($ID==0){$exit="dialogInstance1.close();";}
    $jsafter="LoadAjax('categories-backup-rules','$page?table=yes');$exit";
    $form[]=$tpl->field_hidden("rule-save", "$ID");
    $form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    $form[]=$tpl->field_array_hash($GLOBALS["protocol"],
        "protocol", "nonull:{protocol}", $ligne["protocol"]);

    $form[]=$tpl->field_checkbox("ftp_tls","TLS (FTP)",$ligne["ftp_tls"]);
    $form[]=$tpl->field_checkbox("dnsmethod","{dns_method}",$ligne["dnsmethod"]);
    $form[]=$tpl->field_text("host","{remote_server_address}",$ligne["host"]);
    $form[]=$tpl->field_text("destpath","{remote_directory}",$ligne["destpath"]);
    $form[]=$tpl->field_text("username","{username}",$ligne["username"]);
    $form[]=$tpl->field_password("password","{password}",$ligne["password"]);
    $html=$tpl->form_outside($ligne["rulename"], @implode("\n", $form),$explain,$but,$jsafter,"AsDansGuardianAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function compile_rules(){
    $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
    $results=$q->QUERY_SQL("SELECT * FROM categories_backup WHERE enabled=1 ORDER BY zorder");
    if(!$q->ok){echo $q->mysql_error;}

    $WebErrorPagesCompiled=array();
    foreach ($results as $index=>$ligne){
        $host=$ligne["host"];
        $ID=$ligne["ID"];
        $destpath=$ligne["destpath"];
        $username=$ligne["username"];
        $ftp_tls=$ligne["ftp_tls"];
        $dnsmethod=intval($ligne["dnsmethod"]);
        $password=$ligne["password"];
        $protocol=intval($ligne["protocol"]);
        $WebErrorPagesCompiled[]=array(
            "host"=>$host,
            "ID"=>$ID,
            "ftp_tls"=>$ftp_tls,
            "dnsmethod"=>$dnsmethod,
            "destpath"=>$destpath,
            "username"=>$username,
            "password"=>$password,
            "protocol"=>$protocol);



    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CategoriesBackupCompiled",serialize($WebErrorPagesCompiled));


}

function rule_id_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid-js"]);
    $title="{new_rule}";


    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM categories_backup WHERE ID='$ID'");
        $title="{rule}: {$ligne["rulename"]}";
    }



    $title=$tpl->javascript_parse_text($title);
    $tpl->js_dialog1($title,"$page?rule-popup=$ID");
}
function rule_delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-rule-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM categories_backup WHERE ID='$ID'");
    $title="{rule}: {$ligne["rulename"]}";
    $md=$_GET["md"];
    $tpl->js_confirm_delete($title,"rule-delete-confirm",$ID,"$('#$md').remove();");
    return true;
}
function rule_delete_confirm():bool{
    $ID         = $_POST["rule-delete-confirm"];
    $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM categories_backup WHERE ID='$ID'");
    $title="{$ligne["rulename"]}";
    $q->QUERY_SQL("DELETE FROM categories_backup WHERE ID=$ID");
    admin_tracks("Remove Web error page rule $ID - $title");
    compile_rules();
    return true;
}
function rule_enable(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $ID=intval($_GET["enable-rule-js"]);

    $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled,rulename FROM categories_backup WHERE ID='$ID'");
    $title="{$ligne["rulename"]}";

    if(intval($ligne["enabled"])==0){$enabled=1;}else{$enabled=0;}

    $q->QUERY_SQL("UPDATE categories_backup SET enabled='$enabled' WHERE ID='$ID'");
    if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}
    admin_tracks("Change web page error rule $title to $enabled");
    compile_rules();
}
function rule_move(){
    $ID=intval($_GET["rule-move"]);
    $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
    $ligne=$q->mysqli_fetch_array("SELECT zorder,rulename FROM categories_backup WHERE ID='$ID'");


    $xORDER_ORG=intval($ligne["xORDER"]);
    $xORDER=$xORDER_ORG;
    $aclname=$ligne["rulename"];

    if($_GET["rule-dir"]==1){
        $xORDER=$xORDER_ORG-1;
    }
    if($_GET["rule-dir"]==0){
        $xORDER=$xORDER_ORG+1;

    }

    $sql="UPDATE categories_backup SET zorder=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);


    $sql="UPDATE categories_backup SET zorder=$xORDER_ORG WHERE `ID`<>'$ID' AND zorder=$xORDER";
    $q->QUERY_SQL($sql);

    $c=1;
    $sql="SELECT ID FROM categories_backup WHERE ORDER BY zorder";
    $results = $q->QUERY_SQL($sql);

    foreach($results as $index=>$ligne) {
        echo "// ID {$ligne["ID"]} became $c\n";
        $q->QUERY_SQL("UPDATE categories_backup SET zorder=$c WHERE `ID`={$ligne["ID"]}");
        $c++;
    }
    admin_tracks("Move Web error page rule order of $aclname from $xORDER_ORG to $xORDER");
    compile_rules();
}

function rule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["rule-save"]);
    unset($_POST["rule-save"]);


    reset($_POST);foreach ($_POST as $key=>$val){
        $EDIT[]="`$key`='$val'";
        $ADDFIELD[]="`$key`";
        $ADDVALS[]="'$val'";

    }

    if($ID==0){
        $sql="INSERT INTO categories_backup (".@implode(",", $ADDFIELD).") VALUES (".@implode(",", $ADDVALS).")";

    }else{
        $sql="UPDATE categories_backup SET ".@implode(",", $EDIT)." WHERE ID=$ID";

    }

    $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl->post_error($q->mysql_error);
        return false;
    }
    compile_rules();
    admin_tracks("Add/edit web error page rule {$_POST["rulename"]}");
    return true;
}

function table(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $page=CurrentPageName();
    $t=time();

    $q=new lib_sqlite("/home/artica/SQLITE/backup-local.db");
    //compile_categories_rbl

    $sql="CREATE TABLE IF NOT EXISTS `categories_backup` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				    rulename TEXT NOT NULL,
				    protocol INTEGER NOT NULL DEFAULT 0,
				    destpath TEXT NULL,
				    host TEXT NOT NULL,
				    username TEXT NULL,
				    password TEXT NULL,
				    indexes TEXT NULL,
				    dnsmethod INTEGER NOT NULL DEFAULT 0,
				    ftp_tls INTEGER NOT NULL DEFAULT 0,
				    enabled INTEGER NOT NULL DEFAULT 0,
				    zorder INTEGER NOT NULL DEFAULT 0)";

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}



    $TRCLASS=null;
    $add="Loadjs('$page?ruleid-js=0',true);";
    //if(!$users->AsDansGuardianAdministrator){$add="alert('ERROR_NO_PRIVS2')";}
    //$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ufdb-http.build.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/ufdb-http.build.progress.log";
    $ARRAY["CMD"]="ufdbguard.php?unlock-rules=yes";
    $ARRAY["TITLE"]="{unlock}::{apply}";
    $prgress=base64_encode(serialize($ARRAY));

    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-weberrorules-restart')";

    $html[] = "<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";

    $html[] = "</div>";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false>{move}</th>";
    $html[]="<th data-sortable=false>{enable}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $jsAfter="LoadAjax('table-loader-webhttp-rules','$page?table=yes&eth={$_GET["eth"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

    $results=$q->QUERY_SQL("SELECT * FROM categories_backup ORDER BY zorder");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $TRCLASS=null;

    $qs=new mysql_catz();
    $c=0;
    foreach ($results as $index=>$ligne){

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $host=$ligne["host"];
        $ID=$ligne["ID"];
        $destpath=intval($ligne["destpath"]);
        $username=$ligne["username"];
        $ftp_tls=$ligne["ftp_tls"];
        $dnsmethod=$ligne["dnsmethod"];
        $password=intval($ligne["password"]);
        $protocol=intval($ligne["protocol"]);
        $explain=array();
        $protocol_text=$GLOBALS["protocol"][$protocol];
        $method="{webfiltering_service}";
        if($dnsmethod==1){
            $method="DNS";
        }
        if($destpath<>null){
            $destpath=" {directory} $destpath";
        }

        $explain[]="{backup} {to} $host$destpath {protocol} $protocol_text {method} $method {username} $username";


        $js="Loadjs('$page?ruleid-js=$ID')";
        $final_text=utf8_encode($tpl->_ENGINE_parse_body(@implode("&nbsp;", $explain)));
       if(intval($ligne["enabled"])==1){ $c++;}


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><H3>". $tpl->td_href($ligne["rulename"],null,$js)."</H3>$final_text</td>";
        $up=$tpl->icon_up("Loadjs('$page?rule-move=$ID&rule-dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?rule-move=$ID&rule-dir=0');");
        $html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";

        $html[]="<td width=1% class='center' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-rule-js=$ID')",null,"AsDansGuardianAdministrator");

        $html[]="<td width=1% class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-rule-js=$ID&md=$md')","AsDansGuardianAdministrator") ."</center></td>";
        $html[]="</tr>";

    }

    if($c==0) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[] = "<tr class='$TRCLASS' id='null'>";
        $html[] = "<td><H3>{default}</H3>{BACKUP_WARNING_NOT_CONFIGURED_TEXT}</td>";
        $html[] = "<td width=1% class='center' nowrap>" . $tpl->icon_nothing() . "</center></td>";
        $html[] = "<td width=1% class='center' nowrap>" . $tpl->icon_nothing() . "</center></td>";
        $html[] = "<td width=1% class='center' nowrap>" . $tpl->icon_nothing() . "</center></td>";
        $html[] = "</tr>";
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
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    compile_rules();
}

function page_header($title,$ico,$explain=null,$js=null,$url=null,$force_progress=null,$searchbox=false,$forceloadingdiv=null):string{
    $t=time();$additional_progress=null;
    $tpl=new template_admin();
    $iddiv="table-$t";
    if($forceloadingdiv<>null){$iddiv=$forceloadingdiv;}
    $jsH="LoadAjax('$iddiv','$js');";
    $title_text=$tpl->javascript_parse_text($title);
    $html[]="	<div class=\"row border-bottom white-bg dashboard-header\">";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valign='top' style='padding-right: 10px;width:1%' nowrap><i class='fa-8x $ico'></i></td>";
    $html[]="<td valign='top'><div class=\"col-sm-12\"><h1 class=ng-binding>$title</h1>";
    if($explain<>null) {
        $html[] = "<p><span id='none'></span>$explain</p>";
    }
    $html[]="</div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</div>";
    $html[]="<div class='row'>";
    $html[]="<div id='progress-$t'></div>";

    if(strpos($force_progress,",")>0){
        $tb=explode(",",$force_progress);
        $force_progress=$tb[0];
        $additional_progress=$tb[1];
    }

    if($force_progress<>null){
        $html[]="<div id='$force_progress'></div>";
    }
    if($additional_progress<>null){
        $html[] = "<div id='$additional_progress'></div>";
    }

    $html[]="<div class='ibox-content' style='min-height:600px'>";
    if($searchbox){
        $jsH=null;
        $page=CurrentPageName();
        $html[]=$tpl->search_block($page,"file",null,null,null,$js);
    }else {
        $html[] = "<div id='$iddiv'></div>";
    }

    $html[]="</div>";
    $html[]="</div>";
    $html[]="<script>";
    if($url<>null) {
        $html[] = "$.address.state('/');";
        $html[] = "$.address.value('/$url');";
    }
    $html[]="$.address.title('$title_text');";
    $html[]="$jsH";
    $html[]="</script>";
    return @implode("\n",$html);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}