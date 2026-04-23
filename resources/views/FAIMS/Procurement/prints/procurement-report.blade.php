<!DOCTYPE html>
<html>
<head>
    <title>Procurement Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 8mm 12mm 8mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1;
            color: #000;
            margin: 0;
        }

        .sheet {
            border: 1px solid #222;
            box-shadow: inset 0 0 0 1px #222;
            padding-bottom: 110px;
        }

        .sheet-header {
            padding: 10px 12px 6px;
            text-align: center;
            border-bottom: 1px solid #222;
            position: relative;
        }

        .sheet-title {
            font-size: 16px;
            font-weight: 700;
        }

        .sheet-subtitle {
            font-size: 14px;
            font-weight: 700;
        }

        .document-code {
            position: absolute;
            top: -2px;
            right: 0;
            width: 105px;
            padding: 4px 4px 3px;
            border: 1px solid #222;
            background: #fff;
            text-align: center;
            line-height: 1;
            font-size: 10px;
        }

        .document-code-title {
            font-weight: 700;
        }


        .sheet-meta {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #222;
        }

        .sheet-meta td {
            padding: 6px 8px;
            font-size: 10px;
        }

        .sheet-meta td:last-child {
            text-align: right;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #222;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .report-table th {
            font-weight: 700;
            line-height: 1;
        }

        .band {
            background: #eef3d0;
            font-size: 10px;
        }

        .header-cell {
            background: #d7e6fa;
        }

        .header-status {
            background: #dbe8b4;
        }

        .empty {
            padding: 18px 0;
            color: #555;
        }

        .footer {
            position: fixed;
            left: 8mm;
            right: 8mm;
            bottom: 14mm;
            background: #fff;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .footer-table td {
            vertical-align: top;
        }

        .prepared-column {
            width: 75%;
            vertical-align: bottom;
        }

        .noted-column {
            width: 25%;
            vertical-align: bottom;
            padding-left: 8px;
        }

        .prepared-label {
            font-size: 10px;
            text-align: left;
            margin-bottom: 10px;
        }

        .noted-label {
            font-size: 10px;
            margin: 0 0 10px 0;
        }

        .prepared-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .prepared-grid td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
        }

        .signatory-name {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .signatory-role {
            font-size: 9px;
        }

        .noted-signatory {
            display: inline-block;
            text-align: center;
            width: 210px;
            margin: 0 auto;
        }

        .noted-signatory .signatory-name,
        .noted-signatory .signatory-role {
            display: block;
            text-align: center;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .noted-signatory .signatory-name {
            margin-bottom: 2px;
        }

        .noted-stack {
            width: 210px;
            margin: 14px auto 0;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $formatDate = function ($date) {
            if (! $date) {
                return 'N/A';
            }

            try {
                return \Carbon\Carbon::parse($date)->format('d-M-y');
            } catch (\Throwable $e) {
                return 'N/A';
            }
        };

        $contractSigningDate = function ($date) use ($formatDate) {
            if (! $date) {
                return 'N/A';
            }

            try {
                return \Carbon\Carbon::parse($date)->addDays(10)->format('d-M-y');
            } catch (\Throwable $e) {
                return 'N/A';
            }
        };

        $totalDays = function ($start, $end) {
            if (! $start || ! $end) {
                return 'N/A';
            }

            try {
                $diff = \Carbon\Carbon::parse($start)->startOfDay()->diffInDays(\Carbon\Carbon::parse($end)->startOfDay(), false);
                return $diff >= 0 ? $diff : 0;
            } catch (\Throwable $e) {
                return 'N/A';
            }
        };

        $prepared = collect($prepared_by_signatories ?? [])->values();
        $preparedOne = $prepared->get(0);
        $preparedTwo = $prepared->get(1);
    @endphp

    <div class="sheet">
        <div class="sheet-header">
            <div class="document-code">
                <div class="document-code-title">FASS-PUR F21</div>
                <div>Rev 1 / 05-09-2025</div>
            </div>
            <div class="sheet-title">DOST IX PROCUREMENT EVALUATION SHEET</div>
            <div class="sheet-subtitle">{{ $report_subtitle }}</div>
        </div>

        <table class="sheet-meta">
            <tr>
                <td>{{ $report_period_label }}</td>
                <td>{{ $fo_label }}</td>
            </tr>
        </table>

        <table class="report-table">
            <thead>
                <tr>
                    <th colspan="13" class="band">ALLOWABLE TIMELINE/PERIOD FOR PROCUREMENT PROCESS</th>
                </tr>
                <tr>
                    <th class="header-cell">APPROVED PURCHASE REQUEST NUMBER</th>
                    <th class="header-cell">DATE APPROVED PURCHASE REQUEST</th>
                    <th class="header-cell">Pre-Procurement Conference<br>(1 cd)</th>
                    <th class="header-cell">Advertisement / Posting of Invitation to Bid<br>(7 cd)</th>
                    <th class="header-cell">Pre-Bid Conference<br>(8-45 or 60 cd)</th>
                    <th class="header-cell">Deadline of Submission and Receipt of Bids / Bid Opening<br>(1-50 or 65 cd)</th>
                    <th class="header-cell">Approval of Resolution/Issuance of Notice of Award<br>(1-15 cd)</th>
                    <th class="header-cell">Contract Preparation and Signing<br>(1-10 cd)</th>
                    <th class="header-cell">Approval of contract by higher authority<br>(1-20 or 30 cd)</th>
                    <th class="header-cell">Bid Evaluation<br>(1-7 cd)</th>
                    <th class="header-cell">Post-Qualification<br>(2-45 cd)</th>
                    <th class="header-cell">Issuance of Notice to Proceed<br>(1-7 cd)</th>
                    <th class="header-cell">Total No. of Days</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($procurements as $item)
                    <tr>
                        <td>{{ $item->code }}</td>
                        <td>{{ $formatDate($item->date) }}</td>
                        <td>N/A</td>
                        <td>N/A</td>
                        <td>N/A</td>
                        <td>{{ $formatDate($item->updated_at ?: $item->date) }}</td>
                        <td>{{ $formatDate($item->updated_at ?: $item->date) }}</td>
                        <td>{{ $contractSigningDate($item->updated_at ?: $item->date) }}</td>
                        <td>N/A</td>
                        <td>{{ $formatDate($item->updated_at ?: $item->date) }}</td>
                        <td>{{ $formatDate($item->updated_at ?: $item->date) }}</td>
                        <td>N/A</td>
                        <td>{{ $totalDays($item->date, $item->updated_at ?: $item->date) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="empty">No procurement results available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="prepared-column">
                    <div class="prepared-label">Prepared by:</div>
                    <table class="prepared-grid">
                        <tr>
                            <td>
                                <div class="signatory-name">{{ $preparedOne['name'] ?? 'N/A' }}</div>
                                <div class="signatory-role">{{ $preparedOne['role'] ?? 'Procurement Staff' }}</div>
                            </td>
                            <td>
                                <div class="signatory-name">{{ $preparedTwo['name'] ?? '' }}</div>
                                <div class="signatory-role">{{ $preparedTwo['role'] ?? '' }}</div>
                            </td>
                            <td>
                                <div class="signatory-name">{{ $supply_officer['name'] ?? '' }}</div>
                                <div class="signatory-role">{{ $supply_officer['role'] ?? '' }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="noted-column">
                    <div class="noted-label">Noted by:</div>
                    <div class="noted-signatory">
                        <div class="signatory-name">{{ $noted_by['name'] ?? '' }}</div>
                        <div class="signatory-role">{{ $noted_by['designation'] ?? '' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 12;
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $pdf->page_text(780, 2, $text, $font, $size, [0, 0, 0]);
        }
    </script>
</body>
</html>
