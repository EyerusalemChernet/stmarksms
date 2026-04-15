@extends('layouts.master')
@section('page_title', 'Add Book')
@section('content')
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title">Add New Book</h6></div>
    <div class="card-body">
        <form method="POST" action="{{ route('library.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="name" required class="form-control" value="{{ old('name') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" class="form-control" value="{{ old('author') }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Class</label>
                        <select name="my_class_id" class="select form-control" data-placeholder="General (All Classes)" data-fouc>
                            <option value=""></option>
                            @foreach($my_classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Book Type</label>
                        <select name="book_type" class="select form-control" data-fouc data-placeholder="Choose..">
                            <option value=""></option>
                            <option value="Textbook">Textbook</option>
                            <option value="Reference">Reference</option>
                            <option value="Novel">Novel</option>
                            <option value="Magazine">Magazine</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Copies <span class="text-danger">*</span></label>
                        <input type="number" name="total_copies" required min="1" class="form-control" value="{{ old('total_copies', 1) }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Location / Shelf</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Book</button>
            <a href="{{ route('library.index') }}" class="btn btn-secondary ml-2">Cancel</a>
        </form>
    </div>
</div>
@endsection
