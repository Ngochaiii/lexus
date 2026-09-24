<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

class VehicleStudioTest extends TestCase
{
    private function vehicle(): Product
    {
        return Product::create(['name' => 'Studio RX', 'slug' => 'studio-rx', 'status' => 'published', 'hero' => ['src' => '/assets/hero.webp']]);
    }

    public function test_admin_can_upload_a_turntable_frame_to_its_directory(): void
    {
        $this->actingAs(\App\Models\User::create(['name' => 'Admin', 'email' => 'spin@test.local', 'password' => 'x']));
        $response = $this->post('/admin/media', [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('01.jpg', 640, 360),
            'directory' => 'catalog/options/360',
            'kind' => 'image',
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.type', 'image/jpeg');
        $product = $this->vehicle();
        $frames = [$response->json('data.path')];
        $product->update(['hero' => ['src' => $frames[0]]]);
        $option = $product->options()->create(['name' => 'Trắng', 'spin_frames' => $frames]);
        \Livewire\Livewire::test(\App\Filament\Resources\Products\Pages\EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()->call('save')->assertHasNoFormErrors();
        $this->assertSame($frames, $option->refresh()->spin_frames);
    }

    public function test_any_number_of_colors_and_no_duplicate_studio(): void
    {
        $product = $this->vehicle();
        foreach (range(1, 5) as $i) {
            $product->options()->create(['name' => "Màu $i", 'hex' => '#ffffff', 'image' => '/assets/rx.webp', 'sort' => $i]);
        }
        $html = $this->get('/san-pham/studio-rx')->assertOk()->assertSee('vehicle-studio.js')->getContent();
        $this->assertSame(5, substr_count($html, 'data-color="'));
        $this->assertSame(1, substr_count($html, 'id="vehicle-studio"'));
        $this->assertLessThan(strpos($html, 'data-color="4"'), strpos($html, 'data-color="0"'));
    }

    public function test_missing_color_picture_is_not_presented_as_real_color(): void
    {
        $this->vehicle()->options()->create(['name' => 'Trắng', 'hex' => '#ffffff']);
        $this->get('/san-pham/studio-rx')->assertOk()->assertSee('Ảnh tổng quan minh họa')->assertSee('data-studio-image', false);
    }

    public function test_frames_survive_duplicate_and_keep_order(): void
    {
        $product = $this->vehicle();
        $frames = ['/storage/02.webp', '/storage/01.webp', '/storage/03.webp'];
        $product->options()->create(['name' => 'White', 'spin_frames' => $frames]);
        $copy = $product->fresh()->duplicate();
        $this->assertSame($frames, $copy->options->first()->spin_frames);
        $this->get('/san-pham/studio-rx')->assertOk()->assertSee('02.webp')->assertSee('01.webp');
    }

    public function test_option_data_cannot_break_out_of_script_or_css(): void
    {
        $this->vehicle()->options()->create(['name' => '</script><script>alert(1)</script>', 'hex' => 'red']);
        $this->get('/san-pham/studio-rx')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('--paint:#bcb8b0', false);
    }

    public function test_empty_and_disabled_options_do_not_load_studio(): void
    {
        $product = $this->vehicle();
        $this->get('/san-pham/studio-rx')->assertOk()->assertDontSee('vehicle-studio.js');
        $product->options()->create(['name' => 'White']);
        config(['catalog.features.options' => false]);
        $this->get('/san-pham/studio-rx')->assertOk()->assertDontSee('vehicle-studio.js');
    }
}
