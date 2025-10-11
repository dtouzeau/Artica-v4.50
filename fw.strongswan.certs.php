<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc"); if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
$users=new usersMenus(); if (!$users->AsFirewallManager) {
    exit();
}
include_once(dirname(__FILE__)."/framework/class.unix.inc");
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if(isset($_GET["download"])){download();exit;}
if (isset($_POST["object-save"])) {
    save_object();
    exit;
}
if (isset($_GET["build-table"])) {
    build_table();
    exit;
}
if (isset($_GET["new-object"])) {
    new_object();
    exit;
}
if (isset($_GET["link-object"])) {
    link_object();
    exit;
}
if (isset($_POST["object-link"])) {
    save_link_object();
    exit;
}

if (isset($_GET["delete-js"])) {
    delete_js();
    exit;
}
if (isset($_GET["delete-confirm"])) {
    delete_confirm();
    exit;
}
if (isset($_POST["delete-unlink"])) {
    delete_unlink();
    exit;
}
if (isset($_POST["delete-remove"])) {
    delete_remove();
    exit;
}
if (isset($_GET["enabled-js"])) {
    enabled_js();
    exit;
}
if (isset($_GET["negation-js"])) {
    negation_js();
    exit;
}
if (isset($_GET["newrule-js"])) {
    new_rule_js();
    exit;
}

if(isset($_GET["build-progress"])){build_progress();exit;}
build_page();


function build_progress(){

    $DATAS=unserialize($_SESSION["POSTFORMED"]);
    //print_r($DATAS);
    $page=CurrentPageName();
    
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.build.progress"; // Give percentage as an arra
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.build.log";
    $ARRAY["CMD"]="strongswan.php?build-cert=yes&cert-name={$DATAS["cert-name"]}&cn={$DATAS["cn"]}&id={$DATAS["object-save"]}";
    $ARRAY["TITLE"]="{reconfigure_service} {APP_STRONGSWAN}"; 
    $ARRAY["AFTER"]="BootstrapDialog1.close();LoadAjax('fw-objects-table','$page?build-table=yes&ID={$DATAS["object-save"]}');";
    

    $prgress=base64_encode(serialize($ARRAY)); // Array is compiled
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid={$DATAS["form-id"]}')";
    
    echo $jsrestart;

}

function build_page()
{
    //$ID=intval($_GET["rule-id"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();

//    $q = new lib_sqlite("/home/artica/SQLITE/strongswan.db");
//    $ligne2 = $q->mysqli_fetch_array("SELECT * FROM strongswan_auth WHERE conn_id='$ID'");
//    if (!$q->ok) {
//        echo $q->mysql_error_html(true);
//    }

    $new_certificate = $tpl->_ENGINE_parse_body("{new_certificate}");
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html="
    <div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_STRONGSWAN} {certificates}</h1><p>{STRONGSWAN_CERTIFICATES_EXPLAIN}</p></div>
	
	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>
	
    <button class=\"btn btn btn-primary\" type=\"button\" id=\"dropdownMenu1\" OnClick=\"NewObject$t();\">{$new_certificate}</button>&nbsp;$btn
    <div id='fw-objects-table'></div>
    </div>
    <script>
        $.address.state('/');
	    $.address.value('/ipsec-certificates');	
	    function ss$t(){
	        LoadAjax('fw-objects-table','$page?build-table=yes');
	    }
	    function NewObject$t(){
		    Loadjs('$page?newrule-js=yes&cert-id=0',true);
	    }
        ss$t();
    </script>
";

    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Certificates',$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);
}

function delete_js()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $ID=$_GET["delete-js"];
    $ligne=$q->mysqli_fetch_array("SELECT ID,name FROM strongswan_certs WHERE ID='$ID'");
    $title="{$ligne["name"]} - {delete}";
    $tpl->js_dialog_confirm($title, "$page?delete-confirm=$ID&js-after={$_GET["js-after"]}");
}

