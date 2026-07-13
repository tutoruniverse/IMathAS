<?php
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\Binary\ByteBuffer;

require_once __DIR__ . '/webauthn/WebAuthn.php';

class PasskeyManager {
    private $webauthn;
    private $rpId;
    private $rpName;
    
    public function __construct($rpId, $rpName) {
        $this->rpId = $rpId;
        $this->rpName = $rpName;
        $this->webauthn = new WebAuthn($rpName, $rpId);
    }
    
    /**
     * Generate a registration challenge for passkey enrollment
     * Stores challenge in session for verification
     */
    public function getRegistrationChallenge($userid, $username, $userfullname) {
        global $DBH;

        // exclude already-registered credentials so the same authenticator can't be added twice
        $excludeCredentials = [];
        $stm = $DBH->prepare("SELECT credential_id FROM imas_passkeys WHERE user_id = :user_id");
        $stm->execute([':user_id' => $userid]);
        while ($credId = $stm->fetchColumn()) {
            $excludeCredentials[] = ByteBuffer::fromBase64Url($credId);
        }

        $args = $this->webauthn->getCreateArgs($userid, $username, $userfullname, 20, true, true, null, $excludeCredentials);
        $_SESSION['passkey_registration_challenge'] = $this->webauthn->getChallenge()->getBinaryString();;
        return $args;
    }
    
    /**
     * Verify passkey registration response
     */
    public function verifyRegistration($attestationObject, $clientDataJSON, $userId) {
        global $DBH;
        
        if (empty($_SESSION['passkey_registration_challenge'])) {
            throw new Exception('No registration challenge found');
        }

        try {
            $data = $this->webauthn->processCreate(
                base64_decode($clientDataJSON), 
                base64_decode($attestationObject), 
                $_SESSION['passkey_registration_challenge'],
                true, true, false
            );
        } catch (Exception $e) {
            throw new Exception('Registration verification failed: ' . $e->getMessage());
        }

        $stm = $DBH->prepare("
            INSERT INTO imas_passkeys (user_id, credential_id, public_key)
            VALUES (:user_id, :credential_id, :public_key)
        ");
        $stm->execute([
            ':user_id' => $userId,
            // stored base64url (no padding) to match the browser's PublicKeyCredential.id encoding
            ':credential_id' => rtrim(strtr(base64_encode($data->credentialId), '+/', '-_'), '='),
            ':public_key' => $data->credentialPublicKey
        ]);
        
        unset($_SESSION['passkey_registration_challenge']);
        return true;
    }
    
    /**
     * Generate an assertion challenge for login
     */
    public function getAssertionChallenge($username) {
        global $DBH;
        
        // Get all passkeys for this user (only if username is not 'silent')
        $allowCredentials = [];
        if ($username !== 'silent') {
            $stm = $DBH->prepare("
                SELECT u.id, p.credential_id 
                FROM imas_passkeys p
                JOIN imas_users u ON p.user_id = u.id
                WHERE u.SID = :username
            ");
            $stm->execute([':username' => $username]);
            
            while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                // credential_id is stored base64url-encoded; decode back to raw bytes
                $allowCredentials[] = ByteBuffer::fromBase64Url($row['credential_id']);
            }
        }
        // If username is 'silent', return empty allowCredentials to allow any registered passkey

        $args = $this->webauthn->getGetArgs($allowCredentials, 30, true, true, true, true, true, true);
        $_SESSION['passkey_assertion_challenge'] = $this->webauthn->getChallenge()->getBinaryString();
        $_SESSION['passkey_assertion_username'] = $username;
        
        return $args;
    }
    
    /**
     * Verify passkey assertion during login
     */
    public function verifyAssertion($credentialId, $clientDataJSON, $signature, $authenticatorData, $username = null) {
        global $DBH;

        if (empty($_SESSION['passkey_assertion_challenge'])) {
            throw new Exception('No assertion challenge found');
        }

        // Get stored public key - first try by username, then by userHandle
        $stm = $DBH->prepare("
            SELECT p.id, p.public_key, p.sign_count, u.id as user_id, u.SID
            FROM imas_passkeys p
            JOIN imas_users u ON p.user_id = u.id
            WHERE p.credential_id = :credential_id
        ");
        $stm->execute([':credential_id' => $credentialId]);

        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception('Passkey not found');
        }
        $row['sign_count'] = (int)$row['sign_count'];

    // If username was provided, verify it matches
        if ($username && $username !== 'silent' && $row['SID'] !== $username) {
            throw new Exception('Username does not match passkey');
        }
        
        try {
            $this->webauthn->processGet(
                base64_decode($clientDataJSON),
                base64_decode($authenticatorData),
                base64_decode($signature),
                $row['public_key'],
                $_SESSION['passkey_assertion_challenge'],
                $row['sign_count'],
                true
            );
        } catch (Exception $e) {
            throw new Exception('Assertion verification failed: ' . $e->getMessage());
        }

        // Update stored signature counter (used to detect cloned authenticators on future logins)
        $newSignCount = $this->webauthn->getSignatureCounter();
        if ($newSignCount !== null && $newSignCount !== $row['sign_count']) {
            $stm = $DBH->prepare("UPDATE imas_passkeys SET sign_count = :sign_count WHERE id = :id");
            $stm->execute([
                ':sign_count' => $newSignCount,
                ':id' => $row['id']
            ]);
        }

        unset($_SESSION['passkey_assertion_challenge']);
        unset($_SESSION['passkey_assertion_username']);

        return $row['user_id'];
    }

    /**
     * Get all passkeys for a user
     */
    public function getUserPasskeys($userId) {
        global $DBH;
        
        $stm = $DBH->prepare("
            SELECT id, credential_id, created_at
            FROM imas_passkeys
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");
        $stm->execute([':user_id' => $userId]);
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete a passkey
     */
    public function deletePasskey($passkeyId, $userId) {
        global $DBH;
        
        $stm = $DBH->prepare("
            DELETE FROM imas_passkeys
            WHERE id = :id AND user_id = :user_id
        ");
        return $stm->execute([
            ':id' => $passkeyId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Check if user has any passkeys
     */
    public function userHasPasskeys($userId) {
        global $DBH;
        
        $stm = $DBH->prepare("
            SELECT COUNT(*) FROM imas_passkeys WHERE user_id = :user_id
        ");
        $stm->execute([':user_id' => $userId]);
        return $stm->fetchColumn(0) > 0;
    }
}
