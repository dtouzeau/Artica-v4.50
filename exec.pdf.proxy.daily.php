<?php

include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.artica.graphs.inc');
include_once(dirname(__FILE__).'/ressources/externals/fpdf/fpdf.php');
include_once(dirname(__FILE__).'/ressources/class.statscom-msmtp.inc');
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");

if($argv[1]=="--schedule"){schedule($argv[2]);}
if($argv[1]=="--top"){dangerous_categories($argv[2]);}

function build_progress_schedule($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/statscom.schedule.progres";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($ARRAY["PROGRESS_FILE"], serialize($array));
    @chmod($ARRAY["PROGRESS_FILE"],0755);
}

function excel_not_categorized($taskid){
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM pdf_reports WHERE ID=$taskid");
    $description    = $ligne["TimeDescription"];
    $ID             = $ligne["ID"];
    $subject        = $ligne["subject"];
    $recipients     = $ligne["recipients"];

    $date=date("Y-m-d");
    if(!is_dir("/home/artica/SQLITE/PDF")){@mkdir("/home/artica/SQLITE/PDF",0755,true);}
    $report_path="/home/artica/SQLITE/PDF/NoCategorized-$date-$ID.csv";

    $q=new postgres_sql();
    build_progress_schedule(50,"{building_report}");
    $results=$q->QUERY_SQL("SELECT sitename,familysite,hitstot,sizetot FROM statscom_websites WHERE category=0 ORDER BY sizetot DESC");
    if(!$q->ok){
        echo $q->mysql_error;
        build_progress_schedule(110,"{building_report} {failed}");
        return false;
    }
    $file = fopen($report_path, 'w');
    if(!$file){
        echo "Unable to open $report_path for write\n";
        build_progress_schedule(110,"{building_report} {failed}");
        return false;
    }
    fputcsv($file, array("sitename", "familysite", 'Hits', 'Size','bytes'));

    $i=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $i++;
        $Size=$ligne["sizetot"];
        $size=FormatBytes($Size/1024,true);
        fputcsv($file,array($ligne["sitename"],$ligne["familysite"],$ligne["hitstot"],$size,$ligne["sizetot"]));

    }

    fclose($file);
    echo "$i rows...\n";
    if($i==0){
        build_progress_schedule(100,"{success}");
        @unlink($report_path);
        return false;
    }

    $subject=str_replace("%tot",$i,$subject);
    build_progress_schedule(80,"{sending_message}");
    $smtp=new statscom_msmtp();
    $smtp->recipient=$recipients;
    $smtp->Subject=$subject;
    $smtp->Body=$description;
    if(!$smtp->Send($description,$report_path)){
        @unlink($report_path);
        build_progress_schedule(110,"{error_while_sending_message}");
        squid_admin_mysql(1,"CSV Reports Failed SMTP $smtp->smtp_error",null,__FILE__,__LINE__);
        return false;
    }
    @unlink($report_path);
    build_progress_schedule(100,"{success}");


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

    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM pdf_reports WHERE ID=$taskid");
    $description=$ligne["TimeDescription"];
    $ID=$ligne["ID"];
    $TaskType=$ligne["TaskType"];
    $querydate=date("Y-m-d");
    $php=$unix->LOCATE_PHP5_BIN();
    $local_path="/usr/share/artica-postfix/PDF/$taskid.pdf";
    if(is_file($local_path)){@unlink($local_path);}

    echo "Task Type.......: $TaskType [".__LINE__."]\n";
    CleanImages();
    if($TaskType==0) {
        $time = strtotime("yesterday");
        $querydate=date("Y-m-d",$time);
    }
    if($TaskType==2){
        excel_not_categorized($taskid);
        return true;
    }
    if($TaskType==3){
        system("$php /usr/share/artica-postfix/exec.pdf.proxy.weekly.php --schedule $taskid");
        return true;
    }
    if($TaskType==4){
        system("$php /usr/share/artica-postfix/exec.pdf.proxy.weekly.php --schedule $taskid");
        return true;
    }
    if($TaskType==5){
        echo "Run.....: /usr/share/artica-postfix/exec.pdf.proxy.weekly.php --schedule $taskid\n [".__LINE__."]\n";
        system("$php /usr/share/artica-postfix/exec.pdf.proxy.weekly.php --schedule $taskid");
        return true;
    }
    if($TaskType==6){
        system("$php /usr/share/artica-postfix/exec.pdf.proxy.weekly.php --schedule $taskid");
        echo "Run.....: /usr/share/artica-postfix/exec.pdf.proxy.weekly.php --schedule $taskid\n [".__LINE__."]\n";
        return true;
    }
    if($TaskType==7){
        system("$php /usr/share/artica-postfix/exec.pdf.proxy.sync.php --schedule $taskid");
        echo "Run.....: /usr/share/artica-postfix/exec.pdf.proxy.sync.php --schedule $taskid\n [".__LINE__."]\n";
        return true;
    }


    $subject=$ligne["subject"];
    $recipients=$ligne["recipients"];
    echo "Subject........: $subject [".__LINE__."]\n";

    $report_path="/home/artica/SQLITE/PDF/$querydate-$ID.pdf";

    if($GLOBALS["DEBUG"]){
        $report_path=PROGRESS_DIR."/PDF.pdf";
    }
    if(!is_dir("/home/artica/SQLITE/PDF")){@mkdir("/home/artica/SQLITE/PDF",0755,true);}
    if(is_file($report_path)){@unlink($report_path);}

    echo "Query Date.....: $querydate\n";
    echo "PDF Report.....: $report_path\n";


    build_progress_schedule(50,"{building_report}");
    build_daily_report($querydate,$subject,$description,$report_path);

    if($GLOBALS["DEBUG"]){
        echo "$report_path success\n";
        return false;}

    if(!is_file($report_path)){
        build_progress_schedule(110,"{building_report} {failed}");
        squid_admin_mysql(1,"Unable to build daily report [$subject]",null,__FILE__,__LINE__);
        return false;
    }

    replicate_pdf($local_path,$report_path);
    build_progress_schedule(80,"{sending_message}");
    $smtp=new statscom_msmtp();
    $smtp->recipient=$recipients;
    $smtp->Subject=$subject;
    $smtp->Body=$description;
    if(!$smtp->Send($description,$report_path)){
        build_progress_schedule(110,"{error_while_sending_message}");
        squid_admin_mysql(1,"PDF Reports Failed SMTP $smtp->smtp_error",null,__FILE__,__LINE__);
        return false;
    }
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




