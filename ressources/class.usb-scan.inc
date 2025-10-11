<?php
include_once("/usr/share/artica-postfix/framework/class.unix.inc");


class usbscan{
    private $BLKID_ARRAY=array();
    private $DF_HL=array();
    private $DF_HLARRAY=array();
    private $DF_I=array();
    public $SERIALIZED=array();
    private $get_partitions_array=array();
    function __construct(){


    }


    public function disks_list(){
        $unix=new unix();
        $this->BLKID_ARRAY=$unix->BLKID_ARRAY();
        $this->DFHL();
        $fdisk=$unix->find_program("fdisk");
        $f[]="<?php";
        $f[]="\$_GLOBAL[\"disks_list\"]=Array(";

        exec("$fdisk -l 2>&1",$results);
        foreach ($results as $index=>$line){
            if(!preg_match("#^[A-Za-z]+\s+(.+?):\s+([0-9\.]+).*?([A-Za-z]+)#", $line,$re)){
                if($GLOBALS["VERBOSE"]){echo "$fdisk: NO MATCH  `$line`\n";}
                continue;}


            $dev=trim($re[1]);
            $dev=str_replace(" ", "", $dev);
            $dev=trim($dev);
            if(is_file("/sys/class/block/$dev/size")){
                $size=intval(@file_get_contents("/sys/class/block/$dev/size"));
                $size=round($size/1024);
            }else{
                $size=$re[2]." {$re[3]}";

            }


            if(strpos($dev, "(")>0){continue;}
            if(strpos(" $dev", '/')==0){continue;}
            preg_match("#([a-zA-Z0-9\_\-\/]+)#", $dev,$ri);
            $dev=$ri[1];

            if($GLOBALS["VERBOSE"]){echo "$fdisk: MATCH  `$line` [$dev]\n";}

            if(preg_match("#[\w]+,.*?([0-9]+).*?[\w]+#", $line,$rQ)){
                $this->SERIALIZED[$dev]["OCT"]=$rQ[1];
                $OCT=$rQ[1];
                if($GLOBALS["VERBOSE"]){echo "$fdisk: MATCH  `$line` [{$rQ[1]}]\n";}
            }else{
                if($GLOBALS["VERBOSE"]){echo "OCTETS NOT FOUND IN `$line`\n";}
            }

            $NewArray=$this->udevinfo_disks_query_all($dev);

            foreach ($NewArray as $a=>$b){$this->BLKID_ARRAY[$dev][$a]=$b;}
            preg_match("#([a-zA-Z0-9\_\-\/]+)#", $dev,$ri);
            $dev=$ri[1];unset($ri);
            $this->SERIALIZED[$dev]["SIZE"]=$size;

            $f[]="\t\"$dev\" => Array(";
            $f[]="\t\t\"SIZE\" => \"$size\",";
            $f[]="\t\t\"OCT\" => \"$OCT\",";
            if(isset($this->BLKID_ARRAY[$dev])){
                foreach ($this->BLKID_ARRAY[$dev] as $a=>$b){
                    $this->SERIALIZED[$dev][$a]=$b;
                    $f[]="\t\t\"$a\" => \"$b\",";}
                reset($this->BLKID_ARRAY);
            }

            $PARTITIONS=$f[]=$this->get_partitions($dev);
            $this->SERIALIZED[$dev]["PARTITIONS"]=$this->get_partitions_array;

            $f[]="\t\t\"PARTITIONS\"=>array(";
            $f[]=$PARTITIONS;
            $f[]="\t\t)";


            $f[]="\t),";


        }
        $f[]=");\n";

        if($GLOBALS["VERBOSE"]){print_r($this->SERIALIZED);}

        $f[]="\$_GLOBAL[\"usb_list\"]=array(";

        reset($this->BLKID_ARRAY);
        foreach ($this->BLKID_ARRAY as $dev=>$array){
            if(!isset($array["UUID"])){continue;}
            $UUID=$array["UUID"];
            if($UUID==null){continue;}
            $f[]="\t\"$UUID\"=>array(";
            foreach ($array as $a=>$b){
                $this->SERIALIZED["UUID"][$UUID][$a]=$b;
                $f[]="\t\t\"$a\"=>\"$b\",";
            }
            $f[]="\t),";
        }
        $f[]=");";
        $f[]="?>";
        return @implode("\n", $f);

    }

