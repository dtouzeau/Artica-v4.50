<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");


    if(isset($_GET["table"])){table();exit;}
    if(isset($_GET["count-sessions"])){count_sessions();exit;}
    if(isset($_GET["sessions-delete-all"])){sessions_delete_all();exit;}
    if(isset($_POST["sessions-delete-all"])){sessions_delete_all_confirm();exit;}

    if(isset($_GET["session-delete"])){session_delete();exit;}
    if(isset($_GET["reload-proxy"])){reload_proxy();exit;}

    if(isset($_GET["itchart-js"])){itchart_js();exit;}
    if(isset($_GET["itchart-tabs"])){itchart_tabs();exit;}
    if(isset($_GET["itchart-settings"])){itchart_settings();exit;}
    if(isset($_POST["itchart-settings"])){itchart_settings_save();exit;}
    // itchart_enable() n'existe que dans fw.itcharter.table.php: cette route
    // etait un Fatal "Call to undefined function", et aucune URL de cette page
    // ne la visait. Aiguillage retire.
    if(isset($_GET["itchart-content"])){itchart_content();exit;}
    if(isset($_POST["itchart-content"])){itchart_content_save();exit;}

    if(isset($_GET["itchart-headers"])){itchart_headers();exit;}
    if(isset($_POST["itchart-headers"])){itchart_headers_save();exit;}

    if(isset($_GET["itchart-pdf"])){itchart_pdf();exit;}
    if(isset($_GET["itchart-pdf2"])){itchart_pdf2();exit;}
    if(isset($_POST["itchart-pdf"])){itchart_pdf_save();exit;}
    if(isset($_GET["itchart-pdf-down"])){itchart_pdf_output();exit;}
    if(isset($_GET["file-uploaded"])){itchart_pdf_uploaded();exit;}
    if(isset($_GET["table-start"])){table_start();exit;}
xStart();

function reload_proxy():bool{
    $libmem=new lib_memcached();
    $keys = $libmem->allKeys();
    $regex = 'itchart_cache_.*';
    foreach($keys as $item) {
        if(preg_match('/'.$regex.'/', $item)) {
            $libmem->Delkey($item);
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/nohup/reload");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/clients/proxy/reload");
    $tpl=new template_admin();
    return $tpl->js_error("{empty_cache} {done}");

}

function xStart():bool{
    $page=CurrentPageName();
    echo "<div id='itcharters-sessions-table' style='margin-top: 20px'></div>";
    echo "<script>LoadAjax('itcharters-sessions-table','$page?table-start=yes');</script>";
    return true;
}
function table_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->search_block($page,"","","","&table=yes");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function session_delete():bool{
    $tpl=new template_admin();
    $ID     = $_GET["session-delete"];
    $redis=new redis();
    try {
        $redis->connect('127.0.0.1','6123');
    } catch (Exception $e) {
        $tpl->js_error($e->getMessage());
        die();
    }
    $md=md5($ID);
    $redis->del($ID);
    $redis->close();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/clients/proxy/reload");
    echo "$('#$md').remove();\n";
    return admin_tracks("ITChart: Delete session #$ID");
}

function sessions_delete_all():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_confirm_delete("{delete_all}","sessions-delete-all","0","LoadAjax('itcharters-sessions-table','$page?table=yes');");

}

function count_sessions():bool{
    $mem="";
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/itcharter/dumpmem"),true);

    if(isset($json["list"])){
       $list=json_decode($json["list"],true);
        //var_dump($list);
       if(is_array($list)){
           $CountMem=count($list);
           $mem="&nbsp;<small>(<i class='".ico_mem."'></i>&nbsp;".$tpl->FormatNumber($CountMem).")</small>";

       }
    }

    $redis=new redis();
    try {
        $redis->connect('127.0.0.1','6123');
    } catch (Exception $e) {
        die();
    }
    $results=$redis->keys("*|*");
    if(is_array($results) && count($results)>0){

        echo $tpl->FormatNumber(count($results)).$mem;
    }
    $redis->close();
    return true;

}

function sessions_delete_all_confirm():bool{

    $redis=new redis();
    try {
        $redis->connect('127.0.0.1','6123');
    } catch (Exception $e) {
        echo $e->getMessage();
        die();
    }

    $results=$redis->keys("*|*");
    foreach ($results as $key){
        $redis->del($key);
    }
    $redis->close();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/clients/proxy/reload");
    return admin_tracks("ITChart: Delete all sessions");
}

function itchart_db_get($ID){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/itcharter/db/".intval($ID)));
    if(!is_object($json)){return null;}
    if(empty($json->Status)){return null;}
    if(!isset($json->Row)){return null;}
    return $json->Row;
}

