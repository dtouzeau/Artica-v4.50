<?php
if(!isset($GLOBALS["AS_ROOT"])){
	$GLOBALS["AS_ROOT"]=false;
	if(function_exists("posix_getuid")){
		if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}
	}
}
class os_system{
	var $meta_array=array();
	
	
function memory() {
    $results['ram'] = array('total' => 0, 'free' => 0, 'used' => 0, 'percent' => 0);
    $results['swap'] = array('total' => 0, 'free' => 0, 'used' => 0, 'percent' => 0);
    $results['devswap'] = array();
    
 	
    
    $bufr=null;
    if ($this->rfts('/proc/meminfo', $bufr)) {
      $bufe = explode("\n", $bufr);
      foreach($bufe as $buf) {
        if (preg_match('/^MemTotal:\s+([0-9]+)\s*kB/i', $buf, $ar_buf)) {
          $results['ram']['total'] = intval($ar_buf[1]);
        } else if (preg_match('/^MemFree:\s+([0-9]+)\s*kB/i', $buf, $ar_buf)) {
          $results['ram']['free'] = $ar_buf[1];
        } else if (preg_match('/^Cached:\s+([0-9]+)\s*kB/i', $buf, $ar_buf)) {
          $results['ram']['cached'] = $ar_buf[1];
        } else if (preg_match("#^Buffers:\s+([0-9]+)\s*kB#i", $buf, $ar_buf)) {
          $results['ram']['buffers'] = $ar_buf[1];
        }
      }
      $results['ram']['used'] = intval($results['ram']['total'])-intval($results['ram']['free']);
      $results['ram']['percent'] = round(($results['ram']['used']*100) /$results['ram']['total']);
      // values for splitting memory usage
      if (isset($results['ram']['cached']) && isset($results['ram']['buffers'])) {
        $results['ram']['app'] = $results['ram']['used']-intval($results['ram']['cached'])-intval($results['ram']['buffers']);
        $results['ram']['app_percent'] = round(($results['ram']['app']*100) /$results['ram']['total']);
        $results['ram']['buffers_percent'] = round(($results['ram']['buffers']*100) /$results['ram']['total']);
        $results['ram']['cached_percent'] = round(($results['ram']['cached']*100) /$results['ram']['total']);
      }
      if ($this->rfts('/proc/swaps', $bufr)) {
        $swaps = explode("\n", $bufr);
        for ($i = 1;$i < (sizeof($swaps));$i++) {
          if (trim($swaps[$i]) != "") {
            $ar_buf = preg_split('/\s+/', $swaps[$i], 6);
            $results['devswap'][$i-1] = array();
            $results['devswap'][$i-1]['dev'] = $ar_buf[0];
            $results['devswap'][$i-1]['total'] = $ar_buf[2];
            $results['devswap'][$i-1]['used'] = $ar_buf[3];
            $results['devswap'][$i-1]['free'] = ($results['devswap'][$i-1]['total']-$results['devswap'][$i-1]['used']);
            $results['devswap'][$i-1]['percent'] = round(($ar_buf[3]*100) /$ar_buf[2]);
            $results['swap']['total']+= $ar_buf[2];
            $results['swap']['used']+= $ar_buf[3];
            $results['swap']['free'] = $results['swap']['total']-$results['swap']['used'];
            $results['swap']['percent'] = ceil(($results['swap']['used']*100) /(($results['swap']['total'] <= 0) ? 1 : $results['swap']['total']));
          }
        }
      }
    }
    return $results;
  }
  
