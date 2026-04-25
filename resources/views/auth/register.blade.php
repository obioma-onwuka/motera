<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-black text-brand-text-primary tracking-tight">Create your account</h2>
        <p class="text-brand-text-secondary text-sm mt-2">Open a free MOTERA sandbox account in seconds.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Full Name -->
        <div>
            <label for="name" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Full Name</label>
            <input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name"
                   class="w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all"
                   placeholder="Jane Doe">
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Email Address</label>
            <input id="email" type="email" name="email" :value="old('email')" required autocomplete="username"
                   class="w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all"
                   placeholder="you@example.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all"
                   placeholder="Min. 8 characters">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all"
                   placeholder="Re-enter password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-primary w-full text-base shadow-xl shadow-blue-200">
            Create Account
        </button>

        <!-- Login Link -->
        <p class="text-center text-sm text-brand-text-secondary">
            Already have an account?
            <a href="{{ route('login') }}" class="font-bold text-brand-primary hover:underline">Sign in instead</a>
        </p>
    </form>
</x-guest-layout>
