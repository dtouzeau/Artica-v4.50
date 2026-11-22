<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsSquidAdministrator){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["HotSpotRedirectUI"])){Save();exit;}
if(isset($_GET["events-form"])){events_form();exit;}
if(isset($_GET["events-search"])){events_search();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["export"])){export_download();exit;}
if(isset($_GET["export-progress"])){export_progress_download();exit;}
if(isset($_GET["import-open"])){import_open();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_POST["import"])){import_perform();exit;}
if(isset($_GET["form"])){form();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["enable-feature"])){enable_feature();exit;}
if(isset($_GET["disable-feature"])){disable_feature();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["delete-all"])){delete_all_js();exit;}
if(isset($_POST["delete-all"])){delete_all_perform();exit;}
if(isset($_GET["start-schedule"])){start_schedule();exit;}
if(isset($_POST["start-schedule"])){start_schedule_save();exit;}
if(isset($_GET["edit"])){edit_js();exit;}
if(isset($_GET["edit-popup"])){edit_popup();exit;}
if(isset($_POST["ID"])){edit_save();exit;}
if(isset($_GET["ask-js"])){ask_js();exit;}
if(isset($_GET["ask-popup"])){ask_popup();exit;}
if(isset($_GET["ask-disable"])){ask_disable();exit;}
if(isset($_GET["ask-enable"])){ask_enable();exit;}
page();
function ask_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{improve_website_categorization}","$page?ask-popup=true",650);
}
function ask_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bton=$tpl->button_autnonome("{enable_feature}","Loadjs('$page?ask-enable=yes')",ico_cd,128);
    $btonoff=$tpl->button_autnonome("{cancel}","Loadjs('$page?ask-disable=yes')", ico_exit,128);
    $html[]="<table style='margin-top:10px;width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:50%'>$btonoff</td>";
    $html[]="<td style='width:50%;padding-left:10px'>$bton</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $div=@implode("",$html);
    $f=$tpl->div_explain("{improve_website_categorization}||{improve_website_categorization3}$div");
    echo $f;
    return true;
}
function ask_disable():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldNotCategorizedSeen",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldNotCategorized", 0);
    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();";
    return admin_tracks("Refused sent not-categorized to Artica Cloud");
}
function ask_enable():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldNotCategorizedSeen",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldNotCategorized", 1);
    header("content-type: application/x-javascript");
    echo "dialogInstance6.close();";
    return admin_tracks("Accepted sent not-categorized to Artica Cloud");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{uncategorized_websites}",
        "fa-solid fa-book-circle-arrow-up",
        "{uncategorized_websites_explain}","$page?tabs=yes",
        "uncategorized","progress-uncategorized-div-restart",false,"table-uncategorized-div");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{uncategorized_websites}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{uncategorized_websites}"]="$page?form=yes";
    $array["{events}"]="$page?events-form=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}
