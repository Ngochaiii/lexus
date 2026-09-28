<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Cài báo lead qua Telegram:
 *
 *   1. Telegram → @BotFather → /newbot → lấy token, ghi TELEGRAM_BOT_TOKEN vào .env
 *   2. Mở bot vừa tạo, bấm Start, nhắn một câu bất kỳ
 *   3. php artisan config:clear && php artisan lead:telegram-test
 *      → chưa có TELEGRAM_CHAT_ID: in ra chat id của người vừa nhắn
 *      → đã có: gửi một tin thử tới từng chat id
 *
 * Sau khi sửa .env: php artisan optimize && supervisorctl restart lexus-queue:*
 */
class TelegramLeadTest extends Command
{
    protected $signature = 'lead:telegram-test';

    protected $description = 'Lấy chat id / gửi tin thử cho báo lead qua Telegram';

    public function handle(): int
    {
        $token = config('catalog.leads.telegram.bot_token');
        $chats = (array) config('catalog.leads.telegram.chat_ids', []);

        if (blank($token)) {
            $this->error('Chưa có TELEGRAM_BOT_TOKEN trong .env (tạo bot bằng @BotFather).');

            return self::FAILURE;
        }

        if ($chats === []) {
            $updates = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getUpdates")->json('result') ?? [];
            $found = collect($updates)->map(fn ($u) => $u['message']['chat'] ?? null)->filter()->unique('id');

            if ($found->isEmpty()) {
                $this->warn('Chưa thấy tin nhắn nào. Mở bot trong Telegram, bấm Start, nhắn một câu rồi chạy lại.');

                return self::FAILURE;
            }

            $this->info('Ghi vào .env (nhiều người thì cách nhau dấu phẩy):');
            foreach ($found as $chat) {
                $this->line(sprintf('  TELEGRAM_CHAT_ID=%s   # %s', $chat['id'], trim(($chat['first_name'] ?? '').' '.($chat['last_name'] ?? '')) ?: ($chat['title'] ?? '')));
            }

            return self::SUCCESS;
        }

        foreach ($chats as $chat) {
            $response = Http::timeout(10)->asJson()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => (string) $chat,
                'text' => '✅ Đã nối báo lead website '.config('app.url').'. Khách để lại số, tin sẽ về đây ngay.',
            ]);
            $response->successful()
                ? $this->info("Đã gửi tin thử tới {$chat}.")
                : $this->error("Gửi tới {$chat} lỗi: ".$response->json('description', $response->status()));
        }

        return self::SUCCESS;
    }
}
