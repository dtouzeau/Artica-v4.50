<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["buttons-hosts"])){echo base64_decode($_GET["buttons-hosts"]);exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["host"])){host_js();exit;}
if(isset($_GET["host-popup"])){host_popup();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["zmd5"])){host_save();exit;}
if(isset($_GET["host-delete"])){host_delete_js();exit;}
if(isset($_POST["host-delete"])){host_delete();exit;}
page();


function host_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

	$host=$_GET["host"];
	$title="{new_item}";
	if($host<>null){
		$q=new lib_sqlite("/home/artica/SQLITE/etc_hosts.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM net_hosts WHERE zmd5='$host'");
		$title="{$ligne["ipaddr"]}: {$ligne["hostname"]}";
		
	}
	return $tpl->js_dialog1($title, "$page?host-popup=$host&function={$_GET["function"]}");
	
}

function host_delete_js(){
	$tpl=new template_admin();
	$host=$_GET["host-delete"];
    $id=$_GET["id"];
	$q=new lib_sqlite("/home/artica/SQLITE/etc_hosts.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM net_hosts WHERE zmd5='$host'");
    if(!$q->ok){
        return $tpl->js_error_stop($q->mysql_error);
    }
    if(!isset($ligne["hostname"])){
        $ligne["hostname"]=$host;
    }

	$tpl->js_confirm_delete($ligne["hostname"], "host-delete", $host,"$('#$id').remove()");
}
function host_delete():bool{
	$q=new lib_sqlite("/home/artica/SQLITE/etc_hosts.db");
	$host=$_POST["host-delete"];
    $ligne=$q->mysqli_fetch_array("SELECT * FROM net_hosts WHERE zmd5='$host'");
    $hostname=$ligne["hostname"];

	$q->QUERY_SQL("DELETE FROM net_hosts WHERE zmd5='$host'");
	if(!$q->ok){echo $q->mysql_error;return false;}
    $q->QUERY_SQL("DELETE FROM net_hosts WHERE ipaddr='$host'");
    $sock=new sockets();
    $sock->REST_API("/system/hosts");
    return admin_tracks("Delete $hostname host from hosts file");

}

function host_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$host=$_GET["host-popup"];
	$title="{new_item}";
	$bt="{add}";
	$jsafter="dialogInstance1.close();{$_GET["function"]}();";
	if($host<>null){
		$q=new lib_sqlite("/home/artica/SQLITE/etc_hosts.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM net_hosts WHERE zmd5='$host'");
		$title="{$ligne["ipaddr"]}: {$ligne["hostname"]}";
		$bt="{apply}";
	}
	$form[]=$tpl->field_hidden("zmd5", $ligne["zmd5"]);
	$form[]=$tpl->field_ipaddr("ipaddr", "{ipaddr}", $ligne["ipaddr"],true);
	$form[]=$tpl->field_text("hostname", "{hostname}", $ligne["hostname"],true);
	$form[]=$tpl->field_text("alias", "{alias}", $ligne["alias"],true);
	
	echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator");
}

