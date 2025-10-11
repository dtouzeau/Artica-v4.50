<?php
// SP 127
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc"); if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
$users=new usersMenus(); if (!$users->AsVPNManager) {
    exit();
}

#GET WIZARD S2S
if (isset($_GET["wizard-site2site"])) {
    wizard_site2site_js();
    exit;
}
if (isset($_GET["start-wizard-site2site"])) {
    wizard_site2site_start();
    exit;
}
if (isset($_GET["wizard-site2site-step1"])) {
    wizard_site2site_step1();
    exit;
}
if (isset($_GET["wizard-site2site-step2"])) {
    wizard_site2site_step2();
    exit;
}
if (isset($_GET["wizard-site2site-step3"])) {
    wizard_site2site_step3();
    exit;
}
if (isset($_GET["wizard-site2site-step4"])) {
    wizard_site2site_final();
    exit;
}
if (isset($_POST["WIZARDS2S_TO_SAVE"])) {
    wizard_site2site_save();
    exit;
}
if (isset($_POST["WIZARDS2S_FINAL"])) {
    wizard_site2site_final_save();
    exit;
}
#GET WIZARD RW
if (isset($_GET["wizard-rw"])) {
    wizard_rw_js();
    exit;
}
if (isset($_GET["start-wizard-rw"])) {
    wizard_rw_start();
    exit;
}
if (isset($_GET["wizard-rw-step1"])) {
    wizard_rw_step1();
    exit;
}
if (isset($_GET["wizard-rw-step2"])) {
    wizard_rw_step2();
    exit;
}
if (isset($_GET["wizard-rw-step3"])) {
    wizard_rw_step3();
    exit;
}
if (isset($_GET["wizard-rw-step4"])) {
    wizard_rw_final();
    exit;
}
if (isset($_POST["WIZARDRW_TO_SAVE"])) {
    wizard_rw_save();
    exit;
}
if (isset($_POST["WIZARDRW_FINAL"])) {
    wizard_rw_final_save();
    exit;
}

if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}

if (isset($_GET["search"])) {
    search();
    exit;
}
if (isset($_GET["delete-js"])) {
    delete_js();
    exit;
}
if (isset($_GET["ruleid-js"])) {
    rule_js();
    exit;
}
if (isset($_GET["rule-popup"])) {
    rule_popup();
    exit;
}
if (isset($_GET["rule-settings"])) {
    rule_settings();
    exit;
}
if (isset($_GET["main"])) {
    main();
    exit;
}
if (isset($_REQUEST["GenerateProgress"])) {
    GenerateProgress();
    exit;
}
if (isset($_POST["connection_name"])) {
    buildconfig();
    exit;
}
if (isset($_POST["rule-save"])) {
    rule_save();
    exit;
}

if (isset($_GET["delete-confirm"])) {
    delete_confirm();
    exit;
}
if (isset($_POST["delete-remove"])) {
    delete_remove();
    exit;
}
if (isset($_GET["build-progress"])) {
    build_progress();
    exit;
}
if (isset($_GET["conn-rule-move"])) {
    conn_move();
    exit;
}
if (isset($_GET["enable-js"])) {
    conn_enable();
    exit;
}
if (isset($_GET["download-js"])) {
    export_conf();
    exit;
}
page();
//START WIZARD S2S
function wizard_site2site_js()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{wizard} - {NEW_SITE2SITE_TUNNEL}", "$page?start-wizard-site2site=yes", 650);
}
function wizard_site2site_start()
{
    $page=CurrentPageName();
    echo "<div id='wizard-site2site-for-steps'></div>
		<script>LoadAjax('wizard-site2site-for-steps','$page?wizard-site2site-step1=yes');</script>
	";
}

function wizard_site2site_step1()
{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $tpl->field_hidden("WIZARDS2S_TO_SAVE", 1);
    $form[]=$tpl->field_text("connection_name", "{conn_name}", $_SESSION["WIZARDS2S"]["connection_name"], true, "{Conn_Explain}");
    $jsAfter="LoadAjax('wizard-site2site-for-steps','$page?wizard-site2site-step2=yes');";
    echo $tpl->form_outside("{Conn_Explain}", $form, null, "{next}", $jsAfter, "AsPostfixAdministrator");
}

function wizard_site2site_step2()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->field_hidden("WIZARDS2S_TO_SAVE", 1);
    $form[]=$tpl->field_section("{source} ({local})", "{APP_STRONGSWAN_WIZARD_LEFTSECTION_EXPLAIN}");
    //$form[]=$tpl->field_ipaddr("left","{APP_STRONGSWAN_WIZARD_LEFT}",$_SESSION["WIZARDS2S"]["left"],true);
    $form[]=$tpl->field_interfaces("left", "nooloopNoDef:{listen_interface}", $_SESSION["WIZARDS2S"]["left"], true);
    $form[]=$tpl->field_ipaddr("leftid", "Public IP", $_SESSION["WIZARDS2S"]["leftid"], true);
    // $form[]=$tpl->field_ipaddr("leftsubnet", "{APP_STRONGSWAN_WIZARD_LEFTSUB}", $_SESSION["WIZARDS2S"]["leftsubnet"], true);
    // $form[]=$tpl->field_maskcdir("leftnetmask", "nonull:{netmask}", isset($_SESSION["WIZARDS2S"]["leftnetmask"])?isset($_SESSION["WIZARDS2S"]["leftnetmask"]):"24", true);

    $form[]=$tpl->field_section("{destination} ({remote})", "{APP_STRONGSWAN_WIZARD_RIGHTSECTION_EXPLAIN}");
    $form[]=$tpl->field_ipaddr("right", "{APP_STRONGSWAN_WIZARD_RIGHT}", $_SESSION["WIZARDS2S"]["right"], true);
    $form[]=$tpl->field_ipaddr("rightsubnet", "{APP_STRONGSWAN_WIZARD_RIGHTSUB}", $_SESSION["WIZARDS2S"]["rightsubnet"], true);

    $form[]=$tpl->field_maskcdir("rightnetmask", "nonull:{netmask}", isset($_SESSION["WIZARDS2S"]["rightnetmask"])?$_SESSION["WIZARDS2S"]["rightnetmask"]:"24", true);
    $jsAfter="LoadAjax('wizard-site2site-for-steps','$page?wizard-site2site-step3=yes');";

    // Back Button

    $tpl->form_add_button("{back}", "LoadAjax('wizard-site2site-for-steps','$page?wizard-site2site-step1=yes');");

    echo $tpl->form_outside("{APP_STRONGSWAN_WIZARD_STEP2}", $form, null, "{next}", $jsAfter, "AsPostfixAdministrator");
}

function wizard_site2site_step3()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $psk= shell_exec("head -c 24 /dev/urandom | base64");
    $tpl->field_hidden("WIZARDS2S_TO_SAVE", 1);


    $form[]=$tpl->field_text("psk", "{STRONGSWAN_PSK}", isset($_SESSION["WIZARDS2S"]["psk"])?$_SESSION["WIZARDS2S"]["psk"]:$psk, true);
    $jsAfter="LoadAjax('wizard-site2site-for-steps','$page?wizard-site2site-step4=yes');";
    $tpl->form_add_button("{back}", "LoadAjax('wizard-site2site-for-steps','$page?wizard-site2site-step2=yes');");

    echo $tpl->form_outside("{APP_STRONGSWAN_WIZARD_STEP3}", $form, null, "{next}", $jsAfter, "AsPostfixAdministrator");
}

