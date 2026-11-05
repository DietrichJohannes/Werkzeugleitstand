const slides = document.querySelectorAll('.slide');
const counter = document.getElementById('counter');

let currentSlide = 0;

function showSlide(index) {

    if (index < 0) {
        index = 0;
    }

    if (index >= slides.length) {
        index = slides.length - 1;
    }

    slides.forEach(slide => {
        slide.classList.remove('active');
    });

    slides[index].classList.add('active');

    currentSlide = index;

    counter.textContent =
        `${currentSlide + 1} / ${slides.length}`;
}

document.getElementById('next').addEventListener('click', () => {
    showSlide(currentSlide + 1);
});

document.getElementById('prev').addEventListener('click', () => {
    showSlide(currentSlide - 1);
});

document.addEventListener('keydown', event => {

    if (
        event.key === 'ArrowRight' ||
        event.key === 'PageDown' ||
        event.key === ' '
    ) {
        showSlide(currentSlide + 1);
    }

    if (
        event.key === 'ArrowLeft' ||
        event.key === 'PageUp'
    ) {
        showSlide(currentSlide - 1);
    }

});