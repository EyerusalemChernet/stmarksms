@php
    $nurseryTypeCodes = ['C', 'PN', 'N'];
    $isNursery        = isset($class_type) && in_array($class_type->code, $nurseryTypeCodes);
    $showLetterGrade  = !$isNursery;
@endphp

{{-- Student info header --}}
<table style="width:100%; border-collapse:collapse;">
    <tbody>
    <tr>
        <td><strong>NAME:</strong> {{ strtoupper($sr->user->name) }}</td>
        <td><strong>ADM NO:</strong> {{ $sr->adm_no }}</td>
        <td><strong>RELIGION:</strong> {{ strtoupper($sr->religion ?? '-') }}</td>
        <td><strong>CLASS:</strong> {{ strtoupper($my_class->name) }}</td>
    </tr>
    <tr>
        <td><strong>REPORT SHEET FOR</strong> SEMESTER {!! $ex->term !!}</td>
        <td><strong>ACADEMIC YEAR:</strong> {{ $ex->year }}</td>
        <td><strong>AGE:</strong> {{ $sr->age ?: ($sr->user->dob ? date_diff(date_create($sr->user->dob), date_create('now'))->y : '-') }}</td>
    </tr>
    </tbody>
</table>

{{-- Marks table --}}
<table style="width:100%; border-collapse:collapse; border:1px solid #000; margin:10px auto;" border="1">
    <thead>
    <tr>
        <th rowspan="2">SUBJECTS</th>
        <th colspan="2">CONTINUOUS ASSESSMENT</th>
        <th rowspan="2">Final Exam (50)</th>
        <th rowspan="2">TOTAL (100)</th>
        @if($showLetterGrade)
        <th rowspan="2">GRADE</th>
        @endif
        <th rowspan="2">SUBJECT POSITION</th>
        <th rowspan="2">REMARKS</th>
    </tr>
    <tr>
        <th>Assessment (30)</th>
        <th>Mid Exam (20)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($subjects as $sub)
        <tr>
            <td style="font-weight:bold;">{{ $sub->name }}</td>
            @foreach($marks->where('subject_id', $sub->id)->where('exam_id', $ex->id) as $mk)
                <td>{{ $mk->t1 ?: '-' }}</td>
                <td>{{ $mk->t2 ?: '-' }}</td>
                <td>{{ $mk->exm ?: '-' }}</td>
                <td>{{ $mk->$tex ?: '-' }}</td>
                @if($showLetterGrade)
                <td>{{ $mk->grade ? $mk->grade->name : '-' }}</td>
                @endif
                <td>{!! $mk->grade ? Mk::getSuffix($mk->sub_pos) : '-' !!}</td>
                @php
                    $remark = $mk->grade ? $mk->grade->remark : '-';
                    if ($isNursery && $mk->grade) {
                        $remark = $mk->grade->name; // "Excellent", "Good", etc.
                    }
                @endphp
                <td>{{ $remark }}</td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        <td colspan="{{ $showLetterGrade ? 4 : 3 }}"><strong>TOTAL SCORES OBTAINED:</strong> {{ $exr->total }}</td>
        <td colspan="3"><strong>FINAL AVERAGE:</strong> {{ $exr->ave }}</td>
        <td colspan="{{ $showLetterGrade ? 2 : 2 }}"><strong>CLASS AVERAGE:</strong> {{ $exr->class_ave }}</td>
    </tr>
    </tbody>
</table>
