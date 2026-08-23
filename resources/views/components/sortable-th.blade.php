@props([
    'label'            => '',
    'key'              => '',
    'currentSort'      => '',
    'currentDirection' => 'asc',
    'extraParams'      => [],
])

@php
    $isActive  = $currentSort === $key;
    $nextDir   = ($isActive && $currentDirection === 'asc') ? 'desc' : 'asc';
    $url       = request()->fullUrlWithQuery(array_merge((array) $extraParams, ['sort' => $key, 'direction' => $nextDir, 'page' => 1]));
    $iconClass = $isActive ? ($currentDirection === 'asc' ? 'sort-asc' : 'sort-desc') : 'sort-neutral';
    $iconText  = $isActive ? ($currentDirection === 'asc' ? '↑' : '↓') : '⇅';
    $tooltip   = $isActive
        ? ($currentDirection === 'asc' ? 'คลิกเพื่อเรียงจากมากไปน้อย' : 'คลิกเพื่อเรียงจากน้อยไปมาก')
        : 'เรียงจากน้อยไปมาก';
    $ariaSort  = $isActive ? ($currentDirection . 'ending') : 'none';
@endphp

<th aria-sort="{{ $ariaSort }}" {{ $attributes }}>
    <a href="{{ $url }}"
       class="sort-link"
       title="{{ $tooltip }}"
       aria-label="{{ $label }} — {{ $tooltip }}">
        {{ $label }}<span class="sort-icon {{ $iconClass }}" aria-hidden="true">{{ $iconText }}</span>
    </a>
</th>
