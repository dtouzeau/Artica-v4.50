<?php
/**
 * Artica InstantHotfix console
 * Create a temporary Hotfixes storage for updating quickly a bunch of Artica servers
 */

include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';

if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["upload-popup"])){upload_popup();exit;}
if(isset($_GET["file-uploaded"])){upload_uploaded_js();exit;}
if(isset($_GET["hotfixes-start"])){hotfixes_start();exit;}
if(isset($_GET["search"])){hotfixes_search();exit;}
if(isset($_GET["hotfixes-delete"])){hotfixes_delete_js();exit;}
if(isset($_POST["hotfixes-delete"])){hotfixes_delete_confirm();exit;}
function js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users=new usersMenus();
    if(!$users->AsArticaMetaAdmin){
        return $tpl->js_error("{no_privileges}");
    }
    return $tpl->js_dialog6("InstantHotfix", "$page?tabs=yes", 600);
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{upload}"]="$page?upload-popup=yes";
    $array["{hotfixes}"]="$page?hotfixes-start=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function upload_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $VERSION=@file_get_contents("VERSION");
    $InstantHotfix_explain=$tpl->_ENGINE_parse_body("{InstantHotfix_explain}");
    $CurrentServicePack =  $GLOBALS["CLASS_SOCKETS"]->SPVersion();
    $InstantHotfix_explain=str_replace("%mainver",$VERSION,$InstantHotfix_explain);
    $InstantHotfix_explain=str_replace("%sp",$CurrentServicePack,$InstantHotfix_explain);
    $function="";
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->div_explain("InstantHotfix||$InstantHotfix_explain");
    $html[]="<div id='import-file-progress'></div>";
    $html[]="<div class=center style='margin:30px'>".$tpl->button_upload("{upload_your_file_here} (*.gz)",$page,null,"&function=$function")."</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function upload_uploaded_js():bool{
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);

    $js=$tpl->framework_buildjs("/articarepos/instanthotfix/uploaded/$fileencode",
        "instanthotfix.progress",
        "instanthotfix.progress.log",
        "import-file-progress",""
    );
    header("content-type: application/x-javascript");
    echo "$js\n";


    return admin_tracks("Meta: uploaded new Instant Hotfix $filename");
}
function hotfixes_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}
function hotfixes_delete_js():bool{
    $tpl=new template_admin();
    $md=$_GET["hotfixes-delete"];
    $filename=$_GET["f"];
    return $tpl->js_confirm_delete($filename,"hotfixes-delete",$md,"$('#$md').remove()");
}
function hotfixes_delete_confirm():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $md=$_POST["hotfixes-delete"];

    $Main=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/instanthotfix/list"),true);
    if(!isset($Main["Status"])){
        echo $tpl->javascript_parse_text("{protocol_error}");
        return false;
    }
    if(!$Main["Status"]){
        echo $tpl->javascript_parse_text($Main["Error"]);
        return false;
    }
    $Real=array();
    foreach ($Main["List"] as $index=>$ligne){
        $md5=$ligne["md5"];
        if($md5==$md){
            $Real=$ligne;
            break;
        }
    }
    if(!isset($Real["md5"])){
        echo $tpl->javascript_parse_text("{source_package_not_found}");
        return false;
    }

    $Main=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/articarepos/instanthotfix/delete",$Real),true);
    if(!isset($Main["Status"])){
        echo $tpl->javascript_parse_text("{protocol_error}");
        return false;
    }
    if(!$Main["Status"]){
        echo $tpl->javascript_parse_text($Main["Error"]);
        return false;
    }
    return admin_tracks("Meta: Removed Instant Hotfix $md");
}
function hotfixes_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table id='table-proxypac-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text' colspan='2'>{hotfixes}</th>";
    $html[]="<th data-sortable=true data-type='text' >{uploaded}</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $Main=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/instanthotfix/list"),true);
    if(!isset($Main["Status"])){
        echo $tpl->div_error("{protocol_error}");
        return false;
    }
    if(!$Main["Status"]){
        echo $tpl->div_error($Main["Error"]);
        return false;
    }


    $TRCLASS=null;
    $ico=ico_file_zip;
    foreach ($Main["List"] as $index=>$ligne){

            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $filename=$ligne["filename"];
            $filepath=urlencode($ligne["filepath"]);
            $filenameEnc=urlencode($filename);
            $time=$tpl->time_to_date($ligne["date"],true);
            $size=FormatBytes($ligne["size"]/1024);
            $md5=$ligne["md5"];
            $delete=$tpl->icon_delete("Loadjs('$page?hotfixes-delete=$md5&f=$filenameEnc')","AsArticaMetaAdmin");
            $Update=$tpl->icon_cd("Loadjs('fw.netagents.push.software.php?type=hotfix&f=$filepath')","AsArticaMetaAdmin");
            
            $html[]="<tr class='$TRCLASS' id='$md5'>";
            $html[]="<td style='width:1%' class='center' nowrap><i class='$ico'></i></center></td>";
            $html[]="<td><strong>$filename</strong> ($size)</td>";
            $html[]="<td style='width:1%' nowrap>$time</center></td>";
            $html[]="<td style='width:1%' nowrap>$Update</td>";
            $html[]="<td style='width:1%' nowrap>$delete</td>";
            $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}