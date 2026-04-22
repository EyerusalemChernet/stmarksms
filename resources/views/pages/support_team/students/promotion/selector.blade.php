<form method="post" action="{{ route('students.promote_selector') }}">
    @csrf
    <div class="row">
        <div class="col-md-10">
            <fieldset>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="fc" class="col-form-label font-weight-bold">From Class:</label>
                            <select required onchange="promotionFromClassChanged(this.value)" id="fc" name="fc" class="form-control select">
                                <option value="">Select Class</option>
                                @foreach($my_classes as $c)
                                    <option {{ ($selected && $fc == $c->id) ? 'selected' : '' }}
                                        value="{{ $c->id }}"
                                        data-name="{{ $c->name }}"
                                        data-next="{{ $classNextMap[$c->id] ?? '' }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="fs" class="col-form-label font-weight-bold">From Section:</label>
                            <select required id="fs" name="fs" data-placeholder="Select Class First" class="form-control select">
                                @if($selected && $fs)
                                    <option value="{{ $fs }}">{{ $sections->where('id', $fs)->first()->name ?? '' }}</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="tc" class="col-form-label font-weight-bold">To Class:</label>
                            <select required onchange="getClassSections(this.value, '#ts')" id="tc" name="tc" class="form-control select">
                                <option value="">Select Class</option>
                                @foreach($my_classes as $c)
                                    <option {{ ($selected && $tc == $c->id) ? 'selected' : '' }} value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <span id="next-class-hint" class="text-success small mt-1 d-block"></span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="ts" class="col-form-label font-weight-bold">To Section:</label>
                            <select required id="ts" name="ts" data-placeholder="Select Class First" class="form-control select">
                                @if($selected && $ts)
                                    <option value="{{ $ts }}">{{ $sections->where('id', $ts)->first()->name ?? '' }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="col-md-2 mt-4">
            <div class="text-right mt-1">
                <button type="submit" class="btn btn-primary">Manage Promotion <i class="icon-paperplane ml-2"></i></button>
            </div>
        </div>
    </div>
</form>

<script>
function promotionFromClassChanged(classId) {
    getClassSections(classId, '#fs');

    // Auto-suggest next class
    var opt = $('#fc option[value="' + classId + '"]');
    var nextName = opt.data('next');
    var hint = $('#next-class-hint');

    if (nextName) {
        hint.text('(Suggested: ' + nextName + ')');
        // Auto-select the suggested "To Class"
        $('#tc option').each(function() {
            if ($(this).text().trim().toLowerCase() === nextName.toLowerCase()) {
                $('#tc').val($(this).val()).trigger('change');
                getClassSections($(this).val(), '#ts');
            }
        });
    } else {
        hint.text('');
    }
}
</script>
