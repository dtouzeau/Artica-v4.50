#!/usr/bin/env python
import sys
import logging
import datetime

import smtplib
from phpserialize import serialize, unserialize
from email.MIMEMultipart import MIMEMultipart
from email.MIMEText import MIMEText
import traceback as tb
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *


def main(ruleid):                         
    print "Testing SMTP connection for rule "+str(ruleid)
    build_progress("{running}",20)
    
    ENABLED_SMTP=GET_INT(ruleid,'ENABLED_SMTP')
    if ENABLED_SMTP == 0:
        print "!!! SMTP engine is not enabled... !!!"
        build_progress("{failed}",110)
        return
    
    smtp_server_name=GET_STR(ruleid,'smtp_server_name')
    smtp_auth_user=GET_STR(ruleid,'smtp_auth_user')
    smtp_auth_passwd=GET_STR(ruleid,'smtp_auth_passwd')
    fromaddr=GET_STR(ruleid,'smtp_sender')
    tls_enabled=GET_INT(ruleid,'tls_enabled')
    smtp_port=GET_INT(ruleid,'smtp_server_port')
    smtp_ssl=GET_INT(ruleid,'smtp_ssl')
    if smtp_port==0:
        if tls_enabled ==1: smtp_port=587
        if smtp_ssl==1: smtp_port=465
        if smtp_port==0: smtp_port=25
    
    
    dest=fromaddr
    
    print "Server: "+smtp_server_name+":"+str(smtp_port)
    print "From..: "+fromaddr
    print "To....: "+dest
    print "User..: "+smtp_auth_user
    if smtp_ssl==1:
        print "SSL...: YES"
        tls_enabled=0
        
    if tls_enabled==1: print "TLS...: YES"
    
    
    msg = MIMEMultipart()
    msg['From'] =fromaddr
    msg['To'] = dest
    msg['Subject'] = 'HotSpot test email'
    
    message = "Hi,\nThis is a text message sended by the HotSpot\n"
    msg.attach(MIMEText(message))
    
    build_progress("{running}",25)
    
    text = msg.as_string()
    try:
        if smtp_ssl==1: server = smtplib.SMTP_SSL(smtp_server_name,smtp_port)
        if smtp_ssl==0: server = smtplib.SMTP(smtp_server_name,smtp_port)
    except socket.error as e:
        build_progress("{failed} could not connect",110)
        print "could not connect"
        print tb.format_exc()
        return
        
    
    server.set_debuglevel(1)
    
    
    try:
        if tls_enabled ==1:server.starttls()
        if len(smtp_auth_user)>2: server.ehlo()
        if len(smtp_auth_user)>2:
            build_progress("{running}",30)
            server.login(smtp_auth_user, str(smtp_auth_passwd))
            
        build_progress("{running}",90)
        server.sendmail(fromaddr, dest, text)
    except:
        build_progress("{failed}",110)
        print tb.format_exc()
        server.quit()
        return
    finally:
        if server != None: server.quit()


    build_progress("{success}",100)
   
    
    
def build_progress(text,pourc):
    array={}
    array["POURC"]=pourc;
    array["TEXT"]=text;
    print "["+str(pourc)+"]: "+text;
    file_put_contents("/usr/share/artica-postfix/ressources/logs/web/hostpot.smtp.progress", serialize(array));

    
def GET_INT(ruleid,key):
        filename="/usr/share/hotspot/"+str(ruleid)+"/"+key
        if not os.path.exists(filename):
            file_put_contents(filename,0)
            return 0
        data=file_get_contents(filename)
        testdata=unicode(data,'utf-8')
        if data == '':
            return 0
        
        if testdata.isnumeric():
            return int(data)
        return 0
        pass
    
def GET_STR(ruleid,key):
        filename="/usr/share/hotspot/"+str(ruleid)+"/"+key
        if not os.path.exists(filename):
            file_put_contents(filename,"")
            return ""
        data=file_get_contents(filename)
        return data
        pass      

if __name__ == "__main__":
    main(sys.argv[1])


