import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

const VERTEX = `
attribute vec2 aPosition;
varying vec2 vUv;
void main() { vUv = aPosition * 0.5 + 0.5; gl_Position = vec4(aPosition, 0.0, 1.0); }`;

// A 35 mm strip: sprocket holes down both edges, frame lines, a countdown
// leader sweeping in the middle frame, grain, scratches and gate flicker.
const FRAGMENT = `
precision mediump float;
varying vec2 vUv;
uniform float uShift;   // 1 = strip above the screen, 0 = covering it, -1 = gone below
uniform float uTime;
uniform vec2 uSize;
uniform vec3 uAccent;

float hash(vec2 p) { return fract(sin(dot(p, vec2(12.9898, 78.233))) * 43758.5453); }
float box(vec2 p, vec2 b, float r) { vec2 q = abs(p) - b + r; return length(max(q, 0.0)) + min(max(q.x, q.y), 0.0) - r; }

void main() {
    float y = vUv.y - uShift;
    if (y < 0.0 || y > 1.0) discard;

    float aspect = uSize.x / uSize.y;
    vec2 p = vec2(vUv.x * aspect, y);
    float rail = 0.075 * max(1.0, aspect * 0.5);
    float scroll = uTime * 0.9;

    vec3 base = vec3(0.045, 0.04, 0.035);
    vec3 colour = base;

    // Sprocket holes on both rails.
    float edge = min(p.x, aspect - p.x);
    if (edge < rail) {
        float cell = 0.075;
        vec2 hole = vec2(edge - rail * 0.5, mod(y + scroll, cell) - cell * 0.5);
        float d = box(hole, vec2(rail * 0.22, cell * 0.28), 0.006);
        colour = mix(vec3(0.95, 0.93, 0.86), base, smoothstep(0.0, 0.002, d));
    } else {
        // Frames: three per screen, separated by thin bars.
        float frame = mod(y + scroll * 0.35, 0.3333);
        colour = mix(vec3(0.0), base * 1.6, smoothstep(0.004, 0.012, frame) * smoothstep(0.004, 0.012, 0.3333 - frame));

        // Countdown leader in the centre: rings, cross hair and a sweeping arm.
        vec2 c = vec2(p.x - aspect * 0.5, y - 0.5);
        float r = length(c);
        float ring = smoothstep(0.004, 0.0, abs(r - 0.16)) + smoothstep(0.003, 0.0, abs(r - 0.11));
        float cross = smoothstep(0.0025, 0.0, abs(c.x)) + smoothstep(0.0025, 0.0, abs(c.y));
        float angle = atan(c.x, c.y);
        float sweep = mod(-uTime * 6.2831, 6.2831) - 3.14159;
        float wedge = step(r, 0.16) * step(mod(angle - sweep + 6.2831, 6.2831), 1.2) * 0.35;
        colour += vec3(0.85, 0.82, 0.75) * (ring + cross * step(r, 0.2)) * 0.8;
        colour += uAccent * wedge;
    }

    // Grain, a vertical scratch and gate flicker.
    float grain = hash(vUv * uSize + fract(uTime * 43.0)) - 0.5;
    colour += grain * 0.09;
    float scratch = smoothstep(0.0015, 0.0, abs(vUv.x - 0.33 - 0.05 * sin(uTime * 3.0))) * step(0.6, hash(vec2(floor(uTime * 12.0))));
    colour += scratch * 0.25;
    colour *= 0.92 + 0.08 * sin(uTime * 60.0);

    // Soft leading and trailing edges.
    float fade = smoothstep(0.0, 0.03, y) * smoothstep(1.0, 0.97, y);
    gl_FragColor = vec4(colour, fade);
}`;

/**
 * Between Inertia page visits a strip of film rolls down over the page and
 * off again. Skipped for reduced motion, for in-page updates (filters,
 * partial reloads) and if WebGL is unavailable.
 */
export default function ReelTransition() {
    const canvas = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const element = canvas.current;
        if (!element || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const gl = element.getContext('webgl', { premultipliedAlpha: false, alpha: true, antialias: false });
        if (!gl) return;

        const compile = (type: number, source: string) => {
            const shader = gl.createShader(type) as WebGLShader;
            gl.shaderSource(shader, source);
            gl.compileShader(shader);
            return shader;
        };
        const program = gl.createProgram() as WebGLProgram;
        gl.attachShader(program, compile(gl.VERTEX_SHADER, VERTEX));
        gl.attachShader(program, compile(gl.FRAGMENT_SHADER, FRAGMENT));
        gl.linkProgram(program);
        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) return;
        gl.useProgram(program);

        const buffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
        const position = gl.getAttribLocation(program, 'aPosition');
        gl.enableVertexAttribArray(position);
        gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);
        gl.enable(gl.BLEND);
        gl.blendFunc(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA);

        const uShift = gl.getUniformLocation(program, 'uShift');
        const uTime = gl.getUniformLocation(program, 'uTime');
        const uSize = gl.getUniformLocation(program, 'uSize');
        gl.uniform3f(gl.getUniformLocation(program, 'uAccent'), 0.89, 1.0, 0.23);

        let shift = 1;
        let target = 1;
        let frame = 0;
        let started = 0;
        let pending = false;

        const resize = () => {
            const scale = Math.min(window.devicePixelRatio, 1.5);
            element.width = Math.round(window.innerWidth * scale);
            element.height = Math.round(window.innerHeight * scale);
            gl.viewport(0, 0, element.width, element.height);
            gl.uniform2f(uSize, element.width, element.height);
        };

        const draw = (now: number) => {
            shift += (target - shift) * 0.2;
            if (Math.abs(target - shift) < 0.002) shift = target;
            gl.clearColor(0, 0, 0, 0);
            gl.clear(gl.COLOR_BUFFER_BIT);
            gl.uniform1f(uShift, shift);
            gl.uniform1f(uTime, (now - started) / 1000);
            gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);

            if (shift === -1) {
                // Parked below: reset above and stop drawing.
                shift = target = 1;
                element.style.visibility = 'hidden';
                frame = 0;
                return;
            }
            frame = requestAnimationFrame(draw);
        };

        const play = () => {
            element.style.visibility = 'visible';
            if (!frame) { started = performance.now(); frame = requestAnimationFrame(draw); }
        };

        const offStart = router.on('start', (event) => {
            const visit = event.detail.visit;
            if (visit.method !== 'get' || visit.only.length > 0 || visit.preserveState === true || visit.prefetch) return;
            pending = true;
            resize();
            target = 0;
            play();
        });
        const offFinish = router.on('finish', () => {
            if (!pending) return;
            pending = false;
            // Leave the strip up for a beat so fast visits still read as a cut.
            window.setTimeout(() => { target = -1; play(); }, 140);
        });

        window.addEventListener('resize', resize);
        return () => {
            offStart();
            offFinish();
            window.removeEventListener('resize', resize);
            cancelAnimationFrame(frame);
        };
    }, []);

    return <canvas ref={canvas} aria-hidden="true" className="pointer-events-none fixed inset-0 z-[130] h-full w-full print:hidden" style={{ visibility: 'hidden' }} />;
}
