async function checkForPasskey() {
  // Bail out if WebAuthn isn't supported
  if (!window.fetch || !navigator.credentials || !navigator.credentials.create) return;

  $("#passkeyentry").show();
}

function showPasskeyError(msg) {
  const el = document.getElementById('passkeyerror');
  if (el) {
    el.textContent = msg;
    el.style.display = '';
  } else {
    alert(msg);
  }
}

async function loginWithPasskey() {
  const errEl = document.getElementById('passkeyerror');
  if (errEl) { errEl.style.display = 'none'; }
  try {
    // Fetch a challenge from your PHP backend
    const resp = await fetch(imasroot+'/actions.php?action=getPasskeyChallenge', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ username: 'silent' })
    });
    const options = await resp.json();

    // Convert base64 challenge to ArrayBuffer
    bta(options);

    const credential = await navigator.credentials.get(options);

    if (credential) {
      // We found a passkey
      // Prepare form data
      document.getElementById('passkeyCredentialId').value = credential.id;
      document.getElementById('passkeyClientDataJSON').value = arrayBufferToBase64(credential.response.clientDataJSON);
      document.getElementById('passkeySignature').value = arrayBufferToBase64(credential.response.signature);
      document.getElementById('passkeyAuthenticatorData').value = arrayBufferToBase64(credential.response.authenticatorData);

      // Submit form with passkey data
      document.getElementById('username').form.submit();
    } else {
      showPasskeyError('No Passkey found. Login with your username and password instead. You can add a passkey in your user profile after logging in.');
    }
  } catch(e) {
    showPasskeyError('Unable to use passkey. Login with your username and password instead. You can add a passkey in your user profile after logging in.');
  }
}

function bta (o) {
    let pre = "=?BINARY?B?", suf = "?=";
    for (let k of Object.keys(o)) {
        if (typeof o[k] == "string") {
            let s = o[k];
            if (s.startsWith(pre) && s.endsWith(suf)) {
            let raw = window.atob(s.slice(pre.length, -suf.length)),
                u = new Uint8Array(raw.length);
            for (let i = 0; i < raw.length; i++) u[i] = raw.charCodeAt(i);
            o[k] = u.buffer;
            }
        } else {
            bta(o[k]);
        }
    }
}
function arrayBufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode( bytes[ i ] );
    }
    return window.btoa(binary);
}
document.addEventListener('DOMContentLoaded', checkForPasskey);