    private function udevinfo_disks_query_all($dev){
        if(is_file('/usr/bin/udevinfo')){
            $cmd="/usr/bin/udevinfo -a -p `/usr/bin/udevinfo -q path -n $dev`";
        }
        $ARRAY=array();

        if(is_file('/sbin/udevadm')){
            $cmd="/sbin/udevadm info --query=all --path=`/sbin/udevadm info --query=path --name=$dev`";
        }

        exec("$cmd 2>&1",$results);
        foreach ($results as $index=>$line){
            if(preg_match("#E:\s+([A-Z\_]+)=(.+)#", $line,$re)){
                $ARRAY[$re[1]]=trim($re[2]);
            }
        }

        return $ARRAY;

    }

    private function matches_partitions($line){
        if(preg_match("#^([a-z0-9\/\-]+)([\s|*]+)[0-9\+]+\s+[0-9\+]+\s+[0-9\+]+\s+([0-9a-zA-Z\+]+)\s+#", $line,$re)){
            return $re;
        }
        if(preg_match("#^([a-z0-9\/\-]+)([\s|*]+)[0-9\+]+\s+[0-9\+]+\s+.*?\s+([0-9a-z\+]+)\s+#", $line,$re)){
            return $re;
        }

        return array();

    }

    public function isExpand($mountPoint){

        $binpath="/usr/share/artica-postfix/bin/embiggen-disk";
        @chmod($binpath,0755);
        $cmd="$binpath -dry-run -verbose \"$mountPoint\" 2>&1";
        if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
        exec("$binpath -dry-run -verbose \"$mountPoint\" 2>&1",$results);

        foreach ($results as $line){
            if($GLOBALS["VERBOSE"]){echo "$line\n";}

            if(preg_match("#error: unknown MBR partition type \"(.+?)\" for\s+(.+)#",$line,$re)){
                $dev=$re[2];
                if(preg_match("#^(.+?)([0-9]+)$#",$dev,$re)){
                    $disk=$re[1];
                }
                $this->SERIALIZED[$disk]["EXPAND_ERROR"][$dev]=$line;
                return false;
            }

            if(preg_match("#Need to extend disk by.*?\(([0-9]+)\s+bytes#",$line,$re)){
                $EXTENDTO=intval($re[1]);
                if($GLOBALS["VERBOSE"]){echo "$mountPoint -> $EXTENDTO\n";}
                if($EXTENDTO>0){return array($EXTENDTO,$mountPoint);}
            }

        }
        return false;
    }

    private function get_partitions($dev){
        $unix=new unix();
        $fdisk=$unix->find_program("fdisk");
        $this->get_partitions_array=array();
        $MAIN_EXPAND=array();

        exec("$fdisk -l $dev 2>&1",$results);
        foreach ($results as $index=>$line){
            $line=trim($line);
            if($line==null){continue;}
            $re=$this->matches_partitions($line);
            if(count($re)<2){
                if($GLOBALS["VERBOSE"]){echo "get_partitions:: No match '$line'\n";}
                continue;}
            $dev=$re[1];
            $type=$re[3];
            $mounted=$this->mounted_path($dev,$unix);

            if($mounted<>null){
                if(!isset($MAIN_EXPAND[$dev])) {
                    $EXPANDED = $this->isExpand($mounted);
                    if (is_array($EXPANDED)) {
                        $MAIN_EXPAND[$dev] = $EXPANDED;
                    }
                }
            }

            $this->get_partitions_array[$dev]["MOUNTED"]=$mounted;
            $this->get_partitions_array[$dev]["TYPE"]=$type;

            $this->BLKID_ARRAY[$dev]["MOUNTED"]=$mounted;
            $this->BLKID_ARRAY[$dev]["TYPE"]=$type;
            $NewArray=$this->udevinfo_disks_query_all($dev);
            foreach ($NewArray as $a=>$b){
                $this->BLKID_ARRAY[$dev][$a]=$b;
                $this->get_partitions_array[$dev][$a]=$b;
            }

            if(isset($this->BLKID_ARRAY[$dev]["LABEL"])){
                $this->get_partitions_array[$dev]["ID_FS_LABEL"]=$this->BLKID_ARRAY[$dev]["LABEL"];
                $this->BLKID_ARRAY[$dev]["ID_FS_LABEL"]=$this->BLKID_ARRAY[$dev]["LABEL"];
            }

            $f[]="\t\t\t\"$dev\"=>array(";
            $f[]="\t\t\t\t\"MOUNTED\"=>\"$mounted\",";
            reset($this->BLKID_ARRAY);
            if(isset($this->BLKID_ARRAY[$dev])){
                foreach ($this->BLKID_ARRAY[$dev] as $a=>$b){
                    $this->get_partitions_array[$dev][$a]=$b;
                    $f[]="\t\t\t\t\"$a\" => \"$b\",";
                }
                reset($this->BLKID_ARRAY);
            }


            if(isset($this->DF_HL[$dev])){

                $DFL=$this->DF_HLARRAY[$dev];
                if(!is_array($DFL)) {
                    $this->get_partitions_array[$dev]["free_size"] = $DFL;
                    $this->get_partitions_array[$dev]["INFO"] = $DFL;
                    $f[] = "\t\t\t\t\"free_size\"=>\"" . $DFL . "\",";
                }
            }
            if(isset($this->DF_I[$dev])){
                $this->get_partitions_array[$dev]["INODES"]=$this->DF_I[$dev];
                $f[]="\t\t\t\t\"INODES\" => ".$this->DF_I[$dev].",";
            }

            $f[]="\t\t\t),";


        }

        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DISK_EXPAND",serialize($MAIN_EXPAND));
        return @implode("\n", $f);


    }

