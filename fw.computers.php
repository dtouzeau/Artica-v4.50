<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["delete-sql"])){delete_sql_js();exit;}
if(isset($_POST["delete-sql"])){delete_sql_perform();exit;}
if(isset($_POST["delete-all"])){delete_all_perform();exit;}
if(isset($_GET["delete-all"])){delete_all_js();exit;}
if(isset($_GET["export"])){export();exit;}
if(isset($_GET["import"])){import_computers_js();exit;}
if(isset($_GET["import-popup"])){import_computers_popup();exit;}
if(isset($_GET["export-progress"])){export_progress();exit;}
if(isset($_GET["download-export"])){export_download();exit;}
if(isset($_GET["search-my-computers-desktops"])){page2();exit;}
page();


function page2(){

    $tpl=new template_admin();

    $icons[]=array("ICON"=>"fas fa-plus-circle","JS"=>"Loadjs('fw.add.computer.php?RefreshTable=%func')");
    $html[]=$tpl->table_menu($icons,CurrentPageName(),null,"CallBackMyComps")."</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function page(){
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["DHCPR_SEARCH"]==null){$_SESSION["DHCPR_SEARCH"]="limit 200";}
    $page=CurrentPageName();
    $html=$tpl->page_header("{my_computers}","fa-solid fa-computer","{explain_mycomputers}","$page?search-my-computers-desktops=yes","computers","progress-firehol-restart",false,"search-my-computers-desktops");


	if(isset($_GET["main-page"])){
	    $tpl=new template_admin("{my_computers}",$html);
	    echo $tpl->build_firewall();
	    return;
	}
	echo $tpl->_ENGINE_parse_body($html);

}
function delete_all_js(){
    $tpl = new template_admin();
    $CallBackFunction = $_GET["CallBackFunction"];
    $tpl->js_confirm_delete("{delete_all}", "delete-all", $tpl->_ENGINE_parse_body("{delete_all}"), "$CallBackFunction()");
}
function import_computers_js(){
    $RefreshTable=$_GET["RefreshTable"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{importing_form_csv_file}", "$page?import-popup=yes&RefreshTable=$RefreshTable",650);
}
function import_computers_popup(){
    $tpl = new template_admin();
    $page=CurrentPageName();
    $RefreshTable=$_GET["RefreshTable"];
    $html[]="<H2>{importing_form_csv_file}</H2>";
    $html[]="<div id='import-computers-div'></div>";
    $html[]="<div class='center'>". $tpl->button_upload("{upload}",$page,null,"&RefreshTable=$RefreshTable")."</div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function delete_sql_js(){
    $tpl = new template_admin();
    $query = $_GET["delete-sql"];
    $CallBackFunction = $_GET["CallBackFunction"];
    $tpl->js_confirm_delete("{your_search_results}", "delete-sql", $query, "$CallBackFunction()");
}
function delete_sql_perform(){
    $query = base64_decode($_POST["delete-sql"]);
    $query=str_replace("APUPRES","~",$query);
    $q=new postgres_sql();
    $q->QUERY_SQL($query);
    if(!$q->ok){echo "{$query}\n$q->mysql_error";}

}

function delete_all_perform(){
    $q=new postgres_sql();
    $q->QUERY_SQL("TRUNCATE TABLE hostsnet");
    if(!$q->ok){echo "{TRUNCATE TABLE hostsnet}\n$q->mysql_error";}
}

function export_progress(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $js=$tpl->framework_buildjs("/computers/export/csv",
        "export-computers.progress","export-computers.log","export-computers-div",
    "dialogInstance6.close();document.location.href='$page?download-export=yes';");

    echo "<div id='export-computers-div'></div><script>$js</script>";

}
function file_uploaded(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];
    $RefreshTable=$_GET["RefreshTable"];
    if($RefreshTable<>null){$RefreshTable="$RefreshTable();";}
    $tpl=new template_admin();
    $file=base64_encode($file);
    $jsrestart=$tpl->framework_buildjs("/computers/import/csv/$file",
        "export-computers.progress","export-computers.log","import-computers-div",
        "dialogInstance6.close();$RefreshTable");
    echo $jsrestart;
}
function export(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->js_dialog6("{export} {computers}", "$page?export-progress=yes",650);
    return true;
}

