{{-- Bulk paste marks into the grid below (Manage Marks page only) --}}
<div id="bulk-marks-panel" style="
    border-radius: 10px;
    border: 1.5px solid #059669;
    background: #fff;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
">
    <div style="background: linear-gradient(135deg,#059669,#10b981); padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="background:rgba(255,255,255,.25);border-radius:8px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-table" style="font-size:20px;color:#fff;"></i>
            </div>
            <div>
                <div style="color:#fff;font-weight:700;font-size:14px;">Bulk Insert Marks</div>
                <div style="color:rgba(255,255,255,.85);font-size:12px;margin-top:2px;">
                    Paste rows from Excel — one student per line. Section is already set from your selection above.
                </div>
            </div>
        </div>
        <button type="button" id="toggle-bulk-marks-btn" class="btn btn-sm btn-light font-weight-bold">
            <i class="bi bi-chevron-down mr-1"></i>Show
        </button>
    </div>
    <div id="bulk-marks-body" class="d-none" style="padding:18px;">
        <p style="font-size:12px;color:#64748b;margin-bottom:10px;">
            <strong>Format:</strong> <code>ADM_NO</code> then assessment (0–30), mid exam (0–20), final exam (0–50).
            Use comma or tab between values. Example:<br>
            <code>STM-2026-0001, 28, 18, 45</code>
        </p>
        @if($hasComponents ?? false)
        <div class="alert alert-warning py-2 mb-3" style="font-size:12px;">
            Assessment breakdown is active — bulk paste fills the <strong>total assessment (30)</strong> only. Enter component scores manually if needed.
        </div>
        @endif
        <textarea id="bulk-marks-text" class="form-control" rows="6" placeholder="STM-2026-0001, 28, 18, 45&#10;STM-2026-0002	25	15	40"></textarea>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <button type="button" id="apply-bulk-marks-btn" class="btn btn-success btn-sm">
                <i class="bi bi-check2-all mr-1"></i>Apply to Table
            </button>
            <button type="button" id="clear-bulk-marks-text-btn" class="btn btn-outline-secondary btn-sm">Clear</button>
            <span id="bulk-marks-status" class="text-muted small ml-2"></span>
        </div>
    </div>
</div>
<script>
(function ($) {
    $('#toggle-bulk-marks-btn').on('click', function () {
        var $body = $('#bulk-marks-body');
        $body.toggleClass('d-none');
        $(this).html($body.hasClass('d-none')
            ? '<i class="bi bi-chevron-down mr-1"></i>Show'
            : '<i class="bi bi-chevron-up mr-1"></i>Hide');
    });

    function admMap() {
        var map = {};
        $('table tbody tr[data-mark-id]').each(function () {
            var adm = $(this).find('td .badge').text().trim();
            if (adm) map[adm.toUpperCase()] = $(this);
        });
        return map;
    }

    function setMark($row, assessment, mid, final) {
        if (!$row.length) return false;
        if (assessment !== '' && assessment != null) {
            var $a = $row.find('.assessment-input');
            if ($a.length) $a.val(assessment);
            else $row.find('.assessment-total-display').val(assessment);
        }
        if (mid !== '' && mid != null) $row.find('.mid-exam-input').val(mid);
        if (final !== '' && final != null) $row.find('.final-exam-input').val(final);
        return true;
    }

    $('#apply-bulk-marks-btn').on('click', function () {
        var text = $('#bulk-marks-text').val().trim();
        if (!text) {
            $('#bulk-marks-status').html('<span class="text-danger">Paste at least one row.</span>');
            return;
        }
        var map = admMap();
        var lines = text.split(/\r?\n/).filter(function (l) { return l.trim(); });
        var applied = 0, missed = [];

        lines.forEach(function (line, idx) {
            var parts = line.split(/[\t,;]+/).map(function (p) { return p.trim(); });
            if (parts.length < 2) return;
            var adm = parts[0].toUpperCase();
            var t1 = parts[1] !== '' ? Math.min(30, Math.max(0, parseInt(parts[1], 10) || 0)) : '';
            var t2 = parts.length > 2 && parts[2] !== '' ? Math.min(20, Math.max(0, parseInt(parts[2], 10) || 0)) : '';
            var ex = parts.length > 3 && parts[3] !== '' ? Math.min(50, Math.max(0, parseInt(parts[3], 10) || 0)) : '';
            if (map[adm] && setMark(map[adm], t1, t2, ex)) applied++;
            else missed.push(adm || ('line ' + (idx + 1)));
        });

        var msg = applied + ' row(s) updated.';
        if (missed.length) msg += ' Not found: ' + missed.slice(0, 5).join(', ') + (missed.length > 5 ? '…' : '');
        $('#bulk-marks-status').html('<span class="' + (applied ? 'text-success' : 'text-danger') + '">' + msg + '</span>');
    });

    $('#clear-bulk-marks-text-btn').on('click', function () {
        $('#bulk-marks-text').val('');
        $('#bulk-marks-status').text('');
    });
})(jQuery);
</script>
