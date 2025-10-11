<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);

$GLOBALS["InfluxUseRemoteIpaddr"]="127.0.0.1";
$GLOBALS["PG_USER"]="articastats";
$GLOBALS["InfluxUseRemote"]=True;
$GLOBALS["VERBOSE"]=true;
include_once("/usr/share/artica-postfix/ressources/class.postgres.inc");
include_once("/usr/share/artica-postfix/ressources/class.squid.familysites.inc");

$q=new postgres_sql();
$q->CREATE_STATSCOM();
if(!$q->FIELD_EXISTS("statscom_websites","k9catz")){
    $q->QUERY_SQL("ALTER TABLE statscom_websites ADD k9catz BIGINT NOT NULL DEFAULT '0'");
    if(!$q->ok){echo $q->mysql_error;die();}
}


if(isset($_GET["count"])){count_sites();exit;}

if(isset($_GET["domain"])){
    parse_domain();
    exit;
}


function parse_domain():bool{
    $domain=trim(strtolower($_GET["domain"]));
    $id=get_domainid($domain);
    if($id==0){
        if(!create_domain($domain)){
            echo "RESULT=FALSE\n";
            echo "CREATED=FALSE\n";
            return false;
        }
        echo "RESULT=TRUE\n";
        echo "CREATED=TRUE\n";
        return true;
    }
    if(!update_domain($id)){
        echo "RESULT=FALSE\n";
        echo "UPDATED=$id\n";
        return false;
    }
    echo "RESULT=TRUE\n";
    return true;
}

function get_domainid($domain){
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT siteid FROM statscom_websites WHERE sitename='$domain'");
    return intval($ligne["siteid"]);

}

function update_domain($id){
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT hits FROM statscom_websites WHERE siteid='$id'");
    $hits= intval($ligne["hits"]);
    $hits++;
    $q->QUERY_SQL("UPDATE statscom_websites SET hits='$hits' WHERE siteid='$id'");
}

function create_domain($domain){
    $fam=new squid_familysite();
    $familysite=$fam->GetFamilySites($domain);
    $q=new postgres_sql();
    $lastseen=time();
    $sql="INSERT INTO statscom_websites (sitename,familysite,hits,lastseen) VALUES('$domain','$familysite',1,$lastseen)";
    $q->QUERY_SQL($sql);
    return $q->ok;
}

function count_sites(){
    $q=new postgres_sql();
    $sql="SELECT COUNT(*) as tcount FROM statscom_websites";
    $ligne=$q->mysqli_fetch_array($sql);
    $Count=$ligne["tcount"];
    echo "<H1>$Count</H1>";
}





