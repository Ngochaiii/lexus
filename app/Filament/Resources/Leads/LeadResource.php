<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Filament\Schemas\MoneyInput;
use App\Models\Lead;
use App\Support\Catalog;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
        // Lead mới + lead đến hạn gọi lại — việc cần làm hôm nay.
        $todo = static::getModel()::where('status', 'new')
            ->orWhere(fn ($q) => $q->whereIn('status', Lead::OPEN)->where('follow_up_at', '<=', now()->endOfDay()))
            ->count();

        return $todo ?: null;
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

            Section::make('Chăm sóc & chốt')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('status')
                        ->label('Trạng thái')
                        ->options(Lead::STATUSES)
                        ->default('new')
                        ->live()
                        ->selectablePlaceholder(false),
                    DateTimePicker::make('follow_up_at')
                        ->label('Hẹn gọi lại')
                        ->seconds(false)
                        ->helperText('Đến hạn sẽ hiện trong số đỏ ở menu "Liên hệ".'),
                    Select::make('lost_reason')
                        ->label('Lý do không mua')
                        ->options(Lead::LOST_REASONS)
                        ->visible(fn (Get $get) => $get('status') === 'lost'),
                    MoneyInput::make('deal_value', 'Giá trị hợp đồng')
                        ->visible(fn (Get $get) => in_array($get('status'), ['deposit', 'won'], true)),
                    MoneyInput::make('commission', 'Hoa hồng')
                        ->helperText('Chỉ hiện trong admin — dùng cho báo cáo thu nhập.')
                        ->visible(fn (Get $get) => in_array($get('status'), ['deposit', 'won'], true)),
                    Repeater::make('activities')
                        ->label('Nhật ký chăm sóc')
                        ->addActionLabel('+ Ghi lần liên hệ')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => trim(($state['at'] ?? '').' · '.(['call' => 'Gọi', 'zalo' => 'Zalo', 'meet' => 'Gặp', 'test' => 'Lái thử', 'other' => 'Khác'][$state['type'] ?? ''] ?? '')))
                        ->schema([
                            DateTimePicker::make('at')->label('Lúc')->seconds(false)->default(now())->required(),
                            Select::make('type')->label('Hình thức')->options([
                                'call' => 'Gọi điện', 'zalo' => 'Nhắn Zalo', 'meet' => 'Gặp tại showroom', 'test' => 'Lái thử', 'other' => 'Khác',
                            ])->default('call')->required(),
                            Textarea::make('note')->label('Nội dung')->rows(2)->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Nguồn khách')
                ->description('Ghi tự động khi khách gửi form: lần đầu khách vào web từ đâu.')
                ->columns(3)
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    TextInput::make('source')->label('Nguồn')->disabled()->dehydrated(false)
                        ->formatStateUsing(fn (?string $state) => Lead::sourceLabel($state)),
                    TextInput::make('medium')->label('Kênh')->disabled()->dehydrated(false),
                    TextInput::make('campaign')->label('Chiến dịch')->disabled()->dehydrated(false),
                    // Mẫu theo dõi Google Ads gửi utm_term={keyword}: từ khoá khách gõ
                    // khi thấy quảng cáo — biết nên dồn tiền vào từ khoá nào.
                    TextInput::make('utm.utm_term')->label('Từ khoá quảng cáo')->disabled()->dehydrated(false)
                        ->placeholder('—')->columnSpan(3),
                    TextInput::make('landing_page')->label('Trang vào đầu tiên')->disabled()->dehydrated(false)->columnSpan(2),
                    TextInput::make('device')->label('Thiết bị')->disabled()->dehydrated(false),
                    TextInput::make('gclid')->label('Mã click Google Ads (gclid)')->disabled()->dehydrated(false)
                        ->placeholder('Không đến từ quảng cáo')->columnSpan(2),
                    TextInput::make('qualified_at')->label('Hẹn lái thử lần đầu')->disabled()->dehydrated(false)
                        ->formatStateUsing(fn ($state) => $state ? \Illuminate\Support\Carbon::parse($state)->format('d/m/Y H:i') : null),
                ]),

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
                TextColumn::make('source')
                    ->label('Nguồn')
                    ->formatStateUsing(fn (?string $state) => Lead::sourceLabel($state))
                    ->description(fn ($record) => $record->landing_page)
                    ->placeholder('—')
                    ->toggleable(),
                // Không đặt tên 'utm.utm_term': Filament hiểu dấu chấm là quan hệ.
                TextColumn::make('utm_term')
                    ->label('Từ khoá QC')
                    ->state(fn ($record) => $record->utm['utm_term'] ?? null)
                    ->description(fn ($record) => $record->campaign)
                    ->placeholder('—')
                    ->searchable(query: fn (Builder $q, string $search) => $q->where('utm', 'like', '%'.$search.'%'))
                    ->toggleable(),
                TextColumn::make('follow_up_at')
                    ->label('Hẹn gọi')
                    ->dateTime('d/m H:i')
                    ->placeholder('—')
                    ->color(fn ($record) => $record->follow_up_at && $record->follow_up_at->isPast() && in_array($record->status, Lead::OPEN, true) ? 'danger' : null)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Lead::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'won' => 'success',
                        'lost', 'spam' => 'danger',
                        'new' => 'warning',
                        'deposit', 'test_drive', 'appointment' => 'info',
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
            // Filament truyền tham số closure THEO TÊN: phải là $query. Tên khác
            // ($q) thì nhận null và bộ lọc lặng lẽ không lọc gì.
            ->filters([
                Filter::make('due')
                    ->label('Cần gọi lại hôm nay')
                    ->query(fn (Builder $query) => $query->whereIn('status', Lead::OPEN)->where('follow_up_at', '<=', now()->endOfDay())),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->multiple()
                    ->options(Lead::STATUSES),
                SelectFilter::make('source')
                    ->label('Nguồn')
                    ->options(Lead::SOURCES),
                Filter::make('google_ads')
                    ->label('Từ Google Ads')
                    ->query(fn (Builder $query) => $query->where('source', 'google')->where('medium', 'cpc')),
                SelectFilter::make('campaign')
                    ->label('Chiến dịch')
                    ->options(fn () => Catalog::query('lead')->whereNotNull('campaign')->distinct()
                        ->orderBy('campaign')->pluck('campaign', 'campaign')->all()),
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
