import $ from 'jquery';

/**
 * Turns every not-yet-enhanced `select.search` into a searchable combobox:
 * the native select is kept (hidden) so the form still submits its value,
 * while a text input lets the user filter the option list as they type.
 */
function enhanceSearchableSelects (): void {
    $('select.search').each(function () {
        const select = this as HTMLSelectElement;
        if (select.multiple || $(select).closest('.searchable-select').length > 0) {
            return;
        }

        const $select = $(select);
        $select.addClass('searchable-select-native');
        $select.wrap('<div class="searchable-select"></div>');
        const $wrapper = $select.parent();

        const $toggle = $('<button>', {
            type: 'button',
            class: 'searchable-select-toggle form-select',
            'aria-haspopup': 'listbox',
            'aria-expanded': 'false'
        }).append($('<span>', { class: 'searchable-select-toggle-text' }));

        const $menu = $('<div>', { class: 'searchable-select-menu dropdown-menu' })
            .append(
                $('<input>', {
                    type: 'text',
                    class: 'searchable-select-search form-control form-control-sm',
                    placeholder: window.Messages.searchList
                }).attr('autocomplete', 'off')
            )
            .append($('<ul>', { class: 'searchable-select-options', role: 'listbox' }))
            .append($('<div>', { class: 'searchable-select-empty' }).text(window.Messages.strSearchableSelectNoResults));

        if (select.disabled) {
            $toggle.prop('disabled', true);
        }

        $wrapper.append($toggle).append($menu);
        $wrapper.data('searchableSelectMenu', $menu);
        $menu.data('searchableSelectWrapper', $wrapper);
        updateToggleText($wrapper);

        const id = select.id;
        if (id) {
            $('label[for="' + id + '"]').on('click', function (event) {
                event.preventDefault();
                $toggle.trigger('focus').trigger('click');
            });
        }
    });
}

function updateToggleText ($wrapper: JQuery): void {
    const select = $wrapper.find('select.searchable-select-native')[0] as HTMLSelectElement;
    const selectedOption = select.options[select.selectedIndex] as HTMLOptionElement | undefined;
    $wrapper.find('.searchable-select-toggle-text').text(selectedOption ? (selectedOption.label || selectedOption.text) : '');
}

/**
 * The menu is detached to the end of the document body while open (see positionMenu),
 * so it can escape any `overflow: hidden|auto|scroll` ancestor (e.g. a scrollable table).
 * These helpers resolve the wrapper/menu pair regardless of where the menu currently lives.
 */
function getMenu ($wrapper: JQuery): JQuery {
    return $wrapper.data('searchableSelectMenu');
}

function getWrapper (el: HTMLElement): JQuery {
    const $inWrapper = $(el).closest('.searchable-select');
    if ($inWrapper.length > 0) {
        return $inWrapper;
    }

    return $(el).closest('.searchable-select-menu').data('searchableSelectWrapper');
}

function closeMenu ($wrapper: JQuery): void {
    const $menu = getMenu($wrapper);
    $wrapper.removeClass('show');
    $wrapper.find('.searchable-select-toggle').attr('aria-expanded', 'false');
    $menu.removeClass('show').removeAttr('style');
    if ($menu.parent().get(0) !== $wrapper.get(0)) {
        $wrapper.append($menu);
    }
}

function closeOtherMenus ($except: JQuery): void {
    $('.searchable-select.show').each(function () {
        if (this !== $except.get(0)) {
            closeMenu($(this));
        }
    });
}

/**
 * Positions the (already detached) menu as a viewport-fixed element anchored to its toggle,
 * flipping above the toggle when there is not enough room below.
 */
function positionMenu ($wrapper: JQuery, $menu: JQuery): void {
    document.body.appendChild($menu.get(0) as HTMLElement);
    $menu.addClass('show').css({ position: 'fixed', visibility: 'hidden', top: '0px', left: '0px', width: '' });

    const toggleEl = $wrapper.find('.searchable-select-toggle').get(0) as HTMLElement;
    const toggleRect = toggleEl.getBoundingClientRect();
    const menuHeight = ($menu.get(0) as HTMLElement).offsetHeight;
    const viewportHeight = document.documentElement.clientHeight;
    const viewportWidth = document.documentElement.clientWidth;

    let top = toggleRect.bottom;
    if (top + menuHeight > viewportHeight && toggleRect.top - menuHeight >= 0) {
        top = toggleRect.top - menuHeight;
    }

    const left = Math.max(0, Math.min(toggleRect.left, viewportWidth - toggleRect.width));

    $menu.css({ top: top + 'px', left: left + 'px', width: toggleRect.width + 'px', visibility: '' });
}

