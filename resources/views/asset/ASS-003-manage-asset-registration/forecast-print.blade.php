<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานพยากรณ์งบประมาณจัดซื้อครุภัณฑ์ทดแทน</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: "Noto Sans Thai", Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #111827;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .report-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 18mm 18mm 14mm;
        }

        /* ── Header ── */
        .report-title {
            text-align: center;
            font-size: 17px;
            font-weight: 800;
            color: #173b8f;
            margin-bottom: 4px;
        }

        .report-subtitle {
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 18px;
        }

        .report-divider {
            border: none;
            border-top: 2px solid #173b8f;
            margin-bottom: 14px;
        }

        /* ── Meta block ── */
        .report-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 24px;
            margin-bottom: 18px;
            font-size: 12px;
        }

        .meta-row {
            display: flex;
            gap: 6px;
        }

        .meta-label {
            color: #6b7280;
            font-weight: 700;
            white-space: nowrap;
            min-width: 120px;
        }

        .meta-value {
            color: #111827;
            font-weight: 600;
        }

        /* ── Summary boxes ── */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .summary-box {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px 12px;
            text-align: center;
        }

        .summary-box .label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            display: block;
            margin-bottom: 4px;
        }

        .summary-box .value {
            font-size: 18px;
            font-weight: 900;
            color: #173b8f;
            line-height: 1;
        }

        .summary-box.highlight .value {
            color: #6d28d9;
        }

        /* ── Section title ── */
        .section-title {
            font-size: 13px;
            font-weight: 800;
            color: #173b8f;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #dfe7f0;
        }

        /* ── Tables ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th {
            background: #173b8f;
            color: #ffffff;
            padding: 8px 8px;
            font-size: 11px;
            font-weight: 700;
            text-align: left;
        }

        td {
            padding: 8px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        tr:last-child td { border-bottom: none; }

        .num-cell { text-align: right; }
        .center-cell { text-align: center; }

        .ai-cost { color: #6d28d9; font-weight: 800; }

        /* ── Remark ── */
        .report-remark {
            margin-top: 24px;
            padding: 10px 14px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 10px;
            color: #6b7280;
            line-height: 1.6;
        }

        .empty-msg {
            text-align: center;
            padding: 24px;
            color: #9ca3af;
            font-size: 12px;
        }

        /* ── Print ── */
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .report-page { padding: 10mm 14mm; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }

        /* ── Screen only ── */
        @media screen {
            body { background: #f3f4f6; }
            .report-page {
                margin: 20px auto;
                box-shadow: 0 4px 24px rgba(0,0,0,0.12);
                background: #ffffff;
            }
            .print-btn-bar {
                text-align: center;
                padding: 16px 0 0;
                margin-bottom: 10px;
            }
            .print-btn {
                padding: 8px 24px;
                background: #173b8f;
                color: #ffffff;
                border: none;
                border-radius: 4px;
                font-family: inherit;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
            }
            .print-btn:hover { background: #102d70; }
        }
    </style>
</head>
<body>

<div class="no-print print-btn-bar">
    <button class="print-btn" onclick="window.print()">พิมพ์รายงาน</button>
</div>

<div class="report-page">

    {{-- ── Title ── --}}
    <div class="report-title">รายงานพยากรณ์งบประมาณจัดซื้อครุภัณฑ์ทดแทน</div>
    <div class="report-subtitle">ผลการพยากรณ์โดย AI (Ridge Regression) · ใช้สำหรับประกอบการวางแผนงบประมาณ</div>
    <hr class="report-divider">

    {{-- ── Meta ── --}}
    <div class="report-meta">
        <div class="meta-row">
            <span class="meta-label">วันที่ออกรายงาน</span>
            <span class="meta-value">{{ $printDate }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">ระยะเวลาพยากรณ์</span>
            <span class="meta-value">{{ $params['forecast_years'] ?? '-' }} ปี</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">หมวดครุภัณฑ์</span>
            <span class="meta-value">{{ $params['filter_cat_name'] ?? 'ทั้งหมด' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">หน่วยงาน</span>
            <span class="meta-value">{{ $params['filter_org_name'] ?? 'ทั้งหมด' }}</span>
        </div>
    </div>

    @php
        $success = $result['success'] ?? false;
        $total   = $result['total_assets'] ?? 0;
        $budget  = $result['total_forecast_budget'] ?? 0;
        $years   = $result['years'] ?? [];
        $assets  = $result['assets'] ?? [];
        $model   = $result['model'] ?? [];
    @endphp

    @if (!$success)
        <div class="empty-msg">ไม่สามารถแสดงผลการพยากรณ์ได้: {{ $result['message'] ?? 'ข้อมูลไม่เพียงพอ' }}</div>
    @elseif ($total === 0)
        <div class="empty-msg">ไม่พบครุภัณฑ์ที่คาดว่าจะถึงกำหนดทดแทนในช่วงเวลาที่เลือก</div>
    @else

        {{-- ── Summary boxes ── --}}
        <div class="summary-grid">
            <div class="summary-box">
                <span class="label">ระยะเวลาพยากรณ์</span>
                <span class="value">{{ $params['forecast_years'] ?? '-' }} ปี</span>
            </div>
            <div class="summary-box">
                <span class="label">จำนวนครุภัณฑ์ที่คาดว่าจะทดแทน</span>
                <span class="value">{{ number_format($total) }} รายการ</span>
            </div>
            <div class="summary-box highlight">
                <span class="label">งบประมาณรวมที่คาดการณ์</span>
                <span class="value">{{ number_format($budget, 2) }} บาท</span>
            </div>
        </div>

        {{-- ── Year table ── --}}
        @if (!empty($years))
            <div class="section-title">ผลการพยากรณ์รายปี</div>
            <table>
                <thead>
                    <tr>
                        <th>ปี</th>
                        <th class="center-cell">จำนวนครุภัณฑ์</th>
                        <th class="num-cell">งบประมาณที่คาดการณ์ (บาท)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($years as $yr)
                        @php $yearTh = ($yr['year'] ?? 0) + 543; @endphp
                        <tr>
                            <td>พ.ศ. {{ $yearTh }} (ค.ศ. {{ $yr['year'] ?? '-' }})</td>
                            <td class="center-cell">{{ number_format($yr['asset_count'] ?? 0) }}</td>
                            <td class="num-cell">{{ number_format($yr['forecast_budget'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="font-weight:800;">รวม</td>
                        <td class="center-cell" style="font-weight:800;">{{ number_format($total) }}</td>
                        <td class="num-cell" style="font-weight:800;">{{ number_format($budget, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- ── Asset details ── --}}
        @if (!empty($assets))
            <div class="section-title">รายละเอียดครุภัณฑ์</div>
            <table>
                <thead>
                    <tr>
                        <th>รหัสครุภัณฑ์</th>
                        <th>ชื่อครุภัณฑ์</th>
                        <th>หมวดครุภัณฑ์</th>
                        <th>หน่วยงาน</th>
                        <th>วันที่ตรวจรับ</th>
                        <th class="center-cell">ปีที่คาดว่าจะทดแทน</th>
                        <th class="num-cell">มูลค่าเดิม (บาท)</th>
                        <th class="num-cell">มูลค่าทดแทน AI (บาท)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assets as $a)
                        @php $yearTh = (($a['forecast_year'] ?? 0) + 543); @endphp
                        <tr>
                            <td>{{ $a['asset_code'] ?? '-' }}</td>
                            <td>{{ $a['asset_name'] ?? '-' }}</td>
                            <td>{{ $a['category_name'] ?? '-' }}</td>
                            <td>{{ $a['organization_name'] ?? '-' }}</td>
                            <td>{{ $a['acceptance_date'] ?? '-' }}</td>
                            <td class="center-cell">พ.ศ. {{ $yearTh }}</td>
                            <td class="num-cell">
                                {{ $a['current_value'] !== null ? number_format((float)$a['current_value'], 2) : '-' }}
                            </td>
                            <td class="num-cell ai-cost">
                                {{ number_format((float)($a['predicted_replacement_cost'] ?? 0), 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    @endif

    {{-- ── Remark ── --}}
    <div class="report-remark">
        <strong>หมายเหตุ:</strong>
        รายงานนี้จัดทำโดยระบบพยากรณ์ AI (Ridge Regression) และใช้สำหรับประกอบการวางแผนงบประมาณจัดซื้อครุภัณฑ์ทดแทนเท่านั้น
        ผลการพยากรณ์อาจมีความคลาดเคลื่อนตามปัจจัยทางเศรษฐกิจและตลาดที่เปลี่ยนแปลง
        กรุณาใช้ดุลยพินิจในการตัดสินใจจัดซื้อจริง
        @if (!empty($model['training_records']))
            | โมเดล: {{ $model['name'] ?? 'AI' }}
            · ข้อมูลฝึกสอน: {{ number_format($model['training_records']) }} รายการ
            @if ($model['mae'] !== null)
                · MAE: {{ number_format((float)$model['mae'], 2) }}
            @endif
        @endif
    </div>

</div>

</body>
</html>
