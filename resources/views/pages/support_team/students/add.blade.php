@extends('layouts.master')
@section('page_title', 'Admit Student')
@section('content')
<div class="card">
    <div class="card-header bg-white header-elements-inline">
        <h6 class="card-title">Fill the form below to admit a new student</h6>
        {!! Qs::getPanelOptions() !!}
    </div>

    <form id="ajax-reg" method="post" enctype="multipart/form-data"
          class="wizard-form steps-validation" action="{{ route('students.store') }}" data-fouc>
        @csrf

        {{-- ── STEP 1: Personal Data ─────────────────────────────────────── --}}
        <h6>Personal Data</h6>
        <fieldset>

            {{-- ── OCR Quick Fill ──────────────────────────────────────────── --}}
            <div class="card mb-3" style="border:1px solid #4f46e5;border-radius:8px;">
                <div class="card-header d-flex align-items-center"
                     style="background:#4f46e5;color:#fff;border-radius:7px 7px 0 0;padding:10px 16px;">
                    <i class="bi bi-camera-fill mr-2"></i>
                    <h6 class="mb-0">Quick Fill from Document <small style="opacity:.7;">(Optional)</small></h6>
                </div>
                <div class="card-body py-3">
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label class="small font-weight-bold">Upload Birth Certificate or Student ID</label>
                            <input type="file" id="ocr-upload" accept="image/*"
                                   class="form-control form-control-sm">
                            <small class="text-muted">Upload a clear photo or scan (JPEG/PNG)</small>
                        </div>
                        <div class="col-md-4">
                            <div id="ocr-status" class="small"></div>
                        </div>
                        <div class="col-md-3 text-right">
                            <button type="button" id="ocr-scan-btn" class="btn btn-sm btn-primary" disabled>
                                <i class="bi bi-search mr-1"></i>Scan Document
                            </button>
                        </div>
                    </div>

                    {{-- Preview of extracted data --}}
                    <div id="ocr-preview" class="mt-3" style="display:none;">
                        <hr style="margin:10px 0;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong style="font-size:13px;">
                                <i class="bi bi-check-circle text-success mr-1"></i>
                                Extracted Information <small class="text-muted font-weight-normal">(verify before filling)</small>
                            </strong>
                            <div style="gap:6px;display:flex;">
                                <button type="button" id="ocr-fill-btn" class="btn btn-sm btn-success">
                                    <i class="bi bi-stars mr-1"></i>Auto-Fill Form
                                </button>
                                <button type="button" id="ocr-clear-btn" class="btn btn-sm btn-outline-secondary">
                                    Clear
                                </button>
                            </div>
                        </div>
                        <div class="row" style="font-size:13px;">
                            <div class="col-md-4">
                                <span class="text-muted">Full Name:</span>
                                <strong id="ocr-name" class="ml-1">—</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Date of Birth:</span>
                                <strong id="ocr-dob" class="ml-1">—</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Address / Place:</span>
                                <strong id="ocr-address" class="ml-1">—</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- ── End OCR ──────────────────────────────────────────────────── --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input value="{{ old('name') }}" required type="text" name="name"
                               placeholder="Full Name" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Address <span class="text-danger">*</span></label>
                        <input value="{{ old('address') }}" class="form-control"
                               placeholder="Address" name="address" type="text" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="{{ old('email') }}" name="email"
                               class="form-control" placeholder="Email Address">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Gender <span class="text-danger">*</span></label>
                        <select class="select form-control" name="gender" required
                                data-fouc data-placeholder="Choose..">
                            <option value=""></option>
                            <option {{ old('gender') == 'Male'   ? 'selected' : '' }} value="Male">Male</option>
                            <option {{ old('gender') == 'Female' ? 'selected' : '' }} value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Phone <small class="text-muted">(09XXXXXXXX)</small></label>
                        <input value="{{ old('phone') }}" type="text" name="phone"
                               class="form-control" placeholder="e.g. 0911434321"
                               pattern="09[0-9]{8}" title="10 digits starting with 09">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Alternative Phone <small class="text-muted">(Guardian 2)</small></label>
                        <input value="{{ old('phone2') }}" type="text" name="phone2"
                               class="form-control" placeholder="e.g. 0922434321"
                               pattern="09[0-9]{8}" title="10 digits starting with 09">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input name="dob" value="{{ old('dob') }}" type="text"
                               class="form-control date-pick" placeholder="Select Date...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Nationality <span class="text-danger">*</span></label>
                        <select data-placeholder="Choose..." required name="nal_id"
                                class="select-search form-control">
                            <option value=""></option>
                            @foreach($nationals->sortBy(fn($n) => $n->name === 'Ethiopian' ? 0 : 1) as $nal)
                                <option {{ old('nal_id') == $nal->id ? 'selected' : '' }}
                                        value="{{ $nal->id }}">{{ $nal->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Region <span class="text-danger">*</span></label>
                        <select onchange="getLGA(this.value)" required
                                data-placeholder="Choose.." class="select-search form-control"
                                name="state_id" id="state_id">
                            <option value=""></option>
                            @foreach($states as $st)
                                <option {{ old('state_id') == $st->id ? 'selected' : '' }}
                                        value="{{ $st->id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Sub-city / Woreda <span class="text-danger">*</span></label>
                        <select required data-placeholder="Select Region First"
                                class="select-search form-control" name="lga_id" id="lga_id">
                            <option value=""></option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Blood Group</label>
                        <select class="select form-control" name="bg_id"
                                data-fouc data-placeholder="Choose..">
                            <option value=""></option>
                            @foreach(App\Models\BloodGroup::all() as $bg)
                                <option {{ old('bg_id') == $bg->id ? 'selected' : '' }}
                                        value="{{ $bg->id }}">{{ $bg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="d-block">Upload Passport Photo</label>
                        <input value="{{ old('photo') }}" accept="image/*" type="file"
                               name="photo" class="form-input-styled" data-fouc>
                        <span class="form-text text-muted">Accepted: jpeg, png. Max 2MB</span>
                    </div>
                </div>
            </div>
        </fieldset>

        {{-- ── STEP 2: Student Data ──────────────────────────────────────── --}}
        <h6>Student Data</h6>
        <fieldset>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select onchange="getClassSections(this.value)" data-placeholder="Choose..."
                                required name="my_class_id" class="select-search form-control">
                            <option value=""></option>
                            @foreach($my_classes as $c)
                                <option {{ old('my_class_id') == $c->id ? 'selected' : '' }}
                                        value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Section <span class="text-danger">*</span></label>
                        <select data-placeholder="Select Class First" required
                                name="section_id" id="section_id" class="select-search form-control">
                            <option {{ old('section_id') ? 'selected' : '' }}
                                    value="{{ old('section_id') }}">{{ old('section_id') ? 'Selected' : '' }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Parent</label>
                        <select data-placeholder="Choose..." name="my_parent_id"
                                class="select-search form-control">
                            <option value=""></option>
                            @foreach($parents as $p)
                                <option {{ old('my_parent_id') == Qs::hash($p->id) ? 'selected' : '' }}
                                        value="{{ Qs::hash($p->id) }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Year Admitted <span class="text-danger">*</span></label>
                        <select data-placeholder="Choose..." required name="year_admitted"
                                class="select-search form-control">
                            <option value=""></option>
                            @for($y = date('Y', strtotime('-10 years')); $y <= date('Y'); $y++)
                                <option {{ old('year_admitted') == $y ? 'selected' : '' }}
                                        value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Religion</label>
                        <select name="religion" class="select form-control"
                                data-fouc data-placeholder="Choose..">
                            <option value=""></option>
                            @foreach(['Ethiopian Orthodox','Muslim','Protestant','Catholic','Traditional','Other'] as $rel)
                                <option {{ old('religion') == $rel ? 'selected' : '' }}
                                        value="{{ $rel }}">{{ $rel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Admission Number</label>
                        <div class="input-group">
                            <input type="text" id="adm_no_display" class="form-control"
                                   placeholder="Auto-generated on save" readonly
                                   style="background:#f8f9fa;color:#6c757d;">
                            <div class="input-group-append">
                                <span class="input-group-text" title="Admission number is auto-generated">
                                    <i class="bi bi-lock-fill text-muted"></i>
                                </span>
                            </div>
                        </div>
                        <small class="text-muted">Format: STM-{{ date('Y') }}-XXXX</small>
                    </div>
                </div>
            </div>
        </fieldset>

    </form>
</div>
@endsection

@section('scripts')
<script>
$(function () {

    // ── Helper: validate all required fields in a fieldset ──────────────────
    function validateStep($fieldset) {
        var valid = true;

        // Clear previous errors in this step
        $fieldset.find('.is-invalid').removeClass('is-invalid');
        $fieldset.find('.wizard-step-error').remove();

        // Check every required input / select / textarea
        $fieldset.find('input[required], select[required], textarea[required]').each(function () {
            var $el  = $(this);
            var val  = $el.val();
            var empty = (val === null || val === '' || (Array.isArray(val) && val.length === 0));

            // For Select2-wrapped selects the visible element is a sibling span;
            // we check the underlying <select> value directly.
            if (empty) {
                valid = false;
                $el.addClass('is-invalid');

                // Only add the message once
                if (!$el.next('.wizard-step-error').length) {
                    var label = $el.closest('.form-group').find('label').first().text()
                                   .replace('*', '').trim();
                    $el.after(
                        '<div class="wizard-step-error invalid-feedback" style="display:block;">' +
                        (label || 'This field') + ' is required.' +
                        '</div>'
                    );
                }
            }
        });

        // Phone pattern validation (Ethiopian 09XXXXXXXX)
        $fieldset.find('input[pattern]').each(function () {
            var $el  = $(this);
            var val  = $el.val();
            if (val && !new RegExp($el.attr('pattern')).test(val)) {
                valid = false;
                $el.addClass('is-invalid');
                if (!$el.next('.wizard-step-error').length) {
                    $el.after(
                        '<div class="wizard-step-error invalid-feedback" style="display:block;">' +
                        ($el.attr('title') || 'Invalid format.') +
                        '</div>'
                    );
                }
            }
        });

        return valid;
    }

    // ── Re-initialise the jQuery Steps wizard with validation ────────────────
    // jQuery Steps is already initialised by form_wizard.js via the
    // .wizard-form class. We need to destroy and re-init with our callbacks.
    var $form = $('#ajax-reg');

    // Destroy any existing Steps instance first
    if ($.fn.steps) {
        try { $form.steps('destroy'); } catch(e) {}

        $form.steps({
            headerTag:      'h6',
            bodyTag:        'fieldset',
            transitionEffect: 'slideLeft',
            autoFocus:      true,
            titleTemplate:  '<span class="number">#index#</span> #title#',

            // ── Block "Next" if current step is invalid ──────────────────────
            onStepChanging: function (event, currentIndex, newIndex) {
                // Always allow going backwards
                if (newIndex < currentIndex) return true;

                var $fieldsets = $form.find('fieldset');
                var $current   = $fieldsets.eq(currentIndex);
                var ok         = validateStep($current);

                if (!ok) {
                    // Scroll to first error in this step
                    var $firstErr = $current.find('.is-invalid').first();
                    if ($firstErr.length) {
                        $('html, body').animate({
                            scrollTop: $firstErr.offset().top - 80
                        }, 300);
                    }
                }
                return ok;
            },

            // ── Clear errors when user goes back ─────────────────────────────
            onStepChanged: function (event, currentIndex) {
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.wizard-step-error').remove();
            },

            // ── Final submit: validate last step then let custom_js handle it ─
            onFinishing: function (event, currentIndex) {
                var $fieldsets = $form.find('fieldset');
                var $last      = $fieldsets.eq(currentIndex);
                return validateStep($last);
            },

            onFinished: function (event, currentIndex) {
                $form.submit();
            }
        });
    }

    // ── Live: remove error styling as soon as user fills a field ────────────
    $form.on('change input', 'input, select, textarea', function () {
        var $el = $(this);
        if ($el.val()) {
            $el.removeClass('is-invalid');
            $el.next('.wizard-step-error').remove();
        }
    });

    // Select2 fires a custom 'change' event — handle it too
    $form.on('select2:select select2:unselect', 'select', function () {
        var $el = $(this);
        if ($el.val()) {
            $el.removeClass('is-invalid');
            $el.next('.wizard-step-error').remove();
        }
    });

    // ── OCR: Document Quick Fill ─────────────────────────────────────────────

    var ocrData = { name: '', dob: '', address: '' };

    // Enable scan button when a file is chosen
    $('#ocr-upload').on('change', function () {
        var hasFile = this.files && this.files.length > 0;
        $('#ocr-scan-btn').prop('disabled', !hasFile);
        if (hasFile) {
            $('#ocr-status').html('<span class="text-success"><i class="bi bi-file-image mr-1"></i>Ready to scan</span>');
        }
    });

    // Load Tesseract.js on demand (only when user clicks Scan)
    $('#ocr-scan-btn').on('click', function () {
        var file = $('#ocr-upload')[0].files[0];
        if (!file) return;

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split mr-1"></i>Loading OCR...');
        $('#ocr-status').html('<span class="text-muted">Loading OCR engine...</span>');

        // Lazy-load Tesseract.js from CDN
        if (typeof Tesseract === 'undefined') {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
            script.onload = function () { runOCR(file, $btn); };
            script.onerror = function () {
                $('#ocr-status').html('<span class="text-danger">Failed to load OCR library. Check your internet connection.</span>');
                $btn.prop('disabled', false).html('<i class="bi bi-search mr-1"></i>Scan Document');
            };
            document.head.appendChild(script);
        } else {
            runOCR(file, $btn);
        }
    });

    function runOCR(file, $btn) {
        $btn.html('<i class="bi bi-hourglass-split mr-1"></i>Scanning...');
        $('#ocr-status').html(
            '<div class="progress" style="height:8px;"><div id="ocr-progress-bar" class="progress-bar bg-primary" style="width:0%"></div></div>' +
            '<small class="text-muted">Processing document...</small>'
        );

        var reader = new FileReader();
        reader.onload = function (e) {
            Tesseract.recognize(
                e.target.result,
                'eng',   // English; add '+amh' if Amharic language pack is available
                {
                    logger: function (m) {
                        if (m.status === 'recognizing text') {
                            var pct = Math.round(m.progress * 100);
                            $('#ocr-progress-bar').css('width', pct + '%');
                        }
                    }
                }
            ).then(function (result) {
                var text = result.data.text;
                ocrData  = extractFromText(text);

                $('#ocr-name').text(ocrData.name    || 'Not detected');
                $('#ocr-dob').text(ocrData.dob      || 'Not detected');
                $('#ocr-address').text(ocrData.address || 'Not detected');

                $('#ocr-preview').slideDown(200);
                $('#ocr-status').html(
                    '<span class="text-success"><i class="bi bi-check-circle mr-1"></i>Scan complete. Verify the extracted data above.</span>'
                );
                $btn.prop('disabled', false).html('<i class="bi bi-search mr-1"></i>Scan Again');

            }).catch(function (err) {
                console.error('OCR error:', err);
                $('#ocr-status').html('<span class="text-danger"><i class="bi bi-x-circle mr-1"></i>Scan failed. Try a clearer image.</span>');
                $btn.prop('disabled', false).html('<i class="bi bi-search mr-1"></i>Scan Document');
            });
        };
        reader.readAsDataURL(file);
    }

    // Extract name, DOB, address from raw OCR text
    function extractFromText(text) {
        var result = { name: '', dob: '', address: '' };

        // ── Full name: 2-3 capitalised words in a row ────────────────────────
        var nameMatch = text.match(/\b([A-Z][a-z]{1,20}\s+[A-Z][a-z]{1,20}(?:\s+[A-Z][a-z]{1,20})?)\b/);
        if (nameMatch) result.name = nameMatch[1].trim();

        // ── Date of birth ────────────────────────────────────────────────────
        var dobPatterns = [
            /\b(\d{2}[\/\-\.]\d{2}[\/\-\.]\d{4})\b/,          // DD/MM/YYYY or DD-MM-YYYY
            /\b(\d{4}[\/\-\.]\d{2}[\/\-\.]\d{2})\b/,          // YYYY-MM-DD
            /\b(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4})\b/i,
            /(?:Date\s*of\s*Birth|DOB|Born)[:\s]+([^\n\r,]{6,20})/i
        ];
        for (var i = 0; i < dobPatterns.length; i++) {
            var m = text.match(dobPatterns[i]);
            if (m) { result.dob = m[1].trim(); break; }
        }

        // ── Address / place of birth ─────────────────────────────────────────
        var addrPatterns = [
            /(?:Address|Place\s*of\s*Birth|Residence)[:\s]+([^\n\r]{5,60})/i,
            /(?:Addis\s+Ababa|Addis\s+Abeba)/i,
            /(?:Kebele|Woreda|Sub-?city)[:\s]+([^\n\r]{3,40})/i
        ];
        for (var j = 0; j < addrPatterns.length; j++) {
            var am = text.match(addrPatterns[j]);
            if (am) { result.address = (am[1] || am[0]).trim(); break; }
        }

        return result;
    }

    // Auto-fill form fields from extracted data
    $('#ocr-fill-btn').on('click', function () {
        var filled = 0;

        if (ocrData.name) {
            $('input[name="name"]').val(ocrData.name).trigger('input');
            filled++;
        }

        if (ocrData.dob) {
            // Try to normalise to a format the date-picker accepts (DD/MM/YYYY)
            var normalised = normaliseDateForPicker(ocrData.dob);
            if (normalised) {
                $('input[name="dob"]').val(normalised).trigger('change');
                filled++;
            }
        }

        if (ocrData.address) {
            $('input[name="address"]').val(ocrData.address).trigger('input');
            filled++;
        }

        if (filled > 0) {
            flash({ msg: filled + ' field(s) filled. Please verify and correct if needed.', type: 'success' });
        } else {
            flash({ msg: 'No data could be extracted. Please fill the form manually.', type: 'warning' });
        }
    });

    // Normalise various date formats to DD/MM/YYYY for the date-pick input
    function normaliseDateForPicker(str) {
        // YYYY-MM-DD or YYYY/MM/DD
        var m = str.match(/^(\d{4})[\/\-\.](\d{2})[\/\-\.](\d{2})$/);
        if (m) return m[3] + '/' + m[2] + '/' + m[1];

        // DD/MM/YYYY or DD-MM-YYYY (already correct format)
        m = str.match(/^(\d{2})[\/\-\.](\d{2})[\/\-\.](\d{4})$/);
        if (m) return m[1] + '/' + m[2] + '/' + m[3];

        return str; // return as-is and let the user correct it
    }

    // Clear OCR state
    $('#ocr-clear-btn').on('click', function () {
        ocrData = { name: '', dob: '', address: '' };
        $('#ocr-upload').val('');
        $('#ocr-preview').slideUp(150);
        $('#ocr-scan-btn').prop('disabled', true).html('<i class="bi bi-search mr-1"></i>Scan Document');
        $('#ocr-status').html('');
    });

});
</script>
@endsection