function itchart_db_put($ID,$payload){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/itcharter/db/".intval($ID),$payload));
    if(!is_object($json)){return "Invalid daemon response";}
    if(empty($json->Status)){
        if(isset($json->Error)){return (string)$json->Error;}
        return "Unknown daemon error";
    }
    return "";
}

function itchart_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["itchart-js"]);
    $title="{new_itchart}";
    if($ID>0){
        $row=itchart_db_get($ID);
        if(is_object($row)){$title=$row->title;}
    }

    return $tpl->js_dialog1($title,"$page?itchart-tabs=$ID",990);

}
function itchart_content():bool{

    $tpl=new template_admin();
    $ID=intval($_GET["itchart-content"]);

    $row=itchart_db_get($ID);
    $ChartContent="";
    if(is_object($row) && isset($row->ChartContent)){
        $ChartContent=trim(base64_decode($row->ChartContent));
    }
    $ChartContent=str_replace("%C2%A0"," ",$ChartContent);

    if(strlen($ChartContent)<10){
        $ChartContent=@file_get_contents("ressources/databases/DefaultAcceptableUsePolicy.html");
    }

    $tpl->field_hidden("itchart-content",$ID);
    $form[]=$tpl->field_textareacode("ChartContent",null,$ChartContent);

    echo $tpl->form_outside("{content}",$form,null ,"{apply}", null,"AsSquidAdministrator",true);
    return true;
}

function itchart_headers():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["itchart-headers"]);

    $row=itchart_db_get($ID);
    $Content="";
    if(is_object($row) && isset($row->ChartHeaders)){
        $Content=trim(base64_decode($row->ChartHeaders));
    }

    if(strlen($Content)<10){
        $Content=@file_get_contents("ressources/databases/DefaultAcceptableUsePolicyH.html");
    }

    $tpl->field_hidden("itchart-headers",$ID);
    $form[]=$tpl->field_textareacode("ChartHeaders",null,$Content);

    echo $tpl->form_outside("{headers}",$form,null ,"{apply}", null,"AsSquidAdministrator",true);
    return true;
}
function itchart_pdf():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["itchart-pdf"]);
    echo "<div id='itcharter-pdf-$ID' style='margin-top:20px'></div><script>LoadAjax('itcharter-pdf-$ID','$page?itchart-pdf2=$ID');</script>";
    return true;

}

function itchart_pdf_output(){

    $ID=intval($_GET["itchart-pdf-down"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/itcharter/db/$ID/pdf"));
    if(!is_object($json) || empty($json->Status) || !isset($json->Row)){
        header("HTTP/1.1 404 Not Found");
        return;
    }
    $row=$json->Row;
    $PdfFileName=$row->PdfFileName;
    $PdfFileSize=intval($row->PdfFileSize);
    header('Content-type: application/pdf');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$PdfFileName\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$PdfFileSize);
    ob_clean();
    flush();
    echo base64_decode($row->PdfContent);

}

function itchart_pdf_uploaded():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $filepath="/usr/share/artica-postfix/ressources/conf/upload/$filename";

    if(intval( $_SESSION["PDF-ID"])==0){
            $tpl->js_error("NO ID !");
            @unlink($filepath);
            return false;
    }

    $ID=intval( $_SESSION["PDF-ID"]);

    if(!is_file($filepath)){
        $tpl->js_error($filepath ." no such file");
        return false;
    }

    if(!preg_match("#^.*?\.pdf$#",$filename)){
        $tpl->js_error($filepath ." NOT A PDF");
        @unlink($filepath);
        return false;
    }
    $size=@filesize($filepath);
    $data=base64_encode(@file_get_contents($filepath));
    @unlink($filepath);

    $filename=replace_accents($filename);
    $filename=str_replace(" ","_",$filename);
    $filename=str_replace("'","_",$filename);

    $error=itchart_db_put($ID,array(
        "enablepdf"=>1,
        "PdfFileName"=>$filename,
        "PdfFileSize"=>intval($size),
        "PdfContent"=>$data));
    if($error<>""){echo $tpl->js_error($error); return false;}
    echo "LoadAjax('itcharter-pdf-$ID','$page?itchart-pdf2=$ID')\n";
    echo "LoadAjax('itcharters-sessions-table','$page?table=yes');";
    return admin_tracks("ITChart: Upload a new PDF file $filename for ITCharter #$ID");


}

