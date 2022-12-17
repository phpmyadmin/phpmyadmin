const base64UrlDecode = (input) => {
    // eslint-disable-next-line no-param-reassign
    input = input.replace(/-/g, '+').replace(/_/g, '/');

    const pad = input.length % 4;
    if (pad) {
        if (pad === 1) {
            throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
        }
        // eslint-disable-next-line no-param-reassign
        input += new Array(5 - pad).join('=');
    }

    return window.atob(input);
};

const arrayToBase64String = (a) => window.btoa(String.fromCharCode(...a));

const preparePublicKeyOptions = publicKey => {
    // Convert challenge from Base64Url string to Uint8Array
    publicKey.challenge = Uint8Array.from(
        base64UrlDecode(publicKey.challenge),
        c => c.charCodeAt(0)
    );

    // Convert the user ID from Base64 string to Uint8Array
    if (publicKey.user !== undefined) {
        publicKey.user = {
            ...publicKey.user,
            id: Uint8Array.from(
                window.atob(publicKey.user.id),
                c => c.charCodeAt(0)
            ),
        };
    }

    // If excludeCredentials is defined, we convert all IDs to Uint8Array
    if (publicKey.excludeCredentials !== undefined) {
        publicKey.excludeCredentials = publicKey.excludeCredentials.map(
            data => {
                return {
                    ...data,
                    id: Uint8Array.from(
                        base64UrlDecode(data.id),
                        c => c.charCodeAt(0)
                    ),
                };
            }
        );
    }

    if (publicKey.allowCredentials !== undefined) {
        publicKey.allowCredentials = publicKey.allowCredentials.map(
            data => {
                return {
                    ...data,
                    id: Uint8Array.from(
                        base64UrlDecode(data.id),
                        c => c.charCodeAt(0)
                    ),
                };
            }
        );
    }

    return publicKey;
};

const preparePublicKeyCredentials = data => {
    const publicKeyCredential = {
        id: data.id,
        type: data.type,
        rawId: arrayToBase64String(new Uint8Array(data.rawId)),
        response: {
            clientDataJSON: arrayToBase64String(
                new Uint8Array(data.response.clientDataJSON)
            ),
        },
    };

    if (data.response.attestationObject !== undefined) {
        publicKeyCredential.response.attestationObject = arrayToBase64String(
            new Uint8Array(data.response.attestationObject)
        );
    }

    if (data.response.authenticatorData !== undefined) {
        publicKeyCredential.response.authenticatorData = arrayToBase64String(
            new Uint8Array(data.response.authenticatorData)
        );
    }

    if (data.response.signature !== undefined) {
        publicKeyCredential.response.signature = arrayToBase64String(
            new Uint8Array(data.response.signature)
        );
    }

    if (data.response.userHandle !== undefined) {
        publicKeyCredential.response.userHandle = arrayToBase64String(
            new Uint8Array(data.response.userHandle)
        );
    }

    return publicKeyCredential;
};

const createPublicKeyCredential = async function (publicKey) {
    // eslint-disable-next-line compat/compat
    const credentials = await navigator.credentials.create({ publicKey: publicKey });

    return preparePublicKeyCredentials(credentials);
};

const getPublicKeyCredential = async function (publicKey) {
    // eslint-disable-next-line compat/compat
    const credentials = await navigator.credentials.get({ publicKey: publicKey });

    return preparePublicKeyCredentials(credentials);
};

AJAX.registerOnload('webauthn.js', function () {
    const $inputReg = $('#webauthn_registration_response');
    if ($inputReg.length > 0) {
        const $formReg = $inputReg.parents('form');
        $formReg.find('input[type=submit]').hide();

        const webauthnOptionsJson = $inputReg.attr('data-webauthn-options');
        const webauthnOptions = JSON.parse(webauthnOptionsJson);
        const publicKey = preparePublicKeyOptions(webauthnOptions);
        const publicKeyCredential = createPublicKeyCredential(publicKey);
        publicKeyCredential
            .then((data) => {
                $inputReg.val(JSON.stringify(data));
                $formReg.trigger('submit');
            })
            .catch((error) => Functions.ajaxShowMessage(error, false, 'error'));
    }

    const $inputAuth = $('#webauthn_authentication_response');
    if ($inputAuth.length > 0) {
        const $formAuth = $inputAuth.parents('form');
        $formAuth.find('input[type=submit]').hide();

        const webauthnRequestJson = $inputAuth.attr('data-webauthn-request');
        const webauthnRequest = JSON.parse(webauthnRequestJson);
        const publicKey = preparePublicKeyOptions(webauthnRequest);
        const publicKeyCredential = getPublicKeyCredential(publicKey);
        publicKeyCredential
            .then((data) => {
                $inputAuth.val(JSON.stringify(data));
                $formAuth.trigger('submit');
            })
            .catch((error) => Functions.ajaxShowMessage(error, false, 'error'));
    }
});
