<?php

/**
 * Application Constants
 * 
 * Centralized configuration for application-wide constants.
 * This prevents hardcoding values across the codebase.
 */

return [
    /**
     * Staff User Types
     * 
     * These are the user types that can have Employee records and access the HR module.
     * Used in:
     *   - HRController::linkUser()
     *   - HRController::syncFromUser()
     *   - HRController::syncAllUsers()
     *   - EmployeeProfileService::createFromUser()
     */
    'staff_types' => [
        'teacher',
        'hr_manager',
        'admin',
        'super_admin',
        'employee',
    ],

    /**
     * Employee Status Values
     * 
     * Valid statuses for employee records.
     * Only 'active' employees can be linked to user accounts.
     */
    'employee_statuses' => [
        'active',
        'on_leave',
        'suspended',
        'terminated',
    ],

    /**
     * Employment Types
     * 
     * Types of employment relationships.
     */
    'employment_types' => [
        'full_time',
        'part_time',
        'contract',
        'temporary',
    ],

    /**
     * Salary Currencies
     * 
     * Supported currencies for salary calculations.
     */
    'salary_currencies' => [
        'ETB' => 'Ethiopian Birr',
        'USD' => 'US Dollar',
    ],

    /**
     * Leave Types
     * 
     * Types of leave that employees can request.
     */
    'leave_types' => [
        'annual',
        'sick',
        'maternity',
        'paternity',
        'unpaid',
        'other',
    ],

    /**
     * Attendance Status
     * 
     * Possible attendance statuses for employees.
     */
    'attendance_statuses' => [
        'present',
        'absent',
        'late',
        'half_day',
        'on_leave',
    ],

    /**
     * Contract Status
     * 
     * Possible contract statuses.
     */
    'contract_statuses' => [
        'active',
        'expired',
        'terminated',
        'pending_renewal',
    ],

    /**
     * Performance Rating Scale
     * 
     * Scale for employee performance evaluations.
     */
    'performance_ratings' => [
        1 => 'Poor',
        2 => 'Below Average',
        3 => 'Average',
        4 => 'Good',
        5 => 'Excellent',
    ],

    /**
     * File Upload Limits
     * 
     * Maximum file sizes for various uploads (in bytes).
     */
    'file_limits' => [
        'certificate' => 5 * 1024 * 1024,  // 5 MB
        'photo' => 2 * 1024 * 1024,        // 2 MB
        'document' => 10 * 1024 * 1024,    // 10 MB
    ],

    /**
     * Allowed File Types
     * 
     * MIME types allowed for various uploads.
     */
    'allowed_file_types' => [
        'certificate' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'],
        'photo' => ['image/jpeg', 'image/png'],
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ],

    /**
     * Pagination
     * 
     * Default pagination settings.
     */
    'pagination' => [
        'per_page' => 15,
        'per_page_large' => 50,
    ],

    /**
     * Audit Log Actions
     * 
     * Types of actions that are logged to the audit trail.
     */
    'audit_actions' => [
        'created',
        'updated',
        'deleted',
        'viewed',
        'exported',
        'imported',
    ],
];