  	function rfts($strFileName, &$strRet, $intLines = 0, $intBytes = 4096, $booErrorRep = true) {
  $strFile = "";
  $intCurLine = 1;
  if (file_exists($strFileName)) {
    if ($fd = fopen($strFileName, 'r')) {
      while (!feof($fd)) {
        $strFile.= fgets($fd, $intBytes);
        if ($intLines <= $intCurLine && $intLines != 0) {
          break;
        } else {
          $intCurLine++;
        }
      }
      fclose($fd);
      $strRet = $strFile;
    } else {
      if ($booErrorRep) {
        
      }
      return false;
    }
  } else {
    if ($booErrorRep) {
      
    }
    return false;
  }
  return true;
}

public function cpu_info() {
    $results = array("cpus" => 0);
    if ($this->rfts('/proc/cpuinfo', $bufr)) {
      $bufe = explode("\n", $bufr);
      $results = array('cpus' => 0, 'bogomips' => 0);
      $ar_buf = array();
      foreach($bufe as $buf) {
        $arrBuff = preg_split('/\s+:\s+/', trim($buf));
        if (count($arrBuff) == 2) {
          $key = $arrBuff[0];
          $value = $arrBuff[1];
          // All of the tags here are highly architecture dependant.
          // the only way I could reconstruct them for machines I don't
          // have is to browse the kernel source.  So if your arch isn't
          // supported, tell me you want it written in.
          switch ($key) {
            case 'model name':
              $results['model'] = $value;
            break;
            case 'cpu MHz':
              $results['cpuspeed'] = sprintf('%.2f', $value);
            break;
            case 'cycle frequency [Hz]': // For Alpha arch - 2.2.x
              $results['cpuspeed'] = sprintf('%.2f', $value/1000000);
            break;
            case 'clock': // For PPC arch (damn borked POS)
              $results['cpuspeed'] = sprintf('%.2f', $value);
            break;
            case 'cpu': // For PPC arch (damn borked POS)
              $results['model'] = $value;
            break;
            case 'L2 cache': // More for PPC
              $results['cache'] = $value;
            break;
            case 'revision': // For PPC arch (damn borked POS)
              $results['model'].= ' ( rev: ' . $value . ')';
            break;
            case 'cpu model': // For Alpha arch - 2.2.x
              $results['model'].= ' (' . $value . ')';
            break;
            case 'cache size':
              $results['cache'] = $value;
            break;
            case 'bogomips':
              $results['bogomips']+= $value;
            break;
            case 'BogoMIPS': // For alpha arch - 2.2.x
              $results['bogomips']+= $value;
            break;
            case 'BogoMips': // For sparc arch
              $results['bogomips']+= $value;
            break;
            case 'cpus detected': // For Alpha arch - 2.2.x
              $results['cpus']+= $value;
            break;
            case 'system type': // Alpha arch - 2.2.x
              $results['model'].= ', ' . $value . ' ';
            break;
            case 'platform string': // Alpha arch - 2.2.x
              $results['model'].= ' (' . $value . ')';
            break;
            case 'processor':
              $results['cpus']+= 1;
            break;
            case 'Cpu0ClkTck': // Linux sparc64
              $results['cpuspeed'] = sprintf('%.2f', hexdec($value) /1000000);
            break;
            case 'Cpu0Bogo': // Linux sparc64 & sparc32
              $results['bogomips'] = $value;
            break;
            case 'ncpus probed': // Linux sparc64 & sparc32
              $results['cpus'] = $value;
            break;
          }
        }
      }
      // sparc64 specific code follows
      // This adds the ability to display the cache that a CPU has
      // Originally made by Sven Blumenstein <bazik@gentoo.org> in 2004
      // Modified by Tom Weustink <freshy98@gmx.net> in 2004
      $sparclist = array('SUNW,UltraSPARC@0,0', 'SUNW,UltraSPARC-II@0,0', 'SUNW,UltraSPARC@1c,0', 'SUNW,UltraSPARC-IIi@1c,0', 'SUNW,UltraSPARC-II@1c,0', 'SUNW,UltraSPARC-IIe@0,0');
      foreach($sparclist as $name) {
        if ($this->rfts('/proc/openprom/' . $name . '/ecache-size', $buf, 1, 32, false)) {
          $results['cache'] = base_convert($buf, 16, 10) /1024 . ' KB';
        }
      }
      // sparc64 specific code ends
      // XScale detection code
      if ($results['cpus'] == 0) {
        foreach($bufe as $buf) {
          $fields = preg_split('/\s*:\s*/', trim($buf), 2);
          if (sizeof($fields) == 2) {
            list($key, $value) = $fields;
            switch ($key) {
              case 'Processor':
                $results['cpus']+= 1;
                $results['model'] = $value;
              break;
              case 'BogoMIPS': //BogoMIPS are not BogoMIPS on this CPU, it's the speed, no BogoMIPS available
                $results['cpuspeed'] = $value;
              break;
              case 'I size':
                $results['cache'] = $value;
              break;
              case 'D size':
                $results['cache']+= $value;
              break;
            }
          }
        }
        $results['cache'] = $results['cache']/1024 . " KB";
      }
    }
    $keys = array_keys($results);
    $keys2be = array('model', 'cpuspeed', 'cache', 'bogomips', 'cpus');
    foreach ($keys2be as $ar_buf){
      if (!in_array($ar_buf[1], $keys)) {
        $results[$ar_buf[1]] = 'N.A.';
      }
    }
    if ($this->rfts('/proc/acpi/thermal_zone/THRM/temperature', $buf, 1, 4096, false)) {
      $results['temp'] = substr($buf, 25, 2);
    }
    return $results;
  }

