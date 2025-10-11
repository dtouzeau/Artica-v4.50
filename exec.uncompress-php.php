<?php
// download all files from https://packages.sury.org/php/pool/main/p/php7.4/
// require libargon2-1 libidn2-0 libpcre2-8-0 libpcre3 libxml2 libzstd1 libsodium23

$dir=$argv[1];


uncompress_php($dir);

function uncompress_php($dir){

    $rm="/bin/rm";
    $tar="/bin/tar";
    $files=scandir($dir);
    @mkdir("$dir/old");
    @mkdir("$dir/system");
    echo "Scanning $dir\n";
    foreach ($files as $fname){
        if($fname=="."){continue;}
        if($fname==".."){continue;}
        if(!preg_match("#\.deb$#",$fname)){continue;}
        if(is_dir("$dir/$fname")){continue;}
        $srcfile="$dir/$fname";
        $output=str_replace(".deb","",$srcfile);
        if(is_dir($output)){
            shell_exec("$rm -rf $output");
        }
        @mkdir($output,0755,true);
        echo "extracting $fname\n";
        shell_exec("ar -x $srcfile --output $output");
        shell_exec("cp $srcfile $dir/old/");
        if(!is_file("$output/data.tar.xz")){
            echo "No such file $output/data.tar.xz\n";
            die();
        }
        if(is_file("$output/control.tar.xz")){
            @unlink("$output/control.tar.xz");
        }
        if(is_file("$output/debian-binary")){
            @unlink("$output/debian-binary");
        }
        echo "extracting $output/data.tar.xz\n";
        shell_exec("$tar -xf $output/data.tar.xz -C $output/");
        if(is_file("$output/data.tar.xz")){
            @unlink("$output/data.tar.xz");
        }

        shell_exec("cp -rfv $output/* $dir/system/");
        shell_exec("$rm -rf $output");
        @unlink($srcfile);

    }

    $dirs[]="usr/share/doc";
    $dirs[]="etc/init.d";
    $dirs[]="etc/apache2";
    $dirs[]="lib/systemd";
    $dirs[]="etc/logrotate.d";
    $dirs[]="usr/share/man";
    $dirs[]="usr/share/bug";

    foreach ($dirs as $badir){
        if(is_dir("$dir/system/$badir")){
            echo "Remove $dir/system/$badir\n";
            shell_exec("$rm -rf $dir/system/$badir");
        }
    }

}
