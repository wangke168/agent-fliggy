<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fliggy Products - NocoBase Style</title>
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
        .content-card {
            background-color: var(--nocobase-content-bg-color);
            border-radius: 8px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03), 0 1px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px 0 rgba(0, 0, 0, 0.02);
        }
        .card-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid var(--nocobase-border-color);
        }
        .card-header-actions h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .card-body-content {
            padding: 24px;
        }
        .table {
            color: var(--nocobase-text-color);
        }
        .table thead th {
            background-color: #fafafa;
            border-bottom: 1px solid var(--nocobase-border-color);
            color: var(--nocobase-text-color-secondary);
            font-weight: 500;
            padding: 16px;
        }
        .table tbody tr {
            transition: background-color 0.3s;
        }
        .table tbody tr:hover {
            background-color: #fafafa;
        }
        .table td {
            border-bottom: 1px solid var(--nocobase-border-color);
            vertical-align: middle;
            padding: 16px;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .table a {
            color: var(--nocobase-primary-color);
            text-decoration: none;
        }
        .table a:hover {
            text-decoration: underline;
        }
        .card-footer-actions {
            display: flex;
            justify-content: flex-end;
            padding: 16px 24px;
            border-top: 1px solid var(--nocobase-border-color);
        }
        .response-box {
            background-color: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--nocobase-border-color);
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="content-card">
            <div class="card-header-actions">
                <h5>Fliggy Products</h5>
                <a href="{{ request()->fullUrl() }}" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </a>
            </div>

            <div class="card-body-content">
                @if ($error)
                    <div class="alert alert-danger"><strong>API Error:</strong> {{ $error }}</div>
                @endif

                @if (empty($products) && !$error)
                    <div class="alert alert-info">No products found for the current page.</div>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Product ID</th>
                                    <th scope="col">Product Name</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">City</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    @php $productId = $product['productId'] ?? null; @endphp
                                    <tr>
                                        <td>
                                            @if($productId)
                                                <a href="{{ route('products.show', $productId) }}">{{ $productId }}</a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($productId)
                                                <a href="{{ route('products.show', $productId) }}">{{ $product['productName'] ?? 'N/A' }}</a>
                                            @else
                                                {{ $product['productName'] ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ $product['productCategory'] ?? 'N/A' }}</td>
                                        <td>{{ $product['city'] ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card-footer-actions">
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ route('products.index', ['page' => $currentPage - 1]) }}">&laquo;</a>
                        </li>
                        <li class="page-item active" aria-current="page">
                            <span class="page-link">{{ $currentPage }}</span>
                        </li>
                        <li class="page-item {{ !$hasMorePages ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ route('products.index', ['page' => $currentPage + 1]) }}">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Raw API Response for Debugging -->
        <div class="content-card mt-4">
            <div class="card-header-actions">
                <h5>Raw API Response</h5>
            </div>
            <div class="card-body-content">
                <pre class="response-box"><code>{{ $rawResponse }}</code></pre>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
