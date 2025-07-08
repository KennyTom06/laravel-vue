<?php

namespace App\Services;

use App\Models\Amortization;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AmortizationDelayedMail;
use App\Mail\PaymentDelayedMail;

class AmortizationPaymentService
{
    public function payAmortizations($date, $output = null)
    {
        Amortization::with(['project', 'payments', 'project.promoter.user'])
            ->where('schedule_date', '<=', $date)
            ->where('state', '!=', 'paid')
            ->chunk(100, function ($amortizations) use ($date, $output) {
                foreach ($amortizations as $amortization) {
                    DB::transaction(function () use ($amortization, $date, $output) {
                        $project = $amortization->project;
                        $payments = $amortization->payments;
                        $total = $payments->sum('amount');

                        if ($project->wallet_balance < $total) {
                            if ($output) $output->error('Project ' . $project->id . ' does not have enough balance.');
                            return;
                        }

                        $project->wallet_balance -= $total;
                        $project->save();

                        foreach ($payments as $payment) {
                            $payment->state = 'paid';
                            $payment->save();
                        }
                        $amortization->state = 'paid';
                        $amortization->save();

                         if (now()->gt($amortization->schedule_date)) {

                             if ($amortization->project->promoter && $amortization->project->promoter->user) {
                                 Mail::to($amortization->project->promoter->user->email)
                                     ->send(new AmortizationDelayedMail($amortization));
                             }

                             foreach ($payments as $payment) {
                                 if ($payment->user) {
                                     $userName = $payment->user->name;
                                     $userEmail = $payment->user->email;
                                     if ($output) $output->info("Sending delayed mail to user: {$userName} ({$userEmail})");
                                     Mail::to($userEmail)
                                         ->send(new PaymentDelayedMail($payment));
                                 }
                             }
                         }
                        if ($output) $output->info('Paid amortization ID: ' . $amortization->id);
                    });
                }
            });
    }
}
