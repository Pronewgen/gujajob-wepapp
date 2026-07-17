<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use App\Models\GlbOrganization;
use App\Models\Material;
use App\Models\MaterialProcurement;
use App\Models\MaterialProcurementList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class MaterialReceivingController extends Controller
{
    public function index(): View
    {
        $records = MaterialProcurement::query()
            ->with('organization')
            ->orderByDesc('mat_pro_date')
            ->orderByDesc('id')
            ->get();

        return view('material.MAT-002-record-material-receiving.index', [
            'pageTitle' => 'บันทึกการรับวัสดุเข้าคลัง',
            'receivingRecords' => $records,
            'methodOptions' => $this->methodOptions(),
        ]);
    }

    public function create(): View
    {
        return view('material.MAT-002-record-material-receiving.create', [
            'pageTitle'           => 'บันทึกการรับวัสดุเข้าคลัง',
            'nextReceiptNo'       => $this->nextProcurementCode(),
            'record'              => null,
            'organizationOptions' => $this->organizationOptions(),
            'dealerOptions'       => $this->dealerOptions(),
            'materials'           => $this->materials(),
            'methodOptions'       => $this->methodOptions(),
            'vatOptions'          => $this->vatOptions(),
        ]);
    }

    /**
     * Step 1: Save ONLY the MATERIAL_PROCUREMENT header row.
     * Deliberately does not touch items/MATERIAL_PROCUREMENT_LIST at all.
     */
    public function storeHeader(Request $request): RedirectResponse
    {
        try {
            $validated = $this->validateHeader($request, true);

            $procurementId = DB::connection('oracle')->transaction(function () use ($validated) {
                $procurementId = $this->nextProcurementId();

                MaterialProcurement::create([
                    'id' => $procurementId,
                    'mat_pro_code'         => $validated['mat_pro_code'],
                    'dealer_id'            => (int) $validated['dealer_id'],
                    'org_id'               => (int) $validated['org_id'],
                    'mat_pro_date' => $validated['mat_pro_date'],
                    'mat_pro_method' => isset($validated['mat_pro_method']) ? (int) $validated['mat_pro_method'] : null,
                    'mat_pro_contact_no' => $validated['mat_pro_contact_no'] ?? null,
                    'mat_pro_contact_date' => $validated['mat_pro_contact_date'] ?? null,
                    'mat_pro_quotation' => $validated['mat_pro_quotation'] ?? null,
                    'vat_type' => (int) $validated['vat_type'],
                    'vat_rate' => (int) $validated['vat_type'] === 2 ? null : ($validated['vat_rate'] ?? 7),
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                return $procurementId;
            });

            $newRecord = MaterialProcurement::query()->findOrFail($procurementId);

            return redirect()
                ->route('material.receiving.edit', $newRecord->mat_pro_code)
                ->with('success', 'บันทึกข้อมูลเอกสารเรียบร้อยแล้ว สามารถเพิ่มรายการวัสดุได้');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('MAT-002 storeHeader failed', ['error' => $exception->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถบันทึกข้อมูลเอกสารการรับวัสดุได้ กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง');
        }
    }

    public function show(string $receiptNo): View
    {
        $record = $this->findByCode($receiptNo);

        return view('material.MAT-002-record-material-receiving.show', [
            'pageTitle'     => 'บันทึกการรับวัสดุเข้าคลัง',
            'record'        => $record,
            'dealerOptions' => $this->dealerOptions(),
            'methodOptions' => $this->methodOptions(),
            'vatOptions'    => $this->vatOptions(),
        ]);
    }

    public function edit(string $receiptNo): View
    {
        $record = $this->findByCode($receiptNo);

        return view('material.MAT-002-record-material-receiving.edit', [
            'pageTitle'           => 'บันทึกการรับวัสดุเข้าคลัง',
            'record'              => $record,
            'organizationOptions' => $this->organizationOptions(),
            'dealerOptions'       => $this->dealerOptions(),
            'materials'           => $this->materials(),
            'methodOptions'       => $this->methodOptions(),
            'vatOptions'          => $this->vatOptions(),
        ]);
    }

    /**
     * Header-only update. Deliberately does not touch items/MATERIAL_PROCUREMENT_LIST.
     */
    public function update(Request $request, string $receiptNo): RedirectResponse
    {
        $record = $this->findByCode($receiptNo);

        try {
            $validated = $this->validateHeader($request, false, $record->id);

            DB::connection('oracle')->transaction(function () use ($record, $validated): void {
                $record->update([
                    'dealer_id'            => (int) $validated['dealer_id'],
                    'org_id'               => (int) $validated['org_id'],
                    'mat_pro_date' => $validated['mat_pro_date'],
                    'mat_pro_method' => isset($validated['mat_pro_method']) ? (int) $validated['mat_pro_method'] : null,
                    'mat_pro_contact_no' => $validated['mat_pro_contact_no'] ?? null,
                    'mat_pro_contact_date' => $validated['mat_pro_contact_date'] ?? null,
                    'mat_pro_quotation' => $validated['mat_pro_quotation'] ?? null,
                    'vat_type' => (int) $validated['vat_type'],
                    'vat_rate' => (int) $validated['vat_type'] === 2 ? null : ($validated['vat_rate'] ?? 7),
                    'updated_by' => 1,
                ]);
            });

            return redirect()
                ->route('material.receiving.edit', $record->mat_pro_code)
                ->with('success', 'บันทึกการแก้ไขข้อมูลเอกสารเรียบร้อยแล้ว');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('MAT-002 update failed', [
                'receipt_no' => $receiptNo,
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถแก้ไขข้อมูลเอกสารการรับวัสดุได้ กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * Step 2: Add ONE item row to MATERIAL_PROCUREMENT_LIST for an already-saved header.
     * Called via AJAX (fetch) from the edit page; responds with JSON.
     */
    public function storeItem(Request $request, string $receiptNo)
    {
        $record = $this->findByCode($receiptNo);

        try {
            $item = $this->validateItemInput($request, $record->id);

            $created = DB::connection('oracle')->transaction(function () use ($record, $item) {
                $newId = $this->nextProcurementListId();

                return MaterialProcurementList::create([
                    'id' => $newId,
                    'mat_pro_id' => $record->id,
                    'mat_id' => $item['mat_id'],
                    'mat_amt' => $item['mat_amt'],
                    'mat_price' => $item['mat_price'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
            });

            $material = Material::query()->find($item['mat_id']);

            return response()->json([
                'item' => [
                    'id' => (int) $created->id,
                    'mat_id' => (int) $created->mat_id,
                    'code' => $material?->mat_code,
                    'name' => $material?->mat_name,
                    'unit' => $material?->unit,
                    'qty' => (float) $created->mat_amt,
                    'price' => (float) $created->mat_price,
                ],
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('MAT-002 storeItem failed', [
                'receipt_no' => $receiptNo,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'ไม่สามารถเพิ่มรายการวัสดุได้ กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }

    /**
     * Delete a single item row from MATERIAL_PROCUREMENT_LIST. Called via AJAX (fetch).
     */
    public function destroyItem(string $receiptNo, string $itemId)
    {
        $record = $this->findByCode($receiptNo);

        $item = MaterialProcurementList::query()
            ->where('mat_pro_id', $record->id)
            ->where('id', (int) $itemId)
            ->firstOrFail();

        try {
            DB::connection('oracle')->transaction(function () use ($item): void {
                $item->delete();
            });

            return response()->json(['success' => true]);
        } catch (Throwable $exception) {
            Log::error('MAT-002 destroyItem failed', [
                'receipt_no' => $receiptNo,
                'item_id' => $itemId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'ไม่สามารถลบรายการวัสดุได้ กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }

    /**
     * Step: finalize/complete the document. This is the ONLY place that requires
     * at least one item to exist in MATERIAL_PROCUREMENT_LIST.
     */
    public function finalize(string $receiptNo): RedirectResponse
    {
        $record = $this->findByCode($receiptNo);

        $itemCount = MaterialProcurementList::query()
            ->where('mat_pro_id', $record->id)
            ->count();

        if ($itemCount === 0) {
            return back()->with('finalize_error', 'กรุณาเพิ่มรายการวัสดุอย่างน้อย 1 รายการ');
        }

        return redirect()
            ->route('material.receiving.index')
            ->with('success', 'บันทึกข้อมูลการรับวัสดุเรียบร้อยแล้ว');
    }

    private function findByCode(string $receiptNo): MaterialProcurement
    {
        return MaterialProcurement::query()
            ->with(['organization', 'details.material'])
            ->where('mat_pro_code', $receiptNo)
            ->firstOrFail();
    }

    private function validateHeader(Request $request, bool $isCreate, ?int $currentId = null): array
    {
        $rules = [
            'mat_pro_code'        => ['required', 'string', 'max:50'],
            'mat_pro_date'        => ['required', 'date'],
            'org_id'              => ['required', 'integer'],
            'dealer_id'           => ['required', 'integer', 'min:1'],
            'mat_pro_method'      => ['nullable', 'integer'],
            'mat_pro_contact_no'  => ['nullable', 'string', 'max:100'],
            'mat_pro_contact_date'=> ['nullable', 'date'],
            'mat_pro_quotation'   => ['nullable', 'string', 'max:100'],
            'vat_type'            => ['required', 'integer'],
            'vat_rate'            => ['nullable', 'numeric', 'min:0'],
        ];

        $validated = $request->validate($rules);

        $existsOrg = GlbOrganization::query()
            ->where('org_id', (int) $validated['org_id'])
            ->exists();

        if (! $existsOrg) {
            throw ValidationException::withMessages([
                'org_id' => 'ไม่พบหน่วยงานที่เลือก',
            ]);
        }

        if (! array_key_exists((int) ($validated['vat_type'] ?? 0), $this->vatOptions())) {
            throw ValidationException::withMessages([
                'vat_type' => 'รูปแบบ VAT ไม่ถูกต้อง',
            ]);
        }

        if (isset($validated['mat_pro_method']) && $validated['mat_pro_method'] !== null) {
            if (! array_key_exists((int) $validated['mat_pro_method'], $this->methodOptions())) {
                throw ValidationException::withMessages([
                    'mat_pro_method' => 'วิธีการจัดซื้อจัดจ้างไม่ถูกต้อง',
                ]);
            }
        }

        $duplicateCode = MaterialProcurement::query()
            ->where('mat_pro_code', $validated['mat_pro_code'])
            ->when(! $isCreate && $currentId !== null, function ($query) use ($currentId) {
                $query->where('id', '!=', $currentId);
            })
            ->exists();

        if ($duplicateCode) {
            throw ValidationException::withMessages([
                'mat_pro_code' => 'เลขที่ใบรับวัสดุนี้ถูกใช้งานแล้ว',
            ]);
        }

        return $validated;
    }

    /**
     * Validates ONE item submitted via storeItem(). Never invoked from
     * storeHeader()/update(), so the header can always be saved with zero items.
     */
    private function validateItemInput(Request $request, int $procurementId): array
    {
        $validated = $request->validate([
            'material_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ], [], [
            'material_id' => 'วัสดุ',
            'quantity' => 'จำนวน',
            'unit_price' => 'ราคา/หน่วย',
        ]);

        $matId = (int) $validated['material_id'];

        $materialExists = Material::query()->where('id', $matId)->exists();

        if (! $materialExists) {
            throw ValidationException::withMessages([
                'material_id' => 'ไม่พบวัสดุที่เลือกในระบบ',
            ]);
        }

        $duplicateExists = MaterialProcurementList::query()
            ->where('mat_pro_id', $procurementId)
            ->where('mat_id', $matId)
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'material_id' => 'วัสดุรายการนี้ถูกเพิ่มแล้ว กรุณาแก้ไขจำนวนในรายการเดิม',
            ]);
        }

        return [
            'mat_id' => $matId,
            'mat_amt' => (float) $validated['quantity'],
            'mat_price' => (float) $validated['unit_price'],
        ];
    }

    private function nextProcurementId(): int
    {
        return (int) MaterialProcurement::query()->max('id') + 1;
    }

    private function nextProcurementListId(): int
    {
        return (int) MaterialProcurementList::query()->max('id') + 1;
    }

    private function nextProcurementCode(): string
    {
        $codes = MaterialProcurement::query()->pluck('mat_pro_code')->all();
        $maxRunning = 0;

        foreach ($codes as $code) {
            if (! is_string($code)) {
                continue;
            }

            if (preg_match('/(\d+)$/', $code, $matches) === 1) {
                $maxRunning = max($maxRunning, (int) $matches[1]);
            }
        }

        return 'MPR-' . str_pad((string) ($maxRunning + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Formats all GLB_ORGANIZATION rows as [{value, label}] for the searchable-select
     * component. No limit — all 2,157 records are loaded for client-side filtering.
     */
    private function organizationOptions(): array
    {
        return $this->organizations()->map(function ($org) {
            return [
                'value'      => $org->org_id,
                'label'      => $org->org_code . ' - ' . $org->org_name,
                'searchText' => $org->org_code . ' ' . $org->org_name,
            ];
        })->values()->all();
    }

    /**
     * TEMPORARY MOCK DATA — replace when DEALER table has real rows.
     * To switch to live data, swap the return statement with the commented query.
     *
     * @return array<int,array{value:int,label:string}>
     */
    private function dealerOptions(): array
    {
        // TODO: replace with live query once DEALER is populated:
        // return Dealer::query()->orderBy('dealer_name')
        //     ->get(['id', 'dealer_name'])
        //     ->map(fn($d) => ['value' => $d->id, 'label' => $d->dealer_name])
        //     ->values()->all();

        return [
            ['value' => 1, 'label' => 'บริษัท ออฟฟิศพลัส (ประเทศไทย) จำกัด',  'searchText' => 'ออฟฟิศพลัส ประเทศไทย'],
            ['value' => 2, 'label' => 'บริษัท สยามซัพพลาย แอนด์ เซอร์วิส จำกัด', 'searchText' => 'สยามซัพพลาย'],
            ['value' => 3, 'label' => 'บริษัท ไทยไอทีโซลูชัน จำกัด',              'searchText' => 'ไทยไอทีโชลูชัน'],
            ['value' => 4, 'label' => 'บริษัท พรีเมียมอุปกรณ์สำนักงาน จำกัด', 'searchText' => 'พรีเมียมอุปกรณ์สำนักงาน'],
            ['value' => 5, 'label' => 'ห้างหุ้นส่วนจำกัด เจริญพาณิชย์',              'searchText' => 'เจริญพาณิชย์ ห้างหุ้นส่วน'],
        ];
    }

    private function organizations(?int $limit = null)
    {
        $query = GlbOrganization::query()
            ->orderBy('org_name')
            ->select(['org_id', 'org_code', 'org_name']);

        if ($limit !== null) {
            $query->limit($limit);
        }

        $organizations = $query->get();

        if ($organizations->isNotEmpty()) {
            return $organizations;
        }

        // TEMPORARY MOCK DATA - remove when organization data is available
        return collect([
            (object) ['org_id' => 0, 'org_code' => '0', 'org_name' => 'กรมส่งเสริมสหกรณ์'],
            (object) ['org_id' => 1, 'org_code' => '1', 'org_name' => 'สำนักบริหารกลาง'],
            (object) ['org_id' => 2, 'org_code' => '2', 'org_name' => 'กองคลังพัสดุ'],
            (object) ['org_id' => 3, 'org_code' => '3', 'org_name' => 'ศูนย์เทคโนโลยีสารสนเทศ'],
            (object) ['org_id' => 4, 'org_code' => '4', 'org_name' => 'กลุ่มงานบริหารทรัพยากรบุคคล'],
        ]);
    }

    private function materials()
    {
        return Material::query()
            ->orderBy('mat_code')
            ->get(['id', 'mat_code', 'mat_name', 'unit']);
    }

    private function methodOptions(): array
    {
        return [
            1 => 'เฉพาะเจาะจง',
            2 => 'ประกวดราคา',
            3 => 'คัดเลือก',
        ];
    }

    private function vatOptions(): array
    {
        return [
            1 => 'รวม VAT',
            2 => 'ไม่รวม VAT',
        ];
    }
}
