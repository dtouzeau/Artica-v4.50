<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc"); if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
$users=new usersMenus(); if (!$users->AsVPNManager) {
    exit();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}

if (isset($_GET["download"])) {
    download();
    exit;
}
if (isset($_GET["delete-js"])) {
    delete_rule_js();
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
page();



function page()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("strongswan.php?refresh-sessions=yes");
    $strongSwanCNXNUmber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanCNXNUmber"));
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{APP_STRONGSWAN} {strongswan_real_time_sessions}</h1><p>$btn</p><p>$strongSwanCNXNUmber {sessions}</p></div>
	<div class=\"col-sm-8\" style='padding-top:20px'></div>
	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/ipsec-sessions');	
	LoadAjax('table-loader','$page?main=yes');

	</script>";

    $tpl=new templates();
    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Sessions',$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);
}

function main()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $vpn=new strongswan();
    $nic=new networking();
    $sock=new sockets();
    $page=CurrentPageName();
    
    $since=$tpl->javascript_parse_text("{since}");
    $conn_name=$tpl->javascript_parse_text("{tunnel}");
    $status=$tpl->javascript_parse_text("{status}");
    $local_ip_address=$tpl->javascript_parse_text("{remote_ip_address}");
    $ipaddr=$tpl->javascript_parse_text("{local_ip_address}");
    $userid=$tpl->javascript_parse_text("{member}");
    $BytesReceived=$tpl->javascript_parse_text("{received}");
    $BytesSent=$tpl->javascript_parse_text("{sended}");
    
    $html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    
    
    $TRCLASS=null;
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$conn_name}</th>";
    
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$local_ip_address}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$userid}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Remote vips / traffic selector</th>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$BytesReceived}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$BytesSent}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$since}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $xtpl=new templates();


    
    //$GLOBALS["CLASS_SOCKETS"]->getFrameWork("strongswan.php?refresh-sessions=yes");
    $sessions=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanClientsArray"));


    foreach ($sessions as $j => $v) {
        $GLOBALS['bp']=0;
        $GLOBALS['time']=0;
        if (is_array($v)) {
            $html[]="<tr class='$TRCLASS' >";
            foreach ($v as $k => $w) {
                $html[]="<td>{$k}</td>";
                if (is_array($w)) {
                    foreach ($w as $t=>$l) {

                      
                        

                        if ($t=='state') {
                            $html[]="<td>{$l}</td>";
                        }
                            
                        if ($t=='remote-host') {
                            $html[]="<td>{$l}</td>";
                        }
                        if ($t=='established') {
                            $GLOBALS['time']=secondsToTime($l);

                        }
                        if ($t=='child-sas') {
                            if (count($l)>=2){
                                $x= count($l)-1;
                                for($i = 0; $i < $x; ++$i) {
                                    array_shift($l);
                                }
                                //print_r($l);
                            }
                        }
                        
                            
        

                        $username=null;
                        if (array_key_exists('remote-eap-id', $w)) {
                            if ($t=='remote-eap-id') {
                                $username= "<td>{$l}</td>";
                            }
                        } else {
                            if ($t=='remote-id') {
                                $username="<td>{$l}</td>";
                            }
                        }

                        $html[]=$username;

                        if (is_array($l)) {
                            foreach ($l as $p=>$o) {
                                if ($p=='remote-vips') {
                                    $html[]="<td>$o</td>";
                                    $GLOBALS['bp']=1;
                                }
                                else{
                                    if($GLOBALS['bp']==0) {
                                        if (is_array($o)) {
                                            foreach ($o as $e => $r) {
                                                if ($e == 'remote-ts') {

                                                    $html[] = "<td>{$r[0]}</td>";
                                                }
                                            }
                                        }
                                    }
                                }
                                if (is_array($o)) {
                                    foreach ($o as $e=>$r) {
                                        if ($e=='bytes-in') {
                                            $kb=FormatBytes($r/1024);
                                            $html[]="<td>{$kb}</td>";
                                        }
                                        if ($e=='bytes-out') {
                                            $kb=FormatBytes($r/1024);
                                            $html[]="<td>{$kb}</td>";
                                        }
                                        if ($e=='install-time') {
                                            $html[]="<td>{$GLOBALS['time']}</td>";
                                        }
                                    }
                                }
                            }
                        }

                    }
                }
            }
            $html[]="</tr>";
        }
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
    $html[]="
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
	
function CreateNewVPNClient(){
	Loadjs('$page?ruleid-js=');
}
var xxXGenerateVPNConfig= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){ Loadjs(tempvalue); }
	
}

function XGenerateVPNConfig(uid){
	var XHR = new XHRConnection();
	XHR.appendData('GenerateProgress',uid);
	XHR.sendAndLoad('$page', 'POST',xxXGenerateVPNConfig);		
}	

	</script>";
    
    echo @implode("\n", $html);
}
function secondsToTime($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%h hours, %i minutes and %s seconds');
}