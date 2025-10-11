#!/usr/bin/env python
import sys
import os
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import traceback as tb
import syslog
import logging
import re
import time
from datetime import datetime
from smtpclass import *
import socket
import thread
import os
from daemon import runner
import thread



class App():

    def __init__(self):
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/artica-smtp-notif.pid'
        self.logger = object
        self.pidfile_timeout = 5
        self.smtp = smtpclass()

    def run(self):
        self.smtp.logging=self.logger

        while True:
            time.sleep(60)
            try:
                self.smtp.operate()
            except:
                logger.info(tb.format_exc())






app = App()
logger = logging.getLogger("smtpd-daemon")
logger.setLevel(logging.INFO)
formatter = logging.Formatter("%(asctime)s [%(process)d]: %(message)s")
handler = logging.FileHandler("/var/log/artica-smtp-daemon.log")
handler.setFormatter(formatter)
logger.addHandler(handler)
app.logger=logger
daemon_runner = runner.DaemonRunner(app)
daemon_runner.daemon_context.files_preserve=[handler.stream]
try:
    logger.info("[INFO]: Starting Daemon")
    daemon_runner.do_action()
    logger.info("[INFO]: Stopping Daemon...")
except:
    print(tb.format_exc())