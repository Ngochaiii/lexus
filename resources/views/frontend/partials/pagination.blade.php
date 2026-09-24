{{--
    Phân trang theo hệ CSS Lexus (.section-foot + .text-link), thay bản của
    cars-backend vốn dùng class của giao diện kia.

    Chỉ hiện khi thật sự có nhiều hơn một trang.
--}}
@if ($paginator->hasPages())
    <nav class="section-foot" aria-label="Phân trang">
        @if ($paginator->onFirstPage())
            <small>Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</small>
        @else
            <a class="text-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Trang trước</a>
        @endif

        @if ($paginator->hasMorePages())
            <a class="text-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Trang sau</a>
        @else
            <small>Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</small>
        @endif
    </nav>
@endif
