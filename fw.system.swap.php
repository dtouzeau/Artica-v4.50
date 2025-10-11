<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
//"{macro_remove_disk}","{macro_remove_disk_explain}"

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["stats"])){stats();exit;}
if(isset($_GET["table"])){swap_start();exit;}
if(isset($_GET["swap-table"])){swap_table();exit;}
if(isset($_POST["newswap"])){swap_save();exit;}
if(isset($_GET["swap-delete"])){swap_delete_ask();exit;}

if(isset($_GET["newswap-js"])){newswap_js();exit;}
if(isset($_GET["newswap-popup"])){newswap_popup();exit;}


if(isset($_POST["build"])){Build_save();exit;}
if(isset($_GET["build-after"])){Build_after();exit;}
if(isset($_POST["swap-delete"])){swap_delete_save();exit;}
if(isset($_GET["rescan-js"])){rescan_js();exit;}

page();

function newswap_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog("{add_swap}", "$page?newswap-popup=yes");
}
function swap_delete_ask(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$swapfile=$_GET["swap-delete"];
    $swapfileenc=base64_encode($swapfile);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.swap.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.swap.txt";
    $ARRAY["CMD"]="hd.php?remove-swap=yes";
    $ARRAY["TITLE"]="{remove_swap}";
    $ARRAY["AFTER"]="LoadAjax('swap-part-table','$page?swap-table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $after="Loadjs('fw.progress.php?content=$prgress&mainid=progress-swap-restart')";
    $tpl->js_confirm_delete("{remove_swap} $swapfile","swap-delete",$swapfileenc,$after);
}

function swap_delete_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    $sock->SET_INFO("DELETE_SWAP",$_POST["swap-delete"]);
}

function rescan_js(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $sock=new sockets();
    $sock->getFrameWork("hd.php?rescan-swap=yes");
    echo "LoadAjax('swap-part-table','$page?swap-table=yes');";
}

function directory_monitor_graph_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$path=$_GET["directories-monitor-graph-js"];
	$pathenc=urlencode($path);
	$tpl->js_dialog1("{your_hard_disks} >> {directories_monitor} >> $path", "$page?directories-monitor-graph-popup=$pathenc");	
}
function directory_monitor_path_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$path=$_GET["directories-monitor-path-js"];
	$pathenc=urlencode($path);
	if($path==null){$title="{new_directory}";}else{$title=$path;}
	$tpl->js_dialog2("{your_hard_disks} >> {directories_monitor} >> $title", "$page?directories-monitor-path-popup=$pathenc");
}

function directory_monitor_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$html="<div id='directories-monitor-popup'></div>
	<script>
		LoadAjax('directories-monitor-popup','$page?directories-monitor-table=yes');
	</script>";
	echo $html;	
	
}


function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
    $EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    if($MUNIN_CLIENT_INSTALLED==0){
        $EnableMunin=0;
    }

    if($EnableMunin==0){
        swap_start();
        return true;
    }

	$array["{swap_label}"]="$page?table=yes";
    $array["{statistics}"]="$page?stats=yes";
	echo $tpl->tabs_default($array);
    return true;
	
}
function stats(){
	
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	
	$h[]="swap";

	$f[]="-day.png";
	$f[]="-week.png";
	$f[]="-month.png";
	$f[]="-year.png";
	
	foreach ($h as $them){
		foreach ($f as $suffix){
			$image="$them$suffix";
			if(is_file("$path/$image")){
				echo "<div class='center' style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t' alt=''></div>";
			}
		}
	}
}





function newswap_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.swap.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.swap.txt";
    $ARRAY["CMD"]="hd.php?create-swap=yes";
    $ARRAY["TITLE"]="{creating_swap}";
    $ARRAY["AFTER"]="LoadAjax('swap-part-table','$page?swap-table=yes');BootstrapDialog1.close();";
    $prgress=base64_encode(serialize($ARRAY));
    $after="Loadjs('fw.progress.php?content=$prgress&mainid=creating-new-swap')";

	$form[]=$tpl->field_hidden("newswap", "yes");
	$form[]=$tpl->field_browse_directory("path", "{path}", "/home/swaps");
	$form[]=$tpl->field_numeric("size","{size} (MB)",1024);
	$html="<div id='creating-new-swap'></div>".
        $tpl->form_outside("{add_swap}", @implode("\n", $form),null,"{add}",$after,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}

