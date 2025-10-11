<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.mem.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["database-status"])){database_status();exit;}
if(isset($_GET["parameters-page"])){parameters_page();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}


if(isset($_GET["parameters-flat"])){parameters_flat();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}

if(isset($_POST["DisableCategoriesDatabasesUpdates"])){Save_settings();exit;}
if(isset($_GET["btns-white"])){echo base64_decode($_GET["btns-white"]);exit;}
page();

function parameters_page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{update_parameters}",ico_download,
        "{webfiltering_databases_explain}","$page?parameters=yes",
        "webfiltering-update-databases","progress-firehol-restart",false,"table-webfiltering-databases-tabs");
    $tpl=new template_admin(null,$html);
    echo $tpl->build_firewall("{webfiltering_databases}");
    return true;
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{webfiltering_databases}: {categories_update}",ico_download,
        "{webfiltering_databases_explain}","$page?tabs=yes",
        "webfiltering-databases","progress-firehol-restart",false,"table-webfiltering-databases-tabs");

    if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall("{webfiltering_databases}");
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $ClusterReplicateOfficalDatabases=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterReplicateOfficalDatabases"));
    if($ClusterReplicateOfficalDatabases==0){$PowerDNSEnableClusterSlave=0;}
	$ID=intval($_GET["rule-tabs"]);
	$array["{status}"]="$page?database-status=yes";
	if($PowerDNSEnableClusterSlave==0) {
        $array["{update_parameters}"] = "$page?parameters=yes";
        $array["{schedule}"] = "fw.proxy.tasks.php?microstart=yes&ForceTaskType=1&tiny-page=yes";
        $array["{update_events}"] = "fw.ufdb.updates.events.php";
    }
    //LoadAjaxSilent('btns-white','fw.ufdb.databases.php?btns-white=');
	echo $tpl->tabs_default($array);

}