function export_download(){
    $path="/usr/share/artica-postfix/ressources/logs/computers.csv";
    header('Content-type: application/vnd.ms-excel');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"computers.csv\"");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize($path);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($path);
    @unlink($path);


}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    if(!isset($_GET["t"])){
        $_GET["t"]=time();
    }
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}

    $search="";
    if(isset($_GET["search"])){
        $search=$_GET["search"];
    }
    $stringtofind="";
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$ComputerMacAddress=$tpl->javascript_parse_text("{ComputerMacAddress}");
	$addr=$tpl->javascript_parse_text("{addr}");
	$alias=$tpl->_ENGINE_parse_body("{alias}");
    if(!strlen($search)>1) {
        $stringtofind = url_decode_special_tool($tpl->CLEAN_BAD_XSS($_GET["search"]));
    }
	$sql=BuildRequests($stringtofind);
	$function=base64_encode($_GET["function"]);

    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log",
            "progress-firehol-restart",
            "{$_GET["function"]}()"
        );

        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";

        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    $q=new postgres_sql();
    VERBOSE($sql,__LINE__);
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo "<div class='alert alert-danger'>$q->mysql_error</div>";
        return false;
	}
    $dhcpfixed_lan=$tpl->_ENGINE_parse_body("{dhcpfixed}");
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$hostname</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$alias</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$addr</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$ComputerMacAddress</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $TRCLASS=null;
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$mac=$ligne["mac"];
		$ipaddr=$ligne['ipaddr'];
		$text_class=null;
		$proxyalias=$ligne['proxyalias'];
		$fullhostname=trim($ligne['fullhostname']);
        if (strlen($fullhostname)<2) {
            $fullhostname = $ligne["hostname"];
        }
        if (strlen($fullhostname)<2) {
            $fullhostname="{unknown}";
        }
		$dhcpfixed=$ligne['dhcpfixed'];
		$mac_enc=urlencode($mac);
		$updated=strtotime($ligne["updated"]);
		$date=$tpl->time_to_date($updated,true);
		$dhcpfixed_text=array();
		$unknown=$ligne["unknown"];
        $dnsfixed=$ligne["dnsfixed"];
        $dhcpfixed_label="";
		$MacToVendor=$tpl->MacToVendor($mac);
        $dnsfixed_label="";
        if($MacToVendor<>null){$MacToVendor=" ($MacToVendor)";}

		$tt=array();
		foreach ($ligne as $index=>$value){
            $dnsfixed_label=null;
			if(is_numeric($value)){continue;}
			if(is_numeric($index)){continue;}
			if($index=="scanreport"){continue;}
			if(trim($value==null)){continue;}
			if(trim($value=="0.0.0.0")){continue;}
			$tt[]="<strong>".$tpl->javascript_parse_text("{{$index}}")."</strong>: $value<br>";
		}
		
		$dhcpfixed_text[]=$date;
		if($dhcpfixed==1){
            $dhcpfixed_label= "&nbsp;<span class='label label-primary'>$dhcpfixed_lan</span>";

        }
		$js="Loadjs('fw.edit.computer.php?mac=$mac_enc&CallBackFunction=$function')";

		if($unknown==1){
            $text_class="text-danger";
        }

        if($EnableDNSDist==1) {
            if ($dnsfixed == 1) {
                $dnsfixed_label = "&nbsp;<span class='label label-info'>{dns_entry}</span>";
            }

        }

		$html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\"><strong><i class='fa fa-desktop'></i>&nbsp;". texttooltip($fullhostname,null,$js)."</strong><small>$MacToVendor</small>$dhcpfixed_label$dnsfixed_label</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>". texttooltip($proxyalias,@implode("", $tt),$js)."</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>". texttooltip($ipaddr,@implode("", $tt),$js)."</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>". texttooltip($mac,@implode("<br>", $tt),$js)."</td>";
		$html[]="<td class=\"$text_class\">".@implode("<br>",$dhcpfixed_text)."</td>";
		$html[]="<td class=\"center\" style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('fw.edit.computer.php?remove=$mac_enc&CallBackFunction=$function')","computer") ."</td>";
		$html[]="</tr>";

	}
