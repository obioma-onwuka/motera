<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MOTERA — Modern Digital Banking</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body { font-family: 'Outfit', sans-serif; }
            .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
            .gradient-text { background: linear-gradient(to right, #1D4ED8, #14B8A6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        </style>
    </head>
    <body class="antialiased bg-brand-background text-brand-text-primary selection:bg-brand-primary selection:text-white">
        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-brand-border h-20 flex items-center">
            <div class="container mx-auto px-6 flex items-center justify-between">
                <a href="/" class="text-2xl font-bold tracking-tighter text-brand-primary flex items-center gap-2">
                    <div class="h-8 w-8 bg-brand-primary rounded-lg flex items-center justify-center text-white text-lg">M</div>
                    MOTERA
                </a>
                
                <div class="hidden md:flex items-center gap-8 text-sm font-medium">
                    <a href="#features" class="hover:text-brand-primary transition-colors">Features</a>
                    <a href="#about" class="hover:text-brand-primary transition-colors">About</a>
                    <a href="#open-source" class="hover:text-brand-primary transition-colors">Open Source</a>
                </div>

                <div class="flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold hover:text-brand-primary transition-colors">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-primary">Get Started</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="pt-40 pb-20 px-6">
            <div class="container mx-auto text-center max-w-4xl">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-100 text-brand-primary text-sm font-bold mb-8 animate-fade-in">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-primary"></span>
                    </span>
                    Open-Source Digital Banking Platform
                </div>
                <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-8 leading-tight">
                    The foundation for your <span class="gradient-text">next digital bank.</span>
                </h1>
                <p class="text-xl text-brand-text-secondary leading-relaxed mb-12 max-w-2xl mx-auto">
                    A serious, fintech-grade operations foundation built with Laravel. Secure, mobile-first, and fully auditable by design.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="btn-primary px-8 py-4 text-lg w-full sm:w-auto">Create Sandbox Account</a>
                    <a href="https://github.com" target="_blank" class="btn-outline px-8 py-4 text-lg w-full sm:w-auto gap-2">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                        Star on GitHub
                    </a>
                </div>
            </div>
        </section>

        <!-- Mockup Section -->
        <section class="max-w-6xl mx-auto px-6 mb-20">
            <div class="relative rounded-3xl overflow-hidden shadow-2xl border border-brand-border bg-white p-4">
                <div class="rounded-2xl border border-brand-border overflow-hidden bg-brand-background aspect-video flex items-center justify-center group cursor-pointer hover:shadow-inner transition-all">
                     <div class="text-center group-hover:scale-110 transition-transform">
                        <div class="h-20 w-20 bg-brand-primary rounded-full flex items-center justify-center text-white mx-auto mb-4 shadow-lg shadow-blue-200">
                             <svg class="h-10 w-10 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <p class="font-bold text-xl">Watch Product Overview</p>
                     </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="py-20 bg-white border-y border-brand-border px-6">
            <div class="container mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold mb-4">Everything you need to launch.</h2>
                    <p class="text-brand-text-secondary max-w-xl mx-auto">MOTERA is packed with features designed for serious financial applications.</p>
                </div>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="p-8 rounded-3xl bg-brand-background border border-brand-border hover:border-brand-primary transition-colors">
                        <div class="h-12 w-12 bg-blue-50 rounded-xl flex items-center justify-center text-brand-primary mb-6">
                             <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Double-Entry Ledger</h3>
                        <p class="text-brand-text-secondary">A bulletproof transaction engine where every penny is accounted for with traceable source records.</p>
                    </div>
                    <div class="p-8 rounded-3xl bg-brand-background border border-brand-border hover:border-brand-primary transition-colors">
                        <div class="h-12 w-12 bg-teal-50 rounded-xl flex items-center justify-center text-brand-secondary mb-6">
                             <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">KYC & Compliance</h3>
                        <p class="text-brand-text-secondary">Integrated identity verification workflows, document uploads, and tiered account limits.</p>
                    </div>
                    <div class="p-8 rounded-3xl bg-brand-background border border-brand-border hover:border-brand-primary transition-colors">
                        <div class="h-12 w-12 bg-indigo-50 rounded-xl flex items-center justify-center text-brand-primary mb-6">
                             <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Mobile First UI</h3>
                        <p class="text-brand-text-secondary">Optimized for small screens first, ensuring a premium experience for mobile banking users.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 px-6">
            <div class="container mx-auto flex flex-col md:flex-row items-center justify-between border-t border-brand-border pt-8">
                <p class="text-brand-text-secondary text-sm">© {{ date('Y') }} MOTERA Project. Released under the MIT License.</p>
                <div class="flex items-center gap-6 mt-4 md:mt-0">
                    <a href="#" class="text-sm font-medium hover:text-brand-primary">Security</a>
                    <a href="#" class="text-sm font-medium hover:text-brand-primary">License</a>
                    <a href="#" class="text-sm font-medium hover:text-brand-primary">Documentation</a>
                </div>
            </div>
        </footer>
    </body>
</html>
