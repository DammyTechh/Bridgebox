{{--
    USB Content Panel — reusable partial.

    Variables:
        $variant       'admin' | 'teacher' | 'student'   (default 'admin')
        $showLibrary   true | false                       (default true)
        $title         string                             (default localized)

    For admin & teacher:
        - Shows detected device storage info (drive label, path, size, file-type counts)
        - Shows available content library grouped by folder type
        - Copy/import controls are intentionally removed — use Create Lesson to import content.
    For student:
        - Library only (read-only).
--}}
@php
    $variant     = $variant     ?? 'admin';
    $showLibrary = $showLibrary ?? true;
    $title       = $title       ?? __('Flash Drive Content');
    $isStudent   = $variant === 'student';
@endphp

<section class="panel">
    <div class="panel-header">
        <h4>{{ $title }}</h4>
        <span class="badge blue">{{ $isStudent ? __('Library') : __('Storage') }}</span>
    </div>

    <div class="panel-body">
        <div class="usb-panel"
             data-usb-panel
             data-url-drives="{{ route('usb.drives') }}"
             data-url-list="{{ route('usb.list') }}"
             data-csrf="{{ csrf_token() }}">

            @unless ($isStudent)
            {{-- ── Detected device storage ─────────────────────────────── --}}
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;">
                    <p class="usb-section-title">
                        <i class="fa-solid fa-hard-drive"></i>
                        {{ __('Detected device storage') }}
                    </p>
                    <button type="button" class="btn ghost btn-small" data-usb-refresh>
                        <i class="fa-solid fa-arrows-rotate"></i> {{ __('Refresh') }}
                    </button>
                </div>

                {{-- Drive cards rendered by JS — shows label, path, size, file-type tags --}}
                <div class="usb-drives" data-usb-drives></div>

                <div class="usb-empty" data-usb-empty>
                    <i class="fa-solid fa-circle-info"></i>
                    {{ __('No USB drive detected. Plug a flash drive into the Raspberry Pi and click Refresh.') }}
                </div>
            </div>
            @endunless

            {{-- ── Available content on flash ─────────────────────────── --}}
            @if ($showLibrary)
            <div>
                <p class="usb-section-title" style="margin-top:.25rem;">
                    <i class="fa-solid fa-folder-open"></i>
                    {{ __('Available content') }}
                </p>
                <div class="usb-library-grid" data-usb-library></div>
                <div class="usb-empty" data-usb-library-empty hidden>
                    <i class="fa-solid fa-box-open"></i>
                    {{ __('No content imported yet.') }}
                </div>
            </div>
            @endif

        </div>
    </div>
</section>

@push('scripts')
    <link rel="stylesheet" href="{{ asset('assets/css/usb-import.css') }}">
    <script src="{{ asset('assets/js/usb-import.js') }}" defer></script>
@endpush
