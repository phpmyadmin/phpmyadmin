/* eslint-env node, jest */

import $ from 'jquery';
import handleCreateViewModal from '../../../../resources/js/modules/functions/handleCreateViewModal.ts';

describe('handleCreateViewModal', () => {
    const createUrl = 'index.php?route=/view/create';

    beforeEach(() => {
        (window as any).Messages = { strLoading: 'Loading...' };
        document.body.innerHTML = '<a class="create_view ajax" href="index.php?route=/view/create-dialog">Create view</a>' +
            '<div id="createViewModal"><div class="modal-body"></div>' +
            '<div class="modal-footer"><button type="button" id="createViewModalGoButton">Go</button></div></div>';

        ($.fn as any).modal = jest.fn();
        ($.fn as any).getPostData = jest.fn().mockReturnValue('');
        jest.spyOn($, 'post').mockImplementation(((url: string, _params: unknown, callback?: (data: unknown) => void) => {
            if (url !== createUrl && callback) {
                callback({ success: true, message: '<form></form>' });
            }
        }) as any);
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    test('submits the create view form once after the modal is opened twice', () => {
        const link = document.querySelector('a.create_view') as HTMLElement;

        handleCreateViewModal($(link));
        handleCreateViewModal($(link));

        (document.getElementById('createViewModalGoButton') as HTMLButtonElement).click();

        const createCalls = ($.post as unknown as jest.Mock).mock.calls.filter(([url]) => url === createUrl);
        expect(createCalls).toHaveLength(1);
    });
});
