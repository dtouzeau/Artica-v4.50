<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");


    if(isset($_GET["table"])){table();exit;}
    if(isset($_GET["itchart-ad"])){itchart_ad_start();exit;}
    if(isset($_GET["itchart-ad-table"])){itchart_ad_table();exit;}
    if(isset($_GET["itchart-ad-link"])){itchard_ad_link();exit;}
    if(isset($_GET["itchart-ad-unlink"])){itchard_ad_unlink();exit;}
    if(isset($_GET["itchart-adsave"])){itchard_ad_save();exit;}

    if(isset($_GET["itchart-delete"])){itchart_delete();exit;}
    if(isset($_POST["itchart-delete"])){itchart_delete_confirm();exit;}
    if(isset($_GET["itchart-enable"])){itchart_enable();exit;}

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


function xStart(){
    $page=CurrentPageName();
    echo "<div id='itcharters-table' style='margin-top: 20px'></div>";
    echo "<script>LoadAjax('itcharters-table','$page?table=yes');</script>";

}
function itchart_enable():bool{
    $ID     = intval($_GET["itchart-enable"]);
    $q      = new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $tpl    = new template_admin();
    $ligne  = $q->mysqli_fetch_array("SELECT enabled FROM itcharters WHERE ID=$ID");
    //writelogs("itcharters $ID enabled={$ligne["enabled"]}",__FUNCTION__,__FILE__,__LINE__);
    if(intval($ligne["enabled"])==1){$enabled=0;}else{$enabled=1;}
    $q->QUERY_SQL("UPDATE itcharters SET enabled='$enabled' WHERE ID='$ID'");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return false;}
    itchart_compile();
    return admin_tracks("Enabled ITChart $ID");

}