function host_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/etc_hosts.db");
	$md5=$_POST["zmd5"];

    $_POST["ipaddr"]=$tpl->CLEAN_BAD_CHARSNET($_POST["ipaddr"]);
    $_POST["alias"]=$tpl->CLEAN_BAD_CHARSNET($_POST["alias"]);
    $_POST["hostname"]=$tpl->CLEAN_BAD_CHARSNET($_POST["hostname"]);
    $sock=new sockets();

	if($md5==null){
		$md5=md5("{$_POST["ipaddr"]}{$_POST["hostname"]}");
	
		$q->QUERY_SQL("INSERT OR IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`,`alias`) VALUES
			('$md5','{$_POST["ipaddr"]}','{$_POST["hostname"]}','{$_POST["alias"]}')","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}
        admin_tracks("Add a new host in hosts file {$_POST["hostname"]} - {$_POST["ipaddr"]}");
        $sock->REST_API("/system/hosts");
		return true;
	}
	$sql="UPDATE net_hosts SET ipaddr='{$_POST["ipaddr"]}',hostname='{$_POST["hostname"]}',
	alias='{$_POST["alias"]}' WHERE zmd5='$md5'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return false;}

    $sock->REST_API("/system/hosts");
    return admin_tracks("Edit a host in hosts file {$_POST["hostname"]} - {$_POST["ipaddr"]}");

}



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}

    $html=$tpl->page_header("{etc_hosts}","fa-duotone fa-laptop-file",
        "{host_explain}<div id='buttons-hosts'></div>",null,
        "hostfile","progress-etchost-restart",true,"table-loader-my-etchosts"
    );


	if(isset($_GET["main-page"])){
        $tpl=new template_admin("{my_computers}",$html);
        echo $tpl->build_firewall();
        return;
    }
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $users=new usersMenus();
    $search=$_GET["search"];
    $MAX=150;


    $search="%$search%";
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }


	$q=new lib_sqlite("/home/artica/SQLITE/etc_hosts.db");
	$sql="SELECT * FROM net_hosts WHERE (hostname LIKE '$search' OR alias LIKE '$search' OR ipaddr LIKE '$search' ) ORDER BY hostname LIMIT $MAX";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo "<div class='alert alert-danger'>$q->mysql_error</div>";
	}



    if($users->AsDnsAdministrator) {

        $topbuttons[] = array("Loadjs('$page?host=&function={$_GET["function"]}');", ico_plus, "{new_item}");



    }


	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-my-etchosts' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{hostname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{alias}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $id=md5(serialize($ligne).$index);
		$zmd5=$ligne["zmd5"];
		$ipaddr=$ligne['ipaddr'];
		$hostname=$ligne['hostname'];
		$alias=$ligne['alias'];
		if(strlen($zmd5)<5){
            $zmd5=$ipaddr;
        }
        $zmd5=urlencode($zmd5);
		$js="Loadjs('$page?host=$zmd5&function=$function')";
		
		$html[]="<tr class='$TRCLASS' id='$id'>";
		$html[]="<td style='width:1%' nowrap><i class=\"fa fa-desktop\"></i>&nbsp;&nbsp;". texttooltip($ipaddr,null,$js)."</td>";
		$html[]="<td style='width:1%' nowrap>". texttooltip($hostname,null,$js)."</td>";
		$html[]="<td>". texttooltip($alias,null,$js)."</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('$page?host-delete=$zmd5&id=$id')","AsDnsAdministrator") ."</td>";
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
	$html[]="</table><div><small></small></div>";

    $configure=$tpl->framework_buildjs("/system/hosts",
        "system.hosts","system.hosts.log","progress-etchost-restart");

    $topbuttons[] = array($configure, ico_save, "{rebuild}");


    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{etc_hosts}";
    $TINY_ARRAY["ICO"]="fa-duotone fa-laptop-file";
    $TINY_ARRAY["EXPL"]="{host_explain}";
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( {\"filtering\": {\"enabled\": false},\"sorting\": {\"enabled\": true} } ); });
	$headsjs
</script>";

			echo $tpl->_ENGINE_parse_body($html);
}

function BuildRequests($stringtofind){
	$LIMIT=200;
	$stringtofind=trim(strtolower($stringtofind));
	$stringtofind=str_replace("  ", " ", $stringtofind);
	$stringtofind=str_replace("  ", " ", $stringtofind);
	$stringtofind=str_replace("  ", " ", $stringtofind);
	$ipClass=new IP();
	$fixed=false;
	
	if(preg_match("#limit\s+([0-9]+)#", $stringtofind,$re)){
		$stringtofind=trim(str_replace("limit {$re[1]}", "", $stringtofind));
		$LIMIT=$re[1];
	}
	
	
	if(strpos("  $stringtofind", "+fixed")){
		$fixed=true;
		$stringtofind=str_replace("+fixed", "", $stringtofind);
		$stringtofind=trim($stringtofind);
	}
	

	
	
	$ORDERL="DESC";
	$ORDERBY="ORDER BY fullhostname";
	if(strpos("  $stringtofind", "order by time")){
		$ORDERBY ="ORDER BY updated";
		$stringtofind=str_replace("order by time", "", $stringtofind);
		$stringtofind=trim($stringtofind);
	}
	if(strpos("  $stringtofind", "order by ip")){
		$ORDERBY ="ORDER BY ipaddr";
		$stringtofind=str_replace("order by ip", "", $stringtofind);
		$stringtofind=trim($stringtofind);
	}
	if(strpos("  $stringtofind", "order by name")){
		$ORDERBY ="ORDER BY ipaddr";
		$stringtofind=str_replace("order by name", "", $stringtofind);
		$stringtofind=trim($stringtofind);
	}
	if(strpos("  $stringtofind", "order by alias")){
		$ORDERBY ="ORDER BY proxyalias";
		$stringtofind=str_replace("order by alias", "", $stringtofind);
		$stringtofind=trim($stringtofind);
	}

	if(strpos("  $stringtofind", "+asc")){
		$ORDERL="ASC";
		$stringtofind=str_replace("+asc", "", $stringtofind);
		$stringtofind=trim($stringtofind);
	}
	if(strpos("  $stringtofind", "+desc")){
		$ORDERL="DESC";
		$stringtofind=str_replace("+desc", "", $stringtofind);
		$stringtofind=trim($stringtofind);
	}

	if(preg_match("#(.+?)\s+\(#", $stringtofind,$re)){$stringtofind=trim($re[1]);}

	$stringtofind=str_replace("**", "*", $stringtofind);
	$stringtofind2=str_replace("*", "", $stringtofind);
	$stringtofind=str_replace("*", ".*?", $stringtofind);

	$INET=false;
	$MAC=false;
	if($ipClass->isIPAddressOrRange($stringtofind2)){
		$OR[]="(inet '$stringtofind2' = ipaddr )";
		$INET=true;
	}

	if(!$INET){
		if(preg_match("#^[0-9\.]+$#", $stringtofind2)){
			if($stringtofind2<>null){
				$tt=explode(".",$stringtofind2);

                foreach ($tt as $index=>$value){
					if(strlen( (string) $value)>3){$tt[$index]="0";}
				}

				if(!isset($tt[1])){$tt[1]="0";}
				if(!isset($tt[2])){$tt[2]="0";}
				if(!isset($tt[3])){$tt[3]="0";}



				$tipaddr=@implode(".", $tt);
				if($tipaddr<>"0.0.0.0"){
					$OR[]="( inet '$tipaddr' >= ipaddr )";
				}
			}
		}
	}

	if($ipClass->IsvalidMAC($stringtofind2)){
		$OR[]="( mac='$stringtofind2' )";
		$MAC=true;
	}



	if(!$MAC){
		if($stringtofind2<>null){
			if(preg_match("#^[0-9a-z]+(:|-)[0-9a-z]+#", $stringtofind2)){
				$tt=explode(":",$stringtofind2);

                foreach ($tt as $index=>$value){if(strlen( (string) $value)>2){$tt[$index]="00";}}

				if(!isset($tt[1])){$tt[1]="00";}
				if(!isset($tt[2])){$tt[2]="00";}
				if(!isset($tt[3])){$tt[3]="00";}
				if(!isset($tt[4])){$tt[4]="00";}
				if(!isset($tt[5])){$tt[5]="00";}
				$tipaddr=@implode(":", $tt);
				if($tipaddr<>"00:00:00:00:00:00"){
					$OR[]="( mac >='$tipaddr' )";
					$MAC=true;
				}
			}
		}

	}

	if($INET==false){
		if($MAC==false){
			$OR[]="( fullhostname ~ '$stringtofind' )";
			$OR[]="( hostname ~ '$stringtofind' )";
			$OR[]="( hostalias1 ~ '$stringtofind' )";
			$OR[]="( hostalias2 ~ '$stringtofind' )";
			$OR[]="( hostalias3 ~ '$stringtofind' )";
			$OR[]="( hostalias4 ~ '$stringtofind' )";
		}
	}

	if($fixed){$AND1=" AND dhcpfixed=1 ";}


	return "SELECT * FROM hostsnet WHERE ( ".@implode(" OR ", $OR).")$AND1 $ORDERBY $ORDERL LIMIT $LIMIT";
}