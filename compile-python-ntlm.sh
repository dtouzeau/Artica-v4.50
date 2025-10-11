#!/bin/sh

DIRS=""
FILES=""
DIRS="${DIRS} certifi-2020.12.5-py2.7.egg"
DIRS="${DIRS} idna-2.10-py2.7.egg"
DIRS="${DIRS} requests-2.25.1-py2.7.egg"
DIRS="${DIRS} urllib3-1.26.3-py2.7.egg"
DIRS="${DIRS} ntlm_auth-1.5.0-py2.7.egg"
FILES="${FILES} requests_ntlm2-6.3.1-py2.7.egg"
FILES="${FILES} requests_ntlm-1.1.0-py2.7.egg"
ROOTP="/root/python-ntlm"
PACKAGE="/root/python-ntml.tar.gz";

  if [  -d $ROOTP ]
    then
	      echo "Removing directory $ROOTP"
	      rm -rf $ROOTP
    fi

  for DIRS in ${DIRS}; do
      SPATH=/usr/local/lib/python2.7/dist-packages/${DIRS}
      FPATH=$ROOTP$SPATH
      if [ ! -d $SPATH ]
        then
          echo "$SPATH no such directory, aborting"
          exit
      fi

      echo "Creating directory $FPATH"
      mkdir -p $FPATH
      echo "Copy $SPATH/* to $FPATH/"
      cp -rfd $SPATH/* $FPATH/
  done

  for FILES in ${FILES}; do
      SPATH=/usr/local/lib/python2.7/dist-packages/${FILES}
      FPATH=$ROOTP$SPATH
      if [ ! -f $SPATH ]
        then
          echo "$SPATH no such file, aborting"
          exit
      fi

    echo "Copy $SPATH to $FPATH"
    cp -fd $SPATH $FPATH
    echo "compressing package..."

    cd $ROOTP

    if [ -f $PACKAGE ]
      then
	      echo "Removing $PACKAGE"

    fi
    echo "Turn into $ROOTP directory"
    cd $ROOTP
    echo "Compressing $PACKAGE"
    tar czf /root/python-ntml.tar.gz *
    echo "Compressing $PACKAGE DONE"

done