function swap_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $DATA=base64_encode(serialize($_POST));
    $sock=new sockets();
    $sock->SET_INFO("CREATE_NEW_SWAP",$DATA);

}

function Build_after(){
	header("content-type: application/x-javascript");
	$MAIN=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacroBuildPart"));
	$page=CurrentPageName();
	
	if($MAIN["dev"]==null){echo "alert('No disk defined!');";return;}
	$nofstab=null;
	$dev=urlencode($MAIN["dev"]);
	$label=urlencode($MAIN["label"]);
	$fs_type=urlencode($MAIN["fs_type"]);
	if(isset($MAIN["nofstab"])){$nofstab="&nofstab=yes";}
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.partition.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.partition.txt";
	$ARRAY["CMD"]="cmd.php?fdisk-build-big-partitions=yes&dev=$dev&label=$label&fs_type=$fs_type$nofstab";
	$ARRAY["TITLE"]="{macro_build_bigpart}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-harddisks','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$rescan_hd="Loadjs('fw.progress.php?content=$prgress&mainid=progress-hd-restart')";
	
	echo "BootstrapDialog1.close()\n";
	echo $rescan_hd."\n";
	
	
}

function Build_save(){
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($_POST), "MacroBuildPart");
	
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{swap_label}",ico_hd,"{swap_label_explain}","$page?tabs=yes","system-swap","progress-swap-restart",false,"table-loader-swap");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
	
}
function swap_start(){

    $page=CurrentPageName();
    $html="<div id='swap-part-table'></div><script>LoadAjax('swap-part-table','$page?swap-table=yes');</script>";
    echo $html;
}


function swap_table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
    $topbuttons=array();
    $feature_disabled="";

    $DisableSystemSwap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSystemSwap"));

    $count=0;

    if($users->AsSystemAdministrator) {
        if($DisableSystemSwap==0) {
            $topbuttons[] = array("Loadjs('$page?newswap-js=yes')", ico_plus, "{add_swap}");
        }


        $EmptySwapJS=$tpl->framework_buildjs("/system/clean-swap",
            "swap-clean.progress",
            "swap-clean.progress.txt",
            "progress-swap-restart",
            "LoadAjax('swap-part-table','$page?swap-table=yes')"

        );
        $DisableSwapJS=$tpl->framework_buildjs("/system/disable-swap",
            "swap-clean.progress",
            "swap-clean.progress.txt",
            "progress-swap-restart",
            "LoadAjax('swap-part-table','$page?swap-table=yes')"

        );

        $EnableSwapJS=$tpl->framework_buildjs("/system/enable-swap",
            "swap-clean.progress",
            "swap-clean.progress.txt",
            "progress-swap-restart",
            "LoadAjax('swap-part-table','$page?swap-table=yes')"

        );



        if($DisableSystemSwap==0){
            $topbuttons[] = array($EmptySwapJS, ico_trash, "{empty_swap}");
            $topbuttons[] = array($DisableSwapJS, ico_stop, "{disable}: SWAP");
        }else{
            $feature_disabled="&nbsp;<span class='label label-danger'>{feature_disabled}</span>";
            $topbuttons[] = array($EnableSwapJS, ico_run, "{enable}: SWAP");
        }

    }

	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-hd-disks' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{path}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>%</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{used}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{action}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$TRCLASS=null;
	

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/list-swap"));
    if(!$data->Status){
        echo $tpl->div_error($data->Error);
        return false;
    }
    if(!property_exists($data,"swaps")){
        echo $tpl->div_error("{no_data}");
        return false;
    }
    $SWAP_PARTITIONS=$data->swaps;

	foreach ($SWAP_PARTITIONS as $jsarray){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$TYPE=$jsarray->type;
		$SIZE=$jsarray->size;
		$USED=$jsarray->used;
		//$PRIO=$jsarray->prio;
        $dev=$jsarray->name;

        $devenc=urlencode($dev);
        $action=$tpl->icon_delete("Loadjs('$page?swap-delete=$devenc')","AsSystemAdministrator");

		$SIZE_TEXT=FormatBytes($SIZE);
		$USED_TEXT=FormatBytes($USED);


		$prc=($USED/$SIZE)*100;
		$progressbar=$tpl->progress_barr_static($prc,"{used}");
        if($TYPE<>"file"){$action=$tpl->icon_nothing();}

		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td><strong>$dev</strong></td>";
		$html[]="<td style='width:15%' nowrap>$progressbar</a></td>";
        $html[]="<td style='width:1%' nowrap>$TYPE</a></td>";
        $html[]="<td style='width:1%' nowrap><strong>$SIZE_TEXT</strong></td>";
        $html[]="<td style='width:1%' nowrap><strong>$USED_TEXT</strong></td>";
        $html[]="<td style='width:1%' nowrap><strong>$action</strong></td>";
		$html[]="</tr>";
    }


    $EnableDockerService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerService"));
    if($EnableDockerService==1){
        $topbuttons=array();
    }
    $TINY_ARRAY["TITLE"]="{swap_label}$feature_disabled";
    $TINY_ARRAY["ICO"]=ico_hd;
    $TINY_ARRAY["EXPL"]="{swap_label_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	
	$html[]="</tbody>";
	$html[]="</table>";
	$html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	return true;
	
}



