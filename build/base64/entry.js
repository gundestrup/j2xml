// Entry point — export a decode() function that returns a UTF-8 string,
// matching the old base64.js API used by j2xml.js.
import { fromBase64 } from '@jsonjoy.com/base64';

// decode(base64String) -> UTF-8 string
// Uses native TextDecoder (available in all modern browsers)
function decode(data) {
    return new TextDecoder('utf-8').decode(fromBase64(data));
}

export { decode };
