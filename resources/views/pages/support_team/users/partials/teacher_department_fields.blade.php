<div id="teacher-department-section" class="row" style="display:none;">
    <div class="col-md-12">
        <hr class="my-2">
        <h6 class="font-weight-semibold text-primary mb-3"><i class="bi bi-building mr-1"></i> Teacher Department</h6>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="department_id">Department <span class="text-danger">*</span></label>
            <select class="form-control select-search" name="department_id" id="department_id" data-placeholder="Select department">
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
