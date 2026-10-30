/* Il fuoco del Fogolar: la foto reale, animata dal vivo in WebGL.
   Le fiamme sono 4 fotogrammi veri deformati da rumore, solo dove la maschera dice che il fuoco si muove.
   Senza WebGL, con "riduci movimento" o "risparmio dati" resta la foto. Toccando il fuoco si alzano scintille. */
(function () {
  var hero = document.getElementById('hero');
  if (!hero) return;
  var canvas = hero.querySelector('.hero__fire');
  var conn = navigator.connection;
  if (matchMedia('(prefers-reduced-motion: reduce)').matches || (conn && conn.saveData)) return;
  var gl = canvas.getContext('webgl', { alpha: false, antialias: false, powerPreference: 'low-power' });
  if (!gl) return;

  var IMG = [1920, 1280], CROP = [378, 67, 971, 1025]; // area delle fiamme nella foto (vedi tools/assets.py)
  var FOCUS = [0.36, 0.42];

  var VS = 'attribute vec2 p;varying vec2 v;void main(){v=p*.5+.5;gl_Position=vec4(p,0.,1.);}';
  var FS = [
    'precision mediump float;varying vec2 v;',
    'uniform sampler2D base,atlas,mask;uniform float t,flare;uniform vec2 scale,offset;uniform vec4 crop;',
    'float h(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}',
    'float n(vec2 p){vec2 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);',
    'return mix(mix(h(i),h(i+vec2(1,0)),f.x),mix(h(i+vec2(0,1)),h(i+vec2(1,1)),f.x),f.y);}',
    'float fbm(vec2 p){return n(p)*.55+n(p*2.1+3.)*.3+n(p*4.3+7.)*.15;}',
    'vec3 frame(float i,vec2 c){return texture2D(atlas,vec2((i+clamp(c.x,.002,.998))/4.,c.y)).rgb;}',
    'void main(){',
    ' vec2 uv=vec2(v.x,1.-v.y)*scale+offset;', // cover: uv nella foto, y verso il basso
    ' vec3 col=texture2D(base,uv).rgb;',
    ' vec2 c=(uv-crop.xy)/(crop.zw-crop.xy);',
    ' float flick=.5+.5*sin(t*7.3)*sin(t*3.1+1.2)+.25*(fbm(vec2(t*1.7,2.))-.5);',
    ' if(c.x>0.&&c.x<1.&&c.y>0.&&c.y<1.){',
    '  float m=texture2D(mask,c).r;',
    '  if(m>.01){',
    '   float rise=1.-c.y;',
    '   vec2 q=vec2(c.x*5.,c.y*6.+t*1.9);',
    '   vec2 d=vec2(fbm(q)-.5,fbm(q+vec2(5.2,1.3))-.5);',
    '   vec2 w=d*vec2(.085,.13)*(.35+rise*.9)*m;',
    '   float ph=t*1.15+fbm(vec2(c.x*2.,c.y*2.-t*.25))*1.6;',
    '   float i0=mod(floor(ph),4.),i1=mod(i0+1.,4.),k=smoothstep(.15,.85,fract(ph));',
    '   vec3 a=frame(i0,c+w),b=frame(i1,c+w*1.35);',
    '   vec3 f=mix(a,b,k);',
    '   f*=1.+.14*flick+flare*.35;',
    '   col=mix(col,f,m);',
    '  }',
    ' }',
    // il riverbero del fuoco sulla stanza
    ' float dist=length((uv-vec2(.36,.52))*vec2(1.5,1.));',
    ' float glow=smoothstep(.75,.05,dist);',
    ' col*=1.+glow*(.1*flick+flare*.28);',
    ' col+=vec3(.05,.018,.004)*glow*(flick+flare*1.4);',
    ' gl_FragColor=vec4(col,1.);',
    '}'
  ].join('\n');

  var SVS = [
    'attribute vec4 a;uniform vec2 res;varying float l;varying float s;',
    'void main(){l=a.z;s=a.w;gl_Position=vec4(a.x/res.x*2.-1.,1.-a.y/res.y*2.,0.,1.);gl_PointSize=a.w;}'
  ].join('');
  var SFS = [
    'precision mediump float;varying float l;varying float s;',
    'void main(){float d=length(gl_PointCoord-.5)*2.;float g=pow(max(0.,1.-d),2.);',
    'vec3 c=mix(vec3(1.,.28,.05),vec3(1.,.82,.45),l);gl_FragColor=vec4(c*g*l,g*l);}'
  ].join('');

  function program(vs, fs) {
    var p = gl.createProgram();
    [[gl.VERTEX_SHADER, vs], [gl.FRAGMENT_SHADER, fs]].forEach(function (d) {
      var sh = gl.createShader(d[0]);
      gl.shaderSource(sh, d[1]);
      gl.compileShader(sh);
      gl.attachShader(p, sh);
    });
    gl.linkProgram(p);
    return gl.getProgramParameter(p, gl.LINK_STATUS) ? p : null;
  }

  var prog = program(VS, FS), sparkProg = program(SVS, SFS);
  if (!prog || !sparkProg) return;

  var quad = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, quad);
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
  var sparkBuf = gl.createBuffer();

  function u(p, n) { return gl.getUniformLocation(p, n); }

  function texture(unit, src, ready) {
    var img = new Image();
    img.onload = function () {
      var tex = gl.createTexture();
      gl.activeTexture(gl.TEXTURE0 + unit);
      gl.bindTexture(gl.TEXTURE_2D, tex);
      gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
      gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
      gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
      gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
      gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGB, gl.RGB, gl.UNSIGNED_BYTE, img);
      ready();
    };
    img.src = src;
  }

  var quality = 1, slowSum = 0, slowN = 0, strikes = 0, dead = false;
  var W = 0, H = 0, dpr = 1, scaleX = 1, scaleY = 1, offX = 0, offY = 0;
  function resize() {
    var r = hero.getBoundingClientRect();
    dpr = Math.min(window.devicePixelRatio || 1, r.width < 700 ? 1.5 : 1.25) * quality;
    W = Math.round(r.width * dpr);
    H = Math.round(r.height * dpr);
    canvas.width = W;
    canvas.height = H;
    gl.viewport(0, 0, W, H);
    var ia = IMG[0] / IMG[1], va = W / H;
    scaleX = va < ia ? va / ia : 1;
    scaleY = va < ia ? 1 : ia / va;
    offX = (1 - scaleX) * FOCUS[0];
    offY = (1 - scaleY) * FOCUS[1];
  }

  // Scintille
  var MAX = 90, sparks = [], data = new Float32Array(MAX * 4);
  function imgToScreen(x, y) { return [(x - offX) / scaleX * W, (y - offY) / scaleY * H]; }
  function spawn(x, y, burst) {
    if (sparks.length >= MAX) sparks.shift();
    var a = -Math.PI / 2 + (Math.random() - .5) * (burst ? 1.7 : .7);
    var sp = (burst ? 90 + Math.random() * 230 : 30 + Math.random() * 60) * dpr;
    sparks.push({ x: x, y: y, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp, life: 1, decay: .3 + Math.random() * .5, size: (1.6 + Math.random() * 3) * dpr, seed: Math.random() * 9 });
  }

  var flare = 0, last = 0, t = Math.random() * 40, visible = true, running = false, ready = 0, idle = 0;
  var loc = {
    t: u(prog, 't'), flare: u(prog, 'flare'), scale: u(prog, 'scale'), offset: u(prog, 'offset'), crop: u(prog, 'crop'),
    base: u(prog, 'base'), atlas: u(prog, 'atlas'), mask: u(prog, 'mask')
  };
  var pA = gl.getAttribLocation(prog, 'p'), sA = gl.getAttribLocation(sparkProg, 'a'), sRes = u(sparkProg, 'res');

  function frame(now) {
    if (!visible) { running = false; return; }
    requestAnimationFrame(frame);
    var dt = Math.min((now - last) / 1000 || 0, .05);
    // Telefono lento: prima abbassa la risoluzione, se non basta resta la foto
    slowSum += now - last;
    if (++slowN === 45) {
      if (slowSum / slowN > 42) {
        if (quality > .5) { quality = .5; resize(); }
        else if (++strikes >= 3) { canvas.classList.remove('is-on'); visible = false; dead = true; return; }
      }
      slowSum = slowN = 0;
    }
    last = now;
    t += dt;
    flare *= Math.pow(.12, dt);

    gl.disable(gl.BLEND);
    gl.useProgram(prog);
    gl.bindBuffer(gl.ARRAY_BUFFER, quad);
    gl.enableVertexAttribArray(pA);
    gl.vertexAttribPointer(pA, 2, gl.FLOAT, false, 0, 0);
    gl.uniform1f(loc.t, t);
    gl.uniform1f(loc.flare, flare);
    gl.uniform2f(loc.scale, scaleX, scaleY);
    gl.uniform2f(loc.offset, offX, offY);
    gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);

    idle -= dt;
    if (idle <= 0) {
      idle = .22 + Math.random() * .55;
      var from = imgToScreen(.28 + Math.random() * .2, .42 + Math.random() * .22);
      spawn(from[0], from[1], false);
    }
    var n = 0;
    for (var i = sparks.length - 1; i >= 0; i--) {
      var s = sparks[i];
      s.life -= s.decay * dt;
      if (s.life <= 0 || s.y < -20) { sparks.splice(i, 1); continue; }
      s.vy -= 26 * dpr * dt;
      s.vx += Math.sin(t * 3 + s.seed) * 42 * dpr * dt;
      s.vx *= 1 - .9 * dt;
      s.x += s.vx * dt;
      s.y += s.vy * dt;
      data[n * 4] = s.x; data[n * 4 + 1] = s.y; data[n * 4 + 2] = s.life; data[n * 4 + 3] = s.size * (.5 + s.life * .5) * 2;
      n++;
    }
    if (n) {
      gl.enable(gl.BLEND);
      gl.blendFunc(gl.ONE, gl.ONE);
      gl.useProgram(sparkProg);
      gl.uniform2f(sRes, W, H);
      gl.bindBuffer(gl.ARRAY_BUFFER, sparkBuf);
      gl.bufferData(gl.ARRAY_BUFFER, data.subarray(0, n * 4), gl.DYNAMIC_DRAW);
      gl.enableVertexAttribArray(sA);
      gl.vertexAttribPointer(sA, 4, gl.FLOAT, false, 0, 0);
      gl.drawArrays(gl.POINTS, 0, n);
    }
  }

  function start() {
    if (dead || running || ready < 3 || !visible) return;
    slowSum = slowN = 0;
    running = true;
    last = performance.now();
    requestAnimationFrame(frame);
    canvas.classList.add('is-on');
  }

  resize();
  gl.useProgram(prog);
  gl.uniform1i(loc.base, 0);
  gl.uniform1i(loc.atlas, 1);
  gl.uniform1i(loc.mask, 2);
  gl.uniform4f(loc.crop, CROP[0] / IMG[0], CROP[1] / IMG[1], CROP[2] / IMG[0], CROP[3] / IMG[1]);

  function loaded() { ready++; start(); }
  var size = Math.max(W, H) > 1200 ? 1920 : 1100;
  texture(0, '/assets/fire/base-' + size + '.webp', loaded);
  texture(1, '/assets/fire/atlas.webp', loaded);
  texture(2, '/assets/fire/mask.webp', loaded);

  var timer;
  addEventListener('resize', function () { clearTimeout(timer); timer = setTimeout(resize, 120); }, { passive: true });
  new IntersectionObserver(function (e) { visible = e[0].isIntersecting; start(); }).observe(hero);
  document.addEventListener('visibilitychange', function () { visible = !document.hidden; start(); });

  // Attizzare il fuoco
  var lastPoke = 0;
  hero.addEventListener('pointerdown', function (ev) {
    if (ev.target.closest('a,button')) return;
    var r = hero.getBoundingClientRect(), now = performance.now();
    if (now - lastPoke < 90) return;
    lastPoke = now;
    flare = Math.min(1.2, flare + .8);
    for (var i = 0; i < 26; i++) spawn((ev.clientX - r.left) * dpr, (ev.clientY - r.top) * dpr, true);
  }, { passive: true });
})();
