<?
if (!isset($URL)) {
  $_SERVER['QUERY_STRING']='404';
  require('/home/public/mitxela.com/error.php');
  die();
}


echo $HTMLheaderCodeNoScale;
?>
<style>
body,html{margin:0;padding:0;overflow:hidden;user-select:none; position:fixed;}
canvas{background:url(tex_squares.jpg); touch-action:none;}
#o{display:table;position:fixed;z-index:1;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.2);transition:2s;font-size:28pt;}
#p{display:table-cell;vertical-align:middle;text-align:center;}
#q{margin-right:5px;}
#r{position:fixed;bottom:-30px;transition:bottom 1s;width:100%;text-align:center}
#s{background:rgba(0,0,0,0.2); padding:8px;border-radius:10px 10px 0px 0px;}
#e{background: url(erase.png) center no-repeat;}

.a1,.a2,.a3 {animation: pulsate 0.7s infinite;}

.a1{animation-delay:0;}
.a2{animation-delay:0.15s;}
.a3{animation-delay:0.3s;}

@keyframes pulsate{
  0%  {opacity:1}
  30% {opacity:0}
  60% {opacity:1}
}

</style>
</head><body>
<canvas id=c></canvas>
<div id=r><span id=s></span></div>
<div id=o>
<div id=p>Your Prompt: <b><?=htmlentities($prompt); ?></b>
<div id=q></div>
</div>
</div>
<script>
['p','q','r','o','s','c'].forEach(function(i){ window[i]=document.getElementById(i) })

c.width=cw=window.innerWidth;
c.height=ch=window.innerHeight;
ctx=c.getContext('2d');
brushSize=bs= Math.min(cw,ch)/100;
brushcolor='black'
drawable=false;

function draw(x,y,r) {
  if (!drawable) return;
  ctx.beginPath()
  ctx.arc(x,y,r,0,2*3.1415926535897)
  ctx.fill()
}

function bresenham(x,y,x2,y2,plot){
  var dist=(x-x2)*(x-x2)+(y-y2)*(y-y2);
  if (!dist) return;

  var r=brushSize*Math.pow(window.brushcolor==0? 1/dist:dist,-0.08);
  dist=Math.round(Math.sqrt(dist));

  var dx = (x2-x)/dist, dy=(y2-y)/dist;

  for (var i=0; i<dist; i++) {
    plot(x += dx, y += dy, r);
  }

}

document.onmousedown=function(e){
  if (e.buttons==2 || window.brushcolor==0) {
    ctx.globalCompositeOperation = 'destination-out'; //Erase to transparent
    brushSize=bs*3;
  } else {
    ctx.fillStyle=brushcolor;
    ctx.globalCompositeOperation = 'source-over';
    brushSize=bs;
  }
  var sx=e.clientX, sy=e.clientY;
  draw(sx,sy,brushSize)
  document.onmousemove=function(e){
    bresenham(sx,sy,e.clientX,e.clientY,draw)
    sx=e.clientX, sy=e.clientY
  }
}
document.onmouseup=function(e){
  document.onmousemove=null;
}
c.oncontextmenu=function(){return false;}

s.onmousedown=function(e){e.stopPropagation()};

['black','red','blue','green','yellow',0].forEach(function(i){
  var d = document.createElement('button');
  d.innerHTML='&nbsp;'
  if (i)d.style.backgroundColor=i;
  else d.id='e';
  d.onclick=function(){window.brushcolor=i}
  s.appendChild(d);
});

touches=[];
//lastTap={x:-1,y:-1,t:-1}
document.ontouchstart=function(e){
  e.preventDefault();
  var now=new Date().getTime();
  for (var i =e.changedTouches.length;i--;){
    var t={
      x:e.changedTouches[i].pageX,
      y:e.changedTouches[i].pageY
    };
    //if (Math.abs(t.x-lastTap.x) < 10 && Math.abs(t.y-lastTap.y) < 10 && (now-lastTap.t) < 400) {
    if (window.brushcolor==0) {
      ctx.globalCompositeOperation = 'destination-out';
      brushSize=bs*3;
    } else {
      ctx.fillStyle=brushcolor;
      ctx.globalCompositeOperation = 'source-over';
      brushSize=bs;
    }
    //lastTap={x:t.x,y:t.y,t:now}

    if (e.changedTouches[i].target.tagName!="BUTTON" && e.changedTouches[i].target.tagName!="SPAN")
      draw(t.x,t.y,brushSize)
    touches[e.changedTouches[i].identifier]=t;
  }
}
document.ontouchmove=function(e){
  e.preventDefault();
  for (var i=e.changedTouches.length;i--;){
     var t=touches[e.changedTouches[i].identifier];
     if (e.changedTouches[i].target.tagName!="BUTTON" && e.changedTouches[i].target.tagName!="SPAN")
       bresenham(t.x,t.y,e.changedTouches[i].pageX,e.changedTouches[i].pageY,draw)
     t.x=e.changedTouches[i].pageX;
     t.y=e.changedTouches[i].pageY;
  }
}

function ajax(url, callback, data){
  var r = new XMLHttpRequest();
  r.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200)
      callback(JSON.parse(this.responseText));
  };
  r.open(data?"POST":"GET", url, true);
  if (data) r.setRequestHeader('Content-Type', 'application/octet-stream');
  r.send(data);
}

function upload(){
  q.innerHTML="";
  drawable=false;
  o.style.height='100%';
  o.style.fontSize="28pt";
  o.style.backgroundColor="rgba(255,255,255,0.8)";
  p.innerHTML="Uploading<span class=a1>.</span><span class=a2>.</span><span class=a3>.</span> ";

  var b64 = c.toDataURL();
  var raw = atob(b64.substring(b64.indexOf(',')+1));
  var len = raw.length;
  var bytes = new Uint8Array( len );
  for (var i = 0; i < len; i++) {
    bytes[i] = raw.charCodeAt(i);
  }
  ajax('?upload=1',function(r){

    if (r.uploaded!=len) alert('Upload error');

  // set 'waiting for other players'

    poll();
    window.setInterval(poll, 3000);

  },bytes);

}
function poll(){
  var t = Math.round(new Date().getTime()/1000);
  ajax('?wait=upload&poll='+t, function(r){

    if (r['reload']) window.location=window.location;

    if (r['playerList']) p.innerHTML='Waiting for players... ' + r['playerList'].join(', ');

  })
}


<? if ($straightToPoll) echo "poll();window.setInterval(poll, 3000);"; else  {?>

startCountdown=<?=$startCountdown ?>;
mainCountdown=<?= $countdownSec[$game['Countdown']] ?>;
(startTimer=function(){
  if (startCountdown) {
    q.innerHTML="Start drawing in "+(startCountdown--)+"...";
    setTimeout(startTimer,1000);
  } else {
    o.style.height="0";
    o.style.fontSize="14pt";
    q.style.float="right";
    r.style.bottom='2px'
    drawable=true;

    if (mainCountdown==-1) {
      q.innerHTML="<button onclick='upload();'>Done</button>";
      q.onmousedown=function(e){e.stopPropagation();}
    }
    else {
    q.innerHTML="";
    q.style.color="red";
    setTimeout(mainTimer=function(){
      if (mainCountdown) {
        q.innerHTML=mainCountdown--;
        setTimeout(mainTimer,1000);
      } else upload();
    },1500);
    }
  }
})();

<?}?>
</script>
</body>
</html>
