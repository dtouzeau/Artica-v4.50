<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();

function search_form():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
    return true;
}

function page():bool{
	if($_SESSION["DHCPL_SEARCH"]==null){$_SESSION["DHCPL_SEARCH"]="limit 200";}

    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{leases}",ico_timeout,"{leases_explain}","$page?search-form=yes","dhcp-leases","progress-dhcpleases-restart",true);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_DHCP} {leases}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
	$tpl=new template_admin();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
    $function="blur";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }


    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log",
            "progress-dhcpleases-restart",
            "$function()"
        );

        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";

        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
	
	
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
    $t=time();
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";

	$aliases["src_ip"]="ipaddr";
	$aliases["ipaddr"]="ipaddr";
	$aliases["address"]="ipaddr";
	$aliases["mac"]="mac";
	
	$_SESSION["DHCPL_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])),$aliases);



    $ss="*{$search["Q"]}*";
    $ss=str_replace("**","%",$ss);
    $ss=str_replace("*","%",$ss);
    $ss=str_replace("%%","%",$ss);

    $qeury="WHERE (( TEXT(mac) LIKE '$ss') OR (TEXT(ipaddr) LIKE '$ss') OR (hostname LIKE '$ss'))";


    $q=new postgres_sql();
	$sql="SELECT * FROM dhcpd_leases $qeury ORDER BY starts DESC LIMIT {$search["MAX"]}";
	$results=$q->QUERY_SQL($sql);
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$hostname</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$addr</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$ComputerMacAddress</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{start_time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{end_time}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $func=$_GET["func"];
    $jsAfter="$func()";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);


	
	if(!$q->ok){
		
		echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";
	}
	
	$cmp            = new computers();

	$icocp=ico_computer;
	$TRCLASS=null;
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$ligne["hostname"]=trim($ligne["hostname"]);
		if($ligne["mac"]==null){continue;}
		$href=null;
		$uid=$cmp->ComputerIDFromMAC($ligne["mac"]);
		if($uid<>null){$uid= " ($uid)";}
        $MANU=$tpl->MacToVendor($ligne["mac"]);
		
		$t1=strtotime($ligne["starts"]);
		$end=strtotime($ligne["ends"]);
		
		$t1_took=$tpl->javascript_parse_text(distanceOfTimeInWords($t1,time()));
		$t2_took=$tpl->javascript_parse_text(distanceOfTimeInWords(time(),$end));
		if($end<time()){$t2_took="-";}


        if($MANU<>null){$MANU=" ($MANU)";}

		if($ligne["hostname"]==null){$ligne["hostname"]="&nbsp;";}
		if($ligne["ipaddr"]==null){$ligne["ipaddr"]="&nbsp;";}

		$ligne2=@pg_fetch_array($q->QUERY_SQL("SELECT mac,fullhostname,proxyalias FROM hostsnet WHERE mac='{$ligne["mac"]}'"));
		
		if($ligne2["mac"]<>null){
			$jshost="Loadjs('fw.edit.computer.php?mac=".urlencode($ligne["mac"])."&CallBackFunction=$function')";
			$ligne["hostname"]=$ligne2["fullhostname"];
			if($ligne2["proxyalias"]<>null){$ligne["hostname"]=$ligne["hostname"]." ({$ligne2["proxyalias"]})";}
            $style="class='font-bold text-black'";

		}else{
            $ffields[]="mac=".urlencode($ligne["mac"]);
            $ffields[]="hostname=".urlencode($ligne["hostname"]);
            $ffields[]="ipaddr=".urlencode($ligne["ipaddr"]);
            $ffields[]="CallBackFunction=$function";
            $jshost="Loadjs('fw.add.computer.php?".@implode("&",$ffields)."')";
            $style="class='text-muted'";

        }
		
	    $hostname=$tpl->td_href($ligne["hostname"],"",$jshost);
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\"style='width: 99%' nowrap><span $style><i class='$icocp'></i>&nbsp;$hostname$uid</span></td>";
		$html[]="<td class=\"$text_class\" style='width: 1%' nowrap><span $style>{$ligne["ipaddr"]}</span></td>";
		$html[]="<td class=\"$text_class\" style='width: 1%' nowrap><span $style>$href{$ligne["mac"]}{$MANU}</span></td>";
		$html[]="<td class=\"$text_class\" style='width: 1%' nowrap><span $style>{$ligne["starts"]} ($t1_took)</span></td>";
		$html[]="<td class=\"$text_class\" style='width: 1%' nowrap><span $style>{$ligne["ends"]} ($t2_took)</span></td>";
		
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

    $jsrestart=$tpl->framework_buildjs("/dhcpd/service/leases",
        "dhcpd.leases.progress","dhcpd.leases.log","progress-dhcpleases-restart","$function()");

    $jsClean=$tpl->framework_buildjs("/dhcpd/leases/flush",
        "dhcdp.leases.empty.progress","dhcdp.leases.empty.progress.txt","progress-dhcpleases-restart","$function()");


    $TINY_ARRAY["TITLE"]="{leases}";
    $TINY_ARRAY["ICO"]=ico_timeout;
    $TINY_ARRAY["EXPL"]="{leases_explain}";
    $topbuttons[] = array($jsrestart, "fa fa-repeat", "{rescan}");
    $topbuttons[] = array($jsClean, ico_trash, "{purgeAll}");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
	<script>$jstiny
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": {\"enabled\": false},\"sorting\": {\"enabled\": true } } ) });
</script>";

			echo $tpl->_ENGINE_parse_body($html);
            return true;

}


