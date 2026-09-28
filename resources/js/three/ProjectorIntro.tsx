import { Canvas, useFrame, useThree } from '@react-three/fiber';
import type { MotionValue } from 'motion/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import * as THREE from 'three';

type WallMovie = { id: number; title: string; poster_url: string | null; palette: [string, string, string] };

const WALL_Z = -8;
const PROJECTOR_Z = 10;
const CARD_W = 1.6;
const CARD_H = 1.2;

/** Volumetric-looking light cone: brightest at the lens, fading and softening toward the wall. */
function Beam() {
    const material = useMemo(() => new THREE.ShaderMaterial({
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        side: THREE.DoubleSide,
        uniforms: { uTime: { value: 0 }, uColor: { value: new THREE.Color('#fff4d6') } },
        vertexShader: /* glsl */ `
            varying vec2 vUv;
            varying vec3 vNormal;
            varying vec3 vView;
            void main() {
                vUv = uv;
                vec4 world = modelViewMatrix * vec4(position, 1.0);
                vNormal = normalize(normalMatrix * normal);
                vView = normalize(-world.xyz);
                gl_Position = projectionMatrix * world;
            }`,
        fragmentShader: /* glsl */ `
            uniform float uTime;
            uniform vec3 uColor;
            varying vec2 vUv;
            varying vec3 vNormal;
            varying vec3 vView;
            void main() {
                float along = vUv.y;                                  // 1 at the lens, 0 at the wall
                float edge = pow(abs(dot(vNormal, vView)), 1.6);       // soft cone edges
                float flicker = 0.92 + 0.08 * sin(uTime * 23.0) * sin(uTime * 7.0);
                float alpha = edge * mix(0.03, 0.22, pow(along, 1.4)) * flicker;
                gl_FragColor = vec4(uColor, alpha);
            }`,
    }), []);

    const geometry = useMemo(() => {
        const cone = new THREE.CylinderGeometry(0.06, 3.1, PROJECTOR_Z - WALL_Z, 48, 1, true);
        cone.rotateX(-Math.PI / 2);
        cone.translate(0, 0, (PROJECTOR_Z + WALL_Z) / 2);
        return cone;
    }, []);

    useFrame(({ clock }) => { material.uniforms.uTime.value = clock.elapsedTime; });

    return <mesh geometry={geometry} material={material} position={[0, 1.1, 0]} />;
}

/** Dust drifting through the light. */
function Dust({ count }: { count: number }) {
    const points = useRef<THREE.Points>(null);
    const positions = useMemo(() => {
        const array = new Float32Array(count * 3);
        for (let index = 0; index < count; index++) {
            const t = Math.random();
            const z = PROJECTOR_Z - t * (PROJECTOR_Z - WALL_Z);
            const radius = 0.06 + t * 3.0 * Math.sqrt(Math.random());
            const angle = Math.random() * Math.PI * 2;
            array.set([Math.cos(angle) * radius, 1.1 + Math.sin(angle) * radius, z], index * 3);
        }
        return array;
    }, [count]);

    useFrame((_, delta) => {
        if (!points.current) return;
        points.current.rotation.z += delta * 0.02;
        points.current.position.y = Math.sin(performance.now() / 4000) * 0.05;
    });

    return (
        <points ref={points}>
            <bufferGeometry><bufferAttribute attach="attributes-position" args={[positions, 3]} /></bufferGeometry>
            <pointsMaterial size={0.035} color="#fff1c4" transparent opacity={0.55} depthWrite={false} blending={THREE.AdditiveBlending} sizeAttenuation />
        </points>
    );
}

