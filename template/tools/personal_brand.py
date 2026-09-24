"""Reusable personal-brand sections. Works on current HTML without rebuilding it."""
from pathlib import Path
import re
from html import escape
ROOT=Path(__file__).resolve().parent.parent
# Set the public name once confirmed. Never derive identity from a photograph.
DISPLAY_NAME='Thu Hà'
ROLE='Chuyên viên tư vấn · Lexus Thăng Long'

def photo(key,alt,cls='',sizes='(max-width: 600px) 100vw, 50vw'):
 widths=[320,640,960] if key=='portrait' else [640,1200]
 return f'<img class="{cls}" src="assets/personal/{key}-{widths[-1]}.webp" srcset="'+', '.join(f'assets/personal/{key}-{w}.webp {w}w' for w in widths)+f'" sizes="{sizes}" width="{960 if key=="portrait" else 1200}" height="{960 if key=="portrait" else {"welcome":2191,"handover":900,"celebration":900,"delivery":1609}[key]}" alt="{alt}" loading="lazy" decoding="async">'

def identity():
 return f'<strong>{escape(DISPLAY_NAME or ROLE)}</strong><span>{ROLE if DISPLAY_NAME else "Lắng nghe nhu cầu. Đồng hành lựa chọn."}</span>'

def ribbon():
 return f'''<!-- personal:ribbon --><aside class="advisor-ribbon"><div class="container advisor-ribbon-inner"><a class="advisor-identity" href="#chuyen-vien">{photo('portrait','Chân dung Thu Hà, chuyên viên tư vấn Lexus Thăng Long','advisor-avatar','56px')}<span>{identity()}</span></a><p>Một người đồng hành cho hành trình Lexus của bạn.</p><a class="text-link" href="#chuyen-vien">Gặp người tư vấn</a></div></aside><!-- /personal:ribbon -->'''

def profile():
 return f'''<!-- personal:profile --><section class="advisor-section stone" id="chuyen-vien" aria-labelledby="advisor-title"><div class="container advisor-layout"><div class="advisor-copy"><p class="eyebrow">NGƯỜI ĐỒNG HÀNH CỦA BẠN</p><h2 id="advisor-title">Chọn một chiếc xe.<br>Gặp một người<br> <span>thấu hiểu.</span></h2><div class="advisor-name">{identity()}</div><p class="advisor-intro">Một chiếc Lexus phù hợp bắt đầu từ việc hiểu điều bạn cần. Tôi ở đây để cùng bạn tìm hiểu từng lựa chọn, chuẩn bị buổi lái thử và chăm chút cho khoảnh khắc nhận xe.</p><div class="advisor-promises"><div><span>01</span><p>Lắng nghe nhu cầu sử dụng của bạn</p></div><div><span>02</span><p>Cùng tìm hiểu phiên bản & chi phí</p></div><div><span>03</span><p>Đồng hành từ lái thử đến nhận xe</p></div></div><div class="actions"><a class="button" href="lien-he.html">Trao đổi cùng tôi</a><a class="text-link" href="#khoanh-khac">Những lần đồng hành</a></div></div><figure class="advisor-portrait">{photo('portrait','Chân dung Thu Hà trong không gian Lexus Thăng Long')}<figcaption><span>PERSONAL CONSULTATION</span><p>Sự tận tâm bắt đầu<br>từ một cuộc trò chuyện.</p></figcaption></figure></div></section><!-- /personal:profile -->'''

