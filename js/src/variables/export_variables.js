import { Variables } from './global_variables';
import { PMA_commonParams } from './common_params';

Variables.setAllMessages(window.PMA_messages);

/**
 * This statement to be placed in the file going to be
 * executed firstly like functions.js
 */
PMA_commonParams.setAll(window.common_params);

export const PMA_Messages = Variables.getMessages();
