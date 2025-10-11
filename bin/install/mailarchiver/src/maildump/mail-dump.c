//	cc -I../../sendmail -I../../include -o sample sample.c libmilter.a ../libsm/libsm.a -pthread
//	% ./sample -p local:/var/run/f1.sock

/* A trivial filter that dump all email to a file. */

#include "config.h"
#include <sys/stat.h>
#include <sys/types.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <signal.h>
#include <sysexits.h>
#include <unistd.h>
#include <fcntl.h>
#include <time.h>
#if defined(USE_SYSLOG)
#include <syslog.h>
#endif

#include "libmilter/mfapi.h"

#ifndef true
typedef int bool;
# define false	0
# define true	1
#endif /* ! true */


struct mlfiPriv
{
	char	*mlfi_fname;
	FILE	*mlfi_fp;
};

#define MLFIPRIV	((struct mlfiPriv *) smfi_getpriv(ctx))

/*Program GLOBAL Settings*/
#define FLAG_ADDHEADER    1
/*Program LOCAL Settings*/
char *settings_outputdir;
char *settings_pidfile;
short settings_daemonize;
short settings_timestamp;
/*program variables*/
int file_lock;

extern sfsistat	 mlfi_cleanup(SMFICTX *, bool);

//check email on unstandart character for filesystem before createfile by mask
char *email_check(char *email)
  {
  char *newstring;
  int i;
  char currchar;
  if (*email=='<')
    newstring=strdup(email+1);		//dublicate without first char
  else
    newstring=strdup(email);		//dublicate with first char

  if (newstring==NULL)
    {
#if defined(USE_SYSLOG)
		syslog(LOG_ERR, "can't allocate memory for duplicate string");
#endif
		return NULL;
    }
  for (i=0;*(newstring+i)!=0;i++)
    {
    currchar=*(newstring+i);
    if ( (currchar>='A' && currchar<='Z') || (currchar>='a' && currchar<='z') ||
      (currchar>='0' && currchar<='9') || currchar=='@' || currchar=='.')
      ;	//i't okay symbol
    else
      {
      if (*(newstring+i+1)==0 && *(newstring+i)=='>')	//it's previouschar and it's >
        *(newstring+i)=0;		//remove it
      else
        *(newstring+i)='_';
      }
    if (i>100)	//it's too big string
      {
      *(newstring+i)=0;		//remove it
      break;
      }
    }
  return newstring;
  }

void signal_handler(sig)
int sig;
  {
  syslog(LOG_INFO, "MailArchiver signal received %d",sig);
  switch(sig)
    {
    case SIGTERM:
      syslog(LOG_INFO, "Exiting MailArchiver");
      //unlock and remove pid-file
      close(file_lock);
      if (unlink(settings_pidfile))
        syslog(LOG_ERR, "Can't remove PID file (%s)",settings_pidfile);
      //future - remove pipe
      exit(0);
      break;
    }
  }

