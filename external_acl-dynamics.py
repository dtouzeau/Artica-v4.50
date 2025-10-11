#!/usr/bin/env python
import sys
import os
import time
sys.path.append('/usr/share/artica-postfix/ressources')
import traceback as tb
import logging
import socket
from netaddr import IPNetwork, IPAddress
import re
from unix import *
from squidvirtusersclass import *


    
    

        

def main(arg):
    pid = os.getpid()
    AclsDynamicDebug=GET_INFO_INT("AclsDynamicDebug") 
    levelLOG=logging.INFO
    if AclsDynamicDebug==1:levelLOG=logging.DEBUG
    logging.basicConfig(format='%(asctime)s [%(process)d] [%(levelname)s] %(message)s',filename='/var/log/squid/AclsDynamics.log',  filemode='a',level=levelLOG)
    logging.raiseExceptions = False
    VirtUsers=SQUIDVIRTUSERS(logging)
    logging.info("Starting new helper...")
    CountSleep=0
    HashMem={}
    acl_GroupTypeDynamic={}
    acl_GroupTypeDynamic[0]="mac";
    acl_GroupTypeDynamic[1]="ipaddr";
    acl_GroupTypeDynamic[3]="hostname";
    acl_GroupTypeDynamic[2]="member";
    acl_GroupTypeDynamic[4]="webserver";     
    
    while True:
        line = sys.stdin.readline().strip()
        logging.debug("Receive '"+line+"'")
        if len(line)<2:
            logging.debug("Sleeping 1s "+str(CountSleep)+"/2")
            time.sleep( 1 )
            CountSleep=CountSleep+1
            if CountSleep>2:
                logging.info("Die() maxcount >2 -> raise SystemExit(0)")
                raise SystemExit(0)
            continue
        
        MainArray=line.split(" ")
        userid=''
        clt_conn_tag=''
        try:
            OK_USER=""
            RULE_MATCHES=False
            CONCURRENCY=int(MainArray[0])
            userid=str(MainArray[1])
            IPADDR=MainArray[2]
            MAC=MainArray[3]
            IPADDR2=MainArray[4]
            DESTINATION=str(MainArray[5])
            GPID=str(MainArray[6])
            IPADDR=IPADDR.strip()
            IPADDR2=IPADDR2.strip()
            MAC=MAC.strip()
            userid=userid.strip()
            DESTINATION=DESTINATION.strip()
            
            matches=re.match("ID([0-9]+)",GPID)
            if not matches:
                logging.debug(""+GPID+" No match=<ID([0-9]+)> -> ERR")
                sys.stdout.write(str(CONCURRENCY)+" ERR\n")
                sys.stdout.flush()
                continue
            
            gpid=int(matches.group(1))
                
            
            CountSleep=0
            UserKey=""
        
            
            if IPADDR2=='-': IPADDR2=''
            if MAC=="-": MAC=''
            if MAC=="00:00:00:00:00:00": MAC=''
            if userid=='-':userid=''
            if len(IPADDR2)>5: IPADDR=IPADDR2
            
            if len(UserKey)==0: UserKey=userid
            if len(UserKey)==0: UserKey=MAC
            if len(UserKey)==0: UserKey=IPADDR
            if len(userid)==0:
                userid=VirtUsers.TryToFindAnUserID(UserKey)
                if len(userid)>1: OK_USER=" user="+str(userid)+" "
                
            logging.debug("GRPID["+str(gpid)+"]: "+IPADDR+" Key=["+UserKey+"] userid=["+userid+"] MAC=["+MAC+"]")
            filepath="/etc/squid3/acls/acls_dynamics/Group"+str(gpid)
            if not os.path.exists(filepath):
                logging.debug(""+filepath+" no such file or directory --> ERR ")
                sys.stdout.write(str(CONCURRENCY)+" ERR\n")
                sys.stdout.flush()
                continue
            
           
            
            
            with open(filepath,"r") as f:
                for txt in f :
                    txt=txt.rstrip('\n')
                    if len(txt)<5: next
                    STRTOF=""
                    REGEX=False
                    logging.debug(txt)
                    RULE=txt.split("</data>")
                    RULE_ID=int(RULE[0])
                    RULE_TYPE=int(RULE[1])
                    RULE_PATTERN=str(RULE[2])
                    RULE_MAX_TIME=int(RULE[3])
                    RULE_DURATION=int(RULE[4])
                    
                    if RULE_MAX_TIME>0:
                        now = datetime.now()
                        CurrentTime=int(now.strftime("%s"))
                        reste=RULE_MAX_TIME-CurrentTime
                        logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" Current Time "+str(CurrentTime)+" valid for "+str(RULE_MAX_TIME) +" "+str(reste)+"s TTL")
                        if reste<0:
                            logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" EXPIRED!")
                            RULE_MATCHES=True
                            sys.stdout.write(str(CONCURRENCY)+" ERR\n")
                            sys.stdout.flush()
                            break                 
                            
                            
                    
                    
                    logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" Type:"+acl_GroupTypeDynamic[RULE_TYPE]+" data=["+RULE_PATTERN+"] Time:"+str(RULE_MAX_TIME)+"/"+str(RULE_DURATION))
                    matches=re.match("^re:(.+)",RULE_PATTERN)
                    if matches:
                        RULE_PATTERN=matches.group(1)
                        REGEX=True
                        
                    if RULE_TYPE==0:
                        STRTOF=MAC.lower()
                        STRTOF=MAC.replace("-",":")
                        RULE_PATTERN=RULE_PATTERN.replace("-",":")
                        RULE_PATTERN=RULE_PATTERN.lower()
                        
                    if RULE_TYPE==1: STRTOF=IPADDR
                    
                    if RULE_TYPE==2:
                        STRTOF=userid.lower()
                        RULE_PATTERN=RULE_PATTERN.lower()
                    
                    if RULE_TYPE==4: STRTOF=DESTINATION
                    
                    if RULE_TYPE==3:
                        try:
                            STRTOF=socket.gethostbyaddr( IPADDR )[0]
                        except:
                            logging.info("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" Type:"+acl_GroupTypeDynamic[RULE_TYPE]+" data=["+IPADDR+"] gethostbyaddr failed")
                            continue
                        
                    if not REGEX:
                        if RULE_PATTERN.find("*")>0:
                            REGEX=True
                            RULE_PATTERN=StringToRegex(RULE_PATTERN)
                    
                        
                    if REGEX:
                        logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" use REGEX for pattern=["+RULE_PATTERN+"] data=["+STRTOF+"]")
                        matches=re.match(RULE_PATTERN,STRTOF) 
                        if matches:
                            logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" REGEX for pattern=["+RULE_PATTERN+"] data=["+STRTOF+"] MATCHES")
                            RULE_MATCHES=True
                            sys.stdout.write(str(CONCURRENCY)+" OK tag=DYNACL"+str(RULE_ID)+OK_USER+"\n")
                            sys.stdout.flush()
                            break
                        
                    if RULE_TYPE==0:
                        if STRTOF == RULE_PATTERN:
                            logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" for MAC=["+RULE_PATTERN+"] MAC=["+STRTOF+"] MATCHES")
                            RULE_MATCHES=True
                            sys.stdout.write(str(CONCURRENCY)+" OK tag=DYNACL"+str(RULE_ID)+OK_USER+"\n")
                            sys.stdout.flush()
                            break                        
                        
                    if RULE_TYPE==1:
                        if IPAddress(STRTOF) in IPNetwork(RULE_PATTERN):
                            logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" for IPNetwork=["+RULE_PATTERN+"] IPAddress=["+STRTOF+"] MATCHES")
                            RULE_MATCHES=True
                            sys.stdout.write(str(CONCURRENCY)+" OK tag=DYNACL"+str(RULE_ID)+OK_USER+"\n")
                            sys.stdout.flush()
                            break
                        
                    if RULE_TYPE==2:
                        if STRTOF == RULE_PATTERN:
                            logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" for member=["+RULE_PATTERN+"] member=["+STRTOF+"] MATCHES")
                            RULE_MATCHES=True
                            sys.stdout.write(str(CONCURRENCY)+" OK tag=DYNACL"+str(RULE_ID)+OK_USER+"\n")
                            sys.stdout.flush()
                            break
                            
                    if RULE_TYPE==3:
                        if STRTOF == RULE_PATTERN:
                            logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" for hostname=["+RULE_PATTERN+"] hostname=["+STRTOF+"] MATCHES")
                            RULE_MATCHES=True
                            sys.stdout.write(str(CONCURRENCY)+" OK tag=DYNACL"+str(RULE_ID)+OK_USER+"\n")
                            sys.stdout.flush()
                            break
                        
                    if RULE_TYPE==4:
                        RULE_PATTERN=StringToRegex(RULE_PATTERN)
                        matches=re.match(RULE_PATTERN,STRTOF) 
                        if matches:
                            logging.debug("GRPID["+str(gpid)+"]: Rule."+str(RULE_ID)+" for www=["+RULE_PATTERN+"] www=["+STRTOF+"] MATCHES")
                            RULE_MATCHES=True
                            sys.stdout.write(str(CONCURRENCY)+" OK tag=DYNACL"+str(RULE_ID)+OK_USER+"\n")
                            sys.stdout.flush()
                            break                         
                        
            if RULE_MATCHES: continue
            logging.debug("No rules matches")
            sys.stdout.write(str(CONCURRENCY)+" ERR\n")
            sys.stdout.flush()
            
        except:
            logging.info(tb.format_exc())
            sys.stdout.write(str(CONCURRENCY)+" BH\n" )
            sys.stdout.flush()
            continue

if __name__ == '__main__':
    sys.exit(main(arg=sys.argv[1:]))

