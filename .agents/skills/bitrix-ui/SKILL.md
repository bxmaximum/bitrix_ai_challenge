---
name: bitrix-ui
description: Covers Bitrix UI library — main.popup, main.sidepanel, system-dialog, system-menu, system-input, system-alert, notification-manager, icons, typography. Applied when building admin interfaces and public UI with kernel components. Key terms — Extension::load, Popup, SidePanel, system-dialog, UI kit.
---

# Bitrix UI Library

Modern admin and public interfaces use JS extensions from `ui` and `main` modules. Load via `Extension::load()` in PHP, import classes in modular JS.

## Choosing a Component

| Scenario | Extension |
| --- | --- |
| Modal confirmation, form, settings | `ui.system.dialog` (preferred for new UI) |
| Context/dropdown menu (modern) | `ui.system.menu` |
| Popup with custom positioning, legacy | `main.popup` |
| Slide-over panel (CRM-style) | `main.sidepanel` |
| Toast notifications | `ui.notification-manager` |
| Alerts/banners | `ui.system.alert` |
| Form inputs (modern) | `ui.system.input`, `ui.system.label`, `ui.system.chip` |
| Icons | `ui.icon-set` / `ui.icons` |
| Message boxes | `ui.dialogs.messagebox` |
| Loading skeleton | `ui.system.skeleton` |
| Hints/tooltips | `ui.hint` |
| Animations | `ui.lottie` |

## Loading Pattern

PHP:

```php
\Bitrix\Main\UI\Extension::load(['ui.system.dialog', 'ui.notification-manager']);
```

JS (in extension):

```javascript
import { MessageBox } from 'ui.dialogs.messagebox';
import { Notification } from 'ui.notification-manager';
```

## System Dialog (preferred)

```javascript
import { MessageBox } from 'ui.dialogs.messagebox';

MessageBox.confirm('Delete item?', () => {
    // on confirm
});

MessageBox.alert('Done');
MessageBox.show({
    message: 'Confirm action?',
    buttons: MessageBox.createButtons(MessageBox.BTN_OK, MessageBox.BTN_CANCEL),
    onOk: () => { /* ... */ },
});
```

## Popup (legacy/base)

```php
\Bitrix\Main\UI\Extension::load('main.popup');
```

```javascript
import { Popup } from 'main.popup';

const popup = new Popup({
    id: 'my-popup',
    content: 'Saved successfully',
    closeIcon: true,
});
popup.show();
```

## Side Panel

```php
\Bitrix\Main\UI\Extension::load('main.sidepanel');
```

```javascript
BX.SidePanel.Instance.open('/local/admin/custom-page.php', {
    width: 800,
    cacheable: false,
});
```

## Notifications

```javascript
import { Notification } from 'ui.notification-manager';

Notification.Center.notify({
    content: 'Saved',
    autoHideDelay: 3000,
});
```

## Typography and Icons

Load `ui.design-tokens` / typography extensions for consistent admin styling. Icons via `ui.icon-set` — use named icons, not inline SVG copies.

## Checklist

- [ ] Prefer `system-*` components over legacy `main.popup` for new admin UI.
- [ ] Extensions loaded in PHP before inline scripts.
- [ ] Modular JS uses `import` from extension names, not global `BX` where avoidable.
- [ ] Side panel URLs are real routes or admin pages with proper auth.
- [ ] Notifications used for transient feedback, dialogs for confirmations.
