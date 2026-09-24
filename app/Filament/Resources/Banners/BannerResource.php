<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Forms\Components\NativeMediaUpload;
use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Support\Catalog;
use App\Support\Url;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Banner hero trang chủ.
 *
 * Chưa khai banner nào thì trang chủ lùi về dùng ảnh của ba mặt hàng đầu —
 * xoá sạch bảng này không làm vỡ trang chủ.
 */
class BannerResource extends Resource
{
    use HasCatalogNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 1;

    public static function getModel(): string
    {
        return Catalog::model('banner');
    }

    public static function getModelLabel(): string
    {
        return 'Banner';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Banner trang chủ';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('catalog.admin.navigation_group');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Catalog::feature('banners');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')
                ->label('Tiêu đề')
                ->helperText('Bỏ trống thì banner chỉ hiện ẢNH, bấm vào ảnh là đi tới link — dùng cho ảnh đã thiết kế sẵn chữ bên trong. Điền vào thì hiện đầy đủ tiêu đề, mô tả và nút.')
                ->columnSpanFull(),

            TextInput::make('eyebrow')
                ->label('Dòng nhỏ phía trên')
                ->helperText('Chữ hoa giãn cách, VD "Ưu đãi mùa hè · đến 31/08".'),

            Textarea::make('subtitle')
                ->label('Mô tả')
                ->rows(2)
                ->columnSpanFull(),

            NativeMediaUpload::make('image')
                ->label('Ảnh desktop')
                ->image()
                ->directory('catalog/banners')
                ->helperText('Khuyến nghị 1920×820 px (gần 21:9) để ảnh và thanh chuyển slide nằm gọn trong màn hình laptop. Bỏ trống thì banner dùng nền tối.')
                ->columnSpan(1),

            NativeMediaUpload::make('image_mobile')
                ->label('Ảnh mobile')
                ->image()
                ->directory('catalog/banners')
                ->helperText('Khuyến nghị 1080×1350 px (4:5). Nên tải riêng vì nếu bỏ trống, ảnh desktop 21:9 sẽ bị thu nhỏ và chữ trong ảnh khó đọc trên điện thoại.')
                ->columnSpan(1),

            TextInput::make('cta_label')
                ->label('Nhãn nút')
                ->helperText('Banner chỉ ảnh không dựng nút, nhưng vẫn dùng nhãn này làm chữ mô tả ảnh cho trình đọc màn hình.'),

            TextInput::make('cta_url')
                ->label('Link nút')
                ->helperText('Nhãn không kèm link thì nút không hiện — tránh nút bấm không ra gì.'),

            Toggle::make('is_active')->label('Đang bật')->default(true),

            TextInput::make('sort')->label('Thứ tự')->numeric()->default(0),

            DateTimePicker::make('starts_at')
                ->label('Chạy từ')
                ->seconds(false)
                ->helperText('Bỏ trống = chạy ngay.'),

            DateTimePicker::make('ends_at')
                ->label('Chạy đến')
                ->seconds(false)
                ->helperText('Bỏ trống = không hết hạn.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('image')
                    ->label('Ảnh')
                    ->state(fn ($record): ?string => Url::asset($record->image)),
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->wrap()
                    ->placeholder('(chỉ hiện ảnh)'),
                IconColumn::make('is_active')->label('Bật')->boolean(),
                TextColumn::make('starts_at')->label('Từ')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('ends_at')->label('Đến')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            // Không khai thì bảng không có nút nào: không sửa được, không xoá
            // được. Filament 4 bỏ mặc định này, phải khai tường minh.
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBanners::route('/')];
    }
}
