#!/usr/bin/python -O
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import re
import os
import gzip
import traceback as tb



def extract_gz(filename):

   cur = 0
   with gzip.open(target_file, 'rt') as file_in:
      for line in file_in:
         cur = cur + 1
         url=parseLine(line)


def parseLine(TheLine):
   global debug
   global TheDoms
   global TheCatz
   global catz

   if TheLine.find('000 0 NONE') > 0: return False
   if TheLine.find('error:invalid-request') > 0: return False
   matches = re.search('(CONNECT|HEAD|GET|DELETE|POST|LIST|OPTIONS|PATCH|PUT)\s+(.+?)\s+', TheLine)
   if not matches:
      print("No matches %s" % TheLine)
      return False

def main(argv):
   global debug
   start_time = datetime.now()
   try:
      filename = argv[0]
   except:
      print("Usage [source]")
      sys.exit(0)

   print("Open %s" % target_file)
   matches = re.search('\.gz$', target_file)
   if matches:
      extract_gz(filename)
      sys.exit(0)





if __name__ == "__main__":
   main(sys.argv[1:])