function itchart_pdf2()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $ID = intval($_GET["itchart-pdf2"]);

    $row = itchart_db_get($ID);
    $title = "";
    $enablepdf = 0;
    $PdfFileSize = 0;
    $PdfFileName = "";
    if (is_object($row)) {
        $title = $row->title;
        $enablepdf = intval($row->enablepdf);
        $PdfFileSize = intval($row->PdfFileSize);
        $PdfFileName = $row->PdfFileName;
    }

    if ($PdfFileSize > 0) {
        $title = "PDF $PdfFileName (" . FormatBytes($PdfFileSize / 1024) . ")";
    }

    $tpl->field_hidden("itchart-pdf", $ID);
    $form[] = $tpl->field_checkbox("enablepdf", "{enabled}", $enablepdf);
    $tpl->form_add_button_upload("{upload}:PDF", $page, "AsSquidAdministrator");

    $_SESSION["PDF-ID"] = $ID;

    $html[] = "<table style='width:100%'>";
    $html[] = "<tr>";
    $html[] = "<td style='vertical-align:top;width:255px'>";
    $html[] = "<canvas id=\"the-canvas\" style='height: 400px;width:300px;border:1px solid #CCCCCC;'></canvas>";
    $html[]="</td>";
    $html[] = "<td style='vertical-align:top;width:90%;padding-left:10px'>";
    $html[] = $tpl->form_outside($title, $form, "{charter_pdf_explain}", "{apply}", "LoadAjax('itcharters-sessions-table','$page?table=yes');", "AsSquidAdministrator", true);
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</table>";
    if($PdfFileSize>0) {
        $html[] = "<script>";
        $html[] = "var loadingTask = pdfjsLib.getDocument('$page?itchart-pdf-down=$ID');";
        $html[] = "loadingTask.promise.then(function(pdf) {";
        $html[] = "\tvar pageNumber = 1;";
        $html[] = "\tpdf.getPage(pageNumber).then(function(page) {";
        $html[] = "\tvar scale = 1;";
        $html[] = "\tvar viewport = page.getViewport({scale: scale});";
        $html[] = "\tvar canvas = document.getElementById('the-canvas');";
        $html[] = "\tvar context = canvas.getContext('2d');";
        $html[] = "\tcanvas.height = viewport.height;";
        $html[] = "\tcanvas.width = viewport.width;";
        $html[] = "\tvar renderContext = { canvasContext: context, viewport: viewport };";
        $html[] = "\tvar renderTask = page.render(renderContext);";
        $html[] = "\trenderTask.promise.then(function(){ });";
        $html[] = "\t});";
        $html[] = "});";
        $html[] = "</script>";
    }

    echo $tpl->_ENGINE_parse_body($html);

}

function itchart_pdf_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["itchart-pdf"]);
    $error=itchart_db_put($ID,array("enablepdf"=>intval($_POST["enablepdf"])));
    if($error<>""){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($error));return;}
}

function itchart_content_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["itchart-content"]);
    $content=base64_encode($_POST["ChartContent"]);
    $error=itchart_db_put($ID,array("ChartContent"=>$content));
    if($error<>""){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($error));return;}
}

function itchart_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["itchart-tabs"]);

    $array["{parameters}"]="$page?itchart-settings=$ID";
    if($ID>0){
        $array["{content}"]="$page?itchart-content=$ID";
        $array["{headers}"]="$page?itchart-headers=$ID";
        $array["PDF"]="$page?itchart-pdf=$ID";
    }


    echo $tpl->tabs_default($array);
}

function itchart_settings(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["itchart-settings"]);
    $title="Acceptable Use Policy";
    $title_form="{new_itchart}";
    $btname="{add}";
    $t=time();
    $jsAfter="LoadAjax('itcharters-sessions-table','$page?table=yes');";

    $TextIntro=null;
    $TextButton=null;

    if($ID>0){
        $row=itchart_db_get($ID);
        if(!is_object($row)){echo $tpl->div_error("Unable to fetch IT Charter #$ID from the daemon");}
        if(is_object($row)){
            $title=$row->title;
            $TextIntro=$row->TextIntro;
            $TextButton=$row->TextButton;
        }
        $title_form=$title;
        $btname="{apply}";
        $jsAfter=$jsAfter."dialogInstance1.close();";

    }

    if($TextIntro==null){
        $TextIntro="Please read the IT chart before accessing trough Internet";
    }
    if($TextButton==null){
        $TextButton="I accept the terms and conditions of this agreement";

    }
    $tpl->field_hidden("itchart-settings",$ID);
    $form[]=$tpl->field_text("title","{page_title}",$title,true);
    $form[]=$tpl->field_text("TextButton","{text_button}",$TextButton,true);
    $form[]=$tpl->field_textareacode("TextIntro","{introduction_text}",$TextIntro);

    echo $tpl->form_outside($title_form,$form,null ,$btname, $jsAfter,"AsSquidAdministrator",true);


}
function itchart_headers_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["itchart-headers"]);
    $content=base64_encode($_POST["ChartHeaders"]);
    $error=itchart_db_put($ID,array("ChartHeaders"=>$content));
    if($error<>""){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($error));return;}
}

