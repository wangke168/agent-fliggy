<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Detail - NocoBase Style</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --nocobase-bg-color: #f0f2f5;
            --nocobase-content-bg-color: #ffffff;
            --nocobase-border-color: #e8e8e8;
            --nocobase-text-color: #262626;
            --nocobase-text-color-secondary: #8c8c8c;
            --nocobase-primary-color: #1890ff;
        }
        body {
            background-color: var(--nocobase-bg-color);
            color: var(--nocobase-text-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
        }
        .main-content {
            padding: 24px;
        }
        .page-header {
            background-color: var(--nocobase-content-bg-color);
            padding: 16px 24px;
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid var(--nocobase-border-color);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03), 0 1px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px 0 rgba(0, 0, 0, 0.02);
        }
        .page-header h1 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .page-header .breadcrumb-item a {
            text-decoration: none;
            color: var(--nocobase-primary-color);
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
        }
        .content-card {
            background-color: var(--nocobase-content-bg-color);
            border-radius: 8px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03), 0 1px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px 0 rgba(0, 0, 0, 0.02);
        }
        .card-body-content {
            padding: 24px;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .calendar-header h4 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }
        .calendar-table { width: 100%; }
        .calendar-table th {
            text-align: center;
            padding: 0.75rem;
            color: var(--nocobase-text-color-secondary);
            font-weight: 400;
        }
        .calendar-day {
            vertical-align: top;
            border: 1px solid var(--nocobase-border-color);
            height: 120px;
            padding: 8px;
            transition: background-color 0.2s;
        }
        .calendar-day.not-month { background-color: #fafafa; }
        .calendar-day.has-price:hover { background-color: #f0f8ff; cursor: pointer; }
        .calendar-day.selected {
            background-color: var(--nocobase-primary-color);
            color: white;
            border-color: var(--nocobase-primary-color);
        }
        .calendar-day.selected .day-label, .calendar-day.selected .day-stock { color: white; opacity: 0.8; }
        .day-number { text-align: left; font-size: 14px; }
        .day-price { font-weight: 600; font-size: 14px; }
        .day-label { font-size: 12px; color: var(--nocobase-text-color-secondary); }
        .price-sale { color: #dc3545; }
        .price-dist { color: #28a745; }
        .day-stock { font-size: 12px; color: var(--nocobase-text-color-secondary); }
        .info-card, .booking-form-card {
            background-color: var(--nocobase-content-bg-color);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03), 0 1px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px 0 rgba(0, 0, 0, 0.02);
        }
        .info-card h5, .booking-form-card h5 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="main-content">
        @if ($productDetail)
            <div class="page-header">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detail</li>
                    </ol>
                </nav>
                <h1>{{ $productDetail['productBaseInfo']['productName'] ?? 'Product Name Not Available' }}</h1>
            </div>

            <div class="content-grid mt-4">
                <div class="content-card">
                    <div class="card-body-content">
                        @php
                            $prices = [];
                            if (isset($priceStock['productPriceStock']['calendarStocks'])) {
                                foreach ($priceStock['productPriceStock']['calendarStocks'] as $priceInfo) {
                                    $date = \Carbon\Carbon::createFromTimestampMs($priceInfo['date'])->format('Y-m-d');
                                    $prices[$date] = ['suggestedPrice' => $priceInfo['suggestedPrice'], 'distributionPrice' => $priceInfo['distributionPrice'], 'stock' => $priceInfo['stock']];
                                }
                            }
                            $month = request('month', date('Y-m'));
                            $start = \Carbon\Carbon::parse($month)->startOfMonth();
                            $end = $start->copy()->endOfMonth();
                        @endphp
                        <div class="calendar-header">
                            <a href="?month={{ $start->copy()->subMonth()->format('Y-m') }}" class="btn btn-sm btn-outline-secondary">&laquo; Prev</a>
                            <h4>{{ $start->format('F Y') }}</h4>
                            <a href="?month={{ $start->copy()->addMonth()->format('Y-m') }}" class="btn btn-sm btn-outline-secondary">Next &raquo;</a>
                        </div>
                        <table class="calendar-table">
                            <thead><tr>@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)<th>{{$d}}</th>@endforeach</tr></thead>
                            <tbody>
                                <tr>
                                @for ($i = 0; $i < $start->dayOfWeek; $i++)<td class="calendar-day not-month"></td>@endfor
                                @for ($date = $start->copy(); $date->lte($end); $date->addDay())
                                    @if ($date->dayOfWeek == 0 && !$date->isSameDay($start))</tr><tr>@endif
                                    @php $priceInfo = $prices[$date->format('Y-m-d')] ?? null; @endphp
                                    <td class="calendar-day {{ $priceInfo ? 'has-price' : '' }} {{ $date->isPast() && !$date->isToday() ? 'not-month' : '' }}"
                                        @if($priceInfo) data-date="{{ $date->format('Y-m-d') }}" data-price="{{ $priceInfo['distributionPrice'] }}" data-stock="{{ $priceInfo['stock'] }}" @endif>
                                        <div class="day-number">{{ $date->day }}</div>
                                        @if ($priceInfo)
                                            <div><span class="day-label">售价</span> <span class="day-price price-sale">¥{{ number_format($priceInfo['suggestedPrice'], 2) }}</span></div>
                                            <div class="mt-1"><span class="day-label">结算价</span> <span class="day-price price-dist">¥{{ number_format($priceInfo['distributionPrice'], 2) }}</span></div>
                                            <div class="day-stock mt-1">Stock: {{ $priceInfo['stock'] }}</div>
                                        @endif
                                    </td>
                                @endfor
                                @while ($date->dayOfWeek != 0)<td class="calendar-day not-month"></td>@php $date->addDay(); @endphp @endwhile
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <div class="info-card">
                        <h5>Details</h5>
                        <p class="mb-1"><strong>Confirmation:</strong> {{ $productDetail['confirmRule']['confirmType'] == 0 ? 'Instant' : 'Manual' }}</p>
                        <p class="mb-1"><strong>Refund Policy:</strong> {{ $productDetail['refund']['refundType'] ?? 'N/A' }}</p>
                    </div>
                    <div id="booking-form-container" class="booking-form-card" style="display: none;">
                        <h5>Book Now</h5>
                        <form id="booking-form" method="POST" action="{{ route('products.book', $productId) }}">
                            @csrf
                            <input type="hidden" name="selected_date" id="selected_date"><input type="hidden" name="price" id="price"><input type="hidden" name="stock" id="stock">
                            <div class="mb-3"><label for="name" class="form-label">Name</label><input type="text" class="form-control" id="name" name="name" required></div>
                            <div class="mb-3"><label for="mobile" class="form-label">Mobile</label><input type="text" class="form-control" id="mobile" name="mobile" required pattern="^1[3-9]\d{9}$"><div class="invalid-feedback">Invalid mobile number.</div></div>
                            <div class="mb-3"><label for="id_card" class="form-label">ID Card</label><input type="text" class="form-control" id="id_card" name="id_card" required pattern="(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)"><div class="invalid-feedback">Invalid ID card number.</div></div>
                            <div class="d-grid"><button type="submit" class="btn btn-primary">Submit Booking</button></div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning">Product details could not be loaded.</div>
        @endif
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarDays = document.querySelectorAll('.calendar-day.has-price');
            const bookingFormContainer = document.getElementById('booking-form-container');
            const selectedDateInput = document.getElementById('selected_date');
            const priceInput = document.getElementById('price');
            const stockInput = document.getElementById('stock');
            const form = document.getElementById('booking-form');
            calendarDays.forEach(day => {
                day.addEventListener('click', function () {
                    calendarDays.forEach(d => d.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedDateInput.value = this.dataset.date;
                    priceInput.value = this.dataset.price;
                    stockInput.value = this.dataset.stock;
                    bookingFormContainer.style.display = 'block';
                });
            });
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    </script>
</body>
</html>
