<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Amortization;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Log::info('Starting PaymentSeeder...');

        $amortizations = Amortization::all();
        
        if ($amortizations->isEmpty()) {
            $this->command->warn('No amortizations found. Please run AmortizationSeeder first.');
            return;
        }

        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $this->command->info("Creating payments for {$amortizations->count()} amortizations...");

        foreach ($amortizations as $amortization) {
            $this->createPaymentsForAmortization($amortization, $users);
        }

        $totalPayments = Payment::count();
        $this->command->info("PaymentSeeder completed. Total payments created: {$totalPayments}");
        Log::info('PaymentSeeder completed successfully.');
    }

    /**
     * Create payments for a specific amortization
     *
     * @param Amortization $amortization
     * @param \Illuminate\Database\Eloquent\Collection $users
     * @return void
     */
    private function createPaymentsForAmortization(Amortization $amortization, $users): void
    {
        // Determine number of payments (random between 1-10 for realistic distribution)
        $numberOfPayments = rand(1, min(10, $users->count()));
        
        // Select random users for this amortization
        $selectedUsers = $users->random($numberOfPayments);
        
        // Calculate payment amounts ensuring they sum to amortization amount
        $paymentAmounts = $this->distributeAmount($amortization->amount, $numberOfPayments);
        
        $createdPayments = [];
        
        foreach ($selectedUsers as $index => $user) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'amortization_id' => $amortization->id,
                'amount' => $paymentAmounts[$index],
                'state' => $amortization->state, // Keep same state as amortization
            ]);
            
            $createdPayments[] = $payment;
        }

        // Validate that payments sum equals amortization amount
        $totalPaymentAmount = collect($createdPayments)->sum('amount');
        
        if ($totalPaymentAmount !== $amortization->amount) {
            // This should not happen, but let's log it for debugging
            Log::error("Payment amount mismatch for amortization {$amortization->id}. Expected: {$amortization->amount}, Got: {$totalPaymentAmount}");
            
            // Fix the last payment to ensure correct total
            $lastPayment = $createdPayments[count($createdPayments) - 1];
            $difference = $amortization->amount - ($totalPaymentAmount - $lastPayment->amount);
            $lastPayment->update(['amount' => $difference]);
            
            Log::info("Fixed payment amount for amortization {$amortization->id}");
        }

        $this->command->info("Created {$numberOfPayments} payments for amortization {$amortization->id} (Amount: {$amortization->amount})");
    }

    /**
     * Distribute an amount across multiple payments
     *
     * @param int $totalAmount
     * @param int $numberOfPayments
     * @return array
     */
    private function distributeAmount(int $totalAmount, int $numberOfPayments): array
    {
        if ($numberOfPayments === 1) {
            return [$totalAmount];
        }

        $amounts = [];
        $remaining = $totalAmount;
        
        // For all but the last payment, assign random amounts
        for ($i = 0; $i < $numberOfPayments - 1; $i++) {
            // Ensure minimum payment of 1% of total amount and leave enough for remaining payments
            $minAmount = max(1, (int)($totalAmount * 0.01));
            $maxAmount = (int)($remaining * 0.8); // Leave at least 20% for remaining payments
            
            // Ensure we don't go below minimum or above maximum
            $maxAmount = max($minAmount, $maxAmount);
            $amount = rand($minAmount, $maxAmount);
            
            $amounts[] = $amount;
            $remaining -= $amount;
        }
        
        // The last payment gets whatever is remaining
        $amounts[] = max(1, $remaining);
        
        return $amounts;
    }

    /**
     * Create payments with specific distribution strategy
     *
     * @param Amortization $amortization
     * @param \Illuminate\Database\Eloquent\Collection $users
     * @param string $strategy ('equal', 'random', 'weighted')
     * @return void
     */
    public function createPaymentsWithStrategy(Amortization $amortization, $users, string $strategy = 'random'): void
    {
        $numberOfPayments = rand(2, min(8, $users->count()));
        $selectedUsers = $users->random($numberOfPayments);
        
        switch ($strategy) {
            case 'equal':
                $paymentAmounts = $this->distributeEqualAmounts($amortization->amount, $numberOfPayments);
                break;
            case 'weighted':
                $paymentAmounts = $this->distributeWeightedAmounts($amortization->amount, $numberOfPayments);
                break;
            default:
                $paymentAmounts = $this->distributeAmount($amortization->amount, $numberOfPayments);
        }
        
        foreach ($selectedUsers as $index => $user) {
            Payment::create([
                'user_id' => $user->id,
                'amortization_id' => $amortization->id,
                'amount' => $paymentAmounts[$index],
                'state' => $amortization->state,
            ]);
        }
    }

    /**
     * Distribute amount equally across payments
     *
     * @param int $totalAmount
     * @param int $numberOfPayments
     * @return array
     */
    private function distributeEqualAmounts(int $totalAmount, int $numberOfPayments): array
    {
        $baseAmount = intval($totalAmount / $numberOfPayments);
        $remainder = $totalAmount % $numberOfPayments;
        
        $amounts = array_fill(0, $numberOfPayments, $baseAmount);
        
        // Distribute remainder across first few payments
        for ($i = 0; $i < $remainder; $i++) {
            $amounts[$i]++;
        }
        
        return $amounts;
    }

    /**
     * Distribute amount with weighted distribution (some users pay more)
     *
     * @param int $totalAmount
     * @param int $numberOfPayments
     * @return array
     */
    private function distributeWeightedAmounts(int $totalAmount, int $numberOfPayments): array
    {
        // Create weights (some users might be "major investors")
        $weights = [];
        for ($i = 0; $i < $numberOfPayments; $i++) {
            $weights[] = rand(1, 10); // Random weight between 1-10
        }
        
        $totalWeight = array_sum($weights);
        $amounts = [];
        $remaining = $totalAmount;
        
        for ($i = 0; $i < $numberOfPayments - 1; $i++) {
            $amount = intval(($weights[$i] / $totalWeight) * $totalAmount);
            $amounts[] = $amount;
            $remaining -= $amount;
        }
        
        // Last payment gets remainder
        $amounts[] = $remaining;
        
        return $amounts;
    }
}
