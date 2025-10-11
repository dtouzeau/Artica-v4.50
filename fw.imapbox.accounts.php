<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.imap-read.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["none"])){die();}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["member-js"])){zmember_js();exit;}
if(isset($_GET["member-popup"])){member_popup();exit;}
if(isset($_POST["id"])){member_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["member-tabs"])){member_tabs();exit;}
if(isset($_GET["member-mailboxes"])){member_mailboxes_start();exit;}
if(isset($_GET["member-mailboxes-table"])){member_mailboxes_table();exit;}
if(isset($_GET["mailbox-js"])){member_mailboxes_js();exit;}
if(isset($_GET["mailbox-popup"])){member_mailboxes_popup();exit;}
if(isset($_POST["mailbox-id"])){member_mailboxes_save();exit;}
if(isset($_GET["mailbox-enable"])){member_mailboxes_enable();exit;}
if(isset($_GET["enabled-js"])){member_enable();exit;}
if(isset($_GET["mailbox-delete-js"])){member_mailboxes_delete_js();exit;}
if(isset($_GET["mailbox-delete-confirm"])){member_mailboxes_delete();exit;}
if(isset($_GET["mailbox-delete-popup"])){member_mailboxes_delete_popup();exit;}
page();



function member_mailboxes_delete(){
    $mailbox_id = intval($_GET["mailbox-delete-confirm"]);
    $userid     = intval($_GET["userid"]);
    $md         = $_GET["md"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog3("{remove} {mailbox_archives} $mailbox_id","$page?mailbox-delete-popup=$mailbox_id&userid=$userid&md=$md");

}
function member_mailboxes_delete_popup(){
    $page=CurrentPageName();
    $mailbox_id = intval($_GET["mailbox-delete-popup"]);
    $userid     = intval($_GET["userid"]);
    $t          = time();
    $md         = $_GET["md"];

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/imapbox.$mailbox_id.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/imapbox.$mailbox_id.progress.log";
    $ARRAY["CMD"]="imapbox.php?remove-mailbox=$mailbox_id";
    $ARRAY["TITLE"]="{remove} {mailbox_archives} $mailbox_id";
    $ARRAY["AFTER"]="dialogInstance3.close();$('#$md').remove();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=suppr-$t')";

    $html[]="<div id='suppr-$t'></div>
    <script>$jsrestart</script>";
    $tpl        = new template_admin();
    echo $tpl->_ENGINE_parse_body($html);


}

function page(){
    $page=CurrentPageName();
    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_IMAPBOX} {members}</h1></div>
    </div>
	<div class='row'><div id='progress-imapbox-restart'></div>
	<div class='ibox-content'>
    <div id='table-imapbox-members'></div>
    </div>
	</div>
    <script>
    $.address.state('/');
    $.address.value('/imapbackup-users');
	LoadAjax('table-imapbox-members','$page?table=yes');
	</script>";

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_IMAPBOX} {members}",$html);
		echo $tpl->build_firewall();
		return;
	}


    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);

}
function member_tabs(){
    $page   = CurrentPageName();
    $tpl    =  new template_admin();
    $id     =  intval($_GET["member-tabs"]);
    $q      =   new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $ligne  =   $q->mysqli_fetch_array("SELECT userid FROM accounts WHERE id='$id'");
    $title  =   utf8_decode($tpl->javascript_parse_text($ligne["userid"]));

    $array[$title]="$page?member-popup=$id";
    $array["{mailboxes}"]="$page?member-mailboxes=$id";
    echo $tpl->tabs_default($array);

}
function member_mailboxes_delete_js(){
    $page       = CurrentPageName();
    $tpl        =  new template_admin();
    $mailbox_id =intval($_GET["mailbox-delete-js"]);
    $userid     = intval($_GET["userid"]);
    $md         = $_GET["md"];
    $tpl->js_confirm_delete("{mailbox_archives} id:$mailbox_id","none",$mailbox_id,"Loadjs('$page?mailbox-delete-confirm=$mailbox_id&userid=$userid&md=$md')");

}


