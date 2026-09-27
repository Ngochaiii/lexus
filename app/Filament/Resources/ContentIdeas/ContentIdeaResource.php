<?php

namespace App\Filament\Resources\ContentIdeas;

use App\Filament\Concerns\HasCatalogNavigation;
use App\Filament\Resources\ContentIdeas\Pages\ManageContentIdeas;
use App\Filament\Resources\Posts\PostResource;
use App\Jobs\PlanContentWithGemini;
use App\Jobs\WriteDraftFromIdea;
use App\Models\ContentIdea;
use App\Services\GeminiContentPlanner;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Kế hoạch bài viết: Gemini đề xuất chủ đề theo từ khoá có ý định mua, người
 * duyệt bấm "Viết bài" → Gemini viết thành bài NHÁP, trỏ link về trang cần đẩy.
 */
class ContentIdeaResource extends Resource
{
    use HasCatalogNavigation;

    protected static ?string $model = ContentIdea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'Chủ đề';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kế hoạch bài viết';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('catalog.admin.navigation_group');
    }

    public static function getNavigationBadge(): ?string
    {
        $todo = ContentIdea::where('status', 'idea')->where('priority', 1)->count();

        return $todo ?: null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('Tiêu đề bài')->required()->maxLength(190)->columnSpanFull(),
            TextInput::make('primary_keyword')->label('Từ khoá chính')->required()->maxLength(190)
                ->helperText('Cụm khách gõ trên Google, vd "giá lăn bánh lexus es 350h".'),
            Select::make('target_url')->label('Trang cần đẩy')
                ->options(fn () => app(GeminiContentPlanner::class)->targetUrls())
                ->searchable()
                ->helperText('Bài sẽ chèn link về trang này để dẫn khách sang xem giá/để lại số.'),
            TagsInput::make('secondary_keywords')->label('Từ khoá phụ')->columnSpanFull(),
            Textarea::make('search_intent')->label('Khách tìm để làm gì')->rows(2)->columnSpanFull(),
            Textarea::make('angle')->label('Bài phải trả lời được')->rows(3)->columnSpanFull(),
            Select::make('cluster')->label('Nhóm chủ đề')->options(ContentIdea::CLUSTERS),
            Select::make('priority')->label('Ưu tiên')->options(ContentIdea::PRIORITIES)->default(2)->required()->selectablePlaceholder(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByRaw("status = 'dismissed'")->orderBy('priority')->orderByDesc('id'))
            ->description(fn () => self::planStatus())
            // Tự làm mới khi Gemini đang chạy nền để thấy bài/chủ đề mới ngay.
            ->poll(fn () => self::busy() ? '5s' : null)
            ->columns([
                TextColumn::make('priority')->label('Ưu tiên')->badge()
                    ->formatStateUsing(fn (int $state) => ContentIdea::PRIORITIES[$state] ?? $state)
                    ->color(fn (int $state) => match ($state) { 1 => 'danger', 2 => 'warning', default => 'gray' }),
                TextColumn::make('title')->label('Chủ đề')->wrap()->searchable()
                    ->description(fn (ContentIdea $r) => 'Từ khoá: '.$r->primary_keyword.(filled($r->target_url) ? ' · Đẩy: '.$r->target_url : '')),
                TextColumn::make('cluster')->label('Nhóm')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $state) => ContentIdea::CLUSTERS[$state] ?? $state)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('target_url')->label('Trang cần đẩy')->toggleable(isToggledHiddenByDefault: true)
                    ->url(fn (ContentIdea $r) => filled($r->target_url) ? url($r->target_url) : null, true),
                TextColumn::make('status')->label('Trạng thái')->badge()->wrap()
                    ->formatStateUsing(fn (string $state) => ContentIdea::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) { 'drafted' => 'success', 'writing' => 'info', 'dismissed' => 'gray', default => 'warning' })
                    ->description(fn (ContentIdea $r) => filled($r->error) ? '⚠ '.Str::limit($r->error, 140) : null),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->multiple()->options(ContentIdea::STATUSES)
                    ->default(['idea', 'writing', 'drafted']),
                SelectFilter::make('cluster')->label('Nhóm')->options(ContentIdea::CLUSTERS),
            ])
            ->headerActions([
                Action::make('propose')
                    ->label('Gemini đề xuất chủ đề')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('info')
                    ->disabled(fn () => (Cache::get(PlanContentWithGemini::CACHE_KEY)['status'] ?? null) === 'running')
                    ->modalHeading('Gemini đề xuất chủ đề bài viết')
                    ->modalDescription('Gemini xem bảng giá, các bài đã có và dòng xe khách hỏi nhiều để đề xuất chủ đề mới nhắm từ khoá có ý định mua. Mất khoảng 1 phút.')
                    ->modalSubmitActionLabel('Đề xuất')
                    ->schema([
                        TextInput::make('count')->label('Số chủ đề')->numeric()->minValue(3)->maxValue(12)->default(8)->required(),
                        Textarea::make('focus')->label('Hướng tập trung (không bắt buộc)')->rows(2)
                            ->placeholder('Ví dụ: ES 350h và trả góp; khách gia đình; so sánh với Mercedes E-Class'),
                    ])
                    ->action(function (array $data): void {
                        Cache::put(PlanContentWithGemini::CACHE_KEY, ['status' => 'running'], now()->addHours(6));
                        PlanContentWithGemini::dispatch((int) $data['count'], $data['focus'] ?? null);

                        Notification::make()->title('Gemini đang lập kế hoạch')
                            ->body('Chủ đề mới tự hiện trong bảng khi xong (khoảng 1 phút).')->info()->send();
                    }),
                CreateAction::make()->label('Thêm chủ đề tự nghĩ'),
            ])
            ->recordActions([
                Action::make('write')
                    ->label('Viết bài')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('info')
                    ->visible(fn (ContentIdea $r) => $r->status === 'idea')
                    ->requiresConfirmation()
                    ->modalHeading(fn (ContentIdea $r) => 'Gemini viết: '.$r->title)
                    ->modalDescription('Bài 1.200–1.800 từ, có bảng giá, FAQ, thẻ SEO và link về trang cần đẩy — mất 1–3 phút. Bài lưu ở trạng thái NHÁP: đọc lại số liệu rồi mới chuyển sang Đã đăng.')
                    ->modalSubmitActionLabel('Viết bài nháp')
                    ->action(function (ContentIdea $r): void {
                        $r->update(['status' => 'writing', 'error' => null]);
                        WriteDraftFromIdea::dispatch($r->id);

                        Notification::make()->title('Gemini bắt đầu viết')->body('Khi xong, trạng thái đổi thành "Đã có bài nháp".')->info()->send();
                    }),
                Action::make('openDraft')
                    ->label('Mở bài')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->visible(fn (ContentIdea $r) => $r->status === 'drafted' && $r->post_id && $r->post)
                    ->url(fn (ContentIdea $r) => PostResource::getUrl('edit', ['record' => $r->post])),
                ActionGroup::make([
                    Action::make('dismiss')
                        ->label('Bỏ qua')
                        ->icon(Heroicon::OutlinedXMark)
                        ->color('gray')
                        ->visible(fn (ContentIdea $r) => $r->status === 'idea')
                        ->action(fn (ContentIdea $r) => $r->update(['status' => 'dismissed'])),
                    Action::make('restore')
                        ->label('Khôi phục')
                        ->icon(Heroicon::OutlinedArrowUturnLeft)
                        ->color('gray')
                        ->visible(fn (ContentIdea $r) => $r->status === 'dismissed')
                        ->action(fn (ContentIdea $r) => $r->update(['status' => 'idea'])),
                    EditAction::make()->visible(fn (ContentIdea $r) => $r->status !== 'writing'),
                    DeleteAction::make()->visible(fn (ContentIdea $r) => $r->status !== 'writing'),
                ]),
            ]);
    }

    private static function busy(): bool
    {
        return (Cache::get(PlanContentWithGemini::CACHE_KEY)['status'] ?? null) === 'running'
            || ContentIdea::where('status', 'writing')->exists();
    }

    private static function planStatus(): string
    {
        $plan = Cache::get(PlanContentWithGemini::CACHE_KEY);

        return match ($plan['status'] ?? null) {
            'running' => '⏳ Gemini đang lập kế hoạch chủ đề — chủ đề mới tự hiện khi xong.',
            'error' => '⚠ Lần đề xuất gần nhất lỗi: '.$plan['message'],
            'done' => '✓ Lần đề xuất gần nhất thêm '.$plan['count'].' chủ đề. Viết 2–3 bài/tuần, đọc lại từng bài trước khi đăng — đăng ồ ạt bài AI không biên tập dễ bị Google hạ hạng.',
            default => 'Bấm "Gemini đề xuất chủ đề" để có danh sách bài nên viết. Viết 2–3 bài/tuần, đọc lại từng bài trước khi đăng.',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageContentIdeas::route('/'),
        ];
    }
}
