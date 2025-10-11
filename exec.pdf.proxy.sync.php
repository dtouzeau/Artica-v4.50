<?php
$GLOBALS["DEBUG"]=false;
define("FPDF_FONTPATH",dirname(__FILE__).'/ressources/externals/fpdf/font');
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.artica.graphs.inc');
include_once(dirname(__FILE__).'/ressources/externals/fpdf/fpdf.php');
include_once(dirname(__FILE__).'/ressources/class.statscom-msmtp.inc');
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');

if(preg_match("#--(debug|verbose)#",implode(" ",$argv),$re)){

    if($re[1]=="verbose"){$GLOBALS["VERBOSE"]=true;}
    $GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--schedule"){schedule($argv[2]);}


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

    echo "Task Type......: $TaskType \"$subject\"\n";
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->AddFont('Lato-Bold','','Lato-Bold.php');
    $pdf->AddFont('Lato-Regular','','Lato-Regular.php');
    $pdf->AddFont('Lato-Thin','','Lato-Thin.php');
    $pdf->SetFont('Lato-Bold','',28);

    $text=$subject;
    $pdf->Text(50,30,$text);
    $pdf->Ln();
    $pdf->SetFont('Lato-Thin','',16);
    $pdf->Text(100,40,date("Y F l d H:i:s"));
    $pdf->Ln();
    $pdf->SetFont('Lato-Thin','',14);
    $pdf->Text(20,60,"$description");
    $logo="/usr/share/artica-postfix/img/logo-doc.png";
    $pdf->Image($logo,140,218);
    $pdf->Ln();
    $pdf->AddPage();
    build_progress_schedule(50,"{building}");
    $pdf=categorization_rate($pdf);
    build_progress_schedule(70,"{building}");
    ProxyAlias();
    build_progress_schedule(90,"{building}");
    $pdf=refresh_categorization($pdf);



    echo "Writing PDF $local_path\n";
    $pdf->Output("F",$local_path);
    $pdf->close();
    echo "Writing PDF $local_path done\n";



    build_progress_schedule(80,"{sending_message}");
    $smtp=new statscom_msmtp();
    $smtp->recipient=$recipients;
    $smtp->Subject=$subject;
    $smtp->Body=$description;
    if(!$smtp->Send($description,$local_path)){
        build_progress_schedule(110,"{error_while_sending_message}");
        squid_admin_mysql(1,"Synchronize Report Failed SMTP $smtp->smtp_error",null,__FILE__,__LINE__);
        return false;
    }

    build_progress_schedule(100,"{success}");


}

function ProxyAlias(){

    $q=new postgres_sql();
    $sql="SELECT mac,ipaddr,proxyalias,hostname FROM hostsnet WHERE length(proxyalias) >0;";
    $results = $q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        if ($ligne["mac"] == "00:00:00:00:00:00") {
            continue;
        }
        if (!IsPhysicalAddress($ligne["mac"])) {
            continue;
        }
        $proxyalias = $ligne["proxyalias"];
        echo "{$ligne["mac"]} = {$ligne["proxyalias"]}\n";
        $q->QUERY_SQL("UPDATE statscom SET username ='$proxyalias' WHERE mac='{$ligne["mac"]}'");
        $q->QUERY_SQL("UPDATE statscom_days SET username ='$proxyalias' WHERE mac='{$ligne["mac"]}'");
        $q->QUERY_SQL("UPDATE statscom_users SET username ='$proxyalias' WHERE mac='{$ligne["mac"]}'");
        $q->QUERY_SQL("UPDATE statscom_husers SET username ='$proxyalias' WHERE mac='{$ligne["mac"]}'");
    }

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


function SiteIntegerToString($siteid){

    if(isset($GLOBALS[$siteid])){return $GLOBALS[$siteid];}
    $q = new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT sitename FROM statscom_websites WHERE siteid=$siteid");
    $GLOBALS[$siteid]=$ligne["sitename"];
    return $GLOBALS[$siteid];

}










