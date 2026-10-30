"""Prepara il video del fuoco per la testata: loop senza stacchi, versioni desktop e telefono.

Uso: python tools/fire_loop.py <video sorgente> <cartella output>
Sorgente: "Burning Firewood" di Raúl Muñoz Ruiz, Pexels
https://www.pexels.com/video/burning-firewood-1717998/
"""
import os
import subprocess
import sys

import imageio_ffmpeg

START, LENGTH, FADE = 5, 12, 1.2


def main():
    src, out = sys.argv[1], sys.argv[2]
    os.makedirs(out, exist_ok=True)
    ff = imageio_ffmpeg.get_ffmpeg_exe()
    end = START + LENGTH
    # La coda sfuma nell'inizio: l'ultimo fotogramma si salda al primo
    graph = (
        f"[0:v]fps=25,settb=1/25,split=3[a][b][c];"
        f"[a]trim={end - FADE}:{end},setpts=PTS-STARTPTS[tail];"
        f"[b]trim={START}:{START + FADE},setpts=PTS-STARTPTS[head];"
        f"[c]trim={START + FADE}:{end - FADE},setpts=PTS-STARTPTS[body];"
        f"[tail]fps=25[t];[head]fps=25[h];[t][h]xfade=transition=fade:duration={FADE}:offset=0[seam];"
        f"[seam][body]concat=n=2:v=1,format=yuv420p"
    )
    common = ['-an', '-c:v', 'libx264', '-preset', 'veryslow', '-profile:v', 'high', '-movflags', '+faststart']
    for name, vf, crf in (('fogolar.mp4', 'scale=1600:900', '24'), ('fogolar-m.mp4', 'crop=1200:1080:260:0,scale=1000:900', '24')):
        subprocess.run([ff, '-y', '-loglevel', 'error', '-i', src, '-filter_complex', graph + ',' + vf, *common, '-crf', crf,
                        os.path.join(out, name)], check=True)
    for name, vf in (('poster.webp', 'scale=1600:900'), ('poster-m.webp', 'crop=1200:1080:260:0,scale=1000:900'), ('og.jpg', 'scale=1200:675')):
        subprocess.run([ff, '-y', '-loglevel', 'error', '-ss', str(START + 2), '-i', src, '-frames:v', '1', '-vf', vf,
                        os.path.join(out, name)], check=True)
    for name in ('fogolar.mp4', 'fogolar-m.mp4', 'poster.webp', 'poster-m.webp', 'og.jpg'):
        print(name, os.path.getsize(os.path.join(out, name)) // 1024, 'KB')


if __name__ == '__main__':
    main()