function wizard_site2site_final()
{
    $unix=new unix();
    $left=$unix->InterfaceToIPv4($_SESSION["WIZARDS2S"]["left"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div class='row'><div id='progress-strongswan-tunnels-restart'></div></div><h4><b>{APP_STRONGSWAN_WIZARD_STEP4}</b></h4><ul class=\"list-group\">
	<li class=\"list-group-item\"><b>{conn_name}:</b> {$_SESSION["WIZARDS2S"]["connection_name"]}</li>
	<li class=\"list-group-item\"><b>{listen_interface}:</b> {$left} ({$_SESSION["WIZARDS2S"]["left"]})</li>
<li class=\"list-group-item\"><b>Public IP:</b> {$_SESSION["WIZARDS2S"]["leftid"]}</li>
	<li class=\"list-group-item\"><b>{APP_STRONGSWAN_WIZARD_RIGHT}:</b> {$_SESSION["WIZARDS2S"]["right"]}</li>
	<li class=\"list-group-item\"><b>{APP_STRONGSWAN_WIZARD_RIGHTSUB}:</b> {$_SESSION["WIZARDS2S"]["rightsubnet"]}/{$_SESSION["WIZARDS2S"]["rightnetmask"]}</li>
	<li class=\"list-group-item\"><b>{STRONGSWAN_PSK}:</b> {$_SESSION["WIZARDS2S"]["psk"]}</li>
	</ul>
	";
    $jsAfter2="Loadjs('$page?build-progress=yes')";
    $tpl->field_hidden("WIZARDS2S_FINAL", 1);
    $tpl->form_add_button("{back}", "LoadAjax('wizard-site2site-for-steps','$page?wizard-site2site-step3=yes');");
    $html[]=$tpl->form_outside(null, $form, null, "{save}", $jsAfter2, "AsPostfixAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
}

function wizard_site2site_save()
{
    $unix=new unix();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $left=$unix->InterfaceToIPv4($_SESSION["WIZARDS2S"]["left"]);
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    foreach ($_POST as $key=>$value) {
        $_SESSION["WIZARDS2S"][$key]=$value;
    }


    if (isset($_POST["connection_name"])) {
        $sql1=$q->mysqli_fetch_array("SELECT COUNT(*) as count FROM strongswan_conns WHERE `conn_name`='{$_SESSION["WIZARDS2S"]["connection_name"]}'");
        $Items=$sql1['count'];

        if ($Items>0) {
            echo "jserror: The connection name already exist, please choose a new one\n";
        }
    }


    if (isset($_POST["rightsubnet"])) {
        $ipClass=new IP();
        if (!$ipClass->isValid($_POST["rightsubnet"])) {
            echo "jserror: Invalid remote subnet";
        }
    }
    if (isset($_POST["right"])) {
        $IpClass=new IP();
        $net=new system_nic($_SESSION["WIZARDS2S"]["left"]);
        $netmask=$net->NETMASK;
        $ipaddr=$net->IPADDR;
        if (!$IpClass->isValid($_POST["right"])) {
            echo "jserror: Invalid remote ip address";
        }
        if ($left==$_POST["right"]) {
            echo "jserror: Same network (Source/Remote)";
        }
        $rightsubnet=$IpClass->maskTocdir($ipaddr, $netmask);
        $ipaddrTB=explode(".", $ipaddr);
        $RightNetmaskToTest="{$_SESSION["WIZARDS2S"]["rightsubnet"]}/{$_SESSION["WIZARDS2S"]["rightnetmask"]}";
        if (preg_match("#\/(24|16|8)#", $RightNetmaskToTest)) {
            unset($ipaddrTB[3]);
            $IpToTest=@implode(".", $ipaddrTB).".".rand(1, 255);
        }
        if (ip_in_range($IpToTest, $RightNetmaskToTest)) {
            echo "jserror:Your network $IpToTest cannot be same of the remote network $RightNetmaskToTest for routing incompatibility";
        }
    }
    //echo "jserror: ho!! you have made a wrong patternn...";
}



function wizard_site2site_final_save()
{
    $unix=new unix();
    $sock=new sockets();
    //$sock->SET_INFO("IpsecWizard",serialize($_SESSION["WIZARDS2S"]));
    $conn_name=$_SESSION["WIZARDS2S"]["connection_name"];
    $left=$unix->InterfaceToIPv4($_SESSION["WIZARDS2S"]["left"]);
    //$leftsubnet=$_SESSION["WIZARDS2S"]["leftsubnet"]."/".$_SESSION["WIZARDS2S"]["leftnetmask"];
    $right=$_SESSION["WIZARDS2S"]["right"];
    $rightsubnet=$_SESSION["WIZARDS2S"]["rightsubnet"]."/".$_SESSION["WIZARDS2S"]["rightnetmask"];
    $leftid=$_SESSION["WIZARDS2S"]["leftid"];
    $net=new system_nic($_SESSION["WIZARDS2S"]["left"]);
    $netmask=$net->NETMASK;
    $ipaddr=$net->IPADDR;
    $IpClass=new IP();
    $leftsubnet=$IpClass->maskTocdir($ipaddr, $netmask);
    $saveparams='[{"type":"text","required":false,"label":"connection name","description":"Name of connection. Don`t use special characters or spaces. Example: siteX-to-siteY","className":"form-control","name":"conn","access":false,"subtype":"text","value": "'.$conn_name.'"},{"type":"select","required":false,"label":"authby","description":"How the two security gateways should authenticate each other; acceptable values are secret or pskfor pre-shared secrets, pubkey (the default) for public key signatures as well as the synonyms rsasigfor RSA digital signatures and ecdsasig for Elliptic Curve DSA signatures.never can be used if negotiation is never to be attempted or accepted (useful for shunt-only conns).Digital signatures are superior in every way to shared secrets. IKEv1 additionally supports the valuesxauthpsk and xauthrsasig that will enable eXtended Authentication (XAuth) in addition to IKEv1 mainmode based on shared secrets or digital RSA signatures, respectively.This parameter is deprecated for IKEv2 connections (and IKEv1 connections since 5.0.0), as two peersdo not need to agree on an authentication method. Use the left|rightauth parameter instead to defineauthentication methods.","className":"form-control","name":"authby","access":false,"multiple":false,"values":[{"label":"pubkey","value":"pubkey"},{"label":"rsasig","value":"rsasig"},{"label":"ecdsasig","value":"ecdsasig"},{"label":"psk","value":"psk"},{"label":"secret","value":"secret","selected":true},{"label":"xauthrsasig","value":"xauthrsasig"},{"label":"xauthpsk","value":"xauthpsk"},{"label":"never","value":"never"}]},{"type":"select","required":false,"label":"auto","description":"What operation, if any, should be done automatically at IPsec startup. add loads a connection withoutstarting it. route loads a connection and installs kernel traps. If traffic is detected betweenleftsubnet and rightsubnet, a connection is established. start loads a connection and bringsit up immediately. ignore ignores the connection. This is equal to deleting a connection from the configfile. Relevant only locally, other end need not agree on it.","className":"form-control","name":"auto","access":false,"multiple":false,"values":[{"label":"ignore","value":"ignore"},{"label":"add","value":"add"},{"label":"route","value":"route"},{"label":"start","value":"start","selected":true}]},{"type":"select","required":false,"label":"type","description":"The type of the connection; currently the accepted values are Tunnel: signifying a host-to-host,host-to-subnet, or subnet-to-subnet tunnel;Transport: signifying host-to-host transport mode;Transport_proxy: signifying the special Mobile IPv6 transport proxy mode;Passthrough: signifying that no IPsec processing should be done at all;Drop, signifying that packetsshould be discarded.","className":"form-control","name":"type","access":false,"multiple":false,"values":[{"label":"tunnel","value":"tunnel","selected":true},{"label":"transport","value":"transport"},{"label":"transport_proxy","value":"transport_proxy"},{"label":"passthrough","value":"passthrough"},{"label":"drop","value":"drop"}]},{"type":"text","required":false,"label":"left","description":"The IP address of the participant`s public-network interface or one of several magic values.The value %any for the local endpoint signifies an address to be filled in(by automatic keying) during negotiation. If the local peer initiates the connection setup the routing tablewill be queried to determine the correct local IP address. In case the local peer is responding to a connectionsetup then any IP address that is assigned to a local interface will be accepted. The value %any4 restrictsaddress selection to IPv4 addresses, the value %any6 reistricts address selection to IPv6 addresses.Prior to 5.0.0 specifying %any for the local endpoint was not supported for IKEv1 connections, insteadthe keyword %defaultroute could be used, causing the value to be filled in automatically with the localaddress of the default-route interface (as determined at IPsec startup time and during configurationupdate). Either left or right may be %defaultroute, but not both.The prefix % in front of a fully-qualified domain name or an IP address will implicitly set left|rightallowany=yes.If %any is used for the remote endpoint it literally means any IP address.If an FQDN is assigned it is resolved every time a configuration lookup is done. If DNS resolution times out,the lookup is delayed for that time.Since 5.1.1 connections can be limited to a specific range of hosts. To do so a range (10.1.0.0-10.2.255.255)or a subnet (10.1.0.0/16) can be specified, and multiple addresses, ranges and subnets can be separated by commas.While one can freely combine these items, to initiate the connection at least one non-range/subnet is required.Please note that with the usage of wildcards multiple connection descriptions might match a given incomingconnection attempt. The most specific description is used in that case.","className":"form-control","name":"left","access":false,"subtype":"text","value": "'.$left.'"},{"type":"text","required":false,"label":"leftsubnet","description":"Private subnet behind the left participant, expressed as network/netmask; if omitted, essentially assumedto be left/32|128, signifying that the left|right end of the connection goes to the left|right participant only.The configured subnets of the peers may differ, the protocol narrows it to the greatest common subnet.Since 5.0.0 this is also done for IKEv1, but as this may lead to problems with other implementations,make sure to configure identical subnets in such configurations.IKEv2 supports multiple subnets separated by commas, IKEv1 only interprets the first subnet of such a definition,unless the Cisco Unity extension plugin is enabled (available since 5.0.1). This is due to a limitation of the IKEv1protocol, which only allows a single pair of subnets per CHILD_SA. So to tunnel several subnets a conn entry hasto be defined and brought up for each pair of subnets.Since 5.1.0 the optional part after each subnet enclosed in square brackets specifies a protocol/port to restrictthe selector for that subnet. Examples: leftsubnet=10.0.0.1[tcp/http],10.0.0.2[6/80] or leftsubnet=fec1::1[udp],10.0.0.0/16[/53].Instead of omitting either value %any can be used to the same effect, e.g. leftsubnet=fec1::1[udp/%any],10.0.0.0/16[%any/53].Since 5.1.1, if the protocol is icmp or ipv6-icmp the port is interpreted as ICMP message type if it is less than 256,or as type and code if it greater or equal to 256, with the type in the most significant 8 bits and the code in theleast significant 8 bits.The port value can alternatively take the value %opaque for RFC 4301 OPAQUE selectors, or a numerical rangein the form 1024-65535. None of the kernel backends currently supports opaque or port ranges and uses %anyfor policy installation instead.Instead of specifying a subnet, %dynamic can be used to replace it with the IKE address, having the same effectas omitting left|rightsubnet completely. Using %dynamic can be used to define multiple dynamic selectors,each having a potentially different protocol/port definition.","className":"form-control","name":"leftsubnet","access":false,"subtype":"text","value": "'.$leftsubnet.'"},{"type": "text","required": true,"label": "leftid","description": "How the left|right participant should be identified for authentication; defaults to left|right or the subject of the certificate configured with left|rightcert. If left|rightcert is configured the identity has to be confirmed by the certificate, that is, it has to match the full subject DN or one of the subjectAltName extensions contained in the certificate.Can be an IP address, a fully-qualified domain name, an email address or a Distinguished Name for which theID type is determined automatically and the string is converted to the appropriate encoding.","className": "form-control","name": "leftid","access": true,"value": "'.$leftid.'","subtype": "text"},{"type":"text","required":false,"label":"right","description":"The IP address of the participant`s public-network interface or one of several magic values.The value %any for the local endpoint signifies an address to be filled in(by automatic keying) during negotiation. If the local peer initiates the connection setup the routing tablewill be queried to determine the correct local IP address. In case the local peer is responding to a connectionsetup then any IP address that is assigned to a local interface will be accepted. The value %any4 restrictsaddress selection to IPv4 addresses, the value %any6 reistricts address selection to IPv6 addresses.Prior to 5.0.0 specifying %any for the local endpoint was not supported for IKEv1 connections, insteadthe keyword %defaultroute could be used, causing the value to be filled in automatically with the localaddress of the default-route interface (as determined at IPsec startup time and during configurationupdate). Either left or right may be %defaultroute, but not both.The prefix % in front of a fully-qualified domain name or an IP address will implicitly set left|rightallowany=yes.If %any is used for the remote endpoint it literally means any IP address.If an FQDN is assigned it is resolved every time a configuration lookup is done. If DNS resolution times out,the lookup is delayed for that time.Since 5.1.1 connections can be limited to a specific range of hosts. To do so a range (10.1.0.0-10.2.255.255)or a subnet (10.1.0.0/16) can be specified, and multiple addresses, ranges and subnets can be separated by commas.While one can freely combine these items, to initiate the connection at least one non-range/subnet is required.Please note that with the usage of wildcards multiple connection descriptions might match a given incomingconnection attempt. The most specific description is used in that case.","className":"form-control","name":"right","access":false,"subtype":"text","value": "'.$right.'"},{"type":"text","required":false,"label":"rightsubnet","description":"Private subnet behind the left participant, expressed as network/netmask; if omitted, essentially assumedto be left/32|128, signifying that the left|right end of the connection goes to the left|right participant only.The configured subnets of the peers may differ, the protocol narrows it to the greatest common subnet.Since 5.0.0 this is also done for IKEv1, but as this may lead to problems with other implementations,make sure to configure identical subnets in such configurations.IKEv2 supports multiple subnets separated by commas, IKEv1 only interprets the first subnet of such a definition,unless the Cisco Unity extension plugin is enabled (available since 5.0.1). This is due to a limitation of the IKEv1protocol, which only allows a single pair of subnets per CHILD_SA. So to tunnel several subnets a conn entry hasto be defined and brought up for each pair of subnets.Since 5.1.0 the optional part after each subnet enclosed in square brackets specifies a protocol/port to restrictthe selector for that subnet. Examples: leftsubnet=10.0.0.1[tcp/http],10.0.0.2[6/80] or leftsubnet=fec1::1[udp],10.0.0.0/16[/53].Instead of omitting either value %any can be used to the same effect, e.g. leftsubnet=fec1::1[udp/%any],10.0.0.0/16[%any/53].Since 5.1.1, if the protocol is icmp or ipv6-icmp the port is interpreted as ICMP message type if it is less than 256,or as type and code if it greater or equal to 256, with the type in the most significant 8 bits and the code in theleast significant 8 bits.The port value can alternatively take the value %opaque for RFC 4301 OPAQUE selectors, or a numerical rangein the form 1024-65535. None of the kernel backends currently supports opaque or port ranges and uses %anyfor policy installation instead.Instead of specifying a subnet, %dynamic can be used to replace it with the IKE address, having the same effectas omitting left|rightsubnet completely. Using %dynamic can be used to define multiple dynamic selectors,each having a potentially different protocol/port definition.","className":"form-control","name":"rightsubnet","access":false,"subtype":"text","value": "'.$rightsubnet.'"},{"type":"text","required":false,"label":"ike","description":"Comma-separated list of IKE/ISAKMP SA encryption/authentication algorithms to be used, e.g.aes128-sha256-modp3072. The notation is encryption-integrity[-prf]-dhgroup. In IKEv2, multiple algorithmsand proposals may be included, such as aes128-aes256-sha1-modp3072-modp2048,3des-sha1-md5-modp1024.The ability to configure a PRF algorithm different to that defined for integrity protection was added with 5.0.2.If no PRF is configured, the algorithms defined for integrity are proposed as PRF. The prf keywords are the same asthe integrity algorithms, but have a prf prefix (such as prfsha1, prfsha256 or prfaesxcbc).","className":"form-control","name":"ike","access":false,"subtype":"text","value":"aes256-sha2_256-modp1024!"},{"type":"text","required":false,"label":"ikelifetime","description":"Absolute time after which an IKE SA expires. Examples: 1h, 3600s, 28800s","className":"form-control","name":"ikelifetime","access":false,"subtype":"text","value":"28800"},{"type":"select","required":false,"label":"keyexchange","description":"Method of key exchange; which protocol should be used to initialize the connection.","className":"form-control","name":"keyexchange","access":false,"multiple":false,"values":[{"label":"ikev2","value":"ikev2","selected":true},{"label":"ikev1","value":"ikev1"}]},{"type":"text","required":false,"label":"esp","description":"Comma-separated list of ESP encryption/authentication algorithms to be used for the connection, e.g.aes128-sha256. The notation is encryption-integrity[-dhgroup][-esnmode].For IKEv2, multiple algorithms (separated by -) of the same type can be included in a single proposal.IKEv1 only includes the first algorithm in a proposal. Only either the ah or the esp keyword maybe used, AH+ESP bundles are not supported.","className":"form-control","name":"esp","access":false,"subtype":"text","value":"aes256-sha2_256!"},{"type": "number","required": true,"label": "dpddelay","description": "Defines the period time interval with which R_U_THERE messages/INFORMATIONAL exchanges are sent to the peer.These are only sent if no other traffic is received. In IKEv2, a value of 0 sends no additional INFORMATIONALmessages and uses only standard messages (such as those to rekey) to detect dead peers.","className": "form-control","name": "dpddelay","access": true,"value": "30","min": 0,"max": 9000,"step": 5},{"type": "select","required": true,"label": "dpdaction","className": "form-control","name": "dpdaction","access": true,"multiple": false,"values": [{"label": "none","value": "none"},{"label": "clear","value": "clear"},{"label": "hold","value": "hold"},{"label": "restart","value": "restart","selected": true}]}]';
    $saveparams=json_decode($saveparams, true);
    $PARAMS=base64_encode(serialize($saveparams));
    $sql="INSERT INTO strongswan_conns (`conn_name`,`params`,`order`,`enable`) VALUES ('{$_SESSION["WIZARDS2S"]["connection_name"]}','{$PARAMS}','0','1')";

    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error;
    } else {
        $lastid=$q->last_id;
        $sql="INSERT INTO strongswan_auth (`conn_id`,`selector`,`type`,`cert`,`secret`,`order`,`enable`)
			VALUES ('$lastid','','1','','{$_SESSION["WIZARDS2S"]["psk"]}','0','1');";
        $q->QUERY_SQL($sql);
        $_SESSION["POSTFORMED"]=serialize($_POST);
        //$sock->getFrameWork("strongswan.php?build-auth=yes");
        //build_progress();

        $_SESSION["WIZARDS2S"]=array();
    }
}
//END WIZARD S2S

//START WIZARD RW
function wizard_rw_js()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{wizard} - {NEW_RW_TUNNEL}", "$page?start-wizard-rw=yes", 650);
}
function wizard_rw_start()
{
    $page=CurrentPageName();
    echo "<div id='wizard-rw-for-steps'></div>
		<script>LoadAjax('wizard-rw-for-steps','$page?wizard-rw-step1=yes');</script>
	";
}

function wizard_rw_step1()
{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $tpl->field_hidden("WIZARDRW_TO_SAVE", 1);
    $form[]=$tpl->field_text("connection_name", "{conn_name}", $_SESSION["WIZARDRW"]["connection_name"], true, "{Conn_Explain}");
    $jsAfter="LoadAjax('wizard-rw-for-steps','$page?wizard-rw-step2=yes');";
    echo $tpl->form_outside("{Conn_Explain}", $form, null, "{next}", $jsAfter, "AsPostfixAdministrator");
}

function wizard_rw_step2()
{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $tpl->field_hidden("WIZARDRW_TO_SAVE", 1);
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $sql = "SELECT * FROM strongswan_certs";
    $LeftCert = $q->QUERY_SQL($sql);
    if (count($LeftCert)>0) {
        foreach ($LeftCert as $index=>$ligne) {
            $certs[$ligne["server_cert"]]="{$ligne["name"]}";
        }
        $form[]=$tpl->field_section("{certificate}", "{RW_CERT_EXPLAIN}");
        $form[]=$tpl->field_array_hash($certs, "certificate", "nonull:{certificate}", $_SESSION["WIZARDRW"]["certificate"], false, null);

        $form[]=$tpl->field_section("{source} ({local})", "{APP_STRONGSWAN_WIZARD_LEFTSECTION_EXPLAIN}<br>{RW_LEFT_EXPLAIN}");
        //$form[]=$tpl->field_ipaddr("left","{APP_STRONGSWAN_WIZARD_LEFT}",$_SESSION["WIZARDRW"]["left"],true);
        $form[]=$tpl->field_interfaces("left", "nooloopNoDef:{listen_interface}", $_SESSION["WIZARDRW"]["left"], true);

        //$form[]=$tpl->field_ipaddr("leftsubnet", "{APP_STRONGSWAN_WIZARD_LEFTSUB}", $_SESSION["WIZARDRW"]["leftsubnet"], false);
        //$form[]=$tpl->field_maskcdir("leftnetmask", "nonull:{netmask}", isset($_SESSION["WIZARDRW"]["leftnetmask"])?$_SESSION["WIZARDRW"]["leftnetmask"]:"24", true);

        $form[]=$tpl->field_section("{destination} ({remote})", "{APP_STRONGSWAN_WIZARD_RIGHTSECTION_EXPLAIN_2}<br>{RW_RIGHT_EXPLAIN}");
        $form[]=$tpl->field_ipaddr("right", "{APP_STRONGSWAN_WIZARD_RIGHT}", $_SESSION["WIZARDRW"]["right"], false);
        $form[]=$tpl->field_ipaddr("rightsubnet", "{RW_RIGHTSOURCEIP}", $_SESSION["WIZARDRW"]["rightsubnet"], false);

        $form[]=$tpl->field_maskcdir("rightnetmask", "nonull:{netmask}", isset($_SESSION["WIZARDRW"]["rightnetmask"])?$_SESSION["WIZARDRW"]["rightnetmask"]:"24", true);
        $jsAfter="LoadAjax('wizard-rw-for-steps','$page?wizard-rw-step3=yes');";

        // Back Button

        $tpl->form_add_button("{back}", "LoadAjax('wizard-rw-for-steps','$page?wizard-rw-step1=yes');");

        echo $tpl->form_outside("{APP_STRONGSWAN_WIZARD_STEP2}", $form, null, "{next}", $jsAfter, "AsPostfixAdministrator");
    } else {
        $form[]=$tpl->field_section("{error}", "You must create a certificate first!", true);
        $tpl->form_add_button("{back}", "LoadAjax('wizard-rw-for-steps','$page?wizard-rw-step1=yes');");
        echo $tpl->form_outside("{APP_STRONGSWAN_WIZARD_STEP2}", $form, null, null, null, "AsPostfixAdministrator");
    }
}

function wizard_rw_step3()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $psk= shell_exec("head -c 24 /dev/urandom | base64");
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $tunnelcert=$_SESSION["WIZARDRW"]["certificate"];
    $sql="SELECT server_key FROM strongswan_certs WHERE `server_cert`='$tunnelcert'";
    $usercert = $q->QUERY_SQL($sql);

    $tpl->field_hidden("WIZARDRW_TO_SAVE", 1);
    $form[]=$tpl->field_section("User {certificate}", "{RW_CERT_EXPLAIN_USER}");
    $form[]=$tpl->field_text("usercert", "{certificate}", $usercert[0]["server_key"], true, null, false, true);
    $form[]=$tpl->field_section("{STRONGSWAN_SELECTOR}", "{STRONGSWAN_SELECTOR_EXPLAIN}");
    $form[]=$tpl->field_text("selector", "{STRONGSWAN_SELECTOR}", $_SESSION["WIZARDRW"]["selector"], true);
    $form[]=$tpl->field_text("psk", "{STRONGSWAN_SECRET}", $_SESSION["WIZARDRW"]["psk"], true);
    $jsAfter="LoadAjax('wizard-rw-for-steps','$page?wizard-rw-step4=yes');";
    $tpl->form_add_button("{back}", "LoadAjax('wizard-rw-for-steps','$page?wizard-rw-step2=yes');");

    echo $tpl->form_outside("{APP_STRONGSWAN_WIZARD_STEP3}", $form, null, "{next}", $jsAfter, "AsPostfixAdministrator");
}


