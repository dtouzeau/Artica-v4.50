#!/usr/bin/env python
import sys
import syslog
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *
from phpserialize import serialize, unserialize
from downloadclass import *


def build_menu():


    f=[]
    f.append("#!/bin/bash")
    f.append("INPUT=/tmp/menu.proxy.sh.$$")
    f.append("OUTPUT=/tmp/output.proxy.sh.$$")
    f.append("trap \"rm -f $OUTPUT; rm -f $INPUT; exit\" SIGHUP SIGINT SIGTERM")
    f.append("DIALOG=${DIALOG=dialog}")

    curl = ccurl()
    if not curl.DownloadFile('http://articatech.net/download/UPatchs/indexes.txt',"/tmp/indexes.txt"):
        f.append(msgb_box_error("HTTP Error code %s (%s)" % (curl.RESPONSE_CODE,curl.error)))
        content = "\n".join(f)
        file_put_contents("/tmp/menu.update.sh", content)
        os.chmod("/tmp/menu.update.sh", 0o755)
        return False

    version = file_get_contents("/usr/share/artica-postfix/VERSION")
    data=file_get_contents("/tmp/indexes.txt")
    array=unserialize(data)

    smenu=[]
    sfunctions=[]
    menuitem=[]
    smenu.append("/usr/bin/dialog --clear  --nocancel --backtitle \"Artica Update instable Service Pack\" --title \"[ U P D A T E -  M E N U ]\"")
    smenu.append("--menu \"You can use the UP/DOWN arrow keys")
    smenu.append("Choose the TASK\" 20 100 10")

    if version in array:
        zSP=array[version]
        for SP in zSP:
            smenu.append("%s \"Service Pack %s\"" % (SP,SP))
            menuitem.append("%s) SP%s;;" % (SP,SP))
            sfunctions.append("function SP%s(){" % SP)
            sfunctions.append("$DIALOG --title \"Service Pack Instable %s\" --yesno \"A Service Pack is not fully tested, Continue ?\n\n\\n\\nPress 'Yes' to update to SP%s, or 'No' to exit\\n\" 0 0" % (SP,SP))
            sfunctions.append("case $? in")
            sfunctions.append("  1)")
            sfunctions.append("  return 0")
            sfunctions.append("esac")
            sfunctions.append("/usr/bin/python /usr/share/artica-postfix/bin/menu-instable.py --SP %s" % SP)
            sfunctions.append("}")

    smenu.append("Quit \"Return to main menu\" 2>\"${INPUT}\"")
    f.append("\n".join(sfunctions))
    f.append("while true")
    f.append("  do")
    f.append(" ".join(smenu))

    menuitem.append("Quit) break;;")
    f.append("menuitem=$(<\"${INPUT}\")")
    f.append("case $menuitem in")
    f.append("\n".join(menuitem))
    f.append("esac")
    f.append("done")

    content="\n".join(f)
    file_put_contents("/tmp/menu.update.sh", content)
    os.chmod("/tmp/menu.update.sh", 0o755)



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









def set_msg_box(msg):
    f=[]
    f.append("#!/bin/bash")
    f.append("DIALOG=${DIALOG=dialog}")
    f.append("$DIALOG --title \"HTTP Proxy setup\" --msgbox \"%s\" 9 70" % msg)
    content="\n".join(f)
    file_put_contents("/tmp/proxy-setup.box.sh", content)
    os.chmod("/tmp/proxy-setup.box.sh", 0o755)

def msgb_box_error(msg):
    return "/usr/bin/dialog --title \"\Zb\Z1ERROR! ERROR!\" --colors --msgbox \"\Zb\Z1Fatal Error\n%s\"  0 0" % msg

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




