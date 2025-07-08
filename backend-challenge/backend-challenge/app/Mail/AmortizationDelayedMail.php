<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Amortization;

class AmortizationDelayedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $amortization;

    public function __construct(Amortization $amortization)
    {
        $this->amortization = $amortization;
    }

    public function build()
    {
        return $this->subject('Amortization Payment Delayed')
            ->view('emails.amortization_delayed')
            ->with([
                'amortization' => $this->amortization,
            ]);
    }
} 