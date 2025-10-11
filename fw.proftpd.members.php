<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["member-js"])){zmember_js();exit;}
if(isset($_GET["member-popup"])){member_popup();exit;}
if(isset($_POST["id"])){member_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("proftpd.php?systemusers=yes");

    $html=$tpl->page_header("{APP_PROFTPD} {members}","far fa-users","{APP_PROFTPD_MEMBERS_EXPLAIN}","$page?table=yes","ftp-users","progress-proftpd-restart",false,"table-proftpd-members");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_PROFTPD} {members}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/ftpusers.db");
	$id=intval($_GET["delete-js"]);
	$ligne=$q->mysqli_fetch_array("SELECT userid FROM ftpuser WHERE id='$id'");
	$title=utf8_decode($tpl->javascript_parse_text($ligne["userid"]));
	$md=$_GET["md"];
	$tpl->js_confirm_delete("{remove} {member} $title", "delete", $id,"$('#$md').remove()");
}
function delete(){
	$id=intval($_POST["delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/ftpusers.db");
	$q->QUERY_SQL("DELETE FROM `ftpuser` WHERE id=$id");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function zmember_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$title="{new_profile}";
	$id=intval($_GET["member-js"]);
	
	if($id>0){
		$q=new lib_sqlite("/home/artica/SQLITE/ftpusers.db");
		$ligne=$q->mysqli_fetch_array("SELECT userid FROM ftpuser WHERE id='$id'");
		$title=utf8_decode($tpl->javascript_parse_text($ligne["userid"]));
	}
	
	$tpl->js_dialog1($title, "$page?member-popup=$id");
	
	
}

function member_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$btname="{add}";
    $REALUSERS=array();
	$q=new lib_sqlite("/home/artica/SQLITE/ftpusers.db");
	$title="{new_profile}";
	$id=intval($_GET["member-popup"]);
	$js[]="LoadAjax('table-proftpd-members','$page?table=yes');";
    $ligne=array();
	if($id>0){
		$btname="{apply}";
		$ligne=$q->mysqli_fetch_array("SELECT * FROM ftpuser WHERE id='$id'");
		$ligne2=$q->mysqli_fetch_array("SELECT gpid FROM radusergroup WHERE username='{$ligne["username"]}'");
		$gpid=$ligne2["gpid"];
		$title=$tpl->javascript_parse_text($ligne["userid"]);
		if(!is_numeric($gpid)){$gpid=0;}
	}else{
		$js[]="dialogInstance1.close();";
	}

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/systemlist"));
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    foreach ($json->users as $user=>$line) {
        $REALUSERS[$user]=$line;
    }

    if(isset($REALUSERS["root"])) {
        unset($REALUSERS["root"]);
    }
	$zuid="{$ligne["uid"]}:{$ligne["gid"]}";
	
	$form[]=$tpl->field_hidden("id", $id);
	$form[]=$tpl->field_text("userid", "{username}", $ligne["userid"],true);
	$form[]=$tpl->field_password2("passwd", "{password}", $ligne["passwd"]);
	$form[]=$tpl->field_array_hash($REALUSERS, "uid", "{system_user}", $zuid);
	$form[]=$tpl->field_browse_directory("homedir", "{directory}", $ligne["homedir"]);
	echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,@implode(";", $js),"AsSystemAdministrator");

}

function table(){
	$q=new lib_sqlite("/home/artica/SQLITE/ftpusers.db");
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new template_admin();
    $REALUSERS=array();

	if($users->AsSystemAdministrator){
			$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
			$btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?member-js=0');\">";
			$btn[]="<i class='fa fa-plus-square'></i> {new_profile} </label>";
			$btn[]="</div>";
	}
	
	
	$html[]="<table id='table-proftpd-users' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{system_user}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{directory}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{last_access}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{connections}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Del.</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $icon_user="<i class='fa-solid fa-user-large'></i>";
	$sql="SELECT * FROM ftpuser order by userid";
	$results = $q->QUERY_SQL($sql);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/systemlist"));
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    foreach ($json->users as $user=>$line) {
        $REALUSERS[$user]=$line;
    }


    $TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$id=$ligne["id"];
		$zuid="{$ligne["uid"]}:{$ligne["gid"]}";
		$accessed=strtotime($ligne["accessed"]);
		$requests=FormatNumber(intval($ligne["count"]));
		$accessed_time=$tpl->time_to_date($accessed,true);

		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$id&md=$md')","AsSystemAdministrator");
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% nowrap><span style='font-weight:bold'>". $tpl->td_href($icon_user."&nbsp;&nbsp;".$ligne['userid'],null,"Loadjs('$page?member-js=$id');")."</span></td>";
		$html[]="<td width=1% nowrap><span style='font-weight:bold'>". $tpl->td_href($REALUSERS[$zuid],null,"Loadjs('$page?member-js=$id');")."</span></td>";
		$html[]="<td><span style='font-weight:bold'>{$ligne["homedir"]}</span></td>";
		$html[]="<td width=1% nowrap>$accessed_time</td>";
		$html[]="<td width=1% nowrap>$requests</td>";
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


    $TINY_ARRAY["TITLE"]="{APP_PROFTPD} {members}";
    $TINY_ARRAY["ICO"]="far fa-users";
    $TINY_ARRAY["EXPL"]="{APP_PROFTPD_MEMBERS_EXPLAIN}";
    $TINY_ARRAY["URL"]="ftp-users";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-proftpd-users').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function member_save(){
	$tpl=new template_admin();
	
	$tpl->CLEAN_POST();

    foreach ($_POST as $num=>$line){
		$_POST[$num]=mysql_escape_string2($line);
	
	}
	
	$tt=explode(":",$_POST["uid"]);
	$uid=$tt[0];
	$gid=$tt[1];
	$id=$_POST["id"];
	$userid=$_POST["userid"];
	$passwd=$_POST["passwd"];
	$homedir=$_POST["homedir"];
	$date=date("Y-m-d H:i:s");
	if($id==0){
		$sql="INSERT INTO `ftpuser` ( `userid`, `passwd`, `uid`, `gid`, `homedir`, `shell`, `count`, `accessed` , `modified`, `LoginAllowed` )
		VALUES ('$userid', '$passwd', '$uid', '$gid', '$homedir', '/bin/false', '0', '$date', '$date', 'true' );";
	}else{
		$sql="UPDATE `ftpuser` SET
		`userid`='$userid',
		`passwd`='$passwd',
		`uid`=$uid,
		`gid`=$gid,
		`homedir`='$homedir' WHERE id=$id";
	}
	
	$q=new lib_sqlite("/home/artica/SQLITE/ftpusers.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."<br>$sql";return;}
	$sock=new sockets();
	$sock->getFrameWork("proftpd.php?chowndirs=yes");
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}