<?php declare(strict_types=1);

use Base64Url\Base64Url;

class Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository
{

    public function findOneByCredentialId(string $publicKeyCredentialId): ?\Webauthn\CredentialRecord
    {
        $publicKeyCredentialId = Base64Url::encode($publicKeyCredentialId);
        // strlen is fine since its base64, so only ascii!
        if (strlen($publicKeyCredentialId) > 255) {
            throw new Tinebase_Exception_UnexpectedValue('publicKeyCredentialId base64 encoded is longer than 255');
        }
        $webauthnPublicKey = Tinebase_Controller_WebauthnPublicKey::getInstance()
            ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebauthnPublicKey::class, [
                ['field' => Tinebase_Model_WebauthnPublicKey::FLD_KEY_ID, 'operator' => 'equals', 'value' => $publicKeyCredentialId]
            ]))->getFirstRecord();
        if (null === $webauthnPublicKey) {
            return null;
        }
        return Tinebase_Auth_Webauthn::deserializePublicKeyCredentialRecord(
            json_encode($webauthnPublicKey->{Tinebase_Model_WebauthnPublicKey::FLD_DATA}));
    }

    /**
     * @param \Webauthn\PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity
     * @return array<\Webauthn\CredentialRecord>
     */
    public function findAllForUserEntity(\Webauthn\PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        if (mb_strlen($publicKeyCredentialUserEntity->id) > 40) {
            throw new Tinebase_Exception_UnexpectedValue('user entities id is longer than 40');
        }
        $result = [];
        foreach (Tinebase_Controller_WebauthnPublicKey::getInstance()
                ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebauthnPublicKey::class, [
                    ['field' => Tinebase_Model_WebauthnPublicKey::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $publicKeyCredentialUserEntity->id]
                ])) as $webauthnPublicKey) {
            $result[] = Tinebase_Auth_Webauthn::deserializePublicKeyCredentialRecord(
                json_encode($webauthnPublicKey->{Tinebase_Model_WebauthnPublicKey::FLD_DATA}));
        }

        return $result;
    }

    public function saveCredentialRecord(\Webauthn\CredentialRecord $publicKeyCredentialRecord): void
    {
        if (mb_strlen($publicKeyCredentialRecord->userHandle) > 40) {
            throw new Tinebase_Exception_UnexpectedValue('user handle is longer than 40');
        }
        $credId = Base64Url::encode($publicKeyCredentialRecord->publicKeyCredentialId);
        // strlen is fine since its base64, so only ascii!
        if (strlen($credId) > 255) {
            throw new Tinebase_Exception_UnexpectedValue('publicKeyCredentialId base64 encoded is longer than 255');
        }

        if (null === ($webauthnPublicKey = Tinebase_Controller_WebauthnPublicKey::getInstance()
            ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebauthnPublicKey::class, [
                ['field' => Tinebase_Model_WebauthnPublicKey::FLD_KEY_ID, 'operator' => 'equals', 'value' => $credId],
                ['field' => Tinebase_Model_WebauthnPublicKey::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $publicKeyCredentialRecord->userHandle]
            ]))->getFirstRecord())) {

            Tinebase_Controller_WebauthnPublicKey::getInstance()->create(new Tinebase_Model_WebauthnPublicKey([
                Tinebase_Model_WebauthnPublicKey::FLD_ACCOUNT_ID => $publicKeyCredentialRecord->userHandle,
                Tinebase_Model_WebauthnPublicKey::FLD_KEY_ID => $credId,
                Tinebase_Model_WebauthnPublicKey::FLD_DATA => json_decode(Tinebase_Auth_Webauthn::serializePublicKeyCredentialRecord($publicKeyCredentialRecord), true),
            ]));
        } else {
            $webauthnPublicKey->{Tinebase_Model_WebauthnPublicKey::FLD_DATA} = json_decode(Tinebase_Auth_Webauthn::serializePublicKeyCredentialRecord($publicKeyCredentialRecord), true);
            $unsetUser = false;
            try {
                if (!Tinebase_Core::getUser()) {
                    $unsetUser = true;
                    Tinebase_Core::setUser(Tinebase_User::getInstance()->getFullUserById(
                        $publicKeyCredentialRecord->userHandle));
                }
                Tinebase_Controller_WebauthnPublicKey::getInstance()->update($webauthnPublicKey);
            } finally {
                if ($unsetUser) {
                    Tinebase_Core::unsetUser();
                }
            }
        }
    }
}
