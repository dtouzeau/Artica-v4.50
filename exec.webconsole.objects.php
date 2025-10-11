<?php
ini_set('display_errors', 1);ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["PEITYMAX"]=100;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);


if(!Build_pid_func(__FILE__,"MAIN")){
    events(basename(__FILE__)." Already executed.. aborting the process");
    die("DIE Already executed.. aborting the process in" .__FILE__." Line: ".__LINE__);
}

if(system_is_overloaded()){
    events("{OVERLOADED_SYSTEM}, web console object will be not updated....");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBCONSOLE_SYS_LOAD",sysload());
    die(0);
}
$GLOBALS["PEITYCONF"]="{ width:255,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";

zexec();

function zexec():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBCONSOLE_SYS_TIME",time());
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBCONSOLE_SYS_CPU",syscpu());
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBCONSOLE_SYS_LOAD",sysload());
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBCONSOLE_SYS_BAND",bandwidth());
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBCONSOLE_SYS_DISK",sysdisk());
    return true;
}

function fs_filemax_prc():int{
    $filemax=intval(@file_get_contents("/proc/sys/fs/file-max"));
    $file_nr=@file_get_contents("/proc/sys/fs/file-nr");
    preg_match("#^([0-9]+)\s+#",$file_nr,$re);
    $current=intval($re[1]);
    $prc=$current/$filemax;
    $prc=$prc*100;
    $prc=round($prc);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fs_filemax_prc",$prc);
    return intval($prc);
}

