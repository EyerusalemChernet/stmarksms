@extends('layouts.master')
@section('page_title', 'Library')
@section('breadcrumb')
    <span class="breadcrumb-item active">Library</span>
@endsection
@section('content')
@include('partials.back_button')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Book Inventory</h6>
        @if(Qs::userIsTeamSA())
        <a href="{{ route('library.create') }}" class="btn btn-sm btn-primary">Add Book</a>
        @endif
    </div>
    <div class="card-body p-0">
        @if(session('flash_success'))<div class="alert alert-success m-3">{{ session('flash_success') }}</div>@endif
        @if(session('flash_danger'))<div class="alert alert-danger m-3">{{ session('flash_danger') }}</div>@endif
        <table class="table table-bordered table-sm mb-0 datatable-basic">
            <thead class="thead-light">
                <tr><th>#</th><th>Title</th><th>Author</th><th>Class</th><th>Type</th><th>Total</th><th>Available</th><th>Action</th></tr>
            </thead>
            <tbody>
                @foreach($books as $i => $b)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $b->name }}</td>
                    <td>{{ $b->author ?? '-' }}</td>
                    <td>{{ $b->my_class->name ?? 'General' }}</td>
                    <td>{{ $b->book_type ?? '-' }}</td>
                    <td>{{ $b->total_copies }}</td>
                    <td>{{ $b->total_copies - $b->issued_copies }}</td>
                    <td>
                        @if(Qs::userIsTeamSA())
                        <a href="{{ route('library.edit', $b->id) }}" class="btn btn-xs btn-warning">Edit</a>
                        <form method="POST" action="{{ route('library.destroy', $b->id) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger" onclick="return confirm('Delete?')">Del</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('library.request') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="book_id" value="{{ $b->id }}">
                            <button class="btn btn-xs btn-info" {{ ($b->total_copies - $b->issued_copies) < 1 ? 'disabled' : '' }}>Borrow</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $books->links() }}</div>
    </div>
</div>
@endsection