function wizard_rw_final()
{
    $unix=new unix();
    $left=$unix->InterfaceToIPv4($_SESSION["WIZARDRW"]["left"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $leftsubnet=!empty($_SESSION["WIZARDRW"]["leftsubnet"])?$_SESSION["WIZARDRW"]["leftsubnet"]."/".$_SESSION["WIZARDRW"]["leftnetmask"]:"0.0.0.0/0";
    $right=!empty($_SESSION["WIZARDRW"]["right"])?$_SESSION["WIZARDRW"]["right"]:"%any";
    $rightsubnet=!empty($_SESSION["WIZARDRW"]["rightsubnet"])?$_SESSION["WIZARDRW"]["rightsubnet"]."/".$_SESSION["WIZARDRW"]["rightnetmask"]:"%dhcp";
    $html[]="<div class='row'><div id='progress-strongswan-tunnels-restart'></div></div><h4><b>{APP_STRONGSWAN_WIZARD_STEP4}</b></h4><ul class=\"list-group\">
	<li class=\"list-group-item\"><b>{conn_name}:</b> {$_SESSION["WIZARDRW"]["connection_name"]}</li>
	<li class=\"list-group-item\"><b>{certificate}:</b> {$_SESSION["WIZARDRW"]["certificate"]}</li>
	<li class=\"list-group-item\"><b>{listen_interface}:</b> {$left} ({$_SESSION["WIZARDRW"]["left"]})</li>
	
	<li class=\"list-group-item\"><b>{APP_STRONGSWAN_WIZARD_RIGHT}:</b> {$right}</li>
	<li class=\"list-group-item\"><b>{APP_STRONGSWAN_WIZARD_RIGHTSUB}:</b> {$rightsubnet}</li>
	<li class=\"list-group-item\"><b>User {certificate}:</b> {$_SESSION["WIZARDRW"]["usercert"]}</li>
	<li class=\"list-group-item\"><b>User {STRONGSWAN_SELECTOR}:</b> {$_SESSION["WIZARDRW"]["selector"]}</li>
	<li class=\"list-group-item\"><b>{STRONGSWAN_PSK}:</b> {$_SESSION["WIZARDRW"]["psk"]}</li>
	</ul>
	";
    $jsAfter2="Loadjs('$page?build-progress=yes')";
    $tpl->field_hidden("WIZARDRW_FINAL", 1);
    $tpl->form_add_button("{back}", "LoadAjax('wizard-rw-for-steps','$page?wizard-rw-step3=yes');");
    $html[]=$tpl->form_outside(null, $form, null, "{save}", $jsAfter2, "AsPostfixAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
}

function wizard_rw_save()
{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    foreach ($_POST as $key=>$value) {
        $_SESSION["WIZARDRW"][$key]=$value;
    }
    if (isset($_POST["connection_name"])) {
        $sql1=$q->mysqli_fetch_array("SELECT COUNT(*) as count FROM strongswan_conns WHERE `conn_name`='{$_SESSION["WIZARDRW"]["connection_name"]}'");
        $Items=$sql1['count'];

        if ($Items>0) {
            echo "jserror: The connection name already exist, please choose a new one\n";
        }
    }

    if (isset($_POST["selector"])) {
        $sql2=$q->mysqli_fetch_array("SELECT COUNT(*) as count FROM strongswan_auth WHERE `selector`='{$_SESSION["WIZARDRW"]["selector"]}'");
        $Items2=$sql2['count'];

        if ($Items2>0) {
            echo "jserror: The selector name is already in use by other tunnel, please choose a new one\n";
        }
    }

    if(isset($_POST['certificate'])){
        $checkdupscerts="SELECT * from strongswan_conns";
        $res=$q->QUERY_SQL($checkdupscerts);
        foreach ($res as $index=>$ligne) {
            $parms=unserialize(base64_decode("{$ligne['params']}"));
            foreach ($parms as $key) {
                if ($key['name']=='leftcert') {
                    if ($key['type']=='select') {
                        $_values= getVal($key["values"]);
                        //echo $_values;
                        if ($_values==$_SESSION["WIZARDRW"]["certificate"].'.pem') {
                            echo "jserror: The certificate is already in use by the other tunnel, plese select a new one";
                            return;
                        }
                    }
                }
            }
        }
    }

    if (isset($_POST["usercert"])) {
        $sql3=$q->mysqli_fetch_array("SELECT COUNT(*) as count FROM strongswan_auth WHERE `cert`='{$_SESSION["WIZARDRW"]["usercert"]}.pem'");
        $Items3=$sql3['count'];

        if ($Items3>0) {
            echo "jserror: The certificate is already in use by other tunnel, please choose a new one\n";
        }
    }
}

function wizard_rw_final_save()
{
    $unix=new unix();
    $sock=new sockets();
    //$sock->SET_INFO("IpsecWizard",serialize($_SESSION["WIZARDRW"]));
    $conn_name=$_SESSION["WIZARDRW"]["connection_name"];
    $left=$unix->InterfaceToIPv4($_SESSION["WIZARDRW"]["left"]);
    //$leftsubnet=!empty($_SESSION["WIZARDRW"]["leftsubnet"])?$_SESSION["WIZARDRW"]["leftsubnet"]."/".$_SESSION["WIZARDRW"]["leftnetmask"]:"0.0.0.0/0";
    $right=!empty($_SESSION["WIZARDRW"]["right"])?$_SESSION["WIZARDRW"]["right"]:"%any";
    $rightsubnet=!empty($_SESSION["WIZARDRW"]["rightsubnet"])?$_SESSION["WIZARDRW"]["rightsubnet"]."/".$_SESSION["WIZARDRW"]["rightnetmask"]:"%dhcp";
    $cert="{$_SESSION["WIZARDRW"]["certificate"]}.pem";
    $net=new system_nic($_SESSION["WIZARDRW"]["left"]);
    $netmask=$net->NETMASK;
    $ipaddr=$net->IPADDR;
    $IpClass=new IP();
    //$leftsubnet=$IpClass->maskTocdir($ipaddr, $netmask);
    $leftsubnet="0.0.0.0/0";
    $saveparams='[{"type": "text","required": true,"label": "connection name","description": "Name of connection. Don`t use special characters or spaces. Example: siteX-to-siteY","className": "form-control","name": "conn","access": true,"value": "'.$conn_name.'","subtype": "text"},{"type": "select","required": true,"label": "auto","description": "What operation, if any, should be done automatically at IPsec startup. add loads a connection withoutstarting it. route loads a connection and installs kernel traps. If traffic is detected betweenleftsubnet and rightsubnet, a connection is established. start loads a connection and bringsit up immediately. ignore ignores the connection. This is equal to deleting a connection from the configfile. Relevant only locally, other end need not agree on it.","className": "form-control","name": "auto","access": true,"multiple": false,"values": [{"label": "ignore","value": "ignore"},{"label": "add","value": "add","selected": true},{"label": "route","value": "route"},{"label": "start","value": "start"}]},{"type": "select","required": true,"label": "compress","description": "Whether IPComp compression of content is proposed on the connection (link-level compression does not work onencrypted data, so to be effective, compression must be done before encryption). A value of yes causes the daemonto propose both compressed and uncompressed, and prefer compressed. A value of no prevents the daemon from proposing or accepting compression.","className": "form-control","name": "compress","access": true,"multiple": false,"values": [{"label": "yes","value": "yes"},{"label": "no","value": "no","selected": true}]},{"type": "select","required": true,"label": "type","description": "The type of the connection; currently the accepted values are Tunnel: signifying a host-to-host,host-to-subnet, or subnet-to-subnet tunnel;Transport: signifying host-to-host transport mode;Transport_proxy: signifying the special Mobile IPv6 transport proxy mode;Passthrough: signifying that no IPsec processing should be done at all;Drop, signifying that packetsshould be discarded.","className": "form-control","name": "type","access": true,"multiple": false,"values": [{"label": "tunnel","value": "tunnel","selected": true},{"label": "transport","value": "transport"},{"label": "transport_proxy","value": "transport_proxy"},{"label": "passthrough","value": "passthrough"},{"label": "drop","value": "drop"}]},{"type": "select","required": true,"label": "keyexchange","description": "Method of key exchange; which protocol should be used to initialize the connection.","className": "form-control","name": "keyexchange","access": true,"multiple": false,"values": [{"label": "ikev2","value": "ikev2","selected": true},{"label": "ikev1","value": "ikev1"}]},{"type": "select","required": true,"label": "fragmentation","description": "Fhether to use IKE fragmentation (proprietary IKEv1 extension or IKEv2 fragmentation as per RFC 7383).Fragmented messages sent by a peer are always processed irrespective of the value of this option (even when set to no).If set to yes (the default since 5.5.1) and the peer supports it, oversized IKE messages will be sent in fragments (themaximum fragment size can be configured in strongswan.conf). If set to accept (available since 5.5.3) support forfragmentation is announced to the peer but the daemon does not send its own messages in fragments.If set to force (only supported for IKEv1) the initial IKE message will already be fragmented if required.Available for IKEv1 connections since 5.0.2 and for IKEv2 connections since 5.2.1.","className": "form-control","name": "fragmentation","access": true,"multiple": false,"values": [{"label": "yes","value": "yes","selected": true},{"label": "no","value": "no"},{"label": "accept","value": "accept"},{"label": "force","value": "force"}]},{"type": "select","required": true,"label": "forceencaps","description": "Force UDP encapsulation for ESP packets even if no NAT situation is detected.This may help to surmount restrictive firewalls. In order to force the peer toencapsulate packets, NAT detection payloads are faked.Not supported for IKEv1 connections prior to 5.0.0.","className": "form-control","name": "forceencaps","access": true,"multiple": false,"values": [{"label": "yes","value": "yes","selected": true},{"label": "no","value": "no"}]},{"type": "select","required": true,"label": "dpdaction","className": "form-control","name": "dpdaction","access": true,"multiple": false,"values": [{"label": "none","value": "none"},{"label": "clear","value": "clear"},{"label": "hold","value": "hold"},{"label": "restart","value": "restart","selected": true}]},{"type": "number","required": true,"label": "dpddelay","description": "Defines the period time interval with which R_U_THERE messages/INFORMATIONAL exchanges are sent to the peer.These are only sent if no other traffic is received. In IKEv2, a value of 0 sends no additional INFORMATIONALmessages and uses only standard messages (such as those to rekey) to detect dead peers.","className": "form-control","name": "dpddelay","access": true,"value": "300","min": 0,"max": 9000,"step": 5},{"type": "select","required": true,"label": "rekey","description": "Whether a connection should be renegotiated when it is about to expire. The two ends need not agree, butwhile a value of no prevents the daemon from requesting renegotiation, it does not prevent respondingto renegotiation requested from the other end, so no will be largely ineffective unless both ends agree on it.","className": "form-control","name": "rekey","access": true,"multiple": false,"values": [{"label": "yes","value": "yes"},{"label": "no","value": "no","selected": true}]},{"type": "text","required": true,"label": "left","description": "The IP address of the participant`s public-network interface or one of several magic values.The value %any for the local endpoint signifies an address to be filled in(by automatic keying) during negotiation. If the local peer initiates the connection setup the routing tablewill be queried to determine the correct local IP address. In case the local peer is responding to a connectionsetup then any IP address that is assigned to a local interface will be accepted. The value %any4 restrictsaddress selection to IPv4 addresses, the value %any6 reistricts address selection to IPv6 addresses.Prior to 5.0.0 specifying %any for the local endpoint was not supported for IKEv1 connections, insteadthe keyword %defaultroute could be used, causing the value to be filled in automatically with the localaddress of the default-route interface (as determined at IPsec startup time and during configurationupdate). Either left or right may be %defaultroute, but not both.The prefix % in front of a fully-qualified domain name or an IP address will implicitly set left|rightallowany=yes.If %any is used for the remote endpoint it literally means any IP address.If an FQDN is assigned it is resolved every time a configuration lookup is done. If DNS resolution times out,the lookup is delayed for that time.Since 5.1.1 connections can be limited to a specific range of hosts. To do so a range (10.1.0.0-10.2.255.255)or a subnet (10.1.0.0/16) can be specified, and multiple addresses, ranges and subnets can be separated by commas.While one can freely combine these items, to initiate the connection at least one non-range/subnet is required.Please note that with the usage of wildcards multiple connection descriptions might match a given incomingconnection attempt. The most specific description is used in that case.","className": "form-control","name": "left","access": true,"value": "'.$left.'","subtype": "text"},{"type": "text","required": true,"label": "leftid","description": "How the left|right participant should be identified for authentication; defaults to left|right or the subject of the certificate configured with left|rightcert. If left|rightcert is configured the identity has to be confirmed by the certificate, that is, it has to match the full subject DN or one of the subjectAltName extensions contained in the certificate.Can be an IP address, a fully-qualified domain name, an email address or a Distinguished Name for which theID type is determined automatically and the string is converted to the appropriate encoding.","className": "form-control","name": "leftid","access": true,"value": "'.$left.'","subtype": "text"},{"type": "select","required": true,"label": "leftsendcert","description": "Accepted values are never or no, always or yes, and ifasked, the latter meaning thatthe peer must send a certificate request (CR) payload in order to get a certificate in return.","className": "form-control","name": "leftsendcert","access": true,"multiple": false,"values": [{"label": "never","value": "never"},{"label": "no","value": "no"},{"label": "ifasked","value": "ifasked"},{"label": "always","value": "always","selected": true},{"label": "yes","value": "yes"}]},{"type": "select","required": true,"label": "leftcert","description": "The path to the left|right participant`s X.509 certificate. The file can be coded either in PEM or DER format.","className": "form-control","name": "leftcert","access": true,"multiple": false,"values": [{"label": "MYCERT","value": "'.$cert.'","selected": true}]},{"type": "text","required": true,"label": "leftsubnet","description": "Private subnet behind the left participant, expressed as network/netmask; if omitted, essentially assumedto be left/32|128, signifying that the left|right end of the connection goes to the left|right participant only.The configured subnets of the peers may differ, the protocol narrows it to the greatest common subnet.Since 5.0.0 this is also done for IKEv1, but as this may lead to problems with other implementations,make sure to configure identical subnets in such configurations.IKEv2 supports multiple subnets separated by commas, IKEv1 only interprets the first subnet of such a definition,unless the Cisco Unity extension plugin is enabled (available since 5.0.1). This is due to a limitation of the IKEv1protocol, which only allows a single pair of subnets per CHILD_SA. So to tunnel several subnets a conn entry hasto be defined and brought up for each pair of subnets.Since 5.1.0 the optional part after each subnet enclosed in square brackets specifies a protocol/port to restrictthe selector for that subnet. Examples: leftsubnet=10.0.0.1[tcp/http],10.0.0.2[6/80] or leftsubnet=fec1::1[udp],10.0.0.0/16[/53].Instead of omitting either value %any can be used to the same effect, e.g. leftsubnet=fec1::1[udp/%any],10.0.0.0/16[%any/53].Since 5.1.1, if the protocol is icmp or ipv6-icmp the port is interpreted as ICMP message type if it is less than 256,or as type and code if it greater or equal to 256, with the type in the most significant 8 bits and the code in theleast significant 8 bits.The port value can alternatively take the value %opaque for RFC 4301 OPAQUE selectors, or a numerical rangein the form 1024-65535. None of the kernel backends currently supports opaque or port ranges and uses %anyfor policy installation instead.Instead of specifying a subnet, %dynamic can be used to replace it with the IKE address, having the same effectas omitting left|rightsubnet completely. Using %dynamic can be used to define multiple dynamic selectors,each having a potentially different protocol/port definition.","className": "form-control","name": "leftsubnet","access": true,"value": "'.$leftsubnet.'","subtype": "text"},{"type": "text","required": true,"label": "right","description": "The IP address of the participant`s public-network interface or one of several magic values.The value %any for the local endpoint signifies an address to be filled in(by automatic keying) during negotiation. If the local peer initiates the connection setup the routing tablewill be queried to determine the correct local IP address. In case the local peer is responding to a connectionsetup then any IP address that is assigned to a local interface will be accepted. The value %any4 restrictsaddress selection to IPv4 addresses, the value %any6 reistricts address selection to IPv6 addresses.Prior to 5.0.0 specifying %any for the local endpoint was not supported for IKEv1 connections, insteadthe keyword %defaultroute could be used, causing the value to be filled in automatically with the localaddress of the default-route interface (as determined at IPsec startup time and during configurationupdate). Either left or right may be %defaultroute, but not both.The prefix % in front of a fully-qualified domain name or an IP address will implicitly set left|rightallowany=yes.If %any is used for the remote endpoint it literally means any IP address.If an FQDN is assigned it is resolved every time a configuration lookup is done. If DNS resolution times out,the lookup is delayed for that time.Since 5.1.1 connections can be limited to a specific range of hosts. To do so a range (10.1.0.0-10.2.255.255)or a subnet (10.1.0.0/16) can be specified, and multiple addresses, ranges and subnets can be separated by commas.While one can freely combine these items, to initiate the connection at least one non-range/subnet is required.Please note that with the usage of wildcards multiple connection descriptions might match a given incomingconnection attempt. The most specific description is used in that case.","className": "form-control","name": "right","access": true,"value": "'.$right.'","subtype": "text"},{"type": "text","required": true,"label": "rightid","description": "How the left|right participant should be identified for authentication; defaults to left|right or the subject of the certificate configured with left|rightcert. If left|rightcert is configured the identity has to be confirmed by the certificate, that is, it has to match the full subject DN or one of the subjectAltName extensions contained in the certificate.Can be an IP address, a fully-qualified domain name, an email address or a Distinguished Name for which theID type is determined automatically and the string is converted to the appropriate encoding.","className": "form-control","name": "rightid","access": true,"value": "'.$right.'","subtype": "text"},{"type": "select","required": true,"label": "rightauth","description": "Authentication method to use locally (left) or require from the remote (right) side. Acceptable values are pubkeyfor public key encryption (RSA/ECDSA), psk for pre-shared key authentication, eap to [require the] use of the Extensible Authentication Protocol, and xauth for IKEv1 eXtended Authentication.","className": "form-control","name": "rightauth","access": true,"multiple": false,"values": [{"label": "pubkey","value": "pubkey"},{"label": "psk","value": "psk"},{"label": "eap","value": "eap"},{"label": "xauth","value": "xauth"},{"label": "eap-mschapv2","value": "eap-mschapv2","selected": true}]},{"type": "text","required": true,"label": "rightsourceip","description": "The internal source IP to use in a tunnel for the remote peer. If the value is config on the responderside, the initiator must propose an address which is then echoed back. Also supported are address poolsexpressed as / and - (since 5.2.2) or the use of an external IP address poolusing %poolname where poolname is the name of the IP address pool used for the lookup (see virtual IP for details).Since 5.0.1 a comma-separated list of IP addresses / pools is accepted, for instance, to define pools ofdifferent address families.","className": "form-control","name": "rightsourceip","access": true,"value": "'.$rightsubnet.'","subtype": "text"},{"type": "text","required": true,"label": "rightdns","description": "Comma separated list of DNS server addresses to exchange as configuration attributes. On the initiator,a server is a fixed IPv4/IPv6 address, or %config4/%config6 to request attributes without an address.On the responder, only fixed IPv4/IPv6 addresses are allowed and define DNS servers assigned to the client.","className": "form-control","name": "rightdns","access": true,"value": "8.8.8.8,8.8.4.4","subtype": "text"},{"type": "select","required": true,"label": "rightsendcert","description": "Accepted values are never or no, always or yes, and ifasked, the latter meaning thatthe peer must send a certificate request (CR) payload in order to get a certificate in return.","className": "form-control","name": "rightsendcert","access": true,"multiple": false,"values": [{"label": "never","value": "never","selected": true},{"label": "no","value": "no"},{"label": "ifasked","value": "ifasked"},{"label": "always","value": "always"},{"label": "yes","value": "yes"}]},{"type": "text","required": true,"label": "eap_identity","description": "Defines the identity the client uses to reply to an EAP Identity request. If defined on the EAP server, the defined identity will be used as peer identity during EAP authentication. The special value %identity uses the EAP Identity method to ask the client for a EAP identity. If not defined, the IKEv2 identity will be used as EAP identity.","className": "form-control","name": "eap_identity","access": true,"value": "%identity","subtype": "text"},{"type":"text","required":false,"label":"ike","description":"Comma-separated list of IKE/ISAKMP SA encryption/authentication algorithms to be used, e.g.aes128-sha256-modp3072. The notation is encryption-integrity[-prf]-dhgroup. In IKEv2, multiple algorithmsand proposals may be included, such as aes128-aes256-sha1-modp3072-modp2048,3des-sha1-md5-modp1024.The ability to configure a PRF algorithm different to that defined for integrity protection was added with 5.0.2.If no PRF is configured, the algorithms defined for integrity are proposed as PRF. The prf keywords are the same asthe integrity algorithms, but have a prf prefix (such as prfsha1, prfsha256 or prfaesxcbc).","className":"form-control","name":"ike","access":false,"subtype":"text","value":"aes128-sha1-modp1024,aes128-sha1-modp1536,aes128-sha1-modp2048,aes128-sha256-ecp256,aes128-sha256-modp1024,aes128-sha256-modp1536,aes128-sha256-modp2048,aes256-aes128-sha256-sha1-modp2048-modp4096-modp1024,aes256-sha1-modp1024,aes256-sha256-modp1024,aes256-sha256-modp1536,aes256-sha256-modp2048,aes256-sha256-modp4096,aes256-sha384-ecp384,aes256-sha384-modp1024,aes256-sha384-modp1536,aes256-sha384-modp2048,aes256-sha384-modp4096,aes256gcm16-aes256gcm12-aes128gcm16-aes128gcm12-sha256-sha1-modp2048-modp4096-modp1024,3des-sha1-modp1024!"},{"type":"text","required":false,"label":"esp","description":"Comma-separated list of ESP encryption/authentication algorithms to be used for the connection, e.g.aes128-sha256. The notation is encryption-integrity[-dhgroup][-esnmode].For IKEv2, multiple algorithms (separated by -) of the same type can be included in a single proposal.IKEv1 only includes the first algorithm in a proposal. Only either the ah or the esp keyword maybe used, AH+ESP bundles are not supported.","className":"form-control","name":"esp","access":false,"subtype":"text","value":"aes128-aes256-sha1-sha256-modp2048-modp4096-modp1024,aes128-sha1,aes128-sha1-modp1024,aes128-sha1-modp1536,aes128-sha1-modp2048,aes128-sha256,aes128-sha256-ecp256,aes128-sha256-modp1024,aes128-sha256-modp1536,aes128-sha256-modp2048,aes128gcm12-aes128gcm16-aes256gcm12-aes256gcm16-modp2048-modp4096-modp1024,aes128gcm16,aes128gcm16-ecp256,aes256-sha1,aes256-sha256,aes256-sha256-modp1024,aes256-sha256-modp1536,aes256-sha256-modp2048,aes256-sha256-modp4096,aes256-sha384,aes256-sha384-ecp384,aes256-sha384-modp1024,aes256-sha384-modp1536,aes256-sha384-modp2048,aes256-sha384-modp4096,aes256gcm16,aes256gcm16-ecp384,3des-sha1!"}]';
    $saveparams=json_decode($saveparams, true);
    $PARAMS=base64_encode(serialize($saveparams));
    $sql="INSERT INTO strongswan_conns (`conn_name`,`params`,`order`,`enable`) VALUES ('{$_SESSION["WIZARDRW"]["connection_name"]}','{$PARAMS}','0','1')";

    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error;
    } else {
        $lastid=$q->last_id;
        $sql="INSERT INTO strongswan_auth (`conn_id`,`selector`,`type`,`cert`,`secret`,`order`,`enable`)
			VALUES ('$lastid','{$_SESSION["WIZARDRW"]["selector"]}','5','','{$_SESSION["WIZARDRW"]["psk"]}','1','1');";
        $sql2="INSERT INTO strongswan_auth (`conn_id`,`selector`,`type`,`cert`,`secret`,`order`,`enable`)
		VALUES ('$lastid','','3','{$_SESSION["WIZARDRW"]["usercert"]}.pem','','0','1');";
        $q->QUERY_SQL($sql);
        $q->QUERY_SQL($sql2);
        $_SESSION["POSTFORMED"]=serialize($_POST);
        //$sock->getFrameWork("strongswan.php?build-auth=yes");
        //build_progress();

        $_SESSION["WIZARDRW"]=array();
    }
}
//END WIZARD RW
function ip_in_range($ip, $range)
{
    if (strpos($range, '/') == false) {
        $range .= '/32';
    }

    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}
function build_progress()
{
    $DATAS=unserialize($_SESSION["POSTFORMED"]);
    //print_r($DATAS);
    $page=CurrentPageName();

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.build.progress"; // Give percentage as an arra
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/strongswan.build.log";
    $ARRAY["CMD"]="strongswan.php?build-tunnels=yes";
    $ARRAY["TITLE"]="{reconfigure_service} {APP_STRONGSWAN}";
    $ARRAY["AFTER"]="BootstrapDialog1.close();dialogInstance2.close();LoadAjax('table-loader','$page?build-table=yes');";


    $prgress=base64_encode(serialize($ARRAY)); // Array is compiled
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-strongswan-tunnels-restart')";

    echo $jsrestart;
}

function conn_move()
{
    $tpl=new template_admin();
    $ID=$_GET["conn-rule-move"];
    $TID=$_GET["tunnel-id"];
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $sql="SELECT `order` FROM strongswan_conns WHERE `ID`='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    $xORDER_ORG=intval($ligne["order"]);

    $xORDER=$xORDER_ORG;

    if ($_GET["conn-rule-dir"]==1) {
        $xORDER=$xORDER_ORG-1;
    }
    if ($_GET["conn-rule-dir"]==0) {
        $xORDER=$xORDER_ORG+1;
    }

    $sql="UPDATE strongswan_conns SET `order`=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);

    $sql="UPDATE strongswan_conns SET
    `order`=$xORDER_ORG WHERE `ID`<>'$ID' AND `order`=$xORDER ";
    $q->QUERY_SQL($sql);

    $c=1;
    $sql="SELECT * FROM strongswan_conns ORDER BY `order`";
    $results = $q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE strongswan_conns SET `order`=$c WHERE `ID`={$ligne["ID"]}");
        $c++;
    }
    $page=CurrentPageName();
    // echo "LoadAjax('table-loader','$page?build-table=yes');";
    // $sock=new sockets();
    // $sock->getFrameWork("strongswan.php?build-tunnels=yes");
    echo "Loadjs('$page?build-progress=yes');";
}

function conn_enable()
{
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    header("content-type: application/x-javascript");
    $ligne=$q->mysqli_fetch_array("SELECT enable FROM strongswan_conns WHERE ID='{$_GET["enable-js"]}'");
    if (intval($ligne["enable"])==0) {
        $enabled=1;
    } else {
        $enabled=0;
    }

    $q->QUERY_SQL("UPDATE strongswan_conns SET enable='$enabled' WHERE ID='{$_GET["enable-js"]}'");
    if (!$q->ok) {
        echo "alert('".$q->mysql_error."')";
        return;
    }

    echo "Loadjs('$page?build-progress=yes');";
    //$sock=new sockets();
    //$sock->getFrameWork("strongswan.php?build-tunnels=yes");
}
function export_conf(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    header("content-type: application/x-javascript");
    $tunnel=$q->mysqli_fetch_array("SELECT * FROM strongswan_conns WHERE ID='{$_GET["download-js"]}'");
    $auth="SELECT selector, FROM strongswan_auth WHERE conn_id='{$_GET["download-js"]}' ORDER BY `order` ASC";
    @mkdir("/tmp/ipsec-conn-{$_GET["download-js"]}", 0755, true);


}

function page()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-4\"><h1 class=ng-binding>{APP_STRONGSWAN_TUNNELS}</h1></div>
	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>
	<div id='table-loader'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/ipsec-tunnels');	
	function ss$t(){
		LoadAjax('table-loader','$page?search=yes');
	}
ss$t();
</script>";
    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Tunnels',$html);echo $tpl->build_firewall();return;}

    echo $tpl->_ENGINE_parse_body($html);
}
function delete_js()
{
    $ID=$_GET["delete-js"];
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ligne=$q->mysqli_fetch_array("SELECT `conn_name` FROM strongswan_conns WHERE ID='$ID'");
    $types=Hash_method();
    $title="{$ligne["conn_name"]}";
    $tpl->js_dialog_confirm("{delete} $title ?", "$page?delete-confirm=$ID");
}
function delete_confirm()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $t=time();
    $ID=$_GET["delete-confirm"];
    $types=Hash_method();
    $ligne=$q->mysqli_fetch_array("SELECT `conn_name` FROM strongswan_conns WHERE ID='$ID'");
    $title="{$ligne["conn_name"]}";

    $html="
	<div class=row>
	<div class=\"alert alert-danger\">{delete} $ID {method} $title ?</div>
	<div style='text-align:right;margin-top:20px'><button class='btn btn-danger btn-lg' type='button'
	OnClick=\"javascript:Remove$t()\">{yes_delete_it}</button></div>
	</div>
	<script>
	var xPost$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	DialogConfirm.close();
	//LoadAjax('table-loader','$page?table=yes');
	Loadjs('$page?build-progress=yes')
}

