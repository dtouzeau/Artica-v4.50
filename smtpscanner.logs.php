<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/kav4mailservers.inc');


if(isset($_GET["logs"])){echo Logs();exit;}

$html="
<p>{text_logs}</p>
<div class=logs id='log_area'></div>
<script>Load_m_log();StartTimer_logs();</script>
";



$tpl=new templates('{title_logs}',$html);
echo $tpl->web_page;




function Logs(){
	include_once(dirname(__FILE__)."/ressources/class.status.logs.inc");
	$tpl=new templates();
	$sock=new sockets();
	$datas=$sock->getfile("smtpscanner.log");
	$datas=htmlentities($datas);
	$tbl=explode("\n",$datas);
	$tbl=array_reverse ($tbl, TRUE);
	echo "<table>";
	$class="rowA";
	foreach ($tbl as $num=>$val){
		if($class=="rowA"){$class="rowB";}else{$class="rowA";}
		

		
		if(trim($val)<>null){
		echo "<tr class=$class>
		<td width=1%><img src='" . statusLogs($val) . "'></td>
		<td width=99%' style='font-size:10px;'>$val</td>
		</tr>
		";
		}
		
	}
	
echo "</table>";
}



?>