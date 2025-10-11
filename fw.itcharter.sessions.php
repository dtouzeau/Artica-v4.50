<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");


    if(isset($_GET["table"])){table();exit;}

    if(isset($_GET["sessions-delete-all"])){sessions_delete_all();exit;}
    if(isset($_POST["sessions-delete-all"])){sessions_delete_all_confirm();exit;}

    if(isset($_GET["session-delete"])){session_delete();exit;}
    if(isset($_GET["reload-proxy"])){reload_proxy();exit;}

    if(isset($_GET["itchart-js"])){itchart_js();exit;}
    if(isset($_GET["itchart-tabs"])){itchart_tabs();exit;}
    if(isset($_GET["itchart-settings"])){itchart_settings();exit;}
    if(isset($_POST["itchart-settings"])){itchart_settings_save();exit;}
    if(isset($_GET["itchart-enable"])){itchart_enable();exit;}
    if(isset($_GET["itchart-content"])){itchart_content();exit;}
    if(isset($_POST["itchart-content"])){itchart_content_save();exit;}

    if(isset($_GET["itchart-headers"])){itchart_headers();exit;}
    if(isset($_POST["itchart-headers"])){itchart_headers_save();exit;}

    if(isset($_GET["itchart-pdf"])){itchart_pdf();exit;}
    if(isset($_GET["itchart-pdf2"])){itchart_pdf2();exit;}
    if(isset($_POST["itchart-pdf"])){itchart_pdf_save();exit;}
    if(isset($_GET["itchart-pdf-down"])){itchart_pdf_output();exit;}
    if(isset($_GET["file-uploaded"])){itchart_pdf_uploaded();exit;}

xStart();

function reload_proxy(){
    $libmem=new lib_memcached();
    $keys = $libmem->allKeys();
    $regex = 'itchart_cache_.*';
    foreach($keys as $item) {
        if(preg_match('/'.$regex.'/', $item)) {
            $libmem->Delkey($item);
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?reload-squid-cache=yes");
    $tpl=new template_admin();
    $tpl->js_error("{empty_cache} {done}");

}

function xStart(){

    $page=CurrentPageName();
    echo "<div id='itcharters-sessions-table' style='margin-top: 20px'></div>";
    echo "<script>LoadAjax('itcharters-sessions-table','$page?table=yes');</script>";

}
function session_delete(){
    $tpl=new template_admin();
    $ID     = $_GET["session-delete"];
    $redis=new redis();
    try {
        $redis->connect('127.0.0.1','6123');
    } catch (Exception $e) {
        $tpl->js_error($e->getMessage());
        die();
    }
    $md=$_GET["md"];
    $redis->del($ID);
    $redis->close();
    echo "$('#$md').remove();\n";
}

function sessions_delete_all(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_delete("{delete_all}","sessions-delete-all","0","LoadAjax('itcharters-sessions-table','$page?table=yes');");

}
function sessions_delete_all_confirm(){

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

}

function itchart_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["itchart-js"]);
    $title="{new_itchart}";
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT title FROM itcharters WHERE ID=$ID");
        $title=$ligne["title"];
    }

    $tpl->js_dialog1($title,"$page?itchart-tabs=$ID",990);

}
function itchart_content(){

    $tpl=new template_admin();
    $ID=intval($_GET["itchart-content"]);

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT ChartContent FROM itcharters WHERE ID='$ID'");

    $ChartContent=trim(base64_decode($ligne["ChartContent"]));
    $ChartContent=str_replace("%C2%A0"," ",$ChartContent);

    if(strlen($ChartContent)<10){
        $ChartContent=@file_get_contents("ressources/databases/DefaultAcceptableUsePolicy.html");
    }

    $tpl->field_hidden("itchart-content",$ID);
    $form[]=$tpl->field_textareacode("ChartContent",null,$ChartContent);

    echo $tpl->form_outside("{content}",$form,null ,"{apply}", null,"AsSquidAdministrator",true);

}

function itchart_headers(){
    $tpl=new template_admin();
    $ID=intval($_GET["itchart-headers"]);

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT ChartHeaders FROM itcharters WHERE ID='$ID'");

    $Content=trim(base64_decode($ligne["ChartHeaders"]));

    if(strlen($Content)<10){
        $Content=@file_get_contents("ressources/databases/DefaultAcceptableUsePolicyH.html");
    }

    $tpl->field_hidden("itchart-headers",$ID);
    $form[]=$tpl->field_textareacode("ChartHeaders",null,$Content);

    echo $tpl->form_outside("{headers}",$form,null ,"{apply}", null,"AsSquidAdministrator",true);

}
function itchart_pdf(){
    $page=CurrentPageName();
    $ID=intval($_GET["itchart-pdf"]);
    echo "<div id='itcharter-pdf-$ID' style='margin-top:20px'></div><script>LoadAjax('itcharter-pdf-$ID','$page?itchart-pdf2=$ID');</script>";


}

