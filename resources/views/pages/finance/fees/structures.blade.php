@extends('layouts.master')
@section('page_title', 'Fee Structures')
@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-diagram-3 mr-2"></i>Assign Fee to Class — {{ $session }}</h6></div>
            <div class="card-body">
                <form action="{{ route('fees.structures.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Fee Category</label>
                        <select name="fee_category_id" class="form-control" required>
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <select name="my_class_id" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            @foreach($classes as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-7">
                            <div class="form-group">
                                <label>Amount (ETB)</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="1" required>
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="form-group">
                                <label>Max Installments</label>
                                <input type="number" name="installments" class="form-control" min="1" max="12" value="1" required>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm">Save Structure</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-people mr-2"></i>Bulk Assign to Class</h6></div>
            <div class="card-body">
                <form action="{{ route('fees.bulk_assign') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Fee Structure</label>
                        <select name="fee_structure_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($structures->where('session', $session) as $s)
                            <option value="{{ $s->id }}">{{ $s->category->name }} — {{ $s->my_class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <select name="my_class_id" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            @foreach($classes as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-success btn-sm"><i class="bi bi-people mr-1"></i>Assign to All Students</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-table mr-2"></i>Fee Structures</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr><th>Category</th><th>Class</th><th>Session</th><th>Amount</th><th>Installments</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($structures as $s)
                        <tr>
                            <td><span class="badge badge-primary">{{ $s->category->code ?? '' }}</span> {{ $s->category->name ?? '-' }}</td>
                            <td>{{ $s->my_class->name ?? '-' }}</td>
                            <td>{{ $s->session }}</td>
                            <td>ETB {{ number_format($s->amount, 2) }}</td>
                            <td>{{ $s->installments }}</td>
                            <td>
                                <form action="{{ route('fees.structures.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No structures defined.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
