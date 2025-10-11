<?php


$string=@file_get_contents("/home/dtouzeau/Bureau/css.css");
echo "\n";
echo base64_encode($string);
echo "\n----------------------------------------------------\n";
$string=@file_get_contents("/home/dtouzeau/Bureau/head.html");
echo base64_encode($string);
echo "\n------------------ERROR--BODY-------------------------------\n";
$string=@file_get_contents("/home/dtouzeau/Bureau/body.html");
echo base64_encode($string);
echo "\n----------------------------------------------------\n\n\n";

$string=@file_get_contents("/home/dtouzeau/Bureau/microsoft-css.css");
echo "\n------------------MICROSOFT--CSS-------------------------------\n";
echo base64_encode($string);
echo "\n----------------------------------------------------\n";
$string=@file_get_contents("/home/dtouzeau/Bureau/microsoft-head.html");
echo "\n------------------MICROSOFT--HEAD-------------------------------\n";
echo base64_encode($string);
echo "\n----------------------------------------------------\n";
$string=@file_get_contents("/home/dtouzeau/Bureau/microsoft-body.html");
echo "\n------------------MICROSOFT--BODY-------------------------------\n";
echo base64_encode($string);
echo "\n----------------------------------------------------\n";
$ftpcss=@file_get_contents("/home/dtouzeau/Bureau/ftp.css");
$ftpcss=base64_encode($ftpcss);
$headcss=@file_get_contents("/home/dtouzeau/Bureau/head-ftp.html");
$headcss=base64_encode($headcss);
$bodycss=@file_get_contents("/home/dtouzeau/Bureau/body-ftp.html");
$bodycss=base64_encode($bodycss);
echo "\n(2,'White error page','$ftpcss',\n'$headcss',\n'$bodycss')\n\n";


$ftpcss=@file_get_contents("/home/dtouzeau/Bureau/ftp.css");
$ftpcss=base64_encode($ftpcss);
$headcss=@file_get_contents("/home/dtouzeau/Bureau/head-ftp.html");
$headcss=base64_encode($headcss);
$bodycss=@file_get_contents("/home/dtouzeau/Bureau/body-ftp.html");
$bodycss=base64_encode($bodycss);




$head=base64_encode(@file_get_contents("/home/dtouzeau/Bureau/head-hotspot1.html"));
$css=base64_encode(@file_get_contents("/home/dtouzeau/Bureau/hotspot1.css"));
$body=base64_encode(@file_get_contents("/home/dtouzeau/Bureau/body-hotspot1.html"));
echo "\n(4,'HotSpot Artica default','$css',\n'$head',\n'$body')\n\n";