function buildOptionsList ($wrapper: JQuery): void {
    const select = $wrapper.find('select.searchable-select-native')[0] as HTMLSelectElement;
    const $list = getMenu($wrapper).find('.searchable-select-options');
    $list.empty();

    let $currentGroup: JQuery | null = null;
    let currentGroupLabel: string | null = null;

    Array.from(select.options).forEach(function (option) {
        const parent = option.parentElement;
        const isGrouped = parent !== null && parent.tagName === 'OPTGROUP';
        const groupLabel = isGrouped ? (parent as HTMLOptGroupElement).label : null;

        if (groupLabel !== currentGroupLabel) {
            currentGroupLabel = groupLabel;
            $currentGroup = null;
        }

        if (isGrouped && $currentGroup === null) {
            $currentGroup = $('<ul>', { class: 'searchable-select-optgroup-items' });
            $('<li>', { class: 'searchable-select-optgroup' })
                .append($('<div>', { class: 'searchable-select-optgroup-label' }).text(groupLabel as string))
                .append($currentGroup)
                .appendTo($list);
        }

        const $option = $('<li>', {
            class: 'searchable-select-option' + (option.selected ? ' selected' : ''),
            role: 'option',
            'data-value': option.value
        }).text(option.label || option.text);

        if (option.disabled) {
            $option.addClass('disabled').attr('aria-disabled', 'true');
        }

        ($currentGroup ?? $list).append($option);
    });

    filterOptionsList($wrapper, '');
}

function filterOptionsList ($wrapper: JQuery, query: string): void {
    const $menu = getMenu($wrapper);
    const needle = query.trim().toLowerCase();
    let visibleCount = 0;

    $menu.find('.searchable-select-option').each(function () {
        const matches = needle === '' || $(this).text().toLowerCase().indexOf(needle) > -1;
        $(this).toggleClass('d-none', ! matches);
        if (matches) {
            visibleCount++;
        }
    });

    $menu.find('.searchable-select-optgroup').each(function () {
        const hasVisibleOption = $(this).find('.searchable-select-option:not(.d-none)').length > 0;
        $(this).toggleClass('d-none', ! hasVisibleOption);
    });

    $menu.find('.searchable-select-empty').toggleClass('d-none', visibleCount > 0);
    highlightOption($menu, $menu.find('.searchable-select-option:not(.d-none):not(.disabled)').first());
}

function highlightOption ($menu: JQuery, $option: JQuery): void {
    $menu.find('.searchable-select-option.highlighted').removeClass('highlighted');
    if ($option.length > 0) {
        $option.addClass('highlighted');
        const optionEl = $option.get(0);
        if (optionEl && typeof optionEl.scrollIntoView === 'function') {
            optionEl.scrollIntoView({ block: 'nearest' });
        }
    }
}

function selectOption ($wrapper: JQuery, $option: JQuery): void {
    const select = $wrapper.find('select.searchable-select-native')[0] as HTMLSelectElement;
    select.value = $option.attr('data-value') as string;
    $(select).trigger('change');
    closeMenu($wrapper);
    $wrapper.find('.searchable-select-toggle').trigger('focus');
}

function getVisibleEnabledOptions ($menu: JQuery): JQuery {
    return $menu.find('.searchable-select-option:not(.d-none):not(.disabled)');
}

function openMenu ($wrapper: JQuery): void {
    closeOtherMenus($wrapper);

    const select = $wrapper.find('select.searchable-select-native')[0] as HTMLSelectElement;
    /* Give lazily-populated selects (e.g. bound to a native "focus" handler) a chance to fill their options */
    $(select).trigger('focus');

    buildOptionsList($wrapper);
    const $menu = getMenu($wrapper);
    $menu.find('.searchable-select-search').val('');
    positionMenu($wrapper, $menu);
    $wrapper.addClass('show');
    $wrapper.find('.searchable-select-toggle').attr('aria-expanded', 'true');
    $menu.find('.searchable-select-search').trigger('focus');
}

