<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Support\Catalog;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    use HasCatalogNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?int $navigationSort = 10;

    public static function getModel(): string
    {
        return Catalog::model('lead');
    }

    public static function getModelLabel(): string
    {
        return 'Liên hệ';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Liên hệ';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Khách hàng';
    }

    public static function getNavigationBadge(): ?string
    {
        $new = static::getModel()::where('status', 'new')->count();

        return $new ?: null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Họ tên'),
            TextInput::make('phone')->label('Điện thoại')->tel(),
            TextInput::make('email')->label('Email')->email(),

            // Dòng xe + phiên bản khách chọn lúc gửi — chỉ đọc.
            Select::make('product_id')
                ->label(Catalog::label('product.single'))
                ->relationship('product', 'name')
                ->disabled()
                ->dehydrated(false),
            Select::make('product_variant_id')
                ->label('Phiên bản')
                ->relationship('variant', 'name')
                ->placeholder('Khách chưa chọn phiên bản')
                ->disabled()
                ->dehydrated(false),

            Select::make('status')
                ->label('Trạng thái')
                ->options([
                    'new' => 'Mới',
                    'contacted' => 'Đã liên hệ',
                    'done' => 'Xong',
                    'spam' => 'Spam',
                ])
                ->default('new')
                ->selectablePlaceholder(false),

            KeyValue::make('data')
                ->label('Dữ liệu gửi lên')
                ->disabled()
                ->columnSpanFull(),

            // Tra từ IP lúc nhận lead (job ResolveLeadLocation) — chỉ đọc,
            // nhân viên không tự sửa khu vực.
            TextInput::make('location')
                ->label('Khu vực (theo IP)')
                ->disabled()
                ->dehydrated(false)
                ->placeholder('Chưa tra được'),
            TextInput::make('ip')
                ->label('IP')
                ->disabled()
                ->dehydrated(false),

            Textarea::make('note')->label('Ghi chú')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Lúc')->since()->sortable(),
                TextColumn::make('name')->label('Họ tên')->searchable(),
                TextColumn::make('phone')->label('Điện thoại')->searchable()->copyable(),
                TextColumn::make('form.name')->label('Form')->badge()->color('gray'),
                TextColumn::make('product.name')->label(Catalog::label('product.single'))->toggleable(),
                // Phiên bản cụ thể (RX 350h Luxury…) — biết mẫu nào được hỏi nhiều.
                TextColumn::make('variant.name')->label('Phiên bản')->placeholder('—')->toggleable(),
                TextColumn::make('location')
                    ->label('Khu vực')
                    ->placeholder('—')
                    ->tooltip(fn ($record) => $record->ip)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'contacted' => 'Đã liên hệ',
                        'done' => 'Xong',
                        'spam' => 'Spam',
                        default => 'Mới',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'done' => 'success',
                        'spam' => 'danger',
                        'new' => 'warning',
                        default => 'gray',
                    }),
            ])
            // Sửa và xoá từng bản ghi. CỐ Ý không có xoá hàng loạt: đây là dữ
            // liệu khách hàng, một cú "chọn tất cả rồi xoá" là mất cả đường
            // ống bán hàng. Dọn spam thì xoá từng cái.
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'new' => 'Mới',
                        'contacted' => 'Đã liên hệ',
                        'done' => 'Xong',
                        'spam' => 'Spam',
                    ]),
                SelectFilter::make('product_id')
                    ->label(Catalog::label('product.single'))
                    ->relationship('product', 'name'),
                SelectFilter::make('product_variant_id')
                    ->label('Phiên bản')
                    ->relationship('variant', 'name')
                    ->searchable(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLeads::route('/')];
    }
}