function itchard_ad_unlink(){
    $tpl=new template_admin();
    $dn=$_GET["itchart-ad-unlink"];
    $ID=intval($_GET["id"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne  = $q->mysqli_fetch_array("SELECT title,Params FROM itcharters WHERE ID='$ID'");
    $title=$ligne["title"];
    $Params_encoded=base64_decode($ligne["Params"]);
    $Params = unserialize($Params_encoded);
    unset($Params["ACTIVEDIRECTORY"][$dn]);
    $dn_free=base64_decode($dn);
    $Params_encoded=base64_encode(serialize($Params));
    $q->QUERY_SQL("UPDATE itcharters SET Params='$Params_encoded' WHERE ID=$ID");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    if(!itchart_ad_save()){return false;}
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();";
    return admin_tracks("Remove $dn_free from ITCharter $title");
}

function itchard_ad_save():bool{
    $page=CurrentPageName();
    if(!itchart_ad_save()){return false;}
    $ID=intval($_GET["itchart-adsave"]);
    header("content-type: application/x-javascript");
    echo "LoadAjax('itchart-ad-div','$page?itchart-ad-table=$ID');";
    return true;
}

function itchart_ad_save(){

    $ClusterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterEnabled"));
    $ClusterMaster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterMaster"));
    $tpl=new template_admin();

    $redis_server='127.0.0.1';
    $redis_port=6123;
    if($ClusterEnabled==1){
        if(strpos($ClusterMaster,":")>0){
            $ff=explode(":",$ClusterMaster);
            $redis_server=$ff[0];
            $redis_port=$ff[1];
        }else{$redis_server=$ClusterMaster;}
    }

    $redis=new Redis();
    try {
        $redis->connect($redis_server,$redis_port);
    } catch (Exception $e) {
        echo $tpl->js_mysql_alert($e->getMessage());
        return false;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT ID,Params FROM itcharters";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $key="itchart.activedirtectory.$ID";
        $redis->del($key);

        $Params_encoded=base64_decode($ligne["Params"]);
        $Params = unserialize($Params_encoded);
        if(!isset($Params["ACTIVEDIRECTORY"])){continue;}
        if(count($Params["ACTIVEDIRECTORY"])==0){continue;}
        $xdns=array();
        foreach ($Params["ACTIVEDIRECTORY"] as $dn=>$none){
            $xdns[]=base64_decode($dn);
        }
        if(count($xdns)==0){continue;}
        $val=@implode("|||",$xdns);
        if(!$redis->set($key,$val)){
            echo $tpl->js_mysql_alert("Save Entry: ".$redis->getLastError());
            return false;
        }
        writelogs("Saving $key = $val in $redis_server:$redis_port",__FUNCTION__,__FILE__,__LINE__);
    }

    return true;



}

function itchard_ad_link(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["itchart-ad-link"]);
    if($ID==0){
        $tpl->js_mysql_alert("ID == 0");
        return false;
    }
    $dn=$_GET["dn"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne  = $q->mysqli_fetch_array("SELECT title,Params FROM itcharters WHERE ID='$ID'");
    $title=$ligne["title"];
    $Params_encoded=base64_decode($ligne["Params"]);
    $Params = unserialize($Params_encoded);
    $Params["ACTIVEDIRECTORY"][$dn]=1;
    $dn_free=base64_decode($dn);


    $Params_encoded=base64_encode(serialize($Params));
    $q->QUERY_SQL("UPDATE itcharters SET Params='$Params_encoded' WHERE ID=$ID");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    admin_tracks("Add $dn_free to ITCharter $title");
    if(!itchart_ad_save()){return false;}
    header("content-type: application/x-javascript");
    echo "LoadAjax('itchart-ad-div','$page?itchart-ad-table=$ID');";
    return true;

}

function itchart_delete(){
    $tpl=new template_admin();
    $ID=intval($_GET["itchart-delete"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT title FROM itcharters WHERE ID=$ID");
    $title=$ligne["title"];
    $tpl->js_confirm_delete($title,"itchart-delete",$ID,"$('#$md').remove();");

}

function itchart_ad_start(){
    $ID=intval($_GET["itchart-ad"]);
    $page=CurrentPageName();
    echo "<div id='itchart-ad-div'><script>LoadAjax('itchart-ad-div','$page?itchart-ad-table=$ID')</script>";
}
function itchart_ad_table(){
    $ID=intval($_GET["itchart-ad-table"]);
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    if ($EnableExternalACLADAgent==1){
        $add="Loadjs('fw.BrowseActiveDirectoryGroups.ad.agent.php?UseDN=1&ReturnBack=ItChartADGrp$t');";
    }
    else {
        $add="Loadjs('fw.BrowseActiveDirectoryGroups.php?UseDN=1&ReturnBack=ItChartADGrp$t');";
    }

    $TRCLASS=null;

    $ARRAY=array();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ichart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/itchart.log";
    $ARRAY["CMD"]="/itcharter/reconfigure";
    $ARRAY["TITLE"]="{compile2}";
    $ARRAY["AFTER"]="LoadAjax('itcharters-table','$page?table=yes');";
    $jsRestart="Loadjs('$page?itchart-adsave=$ID')";



    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom:10px;margin-top:10px'>
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {link_ad_group} </label>
        <label class=\"btn btn btn-info\" OnClick=\"$jsRestart\"><i class='fa fa-save'></i> {apply} </label>
     </div>";

    $th_center="data-sortable=true class='text-capitalize center' data-type='text' width='1%'";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{ad_groups}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne  = $q->mysqli_fetch_array("SELECT title,Params FROM itcharters WHERE ID='$ID'");
    $title=$ligne["title"];
    $Params_encoded=base64_decode($ligne["Params"]);
    $Params = unserialize($Params_encoded);
    $results=$Params["ACTIVEDIRECTORY"];

    foreach ($results as $dn=>$none){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md         = md5(serialize($dn));
        $td1prc     = $tpl->table_td1prc();
        $delete     = $tpl->icon_delete("Loadjs('$page?itchart-ad-unlink=$dn&id=$ID&md=$md')");
        $dn=base64_decode($dn);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='99%'><i class='far fa-users'></i>&nbsp;$dn</span></td>";
        $html[]="<td $td1prc>$delete</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";


    $html[]="function ItChartADGrp$t(dn){";
    $html[]="if(dn.length == 0 ){alert('no dn sent');return false;}";
    $html[]="Loadjs('$page?itchart-ad-link=$ID&dn='+dn)";
    $html[]="}";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}


function itchart_delete_confirm(){
    $ID=intval($_POST["itchart-delete"]);
    $itchart_name=itchart_name($ID);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM itcharters WHERE ID=$ID");

    $ClusterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterEnabled"));
    $ClusterMaster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterMaster"));

    $redis_server='127.0.0.1';
    $redis_port=6123;
    if($ClusterEnabled==1){
        if(strpos($ClusterMaster,":")>0){
            $ff=explode(":",$ClusterMaster);
            $redis_server=$ff[0];
            $redis_port=$ff[1];
        }else{$redis_server=$ClusterMaster;}
    }

    $redis=new Redis();
    try {
        $redis->connect($redis_server,$redis_port);
    } catch (Exception $e) {
        echo $tpl->js_mysql_alert($e->getMessage());
        return false;
    }

    $results=$redis->keys("*|$ID");
    foreach ($results as $key){
        $redis->del($key);
    }

    admin_tracks("Removed ITChart $itchart_name");

    itchart_compile();
}

function itchart_name($ID):string{
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT title FROM itcharters WHERE ID=$ID");
   return $ligne["title"];
}

function itchart_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["itchart-js"]);
    $title="{new_itchart}";
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT title FROM itcharters WHERE ID=$ID");
        $title=$ligne["title"];
    }

    return $tpl->js_dialog1($title,"$page?itchart-tabs=$ID",990);

}
function itchart_content(){

    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
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
    $tpl->CLUSTER_CLI=true;
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
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $size=@filesize($filepath);
    $data=base64_encode(@file_get_contents($filepath));
    @unlink($filepath);



    $q->QUERY_SQL("UPDATE itcharters SET enablepdf=1,PdfFileName='$filename',PdfFileSize='$size',PdfContent='$data' WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error); return false;}
    echo "LoadAjax('itcharter-pdf-$ID','$page?itchart-pdf2=$ID')\n";
    echo "LoadAjax('itcharters-table','$page?table=yes');";
    return admin_tracks("Uploaded $filename PDF for ITChart $ID");
}

function itchart_pdf2():bool{
    $tpl = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page = CurrentPageName();
    $ID = intval($_GET["itchart-pdf2"]);
    $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");



    $ligne = $q->mysqli_fetch_array("SELECT title,enablepdf,PdfFileSize,PdfFileName,pdfwidth,pdfheight FROM itcharters WHERE ID=$ID");
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }
    $title = $ligne["title"];

    if (intval($ligne["PdfFileSize"]) > 0) {
        $title = "PDF {$ligne["PdfFileName"]} (" . FormatBytes(intval($ligne["PdfFileSize"]) / 1024) . ")";
    }
    $pdfwidth=intval($ligne["pdfwidth"]);
    $pdfheight=intval($ligne["pdfheight"]);
    if($pdfwidth==0){$pdfwidth=800;}
    if($pdfheight==0){$pdfheight=600;}

    $tpl->field_hidden("itchart-pdf", $ID);
    $form[] = $tpl->field_checkbox("enablepdf", "{enabled}", $ligne["enablepdf"]);
    $form[] = $tpl->field_numeric("pdfwidth", "{width}", $pdfwidth);
    $form[] = $tpl->field_numeric("pdfheight", "{height}", $pdfheight);
    $tpl->form_add_button_upload("{upload}:PDF", $page, "AsSquidAdministrator");

    $_SESSION["PDF-ID"] = $ID;

    $html[] = "<table style='width:100%'>";
    $html[] = "<tr>";
    $html[] = "<td style='vertical-align:top;width:255px'>";
    $html[] = "<canvas id=\"the-canvas\" style='height: 400px;width:300px;border:1px solid #CCCCCC;'></canvas>";
    $html[]="</td>";
    $html[] = "<td style='vertical-align:top;width:90%;padding-left:10px'>";
    $html[] = $tpl->form_outside($title, $form, "{charter_pdf_explain}", "{apply}", "LoadAjax('itcharters-table','$page?table=yes');", "AsSquidAdministrator", true);
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
    return true;
}

function itchart_pdf_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["itchart-pdf"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $enablepdf=intval($_POST["enablepdf"]);
    $pdfwidth=intval($_POST["pdfwidth"]);
    $pdfheight=intval($_POST["pdfheight"]);

    $q->QUERY_SQL("UPDATE itcharters SET enablepdf=$enablepdf,pdfwidth=$pdfwidth,pdfheight=$pdfheight WHERE ID=$ID");
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
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    $array["{parameters}"]="$page?itchart-settings=$ID";
    if($ID>0){
        $array["{content}"]="$page?itchart-content=$ID";
        $array["{headers}"]="$page?itchart-headers=$ID";
        $array["PDF"]="$page?itchart-pdf=$ID";
        if($EnableActiveDirectoryFeature==1){
            $array["Active Directory"]="$page?itchart-ad=$ID";
        }
    }


    echo $tpl->tabs_default($array);
}

function itchart_settings():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["itchart-settings"]);
    $title="Acceptable Use Policy";
    $title_form="{new_itchart}";
    $btname="{add}";
    $t=time();
    $jsAfter="LoadAjax('itcharters-table','$page?table=yes');";
    if($ID==0){
        $jsAfter=$jsAfter."dialogInstance1.close();";
    }
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT TextIntro,TextButton,`title` FROM itcharters WHERE ID='$ID'");

        if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
        $title=$ligne["title"];
        $title_form=$title;
        $btname="{apply}";
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
    return true;

}
function itchart_headers_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["itchart-headers"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $content=base64_encode($_POST["ChartHeaders"]);
    $q->QUERY_SQL("UPDATE itcharters SET ChartHeaders='$content' WHERE ID=$ID");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
    return admin_tracks("Save IT Chart Headers #$ID");
}