function getToggleClickHandler () {
    return function (event: JQuery.ClickEvent) {
        event.preventDefault();
        const $wrapper = getWrapper(this);
        if ($wrapper.hasClass('show')) {
            closeMenu($wrapper);
            $(this).trigger('focus');
        } else {
            openMenu($wrapper);
        }
    };
}

function getSearchInputHandler () {
    return function () {
        const $wrapper = getWrapper(this);
        filterOptionsList($wrapper, ($(this).val() as string));
    };
}

function getSearchKeydownHandler () {
    return function (event: JQuery.KeyDownEvent) {
        const $wrapper = getWrapper(this);
        const $menu = getMenu($wrapper);
        const $visible = getVisibleEnabledOptions($menu);
        const $current = $menu.find('.searchable-select-option.highlighted');
        const currentIndex = $current.length > 0 ? $visible.index($current) : -1;

        switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            highlightOption($menu, $visible.eq(Math.min(currentIndex + 1, $visible.length - 1)));
            break;
        case 'ArrowUp':
            event.preventDefault();
            highlightOption($menu, $visible.eq(Math.max(currentIndex - 1, 0)));
            break;
        case 'Enter':
            event.preventDefault();
            if ($current.length > 0) {
                selectOption($wrapper, $current);
            }

            break;
        case 'Escape':
            event.preventDefault();
            closeMenu($wrapper);
            $wrapper.find('.searchable-select-toggle').trigger('focus');
            break;
        case 'Tab':
            closeMenu($wrapper);
            break;
        }
    };
}

function getOptionClickHandler () {
    return function () {
        const $wrapper = getWrapper(this);
        if (! $(this).hasClass('disabled')) {
            selectOption($wrapper, $(this));
        }
    };
}

function getNativeChangeHandler () {
    return function () {
        updateToggleText(getWrapper(this));
    };
}

function getDocumentClickHandler () {
    return function (event: JQuery.ClickEvent) {
        const $target = $(event.target);
        if ($target.closest('.searchable-select').length > 0 || $target.closest('.searchable-select-menu').length > 0) {
            return;
        }

        closeOtherMenus($());
    };
}

function getResizeHandler () {
    return function () {
        closeOtherMenus($());
    };
}

function getScrollHandler () {
    return function (event: Event) {
        /* Scrolling inside the open menu's own option list must not close it */
        if (event.target instanceof Element && event.target.closest('.searchable-select-menu')) {
            return;
        }

        closeOtherMenus($());
    };
}

const toggleClickHandler = getToggleClickHandler();
const searchInputHandler = getSearchInputHandler();
const searchKeydownHandler = getSearchKeydownHandler();
const optionClickHandler = getOptionClickHandler();
const nativeChangeHandler = getNativeChangeHandler();
const documentClickHandler = getDocumentClickHandler();
const resizeHandler = getResizeHandler();
const scrollHandler = getScrollHandler();

export function onloadSearchableSelects (): void {
    enhanceSearchableSelects();
    $(document).on('click', '.searchable-select-toggle', toggleClickHandler);
    $(document).on('input', '.searchable-select-search', searchInputHandler);
    $(document).on('keydown', '.searchable-select-search', searchKeydownHandler);
    $(document).on('click', '.searchable-select-option', optionClickHandler);
    $(document).on('change', 'select.searchable-select-native', nativeChangeHandler);
    $(document).on('click', documentClickHandler);
    /* scroll does not bubble, so this must be bound on the capture phase to catch scrollable ancestors */
    document.addEventListener('scroll', scrollHandler, true);
    window.addEventListener('resize', resizeHandler);
}

export function teardownSearchableSelects (): void {
    $(document).off('click', '.searchable-select-toggle', toggleClickHandler);
    $(document).off('input', '.searchable-select-search', searchInputHandler);
    $(document).off('keydown', '.searchable-select-search', searchKeydownHandler);
    $(document).off('click', '.searchable-select-option', optionClickHandler);
    $(document).off('change', 'select.searchable-select-native', nativeChangeHandler);
    $(document).off('click', documentClickHandler);
    document.removeEventListener('scroll', scrollHandler, true);
    window.removeEventListener('resize', resizeHandler);
}
