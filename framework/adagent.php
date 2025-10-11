<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["status-instance"])){status_instance();exit;}
if(isset($_GET["statrt"])){status_instance_stats();exit;}
if(isset($_GET["start-instance"])){start_instance();exit;}
if(isset($_GET["stop-instance"])){stop_instance();exit;}
if(isset($_GET["restart-instance"])){restart_instance();exit;}
if(isset($_GET["restart-instance-silent"])){restart_instance_silent();exit;}
if(isset($_GET["build-instance"])){build_instance();exit;}
if(isset($_GET["reload-all-instances"])){reload_all_instances();exit;}
if(isset($_GET["main-status"])){status();exit;}
if(isset($_GET["global-status"])){global_status();exit;}
if(isset($_GET["global-stats"])){global_statistics();exit;}
if(isset($_GET["version"])){version();exit;}
if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["stop-socket"])){backend_stop();exit;}
if(isset($_GET["start-socket"])){backend_start();exit;}
if(isset($_GET["copy-conf"])){copy_conf();exit;}
if(isset($_GET["apply-conf"])){apply_conf();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["access-real"])){access_real();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["stop"])){stop();exit;}
if(isset($_GET["start"])){start();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function status(){

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.status.php --adagent >/usr/share/artica-postfix/ressources/logs/web/adagent.status 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);

}

