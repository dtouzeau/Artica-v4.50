<?php

$GLOBALS["ACTIONSAV"][0]="{use_default}";
$GLOBALS["ACTIONSAV"][1]="{modify_urls_in_message}";
$GLOBALS["ACTIONSAV"][2]="{do_nothing}";



include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_POST["EnableMimedefangUrlsChecker"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["rules"])){rules();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_POST["zmd5"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete"])){rule_delete_perform();exit;}
if(isset($_GET["links"])){links();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["logs-js"])){logs_js();exit;}
if(isset($_GET["logs-popup"])){logs_popup();exit;}
if(isset($_GET["remove-message-js"])){remove_message_js();exit;}
if(isset($_POST["remove_messsage"])){remove_messsage_perform();exit;}
if(isset($_GET["run-task-js"])){run_task_js();exit;}
if(isset($_POST["run-task"])){run_task();exit;}
page();



function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $q=new postgres_sql();
    //$q->QUERY_SQL("DROP TABLE mimedefang_urls");
    $q->SMTP_TABLES();


    $array["{parameters}"]="$page?parameters=yes";
    $array["{rules}"]="$page?rules=yes";
    $array["{links}"]="$page?links=yes";

    echo $tpl->tabs_default($array);

}

function run_task_js(){
    $msgid=$_GET["run-task-js"];
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog_confirm_action("{analyze_this_message} $msgid ?","run-task","$msgid","$function");


}

function run_task(){
    $msgid=$_POST["run-task"];
    $now=time()-10;
    $q=new postgres_sql();
    $sql="UPDATE mimedefang_urls SET ttlmin='$now' WHERE msgid='$msgid'";
    $q->QUERY_SQL($sql);
    $sock=new sockets();
    $sock->getFrameWork("mimedefang.php?urlscan=yes");
}

function logs_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["logs-js"];
    $tpl->js_dialog1($id,"$page?logs-popup=$id");

}
function remove_message_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["remove-message-js"];
    $function=$_GET["function"];
    $tpl->js_confirm_delete("{message} $id","remove_messsage",$id,"$function()");

}
function remove_messsage_perform(){
    $q=new postgres_sql();
    $id=$_POST["remove_messsage"];
    $q->QUERY_SQL("DELETE FROM mimedefang_urls WHERE msgid='$id'");
    $q->QUERY_SQL("DELETE FROM mimedefang_msgurls WHERE msgid='$id'");

}

function logs_popup(){
    $id=$_GET["logs-popup"];
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT log from mimedefang_urls WHERE id=$id");

    $logs=unserialize(base64_decode($ligne["log"]));
    foreach ($logs as $xline){
        $html[]="<div>$xline</div>";


    }

    echo "<div>".@implode("\n",$html)."</div>";

}

function links(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,"postgres","mimedefang_urls");
}