function delete_confirm()
{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $q              = new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $ID             = $_GET["delete-confirm"];
    $jsAfter        = base64_decode($_GET["js-after"]);
    $t              = time();


    $group_unlink_delete_explain=$tpl->_ENGINE_parse_body("{group_unlink_delete_explain}");
    $ligne=$q->mysqli_fetch_array("SELECT ID,name FROM strongswan_certs WHERE ID='$ID'");
    $group_unlink_delete_explain=str_replace('%GPNAME', $ligne["name"], $group_unlink_delete_explain);
    $html="<div class=row>
		
		<div class=\"alert alert-danger\">$group_unlink_delete_explain</div>
		
		<table style='width:100%'>
		<tr>
			<td style='text-align:center;width:50%'>
				<button class='btn btn-danger btn-lg' type='button' OnClick=\"Remove$t()\">{delete}</button>
			</td>			
		</tr>
		</table>
		</div>
<script>
		
		var xPost$t= function (obj) {
			var res=obj.responseText;
			if(res.length>3){alert(res);return;}
			DialogConfirm.close();
			$jsAfter

		}

		function Remove$t(){
			var XHR = new XHRConnection();
            XHR.appendData('delete-remove', '$ID');
            XHR.appendData('conn-id','{$ligne["conn_id"]}');
		    XHR.sendAndLoad('$page', 'POST',xPost$t);  			
		}		
</script>	
";
    
    
    echo $tpl->_ENGINE_parse_body($html);
}



function delete_remove()
{
    $ID=$_POST["delete-remove"];
    $sock=new sockets();

    
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $sql="SELECT * FROM strongswan_certs where ID='$ID'";
    $results=$q->QUERY_SQL($sql);
    $name=$results[0]["name"];
    writelogs("DELETE FROM strongswan_certs WHERE ID='$ID'", __FUNCTION__, __FILE__, __LINE__);
    $sql="DELETE FROM strongswan_certs WHERE ID='$ID'";
    if (!$q->QUERY_SQL($sql)) {
        writelogs("MySQL Error: $q->mysql_error", __FUNCTION__, __FILE__, __LINE__);
        echo $q->mysql_error;
    }

    $sock->getFrameWork("strongswan.php?unlink-cert-file=yes&name={$name}");
    $sock->getFrameWork("strongswan.php?build-auth=yes");
}

function new_rule_js()
{
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $ID     = intval($_GET["cert-id"]);

    $title="{new_certificate}";
    $tpl->js_dialog($title, "$page?new-object=yes&cert-id=$ID");
}

