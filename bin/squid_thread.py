#!/usr/bin/env python
import os
import sys
import time
import signal
import locale
import syslog
import traceback
import threading
import select
import traceback as tb

MAIN_DELAY = 0.5
JOIN_TIMEOUT = 1.0
DEDUP_TIMEOUT = 0.5

class ClienThread():

    def __init__(self,SPEEDMODE,catz):
        self._exiting = False
        self._cache = {}

    def exit(self):
        self._exiting = True

    def stdout(self, lineToSend):
        try:
            sys.stdout.write(lineToSend)
            sys.stdout.flush()

        except IOError as e:
            if e.errno==32:
                # Error Broken PIPE!"
                pass
        except:
            # other execpt
            pass

    def run(self):
        while not self._exiting:
            if sys.stdin in select.select([sys.stdin], [], [], DEDUP_TIMEOUT)[0]:
                line = sys.stdin.readline()
                LenOfline=len(line)

                if LenOfline==0:
                    self._exiting=True
                    break

                if line[-1] == '\n':line = line[:-1]
                channel = None
                options = line.split()

                try:
                    if options[0].isdigit(): channel = options.pop(0)
                except IndexError:
                    self.stdout("0 OK first=ERROR\n")
                    continue

                # Processing here

                try:
                    self.stdout("%s OK\n" % channel)
                except:
                    self.stdout("%s ERROR first=ERROR\n" % channel)




class Main(object):
    def __init__(self):
        self._threads = []
        self._exiting = False
        self._reload = False
        self._config = ""

        for sig, action in (
            (signal.SIGINT, self.shutdown),
            (signal.SIGQUIT, self.shutdown),
            (signal.SIGTERM, self.shutdown),
            (signal.SIGHUP, lambda s, f: setattr(self, '_reload', True)),
            (signal.SIGPIPE, signal.SIG_IGN),
        ):
            try:
                signal.signal(sig, action)
            except AttributeError:
                pass



    def shutdown(self, sig = None, frame = None):
        self._exiting = True
        self.stop_threads()

    def start_threads(self):

        sThread = ClienThread()
        t = threading.Thread(target = sThread.run)
        t.start()
        self._threads.append((sThread, t))



    def stop_threads(self):
        for p, t in self._threads:
            p.exit()
        for p, t in self._threads:
            t.join(timeout = JOIN_TIMEOUT)
        self._threads = []

    def run(self):
        """ main loop """
        ret = 0
        self.start_threads()
        return ret


if __name__ == '__main__':
    # set C locale
    locale.setlocale(locale.LC_ALL, 'C')
    os.environ['LANG'] = 'C'
    ret = 0
    try:
        main = Main()
        ret = main.run()
    except SystemExit:
        pass
    except KeyboardInterrupt:
        ret = 4
    except:
    sys.exit(ret)