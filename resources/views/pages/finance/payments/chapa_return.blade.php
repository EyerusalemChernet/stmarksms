@extends('layouts.master')
@section('page_title', 'Payment Return')
@section('content')

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body py-5">
                <i class="bi bi-hourglass-split text-warning" style="font-size:48px;"></i>
                <h5 class="mt-3">Processing payment</h5>
                <p class="text-muted">If you are not redirected automatically, open your fee invoice from the menu.</p>
                <a href="{{ route('fees.invoices') }}" class="btn btn-primary btn-sm mt-2">Go to Invoices</a>
            </div>
        </div>
    </div>
</div>
@endsection
