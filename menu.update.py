#!/usr/bin/env python
import sys,getopt,hashlib
import io
import ConfigParser
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *



def generate():
    if not os.path.exists("/etc/artica-postfix/NIGHTLY_PARAMS"): os.system("/usr/bin/php /usr/share/artica-postfix/exec.nightly.php --refresh")
    if not os.path.exists("/etc/artica-postfix/NIGHTLY_PARAMS"): sys.exit("/etc/artica-postfix/NIGHTLY_PARAMS no such file.")

    curl_interface=""
    NIGHTLY_PARAMS=file_get_contents("/etc/artica-postfix/NIGHTLY_PARAMS")
    WgetBindIpAddress=GET_INFO_STR("WgetBindIpAddress")

    if len(WgetBindIpAddress)>3: curl_interface="--interface "+WgetBindIpAddress+" "

    print "Use interface....:",WgetBindIpAddress
    URI="/usr/bin/curl "+curl_interface+"--connect-timeout 5 --silent -d 'datas="+NIGHTLY_PARAMS+"' 'http://articatech.net/artica.update4.php' >/etc/artica-postfix/NIGHTLY_CONTENT"
    print "URI..............:",URI
    os.system(URI)
    os.system("/usr/bin/php /usr/share/artica-postfix/menu.update.py.php")

def help():

    print "-g or --generate .........: Generate the bash menu."
    print "--update --url=URL md5=MD5: Download the specified file and test the MD5 after download"
    print "-U -u URL -m MD5..........: Download the specified file and test the MD5 after ndownload"


def download(url,zmd5):
    package=os.path.basename(url)
    cmdline='wget "'+url+'" -O /home/artica/'+package+' 2>&1|stdbuf -o0 awk \'/[.] +[0-9][0-9]?[0-9]?%/ { print substr($0,63,3) }\' | dialog --gauge "Downloading '+package+'" 10 100'


    os.system(cmdline)
    os.system('echo | dialog --gauge "'+package+' Checking MD5" 10 100')
    mdnew=md5sum('/home/artica/'+package)
    if mdnew!=zmd5:
        RemoveFile('/home/artica/'+package)
        os.system('dialog --title "failed" --msgbox "Corrupted package, md5 '+mdnew+' differ '+zmd5+'" 9 70')
        sys.exit(2)

    sys.exit(2)
    os.system("(pv -n /home/artica/"+package+" | /bin/tar xzf - -C /usr/share/ ) 2>&1 | dialog --title 'Extracting "+package+"' --gauge 'Extracting Artica Firmware...' 6 80")
    version=file_get_contents("/usr/share/artica-postfix/VERSION")
    os.system('dialog --title "Success" --msgbox "Updated to '+version+'" 9 70')
    RemoveFile('/home/artica/' + package)
    restart()


