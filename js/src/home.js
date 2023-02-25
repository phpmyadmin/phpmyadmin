import { AJAX } from './modules/ajax.js';
import { showGitVersion } from './modules/git-info.js';
import { ThemesManager } from './modules/themes-manager.js';

AJAX.registerTeardown('home.js', () => {
    const themesModal = document.getElementById('themesModal');
    if (themesModal) {
        themesModal.removeEventListener('show.bs.modal', ThemesManager.handleEvent);
    }
});

AJAX.registerOnload('home.js', () => {
    const themesModal = document.getElementById('themesModal');
    if (themesModal) {
        themesModal.addEventListener('show.bs.modal', ThemesManager.handleEvent);
    }

    showGitVersion();
});
