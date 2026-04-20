<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Mail;

use SchenkeIo\LaravelAuthRouter\Mail\DefaultPasskeyMailer;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;

class DefaultPasskeyMailerTest extends TestCase
{
    public function test_it_can_be_instantiated()
    {
        $mailer = new DefaultPasskeyMailer('123456');
        $this->assertEquals('123456', $mailer->code);
    }

    public function test_it_has_envelope()
    {
        $mailer = new DefaultPasskeyMailer('123456');
        $envelope = $mailer->envelope();
        $this->assertEquals('Passkey Login Code', $envelope->subject);
    }

    public function test_it_has_content()
    {
        $mailer = new DefaultPasskeyMailer('123456');
        $content = $mailer->content();
        $this->assertStringContainsString('Your login code is: 123456', $content->htmlString);
    }

    public function test_it_can_send_mail()
    {
        $mailer = new DefaultPasskeyMailer;
        $mailer->sendMail('654321');
        $this->assertEquals('654321', $mailer->code);
    }
}
