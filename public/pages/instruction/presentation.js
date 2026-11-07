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

// Die Wartungsanleitung wird aus dem Werkzeug-Dialog in einem neuen Tab geöffnet.
// Dort gibt es keinen Verlauf, also Tab schließen oder zur Startseite wechseln.
document.getElementById('back').addEventListener('click', () => {
    if (history.length > 1) {
        history.back();
        return;
    }

    window.close();
    location.href = '/';
});

document.getElementById('next').addEventListener('click', () => {
    showSlide(currentSlide + 1);
});

document.getElementById('prev').addEventListener('click', () => {
    showSlide(currentSlide - 1);
});

document.addEventListener('keydown', event => {

    // Pfeiltasten und Leertaste gehören in Auswahlfeldern dem Feld, nicht der Präsentation
    if (event.target.matches('select, input, textarea')) {
        return;
    }

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