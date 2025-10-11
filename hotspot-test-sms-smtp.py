#!/usr/bin/env python
import sys
import logging
import datetime
import io
import smtplib
from random import randint
from phpserialize import serialize, unserialize
from email.MIMEMultipart import MIMEMultipart
from email.mime.text import MIMEText

import traceback as tb
sys.path.append('/usr/share/artica-postfix/ressources')
from unix import *





def main(ruleid):                         
    print "Testing SMTP connection for rule "+str(ruleid)
    build_progress("{running}",20)
   
    smtp_server_name=GET_STR(ruleid,'sms_smtp_server_name')
    smtp_auth_user=GET_STR(ruleid,'sms_smtp_auth_user')
    smtp_auth_passwd=GET_STR(ruleid,'sms_smtp_auth_passwd')
    fromaddr=GET_STR(ruleid,'sms_smtp_sender')
    sms_smtp_server_port=GET_INT(ruleid,'sms_smtp_server_port')
    tls_enabled=GET_INT(ruleid,'sms_tls_enabled')
    dest=GET_STR(ruleid,'sms_smtp_recipient')
    message=read_file(ruleid,"SMS_SMTP_BODY")
    SMS_SMTP_SUBJECT=read_file(ruleid,"SMS_SMTP_SUBJECT")
    now = datetime.now()
    CurrentTime=int(now.strftime("%s"))
    
    number=random_with_N_digits(4)
    SMS_SMTP_SUBJECT=SMS_SMTP_SUBJECT.replace("%MOBILE%", "00 00 00 00 00")
    SMS_SMTP_SUBJECT=SMS_SMTP_SUBJECT.replace("%CODE%", str(number))
    SMS_SMTP_SUBJECT=SMS_SMTP_SUBJECT.replace("%TIME%", str(CurrentTime))
    if SMS_SMTP_SUBJECT=="EMPTY": SMS_SMTP_SUBJECT=""
    
    message=message.replace("%MOBILE%", "00 00 00 00 00")
    message=message.replace("%CODE%", str(number))
    message=message.replace("%TIME%", str(CurrentTime))
    message=message.replace("\n", "\r\n")

    
   
    msg = "From: "+fromaddr+"\r\n"
    msg = msg+"To: "+dest+"\r\n"
    msg = msg+"Subject: "+SMS_SMTP_SUBJECT+"\r\n"
    msg = msg+"MIME-Version: 1.0\r\n"
    msg = msg+"Content-type: text/plain; charset=\"utf-8\"\r\n"
    msg = msg+"Content-Transfer-Encoding: 8bit\r\n"
    msg = msg+"\r\n"+message
    
 
    
    
    build_progress("{running}",25)
    
   
    try:
        server = smtplib.SMTP(smtp_server_name)
        server.set_debuglevel(2)
    except socket.error as e:
        print "**** Could not connect *****"
        print tb.format_exc()
        build_progress("{failed} could not connect",110)
        return
        
    
    
    
    
    try:
        if tls_enabled ==1:server.starttls()
        if len(smtp_auth_user)>2:
            build_progress("{running}",30)
            server.login(smtp_auth_user, smtp_auth_passwd)
    except:
        print "**** Could not login *****"
        build_progress("{failed}",110)
        print tb.format_exc()
        if server != None: server.quit()
        return    
    
    try:        
        build_progress("{running}",90)
        server.sendmail(fromaddr, dest, msg)
    except:
        print "**** Could not send *****"
        build_progress("{failed}",110)
        print tb.format_exc()
        if server != None: server.quit()
        return
    
    if server != None: server.quit()
    build_progress("{success}",100)
    
    
    
def build_progress(text,pourc):
    array={}
    array["POURC"]=pourc;
    array["TEXT"]=text;
    print "["+str(pourc)+"]: "+text;
    file_put_contents("/usr/share/artica-postfix/ressources/logs/web/hostpot.smtp.progress", serialize(array));

def read_file(ruleid,filename):
    filename="/usr/share/hotspot/"+str(ruleid)+"/"+filename
    if not os.path.exists(filename): return ""
    with io.open(filename, "r", encoding="utf-8") as my_file: return my_file.read()
    pass

def random_with_N_digits(n):
    range_start = 10**(n-1)
    range_end = (10**n)-1
    return randint(range_start, range_end)
    
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