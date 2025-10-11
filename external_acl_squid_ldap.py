#!/usr/bin/env python
# -*- coding: iso-8859-1 -*-
import sys
import os
import time
import io
sys.path.append('/usr/share/artica-postfix/ressources')
import traceback as tb
import logging
from unix import *
from activedirectoryclass import *

def read_file(self,filename):
    if not os.path.exists(filename): return ""
    with io.open(filename, "r", encoding="ISO-8859-1") as my_file: return my_file.read()
    pass

def AccountDecode(value):
    if value.find("%")==-1: return value
    value=value.replace("%C3%C2§","ç",value)
    value=value.replace("%5C","\\",value)
    value=value.replace("%20"," ",value)
    value=value.replace("%0A","\n",value)
    value=value.replace("%C2£","£",value)
    value=value.replace("%C2§","§",value)
    value=value.replace("%C3§","ç",value)
    value=value.replace("%E2%82%AC","€",value)
    value=value.replace("%C3%89","É",value)
    value=value.replace("%C3%A9","é",value)
    value=value.replace("%C3%A0","à",value)
    value=value.replace("%C3%AA","ê",value)
    value=value.replace("%C3%B9","ù",value)
    value=value.replace("%C3%A8","è",value)
    value=value.replace("%C3%A2","â",value)
    value=value.replace("%C3%B4","ô",value)
    value=value.replace("%C3%AE","î",value)
    value=value.replace("%E9","é",value)
    value=value.replace("%E0","à",value)
    value=value.replace("%F9","ù",value)
    value=value.replace("%20"," ",value)
    value=value.replace("%E8","è",value)
    value=value.replace("%E7","ç",value)
    value=value.replace("%26","&",value)
    value=value.replace("%FC","ü",value)
    value=value.replace("%2F","/",value)
    value=value.replace("%F6","ö",value)
    value=value.replace("%EB","ë",value)
    value=value.replace("%EF","ï",value)
    value=value.replace("%EE","î",value)
    value=value.replace("%EA","ê",value)
    value=value.replace("%E2","â",value)
    value=value.replace("%FB","û",value)
    value=value.replace("%u20AC","€",value)
    value=value.replace("%u2014","–",value)
    value=value.replace("%u2013","—",value)
    value=value.replace("%24","$",value)
    value=value.replace("%21","!",value)
    value=value.replace("%23","#",value)
    value=value.replace("%2C",",",value)
    value=value.replace("%7E",'~',value)
    value=value.replace("%22",'"',value)
    value=value.replace("%25",'%',value)
    value=value.replace("%27","'",value)
    value=value.replace("%F8","ø",value)
    value=value.replace("%2C",",",value)
    value=value.replace("%3A",":",value)
    value=value.replace("%A1","¡",value)
    value=value.replace("%A7","§",value)
    value=value.replace("%B2","²",value)
    value=value.replace("%3B","",value)
    value=value.replace("%3C","<",value)
    value=value.replace("%3E",">",value)
    value=value.replace("%B5","µ",value)
    value=value.replace("%B0","°",value)
    value=value.replace("%7C","|",value)
    value=value.replace("%5E","^",value)
    value=value.replace("%60","`",value)
    value=value.replace("%25","%",value)
    value=value.replace("%A3","£",value)
    value=value.replace("%3D","=",value)
    value=value.replace("%3F","?",value)
    value=value.replace("%3F","€",value)
    value=value.replace("%28","(",value)
    value=value.replace("%29",")",value)
    value=value.replace("%5B","[",value)
    value=value.replace("%5D","]",value)
    value=value.replace("%7B","{",value)
    value=value.replace("%7D","}",value)
    value=value.replace("%2B","+",value)
    value=value.replace("%40","@",value)
    value=value.replace("%09","\t",value)
    value=value.replace("%u0430","а",value)
    value=value.replace("%u0431","б",value)
    value=value.replace("%u0432","в",value)
    value=value.replace("%u0433","г",value)
    value=value.replace("%u0434","д",value)
    value=value.replace("%u0435","е",value)
    value=value.replace("%u0451","ё",value)
    value=value.replace("%u0436","ж",value)
    value=value.replace("%u0437","з",value)
    value=value.replace("%u0438","и",value)
    value=value.replace("%u0439","й",value)
    value=value.replace("%u043A","к",value)
    value=value.replace("%u043B","л",value)
    value=value.replace("%u043C","м",value)
    value=value.replace("%u043D","н",value)
    value=value.replace("%u043E","о",value)
    value=value.replace("%u043F","п",value)
    value=value.replace("%u0440","р",value)
    value=value.replace("%u0441","с",value)
    value=value.replace("%u0442","т",value)
    value=value.replace("%u0443","у",value)
    value=value.replace("%u0444","ф",value)
    value=value.replace("%u0445","х",value)
    value=value.replace("%u0446","ц",value)
    value=value.replace("%u0447","ч",value)
    value=value.replace("%u0448","ш",value)
    value=value.replace("%u0449","щ",value)
    value=value.replace("%u044A","ъ",value)
    value=value.replace("%u044B","ы",value)
    value=value.replace("%u044C","ь",value)
    value=value.replace("%u044D","э",value)
    value=value.replace("%u044E","ю",value)
    value=value.replace("%u044F","я",value)
    return value
    pass 

