<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["file-uploaded"])){import_uploaded_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["urls"])){urls();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["params"])){settings();exit;}
if(isset($_POST["REMOTE_PROXY"])){Save();exit;}
if(isset($_POST["SIEGE_URLS"])){SaveUrls();exit;}
if(isset($_GET["reports"])){reports();exit;}
if(isset($_GET["report"])){report_js();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["subject-js"])){subject_js();exit;}
if(isset($_GET["subject-popup"])){subject_popup();exit;}
if(isset($_GET["subject-fill"])){subject_fill();exit;}
if(isset($_POST["subject_edit"])){subject_edit();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["stop-js"])){stop_js();exit;}
if(isset($_GET["import-table"])){import_table();exit;}
if(isset($_GET["import-upload-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["import-executed"])){import_executed();exit;}
page();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?table=yes";
    $array["{urls}"]="$page?urls=yes";
    $array["{reports}"]="$page?reports=yes";
    echo $tpl->tabs_default($array);

}
function stop_js(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/siege/stop");

}

function import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div id='import-access-progress'></div>";
    $html[]="<div id='import-access-next'>";
    $html[]=$tpl->div_explain("{import_access_log_v4_explain}");
    $html[]="<div class=center>".$tpl->button_upload("{upload_file}",$page,null)."</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);

}
function import_executed(){
    $tpl=new template_admin();
    $tsource="/usr/share/artica-postfix/ressources/logs/web/access.log.csv";
    $fsize=@filesize($tsource);
    $ico="<i class='fas fa-file-csv'></i>";

    $size=FormatBytes($fsize/1024);

    echo $tpl->_ENGINE_parse_body(
        "<div class=\"widget-head-color-box navy-bg p-lg text-center\">
                                <h1>access.log.csv</h1>
                                <div class=\"m-b-sm\">
                                        <a href=\"ressources/logs/web/access.log.csv\">
                                        <img src=\"img/csv-64.png\" class=\"img-circle circle-border m-b-md\" alt=\"image\"></a>
                                </div>
                                        <p class=\"font-bold\">$size</p>


                            </div>");
}


function import_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{import} access.log","$page?import-popup=yes");
}
function import_uploaded_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);


    $js=$tpl->framework_buildjs("squid2.php?analyze-access=$fileencode","access.log.parser",
        "access.log.parser.debug","import-access-progress",
        "LoadAjax('import-access-next','$page?import-executed=yes')");

   echo $js;

}