sfsistat
mlfi_envfrom(ctx, envfrom)
	SMFICTX *ctx;
	char **envfrom;
{
	struct stat statbuf;
	struct mlfiPriv *priv;
	int        fd = -1;
	time_t     currtime;
	struct tm *currtime_ts;
	char       currtime_buf[17];
	char       *email_from_checked;
	
	//first - checking exist dump directory
	if (stat(settings_outputdir, &statbuf) == -1)
	  {
#if defined(USE_SYSLOG)
	  syslog(LOG_ERR, "dump directory not exist!try to create");
#endif
	  umask(0);
	  if (mkdir(settings_outputdir,0777)==-1)
	    {
#if defined(USE_SYSLOG)
    	    syslog(LOG_ERR, "can't create dump directory");
#endif
	    return SMFIS_TEMPFAIL;
	    }
	  else
#if defined(USE_SYSLOG)
	    syslog(LOG_ERR, "dump directory created");
#endif
  
	  }
	else
	  if (S_ISDIR(statbuf.st_mode)==0)
	    {
#if defined(USE_SYSLOG)
	    syslog(LOG_ERR, "found file with name is qual dump directory!");
#endif
	    return SMFIS_TEMPFAIL;
	    }
	if (settings_timestamp)	//user want add timestamp & username into file name
	  {
	  currtime=time(NULL);	//get current time;
	  currtime_ts=localtime(&currtime);
	  strftime(currtime_buf,sizeof(currtime_buf),"%Y-%m-%d-%H-%M",currtime_ts); /* Format time, "yyyy-mm-dd-hh-mm" */
	
	  email_from_checked=email_check(envfrom[0]);
	  if (email_from_checked == NULL)
	    {
	    /* can't accept this message right now */
#if defined(USE_SYSLOG)
	    syslog(LOG_ERR, "can't allocate memory for checked email");
#endif
	    return SMFIS_TEMPFAIL;
	    }
	  }
	/* allocate some private memory */
	priv = malloc(sizeof *priv);
	if (priv == NULL)
	{
		/* can't accept this message right now */
#if defined(USE_SYSLOG)
	  syslog(LOG_ERR, "can't allocate memory");
#endif
	  return SMFIS_TEMPFAIL;
	}
	memset(priv, '\0', sizeof *priv);

	/* create filname for store this message */
	priv->mlfi_fname = malloc(1024);
	if (settings_timestamp)
	  sprintf(priv->mlfi_fname,"%s/arch.%s.%s.XXXXXXXX",settings_outputdir,currtime_buf,email_from_checked);
	else
	  sprintf(priv->mlfi_fname,"%s/arch.XXXXXXXX",settings_outputdir);
	if (priv->mlfi_fname == NULL)
	{
		free(priv);
#if defined(USE_SYSLOG)
		syslog(LOG_ERR, "can't allocate memory");
#endif
		return SMFIS_TEMPFAIL;
	}
	if (settings_timestamp)	//when user want add timestamp & username into file name
  	  free(email_from_checked);	//empty not needed now variable
	/* open a file to store this message */
	//now create dump-file
	if ((fd = mkstemp(priv->mlfi_fname)) < 0 ||
	    (priv->mlfi_fp = fdopen(fd, "w+")) == NULL)
	{
#if defined(USE_SYSLOG)
		syslog(LOG_ERR, "Can't create DumpFile: %s",priv->mlfi_fname);
#endif
		if (fd >= 0)
			(void) close(fd);
		free(priv->mlfi_fname);
		free(priv);
		return SMFIS_TEMPFAIL;
	}

	/* save the private data */
	smfi_setpriv(ctx, priv);
	
	//now write in file real MAILFROM
	fprintf(priv->mlfi_fp, "X-REAL-MAILFROM: %s\r\n", envfrom[0]);

	/* continue processing */
	return SMFIS_CONTINUE;
}

sfsistat 
mlfi_envrcpt(ctx, envrcpt)
	SMFICTX *ctx;
	char **envrcpt;
{
//now write in file real RCPT TO
	fprintf(MLFIPRIV->mlfi_fp, "X-REAL-RCPTTO: %s\r\n", envrcpt[0]);

	return SMFIS_CONTINUE;
}


sfsistat
mlfi_header(ctx, headerf, headerv)
	SMFICTX *ctx;
	char *headerf;
	char *headerv;
{
	/* write the header to the log file */
	fprintf(MLFIPRIV->mlfi_fp, "%s: %s\r\n", headerf, headerv);

	/* continue processing */
	return SMFIS_CONTINUE;
}

sfsistat
mlfi_eoh(ctx)
	SMFICTX *ctx;
{
	/* output the blank line between the header and the body */
	fprintf(MLFIPRIV->mlfi_fp, "\r\n");

	/* continue processing */
	return SMFIS_CONTINUE;
}

sfsistat
mlfi_body(ctx, bodyp, bodylen)
	SMFICTX *ctx;
	u_char *bodyp;
	size_t bodylen;
{
	/* output body block to log file */
	if (fwrite(bodyp, bodylen, 1, MLFIPRIV->mlfi_fp) <= 0)
	{
#if defined(USE_SYSLOG)
		syslog(LOG_ERR, "Failed to write DumpFile: %s",MLFIPRIV->mlfi_fname);
#endif
		/* write failed */
		(void) mlfi_cleanup(ctx, false);
		return SMFIS_TEMPFAIL;
	}

	/* continue processing */
	return SMFIS_CONTINUE;
}

