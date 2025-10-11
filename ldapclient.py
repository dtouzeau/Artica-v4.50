#!/usr/bin/python
# -*- coding: utf-8 -*-
import re
import random
import os
import signal
import traceback
import sys
import ldap
from datetime import datetime
from datetime import timedelta
from time import mktime
from time import gmtime
from pprint import pprint

ldap_connection=None
user="Administrator"

ldap_connection = ldap.initialize( 'ldaps://192.168.95.10:636')
ldap_connection.set_option(ldap.OPT_REFERRALS, 0)
ldap_connection.set_option(ldap.OPT_PROTOCOL_VERSION, 3)
ldap_connection.set_option(ldap.OPT_X_TLS, ldap.OPT_X_TLS_DEMAND)
ldap_connection.set_option(ldap.OPT_X_TLS_DEMAND, True)
ldap_connection.set_option(ldap.OPT_DEBUG_LEVEL, 255)
ldap_connection.set_option(ldap.OPT_X_TLS_NEWCTX, 0)
ldap_connection.simple_bind_s("Administrator@labo.int", "*****")

user_base = "DC=labo,DC=int"
user_filter = "(sAMAccountName=%s)" % (user)
matches = re.match('^(.*?)@.+', user)
if matches: user_filter = "(userPrincipalName=%s)" % (user)

user_scope = ldap.SCOPE_SUBTREE
status_attribs = ['pwdLastSet', 'accountExpires', 'userAccountControl', 'memberOf',
                  'msDS-User-Account-Control-Computed', 'msDS-UserPasswordExpiryTimeComputed',
                  'msDS-ResultantPSO', 'lockoutTime', 'sAMAccountName', 'displayName','dn']

print ("user_base=",user_base," user_filter=",user_filter)
results = ldap_connection.search_s(user_base, user_scope, user_filter, status_attribs)

pprint(results)

