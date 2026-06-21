/**
 * DAME PWA Installer Banner Logic
 * Handles Android/Chromium install prompts, iOS instructions, and Service Worker registration.
 */
(function() {
    'use strict';

    // Global variables passed from WordPress via wp_localize_script:
    // damePwaInstaller = { swUrl, pwaScope, siteName, siteIcon, pwaUrl }
    if (typeof damePwaInstaller === 'undefined') {
        return;
    }

    // 1. Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register(damePwaInstaller.swUrl, { scope: damePwaInstaller.pwaScope })
                .then(function(registration) {
                    // Registration successful
                })
                .catch(function(error) {
                    // Registration failed
                });
        });
    }

    // 2. Check if already running in standalone mode
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    if (isStandalone) {
        return; // App is already installed and running
    }

    // 3. Check dismissal status (7 days cooldown)
    const dismissalTime = localStorage.getItem('dame_pwa_dismissed');
    if (dismissalTime) {
        const now = new Date().getTime();
        const difference = now - parseInt(dismissalTime, 10);
        const sevenDays = 7 * 24 * 60 * 60 * 1000;
        if (difference < sevenDays) {
            return; // Still in cooldown
        }
    }

    // Detect device types (including iPadOS 13+ which presents as Macintosh)
    const isIOS = (/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream) || 
                  (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    let deferredPrompt = null;
    let bannerElement = null;

    // Helper to close and save dismissal state
    function dismissBanner() {
        if (bannerElement) {
            bannerElement.classList.remove('dame-pwa-banner--visible');
            setTimeout(() => {
                bannerElement.remove();
            }, 400);
            localStorage.setItem('dame_pwa_dismissed', new Date().getTime().toString());
        }
    }

    // Create the banner UI
    function createBanner(isIOSPlatform) {
        bannerElement = document.createElement('div');
        bannerElement.className = 'dame-pwa-banner';

        // Header section (Icon + Title)
        const header = document.createElement('div');
        header.className = 'dame-pwa-banner__header';

        const icon = document.createElement('img');
        icon.className = 'dame-pwa-banner__icon';
        icon.src = damePwaInstaller.siteIcon;
        icon.alt = damePwaInstaller.siteName;

        const info = document.createElement('div');
        info.className = 'dame-pwa-banner__info';

        const title = document.createElement('h3');
        title.className = 'dame-pwa-banner__title';
        title.textContent = damePwaInstaller.siteName;

        const subtitle = document.createElement('p');
        subtitle.className = 'dame-pwa-banner__subtitle';
        subtitle.textContent = "Application Web Mobile (PWA)";

        const closeBtn = document.createElement('button');
        closeBtn.className = 'dame-pwa-banner__close';
        closeBtn.innerHTML = '&times;';
        closeBtn.ariaLabel = 'Fermer';
        closeBtn.addEventListener('click', dismissBanner);

        info.appendChild(title);
        info.appendChild(subtitle);
        header.appendChild(icon);
        header.appendChild(info);
        header.appendChild(closeBtn);
        bannerElement.appendChild(header);

        // Body / content section
        const body = document.createElement('div');
        body.className = 'dame-pwa-banner__body';

        if (isIOSPlatform) {
            body.innerHTML = `
                <p>Installez l'application sur votre iPhone / iPad pour un accès rapide et hors-ligne :</p>
                <div class="dame-pwa-banner__instructions">
                    <div class="dame-pwa-banner__instruction-step">
                        1. Appuyez sur le bouton de partage de Safari <span style="font-size: 16px;">⎋</span> (ou icône carrée avec une flèche vers le haut).
                    </div>
                    <div class="dame-pwa-banner__instruction-step">
                        2. Faites défiler et sélectionnez <strong>"Sur l'écran d'accueil"</strong>.
                    </div>
                </div>
            `;
        } else {
            body.innerHTML = "<p>Installez l'application sur votre appareil pour y accéder en un clic et recevoir nos notifications.</p>";
        }
        bannerElement.appendChild(body);

        // Actions section (Buttons)
        const actions = document.createElement('div');
        actions.className = 'dame-pwa-banner__actions';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'dame-pwa-banner__btn dame-pwa-banner__btn--cancel';
        cancelBtn.textContent = 'Plus tard';
        cancelBtn.addEventListener('click', dismissBanner);
        actions.appendChild(cancelBtn);

        if (!isIOSPlatform) {
            const installBtn = document.createElement('button');
            installBtn.className = 'dame-pwa-banner__btn dame-pwa-banner__btn--install';
            installBtn.textContent = 'Installer';
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                    dismissBanner();
                }
            });
            actions.appendChild(installBtn);
        }

        bannerElement.appendChild(actions);
        document.body.appendChild(bannerElement);

        // Trigger slide-up animation
        setTimeout(() => {
            bannerElement.classList.add('dame-pwa-banner--visible');
        }, 100);
    }

    // Android/Chromium prompt handling
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default browser banner
        e.preventDefault();
        deferredPrompt = e;

        // If the banner isn't already created, display it
        if (!bannerElement) {
            createBanner(false);
        }
    });

    // iOS flow (no beforeinstallprompt event, show instructions manually)
    if (isIOS) {
        // We delay slightly to avoid blocking page load
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (!bannerElement) {
                    createBanner(true);
                }
            }, 2000);
        });
    }
})();
