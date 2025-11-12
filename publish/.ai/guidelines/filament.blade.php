# Filament

When Filament is used in this project, there will be a lot of code and existing guidelines. Follow those guidelines, and give the ones below priority in case of any conflicting rules.

---

- Use the translate method (`__()`) for all labels, placeholders, help texts, stats, etc.
- When using slug fields, make them live based on the title/name field, on blur
- Add `getLabel`, `getPluralLabel` and `getNavigationLabel` methods with a `__()`, to all resources, at the bottom
- Ignore SoftDeletes completely in Filament, so no filters, custom queries, restore and force delete actions
- Use the `Filament\Support\Icons\Heroicon` class when setting icons
- When creating forms, always use a section to put all the fields in
- Don't add `created_at`, `updated_at` and `delected_at` to forms and tables
- Use our own `Simple*Action` instead of existing `*Action` in tables
- Use `->visible()` rather than `->hidden()`
- RelationManagers are placed in the resource’s directory by default, but in practice, they can be used across multiple resources. Therefore, place RelationManagers in `app/Filament/RelationManagers`

# Components

Sinds Filament 4
