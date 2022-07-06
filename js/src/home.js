import { showGitVersion } from './modules/git-info.js';
import { ThemesManager } from './modules/themes-manager.js';

window.AJAX.registerTeardown('home.js', () => {
    const themesModal = document.getElementById('themesModal');
    if (themesModal) {
        themesModal.removeEventListener('show.bs.modal', ThemesManager.handleEvent);
    }
});

window.AJAX.registerOnload('home.js', () => {
    const themesModal = document.getElementById('themesModal');
    if (themesModal) {
        themesModal.addEventListener('show.bs.modal', ThemesManager.handleEvent);
    }

    showGitVersion();
});
