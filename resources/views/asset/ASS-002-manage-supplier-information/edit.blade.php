@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-002-manage-supplier-information/style.css'])
@endsection

@section('content')
    <div class="page-container supplier-detail-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="supplier-form-card supplier-edit-card">
            <h3 class="supplier-form-heading">ข้อมูลผู้ประกอบการ</h3>

            <form class="supplier-form" action="javascript:void(0);" autocomplete="off">
                <div class="supplier-form-grid">
                    <div class="form-field">
                        <label for="editSupplierType">ประเภทผู้ประกอบการ <span class="required">*</span></label>
                        <select id="editSupplierType" required>
                            <option value="บริษัท จำกัด" {{ $supplier['supplier_type'] === 'บริษัท จำกัด' ? 'selected' : '' }}>บริษัท จำกัด</option>
                            <option value="บริษัทมหาชนจำกัด" {{ $supplier['supplier_type'] === 'บริษัทมหาชนจำกัด' ? 'selected' : '' }}>บริษัทมหาชนจำกัด</option>
                            <option value="ห้างหุ้นส่วนจำกัด" {{ $supplier['supplier_type'] === 'ห้างหุ้นส่วนจำกัด' ? 'selected' : '' }}>ห้างหุ้นส่วนจำกัด</option>
                            <option value="ร้านค้า" {{ $supplier['supplier_type'] === 'ร้านค้า' ? 'selected' : '' }}>ร้านค้า</option>
                            <option value="บุคคลธรรมดา" {{ $supplier['supplier_type'] === 'บุคคลธรรมดา' ? 'selected' : '' }}>บุคคลธรรมดา</option>
                        </select>
                    </div>

                    <div class="form-field"></div>
                    <div class="form-field"></div>

                    <div class="form-field">
                        <label for="editSupplierName">ชื่อผู้ประกอบการ <span class="required">*</span></label>
                        <input id="editSupplierName" type="text" value="{{ $supplier['supplier_name'] }}" required>
                    </div>

                    <div class="form-field">
                        <label for="editTaxId">เลขผู้เสียภาษี <span class="required">*</span></label>
                        <input id="editTaxId" type="text" value="{{ $supplier['tax_id'] }}" maxlength="13" required>
                    </div>

                    <div class="form-field"></div>

                    <div class="form-field">
                        <label for="editAddressNo">เลขที่อาคาร บ้าน หรือห้อง <span class="required">*</span></label>
                        <input id="editAddressNo" type="text" value="{{ $supplier['address_no'] }}" required>
                    </div>

                    <div class="form-field">
                        <label for="editAlley">ซอย</label>
                        <input id="editAlley" type="text" value="{{ $supplier['alley'] }}">
                    </div>

                    <div class="form-field">
                        <label for="editRoad">ถนน</label>
                        <input id="editRoad" type="text" value="{{ $supplier['road'] }}">
                    </div>

                    <div class="form-field">
                        <label for="editProvince">จังหวัด <span class="required">*</span></label>
                        <select id="editProvince" required>
                            <option value="เมืองสมุทรปราการ" selected>เมืองสมุทรปราการ</option>
                            <option value="กรุงเทพมหานคร">กรุงเทพมหานคร</option>
                            <option value="ปทุมธานี">ปทุมธานี</option>
                            <option value="นนทบุรี">นนทบุรี</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="editDistrict">อำเภอ <span class="required">*</span></label>
                        <select id="editDistrict" required>
                            <option value="เมืองสมุทรปราการ" selected>เมืองสมุทรปราการ</option>
                            <option value="คลองหลวง">คลองหลวง</option>
                            <option value="ปากเกร็ด">ปากเกร็ด</option>
                            <option value="บางนา">บางนา</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="editSubDistrict">ตำบล <span class="required">*</span></label>
                        <select id="editSubDistrict" required>
                            <option value="แพรกษา" selected>แพรกษา</option>
                            <option value="คลองหนึ่ง">คลองหนึ่ง</option>
                            <option value="บางพูด">บางพูด</option>
                            <option value="บางนาเหนือ">บางนาเหนือ</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="editPostalCode">รหัสไปรษณีย์ <span class="required">*</span></label>
                        <input id="editPostalCode" type="text" value="{{ $supplier['postal_code'] }}" maxlength="5" required>
                    </div>

                    <div class="form-field">
                        <label for="editContactName">ชื่อผู้ติดต่อ <span class="required">*</span></label>
                        <input id="editContactName" type="text" value="{{ $supplier['contact_name'] }}" required>
                    </div>

                    <div class="form-field">
                        <label for="editContactPhone">เบอร์โทรศัพท์ผู้ติดต่อ <span class="required">*</span></label>
                        <input id="editContactPhone" type="text" value="012-0123-0870" required>
                    </div>
                </div>

                <div class="supplier-form-actions detail-actions">
                    <a class="cancel-btn" href="{{ route('asset.suppliers.show', $supplier['no']) }}">ยกเลิก</a>
                    <button class="submit-btn" type="button" id="openEditSupplierPopup">
                        บันทึกการแก้ไขข้อมูล
                    </button>
                </div>
            </form>
        </section>
    </div>

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
                <button class="modal-confirm-btn edit-confirm-btn" type="button" id="confirmEditSupplierButton">
                    ยืนยันการแก้ไข
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-002-manage-supplier-information/script.js'])
@endsection
