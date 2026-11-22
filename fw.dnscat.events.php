<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.ipinfo.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDnsAdministrator){exit();}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["form"])){form();exit;}
//
page();


function page():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html=$tpl->page_header("{APP_UFDBCAT} {events}",ico_eye,
        "{APP_UFDBCAT_EXPLAIN}","$page?form=yes","ufdbcat-events","progress-ufdbcat-events-restart",false,"table-ufdbcat-events");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();return true;
    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page,null,null);
    echo "</div>";
    return true;
}


function search(){
    $tpl        = new template_admin();
    $MAIN       = $tpl->format_search_protocol($_GET["search"]);
    $line       = base64_encode(serialize($MAIN));
    $tfile      = PROGRESS_DIR."/webfiltering-categories.log";
    $pat        = PROGRESS_DIR."/webfiltering-categories.pattern";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("dnscat.php?syslog=$line");
    $data=explode("\n",@file_get_contents($tfile));
    krsort($data);


    $html[]="
<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>
	<thead>
    	<tr>
        	<th style='width:1%' nowrap>{date}</th>
        	<th style='width:1%' nowrap>PID</th>
        	<th style='width:1%' nowrap>{method}</th>
        	<th style='width:1%' nowrap>{events}</th>
        </tr>
  	</thead>
	<tbody>
";
    $COLORMETH["ERROR"]="label-danger";
    $COLORMETH["MODIFY"]="label-danger";
    $COLORMETH["CREATE"]="label-info";
    $COLORMETH["CHANGE"]="label-warning";
    $COLORMETH["REMOVE"]="label-info";
    $COLORMETH["SUCCESS"]="label-primary";
    $COLORMETH[null]="label-default";

    foreach ($data as $line){

        if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#",$line,$re)){
            // Pentest 2026-11-04 : ligne de journal dnscat (donnee externe) affichee sans encodage (XSS stockee).
            echo "<span style='color:red'>".req_html($line)."</span><br>";
            continue;
        }

        $class=null;
        $Month=$re[1];
        $Day=$re[2];
        $time=$re[3];
        $pid=$re[4];
        // Pentest 2026-11-04 : evenement de journal (donnee externe) affiche sans encodage (XSS stockee).
        $event=req_html($re[5]);
        $Month=req_html($Month);
        $function=null;
        $MOD="INFO";
        if(preg_match("#^(.+?)\(\)\{([0-9]+)\}\s+\[(.+?)\]:(.+)#",$event,$ri)){
            $function=$ri[1];
            $MOD=$ri[3];
            $line=$ri[2];
            $event=trim($ri[4]);
            $event="$event <small>($function/$line)</small>";
        }

        $event=str_replace("'APP_UFDBCAT' Categories Service:","",$event);

        if(preg_match("#\[SUCCESS\]:\s+(.+)#",$event,$re)){
            $event=$re[1];
            $MOD="SUCCESS";
        }
        if(preg_match("#\[INFO\]:\s+(.+)#",$event,$re)){
            $event=$re[1];
            $MOD="INFO";
        }
        if(preg_match("#Unable to#",$event,$re)){
            $MOD="ERROR";
        }


        $method="<span class='label {$COLORMETH["$MOD"]}'>$MOD</span>";
        $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$Month $Day $time</td>
				<td style='width:1%;' nowrap class='$class'>$pid</td>
				<td style='width:1%;' nowrap class='$class'>$method</td>
				<td style='width:99%' nowrap class='$class'>$event</td>
				</tr>";

    }

    $html[]="</tbody></table></div></div></div>";
    // Pentest 2026-11-04 : motif de recherche relu depuis un fichier, affiche sans encodage (XSS stockee).
    $html[]="<div><i>".req_html(@file_get_contents($pat))."</i></div>";
    $TINY_ARRAY["TITLE"]="{your_categories}: {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{personal_categories_explain}";
    $TINY_ARRAY["URL"]=null;
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body($html);
}


