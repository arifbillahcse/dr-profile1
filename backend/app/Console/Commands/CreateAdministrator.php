<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Signature('admin:create {email? : Administrator email} {--name= : Display name} {--password-stdin : Read the password from standard input instead of an interactive prompt}')]
#[Description('Create a new administrator without placing a password in command arguments')]
class CreateAdministrator extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('password-stdin') && ! $this->input->isInteractive()) {
            $this->error('Use an interactive terminal or provide a password through --password-stdin.');

            return self::FAILURE;
        }

        $email = Str::lower(trim((string) ($this->argument('email') ?: $this->ask('Administrator email'))));
        $name = $this->option('name') ?: $this->ask('Display name');
        $password = $this->option('password-stdin') ? rtrim(stream_get_contents(STDIN), "\r\n") : $this->secret('Password');

        if (! $this->option('password-stdin') && $password !== $this->secret('Confirm password')) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $validator = Validator::make(compact('email', 'name', 'password'), [
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1024', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill(['name' => $name, 'email' => $email, 'password' => $password, 'is_admin' => true])->save();
        $this->info('Administrator created: '.$email);

        return self::SUCCESS;
    }
}
