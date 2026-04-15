@if(View::hasSection('breadcrumb') || View::hasSection('page_title'))
<div id="page-header" class="page-header page-header-light" style="border-bottom:1px solid #e2e8f0;background:#fff;padding:10px 20px;">
    <div class="d-flex align-items-center justify-content-between">
        <h5 style="margin:0;font-size:15px;font-weight:700;color:#1e293b;">
            @yield('page_title')
        </h5>
        <div style="font-size:12px;color:#64748b;">
            <a href="{{ route('dashboard') }}" style="color:#4f46e5;text-decoration:none;">Dashboard</a>
            @hasSection('breadcrumb')
                <span style="margin:0 6px;color:#cbd5e1;">/</span>
                @yield('breadcrumb')
            @endif
        </div>
    </div>
</div>
@endif