function directory_monitor_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$page=CurrentPageName();
	$q=new mysql();
	$directory=$tpl->_ENGINE_parse_body("{directory}");
	$partition=$tpl->_ENGINE_parse_body("{partition}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$new_directory=$tpl->_ENGINE_parse_body("{new_directory}");
	$used=$tpl->javascript_parse_text("{used}");
	$hard_drive=$tpl->javascript_parse_text("{disk}");
	$free=$tpl->javascript_parse_text("{free}");
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.dirmon.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.dirmon.progress.txt";
	$ARRAY["CMD"]="system.php?folders-monitors-progress=yes";
	$ARRAY["TITLE"]="{directories_monitor}::{rescan}";
	$ARRAY["AFTER"]="LoadAjax('directories-monitor-popup','$page?directories-monitor-table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	
	
	$rescan_hd="Loadjs('fw.progress.php?content=$prgress&mainid=progress-directories-monitor-restart')";
	
	
	$html[]=$tpl->_ENGINE_parse_body("
			<div id='progress-directories-monitor-restart' style='margin-bottom:10px'></div>
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-info\" OnClick=\"javascript:$rescan_hd;\"><i class='fas fa-check'></i> {launch_scan} </label>
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?directories-monitor-path-js=');\"><i class='fas fa-check'></i> {new_directory} </label>
			</div>");
	
	$html[]="<table id='table-directories-monitor' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$directory}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$partition}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$hard_drive</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$used</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$free</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$results=$q->QUERY_SQL("SELECT * FROM philesight ORDER BY FREEMB","artica_backup");
	$TRCLASS=null;
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$color="black";
		$icon="&nbsp;";
		$distance=null;
		$directory=$ligne["directory"];
		$md5=md5($directory);
		$partition=$ligne["partition"];
		$hd=$ligne["hd"];
		$maxtime=$ligne["maxtime"];
		$lastscan=intval($ligne["lastscan"]);
		$USED=$ligne["USED"];
		$FREEMB=intval($ligne["FREEMB"]);
		if($lastscan>0){
			$lastscan=date("Y-m-d H:i:s",$lastscan);
		}
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		$directoryenc=urlencode($directory);
		$jslink="Loadjs('$page?directory-js=$directoryenc');";
	
		
		if(intval($ligne["lastscan"])>0){
			$distance=$tpl->_ENGINE_parse_body(distanceOfTimeInWords($ligne["lastscan"],time(),true));
			
			if(is_file("/usr/share/artica-postfix/img/philesight/$md5.png")){
				$icon=$tpl->icon_pie("Loadjs('$page?directories-monitor-graph-js=$directoryenc')");
			}
			
			$distance="<br><i>$distance</i>";
		}
		
		$md=md5($directory);
		$delete=$tpl->icon_delete("Loadjs('$page?directories-monitor-delete=$directoryenc&md=$md')","AsSystemAdministrator");
		$text_class=null;
		if($partition==null){$partition=$tpl->icon_nothing();}
		if($hd==null){$hd=$tpl->icon_nothing();}
		if(intval($USED)==0){$USED=$tpl->icon_nothing();}else{$USED="{$USED}%";}
		if(intval($FREEMB)==0){$FREEMB=$tpl->icon_nothing();}else{$FREEMB=FormatBytes($FREEMB*1024);}
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td class=\"$text_class\">".$tpl->td_href($lastscan,"{click_to_edit}",$jslink)."$distance</td>";
		$html[]="<td>".$tpl->td_href($directory,"{click_to_edit}",$jslink)."</td>";
		$html[]="<td class=\"$text_class\" width=1% nowrap>{$partition}</td>";
		$html[]="<td class=\"$text_class\" width=1% nowrap>{$hd}</td>";
		$html[]="<td class=\"$text_class\" width=1% nowrap>{$USED}</td>";
		$html[]="<td class=\"$text_class\" width=1% nowrap>{$FREEMB}</td>";
		$html[]="<td class=\"center\" width=1% nowrap>$icon</td>";
		$html[]="<td class=\"center\" width=1% nowrap>$delete</td>";
		

	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-directories-monitor').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function ParseHDline($dev,$array){
	$sock=new sockets();

	$WatchDog=unserialize($sock->GET_INFO("HardDisksWatchDog"));


	$ID_MODEL=null;
	$ID_VENDOR=null;
	$ID_FS_LABEL_TEXT=null;

	if(isset($array["ID_MODEL_ENC"])){
		$ID_MODEL=$array["ID_MODEL_ENC"];
			
	}

	if($ID_MODEL==null){
		if(isset($array["ID_MODEL"])){
			$ID_MODEL=$array["ID_MODEL"];
		}
	}

	if($ID_MODEL==null){
		if(isset($array["ID_MODEL_1"])){
			$ID_MODEL=$array["ID_MODEL_1"];
		}
	}

	if($ID_MODEL==null){
		if(isset($array["ID_MODEL_2"])){
			$ID_MODEL=$array["ID_MODEL_2"];
		}
	}

	if($ID_MODEL<>null){$MAIN["MODEL"]=$ID_MODEL;}



	$SIZE=$array["SIZE"];
	$ID_BUS=$array["ID_BUS"];
	if(isset($array["ID_VENDOR"])){$ID_VENDOR=$array["ID_VENDOR"];}
	
	
	$ID_USB_DRIVER=$array["ID_USB_DRIVER"];
	if(isset($array["ID_SERIAL_SHORT"])){
		$ID_SERIAL_SHORT=$array["ID_SERIAL_SHORT"];
	}
	$part_number=count($array["PARTITIONS"]);
	if(trim($ID_VENDOR)<>null){$MAIN["VENDOR"]=$ID_VENDOR;}
	$link="PartInfos('$dev')";
	$title[]="<div style='font-size:40px;margin-bottom:15px'><a href=\"javascript:blur();\" OnClick=\"$link\" style='text-decoration:underline'>$dev ($SIZE)</a></div>";
	$title[]="<div style='font-size:18px'>$ID_VENDOR$ID_MODEL</div>";
	if(isset($array["ID_REVISION"])){$MAIN["VERSION"]=$array["ID_REVISION"];}

	$title[]="<div style='font-size:18px'>{path}: $dev {partitions_number}: $part_number</div>";
	if($part_number>0){
		$watchdog_text="{disabled}";
		if(isset($WatchDog[$dev])){
			$watchdog_text="{enabled}";
		}
			
		$title[]="<div style='font-size:18px;font-weight:bold'><a href=\"javascript:blur();\"
			OnClick=\"Loadjs('system.internal.disks.watchdog.php?dev=". urlencode($dev)."')\"
			style='text-decoration:underline'>
			{watchdog}: $watchdog_text</a></div>";
	}

	
	return $MAIN;


}