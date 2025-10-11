<?php
$GLOBALS["DEBUG"]=false;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(!isset($GLOBALS["resolveIP2HOST"])){$GLOBALS["resolveIP2HOST"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));}
define("FPDF_FONTPATH",dirname(__FILE__).'/ressources/externals/fpdf/font');
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.artica.graphs.inc');
include_once(dirname(__FILE__).'/ressources/externals/fpdf/fpdf.php');
include_once(dirname(__FILE__).'/ressources/class.statscom-msmtp.inc');
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");

if(preg_match("#--debug#",implode(" ",$argv),$re)){$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--schedule"){schedule($argv[2]);}
if($argv[1]=="--top"){dangerous_categories($argv[2]);}
if($argv[1]=="--build"){build_weekly_report();}

function build_progress_schedule($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/statscom.schedule.progres";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($ARRAY["PROGRESS_FILE"], serialize($array));
    @chmod($ARRAY["PROGRESS_FILE"],0755);
}



function schedule($taskid){
    $EXEC_PID_FILE      = "/etc/artica-postfix/pids/".basename(__FILE__).".$taskid.pid";
    $EXEC_PID_TIME      = "/etc/artica-postfix/pids/".basename(__FILE__).".$taskid.time";
    $unix               = new unix();

    if(!$GLOBALS["FORCE"]) {
        if (is_file($EXEC_PID_TIME)) {
            $TimeMin = $unix->file_time_min($EXEC_PID_TIME);
            if ($TimeMin<480){
                echo "Only each 480Mn allowed, current = {$TimeMin}mn\n";
                build_progress_schedule(110,"{failed}");
                return false;
            }
        }
        $CurrentPid=$unix->get_pid_from_file($EXEC_PID_FILE);
        if($unix->process_exists($CurrentPid)){
            echo "Already process PID $CurrentPid executed\n";
            build_progress_schedule(110,"{failed}");
            return false;
        }

    }

    @unlink($EXEC_PID_TIME);
    @file_put_contents($EXEC_PID_TIME,time());
    @file_put_contents($EXEC_PID_FILE,getmypid());




    if(!is_dir("/home/artica/SQLITE/PDF")){@mkdir("/home/artica/SQLITE/PDF",0755,true);}
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM pdf_reports WHERE ID=$taskid");
    $description=$ligne["TimeDescription"];
    $ID=$ligne["ID"];
    $TaskType=$ligne["TaskType"];
    $local_path="/usr/share/artica-postfix/PDF/$taskid.pdf";
    if(is_file($local_path)){@unlink($local_path);}

    if($TaskType==0) {die();}
    if($TaskType==1) {die();}
    if($TaskType==2){die();}

    $subject=$ligne["subject"];
    $recipients=$ligne["recipients"];

    echo "Task Type......: $TaskType\n";


    if($TaskType==3){
        $Number = date( 'W', strtotime( 'last week' ) );
        $report_path="/home/artica/SQLITE/PDF/week-$ID.pdf";
        echo "PDF Report.....: $report_path\n";
        $type="week";
        CleanImages();
        build_progress_schedule(50,"{building_report}");
        build_weekly_report($Number,$subject,$description,$report_path,$type);
        build_progress_schedule(100,"{building_report}");
    }

    if($TaskType==4){
        $Number = date( 'W');
        $report_path="/home/artica/SQLITE/PDF/week-$ID.pdf";

        echo "PDF Report.....: $report_path\n";
        $type="week";
        CleanImages();
        build_progress_schedule(50,"{building_report}");
        build_weekly_report($Number,$subject,$description,$report_path,$type);
        build_progress_schedule(100,"{building_report}");
    }
    if($TaskType==4){
        $Number = date( 'W');
        $report_path="/home/artica/SQLITE/PDF/week-$ID.pdf";
        echo "PDF Report.....: $report_path\n";
        $type="week";
        CleanImages();
        build_progress_schedule(50,"{building_report}");
        build_weekly_report($Number,$subject,$description,$report_path,$type);
        build_progress_schedule(100,"{building_report}");
    }
    if($TaskType==5){
        $Number = date( 'm',strtotime( 'last month' ) );
        $report_path="/home/artica/SQLITE/PDF/month-$ID.pdf";
        echo "PDF Report.....: $report_path\n";
        $type="month";
        build_progress_schedule(50,"{building_report}");
        echo "Number.........: $Number\n";
        echo "Type...........: $type\n";
        CleanImages();
        if(!build_weekly_report($Number,$subject,$description,$report_path,$type)){
            $Content=@file_get_contents("/home/artica/SQLITE/PDF/month-error.txt");
            echo "Error:$Content\n";
            $smtp=new statscom_msmtp();
            $smtp->recipient=$recipients;
            $smtp->Subject=$subject;
            $smtp->Body=$description."\r\n$Content";
            if(!$smtp->Send($description,null)){
                build_progress_schedule(110,"{error_while_sending_message}");
                squid_admin_mysql(1,"PDF Reports Failed SMTP $smtp->smtp_error",null,__FILE__,__LINE__);
                return false;
            }
            echo "Building report, failed........\n";
            build_progress_schedule(110,"{failed}");
            return false;
        }
        echo "Building report, Success........\n";
    }

    if($TaskType==6){
        $Number = date('m');
        $report_path="/home/artica/SQLITE/PDF/month-$ID.pdf";
        $type="month";
        echo "Number.........: $Number\n";
        echo "Type...........: $type\n";
        CleanImages();
        build_progress_schedule(50,"{building_report}");
        build_weekly_report($Number,$subject,$description,$report_path,$type);
        echo "Building report, Success........\n";
    }

    if($GLOBALS["DEBUG"]){
        $report_path=PROGRESS_DIR."/PDF.pdf";
    }
    if($GLOBALS["DEBUG"]){echo "$report_path\n";}









    if(!is_file($report_path)){
        build_progress_schedule(110,"{building_report} {failed}");
        squid_admin_mysql(1,"Unable to build daily report [$subject]",null,__FILE__,__LINE__);
        return false;
    }



    replicate_pdf($local_path,$report_path);
    if($GLOBALS["DEBUG"]){
        echo "$report_path success\n";
        return false;
    }


    build_progress_schedule(80,"{sending_message}");
    $smtp=new statscom_msmtp();
    $smtp->recipient=$recipients;
    $smtp->Subject=$subject;
    $smtp->Body=$description;
    if(!$smtp->Send($description,$report_path)){
        @unlink($report_path);
        build_progress_schedule(110,"{error_while_sending_message}");
        squid_admin_mysql(1,"PDF Reports Failed SMTP $smtp->smtp_error",null,__FILE__,__LINE__);
        return;
    }
    @unlink($report_path);
    build_progress_schedule(100,"{success}");


}

function CleanImages(){
    $f[]="/tmp/graph1.png";
    $f[]="/tmp/graph3.png";
    $f[]="/tmp/graph5.png";
    $f[]="/tmp/graph6.png";
    $f[]="/tmp/graph8.png";
    $f[]="/tmp/graph9.png";
    $f[]="/tmp/dang1.png";
    $f[]="/tmp/block1.png";
    $f[]="/tmp/block3.png";

    foreach ($f as $path){
        if(is_file($path)){
            echo "Removing temporary image $path\n";
            @unlink($path);
        }
    }

}

function replicate_pdf($local_path,$report_path){
    if(is_file($local_path)){@unlink($local_path);}
    $local_dir=dirname($local_path);
    if(!is_dir($local_dir)){@mkdir($local_dir,0755,true);}
    @chown($local_dir,"www-data");
    @chgrp($local_dir,"www-data");
    @chmod($local_dir,0755);
    if(!is_file($report_path)){
        echo "$report_path no such file...\n";
        return false;}

    echo "$report_path  ---> $local_path ...\n";
    if(!@copy($report_path,$local_path)){
        $error=error_get_last();
        echo "$report_path  ---> $local_path {$error["message"]} ...\n";
    }

    @chown($local_path,"www-data");
    @chgrp($local_path,"www-data");
}

function send_email_monthly_error($Number){

    $error[]="You have asked to build monthly statistics for the Month number $Number " .date("Y")."\r\n";
    $error[]="It seems that during this period, no data has been saved.";
    $error[]="We suggest to wait next month in order to receive a full report.";
    $error[]="\r\nBest regards.\r\nYour Artica server.\r\n";
    @file_put_contents("/home/artica/SQLITE/PDF/month-error.txt",@implode("\r\n",$error));


}

function categorization_rate($pdf){

    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM statscom_websites WHERE category>0");
    $categorized=intval($ligne["tcount"]);
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM statscom_websites WHERE category=0");
    $nocategorized=intval($ligne["tcount"]);

    $percent=$nocategorized/$categorized;
    $percent=100-round($percent*100,2);
    $categorized=FormatNumber($categorized);
    $nocategorized=FormatNumber($nocategorized);
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7,"Since statistics feature is enabled, your users have visited ");
    $pdf->SetFont('Lato-Bold','',14);
    $pdf->Write(7,"$categorized");
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7," categorized Internet sites and ");
    $pdf->SetFont('Lato-Bold','',14);
    $pdf->Write(7,"$nocategorized");
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7," unknown Internet sites.");
    $pdf->Ln();
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7,"This means the Articatech database cover ");
    $pdf->SetFont('Lato-Bold','',14);
    $pdf->Write(7,"{$percent}%");
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7," of Internet usage");
    $pdf->Ln();
    return $pdf;
}


