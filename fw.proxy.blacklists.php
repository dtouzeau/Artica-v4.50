<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsDansGuardianAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){main();exit;}
if(isset($_POST["deny_websites"])){save();exit;}
if(isset($_GET["add-js"])){add_js();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["export"])){export();exit;}
if(isset($_GET["download-exported"])){export_download();exit;}
if(isset($_GET["truncate"])){truncate_js();exit;}
if(isset($_POST["truncate"])){truncate_perform();exit;}
page();

function add_js(){
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("modal:{deny_websites}","$page?add-popup=yes&function=$function");

}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{deny_websites}","fa fa-ban","{warning_deny_for_all_users}",
        "$page?start=yes","proxy-blacklist","proxy-blacklist-progress",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);
}
function truncate_js():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $count=$tpl->FormatNumber($q->COUNT_ROWS("deny_websites"));
    return $tpl->js_confirm_empty("{deny_websites}","truncate",$count,"$function()");
}
function truncate_perform():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM deny_websites");
    return admin_tracks("Database Deny websites was cleaned and back to 0 record.");
}
function export():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsrestart=$tpl->framework_buildjs(
        "squid2.php?export-blacklists=yes",
        "squid.export.progress","squid.export.txt",
        "proxy-blacklist-progress",
        "document.location.href='$page?download-exported=yes'"

    );
    header("content-type: application/x-javascript");
    echo $jsrestart;
    return true;

}
function export_download():bool{
    $table="deny_websites";
    $tfile=PROGRESS_DIR."/$table.csv";
    $basename="$table.csv";
    if(!is_file($tfile)){die("$tfile no such file");}
   // $type=mime_content_type($tfile);
    $fsize=@filesize($tfile);
    $timestamp =filemtime($tfile);
    $etag = md5($tfile . $timestamp);


    $tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    header("Content-Length: ".$fsize);
    header('Content-type: text/csv');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$basename\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    header("ETag: \"{$etag}\"");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($tfile);
    @unlink($tfile);
    return true;
}
function import_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog1("{import}","$page?import-popup=yes&function=$function");
}

function import_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $html[]="<div id='upload-black-progress'>";
    $html[]=$tpl->div_explain("{generic_upload_carriage_return}");
    $html[]="<div class='center' style='margin:30px'>";
    $html[]=$tpl->button_upload("{upload} (*.txt)",$page,null,"&function=$function");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function file_uploaded():bool{
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $file="/usr/share/artica-postfix/ressources/conf/upload/{$_GET["file-uploaded"]}";

    if(!preg_match("#\.txt$#i",basename($file))){
        $tpl->js_error("$file unexpected file..");
        @unlink($file);
        return false;
    }

    $tfile=urlencode(basename($file));

    $jscompile=$tpl->framework_buildjs(
        "squid2.php?import-blacklist=$tfile",
        "squid.wb.import.progress",
        "squid.wb.import.txt",
         "upload-black-progress","dialogInstance1.close();$function()");


    header("content-type: application/x-javascript");
    echo "$jscompile\n";
    return true;

}

