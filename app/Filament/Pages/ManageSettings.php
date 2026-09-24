<?php

namespace App\Filament\Pages;

use App\Filament\Forms\Components\NativeMediaUpload;
use BackedEnum;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Màn hình Cài đặt dựng từ config('catalog.settings').
 *
 * Dự án nào cần thêm mục cài đặt thì khai báo thêm trong config — không
 * thêm cột, không migration. Cùng tinh thần với `labels` và `features`.
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 7;

    /** @var array<string, mixed> */
    public array $data = [];

    public static function getNavigationLabel(): string
    {
        return 'Cài đặt';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Cấu hình';
    }

    public function getTitle(): string
    {
        return 'Cài đặt';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return filled(static::groups());
    }

    public function mount(): void
    {
        $this->form->fill(Setting::allValues());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs(array_map(
                        fn (array $group, string $key) => Tab::make($group['label'] ?? $key)
                            ->schema(static::fieldsFor($group['fields'] ?? [])),
                        array_values(static::groups()),
                        array_keys(static::groups()),
                    ))
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Lưu cài đặt')
                            ->submit('form'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $values = $this->form->getState();

        foreach (static::groups() as $groupKey => $group) {
            foreach (array_keys($group['fields'] ?? []) as $key) {
                Setting::put($key, $values[$key] ?? null, $groupKey);
            }
        }

        Notification::make()->title('Đã lưu cài đặt')->success()->send();
    }

    /** @return array<string, array<string, mixed>> */
    protected static function groups(): array
    {
        return (array) config('catalog.settings', []);
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<int, Component>
     */
    protected static function fieldsFor(array $fields): array
    {
        $components = [];

        foreach ($fields as $key => $field) {
            $label = $field['label'] ?? $key;

            $components[] = match ($field['type'] ?? 'text') {
                'textarea' => Textarea::make($key)->label($label)->rows(3),
                'url'      => TextInput::make($key)->label($label)->url(),
                'email'    => TextInput::make($key)->label($label)->email(),
                'number'   => TextInput::make($key)->label($label)->numeric(),
                'toggle'   => Toggle::make($key)->label($label),
                'color'    => ColorPicker::make($key)->label($label),
                'image'    => NativeMediaUpload::make($key)
                    ->label($label)
                    ->image()
                    ->directory('catalog/settings'),
                default    => TextInput::make($key)->label($label),
            };
        }

        return $components;
    }
}
