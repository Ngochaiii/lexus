<?php

namespace App\Filament\Forms\Components;

use App\Media\LocalMediaStore;
use Closure;
use Filament\Forms\Components\Field;

class NativeMediaUpload extends Field
{
    protected string $view = 'filament.forms.components.native-media-upload';

    protected string|Closure $directory = 'catalog/media';

    protected string|Closure $kind = 'image';

    protected bool|Closure $isMultiple = false;

    protected bool|Closure $isReorderable = false;

    protected int|string|Closure $previewHeight = 160;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(static function (NativeMediaUpload $component, mixed $state): void {
            if ($component->isMultiple()) {
                $component->state(array_values(array_filter(
                    is_array($state) ? $state : (filled($state) ? [$state] : []),
                    'filled',
                )));

                return;
            }

            if (is_array($state)) {
                $component->state(collect($state)->first(fn (mixed $item): bool => filled($item)));
            }
        });

        $this->mutateDehydratedStateUsing(static function (NativeMediaUpload $component, mixed $state): mixed {
            if ($component->isMultiple()) {
                return array_values(array_filter(is_array($state) ? $state : [], 'filled'));
            }

            if (is_array($state)) {
                return collect($state)->first(fn (mixed $item): bool => filled($item));
            }

            return filled($state) ? $state : null;
        });

        $this->rule(static function (NativeMediaUpload $component): Closure {
            return static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                $paths = ($component->isMultiple() || is_array($value)) ? (array) $value : [$value];

                foreach ($paths as $path) {
                    if (blank($path)) {
                        continue;
                    }

                    if (! LocalMediaStore::isAllowedStoredValue($path, $component->getKind())) {
                        $fail('Đường dẫn media không hợp lệ hoặc không đúng loại file.');

                        return;
                    }
                }
            };
        });
    }

    public function directory(string|Closure $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function image(): static
    {
        $this->kind = 'image';

        return $this;
    }

    public function pdf(): static
    {
        $this->kind = 'pdf';

        return $this;
    }

    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function reorderable(bool|Closure $condition = true): static
    {
        $this->isReorderable = $condition;

        return $this;
    }

    public function imagePreviewHeight(int|string|Closure $height): static
    {
        $this->previewHeight = $height;

        return $this;
    }

    public function getDirectory(): string
    {
        return (string) $this->evaluate($this->directory);
    }

    public function getKind(): string
    {
        return (string) $this->evaluate($this->kind);
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function isReorderable(): bool
    {
        return (bool) $this->evaluate($this->isReorderable);
    }

    public function getPreviewHeight(): string
    {
        $height = $this->evaluate($this->previewHeight);

        return is_int($height) ? "{$height}px" : (string) $height;
    }

    public function getAcceptedTypes(): string
    {
        return $this->getKind() === 'pdf'
            ? 'application/pdf,.pdf'
            : 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp';
    }

    public function getMaxBytes(): int
    {
        $key = $this->getKind() === 'pdf' ? 'max_pdf_size_kb' : 'max_image_size_kb';

        return max(1, (int) config("media.{$key}")) * 1024;
    }

    public function getClientImageMaxDimension(): int
    {
        return max(400, (int) config('media.client_image_max_dimension', 1920));
    }

    public function getClientImageQuality(): float
    {
        $percent = min(100, max(40, (int) config('media.client_image_quality', 82)));

        return $percent / 100;
    }
}
