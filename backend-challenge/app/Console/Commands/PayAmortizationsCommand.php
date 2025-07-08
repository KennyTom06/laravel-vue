<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AmortizationPaymentService;

class PayAmortizationsCommand extends Command
{
    protected $signature = 'amortizations:pay {date?}';
    protected $description = 'Pay all payments for amortizations scheduled on or before a given date.';

    public function handle(AmortizationPaymentService $service)
    {
        $date = $this->argument('date') ?? now()->toDateString();
        $this->info('Processing payments for amortizations scheduled on or before: ' . $date);
        $service->payAmortizations($date, $this);
        $this->info('Done.');
    }
} 
