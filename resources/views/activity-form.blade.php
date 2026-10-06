@extends('layout')
@section('title', 'Activity editor')
@section('content')
<a href="/workspace" class="back-link">← Back to workspace</a>
<h1>{{ isset($activity) && $activity ? 'Edit activity' : 'Plan a new activity' }}</h1>

<div class="row g-4 mt-2">
    <!-- Form Column -->
    <div class="col-lg-7">
        <form id="activity-form" class="panel form-grid" method="post" action="{{ isset($activity) && $activity ? '/workspace/' . $activity['activity_id'] . '/edit' : '/workspace/create' }}" enctype="multipart/form-data">
            @csrf
        
            <div class="wide">
                <label for="title">Activity title</label>
                <input id="title" class="form-control" name="title" required maxlength="160" value="{{ old('title', $activity['title'] ?? '') }}">
            </div>
           
            <div class="wide">
                <label for="description">Description</label>
                <textarea id="description" class="form-control" name="description" required maxlength="5000" rows="4">{{ old('description', $activity['description'] ?? '') }}</textarea>
            </div>

            <div class="wide">
                <label for="image">Banner image file</label>
                <input id="image" class="form-control" type="file" name="image" accept="image/*" onchange="previewImage(event)">
            </div>

            <div>
                <label for="category_id">Category</label>
                <select id="category_id" class="form-select" name="category_id" required onchange="updatePreviewCategory()">
                    @foreach($categories as $c)
                        @if($c['is_active'])
                            <option value="{{ $c['category_id'] }}" data-name="{{ $c['name'] }}" @selected(old('category_id', $activity['category_id'] ?? '') === $c['category_id'])>{{ $c['name'] }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div>
                <label for="venue">Venue</label>
                <input id="venue" class="form-control" name="venue" required maxlength="200" value="{{ old('venue', $activity['venue'] ?? '') }}">
            </div>
           
            @foreach(['start_at' => 'Start time', 'end_at' => 'End time', 'cutoff_at' => 'Registration deadline'] as $field => $label)
            <div>
                <label for="{{ $field }}">{{ $label }} (PHT)</label>
                <input class="form-control" id="{{ $field }}" type="datetime-local" name="{{ $field }}" required value="{{ old($field, isset($activity[$field]) ? \Carbon\Carbon::parse($activity[$field])->timezone(config('app.timezone'))->format('Y-m-d\TH:i') : '') }}">
            </div>
            @endforeach

            <div>
                <label for="capacity">Capacity</label>
                <input id="capacity" class="form-control" type="number" name="capacity" min="1" max="10000" required value="{{ old('capacity', $activity['capacity'] ?? 20) }}">
            </div>

            <div>
                <label for="is_free">Payment</label>
                <select class="form-select" id="is_free" name="is_free" data-payment-mode onchange="toggleFeeVisibility()">
                    <option value="1" @selected((string)old('is_free', ($activity['is_free'] ?? true) ? 1 : 0) === '1')>Free activity</option>
                    <option value="0" @selected((string)old('is_free', ($activity['is_free'] ?? true) ? 1 : 0) === '0')>Cash payment required</option>
                </select>
            </div>

            <div id="fee-wrapper">
                <label for="fee">Cash fee (PHP)</label>
                <input id="fee" class="form-control" type="number" name="fee" min="0" max="100000" step="0.01" value="{{ old('fee', $activity['fee'] ?? 0) }}" data-payment-fee>
            </div>
       
            <div>
                <label for="status">Status</label>
                <select class="form-select" id="status" name="status" onchange="updatePreviewStatus()">
                    @foreach(isset($activity) && $activity ? ['draft', 'open', 'completed', 'cancelled', 'archived'] : ['draft', 'open'] as $status)
                        <option @selected(old('status', $activity['status'] ?? 'draft') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
       
            @if($role === 'admin')
            <div>
                <label for="coordinator_id">Assigned coordinator</label>
                <select class="form-select" id="coordinator_id" name="coordinator_id" required>
                    @foreach($coordinators as $c)
                        <option value="{{ $c['user_id'] }}" @selected(old('coordinator_id', $activity['coordinator_id'] ?? '') === $c['user_id'])>{{ $c['full_name'] }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="wide">
                <label for="requirements">Requirements / what to bring</label>
                <textarea class="form-control" id="requirements" name="requirements" maxlength="2000">{{ old('requirements', $activity['requirements'] ?? '') }}</textarea>
            </div>

            <div class="wide">
                <button class="btn btn-primary">Save activity</button>
            </div>
        </form>
    </div>

    <!-- Live Preview Column -->
    <div class="col-lg-5">
        <div class="sticky-top" style="top: 20px;">
            <h6 class="text-muted fw-bold mb-2">Senior Portal Preview</h6>
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-light">
                @php
                    $existingImg = $activity['image_url'] ?? $activity['image'] ?? null;
                    $previewSrc = $existingImg 
                        ? (str_starts_with($existingImg, 'data:') ? $existingImg : (filter_var($existingImg, FILTER_VALIDATE_URL) ? $existingImg : asset('storage/' . $existingImg))) 
                        : 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=80';
                @endphp
                <img id="preview-img" src="{{ $previewSrc }}" class="card-img-top" style="height: 140px; object-fit: cover;" alt="Preview Banner">
               
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span id="preview-category" class="badge bg-secondary mb-1">General</span>
                        <span id="preview-status" class="badge bg-success">Open</span>
                    </div>
                    <h3 id="preview-title" class="fs-6 fw-bold text-dark mb-1">Activity title</h3>
                    <p id="preview-desc" class="text-muted small mb-2">Description will appear here...</p>
                    <div class="small text-secondary mb-3">
                        <div><strong>Schedule:</strong> <span id="preview-schedule">TBD</span></div>
                        <div><strong>Venue:</strong> <span id="preview-venue">Venue location</span></div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm fw-bold py-2 w-100 disabled">View details & enroll</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFeeVisibility() {
    const isFree = document.getElementById('is_free').value === '1';
    const feeWrapper = document.getElementById('fee-wrapper');
    const feeInput = document.getElementById('fee');
    if (isFree) {
        feeWrapper.style.display = 'none';
        feeInput.value = '0';
    } else {
        feeWrapper.style.display = '';
    }
}

function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('preview-img').src = reader.result;
    };
    if(event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}

function updatePreviewCategory() {
    const select = document.getElementById('category_id');
    const opt = select.options[select.selectedIndex];
    document.getElementById('preview-category').textContent = opt ? opt.text : 'General';
}

function updatePreviewStatus() {
    const status = document.getElementById('status').value;
    const badge = document.getElementById('preview-status');
    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    badge.className = 'badge ' + (status.toLowerCase() === 'open' ? 'bg-success' : 'bg-warning text-dark');
}

document.addEventListener('DOMContentLoaded', () => {
    toggleFeeVisibility();
    updatePreviewCategory();
    updatePreviewStatus();

    // Live text binding
    document.getElementById('title').addEventListener('input', e => document.getElementById('preview-title').textContent = e.target.value || 'Activity title');
    document.getElementById('description').addEventListener('input', e => document.getElementById('preview-desc').textContent = e.target.value || 'Description will appear here...');
    document.getElementById('venue').addEventListener('input', e => document.getElementById('preview-venue').textContent = e.target.value || 'Venue location');
    document.getElementById('start_at').addEventListener('input', e => {
        if(e.target.value) {
            const d = new Date(e.target.value);
            document.getElementById('preview-schedule').textContent = d.toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) + ' • ' + d.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});
        } else {
            document.getElementById('preview-schedule').textContent = 'TBD';
        }
    });

    // Trigger initial values if editing
    document.getElementById('title').dispatchEvent(new Event('input'));
    document.getElementById('description').dispatchEvent(new Event('input'));
    document.getElementById('venue').dispatchEvent(new Event('input'));
    document.getElementById('start_at').dispatchEvent(new Event('input'));
});
</script>
@endsection