function database_status(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();

	$DisableCategoriesDatabasesUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableCategoriesDatabasesUpdates"));
	$ProductName="Artica";
	$ProductNamef=dirname(__FILE__) . "/ressources/templates/{$_COOKIE["artica-template"]}/ProducName.conf";
	if(is_file($ProductNamef)){$ProductName=trim(@file_get_contents($ProductNamef));}
	
	if($DisableCategoriesDatabasesUpdates==1){
		echo $tpl->FATAL_ERROR_SHOW_128("{webfiltering_databases_are_disabled}");
		return;
		
	}
    $jsrestart=$tpl->framework_buildjs("/category/ufdb/update",
        "artica-webfilterdb.progress","artica-webfilterdb.log",
        "progress-firehol-restart","LoadAjax('table-webfiltering-databases-tabs','$page?tabs=yes');");

    $jsDelete=$tpl->framework_buildjs("/categories/ufdb/delete",
        "dansguardian2.databases.delete.progress","dansguardian2.databases.delete.log",
        "progress-firehol-restart","LoadAjax('table-webfiltering-databases-tabs','$page?tabs=yes');");


    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $ClusterReplicateOfficalDatabases=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterReplicateOfficalDatabases"));
    if($ClusterReplicateOfficalDatabases==0){$PowerDNSEnableClusterSlave=0;}



	if($PowerDNSEnableClusterSlave==0) {
        if ($users->AsDansGuardianAdministrator) {
            $topbuttons[] = array($jsDelete,ico_trash,"{delete_databases}");
        }
        $topbuttons[] = array($jsrestart,ico_download,"{update_now}");


    }

	$html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
			$html[]="<thead>";
					$html[]="<tr>";
					$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1%>&nbsp;</th>";
					$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1%>{status}</th>";
					$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
					$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
					$html[]="<th data-sortable=true class='text-capitalize' data-type='text' align='right' style='text-align:right'>{items}</th>";
					$html[]="<th data-sortable=true class='text-capitalize' data-type='text' align='right' style='text-align:right'>{size}</th>";
					$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{version}</th>";
					$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{updated_on}</th>";
					$html[]="</tr>";
					$html[]="</thead>";
					$html[]="<tbody>";
	
	$ARTICA_TOTAL_ROWS=0;
	$TLSE_TOTAL_ROWS=0;
	$TRCLASS=null;

	
	$q=new postgres_sql();

	
	if(!$q->TABLE_EXISTS("personal_categories")){
		$q->create_personal_categories();
		if(!$q->ok){echo "<div class='alert alert-danger' style='margin-top:20px'>$q->mysql_error</div>";return;}
		include_once(dirname(__FILE__)."/ressources/class.categories.inc");

		
	}else{
		if($GLOBALS["VERBOSE"]){VERBOSE("Count of ".$q->COUNT_ROWS("personal_categories"), __LINE__);}
		if(intval($q->COUNT_ROWS("personal_categories"))<100){
			include_once(dirname(__FILE__)."/ressources/class.categories.inc");
			$cat=new categories();$cat->initialize();}
	}
	$sql="SELECT * FROM personal_categories order by categoryname";
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$sql="SELECT *  FROM personal_categories WHERE free_category=0 ORDER BY categoryname";
	}
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<div class='alert alert-danger' style='margin-top:20px'>$q->mysql_error</div>";
		return;
	}
	
	
	$TRCLASS=null;
    $CATMEM=FILL_CATEGORIES_MEM();
    $SKIPPED=$CATMEM["categories_descriptions"]["SKIPPED"];


    $CURRENT=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
    if(!$CURRENT){
        VERBOSE("UfdbMasterCache NOT AN ARRAY!!",__LINE__);
    }
	
	while ($ligne = pg_fetch_assoc($results)) {
        $timeversion=0;
        $UPDATED_TIME_TEXT=$tpl->icon_nothing();
		$timeversion_text=$tpl->icon_nothing();
		$LOCALSIZE_TEXT=$tpl->icon_nothing();
		$category_id=$ligne["category_id"];
		$category_icon=$ligne["category_icon"];
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$official_category=$ligne["official_category"];
		$free_category=$ligne["free_category"];
        if(isset($ligne["timeversion"])) {
            $timeversion = $ligne["timeversion"];
        }
		$categoryname=$ligne["categoryname"];
        $type="&nbsp;";
		if(preg_match("#^reserved#", $categoryname)){continue;}

        if(isset($SKIPPED[$category_id])){
            continue;
        }

        $status="<span class='label label-warning'>{can_be_updated}</span>";
        if(!isset($CURRENT[$category_id])){
            $status="<span class='label'>-&nbsp;-&nbsp;-&nbsp;-&nbsp;-&nbsp;-&nbsp;-&nbsp;-&nbsp;-&nbsp;-&nbsp;-</span>";
            $CURRENT[$category_id]["items"]=0;
            $CURRENT[$category_id]["UPDATED_TIME"]=0;
            $CURRENT[$category_id]["timeversion"]=0;
            $CURRENT[$category_id]["LOCALSIZE"]=0;
        }else{
            $timeversion=intval($CURRENT[$category_id]["timeversion"]);
        }

        $ROWS=FormatNumber($CURRENT[$category_id]["items"]);
        $UPDATED_TIME=intval($CURRENT[$category_id]["UPDATED_TIME"]);

        $LOCALSIZE=intval($CURRENT[$category_id]["LOCALSIZE"]);

		if($timeversion>0){$timeversion_text=$tpl->time_to_date($timeversion,true);}
        if(preg_match("#^lem\.#", $categoryname)){$type="LEMNIA";}


       if($LOCALSIZE>0){
            $LOCALSIZE_TEXT=FormatBytes($LOCALSIZE/1024);
        }


        if($free_category==1 OR $official_category==1) {
            $status="<span class='label label-warning'>{can_be_updated}</span>";
            if ($UPDATED_TIME > 0) {
                $UPDATED_TIME_TEXT = $tpl->time_to_date($UPDATED_TIME, true);
                $status = "<span class='label label-primary'>{updated2}</span>";
            }

        }

		if($free_category==1){
            $type="Toulouse university";
        }

		if($official_category==1){
			$type="$ProductName";
			if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
				$status="<span class=label>{no_license}</span>";
				$UPDATED_TIME_TEXT=$tpl->icon_nothing();
				$timeversion_text=$tpl->icon_nothing();
				$UPDATED_TIME_TEXT=$tpl->icon_nothing();
				$ROWS=$tpl->icon_nothing();
				$LOCALSIZE_TEXT=$tpl->icon_nothing();
			}
		}
		$html[]="<tr class='$TRCLASS' id='row-parent-$category_id'>";
		$html[]="<td>$status</td>";
		$html[]="<td width=1% nowrap><img src='$category_icon'></td>";
		$html[]="<td>$categoryname</td>";
		$html[]="<td width=1% nowrap>$type</td>";
		$html[]="<td align='right'>$ROWS</td>";
		$html[]="<td width=1% nowrap align='right'>$LOCALSIZE_TEXT</td>";
		$html[]="<td>$timeversion_text</td>";
		$html[]="<td>$UPDATED_TIME_TEXT</td>";
		$html[]="</tr>";
		
		
		
	}

	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='8'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $ClusterReplicateOfficalDatabases=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterReplicateOfficalDatabases"));
    if($ClusterReplicateOfficalDatabases==0){$PowerDNSEnableClusterSlave=0;}
    $dberrors="";

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $dberrors="<br><span class=text-danger>{articadb_error_license}</span>";
    }
    if($PowerDNSEnableClusterSlave==1){
        $dberrors="<br><span class=text-danger>{articadb_error_cluster}</span>";
    }

    $TINY_ARRAY["TITLE"]="{webfiltering_databases}: {categories_update}";
    $TINY_ARRAY["ICO"]=ico_download;
    $TINY_ARRAY["EXPL"]="{webfiltering_databases_explain}$dberrors";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["URL"]="webfiltering-databases";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
	$html[]="$jstiny";
    $html[]="</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return;	
	
}


