<?php

Auth::routes();

//Route::get('/test', 'TestController@index')->name('test');
Route::get('/privacy-policy', 'HomeController@privacy_policy')->name('privacy_policy');
Route::get('/terms-of-use', 'HomeController@terms_of_use')->name('terms_of_use');


Route::group(['middleware' => 'auth'], function () {

    Route::get('/', 'HomeController@dashboard')->name('home');
    Route::get('/home', 'HomeController@dashboard');
    Route::get('/dashboard', 'HomeController@dashboard')->name('dashboard');

    Route::group(['prefix' => 'my_account'], function() {
        Route::get('/', 'MyAccountController@edit_profile')->name('my_account');
        Route::put('/', 'MyAccountController@update_profile')->name('my_account.update');
        Route::put('/change_password', 'MyAccountController@change_pass')->name('my_account.change_pass');
    });

    // My Performance (all authenticated staff can view their own)
    Route::get('/my-performance', 'SupportTeam\PerformanceController@myPerformance')->name('my.performance');

    // ── My Leave (self-service — all authenticated staff with an employee record) ──
    Route::prefix('my-leave')->namespace('SupportTeam')->group(function () {
        Route::get('/',          'LeaveController@myLeaveIndex')->name('my.leave.index');
        Route::get('/apply',     'LeaveController@myLeaveCreate')->name('my.leave.create');
        Route::post('/',         'LeaveController@myLeaveStore')->name('my.leave.store');
        Route::get('/{leaveId}', 'LeaveController@myLeaveShow')->name('my.leave.show');
        Route::post('/{leaveId}/cancel', 'LeaveController@myLeaveCancel')->name('my.leave.cancel');
    });

    // ── My Profile self-service portal (all authenticated staff) ─────────────
    Route::prefix('my')->namespace('SupportTeam')->group(function () {
        Route::get('/profile',              'MyProfileController@profile')->name('my.profile');
        Route::get('/payslips',             'MyProfileController@payslips')->name('my.payslips');
        Route::get('/payslips/{payrollId}', 'MyProfileController@payslip')->name('my.payslip');
        Route::get('/performance',          'MyProfileController@performance')->name('my.performance.self');
        Route::get('/jobs',                 'MyProfileController@jobBoard')->name('my.job_board');
        Route::get('/jobs/{postingId}',     'MyProfileController@jobPosting')->name('my.job_posting');
        Route::get('/jobs/{postingId}/apply', 'MyProfileController@applyForm')->name('my.job_apply');
        Route::post('/jobs/{postingId}/apply','MyProfileController@applyStore')->name('my.job_apply.store');
    });

    /*************** Support Team *****************/
    Route::group(['namespace' => 'SupportTeam',], function(){

        /*************** Students *****************/
        Route::group(['prefix' => 'students'], function(){
            Route::get('reset_pass/{st_id}', 'StudentRecordController@reset_pass')->name('st.reset_pass');
            Route::get('graduated', 'StudentRecordController@graduated')->name('students.graduated');
            Route::put('not_graduated/{id}', 'StudentRecordController@not_graduated')->name('st.not_graduated');
            Route::get('list/{class_id}', 'StudentRecordController@listByClass')->name('students.list')->middleware('teamSAT');

            /* Promotions */
            Route::post('promote_selector', 'PromotionController@selector')->name('students.promote_selector');
            Route::get('promotion/manage', 'PromotionController@manage')->name('students.promotion_manage');
            Route::delete('promotion/reset/{pid}', 'PromotionController@reset')->name('students.promotion_reset');
            Route::delete('promotion/reset_all', 'PromotionController@reset_all')->name('students.promotion_reset_all');
            Route::get('promotion/{fc?}/{fs?}/{tc?}/{ts?}', 'PromotionController@promotion')->name('students.promotion');
            Route::post('promote/{fc}/{fs}/{tc}/{ts}', 'PromotionController@promote')->name('students.promote');

        });

        /*************** Users *****************/
        Route::group(['prefix' => 'users'], function(){
            Route::get('reset_pass/{id}', 'UserController@reset_pass')->name('users.reset_pass');
        });

        /*************** TimeTables *****************/
        Route::group(['prefix' => 'timetables'], function(){
            Route::get('/', 'TimeTableController@index')->name('tt.index');

            Route::group(['middleware' => 'teamSA'], function() {
                Route::post('/', 'TimeTableController@store')->name('tt.store');
                Route::put('/{tt}', 'TimeTableController@update')->name('tt.update');
                Route::delete('/{tt}', 'TimeTableController@delete')->name('tt.delete');
            });

            /*************** TimeTable Records *****************/
            Route::group(['prefix' => 'records'], function(){

                Route::group(['middleware' => 'teamSA'], function(){
                    Route::get('manage/{ttr}', 'TimeTableController@manage')->name('ttr.manage');
                    Route::post('/', 'TimeTableController@store_record')->name('ttr.store');
                    Route::get('edit/{ttr}', 'TimeTableController@edit_record')->name('ttr.edit');
                    Route::put('/{ttr}', 'TimeTableController@update_record')->name('ttr.update');
                    Route::get('validate/{ttr}', 'TimeTableController@validateTimetable')->name('ttr.validate');
                });

                Route::get('show/{ttr}', 'TimeTableController@show_record')->name('ttr.show');
                Route::get('print/{ttr}', 'TimeTableController@print_record')->name('ttr.print');
                Route::delete('/{ttr}', 'TimeTableController@delete_record')->name('ttr.destroy');

            });

            /*************** Time Slots *****************/
            Route::group(['prefix' => 'time_slots', 'middleware' => 'teamSA'], function(){
                Route::post('/', 'TimeTableController@store_time_slot')->name('ts.store');
                Route::post('/use/{ttr}', 'TimeTableController@use_time_slot')->name('ts.use');
                Route::get('edit/{ts}', 'TimeTableController@edit_time_slot')->name('ts.edit');
                Route::delete('/{ts}', 'TimeTableController@delete_time_slot')->name('ts.destroy');
                Route::put('/{ts}', 'TimeTableController@update_time_slot')->name('ts.update');
            });

        });

        /*************** Pins *****************/
        Route::group(['prefix' => 'pins'], function(){
            Route::get('create', 'PinController@create')->name('pins.create');
            Route::get('/', 'PinController@index')->name('pins.index');
            Route::post('/', 'PinController@store')->name('pins.store');
            Route::get('enter/{id}', 'PinController@enter_pin')->name('pins.enter');
            Route::post('verify/{id}', 'PinController@verify')->name('pins.verify');
            Route::delete('/', 'PinController@destroy')->name('pins.destroy');
        });

        /*************** Marks *****************/
        Route::group(['prefix' => 'marks'], function(){

           // FOR teamSA
            Route::group(['middleware' => 'teamSA'], function(){
                Route::get('insights', 'MarkController@insights')->name('marks.insights');
                Route::get('batch_fix', 'MarkController@batch_fix')->name('marks.batch_fix');
                Route::put('batch_update', 'MarkController@batch_update')->name('marks.batch_update');
                Route::get('tabulation/{exam?}/{class?}/{sec_id?}', 'MarkController@tabulation')->name('marks.tabulation');
                Route::post('tabulation', 'MarkController@tabulation_select')->name('marks.tabulation_select');
                Route::get('tabulation/print/{exam}/{class}/{sec_id}', 'MarkController@print_tabulation')->name('marks.print_tabulation');
            });

            // FOR teamSAT
            Route::group(['middleware' => 'teamSAT'], function(){
                Route::get('/', 'MarkController@index')->name('marks.index');
                Route::get('manage/{exam}/{class}/{section}/{subject}', 'MarkController@manage')->name('marks.manage');
                Route::put('update/{exam}/{class}/{section}/{subject}', 'MarkController@update')->name('marks.update');
                Route::put('comment_update/{exr_id}', 'MarkController@comment_update')->name('marks.comment_update');
                Route::put('skills_update/{skill}/{exr_id}', 'MarkController@skills_update')->name('marks.skills_update');
                Route::post('selector', 'MarkController@selector')->name('marks.selector');
                Route::get('bulk/{class?}/{section?}', 'MarkController@bulk')->name('marks.bulk');
                Route::post('bulk', 'MarkController@bulk_select')->name('marks.bulk_select');
            });

            Route::get('select_year/{id}', 'MarkController@year_selector')->name('marks.year_selector');
            Route::post('select_year/{id}', 'MarkController@year_selected')->name('marks.year_select');
            Route::get('show/{id}/{year}', 'MarkController@show')->name('marks.show');
            Route::get('print/{id}/{exam_id}/{year}', 'MarkController@print_view')->name('marks.print');

        });

        Route::resource('students', 'StudentRecordController');
        Route::resource('users', 'UserController');
        Route::resource('classes', 'MyClassController');
        Route::resource('sections', 'SectionController');
        Route::resource('subjects', 'SubjectController');
        Route::resource('grades', 'GradeController');
        Route::resource('exams', 'ExamController');

        /*************** Attendance *****************/
        Route::group(['prefix' => 'attendance'], function(){
            // Read-only: all staff (admin, super_admin, teacher)
            Route::get('/', 'AttendanceController@index')->name('attendance.index')->middleware('teamSAT');
            Route::get('/sessions', 'AttendanceController@sessions')->name('attendance.sessions')->middleware('teamSAT');
            Route::get('/report/{student_id}', 'AttendanceController@report')->name('attendance.report')->middleware('teamSAT');
            Route::get('/risk-analysis', 'AttendanceController@riskAnalysis')->name('attendance.risk')->middleware('teamSA');

            // Write: teachers only
            Route::post('/open', 'AttendanceController@create')->name('attendance.create')->middleware('teacher');
            Route::get('/manage/{session_id}', 'AttendanceController@manage')->name('attendance.manage')->middleware('teacher');
            Route::post('/save/{session_id}', 'AttendanceController@store')->name('attendance.store')->middleware('teacher');
        });

        /*************** Library *****************/
        Route::group(['prefix' => 'library'], function(){
            Route::get('/', 'LibraryController@index')->name('library.index');
            Route::get('/create', 'LibraryController@create')->name('library.create');
            Route::post('/', 'LibraryController@store')->name('library.store');
            Route::get('/edit/{id}', 'LibraryController@edit')->name('library.edit');
            Route::put('/{id}', 'LibraryController@update')->name('library.update');
            Route::delete('/{id}', 'LibraryController@destroy')->name('library.destroy');
            Route::get('/requests', 'LibraryController@requests')->name('library.requests');
            Route::post('/request', 'LibraryController@requestBook')->name('library.request');
            Route::put('/approve/{id}', 'LibraryController@approve')->name('library.approve');
            Route::put('/reject/{id}', 'LibraryController@reject')->name('library.reject');
            Route::put('/return/{id}', 'LibraryController@returnBook')->name('library.return');
            Route::get('/history', 'LibraryController@history')->name('library.history');
        });

    });

    /************************ AJAX ****************************/
    Route::group(['prefix' => 'ajax'], function() {
        Route::get('get_lga/{state_id}', 'AjaxController@get_lga')->name('get_lga');
        Route::get('get_class_sections/{class_id}', 'AjaxController@get_class_sections')->name('get_class_sections');
        Route::get('get_class_subjects/{class_id}', 'AjaxController@get_class_subjects')->name('get_class_subjects');
    });

    /************************ AI ****************************/
    Route::post('/ai/generate-comment', 'AICommentController@generate')->name('ai.generate_comment');
    Route::post('/ai/summarize-message', 'AICommentController@summarize')->name('ai.summarize');

});

