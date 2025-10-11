<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

    if(isset($_GET["search"])){search();exit;}
popup();

function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,null,null,null);
}


function search(){
$tpl=new template_admin();
$page=CurrentPageName();

$eth_sql=null;
$token=null;
$class=null;
$t=$_GET["t"];
if(!is_numeric($t)){$t=time();}

$stringtofind=url_decode_special_tool($tpl->CLEAN_BAD_XSS($_GET["search"]));
$function=$_GET["function"];
$q=new postgres_sql();

$sql="SELECT * FROM prads_tot ORDER BY syn DESC";

    if($stringtofind<>null){
        $field="mac";
        if(strpos("   $stringtofind",".")>0){
            $field="ipaddr";
        }

        if(strpos("   $stringtofind","*")>0){
            $stringtofind=str_replace("*","%");
            $where="WHERE text($field) LIKE '$stringtofind'";
        }else{
            $where="WHERE $field='$stringtofind'";
        }

       if(preg_match("#\/[0-9]+#",$stringtofind)){
           $where="WHERE ipaddr << inet '$stringtofind'";
       }


        $sql="SELECT * FROM prads_tot $where ORDER BY syn DESC";
    }

$results=$q->QUERY_SQL($sql);

if(!$q->ok){
echo "<div class='alert alert-danger'><strong>$stringtofind</strong><br>$q->mysql_error<br>$sql</div>";
}

$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
$html[]="<thead>";
$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ComputerMacAddress}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{proto}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ports}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>SYN</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ACK</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>CLIENT</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>SERVER</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>UDP</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>FIN</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>RST</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ARP</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ICMP</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

        while ($ligne = pg_fetch_assoc($results)) {
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $mac=$ligne["mac"];
            $ipaddr=$ligne['ipaddr'];
            $protos=$ligne["protos"];
            $ports=$ligne["ports"];
            $syn=$ligne["syn"];
            $ack=$ligne["ack"];
            $client=$ligne["client"];
            $server=$ligne["server"];
            $udp=$ligne["udp"];
            $fin=$ligne["fin"];
            $rst=$ligne["rst"];
            $arp=$ligne["arp"];
            $icmp=$ligne["icmp"];

            $ipaddrenc=urlencode($ipaddr);
            $ipaddr_link=$tpl->td_href($ipaddr,null,"Loadjs('fw.network.prads.graphs.php?ipaddr=$ipaddrenc')");

            $html[]="<tr class='$TRCLASS'>";
            $html[]="<td width='1%' nowrap><i class='fa fa-desktop'></i></td>";
            $html[]="<td width='99%' nowrap><strong>$ipaddr_link</strong></td>";
            $html[]="<td nowrap><strong>$mac</strong></td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$protos</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$ports</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$syn</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$ack</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$client</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$server</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$udp</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$fin</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$rst</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$arp</td>";
            $html[]="<td width='1%' nowrap style='text-align: right'>$icmp</td>";
            $html[]="</tr>";
        }

        $html[]="</tbody>";
        $html[]="<tfoot>";

$html[]="<tr>";
    $html[]="<td colspan='15'>";
        $html[]="<ul class='pagination pull-right'></ul>";
        $html[]="</td>";
    $html[]="</tr>";
$html[]="</tfoot>";
$html[]="</table>";
$html[]="<div class='center'>";

$html[]="<script>
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    $(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

echo $tpl->_ENGINE_parse_body($html);

}