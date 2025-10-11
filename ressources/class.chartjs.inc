<?php

class Chartjs
{

    public $container = "container";
    public $datas = array();
    public $xAxis = array();
    public $ChartType = 'areaspline';
    public $Title = 'Chart title';
    public $yAxisTtitle = 'Y Axis title';
    public $xAxisTtitle = "X Axis title";
    public $xAxis_labels = true;
    public $LegendPrefix = null;
    public $LegendSuffix = null;
    public $DataToSize =false;
    public $AreaColor = "#1ab394";
    public $PieDatas = array();
    public $PiePlotTitle = null;
    public $xdata = "18px";
    public $AxisFontsize = "14px";
    public $TitleFontSize = 22;
    public $subtitle = null;
    public $OnErrorEvent = null;
    public $SetRefreshCallBack = null;
    public $PieDataLabels = true;
    public $PieRedGreen = false;
    public $RemoveLock = false;
    public $SQL_QUERY = null;

    public function __construct($params = array())
    {
        foreach ($params as $key => $value) {
            $this->$key = $value;
        }

    }

    private function getCanvas():string{
        $html[]="var mucanDiv = document.getElementById('$this->container');";
        $html[]="var canvas = document.createElement('canvas');";
        $html[]="while (mucanDiv.firstChild) {";
        $html[]="\tmucanDiv.removeChild(mucanDiv.firstChild);";
        $html[]="}";
        $html[]="mucanDiv.appendChild(canvas);";
        $html[]="var parentWidth = mucanDiv.clientWidth; // Get the width of the parent div";
        $html[]="var parentHeight = mucanDiv.clientHeight || 350;";
        $html[]="canvas.width = parentWidth; // Set canvas width to match parent";
        $html[]="canvas.height = parentHeight; // Set canvas height to match parent";
        $html[]="var ctx = canvas.getContext('2d');";
        return @implode("\n", $html);
    }

    public function Pie():string{
        $tpl=new template_admin();
        $t=time();
        $title=$tpl->_ENGINE_parse_body($this->Title);
        $title=html_entity_decode($title);

        $bgcolors[]="#005447";
        $bgcolors[]="#006455";
        $bgcolors[]="#1ab394";
        $bgcolors[]="#3fb88f";
        $bgcolors[]="#5fbd8b";
        $bgcolors[]="#7cc287";
        $bgcolors[]="#97c782";
        $bgcolors[]="#b0cc5d";
        $bgcolors[]="#d4e297";
        $bgcolors[]="#F6F9D1";

        $html[]="function ChartJSPie$t(){";
        $html[]=$this->getCanvas();
        $i=0;
        $BColors=array();
        $BoColors=array();
        $labels=array();$zData=array();$specLabels=array();
        foreach ($this->PieDatas as $label=>$value){
            if($i>10){
                $i=0;
            }
            $labels[]="'$label'";
            $zData[]=$value;

            $BColors[]="'$bgcolors[$i]'";
            $BoColors[]="'#FFFFFF'";
            $i++;

            if($this->DataToSize){
                $Formatted=FormatBytes($value);
                $specLabels[]="'".html_entity_decode($Formatted)."'";
                continue;
            }
            if(is_numeric($value)){
                $Formatted=$tpl->FormatNumber($value);
                $specLabels[]="'".html_entity_decode($Formatted)."'";
                continue;
            }

        }
        $LabelPrefix="";

        if(!is_null($this->LegendPrefix)) {
            $LabelPrefix = $tpl->_ENGINE_parse_body($this->LegendPrefix);
        }


        $html[]="var data = {";
        $html[]="\tlabels: [".@implode(",", $labels)."],";
        $html[]="\tdatasets: [{";
        $html[]="\t\tlabel: '$LabelPrefix', // Label for the dataset";
        $html[]="\t\tdata: [".@implode(",", $zData)."], // Data values for each segment";
        $html[]="\t\tbackgroundColor: [".@implode(",", $BColors)."],";
        $html[]="\t\tborderColor: [".@implode(",", $BoColors)."],";
        $html[]="\t\tborderWidth: 1 // Border width";
        $html[]="\t}]";
        $html[]="};";
        $html[]="var options = {";
        $html[]="\tresponsive: true,";
        $html[]="\tlayout: {";
        $html[]="\t\tpadding: {";
        $html[]="\t\t\ttop: 10,";
        $html[]="\t\t\tbottom: 20,";
        $html[]="\t\t\tleft: 5,";
        $html[]="\t\t\tright: 10";
        $html[]="\t\t}";
        $html[]="\t},";
        $html[]="\tplugins: {";
        $html[]="\t\tlegend: {";
        $html[]="\t\t\tposition: 'right', // Position of the legend";
        $html[]="\t\t},";
        $html[]="\t\ttooltip: {";
        $html[]="\t\t\tenabled: true // Enable tooltips";
        $html[]="\t\t},";
        $html[]="\ttitle: {";
        $html[]="\t\tdisplay: true, // Enable the title";
        $html[]="\t\t\ttext: '$title', // Title text";
        $html[]="\t\t\tpadding: 0,";
        $html[]="\t\t\tbottom: -20,";
        $html[]="\t\tfont: {";
        $html[]="\t\t\tfamily: 'Helvetica',";
        $html[]="\t\t\tsize: 20, // Font size";
        $html[]="\t\t\tweight: 'normal' // Font weight";
        $html[]="\t\t},";
        $html[]="\t\tcolor: '#333', // Font color";
        $html[]="\t\tpadding: {";
        $html[]="\t\t\ttop: 15,";
        $html[]="\t\t\tbottom: 5";
        $html[]="\t\t},";
        $html[]="\t\talign: 'center'";
        $html[]="\t\t\t},";
        $html[]="\t\tdatalabels: {";
        $html[]="\t\t\tcolor: '#FFFFFF',";
        $html[]="\t\t\tfont: { weight: 'bold',size: 11},";
        $html[]="\t\t\tformatter: (value,context) => {";
        $def=false;
        if(count($specLabels)>0) {
            $html[] = "\t\t\t\tvar Speclabels = [".@implode(",", $specLabels)."];";
            $html[] = "\t\t\t\treturn Speclabels[context.dataIndex];";
            $def=true;
        }
        if(!is_null($this->LegendSuffix)) {
            $html[] = "\t\t\treturn value + ' $this->LegendSuffix';";
            $def=true;
        }
        if(!$def){
            $html[] = "\t\t\treturn value;";
        }
        $html[]="\t\t\t}";
        $html[]="\t\t}";
        $html[]="\t}";
        $html[]="};";
        $html[]="Chart.register(ChartDataLabels); // Register the plugin";
        $html[]="var myDoughnutChart = new Chart(ctx, { type: 'pie', data: data, options: options });";
        $html[]="}";
        $html[]="ChartJSPie$t();";
        return  @implode("\n", $html);
    }