/************************ SUPER ADMIN ****************************/
Route::group(['namespace' => 'SuperAdmin','middleware' => 'super_admin', 'prefix' => 'super_admin'], function(){

    Route::get('/settings', 'SettingController@index')->name('settings');
    Route::put('/settings', 'SettingController@update')->name('settings.update');

    Route::get('/rules', 'RuleController@index')->name('rules.index');
    Route::post('/rules', 'RuleController@store')->name('rules.store');
    Route::put('/rules/{id}', 'RuleController@update')->name('rules.update');
    Route::delete('/rules/{id}', 'RuleController@destroy')->name('rules.destroy');

    Route::get('/audit-logs', 'AuditLogController@index')->name('audit.index');

});

/************************ COMMUNICATION ****************************/
Route::group(['middleware' => 'auth'], function(){
    Route::get('/announcements', 'CommunicationController@announcements')->name('announcements');
    Route::post('/announcements', 'CommunicationController@storeAnnouncement')->name('announcements.store');
    Route::delete('/announcements/{id}', 'CommunicationController@deleteAnnouncement')->name('announcements.delete');
    Route::get('/inbox', 'CommunicationController@inbox')->name('inbox');
    Route::get('/compose', 'CommunicationController@compose')->name('compose');
    Route::post('/messages', 'CommunicationController@sendMessage')->name('messages.send');
    Route::get('/messages/{message}', 'CommunicationController@readMessage')->name('messages.read');

    /************************ REPORTS ****************************/
    Route::group(['prefix' => 'reports'], function(){
        Route::get('/', 'ReportController@index')->name('reports.index');
        Route::get('/students', 'ReportController@students')->name('reports.students');
        Route::get('/attendance', 'ReportController@attendance')->name('reports.attendance');
        Route::get('/academic', 'ReportController@academic')->name('reports.academic');
        Route::get('/finance', 'ReportController@finance')->name('reports.finance');
        Route::get('/library', 'ReportController@library')->name('reports.library');
    });
});

