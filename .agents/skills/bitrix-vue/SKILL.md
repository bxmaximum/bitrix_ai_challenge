---
name: bitrix-vue
description: Covers BitrixVue 3 — ui.vue3 extension, createApp, integration with Bitrix localization and REST, migration from Vue 2. Applied when building reactive admin/public UI with Vue inside Bitrix. Key terms — BitrixVue, ui.vue3, Vue 3, createApp.
---

# BitrixVue 3

BitrixVue 3 wraps Vue 3 for use inside Bitrix (ui module 22.100+). **Vue 2 is deprecated.**

Features:
- Same Vue 3 API with Bitrix integrations (loc, events, REST).
- Single shared Vue version across product — no `window.Vue` pollution.
- Not suitable for SSR or SFC `.vue` files requiring server compilation.

## Loading

### In extension (preferred)

```javascript
import { BitrixVue } from 'ui.vue3';

BitrixVue.createApp({
    data() {
        return { items: [] };
    },
    mounted() {
        this.loadItems();
    },
    methods: {
        loadItems() {
            BX.ajax.runAction('vendor:module.item.list').then((r) => {
                this.items = r.data.items;
            });
        },
    },
    template: '<div><div v-for="item in items" :key="item.id">{{ item.name }}</div></div>',
}).mount('#app');
```

`bundle.config.js` — add `ui.vue3` to `rel` in `config.php`.

### On PHP page (legacy)

```php
\Bitrix\Main\UI\Extension::load('ui.vue3');
```

```html
<div id="app"></div>
<script>
    BX.BitrixVue.createApp({ /* ... */ }).mount('#app');
</script>
```

## Localization

```javascript
import { Loc } from 'ui.vue3';

// Messages from lang files loaded via Extension
Loc.getMessage('VENDOR_MODULE_ITEM_TITLE');
```

Register messages in extension `lang/` and load via `Extension::load`.

## REST Integration

```javascript
BX.rest.callMethod('vendor.module.item.get', { id: 1 }).then((result) => {
    this.item = result.data();
});
```

## TypeScript

IDE definitions: `bitrix/modules/ui/install/js/ui/vue3/ui.vue3.d.ts` — add as external library if not auto-detected.

## Migration from BitrixVue 2

- Replace `Vue.create` / `BitrixVue.create` with `BitrixVue.createApp`.
- Update lifecycle hooks (`destroyed` → `unmounted`).
- Remove Vue 2-specific filters and `$on`/`$off` on instances.
- See official migration guide on dev.1c-bitrix.ru.

## Checklist

- [ ] BitrixVue 3 only — no Vue 2.
- [ ] Code in `/local/js/` extension, not inline in templates when possible.
- [ ] `ui.vue3` in `config.php` `rel` dependencies.
- [ ] AJAX via `BX.ajax.runAction` or REST, not raw fetch without CSRF.
- [ ] Localization via `Loc`, not hardcoded strings.
