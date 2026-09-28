import { Canvas, useFrame, useThree } from '@react-three/fiber';
import { useEffect, useMemo, useRef, useState } from 'react';
import * as THREE from 'three';
import { token, useTheme } from '@/lib/theme';
import type { Palette } from '@/types';

export type RingMovie = { title: string; slug: string; genre?: string; palette?: Palette; poster_url?: string | null; certificate?: string; release_year?: string | null };

const PANEL_HEIGHT = 2.25;
const PANEL_WIDTH = PANEL_HEIGHT * (4 / 3);
const RADIUS = 5.4;
const MIN_PANELS = 10;

/**
 * Draws the same typographic one-sheet the DOM <Poster> uses onto a canvas,
 * so films without artwork still get a proper face in 3D.
 */
function drawPoster(movie: RingMovie): THREE.CanvasTexture {
    const width = 1024;
    const height = 768;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d') as CanvasRenderingContext2D;
    const [ground, accent, paper] = movie.palette ?? ['#1b1b1b', '#e3ff3b', '#f2f0ea'];

    const gradient = ctx.createLinearGradient(0, 0, width * 0.3, height);
    gradient.addColorStop(0, accent);
    gradient.addColorStop(0.18, ground);
    gradient.addColorStop(1, '#050505');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, width, height);

    ctx.globalAlpha = 0.85;
    ctx.fillStyle = accent;
    ctx.beginPath();
    ctx.arc(width * 0.8, height * 0.3, height * 0.42, 0, Math.PI * 2);
    ctx.fill();
    ctx.globalAlpha = 1;

    ctx.fillStyle = ground;
    for (let band = 1; band <= 6; band++) {
        ctx.globalAlpha = 1 - band * 0.13;
        ctx.fillRect(0, height * 0.28 + band * 22, width, 5);
    }
    ctx.globalAlpha = 1;

    const pad = 40;
    ctx.fillStyle = paper;
    ctx.font = '700 expanded 18px "Archivo Variable", sans-serif';
    ctx.textBaseline = 'top';
    ctx.fillText(movie.release_year ?? '', pad, pad);
    const certificate = movie.certificate ?? '';
    ctx.fillText(certificate, width - pad - ctx.measureText(certificate).width, pad);

    // Title: extra-condensed caps, wrapped and sized to fit three lines.
    const words = movie.title.toUpperCase().split(' ');
    let size = 132;
    let lines: string[] = [];
    while (size > 48) {
        ctx.font = `850 extra-condensed ${size}px "Archivo Variable", sans-serif`;
        lines = [];
        let line = '';
        for (const word of words) {
            const next = line ? `${line} ${word}` : word;
            if (ctx.measureText(next).width > width - pad * 2 && line) {
                lines.push(line);
                line = word;
            } else {
                line = next;
            }
        }
        lines.push(line);
        if (lines.length <= 3 && lines.every((text) => ctx.measureText(text).width <= width - pad * 2)) break;
        size -= 8;
    }

    ctx.textBaseline = 'alphabetic';
    const lineHeight = size * 0.86;
    const bottom = height - pad - 26;
    lines.forEach((text, index) => ctx.fillText(text, pad, bottom - (lines.length - 1 - index) * lineHeight));
    ctx.fillStyle = accent;
    ctx.fillRect(pad, height - pad - 10, 56, 6);

    ctx.fillStyle = accent;
    ctx.font = '700 expanded 16px "Archivo Variable", sans-serif';
    ctx.fillText((movie.genre ?? '').toUpperCase(), pad, bottom - lines.length * lineHeight - 4);

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.anisotropy = 4;
    return texture;
}

function Panel({ movie, index, step }: { movie: RingMovie; index: number; step: number }) {
    const { gl } = useThree();
    const material = useRef<THREE.MeshBasicMaterial>(null);
    const geometry = useMemo(() => {
        // Never wider than its slot (with a gap), so neighbours cannot overlap and z-fight.
        const theta = Math.min(PANEL_WIDTH / RADIUS, step * 0.9);
        const height = theta * RADIUS * (3 / 4);
        return new THREE.CylinderGeometry(RADIUS, RADIUS, height, 24, 1, true, index * step - theta / 2, theta);
    }, [index, step]);
    const fallback = useMemo(() => drawPoster(movie), [movie]);

    useEffect(() => {
        if (!movie.poster_url) return;
        let disposed = false;
        new THREE.TextureLoader().load(movie.poster_url, (texture) => {
            if (disposed || !material.current) return texture.dispose();
            texture.colorSpace = THREE.SRGBColorSpace;
            texture.anisotropy = Math.min(8, gl.capabilities.getMaxAnisotropy());
            material.current.map = texture;
            material.current.needsUpdate = true;
        });
        return () => {
            disposed = true;
        };
    }, [movie.poster_url, gl]);

    useEffect(() => () => {
        geometry.dispose();
        fallback.dispose();
    }, [geometry, fallback]);

    return (
        <mesh geometry={geometry}>
            <meshBasicMaterial ref={material} map={fallback} side={THREE.FrontSide} toneMapped={false} />
        </mesh>
    );
}