    private function mounted_path($dev,$unix){
        if($GLOBALS["VERBOSE"]){echo "mounted_path ? -> $dev\n";}
        $mounted=$unix->MOUNTED_PATH($dev);
        if($mounted<>null){return $mounted;}

        if(isset($this->BLKID_ARRAY[$dev]["DEVLINKS"])){
            $f=explode(" ",$this->BLKID_ARRAY[$dev]["DEVLINKS"]);
            foreach ($f as $dev2){
                $dev2=trim($dev2);
                if($GLOBALS["VERBOSE"]){echo "mounted_path ? -> $dev2\n";}
                $unix=new unix();
                $mounted=$unix->MOUNTED_PATH($dev2);
                if($mounted<>null){
                    if($GLOBALS["VERBOSE"]){echo "$dev2 ? -> $mounted\n";}
                    return $mounted;}
            }
        }
    return "";
    }

    private function DFHL(){
        $unix=new unix();
        $df=$unix->find_program("df");
        exec("$df -h -l 2>&1",$results);
        foreach ($results as $index=>$line){
            if(!preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+#",$line,$re)){continue;}

            if(strpos($re[1], "by-uuid")){
                $re[1]="/dev/".basename(@readlink($re[1]));
            }

            $this->DF_HL[$re[1]]="{$re[2]},{$re[3]},{$re[4]},{$re[5]}";

        }
        $results=array();
        exec("$df -i 2>&1",$results);
        foreach ($results as $index=>$line){
            if(!preg_match("#(.+?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+#",$line,$re)){continue;}


            if(strpos($re[1], "by-uuid")){
                $re[1]="/dev/".basename(@readlink($re[1]));
            }

            $this->DF_HLARRAY[$re[1]]["INODES"]=$re[2];
            $this->DF_HLARRAY[$re[1]]["INODES_UTIL"]=$re[3];
            $this->DF_HLARRAY[$re[1]]["INODES_FREE"]=$re[4];
            $this->DF_HLARRAY[$re[1]]["INODES_PERC"]=$re[5];
            $this->DF_I[$re[1]]="Array(\"DEV\"=>\"{$re[1]}\",\"INODES\"=>\"{$re[2]}\",\"UTIL\"=>\"{$re[3]}\",\"ILIB\"=>\"{$re[4]}\",\"IPOURC\"=>\"{$re[5]}\")";
        }

        $this->DFHL_BK();

    }

    private function DFHL_BK(){
        $unix=new unix();
        $df=$unix->find_program("df");
        exec("$df -BK -l 2>&1",$results);
        foreach ($results as $index=>$line){
            if(!preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+#",$line,$re)){continue;}


            if(strpos($re[1], "by-uuid")){
                $re[1]="/dev/".basename(@readlink($re[1]));
            }

            $this->DF_HL[$re[1]]="{$re[2]},{$re[3]},{$re[4]},{$re[5]}";
            if(preg_match("#^([0-9]+)#", $re[2],$rz)){$re[2]=$rz[1];}
            if(preg_match("#^([0-9]+)#", $re[3],$rz)){$re[3]=$rz[1];}
            if(preg_match("#^([0-9]+)#", $re[4],$rz)){$re[4]=$rz[1];}

            $this->DF_HLARRAY[$re[1]]["SIZE"]=$re[2];
            $this->DF_HLARRAY[$re[1]]["UTIL"]=$re[3];
            $this->DF_HLARRAY[$re[1]]["FREE"]=$re[4];
            $this->DF_HLARRAY[$re[1]]["PERC"]=$re[5];
        }
    }


}