function Remove$t(){
var XHR = new XHRConnection();
XHR.appendData('delete-remove', '$ID');
XHR.sendAndLoad('$page', 'POST',xPost$t);
}
</script>
";
    echo $tpl->_ENGINE_parse_body($html);
}
function delete_remove()
{
    $ID=$_POST["delete-remove"];
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $q->QUERY_SQL("DELETE FROM strongswan_conns WHERE ID='$ID'");
    if (!$q->ok) {
        echo "Error line:".__LINE__."\n".$q->mysql_error;
        return;
    }
    $q->QUERY_SQL("DELETE FROM strongswan_auth WHERE `conn_id`='$ID'");
    if (!$q->ok) {
        echo "Error line:".__LINE__."\n".$q->mysql_error;
        return;
    }
    $sock=new sockets();

    $sock->getFrameWork("strongswan.php?unlink-auth-file=yes&fid={$ID}");
    sleep(2);
    $sock->getFrameWork("strongswan.php?unlink-conf-file=yes&fid={$ID}");
    //sleep(2);
    //$sock->getFrameWork("strongswan.php?build-tunnels=yes");
    //echo "Loadjs('$page?build-progress=yes');";
}
function Hash_method()
{
    $users=new usersMenus();

    //$array[0]="{local} MySQL";
    if ($users->openldap_installed) {
        $array[1]="{local} LDAP";
    }
    $EnableKerbAuth=@intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if ($EnableKerbAuth==1) {
        $array[2]="{current_active_directory}";
    }
    $array[3]="{external_activedirectory_group}";
    return $array;
}

function rule_js()
{
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $tpl=new template_admin();
    $ruleid=intval($_GET["ruleid-js"]);
    if ($ruleid==0) {
        $title="{new_ipsec_tunnel}";
    } else {
        $ligne=$q->mysqli_fetch_array("SELECT * FROM strongswan_conns WHERE ID='$ruleid'");
        $title="{$ligne["conn_name"]}";
    }
    $tpl->js_dialog($title, "$page?rule-popup=$ruleid");
}


function rule_popup()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");




    $array["{parameters}"]="$page?rule-settings=$ID";
    if ($ID>0) {
        $array["{authentication}"]="fw.strongswan.auth.php?rule-id=$ID";
        //$array["{certificate}"]="fw.strongswan.certs.php?rule-id=$ID";
    }
    echo $tpl->tabs_default($array);
}
function rule_settings()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-settings"]);
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $btname="{add}";
    $BootstrapDialog=null;
    $types=Hash_method();
    $title="{new_ipsec_tunnel}";
    $LeftRight_Explain=$tpl->javascript_parse_text("{LeftRight_Explain}");
    $Authby_Explain=$tpl->javascript_parse_text("{Authby_Explain}");
    $Conn_Explain=$tpl->javascript_parse_text("{Conn_Explain}");
    $LeftRightSubnet_Explain=$tpl->javascript_parse_text("{LeftRightSubnet_Explain}");
    $LeftRightId_Explain=$tpl->javascript_parse_text("{LeftRightId_Explain}");
    $Auto_Explain=$tpl->javascript_parse_text("{Auto_Explain}");
    $Type_Explain=$tpl->javascript_parse_text("{Type_Explain}");
    $Keyexchange_Explain=$tpl->javascript_parse_text("{Keyexchange_Explain}");
    $Ike_Explain=$tpl->javascript_parse_text("{Ike_Explain}");
    $Esp_Explain=$tpl->javascript_parse_text("{Esp_Explain}");
    $Keyingtries_Explain=$tpl->javascript_parse_text("{Keyingtries_Explain}");
    $Ikelifetime_Explain=$tpl->javascript_parse_text("{Ikelifetime_Explain}");
    $Lifetime_Explain=$tpl->javascript_parse_text("{Lifetime_Explain}");
    $Dpdtimeout_Explain=$tpl->javascript_parse_text("{Dpdtimeout_Explain}");
    $Dpddelay_Explain=$tpl->javascript_parse_text("{Dpddelay_Explain}");
    $Dpdaction_Explain=$tpl->javascript_parse_text("{Dpdaction_Explain}");
    $Compress_Explain=$tpl->javascript_parse_text("{Compress_Explain}");
    $Aggressive_Explain=$tpl->javascript_parse_text("{Aggressive_Explain}");
    $LeftRightId_Explain=$tpl->javascript_parse_text("{LeftRightId_Explain}");
    $Rightsourceip_Explain=$tpl->javascript_parse_text("{Rightsourceip_Explain}");
    $Xauth_Explain=$tpl->javascript_parse_text("{Xauth_Explain}");
    $LeftRightauth_Explain=$tpl->javascript_parse_text("{LeftRightauth_Explain}");
    $LeftRightauth2_Explain=$tpl->javascript_parse_text("{LeftRightauth2_Explain}");
    $Leftsourceip_Explain=$tpl->javascript_parse_text("{Leftsourceip_Explain}");
    $Xauthidentity_Explain=$tpl->javascript_parse_text("{Xauthidentity_Explain}");
    $Margintime_Explain=$tpl->javascript_parse_text("{Margintime_Explain}");
    $LeftRightFirewall_Explain=$tpl->javascript_parse_text("{LeftRightFirewall_Explain}");
    $LeftRightSendcert_Explain=$tpl->javascript_parse_text("{LeftRightSendcert_Explain}");
    $EapIdentity_Explain=$tpl->javascript_parse_text("{EapIdentity_Explain}");
    $Fragmentation_Explain=$tpl->javascript_parse_text("{Fragmentation_Explain}");
    $Forceencaps_Explain=$tpl->javascript_parse_text("{Forceencaps_Explain}");
    $Rekey_Explain=$tpl->javascript_parse_text("{Rekey_Explain}");
    $LeftRightDNS_Explain=$tpl->javascript_parse_text("{LeftRightDNS_Explain}");
    $STRONSWAN_BLANK=$tpl->javascript_parse_text("{STRONSWAN_BLANK}");
    $STRONSWAN_LOAD_TEMPLATES=$tpl->javascript_parse_text("{STRONSWAN_LOAD_TEMPLATES}");
    $STRONSWAN_FILTER_PARAMETERS=$tpl->javascript_parse_text("{STRONSWAN_FILTER_PARAMETERS}");
    $STRONSWAN_SITE_TO_SITE=$tpl->javascript_parse_text("{STRONSWAN_SITE_TO_SITE}");
    $STRONSWAN_REMOTE_ACCESS=$tpl->javascript_parse_text("{STRONSWAN_REMOTE_ACCESS}");
    $LeftRightCert_Explain=$tpl->javascript_parse_text("{LeftRightCert_Explain}");
    $APP_STRONGSWAN_TUNNELS=$tpl->javascript_parse_text("{APP_STRONGSWAN_TUNNELS}");
    $sql = "SELECT * FROM strongswan_certs";
    $LeftRightCert = $q->QUERY_SQL($sql);
    if (count($LeftRightCert)>0) {
        $i = 0;

        foreach ($LeftRightCert as $index=>$ligne) {
            if ($i==0) {
                $LeftRightCertValue="{
				'label':'{$ligne["name"]}',
				'value':'{$ligne["server_cert"]}.pem',
				'selected': true
			},";
            } else {
                $LeftRightCertValue.="{
					'label':'{$ligne["name"]}',
					'value':'{$ligne["server_cert"]}.pem'
				},";
            }
            $i++;
        }
        $LeftCert="{
			label: 'leftcert',
			name: 'leftcert',
			description:'{$LeftRightCert_Explain}',
			'values': [
				$LeftRightCertValue
			],
			attrs: {
			  type: 'select',
			},

		},
	";

        $RightCert="{
		label: 'rightcert',
		name: 'rightcert',
		description:'{$LeftRightCert_Explain}',
		'values': [
			$LeftRightCertValue
		],
		attrs: {
		  type: 'select',
		},

	},
