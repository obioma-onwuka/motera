<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'MOTERA') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>body { font-family: 'Outfit', sans-serif; }</style>
    </head>
    <body class="antialiased bg-brand-background text-brand-text-primary">
        <div class="min-h-screen flex">
            <!-- Left: Branding Panel (hidden on mobile) -->
            <div class="hidden lg:flex lg:w-1/2 bg-brand-primary relative overflow-hidden flex-col justify-between p-12">
                <div>
                    <a href="/" class="text-2xl font-bold text-white tracking-tighter flex items-center gap-2">
                        <div class="h-8 w-8 bg-white/20 rounded-lg flex items-center justify-center text-white text-lg backdrop-blur-md">M</div>
                        MOTERA
                    </a>
                </div>

                <div class="relative z-10">
                    <h1 class="text-5xl font-black text-white tracking-tight leading-tight mb-6">
                        Banking built<br>for the future.
                    </h1>
                    <p class="text-blue-200 text-lg leading-relaxed max-w-md">
                        Secure, transparent, and fully auditable. Your digital banking experience starts here.
                    </p>
                </div>

                <div class="relative z-10">
                    <p class="text-blue-300 text-sm">&copy; {{ date('Y') }} MOTERA Project. Open Source.</p>
                </div>

                <!-- Abstract design elements -->
                <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-blue-600/30 blur-3xl"></div>
                <div class="absolute -left-16 -bottom-16 h-64 w-64 rounded-full bg-blue-800/40 blur-3xl"></div>
                <div class="absolute right-20 bottom-40 h-48 w-48 rounded-full bg-teal-500/20 blur-3xl"></div>
            </div>

            <!-- Right: Form Panel -->
            <div class="w-full lg:w-1/2 flex flex-col">
                <!-- Mobile Header -->
                <div class="lg:hidden flex items-center justify-between p-6 border-b border-brand-border">
                    <a href="/" class="text-xl font-bold text-brand-primary tracking-tighter flex items-center gap-2">
                        <div class="h-7 w-7 bg-brand-primary rounded-lg flex items-center justify-center text-white text-sm">M</div>
                        MOTERA
                    </a>
                </div>

                <div class="flex-1 flex flex-col justify-center px-6 sm:px-12 lg:px-16 xl:px-24 py-12">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
