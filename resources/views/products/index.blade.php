<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fliggy Products</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 40px;
        }
        .card {
            margin-bottom: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .pagination-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .response-box {
            background-color: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Fliggy Product List</h1>

        @if ($error)
            <div class="alert alert-danger">
                <strong>API Error:</strong> {{ $error }}
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Products</h5>
                @if (empty($products) && !$error)
                    <div class="alert alert-info">
                        No products found for the current page. This might be the end of the list, or the test environment has no data.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Product ID</th>
                                    <th scope="col">Product Name</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">City</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td>{{ $product['productId'] ?? 'N/A' }}</td>
                                        <td>{{ $product['productName'] ?? 'N/A' }}</td>
                                        <td>{{ $product['productCategory'] ?? 'N/A' }}</td>
                                        <td>{{ $product['city'] ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pagination -->
        <nav class="pagination-nav" aria-label="Page navigation">
            <div>
                @if ($currentPage > 1)
                    <a href="{{ route('products.index', ['page' => $currentPage - 1]) }}" class="btn btn-primary">&laquo; Previous</a>
                @else
                    <a href="#" class="btn btn-primary disabled" aria-disabled="true">&laquo; Previous</a>
                @endif
            </div>
            <span>Page {{ $currentPage }}</span>
            <div>
                @if ($hasMorePages)
                    <a href="{{ route('products.index', ['page' => $currentPage + 1]) }}" class="btn btn-primary">Next &raquo;</a>
                @else
                    <a href="#" class="btn btn-primary disabled" aria-disabled="true">Next &raquo;</a>
                @endif
            </div>
        </nav>

        <!-- Raw API Response for Debugging -->
        <div class="card mt-4">
            <div class="card-header">
                Raw API Response
            </div>
            <div class="card-body">
                <pre class="response-box"><code>{{ $rawResponse }}</code></pre>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