function itchart_pdf_output(){

    $ID=intval($_GET["itchart-pdf-down"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT PdfFileName,PdfFileSize,PdfContent FROM itcharters WHERE ID='$ID'");
    $PdfFileName=$ligne["PdfFileName"];
    $PdfFileSize=$ligne["PdfFileSize"];
    header('Content-type: application/pdf');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$PdfFileName\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$PdfFileSize);
    ob_clean();
    flush();
    echo base64_decode($ligne["PdfContent"]);

}

function itchart_pdf_uploaded(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $filepath="/usr/share/artica-postfix/ressources/conf/upload/$filename";

    if(intval( $_SESSION["PDF-ID"])==0){
            $tpl->js_error("NO ID !");
            @unlink($filepath);
            return;
    }

    $ID=intval( $_SESSION["PDF-ID"]);

    if(!is_file($filepath)){
        $tpl->js_error($filepath ." no such file");
        return;
    }

    if(!preg_match("#^.*?\.pdf$#",$filename)){
        $tpl->js_error($filepath ." NOT A PDF");
        @unlink($filepath);
        return;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $size=@filesize($filepath);
    $data=base64_encode(@file_get_contents($filepath));
    @unlink($filepath);

    if(!$q->FIELD_EXISTS("itcharters", "PdfContent")){
        $q->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfContent` TEXT NULL");
        if(!$q->ok){echo $tpl->js_error($q->mysql_error); @unlink($filepath);return;}
    }

    $q->QUERY_SQL("UPDATE itcharters SET enablepdf=1,PdfFileName='$filename',PdfFileSize='$size',PdfContent='$data' WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error); return;}
    echo "LoadAjax('itcharter-pdf-$ID','$page?itchart-pdf2=$ID')\n";
    echo "LoadAjax('itcharters-sessions-table','$page?table=yes');";


}

function itchart_pdf2()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $ID = intval($_GET["itchart-pdf2"]);
    $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");

    if (!$q->FIELD_EXISTS("itcharters", "enablepdf")) {
        $q->QUERY_SQL("ALTER TABLE `itcharters` ADD `enablepdf` INTEGER NOT NULL DEFAULT '0'");
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
        }
    }

    if (!$q->FIELD_EXISTS("itcharters", "PdfFileName")) {
        $q->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfFileName` TEXT NULL");
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
        }
    }

    if (!$q->FIELD_EXISTS("itcharters", "PdfFileSize")) {
        $q->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfFileSize` INTEGER NULL");
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
        }
    }
    if (!$q->FIELD_EXISTS("itcharters", "PdfContent")) {
        $q->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfContent` TEXT NULL");
        if (!$q->ok) {
            echo $q->mysql_error . "\n";
        }
    }


    $ligne = $q->mysqli_fetch_array("SELECT title,enablepdf,PdfFileSize,PdfFileName FROM itcharters WHERE ID='$ID'");
    $title = $ligne["title"];

    if (intval($ligne["PdfFileSize"]) > 0) {
        $title = "PDF {$ligne["PdfFileName"]} (" . FormatBytes(intval($ligne["PdfFileSize"]) / 1024) . ")";
    }

    $tpl->field_hidden("itchart-pdf", $ID);
    $form[] = $tpl->field_checkbox("enablepdf", "{enabled}", $ligne["enablepdf"]);
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
    if($ligne["PdfFileSize"]>0) {
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
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("UPDATE itcharters SET enablepdf='{$_POST["enablepdf"]}' WHERE ID=$ID");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}
}

function itchart_content_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["itchart-content"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $content=base64_encode($_POST["ChartContent"]);
    $q->QUERY_SQL("UPDATE itcharters SET ChartContent='$content' WHERE ID=$ID");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}
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

    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT TextIntro,TextButton,`title` FROM itcharters WHERE ID='$ID'");

        if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
        $title=$ligne["title"];
        $title_form=$title;
        $btname="{apply}";
        $jsAfter=$jsAfter."dialogInstance1.close();";

    }

    if($ligne["TextIntro"]==null){
        $ligne["TextIntro"]="Please read the IT chart before accessing trough Internet";
    }
    if($ligne["TextButton"]==null){
        $ligne["TextButton"]="I accept the terms and conditions of this agreement";

    }
    $tpl->field_hidden("itchart-settings",$ID);
    $form[]=$tpl->field_text("title","{page_title}",$title,true);
    $form[]=$tpl->field_text("TextButton","{text_button}",$ligne["TextButton"],true);
    $form[]=$tpl->field_textareacode("TextIntro","{introduction_text}",$ligne["TextIntro"]);

    echo $tpl->form_outside($title_form,$form,null ,$btname, $jsAfter,"AsSquidAdministrator",true);


}
function itchart_headers_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["itchart-headers"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $content=base64_encode($_POST["ChartHeaders"]);
    $q->QUERY_SQL("UPDATE itcharters SET ChartHeaders='$content' WHERE ID=$ID");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}
}