/** Projector dust drifting through the beam. */
function Dust({ count = 380 }: { count?: number }) {
    const points = useRef<THREE.Points>(null);
    const positions = useMemo(() => {
        const array = new Float32Array(count * 3);
        for (let index = 0; index < count; index++) {
            array[index * 3] = (Math.random() - 0.5) * 16;
            array[index * 3 + 1] = (Math.random() - 0.5) * 9;
            array[index * 3 + 2] = (Math.random() - 0.5) * 8 + 2;
        }
        return array;
    }, [count]);

    useFrame((_, delta) => {
        const geometry = points.current?.geometry;
        if (!geometry) return;
        const attribute = geometry.attributes.position as THREE.BufferAttribute;
        for (let index = 0; index < count; index++) {
            let y = attribute.getY(index) + delta * (0.08 + (index % 7) * 0.012);
            if (y > 4.5) y = -4.5;
            attribute.setY(index, y);
        }
        attribute.needsUpdate = true;
    });

    return (
        <points ref={points}>
            <bufferGeometry>
                <bufferAttribute attach="attributes-position" args={[positions, 3]} />
            </bufferGeometry>
            <pointsMaterial size={0.035} color="#f2f0ea" transparent opacity={0.45} depthWrite={false} blending={THREE.AdditiveBlending} sizeAttenuation />
        </points>
    );
}

function Ring({ movies, active, offset }: { movies: RingMovie[]; active: number; offset: number }) {
    const group = useRef<THREE.Group>(null);
    const { viewport } = useThree();
    // On wide screens the ring sits right of centre, leaving room for the headline.
    const shiftX = viewport.aspect > 1.2 ? viewport.width * offset : 0;
    const pointer = useRef({ x: 0, y: 0 });
    const rotation = useRef(0);
    const lastScroll = useRef(typeof window === 'undefined' ? 0 : window.scrollY);
    const velocity = useRef(0);

    const panels = useMemo(() => {
        if (movies.length === 0) return [];
        const repeat = Math.max(1, Math.ceil(MIN_PANELS / movies.length));
        return Array.from({ length: movies.length * repeat }, (_, index) => movies[index % movies.length]);
    }, [movies]);
    const step = (Math.PI * 2) / Math.max(1, panels.length);

    useEffect(() => {
        const onMove = (event: PointerEvent) => {
            pointer.current.x = (event.clientX / window.innerWidth) * 2 - 1;
            pointer.current.y = (event.clientY / window.innerHeight) * 2 - 1;
        };
        window.addEventListener('pointermove', onMove, { passive: true });
        return () => window.removeEventListener('pointermove', onMove);
    }, []);

    useFrame((state, delta) => {
        const node = group.current;
        if (!node) return;

        // Turn the shortest way round so the active film faces the camera.
        const target = -active * step;
        const full = Math.PI * 2;
        const diff = ((((target - rotation.current) % full) + full * 1.5) % full) - full / 2;
        rotation.current += diff * Math.min(1, delta * 2.4);

        // Scrolling flicks the ring, then it settles back.
        const scroll = window.scrollY;
        velocity.current += (scroll - lastScroll.current) * 0.0009;
        lastScroll.current = scroll;
        velocity.current *= 0.92;

        const time = state.clock.elapsedTime;
        node.rotation.y = rotation.current + velocity.current + Math.sin(time * 0.35) * 0.035;
        node.rotation.x = THREE.MathUtils.lerp(node.rotation.x, 0.06 + pointer.current.y * 0.06 + scroll * 0.00025, 0.05);
        node.rotation.z = THREE.MathUtils.lerp(node.rotation.z, -pointer.current.x * 0.035, 0.05);
        node.position.y = THREE.MathUtils.lerp(node.position.y, scroll * 0.0028, 0.12);
        node.position.x = THREE.MathUtils.lerp(node.position.x, shiftX, 0.08);
        state.camera.position.x = THREE.MathUtils.lerp(state.camera.position.x, pointer.current.x * 0.45, 0.04);
        state.camera.lookAt(0, 0, 0);
    });

    return (
        <group ref={group}>
            {panels.map((movie, index) => <Panel key={`${movie.slug}-${index}`} movie={movie} index={index} step={step} />)}
        </group>
    );
}

export default function PosterRing({ movies, active, className, offset = 0 }: { movies: RingMovie[]; active: number; className?: string; offset?: number }) {
    const wrapper = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(true);
    const [fontsReady, setFontsReady] = useState(false);
    const [theme] = useTheme();
    // The fog fades distant posters into the page colour, so it follows the theme.
    const fog = useMemo(() => token('--color-ink'), [theme]); // eslint-disable-line react-hooks/exhaustive-deps

    // The canvas posters need the condensed face loaded before they are drawn.
    useEffect(() => {
        let cancelled = false;
        Promise.all([
            document.fonts.load('850 extra-condensed 100px "Archivo Variable"'),
            document.fonts.load('700 expanded 18px "Archivo Variable"'),
        ]).finally(() => !cancelled && setFontsReady(true));
        return () => {
            cancelled = true;
        };
    }, []);

    // Stop rendering entirely while the hero is scrolled out of view.
    useEffect(() => {
        const node = wrapper.current;
        if (!node) return;
        const observer = new IntersectionObserver(([entry]) => setVisible(entry.isIntersecting), { rootMargin: '100px' });
        observer.observe(node);
        return () => observer.disconnect();
    }, []);

    return (
        <div ref={wrapper} className={className} aria-hidden="true">
            {fontsReady && (
                <Canvas dpr={[1, 1.75]} frameloop={visible ? 'always' : 'never'} camera={{ position: [0, 0.35, 12.5], fov: 30 }}
                    gl={{ antialias: true, alpha: true, powerPreference: 'high-performance' }}>
                    <fog attach="fog" args={[fog, 10, 19]} />
                    <Ring movies={movies} active={active} offset={offset} />
                    <Dust />
                </Canvas>
            )}
        </div>
    );
}
