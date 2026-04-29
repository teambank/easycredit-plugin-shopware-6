import CookieStorageHelper from 'src/helper/storage/cookie-storage.helper';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';

const EASYCREDIT_COMPONENTS_COOKIE = 'easycredit-components';
const EASYCREDIT_SCRIPT_SELECTOR = 'script[data-easycredit-cookie-script="true"]';
const EASYCREDIT_RUNTIME_SCRIPT_SELECTOR = 'script[data-easycredit-runtime-script="true"]';

function removeEasyCreditElements() {
    const elements = Array.from(document.querySelectorAll('*'))
        .filter((element) => element.tagName.toLowerCase().startsWith('easycredit-'));

    for (const element of elements) {
        element.remove();
    }
}

function cleanupEasyCreditRuntime() {
    removeEasyCreditElements();

    const runtimeScripts = document.querySelectorAll(EASYCREDIT_RUNTIME_SCRIPT_SELECTOR);
    for (const script of runtimeScripts) {
        script.remove();
    }
}

function loadEasyCreditWebcomponentsIfAllowed() {
    if (!CookieStorageHelper.getItem(EASYCREDIT_COMPONENTS_COOKIE)) {
        return;
    }

    loadEasyCreditWebcomponents();
}

function loadEasyCreditWebcomponents() {
    if (document.querySelector(EASYCREDIT_RUNTIME_SCRIPT_SELECTOR)) {
        return;
    }

    const placeholders = document.querySelectorAll(EASYCREDIT_SCRIPT_SELECTOR);
    if (!placeholders.length) {
        return;
    }

    for (const placeholder of placeholders) {
        const src = placeholder.getAttribute('src');
        if (!src) {
            continue;
        }

        const script = document.createElement('script');
        script.type = 'module';
        script.src = src;
        script.defer = true;
        script.setAttribute('fetchpriority', 'low');
        script.setAttribute('data-easycredit-runtime-script', 'true');

        document.head.appendChild(script);
        break;
    }
}

export function initEasyCreditCookieConsent() {
    document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, (event) => {
        const updatedCookies = event?.detail ?? {};

        if (updatedCookies[EASYCREDIT_COMPONENTS_COOKIE]) {
            loadEasyCreditWebcomponentsIfAllowed();
            return;
        }

        if (updatedCookies[EASYCREDIT_COMPONENTS_COOKIE] === false) {
            cleanupEasyCreditRuntime();
        }
    });

    loadEasyCreditWebcomponentsIfAllowed();
}

