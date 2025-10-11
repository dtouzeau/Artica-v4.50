<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
page();

function delete_js(){
	$mac=$_GET["delete-js"];
	$tpl=new template_admin();
	$tpl->js_confirm_delete($mac, "delete", $mac,"dhcpd_hosts_refresh_table()");
	
}

function delete(){
	$mac=$_POST["delete"];
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM dhcpd_hosts WHERE mac='$mac'");
	
}

function page(){
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
    if(!isset($_SESSION["DHCPR_SEARCH"])){
        $_SESSION["DHCPR_SEARCH"]="";
    }

    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{dhcp_requests}",ico_eye,"{dhcp_requests_explain}","$page?search-form=yes","dhcp-requests","progress-dhcrequests-restart",true);


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_DHCP} {reservations}",$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$token=null;
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$created=$tpl->_ENGINE_parse_body("{created}");
	$updated=$tpl->_ENGINE_parse_body("{updated}");
	$func=$_GET["func"];
    $function=$_GET["function"];
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log",
            "progress-dhcrequests-restart",
            "$function()"
        );

        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";

        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    $t=time();
    $html[]=$tpl->_ENGINE_parse_body("
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	$aliases["src_ip"]="ipaddr";
	$aliases["ipaddr"]="ipaddr";
	$aliases["address"]="ipaddr";
	$aliases["mac"]="mac";
	
	$_SESSION["DHCPR_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])),$aliases);

    $ss="*{$search["Q"]}*";
    $ss=str_replace("**","%",$ss);
    $ss=str_replace("*","%",$ss);
    $ss=str_replace("%%","%",$ss);

    $qeury="WHERE (( TEXT(mac) LIKE '$ss') OR (TEXT(ipaddr) LIKE '$ss') OR (hostname LIKE '$ss'))";
	
	$q=new postgres_sql();
	$sql="SELECT * FROM dhcpd_hosts $qeury ORDER BY updated DESC LIMIT {$search["MAX"]}";
	$results=$q->QUERY_SQL($sql);
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$hostname</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$addr</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$ComputerMacAddress</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$created</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$updated</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="$func()";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	
	if(!$q->ok){
		
		echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";
	}
	
	$cmp=new computers();
	
	$TRCLASS=null;
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$ligne["hostname"]=trim($ligne["hostname"]);
		if($ligne["mac"]==null){continue;}
		$color="black";
        $href=null;
		$uid=$cmp->ComputerIDFromMAC($ligne["mac"]);
		
		
		if($uid<>null){
			$js=MEMBER_JS($uid,1,1);
			$href="<a href=\"javascript:blur()\" OnClick=\"$js\" 
			style='font-size:16px;text-decoration:underline;color:$color'>";
        }
		
		if($ligne["hostname"]==null){$ligne["hostname"]="&nbsp;";}
		if($ligne["ipaddr"]==null){$ligne["ipaddr"]="&nbsp;";}


        if(!isset($MAIN[$ligne["mac"]])){
			$ligne2=@pg_fetch_array($q->QUERY_SQL("SELECT mac,fullhostname,proxyalias FROM hostsnet WHERE mac='{$ligne["mac"]}'"));
			$MAIN[$ligne["mac"]]=serialize($ligne2);
		}
		
		$ligne2=unserialize($MAIN[$ligne["mac"]]);
		
		if($ligne2["mac"]<>null){
			$jshost="Loadjs('fw.edit.computer.php?mac=".urlencode($ligne["mac"])."&CallBackFunction={$GLOBALS["jsAfterEnc"]}')";
			$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$jshost\" style='text-decoration:underline'>";
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
		

		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=".urlencode($ligne["mac"])."')","ASDCHPAdmin");
		
		$html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%'>$bton</td>";
        $html[]="<td class=\"$text_class\"><strong>{$ligne["hostname"]}</strong></td>";
		$html[]="<td class=\"$text_class\">{$ligne["ipaddr"]}</a></td>";
		$html[]="<td class=\"$text_class\">$href{$ligne["mac"]}</a></td>";
		$html[]="<td class=\"$text_class\">{$ligne["created"]}</a></td>";
		$html[]="<td class=\"$text_class\">{$ligne["updated"]}</a></td>";
		$html[]="<td style='width:1%'>$delete</a></td>";
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
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('.footable').footable({ \"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true } } ) });
</script>";

			echo @implode("\n", $html);

}
