---
name: bitrix-iblocks
description: Covers iblock module — iblock types, iblocks, sections, elements, user properties (including file and Highload), ORM via IblockTable::compileEntity and ElementTable/SectionTable, classic CIBlockElement/CIBlockSection/CIBlockProperty APIs, SEO templates (IPROPERTY_TEMPLATES), permissions and property groups. Applied for any catalog, news, content-iblock tasks, element import/export and building selections by property values. Key terms — iblock, IblockTable, CIBlockElement, property, section, SEO template, compileEntity, HL-block.
---

# Information Blocks (`iblock`)

The `iblock` module is the primary Bitrix tool for structured dynamic content: catalogs, news, directories. In new projects, we work via **ORM** (typed, autocompletion, fewer bugs). The **classic API** is needed where ORM doesn't cover the entire table graph.

```php
\Bitrix\Main\Loader::includeModule('iblock');
```

## Hierarchy

- **Iblock Type** (`b_iblock_type`) — a family of iblocks with a shared structure: "news", "catalog".
- **Iblock** (`b_iblock`) — a table of elements of a certain type, linked to sites.
- **Section** (`b_iblock_section`) — a group of elements, forming a tree.
- **Element** (`b_iblock_element`) — a unit of content (news, product).
- **Property** (`b_iblock_property`) — an additional characteristic of an element.

## Key Identifiers

- `CODE` — symbolic code (latin+digits+`-`), used in URLs and code.
- `API_CODE` — 1–50 characters, starts with a letter, **CamelCase recommended**. Object-oriented ORM only works if it's present. Set in iblock settings.
- `XML_ID` — external identifier (for exchanges, Highload directories).

## Where ORM / Classic API Boundary Lies