    private function time_min($firstTime){
        $data1 = $firstTime;
        $data2 = time();
        $difference = ($data2 - $data1);
        $results=intval(round($difference/60));
        if($results<0){$results=1;}
        return $results;

    }

  private function fixMemUsage():string{
        $free="/usr/bin/free";
        $awk="/usr/bin/awk";
        $MEM=$this->memory();

      $MEM_USAGE["swap_percent"]=$MEM['swap']['percent'];
      $MEM_USAGE["swap_used"]=$MEM['swap']['used'];
      $MEM_USAGE["swap_total"]=$MEM['swap']['total'];
      $MEM_USAGE["TIME"]=time();
        $cmd[]="$free | $awk '/Mem/{printf(\"Mem used: %.1f%\"),";
        $cmd[]=" $3/($2+.000000001)*100} /buffers\/cache/{printf(\", buffers: %.1f%\"), $4/($3+$4)+.000000001*100}";
        $cmd[]=" /Swap/{printf(\", swap: %.1f%\"), $3/($2+.000000001)*100}'";
        $cmdline=@implode("",$cmd);
        exec("$cmdline 2>&1",$results);
        if(preg_match("#Mem used: ([0-9\.]+)#",$results[0],$re)){
            $MEM_USAGE["percent"]=$re[1];
        }

      @chmod("/usr/share/artica-postfix/ressources/mem.pl",0755);
      $cmdline=dirname(__FILE__)."/mem.pl";
      if($GLOBALS["VERBOSE"]){echo "$cmdline\n--------------------------------------------\n";}
      $datas=shell_exec(dirname(__FILE__)."/mem.pl");
      if(preg_match('#T=([0-9]+) U=([0-9]+)#',$datas,$re)){
          $MEM_USAGE["ram_used"]=$re[2];
          $MEM_USAGE["memory_total"]=$re[1];
      }
      $data=serialize($MEM_USAGE);
      $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MEM_USAGE",base64_encode($data));
      return $data;
      // ;
  }
  
  function realMemory(){
      $MEM_USAGE=array();
      $MEM_USAGE_DATA=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MEM_USAGE"));
      if($MEM_USAGE_DATA==null){
          $MEM_USAGE_DATA=$this->fixMemUsage();
      }
  	  if(strlen($MEM_USAGE_DATA)<10) {
          if($GLOBALS["VERBOSE"]){echo "[DEBUG]: MEM_USAGE_DATA < 10\n";}
  	      return $MEM_USAGE;

  	  }
  	  $MEM_USAGE = @unserialize($MEM_USAGE_DATA);
  	  if(intval($MEM_USAGE["TIME"])==0) {
          $MEM_USAGE = @unserialize($this->fixMemUsage());
      }else{
  	      $min=$this->time_min($MEM_USAGE["TIME"]);
  	      if($min>4){$MEM_USAGE = @unserialize($this->fixMemUsage());}
      }

      if($GLOBALS["VERBOSE"]){print_r($MEM_USAGE);}

  	  if(is_array($MEM_USAGE)) {
          if (count($MEM_USAGE) > 2) {
              if(!isset($MEM_USAGE["percent"])){
                  $MEM_USAGE["percent"]=0;
              }

              $hash_mem["ram"]["percent"] = $MEM_USAGE["percent"];
              $hash_mem["ram"]["used"] = $MEM_USAGE["ram_used"];
              $hash_mem["ram"]["total"] = $MEM_USAGE["memory_total"];
              if($GLOBALS["VERBOSE"]){echo "[DEBUG]: retunr array ".__LINE__."\n";}
              return $hash_mem;

          }
      }

  		$hash_mem=array();

  		@chmod("/usr/share/artica-postfix/ressources/mem.pl",0755);
  		$cmdline=dirname(__FILE__)."/mem.pl";
  		if($GLOBALS["VERBOSE"]){echo "$cmdline\n--------------------------------------------\n";}
  		$datas=shell_exec(dirname(__FILE__)."/mem.pl");
  		if(preg_match('#T=([0-9]+) U=([0-9]+)#',$datas,$re)){
  			$ram_total=$re[1];
  			$ram_used=$re[2];
  			}
		$pourc=($ram_used*100)/$ram_total;
  		$pourc = round($pourc);
  		
  		$hash_mem["ram"]["percent"]=$pourc;
  		$hash_mem["ram"]["used"]=$ram_used;
  		$hash_mem["ram"]["total"]=$ram_total;
  		
  		return $hash_mem;
  		
  	
  }
  
