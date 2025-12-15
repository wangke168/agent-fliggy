@extends('saas.layouts.app')

@section('title', '编辑产品')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        /* Styles from create.blade.php */
    </style>
@endpush

@section('content')
<div class="page-header">
    {{-- Breadcrumb --}}
    <h1>编辑: {{ $product->productname }}</h1>
</div>

<form action="{{ route('saas.products.update', $product) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-8">
            {{-- Basic Info Card --}}

            {{-- Hotel & Room Type Selection Card --}}

            {{-- Inventory Management Card --}}
            <div class="content-card mt-4">
                <div class="card-header-actions">
                    <h5>库存管理</h5>
                    <button type="button" id="add-inventory-btn" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> 添加库存</button>
                </div>
                <div class="card-body-content" id="inventory-container">
                    @foreach($product->inventories as $index => $inventory)
                    <div class="row align-items-end mb-2 inventory-item">
                        <div class="col">
                            <label class="form-label">日期</label>
                            <input type="date" name="inventories[{{$index}}][inventory_date]" class="form-control" value="{{ $inventory->inventory_date->format('Y-m-d') }}">
                        </div>
                        <div class="col">
                            <label class="form-label">库存</label>
                            <input type="number" name="inventories[{{$index}}][stock]" class="form-control" value="{{ $inventory->stock }}">
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-danger remove-inventory-btn"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Attributes Card --}}
        </div>
    </div>

    <div class="main-footer-actions">
        <a href="{{ route('saas.products.index') }}" class="btn btn-secondary">取消</a>
        <button type="submit" class="btn btn-primary ms-2">保存更改</button>
    </div>
</form>

<template id="inventory-template">
    <div class="row align-items-end mb-2 inventory-item">
        <div class="col">
            <label class="form-label">日期</label>
            <input type="date" name="inventories[][inventory_date]" class="form-control">
        </div>
        <div class="col">
            <label class="form-label">库存</label>
            <input type="number" name="inventories[][stock]" class="form-control" value="0">
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-danger remove-inventory-btn"><i class="bi bi-trash"></i></button>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ... (existing hotel/roomtype script)

    // Inventory Management Script
    const inventoryContainer = document.getElementById('inventory-container');
    const inventoryTemplate = document.getElementById('inventory-template');
    let inventoryIndex = {{ $product->inventories->count() }};

    function addInventoryRow() {
        const clone = inventoryTemplate.content.cloneNode(true);
        clone.querySelector('input[name="inventories[][inventory_date]"]').name = `inventories[${inventoryIndex}][inventory_date]`;
        clone.querySelector('input[name="inventories[][stock]"]').name = `inventories[${inventoryIndex}][stock]`;
        inventoryContainer.appendChild(clone);
        inventoryIndex++;
    }

    document.getElementById('add-inventory-btn').addEventListener('click', addInventoryRow);

    inventoryContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-inventory-btn')) {
            e.target.closest('.inventory-item').remove();
        }
    });
});
</script>
@endpush
