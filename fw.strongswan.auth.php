<?php
// Service Pack 9 2026-10-10
// SP 127 - // Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc"); if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
// Include INCONDITIONNEL de premier niveau (CLAUDE.md, awc-* regle 1).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus(); if (!$users->AsFirewallManager) {
    exit();
}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}

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

if (isset($_GET["build-progress"])) {
    build_progress();
    exit;
}
if (isset($_GET["auth-rule-move"])) {
    auth_move();
    exit;
}
if (isset($_GET["enable-js"])) {
    auth_enable();
    exit;
}
build_page();

function build_progress()
{
    $DATAS=unserialize($_SESSION["POSTFORMED"]);
    //print_r($DATAS);
    $page=CurrentPageName();

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.build.progress"; // Give percentage as an arra
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/strongswan.build.log";
    $ARRAY["CMD"]="strongswan.php?build-auth=yes";
    $ARRAY["TITLE"]="{reconfigure_service} {APP_STRONGSWAN}";
    $ARRAY["AFTER"]="LoadAjax('fw-objects-table-auth','$page?build-table=yes&rule-id={$DATAS["object-save"]}');";

    $prgress=base64_encode(serialize($ARRAY)); // Array is compiled
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid={$DATAS["form-id"]}')";

    echo $jsrestart;
}

function auth_move(){
    // Pentest 2026-11-04 : auth-rule-move et tunnel-id etaient concatenes dans la requete.
    $ID=intval($_GET["auth-rule-move"]);
    $TID=intval($_GET["tunnel-id"]);
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $sql="SELECT `order` FROM strongswan_auth WHERE `ID`='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    $xORDER_ORG=intval($ligne["order"]);

    $xORDER=$xORDER_ORG;

    if ($_GET["auth-rule-dir"]==1) {
        $xORDER=$xORDER_ORG-1;
    }
    if ($_GET["auth-rule-dir"]==0) {
        $xORDER=$xORDER_ORG+1;
    }

    $sql="UPDATE strongswan_auth SET `order`=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);

    $sql="UPDATE strongswan_auth SET
    `order`=$xORDER_ORG WHERE `ID`<>'$ID' AND `order`=$xORDER ";
    $q->QUERY_SQL($sql);

    $c=1;
    $sql="SELECT ID FROM strongswan_auth WHERE conn_id=$TID ORDER BY `order`";
    $results = $q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE strongswan_auth SET `order`=$c WHERE `ID`=".intval($ligne["ID"]));
        $c++;
    }
    $page=CurrentPageName();
    echo "LoadAjax('fw-objects-table-auth','$page?build-table=yes&rule-id=$TID');";
    $sock=new sockets();
    $sock->getFrameWork("strongswan.php?build-auth=yes");
}

function auth_enable(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    header("content-type: application/x-javascript");
    // Pentest 2026-11-04 : enable-js etait concatene dans la requete.
    $enableID=intval($_GET["enable-js"]);
    $ligne=$q->mysqli_fetch_array("SELECT conn_id, enable FROM strongswan_auth WHERE ID='$enableID'");
    $aclname=$ligne["aclname"];
    if(intval($ligne["enable"])==0){

        $enabled=1;
    }
    else{
        $enabled=0;
    }

    $q->QUERY_SQL("UPDATE strongswan_auth SET enable='$enabled' WHERE ID='$enableID'");
    if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}

    echo "LoadAjax('fw-objects-table-auth','$page?build-table=yes&rule-id={$ligne["conn_id"]}');";
    $sock=new sockets();
    $sock->getFrameWork("strongswan.php?build-auth=yes");
}

function build_page()
{
    $page=CurrentPageName();
    $CONN_ID=intval($_GET["rule-id"]);
    $tpl=new template_admin();
    $t=time();
    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div ><h4 class=ng-binding>{APP_STRONGSWAN_AUTH}</h4></div>
	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>
	<div id='fw-objects-table-auth'></div>

	</div>
	</div>
	<script>
	function ss$t(){
		LoadAjax('fw-objects-table-auth','$page?build-table=yes&rule-id=$CONN_ID');
	}
ss$t();
</script>";


    echo $tpl->_ENGINE_parse_body($html);
}