  function swap(){
  	$MEM_USAGE=@unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MEM_USAGE")));
  	if(!is_array($MEM_USAGE)){$MEM_USAGE=array();}
  	if(count($MEM_USAGE)>2){
  	    if(!isset($MEM_USAGE["swap_percent"])){$MEM_USAGE["swap_percent"]=0;}
        if(!isset($MEM_USAGE["swap_used"])){$MEM_USAGE["swap_used"]=0;}
        if(!isset($MEM_USAGE["swap_total"])){$MEM_USAGE["swap_total"]=0;}
		return array($MEM_USAGE["swap_percent"],$MEM_USAGE["swap_used"],$MEM_USAGE["swap_total"]);
 
  			
  	}
  	
  	$datas=explode("\n",shell_exec('free -o'));
  	if(!is_array($datas)){return array();}
  	foreach ($datas as $val){
  		if(preg_match("#Swap:\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#",$val,$re)){
  			$total=$re[1];
  			$used=$re[2];
  			if($total==0){return array(0,0,0);}
  			$pourc=round(($used/$total)*100);
  			return array($pourc,$used,$total);
  		}
  		
  	}
  	
  	
  }  
function html_load_usage($loadc=0,$tiny=0){
	include_once(dirname(__FILE__).'/class.users.menus.inc');
	$users=new usersMenus();
	$cpunum=0;$text=null;
	if($GLOBALS["AS_ROOT"]){
		if(class_exists("unix")){
			$unix=new unix();
			$cpunum=$unix->CPU_NUMBER();
		}
	}
		
	if($cpunum==0){	
		if(!is_file("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER")){
			$sock=new sockets();
			$cpunum=intval($sock->getFrameWork("services.php?CPU-NUMBER=yes"));
		}else{
			$cpunum=intval(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/CPU_NUMBER"));
		}
	}
	
	$array_load=sys_getloadavg();
	$org_load=$array_load[0];
	$load=intval($org_load);
	//middle =$cpunum on va dire que 100% ($cpunum*2) + orange =0,75*$cpunum
	$max_vert_fonce=$cpunum;
	$max_vert_tfonce=$cpunum+1;
	$max_orange=$cpunum*0.75;
	$max_over=$cpunum*2;
    $pourc=0;
    if($cpunum>0) {
        $purc1 = $load / $cpunum;
        $pourc = round($purc1 * 100, 2);
    }
	$color="#5DD13D";
	if($load>=$max_orange){
		$color="#F59C44";
	}
	
	if($load>$max_vert_fonce){
		$color="#C5792D";
	}

	if($load>$max_vert_tfonce){
		$color="#83501F";
	}	
	

	
	if($load>=$max_over){
		$color="#640000";
		$text="<br>".texttooltip("{overloaded}","{overloaded}","Loadjs('overloaded.php')",null,0,"font-size:9px;font-weight:bold;color:#d32d2d");
	}

	$this->meta_array["LOAD"]["CPU_NUMBER"]=$cpunum;
	$this->meta_array["LOAD"]["POURC"]=$pourc;
	$this->meta_array["LOAD"]["COLOR"]=$color;
	$this->meta_array["LOAD"]["MAXOVER"]=$max_over;
	$this->meta_array["LOAD"]["ORG_LOAD"]=$org_load;

	if($pourc>100){$pourc=100;}
	
	
if($tiny==1){
return "
<tr>
	<td width=1% nowrap><strong style='font-size:12px'>{load_avg}:</strong></td>
</tr>
<tr>
	<td align='left'>
		<div style='width:100px;background-color:white;padding-left:0px;border:1px solid $color;margin-top:3px'>
			<div style='width:{$pourc}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
				<span style='color:white;font-size:11px;font-weight:bold'>$pourc%</span>
			</div>
		</div>
	</td>
</tr>
<tr>
	<td width=1% nowrap><strong>{load}: $org_load&nbsp;[$cpunum cpu(s)]$text</strong></td>
</tr>";		
	
}	

return "
<tr>
	<td width=1% nowrap class=legend nowrap>{load_avg}:</strong></td>
	<td align='left'>
		<div style='width:100px;background-color:white;padding-left:0px;border:1px solid $color;margin-top:3px'>
			<div style='width:{$pourc}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
				<span style='color:white;font-size:11px;font-weight:bold'>$pourc%</span>
			</div>
		</div>
	</td>
	<td width=1% nowrap><strong>{load}: $org_load&nbsp;[$cpunum cpu(s)]$text</strong></td>
</tr>";	
	
	 
	
	
}

private function uptime_installed() {
  if (!$times = posix_times()) {
    return ("unknown");
  } else {
    $now = $times['ticks'];
    $days = intval($now / (60*60*24*100));
    $remainder = $now % (60*60*24*100);
    $hours = intval($remainder / (60*60*100));
    $remainder = $remainder % (60*60*100);
    $minutes = intval($remainder / (60*100));
    return ("$days {days}, $hours {hours}, $minutes {minutes}");
  }
}


function uptime(){

     $e=file('/proc/uptime');
     preg_match('#(.*) (.*)#', $e[0], $e);
     $t=time();
     $Since=$t-intval($e[1]);
     if($GLOBALS["VERBOSE"]){echo "First integer {$e[1]} -> ".intval($e[1])." ($Since)\n";}     
     return $this->distanceOfTimeInWords($Since,time());
     $d = sprintf('%.0f', $e[1]);
	 $h = 0;
	 $m = '00';
   	 if(($d=($d-$d%60)/60)>0){
     	$m = sprintf("%02d",$d%60);
		$d = ($d-$m)/60;
		if($d>0){
			$h = sprintf("%02d",$d%24);
			$d = ($d-$h)/24;	
			}
		}
		return "$d {days}, $h:$m";
   }
        	
	
private function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
	    $distanceInSeconds = round(abs($toTime - $fromTime));
	    $distanceInMinutes = round($distanceInSeconds / 60);
	       
	        if ( $distanceInMinutes <= 1 ) {
	            if ( !$showLessThanAMinute ) {
	                return ($distanceInMinutes == 0) ? 'less than a minute' : '1 minute';
	            } else {
	                if ( $distanceInSeconds < 5 ) {
	                    return 'less than 5 seconds ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 10 ) {
	                    return 'less than 10 seconds ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 20 ) {
	                    return 'less than 20 seconds ('.$distanceInSeconds.'s) ';
	                }
	                if ( $distanceInSeconds < 40 ) {
	                    return 'about half a minute ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 60 ) {
	                    return 'less than a minute';
	                }
	               
	                return '1 minute';
	            }
	        }
	        if ( $distanceInMinutes < 45 ) {
	            return $distanceInMinutes . ' minutes';
	        }
	        if ( $distanceInMinutes < 90 ) {
	            return 'about 1 hour';
	        }
	        if ( $distanceInMinutes < 1440 ) {
	            return 'about ' . round(floatval($distanceInMinutes) / 60.0) . ' hours';
	        }
	        if ( $distanceInMinutes < 2880 ) {
	            return '1 day';
	        }
	        if ( $distanceInMinutes < 43200 ) {
	            return 'about ' . round(floatval($distanceInMinutes) / 1440) . ' days';
	        }
	        if ( $distanceInMinutes < 86400 ) {
	            return 'about 1 month';
	        }
	        if ( $distanceInMinutes < 525600 ) {
	            return round(floatval($distanceInMinutes) / 43200) . ' months';
	        }
	        if ( $distanceInMinutes < 1051199 ) {
	            return 'about 1 year';
	        }
	       
	        return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' years';
	}

  

function html_Memory_usage(){
	include_once(dirname(__FILE__).'/class.users.menus.inc');
	$cachefileBlkid         = PROGRESS_DIR."/blkid.db";
	$sock                   = new sockets();
    $FileDescText           = null;
    $FileDesc_Text          = null;
    $swap_text              = null;
    $cpu                    = null;
    $dd                     = null;
    $BackupMaxDaysDirCurrentSize_text = null;
	$SessionPathInMemory    = $sock->GET_INFO("SessionPathInMemory");
    $DisableSWAPP           = $sock->GET_INFO("DisableSWAPP");
    $hash_mem               = $this->realMemory();
    $INODES_DEV             = array();
    $BLKID_ARRAY            = array();
    $USBTYPES               = array();
	if(!is_numeric($SessionPathInMemory)){$SessionPathInMemory=0;}
    if(!is_numeric($DisableSWAPP)){$DisableSWAPP=0;}

	$this->html_load_usage();
	$mem_used_p=$hash_mem["ram"]["percent"];
	$mem_used_kb=FormatBytes($hash_mem["ram"]["used"]);
	$total=FormatBytes($hash_mem["ram"]["total"]);

	$this->meta_array["MEM"]["MEM_USED_POURC"]=$mem_used_p;
	$this->meta_array["MEM"]["MEM_USED_KB"]=$hash_mem["ram"]["used"];
	$this->meta_array["MEM"]["TOTAL"]=$hash_mem["ram"]["total"];

	
	if($GLOBALS["VERBOSE"]){
		if($GLOBALS["AS_ROOT"]){
			include_once("/usr/share/artica-postfix/framework/class.unix.inc");
			$unix=new unix();
			$BLKID_ARRAY=$unix->BLKID_ALL();
		}
	}
	
	if(!is_file($cachefileBlkid)){
		$sock->getFrameWork("services.php?blkid-all=yes");
	}
	$BLKID_ARRAY=unserialize(@file_get_contents($cachefileBlkid));
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] ".print_r($BLKID_ARRAY);}
	
