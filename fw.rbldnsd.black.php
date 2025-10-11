<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.rbldnsd.tools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["delete-database"])){delete_database_confirm();exit;}
if(isset($_GET["delete-database"])){delete_database();exit;}
if(isset($_GET["rbldnsd-table-search"])){database_search_form();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["ipaddr-js"])){ipaddr_js();exit;}
if(isset($_GET["ipaddr-import-js"])){ipaddr_import_js();exit;}
if(isset($_GET["ipaddr-popup"])){ipaddr_popup();exit;}
if(isset($_POST["ipaddr"])){ipaddr_save();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["import"])){import_save();exit;}
if(isset($_GET["ipaddr-import-popup"])){ipaddr_import_popup();exit;}
if(isset($_GET["opts"])){opts_js();exit;}
if(isset($_GET["opts-popup"])){opts_popup();exit;}
if(isset($_GET["opts-list"])){opts_list();exit;}
if(isset($_GET["select-database"])){select_database();exit;}
if(isset($_GET["database-key"])){database_key_js();exit;}
if(isset($_GET["database-key-popup"])){database_key_popup();exit;}
if(isset($_GET["database-key-tab"])){database_key_tab();exit;}
if(isset($_POST["keytable"])){database_key_save();exit;}
if(isset($_GET["database-key-sources"])){database_key_source();exit;}
if(isset($_GET["remove-src-database"])){database_key_source_remove();exit;}
if(isset($_POST["remove-src-database"])){database_key_source_remove_perform();exit;}
if(isset($_GET["database-export"])){database_export_js();exit;}
if(isset($_GET["database-export-popup"])){database_export_popup();exit;}
if(isset($_GET["database-export-download"])){database_export_download();exit;}
if(isset($_GET["database-empty"])){database_empty_js();exit;}
if(isset($_POST["database-empty"])){database_empty_perform();exit;}
table();

function table(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    if (!isset($_GET["database"])) {
        $_GET["database"] = "1270002";
    }
    $html[] = "<div style='margin-top:10px' id='rbldnsd-table-search'>";
    $html[] = "</div>";
    $html[] = "<script>";
    $html[] = "LoadAjax('rbldnsd-table-search','$page?rbldnsd-table-search={$_GET["database"]}');";
    echo $tpl->_ENGINE_parse_body($html);
}
function database_empty_js():bool{
    $tpl = new template_admin();
    $tableid=$_GET["tableid"];
    $Array=GetTableArray($tableid);
    $TableName=$Array["tablename"];
    $function=$_GET["function"];
    $title=$Array["title"];
    return $tpl->js_confirm_empty("$tableid/$TableName/$title","database-empty",$tableid,"$function()");
}
function database_empty_perform():bool{
    $tableid=$_POST["database-empty"];
    $Array=GetTableArray($tableid);
    $TableName=$Array["tablename"];
    $title=$Array["title"];
    $q=new postgres_sql();
    $q->QUERY_SQL("TRUNCATE TABLE $TableName");
    if(!$q->ok){
        echo $q->mysql_error;
        writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    $sql="UPDATE rbl_tables SET items=0 WHERE tablename='$TableName'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        echo $q->mysql_error;
        return false;
    }
    writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
    $sql="UPDATE rbl_tables SET items=0 WHERE response='$tableid'";;
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        echo $q->mysql_error;
        return false;
    }
    writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
    return admin_tracks("Empty reputation table $TableName/$title");
}

function database_search_form():bool{

    $database=$_GET["rbldnsd-table-search"];
    $database=str_replace(".","",$database);
    $_SESSION["RBLDNSD"]["DB-CHOOSE"]=$database;
    $CurrentKeyID=TableIDTransform($database);
	$page=CurrentPageName();
	$tpl=new template_admin();
    $options["WRENCH"]="Loadjs('$page?opts=yes&function=%s')";



    $DBS=TranslateTables();
    if(isset($DBS[$CurrentKeyID])) {
        $options["DROPDOWN"]["TITLE"] = $DBS[$CurrentKeyID]["title"];
    }
    if(isset($DBS[$database])) {
        $options["DROPDOWN"]["TITLE"] = $DBS[$database]["title"];
    }
    foreach ($DBS as $key=>$ligne){

        if($CurrentKeyID==$key){
            continue;
        }
        $ipaddr=$ligne["response"];
        $name=$ligne["title"];


        $options["DROPDOWN"]["CONTENT"]["$name ($ipaddr)"]="LoadAjax('rbldnsd-table-search','$page?rbldnsd-table-search=$key');LoadAjax('opts-select-div','$page?opts-list=yes&function=%s');";
    }




	$html[]=$tpl->search_block($page,"","","","&tableid=$database",$options);
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}