//SELECT date_part('week', zdate) as week,siteid,SUM(hits) as hits, sum(size) as size  FROM statscom GROUP BY siteid,week

function build_weekly_report($Number=0,$subject=null,$description=null,$targetpath=null,$type="week"){
    $q=new postgres_sql();
    $tpl=new template_admin();

    if($Number==0){
        $Number = date( 'W', strtotime( 'last week' ) );
    }
    if($subject==null){
        $subject="$type report $Number ".date("Y");
    }
    if($targetpath==null){
        $targetpath="/usr/share/artica-postfix/week.pdf";
    }


    if($type=="week"){
        $date_part="date_part('week', zdate)='$Number'";
    }
    if($type=="month"){
        $date_part="date_part('month', zdate)='$Number'";

    }

    $sql="SELECT date_part('day', zdate) as zdate, SUM(size) as size, SUM(hits) as hits FROM statscom  WHERE $date_part group by date_part('day', zdate) ORDER by zdate";

    echo "$sql\n\n";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        @file_put_contents("/home/artica/SQLITE/PDF/month-error.txt",$q->mysql_error);
        echo $q->mysql_error."\n";
        squid_admin_mysql(0,"Fatal, unable to build PDF report PostgreSQL error",
            $q->mysql_error,__FILE__,__LINE__);
        return false;}

    $SUM=0;
    $HITS=0;
    $HOURLY=array();

    $num_rows=pg_num_rows($results);

    echo "Number of rows: $num_rows\n";
    if($num_rows==0){
        send_email_monthly_error($Number);
        return false;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["size"];
        $SUM  = $SUM + intval($ligne["size"]);
        $HITS = $HITS + intval($ligne["hits"]);
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);
        $date=$ligne["zdate"];
        $xdata[]=$date;
        $ydata[]=$DOWN;
    }

    $fileName="/tmp/graph1.png";
    $graph = new artica_graphs($fileName);
    $graph->ydata=$ydata;
    $graph->xdata=$xdata;
    $graph->title="Daily bandwidth usage";
    $graph->x_title="Days";
    $graph->y_title="MB";
    $graph->filename=$fileName;
    $graph->width=750;
    $graph->height=400;;
    $graph->line_green();


    $results=$q->QUERY_SQL("SELECT date_part('day', zdate) as zdate, username,ipaddr,mac FROM statscom 
        WHERE $date_part group by date_part('day', zdate),username,ipaddr,mac ORDER by zdate");


    $MUN=array();
    $SUMUsers=array();

    while ($ligne = pg_fetch_assoc($results)) {
        $date       = $ligne["zdate"];
        $username   = $ligne["username"];
        $ipaddr     = $ligne["ipaddr"];
        if($GLOBALS["resolveIP2HOST"]==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }
        $mac        = $ligne["mac"];
        $md5=md5($username.$ipaddr.$mac);
        if(!isset($MUN[$date][$md5])){$MUN[$date][$md5]=true;}
        if(!isset($SUMUsers[$md5])){$SUMUsers[$md5]=true;}
    }

    $fileName="/tmp/graph3.png";
    if(count($MUN)>1) {
        $graph = new artica_graphs($fileName);

        foreach ($MUN as $time => $users) {
            $graph->ydata[] = count($users);
            $graph->xdata[] = $time;

        }

        echo "[" . __LINE__ . "]: Number of users each day\n";
        $graph->title = "Number of users each day";
        $graph->x_title = "Day";
        $graph->y_title = "Users";
        $graph->filename = $fileName;
        $graph->width = 750;
        $graph->height = 400;;
        $graph->line_green();
    }

    $results=$q->QUERY_SQL("SELECT SUM(size) as size,username,ipaddr,mac FROM statscom 
        WHERE $date_part group by username,ipaddr,mac ORDER BY size DESC LIMIT 15");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $fileName="/tmp/graph5.png";
    $Count=pg_num_rows($results);

    if($Count>1) {
        $graph = new artica_graphs($fileName);

        while ($ligne = pg_fetch_assoc($results)) {
            $DOWN = $ligne["size"];
            $DOWN = $DOWN / 1024;
            $DOWN = $DOWN / 1024;
            $DOWN = round($DOWN);
            $username = $ligne["username"];
            $ipaddr = $ligne["ipaddr"];
            if($GLOBALS["resolveIP2HOST"]==1){
                $host= new ip2host($ipaddr);
                $ipaddr=$host->output;
            }
            $mac = $ligne["mac"];
            $key = null;
            if ($username <> null) {
                $key = $username;
            }
            if ($key == null) {
                if ($ipaddr <> null) {
                    $key = $ipaddr;
                }
            }
            if ($key == null) {
                if ($mac <> null) {
                    $key = $mac;
                }
                if ($key == "00:00:00:00:00:00") {
                    $key = null;
                }
            }

            if ($key == null) {
                continue;
            }
            echo "$key -> $DOWN\n";
            $graph->ydata[] = $key;
            $graph->xdata[] = $DOWN;

        }
        $graph->title = "Top Users";
        $graph->x_title = "Hour";
        $graph->y_title = "Users";
        $graph->filename = $fileName;
        $graph->width = 750;
        $graph->height = 750;
        $graph->Unit = "MB";

        echo "[" . __LINE__ . "]: Top Users\n";
        $graph->pieFlat();
    }


    echo "[".__LINE__."]: Start PDF\n";
    $pdf = new FPDF();

    echo "[".__LINE__."]: top_of_categories\n";
    top_of_categories($Number,$type);

    $pdf->AddPage();
    $pdf->AddFont('Lato-Bold','','Lato-Bold.php');
    $pdf->AddFont('Lato-Regular','','Lato-Regular.php');
    $pdf->AddFont('Lato-Thin','','Lato-Thin.php');
    $pdf->SetFont('Lato-Bold','',28);

    $text=$subject;
    $pdf->Cell( 0, 10, $text, 0, 0, 'R' );
    $pdf->SetX($pdf->LMargin);
    //$pdf->Text(116,30,$text);
    $pdf->Ln();
    $pdf->SetFont('Lato-Thin','',16);
    //$pdf->Text(165,40,"$requested_date");
    $pdf->Cell( 0, 15, "$type $Number ".date("Y"), 0, 0, 'R' );
    $pdf->SetX($pdf->LMargin);
    $pdf->Ln();
    $pdf->SetFont('Lato-Thin','',14);
    //$pdf->Text(50,60,"$description");
    $pdf->Cell(0,70,$description,0,0,'C');
    $pdf->SetX($pdf->LMargin);
    $logo="/usr/share/artica-postfix/img/logo-doc.png";
    $pdf->Image($logo,112,198);
    $pdf->Ln();
    $SUM=Readablesize($SUM);

    $rqs=$tpl->FormatNumber($HITS);
    $rqs=str_replace("&nbsp;"," ",$rqs);
    $CountOfUsers=count($SUMUsers);
    $line_internet_sites=line_internet_sites($Number,$type);
    $line_internet_sites=$tpl->FormatNumber($line_internet_sites);
    $line_internet_sites=str_replace("&nbsp;"," ",$line_internet_sites);

    echo "[".__LINE__."]: Page 1\n";
    $pdf->AddPage();
    $pdf->SetFont('Lato-Bold','',18);
    $pdf->Write(7,"Introduction for this $type report of $Number");
    $pdf->Ln();$pdf->Ln(); $pdf->Ln();$pdf->Ln();
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7,"During this period ");

    $pdf->SetFont('Lato-Bold','',14);
    $pdf->Write(7,"$CountOfUsers");
    $pdf->SetFont('Lato-Regular','',14);

    $pdf->Write(7," users are have downloaded or uploaded ");
    $pdf->SetFont('Lato-Bold','',14);
    $pdf->Write(7,"$SUM");
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7," of data using ");
    $pdf->SetFont('Lato-Bold','',14);
    $pdf->Write(7,"$rqs");
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7," requests on ");
    $pdf->SetFont('Lato-Bold','',14);
    $pdf->Write(7,"$line_internet_sites");
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7," Internet sites.");
    $pdf->Ln();


    $pdf->Write(7,"In most cases, your users navigates on internet for ");
    $pdf->SetFont('Lato-Bold','',14);
    $pdf->Write(7,@implode(", ",$GLOBALS["CATEGORIES_TEXT"]));
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7," topics.");
    $pdf->Ln();
    $pdf=categorization_rate($pdf);

    if(is_file("/tmp/graph1.png")) {
        $pdf->AddPage();
        echo "[" . __LINE__ . "]: Page 2\n";
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "Bandwidth usage");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular', '', 12);
        $pdf->Ln();
        $pdf->Write(7, "The picture bellow display the bandwidth usage $type $Number with daily interval");
        $pdf->Ln();
        $pdf->Image('/tmp/graph1.png');
    }
    if(is_file("/tmp/graph3.png")) {
        $pdf->AddPage();
        echo "[" . __LINE__ . "]: Page 3\n";
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "Your users");

        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular', '', 12);
        $pdf->Write(7, "The picture bellow display the number of users");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Image('/tmp/graph3.png');
    }
    if(is_file("/tmp/graph5.png")) {
        $pdf->AddPage();
        echo "[" . __LINE__ . "]: Page 4\n";
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "Top 15 of your users");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Image('/tmp/graph5.png');
    }

    $pdf->AddPage();
    echo "[".__LINE__."]: Page 5\n";
    $hideMacs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideMacs'));
    $hideUnkownMembers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideUnkownMembers'));
    $pdf->SetFont('Lato-Bold','',18);
    $pdf->Write(7,"List of your users with requests and bandwidth usage.");
    $pdf->Ln();
    $pdf->SetFont('Lato-Regular','',10);
    $pdf->Write(5,"Users that have used less than 1Mb are not displayed.");
    $pdf->Ln();
    $pdf->Write(5,"Sometimes, usernames are not displayed, this means some proxy ACLs allow accessing to websites.");
    $pdf->Write(5,"In this case proxy did not ask authentication to browsers");
    $pdf->Ln();
    $pdf->SetFont('Lato-Regular','',9);
    $pdf->Cell(20,7,"Size",1,0,'C');
    $pdf->Cell(20,7,"Hits",1,0,'C');
    $pdf->Cell(50,7,"Username",1,0,'C');
    $pdf->Cell(50,7,"IP",1,0,'C');
    if($hideMacs==0) {
        $pdf->Cell(25, 7, "MAC", 1, 0, 'C');
    }
    $pdf->Ln();


    $results=$q->QUERY_SQL("SELECT SUM(size) as size,SUM(hits) as hits,username,ipaddr,mac FROM statscom WHERE $date_part 
                group by username,ipaddr,mac ORDER BY size DESC");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $pdf->SetFont('Lato-Regular','',7);

    echo "[".__LINE__."]: Build Table of users\n";
    $hideMacs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideMacs'));
    $hideUnkownMembers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideUnkownMembers'));
    while ($ligne = pg_fetch_assoc($results)) {
        if($ligne["size"]<1048576){continue;}
        $size=FormatBytes($ligne["size"]/1024,true);
        $username=$ligne["username"];
        $hits=FormatNumber($ligne["hits"]);
        $ipaddr=$ligne["ipaddr"];
        if($username==null){
            if($hideUnkownMembers==1){
                $username=$ipaddr;
            }
            else{
                $username="{unknown}";
            }

        }
        if($GLOBALS["resolveIP2HOST"]==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }
        $MAC=$ligne["mac"];

        $pdf->Cell(20,4,$size,1,0,'R');
        $pdf->Cell(20,4,$hits,1,0,'R');
        $pdf->Cell(50,4,"$username",1,0,'L');
        $pdf->Cell(50,4,"$ipaddr",1,0,'R');
        if($hideMacs==0) {
            $pdf->Cell(25, 4, $MAC, 1, 0, 'R');
        }
        $pdf->Ln();

    }

    $pdf->Cell(20+20+50+25+25,0,'','T');


    echo "[".__LINE__."]: Building Internet Graphs sites\n";
    if(is_file('/tmp/graph6.png')) {
        $pdf->AddPage();
        echo "[" . __LINE__ . "]: Page 6\n";
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "Internet Web sites");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Image('/tmp/graph6.png');
    }


    echo "[".__LINE__."]: Building Internet Graphs Table\n";
    $pdf->AddPage();
    echo "[".__LINE__."]: Page 7\n";
    $pdf->SetFont('Lato-Bold','',18);
    $pdf->Write(7,"List of Internet sites with requests and bandwidth usage.");
    $pdf->Ln();
    $pdf->SetFont('Lato-Regular','',12);
    $pdf->Write(7,"Internet sites that have used less than 1Mb are not displayed.");
    $pdf->Ln();
    $pdf->SetFont('Lato-Regular','',9);
    $pdf->Cell(20,7,"Size",1,0,'C');
    $pdf->Cell(20,7,"Hits",1,0,'C');
    $pdf->Cell(40,7,"Categories",1,0,'C');
    $pdf->Cell(100,7,"Websites",1,0,'C');
    $pdf->Ln();

    $catz=new mysql_catz();
    $q = new postgres_sql();
    $results = $q->QUERY_SQL("SELECT statscom_websites.familysite,SUM(statscom.size) as size,
                            SUM(statscom.hits) as hits,statscom_websites.category
                            FROM statscom,statscom_websites
                            WHERE $date_part
                            AND statscom_websites.siteid=statscom.siteid 
                            group by familysite,category ORDER by size DESC");

    if(!$q->ok){
        echo $q->mysql_error."\n";
        $pdf->Text(7,$q->mysql_error);}
    $pdf->SetFont('Lato-Regular','',7);
    while ($ligne = pg_fetch_assoc($results)) {
        if($ligne["size"]<1048576){continue;}
        $size = $ligne["size"];
        $size = FormatBytes($size/1024,true);
        $sitename = $ligne["familysite"];
        $hits=FormatNumber($ligne["hits"],0,' ');
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $pdf->Cell(20, 4, $size, 1, 0, 'R');
        $pdf->Cell(20, 4, $hits, 1, 0, 'R');
        $pdf->Cell(40, 4, $category, 1, 0, 'R');
        $pdf->Cell(100, 4, "$sitename", 1, 0, 'L');
        $pdf->Ln();
    }
    $pdf->Cell(20+20+40+100,0,'','T');


    if(is_file('/tmp/graph8.png')) {
        $pdf->AddPage();
        echo "[" . __LINE__ . "]: Page 8\n";
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "TOP 15 Web sites");
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular', '', 12);
        $pdf->Write(7, "TOP Internet sites that consume your bandwidth.");
        $pdf->Ln();
        $pdf->Ln();
        echo "[" . __LINE__ . "]: Building TOP 10 if Internet Sites\n";
        top_internet_sites($Number, $type);
        $pdf->Image('/tmp/graph8.png');
    }
    if(is_file('/tmp/graph9.png')) {
        echo " [" . __LINE__ . "]: Building Category section\n";
        $pdf->AddPage();
        echo "[" . __LINE__ . "]: Page 9\n";
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "TOP Categories");
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular', '', 12);
        $pdf->Write(7, "TOP 5 Categories that consume your bandwidth.");
        $pdf->Ln();
        $pdf->Image('/tmp/graph9.png');
    }

    echo "[".__LINE__."]: dangerous_categories\n";
    dangerous_categories($Number,$type);

    if(is_file("/tmp/dang1.png")){
        $pdf->AddPage();
        echo "[".__LINE__."]: Page 10\n";
        $pdf->SetFont('Lato-Bold','',18);
        $pdf->Write(7,"Dangerous categories");
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular','',12);
        $pdf->Write(7,"Here the number of sites categorized in dangerous categories");
        $pdf->Ln();$pdf->Ln();
        $pdf->Image('/tmp/dang1.png');
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular','',12);
        $pdf->Write(7,"List of web sites");
        $pdf->Ln();
        echo "[".__LINE__."]: dangerous_categories_dump\n";
        $pdf=dangerous_categories_dump($pdf,$Number,$type);
    }


    echo "[".__LINE__."]: top_blocked_sites\n";
    top_blocked_sites($Number,$type);
    echo "[".__LINE__."]: top_blocked_sites_why\n";
    top_blocked_sites_why($Number,$type);
    echo "[".__LINE__."]: top_blocked_categories\n";
    top_blocked_categories($Number,$type);

    if(is_file("/tmp/block1.png")){
        $pdf->AddPage();
        echo "[".__LINE__."]: Page 11\n";
        $pdf->SetFont('Lato-Bold','',18);
        $pdf->Write(7,"TOP filtered sites");
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular','',12);
        $pdf->Write(7,"TOP 15 of banned internet sites by the proxy");
        $pdf->Ln();
        $pdf->Image('/tmp/block1.png');
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular','',12);
        $pdf->Write(7,"Type of filtered websites");
        $pdf->Ln();
        $pdf->Image('/tmp/block1.png');
    }
    if(is_file("/tmp/block3.png")) {
        echo "[".__LINE__."]: Page 12\n";
        $pdf->AddPage();
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "TOP filtered categories");
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular', '', 12);
        $pdf->Write(7, "TOP 10 of categories of banned Internet sites");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Image('/tmp/block3.png');
    }


    echo "Writing PDF $targetpath\n";
    $pdf->Output("F",$targetpath);
    $pdf->close();
    echo "Writing PDF $targetpath done\n";
    return true;

}

