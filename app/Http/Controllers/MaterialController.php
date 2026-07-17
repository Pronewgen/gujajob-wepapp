<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $searchBy = $request->string('search_by', 'name')->value();
        $keyword = trim($request->string('keyword')->value());

        $query = Material::query()->orderBy('id');

        if ($keyword !== '') {
            if ($searchBy === 'code') {
                $query->whereRaw('UPPER(mat_code) LIKE ?', ['%' . mb_strtoupper($keyword) . '%']);
            } else {
                $query->whereRaw('UPPER(mat_name) LIKE ?', ['%' . mb_strtoupper($keyword) . '%']);
            }
        }

        return view('material.MAT-001-manage-material-items.index', [
            'pageTitle' => 'จัดการรายการวัสดุ',
            'materials' => $query->get(),
            'searchBy' => $searchBy,
            'keyword' => $keyword,
        ]);
    }

    public function create(): View
    {
        return view('material.MAT-001-manage-material-items.create', [
            'pageTitle' => 'จัดการรายการวัสดุ',
            'nextMaterialCode' => $this->nextMaterialCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'MAT_NAME' => ['required', 'string', 'max:200'],
            'MAT_DESC' => ['nullable', 'string', 'max:1000'],
            'UNIT' => ['required', 'string', 'max:20'],
            'MIN_AMT' => ['nullable', 'integer', 'min:0'],
            'MAX_AMT' => ['nullable', 'integer', 'min:0', 'gte:MIN_AMT'],
        ]);

        try {
            DB::connection('oracle')->transaction(function () use ($validated): void {
                Material::create([
                    'id' => $this->nextMaterialId(),
                    'mat_code' => $this->nextMaterialCode(),
                    'mat_name' => $validated['MAT_NAME'],
                    'mat_desc' => $validated['MAT_DESC'] ?? null,
                    'unit' => $validated['UNIT'],
                    'min_amt' => $validated['MIN_AMT'] ?? 0,
                    'max_amt' => $validated['MAX_AMT'] ?? 0,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
            });

            return redirect()
                ->route('material.items.index')
                ->with('success', 'บันทึกรายการวัสดุเรียบร้อยแล้ว');
        } catch (Throwable $exception) {
            Log::error('MAT-001 store failed', ['error' => $exception->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถบันทึกข้อมูลวัสดุได้ กรุณาตรวจสอบการเชื่อมต่อ Oracle และลองใหม่อีกครั้ง');
        }
    }

    public function show(string $code): View
    {
        return view('material.MAT-001-manage-material-items.show', [
            'pageTitle' => 'จัดการรายการวัสดุ',
            'material' => $this->findByCode($code),
        ]);
    }

    public function edit(string $code): View
    {
        return view('material.MAT-001-manage-material-items.edit', [
            'pageTitle' => 'จัดการรายการวัสดุ',
            'material' => $this->findByCode($code),
        ]);
    }

    public function update(Request $request, string $code): RedirectResponse
    {
        $validated = $request->validate([
            'MAT_NAME' => ['required', 'string', 'max:200'],
            'MAT_DESC' => ['nullable', 'string', 'max:1000'],
            'UNIT' => ['required', 'string', 'max:20'],
            'MIN_AMT' => ['nullable', 'integer', 'min:0'],
            'MAX_AMT' => ['nullable', 'integer', 'min:0', 'gte:MIN_AMT'],
        ]);

        try {
            DB::connection('oracle')->transaction(function () use ($validated, $code): void {
                $material = $this->findByCode($code);

                $material->update([
                    'mat_name' => $validated['MAT_NAME'],
                    'mat_desc' => $validated['MAT_DESC'] ?? null,
                    'unit' => $validated['UNIT'],
                    'min_amt' => $validated['MIN_AMT'] ?? 0,
                    'max_amt' => $validated['MAX_AMT'] ?? 0,
                    'updated_by' => 1,
                ]);
            });

            return redirect()
                ->route('material.items.show', $code)
                ->with('success', 'แก้ไขรายการวัสดุเรียบร้อยแล้ว');
        } catch (Throwable $exception) {
            Log::error('MAT-001 update failed', ['code' => $code, 'error' => $exception->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถแก้ไขข้อมูลวัสดุได้ กรุณาลองใหม่อีกครั้ง');
        }
    }

    public function destroy(string $code): RedirectResponse
    {
        try {
            DB::connection('oracle')->transaction(function () use ($code): void {
                $material = $this->findByCode($code);
                $material->delete();
            });

            return redirect()
                ->route('material.items.index')
                ->with('success', 'ลบรายการวัสดุเรียบร้อยแล้ว');
        } catch (Throwable $exception) {
            Log::error('MAT-001 delete failed', ['code' => $code, 'error' => $exception->getMessage()]);

            return redirect()
                ->route('material.items.show', $code)
                ->with('error', 'ไม่สามารถลบรายการวัสดุได้ กรุณาลองใหม่อีกครั้ง');
        }
    }

    private function findByCode(string $code): Material
    {
        return Material::query()->where('mat_code', $code)->firstOrFail();
    }

    private function nextMaterialId(): int
    {
        return (int) Material::query()->max('id') + 1;
    }

    private function nextMaterialCode(): string
    {
        $codes = Material::query()->pluck('mat_code')->all();
        $max = 1000;

        foreach ($codes as $code) {
            if (preg_match('/^MAT-(\d+)$/', (string) $code, $matches) === 1) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'MAT-' . ($max + 1);
    }
}
