# Filament

- Use the translate method (`__()`) for all labels, placeholders, help texts, stats, etc.
- Make slug fields live based on the title/name field, on blur
- Add `getLabel`, `getPluralLabel` and `getNavigationLabel` methods with a `__()`, to all resources, at the bottom
- Ignore SoftDeletes completely in Filament, so no filters, custom queries, restore and force delete actions
- Use the `Filament\Support\Icons\Heroicon` class when setting icons
- When creating forms, always use a section to put all the fields in
- Don't add `created_at`, `updated_at` and `delected_at` to forms and tables
- Use our own `SimpleEditAction`, `SimpleDetachAction`, `SimpleDeleteAction` in tables