function parameters():bool{
    $page=CurrentPageName();
    echo "<div id='parameters-flat'></div>";
    echo "<script>";
    echo "LoadAjax('parameters-flat','$page?parameters-flat=yes');";
    echo "</script>";
    return true;
}
function parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{parameters}","$page?parameters-popup=yes");
}


function parameters_flat():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $DisableCategoriesDatabasesUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableCategoriesDatabasesUpdates"));
    $CategoriesDatabasesUpdatesAllTimes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesDatabasesUpdatesAllTimes"));
    $CategoriesDatabasesByCron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesDatabasesByCron"));
    if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=1;}
    $CategoriesDatabasesShowIndex=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesDatabasesShowIndex");
    if(!is_numeric($CategoriesDatabasesShowIndex)){$CategoriesDatabasesShowIndex=1;}
    $SquidBlackListDoNotCheckMD5=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBlackListDoNotCheckMD5"));
    $UfdbDownloadTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDownloadTimeout"));
    if($UfdbDownloadTimeout==0){$UfdbDownloadTimeout=7200;}
    $UFDBCurlBandwith=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBCurlBandwith"));
    $UfdbMasterURI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterURI"));
    if($UfdbMasterURI==null){$UfdbMasterURI="http://articatech.net/ufdbv4";}

    $tpl->table_form_field_js("Loadjs('$page?parameters-js=yes')","AsDansGuardianAdministrator");

    if ($DisableCategoriesDatabasesUpdates==1){
        $tpl->table_form_field_bool("{disable_udpates}",1,ico_lock);
        echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
        return true;
    }
    if($CategoriesDatabasesUpdatesAllTimes==1){
        $schedule[]="{free_update_during_the_day}";
    }
    if ($CategoriesDatabasesByCron==1){
        $schedule[]="({update_only_by_schedule})";
    }else{
        $schedule[]="{each} 30 {minutes}";
    }
    $tpl->table_form_field_text("{schedule}",@implode(", ",$schedule),ico_clock);
    $tpl->table_form_field_bool("{free_update_during_the_day}",$CategoriesDatabasesUpdatesAllTimes,ico_clock);
    $tpl->table_form_field_text("{download_link}","<span style='text-transform:none'> $UfdbMasterURI",ico_link);
    $tpl->table_form_field_text("{download_timeout}","$UfdbDownloadTimeout {seconds}",ico_timeout);
    $tpl->table_form_field_bool("{SquidBlackListDoNotCheckMD5}",$SquidBlackListDoNotCheckMD5,ico_check);
    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());

    $jsrestart=$tpl->framework_buildjs("/category/ufdb/update",
        "artica-webfilterdb.progress","artica-webfilterdb.log",
        "progress-firehol-restart","LoadAjax('table-webfiltering-databases-tabs','$page?tabs=yes');");


    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $ClusterReplicateOfficalDatabases=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterReplicateOfficalDatabases"));
    if($ClusterReplicateOfficalDatabases==0){$PowerDNSEnableClusterSlave=0;}



    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array($jsrestart,ico_download,"{update_now}");
    }

    $TINY_ARRAY["TITLE"]="{update_parameters}";
    $TINY_ARRAY["ICO"]=ico_download;
    $TINY_ARRAY["EXPL"]="{webfiltering_databases_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["URL"]="webfiltering-update-databases";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo "<script>";
    echo "$jstiny;";
    echo "LoadAjaxSilent('btns-white','fw.ufdb.databases.php?btns-white=');";
    echo "</script>";
    return true;

}

