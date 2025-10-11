#!/usr/bin/env python
# Patch New trhread model
import sys
import time
import signal
import threading
sys.path.append('/usr/share/artica-postfix/ressources')
from threadobject import *
import syslog
global ThreadP
import traceback as tb
ThreadP=ThreadSrnObject()

#set debug mode for True or False
debug = False
#debug = True
queue = []
threads = []
RUNNING = True
quit = 0


def sig_handler(signum, frame):
    xyslog("Signal is received:" + str(signum) + "\n")
    global quit
    quit = 1
    global RUNNING
    RUNNING=False


def handle_line(line):
    global sMem
    if not RUNNING: return
    if not line: return
    if quit > 0: return

    global ThreadP
    if not ThreadP.emergency:
        try:
            outline=ThreadP.input_proxy(line)
        except:
            xyslog("[ERROR]: %s" %  tb.format_exc())
            tabs = line.split()
            channel = 0
            if tabs[0].isdigit(): channel = tabs.pop(0)
            queue.append("%s OK srn=ERROR" % channel)
            return True

    else:
        tabs = line.split()
        channel = 0
        if tabs[0].isdigit(): channel = tabs.pop(0)
        queue.append("%s OK emergency=yes srn=WHITE" % channel)
        return True

    queue.append("%s" % outline)

def handle_stdout(n):
    global RUNNING
    while RUNNING:
        if quit > 0: return
        while len(queue) > 0:
            item = queue.pop(0)
            Carriage=item.find("\n")
            if Carriage==-1: item="%s\n" % item
            sys.stdout.write(item)
            sys.stdout.flush()
        time.sleep(0.5)

def xyslog(text):
    sysDaemon = syslog
    sysDaemon.openlog("ksrn", syslog.LOG_PID)
    sysDaemon.syslog(syslog.LOG_INFO, "[PROXY_SRV]: %s" % text)

def handle_stdin(n):
    global RUNNING
    while RUNNING:
         line = sys.stdin.readline()
         linelen=len(line)
         if linelen==0:
             xyslog("Shutdown thread...")
             global quit
             quit = 1
             RUNNING = False
             break
         if quit > 0:
             break
         line = line.strip()
         thread = threading.Thread(target=handle_line, args=(line,))
         thread.start()
         threads.append(thread)

signal.signal(signal.SIGUSR1, sig_handler)
signal.signal(signal.SIGUSR2, sig_handler)
signal.signal(signal.SIGALRM, sig_handler)
signal.signal(signal.SIGINT, sig_handler)
signal.signal(signal.SIGQUIT, sig_handler)
signal.signal(signal.SIGTERM, sig_handler)
stdout_thread = threading.Thread(target=handle_stdout, args=(1,))
stdout_thread.start()
threads.append(stdout_thread)
stdin_thread = threading.Thread(target=handle_stdin, args=(2,))
stdin_thread.start()
threads.append(stdin_thread)

xyslog("Threads started...")
while(RUNNING):
    time.sleep(3)

print("Not RUNNING")
for thread in threads:
    thread.join()
xyslog("All threads stopped.")
