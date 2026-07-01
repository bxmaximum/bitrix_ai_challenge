---
name: bitrix-performance
description: Covers Bitrix performance — composite site, query optimization, replication/clustering, sharding, hot/cold sessions. Applied for high-load optimization beyond basic caching. Key terms — composite, NGINX, replication, sharding, query optimization.
---

# Performance Optimization

Complements `bitrix-caching` and `bitrix-sessions` with site-wide and infrastructure patterns.

## Composite Site

Technology caching static HTML while loading dynamic blocks via AJAX.

1. Mark dynamic zones: `<div data-dynamic="true">...</div>` or frame mode APIs.
2. Enable in Admin → Settings → Composite Site (Autocomposite or Composite mode).
3. Configure NGINX to serve composite cache pool directly.
4. Clear component cache before enabling.

Modes:
- **Autocomposite** — kernel auto-detects static/dynamic.
- **Composite** — manual zone configuration.

NGINX: point `try_files` to the composite cache pool directory (BitrixVM: *Configure nginx to use composite cache*).

Do not put personalized data in static zone (cart, user name, permissions).

## Query Optimization

- Limit ORM `select` fields.
- Use indexes matching `filter`/`order` columns.
- Avoid N+1 — `fetchCollection()` with relations.
- Batch operations instead of per-row updates.
- Enable ORM query cache where appropriate.
- Use `SqlTracker` in dev to find slow queries (skill `bitrix-database`).

## Replication and Clustering

- MySQL master-slave for read scaling.
- Additional connections in `.settings.php` `connections` section.
- Read-only analytics queries → separate connection.

## Sharding

Horizontal partitioning for very large tables (enterprise scenarios). Kernel support varies by edition.

## Hot/Cold Sessions

Related to separated session mode (`bitrix-sessions`):

- Hot data (kernel `$_SESSION['BX']`) → encrypted cookies.
- Cold data → Redis/DB backend.
- Reduces storage round-trips on every hit.

## Checklist

- [ ] Composite tested with all dynamic blocks (cart, auth, personal).
- [ ] NGINX composite cache configured in production.
- [ ] Slow queries identified and indexed.
- [ ] Session backend matches load (Redis for high-traffic).
- [ ] File cache replaced with Redis/Memcached in production.