function delete(){
	$ipaddr=$_GET["delete"];
	$q=new postgres_sql();
    $tablename=GetTableName();
	$q->QUERY_SQL("DELETE FROM $tablename WHERE ipaddr='$ipaddr'");
	echo "$('#{$_GET["md"]}').remove()\n";
}
function database_export_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $key=$_GET["database-export"];
    $function=$_GET["function"];
    if(strlen($key)==0){
        return false;
    }
    return $tpl->js_dialog1("{export} $key", "$page?database-export-popup=$key&function=$function",550);
}
function database_export_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $key=$_GET["database-export-popup"];
    $html[]="<div id='database-export-popup'></div>";

    $After="dialogInstance1.close();document.location.href='$page?database-export-download=$key';";

    $js=$tpl->framework_buildjs("/rbldnsd/export/$key",
        "rbldnsd.export","rbldnsd.export.log","database-export-popup",$After);
    $html[]="<script>$js</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function database_export_download():bool{
    $key=$_GET["database-export-download"];
    $fname=PROGRESS_DIR."/$key.gz";
    $fsize=@filesize($fname);
    $timestamp =filemtime($fname);
    $etag = md5($fname . $timestamp);


    $tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    header("Content-Length: ".$fsize);
    header('Content-type: application/x-gzip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$key.gz\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    header("ETag: \"{$etag}\"");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($fname);
    @unlink($fname);
    return true;
}


function database_key_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $key=$_GET["database-key"];
    $function=$_GET["function"];
    if(strlen($key)==0){
        $title="{new_database}";
        return $tpl->js_dialog($title, "$page?database-key-popup=$key&function=$function");
    }
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT description FROM rbl_tables WHERE response='$key'");
    return $tpl->js_dialog($ligne["description"], "$page?database-key-tab=$key&function=$function");
}
function database_key_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $key=$_GET["database-key-tab"];
    $function=$_GET["function"];
    $array["{database_options}"]="$page?database-key-popup=$key&function=$function";
    $array["{sources} ({status})"]="$page?database-key-sources=$key&function=$function";
    echo $tpl->tabs_default($array);

}

function database_key_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $key=$_GET["database-key-popup"];
    $title="";
    $btn="{apply}";
    if(strlen($key)==0){
        $DBS=TranslateTables();
        for($i=1;$i<255;$i++){
            if($i==2){
                continue;
            }
            if($i==9){
                continue;
            }
            $pref="127000";
            if($i>9){
                $pref="12700";
            }
            if($i>100){
                $pref="1270";
            }
            if(isset($DBS[$pref])){
                continue;
            }
            $btn="{create}";
            $key="$pref$i";
            $AIVABLE[$key]="127.0.0.$i";
        }
        $title="{create_database}";
        $form[]=$tpl->field_array_hash($AIVABLE,"keytable","{response}");
        $description="New Database";
        $explain="";
        $enabled=1;
        $ttl=30;
    }else{
        $q=new postgres_sql();
        $ligne=$q->mysqli_fetch_array("SELECT * FROM rbl_tables WHERE response='$key'");
        $description=$ligne["description"];
        $tablename=$ligne["tablename"];
        $enabled=$ligne["enabled"];
        $ttl=$ligne["ttl"];
        $form[]=$tpl->field_hidden("keytable",$key);
        $TranslateTables=TranslateTables();
        $tableid=$_SESSION["RBLDNSD"]["DB-CHOOSE"];
        $response=$TranslateTables[$tableid]["response"];
        $explain=$tpl->_ENGINE_parse_body("{dnsblanswerwith}");
        $explain=str_replace("%s",$response,$explain);

    }
    $jsafter="BootstrapDialog1.close();{$_GET["function"]}()";
    $form[]=$tpl->field_text("description","{database_name}",$description);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled);
    $form[]=$tpl->field_numeric("ttl","{record_lifetime} ({days})",$ttl);
    $BLOCK=false;
    if($key=="1270002"){
        $BLOCK=true;
    }
    if($key=="1270009"){
        $BLOCK=true;
    }
    echo $tpl->form_outside($title,$form,$explain,$btn,$jsafter,null,false,$BLOCK);
    return true;


}
function database_key_source_remove(){
    $q=new postgres_sql();
    $tpl=new template_admin();
    $srcid=intval($_GET["remove-src-database"]);
    $tableid=$_GET["db-key"];
    $md=$_GET["md"];

    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT description FROM rbl_sources WHERE id='$srcid'"));
    $Title=$ligne["description"];

    $DBS=TranslateTables();
    $database=$DBS[$tableid]["tablename"];

    $text="{remove} {records} {from} $Title {database} $database";
    return $tpl->js_confirm_delete($text,"remove-src-database","$srcid-$tableid","$('#$md').remove()");
}
function database_key_source_remove_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("-",$_POST["remove-src-database"]);
    $srcid=intval($tb[0]);
    $tableid=trim($tb[1]);
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/rbldnsd/removedata/$srcid/$tableid"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }

    return admin_tracks("Removed reputation source $srcid from database $tableid");
}