function delete_js()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    // Pentest 2026-11-04 : delete-js etait concatene dans la requete.
    $ID=intval($_GET["delete-js"]);
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM strongswan_auth WHERE ID='$ID'");
    $title="{$ligne["ID"]} - {delete}";
    $tpl->js_dialog_confirm($title, "$page?delete-confirm=$ID&js-after=".jsafter_token($_GET["js-after"])."");
}

function delete_confirm()
{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $q              = new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    // Pentest 2026-11-04 : delete-confirm etait concatene dans la requete.
    $ID             = intval($_GET["delete-confirm"]);
    $jsAfter        = jsafter_sanitize(base64_decode($_GET["js-after"]));
    $t              = time();


    $group_unlink_delete_explain=$tpl->_ENGINE_parse_body("{group_unlink_delete_explain}");
    $ligne=$q->mysqli_fetch_array("SELECT ID,conn_id FROM strongswan_auth WHERE ID='$ID'");
    $group_unlink_delete_explain=str_replace('%GPNAME', $ID, $group_unlink_delete_explain);
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
    // Pentest 2026-11-04 : delete-remove et conn-id etaient concatenes dans la requete.
    $ID=intval($_POST["delete-remove"]);
    $CONN_ID=intval($_POST["conn-id"]);
    $sock=new sockets();


    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    writelogs("DELETE FROM strongswan_auth WHERE ID='$ID'", __FUNCTION__, __FILE__, __LINE__);
    $sql="DELETE FROM strongswan_auth WHERE ID='$ID'";
    if (!$q->QUERY_SQL($sql)) {
        writelogs("MySQL Error: $q->mysql_error", __FUNCTION__, __FILE__, __LINE__);
        echo $q->mysql_error;
    }




    $sql="SELECT * FROM strongswan_auth where conn_id='$CONN_ID'";
    $results=$q->QUERY_SQL($sql);
    if (count($results)==0) {
        $sock->getFrameWork("strongswan.php?unlink-auth-file=yes&fid={$CONN_ID}");
    }
    sleep(2);
    $sock->getFrameWork("strongswan.php?build-auth=yes");
}


