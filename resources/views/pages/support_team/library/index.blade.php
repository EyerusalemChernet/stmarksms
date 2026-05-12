@extends('layouts.master')
@section('page_title', 'Library')
@section('content')
@include('partials.back_button')

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
        <h6 class="card-title mb-0">Book Inventory</h6>
        @if(Qs::userIsTeamSA())
        <div class="d-flex" style="gap:8px;">
            <a href="{{ route('library.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg mr-1"></i>Add Book
            </a>
            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#bulk-modal">
                <i class="bi bi-cloud-upload mr-1"></i>Bulk Import
            </button>
        </div>
        @endif
    </div>

    <div class="card-body p-0">
        @if(session('flash_success'))<div class="alert alert-success m-3">{{ session('flash_success') }}</div>@endif
        @if(session('flash_danger'))<div class="alert alert-danger m-3">{{ session('flash_danger') }}</div>@endif

        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr>
                    <th>#</th><th>Title</th><th>Author</th><th>Class</th>
                    <th>Type</th><th>Total</th><th>Available</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($books as $i => $b)
                <tr>
                    <td>{{ $books->firstItem() + $i }}</td>
                    <td>{{ $b->name }}</td>
                    <td>{{ $b->author ?? '-' }}</td>
                    <td>{{ $b->my_class->name ?? 'General' }}</td>
                    <td>{{ $b->book_type ?? '-' }}</td>
                    <td>{{ $b->total_copies }}</td>
                    <td>
                        @php $avail = $b->total_copies - $b->issued_copies; @endphp
                        <span class="badge badge-{{ $avail > 0 ? 'success' : 'danger' }}">{{ $avail }}</span>
                    </td>
                    <td>
                        @if(Qs::userIsTeamSA())
                        <a href="{{ route('library.edit', $b->id) }}" class="btn btn-xs btn-warning">Edit</a>
                        <form method="POST" action="{{ route('library.destroy', $b->id) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger" onclick="return confirm('Delete this book?')">Del</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('library.request') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="book_id" value="{{ $b->id }}">
                            <button class="btn btn-xs btn-info" {{ $avail < 1 ? 'disabled' : '' }}>Borrow</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $books->links() }}</div>
    </div>
</div>

{{-- ── Bulk Import Modal ─────────────────────────────────────────────────── --}}
@if(Qs::userIsTeamSA())
<div class="modal fade" id="bulk-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#4f46e5;color:#fff;">
                <h5 class="modal-title"><i class="bi bi-cloud-upload mr-2"></i>Bulk Import Books</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">

                <div class="alert alert-info border-0 mb-3">
                    <i class="bi bi-info-circle mr-2"></i>
                    Upload a CSV file to add multiple books at once.
                    <a href="{{ route('library.bulk.template') }}" class="font-weight-bold ml-2">
                        <i class="bi bi-download mr-1"></i>Download CSV Template
                    </a>
                </div>

                {{-- Column reference --}}
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered" style="font-size:12px;">
                        <thead class="thead-light">
                            <tr><th>title</th><th>author</th><th>book_type</th><th>total_copies</th><th>location</th><th>description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Mathematics Grade 5</td><td>Abebe Girma</td><td>Textbook</td>
                                <td>10</td><td>Shelf A-1</td><td>Grade 5 math textbook</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <form id="bulk-book-form" method="post" enctype="multipart/form-data" action="{{ route('library.bulk.import') }}">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="font-weight-semibold">Select CSV File <span class="text-danger">*</span></label>
                                <input type="file" name="csv_file" id="bulk-book-csv" accept=".csv,text/csv" class="form-control" required>
                                <small class="text-muted">Max 5MB. UTF-8 encoded CSV only.</small>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <button type="button" id="bulk-book-preview-btn" class="btn btn-info btn-block">
                                    <i class="bi bi-eye mr-1"></i>Preview CSV
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="bulk-book-preview-area" style="display:none;" class="mb-3">
                        <h6 class="font-weight-semibold mb-2">
                            Preview <span id="bulk-book-row-count" class="badge badge-primary ml-1"></span>
                        </h6>
                        <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="thead-dark" id="bulk-book-head"></thead>
                                <tbody id="bulk-book-body"></tbody>
                            </table>
                        </div>
                        <div id="bulk-book-errors" class="mt-2"></div>
                    </div>

                    <div class="d-flex" style="gap:8px;">
                        <button type="submit" id="bulk-book-submit" class="btn btn-success" disabled>
                            <i class="bi bi-cloud-upload mr-1"></i>Import Books
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>

                <div id="bulk-book-result" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
@if(Qs::userIsTeamSA())
<script>
$(function () {
    $('#bulk-book-csv').on('change', function () {
        $('#bulk-book-preview-area').hide();
        $('#bulk-book-submit').prop('disabled', true);
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
            headers.forEach(function (h) { $head.append($('<th>').text(h)); });
            $head.append('<th>Status</th>');
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
                var statusCell = rowErrors.length
                    ? '<td><span class="badge badge-danger">' + rowErrors.join(', ') + '</span></td>'
                    : '<td><span class="badge badge-success">OK</span></td>';
                if (rowErrors.length) errors.push('Row ' + i + ': ' + rowErrors.join(', '));
                else validRows++;
                var $tr = $('<tr>');
                cols.forEach(function (c) { $tr.append($('<td>').text(c)); });
                $tr.append(statusCell);
                $body.append($tr);
            }
            if (lines.length > 51) $body.append('<tr><td colspan="' + (headers.length + 1) + '" class="text-center text-muted">... and ' + (lines.length - 51) + ' more rows</td></tr>');
            $('#bulk-book-row-count').text((lines.length - 1) + ' rows');
            $('#bulk-book-preview-area').show();
            if (errors.length) {
                $('#bulk-book-errors').html('<div class="alert alert-warning border-0"><strong>' + errors.length + ' row(s) have issues:</strong><ul class="mb-0 mt-1">' + errors.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul></div>');
            }
            $('#bulk-book-submit').prop('disabled', validRows === 0);
        };
        reader.readAsText(file);
    });

    $('#bulk-book-form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#bulk-book-submit').prop('disabled', true).html('<i class="bi bi-hourglass-split mr-1"></i>Importing...');
        var fd = new FormData(this);
        $.ajax({ url: $(this).attr('action'), type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
        .done(function (r) {
            var cls = r.ok ? 'success' : 'danger';
            var html = '<div class="alert alert-' + cls + ' border-0"><strong>' + (r.ok ? 'Import Complete' : 'Import Failed') + '</strong><br>' + r.msg + '</div>';
            if (r.errors && r.errors.length) html += '<ul class="list-group mt-2">' + r.errors.map(function (e) { return '<li class="list-group-item list-group-item-danger py-1 small">' + e + '</li>'; }).join('') + '</ul>';
            $('#bulk-book-result').html(html).show();
            $btn.prop('disabled', false).html('<i class="bi bi-cloud-upload mr-1"></i>Import Books');
            if (r.ok) setTimeout(function () { location.reload(); }, 1500);
        })
        .fail(function (xhr) {
            $('#bulk-book-result').html('<div class="alert alert-danger border-0">Server error: ' + xhr.status + '</div>').show();
            $btn.prop('disabled', false).html('<i class="bi bi-cloud-upload mr-1"></i>Import Books');
        });
    });
});
</script>
@endif
@endsection
