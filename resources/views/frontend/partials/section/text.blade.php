{{-- Nội dung soạn trong ô có định dạng. Nội dung cũ nhập bằng ô văn bản thuần
     vẫn giữ được dấu xuống dòng — catalog_rich_text lo cả hai trường hợp. --}}
<div class="prose">{!! catalog_rich_text($section['body'] ?? '') !!}</div>
