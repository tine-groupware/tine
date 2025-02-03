# Passkey Login

> **NOTE TO ADMIN:**  
> To use this feature, the `webauthn` mfa-option has to be enabled on the instance.

## **Description**
Passkey login is a modern authentication method that replaces traditional passwords with cryptographic key pairs,
providing secure and seamless access to websites, apps, and devices.

## **Setup**

> **NOTE:**  
> You can store passkeys on your device, password manager, or physical security-key. However, the process is __different
> for each operating system and may not be available on all systems__.  
> 
> Most **chrome-based browsers** allow storing passkey on a **physical security-key**.

To use this feature, a user must first **set up a `WebAuthn/FIDO2` device** by following these steps:

1. Log in your **tine** account.

2. Click on the **User Avatar** at the top of the **TineBar**.

3. Click on **Manage MFA Devices**. A new window will pop up.

4. Add a new **webauthn (FIDO2 WebAuthn Device)** if one is not already present.

5. Follow the prompts to create a new passkey.

## **Using Passkey for Login**
Once a passkey has been set up, users can log into their **tine** account without entering their username and password.
If **passkey-login** is enabled for the **tine** instance:

- **Clicking the "Username"** field on the `Login` page will display **autofill options**, showing stored **passwords** and
`passkeys`. Selecting a stored `passkey` will log the user into their `tine` account automatically.

- Alternatively, users can **click on `Login with Passkey`** on the `Login` page to choose from stored `passkeys` and log in.