";
    }

    if ($ID>0) {
        $ligne=$q->mysqli_fetch_array("SELECT * FROM strongswan_conns WHERE ID='$ID'");
        $PARAMS_SERIALIZED=unserialize(base64_decode("{$ligne['params']}"));
        foreach ($PARAMS_SERIALIZED as &$row) {
            if (array_key_exists('multiple', $row)) {
                $row["multiple"] = false;
            }
            if (array_key_exists('values', $row)) {
                foreach ($row['values'] as &$k) {
                    if (array_key_exists('selected', $k)) {
                        $k["selected"] = (bool)$k["selected"];
                    }
                }
            }
        }

        $json_data = json_encode($PARAMS_SERIALIZED);
        $title="{$ligne["conn_name"]}";

        $html[]="
		<div class=\"row border-bottom white-bg dashboard-header\">
		<div ><h4 class=ng-binding>$APP_STRONGSWAN_TUNNELS</h4></div>
		</div>
		<div class='row'><div id='progress-strongswan-tunnels-restart'></div>
		<div class='ibox-content'>
		<div class='col-sm-9'></div>
		<div class=''>
			
			<div class=\"form-group footable-filtering-search\"><label class=\"sr-only\">{$STRONSWAN_FILTER_PARAMETERS}</label><div class=\"input-group\"><input type=\"text\" class=\"form-control\" style=\"float:right\" placeholder=\"{$STRONSWAN_FILTER_PARAMETERS}\"  id='searchParams'><div class=\"input-group-btn\"><button type=\"button\" class=\"btn btn-primary\"><span class=\"fooicon fooicon-search\"></span></button></div></div></div>
		</div>
	
		</div>
		</div>




	
	<div id='build-wrap'></div>
	<script>
	jQuery($ => {
		$('.get-data').css('display', 'none');	
	var fields = [
		$LeftCert
		$RightCert
		{
			label: 'authby',
			name: 'authby',
			description:'{$Authby_Explain}',
			'values': [
				{
					'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'rsasig',
				  	'value': 'rsasig'
				},
				{
				  	'label': 'ecdsasig',
				  	'value': 'ecdsasig'
				},
				{
					'label': 'psk',
					'value': 'psk'
				},
				{
					'label': 'secret',
					'value': 'secret'
				},
				{
					'label': 'xauthrsasig',
					'value': 'xauthrsasig'
				},
				{
					'label': 'xauthpsk',
					'value': 'xauthpsk'
				},
				{
					'label': 'never',
					'value': 'never'
				},
			],
			attrs: {
			  type: 'select',
			},

		},
		{
			label: 'type',
			name: 'type',
			description:'{$Type_Explain}',
			'values': [
				{
					'label': 'tunnel',
				  	'value': 'tunnel',
				  	'selected': true
				},
				{
				  	'label': 'transport',
				  	'value': 'transport'
				},
				{
				  	'label': 'transport_proxy',
				  	'value': 'transport_proxy'
				},
				{
					'label': 'passthrough',
					'value': 'passthrough'
				},
				{
					'label': 'drop',
					'value': 'drop'
				},
			],
			attrs: {
			  type: 'select',
			},

		},
		{
			label: 'auto',
			name: 'auto',
			description:'{$Auto_Explain}',
			'values': [
				{
				  	'label': 'ignore',
				  	'value': 'ignore',
				  	'selected': true
				},
				{
				  	'label': 'add',
				  	'value': 'add'
				},
				{
				  	'label': 'route',
				  	'value': 'route'
				},
				{
					'label': 'start',
					'value': 'start'
				}
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'keyexchange',
			name: 'keyexchange',
			description:'{$Keyexchange_Explain}',
			'values': [
				{
				  	'label': 'ikev2',
				  	'value': 'ikev2',
				  	'selected': true
				},
				{
				  	'label': 'ikev1',
				  	'value': 'ikev1'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'dpdaction',
			name: 'dpdaction',
			description:'{$Dpdaction_Explain}',
			'values': [
				{
				  	'label': 'none',
				  	'value': 'none',
				  	'selected': true
				},
				{
				  	'label': 'clear',
				  	'value': 'clear'
				},
				{
					'label': 'hold',
					'value': 'hold'
				},
				{
					'label': 'restart',
					'value': 'restart'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'compress',
			name: 'compress',
			description:'{$Compress_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'fragmentation',
			name: 'fragmentation',
			description:'{$Fragmentation_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
				{
					'label': 'accept',
					'value': 'accept',
			  	},
			  	{
					'label': 'force',
					'value': 'force'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'forceencaps',
			name: 'forceencaps',
			description:'{$Forceencaps_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rekey',
			name: 'rekey',
			description:'{$Rekey_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'xauth',
			name: 'xauth',
			description:'{$Xauth_Explain}',
			'values': [
				{
				  	'label': 'client',
				  	'value': 'client',
				  	'selected': true
				},
				{
				  	'label': 'server',
				  	'value': 'server'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'aggressive',
			name: 'aggressive',
			description:'{$Aggressive_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'leftfirewall',
			name: 'leftfirewall',
			description:'{$LeftRightFirewall_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rightfirewall',
			name: 'rightfirewall',
			description:'{$LeftRightFirewall_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'leftsendcert',
			name: 'leftsendcert',
			description:'{$LeftRightSendcert_Explain}',
			'values': [
				{
				  	'label': 'never',
				  	'value': 'never',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
				{
					'label': 'ifasked',
					'value': 'ifasked'
			  	},
			  	{
					'label': 'always',
					'value': 'always'
				},
				{
					'label': 'yes',
					'value': 'yes'
		  		},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rightsendcert',
			name: 'rightsendcert',
			description:'{$LeftRightSendcert_Explain}',
			'values': [
				{
					'label': 'never',
					'value': 'never',
					'selected': true
			  	},
			  	{
					'label': 'no',
					'value': 'no'
			  	},
			  	{
				  	'label': 'ifasked',
				  	'value': 'ifasked'
				},
				{
				  	'label': 'always',
				  	'value': 'always'
			  	},
			  	{
				  	'label': 'yes',
				  	'value': 'yes'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'leftauth',
			name: 'leftauth',
			description:'{$LeftRightauth_Explain}',
			'values': [
				{
				  	'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'psk',
				  	'value': 'psk'
				},
				{
					'label': 'eap',
					'value': 'eap'
				},
				{
					'label': 'xauth',
					'value': 'xauth'
				  },
				  {
					'label': 'eap-mschapv2',
					'value': 'eap-mschapv2'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rightauth',
			name: 'rightauth',
			description:'{$LeftRightauth_Explain}',
			'values': [
				{
				  	'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'psk',
				  	'value': 'psk'
				},
				{
					'label': 'eap',
					'value': 'eap'
				},
				{
					'label': 'xauth',
					'value': 'xauth'
				  },
				  {
					'label': 'eap-mschapv2',
					'value': 'eap-mschapv2'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'leftauth2',
			name: 'leftauth2',
			description:'{$LeftRightauth2_Explain}',
			'values': [
				{
				  	'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'psk',
				  	'value': 'psk'
				},
				{
					'label': 'eap',
					'value': 'eap'
				},
				{
					'label': 'xauth',
					'value': 'xauth'
				  },
				  {
					'label': 'eap-mschapv2',
					'value': 'eap-mschapv2'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rightauth2',
			name: 'rightauth2',
			description:'{$LeftRightauth2_Explain}',
			'values': [
				{
				  	'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'psk',
				  	'value': 'psk'
				},
				{
					'label': 'eap',
					'value': 'eap'
				},
				{
					'label': 'xauth',
					'value': 'xauth'
				  },
				  {
					'label': 'eap-mschapv2',
					'value': 'eap-mschapv2'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'dpdtimeout',
			name: 'dpdtimeout',
			description:'{$Dpdtimeout_Explain}',
			attrs: {
			  type: 'number',
			},
		},
		{
			label: 'dpddelay',
			name: 'dpddelay',
			description:'{$Dpddelay_Explain}',
			attrs: {
			  type: 'number',
			},
		},
		{
			label: 'xauth_identity',
			name: 'xauth_identity',
			description:'{$Xauthidentity_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'eap_identity',
			name: 'eap_identity',
			description:'{$EapIdentity_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'margintime',
			name: 'margintime',
			description:'{$Margintime_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'lifetime',
			name: 'lifetime',
			description:'{$Lifetime_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'ikelifetime',
			name: 'ikelifetime',
			description:'{$Ikelifetime_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'keyingtries',
			name: 'keyingtries',
			description:'{$Keyingtries_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'ike',
			name: 'ike',
			description:'{$Ike_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'esp',
			name: 'esp',
			description:'{$Esp_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'rightdns',
		  	name: 'rightdns',
		  	description:'{$LeftRightDNS_Explain}',
			attrs: {
			  type: 'text',
			},
	  	},
		{
			label: 'rightsourceip',
			name: 'rightsourceip',
			description:'{$Rightsourceip_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'rightid',
			name: 'rightid',
			description:'{$LeftRightId_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'rightsubnet',
			name: 'rightsubnet',
			description:'{$LeftRightSubnet_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'right',
			name: 'right',
			description:'{$LeftRight_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'leftdns',
		  	name: 'leftdns',
		  	description:'{$LeftRightDNS_Explain}',
			attrs: {
			  type: 'text',
			},
	  	},
		{
			label: 'leftsourceip',
		  	name: 'leftsourceip',
		  	description:'{$Leftsourceip_Explain}',
			attrs: {
			  type: 'text',
			},
	  	},
		{
			label: 'leftid',
		  	name: 'leftid',
		  	description:'{$LeftRightId_Explain}',
			attrs: {
			  type: 'text',
			},
	  	},		
		{
			label: 'leftsubnet',
		  	name: 'leftsubnet',
		  	description:'{$LeftRightSubnet_Explain}',
			attrs: {
			  	type: 'text',
			},
	  	},		
	  	{
	  		label: 'left',
			name: 'left',
			description:'{$LeftRight_Explain}',
	  		attrs: {
				type: 'text',
	  		},
		},
		{
			label: 'connection name',
			name: 'conn',
			description:'{$Conn_Explain}',
			attrs: {
		  		type: 'text',
			},
	  	},
	];

	var fbEditor = $(document.getElementById('fb-editor')),
	formContainer = $(document.getElementById('fb-rendered-form'));
	var options = {
		dataType: 'json',
		disableFields: ['autocomplete','button','checkbox-group','date','file','header','hidden','number','paragraph','radio-group','select','starRating','textarea','text'],
		fields: fields,
		formData: {$json_data},
		onSave: function(e) {
			save()
		  },
		typeUserEvents: {
			text: {
				onadd: function(fld) {
					$('.icon-copy').remove();
					$('.icon-pencil').remove();
			  }
			},		
			select: {
				onadd: function(fld) {
					$('.icon-copy').remove();
					$('.icon-pencil').remove();
			  }
			},
			number: {
				onadd: function(fld) {
					$('.icon-copy').remove();
					$('.icon-pencil').remove();
			  }
			},		
		}
	};



	
		const fbTemplate = document.getElementById('build-wrap');

		var formBuilder = $(fbTemplate).formBuilder(options);

		$('#searchParams').on('keyup', function() {
			var value = $(this).val().toLowerCase();
			$('.frmb-control li').filter(function() {
			  $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
		  });
		function isEmpty(obj) {
			for(var key in obj) {
				if(obj.hasOwnProperty(key))
					return false;
			}
			return true;
		}
		function isInArray(value, array) {
			return array.indexOf(value) > -1;
		  }
		function save(){
			var xdata=JSON.parse(formBuilder.actions.getData('json'));
			var dups='';
			console.log(xdata)
			if(isEmpty(xdata)){
				swal( {title:'Oops...', text:'<strong>No paramaters</strong>', html: true,type:'error'});
				return false;
			}
			var valueArr = xdata.map(function(item){ return item.name });
			var isDuplicate = valueArr.some(function(item, idx){ 
				dups=item
				return valueArr.indexOf(item) != idx
			});
			if(isDuplicate==true){
				swal( {title:'Oops...', text:'<strong>'+dups+' is duplicated</strong>', html: true,type:'error'});
				return false;
			}

			for(var i=0;i<xdata.length;i++){
				if(xdata[i]['type']=='text' || xdata[i]['type']=='number'){
					if(isEmpty(xdata[i]['value'])){
						swal( {title:'Oops...', text:'<strong>'+xdata[i]['name']+' is empty</strong>', html: true,type:'error'});
						return false;
					}
				}
			}

	

			var left = xdata.filter(function (name) { return name.name == 'left' })
			if (isEmpty(left)){
				swal( {title:'Oops...', text:'<strong>Left paramater is required</strong>', html: true,type:'error'})
				return false;
			}
			if (isEmpty(left[0]['value'])){
				swal( {title:'Oops...', text:'<strong>Left is empty</strong>', html: true,type:'error'})
				return false;
			}

			var leftsubnet = xdata.filter(function (name) { return name.name == 'leftsubnet' })
			if (isEmpty(leftsubnet)){
				swal( {title:'Oops...', text:'<strong>Left Subnet paramater is required</strong>', html: true,type:'error'})
				return false;
			}
			if (isEmpty(leftsubnet[0]['value'])){
				swal( {title:'Oops...', text:'<strong>Left Subnet is empty</strong>', html: true,type:'error'})
				return false;
			}

			var right = xdata.filter(function (name) { return name.name == 'right' })
			if (isEmpty(right)){
				swal( {title:'Oops...', text:'<strong>Right paramater is required</strong>', html: true,type:'error'})
				return false;
			}
			if (isEmpty(right[0]['value'])){
				swal( {title:'Oops...', text:'<strong>Right is empty</strong>', html: true,type:'error'})
				return false;
			}



			var conn = xdata.filter(function (name) { return name.name == 'conn' })
			if (isEmpty(conn)){
				swal( {title:'Oops...', text:'<strong>Connection Name paramater is required</strong>', html: true,type:'error'})
				return false;
			}
			if (isEmpty(conn[0]['value'])){
				swal( {title:'Oops...', text:'<strong>Connection Name is empty</strong>', html: true,type:'error'})
				return false;
			}

			$.ajax({
				type: 'POST',
				url: '$page',
				data: {x:xdata, 'rule-save':$ID},
				success: function(e) {
				  if(e==true){
					Loadjs('$page?build-progress=yes');
					  swal( {title:'Success...', text:'<strong>Tunnel configured successfully</strong>', html: true,type:'success'});
				  }
				  else{
					swal( {title:'Oops...', text:e, html: true,type:'error'})  
				  }
				},
				error: function(e) {
					swal( {title:'Oops...', text:e, html: true,type:'error'})

				}
			  });
			
		}

	  });
	</script>
	<style >
	.get-data{display:none !important}
	.form-builder-dialog {z-index:3000 !important}
	.form-builder-overlay {z-index:2999 !important}
	</style>
	";
        echo @implode("\n", $html);
    }


    if ($ID==0) {
        $BootstrapDialog="BootstrapDialog1.close();";
        $html[]="


	<div class=\"row border-bottom white-bg dashboard-header\">
	<div ><h4 class=ng-binding>$APP_STRONGSWAN_TUNNELS</h4></div>
	</div>
	<div class='row'><div id='progress-strongswan-tunnels-restart'></div>
	<div class='ibox-content'>
	<div class='col-sm-9'>
	<select name='formTemplates' id='formTemplates' class='form-control'>
	<option value='blank'>{$STRONSWAN_LOAD_TEMPLATES}</option>
	<option value='sitetosite'>{$STRONSWAN_SITE_TO_SITE}</option>
	<option value='remoteaccess'>{$STRONSWAN_REMOTE_ACCESS}</option>
</select>
	</div>
	<div class=''>
		
		<div class=\"form-group footable-filtering-search\"><label class=\"sr-only\">{$STRONSWAN_FILTER_PARAMETERS}</label><div class=\"input-group\"><input type=\"text\" class=\"form-control\" style=\"float:right\" placeholder=\"{$STRONSWAN_FILTER_PARAMETERS}\"  id='searchParams'><div class=\"input-group-btn\"><button type=\"button\" class=\"btn btn-primary\"><span class=\"fooicon fooicon-search\"></span></button></div></div></div>
	</div>

	</div>
	</div>
	
	<div id='build-wrap'></div>
	<script>
	jQuery($ => {
		
		const templates = {
			remoteaccess:[
			  {
				'type': 'text',
				'required': false,
				'label': 'connection name',
				'description': 'Name of connection. Don`t use special characters or spaces. Example: siteX-to-siteY',
				'className': 'form-control',
				'name': 'conn',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'auto',
				'description': 'What operation, if any, should be done automatically at IPsec startup. add loads a connection withoutstarting it. route loads a connection and installs kernel traps. If traffic is detected betweenleftsubnet and rightsubnet, a connection is established. start loads a connection and bringsit up immediately. ignore ignores the connection. This is equal to deleting a connection from the configfile. Relevant only locally, other end need not agree on it.',
				'className': 'form-control',
				'name': 'auto',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'ignore',
					'value': 'ignore',
					'selected': true
				  },
				  {
					'label': 'add',
					'value': 'add'
				  },
				  {
					'label': 'route',
					'value': 'route'
				  },
				  {
					'label': 'start',
					'value': 'start'
				  }
				]
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'compress',
				'description': 'Whether IPComp compression of content is proposed on the connection (link-level compression does not work onencrypted data, so to be effective, compression must be done before encryption). A value of yes causes the daemonto propose both compressed and uncompressed, and prefer compressed. A value of no prevents the daemon from proposing or accepting compression.',
				'className': 'form-control',
				'name': 'compress',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'yes',
					'value': 'yes',
					'selected': true
				  },
				  {
					'label': 'no',
					'value': 'no'
				  }
				]
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'type',
				'description': 'The type of the connection; currently the accepted values are Tunnel: signifying a host-to-host,host-to-subnet, or subnet-to-subnet tunnel;Transport: signifying host-to-host transport mode;Transport_proxy: signifying the special Mobile IPv6 transport proxy mode;Passthrough: signifying that no IPsec processing should be done at all;Drop, signifying that packetsshould be discarded.',
				'className': 'form-control',
				'name': 'type',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'tunnel',
					'value': 'tunnel',
					'selected': true
				  },
				  {
					'label': 'transport',
					'value': 'transport'
				  },
				  {
					'label': 'transport_proxy',
					'value': 'transport_proxy'
				  },
				  {
					'label': 'passthrough',
					'value': 'passthrough'
				  },
				  {
					'label': 'drop',
					'value': 'drop'
				  }
				]
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'keyexchange',
				'description': 'Method of key exchange; which protocol should be used to initialize the connection.',
				'className': 'form-control',
				'name': 'keyexchange',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'ikev2',
					'value': 'ikev2',
					'selected': true
				  },
				  {
					'label': 'ikev1',
					'value': 'ikev1'
				  }
				]
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'fragmentation',
				'description': 'Fhether to use IKE fragmentation (proprietary IKEv1 extension or IKEv2 fragmentation as per RFC 7383).Fragmented messages sent by a peer are always processed irrespective of the value of this option (even when set to no).If set to yes (the default since 5.5.1) and the peer supports it, oversized IKE messages will be sent in fragments (themaximum fragment size can be configured in strongswan.conf). If set to accept (available since 5.5.3) support forfragmentation is announced to the peer but the daemon does not send its own messages in fragments.If set to force (only supported for IKEv1) the initial IKE message will already be fragmented if required.Available for IKEv1 connections since 5.0.2 and for IKEv2 connections since 5.2.1.',
				'className': 'form-control',
				'name': 'fragmentation',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'yes',
					'value': 'yes',
					'selected': true
				  },
				  {
					'label': 'no',
					'value': 'no'
				  },
				  {
					'label': 'accept',
					'value': 'accept'
				  },
				  {
					'label': 'force',
					'value': 'force'
				  }
				]
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'forceencaps',
				'description': 'Force UDP encapsulation for ESP packets even if no NAT situation is detected.This may help to surmount restrictive firewalls. In order to force the peer toencapsulate packets, NAT detection payloads are faked.Not supported for IKEv1 connections prior to 5.0.0.',
				'className': 'form-control',
				'name': 'forceencaps',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'yes',
					'value': 'yes',
					'selected': true
				  },
				  {
					'label': 'no',
					'value': 'no'
				  }
				]
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'dpdaction',
				'className': 'form-control',
				'name': 'dpdaction',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'none',
					'value': 'none',
					'selected': true
				  },
				  {
					'label': 'clear',
					'value': 'clear'
				  },
				  {
					'label': 'hold',
					'value': 'hold'
				  },
				  {
					'label': 'restart',
					'value': 'restart'
				  }
				]
			  },
			  {
				'type': 'number',
				'required': false,
				'label': 'dpddelay',
				'description': 'Defines the period time interval with which R_U_THERE messages/INFORMATIONAL exchanges are sent to the peer.These are only sent if no other traffic is received. In IKEv2, a value of 0 sends no additional INFORMATIONALmessages and uses only standard messages (such as those to rekey) to detect dead peers.',
				'className': 'form-control',
				'name': 'dpddelay',
				'access': false
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'rekey',
				'description': 'Whether a connection should be renegotiated when it is about to expire. The two ends need not agree, butwhile a value of no prevents the daemon from requesting renegotiation, it does not prevent respondingto renegotiation requested from the other end, so no will be largely ineffective unless both ends agree on it.',
				'className': 'form-control',
				'name': 'rekey',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'yes',
					'value': 'yes',
					'selected': true
				  },
				  {
					'label': 'no',
					'value': 'no'
				  }
				]
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'left',
				'description': 'The IP address of the participant`s public-network interface or one of several magic values.The value %any for the local endpoint signifies an address to be filled in(by automatic keying) during negotiation. If the local peer initiates the connection setup the routing tablewill be queried to determine the correct local IP address. In case the local peer is responding to a connectionsetup then any IP address that is assigned to a local interface will be accepted. The value %any4 restrictsaddress selection to IPv4 addresses, the value %any6 reistricts address selection to IPv6 addresses.Prior to 5.0.0 specifying %any for the local endpoint was not supported for IKEv1 connections, insteadthe keyword %defaultroute could be used, causing the value to be filled in automatically with the localaddress of the default-route interface (as determined at IPsec startup time and during configurationupdate). Either left or right may be %defaultroute, but not both.The prefix % in front of a fully-qualified domain name or an IP address will implicitly set left|rightallowany=yes.If %any is used for the remote endpoint it literally means any IP address.If an FQDN is assigned it is resolved every time a configuration lookup is done. If DNS resolution times out,the lookup is delayed for that time.Since 5.1.1 connections can be limited to a specific range of hosts. To do so a range (10.1.0.0-10.2.255.255)or a subnet (10.1.0.0/16) can be specified, and multiple addresses, ranges and subnets can be separated by commas.While one can freely combine these items, to initiate the connection at least one non-range/subnet is required.Please note that with the usage of wildcards multiple connection descriptions might match a given incomingconnection attempt. The most specific description is used in that case.',
				'className': 'form-control',
				'name': 'left',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'leftid',
				'description': 'How the left|right participant should be identified for authentication; defaults to left|right or the subject of the certificate configured with left|rightcert. If left|rightcert is configured the identity has to be confirmed by the certificate, that is, it has to match the full subject DN or one of the subjectAltName extensions contained in the certificate.Can be an IP address, a fully-qualified domain name, an email address or a Distinguished Name for which theID type is determined automatically and the string is converted to the appropriate encoding.',
				'className': 'form-control',
				'name': 'leftid',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'leftsendcert',
				'description': 'Accepted values are never or no, always or yes, and ifasked, the latter meaning thatthe peer must send a certificate request (CR) payload in order to get a certificate in return.',
				'className': 'form-control',
				'name': 'leftsendcert',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'never',
					'value': 'never',
					'selected': true
				  },
				  {
					'label': 'no',
					'value': 'no'
				  },
				  {
					'label': 'ifasked',
					'value': 'ifasked'
				  },
				  {
					'label': 'always',
					'value': 'always'
				  },
				  {
					'label': 'yes',
					'value': 'yes'
				  }
				]
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'leftsubnet',
				'description': 'Private subnet behind the left participant, expressed as network/netmask; if omitted, essentially assumedto be left/32|128, signifying that the left|right end of the connection goes to the left|right participant only.The configured subnets of the peers may differ, the protocol narrows it to the greatest common subnet.Since 5.0.0 this is also done for IKEv1, but as this may lead to problems with other implementations,make sure to configure identical subnets in such configurations.IKEv2 supports multiple subnets separated by commas, IKEv1 only interprets the first subnet of such a definition,unless the Cisco Unity extension plugin is enabled (available since 5.0.1). This is due to a limitation of the IKEv1protocol, which only allows a single pair of subnets per CHILD_SA. So to tunnel several subnets a conn entry hasto be defined and brought up for each pair of subnets.Since 5.1.0 the optional part after each subnet enclosed in square brackets specifies a protocol/port to restrictthe selector for that subnet. Examples: leftsubnet=10.0.0.1[tcp/http],10.0.0.2[6/80] or leftsubnet=fec1::1[udp],10.0.0.0/16[/53].Instead of omitting either value %any can be used to the same effect, e.g. leftsubnet=fec1::1[udp/%any],10.0.0.0/16[%any/53].Since 5.1.1, if the protocol is icmp or ipv6-icmp the port is interpreted as ICMP message type if it is less than 256,or as type and code if it greater or equal to 256, with the type in the most significant 8 bits and the code in theleast significant 8 bits.The port value can alternatively take the value %opaque for RFC 4301 OPAQUE selectors, or a numerical rangein the form 1024-65535. None of the kernel backends currently supports opaque or port ranges and uses %anyfor policy installation instead.Instead of specifying a subnet, %dynamic can be used to replace it with the IKE address, having the same effectas omitting left|rightsubnet completely. Using %dynamic can be used to define multiple dynamic selectors,each having a potentially different protocol/port definition.',
				'className': 'form-control',
				'name': 'leftsubnet',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'right',
				'description': 'The IP address of the participant`s public-network interface or one of several magic values.The value %any for the local endpoint signifies an address to be filled in(by automatic keying) during negotiation. If the local peer initiates the connection setup the routing tablewill be queried to determine the correct local IP address. In case the local peer is responding to a connectionsetup then any IP address that is assigned to a local interface will be accepted. The value %any4 restrictsaddress selection to IPv4 addresses, the value %any6 reistricts address selection to IPv6 addresses.Prior to 5.0.0 specifying %any for the local endpoint was not supported for IKEv1 connections, insteadthe keyword %defaultroute could be used, causing the value to be filled in automatically with the localaddress of the default-route interface (as determined at IPsec startup time and during configurationupdate). Either left or right may be %defaultroute, but not both.The prefix % in front of a fully-qualified domain name or an IP address will implicitly set left|rightallowany=yes.If %any is used for the remote endpoint it literally means any IP address.If an FQDN is assigned it is resolved every time a configuration lookup is done. If DNS resolution times out,the lookup is delayed for that time.Since 5.1.1 connections can be limited to a specific range of hosts. To do so a range (10.1.0.0-10.2.255.255)or a subnet (10.1.0.0/16) can be specified, and multiple addresses, ranges and subnets can be separated by commas.While one can freely combine these items, to initiate the connection at least one non-range/subnet is required.Please note that with the usage of wildcards multiple connection descriptions might match a given incomingconnection attempt. The most specific description is used in that case.',
				'className': 'form-control',
				'name': 'right',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'rightid',
				'description': 'How the left|right participant should be identified for authentication; defaults to left|right or the subject of the certificate configured with left|rightcert. If left|rightcert is configured the identity has to be confirmed by the certificate, that is, it has to match the full subject DN or one of the subjectAltName extensions contained in the certificate.Can be an IP address, a fully-qualified domain name, an email address or a Distinguished Name for which theID type is determined automatically and the string is converted to the appropriate encoding.',
				'className': 'form-control',
				'name': 'rightid',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'rightauth',
				'description': 'Authentication method to use locally (left) or require from the remote (right) side. Acceptable values are pubkeyfor public key encryption (RSA/ECDSA), psk for pre-shared key authentication, eap to [require the] use of the Extensible Authentication Protocol, and xauth for IKEv1 eXtended Authentication.',
				'className': 'form-control',
				'name': 'rightauth',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'pubkey',
					'value': 'pubkey',
					'selected': true
				  },
				  {
					'label': 'psk',
					'value': 'psk'
				  },
				  {
					'label': 'eap',
					'value': 'eap'
				  },
				  {
					'label': 'xauth',
					'value': 'xauth'
				  },
				  {
					'label': 'eap-mschapv2',
					'value': 'eap-mschapv2'
			  	},
				]
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'rightsourceip',
				'description': 'The internal source IP to use in a tunnel for the remote peer. If the value is config on the responderside, the initiator must propose an address which is then echoed back. Also supported are address poolsexpressed as / and - (since 5.2.2) or the use of an external IP address poolusing %poolname where poolname is the name of the IP address pool used for the lookup (see virtual IP for details).Since 5.0.1 a comma-separated list of IP addresses / pools is accepted, for instance, to define pools ofdifferent address families.',
				'className': 'form-control',
				'name': 'rightsourceip',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'rightdns',
				'description': 'Comma separated list of DNS server addresses to exchange as configuration attributes. On the initiator,a server is a fixed IPv4/IPv6 address, or %config4/%config6 to request attributes without an address.On the responder, only fixed IPv4/IPv6 addresses are allowed and define DNS servers assigned to the client.',
				'className': 'form-control',
				'name': 'rightdns',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'rightsendcert',
				'description': 'Accepted values are never or no, always or yes, and ifasked, the latter meaning thatthe peer must send a certificate request (CR) payload in order to get a certificate in return.',
				'className': 'form-control',
				'name': 'rightsendcert',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'never',
					'value': 'never',
					'selected': true
				  },
				  {
					'label': 'no',
					'value': 'no'
				  },
				  {
					'label': 'ifasked',
					'value': 'ifasked'
				  },
				  {
					'label': 'always',
					'value': 'always'
				  },
				  {
					'label': 'yes',
					'value': 'yes'
				  }
				]
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'eap_identity',
				'description': 'Defines the identity the client uses to reply to an EAP Identity request. If defined on the EAP server, the defined identity will be used as peer identity during EAP authentication. The special value %identity uses the EAP Identity method to ask the client for a EAP identity. If not defined, the IKEv2 identity will be used as EAP identity.',
				'className': 'form-control',
				'name': 'eap_identity',
				'access': false,
				'subtype': 'text'
			  }
			],
			sitetosite: [
			  {
				'type': 'text',
				'required': false,
				'label': 'connection name',
				'description': 'Name of connection. Don`t use special characters or spaces. Example: siteX-to-siteY',
				'className': 'form-control',
				'name': 'conn',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'authby',
				'description': 'How the two security gateways should authenticate each other; acceptable values are secret or pskfor pre-shared secrets, pubkey (the default) for public key signatures as well as the synonyms rsasigfor RSA digital signatures and ecdsasig for Elliptic Curve DSA signatures.never can be used if negotiation is never to be attempted or accepted (useful for shunt-only conns).Digital signatures are superior in every way to shared secrets. IKEv1 additionally supports the valuesxauthpsk and xauthrsasig that will enable eXtended Authentication (XAuth) in addition to IKEv1 mainmode based on shared secrets or digital RSA signatures, respectively.This parameter is deprecated for IKEv2 connections (and IKEv1 connections since 5.0.0), as two peersdo not need to agree on an authentication method. Use the left|rightauth parameter instead to defineauthentication methods.',
				'className': 'form-control',
				'name': 'authby',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'pubkey',
					'value': 'pubkey',
					'selected': true
				  },
				  {
					'label': 'rsasig',
					'value': 'rsasig'
				  },
				  {
					'label': 'ecdsasig',
					'value': 'ecdsasig'
				  },
				  {
					'label': 'psk',
					'value': 'psk'
				  },
				  {
					'label': 'secret',
					'value': 'secret'
				  },
				  {
					'label': 'xauthrsasig',
					'value': 'xauthrsasig'
				  },
				  {
					'label': 'xauthpsk',
					'value': 'xauthpsk'
				  },
				  {
					'label': 'never',
					'value': 'never'
				  }
				]
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'auto',
				'description': 'What operation, if any, should be done automatically at IPsec startup. add loads a connection withoutstarting it. route loads a connection and installs kernel traps. If traffic is detected betweenleftsubnet and rightsubnet, a connection is established. start loads a connection and bringsit up immediately. ignore ignores the connection. This is equal to deleting a connection from the configfile. Relevant only locally, other end need not agree on it.',
				'className': 'form-control',
				'name': 'auto',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'ignore',
					'value': 'ignore',
					'selected': true
				  },
				  {
					'label': 'add',
					'value': 'add'
				  },
				  {
					'label': 'route',
					'value': 'route'
				  },
				  {
					'label': 'start',
					'value': 'start'
				  }
				]
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'type',
				'description': 'The type of the connection; currently the accepted values are Tunnel: signifying a host-to-host,host-to-subnet, or subnet-to-subnet tunnel;Transport: signifying host-to-host transport mode;Transport_proxy: signifying the special Mobile IPv6 transport proxy mode;Passthrough: signifying that no IPsec processing should be done at all;Drop, signifying that packetsshould be discarded.',
				'className': 'form-control',
				'name': 'type',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'tunnel',
					'value': 'tunnel',
					'selected': true
				  },
				  {
					'label': 'transport',
					'value': 'transport'
				  },
				  {
					'label': 'transport_proxy',
					'value': 'transport_proxy'
				  },
				  {
					'label': 'passthrough',
					'value': 'passthrough'
				  },
				  {
					'label': 'drop',
					'value': 'drop'
				  }
				]
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'left',
				'description': 'The IP address of the participant`s public-network interface or one of several magic values.The value %any for the local endpoint signifies an address to be filled in(by automatic keying) during negotiation. If the local peer initiates the connection setup the routing tablewill be queried to determine the correct local IP address. In case the local peer is responding to a connectionsetup then any IP address that is assigned to a local interface will be accepted. The value %any4 restrictsaddress selection to IPv4 addresses, the value %any6 reistricts address selection to IPv6 addresses.Prior to 5.0.0 specifying %any for the local endpoint was not supported for IKEv1 connections, insteadthe keyword %defaultroute could be used, causing the value to be filled in automatically with the localaddress of the default-route interface (as determined at IPsec startup time and during configurationupdate). Either left or right may be %defaultroute, but not both.The prefix % in front of a fully-qualified domain name or an IP address will implicitly set left|rightallowany=yes.If %any is used for the remote endpoint it literally means any IP address.If an FQDN is assigned it is resolved every time a configuration lookup is done. If DNS resolution times out,the lookup is delayed for that time.Since 5.1.1 connections can be limited to a specific range of hosts. To do so a range (10.1.0.0-10.2.255.255)or a subnet (10.1.0.0/16) can be specified, and multiple addresses, ranges and subnets can be separated by commas.While one can freely combine these items, to initiate the connection at least one non-range/subnet is required.Please note that with the usage of wildcards multiple connection descriptions might match a given incomingconnection attempt. The most specific description is used in that case.',
				'className': 'form-control',
				'name': 'left',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'leftsubnet',
				'description': 'Private subnet behind the left participant, expressed as network/netmask; if omitted, essentially assumedto be left/32|128, signifying that the left|right end of the connection goes to the left|right participant only.The configured subnets of the peers may differ, the protocol narrows it to the greatest common subnet.Since 5.0.0 this is also done for IKEv1, but as this may lead to problems with other implementations,make sure to configure identical subnets in such configurations.IKEv2 supports multiple subnets separated by commas, IKEv1 only interprets the first subnet of such a definition,unless the Cisco Unity extension plugin is enabled (available since 5.0.1). This is due to a limitation of the IKEv1protocol, which only allows a single pair of subnets per CHILD_SA. So to tunnel several subnets a conn entry hasto be defined and brought up for each pair of subnets.Since 5.1.0 the optional part after each subnet enclosed in square brackets specifies a protocol/port to restrictthe selector for that subnet. Examples: leftsubnet=10.0.0.1[tcp/http],10.0.0.2[6/80] or leftsubnet=fec1::1[udp],10.0.0.0/16[/53].Instead of omitting either value %any can be used to the same effect, e.g. leftsubnet=fec1::1[udp/%any],10.0.0.0/16[%any/53].Since 5.1.1, if the protocol is icmp or ipv6-icmp the port is interpreted as ICMP message type if it is less than 256,or as type and code if it greater or equal to 256, with the type in the most significant 8 bits and the code in theleast significant 8 bits.The port value can alternatively take the value %opaque for RFC 4301 OPAQUE selectors, or a numerical rangein the form 1024-65535. None of the kernel backends currently supports opaque or port ranges and uses %anyfor policy installation instead.Instead of specifying a subnet, %dynamic can be used to replace it with the IKE address, having the same effectas omitting left|rightsubnet completely. Using %dynamic can be used to define multiple dynamic selectors,each having a potentially different protocol/port definition.',
				'className': 'form-control',
				'name': 'leftsubnet',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'right',
				'description': 'The IP address of the participant`s public-network interface or one of several magic values.The value %any for the local endpoint signifies an address to be filled in(by automatic keying) during negotiation. If the local peer initiates the connection setup the routing tablewill be queried to determine the correct local IP address. In case the local peer is responding to a connectionsetup then any IP address that is assigned to a local interface will be accepted. The value %any4 restrictsaddress selection to IPv4 addresses, the value %any6 reistricts address selection to IPv6 addresses.Prior to 5.0.0 specifying %any for the local endpoint was not supported for IKEv1 connections, insteadthe keyword %defaultroute could be used, causing the value to be filled in automatically with the localaddress of the default-route interface (as determined at IPsec startup time and during configurationupdate). Either left or right may be %defaultroute, but not both.The prefix % in front of a fully-qualified domain name or an IP address will implicitly set left|rightallowany=yes.If %any is used for the remote endpoint it literally means any IP address.If an FQDN is assigned it is resolved every time a configuration lookup is done. If DNS resolution times out,the lookup is delayed for that time.Since 5.1.1 connections can be limited to a specific range of hosts. To do so a range (10.1.0.0-10.2.255.255)or a subnet (10.1.0.0/16) can be specified, and multiple addresses, ranges and subnets can be separated by commas.While one can freely combine these items, to initiate the connection at least one non-range/subnet is required.Please note that with the usage of wildcards multiple connection descriptions might match a given incomingconnection attempt. The most specific description is used in that case.',
				'className': 'form-control',
				'name': 'right',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'rightsubnet',
				'description': 'Private subnet behind the left participant, expressed as network/netmask; if omitted, essentially assumedto be left/32|128, signifying that the left|right end of the connection goes to the left|right participant only.The configured subnets of the peers may differ, the protocol narrows it to the greatest common subnet.Since 5.0.0 this is also done for IKEv1, but as this may lead to problems with other implementations,make sure to configure identical subnets in such configurations.IKEv2 supports multiple subnets separated by commas, IKEv1 only interprets the first subnet of such a definition,unless the Cisco Unity extension plugin is enabled (available since 5.0.1). This is due to a limitation of the IKEv1protocol, which only allows a single pair of subnets per CHILD_SA. So to tunnel several subnets a conn entry hasto be defined and brought up for each pair of subnets.Since 5.1.0 the optional part after each subnet enclosed in square brackets specifies a protocol/port to restrictthe selector for that subnet. Examples: leftsubnet=10.0.0.1[tcp/http],10.0.0.2[6/80] or leftsubnet=fec1::1[udp],10.0.0.0/16[/53].Instead of omitting either value %any can be used to the same effect, e.g. leftsubnet=fec1::1[udp/%any],10.0.0.0/16[%any/53].Since 5.1.1, if the protocol is icmp or ipv6-icmp the port is interpreted as ICMP message type if it is less than 256,or as type and code if it greater or equal to 256, with the type in the most significant 8 bits and the code in theleast significant 8 bits.The port value can alternatively take the value %opaque for RFC 4301 OPAQUE selectors, or a numerical rangein the form 1024-65535. None of the kernel backends currently supports opaque or port ranges and uses %anyfor policy installation instead.Instead of specifying a subnet, %dynamic can be used to replace it with the IKE address, having the same effectas omitting left|rightsubnet completely. Using %dynamic can be used to define multiple dynamic selectors,each having a potentially different protocol/port definition.',
				'className': 'form-control',
				'name': 'rightsubnet',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'ike',
				'description': 'Comma-separated list of IKE/ISAKMP SA encryption/authentication algorithms to be used, e.g.aes128-sha256-modp3072. The notation is encryption-integrity[-prf]-dhgroup. In IKEv2, multiple algorithmsand proposals may be included, such as aes128-aes256-sha1-modp3072-modp2048,3des-sha1-md5-modp1024.The ability to configure a PRF algorithm different to that defined for integrity protection was added with 5.0.2.If no PRF is configured, the algorithms defined for integrity are proposed as PRF. The prf keywords are the same asthe integrity algorithms, but have a prf prefix (such as prfsha1, prfsha256 or prfaesxcbc).',
				'className': 'form-control',
				'name': 'ike',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'ikelifetime',
				'description': 'Absolute time after which an IKE SA expires. Examples: 1h, 3600s, 28800s',
				'className': 'form-control',
				'name': 'ikelifetime',
				'access': false,
				'subtype': 'text'
			  },
			  {
				'type': 'select',
				'required': false,
				'label': 'keyexchange',
				'description': 'Method of key exchange; which protocol should be used to initialize the connection.',
				'className': 'form-control',
				'name': 'keyexchange',
				'access': false,
				'multiple': false,
				'values': [
				  {
					'label': 'ikev2',
					'value': 'ikev2',
					'selected': true
				  },
				  {
					'label': 'ikev1',
					'value': 'ikev1'
				  }
				]
			  },
			  {
				'type': 'text',
				'required': false,
				'label': 'esp',
				'description': 'Comma-separated list of ESP encryption/authentication algorithms to be used for the connection, e.g.aes128-sha256. The notation is encryption-integrity[-dhgroup][-esnmode].For IKEv2, multiple algorithms (separated by -) of the same type can be included in a single proposal.IKEv1 only includes the first algorithm in a proposal. Only either the ah or the esp keyword maybe used, AH+ESP bundles are not supported.',
				'className': 'form-control',
				'name': 'esp',
				'access': false,
				'subtype': 'text'
			  }
			]
			}	
	var fields = [
		$LeftCert
		$RightCert
		{
			label: 'authby',
			name: 'authby',
			description:'{$Authby_Explain}',
			'values': [
				{
					'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'rsasig',
				  	'value': 'rsasig'
				},
				{
				  	'label': 'ecdsasig',
				  	'value': 'ecdsasig'
				},
				{
					'label': 'psk',
					'value': 'psk'
				},
				{
					'label': 'secret',
					'value': 'secret'
				},
				{
					'label': 'xauthrsasig',
					'value': 'xauthrsasig'
				},
				{
					'label': 'xauthpsk',
					'value': 'xauthpsk'
				},
				{
					'label': 'never',
					'value': 'never'
				},
			],
			attrs: {
			  type: 'select',
			},

		},
		{
			label: 'type',
			name: 'type',
			description:'{$Type_Explain}',
			'values': [
				{
					'label': 'tunnel',
				  	'value': 'tunnel',
				  	'selected': true
				},
				{
				  	'label': 'transport',
				  	'value': 'transport'
				},
				{
				  	'label': 'transport_proxy',
				  	'value': 'transport_proxy'
				},
				{
					'label': 'passthrough',
					'value': 'passthrough'
				},
				{
					'label': 'drop',
					'value': 'drop'
				},
			],
			attrs: {
			  type: 'select',
			},

		},
		{
			label: 'auto',
			name: 'auto',
			description:'{$Auto_Explain}',
			'values': [
				{
				  	'label': 'ignore',
				  	'value': 'ignore',
				  	'selected': true
				},
				{
				  	'label': 'add',
				  	'value': 'add'
				},
				{
				  	'label': 'route',
				  	'value': 'route'
				},
				{
					'label': 'start',
					'value': 'start'
				}
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'keyexchange',
			name: 'keyexchange',
			description:'{$Keyexchange_Explain}',
			'values': [
				{
				  	'label': 'ikev2',
				  	'value': 'ikev2',
				  	'selected': true
				},
				{
				  	'label': 'ikev1',
				  	'value': 'ikev1'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'dpdaction',
			name: 'dpdaction',
			description:'{$Dpdaction_Explain}',
			'values': [
				{
				  	'label': 'none',
				  	'value': 'none',
				  	'selected': true
				},
				{
				  	'label': 'clear',
				  	'value': 'clear'
				},
				{
					'label': 'hold',
					'value': 'hold'
				},
				{
					'label': 'restart',
					'value': 'restart'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'compress',
			name: 'compress',
			description:'{$Compress_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'fragmentation',
			name: 'fragmentation',
			description:'{$Fragmentation_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
				{
					'label': 'accept',
					'value': 'accept',
			  	},
			  	{
					'label': 'force',
					'value': 'force'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'forceencaps',
			name: 'forceencaps',
			description:'{$Forceencaps_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rekey',
			name: 'rekey',
			description:'{$Rekey_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'xauth',
			name: 'xauth',
			description:'{$Xauth_Explain}',
			'values': [
				{
				  	'label': 'client',
				  	'value': 'client',
				  	'selected': true
				},
				{
				  	'label': 'server',
				  	'value': 'server'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'aggressive',
			name: 'aggressive',
			description:'{$Aggressive_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'leftfirewall',
			name: 'leftfirewall',
			description:'{$LeftRightFirewall_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rightfirewall',
			name: 'rightfirewall',
			description:'{$LeftRightFirewall_Explain}',
			'values': [
				{
				  	'label': 'yes',
				  	'value': 'yes',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'leftsendcert',
			name: 'leftsendcert',
			description:'{$LeftRightSendcert_Explain}',
			'values': [
				{
				  	'label': 'never',
				  	'value': 'never',
				  	'selected': true
				},
				{
				  	'label': 'no',
				  	'value': 'no'
				},
				{
					'label': 'ifasked',
					'value': 'ifasked'
			  	},
			  	{
					'label': 'always',
					'value': 'always'
				},
				{
					'label': 'yes',
					'value': 'yes'
		  		},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rightsendcert',
			name: 'rightsendcert',
			description:'{$LeftRightSendcert_Explain}',
			'values': [
				{
					'label': 'never',
					'value': 'never',
					'selected': true
			  	},
			  	{
					'label': 'no',
					'value': 'no'
			  	},
			  	{
				  	'label': 'ifasked',
				  	'value': 'ifasked'
				},
				{
				  	'label': 'always',
				  	'value': 'always'
			  	},
			  	{
				  	'label': 'yes',
				  	'value': 'yes'
				},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'leftauth',
			name: 'leftauth',
			description:'{$LeftRightauth_Explain}',
			'values': [
				{
				  	'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'psk',
				  	'value': 'psk'
				},
				{
					'label': 'eap',
					'value': 'eap'
				},
				{
					'label': 'xauth',
					'value': 'xauth'
				  },
				  {
					'label': 'eap-mschapv2',
					'value': 'eap-mschapv2'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rightauth',
			name: 'rightauth',
			description:'{$LeftRightauth_Explain}',
			'values': [
				{
				  	'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'psk',
				  	'value': 'psk'
				},
				{
					'label': 'eap',
					'value': 'eap'
				},
				{
					'label': 'xauth',
					'value': 'xauth'
				  },
				  {
					'label': 'eap-mschapv2',
					'value': 'eap-mschapv2'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'leftauth2',
			name: 'leftauth2',
			description:'{$LeftRightauth2_Explain}',
			'values': [
				{
				  	'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'psk',
				  	'value': 'psk'
				},
				{
					'label': 'eap',
					'value': 'eap'
				},
				{
					'label': 'xauth',
					'value': 'xauth'
				  },
				  {
					'label': 'eap-mschapv2',
					'value': 'eap-mschapv2'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'rightauth2',
			name: 'rightauth2',
			description:'{$LeftRightauth2_Explain}',
			'values': [
				{
				  	'label': 'pubkey',
				  	'value': 'pubkey',
				  	'selected': true
				},
				{
				  	'label': 'psk',
				  	'value': 'psk'
				},
				{
					'label': 'eap',
					'value': 'eap'
				},
				{
					'label': 'xauth',
					'value': 'xauth'
				  },
				  {
					'label': 'eap-mschapv2',
					'value': 'eap-mschapv2'
			  	},
			],
			attrs: {
			  type: 'select',                                                                                  
			},

		},
		{
			label: 'dpdtimeout',
			name: 'dpdtimeout',
			description:'{$Dpdtimeout_Explain}',
			attrs: {
			  type: 'number',
			},
		},
		{
			label: 'dpddelay',
			name: 'dpddelay',
			description:'{$Dpddelay_Explain}',
			attrs: {
			  type: 'number',
			},
		},
		{
			label: 'xauth_identity',
			name: 'xauth_identity',
			description:'{$Xauthidentity_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'eap_identity',
			name: 'eap_identity',
			description:'{$EapIdentity_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'margintime',
			name: 'margintime',
			description:'{$Margintime_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'lifetime',
			name: 'lifetime',
			description:'{$Lifetime_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'ikelifetime',
			name: 'ikelifetime',
			description:'{$Ikelifetime_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'keyingtries',
			name: 'keyingtries',
			description:'{$Keyingtries_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'ike',
			name: 'ike',
			description:'{$Ike_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'esp',
			name: 'esp',
			description:'{$Esp_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'rightdns',
		  	name: 'rightdns',
		  	description:'{$LeftRightDNS_Explain}',
			attrs: {
			  type: 'text',
			},
	  	},
		{
			label: 'rightsourceip',
			name: 'rightsourceip',
			description:'{$Rightsourceip_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'rightid',
			name: 'rightid',
			description:'{$LeftRightId_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'rightsubnet',
			name: 'rightsubnet',
			description:'{$LeftRightSubnet_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'right',
			name: 'right',
			description:'{$LeftRight_Explain}',
			attrs: {
			  type: 'text',
			},
		},
		{
			label: 'leftdns',
		  	name: 'leftdns',
		  	description:'{$LeftRightDNS_Explain}',
			attrs: {
			  type: 'text',
			},
	  	},
		{
			label: 'leftsourceip',
		  	name: 'leftsourceip',
		  	description:'{$Leftsourceip_Explain}',
			attrs: {
			  type: 'text',
			},
	  	},
		{
			label: 'leftid',
		  	name: 'leftid',
		  	description:'{$LeftRightId_Explain}',
			attrs: {
			  type: 'text',
			},
	  	},		
		{
			label: 'leftsubnet',
		  	name: 'leftsubnet',
		  	description:'{$LeftRightSubnet_Explain}',
			attrs: {
			  	type: 'text',
			},
	  	},		
	  	{
	  		label: 'left',
			name: 'left',
			description:'{$LeftRight_Explain}',
	  		attrs: {
				type: 'text',
	  		},
		},
		{
			label: 'connection name',
			name: 'conn',
			description:'{$Conn_Explain}',
			attrs: {
		  		type: 'text',
			},
	  	},
	];

	var fbEditor = $(document.getElementById('fb-editor')),
	formContainer = $(document.getElementById('fb-rendered-form'));
	var options = {
		dataType: 'json',
		disableFields: ['autocomplete','button','checkbox-group','date','file','header','hidden','number','paragraph','radio-group','select','starRating','textarea','text'],
		fields: fields,
		onSave: function(e) {
			save()
		  },
		typeUserEvents: {
			text: {
				onadd: function(fld) {
					$('.icon-copy').remove();
					$('.icon-pencil').remove();
			  }
			},		
			select: {
				onadd: function(fld) {
					$('.icon-copy').remove();
					$('.icon-pencil').remove();
			  }
			},
			number: {
				onadd: function(fld) {
					$('.icon-copy').remove();
					$('.icon-pencil').remove();
			  }
			},		
		}
	};



	
		const fbTemplate = document.getElementById('build-wrap');
		const templateSelect = document.getElementById('formTemplates');
		var formBuilder = $(fbTemplate).formBuilder(options);
		templateSelect.addEventListener('change', function(e) {
			formBuilder.actions.setData(templates[e.target.value]);
		  });
		$('#searchParams').on('keyup', function() {
			var value = $(this).val().toLowerCase();
			$('.frmb-control li').filter(function() {
			  $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
		  });
		function isEmpty(obj) {
			for(var key in obj) {
				if(obj.hasOwnProperty(key))
					return false;
			}
			return true;
		}
		function isInArray(value, array) {
			return array.indexOf(value) > -1;
		  }
		function save(){
			var xdata=JSON.parse(formBuilder.actions.getData('json'));
			var dups='';
			console.log(xdata)
			if(isEmpty(xdata)){
				swal( {title:'Oops...', text:'<strong>No paramaters</strong>', html: true,type:'error'});
				return false;
			}
			var valueArr = xdata.map(function(item){ return item.name });
			var isDuplicate = valueArr.some(function(item, idx){ 
				dups=item
				return valueArr.indexOf(item) != idx
			});
			if(isDuplicate==true){
				swal( {title:'Oops...', text:'<strong>'+dups+' is duplicated</strong>', html: true,type:'error'});
				return false;
			}
		
			for(var i=0;i<xdata.length;i++){
				if(xdata[i]['type']=='text' || xdata[i]['type']=='number'){
					if(isEmpty(xdata[i]['value'])){
						swal( {title:'Oops...', text:'<strong>'+xdata[i]['name']+' is empty</strong>', html: true,type:'error'});
						return false;
					}
				}
			}

			

			var left = xdata.filter(function (name) { return name.name == 'left' })
			if (isEmpty(left)){
				swal( {title:'Oops...', text:'<strong>Left paramater is required</strong>', html: true,type:'error'})
				return false;
			}
			if (isEmpty(left[0]['value'])){
				swal( {title:'Oops...', text:'<strong>Left is empty</strong>', html: true,type:'error'})
				return false;
			}

			var leftsubnet = xdata.filter(function (name) { return name.name == 'leftsubnet' })
			if (isEmpty(leftsubnet)){
				swal( {title:'Oops...', text:'<strong>Left Subnet paramater is required</strong>', html: true,type:'error'})
				return false;
			}
			if (isEmpty(leftsubnet[0]['value'])){
				swal( {title:'Oops...', text:'<strong>Left Subnet is empty</strong>', html: true,type:'error'})
				return false;
			}

			var right = xdata.filter(function (name) { return name.name == 'right' })
			if (isEmpty(right)){
				swal( {title:'Oops...', text:'<strong>Right paramater is required</strong>', html: true,type:'error'})
				return false;
			}
			if (isEmpty(right[0]['value'])){
				swal( {title:'Oops...', text:'<strong>Right is empty</strong>', html: true,type:'error'})
				return false;
			}

			

			var conn = xdata.filter(function (name) { return name.name == 'conn' })
			if (isEmpty(conn)){
				swal( {title:'Oops...', text:'<strong>Connection Name paramater is required</strong>', html: true,type:'error'})
				return false;
			}
			if (isEmpty(conn[0]['value'])){
				swal( {title:'Oops...', text:'<strong>Connection Name is empty</strong>', html: true,type:'error'})
				return false;
			}



			$.ajax({
				type: 'POST',
				url: '$page',
				data: {x:xdata, 'rule-save':$ID},
				success: function(e) {
					if(e==true){
					BootstrapDialog1.close();	
					Loadjs('$page?build-progress=yes');
					swal( {title:'Success...', text:'<strong>Tunnel configured successfully</strong>', html: true,type:'success'});
					
					}
					else{
						swal( {title:'Oops...', text:e, html: true,type:'error'})  
					  }
				},
				error: function(e) {
					swal( {title:'Oops...', text:e, html: true,type:'error'})

				}
			  });
			
		}
	  });

	</script>
	<style >
	.get-data{display:none !important}
	.form-builder-dialog {z-index:3000 !important}
	.form-builder-overlay {z-index:2999 !important}
	</style>
	";
        echo @implode("\n", $html);
    }
}

function rule_save()
{
    $ID=$_POST["rule-save"];
    $CONN_NAME='';
    $CONN_NAME_JSON=json_encode($_POST['x']);
    $CONN_NAME_ARRAY=json_decode($CONN_NAME_JSON, true);
    $cert='';
    $certexist=false;
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    foreach ($CONN_NAME_ARRAY as $key) {
        if ($key['name']=='conn') {
            $CONN_NAME=$key['value'];
        }
        if ($key['name']=='leftcert') {
            if ($key['type']=='select') {
                $_values= getVal($key["values"]);
                $cert=$_values;
                $certexist=true;
            }
        }
    };

    $PARAMS=base64_encode(serialize($_POST["x"]));
    if ($ID==0) {
        $sql1=$q->mysqli_fetch_array("SELECT COUNT(*) as count FROM strongswan_conns WHERE `conn_name`='{$CONN_NAME}'");
        $Items=$sql1['count'];

        if ($Items>0) {
            echo "The connection name already exist, please choose a new one";
            return;
        }
        if ($certexist) {

            $checkdupscerts="SELECT * from strongswan_conns";
            $res=$q->QUERY_SQL($checkdupscerts);
            foreach ($res as $index=>$ligne) {
                $parms=unserialize(base64_decode("{$ligne['params']}"));
                foreach ($parms as $key) {
                    if ($key['name']=='leftcert') {
                        if ($key['type']=='select') {
                            $_values= getVal($key["values"]);
                            //echo $_values;
                            if ($_values==$cert) {
                                echo "The certificate is already in use by the other tunnel, plese select a new one";
                                return;
                            }
                        }
                    }
                }
            }
        }
        $sql="INSERT INTO strongswan_conns (`conn_name`,`params`,`order`,`enable`) VALUES ('{$CONN_NAME}','{$PARAMS}','0','1')";
    } else {
        $sql1=$q->mysqli_fetch_array("SELECT COUNT(*) as count FROM strongswan_conns WHERE conn_name='$CONN_NAME' AND ID NOT IN ('$ID')");
        $Items=$sql1['count'];

        if ($Items>0) {
            echo "The connection name already exist, please choose a new one $ID";
            return;
        }
        if ($certexist) {
            $checkdupscerts="SELECT * from strongswan_conns WHERE `conn_id` NOT IN ('$ID')";
            $res=$q->QUERY_SQL($checkdupscerts);
            foreach ($res as $index=>$ligne) {
                $parms=unserialize(base64_decode("{$ligne['params']}"));
                foreach ($parms as $key) {
                    if ($key['name']=='leftcert') {
                        if ($key['type']=='select') {
                            $_values= getVal($key["values"]);
                            //echo $_values;
                            if ($_values==$cert) {
                                echo "The certificate is already in use by the other tunnel, plese select a new one";
                                return;
                            }
                        }
                    }
                }
            }
        }
        $sql="UPDATE strongswan_conns SET `conn_name`='{$CONN_NAME}',`params`='{$PARAMS}' WHERE ID='$ID'";
    }

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error;
    }
    echo true;

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
function search()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $nic=new networking();
    $sock=new sockets();
    $page=CurrentPageName();
    $type=$tpl->javascript_parse_text("{type}");
    $hostname=$tpl->javascript_parse_text("{conn_name}");

    $download=$tpl->javascript_parse_text("{download2}");
    $events=$tpl->javascript_parse_text("{events}");
    $enabled        = $tpl->_ENGINE_parse_body("{enabled}");
    $action        = $tpl->_ENGINE_parse_body("{actions}");
    $order          = $tpl->_ENGINE_parse_body("{order}");
    $uptime = $tpl->_ENGINE_parse_body("{uptime}");
    $online_users = $tpl->_ENGINE_parse_body("{online_users}");

    $add="Loadjs('$page?ruleid-js=0',true);";
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html[]=$tpl->_ENGINE_parse_body("
	<div class='row'><div id='progress-strongswan-tunnels-restart'></div></div>
	 <div class=\"dropdown\">
  <button class=\"btn btn btn-primary dropdown-toggle\" type=\"button\" id=\"dropdownMenuButton\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
  {new_ipsec_tunnel} <span class=\"caret\"></span>
  </button>&nbsp;$btn
  <ul class=\"dropdown-menu\">
	<li><a class=\"dropdown-item\" href=\"#\" OnClick=\"Loadjs('$page?wizard-rw=yes');\">{wizard} {REMOTE_USERS}</a></li>
	<li><a class=\"dropdown-item\" href=\"#\" OnClick=\"Loadjs('$page?wizard-site2site=yes');\">{wizard} {SITE_TO_SITE}</a></li>
    <li><a class=\"dropdown-item\" href=\"#\" OnClick=\"$add\">{ADANCED}</a></li>
  </ul>
</div>
	 
	 
	 
	 ");

    $html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";


    $TRCLASS=null;
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$order</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$hostname}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>$uptime</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>$online_users</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>$enabled</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>$action</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));

    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");

    $sql=$q->mysqli_fetch_array("SELECT COUNT(*) as count FROM strongswan_conns");

    if (!$q->ok) {
        echo "<div class='alert alert-danger'>$q->mysql_error<br><strong></strong><br><strong><code>$sql</code></strong></div>";
    }
    $Items=$sql['count'];

    $types=Hash_method();
    $tpl2=new templates();
    //writelogs("myrt: {$y}",__CLASS__.'/'.__FUNCTION__,__FILE__);

    $sql="SELECT * FROM strongswan_conns ORDER BY `order` ASC";
    $results = $q->QUERY_SQL($sql);
//    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("strongswan.php?tunnel-statusall=yes");
//    $statusall = file_get_contents("/etc/artica-postfix/settings/Daemons/ipsec_statusall");
//    $pattern = "/(uptime:) ([0-9]+)( [a-z]+)/";
//    if (preg_match($pattern, $statusall, $matches_)) {
//        $statusall_result=$matches_[2].' '.$matches_[3];
//    } else {
//        $statusall_result= $tpl->_ENGINE_parse_body("{offline}");
//    }
    foreach ($results as $index=>$ligne) {
        $MUTED=null;
        if ($ligne["enable"]==0) {
            $MUTED=" text-muted";
        }
        if ($TRCLASS=="footable-odd") {
            $TRCLASS=null;
        } else {
            $TRCLASS="footable-odd";
        }


        $url="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?ruleid-js={$ligne["ID"]}');\"
		style='text-decoration:underline;font-weight:bold'>";
        $url2="<a href='#' OnClick=\"MenuRoot( $(this),'fw.strongswan.sessions.php');\">";

        $jsedit="Loadjs('$page?ruleid-js={$ligne["ID"]}');";
        $edit=$tpl->icon_parameters($jsedit);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js={$ligne["ID"]}&js-after=$jsAfter')");
        $download=$tpl->icon_download("Loadjs('$page?download-js={$ligne["ID"]}')");

        $up=$tpl->icon_up("Loadjs('$page?conn-rule-move={$ligne["ID"]}&conn-rule-dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?conn-rule-move={$ligne["ID"]}&auth-rule-dir=0');");
        $enable=$tpl->icon_check($ligne["enable"], "Loadjs('$page?enable-js={$ligne["ID"]}')", null, "AsFirewallManager");
        // $GLOBALS["CLASS_SOCKETS"]->getFrameWork("strongswan.php?tunnel-status=yes&tunnel={$ligne["conn_name"]}");
        // $status_tunnel = file_get_contents("/etc/artica-postfix/settings/Daemons/ipsec_status_{$ligne['conn_name']}");
        // $pattern = "/([0-9]+) (up)/";
        // if (preg_match($pattern, $status_tunnel, $matches_)) {
        //     $status_tunnel_result=$matches_[1];
        // } else {
        //     $status_tunnel_result= '0';
        // }
        @unlink("/etc/artica-postfix/settings/Daemons/ipsec_status_{$ligne['conn_name']}");
        @unlink("/etc/artica-postfix/settings/Daemons/ipsec_status_time_{$ligne['conn_name']}");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("strongswan.php?refresh-sessions=yes");


        $tunnel_time_file= file_get_contents("/etc/artica-postfix/settings/Daemons/ipsec_status_time_{$ligne['conn_name']}");
        if (!file_exists("/etc/artica-postfix/settings/Daemons/ipsec_status_time_{$ligne['conn_name']}")) {
            $tunnel_time='---';
        } elseif (empty($tunnel_time_file)) {
            $tunnel_time='---';
        } else {
            $tunnel_time=$tunnel_time_file;
        }



        $status_tunnel = file_get_contents("/etc/artica-postfix/settings/Daemons/ipsec_status_{$ligne['conn_name']}");
        if (!file_exists("/etc/artica-postfix/settings/Daemons/ipsec_status_{$ligne['conn_name']}")) {
            $status_tunnel_result="0";
        } elseif (empty($status_tunnel)) {
            $status_tunnel_result="0";
        } else {
            $status_tunnel_result=$status_tunnel;
        }

        $html[]="<tr class='$TRCLASS{$MUTED}'  id='conn-{$ligne["ID"]}'>";
        $html[]="<td nowrap>{$ligne["order"]}</td>";
        $html[]="<td nowrap><span class='fa fa-desktop' ></span>&nbsp;$url{$ligne["conn_name"]}</td>";
        $html[]="<td nowrap ><span class='fa fa-clock'> </span>&nbsp;$tunnel_time</td>";
        $html[]="<td nowrap ><span class='fas fa-users'> </span>&nbsp;$url2$status_tunnel_result</td>";
        $html[]="<td nowrap >$enable</td>";
        $html[]="<td nowrap >$up&nbsp;$down&nbsp;$edit&nbsp;$delete</td>";
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
    $html[]="<div><i>$Items &laquo;$sql&raquo;</i></div>
	<script>
	NoSpinner();\n".@implode("\n", $tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable(
	{
	\"filtering\": {
	\"enabled\": true
	},
	\"sorting\": {
	\"enabled\": true
	}
	
	}
	
	
	); });
	

	</script>";

    echo @implode("\n", $html);
}
