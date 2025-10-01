# Filament

- Use the translate method (`__()`) for all labels, placeholders, help texts, stats, etc.
- Make slug fields live based on the title/name field, on blur
- Add `getLabel`, `getPluralLabel` and `getNavigationLabel` methods with a `__()`, to all resources, at the bottom
- Don't add methods/logic for SoftDeletes when generating resources
- Use the `Filament\Support\Icons\Heroicon` class when setting icons
- When creating forms, always use a section to put all the fields in
- Don't add `created_at`, `updated_at` and `delected_at` to forms and tables
