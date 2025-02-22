/*mars (Polyansky Sergei) version 3*/
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

typedef unsigned char byte;
typedef unsigned short int word;
typedef unsigned long dword;
typedef struct {int n; int *x; double *y,*b,miny,maxy;} Tfunc;
typedef struct {int m; Tfunc **f;} Tlistfunc;

/******************************************************************************/
Tlistfunc *get_data(char *FileName)
{FILE *f=fopen(FileName,"r");
 if (!f) return 0;
 char numbs[15]="-0123456789.+eE";
 char s,snum[15]="";
 int mny=16, x_step=200, y_step=8,x=0,p=0,ibeg=0,nf,pbeg=0,sln=0,pt,n,nm; double d;
 byte innum=0, conti=1, btmp;
 int i=mny*sizeof(int);
 int *pbegs=(int *)malloc(i);
 int *memx=(int *)malloc(i);
 Tlistfunc *arr=(Tlistfunc *)malloc(sizeof(Tlistfunc));
 (*arr).f=(Tfunc **)malloc(mny*sizeof(Tfunc *));
 (*arr).m=0;
 while (conti)
   {s=fgetc(f);
    if (s<0) conti=0;
    nf=(*arr).m;
    if (strchr(numbs,s))
      {if (innum) {snum[sln]=s; sln++;}
       else
		 {innum=1;
		  byte ii;
	      for (ii=0; ii<20; ii++) snum[ii]=0;
		  snum[0]=s; sln=1; pbeg=p;
	     }
       p++;
      }
    else
      {if (innum)
         {innum=0; d=atof(snum);
          do {if (ibeg<nf) pt=*(pbegs+ibeg); else pt=-8; btmp=abs(pbeg-pt)<2;}
          while ((!btmp) && (pbeg>=pt) && (((++ibeg)<(*arr).m)));
          if (btmp)  /*add point to function*/
            {n=++((**((*arr).f+ibeg)).n);
             if (n>(*(memx+ibeg)))
               {nm=((*(memx+ibeg))+=x_step);
                (**((*arr).f+ibeg)).x = (int *)realloc((**((*arr).f+ibeg)).x,nm*sizeof(int));
                (**((*arr).f+ibeg)).y = (double *)realloc((**((*arr).f+ibeg)).y,nm*sizeof(double));
               }
             *((**((*arr).f+ibeg)).x+n-1)=x;
             *((**((*arr).f+ibeg)).y+n-1)=d;
             ibeg++;
            }
          else
            {if ((pbeg<pt)&&(pt>0)) /*insert new function between 2 old functions*/
               {if (++(*arr).m >= mny)
                  {mny+=y_step;
                   (*arr).f=(Tfunc **)realloc((*arr).f,mny*sizeof(Tfunc *));
                   i=mny*sizeof(int);
                   memx=(int *)realloc(memx,i);
                   pbegs=(int *)realloc(pbegs,i);
                  }
                for (i=(*arr).m-1; i>ibeg; i--)
                  {((*arr).f)[i]=((*arr).f)[i-1];
                   *(memx+i)=*(memx+i-1);
                   *(pbegs+i)=*(pbegs+i-1);
                  };
                *(memx+ibeg)=x_step;
                *((*arr).f+ibeg)=(Tfunc *)malloc(sizeof(Tfunc));
                (**((*arr).f+ibeg)).n=1;
                (**((*arr).f+ibeg)).x=(int *)malloc(x_step*sizeof(int));
                (**((*arr).f+ibeg)).y=(double *)malloc(x_step*sizeof(double));
                *((**((*arr).f+ibeg)).x)=x;
                *((**((*arr).f+ibeg)).y)=d;
                *(pbegs+ibeg)=pbeg;
                ibeg++;
               }
             else  /*new function*/
               {ibeg=(*arr).m;
                if ((++((*arr).m))>=mny)
                  {mny+=y_step;
                   (*arr).f=(Tfunc **)realloc((*arr).f,mny*sizeof(Tfunc *));
                   i=mny*sizeof(int);
                   memx=(int *)realloc(memx,i);
                   pbegs=(int *)realloc(pbegs,i);
                  }
                *(memx+ibeg)=x_step;
                *((*arr).f+ibeg)=(Tfunc *)malloc(sizeof(Tfunc));
                (**((*arr).f+ibeg)).n=1;
                (**((*arr).f+ibeg)).x=(int *)malloc(x_step*sizeof(int));
                (**((*arr).f+ibeg)).y=(double *)malloc(x_step*sizeof(double));
                *((**((*arr).f+ibeg)).x)=x;
                *((**((*arr).f+ibeg)).y)=d;
                *(pbegs+ibeg)=pbeg;
                ibeg++;
               }
            }
         }
       if (s==9) do p++; while (p % 8);
       else if (s==10) {x++; p=0; ibeg=0;}
       else p++;
      }
   }  /*end of while ...*/
 fclose(f);
 free(pbegs); free(memx);
 for (i=0; i<(*arr).m; i++) {Tfunc *fd=(*((*arr).f+i)); (*fd).b=NULL;}
 return arr;
};

/******************************************************************************/
void free_data(Tlistfunc *arr)
{void *p;
 int i;
 for (i=0; i<(*arr).m; i++)
   {Tfunc *fd=*((*arr).f+i);
    free((void *)(*fd).b);
    free((void *)(*fd).y);
    free((void *)(*fd).x);
    free((void *)(*((*arr).f+i)));
   }
 return;
};

