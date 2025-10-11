<?php


$data=@file_get_contents("/etc/artica-postfix/NIGHTLY_CONTENT");

if(is_file("/etc/artica-postfix/NIGHTLY_ARRAY")) {
    @unlink("/etc/artica-postfix/NIGHTLY_ARRAY");
}

if(!preg_match("#<CONTENT>(.+?)</CONTENT>#is", $data,$re)){die("/etc/artica-postfix/NIGHTLY_CONTENT DATA NO CONTENT\n");}

$MAIN=unserialize(base64_decode($re[1]));
if(!is_array($MAIN)){die("It is not an array...\n");}


$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
$script=build_menu($MAIN["OFF"]);

@file_put_contents("/tmp/menu.update.sh",$script);
@chmod("/tmp/menu.update.sh",0755);


function build_menu($MAIN){
    $CURRENTVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
    $CURRENTVERSIONBIN=intval(str_replace(".","",$CURRENTVERSION));
    $NEWVER=null;
    $python="/usr/bin/python";
    $DIALOG="/usr/bin/dialog";


    $f[]="#!/bin/bash";
    $f[]="INPUT=/tmp/menu.sh.$$";
    $f[]="OUTPUT=/tmp/output.sh.$$";
    $f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
    $f[]="DIALOG=\${DIALOG=dialog}";


    $f[]="";

    $diag[]="$DIALOG --clear  --nocancel --backtitle \"Software version $CURRENTVERSION\"";
    $diag[]="--title \"[ UPDATE ARTICA FIREMWARE ]\"";
    $diag[]="--menu \"your current version is: $CURRENTVERSION\nexeYou can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 50";

    $funct[]="function zQuit(){";
    $funct[]="\texit";
    $funct[]="}";

    foreach ($MAIN as $integer_version=>$array) {
        $VERSION=$array["VERSION"];
        $PREFIX="Rollback to";
        if($integer_version>$CURRENTVERSIONBIN){
            $PREFIX="Update to";

        }
        $MD5 = $array["MD5"];
        $URL = $array["URL"];


        $funct[]="function UPD$integer_version(){";
        $funct[]="\t$python /usr/share/artica-postfix/menu.update.py --update --url=\"$URL\" --md5=$MD5";
        $funct[]="}";

        $SIZE = $array["FILESIZE"];
        $SIZE=$SIZE/1024;
        $SIZE=round($SIZE/1024,2);

        if($CURRENTVERSIONBIN==$integer_version){echo "SKIP $integer_version\n";continue;}

        $diag[]="U$integer_version \"$PREFIX $VERSION ({$SIZE}MB)\"";
        $case[]="U$integer_version) UPD$integer_version;;";



    }

    $diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";

    $f[]="";

    $f[]="";
    $f[]=@implode("\n", $funct);
    $f[]="while true";
    $f[]="do";
    $f[]=@implode(" ", $diag);
    $f[]="menuitem=$(<\"\${INPUT}\")";
    $f[]="case \$menuitem in";
    $f[]=@implode("\n", $case);
    $f[]="Quit) zQuit;;";
    $f[]="esac";
    $f[]="done\n";
return @implode("\n",$f);
}
