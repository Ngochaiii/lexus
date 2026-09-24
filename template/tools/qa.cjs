const { chromium } = require('/Users/phamngochai/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs = require('fs'); const path = require('path'); const {pathToFileURL}=require('url');
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});const page=await browser.newPage();const root=path.resolve(__dirname,'..');let issues=[];
 for(const width of [375,768,1024,1440,1920]){
  await page.setViewportSize({width,height:960});
  for(const file of fs.readdirSync(root).filter(f=>f.endsWith('.html'))){
   await page.goto(pathToFileURL(path.join(root,file)).href);await page.evaluate(async()=>{for(const i of document.images){i.loading='eager'}await Promise.all([...document.images].map(i=>i.decode().catch(()=>{})))});
   const result=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth+2,broken:[...document.images].filter(i=>!i.naturalWidth).map(i=>i.getAttribute('src'))}));
   if(result.overflow||result.broken.length)issues.push({file,width,...result});
   if(['index.html','rx.html'].includes(file)&&[375,1440].includes(width))await page.screenshot({path:`/tmp/lexus-${file.replace('.html','')}-${width}.png`,fullPage:true});
  }
 }
 await page.setViewportSize({width:1440,height:960}); await page.goto(pathToFileURL(path.join(root,'models.html')).href);await page.locator('label[for="sedan"]').click();if(await page.locator('.model-card:visible').count()!==1)issues.push('sedan filter');
 await page.goto(pathToFileURL(path.join(root,'rx.html')).href);await page.locator('label[for="copper"]').click();if(await page.locator('#copper').isChecked()!==true)issues.push('color control');
 await page.goto(pathToFileURL(path.join(root,'lai-thu.html')).href);await page.locator('button[type="submit"]').click();if(!page.url().includes('lai-thu.html'))issues.push('required validation');
 console.log(JSON.stringify({pages:fs.readdirSync(root).filter(f=>f.endsWith('.html')).length,widths:[375,768,1024,1440,1920],issues},null,2));await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
