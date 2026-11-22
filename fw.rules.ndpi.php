<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");
// Regles tenues par Artica Meta : ni ajout ni retrait d'application nDPI.
if(firewall_meta_holds("rules") && (isset($_GET["ndpi-js"]) || isset($_GET["ndpi-choose"]) || isset($_GET["delete-js"]))){firewall_meta_refuse_js();exit;}
if(isset($_GET["build-table"])){build_table();exit;}
if(isset($_GET["ndpi-js"])){ndpi_js();exit;}
if(isset($_GET["ndpi-list"])){ndpi_list();exit;}
if(isset($_GET["ndpi-choose"])){ndpi_choose();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}



build_page();

function ndpi_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["rule-id"]);
    $tpl->js_dialog6("{APP_NDPI}","$page?ndpi-list=yes&rule-id=$ID",450);

}
function ndpi_list(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["rule-id"]);

    if($ruleid==0){
        echo $tpl->FATAL_ERROR_SHOW_128("Rule ID == 0!");return;
    }

    $f=explode("\n",@file_get_contents("/proc/net/xt_ndpi/proto"));
    $t=time();
    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{software}</th>";
    $html[]="<th></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ndpi1-rows'>";

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
    $results=$q->QUERY_SQL("SELECT * FROM firehol_ndpi WHERE ruleid=$ruleid");
    foreach ($results as $index=>$ligne){
        $ndpiname=strtolower($ligne["ndpiname"]);
        $ALREADY[$ndpiname]=true;
    }

    $TRCLASS=null;
    foreach ($f as $line) {
        $md = md5($line);
        if (!preg_match("#([0-9a-z]+)\s+.+?\s+(.+?)\s+#", $line, $re)) {
            continue;
        }
        $service = $re[2];

        if (preg_match("#custom#", $line)) {
            continue;
        }
        if (is_numeric($service)) {
            continue;
        }
        $srv=strtolower($service);
        if(isset($ALREADY[$srv])){continue;}
        $MAIN[$srv]=$service;

    }

    ksort($MAIN);
    foreach ($MAIN as $strlow=>$strnorm) {


        $TRCLASS=null;
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"left\"><strong>$strnorm</strong></td>";
        $html[]="<td width='1%'>". $tpl->button_autnonome("{select}","Loadjs('$page?ndpi-choose=$strlow&rulelid=$ruleid&md=$md')","fas fa-box-check","AsFirewallManager",0,"btn-primary","small")."</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='ndpi1-pager'></span></div></div></div>";

    $html[]="
<script>
</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ndpi1-rows","ndpi1-pager");



}

function ndpi_choose(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $service=(string)$_GET["ndpi-choose"];
    $ruleid=intval($_GET["rulelid"]);
    $md=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["md"] ?? ""));

    if($ruleid==0){
        $tpl->js_mysql_alert("Rule ID == 0!");return;
    }

    header("content-type: application/x-javascript");
    $e=fwapi_error(fwapi_send("POST","rules/$ruleid/ndpi",array("name"=>$service)));
    if($e!=""){$tpl->js_error($e);return;}
    echo "$('#$md').remove();\n";
    echo "LoadAjax('fw-ndpi-table','$page?build-table=yes&ID=$ruleid');\n";
    echo "Loadjs('fw.rules.php?fill=$ruleid');\n";

}

function build_page(){
	patch_firewall_tables();
	$ID=intval($_GET["rule-id"]);
	$tpl=new template_admin();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();
	
	
	$metaRules=firewall_meta_holds("rules");
	$addBtn=$metaRules?firewall_meta_banner("rules",$tpl):"<div class=\"btn-group\" data-toggle=\"buttons\">
		<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?ndpi-js=yes&rule-id=$ID');\"><i class='fa fa-plus'></i> {softwares} </label>
	</div>";
	$html[]=$tpl->_ENGINE_parse_body("
	<div class=row style='margin-top:20px'>
	$addBtn
	<div id='fw-ndpi-table'>");
	
	$html[]="	</div>";
	$html[]="</div>
<script>
	LoadAjax('fw-ndpi-table','$page?build-table=yes&ID=$ID');
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["delete-js"]);
	$md=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["md"] ?? ""));
    $ruleid=intval($_GET["ruleid"]);


    header("content-type: application/x-javascript");
    $e=fwapi_error(fwapi_send("DELETE","rules/$ruleid/ndpi/$ID"));
    if($e!=""){$tpl->js_error($e);return;}
    echo "$('#$md').remove();\n";
	echo "LoadAjax('fw-ndpi-table','$page?build-table=yes&ID=$ruleid');\n";
    echo "Loadjs('fw.rules.php?fill=$ruleid');\n";
	
}

function build_table(){
	$RuleID=intval($_GET["ID"]);
    $t=time();
	$tpl=new template_admin();
	$page=CurrentPageName();




	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
    $results=$q->QUERY_SQL("SELECT * FROM firehol_ndpi WHERE ruleid=$RuleID");
	

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>{software}</th>";
	$html[]="<th></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='ndpi2-rows'>";
	

	$TRCLASS=null;
	foreach($results as $index=>$ligne){
        $md=md5(serialize($ligne));
		$TRCLASS=null;
		$text_class=null;
		$ID=$ligne["ID"];
		$ndpiname=$ligne["ndpiname"];
        $html[]="<tr class='$TRCLASS' id='$md'>";
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md&ruleid=$RuleID')",firewall_meta_sec(firewall_meta_holds("rules"),null));
		$html[]="<td class=\"left\"><i class=\"".ico_cd."\"></i>&nbsp;<strong>$ndpiname</strong></td>";
		$html[]="<td width='1%'>$delete</td>";
		$html[]="</tr>";
	
	}
	
	$html[]="</tbody>";
	$html[]="";
	$html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='ndpi2-pager'></span></div></div></div>";
	
	$html[]="
<script>
</script>";
	
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ndpi2-rows","ndpi2-pager");	
	
}