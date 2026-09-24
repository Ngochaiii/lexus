<?php

namespace App\Console\Commands;

use App\Media\MediaStore;
use App\Support\Catalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLexusHandover extends Command
{
    protected $signature = 'catalog:import-lexus-handover {--apply : Apply the curated content and save a backup first}';

    protected $description = 'Preview or import curated Lexus Thang Long images and vehicle content';

    public function handle(): int
    {
        $data = json_decode(file_get_contents(database_path('data/lexus-handover-content.json')), true, flags: JSON_THROW_ON_ERROR);
        $assets = json_decode(file_get_contents(database_path('data/lexus-handover-assets.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->info(count($data['products']).' vehicles / '.count($data['pages']).' pages / '.count($assets).' images');
        if (! $this->option('apply')) {
            $this->line('Preview only. --apply replaces the listed content fields and saves a private JSON backup.');
            return self::SUCCESS;
        }
        foreach ($assets as $asset) {
            if (! app(MediaStore::class)->exists($asset['path'])) {
                $this->error('Missing media: '.$asset['path']);
                return self::FAILURE;
            }
        }
        $products = Catalog::query('product')->with(['options', 'variants'])->whereIn('slug', array_keys($data['products']))->get()->keyBy('slug');
        $pages = Catalog::query('page')->whereIn('slug', array_keys($data['pages']))->get()->keyBy('slug');
        if ($products->count() !== count($data['products']) || $pages->count() !== count($data['pages'])) {
            $this->error('Expected Lexus products/pages are missing. No data changed.');
            return self::FAILURE;
        }
        $directory = storage_path('app/private/handover-backups');
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $backup = $directory.'/lexus-'.now()->format('Ymd-His-u').'.json';
        if (file_put_contents($backup, json_encode(['products' => $products, 'pages' => $pages], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) === false) {
            $this->error('Could not save backup. No data changed.');
            return self::FAILURE;
        }
        DB::transaction(function () use ($data, $products, $pages): void {
            foreach ($data['products'] as $slug => $content) {
                $product = $products[$slug];
                $product->update($content['attributes']);
                foreach ($content['variants'] as $index => $variant) {
                    $existing = $product->variants->values()->get($index);
                    $existing ? $existing->update($variant) : $product->variants()->create($variant);
                }
                foreach ($content['options'] ?? [] as $option) {
                    $product->options()->updateOrCreate(['name' => $option['name']], $option);
                }
            }
            foreach ($data['pages'] as $slug => $attributes) {
                $pages[$slug]->update($attributes);
            }
        });
        $this->info('Imported. Backup: '.$backup);
        return self::SUCCESS;
    }
}
