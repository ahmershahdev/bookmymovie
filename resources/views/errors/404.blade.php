@extends('layouts.app')

@section('title', '404 | BookMyMovie')

@section('content')
    <section
        class="relative flex min-h-screen items-center justify-center overflow-hidden bg-zinc-950 px-4 py-20 text-center">

            <!-- 1. Canvas Interactive Floating Ambient Particles -->
            <canvas id="ambient-particles-canvas" class="absolute inset-0 pointer-events-none z-0"></canvas>

            <!-- 2. Cinema Spotlight Ambient Lighting (Amber & Zinc Vignette) -->
            <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[700px] bg-amber-500/10 rounded-full blur-[150px]"></div>
            <div class="pointer-events-none absolute -bottom-40 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-amber-500/5 rounded-full blur-[140px]"></div>
            <div class="pointer-events-none absolute inset-0 bg-radial-vignette"></div>

            <!-- 3. 3D Perspective Container -->
            <div class="relative z-10 w-full max-w-lg perspective-1000">

                <!-- Interactive Glassmorphic Card -->
                <div id="tilt-card"
                    class="relative transform-gpu rounded-3xl border border-zinc-800/80 bg-zinc-950/80 p-8 md:p-12 backdrop-blur-2xl shadow-[0_25px_60px_rgba(0,0,0,0.9)] transition-transform duration-200 ease-out">

                    <!-- Ambient Glow Ring Behind Badge -->
                    <div class="mx-auto mb-6 relative flex h-16 w-16 items-center justify-center">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400/20 opacity-75"></span>
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-900 border border-amber-500/30 text-amber-400 shadow-[0_0_20px_rgba(245,158,11,0.2)]">
                            {{-- Film Reel SVG --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m17.25 2.625a1.125 1.125 0 001.125-1.125M20.625 19.5h-1.5c-.621 0-1.125-.504-1.125-1.125m3.75 0V5.625m0 12.75v-1.5c0-.621-.504-1.125-1.125-1.125M3.375 4.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m17.25-2.625a1.125 1.125 0 011.125 1.125M20.625 4.5h-1.5c-.621 0-1.125.504-1.125 1.125m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125" />
                            </svg>
                        </div>
                    </div>

                    <!-- Eyebrow Subtitle -->
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-amber-500/90">
                        Scene Missing
                    </p>

                    <!-- Bold 3D Gradient Header -->
                    <h1 class="mt-2 text-7xl font-extrabold tracking-tight bg-gradient-to-br from-zinc-100 via-zinc-200 to-zinc-500 bg-clip-text text-transparent drop-shadow-[0_10px_20px_rgba(0,0,0,0.8)] sm:text-8xl">
                        404
                    </h1>

                    <h2 class="mt-3 text-2xl font-bold tracking-wide text-zinc-100">
                        Feature Not Found
                    </h2>

                    <p class="mt-3 text-sm font-medium leading-relaxed text-zinc-400">
                        The requested page isn't in our current screening schedule. Let’s guide you back to the main feature.
                    </p>

                    <!-- Primary Action Button -->
                    <div class="mt-8 flex justify-center">
                        <a href="{{ route('home') }}"
                            class="group relative inline-flex items-center justify-center overflow-hidden rounded-full bg-amber-500 px-8 py-3.5 text-xs font-bold uppercase tracking-widest text-zinc-950 shadow-[0_0_25px_rgba(245,158,11,0.25)] transition-all duration-300 hover:bg-amber-400 hover:shadow-[0_0_35px_rgba(245,158,11,0.45)] hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-amber-400">
                            <svg class="mr-2 h-4 w-4 transition-transform duration-300 group-hover:-translate-x-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            Return to Homepage
                        </a>
                    </div>

                </div>
            </div>
        </section>

        <!-- Custom CSS Styles -->
        <style nonce="{{ $cspNonce ?? '' }}">
            .perspective-1000 {
                perspective: 1000px;
            }
            .bg-radial-vignette {
                background: radial-gradient(circle at center, transparent 30%, rgba(9, 9, 11, 0.8) 100%);
            }
        </style>

        <!-- Interactive Canvas & Glassmorphic Tilt Logic -->
        <script nonce="{{ $cspNonce ?? '' }}">
            document.addEventListener('DOMContentLoaded', () => {
                // 1. Mouse-Driven 3D Card Tilt Effect
                const card = document.getElementById('tilt-card');

                document.addEventListener('mousemove', (e) => {
                    const { innerWidth, innerHeight } = window;
                    const x = (e.clientX - innerWidth / 2) / (innerWidth / 2);
                    const y = (e.clientY - innerHeight / 2) / (innerHeight / 2);

                    const tiltX = -y * 10;
                    const tiltY = x * 10;

                    card.style.transform = `rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateZ(10px)`;
                });

                document.addEventListener('mouseleave', () => {
                    card.style.transform = `rotateX(0deg) rotateY(0deg) translateZ(0px)`;
                });

                // 2. Interactive Gold Floating Dust Particles Background
                const canvas = document.getElementById('ambient-particles-canvas');
                const ctx = canvas.getContext('2d');

                let width, height;
                function resize() {
                    width = canvas.width = window.innerWidth;
                    height = canvas.height = window.innerHeight;
                }
                resize();
                window.addEventListener('resize', resize);

                const numParticles = 40;
                const particles = [];

                for (let i = 0; i < numParticles; i++) {
                    particles.push({
                        x: Math.random() * width,
                        y: Math.random() * height,
                        radius: Math.random() * 2 + 0.5,
                        vx: (Math.random() - 0.5) * 0.4,
                        vy: (Math.random() - 0.5) * 0.4,
                        alpha: Math.random() * 0.6 + 0.2
                    });
                }

                function animate() {
                    ctx.clearRect(0, 0, width, height);

                    particles.forEach(p => {
                        p.x += p.vx;
                        p.y += p.vy;

                        if (p.x < 0) p.x = width;
                        if (p.x > width) p.x = 0;
                        if (p.y < 0) p.y = height;
                        if (p.y > height) p.y = 0;

                        ctx.beginPath();
                        ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                        ctx.fillStyle = `rgba(245, 158, 11, ${p.alpha})`;
                        ctx.fill();
                    });

                    requestAnimationFrame(animate);
                }

                animate();
            });
        </script>
@endsection