/** A title card for films without artwork, drawn in the film's palette. */
function fallbackTexture(movie: WallMovie): THREE.Texture {
    const canvas = document.createElement('canvas');
    canvas.width = 512;
    canvas.height = 384;
    const ctx = canvas.getContext('2d') as CanvasRenderingContext2D;
    ctx.fillStyle = movie.palette[0];
    ctx.fillRect(0, 0, 512, 384);
    ctx.fillStyle = movie.palette[1];
    ctx.beginPath();
    ctx.arc(400, 140, 120, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = movie.palette[2];
    ctx.font = '800 52px "Archivo Variable", sans-serif';
    ctx.fillText(movie.title.toUpperCase().slice(0, 18), 28, 340, 456);
    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    return texture;
}

function Wall({ movies, progress }: { movies: WallMovie[]; progress: MotionValue<number> }) {
    const columns = 6;
    const [textures, setTextures] = useState<THREE.Texture[]>(() => movies.map(fallbackTexture));
    const materials = useRef<THREE.MeshBasicMaterial[]>([]);

    useEffect(() => {
        const loader = new THREE.TextureLoader();
        let alive = true;
        movies.forEach((movie, index) => {
            if (!movie.poster_url) return;
            const url = movie.poster_url.replace(/card\.webp$/, 'card-sm.webp');
            loader.load(url, (texture) => {
                if (!alive) return texture.dispose();
                texture.colorSpace = THREE.SRGBColorSpace;
                setTextures((current) => current.map((existing, position) => (position === index ? texture : existing)));
            });
        });
        return () => { alive = false; };
    }, [movies]);

    const centre = Math.floor(columns / 2) + columns; // lit first, as if the projector hits it
    useFrame(() => {
        const p = progress.get();
        materials.current.forEach((material, index) => {
            if (!material) return;
            const distanceFromCentre = Math.abs(index - centre);
            const reveal = THREE.MathUtils.clamp((p - 0.45 - distanceFromCentre * 0.025) / 0.25, 0, 1);
            const lit = index === centre ? Math.max(0.35, reveal) : reveal * 0.95;
            material.color.setScalar(0.08 + lit * 0.92);
        });
    });

    return (
        <group position={[0, 1.1, WALL_Z]}>
            {movies.map((movie, index) => {
                const column = index % columns;
                const row = Math.floor(index / columns);
                const x = (column - (columns - 1) / 2) * (CARD_W + 0.14);
                const y = (1 - row) * (CARD_H + 0.14);
                return (
                    <mesh key={movie.id} position={[x, y, 0]}>
                        <planeGeometry args={[CARD_W, CARD_H]} />
                        <meshBasicMaterial ref={(material) => { if (material) materials.current[index] = material; }} map={textures[index]} toneMapped={false} />
                    </mesh>
                );
            })}
        </group>
    );
}

/** Scroll drives the camera: behind the projector, through its beam, up to the wall. */
function Rig({ progress }: { progress: MotionValue<number> }) {
    const { camera } = useThree();
    const look = useRef(new THREE.Vector3(0, 1.1, WALL_Z));

    useFrame((_, delta) => {
        const p = progress.get();
        const eased = p < 0.5 ? 4 * p * p * p : 1 - Math.pow(-2 * p + 2, 3) / 2;
        const target = new THREE.Vector3(
            Math.sin(p * Math.PI) * 0.9,
            THREE.MathUtils.lerp(2.3, 1.15, eased),
            THREE.MathUtils.lerp(PROJECTOR_Z + 3.2, WALL_Z + 5.2, eased),
        );
        const ease = 1 - Math.pow(0.001, delta);
        camera.position.lerp(target, ease);
        look.current.lerp(new THREE.Vector3(0, 1.1, WALL_Z), ease);
        camera.lookAt(look.current);
        camera.rotation.z = Math.sin(p * Math.PI) * -0.05;
    });

    return null;
}

/** The projector body, so the first frames read as "behind the booth". */
function Projector() {
    return (
        <group position={[0, 1.1, PROJECTOR_Z + 0.55]}>
            <mesh><boxGeometry args={[0.9, 0.6, 1.0]} /><meshStandardMaterial color="#1c1b19" roughness={0.6} metalness={0.4} /></mesh>
            <mesh position={[0, 0, -0.62]} rotation={[Math.PI / 2, 0, 0]}><cylinderGeometry args={[0.16, 0.2, 0.3, 24]} /><meshStandardMaterial color="#0e0e0d" metalness={0.8} roughness={0.3} /></mesh>
            <mesh position={[0, 0, -0.78]}><circleGeometry args={[0.13, 24]} /><meshBasicMaterial color="#fff4d6" toneMapped={false} /></mesh>
            {[-0.25, 0.25].map((x) => (
                <mesh key={x} position={[x, 0.55, 0]} rotation={[0, Math.PI / 2, 0]}><torusGeometry args={[0.28, 0.04, 8, 32]} /><meshStandardMaterial color="#2a2926" metalness={0.6} roughness={0.4} /></mesh>
            ))}
        </group>
    );
}

export default function ProjectorIntro({ movies, progress, className, lite = false }: { movies: WallMovie[]; progress: MotionValue<number>; className?: string; lite?: boolean }) {
    return (
        <div className={className}>
            <Canvas dpr={lite ? [1, 1.25] : [1, 1.75]} camera={{ position: [0, 2.3, PROJECTOR_Z + 3.2], fov: 50 }} gl={{ antialias: !lite, powerPreference: 'high-performance' }}>
                <color attach="background" args={['#050505']} />
                <fog attach="fog" args={['#050505', 8, 26]} />
                <ambientLight intensity={0.25} />
                <pointLight position={[0, 1.1, PROJECTOR_Z]} intensity={6} distance={6} color="#fff4d6" />
                <Projector />
                <Beam />
                <Dust count={lite ? 260 : 700} />
                <Wall movies={movies} progress={progress} />
                <Rig progress={progress} />
            </Canvas>
        </div>
    );
}
