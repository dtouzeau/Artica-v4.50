<?php
header("content-type: application/x-javascript");
$f[]="$.each( $(\"span[id^='explain-this-rule-']\"), function () {";
$f[]="\tvar id=\$(this).attr('id');";
$f[]="\tvar query=\$(this).attr('data');";
$f[]="\tLoadAjaxSilent(id,query);";
$f[]="});";

echo @implode("\n",$f);