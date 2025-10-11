<?php

class status{

    var $logFile="";
    var $_postfix_status;
    var $array_queue;
    var $array_right;
    var $array_left;
    var $sock;
    var $cyrus_init_d;
    var $status_in_line;
    var $myini;
    var $newversions=array();
    function __construct($noconnect=0,$local_ini=null,$logfile=null){
        if($GLOBALS["VERBOSE"]){echo " -> status( .. LOADING ...)\n";}
        if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
        if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
        if($logfile<>null){$this->logFile=$logfile;}
        if(!file_exists(dirname(__FILE__) . '/settings.inc')){
            $this->events("ERROR: unable to stat " . dirname(__FILE__) . '/settings.inc',__FUNCTION__,__LINE__);
            return false;
        }
        $this->logFile="{$GLOBALS["ARTICALOGDIR"]}/background-status.debug";
        $this->events("Include settings.inc",__FUNCTION__,__LINE__);


        include(dirname(__FILE__) . '/settings.inc');
        $this->cyrus_init_d=$_GLOBAL["cyrus_initd_path"];
        $this->events("Loading sockets",__FUNCTION__,__LINE__);
        $sock=new sockets();
        $this->myini=$local_ini;
        $this->sock=new sockets();
        if($noconnect<>0){return null;}


    }

    function events($text,$function,$line ){

        if(!$GLOBALS["AS_ROOT"]){
            if($GLOBALS["VERBOSE"]){echo "class.status.inc: $function $text $line\n";}
            return;
        }
        if($this->logFile==null){$this->logFile="{$GLOBALS["ARTICALOGDIR"]}/background-status.debug";}
        $pid=getmypid();
        $date=date('H:i:s');
        $logFile=$this->logFile;
        $size=@filesize($logFile);
        if($size>1000000){@unlink($logFile);}
        $f = @fopen($logFile, 'a');
        $file=basename(__FILE__);
        $class=__CLASS__;
        @fwrite($f, "$file[$pid] $date $class::$function: $text\n");
        @fclose($f);
    }

