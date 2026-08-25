@extends('layouts.app')

@section('page-style')
    @vite([
        'resources/css/components/table-actions.css',
        'resources/css/components/pagination.css',
        'resources/css/components/sort-icon.css',
        'resources/css/asset/ASS-005-receive-department-registered-asset/style.css',
    ])
@endsection

@section('content')
    <div class="page-container receiving-page">
        <x-page-header :title="$pageTitle" />

        <section class="receiving-card">
            <form method="GET" action="{{ route('asset.department-receiving.index') }}" id="assetSearchForm">
                <div class="toolbar">
                    <div class="field-group search-type-field">
                        <label for="searchType">ค้นหาจาก</label>
                        <select id="searchType" name="search_by">
                            <option value="all"  @selected(($searchBy ?? 'all') === 'all')>ทั้งหมด</option>
                            <option value="code" @selected(($searchBy ?? 'all') === 'code')>รหัสครุภัณฑ์</option>
                            <option value="name" @selected(($searchBy ?? 'all') === 'name')>ชื่อครุภัณฑ์</option>
                        </select>
                    </div>

                    <div class="field-group search-input-field">
                        <label for="searchInput">คำค้นหา</label>
                        <input
                            id="searchInput"
                            name="keyword"
                            type="search"
                            value="{{ $keyword }}"
                            placeholder="กรอกคำค้นหา"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                        >
                    </div>

                    <div class="field-group search-category-field">
                        <label for="categoryFilter">หมวดครุภัณฑ์</label>
                        <select id="categoryFilter" name="category">
                            <option value="">ทั้งหมด</option>
                            @foreach ($categoryOptions as $cat)
                                <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($sort)
                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="hidden" name="direction" value="{{ $direction }}">
                    @endif

                    <button class="search-btn" type="submit">ค้นหา</button>
                </div>
            </form>

            <div class="table-wrapper">
                <table class="receiving-table">
                    <thead>
                        <tr>
                            <x-sortable-th label="รหัสครุภัณฑ์" key="code"     :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword,'category'=>$category]" />
                            <x-sortable-th label="ชื่อครุภัณฑ์"  key="name"     :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword,'category'=>$category]" />
                            <x-sortable-th label="หมวดครุภัณฑ์"  key="category" :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword,'category'=>$category]" />
                            <x-sortable-th label="มูลค่า"         key="price"    :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword,'category'=>$category]" />
                            <x-sortable-th label="วันที่รับ"      key="date"     :currentSort="$sort" :currentDirection="$direction" :extraParams="['search_by'=>$searchBy,'keyword'=>$keyword,'category'=>$category]" />
                            <th class="action-column">จัดการ</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($assets as $asset)
                            <tr>
                                <td>
                                    <a class="code-link" href="{{ route('asset.department-receiving.show', $asset->id) }}">
                                        @if ($asset->aa_status === '2')
                                            {{ ($asset->asscat_code ?? '') . $asset->ass_code }}
                                        @else
                                            {{ $asset->ass_code ?? '-' }}
                                        @endif
                                    </a>
                                </td>
                                <td>{{ $asset->asscat_name ?? '-' }}</td>
                                <td>{{ $asset->asscat_group ?? '-' }}</td>
                                <td class="value-text">
                                    {{ $asset->ass_price !== null ? number_format((float) $asset->ass_price, 2) : '-' }}
                                </td>
                                <td class="date-text {{ $asset->aa_status === '2' ? 'received' : 'pending' }}">
                                    {{ $asset->ass_trans_date_th ?? '-' }}
                                </td>
                                <td class="action-column">
                                    <div class="table-action-buttons">
                                        @if ($asset->aa_status === '1')
                                            <a class="receive-btn" href="{{ route('asset.department-receiving.receive', $asset->id) }}">รับ</a>
                                        @else
                                            <span class="received-label">รับแล้ว</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="no-data" colspan="6">ไม่พบข้อมูลครุภัณฑ์รอรับลงทะเบียนหน่วยงาน</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <p class="result-summary">
                    แสดง {{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }}
                    จาก {{ $assets->total() }} รายการ
                </p>
                <x-app-pagination :paginator="$assets" />
            </div>
        </section>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-005-receive-department-registered-asset/script.js'])
@endsection
