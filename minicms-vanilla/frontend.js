
// ========================================
// carousel

function selectImg(carousel, id, select) {
  var elt = carousel.imgs[id];
  if (elt == null) {
    console.error("Carousel selectImg() : provided id doesn't exists", carousel, id, select);
    return;
  }

  if (select === false) {
    elt.className = elt.className.replace(" carousel-selected-img", "");
    carousel.selectedImgId = undefined;
  }
  else {
    // selectImg(carousel, carousel.selectedImgId, false);

    elt.className += " carousel-selected-img";
    carousel.selectedImgId = id;
  }
}

// direction : -1 (left) or +1
function updateImgId(carousel, direction) {
  id = carousel.selectedImgId + direction;

  if (id > carousel.imgs.length - 1)
    id = 0;
  else if (id < 0)
    id = carousel.imgs.length - 1;

  selectImg(carousel, id);
}

// loop through carousels
var carousels = document.getElementsByClassName("carousel");

for (var i=0; i<carousels.length; i++) {
  var carousel = carousels[i];
  var style = getComputedStyle(carousel);
  console.log(style);

  // initially hide imgs until they are selected
  var imgs = carousel.getElementsByClassName("carousel-img");
  for (var imgI=0; imgI<imgs.length; imgI++) {
    var img = imgs[imgI];
    img.className += " js-loaded";
    // console.log(img.className);
  }

  // selected first img
  carousel.imgs = imgs;
  carousel.selectedImgId = 0;
  selectImg(carousel, 0);

  // get clicks on the left and right arrows
  var left = carousel.querySelector(".carousel-arrows.left");
  left.addEventListener("click", function() {
    updateImgId(carousel, -1);
  });

  var right = carousel.querySelector(".carousel-arrows.right");
  right.addEventListener("click", function() {
    updateImgId(carousel, 1);
  });

  // get clicks on indicators
  var indicators = carousel.getElementsByClassName("carousel-indicator");
  for (var j=0; j<indicators.length; j++) {
    var ind = indicators[j];
    ind.addEventListener("click", function(event) {
      var id = parseInt(event.target.getAttribute("data-slide-to"));
      selectImg(carousel, id);
    });
  }
}