function parameters_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	
	$DisableCategoriesDatabasesUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableCategoriesDatabasesUpdates"));
	$CategoriesDatabasesUpdatesAllTimes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesDatabasesUpdatesAllTimes"));
	$CategoriesDatabasesByCron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesDatabasesByCron"));
	if(!is_numeric($CategoriesDatabasesByCron)){$CategoriesDatabasesByCron=1;}
	$CategoriesDatabasesShowIndex=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesDatabasesShowIndex");
	if(!is_numeric($CategoriesDatabasesShowIndex)){$CategoriesDatabasesShowIndex=1;}
	$SquidBlackListDoNotCheckMD5=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidBlackListDoNotCheckMD5"));
	$UfdbDownloadTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDownloadTimeout"));
	if($UfdbDownloadTimeout==0){$UfdbDownloadTimeout=7200;}
	$UFDBCurlBandwith=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBCurlBandwith"));
	$UfdbMasterURI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterURI"));
	if($UfdbMasterURI==null){$UfdbMasterURI="http://articatech.net/ufdbv4";}
	
	$form[]=$tpl->field_checkbox("DisableCategoriesDatabasesUpdates","{disable_udpates}",$DisableCategoriesDatabasesUpdates,false,"{disable_udpates_explain}");
	$form[]=$tpl->field_checkbox("CategoriesDatabasesUpdatesAllTimes","{free_update_during_the_day}",$CategoriesDatabasesUpdatesAllTimes,false,"{free_update_during_the_day_explain}");
	$form[]=$tpl->field_checkbox("CategoriesDatabasesByCron","{update_only_by_schedule}",$CategoriesDatabasesByCron,false,"{articadb_update_only_by_schedule}");	
	$form[]=$tpl->field_checkbox("SquidBlackListDoNotCheckMD5","{SquidBlackListDoNotCheckMD5}",$SquidBlackListDoNotCheckMD5,false,"{SquidBlackListDoNotCheckMD5_explain}");
	
	
	
	$form[]=$tpl->field_text("UfdbMasterURI", "{download_link}", $UfdbMasterURI);
	$form[]=$tpl->field_numeric("UfdbDownloadTimeout","{download_timeout} ({seconds})",$UfdbDownloadTimeout,"{download_timeout_explain}");
	$form[]=$tpl->field_numeric("UFDBCurlBandwith","{limit_bandwidth} (kb/s)",$UFDBCurlBandwith);
	
	
	echo $tpl->form_outside("",$form,null,"{apply}","LoadAjax('parameters-flat','$page?parameters-flat=yes');dialogInstance2.close();","AsDansGuardianAdministrator");


    return true;
}

function Save_settings(){
	
	$sock=new sockets();
	foreach ($_POST as $num=>$val){
		$_POST[$num]=url_decode_special_tool($val);
		$sock->SET_INFO($num, $_POST[$num]);
	}
	
}



function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}