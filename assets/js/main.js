const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');

if (menuToggle && navLinks) {
    menuToggle.addEventListener('click', () => {
        navLinks.classList.toggle('open');
    });
}

const notifyToggle = document.getElementById('notifyToggle');
const notifyMenu = document.getElementById('notifyMenu');

if (notifyToggle && notifyMenu) {
    notifyToggle.addEventListener('click', () => {
        const isOpen = notifyMenu.classList.toggle('open');
        notifyToggle.setAttribute('aria-expanded', String(isOpen));
        notifyMenu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        if (!notifyMenu.contains(target) && !notifyToggle.contains(target)) {
            notifyMenu.classList.remove('open');
            notifyToggle.setAttribute('aria-expanded', 'false');
            notifyMenu.setAttribute('aria-hidden', 'true');
        }
    });
}

const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                observer.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.15 }
);

document.querySelectorAll('.reveal').forEach((node) => observer.observe(node));

document.querySelectorAll('.reveal').forEach((node, index) => {
    node.style.transitionDelay = `${Math.min(index * 60, 360)}ms`;
});
