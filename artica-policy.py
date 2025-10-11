#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import asyncore, socket, sqlite3, time, syslog, os, signal
import requests,memcache
from unix import *
from postgressql import *
import traceback as tb
import logging

'''
Settings go here
'''
action_reject_blacklisted="action=REJECT address or domain is listed in Artica public database"
action_reject_distributed_detect = "action=REJECT Message rejected - conspicuous relay pattern - contact your administrator\n\n"
action_reject_quota = "action=defer_if_permit Message rejected - account over quota - contact your administrator\n\n"
action_ok = "action=PREPEND SMTP-policy: ok\n\n"

bind_ip = "127.0.0.1"
port = 10032
DEBUG=False

database = ":memory:"
flush_database = "postfix-policy.db"

# use distributed relay detect
distributed_relay_detect = True
distributed_relay_detect_release_time = 1800
distributed_relay_detect_max_hosts = 2  # set this to a reasonable value (many users have more than one device!)

# use throtteling
throttle = True
throttle_max_msg = 1000
throttle_release_time = 3600

# use whitelisting
whitelist = False

'''
Settings end here
'''

try:
    conn = sqlite3.connect(database)
except:
    exit


class PolicyServer(asyncore.dispatcher):

    def __init__(self, host, port):
        global conn
        c = conn.cursor()
        try:
            if (os.path.exists(flush_database)):
                # import the flushed database to our work db (which should be :memory:)
                import_db = sqlite3.connect(flush_database)
                import_query = "".join(line for line in import_db.iterdump())
                conn.executescript(import_query)
            else:
                c.execute(
                    '''CREATE TABLE distributed_relay_detect (sasl_username text,client_address text,sender text,time_created bigint)''')
                c.execute(
                    '''CREATE TABLE throttle (sasl_username text, client_address text, sender text, rcpt_max int, rcpt_count int, msg_max int, msg_count int, time_created bigint)''')
                c.execute('''CREATE TABLE blacklist_ip (cidr text, time_created bigint)''')
                c.execute('''CREATE TABLE whitelist_ip (cidr text, time_created bigint)''')
                c.execute('''CREATE_TABLE whitelist_sender (sender text)''')
                c.execute('''CREATE_TABLE blacklist_sender (sender text)''')
                conn.commit()
        except:
            exit
        asyncore.dispatcher.__init__(self)
        self.create_socket(socket.AF_INET, socket.SOCK_STREAM)
        self.in_syslog("Starting dispatcher policy service on port "+str(port))
        try:
            self.bind((host, port))
            self.listen(1)
        except socket.error, e:
            self.in_syslog("Error number: "+str(e.errno)+" via "+host+":"+str(port))
            asyncore.ExitNow()


    def in_syslog(self,text):
        syslog.openlog("artica-policy", syslog.LOG_PID|syslog.LOG_INFO, syslog.LOG_MAIL)
        syslog.syslog(text)
        syslog.closelog()
        pass



    def cleanup(self):
        # flush the memory database to disk
        global conn
        # remove the old file
        if (os.path.exists(flush_database)):
            os.unlink(flush_database)
        flush_db = sqlite3.connect(flush_database)
        flush_query = "".join(line for line in conn.iterdump())
        flush_db.executescript(flush_query)

    def handle_accept(self):
        if DEBUG: self.in_syslog("self.accept()")
        socket, address = self.accept()
        PolicyRequestHandler(socket)


