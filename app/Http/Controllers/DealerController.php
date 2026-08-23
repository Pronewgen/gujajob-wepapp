<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use App\Models\GlbAmphur;
use App\Models\GlbProvince;
use App\Models\GlbTambon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class DealerController extends Controller
{
    private const SORT_MAP = [
        'name'    => 'dealer_name',
        'type'    => 'dealer_type',
        'contact' => 'dealer_contact',
        'phone'   => 'dealer_phone',
        'address' => 'dealer_addr_no',
    ];

    private const SEARCH_COLUMNS = [
        'all'     => ['dealer_name', 'dealer_type', 'dealer_tax_id', 'dealer_contact', 'dealer_phone'],
        'name'    => ['dealer_name'],
        'type'    => ['dealer_type'],
        'tax_id'  => ['dealer_tax_id'],
        'contact' => ['dealer_contact'],
        'phone'   => ['dealer_phone'],
    ];

    public function index(Request $request): View
    {
        $searchBy  = $request->input('search_by', 'all');
        $keyword   = trim($request->input('keyword', ''));
        $sort      = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $orderBy = self::SORT_MAP[$sort] ?? 'dealer_name';

        $query = Dealer::query()->orderBy($orderBy, $direction);

        if ($keyword !== '') {
            if ($searchBy === 'type') {
                // dealer_type stores code ('1'–'5'); translate label search to code match
                $matchingCodes = array_keys(array_filter(
                    Dealer::DEALER_TYPES,
                    fn(string $label) => mb_stripos($label, $keyword) !== false
                ));
                if (empty($matchingCodes)) {
                    $query->whereRaw('1=0');
                } else {
                    $query->whereIn('dealer_type', $matchingCodes);
                }
            } else {
                // For 'all', also match type labels in addition to text columns
                $typeCodes = $searchBy === 'all'
                    ? array_keys(array_filter(
                        Dealer::DEALER_TYPES,
                        fn(string $label) => mb_stripos($label, $keyword) !== false
                    ))
                    : [];

                // Text columns, excluding dealer_type (handled via code matching)
                $textCols = array_diff(
                    self::SEARCH_COLUMNS[$searchBy] ?? self::SEARCH_COLUMNS['all'],
                    ['dealer_type']
                );

                $query->where(function ($q) use ($textCols, $keyword, $typeCodes): void {
                    $isFirst = true;
                    foreach ($textCols as $col) {
                        if ($isFirst) {
                            $q->whereRaw("UPPER({$col}) LIKE UPPER(?)", ['%' . $keyword . '%']);
                            $isFirst = false;
                        } else {
                            $q->orWhereRaw("UPPER({$col}) LIKE UPPER(?)", ['%' . $keyword . '%']);
                        }
                    }
                    if (!empty($typeCodes)) {
                        $isFirst ? $q->whereIn('dealer_type', $typeCodes) : $q->orWhereIn('dealer_type', $typeCodes);
                    }
                });
            }
        }

        $suppliers = $query->paginate(10)->withQueryString();

        return view('asset.ASS-002-manage-supplier-information.index', [
            'pageTitle' => 'จัดการข้อมูลผู้ประกอบการ',
            'suppliers' => $suppliers,
            'searchBy'  => $searchBy,
            'keyword'   => $keyword,
            'sort'      => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        $provinces = GlbProvince::query()->orderBy('province_name')->get(['id', 'province_name']);
        $oldProvId = session()->getOldInput('dealer_prov_id');
        $oldAmpId  = session()->getOldInput('dealer_amp_id');
        $amphurs   = $oldProvId
            ? GlbAmphur::where('province_id', (int) $oldProvId)->orderBy('amphur_name')->get(['id', 'amphur_name'])
            : collect();
        $tambons   = $oldAmpId
            ? GlbTambon::where('amphur_id', (int) $oldAmpId)->orderBy('tambon_name')->get(['id', 'tambon_name', 'zipcode'])
            : collect();

        return view('asset.ASS-002-manage-supplier-information.create', [
            'pageTitle'   => 'จัดการข้อมูลผู้ประกอบการ',
            'provinces'   => $provinces,
            'amphurs'     => $amphurs,
            'tambons'     => $tambons,
            'dealerTypes' => Dealer::DEALER_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dealer_type'    => ['required', 'string', 'max:1'],
            'dealer_name'    => ['required', 'string', 'max:200'],
            'dealer_tax_id'  => ['nullable', 'string', 'max:20'],
            'dealer_addr_no' => ['nullable', 'string', 'max:20'],
            'dealer_alley'   => ['nullable', 'string', 'max:50'],
            'dealer_street'  => ['nullable', 'string', 'max:50'],
            'dealer_prov_id' => ['nullable', 'integer'],
            'dealer_amp_id'  => ['nullable', 'integer'],
            'dealer_tam_id'  => ['nullable', 'integer'],
            'dealer_zipcode' => ['nullable', 'string', 'max:5'],
            'dealer_contact' => ['nullable', 'string', 'max:150'],
            'dealer_phone'   => ['nullable', 'string', 'max:30'],
        ], [
            'dealer_type.required' => 'กรุณาเลือกประเภทผู้ประกอบการ',
            'dealer_name.required' => 'กรุณากรอกชื่อผู้ประกอบการ',
        ]);

        // Validate location FK relationships
        if (!empty($validated['dealer_prov_id'])) {
            if (!GlbProvince::find((int) $validated['dealer_prov_id'])) {
                return back()->withInput()->withErrors(['dealer_prov_id' => 'จังหวัดที่เลือกไม่ถูกต้อง']);
            }
        }
        if (!empty($validated['dealer_amp_id'])) {
            $amp = GlbAmphur::find((int) $validated['dealer_amp_id']);
            if (!$amp || (string) $amp->province_id !== (string) $validated['dealer_prov_id']) {
                return back()->withInput()->withErrors(['dealer_amp_id' => 'อำเภอต้องอยู่ในจังหวัดที่เลือก']);
            }
        }
        if (!empty($validated['dealer_tam_id'])) {
            $tam = GlbTambon::find((int) $validated['dealer_tam_id']);
            if (!$tam || (string) $tam->amphur_id !== (string) $validated['dealer_amp_id']) {
                return back()->withInput()->withErrors(['dealer_tam_id' => 'ตำบลต้องอยู่ในอำเภอที่เลือก']);
            }
        }

        try {
            DB::connection('oracle')->transaction(function () use ($validated): void {
                $nextId = (int) DB::connection('oracle')
                    ->selectOne('SELECT DEALER_SEQ.NEXTVAL AS next_id FROM DUAL')
                    ->next_id;

                Dealer::create([
                    'id'             => $nextId,
                    'dealer_type'    => $validated['dealer_type'],
                    'dealer_name'    => $validated['dealer_name'],
                    'dealer_tax_id'  => $validated['dealer_tax_id'] ?? null,
                    'dealer_addr_no' => $validated['dealer_addr_no'] ?? null,
                    'dealer_alley'   => $validated['dealer_alley'] ?? null,
                    'dealer_street'  => $validated['dealer_street'] ?? null,
                    'dealer_prov_id' => $validated['dealer_prov_id'] ? (int) $validated['dealer_prov_id'] : null,
                    'dealer_amp_id'  => $validated['dealer_amp_id'] ? (int) $validated['dealer_amp_id'] : null,
                    'dealer_tam_id'  => $validated['dealer_tam_id'] ? (int) $validated['dealer_tam_id'] : null,
                    'dealer_zipcode' => $validated['dealer_zipcode'] ?? null,
                    'dealer_contact' => $validated['dealer_contact'] ?? null,
                    'dealer_phone'   => $validated['dealer_phone'] ?? null,
                    'created_by'     => 0,
                    'updated_by'     => 0,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('DealerController::store failed', ['error' => $e->getMessage()]);
            return back()->withInput()->withErrors(['_error' => 'บันทึกไม่สำเร็จ กรุณาลองใหม่']);
        }

        return redirect()->route('asset.suppliers.index')
            ->with('supplier_success', 'บันทึกข้อมูลผู้ประกอบการเรียบร้อยแล้ว');
    }

    public function show(int $id): View
    {
        $supplier = Dealer::with(['province', 'amphur', 'tambon'])->findOrFail($id);

        return view('asset.ASS-002-manage-supplier-information.show', [
            'pageTitle' => 'จัดการข้อมูลผู้ประกอบการ',
            'supplier'  => $supplier,
        ]);
    }

    public function edit(int $id): View
    {
        $supplier  = Dealer::with(['province', 'amphur', 'tambon'])->findOrFail($id);
        $provinces = GlbProvince::query()->orderBy('province_name')->get(['id', 'province_name']);
        $amphurs   = $supplier->dealer_prov_id
            ? GlbAmphur::where('province_id', $supplier->dealer_prov_id)->orderBy('amphur_name')->get(['id', 'amphur_name'])
            : collect();
        $tambons   = $supplier->dealer_amp_id
            ? GlbTambon::where('amphur_id', $supplier->dealer_amp_id)->orderBy('tambon_name')->get(['id', 'tambon_name', 'zipcode'])
            : collect();

        return view('asset.ASS-002-manage-supplier-information.edit', [
            'pageTitle'   => 'จัดการข้อมูลผู้ประกอบการ',
            'supplier'    => $supplier,
            'provinces'   => $provinces,
            'amphurs'     => $amphurs,
            'tambons'     => $tambons,
            'dealerTypes' => Dealer::DEALER_TYPES,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $supplier = Dealer::findOrFail($id);

        $validated = $request->validate([
            'dealer_type'    => ['required', 'string', 'max:1'],
            'dealer_name'    => ['required', 'string', 'max:200'],
            'dealer_tax_id'  => ['nullable', 'string', 'max:20'],
            'dealer_addr_no' => ['nullable', 'string', 'max:20'],
            'dealer_alley'   => ['nullable', 'string', 'max:50'],
            'dealer_street'  => ['nullable', 'string', 'max:50'],
            'dealer_prov_id' => ['nullable', 'integer'],
            'dealer_amp_id'  => ['nullable', 'integer'],
            'dealer_tam_id'  => ['nullable', 'integer'],
            'dealer_zipcode' => ['nullable', 'string', 'max:5'],
            'dealer_contact' => ['nullable', 'string', 'max:150'],
            'dealer_phone'   => ['nullable', 'string', 'max:30'],
        ], [
            'dealer_type.required' => 'กรุณาเลือกประเภทผู้ประกอบการ',
            'dealer_name.required' => 'กรุณากรอกชื่อผู้ประกอบการ',
        ]);

        // Validate location FK relationships
        if (!empty($validated['dealer_prov_id'])) {
            if (!GlbProvince::find((int) $validated['dealer_prov_id'])) {
                return back()->withInput()->withErrors(['dealer_prov_id' => 'จังหวัดที่เลือกไม่ถูกต้อง']);
            }
        }
        if (!empty($validated['dealer_amp_id'])) {
            $amp = GlbAmphur::find((int) $validated['dealer_amp_id']);
            if (!$amp || (string) $amp->province_id !== (string) $validated['dealer_prov_id']) {
                return back()->withInput()->withErrors(['dealer_amp_id' => 'อำเภอต้องอยู่ในจังหวัดที่เลือก']);
            }
        }
        if (!empty($validated['dealer_tam_id'])) {
            $tam = GlbTambon::find((int) $validated['dealer_tam_id']);
            if (!$tam || (string) $tam->amphur_id !== (string) $validated['dealer_amp_id']) {
                return back()->withInput()->withErrors(['dealer_tam_id' => 'ตำบลต้องอยู่ในอำเภอที่เลือก']);
            }
        }

        try {
            $supplier->update([
                'dealer_type'    => $validated['dealer_type'],
                'dealer_name'    => $validated['dealer_name'],
                'dealer_tax_id'  => $validated['dealer_tax_id'] ?? null,
                'dealer_addr_no' => $validated['dealer_addr_no'] ?? null,
                'dealer_alley'   => $validated['dealer_alley'] ?? null,
                'dealer_street'  => $validated['dealer_street'] ?? null,
                'dealer_prov_id' => $validated['dealer_prov_id'] ? (int) $validated['dealer_prov_id'] : null,
                'dealer_amp_id'  => $validated['dealer_amp_id'] ? (int) $validated['dealer_amp_id'] : null,
                'dealer_tam_id'  => $validated['dealer_tam_id'] ? (int) $validated['dealer_tam_id'] : null,
                'dealer_zipcode' => $validated['dealer_zipcode'] ?? null,
                'dealer_contact' => $validated['dealer_contact'] ?? null,
                'dealer_phone'   => $validated['dealer_phone'] ?? null,
                'updated_by'     => 0,
            ]);
        } catch (Throwable $e) {
            Log::error('DealerController::update failed', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->withErrors(['_error' => 'บันทึกการแก้ไขไม่สำเร็จ กรุณาลองใหม่']);
        }

        return redirect()->route('asset.suppliers.index')
            ->with('supplier_success', 'แก้ไขข้อมูลผู้ประกอบการเรียบร้อยแล้ว');
    }

    public function destroy(int $id): RedirectResponse
    {
        $supplier = Dealer::findOrFail($id);

        // Check references before deleting
        $assetCount       = DB::connection('oracle')->selectOne('SELECT COUNT(*) AS cnt FROM ASSET WHERE DEALER_ID = ?', [$id])->cnt ?? 0;
        $procurementCount = DB::connection('oracle')->selectOne('SELECT COUNT(*) AS cnt FROM MATERIAL_PROCUREMENT WHERE DEALER_ID = ?', [$id])->cnt ?? 0;

        if ($assetCount > 0 || $procurementCount > 0) {
            return redirect()->route('asset.suppliers.index')
                ->with('supplier_error', "ไม่สามารถลบผู้ประกอบการ \"{$supplier->dealer_name}\" ได้ เนื่องจากมีข้อมูลอ้างอิงอยู่ในระบบ");
        }

        try {
            $supplier->delete();
        } catch (Throwable $e) {
            Log::error('DealerController::destroy failed', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('asset.suppliers.index')
                ->with('supplier_error', 'ลบข้อมูลไม่สำเร็จ กรุณาลองใหม่');
        }

        return redirect()->route('asset.suppliers.index')
            ->with('supplier_success', "ลบข้อมูลผู้ประกอบการ \"{$supplier->dealer_name}\" เรียบร้อยแล้ว");
    }

    /** AJAX: load amphurs by province. */
    public function amphursByProvince(Request $request): JsonResponse
    {
        $provinceId = (int) $request->input('province_id', 0);
        $amphurs    = GlbAmphur::where('province_id', $provinceId)
            ->orderBy('amphur_name')
            ->get(['id', 'amphur_name']);

        return response()->json($amphurs->map(fn ($a) => ['id' => $a->id, 'name' => $a->amphur_name]));
    }

    /** AJAX: load tambons by amphur. */
    public function tambonsByAmphur(Request $request): JsonResponse
    {
        $amphurId = (int) $request->input('amphur_id', 0);
        $tambons  = GlbTambon::where('amphur_id', $amphurId)
            ->orderBy('tambon_name')
            ->get(['id', 'tambon_name', 'zipcode']);

        return response()->json($tambons->map(fn ($t) => ['id' => $t->id, 'name' => $t->tambon_name, 'zipcode' => $t->zipcode]));
    }
}