def main(arg):
    pid = os.getpid()
    ClientDebug=GET_INFO_INT("external_acl_squid_ldap_debug")
    WINDOWS_SERVER_ADMIN=GET_INFO_STR("WINDOWS_SERVER_ADMIN")
    WINDOWS_SERVER_PASS=GET_INFO_STR("WINDOWS_SERVER_PASS")
    WINDOWS_DNS_SUFFIX=GET_INFO_STR("WINDOWS_DNS_SUFFIX")
    ADNETIPADDR=GET_INFO_STR("ADNETIPADDR")
   
    levelLOG=logging.INFO
    if ClientDebug==1: levelLOG=logging.DEBUG
        
    logging.basicConfig(format='%(asctime)s [%(process)d] [%(levelname)s] %(message)s',filename='/var/log/squid/external_acl_squid_ldap.log',  filemode='a',level=levelLOG)
    logging.raiseExceptions = False
    
    logging.info("Starting new helper...")
    CountSleep=0
    HashMem={}
    HashUser={}
    
    while True:
        line = sys.stdin.readline().strip()
        logging.debug("Receive '"+line+"'")
        if len(line)<2:
            logging.debug("Sleeping 1s "+str(CountSleep)+"/3")
            time.sleep( 1 )
            CountSleep=CountSleep+1
            if CountSleep>3:
                logging.info("Die() maxcount >3 -> raise SystemExit(0)")
                raise SystemExit(0)
            continue
        
        MainArray=line.split(" ")
        UserKey=''
        AS_TAG=False
        clt_conn_tag=''
        username=''
        Concurrency=0
        AsConcurrency=False
        Concurrency_text=''
        MULTIGROUPS={}
        TAG_TEXT=""
        FirstVal=unicode(MainArray[0],'utf-8')
        
        try:
            
            if FirstVal.isnumeric():
                Concurrency=int(MainArray[0])
                username=str(MainArray[1])
                group=str(MainArray[2])
                Concurrency_text=str(Concurrency)+" "
            else:
                logging.debug("Old way to split()")
                username=str(MainArray[0])
                group=str(MainArray[1])

                        
            GroupVal=unicode(group,'utf-8')
            logging.debug("username:"+username+", group:"+group)
            username=AccountDecode(username)
            group=AccountDecode(group)
            group=group.decode('utf-8').lower()
            logging.debug("username:"+username+", group:"+group)
            if username=="-":
                logging.debug("username:"+username+", Failed")
                LineToSend=Concurrency_text+"ERR\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue                
                
            
            matches=re.search('^tag:(.+)',group)
            if matches:
                AS_TAG=True;
                group=matches.group(1)
                group=group.strip()
                group=group.decode('utf-8').lower()
                logging.debug("username:"+username+", Search as TAG for :["+group+"]")

            
            
            if GroupVal.isnumeric():
                logging.debug("username:"+username+", Group is a numeric value :["+GroupVal+"]")
                FileGroup="/etc/squid3/acls/container_"+str(GroupVal)+".txt"
                if not os.path.exists(FileGroup):
                    logging.debug("username:"+username+", ["+FileGroup+"] no such file")
                    LineToSend=Concurrency_text+"ERR\n"
                    sys.stdout.write(LineToSend)
                    sys.stdout.flush()
                    continue                    
                    
                data=read_file(FileGroup)
                array=data.split("\n")
                for line in array:
                    if len(line)==0:continue
                    line=line.decode('utf-8').lower()
                    MULTIGROUPS.append(line)
                    
            
            
            ADClass=ActiveDirectory(logging)
            ADClass.NoSQL=True
            ADClass.ldap_server=ADNETIPADDR
            ADClass.username=WINDOWS_SERVER_ADMIN
            ADClass.password=WINDOWS_SERVER_PASS
            ADGroups=ADClass.GetUserGroups(username)
            
            if ADGroups==None:
                logging.debug("username:"+username+", as no group..")
                LineToSend=Concurrency_text+"ERR\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue
            
            if not GroupVal.isnumeric():
                if group in ADGroups:
                    logging.debug("username:"+username+", is a memberOf ["+group+"]")
                    if AS_TAG: TAG_TEXT=" tag="+group
                    LineToSend=Concurrency_text+"OK"+TAG_TEXT+"\n"
                    sys.stdout.write(LineToSend)
                    sys.stdout.flush()
                    continue
                
            if GroupVal.isnumeric():
                FOUND=False
                for zGroup in MULTIGROUPS:
                    if zGroup in ADGroups:
                        logging.debug("username:"+username+", is a memberOf multi-group ["+zGroup+"]")
                        if AS_TAG: TAG_TEXT=" tag="+zGroup
                        LineToSend=Concurrency_text+"OK"+TAG_TEXT+"\n"
                        sys.stdout.write(LineToSend)
                        sys.stdout.flush()
                        FOUND=True
                        break
                if FOUND: continue
                        
                        
            if AS_TAG:
                logging.debug("username:"+username+", is a memberOf of none bu tag requested")
                TAG_TEXT=" tag=everyone"
                LineToSend=Concurrency_text+"OK"+TAG_TEXT+"\n"
                sys.stdout.write(LineToSend)
                sys.stdout.flush()
                continue
                
  
                
            logging.debug("OUTPUT NOTHING FOR "+username)
            sys.stdout.write(Concurrency_text+"ERR\n")
            sys.stdout.flush()
            
        except:
            logging.info(tb.format_exc())
            sys.stdout.write(Concurrency_text+"ERR\n")
            sys.stdout.flush()
            continue

if __name__ == '__main__':
    sys.exit(main(arg=sys.argv[1:]))

