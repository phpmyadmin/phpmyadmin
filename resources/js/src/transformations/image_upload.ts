import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';

/**
 * Image upload transformations plugin js
 *
 * @package PhpMyAdmin
 */

AJAX.registerOnload('transformations/image_upload.js', function () {
    // Change thumbnail when image file is selected
    // through file upload dialog
    $('input.image-upload').on('change', function () {
        const fileInput = this as HTMLInputElement;
        if (fileInput.files && fileInput.files[0]) {
            var reader = new FileReader();
            var $input = $(this);
            reader.onload = function (e) {
                $input.prevAll('img').attr('src', (e.target.result as string));
            };

            reader.readAsDataURL(fileInput.files[0]);
        }
    });
});

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('transformations/image_upload.js', function () {
    $('input.image-upload').off('change');
});