def moments():
 return f'''<!-- personal:moments --><section class="container section personal-moments" id="khoanh-khac" aria-labelledby="moments-title"><div class="section-heading"><div><p class="eyebrow">NHỮNG KHOẢNH KHẮC ĐỒNG HÀNH</p><h2 id="moments-title">Niềm vui ngày nhận xe.<br>Dấu ấn của một hành trình.</h2></div><p>Từ cuộc gặp gỡ đầu tiên đến khoảnh khắc bàn giao. Những hình ảnh lưu lại sự kết nối giữa người tư vấn, khách hàng và chiếc xe được lựa chọn.</p></div><div class="moments-grid"><figure class="moment moment-featured">{photo('handover','Chuyên viên và khách hàng cùng cầm hộp bàn giao trước xe Lexus',sizes='(max-width: 600px) calc(100vw - 40px), (max-width: 1000px) 90vw, 46vw')}<figcaption><span class="moment-index">01 / NGÀY BÀN GIAO</span><h3>Một khởi đầu đáng nhớ.</h3></figcaption></figure><figure class="moment">{photo('celebration','Khoảnh khắc cùng khách hàng bên xe Lexus và hoa tại showroom',sizes='(max-width: 600px) calc(100vw - 40px), 30vw')}<figcaption><span class="moment-index">02 / NIỀM VUI GẶP GỠ</span><h3>Cùng lưu lại niềm vui.</h3></figcaption></figure><figure class="moment">{photo('delivery','Chuyên viên và khách hàng cầm hoa bên chiếc Lexus màu trắng',sizes='(max-width: 600px) calc(100vw - 40px), 30vw')}<figcaption><span class="moment-index">03 / HÀNH TRÌNH MỚI</span><h3>Sẵn sàng cho chặng đường mới.</h3></figcaption></figure></div><div class="moments-invite"><p>Chiếc Lexus tiếp theo sẽ kể câu chuyện của bạn.</p><a class="text-link" href="lai-thu.html">Hẹn một buổi lái thử</a></div></section><!-- /personal:moments -->'''

def mini():
 return f'''<!-- personal:mini --><a class="advisor-mini" href="index.html#chuyen-vien">{photo('portrait','Chân dung Thu Hà, chuyên viên tư vấn Lexus Thăng Long','advisor-avatar','76px')}<span><span class="eyebrow">NGƯỜI TƯ VẤN CỦA BẠN</span>{identity()}<span class="advisor-mini-link">Tìm hiểu người đồng hành ↗</span></span></a><!-- /personal:mini -->'''

def welcome():
 return '<figure class="advisor-welcome">'+photo('welcome','Chuyên viên tư vấn đón tiếp tại khu vực lễ tân Lexus',sizes='(max-width: 600px) calc(100vw - 40px), 40vw')+'<figcaption>Hân hạnh được đón tiếp bạn tại buổi hẹn riêng.</figcaption></figure>'

# Small span scanner preserves user formatting and unrelated edits.
def section_ranges(s):
 stack=[];out=[]
 for m in re.finditer(r'<(/?)section\b[^>]*>',s,re.I):
  if not m.group(1):stack.append(m.start())
  elif stack:
   start=stack.pop();out.append((start,m.end(),s[start:m.end()]))
 return out

def update(file,s):
 if '<!-- personal:profile -->' in s or '<!-- personal:mini -->' in s:return s
 # Shared identity entry points, without expanding the desktop menu width.
 s=re.sub(r'(<nav\b[^>]*class="desktop-nav"[\s\S]*?)(<a\s+href="tin-tuc.html">[\s\S]*?</a>)',lambda m:m[1]+'<a href="index.html#chuyen-vien">Người đồng hành</a>',s,count=1)
 s=re.sub(r'(<nav\s+aria-label="Điều hướng di động">)',r'\1<a href="index.html?from=menu#chuyen-vien">Người đồng hành</a>',s,count=1)
 s=re.sub(r'(<h3>Kết nối</h3>)',r'\1<a href="index.html#chuyen-vien">Người tư vấn của bạn</a>',s,count=1)
 # Old demo identity should not be attributed to the person in the supplied photos.
 s=re.sub(r' &nbsp; — &nbsp;\s*Minh Anh','',s)
 if file=='index.html':
  s=re.sub(r'<div class="container intro-line">[\s\S]*?</div>',lambda _:ribbon(),s,count=1)
  for a,b,block in section_ranges(s):
   if 'id="collection"' in block:
    s=s[:b]+'\n'+profile()+s[b:];break
  for a,b,block in section_ranges(s):
   if 'class="contact-section"' in block:
    s=s[:a]+moments()+s[b:];break
 if file in ['lien-he.html','bao-gia.html','lai-thu.html']:
  s=s.replace('<div class="lead-copy">','<div class="lead-copy">'+mini(),1)
 if file=='showroom.html':
  s=s.replace('<div class="contact-section"><div>','<div class="contact-section"><div>'+mini(),1)
  s=re.sub(r'<img\b[^>]*src="assets/interior.webp"[^>]*>',lambda _:welcome(),s,count=1)
 return s

if __name__=='__main__':
 for p in ROOT.glob('*.html'):
  old=p.read_text();new=update(p.name,old)
  if new!=old:p.write_text(new)
 print('Personal brand sections applied.')