	if(!is_array($BLKID_ARRAY)){
		if($GLOBALS["AS_ROOT"]){
			include_once("/usr/share/artica-postfix/framework/class.unix.inc");
			$unix=new unix();
			$BLKID_ARRAY=$unix->BLKID_ALL();
		}
	}
	
	$hdparmsArr=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HdparmInfos")));
    if(!$hdparmsArr){
        $hdparms=array();
    }else{
        $hdparms=$hdparmsArr;
    }

	$users=new usersMenus();
    $Disks=$this->DISK_LIST();
    $sessionText=null;

	if(is_file(dirname(__FILE__)."/usb.scan.inc")){include dirname(__FILE__)."/usb.scan.inc";}
    if(!isset($_GLOBAL)){
        $_GLOBAL["usb_list"]=array();
    }
	if(is_array($_GLOBAL["usb_list"])){
		if($GLOBALS["VERBOSE"]){echo "<strong>usb_list = Array() OK GOOD</strong><br>\n";}
		foreach ($_GLOBAL["usb_list"] as $uuid => $array){
			if(!isset($array["MOUNTED"])){$array["MOUNTED"]=null;}
			if(!isset($array["ID_USB_DRIVER"])){$array["ID_USB_DRIVER"]=null;}
			
			if($GLOBALS["VERBOSE"]){
				foreach ($array as $a=>$b){
					echo "$uuid = &raquo; $a=$b<br>\n";
				}
			}
			if(isset($array["DEVLINKS"])){
				$TPDEV=explode(" ",$array["DEVLINKS"]);
				foreach ($TPDEV as $b){
					$DEVNAMES[trim($b)]=$array["PATH"];
				}
			}
			if($array["MOUNTED"]=="/boot"){$array["LABEL"]="boot";}
			
			$USBTYPES[$array["PATH"]]=$array["ID_USB_DRIVER"];
			if(isset($array["LABEL"])) {
                $LABELZ[$array["PATH"]] = $array["LABEL"];
            }
			$MOUNTED[$array["PATH"]]=$array["MOUNTED"];
			
		}
	}

