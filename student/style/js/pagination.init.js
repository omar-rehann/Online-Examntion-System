// تعريف مصفوفة تحتوي على الحروف الأبجدية لاستخدامها في ترقيم الخيارات
var alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");
var container = ''; // متغير لتخزين محتوى السؤال مؤقتًا

$(document).ready(function() {
    // دالة لجلب الأسئلة من الخادم
    function getQuestions(handleData) {
        $.ajax({
            type: "GET",
            url: "app/controller/test.inc.php?action=getQuestions",
            dataType: "json",
            success: function(data) {
                handleData(data); // تمرير البيانات للدالة المعالجة
                // التحقق من وجود رقم الصفحة في التخزين المحلي
                if ("ii" in localStorage) {
                    var ii = ((parseInt(window.localStorage.getItem('ii')) > 0) ? parseInt(window.localStorage.getItem('ii')) : 1);
                    $('#pagination-container').pagination(ii); // تحديد الصفحة الحالية
                } else {
                    window.localStorage.setItem('ii', 1); // تعيين الصفحة الأولى افتراضيًا
                }
            }
        });
    }

    // استدعاء دالة جلب الأسئلة ومعالجة البيانات
    getQuestions(function(questions) {
        // إعداد واجهة التصفح بين الأسئلة
        $('#pagination-container').pagination({
            dataSource: questions, // مصدر البيانات (الأسئلة)
            pageSize: 1, // عرض سؤال واحد لكل صفحة
            showPageNumbers: false, // إخفاء أرقام الصفحات
            showNavigator: true, // إظهار أدوات التنقل
            // تنسيق عرض السؤال
            formatResult: function(data) {
                container = ''; // إعادة تعيين المتغير
                $.each(data, function(z, question) {
                    // إنشاء رأس السؤال مع رقم السؤال وعدد النقاط
                    container += '<div class="card-header">' +
                        '<strong class="card-title" id="questionInc">Question ' + ((window.localStorage.getItem('ii') == null || parseInt(window.localStorage.getItem('ii')) < 1) ? '1' : (window.localStorage.getItem('ii'))) + ' </strong>' +
                        '<small><span class="badge badge-success float-right mt-1">' + question.points + ' Points</span></small>' +
                        '</div><input type="hidden" value="' + question.id + '" id="questionID">' +
                        '<input type="hidden" value="' + question.type + '" id="questionType"><div class="card-body"> <blockquote class="blockquote">' +
                        '<p class="mb-0">' + question.question + '</p>' +
                        '</blockquote>';

                    // إضافة صورة إن وجدت
                    if (question.image) {
                        container += '<div class="text-center"><a href="../style/images/uploads/' + question.image + '.jpg" data-toggle="lightbox"><img style="max-width: 100%;height: 250px;" class="rounded mx-auto" src="../style/images/uploads/' + question.image + '.jpg"></a></div>';
                    }
                    container += '<hr>';

                    // معالجة الأسئلة بناءً على نوعها
                    if (question.type == 0) { // أسئلة الاختيار الواحد
                        var char_index = 0;
                        container += '<div class="row">';
                        $.each(question.answers, function(m, answer) {
                            var selected = (answerExist(question.id, parseInt(answer.id)) ? 'selected' : '');
                            container += '<div class="col-6 mcqChoise ' + selected + '" data-ansid="' + answer.id + '"><div class="badge badge-primary text-wrap m-0">' + alphabet[char_index] + ') </div><div class="ml-5">' + answer.answer + '</div></div>';
                            char_index++;
                        });
                        container += '</div>';
                    } else if (question.type == 3) { // أسئلة الاختيار المتعدد
                        var char_index = 0;
                        container += '<small class="text-muted">This Question might have a multiple Answers</small><ul class="list-group">';
                        container += '<div class="row">';
                        $.each(question.answers, function(m, answer) {
                            var selected = (answerExist(question.id, parseInt(answer.id)) ? 'selected' : '');
                            container += '<div class="col-6 msqChoise ' + selected + '" data-ansid="' + answer.id + '"><div class="badge badge-primary text-wrap m-0">' + alphabet[char_index] + ') </div><div class="ml-5">' + answer.answer + '</div></div>';
                            char_index++;
                        });
                        container += '</div>';
                    } else if (question.type == 1) { // أسئلة صح/خطأ
                        container += '<div class="container">' +
                            '<div class="row">' +
                            '<div class="col selectable text-center ' + (answerExist(question.id, null, 1) ? 'selected' : '') + '" data-istrue="1" style="cursor: pointer;font-size: 6em;">' +
                            '<i class="fa fa-check"></i>' +
                            '</div>' +
                            '<div class="col selectable text-center ' + (answerExist(question.id, null, 0) ? 'selected' : '') + '" data-istrue="0" style="cursor: pointer;font-size: 6em;">' +
                            '<i class="fa fa-close"></i>' +
                            '</div>' +
                            '</div>';
                    } else if (question.type == 2) { // أسئلة الإجابة النصية القصيرة
                        container += '<div class="form-group">' +
                            ' <label for="CompleteAnswer">Answer</label>' +
                            ' <input type="text" class="form-control" id="CompleteAnswer" autocomplete="off" placeholder="Enter Your Answer" value="' + getTextAnswer(question.id) + '">' +
                            ' </div>';
                    } else if (question.type == 5) { // أسئلة المقال
                        container += '<textarea class="form-control" id="EssayAnswer" rows="5">' + getTextAnswer(question.id) + '</textarea>';
                    } else if (question.type == 4) { // أسئلة المطابقة
                        container += '<ul id="MatchingAnswers">';
                        $.each(question.answers, function(m, answer) {
                            container += '<li class="list-group-item matchli">' +
                                '<div class="row">' +
                                '<div class="col-5">' +
                                '<input type="hidden" class="form-control match" value="' + answer.id + '" name="ansID">' +
                                '<span class="badge badge-primary">' + answer.answer + '</span>' +
                                '</div>' +
                                '<div class="col-2 text-center">' +
                                '<i class="fa fa-arrow-right mt-2" aria-hidden="true"></i>' +
                                '</div>' +
                                '<div class="col-5">' +
                                '<select class="form-control matchOpt" value=""><option></option>';
                            $.each(question.matches, function(n, match) {
                                container += '<option ' + ((getTextAnswer(question.id, parseInt(answer.id)) == match.matchAnswer) ? 'selected' : '') + '>' + match.matchAnswer + '</option>';
                            });
                            container += '</select>' +
                                '</div>' +
                                '</div>' +
                                '</li>';
                        });
                        container += '</ul>';
                    }
                });
            },
            // دالة الاستدعاء بعد تحميل السؤال
            callback: function(data, pagination) {
                $('#questionsContainer').html('<div class="preloader"></div>'); // عرض مؤشر التحميل
                $('#questionsContainer').html(container); // تحميل محتوى السؤال
                $('.preloader').fadeOut('slow'); // إخفاء مؤشر التحميل
            }
        });
    });
});

