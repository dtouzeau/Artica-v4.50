<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


$GLOBALS["TABLE"]["check_client_access"]="{check_client_access}";
$GLOBALS["TABLE"]["check_helo_access"]="{check_helo_access}";
$GLOBALS["TABLE"]["check_sender_access"]="{check_sender_access}";
$GLOBALS["TABLE"]["check_recipient_access"]="{check_recipient_access}";
$GLOBALS["TABLE"]["header_checks"]="{check_headers_access}";
$GLOBALS["TABLE"]["body_checks"]="{check_body_access_title}";

$GLOBALS["NOVALUE"]["IGNORE"]=true;



if(isset($_POST["rbl-choose"])){rbl_save();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-wizard"])){rule_wizard();exit;}
if(isset($_POST["rule-wizard"])){rule_wizard_save();exit;}
if(isset($_GET["rule-wizard-step-1"])){rule_wizard_step_1();exit;}
if(isset($_GET["rule-wizard-step-2"])){rule_wizard_step_2();exit;}
if(isset($_GET["rule-wizard-step-3"])){rule_wizard_step_3();exit;}
if(isset($_POST["rule-wizard-final"])){rule_wizard_save_final();exit;}
if(isset($_GET["rule-delete"])){rule_delete();exit;}
if(isset($_POST["rule-delete"])){rule_delete_perform();exit;}
if(isset($_POST["rule-id"])){rule_save();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-enable"])){rule_enable();exit;}
if(isset($_GET["table-div"])){table_div();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["table"])){table_form();exit;}
if(isset($_GET["search"])){table();exit;}

page();
function page(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $html=$tpl->page_header("{global_smtp_rules} v$POSTFIX_VERSION",
        "fas fa-filter",
        "{security_rules_explain}",
        "$page?table-div=yes&instance-id=$instance_id",
        "postfix-smtp-rules-$instance_id",
        "progress-postfix-smtp-rules",false,
        "table-loader-postfix-smtp-rules"
    );



	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}
function table_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $_SESSION["NginxTableCurpage"]=1;
    $_SESSION["NginxTableOffset"]=0;

    if(!isset($_SESSION["NginxTableMaxRecs"])){
        $_SESSION["NginxTableMaxRecs"]=10;
    }
    $websites=$tpl->_ENGINE_parse_body("{websites}");
    $max=$tpl->_ENGINE_parse_body("{maximum}");
    $t=time();
    $options["DROPDOWN"]["TITLE"]=sprintf("<span id='selector-$t'>$max %s $websites</span>",$_SESSION["NginxTableMaxRecs"]);
    $options["DROPDOWN"]["CONTENT"]["5 $websites"]="Loadjs('$page?MaxItems=5&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["10 $websites"]="Loadjs('$page?MaxItems=10&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["15 $websites"]="Loadjs('$page?MaxItems=15&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["25 $websites"]="Loadjs('$page?MaxItems=25&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["50 $websites"]="Loadjs('$page?MaxItems=50&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["100 $websites"]="Loadjs('$page?MaxItems=100&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["150 $websites"]="Loadjs('$page?MaxItems=150&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["200 $websites"]="Loadjs('$page?MaxItems=200&id=selector-$t&function=%s')";
    $options["DROPDOWN"]["CONTENT"]["250 $websites"]="Loadjs('$page?MaxItems=250&id=selector-$t&function=%s')";
    $options=array();

    $instanceid=$_GET["instance-id"];

    echo $tpl->search_block($page,null,null,null,"&instance-id=$instanceid",$options);
    return true;
}



function rule_move_js(){
    $instance_id=intval($_GET["instance-id"]);
	header("content-type: application/x-javascript");
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$tpl=new template_admin();
	$dir=$_GET["dir"];
	$aclid=intval($_GET["id"]);
	$results=$q->QUERY_SQL("SELECT zorder FROM smtp_rules WHERE id='$aclid'");
	$ligne=$results[0];
	$zorder=intval($ligne["zorder"]);
	echo "// Current order = $zorder\n";

	if($dir=="up"){
		$zorder=$zorder-1;
		if($zorder<0){$zorder=0;}
	}
	else{
		$zorder=$zorder+1;
	}
	echo "// New order = $zorder\n";
	$q->QUERY_SQL("UPDATE smtp_rules SET zorder='$zorder' WHERE id='$aclid'");
	if(!$q->ok){$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "alert('$q->mysql_error');";return;}


    if($dir=="up"){
        $zorder2=$zorder+1;
        if($zorder2<0){$zorder2=0;}
        $sql="UPDATE smtp_rules SET zorder=$zorder2 WHERE `id`<>'$aclid' AND zorder=$zorder";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    }
    if($dir=="down"){
        $zorder2=$zorder-1;
        if($zorder2<0){$zorder2=0;}
        $sql="UPDATE smtp_rules SET zorder=$zorder2 WHERE `id`<>'$aclid' AND zorder=$zorder";
        $q->QUERY_SQL($sql);
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
        if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    }


	$c=0;
	$results=$q->QUERY_SQL("SELECT id FROM smtp_rules WHERE instanceid=$instance_id ORDER BY zorder");
	foreach ($results as $index=>$ligne){
		$aclid=$ligne["id"];
		echo "// $aclid New order = $c";
		$q->QUERY_SQL("UPDATE smtp_rules SET zorder='$c' WHERE id='$aclid'");
		$c++;
	}
}


function table_div(){
	$page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);
	echo "<div id='postfix-smtp-rules-div' style='margin-top:20px'></div><script>LoadAjax('postfix-smtp-rules-div','$page?table=yes&instance-id=$instance_id');</script>";
}

function rule_delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["rule-delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$results=$q->QUERY_SQL("SELECT rulename from smtp_rules WHERE id='$id'");
	$rulename=$results[0]["rulename"];
	$tpl->js_confirm_delete($rulename, "rule-delete",$id,"$('#{$_GET["id"]}').remove()");
}
function rule_delete_perform():bool{
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$id=$_POST["rule-delete"];


    $ligne=$q->mysqli_fetch_array("SELECT instanceid FROM smtp_rules WHERE id=$id");
    $instanceid=intval($ligne["instanceid"]);


	$q->QUERY_SQL("DELETE FROM smtp_rules WHERE id='$id'");
	if(!$q->ok){echo $q->mysql_error;return false;}

    $sock=new sockets();
    $sock->REST_API("/postfix/smtpd/restrictions/$instanceid");
    return admin_tracks_post("Delete SMTP rule $id for SMTP instance id $instanceid");
}

function rule_wizard(){
	$page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);
    $function=$_GET["function"];
	echo "<div id='smtp-rule-wizard'></div><script>LoadAjax('smtp-rule-wizard','$page?rule-wizard-step-1=yes&instance-id=$instance_id&function=$function');</script>";
	
}