function search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $q=new postgres_sql();
    $table="mimedefang_urls";
    $t=time();

    $search=$_GET["search"];
    $querys=$tpl->query_pattern($search);
    $MAX=$querys["MAX"];
    if($MAX==0){$MAX=150;}

    $sql="SELECT * FROM $table  {$querys["Q"]} LIMIT $MAX";
    //(id,zdate,instance,method,type,pattern,description)


    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}


    $TRCLASS=null;
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{message}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{task}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sender}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{url}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Content-Type</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ttl_max}</center></th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $md=md5(serialize($ligne));
        $id=$ligne["id"];
        $zdate=$tpl->time_to_date(strtotime($ligne["zdate"]),true);
        $ttl_max=$ligne["ttlmax"];
        $ttl_min=intval($ligne["ttlmin"]);
        $next=$tpl->icon_nothing();
        if($ttl_min>0){$next=date("Y-m-d H:i:s",$ttl_min);}
        $message_id=$ligne["msgid"];
        $id=$ligne["id"];
        $sender=$ligne["sender"];
        $sender_text=$sender;
        if(strlen($sender_text)>30){$sender_text=substr($sender_text,0,27)."...";}
        $content_type=$ligne["content_type"];
        $url=$ligne["urlsource"];
        $urldest=$ligne["urldest"];
        if(strlen($urldest)>3){$url=$urldest;}
        $url_text=$url;
        $scanned=$ligne["scanned"];
        $phishing=$ligne["phishing"];

        $icon=null;
        $label=null;

        if($scanned==0){
           $icon = "<i class=\"fas fa-clock\"></i>";
           $label = "<span class=label>$icon&nbsp;{waiting}</span>";

        }else{

            $icon = "<i class=\"fas fa-thumbs-up\"></i>";
            $label = "<span class='label label-primary'>$icon&nbsp;OK</span>";

            if($phishing==1){
                $icon = "<i class=\"fas fa-bug\"></i>";
                $label = "<span class='label label-danger'>$icon&nbsp;Phishing!</span>";
            }
        }

        if(strlen($url_text)>50){$url_text=substr($url_text,0,47)."...";}

        $js="Loadjs('$page?logs-js=$id');";
        $Runjs="Loadjs('$page?run-task-js=$message_id&function={$_GET["function"]}')";
        $js_msgid="Loadjs('fw.postfix.transactions.php?second-query=".base64_encode("WHERE msgid='$message_id'")."&title=".urlencode("{see_messages_with}:$message_id")."')";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'>".$tpl->td_href($id,"{view2}",$js)."</td>";
        $html[]="<td style='width:1%' nowrap>$label</td>";
        $html[]="<td style='width:1%'>".$tpl->td_href($message_id,"{view2}",$js_msgid)."</td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('$page?remove-message-js=$message_id&function={$_GET["function"]}')")."</td>";
        $html[]="<td style='width:1%' nowrap>$zdate</td>";
        $html[]="<td style='width:1%' nowrap>$next</td>";
        $html[]="<td style='width:1%' nowrap class='center'>".$tpl->icon_run($Runjs)."</td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->td_href($sender_text,$sender,$js)."</td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->td_href($url_text,$url,$js)."</td>";
        $html[]="<td style='width:1%' nowrap>$content_type</td>";
        $html[]="<td style='width:1%' nowrap>". date("Y-m-d H:i:s",$ttl_max)."</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<small>$sql</small>
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function rules(){

    $page=CurrentPageName();
    echo "<div id='mimedefang-urls-rules-table' style='margin-top:20px'></div><script>LoadAjax('mimedefang-urls-rules-table','$page?table=yes');</script>";


}
function rule_delete_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md5=$_GET["delete-rule-js"];
    $q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM mimedefang_urls WHERE zmd5='$md5'");
    $mailfrom=$ligne["mailfrom"];
    $mailto=$ligne["mailto"];
    $type=$ligne["type"];
    $type_text=$tpl->javascript_parse_text($GLOBALS["ACTIONSAV"][$type]);
    $title="$mailfrom > $mailto $type_text ($type)";
    $tpl->js_confirm_delete($title, "delete", $md5,"$('#$md5').remove()");
}
function rule_delete_perform(){
    $md5=$_POST["delete"];
    $q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
    $q->QUERY_SQL("DELETE FROM mimedefang_urls WHERE zmd5='$md5'");
    if(!$q->ok){echo $q->mysql_error;}

}

