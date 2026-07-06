@extends('layouts.app')

@section('page-style')
    @vite(['resources/css/asset/ASS-002-manage-supplier-information/style.css'])
@endsection

@section('content')
    <div class="page-container supplier-create-page">
        <header class="page-header">
            <div class="page-title-box">
                <h2>{{ $pageTitle }}</h2>
                <div class="header-line"></div>
            </div>
        </header>

        <section class="supplier-form-card">
            <h3 class="supplier-form-heading">เพิ่มผู้ประกอบการใหม่</h3>

            <form class="supplier-form" action="javascript:void(0);" autocomplete="off">
                <div class="supplier-form-grid">
                    <div class="form-field supplier-type-field">
                        <label for="supplierType">ประเภทผู้ประกอบการ <span class="required">*</span></label>
                        <select id="supplierType" required>
                            <option value="บริษัท จำกัด" selected>บริษัท จำกัด</option>
                            <option value="บริษัทมหาชนจำกัด">บริษัทมหาชนจำกัด</option>
                            <option value="ห้างหุ้นส่วนจำกัด">ห้างหุ้นส่วนจำกัด</option>
                            <option value="ร้านค้า">ร้านค้า</option>
                            <option value="บุคคลธรรมดา">บุคคลธรรมดา</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="supplierName">ชื่อผู้ประกอบการ <span class="required">*</span></label>
                        <input
                            id="supplierName"
                            type="text"
                            placeholder="ชื่อบริษัท/ร้านค้า"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="taxId">เลขผู้เสียภาษี <span class="required">*</span></label>
                        <input
                            id="taxId"
                            type="text"
                            placeholder="ระบุเลข 13 หลัก"
                            maxlength="13"
                            inputmode="numeric"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="addressNo">เลขที่อาคาร บ้าน หรือห้อง <span class="required">*</span></label>
                        <input
                            id="addressNo"
                            type="text"
                            placeholder="เช่น 24/8"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="alley">ซอย</label>
                        <input
                            id="alley"
                            type="text"
                            placeholder="ระบุซอย"
                        >
                    </div>

                    <div class="form-field">
                        <label for="road">ถนน</label>
                        <input
                            id="road"
                            type="text"
                            placeholder="ระบุถนน"
                        >
                    </div>

                    <div class="form-field">
                        <label for="province">จังหวัด <span class="required">*</span></label>
                        <select id="province" required>
                            <option value="" selected disabled>-- เลือกจังหวัด --</option>
                            <option value="กรุงเทพมหานคร">กรุงเทพมหานคร</option>
                            <option value="ปทุมธานี">ปทุมธานี</option>
                            <option value="นนทบุรี">นนทบุรี</option>
                            <option value="สมุทรปราการ">สมุทรปราการ</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="district">อำเภอ <span class="required">*</span></label>
                        <select id="district" required>
                            <option value="" selected disabled>-- เลือกอำเภอ --</option>
                            <option value="เมืองสมุทรปราการ">เมืองสมุทรปราการ</option>
                            <option value="คลองหลวง">คลองหลวง</option>
                            <option value="ปากเกร็ด">ปากเกร็ด</option>
                            <option value="บางนา">บางนา</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="subDistrict">ตำบล <span class="required">*</span></label>
                        <select id="subDistrict" required>
                            <option value="" selected disabled>-- เลือกตำบล --</option>
                            <option value="แพรกษา">แพรกษา</option>
                            <option value="คลองหนึ่ง">คลองหนึ่ง</option>
                            <option value="บางพูด">บางพูด</option>
                            <option value="บางนาเหนือ">บางนาเหนือ</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="postalCode">รหัสไปรษณีย์ <span class="required">*</span></label>
                        <input
                            id="postalCode"
                            type="text"
                            placeholder="ระบุรหัสไปรษณีย์"
                            maxlength="5"
                            inputmode="numeric"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="contactName">ชื่อผู้ติดต่อ <span class="required">*</span></label>
                        <input
                            id="contactName"
                            type="text"
                            placeholder="ระบุชื่อผู้ติดต่อ"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="contactPhone">เบอร์โทรศัพท์ผู้ติดต่อ <span class="required">*</span></label>
                        <input
                            id="contactPhone"
                            type="text"
                            placeholder="012-345-6789"
                            required
                        >
                    </div>
                </div>

                <div class="supplier-form-actions">
                    <a class="cancel-btn" href="{{ route('asset.suppliers.index') }}">ยกเลิก</a>
                    <button class="submit-btn" type="button" id="saveSupplierButton">
                        บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </section>
    </div>

    <div class="confirm-overlay" id="saveSupplierOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true">
            <div class="confirm-icon success-confirm-icon">
                <svg><use href="#icon-success"></use></svg>
            </div>

            <h3>ยืนยันการบันทึกข้อมูล</h3>

            <p>
                คุณแน่ใจหรือไม่ว่าต้องการข้อมูลผู้ประกอบการนี้
            </p>

            <div class="confirm-actions">
                <button class="modal-cancel-btn" type="button" id="cancelSaveSupplierButton">ยกเลิก</button>
                <button class="modal-confirm-btn success-confirm-btn" type="button" id="confirmSaveSupplierButton">
                    ยืนยันการบันทึก
                </button>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/asset/ASS-002-manage-supplier-information/script.js'])
@endsection