function new_object()
{
    $page=CurrentPageName();
    $ID=intval($_GET["ID"]);
    $AUTHID=intval($_GET["auth-id"]);
    $TYPE=intval($_GET["type"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $title="{new_object}";
    $btname="{add}";

    $psk= shell_exec("head -c 24 /dev/urandom | base64");

    $backjs="LoadAjax('fw-objects-table-auth','$page?build-table=yes&rule-id=$ID');";
    $jsAfter="LoadAjax('fw-objects-table-auth','$page?build-table=yes&rule-id=$ID');";
    $BootstrapDialog="LoadAjax('fw-objects-table-auth','$page?build-table=yes&rule-id=$ID');";
    //PSK SITE2SITE
    if ($TYPE==1) {
        $title="{STRONGSWAN_PSK}<br><br>{STRONGSWAN_PSK_EXPLAIN}";
        if ($AUTHID==0) {
            $tpl->field_hidden("object-save", $ID);
            $tpl->field_hidden("auth-id", $AUTHID);
            $tpl->field_hidden("type", $TYPE);
            $form[]=$tpl->field_text("secret", "{STRONGSWAN_PSK}", "$psk", true);
        }
        if ($AUTHID>0) {
            $btname="{update}";
            $sql="SELECT * FROM strongswan_auth WHERE ID=$AUTHID";
            $results=$q->QUERY_SQL($sql);
            $tpl->field_hidden("object-save", $ID);
            $tpl->field_hidden("auth-id", $AUTHID);
            $tpl->field_hidden("type", $TYPE);
            $form[]=$tpl->field_text("secret", "{STRONGSWAN_SECRET}", "{$results[0]["secret"]}", true);
        }
    }
    //PSK REMOTEACCESS
    if ($TYPE==2 || $TYPE==4 || $TYPE==5) {
        switch ($TYPE) {
            case 2:
                $title="{STRONGSWAN_PSK}<br><br>{STRONGSWAN_PSK_EXPLAIN}";
                break;
            case 4:
                $title="{STRONGSWAN_XAUTH}<br><br>{STRONGSWAN_XAUTH_EXPLAIN}";
                break;
            case 5:
                $title="{STRONGSWAN_EAP}<br><br>{STRONGSWAN_EAP_EXPLAIN}";
                break;
        }
        if ($AUTHID==0) {
            $tpl->field_hidden("object-save", $ID);
            $tpl->field_hidden("auth-id", $AUTHID);
            $tpl->field_hidden("type", $TYPE);
            $form[]=$tpl->field_text("selector", "{STRONGSWAN_SELECTOR}", null, true, "{STRONGSWAN_SELECTOR_EXPLAIN}");
            $form[]=$tpl->field_text("secret", "{STRONGSWAN_SECRET}", "$psk", true);
        }
        if ($AUTHID>0) {
            $btname="{update}";
            $sql="SELECT * FROM strongswan_auth WHERE ID=$AUTHID";
            $results=$q->QUERY_SQL($sql);
            $tpl->field_hidden("object-save", $ID);
            $tpl->field_hidden("auth-id", $AUTHID);
            $tpl->field_hidden("type", $TYPE);
            $form[]=$tpl->field_text("selector", "{STRONGSWAN_SELECTOR}", "{$results[0]["selector"]}", true, "{STRONGSWAN_SELECTOR_EXPLAIN}");
            $form[]=$tpl->field_text("secret", "{STRONGSWAN_SECRET}", "{$results[0]["secret"]}", true);
        }
    }

    //CERT REMOTEACCESS
    if ($TYPE==3 || $TYPE==9 || $TYPE==10) {
        switch ($TYPE) {
            case 3:
                $title="{STRONGSWAN_RSA}<br><br>{STRONGSWAN_RSA_EXPLAIN}";
                break;
            case 9:
                $title="{STRONGSWAN_BLISS}<br><br>{STRONGSWAN_BLISS_EXPLAIN}";
                break;
            case 10:
                $title="{STRONGSWAN_ECDSA}<br><br>{STRONGSWAN_ECDSA_EXPLAIN}";
                break;
        }
        $certInUse='';
        $getTunnelCert=$q->QUERY_SQL("SELECT * from strongswan_conns");
        foreach ($getTunnelCert as $index=>$ligne) {
            $parms=unserialize(base64_decode("{$ligne['params']}"));
            foreach ($parms as $key) {
                if ($key['name']=='leftcert') {
                    if ($key['type']=='select') {
                        $_values= getVal($key["values"]);
                        //echo $_values;
                        $certInUse=$_values;
                    }
                }
            }
        }
        $certToQuery=substr($certInUse, 0, strrpos($certInUse, "."));
        echo $certToQuery;
        // Pentest 2026-11-04 : server_cert etait concatene dans la requete.
        $sql="SELECT server_key FROM strongswan_certs WHERE `server_cert`=?";
        $cert=$q->QUERY_SQL_PARAMS($sql,array($certToQuery));
        foreach ($cert as $key=>$ligne) {
            $certs["{$ligne['server_key']}.pem"]="{$ligne['server_key']}.pem";
        }

        if ($AUTHID==0) {
            $tpl->field_hidden("object-save", $ID);
            $tpl->field_hidden("auth-id", $AUTHID);
            $tpl->field_hidden("type", $TYPE);
            $form[]=$tpl->field_array_hash($certs, "authcert", "nonull:{certificate}", null, true, null);
        }
        if ($AUTHID>0) {
            $btname="{update}";
            $sql="SELECT * FROM strongswan_auth WHERE ID=$AUTHID";
            $results=$q->QUERY_SQL($sql);
            $tpl->field_hidden("object-save", $ID);
            $tpl->field_hidden("auth-id", $AUTHID);
            $tpl->field_hidden("type", $TYPE);
            $form[]=$tpl->field_array_hash($certs, "authcert", "nonull:{certificate}", "{$results[0]["cert"]}", true, null);
        }
    }

    $tpl->form_add_button("{cancel}", $BootstrapDialog);
    $t=time();
    $tpl->field_hidden("form-id", "form-$t");
    $html[]="<div id='form-$t'></div>";
    $html[]=$tpl->form_outside($title, @implode("\n", $form), null, $btname, "Loadjs('$page?build-progress=yes');", "$jsAfter");
    if ($GLOBALS["VERBOSE"]) {
        echo __FUNCTION__.".".__LINE__." bytes: ".strlen($html)."<br>\n";
    }
    echo $tpl->_ENGINE_parse_body($html);
}
function getVal($arr)
{
    foreach ($arr as $key) {
        if (array_key_exists('selected', $key)) {
            if ($key['selected'] == true) {
                return $key['value'];
            }
        }
    }
}

function save_object()
{
    // Pentest 2026-11-04 : object-save, secret, selector et authcert etaient concatenes dans la requete.
    $ID=intval($_POST["object-save"]);
    $AUTHID=intval($_POST["auth-id"]);
    $TYPE=intval($_POST["type"]);
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $sql=null;
    $params=array();
    if ($TYPE==1) {
        if ($AUTHID==0) {
            $secret=$_POST["secret"];
            $sql="INSERT INTO strongswan_auth (`conn_id`,`selector`,`type`,`cert`,`secret`,`order`,`enable`)
            VALUES ('$ID','','$TYPE','',?,'0','1');";
            $params=array($secret);
        } else {
            $secret=$_POST["secret"];
            $sql="UPDATE strongswan_auth SET `conn_id`='{$ID}',`type`='{$TYPE}', `secret`=? WHERE ID='$AUTHID'";
            $params=array($secret);
        }
    }

    if ($TYPE==2 || $TYPE==4 || $TYPE==5) {
        if ($AUTHID==0) {

            $selector=$_POST["selector"];
            $secret=$_POST["secret"];
            $r2=$q->QUERY_SQL_PARAMS("SELECT COUNT(*) as count FROM strongswan_auth WHERE `selector`=?",array($selector));
            $sql2=isset($r2[0]) ? $r2[0] : array();
            $Items2=isset($sql2['count']) ? $sql2['count'] : 0;

            if ($Items2>0) {
                echo "jserror: The selector name is already in use by other tunnel, please choose a new one\n";
                return;
            }
            $sql="INSERT INTO strongswan_auth (`conn_id`,`selector`,`type`,`cert`,`secret`,`order`,`enable`)
            VALUES ('$ID',?,'$TYPE','',?,'0','1');";
            $params=array($selector,$secret);
        } else {
            $selector=$_POST["selector"];
            $secret=$_POST["secret"];
            $r2=$q->QUERY_SQL_PARAMS("SELECT COUNT(*) as count FROM strongswan_auth WHERE `selector`=? AND `conn_id` NOT IN ('$ID')",array($selector));
            $sql2=isset($r2[0]) ? $r2[0] : array();
            $Items2=isset($sql2['count']) ? $sql2['count'] : 0;

            if ($Items2>0) {
                echo "jserror: The selector name is already in use by other tunnel, please choose a new one\n";
                return;
            }
            $sql="UPDATE strongswan_auth SET `conn_id`='{$ID}',`selector`=?,`type`='{$TYPE}', `secret`=? WHERE ID='$AUTHID'";
            $params=array($selector,$secret);
        }
    }

    if ($TYPE==3 || $TYPE==9 || $TYPE==10) {
        if ($AUTHID==0) {
            $selector=$_POST["selector"];
            $cert=$_POST["authcert"];
            $r3=$q->QUERY_SQL_PARAMS("SELECT COUNT(*) as count FROM strongswan_auth WHERE `cert`=?",array($cert));
            $sql3=isset($r3[0]) ? $r3[0] : array();
            $Items3=isset($sql3['count']) ? $sql3['count'] : 0;

            if ($Items3>0) {
                echo "jserror: The certificate is already in use by other tunnel, please choose a new one\n";
                return;
            }
            $sql="INSERT INTO strongswan_auth (`conn_id`,`selector`,`type`,`cert`,`secret`,`order`,`enable`)
            VALUES ('$ID','','$TYPE',?,'','0','1');";
            $params=array($cert);
        } else {
            $selector=$_POST["selector"];
            $cert=$_POST["authcert"];
            $r3=$q->QUERY_SQL_PARAMS("SELECT COUNT(*) as count FROM strongswan_auth WHERE `cert`=? AND `conn_id` NOT IN ('$ID')",array($cert));
            $sql3=isset($r3[0]) ? $r3[0] : array();
            $Items3=isset($sql3['count']) ? $sql3['count'] : 0;

            if ($Items3>0) {
                echo "jserror: The certificate is already in use by other tunnel, please choose a new one\n";
                return;
            }
            $sql="UPDATE strongswan_auth SET `conn_id`='{$ID}',`type`='{$TYPE}', `cert`=? WHERE ID='$AUTHID'";
            $params=array($cert);
        }
    }

    if (!$q->QUERY_SQL_PARAMS($sql,$params)) {
        echo $q->mysql_error;
    }
    $_SESSION["POSTFORMED"]=serialize($_POST);
    // $sock=new sockets();
    // $sock->getFrameWork("strongswan.php?build-auth=yes");
}
function negation_js()
{
    header("content-type: application/x-javascript");
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $mkey           = $_GET["negation-js"];
    $table          = "firewallfilter_sqacllinks";
    $id             = $_GET["id"];
    list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/link/negation?table=".urlencode($table)."&zmd5=".urlencode($mkey));
    if (!$ok) {
        $tpl->js_mysql_alert($data);
        return;
    }
    $aclid    = is_array($data)?intval($data["aclid"] ?? 0):0;
    $negation = is_array($data)?intval($data["negation"] ?? 0):0;
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
    $ID=intval($_GET["ID"]);
    $CONN_ID=intval($_GET["rule-id"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $selector=$tpl->_ENGINE_parse_body("{STRONGSWAN_SELECTOR}");
    $secret=$tpl->_ENGINE_parse_body("{secret}");
    $types=$tpl->_ENGINE_parse_body("{type}");
    $enabled        = $tpl->_ENGINE_parse_body("{enabled}");
    $action        = $tpl->_ENGINE_parse_body("{actions}");
    $order          = $tpl->_ENGINE_parse_body("{order}");
    $NewAuth = $tpl->_ENGINE_parse_body("{STRONGSWAN_NEW_AUTH}");
    $TRCLASS=null;
    $nothing=$tpl->icon_nothing();
    $filtering="true";
    $td1="style='vertical-align:middle' class='center' width=1% nowrap";
    $tdn="style='vertical-align:middle'";
    $t=time();




    $html=array();
    $html[]="
        <div class=\"dropdown\">
            <button class=\"btn btn btn-primary dropdown-toggle\" type=\"button\" id=\"dropdownMenu1\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
            {$NewAuth}
            <span class=\"caret\"></span>
            </button>
            <ul class=\"dropdown-menu\" aria-labelledby=\"dropdownMenu1\">
                <li><a href=\"#\" OnClick=\"NewObject$t('1');\">PSK Site-To-Site</a></li>
                <li><a href=\"#\" OnClick=\"NewObject$t('2');\">PSK Remote Access</a></li>
                <li><a href=\"#\" OnClick=\"NewObject$t('3');\">RSA</a></li>
                <li><a href=\"#\" OnClick=\"NewObject$t('4');\">XAUTH</a></li>
                <li><a href=\"#\" OnClick=\"NewObject$t('5');\">EAP</a></li>
                <!--<li><a href=\"#\" OnClick=\"NewObject$t('6');\">NTLM</a></li>
                <li><a href=\"#\" OnClick=\"NewObject$t('7');\">PIN</a></li>
                <li><a href=\"#\" OnClick=\"NewObject$t('8');\">P12</a></li>-->
                <li><a href=\"#\" OnClick=\"NewObject$t('9');\">BLISS</a></li>
                <li><a href=\"#\" OnClick=\"NewObject$t('10');\">ECDSA</a></li>
            </ul>
        </div>
        ";
    $html[]="<div class='awc-wrap' style='margin-top:10px'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>$selector</th>";
    $html[]="<th class='center' nowrap>$types</th>";
    $html[]="<th class='center' nowrap>$enabled</th>";
    $html[]="<th style='text-align:right'>$action</th>";
    // Identifiants propres a la page: awc_pager_js() pagine le premier <tbody>
    // qu'il trouve quand on ne lui en nomme pas un.
    $html[]="</tr></thead><tbody id='sswa-rows'>";

    $jsAfter=base64_encode("LoadAjax('fw-objects-table-auth','$page?build-table=yes&rule-id={$CONN_ID}');");
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $sql="SELECT * FROM strongswan_auth where conn_id='$CONN_ID' ORDER BY `order` ASC";
    $results=$q->QUERY_SQL($sql);

    // Table de correspondance plutot qu'un switch de 30 lignes: les deux
    // premiers types sont deja le meme libelle (PSK), et une valeur inconnue
    // doit rester visible -- l'ancien switch laissait $type a sa valeur du
    // TOUR PRECEDENT, si bien qu'un type inattendu heritait du libelle de la
    // ligne d'au-dessus au lieu de se signaler.
    $typeLabels=array(1=>"PSK",2=>"PSK",3=>"RSA",4=>"XAUTH",5=>"EAP",
                      6=>"NTLM",7=>"PIN",8=>"P12",9=>"BLISS",10=>"ECDSA");

    $rows=array();
    foreach($results as $ligne){
        $tv=intval($ligne["type"]);
        $type=isset($typeLabels[$tv])?$typeLabels[$tv]:"#".$tv;
        $on=(intval($ligne["enable"])==1);

        $jsedit="LoadAjax('fw-objects-table-auth','$page?new-object=yes&ID={$ligne["conn_id"]}&auth-id={$ligne["ID"]}&type={$ligne["type"]}')";
        $edit=$tpl->icon_parameters($jsedit);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js={$ligne["ID"]}&js-after=$jsAfter')");
        $up=$tpl->icon_up("Loadjs('$page?auth-rule-move={$ligne["ID"]}&auth-rule-dir=1&tunnel-id={$ligne["conn_id"]}');");
        $down=$tpl->icon_down("Loadjs('$page?auth-rule-move={$ligne["ID"]}&auth-rule-dir=0&tunnel-id={$ligne["conn_id"]}');");
        $enable=$tpl->icon_check($ligne["enable"],"Loadjs('$page?enable-js={$ligne["ID"]}')",null,"AsFirewallManager");

        $h ="<tr id='auth-".intval($ligne["ID"])."'>";
        $h.="<td><div class='awc-ident'>";
        $h.="<span class='awc-ico".($on?"":" awc-ico-off")."'><i class='fas fa-key'></i></span>";
        $h.="<span><span class='awc-name".($on?"":" awc-name-off")."'>"
           ."<a href=\"javascript:blur();\" OnClick=\"$jsedit\">".htmlspecialchars((string)$ligne["selector"])."</a></span>";
        // Le rang passe en sous-titre: la ligne est deja rendue dans cet ordre
        // (ORDER BY `order`), une colonne entiere pour le repeter ne dit rien
        // de plus, et les fleches de la colonne d'action le pilotent.
        $h.="<div class='awc-meta'>#".intval($ligne["order"])."</div></span></div></td>";
        $h.="<td class='center'><span class='awc-badge ".($on?"awc-badge-on":"awc-badge-off")."'>"
           .htmlspecialchars($type)."</span></td>";
        $h.="<td class='center'>$enable</td>";
        $h.="<td><div class='awc-acts'>$up&nbsp;$down&nbsp;$edit&nbsp;$delete</div></td>";
        $h.="</tr>";
        $rows[]=$h;
    }
    if(count($rows)==0){
        $html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)." {items}</span>"
           ."<span class='awc-pg' id='sswa-pager'></span></div>";
    $html[]="</div></div>";

    // ICON_SCRIPTS_COMPILE() est IMPERATIF ici: ce tableau contient des
    // icon_check(), dont les fonctions BtSwitch<id>() partent dans un
    // accumulateur. $tpl->ICON_SCRIPTS est une propriete d'INSTANCE -- la case
    // s'afficherait parfaitement et ne repondrait a aucun clic, sans la
    // moindre erreur PHP (CLAUDE.md, awc-* regle 2).
    $js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
	function NewObject$t(x){
		LoadAjax('fw-objects-table-auth','$page?new-object=yes&ID=$CONN_ID&auth-id=0&type='+x);
	}
    </script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$js
        .awc_pager_js(25,"sswa-rows","sswa-pager");
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.')
{
    $tmp1 = round((float) $number, $decimals);
    while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1) {
        $tmp1 = $tmp2;
    }
    return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
