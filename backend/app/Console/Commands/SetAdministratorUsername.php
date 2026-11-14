<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

#[Signature('admin:username {account : Existing administrator ID or email} {username? : New username; omit to display the current username}')]
#[Description('Read or change an existing administrator username without changing the password or privileges')]
class SetAdministratorUsername extends Command
{
    public function handle(): int
    {
        $account = trim((string) $this->argument('account'));
        $query = User::where('is_admin', true);
        $user = ctype_digit($account) ? $query->whereKey((int) $account)->first() : $query->where('email', Str::lower($account))->first();
        if (! $user) {
            $this->error('Administrator account not found.');

            return self::FAILURE;
        }
        if ($this->argument('username') === null) {
            $this->info('Administrator username: '.($user->username ?? '(not set)'));

            return self::SUCCESS;
        }
        $username = Str::lower(trim((string) $this->argument('username')));
        $validator = Validator::make(['username' => $username], [
            'username' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9._-]{2,63}$/D', 'max:64', Rule::unique('users', 'username')->ignore($user->id)],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user->update(['username' => $username]);
        $this->info('Administrator username: '.$username);

        return self::SUCCESS;
    }
}
