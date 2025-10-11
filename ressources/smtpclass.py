import cPickle as pickle
import smtplib,ssl
from phpserialize import serialize, unserialize
from unix import *
import traceback as tb
import sqlite3
import base64
import email,re
from email.header import Header
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.application import MIMEApplication

class smtpclass:
    def __init__(self,logging=None):
        self.logging=logging
        self.UseSMTP=False
        self.smtp_sender_default=""
        self.smtp_dest_default =""
        self.ConfigParsed=False
        self.content = ""
        self.subject = ""
        self.zDate = ""
        self.filename = ""
        self.line = ""
        self.function=""
        self.attached_file = ""
        self.filecontent = ""
        self.recipients_events = ""
        self.severity = 0
        self.contentsize=0
        self.debug=True
        self.disable_cycle=False

        self.smtp_server="127.0.0.1"
        self.smtp_port=25
        self.use_ssl=0
        self.use_tls=0
        self.logname=""
        self.logpass=""
        self.recipients=""
        self.mailfrom=""
        self.rules=[]
        self.filterid=0
        self.responses_rules=[]
        if GET_INFO_INT("SMTPNotifDebug")==1: self.debug=True




    def load_conf(self):
        sValue=GET_INFO_STR("UfdbguardSMTPNotifs")
        if len(sValue)<20: return False
        base64decoded = base64.b64decode(sValue)
        self.use_ssl=0
        tls_enabled=0
        ssl_enabled=0
        smtp_server_port=25
        ENABLED_SQUID_WATCHDOG=0
        try:
            UfdbguardSMTPNotifs=unserialize(base64decoded)
        except:
            if self.logging is not None: self.logging.info("[ERROR] unserialize %s" % sValue)
            if self.logging is not None: self.logging.info("[ERROR]: %s" % tb.format_exc())
            return False

        try:
            if not "ENABLED_SQUID_WATCHDOG" in UfdbguardSMTPNotifs: return False
            if not "smtp_server_name" in UfdbguardSMTPNotifs: return False
            if not "smtp_sender" in UfdbguardSMTPNotifs: return False
            if not "smtp_dest" in UfdbguardSMTPNotifs: return False
        except:
            if self.logging is not None: self.logging.info("[ERROR]: %s" % tb.format_exc())
            return False

        if "ENABLED_SQUID_WATCHDOG" in UfdbguardSMTPNotifs:
            ENABLED_SQUID_WATCHDOG=strtoint(UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])


        if ENABLED_SQUID_WATCHDOG==1: self.UseSMTP=True
        self.smtp_sender_default=UfdbguardSMTPNotifs["smtp_sender"]
        self.smtp_dest_default = UfdbguardSMTPNotifs["smtp_dest"]


        self.smtp_port=2521
        self.smtp_server="127.0.0.1"
        self.load_rules()
        self.ConfigParsed=True
        return True

    def operate(self):
        try:
            self.load_conf()
        except:
            print(tb.format_exc())
            self.logging.info(tb.format_exc())
            return False
        SMTPNotifEmergency=GET_INFO_INT("SMTPNotifEmergency")
        if SMTPNotifEmergency==1: return True
        db = "/home/artica/SQLITE/system_events.db"
        LogsSended=0

        sql = "SELECT content,subject,zDate,ID,filename,line,severity,function,attached_file,filecontent,recipients FROM `squid_admin_mysql` WHERE sended=0 ORDER BY zDate DESC LIMIT 0,200"
        rows=self.QUERY_SQL(sql,db)
        if rows is None:
            print("No row to send email...")
            return False

        for row in rows:
            self.content = row[0]
            self.subject = row[1]
            self.zDate   = row[2]
            ID = row[3]
            self.filename=row[4]
            self.line=row[5]
            self.severity=row[6]
            self.function=row[7]
            self.attached_file=row[8]
            self.filecontent=row[9]
            self.recipients_events=row[10]
            self.contentsize=len(self.content)


            if self.function is None: self.function = ""
            if self.recipients_events is None: self.recipients_events=""
            if self.filecontent is None: self.filecontent = ""
            if self.attached_file is None: self.attached_file = ""


            if self.zDate is None:
                self.QUERY_SQL("DELETE FROM squid_admin_mysql WHERE ID=%s" % ID, db)
                continue


            try:
                matches=re.search('^[0-9\.,]+$',str(self.zDate))
                if matches:
                    date_time = datetime.fromtimestamp(self.zDate)
                    self.zDate = date_time.strftime("%Y-%m-%d %H:%M:%S")
            except:
                print("DELETE->", ID," LINE 140")
                print(tb.format_exc())
                self.QUERY_SQL("DELETE FROM squid_admin_mysql WHERE ID=%s" % ID, db)
                continue



            now = datetime.today()
            try:
                date_time_obj = datetime.strptime(self.zDate, '%Y-%m-%d %H:%M:%S')
                diff = now - date_time_obj
                diff_minutes = (diff.days * 24 * 60) + (diff.seconds / 60)
            except:
                self.logging.info("[WARNING]: issue on %s date conversion of [%s]" % (ID,self.zDate))
                self.logging.info(tb.format_exc())
                print(tb.format_exc())
                diff_minutes=0


            if diff_minutes > 420:
                self.logging.info("Notification: id:%s created on %s (%s minutes),exceed 420 SKIP" % (ID,self.zDate,diff_minutes))
                self.QUERY_SQL("UPDATE squid_admin_mysql SET sended=1 WHERE ID=%s" % ID, db)
                continue


            if self.severity>1 and len(self.recipients_events)==0 and len(self.filecontent)==0:
                self.QUERY_SQL("UPDATE squid_admin_mysql SET sended=1 WHERE ID=%s" % ID, db)
                continue

            if not self.UseSMTP:
                self.logging.info(
                    "Notification: id:%s UseSMTP return false SKIP" % ID)
                self.QUERY_SQL("UPDATE squid_admin_mysql SET sended=1 WHERE ID=%s" % ID, db)
                continue


            if self.debug: self.logging.info("Notification: %s id=%s subject=%s filename=%s severity=%s" % (self.zDate,ID,self.subject,self.filename,self.severity))


            if not self.get_recipients():
                if self.debug: self.logging.info("[DEBUG]: No recipient found for this notification")
                self.QUERY_SQL("UPDATE squid_admin_mysql SET sended=1 WHERE ID=%s" % ID, db)
                continue

            AllOK=False
            for xrules in self.responses_rules:
                xparms=xrules.split("|")
                if len(xparms)==0: continue
                self.mailfrom=xparms[0]
                self.recipients=xparms[1]
                self.filterid=xparms[2]
                if not self.sendmail():
                    self.logging.info("[ERROR]: Notification failed.")
                    if self.disable_cycle == True: break
                    continue
                AllOK=True

            if self.disable_cycle == True: break
            if AllOK:
                self.QUERY_SQL("UPDATE squid_admin_mysql SET sended=1 WHERE ID=%s" % ID, db)
                LogsSended = LogsSended + 1


        if LogsSended>0: self.logging.info("%s notifications sent" % LogsSended)


    def load_rules(self):
        sql="SELECT sender,recipients,critic,warning,filters,ID FROM smtp_notifications WHERE enabled=1"
        db = "/home/artica/SQLITE/webconsole.db"
        rows = self.QUERY_SQL(sql, db)
        if rows is None: return False
        for row in rows:self.rules.append(row)


    def matches_filters(self,filters):
        array=filters.split("\n")
        if len(array)==0:
            array=[]
            array.append(filters)

        for filter in array:
            filter.replace('.','\.')
            filter.replace('*','.*?')
            matches=re.search(filter,self.subject)
            if matches: return True
            matches = re.search(filter, self.filename)
            if matches: return True
            matches = re.search(filter, self.content)
            if matches: return True
            matches = re.search(filter, self.function)
            if matches: return True

        return False


    def get_recipients(self):
        self.responses_rules = []

        if len(self.recipients_events)>3:
            self.responses_rules.append("%s|%s|%s" % (self.smtp_sender_default, self.recipients_events, 0))

        if len(self.responses_rules)==0:
            if len(self.rules)==0: return False

        if len(self.responses_rules)>0:
            if len(self.rules)==0: return True

        for row in self.rules:
            sender=row[0]
            recipients=row[1]
            critic=row[2]
            warning=row[3]
            filters=row[4]
            ID=row[5]
            if self.severity==0 and critic==1:
                if self.debug: self.logging.info("[DEBUG]: %s critic matches" % recipients)
                if len(filters)==0:
                    self.responses_rules.append("%s|%s|%s" % (sender,recipients,ID))
                    continue

                try:
                    if not self.matches_filters(filters):continue
                    self.responses_rules.append("%s|%s|%s" % (sender, recipients,ID))
                except:
                    self.logging.info("[ERROR]: %s, aborting cycles" % tb.format_exc())
                    continue
            if self.severity==1 and warning==1:
                if self.debug: self.logging.info("[DEBUG]: %s warning matches" % recipients)
                if len(filters)==0:
                    self.responses_rules.append("%s|%s|%s" % (sender,recipients,ID))
                    continue

                try:
                    if not self.matches_filters(filters):continue
                    self.responses_rules.append("%s|%s|%s" % (sender, recipients,ID))
                except:
                    self.logging.info("[ERROR]: %s, aborting cycles" % tb.format_exc())
                    continue

        if len(self.responses_rules)>0: return True
        return False

    def replace_subject(self):
        self.subject = self.subject.replace("{restarting}", "Restarting")
        self.subject = self.subject.replace("{CURLE_COULDNT_RESOLVE_HOST}", "Could not resolve host")
        self.subject = self.subject.replace("{action}", "Action")
        self.subject = self.subject.replace("{restart}", "Restart")
        self.subject = self.subject.replace("{OVERLOADED_SYSTEM}", "Overloaded system")
        self.subject = self.subject.replace("{Reloading_Service_after}", "Reloading service after")
        self.subject = self.subject.replace("{reloading_proxy_service}", "Reloading Proxy service")
        self.subject = self.subject.replace("{installing}", "Installing")
        self.subject = self.subject.replace("{uninstalling}", "Uninstalling")
        self.subject = self.subject.replace("{failed}", "Failed")
        self.subject = self.subject.replace("{connecting}", "Connecting")
        self.subject = self.subject.replace("{permission_denied}", "Permission denied")
        self.subject = self.subject.replace("{stopped}", "Stopped")
        self.subject = self.subject.replace("{start}", "Start")
        self.subject = self.subject.replace("{error_connect_curl}","Unable to establish a connection from")
        self.subject = self.subject.replace("{destination}", "Destination")
        self.subject = self.subject.replace("{notify}", "Notify")
        self.subject = self.subject.replace("{APP_PROXY}", "Proxy service")
        self.subject = self.subject.replace("{already_running}", "already running")
        self.subject = self.subject.replace("{stopping}", "Stopping")
        self.subject = self.subject.replace("{APP_DNSDIST}", "DNS Firewall")
        self.subject = self.subject.replace("{APP_DNSDIST_SQUID}","DNS Load balancer for Proxy")
        self.subject = self.subject.replace("{reloading_proxy_service}","Reloading Proxy service")
        self.subject = self.subject.replace("{success}","Success")
        self.subject = self.subject.replace("{compiling}","Compiling")
        self.subject = self.subject.replace("{category}","Category")
        self.subject = self.subject.replace("{see_report}","See report")
        self.subject = self.subject.replace("{warning}","Warning")
        self.subject = self.subject.replace("{service_stopped}","Service is stopped")
        self.subject = self.subject.replace("{remote_http_service_unavailable}","Remote HTTP Service Unavailable")



    def sendmail(self):
        if not self.ConfigParsed: return True
        AsStartTls=False
        to_user=[]
        smtp = smtplib.SMTP(self.smtp_server, self.smtp_port,timeout=120)
        msg = MIMEMultipart()
        self.replace_subject()
        try:
            with open("/var/log/artica-postfix/artica-smtpd.log", "a") as file_object:
                dt_string = now.strftime('%Y-%m-%d %H:%M:%S')
                slogs="time=\"%s\" level=info msg=\"Delivering Notification [%s]\" to=\"[%s]\"" % (dt_string,self.subject,self.recipients)
                file_object.write(slogs)
        except:
            error = tb.format_exc()
            self.logging.info('[ERROR]: %s' % error)


        msg['Subject'] = Header(self.subject).encode()
        msg['From'] = "{} <{}>".format(Header(self.mailfrom).encode(), self.mailfrom)
        self.recipients=self.recipients.replace(";",",")
        if self.recipients.find(",")>0:
            to_user=self.recipients.split(",")
        else:
            to_user.append(self.recipients)

        if self.filterid>0:
            self.content="%s\nSent with notification rule ID %s\n"% (self.content,self.filterid)

        msg['To']=self.recipients
        msg['Message-id'] = email.utils.make_msgid()
        msg['Date'] = email.utils.formatdate()

        self.content="%s\n\n---------------------------------\nGenerated On: %s\nBy %s function %s() line %s content size:%s bytes" % (self.content,self.zDate,self.filename,self.function,self.line,self.contentsize)


        plain_text = MIMEText(self.content, _subtype='plain', _charset='UTF-8')
        msg.attach(plain_text)

        if len(self.attached_file)>3 and len(self.filecontent)>10:
            try:
                filecontentunecode=base64.b64decode(self.filecontent)
                part = MIMEApplication(filecontentunecode,Name=self.attached_file)
                part['Content-Disposition'] = 'attachment; filename="%s"' % self.attached_file
                msg.attach(part)
                self.logging.info('[INFO]: file %s attached successfully' % (self.attached_file))
            except:
                error = tb.format_exc()
                self.logging.info('[ERROR]: attach file %s failed with error %s' % (self.attached_file, error))



        try:
            smtp.sendmail(self.mailfrom, to_user, msg.as_string())
        except Exception as e:
            error=tb.format_exc()
            self.logging.info('[ERROR]: SMTP Send email failed %s -- %s'% (error,e))
            return False

        if self.debug: self.logging.info('[DEBUG]: SMTP Sent to %s with success...' % self.recipients)
        return True


    def QUERY_SQL(self,sql, db, fetchone=False):
        rows = None
        try:
            conn = sqlite3.connect(db)
            conn.text_factory = lambda b: b.decode(errors='ignore')
        except Error as e:
            self.logging.info("[ERROR]: SQL %s" % e)
            return None

        cur = conn.cursor()
        try:
            cur.execute(sql)
            matches = re.search("(INSERT|insert|update|UPDATE)\s+", sql)
            if matches: conn.commit()
            if fetchone: rows = cur.fetchone()
            if not fetchone: rows = cur.fetchall()
        except:
            self.logging.info("[ERROR]: SQL %s" % tb.format_exc())
            conn.close()
            return None

        conn.close()
        return rows