function rule_wizard_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	foreach ($_POST as $key=>$val){
		$_SESSION["SMTP_WIZARD"][$key]=$val;
	}
}

function rule_wizard_step_2(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ruletype=$_SESSION["SMTP_WIZARD"]["ruletype"];
	$ACTION_VALUE_IMPORTANT=true;
    $instance_id=intval($_GET["instance-id"]);
    $function=$_GET["function"];

	$CheckActions["ACCEPT"]="<strong style='font-size:16px'>{ACCEPT}:</strong> <p style='padding-left:30px'>{POSTFIX_REGEX_ACCEPT}</p>";
	$CheckActions["REJECT"]="<strong style='font-size:16px'>{REJECT}:</strong> <p style='padding-left:30px'>{POSTFIX_REGEX_REJECT}</p>";
	$CheckActions["BCC"]="<strong style='font-size:16px'>{BCC}:</strong> <p style='padding-left:30px'>{BCC_help}</p>";
	$CheckActions["DISCARD"]="<strong style='font-size:16px'>{DISCARD}:</strong> <p style='padding-left:30px'>{POSTFIX_REGEX_DISCARD}</p>";
	$CheckActions["HOLD"]="<strong style='font-size:16px'>{HOLD}:</strong> <p style='padding-left:30px'>{POSTFIX_REGEX_HOLD}</p>";
	$CheckActions["PREPEND"]="<strong style='font-size:16px'>{PREPEND}:</strong> <p style='padding-left:30px'>{POSTFIX_REGEX_PREPEND}</p>";
	$CheckActions["REDIRECT"]="<strong style='font-size:16px'>{REDIRECT}:</strong> <p style='padding-left:30px'>{POSTFIX_REGEX_REDIRECT}</p>";
	$CheckActions["INFO"]="<strong style='font-size:16px'>{INFO}:</strong> <p style='padding-left:30px'>{POSTFIX_REGEX_INFO}</p>";
	
	if($ruletype=="header_checks"){
		$CheckActions["IGNORE"]="<strong style='font-size:16px'>{ignore}:</strong> <p style='padding-left:30px'>{POSTFIX_REGEX_IGNORE}</p>";
		$ACTION_VALUE_IMPORTANT=false;
	}


    $form[]=$tpl->field_hidden("instance_id", $instance_id);
	$form[]=$tpl->field_hidden("rule-wizard", "yes");
	$form[]=$tpl->field_array_checkboxes($CheckActions, "ACTION", $_SESSION["SMTP_WIZARD"]["ACTION"],true);
	$form[]=$tpl->field_text("ACTION_VALUE", "{action_value}",  $_SESSION["SMTP_WIZARD"]["ACTION_VALUE"],$ACTION_VALUE_IMPORTANT);
	echo $tpl->form_outside("{new_rule} &raquo; {$_SESSION["SMTP_WIZARD"]["rulename"]} &raquo; {{$_SESSION["SMTP_WIZARD"]["ruletype"]}} &raquo; {ACTION_ON_MESSAGE}",$form,"{SMTP_RULE_WIZARD_STEP2}","{next}","LoadAjax('smtp-rule-wizard','$page?rule-wizard-step-3=yes&instance-id=$instance_id&function=$function');");
	
}

