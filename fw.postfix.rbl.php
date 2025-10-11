<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["rbl-choose"])){rbl_save();exit;}
if(isset($_GET["rbl-js"])){rbl_js();exit;}
if(isset($_GET["rbl-delete"])){rbl_delete_js();exit;}
if(isset($_POST["rbl-delete"])){rbl_delete();exit;}
if(isset($_GET["rbl-popup"])){rbl_popup();exit;}
if(isset($_GET["rbl-enable"])){rbl_enable();exit;}
if(isset($_GET["table-div"])){table_div();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["rbl-status"])){rbl_status();exit;}
if(isset($_GET["EnableDNSWL-js"])){EnableDNSWL_js();exit;}
if(isset($_GET["EnableMilterGreylistExternalDB-js"])){EnableMilterGreylistExternalDB_js();exit;}
if(isset($_GET["EnableMilterGreylistExternalDB-popup"])){EnableMilterGreylistExternalDB_popup();exit;}
if(isset($_POST["EnableMilterGreylistExternalDB"])){EnableMilterGreylistExternalDB_save();;exit;}

page();

function EnableMilterGreylistExternalDB_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{articatech_rbl}", "$page?EnableMilterGreylistExternalDB-popup=yes");
}
function EnableDNSWL_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableDNSWL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSWL"));
    if($EnableDNSWL==1){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSWL",0);}else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSWL",1);
    }
    header("content-type: application/x-javascript");
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress.log";
    $ARRAY["CMD"]="postfix.php?smtpd-client-restrictions=yes";
    $ARRAY["TITLE"]="{smtpd_client_restrictions}";
    $ARRAY["AFTER"]="LoadAjax('rbl-status','$page?rbl-status=yes')";
    $prgress=base64_encode(serialize($ARRAY));
    echo "Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-rbl')";
}

function EnableMilterGreylistExternalDB_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress.log";
	$ARRAY["CMD"]="postfix.php?smtpd-client-restrictions=yes";
	$ARRAY["TITLE"]="{smtpd_client_restrictions}";
	$ARRAY["AFTER"]="BootstrapDialog1.close();LoadAjax('rbl-status','$page?rbl-status=yes')";
	$prgress=base64_encode(serialize($ARRAY));
	$reconfigure="Loadjs('fw.progress.php?content=$prgress&mainid=EnableMilterGreylistExternalDB-progress')";
	
	$html[]="<div id='EnableMilterGreylistExternalDB-progress'></div>";
	
	$EnableMilterGreylistExternalDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterGreylistExternalDB"));
	$jsafter=$reconfigure;
	$form[]=$tpl->field_checkbox("EnableMilterGreylistExternalDB","{enable}",$EnableMilterGreylistExternalDB);
	$html[]=$tpl->form_outside("{articatech_rbl}", $form,"{articatech_rbl_explain}","{apply}",$jsafter,"AsPostfixAdministrator",true);
	echo $tpl->_ENGINE_parse_body($html);
}

function EnableMilterGreylistExternalDB_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableMilterGreylistExternalDB", $_POST["EnableMilterGreylistExternalDB"]);
    $sock=new sockets();
    $sock->getFrameWork("postfix.php?smtpd-client-restrictions=yes");
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $instance_id=intval($_GET["instance-id"]);

    $html=$tpl->page_header("{ip_reputation} v$POSTFIX_VERSION",
        "fas fa-badge-check",
        "{postfix_ip_reputation_explain}",
        "$page?tabs=yes&instance-id=$instance_id",
        "postfix-rbl",
        "progress-postfix-rbls",false,
        "table-loader-postfix-rbl"
    );



    if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
    if($instance_id==0) {
        $array["{status}"] = "$page?status=yes";
    }
    if($EnablePostfixMultiInstance==1){
        if($instance_id==0) {
            echo $tpl->tabs_default($array);
            return true;
        }
    }
	$array["{public_rbls}"]="$page?table-div=yes&instance-id=$instance_id";
	echo $tpl->tabs_default($array);
    return true;
}

function table_div(){
	$page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);
	echo "<div id='postfix-rbl-div' style='margin-top:20px'></div><script>LoadAjax('postfix-rbl-div','$page?table=yes&instance-id=$instance_id');</script>";
}

function rbl_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_confirm_delete($_GET["rbl-delete"], "rbl-delete", $_GET["rbl-delete"],"$('#{$_GET["id"]}').remove()");
}
function rbl_delete(){
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$rbl=$_POST["rbl-delete"];
	$q->QUERY_SQL("DELETE FROM ip_reputations WHERE ID='$rbl'");
	if(!$q->ok){$q->mysql_error;}
}

