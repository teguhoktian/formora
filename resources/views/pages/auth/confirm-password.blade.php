<x-layouts::auth title="Konfirmasi kata sandi">
    <div class="flex flex-col gap-6">
        <x-auth-header
            title="Konfirmasi identitas"
            description="Masukkan kembali kata sandi untuk melanjutkan ke area yang dilindungi."
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            label="Konfirmasi dengan passkey"
            loading-label="Mengonfirmasi…"
            separator="Atau gunakan kata sandi"
        />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                label="Kata sandi"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan kata sandi"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                Konfirmasi
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
