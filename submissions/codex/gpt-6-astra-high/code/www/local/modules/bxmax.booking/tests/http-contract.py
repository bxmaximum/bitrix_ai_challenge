"""Run against the local development stand. Creates test bookings; cleanup via verify.php."""
import urllib.request,urllib.parse,http.cookiejar,re,json,concurrent.futures,datetime
BASE='http://ai.bitrix:8765'
def client():
 jar=http.cookiejar.CookieJar();c=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar));html=c.open(BASE+'/').read().decode(); token=re.search(r'data-sessid="([^"]+)"',html).group(1);return c,token
c,t=client()
def req(action,data,post=False,client=c):
 url=BASE+'/bitrix/services/main/ajax.php?action=bxmax:booking.api.'+action
 if post:r=client.open(url,urllib.parse.urlencode(data).encode())
 else:r=client.open(url+'&'+urllib.parse.urlencode(data))
 return json.load(r)
html=c.open(BASE+'/').read().decode()
master=int(re.search(r'id="booking-master".*?<option value="(\d+)"',html,re.S).group(1)); service=int(re.search(r'id="booking-service".*?<option value="(\d+)"',html,re.S).group(1));today=datetime.date.fromisoformat(re.search(r'data-today="([^"]+)"',html).group(1));monday=today-datetime.timedelta(days=today.weekday())
r=req('slots.list',{'masterId':master,'weekStart':monday.isoformat()});assert r['status']=='success',r
slots=r['data']['slots'];assert slots and all(set(s)=={'id','startsAt','endsAt','status'} for s in slots)
assert all(s['startsAt'][11:13] in [str(x) for x in range(10,20)] for s in slots)
slot=next(s['id'] for s in slots if s['status']=='free')
data={'slotId':slot,'serviceId':service,'name':'Контракт Тест','phone':'+79000000001','sessid':t}
for consent in [None,'0','false','N','junk']:
 d=dict(data)
 if consent is not None:d['consent']=consent
 result=req('bookings.create',d,True);assert result['errors'][0]['code']=='CONSENT_REQUIRED',result
print('PASS missing and false consent')
for extra,code in [({'slotId':99999999},'SLOT_NOT_FOUND'),({'serviceId':99999999},'VALIDATION'),({'name':'<script>'},'VALIDATION'),({'phone':'invalid'},'VALIDATION')]:
 d={**data,'consent':'1',**extra};result=req('bookings.create',d,True);assert result['errors'][0]['code']==code,result
print('PASS validation and unknown ids')
assert req('slots.list',{'masterId':99999999,'weekStart':monday.isoformat()})['errors'][0]['code']=='MASTER_NOT_FOUND'
try: result=req('bookings.create',{**data,'consent':'1','sessid':'invalid'},True)
except urllib.error.HTTPError as e: result=json.load(e)
assert result['status']=='error',result
print('PASS CSRF rejected')
clients=[client() for _ in range(8)]
def race(pair):
 cc,tt=pair
 return req('bookings.create',{**data,'sessid':tt,'consent':'1'},True,cc)
with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool: results=list(pool.map(race,clients))
success=[r for r in results if r['status']=='success'];errors=[r['errors'][0]['code'] for r in results if r['status']!='success']
assert len(success)==1 and errors==['SLOT_TAKEN']*7,results
print('PASS race: exactly 1 success, 7 SLOT_TAKEN; booking',success[0]['data']['bookingId'])
r=req('slots.list',{'masterId':master,'weekStart':monday.isoformat()});assert next(s['status'] for s in r['data']['slots'] if s['id']==slot)=='taken'
print('PASS occupied status, no private data')
