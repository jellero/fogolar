import sys,os,numpy as np
from PIL import Image,ImageOps,ImageFilter
T=sys.argv[1];O=sys.argv[2]
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
