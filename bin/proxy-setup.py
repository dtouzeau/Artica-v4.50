#!/usr/bin/env python
import sys
import syslog
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
from phpserialize import serialize, unserialize
import ConfigParser


def build_menu():
    f=[]
    f.append("#!/bin/bash")
    f.append("INPUT=/tmp/menu.proxy.sh.$$")
    f.append("OUTPUT=/tmp/output.proxy.sh.$$")
    f.append("trap \"rm -f $OUTPUT; rm -f $INPUT; exit\" SIGHUP SIGINT SIGTERM")
    f.append("DIALOG=${DIALOG=dialog}")

    ProxySettings=proxy_settings()
    if ProxySettings["ArticaProxyServerEnabled"] == 0: current="(Currently set to No)"
    if ProxySettings["ArticaProxyServerEnabled"] == 1: current = "(Currently set to Yes)"

    f.append("$DIALOG --title \"Proxy setup\" --yesno \"Did you want to modify Proxy settings to access to Internet?\\nPress 'Yes' to continue, or 'No' to cancel\" 0 0")
    f.append("case $? in")
    f.append("  1)")
    f.append("  exit")
    f.append("esac")
    f.append("")
    f.append("$DIALOG --title \"Use an HTTP Proxy\" --yesno \"Did Artica should use a proxy to access to Internet?\\n\\nPress 'Yes' to continue setup the proxy, or 'No' to use direct method\\n%s\" 0 0" % current)
    f.append("case $? in")
    f.append("  1)")
    f.append("  python /usr/share/artica-postfix/bin/proxy-setup.py --disable-proxy")
    f.append("  clear")
    f.append("  /tmp/proxy-setup.box.sh")
    f.append("  exit")
    f.append("esac")
    f.append(input_dialog("Enter the Proxy address",
                          "Enter the IP address or the hostname of your proxy server",
                          ProxySettings["ArticaProxyServerName"] ,"/etc/artica-postfix/ArticaProxyServerName"))


    f.append(input_dialog("Enter the Proxy listen port",
                          "Enter the listen port of your proxy server",
                          ProxySettings["ArticaProxyServerPort"] ,"/etc/artica-postfix/ArticaProxyServerPort"))

    f.append("$DIALOG --title \"Authentication\" --yesno \"Did your proxy using a basic authentication ?\\n\\nPress 'Yes' to continue setup the credentials, or 'No' to not use authentication\\n\" 0 0")
    f.append("case $? in")
    f.append("  1)")
    f.append("      echo \"\">/etc/artica-postfix/ArticaProxyServerUsername")
    f.append("      echo \"\">/etc/artica-postfix/ArticaProxyServerUserPassword")
    f.append("      python /usr/share/artica-postfix/bin/proxy-setup.py --setup-proxy")
    f.append("      clear")
    f.append("      /tmp/proxy-setup.box.sh")
    f.append("      exit")
    f.append("esac")

    f.append(input_dialog("Enter the Username",
                          "Enter the username used to authenticate",
                          ProxySettings["ArticaProxyServerUsername"] ,"/etc/artica-postfix/ArticaProxyServerUsername"))

    f.append(input_password("Enter the Password",
                          "Enter the Password used to authenticate",
                          ProxySettings["ArticaProxyServerUserPassword"], "/etc/artica-postfix/ArticaProxyServerUserPassword"))

    f.append("python /usr/share/artica-postfix/bin/proxy-setup.py --setup-proxy")
    f.append("clear")
    f.append("/tmp/proxy-setup.box.sh")
    f.append("exit\n")

    content="\n".join(f)
    file_put_contents("/tmp/proxy-setup.sh", content)
    os.chmod("/tmp/proxy-setup.sh", 0o755)

#f.append("$DIALOG --clear --title \"Enter the Proxy \" --inputbox \"Enter your IP address for the DNS number 1.\\nExample: 1.1.1.1\" 10 68 {$DNS[0]} 2> /etc/artica-postfix/WIZARDMASK_DNS1";
#$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
#$f[]="\t\trm -f /etc/artica-postfix/WIZARDMASK_DNS1";
#$f[]="\t\treturn";
#$f[]="\tfi";

def input_dialog(title,text,default,out):
    f=[]
    f.append("$DIALOG --clear --title \"")
    f.append(title)
    f.append("\" --inputbox \"")
    f.append(text)
    f.append("\\n\" 10 68 %s 2> %s" % (default,out))

    suffix="\nif [ $? = 1 -o $? = 255 ]; then\n\texit\nfi";
    s=[]
    s.append("")
    s.append("".join(f))
    s.append(suffix)
    return "\n".join(s)

def input_password(title,text,default,out):
    f=[]
    f.append("$DIALOG --clear --title \"")
    f.append(title)
    f.append("\" --insecure --passwordbox \"")
    f.append(text)
    f.append("\\n\" 10 68 %s 2> \"%s\"" % (default,out))
    suffix = "\nif [ $? = 1 -o $? = 255 ]; then\n\texit\nfi";
    s = []
    s.append("")
    s.append("".join(f))
    s.append(suffix)
    return "\n".join(s)



