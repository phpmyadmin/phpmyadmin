declare global {
    interface Window {
        allowThirdPartyFraming: boolean | string;
    }
}

/**
 * Conditionally included if framing is not allowed.
 */
const crossFramingProtection = (): void => {
    if (window.allowThirdPartyFraming) {
        return;
    }

    if (window.self !== window.top) {
        window.top.location = window.self.location;

        return;
    }

    const styleElement = document.getElementById('cfs-style');
    // check if styleElement has already been removed to avoid frequently reported js error
    if (typeof (styleElement) === 'undefined' || styleElement === null) {
        return;
    }

    styleElement.parentNode.removeChild(styleElement);
};

export { crossFramingProtection };
