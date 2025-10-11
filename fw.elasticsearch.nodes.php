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
	<div id='table-loader-elasticsearch-NODES' style='margin-top:20px'></div>
	<script>
	LoadAjax('table-loader-elasticsearch-NODES','$page?table=yes');
	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function refresh_js(){
    $page=CurrentPageName();
    $sock=new sockets();
    $sock->getFrameWork("elasticsearch.php?nodes=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-elasticsearch-NODES','$page?table=yes');";
}


function delete_database_perform(){
	$el=new elasticsearch();
	$el->remove_database($_POST["DELETEDB"]);
	
}

function delete_database(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$database=$_GET["delete-database"];
	$tpl->js_confirm_empty("$database", "DELETEDB", $database,"LoadAjax('table-loader-elasticsearch-NODES','$page?table=yes');");
	
	
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
    $html[]="			<th>{hostname}</th>";
    $html[]="			<th>{load}</th>";
    $html[]="			<th>{cpu}</th>";
	$html[]="			<th>{memory}</th>";
	$html[]="			<th>{disk}</th>";
	$html[]="       </tr>";
	$html[]="   </thead>";
	$html[]="<tbody>";

	$ELASTICSEARCH_NODESSTATS=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_NODESSTATS"));
	//print_r($ELASTICSEARCH_NODESSTATS->nodes);

    $healthA["yellow"]="label-warning";
    $healthA["green"]="label-primary";
    $healthA["red"]="label-danger";
	
foreach ($ELASTICSEARCH_NODESSTATS->nodes as $uuid=>$nodes_class) {
    $hostname=$nodes_class->name;
    $icon="fas fa-server";
    $load_field="5m";
    foreach ($nodes_class->roles as $role){
        $zrole[$role]=true;
    }

    if(isset($role["master"])){
        $icon="fas fa-brain";
        $hostname=$hostname. "[MASTER]";
    }

    $load=$nodes_class->os->cpu->load_average->$load_field;
    $cpu=$nodes_class->os->cpu->percent;


    $transport_address=$nodes_class->transport_address;
    $total_in_bytes=$nodes_class->os->mem->total_in_bytes;
    $total_in_text=FormatBytes($total_in_bytes/1024);
    $used_percent=$nodes_class->os->mem->used_percent;

    $fs_total_in_bytes=$nodes_class->fs->total->total_in_bytes;
    $fs_total_in_text=FormatBytes($fs_total_in_bytes/1024);
    $fs_free_in_bytes=$nodes_class->fs->total->free_in_bytes;
    $fs_used_in_bytes=$fs_total_in_bytes-$fs_free_in_bytes;
    $fs_used_in_perc=round(($fs_used_in_bytes/$fs_total_in_bytes)*100,2);
    $available_in_bytes=$nodes_class->fs->total->available_in_bytes;
    $used_in_bytes=humanFileSize($nodes_class->indices->store->total_data_set_size_in_bytes);
    $heap_mem=humanFileSize($nodes_class->jvm->mem->heap_used_in_bytes);

    $html[] = "<tr>";
    $html[] = "<td><i class='$icon'></i>&nbsp;&nbsp;<strong>$hostname</strong>/$transport_address <small>($uuid)</small></td>";
    $html[] = "<td with='1%'>$load</td>";
    $html[] = "<td with='1%'>{$cpu}%</td>";
    $html[] = "<td with='1%'>{$heap_mem}/$total_in_text</td>";
    $html[] = "<td with='1%'>{$used_in_bytes}/$fs_total_in_text</td>";
    $html[] = "</tr>";

}
	
	$html[]="</body>";
	$html[]="</table>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}
function humanFileSize($size,$unit="") {
    if( (!$unit && $size >= 1<<30) || $unit == "GB")
        return number_format($size/(1<<30),2)."GB";
    if( (!$unit && $size >= 1<<20) || $unit == "MB")
        return number_format($size/(1<<20),2)."MB";
    if( (!$unit && $size >= 1<<10) || $unit == "KB")
        return number_format($size/(1<<10),2)."KB";
    return number_format($size)." bytes";
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