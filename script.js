const menuButton = document.querySelector('.menu-toggle');
const nav = document.querySelector('.site-header nav');

if (menuButton && nav) {
  menuButton.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(open));
  });

  nav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      nav.classList.remove('open');
      menuButton.setAttribute('aria-expanded', 'false');
    });
  });
}

document.querySelectorAll('#year').forEach(year => {
  year.textContent = new Date().getFullYear();
});

const signupForm = document.querySelector('#signup-form');

if (signupForm) {
  signupForm.addEventListener('submit', event => {
    event.preventDefault();

    const message = event.currentTarget.querySelector('.form-message');

    if (message) {
      message.textContent = 'Thank you — we’ll be in touch.';
    }

    event.currentTarget.reset();
  });
}