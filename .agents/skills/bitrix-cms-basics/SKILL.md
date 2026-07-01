---
name: bitrix-cms-basics
description: Covers CMS fundamentals — sites, site templates, menus, page templates, includes, breadcrumbs, user groups, user fields, admin panel. Applied for site structure and content management tasks. Key terms — CSite, template, menu, include area, user field, UF.
---

# CMS Basics

Site management layer above the framework — sites, templates, menus, content areas.

## Sites (Multisite)

- `\Bitrix\Main\SiteTable` / `CSite` — site definitions (`s1`, `s2`, ...).
- Each site has its own template, domain, language.
- `SITE_ID` constant available after kernel init.

## Site Templates

Location: `/local/templates/<template_id>/`

```
/local/templates/mytemplate/
├── header.php
├── footer.php
├── styles.css
├── components/     # Template-level component overrides
├── page_templates/ # Page layout templates
└── lang/
```

Template selected per site in Admin → Sites → Edit.

## Menus

- Menu types defined per site (`top`, `left`, etc.).
- Files: `/.top.menu.php`, `/.left.menu.php` in site root or section.
- Component: `bitrix:menu`.

## Page Templates

`/local/templates/<id>/page_templates/` — reusable page layouts selectable in visual editor.

## Include Areas

`bitrix:main.include` — editable content blocks in visual editor.

```php
$APPLICATION->IncludeComponent('bitrix:main.include', '', [
    'AREA_FILE_SHOW' => 'file',
    'PATH' => '/include/phone.php',
]);
```

Files in `/include/` or `/local/include/`.

## Breadcrumbs

`$APPLICATION->AddChainItem()` in section `.section.php` files. Component: `bitrix:breadcrumb`.

## Users and Groups

- `CUser`, `\Bitrix\Main\UserTable` — users.
- Groups control permissions via `\CMain::GetUserRight()` and group IDs.
- **User fields (UF)** — `CUserTypeEntity`, `\Bitrix\Main\UserField`; register in module `DoInstall`, access via `USER.UF_*` in ORM or `$USER->GetByID()` fields.

## Admin Panel

`/bitrix/admin/` — admin scripts. Custom admin pages via module `install` admin files or `CAdminList`/`CAdminForm` (legacy) or modern UI components.

## Checklist

- [ ] Site-specific code checks `SITE_ID` / `SITE_DIR`.
- [ ] Template overrides in `/local/templates/`, not `/bitrix/templates/`.
- [ ] Menus edited via `.menu.php` files or Admin UI.
- [ ] Include areas for editable content, not hardcoded in templates.
- [ ] UF definitions registered in module installer when needed.
