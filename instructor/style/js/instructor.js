// عند تحميل الصفحة بالكامل، يتم إخفاء شاشة التحميل ببطء
$(window).load(function() {
    $('.preloader').fadeOut('slow');
});

// عند النقر على عنصر lightbox، يتم منع السلوك الافتراضي وفتح المعرض
$(document).on('click', '[data-toggle="lightbox"]', function(event) {
    event.preventDefault();
    $(this).ekkoLightbox();
});

// دالة لعرض نافذة تأكيد مخصصة باستخدام SweetAlert
function customConfirm(lnk, conf, succ) {
    Swal.fire({
        title: 'Are you sure?',
        text: conf,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes'
    }).then((result) => {
        if (result.value) {
            Swal.fire({
                icon: 'success',
                title: 'Successful...',
                text: succ,
                onClose: () => {
                    window.location.href = lnk;
                }
            })
        }
    })
}

// تنفيذ الكود عند تحميل jQuery
(function($) {
    // عند عرض تبويب المطابقة، إخفاء مجموعة النقاط
    $('.matchingTab').on('show.bs.tab', function() {
        $('.points-group').addClass('d-none');
    });

    // عند إخفاء تبويب المطابقة، إظهار مجموعة النقاط
    $('.matchingTab').on('hide.bs.tab', function() {
        $('.points-group').removeClass('d-none');
    });

    // التحقق من صحة السؤال قبل الإضافة
    $('#AddQuestion').click(function(e) {
        var answerExist = 0;
        var qtype = -1;

        // التحقق من الأسئلة المقالية و الصح/خطأ
        $('#TF.tab-pane.fade.active.show').each(function() {
            answerExist = 1;
        });
        $('#essay.tab-pane.fade.active.show').each(function() {
            answerExist = 1;
        });

        // التحقق من أسئلة الاختيار المتعدد
        $('#MCQ.tab-pane.fade.active.show .mcqTextarea').each(function() {
            qtype = 0;
            if ((!$(this).summernote('isEmpty')) && $(this).closest("li").find("input[type=radio]").prop("checked")) {
                answerExist += 1;
            }
        });

        // التحقق من أسئلة الاختيار المتعدد المتعدد
        $('#MSQ.tab-pane.fade.active.show .msqTextarea').each(function() {
            qtype = 3;
            if ((!$(this).summernote('isEmpty')) && $(this).closest("li").find("input[type=checkbox]").prop("checked")) {
                answerExist += 1;
            }
        });

        // التحقق من أسئلة الإكمال
        $('#COMPLETE.tab-pane.fade.active.show .Completelist input').each(function() {
            if ($(this).val() != '') answerExist = 1;
        });

        // التحقق من أسئلة المطابقة
        $('#matching.tab-pane.fade.active.show #MatchingAnswers li').each(function() {
            if ($(this).find('.matchInp').val() != '' && $(this).find('.matchAnswerInp').val() != '')
                answerExist = 1;
        });

        // التحقق من وجود نص السؤال
        if ($('#textarea-input').summernote('isEmpty')) {
            Swal.fire({
                icon: 'error',
                title: 'Failed..',
                text: 'Question can\'t be empty!'
            });
            return false;
        }

        // التحقق من وجود إجابات صحيحة حسب نوع السؤال
        if (qtype == 0) {
            if (answerExist == 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed..',
                    text: 'Multiple Choice question must have at least one correct answer!'
                });
                return false;
            }
        } else if (qtype == 3) {
            if (answerExist < 2) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed..',
                    text: 'Multiple Select question must have at least two correct answers!'
                });
                return false;
            }
        } else {
            if (answerExist == 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed..',
                    text: 'Question must have at least one valid answer!'
                });
                return false;
            }
        }
    });

    // التحقق من وجود أسئلة قبل تعيين الاختبار
    $('#assignButton').click(function(e) {
        var questions = $(this).data('questions');

        if (questions == 0) {
            Swal.fire({
                icon: 'error',
                title: 'Can\'t assign an empty test',
                text: 'You must add questions to the test before assigning it!'
            });
            return false;
        }
        return true;
    });

    // التحقق من صحة وقت الاختبار
    $('.submitAssign').on('click', function(e) {
        var start = moment($('input[name="startTime"]').val(), "MM/DD/YYYY h:mm a");
        var end = moment($('input[name="endTime"]').val(), "MM/DD/YYYY h:mm a");
        var diff = end.diff(start, "minutes");
        if ($('#durationText').text() > diff) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'The test duration is longer than the time between start and end times!'
            });
            return false;
        } else {
            return true;
        }
    })

    // تهيئة أداة نسخ النص
    new ClipboardJS('.btn');

    // تهيئة عناصر اختيار التاريخ والوقت
    $('#startTimePicker').datetimepicker();
    $('#endTimePicker').datetimepicker({
        useCurrent: false
    });

    // تحديث الحد الأدنى للتاريخ النهائي عند تغيير التاريخ الأولي
    $("#startTimePicker").on("change.datetimepicker", function(e) {
        $('#endTimePicker').datetimepicker('minDate', e.date);
    });

    // تحديث الحد الأقصى للتاريخ الأولي عند تغيير التاريخ النهائي
    $("#endTimePicker").on("change.datetimepicker", function(e) {
        $('#startTimePicker').datetimepicker('maxDate', e.date);
    });

    // تهيئة حقول التاريخ والوقت الموجودة مسبقاً
    $('.datetimepicker-input').each(function() {
        var date = moment($(this).data('datetime'), 'YYYY-MM-DD hh:mm a').toDate();
        $(this).datetimepicker({ date: date });
    });

    // إدارة اختيار الإجابة الصحيحة في الأسئلة متعددة الخيارات
    $(document).on('click', '.mcqCheckInput', function() {
        $('.mcqCheckInput').each(function() {
            $(this).prop('checked', false);
            $(this).closest('li').removeClass('correctAnswer');
        });
        $(this).prop('checked', true);
        $(this).closest('li').addClass('correctAnswer');
    });

    // عرض اسم الملف عند اختياره
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().replace('C:\\fakepath\\', "");
        $(this).next('.custom-file-label').html(fileName);
    })

    // إدارة اختيار الإجابات الصحيحة في الأسئلة متعددة الإجابات
    $(document).on('click', '.msqCheckInput', function() {
        $(this).closest('li').toggleClass('correctAnswer');
    });

    // تغيير نوع الإدخال حسب نوع السؤال
    $('ul').on('change', '#qtype', function() {
        if ($(this).val() == 0)
            $('.mcqCheckInput').attr('type', 'radio');
        else if ($(this).val() == 3)
            $('.mcqCheckInput').attr('type', 'checkbox');
    })

    // عرض بيانات الطالب في نافذة منبثقة
    $('.showStudentData').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        var email = $(this).data('email');
        var phone = $(this).data('phone');
        Swal.fire({
            title: 'Student Information',
            html: '<div class="form-group"><label>Student ID</label><input type="text" class="form-control" value="' + id + '" disabled></div>' +
                '<div class="form-group"><label>Name</label><input type="text" class="form-control" value="' + name + '" disabled></div>' +
                '<div class="form-group"><label>Email address</label><input type="email" class="form-control" value="' + email + '" disabled></div>' +
                '<div class="form-group"><label>Phone Number</label><input type="text" class="form-control" value="' + phone + '" disabled></div>',
            focusConfirm: false,
        })
    });

    // حذف الإجابة مع طلب التأكيد
    $(document).on('click', '.deleteAnswer', function(e) {
        e.preventDefault();
        var completeanswer = $(this).closest('.completeanswer');
        var li = $(this).closest("li");
        var ansID = $(this).data('ansid');
        if (ansID) {
            Swal.fire({
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.value) {
                    $.post("app/controller/question.inc.php?deleteAnswer", {
                        ansID: ansID
                    }, function(data, status) {
                        completeanswer.remove();
                        li.remove();
                    });
                }
            })
        } else {
            completeanswer.remove();
            li.remove();
        }
    });

    // تحديث بيانات الاختبار المعين عند عرض النافذة
    $('#updateAssignedTest').on('show.bs.modal', function(e) {
        var gID = $(e.relatedTarget).data('id');
        var tID = $(e.relatedTarget).data('testid');
        var startTime = $(e.relatedTarget).data('starttime');
        var endTime = $(e.relatedTarget).data('endtime');
        var duration = $(e.relatedTarget).data('duration');
        var viewAnswers = $(e.relatedTarget).data('viewanswers');
        $(e.currentTarget).find('input[name="testID"]').val(tID);
        $(e.currentTarget).find('input[name="groupID"]').val(gID);
        $(e.currentTarget).find('input[name="startTime"]').val(startTime);
        $(e.currentTarget).find('input[name="endTime"]').val(endTime);
        $(e.currentTarget).find('input[name="duration"]').val(duration);
        if (viewAnswers == 2) {
            $(e.currentTarget).find('#sh1').prop("checked", 1);
        } else if (viewAnswers == 1) {
            $(e.currentTarget).find('#sh2').prop("checked", 1);
        } else {
            $(e.currentTarget).find('#sh3').prop("checked", 1);
        }
    });

    // حذف الاختبار المعين مع طلب التأكيد
    $('.deleteAssignedTest').on('click', function(e) {
        var gID = $(this).data('gid');
        Swal.fire({
            title: 'Are you sure?',
            text: "The Assigned Test Will be removed",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.value) {
                window.location.href = "app/controller/assign.inc.php?deleteAssignedTest=" + gID;
            }
        })
    });

    // تحديث بيانات المقرر عند عرض النافذة
    $('#editcourse').on('show.bs.modal', function(e) {
        var name = $(e.relatedTarget).data('cname');
        var courseid = $(e.relatedTarget).data('cid');
        var parentid = $(e.relatedTarget).data('pid');
        var disabled = $(e.relatedTarget).data('cdisabled');
        $(e.currentTarget).find('input[name="courseName"]').val(name);
        $(e.currentTarget).find('input[name="id"]').val(courseid);
        $(e.currentTarget).find('select[name="course"]').prop("disabled", disabled);
        $(e.currentTarget).find('select[name="course"]').val(parentid);
    });

    // تحديث بيانات المجموعة عند عرض النافذة
    $('#editgroup').on('show.bs.modal', function(e) {
        var name = $(e.relatedTarget).data('gname');
        var groupid = $(e.relatedTarget).data('gid');
        $(e.currentTarget).find('input[name="groupName"]').val(name);
        $(e.currentTarget).find('input[name="id"]').val(groupid);
    });

    // تحديث بيانات الاختبار عند عرض النافذة
    $('#editTest').on('show.bs.modal', function(e) {
        var name = $(e.relatedTarget).data('tname');
        var testid = $(e.relatedTarget).data('tid');
        var course = $(e.relatedTarget).data('tcourse');
        $(e.currentTarget).find('input[name="testid"]').val(testid);
        $(e.currentTarget).find('input[name="testName"]').val(name);
        $(e.currentTarget).find('input[name="oldtestName"]').val(name);
        $(e.currentTarget).find('select[name="Course"]').val(course);
    });

    // تبديل حالة القائمة الجانبية
    $('#menuToggle').on('click', function(event) {
        $('body').toggleClass('open');
    });

    // إضافة خيار جديد لأسئلة الاختيار المتعدد
    $("#MCQaddChoise").click(function() {
        var qtype = document.getElementById("qtype").value;
        var lastAnswer = ++document.getElementById("MCQlastAnswer").value;
        $('.MCQchoiseslist').append('<li class="list-group-item">' +
            '<div class="row"><div class="col-6"><div class="icheck-success">' +
            '<input type="radio" class="mcqCheckInput" id="MCQcheck' + lastAnswer + '" name="MCQanswer[' + lastAnswer + '][isCorrect]" value="1">' +
            ' <label for="MCQcheck' + lastAnswer + '">Correct Answer</label>' +
            '	</div></div>' +
            '	<div class="col-lg-6"><i class="fa fa-trash deleteAnswer float-right mb-3 text-danger"></i></div></div>' +
            '	<hr>' +
            '	<textarea rows="4" placeholder="Answer ' + lastAnswer + '..." name="MCQanswer[' + lastAnswer + '][answertext]" class="form-control mcqTextarea moreansmcq' + lastAnswer + '"></textarea>' +
            '<br>' +
            '	</div></div>' +
            '	</li><br>');
        $('.moreansmcq' + lastAnswer).summernote();
        document.getElementById("MCQlastAnswer").value = lastAnswer;
    })

    // إضافة خيار جديد للأسئلة متعددة الخيارات
    $("#addChoise").click(function() {
        var qtype = document.getElementById("qtype").value;
        var lastAnswer = ++document.getElementById("MCQlastAnswer").value;
        $('.choiseslist').append('<li class="list-group-item">' +
            '<div class="row"><div class="col-6"><div class="icheck-success">' +
            '<input type="' + ((qtype == 0) ? 'radio' : 'checkbox') + '" class="answerCheck ' + ((qtype == 0) ? 'mcqCheckInput' : 'msqCheckInput') + '" id="isrightcheck' + lastAnswer + '" name="Qanswer[' + lastAnswer + '][isCorrect]" value="1">' +
            ' <label for="isrightcheck' + lastAnswer + '">Correct Answer</label>' +
            '	</div></div>' +
            '	<div class="col-lg-6"><i class="fa fa-trash deleteAnswer float-right mb-3 text-danger"></i></div></div>' +
            '	<hr>' +
            '	<textarea rows="2" placeholder="Answer ' + lastAnswer + '..." name="Qanswer[' + lastAnswer + '][answertext]" class="form-control moreansmcq' + lastAnswer + '"></textarea>' +
            '<br>' +
            '</div>' +
            '</li><br>');
        $('.moreansmcq' + lastAnswer).summernote();
        document.getElementById("MCQlastAnswer").value = lastAnswer;
    })

    // إضافة خيار جديد للأسئلة متعددة الإجابات
    $("#MSQaddChoise").click(function() {
        var qtype = document.getElementById("qtype").value;
        var lastAnswer = ++document.getElementById("MSQlastAnswer").value;
        $('.MSQchoiseslist').append('<li class="list-group-item">' +
            '<div class="row"><div class="col-6"><div class="icheck-success">' +
            '<input type="checkbox" class="msqCheckInput" id="isrightcheck' + lastAnswer + '" name="MSQanswer[' + lastAnswer + '][isCorrect]" value="1">' +
            ' <label for="isrightcheck' + lastAnswer + '">Correct Answer</label>' +
            '	</div></div>' +
            '	<div class="col-lg-6"><i class="fa fa-trash deleteAnswer float-right mb-3 text-danger"></i></div></div>' +
            '	<hr>' +
            '	<textarea rows="2" placeholder="Answer ' + lastAnswer + '..." name="MSQanswer[' + lastAnswer + '][answertext]" class="form-control msqTextarea moreans' + lastAnswer + '"></textarea>' +
            '<br>' +
            '</div></li><br>');
        $('.moreans' + lastAnswer).summernote();
        document.getElementById("MSQlastAnswer").value = lastAnswer;
    })

    // إضافة إجابة جديدة لأسئلة الإكمال
    $("#addComAnswer").click(function() {
        var lastCompleteAnswer = ++document.getElementById("lastCompleteAnswer").value;
        $('.Completelist').append('<div class="row form-group completeanswer">' +
            '<div class="col-12 col-md-9"><input type="text" id="answer' + lastCompleteAnswer + '" name="Canswer[' + lastCompleteAnswer + '][answertext]" placeholder="Answer ' + lastCompleteAnswer + '" class="form-control"></div>' +
            '<i class="fa fa-trash deleteAnswer float-right mb-3 text-danger"></i>' +
            '</div>')
        document.getElementById("lastCompleteAnswer").value = document.getElementById("lastCompleteAnswer").value++;
    })

    // إضافة زوج مطابقة جديد
    $("#addMatch").click(function() {
        $('#MatchingAnswers').append('<li class="list-group-item">' +
            '<div class="row">' +
            '<div class="col-4">' +
            '<input type="text" class="form-control" name="match[]">' +
            '</div>' +
            '<i class="fa fa-arrow-right mt-2" aria-hidden="true"></i>' +
            '<div class="col-4">' +
            '<input type="text" class="form-control" name="matchAnswer[]">' +
            '</div>' +
            '<div class="col-2">' +
            '<input type="number" class="form-control" placeholder="Points" value="1" name="matchPoints[]">' +
            '</div>' +
            '<i class="fa fa-trash deleteAnswer float-right mb-3 text-danger"></i>' +
            '</div>' +
            '</li>')
    });

})(jQuery);

// قراءة وعرض معاينة الصورة المحددة
function readURL(input, dist) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function(e) {
            $(dist).attr('src', e.target.result);
            $(dist).removeClass('imgboxdisplaynon');
        }

        reader.readAsDataURL(input.files[0]);
    }
}

// حذف عنصر بواسطة معرفه وإضافة حقل مخفي للإشارة إلى الحذف
function RemoveById(input, i, id) {
    document.getElementById(input).remove();
    $('.choiseslist').append('<input type="hidden" name="Qanswer[' + i + '][Delete]" value="' + id + '">')
}