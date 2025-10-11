/*
Использование:
mail-copier -d имя_каталога -f имя_файла

имя_каталога - каталог, в котором происходит поиск msg. - файлов, созданных
               libmilter-фильтром "Sample" для sendmail
имя_файла - почтовый ящик, куда будет осуществляться вывод

*/
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <dirent.h>
#include <sys/types.h>

int main(int argc, char *argv[])
  {
  int c;
  const char *args = "f:d:";
  char *collector_fname = NULL, *directory_name = NULL;
/* Process command line options */
  while ((c = getopt(argc, argv, args)) != -1)
    {
    switch (c)
      {
      case 'f':
        {
	if (optarg == NULL || *optarg == '\0')
          {
          fprintf(stderr, "Illegal destination file: %s\n", optarg);
          return 6;
          }
	collector_fname = (char *)malloc(strlen(optarg) + 1);  
        strcpy(collector_fname, optarg);
	break;
	}
      case 'd':
        {
        if (optarg == NULL || *optarg == '\0')
          {
          fprintf(stderr, "Illegal directory: %s\n", optarg);
          return 7;
          }
	directory_name = (char *)malloc(strlen(optarg) + 16);  
	if(optarg[strlen(optarg) - 1] == '/')
	  optarg[strlen(optarg) - 1] = '\0';
//        sprintf(directory_name, "%s/msg.*", optarg);
        sprintf(directory_name, "%s", optarg);
//	directory_name[strlen(directory_name)] = '*';
//	directory_name[strlen(directory_name)] = '\0';
	break;
	}
      default:
        {
        fprintf(stderr, "Illegal argument: %c\n", c);
        return 8;
	break;
	}
      }
    }

  if(!collector_fname || !directory_name)
    {
    printf("Использование:\n");
    printf("mail-copier -d имя_каталога -f имя_файла\n");
    printf("имя_каталога - каталог, в котором происходит поиск msg. - файлов, созданных\n");
    printf("               libmilter-фильтром \"Sample\" для sendmail\n");
    printf("имя_файла - почтовый ящик, куда будет осуществляться вывод\n");
    }
    
  if(!collector_fname)
    {
    fprintf(stderr, "Can't alloc memory for destination file name\nor destination not defined\n");
    return 6;
    }
  if(!directory_name)
    {
    fprintf(stderr, "Can't alloc memory\nor work directory not defined\n");
    return 7;
    }

  FILE *collector = fopen(collector_fname, "at");
  if(!collector)
    {
    fprintf(stderr, "Can't open file: %s\n", collector_fname);
    return 1; 
    }
  char *filelist_buf = (char *)malloc(32768);
  if(!filelist_buf)
    {
    fprintf(stderr, "Can't allocate memory for filename list\n");
    return 2; 
    }
    
  printf("Working...\n");
//    printf("%s\n", directory_name);
//    printf("%s\n", collector_fname);
  DIR *dir = opendir(directory_name);
  struct dirent *dent;
//    printf("Stage 1\n");
  while((dent = readdir(dir)) != NULL)
    {
    while(dent && memcmp(dent->d_name, "msg.", 4)) 
	{
	dent = readdir(dir);
	}
    if(!dent) break;
    
    sprintf(filelist_buf, "%s/%s", directory_name, dent->d_name);

//    for(int i = 0; i < strlen(filelist_buf) + 2; i++)
//      if(filelist_buf[i] == '\r' || filelist_buf[i] == '\n') filelist_buf[i] = '\0';

//Debug
    printf("%s\n", filelist_buf);

    FILE *msg = fopen(filelist_buf, "rt");
    if(!msg)
      {
      fprintf(stderr, "Can't open message file: %s\n", filelist_buf);
      return 3; 
      }
    int from_flag = 0, date_flag = 0, data_flag = 0;
    if(msg)
      {
      char *msg_buf = (char *)malloc(32768);
      if(!msg_buf)
        {
        fprintf(stderr, "Can't allocate memory for message\n");
        return 4; 
        }
	
      while(!feof(msg))
        {
	fgets(msg_buf, 32768, msg);
	for(int i = 0; i < strlen(msg_buf) + 2; i++)
	  if(msg_buf[i] == '\r' || msg_buf[i] == '\n') msg_buf[i] = '\0';
// Выделение строки отправителя "From:" и запись её в начало файла
        if(!memcmp("From:", msg_buf, 5))
	  {
	  fputs("From ", collector);
	  fputs(&msg_buf[6], collector);
	  fputs("  ", collector);
          from_flag =1;
	  break;
	  }
        }

      if(!from_flag)	
        {
        fprintf(stderr, "File '%s' isn't a message file\n", filelist_buf);
        fclose(msg);
	free(msg_buf);
        }
      else
        {
//Перемотка потока назад
        fseek(msg, 0, SEEK_SET);
      
        while(!feof(msg))
          {
          fgets(msg_buf, 32768, msg);
          for(int i = 0; i < strlen(msg_buf) + 2; i++)
	    if(msg_buf[i] == '\r' || msg_buf[i] == '\r') msg_buf[i] = '\0';
// Выделение строки отправителя "Date:" и запись её в файл
          if(!memcmp("Date:", msg_buf, 5))
	    {
	    char week[16], day[16], mon[16], year[16], time[16], offset[16];
	    sscanf(&msg_buf[6], "%3s, %2s %3s %4s %8s %5s", week, day, mon, year, time, offset);
	    sprintf(msg_buf, "%3s %3s %2s %8s %4s", week, mon, day, time, year);
	    fputs(msg_buf, collector);
	    fputs("\n", collector);
	    date_flag = 1;
	    break;
	    }
          }
//Перемотка потока назад
        fseek(msg, 0, SEEK_SET);

//запись сообщения в почтовый ящик
        while(!feof(msg))
          {
          fgets(msg_buf, 32768, msg);
	  for(int i = 0; i < strlen(msg_buf) + 2; i++)
	    if(msg_buf[i] == 0x0D || msg_buf[i] == 0x0A) msg_buf[i] = '\0';
	  fputs(msg_buf, collector);
	  fputs("\n", collector);
	  data_flag = 1;
          }
//разделитель между сообщениями
        fputs("\n", collector);
      
        free(msg_buf);	
        fclose(msg);
        unlink(filelist_buf);
        }	
      }  
    }

  closedir(dir);
  fclose(collector);
  free(filelist_buf);
  free(collector_fname);
  printf("done.\n");
  return 0;
  }

