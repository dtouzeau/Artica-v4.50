<?php


$logFile="/var/log/cgroup.tests";
$time1=time();

$f = @fopen($logFile, 'w');
$max=1000000;
echo "Writing $max entries\n";
$perc=0;
for($i=1;$i<$max;$i++){
    $xperc=$i/$max;
    $xperc=round($xperc*100);
    if($xperc<>$perc){
        $perc=$xperc;
        echo "Writing {$perc}%\n";
    }
    @fwrite($f, "$i\n");
}

@fclose($f);
$time2=time();
$xtime=$time2-$time1;
@unlink($logFile);
echo "Written  in $xtime seconds\n";

echo "Testing with reduced performances\n";
shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());
$time1=time();
$f = @fopen($logFile, 'w');
$max=10000000;
echo "Writing $max entries\n";
$perc=0;
for($i=1;$i<$max;$i++){
    $xperc=$i/$max;
    $xperc=round($xperc*100);
    if($xperc<>$perc){
        $perc=$xperc;
        echo "Writing {$perc}%\n";
    }

    @fwrite($f, "$i\n");}
@fclose($f);
$time2=time();
$xtime=$time2-$time1;
@unlink($logFile);
echo "Written in $xtime seconds with PHP Group\n";