function Readablesize($SUM){
    $SUM_unit="Bytes";
    if($SUM>1024){
        $SUM=$SUM/1024;
        $SUM_unit="KB";
    }
    if($SUM>1024){
        $SUM=$SUM/1024;
        $SUM_unit="MB";
    }
    if($SUM>1024){
        $SUM=$SUM/1024;
        $SUM_unit="GB";
    }
    return round($SUM,2).$SUM_unit;
}

function line_internet_sites($Number,$type){
    $q=new postgres_sql();

    if($type=="week"){
        $date_part="date_part('week', zdate)='$Number'";
    }
    if($type=="month"){
        $date_part="date_part('month', zdate)='$Number'";
    }

    $results=$q->QUERY_SQL("SELECT date_part('day',zdate) as zdate, siteid FROM statscom 
        WHERE $date_part group by zdate,siteid ORDER by zdate");

    $HOURLY=array();
    $MUN=array();
    $SUMUsers=array();

    while ($ligne = pg_fetch_assoc($results)) {

        $date       = $ligne["zdate"];
        $siteid     = $ligne["siteid"];

        if(!isset($MUN[$date][$siteid])){$MUN[$date][$siteid]=true;}
        if(!isset($SUMUsers[$siteid])){$SUMUsers[$siteid]=true;}
    }

    $fileName="/tmp/graph6.png";
    $graph = new artica_graphs($fileName);

    foreach ($MUN as $time=>$sites){
        $graph->ydata[] = count($sites);
        $graph->xdata[] = $time;

    }

    $graph->title="Number of Internet sites each day";
    $graph->x_title="Minutes";
    $graph->y_title="Websites";
    $graph->filename=$fileName;
    $graph->width=750;
    $graph->height=400;;
    $graph->line_green();
    return count($SUMUsers);

}
function SiteIntegerToString($siteid){

    if(isset($GLOBALS[$siteid])){return $GLOBALS[$siteid];}
    $q = new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT sitename FROM statscom_websites WHERE siteid=$siteid");
    $GLOBALS[$siteid]=$ligne["sitename"];
    return $GLOBALS[$siteid];

}

function top_internet_sites($Number,$type){
    $q = new postgres_sql();

    if($type=="week"){
        $date_part="date_part('week', zdate)='$Number'";
    }
    if($type=="month"){
        $date_part="date_part('month', zdate)='$Number'";
    }



    $results = $q->QUERY_SQL("SELECT statscom_websites.familysite,SUM(statscom.size) as size FROM statscom,statscom_websites 
        WHERE $date_part AND statscom_websites.siteid=statscom.siteid
        group by statscom_websites.familysite ORDER by size DESC LIMIT 100");

    $fileName = "/tmp/graph8.png";
    $graph = new artica_graphs($fileName);
    $ARR=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $sitename=$ligne["familysite"];
        $size=intval($ligne["size"]);

        if(isset($ARR[$sitename])){
            $ARR[$sitename]=$ARR[$sitename]+$size;
            continue;
        }
        $ARR[$sitename]=$size;


    }
    $c=0;
    foreach ($ARR as $sitename=>$size){
        $size=$size/1024;
        $size=$size/1024;
        $size=round($size);
        $graph->ydata[]=$sitename;
        $graph->xdata[]=$size;
        $c++;
        if($c==10){break;}
    }

    $graph->title="Top Web sites";
    $graph->x_title="MB";
    $graph->y_title="Sites";
    $graph->filename=$fileName;
    $graph->width=750;
    $graph->height=750;
    $graph->Unit="MB";
    $graph->pieFlat();



}

function top_of_categories($Number,$type){
    $catz=new mysql_catz();
    $q = new postgres_sql();

    if($type=="week"){
        $date_part="date_part('week', zdate)='$Number'";
    }
    if($type=="month"){
        $date_part="date_part('month', zdate)='$Number'";
    }


    $results = $q->QUERY_SQL("SELECT SUM(statscom.size) as size,statscom_websites.category FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND  $date_part GROUP BY category ORDER BY size DESC LIMIT 8");


    $fileName = "/tmp/graph9.png";
    $graph = new artica_graphs($fileName);
    $c=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $size=$ligne["size"];
        $size=$size/1024;
        $size=$size/1024;
        $size=round($size);
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $graph->ydata[]=$category;
        $graph->xdata[]=$size;
        $c++;
        if($c<4){
            $GLOBALS["CATEGORIES_TEXT"][]=$category;
        }

    }
    $graph->title=null;
    $graph->x_title="MB";
    $graph->y_title="Sites";
    $graph->filename=$fileName;
    $graph->width=700;
    $graph->height=750;
    $graph->Unit="MB";
    $graph->BarFlat();
}

