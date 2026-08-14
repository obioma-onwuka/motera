<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-black text-brand-text-primary tracking-tight">Welcome back</h2>
        <p class="text-brand-text-secondary text-sm mt-2">Sign in to access your MOTERA account.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Email Address</label>
            <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                   class="w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all" 
                   placeholder="you@example.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-bold text-brand-primary hover:underline" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all"
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center gap-2">
            <input id="remember_me" type="checkbox" class="rounded-md border-brand-border text-brand-primary shadow-sm focus:ring-brand-primary h-5 w-5" name="remember">
            <label for="remember_me" class="text-sm text-brand-text-secondary font-medium">Keep me signed in</label>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-primary w-full text-base shadow-xl shadow-blue-200">
            Sign In
        </button>

        <!-- Register Link -->
        <p class="text-center text-sm text-brand-text-secondary">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-bold text-brand-primary hover:underline">Create one for free</a>
        </p>
    </form>
</x-guest-layout>
