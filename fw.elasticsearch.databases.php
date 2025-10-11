<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["delete-database"])){delete_database();exit;}
if(isset($_POST["ElasticsearchMaxMemory"])){save();exit;}
if(isset($_POST["DELETEDB"])){delete_database_perform();exit;}
if(isset($_GET["refresh-js"])){refresh_js();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div id='table-loader-elasticsearch-DB' style='margin-top:20px'></div>
	<script>
	LoadAjax('table-loader-elasticsearch-DB','$page?table=yes');
	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function refresh_js(){
    $page=CurrentPageName();
    $sock=new sockets();
    $sock->getFrameWork("elasticsearch.php?indices=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-elasticsearch-DB','$page?table=yes');";
}


function delete_database_perform(){
	$el=new elasticsearch();
	$el->remove_database($_POST["DELETEDB"]);
	
}

function delete_database(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$database=$_GET["delete-database"];
	$tpl->js_confirm_empty("$database", "DELETEDB", $database,"LoadAjax('table-loader-elasticsearch-DB','$page?table=yes');");
	
	
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$IPClass=new IP();

    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?refresh-js=yes');\"><i class='fal fa-sync-alt'></i> {refresh} </label>
			</div>");


	$html[]="<table class=\"table table-hover\">";
	$html[]="	<thead>";
	$html[]="   	<tr>";
    $html[]="			<th>{status}</th>";
	$html[]="			<th>{database}</th>";
	$html[]="			<th>{size}</th>";
	$html[]="			<th>{items}</th>";
    $html[]="			<th>{memory}</th>";
	$html[]="           <th>{delete}</th>";
	$html[]="       </tr>";
	$html[]="   </thead>";
	$html[]="<tbody>";

	$ELASTICSEARCH_INDICES=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_INDICES"));

    $healthA["yellow"]="label-warning";
    $healthA["green"]="label-primary";
    $healthA["red"]="label-danger";
	
foreach ($ELASTICSEARCH_INDICES as $indices_class) {

    $docs_index="docs.count";
    $store_index="store.size";
    $health=$indices_class->health;
    $status=$indices_class->status;
    $index=$indices_class->index;
    $uuid=$indices_class->uuid;
    $pri=$indices_class->pri;
    $rep=$indices_class->rep;
    $docs=FormatNumber($indices_class->$docs_index);
    $size=$indices_class->$store_index;
    $memory=$indices_class->tm;
    $index_encoded=urlencode($index);


    $html[] = "<tr>";
    $html[] = "<td with='1%'><span class='label {$healthA[$health]}'>$status</span></td>";
    $html[] = "<td><strong>$index</strong> <small>($uuid)</small></td>";
    $html[] = "<td with='1%'>$size</td>";
    $html[] = "<td with='1%'>$docs</td>";
    $html[] = "<td with='1%'>$memory</td>";
    $html[] = "<td style='width:1%' class='center'>" . $tpl->icon_delete("Loadjs('$page?delete-database=$index_encoded');", "AsWebStatisticsAdministrator") . "</td>";
    $html[] = "</tr>";

}
	
	$html[]="</body>";
	$html[]="</table>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function save(){
	$sock=new sockets();
	$tpl=new template_admin();

	
	
	foreach ($_POST as $num=>$val){
		$_POST[$num]=url_decode_special_tool($val);
		$sock->SET_INFO($num, $_POST[$num]);
	}
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}