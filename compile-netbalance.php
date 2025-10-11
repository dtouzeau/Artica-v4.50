<?php


$f["/usr/local/share/perl/5.14.2/Net/Netmask.pm"]=true;
$f["/usr/local/share/perl/5.14.2/Net/Netmask.pod"]=true;
$f["/usr/local/share/perl/5.20.2/Net/Netmask.pod"]=true;
$f["/usr/local/share/perl/5.20.2/Net/Netmask.pm"]=true;
$f["/usr/local/bin/lsm"]=true;
$f["/usr/local/man/man1/load_balance.pl.1p"]=true;
$f["/etc/network/balance.conf"]=true;
$f["/etc/network/balance/lsm/balancer_event_script"]=true;
$f["/etc/network/balance/lsm/default_script"]=true;
$f["/etc/network/balance/pre-run/pre-run-script.pl"]=true;
$f["/etc/network/balance/post-run/post-run-script.pl"]=true;
$f["/etc/network/balance/routes/01.local_routes"]=true;
$f["/etc/network/balance/routes/02.local_routes.pl"]=true;
$f["/etc/network/balance/firewall/01.accept.pl"]=true;
$f["/etc/network/balance/firewall/01.accept"]=true;
$f["/etc/network/balance/firewall/02.forward.pl"]=true;
$f["/usr/local/share/perl/5.14.2/Net/ISP/Balance.pm"]=true;
$f["/usr/local/share/perl/5.14.2/Net/ISP/Balance/ConfigData.pm"]=true;
$f["/usr/local/share/perl/5.20.2/Net/ISP/Balance.pm"]=true;
$f["/usr/local/share/perl/5.20.2/Net/ISP/Balance/ConfigData.pm"]=true;
$f["/usr/local/man/man3/Net::ISP::Balance::ConfigData.3pm"]=true;
$f["/usr/local/man/man3/Net::ISP::Balance.3pm"]=true;
$f["/usr/local/bin/load_balance.pl"]=true;

if(!is_dir("/root/NetBalance-compile")){@mkdir("/root/NetBalance-compile",0755,true);}

while (list ($filepath, $line) = each ($f) ){
	$dirname=dirname($filepath);
	$TargetDir="/root/NetBalance-compile{$dirname}";
	$TargetFile=$TargetDir."/".basename($filepath);
	@mkdir($TargetDir,0755,true);
	echo "Copy $filepath -> $TargetFile\n";
	system("/bin/cp $filepath $TargetFile");
	
}
if(!is_file("/root/NetBalance.tar.gz")){@unlink("/root/NetBalance.tar.gz");}
system("cd /root/NetBalance-compile");
@chdir("/root/NetBalance-compile");
echo "/bin/tar -czf /root/NetBalance.tar.gz *\n";
system("/bin/tar -czf /root/NetBalance.tar.gz *");
system("cd /root/");
@chdir("/root/");