#! /usr/bin/env python
import sys
sys.path.append( "/usr/share/artica-postfix/ressources/python")
import email
import time
import re
from optparse import OptionParser
import ArticaSys
import threading
import smtplib

class SendMailThread ( threading.Thread ):

    def __init__(self, nom = '', row=[]):
        threading.Thread.__init__(self)
        self.nom = nom
        self.row=row;
        self.status=0;
        
    def run(self):
        ArticaSys.events("Thread["+self.nom+"] id=" +str(self.row["ID"])+" "+self.row["from"]+" => "+self.row["rcpt_to"] , "emailer")
        self.sendmail();
        
    def sendmail(self):
        smtpserv='192.168.1.228';
        print self.nom+" SMTP => "+str(self.row["ID"])
        SMTP = smtplib.SMTP(smtpserv, 25) 
        SMTP.set_debuglevel(1)
        print self.nom+" SMTP => connect "+smtpserv
        SMTP.ehlo()
        print self.nom+" SMTP => SEND DATAS "+smtpserv
        SMTP.sendmail(self.row["from"],self.row["rcpt_to"] ,self.row["msmtp"] )
        
        SMTP.close() 
        ArticaSys.events("Thread["+self.nom+"] success", "emailer")

        self.status=1  
        print self.nom+" stopping"
        #print self.row


#python-mysqldb


def main():
    threadNumbers=20
    i=0
    ArticaSys.events("Query the sql", "emailer")
    result=ArticaSys.QUERY_SQL('SELECT * FROM emailing_campain_queues LIMIT 0,50', 'artica_backup')
    MyThreads=[]
    
    for row in result:
        i=i+1
        if(i>threadNumbers):
            print "Thread number exceed, waiting free threads"
            while GetThreadsStatus(MyThreads)==1:
                print "Waiting free threads...";
                time.sleep(1)
                
            MyThreads=[]
            i=0
        
        a=SendMailThread(str(i),row) 
        MyThreads.append(a)
        a.start();
        

    
    
def  GetThreadsStatus(MyThreads):
    status=0
    MyThreadsFalse=[]
    for tt in MyThreads:
        if tt.status==0:
            MyThreadsFalse.append(1)
            
    if len(MyThreadsFalse)>0:
        print  str(len(MyThreadsFalse))+" thread in process...";
        return 1
    return 0

main()



