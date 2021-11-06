<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeleSummary extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public $bs_records;
    public $bp_records;
    public $period;

    public function __construct($user, $bp_records, $bs_records, $period)
    {
        $this->user = $user;
        $this->bs_records = $bs_records;
        $this->bp_records = $bp_records;
        $this->period = ucfirst($period);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@viedial.com', 'Viedial Healthcare')
                ->markdown('emails.reading_summary', [
                    'user' => $this->user,
                    'bs_records' => $bs_records,
                    'bp_records' => $bp_records,
                    'period'    => $period
                ]);
    }
}
