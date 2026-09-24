@php
    $statePath = $getStatePath();
    $isMultiple = $isMultiple();
    $isReorderable = $isReorderable();
    $kind = $getKind();
    $maxBytes = $getMaxBytes();
    $clientImageMaxDimension = $getClientImageMaxDimension();
    $clientImageQuality = $getClientImageQuality();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        class="native-media"
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            uploading: false,
            error: '',
            multiple: @js($isMultiple),
            reorderable: @js($isReorderable),
            kind: @js($kind),
            endpoint: @js(route('admin.media.store', absolute: false)),
            csrf: @js(csrf_token()),
            directory: @js($getDirectory()),
            mediaBase: @js(rtrim((string) config('media.url', '/storage'), '/')),
            maxBytes: @js($maxBytes),
            clientImageMaxDimension: @js($clientImageMaxDimension),
            clientImageQuality: @js($clientImageQuality),
            disabled: @js($isDisabled()),
            progressText: '',
            optimizationNote: '',

            paths() {
                if (Array.isArray(this.state)) return this.state.filter(Boolean)
                return this.state ? [this.state] : []
            },

            mediaUrl(path) {
                if (! path) return ''
                if (/^(https?:)?\/\//.test(path) || path.startsWith('/') || path.startsWith('data:')) return path

                return this.mediaBase + '/' + path.split('/').map(encodeURIComponent).join('/')
            },

            fileName(path) {
                try {
                    return decodeURIComponent(String(path).split('/').pop())
                } catch (_) {
                    return String(path).split('/').pop()
                }
            },

            async choose(event) {
                const files = Array.from(event.target.files || [])
                event.target.value = ''

                await this.upload(files)
            },

            async drop(event) {
                if (this.disabled) return

                await this.upload(Array.from(event.dataTransfer?.files || []))
            },

            async upload(files) {
                if (! files.length) return

                if (! this.multiple && files.length > 1) {
                    this.error = 'Chỉ được chọn một file.'
                    return
                }

                this.uploading = true
                this.error = ''
                this.optimizationNote = ''

                try {
                    const uploaded = []
                    let originalBytes = 0
                    let sentBytes = 0

                    for (const file of files) {
                        this.progressText = this.kind === 'image'
                            ? `Đang tối ưu ${file.name}…`
                            : `Đang chuẩn bị ${file.name}…`

                        const prepared = await this.prepareUpload(file)

                        if (prepared.blob.size > this.maxBytes) {
                            throw new Error(`File ${file.name} sau khi tối ưu vẫn vượt quá ${Math.round(this.maxBytes / 1048576)} MB.`)
                        }

                        originalBytes += file.size
                        sentBytes += prepared.blob.size

                        const body = new FormData()
                        body.append('file', prepared.blob, prepared.name)
                        body.append('directory', this.directory)
                        body.append('kind', this.kind)

                        this.progressText = `Đang tải ${prepared.name}…`

                        const response = await fetch(this.endpoint, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                            body,
                        })

                        const payload = await response.json().catch(() => ({}))

                        if (! response.ok || ! payload?.data?.path) {
                            const message = payload?.errors?.file?.[0]
                                || payload?.message
                                || 'Không thể tải file lên máy chủ.'

                            throw new Error(message)
                        }

                        uploaded.push(payload.data.path)
                    }

                    if (sentBytes < originalBytes) {
                        const percent = Math.round((1 - (sentBytes / originalBytes)) * 100)
                        this.optimizationNote = `Đã giảm ${percent}% dung lượng trước khi tải lên.`
                    }

                    this.state = this.multiple
                        ? [...this.paths(), ...uploaded]
                        : uploaded[0]
                } catch (exception) {
                    this.error = exception.message || 'Không thể tải file lên máy chủ.'
                } finally {
                    this.uploading = false
                    this.progressText = ''
                }
            },

            async prepareUpload(file) {
                if (this.kind !== 'image' || ! window.createImageBitmap) {
                    return { blob: file, name: file.name }
                }

                let bitmap

                try {
                    try {
                        bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' })
                    } catch (_) {
                        bitmap = await createImageBitmap(file)
                    }

                    const longestSide = Math.max(bitmap.width, bitmap.height)
                    const scale = Math.min(1, this.clientImageMaxDimension / longestSide)
                    const width = Math.max(1, Math.round(bitmap.width * scale))
                    const height = Math.max(1, Math.round(bitmap.height * scale))
                    const canvas = document.createElement('canvas')
                    canvas.width = width
                    canvas.height = height

                    const context = canvas.getContext('2d')
                    if (! context) return { blob: file, name: file.name }

                    context.drawImage(bitmap, 0, 0, width, height)

                    const webp = await new Promise((resolve) => {
                        canvas.toBlob(resolve, 'image/webp', this.clientImageQuality)
                    })

                    if (! webp || webp.type !== 'image/webp' || webp.size >= file.size) {
                        return { blob: file, name: file.name }
                    }

                    const stem = file.name.replace(/\.[^.]+$/, '') || 'image'

                    return { blob: webp, name: `${stem}.webp` }
                } catch (_) {
                    // Trình duyệt cũ hoặc ảnh không giải mã được: để server
                    // kiểm tra file gốc như luồng cũ, không làm hỏng upload.
                    return { blob: file, name: file.name }
                } finally {
                    if (bitmap?.close) bitmap.close()
                }
            },

            remove(index) {
                if (this.multiple) {
                    const next = [...this.paths()]
                    next.splice(index, 1)
                    this.state = next
                } else {
                    this.state = null
                }
            },

            move(index, direction) {
                const next = [...this.paths()]
                const target = index + direction
                if (target < 0 || target >= next.length) return
                ;[next[index], next[target]] = [next[target], next[index]]
                this.state = next
            },
        }"
    >
        <div class="native-media__list" x-show="paths().length" x-cloak>
            <template x-for="(path, index) in paths()" :key="path + index">
                <div class="native-media__item">
                    <template x-if="kind === 'image'">
                        <img
                            class="native-media__preview"
                            :src="mediaUrl(path)"
                            :alt="fileName(path)"
                            style="height: {{ $getPreviewHeight() }}"
                        >
                    </template>

                    <template x-if="kind === 'pdf'">
                        <a class="native-media__document" :href="mediaUrl(path)" target="_blank" rel="noopener">
                            <span class="native-media__pdf">PDF</span>
                            <span x-text="fileName(path)"></span>
                        </a>
                    </template>

                    <div class="native-media__meta">
                        <span class="native-media__name" x-text="fileName(path)"></span>
                        <div class="native-media__actions">
                            <template x-if="multiple && reorderable">
                                <span>
                                    <button type="button" class="native-media__icon" @click="move(index, -1)" :disabled="index === 0" aria-label="Đưa lên">↑</button>
                                    <button type="button" class="native-media__icon" @click="move(index, 1)" :disabled="index === paths().length - 1" aria-label="Đưa xuống">↓</button>
                                </span>
                            </template>
                            <button type="button" class="native-media__remove" @click="remove(index)">Gỡ</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <label
            class="native-media__drop"
            :class="{ 'native-media__drop--busy': uploading }"
            @dragover.prevent
            @drop.prevent="drop"
        >
            <input
                class="native-media__input"
                type="file"
                accept="{{ $getAcceptedTypes() }}"
                @if ($isMultiple) multiple @endif
                @change="choose"
                @disabled($isDisabled())
            >
            <span class="native-media__plus" aria-hidden="true">＋</span>
            <span>
                <strong x-text="uploading ? (progressText || 'Đang tải lên…') : @js($isMultiple ? 'Chọn hoặc kéo nhiều file' : 'Chọn hoặc kéo file')"></strong>
                <small>
                    @if ($kind === 'pdf')
                        PDF · tối đa {{ (int) round($maxBytes / 1048576) }} MB/file
                    @else
                        JPEG, PNG hoặc WebP · tự tối ưu WebP tối đa {{ $clientImageMaxDimension }} px trước khi gửi
                    @endif
                </small>
            </span>
        </label>

        <p class="native-media__error" x-show="error" x-text="error" x-cloak></p>
        <p class="native-media__success" x-show="optimizationNote" x-text="optimizationNote" x-cloak></p>
    </div>
