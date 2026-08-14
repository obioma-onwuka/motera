<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-black text-brand-text-primary tracking-tight">Reset password</h2>
        <p class="text-brand-text-secondary text-sm mt-2 leading-relaxed">
            Enter the email linked to your MOTERA account and we'll send you a reset link.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Email Address</label>
            <input id="email" type="email" name="email" :value="old('email')" required autofocus
                   class="w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all"
                   placeholder="you@example.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full text-base shadow-xl shadow-blue-200">
            Send Reset Link
        </button>

        <p class="text-center text-sm text-brand-text-secondary">
            <a href="{{ route('login') }}" class="font-bold text-brand-primary hover:underline">← Back to sign in</a>
        </p>
    </form>
</x-guest-layout>