function member_mailboxes_start(){
    $page   = CurrentPageName();
    $id     =  intval($_GET["member-mailboxes"]);
    echo "<div id='member-mailboxes-$id'></div>
    <script>LoadAjax('member-mailboxes-$id','$page?member-mailboxes-table=$id');</script>
    ";
}
function member_mailboxes_table(){
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $userid=intval($_GET["member-mailboxes-table"]);
    $t=time();

    if($users->AsMailBoxAdministrator){
        $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
        $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?mailbox-js=0&userid=$userid');\">";
        $html[]="<i class='fa fa-plus-square'></i> {new_mailbox} </label>";
        $html[]="</div>";
    }


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{account}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{folder}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enable}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Del.</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $sql="SELECT * FROM mailboxes WHERE account_id=$userid order by hostname ";
    $results = $q->QUERY_SQL($sql);
    $TRCLASS=null;

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $id=$ligne["id"];
        $username=$ligne["username"];
        $hostname=$ligne["hostname"];
        $account_id=$ligne["account_id"];
        $remote_folder=$ligne["remote_folder"];
        $remote_port=$ligne["remote_port"];
        $database_size=FormatBytes($ligne["database_size"]/1024);
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?mailbox-enable=$id&userid=$account_id')");

        $delete=$tpl->icon_delete("Loadjs('$page?mailbox-delete-js=$id&md=$md&userid=$userid')");
        //<i class="fas fa-user-check"></i>
        //<i class="fas fa-user"></i>

        if($remote_folder=="__ALL__"){$remote_folder="{all_folders}";}

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=1% nowrap><span style='font-weight:bold'>".
            $tpl->td_href("$hostname:$remote_port",null,"Loadjs('$page?mailbox-js=$id&userid=$userid');")."</span></td>";
        $html[]="<td width=99% nowrap><span style='font-weight:bold'>". $tpl->td_href($username,null,"Loadjs('$page?mailbox-js=$id');")."</span></td>";
        $html[]="<td width=1% nowrap><span style='font-weight:bold'>$remote_folder</span></td>";
        $html[]="<td width=1% nowrap>$database_size</td>";
        $html[]="<td width=1% nowrap>$enabled</td>";
        $html[]="<td width=1% nowrap>$delete</td>";

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
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function member_mailboxes_save(){
    $tpl=new template_admin();
    $id=intval($_POST["mailbox-id"]);
    unset($_POST["mailbox-id"]);
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");

    if(function_exists("imap_open")) {
        if (!$ImapRead = new ImapRead($_POST["hostname"], $_POST["username"], $_POST["password"], $folder = 'INBOX', $_POST["remote_port"], 'notls')) {
            echo "jserror:$ImapRead->imap_error";
            return;
        }
    }



    foreach ($_POST as $key=>$val){
        $val=$q->sqlite_escape_string2($val);
        $ADDF[]="`$key`";
        $ADDV[]="'$val'";
        $ADDM[]="`$key`='$val'";

    }

    $sqladd="INSERT INTO mailboxes (".@implode(",",$ADDF).") VALUES (".@implode(",",$ADDV).")";
    $sqleditr="UPDATE mailboxes SET ".@implode(",",$ADDM);

    if($id>0) {
        $q->QUERY_SQL($sqleditr);
        if(!$q->ok){echo "jserror:$q->mysql_error<br>$sqleditr";return false;}
        return;
    }

    $q->QUERY_SQL($sqladd);
    if(!$q->ok){echo "jserror:$q->mysql_error<br>$sqladd";}
    return true;

}

function delete_js(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $id=intval($_GET["delete-js"]);
    $ligne=$q->mysqli_fetch_array("SELECT userid FROM ftpuser WHERE id='$id'");
    $title=utf8_decode($tpl->javascript_parse_text($ligne["userid"]));
    $md=$_GET["md"];
    $tpl->js_confirm_delete("{remove} {member} $title", "delete", $id,"$('#$md').remove()");
}
function delete(){
    $id=intval($_POST["delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $q->QUERY_SQL("DELETE FROM `ftpuser` WHERE id=$id");
    if(!$q->ok){echo $q->mysql_error;return;}
}
function member_enable(){
    $id=intval($_GET["enabled-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM accounts WHERE id='$id'");
    if($ligne["enabled"]==1){
        $q->QUERY_SQL("UPDATE accounts SET enabled=0 WHERE id='$id'");
        return;
    }
    $q->QUERY_SQL("UPDATE accounts SET enabled=1 WHERE id='$id'");

}

function member_mailboxes_enable(){
    $id=intval($_GET["mailbox-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM mailboxes WHERE id='$id'");
    if($ligne["enabled"]==1){
        $q->QUERY_SQL("UPDATE mailboxes SET enabled=0 WHERE id='$id'");
        return;
    }
    $q->QUERY_SQL("UPDATE mailboxes SET enabled=1 WHERE id='$id'");
}

function member_mailboxes_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["mailbox-js"]);
    $userid=intval($_GET["userid"]);
    $title="{new_mailbox}";
    if($id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM mailboxes WHERE id='$id'");
        $title=utf8_decode($tpl->javascript_parse_text($ligne["hostname"]));

    }
    $tpl->js_dialog2($title, "$page?mailbox-popup=$id&userid=$userid");

}

function zmember_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $title="{new_profile}";
    $id=intval($_GET["member-js"]);

    if($id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
        $ligne=$q->mysqli_fetch_array("SELECT userid FROM accounts WHERE id='$id'");
        $title=utf8_decode($tpl->javascript_parse_text($ligne["userid"]));
        $tpl->js_dialog1($title, "$page?member-tabs=$id");
        return true;
    }

    $tpl->js_dialog1($title, "$page?member-popup=$id");


}

function member_mailboxes_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btname="{add}";
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $title="{new_mailbox}";
    $id=intval($_GET["mailbox-popup"]);
    $userid=intval($_GET["userid"]);


    $js[]="LoadAjax('member-mailboxes-$userid','$page?member-mailboxes-table=$userid');";

    if($id>0){
        $btname="{apply}";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM mailboxes WHERE id='$id'");
        $title=$tpl->javascript_parse_text($ligne["hostname"]);
        $userid=$ligne["account_id"];
    }else{
        $ligne["enabled"]=1;
        $ligne["remote_port"]=143;
        $js[]="dialogInstance2.close();";
    }
    $form[]=$tpl->field_hidden("mailbox-id", $id);
    $form[]=$tpl->field_hidden("account_id", $userid);
    $form[]=$tpl->field_hidden("remote_folder","__ALL__");


    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_text("hostname", "{imap_server}", $ligne["hostname"],true);
    $form[]=$tpl->field_numeric("remote_port","{imap_port}",$ligne["remote_port"]);
    $form[]=$tpl->field_text("username", "{username}", $ligne["username"],true);
    $form[]=$tpl->field_password2("password", "{password}", $ligne["password"]);
    echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,@implode(";", $js));

}