function refresh_categorization($pdf){
    $unix       = new unix();
    $q          = new postgres_sql();
    $libmem     = new lib_memcached();
    $mem_ttl    = 172800;
    $statscom_websites  =   $q->COUNT_ROWS("statscom_websites");


    $pdf->SetFont('Lato-Bold','',18);
    $pdf->Write(7,"Re-categorization of your database");
    $pdf->Ln();$pdf->Ln(); $pdf->Ln();$pdf->Ln();
    $pdf->SetFont('Lato-Regular','',14);
    $pdf->Write(7,"Your statistics database handle ".$statscom_websites." Websites.");

    if(!$q->FIELD_EXISTS("statscom_websites","lastseen")){
        $q->QUERY_SQL("ALTER TABLE statscom_websites ADD lastseen bigint default 0");
        if(!$q->ok){
            $pdf->Ln();
            $pdf->Write(7,"PostgreSQL error: $q->mysql_error");
            return $pdf;
        }
        $q->create_index("statscom_websites","idx_lastseen",array("lastseen"));
    }

    $lastseen   = time() - 604800;
    $lastseen_text = date("Y l F d",$lastseen);
    $results    = $q->QUERY_SQL("SELECT * FROM statscom_websites WHERE category > 0 AND lastseen < $lastseen");
    if(!$q->ok){
        $pdf->Ln();
        $pdf->Write(7,"PostgreSQL error: $q->mysql_error");
        return $pdf;
    }
    $catz       = new mysql_catz();
    $TimeStart  = time();
    $GLOBALS["NOCACHE"]=true;
    $TotalRows=pg_num_rows($results);




    $pdf->Ln();
    $pdf->Write(7,"This task handle websites not analyzed since $lastseen_text ( about $TotalRows records");

    if($TotalRows==0){
        $pdf->Ln();
        $pdf->Write(7,"No website must be synchronized, waiting the next schedule.");
        return $pdf;
    }


    $c=0;

    $percent=0;
    $ContentLine=array();
    $sec_start=time();
    $sec_sites=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $c++;$sec_sites++;
        if($c++>12000){break;}
        $precent_tmp=$c/$TotalRows;
        $precent_tmp=round($precent_tmp*100);
        if($precent_tmp>$percent){
            $percent=$precent_tmp;
            $Numberofsec=time()-$sec_start;
            $sitesSec=round($sec_sites/$Numberofsec);
            echo  "$precent_tmp% $c/$TotalRows {$sitesSec} sites per second - $sec_sites websites in {$Numberofsec} seconds\n";
            $sec_sites=0;$sec_start=time();

        }
        $HIT=false;
        $siteid         = $ligne["siteid"];
        $category_src   = $ligne["category"];
        $sitename       = $ligne["sitename"];
        $hitkey         = md5("RECATZ:$category_src:$sitename");
        $category_hit=$libmem->getKey($hitkey);
        if($libmem->MemCachedFound){
            if($GLOBALS["DEBUG"]){echo "$c) $sitename = $category_src HIT\n";}
            $HIT=true;}else{
            if($GLOBALS["DEBUG"]){echo "$c) $sitename = $category_src MISS\n";}
        }


        if(!$HIT) {
            $new_category = $catz->GET_CATEGORIES($sitename);
            $libmem->saveKey($hitkey,$new_category,$mem_ttl);
        }else{
            $new_category=$category_hit;
        }

        $q->QUERY_SQL("UPDATE statscom_websites SET lastseen=$sec_start WHERE siteid=$siteid");
        if(!$q->ok){echo $q->mysql_error."\n";break;}

        if($new_category==0){continue;}
        if($category_src==$new_category){continue;}
        $category_src_text=$catz->CategoryIntToStr($category_src);
        $new_category_text=$catz->CategoryIntToStr($new_category);
        if( strtolower($category_src_text)==strtolower($new_category_text) ){continue;}
        $zcontent="$sitename was moved from [$category_src_text] to [$new_category_text]";
        $q->QUERY_SQL("UPDATE statscom_websites SET category='$new_category' WHERE siteid='$siteid'");
        if(!$q->ok){
            $pdf->Ln();
            $pdf->Write(7,"PostgreSQL error: $q->mysql_error");
            return $pdf;
        }

        $ContentLine[]=$zcontent;
        if($GLOBALS["DEBUG"]){echo "$zcontent\n";}

    }

    $TimeOff=time();
    $countResults=count($ContentLine);
    $pdf->Ln();
    $pdf->Write(7,"Analyze your database took ".$unix->distanceOfTimeInWords_text($TimeStart,$TimeOff,true));
    if(is_numeric($sitesSec)) {
        $pdf->Ln();
        $pdf->Write(7, "For a rate of $sitesSec websites per second.");
    }
    $pdf->Ln();

    if($countResults>0) {
        $pdf->Write(7, count($ContentLine) . " websites as been reaffected to other categories:");
    }else{
        $pdf->Write(7, "No website was reaffected to other category, Your statistics database is up-to-date");
    }
    $pdf->Ln();
    $pdf->Ln();

    foreach ($ContentLine as $line){
        $pdf->SetFont('Lato-Regular','',10);
        $pdf->Write(7,$line);
        $pdf->Ln();
    }

    return $pdf;

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