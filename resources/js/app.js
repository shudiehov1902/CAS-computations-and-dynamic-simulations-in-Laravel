const navigationToggle = document.querySelector('.nav-toggle');
const navigationLinks = document.querySelector('.nav-links');

if (navigationToggle && navigationLinks) {
    navigationToggle.addEventListener('click', () => {
        const isOpen = navigationToggle.getAttribute('aria-expanded') === 'true';

        navigationToggle.setAttribute('aria-expanded', String(!isOpen));
        navigationToggle.classList.toggle('is-open', !isOpen);
        navigationLinks.classList.toggle('is-open', !isOpen);
    });
}
