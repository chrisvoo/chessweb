<!-- Container for the image gallery -->
<div class="container">

  <!-- Full-width images with number text -->
  <div class="mySlides">
    <div class="numbertext">1 / 20</div>
      <img src="foto\2025-02_prov\01.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">2 / 20</div>
      <img src="foto\2025-02_prov\02.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">3 / 20</div>
      <img src="foto/2025-02_prov/03.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">4 / 20</div>
      <img src="foto\2025-02_prov\04.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">5 / 20</div>
      <img src="foto\2025-02_prov\05.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">6 / 20</div>
      <img src="foto\2025-02_prov\06.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">7 / 20</div>
      <img src="foto\2025-02_prov\07.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">8 / 20</div>
      <img src="foto\2025-02_prov\08.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">9 / 20</div>
      <img src="foto\2025-02_prov\09.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">10 / 20</div>
      <img src="foto\2025-02_prov\10.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">11 / 20</div>
      <img src="foto\2025-02_prov\11.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">12 / 20</div>
      <img src="foto\2025-02_prov\12.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">13 / 20</div>
      <img src="foto\2025-02_prov\13.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">14 / 20</div>
      <img src="foto\2025-02_prov\14.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">15 / 20</div>
      <img src="foto\2025-02_prov\15.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">16 / 20</div>
      <img src="foto\2025-02_prov\16.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">17 / 20</div>
      <img src="foto\2025-02_prov\17.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">18 / 20</div>
      <img src="foto\2025-02_prov\18.jpeg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">19 / 20</div>
      <img src="foto\2025-02_prov\19.jpg" style="width:100%">
  </div>
  <div class="mySlides">
    <div class="numbertext">20 / 20</div>
      <img src="foto\2025-02_prov\20.jpeg" style="width:100%">
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

function plusSlides(n) {
  showSlides(slideIndex += n);
}

function currentSlide(n) {
  showSlides(slideIndex = n);
}

function showSlides(n) {
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

