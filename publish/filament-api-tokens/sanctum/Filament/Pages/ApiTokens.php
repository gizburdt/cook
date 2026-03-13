<?php

namespace App\Filament\Pages;

use App\Filament\Actions\SimpleDeleteAction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.api-tokens';

    public ?string $plainTextToken = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PersonalAccessToken::query()
                    ->where('tokenable_id', auth()->id())
                    ->where('tokenable_type', auth()->user()::class)
                    ->latest()
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name')),

                TextColumn::make('last_used_at')
                    ->label(__('Last used'))
                    ->dateTime('d-m-Y H:i')
                    ->timezone('Europe/Amsterdam')
                    ->placeholder(__('Never')),

                TextColumn::make('expires_at')
                    ->label(__('Expires'))
                    ->dateTime('d-m-Y H:i')
                    ->timezone('Europe/Amsterdam')
                    ->placeholder(__('Never')),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime('d-m-Y H:i')
                    ->timezone('Europe/Amsterdam'),
            ])
            ->recordActions([
                SimpleDeleteAction::make()
                    ->label(__('Revoke')),
            ])
            ->paginated(false);
    }

    public function showTokenAction(): Action
    {
        return Action::make('showToken')
            ->modalHeading(__('API token created'))
            ->modalDescription(__('Please copy this token now, as it will not be shown again.'))
            ->form([
                TextInput::make('token')
                    ->label(__('Token'))
                    ->default(fn (): ?string => $this->plainTextToken)
                    ->readOnly()
                    ->copyable(copyMessage: __('Copied!')),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createToken')
                ->label(__('Create token'))
                ->form([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $token = auth()->user()->createToken($data['name']);

                    $this->plainTextToken = $token->plainTextToken;

                    $this->replaceMountedAction('showToken');
                }),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('API tokens');
    }

    public function getTitle(): string
    {
        return __('API tokens');
    }
}
