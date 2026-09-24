{{-- Hiện khi đang có job Gemini chạy nền; hỏi lại kết quả 4 giây một lần. --}}
<div wire:poll.4s="pollGeminiArticle"
     class="rounded-lg border border-info-300 bg-info-50 px-4 py-3 text-sm text-info-800 dark:border-info-700 dark:bg-info-950 dark:text-info-200">
    ⏳ Gemini đang nghiên cứu từ khoá và viết bài — thường 1–3 phút. Cứ để trang này mở, bài sẽ tự điền vào khi xong.
</div>
