<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ForceDeleteExpiredProviders extends Command
{
    protected $signature = 'providers:force-delete-expired';
    protected $description = 'Force delete providers soft-deleted more than 30 days ago';

    public function handle()
    {
        $users = User::onlyTrashed()
            ->where('role', 'provider')
            ->where('deleted_at', '<', Carbon::now()->subDays(30))
            ->get();

        foreach ($users as $user) {
            $user->forceDelete();
            $this->info("Force deleted provider ID: {$user->id}");
        }

        $this->info("Completed. Deleted {$users->count()} providers.");
    }
}
