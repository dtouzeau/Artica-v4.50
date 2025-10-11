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



smtp = smtpclass()
logger = logging.getLogger("smtpd-daemon")
logger.setLevel(logging.INFO)
formatter = logging.Formatter("%(asctime)s [%(process)d]: %(message)s")
handler = logging.FileHandler("/var/log/artica-smtp-daemon.log")
handler.setFormatter(formatter)
logger.addHandler(handler)
smtp.logging=logger
try:
    smtp.operate()
except:
    print(tb.format_exc())

