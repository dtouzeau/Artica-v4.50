<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
//"{macro_remove_disk}","{macro_remove_disk_explain}"

if(isset($_GET["extend-partition-js"])){extend_partition_js();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["stats"])){stats();exit;}
if(isset($_GET["table"])){hdlist();exit;}
if(isset($_GET["extend-partition-js"])){extend_partition_js();exit;}
if(isset($_GET["extend-partition-popup"])){extend_partition_popup();exit;}
if(isset($_GET["partitions-js"])){partitions_js();exit;}
if(isset($_GET["partitions-popup"])){partitions_popup();exit;}
if(isset($_GET["disconnect-js"])){disconnect_js();exit;}
if(isset($_GET["build-js"])){Build_js();exit;}
if(isset($_GET["build-popup"])){Build_popup();exit;}
if(isset($_POST["build"])){Build_save();exit;}
if(isset($_GET["build-after"])){Build_after();exit;}
if(isset($_POST["nothing"])){exit;}
if(isset($_GET["move-to"])){action_move_to();exit;}

if(isset($_GET["directories-monitor-js"])){directory_monitor_js();exit;}
if(isset($_GET["directories-monitor-popup"])){directory_monitor_popup();exit;}
if(isset($_GET["directories-monitor-table"])){directory_monitor_table();exit;}
if(isset($_GET["directories-monitor-graph-js"])){directory_monitor_graph_js();exit;}
if(isset($_GET["directories-monitor-graph-popup"])){directory_monitor_graph_popup();exit;}
if(isset($_GET["directories-monitor-path-js"])){directory_monitor_path_js();exit;}
if(isset($_GET["directories-monitor-path-popup"])){directory_monitor_path_popup();exit;}
if(isset($_GET["directories-monitor-delete"])){directory_monitor_delete();exit;}
if(isset($_POST["maxtime"])){directory_monitor_path_save();exit;}
page();
function extend_partition_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dev=$_GET["extend-partition-js"];
    $evenc=urlencode($dev);
    $tpl->js_dialog2("$dev >> {extend_part}", "$page?extend-partition-popup=$evenc",650);
}

function action_move_to(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $dev        = $_GET["move-to"];
    $text       = "$dev: {move_to_hd}";
    $devenc     = urlencode($dev);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.partition.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.mv.txt";
    $ARRAY["CMD"]="hd.php?move-disk=$devenc";
    $ARRAY["TITLE"]="{move_to} $dev";
    $ARRAY["AFTER"]="LoadAjax('disk-systems-table-start','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $rescan_hd="Loadjs('fw.progress.php?content=$prgress&mainid=progress-hd-restart')";
    $tpl->js_confirm_execute($text, "nothing", "nothing",$rescan_hd);

}

function extend_partition_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $dev=$_GET["extend-partition-popup"];

    $devEnc=urlencode($dev);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/hd/expand/size/$devEnc"));

    $size=FormatBytes($json->size/1024);

    $after[]="LoadAjax('disk-systems-table-start','$page?table=yes');";
    $after[]="BootstrapDialog1.close();";
    $after[]="document.getElementById('extend-partition-button').innerHTML='';";
    $after[]="dialogInstance1.close();";


    $extend=$tpl->framework_buildjs(
        "/system/hd/expand/perform/$devEnc",
        "system.extend.progress",
        "system.extend.txt",
        "extend-partition-progress",@implode("",$after)
    );

    $html[]="<H2>$dev: {extend_part_to} $size</H2>";
    $html[]="<div id='extend-partition-progress' style='margin: 10px'>&nbsp;</div>";
    $html[]="<p style='margin-top: 10px'>&nbsp;<p>";
    $html[]="<div class='center' style='margin-top:margin:10px' id='extend-partition-button'>".$tpl->button_autnonome("{extend_part}",$extend,"fas fa-comment-plus","AsSystemAdministrator",250)."</div>";
    $html[]="</p>";
    echo $tpl->_ENGINE_parse_body($html);

}

function partitions_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dev=$_GET["partitions-js"];
    $evenc=urlencode($dev);
    $tpl->js_dialog("$dev >> {partitions}", "$page?partitions-popup=$evenc");
}
function directory_monitor_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{your_hard_disks} >> {directories_monitor}", "$page?directories-monitor-popup=yes");
}
function directory_monitor_graph_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $path=$_GET["directories-monitor-graph-js"];
    $pathenc=urlencode($path);
    return $tpl->js_dialog1("{your_hard_disks} >> {directories_monitor} >> $path", "$page?directories-monitor-graph-popup=$pathenc");
}
function directory_monitor_path_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $path=$_GET["directories-monitor-path-js"];
    $pathenc=urlencode($path);
    if($path==null){$title="{new_directory}";}else{$title=$path;}
    return $tpl->js_dialog2("{your_hard_disks} >> {directories_monitor} >> $title", "$page?directories-monitor-path-popup=$pathenc");
}

