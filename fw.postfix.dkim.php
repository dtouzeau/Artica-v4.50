<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["DisconnectDKFilter"])){Save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["database"])){database();exit;}
if(isset($_GET["dkimkey-private"])){dkimkey_private_js();exit;}
if(isset($_GET["dkimkey-private-popup"])){dkimkey_private_popup();exit;}
if(isset($_GET["dkimkey-dns"])){dkimkey_dns_js();exit;}
if(isset($_GET["dkimkey-dns-popup"])){dkimkey_dns_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}
if(isset($_GET["dkim-status"])){dkim_status();exit;}

page();

function page(){
    $page=CurrentPageName();
    $tpl=new templates();
    $OPENDKIM_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENDKIM_VERSION");
    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_OPENDKIM} v$OPENDKIM_VERSION</h1>
	<p>{dkim_about}<br>{dkim_about2}</p></div>
	</div>
	<div class='row'>
	<div id='progress-opendkim-restart'></div>
	<div class='ibox-content'>
	<div id='table-opendkim-rules'></div>
	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/opendkim');
	LoadAjax('table-opendkim-rules','$page?tabs=yes');

	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{websites}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function delete_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["id"];
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT domain FROM dkimkeys WHERE ID='{$_GET["delete"]}'");
    $domain=$ligne["domain"];
    $tpl->js_confirm_delete($domain,"delete",$_GET["delete"],"$('#$id').remove()");
}
function delete_perform(){
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $q->QUERY_SQL("DELETE FROM dkimkeys WHERE ID='{$_POST["delete"]}'");
    if(!$q->ok){echo $q->mysql_error;}
    $sock=new sockets();
    $sock->getFrameWork("opendkim.php?syncdomains=yes");
}

function dkimkey_private_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT domain FROM dkimkeys WHERE ID='{$_GET["dkimkey-private"]}'");
    $tpl->js_dialog1("{private_key} {$ligne["domain"]}","$page?dkimkey-private-popup={$_GET["dkimkey-private"]}");
}
function dkimkey_dns_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT domain FROM dkimkeys WHERE ID='{$_GET["dkimkey-dns"]}'");
    $tpl->js_dialog1("{dns_entry} {$ligne["domain"]}","$page?dkimkey-dns-popup={$_GET["dkimkey-dns"]}");
}

function dkimkey_private_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT private_key FROM dkimkeys WHERE ID='{$_GET["dkimkey-private-popup"]}'");
    echo "<textarea style='width:99%;height:512px'>".base64_decode($ligne["private_key"])."</textarea>";
}
//dns_entry
function dkimkey_dns_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT dns_entry FROM dkimkeys WHERE ID='{$_GET["dkimkey-dns-popup"]}'");

    $dnscontent=base64_decode($ligne["dns_entry"]);



    if(preg_match("#^(.+?)\s+IN\s+TXT.*?v=(.+?);.*?h=(.+?);.*?k=(.+?);.*?s=(.+?);.*?p=(.+?)\)#is",$dnscontent,$re)){
        $dom=$re[1];
        $v=$re[2];
        $h=$re[3];
        $k=$re[4];
        $s=$re[5];
        $p=$re[6];
        $p=str_replace('"',"",$p);
        $p=str_replace("\t","",$p);
        $p=str_replace(" ","",$p);
        $p=str_replace("\n","",$p);



        $BookMyName="$dom 28800  TXT    \"v=$v; h=$h; k=$k; s=$s; p=$p;\"";

        $dnscontent=$dnscontent."\n--------------------------------------------------------------------------------------------------------\nBookMyName:\n$BookMyName\n";



    }






    echo "<textarea style='width:99%;height:512px'>$dnscontent</textarea>";
}


