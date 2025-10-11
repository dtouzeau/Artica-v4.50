<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 


if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["save"])){save();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{weekranges}");
	$EncodedArray=urlencode($_GET["EncodedArray"]);
	$tpl->js_dialog9($title,"$page?popup=yes&EncodedArray=$EncodedArray&CallBack={$_GET["CallBack"]}",1370);


}


function save(){
	unset($_POST["save"]);
	foreach ($_POST as $index=>$datas){$MAIN[$datas]=true;}
	echo base64_encode(serialize($MAIN));
}

function PeriodToACL($szPeriod){
	
	

}


function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ayDays=array("{sunday}","{monday}","{tuesday}","{wednesday}","{thursday}","{friday}","{saturday}");
	$t=time();
	$Current=unserializeb64($_GET["EncodedArray"]);
	
echo "
<div style='margin-left:-18px;margin-top:20px;height:230px;width:1350px;overflow-x:auto;border-spacing: 1px;'>
<table style='width:100%'>
<tr>		
	<td>&nbsp;</td>";
$z=0;
for ($hour = 0; $hour < 24; $hour += 0.5) {
	$szEnd = date("H:i", ($hour+0.5)*3600);
	$z++;
	$js[]="function Switch$t$z(){";
	$js[]="\tvar attr;";
	$js[]="\tvar bggreen = \"#676a6c\";";
	$js[]="\tvar bgred = \"#18a689\";";
	
	for ($day = 0; $day < 7; $day++) {
		$szPeriod = $day."_".$hour;
		$szPeriod = str_replace(".", "-", $szPeriod);
		$js[]="\tattr=$('#$szPeriod').attr('active');";
		$js[]="\tif(attr==1){ 
			$('#$szPeriod').attr(\"active\", 0);	
			$('#$szPeriod').css({ \"background\": bggreen, \"border\": \"0px solid \" });	
		}else{
			$('#$szPeriod').attr(\"active\", 1);	
			$('#$szPeriod').css({ \"background\": bgred, \"border\": \"0px solid \" });
		}";
		
	}
	$js[]="}\n";
	
	
	$brt=explode(":",$szEnd);
	$jstime="<a href=\"javascript:blur();\" OnClick=\"Switch$t$z();\"  style='text-decoration:underline'>";
	$xtime="<span style='font-size:10px'>$jstime{$brt[0]}</a></span>
	<div style='font-size:9px;float:right;margin-left:-6px;margin-top:-4px;font-size:10px'>{$brt[1]}</div>";
	echo "\t<td style='font-size:10px' align='center' valign='middle'>$xtime</td>";
	
}

echo "</tr>";


$js[]="function SwitchUnique$t(id){";
$js[]="\tvar attr;";
$js[]="\tvar bggreen = \"#676a6c\";";
$js[]="\tvar bgred = \"#18a689\";";
$js[]="\tattr=$('#'+id).attr('active');";
$js[]="\tif(attr==1){
	$('#'+id).attr(\"active\", 0);
	$('#'+id).css({ \"background\": bggreen, \"border\": \"0px solid \" });
	}else{
	$('#'+id).attr(\"active\", 1);
	$('#'+id).css({ \"background\": bgred, \"border\": \"0px solid \" });
	}";
$js[]="}";

$c=0;
for ($day = 0; $day < 7; $day++) {
	
	$Dayjs="SwitchDay".md5(time()+$day);
	$js[]="function $Dayjs(){";
	
	echo "<tr><td align='right' style='font-size:10px'><a href=\"javascript:blur();\"
	OnClick=\"$Dayjs();\" 
	style='text-decoration:underline'>".$tpl->_ENGINE_parse_body($ayDays[$day])."</a>&nbsp;</td>";
	for ($hour = 0; $hour < 24; $hour += 0.5) {
		$szPeriod = $day."_".$hour;
		$szPeriod = str_replace(".", "-", $szPeriod);

		$szStart = date("G:i", $hour*3600);
		$szEnd = date("G:i", ($hour+0.5)*3600);
		$szTitlePeriod = $tpl->javascript_parse_text($ayDays[$day]).": ".$szStart." "." ".$szEnd;
		$bActive=0;
		if(isset($Current[$szPeriod])){
			$tt[]=PeriodToACL($szPeriod);
			$bActive=1;}
		
		$szClass = ($bActive == 0 ? "greencell" : "redcell");
		$c++;
		
		$js[]="\tSwitchUnique$t('$szPeriod');";
		echo "
		<td style='cursor:pointer;padding:1px' align='center' valign='middle'>
		       <div id='".$szPeriod."'
		        class='".$szClass." myover'
		        period='".$szPeriod."'
		        title='".$szTitlePeriod."'
		        Key='$c'
		        active=".$bActive.">
		       </div>
       </td>";
	}
	$js[]="}\n";
	echo "</tr>";
}


echo "</table>

		
</div><div style='text-align:right;padding-right:10px;margin-top:20px'>".

    $tpl->button_autnonome("{save}","Save$t()",null)."</div>	
	<script type=\"text/javascript\">
	
var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){
		{$_GET["CallBack"]}(res);

	}	
	dialogInstance9.close();	
}	
		
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('save','yes');
	$('.myover').each(function(i){
	    attr=$(this).attr( 'period' );
	    active=$(this).attr( 'active' );
	    key=$(this).attr( 'Key' );
	    if(active==1){
			
			XHR.appendData(key,attr);
		}
	});
	XHR.sendAndLoad('$page', 'POST',xSave$t);  		
		
}
		
		
 $(function(){
  var bggreen = \"#676a6c\";
  var bgred = \"#18a689\";
  var testing = 0;
  var selected = 1;

  function rgbConvert(str)
  {
   str = str.replace(/rgb\(|\)/g, \"\").split(\",\");
   str[0] = parseInt(str[0], 10).toString(16).toLowerCase();
   str[1] = parseInt(str[1], 10).toString(16).toLowerCase();
   str[2] = parseInt(str[2], 10).toString(16).toLowerCase();
   str[0] = (str[0].length == 1) ? '0' + str[0] : str[0];
   str[1] = (str[1].length == 1) ? '0' + str[1] : str[1];
   str[2] = (str[2].length == 1) ? '0' + str[2] : str[2];
   result = '#' + str.join(\"\");
   return result.toUpperCase();
  }

  $(\".myover\").hover(
   function(event) {
    if (testing == 1) {
     var color = (selected == 1 ? bgred : bggreen);
     $(this).css({ \"background\": color, \"border\": \"0px solid \" + color });

     var active = $(this).attr(\"active\");
     var nactive = (active == 0 ? 1 : 0);
     $(this).attr(\"active\", selected);
    }   }
  );

  $( \".myover\" ).click(function(){
   var active = $(this).attr(\"active\");
   var nactive = (active == 1 ? 0 : 1);
   var nbgcolor = (active == 1 ? bggreen : bgred);

   $(this).attr(\"active\", nactive);
   $(this).css({ \"background\": nbgcolor, \"border\": \"0px solid \" + nbgcolor });
  });

  $(\".myover\").mousedown(function() {
   var bgcolor = $(this).css(\"background-color\");
   bgcolor = rgbConvert(bgcolor);
   testing = 1;
   selected = (bgcolor == bggreen ? 1 : 0);
  });

  $(\".myover\").mouseup(function() {
   var bgcolor = $(this).css(\"background-color\");
   bgcolor = rgbConvert(bgcolor);
   testing = 0;
   selected = (bgcolor == bggreen ? 1 : 0);
  });
 });
 
".@implode("\n", $js)."
</script>";
}
?>