// التعامل مع اختيار إجابات أسئلة الاختيار المتعدد
$("body").on("click", ".msqChoise", function(e) {
    $(this).toggleClass("selected"); // تبديل حالة الاختيار
});

// التعامل مع اختيار إجابات أسئلة الاختيار الواحد
$("body").on("click", ".mcqChoise", function(e) {
    if ($(this).hasClass("selected")) {
        $('.mcqChoise').removeClass("selected"); // إلغاء الاختيار
    } else {
        $('.mcqChoise').removeClass("selected"); // إلغاء الاختيارات السابقة
        $(this).addClass("selected"); // اختيار الإجابة الحالية
    }
});

// التعامل مع اختيار إجابات أ #questionsContainer
$("#questionsContainer").on('click', '.selectable', function() {
    if ($(this).hasClass("selected") == false) {
        $(".selectable").removeClass("selected");
        $(this).addClass("selected"); // اختيار الإجابة
    } else {
        $(".selectable").removeClass("selected"); // إلغاء الاختيار
    }
});

// التعامل مع زر السؤال التالي
$(".container").on('click', '#NextQuestion', function() {
    if ($(".paginationjs-next").hasClass("disabled")) {
        // إذا كان السؤال الأخير، عرض رسالة تأكيد التسليم
        Swal.fire({
            title: 'Your Reached The Last Question',
            text: "Do your want to submit your answers now?",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Submit it!',
            cancelButtonText: 'No!',
        }).then((result) => {
            if (result.value) {
                $(this).attr("disabled", true);
                saveAnswer(); // حفظ الإجابة
                submitAnswers(); // إرسال الإجابات
                return false;
            }
        });
    }
    saveAnswer(); // حفظ الإجابة
    $(".J-paginationjs-next").trigger('click'); // الانتقال للسؤال التالي
    var str = 'Question ' + $(".J-paginationjs-nav").html();
    var q = str.replace('/', 'of');
    $("#questionInc").text(q); // تحديث رقم السؤال
    window.localStorage.setItem('ii', $('#pagination-container').data('pagination').model.pageNumber); // حفظ رقم الصفحة
});