function sysdisk():string{
    $tpl=new template_admin();
    $DISKS_INODES=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DISKS_INODES"));
    $fs_filemax_prc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("fs_filemax_prc"));
    $fs_class=null;
    $srcjs=array();
    $INODE_TEXT2=null;
    if($fs_filemax_prc==0){
        $fs_filemax_prc=fs_filemax_prc();
    }

    $datas=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/usb.scan.serialize"));
    $prc=$fs_filemax_prc;
    if($prc>90){$fs_class="text-warning";}
    if($prc>95){$fs_class="text-danger";}
    $INODE_TEXT1=$tpl->td_href("<small class='$fs_class'>Descriptors  {$prc}%</small>",null,"javascript:Loadjs('fw.rrd.php?img=system_fd');");
    if(!is_file(dirname(__FILE__)."/img/squid/system_fd-day.png")){
        $INODE_TEXT1="<small class='$fs_class'>Descriptors  {$prc}%</small>";

    }

    $fs_class=null;
    $nf_conntrack_loaded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nf_conntrack_loaded"));
    if($nf_conntrack_loaded==1) {
        $nf_conntrack_prc = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nf_conntrack_prc"));
        $prc = $nf_conntrack_prc;
        if ($prc > 90) {
            $fs_class = "text-warning";
        }
        if ($prc > 95) {
            $fs_class = "text-danger";
        }
        $INODE_TEXT2 = "&nbsp;|&nbsp;".$tpl->td_href("<small class='$fs_class'>Connections tracking {$prc}%</small>", null, "javascript:Loadjs('fw.rrd.php?img=nf_conntrack_count');");

        if(!is_file(dirname(__FILE__)."/img/squid/nf_conntrack_count-day.png")){
            $INODE_TEXT2="&nbsp;|&nbsp;<small class='$fs_class'>Connections tracking {$prc}%</small>";

        }

    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEM_FSNTRCK","$INODE_TEXT1$INODE_TEXT2");
    if(!isset($datas)){$datas=array();}
    if(!is_array($datas)){$datas=array();}


    foreach ($datas as $HDS=>$MAIN_HD){

        if ($HDS == "UUID") {continue;}
        if (count($MAIN_HD["PARTITIONS"]) == 0) {continue;}
        foreach ($MAIN_HD["PARTITIONS"] as $dev=>$MAIN_PART){
            $ID_FS_LABEL="";
            $DEVNAME=$dev;
            if(isset($MAIN_PART["DEVNAME"])) {
                $DEVNAME = $MAIN_PART["DEVNAME"];
            }
            $inodes_text=null;
            $inodes_row=array();
            $INODES_PRC=0;

            if(isset($DISKS_INODES[$DEVNAME])){
                $INODES_USED = $DISKS_INODES[$DEVNAME]["USED"];
                $INODES_TOT  = $DISKS_INODES[$DEVNAME]["INODES"];
                $INODES_PRC  = $DISKS_INODES[$DEVNAME]["POURC"];
                $INODE_TEXT="<small>{inode_size} {$INODES_USED}/{$INODES_TOT}</small>";

                $inodes_row[] = "<div>";
                $inodes_row[] = "            <div class=\"stat-percent font-bold text-success\">";
                $inodes_row[] = "                    {$INODES_PRC}% <i class=\"fa fa-bolt\"></i>";
                $inodes_row[] = "            </div>";
                $inodes_row[] = "            $INODE_TEXT";
                $inodes_row[] = "</div>";
                $inodes_text=@implode("\n",$inodes_row);
            }


            if(isset($MAIN_PART["ID_FS_LABEL"])) {$ID_FS_LABEL = $MAIN_PART["ID_FS_LABEL"];}
            if ($ID_FS_LABEL == "") {$ID_FS_LABEL = $MAIN_PART["MOUNTED"];}
            if ($ID_FS_LABEL == "/boot") {continue;}
            if(!isset($MAIN_PART["INFO"])) {continue;}

            $SIZE = round($MAIN_PART["INFO"]["SIZE"] / 1024);
            if ($SIZE == 0) {continue;}
            $PERCENT=round(($MAIN_PART["INFO"]["UTIL"]/$MAIN_PART["INFO"]["SIZE"])*100,2);

            $value1=intval($MAIN_PART["INFO"]["UTIL"]);
            $value2=$MAIN_PART["INFO"]["SIZE"]-$value1;

            $SIZE_TEXT = FormatBytes($MAIN_PART["INFO"]["SIZE"]);
            $UTIL_TEXT = FormatBytes($MAIN_PART["INFO"]["UTIL"]);
            $label_part   = "label-primary";
            $text_part   = "OK";
            if( ($PERCENT>70) OR ($INODES_PRC>70)){
                $label_part   = "label-warning";
                $text_part   = "{warning}";
            }
            if( ($PERCENT>95) OR ($INODES_PRC>95) ) {
                $label_part   = "label-danger";
                $text_part   = "{critical}";
            }
            if($ID_FS_LABEL<>"/"){continue;}
            if($ID_FS_LABEL=="/"){$ID_FS_LABEL="&nbsp;{system}";}

            $dahs="<span id=\"dashboard-".md5($ID_FS_LABEL)."\">$value1,$value2</span>";

            $srcjs[]="$(\"#dashboard-".md5($ID_FS_LABEL)."\").peity(\"pie\",{ fill: [\"#18a689\", \"#eeeeee\"], height:38,width:38 });";

            $html[] = "    <div class=\"ibox float-e-margins\">";
            $html[] = "        <div class=\"ibox-title\">";
            $html[] = "            <span class=\"label $label_part pull-right\">$text_part</span>";
            $html[] = "            <h5>{partition}:$ID_FS_LABEL</h5>";
            $html[] = "         </div>";
            $html[] = "         <div class=\"ibox-content\">";
            $html[] = "<table>";
            $html[] = "<tr>";
            $html[] = "<td style='width:99%'>";
            $html[] = "            <h1 class=\"no-margins\">{$PERCENT}%&nbsp;</h1>";
            $html[] = "</td>";
            $html[] = "<td style='width:1%' nowrap=''>$dahs</td>";
            $html[] = "</tr>";
            $html[] = "</table>";
            $html[] = "            <div class=\"stat-percent font-bold text-success\">";
            $html[] = "                    $SIZE_TEXT <i class=\"fa fa-bolt\"></i>";
            $html[] = "            </div>";
            $html[] = "            <small>{used} $UTIL_TEXT</small>";
            $html[] = "$inodes_text";
            $html[] = "        </div>";
            $html[] = "    </div>";
            $html[] = "";

        }
    }


    $page="fw.system.status.php";
    $html[]="<script>";
    $html[]=@implode("\n",$srcjs);
    $html[]="LoadAjaxSilent('frontend-notifications','$page?frontend-notifications=yes');";
    $html[]="</script>";
    return @implode($html);


}

