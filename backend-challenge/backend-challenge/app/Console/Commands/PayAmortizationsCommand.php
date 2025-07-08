<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AmortizationPaymentService;

class PayAmortizationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amortizations:pay {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pay all payments for amortizations scheduled on or before a given date.';

    /**
     * Execute the console command.
     *
     * @param AmortizationPaymentService $service
     * @return int
     */
    public function handle(AmortizationPaymentService $service): int
    {
        $date = $this->argument('date') ?? now()->toDateString();
        
        $this->info('Processing payments for amortizations scheduled on or before: ' . $date);
        $this->newLine();

        // Get statistics before processing
        $beforeStats = $service->getPaymentStatistics($date);
        $this->info("Before processing: {$beforeStats['pending_amortizations']} pending amortizations");

        // Process payments
        $results = $service->payAmortizations($date, $this);
        
        $this->newLine();
        $this->info('Processing completed!');
        
        // Display results
        $this->displayResults($results);
        
        // Get statistics after processing
        $afterStats = $service->getPaymentStatistics($date);
        $this->info("After processing: {$afterStats['pending_amortizations']} pending amortizations");
        $this->info("Completion rate: {$afterStats['completion_rate']}%");

        return Command::SUCCESS;
    }

    /**
     * Display processing results in a formatted table
     *
     * @param array $results
     * @return void
     */
    private function displayResults(array $results): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed Successfully', $results['processed']],
                ['Failed', $results['failed']],
                ['Insufficient Funds', $results['insufficient_funds']],
                ['Emails Sent', $results['emails_sent']],
                ['Emails Failed', $results['emails_failed']],
            ]
        );

        // Show warnings if any
        if ($results['failed'] > 0) {
            $this->warn("⚠️  {$results['failed']} amortizations failed to process. Check logs for details.");
        }

        if ($results['insufficient_funds'] > 0) {
            $this->warn("💰 {$results['insufficient_funds']} projects have insufficient funds.");
        }

        if ($results['emails_failed'] > 0) {
            $this->warn("📧 {$results['emails_failed']} emails failed to send (likely due to rate limiting).");
        }

        if ($results['processed'] > 0) {
            $this->info("✅ Successfully processed {$results['processed']} amortizations!");
        }
    }
} 