    public function Doughnut2rows():string{
        $tpl=new template_admin();
        $title=$tpl->_ENGINE_parse_body($this->Title);
        $labels=array();
        $zData=array();
        $specLabels=array();
        foreach ($this->PieDatas as $label=>$value){
            $labels[]="'$label'";
            $zData[]=$value;

            if($this->DataToSize){
                $Formatted=FormatBytes($value);
                $specLabels[]="'".html_entity_decode($Formatted)."'";
            }

        }
        $title=html_entity_decode($title);
        $t=time();
        $html[]="function Doughnut2rows$t(){";
        $html[]=$this->getCanvas();
        $html[]="var data = {";
        $html[]="\tlabels: [".@implode(",", $labels)."],";
        $html[]="\tdatasets: [{";
        $html[]="\t\tlabel: '$title', // Label for the dataset";
        $html[]="\t\tdata: [".@implode(",", $zData)."], // Data values for each segment";

        $html[]="\t\tbackgroundColor: ['#1ab394','#ec4758'],";
        $html[]="\t\tborderColor: ['#1ab394','#FFFFFF'],";
        $html[]="\t\tborderWidth: 1 // Border width";
        $html[]="\t}]";
        $html[]="};";
        $html[]="var options = {";
        $html[]="\tresponsive: true, // Make the chart responsive";
        $html[]="\tplugins: {";
        $html[]="\t\tlegend: {";
        $html[]="\t\t\tposition: 'top', // Position of the legend";
        $html[]="\t\t},";
        $html[]="\t\ttooltip: {";
        $html[]="\t\t\tenabled: true // Enable tooltips";
        $html[]="\t\t},";
        $html[]="\t\tdatalabels: {";
        $html[]="\t\t\tcolor: '#FFFFFF',";
        $html[]="\t\t\tfont: { weight: 'bold',size: 14},";
        $html[]="\t\t\tformatter: (value,context) => {";
        $def=false;
        if(count($specLabels)>0) {
            $html[] = "\t\t\t\tvar Speclabels = [".@implode(",", $specLabels)."];";
            $html[] = "\t\t\t\treturn Speclabels[context.dataIndex];";
            $def=true;
        }
        if(!is_null($this->LegendSuffix)) {
            $html[] = "\t\t\treturn value + ' $this->LegendSuffix';";
            $def=true;
        }
        if(!$def){
            $html[] = "\t\t\treturn value;";
        }
        $html[]="\t\t\t}";
        $html[]="\t\t}";
        $html[]="\t}";
        $html[]="};";
        $html[]="Chart.register(ChartDataLabels); // Register the plugin";
        $html[]="var myDoughnutChart = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: options
        });";
        $html[]="}";
        $html[]="Doughnut2rows$t();";
        return  @implode("\n", $html);
    }

}
