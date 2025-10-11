#!/usr/bin/env python
#-*- coding:utf-8 -*-

from __future__ import print_function

import imaplib, email
import re
import os
import hashlib
from message import Message
import datetime
import traceback
import syslog




class MailboxClient:
    """Operations on a mailbox"""

    def __init__(self, host, port, username, password, remote_folder):

        self.connected=False
        self.adjust_remote_folder =''
        if int(port)==143:
            self.mailbox = imaplib.IMAP4(host, port)
            print("MailboxClient: Using non-ssl protocol")

        if int(port)==993: self.mailbox = imaplib.IMAP4_SSL(host, port)

        try:
            self.mailbox.login(username, password)
        except:
            in_syslog("MailboxClient: Log in failed with username "+username+":"+password+" exiting")
            return None

        self.connected = True
        self.mailbox.select('',readonly=True)

        in_syslog("MailboxClient: opening remote folder " + host + ":" + str(port) + "/"+remote_folder)

        self.adjust_remote_folder=remote_folder
        print("Select folder "+remote_folder)
        typ, data = self.mailbox.select(remote_folder, readonly=True)
        if typ != 'OK':
            # Handle case where Exchange/Outlook uses '.' path separator when
            # reporting subfolders. Adjust to use '/' on remote.
            self.adjust_remote_folder = re.sub('\.', '/', remote_folder)
            typ, data = self.mailbox.select(self.adjust_remote_folder, readonly=True)
            if typ != 'OK':
                in_syslog("MailboxClient: Could not select remote folder [" + remote_folder + "]")



    def copy_emails(self, days, local_folder, wkhtmltopdf):
        if not self.connected: return (0, 0)

        n_saved = 0
        n_exists = 0

        self.local_folder = local_folder
        self.wkhtmltopdf = wkhtmltopdf
        criterion = 'ALL'

        if days:
            date = (datetime.date.today() - datetime.timedelta(days)).strftime("%d-%b-%Y")
            criterion = '(SENTSINCE {date})'.format(date=date)
            print("Search messages %s" % criterion)
            self.mailbox.select(self.adjust_remote_folder,readonly=True)
        try:
            print("Search messages %s" % criterion)
            typ, data = self.mailbox.search(None, criterion)

        except Exception as e:
            tback=traceback.format_exc(e)
            if hasattr(e, 'strerror'):
                print("Search messages %s ERROR.." % criterion)
                print(tback)
                in_syslog("copy_emails failed mailbox.search :%s criterion %s (%s)" % (e.strerror,criterion,tback))
            else:
                in_syslog("copy_emails failed mailbox.search criterion %s (%s)" % (criterion,tback))
            return (0,0)

        messages_array=data[0].split()
        messages_count=len(messages_array)
        print("fetching found  %s messages" % messages_count)
        for num in messages_array:
            typ, data = self.mailbox.fetch(num, '(RFC822)')

            if self.saveEmail(data):
                n_saved += 1
            else:
                n_exists += 1
        in_syslog(local_folder+ " "+ str(n_saved)+" Backuped new messages")
        return (n_saved, n_exists)


    def cleanup(self):
        if self.connected:
            self.mailbox.select()
            self.mailbox.close()
            self.mailbox.logout()


    def getEmailFolder(self, msg, data):
        if msg['Message-Id']:
            foldername = re.sub('[^a-zA-Z0-9_\-\.()\s]+', '', msg['Message-Id'])
        else:
            foldername = hashlib.sha224(data).hexdigest()

        foldername=hashlib.md5(foldername).hexdigest()
        year = 'None'

        if msg['Date']:
            match = re.search('\d{1,2}\s\w{3}\s(\d{4})', msg['Date'])
            if match:
                year = match.group(1)


        return os.path.join(self.local_folder, year, foldername)

    def file_put_contents(self,filename, data):
        try:
            f = open(filename, 'w')
            f.write(str(data))
            f.close()
        except:
            return

    def saveEmail(self, data):
        for response_part in data:
            if isinstance(response_part, tuple):
                msg = ""
                messages_queue="/var/log/artica-postfix/imapbox-queue"

                if isinstance(response_part[1], str):
                    msg = email.message_from_string(response_part[1])
                else:
                    try:
                        msg = email.message_from_string(response_part[1].decode("utf-8"))
                    except:
                        print("couldn't decode message with utf-8 - trying 'ISO-8859-1'")
                        msg = email.message_from_string(response_part[1].decode("ISO-8859-1"))

                srcdirectory = self.getEmailFolder(msg, data[0][1])
                directory=str(srcdirectory)
                directory=re.sub("[\"\'\s]", "", directory)
                directory.replace('"', "")
                directory.replace("'", "")
                directory.replace(" ", "")
                directory.strip("'")
                directory.strip('"')
                if os.path.exists(directory): return False
                os.makedirs(directory)
                if not os.path.exists(messages_queue): os.makedirs(messages_queue)
                message_file=os.path.basename(directory)
                queue_path=messages_queue+"/"+message_file

                try:
                    message = Message(directory, msg)
                    message.createRawFile(data[0][1])
                    message.createMetaFile()
                    message.extractAttachments()
                    self.file_put_contents(queue_path, directory)
                except Exception as e:
                    in_syslog("MailboxClient.saveEmail(): extraction failed "+traceback.format_exc(e))
                    return False


                try:
                    if self.wkhtmltopdf:
                        message.createPdfFile(self.wkhtmltopdf)
                except Exception as e:
                        in_syslog("MailboxClient.saveEmail() failed:" + traceback.format_exc(e))



        return True


def save_emails(account, options):
    mailbox = MailboxClient(account['host'], account['port'], account['username'], account['password'], account['remote_folder'])
    stats = mailbox.copy_emails(options['days'], options['local_folder'], options['wkhtmltopdf'])
    mailbox.cleanup()
    in_syslog('{} emails created, {} emails already exists'.format(stats[0], stats[1]))

def in_syslog(text):
    syslog.openlog("imapbox", syslog.LOG_PID|syslog.LOG_INFO, syslog.LOG_MAIL)
    syslog.syslog(text)
    syslog.closelog()
    pass


def get_folder_fist(account):
    if int(account['port']) == 143:
        mailbox = imaplib.IMAP4(account['host'], account['port'])
        print("MailboxClient: Using non-ssl protocol")

    if int(account['port']) == 993: mailbox = imaplib.IMAP4_SSL(account['host'], account['port'])
    mailbox.login(account['username'], account['password'])
    mailbox.select('', readonly=True)
    folder_list = mailbox.list()[1]
    mailbox.logout()
    return folder_list
