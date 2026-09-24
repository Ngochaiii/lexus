const {chromium}=require('/Users/phamngochai/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const {pathToFileURL}=require('url');const path=require('path');
(async()=>{const b=await chromium.launch({channel:'chrome',headless:true});const p=await b.newPage();
for(const width of [375,768,1440]){
 await p.setViewportSize({width,height:960});await p.goto(pathToFileURL(path.resolve('index.html')).href);
 await p.evaluate(async()=>{for(const i of document.images)i.loading='eager';await Promise.all([...document.images].map(i=>i.decode().catch(()=>{})))});
 for(const [selector,name] of [['.advisor-section','profile'],['.personal-moments','moments'],['.advisor-ribbon','ribbon']])await p.locator(selector).screenshot({path:`/tmp/lexus-personal-${name}-${width}.png`});
 await p.goto(pathToFileURL(path.resolve('lien-he.html')).href);await p.locator('.lead-copy').screenshot({path:`/tmp/lexus-personal-contact-${width}.png`});
 await p.goto(pathToFileURL(path.resolve('showroom.html')).href);await p.locator('.contact-section').screenshot({path:`/tmp/lexus-personal-showroom-${width}.png`});
}
// Keyboard access and narrow viewport navigation.
await p.setViewportSize({width:375,height:812});await p.goto(pathToFileURL(path.resolve('index.html')).href);
await p.locator('.mobile-menu>summary').click();await p.locator('.mobile-menu nav a[href="index.html?from=menu#chuyen-vien"]').click();
console.log('Personal-brand link resolves:',p.url().endsWith('#chuyen-vien'));console.log('Mobile menu closed after navigation:',await p.locator('.mobile-menu').getAttribute('open')===null);
await p.goto(pathToFileURL(path.resolve('index.html')).href);await p.evaluate(()=>document.documentElement.style.fontSize='200%');console.log('200% root font overflow:',await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2));
await b.close();})().catch(e=>{console.error(e);process.exit(1)});