</x-dynamic-component>

@once
    <style>
        [x-cloak] { display: none !important; }
        .native-media { display: grid; gap: .75rem; }
        .native-media__list { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .75rem; }
        .native-media__item { overflow: hidden; border: 1px solid rgba(120,120,120,.22); border-radius: .75rem; background: rgba(127,127,127,.04); }
        .native-media__preview { display: block; width: 100%; object-fit: cover; background: #eef0f2; }
        .native-media__document { display: flex; min-height: 96px; align-items: center; justify-content: center; gap: .65rem; padding: 1rem; color: inherit; text-decoration: none; }
        .native-media__pdf { border-radius: .4rem; background: #b91c1c; color: #fff; font-weight: 800; padding: .35rem .45rem; }
        .native-media__meta { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .55rem .65rem; }
        .native-media__name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; color: #71717a; }
        .native-media__actions { display: flex; align-items: center; gap: .35rem; flex: none; }
        .native-media__icon, .native-media__remove { border: 0; background: transparent; cursor: pointer; font-size: .75rem; }
        .native-media__icon { padding: .2rem; color: #52525b; }
        .native-media__icon:disabled { cursor: default; opacity: .25; }
        .native-media__remove { color: #dc2626; font-weight: 650; }
        .native-media__drop { display: flex; min-height: 92px; align-items: center; justify-content: center; gap: .75rem; border: 1.5px dashed rgba(113,113,122,.45); border-radius: .75rem; padding: 1rem; cursor: pointer; text-align: left; transition: border-color .15s, background .15s; }
        .native-media__drop:hover { border-color: rgb(217 119 6); background: rgba(245,158,11,.05); }
        .native-media__drop--busy { cursor: wait; opacity: .65; pointer-events: none; }
        .native-media__input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }
        .native-media__plus { font-size: 1.6rem; line-height: 1; color: rgb(217 119 6); }
        .native-media__drop strong, .native-media__drop small { display: block; }
        .native-media__drop small { margin-top: .2rem; color: #71717a; font-size: .75rem; }
        .native-media__error { margin: 0; color: #dc2626; font-size: .8rem; }
        .native-media__success { margin: 0; color: #15803d; font-size: .8rem; }
        .dark .native-media__item { border-color: rgba(255,255,255,.13); background: rgba(255,255,255,.03); }
        .dark .native-media__name, .dark .native-media__drop small { color: #a1a1aa; }
        .dark .native-media__icon { color: #d4d4d8; }
    </style>
@endonce
