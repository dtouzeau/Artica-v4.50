<?php
include_once(dirname(__FILE__) . '/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__) . '/ressources/class.mysql.catz.inc');
include_once(dirname(__FILE__) . '/ressources/class.html.tools.inc');
include_once(dirname(__FILE__) . '/ressources/class.categories.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
$GLOBALS["VERBOSE"]=false;
$GLOBALS["UPLOADED"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--uploaded#",implode(" ",$argv))){$GLOBALS["UPLOADED"]=true;;}

if(isset($argv[1])) {
    if ($argv[1] == "--backup") {
        backup_categories();
        exit;
    }
    if ($argv[1] == "--delete") {
        backup_delete($argv[2]);
        exit;
    }
    if ($argv[1] == "--restore") {
        restore_categories($argv[2]);
        exit;
    }
    if ($argv[1] == "--restore-id") {
        restore_from_table($argv[2]);
        exit;
    }
    if ($argv[1] == "--repair") {
        build_personal_category_table();
        exit;
    }
    if ($argv[1] == "--destroy") {
        destroy_all_categories();
        exit;
    }
    if ($argv[1] == "--sync") {
        sync_category($argv[2]);
        exit;
    }
    if ($argv[1] == "--syncs") {
        sync_categories();
        exit;
    }
    if ($argv[1] == "--indexes") {
        move_indexes();
        exit;
    }
}
backup_categories();

function move_indexes(){
    $PersonalCategoriesIndexFrom=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PersonalCategoriesIndexFrom"));
    $q=new postgres_sql();
    $categories=new categories();
    echo "PersonalCategoriesIndexFrom=$PersonalCategoriesIndexFrom\n";
    $ALREADY=array();
    $sql="SELECT category_id FROM personal_categories WHERE category_id >249 AND category_id < $PersonalCategoriesIndexFrom";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $ID=$ligne["category_id"];
        $NextID=$categories->FreeIndex();
        if(isset($ALREADY[$NextID])){
            echo "$ID --> $NextID Already done !!!!\n";
            continue;
        }
        echo "$ID --> $NextID\n";
        $q->QUERY_SQL("UPDATE personal_categories SET category_id=$NextID WHERE category_id=$ID");
        if(!$q->ok){echo $q->mysql_error."\n";return;}
        $ALREADY[$NextID]=true;


    }


    if(count($ALREADY)>0){
        $unix=new unix();
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php /usr/share/artica-postfix/exec.compile.categories.php");
    }



}

function restore_from_table($ID){
    $GLOBALS["UPLOADED"]=false;
    $q=new lib_sqlite("/home/artica/SQLITE/categoriesbackup.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backup WHERE ID=$ID");
    $sourcepath=$ligne["sourcepath"];
    restore_categories($sourcepath);
}

function restore_categories($path){
	$unix=new unix();
	if(!is_file($path)){$path=dirname(__FILE__)."/ressources/conf/upload/$path";$GLOBALS["UPLOADED"]=true;}
	if(!is_file($path)){
		echo "\"$path\" No such file...\n";
		build_progress(110,"{bad_filename}");
		
		exit();
	}
	
	if(!preg_match("#\.gz$#", $path)){
		echo "\"$path\" bad extension...\n";
		build_progress(110,"{bad_file_extension}");
		if($GLOBALS["UPLOADED"]){@unlink($path);}
		exit();
	}
	build_progress(6,"{restoring} {backup} $path");
	
	$basename=basename($path);
	if(preg_match("#\.tar\.gz$#", $basename)){
		restore_categories_untar($path);
		build_progress(100,"$basename {success}");
		return;
	}
	
	if(!preg_match("#\.gz$#", $basename)){
		build_progress(110,"{bad_file_extension}");
		return;
	}
	
	
	$rm=$unix->find_program("rm");
	$temppath=$unix->TEMP_DIR()."/restore_categories";
	if(is_dir($temppath)){
		build_progress(7,"Removing temporary directory...");
		system("$rm -rf $temppath");
	}
	$GLOBALS["MYSQLERROR"]=0;
	@mkdir($temppath,0755,true);
	
	$sourcefile="$path";
	$destfile="$temppath/$basename.sql";
	$tablename=str_replace(".gz", "", $basename);
	build_progress(20,"{extracting} $basename");
	
	if(!$unix->uncompress($sourcefile, $destfile)){
		ilogs("$basename uncompress failed {$GLOBALS["UNCOMPRESSLOGS"]}",__LINE__);
		build_progress(110,"{failed} $basename");
		if($GLOBALS["UPLOADED"]){@unlink($sourcefile);}
		@unlink($destfile);
		return;
	}
	
	if(preg_match("#^categoryuris#",$basename)){
		inject_categoryuris($destfile,20);
		build_personal_category_table();
		build_progress(100,"$basename {success}");
		if($GLOBALS["UPLOADED"]){@unlink($sourcefile);}
		return;
	}
	build_progress(30,"{injecting} $basename....");
	if($GLOBALS["UPLOADED"]){@unlink($sourcefile);}
	inject_category($destfile,30);
	build_personal_category_table();
	build_progress(100,"$basename {success}");
	
}

function restore_categories_untar($filepath){
	$basename=basename($filepath);
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$temppath=$unix->TEMP_DIR()."/restore_categories";
	if(is_dir($temppath)){
		build_progress(7,"Removing temporary directory...");
		system("$rm -rf $temppath");
	}
	$GLOBALS["MYSQLERROR"]=0;
	@mkdir($temppath,0755,true);
	build_progress(10,"{extracting} $basename");
	system("$tar xf $filepath -C $temppath/");
	if($GLOBALS["UPLOADED"]){@unlink($filepath);}
	$filelist=$unix->DirFiles($temppath);
	$max=count($filelist);
	$c=0;
	foreach ($filelist as $filename=>$none){
		$c++;
		$prc=$c/$max;
		$prc=round($prc*100,0);
		if($prc>90){$prc=90;}
		$prc=$prc-3;
		if($prc<6){$prc=6;}
		$mem=round(((memory_get_usage()/1024)/1000),2);
		build_progress($prc++,"{checking} $filename {$mem}MB memory usage");
        $sourcefile="$temppath/$filename";
        if(preg_match("#([0-9]+)\.dump$#",$filename,$re)){
            $t=$re[1];
            build_progress($prc++,"{restore} $filename");
            $cmd="/usr/local/ArticaStats/bin/pg_restore --clean --dbname=proxydb --format=custom -h /var/run/ArticaStats -U ArticaStats  $sourcefile";
            system($cmd);
            $TargetDirectory="/home/artica/backup-categories";
            if(!is_dir($TargetDirectory)){@mkdir($TargetDirectory,0755,true);}
            @chown($TargetDirectory, "www-data");
            $q=new lib_sqlite("/home/artica/SQLITE/categoriesbackup.db");

            if(!is_file("$TargetDirectory/$t.tar.gz")) {
                system("cd $temppath");
                @chdir($temppath);
                system("$tar -czf $TargetDirectory/$t.tar.gz $filename");
                $filesize = filesize("$TargetDirectory/$t.tar.gz");
                $q->QUERY_SQL("INSERT INTO backup (sourcepath,created,filesize,events) VALUES ('$TargetDirectory/$t.tar.gz','$t','$filesize','')");
            }
            @unlink($sourcefile);
            continue;
        }




		if(!preg_match("#^category(uris|)_.*?\.gz$#", $filename)){
			echo "$filename no match ^category_.*?\.gz$\n";
			build_progress($prc++,"{skipping} $filename");
			continue;
		}
		usleep(800);

		$destfile="$temppath/$filename.sql";
		$tablename=str_replace(".gz", "", $filename);
		build_progress($prc++,"{extracting} $filename");
	
		if(!$unix->uncompress($sourcefile, $destfile)){
			ilogs("$filename uncompress failed {$GLOBALS["UNCOMPRESSLOGS"]}",__LINE__);
			build_progress($prc++,"{failed} $filename");
			@unlink($sourcefile);
			@unlink($destfile);
			continue;
		}
		if(preg_match("#^categoryuris#",$filename)){
			inject_categoryuris($destfile,$prc);
			continue;
		}
		build_progress($prc++,"{injecting} $filename....");
		inject_category($destfile,$prc);
	
	}
	
	if(is_dir($temppath)){
		build_progress(95,"Removing temporary directory...");
		system("$rm -rf $temppath");
	}
	
	build_personal_category_table();
	build_progress(100,"{success}");
	
}

function inject_categoryuris($destfile,$prc){
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	$prc=$prc-1;
	$basename=basename($destfile);
	$tablename=null;
	if(preg_match("#^(.+?)\.#", $basename,$re)){$tablename=$re[1];}
	build_progress($prc++,"{checking} $destfile");
	build_progress($prc++,"{tablename} $tablename");
	if($tablename==null){
		@unlink($destfile);
		build_progress($prc++,"{tablename} {failed}");
		return;
	}
	$CountLines=$unix->COUNT_LINES_OF_FILE($destfile);
	if($CountLines==0){
		@unlink($destfile);
		build_progress($prc++,"{tablename} no line");
		return;
	}
	$tablename_pre=str_replace("uris", "", $tablename);
	$postgres=new postgres_sql();
	$q=new mysql_squid_builder();
	
	$CountLinesText=numberFormat($CountLines,0,""," ");

	
	$prefix="INSERT INTO $tablename (sitename) VALUES ";
	$category=$q->tablename_tocat($tablename_pre);
	build_progress($prc++,"{$tablename} --> $category --> $CountLinesText {items}");
	$postgres->CREATE_CATEGORY_TABLE($tablename);
	
	
	

	$handle = @fopen($destfile, "r");
	if (!$handle) {
		@unlink($destfile);
		build_progress($prc++,"{tablename} {failed}");
		echo "Failed to open file\n";
		return;

	}
	$c=0;
	$n=array();
	while (!feof($handle)){
		$c++;
		$prcF=$c/$CountLines;
		$prcF=round($prcF*100,2);
		$www =trim(fgets($handle, 4096));
		$www=replace_accents($www);
		$www=mysql_escape_string2($www);
		$n[]="('$www')";
		if(count($n)>150000){
			$mem=round(((memory_get_usage()/1024)/1000),2);
			$sql=$prefix.@implode(",",$n). " ON CONFLICT DO NOTHING";
			$postgres->QUERY_SQL($sql);
			if(!$postgres->ok){
				ilogs($postgres->mysql_error,__LINE__);
				$GLOBALS["MYSQLERROR"]++;$n=array();
				continue;
			}
			$text= numberFormat($c,0,""," ")." items";
			if($GLOBALS["MYSQLERROR"]>0){$text=$text." MySQL Error(s):{$GLOBALS["MYSQLERROR"]}";}
			build_progress($prc,"$category: $text ({$prcF}%) {$mem}MB memory usage");
			$n=array();

		}

	}

	if(count($n)>0){
		$mem=round(((memory_get_usage()/1024)/1000),2);
		$sql=$prefix.@implode(",",$n). " ON CONFLICT DO NOTHING";
		$postgres->QUERY_SQL($sql);
		if(!$postgres->ok){
			ilogs($postgres->mysql_error,__LINE__);
			$GLOBALS["MYSQLERROR"]++;$n=array();
			
		}
		$text= numberFormat($c,0,""," ")." items";
		if($GLOBALS["MYSQLERROR"]>0){$text=$text." MySQL Error(s):{$GLOBALS["MYSQLERROR"]}";}
		build_progress($prc,"$category: $text ({$prcF}%) - {$mem}MB memory usage");
		$n=array();
			
	}
	$text= numberFormat($c,0,""," ")." items";
	build_progress($prc,"$category: $text optimize table $tablename");
	$postgres->QUERY_SQL("VACUUM FULL $tablename");


}

function sync_category($category_id){
	
	if(intval($category_id)==0){
		echo "Bad Category $category_id\n";
		build_progress(110,"Bad Category $category_id");
		return;
	}
	
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT categorytable FROM personal_categories WHERE category_id='$category_id'"));
	
	$categorytable=$ligne["categorytable"];
	build_progress(15,"$categorytable");
	echo "Table name: {$ligne["categorytable"]}\n";
	
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT count(*) as tcount FROM $categorytable"));
	$Number=$ligne["tcount"];
	echo "$categorytable: $Number elements\n";
	
	$q->QUERY_SQL("UPDATE personal_categories SET items=$Number WHERE category_id=$category_id");
	
}

function sync_categories(){
	
	
	$q=new postgres_sql();
	$results=$q->QUERY_SQL("SELECT category_id,categorytable FROM personal_categories");
	while ($ligne = pg_fetch_assoc($results)) {
		$category_id=$ligne["category_id"];
		$categorytable=$ligne["categorytable"];
		echo "Table name: {$ligne["categorytable"]}\n";
		
		$ligne2=pg_fetch_array($q->QUERY_SQL("SELECT count(*) as tcount FROM $categorytable"));
		$Number=$ligne2["tcount"];
		echo "$categorytable: $Number elements\n";
		
		$q->QUERY_SQL("UPDATE personal_categories SET items=$Number WHERE category_id=$category_id");
		
	}
	
}




function ilogs($text,$line=0){
	writeOtherlogs("/var/log/import-categories.log","[$line]: $text");
	
}

function destroy_all_categories(){
	$q=new postgres_sql();
	$q->QUERY_SQL("DROP TABLE personal_categories");
	$LIST_TABLES_CATEGORIES=LIST_TABLES_CATEGORIES();
	while (list ($tablename,$none ) = each ($LIST_TABLES_CATEGORIES) ){
		$q->QUERY_SQL("DROP TABLE $tablename");
	}
	$categories=new categories();
	$categories->initialize();
	
}

function build_personal_category_table(){
	
	$mysql_catz=new mysql_catz();
	$q2=new mysql_squid_builder();
	$LIST_TABLES_CATEGORIES=LIST_TABLES_CATEGORIES();
	
	
	$q=new postgres_sql();
	$categories=new categories();
	$categories->initialize();
	
	while (list ($tablename,$none ) = each ($LIST_TABLES_CATEGORIES) ){
		$categoryname=$q2->tablename_tocat($tablename);
		$html=new htmltools_inc();
		$categorykey=strtolower($html->StripSpecialsChars($categoryname));
		
		$sql="SELECT category_id FROM personal_categories WHERE categorytable='$tablename'";
		$ligne=$q->mysqli_fetch_array($sql);
		if(!$q->ok){echo "$q->mysql_error\n";}
		$category_id=intval($ligne["category_id"]);
		$ligne2=pg_fetch_array($q->QUERY_SQL("SELECT count(*) as tcount FROM $tablename"));
		$Rows=$ligne2["tcount"];
		echo "$tablename $Rows rows\n";
		if($category_id>0){
			
			$q->QUERY_SQL("UPDATE personal_categories SET items='$Rows' WHERE category_id='$category_id'");
			continue;
		}
		
		
		echo "$tablename -> $categoryname category_id=$category_id $Rows rows\n";
	}
	
}


function inject_category($destfile,$prc){
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	
	$basename=basename($destfile);
	$tablename=null;
	if(preg_match("#^(.+?)\.#", $basename,$re)){$tablename=$re[1];}
	if(preg_match("#^(.+?)\s+\(#", $basename,$re)){$tablename=$re[1];}
	build_progress($prc++,"{checking} $destfile");
	build_progress($prc++,"{tablename} $tablename");
	if($tablename==null){
		@unlink($destfile);
		build_progress($prc++,"{tablename} {failed}");
		return;
	}

	if($tablename=="category_association"){$tablename="category_associations";}
	$filesize=@filesize($destfile);
	echo "FileSize: ".FormatBytes($filesize,true)."\n";
	$CountLines=$unix->COUNT_LINES_OF_FILE($destfile);
	if($CountLines==0){
		@unlink($destfile);
		ilogs("$tablename no line ($destfile)",__LINE__);
		build_progress($prc,"{tablename} no line");
		return;
	}
	$CountLinesText=numberFormat($CountLines,0,""," ");
	$postgres=new postgres_sql();
	$q=new mysql_squid_builder();
	
	$prefix="INSERT INTO $tablename (sitename) VALUES ";
	$category=$q->tablename_tocat($tablename);
	build_progress($prc,"{$tablename} --> $category $CountLinesText {items}");
	$postgres->CREATE_CATEGORY_TABLE($tablename);

	$handle = @fopen($destfile, "r");
	if (!$handle) {
		@unlink($destfile);
		build_progress($prc,"{tablename} {failed}");
		ilogs("$tablename Failed to open file");
		echo "Failed to open file\n";
		return;

	}
	$c=0;
	$n=array();
	while (!feof($handle)){
		$c++;
		$prcF=$c/$CountLines;
		$prcF=round($prcF*100,2);
		$www =trim(fgets($handle, 4096));
		$www=mysql_escape_string2($www);
		$www=replace_accents($www);
		$n[]="('$www')";
		if(count($n)>150000){
			$mem=round(((memory_get_usage()/1024)/1000),2);
			$sql=$prefix.@implode(",",$n). " ON CONFLICT DO NOTHING";
			$postgres->QUERY_SQL($sql,"artica_backup");
			if(!$postgres->ok){
				ilogs($postgres->mysql_error,__LINE__);
				$GLOBALS["MYSQLERROR"]++;$n=array();
				continue;
			}
			$text= numberFormat($c,0,""," ")." items";
			if($GLOBALS["MYSQLERROR"]>0){$text=$text." MySQL Error(s):{$GLOBALS["MYSQLERROR"]}";}
			build_progress($prc,"$category: $text ({$prcF}%) {$mem}MB memory usage");
			$n=array();
				
		}

	}

	if(count($n)>0){
		$mem=round(((memory_get_usage()/1024)/1000),2);
		$sql=$prefix.@implode(",",$n). " ON CONFLICT DO NOTHING";
		$postgres->QUERY_SQL($sql,"artica_backup");
		if(!$postgres->ok){ilogs($postgres->mysql_error,__LINE__);$GLOBALS["MYSQLERROR"]++;$n=array();}
		$text= numberFormat($c,0,""," ")." items";
		if($GLOBALS["MYSQLERROR"]>0){$text=$text." MySQL Error(s):{$GLOBALS["MYSQLERROR"]}";}
		build_progress($prc,"$category: $text ({$prcF}%) - {$mem}MB memory usage");
		$n=array();
			
	}
	$text= numberFormat($c,0,""," ")." items";
	build_progress($prc,"$category: $text optimize table $tablename");
	$postgres->QUERY_SQL("VACUUM FULL $tablename");
	return true;
}

function backup_delete($ID){
    $q=new lib_sqlite("/home/artica/SQLITE/categoriesbackup.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backup WHERE ID=$ID");
    $sourcepath=$ligne["sourcepath"];
    @unlink($sourcepath);
    $q->QUERY_SQL("DELETE FROM backup WHERE ID=$ID");
}

function backup_categories(){
	$unix=new unix();
	$TargetDirectory="/home/artica/backup-categories";
    if(!is_dir($TargetDirectory)){@mkdir($TargetDirectory,0755,true);}
    @chown($TargetDirectory, "www-data");

    build_progress(10,"{backup}");
    $dsn =  "--host='/var/run/ArticaStats'";
	$LIST_TABLES_CATEGORIES=LIST_TABLES_CATEGORIES();
    build_progress(20,"{backup}");


	$COUNT_LIST_TABLES_CATEGORIES=count($LIST_TABLES_CATEGORIES);
	if(count($LIST_TABLES_CATEGORIES)==0){
		build_progress(110, "{failed} no table");
		return;
	}
	$t=time();

	$target_file="$TargetDirectory/$t.dump";
    $targz="$TargetDirectory/$t.tar.gz";
	$T=@implode(" ",$LIST_TABLES_CATEGORIES);
    $cmdline="/usr/local/ArticaStats/bin/pg_dump -Fc --no-password --username=ArticaStats --dbname=proxydb $dsn $T --file=$target_file 2>&1";
    build_progress(50,"{backup}");
    exec($cmdline,$resultsLOGS);
    $tar=$unix->find_program("tar");
    build_progress(60,"{backup}");
    chdir($TargetDirectory);
    system("cd $TargetDirectory");
    shell_exec("$tar -czf ".basename($targz)." ".basename($target_file));
    chdir("/root");
    system("cd /root");
    @unlink($target_file);
    @chown($target_file, "www-data");
    build_progress(70,"{backup}");
    $q=new lib_sqlite("/home/artica/SQLITE/categoriesbackup.db");
    @chmod("/home/artica/SQLITE/categoriesbackup.db", 0644);
    @chown("/home/artica/SQLITE/categoriesbackup.db", "www-data");

    $sql="CREATE TABLE IF NOT EXISTS `backup` (
		 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		 `sourcepath` TEXT UNIQUE,
		`created` INTEGER NOT NULL DEFAULT 0,
		`filesize` INTEGER NOT NULL DEFAULT 0,
		`events` TEXT )";
    $q->QUERY_SQL($sql);

    $events=base64_encode(@implode("\n",$resultsLOGS));
    $filesize=filesize($targz);
    $resultsLOGS[]="Backuped file $filesize bytes";

    $q->QUERY_SQL("INSERT INTO backup (sourcepath,created,filesize,events) VALUES ('$targz','$t','$filesize','$events')");
    if(!$q->ok){echo $q->mysql_error;@unlink($targz);}
    build_progress(80,"{backup}");
    $results=$q->QUERY_SQL("SELECT ID,sourcepath FROM backup");
    foreach ($results as $ligne){
        $sourcepath=$ligne["sourcepath"];
        $ID=$ligne["ID"];
        if(!is_file($sourcepath)){
            $resultsLOGS[]="Removed file $sourcepath($ID) - file doesn't exists";
            echo "Remove $ID $sourcepath\n";
            $q->QUERY_SQL("DELETE FROM backup WHERE ID=$ID");
            continue;
        }
        @chown($sourcepath, "www-data");
    }
    $C=0;
    build_progress(90,"{backup}");
    $results=$q->QUERY_SQL("SELECT ID,sourcepath FROM backup ORDER BY ID DESC");
    foreach ($results as $ligne){
        $sourcepath=$ligne["sourcepath"];
        @chown($sourcepath, "www-data");
        $ID=$ligne["ID"];
        $C++;
        if($C>5){
            @unlink($sourcepath);
            $resultsLOGS[]="Removed file $sourcepath($ID) - Max container 5 ($C)";
            $q->QUERY_SQL("DELETE FROM backup WHERE ID=$ID");
        }
    }
    build_progress(100,"{backup} {success}");
    squid_admin_mysql(2,"[categories]: Backup done {$COUNT_LIST_TABLES_CATEGORIES} categories.",@implode("\n",$resultsLOGS),__FILE__,__LINE__);
}


function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/backup_categories.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}







function LIST_TABLES_CATEGORIES(){
		$q=new postgres_sql();
        if(!$q->TABLE_EXISTS("personal_categories")){return array();}
        $ManageOfficialsCategories = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
        $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by category_id";
        if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories WHERE (official_category=1 OR free_category=1) order by category_id";}
        $results=$q->QUERY_SQL($sql);

        $TABLES[]="-t personal_categories";

        while ($ligne = pg_fetch_assoc($results)) {
            $TABLES[]="-t ".$ligne["categorytable"];
        }

	

	return $TABLES;


}
