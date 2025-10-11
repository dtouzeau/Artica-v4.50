<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");

if($argv[1]=="--import"){xcategorize_import($argv[2],$argv[3],$argv[4],$argv[5]);exit;}
if($argv[1]=="--export"){xcategorize_export($argv[2]);exit;}
if($argv[1]=="--testcatz"){testcategorize($argv[2],$argv[3]);exit;}
if($argv[1]=="--bulk"){bulk_categorize($argv[2]);exit;}



xcategorize($argv[1]);

function bulk_categorize($t){
    $ft=$t;
    $ffname="/usr/share/artica-postfix/ressources/conf/upload/$t";
    $UFDBCAT_TEST_BULK_CATEGORIES=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBCAT_TEST_BULK_CATEGORIES"));
    if(is_file($ffname)){
        $ft=md5($t);
        $UFDBCAT_TEST_BULK_CATEGORIES=@file_get_contents($ffname);
        @unlink($ffname);
        $html[]="<code>$t</code>";
    }

    $GLOBALS["IDENT"]=$ft;

    if(preg_match("#VERBOSE=YES#is",$UFDBCAT_TEST_BULK_CATEGORIES)){$GLOBALS["VERBOSE"]=true;}

    $ff=explode("\n",$UFDBCAT_TEST_BULK_CATEGORIES);
    $count=count($ff);
    echo "$count lines to categorize...\n";
    build_progress("$count Web sites...",1);
    $fam=new squid_familysite();
    $catz=new mysql_catz();
    $categories_descriptions=$catz->categories_descriptions();
    $protos="CONNECT|HEAD|GET|DELETE|POST|LIST|OPTIONS|PATCH|PUT";
    $html[]="<table style='width:100%'>";
    $c=0;$cctat=0;$ccnop=0;$ccreafect=0;
    $ALREADY=array();
    foreach ($ff as $www){
        $category=0;
        $category_text=null;
        $www=trim(strtolower($www));
        $srcline=$www;
        if(strpos($www,"none/000 0 none")>0){continue;}
        if(strpos($www,"error:invalid-request")>0){continue;}
        if(strpos($www,"error:accept-client-connection")>0){continue;}
        
        if(preg_match("#^[0-9\.]+\s+[0-9]+\s+.*?[0-9]+\s+($protos)\s+(.+?)\s+.+?cinfo:([0-9]+)-(.+?);#i",$www,$re)){
            $www=trim($re[2]);
            $category=$re[3];
            $category_text=$re[4];
        }

        if($category==0) {
            if (preg_match("#^[0-9\.]+\s+[0-9]+\s+.*?[0-9]+\s+.*?\s+($protos)\s+(.+?)\s+#i", $www, $re)) {
                $www = trim($re[2]);
            }
        }

        $c++;

        if(preg_match("#^[0-9]+\.[0-9]+(\s|$)#",$www)){
            echo "$www no matches <$srcline>\n";
            continue;
        }


        if($GLOBALS["VERBOSE"]){echo "Scanning -> \"$www\"\n";}
        $parse_url=parse_url($www);
        if(isset($parse_url["host"])){
            $www=$parse_url["host"];
        }
        if(preg_match("#^(.+?)\s+#",$www,$re)){$www=$re[1];}
        if(preg_match("#^(.+?):[0-9]+#",$www,$re)){$www=$re[1];}
        if($GLOBALS["VERBOSE"]){echo "Scanning -> \"$www\"\n";}
        if(strpos($www,">")>0){continue;}
        if(strpos($www,"<")>0){continue;}
        if(strpos($www,"(")>0){continue;}
        if(strpos($www,")")>0){continue;}
        if($www==null){continue;}
        if(isset($ALREADY[$www])){continue;}
        $ALREADY[$www]=array($category,$category_text);

    }
    echo "Final sites = ".count($ALREADY)."\n";
    $d=0;

    $BULK_FINAL=array();
    $Sites_in_array=0;
    foreach ($ALREADY as $www=>$array){
        $category=$array[0];
        $category_text=$array[1];
        if($category>0){
            $cctat++;
            $htmlCatz[]="<tr>";
            $htmlCatz[]="<td width='1%' nowrap>$www:</td>";
            $htmlCatz[]="<td><strong>$category_text</strong></td>";
            $htmlCatz[]="</tr>";
            continue;
        }
        $d++;
        $final[]=$www;
        if(count($final)>300){
            build_progress("{analyze} $d",20);
            $array=$catz->ufdbcat_bulk($final);
            $zArray=unserialize($array["RESPONSE"]);
            $final=array();
            echo "Return sitenames=".count($zArray["sitenames"])."\n";
            foreach ($zArray["sitenames"] as $sitename=>$category){
                $Sites_in_array++;
                $BULK_FINAL[$sitename]=$category;
                $ffinal[]=$sitename;
            }

        }
    }
    if(count($final)>0) {
        build_progress("{analyze} ".count($final),20);
        $array = $catz->ufdbcat_bulk($final);
        $zArray=unserialize($array["RESPONSE"]);
        echo "Return sitenames=".count($zArray["sitenames"])."\n";
        foreach ($zArray["sitenames"] as $sitename => $category) {
            $Sites_in_array++;
            $BULK_FINAL[$sitename] = $category;
            $ffinal[]=$sitename;

        }
    }

    echo "$d finalyzed sites $Sites_in_array in array (".count($BULK_FINAL).")\n";

    foreach ($ffinal as $www){
        $prc=$c/$count;
        $prc=round($prc*100);
        if($prc>20){build_progress("$www",$prc);}
        if(isset($BULK_FINAL[$www])){$category=$BULK_FINAL[$www];}
        if($category==0) {
            echo "Not categorized = $www\n";
            $category = $catz->GET_CATEGORIES($www);
            $category_text=null;
        }
        if($category==1000000){$category=0;}
        if($category>0){
            $cctat++;
            if(isset($categories_descriptions[$category])){
                $category_text=$categories_descriptions[$category]["categoryname"];
            }
            $htmlCatz[]="<tr>";
            $htmlCatz[]="<td width='1%' nowrap>$www:</td>";
            $htmlCatz[]="<td><strong>$category_text</strong></td>";
            $htmlCatz[]="</tr>";
            continue;
        }


        $dom=$fam->GetFamilySites($www);
        if (sizeof(dns_get_record("$dom.")) == 0){
            $ccreafect++;
            $htmlReaffect[]="<tr>";
            $htmlReaffect[]="<td width='1%' nowrap>$www</td>";
            $htmlReaffect[]="<td>$dom</td>";
            $htmlReaffect[]="</tr>";
            continue;
        }


        $ccnop++;
        $htmlNoCatz[]="<tr>";
        $htmlNoCatz[]="<td width='1%' nowrap>$www</td>";
        $htmlNoCatz[]="<td>&nbsp;</td>";
        $htmlNoCatz[]="</tr>";


    }

    build_progress("$www",99);

    if($cctat>0){
        echo "$cctat Categorized sites\n";
        $html[]="<tr>";
        $html[]="<td colspan='2'><H2>$cctat Categorized Websites</H2></td>";
        $html[]="</tr>";
        $html[]=@implode("\n",$htmlCatz);
    }

    if($ccreafect>0){
        $html[]="<tr>";
        $html[]="<td colspan='2'><H2>$ccreafect Reaffected</H2></td>";
        $html[]="</tr>";
        $html[]=@implode("\n",$htmlReaffect);

    }

    if($ccnop>0){
        $html[]="<tr>";
        $html[]="<td colspan='2'><H2>$ccnop unknown Websites</H2></td>";
        $html[]="</tr>";
        $html[]=@implode("\n",$htmlNoCatz);
    }
    $html[]="</table>";

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_TEST_BULK_CATEGORIES_RESULTS",base64_encode(@implode("\n",$html)));

    build_progress("$www",100);

}

