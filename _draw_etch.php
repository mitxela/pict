<?
/*
$HTMLheaderCodeNoScale='<!doctype html><html><head>
<meta charset=utf-8><title>PICT</title>
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">';
$URL = '/pict/';
$fullURL = 'http://localhost'.$URL;

$prompt="A prompt.";
$straightToPoll=false;
$countdownSec=array();
$game=array('Countdown'=>0);
$countdownSec[$game['Countdown']]=-1;
$startCountdown=5;
*/
////////////////////////////////////////////////////////////////////////////////////

if (!isset($URL)) {
  die();
}


echo $HTMLheaderCodeNoScale;
?>
<style>
body,html{margin:0;padding:0;user-select:none;height:100%}

canvas{max-width:100vw;max-height:100vh;display:block;margin:auto; position:relative;}
#o{display:table;position:fixed;z-index:1;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.2);transition:2s;font-size:28pt;}
#p{display:table-cell;vertical-align:middle;text-align:center;}
#q{margin-right:5px;}


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
<div id=o>
<div id=p>Your Prompt: <b><?=entitiesOut($prompt); ?></b>
<div id=q></div>
</div>
</div>
<script>
p=document.getElementById('p')
q=document.getElementById('q')
o=document.getElementById('o')
drawable=false;

////////////////////////////




knobs=false;

(img=new Image()).src='etch.png';

c=document.getElementById('c')
c.width=cw=1248;//window.innerWidth;
c.height=ch=732;//window.innerHeight;
ctx=c.getContext('2d');
ctx.fillStyle='rgba(181,181,181,0.1)';

keys=[];keys[37]=keys[38]=keys[39]=keys[40]=keys[17]=0;
document.onkeydown=function(e){keys[e.keyCode]=true;}
document.onkeyup=function(e){keys[e.keyCode]=false;}
tx=ty=x=y=vx=vy=px=py=0; damp=5;

drawx = cw/2
drawy = ch/2
pdrawx = pdrawy = 0;


function draw(){
  if (drawable) {
    ctx.strokeStyle='#000'
    ctx.lineWidth=1
    ctx.beginPath();
    ctx.moveTo(drawx,drawy);


    //if (debug) ctx.font="40px Verdana",ctx.clearRect(300,250,600,100),ctx.fillStyle='#000',ctx.fillText(debug,300,300)
    if (knobs) {drawx+=knobs[0]*25; drawy-=knobs[1]*25;}

    var speed=keys[17]?5:1;
    drawx +=(keys[39]-keys[37]) *speed;
    drawy +=(keys[40]-keys[38]) *speed;

    drawx=Math.min(Math.max(drawx,224+5),224+800-5)
    drawy=Math.min(Math.max(drawy,66+5),66+600-5)

    if (knobs) {knobs[0]=0; knobs[1]=0}

    if ((pdrawy != drawy) || (pdrawx != drawx)) {
      ctx.lineTo(drawx,drawy)
      ctx.stroke();
    }
    ctx.strokeStyle='#bebab1'
    ctx.lineWidth=3
    ctx.fillStyle='#e1e0da';
    if (pdrawx != drawx)
      drawKnob(0,drawx/50)
    pdrawx = drawx;
    if (pdrawy != drawy)
      drawKnob(1,-drawy/50)

    pdrawy = drawy;

    //if (!held){
      vx-=vx/damp+(x-tx)/10;
      vy-=vy/damp+(y-ty)/10;
      x+=vx;y+=vy;
    //}
    c.style.left=Math.round(x)+"px";
    c.style.top =Math.round(y)+"px";

    if (!knobs) c.style.transform="skew("+vx/20+"deg,"+vy/20+"deg)"

    ctx.fillStyle='rgba(181,181,181,0.1)';
    if (Math.abs(px-x)+Math.abs(py-y)>10 || shakeClear) ctx.fillRect(224+4,66+4,800-4,600-4);
    shakeClear=false;
    px=x;py=y;
  }
  requestAnimationFrame(draw);
}
function drawKnob(w, a){
  var x = 124 + 1000*w, y=366, n=24;
  ctx.beginPath();
  ctx.arc(x,y,62,0,6.283185307179586);
  ctx.fill();
  for(var i=0;i<n;i++){
    var c = Math.cos(a + i/n*6.283185307179586);
    var s = Math.sin(a + i/n*6.283185307179586);

    ctx.beginPath();
    ctx.moveTo(x+c*48,y+s*48)
    ctx.lineTo(x+c*61,y+s*61)
    ctx.stroke();
  }

}


img.onload=function(){
  ctx.drawImage(img,0,0,cw,ch);

  c.onmousedown=function(e){
    document.body.style.overflow="hidden";
    damp=3;
    dx=x;dy=y;
    mx=e.clientX; my=e.clientY;
    document.onmousemove=function(e){
      tx=dx+e.clientX-mx;
      ty=dy+e.clientY-my;
    }
    document.onmouseup=function(e){
      document.onmousemove = null;
      document.onmouseup = null;
      damp=5;
      tx=0;ty=0;
      //vx=x-px; vy=y-py;
    }
  }

  requestAnimationFrame(draw);
}






touches=[], scale = c.clientWidth/cw;
function knobAngle(x, y, rknob){
  if (rknob) x-=window.innerWidth/2 + 500*scale; else x-=window.innerWidth/2 - 500*scale;
  y -= 366*scale;
  return Math.atan2(y,x);
}
function knobDist(x, y){
  if (x >window.innerWidth/2) x-=window.innerWidth/2 + 500*scale; else x-=window.innerWidth/2 - 500*scale;
  y -= 366*scale;
  return Math.sqrt(x*x+y*y)/scale
}
c.addEventListener("touchstart",function(e){
  scale = c.clientWidth/cw;

  if ( knobDist(e.changedTouches[0].pageX, e.changedTouches[0].pageY) >200)return;

  if (!knobs) {knobs=[0,0];};
  for (var i =e.changedTouches.length;i--;){
    var k=(e.changedTouches[i].pageX >window.innerWidth/2);
    touches[e.changedTouches[i].identifier]={
      "k": k,
      "oldAngle": knobAngle(e.changedTouches[i].pageX, e.changedTouches[i].pageY, k)
    }
  }
  e.preventDefault();


},true);

c.addEventListener("touchmove",function(e){
  for (var i=e.touches.length;i--;){
    var t=touches[e.touches[i].identifier];
    var a = knobAngle(e.touches[i].pageX, e.touches[i].pageY, t.k);
    if (a-t.oldAngle < -3.141592653589793) t.oldAngle-=6.283185307179586;
    if (a-t.oldAngle >  3.141592653589793) t.oldAngle+=6.283185307179586;
    knobs[t.k*1]+=a-t.oldAngle;
    t.oldAngle=a;
  }
},true);




shakeClear=false;
if (window.DeviceMotionEvent) {
  window.addEventListener('devicemotion', function(e){
    if (pythag(e.accelerationIncludingGravity)>14 || pythag(e.acceleration)>4) {
      shakeClear=true;
	}
  }, false);
}
function pythag(a){return Math.sqrt(a.x*a.x+a.y*a.y+a.z*a.z);}











////////////////////////////////




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
