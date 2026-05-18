@extends('layouts.master')
@section('page_title', 'Library')
@section('content')

{{-- ── Stats Row ────────────────────────────────────────────────────────── --}}
<div class="row mb-4">
    @php
    $statCards = [
        ['label'=>'Total Books',      'value'=>$stats['total_books'],      'icon'=>'bi-book-fill',           'color'=>'#4f46e5','bg'=>'#ede9fe'],
        ['label'=>'Total Copies',     'value'=>$stats['total_copies'],     'icon'=>'bi-stack',               'color'=>'#0891b2','bg'=>'#cffafe'],
        ['label'=>'Available',        'value'=>$stats['available_copies'], 'icon'=>'bi-check-circle-fill',   'color'=>'#10b981','bg'=>'#d1fae5'],
        ['label'=>'Issued',           'value'=>$stats['issued_copies'],    'icon'=>'bi-arrow-up-right-circle-fill','color'=>'#f59e0b','bg'=>'#fef3c7'],
        ['label'=>'Pending Requests', 'value'=>$stats['pending_requests'], 'icon'=>'bi-hourglass-split',     'color'=>'#8b5cf6','bg'=>'#ede9fe'],
        ['label'=>'Overdue',          'value'=>$stats['overdue'],          'icon'=>'bi-exclamation-triangle-fill','color'=>'#ef4444','bg'=>'#fee2e2'],
    ];
    @endphp
    @foreach($statCards as $sc)
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.05);display:flex;align-items:center;gap:12px;">
            <div style="background:{{ $sc['bg'] }};border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi {{ $sc['icon'] }}" style="font-size:18px;color:{{ $sc['color'] }};"></i>
            </div>
            <div>
                <div style="font-size:22px;font-weight:800;color:#1e293b;line-height:1;">{{ $sc['value'] }}</div>
                <div style="font-size:11px;color:#64748b;font-weight:500;margin-top:2px;">{{ $sc['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;flex-wrap:wrap;gap:10px;">

    {{-- Search --}}
    <form action="{{ route('library.index') }}" method="GET" style="display:flex;gap:6px;flex:1;min-width:200px;">
        <input type="hidden" name="book_type"   value="{{ $typeFilter }}">
        <input type="hidden" name="my_class_id" value="{{ $classFilter }}">
        <input type="hidden" name="available"   value="{{ $availFilter }}">
        <div style="position:relative;flex:1;">
            <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Search title, author, ISBN…"
                   style="width:100%;padding:8px 12px 8px 32px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;">
        </div>
        <button type="submit" style="background:#4f46e5;color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;">Search</button>
        @if($search || $typeFilter || $classFilter || $availFilter)
        <a href="{{ route('library.index') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:8px 12px;font-size:13px;text-decoration:none;">Clear</a>
        @endif
    </form>

    {{-- Filters --}}
    <form action="{{ route('library.index') }}" method="GET" style="display:flex;gap:6px;flex-wrap:wrap;">
        <input type="hidden" name="search" value="{{ $search }}">
        <select name="book_type" onchange="this.form.submit()" style="border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:12px;color:#475569;background:#fff;cursor:pointer;">
            <option value="">All Types</option>
            @foreach($bookTypes as $t)
            <option value="{{ $t }}" {{ $typeFilter === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
        <select name="available" onchange="this.form.submit()" style="border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:12px;color:#475569;background:#fff;cursor:pointer;">
            <option value="">All Availability</option>
            <option value="available"   {{ $availFilter === 'available'   ? 'selected' : '' }}>Available</option>
            <option value="unavailable" {{ $availFilter === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
        </select>
    </form>

    {{-- Actions --}}
    <div style="display:flex;gap:8px;margin-left:auto;">
        <a href="{{ route('library.requests') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-clock-history"></i> Requests
            @if($stats['pending_requests'] > 0)
            <span style="background:#ef4444;color:#fff;border-radius:10px;font-size:10px;padding:1px 6px;">{{ $stats['pending_requests'] }}</span>
            @endif
        </a>
        @if(Qs::userIsTeamSA())
        <button onclick="document.getElementById('bulk-modal').style.display='flex'"
                style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-cloud-upload"></i> Bulk Import
        </button>
        <a href="{{ route('library.create') }}" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(79,70,229,.3);">
            <i class="bi bi-plus-lg"></i> Add Book
        </a>
        @endif
    </div>
</div>

{{-- ── Book Grid ────────────────────────────────────────────────────────── --}}
@if($books->isEmpty())
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:60px 20px;text-align:center;">
    <i class="bi bi-book" style="font-size:48px;color:#cbd5e1;display:block;margin-bottom:16px;"></i>
    <h5 style="color:#475569;font-weight:600;margin-bottom:8px;">No books found</h5>
    <p style="color:#94a3b8;font-size:13px;margin:0;">
        @if($search) No results for "{{ $search }}". @else The library is empty. @endif
    </p>
    @if(Qs::userIsTeamSA())
    <a href="{{ route('library.create') }}" style="display:inline-block;margin-top:16px;background:#4f46e5;color:#fff;border-radius:8px;padding:8px 20px;font-size:13px;font-weight:600;text-decoration:none;">
        <i class="bi bi-plus-lg mr-1"></i>Add First Book
    </a>
    @endif
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-bottom:20px;">
    @foreach($books as $b)
    @php $avail = $b->available_copies; @endphp
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);transition:box-shadow .15s;"
         onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'"
         onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.05)'">

        {{-- Cover --}}
        <div style="height:120px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
            <img src="{{ $b->cover_url }}" alt="{{ $b->name }}"
                 style="width:80px;height:80px;object-fit:cover;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.15);"
                 onerror="this.style.display='none'">
            {{-- Availability badge --}}
            <div style="position:absolute;top:8px;right:8px;">
                <span style="background:{{ $avail > 0 ? ($avail <= 2 ? '#fef3c7' : '#d1fae5') : '#fee2e2' }};color:{{ $avail > 0 ? ($avail <= 2 ? '#92400e' : '#065f46') : '#991b1b' }};font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;">
                    {{ $avail > 0 ? $avail.' avail.' : 'Out' }}
                </span>
            </div>
            @if($b->book_type)
            <div style="position:absolute;top:8px;left:8px;">
                <span style="background:rgba(0,0,0,.5);color:#fff;font-size:9px;font-weight:600;padding:2px 7px;border-radius:20px;">{{ $b->book_type }}</span>
            </div>
            @endif
        </div>

        {{-- Info --}}
        <div style="padding:12px;">
            <div style="font-weight:700;font-size:13px;color:#1e293b;line-height:1.3;margin-bottom:4px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $b->name }}</div>
            @if($b->author)
            <div style="font-size:11px;color:#64748b;margin-bottom:6px;">by {{ $b->author }}</div>
            @endif
            @if($b->isbn)
            <div style="font-size:10px;color:#94a3b8;margin-bottom:6px;font-family:monospace;">ISBN: {{ $b->isbn }}</div>
            @endif
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <span style="font-size:11px;color:#64748b;">{{ $b->my_class->name ?? 'General' }}</span>
                <span style="font-size:11px;color:#94a3b8;">{{ $b->total_copies }} copies</span>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:6px;">
                @if(Qs::userIsTeamSA())
                <a href="{{ route('library.edit', $b->id) }}"
                   style="flex:1;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:7px;padding:6px;font-size:11px;font-weight:600;text-decoration:none;text-align:center;">
                    <i class="bi bi-pencil"></i>
                </a>
                <form method="POST" action="{{ route('library.destroy', $b->id) }}" class="d-inline" style="flex:0;">
                    @csrf @method('DELETE')
                    <button onclick="return confirm('Delete this book?')"
                            style="background:#fee2e2;border:1px solid #fecaca;color:#ef4444;border-radius:7px;padding:6px 8px;font-size:11px;font-weight:600;cursor:pointer;">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ route('library.request') }}" style="flex:1;">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $b->id }}">
                    <button {{ $avail < 1 ? 'disabled' : '' }}
                            style="width:100%;background:{{ $avail > 0 ? 'linear-gradient(135deg,#4f46e5,#7c3aed)' : '#f1f5f9' }};color:{{ $avail > 0 ? '#fff' : '#94a3b8' }};border:none;border-radius:7px;padding:6px;font-size:11px;font-weight:600;cursor:{{ $avail > 0 ? 'pointer' : 'not-allowed' }};">
                        <i class="bi bi-book{{ $avail > 0 ? '' : '-x' }}"></i>
                        {{ $avail > 0 ? 'Borrow' : 'Unavailable' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
<div style="display:flex;justify-content:center;">{{ $books->links() }}</div>
@endif

{{-- ── Bulk Import Modal ────────────────────────────────────────────────── --}}
@if(Qs::userIsTeamSA())
<div id="bulk-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:680px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:20px 24px;border-radius:16px 16px 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <i class="bi bi-cloud-upload" style="font-size:20px;color:#fff;"></i>
                <span style="color:#fff;font-weight:700;font-size:16px;">Bulk Import Books</span>
            </div>
            <button onclick="document.getElementById('bulk-modal').style.display='none'"
                    style="background:rgba(255,255,255,.2);border:none;color:#fff;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:16px;">×</button>
        </div>
        <div style="padding:24px;">
            <div style="background:#dbeafe;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#1e40af;">
                <i class="bi bi-info-circle mr-2"></i>
                Upload a CSV file to add multiple books at once.
                <a href="{{ route('library.bulk.template') }}" style="font-weight:700;color:#1d4ed8;margin-left:8px;">
                    <i class="bi bi-download mr-1"></i>Download Template
                </a>
            </div>

            <div style="background:#f8fafc;border-radius:8px;padding:12px;margin-bottom:16px;overflow-x:auto;">
                <table style="width:100%;font-size:11px;border-collapse:collapse;">
                    <thead><tr style="background:#f1f5f9;">
                        @foreach(['title','author','isbn','publisher','published_year','book_type','total_copies','due_days','location','description'] as $col)
                        <th style="padding:6px 8px;text-align:left;color:#64748b;font-weight:600;white-space:nowrap;">{{ $col }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody><tr>
                        <td style="padding:6px 8px;color:#1e293b;">Math Grade 5</td>
                        <td style="padding:6px 8px;color:#94a3b8;">Abebe G.</td>
                        <td style="padding:6px 8px;color:#94a3b8;">978…</td>
                        <td style="padding:6px 8px;color:#94a3b8;">MoE</td>
                        <td style="padding:6px 8px;color:#94a3b8;">2022</td>
                        <td style="padding:6px 8px;color:#94a3b8;">Textbook</td>
                        <td style="padding:6px 8px;color:#1e293b;">10</td>
                        <td style="padding:6px 8px;color:#94a3b8;">14</td>
                        <td style="padding:6px 8px;color:#94a3b8;">Shelf A-1</td>
                        <td style="padding:6px 8px;color:#94a3b8;">Grade 5</td>
                    </tr></tbody>
                </table>
            </div>

            <form id="bulk-book-form" method="post" enctype="multipart/form-data" action="{{ route('library.bulk.import') }}">
                @csrf
                <div style="display:flex;gap:10px;align-items:flex-end;margin-bottom:12px;">
                    <div style="flex:1;">
                        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">CSV File <span style="color:#ef4444;">*</span></label>
                        <input type="file" name="csv_file" id="bulk-book-csv" accept=".csv,text/csv"
                               style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;" required>
                        <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Max 5MB. UTF-8 CSV only.</div>
                    </div>
                    <button type="button" id="bulk-book-preview-btn"
                            style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
                        <i class="bi bi-eye mr-1"></i>Preview
                    </button>
                </div>

                <div id="bulk-book-preview-area" style="display:none;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-weight:600;font-size:13px;color:#1e293b;">Preview</span>
                        <span id="bulk-book-row-count" style="background:#ede9fe;color:#4f46e5;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;"></span>
                    </div>
                    <div style="max-height:220px;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;">
                        <table style="width:100%;font-size:12px;border-collapse:collapse;">
                            <thead id="bulk-book-head" style="background:#f8fafc;position:sticky;top:0;"></thead>
                            <tbody id="bulk-book-body"></tbody>
                        </table>
                    </div>
                    <div id="bulk-book-errors" class="mt-2"></div>
                </div>

                <div style="display:flex;gap:8px;">
                    <button type="submit" id="bulk-book-submit" disabled
                            style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;opacity:.5;">
                        <i class="bi bi-cloud-upload mr-1"></i>Import Books
                    </button>
                    <button type="button" onclick="document.getElementById('bulk-modal').style.display='none'"
                            style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:10px 16px;font-size:13px;font-weight:600;cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </form>
            <div id="bulk-book-result" style="display:none;margin-top:12px;"></div>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
@if(Qs::userIsTeamSA())
<script>
$(function () {
    // Close modal on backdrop click
    $('#bulk-modal').on('click', function(e){ if(e.target===this) $(this).hide(); });

    $('#bulk-book-csv').on('change', function () {
        $('#bulk-book-preview-area').hide();
        $('#bulk-book-submit').prop('disabled', true).css('opacity', '.5');
        $('#bulk-book-head, #bulk-book-body, #bulk-book-errors').empty();
    });

    $('#bulk-book-preview-btn').on('click', function () {
        var file = $('#bulk-book-csv')[0].files[0];
        if (!file) { flash({ msg: 'Please select a CSV file first.', type: 'warning' }); return; }
        var reader = new FileReader();
        reader.onload = function (e) {
            var lines = e.target.result.split(/\r?\n/).filter(function (l) { return l.trim(); });
            if (lines.length < 2) { flash({ msg: 'CSV must have a header row and at least one data row.', type: 'warning' }); return; }
            var headers = lines[0].split(',').map(function (h) { return h.trim(); });
            var $head = $('<tr>');
            headers.forEach(function (h) { $head.append($('<th style="padding:6px 8px;background:#f8fafc;font-size:11px;font-weight:600;color:#64748b;white-space:nowrap;">').text(h)); });
            $head.append('<th style="padding:6px 8px;background:#f8fafc;font-size:11px;font-weight:600;color:#64748b;">Status</th>');
            $('#bulk-book-head').html($head);
            var $body = $('#bulk-book-body').empty();
            var errors = [], validRows = 0;
            for (var i = 1; i < Math.min(lines.length, 51); i++) {
                var cols = lines[i].split(',').map(function (c) { return c.trim(); });
                var rowErrors = [];
                var titleIdx = headers.indexOf('title');
                var copiesIdx = headers.indexOf('total_copies');
                if (titleIdx >= 0 && !cols[titleIdx]) rowErrors.push('Title required');
                if (copiesIdx >= 0 && cols[copiesIdx] && isNaN(parseInt(cols[copiesIdx]))) rowErrors.push('Copies must be a number');
                var statusHtml = rowErrors.length
                    ? '<span style="background:#fee2e2;color:#991b1b;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;">' + rowErrors.join(', ') + '</span>'
                    : '<span style="background:#d1fae5;color:#065f46;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;">✓ OK</span>';
                if (rowErrors.length) errors.push('Row ' + i + ': ' + rowErrors.join(', '));
                else validRows++;
                var $tr = $('<tr style="border-bottom:1px solid #f1f5f9;">');
                cols.forEach(function (c) { $tr.append($('<td style="padding:6px 8px;font-size:12px;color:#1e293b;">').text(c)); });
                $tr.append('<td style="padding:6px 8px;">' + statusHtml + '</td>');
                $body.append($tr);
            }
            if (lines.length > 51) $body.append('<tr><td colspan="' + (headers.length + 1) + '" style="padding:8px;text-align:center;color:#94a3b8;font-size:12px;">... and ' + (lines.length - 51) + ' more rows</td></tr>');
            $('#bulk-book-row-count').text((lines.length - 1) + ' rows');
            $('#bulk-book-preview-area').show();
            if (errors.length) {
                $('#bulk-book-errors').html('<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400e;margin-top:8px;"><strong>' + errors.length + ' row(s) have issues:</strong><ul style="margin:6px 0 0;padding-left:16px;">' + errors.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul></div>');
            }
            $('#bulk-book-submit').prop('disabled', validRows === 0).css('opacity', validRows === 0 ? '.5' : '1');
        };
        reader.readAsText(file);
    });

    $('#bulk-book-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#bulk-book-submit').prop('disabled', true).css('opacity','.7').html('<i class="bi bi-hourglass-split mr-1"></i>Importing…');
        $.ajax({ url: $(this).attr('action'), type: 'POST', data: new FormData(this), processData: false, contentType: false, dataType: 'json' })
        .done(function (r) {
            var cls = r.ok ? '#d1fae5' : '#fee2e2';
            var tcls = r.ok ? '#065f46' : '#991b1b';
            var html = '<div style="background:' + cls + ';border-radius:8px;padding:12px 16px;font-size:13px;color:' + tcls + ';"><strong>' + (r.ok ? '✓ Import Complete' : '✗ Import Failed') + '</strong><br>' + r.msg + '</div>';
            if (r.errors && r.errors.length) html += '<ul style="margin-top:8px;padding-left:16px;font-size:12px;color:#ef4444;">' + r.errors.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul>';
            $('#bulk-book-result').html(html).show();
            $btn.prop('disabled', false).css('opacity','1').html('<i class="bi bi-cloud-upload mr-1"></i>Import Books');
            if (r.ok) setTimeout(function () { location.reload(); }, 1500);
        })
        .fail(function (xhr) {
            $('#bulk-book-result').html('<div style="background:#fee2e2;border-radius:8px;padding:12px;font-size:13px;color:#991b1b;">Server error: ' + xhr.status + '</div>').show();
            $btn.prop('disabled', false).css('opacity','1').html('<i class="bi bi-cloud-upload mr-1"></i>Import Books');
        });
    });
});
</script>
@endif
@endsection