sfsistat
mlfi_eom(ctx)
	SMFICTX *ctx;
{
	return mlfi_cleanup(ctx, true);
}

sfsistat
mlfi_close(ctx)
	SMFICTX *ctx;
{
	return SMFIS_ACCEPT;
}

sfsistat
mlfi_abort(ctx)
	SMFICTX *ctx;
{
	return mlfi_cleanup(ctx, false);
}

sfsistat
mlfi_cleanup(ctx, ok)
	SMFICTX *ctx;
	bool ok;
{
	sfsistat rstat = SMFIS_CONTINUE;
	struct mlfiPriv *priv = MLFIPRIV;
	char *p; 
	char *newfilename;
	char host[512];
	char hbuf[1024];

	if (priv == NULL)
		return rstat;

	/* close the archive file */
	if (priv->mlfi_fp != NULL && fclose(priv->mlfi_fp) == EOF)
	{
		/* failed; we have to wait until later */
		rstat = SMFIS_TEMPFAIL;
		(void) unlink(priv->mlfi_fname);
#if defined(USE_SYSLOG)
		syslog(LOG_ERR, "Failed to close ArchiveFile: %s",priv->mlfi_fname);
#endif
	}
	else if (ok)
	{
		//Message processing OK.
		//now - first we must rename file
	        if ((newfilename = malloc (1024)) != NULL)
	          {
	          snprintf(newfilename, 1024, "%s.msg", priv->mlfi_fname);
	          (void) link(priv->mlfi_fname, newfilename);
	          (void) unlink(priv->mlfi_fname);
	          free (newfilename);
	          }
	        if (FLAG_ADDHEADER==1)	//if user want then we add our header
	    	  {  
	 	  /* add a header to the message announcing our presence */
		  if (gethostname(host, sizeof host) < 0)
			snprintf(host, sizeof host, "localhost");
		  p = strrchr(priv->mlfi_fname, '/');
		  if (p == NULL)
		    p = priv->mlfi_fname;
		  else
		    p++;
		  snprintf(hbuf, sizeof hbuf, "%s@%s by MailArchiver", p, host);
		  smfi_addheader(ctx, "X-Archived", hbuf);
		  }
#if defined(USE_SYSLOG)
		syslog(LOG_INFO, "MessageDumped: %s",priv->mlfi_fname);
#endif
	}
	else
	{
		/* message was aborted -- delete the archive file */
		(void) unlink(priv->mlfi_fname);
#if defined(USE_SYSLOG)
		syslog(LOG_INFO, "MessageAborted: %s",priv->mlfi_fname);
#endif
	}

	/* release private memory */
	free(priv->mlfi_fname);
	free(priv);
	smfi_setpriv(ctx, NULL);

	/* return status */
	return rstat;
}

struct smfiDesc smfilter =
{
	"MailArchiver. writted by Alexey",	/* filter name */
	SMFI_VERSION,	/* version code -- do not change */
	SMFIF_ADDHDRS,	/* flags */
	NULL,		/* connection info filter */
	NULL,		/* SMTP HELO command filter */
	mlfi_envfrom,	/* envelope sender filter */
	mlfi_envrcpt,	/* envelope recipient filter */
	mlfi_header,	/* header filter */
	mlfi_eoh,	/* end of header */
	mlfi_body,	/* body block filter */
	mlfi_eom,	/* end of message */
	mlfi_abort,	/* message aborted */
	mlfi_close	/* connection cleanup */
};

void program_printhelp()
{
    printf("option:");
    printf("-h          this help\r\n");
    printf("-o          specify directory for dump mail\r\n");
    printf("-p          specify connection file for mail server\r\n");
    printf("-i          interactive work, not going into background\r\n");
    printf("-t          add timestamp&sendername into filename\r\n");
}

