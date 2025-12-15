@extends('saas.layouts.app')

@section('title', '产品管理')

@section('content')
<div class="content-card">
    <div class="card-header-actions">
        <h5>产品管理</h5>
    </div>

    <div class="card-body-content">
        <div class="action-bar">
            <div class="input-group" style="max-width: 400px;">
                <select class="form-select" style="max-width: 120px;">
                    <option selected>所有渠道</option>
                    <option value="1">飞猪</option>
                    <option value="2">携程</option>
                </select>
                <input type="text" class="form-control" placeholder="按产品名称或ID搜索...">
                <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
            </div>
            <div>
                <a href="{{ route('saas.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> 新增产品</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">产品名称</th>
                        <th scope="col">OTA平台</th>
                        <th scope="col">景区</th>
                        <th scope="col">状态</th>
                        <th scope="col">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>
                            <a href="{{ route('saas.products.edit', $product) }}">{{ $product->productname }}</a>
                        </td>
                        <td>
                            @if($product->ota)
                                <span class="tag tag-{{ strtolower($product->ota->name) }}">{{ $product->ota->name }}</span>
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $product->tourist->name ?? 'N/A' }}</td>
                        <td>
                            @if($product->online)
                                <span class="badge bg-success">上架</span>
                            @else
                                <span class="badge bg-secondary">下架</span>
                            @endif
                        </td>
                        <td>
                            <a href="#">查看</a>
                            <a href="{{ route('saas.products.edit', $product) }}" class="ms-2">编辑</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">未找到任何产品。</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer-actions">
        {{ $products->links() }}
    </div>
</div>
@endsection

@push('styles')
<style>
    .content-card {
        background-color: var(--nb-content-bg-color);
        border-radius: 8px;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03), 0 1px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px 0 rgba(0, 0, 0, 0.02);
    }
    .card-header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 24px;
        border-bottom: 1px solid var(--nb-border-color);
    }
    .card-header-actions h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }
    .card-body-content {
        padding: 24px;
    }
    .action-bar {
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    .table {
        color: var(--nb-text-color);
    }
    .table thead th {
        background-color: #fafafa;
        border-bottom: 1px solid var(--nb-border-color);
        color: var(--nb-text-color-secondary);
        font-weight: 500;
        padding: 12px 16px;
    }
    .table tbody tr {
        transition: background-color 0.3s;
    }
    .table tbody tr:hover {
        background-color: #fafafa;
    }
    .table td {
        border-bottom: 1px solid var(--nb-border-color);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    .table a {
        color: var(--nb-primary-color);
        text-decoration: none;
    }
    .table a:hover {
        text-decoration: underline;
    }
    .tag {
        display: inline-block;
        padding: 0.25em 0.6em;
        font-size: 0.85em;
        font-weight: 500;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 4px;
    }
    .tag-fliggy {
        color: #d46b08;
        background: #fff7e6;
        border: 1px solid #ffd591;
    }
    .tag-ctrip {
        color: #096dd9;
        background: #e6f7ff;
        border: 1px solid #91d5ff;
    }
    .card-footer-actions {
        display: flex;
        justify-content: flex-end;
        padding: 16px 24px;
        border-top: 1px solid var(--nb-border-color);
    }
</style>
@endpush
