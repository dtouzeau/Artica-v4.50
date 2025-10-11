<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(isset($_POST["none"])){exit;}
if(isset($_GET["page"])){page();exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["purge-progress"])){purge_popup_js();exit;}
if(isset($_GET["purge-popup"])){purge_popup();exit;}
if(isset($_GET["last-isc-dhcp"])){last_isc_dhcp();exit;}
start();

function purge(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog_confirm_action("{squid_purge_dns_explain}","none","none","Loadjs('$page?purge-progress=yes')");
}
function purge_popup_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{empty_cache}","$page?purge-popup=yes");
}
function purge_popup(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";
    $ARRAY["CMD"]="dnsfilterd.php?purge=yes";
    $ARRAY["TITLE"]="{empty_cache}";
    $ARRAY["AFTER"]="dialogInstance1s.close();LoadAjax('dashboard-dnsfilterd','$page?page=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=$t')";
    $html="<div id='$t'></div><script>$jsrestart</script>";
    echo $html;
}

function start(){
    $page=CurrentPageName();
    $html="<div id='dashboard-dhcpd'></div>
<script>LoadAjax('dashboard-dhcpd','$page?page=yes');</script>";

    echo $html;
}

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $memcached=new lib_memcached();
    $q=new postgres_sql();

    $dhcpd_leases=$q->COUNT_ROWS_LOW("dhcpd_leases");
    $IncludeDHCPLdapDatabase=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IncludeDHCPLdapDatabase"));

    if($IncludeDHCPLdapDatabase==1) {
        $sql="SELECT count(*) as tcount FROM hostsnet WHERE dhcpfixed=1";
        $ligne=$q->mysqli_fetch_array($sql);
        $Fixed=$ligne["tcount"];
        $dhcpd_fixed =$Fixed;

    }
    $defined=0;
    $used=0;
    $touched=0;
    $percent=0;
    $free=0;
    $touch_percent=0;


        $json=json_decode(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_POOL_STATUS")));
        if(property_exists($json,"summary")) {

        $defined=$json->summary->defined;
        $used=$json->summary->used;
        $touched=$json->summary->touched;
        $percent=round($json->summary->percent,2);
        $free=$json->summary->free;
        $touch_count=$json->summary->touch_count;
        $touch_percent=round($json->summary->touch_percent,2);
    }

    $active_bg="lazur-bg";
    $free_bg="navy-bg";

    if($percent<1){$active_bg="gray-bg";}
    if($percent>80){$active_bg="yellow-bg";}
    if($percent>90){$active_bg="red-bg";}

    if($free<5){$free_bg="red-bg";}

//<i class="fas fa-desktop"></i> <i class="fas fa-eye"></i>
    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='padding:2px' width='33%'>".$tpl->widget_style1($active_bg,"fas fa-desktop","{active2} {computers} $used/$defined","{$percent}%")."</td>";
    $html[]="<td style='padding:2px' width='33%'>".$tpl->widget_style1($free_bg,"fas fa-desktop","{free} {computers}",$free)."</td>";
    $html[]="<td style='padding:2px' width='33%'>".$tpl->widget_style1("navy-bg","fas fa-eye","{seen_computers} $touched","{$touch_percent}%")."</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $html[]="<div id='last-isc-dhcp' style='margin-top:10px'></div>";
    $html[]="<script>function LoadDashBoardLastLease(){LoadAjax('last-isc-dhcp','$page?last-isc-dhcp=yes');}LoadDashBoardLastLease();</script>";



    echo $tpl->_ENGINE_parse_body($html);

}

function last_isc_dhcp(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $DHCPD_LEASE_LIST_LAST=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_LEASE_LIST_LAST"));
    $t=time();

    $html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='width:100%'>");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ComputerMacAddress}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{vendor}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{END}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $TRCLASS=null;
    $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");


    foreach ($DHCPD_LEASE_LIST_LAST as $MAC=>$MAIN){
        $mac_enc=urlencode($MAC);
        $fullhostname=$MAIN["HOTS"];
        $ipaddr=$MAIN["IP"];
        $MANU=$MAIN["MANU"];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $js="Loadjs('fw.edit.computer.php?mac=$mac_enc&CallBackFunction=LoadDashBoardLastLease')";
        if($MANU==null) {$MANU = $tpl->MacToVendor($MAC);}



        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td width='1%' nowrap><i class='fa fa-desktop'></i></td>";
        $html[]="<td width='1%' nowrap><strong>". $tpl->td_href($MAC,null,$js)."</strong></td>";
        $html[]="<td width='1%' nowrap><strong>". $tpl->td_href($ipaddr,null,$js)."</strong></td>";
        $html[]="<td>". $tpl->td_href($fullhostname,null,$js)."</td>";
        $html[]="<td width='1%' nowrap>$MANU</td>";
        $html[]="</tr>";


    }


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
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);

}






function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}