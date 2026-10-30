import sys,os,numpy as np
from PIL import Image,ImageOps,ImageFilter
T=sys.argv[1];O=sys.argv[2]
gif=Image.open(T+'/raw/fogolar.gif');fr=[]
for i in range(gif.n_frames):
    gif.seek(i);fr.append(np.asarray(gif.convert('RGB'),dtype=np.float32))
fr=np.stack(fr);std=fr.mean(axis=3).std(axis=0);med=np.median(fr,axis=0)
print('std max',std.max(),'frames',len(fr))
# maschera movimento
m=Image.fromarray(np.clip(std*5,0,255).astype('uint8')).filter(ImageFilter.GaussianBlur(22))
m=np.asarray(m,dtype=np.float32)/255;m=np.clip((m-.12)/.5,0,1);m=m*m*(3-2*m)
ys,xs=np.where(m>.03);print('bbox',xs.min(),xs.max(),ys.min(),ys.max())
x0,x1,y0,y1=int(xs.min()),int(xs.max()),int(ys.min()),int(ys.max())
open(T+'/bbox.txt','w').write(f'{x0},{y0},{x1},{y1}')
def clean(a): return Image.fromarray(a.clip(0,255).astype('uint8')).filter(ImageFilter.MedianFilter(3))
# piastra base: mediana + filigrana rimossa + fotogramma nitido nelle fiamme
base=fr[5].copy();M=m[...,None];base=med*(1-M)+base*M
base[1255:,1800:]=base[1225:1250,1800:].mean(axis=(0,1))
im=clean(base)
for w,q in((1920,74),(1100,68),(640,62)):
    im.resize((w,int(w*1280/1920)),Image.LANCZOS).save(f'{O}/public/assets/fire/base-{w}.webp',quality=q,method=6)
im.resize((1200,800),Image.LANCZOS).save(f'{O}/public/assets/fire/og.jpg',quality=80)
# atlas fiamme 4 fotogrammi
fs=[1,4,7,10];cw,ch=x1-x0,y1-y0;tw=620;th=int(tw*ch/cw)
at=Image.new('RGB',(tw*4,th))
for i,f in enumerate(fs):
    at.paste(clean(fr[f]).crop((x0,y0,x1,y1)).resize((tw,th),Image.LANCZOS),(i*tw,0))
at.save(O+'/public/assets/fire/atlas.webp',quality=72,method=6)
Image.fromarray((m[y0:y1,x0:x1]*255).astype('uint8')).resize((256,int(256*ch/cw)),Image.LANCZOS).convert('RGB').save(O+'/public/assets/fire/mask.webp',quality=80)
print('atlas',at.size)
# foto
R=T+'/raw/'
P={'esterno':'albergo.jpg','esterno-2':'Esterna-FOGOLAR-2.jpg','fogolar-sala':'ristorante1.jpg','sala':'ristorante2.jpg','bar':'ristorante3.jpg','sala-2':'ristorante4.jpg','tavoli':'ristorante.jpg','camera-rossa':'cam7.jpg','camera-1':'albergoletto2.jpg','camera-2':'albergoh.jpg','camera-3':'lettomatrimoniale.png','camera-4':'Albergo-4.jpg','camera-5':'Albergo-1.jpg','camera-6':'Albergo-2.jpg','bagno':'Albergo-3.jpg','app-1':'Appartamenti-1.jpg','app-2':'Appartamenti-7.jpg','app-3':'Appartamenti-5.jpg','app-4':'Appartamenti-6.jpg','app-5':'Appartamenti-9.jpg','app-6':'Appartamenti-4.jpg','app-7':'Appartamenti-2.jpg'}
tot=0
for k,f in P.items():
    im=ImageOps.exif_transpose(Image.open(R+f)).convert('RGB')
    for w in(1600,800,480):
        if im.width<w and w!=480 and w!=800: continue
        r=im if im.width<=w else im.resize((w,round(im.height*w/im.width)),Image.LANCZOS)
        p=f'{O}/public/assets/img/{k}-{w}.webp';r.save(p,quality=72,method=6);tot+=os.path.getsize(p)
print('img KB',tot//1024)
l=Image.open(R+'icons/logo.png' if os.path.exists(R+'icons/logo.png') else R+'logo.png').convert('RGBA');print(l.size,l.getextrema())
l.resize((600,round(600*l.height/l.width)),Image.LANCZOS).save(O+'/public/assets/img/logo.webp',quality=90,method=6)
