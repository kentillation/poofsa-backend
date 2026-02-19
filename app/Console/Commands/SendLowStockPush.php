<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StockService;

class SendLowStockPush extends Command
{
    protected $signature = 'stocks:low-alerts-push';
    protected $description = 'Send low stock alerts via real-time push';

    public function handle()
    {
        $branches = \App\Models\BranchModel::all();

        foreach ($branches as $branch) {
            StockService::getLowStock($branch->shop_id, $branch->branch_id);
            $this->info("Checked low stock for Shop {$branch->shop_id}, Branch {$branch->branch_id}");
        }
    }
}
