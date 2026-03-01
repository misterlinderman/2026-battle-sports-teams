/**
 * Mobile navigation toggle.
 *
 * @package Battle_Sports
 */

const hamburger = document.querySelector('.primary-nav__hamburger');
const navMenu = document.querySelector('#primary-nav-menu');

if (hamburger && navMenu) {
  hamburger.addEventListener('click', () => {
    const expanded = hamburger.getAttribute('aria-expanded') === 'true';
    hamburger.setAttribute('aria-expanded', !expanded);
    navMenu.classList.toggle('is-open');

    // Toggle body scroll lock on mobile
    document.body.classList.toggle('nav-open', !expanded);
  });
}
