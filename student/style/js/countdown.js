function startTimer(duration, display) {
    var timer = duration,
        minutes, seconds;
    var t = setInterval(function() {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = minutes + ":" + seconds;

        console.log("Timer value:", timer); // تصحيح
        if (timer === 1) {
            console.log("Timer reached 00:01, attempting to save answers...");
            clearInterval(t);
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Submitting Your Answers',
                timerProgressBar: true,
                showConfirmButton: false,
                timer: 500
            });
            try {
                saveAnswer();
                submitAnswers().catch(error => {
                    console.error("Error in submitAnswers:", error);
                });
            } catch (error) {
                console.error("Error in saveAnswer or submitAnswers:", error);
            }
            setTimeout(() => {
                console.log("Redirecting to home page...");
                window.location.href = "http://localhost/test/student/?home";
            }, 500);
        } else if (timer === 4 * 60) { // تحذير عند 4 دقايق
            console.log("Showing warning at 4 minutes");
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                onOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });

            Toast.fire({
                icon: 'warning',
                title: 'You are running out of time'
            });
        }

        --timer; // تقليل التايمر بعد الفحص
    }, 1000);
}

function setTimer(time) {
    var display = document.querySelector('#timer');
    startTimer(time, display);
}