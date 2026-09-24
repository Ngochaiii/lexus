<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Support\Catalog;
use App\Support\Url;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('hero.src')
                    ->label('')
                    ->state(fn (Model $record): ?string => Url::asset(data_get($record->hero, 'src')))
                    ->imageHeight(40),

                TextColumn::make('name')
                    ->label('Tên')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Model $record): ?string => $record->tagline),

                TextColumn::make('category.name')
                    ->label('Danh mục')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('price_from')
                    ->label('Giá từ')
                    ->numeric(thousandsSeparator: '.')
                    ->suffix(' đ')
                    ->sortable(),

                // ->state() chứ không phải ->formatStateUsing(): với cột json,
                // Filament coi mảng là nhiều giá trị và render mỗi phần tử một
                // badge, nên formatStateUsing sẽ nhận từng section chứ không
                // nhận cả mảng.
                TextColumn::make('sections_count')
                    ->label(Catalog::label('sections'))
                    ->state(fn (Model $record): string => count($record->sections ?? []).' mục')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Đã đăng',
                        'archived'  => 'Lưu trữ',
                        default     => 'Nháp',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'archived'  => 'gray',
                        default     => 'warning',
                    }),

                TextColumn::make('updated_at')
                    ->label('Sửa lúc')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft'     => 'Nháp',
                        'published' => 'Đã đăng',
                        'archived'  => 'Lưu trữ',
                    ]),

                SelectFilter::make('category')
                    ->label('Danh mục')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            // Gom vào dropdown, để 4 action inline thì tràn ra ngoài mép bảng.
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    static::duplicateAction(),
                    static::saveAsTemplateAction(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Nhân bản: copy nguyên sections + specs sang bản mới, chỉ thay ảnh và chữ.
     * Sản phẩm đầu mất vài giờ, sản phẩm sau còn 20 phút.
     */
    protected static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label('Nhân bản')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->schema([
                TextInput::make('name')
                    ->label('Tên bản mới')
                    ->required()
                    ->default(fn (Model $record): string => $record->name.' (bản sao)'),
            ])
            ->action(function (Model $record, array $data) {
                $copy = $record->duplicate($data['name']);

                Notification::make()
                    ->title('Đã nhân bản')
                    ->body('Bản mới đang ở trạng thái Nháp.')
                    ->success()
                    ->send();

                return redirect(EditProduct::getUrl(['record' => $copy]));
            });
    }

    /**
     * Lưu bố cục thành mẫu: dựng xong sản phẩm đầu của một hãng thì lưu khung
     * mục lại, sản phẩm sau tạo từ mẫu đã có sẵn các mục trống.
     */
    protected static function saveAsTemplateAction(): Action
    {
        return Action::make('saveAsTemplate')
            ->label('Lưu bố cục thành mẫu')
            ->icon(Heroicon::OutlinedBookmarkSquare)
            ->color('gray')
            ->schema([
                TextInput::make('name')
                    ->label('Tên mẫu')
                    ->required()
                    ->default(fn (Model $record): string => 'Bố cục '.Str::of($record->name)->words(3, '')),
            ])
            ->action(function (Model $record, array $data) {
                Catalog::model('template')::create([
                    'name'        => $data['name'],
                    'entity_type' => 'product',
                    'payload'     => [
                        'sections' => $record->sections ?? [],
                        'specs'    => $record->specs ?? [],
                    ],
                ]);

                Notification::make()->title('Đã lưu mẫu')->success()->send();
            });
    }
}