    if(!isset($_GLOBAL["disks_list"])){
        $_GLOBAL["disks_list"]=array();
    }

	if(is_array($_GLOBAL["disks_list"])){
	    foreach ($_GLOBAL["disks_list"] as $dev=>$array){
	        if(!isset($array["ID_USB_DRIVER"])){$array["ID_USB_DRIVER"]=null;}
			if($GLOBALS["VERBOSE"]){echo "USB = &raquo; {$dev} = &raquo; {$array["ID_USB_DRIVER"]}<br>";}
			if(isset($array["ID_USB_DRIVER"])) {
                $USBTYPES[$dev] = $array["ID_USB_DRIVER"];
            }
			
		}	
	}
	
	if(!is_array($Disks)){VERBOSE("\$Disks == null",__LINE__);}
	
	if(is_array($Disks)){
        $array_dup = array();
        $dc=0;
        foreach ($Disks as $disk=>$values){
			$diskPath=$disk;
			if(isset($values["SIZE"])<>null){

				if(is_array($values)){
					$dc=$dc+1;
					
					if($disk=="shm"){continue;}
					if($disk=="tmpfs"){continue;}
					if($disk=="udev"){continue;}
					if($disk=="rootfs"){continue;}
					$disk=str_replace("mapper/","",$disk);
					$disk_name=basename($disk);
					if(strlen($disk_name)==1){continue;}
					if(strpos($disk_name,"by-uuid")>0){
						if(isset($DEVNAMES[$diskPath])){
							$disk_name=$DEVNAMES[$diskPath];
							$disk_name=str_replace("/dev/", "", $disk_name);
							$disk=$disk_name;
						}
					}
					if(strpos($disk_name,"by-uuid")>0){	
						if($GLOBALS["VERBOSE"]){echo "<strong>$disk_name &laquo; by-uuid</strong><br>\n";}
						continue;
					}
					$size=$values["SIZE"];
					$occ=$values["USED"];
					$disp=$values["DISP"];
					$pourc=$values["POURC"];
					
					if(!isset($LABELZ["/dev/$disk_name"])){
					
						if(!$GLOBALS["AS_ROOT"]){
							$label=base64_decode($sock->getFrameWork("cmd.php?disk-get-label=$disk_name"));
						}else{
                            $unix=new unix();
                            $label=$unix->DiskLabel($disk_name);
						}
						
						$this->events("Label:$label ",__LINE__);
					
					}else{
						
						$label=$LABELZ["/dev/$disk_name"];
						$this->events("Label:$label ",__LINE__);
					}
									
					if(strpos($label,"e2label")>0){$label=null;}
					if(!isset($array_dup[$disk])){$array_dup[$disk]=true;}else{continue;}
					if(preg_match("#([a-z\_\-]+)[0-9]+$#",$disk_name,$rt)){$disk_name=$rt[1];}
					
					$this->meta_array["DISKS"][$disk_name]["LABEL"]=$label;
					$this->meta_array["DISKS"][$disk_name]["SIZE"]=$size;
					$this->meta_array["DISKS"][$disk_name]["OCC"]=$occ;
					$this->meta_array["DISKS"][$disk_name]["DISP"]=$disp;
					$this->meta_array["DISKS"][$disk_name]["POURC"]=$pourc;

					if($users->IOSTAT_INSTALLED){
							if(isset($users->IOSTAT_ARRAY[trim($disk)])){
							$pouc_disk_io=$users->IOSTAT_ARRAY[trim($disk)];
							if($pouc_disk_io<>null){
								$pouc_disk_io=intval($pouc_disk_io);
								$color="#5DD13D";
								if($pouc_disk_io>40){$color="#F59C44";}
								if($pouc_disk_io>90){$color="#D32D2D";}
								
								$this->meta_array["DISKS"]["$disk_name"]["pouc_disk_io"]=$pouc_disk_io;
								$this->meta_array["DISKS"]["$disk_name"]["pouc_disk_io_color"]=$color;
							}
						}
					}
					
                    $disk_text=basename($disk);
					
					//LABEL DISK
					
					if($label==null){
						if(isset($BLKID_ARRAY[$diskPath]["LABEL"])){
							if(is_array($BLKID_ARRAY[$diskPath])){
								if(isset($BLKID_ARRAY[$diskPath]["LABEL"])){
									$label=$BLKID_ARRAY[$diskPath]["LABEL"];
								}
							}else{
								$label=$BLKID_ARRAY[$diskPath];
							}
								
						}
					}

                    $color="";
					if($label<>null){$disk_text="$label (". basename($diskPath).")";}
					$this->events("Disk text:$disk_text - $diskPath",__LINE__);
					$this->meta_array["DISKS"]["$disk_name"]["COLOR"]=$color;
                    if(!isset($USBTYPES[$diskPath])){$USBTYPES[$diskPath]=null;}
				}
			}
			
		}
	}

