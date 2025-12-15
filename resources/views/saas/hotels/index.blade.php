@extends('saas.layouts.app')

@section('title', '酒店资源管理')

@section('content')
<div class="content-card">
    <div class="card-header-actions">
        <h5>酒店资源管理</h5>
        <a href="{{ route('saas.hotels.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> 新增酒店</a>
    </div>

    <div class="card-body-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>酒店名称</th>
                        <th>酒店代码</th>
                        <th>所属景区</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($hotels as $hotel)
                        <tr>
                            <td>{{ $hotel->id }}</td>
                            <td>{{ $hotel->hotel_name }}</td>
                            <td>{{ $hotel->hotel_code }}</td>
                            <td>{{ $hotel->tourist->name ?? 'N/A' }}</td>
                            <td>{{ $hotel->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('saas.hotels.edit', $hotel) }}">编辑</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">没有找到任何酒店资源。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer-actions">
        {{ $hotels->links() }}
    </div>
</div>
@endsection
