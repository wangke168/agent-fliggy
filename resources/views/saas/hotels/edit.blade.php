@extends('saas.layouts.app')

@section('title', '编辑酒店资源')

@section('content')
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0 mb-1">
            <li class="breadcrumb-item"><a href="{{ route('saas.hotels.index') }}">酒店资源管理</a></li>
            <li class="breadcrumb-item active" aria-current="page">编辑酒店</li>
        </ol>
    </nav>
    <h1>编辑: {{ $hotel->hotel_name }}</h1>
</div>

<form action="{{ route('saas.hotels.update', $hotel) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-8">
            <div class="content-card mt-4">
                <div class="card-header-actions"><h5>酒店信息</h5></div>
                <div class="card-body-content">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">酒店名称</label>
                            <input type="text" name="hotel_name" class="form-control" value="{{ $hotel->hotel_name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">酒店代码</label>
                            <input type="text" name="hotel_code" class="form-control" value="{{ $hotel->hotel_code }}" required>
                        </div>
                    </div>
                     <div class="mb-3">
                        <label class="form-label">所属景区</label>
                        <select name="tourist_id" class="form-select" required>
                            @foreach($tourists as $tourist)
                                <option value="{{ $tourist->id }}" @if($hotel->tourist_id == $tourist->id) selected @endif>{{ $tourist->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="content-card mt-4">
                <div class="card-header-actions">
                    <h5>房型配置</h5>
                    <button type="button" id="add-room-btn" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> 添加房型</button>
                </div>
                <div class="card-body-content" id="room-types-container">
                    @foreach($hotel->roomTypes as $index => $roomType)
                    <div class="row align-items-end mb-2 room-type-item">
                        <div class="col">
                            <label class="form-label">房型名称</label>
                            <input type="text" name="room_types[{{$index}}][roomtype]" class="form-control" value="{{ $roomType->roomtype }}">
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-danger remove-room-btn"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="main-footer-actions">
        <a href="{{ route('saas.hotels.index') }}" class="btn btn-secondary">取消</a>
        <button type="submit" class="btn btn-primary ms-2">保存更改</button>
    </div>
</form>

<template id="room-type-template">
    <div class="row align-items-end mb-2 room-type-item">
        <div class="col">
            <label class="form-label">房型名称</label>
            <input type="text" name="room_types[][roomtype]" class="form-control">
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-danger remove-room-btn"><i class="bi bi-trash"></i></button>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('room-types-container');
    const template = document.getElementById('room-type-template');
    let roomIndex = {{ $hotel->roomTypes->count() }};

    function addRoomType() {
        const clone = template.content.cloneNode(true);
        clone.querySelector('input').name = `room_types[${roomIndex}][roomtype]`;
        container.appendChild(clone);
        roomIndex++;
    }

    document.getElementById('add-room-btn').addEventListener('click', addRoomType);

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-room-btn')) {
            e.target.closest('.room-type-item').remove();
        }
    });
});
</script>
@endpush
