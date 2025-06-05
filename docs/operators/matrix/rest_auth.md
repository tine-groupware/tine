# Synapse Rest Auth

Tine can be the auth backend of matix synapse. There are two options, open id connect and rest auth. Open id connect is preferred. Here we describe how to configure rest auth.

When using rest auth, the user will logs into synapse with the tine username and password. Synapse will verify the password with tine.

Synapse does not support this mode by default. An auth provider module is required. We used to use https://github.com/PeerD/matrix-synapse-rest-aut, but it no longer works. We have our own reimplementation that only supports the tine use case.

1. Install rest auth provider in synapse
```
curl \
    https://raw.githubusercontent.com/tine-groupware/tine/refs/heads/main/docs/operators/matrix/rest_auth_provider.py \
    -o $(python3 -c 'import sysconfig; print(sysconfig.get_paths()["purelib"])')/rest_auth_provider.py
```
2. Configure Synapse to use rest auth provider:
Add 
```
modules:
  - module: rest_auth_provider.RestAuthProvider
    config:
      endpoint: <tine url> # https://tine.example.com
```
3. Configure Matrix Synapse Integrator
```
return [
    'MatrixSynapseIntegrator' => [
        'matrixDomain' => '<matrix server name>'
];
```