if(isset($GLOBALS["DELETE_SQL"])) {
    $delete_sql = urlencode(base64_encode($GLOBALS["DELETE_SQL"]));
}
	$html[]="</tbody>";
	$html[]="<tfoot>";
    $html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<div class='center'>";


    $CallBackMyComps=base64_encode("$function()");
    $add="Loadjs('fw.add.computer.php?CallBackFunction=$CallBackMyComps')";
    $topbuttons[] = array($add, ico_plus, "{new_computer}");
    $topbuttons[] = array("Loadjs('$page?export=yes')", ico_export, "{export}");
    $topbuttons[] = array("Loadjs('$page?import=yes&RefreshTable=$function')", ico_import, "{import}");
    if(strlen($stringtofind)>2) {
        $topbuttons[] = array("Loadjs('$page?delete-sql=$delete_sql&CallBackFunction=$function')", ico_trash, "{delete} $stringtofind");
    }
    $topbuttons[] = array("Loadjs('$page?delete-all=yes&CallBackFunction=$function')", ico_trash, "{delete_all}");


    $TINY_ARRAY["TITLE"]="{my_computers} $stringtofind";
    $TINY_ARRAY["ICO"]="fa-solid fa-computer";
    $TINY_ARRAY["EXPL"]="{explain_mycomputers}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";





    //$html[]="</div><div class='center' style='margin-top:10px;'><small>$sql</small></div>
    $html[]="<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
$jstiny
var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	LoadAjax('table-loader-my-computers','$page?table=yes');
}

function RuleGroupUpDown$t(ID,direction,eth){
	var XHR = new XHRConnection();
	XHR.appendData('rule-order', ID);
	XHR.appendData('direction', direction);
	XHR.appendData('eth', eth);
	XHR.sendAndLoad('firehol.nic.rules.php', 'POST',xRuleGroupUpDown$t);
}
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}

function BuildRequests($stringtofind){
	$LIMIT=200;
    $AND1="";
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

    if (strlen($stringtofind)<2){
        return "SELECT * FROM hostsnet $ORDERBY $ORDERL LIMIT $LIMIT";
    }

	if(preg_match("#(.+?)\s+\(#", $stringtofind,$re)){$stringtofind=trim($re[1]);}

	$stringtofind=str_replace("**", "*", $stringtofind);
	$stringtofind2=str_replace("*", "", $stringtofind);
	$stringtofind=str_replace("*", ".*?", $stringtofind);

	$INET=false;
	$MAC=false;

	if(!preg_match("#[a-z]+#",$stringtofind2)) {
        if (preg_match("#^[0-9]+\.[0-9]+$#", $stringtofind2)) {
            $OR[] = "(ipaddr << inet '$stringtofind2.0.0/16')";
            $INET = true;
        }
        if (preg_match("#^[0-9]+\.[0-9]+.[0-9]+$#", $stringtofind2)) {
            $OR[] = "(ipaddr << inet '$stringtofind2.0/24')";
            $INET = true;
        }
        if($ipClass->IsValid($stringtofind2)){
            $OR[]="( ipaddr = inet '$stringtofind2')";
            $INET=true;
        }
        if($ipClass->IsACDIR($stringtofind2)){
            $OR[]="(ipaddr << inet '$stringtofind2')";
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
    }

	if($ipClass->IsvalidMAC($stringtofind2)){
		$OR[]="( mac='$stringtofind2' )";
		$MAC=true;
	}



	if(!$MAC){
		if($stringtofind2<>null){
			if(preg_match("#^[0-9a-z]+(:|-)[0-9a-z]+#", $stringtofind2)){
                    $searchMAC="%$stringtofind2%";
                    $searchMAC=str_replace("%%", "%", $searchMAC);
                    $searchMAC=str_replace("%%", "%", $searchMAC);
					$OR[]="( mac::text LIKE '$searchMAC' )";
					$MAC=true;

			}
		}

	}

	if($INET==false){
		if($MAC==false){
			$OR[]="( fullhostname ~ '$stringtofind' )";
            $OR[]="( proxyalias ~ '$stringtofind' )";
			$OR[]="( hostname ~ '$stringtofind' )";
			$OR[]="( hostalias1 ~ '$stringtofind' )";
			$OR[]="( hostalias2 ~ '$stringtofind' )";
			$OR[]="( hostalias3 ~ '$stringtofind' )";
			$OR[]="( hostalias4 ~ '$stringtofind' )";
		}
	}

	if($fixed){$AND1=" AND dhcpfixed=1 ";}

    $GLOBALS["DELETE_SQL"]="DELETE FROM hostsnet WHERE ( ".@implode(" OR ", $OR).")$AND1";
    $GLOBALS["DELETE_SQL"]=str_replace("~","APUPRES",$GLOBALS["DELETE_SQL"]);
    return "SELECT * FROM hostsnet WHERE ( ".@implode(" OR ", $OR).")$AND1 $ORDERBY $ORDERL LIMIT $LIMIT";
}