#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import os
import logging
import tailer
import hashlib
import re
from daemon import runner
from articaevents import *
from unix import *
import datetime
import time
import socket
from phpserialize import serialize, unserialize



class App():

    def __init__(self):
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/auth-tail.pid'
        self.nohup="/usr/bin/nohup"
        self.pidfile_timeout = 5
        self.RequestNumber=0
        self.UsersHash={}
        self.CountTotal=0
        self.debug=False
        self.q=object
        self.hostname=socket.gethostname()
        self.ARTICA_LOG_DIR=GET_INFO_STR("ArticaLogDir")
        self.ldap_password=file_get_contents("/etc/artica-postfix/ldap_settings/password")
        self.SMTPNotifEnabledForAuthLog=GET_INFO_INT("SMTPNotifEnabledForAuthLog")
        self.NotifySSHConsoleLogin=GET_INFO_INT("NotifySSHConsoleLogin")
        if self.ARTICA_LOG_DIR=='':
            self.ARTICA_LOG_DIR='/var/log/artica-postfix'
        
        if self.SMTPNotifEnabledForAuthLog==0:self.NotifySSHConsoleLogin=0

    def run(self):
        logger.info('/var/log/auth-tail.log')
        self.q=ArticaEvents(logger)

        for line in tailer.follow(open('/var/log/auth.log')):
            self.parseline(line)
        pass
    
#-------------------------------------------------------------------------------------------------------------------            
    def ifFileTime(self,LineNumber,Maxtime):
        fileTime="/etc/artica-postfix/pids/authtail.py."+str(LineNumber)
        xtime=file_time_min(fileTime)
        if xtime>Maxtime:
            RemoveFile(fileTime)
            file_put_contents(fileTime,datetime.datetime.now())
            return True
        return False
        pass
#-------------------------------------------------------------------------------------------------------------------

    def events(self,severity,subject,description):
        prefix="INSERT IGNORE INTO `squid_admin_mysql` (`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`,`hostname`) VALUES"
        subject=self.q.mysql_escape_string(subject)
        description=self.q.mysql_escape_string(description)
        logzdate=datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        values="('"+logzdate+"','"+description+"','"+subject+"','parseline','auth-tail','0','"+str(severity)+"','"+self.hostname+"')"
        try:
            self.q.QUERY_SQL(prefix+values)
            if not self.q.ok:
                logger.info("MySQL Error "+self.q.mysql_error)
        except:
            logger.info("MySQL Fatal exception")
            
        pass
