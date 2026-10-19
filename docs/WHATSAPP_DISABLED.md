# Disable unused WhatsApp reminders

Set WHATSAPP_ENABLED=false in the backend server .env, deploy this fix, then run:

    php artisan config:cache
    php artisan queue:restart
    php artisan product-views:send-offers

The last command must print: WhatsApp is disabled; no offers queued.
The explicit setting overrides the legacy HYPERSENDER_ENABLED fallback.
The scheduler and direct command skip dispatch. Previously queued reminders exit
without sending or marking offers as sent. The service skips provider calls.
Keep the existing scheduler and queue worker cron jobs for other tasks.
No frontend rebuild or database migration is needed.

Existing failed-job records are historical and remain until selectively removed.
Use php artisan queue:failed to identify WhatsApp reminder jobs, then
php artisan queue:forget <id> for each confirmed WhatsApp job only.
Do not flush all failed jobs or clear the shared queue.

Regression tests: php artisan test --filter=WhatsAppDisabledTest