function directory_monitor_popup(){
    $page=CurrentPageName();
    $html="<div id='directories-monitor-popup'></div>
	<script>
		LoadAjax('directories-monitor-popup','$page?directories-monitor-table=yes');
	</script>";
    echo $html;

}
function directory_monitor_graph_popup(){
    $dir=$_GET["directories-monitor-graph-popup"];
    $md5=md5($dir);
    $time=microtime();
    echo "<center style='width:98%;padding:10px'><img src='img/philesight/$md5.png?time=$time' alt='none'></center>";
}
function directory_monitor_delete(){
    $q=new mysql();
    $md=$_GET["md"];
    $directory=$_GET["directories-monitor-delete"];
    $md5=md5($directory);
    $directory=mysql_escape_string2($directory);
    $q->QUERY_SQL("DELETE FROM philesight WHERE `directory`='$directory'","artica_backup");
    if(!$q->ok){echo $q->mysql_error;return;}

    @unlink("/usr/share/artica-postfix/img/philesight/$md5.png");
    $sock=new sockets();
    $sock->getFrameWork("hd.php?DeleteFile=".urlencode("/home/artica/philesight/$md5.db"));

    echo "$('#$md').remove();";

}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $array["{your_hard_disks}"]="$page?table-start=yes";
    $array["{statistics}"]="$page?stats=yes";
    echo $tpl->tabs_default($array);


}
function table_start(){
    $page=CurrentPageName();
    echo "<div id='disk-systems-table-start'></div>
    <script>LoadAjax('disk-systems-table-start','$page?table=yes');</script>";
}


function stats(){

    $t=time();
    $path="/var/cache/munin/www/localdomain/localhost.localdomain";

    $h[]="latency";
    $h[]="utilization";
    $h[]="iops";
    $h[]="throughput";

    $f[]="-day.png";
    $f[]="-week.png";
    $f[]="-month.png";
    $f[]="-year.png";

    foreach ($h as $them){
        foreach ($f as $suffix){
            $image="diskstats_$them$suffix";
            if(is_file("$path/$image")){
                echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
            }
        }
    }
}

