<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ResetCustomerPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-customer-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the password for all "customer" role users to their user ID.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting password reset for all "customer" users...');

        $count = 0;
        try {
            User::where('role', 'customer')->chunkById(50, function ($users) use (&$count) {
                foreach ($users as $user) {
                    $user->password = Hash::make($user->id);
                    $user->save();
                    $count++;
                    $this->output->write('.'); // Show progress
                }
            });

            $this->newLine();
            $this->info("Successfully reset the passwords for " . $count . " users.");
            return 0; // Success exit code

        } catch (\Exception $e) {
            $this->error('An error occurred during the password reset process.');
            $this->error('Error: ' . $e->getMessage());
            Log::error('Password reset command failed: ' . $e->getMessage());
            return 1; // Error exit code
        }
    }
}
