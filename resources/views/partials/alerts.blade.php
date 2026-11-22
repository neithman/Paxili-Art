{{-- Flash messages set with ->with('success', '...') / ->with('error', '...') / ->with('info', '...') --}}
@foreach (['success', 'error', 'info'] as $type)
    @if (session($type))
        <div class="alert alert-{{ $type }}">
            <span>{{ session($type) }}</span>
            <button type="button" class="alert-close" aria-label="Close">&times;</button>
        </div>
    @endif
@endforeach

{{-- Validation errors (shown as one box at the top) --}}
@if ($errors->any())
    <div class="alert alert-error">
        <span>Please fix the highlighted fields: {{ $errors->first() }}</span>
        <button type="button" class="alert-close" aria-label="Close">&times;</button>
    </div>
@endif
