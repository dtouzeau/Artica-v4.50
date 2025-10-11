<?php

class rrdbuilder{
	var $error="";
	var $dbpath="";
	var $width=300;
	var $height=500;
	var $borderwith=0;
	var $timestart="-1h";
	var $graphTitle="graph";
	var $tabwidth=10;
	var $vertical_title=null;
	var $table_key="";
	var $watermark="";
	var $base=0;
	var $GPRINT="%3.2lf";
	var $line_title="";
	var $LineColor="#D90000";
	var $units_exponent=null;
	var $lower_limit=null;
	var $upper_limit=null;	
	var $EnableRRDGraphFunction=1;
	function __construct($dbpath=null,$tablekey=null){
		
		$this->dbpath=$dbpath;
		$this->table_key=$tablekey;
		$sock=new sockets();
		$this->EnableRRDGraphFunction=$sock->GET_INFO("EnableRRDGraphFunction");
		if(!is_numeric($this->EnableRRDGraphFunction)){$this->EnableRRDGraphFunction=1;}
		if($this->EnableRRDGraphFunction==1){
			if(!function_exists("rrd_graph")){$this->EnableRRDGraphFunction=0;}
		}
		
		
	}
	
	function buildgraph($imagepath,$tablekey=null){
		$guill="\"";
		if($this->EnableRRDGraphFunction==1){$guill=null;}
		if($tablekey==null){$tablekey=$this->table_key;}
		if(!is_numeric($this->borderwith)){$this->borderwith=0;}
	 	$key=time();
	 	  $options[]="--slope-mode";
		  $options[]="--width";
		  $options[]=$this->width;
		  $options[]="--height";
		  $options[]=$this->height;
		  $options[]="--full-size-mode";
		 // $options[]="--border";
		 // $options[]=$this->borderwith;
		  $options[]="--tabwidth";
		  $options[]=$this->tabwidth;
		  $options[]="--start";
		  $options[]=$this->timestart;
		  $options[]="--title=$guill$this->graphTitle$guill";
		  if($this->vertical_title<>null){
		     $options[]="--vertical-label=$this->vertical_title";
		   }
		   if($this->watermark<>null){
		   $options[]="--watermark";
		   $options[]="{$guill}$this->watermark{$guill}";
		   }
		    $options[]="--font";
		   $options[]="DEFAULT:8:/opt/artica/var/rrd/andalemono";
		   $options[]="--font";
		   $options[]="TITLE:11:FreeSans";
		   $options[]="--alt-autoscale-max";
		   $options[]="--lower-limit=0";
		   $options[]="--lower=0";
		   $options[]="--color";
		   $options[]="CANVAS#FBFBFB";
		   if($this->base>0){
		   	 $options[]="--base";
		   	 $options[]=$this->base;
		   }
		   
		   if(is_numeric($this->units_exponent)){
		   	   	$options[]="--units-exponent";
		   	 	$options[]=$this->units_exponent;
		   }
		   
		  if(is_numeric($this->upper_limit)){
		   		$options[]="--upper-limit";
		   	 	$options[]=$this->upper_limit;
		   }		   
		   
		  if(is_numeric($this->lower_limit)){
		   		$options[]="--lower-limit";
		   	 	$options[]=$this->lower_limit;
		   }		   


		   $options[]="--color";
		   $options[]="BACK#FBFBFB";
		   $options[]="DEF:$tablekey=$this->dbpath:$tablekey:AVERAGE";
		   $options[]="LINE2:$tablekey$this->LineColor:$guill$this->line_title$guill";
	  	   $options[]="GPRINT:$tablekey:AVERAGE:{$guill}Avg\: $this->GPRINT{$guill}";
	  	   $options[]="GPRINT:$tablekey:MAX:{$guill}Max\: $this->GPRINT{$guill}";
		   if($this->EnableRRDGraphFunction==0){
				$cmdline="/usr/bin/rrdtool graph $imagepath ".@implode(" ", $options)." 2>&1";
		   		writelogs($cmdline,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		   		exec($cmdline,$results);
		   		foreach ($results as $index=>$line){
		   			if($GLOBALS["VERBOSE"]){echo "$line<br>\n";}
		   			if(preg_match("#ERROR:\s+(.+)#", $line,$re)){
		   				$this->error=$this->error."$this->graphTitle:{$re[1]}";
		   				if($GLOBALS["VERBOSE"]){echo "!!! $line<br>\n";}
		   				return false;
		   			}
		   		}
		   		if($GLOBALS["VERBOSE"]){
			   		if(!is_file($imagepath)){echo "!!! $imagepath no such file....<br>\n";}
			   		
		   		}
		   		
		   		
		   }else{
		   		if($GLOBALS["VERBOSE"]){echo __LINE__." rrd_graph($imagepath)<br>\n";}
		   		try{
					$ret =@rrd_graph($imagepath, $options, count($options));
		  			if (! $ret) {
		  				if($GLOBALS["VERBOSE"]){echo __LINE__." $this->graphTitle, ".rrd_error()."<br>\n";}
		  				$this->error="$this->graphTitle, ".rrd_error();return false;}
		   		}catch (Exception $e) {
		   			if($GLOBALS["VERBOSE"]){echo __LINE__." ".$e->getMessage()."<br>\n";}
					$this->error=$e->getMessage();
					return false; 
				}
		   }
		   if($GLOBALS["VERBOSE"]){echo __LINE__." OK \"$imagepath\"<br>\n";}
		   if($GLOBALS["AS_ROOT"]){@chmod($imagepath, 0755);}
		   return true;
		
		}
		
function setTransparency($imagepath){
	$image_source = imagecreatefrompng($imagepath);
	imagetruecolortopalette($image_source, false, 256); // convert
	$white = imagecolorresolve($image_source, 255, 255, 255); // resolve given palette entry
	imagecolortransparent($image_source, $white);	
	
	$width = imagesx($image_source);
	$height = imagesy($image_source);
	$new_image = ImageCreateTrueColor($width,$height);
	$black = imagecolorallocate ($new_image, 0, 0, 0);
	imagecolortransparent($new_image, $black);
    imagecopyresampled($new_image, $image_source, 0, 0, 0, 0, $width, $height, $width, $height);
    imagepng($new_image,$imagepath,100);
	} 		
	
	
}