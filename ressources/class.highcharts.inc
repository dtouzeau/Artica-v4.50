<?php

class highcharts{
	
	public $container="container";
	public $datas=array();
	public $xAxis=array();
	public $ChartType='areaspline';
	public $Title='Chart title';
	public $yAxisTtitle='Y Axis title';
	public $xAxisTtitle="X Axis title";
	public $xAxis_labels=true;
	public $LegendPrefix=null;
	public $LegendSuffix=null;
	public $AreaColor="#1ab394";
	public $PieDatas=array();
	public $PiePlotTitle=null;
	public $xdata="18px";
	public $AxisFontsize="14px";
    public $TitleFontSize=22;
	public $subtitle=null;
	public $OnErrorEvent=null;
	public $SetRefreshCallBack=null;
	public $PieDataLabels=true;
	public $PieRedGreen=false;
	public $RemoveLock=false;
    public $height=0;
	public $SQL_QUERY=null;

	public function __construct($params=array()){
		foreach ($params as $key=>$value){
			$this->$key=$value;
		}
		
	}
    public function ApexChart(){
        $tpl=new templates();
        $this->Title=replace_accents($this->Title);
        $this->Title=$tpl->javascript_parse_text($this->Title);
        $borderColor="1ab394";
        $series=array();
        $page=CurrentPageName();
        if(preg_match("#^miniadm#", $page)){
            $borderColor="3B5998";
            $this->AreaColor="#$borderColor";
        }
        if(count($this->xAxis)>0){
            foreach ($this->xAxis as $a=>$b){
                if(strpos(" $b", "\"")>0){continue;}
                $series[]="'$b'";

            }
            reset($this->xAxis);

        }
        $XasisTitle="";
        $XasisData=array();
        if(count($this->datas)>0){
            foreach($this->datas as $xName=>$xData){
                $XasisTitle=$tpl->javascript_parse_text($xName);
                foreach ($xData as $key=>$value){
                    if(!is_numeric($value)){$value="\"$b\"";}
                    $XasisData[]=$value;
                }
            }

        }
        if($this->ChartType=="areaspline"){
            $this->ChartType="area";
        }
        $this->LegendSuffix=$tpl->javascript_parse_text($this->LegendSuffix);
        $this->yAxisTtitle=$tpl->javascript_parse_text($this->yAxisTtitle);
        $this->Title=$tpl->javascript_parse_text($this->Title);
        $this->xAxisTtitle=$tpl->javascript_parse_text($this->xAxisTtitle);
        $ChartString="Chart".md5($this->container.rand(0, time()));
        //$f[]="var chart;";
        $f[]="// Chart type = $this->ChartType";
        $FunctioName="F".md5(time()+rand(0,time()));
        $f[]="function $FunctioName(){";
        $f[]="if(!document.getElementById('$this->container')){return;}";
        $f[]="if($ChartString){ $ChartString.destroy(); }";
        $f[]="const el = document.querySelector('#$this->container');";
        $f[]="const options = {";
        $charts[]="type: '$this->ChartType'";
        $charts[]="width: '100%'";
        if($this->height==0){$this->height=240;}
        $charts[]="height: $this->height";
        $charts[] = "stacked: false";
        $charts[] = "zoom: { type: 'x', enabled: true, autoScaleYaxis: true }";
        $charts[]="redrawOnParentResize: true";
        $charts[]="toolbar: { show: true }";
        $f[]="\tchart: { ".@implode(",\n\t",$charts)." },";
        $f[]="\tseries: [{ name: '$this->yAxisTtitle', data: [".@implode(",",$XasisData)."] }],";
        $f[] = "\tdataLabels: { enabled: false, },";
        $f[] = "\ttooltip: { shared: false },";
        $f[] = "\tstroke: { curve: 'smooth' },";
        $f[] = "title: { text: '$this->Title', align: 'left'},";
        $f[] = "fill: { type: 'gradient', gradient: { shadeIntensity: 1, inverseColors: false, opacityFrom: 0.5, opacityTo: 0, stops: [0, 98, 100], gradientToColors: ['$this->AreaColor'], },},";
        $f[] = "colors: ['$this->AreaColor' ],";
        $f[]="\txaxis: {";
        $f[]="\t\tcategories: [".@implode(",",$series)."],";
        $f[]="\t\ttitle: {";
        $f[]="\t\t\ttext: '$this->LegendSuffix',";
        $f[]="\t\t\tstyle: { fontSize: '$this->AxisFontsize', fontWeight: 600 },";
        $f[]="\t\t\toffsetX: 4,";
        $f[]="\t\t\toffsetY: 4";
        $f[]="\t\t}";
        $f[]="\t}";
        $f[]="};";
        $f[]="\tvar $ChartString = new ApexCharts(el, options);";
        $f[]="\t$ChartString.render();";
        $f[]="}";
        $f[]="$FunctioName();";

        header("content-type: application/x-javascript");
        return @implode("\n", $f);

    }

