#! /usr/bin/env python

EX_USAGE    = 64    # command line usage error 
EX_NOUSER   = 67    # addressee unknown
EX_SOFTWARE = 70    # internal software error
EX_TEMPFAIL = 75    # temporary failure


import sys
sys.path.append("/usr/share/artica-postfix/ressources/python")
import time, email, email.Message, email.Errors, email.Utils,smtplib, os, socket, random, ArticaSys, logging, syslog, re
import time
import datetime
import optparse 
PID=os.getpid()

def logs(text):
    now = datetime.datetime.now()
    mtime=now.strftime("%Y-%m-%d %H:%M:%S")
    LOG_FILENAME = '/var/log/artica-filter/mail.log'
    logging.basicConfig(filename=LOG_FILENAME,level=logging.INFO)    
    logging.info(mtime+' ['+str(PID)+'] '+text)
    
    
def ldap_tests(user): 
     print "Query LDAP server"   
     ArticaSys.QUERY_LDAP()
 
    
def CheckDisclaimerOrg_Recipient(tmppath, sender, recipient):
        regex = re.compile('(.+?)@(.+)')
        result=regex.search(recipient)
        if result.group(2) is not None:
            recipient_domain=result.group(2)

        result=regex.search(sender)
        if result.group(2) is not None:
            sender_domain=result.group(2)   
           
        
    
    

def altermime(tmppath, sender, recipient):
    if not os.path.exists('/usr/local/bin/altermime'):
         logs("altermime(): altermime is not installed => aborting")
         return
         
    EnableAlterMime=ArticaSys.GET_INFO("EnableAlterMime")
    if EnableAlterMime is None:
        logs("altermime(): altermime is disabled => aborting")
        return
     
    if EnableAlterMime==0:
        logs("altermime(): altermime is disabled => aborting")
        return
    
    DisclaimerOrgOverwrite=ArticaSys.GET_INFO("DisclaimerOrgOverwrite")
    if DisclaimerOrgOverwrite is None:
        DisclaimerOrgOverwrite=0

        
        
    logs("altermime(): altermime is disabled => aborting")
    



def main():
    os.nice(5) 
    
    program_name = sys.argv[0]
    arguments = sys.argv[1:]
    count_arguments = len(arguments)    
    str_commands=' '.join( map( str, sys.argv ) )
    
    regex = re.compile('--test-user\s+(.+?)')
    result=regex.search(str_commands)
    if result.group(1) is not None:
     ldap_tests(result.group(1))
     sys.exit(0)
    
    regex = re.compile('-f\s+(.*?)\s+-r\s+(.*?)\s+-c\s+(.*)')
    result=regex.search(str_commands)
    
    if result.group(1) is not None:
        sender=result.group(1)
    if result.group(2) is not None:
        recipient=result.group(2)
    if result.group(3) is not None:
        client_address=result.group(3)
    
    
    raw_msg = sys.stdin.read()
    #emailmsg = email.message_from_string(raw_msg)
    logs(str(len(raw_msg))+' bytes for message size ') 
    filetemp="/tmp/artica-filter-"+ArticaSys.strToMd5(raw_msg)+'.msg'
    ArticaSys.syslog_mail("From=<"+sender+"> to=<"+recipient+"> client="+client_address+' size='+str(len(raw_msg)) )
    ArticaSys.writefile(filetemp, raw_msg)
    logs("From=<"+sender+"> to=<"+recipient+"> client="+client_address+' size='+str(len(raw_msg))+" Temp file="+filetemp ) 
    altermime(filetemp, sender, recipient)
    
    
    
    sys.exit(0)











if __name__ == '__main__':
    try:
        main()
    except SystemExit, argument:
        sys.exit(argument)
    except Exception:
        xt, xv, tb = sys.exc_info()
        sys.stderr.write("%s %s\n" % (xt, xv))
        sys.stderr.write("Line %d\n" % (tb.tb_lineno))
        sys.exit(EX_TEMPFAIL)