	$color="#5DD13D";
	if($mem_used_p>70){$color="#F59C44";}
	if($mem_used_p>79){$color="#D32D2D";}
	$this->meta_array["MEM"]["COLOR"]=$color;


	if($DisableSWAPP==0){
		$swapar=$this->swap();
		if(is_array($swapar)){
			$swap_color="rgb(93, 209, 61)";
			if($swapar[0]>30){
				$swap_color="#F59C44";
			}
			if($swapar[0]>50){
				$swap_color="#D32D2D";
			}	
			$this->meta_array["SWAP_POURC"]=$swapar[0];
			$this->meta_array["SWAP_COLOR"]=$swap_color;
			$this->meta_array["SWAP"]["USED"]=$swapar[1];
			$this->meta_array["SWAP"]["TOT"]=$swapar[2];

		}
	}
}
private function file_time_min($path){
	$last_modified=0;

	if(is_dir($path)){return 10000;}
	if(!is_file($path)){return 100000;}
		
	$data1 = filemtime($path);
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}

private function DISK_LIST(){
	$fileCache=PROGRESS_DIR."/disks-list";
	
	if(is_file($fileCache)){
		$TimeMin=$this->file_time_min($fileCache);
		if($TimeMin<15){return @file_get_contents($fileCache);}
	}
	
	@unlink($fileCache);
	
	if($GLOBALS["AS_ROOT"]){
		$unix=new unix();
		$Disks=$unix->DISK_LIST();
		@file_put_contents($fileCache, serialize($Disks));
		@chmod($fileCache, 0777);
		return $Disks;
	}
	$sock=new sockets();
	$Disks=unserialize(base64_decode($sock->getFrameWork("cmd.php?disks-list=yes")));
	@file_put_contents($fileCache, serialize($Disks));
	@chmod($fileCache, 0777);
	return $Disks;
}










