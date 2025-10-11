<?php
include_once(dirname(__FILE__).'/functions.sql.inc');
include_once(dirname(__FILE__).'/class.files.inc');






$html="
<H1>Artica Web Filter Toolbar for Firefox</H1>
<p style='font-size:16px'>Artica Web Filter Toolbar for Mozilla FireFox allows you to qualify a website including its Category,
	 the Risk of Malware and Phishing and the Risk with Children.</p>
<center style='margin-bottom:10px'><img src='http://proxy-appliance.org/files/5313/3590/9413/01-05-2012_23-54-50.png'></center>

<table style='width:100%'>
<tr>

	<td valign='top' width=1%><center><img src='/img/firefox-logo-128.png'></center></td>
	<td valign='top' style='font-size:14px'>
	 ".RoundedLightGrey("<div style='font-size:18px;font-weight:bold;'>This plugin is designed to display these details:</div>")."
<p>&nbsp;</p>
  <table>
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>The <strong>Category</strong> of the visited Website.</td>
  	</tr> 
  	<tr><td colspan=3>&nbsp;</td></tr> 
   	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>The <strong>Risk</strong> of the Website : % as Malware or Phishing...</td>
  	</tr> 
  	<tr><td colspan=3>&nbsp;</td></tr>  	
    	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'>The Risk with <strong>children</strong> : % of Child vs. Adult</td>
  	</tr> 
  	</table>  	
  	<p>&nbsp;</p>
  	 ".RoundedLightGrey("<div style='font-size:18px;font-weight:bold;'>Download the toolbar:</div>")."
  <table style='margin-top:15px'>
  	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><a href=\"http://sourceforge.net/projects/artica-squid/files/FireFox-plugin/articatoolbar.xpi/download\"><strong>Mirror 1: SourceForge.</strong></td>
  	</tr>  
  	<tr><td colspan=3>&nbsp;</td></tr>  	  	 	 
   	<tr>
  		<td>&nbsp;</td>
  		<td width=1% valign='middle'><img src='/img/arrow-right-16.png'></td>
  		<td style='font-size:16px'><a href=\"http://www.artica.fr/download/articatoolbar.xpi\"><strong>Mirror 2: Artica Web site.</strong></td>
  	</tr>    	    
   </table>
 </td>
</tr>
</table>
  "; 
    
echo $html;

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