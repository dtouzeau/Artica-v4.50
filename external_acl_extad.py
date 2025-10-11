#!/usr/bin/env python
import sys
import os
import time
sys.path.append('/usr/share/artica-postfix/ressources')
import traceback as tb
import logging
from unix import *
from activedirectoryclass import *


def main(arg):
    pid = os.getpid()
    UfdbgClientDebug=GET_INFO_INT("UfdbgClientDebug")
    levelLOG=logging.INFO
    if UfdbgClientDebug==1: levelLOG=logging.DEBUG
    logging.basicConfig(format='%(asctime)s [%(process)d] [%(levelname)s] %(message)s',filename='/var/log/squid/external_acl_extad.debug',  filemode='a',level=levelLOG)
    logging.raiseExceptions = False
    logging.info("Starting new helper...")
    CountSleep=0
    HashMem={}
    while True:
        line = sys.stdin.readline().strip()
        logging.debug("Receive '"+line+"'")
        if len(line)<2:
            logging.debug("Sleeping 1s "+str(CountSleep)+"/5")
            time.sleep( 1 )
            CountSleep=CountSleep+1
            if CountSleep>5:
                logging.info("Die() maxcount >5 -> raise SystemExit(0)")
                raise SystemExit(0)
            continue
        
        MainArray=line.split(" ")
        userid=''
        clt_conn_tag=''
        try:
            CONCURRENCY=int(MainArray[0])
            LOGIN=MainArray[1]
            Groupid=int(MainArray[2])
            logging.debug("LOGIN: "+LOGIN+" Group ID:"+str(Groupid))
            if LOGIN=="-":
                logging.debug("LOGIN='"+LOGIN+"' RETURN: BH")
                LineToSend=str(CONCURRENCY)+" BH\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue
            
            filepath="/etc/squid3/acls/ExternalAdGroup.1"
            if not os.path.exists(filepath):
                logging.debug("File='"+filepath+"' no such file RETURN: ERR")
                LineToSend=str(CONCURRENCY)+" ERR\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue
            
            Conf=file_get_contents(filepath)
            array=Conf.split("|")
            activedirectory_addr=array[0]
            activedirectory_user=array[1]
            activedirectory_pass=array[2]
            activedirectory_grp=array[3]
            activedirectory_grp=activedirectory_grp.decode('utf-8').lower()
            ADClass=ActiveDirectory(logging)
            ADClass.username=activedirectory_user
            ADClass.password=activedirectory_pass  
            ADClass.ldap_server=activedirectory_addr
            Groups=ADClass.GetUserGroups(LOGIN)
            
            if Groups==None:
                logging.debug("AD='"+activedirectory_addr+"' Return NONE RETURN: ERR")
                LineToSend=str(CONCURRENCY)+" ERR\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue
            
            if activedirectory_grp in Groups:
                logging.debug("LOGIN='"+LOGIN+"'-> SUCCESS IN '"+activedirectory_grp+"'")
                LineToSend=str(CONCURRENCY)+" OK tag="+activedirectory_grp+"\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue
            
            logging.debug("LOGIN='"+LOGIN+"'-> FAILED IN '"+activedirectory_grp+"'")
            LineToSend=str(CONCURRENCY)+" ERR\n"
            sys.stdout.write(LineToSend)
            sys.stdout.flush()
            continue

            
        except:
            logging.info(tb.format_exc())
            logging.debug("RETURN: BH")
            sys.stdout.write(str(CONCURRENCY)+" BH\n")
            sys.stdout.flush()
            continue

if __name__ == '__main__':
    sys.exit(main(arg=sys.argv[1:]))