/******************************************************************************/
void approcsi(Tfunc *fn)
{int n=(*fn).n;
 int i=n*sizeof(double);
 (*fn).b=(double *)malloc(i);
 for (i=0; i<=(n-2); i++) *((*fn).b+i)=((*((*fn).y+i+1))-(*((*fn).y+i)))/(((*((*fn).x+i+1))-(*((*fn).x+i))));
   /*find extremums*/
 double miny=*((*fn).y), maxy=miny, y;
 for (i=1; i<n; i++) {y=*((*fn).y+i); if (y<miny) miny=y; else if (y>maxy) maxy=y;}
 (*fn).miny=miny; (*fn).maxy=maxy;
};

/******************************************************************************/
void create_graph(Tlistfunc *fn, int pw, int ph)
{  /*find max x*/
 dword i;
 dword ind=(**((*fn).f)).n-1;
 dword maxn=*((**((*fn).f)).x+ind), fn_count=(*fn).m, k;
 for (i=0; i<fn_count; i++)
   {ind=(**((*fn).f+i)).n-1;
	k=*((**((*fn).f+i)).x+ind);
    if (k>maxn) maxn=k;
   }

   /*find global extremums*/
 double gmin=(**((*fn).f)).miny, gmax=(**((*fn).f)).maxy, y;
 for (i=1; i<(*fn).m; i++)
   {y=(**((*fn).f+i)).miny; if (y<gmin) gmin=y;
    y=(**((*fn).f+i)).maxy; if (y>gmax) gmax=y;
   }
 double ampl=gmax-gmin;

 dword scan_line_size=pw;
 while (scan_line_size&0x3) scan_line_size++;
 dword scans_size=ph*scan_line_size;
 byte *matr=(byte *)malloc(scans_size);
 for (i=0; i<scans_size; i++) *(matr+i)=0;

   /*calcs functions in all points*/
 double m=((pw-1)/(double)maxn), x,x1,y0,px2;
 double my=(ph-1)/(double)ampl;
 word py,mny,mxy,xind,tpy,clnum;
 for (i=0; i<fn_count; i++)
   {clnum=i+1;
	word j=(0.5+m*(*((**((*fn).f+i)).x)));
    y0=*((**((*fn).f+i)).y);
    word predpy=(0.5+(y0-gmin)*my);
    for (xind=0; xind<=((**((*fn).f+i)).n-2); xind++)
      {px2=m*(*((**((*fn).f+i)).x+xind+1));
       y0=*((**((*fn).f+i)).y+xind);
	   x1=(*((**((*fn).f+i)).x+xind));
	   char first=0;
       while (j<=px2)
         {if (first)
		    {x=j/m-x1;
	         if (x>0) y=y0+(*((**((*fn).f+i)).b+xind))*x; else y=y0;
		     py=(0.5+(y-gmin)*my);
			}
		  else {py=(0.5+(y0-gmin)*my); first=1;}
          if (abs(predpy-py)>1)
            {if (predpy>py) {mny=py; mxy=predpy-1;}
             else {mxy=py; mny=predpy+1;}
             for (tpy=mny; tpy<=mxy; tpy++) (*(matr+tpy*scan_line_size+j))=clnum;
		    }
          else *(matr+py*scan_line_size+j)=clnum;
		  predpy=py;
          j++;
         }

       py=(0.5+((*((**((*fn).f+i)).y+xind+1))-gmin)*my);
	   if (abs(predpy-py)>1)
	     {if (predpy>py) {mny=py; mxy=predpy-1;} else {mxy=py; mny=predpy+1;}
	      for (tpy=mny; tpy<=mxy; tpy++) (*(matr+tpy*scan_line_size+j-1))=clnum;
	      predpy=py;
		 }
	  }
   }

 FILE *fbmp=fopen("mars.bmp","wb");
 word *buf=(word *)malloc(40);
 *buf=0x4D42;
 dword ofsd=54+((fn_count+1)<<2);
 *((dword *)(buf+1))=ofsd+scans_size;
 *((dword *)(buf+3))=0;
 *((dword *)(buf+5))=ofsd;
 fwrite((char *)buf,14,1,fbmp);

 *((dword *)buf)=40;
 *((dword *)(buf+2))=pw;
 *((dword *)(buf+4))=ph;
 *(buf+6)=1;
 *(buf+7)=8;
 *((dword *)(buf+8))=0;
 *((dword *)(buf+10))=scans_size;
 *((dword *)(buf+12))=0;
 *((dword *)(buf+14))=0;
 *((dword *)(buf+16))=0;
 *((dword *)(buf+18))=0;
 fwrite((char *)buf,40,1,fbmp);

 *((dword *)buf)=0x00FFFFFF;
 fwrite((char *)buf,4,1,fbmp); /*background color*/
 dword deltacol,col;
 if (fn_count<250) deltacol=0x00EEEEEE/fn_count; else deltacol=0x00000044;
 col=0;
 for (i=0; i<fn_count; i++) {*((dword *)buf)=col; fwrite((char *)buf,4,1,fbmp); col+=deltacol;};
 fwrite((byte *)matr,scans_size,1,fbmp);
 fclose(fbmp);
 free(buf);
 free(matr);
}

/******************************************************************************/
int main(int argc, char *argv[])
{  /* get params from command line */
 if (argc<4) return 1;
 int gwidth=atoi(argv[1]);
 int gheight=atoi(argv[2]);
 char *FileName = argv[3];
 int i;
 Tlistfunc *fn=get_data(FileName); if (fn==0) return 2;
 for (i=0; i<((*fn).m); i++) approcsi(*((*fn).f+i));
 create_graph(fn,gwidth,gheight);
 free_data(fn);
 return 0;
}