function database_key_source():bool{
    $page=CurrentPageName();
    $key=$_GET["database-key-sources"];
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT stats FROM rbl_tables WHERE response='$key'");
    $data= unserializeb64($ligne["stats"]);
    $tpl=new template_admin();
    $t=time();
    $TRCLASS=null;
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{source}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{records}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new postgres_sql();

    foreach ($data as $srcid=>$CountRows){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize("$srcid$key"));
        $ico="<i class='".ico_link."'></i>&nbsp;";

        if($srcid==0){
            $Title="{manual} / API";
        }else{
            $ligne=pg_fetch_array($q->QUERY_SQL("SELECT description FROM rbl_sources WHERE id='$srcid'"));
            $Title=$ligne["description"];
            $Title=wordwrap($Title,90,"<br>");
        }

        $delete=$tpl->icon_delete("Loadjs('$page?remove-src-database=$srcid&db-key=$key&md=$md')");

        $CountRows=$tpl->FormatNumber($CountRows);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap>$ico{$Title}</td>";
        $html[]="<td style='width:1%' nowrap>$CountRows</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$delete</td>";
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

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
return true;
}
function database_key_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $key=$_POST["keytable"];
    $q=new postgres_sql();
    $description=pg_escape_string2($_POST["description"]);
    $enabled=intval($_POST["enabled"]);
    $ttl=$_POST["ttl"];
    $OldDB=$_SESSION["RBLDNSD"]["DB-CHOOSE"];
    $sqlCrteate="";
    $ligne=$q->mysqli_fetch_array("SELECT response FROM rbl_tables WHERE response='$key'");
    if(strlen($ligne["response"])<3){
        $tablename="rbl_$key";
        $sql="INSERT INTO rbl_tables (response,description,tablename,items,enabled,ttl,zDate) 
            VALUES ('$key','$description','$tablename',0,$enabled,$ttl,NOW())";
        $sqlCrteate="CREATE TABLE IF NOT EXISTS $tablename (
			  ipaddr inet PRIMARY KEY,
			  description varchar(255)  NOT NULL,
			  srcid INT NOT NULL DEFAULT 0,
			  zDate timestamp DEFAULT current_timestamp)";

        $_SESSION["RBLDNSD"]["DB-CHOOSE"]=$key;

    }else{
        $sql="UPDATE rbl_tables SET description='$description',enabled=$enabled,ttl=$ttl WHERE response='$key'";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        $_SESSION["RBLDNSD"]["DB-CHOOSE"]=$OldDB;
        return false;
    }
    if(strlen($sqlCrteate)>3){
        $q->QUERY_SQL($sqlCrteate);
        if(!$q->ok){
            $_SESSION["RBLDNSD"]["DB-CHOOSE"]=$OldDB;
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
    }


    return admin_tracks("Add/edit reputation database $key/$tablename - $description");

}


