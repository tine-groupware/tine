# MFA configuration

Version: Liva 2025.11

## Overview

Tine supports multi-factor authentication (MFA) to add an extra layer of security beyond the standard password login. MFA can be configured at the server level and then applied to specific areas (login, apps, data-safe) using area locks.

Tine supports the following MFA providers out of the box:

| Provider | ID | Description |
|----------|----|-------------|
| Authenticator App | `Authenticator App` | Time-based one-time password (TOTP) compatible with Google Authenticator, Authy, etc. |
| Passkey (WebAuthn/FIDO2) | `Passkey` | Passwordless or second-factor authentication using hardware keys, biometrics, or device PINs via WebAuthn |
| PIN | `PIN` | A static user-defined PIN code |
| YubiKey OTP | `YUBICO` | YubiKey hardware token authentication using Yubico OTP protocol |
| Generic SMS | `Generic SMS` | Sends a time-limited PIN code via SMS to the user's phone |
| Mock SMS | `Mock SMS` | Development/testing adapter that emails the PIN code instead of sending SMS |

## Configuration options

### `mfa` — MFA providers

Defines the available MFA providers and their server-level configuration. Each record represents one MFA method.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | string | Yes | Unique identifier for this MFA provider. Must match the IDs referenced in `areaLocks.mfas`. |
| `allow_self_service` | boolean | Yes | Whether users can enable/disable this MFA method themselves via their user settings. |
| `allow_pwd_less_login` | boolean | No (default: `false`) | Whether this MFA provider can be used for passwordless login (no password required at login). Only applies to providers like Passkey. |
| `provider_config_class` | string | Yes | Fully qualified class name for the provider configuration model (server-level settings). |
| `provider_config` | array/object | Yes | Provider-specific configuration, validated against the `provider_config_class`. |
| `provider_class` | string | Yes | Fully qualified class name of the MFA adapter that handles send/validate logic. |
| `user_config_class` | string | Yes | Fully qualified class name for the per-user device configuration model. |

#### Available MFA adapters and their configs

##### TOTP / HOTP (Authenticator App)

- **Adapter:** `Tinebase_Auth_MFA_HTOTPAdapter`
- **Config class:** `Tinebase_Model_MFA_TOTPConfig` (empty config, no server-level options)
- **User config class:** `Tinebase_Model_MFA_TOTPUserConfig`
- **Password length:** 6 digits
- **How it works:** Generates a secret key displayed as a QR code. Users scan it with their authenticator app (Google Authenticator, Authy, etc.). Each code is time-based (30s window) and single-use (codes are tracked to prevent replay within a 10-code window).
- **HOTP support:** Also supports counter-based HOTP (e.g., for hardware tokens). The counter auto-advances with an 8-code search window to handle sync drift.

##### Passkey (WebAuthn / FIDO2)

- **Adapter:** `Tinebase_Auth_MFA_WebAuthnAdapter`
- **Config class:** `Tinebase_Model_MFA_WebAuthnConfig`
- **User config class:** `Tinebase_Model_MFA_WebAuthnUserConfig`
- **Password length:** N/A (uses biometrics/hardware key)
- **How it works:** Uses the WebAuthn/FIDO2 standard. Users can register hardware security keys (YubiKey, etc.), or use built-in platform authenticators (Face ID, Touch ID, Windows Hello, device PIN).
- **Provider config options:**

| Field | Type | Description |
|-------|------|-------------|
| `authenticator_attachment` | string\|null | Restricts registration to a specific attachment mode: `null` (any), `'platform'` (built-in like Face ID), or `'cross-platform'` (roaming like YubiKey). `null` is recommended for maximum flexibility. |
| `user_verification_requirement` | string | User verification policy: `'required'` (forces verification — note: Firefox cannot check this but still works for passwordless), `'preferred'` (default), or `'discouraged'`. |
| `resident_key_requirement` | string | Resident key (client-side) policy: `null` (default), `'required'`, `'preferred'`, or `'discouraged'`. Controls whether the authenticator stores the credential locally. |

##### PIN