function itchart_settings_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ID=$_POST["itchart-settings"];


    if(!$q->FIELD_EXISTS("itcharters", "PdfContent")){
        $q->QUERY_SQL("ALTER TABLE `itcharters` ADD `PdfContent` TEXT NULL");
        if(!$q->ok){echo $q->mysql_error."\n";}
    }

    if(!$q->FIELD_EXISTS("itcharters", "enablepdf")){
        $q->QUERY_SQL("ALTER TABLE `itcharters` ADD `enablepdf` INTEGER NOT NULL DEFAULT '0'");
        if(!$q->ok){echo $q->mysql_error."\n";}
    }


    unset($_POST["itchart-settings"]);
    foreach ($_POST as $key=>$value){
        $fields[]="`$key`";
        $values[]="'".$q->sqlite_escape_string2($value)."'";
        $edit[]="`$key`='".$q->sqlite_escape_string2($value)."'";

    }

    if($ID>0){
        $sql="UPDATE itcharters SET ".@implode(",", $edit)." WHERE ID='$ID'";
    }else{
        $fields[]="`ChartHeaders`";
        $fields[]="`explain`";
        $values[]="''";
        $values[]="''";

        $sql="INSERT INTO itcharters (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
    }

    itchart_compile();


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}
}


function table(){
    $page=CurrentPageName();
    $t=time();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $add="Loadjs('$page?itchart-js=0');";
    $TRCLASS=null;
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

    $html[]="</div>";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{it_charters}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";




    $results=$redis->keys("*|*");
    $itchart_compile=itchart_compile();
    $td1prc=$tpl->table_td1prc();

    foreach ($results as $key){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $Time=$redis->get($key);
        $zdate=$tpl->time_to_date($Time,true);
        $zkey=explode("|",$key);
        $User=$zkey[0];
        $ChartID=$zkey[1];
        $title=$itchart_compile[$ChartID];
        $md=md5($key);
        $delete     = $tpl->icon_delete("Loadjs('$page?session-delete=". urlencode($key)."&md=$md')");

        $html[]="<tr class='$TRCLASS' id='$md'>";

        $html[]="<td style='font-weight:bold;width=1%' nowrap><i class=\"fas fa-user\"></i>&nbsp;$User</td>";
        $html[]="<td style='width:99%'>$title</td>";
        $html[]="<td $td1prc>$zdate</td>";
        $html[]="<td $td1prc>$delete</td>";
        $html[]="</tr>";

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
    $html[]="<script>";

    $items=0;
    $libmem=new lib_memcached();
    $keys = $libmem->allKeys();
    $regex = 'itchart_cache_.*';
    foreach($keys as $item) {
        if(preg_match('/'.$regex.'/', $item)) {
           $items++;
        }
    }

    $topbuttons[] = array("Loadjs('$page?reload-proxy=yes')", ico_refresh, "{empty_cache} ($items)");
    if($ClusterEnabled==0) {
        $topbuttons[] = array("Loadjs('$page?sessions-delete-all=yes');", ico_trash, "{delete_all}");
    }
    $TINY_ARRAY["TITLE"]="{it_charters} {sessions}";
    $TINY_ARRAY["ICO"]=ico_users;
    $TINY_ARRAY["EXPL"]="{IT_charter_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$headsjs;
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);


}

function itchart_compile(){
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT ID,title FROM itcharters ORDER BY ID DESC");

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $icharters[$ID]=$ligne["title"];

    }

   return $icharters;

}