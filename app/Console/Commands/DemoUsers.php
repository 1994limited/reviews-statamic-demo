<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\User;

/**
 * The demo's accounts: admin@example.com for the Control Panel, and Jo,
 * a reader who has bought books. All use the password "password", so this
 * only runs on local sites.
 */
class DemoUsers extends Command
{
    protected $signature = 'demo:users';

    protected $description = 'Make the demo\'s admin and reader accounts (local only)';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->components->error('Demo accounts have a known password, so they\'re only made on local sites (APP_ENV=local).');

            return self::FAILURE;
        }

        foreach ([['admin@example.com', 'Demo Admin', true], ['jo@example.com', 'Jo Reader', false]] as [$email, $name, $super]) {
            $user = User::findByEmail($email) ?? User::make()->email($email);
            $user->set('name', $name)->password('password');
            $super ? $user->makeSuper() : $user->set('super', false);
            $user->save();

            $this->components->twoColumnDetail($email.' / password', $super ? 'Control Panel' : 'Reader');
        }

        return self::SUCCESS;
    }
}