    function ProcessExistsByCommand($command){
        $cmd="/usr/bin/pgrep -l -f \"$command\"";
        writelogs($cmd,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        exec($cmd,$vals);
        while (list ($num, $val) = each ($vals) ){
            writelogs("\"$val\" ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            $val=trim($val);
            if($val==null){continue;}
            if(preg_match("#pgrep#",$val)){continue;}
            if(!preg_match('#([0-9]+)#',$val,$re)){continue;}
            writelogs("\"$val\" Processing {$val["master_pid"]} PID",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            if(is_file("/proc/{$val["master_pid"]}/exe")){return true;}
        }
        return false;
    }

    public function PGREP($command){
        $command=str_replace(".","\.",$command);
        $command=str_replace("/","\/",$command);
        $cmd="/usr/bin/pgrep -l -f \"$command\"";
        writelogs($cmd,__FUNCTION__.'/'.__CLASS__,__FILE__,__LINE__);
        exec($cmd,$vals);
        while (list ($num, $val) = each ($vals) ){
            writelogs("\"$val\" ",__FUNCTION__.'/'.__CLASS__,__FILE__,__LINE__);
            $val=trim($val);
            if($val==null){continue;}
            if(preg_match("#pgrep#",$val)){continue;}
            if(!preg_match('#([0-9]+)#',$val,$re)){continue;}
            writelogs("\"$val\" Processing {$val["master_pid"]} PID",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            if(is_file("/proc/{$val["master_pid"]}/exe")){return true;}
        }
        return false;
    }





    function postfix_check_pid(){
        $queue=system("/usr/sbin/postconf -h queue_directory");
        if(!is_file("$queue/pid/master.pid")){
            $this->events("Scanning {$val["service_name"]} $queue/pid/master.pid does not exists");
            return false;
        }

        $pid=trim(@file_get_contents("$queue/pid/master.pid"));
        if(is_file("/proc/$pid/exe")){
            $this->events("Scanning {$val["service_name"]} /proc/$pid/exe exists, continue;");
            return true;
        }

        $this->events("Scanning {$val["service_name"]} /proc/$pid/exe does not exists, failed;");
        return false;
    }

    function PIDOF($path){
        $datas=system("/bin/pidof $path");
        $this->events("Scanning $path= $datas");
        if(trim($datas)<>null){return true;}

    }

    function AllStatus(){
        include_once(dirname(__FILE__) . '/class.ini.inc');
        $p=new usersMenus();
        $users=new usersMenus();

        if($this->sock->TestArticaPort()==false){
            $srv_artica=$this->BuildLittleTemplate('srv_artica',false);}else{$srv_artica=$this->BuildLittleTemplate('srv_artica',true);}

//-----------------------------------------------------------------------------------------------------------------------------		
        $stat=$users->POSTIFX_STATUS;
        $stat=explode(';',$stat);
        $postfixStatus=$stat[0];
        if($postfixStatus==1){
            $postfix_status=$this->BuildLittleTemplate('postfix_status',true);}else{$postfix_status=$this->BuildLittleTemplate('postfix_status',false,"IndexStartPostfix()");}

        if($users->fetchmail_installed==true){
            $stat=trim($users->FETCHMAIL_STATUS);
            $stat=explode(';',$stat);
            $title="fetchmail_status";
            if($stat[0]==1){
                $fetchmail_status=$this->BuildLittleTemplate($title,true);}else{
                $fetchmail_status=$this->BuildLittleTemplate($title,false);}

        }
//-----------------------------------------------------------------------------------------------------------------------------					
        writelogs("Cyrus installed ? $users->cyrus_imapd_installed",__FUNCTION__,__FILE__);
        if($users->cyrus_imapd_installed==true){
            $stat=trim($users->CYRUS_STATUS);
            $stat=explode(';',$stat);
            $title="cyrus_status";
            if($stat[0]==1){
                $cyrus_status=$this->BuildLittleTemplate($title,true);}else{
                $cyrus_status=$this->BuildLittleTemplate($title,false);}
        }
        //$sock=new sockets();
        if($users->KAV_MILTER_INSTALLED){
            $title="kav_milter_status";
            if ($users->KAVMILTER_PID<>null) {
                $kav_milter_status=$this->BuildLittleTemplate($title,true);}else{
                $kav_milter_status=$this->BuildLittleTemplate($title,false);}
        }

        if($users->SQUID_INSTALLED){
            $title="squid_status";
            if ($users->SQUID_PID<>null) {
                $squid_status=$this->BuildLittleTemplate($title,true);}else{
                $squid_status=$this->BuildLittleTemplate($title,false);}
        }




        if($users->DANSGUARDIAN_INSTALLED){
            $title="dansguardian_status";
            $sock=new sockets();
            $ini=new Bs_IniHandler();
            $ini->loadString($sock->getFrameWork('cmd.php?squid-status=yes'));
            if($ini->_params["DANSGUARDIAN"]["master_pid"]<>null){
                $dansguardian_status=$this->BuildLittleTemplate($title,true);}else{
                $dansguardian_status=$this->BuildLittleTemplate($title,false);}
        }
        $sock=new sockets();
        $ini=new Bs_IniHandler();
        $ini->loadString($sock->getfile('pureftd_status'));


        if($users->PUREFTP_INSTALLED){
            $title="pureftpd_satus";

            if($ini->_params["PUREFTPD"]["master_pid"]<>null){
                $pure_ftp=$this->BuildLittleTemplate($title,true);}else{
                $pure_ftp=$this->BuildLittleTemplate($title,false);}
        }

        if($ini->_params["SQLGREY"]["sqlgrey_installed"]==1){
            $title="sqlgrey_satus";
            if($ini->_params["SQLGREY"]["master_pid"]<>null){
                $sqlgrey=$this->BuildLittleTemplate($title,true);}else{
                $sqlgrey=$this->BuildLittleTemplate($title,false);}

        }



//-----------------------------------------------------------------------------------------------------------------------------	
        if($users->POSTFIX_INSTALLED==false){$postfix_status=null;$artica_filter=null;}
        $html="<H4>{services_status}</H4>
	<table>
	$srv_artica
	$postfix_status
	$sqlgrey
	$artica_filter
	$cyrus_status
	$fetchmail_status
	$kav_milter_status
	$squid_status
	$dansguardian_status
	$pure_ftp
	</table>
	";

        $in_line="
	<table style='width:100%'>
	<tr>
		<td>
			<table style='width:100%'>
			$srv_artica
			</table>
		</td>
		<td>
			<table style='width:100%'>
			$artica_filter
			</table>
		</td>	
		<td>
			<table style='width:100%'>
			
			</table>
		</td>	
		<td>
			<table style='width:100%'>
			$postfix_status
			</table>
		</td>
		<td>
			<table style='width:100%'>
			$cyrus_status
			</table>
		</td>							
	</tr>
	
	";
        $this->status_in_line=$in_line;
        return $html;



    }


    function BuildLittleTemplate($title,$status=false,$onfailed=null){
        if($status==false){
            if($onfailed<>null){$a="<a href=\"javascript:$onfailed\">";$b="</a>";}
            return "<tr>
				<td width=1% style='border-bottom:1px dotted #CCCCCC' valign='top'><img src='img/icon_err.gif'></td>
				<td style='border-bottom:1px dotted #CCCCCC' valign='top'>{".$title."}</td>
				<td style='border-bottom:1px dotted #CCCCCC' valign='top'>$a{some_service_is_stopped}$b</td>
			</tr>";

        }else{
            return "<tr>
				<td width=1% style='border-bottom:1px dotted #CCCCC' valign='top'><img src='img/ok16.png'></td>
				<td style='border-bottom:1px dotted #CCCCCC' valign='top'>{".$title."}</td>
				<td  style='border-bottom:1px dotted #CCCCCC' valign='top'>{all_services_running}</td>
			</tr>";
        }


    }


    function artica_status(){

        if($this->sock->TestArticaPort()==false){
            $img='danger32.png';
            $text='{error_no_socks}<br> {request_start_artica_service}';
            $color='e53838';
            return $this->BuildDesign_infos($img,'{srv_artica}',$text,$color);
        }else{
            $pid=$this->sock->getfile('pid');
            $img='ok32.png';
            $text="{service_running} {using_pid} $pid {on port}:{$this->sock->remote_port}";
            $color='18a712';
        }
        if(!$this->sock->error==false){
            if($this->_postfix_status==0){
                $this->array_left[]=$this->BuildDesign_infos('i32.png','{postfix_status_stopped}','{recommend_restart_postfix}','5e5ec7');

            }
        }

        return $this->BuildDesign_infos($img,'{srv_artica}',$text,$color);

    }










    public function squid_get_current_users_count(){
        $sock=new sockets();
        $count=0;
        $RTTSIZEARRAY=unserialize(base64_decode($sock->getFrameWork("squid.php?currentusersize=yes")));
        if(count($RTTSIZEARRAY["UID"])>0){
            return count($RTTSIZEARRAY["UID"]);
        }

        if($count==0){
            if(count($RTTSIZEARRAY["IP"])>0){
                return count($RTTSIZEARRAY["IP"]);
            }
        }
        if($count==0){
            return 0;
        }

    }


    private function squid_get_current_users(){
        if(!$this->is_squid_statistics()){return "<!-- ". date('Y-m-d H:is')." is_squid_statistics FALSE -->";}
        $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
        $sock=new sockets();
        $count=$this->squid_get_current_users_count();

        if($count==0){
            return "<!-- squid_get_current_users()::". date('Y-m-d H:is')." COUNT = 0 -->";
        }

        $categorized_websites="<div style='font-size:12px;text-align:right;width:220px'
		$pointers OnClick=\"Loadjs('squid.members-rtt.php?js=yes')\"><!-- ". date('Y-m-d H:is')." -->
		<i><strong style='text-decoration:underline'>$count {members} (". date('H')."h)</strong></i>
		</div>";
        return $categorized_websites;


    }


    private function is_squid_statistics(){
        if(isset($GLOBALS["is_squid_statistics"])){return $GLOBALS["is_squid_statistics"];}
        $sock=new sockets();
        $users=new usersMenus();
        $SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
        $EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
        $DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
        $EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
        if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
        if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
        if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
        $UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
        if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
        if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
        if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}

        if($users->PROXYTINY_APPLIANCE){$DisableArticaProxyStatistics=1;}
        if($EnableRemoteStatisticsAppliance==1){$DisableArticaProxyStatistics=1;}
        if($SquidActHasReverse==1){$DisableArticaProxyStatistics=1;}
        if($users->STATS_APPLIANCE){$DisableArticaProxyStatistics=0;}


        if($DisableArticaProxyStatistics==1){
            if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d;font-size:22px'>NOT AS STATS FOR SQUID</span><br>\n";}
            $GLOBALS["is_squid_statistics"]=false;
            return $GLOBALS["is_squid_statistics"];
        }

        if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d;font-size:22px'>AS STATS FOR SQUID</span><br>\n";}
        $GLOBALS["is_squid_statistics"]=true;
        return $GLOBALS["is_squid_statistics"];
    }

    function proxy_sum_of_requests(){

        if(!is_file(PROGRESS_DIR."/PROXY_REQUESTS_NUMBER")){return 0;}
        $num=@file_get_contents(PROGRESS_DIR."/PROXY_REQUESTS_NUMBER");
        return numberFormat($num,0,""," ");


    }



    function Squid_status($NOCACHE=false){
    }

    function Postfix_multi_status($hostname){

        $sock=new sockets();
        $q=new mysql();
        $sql="SELECT SUM(`size`) as tsize,COUNT(msgid) as tcount FROM postqueue WHERE `instance`='$hostname'";
        $PostfixMultiCreateBubble=$sock->GET_INFO("PostfixMultiCreateBubble");
        if(!is_numeric($PostfixMultiCreateBubble)){$PostfixMultiCreateBubble=0;}
        $ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_events'));
        $tot=$ligne["tcount"];
        $tot_size=$ligne["tsize"]/1024;
        $tot_size=FormatBytes($tot_size);
        $maincf=new maincf_multi($hostname);
        $enabled=1;
        if($maincf->GET("DisabledInstance")==1){$enabled=0;$fontcolor="#B3B3B3";}



        $status_queues="
			<table>
			<tr>
				<td colspan=2><span style='font-size:13px'>{smtp_queues}</span></td>
			</tr>
				
	
				<tr $link>
					<td align='right' style='color:#C53110;border-top:1px dotted #CCCCCC'>Total:&nbsp;</td>
					<td style='color:#C53110;border-top:1px dotted #CCCCCC;font-size:12px'>
					<strong>$tot&nbsp;</strong>emails <i style='font-size:9px'>($tot_size)</i></td>
				</tr>
				
			</table>";

        $stat=unserialize(base64_decode($sock->getFrameWork("cmd.php?postfix-mutli-stat=$hostname")));

        switch ($stat[0]) {

            case -2:$img='i64.png';
                $text='{no_infos}';
                return $this->BuildDesign($img,'{postfix_status}',$text);
                break;

            case '-1':$img='status_postfix_bg_failed.png';
                $text='{service_not_installed}';
                break;
            case '1':$img='status_postfix_bg_ok.png';
                if(preg_match("#2\.7.*#",$stat[1])){$img='status_postfix_bg_ok27.png';}
                $text='{service_running}<br>{using_version} ' . $stat[1]. ' {pid} ' . $stat[2];
                break;
            case '0':

                $img='status_postfix_bg_failed.png';
                $text='<span style=color:#d32d2d>{service_stopped}<br>{using_version} ' . $stat[1]."</span>";
                $start="<hr><div style='text-align:right'>".button("{start}","MultipleInstanceRestart()")."</div>";


                break;
        }
        $start=null;
        if($enabled==0){
            $text="<span style=color:$fontcolor>{service_disabled}<br>{using_version} " . $stat[1]."</span>";
            $img='status_postfix_bg_grey.png';

        }

        if($PostfixMultiCreateBubble==1){
            $PostfixMultiCreateBubbleText="&nbsp;<i style='color:#9E0000;font-weight:bold'>{PostfixMultiCreateBubbleIsEnabled}</i>";
        }

        $design="
	<div style='background-image:url(img/$img);background-repeat:no-repeat;width:400px;height:210px'>
		<div style='padding-left:155px;min-height:250px'>
			<H3 style='font-size:16px;font-weight:bold;'>{APP_POSTFIX}</H3>
			<div style='font-size:11px;font-weight:bold;text-align:right;padding-top:4px;border-top:1px solid black;margin:4px'>$hostname</div>
			<br><span style='font-size:11px;font-weight:bold'>
				<i style='color:black'>$text</i> $start$PostfixMultiCreateBubbleText$status_queues</span>
		</div>
		<table style='width:100%'>
			<tr>
				<td valign='top'>
					<table style='width:100%;margin-top:5px'>
						<tr>
							<td style='border-right:2px solid black;padding:4px' align='center'>$amavis</td>
							<td style='border-right:2px solid black;padding:4px' align='center'>$dstat</td>
							<td style='border-right:2px solid black;padding:4px' align='center'>$rpcess_number</td>
						</tr>
					</table>
				</td>
				<td valign='top'>
					
				</td>
			</tr>
		</table>
	</div>
	";

        return $design;

    }

    function CATEGORIES_APPLIANCE(){

    }

    function WEBSTATS(){

    }


    function ZARAFA(){
        return $this->Postfix_satus(true);
    }


    function fetchmail_satus(){
        $user=new usersMenus();
        $stat=trim($user->FETCHMAIL_STATUS);
        $stat=explode(';',$stat);
        $this->_postfix_status=$stat[0];

        switch ($stat[0]) {

            case null:$img='i24.png';
                $text='{no_infos}';
                return $this->BuildDesign($img,'{fetchmail_status}',$text);
                break;

            case '-1':$img='warning24.png';
                $text='{service_not_installed}';
                break;
            case '1':$img='ok24.png';
                $text='{service_running}<br>{using_version} ' . $stat[1]. ' {pid} ' . $stat[2];
                break;
            case '0':$img='warning24.png';
                $text='{service_stopped}<br>{using_version} ' . $stat[1] . ' {pid} ' . $stat[2] . "<br><i style='color:#d32d2d'>{$user->fetchmail_daemon_logs}</i>";

                break;
        }

        return RoundedLightGreen($this->BuildDesign($img,'{fetchmail_status}',$text));


    }

    function cyrus_satus(){
        if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
        $title="{cyrus_status}";
        $init=basename($this->cyrus_init_d);
        $stat=trim($users->CYRUS_STATUS);
        $stat=explode(';',$stat);
        switch ($stat[0]) {
            case null:$img='i24.png';
                $text='{no_infos}';
                break;
            case '-1':$img='i24.png';
                $text='{service_not_installed}';
                break;
            case '1':$img='ok24.png';
                $text='{service_running}<br>{using_version} ' . $stat[1]. ' {pid} ' . $stat[2] .$this->banner_services($init);
                break;
            case '0':$img='warning24.png';
                $text='{service_stopped}<br>{using_version} ' . $stat[1] . ' {pid} ' . $stat[2] .$this->banner_services($init);
                break;
        }
        return $this->BuildDesign($img,$title,$text);



    }



    function avestatus_satus(){
        $stat=trim($this->sock->getfile('aveserver_status'));
        $stat=explode(';',$stat);
        $title="{aveserver_status}";
        switch ($stat[0]) {
            case null:$img='i24.png';
                $text='{no_infos}';
                break;
            case '-1':$img='i24.png';
                $text='{service_not_installed}';
                break;
            case '1':$img='ok24.png';
                $date=$this->avestatus_pattern_date($stat[3],$stat[4]);
                $text='{service_running}<br>{using_version} ' . $stat[1]. ' {pid} ' . $stat[2]  .$date .$this->banner_services('aveserver');
                break;
            case '0':$img='warning24.png';
                $date=$this->avestatus_pattern_date($stat[3],$stat[4]);
                $text='{service_stopped}<br>{using_version} ' . $stat[1] . ' {pid} ' . $stat[2] . $date. $this->banner_services('aveserver');
                break;
        }

        return $this->BuildDesign($img,$title,$text);

    }


    function avestatus_pattern_date($date,$time,$nobuild=0){
        if(preg_match('#([0-9]{2})([0-9]{2})([0-9]{4})#',$date,$reg)){
            $day=$reg[1];
            $month=$reg[2];
            $year=$reg[3];
        }else{return null;}

        if(preg_match('#([0-9]{2})([0-9]{2})#',$time,$reg)){
            $hour=$reg[1];
            $min=$reg[2];

        }else{return null;}
        $date1 = mktime($hour-2,$min,0,$month,$day,$year);
        $time_date=date('Y/m/d H:i/s',$date1);

        $date2 = mktime(date('H'),date('I'),0,date('m'),date('d'),date('Y'));
        $diff_date = $date2-$date1;
        $diff['H'] = (int)($diff_date/(60*60));
        $diff['D'] = (int)($diff_date/(60*60*24));
        if($diff['H'] >0){
            if($nobuild==0){
                $this->array_left[]=$this->BuildDesign_infos('warning32.png','{update_kaspersky_database_title}','{recommend_update_kaspersky}','ffa22a');
            }
        }
        if($nobuild==0){
            $text="<br>{pattern_update_date} :<strong>$time_date</stong><br></strong><i> {since} {$diff['D']} {days},  {$diff['H']} {hours} </i>";
        }else{
            $text="<strong>$time_date</stong><br></strong><i> {since} {$diff['D']} {days},  {$diff['H']} {hours}</i>";
        }
        return $text;

    }

    function dateadd($per,$n,$d) {
        switch($per) {
            case "yyyy": $n*=12;
            case "m":
                $d=mktime(date("H",$d),date("i",$d)
                    ,date("s",$d),date("n",$d)+$n
                    ,date("j",$d),date("Y",$d));
                $n=0; break;
            case "ww": $n*=7;
            case "d": $n*=24;
            case "h": $n*=60;
            case "n": $n*=60;
        }
        return $d+$n;
    }

    function BuildDesign($img,$title,$text,$letcon=null,$title_js=null){
        $h5_style=null;
        if($title_js<>null){
            $js_h5="OnClick=\"$title_js\" OnMouseOver=\"this.style.cursor='pointer'\"
		OnMouseOut=\"this.style.cursor='default\"";
            $h5_style="border-bottom:1px solid #CCCCCC;'";


        }

        $html="
	<table style='border:0px;margin:5px'>
	<td width=1% valign='top'>
		<img src='img/$img'>
		$letcon	
	</td>
		
	<td valign='top' width=100%>
	<table style='width:100%'>
		<tr>
			<td><h5 $title_js $h5_style>$title</h5></td>
		</tr>
		<tr>
			<td valign='top'>$text</td>
		</tr>
	</table>
	</td>
	</tr>
	</table>";
        return $html;

    }
    function BuildDesign_infos($img,$title,$text,$color){
        $html="
	<div style='border:1px solid #CCCCCC;margin-bottom:5px'>
		<div style='background-color:#$color;width:250px;height:20px;padding-top:5px;'>
			<img src='img/$img' align='left'><strong style='font-size:12px;color:white;padding-left:5px;'>$title</strong>
		</div>
		
	<div style='padding:10px;'>$text</div>
	</div>";

        return $html;
    }

    function banner_services($service){
        $start="<a href='#' OnClick=\"javascript:artica_service_start('$service','applications:$service');\"  onMouseOver=\"javascript:AffBulle('{action_start_service}');\" OnMouseOut=\"javascript:HideBulle();\">";
        $stop="<a href='#' OnClick=\"javascript:artica_service_stop('$service','applications:$service');\" onMouseOver=\"javascript:AffBulle('{action_stop_service}');\" OnMouseOut=\"javascript:HideBulle();\">";
        return
            "
	<table style='margin:0px;padding:0px;border:0px;width:90%;;border-top:1px solid #3939ac;margin-top:8px;'>
	<tr>
	<td align='right' style='padding:0px;margin:0px'>
	<table style='margin:0px;padding:0px;border:0px;width:1%' width=1%>
	<tr>
		<td width=1% style='margin:0px;padding:0px' nowrap>&nbsp;$start<img src='img/task-run.gif'></a>&nbsp;</td>
		<td width=1% style='margin:0px;padding:0px;' nowrap>&nbsp;$stop<img src='img/task-stop-on.gif'></a>&nbsp;</td>
	</tr>
	</table>
	</td>
	</tr>
	</table>";
    }

    function ParseIniStatus(){
        if(!is_file($this->myini)){return "<tr><td>{error_waiting_sync}</td></tr>";}
        $ini=new Bs_IniHandler($this->myini);

        if($ini->_params["SQLGREY"]["sqlgrey_installed"]==1){
            $title="sqlgrey_satus";
            if($ini->_params["SQLGREY"]["master_pid"]<>null){
                $sqlgrey=$this->BuildLittleTemplate($title,true);}else{
                $sqlgrey=$this->BuildLittleTemplate($title,false);}
        }

        $POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
        if($POSTFIX_INSTALLED==1){
            $title="postfix_status";
            if($ini->_params["SQLGREY"]["master_pid"]<>null){
                $postfix=$this->BuildLittleTemplate($title,true);}else{
                $postfix=$this->BuildLittleTemplate($title,false);}
        }
        $tpl=new templates();
        return $tpl->_ENGINE_parse_body($postfix.$sqlgrey);

    }

    public function BuildNewersions(){
        if(!is_file(dirname(__FILE__).'/index.ini')){
            $this->NewVersionsEvents("unable to stat ". dirname(__FILE__).'/index.ini');
        }

        if(!$this->front_page_notify()){
            @file_put_contents(dirname(__FILE__)."/logs/jGrowl-new-versions.txt","#");
            $this->NewVersionsEvents("front_page_notify return false");
            return;
        }

        $users=new usersMenus();
        $this->status_artica_version($users);
        $this->status_amavis_version($users);
        $this->status_dansguardian_version($users);
        $this->status_fetchmail_version($users);
        $this->status_squid_version($users);
        $this->status_nmap_version($users);


        $this->status_spamassassin($users);
        $datas=serialize($this->newversions);

        @file_put_contents(dirname(__FILE__)."/logs/jGrowl-new-versions.txt",$datas);
        @chmod(dirname(__FILE__)."/logs/jGrowl-new-versions.txt",0755);

    }

    private function front_page_notify(){
        $sock=new sockets();
        $ini=new Bs_IniHandler("/etc/artica-postfix/settings/Daemons/ArticaAutoUpdateConfig");
        $front_page_notify=$ini->_params["AUTOUPDATE"]["front_page_notify"];
        $this->NewVersionsEvents("front_page_notify():: front_page_notify=$front_page_notify");
        if($front_page_notify==null){return true;}
        if(strtolower($front_page_notify)=='yes'){return true;}
        if(strtolower($front_page_notify)=='no'){return false;}
        return true;

    }

    private function NewVersionsEvents($text){
        writelogs($text,__CLASS__.'/'."BuildNewersions",__FILE__,__LINE__);
    }


    private function status_amavis_version($users){
        if($users->KASPERSKY_SMTP_APPLIANCE){return false;}
        if(!$users->AMAVIS_INSTALLED){return false;}
        $localversion=$users->AMAVISD_VERSION;
        if($localversion==null){return null;}
        if(preg_match("#([0-9\.]+)#",$localversion,$re)){$localversion=$re[1];}
        $ini=new Bs_IniHandler(dirname(__FILE__).'/index.ini');
        $remote_version=$ini->_params["NEXT"]["amavisd-new"];
        if(preg_match("#([0-9\.]+)#",$remote_version,$re)){$remote_version=$re[1];}
        $remote_int=str_replace('.','',$remote_version);
        $local_int=str_replace('.','',$localversion);

        if($remote_int>$local_int){
            $this->newversions["APP_AMAVISD_NEW"]=array(
                "TITLE"=>"{AMAVIS_NEWVERSION}:&nbsp;$remote_version",
                "TEXT"=>"{AMAVIS_NEWVERSION_TEXT}",
                "JS"=>"setup.index.progress.php?product=APP_AMAVISD_NEW&start-install=yes"
            );
        }
    }
    private function status_dansguardian_version($users){
        $sock=new sockets();if($sock->GET_INFO('SQUIDEnable')==0){return false;}
        if(!$users->DANSGUARDIAN_INSTALLED){return false;}
        $localversion=$users->DANSGUARDIAN_VERSION;
        if($localversion==null){$this->NewVersionsEvents(__FUNCTION__.":: unable to stat local dansguardian version");return null;}
        if(preg_match("#([0-9\.]+)#",$localversion,$re)){$localversion=$re[1];}
        $ini=new Bs_IniHandler(dirname(__FILE__).'/index.ini');
        $remote_version=$ini->_params["NEXT"]["dansguardian"];
        if(preg_match("#([0-9\.]+)#",$remote_version,$re)){$remote_version=$re[1];}
        $remote_int=str_replace('.','',$remote_version);
        $local_int=str_replace('.','',$localversion);
        $this->NewVersionsEvents(__FUNCTION__.":: Local $local_int against $remote_int ");
        if($remote_int>$local_int){
            $this->newversions["APP_DANSGUARDIAN"]=array(
                "TITLE"=>"{DANSGUARDIAN_NEWVERSION}:&nbsp;$remote_version",
                "TEXT"=>"{DANSGUARDIAN_NEWVERSION_TEXT}",
                "JS"=>"setup.index.progress.php?product=APP_DANSGUARDIAN&start-install=yes"
            );

        }
    }

    private function status_fetchmail_version($users){
        if(!$users->fetchmail_installed){$this->NewVersionsEvents(__FUNCTION__.":: fetchmail is not installed");return false;}
        $localversion=$users->FETCHMAIL_VERSION;
        if($localversion==null){$this->NewVersionsEvents(__FUNCTION__.":: unable to stat fetchmail version");return null;}
        if(preg_match("#([0-9\.]+)#",$localversion,$re)){$localversion=$re[1];}
        $ini=new Bs_IniHandler(dirname(__FILE__).'/index.ini');
        $remote_version=$ini->_params["NEXT"]["fetchmail"];
        if(preg_match("#([0-9\.]+)#",$remote_version,$re)){$remote_version=$re[1];}
        $remote_int=str_replace('.','',$remote_version);
        $local_int=str_replace('.','',$localversion);
        $this->NewVersionsEvents(__FUNCTION__.":: Local $local_int against $remote_int ");
        if($remote_int>$local_int){
            $this->newversions["APP_FETCHMAIL"]=array(
                "TITLE"=>"{FETCHMAIL_NEWVERSION}:&nbsp;$remote_version",
                "TEXT"=>"{FETCHMAIL_NEWVERSION_TEXT}",
                "JS"=>"setup.index.progress.php?product=APP_FETCHMAIL&start-install=yes"
            );

        }
    }




    private function status_squid_version($users){
        $sock=new sockets();if($sock->GET_INFO('SQUIDEnable')==0){return false;}
        if(!$users->SQUID_INSTALLED){$this->NewVersionsEvents(__FUNCTION__.":: squid is not installed");return false;}
        if(!is_file(dirname(__FILE__).'/index.ini')){$this->NewVersionsEvents(__FUNCTION__.":: unable to stat ".dirname(__FILE__).'/index.ini');return null;}
        $localversion=$users->SQUID_VERSION;
        if($localversion==null){$this->NewVersionsEvents(__FUNCTION__.":: unable to stat squid version");return null;}
        $ini=new Bs_IniHandler(dirname(__FILE__).'/index.ini');
        $remote_version=$ini->_params["NEXT"]["squid3"];
        $localversion=str_replace("STABLE","",$localversion);

        $this->NewVersionsEvents(__FUNCTION__.":: Local $localversion against $remote_version ");

        if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#",$localversion,$re)){
            $localversion=$re[1].'.'.$re[2].'.'.$re[3];
            $local_major=$re[1];
            $local_minor=$re[2];
            $local_build=$re[3];
        }



        if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#",$remote_version,$re)){
            $remote_major=$re[1];
            $remote_minor=$re[2];
            $remote_build=$re[3];
        }
        $new=false;
        if($local_major<$remote_major){$new=true;}
        if(!$new){
            if($local_major==$remote_major){
                if($remote_minor>$local_minor){$new=true;}
            }
        }

        if(!$new){
            if($local_major==$remote_major){
                if($local_minor==$remote_minor){
                    if($remote_build>$local_build){$new=true;}
                }
            }

        }


        if($new){
            $this->NewVersionsEvents(__FUNCTION__.":: new  $remote_version version");
            $this->newversions["APP_SQUID"]=array(
                "TITLE"=>"{SQUID_NEWVERSION}:&nbsp;$remote_version",
                "TEXT"=>"{SQUID_NEWVERSION_TEXT}",
                "JS"=>"setup.index.progress.php?product=APP_SQUID&start-install=yes"
            );

        }else{
            $this->NewVersionsEvents(__FUNCTION__.":: no new  current $localversion version");
        }
    }

    private	function status_nmap_version($users){

        if(!$users->nmap_installed){
            $this->NewVersionsEvents(__FUNCTION__.":: NMAP is not installed");
            return false;}


        $localversion=$users->nmap_version;
        $this->NewVersionsEvents(__FUNCTION__.":: Local version is $users->nmap_version");

        if(preg_match('#([0-9\.]+)#',$localversion,$re)){$localversion=$re[1];}
        $ini=new Bs_IniHandler(dirname(__FILE__).'/index.ini');
        $remote_version=$ini->_params["NEXT"]["nmap"];

        if(preg_match('#([0-9]+)\.([0-9]+)$#',$localversion,$re)){
            $localversion="{$re[1]}.{$re[2]}0";
        }

        $this->NewVersionsEvents(__FUNCTION__.":: remote version is $remote_version");
        if(preg_match('#([0-9]+)\.([0-9]+)$#',$remote_version,$re)){
            $remote_version="{$re[1]}.{$re[2]}0";
        }



        $remote_int=str_replace('.','',$remote_version);
        $local_int=str_replace('.','',$localversion);

        $this->NewVersionsEvents(__FUNCTION__.":: Local $local_int against $remote_int ");

        if($remote_int>$local_int){
            $this->newversions["APP_NMAP"]=array(
                "TITLE"=>"{NMAP_NEWVERSION}:&nbsp;$remote_version",
                "TEXT"=>"{NMAP_NEWVERSION_TEXT}",
                "JS"=>"setup.index.progress.php?product=APP_NMAP&start-install=yes"
            );
        }
    }




    private function status_spamassassin($users){
        if($users->KASPERSKY_SMTP_APPLIANCE){return false;}
        if(!$users->spamassassin_installed){$this->NewVersionsEvents(__FUNCTION__.":: SpamAssassin is not installed");return false;}
        $localversion=$users->spamassassin_version;


        if(preg_match('#([0-9\.]+)#',$localversion,$re)){$localversion=$re[1];}
        $ini=new Bs_IniHandler(dirname(__FILE__).'/index.ini');
        $remote_version=$ini->_params["NEXT"]["Mail-SpamAssassin"];



        if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)#',$remote_version,$re)){
            if(strlen($re[3])==1){$re[3]=$re[3]."0";}
            $remote_version="{$re[1]}.{$re[2]}.{$re[3]}";
        }

