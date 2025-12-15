@csrf
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="productName" class="form-label">产品名称</label>
        <input type="text" class="form-control" id="productName" name="product_name" value="{{ old('product_name', $product->name ?? '') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="scenicName" class="form-label">景区名称</label>
        <input type="text" class="form-control" id="scenicName" name="scenic_name" value="{{ old('scenic_name', $product->scenic_name ?? '') }}">
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="salesStartDate" class="form-label">销售开始日期</label>
        <input type="date" class="form-control" id="salesStartDate" name="sales_start_date" value="{{ old('sales_start_date', $product->sales_start_date ?? '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="salesEndDate" class="form-label">销售结束日期</label>
        <input type="date" class="form-control" id="salesEndDate" name="sales_end_date" value="{{ old('sales_end_date', $product->sales_end_date ?? '') }}">
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="verificationStartDate" class="form-label">核销开始日期</label>
        <input type="date" class="form-control" id="verificationStartDate" name="verification_start_date" value="{{ old('verification_start_date', $product->verification_start_date ?? '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="verificationEndDate" class="form-label">核销结束日期</label>
        <input type="date" class="form-control" id="verificationEndDate" name="verification_end_date" value="{{ old('verification_end_date', $product->verification_end_date ?? '') }}">
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="basePrice" class="form-label">基础价格</label>
        <div class="input-group">
            <span class="input-group-text">¥</span>
            <input type="number" class="form-control" id="basePrice" name="base_price" step="0.01" value="{{ old('base_price', $product->base_price ?? '') }}">
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <label for="usageDays" class="form-label">使用天数</label>
        <input type="number" class="form-control" id="usageDays" name="usage_days" value="{{ old('usage_days', $product->usage_days ?? '1') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="productType" class="form-label">产品类型</label>
        <select class="form-select" id="productType" name="product_type">
            <option value="1" {{ old('product_type', $product->product_type ?? '') == 1 ? 'selected' : '' }}>门票</option>
            <option value="2" {{ old('product_type', $product->product_type ?? '') == 2 ? 'selected' : '' }}>酒店</option>
            <option value="3" {{ old('product_type', $product->product_type ?? '') == 3 ? 'selected' : '' }}>线路</option>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <label for="otaPlatform" class="form-label">OTA平台</label>
        <select class="form-select" id="otaPlatform" name="ota_platform">
            <option value="fliggy" {{ old('ota_platform', $product->ota_platform ?? '') == 'fliggy' ? 'selected' : '' }}>飞猪</option>
            <option value="ctrip" {{ old('ota_platform', $product->ota_platform ?? '') == 'ctrip' ? 'selected' : '' }}>携程</option>
            <option value="hengdian" {{ old('ota_platform', $product->ota_platform ?? '') == 'hengdian' ? 'selected' : '' }}>横店</option>
        </select>
    </div>
</div>