function dangerous_categories_dump($pdf,$Number,$type){
    $catz=new mysql_catz();
    $q = new postgres_sql();
    $required=array(41,92,105,111,135,140,149);
    $addsql=array();

    if($type=="week"){
        $date_part="date_part('week', zdate)='$Number'";
    }
    if($type=="month"){
        $date_part="date_part('month', zdate)='$Number'";
    }


    foreach ($required as $catnum){

        $addsql[]="(statscom_websites.category=$catnum)";
    }
    $OR=@implode(" OR ",$addsql);
    $sql="SELECT statscom_websites.familysite,statscom_websites.category FROM statscom,statscom_websites WHERE $date_part AND statscom_websites.siteid=statscom.siteid  AND ($OR) group by statscom_websites.familysite,category ORDER by familysite ASC";
    $results=$q->QUERY_SQL($sql);


    $pdf->SetFont('Lato-Regular','',7);
    while ($ligne = pg_fetch_assoc($results)) {
        $category = $catz->CategoryIntToStr($ligne["category"]);
        $sitename = $ligne["familysite"];


        $pdf->Cell(50, 4, $sitename, 1, 0, 'R');
        $pdf->Cell(50, 4, $category, 1, 0, 'R');
        $pdf->Ln();
    }
    $pdf->Cell(100,0,'','T');

    return $pdf;
}