function build_progress($text,$pourc){
    if($pourc>120){$pourc=99;}
    echo "{$pourc}% $text\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/categorize.{$GLOBALS["IDENT"]}.progress";
    if(is_numeric($text)){
        $array["POURC"]=$text;
        $array["TEXT"]=$pourc;
    }else{
        $array["POURC"]=$pourc;
        $array["TEXT"]=$text;
    }
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}



function xcategorize_export($category_id=0){
    $unix=new unix();
    if(intval($category_id)==0){return null;}
    $q=new postgres_sql();
    $GLOBALS["IDENT"]=$category_id;
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categoryname,categorytable FROM personal_categories WHERE category_id='$category_id'"));
    $table=$ligne["categorytable"];
    $categoryname=$ligne["categoryname"];
    $tempdir="/home/artica/categories_export";
    if($table==null){
        build_progress("{category} $categoryname $category_id, no table found",110);
        return;
    }

    if(!$q->TABLE_EXISTS($table)){
        build_progress("{category} $categoryname $category_id, $table, no such table",110);
        return;

    }
    $chown=$unix->find_program("chown");
    $rm=$unix->find_program("rm");
    $gzip=$unix->find_program("gzip");
    if(is_dir($tempdir)){system("$rm -rf $tempdir");}

    @mkdir($tempdir,0755,true);
    system("$chown ArticaStats:ArticaStats $tempdir");
    $tmp="$tempdir/$category_id.txt";
    if(is_file($tmp)){@unlink($tmp);}

    build_progress("{compile}: $categoryname {exporting} L.".__LINE__,50);
    $sql="COPY $table TO '$tmp'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        build_progress("{compile}: $categoryname {failed} L.".__LINE__,110);
        @unlink($tmp);
        return;
    }
    build_progress("{compile}: $categoryname {compressing} L.".__LINE__,80);
    shell_exec("$gzip -c $tmp > /usr/share/artica-postfix/ressources/logs/$category_id.gz");
    build_progress("{compile}: $categoryname {compressing}  {success} L.".__LINE__,100);


}

