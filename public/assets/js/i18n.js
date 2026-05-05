/**
 * St. Mark SMS — Amharic / English translation engine
 * Works on every page by scanning text nodes. No data-i18n attributes required.
 */
(function () {
    'use strict';

    // ── English → Amharic dictionary ─────────────────────────────────────────
    var AM = {
        // Navigation
        'Dashboard': 'ዳሽቦርድ',
        'Students': 'ተማሪዎች',
        'Academics': 'አካዳሚክስ',
        'Administration': 'አስተዳደር',
        'Finance': 'ፋይናንስ',
        'Library': 'ቤተ መጻሕፍት',
        'Communication': 'ግንኙነት',
        'Analytics': 'ትንታኔ',
        'Settings': 'ቅንብሮች',
        'Sign Out': 'ውጣ',
        'My Account': 'የእኔ መለያ',
        'Human Resources': 'ሰው ሃብት',
        'HR': 'ሰው ሃብት',
        // Students
        'Admit Student': 'ተማሪ ተቀበል',
        'Student List': 'የተማሪ ዝርዝር',
        'Promotion': 'ዕድገት',
        'Promote Students': 'ተማሪዎችን አሳድግ',
        'Manage Promotions': 'ዕድገቶችን አስተዳድር',
        'Graduated': 'ተመርቀዋል',
        'Admit': 'ተቀበል',
        'Full Name': 'ሙሉ ስም',
        'Address': 'አድራሻ',
        'Email Address': 'ኢሜይል አድራሻ',
        'Gender': 'ፆታ',
        'Male': 'ወንድ',
        'Female': 'ሴት',
        'Date of Birth': 'የልደት ቀን',
        'Nationality': 'ዜግነት',
        'Region': 'ክልል',
        'Sub-city / Woreda': 'ክፍለ ከተማ / ወረዳ',
        'Blood Group': 'የደም ዓይነት',
        'Upload Passport Photo': 'የፓስፖርት ፎቶ ስቀል',
        'Class': 'ክፍል',
        'Section': 'ክፍለ ጊዜ',
        'Parent': 'ወላጅ',
        'Year Admitted': 'የተቀበሉበት ዓመት',
        'Religion': 'ሃይማኖት',
        'Admission Number': 'የምዝገባ ቁጥር',
        'Personal Data': 'የግል መረጃ',
        'Student Data': 'የተማሪ መረጃ',
        // Academics
        'Exams & Marks': 'ፈተናዎች እና ውጤቶች',
        'Exam List': 'የፈተና ዝርዝር',
        'Grades': 'ደረጃዎች',
        'Tabulation Sheet': 'የውጤት ሰሌዳ',
        'Batch Fix': 'ጅምላ ማስተካከያ',
        'Enter Marks': 'ውጤቶችን አስገባ',
        'Marksheet': 'የውጤት ሰሌዳ',
        'Timetable': 'የጊዜ ሰሌዳ',
        'View Timetables': 'የጊዜ ሰሌዳዎችን ይመልከቱ',
        'Attendance': 'ክትትል',
        'Mark Attendance': 'ክትትል ምልክት አድርግ',
        'All Sessions': 'ሁሉም ክፍለ ጊዜዎች',
        'Marks': 'ውጤቶች',
        'Manage Marks': 'ውጤቶችን አስተዳድር',
        'Smart Insights': 'ብልህ ትንታኔ',
        // Attendance
        'Open Attendance Session': 'የክትትል ክፍለ ጊዜ ክፈት',
        'View All Sessions': 'ሁሉም ክፍለ ጊዜዎችን ይመልከቱ',
        'Present': 'ቀርቧል',
        'Absent': 'አልቀረበም',
        'Late': 'ዘግይቷል',
        'Save Attendance': 'ክትትልን አስቀምጥ',
        'Cancel': 'ሰርዝ',
        'Date': 'ቀን',
        'Open Session': 'ክፍለ ጊዜ ክፈት',
        'Your Homeroom Class': 'የእኔ ክፍል',
        'Attendance Sessions': 'የክትትል ክፍለ ጊዜዎች',
        // Library
        'Books': 'መጻሕፍት',
        'Add Book': 'መጽሐፍ ጨምር',
        'Borrow Requests': 'የብድር ጥያቄዎች',
        'History': 'ታሪክ',
        'Author': 'ደራሲ',
        'Total Copies': 'ጠቅላላ ቅጂዎች',
        'Issued Copies': 'የተሰጡ ቅጂዎች',
        'Available': 'ይገኛል',
        'Approve': 'ፍቀድ',
        'Reject': 'ውድቅ አድርግ',
        'Return': 'መልስ',
        'Approved': 'ፀድቋል',
        'Returned': 'ተመልሷል',
        'Pending': 'በመጠባበቅ ላይ',
        // Communication
        'Announcements': 'ማስታወቂያዎች',
        'Inbox': 'መልዕክት ሳጥን',
        'Compose': 'ጻፍ',
        'Messages': 'መልዕክቶች',
        'From': 'ከ',
        'To': 'ወደ',
        'Subject': 'ርዕሰ ጉዳይ',
        'Send': 'ላክ',
        'Reply': 'መልስ',
        'Back to Inbox': 'ወደ መልዕክት ሳጥን ተመለስ',
        'Read Message': 'መልዕክት አንብብ',
        'No messages.': 'ምንም መልዕክት የለም።',
        'Summarize with AI': 'በ AI ጠቅለል አድርግ',
        'AI Summary': 'የ AI ማጠቃለያ',
        // HR
        'Staff Management': 'የሰራተኛ አስተዳደር',
        'Staff List': 'የሰራተኛ ዝርዝር',
        'Departments': 'ክፍሎች',
        'Staff Attendance': 'የሰራተኛ ክትትል',
        'Workload': 'የስራ ጫና',
        'Department': 'ክፍል',
        'Employment Date': 'የቅጥር ቀን',
        'Assign Department': 'ክፍል ምደብ',
        'Update Department': 'ክፍልን አዘምን',
        'Add Department': 'ክፍል ጨምር',
        'On Leave': 'በፈቃድ ላይ',
        // Finance
        'Payments': 'ክፍያዎች',
        'Create Payment': 'ክፍያ ፍጠር',
        'Manage Payments': 'ክፍያዎችን አስተዳድር',
        'Student Payments': 'የተማሪ ክፍያዎች',
        'Amount': 'መጠን',
        'Amount Paid': 'የተከፈለ መጠን',
        'Balance': 'ቀሪ',
        'Paid': 'ተከፍሏል',
        'Unpaid': 'አልተከፈለም',
        'Receipt': 'ደረሰኝ',
        'Pay Now': 'አሁን ክፈል',
        'Pay via Chapa': 'በ Chapa ክፈል',
        'Cash': 'ጥሬ ገንዘብ',
        'Bank Transfer': 'የባንክ ዝውውር',
        'Total Collected': 'ጠቅላላ የተሰበሰበ',
        'Outstanding Balance': 'ቀሪ ሂሳብ',
        'Fees Collected': 'የተሰበሰበ ክፍያ',
        'Fees Cleared': 'የተከፈለ ክፍያ',
        'Fees Outstanding': 'ያልተከፈለ ክፍያ',
        'Students Fully Paid': 'ሙሉ ክፍያ የፈጸሙ ተማሪዎች',
        'Students With Balance': 'ቀሪ ያለባቸው ተማሪዎች',
        // Reports
        'Reports': 'ሪፖርቶች',
        'Overview': 'አጠቃላይ እይታ',
        'Student Reports': 'የተማሪ ሪፖርቶች',
        'Attendance Reports': 'የክትትል ሪፖርቶች',
        'Academic Reports': 'የአካዳሚክ ሪፖርቶች',
        'Finance Reports': 'የፋይናንስ ሪፖርቶች',
        'Library Reports': 'የቤተ መጻሕፍት ሪፖርቶች',
        'All Classes': 'ሁሉም ክፍሎች',
        // Settings
        'Rules Engine': 'የደንቦች ሞተር',
        'System Settings': 'የስርዓት ቅንብሮች',
        'Audit Logs': 'የኦዲት መዝገቦች',
        'Active': 'ንቁ',
        // Common actions
        'Save': 'አስቀምጥ',
        'Update': 'አዘምን',
        'Delete': 'ሰርዝ',
        'Edit': 'አርትዕ',
        'Add': 'ጨምር',
        'Create': 'ፍጠር',
        'Submit': 'አስገባ',
        'Back': 'ተመለስ',
        'Close': 'ዝጋ',
        'Confirm': 'አረጋግጥ',
        'Search': 'ፈልግ',
        'View': 'ይመልከቱ',
        'Print': 'አትም',
        'Download': 'አውርድ',
        'View All': 'ሁሉንም ይመልከቱ',
        'View Profile': 'መገለጫ ይመልከቱ',
        // Dashboard stat labels
        'Total Students': 'ጠቅላላ ተማሪዎች',
        'Total Teachers': 'ጠቅላላ መምህራን',
        'Avg Attendance': 'አማካይ ክትትል',
        'Total Parents': 'ጠቅላላ ወላጆች',
        'Unread Messages': 'ያልተነበቡ መልዕክቶች',
        'Total Staff': 'ጠቅላላ ሰራተኞች',
        'Present Today': 'ዛሬ ቀርቧል',
        'Absent Today': 'ዛሬ አልቀረበም',
        'Outstanding': 'ቀሪ ክፍያ',
        'Students Unpaid': 'ያልከፈሉ ተማሪዎች',
        'My Subjects': 'የእኔ ትምህርቶች',
        "Today's Sessions": 'የዛሬ ክፍለ ጊዜዎች',
        'Parent Messages': 'የወላጅ መልዕክቶች',
        'Recent Announcements': 'የቅርብ ጊዜ ማስታወቂያዎች',
        'Recent Payments': 'የቅርብ ጊዜ ክፍያዎች',
        'Quick Actions': 'ፈጣን ድርጊቶች',
        'Upcoming Exams': 'መጪ ፈተናዎች',
        'No announcements yet.': 'ምንም ማስታወቂያ የለም።',
        'No announcements.': 'ምንም ማስታወቂያ የለም።',
        'No payments recorded yet.': 'ምንም ክፍያ አልተመዘገበም።',
        'No subjects assigned.': 'ምንም ትምህርት አልተመደበም።',
        'No exams scheduled.': 'ምንም ፈተና አልተያዘም።',
        // Table headers
        'S/N': 'ተ.ቁ',
        'Name': 'ስም',
        'Photo': 'ፎቶ',
        'Username': 'የተጠቃሚ ስም',
        'Phone': 'ስልክ',
        'Email': 'ኢሜይል',
        'Action': 'ድርጊት',
        'Year': 'ዓመት',
        'Semester': 'ሴሚስተር',
        'Session': 'ክፍለ ጊዜ',
        'Status': 'ሁኔታ',
        'Remark': 'አስተያየት',
        'Position': 'ደረጃ',
        'Total': 'ጠቅላላ',
        'Average': 'አማካይ',
        'Grade': 'ደረጃ',
        'Assessment': 'ምዘና',
        'Mid Exam': 'የሴሚስተር አጋማሽ ፈተና',
        'Final Exam': 'የመጨረሻ ፈተና',
        'Assessment (30)': 'ምዘና (30)',
        'Mid Exam (20)': 'የሴሚስተር አጋማሽ ፈተና (20)',
        'Final Exam (50)': 'የመጨረሻ ፈተና (50)',
        'ADM No': 'የምዝገባ ቁጥር',
        'ADM_NO': 'የምዝገባ ቁጥር',
        // Marks
        'Update Marks': 'ውጤቶችን አዘምን',
        'AI Comment': 'የ AI አስተያየት',
        // Timetable
        'Manage TimeTable Record': 'የጊዜ ሰሌዳ አስተዳድር',
        'Manage Time Slots': 'የጊዜ ክፍሎችን አስተዳድር',
        'Add Subject': 'ትምህርት ጨምር',
        'Edit Subjects': 'ትምህርቶችን አርትዕ',
        'Validate Timetable': 'የጊዜ ሰሌዳ አረጋግጥ',
        'Add Time Slots': 'የጊዜ ክፍሎች ጨምር',
        'Use Existing Time Slots': 'ያሉ የጊዜ ክፍሎችን ተጠቀም',
        'Start Time': 'የጀምር ጊዜ',
        'End Time': 'የማብቂያ ጊዜ',
        // Parent portal
        'My Children': 'ልጆቼ',
        'Timeline': 'የጊዜ መስመር',
        'Fee Status': 'የክፍያ ሁኔታ',
        'All fees are cleared.': 'ሁሉም ክፍያዎች ተከፍለዋል።',
        'Report Card': 'የውጤት ካርድ',
        // Users
        'Manage Users': 'ተጠቃሚዎችን አስተዳድር',
        'Create New User': 'አዲስ ተጠቃሚ ፍጠር',
        'Password': 'የይለፍ ቃል',
        'Alternative Phone': 'ሌላ ስልክ',
        'Date of Employment': 'የቅጥር ቀን',
        // Exams
        'Manage Exams': 'ፈተናዎችን አስተዳድር',
        'Add Exam': 'ፈተና ጨምር',
        'Semester 1': 'ሴሚስተር 1',
        'Semester 2': 'ሴሚስተር 2',
        // Grades
        'Excellent': 'በጣም ጥሩ',
        'Very Good': 'ጥሩ',
        'Good': 'ጥሩ',
        'Pass': 'አለፈ',
        'Fail': 'ወደቀ',
        'Satisfactory': 'ተቀባይነት ያለው',
        'Needs Improvement': 'ማሻሻያ ያስፈልጋል',
        'Outstanding': 'ልዩ',
        // Classes
        'Manage Classes': 'ክፍሎችን አስተዳድር',
        'Manage Sections': 'ክፍለ ጊዜዎችን አስተዳድር',
        'Manage Subjects': 'ትምህርቶችን አስተዳድር',
        'Teacher': 'መምህር',
        // Misc
        'Submit form': 'ቅጽ አስገባ',
        'No data available': 'ምንም መረጃ የለም',
        'No records found': 'ምንም መዝገብ አልተገኘም',
        'Quick Fill from Document': 'ከሰነድ ፈጣን ሙሌት',
        'Scan Document': 'ሰነድ ቃኝ',
        'Auto-Fill Form': 'ቅጽ በራስ ሙላ',
        'Not detected': 'አልተገኘም',
        'Fee': 'ክፍያ',
        'Method': 'ዘዴ',
        'Staff Att.': 'የሰራተኛ ክትትል',
        'Finance Rpt': 'የፋይናንስ ሪፖርት',
        'Announce': 'ማስታወቂያ',
        'Marksheet': 'የውጤት ሰሌዳ',
        'Staff Att.': 'የሰራተኛ ክትትል',
        'Timetable': 'የጊዜ ሰሌዳ',
        'Admit': 'ተቀበል',
        'Early Warning': 'ቀደምት ማስጠንቀቂያ',
        'Early Warning System': 'ቀደምት ማስጠንቀቂያ ስርዓት',
        'Dropout Early Warning System': 'ማቋረጥ ቀደምት ማስጠንቀቂያ ስርዓት',
        'At-Risk Students': 'አደጋ ላይ ያሉ ተማሪዎች',
        'Risk Score': 'የአደጋ ነጥብ',
        'Risk Level': 'የአደጋ ደረጃ',
        'Critical': 'ወሳኝ',
        'Warning': 'ማስጠንቀቂያ',
        'Low': 'ዝቅተኛ',
        'Recommended Action': 'የሚመከር ድርጊት',
        'Risk Factors': 'የአደጋ ምክንያቶች',
        'Consecutive Absences': 'ተከታታይ ቀሪዎች',
        'Academic Avg': 'አካዳሚክ አማካይ',
        'Trend': 'አዝማሚያ',
        'Declining': 'እየቀነሰ',
        'Improving': 'እየተሻሻለ',
        'Stable': 'ተረጋጋ',
        'Smart Performance Insights': 'ብልህ የአፈጻጸም ትንታኔ',
        'Students Requiring Attention': 'ትኩረት የሚፈልጉ ተማሪዎች',
        'Class Performance Overview': 'የክፍል አፈጻጸም አጠቃላይ እይታ',
        'Subjects Needing Attention': 'ትኩረት የሚፈልጉ ትምህርቶች',
        'Top Performers': 'ምርጥ ተማሪዎች',
        'Most Improved': 'በጣም የተሻሻሉ',
        'School Average': 'የትምህርት ቤት አማካይ',
        'Classes Need Support': 'ድጋፍ የሚፈልጉ ክፍሎች',
        'Timetable Validation': 'የጊዜ ሰሌዳ ማረጋገጫ',
        'No conflicts found.': 'ምንም ግጭት አልተገኘም።',
        'Suggested Fix': 'የሚመከር መፍትሄ',
        'Conflict': 'ግጭት',
        'Conflicts': 'ግጭቶች',
        'Valid Timetable': 'ትክክለኛ የጊዜ ሰሌዳ',
        'Conflict(s) Found': 'ግጭቶች ተገኝተዋል'
    };

    // Build reverse map (Amharic → English) for toggling back
    var EN_REVERSE = {};
    Object.keys(AM).forEach(function (k) { EN_REVERSE[AM[k]] = k; });

    // ── Text node walker ──────────────────────────────────────────────────────
    var SKIP_TAGS = { SCRIPT: 1, STYLE: 1, TEXTAREA: 1, CODE: 1, PRE: 1 };

    function walkAndTranslate(root, map) {
        var walker = document.createTreeWalker(
            root,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function (node) {
                    var p = node.parentElement;
                    if (!p) return NodeFilter.FILTER_REJECT;
                    if (SKIP_TAGS[p.tagName]) return NodeFilter.FILTER_REJECT;
                    if (p.closest('[data-no-translate]')) return NodeFilter.FILTER_REJECT;
                    return NodeFilter.FILTER_ACCEPT;
                }
            },
            false
        );
        var nodes = [];
        var n;
        while ((n = walker.nextNode())) nodes.push(n);
        nodes.forEach(function (node) {
            var original = node.nodeValue;
            var trimmed  = original.trim();
            if (trimmed && map[trimmed] !== undefined) {
                node.nodeValue = original.replace(trimmed, map[trimmed]);
            }
        });
    }

    // ── Attribute translation (placeholder, title, data-i18n) ────────────────
    var I18N_EN = {
        total_students: 'Total Students', total_teachers: 'Total Teachers',
        avg_attendance: 'Avg Attendance', fees_cleared: 'Fees Cleared',
        fees_outstanding: 'Fees Outstanding', att_sessions: 'Attendance Sessions',
        total_parents: 'Total Parents', unread_messages: 'Unread Messages',
        total_staff: 'Total Staff', present_today: 'Present Today',
        absent_today: 'Absent Today', fees_collected: 'Fees Collected',
        outstanding: 'Outstanding', students_unpaid: 'Students Unpaid',
        my_subjects: 'My Subjects', todays_sessions: "Today's Sessions",
        parent_messages: 'Parent Messages', recent_announcements: 'Recent Announcements',
        recent_payments: 'Recent Payments', quick_actions: 'Quick Actions',
        announcements: 'Announcements', upcoming_exams: 'Upcoming Exams',
        subject: 'Subject', class: 'Class', exam: 'Exam', semester: 'Semester',
        year: 'Year', fee: 'Fee', amount: 'Amount', method: 'Method', date: 'Date',
        admit: 'Admit', attendance: 'Attendance', marks: 'Marks', reports: 'Reports',
        announce: 'Announce', inbox: 'Inbox', staff_att: 'Staff Att.',
        payments: 'Payments', staff_list: 'Staff List', finance_rpt: 'Finance Rpt',
        departments: 'Departments', marksheet: 'Marksheet', library: 'Library',
        timetable: 'Timetable', no_announcements: 'No announcements yet.',
        no_payments: 'No payments recorded yet.', no_subjects: 'No subjects assigned.',
        no_exams: 'No exams scheduled.', view_all: 'View All'
    };

    function translateDataI18n(lang) {
        document.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            var enText = I18N_EN[key];
            if (!enText) return;
            var newText = lang === 'am' ? (AM[enText] || enText) : enText;
            // Preserve badge children
            var badges = Array.from(el.querySelectorAll('.badge')).map(function (b) {
                return b.cloneNode(true);
            });
            el.textContent = newText;
            badges.forEach(function (b) { el.appendChild(document.createTextNode(' ')); el.appendChild(b); });
        });
    }

    // ── Main apply function ───────────────────────────────────────────────────
    function applyLanguage(lang) {
        var map = lang === 'am' ? AM : EN_REVERSE;

        // Translate main content area
        var content = document.querySelector('.content');
        if (content) walkAndTranslate(content, map);

        // Translate data-i18n elements (dashboards)
        translateDataI18n(lang);

        // Update toggle button label
        var label = document.getElementById('lang-label');
        if (label) label.textContent = lang === 'am' ? 'EN' : 'አማ';

        // Switch font
        document.body.style.fontFamily = lang === 'am'
            ? "'Noto Sans Ethiopic', 'Inter', sans-serif"
            : "'Inter', sans-serif";

        localStorage.setItem('sms-lang', lang);
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    window.smsI18n = { applyLanguage: applyLanguage };

    document.addEventListener('DOMContentLoaded', function () {
        var saved = localStorage.getItem('sms-lang') || 'en';
        if (saved === 'am') applyLanguage('am');

        var btn = document.getElementById('lang-toggle');
        if (btn) {
            btn.addEventListener('click', function () {
                var current = localStorage.getItem('sms-lang') || 'en';
                applyLanguage(current === 'en' ? 'am' : 'en');
            });
        }
    });

}());
