<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\RSA;

class SSHServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('ssh', function ($app) {
            $ssh = new SSH2(config('ssh.host'));
            #$key = RSA::load(file_get_contents(config('ssh.private_key_path')));
            $key = 'Pisolo84.';
            if (!$ssh->login(config('ssh.username'), $key)) {
                Log::channel('stderr')->info('Login failed');
                throw new \Exception('Login failed');
            }
            return $ssh;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