function dkim_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/opendkim.restart.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/opendkim.restart.log";
    $ARRAY["CMD"]="opendkim.php?restart=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('table-opendkim-rules','$page?tabs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $milterrestart_js="Loadjs('fw.progress.php?content=$prgress&mainid=progress-opendkim-restart');";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork('opendkim.php?status=yes');
    $ini->loadFile("/usr/share/artica-postfix/ressources/logs/web/opendkim.status");
    echo $tpl->SERVICE_STATUS($ini, "APP_OPENDKIM",$milterrestart_js);
}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();



    $actions=array("accept"=>"{accept}","discard"=>"{discard}","tempfail"=>"{tempfail}");
    $DisconnectDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisconnectDKFilter");
    $conf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenDKIMConfig")));


    if(!is_numeric($DisconnectDKFilter)){$DisconnectDKFilter=0;}

    if($conf["On-BadSignature"]==null){$conf["On-BadSignature"]="accept";}
    if($conf["On-NoSignature"]==null){$conf["On-NoSignature"]="accept";}
    if($conf["On-DNSError"]==null){$conf["On-DNSError"]="tempfail";}
    if($conf["On-InternalError"]==null){$conf["On-InternalError"]="accept";}

    if($conf["On-Security"]==null){$conf["On-Security"]="tempfail";}
    if($conf["On-Default"]==null){$conf["On-Default"]="accept";}
    if($conf["ADSPDiscard"]==null){$conf["ADSPDiscard"]="1";}
    if($conf["ADSPNoSuchDomain"]==null){$conf["ADSPNoSuchDomain"]="1";}
    if($conf["DomainKeysCompat"]==null){$conf["DomainKeysCompat"]="0";}
    if(!isset($conf["OpenDKIMTrustInternalNetworks"])){$conf["OpenDKIMTrustInternalNetworks"]=1;}



    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/opendkim.restart.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/opendkim.restart.log";
    $ARRAY["CMD"]="opendkim.php?restart=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('table-opendkim-rules','$page?tabs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $milterrestart_js="Loadjs('fw.progress.php?content=$prgress&mainid=progress-opendkim-restart');";




    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="	<td style='width:260px' valign='top'>";
    $html[]="		<table style='width:100%'>";
    $html[]="			<tr><td valign='top'>";
    $html[]="				<div class=\"ibox\" style='border-top:0px'>";
    $html[]="					<div class=\"ibox-content\" style='border-top:0px'><div id='dkim-status'></div>";
        $html[]="					</div>";
    $html[]="				</div>
     						</td>
     					</tr>";
    $html[]="		</table>";
    $html[]="		</td>";
    $html[]="	<td style='width:98%'>";



    $form[]=$tpl->field_checkbox("DisconnectDKFilter","{disconnect_from_artica}",$DisconnectDKFilter,false);
    $form[]=$tpl->field_checkbox("OpenDKIMTrustInternalNetworks",
                "{OpenDKIMTrustInternalNetworks}",
                $conf["OpenDKIMTrustInternalNetworks"],false,
                "{OpenDKIMTrustInternalNetworks_text}");

    $form[]=$tpl->field_checkbox("DomainKeysCompat",
        "{DomainKeysCompat}",
        $conf["DomainKeysCompat"],false,
        "{DomainKeysCompat_text}");


    $form[]=$tpl->field_checkbox("ADSPDiscard",
        "{ADSPDiscard}",
        $conf["ADSPDiscard"],false,
        "{ADSPDiscard_text}");




    $form[]=$tpl->field_array_hash($actions,"On-Default","{On-Default}",
        $conf["On-Default"]);

    $form[]=$tpl->field_array_hash($actions,"On-BadSignature","{On-BadSignature}",
        $conf["On-BadSignature"]);

    $form[]=$tpl->field_array_hash($actions,"On-NoSignature","{On-NoSignature}",
        $conf["On-NoSignature"]);


    $form[]=$tpl->field_array_hash($actions,"On-DNSError","{On-DNSError}",
        $conf["On-DNSError"]);



    $form[]=$tpl->field_array_hash($actions,"On-Security","{On-Security}",
        $conf["On-Security"]);

    $form[]=$tpl->field_array_hash($actions,"On-InternalError","{On-InternalError}",
        $conf["On-InternalError"]);


    $html[]=$tpl->form_outside("{parameters}", $form,null,"{apply}",$milterrestart_js,"AsPostfixAdministrator");
    $html[]="<script>LoadAjax('dkim-status','$page?dkim-status=yes');</script>";

    echo $tpl->_ENGINE_parse_body($html);
    echo "\n";


}


function Save(){

    $sock=new sockets();
    $sock->SET_INFO("DisconnectDKFilter", $_GET["DisconnectDKFilter"]);
    $sock->SET_INFO("OpenDKIMConfig",base64_encode(serialize($_POST)));

}



function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
    $array["{status}"]="$page?status=yes";
    $array["{database}"]="$page?database=yes";
    echo $tpl->tabs_default($array);

}


function database(){

    $sock=new sockets();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/smtp_generic_maps";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp_generic_maps.txt";
    $ARRAY["CMD"]="postfix.php?postfix-hash-smtp-generic=yes";
    $ARRAY["TITLE"]="{smtp_generic_maps}";
    $prgress=base64_encode(serialize($ARRAY));
    $reconfigure="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-rewrite-rules');";
    $import="Loadjs('$page?import-js=yes')";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:20px'>";
    $html[]="<thead>";
    $html[]="<tr>";


    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domain}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{private_key} {size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{dns_entry}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;



    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results=$q->QUERY_SQL("SELECT * FROM dkimkeys ORDER by domain");
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}


    foreach ($results as $num=>$ligne) {
        $id = $ligne["ID"];
        $domain = $tpl->td_href($ligne["domain"], null, "Loadjs('$page?dkimkey-private=$id')");
        $private_key = strlen(base64_decode($ligne["private_key"]));
        $private_key=FormatBytes($private_key/1024);
        $dns_item = base64_decode($ligne["dns_entry"]);
        $dns_item=substr($dns_item,0,128)."...";
        $iddiv=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$iddiv'>";
        $html[]="<td style='vertical-align:middle' width=1% nowrap><strong>$domain</strong></td>";
        $html[]="<td style='vertical-align:middle' width=1% nowrap>{$private_key}</td>";
        $html[]="<td style='vertical-align:middle' width=99%>".$tpl->td_href($dns_item,null,"Loadjs('$page?dkimkey-dns=$id')")."</td>";
        $html[]="<td style='vertical-align:middle' class='center' width=1%>".$tpl->icon_delete("Loadjs('$page?delete=$id&id=$iddiv')","AsPostfixAdministrator")."</td>";
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