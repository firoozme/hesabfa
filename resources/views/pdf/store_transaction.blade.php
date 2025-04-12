<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>حواله انبار</title>
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('{{ public_path('fonts/Vazir.ttf') }}') format('truetype');
        }
        body {
            font-family: 'Vazir', sans-serif;
            direction: rtl;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>حواله انبار</h1>
    <p>شماره حواله: {{ $transaction->reference }}</p>
    <p>تاریخ: {{ verta($transaction->date)->format('Y/m/d') }}</p>
    <p>انبار مبدا: {{ $transaction->store->title }}</p>
    <p>مقصد: {{ $transaction->destination ? ($transaction->destination_type === 'App\Models\Store' ? 'انبار ' . $transaction->destination->title : 'مشتری') : '-' }}</p>

    <table>
        <thead>
            <tr>
                <th>محصول</th>
                <th>تعداد</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaction->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ number_format($item->quantity) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
