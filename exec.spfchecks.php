<?php
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');


parse_domains();

function parse_domains()
{

    $unix = new unix();

    $q=new lib_sqlite("/home/artica/SQLITE/spftemp.db");
    @chmod("/home/artica/SQLITE/spftemp.db", 0644);
    @chown("/home/artica/SQLITE/spftemp.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);


    $sql="CREATE TABLE IF NOT EXISTS `spf_reports` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`domain` TEXT,
		`mailrelay` TEXT,
		`spf_status` INTEGER,
		`text_error` TEXT
		)";

    $q->QUERY_SQL($sql);
    $q->QUERY_SQL("DELETE FROM spf_reports");


    $hostname = $unix->hostname_g();
    $spfquery = $unix->find_program("spfquery");
    $dig=$unix->find_program("dig");
    $results=array();

    $q = new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results = $q->QUERY_SQL("SELECT * FROM transport_maps ORDER BY addr");

    foreach ($results as $num => $ligne) {
        $item = trim($ligne["addr"]);
        $mxname=GetFirstMx($item);
        $OtherDomains = unserialize(base64_decode($ligne["OtherDomains"]));
        if(!$mxname){
            echo "$item, unable to found the mx\n";
            $q->QUERY_SQL("INSERT INTO spf_reports (domain,mailrelay,spf_status,text_error) *
            VALUES ('$item','unknown',0,'{unable_to_found_mx}')");
            continue;

        }

        echo "$item\t$mxname\n";

        if(count($OtherDomains)==0){continue;}
            foreach ($OtherDomains as $domain=>$index){
                $mxname=GetFirstMx($domain);
                if(!$mxname) {
                    echo "$domain, unable to found the mx\n";
                    $q->QUERY_SQL("INSERT INTO spf_reports (domain,mailrelay,spf_status,text_error) *
                    VALUES ('$domain','unknown',0,'{unable_to_found_mx}')");
                    continue;
                }

                echo "$item\t$mxname\n";


            }



        }









}


function GetFirstMx($domain){
    $unix = new unix();
    $dig=$unix->find_program("dig");
    if(!is_file($dig)){return false;}
    exec("$dig $domain  MX +short 2>&1",$results);
    $MAIN=array();
    foreach ($results as $line){
        if($line==null){continue;}
        if(preg_match("#([0-9]+)\s+(.+)#",$line,$re)){
            $MAIN[$re[1]]=trim($re[2]);
        }


    }

    if(count($MAIN)==0){return false;}
    ksort($MAIN);
    foreach ( $MAIN as $item=>$mx) {
        return $mx;

    }



}