function rule_wizard_step_3(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	$ruletype=$_SESSION["SMTP_WIZARD"]["ruletype"];
    $function=$_GET["function"];

    $form[]=$tpl->field_hidden("instance_id", $instance_id);
	$form[]=$tpl->field_hidden("rule-wizard-final", "yes");
	$form[]=$tpl->field_textareacode("ITEMS", null, null);

    $titlez[]="{new_rule} &raquo; {$_SESSION["SMTP_WIZARD"]["rulename"]}";
	$titlez[]="&raquo; {{$_SESSION["SMTP_WIZARD"]["ruletype"]}}";
	$titlez[]="&raquo; {{$_SESSION["SMTP_WIZARD"]["ACTION"]}}";
	if(!isset($GLOBALS["NOVALUE"][$_SESSION["SMTP_WIZARD"]["ACTION"]])){
		$titlez[]="{$_SESSION["SMTP_WIZARD"]["ACTION_VALUE"]} &raquo; {pattern_action}";
	}
	$title=@implode(" ", $titlez);
	$help="{pattern_action_help}";
	
	if($ruletype=="header_checks"){
		$help="{header_checks_help}";
		
		
	}
    if($ruletype=="body_checks"){$help="{SimpleWords_explain_add}";}
    echo $tpl->form_outside($title,$form,$help,"{next}","dialogInstance1.close();$function()");
}

