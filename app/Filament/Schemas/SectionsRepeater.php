<?php

namespace App\Filament\Schemas;

use App\Filament\Forms\Components\NativeMediaUpload;
use App\Support\Catalog;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/**
 * Repeater cho cột `sections` — trái tim hệ thống.
 *
 * Mỗi mục là một khối do người nhập tự đặt tên. Không có danh sách block
 * cố định, không có field_definitions. Thêm mục = gõ tên, chọn layout, quăng ảnh.
 */
class SectionsRepeater
{
    public static function make(string $name = 'sections'): Repeater
    {
        return Repeater::make($name)
            ->hiddenLabel()
            ->addActionLabel('+ Thêm mục')
            ->defaultItems(0)
            ->reorderableWithDragAndDrop()
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->itemLabel(fn (array $state): string => static::itemLabel($state))
            ->schema([
                TextInput::make('title')
                    ->label('Tên mục')
                    ->required()
                    ->datalist(Catalog::sectionPresets())   // gợi ý, không ép chọn
                    ->columnSpan(2),

                Select::make('layout')
                    ->label('Bố cục')
                    ->options(Catalog::sectionLayouts())
                    ->default('cols-3')
                    ->selectablePlaceholder(false),

                /*
                 * Bề rộng của mục. Bỏ trống thì theo mặc định của trang: bài
                 * viết xếp mọi mục thẳng hàng với cột chữ, trang sản phẩm và
                 * trang tĩnh vẫn rộng bằng khung nội dung như trước.
                 */
                Select::make('width')
                    ->label('Bề rộng')
                    ->options([
                        'narrow' => 'Cột chữ (hẹp, dễ đọc)',
                        'wide' => 'Rộng bằng khung nội dung',
                        'full' => 'Tràn hết màn hình',
                    ])
                    ->live()
                    ->placeholder('Theo mặc định của trang')
                    ->helperText(fn (Get $get) => match ($get('width')) {
                        'narrow' => 'Ảnh trong mục rộng khoảng 1000 px — khuyến nghị ảnh ngang 1600 px trở lên.',
                        'wide' => 'Mục rộng bằng khung nội dung (1456 px) — khuyến nghị ảnh ngang 2000 px trở lên.',
                        'full' => 'Mục tràn hết màn hình — khuyến nghị ảnh ngang 2560 px trở lên, chủ thể nằm giữa vì hai mép sẽ bị xén ở màn hẹp.',
                        default => 'Bỏ trống thì theo mặc định của trang: bài viết dùng cột chữ, trang sản phẩm dùng khung nội dung.',
                    }),

                Select::make('type')
                    ->label('Kiểu')
                    ->options(Catalog::sectionTypes())
                    ->default('media')
                    ->live()
                    ->selectablePlaceholder(false),

                Textarea::make('intro')
                    ->label('Đoạn mở đầu')
                    ->helperText('Bỏ trống thì frontend không render.')
                    ->rows(2)
                    ->columnSpanFull(),

                /*
                 * Ô này trước là Textarea nên dán bài từ Word/Google Docs vào
                 * là mất sạch chữ đậm, tiêu đề, danh sách. RichEditor giữ lại
                 * phần cấu trúc khi dán, còn font/cỡ chữ/màu của tài liệu gốc
                 * thì bỏ — trang web có thang chữ riêng, bê nguyên vào là mỗi
                 * bài một kiểu.
                 */
                RichEditor::make('body')
                    ->label('Nội dung')
                    ->helperText('Dán thẳng từ Word/Google Docs được: giữ chữ đậm, nghiêng, tiêu đề, danh sách và link.')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'link'],
                        ['h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'table'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => in_array($get('type'), ['text', 'notice'], true)),

                /*
                 * Ba kiểu mục dưới đây có trong config('catalog.section_types')
                 * từ đầu nhưng trước không có ô nhập nào — chọn kiểu rồi cũng
                 * không nhập được gì. Giờ mỗi kiểu có đúng ô nó cần.
                 */
                TextInput::make('video_url')
                    ->label('Link video')
                    ->helperText('YouTube/Vimeo hoặc link file .mp4 — frontend tự đổi thành khối nhúng.')
                    ->url()
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === 'video'),

                Select::make('form_key')
                    ->label('Form nhúng vào mục')
                    ->options(fn () => Catalog::query('form')->orderBy('name')->pluck('name', 'key')->all())
                    ->searchable()
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === 'form' && Catalog::feature('forms')),

                SpecsRepeater::rowsPasteField()
                    ->visible(fn (Get $get) => $get('type') === 'table'),

                // Cùng một repeater cho Bảng (Nhãn | Giá trị) và Hỏi đáp (Câu
                // hỏi | Câu trả lời) — dữ liệu y hệt nhau về cấu trúc.
                Repeater::make('rows')
                    ->label(fn (Get $get) => $get('type') === 'faq' ? 'Các câu hỏi' : 'Dòng trong bảng')
                    ->addActionLabel(fn (Get $get) => $get('type') === 'faq' ? '+ Thêm câu hỏi' : '+ Thêm dòng')
                    ->defaultItems(0)
                    ->reorderableWithDragAndDrop()
                    ->schema([
                        TextInput::make('label')
                            ->label(fn (Get $get) => $get('../../type') === 'faq' ? 'Câu hỏi' : 'Nhãn')
                            ->required(),
                        TextInput::make('value')
                            ->label(fn (Get $get) => $get('../../type') === 'faq' ? 'Câu trả lời' : 'Giá trị')
                            ->helperText(fn (Get $get) => $get('../../type') === 'faq'
                                ? 'Trả lời thẳng vào câu hỏi ngay câu đầu, nêu con số cụ thể nếu có.'
                                : null)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => in_array($get('type'), ['table', 'faq'], true)),

                // Upload hàng loạt: kéo 12 ảnh vào một lần → 12 item.
                // Thiếu cái này thì mục Thư viện phải bấm 12 lần.
                NativeMediaUpload::make('bulk_upload')
                    ->label('Upload hàng loạt')
                    ->helperText('Kéo nhiều ảnh vào đây — mỗi ảnh thành một mục ảnh bên dưới.')
                    ->image()
                    ->multiple()
                    ->directory('catalog/sections')
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(function (?array $state, Get $get, Set $set) {
                        if (blank($state)) {
                            return;
                        }

                        $items = $get('items') ?? [];

                        foreach ($state ?? [] as $file) {
                            if (! is_string($file) || blank($file)) {
                                continue;
                            }

                            $items[(string) Str::uuid()] = [
                                // NativeMediaUpload đã lưu file và trả relative path.
                                'image' => $file,
                                'label' => '',
                                'desc'  => '',
                            ];
                        }

                        $set('items', $items);
                        $set('bulk_upload', []);
                    })
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => in_array($get('type'), ['media', null], true)),

                /*
                 * Nút hành động của mục. Hiện dùng cho bố cục `bleed` (băng ảnh
                 * tràn màn) — băng ảnh không có nút thì khách đọc xong không
                 * biết bấm vào đâu. Bỏ trống cả nhãn hoặc link thì nút tự ẩn.
                 */
                TextInput::make('cta_label')
                    ->label('Nút 1 — chữ trên nút')
                    ->placeholder('VD: Đăng ký lái thử')
                    ->visible(fn (Get $get) => in_array($get('layout'), ['bleed', 'split', 'split-alt', 'cols-2'], true)),

                TextInput::make('cta_url')
                    ->label('Nút 1 — link')
                    ->placeholder('/dat-coc')
                    ->visible(fn (Get $get) => in_array($get('layout'), ['bleed', 'split', 'split-alt', 'cols-2'], true)),

                TextInput::make('cta2_label')
                    ->label('Nút 2 — chữ trên nút')
                    ->helperText('Không bắt buộc.')
                    ->visible(fn (Get $get) => in_array($get('layout'), ['bleed', 'split', 'split-alt', 'cols-2'], true)),

                TextInput::make('cta2_url')
                    ->label('Nút 2 — link')
                    ->visible(fn (Get $get) => in_array($get('layout'), ['bleed', 'split', 'split-alt', 'cols-2'], true)),

                Repeater::make('items')
                    ->label('Ảnh trong mục')
                    ->addActionLabel('+ Thêm ảnh')
                    ->defaultItems(0)
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                    ->grid(2)
                    ->schema([
                        NativeMediaUpload::make('image')
                            ->label('Ảnh')
                            ->image()
                            ->directory('catalog/sections')
                            ->imagePreviewHeight('120'),

                        /*
                         * Cùng một ô nhưng vai trò đổi theo bố cục, nên đổi cả
                         * nhãn lẫn lời nhắc — với `tabs` đây là TÊN TAB, bỏ
                         * trống thì tab chỉ còn số 01/02/03 trơ trọi.
                         */
                        TextInput::make('label')
                            ->label(fn (Get $get) => match ($get('../../layout')) {
                                'tabs' => 'Tên tab',
                                'hotspot' => 'Tên điểm',
                                default => 'Nhãn',
                            })
                            ->helperText(fn (Get $get) => match ($get('../../layout')) {
                                'tabs' => 'Chữ hiện trên tab, cạnh số thứ tự. Bỏ trống thì tab chỉ có số.',
                                'hotspot' => 'Tiêu đề hiện khi rê chuột vào chấm.',
                                'feature-rows' => 'Tiêu đề của hàng này.',
                                default => 'Bỏ trống thì không render.',
                            }),

                        /*
                         * Dòng chữ hoa nhỏ phía trên tiêu đề của thẻ — khối
                         * "Đặc quyền sở hữu" của bản thiết kế có nó. Chỉ hiện
                         * ở các bố cục dựng thành thẻ; bố cục khác không có
                         * chỗ đặt nên ẩn ô đi cho khỏi rối.
                         */
                        TextInput::make('eyebrow')
                            ->label(fn (Get $get) => $get('../../layout') === 'groups' ? 'Nhóm (tên tab)' : 'Dòng nhỏ trên tiêu đề')
                            ->placeholder(fn (Get $get) => $get('../../layout') === 'groups' ? 'VD: Mâm xe' : 'VD: TƯ VẤN SỞ HỮU')
                            ->helperText(fn (Get $get) => $get('../../layout') === 'groups'
                                ? 'Ảnh cùng nhóm gom chung một tab, thứ tự tab theo ảnh đầu tiên của nhóm.'
                                : 'Tự viết hoa khi hiển thị. Bỏ trống thì không render.')
                            ->visible(fn (Get $get) => in_array($get('../../layout'), ['cols-2', 'cols-3', 'groups'], true)),

                        Textarea::make('desc')
                            ->label('Mô tả')
                            ->rows(2),

                        /*
                         * Link riêng của từng ô. Dùng cho bố cục `shortcuts`
                         * (mỗi ô một đích đến) và cho thẻ editorial ở
                         * `cols-2`. Bố cục khác bỏ trống thì ô không bấm được.
                         */
                        TextInput::make('url')
                            ->label('Link của ô này')
                            ->placeholder('/bang-gia')
                            ->helperText('Bỏ trống thì ô không phải link.')
                            ->visible(fn (Get $get) => in_array($get('../../layout'), ['shortcuts', 'cols-2', 'cols-3'], true)),

                        /*
                         * Chỉ dùng cho bố cục `hotspot`: ảnh của mục ĐẦU TIÊN
                         * là ảnh nền, mỗi mục sau là một chấm đặt lên ảnh đó.
                         * Toạ độ tính theo phần trăm chiều rộng/cao của ảnh,
                         * gốc ở góc trên trái — 50/50 là chính giữa.
                         */
                        TextInput::make('x')
                            ->label('Chấm — ngang (%)')
                            ->helperText('0 = mép trái, 100 = mép phải.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->visible(fn (Get $get) => $get('../../layout') === 'hotspot'),

                        TextInput::make('y')
                            ->label('Chấm — dọc (%)')
                            ->helperText('0 = mép trên, 100 = mép dưới. Bỏ trống thì không vẽ chấm.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->visible(fn (Get $get) => $get('../../layout') === 'hotspot'),
                    ])
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => in_array($get('type'), ['media', 'video', null], true)),
            ])
            ->columns(4)
            ->columnSpanFull();
    }

    protected static function itemLabel(array $state): string
    {
        $title = $state['title'] ?? 'Mục chưa đặt tên';
        $layout = Catalog::sectionLayouts()[$state['layout'] ?? ''] ?? null;
        $count = count($state['items'] ?? []);

        return trim(implode('  ·  ', array_filter([
            $title,
            $layout,
            $count ? "{$count} ảnh" : null,
        ])));
    }
}
