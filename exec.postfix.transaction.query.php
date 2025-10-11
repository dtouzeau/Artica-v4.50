<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.manager.inc');



transactions_query($argv[1]);




function build_progress($text,$pourc){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/smtp.transactions.{$GLOBALS["tfile"]}.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);


}


function transactions_query($tfile){

    $GLOBALS["tfile"]=$tfile;
    $fields="id,zdate,
		fromdomain ,
		relay_s ,
		relay_r ,
		todomain ,
		frommail ,
		tomail ,
		size ,
		aclid ,
		smtp_code,
		ipaddr,
		refused,
		rbl,
		filtered,
		spamscore,
		sent,
		subject ,
		msgid ,
		rblart,
		whitelisted,
		disclaimer,
		infected,
		maintenance,
		reason";

    if(!is_file(PROGRESS_DIR."/$tfile.sql")){
        echo "$tfile.sql, no such file\n";
        build_progress("{failed}",110);
        return;
    }

    build_progress("{build_query}",10);
    $sql=trim(@file_get_contents(PROGRESS_DIR."/$tfile.sql"));

    if($sql==null){
        echo "$tfile.sql, no query\n";
        build_progress("{failed}",110);
        return;
    }

    $q=new postgres_sql();

    build_progress("{empty} smtplog_query",20);

    $q->QUERY_SQL("TRUNCATE TABLE smtplog_query");
    echo "$sql\n";

    build_progress("{execute} {please_wait}",50);


    $q->QUERY_SQL("INSERT INTO smtplog_query ($fields) $sql");

    if(!$q->ok){
        echo $q->mysql_error;
        build_progress("{failed}",110);
        return;

    }



    build_progress("{success}",100);

}