function dangerous_categories($Number,$type){

    if($type=="week"){
        $date_part="date_part('week', zdate)='$Number'";
    }
    if($type=="month"){
        $date_part="date_part('month', zdate)='$Number'";
    }

    $fileName = "/tmp/dang1.png";

    $catz=new mysql_catz();
    $q = new postgres_sql();


    if(is_file("$fileName")){@unlink($fileName);}
    $required=array(41,92,105,111,135,140,149);
    $addsql=array();
    foreach ($required as $catnum){

        $addsql[]="(statscom_websites.category=$catnum)";
    }

    $OR=@implode(" OR ",$addsql);
    $sql="SELECT COUNT(statscom.*) as tcount ,statscom_websites.category FROM statscom,statscom_websites WHERE $date_part AND statscom_websites.siteid=statscom.siteid  AND ($OR) group by category ORDER by tcount DESC";
    $results = $q->QUERY_SQL($sql);

    if($GLOBALS["DEBUG"]){echo $sql."\n";}

    if(!$q->ok){echo $q->mysql_error."\n";}

    $c=0;
    $graph = new artica_graphs($fileName);
    while ($ligne = pg_fetch_assoc($results)) {
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $Count=$ligne["tcount"];
        $graph->ydata[]=$category;
        $graph->xdata[]=$Count;
        if($GLOBALS["DEBUG"]){echo "$category: $Count\n";}
        $c++;
    }

    if($c<1){return;}

    $graph->title="Dangerous categories, number of sites";
    $graph->x_title="hits";
    $graph->y_title="Sites";
    $graph->filename=$fileName;
    $graph->width=710;
    $graph->height=450;
    $graph->Unit="Requests";
    $graph->BarFlat();
}

