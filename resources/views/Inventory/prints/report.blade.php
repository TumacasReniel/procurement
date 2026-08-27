<!DOCTYPE html>
<html>
<head>
    <title>{{ $report->code }} - {{ $report->title?->name }}</title>
    <style>
        @page {
            margin: 10mm 10mm 12mm 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1;
            color: #000;
            margin: 0;
        }

        .rsmi-title { text-align: center; font-size: 16px; font-weight: 700; text-transform: uppercase; }
        .rsmi-subtitle { text-align: center; font-size: 11px; font-style: italic; color: #1e40af; margin-top: 2px; }
        .rsmi-agency { text-align: center; font-size: 11px; margin-top: 2px; }
        .rsmi-address { text-align: center; font-size: 10px; color: #333; margin-top: 2px; }

        .rsmi-meta { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .rsmi-meta td { padding: 2px 0; font-size: 11px; }
        .rsmi-meta td:last-child { text-align: right; }
        .rsmi-meta-sub { width: 100%; border-collapse: collapse; }
        .rsmi-meta-sub td { font-size: 9px; font-style: italic; text-align: center; padding: 2px 0; border-top: 1px solid #000; border-bottom: 1px solid #000; }

        .rsmi-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .rsmi-table thead { display: table-header-group; }
        .rsmi-table th, .rsmi-table td {
            border: 1px solid #000; padding: 3px 5px; font-size: 10px; vertical-align: top;
        }
        .rsmi-table th { text-align: center; font-weight: 700; background: #f3f4f6; }
        .rsmi-table td.num { text-align: right; }
        .rsmi-table td.center { text-align: center; }
        .rsmi-table tr.total-row td { font-weight: 700; border-top: 2px solid #000; }
        .rsmi-table .empty { text-align: center; color: #555; padding: 14px 0; }

        .rsmi-summary { width: 45%; margin-top: 12px; margin-left: auto; border-collapse: collapse; }
        .rsmi-summary caption { text-align: left; font-size: 10px; font-weight: 700; margin-bottom: 3px; caption-side: top; }
        .rsmi-summary td { padding: 2px 6px; font-size: 10px; }
        .rsmi-summary td:last-child { text-align: right; }
        .rsmi-summary tr.total-row td { font-weight: 700; border-top: 1px solid #000; }

        .rsmi-cert { font-size: 10px; margin-top: 18px; }
        .rsmi-footer { width: 100%; border-collapse: collapse; margin-top: 30px; table-layout: fixed; }
        .rsmi-footer td { vertical-align: top; font-size: 10px; }
        .rsmi-sig-name { font-size: 10px; font-weight: 700; text-align: center; margin-top: 26px; border-top: 1px solid #000; padding-top: 2px; }
        .rsmi-sig-role { font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    @php
        $columnCount = match ($kind) {
            'received' => 8,
            'withdrawn' => 8,
            'ris_issued' => 8,
            default => 1,
        };
        $hasData = $kind === 'ris_issued' ? $groups->isNotEmpty() : $rows->isNotEmpty();
    @endphp

    <div class="rsmi-title">Report of {{ $report->title?->name ?? 'Inventory' }}</div>
    <div class="rsmi-subtitle">
        for {{ $report->period_type === 'monthly' ? 'the month of ' : '' }}{{ $report->period_label }}
    </div>
    <div class="rsmi-agency">Department of Science and Technology Regional Office No. IX</div>
    <div class="rsmi-address">Capt. F. Marcos Rd., cor. Gen. Vicente Alvarez St., Pettit Barracks, Zone IV, Zamboanga City</div>

    <table class="rsmi-meta">
        <tr>
            <td><b>Date:</b> {{ optional($report->created_at)->format('F d, Y') }}</td>
            <td><b>No.:</b> {{ $report->code }}</td>
        </tr>
    </table>
    <table class="rsmi-meta-sub">
        <tr>
            <td style="width:50%">To be filled-up by the Supply and Property Unit</td>
            <td style="width:50%">To be filled-up in the Accounting Unit</td>
        </tr>
    </table>

    @if ($kind === 'ris_issued')
        <table class="rsmi-table">
            <thead>
                <tr>
                    <th style="width:10%">RIS No.</th>
                    <th style="width:10%">Resp Center Code</th>
                    <th style="width:10%">Item No.</th>
                    <th>Item Name</th>
                    <th style="width:7%">Unit</th>
                    <th style="width:8%">Quantity</th>
                    <th style="width:9%">Unit Cost</th>
                    <th style="width:10%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groups as $group)
                    @foreach ($group['items'] as $i => $item)
                        <tr>
                            @if ($i === 0)
                                <td class="center" rowspan="{{ count($group['items']) }}">{{ $group['ris_no'] }}</td>
                                <td class="center" rowspan="{{ count($group['items']) }}">{{ $group['responsibility_center'] ?? '—' }}</td>
                            @endif
                            <td>{{ $item['item_no'] }}</td>
                            <td>{{ $item['item_name'] }}</td>
                            <td class="center">{{ $item['unit'] }}</td>
                            <td class="num">{{ number_format($item['quantity'], 2) }}</td>
                            <td class="num">{{ number_format($item['unit_cost'], 2) }}</td>
                            <td class="num">{{ number_format($item['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="{{ $columnCount }}" class="empty">No issued RIS items found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($kind === 'received')
        <table class="rsmi-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Item</th>
                    <th>Stock</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Date Received</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="center">{{ $row['stock'] ?? '—' }}</td>
                        <td class="num">{{ number_format($row['quantity'], 2) }}</td>
                        <td class="num">{{ number_format($row['unit_cost'], 2) }}</td>
                        <td class="num">{{ number_format($row['total_cost'], 2) }}</td>
                        <td class="center">{{ $row['date'] }}</td>
                        <td class="center">{{ $row['status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $columnCount }}" class="empty">No receiving records found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif ($kind === 'withdrawn')
        <table class="rsmi-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Item</th>
                    <th>Qty Requested</th>
                    <th>Qty Issued</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Date Released</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format($row['quantity'], 2) }}</td>
                        <td class="num">{{ number_format($row['issued_quantity'], 2) }}</td>
                        <td class="num">{{ number_format($row['unit_cost'], 2) }}</td>
                        <td class="num">{{ number_format($row['total_cost'], 2) }}</td>
                        <td class="center">{{ $row['date'] }}</td>
                        <td class="center">{{ $row['status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $columnCount }}" class="empty">No withdrawal records found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table class="rsmi-table">
            <tbody>
                <tr><td class="empty">No data source configured for this report title.</td></tr>
            </tbody>
        </table>
    @endif

    @if ($hasData)
        <table class="rsmi-table" style="margin-top:0">
            <tr class="total-row">
                <td colspan="{{ $columnCount - 1 }}" class="num">TOTAL</td>
                <td class="num">{{ number_format($grand_total, 2) }}</td>
            </tr>
        </table>
    @endif

    @if (count($category_totals))
        <table class="rsmi-summary">
            <caption>Summary by Category</caption>
            @foreach ($category_totals as $categoryName => $amount)
                <tr>
                    <td>{{ $categoryName }}</td>
                    <td>{{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total</td>
                <td>{{ number_format($grand_total, 2) }}</td>
            </tr>
        </table>
    @endif

    <div class="rsmi-cert">I hereby certify to the correctness of the above information.</div>

    <table class="rsmi-footer">
        <tr>
            <td style="width:50%">
                <div class="rsmi-sig-name">{{ $supply_officer['name'] ?? 'N/A' }}</div>
                <div class="rsmi-sig-role">{{ $supply_officer['role'] ?? 'Supply Officer' }}</div>
            </td>
            <td style="width:50%">
                <div style="font-size:10px">Posted by/date:</div>
                <div class="rsmi-sig-name">{{ $accountant['name'] ?? 'N/A' }}</div>
                <div class="rsmi-sig-role">{{ $accountant['role'] ?? 'Accountant III' }}</div>
            </td>
        </tr>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 9;
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $pdf->page_text(500, 815, $text, $font, $size, [0, 0, 0]);
        }
    </script>
</body>
</html>
