<?php

class artica_rrd{
        private $img_path="/usr/share/artica-postfix/img/squid";
        public $period;
        public $flat=false;
        public $database=null;
        public $picture_name=null;
        public $tablename=null;
        public $title=null;
        public $labelx=null;
        public $width=950;
        public $heigth=500;

    private function getColors(){
        $Gridcolor="#ebebeb";
        $linecolor="1db496";
        $bgcolor="-c BACK#FFFFFF -c CANVAS#FFFFFF -c SHADEA#FFFFFF -c SHADEB#FFFFFF -c GRID$Gridcolor -c MGRID$Gridcolor -c ARROW#FFFFFF  --slope-mode --watermark \"$(date -R)\" --no-gridfit --font TITLE:13:Arial --font AXIS:8:'Arial' --font LEGEND:8:'Courier' --font UNIT:8:'Arial' --font WATERMARK:6:'Arial'";


        return array("Gridcolor"=>$Gridcolor,
            "linecolor"=>$linecolor,
            "bgcolor"=>$bgcolor,
            "width"=>$this->width,"heigth"=>$this->heigth,"flat_width"=>1200,"flat_heigth"=>150,
            "img_path"=>$this->img_path
        );

    }


    private function getPeriod(){
        $tperiod="-s \"-1$this->period\"";
        if($this->period=="hourly"){
            return "-s end-1h";
        }
        if($this->period=="yesterday"){
            return "--end 00:00";
        }
        return $tperiod;
    }

    public function simple_graph(){
        $flatName=null;
        $getColors=$this->getColors();
        $linecolor=$getColors["linecolor"];
        $bgcolor=$getColors["bgcolor"];
        $tperiod=$this->getPeriod();
        $img_path=$getColors["img_path"];
        $img_with=$getColors["width"];
        $img_heigth=$getColors["heigth"];

        if($this->flat){
            $img_with=$getColors["flat_width"];
            $img_heigth=$getColors["flat_heigth"];
            $flatName=".flat";
        }

        if(!is_dir($img_path)){@mkdir($img_path,0755,true);}

        if(!isset($GLOBALS["RRDTOOL"])) {
            $unix=new unix();
            $GLOBALS["RRDTOOL"] = $unix->find_program("rrdtool");
        }

        $picture_path="$img_path/$this->picture_name-$this->period$flatName.png";

        $cmd = "{$GLOBALS["RRDTOOL"]} graph $picture_path"
            . " $tperiod"
            . " -t \"$this->title\""
            . " --lazy -h $img_heigth -w $img_with -l 0 --border 0 --tabwidth 400"
            . " -a PNG"
            . " -v \"$this->labelx\""
            . " $bgcolor"
            . " DEF:filed=$this->database:$this->tablename:AVERAGE"
            . " CDEF:base=filed"
            . " GPRINT:base:MAX:\" Max\\: %5.1lf %s\""
            . " GPRINT:base:AVERAGE:\" Avg\\: %5.1lf %S\""
            . " GPRINT:base:LAST:\" Current\\: %5.1lf %S\\n\""
            . " LINE3:base#$linecolor:$this->labelx";

        echo $cmd . "\n";
        shell_exec($cmd);
    }
}