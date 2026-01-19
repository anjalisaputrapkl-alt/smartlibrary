(function(){
  // Smooth scroll for anchor CTAs
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e)=>{
      const targetId = link.getAttribute('href').slice(1);
      const target = document.getElementById(targetId);
      if(target){
        e.preventDefault();
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if(prefersReduced){
          target.focus();
          window.scrollTo({top: target.offsetTop - 20});
        } else {
          target.scrollIntoView({behavior: 'smooth', block: 'start'});
          setTimeout(()=> target.focus(), 600);
        }
      }
    });
  });

  // Simple focus management for accessibility
  document.querySelectorAll('a, button').forEach(el => {
    el.setAttribute('tabindex', '0');
  });
})();

    // Navigation Toggle
    function toggleNav() {
      const nav = document.querySelector('.main-nav');
      const toggle = document.querySelector('.nav-toggle');
      nav.classList.toggle('active');
      
      // Toggle aria-expanded for accessibility
      const isActive = nav.classList.contains('active');
      toggle.setAttribute('aria-expanded', isActive);
      toggle.setAttribute('aria-label', isActive ? 'Close menu' : 'Open menu');
    }

    // Close nav when clicking on a link
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.main-nav a').forEach(link => {
        link.addEventListener('click', (e) => {
          // Check if it's a same-page link
          const href = link.getAttribute('href');
          if (href.startsWith('#')) {
            // Close menu after clicking a link
            setTimeout(() => {
              document.querySelector('.main-nav').classList.remove('active');
              document.querySelector('.nav-toggle').setAttribute('aria-expanded', 'false');
            }, 100);
          }
        });
      });
    });

    // Close nav when clicking outside
    document.addEventListener('click', (e) => {
      const nav = document.querySelector('.main-nav');
      const toggle = document.querySelector('.nav-toggle');
      
      // Only close if menu is active and click is outside both nav and toggle
      if (nav && toggle && nav.classList.contains('active')) {
        if (!nav.contains(e.target) && !toggle.contains(e.target)) {
          nav.classList.remove('active');
          toggle.setAttribute('aria-expanded', 'false');
        }
      }
    });

    // Close nav on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const nav = document.querySelector('.main-nav');
        const toggle = document.querySelector('.nav-toggle');
        if (nav && toggle) {
          nav.classList.remove('active');
          toggle.setAttribute('aria-expanded', 'false');
        }
      }
    });

    // Close menu when window is resized to desktop
    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        const isMobile = window.innerWidth < 900;
        const nav = document.querySelector('.main-nav');
        const toggle = document.querySelector('.nav-toggle');
        
        if (!isMobile && nav && nav.classList.contains('active')) {
          nav.classList.remove('active');
          if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }
      }, 250);
    });

    // Scroll Animation Observer
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.animation = getAnimationName(entry.target) + ' 0.6s ease-out forwards';
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    function getAnimationName(element) {
      const classes = element.classList;
      if (classes.contains('value')) return 'fadeInUp';
      if (classes.contains('section-img')) return 'fadeInRight';
      if (classes.contains('split')) return 'fadeInLeft';
      return 'fadeInUp';
    }

    // Observe all animated elements
    document.querySelectorAll('.value, .section-img, .split .col').forEach((el) => {
      el.style.opacity = '0';
      observer.observe(el);
    });

    // Parallax Effect
    const isMobile = window.innerWidth <= 768;
    if (!isMobile) {
      window.addEventListener('scroll', () => {
        const hero = document.querySelector('.hero');
        if (hero) {
          hero.style.backgroundPosition = `0 ${window.scrollY * 0.5}px`;
        }
      });
    }

    // Smooth scroll for anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({ behavior: 'smooth' });
        }
      });
    });

    // Button ripple effect
    document.querySelectorAll('.btn, .nav-btn').forEach(button => {
      button.addEventListener('click', function (e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');

        this.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
      });
    });

    // Counter animation for stats
    const stats = document.querySelectorAll('.stats .value h3');
    let animated = false;

    window.addEventListener('scroll', () => {
      if (!animated && isElementInViewport(document.querySelector('.stats'))) {
        animateCounters();
        animated = true;
      }
    });

    function isElementInViewport(el) {
      const rect = el.getBoundingClientRect();
      return (
        rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.bottom >= 0
      );
    }

    function animateCounters() {
      stats.forEach(stat => {
        const finalValue = stat.textContent;
        const numericValue = parseInt(finalValue);
        let currentValue = 0;
        const increment = numericValue / 30;

        const counter = setInterval(() => {
          currentValue += increment;
          if (currentValue >= numericValue) {
            stat.textContent = finalValue;
            clearInterval(counter);
          } else {
            const displayValue = finalValue.includes('.')
              ? currentValue.toFixed(0) + '%'
              : currentValue.toFixed(0) + '+';
            stat.textContent = displayValue;
          }
        }, 30);
      });
    }

    // Mouse move parallax on cards (Desktop only)
    if (!isMobile) {
      document.querySelectorAll('.value').forEach(card => {
        card.addEventListener('mousemove', (e) => {
          const rect = card.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;

          const centerX = rect.width / 2;
          const centerY = rect.height / 2;

          const rotateX = (y - centerY) / 10;
          const rotateY = (centerX - x) / 10;

          card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
        });

        card.addEventListener('mouseleave', () => {
          card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1.02)';
        });
      });
    }

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        const isMobileNow = window.innerWidth <= 768;
        if (isMobileNow !== isMobile) {
          window.location.reload();
        }
      }, 250);
    });