function rule_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md5=$_GET["rule-js"];
    $title="{new_rule}";

    if($md5<>null){
        $q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM mimedefang_urls WHERE zmd5='$md5'");
        $mailfrom=$ligne["mailfrom"];
        $mailto=$ligne["mailto"];
        $type=$ligne["type"];
        $type_text=$tpl->javascript_parse_text($GLOBALS["ACTIONSAV"][$type]);
        $title="$mailfrom > $mailto $type_text ($type)";
    }

    $tpl->js_dialog1($title, "$page?popup=$md5");

}
function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md5=$_GET["popup"];
    $title="{new_rule}";
    $bt="{add}";
    if($md5<>null){
        $bt="{apply}";
        $q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM mimedefang_urls WHERE zmd5='$md5'");
        $mailfrom=$ligne["mailfrom"];
        $mailto=$ligne["mailto"];
        $type=$ligne["type"];
        $type_text=$tpl->javascript_parse_text($GLOBALS["ACTIONSAV"][$type]);
        $title="$mailfrom > $mailto $type_text";
    }

    $js="dialogInstance1.close();LoadAjax('mimedefang-urls-rules-table','$page?table=yes');";

    $tpl->field_hidden("zmd5", $md5);
    $form[]=$tpl->field_text("mailfrom", "{sender}", $mailfrom);
    $form[]=$tpl->field_text("mailto", "{recipient}", $mailto);
    $form[]=$tpl->field_array_hash($GLOBALS["ACTIONSAV"], "ztype", "{action}", $type);

    echo $tpl->form_outside($title, $form,"{mimedefang_email_explain}",$bt,$js,"AsPostfixAdministrator",true);
}
function rule_save(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
    $zmd5=$_POST["zmd5"];
    if($zmd5==null){
        $zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
        $ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM mimedefang_urls WHERE zmd5='$zmd5'");
        if($ligne["zmd5"]<>null){echo "{$_POST["mailfrom"]} --> {$_POST["mailto"]} Already exists\n";return;}
        $q->QUERY_SQL("INSERT INTO mimedefang_urls(zmd5,mailfrom,mailto,`type`) VALUES ('$zmd5','{$_POST["mailfrom"]}','{$_POST["mailto"]}','{$_POST["ztype"]}')");
        if(!$q->ok){echo $q->mysql_error;}
        return;
    }

    $new_zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
    if($new_zmd5==$zmd5){
        $q->QUERY_SQL("UPDATE mimedefang_urls SET `type`='{$_POST["ztype"]}' WHERE zmd5='$zmd5'");
        if(!$q->ok){echo $q->mysql_error;}
        return;
    }

    $ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM mimedefang_urls WHERE zmd5='$new_zmd5'");
    if($ligne["zmd5"]<>null){echo "{$_POST["mailfrom"]} --> {$_POST["mailto"]} Already exists\n";return;}

    $q->QUERY_SQL("DELETE FROM mimedefang_urls WHERE zmd5='$zmd5'");
    $q->QUERY_SQL("INSERT INTO mimedefang_urls(zmd5,mailfrom,mailto,`type`) VALUES ('$new_zmd5','{$_POST["mailfrom"]}','{$_POST["mailto"]}','{$_POST["ztype"]}')");
    if(!$q->ok){echo $q->mysql_error;}

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{url_filtering}</h1>
	<p>{url_filtering_smtp_explain}</p>
	</div>

	</div>



	<div class='row'><div id='progress-mimedfurls-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-urls-rules'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/smtp-url-filtering');	
	LoadAjax('table-loader-urls-rules','$page?tabs=yes');

	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{url_filtering} ",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $sock=new sockets();
    $EnableMimedefangUrlsChecker=intval($sock->GET_INFO("EnableMimedefangUrlsChecker"));
    $MimedefangUrlsCheckerHostname=trim($sock->GET_INFO("MimedefangUrlsCheckerHostname"));
    $MimedefangUrlsCheckerTimeOut=intval($sock->GET_INFO("MimedefangUrlsCheckerTimeOut"));
    $MimedefangPhishingInitiativeAPIKey=trim($sock->GET_INFO("MimedefangPhishingInitiativeAPIKey"));
    $MimedefangUrlsCheckerTrustAutoWhitelist=intval($sock->GET_INFO("MimedefangUrlsCheckerTrustAutoWhitelist"));
    $MimedefangUrlsCheckerMaxTTL=intval($sock->GET_INFO("MimedefangUrlsCheckerMaxTTL"));
    $MimedefangUrlsCheckerMinTTL=intval($sock->GET_INFO("MimedefangUrlsCheckerMinTTL"));
    $MimedefangVirusTotalAPIKey=trim($sock->GET_INFO("MimedefangVirusTotalAPIKey"));
    if($MimedefangUrlsCheckerHostname==null){$MimedefangUrlsCheckerHostname=$_SERVER["SERVER_NAME"];}
    if($MimedefangUrlsCheckerTimeOut==0){$MimedefangUrlsCheckerTimeOut=5;}

    $TIMES1[0]="{default}";
    $TIMES1[1]="1 {minute}";
    $TIMES1[10]="10 {minutes}";
    $TIMES1[30]="30 {minutes}";
    $TIMES1[60]="1 {hour}";
    $TIMES1[120]="2 {hours}";
    $TIMES1[240]="4 {hours}";

    $TIMES[0]="{default}";
    $TIMES[30]="30 {minutes}";
    $TIMES[60]="1 {hour}";
    $TIMES[120]="2 {hours}";
    $TIMES[180]="3 {hours}";
    $TIMES[240]="4 {hours}";
    $TIMES[480]="8 {hours}";
    $TIMES[960]="16 {hours}";
    $TIMES[1440]="1 {day}";
    $TIMES[2880]="2 {days}";
    $TIMES[10080]="1 {week}";
    $TIMES[20160]="2 {weeks}";
    $TIMES[43200]="1 {month}";
    $TIMES[129600]="3 {months}";

    if($MimedefangUrlsCheckerMinTTL==0){$MimedefangUrlsCheckerMinTTL=30;}
    if($MimedefangUrlsCheckerMaxTTL==0){$MimedefangUrlsCheckerMaxTTL=604800;}
    $MimeDefangAutoWhiteList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangAutoWhiteList"));


    $form[]=$tpl->field_checkbox("EnableMimedefangUrlsChecker","{enable_feature}",$EnableMimedefangUrlsChecker);
    if($MimeDefangAutoWhiteList==1) {
        $form[] = $tpl->field_checkbox("MimedefangUrlsCheckerTrustAutoWhitelist", "{trust_auto_whitelist}", $MimedefangUrlsCheckerTrustAutoWhitelist);
    }else{
        $form[] = $tpl->field_hidden("MimedefangUrlsCheckerTrustAutoWhitelist",0);
    }
    $form[]=$tpl->field_numeric("MimedefangUrlsCheckerTimeOut","{timeout} HTTP (<small>{seconds}</small>)",$MimedefangUrlsCheckerTimeOut);

    $form[]=$tpl->field_text("MimedefangUrlsCheckerHostname","{sitename}","$MimedefangUrlsCheckerHostname");
    $form[]=$tpl->field_array_hash($TIMES1,"MimedefangUrlsCheckerMinTTL","{min_ttl}",$MimedefangUrlsCheckerMinTTL);
    $form[]=$tpl->field_array_hash($TIMES,"MimedefangUrlsCheckerMaxTTL","{max_ttl}",$MimedefangUrlsCheckerMaxTTL);
    $form[]=$tpl->field_text("MimedefangPhishingInitiativeAPIKey","phishing-initiative.fr {API_KEY}","$MimedefangPhishingInitiativeAPIKey");
    $form[]=$tpl->field_text("MimedefangVirusTotalAPIKey","VirusTotal {API_KEY}","$MimedefangVirusTotalAPIKey");



    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress.log";


    $ARRAY["CMD"]="mimedefang.php?reload=yes";
    $ARRAY["TITLE"]="{apply_parameters}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-mimedfurls-restart')";


    $html=$tpl->form_outside("{parameters}",$form,null,"{apply}",$jsrestart,"AsPostfixAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}


function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");

    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `mimedefang_urls` ( `zmd5` TEXT PRIMARY KEY NOT NULL,  `mailfrom` TEXT NOT NULL, `mailto` TEXT NOT NULL,`type` INTEGER )");
    if(!$q->ok){ echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return; }
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS mailfrom ON mimedefang_urls (mailfrom)");
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS mailto ON mimedefang_urls (mailto)");


    $t=time();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress.log";


    $ARRAY["CMD"]="mimedefang.php?reload=yes";
    $ARRAY["TITLE"]="{apply_parameters}";
    $prgress=base64_encode(serialize($ARRAY));
    $add="Loadjs('$page?rule-js=',true);";

    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-mimedfurls-restart')";

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart;\"><i class='fa fa-save'></i> {apply_rules} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{sender}</th>";
    $html[]="<th data-sortable=true data-type='text'>{recipients}</th>";
    $html[]="<th data-sortable=true data-type='text'>{action}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $jsAfter="LoadAjax('table-loader-webhttp-rules','$page?table=yes&eth={$_GET["eth"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);



    $TRCLASS=null;

    $results=$q->QUERY_SQL("SELECT * FROM mimedefang_urls");


    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $zmd5=$ligne["zmd5"];
        $mailfrom=$ligne["mailfrom"];
        $mailto=$ligne["mailto"];
        $type=$ligne["type"];
        $type_text=$GLOBALS["ACTIONSAV"][$type];

        if($mailfrom=="*"){$mailfrom="{everyone}";}
        if($mailto=="*"){$mailto="{everyone}";}
        $js="Loadjs('$page?rule-js=$zmd5',true);";

        $html[]="<tr class='$TRCLASS' id='$zmd5'>";
        $html[]="<td><strong>". $tpl->td_href($mailfrom,null,$js)."</strong></td>";
        $html[]="<td><strong>". $tpl->td_href($mailto,null,$js)."</strong></td>";
        $html[]="<td width=1% nowrap><strong>". $tpl->td_href($type_text,null,$js)."</strong></td>";
        $html[]="<td width=1% class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-rule-js=$zmd5')","AsPostfixAdministrator") ."</center></td>";
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

function save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $q=new postgres_sql();
    $q->SMTP_TABLES();

    $sock=new sockets();
    $sock->getFrameWork("mimedefang.php?schedules=yes");


}