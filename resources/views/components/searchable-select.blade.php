@props([
    'name',
    'id',
    'placeholder' => '-- เลือก --',
    'options'     => [],
    'selected'    => '',
    'required'    => false,
    'disabled'    => false,
])

@php
    $mappedOptions = collect($options)->map(function ($opt) {
        $opt = (object) $opt;
        return [
            'value'      => $opt->value,
            'label'      => $opt->label,
            'searchText' => $opt->searchText ?? $opt->label,
        ];
    })->values()->all();
@endphp

{{--
    Custom autocomplete dropdown.

    Structure:
      .guja-autocomplete[data-searchable-select]
        input.guja-autocomplete__input     ← visible text/search input
        span.guja-autocomplete__arrow      ← chevron icon (pointer-events:none)
        div.guja-autocomplete__items       ← option list (position:absolute)
        input[hidden].guja-autocomplete__value  ← actual submitted value

    Options are injected via window._saOptions[id] by the inline script below.
    JS initialiser: initSearchableSelects() in resources/js/components/searchable-select.js
--}}
<div
    class="guja-autocomplete{{ $disabled ? ' guja-autocomplete--disabled' : '' }}"
    data-searchable-select>

    {{-- Visible text input shown to the user --}}
    <input
        type="text"
        class="guja-autocomplete__input"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        spellcheck="false"
        aria-haspopup="listbox"
        aria-expanded="false"
        {{ $disabled ? 'disabled' : '' }}>

    {{-- Chevron — pointer-events:none so clicks fall through to the text input --}}
    <span class="guja-autocomplete__arrow" aria-hidden="true">▼</span>

    {{-- Dropdown list — rendered and managed by JS --}}
    <div class="guja-autocomplete__items" role="listbox"></div>

    {{-- Hidden input: carries the real submitted value and the field id
         so that label[for], getElementById, old(), and form submit all work. --}}
    <input
        type="hidden"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $selected }}"
        class="guja-autocomplete__value"
        {{ $required ? 'required' : '' }}>
</div>

{{-- Inject option data keyed by field id so multiple instances on one page
     can each load their own list without conflict. --}}
<script>
(function () {
    window._saOptions = window._saOptions || {};
    window._saOptions[{{ Js::from($id) }}] = @json($mappedOptions);
})();
</script>
