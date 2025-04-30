// تعطيل قائمة السياق (النقر بالزر الأيمن)
$(document).bind('contextmenu', function(e) {
    e.preventDefault(); // منع إظهار قائمة السياق
});
// إخفاء مؤشر التحميل عند اكتمال تحميل الصفحة
$(window).on("load", function() {
    $('.preloader').fadeOut('slow'); // إخفاء الـ preloader تدريجيًا
});

// التعامل مع النقر على إجابات الاختيار الواحد
$(document).on('click', '.mcqAnswer', function() {
    $(this).find('input').click(); // تحديد الإجابة عند النقر عليها
});

// تفعيل مكتبة Lightbox لعرض الصور
$(document).on('click', '[data-toggle="lightbox"]', function(event) {
    event.preventDefault(); // منع السلوك الافتراضي
    $(this).ekkoLightbox(); // فتح الصورة في Lightbox
});

// التعامل مع إرسال نموذج تسجيل الدخول
$("#loginForm").submit(function(e) {
    e.preventDefault(); // منع إرسال النموذج الافتراضي
    e.stopPropagation();
    var form = $(this);
    var url = form.attr('action');
    var posting = $.post(url, form.serialize()); // إرسال البيانات إلى الخادم
    posting.done(function(msg) {
        if (msg == 'success') {
            // إذا نجح تسجيل الدخول
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'You Logged In Successfully',
                showConfirmButton: false,
                timer: 1000,
                onClose: () => {
                    $(location).attr('href', '?home'); // إعادة التوجيه للصفحة الرئيسية
                }
            });
        } else {
            // إذا حدث خطأ
            Swal.fire({
                icon: 'error',
                title: 'Something Went Wrong!',
                text: msg,
            });
        }
    });
});

// التعامل مع إرسال نموذج تحديث كلمة المرور
$("#updatePassword").submit(function(e) {
    e.preventDefault();
    var form = $(this);
    var url = form.attr('action');
    var posting = $.post(url, form.serialize());
    posting.done(function(msg) {
        if (msg == 'success') {
            // إذا نجح التحديث
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: ' success Update Password ',
                footer: ' Try Login Agin',
                showConfirmButton: false,
                timer: 2000,
            }).then(() => {
                // إنهاء الجلسة وتوجيه لصفحة تسجيل الدخول
                $.post('app/controller/logout.php', function() {
                    window.location.href = '../../?login';
                });
            });
            form[0].reset(); // تفريغ النموذج
        } else {
            // إذا حدث خطأ
            Swal.fire({
                icon: 'error',
                title: 'حدث خطأ...',
                text: msg,
            });
        }
    });
});

// التعامل مع إرسال نموذج طلب إعادة تعيين كلمة المرور
$("#requestResetForm").submit(function(e) {
    e.preventDefault();
    var form = $(this);
    var url = form.attr('action');
    var posting = $.post(url, form.serialize());
    posting.done(function(msg) {
        if (msg == 'success') {
            // إذا نجح الطلب
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Account Recovery link has sent to your Email',
                footer: 'The link is valid for one hour',
                showConfirmButton: false,
                timer: 3000,
            });
        } else {
            // إذا حدث خطأ
            Swal.fire({
                icon: 'error',
                title: 'Something Went Wrong...',
                text: msg,
            });
        }
    });
});

// التعامل مع بدء اختبار جديد
$("#StartTest").click(function(e) {
    e.preventDefault();
    var url = 'app/controller/test.inc.php?action=initiateTest';
    var posting = $.post(url);
    posting.done(function(msg) {
        if (msg == 'success') {
            localStorage.clear(); // مسح التخزين المحلي
            // عرض رسالة تحضير الاختبار
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Preparing Your Test',
                footer: 'Don\'t forget to submit your Answers',
                timerProgressBar: true,
                showConfirmButton: false,
                timer: 2000,
                onBeforeOpen: () => {
                    Swal.showLoading();
                    timerInterval = setInterval(() => {
                        const content = Swal.getContent();
                        if (content) {
                            const b = content.querySelector('b');
                            if (b) {
                                b.textContent = Swal.getTimerLeft();
                            }
                        }
                    }, 100);
                },
                onClose: () => {
                    $(location).attr('href', '?tests&resume'); // إعادة التوجيه للاختبار
                }
            });
        } else {
            console.log(posting);
            Swal.fire({
                icon: 'error',
                title: 'Something Went Wrong...',
                text: msg,
            });
        }
    });
});
// التعامل مع مغادرة مجموعة
$(".leaveGroupbtn").click(function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    var url = 'app/controller/group.inc.php?action=leaveGroup';
    // تأكيد مغادرة المجموعة
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't get any tests from this Group!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Leave Group!'
    }).then((result) => {
        if (result.value) {
            var posting = $.post(url, { id: id });
            posting.done(function(msg) {
                if (msg == 'success') {
                    // إذا نجحت المغادرة
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Group Left!',
                        showConfirmButton: false,
                        timer: 1500,
                        onClose: () => {
                            $(location).attr('href', '?groups'); // إعادة التوجيه لصفحة المجموعات
                        }
                    });
                } else {
                    // إذا حدث خطأ
                    Swal.fire({
                        icon: 'error',
                        title: 'Something Went Wrong.',
                        text: msg,
                    });
                }
            });
        }
    });
});

// دالة إرسال الإجابات
function submitAnswers() {
    var url = 'app/controller/test.inc.php?action=submitAnswers';
    var posting = $.post(url, {
        questions: JSON.parse(atob(localStorage.getItem('data'))) // إرسال الإجابات المحفوظة
    });
    posting.done(function(msg) {
        if (msg == 'success') {
            // إذا نجح الإرسال
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Submitting Your Answers',
                timerProgressBar: true,
                showConfirmButton: false,
                timer: 1000,
                onClose: () => {
                    $(location).attr('href', '?results&id=Last'); // إعادة التوجيه لصفحة النتائج
                }
            });
            localStorage.clear(); // مسح التخزين المحلي
        } else {
            console.log(posting);
            // إذا حدث خطأ
            Swal.fire({
                icon: 'error',
                title: 'Something Went Wrong...',
                text: msg,
            });
        }
    });
}