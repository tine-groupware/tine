# Synapse Rest Auth

Tine can be the auth backend of matrix synapse. There are two options, open id connect and rest auth. Here we describe how to configure rest auth.

When using rest auth, the user will log into synapse with the tine loginname (or email) or matrix id and password. Synapse will verify the password with tine.

Synapse does not support this mode by default. An auth provider module is required. 

1. Install rest auth provider in synapse
```shell
curl \
    https://raw.githubusercontent.com/tine-groupware/tine/refs/heads/main/docs/operators/matrix/rest_auth_provider.py \
    -o $(python3 -c 'import sysconfig; print(sysconfig.get_paths()["purelib"])')/rest_auth_provider.py
```
2. Configure Synapse to use rest auth provider:
Add 
```yaml
modules:
  - module: rest_auth_provider.RestAuthProvider
    config:
      endpoint: <tine url> # https://tine.example.com
```
3. Configure Matrix Synapse Integrator
Matrix Synapse Integrator, with matrix accounts is required.
```php
return [
    'MatrixSynapseIntegrator' => [
        'matrixDomain' => '<matrix server name>'
];
```