function opts_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{options}", "$page?opts-popup=yes&function={$_GET["function"]}");

}
function delete_database():bool{
    $tpl=new template_admin();
    $tableid=str_replace(".","",$_GET["delete-database"]);
    $Array=GetTableArray($tableid);
    if(!isset($Array["tablename"])){
        $tpl->js_error("No table id found");
        return false;
    }
    $tableName=$Array["tablename"];
    $tableTitle=$Array["title"];
    $md=$_GET["md"];

    return $tpl->js_confirm_delete("$tableTitle ($tableName)","delete-database",$tableid,"$('#$md').remove();");
}
function delete_database_confirm():bool{
    $tableid=$_POST["delete-database"];
    $Array=GetTableArray($tableid);
    if(!isset($Array["tablename"])){
        echo "No table id found";
        return false;
    }
    $tableName=$Array["tablename"];
    $tableTitle=$Array["title"];
    $q=new postgres_sql();
    $q->QUERY_SQL("DROP TABLE IF EXISTS $tableName");
    if(!$q->ok){
        echo $q->mysql_error;
        return false;
    }
    $q->QUERY_SQL("DELETE FROM rbl_tables WHERE tablename='$tableName'");
    if(!$q->ok){
        echo $q->mysql_error;
        return false;
    }
    return admin_tracks("Delete IP reputation database $tableName ($tableTitle) with id $tableid");
}

function select_database():bool{
    $key=$_GET["select-database"];
    $_SESSION["RBLDNSD"]["DB-CHOOSE"]=$key;
    $function=$_GET["function"];
    echo "$function();";
    return true;
}
function opts_popup():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    echo "<div id='opts-select-div'></div>
    <script>LoadAjax('opts-select-div','$page?opts-list=yes&function=$function')</script>";
    return true;
}
function opts_list():bool{
    $Choosen=$_SESSION["RBLDNSD"]["DB-CHOOSE"];
    $tpl=new template_admin();
    $page=CurrentPageName();

    $function=$_GET["function"];
    $t=time();
    $TRCLASS=null;
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{response}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{select}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $DBS=TranslateTables();
    foreach ($DBS as $key=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $description="";
        $ipaddr=$ligne["response"];
        $class_text="";
        $name=$ligne["title"];
        $items=$tpl->FormatNumber($ligne["items"]);
        if(isset($ligne["description"])) {
            $description = $ligne["description"];
        }

        $bt_color="btn-primary";


        $button_select=$tpl->button_autnonome("&nbsp;{select}",
            "LoadAjax('rbldnsd-table-search','$page?rbldnsd-table-search=$ipaddr');LoadAjax('opts-select-div','$page?opts-list=yes&function=$function');BootstrapDialog1.close();",
            "fas fa-hand-pointer","",0,$bt_color,"small");

        $delete=$tpl->icon_delete("Loadjs('$page?delete-database=$ipaddr&md=$md')");

        if($key==$Choosen){
            $button_select="&nbsp;";
        }
        if(isset($ligne["noremove"])){
            $delete=$tpl->icon_delete("");
        }
        $description_text="";
        if(!is_null($description) ){
            if (strlen($description) > 1) {
                $description_text = "<div><i><span class='$class_text'>$description</span></i></div>";
            }
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$ipaddr</td>";
        $html[]="<td style='width:99%' nowrap>$name$description_text</td>";
        $html[]="<td style='width:1%' nowrap>$items</td>";
        $html[]="<td style='width:1%' nowrap>$button_select</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$delete</td>";
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

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function ipaddr_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ipaddr=$_GET["ipaddr-js"];
    $tableid=$_GET["tableid"];
    $Array=GetTableArray($tableid);
    $response=$Array["response"];
    $tableTitle=$Array["title"];
    $tableName=$Array["tablename"];
    if(strlen($tableName)<4){
        return $tpl->js_error("No table from $tableid");
    }


	if($ipaddr==null){$title="$tableTitle {new_entry} ($response)";}else{$title=$ipaddr;}
	return $tpl->js_dialog1($title, "$page?ipaddr-popup=$ipaddr&function={$_GET["function"]}&tableid=$tableid",650);
}

function ipaddr_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ipaddr=$_GET["ipaddr-popup"];
	$uid=$_SESSION["uid"];
    $tableid=$_GET["tableid"];
	if($uid==-100){$uid="Manager";}
    $Array=GetTableArray($tableid);
    $tableName=$Array["tablename"];
	$jsafter="BootstrapDialog1.close();{$_GET["function"]}()";
	if($ipaddr==null){
		$bt="{add}";
		$title="{new_address}";
        $form[]=$tpl->field_hidden("tableid", $tableid);
		$form[]=$tpl->field_ipaddr("ipaddr", "{address}", null,true);
		$description="Added $uid - ".date("Y-m-d H:i:s");
	}else{
		$bt="{apply}";

        $form[]=$tpl->field_hidden("ipaddr", $ipaddr);
		$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM $tableName WHERE ipaddr='$ipaddr'"));
		$description=$ligne["description"];
		$title=$ipaddr." - {$ligne["zdate"]}";
	}
	$form[]=$tpl->field_text("description", "{description}", $description);
	echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
    return true;
}

function ipaddr_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if($_POST["description"]==null){$_POST["description"]=gethostbyaddr($_POST["ipaddr"]);}else{
        $_POST["description"]=gethostbyaddr($_POST["ipaddr"])." ".$_POST["description"];
    }

	$ipaddr=$_POST["ipaddr"];
    $tableid=$_POST["tableid"];
    $Array=GetTableArray($tableid);
    $tableName=$Array["tablename"];
    if(strlen($tableName)<4){
        return $tpl->post_error("No table from $tableid");
    }
    $tableTitle=$Array["title"];
	$description=$_POST["description"];
	$date=date("Y-m-d H:i:s");
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM $tableName WHERE ipaddr='$ipaddr'");
	$q->QUERY_SQL("INSERT INTO $tableName (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");
	if(!$q->ok){echo $q->mysql_error . "<br>$ipaddr -> $description";return false;}
	return admin_tracks("Add item $ipaddr into reputation table $tableName ($tableTitle)");
	
}
function import_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("\n",$_POST["import"]);
    $session=$_POST["session"];
    $f=array();
    foreach($tb as $item) {
        $item=trim($item);
        if(strlen($item)<3){continue;}
        if(!preg_match("#^([0-9\.]+)$#",$item)){continue;}
        $f[]=$item;

    }
    if(count($f)<2){
        echo $tpl->post_error("Not enough data to import {$_POST["import"]}");
        return false;
    }


    @file_put_contents(PROGRESS_DIR."/$session.data",@implode("\n",$f));
    return true;
}