function top_blocked_sites($Number,$type){

    if($type=="week"){
        $date_part="date_part('week', zdate)='$Number'";
    }
    if($type=="month"){
        $date_part="date_part('month', zdate)='$Number'";
    }

    $fileName = "/tmp/block1.png";
    $sql="SELECT SUM(statsblocks.hits) as hits,statscom_websites.familysite
     FROM statscom_websites,statsblocks WHERE statscom_websites.siteid=statsblocks.siteid AND $date_part GROUP BY familysite ORDER BY hits DESC LIMIT 15";
    if(is_file("$fileName")){@unlink($fileName);}

    $q = new postgres_sql();
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}

    $c=0;
    $graph = new artica_graphs($fileName);
    while ($ligne = pg_fetch_assoc($results)) {
        $sitename=$ligne["familysite"];
        $hits=$ligne["hits"];
        $graph->ydata[]=$sitename;
        $graph->xdata[]=$hits;
        $c++;
    }

    if($c<2){return;}

    $graph->title="Top Blocked sites";
    $graph->x_title="hits";
    $graph->y_title="Sites";
    $graph->filename=$fileName;
    $graph->width=710;
    $graph->height=450;
    $graph->Unit="Requests";
    $graph->pieFlat();
}

function top_blocked_categories($Number,$type){


    if($type=="week"){
        $date_part="date_part('week', zdate)='$Number'";
    }
    if($type=="month"){
        $date_part="date_part('month', zdate)='$Number'";
    }

    $fileName   = "/tmp/block3.png";
    $graph      = new artica_graphs($fileName);
    $q          = new postgres_sql();
    $catz       = new mysql_catz();

    $sql="SELECT SUM(statsblocks.hits) as hits,statscom_websites.category FROM statsblocks,statscom_websites
    WHERE statscom_websites.siteid=statsblocks.siteid AND  $date_part
    GROUP BY statscom_websites.category ORDER BY hits DESC LIMIT 10";

    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}
    $c=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $hits=intval($ligne["hits"]);
        $graph->ydata[]=$category;
        $graph->xdata[]=$hits;
        if($GLOBALS["DEBUG"]){
            echo "$category: $hits hits\n";
        }

        $c++;
    }

    if($c<2){return;}

    $graph->title=null;
    $graph->x_title="hits";
    $graph->y_title="Sites";
    $graph->filename=$fileName;
    $graph->width=710;
    $graph->height=450;
    $graph->Unit="";
    $graph->pieFlat();

}

