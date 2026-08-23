{{-- resources/views/components/app-pagination.blade.php --}}
{{-- Usage: <x-app-pagination :paginator="$paginator" /> --}}
@props(['paginator'])

@php
    $cur   = $paginator->currentPage();
    $last  = $paginator->lastPage();
    $param = $paginator->getPageName();

    if ($last <= 5) {
        $pages = range(1, $last);
    } elseif ($cur <= 3) {
        $pages = [1, 2, 3, 'ellipsis', $last];
    } elseif ($cur >= $last - 2) {
        $pages = [1, 'ellipsis', $last - 2, $last - 1, $last];
    } else {
        $pages = [1, 'ellipsis', $cur - 1, $cur, $cur + 1, 'ellipsis', $last];
    }
@endphp

<div class="app-pagination-actions">

    {{-- ‹ Previous --}}
    @if ($paginator->onFirstPage())
        <span class="app-pagination-link is-disabled" aria-disabled="true" aria-label="ย้อนกลับ">‹</span>
    @else
        <a class="app-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="ย้อนกลับ">‹</a>
    @endif

    {{-- Page numbers --}}
    @foreach ($pages as $p)
        @if ($p === 'ellipsis')
            <span class="app-pagination-ellipsis" aria-hidden="true">…</span>
        @elseif ((int) $p === $cur)
            <span class="app-pagination-link is-active" aria-current="page">{{ $p }}</span>
        @else
            <a class="app-pagination-link" href="{{ $paginator->url($p) }}">{{ $p }}</a>
        @endif
    @endforeach

    {{-- ไปที่หน้า form --}}
    <form
        class="app-page-jump-form"
        method="GET"
        action="{{ url()->current() }}"
    >
        @foreach (request()->except($param) as $key => $value)
            @if (!is_array($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="app-page-jump-{{ $param }}" class="app-page-jump-label">ไปที่หน้า</label>
        <input
            id="app-page-jump-{{ $param }}"
            type="number"
            name="{{ $param }}"
            min="1"
            max="{{ $last }}"
            step="1"
            inputmode="numeric"
            class="app-page-jump-input"
            data-last-page="{{ $last }}"
            autocomplete="off"
            aria-label="ระบุเลขหน้าที่ต้องการ"
        >
        <button type="submit" class="app-page-jump-button">ไป</button>
    </form>

    {{-- › Next --}}
    @if ($paginator->hasMorePages())
        <a class="app-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="ถัดไป">›</a>
    @else
        <span class="app-pagination-link is-disabled" aria-disabled="true" aria-label="ถัดไป">›</span>
    @endif

</div>
