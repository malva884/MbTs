<?php

namespace App\Console\Commands;

use App\Models\ItAsset;
use App\Models\ItTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTransactionLocations extends Command
{
    protected $signature = 'it:fix-transaction-locations';
    protected $description = 'Populate missing location fields in IT transactions based on current asset location';

    public function handle()
    {
        $this->info('Starting to fix transaction locations...');

        $transactions = ItTransaction::where(function($query) {
                $query->whereNull('from_location_id')
                    ->orWhereNull('to_location_id');
            })
            ->with('asset')
            ->get();

        $this->info("Found {$transactions->count()} transactions to fix.");

        foreach ($transactions as $transaction) {
            $asset = $transaction->asset;
            
            if (!$asset) {
                $this->warn("Transaction {$transaction->id} has no asset, skipping.");
                continue;
            }

            $currentLocationId = $asset->location_id;

            if (!$currentLocationId) {
                $this->warn("Asset {$asset->id} has no location, skipping transaction {$transaction->id}.");
                continue;
            }

            DB::transaction(function () use ($transaction, $currentLocationId) {
                switch ($transaction->type) {
                    case 'Out':
                        $transaction->to_location_id = $currentLocationId;
                        break;
                    case 'In':
                    case 'Return':
                        $transaction->to_location_id = $currentLocationId;
                        break;
                    default:
                        $transaction->to_location_id = $currentLocationId;
                        break;
                }
                $transaction->save();
            });

            $this->info("Fixed transaction {$transaction->id} (type: {$transaction->type}) with location {$currentLocationId}");
        }

        $this->info('Transaction locations fix completed.');
        return Command::SUCCESS;
    }
}