def restart():
    
    os.system("echo 10|dialog --gauge 'Restarting Artica' 10 100")
    os.system("/etc/init.d/artica-phpfpm restart >/dev/null 2>&1")
    os.system("echo 15|dialog --gauge 'Restarting Artica' 10 100")
    system("/usr/bin/php /usr/share/artica-postfix/exec.initslapd.php --force >/dev/null 2>&1")

    os.system("echo 20|dialog --gauge 'Restarting Artica Web console' 10 100")
    os.system("/etc/init.d/artica-webconsole start >/dev/null 2>&1")

    if is_file("/etc/init.d/ufdb"):
        os.system("echo 25|dialog --gauge 'Rebuilding Web filtering' 10 100")
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.ufdb.enable.php --ufdb >/dev/null 2>&1")
    

    if is_file("/etc/init.d/firehol"):
        os.system("echo 30|dialog --gauge 'Restarting firewall service' 10 100")
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.firehol.php >/dev/null 2>&1")
        os.system("/usr/bin/php /etc/init.d/firehol restart")
    

    if is_file("/etc/init.d/ssh"):
        os.system("echo 30|dialog --gauge 'Restarting SH service' 10 100")
        os.system("/usr/bin/php /etc/init.d/ssh restart")
    

    if is_file("/etc/init.d/ufdb-tail"):
        os.system("echo 30|dialog --gauge 'Restarting Webfiltering Tail logger' 10 100")
        os.system("/usr/bin/php /etc/init.d/ufdb-tail restart")
    
    if is_file("/etc/init.d/postfix-logger"):
        os.system("echo 35|dialog --gauge 'Restarting Postfix Tail logger' 10 100")
        os.system("/usr/bin/php /etc/init.d/postfix-logger restart")
    

    if is_file("/etc/init.d/slapd"):
        os.system("echo 35|dialog --gauge 'Restarting OpenDLAP service' 10 100")
        os.system("/etc/init.d/slapd start >/dev/null 2>&1")
    

    if is_file("/etc/init.d/artica-syslog"):
        os.system("echo 40|dialog --gauge 'Restarting Syslogger' 10 100")
        os.system("/etc/init.d/artica-syslog restart >/dev/null 2>&1")
    
    if is_file("/etc/init.d/nginx"):
        os.system("echo 45|dialog --gauge 'Restarting Web and reverse proxy service' 10 100")
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.nginx.enable.php --monit >/dev/null 2>&1")
    

    if is_file("/etc/init.d/squid-tail"):
        os.system("echo 50|dialog --gauge 'Restarting Proxy tail logger' 10 100")
        os.system("/etc/init.d/squid-tail restart >/dev/null 2>&1")
    

    if is_file("/etc/init.d/mimedefang"):
        os.system("echo 55|dialog --gauge 'Restarting MimeDefang' 10 100")
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.mimedefang.php --parse >/dev/null 2>&1")
    

    if is_file("/etc/init.d/c-icap-access"):
        os.system("echo 60|dialog --gauge 'Restarting ICAP tail logger' 10 100")
        os.system("/etc/init.d/c-icap-access restart >/dev/null 2>&1")

    os.system("echo 70|dialog --gauge 'Checking..' 10 100")
    os.system("/usr/bin/php /usr/share/artica-postfix/exec.ufdb.used.databases.php >/dev/null 2>&1")
    os.system("echo 71|dialog --gauge 'Checking..' 10 100")
    os.system("/usr/bin/php /usr/share/artica-postfix/exec.postfix.vacuum.php >/dev/null 2>&1")

    if is_file("/etc/init.d/artica-monitor"):
        os.system("echo 71|dialog --gauge 'Restarting artica monitor' 10 100")
        os.system("/etc/init.d/artica-monitor restart >/dev/null 2>&1")

    os.system("echo 72|dialog --gauge 'Checking..' 10 100")
    os.system("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 -perm >/dev/null 2>&1")
    os.system("echo 73|dialog --gauge 'Restarting watchdog' 10 100")
    os.system("/etc/init.d/monit restart >/dev/null 2>&1")
    os.system("echo 74|dialog --gauge 'Restarting artica status' 10 100")
    os.system("/etc/init.d/artica-status restart --force >/dev/null 2>&1")
    os.system("echo 75|dialog --gauge 'Checking..' 10 100")
    os.system("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 >/dev/null 2>&1")

    if is_file("/etc/init.d/squid"):
        os.system("echo 80|dialog --gauge 'Restarting Proxy scheduler' 10 100")
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1")

    os.system("echo 81|dialog --gauge 'Checking..' 10 100")
    os.system("/usr/bin/php /usr/share/artica-postfix/exec.schedules.php --defaults >/dev/null 2>&1")
    os.system("echo 82|dialog --gauge 'Checking..' 10 100")
    os.system("/usr/share/artica-postfix/bin/articarest -phpini -debug >/dev/null 2>&1")
    os.system("echo 83|dialog --gauge 'Checking..' 10 100")
    os.system("/usr/bin/php /usr/share/artica-postfix/exec.verif.packages.php >/dev/null 2>&1")
    os.system("echo 100|dialog --gauge 'Success' 10 100")


def md5sum(filename, blocksize=65536):
    hash = hashlib.md5()
    with open(filename, "rb") as f:
        for block in iter(lambda: f.read(blocksize), b""):
            hash.update(block)
    return hash.hexdigest()




def main(argv):
    UPDATE=False
    url=""
    zmd5=""
    try:
      opts, args = getopt.getopt(argv,"hgUum",["help","generate","update","url=","md5="])
    except getopt.GetoptError as err:
        print "Error in your command line"
        print(err)
        help()
        sys.exit(2)
    for opt, arg in opts:

        print opt

        if opt == '-h':
           print "Output the internal help..."
           help()
           sys.exit()
        elif opt in ("-g", "--generate"):
           generate()

        elif opt in ("-U", "--update"):
            UPDATE=True
        elif opt in ("-u", "--url"):
            url = arg
        elif opt in ("-m", "--md5"):
            zmd5 = arg

    if UPDATE:
        if len(zmd5) ==0:
            os.system('dialog --title "Failed" --msgbox "md5 value failed, ensure --md5=" 9 70')
            sys.exit()

        download(url,zmd5)
        sys.exit()

    sys.exit()
    help()


if __name__ == "__main__":
   main(sys.argv[1:])