function ipaddr_import_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $title="{import}";

    $TranslateTables=TranslateTables();
    $tableid=$_SESSION["RBLDNSD"]["DB-CHOOSE"];
    $response=$TranslateTables[$tableid]["response"];
    $tableTitle=$TranslateTables[$tableid]["title"];

   return  $tpl->js_dialog("$tableTitle: $title ($response)", "$page?ipaddr-import-popup=yes&function={$_GET["function"]}");
}
function ipaddr_import_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="Manager";}
    $jsafter="BootstrapDialog1.close();{$_GET["function"]}()";
    $dbkey=$_SESSION["RBLDNSD"]["DB-CHOOSE"];
    $session=time();
    $progress=$tpl->framework_buildjs(
        "/rbldnsd/importdata/$session/$dbkey",
        "$session.progress","$session.log","progress-rbldnsd-restart",$jsafter);


    $bt="{add}";
    $title="{new_address}";
    $form[]=$tpl->field_hidden("session",$session);
    $form[]=$tpl->field_textareacode("import","{address}", null);
    $html[]="<div id='progress-$session'></div>";
    $html[]= $tpl->form_outside($title, $form,null,$bt,$progress,"AsDnsAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function search(){
    $tableid=null;
    if(isset($_GET["tableid"])){
        $_SESSION["RBLDNSD"]["DB-CHOOSE"]=$_GET["tableid"];
        $tableid=$_GET["tableid"];
    }else{
        if(!isset($_SESSION["RBLDNSD"]["DB-CHOOSE"])){
            $_SESSION["RBLDNSD"]["DB-CHOOSE"]="1270002";
        }
        $tableid=$_SESSION["RBLDNSD"]["DB-CHOOSE"];
    }


    $function=$_GET["function"];
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new postgres_sql();
	$t=time();

    $sql="SELECT ID,description FROM rbl_sources ORDER BY ID ASC";
    $results = $q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $RULES[$ligne["id"]]=$ligne["description"];
    }
    $dbkey=$_SESSION["RBLDNSD"]["DB-CHOOSE"];
    $jsRestart=$tpl->framework_buildjs("/rbldnsd/compile",
        "rbldnsd.compile.progress",
        "rbldnsd.compile.progress.log",
        "progress-rbldnsd-restart");

    $topbuttons[] = array("Loadjs('$page?ipaddr-js=&function=$function&tableid=$tableid');", ico_plus, "{new_address}");
    $topbuttons[] = array("Loadjs('$page?ipaddr-import-js=yes&function=$function&tableid=$tableid');", ico_import, "{import}");
    $topbuttons[]= array("Loadjs('$page?database-key=&function=$function')",ico_plus,"{new_database}");
    $topbuttons[]= array("Loadjs('$page?database-key=$dbkey&function=$function&tableid=$tableid')",ico_params,"{database_options}");
    $topbuttons[]=array("Loadjs('$page?database-export=$dbkey&function=$function&tableid=$tableid')",ico_export,"{export}");
    $topbuttons[]=array("Loadjs('$page?database-empty=$dbkey&function=$function&tableid=$tableid')",ico_trash
    ,"{empty}");

    $topbuttons[]= array("Loadjs('$page?opts=yes&function=$function')",ico_database,"{select_database}");
    $topbuttons[] = array($jsRestart, ico_run, "{compile_rules}");

    $tableName=GetTableName($tableid);
	$search=$_GET["search"];
	$aliases["ipaddr"]="ipaddr";
	$querys=$tpl->query_pattern($search,$aliases);
	$MAX=$querys["MAX"];
	if($MAX==0){$MAX=150;}
    if(strlen($tableName)<3){
        echo $tpl->div_error("No database selected for $tableid");
        return false;
    }

	$sql="SELECT * FROM $tableName {$querys["Q"]} ORDER BY zdate DESC LIMIT $MAX";
	
	if(preg_match("#^([0-9\.]+)#", $search)){
		$sql="SELECT * FROM $tableName WHERE ipaddr='$search' ORDER BY zdate DESC LIMIT $MAX";
	}
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return false;}
	

	$TRCLASS=null;
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$ipaddr=$ligne["ipaddr"];
		$zDate=strtotime($ligne["zdate"]);
		$time=$tpl->time_to_date($zDate,true);
		$class_text=null;
		$description=$ligne["description"];
        $srcid=intval($ligne["srcid"]);
        if($srcid>0){
            if(isset($RULES[$srcid])){
                $description=$description."<br><i>$RULES[$srcid]</i>";
            }
        }

		$ipaddr=$tpl->td_href($ipaddr,null,"Loadjs('$page?ipaddr-js=$ipaddr&function={$_GET["function"]}&tableid=$tableid');");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>$time</td>";
		$html[]="<td style='width:1%' nowrap><span class='$class_text'>$ipaddr</span></td>";
		$html[]="<td style='width:99%' nowrap><span class='$class_text'>$description</span></td>";
		$html[]="<td style='width:1%' class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete={$ligne["ipaddr"]}&md=$md')")."</center></td>";
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
    $dnsblanswerwith=$tpl->_ENGINE_parse_body("{dnsblanswerwith}");

    $TableInfo=GetTableArray($tableid);
    $response=$TableInfo["response"];
    $tableTitle=$TableInfo["title"];
    $APP_RBLDNSD_BLACKRECORDS=$tpl->FormatNumber($TableInfo["items"]);

    $dnsblanswerwith=str_replace("%s",$response,$dnsblanswerwith);
    $TINY_ARRAY["TITLE"]="$tableTitle $APP_RBLDNSD_BLACKRECORDS {records}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{APP_RBLDNSD_EXPLAIN}<br>$dnsblanswerwith";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
