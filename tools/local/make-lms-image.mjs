// LOCAL USE ONLY. Draws the original LMS dashboard illustration (no stock photo, no licence needed) in the site's colours
// and saves it as PNG; tools/local/make-lms-image.php turns it into the WebP the site serves.
//   node tools/local/make-lms-image.mjs
import { chromium } from 'playwright-core';
import fs from 'node:fs';

const html = `<!doctype html><meta charset="utf-8"><style>
*{box-sizing:border-box;margin:0}
body{width:1200px;height:900px;background:linear-gradient(135deg,#eaf1ff 0%,#f7f9ff 55%,#dfe9ff 100%);font-family:Segoe UI,Arial,sans-serif;position:relative;overflow:hidden;color:#0f1f3d}
.blob{position:absolute;border-radius:50%;filter:blur(2px)}
.win{position:absolute;left:110px;top:120px;width:980px;height:660px;background:#fff;border-radius:22px;box-shadow:0 30px 70px rgba(6,18,43,.22);overflow:hidden}
.bar{height:48px;background:#06122b;display:flex;align-items:center;gap:9px;padding:0 20px}
.bar i{width:12px;height:12px;border-radius:50%;background:#3f7af9;opacity:.9}.bar i:nth-child(2){opacity:.6}.bar i:nth-child(3){opacity:.35}
.bar b{margin-left:18px;color:#cfe0ff;font-size:15px;font-weight:600;letter-spacing:.3px}
.side{position:absolute;left:0;top:48px;bottom:0;width:210px;background:#f3f6ff;padding:26px 18px}
.side div{height:40px;border-radius:10px;margin-bottom:12px;display:flex;align-items:center;padding-left:14px;font-size:15px;color:#4a5b82}
.side .on{background:#3f7af9;color:#fff;font-weight:600}
.main{position:absolute;left:210px;top:48px;right:0;bottom:0;padding:26px 30px}
h1{font-size:26px;font-weight:700}.sub{font-size:15px;color:#6b7a99;margin-top:4px}
.stats{display:flex;gap:16px;margin-top:20px}
.stat{flex:1;background:#f3f6ff;border-radius:14px;padding:14px 16px}.stat b{font-size:26px;display:block;color:#3f7af9}.stat span{font-size:13px;color:#6b7a99}
.row{display:flex;gap:18px;margin-top:20px}
.card{flex:1;border:1px solid #e3eafb;border-radius:16px;padding:16px;background:#fff}
.thumb{height:92px;border-radius:10px;margin-bottom:12px;position:relative;overflow:hidden}
.card h3{font-size:16px;margin-bottom:10px}
.prog{height:9px;border-radius:9px;background:#e3eafb;overflow:hidden}.prog i{display:block;height:100%;background:#3f7af9;border-radius:9px}
.card small{display:block;margin-top:8px;font-size:12.5px;color:#6b7a99}
.chart{margin-top:20px;border:1px solid #e3eafb;border-radius:16px;padding:16px 18px;height:178px;position:relative}
.chart h3{font-size:16px}
.bars{position:absolute;left:22px;right:22px;bottom:14px;height:100px;display:flex;align-items:flex-end;gap:14px}
.bars i{flex:1;background:linear-gradient(#6f9bff,#3f7af9);border-radius:7px 7px 0 0}
.badge{position:absolute;right:46px;top:84px;background:#fff;border-radius:16px;box-shadow:0 18px 40px rgba(6,18,43,.2);padding:14px 18px;display:flex;align-items:center;gap:12px}
.badge .c{width:44px;height:44px;border-radius:50%;background:#22b573;color:#fff;font-size:24px;display:flex;align-items:center;justify-content:center}
.badge b{display:block;font-size:16px}.badge span{font-size:13px;color:#6b7a99}
.cap{position:absolute;left:36px;bottom:44px;background:#06122b;color:#fff;border-radius:16px;padding:16px 22px;box-shadow:0 18px 40px rgba(6,18,43,.3);font-size:20px;font-weight:700}
.cap span{display:block;font-size:13px;font-weight:400;color:#9db8f5;margin-top:3px}
</style>
<div class="blob" style="left:-120px;top:-100px;width:420px;height:420px;background:#cfe0ff"></div>
<div class="blob" style="right:-140px;bottom:-120px;width:460px;height:460px;background:#bcd2ff"></div>
<div class="win">
  <div class="bar"><i></i><i></i><i></i><b>Learning Management System</b></div>
  <div class="side"><div class="on">Dashboard</div><div>My courses</div><div>Assignments</div><div>Quizzes</div><div>Students</div><div>Certificates</div></div>
  <div class="main">
    <h1>Welcome back, learner</h1><div class="sub">Pick up where you left off</div>
    <div class="stats"><div class="stat"><b>12</b><span>Active courses</span></div><div class="stat"><b>86%</b><span>Average progress</span></div><div class="stat"><b>340</b><span>Learners</span></div></div>
    <div class="row">
      <div class="card"><div class="thumb" style="background:linear-gradient(135deg,#3f7af9,#7fa6ff)"></div><h3>Web Basics</h3><div class="prog"><i style="width:78%"></i></div><small>78% complete</small></div>
      <div class="card"><div class="thumb" style="background:linear-gradient(135deg,#06122b,#2c4f9e)"></div><h3>Networking</h3><div class="prog"><i style="width:54%"></i></div><small>54% complete</small></div>
      <div class="card"><div class="thumb" style="background:linear-gradient(135deg,#22b573,#7be0b0)"></div><h3>Cyber Safety</h3><div class="prog"><i style="width:92%"></i></div><small>92% complete</small></div>
    </div>
    <div class="chart"><h3>Weekly activity</h3><div class="bars"><i style="height:45%"></i><i style="height:70%"></i><i style="height:55%"></i><i style="height:88%"></i><i style="height:64%"></i><i style="height:96%"></i><i style="height:74%"></i></div></div>
  </div>
</div>
<div class="badge"><div class="c">&#10003;</div><div><b>Course completed</b><span>Certificate issued</span></div></div>
<div class="cap">Learn anywhere<span>Courses, quizzes and certificates in one place</span></div>`;

const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 1200, height: 900 } });
await p.setContent(html);
fs.mkdirSync('storage/app/private/archive/product-sources', { recursive: true });
await p.screenshot({ path: 'storage/app/private/archive/product-sources/lms-dashboard.png' });
await b.close();
console.log('saved storage/app/private/archive/product-sources/lms-dashboard.png');