function itchart_settings_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ID=$_POST["itchart-settings"];

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
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
        itchart_compile();
        return admin_tracks_post("Create a new IT Chart");
    }


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
    itchart_compile();
    return admin_tracks_post("Save IT Chart #$ID parameters");
}


function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $add="Loadjs('$page?itchart-js=0');";
    $TRCLASS=null;

    $ARRAY=array();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ichart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/itchart.log";
    $ARRAY["CMD"]="/itcharter/reconfigure";
    $ARRAY["TITLE"]="{compile2}";
    $ARRAY["AFTER"]="LoadAjax('itcharters-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-itcharter-restart')";





    $th_center="data-sortable=true class='text-capitalize center' data-type='text' width='1%'";
    $html[]="<table id='table-webfilter-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >id</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{it_charters}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>PDF</th>";
    $html[]="<th data-sortable=false>{enabled}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    itchart_compile();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT ID,enabled,title,TextIntro,TextButton,enablepdf FROM itcharters ORDER BY ID DESC");
    if(!$q->ok){$tpl->div_error($q->mysql_error);}

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $title=$ligne["title"];
        $enablepdf=intval($ligne["enablepdf"]);
        $PDF=null;
        $TextIntro=trim($ligne["TextIntro"]);
        $js="Loadjs('$page?itchart-js=$ID');";
        $color=null;
        $text_class=null;
        if($enablepdf==1){$PDF="<i class='fas fa-check'></i>";}
        $md         = md5(serialize($ligne));
        $td1prc     = $tpl->table_td1prc($text_class);
        $td_enable  = $tpl->icon_check($ligne["enabled"],"Loadjs('$page?itchart-enable=$ID}')","AsProxyMonitor");
        $delete     = $tpl->icon_delete("Loadjs('$page?itchart-delete=$ID&md=$md')");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $td1prc><span class='$text_class'>{$ligne["ID"]}</span></td>";
        $html[]="<td><span class='$text_class' style='font-weight:bold;$color'>".$tpl->td_href("<span id='fw-$ID-rname' class='text-success'>$title</span>",null,$js)."<br>$TextIntro</span></td>";
        $html[]="<td $td1prc>$PDF</td>";
        $html[]="<td $td1prc>$td_enable</td>";
        $html[]="<td $td1prc>$delete</td>";
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




    $topbuttons[] = array($add, ico_plus, "{new_itchart}");
    $topbuttons[] = array($jsRestart, ico_save, "{apply}");
    $TINY_ARRAY["TITLE"]="{it_charters} {rules}";
    $TINY_ARRAY["ICO"]="fa fa-file-signature";
    $TINY_ARRAY["EXPL"]="{IT_charter_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]=$headsjs;
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-webfilter-rules').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);


}

function itchart_compile(){
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT ID FROM itcharters WHERE enabled=1 ORDER BY ID DESC");
    $icharters=array();
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $icharters[$ID]=true;

    }
       //$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ITChartArrayTest",base64_encode(serialize($icharters)));

    $ClusterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterEnabled"));
    $ClusterMaster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartClusterMaster"));

    $redis_server='127.0.0.1';
    $redis_port=6123;
    if($ClusterEnabled==1){
        if(strpos($ClusterMaster,":")>0){
            $ff=explode(":",$ClusterMaster);
            $redis_server=$ff[0];
            $redis_port=$ff[1];
        }else{$redis_server=$ClusterMaster;}
    }

    $redis=new Redis();
    try {
        $redis->connect($redis_server,$redis_port);
    } catch (Exception $e) {
        echo $tpl->js_mysql_alert($e->getMessage());
        return false;
    }

    try {
        $redis->set("itcharts.ids", base64_encode(serialize($icharters)));
    } catch (Exception $e) {}

    $redis->close();

}