function rule_wizard_save_final(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	
	if(!$q->IF_TABLE_EXISTS("smtp_rules")){
		$sql="CREATE TABLE smtp_rules (id INTEGER PRIMARY KEY AUTOINCREMENT ,
		rulename TEXT NOT NULL,ruletype TEXT NOT NULL,
		instanceid INTEGER NOT NULL DEFAULT 0,
		action TEXT NOT NULL,items TEXT,zorder INTEGER, zdate DATETIME, enabled INTEGER);";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		@chmod("/home/artica/SQLITE/postfix.db", 0777);
	}
	$rulename=$q->sqlite_escape_string2($_SESSION["SMTP_WIZARD"]["rulename"]);
	$ruletype=$_SESSION["SMTP_WIZARD"]["ruletype"];
	$action=$_SESSION["SMTP_WIZARD"]["ACTION"];
    if(!isset($_SESSION["SMTP_WIZARD"]["instance_id"])){

        echo "No instance ID set!";
        return false;
    }

    $instance_id=$_SESSION["SMTP_WIZARD"]["instance_id"];
	$action_value=$q->sqlite_escape_string2($_SESSION["SMTP_WIZARD"]["ACTION_VALUE"]);
	$items=$q->sqlite_escape_string2($_POST["ITEMS"]);
	$zdate=date("Y-m-d H:i:s");
	
	$results=$q->QUERY_SQL("SELECT zorder smtp_rules WHERE instanceid=$instance_id ORDER by zorder DESC LIMIT 1");
	$order=intval($results[0]["zorder"])+1;
	
	$sql="INSERT INTO smtp_rules (rulename,ruletype,action,action_value,items,zdate,enabled,zorder,instanceid) 
    VALUES ('$rulename','$ruletype','$action','$action_value','$items','$zdate',1,$order,$instance_id)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $sql."<br>$q->mysql_error";}


    $sock=new sockets();
    $sock->REST_API("/postfix/smtpd/restrictions/$instance_id");
    return admin_tracks_post("Create new SMTP rule for SMTP instance id $instance_id");
	
	
}

function rule_wizard_step_1(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $function=$_GET["function"];
	$GLOBALS["TABLE"]["check_client_access"]="<strong style='font-size:16px'>{check_client_access}</strong><p style='padding-left:30px'><small>{check_client_access_text}</small></p>";
	$GLOBALS["TABLE"]["check_helo_access"]="<strong style='font-size:16px'>{check_helo_access}</strong><p style='padding-left:30px'><small>{check_helo_access_text}</small></p>";
	$GLOBALS["TABLE"]["check_sender_access"]="<strong style='font-size:16px'>{check_sender_access}</strong><p style='padding-left:30px'><small>{check_sender_access_text}</small></p>";
	$GLOBALS["TABLE"]["check_recipient_access"]="<strong style='font-size:16px'>{check_recipient_access}</strong><p style='padding-left:30px'><small>{check_recipient_access_text}</small></p";
	$GLOBALS["TABLE"]["header_checks"]="<strong style='font-size:16px'>{check_headers_access}</strong><p style='padding-left:30px'><small>{check_headers_access_text}</small></p>";
    $GLOBALS["TABLE"]["body_checks"]="<strong style='font-size:16px'>{body_checks_title}</strong><p style='padding-left:30px'><small>{body_checks_text}</small></p>";

    $form[]=$tpl->field_hidden("instance_id", $instance_id);
	$form[]=$tpl->field_hidden("rule-wizard", "yes");
	$form[]=$tpl->field_text("rulename", "{rulename}", $_SESSION["SMTP_WIZARD"]["rulename"],true);
	$form[]=$tpl->field_array_checkboxes($GLOBALS["TABLE"], "ruletype", $_SESSION["SMTP_WIZARD"]["ruletype"],true);
	echo $tpl->form_outside("{new_rule}", $form,null,"{next}","LoadAjax('smtp-rule-wizard','$page?rule-wizard-step-2=yes&instance-id=$instance_id&function=$function');","AsPostfixAdministrator");
	
}

function rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$ruleid=$_GET["rule-popup"];
    $function=$_GET["function"];
	$results=$q->QUERY_SQL("SELECT * from smtp_rules WHERE id='$ruleid'");
	$ligne=$results[0];
	
	$ligne["items"]=str_replace("\\n", "\n", $ligne["items"]);

    $form[]=$tpl->field_hidden("instance_id", $instance_id);
    $form[]=$tpl->field_hidden("rule-id", "$ruleid");
	$form[]=$tpl->field_text("rulename", "{rulename}", $ligne["rulename"],true);
	
	if(!isset($GLOBALS["NOVALUE"][$ligne["action"]])){
		$form[]=$tpl->field_text("action_value", "{action_value}",  $ligne["action_value"],true);
	}else{
		$tpl->field_hidden("action_value", "");
	}
	$form[]=$tpl->field_textareacode("items", "{items}", $ligne["items"],"{pattern_action_help}");
	echo $tpl->form_outside("{{$ligne["ruletype"]}} &raquo; <strong>{{$ligne["action"]}}</strong> ",$form,null,"{apply}","dialogInstance1.close();$function()");
	
	
}

function rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	if(!$q->IF_TABLE_EXISTS("smtp_rules")){
		$sql="CREATE TABLE smtp_rules (id INTEGER PRIMARY KEY AUTOINCREMENT ,
		rulename TEXT NOT NULL,ruletype TEXT NOT NULL,action TEXT NOT NULL,items TEXT,zorder INTEGER, zdate DATETIME, enabled INTEGER);";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		@chmod("/home/artica/SQLITE/postfix.db", 0777);
	}
	$rulename=$q->sqlite_escape_string2($_POST["rulename"]);
	$action_value=$q->sqlite_escape_string2($_POST["action_value"]);
	$items=$q->sqlite_escape_string2($_POST["items"]);
	$id=$_POST["rule-id"];
	$sql="UPDATE smtp_rules SET rulename='$rulename',action_value='$action_value',items='$items' WHERE id='$id'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();

    $ligne=$q->mysqli_fetch_array("SELECT instanceid FROM smtp_rules WHERE id=$id");
    $instanceid=intval($ligne["instanceid"]);
    $sock->REST_API("/postfix/smtpd/restrictions/$instanceid");
    return admin_tracks_post("Save SMTP rule for SMTP instance id $instanceid");
}

function rule_js(){
	$sock=new sockets();
    $instance_id=intval($_GET["instance-id"]);
    $function=$_GET["function"];
	$page=CurrentPageName();
	$title="{new_rule}";
	$tpl=new template_admin();
	$ruleid=$_GET["rule-js"];
	if($ruleid==0){
		$tpl->js_dialog1("{new_rule}", "$page?rule-wizard=yes&instance-id=$instance_id&function=$function");
		return;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$results=$q->QUERY_SQL("SELECT rulename from smtp_rules WHERE id='$ruleid'");
	$rulename=$results[0]["rulename"];
	
	$tpl->js_dialog1("$rulename", "$page?rule-popup=$ruleid&instance-id=$instance_id&function=$function");
}
function rule_enable(){
	$rbl=$_GET["rule-enable"];
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$results=$q->QUERY_SQL("SELECT enabled from smtp_rules WHERE id='$rbl'");
	$enabled=intval($results[0]["enabled"]);
    $setEnable=0;
    if($enabled==1){$setEnable=0;}
    if($enabled==0){$setEnable=1;}

	//if($enabled==1){$q->QUERY_SQL("UPDATE smtp_rules SET enabled=0 WHERE id='$rbl'");return;}
	$q->QUERY_SQL("UPDATE smtp_rules SET enabled=$setEnable WHERE id='$rbl'");

    $ligne=$q->mysqli_fetch_array("SELECT instanceid FROM smtp_rules WHERE id=$rbl");
    $instanceid=intval($ligne["instanceid"]);
    $sock=new sockets();
    $sock->REST_API("/postfix/smtpd/restrictions/$instanceid");
    return admin_tracks_post("Enable/Disable SMTP rule for SMTP instance id $instanceid");

}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $function=$_GET["function"];
    $ARRAY["CMD"]="postfix.php?smtpd-client-restrictions=yes&instance-id=$instance_id";

	$reconfigure="Loadjs('fw.postfix.articarest.php?smtpd-client-restrictions=yes&instance-id=$instance_id')";
	

	$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0&instance-id=$instance_id&function=$function')\">";
    $btn[]="<i class='fa fa-plus'></i> {new_rule} </label>";



    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"$reconfigure;\">";
    $btn[]="<i class='fa fa-save'></i> {apply_configuration} </label>";

    $btn[]="</div>";
	$html[]="<table id='table-postfix-smtp-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{rules}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{enabled}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{order}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

    $sql="CREATE TABLE IF NOT EXISTS `smtp_rules` (
        id INTEGER PRIMARY KEY AUTOINCREMENT , 
        rulename TEXT NOT NULL,
        ruletype TEXT NOT NULL,
        instanceid INTEGER NOT NULL DEFAULT 1,
        action TEXT NOT NULL,action_value TEXT,
        items TEXT,zorder INTEGER, 
        zdate DATETIME, 
        enabled INTEGER
    )";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error("{error}|$q->mysql_error");
        return false;
    }


    if(!$q->TABLE_EXISTS("smtp_rules")){
        echo $tpl->div_error("{error}||smtp_rules no such table");
        return false;
    }

    if(!$q->FIELD_EXISTS("smtp_rules","instanceid")){
        $q->QUERY_SQL("ALTER TABLE smtp_rules ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }
    $sql="SELECT * FROM smtp_rules WHERE instanceid=$instance_id ORDER by zorder";
    $search=trim($_GET["search"]);
    if(strlen($search)>2){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT * FROM smtp_rules WHERE (rulename LIKE '$search' OR items LIKE '$search') AND instanceid=$instance_id ORDER by zorder";

    }

	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
	    echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);
	    return false;
	}
	
	$CheckActions["ACCEPT"]="-primary";
	$CheckActions["REJECT"]="-danger";
	$CheckActions["BCC"]="-success";
	$CheckActions["DISCARD"]="-danger";
	$CheckActions["HOLD"]="-warning";
	$CheckActions["PREPEND"]="-success";
	$CheckActions["REDIRECT"]="-success";
	$CheckActions["INFO"]="-success";
	$CheckActions["IGNORE"]="-primary";
	
	
	
	
	foreach ($results as $num=>$ligne){
		$id=$ligne["id"];
		$rulename=$ligne["rulename"];
		$ruletype=$ligne["ruletype"];
		$action=$ligne["action"];
		$action_value=$ligne["action_value"];
		$ligne["items"]=str_replace("\\n", "\n", $ligne["items"]);
		$items_array=explode("\n",$ligne["items"]);
		$items_count=count($items_array);
		$explain="<strong>".$tpl->td_href($rulename,"{click_to_edit}","Loadjs('$page?rule-js=$id&instance-id=$instance_id&function=$function')")."</strong>
		<br><small> <strong>{{$ruletype}}</strong> {for} $items_count {elements} {then} <strong>{{$action}}</strong>";
		if(!isset($GLOBALS["NOVALUE"][$action])){$explain=$explain."  &laquo;$action_value&raquo;";}
		
		
		$iddiv=md5(serialize($ligne));
		$up=$tpl->icon_up("Loadjs('$page?move-js=yes&id=$id&dir=up&instance-id=$instance_id')");
		$down=$tpl->icon_down("Loadjs('$page?move-js=yes&id=$id&dir=down&instance-id=$instance_id')");
		
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}	
		$html[]="<tr class='$TRCLASS' id='$iddiv'>";
		$html[]="<td style='width:1%' nowrap><span class='label label{$CheckActions[$action]}'>$action</span></td>";
		$html[]="<td nowrap>$explain</td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?rule-enable=$id&instance-id=$instance_id')",null,"AsPostfixAdministrator")."</center></td>";
		$html[]="<td class=\"center\" style='width:1%' nowrap>$up&nbsp;&nbsp;$down</td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?rule-delete=$id&id=$iddiv&instance-id=$instance_id')","AsPostfixAdministrator")."</center></td>";
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

    $instancename="SMTP Master";
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename="&nbsp;<small>({$ligne["instancename"]})</small>";
    }

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{global_smtp_rules} v$POSTFIX_VERSION $instancename";
    $TINY_ARRAY["ICO"]="fas fa-filter";
    $TINY_ARRAY["EXPL"]="{security_rules_explain}";
    $TINY_ARRAY["URL"]="postfix-smtp-rule-$instance_id";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-postfix-smtp-rules').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}