#-------------------------------------------------------------------------------------------------------------------

    def smtp_events(self,subject,text):
        if(self.NotifySSHConsoleLogin==0): return False
        array={}
        logzdate=datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        ztime=time.time()
        self.ARTICA_LOG_DIR
        array["zdate"]=logzdate
        array["text"]=text;
        array["subject"]=subject;
        array["function"]="SSH Monitor"
        array["file"]="auth-tail"
        array["line"]=80
        array["pid"]=0
        array["category"]=""
        array["TASKID"]=0
        array["ARGVS"]=""
        content=serialize(array);
        file_put_contents(self.ARTICA_LOG_DIR+"/squid_admin_notifs/"+str(ztime)+".log",content)
        pass
        

            
    def parseline(self,line):
        line = line.strip()

        if self.Dustbin(line):
            return



            
        matches=re.match('pam_ldap:\s+ldap_simple_bind Can.*?t contact LDAP server',line)
        if matches:
            if self.ifFileTime(69,5):
                self.events(2,"Error, system cannot access to LDAP database","pam_ldap claim "+line+"\nArtica will try to reconfigure pam and restart LDAP server")
                os.popen(self.nohup+" /usr/share/artica-postfix/bin/artica-install --nsswitch --start  >/dev/null 2>&1 &")
            return True

        matches=re.match('sshd.+?Accepted keyboard-interactive\/pam for\s+(.+?)\s+from\s+(.+?)\s+port',line)
        if matches:
            array={}
            mkdir(self.ARTICA_LOG_DIR+"/sshd-success",644);
            array[matches.group(2)]=matches.group(1)
            ztime=time.time()
            self.smtp_events("SSH: Accept connection from "+matches.group(1)+"["+matches.group(2)+"]",line)
            file_put_contents(self.ARTICA_LOG_DIR+"/sshd-success/"+str(ztime),serialize(array));
            return True;
        
        matches=re.match('sshd.+?Accepted password for\s+(.+?)\s+from\s+(.+?)\s+',line)
        if matches:
            array={}
            mkdir(self.ARTICA_LOG_DIR+"/sshd-success",644);
            array[matches.group(2)]=matches.group(1)
            ztime=time.time()
            self.smtp_events("SSH: Accept connection from "+matches.group(1)+"["+matches.group(2)+"]",line)
            file_put_contents(self.ARTICA_LOG_DIR+"/sshd-success/"+str(ztime),serialize(array));
            return True

        matches=re.match('sshd.+?Invalid user\s+(.+?)\s+from\s+(.+?)$',line)
        if matches:
            array={}
            mkdir(self.ARTICA_LOG_DIR+"/sshd-failed",644);
            array[matches.group(2)]=matches.group(1)
            ztime=time.time()
            self.smtp_events("SSH: Invalid user from "+matches.group(1)+"["+matches.group(2)+"]",line)
            file_put_contents(self.ARTICA_LOG_DIR+"/sshd-success/"+str(ztime),serialize(array));
            return True;

        matches=re.match('sshd\[.+?Failed none for invalid user\s+(.*?)\s+from\s+(.*?)\s+port.*?ssh2',line)
        if matches:
            array={}
            mkdir(self.ARTICA_LOG_DIR+"/sshd-failed",644);
            array[matches.group(2)]=matches.group(1)
            ztime=time.time()
            self.smtp_events("SSH: Fatal! Invalid user from "+matches.group(1)+"["+matches.group(2)+"]",line)
            file_put_contents(self.ARTICA_LOG_DIR+"/sshd-failed/"+str(ztime),serialize(array));
            return True;
        
        
        matches=re.match('sshd.+?error: PAM: Authentication failure for\s+(.+?)\s+from\s+(.+)',line)
        if matches:
            array={}
            mkdir(self.ARTICA_LOG_DIR+"/sshd-failed",644);
            array[matches.group(2)]=matches.group(1)
            ztime=time.time()
            self.smtp_events("SSH: Authentication failure from "+matches.group(1)+"["+matches.group(2)+"]",line)
            file_put_contents(self.ARTICA_LOG_DIR+"/sshd-failed/"+str(ztime),serialize(array));
            return True;

        matches=re.match('sshd.+?Accepted password for (.+?)\s+from\s+(.+?)\s+port',line)
        if matches:
            array={}
            mkdir(self.ARTICA_LOG_DIR+"/sshd-success",644);
            array[matches.group(2)]=matches.group(1)
            ztime=time.time()
            self.smtp_events("SSH: Success session from "+matches.group(1)+"["+matches.group(2)+"]",line)
            file_put_contents(self.ARTICA_LOG_DIR+"/sshd-success/"+str(ztime),serialize(array));
            return True
        
        matches=re.match('Failed password for invalid user\s+(.+?)\s+from\s+(.+?)\s+',line)
        if matches:
            array={}
            mkdir(self.ARTICA_LOG_DIR+"/sshd-failed",644);
            array[matches.group(2)]=matches.group(1)
            ztime=time.time()
            self.smtp_events("SSH: Failed password from "+matches.group(1)+"["+matches.group(2)+"]",line)
            file_put_contents(self.ARTICA_LOG_DIR+"/sshd-failed/"+str(ztime),serialize(array));
            return True;        

        matches=re.match('Failed password for\s+(.+?)\s+from\s+(.+?)\s+',line)
        if matches:
            array={}
            mkdir(self.ARTICA_LOG_DIR+"/sshd-failed",644);
            array[matches.group(2)]=matches.group(1)
            ztime=time.time()
            self.smtp_events("SSH: Failed password from "+matches.group(1)+"["+matches.group(2)+"]",line)
            file_put_contents(self.ARTICA_LOG_DIR+"/sshd-failed/"+str(ztime),serialize(array));
            return True  		

        matches=re.match('pam_ldap: could not open secret file (.+?)\s+\(No such file or directory',line)
        if matches:
            file_put_contents(matches.group(1),self.ldap_password);
            return True

        matches=re.match('_sasl_plugin_load failed',line)
        if matches: return True

        
        
        logger.info('No match:'+line+'"')

        pass
    
    


    def Dustbin(self,zbuffer):
        if zbuffer =='': return True
        if zbuffer.find("monit[")>0: return True
        if zbuffer.find("_sasl_plugin_load")>0: return True
        if zbuffer.find("session opened for user")>0: return True
        if zbuffer.find("session closed for user")>0: return True
        if zbuffer.find("subsystem request for sftp")>0: return True
        if zbuffer.find("error: Bind to port 22 on ")>0: return True
        if zbuffer.find("fatal: Cannot bind any address")>0: return True
        if zbuffer.find("server_exit     : master exited")>0: return True
        if zbuffer.find("PAM unable to dlopen")>0: return True
        if zbuffer.find("PAM adding faulty module")>0:return True
        if zbuffer.find("pam_unix(")>0: return True
        if zbuffer.find("slapcat:")>0: return True
        if zbuffer.find("Successful su")>0: return True
    
        pass
            

          
app = App()
app.debug=False
logger = logging.getLogger("authtail")
logger.setLevel(logging.INFO)
formatter = logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
handler = logging.FileHandler("/var/log/auth-tail.log")
handler.setFormatter(formatter)
logger.addHandler(handler)
daemon_runner = runner.DaemonRunner(app)
#This ensures that the logger file handle does not get closed during daemonization
daemon_runner.daemon_context.files_preserve=[handler.stream]
daemon_runner.do_action()