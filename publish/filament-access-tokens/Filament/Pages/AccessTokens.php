<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Laravel\Passport\Token;

class AccessTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.access-tokens';

    public ?string $plainTextToken = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Token::query()
                    ->with('client')
                    ->where('user_id', auth()->id())
                    ->latest()
            )
            ->columns([
                TextColumn::make('client.name')
                    ->label(__('Client'))
                    ->placeholder('-'),

                TextColumn::make('name')
                    ->label(__('Name'))
                    ->placeholder('-'),

                TextColumn::make('scopes')
                    ->label(__('Scopes'))
                    ->badge()
                    ->placeholder(__('None')),

                IconColumn::make('revoked')
                    ->label(__('Revoked'))
                    ->boolean(),

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
                Action::make('credentials')
                    ->label(__('Credentials'))
                    ->icon(Heroicon::OutlinedKey)
                    ->iconButton()
                    ->modalHeading(__('Client credentials'))
                    ->modalDescription(fn (Token $record): string => $record->client->secret
                        ? __('Confidential client. Keep the secret safe.')
                        : __('PKCE public client. No secret required.'))
                    ->schema(fn (Token $record): array => [
                        TextInput::make('client_id')
                            ->label(__('Client ID'))
                            ->default($record->client->id)
                            ->readOnly()
                            ->copyable(copyMessage: __('Copied!')),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close')),

                Action::make('revoke')
                    ->label(__('Revoke'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->iconButton()
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Token $record): bool => ! $record->revoked)
                    ->action(function (Token $record): void {
                        $record->revoke();

                        $record->refreshToken?->revoke();

                        Notification::make()
                            ->title(__('Token revoked'))
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated(false);
    }

    public function showTokenAction(): Action
    {
        return Action::make('showToken')
            ->modalHeading(__('API token created'))
            ->modalDescription(__('Please copy this token now, as it will not be shown again.'))
            ->schema([
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
                ->schema([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $token = auth()->user()->createToken($data['name'], ['*']);

                    $this->plainTextToken = $token->accessToken;

                    $this->replaceMountedAction('showToken');
                }),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Access tokens');
    }

    public function getTitle(): string
    {
        return __('Access tokens');
    }
}