    public function ApexPie(){
        $tpl=new templates();
        $this->Title=replace_accents($this->Title);
        $this->Title=$tpl->javascript_parse_text($this->Title);
        $borderColor="1ab394";
        $series=array();
        $page=CurrentPageName();
        if(preg_match("#^miniadm#", $page)){
            $borderColor="3B5998";
            $this->AreaColor="#$borderColor";
        }
        $YasisData=array();
        $XasisData=array();

        foreach ($this->PieDatas as $Labels=>$Nums){
            $XasisData[]=$Nums;
            $YasisData[]="'$Labels'";
        }


        $this->LegendSuffix=$tpl->javascript_parse_text($this->LegendSuffix);
        $this->yAxisTtitle=$tpl->javascript_parse_text($this->yAxisTtitle);
        $this->Title=$tpl->javascript_parse_text($this->Title);
        $this->xAxisTtitle=$tpl->javascript_parse_text($this->xAxisTtitle);
        $ChartString="Chart".md5($this->container.rand(0, time()));
        //$f[]="var chart;";
        $f[]="// Chart type = $this->ChartType";
        $FunctioName="F".md5(time()+rand(0,time()));
        $f[]="function $FunctioName(){";
        $f[]="if(!document.getElementById('$this->container')){return;}";
        $f[]="if($ChartString){ $ChartString.destroy(); }";
        $f[]="const el = document.querySelector('#$this->container');";
        $f[]="const options = {";
        $charts[]="type: 'pie'";
        $charts[]="width: '100%'";
        if($this->height==0){$this->height=240;}
        $charts[]="height: $this->height";
        $charts[] = "stacked: false";
        $charts[] = "zoom: { type: 'x', enabled: true, autoScaleYaxis: true }";
        $charts[]="redrawOnParentResize: true";
        $charts[]="toolbar: { show: true }";
        $f[]="\tchart: { ".@implode(",\n\t",$charts)." },";
        $f[]="\tseries: [".@implode(",",$XasisData)."] ,";
        $f[]="\tlabels: [".@implode(",",$YasisData)."] ,";
        $f[] = "\tdataLabels: { enabled: false, },";
        $f[] = "\ttooltip: { shared: false },";
        $f[] = "\tstroke: { curve: 'smooth' },";
        $f[] = "title: { text: '$this->Title', align: 'left'},";
        $f[]="\txaxis: {";
        $f[]="\t\tcategories: [".@implode(",",$series)."],";
        $f[]="\t\ttitle: {";
        $f[]="\t\t\ttext: '$this->LegendSuffix',";
        $f[]="\t\t\tstyle: { fontSize: '$this->AxisFontsize', fontWeight: 600 },";
        $f[]="\t\t\toffsetX: 4,";
        $f[]="\t\t\toffsetY: 4";
        $f[]="\t\t}";
        $f[]="\t}";
        $f[]="};";
        $f[]="\tvar $ChartString = new ApexCharts(el, options);";
        $f[]="\t$ChartString.render();";
        $f[]="}";
        $f[]="$FunctioName();";

        header("content-type: application/x-javascript");
        return @implode("\n", $f);

    }
	public function BuildChart(){
		$tpl=new templates();
		$this->Title=replace_accents($this->Title);
		$this->Title=$tpl->javascript_parse_text($this->Title);
		$borderColor="1ab394";
		$page=CurrentPageName();
		if(preg_match("#^miniadm#", $page)){
			$borderColor="3B5998";
			$this->AreaColor="#$borderColor";
		}
		

		
		
		$this->yAxisTtitle=$tpl->javascript_parse_text($this->yAxisTtitle);
		$this->xAxisTtitle=$tpl->javascript_parse_text($this->xAxisTtitle);
		$ChartString="Chart".md5($this->container.rand(0, time()));
		//$f[]="var chart;";
		$f[]="// Chart type = $this->ChartType";
		
		//$f[]="\tvar \$container$t = $('#$this->container');";
		$FunctioName="F".md5(time()+rand(0,time()));
		$f[]="function $FunctioName(){";
		$f[]="if(!document.getElementById('$this->container')){return;}";
		$f[]="if($ChartString){ $ChartString.destroy(); }";
		if(!$this->RemoveLock){
			$f[]="LockPage();";
		}
		$f[]="\tvar $ChartString = new Highcharts.Chart({";
		$f[]="\t\tchart: {";
		$f[]="\t\tevents: {";
		$f[]="\t\t\tload: function(event) {";
		if(!$this->RemoveLock){
			$f[]="\t\t\t\tUnlockPage();";
		}
		$f[]="\t\t\t\tvar series=this.series[0];";
		if($this->SetRefreshCallBack<>null){
			$f[]="\t\t\t\t$this->SetRefreshCallBack(series);";
		}
		$f[]="\t\t\t}";
		$f[]="\t\t},";
		$f[]="\t\t\ttype: '$this->ChartType',";
		$f[]="\t\t\trenderTo: '$this->container',";
		if($this->ChartType=="areaspline"){	$f[]="\t\t\tstyle:{	color: '#$borderColor'}";}
		if($this->ChartType=="pie"){
			$f[]="\t\t\tplotBackgroundColor: null,";
			$f[]="\t\t\tplotBorderWidth: null,";
			$f[]="\t\t\tplotShadow: true,";
		}
		
		
		$f[]="\t\t},";
		
		$f[]="\t\t\tcredits: { enabled: false },";	
			
		$f[]="\t\t\ttitle: { ";
		$f[]="\t\t\t\ttext: '$this->Title' ,";
		$f[]="\t\t\t\tstyle:{	color: '#$borderColor', fontSize: '$this->TitleFontSize'}";
		$f[]="\t\t\t},";

        if(!is_null($this->subtitle)) {
            $this->subtitle = $tpl->_ENGINE_parse_body(str_replace("'", "\'", $this->subtitle));
        }
		
		if($this->subtitle<>null){
		$f[]="\t\t\tsubtitle: {";
        $f[]="\t\t\t\tuseHTML:true,";
        $f[]="\t\t\t\ttext: '$this->subtitle',";
        $f[]="\t\t\t\tstyle:{	color: '#$borderColor', fontSize: '".($this->TitleFontSize-2)."'}";
        $f[]="\t\t\t},";	
		
		}
		if($this->ChartType=="areaspline" || $this->ChartType=="line"){
			if(!is_array($this->xAxis)){$this->xAxis=array();}
			if(count($this->xAxis)==0){
				for($i=0;$i<100;$i++){$this->xAxis[]=0;}
			}
			if(count($this->xAxis)==1){
				$this->xAxis[]=0;
			}
				
			
			if(count($this->xAxis)>0){
			    foreach ($this->xAxis as $a=>$b){
					if(strpos(" $b", "\"")>0){continue;}
					if(!is_numeric($b)){$b="\"$b\"";
					$this->xAxis[$a]=$b;
					}
				}
				reset($this->xAxis);
				
			}
			
			
			$xlabels=true;
			if(!$this->xAxis_labels){$xlabels="false";}else{$xlabels="true";}
			
			$f[]="\t\t\txAxis: {";
			$f[]="\t\t\t\tcategories: [". @implode(",", $this->xAxis)."],";
			$f[]="\t\t\t\ttitle: {";
			$f[]="\t\t\t\t\ttext: '$this->xAxisTtitle',style:{	color: '#$borderColor', fontSize: '$this->AxisFontsize'}";
			$f[]="\t\t\t},";
			$f[]="\t\t\t\tlabels: {";
			$f[]="\t\t\t\t\tenabled: $xlabels";
			$f[]="\t\t\t\t}";
			$f[]="\t\t},";
		}

		if($this->ChartType=="areaspline" || $this->ChartType=="line"){
			$f[]="\t\t\tyAxis: {";
			$f[]="\t\t\t\ttitle: {";
			$f[]="\t\t\t\t\ttext: '$this->yAxisTtitle',style:{	color: '#$borderColor', fontSize: '$this->AxisFontsize'}";
			$f[]="\t\t\t}";
			$f[]="\t\t},";
		}
		
//		if($this->ChartType=="line"){
//			$f[]="plotLines: [{
//					value: 0,
//					width: 1,
//					color: '#808080'
//				}]
//			},";
//		}
		
		if($this->ChartType=="pie"){
			$Pie_dataLabels_enabled="true";
		
			if(!$this->PieDataLabels){$Pie_dataLabels_enabled="false";}
			
			$f[]="\t\t\tplotOptions: {";
			$f[]="\t\t\t\tpie: {";
			if($this->PieRedGreen){
				$f[]="\t\t\t\t\tcolors:['#46a346','#d32d2d'],";
				
			}
			$f[]="\t\t\t\t\tdataLabels: {";
			$f[]="\t\t\t\t\tenabled: $Pie_dataLabels_enabled";
			$f[]="\t\t\t\t}";
			$f[]="\t\t\t}";
			$f[]="\t},";
		}



        if($this->ChartType=="line") {
            $f[] = "plotOptions: {
                        series: {
                          label: {
                            connectorAllowed: false
                          },
                        }
                    },
                    responsive: {
                        rules: [{
                            condition: {
                                maxWidth: 500
                            },
                            chartOptions: {
                                legend: {
                                    layout: 'horizontal',
                                    align: 'center',
                                    verticalAlign: 'bottom'
                                }
                            }
                        }]
                    },
                  ";
        }
		
