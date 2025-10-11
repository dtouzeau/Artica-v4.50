<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["top"])){top();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
js();



function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{leases}","$page?top=yes",900);
}
function top(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);

}

function table(){
	$tpl=new template_admin();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
    $function=$_GET["function"];
	$t=time();


	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";


    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/kea/leases"));

    if(!$data->Status){
        echo $tpl->div_error($data->Error);
        return false;
    }



	$aliases["src_ip"]="ipaddr";
	$aliases["ipaddr"]="ipaddr";
	$aliases["address"]="ipaddr";
	$aliases["mac"]="mac";
	
	$_SESSION["DHCPL_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])),$aliases);



    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$hostname</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$addr</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$ComputerMacAddress</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{expire}</th>";

	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $func=$_GET["function"];
    $jsAfter="$func()";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

    $cmp            = new computers();
    $q=new postgres_sql();
	$TRCLASS=null;

    $search=$_GET["search"];
    if(strlen($search)>0){
        $search=str_replace(".", "\.", $search);
        $search=str_replace("*", ".*?", $search);
        $search=str_replace("/", "\/", $search);
    }


    $ALREADY=array();

    foreach ($data->leases as $lease){
        if(strlen($lease->hwaddr)<5){
            continue;
        }
        if(isset($ALREADY[$lease->hwaddr])){
            continue;
        }

		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;


        $ALREADY[$lease->hwaddr]=true;
		$ligne["hostname"]=$lease->hostname;
        $ligne["mac"]=$lease->hwaddr;
        $ligne["ipaddr"]=$lease->address;

        if(strlen($search)>1){
            if(!preg_match("#$search#",$lease->hostname.$lease->hwaddr.$lease->address)){
                continue;
            }
        }


        $expire=$tpl->time_to_date($lease->expire,true);
        $href=null;
		$uid=$cmp->ComputerIDFromMAC($ligne["mac"]);
		if($uid<>null){$uid= " ($uid)";}
        $MANU=$tpl->MacToVendor($ligne["mac"]);
        if($MANU<>null){$MANU=" ($MANU)";}

        $ligne2=$q->mysqli_fetch_array("SELECT mac,fullhostname,proxyalias FROM hostsnet WHERE mac='{$ligne["mac"]}'");
        if(!$ligne2){
            echo $q->mysql_error;
        }

		
		if($ligne2["mac"]<>null){
			$jshost="Loadjs('fw.edit.computer.php?mac=".urlencode($ligne["mac"])."&CallBackFunction={$GLOBALS["jsAfterEnc"]}')";
			$href="<a href=\"javascript:blur();\" OnClick=\"$jshost\" style='text-decoration:underline'>";
			$ligne["hostname"]=$ligne2["fullhostname"];
			if($ligne2["proxyalias"]<>null){$ligne["hostname"]=$ligne["hostname"]." ({$ligne2["proxyalias"]})";}
            $bton=$tpl->icon_loupe(true,$jshost);
		}else{
            $ffields[]="mac=".urlencode($ligne["mac"]);
            $ffields[]="hostname=".urlencode($ligne["hostname"]);
            $ffields[]="ipaddr=".urlencode($ligne["ipaddr"]);
            $ffields[]="CallBackFunction=".$GLOBALS["jsAfterEnc"];
            $jshost="Loadjs('fw.add.computer.php?".@implode("&",$ffields)."')";
            $bton=$tpl->icon_add($jshost);
        }
		
	
		
		$html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%'>$bton</td>";
		$html[]="<td class=\"$text_class\"><strong>{$ligne["hostname"]}$uid</strong></td>";
		$html[]="<td class=\"$text_class\" width='1%' nowrap>{$ligne["ipaddr"]}</a></td>";
		$html[]="<td class=\"$text_class\"  width='1%' nowrap>$href{$ligne["mac"]}{$MANU}</a></td>";
		$html[]="<td class=\"$text_class\">$expire</td>";

		
		$html[]="</tr>";
		

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table></div>";
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": {\"enabled\": false},\"sorting\": {\"enabled\": true } } ); });
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}