- **Adapter:** `Tinebase_Auth_MFA_PinAdapter`
- **Config class:** `Tinebase_Model_MFA_PinConfig` (empty config, no server-level options)
- **User config class:** `Tinebase_Model_MFA_PinUserConfig`
- **Password length:** N/A (user-defined)
- **How it works:** Users define a static PIN code. The PIN is hashed for storage. Simple but effective for basic second-factor protection.

##### YubiKey OTP

- **Adapter:** `Tinebase_Auth_MFA_YubicoOTPAdapter`
- **Config class:** `Tinebase_Model_MFA_YubicoOTPConfig` (empty config, no server-level options)
- **User config class:** `Tinebase_Model_MFA_YubicoOTPUserConfig`
- **Password length:** N/A (YubiKey outputs a fixed-length string when touched)
- **How it works:** Uses Yubico OTP protocol. Users press their YubiKey in a USB NFC slot. The adapter decodes the modhex-encoded OTP, validates the public ID, and verifies the AES-decrypted plaintext contains the expected username. Counter and session tracking prevent replay attacks.
- **Setup:** Requires YubiKey personalization tool to configure the key with the appropriate AES key and public ID.

##### Generic SMS

- **Adapter:** `Tinebase_Auth_MFA_GenericSmsAdapter`
- **Config class:** `Tinebase_Model_MFA_GenericSmsConfig`
- **User config class:** `Tinebase_Model_MFA_SmsUserConfig`
- **Password length:** Configurable (see `pin_length`)
- **How it works:** Generates a random PIN and sends it via SMS using a generic HTTP SMS adapter. The PIN is stored in the session with a TTL for validation.
- **Provider config options:**

| Field | Type | Description |
|-------|------|-------------|
| `system_sms` | string | Name of an existing SMS adapter from the `sms.adapters` configuration. If empty, falls back to inline config below. |
| `url` | string | HTTP endpoint URL for sending SMS (used when no `system_sms` is configured). |
| `method` | string | HTTP method for the SMS request (e.g., `GET`, `POST`). |
| `body` | string | Request body template for the SMS. Supports Twig templating with `{{ code }}` for the PIN. |
| `headers` | array | HTTP headers to include in the SMS request. |
| `pin_length` | integer | Length of the generated PIN code (3–10 digits). |
| `pin_ttl` | integer | Time-to-live in seconds for the generated PIN code in the session. |

- **Message template:** The SMS body defaults to `"{{ code }} is your {{ app.branding.title }} security code."` followed by `"@{{ app.websiteUrl }} {{ code }}"`. Twig filters `alnum`, `gsm7`, and `ucs2` are available.

##### Mock SMS (testing only)

- **Adapter:** `Tinebase_Auth_MFA_MockSmsAdapter`
- **Config class:** `Tinebase_Model_MFA_GenericSmsConfig`
- **User config class:** `Tinebase_Model_MFA_SmsUserConfig`
- **How it works:** Extends the Generic SMS adapter but intercepts the SMS request and sends the PIN code via email instead (to `tine20admin@mail.test`). Useful for development and testing without a real SMS gateway.
- **Note:** Automatically uses an existing SMS adapter configuration if available.

### `areaLocks` — Area-based MFA enforcement

Area locks define which areas of Tine require additional MFA authentication and for how long the authentication remains valid.

#### Structure