function rbl_js(){
	$sock=new sockets();
	$page=CurrentPageName();
	$title="{new_service}";
	$tpl=new template_admin();
	$ID=intval($_GET["rbl-js"]);
	if($ID>0){$title=$_GET["rbl-js"];}
	$instance_id=intval($_GET["instance-id"]);
	$tpl->js_dialog1("{ip_reputation}", "$page?rbl-popup=$ID&instance-id=$instance_id");
}
function rbl_enable(){
	$rbl=intval($_GET["rbl-enable"]);
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled from ip_reputations WHERE ID='$rbl'");
	$enabled=$ligne["enabled"];
	if($enabled==1){$q->QUERY_SQL("UPDATE ip_reputations SET enabled=0 WHERE ID='$rbl'");return;}
	$q->QUERY_SQL("UPDATE ip_reputations SET enabled=1 WHERE ID='$rbl'");
}

function rbl_popup(){
	$t=time();
    $instance_id=intval($_GET["instance-id"]);
	$ID=intval($_GET["rbl-popup"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$data=file_get_contents("ressources/dnsrbl.db");
	$tr=explode("\n",$data);
    foreach ($tr as $val){
		if(preg_match("#RBL:(.+)#",$val,$re)){
			$RBL[$val]=strtolower(trim($re[1]))." ({RBL})";
			
		}
		if(preg_match("#RHSBL:(.+)#",$val,$re)){
			$RBL[$val]=strtolower(trim($re[1]))." ({RHSBL})";
		}
	}
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT * from ip_reputations WHERE ID='$ID'");
    $rbl=$ligne["service"];
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_hidden("instance_id",$instance_id);
	$form[]=$tpl->field_array_hash($RBL, "rbl-choose", "{service2}", $rbl);
	$form[]=$tpl->field_text("rbl", "{create_a_new_one}", null);
	echo $tpl->form_outside("{ip_reputation} {RBL} / {RHSBL}", $form,null,"{add}","dialogInstance1.close();LoadAjax('postfix-rbl-div','$page?table=yes&instance-id=$instance_id');","AsPostfixAdministrator");
	
}

function rbl_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

	$instance_id=intval($_POST["instance_id"]);
	if($_POST["rbl-choose"]<>null){$rbl=$_POST["rbl-choose"];}
	if($_POST["rbl"]<>null){$rbl="RBL:".strtolower(trim($_POST["rbl"]));}
	if($rbl==null){return;}
	$q->QUERY_SQL("INSERT INTO ip_reputations (service,enabled,instanceid) VALUES ('$rbl',1,$instance_id)");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress.log";
	$ARRAY["CMD"]="postfix.php?smtpd-client-restrictions=yes&instance-id=$instance_id";
	$ARRAY["TITLE"]="{smtpd_client_restrictions}";
	$ARRAY["AFTER"]="LoadAjax('postfix-queue-rbl','$page?table=yes&instance-id=$instance_id');";
	$prgress=base64_encode(serialize($ARRAY));
	$reconfigure="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-rbl')";
	

	$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rbl-js=0&instance-id=$instance_id')\">";
    $btn[]="<i class='fa fa-plus'></i> {new_service} </label>";



    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"javascript:$reconfigure;\">";
    $btn[]="<i class='fa fa-save'></i> {apply_configuration} </label>";

    $btn[]="</div>";
	$html[]="<table id='table-postfix-rbl' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{service2}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{enabled}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

	$results=$q->QUERY_SQL("SELECT * FROM ip_reputations WHERE instanceid=$instance_id");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
	
	foreach ($results as $num=>$ligne){
        $ID=$ligne["ID"];
		$service=trim($ligne["service"]);
		$itemencode=urlencode($service);
		$enabled=intval($ligne["enabled"]);
		$arrival_time=$ligne["arrival_time"];
		$id=md5(serialize($ligne));
		
		if(preg_match("#RBL:(.+)#",$service,$re)){
			$service=strtolower(trim($re[1]));
			$TYPE="{RBL}";
				
		}
		if(preg_match("#RHSBL:(.+)#",$service,$re)){
			$service=strtolower(trim($re[1]));
			$TYPE="{RHSBL}";
		}
		
		
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}	
		$html[]="<tr class='$TRCLASS' id='$id'>";
		$html[]="<td nowrap>$service</td>";
		$html[]="<td style='width:1%' nowrap>$TYPE</td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?rbl-enable=$ID&instance-id=$instance_id')",null,"AsPostfixAdministrator")."</center></td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?rbl-delete=$ID&id=$id&instance-id=$instance_id')","AsPostfixAdministrator")."</center></td>";
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

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{ip_reputation}:{public_rbls} v$POSTFIX_VERSION";
    $TINY_ARRAY["ICO"]="fas fa-badge-check";
    $TINY_ARRAY["EXPL"]="{postfix_ip_reputation_explain}";
    $TINY_ARRAY["URL"]="postfix-rbl";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-postfix-rbl').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function status(){
	$page=CurrentPageName();
	echo "<div id='rbl-status'></div>
	<script>LoadAjax('rbl-status','$page?rbl-status=yes');</script>";
}
function rbl_status(){	
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$EnableMilterGreylistExternalDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterGreylistExternalDB"));
	$MilterGreyListPatternTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListPatternTime"));
	$MilterGreyListPatternCount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListPatternCount"));
	$EnableDNSWL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSWL"));

	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$CountRBL=$q->COUNT_ROWS("ip_reputations");
	if($EnableMilterGreylistExternalDB==1){$CountRBL++;}
	
	if($CountRBL>0){
		$CountOFRBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CountOfRBLThreats"));
	}
	
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$EnableMilterGreylistExternalDB=0;}
	
	
	$html[]="<table style='width:750px;margin-top:20px'>";
	$html[]="<tr>";
	$html[]="<td valign='top' style='width:350px'>";
	
	if($CountRBL>0){
		$html[]=$tpl->widget_vert("{public_rbls}<br><small style='color:white'>{nbs_of_rbls}</small>", $CountRBL);
	}else{
		$html[]=$tpl->widget_rouge("{public_rbls}", "{not_used}");
	}
	
	if($EnableMilterGreylistExternalDB==1){
		
		$curl=new ccurl("https://rbl.artica.center/api/rest/rbl/query/version");
		$curl->Timeout=2;
		$curl->NoHTTP_POST=true;
		if(!$curl->get()){
			$VERSION="!{error}";
			$xtime=$curl->error;
			$color=false;
			
		}else{
			$ARRAY=json_decode($curl->data);
			$RBLDNSD_COMPILE_TIME=$ARRAY->TIME;
			$RBLDNSD_BLCK_COUNT=FormatNumber($ARRAY->BLACK_COUNT+$ARRAY->WHITE_COUNT);
			$RBLDNSD_WHITE_COUNT=$ARRAY->WHITE_COUNT;
			$xtime=distanceOfTimeInWords($RBLDNSD_COMPILE_TIME,time(),true);
			$icon="fas fas fa-database";
			$color=true;
			
		}
		
		$btn[0]["name"]="{disable}";
		$btn[0]["icon"]="far fa-shield";
		$btn[0]["js"]="Loadjs('$page?EnableMilterGreylistExternalDB-js=yes');";
		if($color) {
            $html[] = $tpl->widget_vert("{articatech_rbl}: {elements}", "$RBLDNSD_BLCK_COUNT<br><small style='color:white'>$xtime</small>", $btn);
        }else{
            $html[] = $tpl->widget_jaune("{articatech_rbl}: {elements}", "$RBLDNSD_BLCK_COUNT<br><small style='color:white'>$xtime</small>", $btn);
        }
		
	}else{
		$btn[0]["name"]="{activate}";
		$btn[0]["icon"]="far fa-shield-check";
		$btn[0]["js"]="Loadjs('$page?EnableMilterGreylistExternalDB-js=yes');";
		$html[]=$tpl->widget_grey("{articatech_rbl}", "{not_used}",$btn);
	}


	
	
	$html[]="</td>";
	$html[]="<td valign='top' style='padding-left:10px'>";
	if($CountOFRBL>0){
		$CountOFRBL=FormatNumber($CountOFRBL);
		$html[]=$tpl->widget_h("red","fas fa-ban",$CountOFRBL,"{public_rbls} {rejected_messages}");
	}else{
		$html[]=$tpl->widget_h("grey","fas fa-ban",0,"{public_rbls} {rejected_messages}");
	}
	if($EnableMilterGreylistExternalDB==1){
		if($MilterGreyListPatternCount>0){
			$MilterGreyListPatternCount_html=FormatNumber($MilterGreyListPatternCount);
			$html[]=$tpl->widget_h("green","fas fas fa-database",$MilterGreyListPatternCount_html,"{articatech_rbl} {entries}");
		}
	}else{
		if($MilterGreyListPatternCount>1){
			$MilterGreyListPatternCount_html=FormatNumber($MilterGreyListPatternCount);
            $html[]=$tpl->widget_h("grey","fas fas fa-database",$MilterGreyListPatternCount_html,"{articatech_rbl} {entries}");
		}else{
            $html[]=$tpl->widget_h("grey","fas fas fa-database","{unknown}","{articatech_rbl} {entries}");
        }
	}

    $btn=array();
    if($EnableDNSWL==0){
        $btn[0]["name"]="{activate}";
        $btn[0]["icon"]="fas fa-badge-check";
        $btn[0]["js"]="Loadjs('$page?EnableDNSWL-js=yes');";
        $html[]=$tpl->widget_grey("{public_whitelist_database}", "{not_used}",$btn);

    }else{
        $btn[0]["name"]="{disable}";
        $btn[0]["icon"]="fas fa-badge";
        $btn[0]["js"]="Loadjs('$page?EnableDNSWL-js=yes');";
        $html[] = $tpl->widget_vert("{public_whitelist_database}", "{enabled}", $btn);

    }


    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{ip_reputation} {status} v$POSTFIX_VERSION";
    $TINY_ARRAY["ICO"]="fas fa-badge-check";
    $TINY_ARRAY["EXPL"]="{postfix_ip_reputation_explain}";
    $TINY_ARRAY["URL"]="postfix-rbl";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</td>";
	$html[]="</tr>";
	$html[]="</table><script>$jstiny</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
		
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