// التعامل مع زر السؤال السابق
$(".container").on('click', '#PreviousQuestion', function() {
    var cva = $('#cva').val();
    if (cva == 0) {
        // إذا لم يُسمح بالرجوع للخلف
        Swal.fire({
            icon: 'error',
            title: 'Action is not allowed!',
            text: 'The test doesn\'t allow this action',
        });
    } else {
        saveAnswer(); // حفظ الإجابة
        $(".J-paginationjs-previous").trigger('click'); // الانتقال للسؤال السابق
        var str = 'Question ' + $(".J-paginationjs-nav").html();
        var q = str.replace('/', 'of');
        $("#questionInc").text(q); // تحديث رقم السؤال
        window.localStorage.setItem('ii', $('#pagination-container').data('pagination').model.pageNumber); // حفظ رقم الصفحة
    }
});

// دالة حفظ الإجابة
function saveAnswer() {
    var qType = $("#questionType").val();
    var questionID = $("#questionID").val();
    clearQuestion(questionID); // مسح الإجابات السابقة للسؤال
    if (qType == 0 || qType == 3) {
        // حفظ إجابات الاختيار الواحد/المتعدد
        $(".selected").each(function() {
            var answerID = $(this).data('ansid');
            if (answerID != null) insertAnswer(questionID, answerID);
        });
    } else if (qType == 1) {
        // حفظ إجابات الصح/خطأ
        var answer = $(".selected").data("istrue");
        if (answer != null) insertAnswer(questionID, null, answer);
    } else if (qType == 2) {
        // حفظ الإجابة النصية القصيرة
        var answer = $('#CompleteAnswer').val();
        if (answer != null) insertAnswer(questionID, null, null, answer);
    } else if (qType == 5) {
        // حفظ إجابة المقال
        var answer = $('#EssayAnswer').val();
        if (answer != null) insertAnswer(questionID, null, null, answer);
    } else if (qType == 4) {
        // حفظ إجابات المطابقة
        $(".matchli").each(function() {
            var match = $(this).find('.match').val();
            var matchAnswer = $(this).find('.matchOpt option:selected').text();
            if (matchAnswer == 'Select your option')
                matchAnswer = '';
            if (match != '') insertAnswer(questionID, match, null, matchAnswer);
        });
    }
}

// دالة لجلب الإجابة النصية المحفوظة
function getTextAnswer(qID, aID = null) {
    if ('data' in localStorage) {
        answers = JSON.parse(atob(localStorage.getItem('data')));
        var result = answers.find(({ questionID, answerID }) => questionID === parseInt(qID) && answerID === aID);
        if (result)
            return result['textAnswer'];
        else
            return '';
    } else {
        return '';
    }
}

// دالة للتحقق من وجود إجابة محفوظة
function answerExist(qID, aID, it = 1, ta = null) {
    if ('data' in localStorage) {
        answers = JSON.parse(atob(localStorage.getItem('data')));
        var result = answers.find(({ questionID, answerID, isTrue, textAnswer }) => questionID === parseInt(qID) && answerID === aID && isTrue === it && textAnswer === ta);
        if (result != undefined)
            return true;
        else
            return false;
    } else {
        return false;
    }
}

// دالة لمسح إجابات سؤال معين
function clearQuestion(qID) {
    if ('data' in localStorage) {
        answers = JSON.parse(atob(localStorage.getItem('data')));
        var answers = $.grep(answers, function(e) {
            return e.questionID != parseInt(qID);
        });
        localStorage.setItem('data', btoa(JSON.stringify(answers)));
    }
}

// دالة لإدراج إجابة جديدة
function insertAnswer(questionID, answerID, isTrue = 1, textAnswer = null) {
    var answers = [];
    if ('data' in localStorage) {
        answers = JSON.parse(atob(localStorage.getItem('data')));
    }
    answers.push({ questionID: parseInt(questionID), answerID: parseInt(answerID), isTrue: isTrue, textAnswer: textAnswer });
    localStorage.setItem('data', btoa(JSON.stringify(answers)));
    return true;
}