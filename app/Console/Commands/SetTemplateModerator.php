<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetTemplateModerator extends Command
{
    protected $signature = 'formora:templates:moderator {email : Email akun yang diatur} {--revoke : Cabut akses moderasi}';

    protected $description = 'Berikan atau cabut akses moderasi template komunitas untuk akun yang sudah terdaftar.';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if ($user === null) {
            $this->error('Akun tidak ditemukan.');

            return self::FAILURE;
        }

        $isModerator = ! $this->option('revoke');
        $user->forceFill(['is_platform_moderator' => $isModerator])->save();
        $this->info($isModerator ? 'Akses moderator diberikan.' : 'Akses moderator dicabut.');

        return self::SUCCESS;
    }
}
