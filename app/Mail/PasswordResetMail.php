<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
  use Queueable, SerializesModels;

  public $resetLink;

  public function __construct($resetLink)
  {
    $this->resetLink = $resetLink;
  }

  public function build()
  {
    return $this->view('emails.password_reset')
      ->subject('Enlace de Restablecimiento de Contraseña')
      ->with(['resetLink' => $this->resetLink]);
  }
}
