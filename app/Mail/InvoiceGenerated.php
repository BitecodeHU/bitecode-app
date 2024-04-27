<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public $customer_email;
    public $invoiceLink;

    /**
     * Create a new message instance.
     */
    public function __construct($customer_email, $invoiceLink)
    {
        $this->customer_email = $customer_email;
        $this->invoiceLink = $invoiceLink;
    }

    /**
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from('invoice@bitecode.hu')
            ->to($this->customer_email)
            ->subject(__('New invoice'))
            ->markdown('emails.invoice_generated')
            ->with(['invoiceLink' => $this->invoiceLink]);
    }
}
