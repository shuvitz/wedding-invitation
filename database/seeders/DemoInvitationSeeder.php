<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Media;
use App\Models\Page;
use App\Models\Setting;
use App\Services\BlockRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * A fictional, published demo invitation set as the homepage. Safe to run
 * again: it updates the same page (by slug) instead of duplicating it.
 *
 * Everything is made up on purpose. Instagram handles and maps links are
 * left empty (a made-up one may belong to a real person or place), and
 * account numbers are obviously fake so no visitor sends real money.
 * Photos are generated placeholders (gradient + initials, no real people)
 * from database/seeders/demo-photos, copied into the media library.
 *
 *   php artisan db:seed --class=DemoInvitationSeeder
 */
class DemoInvitationSeeder extends Seeder
{
    public const SLUG = 'raka-sekar';

    public function run(): void
    {
        $blocks = $this->blocks();
        $this->assertValid($blocks);

        DB::transaction(function () use ($blocks) {
            $page = Page::firstOrNew(['slug' => self::SLUG]);
            $page->fill([
                'title' => 'Undangan Pernikahan Raka & Sekar',
                'content' => ['blocks' => $blocks],
                'meta_title' => 'Raka & Sekar — Undangan Pernikahan',
                'meta_description' => 'Undangan pernikahan Raka & Sekar, Sabtu, 12 Juni 2027. Contoh undangan fiktif.',
            ]);
            $page->forceFill([
                'status' => PageStatus::Published,
                'published_at' => $page->published_at ?? now(),
                'is_home' => true,
            ])->save();

            Page::query()->whereKeyNot($page->id)->where('is_home', true)->update(['is_home' => false]);

            $setting = Setting::current();
            $setting->site_identity = ['name' => 'Raka & Sekar'] + $setting->site_identity;
            $setting->save();
        });
    }

    /**
     * Same rules the admin's page editor goes through, so the demo can't
     * drift from the block schema.
     *
     * @param  list<array{type: string, data: array<string, mixed>}>  $blocks
     */
    private function assertValid(array $blocks): void
    {
        $rules = [
            'blocks' => ['array', BlockRegistry::uniqueFormKeysRule(), BlockRegistry::singleCoverRule()],
            'blocks.*.type' => ['required', Rule::in(BlockRegistry::types())],
        ];
        foreach ($blocks as $index => $block) {
            $rules += BlockRegistry::rulesFor($block['type'], "blocks.{$index}.data");
        }

        Validator::make(['blocks' => $blocks], $rules)->validate();
    }

    /**
     * Copies a bundled placeholder into the media library, reusing the same
     * row (matched by path) on re-runs. Returns the media ID.
     */
    private function photo(string $name, string $alt): int
    {
        $disk = config('media.disk', 'public');
        $source = __DIR__."/demo-photos/{$name}";
        $path = "media/demo/{$name}";

        Storage::disk($disk)->put($path, file_get_contents($source));
        [$width, $height] = getimagesize($source);

        $media = Media::where('disk', $disk)->where('path', $path)->first() ?? new Media();
        $media->disk = $disk;
        $media->path = $path;
        $media->original_name = "demo-{$name}";
        $media->mime_type = 'image/jpeg';
        $media->size = filesize($source);
        $media->width = $width;
        $media->height = $height;
        $media->alt_text = $alt;
        $media->save();

        return $media->id;
    }

    /**
     * @return list<array{type: string, data: array<string, mixed>}>
     */
    private function blocks(): array
    {
        return [
            ['type' => 'cover', 'data' => [
                'title' => 'The Wedding of',
                'names' => 'Raka & Sekar',
                'greeting' => 'Kepada Yth. Bapak/Ibu/Saudara/i',
                'button_label' => 'Buka Undangan',
                'image_media_id' => $this->photo('cover.jpg', 'Raka & Sekar'),
            ]],
            ['type' => 'rich_text', 'data' => [
                'title' => null,
                'body' => "Dengan penuh rasa syukur, kami mengundang Bapak/Ibu/Saudara/i untuk hadir dan memberikan doa restu pada hari bahagia kami.",
            ]],
            ['type' => 'couple', 'data' => [
                'title' => 'Mempelai',
                'items' => [
                    ['name' => 'Raka Pratama', 'parents' => 'Putra pertama dari Bapak Hendra Pratama & Ibu Wulan Sari', 'instagram' => null, 'image_media_id' => $this->photo('raka.jpg', 'Raka Pratama')],
                    ['name' => 'Sekar Ayu Lestari', 'parents' => 'Putri kedua dari Bapak Bambang Wijaya & Ibu Ratna Dewi', 'instagram' => null, 'image_media_id' => $this->photo('sekar.jpg', 'Sekar Ayu Lestari')],
                ],
            ]],
            ['type' => 'gallery', 'data' => [
                'title' => 'Galeri',
                'items' => [
                    ['media_id' => $this->photo('galeri-1.jpg', 'Galeri foto 1'), 'alt_text' => 'Galeri foto 1'],
                    ['media_id' => $this->photo('galeri-2.jpg', 'Galeri foto 2'), 'alt_text' => 'Galeri foto 2'],
                    ['media_id' => $this->photo('galeri-3.jpg', 'Galeri foto 3'), 'alt_text' => 'Galeri foto 3'],
                ],
            ]],
            ['type' => 'schedule', 'data' => [
                'title' => 'Rangkaian Acara',
                'show_countdown' => true,
                'items' => [
                    ['title' => 'Akad Nikah', 'date' => '2027-06-12', 'start_time' => '08:00', 'end_time' => '10:00', 'venue' => 'Gedung Serbaguna Melati', 'address' => 'Jl. Contoh No. 1, Bandung', 'maps_url' => null],
                    ['title' => 'Resepsi', 'date' => '2027-06-12', 'start_time' => '11:00', 'end_time' => '14:00', 'venue' => 'Gedung Serbaguna Melati', 'address' => 'Jl. Contoh No. 1, Bandung', 'maps_url' => null],
                ],
            ]],
            ['type' => 'form', 'data' => [
                'form_key' => 'rsvp',
                'title' => 'Konfirmasi Kehadiran',
                'submit_label' => 'Kirim',
                'success_message' => 'Terima kasih atas konfirmasi dan doanya!',
                'fields' => [
                    ['name' => 'nama', 'label' => 'Nama', 'type' => 'text', 'required' => true, 'options' => []],
                    ['name' => 'kehadiran', 'label' => 'Kehadiran', 'type' => 'select', 'required' => true, 'options' => ['Hadir', 'Tidak Hadir']],
                    ['name' => 'jumlah_tamu', 'label' => 'Jumlah Tamu', 'type' => 'number', 'required' => false, 'options' => []],
                    ['name' => 'ucapan', 'label' => 'Ucapan & Doa', 'type' => 'textarea', 'required' => false, 'options' => []],
                ],
            ]],
            ['type' => 'gift', 'data' => [
                'title' => 'Amplop Digital',
                'description' => "Doa restu Anda adalah hadiah terindah bagi kami.\nNamun jika ingin memberikan tanda kasih, dapat melalui:",
                'items' => [
                    ['bank' => 'Bank (contoh)', 'account_number' => '0000 0000 00', 'account_name' => 'Raka Pratama'],
                    ['bank' => 'E-wallet (contoh)', 'account_number' => '0800-0000-0000', 'account_name' => 'Sekar Ayu Lestari'],
                ],
            ]],
            ['type' => 'rich_text', 'data' => [
                'title' => 'Terima Kasih',
                'body' => "Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir.\n\nRaka & Sekar",
            ]],
        ];
    }
}
