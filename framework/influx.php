<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.hd.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["retention-search"])){searchInSyslog();}


foreach ($_GET as $num=>$ligne){$a[]="$num=$ligne";}
writelogs_framework("Unable to understand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);


function searchInSyslog(){
    $unix       = new unix();
    $grep       = $unix->find_program("grep");
    $tail       = $unix->find_program("tail");
    $tfile      = "/usr/share/artica-postfix/ressources/logs/web/clean-postgres.syslog";
    $MAIN=unserialize(base64_decode($_GET["retention-search"]));


    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $IN=$MAIN["IN"];
    $PID=$MAIN["PID"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
    if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
    if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
    if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$PID_P}{$TERM_P}{$IN_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$date.*?$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/clean-postgres.log |$tail -n $max >$tfile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/dhcpd.syslog.pattern", $search);
    shell_exec($cmd);

}



























?>