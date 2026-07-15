<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature = 'encore:create-super-admin {email} {--name=Encore Administrator}';

    protected $description = 'Create an active Encore super administrator';

    public function handle(): int
    {
        $password = $this->secret('Password (at least 10 characters)');
        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $this->option('name'),
            'email' => $this->argument('email'),
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $this->option('name'),
            'email' => $this->argument('email'),
            'password' => $password,
            'role' => 'super_admin',
            'is_active' => true,
            'organisation_id' => null,
        ]);

        $this->info('Encore super administrator created.');

        return self::SUCCESS;
    }
}
