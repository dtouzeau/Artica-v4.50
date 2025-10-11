<?php
include_once(dirname(__FILE__).'/functions.sql.inc');
include_once(dirname(__FILE__).'/class.files.inc');




        $sql="SELECT SUM(rowsnum) as tsum FROM indexcats";
        $ligne=mysql_fetch_array(QUERY_SQL($sql,null,0,"articafr9"));
        $websites=$ligne["tsum"];
        $websites=numberFormat($websites,0,""," ");
        
        $sql="SELECT tablename,category,rowsnum FROM indexcats ORDER BY rowsnum DESC";
		$results=QUERY_SQL($sql,null,0,"articafr9");
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		if(trim($ligne["category"])==null){continue;}
		if($ligne["rowsnum"]<3){continue;}
		$c++;
		$ligne["rowsnum"]=numberFormat($ligne["rowsnum"],0,""," ");
		$table=$table."
		<tr>
			<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
			<td style='font-size:16px;font-weight:bold' width=99% nowrap>{$ligne["category"]}:</td>
			<td style='font-size:16px;font-weight:bold' width=1% nowrap>{$ligne["rowsnum"]} items</td>
		</tr>
		<tr><td colspan=3 style='border-top:1px solid #CCCCCC'>&nbsp;</td></tr>
		
		";
		
	}

	        
        



$html="
<table style='width:900px'>
<tr>

	<td valign='top' width=1%><center><img src='/img/squid-350.png' style='border:3px solid #CCCCCC'></center></td>
	<td valign='top'>
<H1>Artica For Proxy appliance, $c Available Categories: </H1>
". RoundedLightGrey("<p style='font-size:16px'>Currently <strong>$websites</strong> categorized websites</p>
<div style='text-align:right;margin-top:-10px'><a href=\"index.php?option=com_content&view=article&catid=null&id=449:proxy-appliances-features-list\" style='font-size:14px'>See all Artica For Proxy Appliance features</a></div>

")."
<center style='margin-top:10px;'><table style='width:25%;border:3px solid #CCCCCC;padding:3px'><tbody>$table</tbody></table></center>
</td>
</tr>
</table>";



echo $html;
	
	function numberFormat  ($number  , $decimals = 2 , $dec_point = '.' , $sep = ',', $group=3   ){
    $num = sprintf("%0.{$decimals}f",$number);   
    $num = explode('.',$num);
    while (strlen($num[0]) % $group) $num[0]= ' '.$num[0];
    $num[0] = str_split($num[0],$group);
    $num[0] = join($sep[0],$num[0]);
    $num[0] = trim($num[0]);
    $num = join($dec_point[0],$num);
   
    return $num;
}	

function RoundedLightGrey($text){
	
	$id=md5($text);
	
	
	
	
return "
<div $ls>
  <b class=\"RLightGrey\" id='$id'>
  <b class=\"RLightGrey1\" id ='$id" . "_1'><b></b></b>
  <b class=\"RLightGrey2\" id ='$id" . "_2'><b></b></b>
  <b class=\"RLightGrey3\" id ='$id" . "_3'></b>
  <b class=\"RLightGrey4\" id ='$id" . "_4'></b>
  <b class=\"RLightGrey5\" id ='$id" . "_5'></b></b>

  <div class=\"RLightGreyfg\" style='padding:8px;'  id ='$id" . "_11'>
   			$left$text
  </div>

  <b class=\"RLightGrey\" id ='$id" . "_0'>
  <b class=\"RLightGrey5\" id ='$id" . "_6'></b>
  <b class=\"RLightGrey4\" id ='$id" . "_7'></b>
  <b class=\"RLightGrey3\" id ='$id" . "_8'></b>
  <b class=\"RLightGrey2\" id ='$id" . "_9'><b></b></b>
  <b class=\"RLightGrey1\" id ='$id" . "_10'><b></b></b></b>
</div>
";	
	
}  