int
main(argc, argv)
	int argc;
	char *argv[];
{
	int c;
	char str[10];
	const char *args = "p:o:i::h::t::";

	if (argc<2)
		{
		program_printhelp();
		exit(EX_USAGE);	//maybe incorrect errorcode
		}
	
	settings_daemonize=1;	//default settings
	settings_timestamp=0;	//default settings
	
	settings_outputdir=malloc(1024);
	if (settings_outputdir==NULL)
	{
		(void) fprintf(stderr, "Can't allocate memory for SETTINGS_OUTPUTDIR");
		exit(EX_USAGE);
	}
	strncpy(settings_outputdir,DEFAULT_DUMPMAILDIR, 1023);
	settings_pidfile=malloc(1024);
	if (settings_pidfile==NULL)
	{
		(void) fprintf(stderr, "Can't allocate memory for SETTINGS_PIDFILE");
		exit(EX_USAGE);
	}
	strncpy(settings_pidfile,DEFAULT_DUMPMAIL_LOCK, 1023);

	/* Process command line options */
	while ((c = getopt(argc, argv, args)) != -1)
	{
		switch (c)
		{
		    case 'p':
			if (optarg == NULL || *optarg == '\0')
			{
				(void) fprintf(stderr, "Illegal conn: %s\n",
					       optarg);
				exit(EX_USAGE);
			}
			(void) smfi_setconn(optarg);
			break;
		    case 'o':
			if (optarg == NULL || *optarg == '\0')
			{
				fprintf(stderr, "Illegal directory: %s\n", optarg);
				exit(EX_USAGE);
			}
			strncpy(settings_outputdir,optarg, 1023 );
			break;
		    case 'i':	//user want interactive work
			settings_daemonize=0;
			break;
		    case 't':	//user want add timestamp&sender name into filename
			settings_timestamp=1;
			break;
		    case 'h':
			program_printhelp();
			exit(EX_USAGE);	//maybe incorrect errorcode
		}
	}
	//daemonize program if user want...
	if (settings_daemonize==1)
		{
		//1stage - fork current process
		c=fork();
		if (c<0)
			{
			fprintf(stderr,"fork error");
			exit(EX_UNAVAILABLE);
			}
		if (c>0)
			exit(0);	//parent must exit...
		//now we are parent.
		//2stage - process independency
		if (setsid()<0)
			{
			fprintf(stderr,"setsid error");
			exit(EX_UNAVAILABLE);
			}
		//3stage - filedescriptors
		for (c=getdtablesize();c>=0;--c) close(c); /* close all descriptors */
		//open /dev/null as standart I/O
		c=open("/dev/null",O_RDWR); /* open stdin */
		dup(c); /* stdout */
		dup(c); /* stderr */
		//4stage - action with umask created filez
		umask(027);
		//5stage - prevent second copy
		file_lock=open(settings_pidfile,O_RDWR|O_CREAT,0640);
		if (file_lock<0) exit(1); /* can not open */
		if (lockf(file_lock,F_TLOCK,0)<0) exit(0); /* can not lock */
		/* only first instance continues */
		sprintf(str,"%d\n",getpid());
		write(file_lock,str,strlen(str)); /* record pid to lockfile */
		/*6stage - catch signal*/
		signal(SIGHUP,signal_handler);
		signal(SIGINT,signal_handler);
		signal(SIGQUIT,signal_handler);
		signal(SIGTERM,signal_handler);
		}
	else
	  printf("Interactive mode\r\n");

#if defined(USE_SYSLOG)
	openlog("MailArchiver",LOG_PID,LOG_DAEMON);	//register SYSLOG
	syslog(LOG_INFO, "Started");
#endif			
	if (smfi_register(smfilter) == MI_FAILURE)
	{
		if (settings_daemonize!=1)
			fprintf(stderr, "smfi_register failed\n");
#if defined(USE_SYSLOG)
		else
			syslog(LOG_INFO, "Smfi_register failed");
#endif
		exit(EX_UNAVAILABLE);
	}
	return smfi_main();
}