function itchart_settings_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["itchart-settings"]);

    unset($_POST["itchart-settings"]);
    $payload=array();
    foreach ($_POST as $key=>$value){
        $payload[$key]=$value;
    }

    if($ID>0){
        $error=itchart_db_put($ID,$payload);
        if($error<>""){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($error));return;}
        return;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/itcharter/db",$payload));
    if(!is_object($json) || empty($json->Status)){
        $error="Unknown daemon error";
        if(is_object($json) && isset($json->Error)){$error=(string)$json->Error;}
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text($error));
        return;
    }
}


// repondeur ?search=&table=yes : possede le tableau ET le TINY (skill §7d, §13b)
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

    $redis=new redis();
    try {
        $redis->connect('127.0.0.1','6123');
        $ClusterEnabled=intval($redis->get("ITChartClusterEnabled"));

    } catch (Exception $e) {
        echo $tpl->FATAL_ERROR_SHOW_128($e->getMessage());
        die();
    }

    if($PowerDNSEnableClusterSlave==1){$ClusterEnabled=1;}
    $html=array();
    if($HaClusterClient==1){
        $ClusterEnabled=1;
        $html[]=$tpl->div_info("{feature_to_hacluster}");
    }

    $results=$redis->keys("*|*");
    if(!is_array($results)){ $results=array(); }
    $itchart_compile=itchart_compile();
    $search=trim((string)($_GET["search"] ?? ""));
    $search=trim($search,"*");

    $rows=array();
    foreach ($results as $key){
        if($search!=="" && stripos($key,$search)===false){ continue; }
        $Time=intval($redis->get($key));
        $zkey=explode("|",$key);
        $User=(string)$zkey[0];
        $ChartID=intval($zkey[1] ?? 0);
        $title=isset($itchart_compile[$ChartID]) ? (string)$itchart_compile[$ChartID] : "#$ChartID";
        $rows[]=session_row($tpl,$page,$key,$User,$ChartID,$title,$Time,$HaClusterClient);
    }
    $redis->close();

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{members}</th>";
    $html[]="<th>{it_charters}</th>";
    $html[]="<th class='awc-nowrap'>{date}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='awc-rows'>";
    if(count($rows)==0){ $html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>"; }
    else { $html[]=implode("\n",$rows); }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)." / ".count($results)." {items}</span><span class='awc-pg' id='awc-pager'></span></div>";
    $html[]="</div></div>";

    $items=0;
    $libmem=new lib_memcached();
    $keys = $libmem->allKeys();
    if(is_array($keys)){
        foreach($keys as $item) {
            if(preg_match('/itchart_cache_.*/', $item)) { $items++; }
        }
    }

    // TINY : titre + boutons, emis en DERNIER, apres le tableau (skill §2, §7d)
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?reload-proxy=yes')", ico_refresh, "{empty_cache} ($items)");
    if($ClusterEnabled==0) {
        $topbuttons[] = array("Loadjs('$page?sessions-delete-all=yes');", ico_trash, "{delete_all}");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{it_charters} <span id='NumSessions'></span> {sessions}";
    $TINY_ARRAY["ICO"]=ico_users;
    $TINY_ARRAY["EXPL"]="{IT_charter_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $SessionsPing=$tpl->RefreshInterval_js("NumSessions",$page,"count-sessions=yes",5);

    // CSS, scripts d'icones et pager concatenes AUTOUR du parse, jamais dedans (skill §17 regle 1)
    $js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$headsjs\n$SessionsPing</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js.awc_pager_js(25);
}

// une ligne du tableau (skill §13b)
function session_row(template_admin $tpl,string $page,string $key,string $User,int $ChartID,string $title,int $Time,int $HaClusterClient):string{
    $md=md5($key);
    $zdate=htmlspecialchars($tpl->time_to_date($Time,true));
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-user'></i></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars($User)."</span>";
    $h.="<div class='awc-meta'>{it_charters} #$ChartID</div>";
    $h.="</span></div></td>";
    $h.="<td>".htmlspecialchars($title)."</td>";
    $h.="<td class='awc-nowrap'><span class='awc-seen'>$zdate</span></td>";
    $h.="<td><div class='awc-acts'>";
    if($HaClusterClient!=1){
        $h.="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" onclick=\"Loadjs('$page?session-delete=".urlencode($key)."&md=$md')\"><i class='fas fa-trash-can'></i></a>";
    }
    $h.="</div></td>";
    $h.="</tr>";
    return $h;
}

function itchart_compile(){
    $icharters=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/itcharter/db/list"));
    if(!is_object($json)){return $icharters;}
    if(empty($json->Status)){return $icharters;}
    if(!isset($json->Rows)){return $icharters;}
    if(!is_array($json->Rows)){return $icharters;}

    foreach ($json->Rows as $row){
        $icharters[intval($row->ID)]=$row->title;
    }

   return $icharters;

}