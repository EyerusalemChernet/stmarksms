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
            Route::get('/', 'AttendanceController@index')->name('attendance.index');
            Route::post('/open', 'AttendanceController@create')->name('attendance.create');
            Route::get('/manage/{session_id}', 'AttendanceController@manage')->name('attendance.manage');
            Route::post('/save/{session_id}', 'AttendanceController@store')->name('attendance.store');
            Route::get('/report/{student_id}', 'AttendanceController@report')->name('attendance.report');
            Route::get('/sessions', 'AttendanceController@sessions')->name('attendance.sessions');
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
