@extends('layouts.master')
@section('page_title', 'Finance Messages')
@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-chat-dots mr-2"></i>Send SMS Reminders</h6></div>
            <div class="card-body">
                <form action="{{ route('finance.messages.sms') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Target Group</label>
                        <select name="target" class="form-control" required>
                            <option value="unpaid">Parents with Unpaid Fees</option>
                            <option value="partial">Parents with Partial Fees</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message Content</label>
                        <textarea name="message" class="form-control" rows="4" required>Dear Parent, a reminder that your child's fee is pending. Please pay at the earliest.</textarea>
                    </div>
                    <button class="btn btn-primary btn-block">Send SMS Bulk</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-envelope mr-2"></i>Email Invoices</h6></div>
            <div class="card-body">
                <form action="{{ route('finance.messages.email') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Select Invoice Status</label>
                        <select name="status" class="form-control" required>
                            <option value="unpaid">Unpaid Invoices</option>
                            <option value="all">All Invoices</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email Template</label>
                        <select name="template" class="form-control" required>
                            <option value="default">Default Template</option>
                        </select>
                    </div>
                    <button class="btn btn-success btn-block">Send Emails</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-bell mr-2"></i>Salary Notifications</h6></div>
            <div class="card-body">
                <form action="{{ route('finance.messages.notifications') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Select Staff Group</label>
                        <select name="group" class="form-control" required>
                            <option value="all">All Staff</option>
                            <option value="teachers">Teachers Only</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="4" required>Your salary for this month has been processed and deposited into your account.</textarea>
                    </div>
                    <button class="btn btn-warning btn-block">Send Notifications</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