function top_blocked_sites_why($Number,$type){

    if($type=="week"){
        $date_part="date_part('week', zdate)='$Number'";
    }
    if($type=="month"){
        $date_part="date_part('month', zdate)='$Number'";
    }

    $BLOCKED_TYPES[1]="Web Filtering";
    $BLOCKED_TYPES[2]="NoTracks feature";
    $BLOCKED_TYPES[403]="Permission denied";
    $BLOCKED_TYPES[503]="Domain Does not exists";

    $fileName   = "/tmp/block2.png";
    $graph      = new artica_graphs($fileName);
    $q          = new postgres_sql();

    $sql="SELECT SUM(hits) as hits,block
     FROM statsblocks WHERE $date_part GROUP BY block ORDER BY hits";
    if(is_file("$fileName")){@unlink($fileName);}

    $c=0;

    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}

    while ($ligne = pg_fetch_assoc($results)) {
        $why=$BLOCKED_TYPES[$ligne["block"]];
        $hits=$ligne["hits"];
        $graph->ydata[]=$why;
        $graph->xdata[]=$hits;
        if($GLOBALS["DEBUG"]){
            echo "$why: $hits hits\n";
        }

        $c++;
    }

    if($GLOBALS["DEBUG"]){
        echo "$c records\n";
    }
    if($c<2){

        if($GLOBALS["DEBUG"]){echo "$c < 2 --> Aborting...\n";}
        return;}

    $graph->title="Why sites are Blocked ?";
    $graph->x_title="hits";
    $graph->y_title="reason";
    $graph->filename=$fileName;
    $graph->width=710;
    $graph->height=450;
    $graph->Unit="";
    $graph->pieFlat();
    if($GLOBALS["DEBUG"]){echo "$fileName done...\n";}

}


function FormatNumber($number, $decimals = 0, $thousand_separator = ' ', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}