		if($this->ChartType=="areaspline"){
			$f[]="\t\t\tplotOptions: {";
			$f[]="\t\t\t\tcolor:'$this->AreaColor',";
	   	 	$f[]="\t\t\t\tareaspline: {";
	   	 	$f[]="\t\t\t\t\tcolor:'$this->AreaColor',";
	   	 	$f[]="\t\t\t\t\tfillOpacity: 0.5,";
	   	 	$f[]="\t\t\t\t},";
	   	 	$f[]="\t\t\t\t\tseries: {";
	   	 	$f[]="\t\t\t\t\t\tmarker: { enabled: false },";
	   	 	$f[]="\t\t\t\t}";	   	 	
	   	 	$f[]="\t\t},";
		}
		
	$f[]=" tooltip: {
					useHTML: true,
					headerFormat: '<center><strong style\"font-size:16px\">$this->LegendPrefix{point.key}</strong>&nbsp;',
	                pointFormat: '<strong style\"font-size:16px\">{series.name}</strong> <b>{point.y:&nbsp;.0f}$this->LegendSuffix</b><br/>'
	            },
	           ";	
	$f[]=" legend: {
	                itemStyle:{	color: '#$borderColor', fontSize: '12px'},
					shadow: true
	            },
	           ";	
	
	$f[]="series: [";
	
	if($this->ChartType=="areaspline" || $this->ChartType=="line"){
		if(count($this->datas)==0){
			for($i=0;$i<100;$i++){$this->datas[]=0;}
		}
        if(isset($this->datas[0])) {
            if (count($this->datas[0]) == 1) {
                $this->datas[] = 0;
            }
        }
			
		
		
		foreach ($this->datas as $key =>$series){
            if(!is_array($series)){$series=array();}
			$key=$tpl->javascript_parse_text($key);
			$f[]="{name: '$key',";
			$f[]="data: [" .@implode(",", $series)."]";
			$f[]="},";
			
		}
	}
	
	if($this->ChartType=="pie"){
		
		$this->PiePlotTitle=$tpl->javascript_parse_text($this->PiePlotTitle);
        if(!is_array($this->PieDatas)){
            $this->PieDatas=array();
        }
		if(count($this->PieDatas)==0){
			for($i=0;$i<10;$i++){$this->PieDatas[$i]=0;}
		}
        foreach ($this->PieDatas as $key =>$value){
			$key=$tpl->javascript_parse_text($key);
			$key=str_replace("'", "`", $key);
			$P[]="\n\t['$key',   $value]";
		}
		
		$f[]="{type: 'pie',";
		$f[]="name: '$this->PiePlotTitle',";
		$f[]="data: [";
		$f[]=@implode(",", $P);
		$f[]="]},";
	}	
	
	
	
	$f[]="]";
	$f[]="});";
	$f[]="}";
	$f[]="$FunctioName();";

	header("content-type: application/x-javascript");
	return @implode("\n", $f);
	
		
		
	}
	
	
	private function OnlyOneValue($array,$line=0){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					if($line>0){$trace[1]["line"]=$line;}
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		$tpl=new templates();
		$onlionetext=$tpl->javascript_parse_text("{only_one_value_cannot_generate_graph}");
		
		if(is_array($this->datas)){
            foreach ($this->datas  as $a=>$b){
				$bt=$b;
				$e=array();
				if(is_array($b)){
                    foreach ($b  as $d=>$e){
						if($d==0){$d=null;}
						$f[]="$d $e";}
					$bt=@implode(" ", $f);
				}
				if($a==0){$a=null;}
				$c[]="$a $bt";
					
			}
		}
		
		foreach ($array as $a=>$b){
				$bt=$b;
				$e=array();
				if(is_array($b)){
                    foreach ($b  as $d=>$e){
						if($d==0){$d=null;}
						$f[]="$d $e";}
					$bt=@implode(" ", $f);
				}
				if($a==0){$a=null;}
				$c[]="$a $bt";
			
		}
		$value=$tpl->javascript_parse_text(@implode(" ", $c));
		$onlionetext=str_replace("'", "`", $onlionetext);
		$value=str_replace("'", "`", $value);
		return "
		document.getElementById('$this->container').style.height='auto';
		document.getElementById('$this->container').style.width='auto';		
		document.getElementById('$this->container').innerHTML='<p class=text-warning><strong>$this->Title</strong><br>$onlionetext <strong style=font-size:14px>$value</strong><br>$called</p>';";
		
		
	}
	
	public function NoreturnedValue($array=array(),$line=0){
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				if($line>0){$trace[1]["line"]=$line;}
				$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
			}
		}		
		$tpl=new templates();
		$onlionetext=$tpl->javascript_parse_text("{no_value_cannot_generate_graph}<br>".$this->OnErrorEvent);
		$onlionetext=str_replace("'", "`", $onlionetext);
		$this->SQL_QUERY=str_replace("\n", " ", $this->SQL_QUERY);
		$this->SQL_QUERY=str_replace("'", "`", $this->SQL_QUERY);
		
		return "
		document.getElementById('$this->container').style.height='auto';
		document.getElementById('$this->container').style.width='auto';		
		document.getElementById('$this->container').innerHTML='<p class=text-warning><strong>$this->Title</strong><br>$onlionetext<br>$this->SQL_QUERY<br>$called</p>';
		
		";
				
		
	}
	
	
	
	
	
}