@php
    // Nursery-type classes (Creche, Pre Nursery, Nursery) use descriptive grades only
    $nurseryTypeCodes = ['C', 'PN', 'N'];
    $isNursery        = isset($class_type) && in_array($class_type->code, $nurseryTypeCodes);
    $showLetterGrade  = !$isNursery;
@endphp

<table class="table table-bordered table-responsive text-center">
    <thead>
    <tr>
        <th rowspan="2">S/N</th>
        <th rowspan="2">SUBJECTS</th>
        <th rowspan="2">Assessment<br>(30)</th>
        <th rowspan="2">Mid Exam<br>(20)</th>
        <th rowspan="2">Final Exam<br>(50)</th>
        <th rowspan="2">TOTAL<br>(100)</th>
        @if($showLetterGrade)
        <th rowspan="2">GRADE</th>
        @endif
        <th rowspan="2">SUBJECT<br>POSITION</th>
        <th rowspan="2">REMARKS</th>
    </tr>
    </thead>

    <tbody>
    @foreach($subjects as $sub)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $sub->name }}</td>
            @foreach($marks->where('subject_id', $sub->id)->where('exam_id', $ex->id) as $mk)
                <td>{{ $mk->t1 ?: '-' }}</td>
                <td>{{ $mk->t2 ?: '-' }}</td>
                <td>{{ $mk->exm ?: '-' }}</td>
                <td>
                    @if($ex->term === 1) {{ $mk->tex1 ?: '-' }}
                    @elseif($ex->term === 2) {{ $mk->tex2 ?: '-' }}
                    @else {{ '-' }}
                    @endif
                </td>

                @if($showLetterGrade)
                <td>{{ $mk->grade ? $mk->grade->name : '-' }}</td>
                @endif

                <td>{!! $mk->grade ? Mk::getSuffix($mk->sub_pos) : '-' !!}</td>

                {{-- Remarks: auto-generate descriptive remark for nursery if none set --}}
                @php
                    $total  = ($mk->t1 ?? 0) + ($mk->t2 ?? 0) + ($mk->exm ?? 0);
                    $remark = $mk->grade ? $mk->grade->remark : '-';
                    if ($isNursery && $mk->grade) {
                        $remark = $mk->grade->name; // e.g. "Excellent", "Good"
                    }
                @endphp
                <td>{{ $remark }}</td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        <td colspan="{{ $showLetterGrade ? 4 : 3 }}">
            <strong>TOTAL SCORES OBTAINED: </strong> {{ $exr->total }}
        </td>
        <td colspan="3"><strong>FINAL AVERAGE: </strong> {{ $exr->ave }}</td>
        <td colspan="2"><strong>CLASS AVERAGE: </strong> {{ $exr->class_ave }}</td>
    </tr>
    </tbody>
</table>
