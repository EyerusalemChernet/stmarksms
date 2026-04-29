@extends('layouts.master')
@section('page_title', 'Fee Invoices')
@section('content')

{{-- Assign Fee Form --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-file-earmark-plus mr-2"></i>Assign Fee to Student</h6>
        <button class="btn btn-primary btn-sm" data-toggle="collapse" data-target="#assignForm">
            <i class="bi bi-plus-lg mr-1"></i>New Invoice
        </button>
    </div>
    <div class="collapse" id="assignForm">
        <div class="card-body border-top">
            <form action="{{ route('fees.assign') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="number" name="student_id" class="form-control" placeholder="User ID" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fee Structure</label>
                            <select name="fee_structure_id" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach(\App\Models\FeeStructure::with(['category','my_class'])->where('session', $session)->get() as $s)
                                <option value="{{ $s->id }}">{{ $s->category->name }} — {{ $s->my_class->name }} (ETB {{ number_format($s->amount,2) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Discount (ETB)</label>
                            <input type="number" name="discount" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Fine (ETB)</label>
                            <input type="number" name="fine" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                    </div>
                </div>
                <button class="btn btn-success btn-sm">Create Invoice</button>
            </form>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-receipt mr-2"></i>Invoices — {{ $session }}</h6>
        <form method="GET" class="d-flex flex-wrap gap-2">
            <select name="class_id" class="form-control form-control-sm" style="width:140px">
                <option value="">All Classes</option>
                @foreach($classes as $cls)
                <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>{{ $cls->name }}</option>
                @endforeach
            </select>
            <select name="status" class="form-control form-control-sm" style="width:120px">
                <option value="">All Status</option>
                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
            <button class="btn btn-secondary btn-sm">Filter</button>
        </form>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead class="thead-light">
                <tr><th>Invoice No</th><th>Student</th><th>Fee</th><th>Class</th><th>Net Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td><code>{{ $inv->invoice_no }}</code></td>
                    <td>{{ $inv->student->name ?? 'N/A' }}</td>
                    <td>{{ $inv->fee_structure->category->name ?? '-' }}</td>
                    <td>{{ $inv->fee_structure->my_class->name ?? '-' }}</td>
                    <td>{{ number_format($inv->net_amount, 2) }}</td>
                    <td class="text-success">{{ number_format($inv->amount_paid, 2) }}</td>
                    <td class="text-danger">{{ number_format($inv->balance, 2) }}</td>
                    <td>
                        @php $badge = ['unpaid'=>'danger','partial'=>'warning','paid'=>'success'][$inv->status] @endphp
                        <span class="badge badge-{{ $badge }}">{{ ucfirst($inv->status) }}</span>
                    </td>
                    <td>{{ $inv->due_date ?? '-' }}</td>
                    <td>
                        <a href="{{ route('fees.invoice', $inv->id) }}" class="btn btn-primary btn-xs"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('fees.student_history', $inv->student_id) }}" class="btn btn-info btn-xs"><i class="bi bi-clock-history"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-4">No invoices found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