function start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
}
function delete_js(){
    $tpl=new template_admin();
    $domain=$_GET["delete-js"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM deny_websites WHERE items='$domain'");
    if(!$q->ok){
        echo $tpl->js_mysql_alert($q->mysql_error);
        return false;
    }
    admin_tracks("Remove $domain from proxy blacklisted domains list");
    echo "$('#$md').remove()";
    return true;
}

function main(){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $search=null;
    $function=$_GET["function"];
    $t=time();
    $page=CurrentPageName();
    $QUERY=null;
    if(isset($_GET["search"])){
        $search="*{$_GET["search"]}*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $QUERY=" WHERE items LIKE '$search'";
    }
    $TRCLASS=null;
	$t=time();
	$sql="SELECT items  FROM deny_websites $QUERY ORDER BY items LIMIT 1000";

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
			$html[]="<thead>";
			$html[]="<tr>";
            $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
            $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites}</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>DEL</th>";
			$html[]="<th data-sortable=false></th>";
			$html[]="</tr>";
			$html[]="</thead>";
			$html[]="<tbody>";


    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
	foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $domain=$ligne["items"];
        $md=md5($domain);
        $domainEnc=urlencode($domain);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$domainEnc&md=$md')");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'><i class='fas fa-globe-africa'></i></td>";
        $html[]="<td width='99%'><strong>{$ligne["items"]}</strong></td>";
        $html[]="<td width='1%'>$delete</td>";
        $html[]="</tr>";

	}

    $jscompile=$tpl->framework_buildjs(
        "/proxy/global/blacklists/compile",
        "squid.wb.progress","squid.wb.txt","proxy-blacklist-progress");

    $page=CurrentPageName();


    $topbuttons[] = array("Loadjs('$page?add-js=yes&function=$function')",ico_plus,"{domains}");
    $topbuttons[] = array("Loadjs('$page?import-js=yes&function=$function')",ico_upload,"{import}");
    $topbuttons[] = array("Loadjs('$page?export=yes&function=$function')",ico_download,"{export}");
    $topbuttons[] = array("Loadjs('$page?truncate=yes&function=$function')",ico_trash,"{empty}");
    $topbuttons[] = array($jscompile,ico_save,"{apply_parameters}");

    $err=null;
    $srows=intval($q->COUNT_ROWS("deny_websites"));
    $Rows=$tpl->FormatNumber($q->COUNT_ROWS("deny_websites"));
    if($srows>50000){
            $err="&nbsp;&nbsp;<strong class='text-danger'>{limited_to} 50 000 {records}!</strong>";
    }

    $warning_deny_for_all_users2=$tpl->_ENGINE_parse_body("{warning_deny_for_all_users2}");
    $webfilterurl="document.location.href='/webfiltering-rules'";
    $APP_UFDBGUARD="<strong>".$tpl->td_href("{APP_UFDBGUARD}",null,$webfilterurl)."</strong>";
    $warning_deny_for_all_users2=$tpl->_ENGINE_parse_body(str_replace("%s",$APP_UFDBGUARD,$warning_deny_for_all_users2));
    $TINY_ARRAY["TITLE"]="{deny_websites} ($Rows {records})$err";
    $TINY_ARRAY["ICO"]="fa fa-ban";
    $TINY_ARRAY["EXPL"]="{warning_deny_for_all_users}<br>$warning_deny_for_all_users2";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</table>
    <script>
        NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
        $jstiny
    </script>";

echo $tpl->_ENGINE_parse_body($html);

	
}

function add_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];

    $jsrestart=$tpl->framework_buildjs(
        "/proxy/global/blacklists/compile",
        "squid.wb.progress","squid.wb.txt","proxy-blacklist-progress");


    $form[]=$tpl->field_section("{deny_websites}","{squid_ask_domain}");
    $form[]=$tpl->field_textareacode("deny_websites", "{websites}", null);
    echo $tpl->form_outside("",@implode("\n", $form),"","{add}","dialogInstance1.close();$function();$jsrestart");
}

function save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$f=explode("\n",$_POST["deny_websites"]);
    $adminTrack=array();
	foreach ($f as $line){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		$line=CleanWebSite($line);
		$line=$q->sqlite_escape_string2($line);
    	$n[]="('$line')";
	    $adminTrack[]="$line";
	}
	

	if(count($n)>0){
		$q->QUERY_SQL("INSERT OR IGNORE INTO `deny_websites` (`items`) VALUES ".@implode(",", $n));
		if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
	}

    if(count($adminTrack)>0){
        return admin_tracks("Add ".count($adminTrack)." in proxy blacklist domains list");
    }



    return true;
}

function CleanWebSite($site):string{
	$site=trim(strtolower($site));

    if(preg_match("#^\.(.+)#",$site,$re)){
        $site=$re[1];
    }

    if(preg_match("#^\##",$site)){
        return "";
    }

	if(preg_match("#^http#", $site)){
		$arrayURI=parse_url($site);
		$site=$arrayURI["host"];
	}

	if(strpos($site, "/")>0){
		$site="http://$site";
		$arrayURI=parse_url($site);
		$site=$arrayURI["host"];
	}

	if(preg_match("#(.+.):([0-9]+)", $site,$re)){
		$site=$re[1];
	}
	return $site;
}