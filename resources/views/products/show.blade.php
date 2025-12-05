<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .product-header { background-color: #ffffff; padding: 2rem; border-radius: 0.5rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
        .calendar { background-color: #ffffff; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
        .calendar-table { width: 100%; }
        .calendar-table th, .calendar-table td { text-align: center; padding: 0.75rem; }
        .calendar-table thead { background-color: #343a40; color: #ffffff; }
        .calendar-day { vertical-align: top; border: 1px solid #dee2e6; height: 120px; transition: background-color 0.2s; }
        .calendar-day.not-month { background-color: #f8f9fa; }
        .calendar-day.has-price:hover { background-color: #e9ecef; cursor: pointer; }
        .calendar-day.selected { background-color: #0d6efd; color: white; }
        .calendar-day.selected .day-price, .calendar-day.selected .day-stock { color: white; }
        .day-number { font-weight: bold; font-size: 1.2rem; }
        .day-price { color: #28a745; font-weight: bold; }
        .day-stock { color: #6c757d; }
        .info-card, .booking-form-card { background-color: #ffffff; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary mb-4">&larr; Back to Product List</a>

        @if ($error)
            <div class="alert alert-danger"><strong>API Error:</strong> {{ $error }}</div>
        @endif

        @if (session('booking_error'))
            <div class="alert alert-danger"><strong>Booking Error:</strong> {{ session('booking_error') }}</div>
        @endif

        @if (session('booking_success'))
            <div class="alert alert-success"><strong>Success:</strong> {{ session('booking_success') }}</div>
        @endif

        @if ($productDetail)
            <div class="product-header">
                <h1>{{ $productDetail['productBaseInfo']['productName'] ?? 'Product Name Not Available' }}</h1>
                <p class="lead">Product ID: {{ $productId }}</p>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="calendar">
                        <h3 class="mb-4">1. Select a Date</h3>
                        @php
                            $prices = [];
                            if (isset($priceStock['productPriceStock']['calendarStocks'])) {
                                foreach ($priceStock['productPriceStock']['calendarStocks'] as $priceInfo) {
                                    $date = \Carbon\Carbon::createFromTimestampMs($priceInfo['date'])->format('Y-m-d');
                                    $prices[$date] = ['price' => $priceInfo['distributionPrice'], 'stock' => $priceInfo['stock']];
                                }
                            }
                            $month = request('month', date('Y-m'));
                            $start = \Carbon\Carbon::parse($month)->startOfMonth();
                            $end = $start->copy()->endOfMonth();
                        @endphp

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <a href="?month={{ $start->copy()->subMonth()->format('Y-m') }}" class="btn btn-primary">&laquo; Prev</a>
                            <h4>{{ $start->format('F Y') }}</h4>
                            <a href="?month={{ $start->copy()->addMonth()->format('Y-m') }}" class="btn btn-primary">Next &raquo;</a>
                        </div>

                        <table class="calendar-table">
                            <thead><tr>@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)<th>{{$d}}</th>@endforeach</tr></thead>
                            <tbody>
                                <tr>
                                @for ($i = 0; $i < $start->dayOfWeek; $i++)<td class="calendar-day not-month"></td>@endfor
                                @for ($date = $start->copy(); $date->lte($end); $date->addDay())
                                    @if ($date->dayOfWeek == 0 && !$date->isSameDay($start))</tr><tr>@endif
                                    @php
                                        $current_date_str = $date->format('Y-m-d');
                                        $priceInfo = $prices[$current_date_str] ?? null;
                                    @endphp
                                    <td class="calendar-day {{ $priceInfo ? 'has-price' : '' }} {{ $date->isPast() && !$date->isToday() ? 'not-month' : '' }}"
                                        @if($priceInfo)
                                            data-date="{{ $current_date_str }}"
                                            data-price="{{ $priceInfo['price'] }}"
                                            data-stock="{{ $priceInfo['stock'] }}"
                                        @endif>
                                        <div class="day-number">{{ $date->day }}</div>
                                        @if ($priceInfo)
                                            <div class="day-price">¥{{ number_format($priceInfo['price'], 2) }}</div>
                                            <div class="day-stock">Stock: {{ $priceInfo['stock'] }}</div>
                                        @endif
                                    </td>
                                @endfor
                                @while ($date->dayOfWeek != 0)<td class="calendar-day not-month"></td>@php $date->addDay(); @endphp @endwhile
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <h5>Confirmation Rule</h5>
                        <p>Type: {{ $productDetail['confirmRule']['confirmType'] == 0 ? 'Instant Confirmation' : 'Manual Confirmation' }}</p>
                        @if($productDetail['confirmRule']['confirmType'] == 1)<p>Minutes: {{ $productDetail['confirmRule']['confirmMinutes'] }}</p>@endif
                    </div>
                    <div class="info-card">
                        <h5>Refund Policy</h5>
                        <p>Type: {{ $productDetail['refund']['refundType'] ?? 'N/A' }}</p>
                    </div>

                    <!-- Booking Form -->
                    <div id="booking-form-container" class="booking-form-card" style="display: none;">
                        <h3 class="mb-4">2. Fill Information</h3>
                        <form id="booking-form" method="POST" action="{{ route('products.book', $productId) }}">
                            @csrf
                            <input type="hidden" name="selected_date" id="selected_date">
                            <input type="hidden" name="price" id="price">
                            <input type="hidden" name="stock" id="stock">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="mobile" class="form-label">Mobile Phone</label>
                                <input type="text" class="form-control" id="mobile" name="mobile" required pattern="^1[3-9]\d{9}$">
                                <div class="invalid-feedback">Please enter a valid 11-digit mobile number.</div>
                            </div>
                            <div class="mb-3">
                                <label for="id_card" class="form-label">ID Card Number</label>
                                <input type="text" class="form-control" id="id_card" name="id_card" required pattern="(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)">
                                <div class="invalid-feedback">Please enter a valid 15 or 18-digit ID card number.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Submit Booking</button>
                            </div>
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
