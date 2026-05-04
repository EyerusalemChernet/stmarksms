<div class="sidebar sidebar-dark sidebar-main sidebar-expand-md">

    <div class="sidebar-mobile-toggler text-center">
        <a href="#" class="sidebar-mobile-main-toggle"><i class="bi bi-arrow-left"></i></a>
        Navigation
        <a href="#" class="sidebar-mobile-expand">
            <i class="icon-screen-full"></i><i class="icon-screen-normal"></i>
        </a>
    </div>

    <div class="sidebar-content">

        {{-- User block --}}
        <div class="sidebar-user">
            <div class="card-body" style="padding:14px 16px;">
                <div class="media d-flex align-items-center" style="gap:10px;">
                    <a href="{{ route('my_account') }}">
                        <img src="{{ Auth::user()->photo }}" width="40" height="40" class="rounded-circle"
                             style="object-fit:cover;border:2px solid rgba(255,255,255,.2);" alt="photo">
                    </a>
                    <div class="media-body" style="min-width:0;">
                        <div class="media-title font-weight-semibold"
                             style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Auth::user()->name }}</div>
                        <div style="font-size:11px;color:rgba(255,255,255,.45);">
                            <i class="bi bi-person-badge mr-1"></i>{{ ucwords(str_replace('_',' ', Auth::user()->user_type)) }}
                        </div>
                    </div>
                    <a href="{{ route('my_account') }}" class="text-white" style="opacity:.5;font-size:14px;"><i class="bi bi-gear"></i></a>
                </div>
            </div>
        </div>

        <div class="card card-sidebar-mobile">
            <ul class="nav nav-sidebar" data-nav-type="accordion">

                {{-- Dashboard --}}
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                    </a>
                </li>

                {{-- ════════════════════════════════════════════════════════════
                     ADMIN / SUPER ADMIN MENU
                     ════════════════════════════════════════════════════════════ --}}
                @if(Qs::userIsTeamSA())

                {{-- Students --}}
                <li class="sidebar-section-label">Students</li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.create','students.list','students.edit','students.show','students.promotion','students.promotion_manage','students.graduated']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-people"></i><span>Students</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('students.create') }}" class="nav-link {{ Route::is('students.create') ? 'active' : '' }}"><i class="bi bi-person-plus mr-1"></i>Admit Student</a></li>
                        <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.list','students.edit','students.show']) ? 'nav-item-expanded' : '' }}">
                            <a href="#" class="nav-link"><i class="bi bi-list-ul mr-1"></i>Student List</a>
                            <ul class="nav nav-group-sub">
                                @foreach(App\Models\MyClass::orderBy('name')->get() as $c)
                                <li class="nav-item"><a href="{{ route('students.list', $c->id) }}" class="nav-link">{{ $c->name }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                        <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.promotion','students.promotion_manage']) ? 'nav-item-expanded' : '' }}">
                            <a href="#" class="nav-link"><i class="bi bi-arrow-up-circle mr-1"></i>Promotion</a>
                            <ul class="nav nav-group-sub">
                                <li class="nav-item"><a href="{{ route('students.promotion') }}" class="nav-link {{ Route::is('students.promotion') ? 'active' : '' }}">Promote Students</a></li>
                                <li class="nav-item"><a href="{{ route('students.promotion_manage') }}" class="nav-link {{ Route::is('students.promotion_manage') ? 'active' : '' }}">Manage Promotions</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a href="{{ route('students.graduated') }}" class="nav-link {{ Route::is('students.graduated') ? 'active' : '' }}"><i class="bi bi-mortarboard mr-1"></i>Graduated</a></li>
                    </ul>
                </li>

                {{-- Academics --}}
                <li class="sidebar-section-label">Academics</li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['exams.index','exams.edit','grades.index','grades.edit','marks.index','marks.manage','marks.bulk','marks.tabulation','marks.show','marks.batch_fix']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-journal-check"></i><span>Exams & Marks</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('exams.index') }}" class="nav-link {{ Route::is('exams.index') ? 'active' : '' }}">Exam List</a></li>
                        <li class="nav-item"><a href="{{ route('grades.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['grades.index','grades.edit']) ? 'active' : '' }}">Grades</a></li>
                        <li class="nav-item"><a href="{{ route('marks.tabulation') }}" class="nav-link {{ Route::is('marks.tabulation') ? 'active' : '' }}">Tabulation Sheet</a></li>
                        <li class="nav-item"><a href="{{ route('marks.batch_fix') }}" class="nav-link {{ Route::is('marks.batch_fix') ? 'active' : '' }}">Batch Fix</a></li>
                        <li class="nav-item"><a href="{{ route('marks.index') }}" class="nav-link {{ Route::is('marks.index') ? 'active' : '' }}">Enter Marks</a></li>
                        <li class="nav-item"><a href="{{ route('marks.bulk') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['marks.bulk','marks.show']) ? 'active' : '' }}">Marksheet</a></li>
                    </ul>
                </li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['tt.index','ttr.edit','ttr.show','ttr.manage']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-calendar-week"></i><span>Timetable</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('tt.index') }}" class="nav-link {{ Route::is('tt.index') ? 'active' : '' }}">View Timetables</a></li>
                    </ul>
                </li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['attendance.index','attendance.manage','attendance.sessions','attendance.report']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-clipboard-check"></i><span>Attendance</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('attendance.index') }}" class="nav-link {{ Route::is('attendance.index') ? 'active' : '' }}">Mark Attendance</a></li>
                        <li class="nav-item"><a href="{{ route('attendance.sessions') }}" class="nav-link {{ Route::is('attendance.sessions') ? 'active' : '' }}">All Sessions</a></li>
                    </ul>
                </li>

                {{-- Administration --}}
                <li class="sidebar-section-label">Administration</li>
                <li class="nav-item"><a href="{{ route('users.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['users.index','users.show','users.edit']) ? 'active' : '' }}"><i class="bi bi-person-lines-fill"></i><span>Users</span></a></li>
                <li class="nav-item"><a href="{{ route('classes.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['classes.index','classes.edit']) ? 'active' : '' }}"><i class="bi bi-grid-3x3-gap"></i><span>Classes</span></a></li>
                <li class="nav-item"><a href="{{ route('sections.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['sections.index','sections.edit']) ? 'active' : '' }}"><i class="bi bi-diagram-3"></i><span>Sections</span></a></li>
                <li class="nav-item"><a href="{{ route('subjects.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['subjects.index','subjects.edit']) ? 'active' : '' }}"><i class="bi bi-book"></i><span>Subjects</span></a></li>

                {{-- Library --}}
                <li class="sidebar-section-label">Library</li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['library.index','library.create','library.requests','library.history']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-bookshelf"></i><span>Library</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('library.index') }}" class="nav-link {{ Route::is('library.index') ? 'active' : '' }}">Books</a></li>
                        <li class="nav-item"><a href="{{ route('library.create') }}" class="nav-link {{ Route::is('library.create') ? 'active' : '' }}">Add Book</a></li>
                        <li class="nav-item"><a href="{{ route('library.requests') }}" class="nav-link {{ Route::is('library.requests') ? 'active' : '' }}">Borrow Requests</a></li>
                        <li class="nav-item"><a href="{{ route('library.history') }}" class="nav-link {{ Route::is('library.history') ? 'active' : '' }}">History</a></li>
                    </ul>
                </li>

                {{-- Analytics --}}
                @php $reportsActive = str_starts_with(Route::currentRouteName() ?? '', 'reports.'); @endphp
                <li class="sidebar-section-label">Analytics</li>
                <li class="nav-item nav-item-submenu {{ $reportsActive ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-bar-chart-line"></i><span>Reports</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('reports.index') }}" class="nav-link {{ Route::is('reports.index') ? 'active' : '' }}">Overview</a></li>
                        <li class="nav-item"><a href="{{ route('reports.students') }}" class="nav-link {{ Route::is('reports.students') ? 'active' : '' }}">Students</a></li>
                        <li class="nav-item"><a href="{{ route('reports.attendance') }}" class="nav-link {{ Route::is('reports.attendance') ? 'active' : '' }}">Attendance</a></li>
                        <li class="nav-item"><a href="{{ route('reports.academic') }}" class="nav-link {{ Route::is('reports.academic') ? 'active' : '' }}">Academic</a></li>
                        <li class="nav-item"><a href="{{ route('reports.library') }}" class="nav-link {{ Route::is('reports.library') ? 'active' : '' }}">Library</a></li>
                    </ul>
                </li>

                {{-- Settings --}}
                <li class="sidebar-section-label">Settings</li>
                <li class="nav-item"><a href="{{ route('rules.index') }}" class="nav-link {{ Route::is('rules.index') ? 'active' : '' }}"><i class="bi bi-sliders"></i><span>Rules Engine</span></a></li>
                <li class="nav-item"><a href="{{ route('audit.index') }}" class="nav-link {{ Route::is('audit.index') ? 'active' : '' }}"><i class="bi bi-journal-text"></i><span>Audit Logs</span></a></li>
                @if(Qs::userIsSuperAdmin())
                <li class="nav-item"><a href="{{ route('settings') }}" class="nav-link {{ Route::is('settings') ? 'active' : '' }}"><i class="bi bi-gear"></i><span>System Settings</span></a></li>
                @endif

                @endif {{-- end teamSA --}}

                {{-- ════════════════════════════════════════════════════════════
                     TEACHER MENU
                     ════════════════════════════════════════════════════════════ --}}
                @if(Qs::userIsTeacher())

                <li class="sidebar-section-label">Academics</li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['marks.index','marks.manage','marks.bulk','marks.show']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-journal-check"></i><span>Exams & Marks</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('marks.index') }}" class="nav-link {{ Route::is('marks.index') ? 'active' : '' }}">Enter Marks</a></li>
                        <li class="nav-item"><a href="{{ route('marks.bulk') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['marks.bulk','marks.show']) ? 'active' : '' }}">Marksheet</a></li>
                    </ul>
                </li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['tt.index','ttr.show']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-calendar-week"></i><span>Timetable</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('tt.index') }}" class="nav-link {{ Route::is('tt.index') ? 'active' : '' }}">View Timetables</a></li>
                    </ul>
                </li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['attendance.index','attendance.manage','attendance.sessions','attendance.report']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-clipboard-check"></i><span>Attendance</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('attendance.index') }}" class="nav-link {{ Route::is('attendance.index') ? 'active' : '' }}">Mark Attendance</a></li>
                        <li class="nav-item"><a href="{{ route('attendance.sessions') }}" class="nav-link {{ Route::is('attendance.sessions') ? 'active' : '' }}">All Sessions</a></li>
                    </ul>
                </li>

                <li class="sidebar-section-label">Students</li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['students.list','students.show']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-people"></i><span>Student List</span></a>
                    <ul class="nav nav-group-sub">
                        @foreach(App\Models\MyClass::orderBy('name')->get() as $c)
                        <li class="nav-item"><a href="{{ route('students.list', $c->id) }}" class="nav-link">{{ $c->name }}</a></li>
                        @endforeach
                    </ul>
                </li>

                <li class="sidebar-section-label">Library</li>
                <li class="nav-item"><a href="{{ route('library.index') }}" class="nav-link {{ Route::is('library.index') ? 'active' : '' }}"><i class="bi bi-bookshelf"></i><span>Library</span></a></li>

                <li class="sidebar-section-label">My HR</li>
                <li class="nav-item">
                    <a href="{{ route('my.profile') }}" class="nav-link {{ Route::is('my.profile') ? 'active' : '' }}">
                        <i class="bi bi-person-badge"></i><span>My Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('my.performance.self') }}" class="nav-link {{ Route::is('my.performance.self') || Route::is('my.performance') ? 'active' : '' }}">
                        <i class="bi bi-star"></i><span>My Performance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('my.payslips') }}" class="nav-link {{ Route::is('my.payslips') || Route::is('my.payslip') ? 'active' : '' }}">
                        <i class="bi bi-cash-stack"></i><span>My Payslips</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('my.leave.index') }}" class="nav-link {{ Route::is('my.leave.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-heart"></i><span>My Leave</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('my.job_board') }}" class="nav-link {{ Route::is('my.job_board') || Route::is('my.job_posting') || Route::is('my.job_apply') ? 'active' : '' }}">
                        <i class="bi bi-briefcase"></i><span>Job Board</span>
                    </a>
                </li>

                @endif {{-- end teacher --}}

                {{-- ════════════════════════════════════════════════════════════
                     HR MANAGER MENU
                     ════════════════════════════════════════════════════════════ --}}
                @if(Qs::userIsHRManager())

                @php $hrActive = str_starts_with(Route::currentRouteName() ?? '', 'hr.'); @endphp
                <li class="sidebar-section-label">Staff Management</li>
                <li class="nav-item nav-item-submenu {{ $hrActive ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-person-badge"></i><span>HR</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('hr.index') }}" class="nav-link {{ Route::is('hr.index') ? 'active' : '' }}"><i class="bi bi-people mr-1"></i>Staff List</a></li>
                        <li class="nav-item"><a href="{{ route('hr.employees.create') }}" class="nav-link {{ Route::is('hr.employees.create') ? 'active' : '' }}"><i class="bi bi-person-plus mr-1"></i>Add Employee</a></li>
                        <li class="nav-item"><a href="{{ route('hr.departments') }}" class="nav-link {{ Route::is('hr.departments') ? 'active' : '' }}"><i class="bi bi-building mr-1"></i>Departments</a></li>
                        <li class="nav-item"><a href="{{ route('hr.positions') }}" class="nav-link {{ Route::is('hr.positions') ? 'active' : '' }}"><i class="bi bi-briefcase mr-1"></i>Positions</a></li>
                        <li class="nav-item"><a href="{{ route('hr.shifts') }}" class="nav-link {{ Route::is('hr.shifts') ? 'active' : '' }}"><i class="bi bi-clock mr-1"></i>Shifts</a></li>
                        <li class="nav-item"><a href="{{ route('hr.attendance') }}" class="nav-link {{ Route::is('hr.attendance') || Route::is('hr.attendance.report') ? 'active' : '' }}"><i class="bi bi-clipboard-check mr-1"></i>Staff Attendance</a></li>
                        <li class="nav-item"><a href="{{ route('hr.payroll') }}" class="nav-link {{ Route::is('hr.payroll') || Route::is('hr.payroll.edit') ? 'active' : '' }}"><i class="bi bi-cash-stack mr-1"></i>Payroll</a></li>
                        <li class="nav-item"><a href="{{ route('hr.workload') }}" class="nav-link {{ Route::is('hr.workload') ? 'active' : '' }}"><i class="bi bi-bar-chart mr-1"></i>Workload</a></li>
                        @php $leaveActive = str_starts_with(Route::currentRouteName() ?? '', 'hr.leave.'); @endphp
                        <li class="nav-item nav-item-submenu {{ $leaveActive ? 'nav-item-expanded' : '' }}">
                            <a href="#" class="nav-link {{ $leaveActive ? 'active' : '' }}">
                                <i class="bi bi-calendar-x mr-1"></i>Leave Management
                            </a>
                            <ul class="nav nav-group-sub">
                                <li class="nav-item"><a href="{{ route('hr.leave.requests') }}" class="nav-link {{ Route::is('hr.leave.requests') || Route::is('hr.leave.requests.show') ? 'active' : '' }}">Requests</a></li>
                                <li class="nav-item"><a href="{{ route('hr.leave.balances') }}" class="nav-link {{ Route::is('hr.leave.balances') || Route::is('hr.leave.employee_balance') ? 'active' : '' }}">Balances</a></li>
                                <li class="nav-item"><a href="{{ route('hr.leave.policies') }}" class="nav-link {{ Route::is('hr.leave.policies') ? 'active' : '' }}">Policies</a></li>
                            </ul>
                        </li>
                        @php $recruitActive = str_starts_with(Route::currentRouteName() ?? '', 'hr.recruitment.'); @endphp
                        <li class="nav-item nav-item-submenu {{ $recruitActive ? 'nav-item-expanded' : '' }}">
                            <a href="#" class="nav-link {{ $recruitActive ? 'active' : '' }}">
                                <i class="bi bi-person-plus mr-1"></i>Recruitment
                            </a>
                            <ul class="nav nav-group-sub">
                                <li class="nav-item"><a href="{{ route('hr.recruitment.postings') }}" class="nav-link {{ Route::is('hr.recruitment.postings') ? 'active' : '' }}">Job Postings</a></li>
                                <li class="nav-item"><a href="{{ route('hr.recruitment.applications') }}" class="nav-link {{ Route::is('hr.recruitment.applications') || Route::is('hr.recruitment.applications.show') ? 'active' : '' }}">Applications</a></li>
                            </ul>
                        </li>
                        @php $perfActive = str_starts_with(Route::currentRouteName() ?? '', 'hr.performance.'); @endphp
                        <li class="nav-item nav-item-submenu {{ $perfActive ? 'nav-item-expanded' : '' }}">
                            <a href="#" class="nav-link {{ $perfActive ? 'active' : '' }}">
                                <i class="bi bi-star mr-1"></i>Performance
                            </a>
                            <ul class="nav nav-group-sub">
                                <li class="nav-item"><a href="{{ route('hr.performance.reviews') }}" class="nav-link {{ Route::is('hr.performance.reviews') || Route::is('hr.performance.reviews.show') ? 'active' : '' }}">Reviews</a></li>
                                <li class="nav-item"><a href="{{ route('hr.performance.categories') }}" class="nav-link {{ Route::is('hr.performance.categories') ? 'active' : '' }}">Score Categories</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                {{-- My Leave self-service for HR manager --}}
                <li class="nav-item">
                    <a href="{{ route('my.leave.index') }}" class="nav-link {{ Route::is('my.leave.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-heart"></i><span>My Leave</span>
                    </a>
                </li>

                <li class="sidebar-section-label">Finance</li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['payments.index','payments.create','payments.edit','payments.manage','payments.show','payments.invoice']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-cash-stack"></i><span>Payments</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('payments.create') }}" class="nav-link {{ Route::is('payments.create') ? 'active' : '' }}">Create Payment</a></li>
                        <li class="nav-item"><a href="{{ route('payments.index') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['payments.index','payments.edit','payments.show']) ? 'active' : '' }}">Manage Payments</a></li>
                        <li class="nav-item"><a href="{{ route('payments.manage') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['payments.manage','payments.invoice','payments.receipts']) ? 'active' : '' }}">Student Payments</a></li>
                    </ul>
                </li>

                @php $reportsActive = str_starts_with(Route::currentRouteName() ?? '', 'reports.'); @endphp
                <li class="sidebar-section-label">Analytics</li>
                <li class="nav-item nav-item-submenu {{ $reportsActive ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-bar-chart-line"></i><span>Reports</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('reports.finance') }}" class="nav-link {{ Route::is('reports.finance') ? 'active' : '' }}">Finance Report</a></li>
                    </ul>
                </li>

                @endif {{-- end hr_manager --}}

                {{-- ════════════════════════════════════════════════════════════
                     COMMUNICATION — all roles
                     ════════════════════════════════════════════════════════════ --}}
                <li class="sidebar-section-label">Communication</li>
                <li class="nav-item nav-item-submenu {{ in_array(Route::currentRouteName(), ['announcements','inbox','compose','messages.read']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="#" class="nav-link"><i class="bi bi-chat-dots"></i><span>Messages</span></a>
                    <ul class="nav nav-group-sub">
                        <li class="nav-item"><a href="{{ route('announcements') }}" class="nav-link {{ Route::is('announcements') ? 'active' : '' }}">Announcements</a></li>
                        <li class="nav-item">
                            <a href="{{ route('inbox') }}" class="nav-link {{ in_array(Route::currentRouteName(), ['inbox','messages.read']) ? 'active' : '' }}">
                                Inbox
                                @php $mc = \App\Models\Message::where('receiver_id', Auth::id())->where('read',false)->count(); @endphp
                                @if($mc > 0)<span class="badge badge-danger ml-1" style="font-size:10px;">{{ $mc }}</span>@endif
                            </a>
                        </li>
                        <li class="nav-item"><a href="{{ route('compose') }}" class="nav-link {{ Route::is('compose') ? 'active' : '' }}">Compose</a></li>
                    </ul>
                </li>

                {{-- Role-specific extra menu items --}}
                @include('pages.'.Qs::getUserType().'.menu')

                {{-- Account --}}
                <li class="nav-item" style="margin-top:8px;">
                    <a href="{{ route('my_account') }}" class="nav-link {{ Route::is('my_account') ? 'active' : '' }}">
                        <i class="bi bi-person-circle"></i><span>My Account</span>
                    </a>
                </li>

                {{-- Logout --}}
                <li class="nav-item" style="margin-top:4px;margin-bottom:12px;">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="nav-link w-100 text-left"
                                style="background:none;border:none;cursor:pointer;color:rgba(255,100,100,.8) !important;width:100%;">
                            <i class="bi bi-box-arrow-right"></i><span>Sign Out</span>
                        </button>
                    </form>
                </li>

            </ul>
        </div>
    </div>
</div>
