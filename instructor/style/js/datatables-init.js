(function($) {
    // تهيئة جدول عام مع خيارات أساسية
    $('#bootstrap-data-table').DataTable({
        lengthMenu: [
            [10, 20, 50, -1], // خيارات عدد الصفوف لكل صفحة
            [10, 20, 50, "All"] // نصوص الخيارات
        ],
        responsive: true, // تفعيل الاستجابة لأحجام الشاشات المختلفة
        autoWidth: false, // تعطيل الضبط التلقائي لعرض الأعمدة
        ordering: false // تعطيل إمكانية الترتيب
    });

    // تهيئة جدول الطلاب مع خيارات الطباعة
    $('#allStudents').DataTable({
        responsive: true,
        autoWidth: false,
        dom: 'Bfrtip', // عناصر التحكم في الجدول (Buttons, filtering, info etc.)
        buttons: [{ // أزرار إضافية
            extend: 'print', // زر الطباعة
            title: 'All Students', // عنوان عند الطباعة
            messageBottom: null, // لا يوجد نص سفلي
            exportOptions: {
                columns: [0, 1, 2, 3, 4] // الأعمدة المراد تصديرها
            }
        }],
        paging: false, // تعطيل التقسيم إلى صفحات
        ordering: false // تعطيل الترتيب
    });

    // تهيئة جدول المدربين مشابه لجدول الطلاب
    $('#allInstructors').DataTable({
        responsive: true,
        autoWidth: false,
        dom: 'Bfrtip',
        buttons: [{
            extend: 'print',
            title: 'All Instructors',
            messageBottom: null,
            exportOptions: {
                columns: [0, 1, 2, 3, 4]
            }
        }],
        paging: false,
        ordering: false
    });

    // تهيئة جدول الأسئلة مع فلتر حسب الموضوع
    $('#questionsTable').DataTable({
        lengthMenu: [
            [10, 20, 50, -1],
            [10, 20, 50, "All"]
        ],
        responsive: true,
        autoWidth: false,
        ordering: false,
        initComplete: function() {
            // إضافة فلتر dropdown للعمود الثاني (الموضوع)
            this.api().columns(1).every(function() {
                var column = this;
                var select = $('<select><option value="">All Topics</option></select>')
                    .appendTo($(column.header()).empty())
                    .on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );
                        column
                            .search(val ? '^' + val + '$' : '', true, false)
                            .draw();
                    });
                column.data().unique().sort().each(function(d, j) {
                    select.append('<option value="' + d + '"> >' + d + '</option>')
                });
            });
        }
    });

    // تهيئة جدول الاختبارات مشابه لجدول الأسئلة
    $('#testsTable').DataTable({
        lengthMenu: [
            [10, 20, 50, -1],
            [10, 20, 50, "All"]
        ],
        responsive: true,
        autoWidth: false,
        ordering: false,
        initComplete: function() {
            this.api().columns(1).every(function() {
                var column = this;
                var select = $('<select><option value="">All Topics</option></select>')
                    .appendTo($(column.header()).empty())
                    .on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );
                        column
                            .search(val ? '^' + val + '$' : '', true, false)
                            .draw();
                    });
                column.data().unique().sort().each(function(d, j) {
                    select.append('<option value="' + d + '"> >' + d + '</option>')
                });
            });
        }
    });

    // تهيئة جدول النتائج مع خيارات التصدير
    $('#ResultsTable').DataTable({
        lengthMenu: [
            [10, 20, 50, -1],
            [10, 20, 50, "All"]
        ],
        responsive: true,
        autoWidth: false,
        ordering: false,
        dom: 'Bfrtip',
        buttons: [ // أزرار التصدير
            'copy', 'excel', 'pdf', 'print'
        ],
        initComplete: function() {
            // فلتر dropdown للعمود الرابع (الاختبارات)
            this.api().columns(3).every(function() {
                var column = this;
                var select = $('<select><option value="">All Tests</option></select>')
                    .appendTo($(column.header()).empty())
                    .on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );
                        column
                            .search(val ? '^' + val + '$' : '', true, false)
                            .draw();
                    });
                column.data().unique().sort().each(function(d, j) {
                    select.append('<option value="' + d + '"> >' + d + '</option>')
                });
            });
        }
    });

    // جدول مع خيارات تصدير متقدمة
    $('#bootstrap-data-table-export').DataTable({
        lengthMenu: [
            [10, 25, 50, -1],
            [10, 25, 50, "All"]
        ],
        responsive: true,
        autoWidth: false,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'excel', 'pdf', 'print'
        ],
    });

    // جدول الكورسات بدون ترتيب
    $('#CoursesTable').DataTable({
        lengthMenu: [
            [10, 25, 50, -1],
            [10, 25, 50, "All"]
        ],
        responsive: true,
        autoWidth: false,
        ordering: false,
    });

    // جدول تعيين الأسئلة مع خيارات متعددة
    $('#AssignQuestionsTable').DataTable({
        paging: false, // بدون pagination
        ordering: false, // بدون ترتيب
        dom: 'Blfrtip', // عناصر التحكم
        buttons: [ // أزرار الاختيار
            'selectAll', // تحديد الكل
            'selectNone', // إلغاء التحديد
        ],
        columnDefs: [{
            orderable: false,
            className: 'select-checkbox', // فئة لأعمدة الاختيار
            targets: 0 // العمود الأول
        }],
        select: {
            style: 'multi', // تعددية الاختيار
            selector: 'td' // يمكن الاختيار من أي خلية
        },
        order: [
            [1, 'asc'] // الترتيب الافتراضي للعمود الثاني تصاعدياً
        ],
        initComplete: function() {
            // فلتر حسب الموضوع للعمود الرابع
            this.api().columns(3).every(function() {
                var column = this;
                var select = $('<select><option value="">All Topics</option></select>')
                    .appendTo($(column.header()).empty())
                    .on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );
                        column
                            .search(val ? '^' + val + '$' : '', true, false)
                            .draw();
                    });
                column.data().unique().sort().each(function(d, j) {
                    select.append('<option value="' + d + '"> >' + d + '</option>')
                });
            });
        }
    });

    // جدول حذف الأسئلة من الاختبار
    $('#deleteQuestionsFromTest').DataTable({
        paging: false,
        ordering: false,
        dom: 'Blfrtip',
        buttons: [
            'selectAll',
            'selectNone',
        ],
        columnDefs: [{
            orderable: false,
            className: 'select-checkbox',
            targets: 0
        }],
        select: {
            style: 'multi',
            selector: 'td'
        },
        order: [
            [1, 'asc']
        ],
    });

    // أحداث عند تحديد/إلغاء تحديد أسئلة في جدول التعيين
    $('#AssignQuestionsTable').DataTable().on('select deselect', function(event) {
        var count = 0;
        var theTotal = 0;
        // حساب مجموع درجات الأسئلة المحددة
        $(".selected .qDegree input").each(function() {
            var val = $(this).val();
            theTotal += parseInt(val);
            count++;
        });
        $("#total").text(theTotal); // عرض المجموع
        $("#counter").text(count); // عرض العدد

        // تحديث الحقول المخفية للأسئلة المحددة
        $('#testQuestions input[type="hidden"]').remove();
        $("#AssignQuestionsTable tr.selected").each(function() {
            $('#testQuestions').append('<input type="hidden" name="Question[]" value="' + $(this).find('td:nth-child(2)').text() + '">');
        });
    });

    // أحداث عند تحديد/إلغاء تحديد أسئلة في جدول الحذف
    $('#deleteQuestionsFromTest').DataTable().on('select deselect', function(event) {
        $('#testQuestions input[type="hidden"]').remove();
        $("#deleteQuestionsFromTest tr.selected").each(function() {
            $('#testQuestions').append('<input type="hidden" name="Question[]" value="' + $(this).find('td:nth-child(2)').text().trim() + '">');
        });
    });
})(jQuery);