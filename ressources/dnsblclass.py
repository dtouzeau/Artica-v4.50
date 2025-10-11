#!/usr/bin/env python
# -*- coding: utf-8 -*-
import os
import re
import sys
import dns.resolver
import traceback as tb
import syslog


class query_dnsbl():

    def __init__(self):
        self.Debug=False
        self.debug_output = False
        self.dns_use_system = True
        self.error=""
        self.RESOLVER = dns.resolver.Resolver(configure=False)
        self.RESOLVER.nameservers = self.get_nameservers()
        self.syslog_engine = None
        pass


    def get_nameservers(self):
        servers = []
        for line in open('/etc/resolv.conf','r').readlines():
            matches=re.search("^nameserver\s+([0-9\.]+)\s+",line)
            if matches:
                hostname=matches.group(1)
                if hostname=="127.0.0.1":continue
                servers.append(hostname)

        return servers

    def syslog(self,text):
        if self.syslog_engine is None: return False
        try:
            self.syslog_engine.syslog(syslog.LOG_INFO, text)
        except:
            if self.debug_output: print(tb.format_exc())



    def query_uribl(self,uribldomain, querydomain):
        uriblmapoutput  = ""
        stringquery     = querydomain + "." + uribldomain
        if self.debug_output: print("nameservers: ",self.RESOLVER.nameservers)
        try:
            answer = self.RESOLVER.query( (stringquery) , "A")
        except (dns.resolver.NXDOMAIN, dns.name.LabelTooLong, dns.name.EmptyLabel):
            if self.Debug: self.syslog("[DEBUG]: Query %s failed Err.26" % stringquery)
            if self.debug_output:
                print("Query %s failed Err.48" % stringquery)
                print(tb.format_exc())
            return (False, uriblmapoutput)
        except dns.exception.Timeout:
            self.error="URIBL [%s] failed to answer query for '%s' within %s seconds" % (uribldomain, querydomain, self.RESOLVER.lifetime)
            if self.Debug: self.syslog("[DEBUG]: Query %s failed Err.55" % stringquery)
            if self.debug_output:
                print("Query %s failed Err.55" % stringquery)
                print(tb.format_exc())
            return (False, uriblmapoutput)

        responses = ""

        for rdata in answer:
            rdata = str(rdata)
            responses = responses + rdata + " "

        if self.Debug: self.syslog("[DEBUG]: Final responses %s" % responses)
        return (True, responses.strip())

    def parse_config(self,line):
        dnsbl_host=""
        dnsbl_name=""
        dnsbl_result=""
        matches=re.search('(.+)\/(.*?)\/(.*)',line)
        if not matches:
            if self.Debug: self.syslog("[DEBUG]: %s config %s did not matches, return False" % line)
            return (dnsbl_host,dnsbl_name,dnsbl_result)

        dnsbl_host = matches.group(1)
        dnsbl_name = matches.group(2)
        dnsbl_result = matches.group(3)
        dnsbl_host=dnsbl_host.strip()
        if dnsbl_host == "multi.uribl.com": dnsbl_result="127.0.0.2,127.0.0.4,127.0.0.8"
        if dnsbl_host == "ob.surbl.org": dnsbl_result="127.0.0.2,127.0.0.4,127.0.0.8,127.0.0.16,127.0.0.32,127.0.0.64"
        if dnsbl_host == "multi.surbl.org": dnsbl_result="127.0.0.2,127.0.0.4,127.0.0.8,127.0.0.16,127.0.0.32,127.0.0.64"


        if len(dnsbl_result) == 0: dnsbl_result = "127.0.0.1,127.0.0.2"
        return (dnsbl_host, dnsbl_name, dnsbl_result)