function bandwidth():string{
    $tpl                    = new template_admin();

    if(!is_file("/usr/share/artica-postfix/ressources/logs/speed/latest")){
        return "";
    }
    $ligne=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/speed/latest"));
    $download=$ligne["download"];
    $upload=$ligne["upload"];
    $public_ip=$ligne["public_ip"];
    $isp=$ligne["isp"];
    $country=$ligne["country"];
    $tt=array();
    if(strlen($public_ip)>2){
        $tt[]=$public_ip."<br>";
    }
    if(strlen($isp)>2){
        $tt[]="($isp";
    }
    if(strlen($country)>2){
        $tt[]="/$country";
    }
    if(count($tt)>2){
        $tt[]=")";
    }
    if(count($tt)==0){
        $tt[]="{report}";
    }
    $subtitle=$tpl->td_href(@implode(" ",$tt),"{statistics}","Loadjs('fw.system.status.bandwidth.php')");
    $title_load="<span style='color:#337AB7'>$subtitle</span>";
    $html[]="                <div class=\"ibox float-e-margins\">";
    $html[]="                    <div class=\"ibox-title\">";
    $html[]="                        <span class=\"label label-primary pull-right\">OK</span>";
    $html[]="                        <h5>{bandwidth}</h5>";
    $html[]="                    </div>";
    $html[]="                    <div class=\"ibox-content\">";
    $html[]="                        <h1 class=\"no-margins\">{$download}Mbps </h1>";
    $html[]="                        <div class=\"font-bold text-success\">Upload: {$upload}Mbps <i class=\"fa fa-upload\"></i></div>";
    $html[]="                        <small>$title_load</small>";
    $html[]="                    </div>";
    $html[]="                </div>";
    return @implode($html);

}

function sysload():string{
    $tpl                    = new template_admin();
    $os                     = new os_system();
    $title_load             = "{load2}";
    $os->html_Memory_usage();
    $MAIN                   = $os->meta_array;
    $ORG_LOAD               = $MAIN["LOAD"]["ORG_LOAD"];
    $label_load             = "label-primary";
    $text_load              = "OK";
    $CPU_NUMBER             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    $MAXOVER                = $CPU_NUMBER*1.7;
    $MUNIN_CLIENT_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
    $EnableMunin            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    $MUNIN                  = false;
    if($MUNIN_CLIENT_INSTALLED==1){if($EnableMunin==1){$MUNIN=true;}}

    if(!isset($GLOBALS["PEITYCONF"])) {
        $GLOBALS["PEITYCONF"] = "{ width:255,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
    }


    $ORG_LOAD=round($ORG_LOAD,2);
    if($ORG_LOAD>$MAXOVER){
        $text_load="{critical}";
        $label_load="label-danger";
    }
    $js2="Loadjs('fw.rrd.php?img=load')";
    $title_load="<span style='color:#337AB7'>".
        $tpl->td_href("{load2}","{load}:{statistics}",$js2)."</span>";

    if($MUNIN){
        $title_load="<span style='color:#337AB7'>".
            $tpl->td_href("{load2}","{load}:{statistics}","Loadjs('fw.system.status.load.php')")."</span>";
    }

    $DASHBOARD_LOAD=unserialize(@file_get_contents("/etc/artica-postfix/DASHBOARD_LOAD"));
    if(!is_array($DASHBOARD_LOAD)){$DASHBOARD_LOAD=array();}
    if(count($DASHBOARD_LOAD)>$GLOBALS["PEITYMAX"]){
        $splice=count($DASHBOARD_LOAD)-$GLOBALS["PEITYMAX"];
        array_splice($DASHBOARD_LOAD, 0, $splice);
    }
    $DASHBOARD_LOAD[]=$ORG_LOAD;
    @file_put_contents("/etc/artica-postfix/DASHBOARD_LOAD",serialize($DASHBOARD_LOAD));

    $dashjs=null;
    $html[]="                <div class=\"ibox float-e-margins\">";
    $html[]="                    <div class=\"ibox-title\">";
    $html[]="                        <span class=\"label $label_load pull-right\">$text_load</span>";
    $html[]="                        <h5>{load2}</h5>";
    $html[]="                    </div>";
    $html[]="                    <div class=\"ibox-content\">";
    $html[]="                        <h1 class=\"no-margins\">{$ORG_LOAD}</h1>";
    $html[]="                        <div class=\"stat-percent font-bold text-success\">Max: $MAXOVER <i class=\"fa fa-bolt\"></i></div>";
    $html[]="                        <small>$title_load</small>";
    $html[]="                    </div>";
    if(count($DASHBOARD_LOAD)>1){
        $peity_conf=$GLOBALS["PEITYCONF"];
        $html[]="<span id=\"dashboard-load-line\">".@implode(",",$DASHBOARD_LOAD)."</span>";
        $dashjs="\t$(\"#dashboard-load-line\").peity(\"line\",$peity_conf);";
    }
    $html[]="                </div>";
    $page="fw.system.status.php";
    $html[]="<script>";
    $html[]="$dashjs";
    $html[]="\tLoadAjaxSilent('sysdisk','$page?sysdisk=yes');";
    $html[]="</script>";
    return @implode($html);

}