function delete_js(){
    $ID=intval($_GET["delete-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $md=$_GET["md"];
    $tpl->js_confirm_delete($subject,"delete",$ID,"$('#$md').remove()");
}
function subject_js(){
    $ID=intval($_GET["subject-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $md=$_GET["md"];
    $tpl->js_dialog2($subject,"$page?subject-popup=$ID&md=$md");

}

function delete(){
    $ID=intval($_POST["delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $q->QUERY_SQL("DELETE FROM reports WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Removed proxy stress report $subject");
    return true;
}




function subject_popup(){
    $ID=intval($_GET["subject-popup"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $md=$_GET["md"];
    $form[]=$tpl->field_hidden("subject_id",$ID);
    $form[]=$tpl->field_text("subject_edit","{description}",$subject,true);
    $js="dialogInstance2.close();LoadAjaxSilent('subject-$md','$page?subject-fill=$ID')";
    echo $tpl->form_outside("{report}: $ID", @implode("\n", $form),null,"{save}",$js,"AsProxyMonitor");
}
function subject_edit(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $id=intval($_POST["subject_id"]);
    $subject=$q->sqlite_escape_string2($_POST["subject_edit"]);
    $q->QUERY_SQL("UPDATE reports SET subject='$subject' WHERE ID=$id");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
    admin_tracks("Change stress tool report subject to $subject");
    return true;
}

function subject_fill(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["subject-fill"]);
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject = $tpl->td_href($ligne["subject"],null,"Loadjs('$page?report=$ID')");
    echo $subject;
}
function report_js(){
    $ID=intval($_GET["report"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ligne=$q->mysqli_fetch_array("SELECT subject FROM reports WHERE ID=$ID");
    $subject=$ligne["subject"];
    $tpl->js_dialog1("{report} $subject","$page?report-popup=$ID");

}

function report_popup(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $ID=intval($_GET["report-popup"]);
    $ligne=$q->mysqli_fetch_array("SELECT report FROM reports WHERE ID=$ID");
    $report=base64_decode($ligne["report"]);
    if(!preg_match("#\{(.+?)\}#is",$report,$re)){
        echo $tpl->div_error("{failed} <code>$report</code>");
        return false;
    }
    $json=json_decode("{".$re[1]."}");
    $transactions=$json->transactions;
    $availability=$json->availability;
    $data_transferred=$json->data_transferred;
    $availability_w=$tpl->widget_h("green","fa-thumbs-up","$availability%","{availability}");
    if($availability<60){
        $availability_w=$tpl->widget_h("yellow","fa-thumbs-up","$availability","{availability}");
    }
    if($availability<30){
        $availability_w=$tpl->widget_h("red","fa-thumbs-down","$availability","{availability}");
    }

    $response_time=$json->response_time;
    $transaction_rate=$json->transaction_rate;
    $throughput=$json->throughput;
    $concurrency=$json->concurrency;
    $s_transactions=$json->successful_transactions;
    $f_transactions=$json->failed_transactions;
    $longest_transaction=$json->longest_transaction;
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","fa-cloud","$transactions/{$data_transferred}MB","{transactions}");
    $html[]="</td>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","far fa-arrow-to-right","{$throughput}MB","{throughput}");
    $html[]="</td>";
    $html[]="</tr><tr>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$availability_w;
    $html[]="</td>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("lazur","fas fa-exchange-alt","$transaction_rate trans/sec","{transaction_rate}");
    $html[]="</td>";
    $html[]="</tr><tr>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","fas fa-random","$s_transactions/$f_transactions",
        "{s_transactions}/{f_transactions}");
    $html[]="</td>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("yellow","fas fa-hourglass","$longest_transaction sec","{longest_transaction}");
    $html[]="</td>";
    $html[]="</tr><tr>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","fa-clock","$response_time sec","{response_time}");
    $html[]="</td>";
    $html[]="<td style='padding-left:5px'>";
    $html[]=$tpl->widget_h("blue","fal fa-compress-arrows-alt","$concurrency","{concurrency}");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function urls(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SIEGE_URLS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SIEGE_URLS");
    if($SIEGE_URLS==null){$SIEGE_URLS=@file_get_contents("/usr/share/artica-postfix/bin/install/squid/urls.txt");}
    $form[]=$tpl->field_textarea_normal("SIEGE_URLS", "", $SIEGE_URLS,"100%");

     echo $tpl->form_outside("URLs: ".count(explode("\n",$SIEGE_URLS)), @implode("\n", $form),null,"{save}",null,"AsProxyMonitor");
}
function SaveUrls(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SIEGE_URLS",$_POST["SIEGE_URLS"]);
    admin_tracks("Stress stool urls updated");
}


function status()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/siege/status"));
    if(!$json->Status){
        echo $tpl->widget_rouge($json->Error,"{failed}");
        return false;
    }

    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);


    $running = intval($ini->get("APP_SIEGE", "running"));
    if ($running == 0){
        $html[] = $tpl->widget_grey("{sleeping}", "{stopped}");
    }else{
        $btn[0]["name"]="{stop}";
        $btn[0]["icon"]="far fa-stop";
        $btn[0]["js"]="Loadjs('$page?stop-js=yes');";
        $uptime=$ini->get("APP_SIEGE","uptime");
        $memory=FormatBytes($ini->get("APP_SIEGE","master_memory"));
        $html[]=$tpl->widget_vert("{running}","<small style='color:white'>{since}: $uptime Mem: $memory</small>",$btn);
    }
    echo $tpl->_ENGINE_parse_body($html);
}


function page():bool{
   $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{mysql_benchmark}",ico_performance,
        "{squid_siege_explain}","$page?tabs=yes","siege","progress-siege-restart",false,"table-siege-status");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica:{mysql_benchmark}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html[]="<table style='width:100%;margin-top:10px'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px;vertical-align: top'>";
	$html[]="<div id='siege-status'></div></td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>";
    $html[]="<div id='siege-params'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('siege-params','$page?params=yes');";
    $html[]="";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function reports(){
        $tpl = new template_admin();
        $page = CurrentPageName();
        $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
        $js = "OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/proxy-service/tuning/stress-your-proxy-server',1024,768,'Stress Tool')";
        $t = time();

    $html[] = "<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?import-upload-js=yes')\"><i class='fas fa-file-import'></i> {analyze_access_log} </label>";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"$jshelp\"><i class='fas fa-question-circle'></i> Wiki </label>";
    $html[] = "</div>";
        $html[] = "<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
        $html[] = "<thead>";
        $html[] = "<tr>";
        $html[] = "<th data-sortable=true class='text-capitalize'>{date}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize'>{duration}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize'>{members}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize'>{target}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize' nowrap>{subject}</th>";
        $html[] = "<th data-sortable=true class='text-capitalize'></th>";
        $html[] = "<th data-sortable=true class='text-capitalize'></th>";
        $html[] = "</tr>";
        $html[] = "</thead>";
        $html[] = "<tbody>";


        $sql = "SELECT * FROM reports ORDER BY ID DESC";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error . "<br>$sql");
            return;
        }




        $TRCLASS = null;
       foreach ($results as $index=>$ligne){
           if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
            $md = md5(serialize($ligne));
            $date = strtotime($ligne["zdate"]);
            $date_text = $tpl->time_to_date($date, true);
            $zend=strtotime($ligne["zend"]);
            $users = $ligne["users"];
            $target = $ligne["target"];
            $duration = distanceOfTimeInWords($date,$zend);
           $ID=$ligne["ID"];
            $subject = $tpl->td_href($ligne["subject"],null,"Loadjs('$page?report=$ID')");
            $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsProxyMonitor");
            $edit=$tpl->icon_parameters("Loadjs('$page?subject-js=$ID&md=$md')");
            $html[] = "<tr class='$TRCLASS' id='$md'>";
            $html[] = "<td width=1% nowrap>{$date_text}</td>";
            $html[] = "<td width=1% nowrap>{$duration}</td>";
            $html[] = "<td width=1% nowrap>$users</td>";
            $html[] = "<td  width=1% nowrap>$target</td>";
            $html[] = "<td  width=99% nowrap><span id='subject-$md'>$subject</span></td>";
           $html[] = "<td  width=1% nowrap>$edit</td>";
           $html[] = "<td  width=1% nowrap>$delete</td>";
            $html[] = "</tr>";

        }


        $html[] = "</tbody>";
        $html[] = "<tfoot>";

        $html[] = "<tr>";
        $html[] = "<td colspan='7'>";
        $html[] = "<ul class='pagination pull-right'></ul>";
        $html[] = "</td>";
        $html[] = "</tr>";
        $html[] = "</tfoot>";
        $html[] = "</table>";
        $html[] = "<div><small>$sql</small></div>
	<script>
	NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    }


function settings(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ARRAY=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSiegeConfig"));
    if(!is_numeric($ARRAY["GRAB_URLS"])){$ARRAY["GRAB_URLS"]=0;}
    if(!is_numeric($ARRAY["USE_LOCAL_PROXY"])){$ARRAY["USE_LOCAL_PROXY"]=0;}
    if(!is_numeric($ARRAY["SESSIONS"])){$ARRAY["SESSIONS"]=150;}
    if(!is_numeric($ARRAY["MAX_TIME"])){$ARRAY["MAX_TIME"]=30;}
    if(!is_numeric($ARRAY["REMOTE_PROXY_PORT"])){$ARRAY["REMOTE_PROXY_PORT"]=3128;}
    if(!isset($ARRAY["CONNECTION"])){$ARRAY["CONNECTION"]="keep-alive";}


    $cnxs["keep-alive"]="keep-alive";
    $cnxs["close"]="close";

    $script=$tpl->framework_buildjs("/siege/run",
        "squid.siege.progress","squid.siege.progress.txt","progress-siege-restart");

    $form[]=$tpl->field_text("REMOTE_PROXY","{remote_proxy}",$ARRAY["REMOTE_PROXY"]);
    $form[]=$tpl->field_numeric("REMOTE_PROXY_PORT","{remote_port}",$ARRAY["REMOTE_PROXY_PORT"]);
    $form[]=$tpl->field_text("USERNAME","{username}",$ARRAY["USERNAME"]);
    $form[]=$tpl->field_password("PASSWORD","{password}",$ARRAY["PASSWORD"]);
    $form[]=$tpl->field_numeric("SESSIONS","{simulate} ({members})",$ARRAY["SESSIONS"]);
    $form[]=$tpl->field_numeric("MAX_TIME","{execution_time} ({seconds})",$ARRAY["MAX_TIME"]);
    $form[]=$tpl->field_array_hash($cnxs,"CONNECTION","{connection}",$ARRAY["CONNECTION"]);

    $html[]=$tpl->form_outside("",@implode("\n", $form),null,"{launch_test}",$script,"AsProxyMonitor");

    $refresh=$tpl->RefreshInterval_js("siege-status",$page,"status=yes");

    $html[]="<script>$refresh</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}

function Save(){

	$tpl=new template_admin();
	$tpl->CLEAN_POST();

    $ARRAY=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSiegeConfig"));
	
	foreach ($_POST as $key=>$value){
        $ARRAY[$key]=$value;
	}
    admin_tracks("Running a stress tool to the targeted proxy {$_POST["REMOTE_PROXY"]}");
    $newval=base64_encode(serialize($ARRAY));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidSiegeConfig",$newval);

}