class PolicyRequestHandler(asyncore.dispatcher_with_send):
    client_address = False
    sasl_username = False
    sender = False
    client_name = False
    reverse_client_name = False
    queue_id="NOQUEUE"
    recipient=False
    message_size=0
    EnableMilterGreylistExternalDB = 0


    def in_syslog(self,text):
        syslog.openlog("artica-policy", syslog.LOG_PID|syslog.LOG_INFO, syslog.LOG_MAIL)
        syslog.syslog(text)
        syslog.closelog()
        pass

    # this function checks the records against counters
    # a user is permitted to send a given amount of mail
    # message will be rejected if we have an overflow of the counter associated with the record
    def check_throttle(self):
        if throttle == False:
            return True
        global conn
        c = conn.cursor()
        try:
            if self.sasl_username.__len__() > 0:
                c.execute('''SELECT msg_count FROM throttle WHERE sasl_username= ?''', [self.sasl_username])
            else:
                c.execute('''SELECT msg_count FROM throttle WHERE sender = ?''', [self.sender])
        except Exception:
            syslog.syslog("database problem")
            return True

    # this function checks the record for a distributed relay pattern
    # if the same sasl user or the same sender address tries to relay a mail
    # we check if the same username or sender address is used by multiple client addresses
    # if so, this is a certain sign of an abuse attempt
    def check_distributed_relay(self):
        if not distributed_relay_detect : return True
        # check if we got multiple ips for the same sasl_username or sender
        global conn
        c = conn.cursor()
        try:
            if self.sasl_username.__len__() > 0:
                c.execute(
                    '''SELECT COUNT(DISTINCT(client_address)) FROM distributed_relay_detect WHERE sasl_username = ?''',[self.sasl_username])
            else:
                c.execute('''SELECT COUNT(DISTINCT(client_address)) FROM distributed_relay_detect WHERE sender = ?''',[self.sender])
        except Exception, e:
            syslog.syslog("database problem")
            return True

        count_hosts = c.fetchone()[0]
        if count_hosts > distributed_relay_detect_max_hosts:
            return False
        else:
            # check if the record exists and create if not
            try:
                c.execute('''SELECT COUNT(client_address) FROM distributed_relay_detect WHERE sasl_username = ? AND sender = ? AND client_address = ?''',[self.sasl_username, self.sender, self.client_address])
            except Exception:
                syslog.syslog("check_distributed_relay: database problem")
                return True

            count = c.fetchone()[0]
            if (count == 0):
                try:
                    # remove old record and insert the new triplet
                    c.execute('''DELETE FROM distributed_relay_detect WHERE time_created < ?''',[(int(time.time()) - distributed_relay_detect_release_time)])
                    c.execute('''INSERT INTO distributed_relay_detect VALUES (?,?,?,?)''',[self.sasl_username, self.client_address, self.sender, int(time.time())])
                    syslog.syslog("new record (drt): client_address=%s sasl_username=%s sender=%s" % (self.queue_id,self.client_name,self.client_address, self.sasl_username, self.sender,self.recipient))
                    conn.commit()
                except:
                    pass
            if(count>0):
                syslog.syslog("new record (drt): client_address=%s sasl_username=%s sender=%s" % (count,
                self.queue_id, self.client_name, self.client_address, self.sasl_username, self.sender, self.recipient))

        return True

    def check_record(self):
        global conn
        syslog.openlog("artica-policy", syslog.LOG_PID|syslog.LOG_INFO, syslog.LOG_MAIL)
        action = action_ok
        if self.client_address == False or self.sasl_username == False or self.sender == False:
            if DEBUG: syslog.syslog("%s: artica-policy: from %s[%s] something missing in the record" % (self.queue_id,self.client_name,self.client_address))
            return action

        if len(self.queue_id)==0:self.queue_id="NOQUEUE"

        try:
            self.EnableMilterGreylistExternalDB=GET_INFO_INT("EnableMilterGreylistExternalDB")
        except:
            if DEBUG: self.in_syslog("FATAL WHILE READ EnableMilterGreylistExternalDB")



        if DEBUG: self.in_syslog("INVOKE check_blacklisted() =='"+str(self.EnableMilterGreylistExternalDB)+"'")
        if self.EnableMilterGreylistExternalDB==1:
            action_reject_blacklisted=self.check_blacklisted()
            if action_reject_blacklisted is not None:
                syslog.closelog()
                return action_reject_blacklisted+"\n\n"


        if len(self.sasl_username)>0:
            if not self.check_distributed_relay(): action = action_reject_distributed_detect
            # special case for bouncers overriding previous action, let postfix control this with other restrictions
            if self.sender.__len__() == 0: action = action_ok

        if action == action_ok:
            syslog.syslog("client_address=%s sasl_username=%s from=%s action=ok" % (self.client_address, self.sasl_username, self.sender))
        elif action == action_reject_distributed_detect:
            syslog.syslog("client_address=%s sasl_username=%s from=%s action=reject distributed relay" % (self.client_address, self.sasl_username, self.sender))
        syslog.closelog()
        return action


    def in_syslog(self,text):
        syslog.openlog("artica-policy", syslog.LOG_PID|syslog.LOG_INFO, syslog.LOG_MAIL)
        syslog.syslog(text)
        syslog.closelog()
        pass



    def check_whitelisted(self):
        POSTGRES = Postgres()
        POSTGRES.log = logger
        member=""
        domain=""
        matches=re.search('^(.+?)@(.+)',self.sender)

        if matches:
            member=matches.group(1)
            domain=matches.group(2)


        logger.debug("Checking sender <%s>@<%s>" % (member,domain))
        sql = "SELECT id FROM miltergreylist_acls WHERE method='whitelist' AND type='from' AND pattern='%s'" % self.sender
        rows = POSTGRES.QUERY_SQL(sql)
        if len(rows)>0:
            syslog.syslog("%s: artica-policy: from %s[%s] from=<%s> to=<%s> whitelisted-address" % (self.queue_id, self.client_name, self.client_address, self.sender, self.recipient))
            return True

        sql = "SELECT id FROM miltergreylist_acls WHERE method='whitelist' AND type='from' AND pattern='*@%s'" % domain
        rows = POSTGRES.QUERY_SQL(sql)
        if len(rows)>0:
            syslog.syslog("%s: artica-policy: from %s[%s] from=<%s> to=<%s> whitelisted-domain" % (
            self.queue_id, self.client_name, self.client_address, self.sender,self.recipient))
            return True

        if DEBUG: syslog.syslog("%s: artica-policy: from %s[%s] from=<%s> Not whitelisted" % (
        self.queue_id, self.client_name, self.client_address,self.sender))
        return False
        pass



    def check_blacklisted(self):
        if DEBUG: self.in_syslog("check_blacklisted() START")
        mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)
        value = str(mc.get("POLICYD:"+self.sender))
        if DEBUG: self.in_syslog("check_blacklisted()  memcache.Client return '"+str(value)+"'")

        if value:
            if len(str(value))>20: return value;
            if value=="NONE": return None;

        try:
            if self.check_whitelisted():
                mc.set("POLICYD:" + self.sender, "NONE", 2880)
                return None
        except:
            self.in_syslog(tb.format_exc());
            return None



        try:
            r = requests.get('https://rbl.artica.center/api/rest/rbl/query/email/'+self.sender)
            # proxy = {"http": "http://username:password@proxy:port"}
            r.raise_for_status()
        except requests.exceptions.RequestException as err:
            self.in_syslog("HTTP ENGINE Something Else")
            return False
        except requests.exceptions.HTTPError as errh:
            self.in_syslog("HTTP ENGINE Http Error")
            return False
        except requests.exceptions.ConnectionError as errc:
            self.in_syslog("HTTP ENGINE  Error Connecting")
            return False
        except requests.exceptions.Timeout as errt:
            self.in_syslog("HTTP ENGINE Timeout Error")
            return False

        if (r.status_code != requests.codes.ok):
            self.in_syslog(" Error " +r.status_code)
            return False

        data = r.json()
        if data["FOUND"]:
            mc.set("POLICYD:"+self.sender, "action=REJECT Artica Reputation " + data["TYPE"], 1880)
            return "action=REJECT Artica Reputation "+data["TYPE"]

        mc.set("POLICYD:" + self.sender,"NONE",1880)
        return None



    def handle_read(self):
        if DEBUG: self.in_syslog("Read socket....")
        thestring = self.recv(10248)
        if DEBUG: self.in_syslog("socket data: "+str(len(thestring))+" bytes")
        lines = thestring.split("\n")
        for line in lines:

            line_array = line.split("=", 1)
            if len(line_array)<2: continue
            if DEBUG: self.in_syslog(line_array[0] +"===="+ line_array[1])
            if line_array[0] == 'client_address':
                self.client_address = line_array[1]
            elif line_array[0] == 'sasl_username':
                self.sasl_username = line_array[1]
            elif line_array[0] == 'sender':
                self.sender = line_array[1]
            elif line_array[0] == 'client_name':
                self.client_name=line_array[1]
            elif line_array[0] == 'reverse_client_name':
                self.reverse_client_name=line_array[1]
            elif line_array[0] == 'queue_id':
                self.queue_id = line_array[1]
            elif line_array[0] == 'recipient':
                self.recipient = line_array[1]

        if DEBUG: self.in_syslog("self.check_record()")
        EnableMilterGreylistExternalDB=GET_INFO_INT("EnableMilterGreylistExternalDB")
        action = self.check_record()
        self.send(action)
        self.close()


def shutdown_handler(signum, frame):
    global s
    s.cleanup()
    del s
    raise asyncore.ExitNow()


signal.signal(signal.SIGHUP, shutdown_handler)

pid = str(os.getpid())
f = open('/var/run/artica-policy.pid', 'w')
f.write(pid)
f.close()

logger = logging.getLogger("artica-policy")
formatter = logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
handler = logging.FileHandler("/var/log/artica-policy.log")
handler.setFormatter(formatter)
logger.addHandler(handler)
logger.setLevel(logging.DEBUG)
# logger.setLevel(logging.INFO)
logger.info("Sarting new server instance pid %s" %str(pid))

s = PolicyServer('127.0.0.1', 10032)

try:
    asyncore.loop()
except:
    # graceful exit
    s.cleanup()
    del s
    raise asyncore.ExitNow()
