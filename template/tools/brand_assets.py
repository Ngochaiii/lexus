"""Authoring-only: derive white logo + favicons from assets/logo-ltl-black.png. Pure Python (zlib), no PIL."""
from pathlib import Path
import struct, zlib
ROOT=Path(__file__).resolve().parent.parent
A=ROOT/'assets'

def read_png(p):
 d=p.read_bytes(); assert d[:8]==b'\x89PNG\r\n\x1a\n'
 pos=8; idat=b''
 while pos<len(d):
  n,=struct.unpack('>I',d[pos:pos+4]); t=d[pos+4:pos+8]; c=d[pos+8:pos+8+n]; pos+=12+n
  if t==b'IHDR': w,h,bd,ct,_,_,il=struct.unpack('>IIBBBBB',c)
  elif t==b'IDAT': idat+=c
 assert bd==8 and il==0, (bd,il)
 ch={0:1,2:3,4:2,6:4}[ct]; raw=zlib.decompress(idat); stride=w*ch; out=[]; prev=bytearray(stride); i=0
 for _ in range(h):
  f=raw[i]; line=bytearray(raw[i+1:i+1+stride]); i+=1+stride
  for x in range(stride):
   a=line[x-ch] if x>=ch else 0; b=prev[x]; c=prev[x-ch] if x>=ch else 0
   if f==1: line[x]=(line[x]+a)&255
   elif f==2: line[x]=(line[x]+b)&255
   elif f==3: line[x]=(line[x]+(a+b)//2)&255
   elif f==4:
    pa=abs(b-c); pb=abs(a-c); pc=abs(a+b-2*c)
    line[x]=(line[x]+(a if pa<=pb and pa<=pc else b if pb<=pc else c))&255
  out.append(bytes(line)); prev=line
 # normalise to RGBA rows
 rows=[]
 for line in out:
  px=bytearray()
  for j in range(w):
   s=line[j*ch:(j+1)*ch]
   if ct==6: px+=s
   elif ct==2: px+=s+b'\xff'
   elif ct==4: px+=bytes([s[0],s[0],s[0],s[1]])
   else: px+=bytes([s[0],s[0],s[0],255])
  rows.append(px)
 return w,h,rows

def write_png(p,w,h,rows):
 def chunk(t,c): return struct.pack('>I',len(c))+t+c+struct.pack('>I',zlib.crc32(t+c)&0xffffffff)
 raw=b''.join(b'\x00'+bytes(r) for r in rows)
 p.write_bytes(b'\x89PNG\r\n\x1a\n'+chunk(b'IHDR',struct.pack('>IIBBBBB',w,h,8,6,0,0,0))+chunk(b'IDAT',zlib.compress(raw,9))+chunk(b'IEND',b''))

w,h,rows=read_png(A/'logo-ltl-black.png')
# 1) White logo: keep alpha, paint RGB white.
white=[bytearray(r) for r in rows]
for r in white:
 for j in range(w): r[j*4]=r[j*4+1]=r[j*4+2]=255
write_png(A/'logo-ltl-white.png',w,h,white)

# 2) Favicon: crop the emblem (left block up to the first fully transparent column gap), centre it white on ink.
alpha_cols=[any(rows[y][x*4+3]>16 for y in range(h)) for x in range(w)]
x0=next(x for x in range(w) if alpha_cols[x]); x1=x0
while x1<w and alpha_cols[x1]: x1+=1
alpha_rows=[any(rows[y][x*4+3]>16 for x in range(x0,x1)) for y in range(h)]
y0=alpha_rows.index(True); y1=h-alpha_rows[::-1].index(True)
ew,eh=x1-x0,y1-y0
S=512; ink=(21,22,23); scale=S*0.66/max(ew,eh)
tw,th=int(ew*scale),int(eh*scale); ox,oy=(S-tw)//2,(S-th)//2
def sample(sx,sy):  # box-filter alpha from source
 X0=int(sx);X1=max(X0+1,int(sx+1/scale));Y0=int(sy);Y1=max(Y0+1,int(sy+1/scale)); t=n=0
 for yy in range(Y0,min(Y1,eh)):
  for xx in range(X0,min(X1,ew)): t+=rows[y0+yy][(x0+xx)*4+3]; n+=1
 return t/n if n else 0
fav=[]
for y in range(S):
 r=bytearray()
 for x in range(S):
  a=0
  if ox<=x<ox+tw and oy<=y<oy+th: a=sample((x-ox)/scale,(y-oy)/scale)/255
  r+=bytes([int(ink[0]+(255-ink[0])*a),int(ink[1]+(255-ink[1])*a),int(ink[2]+(255-ink[2])*a),255])
 fav.append(r)
write_png(A/'favicon-512.png',S,S,fav)
print('emblem crop',x0,y0,x1,y1,'->',ew,eh)
