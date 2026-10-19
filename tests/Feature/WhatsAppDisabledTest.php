<?php

namespace Tests\Feature;

use App\Jobs\SendAbandonedProductViewOffer;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppDisabledTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp.enabled' => false]);
        Http::fake();
    }

    public function test_disabled_command_does_not_query_views_or_queue_reminders(): void
    {
        Queue::fake();

        // No tables are created: disabled dispatch must exit before querying.
        $this->artisan('product-views:send-offers')
            ->expectsOutput('WhatsApp is disabled; no offers queued.')
            ->assertSuccessful();

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_previously_queued_job_skips_without_marking_offer_sent(): void
    {
        $view = \Mockery::mock(ProductView::class);
        $view->shouldNotReceive('fresh');
        $view->shouldNotReceive('markOfferSent');
        $service = \Mockery::mock(WhatsAppService::class);
        $service->shouldNotReceive('sendProductOffer');

        (new SendAbandonedProductViewOffer($view))->handle($service);

        Http::assertNothingSent();
    }

    public function test_disabled_service_does_not_send_or_poll_provider(): void
    {
        $service = new WhatsAppService();
        $user = new User();

        $this->assertFalse($service->sendMessage($user, 'Test'));
        $this->assertFalse($service->sendProductOffer($user, new Product()));
        $this->assertNull($service->checkQueuedRequestStatus('https://example.com/status', 'test'));
        Http::assertNothingSent();
    }
}
