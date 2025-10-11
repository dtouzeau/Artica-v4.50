<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once("/usr/share/artica-postfix/ressources/class.mysql.catz.inc");
$users=new usersMenus();if(!$users->AsSquidAdministrator){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["HotSpotRedirectUI"])){Save();exit;}
if(isset($_GET["events-form"])){events_form();exit;}
if(isset($_GET["events-search"])){events_search();exit;}
if(isset($_GET["search"])){table();exit;}
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
page();
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
				<td width=1% class='$color' nowrap>$FTime</td>
				<td width=1% class='$color' nowrap >$pid</td>
				<td width=1% class='$color' nowrap >$type</td>
				<td width=99% class='$color' nowrap><span class='$color'>$events</span></td>
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
    $function=$_GET["function"];
    $search=$_GET["search"];
    $q=new lib_sqlite("/home/artica/SQLITE/uncategorized.db");
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

   $ligne=$q->mysqli_fetch_array("SELECT count(host) as tcount FROM domains WHERE category=0");
   if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $count=$tpl->FormatNumber($ligne["tcount"]);

    $sql="CREATE TABLE IF NOT EXISTS `domains` (
		  `host` TEXT PRIMARY KEY,
		  `created` INTEGER NOT NULL DEFAULT 0,
          `category` INTEGER NOT NULL DEFAULT 0,
          `pushed` INTEGER NOT NULL DEFAULT 0,
          `categorized` INTEGER NOT NULL DEFAULT 0 )";

    $q->QUERY_SQL($sql);
    $countMax=$tpl->FormatNumber($q->COUNT_ROWS("domains"));

    if($search<>null){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $search_text=" WHERE host LIKE '$search'";
    }

    $results=$q->QUERY_SQL("SELECT * FROM domains$search_text ORDER BY category ASC,created DESC,categorized ASC LIMIT 500");
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
    $TRCLASS=null;
    $earch=ico_earth;
    $Qcl=new mysql_catz();
    foreach ($results as $index=>$ligne){
        $md=md5(serialize($ligne));
        $host=$ligne["host"];
        $created=$ligne["created"];
        $category=$ligne["category"];
        $category_text="{unknown}";
        $categorized_text="-";
        $categorized=$ligne["categorized"];
        $categorized_distance=null;
        if($category>0){
            $category_text=$Qcl->CategoryIntToStr($category);

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
        $html[]="<td width=1% nowrap>$ico</td>";
        $html[]="<td width=1% nowrap>$created</td>";
        $html[]="<td width=99%>$host</td>";
        $html[]="<td width=1% nowrap>$category_text</td>";
        $html[]="<td width=1% nowrap>$categorized_text$categorized_distance</td>";
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

    $topbuttons[]=array($launch,ico_run,"{analyze}");

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