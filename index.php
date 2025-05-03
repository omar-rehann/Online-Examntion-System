<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Online Exam System for students and instructors">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ONLINE EXAM SYSTEM</title>

    <link rel="icon" href="favicon.ico">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="stylee.css">

<body>
    <!-- Preloader -->
    <div class="preloader"></div>

    <!-- Header Section -->
    <header id="home" class="block">
        <div class="header-overlay d-flex align-items-center">
            <div class="container d-flex justify-content-center">
                <div class="header-box text-center">
                    <h1 class="text-white">ONLINE EXAM SYSTEM</h1>
                    <div class="d-flex flex-wrap justify-content-center mt-3">
                        <a class="btn btn-success text-white title-link" href="student">
                            <i class="fas fa-user"></i> I'm Student
                        </a>
                        <a class="btn btn-primary text-white title-link" href="instructor">
                            <i class="fas fa-chalkboard-teacher"></i> I'm Instructor
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Same original scripts -->
    <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-backstretch/2.1.18/jquery.backstretch.min.js" integrity="sha256-OZZMwc3o7txR3vFfunl0M9yk3SayGp444eZdL9QDi1Y=" crossorigin="anonymous"></script>
    <script>
        jQuery(document).ready(function() {
    // Preloader
    setTimeout(function() {
        $('.preloader').fadeOut('slow');

        // Activate card animations after preloader finishes
        setTimeout(function() {
            $('.header-box').addClass('animate');

            // Add pulse animation to buttons after they appear
            setTimeout(function() {
                $('.title-link').addClass('pulse');
            }, 1500);
        }, 300);
    }, 1500);
    // Background with backstretch
    $.backstretch("BackGround/photo.jpg", { speed: 500 });
});
    </script>
</body>
</html>