function events_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page,null,null,null,"&events-search=yes");
    echo "</div>";
    return true;
}
function enable_feature():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldNotCategorized",1);
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "$function();";
    return true;
}
function disable_feature():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldNotCategorized",0);
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "$function();";
    return true;

}
function events_search():bool{

    $time=null;
    $sock=new sockets();
    $tpl=new template_admin();
    $max=0;$date=null;$c=0;

    $MAIN=$tpl->format_search_protocol($_GET["search"]);

    $line=base64_encode(serialize($MAIN));
    $sock->getFrameWork("ksrn.php?uncategorized-events=$line");
    $target_file=PROGRESS_DIR."/uncategoryzed.syslog";
    $date_text=$tpl->_ENGINE_parse_body("{date}");
    $events=$tpl->_ENGINE_parse_body("{events}");
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>PID</th>
        	<th>{type}</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";

    $data=explode("\n",@file_get_contents($target_file));
    if(count($data)>3){$_SESSION["KSRN_SEARCH"]=$_GET["search"];}
    rsort($data);
    $months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");
    $Year=date("Y");
    foreach ($data as $line) {
        $line = trim($line);
        $rulename = null;
        $ACTION = null;
        $color = "text-muted";
        if(!preg_match("#^([a-zA-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:(.+)#",$line,$re)){
            continue;
        }
        $Month=$months[$re[1]];
        $type="<span class='label label-default'>INFO</span>";
        $day=$re[2];
        $hour=$re[3];
        $pid=$re[4];
        $events=$re[5];
        $FTime=$tpl->time_to_date(strtotime("$Year-$Month-$day $hour"),true);
        if(preg_match("#\[SUCCESS\]:(.+)#",$events,$re)){
            $type="<span class='label label-primary'>{success}</span>";
            $events=$re[1];
        }
        if(preg_match("#\[CATEGORIZE\]:(.+)#",$events,$re)){
            $type="<span class='label label-info'>{categorize}</span>";
            $events=$re[1];
        }
        if(preg_match("#\[REMOVE\]:(.+)#",$events,$re)){
            $type="<span class='label label-warning'>{corrupted}</span>";
            $events=$re[1];
        }
        if(preg_match("#\[DELETE\]:(.+)#",$events,$re)){
            $type="<span class='label label-warning'>{corrupted}</span>";
            $events=$re[1];
        }
        if(preg_match("#\[ERROR\]:(.+)#",$events,$re)){
            $type="<span class='label label-danger'>{error}</span>";
            $events=$re[1];
            $color="text-danger";
        }
        if(preg_match("#\[INFO\]:(.+)#",$events,$re)){
            $type="<span class='label label-info'>{info}</span>";
            $events=$re[1];
            $color="text-muted";
        }

        if(preg_match("#(.+?).go\[(.+?)/(.+?):([0-9]+)](.+)#",$events,$re)){
            $filename=$re[1].".go";
            $class=$re[2];
            $function=$re[3];
            $line=$re[4];
            $events=$re[5]. "&nbsp;&nbsp;&nbsp;<small><i>({line} $line $class $function in $filename)</i></small>";
        }

        $html[]="<tr>
				<td style='width:1%' class='$color' nowrap>$FTime</td>
				<td style='width:1%' class='$color' nowrap >$pid</td>
				<td style='width:1%' class='$color' nowrap >$type</td>
				<td style='width:99%' class='$color' nowrap><span class='$color'>$events</span></td>
				</tr>";

    }
    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/ksrn.syslog.pattern")."</i></div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace("/[^a-zA-Z0-9_]/","",$_GET["function"]);
    $search=$_GET["search"];
    $order=(isset($_GET["order"]) && $_GET["order"]=="asc")?"asc":((isset($_GET["order"]) && $_GET["order"]=="desc")?"desc":"");
    $divid=isset($_GET["div-id"])?preg_replace("/[^a-zA-Z0-9_-]/","",$_GET["div-id"]):"";
    $GoShieldNotCategorized=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldNotCategorized"));
    $LIC=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    if (!$LIC) {
        $html=$tpl->div_warning("{license_error}||{uncategorized_websites_license}");
        echo $tpl->_ENGINE_parse_body($html);
        return true;

    }

    if($GoShieldNotCategorized==0){
        $button1 = $tpl->button_autnonome("{enable_feature}",
            "Loadjs('$page?enable-feature=yes&function=$function');", ico_cd, null, 501);
        $html=$tpl->div_explain("{backup_disabled}||{uncategorized_websites_enable}<p style='margin-top: 30px'>$button1</p>||8");
        echo $tpl->_ENGINE_parse_body($html);
        return true;

    }

    $t=time();
    $add="Loadjs('$page?edit=0');";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_field}</label>";
    $btns[]="</div>";

    $html[]="<table id='table-hotspot-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' colspan='2'>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{domains}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{updated}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $uri="/categories/uncategorized/list";
    $params=array();
    if($search<>null){$params[]="search=".urlencode($search);}
    if($order<>null){$params[]="order=".urlencode($order);}
    if(count($params)>0){$uri.="?".@implode("&",$params);}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($uri));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        $err=(is_object($json) && isset($json->Error) && $json->Error<>null) ? $json->Error : "{error}";
        echo $tpl->FATAL_ERROR_SHOW_128($err);
        return true;
    }
    $count=$tpl->FormatNumber(intval($json->CountCat0 ?? 0));
    $countMax=$tpl->FormatNumber(intval($json->CountTotal ?? 0));

    $results=(isset($json->Rows) && is_array($json->Rows)) ? $json->Rows : array();
    $TRCLASS=null;
    $earch=ico_earth;
    foreach ($results as $index=>$ligne){
        $md=md5(serialize($ligne));
        $host=htmlspecialchars($ligne->host,ENT_QUOTES,"UTF-8");
        $created=intval($ligne->created);
        $category=intval($ligne->category);
        $category_text="{unknown}";
        $categorized_text="-";
        $categorized=intval($ligne->categorized);
        $categorized_distance=null;
        if($category>0){
            $category_text=htmlspecialchars($ligne->category_name,ENT_QUOTES,"UTF-8");

        }

        $ico="<i class='$earch' style='color:rgb(103, 106, 108)'></i>";
        if($categorized>0){
            $categorized_text=$tpl->time_to_date($categorized);
            $categorized_distance=" (".distanceOfTimeInWords($created,$categorized).")";
            $ico="<i class='$earch' style='color:rgb(26, 179, 148)'></i>";
        }
        $created=$tpl->time_to_date($created);
        //
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$ico</td>";
        $html[]="<td style='width:1%' nowrap>$created</td>";
        $html[]="<td style='width:99%'>$host</td>";
        $html[]="<td style='width:1%' nowrap>$category_text</td>";
        $html[]="<td style='width:1%' nowrap>$categorized_text$categorized_distance</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="</table>";


    $launch=$tpl->framework_buildjs("/categories/uncategorized/launch",
        "uncategorize.launch.progress",
        "uncategorize.launch.log",
        "progress-uncategorized-div-restart",
        "$function()"
    );

    $export=$tpl->framework_buildjs("/categories/uncategorized/launch",
        "not-categorized.progress",
        "not-categorized.progress.log",
        "progress-uncategorized-div-restart",
        "document.location.href='$page?export-progress=yes'"
    );

    $topbuttons[]=array($export,ico_export,"{export}");
    $topbuttons[]=array($launch,ico_run,"{analyze}");


    if($divid<>null){
        $searchEnc=urlencode($search);
        $nextOrder=($order=="asc")?"desc":"asc";
        if($nextOrder=="asc"){$sortIco="fa-solid fa-arrow-down-short-wide";$sortLabel="{oldest_first}";}
        else{$sortIco="fa-solid fa-arrow-up-wide-short";$sortLabel="{newest_first}";}
        $sortJs="LoadAjaxTiny('$divid','$page?search=$searchEnc&function=$function&div-id=$divid&order=$nextOrder');";
        $topbuttons[]=array($sortJs,$sortIco,$sortLabel);
    }

    $topbuttons[]=array("document.location.href='$page?export=yes'",ico_download,"{export}");
    $topbuttons[]=array("Loadjs('$page?import-open=yes&function=$function')",ico_upload,"{import}");

    $topbuttons[]=array("Loadjs('$page?disable-feature=yes&function=$function');",
        ico_trash,"{disabled_feature}");
    $btns=$tpl->table_buttons($topbuttons);

    $TINY_ARRAY["TITLE"]="{uncategorized_websites} $count/$countMax {records}";
    $TINY_ARRAY["ICO"]="fa-solid fa-book-circle-arrow-up";
    $TINY_ARRAY["EXPL"]="{uncategorized_websites_explain}";
    $TINY_ARRAY["URL"]="uncategorized";
    $TINY_ARRAY["BUTTONS"] = $btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$jstiny
	</script>";


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function export_progress_download():bool{
    @ini_set("display_errors",0);
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/uncategorized/export/get"),true);
    @ob_clean();
    $body=$data["content"];
    header("Content-Type: application/text");
    header("Content-Disposition: attachment; filename=\"export.txt\"");
    header("Content-Length: ".strlen($body));
    header("Cache-Control: no-cache, must-revalidate");
    echo $body;
    ob_end_flush();
    return true;
}
function export_download(){
    @ini_set("display_errors",0);
    if(!function_exists("curl_init")){header("HTTP/1.1 500 Internal Server Error");echo "curl not available";return;}
    $socket="/usr/share/artica-postfix/bin/run/articarest.sock";
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_URL,"http://localhost/categories/uncategorized/export");
    curl_setopt($ch,CURLOPT_UNIX_SOCKET_PATH,$socket);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_TIMEOUT,300);
    $body=curl_exec($ch);
    $code=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));
    curl_close($ch);
    if($body===false || $code!=200){
        header("HTTP/1.1 ".($code?:500)." Error");
        echo "Export error (HTTP $code)";
        return;
    }
    $filename="uncategorized-".date("Y-m-d-His").".json.gz";
    @ob_clean();
    header("Content-Type: application/gzip");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Length: ".strlen($body));
    header("Cache-Control: no-cache, must-revalidate");
    echo $body;
}

