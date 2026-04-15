@extends('layouts.master')
@section('page_title', 'Edit Book')
@section('content')
<div class="card">
    <div class="card-header bg-white"><h6 class="card-title">Edit Book — {{ $book->name }}</h6></div>
    <div class="card-body">
        <form method="POST" action="{{ route('library.update', $book->id) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="name" required class="form-control" value="{{ $book->name }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" class="form-control" value="{{ $book->author }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Class</label>
                        <select name="my_class_id" class="select form-control" data-fouc data-placeholder="General">
                            <option value=""></option>
                            @foreach($my_classes as $c)
                                <option value="{{ $c->id }}" {{ $book->my_class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Book Type</label>
                        <select name="book_type" class="select form-control" data-fouc data-placeholder="Choose..">
                            <option value=""></option>
                            @foreach(['Textbook','Reference','Novel','Magazine','Other'] as $t)
                                <option value="{{ $t }}" {{ $book->book_type == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Copies <span class="text-danger">*</span></label>
                        <input type="number" name="total_copies" required min="1" class="form-control" value="{{ $book->total_copies }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" value="{{ $book->location }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" value="{{ $book->description }}">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-warning">Update Book</button>
            <a href="{{ route('library.index') }}" class="btn btn-secondary ml-2">Cancel</a>
        </form>
    </div>
</div>
@endsection
