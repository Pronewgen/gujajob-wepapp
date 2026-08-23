@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-002-manage-supplier-information/style.css'])
@endsection

@section('content')
    <div class="page-container supplier-show-page">
        <x-page-header :title="$pageTitle" />

        <section class="supplier-form-card">
            <h3 class="supplier-form-heading">ข้อมูลผู้ประกอบการ</h3>

            <div class="supplier-form-grid">
                <div class="form-field supplier-type-field">
                    <label>ประเภทผู้ประกอบการ</label>
                    <select disabled>
                        @foreach (\App\Models\Dealer::DEALER_TYPES as $code => $label)
                            <option value="{{ $code }}" @selected($supplier->dealer_type === (string) $code)>{{ $label }}</option>
                        @endforeach
                        @if (!$supplier->dealer_type)
                            <option value="" selected>-</option>
                        @endif
                    </select>
                </div>

                <div class="form-field">
                    <label>ชื่อผู้ประกอบการ</label>
                    <input type="text" value="{{ $supplier->dealer_name }}" readonly>
                </div>

                <div class="form-field">
                    <label>เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" value="{{ $supplier->dealer_tax_id ?? '' }}" readonly>
                </div>

                <div class="form-field">
                    <label>เลขที่อาคาร บ้าน หรือห้อง</label>
                    <input type="text" value="{{ $supplier->dealer_addr_no ?? '' }}" readonly>
                </div>

                <div class="form-field">
                    <label>ซอย</label>
                    <input type="text" value="{{ $supplier->dealer_alley ?? '' }}" readonly>
                </div>

                <div class="form-field">
                    <label>ถนน</label>
                    <input type="text" value="{{ $supplier->dealer_street ?? '' }}" readonly>
                </div>

                <div class="form-field">
                    <label>จังหวัด</label>
                    <input type="text" value="{{ $supplier->province->province_name ?? '' }}" readonly>
                </div>

                <div class="form-field">
                    <label>อำเภอ</label>
                    <input type="text" value="{{ $supplier->amphur->amphur_name ?? '' }}" readonly>
                </div>

                <div class="form-field">
                    <label>ตำบล</label>
                    <input type="text" value="{{ $supplier->tambon->tambon_name ?? '' }}" readonly>
                </div>

                <div class="form-field">
                    <label>รหัสไปรษณีย์</label>
                    <input type="text" value="{{ $supplier->dealer_zipcode ?? '' }}" readonly>
                </div>

                <div class="form-field">
                    <label>ชื่อผู้ติดต่อ</label>
                    <input type="text" value="{{ $supplier->dealer_contact ?? '' }}" readonly>
                </div>

                <div class="form-field">
                    <label>เบอร์โทรศัพท์ผู้ติดต่อ</label>
                    <input type="text" value="{{ $supplier->dealer_phone ?? '' }}" readonly>
                </div>
            </div>

            <div class="supplier-form-actions">
                <a class="cancel-btn" href="{{ route('asset.suppliers.index') }}">ย้อนกลับ</a>
            </div>
        </section>
    </div>
@endsection

