<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.manager.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.hosts.inc');
$GLOBALS["OUTPUT"]=true;
$GLOBALS["TITLENAME"]="Computers";


if($argv[1]=="--export"){export();exit();}
if($argv[1]=="--import"){import($argv[2]);exit();}



die("Unable to understand command line\n");




function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/export-computers.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}

function import($filename){
    $unix = new unix();
    $filepath = dirname(__FILE__) . "/ressources/conf/upload/$filename";
    if (!is_file($filepath)) {
        build_progress(110, "$filepath no such file");
        return;
    }

    $Count=$unix->COUNT_LINES_OF_FILE($filepath);
    $csv = new SplFileObject($filepath);
    $csv->setFlags(SplFileObject::READ_CSV);
    $csv->setCsvControl(';');
    $IPClass=new IP();


    $i=1;
    foreach($csv as $ligne){

        $prc=$i/$Count;
        $prc=round($prc*100);
        if($prc>95){$prc=95;}


        $dhcpfixed=0;
        $proxyalias=null;
        $hostalias1=null;
        $hostalias2=null;
        $hostalias3=null;
        $hostalias4=null;
        $hostname=null;
        $OS=null;
        $mac        = $ligne[0];
        $ipaddr     = $ligne[1];

        build_progress($prc, "{importing} $mac/$ipaddr");

        if(isset($ligne[2])) {
            $hostname = $ligne[2];
        }

        if(isset($ligne[3])) {
            $dhcpfixed = $ligne[3];
        }

        if(isset($ligne[4])) {
            $proxyalias = $ligne[4];
        }
        if(isset($ligne[5])) {
            $hostalias1 = $ligne[5];
        }
        if(isset($ligne[6])) {
            $hostalias2 = $ligne[6];
        }
        if(isset($ligne[7])) {
            $hostalias3 = $ligne[7];
        }
        if(isset($ligne[8])) {
            $hostalias4 = $ligne[8];
        }
        if(isset($ligne[9])) {
            $OS = $ligne[9];
        }

        if(!$IPClass->IsvalidMAC($mac)){
            echo "SKIPPING $mac invalid MAC address\n";
            continue;
        }

        if(!$IPClass->isValid($ipaddr)){
            echo "SKIPPING $ipaddr invalid IP address\n";
            continue;
        }

        $cmp=new hosts($mac);
        if($hostname<>null) {
            $cmp->hostname = $hostname;
        }

        $cmp->dhcpfixed=$dhcpfixed;
        $cmp->ipaddr=$ipaddr;
        if($OS<>null){$cmp->ComputerOS=$OS;}
        if($proxyalias<>null){$cmp->proxyalias=$proxyalias;}
        if($hostalias1<>null){$cmp->hostalias1=$hostalias1;}
        if($hostalias2<>null){$cmp->hostalias2=$hostalias2;}
        if($hostalias3<>null){$cmp->hostalias3=$hostalias3;}
        if($hostalias4<>null){$cmp->hostalias4=$hostalias4;}
        $cmp->Save();
    }

    @unlink($filepath);

    if(is_file("/etc/init.d/isc-dhcp-server")){
        build_progress(96, "{restarting} {APP_DHCP}");
        shell_exec("/etc/init.d/isc-dhcp-server restart");
    }


    build_progress(100, "{success}");

}

function export(){
    $q=new postgres_sql();
    $sql="SELECT * FROM hostsnet ORDER BY fullhostname";

    $fp = fopen("/usr/share/artica-postfix/ressources/logs/computers.csv", 'w+');
    fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

    $line = array('mac', 'ipaddr','hostname','Fixed DHCP','Proxy Alias','hostalias 1','hostalias 2','hostalias 3','hostalias 4','Operating system');
    fputcsv($fp, $line, ";");


    build_progress(5,"{exporting}");

    $results=$q->QUERY_SQL($sql);

    $max=pg_num_rows($results);

    $i=1;

    while ($ligne = pg_fetch_assoc($results)) {
        $mac = $ligne["mac"];
        $prc=$i/$max;
        $prc=round($prc*100);
        if($prc>5) {
            if($prc<95) {
                build_progress($prc,"{exporting} $mac");
            }
        }


        $ipaddr = $ligne['ipaddr'];
        $text_class = null;
        $proxyalias = $ligne['proxyalias'];
        $fullhostname = $ligne['fullhostname'];
        $dhcpfixed = $ligne['dhcpfixed'];
        $hostalias1 = $ligne["hostalias1"];
        $hostalias2 = $ligne["hostalias2"];
        $hostalias3 = $ligne["hostalias3"];
        $hostalias4 = $ligne["hostalias4"];
        $OS = $ligne["OS"];


        $line = array($mac, $ipaddr, $fullhostname, $dhcpfixed,
            $proxyalias, $hostalias1, $hostalias2, $hostalias3, $hostalias4, $OS);

        fputcsv($fp, $line, ";");
    }


    fclose($fp);
    build_progress(100,"{success}");
}
