@extends('layouts.master')
@section('page_title', 'Edit Book')
@section('content')

<div style="max-width:760px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div>
            <h5 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">Edit Book</h5>
            <p style="font-size:13px;color:#64748b;margin:0;">{{ $book->name }}</p>
        </div>
        <a href="{{ route('library.index') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('library.update', $book->id) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">

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
                            <input type="text" name="name" required class="form-control" value="{{ $book->name }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>ISBN</label>
                            <input type="text" name="isbn" class="form-control" value="{{ $book->isbn }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Author</label>
                            <input type="text" name="author" class="form-control" value="{{ $book->author }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Publisher</label>
                            <input type="text" name="publisher" class="form-control" value="{{ $book->publisher }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Published Year</label>
                            <input type="number" name="published_year" class="form-control" value="{{ $book->published_year }}" min="1800" max="{{ date('Y') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Book Type</label>
                            <select name="book_type" class="select form-control" data-fouc data-placeholder="Choose…">
                                <option value=""></option>
                                @foreach($bookTypes as $t)
                                <option value="{{ $t }}" {{ $book->book_type === $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Subject Area</label>
                            <input type="text" name="subject_area" class="form-control" value="{{ $book->subject_area }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Class</label>
                            <select name="my_class_id" class="select form-control" data-fouc data-placeholder="General (All)">
                                <option value=""></option>
                                @foreach($my_classes as $c)
                                <option value="{{ $c->id }}" {{ $book->my_class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ $book->description }}</textarea>
                </div>
            </div>

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
                            <input type="number" name="total_copies" required min="1" class="form-control" value="{{ $book->total_copies }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Loan Period (days)</label>
                            <input type="number" name="due_days" min="1" max="365" class="form-control" value="{{ $book->due_days ?? 14 }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Location / Shelf</label>
                            <input type="text" name="location" class="form-control" value="{{ $book->location }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Cover Image</label>
                            <input type="file" name="cover_image" accept="image/*" class="form-control" style="padding:5px;">
                            @if($book->cover_image)
                            <div style="margin-top:8px;">
                                <img src="{{ $book->cover_url }}" style="width:50px;height:50px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">
                                <small class="text-muted d-block mt-1">Current cover</small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div style="padding:16px 20px;border-top:1px solid #f1f5f9;background:#f8fafc;display:flex;gap:8px;">
                <button type="submit" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(245,158,11,.3);">
                    <i class="bi bi-save mr-1"></i>Update Book
                </button>
                <a href="{{ route('library.index') }}" style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:600;text-decoration:none;">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>

@endsection
