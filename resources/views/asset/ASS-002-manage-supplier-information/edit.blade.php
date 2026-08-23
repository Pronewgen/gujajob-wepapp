@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-002-manage-supplier-information/style.css'])
@endsection

@section('content')
    <div class="page-container supplier-edit-page">
        <x-page-header :title="$pageTitle" />

        <section class="supplier-form-card">
            <h3 class="supplier-form-heading">แก้ไขข้อมูลผู้ประกอบการ</h3>

            @if ($errors->has('_error'))
                <div class="form-error-box" style="margin-bottom:12px;padding:10px 14px;background:#fee2e2;border:1px solid #f87171;border-radius:6px;font-size:13px;color:#991b1b;">
                    {{ $errors->first('_error') }}
                </div>
            @endif

            <form class="supplier-form" id="supplierEditForm" method="POST" action="{{ route('asset.suppliers.update', $supplier->id) }}" autocomplete="off">
                @csrf
                @method('PUT')

                <div class="supplier-form-grid">
                    <div class="form-field supplier-type-field">
                        <label for="supplierType">ประเภทผู้ประกอบการ <span class="required">*</span></label>
                        <select id="supplierType" name="dealer_type" required>
                            <option value="">-- เลือกประเภท --</option>
                            @foreach ($dealerTypes as $code => $label)
                                <option value="{{ $code }}" @selected(old('dealer_type', $supplier->dealer_type) === (string) $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('dealer_type')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="supplierName">ชื่อผู้ประกอบการ <span class="required">*</span></label>
                        <input id="supplierName" name="dealer_name" type="text" value="{{ old('dealer_name', $supplier->dealer_name) }}" required>
                        @error('dealer_name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="taxId">เลขประจำตัวผู้เสียภาษี</label>
                        <input id="taxId" name="dealer_tax_id" type="text" maxlength="20" value="{{ old('dealer_tax_id', $supplier->dealer_tax_id) }}">
                        @error('dealer_tax_id')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="addressNo">เลขที่อาคาร บ้าน หรือห้อง</label>
                        <input id="addressNo" name="dealer_addr_no" type="text" maxlength="20" value="{{ old('dealer_addr_no', $supplier->dealer_addr_no) }}">
                        @error('dealer_addr_no')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="alley">ซอย</label>
                        <input id="alley" name="dealer_alley" type="text" maxlength="50" value="{{ old('dealer_alley', $supplier->dealer_alley) }}">
                        @error('dealer_alley')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="road">ถนน</label>
                        <input id="road" name="dealer_street" type="text" maxlength="50" value="{{ old('dealer_street', $supplier->dealer_street) }}">
                        @error('dealer_street')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="dealer_prov_id">จังหวัด</label>
                        <x-searchable-select
                            name="dealer_prov_id"
                            id="dealer_prov_id"
                            placeholder="พิมพ์ชื่อจังหวัดเพื่อค้นหา"
                            :options="$provinces->map(fn($p) => ['value' => (string)$p->id, 'label' => $p->province_name])->toArray()"
                            :selected="old('dealer_prov_id', (string)($supplier->dealer_prov_id ?? ''))"
                        />
                        @error('dealer_prov_id')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="dealer_amp_id">อำเภอ</label>
                        <x-searchable-select
                            name="dealer_amp_id"
                            id="dealer_amp_id"
                            :placeholder="$amphurs->isEmpty() ? 'เลือกจังหวัดก่อน' : 'พิมพ์ชื่ออำเภอเพื่อค้นหา'"
                            :options="$amphurs->map(fn($a) => ['value' => (string)$a->id, 'label' => $a->amphur_name])->toArray()"
                            :selected="old('dealer_amp_id', (string)($supplier->dealer_amp_id ?? ''))"
                            :disabled="$amphurs->isEmpty()"
                        />
                        @error('dealer_amp_id')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="dealer_tam_id">ตำบล</label>
                        <x-searchable-select
                            name="dealer_tam_id"
                            id="dealer_tam_id"
                            :placeholder="$tambons->isEmpty() ? 'เลือกอำเภอก่อน' : 'พิมพ์ชื่อตำบลเพื่อค้นหา'"
                            :options="$tambons->map(fn($t) => ['value' => (string)$t->id, 'label' => $t->tambon_name])->toArray()"
                            :selected="old('dealer_tam_id', (string)($supplier->dealer_tam_id ?? ''))"
                            :disabled="$tambons->isEmpty()"
                        />
                        @error('dealer_tam_id')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="postalCode">รหัสไปรษณีย์</label>
                        <input id="postalCode" name="dealer_zipcode" type="text" maxlength="5" inputmode="numeric" value="{{ old('dealer_zipcode', $supplier->dealer_zipcode) }}">
                        @error('dealer_zipcode')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="contactName">ชื่อผู้ติดต่อ</label>
                        <input id="contactName" name="dealer_contact" type="text" maxlength="150" value="{{ old('dealer_contact', $supplier->dealer_contact) }}">
                        @error('dealer_contact')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-field">
                        <label for="contactPhone">เบอร์โทรศัพท์ผู้ติดต่อ</label>
                        <input id="contactPhone" name="dealer_phone" type="text" maxlength="30" value="{{ old('dealer_phone', $supplier->dealer_phone) }}">
                        @error('dealer_phone')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="supplier-form-actions">
                    <a class="cancel-btn" href="{{ route('asset.suppliers.index') }}">ย้อนกลับ</a>
                    <button class="submit-btn" type="button" id="saveSupplierButton">บันทึก</button>
                </div>
            </form>
        </section>
    </div>

    {{-- Edit confirmation modal (yellow) --}}
    <div class="confirm-overlay" id="editSupplierOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon edit-confirm-icon">
                <svg><use href="#icon-square-pen"></use></svg>
            </div>
            <h3>ยืนยันการแก้ไขข้อมูล</h3>
            <p>
                คุณแน่ใจหรือไม่ว่าต้องการแก้ไขข้อมูลผู้ประกอบการ<br>
                การดำเนินการนี้ไม่สามารถเรียกคืนได้
            </p>
            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelEditSupplierButton">ยกเลิก</button>
                <button class="modal-confirm-btn edit-confirm-btn" type="button" id="confirmEditSupplierButton">ยืนยันการแก้ไข</button>
            </div>
        </div>
    </div>

    <script>
        window._tambonZipcodes = @json($tambons->pluck('zipcode', 'id')->toArray());
        window._locationUrls = {
            amphurs: '{{ route('api.amphurs') }}',
            tambons: '{{ route('api.tambons') }}',
        };
    </script>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-002-manage-supplier-information/script.js'])
@endsection

