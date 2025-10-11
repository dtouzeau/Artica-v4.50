<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){search();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $searchBlock=$tpl->search_block($page,null);
	echo $tpl->_ENGINE_parse_body($searchBlock);

}

function srules():array{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID,rulename FROM dnsfw_acls");
    $FWRULES[0]="PASS";
    foreach ($results as $ligne){
        $FWRULES[$ligne["ID"]]=$ligne["rulename"];
    }
    return $FWRULES;
}

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $FWRULES=srules();
    $t=time();
	$q=new postgres_sql();

    $rsearch=null;$xrule=null;$isrule=false;$xrule1=null;
    if(preg_match("#(rule|r.*?gle)(\s+|=)(.+?)(\s+|$)#",$_GET["search"],$re)){
        $rsearch=$re[3].$re[4];
        $_GET["search"]=str_replace($re[1].$re[2].$re[3].$re[4],"",$_GET["search"]);
    }
    if(preg_match("#is rule#",$_GET["search"],$re)){
        $isrule=true;
        $_GET["search"]=str_ireplace("is rule","",$_GET["search"]);
    }


    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));

    $MAX=intval($search["MAX"]);
    $pattern=$search["Q"];
    if($MAX==0){$MAX='250';}

    if($rsearch<>null) {
        foreach ($FWRULES as $index => $name) {
            if (preg_match("#$rsearch#i", $name)) {
                $xrule = " AND ruleid=$index";
                $xrule1= "WHERE ruleid=$index";
            }else{
                VERBOSE("rsearch=[$rsearch] NO MATCH [$name]",__LINE__);
            }
        }
    }
    if($xrule1==null){
        if($isrule){
            $xrule1="WHERE ruleid > 0";
        }
    }

    $sql="SELECT * FROM dnsfw_access $xrule1 ORDER BY zdate DESC LIMIT $MAX";



    if($pattern<>null){
        VERBOSE("rsearch=[$rsearch]",__LINE__);

        if($xrule==null){
            if($isrule){
                $xrule= " AND ruleid>0";
            }
        }

        $sql="SELECT * FROM dnsfw_access WHERE (domain LIKE '$pattern' OR qtype LIKE '$pattern' OR TEXT(ipaddr) LIKE '$pattern') $xrule ORDER BY zdate DESC LIMIT $MAX";
    }

	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }

	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{src}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{query}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rule}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$TRCLASS=null;
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$src_ip=$ligne["ipaddr"];
		$zDate=$tpl->time_to_date($ligne["zdate"],true);
		$domain=$ligne["domain"];
		$cached=$ligne["cached"];
		$rule=$FWRULES[$ligne["ruleid"]];
        $qtype=$ligne["qtype"];
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>$zDate</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>$src_ip</a></td>";
		$html[]="<td class=\"$text_class\" nowrap>$domain ($qtype)</td>";
		$html[]="<td class='$text_class' nowrap>$rule</td>";
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
	$html[]="</table><div><i>{$sql}</i></div>";
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( {\"filtering\": {\"enabled\": false },\"sorting\": {\"enabled\": true } } ); });

</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