function directory_monitor_path_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new mysql();
    $path=$_GET["directories-monitor-path-popup"];
    if($path==null){
        $title="{new_directory}";
        $ligne["enabled"]=1;
        $ligne["maxtime"]=1440;
        $btname="{add}";
        $jsafter="dialogInstance2.close();LoadAjax('directories-monitor-popup','$page?directories-monitor-table=yes');";
    }
    else{
        $ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM philesight WHERE directory='$path'","artica_backup"));
        $title=$path;
        $btname="{apply}";
        $jsafter="LoadAjax('directories-monitor-popup','$page?directories-monitor-table=yes');";
    }



    $maxtime_array[0]="{never}";
    $maxtime_array[60]="1 {hour}";
    $maxtime_array[120]="2 {hours}";
    $maxtime_array[380]="3 {hours}";
    $maxtime_array[420]="4 {hours}";
    $maxtime_array[480]="8 {hours}";
    $maxtime_array[720]="12 {hours}";
    $maxtime_array[1440]="1 {day}";
    $maxtime_array[2880]="1 {days}";
    $maxtime_array[10080]="1 {week}";

    if($path==null){
        $form[]=$tpl->field_browse_directory("directory", "{directory}", null);
    }else{
        $form[]=$tpl->field_info("directory", "{directory}", $path);
    }

    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    $form[]=$tpl->field_array_hash($maxtime_array, "maxtime", "{scan_period}", $ligne["maxtime"]);
    echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,"AsSystemAdministrator");

}
function directory_monitor_path_save(){

    foreach ($_POST as $num=>$val){
        $_POST[$num]=url_decode_special_tool($val);
    }
    $q=new mysql();
    $directory=url_decode_special_tool($_POST["directory"]);
    $directory=mysql_escape_string2($directory);
    $ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT directory FROM philesight WHERE directory='$directory'","artica_backup"));
    if($ligne["directory"]==null){
        $q->QUERY_SQL("INSERT IGNORE INTO philesight (`directory`,`enabled`,`maxtime`)
				VALUES ('$directory','{$_POST["enabled"]}','{$_POST["maxtime"]}')","artica_backup");
        if(!$q->ok){echo $q->mysql_error;}
        return;
    }

    $q->QUERY_SQL("UPDATE philesight SET maxtime='{$_POST["maxtime"]}',
	enabled='{$_POST["enabled"]}' WHERE directory='$directory'","artica_backup");
    if(!$q->ok){echo $q->mysql_error;}

}


function Build_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dev=$_GET["build-js"];
    $evenc=urlencode($dev);
    $nofstab=null;
    if(isset($_GET["nofstab"])){$nofstab="&nofstab=yes";}
    $tpl->js_dialog("$dev >> {macro_build_bigpart}", "$page?build-popup=$evenc$nofstab");
}
function Build_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dev=$_GET["build-popup"];
    $sock=new sockets();
    $nofstab=null;
    $json=json_decode($sock->REST_API("/system/harddrives/infos"));
    $fsarray=unserialize($json->FsArray);

    if(isset($_GET["nofstab"])){
        $form[]=$tpl->field_hidden("nofstab", "yes");
        $nofstab="<hr><strong>{warn_nofstab}</strong>";
    }

    $form[]=$tpl->field_hidden("build", $dev);
    $form[]=$tpl->field_hidden("dev", $dev);
    $form[]=$tpl->field_array_hash($fsarray, "fs_type", "{filesystem_type}", "ext4");
    $form[]=$tpl->field_text("label", "{label}", "NewDisk");
    $html=$tpl->form_outside("{macro_build_bigpart}: $dev", @implode("\n", $form),"{macro_build_bigpart_text}$nofstab","{apply}","Loadjs('$page?build-after=yes')","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}

function Build_after(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $MAIN=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacroBuildPart"));
    $page=CurrentPageName();
    if($MAIN["dev"]==null){echo "alert('No disk defined!');";return;}
    $dev=base64_encode($MAIN["dev"]);
    $label=$MAIN["label"];
    $label=str_replace("/","_",$label);
    $fs_type=$MAIN["fs_type"];
    $nofstab="no";
    if(isset($MAIN["nofstab"])){$nofstab="yes";}
    $rescan_hd= $tpl->framework_buildjs("/system/harddrives/build/partition/$dev/$label/$fs_type/$nofstab",
    "system.partition.progress",
    "system.partition.txt",
    "progress-hd-restart",
    "LoadAjax('disk-systems-table-start','$page?table=yes');");
    echo "BootstrapDialog1.close()\n";
    echo $rescan_hd."\n";
}

function Build_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    $sock->SaveConfigFile(serialize($_POST), "MacroBuildPart");
}

function disconnect_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dev=$_GET["disconnect-js"];
    $devenc=base64_encode($dev);

    $rescan_hd=$tpl->framework_buildjs("/system/harddrives/unlink/$devenc",
        "system.partition.progress","system.partition.txt","progress-hd-restart","LoadAjax('disk-systems-table-start','$page?table=yes');");

    return $tpl->js_confirm_execute("$dev: {macro_remove_disk_explain}", "nothing", "nothing",$rescan_hd);
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js="$page?table-start=yes";
    $MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
    $EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    if($MUNIN_CLIENT_INSTALLED==1){
        if($EnableMunin==1){
            $js="$page?tabs=yes";
        }
    }





    $html=$tpl->page_header("{your_hard_disks}",ico_hd,"{your_hard_disks_explain}",
        "$js","system-disks","progress-hd-restart",false,"table-loader-harddisks");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}