function GetContainerStats():array{

    $statsf=dirname(__FILE__)."/Docker/stats.json";
    if(!is_file($statsf)){
       return array();
    }
    $statsData=@file_get_contents($statsf);
    if(strlen($statsData)==0) {return array();}
    $DockerContainersStats = unserialize($statsf);
    if(!is_array($DockerContainersStats)){return array();}
    return $DockerContainersStats;
}


function syscpu():string{

    $tpl                    = new template_admin();
    $glances                = $tpl->GlancesInfos();
    $title_cpu              = "{cpu_use}";
    $CPU_NUMBER             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    $MUNIN_CLIENT_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
    $EnableMunin            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    $label_cpu              = "label-primary";
    $text_cpu               = "OK";
    $MUNIN                  = false;
    $ASDOCKER               = false;
    $acpu                   = array();

    if($CPU_NUMBER==0){
        $CPU_NUMBER=intval(exec("/usr/bin/nproc"));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CPU_NUMBER",$CPU_NUMBER);
    }

    if( $MUNIN_CLIENT_INSTALLED == 1){
        if($EnableMunin==1){
            $MUNIN=true;
        }
    }

    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){
        $ASDOCKER=true;
        $MUNIN=false;
    }

    if($MUNIN){
        $title_cpu="<span style='color:#337AB7'>".$tpl->td_href("{cpu_use}","{statistics}",
                "Loadjs('fw.system.status.cpu.php')")."</SPAN>";


    }
    $CPUPERCENT=0;
    if(!$ASDOCKER) {
        $CURRENT_CPU_AVG = floatval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CURRENT_CPU_AVG"));
        echo "CURRENT_CPU_AVG:\"$CURRENT_CPU_AVG\"\n";
        if (!is_null($CURRENT_CPU_AVG)) {
            if (is_float($CURRENT_CPU_AVG)) {
                $CPUPERCENT = round($CURRENT_CPU_AVG, 2);
            }
        }


        $DASHBOARD_CPU = unserialize(@file_get_contents("/etc/artica-postfix/DASHBOARD_CPU_MAP"));


        if (is_array($DASHBOARD_CPU)) {
            foreach ($DASHBOARD_CPU as $key => $value) {
                $acpu[] = $value;
            }


            if (count($DASHBOARD_CPU) > $GLOBALS["PEITYMAX"]) {
                $splice = count($DASHBOARD_CPU) - $GLOBALS["PEITYMAX"];
                array_splice($DASHBOARD_CPU, 0, $splice);
                @file_put_contents("/etc/artica-postfix/DASHBOARD_CPU_MAP", serialize($DASHBOARD_CPU));
            }

        }
    }else{
        $ContenerID=GetMyContainerID();
        $DockerContainersStats=GetContainerStats();
        if(isset($DockerContainersStats[$ContenerID])){
            $CPUPERCENT=$DockerContainersStats[$ContenerID]["CPUPerc"];
        }else{
            echo "ContenerID: $ContenerID - NOT FOUND IN ARRAY\n";
        }
        echo "ContenerID: $ContenerID - CPU = $CPUPERCENT\n";
    }

    if($CPUPERCENT>70){
        $label_cpu="label-warning";
        $text_cpu="{warning}";
    }
    if($CPUPERCENT>90){
        $label_cpu="label-danger";
        $text_cpu="{critical}";

    }
    $dashjs=null;
    $label_a="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('fw.rrd.php?img=system_cpu');\">";
    if(!is_file(dirname(__FILE__)."/img/squid/system_cpu-day.png")){
        $label_a=null;
    }


    $label="$label_a<span class='label $label_cpu'>{history}</span></a>";

    $html[]="                <div class=\"ibox float-e-margins\">";
    $html[]="                    <div class=\"ibox-title\">";
    $html[]="                        <span class=\"label $label_cpu pull-right\">$text_cpu</span>";
    $html[]="                        <h5>{cpu}</h5>";
    $html[]="                    </div>";
    $html[]="                    <div class=\"ibox-content\">";
    $html[]="                        <h1 class=\"no-margins\" id='dash-cpu-title'>{$CPUPERCENT}%</h1>";
    $html[]="                        <div class=\"stat-percent font-bold text-success\">{$CPU_NUMBER} CPUs <i class=\"fa fa-bolt\"></i>&nbsp;&nbsp;&nbsp;$label</div>";
    $html[]="                        <small>$title_cpu</small>";
    $html[]="                    </div>";
    if(count($acpu)>0){
        $html[]="<span id=\"dashboard-cpu-line\">".@implode(",",$acpu)."</span>";
        $peity_conf=$GLOBALS["PEITYCONF"];
        $dashjs="$(\"#dashboard-cpu-line\").peity(\"line\",$peity_conf);";
    }

    $html[]="                </div>";
    $page="fw.system.status.php";
    $html[]="<script>";
    $html[]="$dashjs";
    $html[]="LoadAjaxSilent('sysload','$page?sysload=yes');";
    $html[]="</script>";

    return @implode("\n",$html);

}

