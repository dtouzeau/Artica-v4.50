#!/usr/bin/env python
from unix import *
import dns.resolver
import traceback as tb
import re

class mysqlcatz:

    def __init__(self, domain):
        self.domain=domain
        self.suffix='filter.artica.center'
        self.error=''


    def GET_CATEGORY(self):
        query="%s.%s" % (self.domain,self.suffix)
        resolver = dns.resolver.Resolver()
        resolver.timeout = 5
        resolver.lifetime = 5
        try:
            answers = dns.resolver.query(query, 'TXT')

        except:
            self.error=tb.format_exc()
            return None


        for rdata in answers:
            for txt_string in rdata.strings:
                matches=re.search('(^[0-9]+):[a-zA-Z_]+:',txt_string)
                if matches: return matches.group(1)

        return 0