function build_daily_report($requested_date,$subject,$description,$targetpath){
    $q=new postgres_sql();
    $tpl=new template_admin();
    if($subject==null){
        $subject="Daily report";
    }
    $results=$q->QUERY_SQL("SELECT zdate, SUM(size) as size, SUM(hits) as hits FROM statscom 
        WHERE date(zdate)='$requested_date' group by zdate ORDER by zdate");
    if(!$q->ok){
        echo $q->mysql_error."\n";
        squid_admin_mysql(0,"Fatal, unable to build PDF report PostgreSQL error",
            $q->mysql_error,__FILE__,__LINE__);
        return;}

    $SUM=0;
    $HITS=0;
    $HOURLY=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["size"];
        $SUM  = $SUM + intval($ligne["size"]);
        $HITS = $HITS + intval($ligne["hits"]);
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);
        $stime=strtotime($ligne["zdate"]);
        $date=date("H:i",$stime);
        $tb=explode(":",$date);
        if(!isset($HOURLY[$tb[0]])){
            $HOURLY[$tb[0]]=intval($ligne["size"]);
        }else{
            $HOURLY[$tb[0]] = $HOURLY[$tb[0]]+intval($ligne["size"]);
        }
        $xdata[]=$date;
        $ydata[]=$DOWN;
    }

    $fileName="/tmp/graph1.png";
    $graph = new artica_graphs($fileName);
    $graph->ydata=$ydata;
    $graph->xdata=$xdata;
    $graph->title="Bandwidth each 10 minutes";
    $graph->x_title="Minutes";
    $graph->y_title="MB";
    $graph->filename=$fileName;
    $graph->width=750;
    $graph->height=400;;
    $graph->line_green();

    $fileName="/tmp/graph2.png";
    $graph = new artica_graphs($fileName);
    foreach ($HOURLY as $Hour=>$size) {
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $graph->ydata[] = $size;
        $graph->xdata[] = $Hour;
    }

    $graph->title="Hourly Bandwidth usage";
    $graph->x_title="Hour";
    $graph->y_title="MB";
    $graph->filename=$fileName;
    $graph->width=750;
    $graph->height=400;;
    $graph->line_green();


    $results=$q->QUERY_SQL("SELECT zdate, username,ipaddr,mac FROM statscom 
        WHERE date(zdate)='$requested_date' group by zdate,username,ipaddr,mac ORDER by zdate");

    $HOURLY=array();
    $MUN=array();
    $SUMUsers=array();

    while ($ligne = pg_fetch_assoc($results)) {
        $stime=strtotime($ligne["zdate"]);
        $date       = date("H:i",$stime);
        $username   = $ligne["username"];
        $ipaddr     = $ligne["ipaddr"];
        if($GLOBALS["resolveIP2HOST"]==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }
        $mac        = $ligne["mac"];
        $tb=explode(":",$date);
        $Hourly=$tb[0];
        $md5=md5($username.$ipaddr.$mac);
        if(!isset($MUN[$date][$md5])){$MUN[$date][$md5]=true;}
        if(!isset($HOURLY[$Hourly][$md5])){$HOURLY[$Hourly][$md5]=true;}
        if(!isset($SUMUsers[$md5])){$SUMUsers[$md5]=true;}
    }

    $fileName="/tmp/graph3.png";
    $graph = new artica_graphs($fileName);

    foreach ($MUN as $time=>$users){
        $graph->ydata[] = count($users);
        $graph->xdata[] = $time;

    }

    $graph->title="Number of users each 10 minutes";
    $graph->x_title="Minutes";
    $graph->y_title="Users";
    $graph->filename=$fileName;
    $graph->width=750;
    $graph->height=400;;
    $graph->line_green();


    $fileName="/tmp/graph4.png";
    $graph = new artica_graphs($fileName);

    foreach ($HOURLY as $time=>$users){
        $graph->ydata[] = count($users);
        $graph->xdata[] = $time;
    }

    $graph->title="Hourly Number of users";
    $graph->x_title="Hour";
    $graph->y_title="Users";
    $graph->filename=$fileName;
    $graph->width=750;
    $graph->height=400;;
    $graph->line_green();


    $results=$q->QUERY_SQL("SELECT SUM(size) as size,username,ipaddr,mac FROM statscom 
        WHERE date(zdate)='$requested_date' group by username,ipaddr,mac ORDER BY size DESC LIMIT 15");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $fileName="/tmp/graph5.png";
    $graph = new artica_graphs($fileName);

    while ($ligne = pg_fetch_assoc($results)) {
        $DOWN = $ligne["size"];
        $DOWN = $DOWN / 1024;
        $DOWN = $DOWN / 1024;
        $DOWN = round($DOWN);
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        if($GLOBALS["resolveIP2HOST"]==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }
        $mac=$ligne["mac"];
        $key=null;
        if($username<>null){$key=$username;}
        if($key==null){
            if($ipaddr<>null){
                $key=$ipaddr;
            }
        }
        if($key==null){
            if($mac<>null){$key=$mac;}
            if($key=="00:00:00:00:00:00"){$key=null;}
        }

        if($key==null){continue;}
        echo "$key -> $DOWN\n";
        $graph->ydata[]=$key;
        $graph->xdata[]=$DOWN;

    }
    $graph->title="Top Users";
    $graph->x_title="Hour";
    $graph->y_title="Users";
    $graph->filename=$fileName;
    $graph->width=750;
    $graph->height=750;
    $graph->Unit="MB";
    $graph->pieFlat();


    $pdf = new FPDF();

    top_of_categories($requested_date);

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
    $pdf->Cell( 0, 15, $requested_date, 0, 0, 'R' );
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
    $line_internet_sites=line_internet_sites($requested_date);
    $line_internet_sites=$tpl->FormatNumber($line_internet_sites);
    $line_internet_sites=str_replace("&nbsp;"," ",$line_internet_sites);

    $pdf->AddPage();
    $pdf->SetFont('Lato-Bold','',18);
    $pdf->Write(7,"Introduction for this daily report of $requested_date");
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
        if(is_file('/tmp/graph2.png')) {
            $pdf->AddPage();
            $pdf->SetFont('Lato-Bold', '', 18);
            $pdf->Write(7, "Bandwidth usage");
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Lato-Regular', '', 12);
            $pdf->Ln();
            $pdf->Write(7, "The picture bellow display the bandwidth usage from $requested_date 00:00 to $requested_date 00:59 with 10 minutes interval");
            $pdf->Ln();
            $pdf->Image('/tmp/graph1.png');
            $pdf->Image('/tmp/graph2.png');
        }
    }

    $ADDUSER=true;
    if(!is_file("/tmp/graph3.png") AND !is_file("/tmp/graph4.png") ){
        $ADDUSER=false;
    }

    if($ADDUSER) {
        $pdf->AddPage();
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "Your users");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular', '', 12);
        $pdf->Write(7, "The picture bellow display the number of users");
        $pdf->Ln();
        $pdf->Ln();
        if (is_file("/tmp/graph3.png")) {
            $pdf->Image('/tmp/graph3.png');
        }
        if (is_file("/tmp/graph4.png")) {
            $pdf->Image('/tmp/graph4.png');
        }
    }

    if (is_file("/tmp/graph5.png")) {
        $pdf->AddPage();
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "Top 15 of your users");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Image('/tmp/graph5.png');
    }
    $hideMacs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideMacs'));
    $hideUnkownMembers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideUnkownMembers'));
    $pdf->AddPage();
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


    $results=$q->QUERY_SQL("SELECT SUM(size) as size,SUM(hits) as hits,username,ipaddr,mac FROM statscom WHERE date(zdate)='$requested_date' 
                group by username,ipaddr,mac ORDER BY size DESC");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}

    $pdf->SetFont('Lato-Regular','',7);

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
        $cellWidth = $pdf->GetStringWidth($ipaddr);
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

    $buildpage=true;
    if(!is_file("/tmp/graph6.png")){$buildpage=false;}
    if(!is_file("/tmp/graph7.png")){$buildpage=false;}
    echo "Building Internet Graphs sites [".__LINE__."]\n";

    if($buildpage) {
        $pdf->AddPage();
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "Internet Web sites");
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Image('/tmp/graph6.png');
        $pdf->Image('/tmp/graph7.png');
    }

    echo "Building Internet Graphs Table [".__LINE__."]\n";
    $pdf->AddPage();
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
    $results = $q->QUERY_SQL("SELECT statscom_websites.sitename,SUM(statscom.size) as size,
                            SUM(statscom.hits) as hits,statscom_websites.category
                            FROM statscom,statscom_websites
                            WHERE date(zdate)='$requested_date'
                            AND statscom_websites.siteid=statscom.siteid 
                            group by sitename,category ORDER by size DESC");

    if(!$q->ok){
        echo $q->mysql_error."\n";
        $pdf->Text(7,$q->mysql_error);}
    $pdf->SetFont('Lato-Regular','',7);
    while ($ligne = pg_fetch_assoc($results)) {
        if($ligne["size"]<1048576){continue;}
        $size = $ligne["size"];
        $size = FormatBytes($size/1024,true);
        $sitename = $ligne["sitename"];
        $hits=FormatNumber($ligne["hits"],0,' ');
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $pdf->Cell(20, 4, $size, 1, 0, 'R');
        $pdf->Cell(20, 4, $hits, 1, 0, 'R');
        $pdf->Cell(40, 4, $category, 1, 0, 'R');
        $pdf->Cell(100, 4, "$sitename", 1, 0, 'L');
        $pdf->Ln();
    }
    $pdf->Cell(20+20+40+100,0,'','T');


    top_internet_sites($requested_date);
    if(is_file("/tmp/graph8.png")){
        $pdf->AddPage();
        $pdf->SetFont('Lato-Bold','',18);
        $pdf->Write(7,"TOP 15 Web sites");
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular','',12);
        $pdf->Write(7,"TOP Internet sites that consume your bandwidth.");
        $pdf->Ln();$pdf->Ln();
        echo "Building TOP 10 if Internet Sites [".__LINE__."]\n";
        $pdf->Image('/tmp/graph8.png');
    }
    if(is_file("/tmp/graph9.png")) {
        echo "Building Category section [" . __LINE__ . "]\n";
        $pdf->AddPage();
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "TOP Categories");
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular', '', 12);
        $pdf->Write(7, "TOP 5 Categories that consume your bandwidth.");
        $pdf->Ln();
        $pdf->Image('/tmp/graph9.png');
    }

    dangerous_categories($requested_date);

    if(is_file("/tmp/dang1.png")){
        $pdf->AddPage();
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
        $pdf=dangerous_categories_dump($pdf,$requested_date);
    }



    top_blocked_sites($requested_date);
    top_blocked_sites_why($requested_date);
    top_blocked_categories($requested_date);
    if(is_file("/tmp/block1.png")) {
        $pdf->AddPage();
        $pdf->SetFont('Lato-Bold', '', 18);
        $pdf->Write(7, "TOP filtered sites");
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular', '', 12);
        $pdf->Write(7, "TOP 15 of banned internet sites by the proxy");
        $pdf->Ln();
        $pdf->Image('/tmp/block1.png');
    }

    if(is_file("/tmp/block2.png")) {
        $pdf->Ln();
        $pdf->SetFont('Lato-Regular','',12);
        $pdf->Write(7,"Type of filtered websites");
        $pdf->Ln();
        $pdf->Image('/tmp/block2.png');
    }
    if(is_file("/tmp/block3.png")) {
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



    $pdf->Output("F",$targetpath);
    $pdf->close();

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

function line_internet_sites($requested_date){
    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT zdate, siteid FROM statscom 
        WHERE date(zdate)='$requested_date' group by zdate,siteid ORDER by zdate");

    $HOURLY=array();
    $MUN=array();
    $SUMUsers=array();

    while ($ligne = pg_fetch_assoc($results)) {
        $stime=strtotime($ligne["zdate"]);
        $date       = date("H:i",$stime);
        $siteid     = $ligne["siteid"];
        $tb=explode(":",$date);
        $Hourly=$tb[0];

        if(!isset($MUN[$date][$siteid])){$MUN[$date][$siteid]=true;}
        if(!isset($HOURLY[$Hourly][$siteid])){$HOURLY[$Hourly][$siteid]=true;}
        if(!isset($SUMUsers[$siteid])){$SUMUsers[$siteid]=true;}
    }

    $fileName="/tmp/graph6.png";
    $graph = new artica_graphs($fileName);

    foreach ($MUN as $time=>$sites){
        $graph->ydata[] = count($sites);
        $graph->xdata[] = $time;

    }

    $graph->title="Number of Internet sites each 10 minutes";
    $graph->x_title="Minutes";
    $graph->y_title="Websites";
    $graph->filename=$fileName;
    $graph->width=750;
    $graph->height=400;;
    $graph->line_green();


    $fileName="/tmp/graph7.png";
    $graph = new artica_graphs($fileName);

    foreach ($HOURLY as $time=>$sites){
        $graph->ydata[] = count($sites);
        $graph->xdata[] = $time;

    }
    $graph->title="Hourly Number of Internet sites";
    $graph->x_title="Hour";
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

function top_internet_sites($requested_date)
{
    $q = new postgres_sql();
    $results = $q->QUERY_SQL("SELECT date(statscom.zdate) as zdate, statscom_websites.familysite,SUM(statscom.size) as size FROM statscom,statscom_websites 
        WHERE date(statscom.zdate)='$requested_date' 
        AND statscom_websites.siteid=statscom.siteid
        group by statscom.zdate,statscom_websites.familysite ORDER by size DESC LIMIT 100");

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

function top_of_categories($requested_date){
    $catz=new mysql_catz();
    $q = new postgres_sql();
    $results = $q->QUERY_SQL("SELECT date(statscom.zdate) as date,SUM(statscom.size) as size,statscom_websites.category FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND  date(statscom.zdate)='$requested_date' GROUP BY category,date ORDER BY size DESC LIMIT 8");


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

function dangerous_categories_dump($pdf,$requested_date){
    $catz=new mysql_catz();
    $q = new postgres_sql();
    $required=array(41,92,105,111,135,140,149);
    $addsql=array();
    foreach ($required as $catnum){

        $addsql[]="(statscom_websites.category=$catnum)";
    }
    $OR=@implode(" OR ",$addsql);
    $sql="SELECT statscom_websites.sitename,date(statscom.zdate) as zdate,statscom_websites.category FROM statscom,statscom_websites WHERE date(statscom.zdate)='$requested_date'  AND statscom_websites.siteid=statscom.siteid  AND ($OR) group by statscom_websites.sitename,date(statscom.zdate),category ORDER by sitename ASC";
    $results=$q->QUERY_SQL($sql);


    $pdf->SetFont('Lato-Regular','',7);
    while ($ligne = pg_fetch_assoc($results)) {
        $category = $catz->CategoryIntToStr($ligne["category"]);
        $sitename = $ligne["sitename"];


        $pdf->Cell(50, 4, $sitename, 1, 0, 'R');
        $pdf->Cell(50, 4, $category, 1, 0, 'R');
        $pdf->Ln();
    }
    $pdf->Cell(100,0,'','T');

    return $pdf;
}

function dangerous_categories($requested_date){
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
    $sql="SELECT COUNT(statscom.*) as tcount ,date(statscom.zdate) as zdate,statscom_websites.category FROM statscom,statscom_websites WHERE date(statscom.zdate)='$requested_date'  AND statscom_websites.siteid=statscom.siteid  AND ($OR) group by date(statscom.zdate),category ORDER by tcount DESC";
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

function top_blocked_sites($requested_date){
    $fileName = "/tmp/block1.png";
    $sql="SELECT date(statsblocks.zdate) as date, SUM(statsblocks.hits) as hits,statscom_websites.sitename
     FROM statscom_websites,statsblocks WHERE statscom_websites.siteid=statsblocks.siteid AND date(statsblocks.zdate)='$requested_date' GROUP BY sitename,date ORDER BY hits DESC LIMIT 15";
    if(is_file("$fileName")){@unlink($fileName);}

    $q = new postgres_sql();
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}

    $c=0;
    $graph = new artica_graphs($fileName);
    while ($ligne = pg_fetch_assoc($results)) {
        $sitename=$ligne["sitename"];
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

function top_blocked_categories($requested_date){

    $fileName   = "/tmp/block3.png";
    $graph      = new artica_graphs($fileName);
    $q          = new postgres_sql();
    $catz       = new mysql_catz();

    $sql="SELECT date(statsblocks.zdate) as date, SUM(statsblocks.hits) as hits,statscom_websites.category FROM statsblocks,statscom_websites
    WHERE statscom_websites.siteid=statsblocks.siteid AND   date(statsblocks.zdate)='$requested_date'
    GROUP BY date(statsblocks.zdate),statscom_websites.category ORDER BY hits DESC LIMIT 10";

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

function top_blocked_sites_why($requested_date){

    $BLOCKED_TYPES[1]="Web Filtering";
    $BLOCKED_TYPES[2]="NoTracks feature";
    $BLOCKED_TYPES[403]="Permission denied";
    $BLOCKED_TYPES[503]="Domain Does not exists";

    $fileName   = "/tmp/block2.png";
    $graph      = new artica_graphs($fileName);
    $q          = new postgres_sql();

    $sql="SELECT date(statsblocks.zdate) as date, SUM(hits) as hits,block
     FROM statsblocks WHERE date(zdate)='$requested_date' GROUP BY block,date ORDER BY hits";
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