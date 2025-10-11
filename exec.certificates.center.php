<?php
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.sqlite.inc');

$db="/home/artica/SQLITE/certificates.db";
if(is_array($argv)) {
    if (preg_match("#--HarmpID=([0-9]+)#", implode(" ", $argv), $re)) {
        $GLOBALS["HarmpID"] = $re[1];
        $db="/home/artica/SQLITE/certificates.{$GLOBALS["HarmpID"]}.db";
    }
}

$unix=new unix();
$sql="SELECT  *  FROM sslcertificates ORDER BY CommonName";
$q=new lib_sqlite($db);

$results=$q->QUERY_SQL($sql);
echo count($results)." certificates\n";
$TRCLASS=null;
foreach ($results as $index=>$ligne){
    $CommonName=$ligne["CommonName"];
    $UseLetsEncrypt=intval($ligne["UseLetsEncrypt"]);
    echo "$CommonName UseLetsEncrypt=$UseLetsEncrypt\n";
    $UsePrivKeyCrt=$ligne["UsePrivKeyCrt"];
    $privkey=$ligne["privkey"];
    $bundle=$ligne["bundle"];
    $crt=$ligne["crt"];
    $SquidCert=$ligne["SquidCert"];
    $Squidkey=$ligne["Squidkey"];
    $ID=$ligne["ID"];
    $C=null;
    $ST=null;
    $L=null;
    $O=null;
    $OU=null;
    $emailAddress=null;
    $privkey_content=$Squidkey;
    $certificate_content=$SquidCert;
    $array=array();
    $data=array();

    if($UsePrivKeyCrt==1){
        $privkey_content=$privkey;
        $certificate_content=$crt;

    }

    if(strlen($certificate_content)==null){
        echo "Certificate content, no data -> SKIP\n";
        continue;
    }


    $data[]=$certificate_content;
    if(strlen($bundle)>100){$data[]=$bundle;}
    $data[]=$privkey_content;

    $tmpfile=$unix->FILE_TEMP().".$CommonName.crt";
    @file_put_contents($tmpfile,@implode("\n",$data));

    $fp = fopen($tmpfile, "r");
    $cert = fread($fp, 16192);
    fclose($fp);
    $array=openssl_x509_parse($cert);
    @unlink($tmpfile);

    echo "$CommonName $tmpfile -------------\n";

    $validFrom_time_t=$array["validFrom_time_t"];
    $validTo_time_t=$array["validTo_time_t"];


    if(isset($array["subject"]["ST"])){$ST=$array["subject"]["ST"];}
    if(isset($array["subject"]["C"])){$C=$array["subject"]["C"];}
    if(isset($array["subject"]["L"])){$L=$array["subject"]["L"];}
    if(isset($array["subject"]["L"])){$C=$array["subject"]["L"];}
    if(isset($array["subject"]["O"])){$O=$array["subject"]["O"];}
    if(isset($array["subject"]["OU"])){$OU=$array["subject"]["OU"];}
    if(isset($array["subject"]["CN"])){$CN=$array["subject"]["CN"];}
    if(isset($array["subject"]["emailAddress"])){$emailAddress=$array["subject"]["emailAddress"];}

    if(isset($array["issuer"]["ST"])){$ST=$array["issuer"]["ST"];}
    if(isset($array["issuer"]["C"])){$C=$array["issuer"]["C"];}
    if(isset($array["issuer"]["L"])){$L=$array["issuer"]["L"];}
    if(isset($array["issuer"]["L"])){$C=$array["issuer"]["L"];}
    if(isset($array["issuer"]["O"])){$O=$array["issuer"]["O"];}
    if(isset($array["issuer"]["OU"])){$OU=$array["issuer"]["OU"];}
    if(isset($array["issuer"]["CN"])){$CN=$array["issuer"]["CN"];}
    if(isset($array["issuer"]["emailAddress"])){$emailAddress=$array["issuer"]["emailAddress"];}
    $validFrom=null;
    $validTo=null;
    if($array["validFrom_time_t"]<>null) {
        $validFrom = date("Y-m-d H:i:s", $array["validFrom_time_t"]);
    }
    if($array["validTo_time_t"]<>null) {
        $validTo=date("Y-m-d H:i:s",$array["validTo_time_t"]);
    }


    echo "ST...........: $ST\n";
    echo "L............: $L\n";
    echo "O............: $O\n";
    echo "O............: $O\n";
    echo "OU...........: $OU\n";
    echo "Email........: $emailAddress\n";
    $ST=str_replace("'","`",$ST);
    $C=str_replace("'","`",$C);
    $L=str_replace("'","`",$L);
    $O=str_replace("'","`",$O);
    $OU=str_replace("'","`",$OU);
    if(!is_null($emailAddress)) {
        $emailAddress = str_replace("'", "`", $emailAddress);
    }

    $fields=array();
    if($ST<>null){
        $fields[]="stateOrProvinceName='$ST'";
    }
    if($L<>null){
        $fields[]="localityName='$L'";
    }
    if($O<>null){
        $fields[]="OrganizationName='$O'";
    }
    if($OU<>null){
        $fields[]="OrganizationalUnit='$OU'";
    }
    if($emailAddress<>null){
        $fields[]="emailAddress='$emailAddress'";
    }
    if($C<>null){
        $fields[]="CountryName='$C'";
    }
    if($validFrom<>null){
        $fields[]="DateFrom='$validFrom'";
    }
    if($validTo<>null){
        $fields[]="DateTo='$validTo'";
    }
    if(count($fields)==0){continue;}
    $sql="UPDATE sslcertificates SET ".@implode(",",$fields)." WHERE ID=$ID;";
    echo $sql."\n";
    $q->QUERY_SQL($sql);


}



