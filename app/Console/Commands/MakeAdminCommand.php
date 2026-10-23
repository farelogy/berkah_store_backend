<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'make:admin {email : Alamat email Google pengguna yang ingin dijadikan Super Admin}';

    protected $description = 'Jadikan akun pengguna sebagai Super Admin Berkah Store';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        $user = User::where('email', $email)->first();

        if (! $user) {
            $name = $this->ask('Akun dengan email ini belum pernah login. Masukkan Nama Pengguna untuk dibuatkan akun', 'Super Admin');
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'role' => 'admin',
                'is_active' => true,
            ]);
            $this->info("Akun Super Admin baru berhasil dibuat untuk: {$email}");
        } else {
            $user->update([
                'role' => 'admin',
                'id_cabang' => null,
                'is_active' => true,
            ]);
            $this->info("Berhasil! Akun {$email} ({$user->name}) telah diangkat menjadi Super Admin.");
        }

        return Command::SUCCESS;
    }
}
