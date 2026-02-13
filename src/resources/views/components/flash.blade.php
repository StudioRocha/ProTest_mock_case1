@if (session('success'))
<div class="c-alert c-alert--success">{{ session("success") }}</div>
@endif @if (session('error'))
<div class="c-alert c-alert--error">{{ session("error") }}</div>
@endif @if (session('info'))
<div class="c-alert c-alert--info">{{ session("info") }}</div>
@endif @if (session('status'))
<div class="c-alert c-alert--info">{{ session("status") }}</div>
@endif @if ($errors->any())
<div class="c-alert c-alert--error">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    @endif
</div>
