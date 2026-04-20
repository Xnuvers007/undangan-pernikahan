document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const preloadScreen = document.getElementById('preloadScreen');
    const coverOverlay = document.getElementById('coverOverlay');
    const openButton = document.getElementById('openInvitationButton');
    const revealElements = document.querySelectorAll('.reveal');
    const sceneSections = document.querySelectorAll('.scene-section');
    const parallaxTargets = document.querySelectorAll('[data-parallax-speed]');
    const countdownBox = document.querySelector('[data-countdown]');
    const countdownNote = document.getElementById('countdownNote');
    const rsvpForm = document.querySelector('.rsvp-form');
    const floatingLinks = document.querySelectorAll('.floating-nav a');
    const backgroundMusic = document.getElementById('backgroundMusic');
    const musicToggleButton = document.getElementById('musicToggleButton');
    const copyButtons = document.querySelectorAll('.copy-btn[data-copy]');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const initPreloader = () => {
        if (!preloadScreen) {
            body.classList.remove('is-preloading');
            return;
        }

        let isClosed = false;

        const closePreloader = () => {
            if (isClosed) {
                return;
            }

            isClosed = true;
            preloadScreen.classList.add('is-hidden');
            body.classList.remove('is-preloading');

            window.setTimeout(() => {
                preloadScreen.remove();
            }, 650);
        };

        window.setTimeout(closePreloader, 900);
        window.addEventListener(
            'load',
            () => {
                window.setTimeout(closePreloader, 180);
            },
            { once: true }
        );
    };

    initPreloader();

    const guardUnsafeInteractions = () => {
        document.addEventListener('contextmenu', (event) => {
            event.preventDefault();
        });

        document.addEventListener('dragstart', (event) => {
            if (event.target instanceof HTMLImageElement) {
                event.preventDefault();
            }
        });

        document.addEventListener('keydown', (event) => {
            const key = event.key.toLowerCase();
            const ctrlOrMeta = event.ctrlKey || event.metaKey;
            const isBlockedShortcut =
                key === 'f12' ||
                (ctrlOrMeta && event.shiftKey && ['i', 'j', 'c'].includes(key)) ||
                (ctrlOrMeta && ['u', 's'].includes(key));

            if (isBlockedShortcut) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    };

    guardUnsafeInteractions();

    const setMusicButtonState = (playing) => {
        if (!musicToggleButton) {
            return;
        }

        if (playing) {
            musicToggleButton.textContent = 'Musik: On';
            musicToggleButton.classList.add('is-playing');
        } else {
            musicToggleButton.textContent = 'Musik: Off';
            musicToggleButton.classList.remove('is-playing');
        }
    };

    const playMusic = async () => {
        if (!backgroundMusic) {
            return;
        }

        try {
            await backgroundMusic.play();
            setMusicButtonState(true);
        } catch (error) {
            setMusicButtonState(false);
        }
    };

    const pauseMusic = () => {
        if (!backgroundMusic) {
            return;
        }

        backgroundMusic.pause();
        setMusicButtonState(false);
    };

    const openInvitation = () => {
        if (!coverOverlay || coverOverlay.classList.contains('is-hidden') || coverOverlay.classList.contains('is-opening')) {
            return;
        }

        coverOverlay.classList.add('is-opening');
        window.setTimeout(() => {
            coverOverlay.classList.add('is-hidden');
            body.classList.remove('is-locked');
        }, 1150);
    };

    if (openButton) {
        openButton.addEventListener('click', () => {
            openInvitation();
            playMusic();
        });
    }

    if (musicToggleButton) {
        musicToggleButton.addEventListener('click', () => {
            if (!backgroundMusic) {
                return;
            }

            if (backgroundMusic.paused) {
                playMusic();
            } else {
                pauseMusic();
            }
        });
    }

    if (backgroundMusic) {
        backgroundMusic.addEventListener('play', () => setMusicButtonState(true));
        backgroundMusic.addEventListener('pause', () => setMusicButtonState(false));
    }

    copyButtons.forEach((button) => {
        const originalLabel = button.textContent;

        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-copy') || '';
            if (!value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);
                button.textContent = 'Tersalin';
                window.setTimeout(() => {
                    button.textContent = originalLabel;
                }, 1400);
            } catch (error) {
                button.textContent = 'Gagal Salin';
                window.setTimeout(() => {
                    button.textContent = originalLabel;
                }, 1400);
            }
        });
    });

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                entry.target.classList.toggle('in-view', entry.isIntersecting);
            });
        },
        {
            threshold: 0.14,
            rootMargin: '0px 0px -12% 0px',
        }
    );

    if (prefersReducedMotion) {
        revealElements.forEach((item) => item.classList.add('in-view'));
    } else {
        revealElements.forEach((item) => observer.observe(item));
    }

    const initSoftScrollReveal = () => {
        const softTargets = document.querySelectorAll(
            '.person-card, .event-card, .gift-card, .wish-item, .count-box, .section-title, .section-subtitle, .intro-copy, .love-quote, .pantun-minang, .restu-copy, .doa-arabic, .doa-latin, .doa-meaning, .event-blessing, .closing-copy'
        );

        if (softTargets.length === 0) {
            return;
        }

        softTargets.forEach((target, index) => {
            target.classList.add('soft-reveal');
            target.style.setProperty('--soft-reveal-delay', `${(index % 7) * 35}ms`);
        });

        if (prefersReducedMotion) {
            softTargets.forEach((target) => target.classList.add('is-visible'));
            return;
        }

        const softObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    entry.target.classList.toggle('is-visible', entry.isIntersecting);
                });
            },
            {
                threshold: 0.2,
                rootMargin: '0px 0px -8% 0px',
            }
        );

        softTargets.forEach((target) => softObserver.observe(target));
    };

    initSoftScrollReveal();

    const parseDate = (raw) => {
        if (!raw) {
            return null;
        }

        const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
        const date = new Date(normalized);
        return Number.isNaN(date.getTime()) ? null : date;
    };

    const initCountdown = () => {
        if (!countdownBox) {
            return;
        }

        const targetDate = parseDate(countdownBox.getAttribute('data-countdown'));
        if (!targetDate) {
            return;
        }

        const eventLabel = (countdownBox.getAttribute('data-label') || 'Acara').trim() || 'Acara';

        const units = {
            days: countdownBox.querySelector('[data-unit="days"]'),
            hours: countdownBox.querySelector('[data-unit="hours"]'),
            minutes: countdownBox.querySelector('[data-unit="minutes"]'),
            seconds: countdownBox.querySelector('[data-unit="seconds"]'),
        };

        let timerId = null;

        const tick = () => {
            const now = new Date();
            let diff = targetDate.getTime() - now.getTime();

            if (diff <= 0) {
                diff = 0;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const minutes = Math.floor((diff / (1000 * 60)) % 60);
            const seconds = Math.floor((diff / 1000) % 60);

            if (units.days) units.days.textContent = String(days);
            if (units.hours) units.hours.textContent = String(hours).padStart(2, '0');
            if (units.minutes) units.minutes.textContent = String(minutes).padStart(2, '0');
            if (units.seconds) units.seconds.textContent = String(seconds).padStart(2, '0');

            if (diff === 0) {
                countdownBox.classList.add('is-finished');
                if (countdownNote) {
                    countdownNote.textContent = `${eventLabel} telah berlangsung.`;
                }

                if (timerId !== null) {
                    window.clearInterval(timerId);
                    timerId = null;
                }
            } else {
                countdownBox.classList.remove('is-finished');
                if (countdownNote) {
                    countdownNote.textContent = `Menuju ${eventLabel}`;
                }
            }
        };

        tick();
        timerId = window.setInterval(tick, 1000);
    };

    initCountdown();

    if (rsvpForm) {
        rsvpForm.addEventListener('submit', (event) => {
            const button = rsvpForm.querySelector('.submit-btn');
            if (!button) {
                return;
            }

            if (!rsvpForm.checkValidity()) {
                event.preventDefault();
                rsvpForm.reportValidity();
                return;
            }

            button.textContent = 'Mengirim...';
            button.disabled = true;
        });
    }

    const sectionIds = Array.from(floatingLinks)
        .map((link) => (link.getAttribute('href') || '').replace('#', ''))
        .filter(Boolean);

    const sections = sectionIds
        .map((id) => document.getElementById(id))
        .filter(Boolean);

    const updateActiveLink = () => {
        const marker = window.scrollY + 170;
        let activeId = sectionIds[0] || 'home';

        sections.forEach((section) => {
            if (section.offsetTop <= marker) {
                activeId = section.id;
            }
        });

        floatingLinks.forEach((link) => {
            const target = (link.getAttribute('href') || '').replace('#', '');
            if (target === activeId) {
                link.classList.add('is-active');
            } else {
                link.classList.remove('is-active');
            }
        });
    };

    updateActiveLink();
    window.addEventListener('scroll', updateActiveLink, { passive: true });

    floatingLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const targetId = (link.getAttribute('href') || '').replace('#', '');
            if (!targetId) {
                return;
            }

            const targetSection = document.getElementById(targetId);
            if (!targetSection) {
                return;
            }

            event.preventDefault();

            const targetTop = targetSection.getBoundingClientRect().top + window.scrollY - 112;
            window.scrollTo({
                top: Math.max(0, targetTop),
                behavior: 'smooth',
            });

            targetSection.classList.add('section-focus');
            window.setTimeout(() => {
                targetSection.classList.remove('section-focus');
            }, 900);
        });
    });

    const initParallax = () => {
        if (prefersReducedMotion || parallaxTargets.length === 0) {
            return;
        }

        let ticking = false;

        const updateParallax = () => {
            const offsetY = window.scrollY;

            parallaxTargets.forEach((element) => {
                const speed = Number(element.getAttribute('data-parallax-speed') || '0');
                const shift = `${(offsetY * speed).toFixed(2)}px`;

                if (element.classList.contains('parallax-layer')) {
                    element.style.setProperty('--parallax-shift', shift);
                } else {
                    element.style.setProperty('--parallax-offset', shift);
                }
            });

            ticking = false;
        };

        const onScroll = () => {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(updateParallax);
        };

        updateParallax();
        window.addEventListener('scroll', onScroll, { passive: true });
    };

    if (sceneSections.length > 0) {
        initParallax();
    }
});