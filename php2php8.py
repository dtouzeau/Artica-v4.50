#!/usr/bin/env python
import sys,os,re
sys.path.append('/usr/share/artica-postfix/ressources')


def debian_version():
    srcfile="/etc/debian_version"
    with open(srcfile, "r") as f:
        for txt in f:
            sline = txt.strip()
            print(sline)
            matches = re.match('^([0-9]+)', sline)
            if matches: return int(matches.group(1))

    return 0
    pass


def php_version():
    tempfile="/tmp/phpver"
    os.system("/usr/bin/php -v >%s 2>&1" % tempfile)
    print("open %s" % tempfile)
    with open(tempfile,"r") as f:
        for txt in f :
            sline=txt.strip()
            print(sline)
            matches=re.match('^PHP\s+([0-9]+)\.',sline)
            if matches: return int(matches.group(1))

    return 0
    pass

def dumpsh(zarray):
    with open('/tmp/php2php8.sh', 'w') as f:
        for item in zarray:
            f.write("%s\n" % item)

    os.system("/bin/chmod 0755 /tmp/php2php8.sh")

def main(argv):
    if "--final" in argv:
        phpver      = php_version()
        ffile       = "/etc/artica-postfix/php7convertedTo8"
        if phpver == 8:
            system("/usr/bin/touch %s" % ffile)
            sys.exit(0)
        system("/bin/rm %s" % ffile)
        sys.exit(0)


    if "--build" in argv:
        buildsh()
        sys.exit(0)


def buildsh():
    echo        = "/bin/echo"
    tee         = "/usr/bin/tee"
    wget        = "/usr/bin/wget"
    php         = "/usr/bin/php"
    aptkey      = "/usr/bin/apt-key"
    aptget      = "DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get"
    artica_root = "/usr/share/artica-postfix"
    sh          = []
    phpver      = php_version()
    debver      = debian_version()
    lsb_release = "buster"
    sh.append("#!/bin/bash")
    opts        = '-o Dpkg::Options::="--force-confold" -o Dpkg::Options::="--force-confdef"'
    aptinst     = 'DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get %s -fuy install' % opts
    srclist     = "/etc/apt/sources.list.d/sury-php.list"
    suri        = "https://packages.sury.org/php/"
    logfile     = "/var/log/2php8.debug"
    tolog       = ">>%s 2>&1" % logfile
    subphp      = ["php8.0-cli","php8.0-common","php8.0-curl","php8.0-dba","php8.0-dev","php8.0-fpm","php8.0-gd","php8.0-imap","php8.0-json","php8.0-ldap","php8.0-mbstring","php8.0-mcrypt","php8.0-mysql","php8.0-opcache","php8.0-pgsql","php8.0-pspell","php8.0-readline","php8.0-snmp","php8.0-sqlite3","php8.0-xml","php8.0-xmlrpc"]
    print("Current PHP version is %s on %s " % (phpver,debver))

    if debver < 10:
        sh.append('/usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --msgbox "\Zb\Z1PDebian v%s is not supported, Only Debian 10"  0 0' % phpver)
        dumpsh(sh)
        sys.exit(0)

    if phpver > 7:
        sh.append('/usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --msgbox "\Zb\Z1PHP v%s is already installed"  0 0' % phpver)
        dumpsh(sh)
        sys.exit(0)

    sh.append('APTAUTH="/usr/lib/python3/dist-packages/softwareproperties/AptAuth.py"')
    sh.append("rm -f %s" % logfile)
    sh.append("touch %s" % logfile)
    sh.append('SRCLIST="%s"' % srclist)
    sh.append("%s %s/exec.apt-get.php --grubpc %s" % (php,artica_root,tolog))
    sh.append('if [ ! -f $APTAUTH ]; then')
    sh.append('     echo 2 | /usr/bin/dialog --gauge "Installing software-properties-common..." 10 70 0')
    sh.append("     %s software-properties-common %s" % (aptinst,tolog))
    sh.append('     if [ ! -f $APTAUTH ]; then')
    sh.append('             /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --msgbox "\Zb\Z1Installing software-properties-common failed"  0 0')
    sh.append('             exit 1')
    sh.append('     fi')
    sh.append('fi')
    sh.append('')
    sh.append('')
    sh.append('if [ ! -f $SRCLIST ]; then')
    sh.append('     echo 10 | /usr/bin/dialog --gauge "Adding repositories" 10 70 0')
    sh.append('     %s "deb %s %s main" | %s $SRCLIST' % (echo,suri,lsb_release,tee))
    sh.append('     if [ ! -f $SRCLIST ]; then')
    sh.append('             /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --msgbox "\Zb\Z1Adding repositories failed"  0 0')
    sh.append('             exit 1')
    sh.append('     fi')
    sh.append('fi')
    sh.append('')
    sh.append('')
    sh.append('echo 20 | /usr/bin/dialog --gauge "Adding repositories crypted keys" 10 70 0')
    sh.append('%s -qO - %sapt.gpg | %s add -' % (wget,suri,aptkey))
    sh.append('echo 20 | /usr/bin/dialog --gauge "Updating repositories" 10 70 0')
    sh.append('%s update %s' % (aptget,tolog))
    sh.append('echo 50 | /usr/bin/dialog --gauge "Installing PHP 8" 10 70 0')
    sh.append('%s php8.0 %s' % (aptinst,tolog))
    i=50
    for package in subphp:
        i=i+1
        sh.append('echo %s | /usr/bin/dialog --gauge "Installing %s" 10 70 0' % (i,package))
        sh.append('%s %s %s' % (aptinst,package,tolog))

    sh.append("/usr/bin/python %s/php2php8.py --final %s" % (artica_root,tolog))
    sh.append('if [ ! -f /etc/artica-postfix/php7convertedTo8 ]; then')
    sh.append('     /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --msgbox "\Zb\Z1Failed to upgrade to 8.x"  0 0')
    sh.append('     exit 1')
    sh.append('fi')



    dumpsh(sh)

if __name__ == "__main__":
   main(sys.argv[1:])
