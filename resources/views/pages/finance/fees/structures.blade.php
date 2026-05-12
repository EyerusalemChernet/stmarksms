@extends('layouts.master')
@section('page_title','Fee Structures')
@section('content')
<div class="row">
  <div class="col-md-5">
    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-diagram-3 mr-2"></i>Add Fee Structure &mdash; {{ $session }}</h6></div>
      <div class="card-body">
        <form action="{{ route('fees.structures.store') }}" method="POST">@csrf
          <div class="form-group">
            <label>Fee Category *</label>
            <select name="fee_category_id" class="form-control" required>
              <option value="">-- Select --</option>
              @foreach($categories as $c)
                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Class *</label>
            <select name="my_class_id" class="form-control" required>
              <option value="">-- Select --</option>
              @foreach($classes as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Session *</label>
            <input type="text" name="session" class="form-control" value="{{ $session }}" required>
          </div>
          <div class="form-group">
            <label>Amount (ETB) *</label>
            <input type="number" name="amount" class="form-control" step="0.01" min="1" required>
          </div>
          <div class="form-group">
            <label>Max Installments</label>
            <input type="number" name="installments" class="form-control" min="1" max="12" value="1">
            <small class="text-muted">1 = full payment only</small>
          </div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Save Structure</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-people mr-2"></i>Bulk Assign to Class</h6></div>
      <div class="card-body">
        <form action="{{ route('fees.bulk_assign') }}" method="POST">@csrf
          <div class="form-group">
            <label>Fee Structure *</label>
            <select name="fee_structure_id" class="form-control" required>
              <option value="">-- Select --</option>
              @foreach($structures as $s)
                <option value="{{ $s->id }}">
                  {{ $s->category->name ?? 'N/A' }} &mdash; {{ $s->my_class->name ?? 'N/A' }}
                  (ETB {{ number_format($s->amount, 2) }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Assign to Class *</label>
            <select name="my_class_id" class="form-control" required>
              <option value="">-- Select --</option>
              @foreach($classes as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <button class="btn btn-success btn-sm"><i class="bi bi-lightning mr-1"></i>Assign to All Students</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-ul mr-2"></i>Fee Structures</h6></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="thead-light">
              <tr><th>#</th><th>Category</th><th>Class</th><th>Amount</th><th>Inst.</th><th>Session</th><th></th></tr>
            </thead>
            <tbody>
              @forelse($structures as $i => $s)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                  <span class="badge badge-primary">{{ $s->category->code ?? '?' }}</span>
                  {{ $s->category->name ?? '-' }}
                </td>
                <td>{{ $s->my_class->name ?? '-' }}</td>
                <td><strong>ETB {{ number_format($s->amount, 2) }}</strong></td>
                <td>{{ $s->installments }}</td>
                <td>{{ $s->session }}</td>
                <td class="text-nowrap">
                  <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#editStruct{{ $s->id }}">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form action="{{ route('fees.structures.destroy', Qs::hash($s->id)) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Delete this structure?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-xs"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
              @empty
              <tr><td colspan="7" class="text-center text-muted py-4">No fee structures yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Edit Modals --}}
@foreach($structures as $s)
<div class="modal fade" id="editStruct{{ $s->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">
          Edit: {{ $s->category->name ?? 'Structure' }} &mdash; {{ $s->my_class->name ?? '' }}
        </h6>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('fees.structures.update', Qs::hash($s->id)) }}" method="POST">
        @csrf @method('PUT')
        <div class="modal-body">
          <div class="form-group">
            <label>Amount (ETB) *</label>
            <input type="number" name="amount" class="form-control" step="0.01" min="0"
                   value="{{ $s->amount }}" required>
          </div>
          <div class="form-group">
            <label>Max Installments</label>
            <input type="number" name="installments" class="form-control" min="1" max="12"
                   value="{{ $s->installments }}">
          </div>
          <p class="text-muted mb-0" style="font-size:12px;">
            Category, class, and session cannot be changed after creation.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach
@endsection