function xcategorize_import($category_id,$filename,$ForceCat=0,$ForceExt=0){
    $unix=new unix();
    $GLOBALS["IDENT"]=$category_id;
    $filepath = dirname(__FILE__) . "/ressources/conf/upload/$filename";
    if(!is_file($filepath)){
        build_progress("$filename no such file",110);
        return;
    }
    if($category_id==0){
        build_progress("Wrong category...",110);
        return;
    }

    if(preg_match("#(.+?)\.gz$#", $filename)) {
        echo "$filename -> gunzip\n";
        $gunzip = $unix->find_program("gunzip");
        $target_file = $unix->FILE_TEMP();
        $cmd = "$gunzip -d -c \"$filepath\" >$target_file 2>&1";
        echo "$cmd\n";
        shell_exec($cmd);
        @unlink($filepath);
        if (!is_file($target_file)) {
            build_progress("uncompress failed", 110);
            return;
        }
        $filepath=$target_file;
        xcategorize_import_perform($category_id,$filepath,$ForceCat,$ForceExt);
        build_progress("{done}", 100);
        return;
    }

    if(preg_match("#(.+?)\.zip$#", $filename)) {
        echo "$filename -> unzip\n";
        $unzip = $unix->find_program("unzip");
        $target_dir = $unix->TEMP_DIR();
        @mkdir("$target_dir/import-$category_id", 0755, true);
        $cmd = "$unzip \"$filepath\" -d $target_dir/import-$category_id 2>&1";
        shell_exec($cmd);
        @unlink($filepath);
        $Files = $unix->DirFiles("$target_dir/import-$category_id");
        foreach ($Files as $ffname => $ffname2) {

            $newFilePath="$target_dir/import-$category_id/$ffname";
            if (is_file($newFilePath)) {
                if (!xcategorize_import_perform($category_id, $newFilePath, $ForceCat, $ForceExt)) {
                    $rm = $unix->find_program("rm");
                    shell_exec("$rm -rf $target_dir/import-$category_id");
                    build_progress("{failed}", 110);
                    return;
                }
            }
        }
        $rm = $unix->find_program("rm");
        shell_exec("$rm -rf $target_dir/import-$category_id");
        build_progress("{done}", 100);
        return;

    }

    if(!xcategorize_import_perform($category_id,$filepath,$ForceCat,$ForceExt)){
        $rm=$unix->find_program("rm");
        shell_exec("$rm -f $filepath");
        build_progress("{failed}", 110);
        return;

    }

    build_progress("{success}", 100);

}