/************************ HR MODULE + FINANCE (hr_manager only) ****************************/
Route::group(['namespace' => 'SupportTeam', 'middleware' => ['auth', 'hr_manager']], function(){

    /*************** Payments *****************/
    Route::group(['prefix' => 'payments'], function(){
        Route::get('manage/{class_id?}', 'PaymentController@manage')->name('payments.manage');
        Route::get('invoice/{id}/{year?}', 'PaymentController@invoice')->name('payments.invoice');
        Route::get('receipts/{id}', 'PaymentController@receipts')->name('payments.receipts');
        Route::get('pdf_receipts/{id}', 'PaymentController@pdf_receipts')->name('payments.pdf_receipts');
        Route::post('select_year', 'PaymentController@select_year')->name('payments.select_year');
        Route::post('select_class', 'PaymentController@select_class')->name('payments.select_class');
        Route::delete('reset_record/{id}', 'PaymentController@reset_record')->name('payments.reset_record');
        Route::post('pay_now/{id}', 'PaymentController@pay_now')->name('payments.pay_now');
    });
    Route::resource('payments', 'PaymentController');

    /*************** HR *****************/
    Route::prefix('hr')->middleware(['auth', 'hr_manager'])->group(function(){
        Route::get('/', 'HRController@index')->name('hr.index');
        Route::get('/staff/{hrId}', 'HRController@show')->name('hr.show');

        // Employee create
        Route::get('/employees/create', 'HRController@createEmployee')->name('hr.employees.create');
        Route::post('/employees', 'HRController@storeEmployee')->name('hr.employees.store');

        // Employee profile
        Route::get('/employees/{hrId}/edit', 'HRController@editProfile')->name('hr.profile.edit');
        Route::put('/employees/{hrId}/profile', 'HRController@updateProfile')->name('hr.profile.update');
        Route::post('/employees/{hrId}/terminate', 'HRController@terminateEmployee')->name('hr.terminate');
        Route::post('/employees/{hrId}/reactivate', 'HRController@reactivateEmployee')->name('hr.reactivate');
        Route::post('/employees/{hrId}/status', 'HRController@changeEmployeeStatus')->name('hr.status.change');
        Route::post('/employees/{hrId}/qualifications', 'HRController@addQualification')->name('hr.qualification.add');
        Route::delete('/employees/{hrId}/qualifications', 'HRController@deleteQualification')->name('hr.qualification.delete');

        // Salary & shift assignment
        Route::post('/employees/{hrId}/salary', 'HRController@assignSalary')->name('hr.assign_salary');
        Route::post('/employees/{hrId}/shift', 'HRController@assignShift')->name('hr.assign_shift');

        // Departments
        Route::get('/departments', 'HRController@departments')->name('hr.departments');
        Route::post('/departments', 'HRController@storeDepartment')->name('hr.departments.store');
        Route::put('/departments/{hrId}', 'HRController@updateDepartment')->name('hr.departments.update');
        Route::delete('/departments/{hrId}', 'HRController@destroyDepartment')->name('hr.departments.destroy');

        // Positions
        Route::get('/positions', 'HRController@positions')->name('hr.positions');
        Route::post('/positions', 'HRController@storePosition')->name('hr.positions.store');
        Route::put('/positions/{hrId}', 'HRController@updatePosition')->name('hr.positions.update');
        Route::delete('/positions/{hrId}', 'HRController@destroyPosition')->name('hr.positions.destroy');
        Route::get('/positions/by-department/{departmentId}', 'HRController@positionsByDepartment')->name('hr.positions.by_department');

        // Shifts
        Route::get('/shifts', 'HRController@shifts')->name('hr.shifts');
        Route::post('/shifts', 'HRController@storeShift')->name('hr.shifts.store');
        Route::put('/shifts/{hrId}', 'HRController@updateShift')->name('hr.shifts.update');
        Route::delete('/shifts/{hrId}', 'HRController@destroyShift')->name('hr.shifts.destroy');

        // Attendance
        Route::get('/attendance', 'HRController@attendance')->name('hr.attendance');
        Route::post('/attendance', 'HRController@saveAttendance')->name('hr.attendance.save');
        Route::get('/attendance/report/{hrId}', 'HRController@attendanceReport')->name('hr.attendance.report');

        // Payroll
        Route::get('/payroll', 'HRController@payroll')->name('hr.payroll');
        Route::post('/payroll/generate', 'HRController@generatePayroll')->name('hr.payroll.generate');
        Route::get('/payroll/{hrId}/edit', 'HRController@editPayroll')->name('hr.payroll.edit');
        Route::put('/payroll/{hrId}', 'HRController@updatePayroll')->name('hr.payroll.update');
        Route::post('/payroll/{hrId}/items', 'HRController@addPayrollItem')->name('hr.payroll.item.add');
        Route::delete('/payroll/{hrId}/items', 'HRController@removePayrollItem')->name('hr.payroll.item.remove');
        Route::post('/payroll/{hrId}/approve', 'HRController@approvePayroll')->name('hr.payroll.approve');
        Route::post('/payroll/{hrId}/paid', 'HRController@markPayrollPaid')->name('hr.payroll.paid');
        Route::post('/payroll/{hrId}/draft', 'HRController@revertPayrollToDraft')->name('hr.payroll.draft');

        // Workload
        Route::get('/workload', 'HRController@workload')->name('hr.workload');

        // ── Recruitment ──────────────────────────────────────────────────────
        Route::prefix('recruitment')->group(function () {
            Route::get('/postings', 'RecruitmentController@postings')->name('hr.recruitment.postings');
            Route::post('/postings', 'RecruitmentController@storePosting')->name('hr.recruitment.postings.store');
            Route::get('/postings/{hrId}/edit', 'RecruitmentController@editPosting')->name('hr.recruitment.postings.edit');
            Route::put('/postings/{hrId}', 'RecruitmentController@updatePosting')->name('hr.recruitment.postings.update');
            Route::delete('/postings/{hrId}', 'RecruitmentController@destroyPosting')->name('hr.recruitment.postings.destroy');

            Route::get('/applications', 'RecruitmentController@applications')->name('hr.recruitment.applications');
            Route::get('/applications/create/{postingId?}', 'RecruitmentController@createApplication')->name('hr.recruitment.applications.create');
            Route::post('/applications', 'RecruitmentController@storeApplication')->name('hr.recruitment.applications.store');
            Route::get('/applications/{hrId}', 'RecruitmentController@showApplication')->name('hr.recruitment.applications.show');
            Route::post('/applications/{hrId}/status', 'RecruitmentController@updateStatus')->name('hr.recruitment.applications.status');
            Route::post('/applications/{hrId}/note', 'RecruitmentController@addNote')->name('hr.recruitment.applications.note');
            Route::get('/applications/{hrId}/convert', 'RecruitmentController@convertToEmployee')->name('hr.recruitment.applications.convert');
        });

        // ── Performance ──────────────────────────────────────────────────────
        Route::prefix('performance')->group(function () {
            Route::get('/categories', 'PerformanceController@categories')->name('hr.performance.categories');
            Route::post('/categories', 'PerformanceController@storeCategory')->name('hr.performance.categories.store');
            Route::put('/categories/{hrId}', 'PerformanceController@updateCategory')->name('hr.performance.categories.update');
            Route::delete('/categories/{hrId}', 'PerformanceController@destroyCategory')->name('hr.performance.categories.destroy');

            Route::get('/reviews', 'PerformanceController@reviews')->name('hr.performance.reviews');
            Route::get('/reviews/create', 'PerformanceController@createReview')->name('hr.performance.reviews.create');
            Route::post('/reviews', 'PerformanceController@storeReview')->name('hr.performance.reviews.store');
            Route::get('/reviews/{hrId}', 'PerformanceController@showReview')->name('hr.performance.reviews.show');
            Route::get('/reviews/{hrId}/edit', 'PerformanceController@editReview')->name('hr.performance.reviews.edit');
            Route::put('/reviews/{hrId}', 'PerformanceController@updateReview')->name('hr.performance.reviews.update');
            Route::delete('/reviews/{hrId}', 'PerformanceController@destroyReview')->name('hr.performance.reviews.destroy');

            Route::get('/employee/{employeeId}', 'PerformanceController@employeeHistory')->name('hr.performance.employee');
        });

        // ── Leave Management ─────────────────────────────────────────────────
        Route::prefix('leave')->group(function () {
            Route::get('/policies', 'LeaveController@policies')->name('hr.leave.policies');
            Route::post('/policies', 'LeaveController@storePolicy')->name('hr.leave.policies.store');
            Route::delete('/policies/{hrId}', 'LeaveController@destroyPolicy')->name('hr.leave.policies.destroy');
            Route::post('/policies/init-balances', 'LeaveController@initBalances')->name('hr.leave.init_balances');

            Route::get('/requests', 'LeaveController@requests')->name('hr.leave.requests');
            Route::get('/requests/create', 'LeaveController@createRequest')->name('hr.leave.requests.create');
            Route::post('/requests', 'LeaveController@storeRequest')->name('hr.leave.requests.store');
            Route::get('/requests/{hrId}', 'LeaveController@showRequest')->name('hr.leave.requests.show');
            Route::post('/requests/{hrId}/approve', 'LeaveController@approveRequest')->name('hr.leave.requests.approve');
            Route::post('/requests/{hrId}/reject', 'LeaveController@rejectRequest')->name('hr.leave.requests.reject');
            Route::post('/requests/{hrId}/cancel', 'LeaveController@cancelRequest')->name('hr.leave.requests.cancel');

            Route::get('/balances', 'LeaveController@balances')->name('hr.leave.balances');
            Route::get('/balances/{employee_id}', 'LeaveController@employeeBalance')->name('hr.leave.employee_balance');
        });
    });
});

/************************ CHAPA PAYMENT ****************************/
Route::group(['namespace' => 'SupportTeam', 'middleware' => 'auth'], function(){
    Route::post('/chapa/initiate/{pr_id}', 'ChapaController@initiate')->name('chapa.initiate');
    Route::get('/chapa/return/{pr_id}', 'ChapaController@returnUrl')->name('chapa.return');
    Route::post('/chapa/callback', 'ChapaController@callback')->name('chapa.callback');
});

/************************ PARENT ****************************/
Route::group(['namespace' => 'MyParent', 'middleware' => 'my_parent'], function () {
    Route::get('/parent/dashboard', 'MyController@dashboard')->name('parent.dashboard');
    Route::get('/parent/child/{student_id}', 'MyController@childDetail')->name('parent.child');
    Route::get('/parent/child/{student_id}/timeline', 'MyController@timeline')->name('parent.timeline');
    Route::get('/my_children', 'MyController@children')->name('my_children'); // legacy redirect
});