function hdlist(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();

    $data=$sock->REST_API("/system/harddrives/infos");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding data||".json_last_error()."<br>$sock->mysql_error");
        return false;

    }

    if (!$json->Status) {
        echo $tpl->div_error("ERROR||".$tpl->_ENGINE_parse_body($sock->mysql_error));
        return false;
    }

    $html[]=$tpl->_ENGINE_parse_body("
			<table id='table-hd-disks' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{disk}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{partitions}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{free}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{used}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>MB/s</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{model}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{version}</th>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{action}</th>";


    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $Styl1="style='width:1%' nowrap";


    foreach ($json->Disks->blockdevices as $DiskInfo){
        $perf="";
        $dev=$DiskInfo->path;
        $array["ID_MODEL"]=$DiskInfo->model;
        $array["SIZE"]=$DiskInfo->size;
        $MAIN["VENDOR"]=$DiskInfo->vendor;
        $MAIN["VERSION"]=$DiskInfo->version;
        $MAIN["MODEL"]=$DiskInfo->model;
        $part_number=0;
        $DEVPATH=$DiskInfo->devPath;
        $ID_MODEL=$DiskInfo->model;
        $fstype=$DiskInfo->fstype;

        if(preg_match("#\/dev\/loop[0-9]+$#", $dev)){continue;}
        if(preg_match("#\/dev\/ram[0-9]+$#", $dev)){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text_class=null;
        $DISCONNECT=true;
        $labeldisk=null;
        $IS_MOUNTED=false;
        $nofstab=null;
        $ico=ico_hd;
        $array["ID_MODEL"]=trim(str_replace("\x20"," ",$array["ID_MODEL"]));
        if(preg_match("#([0-9,\.]+)\s+GiB#", $array["SIZE"],$re)){
            $SIZE_KB=intval($re[1])*1099511.627776;
        }
        if(preg_match("#\/virtio3\/#",$DEVPATH)){$ID_MODEL="virtio";}


        if(strtolower($ID_MODEL)=="iscsi_storage"){
            $nofstab="&nofstab=yes";
            $labeldisk="&nbsp;&nbsp;&nbsp;<span class='label label-warning'>iSCSI</span>";
        }
        if(strtolower($ID_MODEL)=="virtual_disk"){
            $labeldisk="&nbsp;&nbsp;&nbsp;<span class='label'>{virtual}</span>";
        }
        if(strtolower($ID_MODEL)=="qemu_harddisk"){
            $labeldisk="&nbsp;&nbsp;&nbsp;<span class='label'>{virtual} (Qemu)</span>";
        }
        if(strtolower($ID_MODEL)=="virtio"){
            $labeldisk="&nbsp;&nbsp;&nbsp;<span class='label'>{virtual} (VirtIO)</span>";
        }
        if(strtolower($ID_MODEL)=="cdrom"){
            $ico=ico_cd;
            $labeldisk="&nbsp;&nbsp;&nbsp;<span class='label'>CD-ROM</span>";
        }
        $extends=array();
        $VENDOR=$MAIN["VENDOR"];
        $VERSION=$MAIN["VERSION"];
        $MODEL=$MAIN["MODEL"];
        if($VERSION==null){$VERSION=$tpl->icon_nothing();}
        $PARTSIZES=0;
        $USED_SIZES=0;
        $FREE_SIZES=0;
        $SIZE_KB=0;
        $ID_FS_TYPES=array();$fsts=array();$filesystemTypes=null;


        if(!property_exists($DiskInfo,"children")) {
            VERBOSE("$dev Disks children set",__LINE__);
        }

        if(property_exists($DiskInfo,"children")) {
            foreach ($DiskInfo->children as $partarray) {
                $part_number++;
                $DEVNAME = $partarray->path;
                if(property_exists($partarray,"tune2FS")) {
                    if(property_exists($partarray->tune2FS,"FilesystemState")) {
                        $FilesystemState = $partarray->tune2FS->FilesystemState;
                        if (strlen($FilesystemState) > 1) {
                            if(!preg_match("#clean#",$FilesystemState)) {
                                $extends[] = "&nbsp;&nbsp;<i class=\"text-danger fas fa-exclamation-square\"></i>&nbsp;$FilesystemState";
                            }
                        }
                    }
                }

                $free = $partarray->partAvai;

                $bigsize = intval($partarray->partSizeK);
                $used = $partarray->partUsedK;

                $MOUNTED = $partarray->mountpoint;
                if ($MOUNTED <> null) {
                    if ($MOUNTED == "/") {
                        $DISCONNECT = false;
                    }
                    $IS_MOUNTED = true;
                }
                $PARTSIZES = $PARTSIZES + $bigsize;
                $USED_SIZES = $USED_SIZES + $used;
                $FREE_SIZES = $FREE_SIZES + $free;
            }
        }
        if($SIZE_KB>$PARTSIZES){
            $USED_SIZES=$USED_SIZES+($SIZE_KB-$PARTSIZES);
        }
        $FREE_SIZES_TEXT=FormatBytes($FREE_SIZES);
        $USED_SIZES_TEXT=FormatBytes($USED_SIZES);
        $prc=0;
        if($PARTSIZES>0) {
            $prc = round(($USED_SIZES / $PARTSIZES) * 100);
        }

       // $prc=$partarray->partUsePerc;
        $devEnc=urlencode($dev);
        $progressbar=$tpl->progress_barr_static($prc,"{used}");

        if($DISCONNECT){
            $DISCONNECT_BUTTON=$tpl->icon_unlink("Loadjs('$page?disconnect-js=$devEnc')","AsSystemAdministrator");
        }else{
            $DISCONNECT_BUTTON=$tpl->icon_nothing();
        }
        if(!$IS_MOUNTED){
            $progressbar=$tpl->icon_nothing();
            $FREE_SIZES_TEXT=$tpl->icon_nothing();
            $USED_SIZES_TEXT=$tpl->icon_nothing();
            $DISCONNECT_BUTTON=$tpl->icon_download("Loadjs('$page?build-js=$devEnc{$nofstab}')","AsSystemAdministrator");
        }
        $devenco=urlencode($dev);
        $basenamedev=basename($dev);
        if (strlen($DiskInfo->speed)>2){
            $perf=$DiskInfo->speed;
        }

        if(count($ID_FS_TYPES)>0){
            foreach ($ID_FS_TYPES as $fst=>$none){
                $fsts[]=$fst;
            }
        }

        $extand=null;
        if(count($fsts)){$filesystemTypes="&nbsp;(".@implode(",&nbsp;",$fsts).")";}

        if(count($extends)>0){
            $extand=@implode("&nbsp;",$extends);
        }
        if($fstype=="btrfs"){
            $filesystemTypes="(btrfs)";
            $DISCONNECT_BUTTON="";
        }

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\" $Styl1><i class='$ico'></i></td>";
        $html[]="<td class=\"$text_class\"><strong>".$tpl->td_href($dev,"{partitions}","Loadjs('$page?partitions-js=$devEnc')")." $filesystemTypes</strong>$labeldisk $extand</td>";
        $html[]="<td width=15% nowrap><div>$progressbar</div></td>";
        $html[]="<td class=\"$text_class\" $Styl1>{$part_number}</a></td>";
        $html[]="<td class=\"$text_class\" $Styl1><strong>{$array["SIZE"]}</strong></td>";
        $html[]="<td class=\"$text_class\" $Styl1><strong>{$FREE_SIZES_TEXT}</strong></td>";
        $html[]="<td class=\"$text_class\" $Styl1><strong>{$USED_SIZES_TEXT}</strong></td>";
        $html[]="<td class=\"$text_class\" $Styl1><strong>$perf</strong></td>";
        $html[]="<td class=\"$text_class\">{$VENDOR} {$MODEL}</a></td>";
        $html[]="<td class=\"$text_class\" $Styl1>$VERSION</a></td>";
        $html[]="<td class=\"center\" $Styl1>{$DISCONNECT_BUTTON}</a></td>";
        $html[]="</tr>";



    }

    $topbuttons[] = array("LoadAjax('disk-systems-table-start','$page?table=yes');", ico_refresh, "{refresh}");

    $TINY_ARRAY["TITLE"]="{your_hard_disks}";
    $TINY_ARRAY["ICO"]=ico_hd;
    $TINY_ARRAY["EXPL"]="{your_hard_disks_explain}";
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


}

function FilesystemState_ico($partarray):string{

    $FilesystemState_ico = "<span class='label label-default'>{none}</span>";

    if (!property_exists($partarray, "tune2FS")) {
        return $FilesystemState_ico;
    }

    $FilesystemState = $partarray->tune2FS->FilesystemState;
    if(is_null($FilesystemState)){
        return $FilesystemState_ico;
    }
    if (strlen($FilesystemState) < 2) {
        return $FilesystemState_ico;
    }

   if ($FilesystemState <> "clean") {
    return  "<span class='label label-warning'>$FilesystemState</span>";

   }
    return "<span class='label label-primary'>$FilesystemState</span>";

}

function partitions_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dev=$_GET["partitions-popup"];
    $sock=new sockets();
    $data=$sock->REST_API("/system/harddrives/infos");
    $MasTerJson=json_encode(array());
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding data||".json_last_error()."<br>$sock->mysql_error");
        return false;

    }

    if (!$json->Status) {
        echo $tpl->div_error("ERROR||".$tpl->_ENGINE_parse_body($sock->mysql_error));
        return false;
    }

    foreach ($json->Disks->blockdevices as $DiskInfo) {
        if (!property_exists($DiskInfo, "children")) {
            VERBOSE("$dev Disks children set", __LINE__);
            continue;
        }
        if(property_exists($DiskInfo,"children")) {
            $DEVNAME=$DiskInfo->path;
            if ($DEVNAME==$dev) {
                $MasTerJson = $DiskInfo->children;
                break;
            }
        }
    }


    $html[]=$tpl->_ENGINE_parse_body("
			<table id='table-hd-disks' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{partitions}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{used}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{free}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{speed}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{mounted}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{action}</th>";
    $Styl1="style='width:1%' nowrap";

    $TRCLASS=null;
    foreach ($MasTerJson as $partarray) {
        $text_class     = null;
        $extend         = null;
        $MOUNTED        = $partarray->mountpoint;
        $num=$partarray->path;
        $label=$partarray->label;
        $type2=$partarray->fstype;
        $MOUNTED_ENCODE = urlencode($MOUNTED);
        $ASSSYS         = false;
        $used           = null;
        $ACTIONS        = null;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $POURC=$partarray->partUsePerc;
        $used=$partarray->partUsedK;
        $FREE=$partarray->partAvai;
        $SIZE=$partarray->size;
        $NOExpanDable=false;

        if($MOUNTED=="/"){$ASSSYS=true;}
        if($MOUNTED==null){
            $ASSSYS=true;
            $MOUNTED=$tpl->icon_nothing();
            $NOExpanDable=true;
        }
        $FilesystemState_ico=FilesystemState_ico($partarray);



        if($type2=="swap"){
            $NOExpanDable=true;
        }

        $prbarr=$tpl->progress_barr_static($POURC,"{used}");
        $expandableError=$partarray->expandableError;
        $expandable=$partarray->expandable;
        $expandableSize=$partarray->expandableSize;

        if(!$NOExpanDable) {
            if (strlen($expandableError) > 3) {
                $expandable=false;
            }
        }

        if($expandable){
            $size=$expandableSize;
            $size=FormatBytes($size/1024);
            $extend="&nbsp;<span class=\"label label-warning\">".$tpl->td_href("{extend_part}</span>",
                    "{extend_part_to} :$size",
                    "Loadjs('$page?extend-partition-js=".urlencode($num)."');");
        }



        if(!$ASSSYS){
            if($FREE>23905886) {
                $ACTIONS = $tpl->td_href("{move_to}", "{move_to_hd}", "Loadjs('$page?move-to=$MOUNTED_ENCODE')");
            }
        }

        if($used==null){$USED=$tpl->icon_nothing();}else{$USED=FormatBytes($used);}
        if($FREE==0){$FREE=$tpl->icon_nothing();}else{$FREE=FormatBytes($FREE);}


        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\"><strong>". basename($num)."&nbsp;$label</strong>$extend</td>";
        $html[]="<td width=15% nowrap>$prbarr</td>";
        $html[]="<td class=\"$text_class\" $Styl1>$SIZE</a></td>";
        $html[]="<td class=\"$text_class\" $Styl1>{$USED}</a></td>";
        $html[]="<td class=\"$text_class\" $Styl1>{$FREE}</a></td>";
        $html[]="<td class=\"$text_class\" $Styl1>$FilesystemState_ico</td>";
        $html[]="<td class=\"$text_class\" $Styl1>$partarray->speed</a></td>";
        $html[]="<td class=\"$text_class\">{$MOUNTED}</a></td>";
        $html[]="<td class=\"$text_class\" $Styl1>{$type2}</a></td>";
        $html[]="<td class=\"$text_class\">{$ACTIONS}</a></td>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	
	</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function directory_monitor_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new mysql();
    $directory=$tpl->_ENGINE_parse_body("{directory}");
    $partition=$tpl->_ENGINE_parse_body("{partition}");
    $date=$tpl->_ENGINE_parse_body("{date}");
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


    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$date</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$directory</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$partition</th>";
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
        $html[]="<td class=\"$text_class\" $Styl1>{$partition}</td>";
        $html[]="<td class=\"$text_class\" $Styl1>{$hd}</td>";
        $html[]="<td class=\"$text_class\" $Styl1>{$USED}</td>";
        $html[]="<td class=\"$text_class\" $Styl1>{$FREEMB}</td>";
        $html[]="<td class=\"center\" $Styl1>$icon</td>";
        $html[]="<td class=\"center\" $Styl1>$delete</td>";


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
	$(document).ready(function() { $('#table-directories-monitor').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function ParseHDline_clean($data): string{
    $data=str_replace('\x20',"",$data);
    return trim($data);
}

