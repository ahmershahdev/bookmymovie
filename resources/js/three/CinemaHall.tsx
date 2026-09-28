import { Canvas, useFrame, useThree, type ThreeEvent } from '@react-three/fiber';
import { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import * as THREE from 'three';
import type { Palette } from '@/types';

import { computeLayout, EYE_HEIGHT, FIRST_ROW_Z, ROW_DEPTH, ROW_RISE, SCREEN_HEIGHT, SCREEN_WIDTH, SCREEN_Y, SCREEN_Z, type HallRow, type HallSeat, type Layout } from './hallLayout';

export type { HallRow, HallSeat };

const TIER_COLOURS: Record<string, string> = { Gold: '#8f8b82', Platinum: '#c7e03a', Box: '#9a82e0' };

function useLayout(rows: HallRow[], aisles: number[]): Layout {
    return useMemo(() => computeLayout(rows, aisles), [rows, aisles]);
}

/** The picture on the screen: the film's artwork, or a title card in its palette. */
function useScreenTexture(image: string | null | undefined, title: string, palette: Palette) {
    const [texture, setTexture] = useState<THREE.Texture>(() => {
        const canvas = document.createElement('canvas');
        canvas.width = 1280;
        canvas.height = 582;
        const ctx = canvas.getContext('2d') as CanvasRenderingContext2D;
        const [ground, accent, paper] = palette;
        const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        gradient.addColorStop(0, ground);
        gradient.addColorStop(1, '#050505');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = accent;
        ctx.globalAlpha = 0.9;
        ctx.beginPath();
        ctx.arc(canvas.width * 0.78, canvas.height * 0.45, canvas.height * 0.42, 0, Math.PI * 2);
        ctx.fill();
        ctx.globalAlpha = 1;
        ctx.fillStyle = paper;
        ctx.font = '850 extra-condensed 150px "Archivo Variable", sans-serif';
        ctx.textBaseline = 'bottom';
        ctx.fillText(title.toUpperCase(), 70, canvas.height - 70, canvas.width * 0.8);
        const canvasTexture = new THREE.CanvasTexture(canvas);
        canvasTexture.colorSpace = THREE.SRGBColorSpace;
        return canvasTexture;
    });

    useEffect(() => {
        if (!image) return;
        let alive = true;
        new THREE.TextureLoader().load(image, (loaded) => {
            if (!alive) return loaded.dispose();
            loaded.colorSpace = THREE.SRGBColorSpace;
            setTexture(loaded);
        });
        return () => {
            alive = false;
        };
    }, [image]);

    return texture;
}

/**
 * The trailer as a texture, muted and looping, only while you sit in a seat.
 * WebM first, MP4 fallback; if it cannot play, the still stays up.
 */
function useTrailerTexture(sources: { webm?: string | null; mp4?: string | null } | undefined, playing: boolean) {
    const [texture, setTexture] = useState<THREE.VideoTexture | null>(null);

    useEffect(() => {
        if (!playing || !sources?.mp4) return;
        const video = document.createElement('video');
        video.muted = true;
        video.loop = true;
        video.playsInline = true;
        video.crossOrigin = 'anonymous';
        video.preload = 'auto';
        const canWebm = sources.webm && video.canPlayType('video/webm; codecs="vp9"') !== '';
        video.src = (canWebm ? sources.webm : sources.mp4) as string;
        const videoTexture = new THREE.VideoTexture(video);
        videoTexture.colorSpace = THREE.SRGBColorSpace;
        let alive = true;
        video.addEventListener('playing', () => alive && setTexture(videoTexture), { once: true });
        video.play().catch(() => undefined);
        return () => {
            alive = false;
            setTexture(null);
            video.pause();
            video.removeAttribute('src');
            video.load();
            videoTexture.dispose();
        };
    }, [playing, sources?.webm, sources?.mp4]);

    return texture;
}

/** House lights: bright in the overview, dimmed like showtime in a seat. */
function HouseLights({ dim }: { dim: boolean }) {
    const ambient = useRef<THREE.AmbientLight>(null);
    const hemi = useRef<THREE.HemisphereLight>(null);
    useFrame((_, delta) => {
        const ease = 1 - Math.pow(0.05, delta);
        if (ambient.current) ambient.current.intensity = THREE.MathUtils.lerp(ambient.current.intensity, dim ? 0.08 : 0.35, ease);
        if (hemi.current) hemi.current.intensity = THREE.MathUtils.lerp(hemi.current.intensity, dim ? 0.06 : 0.35, ease);
    });
    return (
        <>
            <ambientLight ref={ambient} intensity={0.35} />
            <hemisphereLight ref={hemi} args={['#fff6e0', '#0b0a09', 0.35]} />
        </>
    );
}

function Seats({ layout, selected, focus, hovered, onHover, onToggle }: {
    layout: Layout;
    selected: number[];
    focus: number | null;
    hovered: number | null;
    onHover: (seat: HallSeat | null) => void;
    onToggle: (seat: HallSeat) => void;
}) {
    const cushions = useRef<THREE.InstancedMesh>(null);
    const backs = useRef<THREE.InstancedMesh>(null);
    const cushionGeometry = useMemo(() => new THREE.BoxGeometry(0.5, 0.16, 0.46), []);
    const backGeometry = useMemo(() => new THREE.BoxGeometry(0.5, 0.52, 0.1), []);

    useLayoutEffect(() => {
        const matrix = new THREE.Matrix4();
        const colour = new THREE.Color();
        layout.forEach(({ seat, x, y, z }, index) => {
            matrix.makeTranslation(x, y + 0.3, z);
            cushions.current?.setMatrixAt(index, matrix);
            matrix.makeTranslation(x, y + 0.58, z + 0.22);
            backs.current?.setMatrixAt(index, matrix);

            if (selected.includes(seat.id)) colour.set(seat.id === focus ? '#ffffff' : '#f2f0ea');
            else if (seat.status === 'reserved') colour.set('#7a2414');
            else if (!seat.available) colour.set('#1f1f1f');
            else colour.set(TIER_COLOURS[seat.tier] ?? '#8f8b82');
            if (seat.id === hovered && seat.available && !selected.includes(seat.id)) colour.lerp(new THREE.Color('#ffffff'), 0.45);

            cushions.current?.setColorAt(index, colour);
            backs.current?.setColorAt(index, colour);
        });
        [cushions.current, backs.current].forEach((mesh) => {
            if (!mesh) return;
            mesh.instanceMatrix.needsUpdate = true;
            if (mesh.instanceColor) mesh.instanceColor.needsUpdate = true;
        });
    }, [layout, selected, focus, hovered]);

    const seatAt = (event: ThreeEvent<PointerEvent | MouseEvent>) => (event.instanceId === undefined ? null : layout[event.instanceId]?.seat ?? null);
    const handlers = {
        onPointerMove: (event: ThreeEvent<PointerEvent>) => {
            event.stopPropagation();
            const seat = seatAt(event);
            onHover(seat);
            document.body.style.cursor = seat?.available ? 'pointer' : 'default';
        },
        onPointerOut: () => {
            onHover(null);
            document.body.style.cursor = 'default';
        },
        onClick: (event: ThreeEvent<MouseEvent>) => {
            event.stopPropagation();
            const seat = seatAt(event);
            if (seat?.available) onToggle(seat);
        },
    };

    return (
        <>
            <instancedMesh ref={cushions} args={[cushionGeometry, undefined, layout.length]} {...handlers}>
                <meshStandardMaterial roughness={0.7} metalness={0.05} />
            </instancedMesh>
            <instancedMesh ref={backs} args={[backGeometry, undefined, layout.length]} {...handlers}>
                <meshStandardMaterial roughness={0.7} metalness={0.05} />
            </instancedMesh>
        </>
    );
}

function Room({ layout, texture, accent }: { layout: Layout; texture: THREE.Texture; accent: string }) {
    const depth = layout.length ? Math.max(...layout.map((item) => item.z)) + 3 : 14;
    const rise = layout.length ? Math.max(...layout.map((item) => item.y)) : 3;
    const width = layout.length ? Math.max(...layout.map((item) => Math.abs(item.x))) * 2 + 5 : 14;
    const screenGeometry = useMemo(() => {
        // A gently curved screen: a slice of a very large cylinder.
        const radius = 18;
        const theta = SCREEN_WIDTH / radius;
        const geometry = new THREE.CylinderGeometry(radius, radius, SCREEN_HEIGHT, 48, 1, true, Math.PI - theta / 2, theta);
        geometry.translate(0, 0, radius);
        geometry.scale(-1, 1, 1);
        return geometry;
    }, []);

    return (
        <group>
            {/* Screen and its glow */}
            <mesh geometry={screenGeometry} position={[0, SCREEN_Y, SCREEN_Z]}>
                <meshBasicMaterial map={texture} side={THREE.DoubleSide} toneMapped={false} />
            </mesh>
            <mesh position={[0, SCREEN_Y, SCREEN_Z - 0.3]}>
                <planeGeometry args={[SCREEN_WIDTH + 1.6, SCREEN_HEIGHT + 1.2]} />
                <meshBasicMaterial color="#000000" />
            </mesh>
            <pointLight position={[0, SCREEN_Y, SCREEN_Z + 2]} intensity={28} distance={22} color={accent} decay={1.6} />
            <pointLight position={[0, SCREEN_Y + 1, SCREEN_Z + 6]} intensity={10} distance={26} color="#ffffff" decay={1.8} />

            {/* Stepped floor under each row */}
            {Array.from(new Set(layout.map((item) => item.z))).map((z, index) => (
                <mesh key={z} position={[0, (index * ROW_RISE + 0.1) / 2 - 0.1, z + 0.1]}>
                    <boxGeometry args={[width, index * ROW_RISE + 0.1, ROW_DEPTH]} />
                    <meshStandardMaterial color="#161514" roughness={0.95} />
                </mesh>
            ))}
            {/* Aisle step lights */}
            {Array.from(new Set(layout.map((item) => item.z))).map((z, index) => (
                <mesh key={`light-${z}`} position={[-width / 2 + 0.35, index * ROW_RISE + 0.02, z - 0.38]}>
                    <boxGeometry args={[0.35, 0.02, 0.04]} />
                    <meshBasicMaterial color="#e3ff3b" toneMapped={false} />
                </mesh>
            ))}

            {/* Front floor, walls and ceiling */}
            <mesh rotation={[-Math.PI / 2, 0, 0]} position={[0, -0.1, (SCREEN_Z + FIRST_ROW_Z) / 2]}>
                <planeGeometry args={[width, FIRST_ROW_Z - SCREEN_Z + 1]} />
                <meshStandardMaterial color="#121110" roughness={1} />
            </mesh>
            {[-1, 1].map((side) => (
                <mesh key={side} position={[side * width / 2, rise / 2 + 3, (SCREEN_Z + depth) / 2]} rotation={[0, -side * Math.PI / 2, 0]}>
                    <planeGeometry args={[depth - SCREEN_Z, rise + 8]} />
                    <meshStandardMaterial color="#1b1716" roughness={0.9} />
                </mesh>
            ))}
            <mesh position={[0, rise + 7, (SCREEN_Z + depth) / 2]} rotation={[Math.PI / 2, 0, 0]}>
                <planeGeometry args={[width, depth - SCREEN_Z]} />
                <meshStandardMaterial color="#0c0b0a" />
            </mesh>
        </group>
    );
}

/**
 * Camera director. Overview: drag to orbit around the room. Seat view: sit
 * at the chosen seat's eye height looking at the screen; moving the pointer
 * turns your head a little, like looking around a real auditorium.
 */
function Director({ layout, focus, mode }: { layout: Layout; focus: number | null; mode: 'overview' | 'seat' }) {
    const { camera, gl } = useThree();
    const orbit = useRef({ yaw: 0, pitch: 0.42, dragging: false, lastX: 0, lastY: 0 });
    const look = useRef({ x: 0, y: 0 });
    const target = useRef(new THREE.Vector3(0, SCREEN_Y - 0.6, SCREEN_Z));
    const center = useMemo(() => {
        const depth = layout.length ? Math.max(...layout.map((item) => item.z)) : 12;
        return new THREE.Vector3(0, 1.4, (FIRST_ROW_Z + depth) / 2);
    }, [layout]);

    useEffect(() => {
        const element = gl.domElement;
        const down = (event: PointerEvent) => {
            orbit.current.dragging = true;
            orbit.current.lastX = event.clientX;
            orbit.current.lastY = event.clientY;
        };
        const move = (event: PointerEvent) => {
            const rect = element.getBoundingClientRect();
            look.current.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            look.current.y = ((event.clientY - rect.top) / rect.height) * 2 - 1;
            if (!orbit.current.dragging || mode !== 'overview') return;
            orbit.current.yaw -= (event.clientX - orbit.current.lastX) * 0.006;
            orbit.current.pitch = THREE.MathUtils.clamp(orbit.current.pitch + (event.clientY - orbit.current.lastY) * 0.004, 0.12, 1.1);
            orbit.current.lastX = event.clientX;
            orbit.current.lastY = event.clientY;
        };
        const up = () => {
            orbit.current.dragging = false;
        };
        element.addEventListener('pointerdown', down);
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', up);
        return () => {
            element.removeEventListener('pointerdown', down);
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', up);
        };
    }, [gl, mode]);

    useFrame((_, delta) => {
        const seat = focus !== null ? layout.find((item) => item.seat.id === focus) : undefined;
        const desired = new THREE.Vector3();
        const aim = new THREE.Vector3();

        if (mode === 'seat' && seat) {
            desired.set(seat.x, seat.y + 0.3 + EYE_HEIGHT, seat.z + 0.05);
            aim.set(look.current.x * 3.2, SCREEN_Y - 0.4 - look.current.y * 1.4, SCREEN_Z);
        } else {
            const distance = 17;
            desired.set(
                center.x + Math.sin(orbit.current.yaw) * distance * Math.cos(orbit.current.pitch),
                center.y + Math.sin(orbit.current.pitch) * distance,
                center.z + Math.cos(orbit.current.yaw) * distance * Math.cos(orbit.current.pitch),
            );
            aim.set(0, 1.6, (center.z + SCREEN_Z) / 2);
        }

        const ease = 1 - Math.pow(0.02, delta);
        camera.position.lerp(desired, ease);
        target.current.lerp(aim, ease);
        camera.lookAt(target.current);
    });

    return null;
}

export default function CinemaHall({ rows, aisles, selected, focus, mode, onToggle, onHover, screenImage, trailer, title, palette, className }: {
    rows: HallRow[];
    aisles: number[];
    selected: number[];
    focus: number | null;
    mode: 'overview' | 'seat';
    onToggle: (seat: HallSeat) => void;
    onHover?: (seat: HallSeat | null) => void;
    screenImage?: string | null;
    trailer?: { webm?: string | null; mp4?: string | null };
    title: string;
    palette: Palette;
    className?: string;
}) {
    const layout = useLayout(rows, aisles);
    const [hovered, setHovered] = useState<number | null>(null);
    const still = useScreenTexture(screenImage, title, palette);
    const seated = mode === 'seat' && focus !== null;
    const video = useTrailerTexture(trailer, seated);
    const texture = video ?? still;

    useEffect(() => () => {
        document.body.style.cursor = 'default';
    }, []);

    return (
        <div className={className}>
            <Canvas dpr={[1, 1.75]} camera={{ position: [0, 9, 26], fov: 46 }} gl={{ antialias: true, powerPreference: 'high-performance' }}>
                <color attach="background" args={['#070707']} />
                <fog attach="fog" args={['#070707', 18, 42]} />
                <HouseLights dim={seated} />
                <Room layout={layout} texture={texture} accent={palette[1]} />
                <Seats layout={layout} selected={selected} focus={focus} hovered={hovered}
                    onHover={(seat) => { setHovered(seat?.id ?? null); onHover?.(seat); }} onToggle={onToggle} />
                <Director layout={layout} focus={focus} mode={mode} />
            </Canvas>
        </div>
    );
}
