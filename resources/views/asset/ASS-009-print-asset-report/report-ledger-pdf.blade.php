<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <style>
        {{-- Bold needs its own font file, and Dompdf only registers @font-face from a plain
             absolute path (a file:/// URL silently fails), otherwise bold Thai text falls
             back to Helvetica-Bold and disappears. --}}
        @font-face { font-family: 'ReportThai'; src: url('{{ str_replace('\\', '/', public_path('fonts/tahoma.ttf')) }}') format('truetype'); font-weight: normal; font-style: normal; }
        @font-face { font-family: 'ReportThai'; src: url('{{ str_replace('\\', '/', public_path('fonts/tahomabd.ttf')) }}') format('truetype'); font-weight: bold; font-style: normal; }

        @page { margin: 8mm; }

        body { color: #111827; font-family: 'ReportThai', sans-serif; font-size: 8px; }
        .asset-ledger-heading { margin-bottom: 12px; text-align: center; }
        .asset-ledger-title { font-size: 14px; font-weight: bold; }
        .asset-ledger-org { margin-top: 5px; font-size: 11px; font-weight: bold; }
        .asset-ledger-report-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .asset-ledger-report-table th,
        .asset-ledger-report-table td { border: 1px solid #64748b; overflow-wrap: break-word; padding: 3px 2px; vertical-align: top; }
        .asset-ledger-report-table th { font-weight: bold; text-align: center; vertical-align: middle; }
        .asset-ledger-report-table .number-cell { text-align: right; white-space: nowrap; }
        .asset-ledger-report-table .center-cell { text-align: center; }
        .report-no-data { padding: 10px; text-align: center; }
        .ledger-signature { margin-top: 26px; width: 100%; border-collapse: collapse; }
        .ledger-signature td { padding: 0; border: 0; vertical-align: top; }
        .ledger-signature .signature-box { line-height: 2.1; text-align: left; }
        .asset-ledger-report-table th:nth-child(1) { width: 4%; }
        .asset-ledger-report-table th:nth-child(2) { width: 5%; }
        .asset-ledger-report-table th:nth-child(3) { width: 6%; }
        .asset-ledger-report-table th:nth-child(4) { width: 9%; }
        .asset-ledger-report-table th:nth-child(5) { width: 9%; }
        .asset-ledger-report-table th:nth-child(6) { width: 10%; }
        .asset-ledger-report-table th:nth-child(7) { width: 11%; }
        .asset-ledger-report-table th:nth-child(8) { width: 7%; }
        .asset-ledger-report-table th:nth-child(9) { width: 7%; }
        .asset-ledger-report-table th:nth-child(10) { width: 4%; }
        .asset-ledger-report-table th:nth-child(11) { width: 5%; }
        .asset-ledger-report-table th:nth-child(12) { width: 7%; }
        .asset-ledger-report-table th:nth-child(13) { width: 6%; }
        .asset-ledger-report-table th:nth-child(14) { width: 5%; }
        .asset-ledger-report-table th:nth-child(15) { width: 5%; }
    </style>
</head>
<body>
    @include('asset.ASS-009-print-asset-report._ledger-content')

    {{-- Signature block belongs to the printed form only, never to the on-screen preview. --}}
    <table class="ledger-signature">
        <tr>
            <td style="width: 60%;"></td>
            <td class="signature-box">
                ลงชื่อผู้รายงาน ................................................<br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(................................................)<br>
                ตำแหน่ง ........................................................<br>
                วันที่ .......... เดือน ...................... พ.ศ. ..............
            </td>
        </tr>
    </table>
</body>
</html>
