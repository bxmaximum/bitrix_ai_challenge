---
name: bitrix-catalog
description: Covers Trade Catalog module — products, SKU/offers, prices, inventory, discounts, bundles, export/import, catalog API choice. Applied for e-commerce features requiring prices, stock, and sale integration. Key terms — catalog, product, SKU, offer, price type, CCatalogProduct, catalog module.
---

# Trade Catalog Module

Catalog adds commerce data to iblock elements. Requires both `iblock` (content) and `catalog` (trade data) modules. Works with `sale` for cart and orders.

## Core Concepts

| Term | Meaning |
| --- | --- |
| Product iblock | Iblock with product cards (name, images, properties) |
| Catalog | Product iblock linked to `catalog` module |
| Product | Iblock element with trade params (price, quantity, availability) |
| SKU / Offer | Variant with own price/stock (separate offers iblock) |
| Price type | Retail, wholesale, etc. — with access rights |
| Warehouse | Storage location; stock per warehouse |

Product ID = iblock element ID. Trade data in `b_catalog_product`, prices in `b_catalog_price`.

## Product Types

- **Simple** — one element, own prices and stock.
- **With offers (SKU)** — parent in product iblock, variants in linked offers iblock. Customer buys a specific offer.
- **Sets/bundles** — multiple products sold together.
- **Service** — no warehouse tracking.

## API Choice

- **D7 ORM** — `Bitrix\Catalog\ProductTable`, `PriceTable` where available; preferred for new code.
- **Legacy `CCatalogProduct`**, `CPrice` — compatibility, some admin operations.
- For new catalog APIs in recent main versions, inspect `bitrix/modules/catalog/lib/` in the project.

Always `Loader::includeModule('catalog')` and `Loader::includeModule('iblock')`.

## Typical Workflow

1. Create/configure product iblock with `API_CODE`.
2. Link iblock to catalog (make it a catalog).
3. Set product type (simple / SKU).
4. Add prices via price types.
5. Manage stock (quantity accounting, warehouses).
6. Integrate with `sale` basket via standard components or custom services.

## Prices and Availability

- Multiple price types per product/offer.
- VAT rate and "VAT included" flag.
- Subscribe to out-of-stock products.
- Availability rules depend on catalog settings (allow buy when zero, etc.).

## Performance

- Batch price/stock updates instead of per-item calls in loops.
- Cache product list queries; warm cache after bulk import.
- Load catalog data with ORM collections, not N+1 `CCatalogProduct::GetByID` in templates.

## Checklist

- [ ] Both `iblock` and `catalog` modules included.
- [ ] Product iblock has `API_CODE` and catalog binding.
- [ ] SKU products use linked offers iblock correctly.
- [ ] Price types and access rights configured.
- [ ] Stock updates use catalog API, not direct SQL.
- [ ] Discounts/coupons via catalog rules, not hardcoded prices.
