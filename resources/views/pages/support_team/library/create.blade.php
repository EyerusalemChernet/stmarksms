@extends('layouts.master')
@section('page_title', 'Add Book')
@section('content')

<div style="max-width:760px;margin:0 auto;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div>
            <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">Add New Book</h5>
            <p style="font-size:13px;color:#64748b;margin:0;">Fill in manually or use ISBN to auto-fill from Open Library.</p>
        </div>
        <a href="{{ route('library.index') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    {{-- ISBN Lookup Card --}}
    <div style="background:linear-gradient(135deg,#ede9fe,#dbeafe);border:1px solid #c4b5fd;border-radius:12px;padding:18px 20px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
            <i class="bi bi-upc-scan" style="font-size:18px;color:#4f46e5;"></i>
            <span style="font-weight:700;font-size:14px;color:#1e293b;">ISBN Auto-Fill</span>
            <span style="background:#4f46e5;color:#fff;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;margin-left:4px;">Smart</span>
        </div>
        <p style="font-size:12px;color:#475569;margin:0 0 12px;">Enter the book's ISBN and we'll fetch the title, author, publisher, and cover from Open Library automatically.</p>
        <div style="display:flex;gap:8px;">
            <input type="text" id="isbn-input" placeholder="e.g. 9780000000000 or 0-306-40615-2"
                   style="flex:1;padding:9px 14px;border:1px solid #c4b5fd;border-radius:8px;font-size:13px;color:#1e293b;background:#fff;outline:none;">
            <button type="button" id="isbn-lookup-btn"
                    style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
                <i class="bi bi-search mr-1"></i>Look Up
            </button>
        </div>
        <div id="isbn-status" style="margin-top:8px;font-size:12px;display:none;"></div>
    </div>

    {{-- Book Form --}}
    <form method="POST" action="{{ route('library.store') }}" enctype="multipart/form-data">
        @csrf

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">

            {{-- Section: Basic Info --}}
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
                <span style="font-weight:700;font-size:13px;color:#475569;text-transform:uppercase;letter-spacing:.5px;">
                    <i class="bi bi-book mr-1"></i>Book Information
                </span>
            </div>
            <div style="padding:20px;">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Title <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="field-name" required class="form-control" value="{{ old('name') }}" placeholder="Full book title">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>ISBN</label>
                            <input type="text" name="isbn" id="field-isbn" class="form-control" value="{{ old('isbn') }}" placeholder="e.g. 9780000000000">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Author</label>
                            <input type="text" name="author" id="field-author" class="form-control" value="{{ old('author') }}" placeholder="Author name(s)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Publisher</label>
                            <input type="text" name="publisher" id="field-publisher" class="form-control" value="{{ old('publisher') }}" placeholder="Publisher name">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Published Year</label>
                            <input type="number" name="published_year" id="field-year" class="form-control" value="{{ old('published_year') }}" placeholder="e.g. 2022" min="1800" max="{{ date('Y') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Book Type</label>
                            <select name="book_type" class="select form-control" data-fouc data-placeholder="Choose…">
                                <option value=""></option>
                                @foreach($bookTypes as $t)
                                <option value="{{ $t }}" {{ old('book_type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Subject Area</label>
                            <input type="text" name="subject_area" class="form-control" value="{{ old('subject_area') }}" placeholder="e.g. Mathematics">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Class</label>
                            <select name="my_class_id" class="select form-control" data-fouc data-placeholder="General (All)">
                                <option value=""></option>
                                @foreach($my_classes as $c)
                                <option value="{{ $c->id }}" {{ old('my_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="field-description" class="form-control" rows="2" placeholder="Brief description of the book">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- Section: Inventory --}}
            <div style="padding:16px 20px;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
                <span style="font-weight:700;font-size:13px;color:#475569;text-transform:uppercase;letter-spacing:.5px;">
                    <i class="bi bi-stack mr-1"></i>Inventory & Location
                </span>
            </div>
            <div style="padding:20px;">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Total Copies <span class="text-danger">*</span></label>
                            <input type="number" name="total_copies" required min="1" class="form-control" value="{{ old('total_copies', 1) }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Loan Period (days)</label>
                            <input type="number" name="due_days" min="1" max="365" class="form-control" value="{{ old('due_days', 14) }}" placeholder="14">
                            <small class="text-muted">Default: 14 days</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Location / Shelf</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="e.g. Shelf A-1">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Cover Image</label>
                            <input type="file" name="cover_image" id="field-cover" accept="image/*" class="form-control" style="padding:5px;">
                            <small class="text-muted">JPEG/PNG, max 2MB</small>
                        </div>
                    </div>
                </div>

                {{-- Cover preview --}}
                <div id="cover-preview-wrap" style="display:none;margin-top:8px;">
                    <img id="cover-preview" src="" alt="Cover preview"
                         style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,.1);">
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding:16px 20px;border-top:1px solid #f1f5f9;background:#f8fafc;display:flex;gap:8px;">
                <button type="submit" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(79,70,229,.3);">
                    <i class="bi bi-save mr-1"></i>Save Book
                </button>
                <a href="{{ route('library.index') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:600;text-decoration:none;">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
$(function () {

    // ── Cover image preview ──────────────────────────────────────────────────
    $('#field-cover').on('change', function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#cover-preview').attr('src', e.target.result);
            $('#cover-preview-wrap').show();
        };
        reader.readAsDataURL(file);
    });

    // ── ISBN Lookup ──────────────────────────────────────────────────────────
    function doIsbnLookup() {
        var isbn = $('#isbn-input').val().trim();
        if (!isbn) { flash({ msg: 'Enter an ISBN first.', type: 'warning' }); return; }

        var $btn = $('#isbn-lookup-btn').prop('disabled', true).html('<i class="bi bi-hourglass-split mr-1"></i>Looking up…');
        var $status = $('#isbn-status').show().html('<span style="color:#64748b;">Searching Open Library…</span>');

        $.ajax({
            url:  '{{ route("library.isbn.lookup") }}',
            data: { isbn: isbn },
            success: function (r) {
                if (r.ok) {
                    if (r.title)          $('#field-name').val(r.title);
                    if (r.author)         $('#field-author').val(r.author);
                    if (r.publisher)      $('#field-publisher').val(r.publisher);
                    if (r.published_year) $('#field-year').val(r.published_year);
                    if (r.description)    $('#field-description').val(r.description);
                    if (r.isbn)           $('#field-isbn').val(isbn);
                    if (r.cover) {
                        $('#cover-preview').attr('src', r.cover);
                        $('#cover-preview-wrap').show();
                    }
                    $status.html('<span style="color:#10b981;font-weight:600;">✓ Book details filled in from Open Library!</span>');
                } else {
                    $status.html('<span style="color:#f59e0b;">' + r.msg + '</span>');
                }
            },
            error: function () {
                $status.html('<span style="color:#ef4444;">Lookup failed. Please fill in manually.</span>');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bi bi-search mr-1"></i>Look Up');
            }
        });
    }

    $('#isbn-lookup-btn').on('click', doIsbnLookup);
    $('#isbn-input').on('keypress', function (e) { if (e.which === 13) { e.preventDefault(); doIsbnLookup(); } });
});
</script>
@endsection
