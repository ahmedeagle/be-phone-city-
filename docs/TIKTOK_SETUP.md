# TikTok setup — مدينة الهواتف

الربط لا ينشئ حملة ولا يصرف ميزانية. حدد الطائف من الجمهور في TikTok Ads Manager.
المشروعان يستخدمان نفس Pixel ID. التفعيل مقفول افتراضيًا.

## Events

- ViewContent: correct product rendered.
- AddToCart: successful add (guest and signed-in). Guest cart sync is excluded.
- InitiateCheckout: customer moves beyond cart summary; payment return URLs excluded.
- Purchase: backend only, payment_status=paid, order not cancelled, consent at checkout.
  Pending bank transfers, failed payments and frontend success URLs are not purchases.

Currency SAR. All content_id values use the parent product ID, including variants.
A future catalog must use matching IDs. No catalog feed is created here.
Purchase value uses the saved order total (including discounts/shipping/tax as calculated by checkout).
Never configure a second browser Purchase rule in Event Builder/GTM: it would double-count.

## Deployment order

1. In TikTok Events Manager create a Web data source for manual Pixel + Events API.
2. Copy the variable names from backend/.env.tiktok.example into backend deployment secrets.
   Run migrations with the integration disabled: `php artisan migrate --force`.
3. Set TIKTOK_PIXEL_ID and TIKTOK_ACCESS_TOKEN, optionally TIKTOK_TEST_EVENT_CODE.
   Then enable TIKTOK_ENABLED=true and run `php artisan config:cache`.
4. Keep Laravel scheduler running every minute:
   `* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1`.
   Multiple servers need a shared cache supporting locks. No queue worker is required.
5. Set frontend VITE_TIKTOK_PIXEL_ID (public), rebuild with `npm run build`, deploy.
   Never put the access token in any VITE variable or commit it to Git.
6. Test in Events Manager before launching ads. Remove the backend test code afterwards,
   run config:cache again, and use a new test order; an accepted order is never resent.
7. Set the ad geography to Taif. Geography is not hard-coded in tracking.

## Acceptance checks

- Before consent: no TikTok SDK or events from this integration.
- Allow tracking; view a product, add it, then start address/payment.
- Sign in with a guest cart: synchronization does not create an extra AddToCart.
- Confirm an actual test payment through the gateway: one Purchase with correct SAR total.
- Failed payment and unapproved bank transfer: no Purchase.
- Refresh success page/run `php artisan tiktok:send-purchases` twice: no duplicate.
- Withdraw consent: browser tracking stops; signed-in customer's unsent events are cleared.
- Test Arabic/English on mobile. Ad blockers can prevent browser events.

## Operations and privacy

tiktok_purchases tracks sent_at, attempts and last_error. HTTP 200 alone is insufficient;
TikTok must return code=0. Retries preserve event_id, event time and payload.
Up to 12 attempts with backoff, within 24 hours from first attempt.
Don't reset sent_at/attempts or change event_id for accepted events.
Monitor scheduler and failed deliveries; no raw provider responses or tokens are logged.
Matching data is cleared on acceptance or after 30 days; deduplication keys remain.
Cleanup runs while the integration is enabled. If disabling permanently, purge saved context/payload.
Old orders without consented attribution are not backfilled.
Withdrawing consent cannot retract an event already sent. Sign in to clear pending account orders.
Update the privacy notice to describe sharing purchase/visit data, IP, user agent,
TikTok identifiers and SHA-256 email for matching; hashing does not make it anonymous.
Configure trusted proxies correctly so request IP is the customer's IP.

## Tests

Frontend: `node --test tests/tiktok.test.mjs`, then `npm run build`.
Backend: `php artisan test --filter=TikTokPurchaseTest` (test sqlite :memory: database).
Tests use fake identifiers and mocked HTTP, never a real TikTok account.

## Official references

- [Standard events](https://ads.tiktok.com/resources/help/article/standard-events-parameters)
- [Events API](https://business-api.tiktok.com/gateway/docs/index?doc_id=1771100779668482)
- [Consent](https://business-api.tiktok.com/portal/docs/pixel-cookie-consent-mode/v1.3)
