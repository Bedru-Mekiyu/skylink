<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * The owner sets their own admin password on the server. The password is typed at a hidden prompt, so it never
 * appears in the shell history, the logs or this chat. Only an existing admin account can be changed.
 */
class SetAdminPassword extends Command
{
    protected $signature = 'admin:set-password {email : e-mail of the existing admin account}';

    protected $description = 'Set the password of an existing admin account (typed at a hidden prompt)';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->where('is_admin', true)->first();
        if (! $user) {
            $this->error('No admin account with that e-mail.');

            return self::FAILURE;
        }
        $password = $this->secret('New password');
        $v = Validator::make(['password' => $password, 'password_confirmation' => $this->secret('Type it again')], ['password' => ['required', 'confirmed', Password::defaults()]]);
        if ($v->fails()) {
            $this->error(implode(' ', $v->errors()->all()));

            return self::FAILURE;
        }
        $user->forceFill(['password' => Hash::make($password), 'remember_token' => null])->save();
        Log::notice('admin password set from the command line', ['user_id' => $user->id]);
        $this->info('Password changed for '.$user->email.'. Log in at /login.');

        return self::SUCCESS;
    }
}
