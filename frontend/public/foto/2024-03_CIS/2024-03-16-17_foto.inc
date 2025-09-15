<!-- Container for the image gallery -->
<div class="container">

  <!-- Full-width images with number text -->
  <div class="mySlides">
    <div class="numbertext">1 / 14</div>
      <img src="foto/2024-03_CIS/01.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">2 / 14</div>
      <img src="foto/2024-03_CIS/02.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">3 / 14</div>
      <img src="foto/2024-03_CIS/03.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">4 / 14</div>
      <img src="foto/2024-03_CIS/04.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">5 / 14</div>
      <img src="foto/2024-03_CIS/05.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">6 / 14</div>
      <img src="foto/2024-03_CIS/06.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">7 / 14</div>
      <img src="foto/2024-03_CIS/07.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">8 / 14</div>
      <img src="foto/2024-03_CIS/08.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">9 / 14</div>
      <img src="foto/2024-03_CIS/09.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">10 / 14</div>
      <img src="foto/2024-03_CIS/10.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">11 / 14</div>
      <img src="foto/2024-03_CIS/11.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">12 / 14</div>
      <img src="foto/2024-03_CIS/12.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">13 / 14</div>
      <img src="foto/2024-03_CIS/13.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">14 / 14</div>
      <img src="foto/2024-03_CIS/14.jpg" style="width:100%">
  </div>

  <!-- Next and previous buttons -->
  <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
  <a class="next" onclick="plusSlides(1)">&#10095;</a>

  <!-- Image text -->
<!--
  <div class="caption-container">
    <p id="caption"></p>
  </div>
-->

</div>

<script>
let slideIndex = 1;
showSlides(slideIndex);

function plusSlides2(n) {
  showSlides2(slideIndex += n);
}

function currentSlide2(n) {
  showSlides2(slideIndex = n);
}

function showSlides2(n) {
  let i;
  let slides = document.getElementsByClassName("mySlides");
  let dots = document.getElementsByClassName("demo");
  let captionText = document.getElementById("caption");
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
  captionText.innerHTML = dots[slideIndex-1].alt;
}
</script>