        if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)#',$localversion,$re)){
            if(strlen($re[3])==1){$re[3]=$re[3]."0";}
            $localversion="{$re[1]}.{$re[2]}.{$re[3]}";
        }
        $this->NewVersionsEvents(__FUNCTION__.":: Local version is $localversion");
        $this->NewVersionsEvents(__FUNCTION__.":: remote version is $remote_version");


        $remote_int=str_replace('.','',$remote_version);
        $local_int=str_replace('.','',$localversion);

        $this->NewVersionsEvents(__FUNCTION__.":: Local $local_int against $remote_int ");

        if($remote_int>$local_int){
            $this->newversions["APP_SPAMASSASSIN"]=array(
                "TITLE"=>"{SPAMASSASSIN_NEWVERSION}:&nbsp;$remote_version",
                "TEXT"=>"{SPAMASSASSIN_NEWVERSION_TEXT}",
                "JS"=>"setup.index.progress.php?product=APP_SPAMASSASSIN&start-install=yes"
            );

        }
    }

    private function status_artica_version($users){
        $ini=new Bs_IniHandler(dirname(__FILE__)."/index.ini");
        $remote_version=$ini->_params["NEXT"]["artica"];
        $local_version=$users->ARTICA_VERSION;
        $remote_version_bin=str_replace('.','',$remote_version);
        $local_version_bin=str_replace('.','',$local_version);
        if($local_version<$remote_version){
            $this->newversions["APP_ARTICA"]=array(
                "TITLE"=>"{upgrade_artica}:&nbsp;$remote_version",
                "TEXT"=>"{upgrade_artica_text}",
                "JS"=>"artica.update.php?js=yes"
            );


        }
    }
}
function status_pgrep($command){
    $cmd="/usr/bin/pgrep -l -f \"$command\"";
    writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
    exec($cmd,$vals);
    while (list ($num, $val) = each ($vals) ){
        writelogs("\"$val\" ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $val=trim($val);
        if($val==null){continue;}
        if(preg_match("#pgrep#",$val)){continue;}
        if(!preg_match('#([0-9]+)#',$val,$re)){continue;}
        writelogs("\"$val\" Processing {$val["master_pid"]} PID",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        if(is_file("/proc/{$val["master_pid"]}/exe")){return true;}
    }
    return false;
}