{{-- Department field — shown for all staff except parents --}}
<div id="staff-department-section" class="row" style="display:none;">
    <div class="col-md-12">
        <hr class="my-2">
        <h6 class="font-weight-semibold text-primary mb-3"><i class="bi bi-building mr-1"></i> Department</h6>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="department_id">Department</label>
            <select class="form-control select-search" name="department_id" id="department_id" data-placeholder="Select department (optional for non-teachers)">
                <option value=""></option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
            @if($departments->isEmpty())
                <small class="form-text text-warning">No departments yet. Super Admin can create them under Settings → Departments.</small>
            @endif
        </div>
    </div>
</div>

{{-- Employment date — hidden for parents --}}
<div id="emp-date-section" class="row" style="display:none;">
    <div class="col-md-3">
        <div class="form-group">
            <label>Date of Employment:</label>
            <input autocomplete="off" name="emp_date" value="{{ old('emp_date') }}" type="text" class="form-control date-pick" placeholder="Select Date...">
        </div>
    </div>
</div>
