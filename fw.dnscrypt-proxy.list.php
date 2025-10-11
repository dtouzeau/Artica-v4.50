<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["disable-all-js"])){disable_all();exit;}
if(isset($_GET["table-list"])){table();exit;}
if(isset($_GET["option-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_POST["DnsCryptEnableUniqueProvider"])){save_options();exit;}
main();

function enable_js(){
    $tpl=new template_admin();
	$name=$_GET["enable-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array("SELECT Enabled FROM DnsCryptResolvers WHERE name='$name'");



    if (!$q->ok) {
        $tpl->js_mysql_alert($q->mysql_error);
        return;
    }


	if(intval($ligne["Enabled"])==0) {
        $q->QUERY_SQL("UPDATE DnsCryptResolvers SET `Enabled`=1 WHERE name='$name'");
        if (!$q->ok) {
            $tpl->js_mysql_alert($q->mysql_error);
            return;
        }
        return;
    }
	$q->QUERY_SQL("UPDATE DnsCryptResolvers SET `Enabled`=0 WHERE name='$name'");
}

function disable_all(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $q->QUERY_SQL("UPDATE DnsCryptResolvers SET Enabled=0");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);
        return;
    }
    echo "LoadAjax('dnscrypt-proxy-list','$page?table-list=yes');";
}

function options_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{options}","$page?options-popup=yes");
}

function options_popup(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $sock=new sockets();
    $DnsCryptEnableUniqueProvider=intval($sock->GET_INFO("DnsCryptEnableUniqueProvider"));
    $DnsCryptQueryLogging=intval($sock->GET_INFO("DnsCryptQueryLogging"));
    $DnsCryptProvider=trim($sock->GET_INFO("DnsCryptProvider"));
    $providers=array();
    $sql="SELECT *  FROM DnsCryptResolvers ORDER by name";
    $results = $q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne){
       $name=$ligne["Name"];
        $FullName=$ligne["FullName"];
        $providers[$name]=$FullName;
    }

    $html[]=$tpl->field_section("{unique_provider}","{DnsCryptEnableUniqueProvider}");
    $html[]=$tpl->field_checkbox("DnsCryptEnableUniqueProvider","{unique_provider}",$DnsCryptEnableUniqueProvider,false);
    $html[]=$tpl->field_array_hash($providers,"DnsCryptProvider","{providers}",$DnsCryptProvider);
    $html[]=$tpl->field_section("{events}","");
    $html[]=$tpl->field_checkbox("DnsCryptQueryLogging","{log_cqueries}",$DnsCryptQueryLogging,false);




    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
    $ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
    $ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

    echo $tpl->form_outside("{options}",$html,null,"{apply}","BootstrapDialog1.close();$jsrestart","AsDnsAdministrator");
}

/**
 *
 */
function save_options(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
	$ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
	$ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.update.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.update.progress.log";
    $ARRAY["CMD"]="dnscrypt-proxy.php?update=yes";
    $ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {update2}";
    $ARRAY["AFTER"]="LoadAjax('dnscrypt-proxy-list','$page?table-list=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsupdate="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";


	$html[]="
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:15px'>
			<label class='btn btn btn-primary' OnClick=\"Loadjs('$page?disable-all-js=yes');\"><i class='fas fa-comment-minus'></i> {disable_all} </label>
			<label class='btn btn btn-info' OnClick=\"$jsupdate\"><i class='fas fa-download'></i> {update2} </label>
			<label class='btn btn btn-primary' OnClick=\"Loadjs('$page?option-js=yes');\"><i class='fas fa-wrench'></i> {options} </label>
			<label class='btn btn btn-info' OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {reconfigure_service} </label>
			</div>";


    $html[]="<div id='dnscrypt-proxy-list'></div>";
    $html[]="<script>LoadAjax('dnscrypt-proxy-list','$page?table-list=yes');</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function table(){

    $tpl=new template_admin();
    $sock=new sockets();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $t=time();


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='align:center'>{enable}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    $sql="SELECT *  FROM DnsCryptResolvers ORDER by name";

    $results = $q->QUERY_SQL($sql);



    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $md=md5(serialize($ligne));
        $name=$ligne["Name"];
        $nameenc=urlencode($name);
        $Location=$ligne["Location"];
        $url=$ligne["url"];
        $Description=$ligne["Description"];
        $ResolverAddress=$ligne["ResolverAddress"];
        $FullName=$ligne["Name"];


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='vertical-align:middle;align:center' width=1%>".$tpl->icon_check($ligne["Enabled"],"Loadjs('$page?enable-js=$nameenc&id=$md')","AsDnsAdministrator")."</td>";
        $html[]="<td nowrap style='width:1%;font-weight:bold'>$FullName</td>";
        $html[]="<td><small>$Description<br><strong>$ResolverAddress</strong></td>";
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
	$(document).ready(function() { $('#table-$t').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body($html);

}