# Filament

When Filament is used in this project, there will be a lot of code and existing guidelines. Follow those guidelines, and give the ones below priority in case of any conflicting rules.

---

## General
- Ignore SoftDeletes completely in Filament, so no filters, custom queries, restore and force delete actions
- Use the `Filament\Support\Icons\Heroicon` class when setting icons
- Use `->visible()` rather than `->hidden()`

## Tables
- Don't add `created_at`, `updated_at` and `delected_at` to forms and tables
- Use our own `Simple*Action` instead of existing `*Action` in tables

## Forms
- When creating forms, always use a section to put all the fields in, give it `columnSpanFull()`

## Components
- When using slug fields, make them live based on the title/name field, on blur

## Labels
- Don't use `translateLabel()`, instead use `label()` with `__()`
- Use the translate method (`__()`) for all labels, placeholders, help texts, stats, etc.

## Resources
- Add `getLabel()`, `getPluralLabel()` and `getNavigationLabel()` methods with a `__()` to all resources, at the bottom

## RelationManagers
- RelationManagers are placed in the resource’s directory by default, but in practice, they can be used across multiple resources. Therefore, place RelationManagers in `app/Filament/RelationManagers`

## Enums
- When Enums are used in this project, add `implements HasLabel` from Filament
- Use `Enum::class` as parameter to `Select::options()`
