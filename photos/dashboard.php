<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookMyShow Clone</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        .navbar {
            display: flex; 
            align-items: center;   
            gap: 25px;            
            padding: 20px 25px;   
            box-shadow: 0px 4px 10px 0px rgba(0, 0, 0, 0.2); 
            background-color: #fff;
        }
        
        .navbar .logo {
            width: 125px;
            height: auto;
        }

        .search-container {
            border: solid black 1px; 
            width: 200px; 
        }
        .search-container input {
            width: 100%;
            padding: 5px;
        }
        .signup {
            background-color: rgb(217,90,101);
            border: solid rgb(217,90,101); 
            color: white; 
            margin-left: auto; 
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 15px;
        }

        .slideshow-container {
            max-width: 1000px;
            position: relative;
            margin: 20px auto;
        }

        .mySlides {
            display: none;
        }

        .mySlides img {
            width: 100%;
            height: 400px; 
            object-fit: cover; 
        }

        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            margin-top: -22px;
            padding: 16px;
            color: white;
            font-weight: bold;
            font-size: 18px;
            transition: 0.6s ease;
            background-color: rgba(0,0,0,0.3);
            user-select: none;
        }

        .next {
            right: 0;
        }

        .prev:hover, .next:hover {
            background-color: rgba(0,0,0,0.8);
        }

        .text {
            color: #f2f2f2;
            font-size: 15px;
            padding: 12px;
            position: absolute;
            bottom: 0;
            width: 100%;
            text-align: center;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
        }

        .numbertext {
            color: #f2f2f2;
            font-size: 12px;
            padding: 8px 12px;
            position: absolute;
            top: 0;
        }

        .dot {
            cursor: pointer;
            height: 15px;
            width: 15px;
            margin: 0 2px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
        }

        .active, .dot:hover {
            background-color: #717171;
        }

        .fade {
            animation-name: fade;
            animation-duration: 1.5s;
        }

        @keyframes fade {
            from {opacity: .4}
            to {opacity: 1}
        }

        .movies {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            font-family: Arial, sans-serif;
        }

        .movie {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;           
            justify-content: center;
            margin-top: 20px;
        }

        .movie img {
            width: 200px;         
            height: 300px;        
            object-fit: cover;   
            border-radius: 8px;   
        }

        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    
    <div class="navbar">
        <img src="a.png" alt="Logo" class="logo">
        <div class="search-container">
            <input type="search" name="search" id="search">
        </div>
        <a href="form.php" class="signup">Sign in</a>
    </div>
    
    <div class="slideshow-container">
        <div class="mySlides fade">
            <div class="numbertext">1 / 3</div>
            <a href="movie-details.php?id=obsession"><img src="./photos/Obsession.jpg" alt="Obsession"></a>
            <div class="text">Caption Text</div>
        </div>

        <div class="mySlides fade">
            <div class="numbertext">2 / 3</div>
            <a href="movie-details.php?id=bhootbangla"><img src="./photos/bhootbangla.png" alt="Bhoot Bangla"></a>
            <div class="text">Caption Two</div>
        </div>

        <div class="mySlides fade">
            <div class="numbertext">3 / 3</div>
            <a href="movie-details.php?id=dhurandhar2"><img src="./photos/dhurandhar2.jpg" alt="Dhurandhar 2"></a>
            <div class="text">Caption Three</div>
        </div>

        <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
        <a class="next" onclick="plusSlides(1)">&#10095;</a>
    </div>
    <br>

    <div style="text-align:center">
        <span class="dot" onclick="currentSlide(1)"></span>
        <span class="dot" onclick="currentSlide(2)"></span>
        <span class="dot" onclick="currentSlide(3)"></span>
    </div>

    <div class="movies">
        <h2>Movies</h2>
        <div class="movie">
            <a href="movie-details.php?id=animal"><img src="./photos/animal.jpg" alt=""></a>
            <a href="movie-details.php?id=cocktail"><img src="./photos/cocktail.jpg" alt=""></a>
            <a href="movie-details.php?id=dhurandhar"><img src="./photos/dhurandhar.jpg" alt=""></a>
            <a href="movie-details.php?id=obsession2"><img src="./photos/obsession2.jpg" alt=""></a>
            <a href="movie-details.php?id=bandar"><img src="./photos/bandar.jpg" alt=""></a>
            <a href="movie-details.php?id=haunted"><img src="./photos/huanted.jpg" alt=""></a>
            <a href="movie-details.php?id=images9"><img src="./photos/images9.jpeg" alt=""></a>
            <a href="movie-details.php?id=peddi"><img src="./photos/peddi.jpg" alt=""></a>
            <a href="movie-details.php?id=hoppers"><img src="./photos/hoppers.jpg" alt=""></a>
            <a href="movie-details.php?id=odyssey"><img src="./photos/odyssey.jpg" alt=""></a>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 BookMyShow. All rights reserved.</p>
        <p>Contact us at: info@example.com</p>
    </footer>

    <script>
        let slideIndex = 1;
        showSlides(slideIndex);

        function plusSlides(n) {
            showSlides(slideIndex += n);
        }

        function currentSlide(n) {
            showSlides(slideIndex = n);
        }

        function showSlides(n) {
            let i;
            let slides = document.getElementsByClassName("mySlides");
            let dots = document.getElementsByClassName("dot");
            if (n > slides.length) {slideIndex = 1}
            if (n < 1) {slideIndex = slides.length}
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "");
            }
            slides[slideIndex-1].style.display = "block";
            dots[slideIndex-1].className += " active";
        }
    </script>
</body>
</html>