function disk_type_array(){
	return array("1"=>"DOS 12-bit FAT","9"=>"AIX bootable","80"=>"Old MINIX","c7"=>"Syrinx",
   				"2"=>"XENIX root","a"=>"OPUS","81"=>"Linux/MINIX","db"=>"CP/M",
   				"3"=>"XENIX usr","40"=>"Venix 80286","82"=>"Linux swap","e1"=>"DOS access",
   				"4"=>"DOS 16-bit <32M", "51"=>"Novell?","83"=>"Linux native","e3"=>"DOS R/O",
   				"5"=>"Extended","52"=>"Microport","93"=>"Amoeba","f2"=>"DOS secondary",
   				"6"=>"DOS 16-bit >=32M","63"=>"GNU HURD","94"=>"Amoeba BBT","ff"=>"BBT",
   				"7"=>"OS/2 HPFS","64"=>"Novell","b7"=>"BSDI fs","8"=>"AIX","75"=>"PC/IX","b8"=>"BSDI swap");
 }
 
 

 
 private function events($text,$line){
 	$GLOBALS["LOGFILE"]=PROGRESS_DIR."/class.status.debug";
 	$pid=@getmypid();
 	$date=@date("H:i:s");
 	
 	
 	$size=@filesize($GLOBALS["LOGFILE"]);
 	if($size>1000000){@unlink($GLOBALS["LOGFILE"]);}
 	$f = @fopen($GLOBALS["LOGFILE"], 'a');
 	@fwrite($f, "$date [$pid]:".basename(__FILE__).":$line: $text\n");
 	@fclose($f);
 	@chmod($GLOBALS["LOGFILE"], 0777);
 }



	
}

?>