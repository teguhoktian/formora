<x-layouts::auth title="Masuk">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Selamat datang kembali" description="Masuk untuk melanjutkan pekerjaan di workspace Anda." />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify label="Masuk dengan passkey" loading-label="Memverifikasi…" separator="Atau masuk dengan email" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Alamat email"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    label="Kata sandi"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        Lupa kata sandi?
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" label="Ingat saya" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    Masuk
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>Belum punya akun?</span>
            <flux:link :href="route('register')" wire:navigate>Buat akun</flux:link>
        </div>
    </div>
</x-layouts::auth>
