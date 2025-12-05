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
        .calendar-day { vertical-align: top; border: 1px solid #dee2e6; height: 120px; }
        .calendar-day.not-month { background-color: #f8f9fa; }
        .day-number { font-weight: bold; font-size: 1.2rem; }
        .day-price { color: #28a745; font-weight: bold; }
        .day-stock { color: #ffc107; }
        .info-card { background-color: #ffffff; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary mb-4">&larr; Back to Product List</a>

        @if ($error)
            <div class="alert alert-danger">
                <strong>API Error:</strong> {{ $error }}
            </div>
        @endif

        @if ($productDetail)
            <div class="product-header">
                <h1>{{ $productDetail['productBaseInfo']['productName'] ?? 'Product Name Not Available' }}</h1>
                <p class="lead">Product ID: {{ $productId }}</p>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="calendar">
                        <h3 class="mb-4">Price Calendar</h3>
                        @php
                            // Basic calendar generation
                            $prices = [];
                            if (isset($priceStock['productPriceStock']['prices'])) {
                                foreach ($priceStock['productPriceStock']['prices'] as $priceInfo) {
                                    $date = \Carbon\Carbon::createFromTimestampMs($priceInfo['date'])->format('Y-m-d');
                                    $prices[$date] = [
                                        'price' => $priceInfo['price'] / 100, // Assuming price is in cents
                                        'stock' => $priceInfo['stock']
                                    ];
                                }
                            }

                            $month = request('month', date('Y-m'));
                            $start = \Carbon\Carbon::parse($month)->startOfMonth();
                            $end = $start->copy()->endOfMonth();
                            $today = \Carbon\Carbon::today();
                        @endphp

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <a href="?month={{ $start->copy()->subMonth()->format('Y-m') }}" class="btn btn-primary">&laquo; Prev</a>
                            <h4>{{ $start->format('F Y') }}</h4>
                            <a href="?month={{ $start->copy()->addMonth()->format('Y-m') }}" class="btn btn-primary">Next &raquo;</a>
                        </div>

                        <table class="calendar-table">
                            <thead>
                                <tr>
                                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                                        <th>{{ $day }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                @for ($i = 0; $i < $start->dayOfWeek; $i++)
                                    <td class="calendar-day not-month"></td>
                                @endfor

                                @for ($date = $start->copy(); $date->lte($end); $date->addDay())
                                    @if ($date->dayOfWeek == 0 && !$date->isSameDay($start))
                                        </tr><tr>
                                    @endif

                                    <td class="calendar-day {{ $date->isToday() ? 'table-info' : '' }}">
                                        <div class="day-number">{{ $date->day }}</div>
                                        @php $current_date_str = $date->format('Y-m-d'); @endphp
                                        @if (isset($prices[$current_date_str]))
                                            <div class="day-price">¥{{ number_format($prices[$current_date_str]['price'], 2) }}</div>
                                            <div class="day-stock">Stock: {{ $prices[$current_date_str]['stock'] }}</div>
                                        @endif
                                    </td>

                                @endfor

                                @while ($date->dayOfWeek != 0)
                                    <td class="calendar-day not-month"></td>
                                    @php $date->addDay(); @endphp
                                @endwhile
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <h5>Confirmation Rule</h5>
                        <p>Type: {{ $productDetail['confirmRule']['confirmType'] == 0 ? 'Instant Confirmation' : 'Manual Confirmation' }}</p>
                        @if($productDetail['confirmRule']['confirmType'] == 1)
                            <p>Minutes: {{ $productDetail['confirmRule']['confirmMinutes'] }}</p>
                        @endif
                    </div>
                    <div class="info-card">
                        <h5>Refund Policy</h5>
                        <p>Type: {{ $productDetail['refund']['refundType'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning">
                Product details could not be loaded.
            </div>
        @endif
    </div>
</body>
</html>
