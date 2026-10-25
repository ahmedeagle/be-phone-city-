# TikTok product catalog

## Deployment (backend only)

No local database is exported, no migration is required, and no frontend build is needed.
The command reads the database configured on the machine where it runs. Run it on
production to generate the feed from real store data.

Add to the server backend .env:

    TIKTOK_CATALOG_ENABLED=true

Ensure APP_URL / ASSET_URL resolve public image URLs and FRONTEND_URL is
https://cityphonesa.com. Keep TikTok tokens private; never put them in a feed URL.

After deploying:

    php artisan config:cache
    php artisan route:clear
    php artisan tiktok:generate-catalog

Check the exported/skipped counts and the private report at
storage/app/tiktok-catalog/report.json. Open the feed:

    https://cityphonesa.com/feeds/tiktok-products.csv

If the application is hosted under another base URL, use the URL printed by the
command. Check sample product links, prices, stock and images against the site.
The feed does not include purchase costs, customer details or exact stock counts.
No products are changed in the store.

The existing Laravel scheduler regenerates the snapshot hourly when enabled.
Downloads never generate the feed on demand. An incomplete generation does not
replace the previous snapshot. Zero valid products is an error, preserving the
previous file. The endpoint returns 503 if the snapshot is over 24 hours old;
monitor refresh failures and pause affected ads if prices/stock become stale.

## Catalog setup in TikTok (requires account access)

1. Business Center > Assets > Catalogs > create a product/e-commerce catalog.
2. Select Saudi Arabia and SAR. Name it City Phones - Saudi Arabia.
3. Add products using Data Feed Schedule with the URL above. Choose a full
   replacement feed where supported so deleted/skipped products are removed;
   do not leave an incremental-only import retaining removed items.
4. Use the most frequent practical refresh offered by the account (hourly if
   available). Review import results and rejected products before advertising.
5. Connect Pixel DAU2N3RC77U88MSOCHN0 as the catalog event source and grant the
   intended advertiser account access.
6. Check event matching with actual product interactions. Inspect the diagnostic
   sample for the existing Purchase missing-content-ID warning separately;
   creating the catalog does not itself fix missing event parameters.
7. Create product sets, e.g. phones/accessories and new/used, then create a separate
   Sales > Website catalog campaign. Budget and publication remain manual.

## Mapping and boundaries

- sku_id is the parent product database ID (e.g. 1915), matching the existing
  ViewContent, AddToCart and server Purchase contents[].content_id values.
- One catalog item per product page. This does NOT separately advertise every
  color/storage option: the current tracking identifies the parent product.
- If options exist, price and stock use the first option, matching the initial
  storefront selection. Otherwise they use the product. A sold-out default
  option remains out of stock even if another option is available.
- Prices use the same final-price methods as the storefront, including offers.
  Payment-method-specific discounts are not advertised as universal discounts.
- is_new=true maps to new, false to used (matching the admin condition field).
  Confirm these flags are accurate on production before import.
- Brand comes from an is_trademark category or ancestor, never a guessed brand.
- Missing ID/slug/name/description/brand/image, invalid URLs, nonpositive price,
  and unsupported image extensions are reported and skipped. Stock zero is
  exported as out of stock, not silently discarded.
- Image URLs must be publicly accessible. This generator validates URL/extension,
  not remote availability or image dimensions. Verify TikTok import results;
  JPG/PNG images should meet the dimensions required by the selected ad format.
- A catalog is a product-data asset, not an automatically launched campaign or a
  guarantee of sales. Retargeting needs an eligible audience and configured ads.

Tests:

    php artisan test --filter=TikTokCatalogTest

Official references:
https://ads.tiktok.com/resources/help/article/catalog-product-parameters
https://ads.tiktok.com/resources/help/article/create-manage-catalogs