def save_proxy_settings(ProxySettings):
    f=[]
    f.append("[PROXY]")
    f.append("ArticaProxyServerEnabled = %s" % ProxySettings["ArticaProxyServerEnabled"])
    f.append("ArticaProxyServerName = %s" % ProxySettings["ArticaProxyServerName"])
    f.append("ArticaProxyServerPort = %s" % ProxySettings["ArticaProxyServerPort"])
    f.append("ArticaProxyServerUsername = %s" % ProxySettings["ArticaProxyServerUsername"])
    f.append("ArticaProxyServerUserPassword = %s" % ProxySettings["ArticaProxyServerUserPassword"])
    f.append("NoCheckSquid = %s" % ProxySettings["NoCheckSquid"])
    f.append("WgetBindIpAddress = %s" % ProxySettings["WgetBindIpAddress"])
    f.append("CurlUserAgent = %s" % ProxySettings["CurlUserAgent"])
    content = "\n".join(f)
    SET_INFO("ArticaProxySettings",content)

def proxy_settings():
    ArticaProxySettings = GET_INFO_STR("ArticaProxySettings")
    ProxySettings = {}
    tbl = ArticaProxySettings.split('\n')
    for line in tbl:
        if line.find('=') > 0:
            sx = line.split('=')
            key = sx[0].strip()
            value = sx[1].strip()
            ProxySettings[key] = value

    if not "ArticaProxyServerEnabled" in ProxySettings: ProxySettings["ArticaProxyServerEnabled"] = 0
    if not "ArticaProxyServerName" in ProxySettings: ProxySettings["ArticaProxyServerName"] = ""
    if not "ArticaProxyServerPort" in ProxySettings: ProxySettings["ArticaProxyServerPort"] = 3128
    if not "ArticaProxyServerUsername" in ProxySettings: ProxySettings["ArticaProxyServerUsername"] = ""
    if not "ArticaProxyServerUserPassword" in ProxySettings: ProxySettings["ArticaProxyServerUserPassword"] = ""
    if not "NoCheckSquid" in ProxySettings: ProxySettings["NoCheckSquid"] = 0
    if not "WgetBindIpAddress" in ProxySettings: ProxySettings["WgetBindIpAddress"] = ""
    if not "CurlUserAgent" in ProxySettings: ProxySettings["CurlUserAgent"] = "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0"
    try:
        ArticaProxyServerEnabled=int(ProxySettings["ArticaProxyServerEnabled"])
    except:
        ArticaProxyServerEnabled=0

    try:
        ArticaProxyServerPort = int(ProxySettings["ArticaProxyServerPort"])
    except:
        ArticaProxyServerPort = 3128

    try:
        NoCheckSquid = int(ProxySettings["NoCheckSquid"])
    except:
        NoCheckSquid = 0

    ProxySettings["ArticaProxyServerEnabled"]=ArticaProxyServerEnabled
    ProxySettings["ArticaProxyServerPort"] = ArticaProxyServerPort
    ProxySettings["NoCheckSquid"] = NoCheckSquid
    return ProxySettings

def setup_proxy():
    ProxySettings = proxy_settings()
    ProxySettings["ArticaProxyServerEnabled"] = 1
    ProxySettings["NoCheckSquid"] = 1
    ProxySettings["ArticaProxyServerName"]=file_get_contents("/etc/artica-postfix/ArticaProxyServerName")
    ProxySettings["ArticaProxyServerPort"] = file_get_contents("/etc/artica-postfix/ArticaProxyServerPort")
    ProxySettings["ArticaProxyServerUsername"] = file_get_contents("/etc/artica-postfix/ArticaProxyServerUsername")
    ProxySettings["ArticaProxyServerUserPassword"] = file_get_contents("/etc/artica-postfix/ArticaProxyServerUserPassword")
    save_proxy_settings(ProxySettings)
    f=[]
    if len(ProxySettings["ArticaProxyServerUsername"])>2:
        f.append(ProxySettings["ArticaProxyServerUsername"])
        f.append(" @ ")
    f.append(ProxySettings["ArticaProxyServerName"])
    f.append(":")
    f.append(ProxySettings["ArticaProxyServerPort"])
    text="".join(f)
    set_msg_box("Success using proxy %s" % text)

def set_msg_box(msg):
    f=[]
    f.append("#!/bin/bash")
    f.append("DIALOG=${DIALOG=dialog}")
    f.append("$DIALOG --title \"HTTP Proxy setup\" --msgbox \"%s\" 9 70" % msg)
    content="\n".join(f)
    file_put_contents("/tmp/proxy-setup.box.sh", content)
    os.chmod("/tmp/proxy-setup.box.sh", 0o755)

def main(argv):
    cmdline=argv[0]

    if cmdline=="--build":
        build_menu()
        sys.exit()
    if cmdline=="--disable-proxy":
        ProxySettings = proxy_settings()
        ProxySettings["ArticaProxyServerEnabled"]=0
        ProxySettings["NoCheckSquid"]=1
        save_proxy_settings(ProxySettings)
        set_msg_box("Success disable using proxy")
        sys.exit()
    if cmdline == "--setup-proxy":
        setup_proxy()
        sys.exit()

if __name__ == "__main__":
   main(sys.argv[1:])




