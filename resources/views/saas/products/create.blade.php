@extends('saas.layouts.app')

@section('title', '新增产品')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        .page-header {
            background-color: var(--nb-content-bg-color);
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,.03), 0 1px 6px -1px rgba(0,0,0,.02), 0 2px 4px 0 rgba(0,0,0,.02);
        }
        .page-header h1 { font-size: 20px; font-weight: 600; margin-bottom: 4px; }
        .page-header .breadcrumb-item a { text-decoration: none; color: var(--nb-primary-color); }
        .content-card {
            background-color: var(--nb-content-bg-color);
            border-radius: 8px;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,.03), 0 1px 6px -1px rgba(0,0,0,.02), 0 2px 4px 0 rgba(0,0,0,.02);
        }
        .card-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            border-bottom: 1px solid var(--nb-border-color);
        }
        .card-header-actions h5 { margin: 0; font-size: 16px; font-weight: 600; }
        .card-body-content { padding: 24px; }
        .main-footer-actions {
            position: sticky;
            bottom: 0;
            background-color: #ffffff;
            padding: 16px 24px;
            border-top: 1px solid var(--nb-border-color);
            box-shadow: 0 -2px 4px 0 rgba(0,0,0,.05);
            display: flex;
            justify-content: flex-end;
            margin: 24px -24px -24px -24px;
        }
        .choices__inner {
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: .375rem;
            min-height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
        }
        .choices__list--multiple .choices__item {
            background-color: var(--nb-primary-color);
            border: 1px solid var(--nb-primary-color);
            border-radius: .375rem;
        }
    </style>
@endpush

@section('content')
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0 mb-1">
            <li class="breadcrumb-item"><a href="{{ route('saas.products.index') }}">产品管理</a></li>
            <li class="breadcrumb-item active" aria-current="page">新增产品</li>
        </ol>
    </nav>
    <h1>新增产品</h1>
</div>

<form action="{{ route('saas.products.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-8">
            <div class="content-card mt-4">
                <div class="card-header-actions"><h5>基本信息</h5></div>
                <div class="card-body-content">
                    {{-- Basic Info Fields --}}
                </div>
            </div>

            <div class="content-card mt-4">
                <div class="card-header-actions"><h5>酒店及房型配置</h5></div>
                <div class="card-body-content">
                    <div class="mb-3">
                        <label for="hotel_select" class="form-label">选择酒店</label>
                        <select id="hotel_select" multiple>
                            @foreach($hotels as $hotel)
                                <option value="{{ $hotel->id }}" data-name="{{$hotel->hotel_name}}">{{ $hotel->hotel_name }} ({{$hotel->hotel_code}})</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="room-type-cards-container"></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Attributes Card --}}
        </div>
    </div>

    <div class="main-footer-actions">
        <a href="{{ route('saas.products.index') }}" class="btn btn-secondary">取消</a>
        <button type="submit" class="btn btn-primary ms-2">保存产品</button>
    </div>
</form>

<template id="room-type-card-template">
    <div class="card mb-3 room-type-card" data-hotel-id="">
        <div class="card-header"></div>
        <div class="card-body">
            {{-- Checkboxes will be inserted here --}}
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const hotelSelectElement = document.getElementById('hotel_select');
    const cardsContainer = document.getElementById('room-type-cards-container');
    const cardTemplate = document.getElementById('room-type-card-template');

    const hotelChoices = new Choices(hotelSelectElement, {
        removeItemButton: true,
        placeholder: true,
        placeholderValue: '选择一个或多个酒店...',
    });

    hotelSelectElement.addEventListener('change', function(event) {
        // Clear existing cards
        cardsContainer.innerHTML = '';

        const selectedHotels = hotelChoices.getValue(true);

        selectedHotels.forEach(hotel => {
            const hotelId = hotel.value;
            const hotelName = hotel.label;

            const cardClone = cardTemplate.content.cloneNode(true);
            const card = cardClone.querySelector('.room-type-card');
            card.dataset.hotelId = hotelId;
            card.querySelector('.card-header').textContent = hotelName;
            const cardBody = card.querySelector('.card-body');

            cardsContainer.appendChild(card);

            // Fetch room types for this hotel
            fetch(`/saas/api/hotels/${hotelId}/roomtypes`)
                .then(response => response.json())
                .then(roomTypes => {
                    if (roomTypes.length > 0) {
                        roomTypes.forEach(roomType => {
                            const div = document.createElement('div');
                            div.className = 'form-check';
                            div.innerHTML = `
                                <input class="form-check-input" type="checkbox" name="hotels[${hotelId}][roomtype_ids][]" value="${roomType.id}" id="roomtype_${roomType.id}">
                                <label class="form-check-label" for="roomtype_${roomType.id}">
                                    ${roomType.roomtype}
                                </label>
                            `;
                            cardBody.appendChild(div);
                        });
                    } else {
                        cardBody.innerHTML = '<p class="text-muted">该酒店下没有配置房型。</p>';
                    }
                });
        });
    });
});
</script>
@endpush