function new_object()
{
    $page=CurrentPageName();
    $ID=intval($_GET["cert-id"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $btname="{add}";
    
    

    $backjs="LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID');";
    $jsAfter="LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID');";
    $BootstrapDialog="BootstrapDialog1.close();LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID');";
    $title="{STRONGSWAN_NEW_CERTIFICATES_EXPLAIN}";
    if ($ID==0) {
        $tpl->field_hidden("object-save", $ID);
        $form[]=$tpl->field_text("cert-name", "{name}", null, true);
        $form[]=$tpl->field_text("cn", "{CommonName}", null, true);
    }
    if ($ID>0) {
        $btname="{update}";
        $sql="SELECT * FROM strongswan_certs WHERE ID=$ID";
        $results=$q->QUERY_SQL($sql);
        $tpl->field_hidden("object-save", $ID);
        $tpl->field_hidden("cert-name", $results[0]["name"]);
        $form[]=$tpl->field_text("cn", "{CommonName}", "{$results[0]["cn"]}", true);
    }

    $tpl->form_add_button("{cancel}", $BootstrapDialog);
    $t=time();
    $tpl->field_hidden("form-id","form-$t");
    $html[]="<div id='form-$t'></div>";
    $html[]=$tpl->form_outside($title, @implode("\n", $form), null, $btname, "Loadjs('$page?build-progress=yes');", "$jsAfter");
    if ($GLOBALS["VERBOSE"]) {
        echo __FUNCTION__.".".__LINE__." bytes: ".strlen($html)."<br>\n";
    }

    echo $tpl->_ENGINE_parse_body($html);
    //echo $html;
}


function save_object()
{
    $ID=$_POST["object-save"];
    $cn=$_POST["cn"];
    $name=$_POST["cert-name"];
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $tpl=new template_admin();
    if (preg_match('#[^a-zA-Z0-9]#', $name)) {
        echo "jserror:Invalid characters in Name ";
        die();// one or more of the 'special characters' found in $string
    }

    if (!preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/', $cn, $output_array)) {
        if (!filter_var($cn, FILTER_VALIDATE_IP)) {
            echo "jserror:CN is not a valid FQN or IP";
            die();
        }
    }
    if ($ID==0) {
        $sql= ("SELECT * FROM strongswan_certs where name='{$name}'");
        $results=$q->QUERY_SQL($sql);
    
        if (count($results)>0) {
            echo "jserror:Alredy exist on certificate with the name $name";
            die();
        }
    }

    $_SESSION["POSTFORMED"]=serialize($_POST);

    
}
function negation_js()
{
    header("content-type: application/x-javascript");
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $mkey           = $_GET["negation-js"];
    $q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $table          = "firewallfilter_sqacllinks";
    $sql            = "SELECT aclid,negation FROM $table WHERE zmd5='$mkey'";
    $ligne          = $q->mysqli_fetch_array($sql);
    $id             = $_GET["id"];
    $aclid          = $ligne["aclid"];

    echo "//$sql --> negation == {$ligne["negation"]},ID={$ligne["aclid"]}\n";
    if (intval($ligne["negation"])==0) {
        $negation=1;
    } else {
        $negation=0;
    }

    $sql="UPDATE $table SET `negation`='$negation' WHERE zmd5='$mkey'";
    $q->QUERY_SQL($sql);

    if (!$q->ok) {
        $tpl->js_mysql_alert($q->mysql_error);
        return;
    }
    $text_is="{is}";
    if ($negation==1) {
        $text_is="{is_not}";
    }
    $text_is=$tpl->_ENGINE_parse_body($text_is);
    $text_is=str_replace("'", "\'", $text_is);
    echo "document.getElementById('$id').innerHTML='$text_is';\n";
    echo "Loadjs('fw.rules.php?fill=$aclid');\n";
}

function build_table()
{
    //$ID=intval($_GET["ID"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $selector=$tpl->_ENGINE_parse_body("{STRONGSWAN_SELECTOR}");
    $certificate=$tpl->_ENGINE_parse_body("{certificate}");
    $download=$tpl->javascript_parse_text("{download2}");
    $cn=$tpl->_ENGINE_parse_body("{CommonName}");
    $action        = $tpl->_ENGINE_parse_body("{actions}");
    $TRCLASS=null;
    $nothing=$tpl->icon_nothing();
    $filtering="true";
    $td1="style='vertical-align:middle' class='center' width=1% nowrap";
    $tdn="style='vertical-align:middle'";

    $html=array();
    $html[]="<table id='table-strongswan-objects' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable='true' class='text-capitalize' data-type='text'>ID</th>";
    $html[]="<th data-sortable='true' class='text-capitalize' data-type='text'>$certificate</th>";
    $html[]="<th data-sortable='true' class='text-capitalize' data-type='text'>$cn</th>";
    $html[]="<th data-sortable='false' class='text-capitalize' data-type='text'>$action</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    
    $jsAfter=base64_encode("LoadAjax('fw-objects-table','$page?build-table=yes');");
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $sql = "SELECT * FROM strongswan_certs ORDER BY ID DESC";
    $results = $q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne) {
        if ($TRCLASS=="footable-odd") {
            $TRCLASS=null;
        } else {
            $TRCLASS="footable-odd";
        }

        $html[]="<tr class='$TRCLASS'>";

    
        $MAIN=$tpl->table_object($ligne["ID"]);

        $jsdownload="$page?download=yes&ID={$ligne["ID"]}&t=$t";
        $ca_cert = $ligne["ca_cert"];
        $size=filesize("/etc/ipsec.d/cacerts/{$ca_cert}.pem");
        $urljs="<a href=\"$jsdownload;\" class=\"btn btn-white btn-bitbucket\">";
        $jsedit="Loadjs('$page?newrule-js=yes&cert-id={$ligne["ID"]}&js-after=$jsAfter')";
        $edit=$tpl->icon_parameters($jsedit);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js={$ligne["ID"]}&js-after=$jsAfter')");
    
        $html[]="<td nowrap>{$ligne["ID"]}</td>";
        $html[]="<td nowrap>{$ligne["name"]}</td>";
        $html[]="<td nowrap >{$ligne["cn"]}</td>";
        $html[]="<td nowrap >$urljs<i class='fa fa-download' style='color:#18a689'></i></a>&nbsp;$edit&nbsp;$delete</td>";

        $html[]="</tr>";
    }
    
    $html[]="</tbody>";
    $html[]="<tfoot>";
    
    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    
    $html[]="
<script>
	$(document).ready(function() { $('#table-strongswan-objects').footable( { \"filtering\": { \"enabled\": $filtering }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
    
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function download(){
    $ID=$_GET["ID"];
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $ligne=$q->mysqli_fetch_array("SELECT name,ca_cert_content,ca_cert FROM strongswan_certs WHERE ID='$ID'");
    $name=$ligne["name"];
    $ca_cert = $ligne["ca_cert"];
    $size=filesize("/etc/ipsec.d/cacerts/{$ca_cert}.pem");
	header("Content-Type:  application/octet-stream");
	header("Content-Disposition: attachment; filename=$name.pem");
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
	header("Pragma: no-cache"); // HTTP 1.0
	header("Expires: 0"); // Proxies
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé

	if(!$q->ok){exit;}
	header("Content-Length: ".$size);
	ob_clean();
	flush();
	echo unserialize(base64_decode($ligne["ca_cert_content"]));
}
