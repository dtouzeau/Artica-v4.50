<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["SambaInterfaces"])){save_config();exit;}
if(isset($_POST["SambaWorkgroup"])){save_config();exit;}
if(isset($_GET["flat-config"])){flat_config();exit;}
if(isset($_GET["link-js"])){link_js();exit;}
if(isset($_GET["link-popup"])){link_popup();exit;}
if(isset($_GET["link-btrfs"])){link_btrfs();exit;}
if(isset($_POST["link-btrfs"])){link_btrfs_confirm();exit;}
if(isset($_GET["addvolume-js"])){add_volume_js();exit;}
if(isset($_GET["addvolume-popup"])){add_volume_popup();exit;}
if(isset($_POST["volume"])){add_volume_save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
    $html=$tpl->page_header("{storage}",ico_hd,"{SAMBA_BTRFS_TEXT}",
    "$page?tabs=yes","btrfs","progress-btrfs-restart",false,"table-loader-btrfs-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{storage} {$version}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function link_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{storage}:{add_new_disk}","$page?link-popup=yes");
}
function add_volume_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{storage}:{ADD_VG}","$page?addvolume-popup=yes");
}
function link_btrfs():bool{
    $dev=$_GET["link-btrfs"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsafter=$tpl->framework_buildjs("/btrfs/link/$dev","btrfs.progress","btrfs.log",
        "progress-btrfs-restart","LoadAjax('flat-config','$page?flat-config=yes');");
   return $tpl->js_confirm_execute("$dev: {this_format_data_lost}","link-btrfs",$dev,$jsafter);
}
function link_btrfs_confirm():bool{
    $dev=$_POST["link-btrfs"];
    return admin_tracks("Format and link new HD $dev into btrfs pool");
}
function add_volume_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $form[]=$tpl->field_text("volume","{name}","New volume",true);
    $form[]=$tpl->field_numeric("size","{maxsize} (GB)","100",true);
    echo $tpl->form_outside("",$form,"{ADD_VG_TEXT}","{add}",
     "dialogInstance2.close();LoadAjax('flat-config','$page?flat-config=yes');");
}
function add_volume_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $volume=base64_encode($_POST["volume"]);
    $size=intval($_POST["size"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/btrfs/volume/add/$volume/$size"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Create a new BTRFS volume {$_POST["volume"]} of {$size}G");
}

function service_status(){
    $tpl=new template_admin();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/samba/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($data->Info);

    $jsrestart=$tpl->framework_buildjs("/samba/restart",
        "samba.restart.progress","samba.restart.log","progress-samba-restart");

    $f[]=$tpl->SERVICE_STATUS($ini, "APP_SAMBA",$jsrestart);
    echo $tpl->_ENGINE_parse_body($f);
}
function status(){
	$sock=new sockets();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$NMBD_STATUS=null;


	
	$html[]="<table style='width:100%;margin-top:20px'>
	<tr>
		<td style='width:350px;vertical-align:top'><div id='main-btrfs-status'></div></td>
		<td style='vertical-align:top'><div id='flat-config'></div>";
	$html[]="</td></tr></table>";
    $html[]="<script>";
    $html[]="LoadAjax('flat-config','$page?flat-config=yes');";
    $html[]=$tpl->RefreshInterval_js("main-samba-status",$page,"service-status=yes");
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function link_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/harddrives/free"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    if(!property_exists($json,"Disks")){
        echo $tpl->div_error("System error: Disks are not defined");
        return false;
    }
    $html[]=$tpl->div_explain("{add_new_disk}||{vgextend_explain}");
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"20\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false colspan='2'>{disks}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=false>{select}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;



    foreach ($json->Disks as $index=>$class){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $bt_color="btn-primary";

        $key=$class->name;
        $size=FormatBytes($class->size/1024,true);


        $idrow=md5($key);

        $button_select=$tpl->button_autnonome("&nbsp;{select}",
            "select{$idrow}()",
            "fas fa-hand-pointer","",0,$bt_color,"small");
        $func[]="";
        $func[]="function select{$idrow}(){";
        $func[]="\tLoadjs('$page?link-btrfs=$key');";
        $func[]="\tdialogInstance2.close();";
        $func[]="}";
        $func[]="";
        $html[]="<tr class='$TRCLASS' id='$idrow'>";
        $html[]="<td style='width:1%' nowrap><i class=\"".ico_hd."\"></i></td>";
        $html[]="<td style='width:1%' nowrap><strong style='font-size:14px'>$key</strong></td>";
        $html[]="<td><span style='font-size:14px'>$size</span></td>";
        $html[]="<td width=1% nowrap >$button_select</td>";
    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]=@implode("\n",$func);
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function flat_config(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/harddrives/free"));
    $topbuttons=array();
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return;
    }
    if(!property_exists($json,"Disks")){
        echo $tpl->div_error("System error: Disks are not defined");
        return;
    }
    if(!is_null($json->Disks)) {
        $AvailableDisk = count($json->Disks);
        if ($AvailableDisk > 0) {
            $topbuttons[] = array("Loadjs('$page?link-js=yes')", ico_plus, "{add_new_disk}");
        }
    }

   $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/btrfs/status"));
   $Class=$json->Info;
   $DiskCount=0;
   $tpl->table_form_section("{status}");
   $tpl->table_form_field_text("{size}",FormatBytes($Class->deviceSize/1024,true),ico_weight);
   $tpl->table_form_field_text("{free}",FormatBytes($Class->free/1024,true),ico_weight);
   $tpl->table_form_section("{internal_hard_drives}");

   foreach ($Class->devices as $index=>$dev){
       $DiskCount++;
       $tpl->table_form_field_text($dev->diskname,FormatBytes($dev->total/1024,true),ico_hd);
   }

   $tpl->table_form_section("{volumes}");
    foreach ($Class->volumes as $index=>$vol){
        $limit="{unlimited}";
        if($vol->limit>0){
            $limit="{max_size}:".FormatBytes($vol->limit/1024,true);
        }
        $tpl->table_form_field_text($vol->name,"$limit",ico_directory);

    }



    $html[]=$tpl->table_form_compile();

   if($DiskCount>0){
       $topbuttons[] = array("Loadjs('$page?addvolume-js=yes')", ico_directory, "{ADD_VG}");
   }

    $TINY_ARRAY["TITLE"]="{storage}";
    $TINY_ARRAY["ICO"]=ico_hd;
    $TINY_ARRAY["EXPL"]="{SAMBA_BTRFS_TEXT}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}



function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $array["{status}"]="$page?status=yes";
    echo $tpl->tabs_default($array);
}