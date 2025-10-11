#!/usr/bin/env python
import sys
import time
sys.path.append('/usr/share/artica-postfix/ressources')
import signal
import threading
import syslog
import traceback as tb
from threadobject import *



#set debug mode for True or False
debug = False
#debug = True
queue = []
threads = []
RUNNING = True
quit = 0




def sig_handler(signum, frame):
    sys.stderr.write("Signal is received:" + str(signum) + "\n")
    global quit
    quit = 1
    global RUNNING
    RUNNING=False


def handle_line(line,ThreadSrnObject):
     if not RUNNING: return
     if not line: return
     if quit > 0: return
     debug = 0
     channel = 0
     if ThreadSrnObject.ExternalAclFirstDebug==1: debug=1
     if line[-1] == '\n': line = line[:-1]
     LenOfline = len(line)
     options=line.split()
     xsyslog("DEBUG: handle_line() L.42 <%s>" % line)
     try:
         if options[0].isdigit(): channel = options.pop(0)
     except IndexError:
         xsyslog("DEBUG: handle_line() L.46 IndexError on %s" % line)
         queue.append("0 OK first=ERROR ptime=\n")
         return True

     try:
         xsyslog("DEBUG: handle_line() --> L.51 input_proxy()")
         outline=ThreadSrnObject.input_proxy(line)
     except:
         xsyslog("ERROR handle_line() L.53 channel = [%s] <%s> <%s>" % (channel,line,tb.format_exc()))
         queue.append("%s OK first=ERROR ptime=\n" % channel)
         return True
     xsyslog("DEBUG: handle_line() --> APPEND <%s>" % outline)
     queue.append(outline)

def handle_stdout(n,ThreadSrnObject):
    debug = 0
    if ThreadSrnObject.ExternalAclFirstDebug == 1: debug = 1
    while RUNNING:
        if quit > 0: return
        while len(queue) > 0:
            item = queue.pop(0)
            try:
                if debug: xsyslog("DEBUG handle_stdout() out <%s>" % item)
                sys.stdout.write(item)
                sys.stdout.flush()
                time.sleep(0.5)
            except IOError as e:
                try:
                    if e.errno == 32:
                        xsyslog("ERROR Broken PIPE!")
                    else:
                        xsyslog("IOError %s" % tb.format_exc())
                except:
                    xsyslog("Stdout <%s>" % tb.format_exc())
            except:
                xsyslog("Stdout <%s>" % tb.format_exc())

def xsyslog(text):
    syslog.openlog("ksrn", syslog.LOG_PID)
    syslog.syslog(syslog.LOG_INFO,"[PROXY_IN]: %s" % text)

def handle_stdin(n,ThreadSrnObject):
    while RUNNING:
         line = sys.stdin.readline()
         if not line: break
         if quit > 0: break
         line = line.strip()
         thread = threading.Thread(target=handle_line, args=(line,ThreadSrnObject,))
         thread.start()
         threads.append(thread)

signal.signal(signal.SIGUSR1, sig_handler)
signal.signal(signal.SIGUSR2, sig_handler)
signal.signal(signal.SIGALRM, sig_handler)
signal.signal(signal.SIGINT, sig_handler)
signal.signal(signal.SIGQUIT, sig_handler)
signal.signal(signal.SIGTERM, sig_handler)

ThreadSrnObject=ThreadSrnObject()

stdout_thread = threading.Thread(target=handle_stdout, args=(1,ThreadSrnObject,))
stdout_thread.start()
threads.append(stdout_thread)
stdin_thread = threading.Thread(target=handle_stdin, args=(2,ThreadSrnObject,))
stdin_thread.start()
threads.append(stdin_thread)

while(RUNNING):
    time.sleep(3)

print("Not RUNNING")
for thread in threads:
    thread.join()
print("All threads stopped.")
