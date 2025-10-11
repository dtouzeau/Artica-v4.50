<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["step1"])){step1();exit;}
if(isset($_GET["step2"])){step2();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded_js();exit;}
if(isset($_GET["installer-php"])){installer_php();exit;}
if(isset($_GET["step3"])){step3();exit;}
if(isset($_GET["step4"])){step4();exit;}
if(isset($_GET["step5"])){step5();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog5("{restore}: Wordpress Duplicator","$page?start=yes");
}
function start(){
    $page=CurrentPageName();
    echo "<div id='wordpress_duplicator'></div>\n";
    echo "<script>LoadAjaxTiny('wordpress_duplicator','$page?step1=yes');</script>";
}

function step1(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<H1>{restore}: Wordpress Duplicator</H1>";
    $html[]="<H2>{welcome_wordpress_duplicator_0}</H2>";
    $t=time();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $results=$q->QUERY_SQL("SELECT * FROM wp_sites ORDER BY hostname");
    if(!$q->ok){echo $q->mysql_error_html();}
    $TRCLASS=null;
    VERBOSE("SELECT * FROM wp_sites ORDER BY hostname == ".count($results));
    $html[] = "<table id='table-$t-main' class=\"table table-stripped\" style='margin-top:5px'>";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th>&nbsp;</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{wordpress_websites}</th>";
    $html[] = "<th>&nbsp;</th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";
    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $ID = $ligne["ID"];
        $hostname = $ligne["hostname"];
        $database_name=trim($ligne["database_name"]);
        $database_user=trim($ligne["database_user"]);
        if($database_name==null){
            VERBOSE("$hostname: database_name == NULL",__LINE__);
            continue;}
        if($database_user==null){
            VERBOSE("$hostname: database_user == NULL",__LINE__);
            continue;}

        $md = md5(serialize($ligne));
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td style='width:1%' nowrap><i class='fas fa-globe'></i></span></td>";
        $html[] = "<td style='width:99%'>$hostname</td>";
        $js = "LoadAjax('wordpress_duplicator','$page?step2=$ID')";
        $button = "<button class='btn btn-primary btn-xs' OnClick=\"javascript:$js\">{select}</button>";
        $html[] = "<td style='width:1%' nowrap>$button</td>";
        $html[] = "</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function step2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $siteid=intval($_GET["step2"]);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");

    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID='$siteid'");
    $hostname = $ligne["hostname"];
    $html[]="<H1>{restore}: $hostname</H1>";
    $html[]="<H3>{welcome_wordpress_duplicator_1}</H3>";
    $html[]="<div style='text-align:center;margin:30px'>";
    $html[]=$tpl->button_upload("{restore_container} (zip/daf)",$page,null,"&siteid=$siteid");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function file_uploaded_js(){
    $page=CurrentPageName();
    $filename=$_GET["file-uploaded"];
    $siteid=$_GET["siteid"];
    $fileencode=urlencode($filename);
    $sock=new sockets();
    $sock->getFrameWork("wordpress.php?wp-duplicator-back=$fileencode&siteid=$siteid");
    echo "LoadAjax('wordpress_duplicator','$page?step3=$fileencode&siteid=$siteid');";
}

function step3(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $siteid=intval($_GET["siteid"]);
    $fname=$_GET["step3"];
    $fnameEnc=urlencode($fname);
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $welcome_wordpress_duplicator_2=$tpl->_ENGINE_parse_body("{welcome_wordpress_duplicator_2}");
    $welcome_wordpress_duplicator_2=str_replace("%con","<span style='font-size:12px;font-weight:bold'>$fname</span>",$welcome_wordpress_duplicator_2);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID='$siteid'");
    $hostname = $ligne["hostname"];
    $html[]="<H1>{restore}: $hostname</H1>";
    $html[]="<H3>$welcome_wordpress_duplicator_2</H3>";
    $html[]="<div style='text-align:center;margin:30px'>";
    $html[]=$tpl->button_upload("installer.php",$page,null,"&siteid=$siteid&contener=$fnameEnc","installer-php");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);

}

function installer_php(){
    $page=CurrentPageName();
    $filename=$_GET["installer-php"];
    $siteid=$_GET["siteid"];
    $fileencode=urlencode($filename);
    $sock=new sockets();
    $sock->getFrameWork("wordpress.php?wp-duplicator-insta=$fileencode&siteid=$siteid");
    echo "LoadAjax('wordpress_duplicator','$page?step4=$siteid');";
}
function step4(){
    $siteid=intval($_GET["step4"]);
    $page=CurrentPageName();
    $tpl=new template_admin();

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID='$siteid'");
    $welcome_wordpress_duplicator_3=$tpl->_ENGINE_parse_body("{welcome_wordpress_duplicator_3}");
    $hostname = $ligne["hostname"];
    $html[]="<H1>{restore}: $hostname</H1>";
    $html[]="<H3>$welcome_wordpress_duplicator_3</H3>";
    $html[]="<div style='text-align:center;margin:30px'>";

    $service_reconfigure=$tpl->framework_buildjs("wordpress.php?wp-restore=$siteid",
        "wordpress.build.progress",
        "wordpress.build.progress.txt",
        "wordpress_duplicator",
        "LoadAjax('wordpress_duplicator','$page?step5=$siteid');");


    $html[]=$tpl->button_autnonome("{next}...", $service_reconfigure, "fad fa-file-import");
    echo $tpl->_ENGINE_parse_body($html);
}
function step5(){
    $siteid=intval($_GET["step5"]);
    $page=CurrentPageName();
    $tpl=new template_admin();

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wp_sites WHERE ID='$siteid'");
    $hostname = $ligne["hostname"];

    $welcome_wordpress_duplicator_4=$tpl->_ENGINE_parse_body("{welcome_wordpress_duplicator_4}");
    $welcome_wordpress_duplicator_4=str_replace("%urls","<strong>http://$hostname/installer.php</strong>",$welcome_wordpress_duplicator_4);
    $html[]="<H1>{restore}: $hostname</H1>";
    $html[]="<H3>$welcome_wordpress_duplicator_4</H3>";
    $tb[]="<table>";
    $tb[]="<tr>";
    $tb[]="<td style='font-size:16px'>{database_name}:</td>";
    $tb[]="<td style='font-size:16px;padding-left:10px'><strong>{$ligne["database_name"]}</strong></td>";
    $tb[]="</tr>";
    $tb[]="<tr>";
    $tb[]="<td style='font-size:16px'>{mysql_username}:</td>";
    $tb[]="<td style='font-size:16px;padding-left:10px'><strong>{$ligne["database_user"]}</strong></td>";
    $tb[]="</tr>";
    $tb[]="<tr>";
    $tb[]="<td style='font-size:16px'>{mysql_password}:</td>";
    $tb[]="<td style='font-size:16px;padding-left:10px'><strong>{$ligne["database_password"]}</strong></td>";
    $tb[]="</tr>";
    $tb[]="</table>";
    $html[]=$tpl->div_explain(@implode("",$tb));
    echo $tpl->_ENGINE_parse_body($html);
}