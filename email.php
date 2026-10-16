<?php

$resend = Resend::client(getenv('RESEND_API_KEY'));

$resend->emails->send([
  'from' => 'FlowUp <onboarding@resend.dev>',
  'to' => 'davi.santana110609@gmail.com',
  'subject' => 'Hello World',
  'html' => '<p>Congrats on sending your <strong>first email</strong>!</p>'
]);