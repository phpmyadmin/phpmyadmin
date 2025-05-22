import { AJAX } from './modules/ajax.ts';
import { showGitVersion } from './modules/git-info.ts';
import { ThemesManager } from './modules/themes-manager.ts';

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