function GetMyContainerID():string{

    if(!is_file("/proc/self/cgroup")){return "";}
    $f=explode("\n",@file_get_contents("/proc/self/cgroup"));
    foreach ($f as $line){
        if(preg_match("#cpu:\/docker\/(.+)#",$line,$re)){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MyContainerID",$re[1]);
            return $re[1];
        }
    }
    return "";
}

function sysmemory():string{

    $tpl                    = new template_admin();
    $glances                = $tpl->GlancesInfos();
    $MUNIN_CLIENT_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
    $EnableMunin            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    $MUNIN                  = false;
    $title_memory           = "<span style='font-size:60%'>(". date("H:i:s").")</span>";
    if($MUNIN_CLIENT_INSTALLED==1){if($EnableMunin==1){$MUNIN=true;}}
    $MEM_USED_PERC          = 0;
    $MEM_USED_TOT2          = 0;
    $MEM_USED_KB2           = 0;
    $MEM_USED_KB2_KB        = 0;
    $label_memory           = "label-primary";
    $text_memory            = "OK";
    $MEM_USED_TEXT          = "";
    $MEM_USED_TOT2_GB       =   0;
    $DASHBOARD_MEM_CUR      = "";
    $DASHBOARD_MEM          = "";
    $ASDOCKER               = false;
    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){
        $ASDOCKER=true;
        $MUNIN=false;
    }

    if(!$ASDOCKER) {
        if (is_file("/etc/artica-postfix/DASHBOARD_MEM_CUR")) {
            $DASHBOARD_MEM_CUR = trim(@file_get_contents("/etc/artica-postfix/DASHBOARD_MEM_CUR"));
            if (strpos($DASHBOARD_MEM_CUR, ",") > 0) {
                $MEM_USED = explode(",", trim(@file_get_contents("/etc/artica-postfix/DASHBOARD_MEM_CUR")));
                $MEM_USED_PERC = intval($MEM_USED[0]);
                $MEM_USED_TOT2 = $MEM_USED[1];
                $MEM_USED_KB2 = $MEM_USED[2];
                $MEM_USED_TEXT = $MEM_USED[0];
            }
            if (isset($glances["mem"])) {
                $MEM_USED_PERC = $glances["mem"];
            }
        }

        if (is_file("/etc/artica-postfix/DASHBOARD_CPU_MEM")) {
            $DASHBOARD_MEM = unserialize(@file_get_contents("/etc/artica-postfix/DASHBOARD_CPU_MEM"));
        }

        if (!is_array($DASHBOARD_MEM)) {
            $DASHBOARD_MEM = array();
        }
        if (count($DASHBOARD_MEM) > $GLOBALS["PEITYMAX"]) {
            $splice = count($DASHBOARD_MEM) - $GLOBALS["PEITYMAX"];
            array_splice($DASHBOARD_MEM, 0, $splice);
            @file_put_contents("/etc/artica-postfix/DASHBOARD_CPU_MEM", serialize($DASHBOARD_MEM));

        }
    }else{
        $ContenerID=GetMyContainerID();
        $DockerContainersStats=GetContainerStats();
        if(isset($DockerContainersStats[$ContenerID])){
            $MEM_USED_PERC=$DockerContainersStats[$ContenerID]["MemPerc"];
            $MEM_USED_TOT2=$DockerContainersStats[$ContenerID]["MemUsage"];
        }else{
            echo "ContenerID: $ContenerID - NOT FOUND IN ARRAY\n";
        }
        echo "ContenerID: $ContenerID - CPU = $MEM_USED_PERC\n";

    }

    if($MEM_USED_PERC>70){
        $label_memory="label-warning";
        $text_memory="{warning}";
    }
    if($MEM_USED_PERC>90){
        $label_memory="label-danger";
        $text_memory="{critical}";

    }

    if($MUNIN){
        $title_memory="<span style='color:#337AB7'>".$tpl->td_href("{memory_used}","{statistics}",
                "Loadjs('fw.system.memory.php')")." <span style='font-size:60%'>(". date("H:i:s").")</span></span>";

    }
    if($MEM_USED_TOT2>1024) {
        $MEM_USED_TOT2_GB = FormatBytes($MEM_USED_TOT2 / 1024);
    }
    if($MEM_USED_KB2>1024) {
        $MEM_USED_KB2_KB = FormatBytes($MEM_USED_KB2 / 1024);
    }


    $label_a="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('fw.rrd.php?img=system_memory');\">";

    if(!is_file(dirname(__FILE__)."/img/squid/system_memory-day.png")){
        $label_a=null;
    }

    $label="$label_a<span class='label $label_memory'>{history}</span></a>";


    if(!is_array($DASHBOARD_MEM)){$DASHBOARD_MEM=array();}
    $dashjs=null;
    $html[]="                <div class=\"ibox float-e-margins\">";
    $html[]="                    <div class=\"ibox-title\">";
    $html[]="                        <span class=\"label $label_memory pull-right\">$text_memory</span>";
    $html[]="                        <h5>{memory} ($MEM_USED_TOT2_GB) </h5>";
    $html[]="                    </div>";
    $html[]="                    <div class=\"ibox-content\">";
    $html[]="                        <h1 class=\"no-margins\">$MEM_USED_KB2_KB</h1>";
    $html[]="                        <div class=\"stat-percent font-bold text-success\">{$MEM_USED_TEXT}% <i class=\"fa fa-bolt\"></i>&nbsp;&nbsp;&nbsp;$label</div>";
    $html[]="                        <small>$title_memory</small>";
    $html[]="                    </div>";
    if(count($DASHBOARD_MEM)>1){
        $peity_conf=$GLOBALS["PEITYCONF"];
        $html[]="<span id=\"dashboard-mem-line\">".@implode(",",$DASHBOARD_MEM)."</span>";
        $dashjs="\t$(\"#dashboard-mem-line\").peity(\"line\",$peity_conf);";
    }
    $html[]="                </div>";

    $page="fw.system.status.php";
    $html[]="<script>";
    $html[]=$dashjs;
    $html[]="\tLoadAjaxSilent('syscpu','$page?syscpu=yes');";
    $html[]="</script>";

    return @implode("\n",$html);

}

function events($text){$LOG_SEV=LOG_INFO;openlog("artica-system", LOG_PID , LOG_SYSLOG);syslog($LOG_SEV, $text);closelog();}