| Task | API |
| --- | --- |
| Create iblock type | `CIBlockType::Add` (ORM won't add name translations) |
| Create iblock | `CIBlock::Add` (ORM won't link to site, permissions, SEO) |
| Add/change property | `CIBlockProperty::Add/Update/Delete` |
| Basic iblock permissions | `CIBlock::SetPermission` / `GROUP_ID` field in `Add` |
| Advanced permissions | `CIBlockRights` / `CIBlockSectionRights` / `CIBlockElementRights` |
| Image resizing | `CFile::ResizeImage` |
| Full-text search | `CIBlockElement::UpdateSearch($id)` |
| Daily element/section CRUD | ORM |

## Compiling ORM Classes

ORM generates classes "on the fly" by iblock `API_CODE` (`News` in examples below):

```php
\Bitrix\Iblock\IblockTable::compileEntity('News');
// Elements class: \Bitrix\Iblock\Elements\ElementNewsTable
// Sections class: \Bitrix\Iblock\Model\Section::compileEntityByIblock('News')

$elementClass = \Bitrix\Iblock\Elements\ElementNewsTable::class;
$sectionClass = \Bitrix\Iblock\Model\Section::compileEntityByIblock('News');
```

Do not use `\Bitrix\Iblock\ElementTable` and `\Bitrix\Iblock\SectionTable` — they **only** work with basic fields and **do not know about properties/UF**.

IDE annotations: `php bitrix/bitrix.php orm:annotate`.

## Creating an Iblock Programmatically

```php
$iblock = new \CIBlock();
$iblockId = $iblock->Add([
    'IBLOCK_TYPE_ID' => 'mynews',
    'NAME'           => 'News',
    'CODE'           => 'mycompany_news',
    'API_CODE'       => 'News',           // required for ORM
    'ACTIVE'         => 'Y',
    'LID'            => ['s1'],           // link to site
    'GROUP_ID'       => [
        2 => \CIBlockRights::PUBLIC_READ,
        8 => \CIBlockRights::EDIT_ACCESS,
    ],
    'VERSION'        => 2,                // property storage version (usually 2)
]);
if (!$iblockId) { throw new \RuntimeException($iblock->getLastError()->getMessage()); }
```

### Property Storage Versions

- **Version 1** — separate row in the shared `b_iblock_element_property` table. Slow selection, wins with hundreds of properties.
- **Version 2** (default for new) — element values in a single row of `b_iblock_element_prop_s{IBLOCK_ID}` table. Fast selection, with ≤50 properties. For v2, `PropertyTable::add/update/delete` **does not work** — use classic API only.

## Properties

### Basic Types

```php
(new \CIBlockProperty)->Add([
    'IBLOCK_ID'     => $iblockId,
    'NAME'          => 'Author',
    'CODE'          => 'AUTHOR',      // required! ORM won't see it without CODE
    'PROPERTY_TYPE' => 'S',            // S-string, N-number, L-list, F-file, E-element, G-section
    'MULTIPLE'      => 'N',
]);
```

### List (`L`)

```php
$propId = (new \CIBlockProperty)->Add([
    'IBLOCK_ID' => $iblockId, 'NAME' => 'Source', 'CODE' => 'SOURCE',
    'PROPERTY_TYPE' => 'L', 'MULTIPLE' => 'N',
]);

$enum = new \CIBlockPropertyEnum();
$enum->Add(['PROPERTY_ID' => $propId, 'VALUE' => 'Reuters', 'XML_ID' => 'reuters', 'SORT' => 10]);
```

### User Types (`USER_TYPE`)

- `USER_TYPE = 'HTML'`, `PROPERTY_TYPE = 'S'` — HTML editor.
- `USER_TYPE = 'directory'`, `PROPERTY_TYPE = 'S'` + `USER_TYPE_SETTINGS = ['TABLE_NAME' => 'b_<hl_table>']` — value from Highload block, `UF_XML_ID` is stored.
- `USER_TYPE = 'DateTime'`, `PROPERTY_TYPE = 'S'` — date-time.

## Sections via ORM

```php
$sectionClass = \Bitrix\Iblock\Model\Section::compileEntityByIblock('News');

$parent = $sectionClass::createObject()
    ->setIblockId($iblockId)
    ->setName('Events')
    ->setCode('events')
    ->set('UF_MANAGER', 'John Doe') // UF fields via set('UF_*', ...)
    ->setActive(true)
    ->save();

$child = $sectionClass::createObject()
    ->setIblockId($iblockId)
    ->setName('Exhibitions')
    ->setCode('exhibitions')
    ->setIblockSectionId($parent->getObject()->getId())
    ->save();
```

Reading with parent:

```php
$section = $sectionClass::query()
    ->setSelect(['*', 'PARENT_SECTION', 'UF_*'])
    ->where('CODE', 'exhibitions')
    ->fetchObject();

$section->getParentSection()?->getName();
```

Deletion:

- **`CIBlockSection::Delete($id)`** — recursively deletes sub-sections and elements, clears cache and search index.
- `$section->delete()` — only deletes the section itself (children will become orphaned). Use with caution.

## Elements via ORM

### Creation

```php
$elementClass = \Bitrix\Iblock\Elements\ElementNewsTable::class;

$element = $elementClass::createObject()
    ->setName('Security Update')
    ->setCode('security-update')
    ->setActive(true)
    ->setIblockSectionId($parentSectionId)
    ->set('AUTHOR', 'Jane Smith')  // string
    ->set('SOURCE', $enumId);       // list — ID of value from CIBlockPropertyEnum

$result = $element->save();
if (!$result->isSuccess()) { /* errors */ }
```

### Multiple Properties

```php
$element
    ->addTo('TAGS', 'security')
    ->addTo('TAGS', '2026');

$element->removeAll('TAGS');      // clear all
$element->removeAllBy('TAGS', 'security');
```

### File Properties

ORM requires `PropertyValue` — it contains file `ID` + description.

```php
use Bitrix\Iblock\ORM\PropertyValue;

$fileId = \CFile::SaveFile(
    \CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . '/upload/img.png'),
    'iblock',
);

\CFile::ResizeImage($fileId, ['width' => 300, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL, true);

$element
    ->set('PHOTO',   new PropertyValue($fileId, 'Main Photo'))
    ->addTo('GALLERY', new PropertyValue($otherId, 'Second Shot'));
```

### Element Relations (`E`, `G`)

```php
$element->set('RELATED_ARTICLE', $otherElementId);
$element->set('MANUFACTURER', $sectionId);
```

## Selections and Filters

```php
$elements = $elementClass::query()
    ->setSelect(['ID', 'NAME', 'PREVIEW_TEXT', 'AUTHOR', 'SOURCE'])
    ->where('ACTIVE', 'Y')
    ->where('IBLOCK_SECTION_ID', $sectionId)
    ->where('AUTHOR.VALUE', 'Jane Smith')           // filter by property value
    ->whereIn('SOURCE.VALUE', [$enumId1, $enumId2])
    ->setOrder(['SORT' => 'ASC', 'ID' => 'DESC'])
    ->setLimit(10)
    ->fetchCollection();

foreach ($elements as $el)
{
    echo $el->getName();
    echo $el->getAuthor()?->getValue(); // single property
}
```

For properties of type "List" (`L`), "Element" (`E`), "Section" (`G`), ORM provides access to the related entity:

```php
// Property AUTHOR (List) -> CIBlockPropertyEnum
echo $el->getAuthor()->getItem()->getValue();
echo $el->getAuthor()->getItem()->getXmlId();
```

## SEO Templates

SEO values (Meta Title, Description, etc.) are stored in `IPROPERTY_TEMPLATES`.

```php
$iproperty = new \Bitrix\Iblock\InheritedProperty\ElementValues($iblockId, $elementId);
$seoValues = $iproperty->getValues();

echo $seoValues['ELEMENT_META_TITLE'];
```

## Checklist

- [ ] `API_CODE` is set in iblock settings.
- [ ] Properties have unique `CODE`.
- [ ] Elements/Sections are handled via ORM generated classes (`ElementXxxTable`).
- [ ] Iblock creation/deletion uses `CIBlock` / `CIBlockSection` for full cleanup.
- [ ] Permissions are set during iblock creation.
- [ ] Properties version 2 is used for performance where possible.
- [ ] File properties are set via `PropertyValue`.

## Performance

- Limit `select` to needed fields — avoid `['*']` on elements with many properties.
- Use ORM cache: `['cache' => ['ttl' => 3600]]` in queries.
- Avoid N+1: use `fetchCollection()` with relations in `select`, not per-element property fetches.
- `ElementTable` for ID+NAME lists is fine; for properties use compiled entity classes.
- Disable `UpdateSearch` on bulk imports when search index refresh is not needed.