function service_cmds(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $command=$_GET["service-cmds"];
    $cmd="$nohup /etc/init.d/adagent $command >/usr/share/artica-postfix/ressources/logs/web/adagent.cmds 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function install_tgz(){
    $migration=null;
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/adagent.install.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/adagent.install.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ad-agent.install.php --install {$_GET["key"]} {$_GET["OS"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function restart(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/adagent.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/adagent.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ad-agent.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function reload(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/adagent.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/adagent.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ad-agent.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function stop(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/adagent-stop.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/adagent-stop.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ad-agent.php --stop >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function start(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/adagent-stop.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/adagent-stop.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ad-agent.php --start >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function uninstall(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/adagent.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/adagent.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{disable_feature}<hr>{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ad-agent.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function access_real(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/adagent.log.tmp";
    $query2=null;
    $sourceLog="/var/log/adagent.log";

    if($_GET["FinderList"]<>null){
        $filename_compressed=PROGRESS_DIR."/logsfinder/{$_GET["FinderList"]}.gz";
        $filename_logs=PROGRESS_DIR."/logsfinder/{$_GET["FinderList"]}.log";
        if(is_file($filename_compressed)){
            if(!is_file($filename_logs)){
                $unix->uncompress($filename_compressed, $filename_logs);
                @chmod($filename_logs,0755);
                $sourceLog=$filename_logs;
            }else{
                $sourceLog=$filename_logs;
            }
        }
    }



    $rp=intval($_GET["rp"]);
    writelogs_framework("access_real -> $rp search {$_GET["query"]} SearchString = {$_GET["SearchString"]}" ,__FUNCTION__,__FILE__,__LINE__);

    $query=$_GET["query"];
    if($_GET["SearchString"]<>null){
        $query2=$query;
        $query=$_GET["SearchString"];
    }

    $grep=$unix->find_program("grep");
    $pattern2=array();

    $cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

    if($query2<>null){
        $pattern2=str_replace(".", "\.", $query2);
        $pattern2=str_replace("*", ".*?", $pattern2);
        $pattern2=str_replace("/", "\/", $pattern2);
        $cmd2="$grep --binary-files=text -Ei \"$pattern2\"| ";
        $cmd3="$grep --binary-files=text -Ei \"$pattern2\"";
    }

    if($query<>null){
        if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
            $pattern=str_replace(".", "\.", $query);
            $pattern=str_replace("*", ".*?", $pattern);
            $pattern=str_replace("/", "\/", $pattern);
        }
    }
    if($pattern<>null){

        if(preg_match("#error(\s+|=)([0-9]+)#i", $pattern,$re)){
            $pattern2[]="[0-9\+]+\s+{$re[2]}\s+[0-9\+]+";
        }

        if(preg_match("#ip(\s+|=)([0-9\.]+)#", $query,$re)){
            $pattern2[]=":\s+".str_replace(".", "\.", $re[2]).":";
        }

        if(preg_match("#to(\s+|=)([0-9\-\_a-zA-Z]+)#", $query,$re)){
            $pattern2[]="\/{$re[2]}\s+[0-9]+";
        }

        if(preg_match("#site(\s+|=)(.+?)($|\s+)#", $query,$re)){
            $re[2]=str_replace(".", "\.", $re[2]);
            $pattern2[]="(CONNECT|GET|POST)\s+{$re[2]}";
        }

        if(count($pattern2)>0){
            $pattern=$pattern2[0];
            unset($pattern2[0]);
        }
        if(count($pattern2)>0){
            foreach ($pattern2 as $pp){
                $FINALP[]="$grep --binary-files=text -Ei \"$pp\"";
            }

            $cmd2=@implode("| ", $FINALP)."|";
        }



        $cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog|$cmd2$tail -n $rp  >$targetfile 2>&1";
    }else{
        if($cmd3<>null){
            $cmd="$cmd3 $sourceLog|$cmd2 $tail -n $rp  >$targetfile 2>&1";
        }

    }



    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/adagent.log.cmd",$cmd);
    shell_exec($cmd);
    @chmod("$targetfile",0755);
}

function install(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/adagent.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/adagent.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.ad-agent.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function backend_stop(){
    $unix=new unix();
    $array=unserialize(base64_decode($_GET["stop-socket"]));
    $echo=$unix->find_program("echo");
    $socat=$unix->find_program("socat");
    if(!is_file($socat)){shell_exec("/usr/share/artica-postfix/bin/artica-make APP_SOCAT &");}
    $cmd="$echo \"disable server {$array[0]}/{$array[1]}\"|$socat stdio /var/run/adagent.stat 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function backend_start(){
    $unix=new unix();
    $array=unserialize(base64_decode($_GET["start-socket"]));
    $echo=$unix->find_program("echo");
    $socat=$unix->find_program("socat");
    if(!is_file($socat)){shell_exec("/usr/share/artica-postfix/bin/artica-make APP_SOCAT &");}
    $cmd="$echo \"enable server {$array[0]}/{$array[1]}\"|$socat stdio /var/run/adagent.stat 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function global_status(){
    $unix=new unix();
    $echo=$unix->find_program("echo");
    $socat=$unix->find_program("socat");
    if(!is_file($socat)){
        shell_exec("/usr/share/artica-postfix/bin/artica-make APP_SOCAT &");
    }

    $cmd="$echo \"show info\"|$socat stdio /var/run/adagent.stat 2>&1";
    exec($cmd,$results);
    writelogs_framework($cmd."=".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function global_statistics(){
    $unix=new unix();
    $echo=$unix->find_program("echo");
    $socat=$unix->find_program("socat");
    if(!is_file($socat)){
        shell_exec("/usr/share/artica-postfix/bin/artica-make APP_SOCAT &");
    }

    $cmd="$echo \"show stat\"|$socat stdio unix-connect:/var/run/adagent.stat >/usr/share/artica-postfix/ressources/logs/web/adagent.stattus.dmp 2>&1";
    exec($cmd,$results);
    writelogs_framework($cmd."=".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}



function version(){
    $unix=new unix();
    $xr=$unix->find_program("adagent");
    if(!is_file($xr)){return;}
    $version=$unix->HaProxyVersion($xr);
    echo "<articadatascgi>$version</articadatascgi>";
}

function restart_instance_silent(){
    $id=$_GET["ID"];
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php /usr/share/artica-postfix/exec.loadbalance.php --restart-instance $id >/dev/null 2>&1 &");
    shell_exec($cmd);
}

function restart_instance(){
    $id=$_GET["ID"];
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --stop-instance $id 2>&1";
    exec($cmd,$results);

    $cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --build-instance $id 2>&1";
    exec($cmd,$results);

    $cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --start-instance $id 2>&1";
    exec($cmd,$results);
    writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". @implode("\n", $results)."</articadatascgi>";
}
function build_instance(){
    $id=$_GET["ID"];
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php /usr/share/artica-postfix/exec.loadbalance.php --build-instance $id >/dev/null 2>&1 &");
    shell_exec($cmd);

}
function start_instance(){
    $id=$_GET["ID"];
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --start-instance $id 2>&1";
    exec($cmd,$results);
    writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". @implode("\n", $results)."</articadatascgi>";
}
function stop_instance(){
    $id=$_GET["ID"];
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.loadbalance.php --stop-instance $id 2>&1";
    exec($cmd,$results);
    writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". @implode("\n", $results)."</articadatascgi>";
}

function reconfigure_all_instances(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php /usr/share/artica-postfix/exec.loadbalance.php --build >/dev/null 2>&1");
    shell_exec($cmd);
}

function reload_all_instances(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php /usr/share/artica-postfix/exec.loadbalance.php --reload >/dev/null 2>&1 &");

}

function apply_conf(){
    $unix=new unix();
    if(!is_file(PROGRESS_DIR."/haproxy2.cfg")){return;}
    @unlink("/etc/adagent/adagent.cfg");
    @copy(PROGRESS_DIR."/haproxy2.cfg","/etc/adagent/adagent.cfg");
    @unlink(PROGRESS_DIR."/haproxy2.cfg");
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php /usr/share/artica-postfix/exec.ad-agent.php --reload --noconf >/dev/null 2>&1 &");
}


function copy_conf(){

    @unlink(PROGRESS_DIR."/adagent.cfg");
    @copy("/etc/adagent/adagent.cfg",PROGRESS_DIR."/adagent.cfg");
    @chmod(PROGRESS_DIR."/adagent.cfg", 0755);

}

