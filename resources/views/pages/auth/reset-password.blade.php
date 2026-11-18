<x-layouts::auth title="Atur ulang kata sandi">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Buat kata sandi baru" description="Gunakan kata sandi yang kuat untuk melindungi akun Anda." />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <flux:input
                name="email"
                value="{{ request('email') }}"
                label="Alamat email"
                type="email"
                required
                autocomplete="email"
            />

            <!-- Password -->
            <flux:input
                name="password"
                label="Kata sandi baru"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Masukkan kata sandi baru"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                label="Ulangi kata sandi"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Masukkan kembali kata sandi"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
                    Simpan kata sandi baru
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
