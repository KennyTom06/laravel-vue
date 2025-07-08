<?php

namespace App\Services;

use App\Models\Amortization;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\AmortizationDelayedMail;
use App\Mail\PaymentDelayedMail;
use Exception;

class AmortizationPaymentService
{
    private EmailThrottleService $emailThrottleService;

    public function __construct(EmailThrottleService $emailThrottleService)
    {
        $this->emailThrottleService = $emailThrottleService;
    }

    /**
     * Process amortization payments for the given date
     *
     * @param string $date
     * @param mixed $output Console output for logging
     * @return array Processing results
     */
    public function payAmortizations(string $date, $output = null): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'insufficient_funds' => 0,
            'emails_sent' => 0,
            'emails_failed' => 0,
            'validation_errors' => 0,
        ];

        Log::info("Starting amortization payment processing for date: {$date}");

        // Optimized chunk size for better performance
        $chunkSize = 1000;

        try {
            // Use eager loading and query optimization
            Amortization::with(['project.promoter.user', 'payments.user'])
                ->where('schedule_date', '<=', $date)
                ->pending() // Use model scope
                ->chunk($chunkSize, function ($amortizations) use (&$results, $date, $output) {
                    $this->processAmortizationChunk($amortizations, $results, $date, $output);
                });

            Log::info("Amortization payment processing completed", $results);

        } catch (Exception $e) {
            Log::error("Amortization payment processing failed: " . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($output) $output->error("Processing failed: " . $e->getMessage());
            
            $results['failed']++;
        }

        return $results;
    }

    /**
     * Process a chunk of amortizations
     *
     * @param \Illuminate\Support\Collection $amortizations
     * @param array $results
     * @param string $date
     * @param mixed $output
     * @return void
     */
    private function processAmortizationChunk($amortizations, array &$results, string $date, $output = null): void
    {
        foreach ($amortizations as $amortization) {
            try {
                DB::transaction(function () use ($amortization, &$results, $date, $output) {
                    $this->processAmortization($amortization, $results, $date, $output);
                });
            } catch (Exception $e) {
                $results['failed']++;
                Log::error("Failed to process amortization {$amortization->id}: " . $e->getMessage(), [
                    'amortization_id' => $amortization->id,
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                if ($output) $output->error("Failed amortization ID: {$amortization->id} - " . $e->getMessage());
            }
        }
    }

    /**
     * Process a single amortization
     *
     * @param Amortization $amortization
     * @param array $results
     * @param string $date
     * @param mixed $output
     * @return void
     * @throws Exception
     */
    private function processAmortization(Amortization $amortization, array &$results, string $date, $output = null): void
    {
        // Validate amortization can be processed
        if (!$amortization->canBeProcessed()) {
            Log::warning("Amortization {$amortization->id} cannot be processed", [
                'amortization_id' => $amortization->id,
                'state' => $amortization->state,
                'schedule_date' => $amortization->schedule_date,
                'project_balance' => $amortization->project->wallet_balance,
                'required_amount' => $amortization->total_payments
            ]);
            
            if (!$amortization->project->hasSufficientFunds($amortization->total_payments)) {
                $results['insufficient_funds']++;
                if ($output) $output->warn("Project {$amortization->project->id} has insufficient funds.");
            } else {
                $results['validation_errors']++;
                if ($output) $output->warn("Amortization {$amortization->id} failed validation checks.");
            }
            return;
        }

        $project = $amortization->project;
        $payments = $amortization->payments;
        $totalAmount = $amortization->total_payments;

        // Validate payments are balanced
        if (!$amortization->paymentsBalanced()) {
            Log::error("Payment amounts don't match amortization amount for amortization {$amortization->id}", [
                'amortization_id' => $amortization->id,
                'amortization_amount' => $amortization->amount,
                'payments_total' => $totalAmount
            ]);
            
            $results['validation_errors']++;
            if ($output) $output->error("Payment amounts mismatch for amortization {$amortization->id}");
            return;
        }

        // Process the payment using model methods
        if (!$project->deductBalance($totalAmount)) {
            throw new Exception("Failed to deduct balance from project {$project->id}");
        }

        // Update payment states using model methods
        foreach ($payments as $payment) {
            if (!$payment->markAsPaid()) {
                throw new Exception("Failed to mark payment {$payment->id} as paid");
            }
        }

        // Update amortization state using model method
        if (!$amortization->markAsPaid()) {
            throw new Exception("Failed to mark amortization {$amortization->id} as paid");
        }

        $results['processed']++;
        Log::info("Successfully processed amortization {$amortization->id}", [
            'amortization_id' => $amortization->id,
            'amount' => $totalAmount,
            'project_id' => $project->id,
            'payments_count' => $payments->count()
        ]);
        
        if ($output) $output->info("Paid amortization ID: {$amortization->id}");

        // Send delayed notification emails if applicable
        if ($amortization->is_delayed) {
            $this->sendDelayedNotifications($amortization, $results, $output);
        }
    }

    /**
     * Send delayed notification emails
     *
     * @param Amortization $amortization
     * @param array $results
     * @param mixed $output
     * @return void
     */
    private function sendDelayedNotifications(Amortization $amortization, array &$results, $output = null): void
    {
        try {
            // Send email to promoter
            if ($amortization->project->promoter && $amortization->project->promoter->email) {
                $promoterEmail = $amortization->project->promoter->email;
                
                if ($this->emailThrottleService->sendThrottledEmail(
                    $promoterEmail,
                    new AmortizationDelayedMail($amortization),
                    'amortization_delayed'
                )) {
                    $results['emails_sent']++;
                    if ($output) $output->info("Sent delayed amortization notification to promoter: {$promoterEmail}");
                } else {
                    $results['emails_failed']++;
                    if ($output) $output->warn("Failed to send delayed amortization notification to promoter: {$promoterEmail}");
                }
            }

            // Collect unique user emails to prevent duplicate emails
            $userEmails = [];
            foreach ($amortization->payments as $payment) {
                if ($payment->user && $payment->user->email) {
                    $userEmails[$payment->user->email] = [
                        'user' => $payment->user,
                        'payment' => $payment
                    ];
                }
            }

            // Send emails to users with throttling
            foreach ($userEmails as $email => $data) {
                if ($this->emailThrottleService->sendThrottledEmail(
                    $email,
                    new PaymentDelayedMail($data['payment']),
                    'payment_delayed'
                )) {
                    $results['emails_sent']++;
                    if ($output) $output->info("Sent delayed payment notification to user: {$data['user']->name} ({$email})");
                } else {
                    $results['emails_failed']++;
                    if ($output) $output->warn("Failed to send delayed payment notification to user: {$data['user']->name} ({$email})");
                }
            }
        } catch (Exception $e) {
            Log::error("Failed to send delayed notifications for amortization {$amortization->id}: " . $e->getMessage(), [
                'amortization_id' => $amortization->id,
                'exception' => get_class($e)
            ]);
            
            $results['emails_failed']++;
            if ($output) $output->error("Failed to send delayed notifications for amortization {$amortization->id}");
        }
    }

    /**
     * Get payment processing statistics
     *
     * @param string $date
     * @return array
     */
    public function getPaymentStatistics(string $date): array
    {
        try {
            $totalAmortizations = Amortization::where('schedule_date', '<=', $date)->count();
            $paidAmortizations = Amortization::where('schedule_date', '<=', $date)
                ->paid() // Use model scope
                ->count();
            $pendingAmortizations = Amortization::where('schedule_date', '<=', $date)
                ->pending() // Use model scope
                ->count();
            $delayedAmortizations = Amortization::delayed()->count(); // Use model scope

            return [
                'total_amortizations' => $totalAmortizations,
                'paid_amortizations' => $paidAmortizations,
                'pending_amortizations' => $pendingAmortizations,
                'delayed_amortizations' => $delayedAmortizations,
                'completion_rate' => $totalAmortizations > 0 ? round(($paidAmortizations / $totalAmortizations) * 100, 2) : 0
            ];
        } catch (Exception $e) {
            Log::error("Failed to get payment statistics: " . $e->getMessage());
            
            return [
                'total_amortizations' => 0,
                'paid_amortizations' => 0,
                'pending_amortizations' => 0,
                'delayed_amortizations' => 0,
                'completion_rate' => 0,
                'error' => 'Failed to retrieve statistics'
            ];
        }
    }

    /**
     * Validate project has sufficient funds for all pending amortizations
     *
     * @param int $projectId
     * @return array
     */
    public function validateProjectFunds(int $projectId): array
    {
        try {
            $project = \App\Models\Project::findOrFail($projectId);
            $pendingAmount = $project->total_pending_amortizations;
            $hasSufficientFunds = $project->hasSufficientFunds($pendingAmount);

            return [
                'project_id' => $projectId,
                'wallet_balance' => $project->wallet_balance,
                'pending_amount' => $pendingAmount,
                'has_sufficient_funds' => $hasSufficientFunds,
                'deficit' => $hasSufficientFunds ? 0 : $pendingAmount - $project->wallet_balance
            ];
        } catch (Exception $e) {
            Log::error("Failed to validate project funds for project {$projectId}: " . $e->getMessage());
            
            return [
                'project_id' => $projectId,
                'error' => 'Failed to validate project funds'
            ];
        }
    }
}