Each area lock record has the following fields:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `area_name` | string | Yes | Human-readable name for this area lock rule. |
| `areas` | array | Yes | List of area patterns to match. Supports hierarchical matching (e.g., `'Calendar.saveEvent'` matches `'Calendar.*'`). |
| `mfas` | array | Yes | List of MFA provider IDs (must match IDs defined in the `mfa` config). |
| `validity` | string | Yes | How long the MFA validation remains valid. See [Validity modes](#validity-modes) below. |
| `lifetime` | integer | Conditional | Lifetime in minutes. Required for `lifetime` validity, optional for `presence` (defaults to 15 minutes). |
| `policy` | string | No | Enforcement policy: `'required'` (hard block) or `'encouraged'` (soft prompt). Default: `'encouraged'`. |

#### Validity modes

| Mode | Constant | Description |
|------|----------|-------------|
| `session` | `VALIDITY_SESSION` | MFA remains valid until the user session ends (fixed until year 2150). |
| `lifetime` | `VALIDITY_LIFETIME` | MFA remains valid for a fixed duration from the time of validation. Requires `lifetime` field (default: 15 minutes). |
| `presence` | `VALIDITY_PRESENCE` | MFA validity is relative to the user's last activity (presence recording). Requires `lifetime` field (default: 15 minutes). The user must be actively using Tine for the lock to remain valid. |

Note: The `once` validity mode exists in the model but is not yet supported by any backend.

#### Area matching

Areas use hierarchical dot-notation matching. An area lock with `areas: ['Calendar.*']` will match:
- `Calendar.saveEvent`
- `Calendar.deleteEvent`
- `Calendar.*` (any Calendar sub-action)

An area lock with `areas: ['Calendar']` will match all Calendar operations.

#### Predefined areas

| Area constant | Value | Description |
|---------------|-------|-------------|
| `AREA_LOGIN` | `'Tinebase_login'` | The login page / authentication screen |
| `AREA_DATASAFE` | `'Tinebase_datasafe'` | Data-safe operations (sensitive data access) |

#### Providers

Area locks support the following provider types for re-authentication:

| Provider | Value | Description |
|----------|-------|-------------|
| PIN | `'pin'` | Re-authentication with a PIN |
| User Password | `'userpassword'` | Re-authentication with the user's main password |
| Token | `'token'` | Re-authentication with an app password / token |

#### Example configurations

**Require MFA at login, valid for the entire session:**

```php
'areaLocks' => [
    'records' => [[
        'area_name' => 'login',
        'areas' => ['Tinebase_login'],
        'mfas' => ['Authenticator App', 'FIDO2'],
        'validity' => 'session',
    ]]
]
```

**Require MFA for Filemanager access, valid for 1 hour:**

```php
'areaLocks' => [
    'records' => [[
        'area_name' => 'app lock',
        'areas' => ['Filemanager'],
        'mfas' => ['Authenticator App', 'Passkey'],
        'validity' => 'lifetime',
        'lifetime' => 3600,
    ]]
]
```

**Require MFA for sensitive Calendar operations, based on presence:**

```php
'areaLocks' => [
    'records' => [[
        'area_name' => 'calendar save',
        'areas' => ['Calendar.saveEvent'],
        'mfas' => ['Passkey'],
        'validity' => 'presence',
        'lifetime' => 30, // 30 minutes of active presence
    ]]
]
```

### `mfa_bypass_netmasks` — Network-based MFA bypass

Allows skipping MFA for requests originating from specific IP ranges. Useful for internal networks where MFA is handled by other means (e.g., network-level authentication).

| Field | Type | Description |
|-------|------|-------------|
| `mfa_bypass_netmasks` | array | Array of CIDR-notation netmasks (e.g., `'10.0.0.0/8'`, `'192.168.1.0/24'`, `'::1/128'` for IPv6). |

Example:

```php
'mfa_bypass_netmasks' => [
    '10.0.0.0/8',       // Private network A
    '192.168.0.0/16',   // Private network B
    '172.16.0.0/12',    // Private network C
    '::1/128',          // IPv6 localhost
],
```

### `mfa_encourage` — Encourage MFA setup at login

When set to `true`, users without any MFA device configured will be prompted at login to set up an MFA method.

| Type | Default | Settable by |
|------|---------|-------------|
| boolean | `false` | Admin module, Setup module |

```php
'mfa_encourage' => true,
```

Users can dismiss the prompt, and it will reappear on the next login. A preference should be added to allow users to suppress the prompt permanently.

## Complete example configuration

Here is a comprehensive configuration covering multiple MFA providers and area locks:

```php
<?php
return [
    // Allow MFA-free access from internal networks
    'mfa_bypass_netmasks' => [
        '10.0.0.0/8',
        '192.168.0.0/16',
    ],

    // Prompt users without MFA to set one up
    'mfa_encourage' => true,

    // Define available MFA providers
    'mfa' => [
        'records' => [
            // TOTP via authenticator app
            [
                'id'                    => 'Authenticator App',
                'allow_self_service'    => true,
                'provider_config_class' => 'Tinebase_Model_MFA_TOTPConfig',
                'provider_config'       => [],
                'provider_class'        => 'Tinebase_Auth_MFA_HTOTPAdapter',
                'user_config_class'     => 'Tinebase_Model_MFA_TOTPUserConfig'
            ],

            // WebAuthn / FIDO2 passkeys
            [
                'id'                    => 'Passkey',
                'allow_self_service'    => true,
                'allow_pwd_less_login'  => true, // enables passwordless login
                'provider_config_class' => 'Tinebase_Model_MFA_WebAuthnConfig',
                'provider_config'       => [
                    'authenticator_attachment' => null,
                    'user_verification_requirement' => 'preferred',
                    'resident_key_requirement' => 'preferred',
                ],
                'provider_class'        => 'Tinebase_Auth_MFA_WebAuthnAdapter',
                'user_config_class'     => 'Tinebase_Model_MFA_WebAuthnUserConfig'
            ],

            // Static PIN
            [
                'id'                    => 'PIN',
                'allow_self_service'    => true,
                'provider_config_class' => 'Tinebase_Model_MFA_PinConfig',
                'provider_config'       => [],
                'provider_class'        => 'Tinebase_Auth_MFA_PinAdapter',
                'user_config_class'     => 'Tinebase_Model_MFA_PinUserConfig'
            ],

            // YubiKey OTP
            [
                'id'                    => 'YUBICO',
                'allow_self_service'    => true,
                'provider_config_class' => 'Tinebase_Model_MFA_YubicoOTPConfig',
                'provider_config'       => [],
                'provider_class'        => 'Tinebase_Auth_MFA_YubicoOTPAdapter',
                'user_config_class'     => 'Tinebase_Model_MFA_YubicoOTPUserConfig'
            ],

            // SMS-based OTP
            [
                'id'                    => 'Generic SMS',
                'allow_self_service'    => true,
                'provider_config_class' => 'Tinebase_Model_MFA_GenericSmsConfig',
                'provider_config'       => [
                    'system_sms' => 'my-sms-gateway', // name from sms.adapters config
                    'pin_length' => 6,
                    'pin_ttl' => 300, // 5 minutes
                ],
                'provider_class'        => 'Tinebase_Auth_MFA_GenericSmsAdapter',
                'user_config_class'     => 'Tinebase_Model_MFA_SmsUserConfig'
            ],
        ]
    ],

    // Apply MFA to specific areas
    'areaLocks' => [
        'records' => [
            // MFA required at login, valid for entire session
            [
                'area_name' => 'login',
                'areas' => ['Tinebase_login'],
                'mfas' => ['Authenticator App', 'Passkey', 'PIN', 'YUBICO'],
                'validity' => 'session',
                'policy' => 'required',
            ],

            // MFA required for CRM operations, re-validate every 30 min of inactivity
            [
                'area_name' => 'crm sensitive',
                'areas' => ['Crm'],
                'mfas' => ['Passkey', 'Authenticator App'],
                'validity' => 'presence',
                'lifetime' => 30,
                'policy' => 'encouraged',
            ],
        ]
    ],
];
```

## Architecture

Tine's MFA system uses a dual-layer design:

1. **Server-level Config** — Defines available MFA providers and their global settings (`Tinebase_Model_MFA_Config`).
2. **Per-user UserConfig** — Stores individual user device registrations (`Tinebase_Model_MFA_UserConfig`), stored encrypted in the user's `mfa_configs` field.

The `Tinebase_Auth_MFA` facade manages provider instances, validation, and bypass checks. Each provider implements `Tinebase_Auth_MFA_AdapterInterface` with `sendOut()` and `validate()` methods. Secrets (TOTP keys, YubiKey AES keys, etc.) are stored and encrypted via the `CredentialCache` system.

Area locks use dedicated backends for validity tracking:
- `Tinebase_AreaLock_Session` — Session-based or fixed-lifetime validity
- `Tinebase_AreaLock_Presence` — Activity-based validity using the Presence API

## Related documentation

- [Passkey Login (User Guide)](../users/PasskeyLogin.md) — How users set up and use Passkey authentication
- [SSO Integration](Setup_SSO.md) — MFA integration with SAML-based single sign-on
