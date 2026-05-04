<?php

Auth::routes();

//Route::get('/test', 'TestController@index')->name('test');
Route::get('/privacy-policy', 'HomeController@privacy_policy')->name('privacy_policy');
Route::get('/terms-of-use', 'HomeController@terms_of_use')->name('terms_of_use');

// Public ICS feed ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â no auth so Google Calendar can subscribe to it
Route::get('/calendar/ics', 'CalendarController@icsPublicFeed')->name('calendar.ics');


Route::group(['middleware' => 'auth'], function () {

    Route::get('/', 'HomeController@dashboard')->name('home');
    Route::get('/home', 'HomeController@dashboard');
    Route::get('/dashboard', 'HomeController@dashboard')->name('dashboard');

    Route::group(['prefix' => 'my_account'], function() {
        Route::get('/', 'MyAccountController@edit_profile')->name('my_account');
        Route::put('/', 'MyAccountController@update_profile')->name('my_account.update');
        Route::put('/change_password', 'MyAccountController@change_pass')->name('my_account.change_pass');
    });

    /*************** Support Team *****************/
    Route::group(['namespace' => 'SupportTeam',], function(){

        /*************** Students *****************/
        Route::group(['prefix' => 'students'], function(){
            Route::get('reset_pass/{st_id}', 'StudentRecordController@reset_pass')->name('st.reset_pass');
            Route::get('graduated', 'StudentRecordController@graduated')->name('students.graduated');
            Route::put('not_graduated/{id}', 'StudentRecordController@not_graduated')->name('st.not_graduated');
            Route::get('list/{class_id}', 'StudentRecordController@listByClass')->name('students.list')->middleware('teamSAT');

            /* Bulk import */
            Route::get('bulk/template', 'StudentRecordController@bulkTemplate')->name('students.bulk.template')->middleware('teamSA');
            Route::post('bulk/import', 'StudentRecordController@bulkImport')->name('students.bulk.import')->middleware('teamSA');

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
        Route::post('bulk-import', 'UserController@bulkImport')->name('users.bulk.import')->middleware('teamSA');
        Route::get('bulk-template', 'UserController@bulkTemplate')->name('users.bulk.template')->middleware('teamSA');
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

    /************************ CALENDAR ****************************/
    Route::get('/calendar', 'CalendarController@index')->name('calendar.index');
    Route::get('/calendar/events', 'CalendarController@events')->name('calendar.events');
    Route::post('/calendar/events', 'CalendarController@store')->name('calendar.store')->middleware('teamSA');
    Route::post('/calendar/events/{rid}/update', 'CalendarController@update')->name('calendar.update')->middleware('teamSA');
    Route::post('/calendar/events/{rid}/delete', 'CalendarController@destroy')->name('calendar.destroy')->middleware('teamSA');
    // Keep PUT/DELETE for any direct API calls
    Route::put('/calendar/events/{eid}', 'CalendarController@update')->middleware('teamSA');
    Route::delete('/calendar/events/{eid}', 'CalendarController@destroy')->middleware('teamSA');

    /************************ ACADEMIC CALENDAR GENERATOR ****************************/

    // Calendar Rules ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â explicit prefix, defined BEFORE the academic-calendar/{id} wildcards
    Route::prefix('academic-calendar/rules')->middleware('teamSA')->group(function () {
        Route::get('/index',        'AcademicCalendarController@rulesIndex')->name('acal.rules');
        Route::post('/',            'AcademicCalendarController@rulesStore')->name('acal.rules.store');
        Route::post('/{rid}/update', 'AcademicCalendarController@rulesUpdate')->name('acal.rules.update');
        Route::post('/{rid}/delete', 'AcademicCalendarController@rulesDestroy')->name('acal.rules.destroy');
    });

    Route::prefix('academic-calendar')->group(function () {
        Route::get('/',                         'AcademicCalendarController@index')->name('acal.index');
        Route::post('/generate',                'AcademicCalendarController@generate')->name('acal.generate')->middleware('teamSA');
        Route::post('/{yid}/import-holidays',    'AcademicCalendarController@importHolidays')->name('acal.import_holidays')->middleware('teamSA');
        Route::post('/{yid}/resolve-conflicts',  'AcademicCalendarController@resolveConflicts')->name('acal.resolve_conflicts')->middleware('teamSA');
        Route::post('/{yid}/archive',             'AcademicCalendarController@archive')->name('acal.archive')->middleware('teamSA');
        Route::post('/{yid}/activate',             'AcademicCalendarController@activate')->name('acal.activate')->middleware('teamSA');
        Route::post('/{yid}/destroy',             'AcademicCalendarController@destroy')->name('acal.destroy')->middleware('teamSA');
        // Wildcard show MUST be last
        Route::get('/{yid}',                     'AcademicCalendarController@show')->name('acal.show');
    });

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
Route::group(['namespace' => 'SupportTeam', 'middleware' => 'hr_manager'], function(){

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
    Route::prefix('hr')->group(function(){
        Route::get('/', 'HRController@index')->name('hr.index');
        Route::get('/staff/{id}', 'HRController@show')->name('hr.show');
        Route::get('/departments', 'HRController@departments')->name('hr.departments');
        Route::post('/departments', 'HRController@storeDepartment')->name('hr.departments.store');
        Route::put('/departments/{id}', 'HRController@updateDepartment')->name('hr.departments.update');
        Route::delete('/departments/{id}', 'HRController@destroyDepartment')->name('hr.departments.destroy');
        Route::post('/assign-department/{user_id}', 'HRController@assignDepartment')->name('hr.assign_department');
        Route::get('/attendance', 'HRController@attendance')->name('hr.attendance');
        Route::post('/attendance', 'HRController@saveAttendance')->name('hr.attendance.save');
        Route::get('/workload', 'HRController@workload')->name('hr.workload');
    });
});

/************************ FINANCE MODULE ****************************/
Route::group(['namespace' => 'Finance', 'middleware' => 'hr_manager', 'prefix' => 'finance'], function () {

    // Transport Fees
    Route::get('/transport', 'TransportController@index')->name('finance.transport.index');
    Route::get('/transport/create', 'TransportController@create')->name('finance.transport.create');
    Route::post('/transport', 'TransportController@store')->name('finance.transport.store');
    Route::get('/transport/{id}/edit', 'TransportController@edit')->name('finance.transport.edit');
    Route::put('/transport/{id}', 'TransportController@update')->name('finance.transport.update');
    Route::delete('/transport/{id}', 'TransportController@destroy')->name('finance.transport.destroy');
    Route::get('/transport/{id}/records', 'TransportController@records')->name('finance.transport.records');
    Route::post('/transport/{id}/assign', 'TransportController@assignStudent')->name('finance.transport.assign');
    Route::post('/transport/pay/{id}', 'TransportController@payNow')->name('finance.transport.pay');

    // Payroll
    Route::get('/payroll', 'PayrollController@index')->name('finance.payroll.index');
    Route::get('/payroll/create', 'PayrollController@create')->name('finance.payroll.create');
    Route::post('/payroll', 'PayrollController@store')->name('finance.payroll.store');
    Route::get('/payroll/{id}/edit', 'PayrollController@edit')->name('finance.payroll.edit');
    Route::put('/payroll/{id}', 'PayrollController@update')->name('finance.payroll.update');
    Route::delete('/payroll/{id}', 'PayrollController@destroy')->name('finance.payroll.destroy');
    Route::post('/payroll/{id}/mark-paid', 'PayrollController@markPaid')->name('finance.payroll.mark_paid');
    Route::get('/payroll/{id}/payslip', 'PayrollController@payslip')->name('finance.payroll.payslip');


    // Expenses
    Route::get('/expenses', 'ExpenseController@index')->name('finance.expenses.index');
    Route::get('/expenses/create', 'ExpenseController@create')->name('finance.expenses.create');
    Route::post('/expenses', 'ExpenseController@store')->name('finance.expenses.store');
    Route::get('/expenses/{id}/edit', 'ExpenseController@edit')->name('finance.expenses.edit');
    Route::put('/expenses/{id}', 'ExpenseController@update')->name('finance.expenses.update');
    Route::delete('/expenses/{id}', 'ExpenseController@destroy')->name('finance.expenses.destroy');

    // Income
    Route::get('/income', 'IncomeController@index')->name('finance.income.index');
    Route::get('/income/create', 'IncomeController@create')->name('finance.income.create');
    Route::post('/income', 'IncomeController@store')->name('finance.income.store');
    Route::get('/income/{id}/edit', 'IncomeController@edit')->name('finance.income.edit');
    Route::put('/income/{id}', 'IncomeController@update')->name('finance.income.update');
    Route::delete('/income/{id}', 'IncomeController@destroy')->name('finance.income.destroy');

    // Finance Dashboard
    Route::get('/dashboard', 'FinanceDashboardController@index')->name('finance.dashboard');

    // Finance Reports
    Route::get('/reports', 'FinanceReportController@index')->name('finance.reports');

    // Student Fee Management
    Route::get('/fees/categories', 'StudentFeeController@categories')->name('fees.categories');
    Route::post('/fees/categories', 'StudentFeeController@storeCategory')->name('fees.categories.store');
    Route::put('/fees/categories/{id}', 'StudentFeeController@updateCategory')->name('fees.categories.update');
    Route::delete('/fees/categories/{id}', 'StudentFeeController@destroyCategory')->name('fees.categories.destroy');

    Route::get('/fees/structures', 'StudentFeeController@structures')->name('fees.structures');
    Route::post('/fees/structures', 'StudentFeeController@storeStructure')->name('fees.structures.store');
    Route::delete('/fees/structures/{id}', 'StudentFeeController@destroyStructure')->name('fees.structures.destroy');

    Route::get('/fees/invoices', 'StudentFeeController@invoices')->name('fees.invoices');
    Route::get('/fees/payments', 'StudentFeeController@payments')->name('fees.payments');
    Route::post('/fees/assign', 'StudentFeeController@assignFee')->name('fees.assign');
    Route::post('/fees/bulk-assign', 'StudentFeeController@bulkAssign')->name('fees.bulk_assign');
    Route::get('/fees/invoice/{id}', 'StudentFeeController@invoiceDetail')->name('fees.invoice');
    Route::post('/fees/invoice/{id}/pay', 'StudentFeeController@recordPayment')->name('fees.pay');
    Route::post('/fees/invoice/{id}/discount', 'StudentFeeController@applyDiscount')->name('fees.discount');
    Route::post('/fees/invoice/{id}/fine', 'StudentFeeController@applyFine')->name('fees.fine');
    Route::get('/fees/receipt/{id}', 'StudentFeeController@receipt')->name('fees.receipt');
    Route::get('/fees/pending', 'StudentFeeController@pendingList')->name('fees.pending');
    Route::get('/fees/student/{id}/history', 'StudentFeeController@studentHistory')->name('fees.student_history');

    // Finance Messages
    Route::get('/messages', 'FinanceMessageController@index')->name('finance.messages.index');
    Route::post('/messages/sms', 'FinanceMessageController@sendSms')->name('finance.messages.sms');
    Route::post('/messages/email', 'FinanceMessageController@sendEmail')->name('finance.messages.email');
    Route::post('/messages/notifications', 'FinanceMessageController@sendNotifications')->name('finance.messages.notifications');

    // Finance Settings
    Route::get('/settings', 'FinanceSettingController@index')->name('finance.settings.index');
    Route::post('/settings/expense-category', 'FinanceSettingController@storeExpenseCategory')->name('finance.settings.expense_cat');
    Route::delete('/settings/expense-category/{id}', 'FinanceSettingController@destroyExpenseCategory')->name('finance.settings.expense_cat_del');
    Route::post('/settings/income-category', 'FinanceSettingController@storeIncomeCategory')->name('finance.settings.income_cat');
    Route::delete('/settings/income-category/{id}', 'FinanceSettingController@destroyIncomeCategory')->name('finance.settings.income_cat_del');
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
