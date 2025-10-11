<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.firecracker.inc');
$GLOBALS["IMAGES"]["artica-web.ext4"]="Web Service";
if(isset($_GET["table"])){table_start();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["delete-image"])){delete_image();exit;}
if(isset($_POST["delete-image"])){delete_image_perform();exit;}
if(isset($_GET["registries-js"])){registries_js();exit;}
if(isset($_GET["registries-popup"])){registries_popup();exit;}
if(isset($_GET["registries-search"])){registries_search();exit;}
if(isset($_GET["table-search"])){table();exit;}
if(isset($_GET["convert-js"])){convert_js();exit;}
if(isset($_GET["convert-popup"])){convert_popup();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $APP_FIRECRACKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_VERSION");

    $html=$tpl->page_header("{APP_FIRECRACKER} $APP_FIRECRACKER_VERSION &raquo;&raquo; {images}",
    ico_cd,
    "{APP_FIRECRACKER_IMAGES}",
    "$page?table=yes","firecracker-images","progress-firecracker-images",false,"table-firecracker-images");



    if(isset($_GET["main-page"])){
    $tpl=new template_admin("Artica: Docker containers",$html);
    echo $tpl->build_firewall();
    return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
    }
function delete_image(){
    $fname=$_GET["delete-image"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=md5($fname);
    $tpl->js_confirm_delete("{image}:".$GLOBALS["IMAGES"][$fname],
        "delete-image",$fname,"LoadAjax('table-firecracker-images','$page?table=yes');");
}
function registries_js():bool{
    $mainfunc=$_GET["mainfunc"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{ConvertFromRegistries}","$page?registries-popup=yes&mainfunc=$mainfunc");
}
function convert_js():bool{
    $mainfunc=$_GET["mainfunc"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=$_GET["convert-js"];
    $data=urlencode($data);
    return $tpl->js_dialog3("{convert}","$page?convert-popup=$data&mainfunc=$mainfunc");
}
function convert_popup():bool{
    $mainfunc=$_GET["mainfunc"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=md5($_GET["convert-popup"]);
    $data=json_decode(base64_decode($_GET["convert-popup"]));

    $after="dialogInstance3.close();$mainfunc()";

    $js=$tpl->framework_buildjs("firecrack:/docker/registry/convert/".urlencode($_GET["convert-popup"]),
        "RepoToFC.progress","RepoToFC.progress.log","progress-$id",$after);

    $explain=$tpl->_ENGINE_parse_body("{convert_image_explain}");
    $explain=str_replace("%s","<strong>$data->name</strong>",$explain);

    $html[]="<div id='progress-$id'></div>";
    $tpl->table_form_section("",$explain);
    $tpl->table_form_field_text("{registry}",$data->registry,ico_database);
    $tpl->table_form_field_text("{name}",$data->name."&nbsp;-&nbsp;".$data->full_name,ico_computer);
    $tpl->table_form_button("{convert}",$js,ico_arrow_right);
    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function registries_popup():bool{
    $mainfunc=$_GET["mainfunc"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page,null,null,null,"&registries-search=yes&mainfunc=$mainfunc");
    return true;
}
function registries_search():bool{

    $mainfunc=$_GET["mainfunc"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $search=$_GET["search"];
    $function=$_GET["function"];
    $mainfunc=$_GET["mainfunc"];
    if($search==""){$search="*";}
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="*";}
    $rp=intval($MAIN["MAX"]);
    $sock=new sockets();
    $md5=md5($search.$rp);
    $cacheFile="/usr/share/artica-postfix/ressources/logs/repo.$md5";
    if(!is_file($cacheFile)){
        echo "<div id='$md5'></div>";
        $js=$tpl->framework_buildjs("firecrack:/docker/images/search/$search/$rp/$md5",
            "$md5.progress","$md5.log",$md5,"$function()");
        echo "<script type='text/javascript'>$js</script>";
        return true;
    }


    $json=json_decode(file_get_contents($cacheFile));

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-hover\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{registry}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{score}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{convert}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";
    $tdStyle1="style='width:1%;vertical-align:top' nowrap";
    $tdStyle1R="style='width:1%;text-align:right;;vertical-align:top' nowrap";
    foreach($json as $class){
        $registry=$class->registry;
        $name=$class->name;
        $full_name=$class->full_name;
        $description=$class->description;
        $stars=$class->stars;
        $descriptionT=descriptionClean(base64_decode($description));
        $class->description="";
        $encoded=base64_encode(json_encode($class));
        $convert_js="Loadjs('$page?convert-js=".urlencode($encoded)."&mainfunc=$mainfunc')";

        $button="<button OnClick=\"$convert_js\" class='btn btn-primary btn-xs' type='button'>{convert}</button>";

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $id=md5($encoded);
        $html[]="<tr class='$TRCLASS' id='$id'>";
        $html[]="<td $tdStyle1><i class='".ico_cd."'></i></td>";
        $html[]="<td $tdStyle1>$registry</td>";
        $html[]="<td style='width:99%' nowrap><strong>$name</strong><div><i>$full_name</i></div>$descriptionT</td>";
        $html[]="<td $tdStyle1R>$stars</td>";
        $html[]="<td $tdStyle1R>$button</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function descriptionClean($description):string{

    $descriptionN=array();
    $descriptionR=explode("\n",html_entity_decode($description));
    foreach ($descriptionR as $index=>$value){
        $value=trim($value);
        if($value==""){
            continue;
        }
        $value=trim(str_ireplace("<br>","",$value));
        if($value==""){
            continue;
        }
        $descriptionN[]=$value;
    }
    $descriptionText=@implode("\n",$descriptionN);
    $descriptionS=strlen($descriptionText);
    if($descriptionS>90){
        $descriptionText=substr($descriptionText,0,87)."...";
    }
    if($descriptionS<2){
       return "";
    }
    return $descriptionText;
}

function registries_search_old():bool{
    $mainfunc=$_GET["mainfunc"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $search=$_GET["search"];
    if($search==""){$search="*";}
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="*";}
    $search=urlencode($search);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_FIRECR("/docker/images/search/$search/$rp"));
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return true;
    }

    foreach ($json->Images as $Image){
        var_dump($Image);
    }
    return true;
}

function delete_image_perform():bool{
    $fname=$_POST["delete-image"];
    $sock=new sockets();
    $data=json_decode($sock->REST_API("/firecracker/image/delete/$fname"));
    if(!$data->Status){
        echo $data->Error;
        return false;
    }
    return admin_tracks("Deleting MicroVM Image $fname");
}
function install(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fname=$_GET["install"];
    echo $tpl->framework_buildjs("/firecracker/image/download/$fname",
    "firecracker.images.download","firecracker.images.log",
        "progress-firecracker-images","LoadAjax('table-firecracker-images','$page?table=yes');");
}

function table_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,null,null,null,"&table-search=yes");
    return true;
}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $json=json_decode($sock->REST_API_FIRECR("/firecracker/images"));
    $function=$_GET["function"];

    $tdStyle1="style='width:1%' nowrap";
    $tdStyle1R="style='width:1%;text-align:right' nowrap";
    $html[]="<table id='table-firecracker-images' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th colspan='2'>{image}</th>";
    $html[]="<th nowrap>{size}</th>";
    $html[]="<th nowrap>{OS}</th>";
    $html[]="<th>{installed}</th>";
    $html[]="<th>{release}</th>";
    $html[]="<th style='width:1%' nowrap>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach($json->Images as $fname=>$ImageInfo){

       // var_dump($ImageInfo);

        $time=$tpl->time_to_date($ImageInfo->FileTime);
        $Version=$tpl->time_to_date($ImageInfo->GenTime);
        $delete=$tpl->icon_delete("");
//https://artica-iso.b-cdn.net/artica-web.ext4.tar.gz


    $installed="<span class='label label-primary'>{since} $time</span>";
    $delete=$tpl->icon_delete("Loadjs('$page?delete-image=artica-web.ext4')","AsSystemAdministrator");

    $Size=FormatBytes($ImageInfo->FileSize/1024);

    $download=$tpl->icon_download("Loadjs('$page?install=artica-web.ext4.tar.gz')","AsSystemAdministrator");

    $id=md5(json_encode($ImageInfo));
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='$id'>";
    $html[]="<td $tdStyle1><i class='".ico_cd."'></i></td>";
    $html[]="<td style='width:99%' nowrap><strong>$fname</td>";
    $html[]="<td $tdStyle1R>$Size</td>";
    $html[]="<td $tdStyle1R nowrap>Debian $ImageInfo->OperatingSystem</td>";
    $html[]="<td $tdStyle1 nowrap>$installed</td>";
    $html[]="<td $tdStyle1 nowrap>$Version</td>";
    $html[]="<td $tdStyle1 class='center' nowrap>$delete</td>";
    $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";


    $sync=$tpl->framework_buildjs("firecrack:/firecracker/images/sync",
        "firecracker.images.sync","firecracker.images.log","progress-firecracker-images",
        "LoadAjax('table-firecracker-images','$page?table=yes');");


    $topbuttons[] = array("Loadjs('$page?registries-js=yes&mainfunc=$function')", ico_import, "{ConvertFromRegistries}");

    $topbuttons[] = array($sync, ico_refresh, "{synchronize}");

    $APP_FIRECRACKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_VERSION");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{APP_FIRECRACKER} $APP_FIRECRACKER_VERSION &raquo;&raquo; {images}";
    $TINY_ARRAY["ICO"]=ico_cd;
    $TINY_ARRAY["EXPL"]="{APP_FIRECRACKER_IMAGES}";
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]=$headsjs;
    $html[]="</script>";


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}