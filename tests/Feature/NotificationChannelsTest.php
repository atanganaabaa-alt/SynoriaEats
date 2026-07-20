<?php

namespace Tests\Feature;

use App\Services\Notifications\LogNotifier;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\Notifier;
use App\Services\Notifications\TwilioSmsNotifier;
use App\Services\Notifications\TwilioWhatsAppNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationChannelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_sends_on_all_configured_channels(): void
    {
        Log::spy();

        $dispatcher = new NotificationDispatcher([
            new LogNotifier('sms'),
            new LogNotifier('whatsapp'),
        ]);

        $dispatcher->send('655000000', 'Test message');

        Log::shouldHaveReceived('info')
            ->twice()
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'SynoriaEats')
                    && in_array($context['to'], ['+237655000000'], true)
                    && $context['message'] === 'Test message';
            });
    }

    public function test_whatsapp_notifier_uses_twilio_whatsapp_prefix(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        config([
            'services.twilio.sid' => 'ACtest',
            'services.twilio.token' => 'secret',
            'services.twilio.whatsapp_from' => '14155238886',
        ]);

        app(TwilioWhatsAppNotifier::class)->send('655000000', 'Commande confirmée');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/ACtest/Messages.json'
                && $request['From'] === 'whatsapp:+14155238886'
                && $request['To'] === 'whatsapp:+237655000000'
                && $request['Body'] === 'Commande confirmée';
        });
    }

    public function test_orange_sms_notifier_calls_orange_api(): void
    {
        Http::fake([
            'api.orange.com/oauth/v3/token' => Http::response(['access_token' => 'token-abc', 'expires_in' => 3600]),
            'api.orange.com/smsmessaging/*' => Http::response(['resourceURL' => 'http://example.com/msg/1'], 201),
        ]);

        config([
            'services.orange_sms.client_id' => 'client-id',
            'services.orange_sms.client_secret' => 'client-secret',
            'services.orange_sms.sender' => '2370000',
        ]);

        app(\App\Services\Notifications\OrangeSmsNotifier::class)->send('655000000', 'Commande confirmée');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'smsmessaging/v1/outbound')
                && $request['outboundSMSMessageRequest']['outboundSMSTextMessage']['message'] === 'Commande confirmée';
        });
    }

    public function test_app_resolves_notifier_from_config_channels(): void
    {
        config(['synoria.notifications.channels' => 'log']);

        $notifier = app(Notifier::class);

        $this->assertInstanceOf(NotificationDispatcher::class, $notifier);
    }
}
