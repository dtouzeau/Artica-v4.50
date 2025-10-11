<?php


class rsyslogd_events{
    private $results=array();

    public function __construct(){
    }


    private function Parse1($line):bool{

        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#",$line,$re)){return false;}

        $this->results["Month"]=$re[1];
        $this->results["Day"]=$re[2];
        $this->results["time"]=$re[3];
        $this->results["Hostname"]=$re[4];
        $this->results["program"]=$re[5];
        $this->results["pid"]=$re[6];
        $this->results["line"]=trim($re[7]);
        $this->results["API"]=__LINE__;
        return true;
    }

    private function ParseFortigate($line){


        if(!preg_match("#^.*?date=(.+?)\s+time=(.+?)\s+devname=\"(.+?)\"\s+(.+)#",$line,$re)){return false;}

        $sdate=$re[1]. $re[2];
        $this->results["Month"]=date("M",$sdate);
        $this->results["Day"]=date("d",$sdate);
        $this->results["time"]=$re[2];
        $this->results["Hostname"]=$re[3];
        $this->results["program"]="fortigate";
        $this->results["pid"]="000";
        $line=$re[4];
        if(preg_match_all("#([a-z]+)=\"(.+?)\"#",$line,$re)){
            $tt=array();
            foreach ($re[1] as $index=>$z){
                $tt[]="$z: <strong>".$re[2][$index]."</strong>";
            }

            $line=@implode(" ",$tt);
        }

        $this->results["line"]=$line;
        $this->results["API"]=__LINE__;
        return true;
    }

    private function ParseWindowsEvents2($line):bool{

        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+MSWinEventLog(.+)#",$line,$re)){
            echo "ParseWindowsEvents2 failed [$line]<br>";
            return false;}
        $this->results["Month"]=$re[1];
        $this->results["Day"]=$re[2];
        $this->results["time"]=$re[3];
        $this->results["Hostname"]=$re[4];
        $this->results["program"]="-";
        $this->results["pid"]="000";
        $this->results["line"]=trim($re[5]);
        


        if(preg_match("#^.*?[0-9]([A-Z])([a-z])([a-z])\s+([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+([0-9]+).*?[0-9]+.*?[0-9]+(.+)#",$line,$re)){
            $this->results["line"]=trim($re[8]);
        }
        $this->results["line"]=str_replace("#011","<br>",$this->results["line"]);
        return true;
    }

    private function ParseWindowsEvents($line):bool{
        if(strpos($line,"MSWinEventLog")>0){return $this->ParseWindowsEvents2($line);}
        if(strpos($line,"EvntSLog")==0){return false;}
        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+EvntSLog\s+(.+)#",$line,$re)){return false;}
        $this->results["Month"]=$re[1];
        $this->results["Day"]=$re[2];
        $this->results["time"]=$re[3];
        $this->results["Hostname"]=$re[4];
        $this->results["line"]=trim(utf8_encode($re[5]));
        $this->results["WINEVNTS"]=true;
        $this->results["pid"]="000";
        $this->results["program"]="-";
        $this->results["API"]=__LINE__;
        if(preg_match("#PID\s+([0-9]+)#",$line,$re)){
            $this->results["pid"]=$re[1];
            $this->results["API"]=__LINE__;
        }
        if(preg_match("#[A-Za-z]:(.+?)\.exe#", $line, $output_array)){
            $program=$output_array[1];
            $program=str_replace("\\","/",$program);
            $this->results["program"]=basename($program).".exe";
            $this->results["API"]=__LINE__;
        }

        if(preg_match("#^(.+?)\.\s+#",$line,$re)){
            $line=str_replace("$re[1].","",$line);
            $line=trim($line);
            $line=str_replace(". ",".<br>",$line);
            $line=str_replace(": -",":<br>-",$line);
            $this->results["line"]="<strong>$re[1].</strong><br>$line";
            $this->results["API"]=__LINE__;



        }

        return true;

    }

    public function parse_line($line=null):array{

        if(preg_match("#date=[0-9\-]+ time=[0-9:]+ devname=#",$line)){
            if($this->ParseFortigate($line)){
                return $this->results;
            }
        }

        if($this->ParseWindowsEvents($line)){
            return $this->results;
        }

        if($this->Parse1($line)){
            return $this->results;
        }

        $this->results["Month"]="00";
        $this->results["Day"]="1";
        $this->results["time"]="00:00:00";
        $this->results["Hostname"]="Unknown";
        $this->results["program"]="-";
        $this->results["pid"]="000";
        $this->results["line"]=$line;
        $this->results["API"]=__LINE__;
        return $this->results;
    }


}