function member_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btname="{add}";
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $title="{new_profile}";
    $id=intval($_GET["member-popup"]);
    $js[]="LoadAjax('table-imapbox-members','$page?table=yes');";

    if($id>0){
        $btname="{apply}";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM accounts WHERE id='$id'");
        $title=$tpl->javascript_parse_text($ligne["userid"]);
    }else{
        $ligne["enabled"]=1;
        $js[]="dialogInstance1.close();";
    }
    $form[]=$tpl->field_hidden("id", $id);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_text("userid", "{username}", $ligne["userid"],true);
    $form[]=$tpl->field_password2("passwd", "{password}", $ligne["passwd"]);
    echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,@implode(";", $js),"AsMailBoxAdministrator");

}

function table(){
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();

    if($users->AsMailBoxAdministrator){
        $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
        $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?member-js=0');\">";
        $html[]="<i class='fa fa-plus-square'></i> {new_member} </label>";
        $html[]="</div>";
    }


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{mailboxes}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enable}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Del.</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $sql="SELECT * FROM accounts order by userid";
    $results = $q->QUERY_SQL($sql);
    $TRCLASS=null;

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $id=$ligne["id"];
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$id&md=$md')","AsSystemAdministrator");
        //<i class="fas fa-user-check"></i>
        //

        $ligne2=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM mailboxes WHERE account_id=$id");
        $mailboxes_count=FormatNumber($ligne2["tcount"]);
        $ligne2=$q->mysqli_fetch_array("SELECT SUM(database_size) as dbsize FROM mailboxes WHERE account_id=$id");
        $msize=FormatBytes($ligne2["dbsize"]/1024);
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$id')");


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=99% nowrap><i class='fas fa-user'></i>&nbsp;&nbsp;<span style='font-weight:bold'>". $tpl->td_href($ligne['userid'],null,"Loadjs('$page?member-js=$id');")."</span></td>";
        $html[]="<td width=1% nowrap>$mailboxes_count</td>";
        $html[]="<td width=1% nowrap>$msize</td>";
        $html[]="<td width=1% nowrap>$enabled</td>";
        $html[]="<td width=1% nowrap>$delete</td>";

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
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function member_save(){
    $tpl=new template_admin();
    $id=intval($_POST["id"]);
    unset($_POST["id"]);
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");

    foreach ($_POST as $key=>$val){
        $val=$q->sqlite_escape_string2($val);
        $ADDF[]="`$key`";
        $ADDV[]="'$val'";
        $ADDM[]="`$key`='$val'";

    }

    $sqladd="INSERT INTO accounts (".@implode(",",$ADDF).") VALUES (".@implode(",",$ADDV).")";
    $sqleditr="UPDATE accounts SET ".@implode(",",$ADDM);

    if($id>0) {
        $q->QUERY_SQL($sqleditr);
        if(!$q->ok){echo "jserror:$q->mysql_error";return false;}
        return;
    }

    $q->QUERY_SQL($sqladd);
    if(!$q->ok){echo "jserror:$q->mysql_error";}
    return true;

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}