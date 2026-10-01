# Nginx production vhost

`enersim.conf.example` is a deployment template for the production hostname
`enersim.ro` and `www.enersim.ro`. The current public endpoint resolves to an
Nginx server, but this repository does not contain its live configuration.

Before enabling the template on that host:

1. Confirm the SSH host-key fingerprint against a trusted provider console.
2. Confirm the deployment directory and PHP-FPM socket, then update the example.
3. Obtain a trusted certificate covering both hostnames before enabling the TLS
   servers. HTTP requests will redirect to the canonical HTTPS hostname.
4. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://enersim.ro`, and
   `SESSION_SECURE_COOKIE=true` in the deployment environment.
5. Run `nginx -t` and reload Nginx; verify redirects, certificate validity, and
   response headers over HTTPS.

HSTS is sent only on the TLS virtual hosts and intentionally omits
`includeSubDomains` until all subdomains are confirmed HTTPS-capable. CSP is not
set here because the app currently uses inline scripts and third-party font
origins; it requires a separate nonce/hash and asset-origin review.

Pull-request and `main` CI continues to run `composer audit --locked` and
`npm audit`.
