<?php

namespace App\Media;

final readonly class StoredMedia
{
    public function __construct(
        public string $path,
        public string $url,
        public string $type,
        public int $size,
        public ?int $width = null,
        public ?int $height = null,
    ) {}

    /** @return array{path:string,url:string,type:string,size:int,width:?int,height:?int} */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'url' => $this->url,
            'type' => $this->type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