function import_open(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=preg_replace("/[^a-zA-Z0-9_]/","",$_GET["function"]);
    $tpl->js_dialog1("{import_uncategorized}","$page?import-popup=yes&function=$function",540);
}

function import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=preg_replace("/[^a-zA-Z0-9_]/","",$_GET["function"]);
    $t=time();
    $html=array();
    $html[]="<div style='width:98%;padding:10px'>";
    $html[]="<div class='text-info' style='margin-bottom:15px'>{import_uncategorized_explain}</div>";
    $html[]="<form id='import-frm-$t' enctype='multipart/form-data'>";
    $html[]="<input type='hidden' name='import' value='yes'>";
    $html[]="<input type='file' name='importfile' accept='.gz' class='form-control' style='margin-bottom:15px'>";
    $html[]="<center><button type='button' class='btn btn-primary' onclick='ImportUncat$t()'><i class='".ico_upload."'></i> {import}</button></center>";
    $html[]="</form>";
    $html[]="<div id='import-res-$t' style='margin-top:15px'></div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    echo "<script>
function ImportUncat$t(){
    var frm=document.getElementById('import-frm-$t');
    var fileInput=frm.querySelector('input[type=file]');
    if(!fileInput.files.length){return;}
    var fd=new FormData(frm);
    document.getElementById('import-res-$t').innerHTML=\"<div class='text-center'><i class='fa fa-spinner fa-spin fa-2x'></i></div>\";
    $.ajax({url:'$page',type:'POST',data:fd,processData:false,contentType:false,
        success:function(r){document.getElementById('import-res-$t').innerHTML=r;if(typeof window['$function']==='function'){window['$function']();}},
        error:function(){document.getElementById('import-res-$t').innerHTML=\"<div class='alert alert-danger'>Error</div>\";}
    });
}
</script>";
}

function import_perform(){
    $tpl=new template_admin();
    if(!isset($_FILES["importfile"]) || !isset($_FILES["importfile"]["tmp_name"]) || intval($_FILES["importfile"]["error"])!=0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{select_a_file}"));
        return;
    }
    if(intval($_FILES["importfile"]["size"])==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{select_a_file}"));
        return;
    }
    $binary=@file_get_contents($_FILES["importfile"]["tmp_name"]);
    if($binary===false || strlen($binary)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_BINARY("/categories/uncategorized/import",$binary));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        $err=(is_object($json) && isset($json->Error) && $json->Error<>null)?$json->Error:"{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }
    $inserted=intval($json->Inserted ?? 0);
    $skipped=intval($json->Skipped ?? 0);
    $total=intval($json->Total ?? 0);
    admin_tracks("Imported uncategorized websites: $inserted added, $skipped skipped");
    $msg="{import_done} - $inserted {added}, $skipped {skipped} / $total {total}";
    echo $tpl->_ENGINE_parse_body($tpl->div_success($msg));
}