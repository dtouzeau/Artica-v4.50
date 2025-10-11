#!/bin/sh

# apt-get install libmime-tools-perl libmime-encwords-perl spamassassin libhtml-parser-perl clamav ripmime spfquery
# wget http://search.cpan.org/CPAN/authors/id/G/GA/GAAS/Digest-SHA1-2.13.tar.gz
# tar -xf Digest-SHA1-2.13.tar.gz
# perl Makefile.PL
# make
# make install
mkdir -p /root-mimedefang-builder/usr/local/lib/x86_64-linux-gnu/perl/5.24.1/Digest
mkdir -p /root-mimedefang-builder/usr/local/lib/x86_64-linux-gnu/perl/5.24.1/auto/Digest
mkdir -p /root-mimedefang-builder/usr/local/bin

mkdir -p mkdir -p /root-mimedefang-builder/var/spool/MIMEDefang && chmod 0750 /root-mimedefang-builder/var/spool/MIMEDefang || true
mkdir -p mkdir -p /root-mimedefang-builder/var/spool/MD-Quarantine && chmod 0750 /root-mimedefang-builder/var/spool/MD-Quarantine || true


cp -rfvd /usr/local/lib/x86_64-linux-gnu/perl/5.24.1/Digest/* /root-mimedefang-builder/usr/local/lib/x86_64-linux-gnu/perl/5.24.1/Digest/
cp -rfvd /usr/local/lib/x86_64-linux-gnu/perl/5.24.1/auto/Digest/* /root-mimedefang-builder/usr/local/lib/x86_64-linux-gnu/perl/5.24.1/auto/Digest/
cp -fvd /usr/local/lib/x86_64-linux-gnu/perl/5.24.1/perllocal.pod /root-mimedefang-builder/usr/local/lib/x86_64-linux-gnu/perl/5.24.1/

cp -fvd /usr/local/bin/mimedefang-multiplexor /root-mimedefang-builder/usr/local/bin/
cp -fvd /usr/local/bin/md-mx-ctrl /root-mimedefang-builder/usr/local/bin/
cp -fvd /usr/local/bin/mimedefang /root-mimedefang-builder/usr/local/bin/
cp -fvd /usr/local/bin/watch-mimedefang /root-mimedefang-builder/usr/local/bin/
cp -fvd /usr/local/bin/mimedefang.pl /root-mimedefang-builder/usr/local/bin/
cp -fvd /usr/local/bin/mimedefang-util /root-mimedefang-builder/usr/local/bin/

VERSION=`/usr/local/bin/mimedefang -v|cut -s -d' ' -f3`

cd /root-mimedefang-builder
rm -f /root/mimedefang-builder.$VERSION.tar.gz
tar -czvf /root/mimedefang-builder.$VERSION.tar.gz *
echo "Success /root/mimedefang-builder.$VERSION.tar.gz"