function xcategorize_import_perform($category_id,$filepath,$ForceCat=0,$ForceExt=0){
    $unix=new unix();
    $catz=new mysql_catz();
    echo "$filepath\n";
    $CountOfWebsites=$unix->COUNT_LINES_OF_FILE($filepath);
    echo "$CountOfWebsites lines to import\n";


    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categorytable FROM personal_categories WHERE category_id='$category_id'"));
    $category_table=$ligne["categorytable"];


    if($category_table==null){
        echo "Failed no category table\n";
        build_progress("{failed}", 110);
        return false;
    }


    $handle = @fopen($filepath, "r");
    if (!$handle) {
        echo "Failed to open file\n";
        build_progress("{failed}", 110);
        return false;
    }




    $oldprc=0;
    $c=0;
    $CBAD=0;
    $ADDED=0;
    $SKIPPED=0;
    $ESKIPPED=0;
    $CBADNULL=0;
    while (!feof($handle)) {
        $c++;
        $www = trim(fgets($handle, 4096));
        $www=trim(strtolower($www));
        if($www==null){continue;}
        $sitename=clean_sitename($www);
        if($sitename==null){echo "FALSE: $www\n";$CBAD++;continue;}
        $www=trim(strtolower($sitename));
        if($www==null){$CBADNULL++;continue;}

        if(strpos($www, "#")>0){echo "FALSE: $www\n";$CBAD++;continue;}
        if(strpos($www, "'")>0){echo "FALSE: $www\n";$CBAD++;continue;}
        if(strpos($www, "{")>0){echo "FALSE: $www\n";$CBAD++;continue;}
        if(strpos($www, "(")>0){echo "FALSE: $www\n";$CBAD++;continue;}
        if(strpos($www, ")")>0){echo "FALSE: $www\n";$CBAD++;continue;}
        if(strpos($www, "%")>0){echo "FALSE: $www\n";$CBAD++;continue;}

        if($ForceCat==0){
            $SKIPPED++;
            $nextid=$catz->GET_CATEGORIES($www);
            if($nextid>0){continue;}

        }
        $ADDED++;
        $sql="INSERT INTO $category_table (sitename) VALUES ('$www') ON CONFLICT DO NOTHING";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $NoError=false;
            if(preg_match("#Could not Connect to database service#i",$q->mysql_error)){
                for($i=0;$i<5;$i++){
                    sleep(2);
                    $q->QUERY_SQL($sql);
                    if($q->ok){
                        $NoError=true;
                        break;
                    }
                }

            }

            if(!$NoError) {
                build_progress("{failed} $q->mysql_error", 110);
                return false;
            }
        }

        $prc=$c/$CountOfWebsites;
        $prc=round($prc*100);
        if($prc>98){$prc=98;}
        if($prc<>$oldprc) {
            build_progress($prc, "$www  $c/$CountOfWebsites");
            $oldprc=$prc;
        }




    }
    $CBAD_PRC=0;
    $SKIPPED_PRC=0;
    $ADDED_PRC=0;
    if($SKIPPED>0){
        $SKIPPED_PRC=round(($SKIPPED/$CountOfWebsites)*100,2);
    }
    if($CBAD>0){
        $CBAD_PRC=round(($CBAD/$CountOfWebsites)*100,2);
    }
    if($ADDED_PRC>0){
        $ADDED_PRC=round(($ADDED/$CountOfWebsites)*100,2);
    }
    echo "Total parsed..........: $CountOfWebsites\n";
    echo "Skipped (Error).......: $CBAD ({$CBAD_PRC}%)\n";
    echo "Skipped (categorized).: $SKIPPED ({$SKIPPED_PRC}%)\n";
    echo "Added.................: $ADDED ({$ADDED_PRC}%)\n";
    fclose($handle);
    @unlink($filepath);
    return true;

}

