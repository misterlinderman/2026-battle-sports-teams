/**
 * Mobile navigation: hamburger opens slide-out drawer.
 *
 * @package Battle_Sports
 */

const hamburger = document.querySelector('.primary-nav__hamburger');
const primaryNav = document.querySelector('.primary-nav');
const backdrop = document.querySelector('.primary-nav__backdrop');

function openDrawer() {
  if (primaryNav) primaryNav.classList.add('is-open');
  if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
  document.body.classList.add('nav-open');
}

function closeDrawer() {
  if (primaryNav) primaryNav.classList.remove('is-open');
  if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
  document.body.classList.remove('nav-open');
}

function toggleDrawer() {
  const isOpen = primaryNav?.classList.contains('is-open');
  if (isOpen) {
    closeDrawer();
  } else {
    openDrawer();
  }
}

if (hamburger && primaryNav) {
  hamburger.addEventListener('click', toggleDrawer);
}

if (backdrop) {
  backdrop.addEventListener('click', closeDrawer);
}

// Close drawer when pressing Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && primaryNav?.classList.contains('is-open')) {
    closeDrawer();
  }
});
