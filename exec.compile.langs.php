#!/usr/bin/php
<?php
$langues["fr"]=true;
$langues["it"]=true;
$langues["de"]=true;
$langues["po"]=true;
$langues["es"]=true;
$langues["br"]=true;
$langues["pol"]=true;

foreach ($langues as $lang=>$none){

echo "Compile $lang\n";
system("wget http://www.artica.fr/export.lang.php?lang=$lang -O /dev/null");

}
foreach ($langues as $lang=>$none){
echo "Downloading $lang\n";
system("wget http://www.artica.fr/languages/download/$lang/$lang.tar -O /tmp/$lang.tar");
echo "Installing $lang\n";
if(!is_dir("/usr/share/artica-postfix/ressources/language/$lang")){
mkdir("/usr/share/artica-postfix/ressources/language/$lang",0755,true);}
system("tar -xf /tmp/$lang.tar -C /usr/share/artica-postfix/ressources/language/$lang/");
@unlink("/tmp/$lang.tar");
}

system('php /usr/share/artica-postfix/compile-lang.php');