function  testcategorize($category_id,$sitename){
    $GLOBALS["VERBOSE"]=true;
    $q=new mysql_squid_builder();
    $q->free_categorizeSave($sitename,$category_id,1,0,0);
}

function xcategorize($id){

        $GLOBALS["IDENT"]=$id;

        if(!is_file("/usr/share/artica-postfix/ressources/logs/categorize.$id.database")){
            build_progress("categorize.$id.database no such file {failed}",110);
            return;
        }

    $MAIN=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/categorize.$id.database"));

    $ForceCat=$MAIN["ForceCat"];
    $ForceExt=$MAIN["ForceExt"];
    $category_id=intval($MAIN["category_id"]);
    $websites=explode("\n",$MAIN["websites"]);
    $GLOBALS["ROOT_UID"]="Unknown";
    if(isset($MAIN["SESSIONID"])){ $GLOBALS["ROOT_UID"]=$MAIN["SESSIONID"]; }
    $CountOfWebsites=count($websites);

    $q=new mysql_squid_builder();
    echo "Force category..........: $ForceCat\n";
    echo "Force Extension.........: $ForceExt\n";
    echo "Sites to categorize.....: $CountOfWebsites\n";
    echo "category_id.............: $category_id\n";

    if($category_id==0){
        build_progress("categorize category_id == 0 {failed}",110);
        return;
    }

    $c=0;
    foreach ($websites as $sitename){
        if(trim($sitename)==null){continue;}
        $c++;
        $prc=$c/$CountOfWebsites;
        $prc=round($prc*100);
        if($prc>98){$prc=98;}
        build_progress($prc,$sitename);
        $sitename=clean_sitename($sitename);
        if($sitename==null){continue;}
        $q->free_categorizeSave($sitename,$category_id,$ForceCat,$ForceExt,$id);

    }


    build_progress(100,"{done}");


}
function clean_sitename($sitename){
    $sitename=trim(strtolower($sitename));
    if($sitename==null){return "";}
    $sitename=str_replace("[.]",".",$sitename);
    $sitename=str_replace("hxxp:","http:",$sitename);
    $sitename=str_replace("hxxps:","https:",$sitename);
    if(preg_match("#^\##",$sitename)){return "";}
    if(preg_match("#^domain\s+(.+?)\s+#",$sitename,$re)){$sitename=$re[1];}
    if(preg_match("#^(.+?)\##",$sitename,$re)){$sitename=$re[1];}
    if(preg_match("#^[0-9\.]+\s+(.+)#",$sitename,$re)){$sitename=$re[1];}
    if(preg_match("#^(.+?)\##",$sitename,$re)){$sitename=$re[1];}
    if($sitename=="thisisarandomentrythatdoesnotexist.com") {return "";}
    if(preg_match("#(.+?)\s+(.+)#", $sitename,$re)){$sitename=$re[1];}
    if(preg_match("#^http:\/\/(.*)$#", $sitename,$re)){$sitename=$re[1];}
    if(preg_match("#^https:\/\/(.*)$#", $sitename,$re)){$sitename=$re[1];}
    if(preg_match("#^\.(.*)$#", $sitename,$re)){$sitename=$re[1];}
    if(preg_match("#^www\.(.*)$#", $sitename,$re)){$sitename=$re[1];}
    if(preg_match("#^(.?)\/#", $sitename,$re)){$sitename=$re[1];}
    return $sitename;
}


?>